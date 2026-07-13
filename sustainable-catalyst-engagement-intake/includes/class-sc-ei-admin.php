<?php
/**
 * WordPress administration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Admin {

	public static function register(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_init', array( __CLASS__, 'settings' ) );
		add_action( 'admin_post_sc_ei_update_status', array( __CLASS__, 'handle_status' ) );
		add_action( 'admin_post_sc_ei_add_note', array( __CLASS__, 'handle_note' ) );
		add_action( 'admin_post_sc_ei_update_scheduling', array( __CLASS__, 'handle_scheduling' ) );
		add_action( 'admin_post_sc_ei_download_attachment', array( __CLASS__, 'handle_attachment_download' ) );
		add_action( 'admin_post_sc_ei_update_attachment_status', array( __CLASS__, 'handle_attachment_status' ) );
		add_action( 'admin_post_sc_ei_update_attachment_retention', array( __CLASS__, 'handle_attachment_retention' ) );
		add_action( 'admin_post_sc_ei_delete_attachment', array( __CLASS__, 'handle_attachment_delete' ) );
		add_action( 'admin_post_sc_ei_verify_attachment_integrity', array( __CLASS__, 'handle_attachment_integrity' ) );
		add_action( 'admin_post_sc_ei_run_storage_probe', array( __CLASS__, 'handle_storage_probe' ) );
		add_action( 'admin_post_sc_ei_repair_storage', array( __CLASS__, 'handle_storage_repair' ) );
		add_action( 'admin_post_sc_ei_run_storage_reconciliation', array( __CLASS__, 'handle_storage_reconciliation' ) );
		add_action( 'admin_post_sc_ei_preview_retention_cleanup', array( __CLASS__, 'handle_retention_preview' ) );
		add_action( 'admin_post_sc_ei_run_retention_cleanup', array( __CLASS__, 'handle_retention_cleanup' ) );
		add_action( 'admin_post_sc_ei_run_scanner_readiness_test', array( __CLASS__, 'handle_scanner_readiness_test' ) );
		add_action( 'admin_post_sc_ei_retry_attachment_scan', array( __CLASS__, 'handle_attachment_scan_retry' ) );
		add_action( 'admin_post_sc_ei_quarantine_bulk', array( __CLASS__, 'handle_quarantine_bulk' ) );
		add_action( 'admin_post_sc_ei_export_file_audit', array( __CLASS__, 'handle_file_audit_export' ) );
		add_filter( 'set-screen-option', array( __CLASS__, 'screen_option' ), 10, 3 );
		add_filter( 'plugin_action_links_' . SC_EI_BASENAME, array( __CLASS__, 'plugin_links' ) );
	}

	public static function menu(): void {
		$hook = add_menu_page(
			__( 'Engagement Intake', 'sustainable-catalyst-engagement-intake' ),
			__( 'Engagement Intake', 'sustainable-catalyst-engagement-intake' ),
			'sc_intake_view',
			'sc-engagement-intake',
			array( __CLASS__, 'inquiries_page' ),
			'dashicons-id-alt',
			27
		);

		add_action(
			"load-{$hook}",
			static function(): void {
				add_screen_option(
					'per_page',
					array(
						'label'   => __( 'Inquiries per page', 'sustainable-catalyst-engagement-intake' ),
						'default' => 20,
						'option'  => 'sc_ei_inquiries_per_page',
					)
				);
			}
		);

		add_submenu_page(
			'sc-engagement-intake',
			__( 'Inquiries', 'sustainable-catalyst-engagement-intake' ),
			__( 'Inquiries', 'sustainable-catalyst-engagement-intake' ),
			'sc_intake_view',
			'sc-engagement-intake',
			array( __CLASS__, 'inquiries_page' )
		);

		SC_EI_Review_Admin::submenu();
		SC_EI_Fit_Admin::submenu();
		SC_EI_Portal_Admin::submenu();
		SC_EI_Workflow_Admin::submenu();
		SC_EI_Graph_Admin::submenu();
		SC_EI_Communication_Admin::submenu();
		SC_EI_Privacy_Admin::submenu();

		$quarantine_hook = add_submenu_page(
			'sc-engagement-intake',
			__( 'Quarantine Operations', 'sustainable-catalyst-engagement-intake' ),
			__( 'Quarantine', 'sustainable-catalyst-engagement-intake' ),
			'sc_intake_review',
			'sc-engagement-intake-quarantine',
			array( __CLASS__, 'quarantine_page' )
		);

		add_action(
			"load-{$quarantine_hook}",
			static function(): void {
				$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'queue';
				$is_access = 'access' === $view;
				add_screen_option(
					'per_page',
					array(
						'label'   => $is_access
							? __( 'File audit events per page', 'sustainable-catalyst-engagement-intake' )
							: __( 'Quarantine documents per page', 'sustainable-catalyst-engagement-intake' ),
						'default' => $is_access ? 25 : 20,
						'option'  => $is_access ? 'sc_ei_file_audit_per_page' : 'sc_ei_quarantine_per_page',
					)
				);
			}
		);

		add_submenu_page(
			'sc-engagement-intake',
			__( 'Diagnostics', 'sustainable-catalyst-engagement-intake' ),
			__( 'Diagnostics', 'sustainable-catalyst-engagement-intake' ),
			'sc_intake_manage_settings',
			'sc-engagement-intake-diagnostics',
			array( __CLASS__, 'diagnostics_page' )
		);

		add_submenu_page(
			'sc-engagement-intake',
			__( 'Settings', 'sustainable-catalyst-engagement-intake' ),
			__( 'Settings', 'sustainable-catalyst-engagement-intake' ),
			'sc_intake_manage_settings',
			'sc-engagement-intake-settings',
			array( __CLASS__, 'settings_page' )
		);
	}

	public static function assets( string $hook ): void {
		if ( false === strpos( $hook, 'sc-engagement-intake' ) ) {
			return;
		}

		wp_enqueue_style(
			'sc-ei-admin',
			SC_EI_URL . 'assets/css/admin.css',
			array(),
			SC_EI_VERSION
		);

		wp_enqueue_script(
			'sc-ei-admin',
			SC_EI_URL . 'assets/js/admin.js',
			array(),
			SC_EI_VERSION,
			true
		);
	}

	public static function settings(): void {
		register_setting(
			'sc_ei_settings_group',
			'sc_ei_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => self::default_settings(),
			)
		);
	}

	public static function default_settings(): array {
		return array_merge(
			array(
			'delete_data_on_uninstall'           => 0,
				'abandoned_draft_days'               => 30,
				'minimum_completion_seconds'         => 3,
				'submissions_per_hour'               => 5,
				'teams_organizer_email'              => '',
				'default_teams_duration'             => 20,
				'upload_max_files'                   => 5,
				'upload_max_file_mb'                 => 20,
				'allowed_upload_extensions'          => array( 'pdf', 'docx', 'xlsx', 'csv', 'txt', 'png', 'jpg', 'jpeg' ),
				'require_external_scanner'           => 0,
				'scanner_test_freshness_hours'       => 24,
				'scanner_bulk_retry_limit'           => 25,
				'private_storage_path'               => '',
				'default_review_due_days'            => 3,
				'high_priority_review_due_days'      => 1,
				'low_priority_review_due_days'       => 7,
				'urgent_review_due_hours'            => 4,
				'stale_review_days'                  => 7,
				'review_bulk_limit'                  => 50,
				'reviewer_self_assignment'           => 1,
				'restrict_review_to_assignee'        => 1,
				'require_review_rationale'           => 1,
				'require_completion_checklist'       => 1,
				'communication_sender_name'          => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
				'communication_sender_email'         => sanitize_email( get_option( 'admin_email' ) ),
				'communication_reply_to_email'       => sanitize_email( get_option( 'admin_email' ) ),
				'notification_internal_recipients'   => '',
				'notification_escalation_recipients' => '',
				'sender_acknowledgment_enabled'      => 0,
				'internal_new_inquiry_enabled'       => 0,
				'review_due_reminders_enabled'       => 0,
				'follow_up_reminders_enabled'        => 0,
				'escalation_notifications_enabled'   => 0,
				'review_reminder_lead_hours'         => 24,
				'notification_batch_limit'           => 25,
			),
			SC_EI_Privacy_Schema::default_settings(),
			SC_EI_Fit_Schema::default_settings(),
			SC_EI_Portal_Schema::default_settings(),
			SC_EI_Workflow_Schema::default_settings(),
			SC_EI_Graph_Credentials::defaults()
		);
	}

	public static function sanitize_settings( $value ): array {
		$value   = is_array( $value ) ? $value : array();
		$current = wp_parse_args( get_option( 'sc_ei_settings', array() ), self::default_settings() );

		$freshness_hours = max( 1, min( 168, absint( $value['scanner_test_freshness_hours'] ?? $current['scanner_test_freshness_hours'] ) ) );
		$bulk_limit      = max( 1, min( 50, absint( $value['scanner_bulk_retry_limit'] ?? $current['scanner_bulk_retry_limit'] ) ) );
		$requested_clean = empty( $value['require_external_scanner'] ) ? 0 : 1;
		$provisional = array_merge(
			$current,
			array(
				'scanner_test_freshness_hours' => $freshness_hours,
				'scanner_bulk_retry_limit'     => $bulk_limit,
			)
		);
		if (
			$requested_clean
			&& empty( $current['require_external_scanner'] )
			&& ! SC_EI_Scanner_Operations::can_enable_required_mode( $provisional )
		) {
			$requested_clean = 0;
			add_settings_error(
				'sc_ei_settings',
				'scanner_readiness_required',
				__( 'Clean-required scanner mode was not enabled. Run a scanner readiness test and obtain a recent clean result first.', 'sustainable-catalyst-engagement-intake' ),
				'error'
			);
		}

		$sender_name  = sanitize_text_field( (string) ( $value['communication_sender_name'] ?? $current['communication_sender_name'] ) );
		$sender_email = sanitize_email( (string) ( $value['communication_sender_email'] ?? $current['communication_sender_email'] ) );
		$reply_email  = sanitize_email( (string) ( $value['communication_reply_to_email'] ?? $current['communication_reply_to_email'] ) );
		$internal_recipients = implode( ', ', SC_EI_Communication_Schema::sanitize_emails( $value['notification_internal_recipients'] ?? $current['notification_internal_recipients'], 10 ) );
		$escalation_recipients = implode( ', ', SC_EI_Communication_Schema::sanitize_emails( $value['notification_escalation_recipients'] ?? $current['notification_escalation_recipients'], 10 ) );

		$sender_ready = '' !== $sender_name && is_email( $sender_email ) && is_email( $reply_email );
		$sender_ack_enabled = empty( $value['sender_acknowledgment_enabled'] ) ? 0 : 1;
		$internal_new_enabled = empty( $value['internal_new_inquiry_enabled'] ) ? 0 : 1;
		$review_reminders_enabled = empty( $value['review_due_reminders_enabled'] ) ? 0 : 1;
		$follow_up_reminders_enabled = empty( $value['follow_up_reminders_enabled'] ) ? 0 : 1;
		$escalation_enabled = empty( $value['escalation_notifications_enabled'] ) ? 0 : 1;
		if ( ! $sender_ready && ( $sender_ack_enabled || $internal_new_enabled || $review_reminders_enabled || $follow_up_reminders_enabled || $escalation_enabled ) ) {
			$sender_ack_enabled = 0;
			$internal_new_enabled = 0;
			$review_reminders_enabled = 0;
			$follow_up_reminders_enabled = 0;
			$escalation_enabled = 0;
			add_settings_error(
				'sc_ei_settings',
				'communication_sender_required',
				__( 'Automatic notification policies were not enabled because the sender name, sender email, or reply-to email is invalid.', 'sustainable-catalyst-engagement-intake' ),
				'error'
			);
		}

		return array(
			'fit_assessment_enabled'                  => 1,
			'fit_advisory_score_enabled'              => empty( $value['fit_advisory_score_enabled'] ) ? 0 : 1,
			'fit_require_human_attestation'           => 1,
			'fit_require_evidence_for_assessed_items' => empty( $value['fit_require_evidence_for_assessed_items'] ) ? 0 : 1,
			'fit_require_rationale_for_finalization'  => empty( $value['fit_require_rationale_for_finalization'] ) ? 0 : 1,
			'fit_require_second_review_high_risk'     => empty( $value['fit_require_second_review_high_risk'] ) ? 0 : 1,
			'fit_require_second_review_conflict'      => empty( $value['fit_require_second_review_conflict'] ) ? 0 : 1,
			'fit_require_second_review_decline'       => empty( $value['fit_require_second_review_decline'] ) ? 0 : 1,
			'fit_require_second_review_unsafe_scope'  => empty( $value['fit_require_second_review_unsafe_scope'] ) ? 0 : 1,
			'fit_distinct_second_reviewer'            => empty( $value['fit_distinct_second_reviewer'] ) ? 0 : 1,
			'fit_assessment_stale_days'               => max( 1, min( 365, absint( $value['fit_assessment_stale_days'] ?? $current['fit_assessment_stale_days'] ) ) ),
			'fit_assessment_queue_limit'              => max( 10, min( 500, absint( $value['fit_assessment_queue_limit'] ?? $current['fit_assessment_queue_limit'] ) ) ),
			'portal_enabled'                    => 1,
			'portal_page_url'                   => SC_EI_Portal_Schema::sanitize_portal_page_url( (string) ( $value['portal_page_url'] ?? $current['portal_page_url'] ) ),
			'portal_invite_ttl_hours'           => max( 1, min( 720, absint( $value['portal_invite_ttl_hours'] ?? $current['portal_invite_ttl_hours'] ) ) ),
			'portal_session_ttl_minutes'        => max( 30, min( 4320, absint( $value['portal_session_ttl_minutes'] ?? $current['portal_session_ttl_minutes'] ) ) ),
			'portal_idle_timeout_minutes'       => max( 5, min( 1440, absint( $value['portal_idle_timeout_minutes'] ?? $current['portal_idle_timeout_minutes'] ) ) ),
			'portal_max_active_sessions'        => max( 1, min( 10, absint( $value['portal_max_active_sessions'] ?? $current['portal_max_active_sessions'] ) ) ),
			'portal_max_failed_attempts'        => max( 1, min( 20, absint( $value['portal_max_failed_attempts'] ?? $current['portal_max_failed_attempts'] ) ) ),
			'portal_lockout_minutes'            => max( 1, min( 1440, absint( $value['portal_lockout_minutes'] ?? $current['portal_lockout_minutes'] ) ) ),
			'portal_message_rate_limit_hour'    => max( 1, min( 100, absint( $value['portal_message_rate_limit_hour'] ?? $current['portal_message_rate_limit_hour'] ) ) ),
			'portal_update_rate_limit_hour'     => max( 1, min( 200, absint( $value['portal_update_rate_limit_hour'] ?? $current['portal_update_rate_limit_hour'] ) ) ),
			'portal_session_touch_seconds'      => max( 30, min( 900, absint( $value['portal_session_touch_seconds'] ?? $current['portal_session_touch_seconds'] ) ) ),
			'portal_event_retention_days'       => max( 30, min( 3650, absint( $value['portal_event_retention_days'] ?? $current['portal_event_retention_days'] ) ) ),
			'portal_recovery_enabled'            => 1,
			'portal_recovery_requests_per_hour'  => max( 1, min( 20, absint( $value['portal_recovery_requests_per_hour'] ?? $current['portal_recovery_requests_per_hour'] ) ) ),
			'portal_recovery_cooldown_minutes'   => max( 1, min( 1440, absint( $value['portal_recovery_cooldown_minutes'] ?? $current['portal_recovery_cooldown_minutes'] ) ) ),
			'portal_recovery_expiry_days'        => max( 1, min( 90, absint( $value['portal_recovery_expiry_days'] ?? $current['portal_recovery_expiry_days'] ) ) ),
			'portal_recovery_min_reason_chars'   => max( 0, min( 500, absint( $value['portal_recovery_min_reason_chars'] ?? $current['portal_recovery_min_reason_chars'] ) ) ),
			'portal_require_https'               => 1,
			'portal_allow_legacy_cookie'         => 1,
			'portal_allow_messages'             => empty( $value['portal_allow_messages'] ) ? 0 : 1,
			'portal_allow_documents'            => empty( $value['portal_allow_documents'] ) ? 0 : 1,
			'portal_allow_contact_updates'      => empty( $value['portal_allow_contact_updates'] ) ? 0 : 1,
			'portal_allow_scheduling_updates'   => empty( $value['portal_allow_scheduling_updates'] ) ? 0 : 1,
			'portal_allow_privacy_requests'     => empty( $value['portal_allow_privacy_requests'] ) ? 0 : 1,
			'portal_allow_withdrawal_requests'  => empty( $value['portal_allow_withdrawal_requests'] ) ? 0 : 1,
			'portal_require_email_challenge'    => 1,
			'portal_require_terms_acceptance'   => 1,
			'portal_terms_version'              => sanitize_text_field( (string) ( $value['portal_terms_version'] ?? $current['portal_terms_version'] ) ),
			'portal_default_permissions'        => SC_EI_Portal_Schema::sanitize_permissions( $value['portal_default_permissions'] ?? $current['portal_default_permissions'] ),
			'portal_cookie_samesite'            => 'Strict',
			'portal_cookie_httponly'            => 1,
			'portal_noindex'                    => 1,
			'portal_no_store'                   => 1,
			'workflow_enabled'                   => 1,
			'workflow_meeting_offer_expiry_days' => max( 1, min( 90, absint( $value['workflow_meeting_offer_expiry_days'] ?? $current['workflow_meeting_offer_expiry_days'] ) ) ),
			'workflow_proposal_expiry_days'      => max( 1, min( 180, absint( $value['workflow_proposal_expiry_days'] ?? $current['workflow_proposal_expiry_days'] ) ) ),
			'workflow_max_meeting_slots'         => max( 1, min( 10, absint( $value['workflow_max_meeting_slots'] ?? $current['workflow_max_meeting_slots'] ) ) ),
			'workflow_require_teams_url'         => empty( $value['workflow_require_teams_url'] ) ? 0 : 1,
			'workflow_allow_sender_ics'          => empty( $value['workflow_allow_sender_ics'] ) ? 0 : 1,
			'workflow_allow_proposal_acceptance' => empty( $value['workflow_allow_proposal_acceptance'] ) ? 0 : 1,
			'workflow_require_authority_attestation' => 1,
			'workflow_require_boundary_acknowledgment'=> 1,
			'workflow_no_auto_calendar'          => 1,
			'workflow_no_auto_contract'          => 1,
			'workflow_no_auto_payment'           => 1,
			'graph_enabled'                     => empty( $value['graph_enabled'] ) ? 0 : 1,
			'graph_tenant_id'                   => sanitize_text_field( (string) ( $value['graph_tenant_id'] ?? $current['graph_tenant_id'] ) ),
			'graph_client_id'                   => sanitize_text_field( (string) ( $value['graph_client_id'] ?? $current['graph_client_id'] ) ),
			'graph_organizer_user'              => sanitize_email( (string) ( $value['graph_organizer_user'] ?? $current['graph_organizer_user'] ) ),
			'graph_calendar_id'                 => sanitize_text_field( (string) ( $value['graph_calendar_id'] ?? $current['graph_calendar_id'] ) ),
			'graph_secret_expires_at'           => sanitize_text_field( (string) ( $value['graph_secret_expires_at'] ?? $current['graph_secret_expires_at'] ) ),
			'graph_include_sender_attendee'     => empty( $value['graph_include_sender_attendee'] ) ? 0 : 1,
			'graph_require_calendar_consent'    => 1,
			'graph_allow_remote_cancel'         => empty( $value['graph_allow_remote_cancel'] ) ? 0 : 1,
			'graph_retry_enabled'               => empty( $value['graph_retry_enabled'] ) ? 0 : 1,
			'graph_max_attempts'                => max( 1, min( 20, absint( $value['graph_max_attempts'] ?? $current['graph_max_attempts'] ) ) ),
			'graph_retry_base_seconds'          => max( 15, min( 900, absint( $value['graph_retry_base_seconds'] ?? $current['graph_retry_base_seconds'] ) ) ),
			'graph_retry_max_seconds'           => max( 60, min( DAY_IN_SECONDS, absint( $value['graph_retry_max_seconds'] ?? $current['graph_retry_max_seconds'] ) ) ),
			'graph_request_timeout_seconds'     => max( 10, min( 60, absint( $value['graph_request_timeout_seconds'] ?? $current['graph_request_timeout_seconds'] ) ) ),
			'graph_token_skew_seconds'          => max( 60, min( 900, absint( $value['graph_token_skew_seconds'] ?? $current['graph_token_skew_seconds'] ) ) ),
			'graph_circuit_failure_threshold'   => max( 2, min( 20, absint( $value['graph_circuit_failure_threshold'] ?? $current['graph_circuit_failure_threshold'] ) ) ),
			'graph_circuit_cooldown_minutes'    => max( 1, min( 1440, absint( $value['graph_circuit_cooldown_minutes'] ?? $current['graph_circuit_cooldown_minutes'] ) ) ),
			'graph_reconcile_delay_seconds'     => max( 10, min( 900, absint( $value['graph_reconcile_delay_seconds'] ?? $current['graph_reconcile_delay_seconds'] ) ) ),
			'graph_global_cloud_only'           => 1,
			'delete_data_on_uninstall'           => empty( $value['delete_data_on_uninstall'] ) ? 0 : 1,
			'default_unaccepted_retention_days'  => max( 30, min( 3650, absint( $value['default_unaccepted_retention_days'] ?? $current['default_unaccepted_retention_days'] ) ) ),
			'withdrawn_retention_days'           => max( 1, min( 3650, absint( $value['withdrawn_retention_days'] ?? $current['withdrawn_retention_days'] ) ) ),
			'closed_retention_days'              => max( 30, min( 3650, absint( $value['closed_retention_days'] ?? $current['closed_retention_days'] ) ) ),
			'accepted_retention_days'            => max( 365, min( 36500, absint( $value['accepted_retention_days'] ?? $current['accepted_retention_days'] ) ) ),
			'communication_retention_days'       => max( 30, min( 36500, absint( $value['communication_retention_days'] ?? $current['communication_retention_days'] ) ) ),
			'attachment_retention_days'          => max( 7, min( 3650, absint( $value['attachment_retention_days'] ?? $current['attachment_retention_days'] ) ) ),
			'privacy_request_due_days'           => max( 1, min( 365, absint( $value['privacy_request_due_days'] ?? $current['privacy_request_due_days'] ) ) ),
			'retention_queue_batch_limit'        => max( 1, min( 1000, absint( $value['retention_queue_batch_limit'] ?? $current['retention_queue_batch_limit'] ) ) ),
			'retention_execution_batch_limit'    => max( 1, min( 50, absint( $value['retention_execution_batch_limit'] ?? $current['retention_execution_batch_limit'] ) ) ),
			'require_retention_approval'         => 1,
			'require_distinct_retention_approver'=> empty( $value['require_distinct_retention_approver'] ) ? 0 : 1,
			'retention_cron_queue_only'          => 1,
			'retain_tombstones'                  => 1,
			'legal_hold_review_days'             => max( 1, min( 3650, absint( $value['legal_hold_review_days'] ?? $current['legal_hold_review_days'] ) ) ),
			'abandoned_draft_days'               => max( 1, min( 365, absint( $value['abandoned_draft_days'] ?? $current['abandoned_draft_days'] ) ) ),
			'minimum_completion_seconds'         => max( 1, min( 30, absint( $value['minimum_completion_seconds'] ?? $current['minimum_completion_seconds'] ) ) ),
			'submissions_per_hour'               => max( 1, min( 20, absint( $value['submissions_per_hour'] ?? $current['submissions_per_hour'] ) ) ),
			'teams_organizer_email'              => sanitize_email( $value['teams_organizer_email'] ?? $current['teams_organizer_email'] ),
			'default_teams_duration'             => in_array( absint( $value['default_teams_duration'] ?? $current['default_teams_duration'] ), array( 20, 30, 45, 60, 90 ), true ) ? absint( $value['default_teams_duration'] ?? $current['default_teams_duration'] ) : 20,
			'upload_max_files'                   => max( 1, min( 10, absint( $value['upload_max_files'] ?? $current['upload_max_files'] ) ) ),
			'upload_max_file_mb'                 => max( 1, min( 100, absint( $value['upload_max_file_mb'] ?? $current['upload_max_file_mb'] ) ) ),
			'allowed_upload_extensions'          => self::sanitize_upload_extensions( $value['allowed_upload_extensions'] ?? $current['allowed_upload_extensions'] ),
			'require_external_scanner'           => $requested_clean,
			'scanner_test_freshness_hours'       => $freshness_hours,
			'scanner_bulk_retry_limit'           => $bulk_limit,
			'private_storage_path'               => self::sanitize_private_storage_path( (string) ( $value['private_storage_path'] ?? $current['private_storage_path'] ) ),
			'default_review_due_days'            => max( 1, min( 30, absint( $value['default_review_due_days'] ?? $current['default_review_due_days'] ) ) ),
			'high_priority_review_due_days'      => max( 1, min( 14, absint( $value['high_priority_review_due_days'] ?? $current['high_priority_review_due_days'] ) ) ),
			'low_priority_review_due_days'       => max( 1, min( 60, absint( $value['low_priority_review_due_days'] ?? $current['low_priority_review_due_days'] ) ) ),
			'urgent_review_due_hours'            => max( 1, min( 72, absint( $value['urgent_review_due_hours'] ?? $current['urgent_review_due_hours'] ) ) ),
			'stale_review_days'                  => max( 1, min( 90, absint( $value['stale_review_days'] ?? $current['stale_review_days'] ) ) ),
			'review_bulk_limit'                  => max( 1, min( 50, absint( $value['review_bulk_limit'] ?? $current['review_bulk_limit'] ) ) ),
			'reviewer_self_assignment'           => empty( $value['reviewer_self_assignment'] ) ? 0 : 1,
			'restrict_review_to_assignee'        => empty( $value['restrict_review_to_assignee'] ) ? 0 : 1,
			'require_review_rationale'           => empty( $value['require_review_rationale'] ) ? 0 : 1,
			'require_completion_checklist'       => empty( $value['require_completion_checklist'] ) ? 0 : 1,
			'communication_sender_name'          => $sender_name,
			'communication_sender_email'         => $sender_email,
			'communication_reply_to_email'       => $reply_email,
			'notification_internal_recipients'   => $internal_recipients,
			'notification_escalation_recipients' => $escalation_recipients,
			'sender_acknowledgment_enabled'      => $sender_ack_enabled,
			'internal_new_inquiry_enabled'       => $internal_new_enabled,
			'review_due_reminders_enabled'       => $review_reminders_enabled,
			'follow_up_reminders_enabled'        => $follow_up_reminders_enabled,
			'escalation_notifications_enabled'   => $escalation_enabled,
			'review_reminder_lead_hours'         => max( 0, min( 168, absint( $value['review_reminder_lead_hours'] ?? $current['review_reminder_lead_hours'] ) ) ),
			'notification_batch_limit'           => max( 1, min( 100, absint( $value['notification_batch_limit'] ?? $current['notification_batch_limit'] ) ) ),
		);
	}

	private static function sanitize_upload_extensions( $extensions ): array {
		$supported = array_keys( SC_EI_Upload_Validator::supported_extensions() );
		$clean     = array_values( array_intersect( $supported, array_map( 'sanitize_key', (array) $extensions ) ) );

		return $clean ?: $supported;
	}

	private static function sanitize_private_storage_path( string $path ): string {
		$locked = get_option( 'sc_ei_storage_base_dir', '' );
		if ( is_string( $locked ) && '' !== trim( $locked ) ) {
			return untrailingslashit( wp_normalize_path( $locked ) );
		}

		$path = trim( wp_normalize_path( $path ) );
		if ( '' === $path ) {
			return '';
		}

		if ( str_contains( $path, "\0" ) || str_contains( $path, '../' ) || str_contains( $path, '/..' ) ) {
			return '';
		}

		$is_unix_absolute    = str_starts_with( $path, '/' );
		$is_windows_absolute = (bool) preg_match( '/^[A-Za-z]:\//', $path );

		return ( $is_unix_absolute || $is_windows_absolute ) ? untrailingslashit( $path ) : '';
	}

	public static function screen_option( $status, string $option, $value ) {
		if ( in_array( $option, array( 'sc_ei_inquiries_per_page', 'sc_ei_quarantine_per_page', 'sc_ei_file_audit_per_page' ), true ) ) {
			return max( 1, min( 100, absint( $value ) ) );
		}
		return $status;
	}

	public static function plugin_links( array $links ): array {
		array_unshift(
			$links,
			sprintf(
				'<a href="%1$s">%2$s</a> · <a href="%3$s">%4$s</a> · <a href="%5$s">%6$s</a> · <a href="%7$s">%8$s</a> · <a href="%9$s">%10$s</a> · <a href="%11$s">%12$s</a>',
				esc_url( admin_url( 'admin.php?page=sc-engagement-intake' ) ),
				esc_html__( 'Inquiries', 'sustainable-catalyst-engagement-intake' ),
				esc_url( admin_url( 'admin.php?page=sc-engagement-intake-review' ) ),
				esc_html__( 'Review Workspace', 'sustainable-catalyst-engagement-intake' ),
				esc_url( admin_url( 'admin.php?page=sc-engagement-intake-communications' ) ),
				esc_html__( 'Communications', 'sustainable-catalyst-engagement-intake' ),
				esc_url( admin_url( 'admin.php?page=sc-engagement-intake-fit' ) ),
				esc_html__( 'Fit Assessment', 'sustainable-catalyst-engagement-intake' ),
				esc_url( admin_url( 'admin.php?page=sc-engagement-intake-privacy' ) ),
				esc_html__( 'Privacy Center', 'sustainable-catalyst-engagement-intake' ),
				esc_url( admin_url( 'admin.php?page=sc-engagement-intake-quarantine' ) ),
				esc_html__( 'Quarantine', 'sustainable-catalyst-engagement-intake' )
			)
		);
		return $links;
	}

	public static function inquiries_page(): void {
		if ( ! current_user_can( 'sc_intake_view' ) ) {
			wp_die( esc_html__( 'You do not have permission to view private inquiries.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
		$id     = isset( $_GET['inquiry'] ) ? absint( $_GET['inquiry'] ) : 0;

		if ( 'view' === $action && $id ) {
			self::render_inquiry( $id );
			return;
		}

		$list_table = new SC_EI_Admin_List_Table();
		$list_table->prepare_items();
		include SC_EI_DIR . 'admin/views/inquiries-list.php';
	}

	private static function render_inquiry( int $id ): void {
		$inquiry = SC_EI_Inquiry_Repository::find( $id );
		if ( ! $inquiry ) {
			wp_die( esc_html__( 'Inquiry not found.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$attachments = SC_EI_Attachment_Repository::for_inquiry( $id );
		$audit_log   = SC_EI_Audit_Log::for_inquiry( $id );
		include SC_EI_DIR . 'admin/views/inquiry-view.php';
	}

	public static function quarantine_page(): void {
		if ( ! current_user_can( 'sc_intake_review' ) ) {
			wp_die( esc_html__( 'You do not have permission to review private document quarantine.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'queue';
		if ( ! in_array( $view, array( 'queue', 'access', 'guidance' ), true ) ) {
			$view = 'queue';
		}
		if ( 'access' === $view && ! current_user_can( 'sc_intake_view_file_audit' ) ) {
			$view = 'queue';
		}

		$settings      = wp_parse_args( get_option( 'sc_ei_settings', array() ), self::default_settings() );
		$summary       = SC_EI_Attachment_Repository::operational_summary();
		$storage       = SC_EI_Storage::utilization();
		$readiness     = SC_EI_Scanner_Operations::readiness( $settings );
		$audit_summary = SC_EI_Audit_Log::file_event_summary();

		$quarantine_table = null;
		$access_table     = null;

		if ( 'queue' === $view ) {
			$quarantine_table = new SC_EI_Quarantine_List_Table();
			$quarantine_table->prepare_items();
		} elseif ( 'access' === $view ) {
			$access_table = new SC_EI_File_Access_List_Table();
			$access_table->prepare_items();
		}

		include SC_EI_DIR . 'admin/views/quarantine.php';
	}

	public static function diagnostics_page(): void {
		if ( ! current_user_can( 'sc_intake_manage_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to view diagnostics.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$diagnostics = SC_EI_Diagnostics::run();
		$status      = SC_EI_Diagnostics::overall_status( $diagnostics );
		include SC_EI_DIR . 'admin/views/diagnostics.php';
	}

	public static function settings_page(): void {
		if ( ! current_user_can( 'sc_intake_manage_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage settings.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), self::default_settings() );
		include SC_EI_DIR . 'admin/views/settings.php';
	}

	public static function handle_status(): void {
		if ( ! current_user_can( 'sc_intake_change_status' ) ) {
			wp_die( esc_html__( 'You do not have permission to change inquiry status.', 'sustainable-catalyst-engagement-intake' ) );
		}

		check_admin_referer( 'sc_ei_update_status' );

		$id     = isset( $_POST['inquiry_id'] ) ? absint( $_POST['inquiry_id'] ) : 0;
		$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		$note   = isset( $_POST['status_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['status_note'] ) ) : '';

		$success = $id && SC_EI_Inquiry_Repository::update_status( $id, $status, $note );

		$url = add_query_arg(
			array(
				'page'      => 'sc-engagement-intake',
				'action'    => 'view',
				'inquiry'   => $id,
				'sc_ei_msg' => $success ? 'status_updated' : 'error',
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}


	public static function handle_scheduling(): void {
		if ( ! current_user_can( 'sc_intake_review' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage Microsoft Teams scheduling.', 'sustainable-catalyst-engagement-intake' ) );
		}

		check_admin_referer( 'sc_ei_update_scheduling' );

		$id = isset( $_POST['inquiry_id'] ) ? absint( $_POST['inquiry_id'] ) : 0;
		$input = array(
			'scheduling_status'      => isset( $_POST['scheduling_status'] ) ? sanitize_key( wp_unslash( $_POST['scheduling_status'] ) ) : '',
			'teams_meeting_url'      => isset( $_POST['teams_meeting_url'] ) ? esc_url_raw( wp_unslash( $_POST['teams_meeting_url'] ) ) : '',
			'scheduled_start_local'  => isset( $_POST['scheduled_start_local'] ) ? sanitize_text_field( wp_unslash( $_POST['scheduled_start_local'] ) ) : '',
			'scheduled_end_local'    => isset( $_POST['scheduled_end_local'] ) ? sanitize_text_field( wp_unslash( $_POST['scheduled_end_local'] ) ) : '',
			'scheduled_timezone'     => isset( $_POST['scheduled_timezone'] ) ? sanitize_text_field( wp_unslash( $_POST['scheduled_timezone'] ) ) : '',
			'calendar_event_id'      => isset( $_POST['calendar_event_id'] ) ? sanitize_text_field( wp_unslash( $_POST['calendar_event_id'] ) ) : '',
			'scheduling_admin_note'  => isset( $_POST['scheduling_admin_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['scheduling_admin_note'] ) ) : '',
		);

		$success = $id && SC_EI_Inquiry_Repository::update_scheduling( $id, $input );

		$url = add_query_arg(
			array(
				'page'      => 'sc-engagement-intake',
				'action'    => 'view',
				'inquiry'   => $id,
				'sc_ei_msg' => $success ? 'scheduling_updated' : 'scheduling_error',
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	public static function handle_attachment_download(): void {
		if ( ! current_user_can( 'sc_intake_download_files' ) ) {
			wp_die( esc_html__( 'You do not have permission to download private attachments.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		$id = isset( $_GET['attachment'] ) ? absint( $_GET['attachment'] ) : 0;
		check_admin_referer( 'sc_ei_download_attachment_' . $id );

		$attachment = SC_EI_Attachment_Repository::find( $id );
		if ( ! $attachment || ! empty( $attachment['deleted_at'] ) || in_array( $attachment['quarantine_status'], array( 'deleted', 'rejected' ), true ) ) {
			wp_die( esc_html__( 'The private attachment is unavailable.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 404 ) );
		}

		if ( 'infected' === $attachment['scan_status'] ) {
			wp_die( esc_html__( 'The attachment was identified as infected and cannot be downloaded.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		$verification = SC_EI_Attachment_Repository::verify_record( $attachment, get_current_user_id(), 'download' );
		if ( empty( $verification['ok'] ) ) {
			SC_EI_Audit_Log::record(
				'attachment_integrity_mismatch',
				'Download blocked because the stored file did not match its SHA-256 fingerprint.',
				array(
					'sha256'        => $attachment['sha256'],
					'storage_status'=> $verification['status'] ?? 'attention',
				),
				(int) $attachment['inquiry_id'],
				$id
			);
			wp_die( esc_html__( 'The attachment failed its integrity check and cannot be downloaded.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 409 ) );
		}

		SC_EI_Attachment_Repository::record_download( $id, get_current_user_id(), 'verified' );
		SC_EI_Storage::stream_download( $attachment );
	}

	public static function handle_attachment_integrity(): void {
		if ( ! current_user_can( 'sc_intake_download_files' ) ) {
			wp_die( esc_html__( 'You do not have permission to verify private attachments.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		$id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
		check_admin_referer( 'sc_ei_verify_attachment_integrity_' . $id );

		$attachment = SC_EI_Attachment_Repository::find( $id );
		$result     = $attachment
			? SC_EI_Attachment_Repository::verify_record( $attachment, get_current_user_id(), 'manual' )
			: array( 'ok' => false );

		self::redirect_to_inquiry(
			(int) ( $attachment['inquiry_id'] ?? 0 ),
			! empty( $result['ok'] ) ? 'attachment_verified' : 'attachment_verification_failed'
		);
	}

	public static function handle_storage_probe(): void {
		self::require_diagnostics_capability();
		check_admin_referer( 'sc_ei_run_storage_probe' );

		$probe = SC_EI_Storage::probe();
		self::redirect_to_diagnostics( ! empty( $probe['ok'] ) ? 'storage_probe_passed' : 'storage_probe_failed' );
	}

	public static function handle_storage_repair(): void {
		self::require_diagnostics_capability();
		check_admin_referer( 'sc_ei_repair_storage' );

		$result = SC_EI_Storage::repair();
		SC_EI_Audit_Log::record(
			'storage_repair_completed',
			'Protected storage repair and probe completed.',
			array(
				'ok'                    => ! empty( $result['ok'] ),
				'stale_staging_deleted' => absint( $result['stale_staging_deleted'] ?? 0 ),
			),
			null,
			null,
			get_current_user_id()
		);

		self::redirect_to_diagnostics( ! empty( $result['ok'] ) ? 'storage_repaired' : 'storage_repair_failed' );
	}

	public static function handle_storage_reconciliation(): void {
		self::require_diagnostics_capability();
		check_admin_referer( 'sc_ei_run_storage_reconciliation' );

		$report = SC_EI_Storage_Reconciler::run( 1000, 5000 );
		$issues = array_sum(
			array_intersect_key(
				(array) ( $report['counts'] ?? array() ),
				array_flip( array( 'missing_files', 'hash_mismatches', 'size_mismatches', 'misplaced_files', 'unresolvable_paths', 'orphan_files' ) )
			)
		);

		self::redirect_to_diagnostics( $issues > 0 ? 'reconciliation_attention' : 'reconciliation_clean' );
	}

	public static function handle_retention_preview(): void {
		if ( ! current_user_can( 'sc_intake_manage_retention_policies' ) ) {
			wp_die( esc_html__( 'You do not have permission to preview retention candidates.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'sc_ei_preview_retention_cleanup' );
		$preview = SC_EI_Retention::preview( 250 );
		update_option( 'sc_ei_last_retention_preview', $preview, false );
		update_option( 'sc_ei_last_privacy_retention_preview', $preview, false );
		self::redirect_to_diagnostics( 'retention_preview_ready' );
	}

	public static function handle_retention_cleanup(): void {
		if ( ! current_user_can( 'sc_intake_manage_retention_policies' ) ) {
			wp_die( esc_html__( 'You do not have permission to queue retention candidates.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'sc_ei_run_retention_cleanup' );
		$confirmation = isset( $_POST['cleanup_confirmation'] ) ? strtoupper( trim( sanitize_text_field( wp_unslash( $_POST['cleanup_confirmation'] ) ) ) ) : '';

		if ( 'QUEUE CANDIDATES' !== $confirmation ) {
			self::redirect_to_diagnostics( 'retention_confirmation_failed' );
		}

		SC_EI_Retention_Engine::queue_candidates( 250, get_current_user_id(), 'legacy_diagnostics' );
		self::redirect_to_diagnostics( 'retention_queue_completed' );
	}

	public static function handle_scanner_readiness_test(): void {
		if ( ! current_user_can( 'sc_intake_manage_scanner' ) ) {
			wp_die( esc_html__( 'You do not have permission to test the external scanner.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'sc_ei_run_scanner_readiness_test' );
		$result = SC_EI_Scanner_Operations::run_readiness_test( get_current_user_id() );
		$clean  = 'clean' === sanitize_key( (string) ( $result['scan_status'] ?? '' ) )
			&& ! empty( $result['probe_configured'] )
			&& ! empty( $result['test_file_deleted'] );

		self::redirect_to_quarantine( $clean ? 'scanner_test_clean' : 'scanner_test_attention' );
	}

	public static function handle_attachment_scan_retry(): void {
		if ( ! current_user_can( 'sc_intake_manage_scanner' ) ) {
			wp_die( esc_html__( 'You do not have permission to retry private attachment scans.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		$id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
		check_admin_referer( 'sc_ei_retry_attachment_scan_' . $id );

		$attachment = SC_EI_Attachment_Repository::find( $id );
		$result     = $attachment
			? SC_EI_Scanner_Operations::rescan_attachment( $id, get_current_user_id(), 'single_retry' )
			: array( 'ok' => false, 'status' => 'unavailable' );

		self::redirect_to_inquiry(
			(int) ( $attachment['inquiry_id'] ?? 0 ),
			! empty( $result['ok'] ) && 'clean' === ( $result['status'] ?? '' )
				? 'attachment_scan_clean'
				: 'attachment_scan_attention'
		);
	}

	public static function handle_quarantine_bulk(): void {
		if ( ! current_user_can( 'sc_intake_bulk_file_actions' ) ) {
			wp_die( esc_html__( 'You do not have permission to run bulk private-document operations.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'sc_ei_quarantine_bulk' );

		$operation = isset( $_POST['bulk_operation'] ) ? sanitize_key( wp_unslash( $_POST['bulk_operation'] ) ) : '';
		$ids       = isset( $_POST['attachment_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['attachment_ids'] ) ) : array();
		$ids       = array_slice( array_values( array_unique( array_filter( $ids ) ) ), 0, 50 );

		$allowed = array( 'retry_scan', 'verify_integrity', 'approve', 'quarantine', 'replacement_requested', 'set_retention', 'reject_delete' );
		if ( ! $ids || ! in_array( $operation, $allowed, true ) || ! self::bulk_operation_allowed( $operation ) ) {
			self::redirect_to_quarantine( 'bulk_error' );
		}

		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), self::default_settings() );
		$result   = array(
			'operation' => $operation,
			'selected'  => count( $ids ),
			'processed' => 0,
			'succeeded' => 0,
			'failed'    => 0,
			'skipped'   => 0,
			'details'   => array(),
		);

		if ( 'retry_scan' === $operation ) {
			$limit      = max( 1, min( 50, absint( $settings['scanner_bulk_retry_limit'] ?? 25 ) ) );
			$scan_result= SC_EI_Scanner_Operations::bulk_rescan( $ids, get_current_user_id(), $limit );
			$result['processed'] = absint( $scan_result['processed'] ?? 0 );
			$result['succeeded'] = absint( $scan_result['clean'] ?? 0 );
			$result['failed']    = absint( $scan_result['infected'] ?? 0 ) + absint( $scan_result['error'] ?? 0 );
			$result['skipped']   = max( 0, count( $ids ) - $result['processed'] ) + absint( $scan_result['skipped'] ?? 0 );
			$result['details']   = (array) ( $scan_result['details'] ?? array() );
		} else {
			$retention = null;
			if ( 'set_retention' === $operation ) {
				$date = isset( $_POST['bulk_retention_date'] ) ? sanitize_text_field( wp_unslash( $_POST['bulk_retention_date'] ) ) : '';
				$retention = self::local_date_to_utc_end( $date );
				if ( ! $retention ) {
					self::redirect_to_quarantine( 'bulk_error' );
				}
			}

			if ( 'reject_delete' === $operation ) {
				$confirmation = isset( $_POST['bulk_confirmation'] ) ? sanitize_text_field( wp_unslash( $_POST['bulk_confirmation'] ) ) : '';
				if ( 'REJECT SELECTED' !== $confirmation ) {
					self::redirect_to_quarantine( 'bulk_error' );
				}
			}

			foreach ( SC_EI_Attachment_Repository::find_many( $ids, 50 ) as $attachment ) {
				$result['processed']++;
				$success = false;
				$message = '';

				if ( ! empty( $attachment['deleted_at'] ) ) {
					$result['skipped']++;
					$message = 'Attachment already deleted.';
				} elseif ( 'verify_integrity' === $operation ) {
					$verification = SC_EI_Attachment_Repository::verify_record( $attachment, get_current_user_id(), 'bulk_verification' );
					$success = ! empty( $verification['ok'] );
					$message = (string) ( $verification['message'] ?? '' );
				} elseif ( 'approve' === $operation ) {
					if ( self::attachment_can_be_approved( $attachment, $settings ) ) {
						$success = self::transition_attachment_storage_status(
							$attachment,
							'approved',
							'Private attachment approved through a guarded bulk quarantine operation.'
						);
					} else {
						$message = 'Validation, storage, integrity, or scanner policy blocked approval.';
					}
				} elseif ( 'quarantine' === $operation ) {
					$success = self::transition_attachment_storage_status(
						$attachment,
						'quarantined',
						'Private attachment returned to quarantine through a bulk operation.'
					);
				} elseif ( 'replacement_requested' === $operation ) {
					$success = self::transition_attachment_storage_status(
						$attachment,
						'replacement_requested',
						'Replacement requested through a bulk quarantine operation.'
					);
				} elseif ( 'set_retention' === $operation ) {
					$success = SC_EI_Attachment_Repository::update_retention(
						absint( $attachment['id'] ),
						$retention,
						get_current_user_id()
					);
				} elseif ( 'reject_delete' === $operation ) {
					$deleted = SC_EI_Storage::delete_file( (string) $attachment['relative_path'] );
					$success = $deleted && SC_EI_Attachment_Repository::mark_deleted(
						absint( $attachment['id'] ),
						get_current_user_id(),
						'Private attachment rejected and physically deleted through a confirmed bulk operation.',
						'rejected'
					);
				}

				if ( $success ) {
					$result['succeeded']++;
				} elseif ( 'Attachment already deleted.' !== $message ) {
					$result['failed']++;
				}

				if ( count( $result['details'] ) < 50 ) {
					$result['details'][] = array(
						'attachment_id' => absint( $attachment['id'] ),
						'original_name' => sanitize_file_name( (string) $attachment['original_name'] ),
						'success'       => $success,
						'message'       => sanitize_text_field( $message ),
					);
				}
			}

			$result['skipped'] += max( 0, count( $ids ) - $result['processed'] );
		}

		SC_EI_Audit_Log::record(
			'quarantine_bulk_action_completed',
			'Guarded bulk private-document operation completed.',
			$result,
			null,
			null,
			get_current_user_id()
		);

		set_transient( 'sc_ei_quarantine_bulk_result_' . get_current_user_id(), $result, 5 * MINUTE_IN_SECONDS );
		self::redirect_to_quarantine( 'bulk_completed' );
	}

	public static function handle_file_audit_export(): void {
		if ( ! current_user_can( 'sc_intake_view_file_audit' ) ) {
			wp_die( esc_html__( 'You do not have permission to export the private document audit.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'sc_ei_export_file_audit' );

		$filters = array(
			'event_type' => isset( $_GET['event_type'] ) ? sanitize_key( wp_unslash( $_GET['event_type'] ) ) : '',
			'actor'      => isset( $_GET['actor'] ) ? absint( $_GET['actor'] ) : 0,
			'date_from'  => isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '',
			'date_to'    => isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '',
			'search'     => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
		);

		$rows = array();
		for ( $page = 1; $page <= 50; $page++ ) {
			$result = SC_EI_Audit_Log::query_file_events(
				array_merge(
					$filters,
					array(
						'page'     => $page,
						'per_page' => 100,
						'orderby'  => 'created_at',
						'order'    => 'DESC',
					)
				)
			);
			$rows = array_merge( $rows, $result['items'] );
			if ( $page >= absint( $result['total_pages'] ) || count( $rows ) >= 5000 ) {
				break;
			}
		}

		SC_EI_Audit_Log::record(
			'file_audit_exported',
			'Authorized user exported a filtered private document audit report.',
			array(
				'filters'   => $filters,
				'row_count' => count( $rows ),
			),
			null,
			null,
			get_current_user_id()
		);

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="engagement-intake-file-audit-' . gmdate( 'Y-m-d-His' ) . '.csv"' );
		header( 'X-Content-Type-Options: nosniff' );

		$output = fopen( 'php://output', 'wb' );
		if ( false === $output ) {
			wp_die( esc_html__( 'The CSV export stream could not be opened.', 'sustainable-catalyst-engagement-intake' ) );
		}

		fputcsv(
			$output,
			array(
				'event_id',
				'created_at_utc',
				'event_type',
				'inquiry_reference',
				'attachment_name',
				'actor',
				'actor_email',
				'message',
				'context_json',
			),
			',',
			'"',
			''
		);

		foreach ( array_slice( $rows, 0, 5000 ) as $row ) {
			fputcsv(
				$output,
				array_map(
					array( __CLASS__, 'csv_cell' ),
					array(
						$row['id'],
						$row['created_at'],
						$row['event_type'],
						$row['reference'],
						$row['original_name'],
						$row['actor_name'] ?: 'System',
						$row['actor_email'],
						$row['event_message'],
						$row['context_json'],
					)
				),
				',',
				'"',
				''
			);
		}

		fclose( $output );
		exit;
	}

	private static function bulk_operation_allowed( string $operation ): bool {
		return match ( $operation ) {
			'retry_scan'            => current_user_can( 'sc_intake_manage_scanner' ),
			'verify_integrity'       => current_user_can( 'sc_intake_download_files' ),
			'approve',
			'quarantine',
			'replacement_requested' => current_user_can( 'sc_intake_release_files' ),
			'set_retention'          => current_user_can( 'sc_intake_manage_file_retention' ),
			'reject_delete'          => current_user_can( 'sc_intake_delete' ),
			default                  => false,
		};
	}

	private static function attachment_can_be_approved( array $attachment, array $settings ): bool {
		if ( 'validated' !== (string) $attachment['validation_status'] || 'infected' === (string) $attachment['scan_status'] ) {
			return false;
		}

		if ( ! empty( $settings['require_external_scanner'] ) && 'clean' !== (string) $attachment['scan_status'] ) {
			return false;
		}

		$verification = SC_EI_Attachment_Repository::verify_record( $attachment, get_current_user_id(), 'bulk_approval' );
		return ! empty( $verification['ok'] );
	}

	private static function local_date_to_utc_end( string $date ): ?string {
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return null;
		}

		try {
			$local = new DateTimeImmutable( $date . ' 23:59:59', wp_timezone() );
			return $local->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
		} catch ( Throwable $exception ) {
			return null;
		}
	}

	private static function csv_cell( $value ): string {
		$value = is_scalar( $value ) || null === $value ? (string) $value : wp_json_encode( $value );
		$value = str_replace( "\0", '', $value );
		return preg_match( '/^[=+\-@]/', $value ) ? "'" . $value : $value;
	}

	private static function require_diagnostics_capability(): void {
		if ( ! current_user_can( 'sc_intake_manage_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to run intake diagnostics.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}
	}

	private static function redirect_to_quarantine( string $message, string $view = 'queue' ): void {
		$url = add_query_arg(
			array(
				'page'      => 'sc-engagement-intake-quarantine',
				'view'      => sanitize_key( $view ),
				'sc_ei_msg' => sanitize_key( $message ),
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url, 303 );
		exit;
	}

	private static function redirect_to_diagnostics( string $message ): void {
		$url = add_query_arg(
			array(
				'page'      => 'sc-engagement-intake-diagnostics',
				'sc_ei_msg' => sanitize_key( $message ),
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url, 303 );
		exit;
	}

	private static function transition_attachment_storage_status(
		array $attachment,
		string $target_status,
		string $note
	): bool {
		$id           = absint( $attachment['id'] ?? 0 );
		$old_relative = (string) ( $attachment['relative_path'] ?? '' );
		$old_status   = sanitize_key( (string) ( $attachment['quarantine_status'] ?? 'quarantined' ) );
		$target_area  = 'approved' === $target_status ? 'approved/' : 'quarantine/';
		$current_area = str_starts_with( $old_relative, 'approved/' ) ? 'approved/' : 'quarantine/';
		$new_relative = $old_relative;
		$moved        = false;

		if ( $target_area !== $current_area ) {
			$new_relative = 'approved/' === $target_area
				? SC_EI_Storage::move_to_approved( $old_relative )
				: SC_EI_Storage::move_to_quarantine( $old_relative );

			if ( ! $new_relative ) {
				return false;
			}
			$moved = true;
		}

		$new_absolute = SC_EI_Storage::absolute_path( $new_relative );
		$size_ok      = $new_absolute
			&& is_file( $new_absolute )
			&& (int) filesize( $new_absolute ) === absint( $attachment['size_bytes'] ?? 0 );
		$hash_ok      = SC_EI_Storage::verify_integrity( $new_relative, (string) ( $attachment['sha256'] ?? '' ) );

		if ( ! $size_ok || ! $hash_ok ) {
			if ( $moved ) {
				'approved/' === $target_area
					? SC_EI_Storage::move_to_quarantine( $new_relative )
					: SC_EI_Storage::move_to_approved( $new_relative );
			}
			return false;
		}

		if ( $moved && ! SC_EI_Attachment_Repository::update_relative_path( $id, $new_relative ) ) {
			'approved/' === $target_area
				? SC_EI_Storage::move_to_quarantine( $new_relative )
				: SC_EI_Storage::move_to_approved( $new_relative );
			return false;
		}

		$updated = SC_EI_Attachment_Repository::update_quarantine_status(
			$id,
			$target_status,
			get_current_user_id(),
			$note
		);

		if ( ! $updated ) {
			if ( $moved ) {
				$rolled_back = 'approved/' === $target_area
					? SC_EI_Storage::move_to_quarantine( $new_relative )
					: SC_EI_Storage::move_to_approved( $new_relative );

				if ( $rolled_back ) {
					SC_EI_Attachment_Repository::update_relative_path( $id, $old_relative );
				}
			}
			return false;
		}

		$fresh = SC_EI_Attachment_Repository::find( $id );
		if ( $fresh ) {
			SC_EI_Attachment_Repository::verify_record( $fresh, get_current_user_id(), 'manual' );
		}

		return true;
	}

	public static function handle_attachment_status(): void {
		if ( ! current_user_can( 'sc_intake_release_files' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage private attachment quarantine status.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
		check_admin_referer( 'sc_ei_update_attachment_status_' . $id );

		$status = isset( $_POST['attachment_status'] ) ? sanitize_key( wp_unslash( $_POST['attachment_status'] ) ) : '';
		$note   = isset( $_POST['attachment_status_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['attachment_status_note'] ) ) : '';

		$attachment = SC_EI_Attachment_Repository::find( $id );
		$success    = false;

		if ( $attachment && empty( $attachment['deleted_at'] ) ) {
			if ( 'approved' === $status ) {
				$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), self::default_settings() );
				$scan_ok  = 'infected' !== $attachment['scan_status'];
				if ( ! empty( $settings['require_external_scanner'] ) ) {
					$scan_ok = 'clean' === $attachment['scan_status'];
				}

				$verification = SC_EI_Attachment_Repository::verify_record( $attachment, get_current_user_id(), 'manual' );
				if ( 'validated' === $attachment['validation_status'] && $scan_ok && ! empty( $verification['ok'] ) ) {
					$success = self::transition_attachment_storage_status(
						$attachment,
						'approved',
						$note ?: 'Private attachment approved for controlled use.'
					);
				}
			} elseif ( 'replacement_requested' === $status ) {
				$success = self::transition_attachment_storage_status(
					$attachment,
					'replacement_requested',
					$note ?: 'A replacement document was requested.'
				);
			} elseif ( 'quarantined' === $status ) {
				$success = self::transition_attachment_storage_status(
					$attachment,
					'quarantined',
					$note ?: 'Attachment returned to quarantine review.'
				);
			} elseif ( 'rejected' === $status ) {
				$deleted = SC_EI_Storage::delete_file( (string) $attachment['relative_path'] );
				if ( $deleted ) {
					$success = SC_EI_Attachment_Repository::mark_deleted(
						$id,
						get_current_user_id(),
						$note ?: 'Private attachment rejected and removed from protected storage.',
						'rejected'
					);
				}
			}
		}

		self::redirect_to_inquiry(
			(int) ( $attachment['inquiry_id'] ?? 0 ),
			$success ? 'attachment_updated' : 'attachment_error'
		);
	}

	public static function handle_attachment_retention(): void {
		if ( ! current_user_can( 'sc_intake_manage_file_retention' ) ) {
			wp_die( esc_html__( 'You do not have permission to change attachment retention.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
		check_admin_referer( 'sc_ei_update_attachment_retention_' . $id );

		$attachment = SC_EI_Attachment_Repository::find( $id );
		$date_value = isset( $_POST['retention_date'] ) ? sanitize_text_field( wp_unslash( $_POST['retention_date'] ) ) : '';
		$retention  = null;

		if ( $date_value ) {
			try {
				$local     = new DateTimeImmutable( $date_value . ' 23:59:59', wp_timezone() );
				$retention = $local->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
			} catch ( Throwable $exception ) {
				$retention = null;
			}
		}

		$success = $attachment && SC_EI_Attachment_Repository::update_retention( $id, $retention, get_current_user_id() );
		self::redirect_to_inquiry(
			(int) ( $attachment['inquiry_id'] ?? 0 ),
			$success ? 'attachment_retention_updated' : 'attachment_error'
		);
	}

	public static function handle_attachment_delete(): void {
		if ( ! current_user_can( 'sc_intake_delete' ) ) {
			wp_die( esc_html__( 'You do not have permission to permanently delete private attachments.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
		check_admin_referer( 'sc_ei_delete_attachment_' . $id );

		$attachment = SC_EI_Attachment_Repository::find( $id );
		$reason     = isset( $_POST['delete_reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['delete_reason'] ) ) : '';

		$success = false;
		if ( $attachment && empty( $attachment['deleted_at'] ) && SC_EI_Storage::delete_file( (string) $attachment['relative_path'] ) ) {
			$success = SC_EI_Attachment_Repository::mark_deleted(
				$id,
				get_current_user_id(),
				$reason ?: 'Private attachment permanently deleted by an authorized administrator.',
				'deleted'
			);
		}

		self::redirect_to_inquiry(
			(int) ( $attachment['inquiry_id'] ?? 0 ),
			$success ? 'attachment_deleted' : 'attachment_error'
		);
	}

	private static function redirect_to_inquiry( int $inquiry_id, string $message ): void {
		$url = add_query_arg(
			array(
				'page'      => 'sc-engagement-intake',
				'action'    => 'view',
				'inquiry'   => $inquiry_id,
				'sc_ei_msg' => sanitize_key( $message ),
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	public static function handle_note(): void {
		if ( ! current_user_can( 'sc_intake_add_notes' ) ) {
			wp_die( esc_html__( 'You do not have permission to add private notes.', 'sustainable-catalyst-engagement-intake' ) );
		}

		check_admin_referer( 'sc_ei_add_note' );

		$id   = isset( $_POST['inquiry_id'] ) ? absint( $_POST['inquiry_id'] ) : 0;
		$note = isset( $_POST['internal_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['internal_note'] ) ) : '';

		$success = $id && SC_EI_Inquiry_Repository::add_internal_note( $id, $note );
		if ( $success && '' !== trim( $note ) ) {
			SC_EI_Communication_Repository::record_interaction(
				$id,
				array(
					'direction'          => 'internal',
					'channel'            => 'other',
					'communication_type' => 'internal_note',
					'subject'            => __( 'Internal inquiry note', 'sustainable-catalyst-engagement-intake' ),
					'body_text'          => $note,
					'party_name'         => wp_get_current_user()->display_name,
					'party_email'        => wp_get_current_user()->user_email,
					'occurred_at_local'  => current_time( 'Y-m-d\TH:i' ),
					'needs_response'     => 0,
					'privacy_classification' => 'private',
				),
				get_current_user_id()
			);
		}

		$url = add_query_arg(
			array(
				'page'      => 'sc-engagement-intake',
				'action'    => 'view',
				'inquiry'   => $id,
				'sc_ei_msg' => $success ? 'note_added' : 'error',
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}
}
