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
		update_option( 'sc_ei_engagement_schema_version', SC_EI_ENGAGEMENT_SCHEMA_VERSION, false );
		update_option( 'sc_ei_analytics_schema_version', SC_EI_ANALYTICS_SCHEMA_VERSION, false );
		update_option( 'sc_ei_hardening_schema_version', SC_EI_HARDENING_SCHEMA_VERSION, false );
		update_option( 'sc_ei_workflow_core_schema_version', SC_EI_WORKFLOW_CORE_SCHEMA_VERSION, false );
		SC_EI_Analytics_Repository::schedule();
		SC_EI_Hardening_Repository::schedule();
		SC_EI_Workflow_Core_Repository::schedule();

		SC_EI_Audit_Log::record(
			'plugin_activated',
			'Engagement Intake v0.12.0 activated with a canonical Workflow Core, audit-driven case projections, idempotent human commands, signed versioned cross-plugin handoffs, durable internal-adapter outbox delivery, and all prior reliability, analytics, engagement, Graph, portal, privacy, review, quarantine, and storage controls.',
			array( 'version' => SC_EI_VERSION )
		);
	}

	public static function deactivate(): void {
		SC_EI_Retention::unschedule();
		SC_EI_Notification_Service::unschedule();
		SC_EI_Portal_Repository::unschedule();
		SC_EI_Workflow_Repository::unschedule();
		SC_EI_Graph_Repository::unschedule();
		SC_EI_Analytics_Repository::unschedule();
		SC_EI_Hardening_Repository::unschedule();
		SC_EI_Workflow_Core_Repository::unschedule();
	}
}
