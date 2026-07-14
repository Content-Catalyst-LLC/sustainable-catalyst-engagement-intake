<?php
/**
 * Advisory operations and engagement lifecycle repository.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Lifecycle_Repository {

	public const REMINDER_HOOK = 'sc_ei_lifecycle_task_reminders';
	public const MIGRATION_KEY = 'v1_1_0_advisory_operations_engagement_lifecycle';

	public static function register(): void {
		add_action( self::REMINDER_HOOK, array( __CLASS__, 'process_due_tasks' ) );
	}

	public static function maybe_upgrade(): void {
		$stored = (string) get_option( 'sc_ei_lifecycle_schema_version', '' );
		if ( version_compare( $stored, SC_EI_LIFECYCLE_SCHEMA_VERSION, '<' ) ) {
			update_option( 'sc_ei_lifecycle_schema_version_previous', $stored, false );
			self::backfill_defaults();
			update_option( 'sc_ei_lifecycle_schema_version', SC_EI_LIFECYCLE_SCHEMA_VERSION, false );
			self::record_migration( $stored );
		}
		self::schedule();
	}

	public static function settings(): array {
		return wp_parse_args(
			get_option( 'sc_ei_settings', array() ),
			SC_EI_Lifecycle_Schema::default_settings()
		);
	}

	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::REMINDER_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', self::REMINDER_HOOK );
		}
	}

	public static function unschedule(): void {
		wp_clear_scheduled_hook( self::REMINDER_HOOK );
	}

	public static function backfill_defaults(): void {
		global $wpdb;
		$table = SC_EI_Database::table( 'inquiries' );
		$now = current_time( 'mysql', true );
		$wpdb->query(
			"UPDATE {$table}
			SET lifecycle_stage = CASE status
				WHEN 'new' THEN 'new_inquiry'
				WHEN 'under_review' THEN 'under_review'
				WHEN 'more_information_needed' THEN 'needs_information'
				WHEN 'fit_call_recommended' THEN 'meeting_requested'
				WHEN 'consultation_recommended' THEN 'qualified'
				WHEN 'proposal_requested' THEN 'proposal_preparation'
				WHEN 'proposal_sent' THEN 'proposal_sent'
				WHEN 'accepted' THEN 'accepted'
				WHEN 'not_a_fit' THEN 'declined'
				WHEN 'referred' THEN 'declined'
				WHEN 'withdrawn' THEN 'archived'
				WHEN 'closed' THEN 'archived'
				ELSE 'new_inquiry'
			END
			WHERE lifecycle_stage IS NULL OR lifecycle_stage = ''" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				SET lifecycle_owner_user_id = assigned_user_id,
					lifecycle_priority = CASE WHEN review_priority IN ('low','normal','high','urgent') THEN review_priority ELSE 'normal' END,
					qualification_status = CASE WHEN fit_assessment_status = 'finalized' THEN 'complete' WHEN fit_assessment_status = 'in_progress' THEN 'in_progress' ELSE 'not_started' END,
					lifecycle_updated_at = COALESCE(updated_at, %s),
					lifecycle_version = CASE WHEN lifecycle_version IS NULL THEN 0 ELSE lifecycle_version END
				WHERE lifecycle_updated_at IS NULL", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$now
			)
		);
	}

	public static function transition( int $inquiry_id, string $to_stage, int $actor_user_id, array $context = array() ) {
		global $wpdb;
		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $inquiry ) {
			return new WP_Error( 'lifecycle_inquiry_not_found', __( 'The inquiry could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$from_stage = SC_EI_Lifecycle_Schema::sanitize_stage( (string) ( $inquiry['lifecycle_stage'] ?: SC_EI_Lifecycle_Schema::map_legacy_status( (string) $inquiry['status'] ) ) );
		$to_stage = SC_EI_Lifecycle_Schema::sanitize_stage( $to_stage );
		if ( ! SC_EI_Lifecycle_Schema::can_transition( $from_stage, $to_stage ) ) {
			return new WP_Error( 'lifecycle_transition_not_allowed', __( 'That lifecycle transition is not allowed from the current stage.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$settings = self::settings();
		$reason = sanitize_textarea_field( (string) ( $context['reason'] ?? '' ) );
		if ( ! empty( $settings['lifecycle_require_transition_reason'] ) && $from_stage !== $to_stage && '' === trim( $reason ) ) {
			return new WP_Error( 'lifecycle_transition_reason_required', __( 'Record a transition reason before changing the lifecycle stage.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( ! empty( $settings['lifecycle_require_owner_for_qualified'] ) && in_array( $to_stage, array( 'qualified', 'meeting_requested', 'meeting_scheduled', 'proposal_preparation', 'proposal_sent', 'accepted', 'active_engagement' ), true ) && empty( $inquiry['lifecycle_owner_user_id'] ) && empty( $inquiry['assigned_user_id'] ) ) {
			return new WP_Error( 'lifecycle_owner_required', __( 'Assign an internal lifecycle owner before advancing this inquiry.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$now = current_time( 'mysql', true );
		$data = array(
			'lifecycle_stage'      => $to_stage,
			'status'               => SC_EI_Lifecycle_Schema::legacy_status_for_stage( $to_stage ),
			'lifecycle_updated_at' => $now,
			'lifecycle_updated_by' => $actor_user_id ?: null,
			'lifecycle_version'    => absint( $inquiry['lifecycle_version'] ?? 0 ) + 1,
			'updated_at'           => $now,
		);
		$transition_next_action = mb_substr( sanitize_text_field( (string) ( $context['next_action'] ?? '' ) ), 0, 255 );
		if ( '' !== $transition_next_action ) {
			$data['next_action'] = $transition_next_action;
		}
		if ( in_array( $to_stage, array( 'completed', 'declined', 'archived' ), true ) ) {
			$data['closed_at'] = $now;
		} elseif ( ! empty( $inquiry['closed_at'] ) ) {
			$data['closed_at'] = null;
		}

		$table = SC_EI_Database::table( 'inquiries' );
		$wpdb->query( 'START TRANSACTION' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$updated = $wpdb->update(
			$table,
			$data,
			array( 'id' => $inquiry_id, 'lifecycle_version' => absint( $inquiry['lifecycle_version'] ?? 0 ) ),
			self::formats( $data, array( 'lifecycle_updated_by', 'lifecycle_version' ) ),
			array( '%d', '%d' )
		);
		if ( 1 !== $updated ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			return new WP_Error( 'lifecycle_concurrent_update', __( 'The lifecycle record changed while you were editing it. Reload and try again.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$event_id = self::insert_event(
			$inquiry_id,
			'lifecycle_stage_changed',
			$from_stage,
			$to_stage,
			$actor_user_id,
			array(
				'reason' => $reason,
				'next_action' => $transition_next_action,
				'sender_visible' => ! empty( $context['sender_visible'] ),
			)
		);
		if ( ! $event_id ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			return new WP_Error( 'lifecycle_event_failed', __( 'The transition audit event could not be recorded.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		SC_EI_Audit_Log::record(
			'lifecycle_stage_changed',
			'Advisory lifecycle stage changed through an authorized human action.',
			array( 'from_stage' => $from_stage, 'to_stage' => $to_stage, 'reason' => $reason, 'automatic' => false ),
			$inquiry_id,
			null,
			$actor_user_id
		);
		do_action( 'sc_ei_lifecycle_transitioned', $inquiry_id, $from_stage, $to_stage, $actor_user_id, $context );
		return SC_EI_Inquiry_Repository::find( $inquiry_id );
	}

	public static function update_workspace( int $inquiry_id, array $input, int $actor_user_id ) {
		global $wpdb;
		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $inquiry ) {
			return new WP_Error( 'lifecycle_inquiry_not_found', __( 'The inquiry could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		$next_at = self::sanitize_datetime( (string) ( $input['next_action_at'] ?? '' ) );
		$data = array(
			'lifecycle_owner_user_id' => absint( $input['lifecycle_owner_user_id'] ?? 0 ) ?: null,
			'assigned_user_id'        => absint( $input['lifecycle_owner_user_id'] ?? 0 ) ?: null,
			'lifecycle_priority'      => SC_EI_Lifecycle_Schema::sanitize_priority( (string) ( $input['lifecycle_priority'] ?? 'normal' ) ),
			'next_action'             => mb_substr( sanitize_text_field( (string) ( $input['next_action'] ?? '' ) ), 0, 255 ),
			'next_action_at'          => $next_at,
			'next_follow_up_at'       => $next_at,
			'sender_lifecycle_summary'=> sanitize_textarea_field( (string) ( $input['sender_lifecycle_summary'] ?? '' ) ),
			'lifecycle_updated_at'    => $now,
			'lifecycle_updated_by'    => $actor_user_id ?: null,
			'lifecycle_version'       => absint( $inquiry['lifecycle_version'] ?? 0 ) + 1,
			'updated_at'              => $now,
		);
		$ok = $wpdb->update(
			SC_EI_Database::table( 'inquiries' ),
			$data,
			array( 'id' => $inquiry_id, 'lifecycle_version' => absint( $inquiry['lifecycle_version'] ?? 0 ) ),
			self::formats( $data, array( 'lifecycle_owner_user_id', 'assigned_user_id', 'lifecycle_updated_by', 'lifecycle_version' ) ),
			array( '%d', '%d' )
		);
		if ( 1 !== $ok ) {
			return new WP_Error( 'lifecycle_concurrent_update', __( 'The lifecycle record changed while you were editing it. Reload and try again.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::insert_event( $inquiry_id, 'lifecycle_workspace_updated', (string) $inquiry['lifecycle_stage'], (string) $inquiry['lifecycle_stage'], $actor_user_id, array( 'next_action_at' => $next_at, 'owner_user_id' => $data['lifecycle_owner_user_id'], 'priority' => $data['lifecycle_priority'] ) );
		SC_EI_Audit_Log::record( 'lifecycle_workspace_updated', 'Advisory lifecycle ownership, priority, next action, or sender summary updated.', array( 'next_action_at' => $next_at, 'priority' => $data['lifecycle_priority'] ), $inquiry_id, null, $actor_user_id );
		return SC_EI_Inquiry_Repository::find( $inquiry_id );
	}

	public static function update_qualification( int $inquiry_id, array $input, int $actor_user_id ) {
		global $wpdb;
		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $inquiry ) {
			return new WP_Error( 'lifecycle_inquiry_not_found', __( 'The inquiry could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$authority = sanitize_key( (string) ( $input['decision_authority'] ?? 'unknown' ) );
		$authority = isset( SC_EI_Lifecycle_Schema::decision_authority_options()[ $authority ] ) ? $authority : 'unknown';
		$funding = sanitize_key( (string) ( $input['funding_status'] ?? 'unknown' ) );
		$funding = isset( SC_EI_Lifecycle_Schema::funding_statuses()[ $funding ] ) ? $funding : 'unknown';
		$ai = sanitize_key( (string) ( $input['ai_assurance_applicable'] ?? 'not_assessed' ) );
		$ai = isset( SC_EI_Lifecycle_Schema::assessment_options()[ $ai ] ) ? $ai : 'not_assessed';
		$teams = sanitize_key( (string) ( $input['teams_readiness'] ?? 'not_assessed' ) );
		$teams = isset( SC_EI_Lifecycle_Schema::teams_readiness_options()[ $teams ] ) ? $teams : 'not_assessed';
		$score = max( 0, min( 100, absint( $input['qualification_score'] ?? 0 ) ) );
		$qualification = array(
			'organizational_challenge' => sanitize_textarea_field( (string) ( $input['organizational_challenge'] ?? '' ) ),
			'desired_outcome'          => sanitize_textarea_field( (string) ( $input['desired_outcome'] ?? '' ) ),
			'current_systems'          => sanitize_textarea_field( (string) ( $input['current_systems'] ?? '' ) ),
			'constraints'              => sanitize_textarea_field( (string) ( $input['constraints'] ?? '' ) ),
			'timeline_context'         => sanitize_textarea_field( (string) ( $input['timeline_context'] ?? '' ) ),
			'privacy_security'         => sanitize_textarea_field( (string) ( $input['privacy_security'] ?? '' ) ),
			'stakeholders'             => sanitize_textarea_field( (string) ( $input['stakeholders'] ?? '' ) ),
			'qualification_rationale'  => sanitize_textarea_field( (string) ( $input['qualification_rationale'] ?? '' ) ),
		);
		$now = current_time( 'mysql', true );
		$data = array(
			'qualification_status'      => SC_EI_Lifecycle_Schema::sanitize_qualification_status( (string) ( $input['qualification_status'] ?? 'not_started' ) ),
			'qualification_score'       => $score,
			'qualification_json'        => wp_json_encode( $qualification, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
			'decision_authority'        => $authority,
			'funding_status'            => $funding,
			'stakeholder_summary'       => $qualification['stakeholders'],
			'systems_constraints'       => trim( $qualification['current_systems'] . "\n\n" . $qualification['constraints'] ),
			'data_security_requirements'=> $qualification['privacy_security'],
			'ai_assurance_applicable'   => $ai,
			'teams_readiness'           => $teams,
			'lifecycle_updated_at'      => $now,
			'lifecycle_updated_by'      => $actor_user_id ?: null,
			'lifecycle_version'         => absint( $inquiry['lifecycle_version'] ?? 0 ) + 1,
			'updated_at'                => $now,
		);
		$ok = $wpdb->update( SC_EI_Database::table( 'inquiries' ), $data, array( 'id' => $inquiry_id, 'lifecycle_version' => absint( $inquiry['lifecycle_version'] ?? 0 ) ), self::formats( $data, array( 'qualification_score', 'lifecycle_updated_by', 'lifecycle_version' ) ), array( '%d', '%d' ) );
		if ( 1 !== $ok ) {
			return new WP_Error( 'lifecycle_concurrent_update', __( 'The lifecycle record changed while you were editing it. Reload and try again.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::insert_event( $inquiry_id, 'qualification_updated', (string) $inquiry['lifecycle_stage'], (string) $inquiry['lifecycle_stage'], $actor_user_id, array( 'status' => $data['qualification_status'], 'score' => $score, 'decision_authority' => $authority, 'funding_status' => $funding, 'ai_assurance_applicable' => $ai, 'teams_readiness' => $teams ) );
		SC_EI_Audit_Log::record( 'lifecycle_qualification_updated', 'Structured advisory qualification record updated without automatic rejection.', array( 'status' => $data['qualification_status'], 'score' => $score, 'automatic_decision' => false ), $inquiry_id, null, $actor_user_id );
		return SC_EI_Inquiry_Repository::find( $inquiry_id );
	}

	public static function add_note( int $inquiry_id, string $body, string $note_type, int $actor_user_id, bool $is_sensitive = false ) {
		global $wpdb;
		if ( ! SC_EI_Inquiry_Repository::find( $inquiry_id ) ) {
			return new WP_Error( 'lifecycle_inquiry_not_found', __( 'The inquiry could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$body = sanitize_textarea_field( $body );
		if ( '' === trim( $body ) ) {
			return new WP_Error( 'lifecycle_note_required', __( 'Enter an internal note.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$note_type = sanitize_key( $note_type );
		if ( ! isset( SC_EI_Lifecycle_Schema::note_types()[ $note_type ] ) ) {
			$note_type = 'internal';
		}
		$now = current_time( 'mysql', true );
		$data = array(
			'public_id'    => wp_generate_uuid4(),
			'inquiry_id'   => $inquiry_id,
			'note_type'    => $note_type,
			'note_body'    => $body,
			'is_sensitive' => $is_sensitive ? 1 : 0,
			'created_by'   => $actor_user_id ?: null,
			'created_at'   => $now,
			'updated_at'   => $now,
		);
		$ok = $wpdb->insert( SC_EI_Database::table( 'lifecycle_notes' ), $data, self::formats( $data, array( 'inquiry_id', 'is_sensitive', 'created_by' ) ) );
		if ( false === $ok ) {
			return new WP_Error( 'lifecycle_note_save_failed', __( 'The internal lifecycle note could not be saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::insert_event( $inquiry_id, 'internal_note_added', '', '', $actor_user_id, array( 'note_id' => (int) $wpdb->insert_id, 'note_type' => $note_type, 'is_sensitive' => $is_sensitive, 'sender_visible' => false ) );
		SC_EI_Audit_Log::record( 'lifecycle_internal_note_added', 'Private lifecycle note added. The note is excluded from the Sender Portal.', array( 'note_type' => $note_type, 'is_sensitive' => $is_sensitive, 'sender_visible' => false ), $inquiry_id, null, $actor_user_id );
		return self::find_note( (int) $wpdb->insert_id );
	}

	public static function notes( int $inquiry_id, int $limit = 200 ): array {
		global $wpdb;
		$table = SC_EI_Database::table( 'lifecycle_notes' );
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE inquiry_id = %d ORDER BY created_at DESC, id DESC LIMIT %d", $inquiry_id, max( 1, min( 1000, $limit ) ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function find_note( int $note_id ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'lifecycle_notes' );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $note_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $row ?: null;
	}

	public static function add_task( int $inquiry_id, array $input, int $actor_user_id ) {
		global $wpdb;
		if ( ! SC_EI_Inquiry_Repository::find( $inquiry_id ) ) {
			return new WP_Error( 'lifecycle_inquiry_not_found', __( 'The inquiry could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$title = mb_substr( sanitize_text_field( (string) ( $input['title'] ?? '' ) ), 0, 255 );
		if ( '' === trim( $title ) ) {
			return new WP_Error( 'lifecycle_task_title_required', __( 'Enter a task title.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		$data = array(
			'public_id'       => wp_generate_uuid4(),
			'inquiry_id'      => $inquiry_id,
			'task_title'      => $title,
			'task_details'    => sanitize_textarea_field( (string) ( $input['details'] ?? '' ) ),
			'task_status'     => 'open',
			'priority'        => SC_EI_Lifecycle_Schema::sanitize_priority( (string) ( $input['priority'] ?? 'normal' ) ),
			'due_at'          => self::sanitize_datetime( (string) ( $input['due_at'] ?? '' ) ),
			'assigned_user_id'=> absint( $input['assigned_user_id'] ?? 0 ) ?: null,
			'reminder_policy' => sanitize_key( (string) ( $input['reminder_policy'] ?? 'daily_when_due' ) ),
			'last_reminded_at'=> null,
			'completed_at'    => null,
			'completed_by'    => null,
			'created_by'      => $actor_user_id ?: null,
			'created_at'      => $now,
			'updated_at'      => $now,
		);
		$ok = $wpdb->insert( SC_EI_Database::table( 'lifecycle_tasks' ), $data, self::formats( $data, array( 'inquiry_id', 'assigned_user_id', 'completed_by', 'created_by' ) ) );
		if ( false === $ok ) {
			return new WP_Error( 'lifecycle_task_save_failed', __( 'The lifecycle task could not be saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::insert_event( $inquiry_id, 'lifecycle_task_created', '', '', $actor_user_id, array( 'task_id' => (int) $wpdb->insert_id, 'due_at' => $data['due_at'], 'assigned_user_id' => $data['assigned_user_id'] ) );
		SC_EI_Audit_Log::record( 'lifecycle_task_created', 'Lifecycle follow-up task created.', array( 'task_id' => (int) $wpdb->insert_id, 'due_at' => $data['due_at'] ), $inquiry_id, null, $actor_user_id );
		return self::find_task( (int) $wpdb->insert_id );
	}

	public static function update_task( int $task_id, array $input, int $actor_user_id ) {
		global $wpdb;
		$task = self::find_task( $task_id );
		if ( ! $task ) {
			return new WP_Error( 'lifecycle_task_not_found', __( 'The lifecycle task could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$status = SC_EI_Lifecycle_Schema::sanitize_task_status( (string) ( $input['task_status'] ?? $task['task_status'] ) );
		$now = current_time( 'mysql', true );
		$data = array(
			'task_status'      => $status,
			'priority'         => SC_EI_Lifecycle_Schema::sanitize_priority( (string) ( $input['priority'] ?? $task['priority'] ) ),
			'due_at'           => self::sanitize_datetime( (string) ( $input['due_at'] ?? $task['due_at'] ) ),
			'assigned_user_id' => absint( $input['assigned_user_id'] ?? $task['assigned_user_id'] ) ?: null,
			'updated_at'       => $now,
			'completed_at'     => 'completed' === $status ? ( $task['completed_at'] ?: $now ) : null,
			'completed_by'     => 'completed' === $status ? ( $actor_user_id ?: null ) : null,
		);
		$ok = $wpdb->update( SC_EI_Database::table( 'lifecycle_tasks' ), $data, array( 'id' => $task_id ), self::formats( $data, array( 'assigned_user_id', 'completed_by' ) ), array( '%d' ) );
		if ( false === $ok ) {
			return new WP_Error( 'lifecycle_task_update_failed', __( 'The lifecycle task could not be updated.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::insert_event( absint( $task['inquiry_id'] ), 'lifecycle_task_updated', '', '', $actor_user_id, array( 'task_id' => $task_id, 'from_status' => $task['task_status'], 'to_status' => $status ) );
		SC_EI_Audit_Log::record( 'lifecycle_task_updated', 'Lifecycle task status, due date, priority, or assignment updated.', array( 'task_id' => $task_id, 'from_status' => $task['task_status'], 'to_status' => $status ), absint( $task['inquiry_id'] ), null, $actor_user_id );
		return self::find_task( $task_id );
	}

	public static function tasks( int $inquiry_id, bool $include_closed = true ): array {
		global $wpdb;
		$table = SC_EI_Database::table( 'lifecycle_tasks' );
		$where = $include_closed ? '' : " AND task_status IN ('open','in_progress')";
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE inquiry_id = %d {$where} ORDER BY CASE task_status WHEN 'open' THEN 1 WHEN 'in_progress' THEN 2 ELSE 3 END, due_at IS NULL, due_at ASC, id DESC", $inquiry_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function find_task( int $task_id ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'lifecycle_tasks' );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $task_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $row ?: null;
	}

	public static function events( int $inquiry_id, int $limit = 300 ): array {
		global $wpdb;
		$table = SC_EI_Database::table( 'lifecycle_events' );
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE inquiry_id = %d ORDER BY occurred_at DESC, id DESC LIMIT %d", $inquiry_id, max( 1, min( 1000, $limit ) ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function query( array $args = array() ): array {
		global $wpdb;
		$args = wp_parse_args( $args, array( 'stage' => '', 'owner_user_id' => 0, 'priority' => '', 'search' => '', 'limit' => 200 ) );
		$table = SC_EI_Database::table( 'inquiries' );
		$where = array( '1=1' );
		$params = array();
		if ( $args['stage'] && isset( SC_EI_Lifecycle_Schema::stages()[ sanitize_key( $args['stage'] ) ] ) ) {
			$where[] = 'lifecycle_stage = %s';
			$params[] = sanitize_key( $args['stage'] );
		}
		if ( absint( $args['owner_user_id'] ) ) {
			$where[] = 'lifecycle_owner_user_id = %d';
			$params[] = absint( $args['owner_user_id'] );
		}
		if ( $args['priority'] && isset( SC_EI_Lifecycle_Schema::priorities()[ sanitize_key( $args['priority'] ) ] ) ) {
			$where[] = 'lifecycle_priority = %s';
			$params[] = sanitize_key( $args['priority'] );
		}
		if ( $args['search'] ) {
			$like = '%' . $wpdb->esc_like( sanitize_text_field( (string) $args['search'] ) ) . '%';
			$where[] = '(reference LIKE %s OR contact_name LIKE %s OR contact_email LIKE %s OR organization LIKE %s OR subject LIKE %s OR next_action LIKE %s)';
			$params = array_merge( $params, array_fill( 0, 6, $like ) );
		}
		$limit = max( 1, min( 1000, absint( $args['limit'] ) ) );
		$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . " ORDER BY CASE lifecycle_priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 ELSE 4 END, next_action_at IS NULL, next_action_at ASC, updated_at DESC LIMIT %d"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$params[] = $limit;
		return (array) $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	public static function metrics(): array {
		global $wpdb;
		$inquiries = SC_EI_Database::table( 'inquiries' );
		$tasks = SC_EI_Database::table( 'lifecycle_tasks' );
		$events = SC_EI_Database::table( 'lifecycle_events' );
		$now = current_time( 'mysql', true );
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS );
		$stage_rows = (array) $wpdb->get_results( "SELECT lifecycle_stage, COUNT(*) AS total FROM {$inquiries} GROUP BY lifecycle_stage", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$source_rows = (array) $wpdb->get_results( $wpdb->prepare( "SELECT source_page, COUNT(*) AS total FROM {$inquiries} WHERE created_at >= %s GROUP BY source_page ORDER BY total DESC LIMIT 20", $cutoff ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$service_rows = (array) $wpdb->get_results( $wpdb->prepare( "SELECT service_interest, COUNT(*) AS total FROM {$inquiries} WHERE created_at >= %s GROUP BY service_interest ORDER BY total DESC LIMIT 20", $cutoff ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$by_stage = array_fill_keys( array_keys( SC_EI_Lifecycle_Schema::stages() ), 0 );
		foreach ( $stage_rows as $row ) {
			$key = SC_EI_Lifecycle_Schema::sanitize_stage( (string) $row['lifecycle_stage'] );
			$by_stage[ $key ] = absint( $row['total'] );
		}
		$total = array_sum( $by_stage );
		$qualified = absint( $by_stage['qualified'] ) + absint( $by_stage['meeting_requested'] ) + absint( $by_stage['meeting_scheduled'] ) + absint( $by_stage['proposal_preparation'] ) + absint( $by_stage['proposal_sent'] ) + absint( $by_stage['accepted'] ) + absint( $by_stage['active_engagement'] ) + absint( $by_stage['completed'] );
		$proposal_count = absint( $by_stage['proposal_sent'] ) + absint( $by_stage['accepted'] ) + absint( $by_stage['active_engagement'] ) + absint( $by_stage['completed'] );
		$accepted_count = absint( $by_stage['accepted'] ) + absint( $by_stage['active_engagement'] ) + absint( $by_stage['completed'] );
		$avg_response_hours = (float) $wpdb->get_var(
			"SELECT AVG(TIMESTAMPDIFF(SECOND, i.created_at, first_event.first_at))/3600
			FROM {$inquiries} i
			INNER JOIN (SELECT inquiry_id, MIN(occurred_at) AS first_at FROM {$events} WHERE event_type = 'lifecycle_stage_changed' AND to_stage = 'under_review' GROUP BY inquiry_id) first_event ON first_event.inquiry_id = i.id" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
		return array(
			'by_stage' => $by_stage,
			'total' => $total,
			'created_last_30_days' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$inquiries} WHERE created_at >= %s", $cutoff ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'qualified' => $qualified,
			'qualification_rate' => $total ? round( 100 * $qualified / $total, 1 ) : 0,
			'proposal_count' => $proposal_count,
			'proposal_rate' => $qualified ? round( 100 * $proposal_count / $qualified, 1 ) : 0,
			'accepted_count' => $accepted_count,
			'acceptance_rate' => $proposal_count ? round( 100 * $accepted_count / $proposal_count, 1 ) : 0,
			'completed_count' => absint( $by_stage['completed'] ),
			'active_engagements' => absint( $by_stage['active_engagement'] ),
			'average_first_review_hours' => round( max( 0, $avg_response_hours ), 1 ),
			'by_source_last_30_days' => array_map( static fn( array $row ): array => array( 'source' => sanitize_key( (string) ( $row['source_page'] ?: 'unspecified' ) ), 'total' => absint( $row['total'] ) ), $source_rows ),
			'by_service_last_30_days' => array_map( static fn( array $row ): array => array( 'service' => sanitize_key( (string) ( $row['service_interest'] ?: 'unspecified' ) ), 'total' => absint( $row['total'] ) ), $service_rows ),
			'open_tasks' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tasks} WHERE task_status IN ('open','in_progress')" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'overdue_tasks' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$tasks} WHERE task_status IN ('open','in_progress') AND due_at IS NOT NULL AND due_at < %s", $now ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'unassigned' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$inquiries} WHERE lifecycle_stage NOT IN ('completed','declined','archived') AND (lifecycle_owner_user_id IS NULL OR lifecycle_owner_user_id = 0)" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'next_actions_due' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$inquiries} WHERE lifecycle_stage NOT IN ('completed','declined','archived') AND next_action_at IS NOT NULL AND next_action_at < %s", $now ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	public static function sender_snapshot( int $inquiry_id ): array {
		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $inquiry ) {
			return array();
		}
		$stage = SC_EI_Lifecycle_Schema::sanitize_stage( (string) ( $inquiry['lifecycle_stage'] ?: SC_EI_Lifecycle_Schema::map_legacy_status( (string) $inquiry['status'] ) ) );
		return array(
			'stage' => $stage,
			'label' => SC_EI_Lifecycle_Schema::public_stage_labels()[ $stage ] ?? __( 'Under Review', 'sustainable-catalyst-engagement-intake' ),
			'summary' => sanitize_textarea_field( (string) ( $inquiry['sender_lifecycle_summary'] ?? '' ) ),
			'next_step' => mb_substr( sanitize_text_field( (string) ( $inquiry['next_action'] ?? '' ) ), 0, 255 ),
			'updated_at' => (string) ( $inquiry['lifecycle_updated_at'] ?? $inquiry['updated_at'] ?? '' ),
		);
	}

	public static function export_for_inquiry( int $inquiry_id ): array {
		return array(
			'events' => self::events( $inquiry_id, 1000 ),
			'notes'  => self::notes( $inquiry_id, 1000 ),
			'tasks'  => self::tasks( $inquiry_id, true ),
		);
	}

	public static function redact_for_privacy( int $inquiry_id, string $now ): bool {
		global $wpdb;
		$marker = wp_json_encode( array( 'personal_data_erased' => true, 'lifecycle_schema_version' => SC_EI_LIFECYCLE_SCHEMA_VERSION ), JSON_UNESCAPED_SLASHES );
		$events = $wpdb->query( $wpdb->prepare( "UPDATE " . SC_EI_Database::table( 'lifecycle_events' ) . " SET payload_json = %s WHERE inquiry_id = %d", $marker, $inquiry_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$notes = $wpdb->query( $wpdb->prepare( "UPDATE " . SC_EI_Database::table( 'lifecycle_notes' ) . " SET note_body = %s, is_sensitive = 0, updated_at = %s WHERE inquiry_id = %d", '[Lifecycle note content erased through Privacy and Retention Center.]', $now, $inquiry_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$tasks = $wpdb->query( $wpdb->prepare( "UPDATE " . SC_EI_Database::table( 'lifecycle_tasks' ) . " SET task_title = %s, task_details = '', updated_at = %s WHERE inquiry_id = %d", '[Lifecycle task content erased]', $now, $inquiry_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return false !== $events && false !== $notes && false !== $tasks;
	}

	public static function process_due_tasks(): void {
		global $wpdb;
		$settings = self::settings();
		if ( empty( $settings['lifecycle_enabled'] ) || empty( $settings['lifecycle_task_reminders_enabled'] ) ) {
			return;
		}
		if ( ! add_option( 'sc_ei_lifecycle_reminder_lock', current_time( 'mysql', true ), '', false ) ) {
			return;
		}
		try {
			$table = SC_EI_Database::table( 'lifecycle_tasks' );
			$now = current_time( 'mysql', true );
			$day_start = gmdate( 'Y-m-d 00:00:00' );
			$limit = max( 1, min( 250, absint( $settings['lifecycle_task_batch_limit'] ?? 50 ) ) );
			$rows = (array) $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table}
					WHERE task_status IN ('open','in_progress')
						AND due_at IS NOT NULL
						AND due_at <= %s
						AND (last_reminded_at IS NULL OR last_reminded_at < %s)
					ORDER BY due_at ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$now,
					$day_start,
					$limit
				),
				ARRAY_A
			);
			foreach ( $rows as $task ) {
				$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET last_reminded_at = %s, updated_at = %s WHERE id = %d AND (last_reminded_at IS NULL OR last_reminded_at < %s)", $now, $now, absint( $task['id'] ), $day_start ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				if ( 1 !== $updated ) {
					continue;
				}
				self::insert_event( absint( $task['inquiry_id'] ), 'lifecycle_task_reminder_recorded', '', '', 0, array( 'task_id' => absint( $task['id'] ), 'due_at' => $task['due_at'], 'idempotency_day' => gmdate( 'Y-m-d' ) ) );
				do_action( 'sc_ei_lifecycle_task_due', $task, SC_EI_Inquiry_Repository::find( absint( $task['inquiry_id'] ) ) );
			}
			update_option( 'sc_ei_last_lifecycle_reminder_run', $now, false );
		} finally {
			delete_option( 'sc_ei_lifecycle_reminder_lock' );
		}
	}

	public static function record_migration( string $from_schema = '' ) {
		global $wpdb;
		$table = SC_EI_Database::table( 'platform_migrations' );
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE migration_key = %s LIMIT 1", self::MIGRATION_KEY ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $existing && 'completed' === (string) $existing['status'] ) {
			return $existing;
		}
		$now = current_time( 'mysql', true );
		$data = array(
			'public_id' => $existing['public_id'] ?? wp_generate_uuid4(),
			'migration_key' => self::MIGRATION_KEY,
			'from_version' => sanitize_text_field( $from_schema ),
			'to_version' => SC_EI_LIFECYCLE_SCHEMA_VERSION,
			'status' => 'completed',
			'schema_hash' => hash( 'sha256', wp_json_encode( array( 'lifecycle' => SC_EI_LIFECYCLE_SCHEMA_VERSION, 'database' => SC_EI_DB_VERSION, 'plugin' => SC_EI_VERSION ) ) ),
			'context_json' => wp_json_encode( array( 'release' => 'Advisory Operations and Engagement Lifecycle', 'database_schema_changed' => true, 'destructive' => false, 'backfill_preserves_inquiries' => true, 'internal_notes_sender_visible' => false, 'automatic_rejection' => false, 'automatic_commitment' => false ), JSON_UNESCAPED_SLASHES ),
			'started_at' => $existing['started_at'] ?? $now,
			'completed_at' => $now,
			'error_code' => '',
			'error_message' => '',
			'created_at' => $existing['created_at'] ?? $now,
			'updated_at' => $now,
		);
		if ( $existing ) {
			$ok = $wpdb->update( $table, $data, array( 'id' => absint( $existing['id'] ) ), self::formats( $data, array() ), array( '%d' ) );
			$id = absint( $existing['id'] );
		} else {
			$ok = $wpdb->insert( $table, $data, self::formats( $data, array() ) );
			$id = (int) $wpdb->insert_id;
		}
		if ( false === $ok ) {
			return new WP_Error( 'lifecycle_migration_journal_failed', __( 'The v1.1.0 lifecycle migration journal could not be recorded.', 'sustainable-catalyst-engagement-intake' ) );
		}
		SC_EI_Audit_Log::record( 'lifecycle_migration_completed', 'Advisory lifecycle schema and nondestructive inquiry backfill completed.', array( 'migration_key' => self::MIGRATION_KEY, 'from_schema' => $from_schema, 'to_schema' => SC_EI_LIFECYCLE_SCHEMA_VERSION ), null, null, get_current_user_id() );
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	private static function insert_event( int $inquiry_id, string $event_type, string $from_stage, string $to_stage, int $actor_user_id, array $payload ): int {
		global $wpdb;
		$data = array(
			'public_id'    => wp_generate_uuid4(),
			'inquiry_id'   => $inquiry_id,
			'event_type'   => sanitize_key( $event_type ),
			'from_stage'   => $from_stage ? SC_EI_Lifecycle_Schema::sanitize_stage( $from_stage ) : '',
			'to_stage'     => $to_stage ? SC_EI_Lifecycle_Schema::sanitize_stage( $to_stage ) : '',
			'actor_user_id'=> $actor_user_id ?: null,
			'payload_json' => wp_json_encode( self::sanitize_context( $payload ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
			'occurred_at'  => current_time( 'mysql', true ),
		);
		$ok = $wpdb->insert( SC_EI_Database::table( 'lifecycle_events' ), $data, self::formats( $data, array( 'inquiry_id', 'actor_user_id' ) ) );
		return false === $ok ? 0 : (int) $wpdb->insert_id;
	}

	private static function sanitize_context( array $context ): array {
		$result = array();
		foreach ( $context as $key => $value ) {
			$key = sanitize_key( (string) $key );
			if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) || null === $value ) {
				$result[ $key ] = $value;
			} elseif ( is_array( $value ) ) {
				$result[ $key ] = self::sanitize_context( $value );
			} else {
				$result[ $key ] = mb_substr( sanitize_textarea_field( (string) $value ), 0, 5000 );
			}
		}
		return $result;
	}

	private static function sanitize_datetime( string $value ): ?string {
		$value = trim( $value );
		if ( '' === $value ) {
			return null;
		}
		try {
			$date = new DateTimeImmutable( $value, wp_timezone() );
			return $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
		} catch ( Throwable $exception ) {
			return null;
		}
	}

	private static function formats( array $data, array $integer_fields ): array {
		return array_map( static fn( string $field ): string => in_array( $field, $integer_fields, true ) ? '%d' : '%s', array_keys( $data ) );
	}
}
