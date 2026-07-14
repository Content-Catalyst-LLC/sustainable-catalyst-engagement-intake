<?php
/** Product support case administration. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SC_EI_Support_Admin {
	public static function register(): void {
		add_action( 'admin_post_sc_ei_support_update', array( __CLASS__, 'handle_update' ) );
		add_action( 'admin_post_sc_ei_support_transition', array( __CLASS__, 'handle_transition' ) );
		add_action( 'admin_post_sc_ei_support_add_link', array( __CLASS__, 'handle_add_link' ) );
		add_action( 'admin_post_sc_ei_support_add_signal', array( __CLASS__, 'handle_add_signal' ) );
	}

	public static function submenu(): void {
		add_submenu_page(
			'sc-engagement-intake',
			__( 'Support Operations and Product Intelligence', 'sustainable-catalyst-engagement-intake' ),
			__( 'Support Cases', 'sustainable-catalyst-engagement-intake' ),
			'sc_intake_view_support',
			'sc-engagement-intake-support',
			array( __CLASS__, 'page' )
		);
	}

	public static function page(): void {
		self::require_cap( 'sc_intake_view_support' );
		$stage_filter = isset( $_GET['support_stage'] ) ? sanitize_key( wp_unslash( $_GET['support_stage'] ) ) : '';
		$product_filter = isset( $_GET['support_product'] ) ? sanitize_title( wp_unslash( $_GET['support_product'] ) ) : '';
		$severity_filter = isset( $_GET['support_severity'] ) ? sanitize_key( wp_unslash( $_GET['support_severity'] ) ) : '';
		$search = isset( $_GET['support_search'] ) ? sanitize_text_field( wp_unslash( $_GET['support_search'] ) ) : '';
		$case_id = absint( $_GET['support_case'] ?? 0 );
		$message = isset( $_GET['sc_ei_msg'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_msg'] ) ) : '';
		$items = SC_EI_Support_Repository::query( array( 'stage' => $stage_filter, 'product' => $product_filter, 'severity' => $severity_filter, 'search' => $search, 'limit' => 500 ) );
		$selected = $case_id ? SC_EI_Support_Repository::find( $case_id ) : ( $items[0] ?? null );
		$inquiry = $selected ? SC_EI_Inquiry_Repository::find( absint( $selected['inquiry_id'] ) ) : null;
		$links = $selected ? SC_EI_Support_Repository::links( absint( $selected['id'] ) ) : array();
		$events = $selected ? SC_EI_Support_Repository::events( absint( $selected['id'] ) ) : array();
		$metrics = SC_EI_Support_Repository::metrics();
		$users = get_users( array( 'fields' => array( 'ID', 'display_name', 'user_email' ), 'orderby' => 'display_name', 'order' => 'ASC' ) );
		include SC_EI_DIR . 'admin/views/support-cases.php';
	}

	public static function url( int $case_id = 0, array $args = array() ): string {
		$query = array_merge( array( 'page' => 'sc-engagement-intake-support' ), $args );
		if ( $case_id ) { $query['support_case'] = $case_id; }
		return add_query_arg( $query, admin_url( 'admin.php' ) );
	}

	public static function handle_update(): void {
		self::require_cap( 'sc_intake_manage_support' );
		$id = absint( $_POST['support_case_id'] ?? 0 );
		check_admin_referer( 'sc_ei_support_update_' . $id );
		$result = SC_EI_Support_Repository::update_context( $id, array(
			'product' => wp_unslash( $_POST['product'] ?? '' ), 'product_version' => wp_unslash( $_POST['product_version'] ?? '' ),
			'component' => wp_unslash( $_POST['component'] ?? '' ), 'issue_type' => wp_unslash( $_POST['issue_type'] ?? '' ),
			'severity' => wp_unslash( $_POST['severity'] ?? '' ), 'priority' => wp_unslash( $_POST['priority'] ?? '' ),
			'assigned_user_id' => absint( $_POST['assigned_user_id'] ?? 0 ), 'error_message' => wp_unslash( $_POST['error_message'] ?? '' ),
			'reproduction_steps' => wp_unslash( $_POST['reproduction_steps'] ?? '' ), 'expected_behavior' => wp_unslash( $_POST['expected_behavior'] ?? '' ),
			'actual_behavior' => wp_unslash( $_POST['actual_behavior'] ?? '' ), 'known_issue_reference' => wp_unslash( $_POST['known_issue_reference'] ?? '' ),
			'sender_summary' => wp_unslash( $_POST['sender_summary'] ?? '' ), 'sender_next_step' => wp_unslash( $_POST['sender_next_step'] ?? '' ),
			'environment' => array( 'browser' => wp_unslash( $_POST['environment_browser'] ?? '' ), 'os' => wp_unslash( $_POST['environment_os'] ?? '' ), 'wordpress' => wp_unslash( $_POST['environment_wordpress'] ?? '' ), 'php' => wp_unslash( $_POST['environment_php'] ?? '' ), 'plugin_version' => wp_unslash( $_POST['environment_plugin_version'] ?? '' ), 'backend_version' => wp_unslash( $_POST['environment_backend_version'] ?? '' ), 'url' => wp_unslash( $_POST['environment_url'] ?? '' ) ),
		), get_current_user_id() );
		self::redirect( $id, is_wp_error( $result ) ? $result->get_error_code() : 'support_case_updated' );
	}

	public static function handle_transition(): void {
		self::require_cap( 'sc_intake_manage_support' );
		$id = absint( $_POST['support_case_id'] ?? 0 );
		check_admin_referer( 'sc_ei_support_transition_' . $id );
		$result = SC_EI_Support_Repository::transition( $id, (string) wp_unslash( $_POST['support_stage'] ?? '' ), (string) wp_unslash( $_POST['support_confirmation'] ?? '' ), (string) wp_unslash( $_POST['transition_reason'] ?? '' ), get_current_user_id() );
		self::redirect( $id, is_wp_error( $result ) ? $result->get_error_code() : 'support_stage_changed' );
	}

	public static function handle_add_link(): void {
		self::require_cap( 'sc_intake_manage_support_links' );
		$id = absint( $_POST['support_case_id'] ?? 0 );
		check_admin_referer( 'sc_ei_support_add_link_' . $id );
		$result = SC_EI_Support_Repository::add_link( $id, array( 'related_type' => wp_unslash( $_POST['related_type'] ?? '' ), 'related_reference' => wp_unslash( $_POST['related_reference'] ?? '' ), 'relation_type' => wp_unslash( $_POST['relation_type'] ?? 'related' ), 'title' => wp_unslash( $_POST['link_title'] ?? '' ), 'url' => wp_unslash( $_POST['link_url'] ?? '' ), 'sender_visible' => ! empty( $_POST['sender_visible'] ) ), get_current_user_id() );
		self::redirect( $id, is_wp_error( $result ) ? $result->get_error_code() : 'support_link_added' );
	}

	public static function handle_add_signal(): void {
		self::require_cap( 'sc_intake_manage_support_intelligence' );
		$id = absint( $_POST['support_case_id'] ?? 0 );
		check_admin_referer( 'sc_ei_support_add_signal_' . $id );
		$case = SC_EI_Support_Repository::find( $id );
		$result = $case ? SC_EI_Support_Repository::record_signal( (string) wp_unslash( $_POST['signal_type'] ?? '' ), array( 'product' => $case['product'], 'product_version' => $case['product_version'], 'component' => $case['component'], 'issue_type' => $case['issue_type'], 'search_query' => wp_unslash( $_POST['signal_summary'] ?? '' ), 'source_url' => '' ), get_current_user_id() ) : new WP_Error( 'support_case_not_found' );
		self::redirect( $id, is_wp_error( $result ) ? $result->get_error_code() : 'support_signal_added' );
	}

	private static function require_cap( string $capability ): void {
		if ( ! current_user_can( $capability ) ) { wp_die( esc_html__( 'You do not have permission to manage product support cases.', 'sustainable-catalyst-engagement-intake' ) ); }
	}
	private static function redirect( int $case_id, string $message ): void { wp_safe_redirect( self::url( $case_id, array( 'sc_ei_msg' => sanitize_key( $message ) ) ), 303 ); exit; }
}
