<?php
/**
 * Product support case, relationship, event, and intelligence-signal repository.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Support_Repository {

	public const MIGRATION_KEY = 'v1_2_0_support_operations_product_intelligence';
	public const PATCH_MIGRATION_KEY = 'v1_2_1_support_operations_cross_product_reliability';
	public const SIGNAL_DIGEST_HOOK = 'sc_ei_support_signal_digest';

	public static function register(): void {
		add_action( 'sc_ei_public_inquiry_created', array( __CLASS__, 'capture_public_inquiry' ), 10, 2 );
		add_action( self::SIGNAL_DIGEST_HOOK, array( __CLASS__, 'scheduled_signal_digest' ) );
	}

	public static function maybe_upgrade(): void {
		self::record_migration( (string) get_option( 'sc_ei_version_previous', '' ) );
		self::record_patch_migration( (string) get_option( 'sc_ei_version_previous', '' ) );
		self::schedule();
	}

	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::SIGNAL_DIGEST_HOOK ) ) {
			wp_schedule_event( time() + 3 * HOUR_IN_SECONDS, 'daily', self::SIGNAL_DIGEST_HOOK );
		}
	}

	public static function unschedule(): void {
		wp_clear_scheduled_hook( self::SIGNAL_DIGEST_HOOK );
	}

	public static function scheduled_signal_digest(): void {
		update_option( 'sc_ei_last_support_signal_digest', current_time( 'mysql', true ), false );
	}

	public static function record_migration( string $from_version = '' ) {
		global $wpdb;
		$table = SC_EI_Database::table( 'platform_migrations' );
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE migration_key = %s LIMIT 1", self::MIGRATION_KEY ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $existing && 'completed' === (string) $existing['status'] ) {
			return $existing;
		}
		$contract = SC_EI_Database::support_columns_exist();
		$ok = ! in_array( false, $contract, true );
		$now = current_time( 'mysql', true );
		$data = array(
			'public_id'     => $existing['public_id'] ?? wp_generate_uuid4(),
			'migration_key' => self::MIGRATION_KEY,
			'from_version'  => sanitize_text_field( $from_version ),
			'to_version'    => '1.2.0',
			'status'        => $ok ? 'completed' : 'failed',
			'schema_hash'   => hash( 'sha256', wp_json_encode( array( 'support' => SC_EI_SUPPORT_SCHEMA_VERSION, 'database' => SC_EI_DB_VERSION, 'plugin' => SC_EI_VERSION ) ) ),
			'context_json'  => wp_json_encode( array( 'release' => 'Support Operations and Product Intelligence Integration', 'database_schema_changed' => true, 'private_support_cases' => true, 'privacy_safe_signals' => true, 'handoff_schema' => SC_EI_Support_Schema::HANDOFF_SCHEMA ), JSON_UNESCAPED_SLASHES ),
			'started_at'    => $existing['started_at'] ?? $now,
			'completed_at'  => $now,
			'error_code'    => $ok ? '' : 'support_schema_incomplete',
			'error_message' => $ok ? '' : 'One or more support operations tables or columns are unavailable.',
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
			return new WP_Error( 'support_migration_journal_failed', __( 'The v1.2.0 support migration journal could not be recorded.', 'sustainable-catalyst-engagement-intake' ) );
		}
		SC_EI_Audit_Log::record( $ok ? 'support_migration_completed' : 'support_migration_failed', $ok ? 'Support operations and product-intelligence schema verified.' : 'Support operations schema verification failed.', array( 'migration_key' => self::MIGRATION_KEY, 'destructive' => false ), null, null, get_current_user_id() );
		return $ok ? self::find_migration( $id ) : new WP_Error( 'support_schema_incomplete', __( 'The v1.2.0 support operations database contract requires repair.', 'sustainable-catalyst-engagement-intake' ) );
	}


	public static function record_patch_migration( string $from_version = '' ) {
		global $wpdb;
		$table = SC_EI_Database::table( 'platform_migrations' );
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE migration_key = %s LIMIT 1", self::PATCH_MIGRATION_KEY ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $existing && 'completed' === (string) $existing['status'] ) {
			return $existing;
		}
		$contract = SC_EI_Database::support_columns_exist();
		$inquiry_contract = SC_EI_Database::inquiry_columns_exist();
		$ok = ! in_array( false, $contract, true ) && ! in_array( false, $inquiry_contract, true );
		$now = current_time( 'mysql', true );
		$data = array(
			'public_id' => $existing['public_id'] ?? wp_generate_uuid4(),
			'migration_key' => self::PATCH_MIGRATION_KEY,
			'from_version' => sanitize_text_field( $from_version ),
			'to_version' => '1.2.1',
			'status' => $ok ? 'completed' : 'failed',
			'schema_hash' => hash( 'sha256', wp_json_encode( array( 'support' => SC_EI_SUPPORT_SCHEMA_VERSION, 'database' => SC_EI_DB_VERSION, 'plugin' => SC_EI_VERSION ) ) ),
			'context_json' => wp_json_encode( array( 'release' => 'Support Operations and Cross-Product Reliability Patch', 'database_schema_changed' => false, 'atomic_public_support_persistence' => true, 'idempotent_handoffs' => true, 'strict_product_contract' => true ), JSON_UNESCAPED_SLASHES ),
			'started_at' => $existing['started_at'] ?? $now,
			'completed_at' => $now,
			'error_code' => $ok ? '' : 'support_reliability_contract_incomplete',
			'error_message' => $ok ? '' : 'The support or inquiry write-path database contract is incomplete.',
			'created_at' => $existing['created_at'] ?? $now,
			'updated_at' => $now,
		);
		if ( $existing ) {
			$result = $wpdb->update( $table, $data, array( 'id' => absint( $existing['id'] ) ), self::formats( $data ), array( '%d' ) );
			$id = absint( $existing['id'] );
		} else {
			$result = $wpdb->insert( $table, $data, self::formats( $data ) );
			$id = (int) $wpdb->insert_id;
		}
		if ( false === $result ) {
			return new WP_Error( 'support_patch_migration_journal_failed', __( 'The v1.2.1 support reliability migration journal could not be recorded.', 'sustainable-catalyst-engagement-intake' ) );
		}
		return $ok ? self::find_migration( $id ) : new WP_Error( 'support_reliability_contract_incomplete', __( 'The v1.2.1 support reliability contract requires repair.', 'sustainable-catalyst-engagement-intake' ) );
	}

	public static function public_case_input( array $record, array $raw ): array {
		return array(
			'product' => $raw['support_product'] ?? 'other',
			'product_version' => $raw['support_product_version'] ?? '',
			'component' => $raw['support_component'] ?? '',
			'issue_type' => $raw['support_issue_type'] ?? 'other',
			'error_message' => $raw['support_error_message'] ?? '',
			'reproduction_steps' => $raw['support_reproduction_steps'] ?? '',
			'expected_behavior' => $raw['support_expected_behavior'] ?? '',
			'actual_behavior' => $raw['support_actual_behavior'] ?? '',
			'environment' => array(
				'browser' => sanitize_text_field( (string) ( $raw['support_browser'] ?? '' ) ),
				'os' => sanitize_text_field( (string) ( $raw['support_os'] ?? '' ) ),
				'wordpress' => sanitize_text_field( (string) ( $raw['support_wordpress_version'] ?? '' ) ),
				'php' => sanitize_text_field( (string) ( $raw['support_php_version'] ?? '' ) ),
			),
			'source_system' => 'public_support_form',
			'source_reference' => (string) ( $record['reference'] ?? '' ),
		);
	}

	public static function ensure_public_case( array $record, array $raw ) {
		$inquiry_id = absint( $record['id'] ?? 0 );
		$existing = $inquiry_id ? self::for_inquiry( $inquiry_id ) : null;
		if ( $existing ) {
			return $existing;
		}
		return self::create_for_inquiry( $inquiry_id, self::public_case_input( $record, $raw ), 0 );
	}

	public static function capture_public_inquiry( array $record, array $raw ): void {
		$type = sanitize_key( (string) ( $record['inquiry_type'] ?? '' ) );
		$route = sanitize_key( (string) ( $record['conversion_route'] ?? '' ) );
		$source = sanitize_key( (string) ( $record['source_page'] ?? '' ) );
		if ( 'product_support' !== $type && 'product_support' !== $route && false === strpos( $source, 'support' ) ) {
			return;
		}
		$result = self::ensure_public_case( $record, $raw );
		if ( is_wp_error( $result ) ) {
			self::record_failure( 'public_support_case_capture_failed', $result, array( 'inquiry_id' => absint( $record['id'] ?? 0 ) ) );
		}
	}

	public static function create_for_inquiry( int $inquiry_id, array $input, int $actor_user_id = 0 ) {
		global $wpdb;
		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $inquiry ) {
			return new WP_Error( 'support_inquiry_not_found', __( 'The related inquiry could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$existing = self::for_inquiry( $inquiry_id );
		if ( $existing ) {
			return self::update_context( absint( $existing['id'] ), $input, $actor_user_id );
		}
		$now = current_time( 'mysql', true );
		$data = array(
			'public_id'           => wp_generate_uuid4(),
			'case_number'         => self::case_number(),
			'inquiry_id'          => $inquiry_id,
			'workflow_stage'      => 'new_support_request',
			'product'             => SC_EI_Support_Schema::sanitize_product( (string) ( $input['product'] ?? 'other' ) ),
			'product_version'     => substr( sanitize_text_field( (string) ( $input['product_version'] ?? '' ) ), 0, 80 ),
			'component'           => substr( sanitize_title( (string) ( $input['component'] ?? '' ) ), 0, 120 ),
			'issue_type'          => SC_EI_Support_Schema::sanitize_issue_type( (string) ( $input['issue_type'] ?? 'other' ) ),
			'environment_json'    => wp_json_encode( self::sanitize_environment( (array) ( $input['environment'] ?? array() ) ), JSON_UNESCAPED_SLASHES ),
			'error_message'       => sanitize_textarea_field( (string) ( $input['error_message'] ?? '' ) ),
			'reproduction_steps'  => sanitize_textarea_field( (string) ( $input['reproduction_steps'] ?? '' ) ),
			'expected_behavior'   => sanitize_textarea_field( (string) ( $input['expected_behavior'] ?? '' ) ),
			'actual_behavior'     => sanitize_textarea_field( (string) ( $input['actual_behavior'] ?? '' ) ),
			'severity'            => SC_EI_Support_Schema::sanitize_severity( (string) ( $input['severity'] ?? 'normal' ) ),
			'priority'            => SC_EI_Lifecycle_Schema::sanitize_priority( (string) ( $input['priority'] ?? 'normal' ) ),
			'assigned_user_id'    => absint( $input['assigned_user_id'] ?? 0 ) ?: null,
			'source_system'       => substr( sanitize_key( (string) ( $input['source_system'] ?? 'manual' ) ), 0, 80 ),
			'source_reference'    => substr( sanitize_text_field( (string) ( $input['source_reference'] ?? '' ) ), 0, 191 ),
			'known_issue_reference'=> '',
			'sender_summary'      => '',
			'sender_next_step'    => '',
			'row_version'         => 0,
			'created_by'          => $actor_user_id ?: null,
			'created_at'          => $now,
			'updated_at'          => $now,
			'resolved_at'         => null,
			'closed_at'           => null,
		);
		$ok = false;
		for ( $attempt = 1; $attempt <= 3; $attempt++ ) {
			$data['case_number'] = self::case_number();
			$ok = $wpdb->insert( SC_EI_Database::table( 'support_cases' ), $data, self::formats( $data, array( 'inquiry_id', 'assigned_user_id', 'row_version', 'created_by' ) ) );
			if ( false !== $ok ) {
				break;
			}
			$concurrent = self::for_inquiry( $inquiry_id );
			if ( $concurrent ) {
				return $concurrent;
			}
		}
		if ( false === $ok ) {
			$error = new WP_Error( 'support_case_create_failed', __( 'The support case could not be created.', 'sustainable-catalyst-engagement-intake' ) );
			self::record_failure( 'support_case_insert_failed', $error, array( 'inquiry_id' => $inquiry_id, 'database_error_hash' => self::database_error_hash() ) );
			return $error;
		}
		$case_id = (int) $wpdb->insert_id;
		$event_ok = self::insert_event( $case_id, $inquiry_id, 'support_case_created', '', 'new_support_request', $actor_user_id, array( 'product' => $data['product'], 'issue_type' => $data['issue_type'], 'source_system' => $data['source_system'] ) );
		if ( ! $event_ok ) {
			$wpdb->delete( SC_EI_Database::table( 'support_cases' ), array( 'id' => $case_id ), array( '%d' ) );
			$error = new WP_Error( 'support_case_event_create_failed', __( 'The support case audit event could not be created.', 'sustainable-catalyst-engagement-intake' ) );
			self::record_failure( 'support_case_event_insert_failed', $error, array( 'inquiry_id' => $inquiry_id, 'database_error_hash' => self::database_error_hash() ) );
			return $error;
		}
		SC_EI_Audit_Log::record( 'support_case_created', 'Private product support case created from the canonical inquiry.', array( 'case_id' => $case_id, 'case_number' => $data['case_number'], 'product' => $data['product'] ), $inquiry_id, null, $actor_user_id ?: null );
		return self::find( $case_id );
	}

	public static function update_context( int $case_id, array $input, int $actor_user_id ) {
		global $wpdb;
		$case = self::find( $case_id );
		if ( ! $case ) {
			return new WP_Error( 'support_case_not_found', __( 'The support case could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$data = array(
			'product'             => SC_EI_Support_Schema::sanitize_product( (string) ( $input['product'] ?? $case['product'] ) ),
			'product_version'     => substr( sanitize_text_field( (string) ( $input['product_version'] ?? $case['product_version'] ) ), 0, 80 ),
			'component'           => substr( sanitize_title( (string) ( $input['component'] ?? $case['component'] ) ), 0, 120 ),
			'issue_type'          => SC_EI_Support_Schema::sanitize_issue_type( (string) ( $input['issue_type'] ?? $case['issue_type'] ) ),
			'environment_json'    => wp_json_encode( self::sanitize_environment( (array) ( $input['environment'] ?? json_decode( (string) $case['environment_json'], true ) ?: array() ) ), JSON_UNESCAPED_SLASHES ),
			'error_message'       => sanitize_textarea_field( (string) ( $input['error_message'] ?? $case['error_message'] ) ),
			'reproduction_steps'  => sanitize_textarea_field( (string) ( $input['reproduction_steps'] ?? $case['reproduction_steps'] ) ),
			'expected_behavior'   => sanitize_textarea_field( (string) ( $input['expected_behavior'] ?? $case['expected_behavior'] ) ),
			'actual_behavior'     => sanitize_textarea_field( (string) ( $input['actual_behavior'] ?? $case['actual_behavior'] ) ),
			'severity'            => SC_EI_Support_Schema::sanitize_severity( (string) ( $input['severity'] ?? $case['severity'] ) ),
			'priority'            => SC_EI_Lifecycle_Schema::sanitize_priority( (string) ( $input['priority'] ?? $case['priority'] ) ),
			'assigned_user_id'    => absint( $input['assigned_user_id'] ?? $case['assigned_user_id'] ) ?: null,
			'known_issue_reference'=> substr( sanitize_text_field( (string) ( $input['known_issue_reference'] ?? $case['known_issue_reference'] ) ), 0, 191 ),
			'sender_summary'      => sanitize_textarea_field( (string) ( $input['sender_summary'] ?? $case['sender_summary'] ) ),
			'sender_next_step'    => sanitize_textarea_field( (string) ( $input['sender_next_step'] ?? $case['sender_next_step'] ) ),
			'row_version'         => absint( $case['row_version'] ) + 1,
			'updated_at'          => current_time( 'mysql', true ),
		);
		$ok = $wpdb->update( SC_EI_Database::table( 'support_cases' ), $data, array( 'id' => $case_id, 'row_version' => absint( $case['row_version'] ) ), self::formats( $data, array( 'assigned_user_id', 'row_version' ) ), array( '%d', '%d' ) );
		if ( 1 !== $ok ) {
			return new WP_Error( 'support_case_concurrent_update', __( 'The support case changed while you were editing it. Reload and try again.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::insert_event( $case_id, absint( $case['inquiry_id'] ), 'support_context_updated', (string) $case['workflow_stage'], (string) $case['workflow_stage'], $actor_user_id, array( 'product' => $data['product'], 'component' => $data['component'], 'severity' => $data['severity'] ) );
		return self::find( $case_id );
	}

	public static function transition( int $case_id, string $to_stage, string $confirmation, string $reason, int $actor_user_id ) {
		global $wpdb;
		$case = self::find( $case_id );
		if ( ! $case ) {
			return new WP_Error( 'support_case_not_found', __( 'The support case could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$to_stage = SC_EI_Support_Schema::sanitize_stage( $to_stage );
		$from_stage = SC_EI_Support_Schema::sanitize_stage( (string) $case['workflow_stage'] );
		if ( ! in_array( $to_stage, SC_EI_Support_Schema::allowed_transitions()[ $from_stage ] ?? array(), true ) ) {
			return new WP_Error( 'support_transition_not_allowed', __( 'That support-case transition is not allowed from the current stage.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$expected = 'MOVE ' . strtoupper( (string) $case['case_number'] ) . ' TO ' . strtoupper( $to_stage );
		if ( strtoupper( trim( sanitize_text_field( $confirmation ) ) ) !== $expected ) {
			return new WP_Error( 'support_transition_confirmation_failed', __( 'The typed support-case confirmation did not match.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		$data = array(
			'workflow_stage' => $to_stage,
			'row_version'    => absint( $case['row_version'] ) + 1,
			'updated_at'     => $now,
			'resolved_at'    => 'resolved' === $to_stage ? $now : $case['resolved_at'],
			'closed_at'      => 'closed' === $to_stage ? $now : $case['closed_at'],
		);
		$ok = $wpdb->update( SC_EI_Database::table( 'support_cases' ), $data, array( 'id' => $case_id, 'row_version' => absint( $case['row_version'] ) ), self::formats( $data, array( 'row_version' ) ), array( '%d', '%d' ) );
		if ( 1 !== $ok ) {
			return new WP_Error( 'support_case_concurrent_update', __( 'The support case changed while you were editing it. Reload and try again.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$event_ok = self::insert_event( $case_id, absint( $case['inquiry_id'] ), 'support_stage_changed', $from_stage, $to_stage, $actor_user_id, array( 'reason' => sanitize_textarea_field( $reason ), 'automatic' => false ) );
		if ( ! $event_ok ) {
			$wpdb->update(
				SC_EI_Database::table( 'support_cases' ),
				array( 'workflow_stage' => $from_stage, 'row_version' => absint( $case['row_version'] ), 'updated_at' => (string) $case['updated_at'], 'resolved_at' => $case['resolved_at'], 'closed_at' => $case['closed_at'] ),
				array( 'id' => $case_id, 'row_version' => absint( $case['row_version'] ) + 1 ),
				array( '%s', '%d', '%s', '%s', '%s' ),
				array( '%d', '%d' )
			);
			$error = new WP_Error( 'support_transition_audit_failed', __( 'The support stage could not be changed because its audit event was not stored.', 'sustainable-catalyst-engagement-intake' ) );
			self::record_failure( 'support_transition_audit_failed', $error, array( 'case_id' => $case_id, 'database_error_hash' => self::database_error_hash() ) );
			return $error;
		}
		SC_EI_Audit_Log::record( 'support_case_stage_changed', 'Authorized staff changed the governed support-case stage.', array( 'case_number' => $case['case_number'], 'from' => $from_stage, 'to' => $to_stage, 'automatic' => false ), absint( $case['inquiry_id'] ), null, $actor_user_id );
		return self::find( $case_id );
	}

	public static function add_link( int $case_id, array $input, int $actor_user_id ) {
		global $wpdb;
		$case = self::find( $case_id );
		if ( ! $case ) {
			return new WP_Error( 'support_case_not_found', __( 'The support case could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$type = sanitize_key( (string) ( $input['related_type'] ?? '' ) );
		if ( ! isset( SC_EI_Support_Schema::relationship_types()[ $type ] ) ) {
			return new WP_Error( 'support_relationship_type_invalid', __( 'Choose a supported relationship type.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$reference = substr( sanitize_text_field( (string) ( $input['related_reference'] ?? '' ) ), 0, 191 );
		if ( '' === $reference ) {
			return new WP_Error( 'support_relationship_reference_required', __( 'Enter the related article, issue, suggestion, release, event, or case reference.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$relation_type = substr( sanitize_key( (string) ( $input['relation_type'] ?? 'related' ) ), 0, 60 );
		$table = SC_EI_Database::table( 'support_case_links' );
		$existing_id = absint( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE support_case_id = %d AND related_type = %s AND related_reference = %s AND relation_type = %s LIMIT 1", $case_id, $type, $reference, $relation_type ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $existing_id ) {
			return $existing_id;
		}
		$now = current_time( 'mysql', true );
		$data = array(
			'public_id'        => wp_generate_uuid4(),
			'support_case_id'  => $case_id,
			'inquiry_id'       => absint( $case['inquiry_id'] ),
			'related_type'     => $type,
			'related_reference'=> $reference,
			'relation_type'    => $relation_type,
			'title'            => substr( sanitize_text_field( (string) ( $input['title'] ?? '' ) ), 0, 191 ),
			'url'              => esc_url_raw( (string) ( $input['url'] ?? '' ) ),
			'sender_visible'   => empty( $input['sender_visible'] ) ? 0 : 1,
			'metadata_json'    => wp_json_encode( array( 'added_by' => $actor_user_id ), JSON_UNESCAPED_SLASHES ),
			'created_by'       => $actor_user_id ?: null,
			'created_at'       => $now,
			'updated_at'       => $now,
		);
		$ok = $wpdb->insert( SC_EI_Database::table( 'support_case_links' ), $data, self::formats( $data, array( 'support_case_id', 'inquiry_id', 'sender_visible', 'created_by' ) ) );
		if ( false === $ok ) {
			$concurrent = self::find_link( $case_id, $type, $reference, $relation_type );
			if ( $concurrent ) {
				return absint( $concurrent['id'] );
			}
			$error = new WP_Error( 'support_relationship_save_failed', __( 'The support relationship could not be saved.', 'sustainable-catalyst-engagement-intake' ) );
			self::record_failure( 'support_relationship_insert_failed', $error, array( 'case_id' => $case_id, 'database_error_hash' => self::database_error_hash() ) );
			return $error;
		}
		self::insert_event( $case_id, absint( $case['inquiry_id'] ), 'support_relationship_added', '', '', $actor_user_id, array( 'related_type' => $type, 'related_reference' => $reference, 'sender_visible' => (bool) $data['sender_visible'] ) );
		return (int) $wpdb->insert_id;
	}

	public static function ingest_handoff( array $payload, int $actor_user_id ) {
		$schema = sanitize_text_field( (string) ( $payload['schema'] ?? '' ) );
		if ( SC_EI_Support_Schema::HANDOFF_SCHEMA !== $schema ) {
			return self::handoff_error( 'support_handoff_schema_invalid', __( 'The product-support handoff schema is unsupported.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$handoff_id = SC_EI_Support_Schema::handoff_id( (string) ( $payload['handoff_id'] ?? '' ) );
		if ( is_wp_error( $handoff_id ) ) {
			return self::handoff_failure( $handoff_id );
		}
		$source_system = SC_EI_Support_Schema::source_system( (string) ( $payload['source_system'] ?? '' ) );
		if ( is_wp_error( $source_system ) ) {
			return self::handoff_failure( $source_system );
		}
		$inquiry_id = absint( $payload['inquiry_id'] ?? 0 );
		if ( ! $inquiry_id ) {
			return self::handoff_failure( new WP_Error( 'support_handoff_inquiry_required', __( 'A canonical inquiry ID is required before product-support context can be attached.', 'sustainable-catalyst-engagement-intake' ) ) );
		}
		$context = (array) ( $payload['context'] ?? array() );
		$product = SC_EI_Support_Schema::strict_product( (string) ( $context['product'] ?? '' ) );
		if ( is_wp_error( $product ) ) {
			return self::handoff_failure( $product );
		}
		$context['product'] = $product;
		$signal = SC_EI_Support_Schema::signal_payload( $context );
		if ( is_wp_error( $signal ) ) {
			return self::handoff_failure( $signal );
		}
		$existing_case = self::for_inquiry( $inquiry_id );
		if ( $existing_case && self::find_link( absint( $existing_case['id'] ), 'external_reference', $handoff_id, 'handoff_receipt' ) ) {
			return array( 'case' => $existing_case, 'context' => $signal, 'schema' => SC_EI_Support_Schema::HANDOFF_SCHEMA, 'handoff_id' => $handoff_id, 'idempotent' => true );
		}
		$case = self::create_for_inquiry(
			$inquiry_id,
			array(
				'product' => $signal['product'],
				'product_version' => $signal['product_version'],
				'component' => $signal['component'],
				'issue_type' => $signal['issue_type'],
				'source_system' => $source_system,
				'source_reference' => substr( sanitize_text_field( (string) ( $payload['source_reference'] ?? $handoff_id ) ), 0, 191 ),
			),
			$actor_user_id
		);
		if ( is_wp_error( $case ) ) {
			return self::handoff_failure( $case );
		}
		$link_inputs = array();
		foreach ( (array) $signal['article_ids'] as $article_id ) {
			$link_inputs[] = array( 'related_type' => 'knowledge_article', 'related_reference' => (string) $article_id, 'relation_type' => 'suggested_before_case', 'sender_visible' => true );
		}
		if ( $signal['known_issue'] ) {
			$link_inputs[] = array( 'related_type' => 'known_issue', 'related_reference' => $signal['known_issue'], 'relation_type' => 'matched_before_case', 'sender_visible' => true );
		}
		if ( $signal['feature_suggestion'] ) {
			$link_inputs[] = array( 'related_type' => 'feature_suggestion', 'related_reference' => $signal['feature_suggestion'], 'relation_type' => 'context_from_handoff', 'sender_visible' => false );
		}
		if ( $signal['product_release'] ) {
			$link_inputs[] = array( 'related_type' => 'product_release', 'related_reference' => $signal['product_release'], 'relation_type' => 'affected_release', 'sender_visible' => true );
		}
		foreach ( $link_inputs as $link_input ) {
			$link_result = self::add_link( absint( $case['id'] ), $link_input, $actor_user_id );
			if ( is_wp_error( $link_result ) ) {
				return self::handoff_failure( $link_result );
			}
		}
		$signal_type = ! empty( $signal['search_query'] ) && empty( $signal['article_ids'] ) ? 'zero_result_search' : ( false === $signal['article_helpful'] ? 'unhelpful_article' : 'recurring_issue' );
		$signal_result = self::record_signal( $signal_type, $signal, $actor_user_id );
		if ( is_wp_error( $signal_result ) ) {
			return self::handoff_failure( $signal_result );
		}
		$receipt_id = self::add_link( absint( $case['id'] ), array( 'related_type' => 'external_reference', 'related_reference' => $handoff_id, 'relation_type' => 'handoff_receipt', 'title' => $source_system, 'sender_visible' => false ), $actor_user_id );
		if ( is_wp_error( $receipt_id ) ) {
			return self::handoff_failure( $receipt_id );
		}
		update_option( 'sc_ei_support_last_handoff_at', current_time( 'mysql', true ), false );
		do_action( 'sc_ei_support_handoff_ingested', $case, $signal, $payload );
		return array( 'case' => $case, 'context' => $signal, 'schema' => SC_EI_Support_Schema::HANDOFF_SCHEMA, 'handoff_id' => $handoff_id, 'idempotent' => false );
	}

	public static function record_signal( string $signal_type, array $payload, int $actor_user_id = 0 ) {
		global $wpdb;
		$signal_type = sanitize_key( $signal_type );
		if ( ! isset( SC_EI_Support_Schema::signal_types()[ $signal_type ] ) ) {
			return new WP_Error( 'support_signal_type_invalid', __( 'Choose a supported product-intelligence signal type.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$clean = SC_EI_Support_Schema::signal_payload( $payload );
		if ( is_wp_error( $clean ) ) {
			return $clean;
		}
		$aggregate_key = hash( 'sha256', implode( '|', array( $signal_type, $clean['product'], $clean['product_version'], $clean['component'], $clean['issue_type'], strtolower( $clean['search_query'] ) ) ) );
		$table = SC_EI_Database::table( 'support_signals' );
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE aggregate_key = %s LIMIT 1", $aggregate_key ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$now = current_time( 'mysql', true );
		$evidence = array(
			'schema'               => 'sc-product-intelligence-signal/1.0',
			'search_query'         => $clean['search_query'],
			'article_ids'          => $clean['article_ids'],
			'known_issue'          => $clean['known_issue'],
			'resolution_attempted' => $clean['resolution_attempted'],
			'article_helpful'      => $clean['article_helpful'],
			'source_url'           => $clean['source_url'],
			'feature_suggestion'   => $clean['feature_suggestion'],
			'product_release'      => $clean['product_release'],
			'contains_personal_data'=> false,
		);
		if ( $existing ) {
			$updated = $wpdb->update( $table, array( 'occurrence_count' => absint( $existing['occurrence_count'] ) + 1, 'evidence_json' => wp_json_encode( $evidence, JSON_UNESCAPED_SLASHES ), 'updated_at' => $now ), array( 'id' => absint( $existing['id'] ) ), array( '%d', '%s', '%s' ), array( '%d' ) );
			if ( false === $updated ) {
				$error = new WP_Error( 'support_signal_update_failed', __( 'The privacy-safe product-intelligence signal could not be updated.', 'sustainable-catalyst-engagement-intake' ) );
				self::record_failure( 'support_signal_update_failed', $error, array( 'aggregate_key' => $aggregate_key, 'database_error_hash' => self::database_error_hash() ) );
				return $error;
			}
			return self::find_signal( absint( $existing['id'] ) );
		}
		$data = array(
			'public_id'            => wp_generate_uuid4(),
			'signal_type'         => $signal_type,
			'product'             => $clean['product'],
			'product_version'     => $clean['product_version'],
			'component'           => $clean['component'],
			'issue_type'          => $clean['issue_type'],
			'aggregate_key'       => $aggregate_key,
			'occurrence_count'    => 1,
			'evidence_json'       => wp_json_encode( $evidence, JSON_UNESCAPED_SLASHES ),
			'contains_personal_data'=> 0,
			'status'              => 'open',
			'created_by'          => $actor_user_id ?: null,
			'created_at'          => $now,
			'updated_at'          => $now,
		);
		$ok = $wpdb->insert( $table, $data, self::formats( $data, array( 'occurrence_count', 'contains_personal_data', 'created_by' ) ) );
		if ( false === $ok ) {
			$concurrent = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE aggregate_key = %s LIMIT 1", $aggregate_key ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( $concurrent ) {
				return self::record_signal( $signal_type, $payload, $actor_user_id );
			}
			$error = new WP_Error( 'support_signal_save_failed', __( 'The privacy-safe product-intelligence signal could not be recorded.', 'sustainable-catalyst-engagement-intake' ) );
			self::record_failure( 'support_signal_insert_failed', $error, array( 'aggregate_key' => $aggregate_key, 'database_error_hash' => self::database_error_hash() ) );
			return $error;
		}
		return self::find_signal( (int) $wpdb->insert_id );
	}

	public static function for_inquiry( int $inquiry_id ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'support_cases' );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE inquiry_id = %d LIMIT 1", $inquiry_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $row ?: null;
	}

	public static function find( int $case_id ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'support_cases' );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $case_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $row ?: null;
	}

	public static function query( array $args = array() ): array {
		global $wpdb;
		$args = wp_parse_args( $args, array( 'stage' => '', 'product' => '', 'severity' => '', 'search' => '', 'limit' => 200 ) );
		$where = array( '1=1' );
		$params = array();
		if ( $args['stage'] ) { $where[] = 'workflow_stage = %s'; $params[] = SC_EI_Support_Schema::sanitize_stage( (string) $args['stage'] ); }
		if ( $args['product'] ) { $where[] = 'product = %s'; $params[] = SC_EI_Support_Schema::sanitize_product( (string) $args['product'] ); }
		if ( $args['severity'] ) { $where[] = 'severity = %s'; $params[] = SC_EI_Support_Schema::sanitize_severity( (string) $args['severity'] ); }
		if ( $args['search'] ) { $like = '%' . $wpdb->esc_like( sanitize_text_field( (string) $args['search'] ) ) . '%'; $where[] = '(case_number LIKE %s OR product_version LIKE %s OR component LIKE %s OR error_message LIKE %s)'; array_push( $params, $like, $like, $like, $like ); }
		$params[] = max( 1, min( 1000, absint( $args['limit'] ) ) );
		$sql = "SELECT * FROM " . SC_EI_Database::table( 'support_cases' ) . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY FIELD(severity,\'critical\',\'high\',\'normal\',\'low\'), updated_at DESC, id DESC LIMIT %d';
		return (array) $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	public static function links( int $case_id, bool $sender_visible_only = false ): array {
		global $wpdb;
		$table = SC_EI_Database::table( 'support_case_links' );
		$sql = "SELECT * FROM {$table} WHERE support_case_id = %d" . ( $sender_visible_only ? ' AND sender_visible = 1' : '' ) . ' ORDER BY created_at DESC, id DESC';
		return (array) $wpdb->get_results( $wpdb->prepare( $sql, $case_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function events( int $case_id, int $limit = 250 ): array {
		global $wpdb;
		$table = SC_EI_Database::table( 'support_case_events' );
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE support_case_id = %d ORDER BY occurred_at DESC, id DESC LIMIT %d", $case_id, max( 1, min( 1000, $limit ) ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function sender_snapshot( int $inquiry_id ): array {
		$case = self::for_inquiry( $inquiry_id );
		if ( ! $case ) {
			return array();
		}
		return array(
			'case_number' => (string) $case['case_number'],
			'product'     => SC_EI_Support_Schema::label( SC_EI_Support_Schema::products(), (string) $case['product'] ),
			'version'     => (string) $case['product_version'],
			'component'   => (string) $case['component'],
			'status'      => SC_EI_Support_Schema::public_stage( (string) $case['workflow_stage'] ),
			'summary'     => (string) $case['sender_summary'],
			'next_step'   => (string) $case['sender_next_step'],
			'known_issue' => (string) $case['known_issue_reference'],
			'links'       => self::links( absint( $case['id'] ), true ),
		);
	}

	public static function export_for_inquiry( int $inquiry_id ): array {
		$case = self::for_inquiry( $inquiry_id );
		return array(
			'case'   => $case,
			'links'  => $case ? self::links( absint( $case['id'] ) ) : array(),
			'events' => $case ? self::events( absint( $case['id'] ) ) : array(),
		);
	}

	public static function redact_for_privacy( int $inquiry_id, string $now ): bool {
		global $wpdb;
		$marker = wp_json_encode( array( 'personal_data_erased' => true, 'support_schema_version' => SC_EI_SUPPORT_SCHEMA_VERSION ), JSON_UNESCAPED_SLASHES );
		$case = $wpdb->query(
			$wpdb->prepare(
				"UPDATE " . SC_EI_Database::table( 'support_cases' ) . " SET environment_json = %s, error_message = %s, reproduction_steps = '', expected_behavior = '', actual_behavior = '', sender_summary = '', sender_next_step = '', source_reference = '', updated_at = %s WHERE inquiry_id = %d",
				$marker,
				'[Support case content erased through Privacy and Retention Center.]',
				$now,
				$inquiry_id
			)
		);
		$events = $wpdb->query( $wpdb->prepare( "UPDATE " . SC_EI_Database::table( 'support_case_events' ) . " SET payload_json = %s WHERE inquiry_id = %d", $marker, $inquiry_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$links = $wpdb->query( $wpdb->prepare( "UPDATE " . SC_EI_Database::table( 'support_case_links' ) . " SET title = '', metadata_json = %s, updated_at = %s WHERE inquiry_id = %d", $marker, $now, $inquiry_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return false !== $case && false !== $events && false !== $links;
	}

	public static function metrics(): array {
		global $wpdb;
		$table = SC_EI_Database::table( 'support_cases' );
		$row = (array) $wpdb->get_row( "SELECT COUNT(*) total, SUM(workflow_stage='new_support_request') untriaged, SUM(workflow_stage='needs_information') awaiting_sender, SUM(severity IN ('high','critical') AND workflow_stage NOT IN ('resolved','closed')) high_priority, SUM(workflow_stage='known_issue') known_issues, SUM(workflow_stage='resolved') resolved, SUM(workflow_stage='closed') closed FROM {$table}", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( $row as $key => $value ) { $row[ $key ] = absint( $value ); }
		$row['signals_open'] = absint( $wpdb->get_var( "SELECT COUNT(*) FROM " . SC_EI_Database::table( 'support_signals' ) . " WHERE status = 'open'" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$row['missing_product'] = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE product = 'other' AND workflow_stage NOT IN ('resolved','closed')" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row['missing_version'] = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE product_version = '' AND workflow_stage NOT IN ('resolved','closed')" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row['missing_component'] = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE component = '' AND workflow_stage NOT IN ('resolved','closed')" ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row['failed_handoffs'] = absint( get_option( 'sc_ei_support_failed_handoff_count', 0 ) );
		$last_success = strtotime( (string) get_option( 'sc_ei_support_last_handoff_at', '' ) . ' UTC' ) ?: 0;
		$last_failure = strtotime( (string) get_option( 'sc_ei_support_last_handoff_failure_at', '' ) . ' UTC' ) ?: 0;
		$row['handoff_reliability_open'] = $last_failure > 0 && $last_success < $last_failure ? 1 : 0;
		return $row;
	}

	public static function find_signal( int $signal_id ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'support_signals' );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $signal_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $row ?: null;
	}


	public static function find_link( int $case_id, string $related_type, string $reference, string $relation_type = 'related' ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'support_case_links' );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE support_case_id = %d AND related_type = %s AND related_reference = %s AND relation_type = %s LIMIT 1", $case_id, sanitize_key( $related_type ), substr( sanitize_text_field( $reference ), 0, 191 ), substr( sanitize_key( $relation_type ), 0, 60 ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $row ?: null;
	}

	private static function handoff_error( string $code, string $message ): WP_Error {
		return self::handoff_failure( new WP_Error( $code, $message ) );
	}

	private static function handoff_failure( WP_Error $error ): WP_Error {
		update_option( 'sc_ei_support_failed_handoff_count', absint( get_option( 'sc_ei_support_failed_handoff_count', 0 ) ) + 1, false );
		update_option( 'sc_ei_support_last_handoff_failure_at', current_time( 'mysql', true ), false );
		self::record_failure( 'support_handoff_rejected', $error, array( 'error_code' => $error->get_error_code() ) );
		return $error;
	}

	private static function record_failure( string $event_type, WP_Error $error, array $context = array() ): void {
		SC_EI_Hardening_Repository::record_event(
			'database',
			sanitize_key( $event_type ),
			'warning',
			'Support operations encountered a persistence or integration failure.',
			array_merge( array( 'error_code' => $error->get_error_code(), 'request_id' => SC_EI_Hardening_Repository::request_id() ), $context )
		);
	}

	private static function database_error_hash(): string {
		global $wpdb;
		$error = isset( $wpdb->last_error ) ? trim( (string) $wpdb->last_error ) : '';
		return '' === $error ? '' : hash_hmac( 'sha256', $error, wp_salt( 'secure_auth' ) );
	}

	private static function insert_event( int $case_id, int $inquiry_id, string $event_type, string $from_stage, string $to_stage, int $actor_user_id, array $payload ): bool {
		global $wpdb;
		$data = array(
			'public_id'       => wp_generate_uuid4(),
			'support_case_id' => $case_id,
			'inquiry_id'      => $inquiry_id,
			'event_type'      => sanitize_key( $event_type ),
			'from_stage'      => sanitize_key( $from_stage ),
			'to_stage'        => sanitize_key( $to_stage ),
			'actor_user_id'   => $actor_user_id ?: null,
			'payload_json'    => wp_json_encode( $payload, JSON_UNESCAPED_SLASHES ),
			'occurred_at'     => current_time( 'mysql', true ),
		);
		return false !== $wpdb->insert( SC_EI_Database::table( 'support_case_events' ), $data, self::formats( $data, array( 'support_case_id', 'inquiry_id', 'actor_user_id' ) ) );
	}

	private static function case_number(): string {
		return 'SUP-' . gmdate( 'Ymd' ) . '-' . strtoupper( substr( str_replace( '-', '', wp_generate_uuid4() ), 0, 8 ) );
	}

	private static function sanitize_environment( array $input ): array {
		$result = array();
		foreach ( array( 'browser', 'os', 'wordpress', 'php', 'plugin_version', 'backend_version', 'url' ) as $key ) {
			$result[ $key ] = 'url' === $key ? esc_url_raw( (string) ( $input[ $key ] ?? '' ) ) : substr( sanitize_text_field( (string) ( $input[ $key ] ?? '' ) ), 0, 191 );
		}
		return $result;
	}

	private static function find_migration( int $id ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'platform_migrations' );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $row ?: null;
	}

	private static function formats( array $data, array $integer_keys = array() ): array {
		return array_map( static fn( string $key ): string => in_array( $key, $integer_keys, true ) ? '%d' : '%s', array_keys( $data ) );
	}
}
