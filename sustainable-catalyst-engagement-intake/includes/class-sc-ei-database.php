<?php
/**
 * Database schema and migrations.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Database {

	/**
	 * Cache physical table checks during one request so missing-table recovery
	 * does not issue repeated metadata queries.
	 *
	 * @var array<string,bool>
	 */
	private static array $table_presence_cache = array();

	public static function table( string $name ): string {
		global $wpdb;

		$allowed = array( 'inquiries', 'attachments', 'reviews', 'fit_assessments', 'fit_assessment_items', 'fit_assessment_reviews', 'portal_access', 'portal_sessions', 'portal_events', 'portal_recovery_requests', 'meeting_offers', 'meeting_reminders', 'graph_operations', 'proposals', 'proposal_versions', 'proposal_approvals', 'statements_of_work', 'statement_of_work_versions', 'change_requests', 'client_workspaces', 'workspace_members', 'workspace_milestones', 'workspace_deliverables', 'workspace_messages', 'workspace_documents', 'workspace_events', 'workflow_events', 'engagements', 'engagement_snapshots', 'engagement_requirements', 'engagement_events', 'analytics_snapshots', 'service_intelligence_findings', 'service_intelligence_events', 'billing_profiles', 'invoices', 'invoice_items', 'invoice_versions', 'payment_handoffs', 'billing_events', 'engagement_dossiers', 'dossier_relationships', 'dossier_events', 'platform_handoffs', 'health_events', 'rate_limits', 'workflow_cases', 'workflow_commands', 'workflow_handoffs', 'workflow_outbox', 'platform_snapshots', 'platform_migrations', 'communications', 'communication_events', 'communication_templates', 'lifecycle_events', 'lifecycle_notes', 'lifecycle_tasks', 'support_cases', 'support_case_events', 'support_case_links', 'support_signals', 'privacy_requests', 'consent_events', 'legal_holds', 'retention_policies', 'retention_actions', 'audit_log' );
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
		$fit_assessments = self::table( 'fit_assessments' );
		$fit_assessment_items = self::table( 'fit_assessment_items' );
		$fit_assessment_reviews = self::table( 'fit_assessment_reviews' );
		$portal_access = self::table( 'portal_access' );
		$portal_sessions = self::table( 'portal_sessions' );
		$portal_events = self::table( 'portal_events' );
		$portal_recovery_requests = self::table( 'portal_recovery_requests' );
		$meeting_offers = self::table( 'meeting_offers' );
		$meeting_reminders = self::table( 'meeting_reminders' );
		$graph_operations = self::table( 'graph_operations' );
		$proposals = self::table( 'proposals' );
		$proposal_versions = self::table( 'proposal_versions' );
		$proposal_approvals = self::table( 'proposal_approvals' );
		$statements_of_work = self::table( 'statements_of_work' );
		$statement_of_work_versions = self::table( 'statement_of_work_versions' );
		$change_requests = self::table( 'change_requests' );
		$client_workspaces = self::table( 'client_workspaces' );
		$workspace_members = self::table( 'workspace_members' );
		$workspace_milestones = self::table( 'workspace_milestones' );
		$workspace_deliverables = self::table( 'workspace_deliverables' );
		$workspace_messages = self::table( 'workspace_messages' );
		$workspace_documents = self::table( 'workspace_documents' );
		$workspace_events = self::table( 'workspace_events' );
		$workflow_events = self::table( 'workflow_events' );
		$engagements = self::table( 'engagements' );
		$engagement_snapshots = self::table( 'engagement_snapshots' );
		$engagement_requirements = self::table( 'engagement_requirements' );
		$engagement_events = self::table( 'engagement_events' );
		$analytics_snapshots = self::table( 'analytics_snapshots' );
		$service_intelligence_findings = self::table( 'service_intelligence_findings' );
		$service_intelligence_events = self::table( 'service_intelligence_events' );
		$billing_profiles = self::table( 'billing_profiles' );
		$invoices = self::table( 'invoices' );
		$invoice_items = self::table( 'invoice_items' );
		$invoice_versions = self::table( 'invoice_versions' );
		$payment_handoffs = self::table( 'payment_handoffs' );
		$billing_events = self::table( 'billing_events' );
		$engagement_dossiers = self::table( 'engagement_dossiers' );
		$dossier_relationships = self::table( 'dossier_relationships' );
		$dossier_events = self::table( 'dossier_events' );
		$platform_handoffs = self::table( 'platform_handoffs' );
		$health_events = self::table( 'health_events' );
		$rate_limits = self::table( 'rate_limits' );
		$workflow_cases = self::table( 'workflow_cases' );
		$workflow_commands = self::table( 'workflow_commands' );
		$workflow_handoffs = self::table( 'workflow_handoffs' );
		$workflow_outbox = self::table( 'workflow_outbox' );
		$platform_snapshots = self::table( 'platform_snapshots' );
		$platform_migrations = self::table( 'platform_migrations' );
		$communication_templates = self::table( 'communication_templates' );
		$lifecycle_events = self::table( 'lifecycle_events' );
		$lifecycle_notes = self::table( 'lifecycle_notes' );
		$lifecycle_tasks = self::table( 'lifecycle_tasks' );
		$support_cases = self::table( 'support_cases' );
		$support_case_events = self::table( 'support_case_events' );
		$support_case_links = self::table( 'support_case_links' );
		$support_signals = self::table( 'support_signals' );
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
			fit_assessment_status varchar(30) NOT NULL DEFAULT 'not_started',
			current_fit_assessment_id bigint(20) unsigned NULL,
			fit_assessment_updated_at datetime NULL,
			fit_assessment_finalized_at datetime NULL,
			fit_assessment_version int(10) unsigned NOT NULL DEFAULT 0,
			portal_status varchar(30) NOT NULL DEFAULT 'inactive',
			portal_access_id bigint(20) unsigned NULL,
			portal_last_activity_at datetime NULL,
			portal_message_count int(10) unsigned NOT NULL DEFAULT 0,
			portal_document_count int(10) unsigned NOT NULL DEFAULT 0,
			portal_last_sender_message_at datetime NULL,
			sender_withdrawal_status varchar(30) NOT NULL DEFAULT 'none',
			sender_withdrawal_requested_at datetime NULL,
			sender_withdrawal_reason longtext NULL,
			portal_version int(10) unsigned NOT NULL DEFAULT 0,
			lifecycle_stage varchar(60) NOT NULL DEFAULT 'new_inquiry',
			lifecycle_owner_user_id bigint(20) unsigned NULL,
			lifecycle_priority varchar(20) NOT NULL DEFAULT 'normal',
			next_action varchar(255) NOT NULL DEFAULT '',
			next_action_at datetime NULL,
			qualification_status varchar(30) NOT NULL DEFAULT 'not_started',
			qualification_score smallint(5) unsigned NOT NULL DEFAULT 0,
			qualification_json longtext NULL,
			decision_authority varchar(40) NOT NULL DEFAULT 'unknown',
			funding_status varchar(40) NOT NULL DEFAULT 'unknown',
			stakeholder_summary longtext NULL,
			systems_constraints longtext NULL,
			data_security_requirements longtext NULL,
			ai_assurance_applicable varchar(20) NOT NULL DEFAULT 'not_assessed',
			teams_readiness varchar(30) NOT NULL DEFAULT 'not_assessed',
			sender_lifecycle_summary longtext NULL,
			lifecycle_version int(10) unsigned NOT NULL DEFAULT 0,
			lifecycle_updated_at datetime NULL,
			lifecycle_updated_by bigint(20) unsigned NULL,
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
			KEY fit_assessment_status (fit_assessment_status),
			KEY current_fit_assessment_id (current_fit_assessment_id),
			KEY fit_assessment_updated_at (fit_assessment_updated_at),
			KEY fit_assessment_finalized_at (fit_assessment_finalized_at),
			KEY portal_status (portal_status),
			KEY portal_access_id (portal_access_id),
			KEY portal_last_activity_at (portal_last_activity_at),
			KEY portal_last_sender_message_at (portal_last_sender_message_at),
			KEY sender_withdrawal_status (sender_withdrawal_status),
			KEY lifecycle_stage (lifecycle_stage),
			KEY lifecycle_owner_user_id (lifecycle_owner_user_id),
			KEY lifecycle_priority (lifecycle_priority),
			KEY next_action_at (next_action_at),
			KEY qualification_status (qualification_status),
			KEY decision_authority (decision_authority),
			KEY funding_status (funding_status),
			KEY lifecycle_updated_at (lifecycle_updated_at),
			KEY lifecycle_updated_by (lifecycle_updated_by),
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



		$sql_fit_assessments = "CREATE TABLE {$fit_assessments} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			assessment_version int(10) unsigned NOT NULL DEFAULT 1,
			parent_assessment_id bigint(20) unsigned NULL,
			assessor_user_id bigint(20) unsigned NULL,
			status varchar(30) NOT NULL DEFAULT 'draft',
			recommendation varchar(40) NOT NULL DEFAULT 'undecided',
			confidence varchar(20) NOT NULL DEFAULT 'unassessed',
			service_route varchar(80) NOT NULL DEFAULT 'continue_review',
			scope_boundary varchar(40) NOT NULL DEFAULT 'within_scope',
			advisory_score decimal(5,2) NULL,
			score_complete tinyint(1) unsigned NOT NULL DEFAULT 0,
			material_concern_count smallint(5) unsigned NOT NULL DEFAULT 0,
			second_review_required tinyint(1) unsigned NOT NULL DEFAULT 0,
			second_review_reason longtext NULL,
			second_reviewer_user_id bigint(20) unsigned NULL,
			second_review_disposition varchar(40) NOT NULL DEFAULT 'not_requested',
			second_reviewed_at datetime NULL,
			overall_summary longtext NULL,
			recommendation_rationale longtext NULL,
			limitations_notes longtext NULL,
			conditions_for_fit longtext NULL,
			referral_notes longtext NULL,
			human_attestation tinyint(1) unsigned NOT NULL DEFAULT 0,
			assistance_disclosure varchar(40) NOT NULL DEFAULT 'none',
			assistance_notes longtext NULL,
			submitted_at datetime NULL,
			finalized_by bigint(20) unsigned NULL,
			finalized_at datetime NULL,
			superseded_at datetime NULL,
			row_version int(10) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY inquiry_version (inquiry_id, assessment_version),
			KEY inquiry_id (inquiry_id),
			KEY parent_assessment_id (parent_assessment_id),
			KEY assessor_user_id (assessor_user_id),
			KEY status (status),
			KEY recommendation (recommendation),
			KEY service_route (service_route),
			KEY scope_boundary (scope_boundary),
			KEY second_review_required (second_review_required),
			KEY second_reviewer_user_id (second_reviewer_user_id),
			KEY second_review_disposition (second_review_disposition),
			KEY finalized_by (finalized_by),
			KEY finalized_at (finalized_at),
			KEY updated_at (updated_at)
		) {$charset_collate};";

		$sql_fit_assessment_items = "CREATE TABLE {$fit_assessment_items} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			assessment_id bigint(20) unsigned NOT NULL,
			criterion_key varchar(80) NOT NULL,
			criterion_group varchar(60) NOT NULL DEFAULT '',
			rating varchar(30) NOT NULL DEFAULT 'not_assessed',
			weight decimal(5,2) NOT NULL DEFAULT 1.00,
			numeric_value decimal(5,2) NULL,
			is_applicable tinyint(1) unsigned NOT NULL DEFAULT 1,
			is_material_concern tinyint(1) unsigned NOT NULL DEFAULT 0,
			evidence_note longtext NULL,
			concern_note longtext NULL,
			source_refs_json longtext NULL,
			row_version int(10) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY assessment_criterion (assessment_id, criterion_key),
			KEY assessment_id (assessment_id),
			KEY criterion_group (criterion_group),
			KEY rating (rating),
			KEY is_material_concern (is_material_concern),
			KEY updated_at (updated_at)
		) {$charset_collate};";

		$sql_fit_assessment_reviews = "CREATE TABLE {$fit_assessment_reviews} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			assessment_id bigint(20) unsigned NOT NULL,
			reviewer_user_id bigint(20) unsigned NULL,
			disposition varchar(40) NOT NULL DEFAULT 'agree',
			recommendation varchar(40) NOT NULL DEFAULT 'undecided',
			service_route varchar(80) NOT NULL DEFAULT 'continue_review',
			scope_boundary varchar(40) NOT NULL DEFAULT 'within_scope',
			review_notes longtext NULL,
			required_changes longtext NULL,
			conflict_disclosure longtext NULL,
			human_attestation tinyint(1) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			KEY assessment_id (assessment_id),
			KEY reviewer_user_id (reviewer_user_id),
			KEY disposition (disposition),
			KEY recommendation (recommendation),
			KEY created_at (created_at)
		) {$charset_collate};";


		$sql_portal_access = "CREATE TABLE {$portal_access} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			status varchar(30) NOT NULL DEFAULT 'invited',
			sender_email_hash char(64) NOT NULL DEFAULT '',
			invite_token_hash char(64) NOT NULL DEFAULT '',
			invite_token_prefix varchar(16) NOT NULL DEFAULT '',
			invite_expires_at datetime NULL,
			invite_used_at datetime NULL,
			permissions_json longtext NULL,
			terms_version varchar(80) NOT NULL DEFAULT '',
			terms_accepted_at datetime NULL,
			invited_by bigint(20) unsigned NULL,
			invitation_note longtext NULL,
			activated_at datetime NULL,
			suspended_at datetime NULL,
			revoked_at datetime NULL,
			revoked_by bigint(20) unsigned NULL,
			revocation_reason longtext NULL,
			last_access_at datetime NULL,
			last_ip_hash char(64) NOT NULL DEFAULT '',
			last_user_agent_hash char(64) NOT NULL DEFAULT '',
			failed_attempts int(10) unsigned NOT NULL DEFAULT 0,
			locked_until datetime NULL,
			row_version int(10) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY inquiry_id (inquiry_id),
			KEY status (status),
			KEY invite_expires_at (invite_expires_at),
			KEY invited_by (invited_by),
			KEY revoked_by (revoked_by),
			KEY locked_until (locked_until),
			KEY last_access_at (last_access_at)
		) {$charset_collate};";

		$sql_portal_sessions = "CREATE TABLE {$portal_sessions} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			access_id bigint(20) unsigned NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			session_hash char(64) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'active',
			expires_at datetime NOT NULL,
			idle_expires_at datetime NOT NULL,
			last_seen_at datetime NOT NULL,
			ip_hash char(64) NOT NULL DEFAULT '',
			user_agent_hash char(64) NOT NULL DEFAULT '',
			activity_count int(10) unsigned NOT NULL DEFAULT 0,
			rotated_from_id bigint(20) unsigned NULL,
			revoked_at datetime NULL,
			revoked_reason longtext NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY session_hash (session_hash),
			KEY access_id (access_id),
			KEY inquiry_id (inquiry_id),
			KEY status (status),
			KEY expires_at (expires_at),
			KEY idle_expires_at (idle_expires_at),
			KEY last_seen_at (last_seen_at),
			KEY rotated_from_id (rotated_from_id)
		) {$charset_collate};";

		$sql_portal_events = "CREATE TABLE {$portal_events} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			inquiry_id bigint(20) unsigned NULL,
			access_id bigint(20) unsigned NULL,
			session_id bigint(20) unsigned NULL,
			event_type varchar(80) NOT NULL DEFAULT '',
			target_type varchar(40) NOT NULL DEFAULT '',
			target_id bigint(20) unsigned NOT NULL DEFAULT 0,
			outcome varchar(30) NOT NULL DEFAULT 'recorded',
			ip_hash char(64) NOT NULL DEFAULT '',
			user_agent_hash char(64) NOT NULL DEFAULT '',
			context_json longtext NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			KEY inquiry_id (inquiry_id),
			KEY access_id (access_id),
			KEY session_id (session_id),
			KEY event_type (event_type),
			KEY target_type (target_type),
			KEY target_id (target_id),
			KEY outcome (outcome),
			KEY created_at (created_at)
		) {$charset_collate};";


		$sql_portal_recovery_requests = "CREATE TABLE {$portal_recovery_requests} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			inquiry_id bigint(20) unsigned NULL,
			access_id bigint(20) unsigned NULL,
			status varchar(30) NOT NULL DEFAULT 'pending',
			match_status varchar(20) NOT NULL DEFAULT 'matched',
			reference_hash char(64) NOT NULL DEFAULT '',
			email_hash char(64) NOT NULL DEFAULT '',
			recovery_reason longtext NULL,
			request_ip_hash char(64) NOT NULL DEFAULT '',
			request_user_agent_hash char(64) NOT NULL DEFAULT '',
			request_count int(10) unsigned NOT NULL DEFAULT 1,
			requested_at datetime NOT NULL,
			last_requested_at datetime NOT NULL,
			expires_at datetime NOT NULL,
			reviewed_by bigint(20) unsigned NULL,
			reviewed_at datetime NULL,
			decision_note longtext NULL,
			completed_at datetime NULL,
			row_version int(10) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			KEY inquiry_id (inquiry_id),
			KEY access_id (access_id),
			KEY status (status),
			KEY match_status (match_status),
			KEY email_hash (email_hash),
			KEY request_ip_hash (request_ip_hash),
			KEY requested_at (requested_at),
			KEY expires_at (expires_at),
			KEY reviewed_by (reviewed_by),
			KEY completed_at (completed_at)
		) {$charset_collate};";


		$sql_meeting_offers = "CREATE TABLE {$meeting_offers} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			access_id bigint(20) unsigned NOT NULL,
			offer_number varchar(40) NOT NULL DEFAULT '',
			status varchar(40) NOT NULL DEFAULT 'draft',
			title varchar(255) NOT NULL DEFAULT '',
			purpose longtext NULL,
			meeting_type varchar(60) NOT NULL DEFAULT 'other',
			organizer_name varchar(191) NOT NULL DEFAULT '',
			organizer_email varchar(191) NOT NULL DEFAULT '',
			participant_emails_json longtext NULL,
			agenda longtext NULL,
			preparation_requests longtext NULL,
			sender_summary longtext NULL,
			sender_next_step longtext NULL,
			related_document_ids_json longtext NULL,
			duration_minutes smallint(5) unsigned NOT NULL DEFAULT 30,
			timezone varchar(120) NOT NULL DEFAULT '',
			slots_json longtext NULL,
			selected_slot_key varchar(40) NOT NULL DEFAULT '',
			selected_start_utc datetime NULL,
			selected_end_utc datetime NULL,
			teams_url text NULL,
			calendar_provider varchar(40) NOT NULL DEFAULT 'manual',
			external_calendar_reference varchar(255) NOT NULL DEFAULT '',
			previous_start_utc datetime NULL,
			previous_end_utc datetime NULL,
			reschedule_count smallint(5) unsigned NOT NULL DEFAULT 0,
			last_rescheduled_at datetime NULL,
			last_rescheduled_by bigint(20) unsigned NULL,
			join_url_revoked_at datetime NULL,
			graph_sync_status varchar(40) NOT NULL DEFAULT 'not_requested',
			graph_transaction_id char(36) NOT NULL DEFAULT '',
			graph_event_id text NULL,
			graph_i_cal_uid varchar(255) NOT NULL DEFAULT '',
			graph_change_key text NULL,
			graph_etag varchar(255) NOT NULL DEFAULT '',
			graph_web_link text NULL,
			graph_join_url text NULL,
			graph_organizer varchar(191) NOT NULL DEFAULT '',
			graph_calendar_id varchar(255) NOT NULL DEFAULT '',
			graph_payload_hash char(64) NOT NULL DEFAULT '',
			graph_remote_start_utc datetime NULL,
			graph_remote_end_utc datetime NULL,
			graph_last_request_id varchar(191) NOT NULL DEFAULT '',
			graph_last_client_request_id char(36) NOT NULL DEFAULT '',
			graph_last_error_code varchar(120) NOT NULL DEFAULT '',
			graph_last_error_message longtext NULL,
			graph_attempt_count smallint(5) unsigned NOT NULL DEFAULT 0,
			graph_last_attempt_at datetime NULL,
			graph_last_success_at datetime NULL,
			graph_next_retry_at datetime NULL,
			graph_reconciled_at datetime NULL,
			graph_deleted_at datetime NULL,
			sender_note longtext NULL,
			alternative_request longtext NULL,
			admin_note longtext NULL,
			expires_at datetime NOT NULL,
			published_by bigint(20) unsigned NULL,
			published_at datetime NULL,
			responded_at datetime NULL,
			finalized_by bigint(20) unsigned NULL,
			finalized_at datetime NULL,
			completed_at datetime NULL,
			canceled_at datetime NULL,
			cancellation_reason longtext NULL,
			post_meeting_internal_notes longtext NULL,
			post_meeting_sender_summary longtext NULL,
			decisions longtext NULL,
			open_questions longtext NULL,
			follow_up_owner_user_id bigint(20) unsigned NULL,
			follow_up_due_at datetime NULL,
			follow_up_task_id bigint(20) unsigned NULL,
			no_show_at datetime NULL,
			row_version int(10) unsigned NOT NULL DEFAULT 0,
			created_by bigint(20) unsigned NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY offer_number (offer_number),
			KEY inquiry_id (inquiry_id),
			KEY access_id (access_id),
			KEY status (status),
			KEY expires_at (expires_at),
			KEY selected_start_utc (selected_start_utc),
			KEY meeting_type (meeting_type),
			KEY organizer_email (organizer_email),
			KEY follow_up_due_at (follow_up_due_at),
			KEY graph_sync_status (graph_sync_status),
			KEY graph_transaction_id (graph_transaction_id),
			KEY graph_last_success_at (graph_last_success_at),
			KEY graph_next_retry_at (graph_next_retry_at),
			KEY published_by (published_by),
			KEY finalized_by (finalized_by),
			KEY created_at (created_at)
		) {$charset_collate};";


		$sql_meeting_reminders = "CREATE TABLE {$meeting_reminders} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			meeting_offer_id bigint(20) unsigned NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			reminder_type varchar(40) NOT NULL DEFAULT 'invitation',
			audience varchar(30) NOT NULL DEFAULT 'sender',
			status varchar(30) NOT NULL DEFAULT 'pending',
			due_at datetime NOT NULL,
			idempotency_key char(64) NOT NULL,
			communication_id bigint(20) unsigned NULL,
			attempt_count smallint(5) unsigned NOT NULL DEFAULT 0,
			last_error_code varchar(120) NOT NULL DEFAULT '',
			last_error_message longtext NULL,
			ready_at datetime NULL,
			sent_at datetime NULL,
			canceled_at datetime NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY idempotency_key (idempotency_key),
			KEY meeting_offer_id (meeting_offer_id),
			KEY inquiry_id (inquiry_id),
			KEY status_due_at (status,due_at),
			KEY communication_id (communication_id),
			KEY created_at (created_at)
		) {$charset_collate};";


		$sql_graph_operations = "CREATE TABLE {$graph_operations} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			meeting_offer_id bigint(20) unsigned NOT NULL,
			operation_type varchar(30) NOT NULL DEFAULT 'create',
			status varchar(30) NOT NULL DEFAULT 'pending',
			idempotency_key char(64) NOT NULL DEFAULT '',
			request_hash char(64) NOT NULL DEFAULT '',
			payload_json longtext NULL,
			attempt_count smallint(5) unsigned NOT NULL DEFAULT 0,
			max_attempts smallint(5) unsigned NOT NULL DEFAULT 6,
			scheduled_at datetime NOT NULL,
			next_retry_at datetime NULL,
			locked_at datetime NULL,
			lock_token char(36) NOT NULL DEFAULT '',
			started_at datetime NULL,
			completed_at datetime NULL,
			http_method varchar(10) NOT NULL DEFAULT '',
			endpoint_path text NULL,
			response_status smallint(5) unsigned NOT NULL DEFAULT 0,
			graph_error_code varchar(120) NOT NULL DEFAULT '',
			graph_error_message longtext NULL,
			retry_after_seconds int(10) unsigned NOT NULL DEFAULT 0,
			request_id varchar(191) NOT NULL DEFAULT '',
			client_request_id char(36) NOT NULL DEFAULT '',
			response_snapshot_json longtext NULL,
			actor_user_id bigint(20) unsigned NULL,
			context_json longtext NULL,
			row_version int(10) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY idempotency_key (idempotency_key),
			KEY inquiry_id (inquiry_id),
			KEY meeting_offer_id (meeting_offer_id),
			KEY operation_type (operation_type),
			KEY status (status),
			KEY scheduled_at (scheduled_at),
			KEY next_retry_at (next_retry_at),
			KEY locked_at (locked_at),
			KEY request_id (request_id),
			KEY actor_user_id (actor_user_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		$sql_proposals = "CREATE TABLE {$proposals} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			access_id bigint(20) unsigned NOT NULL,
			proposal_number varchar(40) NOT NULL DEFAULT '',
			status varchar(40) NOT NULL DEFAULT 'draft',
			current_version_id bigint(20) unsigned NULL,
			pending_version_id bigint(20) unsigned NULL,
			currency char(3) NOT NULL DEFAULT 'USD',
			total_minor bigint(20) unsigned NOT NULL DEFAULT 0,
			expires_at datetime NOT NULL,
			published_by bigint(20) unsigned NULL,
			published_at datetime NULL,
			sender_response varchar(30) NOT NULL DEFAULT '',
			sender_response_note longtext NULL,
			sender_authority_attested tinyint(1) unsigned NOT NULL DEFAULT 0,
			boundary_acknowledged tinyint(1) unsigned NOT NULL DEFAULT 0,
			responded_at datetime NULL,
			accepted_at datetime NULL,
			declined_at datetime NULL,
			withdrawn_at datetime NULL,
			superseded_by_id bigint(20) unsigned NULL,
			contract_reference varchar(191) NOT NULL DEFAULT '',
			contracted_by bigint(20) unsigned NULL,
			contracted_at datetime NULL,
			row_version int(10) unsigned NOT NULL DEFAULT 0,
			created_by bigint(20) unsigned NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY proposal_number (proposal_number),
			KEY inquiry_id (inquiry_id),
			KEY access_id (access_id),
			KEY status (status),
			KEY current_version_id (current_version_id),
			KEY pending_version_id (pending_version_id),
			KEY expires_at (expires_at),
			KEY published_by (published_by),
			KEY superseded_by_id (superseded_by_id),
			KEY contracted_by (contracted_by),
			KEY created_at (created_at)
		) {$charset_collate};";

		$sql_proposal_versions = "CREATE TABLE {$proposal_versions} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			proposal_id bigint(20) unsigned NOT NULL,
			version_number int(10) unsigned NOT NULL DEFAULT 1,
			title varchar(255) NOT NULL DEFAULT '',
			executive_summary longtext NULL,
			scope_json longtext NULL,
			deliverables_json longtext NULL,
			exclusions_json longtext NULL,
			assumptions_json longtext NULL,
			timeline_text longtext NULL,
			fee_summary longtext NULL,
			payment_terms longtext NULL,
			legal_terms longtext NULL,
			version_note longtext NULL,
			content_hash char(64) NOT NULL DEFAULT '',
			created_by bigint(20) unsigned NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY proposal_version (proposal_id, version_number),
			KEY proposal_id (proposal_id),
			KEY version_number (version_number),
			KEY content_hash (content_hash),
			KEY created_by (created_by),
			KEY created_at (created_at)
		) {$charset_collate};";


		$sql_proposal_approvals = "CREATE TABLE {$proposal_approvals} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			schema varchar(80) NOT NULL DEFAULT 'sc-proposal-approval/1.0',
			inquiry_id bigint(20) unsigned NOT NULL,
			proposal_id bigint(20) unsigned NOT NULL,
			proposal_version_id bigint(20) unsigned NOT NULL,
			sow_id bigint(20) unsigned NULL,
			action varchar(50) NOT NULL DEFAULT '',
			actor_type varchar(20) NOT NULL DEFAULT 'sender',
			actor_id bigint(20) unsigned NULL,
			note longtext NULL,
			authority_attested tinyint(1) unsigned NOT NULL DEFAULT 0,
			boundary_acknowledged tinyint(1) unsigned NOT NULL DEFAULT 0,
			confirmation_hash char(64) NOT NULL DEFAULT '',
			immutable_hash char(64) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			KEY inquiry_id (inquiry_id),
			KEY proposal_version (proposal_id, proposal_version_id),
			KEY sow_id (sow_id),
			KEY action (action),
			KEY actor_type (actor_type),
			KEY created_at (created_at)
		) {$charset_collate};";

		$sql_statements_of_work = "CREATE TABLE {$statements_of_work} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			sow_number varchar(40) NOT NULL DEFAULT '',
			inquiry_id bigint(20) unsigned NOT NULL,
			proposal_id bigint(20) unsigned NOT NULL,
			proposal_version_id bigint(20) unsigned NOT NULL,
			current_version_id bigint(20) unsigned NULL,
			pending_version_id bigint(20) unsigned NULL,
			status varchar(40) NOT NULL DEFAULT 'draft',
			sender_visible tinyint(1) unsigned NOT NULL DEFAULT 0,
			approved_by bigint(20) unsigned NULL,
			approved_at datetime NULL,
			sender_approved_at datetime NULL,
			row_version int(10) unsigned NOT NULL DEFAULT 0,
			created_by bigint(20) unsigned NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY sow_number (sow_number),
			UNIQUE KEY proposal_id (proposal_id),
			KEY inquiry_id (inquiry_id),
			KEY proposal_version_id (proposal_version_id),
			KEY current_version_id (current_version_id),
			KEY pending_version_id (pending_version_id),
			KEY status (status),
			KEY created_at (created_at)
		) {$charset_collate};";

		$sql_statement_of_work_versions = "CREATE TABLE {$statement_of_work_versions} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			sow_id bigint(20) unsigned NOT NULL,
			version_number int(10) unsigned NOT NULL DEFAULT 1,
			title varchar(255) NOT NULL DEFAULT '',
			purpose_background longtext NULL,
			scope_json longtext NULL,
			deliverables_json longtext NULL,
			milestones_json longtext NULL,
			responsibilities_json longtext NULL,
			dependencies_json longtext NULL,
			acceptance_criteria longtext NULL,
			change_control longtext NULL,
			communication_expectations longtext NULL,
			data_handling longtext NULL,
			ip_terms longtext NULL,
			open_source_boundaries longtext NULL,
			fees_payment longtext NULL,
			start_date date NULL,
			target_end_date date NULL,
			termination_conditions longtext NULL,
			attachment_ids_json longtext NULL,
			version_note longtext NULL,
			content_hash char(64) NOT NULL DEFAULT '',
			created_by bigint(20) unsigned NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY sow_version (sow_id, version_number),
			KEY sow_id (sow_id),
			KEY version_number (version_number),
			KEY content_hash (content_hash),
			KEY created_at (created_at)
		) {$charset_collate};";

		$sql_change_requests = "CREATE TABLE {$change_requests} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			change_number varchar(40) NOT NULL DEFAULT '',
			inquiry_id bigint(20) unsigned NOT NULL,
			proposal_id bigint(20) unsigned NULL,
			proposal_version_id bigint(20) unsigned NULL,
			sow_id bigint(20) unsigned NULL,
			sow_version_id bigint(20) unsigned NULL,
			engagement_id bigint(20) unsigned NULL,
			status varchar(40) NOT NULL DEFAULT 'requested',
			requester_type varchar(20) NOT NULL DEFAULT 'staff',
			requester_id bigint(20) unsigned NULL,
			request_summary longtext NULL,
			reason longtext NULL,
			scope_impact longtext NULL,
			timeline_impact longtext NULL,
			fee_impact_minor bigint(20) unsigned NOT NULL DEFAULT 0,
			currency char(3) NOT NULL DEFAULT 'USD',
			decision_note longtext NULL,
			decided_by bigint(20) unsigned NULL,
			decided_at datetime NULL,
			applied_by bigint(20) unsigned NULL,
			applied_at datetime NULL,
			row_version int(10) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY change_number (change_number),
			KEY inquiry_id (inquiry_id),
			KEY proposal_id (proposal_id),
			KEY sow_id (sow_id),
			KEY engagement_id (engagement_id),
			KEY status (status),
			KEY created_at (created_at)
		) {$charset_collate};";

		$sql_workflow_events = "CREATE TABLE {$workflow_events} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			actor_type varchar(30) NOT NULL DEFAULT 'system',
			actor_id bigint(20) unsigned NULL,
			object_type varchar(30) NOT NULL DEFAULT '',
			object_id bigint(20) unsigned NOT NULL DEFAULT 0,
			event_type varchar(80) NOT NULL DEFAULT '',
			from_status varchar(40) NOT NULL DEFAULT '',
			to_status varchar(40) NOT NULL DEFAULT '',
			context_json longtext NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			KEY inquiry_id (inquiry_id),
			KEY actor_type (actor_type),
			KEY actor_id (actor_id),
			KEY object_type_id (object_type, object_id),
			KEY event_type (event_type),
			KEY created_at (created_at)
		) {$charset_collate};";


		$sql_engagements = "CREATE TABLE {$engagements} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			engagement_number varchar(40) NOT NULL DEFAULT '',
			inquiry_id bigint(20) unsigned NOT NULL,
			proposal_id bigint(20) unsigned NOT NULL,
			proposal_version_id bigint(20) unsigned NOT NULL,
			access_id bigint(20) unsigned NOT NULL DEFAULT 0,
			current_snapshot_id bigint(20) unsigned NULL,
			status varchar(40) NOT NULL DEFAULT 'handoff_pending',
			title varchar(255) NOT NULL DEFAULT '',
			sender_organization varchar(191) NOT NULL DEFAULT '',
			contract_reference varchar(191) NOT NULL DEFAULT '',
			currency char(3) NOT NULL DEFAULT 'USD',
			total_minor bigint(20) unsigned NOT NULL DEFAULT 0,
			owner_user_id bigint(20) unsigned NULL,
			participant_user_ids_json longtext NULL,
			proposed_start_date date NULL,
			target_end_date date NULL,
			kickoff_status varchar(40) NOT NULL DEFAULT 'not_scheduled',
			kickoff_at datetime NULL,
			onboarding_summary longtext NULL,
			sender_summary longtext NULL,
			internal_notes longtext NULL,
			external_project_reference varchar(191) NOT NULL DEFAULT '',
			workbench_handoff_status varchar(40) NOT NULL DEFAULT 'not_requested',
			decision_studio_handoff_status varchar(40) NOT NULL DEFAULT 'not_requested',
			handoff_prepared_by bigint(20) unsigned NULL,
			handoff_prepared_at datetime NULL,
			ready_by bigint(20) unsigned NULL,
			ready_at datetime NULL,
			activated_by bigint(20) unsigned NULL,
			activated_at datetime NULL,
			paused_by bigint(20) unsigned NULL,
			paused_at datetime NULL,
			pause_reason longtext NULL,
			completed_by bigint(20) unsigned NULL,
			completed_at datetime NULL,
			completion_note longtext NULL,
			canceled_by bigint(20) unsigned NULL,
			canceled_at datetime NULL,
			cancellation_reason longtext NULL,
			row_version int(10) unsigned NOT NULL DEFAULT 0,
			created_by bigint(20) unsigned NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY engagement_number (engagement_number),
			UNIQUE KEY proposal_id (proposal_id),
			KEY inquiry_id (inquiry_id),
			KEY proposal_version_id (proposal_version_id),
			KEY access_id (access_id),
			KEY current_snapshot_id (current_snapshot_id),
			KEY status (status),
			KEY owner_user_id (owner_user_id),
			KEY proposed_start_date (proposed_start_date),
			KEY kickoff_at (kickoff_at),
			KEY activated_at (activated_at),
			KEY created_at (created_at)
		) {$charset_collate};";

		$sql_engagement_snapshots = "CREATE TABLE {$engagement_snapshots} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			engagement_id bigint(20) unsigned NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			proposal_id bigint(20) unsigned NOT NULL,
			proposal_version_id bigint(20) unsigned NOT NULL,
			snapshot_version int(10) unsigned NOT NULL DEFAULT 1,
			snapshot_type varchar(40) NOT NULL DEFAULT 'contracted_proposal_handoff',
			proposal_number varchar(40) NOT NULL DEFAULT '',
			proposal_version_number int(10) unsigned NOT NULL DEFAULT 1,
			proposal_content_hash char(64) NOT NULL DEFAULT '',
			contract_reference varchar(191) NOT NULL DEFAULT '',
			payload_json longtext NULL,
			content_hash char(64) NOT NULL DEFAULT '',
			created_by bigint(20) unsigned NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY engagement_snapshot (engagement_id, snapshot_version),
			KEY engagement_id (engagement_id),
			KEY inquiry_id (inquiry_id),
			KEY proposal_id (proposal_id),
			KEY proposal_version_id (proposal_version_id),
			KEY content_hash (content_hash),
			KEY created_at (created_at)
		) {$charset_collate};";

		$sql_engagement_requirements = "CREATE TABLE {$engagement_requirements} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			engagement_id bigint(20) unsigned NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			requirement_key varchar(100) NOT NULL DEFAULT '',
			title varchar(255) NOT NULL DEFAULT '',
			description longtext NULL,
			category varchar(40) NOT NULL DEFAULT 'other',
			status varchar(40) NOT NULL DEFAULT 'pending',
			is_required tinyint(1) unsigned NOT NULL DEFAULT 1,
			sender_visible tinyint(1) unsigned NOT NULL DEFAULT 0,
			due_date date NULL,
			assigned_user_id bigint(20) unsigned NULL,
			completion_note longtext NULL,
			evidence_reference varchar(255) NOT NULL DEFAULT '',
			sort_order int(10) unsigned NOT NULL DEFAULT 0,
			completed_by bigint(20) unsigned NULL,
			completed_at datetime NULL,
			waived_by bigint(20) unsigned NULL,
			waived_at datetime NULL,
			row_version int(10) unsigned NOT NULL DEFAULT 0,
			created_by bigint(20) unsigned NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY engagement_requirement (engagement_id, requirement_key),
			KEY engagement_id (engagement_id),
			KEY inquiry_id (inquiry_id),
			KEY category (category),
			KEY status (status),
			KEY is_required (is_required),
			KEY sender_visible (sender_visible),
			KEY due_date (due_date),
			KEY assigned_user_id (assigned_user_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		$sql_engagement_events = "CREATE TABLE {$engagement_events} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			engagement_id bigint(20) unsigned NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			actor_type varchar(30) NOT NULL DEFAULT 'system',
			actor_id bigint(20) unsigned NULL,
			event_type varchar(80) NOT NULL DEFAULT '',
			object_type varchar(40) NOT NULL DEFAULT 'engagement',
			object_id bigint(20) unsigned NOT NULL DEFAULT 0,
			from_status varchar(40) NOT NULL DEFAULT '',
			to_status varchar(40) NOT NULL DEFAULT '',
			context_json longtext NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			KEY engagement_id (engagement_id),
			KEY inquiry_id (inquiry_id),
			KEY actor_type (actor_type),
			KEY actor_id (actor_id),
			KEY event_type (event_type),
			KEY object_type_id (object_type, object_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		$sql_analytics_snapshots = "CREATE TABLE {$analytics_snapshots} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			range_days smallint(5) unsigned NOT NULL DEFAULT 90,
			period_start datetime NOT NULL,
			period_end datetime NOT NULL,
			minimum_cohort smallint(5) unsigned NOT NULL DEFAULT 5,
			payload_json longtext NOT NULL,
			content_hash char(64) NOT NULL,
			generated_by bigint(20) unsigned NULL,
			generated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			KEY range_days (range_days),
			KEY content_hash (content_hash),
			KEY generated_by (generated_by),
			KEY generated_at (generated_at)
		) {$charset_collate};";


		$sql_service_intelligence_findings = "CREATE TABLE {$service_intelligence_findings} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			schema varchar(80) NOT NULL DEFAULT 'sc-service-intelligence-finding/1.0',
			finding_type varchar(60) NOT NULL DEFAULT 'service_demand',
			severity varchar(20) NOT NULL DEFAULT 'watch',
			status varchar(20) NOT NULL DEFAULT 'candidate',
			title varchar(255) NOT NULL DEFAULT '',
			service_key varchar(120) NOT NULL DEFAULT '',
			product_key varchar(120) NOT NULL DEFAULT '',
			component_key varchar(120) NOT NULL DEFAULT '',
			period_start datetime NOT NULL,
			period_end datetime NOT NULL,
			cohort_count bigint(20) unsigned NOT NULL DEFAULT 0,
			metric_value decimal(20,4) NULL,
			metric_unit varchar(40) NOT NULL DEFAULT 'count',
			evidence_json longtext NULL,
			evidence_hash char(64) NOT NULL DEFAULT '',
			owner_user_id bigint(20) unsigned NULL,
			review_due_at datetime NULL,
			action_summary longtext NULL,
			decision_note longtext NULL,
			row_version int(10) unsigned NOT NULL DEFAULT 0,
			created_by bigint(20) unsigned NULL,
			reviewed_by bigint(20) unsigned NULL,
			reviewed_at datetime NULL,
			closed_at datetime NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			KEY finding_type (finding_type),
			KEY severity (severity),
			KEY status (status),
			KEY service_key (service_key),
			KEY product_key (product_key),
			KEY component_key (component_key),
			KEY review_due_at (review_due_at),
			KEY created_at (created_at)
		) {$charset_collate};";

		$sql_service_intelligence_events = "CREATE TABLE {$service_intelligence_events} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			finding_id bigint(20) unsigned NOT NULL,
			event_type varchar(80) NOT NULL DEFAULT '',
			from_status varchar(20) NOT NULL DEFAULT '',
			to_status varchar(20) NOT NULL DEFAULT '',
			actor_type varchar(20) NOT NULL DEFAULT 'system',
			actor_id bigint(20) unsigned NULL,
			context_json longtext NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			KEY finding_id (finding_id),
			KEY event_type (event_type),
			KEY actor_id (actor_id),
			KEY created_at (created_at)
		) {$charset_collate};";


		$sql_billing_profiles = "CREATE TABLE {$billing_profiles} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			engagement_id bigint(20) unsigned NOT NULL,
			organization_name varchar(191) NOT NULL DEFAULT '',
			billing_contact_name varchar(191) NOT NULL DEFAULT '',
			billing_contact_email varchar(191) NOT NULL DEFAULT '',
			billing_address_json longtext NULL,
			tax_identifier_reference varchar(191) NOT NULL DEFAULT '',
			currency char(3) NOT NULL DEFAULT 'USD',
			payment_terms_days smallint(5) unsigned NOT NULL DEFAULT 30,
			status varchar(30) NOT NULL DEFAULT 'active',
			sender_visible tinyint(1) unsigned NOT NULL DEFAULT 0,
			row_version int(10) unsigned NOT NULL DEFAULT 0,
			created_by bigint(20) unsigned NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id), UNIQUE KEY public_id (public_id), KEY inquiry_id (inquiry_id), KEY engagement_id (engagement_id), KEY status (status)
		) {$charset_collate};";

		$sql_invoices = "CREATE TABLE {$invoices} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			invoice_number varchar(60) NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			engagement_id bigint(20) unsigned NOT NULL,
			billing_profile_id bigint(20) unsigned NOT NULL,
			proposal_id bigint(20) unsigned NULL,
			sow_id bigint(20) unsigned NULL,
			status varchar(30) NOT NULL DEFAULT 'draft',
			currency char(3) NOT NULL DEFAULT 'USD',
			subtotal_minor bigint(20) unsigned NOT NULL DEFAULT 0,
			tax_minor bigint(20) unsigned NOT NULL DEFAULT 0,
			total_minor bigint(20) unsigned NOT NULL DEFAULT 0,
			amount_paid_minor bigint(20) unsigned NOT NULL DEFAULT 0,
			balance_due_minor bigint(20) unsigned NOT NULL DEFAULT 0,
			issued_at datetime NULL, due_at datetime NULL, paid_at datetime NULL, voided_at datetime NULL,
			sender_visible tinyint(1) unsigned NOT NULL DEFAULT 0,
			memo longtext NULL, internal_note longtext NULL,
			current_version int(10) unsigned NOT NULL DEFAULT 0,
			row_version int(10) unsigned NOT NULL DEFAULT 0,
			created_by bigint(20) unsigned NULL,
			created_at datetime NOT NULL, updated_at datetime NOT NULL,
			PRIMARY KEY  (id), UNIQUE KEY public_id (public_id), UNIQUE KEY invoice_number (invoice_number), KEY inquiry_id (inquiry_id), KEY engagement_id (engagement_id), KEY billing_profile_id (billing_profile_id), KEY status (status), KEY due_at (due_at)
		) {$charset_collate};";

		$sql_invoice_items = "CREATE TABLE {$invoice_items} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT, public_id char(36) NOT NULL, invoice_id bigint(20) unsigned NOT NULL,
			line_number int(10) unsigned NOT NULL, item_type varchar(40) NOT NULL DEFAULT 'service', description longtext NOT NULL,
			quantity decimal(12,4) NOT NULL DEFAULT 1.0000, unit_amount_minor bigint(20) unsigned NOT NULL DEFAULT 0, amount_minor bigint(20) unsigned NOT NULL DEFAULT 0,
			tax_code varchar(80) NOT NULL DEFAULT '', metadata_json longtext NULL, created_by bigint(20) unsigned NULL, created_at datetime NOT NULL, updated_at datetime NOT NULL,
			PRIMARY KEY  (id), UNIQUE KEY public_id (public_id), UNIQUE KEY invoice_line (invoice_id,line_number), KEY invoice_id (invoice_id)
		) {$charset_collate};";

		$sql_invoice_versions = "CREATE TABLE {$invoice_versions} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT, public_id char(36) NOT NULL, invoice_id bigint(20) unsigned NOT NULL, version_number int(10) unsigned NOT NULL,
			snapshot_json longtext NOT NULL, content_hash char(64) NOT NULL, status varchar(30) NOT NULL DEFAULT 'issued', created_by bigint(20) unsigned NULL, created_at datetime NOT NULL,
			PRIMARY KEY  (id), UNIQUE KEY public_id (public_id), UNIQUE KEY invoice_version (invoice_id,version_number), KEY content_hash (content_hash)
		) {$charset_collate};";

		$sql_payment_handoffs = "CREATE TABLE {$payment_handoffs} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT, public_id char(36) NOT NULL, schema varchar(80) NOT NULL DEFAULT 'sc-payment-handoff/1.0', invoice_id bigint(20) unsigned NOT NULL, inquiry_id bigint(20) unsigned NOT NULL,
			provider varchar(40) NOT NULL DEFAULT 'manual', provider_reference varchar(191) NOT NULL DEFAULT '', checkout_url text NULL, status varchar(30) NOT NULL DEFAULT 'pending',
			amount_minor bigint(20) unsigned NOT NULL DEFAULT 0, currency char(3) NOT NULL DEFAULT 'USD', idempotency_key char(64) NOT NULL,
			expires_at datetime NULL, authorized_at datetime NULL, settled_at datetime NULL, failed_at datetime NULL, refunded_at datetime NULL, last_event_at datetime NULL,
			sender_visible tinyint(1) unsigned NOT NULL DEFAULT 0, metadata_json longtext NULL, created_by bigint(20) unsigned NULL, created_at datetime NOT NULL, updated_at datetime NOT NULL,
			PRIMARY KEY  (id), UNIQUE KEY public_id (public_id), UNIQUE KEY idempotency_key (idempotency_key), KEY invoice_id (invoice_id), KEY inquiry_id (inquiry_id), KEY status (status), KEY provider_reference (provider_reference)
		) {$charset_collate};";

		$sql_billing_events = "CREATE TABLE {$billing_events} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT, public_id char(36) NOT NULL, invoice_id bigint(20) unsigned NULL, payment_handoff_id bigint(20) unsigned NULL, inquiry_id bigint(20) unsigned NOT NULL,
			event_type varchar(80) NOT NULL, from_status varchar(30) NOT NULL DEFAULT '', to_status varchar(30) NOT NULL DEFAULT '', actor_type varchar(20) NOT NULL DEFAULT 'system', actor_id bigint(20) unsigned NULL,
			context_json longtext NULL, immutable_hash char(64) NOT NULL, created_at datetime NOT NULL,
			PRIMARY KEY  (id), UNIQUE KEY public_id (public_id), UNIQUE KEY immutable_hash (immutable_hash), KEY invoice_id (invoice_id), KEY payment_handoff_id (payment_handoff_id), KEY inquiry_id (inquiry_id), KEY event_type (event_type), KEY created_at (created_at)
		) {$charset_collate};";


		$sql_engagement_dossiers = "CREATE TABLE {$engagement_dossiers} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			reference varchar(40) NOT NULL DEFAULT '',
			route_group varchar(40) NOT NULL DEFAULT 'general',
			phase varchar(40) NOT NULL DEFAULT 'intake',
			health_status varchar(30) NOT NULL DEFAULT 'healthy',
			owner_user_id bigint(20) unsigned NULL,
			sender_summary longtext NULL,
			sender_next_step longtext NULL,
			relationship_count int(10) unsigned NOT NULL DEFAULT 0,
			activity_count int(10) unsigned NOT NULL DEFAULT 0,
			content_hash char(64) NOT NULL DEFAULT '',
			row_version int(10) unsigned NOT NULL DEFAULT 0,
			last_refreshed_at datetime NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY public_id (public_id), UNIQUE KEY inquiry_id (inquiry_id), KEY reference (reference), KEY route_group (route_group), KEY phase (phase), KEY health_status (health_status), KEY owner_user_id (owner_user_id), KEY updated_at (updated_at)
		) {$charset_collate};";

		$sql_dossier_relationships = "CREATE TABLE {$dossier_relationships} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			dossier_id bigint(20) unsigned NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			entity_type varchar(50) NOT NULL DEFAULT '',
			entity_id bigint(20) unsigned NOT NULL,
			entity_public_id varchar(191) NOT NULL DEFAULT '',
			relation_type varchar(50) NOT NULL DEFAULT 'belongs_to',
			entity_status varchar(60) NOT NULL DEFAULT '',
			sender_visible tinyint(1) unsigned NOT NULL DEFAULT 0,
			metadata_json longtext NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY public_id (public_id), UNIQUE KEY dossier_entity (dossier_id,entity_type,entity_id), KEY inquiry_id (inquiry_id), KEY entity_type (entity_type), KEY entity_public_id (entity_public_id), KEY sender_visible (sender_visible)
		) {$charset_collate};";

		$sql_dossier_events = "CREATE TABLE {$dossier_events} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			dossier_id bigint(20) unsigned NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			event_type varchar(80) NOT NULL DEFAULT '',
			object_type varchar(50) NOT NULL DEFAULT '',
			object_id bigint(20) unsigned NULL,
			visibility varchar(20) NOT NULL DEFAULT 'internal',
			summary text NULL,
			context_json longtext NULL,
			actor_user_id bigint(20) unsigned NULL,
			occurred_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY public_id (public_id), KEY dossier_id (dossier_id), KEY inquiry_id (inquiry_id), KEY event_type (event_type), KEY object_type (object_type), KEY object_id (object_id), KEY visibility (visibility), KEY occurred_at (occurred_at)
		) {$charset_collate};";

		$sql_platform_handoffs = "CREATE TABLE {$platform_handoffs} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			schema varchar(100) NOT NULL DEFAULT 'sc-engagement-platform-handoff/2.0',
			handoff_key char(64) NOT NULL,
			source_system varchar(80) NOT NULL DEFAULT 'manual',
			target_module varchar(80) NOT NULL DEFAULT 'intake',
			inquiry_id bigint(20) unsigned NULL,
			route_group varchar(40) NOT NULL DEFAULT 'general',
			status varchar(30) NOT NULL DEFAULT 'pending',
			payload_json longtext NULL,
			content_hash char(64) NOT NULL DEFAULT '',
			received_by bigint(20) unsigned NULL,
			received_at datetime NOT NULL,
			processed_at datetime NULL,
			error_code varchar(120) NOT NULL DEFAULT '',
			error_message text NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY public_id (public_id), UNIQUE KEY handoff_key (handoff_key), KEY source_system (source_system), KEY target_module (target_module), KEY inquiry_id (inquiry_id), KEY route_group (route_group), KEY status (status), KEY received_at (received_at)
		) {$charset_collate};";

		$sql_health_events = "CREATE TABLE {$health_events} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			fingerprint char(64) NOT NULL,
			component varchar(40) NOT NULL DEFAULT 'plugin',
			event_type varchar(100) NOT NULL DEFAULT '',
			severity varchar(20) NOT NULL DEFAULT 'warning',
			message text NULL,
			context_json longtext NULL,
			occurrences bigint(20) unsigned NOT NULL DEFAULT 1,
			first_seen_at datetime NOT NULL,
			last_seen_at datetime NOT NULL,
			resolved_at datetime NULL,
			resolved_by bigint(20) unsigned NULL,
			resolution_note text NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY fingerprint (fingerprint),
			KEY component (component),
			KEY event_type (event_type),
			KEY severity (severity),
			KEY last_seen_at (last_seen_at),
			KEY resolved_at (resolved_at),
			KEY resolved_by (resolved_by)
		) {$charset_collate};";

		$sql_rate_limits = "CREATE TABLE {$rate_limits} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			bucket_hash char(64) NOT NULL,
			scope varchar(80) NOT NULL DEFAULT '',
			window_start datetime NOT NULL,
			window_seconds int(10) unsigned NOT NULL DEFAULT 3600,
			hits int(10) unsigned NOT NULL DEFAULT 0,
			blocked_until datetime NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY scope_bucket_window (scope, bucket_hash, window_start),
			KEY scope (scope),
			KEY blocked_until (blocked_until),
			KEY updated_at (updated_at)
		) {$charset_collate};";


		$sql_workflow_cases = "CREATE TABLE {$workflow_cases} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			reference varchar(40) NOT NULL DEFAULT '',
			current_stage varchar(40) NOT NULL DEFAULT 'intake',
			current_state varchar(50) NOT NULL DEFAULT 'new',
			terminal_state varchar(40) NOT NULL DEFAULT '',
			priority varchar(20) NOT NULL DEFAULT 'normal',
			owner_user_id bigint(20) unsigned NULL,
			source_updated_at datetime NULL,
			projection_version int(10) unsigned NOT NULL DEFAULT 1,
			projection_hash char(64) NOT NULL DEFAULT '',
			blocker_count int(10) unsigned NOT NULL DEFAULT 0,
			open_command_count int(10) unsigned NOT NULL DEFAULT 0,
			pending_handoff_count int(10) unsigned NOT NULL DEFAULT 0,
			last_event_at datetime NULL,
			last_transition_at datetime NULL,
			last_synced_at datetime NOT NULL,
			stale_after datetime NULL,
			consistency_status varchar(20) NOT NULL DEFAULT 'consistent',
			consistency_notes longtext NULL,
			row_version int(10) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY inquiry_id (inquiry_id),
			KEY reference (reference),
			KEY current_stage (current_stage),
			KEY current_state (current_state),
			KEY terminal_state (terminal_state),
			KEY priority (priority),
			KEY owner_user_id (owner_user_id),
			KEY consistency_status (consistency_status),
			KEY stale_after (stale_after),
			KEY updated_at (updated_at)
		) {$charset_collate};";

		$sql_workflow_commands = "CREATE TABLE {$workflow_commands} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			command_key char(64) NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			case_id bigint(20) unsigned NOT NULL,
			command_type varchar(60) NOT NULL DEFAULT '',
			target_type varchar(40) NOT NULL DEFAULT 'case',
			target_id bigint(20) unsigned NOT NULL DEFAULT 0,
			expected_stage varchar(40) NOT NULL DEFAULT '',
			requested_by bigint(20) unsigned NULL,
			reason text NULL,
			payload_json longtext NULL,
			payload_hash char(64) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'pending',
			claimed_at datetime NULL,
			claimed_by bigint(20) unsigned NULL,
			completed_at datetime NULL,
			result_json longtext NULL,
			error_code varchar(120) NOT NULL DEFAULT '',
			error_message text NULL,
			row_version int(10) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY command_key (command_key),
			KEY inquiry_id (inquiry_id),
			KEY case_id (case_id),
			KEY command_type (command_type),
			KEY target_type_id (target_type, target_id),
			KEY status (status),
			KEY requested_by (requested_by),
			KEY created_at (created_at)
		) {$charset_collate};";

		$sql_workflow_handoffs = "CREATE TABLE {$workflow_handoffs} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			handoff_key char(64) NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			case_id bigint(20) unsigned NOT NULL,
			target varchar(60) NOT NULL DEFAULT '',
			schema_id varchar(120) NOT NULL DEFAULT '',
			contract_version varchar(30) NOT NULL DEFAULT '',
			data_classification varchar(40) NOT NULL DEFAULT 'operational_minimum',
			status varchar(20) NOT NULL DEFAULT 'prepared',
			payload_json longtext NOT NULL,
			content_hash char(64) NOT NULL,
			signature char(64) NOT NULL,
			prepared_by bigint(20) unsigned NULL,
			prepared_at datetime NOT NULL,
			dispatched_at datetime NULL,
			acknowledged_by bigint(20) unsigned NULL,
			acknowledged_at datetime NULL,
			failed_at datetime NULL,
			failure_code varchar(120) NOT NULL DEFAULT '',
			failure_message text NULL,
			expires_at datetime NULL,
			row_version int(10) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY handoff_key (handoff_key),
			KEY inquiry_id (inquiry_id),
			KEY case_id (case_id),
			KEY target (target),
			KEY status (status),
			KEY content_hash (content_hash),
			KEY prepared_by (prepared_by),
			KEY expires_at (expires_at),
			KEY created_at (created_at)
		) {$charset_collate};";

		$sql_workflow_outbox = "CREATE TABLE {$workflow_outbox} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			event_key char(64) NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			case_id bigint(20) unsigned NOT NULL,
			event_type varchar(80) NOT NULL DEFAULT '',
			aggregate_type varchar(40) NOT NULL DEFAULT 'case',
			aggregate_id bigint(20) unsigned NOT NULL DEFAULT 0,
			target varchar(60) NOT NULL DEFAULT '',
			payload_json longtext NOT NULL,
			payload_hash char(64) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			available_at datetime NOT NULL,
			claimed_at datetime NULL,
			claim_token char(36) NOT NULL DEFAULT '',
			attempts int(10) unsigned NOT NULL DEFAULT 0,
			max_attempts int(10) unsigned NOT NULL DEFAULT 6,
			dispatched_at datetime NULL,
			acknowledged_at datetime NULL,
			error_code varchar(120) NOT NULL DEFAULT '',
			error_message text NULL,
			row_version int(10) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY event_key (event_key),
			KEY inquiry_id (inquiry_id),
			KEY case_id (case_id),
			KEY event_type (event_type),
			KEY aggregate_type_id (aggregate_type, aggregate_id),
			KEY target (target),
			KEY status_available (status, available_at),
			KEY claimed_at (claimed_at),
			KEY created_at (created_at)
		) {$charset_collate};";


		$sql_platform_snapshots = "CREATE TABLE {$platform_snapshots} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			snapshot_version int(10) unsigned NOT NULL DEFAULT 1,
			launch_state varchar(30) NOT NULL DEFAULT 'setup',
			readiness_score int(10) unsigned NOT NULL DEFAULT 0,
			required_failures int(10) unsigned NOT NULL DEFAULT 0,
			warning_count int(10) unsigned NOT NULL DEFAULT 0,
			payload_json longtext NOT NULL,
			content_hash char(64) NOT NULL,
			source varchar(30) NOT NULL DEFAULT 'manual',
			generated_by bigint(20) unsigned NULL,
			generated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			KEY launch_state (launch_state),
			KEY readiness_score (readiness_score),
			KEY content_hash (content_hash),
			KEY generated_by (generated_by),
			KEY generated_at (generated_at)
		) {$charset_collate};";

		$sql_platform_migrations = "CREATE TABLE {$platform_migrations} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			migration_key varchar(120) NOT NULL,
			from_version varchar(30) NOT NULL DEFAULT '',
			to_version varchar(30) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'processing',
			schema_hash char(64) NOT NULL DEFAULT '',
			context_json longtext NULL,
			started_at datetime NOT NULL,
			completed_at datetime NULL,
			error_code varchar(120) NOT NULL DEFAULT '',
			error_message text NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY migration_key (migration_key),
			KEY status (status),
			KEY schema_hash (schema_hash),
			KEY completed_at (completed_at),
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
			portal_visibility varchar(20) NOT NULL DEFAULT 'hidden',
			portal_published_at datetime NULL,
			portal_published_by bigint(20) unsigned NULL,
			portal_source varchar(40) NOT NULL DEFAULT '',
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



		$sql_lifecycle_events = "CREATE TABLE {$lifecycle_events} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			event_type varchar(80) NOT NULL DEFAULT '',
			from_stage varchar(60) NOT NULL DEFAULT '',
			to_stage varchar(60) NOT NULL DEFAULT '',
			actor_user_id bigint(20) unsigned NULL,
			payload_json longtext NULL,
			occurred_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			KEY inquiry_id (inquiry_id),
			KEY event_type (event_type),
			KEY from_stage (from_stage),
			KEY to_stage (to_stage),
			KEY actor_user_id (actor_user_id),
			KEY occurred_at (occurred_at)
		) {$charset_collate};";

		$sql_lifecycle_notes = "CREATE TABLE {$lifecycle_notes} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			note_type varchar(40) NOT NULL DEFAULT 'internal',
			note_body longtext NULL,
			is_sensitive tinyint(1) unsigned NOT NULL DEFAULT 0,
			created_by bigint(20) unsigned NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			KEY inquiry_id (inquiry_id),
			KEY note_type (note_type),
			KEY is_sensitive (is_sensitive),
			KEY created_by (created_by),
			KEY created_at (created_at)
		) {$charset_collate};";

		$sql_lifecycle_tasks = "CREATE TABLE {$lifecycle_tasks} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			task_title varchar(255) NOT NULL DEFAULT '',
			task_details longtext NULL,
			task_status varchar(30) NOT NULL DEFAULT 'open',
			priority varchar(20) NOT NULL DEFAULT 'normal',
			due_at datetime NULL,
			assigned_user_id bigint(20) unsigned NULL,
			reminder_policy varchar(40) NOT NULL DEFAULT 'daily_when_due',
			last_reminded_at datetime NULL,
			completed_at datetime NULL,
			completed_by bigint(20) unsigned NULL,
			created_by bigint(20) unsigned NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			KEY inquiry_id (inquiry_id),
			KEY task_status (task_status),
			KEY priority (priority),
			KEY due_at (due_at),
			KEY assigned_user_id (assigned_user_id),
			KEY last_reminded_at (last_reminded_at),
			KEY completed_at (completed_at),
			KEY created_by (created_by)
		) {$charset_collate};";


		$sql_support_cases = "CREATE TABLE {$support_cases} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			case_number varchar(40) NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			workflow_stage varchar(60) NOT NULL DEFAULT 'new_support_request',
			product varchar(80) NOT NULL DEFAULT 'other',
			product_version varchar(80) NOT NULL DEFAULT '',
			component varchar(120) NOT NULL DEFAULT '',
			issue_type varchar(80) NOT NULL DEFAULT 'other',
			environment_json longtext NULL,
			error_message longtext NULL,
			reproduction_steps longtext NULL,
			expected_behavior longtext NULL,
			actual_behavior longtext NULL,
			severity varchar(20) NOT NULL DEFAULT 'normal',
			priority varchar(20) NOT NULL DEFAULT 'normal',
			assigned_user_id bigint(20) unsigned NULL,
			source_system varchar(80) NOT NULL DEFAULT 'manual',
			source_reference varchar(191) NOT NULL DEFAULT '',
			known_issue_reference varchar(191) NOT NULL DEFAULT '',
			sender_summary longtext NULL,
			sender_next_step longtext NULL,
			row_version int(10) unsigned NOT NULL DEFAULT 0,
			created_by bigint(20) unsigned NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			resolved_at datetime NULL,
			closed_at datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY case_number (case_number),
			UNIQUE KEY inquiry_id (inquiry_id),
			KEY workflow_stage (workflow_stage),
			KEY product (product),
			KEY component (component),
			KEY issue_type (issue_type),
			KEY severity (severity),
			KEY assigned_user_id (assigned_user_id),
			KEY updated_at (updated_at)
		) {$charset_collate};";

		$sql_support_case_events = "CREATE TABLE {$support_case_events} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			support_case_id bigint(20) unsigned NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			event_type varchar(80) NOT NULL DEFAULT '',
			from_stage varchar(60) NOT NULL DEFAULT '',
			to_stage varchar(60) NOT NULL DEFAULT '',
			actor_user_id bigint(20) unsigned NULL,
			payload_json longtext NULL,
			occurred_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			KEY support_case_id (support_case_id),
			KEY inquiry_id (inquiry_id),
			KEY event_type (event_type),
			KEY occurred_at (occurred_at)
		) {$charset_collate};";

		$sql_support_case_links = "CREATE TABLE {$support_case_links} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			support_case_id bigint(20) unsigned NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			related_type varchar(60) NOT NULL DEFAULT '',
			related_reference varchar(191) NOT NULL DEFAULT '',
			relation_type varchar(60) NOT NULL DEFAULT 'related',
			title varchar(191) NOT NULL DEFAULT '',
			url text NULL,
			sender_visible tinyint(1) unsigned NOT NULL DEFAULT 0,
			metadata_json longtext NULL,
			created_by bigint(20) unsigned NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			KEY support_case_id (support_case_id),
			KEY inquiry_id (inquiry_id),
			KEY related_type (related_type),
			KEY related_reference (related_reference),
			KEY sender_visible (sender_visible)
		) {$charset_collate};";

		$sql_support_signals = "CREATE TABLE {$support_signals} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			signal_type varchar(80) NOT NULL DEFAULT '',
			product varchar(80) NOT NULL DEFAULT 'other',
			product_version varchar(80) NOT NULL DEFAULT '',
			component varchar(120) NOT NULL DEFAULT '',
			issue_type varchar(80) NOT NULL DEFAULT 'other',
			aggregate_key char(64) NOT NULL,
			occurrence_count int(10) unsigned NOT NULL DEFAULT 1,
			evidence_json longtext NULL,
			contains_personal_data tinyint(1) unsigned NOT NULL DEFAULT 0,
			status varchar(30) NOT NULL DEFAULT 'open',
			created_by bigint(20) unsigned NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY aggregate_key (aggregate_key),
			KEY signal_type (signal_type),
			KEY product (product),
			KEY component (component),
			KEY status (status),
			KEY updated_at (updated_at)
		) {$charset_collate};";


		$sql_client_workspaces = "CREATE TABLE {$client_workspaces} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			workspace_number varchar(40) NOT NULL,
			inquiry_id bigint(20) unsigned NOT NULL,
			engagement_id bigint(20) unsigned NOT NULL,
			title varchar(255) NOT NULL DEFAULT '',
			status varchar(30) NOT NULL DEFAULT 'draft',
			owner_user_id bigint(20) unsigned NULL,
			sender_summary longtext NULL,
			sender_next_step longtext NULL,
			sender_visible tinyint(1) unsigned NOT NULL DEFAULT 0,
			row_version int(10) unsigned NOT NULL DEFAULT 0,
			activated_at datetime NULL,
			paused_at datetime NULL,
			completed_at datetime NULL,
			created_by bigint(20) unsigned NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY public_id (public_id), UNIQUE KEY workspace_number (workspace_number), UNIQUE KEY engagement_id (engagement_id),
			KEY inquiry_id (inquiry_id), KEY status (status), KEY owner_user_id (owner_user_id), KEY sender_visible (sender_visible), KEY updated_at (updated_at)
		) {$charset_collate};";

		$sql_workspace_members = "CREATE TABLE {$workspace_members} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT, public_id char(36) NOT NULL, workspace_id bigint(20) unsigned NOT NULL, inquiry_id bigint(20) unsigned NOT NULL,
			member_type varchar(30) NOT NULL DEFAULT 'sender', user_id bigint(20) unsigned NULL, email_hash char(64) NOT NULL DEFAULT '', display_name varchar(191) NOT NULL DEFAULT '', role_label varchar(120) NOT NULL DEFAULT '',
			permissions_json longtext NULL, status varchar(20) NOT NULL DEFAULT 'active', invited_at datetime NULL, activated_at datetime NULL, revoked_at datetime NULL,
			created_by bigint(20) unsigned NULL, created_at datetime NOT NULL, updated_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY public_id (public_id), KEY workspace_id (workspace_id), KEY inquiry_id (inquiry_id), KEY member_type (member_type), KEY user_id (user_id), KEY email_hash (email_hash), KEY status (status)
		) {$charset_collate};";

		$sql_workspace_milestones = "CREATE TABLE {$workspace_milestones} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT, public_id char(36) NOT NULL, workspace_id bigint(20) unsigned NOT NULL, inquiry_id bigint(20) unsigned NOT NULL,
			title varchar(255) NOT NULL DEFAULT '', description longtext NULL, status varchar(30) NOT NULL DEFAULT 'planned', due_date date NULL, sender_visible tinyint(1) unsigned NOT NULL DEFAULT 0, sort_order int(10) unsigned NOT NULL DEFAULT 0,
			completed_by bigint(20) unsigned NULL, completed_at datetime NULL, created_by bigint(20) unsigned NULL, created_at datetime NOT NULL, updated_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY public_id (public_id), KEY workspace_id (workspace_id), KEY inquiry_id (inquiry_id), KEY status (status), KEY due_date (due_date), KEY sender_visible (sender_visible)
		) {$charset_collate};";

		$sql_workspace_deliverables = "CREATE TABLE {$workspace_deliverables} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT, public_id char(36) NOT NULL, workspace_id bigint(20) unsigned NOT NULL, inquiry_id bigint(20) unsigned NOT NULL,
			title varchar(255) NOT NULL DEFAULT '', description longtext NULL, status varchar(30) NOT NULL DEFAULT 'draft', due_date date NULL, sender_visible tinyint(1) unsigned NOT NULL DEFAULT 0,
			approval_required tinyint(1) unsigned NOT NULL DEFAULT 0, sender_decision varchar(30) NOT NULL DEFAULT 'pending', sender_decision_note longtext NULL, decided_at datetime NULL,
			current_version int(10) unsigned NOT NULL DEFAULT 1, attachment_id bigint(20) unsigned NULL, row_version int(10) unsigned NOT NULL DEFAULT 0,
			created_by bigint(20) unsigned NULL, created_at datetime NOT NULL, updated_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY public_id (public_id), KEY workspace_id (workspace_id), KEY inquiry_id (inquiry_id), KEY status (status), KEY due_date (due_date), KEY sender_visible (sender_visible), KEY sender_decision (sender_decision), KEY attachment_id (attachment_id)
		) {$charset_collate};";

		$sql_workspace_messages = "CREATE TABLE {$workspace_messages} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT, public_id char(36) NOT NULL, workspace_id bigint(20) unsigned NOT NULL, inquiry_id bigint(20) unsigned NOT NULL,
			direction varchar(20) NOT NULL DEFAULT 'outbound', sender_type varchar(20) NOT NULL DEFAULT 'staff', body_text longtext NULL, sender_visible tinyint(1) unsigned NOT NULL DEFAULT 0,
			related_deliverable_id bigint(20) unsigned NULL, created_by bigint(20) unsigned NULL, created_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY public_id (public_id), KEY workspace_id (workspace_id), KEY inquiry_id (inquiry_id), KEY sender_visible (sender_visible), KEY related_deliverable_id (related_deliverable_id), KEY created_at (created_at)
		) {$charset_collate};";

		$sql_workspace_documents = "CREATE TABLE {$workspace_documents} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT, public_id char(36) NOT NULL, workspace_id bigint(20) unsigned NOT NULL, inquiry_id bigint(20) unsigned NOT NULL, attachment_id bigint(20) unsigned NOT NULL,
			document_role varchar(40) NOT NULL DEFAULT 'shared_document', title varchar(255) NOT NULL DEFAULT '', version_label varchar(80) NOT NULL DEFAULT '', sender_visible tinyint(1) unsigned NOT NULL DEFAULT 0,
			related_deliverable_id bigint(20) unsigned NULL, created_by bigint(20) unsigned NULL, created_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY public_id (public_id), UNIQUE KEY workspace_attachment (workspace_id,attachment_id), KEY workspace_id (workspace_id), KEY inquiry_id (inquiry_id), KEY attachment_id (attachment_id), KEY sender_visible (sender_visible)
		) {$charset_collate};";

		$sql_workspace_events = "CREATE TABLE {$workspace_events} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT, public_id char(36) NOT NULL, workspace_id bigint(20) unsigned NOT NULL, inquiry_id bigint(20) unsigned NOT NULL,
			event_type varchar(80) NOT NULL DEFAULT '', object_type varchar(40) NOT NULL DEFAULT '', object_id bigint(20) unsigned NULL, from_status varchar(30) NOT NULL DEFAULT '', to_status varchar(30) NOT NULL DEFAULT '',
			actor_type varchar(20) NOT NULL DEFAULT 'system', actor_id bigint(20) unsigned NULL, context_json longtext NULL, created_at datetime NOT NULL,
			PRIMARY KEY (id), UNIQUE KEY public_id (public_id), KEY workspace_id (workspace_id), KEY inquiry_id (inquiry_id), KEY event_type (event_type), KEY object_type (object_type), KEY object_id (object_id), KEY created_at (created_at)
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

		// Recovery-critical tables are created with native SQL first. dbDelta()
		// still runs below for normal schema reconciliation, but a prior disk-full
		// or interrupted upgrade can no longer leave these tables permanently
		// absent while column probes run on every request.
		self::create_table_if_missing( $proposal_approvals, $sql_proposal_approvals );
		self::create_table_if_missing( $platform_handoffs, $sql_platform_handoffs );

		dbDelta( $sql_inquiries );
		dbDelta( $sql_attachments );
		dbDelta( $sql_reviews );
		dbDelta( $sql_fit_assessments );
		dbDelta( $sql_fit_assessment_items );
		dbDelta( $sql_fit_assessment_reviews );
		dbDelta( $sql_portal_access );
		dbDelta( $sql_portal_sessions );
		dbDelta( $sql_portal_events );
		dbDelta( $sql_portal_recovery_requests );
		dbDelta( $sql_meeting_offers );
		dbDelta( $sql_meeting_reminders );
		dbDelta( $sql_graph_operations );
		dbDelta( $sql_proposals );
		dbDelta( $sql_proposal_versions );
		dbDelta( $sql_proposal_approvals );
		dbDelta( $sql_statements_of_work );
		dbDelta( $sql_statement_of_work_versions );
		dbDelta( $sql_change_requests );
		dbDelta( $sql_client_workspaces );
		dbDelta( $sql_workspace_members );
		dbDelta( $sql_workspace_milestones );
		dbDelta( $sql_workspace_deliverables );
		dbDelta( $sql_workspace_messages );
		dbDelta( $sql_workspace_documents );
		dbDelta( $sql_workspace_events );
		dbDelta( $sql_workflow_events );
		dbDelta( $sql_engagements );
		dbDelta( $sql_engagement_snapshots );
		dbDelta( $sql_engagement_requirements );
		dbDelta( $sql_engagement_events );
		dbDelta( $sql_analytics_snapshots );
		dbDelta( $sql_service_intelligence_findings );
		dbDelta( $sql_service_intelligence_events );
		dbDelta( $sql_billing_profiles );
		dbDelta( $sql_invoices );
		dbDelta( $sql_invoice_items );
		dbDelta( $sql_invoice_versions );
		dbDelta( $sql_payment_handoffs );
		dbDelta( $sql_billing_events );
		dbDelta( $sql_engagement_dossiers );
		dbDelta( $sql_dossier_relationships );
		dbDelta( $sql_dossier_events );
		dbDelta( $sql_platform_handoffs );
		dbDelta( $sql_health_events );
		dbDelta( $sql_rate_limits );
		dbDelta( $sql_workflow_cases );
		dbDelta( $sql_workflow_commands );
		dbDelta( $sql_workflow_handoffs );
		dbDelta( $sql_workflow_outbox );
		dbDelta( $sql_platform_snapshots );
		dbDelta( $sql_platform_migrations );
		dbDelta( $sql_communications );
		dbDelta( $sql_communication_events );
		dbDelta( $sql_communication_templates );
		dbDelta( $sql_lifecycle_events );
		dbDelta( $sql_lifecycle_notes );
		dbDelta( $sql_lifecycle_tasks );
		dbDelta( $sql_support_cases );
		dbDelta( $sql_support_case_events );
		dbDelta( $sql_support_case_links );
		dbDelta( $sql_support_signals );
		dbDelta( $sql_privacy_requests );
		dbDelta( $sql_consent_events );
		dbDelta( $sql_legal_holds );
		dbDelta( $sql_retention_policies );
		dbDelta( $sql_retention_actions );
		dbDelta( $sql_audit );

		self::$table_presence_cache = array();

		self::backfill_review_defaults();
		self::backfill_fit_defaults();
		self::backfill_portal_defaults();
		self::backfill_communication_defaults();
		self::backfill_privacy_defaults();
		self::backfill_lifecycle_defaults();

		$contract = self::required_contract();
		if ( ! in_array( false, $contract, true ) ) {
			update_option( 'sc_ei_db_version', SC_EI_DB_VERSION, false );
		} elseif ( class_exists( 'SC_EI_Hardening_Repository' ) ) {
			SC_EI_Hardening_Repository::record_event(
				'database',
				'database_contract_incomplete',
				'critical',
				'The database installer did not advance the stored database version because required tables or columns remain unavailable.',
				array(
					'missing_contract_items' => array_keys( array_filter( $contract, static fn( bool $available ): bool => ! $available ) ),
					'expected_db_version'    => SC_EI_DB_VERSION,
				)
			);
		}
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

	private static function backfill_fit_defaults(): void {
		global $wpdb;

		$table = self::table( 'inquiries' );
		$wpdb->query(
			"UPDATE {$table}
			SET fit_assessment_status = 'not_started',
				fit_assessment_version = 0
			WHERE fit_assessment_status IS NULL
				OR fit_assessment_status = ''" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	private static function backfill_portal_defaults(): void {
		global $wpdb;

		$table = self::table( 'inquiries' );
		$wpdb->query(
			"UPDATE {$table}
			SET portal_status = 'inactive',
				portal_message_count = 0,
				portal_document_count = 0,
				sender_withdrawal_status = 'none',
				portal_version = 0
			WHERE portal_status IS NULL
				OR portal_status = ''" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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


	private static function backfill_lifecycle_defaults(): void {
		if ( class_exists( 'SC_EI_Lifecycle_Repository' ) ) {
			SC_EI_Lifecycle_Repository::backfill_defaults();
		}
	}

	public static function maybe_upgrade(): void {
		$current = (string) get_option( 'sc_ei_db_version', '' );
		$critical = self::critical_tables_exist();
		if ( ! version_compare( $current, SC_EI_DB_VERSION, '<' ) && ! in_array( false, $critical, true ) ) {
			return;
		}

		$lock_key = 'sc_ei_database_upgrade_lock';
		if ( get_transient( $lock_key ) ) {
			return;
		}

		set_transient( $lock_key, time(), 5 * MINUTE_IN_SECONDS );
		try {
			self::install();
		} finally {
			delete_transient( $lock_key );
		}
	}

	/**
	 * Return the two tables whose absence previously caused an unbounded
	 * migration/error-log loop.
	 *
	 * @return array<string,bool>
	 */
	public static function critical_tables_exist(): array {
		$result = array();
		foreach ( array( 'proposal_approvals', 'platform_handoffs' ) as $name ) {
			$result[ $name ] = self::physical_table_exists( self::table( $name ) );
		}
		return $result;
	}

	public static function tables_exist(): array {
		$result = array();
		foreach ( array( 'inquiries', 'attachments', 'reviews', 'fit_assessments', 'fit_assessment_items', 'fit_assessment_reviews', 'portal_access', 'portal_sessions', 'portal_events', 'portal_recovery_requests', 'meeting_offers', 'meeting_reminders', 'graph_operations', 'proposals', 'proposal_versions', 'proposal_approvals', 'statements_of_work', 'statement_of_work_versions', 'change_requests', 'client_workspaces', 'workspace_members', 'workspace_milestones', 'workspace_deliverables', 'workspace_messages', 'workspace_documents', 'workspace_events', 'workflow_events', 'engagements', 'engagement_snapshots', 'engagement_requirements', 'engagement_events', 'analytics_snapshots', 'service_intelligence_findings', 'service_intelligence_events', 'billing_profiles', 'invoices', 'invoice_items', 'invoice_versions', 'payment_handoffs', 'billing_events', 'engagement_dossiers', 'dossier_relationships', 'dossier_events', 'platform_handoffs', 'health_events', 'rate_limits', 'workflow_cases', 'workflow_commands', 'workflow_handoffs', 'workflow_outbox', 'platform_snapshots', 'platform_migrations', 'communications', 'communication_events', 'communication_templates', 'lifecycle_events', 'lifecycle_notes', 'lifecycle_tasks', 'support_cases', 'support_case_events', 'support_case_links', 'support_signals', 'privacy_requests', 'consent_events', 'legal_holds', 'retention_policies', 'retention_actions', 'audit_log' ) as $name ) {
			$result[ $name ] = self::physical_table_exists( self::table( $name ) );
		}

		return $result;
	}

	private static function physical_table_exists( string $table ): bool {
		global $wpdb;

		if ( array_key_exists( $table, self::$table_presence_cache ) ) {
			return self::$table_presence_cache[ $table ];
		}

		$pattern = method_exists( $wpdb, 'esc_like' ) ? $wpdb->esc_like( $table ) : $table;
		$found   = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $pattern ) );
		self::$table_presence_cache[ $table ] = ( $found === $table );
		return self::$table_presence_cache[ $table ];
	}

	private static function column_exists( string $table, string $column ): bool {
		global $wpdb;

		if ( ! self::physical_table_exists( $table ) ) {
			return false;
		}

		$found = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", $column ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $found === $column;
	}

	private static function create_table_if_missing( string $table, string $sql ): bool {
		global $wpdb;

		if ( self::physical_table_exists( $table ) ) {
			return true;
		}

		$create_sql = preg_replace( '/^CREATE TABLE\s+/i', 'CREATE TABLE IF NOT EXISTS ', ltrim( $sql ), 1 );
		if ( ! is_string( $create_sql ) || '' === $create_sql ) {
			return false;
		}

		$previous_suppression = $wpdb->suppress_errors( true );
		$wpdb->query( $create_sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->suppress_errors( $previous_suppression );
		unset( self::$table_presence_cache[ $table ] );

		return self::physical_table_exists( $table );
	}

	/**
	 * Return the minimum write-path database contract required for new inquiries.
	 *
	 * This intentionally combines table, inquiry-column, platform, and lifecycle
	 * evidence so activation and readiness cannot report a current database
	 * version while the public inquiry insert path is incomplete.
	 */
	public static function required_contract(): array {
		$result = array();
		foreach ( self::tables_exist() as $key => $available ) {
			$result[ 'table.' . $key ] = $available;
		}
		foreach ( self::inquiry_columns_exist() as $key => $available ) {
			$result[ 'inquiries.' . $key ] = $available;
		}
		foreach ( self::platform_columns_exist() as $key => $available ) {
			$result[ 'platform.' . $key ] = $available;
		}
		foreach ( self::lifecycle_columns_exist() as $key => $available ) {
			$result[ 'lifecycle.' . $key ] = $available;
		}
		foreach ( self::support_columns_exist() as $key => $available ) {
			$result[ 'support.' . $key ] = $available;
		}
		foreach ( self::calendar_columns_exist() as $key => $available ) {
			$result[ 'calendar.' . $key ] = $available;
		}
		foreach ( self::proposal_governance_columns_exist() as $key => $available ) {
			$result[ 'proposal_governance.' . $key ] = $available;
		}
		foreach ( self::workspace_columns_exist() as $key => $available ) {
			$result[ 'workspace.' . $key ] = $available;
		}
		foreach ( self::service_intelligence_columns_exist() as $key => $available ) {
			$result[ 'service_intelligence.' . $key ] = $available;
		}
		foreach ( self::billing_columns_exist() as $key => $available ) {
			$result[ 'billing.' . $key ] = $available;
		}
		foreach ( self::unified_platform_columns_exist() as $key => $available ) {
			$result[ 'unified_platform.' . $key ] = $available;
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
			'fit_assessment_status',
			'current_fit_assessment_id',
			'fit_assessment_updated_at',
			'fit_assessment_finalized_at',
			'fit_assessment_version',
			'portal_status',
			'portal_access_id',
			'portal_last_activity_at',
			'portal_message_count',
			'portal_document_count',
			'portal_last_sender_message_at',
			'sender_withdrawal_status',
			'sender_withdrawal_requested_at',
			'sender_withdrawal_reason',
			'portal_version',
			'lifecycle_stage',
			'lifecycle_owner_user_id',
			'lifecycle_priority',
			'next_action',
			'next_action_at',
			'qualification_status',
			'qualification_score',
			'qualification_json',
			'decision_authority',
			'funding_status',
			'stakeholder_summary',
			'systems_constraints',
			'data_security_requirements',
			'ai_assurance_applicable',
			'teams_readiness',
			'sender_lifecycle_summary',
			'lifecycle_version',
			'lifecycle_updated_at',
			'lifecycle_updated_by',
		);

		$result = array();
		foreach ( $columns as $column ) {
			$found             = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", $column ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$result[ $column ] = ( $found === $column );
		}

		return $result;
	}


	public static function proposal_governance_columns_exist(): array {
		global $wpdb;
		$tables = array(
			'proposal_approvals' => array( 'public_id', 'schema', 'inquiry_id', 'proposal_id', 'proposal_version_id', 'sow_id', 'action', 'actor_type', 'actor_id', 'note', 'authority_attested', 'boundary_acknowledged', 'confirmation_hash', 'immutable_hash', 'created_at' ),
			'statements_of_work' => array( 'public_id', 'sow_number', 'inquiry_id', 'proposal_id', 'proposal_version_id', 'current_version_id', 'pending_version_id', 'status', 'sender_visible', 'approved_by', 'approved_at', 'sender_approved_at', 'row_version', 'created_by', 'created_at', 'updated_at' ),
			'statement_of_work_versions' => array( 'public_id', 'sow_id', 'version_number', 'title', 'purpose_background', 'scope_json', 'deliverables_json', 'milestones_json', 'responsibilities_json', 'dependencies_json', 'acceptance_criteria', 'change_control', 'communication_expectations', 'data_handling', 'ip_terms', 'open_source_boundaries', 'fees_payment', 'start_date', 'target_end_date', 'termination_conditions', 'attachment_ids_json', 'version_note', 'content_hash', 'created_by', 'created_at' ),
			'change_requests' => array( 'public_id', 'change_number', 'inquiry_id', 'proposal_id', 'proposal_version_id', 'sow_id', 'sow_version_id', 'engagement_id', 'status', 'requester_type', 'requester_id', 'request_summary', 'reason', 'scope_impact', 'timeline_impact', 'fee_impact_minor', 'currency', 'decision_note', 'decided_by', 'decided_at', 'applied_by', 'applied_at', 'row_version', 'created_at', 'updated_at' ),
		);
		$result = array();
		foreach ( $tables as $table_name => $columns ) {
			$table = self::table( $table_name );
			foreach ( $columns as $column ) {
				$result[ $table_name . '.' . $column ] = self::column_exists( $table, $column );
			}
		}
		return $result;
	}


	public static function workspace_columns_exist(): array {
		global $wpdb;
		$tables = array(
			'client_workspaces' => array( 'public_id','workspace_number','inquiry_id','engagement_id','title','status','owner_user_id','sender_summary','sender_next_step','sender_visible','row_version','activated_at','paused_at','completed_at','created_by','created_at','updated_at' ),
			'workspace_members' => array( 'public_id','workspace_id','inquiry_id','member_type','user_id','email_hash','display_name','role_label','permissions_json','status','invited_at','activated_at','revoked_at','created_by','created_at','updated_at' ),
			'workspace_milestones' => array( 'public_id','workspace_id','inquiry_id','title','description','status','due_date','sender_visible','sort_order','completed_by','completed_at','created_by','created_at','updated_at' ),
			'workspace_deliverables' => array( 'public_id','workspace_id','inquiry_id','title','description','status','due_date','sender_visible','approval_required','sender_decision','sender_decision_note','decided_at','current_version','attachment_id','row_version','created_by','created_at','updated_at' ),
			'workspace_messages' => array( 'public_id','workspace_id','inquiry_id','direction','sender_type','body_text','sender_visible','related_deliverable_id','created_by','created_at' ),
			'workspace_documents' => array( 'public_id','workspace_id','inquiry_id','attachment_id','document_role','title','version_label','sender_visible','related_deliverable_id','created_by','created_at' ),
			'workspace_events' => array( 'public_id','workspace_id','inquiry_id','event_type','object_type','object_id','from_status','to_status','actor_type','actor_id','context_json','created_at' ),
		);
		$result=array();
		foreach($tables as $table_name=>$columns){$table=self::table($table_name);foreach($columns as $column){$found=$wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s",$column));$result[$table_name.'.'.$column]=($found===$column);}}
		return $result;
	}


	public static function service_intelligence_columns_exist(): array {
		global $wpdb;
		$tables = array(
			'service_intelligence_findings' => array( 'public_id','schema','finding_type','severity','status','title','service_key','product_key','component_key','period_start','period_end','cohort_count','metric_value','metric_unit','evidence_json','evidence_hash','owner_user_id','review_due_at','action_summary','decision_note','row_version','created_by','reviewed_by','reviewed_at','closed_at','created_at','updated_at' ),
			'service_intelligence_events' => array( 'public_id','finding_id','event_type','from_status','to_status','actor_type','actor_id','context_json','created_at' ),
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


	public static function billing_columns_exist(): array {
		global $wpdb;
		$tables = array(
			'billing_profiles' => array( 'public_id','inquiry_id','engagement_id','organization_name','billing_contact_name','billing_contact_email','billing_address_json','tax_identifier_reference','currency','payment_terms_days','status','sender_visible','row_version','created_by','created_at','updated_at' ),
			'invoices' => array( 'public_id','invoice_number','inquiry_id','engagement_id','billing_profile_id','proposal_id','sow_id','status','currency','subtotal_minor','tax_minor','total_minor','amount_paid_minor','balance_due_minor','issued_at','due_at','paid_at','voided_at','sender_visible','memo','internal_note','current_version','row_version','created_by','created_at','updated_at' ),
			'invoice_items' => array( 'public_id','invoice_id','line_number','item_type','description','quantity','unit_amount_minor','amount_minor','tax_code','metadata_json','created_by','created_at','updated_at' ),
			'invoice_versions' => array( 'public_id','invoice_id','version_number','snapshot_json','content_hash','status','created_by','created_at' ),
			'payment_handoffs' => array( 'public_id','schema','invoice_id','inquiry_id','provider','provider_reference','checkout_url','status','amount_minor','currency','idempotency_key','expires_at','authorized_at','settled_at','failed_at','refunded_at','last_event_at','sender_visible','metadata_json','created_by','created_at','updated_at' ),
			'billing_events' => array( 'public_id','invoice_id','payment_handoff_id','inquiry_id','event_type','from_status','to_status','actor_type','actor_id','context_json','immutable_hash','created_at' ),
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


	public static function unified_platform_columns_exist(): array {
		global $wpdb;
		$tables = array(
			'engagement_dossiers' => array( 'public_id','inquiry_id','reference','route_group','phase','health_status','owner_user_id','sender_summary','sender_next_step','relationship_count','activity_count','content_hash','row_version','last_refreshed_at','created_at','updated_at' ),
			'dossier_relationships' => array( 'public_id','dossier_id','inquiry_id','entity_type','entity_id','entity_public_id','relation_type','entity_status','sender_visible','metadata_json','created_at','updated_at' ),
			'dossier_events' => array( 'public_id','dossier_id','inquiry_id','event_type','object_type','object_id','visibility','summary','context_json','actor_user_id','occurred_at' ),
			'platform_handoffs' => array( 'public_id','schema','handoff_key','source_system','target_module','inquiry_id','route_group','status','payload_json','content_hash','received_by','received_at','processed_at','error_code','error_message','created_at','updated_at' ),
		);
		$result = array();
		foreach ( $tables as $table_name => $columns ) {
			$table = self::table( $table_name );
			foreach ( $columns as $column ) {
				$result[ $table_name . '.' . $column ] = self::column_exists( $table, $column );
			}
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
				'dedupe_key', 'metadata_json', 'portal_visibility', 'portal_published_at',
				'portal_published_by', 'portal_source', 'row_version', 'created_at', 'updated_at', 'deleted_at',
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

	public static function fit_columns_exist(): array {
		global $wpdb;

		$tables = array(
			'fit_assessments' => array(
				'public_id', 'inquiry_id', 'assessment_version', 'parent_assessment_id',
				'assessor_user_id', 'status', 'recommendation', 'confidence', 'service_route',
				'scope_boundary', 'advisory_score', 'score_complete', 'material_concern_count',
				'second_review_required', 'second_review_reason', 'second_reviewer_user_id',
				'second_review_disposition', 'second_reviewed_at', 'overall_summary',
				'recommendation_rationale', 'limitations_notes', 'conditions_for_fit',
				'referral_notes', 'human_attestation', 'assistance_disclosure',
				'assistance_notes', 'submitted_at', 'finalized_by', 'finalized_at',
				'superseded_at', 'row_version', 'created_at', 'updated_at',
			),
			'fit_assessment_items' => array(
				'assessment_id', 'criterion_key', 'criterion_group', 'rating', 'weight',
				'numeric_value', 'is_applicable', 'is_material_concern', 'evidence_note',
				'concern_note', 'source_refs_json', 'row_version', 'created_at', 'updated_at',
			),
			'fit_assessment_reviews' => array(
				'public_id', 'assessment_id', 'reviewer_user_id', 'disposition',
				'recommendation', 'service_route', 'scope_boundary', 'review_notes',
				'required_changes', 'conflict_disclosure', 'human_attestation', 'created_at',
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

	public static function portal_columns_exist(): array {
		global $wpdb;

		$tables = array(
			'portal_access' => array(
				'public_id', 'inquiry_id', 'status', 'sender_email_hash', 'invite_token_hash',
				'invite_token_prefix', 'invite_expires_at', 'invite_used_at', 'permissions_json',
				'terms_version', 'terms_accepted_at', 'invited_by', 'invitation_note',
				'activated_at', 'suspended_at', 'revoked_at', 'revoked_by', 'revocation_reason',
				'last_access_at', 'last_ip_hash', 'last_user_agent_hash', 'failed_attempts',
				'locked_until', 'row_version', 'created_at', 'updated_at',
			),
			'portal_sessions' => array(
				'public_id', 'access_id', 'inquiry_id', 'session_hash', 'status',
				'expires_at', 'idle_expires_at', 'last_seen_at', 'ip_hash',
				'user_agent_hash', 'activity_count', 'rotated_from_id', 'revoked_at',
				'revoked_reason', 'created_at', 'updated_at',
			),
			'portal_events' => array(
				'public_id', 'inquiry_id', 'access_id', 'session_id', 'event_type',
				'target_type', 'target_id', 'outcome', 'ip_hash', 'user_agent_hash',
				'context_json', 'created_at',
			),
			'portal_recovery_requests' => array(
				'public_id', 'inquiry_id', 'access_id', 'status', 'match_status',
				'reference_hash', 'email_hash', 'recovery_reason', 'request_ip_hash',
				'request_user_agent_hash', 'request_count', 'requested_at',
				'last_requested_at', 'expires_at', 'reviewed_by', 'reviewed_at',
				'decision_note', 'completed_at', 'row_version', 'created_at', 'updated_at',
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

	public static function workflow_columns_exist(): array {
		global $wpdb;

		$tables = array(
			'meeting_offers' => array(
				'public_id', 'inquiry_id', 'access_id', 'offer_number', 'status', 'title',
				'purpose', 'meeting_type', 'organizer_name', 'organizer_email',
				'participant_emails_json', 'agenda', 'preparation_requests', 'sender_summary',
				'sender_next_step', 'related_document_ids_json', 'duration_minutes', 'timezone',
				'slots_json', 'selected_slot_key', 'selected_start_utc', 'selected_end_utc',
				'teams_url', 'calendar_provider', 'external_calendar_reference',
				'previous_start_utc', 'previous_end_utc', 'reschedule_count',
				'last_rescheduled_at', 'last_rescheduled_by', 'join_url_revoked_at', 'graph_sync_status',
				'graph_transaction_id', 'graph_event_id', 'graph_i_cal_uid', 'graph_change_key',
				'graph_etag', 'graph_web_link', 'graph_join_url', 'graph_organizer',
				'graph_calendar_id', 'graph_payload_hash', 'graph_remote_start_utc',
				'graph_remote_end_utc', 'graph_last_request_id', 'graph_last_client_request_id',
				'graph_last_error_code', 'graph_last_error_message', 'graph_attempt_count',
				'graph_last_attempt_at', 'graph_last_success_at', 'graph_next_retry_at',
				'graph_reconciled_at', 'graph_deleted_at', 'sender_note',
				'alternative_request', 'admin_note', 'expires_at', 'published_by',
				'published_at', 'responded_at', 'finalized_by', 'finalized_at',
				'completed_at', 'canceled_at', 'cancellation_reason',
				'post_meeting_internal_notes', 'post_meeting_sender_summary', 'decisions',
				'open_questions', 'follow_up_owner_user_id', 'follow_up_due_at',
				'follow_up_task_id', 'no_show_at', 'row_version', 'created_by',
				'created_at', 'updated_at',
			),
			'meeting_reminders' => array(
				'public_id', 'meeting_offer_id', 'inquiry_id', 'reminder_type', 'audience',
				'status', 'due_at', 'idempotency_key', 'communication_id', 'attempt_count',
				'last_error_code', 'last_error_message', 'ready_at', 'sent_at',
				'canceled_at', 'created_at', 'updated_at',
			),
			'graph_operations' => array(
				'public_id', 'inquiry_id', 'meeting_offer_id', 'operation_type', 'status',
				'idempotency_key', 'request_hash', 'payload_json', 'attempt_count',
				'max_attempts', 'scheduled_at', 'next_retry_at', 'locked_at', 'lock_token',
				'started_at', 'completed_at', 'http_method', 'endpoint_path',
				'response_status', 'graph_error_code', 'graph_error_message',
				'retry_after_seconds', 'request_id', 'client_request_id',
				'response_snapshot_json', 'actor_user_id', 'context_json',
				'row_version', 'created_at', 'updated_at',
			),
			'proposals' => array(
				'public_id', 'inquiry_id', 'access_id', 'proposal_number', 'status',
				'current_version_id', 'pending_version_id', 'currency', 'total_minor', 'expires_at', 'published_by',
				'published_at', 'sender_response', 'sender_response_note',
				'sender_authority_attested', 'boundary_acknowledged', 'responded_at',
				'accepted_at', 'declined_at', 'withdrawn_at', 'superseded_by_id',
				'contract_reference', 'contracted_by', 'contracted_at', 'row_version',
				'created_by', 'created_at', 'updated_at',
			),
			'proposal_versions' => array(
				'public_id', 'proposal_id', 'version_number', 'title', 'executive_summary',
				'scope_json', 'deliverables_json', 'exclusions_json', 'assumptions_json',
				'timeline_text', 'fee_summary', 'payment_terms', 'legal_terms', 'version_note',
				'content_hash', 'created_by', 'created_at',
			),
			'workflow_events' => array(
				'public_id', 'inquiry_id', 'actor_type', 'actor_id', 'object_type',
				'object_id', 'event_type', 'from_status', 'to_status', 'context_json',
				'created_at',
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

	public static function calendar_columns_exist(): array {
		global $wpdb;
		$tables = array(
			'meeting_offers' => array(
				'meeting_type', 'organizer_name', 'organizer_email', 'participant_emails_json',
				'agenda', 'preparation_requests', 'sender_summary', 'sender_next_step',
				'related_document_ids_json', 'calendar_provider', 'external_calendar_reference',
				'previous_start_utc', 'previous_end_utc', 'reschedule_count',
				'last_rescheduled_at', 'last_rescheduled_by', 'join_url_revoked_at',
				'post_meeting_internal_notes', 'post_meeting_sender_summary', 'decisions',
				'open_questions', 'follow_up_owner_user_id', 'follow_up_due_at',
				'follow_up_task_id', 'no_show_at',
			),
			'meeting_reminders' => array(
				'public_id', 'meeting_offer_id', 'inquiry_id', 'reminder_type', 'audience',
				'status', 'due_at', 'idempotency_key', 'communication_id', 'attempt_count',
				'last_error_code', 'last_error_message', 'ready_at', 'sent_at',
				'canceled_at', 'created_at', 'updated_at',
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

	public static function engagement_columns_exist(): array {
		global $wpdb;

		$tables = array(
			'engagements' => array(
				'public_id', 'engagement_number', 'inquiry_id', 'proposal_id',
				'proposal_version_id', 'access_id', 'current_snapshot_id', 'status',
				'title', 'sender_organization', 'contract_reference', 'currency',
				'total_minor', 'owner_user_id', 'participant_user_ids_json',
				'proposed_start_date', 'target_end_date', 'kickoff_status', 'kickoff_at',
				'onboarding_summary', 'sender_summary', 'internal_notes',
				'external_project_reference', 'workbench_handoff_status',
				'decision_studio_handoff_status', 'handoff_prepared_by',
				'handoff_prepared_at', 'ready_by', 'ready_at', 'activated_by',
				'activated_at', 'paused_by', 'paused_at', 'pause_reason',
				'completed_by', 'completed_at', 'completion_note', 'canceled_by',
				'canceled_at', 'cancellation_reason', 'row_version', 'created_by',
				'created_at', 'updated_at',
			),
			'engagement_snapshots' => array(
				'public_id', 'engagement_id', 'inquiry_id', 'proposal_id',
				'proposal_version_id', 'snapshot_version', 'snapshot_type',
				'proposal_number', 'proposal_version_number', 'proposal_content_hash',
				'contract_reference', 'payload_json', 'content_hash', 'created_by',
				'created_at',
			),
			'engagement_requirements' => array(
				'public_id', 'engagement_id', 'inquiry_id', 'requirement_key', 'title',
				'description', 'category', 'status', 'is_required', 'sender_visible',
				'due_date', 'assigned_user_id', 'completion_note',
				'evidence_reference', 'sort_order', 'completed_by', 'completed_at',
				'waived_by', 'waived_at', 'row_version', 'created_by', 'created_at',
				'updated_at',
			),
			'engagement_events' => array(
				'public_id', 'engagement_id', 'inquiry_id', 'actor_type', 'actor_id',
				'event_type', 'object_type', 'object_id', 'from_status', 'to_status',
				'context_json', 'created_at',
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



	public static function lifecycle_columns_exist(): array {
		global $wpdb;
		$tables = array(
			'lifecycle_events' => array( 'id', 'public_id', 'inquiry_id', 'event_type', 'from_stage', 'to_stage', 'actor_user_id', 'payload_json', 'occurred_at' ),
			'lifecycle_notes' => array( 'id', 'public_id', 'inquiry_id', 'note_type', 'note_body', 'is_sensitive', 'created_by', 'created_at', 'updated_at' ),
			'lifecycle_tasks' => array( 'id', 'public_id', 'inquiry_id', 'task_title', 'task_details', 'task_status', 'priority', 'due_at', 'assigned_user_id', 'reminder_policy', 'last_reminded_at', 'completed_at', 'completed_by', 'created_by', 'created_at', 'updated_at' ),
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


	public static function support_columns_exist(): array {
		global $wpdb;
		$tables = array(
			'support_cases' => array( 'id', 'public_id', 'case_number', 'inquiry_id', 'workflow_stage', 'product', 'product_version', 'component', 'issue_type', 'environment_json', 'error_message', 'reproduction_steps', 'expected_behavior', 'actual_behavior', 'severity', 'priority', 'assigned_user_id', 'source_system', 'source_reference', 'known_issue_reference', 'sender_summary', 'sender_next_step', 'row_version', 'created_by', 'created_at', 'updated_at', 'resolved_at', 'closed_at' ),
			'support_case_events' => array( 'id', 'public_id', 'support_case_id', 'inquiry_id', 'event_type', 'from_stage', 'to_stage', 'actor_user_id', 'payload_json', 'occurred_at' ),
			'support_case_links' => array( 'id', 'public_id', 'support_case_id', 'inquiry_id', 'related_type', 'related_reference', 'relation_type', 'title', 'url', 'sender_visible', 'metadata_json', 'created_by', 'created_at', 'updated_at' ),
			'support_signals' => array( 'id', 'public_id', 'signal_type', 'product', 'product_version', 'component', 'issue_type', 'aggregate_key', 'occurrence_count', 'evidence_json', 'contains_personal_data', 'status', 'created_by', 'created_at', 'updated_at' ),
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

	public static function workflow_core_columns_exist(): array {
		global $wpdb;
		$tables = array(
			'workflow_cases' => array(
				'public_id', 'inquiry_id', 'reference', 'current_stage', 'current_state',
				'terminal_state', 'priority', 'owner_user_id', 'source_updated_at',
				'projection_version', 'projection_hash', 'blocker_count',
				'open_command_count', 'pending_handoff_count', 'last_event_at',
				'last_transition_at', 'last_synced_at', 'stale_after',
				'consistency_status', 'consistency_notes', 'row_version',
				'created_at', 'updated_at',
			),
			'workflow_commands' => array(
				'public_id', 'command_key', 'inquiry_id', 'case_id', 'command_type',
				'target_type', 'target_id', 'expected_stage', 'requested_by', 'reason',
				'payload_json', 'payload_hash', 'status', 'claimed_at', 'claimed_by',
				'completed_at', 'result_json', 'error_code', 'error_message',
				'row_version', 'created_at', 'updated_at',
			),
			'workflow_handoffs' => array(
				'public_id', 'handoff_key', 'inquiry_id', 'case_id', 'target',
				'schema_id', 'contract_version', 'data_classification', 'status',
				'payload_json', 'content_hash', 'signature', 'prepared_by',
				'prepared_at', 'dispatched_at', 'acknowledged_by', 'acknowledged_at',
				'failed_at', 'failure_code', 'failure_message', 'expires_at',
				'row_version', 'created_at', 'updated_at',
			),
			'workflow_outbox' => array(
				'public_id', 'event_key', 'inquiry_id', 'case_id', 'event_type',
				'aggregate_type', 'aggregate_id', 'target', 'payload_json',
				'payload_hash', 'status', 'available_at', 'claimed_at', 'claim_token',
				'attempts', 'max_attempts', 'dispatched_at', 'acknowledged_at',
				'error_code', 'error_message', 'row_version', 'created_at', 'updated_at',
			),
		);
		$result = array();
		foreach ( $tables as $table_name => $columns ) {
			$table = self::table( $table_name );
			foreach ( $columns as $column ) {
				$found = $wpdb->get_var(
					$wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", $column ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				);
				$result[ $table_name . '.' . $column ] = ( $found === $column );
			}
		}
		return $result;
	}

	public static function platform_columns_exist(): array {
		global $wpdb;
		$tables = array(
			'platform_snapshots' => array(
				'public_id', 'snapshot_version', 'launch_state', 'readiness_score',
				'required_failures', 'warning_count', 'payload_json', 'content_hash',
				'source', 'generated_by', 'generated_at',
			),
			'platform_migrations' => array(
				'public_id', 'migration_key', 'from_version', 'to_version', 'status',
				'schema_hash', 'context_json', 'started_at', 'completed_at',
				'error_code', 'error_message', 'created_at', 'updated_at',
			),
		);
		$result = array();
		foreach ( $tables as $table_name => $columns ) {
			$table = self::table( $table_name );
			foreach ( $columns as $column ) {
				$found = $wpdb->get_var(
					$wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", $column ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				);
				$result[ $table_name . '.' . $column ] = ( $found === $column );
			}
		}
		return $result;
	}

	public static function hardening_columns_exist(): array {
		global $wpdb;
		$tables = array(
			'health_events' => array( 'public_id','fingerprint','component','event_type','severity','message','context_json','occurrences','first_seen_at','last_seen_at','resolved_at','resolved_by','resolution_note' ),
			'rate_limits' => array( 'bucket_hash','scope','window_start','window_seconds','hits','blocked_until','created_at','updated_at' ),
		);
		$result=array();
		foreach($tables as $table_name=>$columns){ $table=self::table($table_name); foreach($columns as $column){ $found=$wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s",$column)); $result[$table_name.'.'.$column]=($found===$column); } }
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

		foreach ( array( 'audit_log', 'dossier_events', 'dossier_relationships', 'platform_handoffs', 'engagement_dossiers', 'workspace_events', 'workspace_documents', 'workspace_messages', 'workspace_deliverables', 'workspace_milestones', 'workspace_members', 'client_workspaces', 'retention_actions', 'retention_policies', 'legal_holds', 'consent_events', 'privacy_requests', 'support_signals', 'support_case_links', 'support_case_events', 'support_cases', 'lifecycle_tasks', 'lifecycle_notes', 'lifecycle_events', 'platform_snapshots', 'platform_migrations', 'workflow_outbox', 'workflow_handoffs', 'workflow_commands', 'workflow_cases', 'billing_events', 'payment_handoffs', 'invoice_versions', 'invoice_items', 'invoices', 'billing_profiles', 'service_intelligence_events', 'service_intelligence_findings', 'engagement_events', 'engagement_requirements', 'engagement_snapshots', 'engagements', 'workflow_events', 'proposal_versions', 'proposals', 'graph_operations', 'meeting_reminders', 'meeting_offers', 'portal_recovery_requests', 'portal_events', 'portal_sessions', 'portal_access', 'communication_events', 'communications', 'communication_templates', 'fit_assessment_reviews', 'fit_assessment_items', 'fit_assessments', 'reviews', 'attachments', 'inquiries' ) as $name ) {
			$table = self::table( $name );
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}
}
