<?php
/**
 * WordPress administration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Admin {

	public static function register(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_init', array( __CLASS__, 'settings' ) );
		add_action( 'admin_post_sc_ei_update_status', array( __CLASS__, 'handle_status' ) );
		add_action( 'admin_post_sc_ei_add_note', array( __CLASS__, 'handle_note' ) );
		add_filter( 'set-screen-option', array( __CLASS__, 'screen_option' ), 10, 3 );
		add_filter( 'plugin_action_links_' . SC_EI_BASENAME, array( __CLASS__, 'plugin_links' ) );
	}

	public static function menu(): void {
		$hook = add_menu_page(
			__( 'Engagement Intake', 'sustainable-catalyst-engagement-intake' ),
			__( 'Engagement Intake', 'sustainable-catalyst-engagement-intake' ),
			'sc_intake_view',
			'sc-engagement-intake',
			array( __CLASS__, 'inquiries_page' ),
			'dashicons-id-alt',
			27
		);

		add_action(
			"load-{$hook}",
			static function(): void {
				add_screen_option(
					'per_page',
					array(
						'label'   => __( 'Inquiries per page', 'sustainable-catalyst-engagement-intake' ),
						'default' => 20,
						'option'  => 'sc_ei_inquiries_per_page',
					)
				);
			}
		);

		add_submenu_page(
			'sc-engagement-intake',
			__( 'Inquiries', 'sustainable-catalyst-engagement-intake' ),
			__( 'Inquiries', 'sustainable-catalyst-engagement-intake' ),
			'sc_intake_view',
			'sc-engagement-intake',
			array( __CLASS__, 'inquiries_page' )
		);

		add_submenu_page(
			'sc-engagement-intake',
			__( 'Diagnostics', 'sustainable-catalyst-engagement-intake' ),
			__( 'Diagnostics', 'sustainable-catalyst-engagement-intake' ),
			'sc_intake_manage_settings',
			'sc-engagement-intake-diagnostics',
			array( __CLASS__, 'diagnostics_page' )
		);

		add_submenu_page(
			'sc-engagement-intake',
			__( 'Settings', 'sustainable-catalyst-engagement-intake' ),
			__( 'Settings', 'sustainable-catalyst-engagement-intake' ),
			'sc_intake_manage_settings',
			'sc-engagement-intake-settings',
			array( __CLASS__, 'settings_page' )
		);
	}

	public static function assets( string $hook ): void {
		if ( false === strpos( $hook, 'sc-engagement-intake' ) ) {
			return;
		}

		wp_enqueue_style(
			'sc-ei-admin',
			SC_EI_URL . 'assets/css/admin.css',
			array(),
			SC_EI_VERSION
		);
	}

	public static function settings(): void {
		register_setting(
			'sc_ei_settings_group',
			'sc_ei_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => self::default_settings(),
			)
		);
	}

	public static function default_settings(): array {
		return array(
			'delete_data_on_uninstall'         => 0,
			'default_unaccepted_retention_days'=> 365,
			'withdrawn_retention_days'         => 30,
			'abandoned_draft_days'              => 30,
		);
	}

	public static function sanitize_settings( $value ): array {
		$value = is_array( $value ) ? $value : array();

		return array(
			'delete_data_on_uninstall'          => empty( $value['delete_data_on_uninstall'] ) ? 0 : 1,
			'default_unaccepted_retention_days' => max( 30, min( 3650, absint( $value['default_unaccepted_retention_days'] ?? 365 ) ) ),
			'withdrawn_retention_days'          => max( 1, min( 365, absint( $value['withdrawn_retention_days'] ?? 30 ) ) ),
			'abandoned_draft_days'              => max( 1, min( 365, absint( $value['abandoned_draft_days'] ?? 30 ) ) ),
		);
	}

	public static function screen_option( $status, string $option, $value ) {
		if ( 'sc_ei_inquiries_per_page' === $option ) {
			return max( 1, min( 100, absint( $value ) ) );
		}
		return $status;
	}

	public static function plugin_links( array $links ): array {
		array_unshift(
			$links,
			sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'admin.php?page=sc-engagement-intake' ) ),
				esc_html__( 'Inquiries', 'sustainable-catalyst-engagement-intake' )
			)
		);
		return $links;
	}

	public static function inquiries_page(): void {
		if ( ! current_user_can( 'sc_intake_view' ) ) {
			wp_die( esc_html__( 'You do not have permission to view private inquiries.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
		$id     = isset( $_GET['inquiry'] ) ? absint( $_GET['inquiry'] ) : 0;

		if ( 'view' === $action && $id ) {
			self::render_inquiry( $id );
			return;
		}

		$list_table = new SC_EI_Admin_List_Table();
		$list_table->prepare_items();
		include SC_EI_DIR . 'admin/views/inquiries-list.php';
	}

	private static function render_inquiry( int $id ): void {
		$inquiry = SC_EI_Inquiry_Repository::find( $id );
		if ( ! $inquiry ) {
			wp_die( esc_html__( 'Inquiry not found.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$attachments = SC_EI_Attachment_Repository::for_inquiry( $id );
		$audit_log   = SC_EI_Audit_Log::for_inquiry( $id );
		include SC_EI_DIR . 'admin/views/inquiry-view.php';
	}

	public static function diagnostics_page(): void {
		if ( ! current_user_can( 'sc_intake_manage_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to view diagnostics.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$diagnostics = SC_EI_Diagnostics::run();
		$status      = SC_EI_Diagnostics::overall_status( $diagnostics );
		include SC_EI_DIR . 'admin/views/diagnostics.php';
	}

	public static function settings_page(): void {
		if ( ! current_user_can( 'sc_intake_manage_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage settings.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), self::default_settings() );
		include SC_EI_DIR . 'admin/views/settings.php';
	}

	public static function handle_status(): void {
		if ( ! current_user_can( 'sc_intake_change_status' ) ) {
			wp_die( esc_html__( 'You do not have permission to change inquiry status.', 'sustainable-catalyst-engagement-intake' ) );
		}

		check_admin_referer( 'sc_ei_update_status' );

		$id     = isset( $_POST['inquiry_id'] ) ? absint( $_POST['inquiry_id'] ) : 0;
		$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		$note   = isset( $_POST['status_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['status_note'] ) ) : '';

		$success = $id && SC_EI_Inquiry_Repository::update_status( $id, $status, $note );

		$url = add_query_arg(
			array(
				'page'      => 'sc-engagement-intake',
				'action'    => 'view',
				'inquiry'   => $id,
				'sc_ei_msg' => $success ? 'status_updated' : 'error',
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	public static function handle_note(): void {
		if ( ! current_user_can( 'sc_intake_add_notes' ) ) {
			wp_die( esc_html__( 'You do not have permission to add private notes.', 'sustainable-catalyst-engagement-intake' ) );
		}

		check_admin_referer( 'sc_ei_add_note' );

		$id   = isset( $_POST['inquiry_id'] ) ? absint( $_POST['inquiry_id'] ) : 0;
		$note = isset( $_POST['internal_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['internal_note'] ) ) : '';

		$success = $id && SC_EI_Inquiry_Repository::add_internal_note( $id, $note );

		$url = add_query_arg(
			array(
				'page'      => 'sc-engagement-intake',
				'action'    => 'view',
				'inquiry'   => $id,
				'sc_ei_msg' => $success ? 'note_added' : 'error',
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}
}
