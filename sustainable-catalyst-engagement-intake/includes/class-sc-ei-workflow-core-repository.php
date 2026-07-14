<?php
/**
 * Durable canonical Workflow Core projections, commands, handoffs, and outbox.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Workflow_Core_Repository {

	public const SYNC_HOOK = 'sc_ei_workflow_core_sync';
	public const OUTBOX_HOOK = 'sc_ei_workflow_core_outbox';

	private static bool $syncing = false;

	public static function register(): void {
		add_filter( 'cron_schedules', array( __CLASS__, 'cron_schedules' ) );
		add_action( self::SYNC_HOOK, array( __CLASS__, 'scheduled_sync' ) );
		add_action( self::OUTBOX_HOOK, array( __CLASS__, 'scheduled_outbox' ) );
	}

	public static function cron_schedules( array $schedules ): array {
		$settings = self::settings();
		$sync_minutes = max( 15, min( 1440, absint( $settings['workflow_core_sync_interval_minutes'] ?? 60 ) ) );
		$outbox_minutes = max( 5, min( 60, min( 15, $sync_minutes ) ) );
		$schedules['sc_ei_workflow_core_sync_interval'] = array(
			'interval' => $sync_minutes * MINUTE_IN_SECONDS,
			'display'  => sprintf( __( 'Every %d minutes (Engagement Intake Workflow Core)', 'sustainable-catalyst-engagement-intake' ), $sync_minutes ),
		);
		$schedules['sc_ei_workflow_core_outbox_interval'] = array(
			'interval' => $outbox_minutes * MINUTE_IN_SECONDS,
			'display'  => sprintf( __( 'Every %d minutes (Engagement Intake Workflow Outbox)', 'sustainable-catalyst-engagement-intake' ), $outbox_minutes ),
		);
		return $schedules;
	}

	public static function settings(): array {
		return wp_parse_args(
			get_option( 'sc_ei_settings', array() ),
			SC_EI_Workflow_Core_Schema::default_settings()
		);
	}

	public static function schedule(): void {
		$settings = self::settings();
		$interval = max( 15, min( 1440, absint( $settings['workflow_core_sync_interval_minutes'] ?? 60 ) ) );
		if ( ! wp_next_scheduled( self::SYNC_HOOK ) ) {
			wp_schedule_event( time() + 5 * MINUTE_IN_SECONDS, 'sc_ei_workflow_core_sync_interval', self::SYNC_HOOK );
		}
		if ( ! wp_next_scheduled( self::OUTBOX_HOOK ) ) {
			wp_schedule_event( time() + min( 15, $interval ) * MINUTE_IN_SECONDS, 'sc_ei_workflow_core_outbox_interval', self::OUTBOX_HOOK );
		}
	}

	public static function unschedule(): void {
		wp_clear_scheduled_hook( self::SYNC_HOOK );
		wp_clear_scheduled_hook( self::OUTBOX_HOOK );
	}

	public static function scheduled_sync(): void {
		if ( empty( self::settings()['workflow_core_enabled'] ) ) {
			return;
		}
		self::sync_batch( 250 );
		self::expire_handoffs();
	}

	public static function scheduled_outbox(): void {
		if ( empty( self::settings()['workflow_core_enabled'] ) || empty( self::settings()['workflow_core_outbox_enabled'] ) ) {
			return;
		}
		self::recover_stale_outbox_claims();
		self::process_outbox( absint( self::settings()['workflow_core_outbox_batch_limit'] ?? 25 ) );
	}

	public static function sync_inquiry( int $inquiry_id, int $actor_user_id = 0, string $reason = 'manual' ) {
		global $wpdb;

		if ( self::$syncing ) {
			return self::case_for_inquiry( $inquiry_id );
		}
		self::$syncing = true;
		try {
			$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
			if ( ! $inquiry ) {
				return new WP_Error( 'workflow_core_inquiry_not_found', __( 'The authoritative inquiry could not be found.', 'sustainable-catalyst-engagement-intake' ) );
			}

			$existing = self::case_for_inquiry( $inquiry_id );
			$projection = self::derive_projection( $inquiry, $existing );
			$now = current_time( 'mysql', true );
			$projection['last_synced_at'] = $now;
			$projection['stale_after'] = gmdate(
				'Y-m-d H:i:s',
				time() + max( 1, absint( self::settings()['workflow_core_stale_after_hours'] ?? 24 ) ) * HOUR_IN_SECONDS
			);
			$projection['updated_at'] = $now;

			$changed = ! $existing
				|| ! hash_equals( (string) $existing['projection_hash'], (string) $projection['projection_hash'] )
				|| (string) $existing['consistency_status'] !== (string) $projection['consistency_status'];

			if ( $existing ) {
				$projection['projection_version'] = $changed
					? absint( $existing['projection_version'] ) + 1
					: absint( $existing['projection_version'] );
				$projection['row_version'] = absint( $existing['row_version'] ) + 1;
				$projection['last_transition_at'] = (
					(string) $existing['current_stage'] !== (string) $projection['current_stage']
					|| (string) $existing['current_state'] !== (string) $projection['current_state']
				) ? $now : $existing['last_transition_at'];
				$updated = $wpdb->update(
					SC_EI_Database::table( 'workflow_cases' ),
					$projection,
					array(
						'id'          => absint( $existing['id'] ),
						'row_version' => absint( $existing['row_version'] ),
					),
					self::formats( $projection, self::case_integer_fields() ),
					array( '%d', '%d' )
				);
				if ( 1 !== $updated ) {
					return new WP_Error( 'workflow_core_sync_conflict', __( 'The Workflow Core case changed before synchronization completed.', 'sustainable-catalyst-engagement-intake' ) );
				}
				$case_id = absint( $existing['id'] );
			} else {
				$projection['public_id'] = wp_generate_uuid4();
				$projection['inquiry_id'] = $inquiry_id;
				$projection['projection_version'] = 1;
				$projection['row_version'] = 0;
				$projection['created_at'] = $now;
				$projection['last_transition_at'] = $now;
				$inserted = $wpdb->insert(
					SC_EI_Database::table( 'workflow_cases' ),
					$projection,
					self::formats( $projection, self::case_integer_fields() )
				);
				if ( false === $inserted ) {
					return new WP_Error( 'workflow_core_case_insert_failed', __( 'The canonical Workflow Core case could not be created.', 'sustainable-catalyst-engagement-intake' ) );
				}
				$case_id = (int) $wpdb->insert_id;
			}

			$case = self::find_case( $case_id );
			$registered_targets = SC_EI_Workflow_Core_Service::registered_targets();
			if ( $changed && $case && ! empty( $registered_targets['platform_core']['registered'] ) ) {
				self::enqueue_event(
					$case,
					'workflow_case_synchronized',
					'case',
					$case_id,
					'platform_core',
					array(
						'reason'             => sanitize_key( $reason ),
						'stage'              => $case['current_stage'],
						'state'              => $case['current_state'],
						'projection_version' => absint( $case['projection_version'] ),
						'projection_hash'    => $case['projection_hash'],
						'consistency_status' => $case['consistency_status'],
					)
				);
			}

			if ( $case ) {
				SC_EI_Audit_Log::record(
					'workflow_core_case_synchronized',
					'Canonical Workflow Core projection synchronized from authoritative domain records.',
					array(
						'case_id'            => $case_id,
						'stage'              => $case['current_stage'],
						'state'              => $case['current_state'],
						'projection_version' => absint( $case['projection_version'] ),
						'projection_hash'    => $case['projection_hash'],
						'changed'            => $changed,
						'reason'             => sanitize_key( $reason ),
						'automatic_decision' => false,
					),
					$inquiry_id,
					null,
					$actor_user_id ?: null
				);
			}
			return $case;
		} finally {
			self::$syncing = false;
		}
	}

	public static function sync_batch( int $limit = 100 ): array {
		global $wpdb;

		$limit = max( 1, min( 1000, $limit ) );
		$inquiries = SC_EI_Database::table( 'inquiries' );
		$cases = SC_EI_Database::table( 'workflow_cases' );
		$ids = (array) $wpdb->get_col(
			$wpdb->prepare(
				"SELECT i.id
				FROM {$inquiries} i
				LEFT JOIN {$cases} c ON c.inquiry_id = i.id
				WHERE c.id IS NULL
					OR c.last_synced_at < i.updated_at
					OR c.stale_after IS NULL
					OR c.stale_after < UTC_TIMESTAMP()
				ORDER BY COALESCE(c.last_synced_at, '1970-01-01 00:00:00') ASC, i.id ASC
				LIMIT %d",
				$limit
			)
		);
		$result = array( 'requested' => count( $ids ), 'succeeded' => 0, 'failed' => 0, 'errors' => array() );
		foreach ( $ids as $id ) {
			$synced = self::sync_inquiry( absint( $id ), 0, 'scheduled' );
			if ( is_wp_error( $synced ) ) {
				$result['failed']++;
				$result['errors'][] = $synced->get_error_code();
			} else {
				$result['succeeded']++;
			}
		}
		update_option( 'sc_ei_workflow_core_last_sync', array_merge( $result, array( 'completed_at' => current_time( 'mysql', true ) ) ), false );
		return $result;
	}

	public static function find_case( int $id ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'workflow_cases' );
		$inquiries = SC_EI_Database::table( 'inquiries' );
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT c.*, i.contact_name, i.organization, i.inquiry_type, i.status AS inquiry_status,
					i.privacy_status, u.display_name AS owner_name
				FROM {$table} c
				LEFT JOIN {$inquiries} i ON i.id = c.inquiry_id
				LEFT JOIN {$wpdb->users} u ON u.ID = c.owner_user_id
				WHERE c.id = %d",
				$id
			),
			ARRAY_A
		);
		return $row ?: null;
	}

	public static function case_for_inquiry( int $inquiry_id ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'workflow_cases' );
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE inquiry_id = %d LIMIT 1", $inquiry_id ) );
		return $id ? self::find_case( absint( $id ) ) : null;
	}

	public static function query_cases( array $args = array() ): array {
		global $wpdb;
		$args = wp_parse_args(
			$args,
			array(
				'stage'       => '',
				'state'       => '',
				'consistency' => '',
				'owner'       => 0,
				'search'      => '',
				'limit'       => 500,
			)
		);
		$table = SC_EI_Database::table( 'workflow_cases' );
		$inquiries = SC_EI_Database::table( 'inquiries' );
		$where = array( '1=1' );
		$params = array();

		$stage = SC_EI_Workflow_Core_Schema::sanitize_stage( (string) $args['stage'] );
		if ( '' !== (string) $args['stage'] && isset( SC_EI_Workflow_Core_Schema::stages()[ $stage ] ) ) {
			$where[] = 'c.current_stage = %s';
			$params[] = $stage;
		}
		$state = sanitize_key( (string) $args['state'] );
		if ( $state && isset( SC_EI_Workflow_Core_Schema::states()[ $state ] ) ) {
			$where[] = 'c.current_state = %s';
			$params[] = $state;
		}
		$consistency = sanitize_key( (string) $args['consistency'] );
		if ( $consistency && isset( SC_EI_Workflow_Core_Schema::consistency_states()[ $consistency ] ) ) {
			$where[] = 'c.consistency_status = %s';
			$params[] = $consistency;
		}
		if ( absint( $args['owner'] ) ) {
			$where[] = 'c.owner_user_id = %d';
			$params[] = absint( $args['owner'] );
		}
		$search = sanitize_text_field( (string) $args['search'] );
		if ( '' !== $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$where[] = '(c.reference LIKE %s OR i.contact_name LIKE %s OR i.organization LIKE %s)';
			array_push( $params, $like, $like, $like );
		}

		$sql = "SELECT c.id
			FROM {$table} c
			LEFT JOIN {$inquiries} i ON i.id = c.inquiry_id
			WHERE " . implode( ' AND ', $where ) . '
			ORDER BY
				CASE c.consistency_status WHEN "blocked" THEN 0 WHEN "warning" THEN 1 WHEN "stale" THEN 2 ELSE 3 END,
				c.updated_at DESC
			LIMIT %d';
		$params[] = max( 1, min( 2000, absint( $args['limit'] ) ) );
		$ids = (array) $wpdb->get_col( $wpdb->prepare( $sql, $params ) );
		return array_values( array_filter( array_map( array( __CLASS__, 'find_case' ), array_map( 'absint', $ids ) ) ) );
	}

	public static function metrics(): array {
		global $wpdb;
		$cases = SC_EI_Database::table( 'workflow_cases' );
		$commands = SC_EI_Database::table( 'workflow_commands' );
		$handoffs = SC_EI_Database::table( 'workflow_handoffs' );
		$outbox = SC_EI_Database::table( 'workflow_outbox' );
		return array(
			'cases'             => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$cases}" ),
			'blocked_cases'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$cases} WHERE consistency_status = 'blocked'" ),
			'warning_cases'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$cases} WHERE consistency_status = 'warning'" ),
			'stale_cases'       => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$cases} WHERE stale_after < UTC_TIMESTAMP()" ),
			'pending_commands'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$commands} WHERE status IN ('pending','processing')" ),
			'prepared_handoffs' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$handoffs} WHERE status = 'prepared'" ),
			'acknowledged_handoffs' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$handoffs} WHERE status = 'acknowledged'" ),
			'pending_outbox'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$outbox} WHERE status IN ('pending','retry_wait','processing')" ),
			'failed_outbox'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$outbox} WHERE status = 'failed'" ),
		);
	}

	public static function submit_command(
		int $case_id,
		string $command_type,
		array $payload,
		string $reason,
		int $actor_user_id
	) {
		global $wpdb;

		$case = self::find_case( $case_id );
		if ( ! $case ) {
			return new WP_Error( 'workflow_core_case_not_found', __( 'The Workflow Core case was not found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$command_type = SC_EI_Workflow_Core_Schema::sanitize_command_type( $command_type );
		if ( '' === $command_type ) {
			return new WP_Error( 'workflow_core_command_invalid', __( 'Choose a supported Workflow Core command.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$payload = self::sanitize_payload( $payload );
		$payload_json = wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$payload_hash = hash( 'sha256', (string) $payload_json );
		$command_key = hash(
			'sha256',
			$command_type . '|' . $case_id . '|' . $case['projection_hash'] . '|' . $payload_hash
		);
		$existing = self::command_by_key( $command_key );
		if ( $existing ) {
			return $existing;
		}
		$now = current_time( 'mysql', true );
		$data = array(
			'public_id'      => wp_generate_uuid4(),
			'command_key'    => $command_key,
			'inquiry_id'     => absint( $case['inquiry_id'] ),
			'case_id'        => $case_id,
			'command_type'   => $command_type,
			'target_type'    => sanitize_key( (string) ( $payload['target_type'] ?? 'case' ) ),
			'target_id'      => absint( $payload['target_id'] ?? $case_id ),
			'expected_stage' => (string) $case['current_stage'],
			'requested_by'   => $actor_user_id,
			'reason'         => sanitize_textarea_field( $reason ),
			'payload_json'   => $payload_json,
			'payload_hash'   => $payload_hash,
			'status'         => 'pending',
			'claimed_at'     => null,
			'claimed_by'     => null,
			'completed_at'   => null,
			'result_json'    => '',
			'error_code'     => '',
			'error_message'  => '',
			'row_version'    => 0,
			'created_at'     => $now,
			'updated_at'     => $now,
		);
		$inserted = $wpdb->insert(
			SC_EI_Database::table( 'workflow_commands' ),
			$data,
			self::formats( $data, self::command_integer_fields() )
		);
		if ( false === $inserted ) {
			$existing = self::command_by_key( $command_key );
			return $existing ?: new WP_Error( 'workflow_core_command_save_failed', __( 'The Workflow Core command could not be recorded.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$id = (int) $wpdb->insert_id;
		self::refresh_counts( $case_id );
		return self::execute_command( $id, $actor_user_id );
	}

	public static function execute_command( int $command_id, int $actor_user_id ) {
		global $wpdb;

		$command = self::find_command( $command_id );
		if ( ! $command ) {
			return new WP_Error( 'workflow_core_command_not_found', __( 'The Workflow Core command was not found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( 'succeeded' === $command['status'] ) {
			return $command;
		}
		if ( ! in_array( $command['status'], array( 'pending', 'failed' ), true ) ) {
			return new WP_Error( 'workflow_core_command_busy', __( 'The Workflow Core command is already being processed.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$case = self::find_case( absint( $command['case_id'] ) );
		if ( ! $case ) {
			return self::fail_command( $command, 'workflow_core_case_not_found', __( 'The Workflow Core case no longer exists.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if (
			! in_array( $command['command_type'], array( 'sync_case', 'rebuild_case', 'resolve_consistency' ), true )
			&& '' !== $command['expected_stage']
			&& $command['expected_stage'] !== $case['current_stage']
		) {
			return self::fail_command( $command, 'workflow_core_stage_changed', __( 'The case stage changed before the command was executed.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$now = current_time( 'mysql', true );
		$claimed = $wpdb->update(
			SC_EI_Database::table( 'workflow_commands' ),
			array(
				'status'      => 'processing',
				'claimed_at'  => $now,
				'claimed_by'  => $actor_user_id,
				'row_version' => absint( $command['row_version'] ) + 1,
				'updated_at'  => $now,
			),
			array(
				'id'          => $command_id,
				'row_version' => absint( $command['row_version'] ),
				'status'      => $command['status'],
			),
			array( '%s', '%s', '%d', '%d', '%s' ),
			array( '%d', '%d', '%s' )
		);
		if ( 1 !== $claimed ) {
			return new WP_Error( 'workflow_core_command_claim_conflict', __( 'The Workflow Core command changed before it could be claimed.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$command = self::find_command( $command_id );
		$payload = json_decode( (string) $command['payload_json'], true ) ?: array();
		$result = null;

		switch ( $command['command_type'] ) {
			case 'sync_case':
			case 'rebuild_case':
				$result = self::sync_inquiry( absint( $command['inquiry_id'] ), $actor_user_id, $command['command_type'] );
				break;
			case 'prepare_handoff':
				$result = self::prepare_handoff(
					absint( $command['case_id'] ),
					(string) ( $payload['target'] ?? '' ),
					(string) ( $payload['classification'] ?? 'operational_minimum' ),
					! empty( $payload['include_personal_data'] ),
					$actor_user_id
				);
				break;
			case 'dispatch_outbox':
				$result = self::process_outbox( absint( $payload['limit'] ?? 25 ), true );
				break;
			case 'acknowledge_handoff':
				$result = self::acknowledge_handoff(
					absint( $payload['handoff_id'] ?? 0 ),
					sanitize_text_field( (string) ( $payload['receipt'] ?? '' ) ),
					$actor_user_id
				);
				break;
			case 'cancel_handoff':
				$result = self::cancel_handoff(
					absint( $payload['handoff_id'] ?? 0 ),
					sanitize_textarea_field( (string) ( $payload['reason'] ?? $command['reason'] ) ),
					$actor_user_id
				);
				break;
			case 'resolve_consistency':
				$result = self::resolve_consistency(
					absint( $command['case_id'] ),
					sanitize_textarea_field( (string) ( $payload['note'] ?? $command['reason'] ) ),
					$actor_user_id
				);
				break;
			default:
				$result = new WP_Error( 'workflow_core_command_not_implemented', __( 'The Workflow Core command is not implemented.', 'sustainable-catalyst-engagement-intake' ) );
		}

		if ( is_wp_error( $result ) ) {
			return self::fail_command( $command, $result->get_error_code(), $result->get_error_message() );
		}
		$result_json = wp_json_encode( self::sanitize_payload( is_array( $result ) ? $result : array( 'ok' => true ) ) );
		$completed = current_time( 'mysql', true );
		$wpdb->update(
			SC_EI_Database::table( 'workflow_commands' ),
			array(
				'status'        => 'succeeded',
				'completed_at'  => $completed,
				'result_json'   => $result_json,
				'error_code'    => '',
				'error_message' => '',
				'row_version'   => absint( $command['row_version'] ) + 1,
				'updated_at'    => $completed,
			),
			array( 'id' => $command_id, 'status' => 'processing' ),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%s' ),
			array( '%d', '%s' )
		);
		self::refresh_counts( absint( $command['case_id'] ) );
		SC_EI_Audit_Log::record(
			'workflow_core_command_succeeded',
			'Authorized Workflow Core command completed.',
			array(
				'command_id'       => $command_id,
				'command_type'     => $command['command_type'],
				'command_key'      => $command['command_key'],
				'automatic_decision'=> false,
			),
			absint( $command['inquiry_id'] ),
			null,
			$actor_user_id
		);
		return self::find_command( $command_id );
	}

	public static function prepare_handoff(
		int $case_id,
		string $target,
		string $classification,
		bool $include_personal_data,
		int $actor_user_id
	) {
		global $wpdb;

		$case = self::find_case( $case_id );
		if ( ! $case ) {
			return new WP_Error( 'workflow_core_case_not_found', __( 'The Workflow Core case was not found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( 'blocked' === $case['consistency_status'] ) {
			return new WP_Error( 'workflow_core_case_blocked', __( 'Resolve the canonical consistency blockers before preparing a handoff.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( $include_personal_data && ! current_user_can( 'sc_intake_export_workflow_core_private' ) ) {
			return new WP_Error( 'workflow_core_private_export_forbidden', __( 'You do not have permission to include private personal data in a handoff.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$contract = SC_EI_Workflow_Core_Contract::build( $case, $target, $classification, $include_personal_data );
		if ( is_wp_error( $contract ) ) {
			return $contract;
		}
		$handoff_key = hash(
			'sha256',
			$case_id . '|' . $target . '|' . $case['projection_hash'] . '|' . $contract['content_hash']
		);
		$existing = self::handoff_by_key( $handoff_key );
		if ( $existing ) {
			return $existing;
		}
		$now = current_time( 'mysql', true );
		$expires_at = gmdate(
			'Y-m-d H:i:s',
			time() + max( 1, absint( self::settings()['workflow_core_handoff_expiry_days'] ?? 30 ) ) * DAY_IN_SECONDS
		);
		$data = array(
			'public_id'           => wp_generate_uuid4(),
			'handoff_key'         => $handoff_key,
			'inquiry_id'          => absint( $case['inquiry_id'] ),
			'case_id'             => $case_id,
			'target'              => SC_EI_Workflow_Core_Schema::sanitize_target( $target ),
			'schema_id'           => $contract['schema_id'],
			'contract_version'    => $contract['contract_version'],
			'data_classification' => SC_EI_Workflow_Core_Schema::sanitize_classification( $classification ),
			'status'              => 'prepared',
			'payload_json'        => $contract['payload_json'],
			'content_hash'        => $contract['content_hash'],
			'signature'           => $contract['signature'],
			'prepared_by'         => $actor_user_id,
			'prepared_at'         => $now,
			'dispatched_at'       => null,
			'acknowledged_by'     => null,
			'acknowledged_at'     => null,
			'failed_at'           => null,
			'failure_code'        => '',
			'failure_message'     => '',
			'expires_at'          => $expires_at,
			'row_version'         => 0,
			'created_at'          => $now,
			'updated_at'          => $now,
		);
		$inserted = $wpdb->insert(
			SC_EI_Database::table( 'workflow_handoffs' ),
			$data,
			self::formats( $data, self::handoff_integer_fields() )
		);
		if ( false === $inserted ) {
			$existing = self::handoff_by_key( $handoff_key );
			return $existing ?: new WP_Error( 'workflow_core_handoff_save_failed', __( 'The signed Workflow Core handoff could not be saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$handoff_id = (int) $wpdb->insert_id;
		$handoff = self::find_handoff( $handoff_id );
		self::enqueue_event(
			$case,
			'workflow_handoff_prepared',
			'handoff',
			$handoff_id,
			$target,
			array(
				'handoff_id'       => $handoff_id,
				'handoff_public_id'=> $handoff['public_id'],
				'content_hash'     => $handoff['content_hash'],
				'schema_id'        => $handoff['schema_id'],
				'contract_version' => $handoff['contract_version'],
				'classification'   => $handoff['data_classification'],
			)
		);
		self::refresh_counts( $case_id );
		SC_EI_Audit_Log::record(
			'workflow_core_handoff_prepared',
			'Authorized staff prepared a signed, versioned cross-plugin Workflow Core handoff.',
			array(
				'handoff_id'        => $handoff_id,
				'target'            => $target,
				'classification'    => $classification,
				'content_hash'      => $handoff['content_hash'],
				'personal_data'     => $include_personal_data,
				'automatic_delivery'=> false,
			),
			absint( $case['inquiry_id'] ),
			null,
			$actor_user_id
		);
		return $handoff;
	}

	public static function enqueue_event(
		array $case,
		string $event_type,
		string $aggregate_type,
		int $aggregate_id,
		string $target,
		array $payload
	) {
		global $wpdb;

		$payload = self::sanitize_payload( $payload );
		$payload['case_public_id'] = (string) $case['public_id'];
		$payload['inquiry_id'] = absint( $case['inquiry_id'] );
		$payload['stage'] = (string) $case['current_stage'];
		$payload['state'] = (string) $case['current_state'];
		$payload['projection_hash'] = (string) $case['projection_hash'];
		$payload['source_version'] = SC_EI_VERSION;
		$payload_json = wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$payload_hash = hash( 'sha256', (string) $payload_json );
		$event_key = hash(
			'sha256',
			sanitize_key( $event_type ) . '|' . sanitize_key( $aggregate_type ) . '|' . $aggregate_id . '|' . sanitize_key( $target ) . '|' . $payload_hash
		);
		$existing = self::outbox_by_key( $event_key );
		if ( $existing ) {
			return $existing;
		}
		$now = current_time( 'mysql', true );
		$data = array(
			'public_id'      => wp_generate_uuid4(),
			'event_key'      => $event_key,
			'inquiry_id'     => absint( $case['inquiry_id'] ),
			'case_id'        => absint( $case['id'] ),
			'event_type'     => sanitize_key( $event_type ),
			'aggregate_type' => sanitize_key( $aggregate_type ),
			'aggregate_id'   => $aggregate_id,
			'target'         => SC_EI_Workflow_Core_Schema::sanitize_target( $target ) ?: 'generic_internal',
			'payload_json'   => $payload_json,
			'payload_hash'   => $payload_hash,
			'status'         => 'pending',
			'available_at'   => $now,
			'claimed_at'     => null,
			'claim_token'    => '',
			'attempts'       => 0,
			'max_attempts'   => max( 1, absint( self::settings()['workflow_core_outbox_max_attempts'] ?? 6 ) ),
			'dispatched_at'  => null,
			'acknowledged_at'=> null,
			'error_code'     => '',
			'error_message'  => '',
			'row_version'    => 0,
			'created_at'     => $now,
			'updated_at'     => $now,
		);
		$inserted = $wpdb->insert(
			SC_EI_Database::table( 'workflow_outbox' ),
			$data,
			self::formats( $data, self::outbox_integer_fields() )
		);
		return false === $inserted
			? new WP_Error( 'workflow_core_outbox_save_failed', __( 'The Workflow Core outbox event could not be saved.', 'sustainable-catalyst-engagement-intake' ) )
			: self::find_outbox( (int) $wpdb->insert_id );
	}

	public static function process_outbox( int $limit = 25, bool $manual = false ): array {
		global $wpdb;

		$limit = max( 1, min( 250, $limit ) );
		$table = SC_EI_Database::table( 'workflow_outbox' );
		$ids = (array) $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$table}
				WHERE status IN ('pending','retry_wait')
					AND available_at <= UTC_TIMESTAMP()
				ORDER BY available_at ASC, id ASC
				LIMIT %d",
				$limit
			)
		);
		$result = array( 'requested' => count( $ids ), 'dispatched' => 0, 'acknowledged' => 0, 'failed' => 0, 'retrying' => 0 );
		foreach ( $ids as $id ) {
			$dispatched = self::dispatch_outbox( absint( $id ), $manual );
			if ( is_wp_error( $dispatched ) ) {
				if ( 'workflow_core_outbox_retry' === $dispatched->get_error_code() ) {
					$result['retrying']++;
				} else {
					$result['failed']++;
				}
			} elseif ( 'acknowledged' === ( $dispatched['status'] ?? '' ) ) {
				$result['acknowledged']++;
			} else {
				$result['dispatched']++;
			}
		}
		update_option( 'sc_ei_workflow_core_last_outbox', array_merge( $result, array( 'completed_at' => current_time( 'mysql', true ) ) ), false );
		return $result;
	}

	public static function dispatch_outbox( int $outbox_id, bool $manual = false ) {
		global $wpdb;

		$event = self::find_outbox( $outbox_id );
		if ( ! $event || ! in_array( $event['status'], array( 'pending', 'retry_wait' ), true ) ) {
			return new WP_Error( 'workflow_core_outbox_not_dispatchable', __( 'The Workflow Core outbox event cannot be dispatched.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$token = wp_generate_uuid4();
		$now = current_time( 'mysql', true );
		$claimed = $wpdb->update(
			SC_EI_Database::table( 'workflow_outbox' ),
			array(
				'status'      => 'processing',
				'claimed_at'  => $now,
				'claim_token' => $token,
				'attempts'    => absint( $event['attempts'] ) + 1,
				'row_version' => absint( $event['row_version'] ) + 1,
				'updated_at'  => $now,
			),
			array(
				'id'          => $outbox_id,
				'row_version' => absint( $event['row_version'] ),
				'status'      => $event['status'],
			),
			array( '%s', '%s', '%s', '%d', '%d', '%s' ),
			array( '%d', '%d', '%s' )
		);
		if ( 1 !== $claimed ) {
			return new WP_Error( 'workflow_core_outbox_claim_conflict', __( 'The Workflow Core outbox event changed before it could be claimed.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$event = self::find_outbox( $outbox_id );
		$payload_json = (string) $event['payload_json'];
		if ( ! hash_equals( (string) $event['payload_hash'], hash( 'sha256', $payload_json ) ) ) {
			return self::fail_outbox( $event, $token, 'workflow_core_payload_integrity_failed', __( 'The outbox payload failed its integrity check.', 'sustainable-catalyst-engagement-intake' ), false );
		}
		$payload = json_decode( $payload_json, true );
		if ( ! is_array( $payload ) ) {
			return self::fail_outbox( $event, $token, 'workflow_core_payload_invalid', __( 'The outbox payload is invalid.', 'sustainable-catalyst-engagement-intake' ), false );
		}

		$adapter_result = apply_filters(
			'sc_ei_workflow_core_dispatch',
			null,
			$event,
			$payload
		);
		if ( is_wp_error( $adapter_result ) ) {
			return self::fail_outbox(
				$event,
				$token,
				$adapter_result->get_error_code(),
				$adapter_result->get_error_message(),
				true
			);
		}
		do_action( 'sc_ei_workflow_core_event_dispatched', $event, $payload, $adapter_result );
		$acknowledged = is_array( $adapter_result ) && ! empty( $adapter_result['acknowledged'] );
		$status = $acknowledged ? 'acknowledged' : 'dispatched';
		$completed = current_time( 'mysql', true );
		$updated = $wpdb->update(
			SC_EI_Database::table( 'workflow_outbox' ),
			array(
				'status'          => $status,
				'dispatched_at'   => $completed,
				'acknowledged_at' => $acknowledged ? $completed : null,
				'claim_token'     => '',
				'error_code'      => '',
				'error_message'   => '',
				'row_version'     => absint( $event['row_version'] ) + 1,
				'updated_at'      => $completed,
			),
			array( 'id' => $outbox_id, 'claim_token' => $token, 'status' => 'processing' ),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' ),
			array( '%d', '%s', '%s' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'workflow_core_outbox_completion_conflict', __( 'The Workflow Core outbox event changed before dispatch completion.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( 'handoff' === $event['aggregate_type'] ) {
			self::update_handoff_dispatch( absint( $event['aggregate_id'] ), $acknowledged );
		}
		self::refresh_counts( absint( $event['case_id'] ) );
		SC_EI_Audit_Log::record(
			'workflow_core_outbox_dispatched',
			'Workflow Core dispatched an event to registered internal adapters.',
			array(
				'outbox_id'       => $outbox_id,
				'event_type'      => $event['event_type'],
				'target'          => $event['target'],
				'acknowledged'    => $acknowledged,
				'manual'          => $manual,
				'external_http'   => false,
				'automatic_decision'=> false,
			),
			absint( $event['inquiry_id'] )
		);
		return self::find_outbox( $outbox_id );
	}

	public static function acknowledge_handoff( int $handoff_id, string $receipt, int $actor_user_id ) {
		global $wpdb;
		$handoff = self::find_handoff( $handoff_id );
		if ( ! $handoff || ! in_array( $handoff['status'], array( 'prepared', 'dispatched' ), true ) ) {
			return new WP_Error( 'workflow_core_handoff_not_acknowledgeable', __( 'The handoff cannot be acknowledged.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( ! SC_EI_Workflow_Core_Contract::verify(
			(string) $handoff['payload_json'],
			(string) $handoff['target'],
			(string) $handoff['content_hash'],
			(string) $handoff['signature']
		) ) {
			return new WP_Error( 'workflow_core_handoff_integrity_failed', __( 'The handoff failed signature verification.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		$updated = $wpdb->update(
			SC_EI_Database::table( 'workflow_handoffs' ),
			array(
				'status'          => 'acknowledged',
				'acknowledged_by' => $actor_user_id,
				'acknowledged_at' => $now,
				'failure_code'    => '',
				'failure_message' => '',
				'row_version'     => absint( $handoff['row_version'] ) + 1,
				'updated_at'      => $now,
			),
			array( 'id' => $handoff_id, 'row_version' => absint( $handoff['row_version'] ) ),
			array( '%s', '%d', '%s', '%s', '%s', '%d', '%s' ),
			array( '%d', '%d' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'workflow_core_handoff_ack_conflict', __( 'The handoff changed before acknowledgment was saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		SC_EI_Audit_Log::record(
			'workflow_core_handoff_acknowledged',
			'Authorized staff recorded a handoff acknowledgment.',
			array(
				'handoff_id'   => $handoff_id,
				'target'       => $handoff['target'],
				'receipt_hash' => hash( 'sha256', $receipt ),
			),
			absint( $handoff['inquiry_id'] ),
			null,
			$actor_user_id
		);
		self::refresh_counts( absint( $handoff['case_id'] ) );
		return self::find_handoff( $handoff_id );
	}

	public static function cancel_handoff( int $handoff_id, string $reason, int $actor_user_id ) {
		global $wpdb;
		$handoff = self::find_handoff( $handoff_id );
		if ( ! $handoff || in_array( $handoff['status'], array( 'acknowledged', 'canceled', 'expired' ), true ) ) {
			return new WP_Error( 'workflow_core_handoff_not_cancelable', __( 'The handoff cannot be canceled.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$reason = sanitize_textarea_field( $reason );
		if ( '' === trim( $reason ) ) {
			return new WP_Error( 'workflow_core_handoff_reason_required', __( 'Record why the handoff is being canceled.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		$updated = $wpdb->update(
			SC_EI_Database::table( 'workflow_handoffs' ),
			array(
				'status'          => 'canceled',
				'failed_at'       => $now,
				'failure_code'    => 'canceled_by_staff',
				'failure_message' => $reason,
				'row_version'     => absint( $handoff['row_version'] ) + 1,
				'updated_at'      => $now,
			),
			array( 'id' => $handoff_id, 'row_version' => absint( $handoff['row_version'] ) ),
			array( '%s', '%s', '%s', '%s', '%d', '%s' ),
			array( '%d', '%d' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'workflow_core_handoff_cancel_conflict', __( 'The handoff changed before cancellation was saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$wpdb->update(
			SC_EI_Database::table( 'workflow_outbox' ),
			array( 'status' => 'canceled', 'error_code' => 'handoff_canceled', 'updated_at' => $now ),
			array( 'aggregate_type' => 'handoff', 'aggregate_id' => $handoff_id, 'status' => 'pending' ),
			array( '%s', '%s', '%s' ),
			array( '%s', '%d', '%s' )
		);
		SC_EI_Audit_Log::record(
			'workflow_core_handoff_canceled',
			'Authorized staff canceled a prepared Workflow Core handoff.',
			array( 'handoff_id' => $handoff_id, 'target' => $handoff['target'], 'reason' => $reason ),
			absint( $handoff['inquiry_id'] ),
			null,
			$actor_user_id
		);
		self::refresh_counts( absint( $handoff['case_id'] ) );
		return self::find_handoff( $handoff_id );
	}

	public static function resolve_consistency( int $case_id, string $note, int $actor_user_id ) {
		global $wpdb;
		$case = self::find_case( $case_id );
		if ( ! $case ) {
			return new WP_Error( 'workflow_core_case_not_found', __( 'The Workflow Core case was not found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$note = sanitize_textarea_field( $note );
		if ( '' === trim( $note ) ) {
			return new WP_Error( 'workflow_core_resolution_note_required', __( 'Record how the consistency warning was reviewed.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		$updated = $wpdb->update(
			SC_EI_Database::table( 'workflow_cases' ),
			array(
				'consistency_status' => 'consistent',
				'consistency_notes'  => wp_json_encode( array( 'human_resolution_note' => $note, 'resolved_at' => $now, 'resolved_by' => $actor_user_id ) ),
				'row_version'        => absint( $case['row_version'] ) + 1,
				'updated_at'         => $now,
			),
			array( 'id' => $case_id, 'row_version' => absint( $case['row_version'] ) ),
			array( '%s', '%s', '%d', '%s' ),
			array( '%d', '%d' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'workflow_core_resolution_conflict', __( 'The case changed before the consistency resolution was saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		SC_EI_Audit_Log::record(
			'workflow_core_consistency_resolved',
			'Authorized staff reviewed and resolved a Workflow Core consistency warning.',
			array( 'case_id' => $case_id, 'note' => $note, 'automatic_domain_change' => false ),
			absint( $case['inquiry_id'] ),
			null,
			$actor_user_id
		);
		return self::find_case( $case_id );
	}

	public static function find_command( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'workflow_commands' ) . ' WHERE id = %d', $id ),
			ARRAY_A
		);
		return $row ?: null;
	}

	public static function commands( int $case_id, int $limit = 200 ): array {
		global $wpdb;
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT c.*, u.display_name AS requested_by_name
				FROM ' . SC_EI_Database::table( 'workflow_commands' ) . ' c
				LEFT JOIN ' . $wpdb->users . ' u ON u.ID = c.requested_by
				WHERE c.case_id = %d
				ORDER BY c.created_at DESC, c.id DESC LIMIT %d',
				$case_id,
				max( 1, min( 1000, $limit ) )
			),
			ARRAY_A
		);
	}

	public static function find_handoff( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT h.*, u.display_name AS prepared_by_name
				FROM ' . SC_EI_Database::table( 'workflow_handoffs' ) . ' h
				LEFT JOIN ' . $wpdb->users . ' u ON u.ID = h.prepared_by
				WHERE h.id = %d',
				$id
			),
			ARRAY_A
		);
		return $row ?: null;
	}

	public static function handoffs( int $case_id, int $limit = 200 ): array {
		global $wpdb;
		$ids = (array) $wpdb->get_col(
			$wpdb->prepare(
				'SELECT id FROM ' . SC_EI_Database::table( 'workflow_handoffs' ) . '
				WHERE case_id = %d ORDER BY created_at DESC, id DESC LIMIT %d',
				$case_id,
				max( 1, min( 1000, $limit ) )
			)
		);
		return array_values( array_filter( array_map( array( __CLASS__, 'find_handoff' ), array_map( 'absint', $ids ) ) ) );
	}

	public static function find_outbox( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'workflow_outbox' ) . ' WHERE id = %d', $id ),
			ARRAY_A
		);
		return $row ?: null;
	}

	public static function outbox( int $case_id, int $limit = 200 ): array {
		global $wpdb;
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . SC_EI_Database::table( 'workflow_outbox' ) . '
				WHERE case_id = %d ORDER BY created_at DESC, id DESC LIMIT %d',
				$case_id,
				max( 1, min( 1000, $limit ) )
			),
			ARRAY_A
		);
	}

	public static function export_for_inquiry( int $inquiry_id ): array {
		$case = self::case_for_inquiry( $inquiry_id );
		if ( ! $case ) {
			$case = self::sync_inquiry( $inquiry_id, 0, 'export' );
		}
		if ( is_wp_error( $case ) || ! $case ) {
			return array();
		}
		$handoffs = array_map(
			static fn( array $handoff ): array => SC_EI_Workflow_Core_Contract::public_metadata( $handoff ),
			self::handoffs( absint( $case['id'] ), 500 )
		);
		$commands = self::commands( absint( $case['id'] ), 500 );
		foreach ( $commands as &$command ) {
			unset( $command['payload_json'], $command['result_json'], $command['reason'], $command['error_message'] );
		}
		unset( $command );
		return array(
			'schema'      => 'sc-engagement-workflow-core-export/1.0',
			'generated_at'=> current_time( 'mysql', true ),
			'case'        => $case,
			'commands'    => $commands,
			'handoffs'    => $handoffs,
			'outbox'      => array_map(
				static function ( array $event ): array {
					unset( $event['payload_json'], $event['error_message'], $event['claim_token'] );
					return $event;
				},
				self::outbox( absint( $case['id'] ), 500 )
			),
			'boundaries'  => array(
				'automatic_acceptance' => false,
				'automatic_fit_decision'=> false,
				'automatic_contract'   => false,
				'automatic_activation' => false,
				'external_http_delivery'=> false,
				'inbound_commands'     => false,
			),
		);
	}

	public static function redact_for_privacy( int $inquiry_id, string $now ): bool {
		global $wpdb;

		$case_result = $wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . SC_EI_Database::table( 'workflow_cases' ) . '
				SET consistency_notes = %s, updated_at = %s
				WHERE inquiry_id = %d',
				wp_json_encode( array( 'personal_data_erased' => true, 'schema' => SC_EI_WORKFLOW_CORE_SCHEMA_VERSION ) ),
				$now,
				$inquiry_id
			)
		);

		$command_rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, payload_json FROM ' . SC_EI_Database::table( 'workflow_commands' ) . ' WHERE inquiry_id = %d',
				$inquiry_id
			),
			ARRAY_A
		);
		$command_result = true;
		foreach ( $command_rows as $command ) {
			$tombstone = wp_json_encode(
				array(
					'personal_data_erased' => true,
					'original_payload_hash'=> hash( 'sha256', (string) $command['payload_json'] ),
				)
			);
			$updated = $wpdb->update(
				SC_EI_Database::table( 'workflow_commands' ),
				array(
					'reason'        => '',
					'payload_json'  => $tombstone,
					'payload_hash'  => hash( 'sha256', (string) $tombstone ),
					'result_json'   => '',
					'error_message' => '',
					'updated_at'    => $now,
				),
				array( 'id' => absint( $command['id'] ) ),
				array( '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
			$command_result = $command_result && false !== $updated;
		}

		$handoff_rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . SC_EI_Database::table( 'workflow_handoffs' ) . ' WHERE inquiry_id = %d',
				$inquiry_id
			),
			ARRAY_A
		);
		$handoff_result = true;
		foreach ( $handoff_rows as $handoff ) {
			$tombstone = array(
				'schema'                => SC_EI_Workflow_Core_Contract::SCHEMA_ID,
				'contract_version'      => SC_EI_Workflow_Core_Contract::CONTRACT_VERSION,
				'personal_data_erased'  => true,
				'original_content_hash' => (string) $handoff['content_hash'],
				'original_status'       => (string) $handoff['status'],
				'target'                => (string) $handoff['target'],
				'erased_at'             => $now,
			);
			$sealed = SC_EI_Workflow_Core_Contract::seal_payload( $tombstone, (string) $handoff['target'] );
			if ( is_wp_error( $sealed ) ) {
				$handoff_result = false;
				continue;
			}
			$updated = $wpdb->update(
				SC_EI_Database::table( 'workflow_handoffs' ),
				array(
					'status'          => 'canceled',
					'payload_json'    => $sealed['payload_json'],
					'content_hash'    => $sealed['content_hash'],
					'signature'       => $sealed['signature'],
					'failure_code'    => 'privacy_erased',
					'failure_message' => '',
					'row_version'     => absint( $handoff['row_version'] ) + 1,
					'updated_at'      => $now,
				),
				array( 'id' => absint( $handoff['id'] ), 'row_version' => absint( $handoff['row_version'] ) ),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' ),
				array( '%d', '%d' )
			);
			$handoff_result = $handoff_result && 1 === $updated;
		}

		$outbox_rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, payload_json, status FROM ' . SC_EI_Database::table( 'workflow_outbox' ) . ' WHERE inquiry_id = %d',
				$inquiry_id
			),
			ARRAY_A
		);
		$outbox_result = true;
		foreach ( $outbox_rows as $event ) {
			$tombstone = wp_json_encode(
				array(
					'personal_data_erased' => true,
					'original_payload_hash'=> hash( 'sha256', (string) $event['payload_json'] ),
				)
			);
			$updated = $wpdb->update(
				SC_EI_Database::table( 'workflow_outbox' ),
				array(
					'payload_json'  => $tombstone,
					'payload_hash'  => hash( 'sha256', (string) $tombstone ),
					'status'        => in_array( $event['status'], array( 'pending', 'retry_wait', 'processing' ), true ) ? 'canceled' : $event['status'],
					'claim_token'   => '',
					'error_code'    => 'privacy_erased',
					'error_message' => '',
					'updated_at'    => $now,
				),
				array( 'id' => absint( $event['id'] ) ),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
			$outbox_result = $outbox_result && false !== $updated;
		}

		return false !== $case_result && $command_result && $handoff_result && $outbox_result;
	}

	public static function expire_handoffs(): int {
		global $wpdb;
		$now = current_time( 'mysql', true );
		$result = $wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . SC_EI_Database::table( 'workflow_handoffs' ) . '
				SET status = "expired", updated_at = %s
				WHERE status IN ("prepared","dispatched") AND expires_at IS NOT NULL AND expires_at < %s',
				$now,
				$now
			)
		);
		return false === $result ? 0 : (int) $result;
	}

	public static function recover_stale_outbox_claims(): int {
		global $wpdb;
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - 15 * MINUTE_IN_SECONDS );
		$now = current_time( 'mysql', true );
		$result = $wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . SC_EI_Database::table( 'workflow_outbox' ) . '
				SET status = "retry_wait", claim_token = "", available_at = %s,
					error_code = "stale_claim_recovered", updated_at = %s
				WHERE status = "processing" AND claimed_at < %s',
				$now,
				$now,
				$cutoff
			)
		);
		return false === $result ? 0 : (int) $result;
	}

	private static function derive_projection( array $inquiry, ?array $existing ): array {
		global $wpdb;

		$inquiry_id = absint( $inquiry['id'] );
		$latest_meeting = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . SC_EI_Database::table( 'meeting_offers' ) . ' WHERE inquiry_id = %d ORDER BY created_at DESC, id DESC LIMIT 1',
				$inquiry_id
			),
			ARRAY_A
		) ?: array();
		$latest_proposal = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . SC_EI_Database::table( 'proposals' ) . ' WHERE inquiry_id = %d ORDER BY created_at DESC, id DESC LIMIT 1',
				$inquiry_id
			),
			ARRAY_A
		) ?: array();
		$latest_engagement = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . SC_EI_Database::table( 'engagements' ) . ' WHERE inquiry_id = %d ORDER BY created_at DESC, id DESC LIMIT 1',
				$inquiry_id
			),
			ARRAY_A
		) ?: array();
		$fit = SC_EI_Fit_Repository::current_for_inquiry( $inquiry_id ) ?: array();
		$last_event_at = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT MAX(created_at) FROM ' . SC_EI_Database::table( 'audit_log' ) . ' WHERE inquiry_id = %d',
				$inquiry_id
			)
		);
		$open_commands = $existing
			? (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM ' . SC_EI_Database::table( 'workflow_commands' ) . ' WHERE case_id = %d AND status IN ("pending","processing")',
					absint( $existing['id'] )
				)
			) : 0;
		$pending_handoffs = $existing
			? (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM ' . SC_EI_Database::table( 'workflow_handoffs' ) . ' WHERE case_id = %d AND status IN ("prepared","dispatched")',
					absint( $existing['id'] )
				)
			) : 0;

		$stage = 'intake';
		$state = 'new';
		$terminal = '';

		if ( $latest_engagement ) {
			switch ( $latest_engagement['status'] ) {
				case 'active':
					$stage = 'active_engagement'; $state = 'active'; break;
				case 'paused':
					$stage = 'active_engagement'; $state = 'paused'; break;
				case 'completed':
					$stage = 'completed'; $state = 'completed'; $terminal = 'completed'; break;
				case 'canceled':
					$stage = 'closed'; $state = 'canceled'; $terminal = 'canceled'; break;
				case 'ready_for_setup':
					$stage = 'engagement_handoff'; $state = 'ready_for_setup'; break;
				default:
					$stage = 'engagement_handoff'; $state = 'handoff_pending'; break;
			}
		} elseif ( $latest_proposal ) {
			switch ( $latest_proposal['status'] ) {
				case 'contracted':
					$stage = 'contracted'; $state = 'contract_recorded'; break;
				case 'accepted_pending_contract':
					$stage = 'proposal'; $state = 'proposal_accepted'; break;
				case 'published':
					$stage = 'proposal'; $state = 'proposal_published'; break;
				case 'draft':
					$stage = 'proposal'; $state = 'proposal_draft'; break;
			}
		} elseif ( $latest_meeting ) {
			switch ( $latest_meeting['status'] ) {
				case 'completed':
					$stage = 'consultation'; $state = 'meeting_completed'; break;
				case 'scheduled':
					$stage = 'consultation'; $state = 'meeting_scheduled'; break;
				case 'accepted_pending_link':
					$stage = 'consultation'; $state = 'meeting_accepted'; break;
				case 'published':
					$stage = 'consultation'; $state = 'meeting_offered'; break;
			}
		} elseif ( $fit ) {
			switch ( $fit['status'] ) {
				case 'finalized':
					$stage = 'fit'; $state = 'fit_finalized'; break;
				case 'submitted':
					$stage = 'fit'; $state = 'fit_submitted'; break;
				default:
					$stage = 'fit'; $state = 'fit_draft'; break;
			}
		} elseif ( 'more_information_needed' === $inquiry['status'] ) {
			$stage = 'review'; $state = 'more_information_needed';
		} elseif ( in_array( $inquiry['status'], array( 'fit_call_recommended', 'consultation_recommended' ), true ) ) {
			$stage = 'consultation'; $state = 'consultation_recommended';
		} elseif ( 'under_review' === $inquiry['status'] || ! empty( $inquiry['review_started_at'] ) ) {
			$stage = 'review'; $state = 'review_in_progress';
		} elseif ( 'new' !== $inquiry['status'] ) {
			$stage = 'review'; $state = 'review_pending';
		}

		if ( in_array( $inquiry['status'], array( 'not_a_fit', 'referred', 'withdrawn', 'closed' ), true ) && ! $latest_engagement ) {
			$stage = 'closed';
			$state = $inquiry['status'];
			$terminal = $inquiry['status'];
		}

		$blockers = array();
		$warnings = array();
		if ( in_array( $inquiry['privacy_status'], array( 'restricted', 'erasure_requested', 'erased' ), true ) ) {
			$blockers[] = 'privacy_processing_restricted';
		}
		if (
			! empty( $inquiry['review_due_at'] )
			&& empty( $inquiry['review_completed_at'] )
			&& strtotime( $inquiry['review_due_at'] . ' UTC' ) < time()
		) {
			$warnings[] = 'review_overdue';
		}
		if ( $latest_engagement && ! $latest_proposal ) {
			$blockers[] = 'engagement_without_proposal';
		}
		if ( $latest_engagement && 'contracted' !== (string) ( $latest_proposal['status'] ?? '' ) ) {
			$blockers[] = 'engagement_without_contracted_proposal';
		}
		if ( 'contracted' === (string) ( $latest_proposal['status'] ?? '' ) && empty( $latest_proposal['contract_reference'] ) ) {
			$blockers[] = 'contract_reference_missing';
		}
		if (
			'accepted_pending_link' === (string) ( $latest_meeting['status'] ?? '' )
			&& 'permanent_failure' === (string) ( $latest_meeting['graph_sync_status'] ?? '' )
		) {
			$blockers[] = 'teams_link_creation_failed';
		}
		if ( $latest_engagement && in_array( $latest_engagement['status'], array( 'handoff_pending', 'ready_for_setup' ), true ) ) {
			$required_blockers = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM ' . SC_EI_Database::table( 'engagement_requirements' ) . '
					WHERE engagement_id = %d AND is_required = 1 AND status NOT IN ("complete","waived")',
					absint( $latest_engagement['id'] )
				)
			);
			if ( $required_blockers > 0 ) {
				$blockers[] = 'engagement_readiness_incomplete';
			}
		}
		if ( 'contracted' === (string) ( $latest_proposal['status'] ?? '' ) && ! $latest_engagement ) {
			$warnings[] = 'engagement_handoff_not_created';
		}
		if ( $latest_proposal && ! in_array( $inquiry['status'], array( 'proposal_requested', 'proposal_sent', 'accepted', 'closed' ), true ) ) {
			$warnings[] = 'inquiry_status_lags_proposal';
		}

		$consistency = $blockers ? 'blocked' : ( $warnings ? 'warning' : 'consistent' );
		$source_timestamps = array_filter(
			array(
				(string) ( $inquiry['updated_at'] ?? '' ),
				(string) ( $fit['updated_at'] ?? '' ),
				(string) ( $latest_meeting['updated_at'] ?? '' ),
				(string) ( $latest_proposal['updated_at'] ?? '' ),
				(string) ( $latest_engagement['updated_at'] ?? '' ),
			)
		);
		$source_updated_at = $source_timestamps ? max( $source_timestamps ) : (string) $inquiry['updated_at'];
		$notes = array(
			'blockers' => array_values( array_unique( $blockers ) ),
			'warnings' => array_values( array_unique( $warnings ) ),
			'authoritative' => array(
				'inquiry' => array(
					'id'             => $inquiry_id,
					'status'         => (string) $inquiry['status'],
					'privacy_status' => (string) $inquiry['privacy_status'],
					'updated_at'     => (string) $inquiry['updated_at'],
				),
				'fit' => array(
					'id'            => absint( $fit['id'] ?? 0 ),
					'status'        => (string) ( $fit['status'] ?? '' ),
					'version'       => absint( $fit['assessment_version'] ?? 0 ),
					'content_hash'  => (string) ( $fit['content_hash'] ?? '' ),
					'updated_at'    => (string) ( $fit['updated_at'] ?? '' ),
				),
				'meeting' => array(
					'id'                => absint( $latest_meeting['id'] ?? 0 ),
					'status'            => (string) ( $latest_meeting['status'] ?? '' ),
					'graph_sync_status' => (string) ( $latest_meeting['graph_sync_status'] ?? '' ),
					'selected_start_utc'=> (string) ( $latest_meeting['selected_start_utc'] ?? '' ),
					'updated_at'        => (string) ( $latest_meeting['updated_at'] ?? '' ),
				),
				'proposal' => array(
					'id'                 => absint( $latest_proposal['id'] ?? 0 ),
					'status'             => (string) ( $latest_proposal['status'] ?? '' ),
					'current_version_id' => absint( $latest_proposal['current_version_id'] ?? 0 ),
					'content_hash'       => (string) ( $latest_proposal['content_hash'] ?? '' ),
					'contract_reference_hash' => ! empty( $latest_proposal['contract_reference'] ) ? hash( 'sha256', (string) $latest_proposal['contract_reference'] ) : '',
					'updated_at'         => (string) ( $latest_proposal['updated_at'] ?? '' ),
				),
				'engagement' => array(
					'id'                  => absint( $latest_engagement['id'] ?? 0 ),
					'status'              => (string) ( $latest_engagement['status'] ?? '' ),
					'current_snapshot_id' => absint( $latest_engagement['current_snapshot_id'] ?? 0 ),
					'row_version'         => absint( $latest_engagement['row_version'] ?? 0 ),
					'updated_at'          => (string) ( $latest_engagement['updated_at'] ?? '' ),
				),
			),
		);
		$hash_payload = array(
			'inquiry_id'      => $inquiry_id,
			'stage'           => $stage,
			'state'           => $state,
			'terminal'        => $terminal,
			'priority'        => $inquiry['review_priority'],
			'owner'           => absint( $latest_engagement['owner_user_id'] ?? $inquiry['assigned_user_id'] ),
			'privacy_status'  => $inquiry['privacy_status'],
			'blockers'        => $notes['blockers'],
			'warnings'        => $notes['warnings'],
			'authoritative'   => $notes['authoritative'],
			'source_updated_at'=> $source_updated_at,
		);
		$projection_hash = hash( 'sha256', wp_json_encode( $hash_payload, JSON_UNESCAPED_SLASHES ) );

		return array(
			'reference'             => (string) $inquiry['reference'],
			'current_stage'         => $stage,
			'current_state'         => $state,
			'terminal_state'        => $terminal,
			'priority'              => (string) $inquiry['review_priority'],
			'owner_user_id'         => absint( $latest_engagement['owner_user_id'] ?? $inquiry['assigned_user_id'] ) ?: null,
			'source_updated_at'     => $source_updated_at,
			'projection_hash'       => $projection_hash,
			'blocker_count'         => count( $blockers ),
			'open_command_count'    => $open_commands,
			'pending_handoff_count' => $pending_handoffs,
			'last_event_at'         => $last_event_at ?: null,
			'consistency_status'    => $consistency,
			'consistency_notes'     => wp_json_encode( $notes ),
		);
	}

	private static function refresh_counts( int $case_id ): void {
		global $wpdb;
		$commands = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . SC_EI_Database::table( 'workflow_commands' ) . ' WHERE case_id = %d AND status IN ("pending","processing")',
				$case_id
			)
		);
		$handoffs = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT COUNT(*) FROM ' . SC_EI_Database::table( 'workflow_handoffs' ) . ' WHERE case_id = %d AND status IN ("prepared","dispatched")',
				$case_id
			)
		);
		$wpdb->update(
			SC_EI_Database::table( 'workflow_cases' ),
			array(
				'open_command_count'    => $commands,
				'pending_handoff_count' => $handoffs,
				'updated_at'            => current_time( 'mysql', true ),
			),
			array( 'id' => $case_id ),
			array( '%d', '%d', '%s' ),
			array( '%d' )
		);
	}

	private static function update_handoff_dispatch( int $handoff_id, bool $acknowledged ): void {
		global $wpdb;
		$handoff = self::find_handoff( $handoff_id );
		if ( ! $handoff || ! in_array( $handoff['status'], array( 'prepared', 'dispatched' ), true ) ) {
			return;
		}
		$now = current_time( 'mysql', true );
		$wpdb->update(
			SC_EI_Database::table( 'workflow_handoffs' ),
			array(
				'status'          => $acknowledged ? 'acknowledged' : 'dispatched',
				'dispatched_at'   => $now,
				'acknowledged_at' => $acknowledged ? $now : $handoff['acknowledged_at'],
				'row_version'     => absint( $handoff['row_version'] ) + 1,
				'updated_at'      => $now,
			),
			array( 'id' => $handoff_id, 'row_version' => absint( $handoff['row_version'] ) ),
			array( '%s', '%s', '%s', '%d', '%s' ),
			array( '%d', '%d' )
		);
	}

	private static function fail_command( array $command, string $code, string $message ) {
		global $wpdb;
		$now = current_time( 'mysql', true );
		$wpdb->update(
			SC_EI_Database::table( 'workflow_commands' ),
			array(
				'status'        => 'failed',
				'completed_at'  => $now,
				'error_code'    => sanitize_key( $code ),
				'error_message' => mb_substr( sanitize_textarea_field( $message ), 0, 1000 ),
				'row_version'   => absint( $command['row_version'] ) + 1,
				'updated_at'    => $now,
			),
			array( 'id' => absint( $command['id'] ) ),
			array( '%s', '%s', '%s', '%s', '%d', '%s' ),
			array( '%d' )
		);
		self::refresh_counts( absint( $command['case_id'] ) );
		SC_EI_Audit_Log::record(
			'workflow_core_command_failed',
			'Workflow Core command failed without changing authoritative domain decisions.',
			array(
				'command_id'   => absint( $command['id'] ),
				'command_type' => $command['command_type'],
				'error_code'   => sanitize_key( $code ),
			),
			absint( $command['inquiry_id'] )
		);
		return new WP_Error( $code, $message );
	}

	private static function fail_outbox( array $event, string $token, string $code, string $message, bool $retryable ) {
		global $wpdb;
		$attempts = max( 1, absint( $event['attempts'] ) );
		$max = max( 1, absint( $event['max_attempts'] ) );
		$retry = $retryable && $attempts < $max;
		$status = $retry ? 'retry_wait' : 'failed';
		$delay = min( HOUR_IN_SECONDS, 30 * ( 2 ** min( 6, $attempts - 1 ) ) );
		$now = current_time( 'mysql', true );
		$available = $retry ? gmdate( 'Y-m-d H:i:s', time() + $delay ) : $event['available_at'];
		$wpdb->update(
			SC_EI_Database::table( 'workflow_outbox' ),
			array(
				'status'        => $status,
				'available_at'  => $available,
				'claim_token'   => '',
				'error_code'    => sanitize_key( $code ),
				'error_message' => mb_substr( sanitize_textarea_field( $message ), 0, 1000 ),
				'row_version'   => absint( $event['row_version'] ) + 1,
				'updated_at'    => $now,
			),
			array( 'id' => absint( $event['id'] ), 'claim_token' => $token ),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%s' ),
			array( '%d', '%s' )
		);
		if ( ! $retry && 'handoff' === $event['aggregate_type'] ) {
			$wpdb->update(
				SC_EI_Database::table( 'workflow_handoffs' ),
				array(
					'status'          => 'failed',
					'failed_at'       => $now,
					'failure_code'    => sanitize_key( $code ),
					'failure_message' => mb_substr( sanitize_textarea_field( $message ), 0, 1000 ),
					'updated_at'      => $now,
				),
				array( 'id' => absint( $event['aggregate_id'] ) ),
				array( '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
		}
		return new WP_Error( $retry ? 'workflow_core_outbox_retry' : $code, $message );
	}

	private static function command_by_key( string $key ): ?array {
		global $wpdb;
		$id = $wpdb->get_var(
			$wpdb->prepare( 'SELECT id FROM ' . SC_EI_Database::table( 'workflow_commands' ) . ' WHERE command_key = %s LIMIT 1', $key )
		);
		return $id ? self::find_command( absint( $id ) ) : null;
	}

	private static function handoff_by_key( string $key ): ?array {
		global $wpdb;
		$id = $wpdb->get_var(
			$wpdb->prepare( 'SELECT id FROM ' . SC_EI_Database::table( 'workflow_handoffs' ) . ' WHERE handoff_key = %s LIMIT 1', $key )
		);
		return $id ? self::find_handoff( absint( $id ) ) : null;
	}

	private static function outbox_by_key( string $key ): ?array {
		global $wpdb;
		$id = $wpdb->get_var(
			$wpdb->prepare( 'SELECT id FROM ' . SC_EI_Database::table( 'workflow_outbox' ) . ' WHERE event_key = %s LIMIT 1', $key )
		);
		return $id ? self::find_outbox( absint( $id ) ) : null;
	}

	private static function sanitize_payload( array $payload ): array {
		$clean = array();
		$sensitive = array( 'password', 'secret', 'token', 'authorization', 'cookie', 'session', 'client_secret', 'access_token' );
		foreach ( array_slice( $payload, 0, 100, true ) as $key => $value ) {
			$key = sanitize_key( (string) $key );
			if ( '' === $key || in_array( $key, $sensitive, true ) ) {
				continue;
			}
			if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
				$clean[ $key ] = $value;
			} elseif ( is_array( $value ) ) {
				$clean[ $key ] = self::sanitize_payload( $value );
			} elseif ( is_scalar( $value ) ) {
				$clean[ $key ] = mb_substr( sanitize_textarea_field( (string) $value ), 0, 2000 );
			}
		}
		ksort( $clean, SORT_STRING );
		return $clean;
	}

	private static function case_integer_fields(): array {
		return array(
			'inquiry_id', 'owner_user_id', 'projection_version', 'blocker_count',
			'open_command_count', 'pending_handoff_count', 'row_version',
		);
	}

	private static function command_integer_fields(): array {
		return array(
			'inquiry_id', 'case_id', 'target_id', 'requested_by', 'claimed_by',
			'row_version',
		);
	}

	private static function handoff_integer_fields(): array {
		return array(
			'inquiry_id', 'case_id', 'prepared_by', 'acknowledged_by', 'row_version',
		);
	}

	private static function outbox_integer_fields(): array {
		return array(
			'inquiry_id', 'case_id', 'aggregate_id', 'attempts', 'max_attempts',
			'row_version',
		);
	}

	private static function formats( array $data, array $integer_fields ): array {
		return array_map(
			static fn( string $key ): string => in_array( $key, $integer_fields, true ) ? '%d' : '%s',
			array_keys( $data )
		);
	}
}
