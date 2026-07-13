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
			wp_die( esc_html__( 'Sustainable Catalyst Engagement Intake requires PHP 8.1 or newer.', 'sustainable-catalyst-engagement-intake' ) );
		}

		SC_EI_Database::install();
		SC_EI_Capabilities::install();
		SC_EI_Storage::ensure();
		SC_EI_Retention::schedule();
		SC_EI_Notification_Service::schedule();
		SC_EI_Portal_Repository::schedule();
		SC_EI_Workflow_Repository::schedule();
		SC_EI_Graph_Repository::schedule();
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
		update_option( 'sc_ei_graph_schema_version', SC_EI_GRAPH_SCHEMA_VERSION, false );

		SC_EI_Audit_Log::record(
			'plugin_activated',
			'Engagement Intake v0.9.1 activated with an optional encrypted Microsoft Graph connector, human-triggered idempotent calendar-backed Teams creation, durable retries and reconciliation, circuit breaking, manual fallback, and all v0.9.0 scheduling, proposal, portal, privacy, review, quarantine, and storage controls.',
			array( 'version' => SC_EI_VERSION )
		);
	}

	public static function deactivate(): void {
		SC_EI_Retention::unschedule();
		SC_EI_Notification_Service::unschedule();
		SC_EI_Portal_Repository::unschedule();
		SC_EI_Workflow_Repository::unschedule();
		SC_EI_Graph_Repository::unschedule();
	}
}
