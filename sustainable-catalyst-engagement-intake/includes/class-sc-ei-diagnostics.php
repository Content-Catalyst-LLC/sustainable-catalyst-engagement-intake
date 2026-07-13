<?php
/**
 * Diagnostics.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Diagnostics {

	public static function run(): array {
		$tables   = SC_EI_Database::tables_exist();
		$columns  = SC_EI_Database::inquiry_columns_exist();
		$admin    = get_role( 'administrator' );
		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() );

		$capabilities = array();
		foreach ( SC_EI_Capabilities::ALL as $cap ) {
			$capabilities[ $cap ] = $admin ? $admin->has_cap( $cap ) : false;
		}

		return array(
			'plugin_version'     => SC_EI_VERSION,
			'database_version'   => (string) get_option( 'sc_ei_db_version', '' ),
			'tables'             => $tables,
			'inquiry_columns'    => $columns,
			'capabilities'       => $capabilities,
			'privacy_exporter'   => true,
			'privacy_eraser'     => true,
			'public_forms'       => true,
			'public_shortcodes'  => array(
				'[sc_engagement_inquiry mode="compact" source="consulting-page"]',
				'[sc_contact_hub mode="advanced" source="contact-page"]',
				'[sc_contact_form mode="general"]',
				'[sc_engagement_inquiry mode="consulting"]',
			),
			'dual_intake'        => array(
				'compact_consulting'   => true,
				'advanced_contact_hub' => true,
				'source_attribution'   => true,
				'entry_cta_attribution'=> true,
				'conversion_routes'    => true,
				'guidance_flags'       => true,
				'admin_filters'        => true,
				'php_event_hooks'      => true,
				'browser_event_hooks'  => true,
			),
			'public_submit_rest' => rest_url( 'sc-engagement-intake/v1/submit' ),
			'secure_uploads'     => false,
			'upload_note'        => __( 'Secure physical upload handling is scheduled for v0.3.0. v0.2.2 collects descriptions and non-confidential public links only.', 'sustainable-catalyst-engagement-intake' ),
			'teams'              => array(
				'provider'              => 'Microsoft Teams',
				'preference_fields'     => true,
				'timezone_detection'    => true,
				'availability_fields'   => true,
				'admin_status_workflow' => true,
				'meeting_url_storage'   => true,
				'utc_time_storage'      => true,
				'calendar_consent'      => true,
				'graph_api_connected'   => false,
				'organizer_configured'  => ! empty( $settings['teams_organizer_email'] ),
			),
			'spam_controls'      => array(
				'nonce'      => true,
				'honeypot'   => true,
				'timing'     => true,
				'rate_limit' => true,
				'duplicates' => true,
			),
			'wordpress_version'  => get_bloginfo( 'version' ),
			'php_version'        => PHP_VERSION,
			'multisite'          => is_multisite(),
			'site_url'           => site_url(),
		);
	}

	public static function overall_status( array $results ): string {
		$tables_ok  = ! in_array( false, $results['tables'], true );
		$columns_ok = ! in_array( false, $results['inquiry_columns'], true );
		$caps_ok    = ! in_array( false, $results['capabilities'], true );
		$dual_ok    = ! in_array( false, $results['dual_intake'], true );

		return ( $tables_ok && $columns_ok && $caps_ok && $dual_ok && $results['public_forms'] ) ? 'healthy' : 'attention';
	}
}
