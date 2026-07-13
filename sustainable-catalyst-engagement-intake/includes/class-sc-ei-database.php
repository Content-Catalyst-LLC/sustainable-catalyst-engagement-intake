<?php
/**
 * Database schema and migrations.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Database {

	public static function table( string $name ): string {
		global $wpdb;

		$allowed = array( 'inquiries', 'attachments', 'reviews', 'communications', 'communication_events', 'communication_templates', 'privacy_requests', 'consent_events', 'legal_holds', 'retention_policies', 'retention_actions', 'audit_log' );
		if ( ! in_array( $name, $allowed, true ) ) {
			throw new InvalidArgumentException( 'Unknown Engagement Intake table.' );
		}

		return $wpdb->prefix . 'sc_ei_' . $name;
	}

	public static function install(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$inquiries      = self::table( 'inquiries' );
		$attachments    = self::table( 'attachments' );
		$reviews        = self::table( 'reviews' );
		$communications = self::table( 'communications' );
		$communication_events = self::table( 'communication_events' );
		$communication_templates = self::table( 'communication_templates' );
		$privacy_requests = self::table( 'privacy_requests' );
		$consent_events = self::table( 'consent_events' );
		$legal_holds = self::table( 'legal_holds' );
		$retention_policies = self::table( 'retention_policies' );
		$retention_actions = self::table( 'retention_actions' );
		$audit_log      = self::table( 'audit_log' );

		$sql_inquiries = "CREATE TABLE {$inquiries} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			reference varchar(40) NOT NULL,
			inquiry_type varchar(80) NOT NULL DEFAULT 'general',
			status varchar(80) NOT NULL DEFAULT 'new',
			form_variant varchar(40) NOT NULL DEFAULT 'advanced',
			source_page varchar(80) NOT NULL DEFAULT 'other',
			entry_cta varchar(120) NOT NULL DEFAULT 'unspecified',
			conversion_route varchar(120) NOT NULL DEFAULT '',
			guidance_flags longtext NULL,
			contact_name varchar(191) NOT NULL DEFAULT '',
			contact_email varchar(191) NOT NULL DEFAULT '',
			organization varchar(191) NOT NULL DEFAULT '',
			role_title varchar(191) NOT NULL DEFAULT '',
			subject varchar(255) NOT NULL DEFAULT '',
			message longtext NULL,
			project_summary longtext NULL,
			desired_outcome longtext NULL,
			service_interest varchar(120) NOT NULL DEFAULT '',
			budget_range varchar(120) NOT NULL DEFAULT '',
			desired_start_date date NULL,
			deadline_date date NULL,
			preferred_contact_method varchar(40) NOT NULL DEFAULT 'email',
			teams_email varchar(191) NOT NULL DEFAULT '',
			phone_number varchar(80) NOT NULL DEFAULT '',
			timezone varchar(120) NOT NULL DEFAULT '',
			city varchar(120) NOT NULL DEFAULT '',
			country varchar(120) NOT NULL DEFAULT '',
			meeting_request varchar(20) NOT NULL DEFAULT 'no',
			preferred_weekdays varchar(255) NOT NULL DEFAULT '[]',
			preferred_time_windows longtext NULL,
			preferred_duration smallint(5) unsigned NOT NULL DEFAULT 0,
			participant_count smallint(5) unsigned NOT NULL DEFAULT 1,
			participant_emails longtext NULL,
			accessibility_needs longtext NULL,
			calendar_invite_consent tinyint(1) unsigned NOT NULL DEFAULT 0,
			scheduling_notes longtext NULL,
			scheduling_status varchar(40) NOT NULL DEFAULT 'not_requested',
			teams_meeting_url text NULL,
			scheduled_start_utc datetime NULL,
			scheduled_end_utc datetime NULL,
			scheduled_timezone varchar(120) NOT NULL DEFAULT '',
			calendar_event_id varchar(191) NOT NULL DEFAULT '',
			relevant_links longtext NULL,
			metadata_json longtext NULL,
			consent_version varchar(80) NOT NULL DEFAULT '',
			consent_at datetime NULL,
			assigned_user_id bigint(20) unsigned NULL,
			assignment_at datetime NULL,
			assignment_by bigint(20) unsigned NULL,
			review_stage varchar(40) NOT NULL DEFAULT 'intake',
			review_priority varchar(20) NOT NULL DEFAULT 'normal',
			review_due_at datetime NULL,
			fit_decision varchar(40) NOT NULL DEFAULT 'undecided',
			fit_confidence varchar(20) NOT NULL DEFAULT 'unassessed',
			risk_level varchar(20) NOT NULL DEFAULT 'unassessed',
			evidence_readiness varchar(20) NOT NULL DEFAULT 'not_assessed',
			scope_clarity varchar(20) NOT NULL DEFAULT 'not_assessed',
			recommended_next_step varchar(80) NOT NULL DEFAULT 'review',
			review_summary longtext NULL,
			decision_rationale longtext NULL,
			information_gaps longtext NULL,
			conflict_notes longtext NULL,
			review_checklist longtext NULL,
			escalation_status varchar(30) NOT NULL DEFAULT 'none',
			escalation_reason longtext NULL,
			review_started_at datetime NULL,
			last_reviewed_at datetime NULL,
			last_reviewed_by bigint(20) unsigned NULL,
			decision_at datetime NULL,
			review_completed_at datetime NULL,
			review_version int(10) unsigned NOT NULL DEFAULT 0,
			communication_status varchar(30) NOT NULL DEFAULT 'open',
			next_follow_up_at datetime NULL,
			last_communication_at datetime NULL,
			last_outbound_at datetime NULL,
			last_inbound_at datetime NULL,
			last_notification_at datetime NULL,
			communication_count int(10) unsigned NOT NULL DEFAULT 0,
			unread_inbound_count int(10) unsigned NOT NULL DEFAULT 0,
			do_not_email tinyint(1) unsigned NOT NULL DEFAULT 0,
			do_not_email_reason text NULL,
			communication_version int(10) unsigned NOT NULL DEFAULT 0,
			privacy_status varchar(30) NOT NULL DEFAULT 'active',
			retention_policy_key varchar(80) NOT NULL DEFAULT 'unaccepted_inquiry',
			retention_until datetime NULL,
			legal_hold_count int(10) unsigned NOT NULL DEFAULT 0,
			privacy_restriction_reason text NULL,
			last_privacy_review_at datetime NULL,
			last_privacy_review_by bigint(20) unsigned NULL,
			personal_data_erased_at datetime NULL,
			privacy_version int(10) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			closed_at datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY reference (reference),
			KEY status (status),
			KEY inquiry_type (inquiry_type),
			KEY form_variant (form_variant),
			KEY source_page (source_page),
			KEY conversion_route (conversion_route),
			KEY contact_email (contact_email),
			KEY preferred_contact_method (preferred_contact_method),
			KEY meeting_request (meeting_request),
			KEY scheduling_status (scheduling_status),
			KEY assigned_user_id (assigned_user_id),
			KEY assignment_by (assignment_by),
			KEY review_stage (review_stage),
			KEY review_priority (review_priority),
			KEY review_due_at (review_due_at),
			KEY fit_decision (fit_decision),
			KEY risk_level (risk_level),
			KEY escalation_status (escalation_status),
			KEY last_reviewed_by (last_reviewed_by),
			KEY last_reviewed_at (last_reviewed_at),
			KEY communication_status (communication_status),
			KEY next_follow_up_at (next_follow_up_at),
			KEY last_communication_at (last_communication_at),
			KEY do_not_email (do_not_email),
			KEY privacy_status (privacy_status),
			KEY retention_policy_key (retention_policy_key),
			KEY retention_until (retention_until),
			KEY legal_hold_count (legal_hold_count),
			KEY last_privacy_review_by (last_privacy_review_by),
			KEY created_at (created_at)
		) {$charset_collate};";

		$sql_attachments = "CREATE TABLE {$attachments} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			inquiry_id bigint(20) unsigned NOT NULL,
			public_id char(36) NOT NULL,
			original_name varchar(255) NOT NULL DEFAULT '',
			stored_name varchar(255) NOT NULL DEFAULT '',
			relative_path text NULL,
			mime_type varchar(191) NOT NULL DEFAULT '',
			detected_mime varchar(191) NOT NULL DEFAULT '',
			extension varchar(32) NOT NULL DEFAULT '',
			size_bytes bigint(20) unsigned NOT NULL DEFAULT 0,
			sha256 char(64) NOT NULL DEFAULT '',
			signature_type varchar(80) NOT NULL DEFAULT '',
			validator_version varchar(40) NOT NULL DEFAULT '',
			document_category varchar(80) NOT NULL DEFAULT 'other',
			document_notes text NULL,
			confidentiality varchar(40) NOT NULL DEFAULT 'non_confidential',
			quarantine_status varchar(80) NOT NULL DEFAULT 'quarantined',
			validation_status varchar(80) NOT NULL DEFAULT 'validated',
			scan_status varchar(80) NOT NULL DEFAULT 'not_configured',
			scanner_provider varchar(120) NOT NULL DEFAULT 'none',
			scan_message text NULL,
			scan_attempts int(10) unsigned NOT NULL DEFAULT 0,
			last_scanned_at datetime NULL,
			last_scanned_by bigint(20) unsigned NULL,
			integrity_status varchar(80) NOT NULL DEFAULT 'verified',
			storage_status varchar(40) NOT NULL DEFAULT 'unverified',
			last_verified_at datetime NULL,
			last_verified_by bigint(20) unsigned NULL,
			last_verification_source varchar(40) NOT NULL DEFAULT '',
			last_verification_message text NULL,
			retention_until datetime NULL,
			metadata_json longtext NULL,
			approved_by bigint(20) unsigned NULL,
			approved_at datetime NULL,
			rejected_by bigint(20) unsigned NULL,
			rejected_at datetime NULL,
			replacement_requested_at datetime NULL,
			deleted_by bigint(20) unsigned NULL,
			downloaded_count int(10) unsigned NOT NULL DEFAULT 0,
			last_downloaded_at datetime NULL,
			uploaded_at datetime NOT NULL,
			deleted_at datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			KEY inquiry_id (inquiry_id),
			KEY sha256 (sha256),
			KEY quarantine_status (quarantine_status),
			KEY validation_status (validation_status),
			KEY scan_status (scan_status),
			KEY last_scanned_at (last_scanned_at),
			KEY storage_status (storage_status),
			KEY retention_until (retention_until),
			KEY deleted_at (deleted_at)
		) {$charset_collate};";


		$sql_reviews = "CREATE TABLE {$reviews} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			inquiry_id bigint(20) unsigned NOT NULL,
			public_id char(36) NOT NULL,
			reviewer_user_id bigint(20) unsigned NULL,
			event_type varchar(40) NOT NULL DEFAULT 'review_saved',
			from_stage varchar(40) NOT NULL DEFAULT '',
			to_stage varchar(40) NOT NULL DEFAULT '',
			priority varchar(20) NOT NULL DEFAULT 'normal',
			fit_decision varchar(40) NOT NULL DEFAULT 'undecided',
			fit_confidence varchar(20) NOT NULL DEFAULT 'unassessed',
			risk_level varchar(20) NOT NULL DEFAULT 'unassessed',
			evidence_readiness varchar(20) NOT NULL DEFAULT 'not_assessed',
			scope_clarity varchar(20) NOT NULL DEFAULT 'not_assessed',
			recommended_next_step varchar(80) NOT NULL DEFAULT 'review',
			summary longtext NULL,
			rationale longtext NULL,
			information_gaps longtext NULL,
			conflict_notes longtext NULL,
			checklist_json longtext NULL,
			escalation_status varchar(30) NOT NULL DEFAULT 'none',
			escalation_reason longtext NULL,
			assigned_user_id bigint(20) unsigned NULL,
			due_at datetime NULL,
			inquiry_status varchar(80) NOT NULL DEFAULT '',
			review_version int(10) unsigned NOT NULL DEFAULT 0,
			snapshot_json longtext NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			KEY inquiry_id (inquiry_id),
			KEY reviewer_user_id (reviewer_user_id),
			KEY event_type (event_type),
			KEY to_stage (to_stage),
			KEY fit_decision (fit_decision),
			KEY risk_level (risk_level),
			KEY escalation_status (escalation_status),
			KEY created_at (created_at)
		) {$charset_collate};";


		$sql_communications = "CREATE TABLE {$communications} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			inquiry_id bigint(20) unsigned NOT NULL,
			public_id char(36) NOT NULL,
			thread_key varchar(80) NOT NULL DEFAULT '',
			reply_to_id bigint(20) unsigned NULL,
			direction varchar(20) NOT NULL DEFAULT 'outbound',
			channel varchar(30) NOT NULL DEFAULT 'email',
			communication_type varchar(60) NOT NULL DEFAULT 'general_response',
			status varchar(30) NOT NULL DEFAULT 'draft',
			subject varchar(255) NOT NULL DEFAULT '',
			body_text longtext NULL,
			sender_user_id bigint(20) unsigned NULL,
			sender_name varchar(191) NOT NULL DEFAULT '',
			sender_email varchar(191) NOT NULL DEFAULT '',
			recipient_name varchar(191) NOT NULL DEFAULT '',
			recipient_email varchar(191) NOT NULL DEFAULT '',
			cc_json longtext NULL,
			template_key varchar(80) NOT NULL DEFAULT '',
			template_version int(10) unsigned NOT NULL DEFAULT 0,
			is_automated tinyint(1) unsigned NOT NULL DEFAULT 0,
			requires_approval tinyint(1) unsigned NOT NULL DEFAULT 1,
			approved_by bigint(20) unsigned NULL,
			approved_at datetime NULL,
			provider varchar(80) NOT NULL DEFAULT '',
			provider_message_id varchar(191) NOT NULL DEFAULT '',
			attempt_count int(10) unsigned NOT NULL DEFAULT 0,
			last_attempt_at datetime NULL,
			accepted_at datetime NULL,
			failed_at datetime NULL,
			error_code varchar(120) NOT NULL DEFAULT '',
			error_message text NULL,
			occurred_at datetime NULL,
			scheduled_for datetime NULL,
			privacy_classification varchar(30) NOT NULL DEFAULT 'private',
			message_hash char(64) NOT NULL DEFAULT '',
			dedupe_key varchar(191) NULL,
			metadata_json longtext NULL,
			row_version int(10) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			deleted_at datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY dedupe_key (dedupe_key),
			KEY inquiry_id (inquiry_id),
			KEY thread_key (thread_key),
			KEY reply_to_id (reply_to_id),
			KEY direction (direction),
			KEY channel (channel),
			KEY communication_type (communication_type),
			KEY status (status),
			KEY sender_user_id (sender_user_id),
			KEY recipient_email (recipient_email),
			KEY accepted_at (accepted_at),
			KEY occurred_at (occurred_at),
			KEY scheduled_for (scheduled_for),
			KEY created_at (created_at),
			KEY deleted_at (deleted_at)
		) {$charset_collate};";

		$sql_communication_events = "CREATE TABLE {$communication_events} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			communication_id bigint(20) unsigned NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			actor_user_id bigint(20) unsigned NULL,
			event_type varchar(60) NOT NULL DEFAULT '',
			from_status varchar(30) NOT NULL DEFAULT '',
			to_status varchar(30) NOT NULL DEFAULT '',
			provider varchar(80) NOT NULL DEFAULT '',
			provider_message_id varchar(191) NOT NULL DEFAULT '',
			error_code varchar(120) NOT NULL DEFAULT '',
			error_message text NULL,
			context_json longtext NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY communication_id (communication_id),
			KEY inquiry_id (inquiry_id),
			KEY actor_user_id (actor_user_id),
			KEY event_type (event_type),
			KEY to_status (to_status),
			KEY created_at (created_at)
		) {$charset_collate};";

		$sql_communication_templates = "CREATE TABLE {$communication_templates} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			template_key varchar(80) NOT NULL,
			version int(10) unsigned NOT NULL DEFAULT 1,
			name varchar(191) NOT NULL DEFAULT '',
			communication_type varchar(60) NOT NULL DEFAULT 'general_response',
			subject_template text NULL,
			body_template longtext NULL,
			status varchar(20) NOT NULL DEFAULT 'active',
			is_system tinyint(1) unsigned NOT NULL DEFAULT 0,
			created_by bigint(20) unsigned NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY template_version (template_key, version),
			KEY template_key (template_key),
			KEY communication_type (communication_type),
			KEY status (status),
			KEY created_by (created_by),
			KEY created_at (created_at)
		) {$charset_collate};";


		$sql_privacy_requests = "CREATE TABLE {$privacy_requests} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			inquiry_id bigint(20) unsigned NULL,
			requester_name varchar(191) NOT NULL DEFAULT '',
			requester_email varchar(191) NOT NULL DEFAULT '',
			request_type varchar(40) NOT NULL DEFAULT 'access',
			status varchar(30) NOT NULL DEFAULT 'received',
			identity_status varchar(30) NOT NULL DEFAULT 'unverified',
			source varchar(40) NOT NULL DEFAULT 'admin',
			received_at datetime NOT NULL,
			due_at datetime NULL,
			assigned_user_id bigint(20) unsigned NULL,
			request_summary longtext NULL,
			resolution_summary longtext NULL,
			evidence_json longtext NULL,
			completed_at datetime NULL,
			created_by bigint(20) unsigned NULL,
			updated_by bigint(20) unsigned NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			KEY inquiry_id (inquiry_id),
			KEY requester_email (requester_email),
			KEY request_type (request_type),
			KEY status (status),
			KEY identity_status (identity_status),
			KEY due_at (due_at),
			KEY assigned_user_id (assigned_user_id),
			KEY received_at (received_at)
		) {$charset_collate};";

		$sql_consent_events = "CREATE TABLE {$consent_events} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			consent_type varchar(60) NOT NULL DEFAULT 'privacy_notice',
			action varchar(30) NOT NULL DEFAULT 'granted',
			consent_version varchar(80) NOT NULL DEFAULT '',
			lawful_basis varchar(60) NOT NULL DEFAULT 'request_processing',
			source varchar(40) NOT NULL DEFAULT 'public_form',
			evidence_text text NULL,
			subject_email_hash char(64) NOT NULL DEFAULT '',
			actor_user_id bigint(20) unsigned NULL,
			occurred_at datetime NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			KEY inquiry_id (inquiry_id),
			KEY consent_type (consent_type),
			KEY action (action),
			KEY consent_version (consent_version),
			KEY lawful_basis (lawful_basis),
			KEY actor_user_id (actor_user_id),
			KEY occurred_at (occurred_at)
		) {$charset_collate};";

		$sql_legal_holds = "CREATE TABLE {$legal_holds} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			inquiry_id bigint(20) unsigned NULL,
			attachment_id bigint(20) unsigned NULL,
			scope varchar(30) NOT NULL DEFAULT 'inquiry',
			status varchar(20) NOT NULL DEFAULT 'active',
			reason longtext NULL,
			authority varchar(191) NOT NULL DEFAULT '',
			placed_by bigint(20) unsigned NULL,
			placed_at datetime NOT NULL,
			review_at datetime NULL,
			released_by bigint(20) unsigned NULL,
			released_at datetime NULL,
			release_reason longtext NULL,
			metadata_json longtext NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			KEY inquiry_id (inquiry_id),
			KEY attachment_id (attachment_id),
			KEY scope (scope),
			KEY status (status),
			KEY placed_by (placed_by),
			KEY review_at (review_at),
			KEY placed_at (placed_at)
		) {$charset_collate};";

		$sql_retention_policies = "CREATE TABLE {$retention_policies} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			policy_key varchar(80) NOT NULL,
			version int(10) unsigned NOT NULL DEFAULT 1,
			name varchar(191) NOT NULL DEFAULT '',
			target_type varchar(30) NOT NULL DEFAULT 'inquiry',
			status_scope longtext NULL,
			retention_days int(10) unsigned NOT NULL DEFAULT 365,
			anchor_field varchar(60) NOT NULL DEFAULT 'created_at',
			action_type varchar(40) NOT NULL DEFAULT 'redact_inquiry',
			status varchar(20) NOT NULL DEFAULT 'active',
			legal_basis varchar(120) NOT NULL DEFAULT '',
			description longtext NULL,
			is_system tinyint(1) unsigned NOT NULL DEFAULT 0,
			created_by bigint(20) unsigned NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY policy_version (policy_key, version),
			KEY policy_key (policy_key),
			KEY target_type (target_type),
			KEY action_type (action_type),
			KEY status (status),
			KEY created_by (created_by)
		) {$charset_collate};";

		$sql_retention_actions = "CREATE TABLE {$retention_actions} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			inquiry_id bigint(20) unsigned NULL,
			target_type varchar(30) NOT NULL DEFAULT 'attachment',
			target_id bigint(20) unsigned NOT NULL DEFAULT 0,
			policy_key varchar(80) NOT NULL DEFAULT '',
			policy_version int(10) unsigned NOT NULL DEFAULT 0,
			action_type varchar(40) NOT NULL DEFAULT 'delete_attachment',
			status varchar(30) NOT NULL DEFAULT 'queued',
			reason longtext NULL,
			due_at datetime NULL,
			dedupe_key varchar(191) NOT NULL DEFAULT '',
			proposed_by bigint(20) unsigned NULL,
			proposed_at datetime NOT NULL,
			approved_by bigint(20) unsigned NULL,
			approved_at datetime NULL,
			executed_by bigint(20) unsigned NULL,
			executed_at datetime NULL,
			verified_at datetime NULL,
			failure_code varchar(120) NOT NULL DEFAULT '',
			failure_message longtext NULL,
			snapshot_json longtext NULL,
			action_version int(10) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY dedupe_key (dedupe_key),
			KEY inquiry_id (inquiry_id),
			KEY target_type (target_type),
			KEY target_id (target_id),
			KEY policy_key (policy_key),
			KEY action_type (action_type),
			KEY status (status),
			KEY due_at (due_at),
			KEY proposed_by (proposed_by),
			KEY approved_by (approved_by),
			KEY executed_by (executed_by),
			KEY proposed_at (proposed_at)
		) {$charset_collate};";

		$sql_audit = "CREATE TABLE {$audit_log} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			inquiry_id bigint(20) unsigned NULL,
			attachment_id bigint(20) unsigned NULL,
			actor_user_id bigint(20) unsigned NULL,
			event_type varchar(100) NOT NULL,
			event_message text NULL,
			context_json longtext NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY inquiry_id (inquiry_id),
			KEY attachment_id (attachment_id),
			KEY actor_user_id (actor_user_id),
			KEY event_type (event_type),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $sql_inquiries );
		dbDelta( $sql_attachments );
		dbDelta( $sql_reviews );
		dbDelta( $sql_communications );
		dbDelta( $sql_communication_events );
		dbDelta( $sql_communication_templates );
		dbDelta( $sql_privacy_requests );
		dbDelta( $sql_consent_events );
		dbDelta( $sql_legal_holds );
		dbDelta( $sql_retention_policies );
		dbDelta( $sql_retention_actions );
		dbDelta( $sql_audit );

		self::backfill_review_defaults();
		self::backfill_communication_defaults();
		self::backfill_privacy_defaults();
		update_option( 'sc_ei_db_version', SC_EI_DB_VERSION, false );
	}

	private static function backfill_review_defaults(): void {
		global $wpdb;

		$table = self::table( 'inquiries' );
		$settings = wp_parse_args(
			get_option( 'sc_ei_settings', array() ),
			SC_EI_Review_Schema::default_review_settings()
		);
		$days = max( 1, min( 30, absint( $settings['default_review_due_days'] ?? 3 ) ) );
		$checklist = wp_json_encode( SC_EI_Review_Schema::sanitize_checklist( array() ) );

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				SET review_due_at = DATE_ADD(created_at, INTERVAL %d DAY)
				WHERE review_due_at IS NULL
				AND review_stage <> %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$days,
				'completed'
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				SET review_checklist = %s
				WHERE review_checklist IS NULL OR review_checklist = ''", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$checklist
			)
		);
	}

	private static function backfill_communication_defaults(): void {
		global $wpdb;

		$table = self::table( 'inquiries' );
		$wpdb->query(
			"UPDATE {$table}
			SET communication_status = 'open'
			WHERE communication_status IS NULL OR communication_status = ''" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	private static function backfill_privacy_defaults(): void {
		global $wpdb;

		$table = self::table( 'inquiries' );
		$settings = wp_parse_args(
			get_option( 'sc_ei_settings', array() ),
			SC_EI_Privacy_Schema::default_settings()
		);
		$days = max( 30, min( 3650, absint( $settings['default_unaccepted_retention_days'] ?? 365 ) ) );

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				SET privacy_status = 'active',
					retention_policy_key = 'unaccepted_inquiry',
					retention_until = DATE_ADD(created_at, INTERVAL %d DAY)
				WHERE (privacy_status IS NULL OR privacy_status = '')
					OR retention_policy_key IS NULL
					OR retention_policy_key = ''", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$days
			)
		);
	}

	public static function maybe_upgrade(): void {
		$current = (string) get_option( 'sc_ei_db_version', '' );
		if ( version_compare( $current, SC_EI_DB_VERSION, '<' ) ) {
			self::install();
		}
	}

	public static function tables_exist(): array {
		global $wpdb;

		$result = array();
		foreach ( array( 'inquiries', 'attachments', 'reviews', 'communications', 'communication_events', 'communication_templates', 'privacy_requests', 'consent_events', 'legal_holds', 'retention_policies', 'retention_actions', 'audit_log' ) as $name ) {
			$table           = self::table( $name );
			$found           = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
			$result[ $name ] = ( $found === $table );
		}

		return $result;
	}

	public static function inquiry_columns_exist(): array {
		global $wpdb;

		$table   = self::table( 'inquiries' );
		$columns = array(
			'form_variant',
			'source_page',
			'entry_cta',
			'conversion_route',
			'guidance_flags',
			'preferred_contact_method',
			'teams_email',
			'timezone',
			'meeting_request',
			'scheduling_status',
			'teams_meeting_url',
			'scheduled_start_utc',
			'assignment_at',
			'assignment_by',
			'review_stage',
			'review_priority',
			'review_due_at',
			'fit_decision',
			'fit_confidence',
			'risk_level',
			'evidence_readiness',
			'scope_clarity',
			'recommended_next_step',
			'review_summary',
			'decision_rationale',
			'information_gaps',
			'conflict_notes',
			'review_checklist',
			'escalation_status',
			'escalation_reason',
			'review_started_at',
			'last_reviewed_at',
			'last_reviewed_by',
			'decision_at',
			'review_completed_at',
			'review_version',
			'communication_status',
			'next_follow_up_at',
			'last_communication_at',
			'last_outbound_at',
			'last_inbound_at',
			'last_notification_at',
			'communication_count',
			'unread_inbound_count',
			'do_not_email',
			'do_not_email_reason',
			'communication_version',
			'privacy_status',
			'retention_policy_key',
			'retention_until',
			'legal_hold_count',
			'privacy_restriction_reason',
			'last_privacy_review_at',
			'last_privacy_review_by',
			'personal_data_erased_at',
			'privacy_version',
		);

		$result = array();
		foreach ( $columns as $column ) {
			$found             = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", $column ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$result[ $column ] = ( $found === $column );
		}

		return $result;
	}

	public static function review_columns_exist(): array {
		global $wpdb;

		$table   = self::table( 'reviews' );
		$columns = array(
			'inquiry_id',
			'public_id',
			'reviewer_user_id',
			'event_type',
			'from_stage',
			'to_stage',
			'priority',
			'fit_decision',
			'fit_confidence',
			'risk_level',
			'evidence_readiness',
			'scope_clarity',
			'recommended_next_step',
			'summary',
			'rationale',
			'information_gaps',
			'conflict_notes',
			'checklist_json',
			'escalation_status',
			'escalation_reason',
			'assigned_user_id',
			'due_at',
			'inquiry_status',
			'review_version',
			'snapshot_json',
			'created_at',
		);

		$result = array();
		foreach ( $columns as $column ) {
			$found             = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", $column ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$result[ $column ] = ( $found === $column );
		}

		return $result;
	}

	public static function communication_columns_exist(): array {
		global $wpdb;

		$tables = array(
			'communications' => array(
				'inquiry_id', 'public_id', 'thread_key', 'reply_to_id', 'direction', 'channel',
				'communication_type', 'status', 'subject', 'body_text', 'sender_user_id',
				'sender_name', 'sender_email', 'recipient_name', 'recipient_email', 'cc_json',
				'template_key', 'template_version', 'is_automated', 'requires_approval',
				'approved_by', 'approved_at', 'provider', 'provider_message_id', 'attempt_count',
				'last_attempt_at', 'accepted_at', 'failed_at', 'error_code', 'error_message',
				'occurred_at', 'scheduled_for', 'privacy_classification', 'message_hash',
				'dedupe_key', 'metadata_json', 'row_version', 'created_at', 'updated_at', 'deleted_at',
			),
			'communication_events' => array(
				'communication_id', 'inquiry_id', 'actor_user_id', 'event_type', 'from_status',
				'to_status', 'provider', 'provider_message_id', 'error_code', 'error_message',
				'context_json', 'created_at',
			),
			'communication_templates' => array(
				'template_key', 'version', 'name', 'communication_type', 'subject_template',
				'body_template', 'status', 'is_system', 'created_by', 'created_at', 'updated_at',
			),
		);

		$result = array();
		foreach ( $tables as $table_name => $columns ) {
			$table = self::table( $table_name );
			foreach ( $columns as $column ) {
				$found = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", $column ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$result[ $table_name . '.' . $column ] = ( $found === $column );
			}
		}
		return $result;
	}

	public static function privacy_columns_exist(): array {
		global $wpdb;

		$tables = array(
			'privacy_requests' => array(
				'public_id', 'inquiry_id', 'requester_name', 'requester_email', 'request_type',
				'status', 'identity_status', 'source', 'received_at', 'due_at', 'assigned_user_id',
				'request_summary', 'resolution_summary', 'evidence_json', 'completed_at',
				'created_by', 'updated_by', 'created_at', 'updated_at',
			),
			'consent_events' => array(
				'public_id', 'inquiry_id', 'consent_type', 'action', 'consent_version',
				'lawful_basis', 'source', 'evidence_text', 'subject_email_hash',
				'actor_user_id', 'occurred_at', 'created_at',
			),
			'legal_holds' => array(
				'public_id', 'inquiry_id', 'attachment_id', 'scope', 'status', 'reason',
				'authority', 'placed_by', 'placed_at', 'review_at', 'released_by',
				'released_at', 'release_reason', 'metadata_json', 'created_at', 'updated_at',
			),
			'retention_policies' => array(
				'policy_key', 'version', 'name', 'target_type', 'status_scope',
				'retention_days', 'anchor_field', 'action_type', 'status', 'legal_basis',
				'description', 'is_system', 'created_by', 'created_at', 'updated_at',
			),
			'retention_actions' => array(
				'public_id', 'inquiry_id', 'target_type', 'target_id', 'policy_key',
				'policy_version', 'action_type', 'status', 'reason', 'due_at', 'dedupe_key',
				'proposed_by', 'proposed_at', 'approved_by', 'approved_at', 'executed_by',
				'executed_at', 'verified_at', 'failure_code', 'failure_message',
				'snapshot_json', 'action_version', 'created_at', 'updated_at',
			),
		);

		$result = array();
		foreach ( $tables as $table_name => $columns ) {
			$table = self::table( $table_name );
			foreach ( $columns as $column ) {
				$found = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", $column ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$result[ $table_name . '.' . $column ] = ( $found === $column );
			}
		}
		return $result;
	}

	public static function attachment_columns_exist(): array {
		global $wpdb;

		$table   = self::table( 'attachments' );
		$columns = array(
			'detected_mime',
			'signature_type',
			'validator_version',
			'document_category',
			'confidentiality',
			'scanner_provider',
			'scan_message',
			'scan_attempts',
			'last_scanned_at',
			'last_scanned_by',
			'integrity_status',
			'storage_status',
			'last_verified_at',
			'last_verified_by',
			'last_verification_source',
			'last_verification_message',
			'retention_until',
			'approved_by',
			'approved_at',
			'rejected_by',
			'rejected_at',
			'replacement_requested_at',
			'deleted_by',
			'downloaded_count',
			'last_downloaded_at',
		);

		$result = array();
		foreach ( $columns as $column ) {
			$found             = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", $column ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$result[ $column ] = ( $found === $column );
		}

		return $result;
	}

	public static function drop_all(): void {
		global $wpdb;

		foreach ( array( 'audit_log', 'retention_actions', 'retention_policies', 'legal_holds', 'consent_events', 'privacy_requests', 'communication_events', 'communications', 'communication_templates', 'reviews', 'attachments', 'inquiries' ) as $name ) {
			$table = self::table( $name );
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}
}
