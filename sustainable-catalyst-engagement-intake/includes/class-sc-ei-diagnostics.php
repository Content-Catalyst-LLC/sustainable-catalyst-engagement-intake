<?php
/**
 * Production upload and storage diagnostics.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Diagnostics {

	public static function run(): array {
		$tables             = SC_EI_Database::tables_exist();
		$inquiry_columns    = SC_EI_Database::inquiry_columns_exist();
		$attachment_columns = SC_EI_Database::attachment_columns_exist();
		$review_columns     = SC_EI_Database::review_columns_exist();
		$communication_columns = SC_EI_Database::communication_columns_exist();
		$privacy_columns       = SC_EI_Database::privacy_columns_exist();
		$fit_columns           = SC_EI_Database::fit_columns_exist();
		$portal_columns        = SC_EI_Database::portal_columns_exist();
		$workflow_columns      = SC_EI_Database::workflow_columns_exist();
		$engagement_columns    = SC_EI_Database::engagement_columns_exist();
		$hardening_columns     = SC_EI_Database::hardening_columns_exist();
		$workflow_core_columns = SC_EI_Database::workflow_core_columns_exist();
		$platform_columns      = SC_EI_Database::platform_columns_exist();
		$lifecycle_columns     = SC_EI_Database::lifecycle_columns_exist();
		$admin              = get_role( 'administrator' );
		$settings           = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() );

		SC_EI_Storage::ensure();

		$storage       = SC_EI_Storage::storage_health();
		$utilization   = SC_EI_Storage::utilization();
		$scanner       = SC_EI_File_Scanner::probe();
		$scanner_readiness = SC_EI_Scanner_Operations::readiness( $settings );
		$operations    = SC_EI_Attachment_Repository::operational_summary();
		$environment   = SC_EI_Upload_Environment::limits();
		$effective     = SC_EI_Upload_Environment::effective_limits( $settings );
		$reconciliation= SC_EI_Storage_Reconciler::latest();
		$retention_preview = get_option( 'sc_ei_last_privacy_retention_preview', get_option( 'sc_ei_last_retention_preview', array() ) );
		$retention_run     = SC_EI_Retention::latest_run();
		$probe             = SC_EI_Storage::latest_probe();
		$database_totals   = SC_EI_Attachment_Repository::storage_totals();
		$review_metrics    = SC_EI_Review_Repository::metrics( get_current_user_id() );
		$communication_metrics = SC_EI_Communication_Repository::metrics();
		$communication_templates = SC_EI_Template_Repository::active_templates();
		$privacy_metrics = SC_EI_Privacy_Repository::metrics();
		$privacy_inventory = SC_EI_Privacy_Repository::data_inventory();
		$retention_policies = SC_EI_Retention_Policy_Repository::active();
		$fit_metrics = SC_EI_Fit_Repository::metrics();
		$portal_metrics = SC_EI_Portal_Repository::metrics();
		$workflow_metrics = SC_EI_Workflow_Repository::metrics();
		$graph_credentials = SC_EI_Graph_Credentials::public_status();
		$graph_crypto = SC_EI_Graph_Crypto::status();
		$graph_circuit = SC_EI_Graph_Client::circuit_status();
		$graph_health = SC_EI_Graph_Repository::last_health();
		$graph_metrics = SC_EI_Graph_Repository::metrics();
		$engagement_metrics = SC_EI_Engagement_Repository::metrics();
		$lifecycle_metrics = SC_EI_Lifecycle_Repository::metrics();
		$hardening_metrics = SC_EI_Hardening_Repository::metrics();
		$hardening_watchdog = SC_EI_Hardening_Repository::last_watchdog();
		$workflow_core_metrics = SC_EI_Workflow_Core_Repository::metrics();
		$workflow_core_targets = SC_EI_Workflow_Core_Service::registered_targets();
		$workflow_core_last_sync = get_option( 'sc_ei_workflow_core_last_sync', array() );
		$workflow_core_last_outbox = get_option( 'sc_ei_workflow_core_last_outbox', array() );
		$platform_summary = SC_EI_Platform_Repository::platform_summary();

		$notification_policies = array(
			'sender_acknowledgment' => ! empty( $settings['sender_acknowledgment_enabled'] ),
			'internal_new_inquiry'  => ! empty( $settings['internal_new_inquiry_enabled'] ),
			'review_due_reminders'  => ! empty( $settings['review_due_reminders_enabled'] ),
			'follow_up_reminders'   => ! empty( $settings['follow_up_reminders_enabled'] ),
			'escalation_alerts'     => ! empty( $settings['escalation_notifications_enabled'] ),
		);
		$notification_automation_enabled = in_array( true, $notification_policies, true );
		$notification_sender_ready = '' !== trim( (string) ( $settings['communication_sender_name'] ?? '' ) )
			&& is_email( (string) ( $settings['communication_sender_email'] ?? '' ) )
			&& is_email( (string) ( $settings['communication_reply_to_email'] ?? '' ) );

		$capabilities = array();
		foreach ( SC_EI_Capabilities::ALL as $cap ) {
			$capabilities[ $cap ] = $admin ? $admin->has_cap( $cap ) : false;
		}

		return array(
			'plugin_version'       => SC_EI_VERSION,
			'database_version'     => (string) get_option( 'sc_ei_db_version', '' ),
			'validator_version'    => SC_EI_VALIDATOR_VERSION,
			'tables'               => $tables,
			'inquiry_columns'      => $inquiry_columns,
			'attachment_columns'   => $attachment_columns,
			'review_columns'       => $review_columns,
			'review_metrics'       => $review_metrics,
			'review_schema_version'=> SC_EI_REVIEW_SCHEMA_VERSION,
			'communication_columns'=> $communication_columns,
			'communication_metrics'=> $communication_metrics,
			'communication_schema_version' => SC_EI_COMMUNICATION_SCHEMA_VERSION,
			'communication_templates' => array(
				'active_count' => count( $communication_templates ),
				'keys'         => array_keys( $communication_templates ),
			),
			'portal_columns'         => $portal_columns,
			'portal_schema_version'  => SC_EI_PORTAL_SCHEMA_VERSION,
			'portal_metrics'         => $portal_metrics,
			'portal_security'        => array(
				'cleanup_scheduled'     => (bool) wp_next_scheduled( 'sc_ei_portal_cleanup' ),
				'next_cleanup_utc'      => ( $next = wp_next_scheduled( 'sc_ei_portal_cleanup' ) ) ? gmdate( 'Y-m-d H:i:s', $next ) : null,
				'last_cleanup'          => get_option( 'sc_ei_last_portal_cleanup', array() ),
				'raw_invite_stored'     => false,
				'raw_session_stored'    => false,
				'wordpress_user_created'=> false,
				'automatic_invite_email'=> false,
				'email_challenge'       => ! empty( $settings['portal_require_email_challenge'] ),
				'terms_required'        => ! empty( $settings['portal_require_terms_acceptance'] ),
				'cookie_httponly'       => ! empty( $settings['portal_cookie_httponly'] ),
				'cookie_samesite'       => (string) $settings['portal_cookie_samesite'],
				'cookie_name'           => SC_EI_Portal_Schema::COOKIE_NAME,
				'legacy_cookie_allowed' => ! empty( $settings['portal_allow_legacy_cookie'] ),
				'https_required'        => ! empty( $settings['portal_require_https'] ),
				'secure_transport_now'  => SC_EI_Portal_Schema::secure_transport_available(),
				'atomic_activation'     => true,
				'wrong_token_lockout'   => false,
				'recovery_enabled'      => ! empty( $settings['portal_recovery_enabled'] ),
				'recovery_human_review' => true,
				'recovery_auto_issue'   => false,
				'recovery_auto_email'   => false,
				'noindex'               => ! empty( $settings['portal_noindex'] ),
				'no_store'              => ! empty( $settings['portal_no_store'] ),
				'page_url'              => (string) $settings['portal_page_url'],
			),
			'workflow_columns'       => $workflow_columns,
			'workflow_schema_version'=> SC_EI_WORKFLOW_SCHEMA_VERSION,
			'workflow_metrics'       => $workflow_metrics,
			'workflow_controls'      => array(
				'cleanup_scheduled'       => (bool) wp_next_scheduled( 'sc_ei_workflow_cleanup' ),
				'next_cleanup_utc'        => ( $workflow_next = wp_next_scheduled( 'sc_ei_workflow_cleanup' ) ) ? gmdate( 'Y-m-d H:i:s', $workflow_next ) : null,
				'last_cleanup'            => get_option( 'sc_ei_last_workflow_cleanup', array() ),
				'microsoft_teams_only'    => true,
				'automatic_calendar'      => false,
				'graph_api_configured'    => ! empty( $graph_credentials['configured'] ),
				'graph_api_connected'     => ! empty( $graph_health['ok'] ),
				'graph_human_triggered'   => true,
				'graph_manual_fallback'   => true,
				'automatic_email'         => false,
				'automatic_contract'      => false,
				'automatic_payment'       => false,
				'portal_acceptance_signature' => false,
				'proposal_version_hash'   => true,
				'human_publish_required'  => true,
				'human_contract_attestation'=> true,
				'sender_ics_enabled'      => ! empty( $settings['workflow_allow_sender_ics'] ),
				'max_meeting_slots'       => absint( $settings['workflow_max_meeting_slots'] ?? 5 ),
				'meeting_expiry_days'     => absint( $settings['workflow_meeting_offer_expiry_days'] ?? 7 ),
				'proposal_expiry_days'    => absint( $settings['workflow_proposal_expiry_days'] ?? 14 ),
			),
			'engagement_columns'       => $engagement_columns,
			'engagement_schema_version'=> SC_EI_ENGAGEMENT_SCHEMA_VERSION,
			'engagement_metrics'       => $engagement_metrics,
			'engagement_controls'      => array(
				'enabled'                    => ! empty( $settings['engagement_enabled'] ),
				'unique_proposal_handoff'    => true,
				'immutable_snapshot'         => true,
				'snapshot_hash_required'     => ! empty( $settings['engagement_require_snapshot_hash'] ),
				'contract_reference_required'=> ! empty( $settings['engagement_require_contract_reference'] ),
				'owner_required'             => ! empty( $settings['engagement_require_owner'] ),
				'all_required_items'         => ! empty( $settings['engagement_require_all_required_items'] ),
				'human_ready_required'       => true,
				'human_activation_required'  => true,
				'automatic_activation'       => false,
				'automatic_provisioning'     => false,
				'automatic_invoice'          => false,
				'automatic_payment'          => false,
				'electronic_signature'       => false,
				'portal_sender_safe'         => ! empty( $settings['engagement_sender_portal_enabled'] ),
				'workbench_export'           => ! empty( $settings['engagement_allow_workbench_export'] ),
				'decision_studio_export'     => ! empty( $settings['engagement_allow_decision_studio_export'] ),
			),
			'lifecycle_schema_version'=> SC_EI_LIFECYCLE_SCHEMA_VERSION,
			'lifecycle_columns'       => $lifecycle_columns,
			'lifecycle_metrics'       => $lifecycle_metrics,
			'lifecycle_controls'      => array(
				'enabled' => ! empty( $settings['lifecycle_enabled'] ),
				'reminder_scheduled' => (bool) wp_next_scheduled( SC_EI_Lifecycle_Repository::REMINDER_HOOK ),
				'reminder_callback_registered' => false !== has_action( SC_EI_Lifecycle_Repository::REMINDER_HOOK, array( 'SC_EI_Lifecycle_Repository', 'process_due_tasks' ) ),
				'private_internal_notes' => true,
				'sender_safe_summary_only' => true,
				'automatic_rejection' => false,
				'automatic_commitment' => false,
				'human_transition_reason' => ! empty( $settings['lifecycle_require_transition_reason'] ),
				'owner_required_for_qualified' => ! empty( $settings['lifecycle_require_owner_for_qualified'] ),
			),
			'platform_schema_version' => SC_EI_PLATFORM_SCHEMA_VERSION,
			'platform_columns'        => $platform_columns,
			'platform'                => array(
				'readiness' => $platform_summary['readiness'],
				'migrations'=> $platform_summary['migrations'],
				'snapshots' => $platform_summary['snapshots'],
				'boundaries'=> SC_EI_Platform_Repository::boundaries(),
			),
			'workflow_core_schema_version' => SC_EI_WORKFLOW_CORE_SCHEMA_VERSION,
			'workflow_core_columns'        => $workflow_core_columns,
			'workflow_core'                => array(
				'enabled'                   => ! empty( $settings['workflow_core_enabled'] ),
				'metrics'                   => $workflow_core_metrics,
				'targets'                   => $workflow_core_targets,
				'sync_scheduled'            => (bool) wp_next_scheduled( SC_EI_Workflow_Core_Repository::SYNC_HOOK ),
				'next_sync_utc'             => ( $workflow_core_sync = wp_next_scheduled( SC_EI_Workflow_Core_Repository::SYNC_HOOK ) ) ? gmdate( 'Y-m-d H:i:s', $workflow_core_sync ) : null,
				'outbox_scheduled'          => (bool) wp_next_scheduled( SC_EI_Workflow_Core_Repository::OUTBOX_HOOK ),
				'next_outbox_utc'           => ( $workflow_core_outbox = wp_next_scheduled( SC_EI_Workflow_Core_Repository::OUTBOX_HOOK ) ) ? gmdate( 'Y-m-d H:i:s', $workflow_core_outbox ) : null,
				'last_sync'                 => is_array( $workflow_core_last_sync ) ? $workflow_core_last_sync : array(),
				'last_outbox'               => is_array( $workflow_core_last_outbox ) ? $workflow_core_last_outbox : array(),
				'audit_driven_sync'         => ! empty( $settings['workflow_core_auto_sync_on_audit'] ),
				'signed_handoffs'           => true,
				'idempotent_commands'       => true,
				'durable_outbox'            => true,
				'internal_adapters_only'    => true,
				'arbitrary_webhooks'        => false,
				'inbound_commands'          => false,
				'automatic_acceptance'      => false,
				'automatic_fit_decision'    => false,
				'automatic_proposal'        => false,
				'automatic_contract'        => false,
				'automatic_activation'      => false,
				'automatic_project_creation'=> false,
			),
			'hardening_schema_version' => SC_EI_HARDENING_SCHEMA_VERSION,
			'hardening_columns'        => $hardening_columns,
			'hardening'                => array(
				'enabled'                => ! empty( $settings['hardening_enabled'] ),
				'public_writes_paused'   => ! empty( $settings['hardening_public_writes_paused'] ),
				'metrics'                => $hardening_metrics,
				'watchdog'               => $hardening_watchdog,
				'watchdog_scheduled'     => (bool) wp_next_scheduled( 'sc_ei_hardening_watchdog' ),
				'prune_scheduled'        => (bool) wp_next_scheduled( 'sc_ei_hardening_prune' ),
				'durable_rate_limits'    => true,
				'health_event_deduping'  => true,
				'fatal_capture'          => ! empty( $settings['hardening_fatal_capture_enabled'] ),
				'security_headers'       => ! empty( $settings['hardening_security_headers_enabled'] ),
				'accessibility_helpers'  => ! empty( $settings['hardening_accessibility_helpers'] ),
				'no_secret_context'      => true,
				'no_automatic_decisions' => true,
				'no_automatic_deletion'  => true,
			),
			'graph_schema_version'   => SC_EI_GRAPH_SCHEMA_VERSION,
			'graph'                  => array(
				'enabled'                 => ! empty( $settings['graph_enabled'] ),
				'credentials'             => $graph_credentials,
				'crypto'                  => $graph_crypto,
				'circuit'                 => $graph_circuit,
				'health'                  => $graph_health,
				'metrics'                 => $graph_metrics,
				'catchup_scheduled'       => (bool) wp_next_scheduled( 'sc_ei_graph_catchup' ),
				'next_catchup_utc'        => ( $graph_catchup = wp_next_scheduled( 'sc_ei_graph_catchup' ) ) ? gmdate( 'Y-m-d H:i:s', $graph_catchup ) : null,
				'queue_scheduled'         => (bool) wp_next_scheduled( 'sc_ei_graph_process_queue' ),
				'next_queue_utc'          => ( $graph_queue = wp_next_scheduled( 'sc_ei_graph_process_queue' ) ) ? gmdate( 'Y-m-d H:i:s', $graph_queue ) : null,
				'app_only'                => true,
				'global_cloud_only'       => ! empty( $settings['graph_global_cloud_only'] ),
				'encrypted_credentials'   => ! empty( $graph_crypto['available'] ),
				'encrypted_token_cache'   => true,
				'transaction_id'          => true,
				'retry_after'             => true,
				'exponential_backoff'     => true,
				'bounded_attempts'        => absint( $settings['graph_max_attempts'] ?? 6 ),
				'circuit_breaker'         => true,
				'human_triggered_only'    => true,
				'manual_fallback'         => true,
				'include_sender_attendee' => ! empty( $settings['graph_include_sender_attendee'] ),
				'calendar_consent_required'=> ! empty( $settings['graph_require_calendar_consent'] ),
				'automatic_contract'      => false,
				'automatic_payment'       => false,
			),
			'fit_columns'            => $fit_columns,
			'fit_schema_version'     => SC_EI_FIT_SCHEMA_VERSION,
			'fit_metrics'            => $fit_metrics,
			'fit_human_control'      => array(
				'automatic_recommendation' => false,
				'automatic_acceptance'     => false,
				'automatic_rejection'      => false,
				'automatic_status_change'  => false,
				'automatic_communication'  => false,
				'automatic_scheduling'     => false,
				'advisory_thresholds'      => false,
				'human_attestation_required' => true,
				'distinct_second_reviewer' => ! empty( $settings['fit_distinct_second_reviewer'] ),
				'advisory_score_enabled'   => ! empty( $settings['fit_advisory_score_enabled'] ),
				'criteria_count'           => count( SC_EI_Fit_Schema::criteria() ),
			),
			'privacy_columns'        => $privacy_columns,
			'privacy_schema_version' => SC_EI_PRIVACY_SCHEMA_VERSION,
			'privacy_metrics'        => $privacy_metrics,
			'privacy_inventory'      => $privacy_inventory,
			'retention_policies'     => array(
				'active_count' => count( $retention_policies ),
				'keys'         => array_keys( $retention_policies ),
			),
			'privacy_lifecycle' => array(
				'queue_only_cron'       => true,
				'tombstones_retained'   => true,
				'approval_required'     => ! empty( $settings['require_retention_approval'] ),
				'distinct_approver'     => ! empty( $settings['require_distinct_retention_approver'] ),
				'cron_scheduled'        => (bool) wp_next_scheduled( SC_EI_Retention::CRON_HOOK ),
				'next_cron_utc'         => wp_next_scheduled( SC_EI_Retention::CRON_HOOK )
					? gmdate( 'Y-m-d H:i:s', (int) wp_next_scheduled( SC_EI_Retention::CRON_HOOK ) )
					: '',
				'last_queue_run'        => $retention_run,
				'request_due_days'      => absint( $settings['privacy_request_due_days'] ?? 30 ),
				'queue_batch_limit'     => absint( $settings['retention_queue_batch_limit'] ?? 100 ),
				'execution_batch_limit' => absint( $settings['retention_execution_batch_limit'] ?? 25 ),
			),
			'notifications' => array(
				'policies'                    => $notification_policies,
				'automation_enabled'          => $notification_automation_enabled,
				'sender_ready'                => $notification_sender_ready,
				'sender_name'                 => sanitize_text_field( (string) ( $settings['communication_sender_name'] ?? '' ) ),
				'sender_email'                => sanitize_email( (string) ( $settings['communication_sender_email'] ?? '' ) ),
				'reply_to_email'              => sanitize_email( (string) ( $settings['communication_reply_to_email'] ?? '' ) ),
				'internal_recipient_count'    => count( SC_EI_Communication_Schema::sanitize_emails( $settings['notification_internal_recipients'] ?? '', 10 ) ),
				'escalation_recipient_count'  => count( SC_EI_Communication_Schema::sanitize_emails( $settings['notification_escalation_recipients'] ?? '', 10 ) ),
				'cron_scheduled'              => (bool) wp_next_scheduled( SC_EI_Notification_Service::CRON_HOOK ),
				'next_cron_utc'               => wp_next_scheduled( SC_EI_Notification_Service::CRON_HOOK )
					? gmdate( 'Y-m-d H:i:s', (int) wp_next_scheduled( SC_EI_Notification_Service::CRON_HOOK ) )
					: '',
				'last_reminder_run'           => sanitize_text_field( (string) get_option( 'sc_ei_last_notification_reminder_run', '' ) ),
				'review_reminder_lead_hours'  => absint( $settings['review_reminder_lead_hours'] ?? 24 ),
				'batch_limit'                 => absint( $settings['notification_batch_limit'] ?? 25 ),
				'mail_transport'              => 'wordpress_wp_mail',
				'delivery_confirmation'       => false,
				'plain_text_only'             => true,
				'attachments_supported'       => false,
			),
			'capabilities'         => $capabilities,
			'privacy_exporter'     => true,
			'privacy_eraser'       => true,
			'public_forms'         => true,
			'public_shortcodes'    => array(
				'[sc_engagement_inquiry mode="compact" source="consulting-page"]',
				'[sc_contact_hub mode="advanced" source="contact-page"]',
				'[sc_contact_form mode="general"]',
				'[sc_engagement_inquiry mode="consulting"]',
			),
			'public_submit_rest'   => rest_url( 'sc-engagement-intake/v1/submit' ),
			'storage'              => $storage,
			'utilization'          => $utilization,
			'database_totals'      => $database_totals,
			'storage_probe'        => $probe,
			'reconciliation'       => is_array( $reconciliation ) ? $reconciliation : array(),
			'retention_preview'    => is_array( $retention_preview ) ? $retention_preview : array(),
			'retention_run'        => $retention_run,
			'environment'          => $environment,
			'effective_limits'     => $effective,
			'cache_headers'        => SC_EI_Upload_Environment::no_cache_headers(),
			'scanner'              => $scanner,
			'scanner_readiness'    => $scanner_readiness,
			'quarantine_operations'=> $operations,
			'uploads'              => array(
				'enabled'                  => true,
				'max_files'                => absint( $settings['upload_max_files'] ?? 5 ),
				'max_file_mb'              => absint( $settings['upload_max_file_mb'] ?? 20 ),
				'allowed_extensions'       => implode( ', ', (array) ( $settings['allowed_upload_extensions'] ?? array() ) ),
				'ziparchive_available'     => class_exists( 'ZipArchive' ),
				'finfo_available'          => class_exists( 'finfo' ),
				'retention_days'           => absint( $settings['attachment_retention_days'] ?? 180 ),
				'retention_cron_scheduled' => (bool) wp_next_scheduled( SC_EI_Retention::CRON_HOOK ),
				'scanner_required'         => ! empty( $settings['require_external_scanner'] ),
				'atomic_commit'            => true,
				'post_move_verification'   => true,
				'request_envelope_checks'  => true,
				'partial_failure_reporting'=> true,
			),
			'spam_controls'        => array(
				'nonce'                => true,
				'honeypot'             => true,
				'timing'               => true,
				'rate_limit'           => true,
				'durable_rate_limit'   => true,
				'incident_write_pause' => true,
				'duplicates'           => true,
				'file_count_limit'     => true,
				'file_size_limit'      => true,
				'aggregate_size_limit' => true,
				'request_size_guard'   => true,
				'extension_allowlist'  => true,
				'mime_validation'      => true,
				'signature_checks'     => true,
				'sha256_integrity'     => true,
				'cache_bypass_headers' => true,
			),
			'teams'                => array(
				'provider'              => 'Microsoft Teams',
				'preference_fields'     => true,
				'timezone_detection'    => true,
				'availability_fields'   => true,
				'admin_status_workflow' => true,
				'time_offer_workflow'   => true,
				'sender_slot_selection' => true,
				'secure_ics_export'     => ! empty( $settings['workflow_allow_sender_ics'] ),
				'proposal_workflow'     => true,
				'proposal_versioning'   => true,
				'proposal_acceptance_is_signature' => false,
				'graph_api_connected'   => ! empty( $graph_health['ok'] ),
				'graph_api_configured'  => ! empty( $graph_credentials['configured'] ),
				'graph_manual_fallback' => true,
				'organizer_configured'  => ! empty( $settings['teams_organizer_email'] ) || ! empty( $graph_credentials['organizer_user'] ),
			),
			'wordpress_version'    => get_bloginfo( 'version' ),
			'php_version'          => PHP_VERSION,
			'multisite'            => is_multisite(),
			'site_url'             => site_url(),
		);
	}

	public static function overall_status( array $results ): string {
		$tables_ok      = ! in_array( false, $results['tables'], true );
		$inquiry_ok     = ! in_array( false, $results['inquiry_columns'], true );
		$attachments_ok = ! in_array( false, $results['attachment_columns'], true );
		$reviews_ok     = ! in_array( false, $results['review_columns'], true );
		$communications_ok = ! in_array( false, $results['communication_columns'], true );
		$portal_ok = ! in_array( false, $results['portal_columns'], true )
			&& ! empty( $results['portal_security']['cleanup_scheduled'] )
			&& empty( $results['portal_security']['raw_invite_stored'] )
			&& empty( $results['portal_security']['raw_session_stored'] )
			&& empty( $results['portal_security']['wordpress_user_created'] )
			&& empty( $results['portal_security']['automatic_invite_email'] )
			&& ! empty( $results['portal_security']['email_challenge'] )
			&& ! empty( $results['portal_security']['terms_required'] )
			&& ! empty( $results['portal_security']['cookie_httponly'] )
			&& 'Strict' === $results['portal_security']['cookie_samesite']
			&& '__Host-sc_ei_sender_session' === $results['portal_security']['cookie_name']
			&& ! empty( $results['portal_security']['https_required'] )
			&& ! empty( $results['portal_security']['atomic_activation'] )
			&& empty( $results['portal_security']['wrong_token_lockout'] )
			&& ! empty( $results['portal_security']['recovery_enabled'] )
			&& ! empty( $results['portal_security']['recovery_human_review'] )
			&& empty( $results['portal_security']['recovery_auto_issue'] )
			&& empty( $results['portal_security']['recovery_auto_email'] )
			&& ! empty( $results['portal_security']['noindex'] )
			&& ! empty( $results['portal_security']['no_store'] );
		$workflow_ok = ! in_array( false, $results['workflow_columns'], true )
			&& ! empty( $results['workflow_controls']['cleanup_scheduled'] )
			&& ! empty( $results['workflow_controls']['microsoft_teams_only'] )
			&& empty( $results['workflow_controls']['automatic_calendar'] )
			&& empty( $results['workflow_controls']['automatic_contract'] )
			&& empty( $results['workflow_controls']['automatic_payment'] )
			&& empty( $results['workflow_controls']['portal_acceptance_signature'] )
			&& ! empty( $results['workflow_controls']['proposal_version_hash'] )
			&& ! empty( $results['workflow_controls']['human_publish_required'] )
			&& ! empty( $results['workflow_controls']['human_contract_attestation'] );
		$engagement_ok = ! in_array( false, $results['engagement_columns'], true )
			&& ! empty( $results['engagement_controls']['unique_proposal_handoff'] )
			&& ! empty( $results['engagement_controls']['immutable_snapshot'] )
			&& ! empty( $results['engagement_controls']['human_ready_required'] )
			&& ! empty( $results['engagement_controls']['human_activation_required'] )
			&& empty( $results['engagement_controls']['automatic_activation'] )
			&& empty( $results['engagement_controls']['automatic_provisioning'] )
			&& empty( $results['engagement_controls']['automatic_invoice'] )
			&& empty( $results['engagement_controls']['automatic_payment'] )
			&& empty( $results['engagement_controls']['electronic_signature'] );
		$graph_ok = true;
		if ( ! empty( $results['graph']['enabled'] ) ) {
			$graph_ok = ! empty( $results['graph']['crypto']['available'] )
				&& ! empty( $results['graph']['credentials']['configured'] )
				&& empty( $results['graph']['credentials']['secret_expired'] )
				&& ! empty( $results['graph']['catchup_scheduled'] )
				&& ! empty( $results['graph']['app_only'] )
				&& ! empty( $results['graph']['global_cloud_only'] )
				&& ! empty( $results['graph']['transaction_id'] )
				&& ! empty( $results['graph']['retry_after'] )
				&& ! empty( $results['graph']['exponential_backoff'] )
				&& ! empty( $results['graph']['human_triggered_only'] )
				&& ! empty( $results['graph']['manual_fallback'] )
				&& ! empty( $results['graph']['health']['ok'] );
		}
		$workflow_core_ok = ! in_array( false, $results['workflow_core_columns'], true )
			&& ! empty( $results['workflow_core']['sync_scheduled'] )
			&& ! empty( $results['workflow_core']['outbox_scheduled'] )
			&& ! empty( $results['workflow_core']['signed_handoffs'] )
			&& ! empty( $results['workflow_core']['idempotent_commands'] )
			&& ! empty( $results['workflow_core']['durable_outbox'] )
			&& ! empty( $results['workflow_core']['internal_adapters_only'] )
			&& empty( $results['workflow_core']['arbitrary_webhooks'] )
			&& empty( $results['workflow_core']['inbound_commands'] )
			&& empty( $results['workflow_core']['automatic_acceptance'] )
			&& empty( $results['workflow_core']['automatic_fit_decision'] )
			&& empty( $results['workflow_core']['automatic_proposal'] )
			&& empty( $results['workflow_core']['automatic_contract'] )
			&& empty( $results['workflow_core']['automatic_activation'] )
			&& empty( $results['workflow_core']['automatic_project_creation'] );
		$hardening_ok = ! in_array( false, $results['hardening_columns'], true )
			&& ! empty( $results['hardening']['watchdog_scheduled'] )
			&& ! empty( $results['hardening']['prune_scheduled'] )
			&& ! empty( $results['hardening']['durable_rate_limits'] )
			&& ! empty( $results['hardening']['health_event_deduping'] )
			&& ! empty( $results['hardening']['security_headers'] )
			&& ! empty( $results['hardening']['accessibility_helpers'] )
			&& ! empty( $results['hardening']['no_secret_context'] )
			&& ! empty( $results['hardening']['no_automatic_decisions'] )
			&& ! empty( $results['hardening']['no_automatic_deletion'] );
		$fit_ok = ! in_array( false, $results['fit_columns'], true )
			&& empty( $results['fit_human_control']['automatic_recommendation'] )
			&& empty( $results['fit_human_control']['automatic_acceptance'] )
			&& empty( $results['fit_human_control']['automatic_rejection'] )
			&& empty( $results['fit_human_control']['automatic_status_change'] )
			&& empty( $results['fit_human_control']['advisory_thresholds'] )
			&& ! empty( $results['fit_human_control']['human_attestation_required'] );
		$privacy_ok      = ! in_array( false, $results['privacy_columns'], true )
			&& ! empty( $results['retention_policies']['active_count'] )
			&& ! empty( $results['privacy_lifecycle']['queue_only_cron'] )
			&& ! empty( $results['privacy_lifecycle']['tombstones_retained'] );
		$caps_ok        = ! in_array( false, $results['capabilities'], true );

		$storage_ok = ! empty( $results['storage']['exists'] )
			&& ! empty( $results['storage']['writable'] )
			&& ! empty( $results['storage']['marker'] )
			&& ! empty( $results['storage']['protection_files'] )
			&& ! empty( $results['storage']['outside_document_root'] )
			&& ! empty( $results['storage']['quarantine_writable'] )
			&& ! empty( $results['storage']['approved_writable'] )
			&& empty( $results['storage']['base_is_symlink'] );

		$environment_ok = ! empty( $results['environment']['file_uploads_enabled'] )
			&& ! empty( $results['environment']['temporary_exists'] )
			&& ! empty( $results['environment']['temporary_writable'] )
			&& (int) $results['effective_limits']['max_files'] >= 1
			&& (int) $results['effective_limits']['max_file_bytes'] >= MB_IN_BYTES
			&& (int) $results['effective_limits']['max_total_bytes'] >= MB_IN_BYTES;

		$upload_ok = ! empty( $results['uploads']['finfo_available'] )
			&& ! empty( $results['uploads']['retention_cron_scheduled'] );

		$allowed = explode( ', ', (string) $results['uploads']['allowed_extensions'] );
		if ( array_intersect( array( 'docx', 'xlsx' ), $allowed ) && empty( $results['uploads']['ziparchive_available'] ) ) {
			$upload_ok = false;
		}
		if (
			! empty( $results['uploads']['scanner_required'] )
			&& (
				empty( $results['scanner']['configured'] )
				|| empty( $results['scanner_readiness']['ready'] )
			)
		) {
			$upload_ok = false;
		}

		$reconciliation_ok = true;
		if ( ! empty( $results['reconciliation']['counts'] ) ) {
			$counts = $results['reconciliation']['counts'];
			$reconciliation_ok = 0 === array_sum(
				array_map(
					'absint',
					array_intersect_key(
						$counts,
						array_flip( array( 'missing_files', 'hash_mismatches', 'size_mismatches', 'misplaced_files', 'unresolvable_paths', 'orphan_files' ) )
					)
				)
			);
		}

		$disk_ok = empty( $results['storage']['disk_free_bytes'] )
			|| (int) $results['storage']['disk_free_bytes'] >= 100 * MB_IN_BYTES;

		$notifications_ok = true;
		if ( ! empty( $results['notifications']['automation_enabled'] ) ) {
			$notifications_ok = ! empty( $results['notifications']['sender_ready'] )
				&& ! empty( $results['notifications']['cron_scheduled'] )
				&& ! empty( $results['communication_templates']['active_count'] );
		}

		return ( $tables_ok && $inquiry_ok && $attachments_ok && $reviews_ok && $communications_ok && $portal_ok && $workflow_ok && $engagement_ok && $graph_ok && $workflow_core_ok && $hardening_ok && $fit_ok && $privacy_ok && $caps_ok && $storage_ok && $environment_ok && $upload_ok && $reconciliation_ok && $disk_ok && $notifications_ok )
			? 'healthy'
			: 'attention';
	}
}
