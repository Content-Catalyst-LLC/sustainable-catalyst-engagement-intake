<?php
/**
 * Durable Microsoft Graph calendar operation queue and meeting reconciliation.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Graph_Repository {

	private const PROCESS_HOOK = 'sc_ei_graph_process_queue';
	private const CATCHUP_HOOK = 'sc_ei_graph_catchup';
	private const LAST_HEALTH_OPTION = 'sc_ei_graph_last_health';
	private const EXTENDED_PROPERTY_GUID = '5d59d8a5-f4d5-4a2a-aef1-5a72ebf63b70';

	public static function register(): void {
		add_action( self::PROCESS_HOOK, array( __CLASS__, 'handle_process_queue' ) );
		add_action( self::CATCHUP_HOOK, array( __CLASS__, 'handle_catchup' ) );
	}

	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::CATCHUP_HOOK ) ) {
			wp_schedule_event( time() + 10 * MINUTE_IN_SECONDS, 'hourly', self::CATCHUP_HOOK );
		}
	}

	public static function unschedule(): void {
		wp_clear_scheduled_hook( self::PROCESS_HOOK );
		wp_clear_scheduled_hook( self::CATCHUP_HOOK );
	}

	public static function handle_process_queue(): void {
		self::process_due( 10 );
	}

	public static function handle_catchup(): void {
		self::recover_stale_locks();
		self::process_due( 25 );
	}

	public static function enqueue_create( int $meeting_offer_id, int $actor_user_id, bool $process_now = true ) {
		global $wpdb;

		$ready = SC_EI_Graph_Credentials::ready();
		if ( is_wp_error( $ready ) ) {
			return $ready;
		}
		$offer = SC_EI_Workflow_Repository::find_meeting_offer( $meeting_offer_id );
		if ( ! $offer || 'accepted_pending_link' !== $offer['status'] ) {
			return new WP_Error( 'graph_meeting_not_ready', __( 'The sender must accept a meeting time that is still waiting for a Teams link before a Graph calendar event can be created.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( empty( $offer['selected_start_utc'] ) || empty( $offer['selected_end_utc'] ) ) {
			return new WP_Error( 'graph_meeting_time_missing', __( 'The selected meeting start or end time is missing.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( ! empty( $offer['graph_event_id'] ) ) {
			return self::enqueue_reconcile( $meeting_offer_id, $actor_user_id, $process_now );
		}

		$inquiry = SC_EI_Inquiry_Repository::find( absint( $offer['inquiry_id'] ) );
		if ( ! $inquiry || 'erased' === $inquiry['privacy_status'] ) {
			return new WP_Error( 'graph_inquiry_unavailable', __( 'The inquiry is unavailable for Microsoft Graph scheduling.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$transaction_id = (string) $offer['graph_transaction_id'];
		if ( '' === $transaction_id ) {
			$transaction_id = wp_generate_uuid4();
			$updated = $wpdb->update(
				SC_EI_Database::table( 'meeting_offers' ),
				array(
					'graph_transaction_id' => $transaction_id,
					'graph_sync_status'    => 'preparing',
					'graph_organizer'      => strtolower( (string) $ready['organizer_user'] ),
					'graph_calendar_id'    => sanitize_text_field( (string) $ready['calendar_id'] ),
					'row_version'          => absint( $offer['row_version'] ) + 1,
					'updated_at'           => current_time( 'mysql', true ),
				),
				array(
					'id'          => $meeting_offer_id,
					'row_version' => absint( $offer['row_version'] ),
				),
				array( '%s', '%s', '%s', '%s', '%d', '%s' ),
				array( '%d', '%d' )
			);
			if ( 1 !== $updated ) {
				return new WP_Error( 'graph_meeting_conflict', __( 'The meeting changed before Graph preparation completed.', 'sustainable-catalyst-engagement-intake' ) );
			}
			$offer = SC_EI_Workflow_Repository::find_meeting_offer( $meeting_offer_id );
		}

		$payload = self::build_event_payload( $offer, $inquiry, $ready );
		if ( is_wp_error( $payload ) ) {
			return $payload;
		}
		$request_hash = hash( 'sha256', wp_json_encode( $payload, JSON_UNESCAPED_SLASHES ) );
		$idempotency_key = hash( 'sha256', 'create|' . $meeting_offer_id . '|' . $transaction_id );
		$operation = self::create_operation(
			array(
				'inquiry_id'      => absint( $offer['inquiry_id'] ),
				'meeting_offer_id'=> $meeting_offer_id,
				'operation_type'  => 'create',
				'idempotency_key' => $idempotency_key,
				'request_hash'    => $request_hash,
				'payload'         => $payload,
				'actor_user_id'   => $actor_user_id,
				'context'         => array(
					'offer_number'              => $offer['offer_number'],
					'transaction_id'            => $transaction_id,
					'include_sender_attendee'   => ! empty( self::settings()['graph_include_sender_attendee'] ),
					'calendar_consent_recorded' => ! empty( $inquiry['calendar_invite_consent'] ),
					'human_triggered'           => true,
				),
			)
		);
		if ( is_wp_error( $operation ) ) {
			return $operation;
		}
		self::set_meeting_graph_state(
			$meeting_offer_id,
			array(
				'graph_sync_status'       => 'queued_create',
				'graph_payload_hash'      => $request_hash,
				'graph_last_error_code'   => '',
				'graph_last_error_message'=> '',
				'graph_next_retry_at'     => current_time( 'mysql', true ),
			)
		);
		self::schedule_processing( time() + 1 );
		return $process_now ? self::process_operation( absint( $operation['id'] ), true ) : $operation;
	}

	public static function enqueue_reconcile( int $meeting_offer_id, int $actor_user_id, bool $process_now = true, int $delay = 0 ) {
		$offer = SC_EI_Workflow_Repository::find_meeting_offer( $meeting_offer_id );
		if ( ! $offer || empty( $offer['graph_event_id'] ) ) {
			return new WP_Error( 'graph_event_link_missing', __( 'No Microsoft Graph event is linked to this meeting.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$existing = self::active_operation( $meeting_offer_id, 'reconcile' );
		if ( $existing ) {
			return $process_now && 0 === $delay ? self::process_operation( absint( $existing['id'] ), true ) : $existing;
		}
		$operation = self::create_operation(
			array(
				'inquiry_id'       => absint( $offer['inquiry_id'] ),
				'meeting_offer_id' => $meeting_offer_id,
				'operation_type'   => 'reconcile',
				'idempotency_key'  => hash( 'sha256', 'reconcile|' . $meeting_offer_id . '|' . $offer['graph_event_id'] . '|' . wp_generate_uuid4() ),
				'request_hash'     => hash( 'sha256', 'GET|' . $offer['graph_event_id'] ),
				'payload'          => array(),
				'actor_user_id'    => $actor_user_id,
				'scheduled_at'     => gmdate( 'Y-m-d H:i:s', time() + max( 0, $delay ) ),
				'context'          => array(
					'offer_number'    => $offer['offer_number'],
					'graph_event_id'  => $offer['graph_event_id'],
					'human_triggered' => 0 === $delay,
				),
			)
		);
		if ( is_wp_error( $operation ) ) {
			return $operation;
		}
		self::set_meeting_graph_state(
			$meeting_offer_id,
			array(
				'graph_sync_status'   => 'reconcile_queued',
				'graph_next_retry_at' => $operation['scheduled_at'],
			)
		);
		self::schedule_processing( strtotime( $operation['scheduled_at'] . ' UTC' ) ?: time() + 1 );
		return $process_now && 0 === $delay ? self::process_operation( absint( $operation['id'] ), true ) : $operation;
	}

	public static function enqueue_delete( int $meeting_offer_id, int $actor_user_id, bool $process_now = true ) {
		$settings = self::settings();
		if ( empty( $settings['graph_allow_remote_cancel'] ) ) {
			return new WP_Error( 'graph_remote_cancel_disabled', __( 'Remote Graph event cancellation is disabled.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$offer = SC_EI_Workflow_Repository::find_meeting_offer( $meeting_offer_id );
		if ( ! $offer || empty( $offer['graph_event_id'] ) ) {
			return new WP_Error( 'graph_event_link_missing', __( 'No Microsoft Graph event is linked to this meeting.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$existing = self::active_operation( $meeting_offer_id, 'delete' );
		if ( $existing ) {
			return $process_now ? self::process_operation( absint( $existing['id'] ), true ) : $existing;
		}
		$operation = self::create_operation(
			array(
				'inquiry_id'       => absint( $offer['inquiry_id'] ),
				'meeting_offer_id' => $meeting_offer_id,
				'operation_type'   => 'delete',
				'idempotency_key'  => hash( 'sha256', 'delete|' . $meeting_offer_id . '|' . $offer['graph_event_id'] ),
				'request_hash'     => hash( 'sha256', 'DELETE|' . $offer['graph_event_id'] ),
				'payload'          => array(),
				'actor_user_id'    => $actor_user_id,
				'context'          => array(
					'offer_number'         => $offer['offer_number'],
					'graph_event_id'       => $offer['graph_event_id'],
					'cancellation_message' => true,
					'human_triggered'      => true,
				),
			)
		);
		if ( is_wp_error( $operation ) ) {
			return $operation;
		}
		self::set_meeting_graph_state(
			$meeting_offer_id,
			array(
				'graph_sync_status'   => 'queued_delete',
				'graph_next_retry_at' => current_time( 'mysql', true ),
			)
		);
		self::schedule_processing( time() + 1 );
		return $process_now ? self::process_operation( absint( $operation['id'] ), true ) : $operation;
	}

	public static function retry_operation( int $operation_id, int $actor_user_id ) {
		global $wpdb;

		$operation = self::find_operation( $operation_id );
		if ( ! $operation || 'permanent_failure' !== $operation['status'] ) {
			return new WP_Error( 'graph_operation_not_failed', __( 'Only a permanently failed Graph operation can be retried manually.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		$updated = $wpdb->update(
			SC_EI_Database::table( 'graph_operations' ),
			array(
				'status'                => 'pending',
				'attempt_count'         => 0,
				'scheduled_at'          => $now,
				'next_retry_at'         => $now,
				'locked_at'             => null,
				'lock_token'            => '',
				'started_at'            => null,
				'completed_at'          => null,
				'graph_error_code'      => '',
				'graph_error_message'   => '',
				'retry_after_seconds'   => 0,
				'actor_user_id'         => $actor_user_id,
				'row_version'           => absint( $operation['row_version'] ) + 1,
				'updated_at'            => $now,
			),
			array(
				'id'          => $operation_id,
				'row_version' => absint( $operation['row_version'] ),
				'status'      => 'permanent_failure',
			),
			array( '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s' ),
			array( '%d', '%d', '%s' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'graph_operation_retry_conflict', __( 'The failed Graph operation changed before it could be retried.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::set_meeting_graph_state(
			absint( $operation['meeting_offer_id'] ),
			array(
				'graph_sync_status'        => 'queued_manual_retry',
				'graph_last_error_code'    => '',
				'graph_last_error_message' => '',
				'graph_attempt_count'      => 0,
				'graph_next_retry_at'      => $now,
			)
		);
		SC_EI_Workflow_Repository::note_graph_event(
			absint( $operation['inquiry_id'] ),
			$actor_user_id,
			absint( $operation['meeting_offer_id'] ),
			'graph_operation_retry_scheduled',
			'permanent_failure',
			'pending',
			array(
				'operation_id'        => $operation_id,
				'operation_type'      => $operation['operation_type'],
				'idempotency_preserved'=> true,
				'request_hash'        => $operation['request_hash'],
			)
		);
		SC_EI_Audit_Log::record(
			'graph_operation_manual_retry',
			'Authorized user requeued a permanently failed Microsoft Graph operation with its original idempotency key and encrypted request payload.',
			array(
				'operation_id'         => $operation_id,
				'meeting_offer_id'     => absint( $operation['meeting_offer_id'] ),
				'operation_type'       => $operation['operation_type'],
				'idempotency_preserved'=> true,
			),
			absint( $operation['inquiry_id'] ),
			null,
			$actor_user_id
		);
		self::schedule_processing( time() + 1 );
		return self::process_operation( $operation_id, true );
	}

	public static function process_due( int $limit = 10 ): array {
		global $wpdb;

		$now = current_time( 'mysql', true );
		$table = SC_EI_Database::table( 'graph_operations' );
		$ids = (array) $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$table}
				WHERE status IN ('pending','retry_wait')
					AND scheduled_at <= %s
					AND (next_retry_at IS NULL OR next_retry_at <= %s)
				ORDER BY scheduled_at ASC, id ASC
				LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$now,
				$now,
				max( 1, min( 100, $limit ) )
			)
		);
		$results = array();
		foreach ( $ids as $id ) {
			$results[ absint( $id ) ] = self::process_operation( absint( $id ), false );
		}
		return $results;
	}

	public static function process_operation( int $operation_id, bool $force = false ) {
		global $wpdb;

		$operation = self::find_operation( $operation_id );
		if ( ! $operation ) {
			return new WP_Error( 'graph_operation_not_found', __( 'The Microsoft Graph operation could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( 'succeeded' === $operation['status'] ) {
			return $operation;
		}
		if ( ! in_array( $operation['status'], array( 'pending', 'retry_wait' ), true ) ) {
			return new WP_Error( 'graph_operation_not_processable', __( 'The Microsoft Graph operation is not available for processing.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( ! $force && ! empty( $operation['next_retry_at'] ) && strtotime( $operation['next_retry_at'] . ' UTC' ) > time() ) {
			return $operation;
		}

		$lock_token = wp_generate_uuid4();
		$now = current_time( 'mysql', true );
		$claimed = $wpdb->update(
			SC_EI_Database::table( 'graph_operations' ),
			array(
				'status'        => 'processing',
				'locked_at'     => $now,
				'lock_token'    => $lock_token,
				'started_at'    => $operation['started_at'] ?: $now,
				'attempt_count' => absint( $operation['attempt_count'] ) + 1,
				'row_version'   => absint( $operation['row_version'] ) + 1,
				'updated_at'    => $now,
			),
			array(
				'id'          => $operation_id,
				'row_version' => absint( $operation['row_version'] ),
				'status'      => $operation['status'],
			),
			array( '%s', '%s', '%s', '%s', '%d', '%d', '%s' ),
			array( '%d', '%d', '%s' )
		);
		if ( 1 !== $claimed ) {
			return new WP_Error( 'graph_operation_claim_conflict', __( 'Another process claimed this Microsoft Graph operation.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$operation = self::find_operation( $operation_id );
		$offer = SC_EI_Workflow_Repository::find_meeting_offer( absint( $operation['meeting_offer_id'] ) );
		if ( ! $offer ) {
			return self::fail_operation( $operation, $lock_token, 'graph_meeting_not_found', __( 'The linked meeting offer no longer exists.', 'sustainable-catalyst-engagement-intake' ), false, 0, array() );
		}

		$client_request_id = wp_generate_uuid4();
		$result = null;
		if ( 'create' === $operation['operation_type'] && 'accepted_pending_link' !== $offer['status'] ) {
			return self::fail_operation(
				$operation,
				$lock_token,
				'graph_local_state_blocked',
				__( 'The meeting is no longer waiting for a Teams link, so remote event creation was blocked.', 'sustainable-catalyst-engagement-intake' ),
				false,
				0,
				array()
			);
		}
		if ( 'create' === $operation['operation_type'] ) {
			$payload = SC_EI_Graph_Crypto::open_array( (string) $operation['payload_json'] );
			if ( is_wp_error( $payload ) ) {
				return self::fail_operation( $operation, $lock_token, $payload->get_error_code(), $payload->get_error_message(), false, 0, array() );
			}
			$result = SC_EI_Graph_Client::create_event( $payload, $client_request_id );
		} elseif ( 'reconcile' === $operation['operation_type'] ) {
			if ( empty( $offer['graph_event_id'] ) ) {
				return self::fail_operation( $operation, $lock_token, 'graph_event_link_missing', __( 'The linked Graph event ID is missing.', 'sustainable-catalyst-engagement-intake' ), false, 0, array() );
			}
			$result = SC_EI_Graph_Client::get_event( (string) $offer['graph_event_id'], $client_request_id );
		} elseif ( 'delete' === $operation['operation_type'] ) {
			if ( empty( $offer['graph_event_id'] ) ) {
				return self::complete_delete( $operation, $lock_token, $offer, array( 'status' => 404, 'request_id' => '', 'client_request_id' => $client_request_id ) );
			}
			$result = SC_EI_Graph_Client::delete_event( (string) $offer['graph_event_id'], $client_request_id );
		} else {
			return self::fail_operation( $operation, $lock_token, 'graph_operation_type_invalid', __( 'The Graph operation type is not supported.', 'sustainable-catalyst-engagement-intake' ), false, 0, array() );
		}

		if ( is_wp_error( $result ) ) {
			$data = $result->get_error_data();
			$data = is_array( $data ) ? $data : array();
			if ( 'delete' === $operation['operation_type'] && 404 === absint( $data['status'] ?? 0 ) ) {
				return self::complete_delete( $operation, $lock_token, $offer, $data );
			}
			return self::fail_operation(
				$operation,
				$lock_token,
				$result->get_error_code(),
				$result->get_error_message(),
				! empty( $data['retryable'] ),
				absint( $data['retry_after'] ?? 0 ),
				$data
			);
		}

		if ( 'delete' === $operation['operation_type'] ) {
			return self::complete_delete( $operation, $lock_token, $offer, $result );
		}

		$remote = self::remote_snapshot( (array) ( $result['data'] ?? array() ) );
		if ( empty( $remote['event_id'] ) ) {
			return self::fail_operation(
				$operation,
				$lock_token,
				'graph_event_id_missing',
				__( 'Microsoft Graph returned success without an event identifier.', 'sustainable-catalyst-engagement-intake' ),
				true,
				0,
				$result
			);
		}
		$applied = self::apply_remote_event( $offer, $remote, absint( $operation['actor_user_id'] ), $result );
		if ( is_wp_error( $applied ) ) {
			return self::fail_operation( $operation, $lock_token, $applied->get_error_code(), $applied->get_error_message(), true, 0, $result );
		}

		if (
			'reconcile' === $operation['operation_type']
			&& empty( $remote['join_url'] )
			&& empty( $remote['is_cancelled'] )
			&& in_array( $offer['status'], array( 'accepted_pending_link', 'scheduled' ), true )
		) {
			return self::fail_operation(
				$operation,
				$lock_token,
				'graph_join_url_pending',
				__( 'The Graph event exists, but the Microsoft Teams join URL is still initializing.', 'sustainable-catalyst-engagement-intake' ),
				true,
				absint( self::settings()['graph_reconcile_delay_seconds'] ?? 30 ),
				$result
			);
		}

		$completed = self::complete_operation( $operation, $lock_token, $result, $remote );
		if ( is_wp_error( $completed ) ) {
			return $completed;
		}

		if ( 'create' === $operation['operation_type'] && empty( $remote['join_url'] ) && empty( $remote['is_cancelled'] ) ) {
			self::enqueue_reconcile(
				absint( $offer['id'] ),
				absint( $operation['actor_user_id'] ),
				false,
				max( 10, absint( self::settings()['graph_reconcile_delay_seconds'] ?? 30 ) )
			);
		}
		return $completed;
	}

	public static function run_health_check( int $actor_user_id ) {
		$result = SC_EI_Graph_Client::health();
		$record = array(
			'ok'         => ! is_wp_error( $result ),
			'checked_at' => current_time( 'mysql', true ),
			'actor_id'   => $actor_user_id,
		);
		if ( is_wp_error( $result ) ) {
			$data = $result->get_error_data();
			$record['error_code'] = $result->get_error_code();
			$record['error_message'] = mb_substr( sanitize_textarea_field( $result->get_error_message() ), 0, 1000 );
			$record['response_status'] = is_array( $data ) ? absint( $data['status'] ?? 0 ) : 0;
			$record['request_id'] = is_array( $data ) ? sanitize_text_field( (string) ( $data['request_id'] ?? '' ) ) : '';
		} else {
			$record += $result;
		}
		update_option( self::LAST_HEALTH_OPTION, $record, false );
		SC_EI_Audit_Log::record(
			'graph_health_checked',
			$record['ok']
				? 'Authorized user verified the Microsoft Graph calendar connector.'
				: 'Authorized user ran a Microsoft Graph calendar connector test that failed.',
			array(
				'ok'              => $record['ok'],
				'error_code'      => (string) ( $record['error_code'] ?? '' ),
				'response_status' => absint( $record['response_status'] ?? 0 ),
				'request_id'      => (string) ( $record['request_id'] ?? '' ),
			),
			null,
			null,
			$actor_user_id
		);
		return $result;
	}

	public static function last_health(): array {
		return wp_parse_args(
			get_option( self::LAST_HEALTH_OPTION, array() ),
			array(
				'ok'              => false,
				'checked_at'      => '',
				'error_code'      => '',
				'error_message'   => '',
				'response_status' => 0,
				'request_id'      => '',
				'calendar_id'     => '',
				'calendar_name'   => '',
				'calendar_can_edit'=> false,
				'organizer_user'  => '',
			)
		);
	}

	public static function query_operations( array $args = array() ): array {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			array(
				'status'     => '',
				'type'       => '',
				'inquiry_id' => 0,
				'limit'      => 250,
			)
		);
		$table = SC_EI_Database::table( 'graph_operations' );
		$meetings = SC_EI_Database::table( 'meeting_offers' );
		$inquiries = SC_EI_Database::table( 'inquiries' );
		$where = array( '1=1' );
		$params = array();

		$status = sanitize_key( (string) $args['status'] );
		if ( in_array( $status, array( 'pending', 'retry_wait', 'processing', 'succeeded', 'permanent_failure', 'canceled' ), true ) ) {
			$where[] = 'o.status = %s';
			$params[] = $status;
		}
		$type = sanitize_key( (string) $args['type'] );
		if ( in_array( $type, array( 'create', 'reconcile', 'delete' ), true ) ) {
			$where[] = 'o.operation_type = %s';
			$params[] = $type;
		}
		if ( absint( $args['inquiry_id'] ) ) {
			$where[] = 'o.inquiry_id = %d';
			$params[] = absint( $args['inquiry_id'] );
		}

		$sql = "SELECT o.*, m.offer_number, m.graph_sync_status, m.graph_event_id,
				i.reference, i.contact_name, u.display_name AS actor_name
			FROM {$table} o
			LEFT JOIN {$meetings} m ON m.id = o.meeting_offer_id
			LEFT JOIN {$inquiries} i ON i.id = o.inquiry_id
			LEFT JOIN {$wpdb->users} u ON u.ID = o.actor_user_id
			WHERE " . implode( ' AND ', $where ) . "
			ORDER BY
				CASE o.status
					WHEN 'processing' THEN 0
					WHEN 'retry_wait' THEN 1
					WHEN 'pending' THEN 2
					WHEN 'permanent_failure' THEN 3
					ELSE 4
				END,
				COALESCE(o.next_retry_at, o.scheduled_at) ASC,
				o.id DESC
			LIMIT %d";
		$params[] = max( 1, min( 2000, absint( $args['limit'] ) ) );
		return (array) $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
	}

	public static function operations_for_meeting( int $meeting_offer_id, int $limit = 100 ): array {
		global $wpdb;
		$table = SC_EI_Database::table( 'graph_operations' );
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT o.*, u.display_name AS actor_name
				FROM {$table} o
				LEFT JOIN {$wpdb->users} u ON u.ID = o.actor_user_id
				WHERE o.meeting_offer_id = %d
				ORDER BY o.created_at DESC, o.id DESC
				LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$meeting_offer_id,
				max( 1, min( 1000, $limit ) )
			),
			ARRAY_A
		);
	}

	public static function operations_for_inquiry( int $inquiry_id, int $limit = 500 ): array {
		global $wpdb;
		$table = SC_EI_Database::table( 'graph_operations' );
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT o.*, u.display_name AS actor_name
				FROM {$table} o
				LEFT JOIN {$wpdb->users} u ON u.ID = o.actor_user_id
				WHERE o.inquiry_id = %d
				ORDER BY o.created_at DESC, o.id DESC
				LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$inquiry_id,
				max( 1, min( 2000, $limit ) )
			),
			ARRAY_A
		);
	}

	public static function find_operation( int $id ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'graph_operations' );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $row ?: null;
	}

	public static function metrics(): array {
		global $wpdb;
		$table = SC_EI_Database::table( 'graph_operations' );
		$meetings = SC_EI_Database::table( 'meeting_offers' );
		$now = current_time( 'mysql', true );
		return array(
			'queued'           => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status IN ('pending','retry_wait')" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'processing'       => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'processing'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'failed'           => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'permanent_failure'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'succeeded_today'  => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = 'succeeded' AND completed_at >= %s", gmdate( 'Y-m-d 00:00:00' ) ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'synced_meetings'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$meetings} WHERE graph_sync_status = 'synced'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'pending_join_url' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$meetings} WHERE graph_sync_status IN ('created_pending_join_url','reconcile_queued','retry_wait')" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'due_now'          => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status IN ('pending','retry_wait') AND scheduled_at <= %s AND (next_retry_at IS NULL OR next_retry_at <= %s)", $now, $now ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	public static function export_for_inquiry( int $inquiry_id ): array {
		$operations = self::operations_for_inquiry( $inquiry_id, 2000 );
		foreach ( $operations as &$operation ) {
			unset( $operation['payload_json'] );
			$operation['payload_encrypted'] = true;
		}
		unset( $operation );
		return array(
			'credentials' => SC_EI_Graph_Credentials::public_status(),
			'circuit'     => SC_EI_Graph_Client::circuit_status(),
			'operations'  => $operations,
		);
	}

	public static function redact_for_privacy( int $inquiry_id, string $now ): bool {
		global $wpdb;

		$operations = $wpdb->query(
			$wpdb->prepare(
				"UPDATE " . SC_EI_Database::table( 'graph_operations' ) . "
				SET payload_json = '', graph_error_message = '', response_snapshot_json = '',
					context_json = %s, updated_at = %s
				WHERE inquiry_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				wp_json_encode(
					array(
						'personal_data_erased' => true,
						'graph_schema_version'  => SC_EI_GRAPH_SCHEMA_VERSION,
					)
				),
				$now,
				$inquiry_id
			)
		);
		$meetings = $wpdb->query(
			$wpdb->prepare(
				"UPDATE " . SC_EI_Database::table( 'meeting_offers' ) . "
				SET graph_event_id = '', graph_i_cal_uid = '', graph_change_key = '',
					graph_etag = '', graph_web_link = '', graph_join_url = '',
					graph_last_error_message = '', updated_at = %s
				WHERE inquiry_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$now,
				$inquiry_id
			)
		);
		return false !== $operations && false !== $meetings;
	}

	public static function reset_linkage( int $meeting_offer_id, string $reason, int $actor_user_id ) {
		global $wpdb;

		$offer = SC_EI_Workflow_Repository::find_meeting_offer( $meeting_offer_id );
		if ( ! $offer ) {
			return new WP_Error( 'graph_meeting_not_found', __( 'The meeting offer could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$reason = sanitize_textarea_field( $reason );
		if ( '' === trim( $reason ) ) {
			return new WP_Error( 'graph_reset_reason_required', __( 'Record why Graph linkage is being reset.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$updated = $wpdb->update(
			SC_EI_Database::table( 'meeting_offers' ),
			array(
				'graph_sync_status'           => 'reset_manual',
				'graph_transaction_id'        => '',
				'graph_event_id'              => '',
				'graph_i_cal_uid'             => '',
				'graph_change_key'            => '',
				'graph_etag'                  => '',
				'graph_web_link'              => '',
				'graph_join_url'              => '',
				'graph_payload_hash'          => '',
				'graph_remote_start_utc'      => null,
				'graph_remote_end_utc'        => null,
				'graph_last_request_id'       => '',
				'graph_last_client_request_id'=> '',
				'graph_last_error_code'       => '',
				'graph_last_error_message'    => '',
				'graph_next_retry_at'         => null,
				'graph_reconciled_at'         => null,
				'graph_deleted_at'            => null,
				'row_version'                 => absint( $offer['row_version'] ) + 1,
				'updated_at'                  => current_time( 'mysql', true ),
			),
			array(
				'id'          => $meeting_offer_id,
				'row_version' => absint( $offer['row_version'] ),
			),
			null,
			array( '%d', '%d' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'graph_meeting_conflict', __( 'The meeting changed before Graph linkage could be reset.', 'sustainable-catalyst-engagement-intake' ) );
		}
		SC_EI_Workflow_Repository::note_graph_event(
			absint( $offer['inquiry_id'] ),
			$actor_user_id,
			$meeting_offer_id,
			'graph_linkage_reset',
			$offer['graph_sync_status'],
			'reset_manual',
			array(
				'reason'                => $reason,
				'remote_event_deleted'  => ! empty( $offer['graph_deleted_at'] ),
				'remote_event_id_existed'=> ! empty( $offer['graph_event_id'] ),
			)
		);
		SC_EI_Audit_Log::record(
			'graph_linkage_reset',
			'Authorized user reset local Microsoft Graph event linkage after documenting the reason.',
			array(
				'meeting_offer_id'       => $meeting_offer_id,
				'reason'                 => $reason,
				'remote_event_id_existed'=> ! empty( $offer['graph_event_id'] ),
			),
			absint( $offer['inquiry_id'] ),
			null,
			$actor_user_id
		);
		return SC_EI_Workflow_Repository::find_meeting_offer( $meeting_offer_id );
	}

	public static function settings(): array {
		return wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Graph_Credentials::defaults() );
	}

	private static function create_operation( array $args ) {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			array(
				'inquiry_id'       => 0,
				'meeting_offer_id' => 0,
				'operation_type'   => 'create',
				'idempotency_key'  => '',
				'request_hash'     => '',
				'payload'          => array(),
				'actor_user_id'    => 0,
				'scheduled_at'     => current_time( 'mysql', true ),
				'context'          => array(),
			)
		);
		$table = SC_EI_Database::table( 'graph_operations' );
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE idempotency_key = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$args['idempotency_key']
			),
			ARRAY_A
		);
		if ( $existing ) {
			return $existing;
		}
		$sealed = SC_EI_Graph_Crypto::seal_array( (array) $args['payload'] );
		if ( is_wp_error( $sealed ) ) {
			return $sealed;
		}
		$now = current_time( 'mysql', true );
		$settings = self::settings();
		$data = array(
			'public_id'             => wp_generate_uuid4(),
			'inquiry_id'            => absint( $args['inquiry_id'] ),
			'meeting_offer_id'      => absint( $args['meeting_offer_id'] ),
			'operation_type'        => sanitize_key( $args['operation_type'] ),
			'status'                => 'pending',
			'idempotency_key'       => sanitize_text_field( $args['idempotency_key'] ),
			'request_hash'          => sanitize_text_field( $args['request_hash'] ),
			'payload_json'          => $sealed,
			'attempt_count'         => 0,
			'max_attempts'          => max( 1, min( 20, absint( $settings['graph_max_attempts'] ?? 6 ) ) ),
			'scheduled_at'          => sanitize_text_field( $args['scheduled_at'] ),
			'next_retry_at'         => sanitize_text_field( $args['scheduled_at'] ),
			'locked_at'             => null,
			'lock_token'            => '',
			'started_at'            => null,
			'completed_at'          => null,
			'http_method'           => self::method_for_type( $args['operation_type'] ),
			'endpoint_path'         => '',
			'response_status'       => 0,
			'graph_error_code'      => '',
			'graph_error_message'   => '',
			'retry_after_seconds'   => 0,
			'request_id'            => '',
			'client_request_id'     => '',
			'response_snapshot_json'=> '',
			'actor_user_id'         => absint( $args['actor_user_id'] ),
			'context_json'          => wp_json_encode( self::sanitize_context( (array) $args['context'] ) ),
			'row_version'           => 0,
			'created_at'            => $now,
			'updated_at'            => $now,
		);
		$inserted = $wpdb->insert( $table, $data, self::formats( $data ) );
		if ( false === $inserted ) {
			$existing = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$table} WHERE idempotency_key = %s LIMIT 1", $args['idempotency_key'] ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				ARRAY_A
			);
			if ( $existing ) {
				return $existing;
			}
			return new WP_Error( 'graph_operation_save_failed', __( 'The Microsoft Graph operation could not be queued.', 'sustainable-catalyst-engagement-intake' ) );
		}
		return self::find_operation( (int) $wpdb->insert_id );
	}

	private static function active_operation( int $meeting_offer_id, string $type ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'graph_operations' );
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE meeting_offer_id = %d
					AND operation_type = %s
					AND status IN ('pending','retry_wait','processing')
				ORDER BY id DESC
				LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$meeting_offer_id,
				$type
			),
			ARRAY_A
		);
		return $row ?: null;
	}

	private static function complete_operation( array $operation, string $lock_token, array $result, array $snapshot ) {
		global $wpdb;

		$now = current_time( 'mysql', true );
		$updated = $wpdb->update(
			SC_EI_Database::table( 'graph_operations' ),
			array(
				'status'                 => 'succeeded',
				'completed_at'           => $now,
				'next_retry_at'          => null,
				'locked_at'              => null,
				'lock_token'             => '',
				'endpoint_path'          => sanitize_text_field( (string) ( $result['path'] ?? '' ) ),
				'response_status'        => absint( $result['status'] ?? 0 ),
				'graph_error_code'       => '',
				'graph_error_message'    => '',
				'retry_after_seconds'    => 0,
				'request_id'             => sanitize_text_field( (string) ( $result['request_id'] ?? '' ) ),
				'client_request_id'      => sanitize_text_field( (string) ( $result['client_request_id'] ?? '' ) ),
				'response_snapshot_json' => wp_json_encode( $snapshot ),
				'row_version'            => absint( $operation['row_version'] ) + 1,
				'updated_at'             => $now,
			),
			array(
				'id'         => absint( $operation['id'] ),
				'lock_token' => $lock_token,
				'status'     => 'processing',
			),
			null,
			array( '%d', '%s', '%s' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'graph_operation_completion_conflict', __( 'The Microsoft Graph operation completed, but its queue record changed before completion was saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		return self::find_operation( absint( $operation['id'] ) );
	}

	private static function complete_delete( array $operation, string $lock_token, array $offer, array $result ) {
		$marked = self::set_meeting_graph_state(
			absint( $offer['id'] ),
			array(
				'graph_sync_status'        => 'deleted',
				'graph_join_url'           => '',
				'graph_web_link'           => '',
				'graph_deleted_at'         => current_time( 'mysql', true ),
				'graph_last_success_at'    => current_time( 'mysql', true ),
				'graph_last_request_id'    => sanitize_text_field( (string) ( $result['request_id'] ?? '' ) ),
				'graph_last_client_request_id' => sanitize_text_field( (string) ( $result['client_request_id'] ?? '' ) ),
				'graph_last_error_code'    => '',
				'graph_last_error_message' => '',
				'graph_next_retry_at'      => null,
			)
		);
		if ( is_wp_error( $marked ) ) {
			return self::fail_operation( $operation, $lock_token, $marked->get_error_code(), $marked->get_error_message(), true, 0, $result );
		}
		SC_EI_Workflow_Repository::note_graph_event(
			absint( $offer['inquiry_id'] ),
			absint( $operation['actor_user_id'] ),
			absint( $offer['id'] ),
			'graph_event_deleted',
			$offer['graph_sync_status'],
			'deleted',
			array(
				'response_status' => absint( $result['status'] ?? 0 ),
				'request_id'      => sanitize_text_field( (string) ( $result['request_id'] ?? '' ) ),
			)
		);
		return self::complete_operation(
			$operation,
			$lock_token,
			$result,
			array(
				'deleted'         => true,
				'event_id_hash'   => ! empty( $offer['graph_event_id'] ) ? substr( hash( 'sha256', $offer['graph_event_id'] ), 0, 16 ) : '',
				'response_status' => absint( $result['status'] ?? 0 ),
			)
		);
	}

	private static function fail_operation(
		array $operation,
		string $lock_token,
		string $code,
		string $message,
		bool $retryable,
		int $retry_after,
		array $result
	) {
		global $wpdb;

		$attempt = max( 1, absint( $operation['attempt_count'] ) );
		$settings = self::settings();
		$may_retry = $retryable
			&& ! empty( $settings['graph_retry_enabled'] )
			&& $attempt < max( 1, absint( $operation['max_attempts'] ) );
		$delay = $may_retry ? SC_EI_Graph_Client::retry_delay( $attempt, $retry_after ) : 0;
		$next = $may_retry ? gmdate( 'Y-m-d H:i:s', time() + $delay ) : null;
		$status = $may_retry ? 'retry_wait' : 'permanent_failure';
		$now = current_time( 'mysql', true );
		$clean_message = mb_substr( sanitize_textarea_field( $message ), 0, 2000 );
		$updated = $wpdb->update(
			SC_EI_Database::table( 'graph_operations' ),
			array(
				'status'                 => $status,
				'next_retry_at'          => $next,
				'locked_at'              => null,
				'lock_token'             => '',
				'completed_at'           => $may_retry ? null : $now,
				'endpoint_path'          => sanitize_text_field( (string) ( $result['path'] ?? '' ) ),
				'response_status'        => absint( $result['status'] ?? 0 ),
				'graph_error_code'       => sanitize_key( $code ),
				'graph_error_message'    => $clean_message,
				'retry_after_seconds'    => $delay,
				'request_id'             => sanitize_text_field( (string) ( $result['request_id'] ?? '' ) ),
				'client_request_id'      => sanitize_text_field( (string) ( $result['client_request_id'] ?? '' ) ),
				'response_snapshot_json' => '',
				'row_version'            => absint( $operation['row_version'] ) + 1,
				'updated_at'             => $now,
			),
			array(
				'id'         => absint( $operation['id'] ),
				'lock_token' => $lock_token,
				'status'     => 'processing',
			),
			null,
			array( '%d', '%s', '%s' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'graph_operation_failure_conflict', __( 'The Graph failure could not be recorded because the queue record changed.', 'sustainable-catalyst-engagement-intake' ) );
		}

		self::set_meeting_graph_state(
			absint( $operation['meeting_offer_id'] ),
			array(
				'graph_sync_status'           => $may_retry ? 'retry_wait' : 'permanent_failure',
				'graph_last_error_code'       => sanitize_key( $code ),
				'graph_last_error_message'    => $clean_message,
				'graph_attempt_count'         => $attempt,
				'graph_last_attempt_at'       => $now,
				'graph_last_request_id'       => sanitize_text_field( (string) ( $result['request_id'] ?? '' ) ),
				'graph_last_client_request_id'=> sanitize_text_field( (string) ( $result['client_request_id'] ?? '' ) ),
				'graph_next_retry_at'         => $next,
			)
		);
		if ( $may_retry ) {
			self::schedule_processing( strtotime( $next . ' UTC' ) ?: time() + $delay );
		}
		SC_EI_Workflow_Repository::note_graph_event(
			absint( $operation['inquiry_id'] ),
			absint( $operation['actor_user_id'] ),
			absint( $operation['meeting_offer_id'] ),
			$may_retry ? 'graph_operation_retry_scheduled' : 'graph_operation_failed',
			'processing',
			$status,
			array(
				'operation_type' => $operation['operation_type'],
				'error_code'     => sanitize_key( $code ),
				'attempt'        => $attempt,
				'next_retry_at'  => $next,
				'request_id'     => sanitize_text_field( (string) ( $result['request_id'] ?? '' ) ),
			)
		);
		return new WP_Error(
			sanitize_key( $code ) ?: 'graph_operation_failed',
			$clean_message,
			array(
				'retryable'      => $may_retry,
				'next_retry_at'  => $next,
				'operation_id'   => absint( $operation['id'] ),
				'request_id'     => sanitize_text_field( (string) ( $result['request_id'] ?? '' ) ),
			)
		);
	}

	private static function apply_remote_event( array $offer, array $remote, int $actor_user_id, array $result ) {
		global $wpdb;

		$now = current_time( 'mysql', true );
		$join_url = (string) $remote['join_url'];
		if ( '' !== $join_url && ! SC_EI_Teams::is_teams_url( $join_url ) ) {
			$join_url = '';
		}
		$sync_status = ! empty( $remote['is_cancelled'] )
			? 'remote_canceled'
			: ( '' !== $join_url ? 'synced' : 'created_pending_join_url' );
		$was_synced = 'synced' === $offer['graph_sync_status'];
		$data = array(
			'graph_sync_status'           => $sync_status,
			'graph_event_id'              => sanitize_text_field( (string) $remote['event_id'] ),
			'graph_i_cal_uid'             => sanitize_text_field( (string) $remote['i_cal_uid'] ),
			'graph_change_key'            => sanitize_text_field( (string) $remote['change_key'] ),
			'graph_etag'                  => sanitize_text_field( (string) $remote['etag'] ),
			'graph_web_link'              => esc_url_raw( (string) $remote['web_link'] ),
			'graph_join_url'              => esc_url_raw( $join_url ),
			'graph_remote_start_utc'      => $remote['start_utc'] ?: null,
			'graph_remote_end_utc'        => $remote['end_utc'] ?: null,
			'graph_last_request_id'       => sanitize_text_field( (string) ( $result['request_id'] ?? '' ) ),
			'graph_last_client_request_id'=> sanitize_text_field( (string) ( $result['client_request_id'] ?? '' ) ),
			'graph_last_error_code'       => '',
			'graph_last_error_message'    => '',
			'graph_attempt_count'         => absint( $offer['graph_attempt_count'] ) + 1,
			'graph_last_attempt_at'       => $now,
			'graph_last_success_at'       => $now,
			'graph_next_retry_at'         => null,
			'graph_reconciled_at'         => $now,
			'row_version'                 => absint( $offer['row_version'] ) + 1,
			'updated_at'                  => $now,
		);
		$may_finalize = '' !== $join_url
			&& empty( $remote['is_cancelled'] )
			&& in_array( $offer['status'], array( 'accepted_pending_link', 'scheduled' ), true );
		if ( $may_finalize ) {
			$data['teams_url'] = $join_url;
			$data['status'] = 'scheduled';
			$data['finalized_by'] = $actor_user_id ?: $offer['finalized_by'];
			$data['finalized_at'] = $offer['finalized_at'] ?: $now;
		} elseif ( '' !== $join_url && in_array( $offer['status'], array( 'canceled', 'superseded', 'declined', 'completed' ), true ) ) {
			$data['graph_sync_status'] = 'remote_exists_local_closed';
		}
		$updated = $wpdb->update(
			SC_EI_Database::table( 'meeting_offers' ),
			$data,
			array(
				'id'          => absint( $offer['id'] ),
				'row_version' => absint( $offer['row_version'] ),
			),
			null,
			array( '%d', '%d' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'graph_meeting_apply_conflict', __( 'The meeting changed before the Graph event could be reconciled.', 'sustainable-catalyst-engagement-intake' ) );
		}

		if ( $may_finalize ) {
			$inquiry = SC_EI_Inquiry_Repository::find( absint( $offer['inquiry_id'] ) );
			if ( $inquiry ) {
				$wpdb->update(
					SC_EI_Database::table( 'inquiries' ),
					array(
						'scheduling_status'   => 'scheduled',
						'teams_meeting_url'   => $join_url,
						'scheduled_start_utc' => $offer['selected_start_utc'],
						'scheduled_end_utc'   => $offer['selected_end_utc'],
						'scheduled_timezone'  => $offer['timezone'],
						'updated_at'          => $now,
					),
					array( 'id' => absint( $offer['inquiry_id'] ) ),
					array( '%s', '%s', '%s', '%s', '%s', '%s' ),
					array( '%d' )
				);
			}
			if ( ! $was_synced ) {
				SC_EI_Portal_Repository::create_portal_message(
					absint( $offer['inquiry_id'] ),
					'outbound',
					sprintf(
						/* translators: %s meeting offer number. */
						__( 'Your calendar-backed Microsoft Teams meeting is ready. Open the Meetings section for the join link and calendar details. Offer: %s.', 'sustainable-catalyst-engagement-intake' ),
						$offer['offer_number']
					),
					$actor_user_id
				);
			}
		}

		SC_EI_Workflow_Repository::note_graph_event(
			absint( $offer['inquiry_id'] ),
			$actor_user_id,
			absint( $offer['id'] ),
			! empty( $remote['is_cancelled'] ) ? 'graph_remote_canceled' : ( '' !== $join_url ? 'graph_event_synced' : 'graph_join_url_pending' ),
			$offer['graph_sync_status'],
			$sync_status,
			array(
				'event_id_hash'   => substr( hash( 'sha256', (string) $remote['event_id'] ), 0, 16 ),
				'transaction_id'  => $offer['graph_transaction_id'],
				'request_id'      => sanitize_text_field( (string) ( $result['request_id'] ?? '' ) ),
				'join_url_ready'  => '' !== $join_url,
				'remote_start_utc'=> $remote['start_utc'],
				'remote_end_utc'  => $remote['end_utc'],
			)
		);
		return SC_EI_Workflow_Repository::find_meeting_offer( absint( $offer['id'] ) );
	}

	private static function build_event_payload( array $offer, array $inquiry, array $credentials ) {
		$settings = self::settings();
		$attendees = array();
		if ( ! empty( $settings['graph_include_sender_attendee'] ) ) {
			if ( ! empty( $settings['graph_require_calendar_consent'] ) && empty( $inquiry['calendar_invite_consent'] ) ) {
				return new WP_Error( 'graph_calendar_consent_required', __( 'The sender has not granted calendar-invitation consent.', 'sustainable-catalyst-engagement-intake' ) );
			}
			if ( empty( $inquiry['contact_email'] ) || ! is_email( $inquiry['contact_email'] ) ) {
				return new WP_Error( 'graph_attendee_email_invalid', __( 'The sender email is invalid for a calendar invitation.', 'sustainable-catalyst-engagement-intake' ) );
			}
			$attendees[] = array(
				'emailAddress' => array(
					'address' => strtolower( sanitize_email( $inquiry['contact_email'] ) ),
					'name'    => sanitize_text_field( (string) $inquiry['contact_name'] ),
				),
				'type' => 'required',
			);
		}

		$body = '<p>' . esc_html( (string) $offer['purpose'] ) . '</p>'
			. '<p><strong>Engagement reference:</strong> ' . esc_html( (string) $inquiry['reference'] ) . '</p>'
			. '<p><strong>Meeting offer:</strong> ' . esc_html( (string) $offer['offer_number'] ) . '</p>'
			. '<p>This calendar event was created by an authorized Sustainable Catalyst administrator after the sender selected an approved time.</p>';

		$payload = array(
			'subject' => sanitize_text_field( (string) $offer['title'] ),
			'body'    => array(
				'contentType' => 'HTML',
				'content'     => wp_kses_post( $body ),
			),
			'start'   => array(
				'dateTime' => gmdate( 'Y-m-d\TH:i:s', strtotime( $offer['selected_start_utc'] . ' UTC' ) ),
				'timeZone' => 'UTC',
			),
			'end'     => array(
				'dateTime' => gmdate( 'Y-m-d\TH:i:s', strtotime( $offer['selected_end_utc'] . ' UTC' ) ),
				'timeZone' => 'UTC',
			),
			'location' => array( 'displayName' => 'Microsoft Teams' ),
			'attendees' => $attendees,
			'allowNewTimeProposals' => false,
			'responseRequested'     => ! empty( $attendees ),
			'isOnlineMeeting'       => true,
			'onlineMeetingProvider' => 'teamsForBusiness',
			'showAs'                => 'busy',
			'sensitivity'           => 'private',
			'transactionId'         => (string) $offer['graph_transaction_id'],
			'singleValueExtendedProperties' => array(
				array(
					'id'    => 'String {' . self::EXTENDED_PROPERTY_GUID . '} Name SC_EI_OfferNumber',
					'value' => sanitize_text_field( (string) $offer['offer_number'] ),
				),
				array(
					'id'    => 'String {' . self::EXTENDED_PROPERTY_GUID . '} Name SC_EI_PublicId',
					'value' => sanitize_text_field( (string) $offer['public_id'] ),
				),
			),
		);
		return $payload;
	}

	private static function remote_snapshot( array $event ): array {
		$join_url = (string) ( $event['onlineMeeting']['joinUrl'] ?? '' );
		return array(
			'event_id'           => sanitize_text_field( (string) ( $event['id'] ?? '' ) ),
			'i_cal_uid'          => sanitize_text_field( (string) ( $event['iCalUId'] ?? '' ) ),
			'change_key'         => sanitize_text_field( (string) ( $event['changeKey'] ?? '' ) ),
			'etag'               => sanitize_text_field( (string) ( $event['@odata.etag'] ?? '' ) ),
			'web_link'           => esc_url_raw( (string) ( $event['webLink'] ?? '' ) ),
			'join_url'           => esc_url_raw( $join_url ),
			'start_utc'          => self::graph_datetime_to_utc( (array) ( $event['start'] ?? array() ) ),
			'end_utc'            => self::graph_datetime_to_utc( (array) ( $event['end'] ?? array() ) ),
			'is_online_meeting'  => ! empty( $event['isOnlineMeeting'] ),
			'online_provider'    => sanitize_key( (string) ( $event['onlineMeetingProvider'] ?? '' ) ),
			'is_cancelled'       => ! empty( $event['isCancelled'] ),
			'transaction_id'     => sanitize_text_field( (string) ( $event['transactionId'] ?? '' ) ),
			'last_modified'      => sanitize_text_field( (string) ( $event['lastModifiedDateTime'] ?? '' ) ),
		);
	}

	private static function graph_datetime_to_utc( array $value ): ?string {
		$date = sanitize_text_field( (string) ( $value['dateTime'] ?? '' ) );
		$timezone = sanitize_text_field( (string) ( $value['timeZone'] ?? 'UTC' ) );
		if ( '' === $date ) {
			return null;
		}
		try {
			$zone = 'UTC' === strtoupper( $timezone ) ? new DateTimeZone( 'UTC' ) : new DateTimeZone( $timezone );
			return ( new DateTimeImmutable( $date, $zone ) )->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
		} catch ( Throwable $error ) {
			$timestamp = strtotime( $date . ' UTC' );
			return $timestamp ? gmdate( 'Y-m-d H:i:s', $timestamp ) : null;
		}
	}

	private static function set_meeting_graph_state( int $meeting_offer_id, array $data ) {
		global $wpdb;

		for ( $attempt = 0; $attempt < 2; $attempt++ ) {
			$offer = SC_EI_Workflow_Repository::find_meeting_offer( $meeting_offer_id );
			if ( ! $offer ) {
				return new WP_Error( 'graph_meeting_not_found', __( 'The meeting offer could not be found.', 'sustainable-catalyst-engagement-intake' ) );
			}
			$update = $data;
			$update['row_version'] = absint( $offer['row_version'] ) + 1;
			$update['updated_at'] = current_time( 'mysql', true );
			$result = $wpdb->update(
				SC_EI_Database::table( 'meeting_offers' ),
				$update,
				array(
					'id'          => $meeting_offer_id,
					'row_version' => absint( $offer['row_version'] ),
				),
				null,
				array( '%d', '%d' )
			);
			if ( 1 === $result ) {
				return SC_EI_Workflow_Repository::find_meeting_offer( $meeting_offer_id );
			}
		}
		return new WP_Error( 'graph_meeting_state_conflict', __( 'The meeting changed before the Graph state could be saved.', 'sustainable-catalyst-engagement-intake' ) );
	}

	private static function recover_stale_locks(): int {
		global $wpdb;
		$table = SC_EI_Database::table( 'graph_operations' );
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - 15 * MINUTE_IN_SECONDS );
		$now = current_time( 'mysql', true );
		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				SET status = 'retry_wait', next_retry_at = %s, locked_at = NULL,
					lock_token = '', graph_error_code = 'graph_stale_lock_recovered',
					graph_error_message = '', row_version = row_version + 1, updated_at = %s
				WHERE status = 'processing' AND locked_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$now,
				$now,
				$cutoff
			)
		);
		return false === $result ? 0 : absint( $result );
	}

	private static function schedule_processing( int $timestamp ): void {
		$timestamp = max( time() + 1, $timestamp );
		$next = wp_next_scheduled( self::PROCESS_HOOK );
		if ( ! $next || $next > $timestamp + 5 ) {
			wp_schedule_single_event( $timestamp, self::PROCESS_HOOK );
		}
	}

	private static function method_for_type( string $type ): string {
		return 'create' === $type ? 'POST' : ( 'delete' === $type ? 'DELETE' : 'GET' );
	}

	private static function sanitize_context( array $context ): array {
		$clean = array();
		foreach ( $context as $key => $value ) {
			$key = sanitize_key( (string) $key );
			if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
				$clean[ $key ] = $value;
			} elseif ( is_scalar( $value ) ) {
				$clean[ $key ] = mb_substr( sanitize_text_field( (string) $value ), 0, 500 );
			}
		}
		return $clean;
	}

	private static function formats( array $data ): array {
		$integers = array(
			'inquiry_id', 'meeting_offer_id', 'attempt_count', 'max_attempts',
			'response_status', 'retry_after_seconds', 'actor_user_id', 'row_version',
		);
		return array_map(
			static fn( string $key ): string => in_array( $key, $integers, true ) ? '%d' : '%s',
			array_keys( $data )
		);
	}
}
