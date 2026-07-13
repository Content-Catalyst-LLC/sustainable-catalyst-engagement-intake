<?php
/**
 * Privacy and Retention Center administration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Privacy_Admin {

	public static function register(): void {
		add_action( 'admin_post_sc_ei_create_privacy_request', array( __CLASS__, 'handle_create_request' ) );
		add_action( 'admin_post_sc_ei_update_privacy_request', array( __CLASS__, 'handle_update_request' ) );
		add_action( 'admin_post_sc_ei_record_consent_event', array( __CLASS__, 'handle_record_consent' ) );
		add_action( 'admin_post_sc_ei_place_legal_hold', array( __CLASS__, 'handle_place_hold' ) );
		add_action( 'admin_post_sc_ei_release_legal_hold', array( __CLASS__, 'handle_release_hold' ) );
		add_action( 'admin_post_sc_ei_preview_retention_center', array( __CLASS__, 'handle_preview' ) );
		add_action( 'admin_post_sc_ei_queue_retention_center', array( __CLASS__, 'handle_queue' ) );
		add_action( 'admin_post_sc_ei_approve_retention_action', array( __CLASS__, 'handle_approve_action' ) );
		add_action( 'admin_post_sc_ei_execute_retention_action', array( __CLASS__, 'handle_execute_action' ) );
		add_action( 'admin_post_sc_ei_cancel_retention_action', array( __CLASS__, 'handle_cancel_action' ) );
		add_action( 'admin_post_sc_ei_save_retention_policy', array( __CLASS__, 'handle_save_policy' ) );
		add_action( 'admin_post_sc_ei_update_inquiry_privacy_state', array( __CLASS__, 'handle_inquiry_state' ) );
		add_action( 'admin_post_sc_ei_export_privacy_inventory', array( __CLASS__, 'handle_export_inventory' ) );
	}

	public static function submenu(): void {
		add_submenu_page(
			'sc-engagement-intake',
			__( 'Privacy and Retention Center', 'sustainable-catalyst-engagement-intake' ),
			__( 'Privacy Center', 'sustainable-catalyst-engagement-intake' ),
			'sc_intake_view_privacy_center',
			'sc-engagement-intake-privacy',
			array( __CLASS__, 'page' )
		);
	}

	public static function page(): void {
		if ( ! current_user_can( 'sc_intake_view_privacy_center' ) ) {
			wp_die( esc_html__( 'You do not have permission to view the Privacy and Retention Center.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'overview';
		$allowed = array( 'overview', 'requests', 'consent', 'holds', 'queue', 'policies', 'method' );
		if ( ! in_array( $view, $allowed, true ) ) {
			$view = 'overview';
		}

		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() );
		$metrics = SC_EI_Privacy_Repository::metrics();
		$inventory = SC_EI_Privacy_Repository::data_inventory();
		$policies = SC_EI_Retention_Policy_Repository::active();
		$reviewers = SC_EI_Review_Schema::reviewers();
		$requests = array();
		$consents = array();
		$holds = array();
		$actions = array();
		$preview = get_option( 'sc_ei_last_privacy_retention_preview', array() );
		$last_queue = get_option( 'sc_ei_last_retention_queue_run', array() );

		if ( 'requests' === $view ) {
			$requests = SC_EI_Privacy_Repository::requests(
				array(
					'status' => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '',
					'type'   => isset( $_GET['type'] ) ? sanitize_key( wp_unslash( $_GET['type'] ) ) : '',
					'search' => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
					'page'   => isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1,
					'per_page'=> 50,
				)
			);
		} elseif ( 'consent' === $view ) {
			$consents = SC_EI_Privacy_Repository::consent_events(
				array(
					'action' => isset( $_GET['action_filter'] ) ? sanitize_key( wp_unslash( $_GET['action_filter'] ) ) : '',
					'search' => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
					'limit'  => 500,
				)
			);
		} elseif ( 'holds' === $view ) {
			$holds = SC_EI_Privacy_Repository::holds(
				array(
					'status' => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '',
					'search' => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
					'limit'  => 500,
				)
			);
		} elseif ( 'queue' === $view ) {
			$actions = SC_EI_Privacy_Repository::retention_actions(
				array(
					'status'      => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '',
					'target_type' => isset( $_GET['target_type'] ) ? sanitize_key( wp_unslash( $_GET['target_type'] ) ) : '',
					'search'      => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
					'limit'       => 500,
				)
			);
		}

		include SC_EI_DIR . 'admin/views/privacy-center.php';
	}

	public static function url( string $view = 'overview', array $args = array() ): string {
		return add_query_arg(
			array_merge( array( 'page' => 'sc-engagement-intake-privacy', 'view' => $view ), $args ),
			admin_url( 'admin.php' )
		);
	}

	public static function handle_create_request(): void {
		self::require_cap( 'sc_intake_manage_privacy_requests' );
		check_admin_referer( 'sc_ei_create_privacy_request' );
		$result = SC_EI_Privacy_Repository::create_request(
			array(
				'inquiry_id'       => absint( $_POST['inquiry_id'] ?? 0 ),
				'requester_name'    => isset( $_POST['requester_name'] ) ? sanitize_text_field( wp_unslash( $_POST['requester_name'] ) ) : '',
				'requester_email'   => isset( $_POST['requester_email'] ) ? sanitize_email( wp_unslash( $_POST['requester_email'] ) ) : '',
				'request_type'      => isset( $_POST['request_type'] ) ? sanitize_key( wp_unslash( $_POST['request_type'] ) ) : 'access',
				'identity_status'   => isset( $_POST['identity_status'] ) ? sanitize_key( wp_unslash( $_POST['identity_status'] ) ) : 'unverified',
				'source'            => isset( $_POST['source'] ) ? sanitize_key( wp_unslash( $_POST['source'] ) ) : 'admin',
				'due_at'            => isset( $_POST['due_at'] ) ? sanitize_text_field( wp_unslash( $_POST['due_at'] ) ) : '',
				'assigned_user_id'  => absint( $_POST['assigned_user_id'] ?? 0 ),
				'request_summary'   => isset( $_POST['request_summary'] ) ? sanitize_textarea_field( wp_unslash( $_POST['request_summary'] ) ) : '',
			),
			get_current_user_id()
		);
		self::redirect( 'requests', is_wp_error( $result ) ? $result->get_error_code() : 'privacy_request_created' );
	}

	public static function handle_update_request(): void {
		self::require_cap( 'sc_intake_manage_privacy_requests' );
		$id = absint( $_POST['request_id'] ?? 0 );
		check_admin_referer( 'sc_ei_update_privacy_request_' . $id );
		$result = SC_EI_Privacy_Repository::update_request(
			$id,
			array(
				'status'             => isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '',
				'identity_status'    => isset( $_POST['identity_status'] ) ? sanitize_key( wp_unslash( $_POST['identity_status'] ) ) : '',
				'assigned_user_id'   => absint( $_POST['assigned_user_id'] ?? 0 ),
				'due_at'             => isset( $_POST['due_at'] ) ? sanitize_text_field( wp_unslash( $_POST['due_at'] ) ) : '',
				'request_summary'    => isset( $_POST['request_summary'] ) ? sanitize_textarea_field( wp_unslash( $_POST['request_summary'] ) ) : '',
				'resolution_summary' => isset( $_POST['resolution_summary'] ) ? sanitize_textarea_field( wp_unslash( $_POST['resolution_summary'] ) ) : '',
			),
			get_current_user_id()
		);
		self::redirect( 'requests', is_wp_error( $result ) ? $result->get_error_code() : 'privacy_request_updated' );
	}

	public static function handle_record_consent(): void {
		self::require_cap( 'sc_intake_manage_consent' );
		check_admin_referer( 'sc_ei_record_consent_event' );
		$result = SC_EI_Privacy_Repository::record_consent(
			absint( $_POST['inquiry_id'] ?? 0 ),
			array(
				'consent_type'    => isset( $_POST['consent_type'] ) ? sanitize_key( wp_unslash( $_POST['consent_type'] ) ) : 'privacy_notice',
				'action'          => isset( $_POST['consent_action'] ) ? sanitize_key( wp_unslash( $_POST['consent_action'] ) ) : 'granted',
				'consent_version' => isset( $_POST['consent_version'] ) ? sanitize_text_field( wp_unslash( $_POST['consent_version'] ) ) : '',
				'lawful_basis'    => isset( $_POST['lawful_basis'] ) ? sanitize_key( wp_unslash( $_POST['lawful_basis'] ) ) : 'request_processing',
				'source'          => isset( $_POST['source'] ) ? sanitize_key( wp_unslash( $_POST['source'] ) ) : 'admin',
				'evidence_text'   => isset( $_POST['evidence_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['evidence_text'] ) ) : '',
				'occurred_at'     => isset( $_POST['occurred_at'] ) ? sanitize_text_field( wp_unslash( $_POST['occurred_at'] ) ) : '',
			),
			get_current_user_id()
		);
		self::redirect( 'consent', is_wp_error( $result ) ? $result->get_error_code() : 'consent_event_recorded' );
	}

	public static function handle_place_hold(): void {
		self::require_cap( 'sc_intake_manage_legal_holds' );
		check_admin_referer( 'sc_ei_place_legal_hold' );
		$result = SC_EI_Privacy_Repository::place_hold(
			array(
				'inquiry_id'    => absint( $_POST['inquiry_id'] ?? 0 ),
				'attachment_id' => absint( $_POST['attachment_id'] ?? 0 ),
				'scope'         => isset( $_POST['scope'] ) ? sanitize_key( wp_unslash( $_POST['scope'] ) ) : 'inquiry',
				'reason'        => isset( $_POST['reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['reason'] ) ) : '',
				'authority'     => isset( $_POST['authority'] ) ? sanitize_text_field( wp_unslash( $_POST['authority'] ) ) : '',
				'review_at'     => isset( $_POST['review_at'] ) ? sanitize_text_field( wp_unslash( $_POST['review_at'] ) ) : '',
			),
			get_current_user_id()
		);
		self::redirect( 'holds', is_wp_error( $result ) ? $result->get_error_code() : 'legal_hold_placed' );
	}

	public static function handle_release_hold(): void {
		self::require_cap( 'sc_intake_manage_legal_holds' );
		$id = absint( $_POST['hold_id'] ?? 0 );
		check_admin_referer( 'sc_ei_release_legal_hold_' . $id );
		$result = SC_EI_Privacy_Repository::release_hold(
			$id,
			isset( $_POST['release_reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['release_reason'] ) ) : '',
			get_current_user_id()
		);
		self::redirect( 'holds', is_wp_error( $result ) ? $result->get_error_code() : 'legal_hold_released' );
	}

	public static function handle_preview(): void {
		self::require_cap( 'sc_intake_manage_retention_policies' );
		check_admin_referer( 'sc_ei_preview_retention_center' );
		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() );
		$preview = SC_EI_Retention_Engine::preview( absint( $settings['retention_queue_batch_limit'] ?? 100 ) );
		update_option( 'sc_ei_last_privacy_retention_preview', $preview, false );
		SC_EI_Audit_Log::record(
			'retention_preview_generated',
			'Privacy and Retention Center preview generated. No deletion was performed.',
			array( 'candidate_count' => $preview['count'], 'total_bytes' => $preview['total_bytes'] ),
			null,
			null,
			get_current_user_id()
		);
		self::redirect( 'queue', 'retention_preview_ready' );
	}

	public static function handle_queue(): void {
		self::require_cap( 'sc_intake_manage_retention_policies' );
		check_admin_referer( 'sc_ei_queue_retention_center' );
		if ( 'QUEUE' !== strtoupper( trim( (string) ( $_POST['confirm_queue'] ?? '' ) ) ) ) {
			self::redirect( 'queue', 'retention_queue_confirmation_failed' );
		}
		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() );
		SC_EI_Retention_Engine::queue_candidates(
			absint( $settings['retention_queue_batch_limit'] ?? 100 ),
			get_current_user_id(),
			'privacy_center'
		);
		self::redirect( 'queue', 'retention_candidates_queued' );
	}

	public static function handle_approve_action(): void {
		self::require_cap( 'sc_intake_approve_retention_actions' );
		$id = absint( $_POST['action_id'] ?? 0 );
		check_admin_referer( 'sc_ei_approve_retention_action_' . $id );
		$result = SC_EI_Privacy_Repository::approve_action( $id, get_current_user_id() );
		self::redirect( 'queue', is_wp_error( $result ) ? $result->get_error_code() : 'retention_action_approved' );
	}

	public static function handle_execute_action(): void {
		self::require_cap( 'sc_intake_execute_retention_actions' );
		$id = absint( $_POST['action_id'] ?? 0 );
		check_admin_referer( 'sc_ei_execute_retention_action_' . $id );
		$expected = 'EXECUTE ' . $id;
		$provided = strtoupper( trim( (string) ( $_POST['confirm_execute'] ?? '' ) ) );
		if ( $expected !== $provided ) {
			self::redirect( 'queue', 'retention_execution_confirmation_failed' );
		}
		$result = SC_EI_Retention_Engine::execute_action( $id, get_current_user_id() );
		self::redirect( 'queue', is_wp_error( $result ) ? $result->get_error_code() : 'retention_action_executed' );
	}

	public static function handle_cancel_action(): void {
		self::require_cap( 'sc_intake_approve_retention_actions' );
		$id = absint( $_POST['action_id'] ?? 0 );
		check_admin_referer( 'sc_ei_cancel_retention_action_' . $id );
		$result = SC_EI_Privacy_Repository::cancel_action(
			$id,
			isset( $_POST['cancel_reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['cancel_reason'] ) ) : '',
			get_current_user_id()
		);
		self::redirect( 'queue', is_wp_error( $result ) ? $result->get_error_code() : 'retention_action_canceled' );
	}

	public static function handle_save_policy(): void {
		self::require_cap( 'sc_intake_manage_retention_policies' );
		check_admin_referer( 'sc_ei_save_retention_policy' );
		$result = SC_EI_Retention_Policy_Repository::create_version(
			array(
				'policy_key'     => isset( $_POST['policy_key'] ) ? sanitize_key( wp_unslash( $_POST['policy_key'] ) ) : '',
				'name'           => isset( $_POST['policy_name'] ) ? sanitize_text_field( wp_unslash( $_POST['policy_name'] ) ) : '',
				'target_type'    => isset( $_POST['target_type'] ) ? sanitize_key( wp_unslash( $_POST['target_type'] ) ) : 'inquiry',
				'status_scope'   => isset( $_POST['status_scope'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['status_scope'] ) ) : array(),
				'retention_days' => absint( $_POST['retention_days'] ?? 365 ),
				'anchor_field'   => isset( $_POST['anchor_field'] ) ? sanitize_key( wp_unslash( $_POST['anchor_field'] ) ) : 'created_at',
				'action_type'    => isset( $_POST['action_type'] ) ? sanitize_key( wp_unslash( $_POST['action_type'] ) ) : 'archive_only',
				'legal_basis'    => isset( $_POST['legal_basis'] ) ? sanitize_key( wp_unslash( $_POST['legal_basis'] ) ) : 'other',
				'description'    => isset( $_POST['description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['description'] ) ) : '',
			),
			get_current_user_id()
		);
		self::redirect( 'policies', is_wp_error( $result ) ? $result->get_error_code() : 'retention_policy_saved' );
	}

	public static function handle_inquiry_state(): void {
		self::require_cap( 'sc_intake_manage_privacy_requests' );
		$inquiry_id = absint( $_POST['inquiry_id'] ?? 0 );
		check_admin_referer( 'sc_ei_update_inquiry_privacy_state' );
		$success = SC_EI_Privacy_Repository::set_inquiry_privacy_state(
			$inquiry_id,
			isset( $_POST['privacy_status'] ) ? sanitize_key( wp_unslash( $_POST['privacy_status'] ) ) : 'active',
			isset( $_POST['privacy_reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['privacy_reason'] ) ) : '',
			get_current_user_id()
		);
		self::redirect( 'overview', $success ? 'inquiry_privacy_state_updated' : 'inquiry_privacy_state_failed' );
	}

	public static function handle_export_inventory(): void {
		self::require_cap( 'sc_intake_export_privacy_data' );
		check_admin_referer( 'sc_ei_export_privacy_inventory' );
		$data = array(
			'schema'       => 'sc-engagement-intake-privacy-inventory/1.0',
			'generated_at' => current_time( 'mysql', true ),
			'generated_by' => get_current_user_id(),
			'inventory'    => SC_EI_Privacy_Repository::data_inventory(),
			'metrics'      => SC_EI_Privacy_Repository::metrics(),
			'policies'     => SC_EI_Retention_Policy_Repository::active(),
			'settings'     => array_intersect_key(
				wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() ),
				SC_EI_Privacy_Schema::default_settings()
			),
			'last_queue_run'=> get_option( 'sc_ei_last_retention_queue_run', array() ),
		);
		SC_EI_Audit_Log::record(
			'privacy_inventory_exported',
			'Authorized user exported Privacy and Retention Center inventory.',
			array( 'schema' => $data['schema'] ),
			null,
			null,
			get_current_user_id()
		);
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="engagement-intake-privacy-inventory-' . gmdate( 'Y-m-d-His' ) . '.json"' );
		header( 'X-Content-Type-Options: nosniff' );
		echo wp_json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		exit;
	}

	private static function require_cap( string $capability ): void {
		if ( ! current_user_can( $capability ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this privacy operation.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}
	}

	private static function redirect( string $view, string $message ): void {
		wp_safe_redirect( self::url( $view, array( 'sc_ei_msg' => sanitize_key( $message ) ) ), 303 );
		exit;
	}
}
