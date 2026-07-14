<?php
/**
 * Unified platform lifecycle, readiness, and fixed release boundaries.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Platform_Schema {

	public static function launch_states(): array {
		return array(
			'setup'       => __( 'Setup', 'sustainable-catalyst-engagement-intake' ),
			'pilot'       => __( 'Pilot', 'sustainable-catalyst-engagement-intake' ),
			'production'  => __( 'Production', 'sustainable-catalyst-engagement-intake' ),
			'maintenance' => __( 'Maintenance', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function check_states(): array {
		return array(
			'pass' => __( 'Pass', 'sustainable-catalyst-engagement-intake' ),
			'warn' => __( 'Warning', 'sustainable-catalyst-engagement-intake' ),
			'fail' => __( 'Fail', 'sustainable-catalyst-engagement-intake' ),
			'info' => __( 'Information', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function check_groups(): array {
		return array(
			'platform'       => __( 'Platform', 'sustainable-catalyst-engagement-intake' ),
			'data'           => __( 'Data and Migration', 'sustainable-catalyst-engagement-intake' ),
			'public_entry'   => __( 'Public Entry', 'sustainable-catalyst-engagement-intake' ),
			'portal'         => __( 'Secure Sender Portal', 'sustainable-catalyst-engagement-intake' ),
			'operations'     => __( 'Operations', 'sustainable-catalyst-engagement-intake' ),
			'security'       => __( 'Security and Privacy', 'sustainable-catalyst-engagement-intake' ),
			'integrations'   => __( 'Integrations', 'sustainable-catalyst-engagement-intake' ),
			'accessibility'  => __( 'Accessibility', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function default_settings(): array {
		return array(
			'platform_enabled'                         => 1,
			'platform_launch_state'                    => 'setup',
			'platform_display_name'                    => 'Sustainable Catalyst Contact and Engagement Platform',
			'platform_support_email'                   => sanitize_email( get_option( 'admin_email' ) ),
			'platform_contact_page_url'                => '',
			'platform_engagement_page_url'             => '',
			'platform_portal_page_url'                 => '',
			'platform_privacy_page_url'                => '',
			'platform_readiness_snapshot_daily'        => 0,
			'platform_snapshot_retention_days'         => 365,
			'platform_require_https'                   => 1,
			'platform_require_protected_storage'       => 1,
			'platform_require_schema_integrity'        => 1,
			'platform_require_public_entry'            => 1,
			'platform_require_portal_url'               => 1,
			'platform_require_typed_launch'             => 1,
			'platform_no_auto_launch'                   => 1,
			'platform_no_auto_acceptance'               => 1,
			'platform_no_auto_fit_decision'             => 1,
			'platform_no_auto_proposal'                 => 1,
			'platform_no_auto_contract'                 => 1,
			'platform_no_auto_activation'               => 1,
			'platform_no_auto_project_provisioning'     => 1,
			'platform_no_auto_payment'                  => 1,
			'platform_no_unverified_external_commands' => 1,
		);
	}

	public static function sanitize_launch_state( string $value ): string {
		$value = sanitize_key( $value );
		return isset( self::launch_states()[ $value ] ) ? $value : 'setup';
	}

	public static function label( array $choices, string $value ): string {
		return $choices[ $value ] ?? ucwords( str_replace( '_', ' ', $value ) );
	}
}
