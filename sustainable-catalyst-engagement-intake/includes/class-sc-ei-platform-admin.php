<?php
/**
 * Unified platform administration and launch governance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Platform_Admin {

	public static function register(): void {
		add_action( 'admin_post_sc_ei_platform_snapshot', array( __CLASS__, 'handle_snapshot' ) );
		add_action( 'admin_post_sc_ei_platform_launch_state', array( __CLASS__, 'handle_launch_state' ) );
		add_action( 'admin_post_sc_ei_platform_save_settings', array( __CLASS__, 'handle_save_settings' ) );
		add_action( 'admin_post_sc_ei_platform_export', array( __CLASS__, 'handle_export' ) );
		add_action( 'admin_post_sc_ei_platform_verify_migration', array( __CLASS__, 'handle_verify_migration' ) );
		add_action( 'admin_post_sc_ei_platform_repair', array( __CLASS__, 'handle_repair' ) );
		add_action( 'admin_post_sc_ei_platform_live_validation', array( __CLASS__, 'handle_live_validation' ) );
		add_action( 'admin_post_sc_ei_platform_backup_attestation', array( __CLASS__, 'handle_backup_attestation' ) );
	}

	public static function page(): void {
		if ( isset( $_GET['action'] ) || isset( $_GET['inquiry'] ) || isset( $_GET['status'] ) || isset( $_GET['s'] ) ) {
			SC_EI_Admin::inquiries_page();
			return;
		}
		self::require_cap( 'sc_intake_view_platform' );
		$message = isset( $_GET['sc_ei_msg'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_msg'] ) ) : '';
		$summary = SC_EI_Platform_Repository::platform_summary();
		$readiness = $summary['readiness'];
		$settings = SC_EI_Platform_Repository::settings();
		$launch_record = get_option( 'sc_ei_platform_launch_record', array() );
		include SC_EI_DIR . 'admin/views/platform-overview.php';
	}

	public static function handle_snapshot(): void {
		self::require_cap( 'sc_intake_snapshot_platform' );
		check_admin_referer( 'sc_ei_platform_snapshot' );
		self::require_confirmation( 'SNAPSHOT PLATFORM', $_POST['platform_confirmation'] ?? '', 'platform_snapshot_confirmation_failed' );
		$result = SC_EI_Platform_Repository::create_snapshot( get_current_user_id(), 'manual' );
		self::redirect( is_wp_error( $result ) ? $result->get_error_code() : 'platform_snapshot_created' );
	}

	public static function handle_launch_state(): void {
		self::require_cap( 'sc_intake_launch_platform' );
		$state = SC_EI_Platform_Schema::sanitize_launch_state( wp_unslash( $_POST['platform_launch_state'] ?? '' ) );
		check_admin_referer( 'sc_ei_platform_launch_state_' . $state );
		self::require_confirmation( 'SET PLATFORM ' . strtoupper( $state ), $_POST['platform_confirmation'] ?? '', 'platform_launch_confirmation_failed' );
		$result = SC_EI_Platform_Repository::set_launch_state( $state, wp_unslash( $_POST['platform_launch_note'] ?? '' ), get_current_user_id() );
		self::redirect( is_wp_error( $result ) ? $result->get_error_code() : 'platform_launch_state_updated' );
	}

	public static function handle_save_settings(): void {
		self::require_cap( 'sc_intake_manage_platform' );
		check_admin_referer( 'sc_ei_platform_save_settings' );
		self::require_confirmation( 'SAVE PLATFORM SETTINGS', $_POST['platform_confirmation'] ?? '', 'platform_settings_confirmation_failed' );
		$current = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() );
		$raw = isset( $_POST['platform_settings'] ) ? (array) wp_unslash( $_POST['platform_settings'] ) : array();
		$updates = array(
			'platform_enabled'                  => empty( $raw['platform_enabled'] ) ? 0 : 1,
			'platform_display_name'             => sanitize_text_field( (string) ( $raw['platform_display_name'] ?? $current['platform_display_name'] ) ),
			'platform_support_email'            => sanitize_email( (string) ( $raw['platform_support_email'] ?? $current['platform_support_email'] ) ),
			'platform_contact_page_url'         => esc_url_raw( (string) ( $raw['platform_contact_page_url'] ?? '' ) ),
			'platform_engagement_page_url'      => esc_url_raw( (string) ( $raw['platform_engagement_page_url'] ?? '' ) ),
			'platform_portal_page_url'          => esc_url_raw( (string) ( $raw['platform_portal_page_url'] ?? '' ) ),
			'platform_privacy_page_url'         => esc_url_raw( (string) ( $raw['platform_privacy_page_url'] ?? '' ) ),
			'platform_readiness_snapshot_daily' => empty( $raw['platform_readiness_snapshot_daily'] ) ? 0 : 1,
			'platform_snapshot_retention_days'  => max( 30, min( 3650, absint( $raw['platform_snapshot_retention_days'] ?? 365 ) ) ),
			'platform_require_https'            => 1,
			'platform_require_protected_storage'=> 1,
			'platform_require_schema_integrity' => 1,
			'platform_require_public_entry'     => 1,
			'platform_require_portal_url'        => 1,
			'platform_require_typed_launch'      => 1,
			'platform_no_auto_launch'            => 1,
			'platform_no_auto_acceptance'        => 1,
			'platform_no_auto_fit_decision'      => 1,
			'platform_no_auto_proposal'          => 1,
			'platform_no_auto_contract'          => 1,
			'platform_no_auto_activation'        => 1,
			'platform_no_auto_project_provisioning'=> 1,
			'platform_no_auto_payment'           => 1,
			'platform_no_unverified_external_commands'=> 1,
		);
		update_option( 'sc_ei_settings', array_merge( $current, $updates ), false );
		SC_EI_Platform_Repository::unschedule();
		SC_EI_Platform_Repository::schedule();
		self::redirect( 'platform_settings_saved' );
	}

	public static function handle_export(): void {
		self::require_cap( 'sc_intake_export_platform' );
		check_admin_referer( 'sc_ei_platform_export' );
		$payload = SC_EI_Platform_Repository::export();
		SC_EI_Audit_Log::record(
			'platform_report_exported',
			'Authorized staff exported the unified platform readiness and operational report.',
			array( 'schema' => $payload['schema'] ?? '', 'personal_data' => false ),
			null,
			null,
			get_current_user_id()
		);
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="unified-contact-engagement-platform-' . gmdate( 'Y-m-d' ) . '.json"' );
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		exit;
	}

	public static function handle_verify_migration(): void {
		self::require_cap( 'sc_intake_manage_platform' );
		check_admin_referer( 'sc_ei_platform_verify_migration' );
		self::require_confirmation( 'VERIFY PLATFORM MIGRATION', $_POST['platform_confirmation'] ?? '', 'platform_migration_confirmation_failed' );
		$result = SC_EI_Platform_Repository::run_migrations( (string) get_option( 'sc_ei_version_previous', '0.12.0' ) );
		self::redirect( is_wp_error( $result ) ? $result->get_error_code() : 'platform_migration_verified' );
	}


	public static function handle_repair(): void {
		self::require_cap( 'sc_intake_manage_platform' );
		$repair_key = sanitize_key( wp_unslash( $_POST['platform_repair_key'] ?? '' ) );
		check_admin_referer( 'sc_ei_platform_repair_' . $repair_key );
		$result = SC_EI_Platform_Repository::repair( $repair_key, get_current_user_id() );
		self::redirect( is_wp_error( $result ) ? $result->get_error_code() : 'platform_repair_completed' );
	}

	public static function handle_live_validation(): void {
		self::require_cap( 'sc_intake_manage_platform' );
		check_admin_referer( 'sc_ei_platform_live_validation' );
		self::require_confirmation( 'RUN LIVE VALIDATION', $_POST['platform_confirmation'] ?? '', 'platform_live_validation_confirmation_failed' );
		$result = SC_EI_Platform_Validation::run(
			(string) wp_unslash( $_POST['platform_test_email'] ?? '' ),
			get_current_user_id()
		);
		self::redirect( ! empty( $result['passed'] ) ? 'platform_live_validation_passed' : 'platform_live_validation_failed' );
	}

	public static function handle_backup_attestation(): void {
		self::require_cap( 'sc_intake_manage_platform' );
		check_admin_referer( 'sc_ei_platform_backup_attestation' );
		self::require_confirmation( 'ATTEST PLATFORM BACKUPS', $_POST['platform_confirmation'] ?? '', 'platform_backup_confirmation_failed' );
		$result = SC_EI_Platform_Validation::attest_backups(
			(string) wp_unslash( $_POST['database_backup_reference'] ?? '' ),
			(string) wp_unslash( $_POST['storage_backup_reference'] ?? '' ),
			get_current_user_id()
		);
		self::redirect( is_wp_error( $result ) ? $result->get_error_code() : 'platform_backups_attested' );
	}

	private static function require_cap( string $capability ): void {
		if ( ! current_user_can( $capability ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this unified platform action.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}
	}

	private static function require_confirmation( string $expected, $provided, string $error ): void {
		$provided = strtoupper( trim( sanitize_text_field( wp_unslash( (string) $provided ) ) ) );
		if ( ! hash_equals( $expected, $provided ) ) {
			self::redirect( $error );
		}
	}

	private static function redirect( string $message ): void {
		wp_safe_redirect( add_query_arg( array( 'page' => 'sc-engagement-intake', 'sc_ei_msg' => sanitize_key( $message ) ), admin_url( 'admin.php' ) ), 303 );
		exit;
	}
}
