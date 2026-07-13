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
			'Engagement Intake v0.4.0 activated with a human-controlled Administrative Review Workspace, assignments, due-date visibility, manual fit and risk judgments, review checklists, escalation, immutable snapshots, private review packet export, quarantine operations, and reliable protected storage.',
			array( 'version' => SC_EI_VERSION )
		);
	}

	public static function deactivate(): void {
		SC_EI_Retention::unschedule();
	}
}
