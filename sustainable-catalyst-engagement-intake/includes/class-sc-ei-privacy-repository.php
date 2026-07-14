<?php
/**
 * Privacy requests, consent ledger, legal holds, retention actions, and lifecycle metrics.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Privacy_Repository {

	public static function register(): void {
		add_action( 'sc_ei_public_inquiry_created', array( __CLASS__, 'capture_public_consent' ), 5, 2 );
	}

	public static function capture_public_consent( array $inquiry, array $raw = array() ): void {
		$inquiry_id = absint( $inquiry['id'] ?? 0 );
		if ( ! $inquiry_id ) {
			return;
		}
		self::record_consent(
			$inquiry_id,
			array(
				'consent_type'    => 'privacy_notice',
				'action'          => 'granted',
				'consent_version' => sanitize_text_field( (string) ( $inquiry['consent_version'] ?? $raw['consent_version'] ?? '' ) ),
				'lawful_basis'    => 'request_processing',
				'source'          => 'public_form',
				'evidence_text'   => 'Public intake privacy acknowledgment recorded at successful submission.',
				'occurred_at'     => sanitize_text_field( (string) ( $inquiry['consent_at'] ?? current_time( 'mysql', true ) ) ),
			),
			0
		);
		if ( ! empty( $inquiry['calendar_invite_consent'] ) ) {
			self::record_consent(
				$inquiry_id,
				array(
					'consent_type'    => 'calendar_invitation',
					'action'          => 'granted',
					'consent_version' => sanitize_text_field( (string) ( $inquiry['consent_version'] ?? '' ) ),
					'lawful_basis'    => 'consent',
					'source'          => 'public_form',
					'evidence_text'   => 'Calendar invitation consent recorded through the intake form.',
					'occurred_at'     => sanitize_text_field( (string) ( $inquiry['consent_at'] ?? current_time( 'mysql', true ) ) ),
				),
				0
			);
		}
	}


	public static function create_request( array $input, int $actor_user_id = 0 ) {
		global $wpdb;

		$email = sanitize_email( (string) ( $input['requester_email'] ?? '' ) );
		$name = sanitize_text_field( (string) ( $input['requester_name'] ?? '' ) );
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'privacy_request_email_invalid', __( 'A valid requester email is required.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$type = SC_EI_Privacy_Schema::sanitize_choice( (string) ( $input['request_type'] ?? 'access' ), SC_EI_Privacy_Schema::request_types(), 'access' );
		$status = SC_EI_Privacy_Schema::sanitize_choice( (string) ( $input['status'] ?? 'received' ), SC_EI_Privacy_Schema::request_statuses(), 'received' );
		$identity = SC_EI_Privacy_Schema::sanitize_choice( (string) ( $input['identity_status'] ?? 'unverified' ), SC_EI_Privacy_Schema::identity_statuses(), 'unverified' );
		$source = SC_EI_Privacy_Schema::sanitize_choice( (string) ( $input['source'] ?? 'admin' ), SC_EI_Privacy_Schema::request_sources(), 'admin' );
		$summary = sanitize_textarea_field( (string) ( $input['request_summary'] ?? '' ) );
		if ( '' === $summary ) {
			return new WP_Error( 'privacy_request_summary_required', __( 'Record a concise request summary.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$inquiry_id = absint( $input['inquiry_id'] ?? 0 );
		if ( $inquiry_id && ! SC_EI_Inquiry_Repository::find( $inquiry_id ) ) {
			return new WP_Error( 'privacy_request_inquiry_invalid', __( 'The linked inquiry could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Privacy_Schema::default_settings() );
		$received_at = self::sanitize_datetime( $input['received_at'] ?? '' ) ?: current_time( 'mysql', true );
		$due_at = self::sanitize_datetime( $input['due_at'] ?? '' );
		if ( ! $due_at ) {
			$due_at = gmdate( 'Y-m-d H:i:s', strtotime( $received_at . ' UTC' ) + max( 1, absint( $settings['privacy_request_due_days'] ?? 30 ) ) * DAY_IN_SECONDS );
		}
		$now = current_time( 'mysql', true );
		$data = array(
			'public_id'          => wp_generate_uuid4(),
			'inquiry_id'         => $inquiry_id ?: null,
			'requester_name'     => $name,
			'requester_email'    => $email,
			'request_type'       => $type,
			'status'             => $status,
			'identity_status'    => $identity,
			'source'             => $source,
			'received_at'        => $received_at,
			'due_at'             => $due_at,
			'assigned_user_id'   => absint( $input['assigned_user_id'] ?? 0 ) ?: null,
			'request_summary'    => $summary,
			'resolution_summary' => sanitize_textarea_field( (string) ( $input['resolution_summary'] ?? '' ) ),
			'evidence_json'      => wp_json_encode( array( 'privacy_schema_version' => SC_EI_PRIVACY_SCHEMA_VERSION ) ),
			'completed_at'       => in_array( $status, array( 'completed', 'denied', 'withdrawn' ), true ) ? $now : null,
			'created_by'         => $actor_user_id ?: null,
			'updated_by'         => $actor_user_id ?: null,
			'created_at'         => $now,
			'updated_at'         => $now,
		);
		$integer = array( 'inquiry_id', 'assigned_user_id', 'created_by', 'updated_by' );
		$formats = self::formats( $data, $integer );
		if ( false === $wpdb->insert( SC_EI_Database::table( 'privacy_requests' ), $data, $formats ) ) {
			return new WP_Error( 'privacy_request_save_failed', __( 'The privacy request could not be saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$id = (int) $wpdb->insert_id;
		if ( $inquiry_id && in_array( $type, array( 'erasure', 'restriction', 'withdrawal', 'objection' ), true ) ) {
			self::set_inquiry_privacy_state(
				$inquiry_id,
				'erasure' === $type ? 'erasure_requested' : 'restricted',
				$summary,
				$actor_user_id
			);
		}
		SC_EI_Audit_Log::record(
			'privacy_request_created',
			'Privacy request case created.',
			array( 'request_id' => $id, 'type' => $type, 'status' => $status, 'due_at' => $due_at ),
			$inquiry_id ?: null,
			null,
			$actor_user_id ?: null
		);
		return self::find_request( $id );
	}

	public static function update_request( int $id, array $input, int $actor_user_id ) {
		global $wpdb;

		$current = self::find_request( $id );
		if ( ! $current ) {
			return new WP_Error( 'privacy_request_not_found', __( 'The privacy request could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$status = SC_EI_Privacy_Schema::sanitize_choice( (string) ( $input['status'] ?? $current['status'] ), SC_EI_Privacy_Schema::request_statuses(), $current['status'] );
		$identity = SC_EI_Privacy_Schema::sanitize_choice( (string) ( $input['identity_status'] ?? $current['identity_status'] ), SC_EI_Privacy_Schema::identity_statuses(), $current['identity_status'] );
		$resolution = sanitize_textarea_field( (string) ( $input['resolution_summary'] ?? $current['resolution_summary'] ) );
		if ( in_array( $status, array( 'completed', 'denied' ), true ) && '' === trim( $resolution ) ) {
			return new WP_Error( 'privacy_resolution_required', __( 'A resolution summary is required before completing or denying a privacy request.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$due_at = self::sanitize_datetime( $input['due_at'] ?? $current['due_at'] );
		$now = current_time( 'mysql', true );
		$data = array(
			'status'             => $status,
			'identity_status'    => $identity,
			'assigned_user_id'   => absint( $input['assigned_user_id'] ?? $current['assigned_user_id'] ) ?: null,
			'due_at'             => $due_at,
			'request_summary'    => sanitize_textarea_field( (string) ( $input['request_summary'] ?? $current['request_summary'] ) ),
			'resolution_summary' => $resolution,
			'completed_at'       => in_array( $status, array( 'completed', 'denied', 'withdrawn' ), true ) ? ( $current['completed_at'] ?: $now ) : null,
			'updated_by'         => $actor_user_id ?: null,
			'updated_at'         => $now,
		);
		$updated = $wpdb->update(
			SC_EI_Database::table( 'privacy_requests' ),
			$data,
			array( 'id' => $id ),
			self::formats( $data, array( 'assigned_user_id', 'updated_by' ) ),
			array( '%d' )
		);
		if ( false === $updated ) {
			return new WP_Error( 'privacy_request_update_failed', __( 'The privacy request could not be updated.', 'sustainable-catalyst-engagement-intake' ) );
		}
		SC_EI_Audit_Log::record(
			'privacy_request_updated',
			'Privacy request case updated.',
			array(
				'request_id'     => $id,
				'old_status'     => $current['status'],
				'new_status'     => $status,
				'identity_status'=> $identity,
			),
			absint( $current['inquiry_id'] ) ?: null,
			null,
			$actor_user_id
		);
		return self::find_request( $id );
	}

	public static function find_request( int $id ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'privacy_requests' );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $row ?: null;
	}

	public static function requests( array $args = array() ): array {
		global $wpdb;
		$args = wp_parse_args( $args, array( 'status' => '', 'type' => '', 'search' => '', 'page' => 1, 'per_page' => 25 ) );
		$table = SC_EI_Database::table( 'privacy_requests' );
		$inquiries = SC_EI_Database::table( 'inquiries' );
		$where = array( '1=1' );
		$values = array();
		$status = sanitize_key( (string) $args['status'] );
		if ( array_key_exists( $status, SC_EI_Privacy_Schema::request_statuses() ) ) {
			$where[] = 'r.status = %s'; $values[] = $status;
		}
		$type = sanitize_key( (string) $args['type'] );
		if ( array_key_exists( $type, SC_EI_Privacy_Schema::request_types() ) ) {
			$where[] = 'r.request_type = %s'; $values[] = $type;
		}
		$search = sanitize_text_field( (string) $args['search'] );
		if ( '' !== $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$where[] = '(r.requester_name LIKE %s OR r.requester_email LIKE %s OR r.request_summary LIKE %s OR i.reference LIKE %s)';
			array_push( $values, $like, $like, $like, $like );
		}
		$page = max( 1, absint( $args['page'] ) );
		$per_page = max( 1, min( 100, absint( $args['per_page'] ) ) );
		$offset = ( $page - 1 ) * $per_page;
		$where_sql = implode( ' AND ', $where );
		$join = "LEFT JOIN {$inquiries} i ON i.id = r.inquiry_id LEFT JOIN {$wpdb->users} u ON u.ID = r.assigned_user_id";
		$count_sql = "SELECT COUNT(*) FROM {$table} r {$join} WHERE {$where_sql}";
		$data_sql = "SELECT r.*, i.reference, i.contact_name, u.display_name AS assigned_name
			FROM {$table} r {$join}
			WHERE {$where_sql}
			ORDER BY CASE WHEN r.status IN ('completed','denied','withdrawn') THEN 1 ELSE 0 END ASC,
				r.due_at ASC, r.received_at DESC
			LIMIT %d OFFSET %d";
		$total = (int) $wpdb->get_var( $values ? $wpdb->prepare( $count_sql, $values ) : $count_sql );
		$rows = (array) $wpdb->get_results( $wpdb->prepare( $data_sql, array_merge( $values, array( $per_page, $offset ) ) ), ARRAY_A );
		return array( 'items' => $rows, 'total' => $total, 'total_pages' => max( 1, (int) ceil( $total / $per_page ) ) );
	}

	public static function record_consent( int $inquiry_id, array $input, int $actor_user_id = 0 ) {
		global $wpdb;
		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $inquiry ) {
			return new WP_Error( 'consent_inquiry_not_found', __( 'The inquiry could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$type = SC_EI_Privacy_Schema::sanitize_choice( (string) ( $input['consent_type'] ?? 'privacy_notice' ), SC_EI_Privacy_Schema::consent_types(), 'privacy_notice' );
		$action = SC_EI_Privacy_Schema::sanitize_choice( (string) ( $input['action'] ?? 'granted' ), SC_EI_Privacy_Schema::consent_actions(), 'granted' );
		$basis = SC_EI_Privacy_Schema::sanitize_choice( (string) ( $input['lawful_basis'] ?? 'request_processing' ), SC_EI_Privacy_Schema::lawful_bases(), 'request_processing' );
		$source = sanitize_key( (string) ( $input['source'] ?? 'admin' ) );
		$occurred_at = self::sanitize_datetime( $input['occurred_at'] ?? '' ) ?: current_time( 'mysql', true );
		$data = array(
			'public_id'          => wp_generate_uuid4(),
			'inquiry_id'         => $inquiry_id,
			'consent_type'       => $type,
			'action'             => $action,
			'consent_version'    => sanitize_text_field( (string) ( $input['consent_version'] ?? $inquiry['consent_version'] ) ),
			'lawful_basis'       => $basis,
			'source'             => $source ?: 'admin',
			'evidence_text'      => sanitize_textarea_field( (string) ( $input['evidence_text'] ?? '' ) ),
			'subject_email_hash' => hash( 'sha256', strtolower( trim( (string) $inquiry['contact_email'] ) ) ),
			'actor_user_id'      => $actor_user_id ?: null,
			'occurred_at'        => $occurred_at,
			'created_at'         => current_time( 'mysql', true ),
		);
		if ( false === $wpdb->insert( SC_EI_Database::table( 'consent_events' ), $data, self::formats( $data, array( 'inquiry_id', 'actor_user_id' ) ) ) ) {
			return new WP_Error( 'consent_event_failed', __( 'The consent event could not be recorded.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( 'withdrawn' === $action ) {
			self::set_inquiry_privacy_state( $inquiry_id, 'restricted', 'Consent or authorization withdrawn: ' . $type, $actor_user_id );
		}
		SC_EI_Audit_Log::record(
			'consent_event_recorded',
			'Consent or authorization event recorded.',
			array( 'consent_type' => $type, 'action' => $action, 'version' => $data['consent_version'], 'lawful_basis' => $basis ),
			$inquiry_id,
			null,
			$actor_user_id ?: null
		);
		return (int) $wpdb->insert_id;
	}

	public static function consent_events( array $args = array() ): array {
		global $wpdb;
		$args = wp_parse_args( $args, array( 'inquiry_id' => 0, 'action' => '', 'search' => '', 'limit' => 250 ) );
		$table = SC_EI_Database::table( 'consent_events' );
		$inquiries = SC_EI_Database::table( 'inquiries' );
		$where = array( '1=1' ); $values = array();
		if ( absint( $args['inquiry_id'] ) ) { $where[] = 'c.inquiry_id = %d'; $values[] = absint( $args['inquiry_id'] ); }
		$action = sanitize_key( (string) $args['action'] );
		if ( array_key_exists( $action, SC_EI_Privacy_Schema::consent_actions() ) ) { $where[] = 'c.action = %s'; $values[] = $action; }
		$search = sanitize_text_field( (string) $args['search'] );
		if ( '' !== $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$where[] = '(i.reference LIKE %s OR i.contact_name LIKE %s OR i.contact_email LIKE %s OR c.evidence_text LIKE %s)';
			array_push( $values, $like, $like, $like, $like );
		}
		$sql = "SELECT c.*, i.reference, i.contact_name FROM {$table} c
			INNER JOIN {$inquiries} i ON i.id = c.inquiry_id
			WHERE " . implode( ' AND ', $where ) . "
			ORDER BY c.occurred_at DESC, c.id DESC LIMIT %d";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, array_merge( $values, array( max( 1, min( 1000, absint( $args['limit'] ) ) ) ) ) ), ARRAY_A );
		return (array) $rows;
	}

	public static function place_hold( array $input, int $actor_user_id ) {
		global $wpdb;
		$inquiry_id = absint( $input['inquiry_id'] ?? 0 );
		$attachment_id = absint( $input['attachment_id'] ?? 0 );
		$scope = SC_EI_Privacy_Schema::sanitize_choice( (string) ( $input['scope'] ?? 'inquiry' ), SC_EI_Privacy_Schema::hold_scopes(), 'inquiry' );
		if ( ! $inquiry_id || ! SC_EI_Inquiry_Repository::find( $inquiry_id ) ) {
			return new WP_Error( 'hold_inquiry_invalid', __( 'A valid inquiry is required for a legal hold.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( 'attachment' === $scope ) {
			$attachment = SC_EI_Attachment_Repository::find( $attachment_id );
			if ( ! $attachment || absint( $attachment['inquiry_id'] ) !== $inquiry_id ) {
				return new WP_Error( 'hold_attachment_invalid', __( 'The selected document does not belong to the inquiry.', 'sustainable-catalyst-engagement-intake' ) );
			}
		} else {
			$attachment_id = 0;
		}
		$reason = sanitize_textarea_field( (string) ( $input['reason'] ?? '' ) );
		$authority = sanitize_text_field( (string) ( $input['authority'] ?? '' ) );
		if ( '' === $reason || '' === $authority ) {
			return new WP_Error( 'hold_reason_required', __( 'A hold reason and authority are required.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Privacy_Schema::default_settings() );
		$placed_at = current_time( 'mysql', true );
		$review_at = self::sanitize_datetime( $input['review_at'] ?? '' )
			?: gmdate( 'Y-m-d H:i:s', time() + max( 1, absint( $settings['legal_hold_review_days'] ?? 90 ) ) * DAY_IN_SECONDS );
		$data = array(
			'public_id'       => wp_generate_uuid4(),
			'inquiry_id'      => $inquiry_id,
			'attachment_id'   => $attachment_id ?: null,
			'scope'           => $scope,
			'status'          => 'active',
			'reason'          => $reason,
			'authority'       => $authority,
			'placed_by'       => $actor_user_id ?: null,
			'placed_at'       => $placed_at,
			'review_at'       => $review_at,
			'released_by'     => null,
			'released_at'     => null,
			'release_reason'  => '',
			'metadata_json'   => wp_json_encode( array( 'privacy_schema_version' => SC_EI_PRIVACY_SCHEMA_VERSION ) ),
			'created_at'      => $placed_at,
			'updated_at'      => $placed_at,
		);
		if ( false === $wpdb->insert( SC_EI_Database::table( 'legal_holds' ), $data, self::formats( $data, array( 'inquiry_id', 'attachment_id', 'placed_by', 'released_by' ) ) ) ) {
			return new WP_Error( 'hold_save_failed', __( 'The legal hold could not be saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::refresh_hold_count( $inquiry_id );
		self::block_queued_actions_for_hold( $inquiry_id, $attachment_id );
		SC_EI_Audit_Log::record(
			'legal_hold_placed',
			'Legal or operational hold placed.',
			array( 'hold_id' => (int) $wpdb->insert_id, 'scope' => $scope, 'authority' => $authority, 'review_at' => $review_at ),
			$inquiry_id,
			$attachment_id ?: null,
			$actor_user_id
		);
		return (int) $wpdb->insert_id;
	}

	public static function release_hold( int $id, string $reason, int $actor_user_id ) {
		global $wpdb;
		$table = SC_EI_Database::table( 'legal_holds' );
		$hold = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $hold || 'active' !== $hold['status'] ) {
			return new WP_Error( 'hold_not_active', __( 'The active legal hold could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$reason = sanitize_textarea_field( $reason );
		if ( '' === $reason ) {
			return new WP_Error( 'hold_release_reason_required', __( 'A release reason is required.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		$updated = $wpdb->update(
			$table,
			array( 'status' => 'released', 'released_by' => $actor_user_id, 'released_at' => $now, 'release_reason' => $reason, 'updated_at' => $now ),
			array( 'id' => $id, 'status' => 'active' ),
			array( '%s', '%d', '%s', '%s', '%s' ),
			array( '%d', '%s' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'hold_release_failed', __( 'The legal hold could not be released.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::refresh_hold_count( absint( $hold['inquiry_id'] ) );
		SC_EI_Audit_Log::record(
			'legal_hold_released',
			'Legal or operational hold released.',
			array( 'hold_id' => $id, 'scope' => $hold['scope'], 'release_reason' => $reason ),
			absint( $hold['inquiry_id'] ),
			absint( $hold['attachment_id'] ) ?: null,
			$actor_user_id
		);
		return true;
	}

	public static function holds( array $args = array() ): array {
		global $wpdb;
		$args = wp_parse_args( $args, array( 'status' => '', 'search' => '', 'limit' => 500 ) );
		$table = SC_EI_Database::table( 'legal_holds' );
		$inquiries = SC_EI_Database::table( 'inquiries' );
		$attachments = SC_EI_Database::table( 'attachments' );
		$where = array( '1=1' ); $values = array();
		$status = sanitize_key( (string) $args['status'] );
		if ( array_key_exists( $status, SC_EI_Privacy_Schema::hold_statuses() ) ) { $where[] = 'h.status = %s'; $values[] = $status; }
		$search = sanitize_text_field( (string) $args['search'] );
		if ( '' !== $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$where[] = '(i.reference LIKE %s OR i.contact_name LIKE %s OR h.reason LIKE %s OR h.authority LIKE %s OR a.original_name LIKE %s)';
			array_push( $values, $like, $like, $like, $like, $like );
		}
		$sql = "SELECT h.*, i.reference, i.contact_name, a.original_name, u.display_name AS placed_by_name
			FROM {$table} h
			LEFT JOIN {$inquiries} i ON i.id = h.inquiry_id
			LEFT JOIN {$attachments} a ON a.id = h.attachment_id
			LEFT JOIN {$wpdb->users} u ON u.ID = h.placed_by
			WHERE " . implode( ' AND ', $where ) . "
			ORDER BY CASE WHEN h.status = 'active' THEN 0 ELSE 1 END, h.review_at ASC, h.placed_at DESC
			LIMIT %d";
		return (array) $wpdb->get_results( $wpdb->prepare( $sql, array_merge( $values, array( max( 1, min( 1000, absint( $args['limit'] ) ) ) ) ) ), ARRAY_A );
	}

	public static function active_hold( int $inquiry_id, int $attachment_id = 0 ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'legal_holds' );
		if ( $attachment_id ) {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$table} WHERE status = 'active' AND inquiry_id = %d AND (scope = 'inquiry' OR (scope = 'attachment' AND attachment_id = %d)) ORDER BY id ASC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$inquiry_id,
				$attachment_id
			);
		} else {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$table} WHERE status = 'active' AND inquiry_id = %d ORDER BY CASE WHEN scope = 'inquiry' THEN 0 ELSE 1 END, id ASC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$inquiry_id
			);
		}
		$row = $wpdb->get_row( $sql, ARRAY_A );
		return $row ?: null;
	}

	public static function queue_action( array $input, int $actor_user_id = 0 ) {
		global $wpdb;
		$target_type = SC_EI_Privacy_Schema::sanitize_choice( (string) ( $input['target_type'] ?? 'attachment' ), SC_EI_Privacy_Schema::target_types(), 'attachment' );
		$target_id = absint( $input['target_id'] ?? 0 );
		$inquiry_id = absint( $input['inquiry_id'] ?? 0 );
		$action_type = SC_EI_Privacy_Schema::sanitize_choice( (string) ( $input['action_type'] ?? 'archive_only' ), SC_EI_Privacy_Schema::retention_action_types(), 'archive_only' );
		$policy_key = sanitize_key( (string) ( $input['policy_key'] ?? '' ) );
		$policy_version = absint( $input['policy_version'] ?? 0 );
		$due_at = self::sanitize_datetime( $input['due_at'] ?? '' ) ?: current_time( 'mysql', true );
		$dedupe_key = sanitize_text_field( (string) ( $input['dedupe_key'] ?? sprintf( '%s:%d:%s:%d', $target_type, $target_id, $action_type, $policy_version ) ) );
		if ( ! $target_id || ! $inquiry_id || '' === $dedupe_key ) {
			return new WP_Error( 'retention_action_target_invalid', __( 'Retention action target information is incomplete.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$existing = self::find_action_by_dedupe( $dedupe_key );
		if ( $existing ) {
			return $existing;
		}
		$hold = self::active_hold( $inquiry_id, 'attachment' === $target_type ? $target_id : 0 );
		$status = $hold ? 'blocked_hold' : 'queued';
		$now = current_time( 'mysql', true );
		$data = array(
			'public_id'      => wp_generate_uuid4(),
			'inquiry_id'     => $inquiry_id,
			'target_type'    => $target_type,
			'target_id'      => $target_id,
			'policy_key'     => $policy_key,
			'policy_version' => $policy_version,
			'action_type'    => $action_type,
			'status'         => $status,
			'reason'         => sanitize_textarea_field( (string) ( $input['reason'] ?? '' ) ),
			'due_at'         => $due_at,
			'dedupe_key'     => $dedupe_key,
			'proposed_by'    => $actor_user_id ?: null,
			'proposed_at'    => $now,
			'approved_by'    => null,
			'approved_at'    => null,
			'executed_by'    => null,
			'executed_at'    => null,
			'verified_at'    => null,
			'failure_code'   => $hold ? 'legal_hold_active' : '',
			'failure_message'=> $hold ? sanitize_textarea_field( (string) $hold['reason'] ) : '',
			'snapshot_json'  => wp_json_encode( $input['snapshot'] ?? array() ),
			'action_version' => 0,
			'created_at'     => $now,
			'updated_at'     => $now,
		);
		if ( false === $wpdb->insert( SC_EI_Database::table( 'retention_actions' ), $data, self::formats( $data, array( 'inquiry_id', 'target_id', 'policy_version', 'proposed_by', 'approved_by', 'executed_by', 'action_version' ) ) ) ) {
			$existing = self::find_action_by_dedupe( $dedupe_key );
			return $existing ?: new WP_Error( 'retention_action_queue_failed', __( 'The retention action could not be queued.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$id = (int) $wpdb->insert_id;
		SC_EI_Audit_Log::record(
			'retention_action_queued',
			'Retention or privacy lifecycle action queued for human review.',
			array( 'action_id' => $id, 'target_type' => $target_type, 'target_id' => $target_id, 'action_type' => $action_type, 'status' => $status, 'policy_key' => $policy_key ),
			$inquiry_id,
			'attachment' === $target_type ? $target_id : null,
			$actor_user_id ?: null
		);
		return self::find_action( $id );
	}

	public static function find_action( int $id ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'retention_actions' );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $row ?: null;
	}

	public static function find_action_by_dedupe( string $key ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'retention_actions' );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE dedupe_key = %s", sanitize_text_field( $key ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $row ?: null;
	}

	public static function retention_actions( array $args = array() ): array {
		global $wpdb;
		$args = wp_parse_args( $args, array( 'status' => '', 'target_type' => '', 'search' => '', 'limit' => 500 ) );
		$table = SC_EI_Database::table( 'retention_actions' );
		$inquiries = SC_EI_Database::table( 'inquiries' );
		$attachments = SC_EI_Database::table( 'attachments' );
		$where = array( '1=1' ); $values = array();
		$status = sanitize_key( (string) $args['status'] );
		if ( array_key_exists( $status, SC_EI_Privacy_Schema::retention_action_statuses() ) ) { $where[] = 'a.status = %s'; $values[] = $status; }
		$target = sanitize_key( (string) $args['target_type'] );
		if ( array_key_exists( $target, SC_EI_Privacy_Schema::target_types() ) ) { $where[] = 'a.target_type = %s'; $values[] = $target; }
		$search = sanitize_text_field( (string) $args['search'] );
		if ( '' !== $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$where[] = '(i.reference LIKE %s OR i.contact_name LIKE %s OR d.original_name LIKE %s OR a.reason LIKE %s OR a.policy_key LIKE %s)';
			array_push( $values, $like, $like, $like, $like, $like );
		}
		$sql = "SELECT a.*, i.reference, i.contact_name, d.original_name,
			p.display_name AS proposed_name, ap.display_name AS approved_name, ex.display_name AS executed_name
			FROM {$table} a
			LEFT JOIN {$inquiries} i ON i.id = a.inquiry_id
			LEFT JOIN {$attachments} d ON d.id = a.target_id AND a.target_type = 'attachment'
			LEFT JOIN {$wpdb->users} p ON p.ID = a.proposed_by
			LEFT JOIN {$wpdb->users} ap ON ap.ID = a.approved_by
			LEFT JOIN {$wpdb->users} ex ON ex.ID = a.executed_by
			WHERE " . implode( ' AND ', $where ) . "
			ORDER BY CASE a.status
				WHEN 'failed' THEN 0 WHEN 'blocked_hold' THEN 1 WHEN 'blocked_dependency' THEN 2
				WHEN 'queued' THEN 3 WHEN 'approved' THEN 4 ELSE 5 END,
				a.due_at ASC, a.id DESC LIMIT %d";
		return (array) $wpdb->get_results( $wpdb->prepare( $sql, array_merge( $values, array( max( 1, min( 1000, absint( $args['limit'] ) ) ) ) ) ), ARRAY_A );
	}

	public static function update_action( int $id, array $data, array $integer_fields = array() ): bool {
		global $wpdb;
		$data['updated_at'] = current_time( 'mysql', true );
		return false !== $wpdb->update(
			SC_EI_Database::table( 'retention_actions' ),
			$data,
			array( 'id' => $id ),
			self::formats( $data, $integer_fields ),
			array( '%d' )
		);
	}

	public static function approve_action( int $id, int $actor_user_id ) {
		$action = self::find_action( $id );
		if ( ! $action || ! in_array( $action['status'], array( 'queued', 'blocked_hold', 'blocked_dependency', 'failed' ), true ) ) {
			return new WP_Error( 'retention_action_not_approvable', __( 'The retention action is not available for approval.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Privacy_Schema::default_settings() );
		if ( ! empty( $settings['require_distinct_retention_approver'] ) && absint( $action['proposed_by'] ) === $actor_user_id ) {
			return new WP_Error( 'distinct_approver_required', __( 'A different authorized person must approve this action.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$hold = self::active_hold( absint( $action['inquiry_id'] ), 'attachment' === $action['target_type'] ? absint( $action['target_id'] ) : 0 );
		if ( $hold ) {
			self::update_action( $id, array( 'status' => 'blocked_hold', 'failure_code' => 'legal_hold_active', 'failure_message' => sanitize_textarea_field( $hold['reason'] ), 'action_version' => absint( $action['action_version'] ) + 1 ), array( 'action_version' ) );
			return new WP_Error( 'legal_hold_active', __( 'An active legal hold blocks approval.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		self::update_action(
			$id,
			array(
				'status' => 'approved', 'approved_by' => $actor_user_id, 'approved_at' => $now,
				'failure_code' => '', 'failure_message' => '', 'action_version' => absint( $action['action_version'] ) + 1,
			),
			array( 'approved_by', 'action_version' )
		);
		SC_EI_Audit_Log::record(
			'retention_action_approved',
			'Retention action approved for execution.',
			array( 'action_id' => $id, 'action_type' => $action['action_type'], 'target_type' => $action['target_type'] ),
			absint( $action['inquiry_id'] ),
			'attachment' === $action['target_type'] ? absint( $action['target_id'] ) : null,
			$actor_user_id
		);
		return self::find_action( $id );
	}

	public static function cancel_action( int $id, string $reason, int $actor_user_id ) {
		$action = self::find_action( $id );
		if ( ! $action || in_array( $action['status'], array( 'executed', 'canceled' ), true ) ) {
			return new WP_Error( 'retention_action_not_cancelable', __( 'The retention action cannot be canceled.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$reason = sanitize_textarea_field( $reason );
		if ( '' === $reason ) {
			return new WP_Error( 'retention_cancel_reason_required', __( 'A cancellation reason is required.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::update_action(
			$id,
			array( 'status' => 'canceled', 'failure_code' => 'canceled_by_authorized_user', 'failure_message' => $reason, 'action_version' => absint( $action['action_version'] ) + 1 ),
			array( 'action_version' )
		);
		SC_EI_Audit_Log::record(
			'retention_action_canceled',
			'Retention action canceled.',
			array( 'action_id' => $id, 'reason' => $reason ),
			absint( $action['inquiry_id'] ),
			'attachment' === $action['target_type'] ? absint( $action['target_id'] ) : null,
			$actor_user_id
		);
		return true;
	}

	public static function set_inquiry_privacy_state( int $inquiry_id, string $status, string $reason, int $actor_user_id ): bool {
		global $wpdb;
		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $inquiry ) {
			return false;
		}
		$status = SC_EI_Privacy_Schema::sanitize_choice( $status, SC_EI_Privacy_Schema::privacy_statuses(), 'active' );
		if ( 'erased' === $status ) {
			return false;
		}
		$data = array(
			'privacy_status'             => $status,
			'privacy_restriction_reason' => sanitize_textarea_field( $reason ),
			'last_privacy_review_at'     => current_time( 'mysql', true ),
			'last_privacy_review_by'     => $actor_user_id ?: null,
			'privacy_version'            => absint( $inquiry['privacy_version'] ) + 1,
			'updated_at'                 => current_time( 'mysql', true ),
		);
		return false !== $wpdb->update(
			SC_EI_Database::table( 'inquiries' ),
			$data,
			array( 'id' => $inquiry_id ),
			self::formats( $data, array( 'last_privacy_review_by', 'privacy_version' ) ),
			array( '%d' )
		);
	}

	public static function refresh_hold_count( int $inquiry_id ): void {
		global $wpdb;
		$holds = SC_EI_Database::table( 'legal_holds' );
		$count = (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM {$holds} WHERE inquiry_id = %d AND status = 'active'", $inquiry_id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
		$wpdb->update(
			SC_EI_Database::table( 'inquiries' ),
			array( 'legal_hold_count' => $count, 'updated_at' => current_time( 'mysql', true ) ),
			array( 'id' => $inquiry_id ),
			array( '%d', '%s' ),
			array( '%d' )
		);
	}

	public static function metrics(): array {
		global $wpdb;
		$requests = SC_EI_Database::table( 'privacy_requests' );
		$holds = SC_EI_Database::table( 'legal_holds' );
		$actions = SC_EI_Database::table( 'retention_actions' );
		$consents = SC_EI_Database::table( 'consent_events' );
		$inquiries = SC_EI_Database::table( 'inquiries' );
		$now = current_time( 'mysql', true );
		return array(
			'open_requests' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$requests} WHERE status NOT IN ('completed','denied','withdrawn')" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'overdue_requests' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$requests} WHERE status NOT IN ('completed','denied','withdrawn') AND due_at IS NOT NULL AND due_at < %s", $now ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'active_holds' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$holds} WHERE status = 'active'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'holds_due_review' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$holds} WHERE status = 'active' AND review_at IS NOT NULL AND review_at <= %s", $now ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'queued_actions' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$actions} WHERE status = 'queued'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'approved_actions' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$actions} WHERE status = 'approved'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'blocked_actions' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$actions} WHERE status IN ('blocked_hold','blocked_dependency')" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'failed_actions' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$actions} WHERE status = 'failed'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'executed_actions' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$actions} WHERE status = 'executed'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'restricted_inquiries' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$inquiries} WHERE privacy_status IN ('restricted','erasure_requested')" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'erased_inquiries' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$inquiries} WHERE privacy_status = 'erased'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'consent_events' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$consents}" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	public static function data_inventory(): array {
		global $wpdb;
		$tables = array(
			'inquiries', 'attachments', 'reviews', 'fit_assessments', 'fit_assessment_items',
			'fit_assessment_reviews', 'portal_access', 'portal_sessions', 'portal_events', 'portal_recovery_requests', 'meeting_offers', 'graph_operations', 'proposals', 'proposal_versions', 'workflow_events', 'engagements', 'engagement_snapshots', 'engagement_requirements', 'engagement_events', 'workflow_cases', 'workflow_commands', 'workflow_handoffs', 'workflow_outbox',
			'communications', 'communication_events',
			'communication_templates', 'privacy_requests', 'consent_events', 'legal_holds',
			'retention_policies', 'retention_actions', 'audit_log',
		);
		$result = array();
		foreach ( $tables as $name ) {
			$table = SC_EI_Database::table( $name );
			$result[ $name ] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
		$result['active_attachment_bytes'] = (int) $wpdb->get_var(
			"SELECT COALESCE(SUM(size_bytes),0) FROM " . SC_EI_Database::table( 'attachments' ) . " WHERE deleted_at IS NULL" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);
		return $result;
	}

	private static function block_queued_actions_for_hold( int $inquiry_id, int $attachment_id = 0 ): void {
		global $wpdb;
		$table = SC_EI_Database::table( 'retention_actions' );
		$where = $attachment_id
			? $wpdb->prepare( "inquiry_id = %d AND (target_type <> 'attachment' OR target_id = %d)", $inquiry_id, $attachment_id )
			: $wpdb->prepare( 'inquiry_id = %d', $inquiry_id );
		$wpdb->query(
			"UPDATE {$table}
			SET status = 'blocked_hold',
				failure_code = 'legal_hold_active',
				failure_message = 'Active legal hold blocks execution.',
				updated_at = UTC_TIMESTAMP()
			WHERE {$where}
				AND status IN ('queued','approved','failed','blocked_dependency')" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	private static function sanitize_datetime( $value ): ?string {
		$value = sanitize_text_field( (string) $value );
		if ( '' === $value ) {
			return null;
		}
		try {
			$date = new DateTimeImmutable( $value, wp_timezone() );
			return $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
		} catch ( Throwable $exception ) {
			$date = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $value, new DateTimeZone( 'UTC' ) );
			return $date ? $date->format( 'Y-m-d H:i:s' ) : null;
		}
	}

	private static function formats( array $data, array $integer_fields = array() ): array {
		return array_map(
			static fn( string $key ): string => in_array( $key, $integer_fields, true ) ? '%d' : '%s',
			array_keys( $data )
		);
	}
}
