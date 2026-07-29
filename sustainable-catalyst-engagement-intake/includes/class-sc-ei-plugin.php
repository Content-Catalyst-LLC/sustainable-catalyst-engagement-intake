<?php
/**
 * Main plugin coordinator.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Plugin {

	private static ?self $instance = null;
	private bool $booted = false;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public static function database_recovery_notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Sustainable Catalyst Contact and Engagement Platform paused its runtime because required database tables could not be created. Confirm database write access and reactivate the plugin to retry the protected recovery migration.', 'sustainable-catalyst-engagement-intake' ) . '</p></div>';
	}

	public function boot(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		load_plugin_textdomain(
			'sustainable-catalyst-engagement-intake',
			false,
			dirname( SC_EI_BASENAME ) . '/languages'
		);

		SC_EI_Database::maybe_upgrade();
		if ( in_array( false, SC_EI_Database::critical_tables_exist(), true ) ) {
			if ( is_admin() ) {
				add_action( 'admin_notices', array( __CLASS__, 'database_recovery_notice' ) );
			}
			return;
		}

		SC_EI_Lifecycle_Repository::maybe_upgrade();
		SC_EI_Support_Repository::maybe_upgrade();
		SC_EI_Calendar_Repository::maybe_upgrade();
		SC_EI_Proposal_Governance_Repository::maybe_upgrade();
		SC_EI_Workspace_Repository::maybe_upgrade();
		SC_EI_Service_Intelligence_Repository::maybe_upgrade();
		SC_EI_Billing_Repository::maybe_upgrade();
		SC_EI_Unified_Platform_Repository::maybe_upgrade();
		SC_EI_Platform_Repository::maybe_upgrade();
		SC_EI_Capabilities::install();
		SC_EI_Upload_Environment::register();
		SC_EI_Privacy::register();
		SC_EI_Privacy_Repository::register();
		SC_EI_REST::register();
		SC_EI_Form_Handler::register();
		SC_EI_Public::register();
		SC_EI_Portal_Public::register();
		SC_EI_Portal_Repository::register();
		SC_EI_Workflow_Repository::register();
		SC_EI_Proposal_Governance_Repository::register();
		SC_EI_Workspace_Repository::register();
		SC_EI_Calendar_Repository::register();
		SC_EI_Graph_Repository::register();
		SC_EI_Lifecycle_Repository::register();
		SC_EI_Support_Repository::register();
		SC_EI_Analytics_Repository::register();
		SC_EI_Service_Intelligence_Repository::register();
		SC_EI_Billing_Repository::register();
		SC_EI_Unified_Platform_Repository::register();
		SC_EI_Hardening_Repository::register();
		SC_EI_Workflow_Core_Repository::register();
		SC_EI_Workflow_Core_Service::register();
		SC_EI_Platform_Repository::register();
		SC_EI_Platform_Public::register();
		SC_EI_Retention::register();
		SC_EI_Mailer::register();
		SC_EI_Notification_Service::register();

		if ( is_admin() ) {
			SC_EI_Admin::register();
			SC_EI_Review_Admin::register();
			SC_EI_Communication_Admin::register();
			SC_EI_Privacy_Admin::register();
			SC_EI_Fit_Admin::register();
			SC_EI_Portal_Admin::register();
			SC_EI_Workflow_Admin::register();
			SC_EI_Proposal_Governance_Admin::register();
			SC_EI_Workspace_Admin::register();
			SC_EI_Calendar_Admin::register();
			SC_EI_Graph_Admin::register();
			SC_EI_Engagement_Admin::register();
			SC_EI_Lifecycle_Admin::register();
			SC_EI_Support_Admin::register();
			SC_EI_Analytics_Admin::register();
			SC_EI_Service_Intelligence_Admin::register();
			SC_EI_Billing_Admin::register();
			SC_EI_Command_Center_Admin::register();
			SC_EI_Hardening_Admin::register();
			SC_EI_Workflow_Core_Admin::register();
			SC_EI_Platform_Admin::register();
		}

		do_action( 'sc_ei_loaded', $this );
	}
}
