<?php
/** Integrated Engagement Command Center administration. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class SC_EI_Command_Center_Admin {
	public static function register(): void {
		add_action( 'admin_post_sc_ei_unified_refresh_dossier', array( __CLASS__, 'refresh_dossier' ) );
		add_action( 'admin_post_sc_ei_unified_backfill', array( __CLASS__, 'backfill' ) );
	}
	public static function submenu(): void {
		add_submenu_page( 'sc-engagement-intake', __( 'Integrated Engagement Command Center', 'sustainable-catalyst-engagement-intake' ), __( 'Command Center', 'sustainable-catalyst-engagement-intake' ), 'sc_intake_view_platform', 'sc-engagement-intake-command-center', array( __CLASS__, 'page' ) );
	}
	public static function page(): void {
		if ( ! current_user_can( 'sc_intake_view_platform' ) ) wp_die( esc_html__( 'Forbidden', 'sustainable-catalyst-engagement-intake' ) );
		$dossier_id = absint( $_GET['dossier_id'] ?? 0 );
		$dashboard = SC_EI_Unified_Platform_Repository::dashboard();
		$dossiers = SC_EI_Unified_Platform_Repository::dossiers( array( 'route_group' => sanitize_key( $_GET['route_group'] ?? '' ), 'phase' => sanitize_key( $_GET['phase'] ?? '' ), 'health_status' => sanitize_key( $_GET['health_status'] ?? '' ), 'search' => sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) ), 'limit' => 250 ) );
		$dossier = $dossier_id ? SC_EI_Unified_Platform_Repository::dossier_export( $dossier_id ) : null;
		include SC_EI_DIR . 'admin/views/command-center.php';
	}
	public static function refresh_dossier(): void {
		if ( ! current_user_can( 'sc_intake_manage_platform' ) ) wp_die( esc_html__( 'Forbidden', 'sustainable-catalyst-engagement-intake' ) );
		$id = absint( $_POST['dossier_id'] ?? 0 );
		check_admin_referer( 'sc_ei_unified_refresh_dossier_' . $id );
		$dossier = SC_EI_Unified_Platform_Repository::find( $id );
		$result = $dossier ? SC_EI_Unified_Platform_Repository::refresh_dossier( absint( $dossier['inquiry_id'] ), get_current_user_id(), 'admin_refresh' ) : new WP_Error( 'dossier_missing', 'Dossier missing.' );
		wp_safe_redirect( add_query_arg( array( 'page' => 'sc-engagement-intake-command-center', 'dossier_id' => $id, 'sc_ei_msg' => is_wp_error( $result ) ? $result->get_error_code() : 'dossier_refreshed' ), admin_url( 'admin.php' ) ), 303 ); exit;
	}
	public static function backfill(): void {
		if ( ! current_user_can( 'sc_intake_manage_platform' ) ) wp_die( esc_html__( 'Forbidden', 'sustainable-catalyst-engagement-intake' ) );
		check_admin_referer( 'sc_ei_unified_backfill' );
		$result = SC_EI_Unified_Platform_Repository::backfill( 5000, 0 );
		wp_safe_redirect( add_query_arg( array( 'page' => 'sc-engagement-intake-command-center', 'sc_ei_msg' => empty( $result['failed'] ) ? 'dossiers_rebuilt' : 'dossier_rebuild_attention' ), admin_url( 'admin.php' ) ), 303 ); exit;
	}
}
