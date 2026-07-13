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

		if ( false === get_option( 'sc_ei_settings', false ) ) {
			add_option( 'sc_ei_settings', SC_EI_Admin::default_settings(), '', false );
		}

		update_option( 'sc_ei_version', SC_EI_VERSION, false );

		SC_EI_Audit_Log::record(
			'plugin_activated',
			'Engagement Intake v0.3.2 activated with cross-inquiry quarantine operations, scanner readiness testing and retry, guarded bulk file actions, access audit reporting, storage utilization, isolation guidance, and v0.3.1 reliability controls.',
			array( 'version' => SC_EI_VERSION )
		);
	}

	public static function deactivate(): void {
		SC_EI_Retention::unschedule();
	}
}
