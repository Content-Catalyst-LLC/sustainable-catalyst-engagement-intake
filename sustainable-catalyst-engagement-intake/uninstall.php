<?php
/**
 * Uninstall cleanup.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-sc-ei-database.php';
require_once __DIR__ . '/includes/class-sc-ei-capabilities.php';
require_once __DIR__ . '/includes/class-sc-ei-storage.php';
require_once __DIR__ . '/includes/class-sc-ei-retention.php';
require_once __DIR__ . '/includes/class-sc-ei-notification-service.php';

$settings = get_option( 'sc_ei_settings', array() );
$delete   = ! empty( $settings['delete_data_on_uninstall'] );

SC_EI_Retention::unschedule();
SC_EI_Notification_Service::unschedule();
wp_clear_scheduled_hook( 'sc_ei_graph_process_queue' );
wp_clear_scheduled_hook( 'sc_ei_graph_catchup' );
wp_clear_scheduled_hook( 'sc_ei_hardening_watchdog' );
wp_clear_scheduled_hook( 'sc_ei_hardening_prune' );
wp_clear_scheduled_hook( 'sc_ei_analytics_daily_snapshot' );
wp_clear_scheduled_hook( 'sc_ei_workflow_core_sync' );
wp_clear_scheduled_hook( 'sc_ei_workflow_core_outbox' );
wp_clear_scheduled_hook( 'sc_ei_workflow_core_sync_inquiry' );
wp_clear_scheduled_hook( 'sc_ei_platform_readiness_snapshot' );
SC_EI_Capabilities::uninstall();

if ( $delete ) {
	SC_EI_Storage::delete_storage_tree();
	SC_EI_Database::drop_all();
	delete_option( 'sc_ei_settings' );
	delete_option( 'sc_ei_version' );
	delete_option( 'sc_ei_db_version' );
	delete_option( 'sc_ei_storage_base_dir' );
	delete_option( 'sc_ei_last_storage_probe' );
	delete_option( 'sc_ei_last_storage_reconciliation' );
	delete_option( 'sc_ei_last_retention_preview' );
	delete_option( 'sc_ei_last_retention_run' );
	delete_option( 'sc_ei_last_privacy_retention_preview' );
	delete_option( 'sc_ei_last_retention_queue_run' );
	delete_option( 'sc_ei_scanner_readiness' );
	delete_option( 'sc_ei_last_notification_reminder_run' );
	delete_option( 'sc_ei_notification_cron_lock' );
	delete_option( 'sc_ei_privacy_schema_version' );
	delete_option( 'sc_ei_fit_schema_version' );
	delete_option( 'sc_ei_portal_schema_version' );
	delete_option( 'sc_ei_workflow_schema_version' );
	delete_option( 'sc_ei_graph_schema_version' );
	delete_option( 'sc_ei_hardening_schema_version' );
	delete_option( 'sc_ei_workflow_core_schema_version' );
	delete_option( 'sc_ei_platform_schema_version' );
	delete_option( 'sc_ei_platform_launch_record' );
	delete_option( 'sc_ei_platform_live_validation' );
	delete_option( 'sc_ei_platform_backup_attestation' );
	delete_option( 'sc_ei_version_previous' );
	delete_option( 'sc_ei_workflow_core_last_sync' );
	delete_option( 'sc_ei_workflow_core_last_outbox' );
	delete_option( 'sc_ei_last_hardening_watchdog' );
	delete_option( 'sc_ei_analytics_schema_version' );
	delete_option( 'sc_ei_engagement_schema_version' );
	delete_option( 'sc_ei_graph_credentials' );
	delete_option( 'sc_ei_graph_circuit' );
	delete_option( 'sc_ei_graph_last_health' );
	delete_option( 'sc_ei_last_workflow_cleanup' );
	delete_option( 'sc_ei_last_portal_cleanup' );
	delete_transient( 'sc_ei_retention_cleanup_lock' );
	delete_transient( 'sc_ei_request_lock_cleanup_throttle' );

	global $wpdb;
	$lock_like    = $wpdb->esc_like( 'sc_ei_lock_' ) . '%';
	$success_like = $wpdb->esc_like( '_transient_sc_ei_success_' ) . '%';
	$timeout_like = $wpdb->esc_like( '_transient_timeout_sc_ei_success_' ) . '%';
	$bulk_like    = $wpdb->esc_like( '_transient_sc_ei_quarantine_bulk_result_' ) . '%';
	$bulk_timeout = $wpdb->esc_like( '_transient_timeout_sc_ei_quarantine_bulk_result_' ) . '%';
	$review_bulk_like    = $wpdb->esc_like( '_transient_sc_ei_bulk_review_result_' ) . '%';
	$review_bulk_timeout = $wpdb->esc_like( '_transient_timeout_sc_ei_bulk_review_result_' ) . '%';
	$mail_lock_like       = $wpdb->esc_like( 'sc_ei_mail_lock_' ) . '%';
	$hardening_lock_like = $wpdb->esc_like( 'sc_ei_hardening_lock_' ) . '%';
	$workflow_core_like = $wpdb->esc_like( 'sc_ei_workflow_core_' ) . '%';
	$graph_token_like     = $wpdb->esc_like( '_site_transient_sc_ei_graph_token_' ) . '%';
	$graph_token_timeout  = $wpdb->esc_like( '_site_transient_timeout_sc_ei_graph_token_' ) . '%';
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$lock_like,
			$success_like,
			$timeout_like,
			$bulk_like,
			$bulk_timeout,
			$review_bulk_like,
			$review_bulk_timeout,
			$mail_lock_like,
			$hardening_lock_like,
			$workflow_core_like,
			$graph_token_like,
			$graph_token_timeout
		)
	);
}
