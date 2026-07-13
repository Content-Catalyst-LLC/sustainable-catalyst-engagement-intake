<?php
/**
 * Uninstall cleanup.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-sc-ei-database.php';
require_once __DIR__ . '/includes/class-sc-ei-capabilities.php';

$settings = get_option( 'sc_ei_settings', array() );
$delete   = ! empty( $settings['delete_data_on_uninstall'] );

SC_EI_Capabilities::uninstall();

if ( $delete ) {
	SC_EI_Database::drop_all();
	delete_option( 'sc_ei_settings' );
	delete_option( 'sc_ei_version' );
	delete_option( 'sc_ei_db_version' );
}
