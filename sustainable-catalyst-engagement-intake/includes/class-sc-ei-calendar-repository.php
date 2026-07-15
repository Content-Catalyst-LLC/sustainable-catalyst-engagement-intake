<?php
/**
 * Governed Microsoft Teams and calendar coordination.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Calendar_Repository {

	public const MIGRATION_KEY = 'v1_3_0_microsoft_teams_calendar_coordination';
	public const REMINDER_HOOK = 'sc_ei_calendar_process_reminders';

	public static function register(): void {
		add_action( self::REMINDER_HOOK, array( __CLASS__, 'process_due_reminders' ) );
	}

	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::REMINDER_HOOK ) ) {
			wp_schedule_event( time() + 15 * MINUTE_IN_SECONDS, 'hourly', self::REMINDER_HOOK );
		}
	}

	public static function unschedule(): void {
		wp_clear_scheduled_hook( self::REMINDER_HOOK );
	}

	public static function settings(): array {
		return wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Calendar_Schema::default_settings() );
	}

	public static function maybe_upgrade(): void {
		$stored = (string) get_option( 'sc_ei_calendar_schema_version', '' );
		if ( SC_EI_CALENDAR_SCHEMA_VERSION !== $stored ) {
			SC_EI_Database::install();
			$contract = SC_EI_Database::calendar_columns_exist();
			if ( ! in_array( false, $contract, true ) ) {
				update_option( 'sc_ei_calendar_schema_version_previous', $stored, false );
				update_option( 'sc_ei_calendar_schema_version', SC_EI_CALENDAR_SCHEMA_VERSION, false );
			}
		}
		self::record_migration( $stored );
		self::schedule();
	}

	public static function record_migration( string $from_schema = '' ) {
		global $wpdb;
		$table = SC_EI_Database::table( 'platform_migrations' );
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE migration_key = %s LIMIT 1", self::MIGRATION_KEY ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $existing && 'completed' === (string) $existing['status'] ) {
			return $existing;
		}
		$contract = SC_EI_Database::calendar_columns_exist();
		$ok = ! in_array( false, $contract, true );
		$now = current_time( 'mysql', true );
		$data = array(
			'public_id'     => $existing['public_id'] ?? wp_generate_uuid4(),
			'migration_key' => self::MIGRATION_KEY,
			'from_version'  => sanitize_text_field( $from_schema ),
			'to_version'    => SC_EI_CALENDAR_SCHEMA_VERSION,
			'status'        => $ok ? 'completed' : 'failed',
			'schema_hash'   => hash( 'sha256', wp_json_encode( array( 'calendar' => SC_EI_CALENDAR_SCHEMA_VERSION, 'workflow' => SC_EI_WORKFLOW_SCHEMA_VERSION, 'database' => SC_EI_DB_VERSION, 'plugin' => SC_EI_VERSION ) ) ),
			'context_json'  => wp_json_encode(
				array(
					'release'                    => 'Microsoft Teams and Calendar Coordination',
					'database_schema_changed'    => true,
					'destructive'                => false,
					'public_booking_enabled'     => false,
					'automatic_calendar_booking' => false,
					'automatic_reminder_sending' => false,
					'explicit_timezone_required' => true,
					'sender_portal_allowlist'    => true,
				),
				JSON_UNESCAPED_SLASHES
			),
			'started_at'    => $existing['started_at'] ?? $now,
			'completed_at'  => $now,
			'error_code'    => $ok ? '' : 'calendar_schema_incomplete',
			'error_message' => $ok ? '' : 'One or more calendar-coordination columns or tables are unavailable.',
			'created_at'    => $existing['created_at'] ?? $now,
			'updated_at'    => $now,
		);
		if ( $existing ) {
			$result = $wpdb->update( $table, $data, array( 'id' => absint( $existing['id'] ) ), self::formats( $data, array() ), array( '%d' ) );
			$id = absint( $existing['id'] );
		} else {
			$result = $wpdb->insert( $table, $data, self::formats( $data, array() ) );
			$id = (int) $wpdb->insert_id;
		}
		if ( false === $result ) {
			return new WP_Error( 'calendar_migration_journal_failed', __( 'The v1.3.0 calendar-coordination migration journal could not be recorded.', 'sustainable-catalyst-engagement-intake' ) );
		}
		SC_EI_Audit_Log::record(
			$ok ? 'calendar_coordination_migration_completed' : 'calendar_coordination_migration_failed',
			$ok ? 'Microsoft Teams and calendar-coordination schema migration completed without destructive conversion.' : 'Calendar-coordination migration found an incomplete schema.',
			array( 'migration_key' => self::MIGRATION_KEY, 'from_schema' => $from_schema, 'to_schema' => SC_EI_CALENDAR_SCHEMA_VERSION, 'destructive' => false ),
			null,
			null,
			get_current_user_id()
		);
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $ok ? $row : new WP_Error( 'calendar_schema_incomplete', __( 'The calendar-coordination schema requires repair.', 'sustainable-catalyst-engagement-intake' ), $row );
	}

	public static function save_coordination( int $meeting_id, array $input, int $actor_user_id ) {
		global $wpdb;
		$meeting = SC_EI_Workflow_Repository::find_meeting_offer( $meeting_id );
		if ( ! $meeting ) {
			return new WP_Error( 'calendar_meeting_not_found', __( 'The meeting record could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( in_array( (string) $meeting['status'], array( 'canceled', 'expired', 'superseded' ), true ) ) {
			return new WP_Error( 'calendar_meeting_closed', __( 'Closed meeting records cannot be edited.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$timezone = sanitize_text_field( (string) ( $input['timezone'] ?? $meeting['timezone'] ) );
		if ( ! SC_EI_Teams::valid_timezone( $timezone ) ) {
			return new WP_Error( 'calendar_timezone_invalid', __( 'Choose a valid IANA timezone.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$teams_url = esc_url_raw( (string) ( $input['teams_url'] ?? $meeting['teams_url'] ) );
		if ( $teams_url && ! SC_EI_Teams::is_teams_url( $teams_url ) ) {
			return new WP_Error( 'calendar_teams_url_invalid', __( 'The meeting link must use an approved Microsoft Teams domain.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$organizer_email = sanitize_email( (string) ( $input['organizer_email'] ?? $meeting['organizer_email'] ) );
		if ( $organizer_email && ! is_email( $organizer_email ) ) {
			return new WP_Error( 'calendar_organizer_email_invalid', __( 'Enter a valid organizer email address.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$participants = SC_EI_Teams::sanitize_participant_emails( $input['participant_emails'] ?? json_decode( (string) ( $meeting['participant_emails_json'] ?? '[]' ), true ) );
		$data = array(
			'meeting_type'              => SC_EI_Calendar_Schema::sanitize_meeting_type( (string) ( $input['meeting_type'] ?? $meeting['meeting_type'] ) ),
			'timezone'                  => $timezone,
			'teams_url'                 => $teams_url,
			'organizer_name'            => mb_substr( sanitize_text_field( (string) ( $input['organizer_name'] ?? $meeting['organizer_name'] ) ), 0, 191 ),
			'organizer_email'           => strtolower( $organizer_email ),
			'participant_emails_json'   => wp_json_encode( $participants ),
			'agenda'                    => sanitize_textarea_field( (string) ( $input['agenda'] ?? $meeting['agenda'] ) ),
			'preparation_requests'      => sanitize_textarea_field( (string) ( $input['preparation_requests'] ?? $meeting['preparation_requests'] ) ),
			'sender_summary'            => sanitize_textarea_field( (string) ( $input['sender_summary'] ?? $meeting['sender_summary'] ) ),
			'sender_next_step'          => sanitize_textarea_field( (string) ( $input['sender_next_step'] ?? $meeting['sender_next_step'] ) ),
			'related_document_ids_json' => wp_json_encode( SC_EI_Calendar_Schema::sanitize_document_ids( $input['related_document_ids'] ?? json_decode( (string) ( $meeting['related_document_ids_json'] ?? '[]' ), true ) ) ),
			'calendar_provider'         => SC_EI_Calendar_Schema::sanitize_provider( (string) ( $input['calendar_provider'] ?? $meeting['calendar_provider'] ) ),
			'external_calendar_reference'=> mb_substr( sanitize_text_field( (string) ( $input['external_calendar_reference'] ?? $meeting['external_calendar_reference'] ) ), 0, 255 ),
			'row_version'               => absint( $meeting['row_version'] ) + 1,
			'updated_at'                => current_time( 'mysql', true ),
		);
		$updated = $wpdb->update(
			SC_EI_Database::table( 'meeting_offers' ),
			$data,
			array( 'id' => $meeting_id, 'row_version' => absint( $meeting['row_version'] ) ),
			self::formats( $data, array( 'row_version' ) ),
			array( '%d', '%d' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'calendar_coordination_conflict', __( 'The meeting changed before coordination details were saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::record_event( $meeting, 'meeting_coordination_updated', (string) $meeting['status'], (string) $meeting['status'], $actor_user_id, array( 'meeting_type' => $data['meeting_type'], 'participant_count' => count( $participants ), 'calendar_provider' => $data['calendar_provider'] ) );
		SC_EI_Audit_Log::record( 'calendar_coordination_updated', 'Authorized staff updated governed Microsoft Teams coordination details.', array( 'meeting_offer_id' => $meeting_id, 'meeting_type' => $data['meeting_type'], 'participant_count' => count( $participants ) ), absint( $meeting['inquiry_id'] ), null, $actor_user_id );
		return SC_EI_Workflow_Repository::find_meeting_offer( $meeting_id );
	}

	public static function reschedule( int $meeting_id, string $start_local, string $end_local, string $timezone, string $reason, int $actor_user_id ) {
		global $wpdb;
		$meeting = SC_EI_Workflow_Repository::find_meeting_offer( $meeting_id );
		if ( ! $meeting || ! in_array( (string) $meeting['status'], array( 'accepted_pending_link', 'scheduled' ), true ) ) {
			return new WP_Error( 'calendar_reschedule_unavailable', __( 'Only an accepted or scheduled meeting can be rescheduled.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$timezone = sanitize_text_field( $timezone );
		if ( ! SC_EI_Teams::valid_timezone( $timezone ) ) {
			return new WP_Error( 'calendar_timezone_invalid', __( 'Choose a valid IANA timezone.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$start_utc = SC_EI_Teams::local_to_utc( $start_local, $timezone );
		$end_utc = SC_EI_Teams::local_to_utc( $end_local, $timezone );
		if ( ! $start_utc || ! $end_utc || strtotime( $start_utc . ' UTC' ) <= time() || strtotime( $end_utc . ' UTC' ) <= strtotime( $start_utc . ' UTC' ) ) {
			return new WP_Error( 'calendar_reschedule_time_invalid', __( 'Provide a future start and a later end time.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$reason = sanitize_textarea_field( $reason );
		if ( '' === trim( $reason ) ) {
			return new WP_Error( 'calendar_reschedule_reason_required', __( 'Record why the meeting is being rescheduled.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		$data = array(
			'previous_start_utc'  => $meeting['selected_start_utc'],
			'previous_end_utc'    => $meeting['selected_end_utc'],
			'selected_start_utc'  => $start_utc,
			'selected_end_utc'    => $end_utc,
			'selected_slot_key'   => 'reschedule-' . ( absint( $meeting['reschedule_count'] ) + 1 ),
			'timezone'            => $timezone,
			'reschedule_count'    => absint( $meeting['reschedule_count'] ) + 1,
			'last_rescheduled_at' => $now,
			'last_rescheduled_by' => $actor_user_id ?: null,
			'graph_sync_status'   => ! empty( $meeting['graph_event_id'] ) ? 'reconcile_required' : (string) $meeting['graph_sync_status'],
			'row_version'         => absint( $meeting['row_version'] ) + 1,
			'updated_at'          => $now,
		);
		$updated = $wpdb->update( SC_EI_Database::table( 'meeting_offers' ), $data, array( 'id' => $meeting_id, 'row_version' => absint( $meeting['row_version'] ) ), self::formats( $data, array( 'reschedule_count', 'last_rescheduled_by', 'row_version' ) ), array( '%d', '%d' ) );
		if ( 1 !== $updated ) {
			return new WP_Error( 'calendar_reschedule_conflict', __( 'The meeting changed before the new time was saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::cancel_open_reminders( $meeting_id, 'meeting_rescheduled' );
		self::schedule_reminders( $meeting_id, true );
		$fresh = SC_EI_Workflow_Repository::find_meeting_offer( $meeting_id );
		self::record_event( $meeting, 'meeting_rescheduled', (string) $meeting['status'], (string) $meeting['status'], $actor_user_id, array( 'previous_start_utc' => $meeting['selected_start_utc'], 'previous_end_utc' => $meeting['selected_end_utc'], 'new_start_utc' => $start_utc, 'new_end_utc' => $end_utc, 'timezone' => $timezone, 'reason' => $reason ) );
		SC_EI_Audit_Log::record( 'calendar_meeting_rescheduled', 'Authorized staff rescheduled a Microsoft Teams meeting with explicit timezone and preserved history.', array( 'meeting_offer_id' => $meeting_id, 'previous_start_utc' => $meeting['selected_start_utc'], 'new_start_utc' => $start_utc, 'timezone' => $timezone, 'reason' => $reason ), absint( $meeting['inquiry_id'] ), null, $actor_user_id );
		return $fresh;
	}

	public static function complete( int $meeting_id, array $input, int $actor_user_id ) {
		global $wpdb;
		$meeting = SC_EI_Workflow_Repository::find_meeting_offer( $meeting_id );
		if ( ! $meeting || 'scheduled' !== (string) $meeting['status'] ) {
			return new WP_Error( 'calendar_completion_unavailable', __( 'Only a scheduled meeting can be completed.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$result = SC_EI_Workflow_Repository::change_meeting_status( $meeting_id, 'completed', '', $actor_user_id );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$follow_up_due_local = (string) ( $input['follow_up_due_at'] ?? '' );
		$follow_up_due_at = self::sanitize_datetime( $follow_up_due_local, (string) $meeting['timezone'] );
		$follow_up_owner = absint( $input['follow_up_owner_user_id'] ?? 0 );
		$task_id = 0;
		$follow_up_title = mb_substr( sanitize_text_field( (string) ( $input['follow_up_title'] ?? '' ) ), 0, 255 );
		if ( $follow_up_title ) {
			$task = SC_EI_Lifecycle_Repository::add_task(
				absint( $meeting['inquiry_id'] ),
				array(
					'title'            => $follow_up_title,
					'details'          => sanitize_textarea_field( (string) ( $input['follow_up_details'] ?? '' ) ),
					'priority'         => 'normal',
					'due_at'           => $follow_up_due_local,
					'assigned_user_id' => $follow_up_owner,
					'reminder_policy'  => 'daily_when_due',
				),
				$actor_user_id
			);
			if ( ! is_wp_error( $task ) ) {
				$task_id = absint( $task['id'] ?? 0 );
			}
		}
		$data = array(
			'post_meeting_internal_notes' => sanitize_textarea_field( (string) ( $input['internal_notes'] ?? '' ) ),
			'post_meeting_sender_summary' => sanitize_textarea_field( (string) ( $input['sender_summary'] ?? '' ) ),
			'decisions'                   => sanitize_textarea_field( (string) ( $input['decisions'] ?? '' ) ),
			'open_questions'              => sanitize_textarea_field( (string) ( $input['open_questions'] ?? '' ) ),
			'follow_up_owner_user_id'     => $follow_up_owner ?: null,
			'follow_up_due_at'            => $follow_up_due_at,
			'follow_up_task_id'           => $task_id ?: null,
			'row_version'                 => absint( $result['row_version'] ?? 0 ) + 1,
			'updated_at'                  => current_time( 'mysql', true ),
		);
		$updated = $wpdb->update( SC_EI_Database::table( 'meeting_offers' ), $data, array( 'id' => $meeting_id, 'row_version' => absint( $result['row_version'] ?? 0 ) ), self::formats( $data, array( 'follow_up_owner_user_id', 'follow_up_task_id', 'row_version' ) ), array( '%d', '%d' ) );
		if ( 1 !== $updated ) {
			return new WP_Error( 'calendar_completion_conflict', __( 'The meeting completed, but post-meeting coordination could not be saved. Review the meeting record.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::cancel_open_reminders( $meeting_id, 'meeting_completed' );
		self::create_reminder( $meeting_id, 'post_meeting', current_time( 'mysql', true ) );
		$fresh = SC_EI_Workflow_Repository::find_meeting_offer( $meeting_id );
		self::record_event( $meeting, 'meeting_followup_recorded', 'scheduled', 'completed', $actor_user_id, array( 'follow_up_task_id' => $task_id, 'follow_up_due_at' => $follow_up_due_at, 'sender_summary_published' => '' !== trim( (string) $data['post_meeting_sender_summary'] ) ) );
		return $fresh;
	}

	public static function cancel( int $meeting_id, string $reason, int $actor_user_id ) {
		global $wpdb;
		$meeting = SC_EI_Workflow_Repository::find_meeting_offer( $meeting_id );
		if ( ! $meeting || in_array( (string) $meeting['status'], array( 'completed', 'canceled', 'expired', 'superseded' ), true ) ) {
			return new WP_Error( 'calendar_cancellation_unavailable', __( 'This meeting cannot be canceled from its current state.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$reason = sanitize_textarea_field( $reason );
		if ( '' === trim( $reason ) ) {
			return new WP_Error( 'calendar_cancellation_reason_required', __( 'Record why the meeting is being canceled.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$result = SC_EI_Workflow_Repository::change_meeting_status( $meeting_id, 'canceled', $reason, $actor_user_id );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$now = current_time( 'mysql', true );
		$data = array(
			'teams_url'         => '',
			'graph_join_url'    => '',
			'join_url_revoked_at'=> $now,
			'sender_next_step'  => __( 'This meeting was canceled. A new approved time will be shared if another meeting is needed.', 'sustainable-catalyst-engagement-intake' ),
			'row_version'       => absint( $result['row_version'] ?? 0 ) + 1,
			'updated_at'        => $now,
		);
		$wpdb->update( SC_EI_Database::table( 'meeting_offers' ), $data, array( 'id' => $meeting_id, 'row_version' => absint( $result['row_version'] ?? 0 ) ), self::formats( $data, array( 'row_version' ) ), array( '%d', '%d' ) );
		self::cancel_open_reminders( $meeting_id, 'meeting_canceled' );
		self::create_reminder( $meeting_id, 'canceled', $now );
		return SC_EI_Workflow_Repository::find_meeting_offer( $meeting_id );
	}

	public static function schedule_reminders( int $meeting_id, bool $rescheduled = false ): array {
		$meeting = SC_EI_Workflow_Repository::find_meeting_offer( $meeting_id );
		if ( ! $meeting || 'scheduled' !== (string) $meeting['status'] || empty( $meeting['selected_start_utc'] ) ) {
			return array();
		}
		$settings = self::settings();
		$start = strtotime( $meeting['selected_start_utc'] . ' UTC' );
		$created = array();
		if ( ! empty( $settings['calendar_create_confirmation_record'] ) ) {
			$created[] = self::create_reminder( $meeting_id, $rescheduled ? 'rescheduled' : 'invitation', current_time( 'mysql', true ) );
		}
		if ( ! empty( $settings['calendar_create_24_hour_reminder'] ) && $start - DAY_IN_SECONDS > time() ) {
			$created[] = self::create_reminder( $meeting_id, 'twenty_four_hour', gmdate( 'Y-m-d H:i:s', $start - DAY_IN_SECONDS ) );
		}
		if ( ! empty( $settings['calendar_create_1_hour_reminder'] ) && $start - HOUR_IN_SECONDS > time() ) {
			$created[] = self::create_reminder( $meeting_id, 'one_hour', gmdate( 'Y-m-d H:i:s', $start - HOUR_IN_SECONDS ) );
		}
		return array_values( array_filter( $created ) );
	}

	public static function create_reminder( int $meeting_id, string $reminder_type, string $due_at ) {
		global $wpdb;
		$meeting = SC_EI_Workflow_Repository::find_meeting_offer( $meeting_id );
		if ( ! $meeting || ! isset( SC_EI_Calendar_Schema::reminder_types()[ $reminder_type ] ) ) {
			return new WP_Error( 'calendar_reminder_invalid', __( 'The reminder could not be created.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$idempotency_key = hash( 'sha256', implode( '|', array( $meeting_id, $reminder_type, (string) $meeting['selected_start_utc'], (string) $meeting['reschedule_count'] ) ) );
		$table = SC_EI_Database::table( 'meeting_reminders' );
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE idempotency_key = %s LIMIT 1", $idempotency_key ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $existing ) {
			return $existing;
		}
		$now = current_time( 'mysql', true );
		$data = array(
			'public_id'        => wp_generate_uuid4(),
			'meeting_offer_id' => $meeting_id,
			'inquiry_id'       => absint( $meeting['inquiry_id'] ),
			'reminder_type'    => sanitize_key( $reminder_type ),
			'audience'         => 'sender',
			'status'           => 'pending',
			'due_at'           => sanitize_text_field( $due_at ),
			'idempotency_key'  => $idempotency_key,
			'communication_id' => null,
			'attempt_count'    => 0,
			'last_error_code'  => '',
			'last_error_message'=> '',
			'ready_at'         => null,
			'sent_at'          => null,
			'canceled_at'      => null,
			'created_at'       => $now,
			'updated_at'       => $now,
		);
		$inserted = $wpdb->insert( $table, $data, self::formats( $data, array( 'meeting_offer_id', 'inquiry_id', 'communication_id', 'attempt_count' ) ) );
		if ( false === $inserted ) {
			return new WP_Error( 'calendar_reminder_save_failed', __( 'The reminder record could not be saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", (int) $wpdb->insert_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function process_due_reminders(): void {
		global $wpdb;
		$table = SC_EI_Database::table( 'meeting_reminders' );
		$now = current_time( 'mysql', true );
		$rows = (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status = 'pending' AND due_at <= %s ORDER BY due_at ASC, id ASC LIMIT 100", $now ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( $rows as $row ) {
			$meeting = SC_EI_Workflow_Repository::find_meeting_offer( absint( $row['meeting_offer_id'] ) );
			if ( ! $meeting || in_array( (string) $meeting['status'], array( 'canceled', 'expired', 'superseded' ), true ) ) {
				$wpdb->update( $table, array( 'status' => 'canceled', 'canceled_at' => $now, 'updated_at' => $now ), array( 'id' => absint( $row['id'] ), 'status' => 'pending' ), array( '%s', '%s', '%s' ), array( '%d', '%s' ) );
				continue;
			}
			$updated = $wpdb->update( $table, array( 'status' => 'ready_for_review', 'ready_at' => $now, 'attempt_count' => absint( $row['attempt_count'] ) + 1, 'updated_at' => $now ), array( 'id' => absint( $row['id'] ), 'status' => 'pending' ), array( '%s', '%s', '%d', '%s' ), array( '%d', '%s' ) );
			if ( 1 === $updated ) {
				do_action( 'sc_ei_calendar_reminder_due', $row, $meeting );
			}
		}
		update_option( 'sc_ei_last_calendar_reminder_run', $now, false );
	}

	public static function mark_reminder_sent( int $reminder_id, int $communication_id, int $actor_user_id ) {
		global $wpdb;
		$table = SC_EI_Database::table( 'meeting_reminders' );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $reminder_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( ! $row || ! in_array( (string) $row['status'], array( 'pending', 'ready_for_review', 'failed' ), true ) ) {
			return new WP_Error( 'calendar_reminder_unavailable', __( 'The reminder cannot be marked sent.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		$updated = $wpdb->update( $table, array( 'status' => 'sent', 'communication_id' => $communication_id ?: null, 'sent_at' => $now, 'updated_at' => $now ), array( 'id' => $reminder_id ), array( '%s', '%d', '%s', '%s' ), array( '%d' ) );
		if ( false === $updated ) {
			return new WP_Error( 'calendar_reminder_update_failed', __( 'The reminder status could not be updated.', 'sustainable-catalyst-engagement-intake' ) );
		}
		SC_EI_Audit_Log::record( 'calendar_reminder_marked_sent', 'Authorized staff linked a reviewed meeting reminder to a communication record.', array( 'reminder_id' => $reminder_id, 'communication_id' => $communication_id ), absint( $row['inquiry_id'] ), null, $actor_user_id );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $reminder_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function reminders_for_meeting( int $meeting_id ): array {
		global $wpdb;
		$table = SC_EI_Database::table( 'meeting_reminders' );
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE meeting_offer_id = %d ORDER BY due_at ASC, id ASC", $meeting_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function metrics(): array {
		global $wpdb;
		$meetings = SC_EI_Database::table( 'meeting_offers' );
		$reminders = SC_EI_Database::table( 'meeting_reminders' );
		$now = current_time( 'mysql', true );
		$week = gmdate( 'Y-m-d H:i:s', time() + 7 * DAY_IN_SECONDS );
		return array(
			'scheduled'           => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$meetings} WHERE status = 'scheduled'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'next_seven_days'     => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$meetings} WHERE status = 'scheduled' AND selected_start_utc BETWEEN %s AND %s", $now, $week ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'reminders_ready'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$reminders} WHERE status = 'ready_for_review'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'follow_up_overdue'   => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$meetings} WHERE status = 'completed' AND follow_up_due_at IS NOT NULL AND follow_up_due_at < %s AND (follow_up_task_id IS NULL OR follow_up_task_id = 0)", $now ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'graph_reconcile_due' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$meetings} WHERE graph_sync_status IN ('reconcile_required','cancel_required')" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'missing_timezone'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$meetings} WHERE status = 'scheduled' AND (timezone = '' OR timezone IS NULL)" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'canceled_active_link' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$meetings} WHERE status = 'canceled' AND ((teams_url IS NOT NULL AND teams_url <> '') OR (graph_join_url IS NOT NULL AND graph_join_url <> ''))" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	public static function sender_snapshot( int $inquiry_id ): array {
		$rows = SC_EI_Workflow_Repository::meeting_offers_for_inquiry( $inquiry_id, true );
		$result = array();
		foreach ( $rows as $row ) {
			$closed = in_array( (string) $row['status'], array( 'canceled', 'expired', 'superseded' ), true );
			$result[] = array(
				'id'                   => absint( $row['id'] ),
				'offer_number'         => (string) $row['offer_number'],
				'status'               => (string) $row['status'],
				'title'                => (string) $row['title'],
				'meeting_type'         => (string) ( $row['meeting_type'] ?? 'other' ),
				'purpose'              => (string) $row['purpose'],
				'timezone'             => (string) $row['timezone'],
				'selected_start_utc'   => $row['selected_start_utc'],
				'selected_end_utc'     => $row['selected_end_utc'],
				'teams_url'            => $closed ? '' : ( SC_EI_Teams::is_teams_url( (string) $row['teams_url'] ) ? (string) $row['teams_url'] : '' ),
				'agenda'               => (string) ( $row['agenda'] ?? '' ),
				'preparation_requests' => (string) ( $row['preparation_requests'] ?? '' ),
				'sender_summary'       => (string) ( $row['sender_summary'] ?? '' ),
				'sender_next_step'     => (string) ( $row['sender_next_step'] ?? '' ),
				'post_meeting_summary' => (string) ( $row['post_meeting_sender_summary'] ?? '' ),
				'cancellation_reason'  => 'canceled' === (string) $row['status'] ? (string) $row['cancellation_reason'] : '',
				'reschedule_count'     => absint( $row['reschedule_count'] ?? 0 ),
			);
		}
		return $result;
	}

	public static function export_for_inquiry( int $inquiry_id ): array {
		$meetings = SC_EI_Workflow_Repository::meeting_offers_for_inquiry( $inquiry_id, false );
		foreach ( $meetings as &$meeting ) {
			$meeting['reminders'] = self::reminders_for_meeting( absint( $meeting['id'] ) );
		}
		unset( $meeting );
		return array( 'schema' => 'sc-calendar-coordination/1.0', 'meetings' => $meetings );
	}

	public static function redact_for_privacy( int $inquiry_id, string $now ): bool {
		global $wpdb;
		$meetings = $wpdb->query(
			$wpdb->prepare(
				"UPDATE " . SC_EI_Database::table( 'meeting_offers' ) . " SET organizer_name = '', organizer_email = '', participant_emails_json = '[]', post_meeting_internal_notes = '', decisions = '', open_questions = '', updated_at = %s WHERE inquiry_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$now,
				$inquiry_id
			)
		);
		return false !== $meetings;
	}

	private static function cancel_open_reminders( int $meeting_id, string $reason ): void {
		global $wpdb;
		$now = current_time( 'mysql', true );
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE " . SC_EI_Database::table( 'meeting_reminders' ) . " SET status = 'canceled', canceled_at = %s, last_error_code = %s, updated_at = %s WHERE meeting_offer_id = %d AND status IN ('pending','ready_for_review','failed')", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$now,
				sanitize_key( $reason ),
				$now,
				$meeting_id
			)
		);
	}

	private static function record_event( array $meeting, string $event_type, string $from_status, string $to_status, int $actor_user_id, array $context ): void {
		global $wpdb;
		$data = array(
			'public_id'   => wp_generate_uuid4(),
			'inquiry_id'  => absint( $meeting['inquiry_id'] ),
			'actor_type'  => 'staff',
			'actor_id'    => $actor_user_id ?: null,
			'object_type' => 'meeting',
			'object_id'   => absint( $meeting['id'] ),
			'event_type'  => sanitize_key( $event_type ),
			'from_status' => sanitize_key( $from_status ),
			'to_status'   => sanitize_key( $to_status ),
			'context_json'=> wp_json_encode( $context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
			'created_at'  => current_time( 'mysql', true ),
		);
		$wpdb->insert( SC_EI_Database::table( 'workflow_events' ), $data, self::formats( $data, array( 'inquiry_id', 'actor_id', 'object_id' ) ) );
	}

	private static function sanitize_datetime( string $value, string $timezone ): ?string {
		$value = trim( $value );
		if ( '' === $value ) {
			return null;
		}
		$timezone = SC_EI_Teams::valid_timezone( $timezone ) ? $timezone : ( wp_timezone_string() ?: 'UTC' );
		return SC_EI_Teams::local_to_utc( $value, $timezone );
	}

	private static function formats( array $data, array $integer_fields ): array {
		return array_map( static fn( string $field ): string => in_array( $field, $integer_fields, true ) ? '%d' : '%s', array_keys( $data ) );
	}
}
