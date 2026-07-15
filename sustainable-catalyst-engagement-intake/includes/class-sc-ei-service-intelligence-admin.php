<?php
/**
 * Engagement analytics and service-intelligence administration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Service_Intelligence_Admin {

	public static function register(): void {
		add_action( 'admin_post_sc_ei_service_intelligence_snapshot', array( __CLASS__, 'snapshot' ) );
		add_action( 'admin_post_sc_ei_service_intelligence_create_finding', array( __CLASS__, 'create_finding' ) );
		add_action( 'admin_post_sc_ei_service_intelligence_transition', array( __CLASS__, 'transition' ) );
		add_action( 'admin_post_sc_ei_service_intelligence_export', array( __CLASS__, 'export' ) );
	}

	public static function page(): void {
		if ( ! current_user_can( 'sc_intake_view_analytics' ) ) {
			wp_die( esc_html__( 'Forbidden', 'sustainable-catalyst-engagement-intake' ) );
		}
		$days = SC_EI_Analytics_Schema::sanitize_days( $_GET['days'] ?? SC_EI_Analytics_Repository::settings()['analytics_default_days'] );
		$analytics = SC_EI_Service_Intelligence_Repository::dashboard( $days );
		$findings = SC_EI_Service_Intelligence_Repository::findings( 100, sanitize_key( $_GET['finding_status'] ?? '' ) );
		$metrics = SC_EI_Service_Intelligence_Repository::metrics();
		$snapshot_evidence = SC_EI_Service_Intelligence_Repository::latest_snapshot_evidence();
		include SC_EI_DIR . 'admin/views/service-intelligence.php';
	}

	public static function snapshot(): void {
		if ( ! current_user_can( 'sc_intake_manage_analytics' ) ) wp_die( esc_html__( 'Forbidden', 'sustainable-catalyst-engagement-intake' ) );
		check_admin_referer( 'sc_ei_service_intelligence_snapshot' );
		$days = SC_EI_Analytics_Schema::sanitize_days( $_POST['days'] ?? 90 );
		$confirmation = strtoupper( trim( sanitize_text_field( wp_unslash( $_POST['confirmation'] ?? '' ) ) ) );
		if ( ! hash_equals( 'SNAPSHOT SERVICE INTELLIGENCE', $confirmation ) ) self::go( $days, 'confirmation_failed' );
		$result = SC_EI_Service_Intelligence_Repository::create_snapshot( $days, get_current_user_id() );
		self::go( $days, is_wp_error( $result ) ? $result->get_error_code() : 'service_intelligence_snapshot_created' );
	}

	public static function create_finding(): void {
		if ( ! current_user_can( 'sc_intake_manage_analytics' ) ) wp_die( esc_html__( 'Forbidden', 'sustainable-catalyst-engagement-intake' ) );
		check_admin_referer( 'sc_ei_service_intelligence_create_finding' );
		$days = SC_EI_Analytics_Schema::sanitize_days( $_POST['days'] ?? 90 );
		$confirmation = strtoupper( trim( sanitize_text_field( wp_unslash( $_POST['confirmation'] ?? '' ) ) ) );
		if ( ! hash_equals( 'CREATE AGGREGATE FINDING', $confirmation ) ) self::go( $days, 'confirmation_failed' );
		$evidence = array(
			'metric' => sanitize_key( wp_unslash( $_POST['metric_key'] ?? 'manual_observation' ) ),
			'note'   => sanitize_textarea_field( wp_unslash( $_POST['aggregate_evidence'] ?? '' ) ),
			'range_days' => $days,
		);
		$result = SC_EI_Service_Intelligence_Repository::create_finding(
			array(
				'finding_type' => wp_unslash( $_POST['finding_type'] ?? 'service_demand' ),
				'severity'     => wp_unslash( $_POST['severity'] ?? 'watch' ),
				'title'        => wp_unslash( $_POST['title'] ?? '' ),
				'service_key'  => wp_unslash( $_POST['service_key'] ?? '' ),
				'product_key'  => wp_unslash( $_POST['product_key'] ?? '' ),
				'component_key'=> wp_unslash( $_POST['component_key'] ?? '' ),
				'cohort_count' => absint( $_POST['cohort_count'] ?? 0 ),
				'metric_value' => is_numeric( $_POST['metric_value'] ?? null ) ? (float) $_POST['metric_value'] : null,
				'metric_unit'  => wp_unslash( $_POST['metric_unit'] ?? 'count' ),
				'period_start' => gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS ),
				'period_end'   => current_time( 'mysql', true ),
				'evidence'     => $evidence,
				'owner_user_id'=> get_current_user_id(),
			),
			get_current_user_id()
		);
		self::go( $days, is_wp_error( $result ) ? $result->get_error_code() : 'aggregate_finding_created' );
	}

	public static function transition(): void {
		if ( ! current_user_can( 'sc_intake_manage_analytics' ) ) wp_die( esc_html__( 'Forbidden', 'sustainable-catalyst-engagement-intake' ) );
		$finding_id = absint( $_POST['finding_id'] ?? 0 );
		check_admin_referer( 'sc_ei_service_intelligence_transition_' . $finding_id );
		$days = SC_EI_Analytics_Schema::sanitize_days( $_POST['days'] ?? 90 );
		$result = SC_EI_Service_Intelligence_Repository::transition_finding(
			$finding_id,
			wp_unslash( $_POST['status'] ?? '' ),
			wp_unslash( $_POST['confirmation'] ?? '' ),
			wp_unslash( $_POST['decision_note'] ?? '' ),
			wp_unslash( $_POST['action_summary'] ?? '' ),
			get_current_user_id()
		);
		self::go( $days, is_wp_error( $result ) ? $result->get_error_code() : 'finding_transitioned' );
	}

	public static function export(): void {
		if ( ! current_user_can( 'sc_intake_export_analytics' ) ) wp_die( esc_html__( 'Forbidden', 'sustainable-catalyst-engagement-intake' ) );
		$days = SC_EI_Analytics_Schema::sanitize_days( $_GET['days'] ?? 90 );
		check_admin_referer( 'sc_ei_service_intelligence_export_' . $days );
		$payload = SC_EI_Service_Intelligence_Repository::dashboard( $days );
		SC_EI_Audit_Log::record( 'service_intelligence_exported', 'Authorized user exported aggregate engagement analytics and service intelligence.', array( 'range_days' => $days, 'personal_data' => false, 'schema' => SC_EI_Service_Intelligence_Schema::SNAPSHOT_SCHEMA ), null, null, get_current_user_id() );
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="engagement-service-intelligence-' . $days . 'd-' . gmdate( 'Ymd-His' ) . '.json"' );
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		exit;
	}

	private static function go( int $days, string $message ): void {
		wp_safe_redirect( add_query_arg( array( 'page' => 'sc-engagement-intake-analytics', 'days' => $days, 'sc_ei_msg' => sanitize_key( $message ) ), admin_url( 'admin.php' ) ), 303 );
		exit;
	}
}
