<?php
/**
 * Activation.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Activator {

	public static function activate(): void {
		if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
			deactivate_plugins( SC_EI_BASENAME );
			wp_die( esc_html__( 'Sustainable Catalyst Contact and Engagement Platform requires PHP 8.1 or newer.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$previous_version = (string) get_option( 'sc_ei_version', '' );
		$previous_lifecycle_schema = (string) get_option( 'sc_ei_lifecycle_schema_version', '' );
		$previous_calendar_schema = (string) get_option( 'sc_ei_calendar_schema_version', '' );
		$previous_proposal_schema = (string) get_option( 'sc_ei_proposal_governance_schema_version', '' );
		$previous_workspace_schema = (string) get_option( 'sc_ei_workspace_schema_version', '' );
		$previous_service_intelligence_schema = (string) get_option( 'sc_ei_service_intelligence_schema_version', '' );
		$previous_billing_schema = (string) get_option( 'sc_ei_billing_schema_version', '' );
		SC_EI_Database::install();
		SC_EI_Capabilities::install();
		SC_EI_Storage::ensure();
		SC_EI_Retention::schedule();
		SC_EI_Notification_Service::schedule();
		SC_EI_Portal_Repository::schedule();
		SC_EI_Workflow_Repository::schedule();
		SC_EI_Calendar_Repository::schedule();
		SC_EI_Graph_Repository::schedule();
		SC_EI_Lifecycle_Repository::schedule();
		SC_EI_Support_Repository::schedule();
		SC_EI_Template_Repository::seed_defaults();
		SC_EI_Retention_Policy_Repository::seed_defaults();

		if ( false === get_option( 'sc_ei_settings', false ) ) {
			add_option( 'sc_ei_settings', SC_EI_Admin::default_settings(), '', false );
		}

		update_option( 'sc_ei_version', SC_EI_VERSION, false );
		update_option( 'sc_ei_privacy_schema_version', SC_EI_PRIVACY_SCHEMA_VERSION, false );
		update_option( 'sc_ei_fit_schema_version', SC_EI_FIT_SCHEMA_VERSION, false );
		update_option( 'sc_ei_portal_schema_version', SC_EI_PORTAL_SCHEMA_VERSION, false );
		update_option( 'sc_ei_workflow_schema_version', SC_EI_WORKFLOW_SCHEMA_VERSION, false );
		update_option( 'sc_ei_calendar_schema_version_previous', $previous_calendar_schema, false );
		update_option( 'sc_ei_calendar_schema_version', SC_EI_CALENDAR_SCHEMA_VERSION, false );
		update_option( 'sc_ei_graph_schema_version', SC_EI_GRAPH_SCHEMA_VERSION, false );
		update_option( 'sc_ei_engagement_schema_version', SC_EI_ENGAGEMENT_SCHEMA_VERSION, false );
		update_option( 'sc_ei_lifecycle_schema_version_previous', $previous_lifecycle_schema, false );
		update_option( 'sc_ei_lifecycle_schema_version', SC_EI_LIFECYCLE_SCHEMA_VERSION, false );
		update_option( 'sc_ei_support_schema_version', SC_EI_SUPPORT_SCHEMA_VERSION, false );
		update_option( 'sc_ei_analytics_schema_version', SC_EI_ANALYTICS_SCHEMA_VERSION, false );
		update_option( 'sc_ei_hardening_schema_version', SC_EI_HARDENING_SCHEMA_VERSION, false );
		update_option( 'sc_ei_workflow_core_schema_version', SC_EI_WORKFLOW_CORE_SCHEMA_VERSION, false );
		update_option( 'sc_ei_platform_schema_version', SC_EI_PLATFORM_SCHEMA_VERSION, false );
		update_option( 'sc_ei_proposal_governance_schema_version_previous', $previous_proposal_schema, false );
		update_option( 'sc_ei_proposal_governance_schema_version', SC_EI_PROPOSAL_SCHEMA_VERSION, false );
		update_option( 'sc_ei_workspace_schema_version_previous', $previous_workspace_schema, false );
		update_option( 'sc_ei_workspace_schema_version', SC_EI_WORKSPACE_SCHEMA_VERSION, false );
		update_option( 'sc_ei_service_intelligence_schema_version_previous', $previous_service_intelligence_schema, false );
		update_option( 'sc_ei_service_intelligence_schema_version', SC_EI_SERVICE_INTELLIGENCE_SCHEMA_VERSION, false );
		update_option( 'sc_ei_billing_schema_version_previous', $previous_billing_schema, false );
		update_option( 'sc_ei_billing_schema_version', SC_EI_BILLING_SCHEMA_VERSION, false );
		update_option( 'sc_ei_version_previous', $previous_version, false );
		SC_EI_Platform_Repository::run_migrations( $previous_version );
		SC_EI_Analytics_Repository::schedule();
		SC_EI_Hardening_Repository::schedule();
		SC_EI_Workflow_Core_Repository::schedule();
		SC_EI_Platform_Repository::schedule();
		SC_EI_Lifecycle_Repository::backfill_defaults();
		SC_EI_Lifecycle_Repository::record_migration( $previous_lifecycle_schema );
		SC_EI_Support_Repository::record_migration( $previous_version );
		SC_EI_Support_Repository::record_patch_migration( $previous_version );
		SC_EI_Calendar_Repository::record_migration( $previous_calendar_schema );
		SC_EI_Calendar_Repository::record_patch_migration( $previous_calendar_schema );
		SC_EI_Proposal_Governance_Repository::record_migration( $previous_proposal_schema );
		SC_EI_Proposal_Governance_Repository::record_patch_migration( $previous_proposal_schema );
		SC_EI_Workspace_Repository::record_migration( $previous_workspace_schema );
		SC_EI_Service_Intelligence_Repository::record_migration( $previous_service_intelligence_schema );
		SC_EI_Billing_Repository::record_migration( $previous_billing_schema );

		SC_EI_Audit_Log::record(
			'plugin_activated',
			'Sustainable Catalyst Contact and Engagement Platform v1.7.0 activated with governed billing profiles, immutable invoice versions, external payment handoffs, Sender Portal billing projections, and the established analytics, workspace, proposal, calendar, support, advisory, privacy, and production gates.',
			array( 'version' => SC_EI_VERSION )
		);
	}

	public static function deactivate(): void {
		SC_EI_Retention::unschedule();
		SC_EI_Notification_Service::unschedule();
		SC_EI_Portal_Repository::unschedule();
		SC_EI_Workflow_Repository::unschedule();
		SC_EI_Calendar_Repository::unschedule();
		SC_EI_Graph_Repository::unschedule();
		SC_EI_Lifecycle_Repository::unschedule();
		SC_EI_Support_Repository::unschedule();
		SC_EI_Analytics_Repository::unschedule();
		SC_EI_Hardening_Repository::unschedule();
		SC_EI_Workflow_Core_Repository::unschedule();
		SC_EI_Platform_Repository::unschedule();
	}
}
