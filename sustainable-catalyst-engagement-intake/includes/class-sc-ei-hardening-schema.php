<?php
/**
 * Reliability, accessibility, and security hardening policy.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SC_EI_Hardening_Schema {
	public static function default_settings(): array {
		return array(
			'hardening_enabled'                    => 1,
			'hardening_public_writes_paused'       => 0,
			'hardening_watchdog_enabled'           => 1,
			'hardening_watchdog_interval_minutes'  => 60,
			'hardening_event_retention_days'       => 90,
			'hardening_resolved_retention_days'    => 30,
			'hardening_rate_limit_retention_days'  => 7,
			'hardening_intake_ip_limit_hour'       => 15,
			'hardening_intake_identity_limit_hour' => 5,
			'hardening_portal_edge_limit_15m'      => 60,
			'hardening_recovery_edge_limit_hour'   => 10,
			'hardening_fatal_capture_enabled'      => 1,
			'hardening_security_headers_enabled'   => 1,
			'hardening_csp_report_only_enabled'    => 0,
			'hardening_accessibility_helpers'      => 1,
			'hardening_export_request_id'          => 1,
			'hardening_no_secret_context'          => 1,
			'hardening_no_automatic_decisions'     => 1,
			'hardening_no_automatic_deletion'      => 1,
		);
	}

	public static function severities(): array {
		return array(
			'info'     => __( 'Information', 'sustainable-catalyst-engagement-intake' ),
			'warning'  => __( 'Warning', 'sustainable-catalyst-engagement-intake' ),
			'critical' => __( 'Critical', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function components(): array {
		return array(
			'database'      => __( 'Database', 'sustainable-catalyst-engagement-intake' ),
			'storage'       => __( 'Private Storage', 'sustainable-catalyst-engagement-intake' ),
			'cron'          => __( 'Scheduled Work', 'sustainable-catalyst-engagement-intake' ),
			'portal'        => __( 'Sender Portal', 'sustainable-catalyst-engagement-intake' ),
			'public_intake' => __( 'Public Intake', 'sustainable-catalyst-engagement-intake' ),
			'graph'         => __( 'Microsoft Graph', 'sustainable-catalyst-engagement-intake' ),
			'privacy'       => __( 'Privacy and Retention', 'sustainable-catalyst-engagement-intake' ),
			'security'      => __( 'Security', 'sustainable-catalyst-engagement-intake' ),
			'accessibility' => __( 'Accessibility', 'sustainable-catalyst-engagement-intake' ),
			'php'           => __( 'PHP Runtime', 'sustainable-catalyst-engagement-intake' ),
			'plugin'        => __( 'Plugin Runtime', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function sanitize_severity( string $value ): string {
		$value = sanitize_key( $value );
		return isset( self::severities()[ $value ] ) ? $value : 'warning';
	}

	public static function sanitize_component( string $value ): string {
		$value = sanitize_key( $value );
		return isset( self::components()[ $value ] ) ? $value : 'plugin';
	}
}
