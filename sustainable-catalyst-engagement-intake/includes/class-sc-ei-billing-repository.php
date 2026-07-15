<?php
/**
 * Governed billing, invoice, and payment-handoff persistence.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Billing_Repository {

	public const MIGRATION_KEY = 'v1_7_0_billing_invoicing_payment_handoffs';
	private const OPTION_SCHEMA = 'sc_ei_billing_schema_version';

	public static function register(): void {}

	public static function maybe_upgrade(): void {
		$stored = (string) get_option( self::OPTION_SCHEMA, '' );
		if ( version_compare( $stored, SC_EI_BILLING_SCHEMA_VERSION, '<' ) ) {
			SC_EI_Database::install();
		}
		self::record_migration( $stored );
		if ( ! in_array( false, SC_EI_Database::billing_columns_exist(), true ) ) {
			update_option( self::OPTION_SCHEMA, SC_EI_BILLING_SCHEMA_VERSION, false );
		}
	}

	public static function record_migration( string $from_schema = '' ) {
		global $wpdb;
		$table = SC_EI_Database::table( 'platform_migrations' );
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE migration_key = %s LIMIT 1", self::MIGRATION_KEY ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$columns = SC_EI_Database::billing_columns_exist();
		$ok = ! in_array( false, $columns, true );
		$now = current_time( 'mysql', true );
		$data = array(
			'public_id'     => $existing['public_id'] ?? wp_generate_uuid4(),
			'migration_key' => self::MIGRATION_KEY,
			'from_version'  => sanitize_text_field( $from_schema ),
			'to_version'    => SC_EI_BILLING_SCHEMA_VERSION,
			'status'        => $ok ? 'completed' : 'failed',
			'context_json'  => wp_json_encode(
				array(
					'release'                     => 'Billing, Invoicing, and Payment Handoffs',
					'external_provider_handoffs'  => true,
					'payment_instruments_stored'  => false,
					'human_review_required'       => true,
					'no_automatic_collection'     => true,
					'no_destructive_migration'    => true,
					'missing_contract_items'      => array_keys( array_filter( $columns, static fn( bool $value ): bool => ! $value ) ),
				),
				JSON_UNESCAPED_SLASHES
			),
			'started_at'    => $existing['started_at'] ?? $now,
			'completed_at'  => $ok ? $now : null,
			'error_code'    => $ok ? '' : 'billing_schema_incomplete',
			'error_message' => $ok ? '' : 'The billing database contract is incomplete.',
			'created_at'    => $existing['created_at'] ?? $now,
			'updated_at'    => $now,
		);
		if ( $existing ) {
			$result = $wpdb->update( $table, $data, array( 'id' => absint( $existing['id'] ) ), self::formats( $data ), array( '%d' ) );
			$id = absint( $existing['id'] );
		} else {
			$result = $wpdb->insert( $table, $data, self::formats( $data ) );
			$id = (int) $wpdb->insert_id;
		}
		if ( false === $result ) {
			return new WP_Error( 'billing_migration_failed', __( 'The billing migration journal could not be recorded.', 'sustainable-catalyst-engagement-intake' ) );
		}
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function create_profile( int $engagement_id, array $data, int $actor_user_id ) {
		global $wpdb;
		$engagement = SC_EI_Engagement_Repository::find( $engagement_id );
		if ( ! $engagement ) {
			return new WP_Error( 'billing_engagement_missing', __( 'A governed engagement is required.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$inquiry_id = absint( $engagement['inquiry_id'] ?? 0 );
		$now = current_time( 'mysql', true );
		$payload = array(
			'public_id'                 => wp_generate_uuid4(),
			'inquiry_id'                => $inquiry_id,
			'engagement_id'             => $engagement_id,
			'organization_name'         => sanitize_text_field( $data['organization_name'] ?? '' ),
			'billing_contact_name'      => sanitize_text_field( $data['billing_contact_name'] ?? '' ),
			'billing_contact_email'     => sanitize_email( $data['billing_contact_email'] ?? '' ),
			'billing_address_json'      => wp_json_encode( self::sanitize_address( $data['billing_address'] ?? array() ), JSON_UNESCAPED_SLASHES ),
			'tax_identifier_reference'  => sanitize_text_field( $data['tax_identifier_reference'] ?? '' ),
			'currency'                  => SC_EI_Billing_Schema::sanitize_currency( (string) ( $data['currency'] ?? 'USD' ) ),
			'payment_terms_days'        => max( 0, min( 365, absint( $data['payment_terms_days'] ?? 30 ) ) ),
			'status'                    => 'active',
			'sender_visible'            => empty( $data['sender_visible'] ) ? 0 : 1,
			'row_version'               => 0,
			'created_by'                => $actor_user_id ?: null,
			'created_at'                => $now,
			'updated_at'                => $now,
		);
		$ok = $wpdb->insert( SC_EI_Database::table( 'billing_profiles' ), $payload, self::formats( $payload, array( 'inquiry_id', 'engagement_id', 'payment_terms_days', 'sender_visible', 'row_version', 'created_by' ) ) );
		if ( false === $ok ) {
			return new WP_Error( 'billing_profile_create_failed', __( 'The billing profile could not be created.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$id = (int) $wpdb->insert_id;
		self::event( 0, 0, $inquiry_id, 'billing_profile_created', '', 'active', $actor_user_id, array( 'billing_profile_id' => $id ) );
		return self::find_profile( $id );
	}

	public static function find_profile( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'billing_profiles' ) . ' WHERE id = %d', $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $row ) ? $row : null;
	}

	public static function create_invoice( int $engagement_id, array $data, int $actor_user_id ) {
		global $wpdb;
		$engagement = SC_EI_Engagement_Repository::find( $engagement_id );
		if ( ! $engagement ) {
			return new WP_Error( 'billing_engagement_missing', __( 'A governed engagement is required.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$profile_id = absint( $data['billing_profile_id'] ?? 0 );
		$profile = $profile_id ? self::find_profile( $profile_id ) : null;
		if ( ! $profile || absint( $profile['engagement_id'] ?? 0 ) !== $engagement_id ) {
			return new WP_Error( 'billing_profile_mismatch', __( 'A billing profile for this engagement is required.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$inquiry_id = absint( $engagement['inquiry_id'] ?? 0 );
		$now = current_time( 'mysql', true );
		$invoice_number = self::next_invoice_number();
		$currency = SC_EI_Billing_Schema::sanitize_currency( (string) ( $data['currency'] ?? $profile['currency'] ?? 'USD' ) );
		$payload = array(
			'public_id'          => wp_generate_uuid4(),
			'invoice_number'     => $invoice_number,
			'inquiry_id'         => $inquiry_id,
			'engagement_id'      => $engagement_id,
			'billing_profile_id' => $profile_id,
			'proposal_id'        => absint( $data['proposal_id'] ?? 0 ) ?: null,
			'sow_id'             => absint( $data['sow_id'] ?? 0 ) ?: null,
			'status'             => 'draft',
			'currency'           => $currency,
			'subtotal_minor'     => 0,
			'tax_minor'          => 0,
			'total_minor'        => 0,
			'amount_paid_minor'  => 0,
			'balance_due_minor'  => 0,
			'issued_at'          => null,
			'due_at'             => self::sanitize_datetime( $data['due_at'] ?? '' ),
			'paid_at'            => null,
			'voided_at'          => null,
			'sender_visible'     => 0,
			'memo'               => sanitize_textarea_field( $data['memo'] ?? '' ),
			'internal_note'      => sanitize_textarea_field( $data['internal_note'] ?? '' ),
			'current_version'    => 0,
			'row_version'        => 0,
			'created_by'         => $actor_user_id ?: null,
			'created_at'         => $now,
			'updated_at'         => $now,
		);
		$ok = $wpdb->insert( SC_EI_Database::table( 'invoices' ), $payload, self::formats( $payload, array( 'inquiry_id', 'engagement_id', 'billing_profile_id', 'proposal_id', 'sow_id', 'subtotal_minor', 'tax_minor', 'total_minor', 'amount_paid_minor', 'balance_due_minor', 'sender_visible', 'current_version', 'row_version', 'created_by' ) ) );
		if ( false === $ok ) {
			return new WP_Error( 'invoice_create_failed', __( 'The invoice could not be created.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$id = (int) $wpdb->insert_id;
		self::event( $id, 0, $inquiry_id, 'invoice_created', '', 'draft', $actor_user_id, array( 'invoice_number' => $invoice_number ) );
		return self::find_invoice( $id );
	}

	public static function find_invoice( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'invoices' ) . ' WHERE id = %d', $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $row ) ? $row : null;
	}

	public static function invoices( int $limit = 200 ): array {
		global $wpdb;
		return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'invoices' ) . ' ORDER BY created_at DESC, id DESC LIMIT %d', max( 1, min( 500, $limit ) ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	public static function add_item( int $invoice_id, array $data, int $actor_user_id ) {
		global $wpdb;
		$invoice = self::find_invoice( $invoice_id );
		if ( ! $invoice || ! in_array( (string) $invoice['status'], array( 'draft', 'internal_review' ), true ) ) {
			return new WP_Error( 'invoice_not_editable', __( 'Only draft or internal-review invoices can be edited.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$quantity = max( 0.0001, (float) ( $data['quantity'] ?? 1 ) );
		$unit = max( 0, (int) ( $data['unit_amount_minor'] ?? 0 ) );
		$amount = (int) round( $quantity * $unit );
		$line = 1 + (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(MAX(line_number),0) FROM ' . SC_EI_Database::table( 'invoice_items' ) . ' WHERE invoice_id = %d', $invoice_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$payload = array(
			'public_id'        => wp_generate_uuid4(),
			'invoice_id'       => $invoice_id,
			'line_number'      => $line,
			'item_type'        => sanitize_key( $data['item_type'] ?? 'service' ),
			'description'      => sanitize_textarea_field( $data['description'] ?? '' ),
			'quantity'         => number_format( $quantity, 4, '.', '' ),
			'unit_amount_minor'=> $unit,
			'amount_minor'     => $amount,
			'tax_code'         => sanitize_text_field( $data['tax_code'] ?? '' ),
			'metadata_json'    => wp_json_encode( array(), JSON_UNESCAPED_SLASHES ),
			'created_by'       => $actor_user_id ?: null,
			'created_at'       => current_time( 'mysql', true ),
			'updated_at'       => current_time( 'mysql', true ),
		);
		$ok = $wpdb->insert( SC_EI_Database::table( 'invoice_items' ), $payload, self::formats( $payload, array( 'invoice_id', 'line_number', 'unit_amount_minor', 'amount_minor', 'created_by' ) ) );
		if ( false === $ok ) {
			return new WP_Error( 'invoice_item_create_failed', __( 'The invoice line item could not be created.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::recalculate( $invoice_id, $actor_user_id );
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'invoice_items' ) . ' WHERE id = %d', (int) $wpdb->insert_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	public static function items( int $invoice_id ): array {
		global $wpdb;
		return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'invoice_items' ) . ' WHERE invoice_id = %d ORDER BY line_number ASC, id ASC', $invoice_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	public static function transition( int $invoice_id, string $status, string $confirmation, string $note, int $actor_user_id ) {
		global $wpdb;
		$invoice = self::find_invoice( $invoice_id );
		if ( ! $invoice ) {
			return new WP_Error( 'invoice_missing', __( 'Invoice not found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$status = SC_EI_Billing_Schema::sanitize_status( $status, SC_EI_Billing_Schema::invoice_statuses(), '' );
		if ( '' === $status || ! in_array( $status, SC_EI_Billing_Schema::allowed_transitions()[ $invoice['status'] ] ?? array(), true ) ) {
			return new WP_Error( 'invoice_transition_invalid', __( 'That invoice transition is not permitted.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$expected = strtoupper( $status . ' ' . (string) $invoice['invoice_number'] );
		if ( ! hash_equals( $expected, strtoupper( trim( $confirmation ) ) ) ) {
			return new WP_Error( 'invoice_confirmation_invalid', sprintf( __( 'Type %s to continue.', 'sustainable-catalyst-engagement-intake' ), $expected ) );
		}
		if ( in_array( $status, array( 'approved_to_issue', 'issued' ), true ) && ( ! self::items( $invoice_id ) || absint( $invoice['total_minor'] ) <= 0 ) ) {
			return new WP_Error( 'invoice_empty', __( 'An invoice must contain a positive line item before approval or issue.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$previous = (string) $invoice['status'];
		$now = current_time( 'mysql', true );
		$created_version_id = 0;
		$data = array(
			'status'         => $status,
			'row_version'    => absint( $invoice['row_version'] ) + 1,
			'updated_at'     => $now,
			'sender_visible' => in_array( $status, array( 'issued', 'partially_paid', 'paid', 'overdue', 'disputed' ), true ) ? 1 : absint( $invoice['sender_visible'] ),
		);
		if ( 'issued' === $status ) {
			$version = self::create_version( $invoice_id, $actor_user_id );
			if ( is_wp_error( $version ) ) {
				return $version;
			}
			$created_version_id = absint( $version['id'] ?? 0 );
			$data['current_version'] = absint( $version['version_number'] ?? 0 );
			$data['issued_at'] = $now;
			if ( empty( $invoice['due_at'] ) ) {
				$profile = self::find_profile( absint( $invoice['billing_profile_id'] ) );
				$days = max( 0, absint( $profile['payment_terms_days'] ?? 30 ) );
				$data['due_at'] = gmdate( 'Y-m-d H:i:s', time() + $days * DAY_IN_SECONDS );
			}
		}
		if ( 'paid' === $status ) {
			$data['amount_paid_minor'] = absint( $invoice['total_minor'] );
			$data['balance_due_minor'] = 0;
			$data['paid_at'] = $now;
		}
		if ( 'void' === $status ) {
			$data['voided_at'] = $now;
			$data['sender_visible'] = 0;
		}

		$ok = $wpdb->update( SC_EI_Database::table( 'invoices' ), $data, array( 'id' => $invoice_id, 'row_version' => absint( $invoice['row_version'] ) ), self::formats( $data, array( 'row_version', 'sender_visible', 'current_version', 'amount_paid_minor', 'balance_due_minor' ) ), array( '%d', '%d' ) );
		if ( false === $ok || 0 === $ok ) {
			if ( $created_version_id ) {
				$wpdb->delete( SC_EI_Database::table( 'invoice_versions' ), array( 'id' => $created_version_id ), array( '%d' ) );
			}
			return new WP_Error( 'invoice_transition_conflict', __( 'The invoice changed before this transition could be saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( ! self::event( $invoice_id, 0, absint( $invoice['inquiry_id'] ), 'invoice_transitioned', $previous, $status, $actor_user_id, array( 'note' => sanitize_textarea_field( $note ), 'invoice_version_id' => $created_version_id ) ) ) {
			$restore = array(
				'status' => $invoice['status'], 'row_version' => absint( $invoice['row_version'] ), 'updated_at' => $invoice['updated_at'],
				'sender_visible' => absint( $invoice['sender_visible'] ), 'current_version' => absint( $invoice['current_version'] ),
				'issued_at' => $invoice['issued_at'], 'due_at' => $invoice['due_at'], 'paid_at' => $invoice['paid_at'], 'voided_at' => $invoice['voided_at'],
				'amount_paid_minor' => absint( $invoice['amount_paid_minor'] ), 'balance_due_minor' => absint( $invoice['balance_due_minor'] ),
			);
			$wpdb->update( SC_EI_Database::table( 'invoices' ), $restore, array( 'id' => $invoice_id ), self::formats( $restore, array( 'row_version', 'sender_visible', 'current_version', 'amount_paid_minor', 'balance_due_minor' ) ), array( '%d' ) );
			if ( $created_version_id ) {
				$wpdb->delete( SC_EI_Database::table( 'invoice_versions' ), array( 'id' => $created_version_id ), array( '%d' ) );
			}
			return new WP_Error( 'invoice_event_failed', __( 'The invoice transition audit event could not be stored; the transition was rolled back.', 'sustainable-catalyst-engagement-intake' ) );
		}
		return self::find_invoice( $invoice_id );
	}

	public static function create_payment_handoff( int $invoice_id, array $data, int $actor_user_id ) {
		global $wpdb;
		$invoice = self::find_invoice( $invoice_id );
		if ( ! $invoice || ! in_array( (string) $invoice['status'], array( 'issued', 'partially_paid', 'overdue', 'disputed' ), true ) ) {
			return new WP_Error( 'payment_handoff_invoice_invalid', __( 'Only an issued invoice can receive a payment handoff.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$metadata = is_array( $data['metadata'] ?? null ) ? $data['metadata'] : array();
		if ( ! SC_EI_Billing_Schema::payment_metadata_is_safe( $metadata ) ) {
			return new WP_Error( 'payment_handoff_sensitive_data_rejected', __( 'Payment handoff metadata cannot contain payment instruments, credentials, or personal contact data.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$url = esc_url_raw( $data['checkout_url'] ?? '' );
		if ( $url && 'https' !== strtolower( (string) wp_parse_url( $url, PHP_URL_SCHEME ) ) ) {
			return new WP_Error( 'payment_handoff_https_required', __( 'Payment handoff URLs must use HTTPS.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$idempotency_key = sanitize_text_field( $data['idempotency_key'] ?? '' );
		if ( '' === $idempotency_key ) {
			$idempotency_key = hash( 'sha256', $invoice_id . '|' . ( $data['provider'] ?? 'manual' ) . '|' . ( $data['provider_reference'] ?? '' ) . '|' . ( $data['amount_minor'] ?? $invoice['balance_due_minor'] ) );
		}
		$existing = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'payment_handoffs' ) . ' WHERE idempotency_key = %s LIMIT 1', $idempotency_key ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( $existing ) {
			return $existing;
		}
		$now = current_time( 'mysql', true );
		$payload = array(
			'public_id'          => wp_generate_uuid4(),
			'schema'             => SC_EI_Billing_Schema::PAYMENT_HANDOFF_SCHEMA,
			'invoice_id'         => $invoice_id,
			'inquiry_id'         => absint( $invoice['inquiry_id'] ),
			'provider'           => SC_EI_Billing_Schema::sanitize_provider( (string) ( $data['provider'] ?? 'manual' ) ),
			'provider_reference' => sanitize_text_field( $data['provider_reference'] ?? '' ),
			'checkout_url'       => $url,
			'status'             => 'pending',
			'amount_minor'       => max( 0, (int) ( $data['amount_minor'] ?? $invoice['balance_due_minor'] ) ),
			'currency'           => SC_EI_Billing_Schema::sanitize_currency( (string) ( $data['currency'] ?? $invoice['currency'] ) ),
			'idempotency_key'    => $idempotency_key,
			'expires_at'         => self::sanitize_datetime( $data['expires_at'] ?? '' ),
			'authorized_at'      => null,
			'settled_at'         => null,
			'failed_at'          => null,
			'refunded_at'        => null,
			'last_event_at'      => $now,
			'sender_visible'     => empty( $data['sender_visible'] ) ? 0 : 1,
			'metadata_json'      => wp_json_encode( $metadata, JSON_UNESCAPED_SLASHES ),
			'created_by'         => $actor_user_id ?: null,
			'created_at'         => $now,
			'updated_at'         => $now,
		);
		$ok = $wpdb->insert( SC_EI_Database::table( 'payment_handoffs' ), $payload, self::formats( $payload, array( 'invoice_id', 'inquiry_id', 'amount_minor', 'sender_visible', 'created_by' ) ) );
		if ( false === $ok ) {
			return new WP_Error( 'payment_handoff_create_failed', __( 'The payment handoff could not be created.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$id = (int) $wpdb->insert_id;
		if ( ! self::event( $invoice_id, $id, absint( $invoice['inquiry_id'] ), 'payment_handoff_created', '', 'pending', $actor_user_id, array( 'provider' => $payload['provider'] ) ) ) {
			$wpdb->delete( SC_EI_Database::table( 'payment_handoffs' ), array( 'id' => $id ), array( '%d' ) );
			return new WP_Error( 'payment_handoff_event_failed', __( 'The payment handoff audit event could not be stored; the handoff was rolled back.', 'sustainable-catalyst-engagement-intake' ) );
		}
		return self::find_handoff( $id );
	}

	public static function find_handoff( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'payment_handoffs' ) . ' WHERE id = %d', $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $row ) ? $row : null;
	}

	public static function record_payment_status( int $handoff_id, string $status, string $provider_event_key, array $metadata, int $actor_user_id ) {
		global $wpdb;
		$handoff = self::find_handoff( $handoff_id );
		if ( ! $handoff ) {
			return new WP_Error( 'payment_handoff_missing', __( 'Payment handoff not found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$status = SC_EI_Billing_Schema::sanitize_status( $status, SC_EI_Billing_Schema::payment_statuses(), '' );
		if ( '' === $status ) {
			return new WP_Error( 'payment_status_invalid', __( 'Invalid payment status.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( ! SC_EI_Billing_Schema::payment_metadata_is_safe( $metadata ) ) {
			return new WP_Error( 'payment_event_sensitive_data_rejected', __( 'Payment event metadata cannot contain payment instruments, credentials, or personal contact data.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$provider_event_key = sanitize_text_field( $provider_event_key );
		$event_hash = hash( 'sha256', $handoff_id . '|' . $provider_event_key . '|' . $status );
		$existing = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . SC_EI_Database::table( 'billing_events' ) . ' WHERE immutable_hash = %s LIMIT 1', $event_hash ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( $existing ) {
			return $handoff;
		}
		$now = current_time( 'mysql', true );
		$data = array( 'status' => $status, 'last_event_at' => $now, 'updated_at' => $now );
		if ( 'authorized' === $status ) $data['authorized_at'] = $now;
		if ( 'settled' === $status ) $data['settled_at'] = $now;
		if ( 'failed' === $status ) $data['failed_at'] = $now;
		if ( 'refunded' === $status ) $data['refunded_at'] = $now;
		$ok = $wpdb->update( SC_EI_Database::table( 'payment_handoffs' ), $data, array( 'id' => $handoff_id ), self::formats( $data ), array( '%d' ) );
		if ( false === $ok ) {
			return new WP_Error( 'payment_status_update_failed', __( 'The payment status could not be recorded.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( ! self::event( absint( $handoff['invoice_id'] ), $handoff_id, absint( $handoff['inquiry_id'] ), 'payment_status_recorded', (string) $handoff['status'], $status, $actor_user_id, array( 'provider_event_key' => $provider_event_key, 'metadata' => $metadata ), $event_hash ) ) {
			$restore = array( 'status' => $handoff['status'], 'last_event_at' => $handoff['last_event_at'], 'authorized_at' => $handoff['authorized_at'], 'settled_at' => $handoff['settled_at'], 'failed_at' => $handoff['failed_at'], 'refunded_at' => $handoff['refunded_at'], 'updated_at' => $handoff['updated_at'] );
			$wpdb->update( SC_EI_Database::table( 'payment_handoffs' ), $restore, array( 'id' => $handoff_id ), self::formats( $restore ), array( '%d' ) );
			return new WP_Error( 'payment_event_audit_failed', __( 'The payment status audit event could not be stored; the status was rolled back.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::apply_invoice_payment_state( absint( $handoff['invoice_id'] ), $status, absint( $handoff['amount_minor'] ), $actor_user_id );
		return self::find_handoff( $handoff_id );
	}

	public static function sender_snapshot( int $inquiry_id ): array {
		global $wpdb;
		$invoices = (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . SC_EI_Database::table( 'invoices' ) . " WHERE inquiry_id = %d AND sender_visible = 1 AND status IN ('issued','partially_paid','paid','overdue','disputed') ORDER BY issued_at DESC, id DESC", $inquiry_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = array();
		foreach ( $invoices as $invoice ) {
			$items = array_map(
				static fn( array $item ): array => array(
					'description' => (string) $item['description'],
					'quantity' => (string) $item['quantity'],
					'unit_amount_minor' => absint( $item['unit_amount_minor'] ),
					'amount_minor' => absint( $item['amount_minor'] ),
				),
				self::items( absint( $invoice['id'] ) )
			);
			$handoffs = (array) $wpdb->get_results( $wpdb->prepare( "SELECT public_id, provider, checkout_url, status, amount_minor, currency, expires_at FROM " . SC_EI_Database::table( 'payment_handoffs' ) . " WHERE invoice_id = %d AND sender_visible = 1 AND status NOT IN ('failed','canceled') ORDER BY created_at DESC", absint( $invoice['id'] ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$row = array(
				'public_id'         => (string) $invoice['public_id'],
				'invoice_number'    => (string) $invoice['invoice_number'],
				'status'            => (string) $invoice['status'],
				'currency'          => (string) $invoice['currency'],
				'subtotal_minor'    => absint( $invoice['subtotal_minor'] ),
				'tax_minor'         => absint( $invoice['tax_minor'] ),
				'total_minor'       => absint( $invoice['total_minor'] ),
				'amount_paid_minor' => absint( $invoice['amount_paid_minor'] ),
				'balance_due_minor' => absint( $invoice['balance_due_minor'] ),
				'issued_at'         => (string) $invoice['issued_at'],
				'due_at'            => (string) $invoice['due_at'],
				'paid_at'           => (string) $invoice['paid_at'],
				'memo'              => (string) $invoice['memo'],
				'current_version'   => absint( $invoice['current_version'] ),
				'line_items'        => $items,
				'payment_handoffs'  => $handoffs,
			);
			$result[] = array_intersect_key( $row, array_flip( SC_EI_Billing_Schema::sender_projection_keys() ) );
		}
		return $result;
	}

	public static function export_for_inquiry( int $inquiry_id ): array {
		global $wpdb;
		$profiles = (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'billing_profiles' ) . ' WHERE inquiry_id = %d ORDER BY id ASC', $inquiry_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$invoices = (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'invoices' ) . ' WHERE inquiry_id = %d ORDER BY id ASC', $inquiry_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$invoice_ids = array_map( 'absint', array_column( $invoices, 'id' ) );
		$items = array();
		$versions = array();
		$handoffs = array();
		$events = (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'billing_events' ) . ' WHERE inquiry_id = %d ORDER BY id ASC', $inquiry_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $invoice_ids as $invoice_id ) {
			$items = array_merge( $items, (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'invoice_items' ) . ' WHERE invoice_id = %d ORDER BY id ASC', $invoice_id ), ARRAY_A ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$versions = array_merge( $versions, (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'invoice_versions' ) . ' WHERE invoice_id = %d ORDER BY id ASC', $invoice_id ), ARRAY_A ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$handoffs = array_merge( $handoffs, (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'payment_handoffs' ) . ' WHERE invoice_id = %d ORDER BY id ASC', $invoice_id ), ARRAY_A ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		return compact( 'profiles', 'invoices', 'items', 'versions', 'handoffs', 'events' );
	}

	public static function metrics(): array {
		global $wpdb;
		$invoices = SC_EI_Database::table( 'invoices' );
		$handoffs = SC_EI_Database::table( 'payment_handoffs' );
		return array(
			'invoices' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$invoices}" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'issued' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$invoices} WHERE status IN ('issued','partially_paid','overdue','disputed')" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'paid' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$invoices} WHERE status = 'paid'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'overdue' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$invoices} WHERE status = 'overdue' OR (status IN ('issued','partially_paid') AND due_at IS NOT NULL AND due_at < UTC_TIMESTAMP())" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'open_balance_minor' => (int) $wpdb->get_var( "SELECT COALESCE(SUM(balance_due_minor),0) FROM {$invoices} WHERE status IN ('issued','partially_paid','overdue','disputed')" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'active_handoffs' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$handoffs} WHERE status IN ('pending','authorized','processing')" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	public static function operational_blockers(): array {
		global $wpdb;
		$i = SC_EI_Database::table( 'invoices' );
		$h = SC_EI_Database::table( 'payment_handoffs' );
		$v = SC_EI_Database::table( 'invoice_versions' );
		return array(
			'issued_without_version' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$i} WHERE status IN ('issued','partially_paid','paid','overdue','disputed') AND current_version = 0" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'invalid_balances' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$i} WHERE total_minor < 0 OR amount_paid_minor < 0 OR balance_due_minor < 0 OR total_minor <> amount_paid_minor + balance_due_minor" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'missing_invoice_versions' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$i} x WHERE x.current_version > 0 AND NOT EXISTS (SELECT 1 FROM {$v} v WHERE v.invoice_id=x.id AND v.version_number=x.current_version)" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'insecure_payment_urls' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$h} WHERE checkout_url <> '' AND checkout_url NOT LIKE 'https://%'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'settled_without_invoice_state' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$h} h INNER JOIN {$i} i ON i.id=h.invoice_id WHERE h.status='settled' AND i.status NOT IN ('paid','partially_paid')" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	public static function cleanup_for_inquiry( int $inquiry_id ): void {
		global $wpdb;
		$invoice_ids = array_map( 'absint', (array) $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . SC_EI_Database::table( 'invoices' ) . ' WHERE inquiry_id = %d', $inquiry_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $invoice_ids as $invoice_id ) {
			$wpdb->delete( SC_EI_Database::table( 'billing_events' ), array( 'invoice_id' => $invoice_id ), array( '%d' ) );
			$wpdb->delete( SC_EI_Database::table( 'payment_handoffs' ), array( 'invoice_id' => $invoice_id ), array( '%d' ) );
			$wpdb->delete( SC_EI_Database::table( 'invoice_items' ), array( 'invoice_id' => $invoice_id ), array( '%d' ) );
			$wpdb->delete( SC_EI_Database::table( 'invoice_versions' ), array( 'invoice_id' => $invoice_id ), array( '%d' ) );
		}
		$wpdb->delete( SC_EI_Database::table( 'billing_events' ), array( 'inquiry_id' => $inquiry_id ), array( '%d' ) );
		$wpdb->delete( SC_EI_Database::table( 'invoices' ), array( 'inquiry_id' => $inquiry_id ), array( '%d' ) );
		$wpdb->delete( SC_EI_Database::table( 'billing_profiles' ), array( 'inquiry_id' => $inquiry_id ), array( '%d' ) );
	}

	private static function recalculate( int $invoice_id, int $actor_user_id ): void {
		global $wpdb;
		$subtotal = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(amount_minor),0) FROM ' . SC_EI_Database::table( 'invoice_items' ) . ' WHERE invoice_id=%d', $invoice_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$invoice = self::find_invoice( $invoice_id );
		if ( ! $invoice ) return;
		$tax = max( 0, absint( $invoice['tax_minor'] ) );
		$total = $subtotal + $tax;
		$paid = min( $total, absint( $invoice['amount_paid_minor'] ) );
		$wpdb->update( SC_EI_Database::table( 'invoices' ), array( 'subtotal_minor' => $subtotal, 'total_minor' => $total, 'amount_paid_minor' => $paid, 'balance_due_minor' => $total - $paid, 'row_version' => absint( $invoice['row_version'] ) + 1, 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $invoice_id ), array( '%d','%d','%d','%d','%d','%s' ), array( '%d' ) );
		self::event( $invoice_id, 0, absint( $invoice['inquiry_id'] ), 'invoice_recalculated', (string) $invoice['status'], (string) $invoice['status'], $actor_user_id, array( 'subtotal_minor' => $subtotal, 'total_minor' => $total ) );
	}

	private static function create_version( int $invoice_id, int $actor_user_id ) {
		global $wpdb;
		$invoice = self::find_invoice( $invoice_id );
		if ( ! $invoice ) return new WP_Error( 'invoice_missing', __( 'Invoice not found.', 'sustainable-catalyst-engagement-intake' ) );
		$version = 1 + (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(MAX(version_number),0) FROM ' . SC_EI_Database::table( 'invoice_versions' ) . ' WHERE invoice_id=%d', $invoice_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$snapshot = array(
			'schema' => SC_EI_Billing_Schema::INVOICE_SCHEMA,
			'invoice' => array_diff_key( $invoice, array_flip( array( 'internal_note', 'created_by', 'row_version' ) ) ),
			'items' => self::items( $invoice_id ),
		);
		$json = wp_json_encode( $snapshot, JSON_UNESCAPED_SLASHES );
		$payload = array( 'public_id' => wp_generate_uuid4(), 'invoice_id' => $invoice_id, 'version_number' => $version, 'snapshot_json' => $json, 'content_hash' => hash( 'sha256', (string) $json ), 'status' => 'issued', 'created_by' => $actor_user_id ?: null, 'created_at' => current_time( 'mysql', true ) );
		$ok = $wpdb->insert( SC_EI_Database::table( 'invoice_versions' ), $payload, self::formats( $payload, array( 'invoice_id','version_number','created_by' ) ) );
		if ( false === $ok ) return new WP_Error( 'invoice_version_failed', __( 'The immutable invoice version could not be stored.', 'sustainable-catalyst-engagement-intake' ) );
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'invoice_versions' ) . ' WHERE id=%d', (int) $wpdb->insert_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	private static function apply_invoice_payment_state( int $invoice_id, string $status, int $amount_minor, int $actor_user_id ): void {
		global $wpdb;
		$invoice = self::find_invoice( $invoice_id );
		if ( ! $invoice ) return;
		$paid = absint( $invoice['amount_paid_minor'] );
		if ( 'settled' === $status ) $paid = min( absint( $invoice['total_minor'] ), $paid + max( 0, $amount_minor ) );
		if ( 'refunded' === $status ) $paid = max( 0, $paid - max( 0, $amount_minor ) );
		$balance = max( 0, absint( $invoice['total_minor'] ) - $paid );
		$new_status = $balance <= 0 && absint( $invoice['total_minor'] ) > 0 ? 'paid' : ( $paid > 0 ? 'partially_paid' : (string) $invoice['status'] );
		$data = array( 'amount_paid_minor' => $paid, 'balance_due_minor' => $balance, 'status' => $new_status, 'updated_at' => current_time( 'mysql', true ), 'row_version' => absint( $invoice['row_version'] ) + 1 );
		if ( 'paid' === $new_status ) $data['paid_at'] = current_time( 'mysql', true );
		$wpdb->update( SC_EI_Database::table( 'invoices' ), $data, array( 'id' => $invoice_id ), self::formats( $data, array( 'amount_paid_minor','balance_due_minor','row_version' ) ), array( '%d' ) );
		self::event( $invoice_id, 0, absint( $invoice['inquiry_id'] ), 'invoice_payment_state_updated', (string) $invoice['status'], $new_status, $actor_user_id, array( 'amount_paid_minor' => $paid, 'balance_due_minor' => $balance ) );
	}

	private static function event( int $invoice_id, int $handoff_id, int $inquiry_id, string $event_type, string $from_status, string $to_status, int $actor_user_id, array $context, string $immutable_hash = '' ): bool {
		global $wpdb;
		$created_at = current_time( 'mysql', true );
		if ( '' === $immutable_hash ) {
			$immutable_hash = hash( 'sha256', wp_json_encode( array( $invoice_id, $handoff_id, $event_type, $from_status, $to_status, $actor_user_id, $context, $created_at ), JSON_UNESCAPED_SLASHES ) );
		}
		$result = $wpdb->insert( SC_EI_Database::table( 'billing_events' ), array( 'public_id' => wp_generate_uuid4(), 'invoice_id' => $invoice_id ?: null, 'payment_handoff_id' => $handoff_id ?: null, 'inquiry_id' => $inquiry_id, 'event_type' => sanitize_key( $event_type ), 'from_status' => sanitize_key( $from_status ), 'to_status' => sanitize_key( $to_status ), 'actor_type' => $actor_user_id ? 'staff' : 'system', 'actor_id' => $actor_user_id ?: null, 'context_json' => wp_json_encode( $context, JSON_UNESCAPED_SLASHES ), 'immutable_hash' => $immutable_hash, 'created_at' => $created_at ), array( '%s','%d','%d','%d','%s','%s','%s','%s','%d','%s','%s','%s' ) );
		return false !== $result;
	}

	private static function next_invoice_number(): string {
		global $wpdb;
		$year = gmdate( 'Y' );
		$prefix = 'SC-' . $year . '-';
		$last = (string) $wpdb->get_var( $wpdb->prepare( 'SELECT invoice_number FROM ' . SC_EI_Database::table( 'invoices' ) . ' WHERE invoice_number LIKE %s ORDER BY id DESC LIMIT 1', $wpdb->esc_like( $prefix ) . '%' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$sequence = $last ? absint( substr( $last, strlen( $prefix ) ) ) + 1 : 1;
		return $prefix . str_pad( (string) $sequence, 5, '0', STR_PAD_LEFT );
	}

	private static function sanitize_address( $value ): array {
		$value = is_array( $value ) ? $value : array();
		$result = array();
		foreach ( array( 'line1','line2','city','region','postal_code','country' ) as $key ) {
			$result[ $key ] = sanitize_text_field( $value[ $key ] ?? '' );
		}
		return $result;
	}

	private static function sanitize_datetime( $value ): ?string {
		$value = sanitize_text_field( (string) $value );
		if ( '' === $value ) return null;
		try {
			$date = new DateTimeImmutable( $value, wp_timezone() );
			return $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
		} catch ( Throwable $exception ) {
			return null;
		}
	}

	private static function formats( array $data, array $integer_fields = array() ): array {
		return array_map( static fn( string $key ): string => in_array( $key, $integer_fields, true ) ? '%d' : '%s', array_keys( $data ) );
	}
}
