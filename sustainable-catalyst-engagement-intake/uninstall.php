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
	delete_option( 'sc_ei_scanner_readiness' );
	delete_option( 'sc_ei_last_notification_reminder_run' );
	delete_option( 'sc_ei_notification_cron_lock' );
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
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$lock_like,
			$success_like,
			$timeout_like,
			$bulk_like,
			$bulk_timeout,
			$review_bulk_like,
			$review_bulk_timeout,
			$mail_lock_like
		)
	);
}
