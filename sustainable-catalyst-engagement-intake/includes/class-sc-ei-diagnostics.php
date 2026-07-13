<?php
/**
 * Diagnostics.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Diagnostics {

	public static function run(): array {
		$tables = SC_EI_Database::tables_exist();
		$admin  = get_role( 'administrator' );

		$capabilities = array();
		foreach ( SC_EI_Capabilities::ALL as $cap ) {
			$capabilities[ $cap ] = $admin ? $admin->has_cap( $cap ) : false;
		}

		return array(
			'plugin_version'     => SC_EI_VERSION,
			'database_version'   => (string) get_option( 'sc_ei_db_version', '' ),
			'tables'             => $tables,
			'capabilities'       => $capabilities,
			'privacy_exporter'   => true,
			'privacy_eraser'     => true,
			'public_forms'       => false,
			'secure_uploads'     => false,
			'upload_note'        => __( 'Secure physical upload handling is scheduled for v0.3.0. The attachment metadata table is installed in v0.1.0.', 'sustainable-catalyst-engagement-intake' ),
			'wordpress_version'  => get_bloginfo( 'version' ),
			'php_version'        => PHP_VERSION,
			'multisite'          => is_multisite(),
			'site_url'           => site_url(),
		);
	}

	public static function overall_status( array $results ): string {
		$tables_ok = ! in_array( false, $results['tables'], true );
		$caps_ok   = ! in_array( false, $results['capabilities'], true );

		return ( $tables_ok && $caps_ok ) ? 'healthy' : 'attention';
	}
}
