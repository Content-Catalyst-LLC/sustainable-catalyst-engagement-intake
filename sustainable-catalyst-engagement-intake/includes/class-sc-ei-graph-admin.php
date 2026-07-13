<?php
/**
 * Microsoft Graph connector administration and human-triggered meeting actions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Graph_Admin {

	public static function register(): void {
		add_action( 'admin_post_sc_ei_save_graph_settings', array( __CLASS__, 'handle_save_settings' ) );
		add_action( 'admin_post_sc_ei_test_graph', array( __CLASS__, 'handle_test' ) );
		add_action( 'admin_post_sc_ei_reset_graph_circuit', array( __CLASS__, 'handle_reset_circuit' ) );
		add_action( 'admin_post_sc_ei_clear_graph_token', array( __CLASS__, 'handle_clear_token' ) );
		add_action( 'admin_post_sc_ei_process_graph_queue', array( __CLASS__, 'handle_process_queue' ) );
		add_action( 'admin_post_sc_ei_retry_graph_operation', array( __CLASS__, 'handle_retry_operation' ) );
		add_action( 'admin_post_sc_ei_create_graph_event', array( __CLASS__, 'handle_create_event' ) );
		add_action( 'admin_post_sc_ei_reconcile_graph_event', array( __CLASS__, 'handle_reconcile_event' ) );
		add_action( 'admin_post_sc_ei_delete_graph_event', array( __CLASS__, 'handle_delete_event' ) );
		add_action( 'admin_post_sc_ei_reset_graph_linkage', array( __CLASS__, 'handle_reset_linkage' ) );
		add_action( 'admin_post_sc_ei_export_graph_operations', array( __CLASS__, 'handle_export' ) );
	}

	public static function submenu(): void {
		add_submenu_page(
			'sc-engagement-intake',
			__( 'Microsoft Graph Connector', 'sustainable-catalyst-engagement-intake' ),
			__( 'Microsoft Graph', 'sustainable-catalyst-engagement-intake' ),
			'sc_intake_view_graph',
			'sc-engagement-intake-graph',
			array( __CLASS__, 'page' )
		);
	}

	public static function page(): void {
		self::require_cap( 'sc_intake_view_graph' );

		$message = isset( $_GET['sc_ei_msg'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_msg'] ) ) : '';
		$status_filter = isset( $_GET['graph_status'] ) ? sanitize_key( wp_unslash( $_GET['graph_status'] ) ) : '';
		$type_filter = isset( $_GET['graph_type'] ) ? sanitize_key( wp_unslash( $_GET['graph_type'] ) ) : '';
		$settings = SC_EI_Graph_Repository::settings();
		$credentials = SC_EI_Graph_Credentials::public_status();
		$crypto = SC_EI_Graph_Crypto::status();
		$circuit = SC_EI_Graph_Client::circuit_status();
		$health = SC_EI_Graph_Repository::last_health();
		$metrics = SC_EI_Graph_Repository::metrics();
		$operations = SC_EI_Graph_Repository::query_operations(
			array(
				'status' => $status_filter,
				'type'   => $type_filter,
				'limit'  => 500,
			)
		);
		$next_catchup = wp_next_scheduled( 'sc_ei_graph_catchup' );
		$next_process = wp_next_scheduled( 'sc_ei_graph_process_queue' );
		include SC_EI_DIR . 'admin/views/microsoft-graph.php';
	}

	public static function url( array $args = array() ): string {
		return add_query_arg(
			array_merge( array( 'page' => 'sc-engagement-intake-graph' ), $args ),
			admin_url( 'admin.php' )
		);
	}

	public static function handle_save_settings(): void {
		self::require_cap( 'sc_intake_manage_graph_settings' );
		check_admin_referer( 'sc_ei_save_graph_settings' );
		self::require_confirmation( 'SAVE GRAPH SETTINGS', $_POST['graph_confirmation'] ?? '', 'graph_settings_confirmation_failed' );

		$raw = isset( $_POST['graph_settings'] ) ? (array) wp_unslash( $_POST['graph_settings'] ) : array();
		$credential_result = SC_EI_Graph_Credentials::save( $raw, get_current_user_id() );
		if ( is_wp_error( $credential_result ) ) {
			self::redirect( $credential_result->get_error_code() );
		}

		$current = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() );
		$enabled = empty( $raw['graph_enabled'] ) ? 0 : 1;
		if ( $enabled && empty( $credential_result['configured'] ) ) {
			$enabled = 0;
		}
		$updates = array(
			'graph_enabled'                   => $enabled,
			'graph_tenant_id'                 => sanitize_text_field( (string) ( $raw['graph_tenant_id'] ?? '' ) ),
			'graph_client_id'                 => sanitize_text_field( (string) ( $raw['graph_client_id'] ?? '' ) ),
			'graph_organizer_user'            => sanitize_email( (string) ( $raw['graph_organizer_user'] ?? '' ) ),
			'graph_calendar_id'               => sanitize_text_field( (string) ( $raw['graph_calendar_id'] ?? '' ) ),
			'graph_secret_expires_at'         => sanitize_text_field( (string) ( $raw['graph_secret_expires_at'] ?? '' ) ),
			'graph_include_sender_attendee'   => empty( $raw['graph_include_sender_attendee'] ) ? 0 : 1,
			'graph_require_calendar_consent'  => 1,
			'graph_allow_remote_cancel'       => empty( $raw['graph_allow_remote_cancel'] ) ? 0 : 1,
			'graph_retry_enabled'             => empty( $raw['graph_retry_enabled'] ) ? 0 : 1,
			'graph_max_attempts'              => max( 1, min( 20, absint( $raw['graph_max_attempts'] ?? 6 ) ) ),
			'graph_retry_base_seconds'        => max( 15, min( 900, absint( $raw['graph_retry_base_seconds'] ?? 60 ) ) ),
			'graph_retry_max_seconds'         => max( 60, min( DAY_IN_SECONDS, absint( $raw['graph_retry_max_seconds'] ?? 3600 ) ) ),
			'graph_request_timeout_seconds'   => max( 10, min( 60, absint( $raw['graph_request_timeout_seconds'] ?? 25 ) ) ),
			'graph_token_skew_seconds'        => max( 60, min( 900, absint( $raw['graph_token_skew_seconds'] ?? 300 ) ) ),
			'graph_circuit_failure_threshold' => max( 2, min( 20, absint( $raw['graph_circuit_failure_threshold'] ?? 5 ) ) ),
			'graph_circuit_cooldown_minutes'  => max( 1, min( 1440, absint( $raw['graph_circuit_cooldown_minutes'] ?? 15 ) ) ),
			'graph_reconcile_delay_seconds'   => max( 10, min( 900, absint( $raw['graph_reconcile_delay_seconds'] ?? 30 ) ) ),
			'graph_global_cloud_only'         => 1,
		);
		update_option( 'sc_ei_settings', array_merge( $current, $updates ), false );
		SC_EI_Graph_Client::reset_circuit( get_current_user_id() );
		self::redirect( $enabled ? 'graph_settings_saved' : 'graph_settings_saved_disabled' );
	}

	public static function handle_test(): void {
		self::require_cap( 'sc_intake_manage_graph_settings' );
		check_admin_referer( 'sc_ei_test_graph' );
		self::require_confirmation( 'TEST GRAPH', $_POST['graph_confirmation'] ?? '', 'graph_test_confirmation_failed' );
		$result = SC_EI_Graph_Repository::run_health_check( get_current_user_id() );
		self::redirect( is_wp_error( $result ) ? $result->get_error_code() : 'graph_test_succeeded' );
	}

	public static function handle_reset_circuit(): void {
		self::require_cap( 'sc_intake_manage_graph_settings' );
		check_admin_referer( 'sc_ei_reset_graph_circuit' );
		self::require_confirmation( 'RESET GRAPH CIRCUIT', $_POST['graph_confirmation'] ?? '', 'graph_circuit_confirmation_failed' );
		SC_EI_Graph_Client::reset_circuit( get_current_user_id() );
		self::redirect( 'graph_circuit_reset' );
	}

	public static function handle_clear_token(): void {
		self::require_cap( 'sc_intake_manage_graph_settings' );
		check_admin_referer( 'sc_ei_clear_graph_token' );
		self::require_confirmation( 'CLEAR GRAPH TOKEN', $_POST['graph_confirmation'] ?? '', 'graph_token_confirmation_failed' );
		SC_EI_Graph_Credentials::clear_token();
		SC_EI_Audit_Log::record(
			'graph_token_cache_cleared',
			'Authorized administrator cleared the encrypted Microsoft Graph access-token cache.',
			array(),
			null,
			null,
			get_current_user_id()
		);
		self::redirect( 'graph_token_cleared' );
	}

	public static function handle_process_queue(): void {
		self::require_cap( 'sc_intake_reconcile_graph_events' );
		check_admin_referer( 'sc_ei_process_graph_queue' );
		self::require_confirmation( 'PROCESS GRAPH QUEUE', $_POST['graph_confirmation'] ?? '', 'graph_queue_confirmation_failed' );
		SC_EI_Graph_Repository::process_due( 50 );
		self::redirect( 'graph_queue_processed' );
	}

	public static function handle_retry_operation(): void {
		self::require_cap( 'sc_intake_reconcile_graph_events' );
		$operation_id = absint( $_POST['graph_operation_id'] ?? 0 );
		check_admin_referer( 'sc_ei_retry_graph_operation_' . $operation_id );
		self::require_confirmation( 'RETRY GRAPH ' . $operation_id, $_POST['graph_confirmation'] ?? '', 'graph_retry_confirmation_failed' );
		$result = SC_EI_Graph_Repository::retry_operation( $operation_id, get_current_user_id() );
		self::redirect( is_wp_error( $result ) ? $result->get_error_code() : 'graph_operation_retried' );
	}

	public static function handle_create_event(): void {
		self::require_cap( 'sc_intake_create_graph_events' );
		$meeting_id = absint( $_POST['meeting_offer_id'] ?? 0 );
		check_admin_referer( 'sc_ei_create_graph_event_' . $meeting_id );
		$offer = SC_EI_Workflow_Repository::find_meeting_offer( $meeting_id );
		if ( ! $offer ) {
			self::workflow_redirect( 0, 'graph_meeting_not_found' );
		}
		self::require_confirmation( 'GRAPH ' . strtoupper( $offer['offer_number'] ), $_POST['graph_confirmation'] ?? '', 'graph_create_confirmation_failed' );
		$result = SC_EI_Graph_Repository::enqueue_create( $meeting_id, get_current_user_id(), true );
		self::workflow_redirect(
			absint( $offer['inquiry_id'] ),
			is_wp_error( $result ) ? $result->get_error_code() : 'graph_event_created_or_queued'
		);
	}

	public static function handle_reconcile_event(): void {
		self::require_cap( 'sc_intake_reconcile_graph_events' );
		$meeting_id = absint( $_POST['meeting_offer_id'] ?? 0 );
		check_admin_referer( 'sc_ei_reconcile_graph_event_' . $meeting_id );
		$offer = SC_EI_Workflow_Repository::find_meeting_offer( $meeting_id );
		if ( ! $offer ) {
			self::workflow_redirect( 0, 'graph_meeting_not_found' );
		}
		self::require_confirmation( 'RECONCILE ' . strtoupper( $offer['offer_number'] ), $_POST['graph_confirmation'] ?? '', 'graph_reconcile_confirmation_failed' );
		$result = SC_EI_Graph_Repository::enqueue_reconcile( $meeting_id, get_current_user_id(), true );
		self::workflow_redirect(
			absint( $offer['inquiry_id'] ),
			is_wp_error( $result ) ? $result->get_error_code() : 'graph_event_reconciled_or_queued'
		);
	}

	public static function handle_delete_event(): void {
		self::require_cap( 'sc_intake_cancel_graph_events' );
		$meeting_id = absint( $_POST['meeting_offer_id'] ?? 0 );
		check_admin_referer( 'sc_ei_delete_graph_event_' . $meeting_id );
		$offer = SC_EI_Workflow_Repository::find_meeting_offer( $meeting_id );
		if ( ! $offer ) {
			self::workflow_redirect( 0, 'graph_meeting_not_found' );
		}
		self::require_confirmation( 'DELETE GRAPH ' . strtoupper( $offer['offer_number'] ), $_POST['graph_confirmation'] ?? '', 'graph_delete_confirmation_failed' );
		$result = SC_EI_Graph_Repository::enqueue_delete( $meeting_id, get_current_user_id(), true );
		self::workflow_redirect(
			absint( $offer['inquiry_id'] ),
			is_wp_error( $result ) ? $result->get_error_code() : 'graph_event_deleted_or_queued'
		);
	}

	public static function handle_reset_linkage(): void {
		self::require_cap( 'sc_intake_reconcile_graph_events' );
		$meeting_id = absint( $_POST['meeting_offer_id'] ?? 0 );
		check_admin_referer( 'sc_ei_reset_graph_linkage_' . $meeting_id );
		$offer = SC_EI_Workflow_Repository::find_meeting_offer( $meeting_id );
		if ( ! $offer ) {
			self::workflow_redirect( 0, 'graph_meeting_not_found' );
		}
		self::require_confirmation( 'RESET GRAPH ' . strtoupper( $offer['offer_number'] ), $_POST['graph_confirmation'] ?? '', 'graph_reset_confirmation_failed' );
		$result = SC_EI_Graph_Repository::reset_linkage(
			$meeting_id,
			wp_unslash( $_POST['graph_reset_reason'] ?? '' ),
			get_current_user_id()
		);
		self::workflow_redirect(
			absint( $offer['inquiry_id'] ),
			is_wp_error( $result ) ? $result->get_error_code() : 'graph_linkage_reset'
		);
	}

	public static function handle_export(): void {
		self::require_cap( 'sc_intake_export_graph_operations' );
		check_admin_referer( 'sc_ei_export_graph_operations' );
		$operations = SC_EI_Graph_Repository::query_operations( array( 'limit' => 2000 ) );
		foreach ( $operations as &$operation ) {
			unset( $operation['payload_json'] );
			$operation['payload_encrypted'] = true;
		}
		unset( $operation );
		$payload = array(
			'schema'      => 'sc-engagement-intake-microsoft-graph-operations/1.0',
			'generated_at'=> current_time( 'mysql', true ),
			'credentials' => SC_EI_Graph_Credentials::public_status(),
			'crypto'      => SC_EI_Graph_Crypto::status(),
			'circuit'     => SC_EI_Graph_Client::circuit_status(),
			'health'      => SC_EI_Graph_Repository::last_health(),
			'metrics'     => SC_EI_Graph_Repository::metrics(),
			'operations'  => $operations,
			'secrets_included' => false,
		);
		SC_EI_Audit_Log::record(
			'graph_operations_exported',
			'Authorized user exported redacted Microsoft Graph operation records.',
			array( 'operation_count' => count( $operations ), 'secrets_included' => false ),
			null,
			null,
			get_current_user_id()
		);
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="sc-ei-microsoft-graph-operations-' . gmdate( 'Ymd-His' ) . '.json"' );
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		exit;
	}

	private static function require_cap( string $capability ): void {
		if ( ! current_user_can( $capability ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this Microsoft Graph action.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}
	}

	private static function require_confirmation( string $expected, $provided, string $error_code ): void {
		$provided = strtoupper( trim( sanitize_text_field( wp_unslash( (string) $provided ) ) ) );
		if ( ! hash_equals( $expected, $provided ) ) {
			self::redirect( $error_code );
		}
	}

	private static function redirect( string $message ): void {
		wp_safe_redirect( self::url( array( 'sc_ei_msg' => sanitize_key( $message ) ) ), 303 );
		exit;
	}

	private static function workflow_redirect( int $inquiry_id, string $message ): void {
		wp_safe_redirect( SC_EI_Workflow_Admin::url( $inquiry_id, array( 'sc_ei_msg' => sanitize_key( $message ) ) ), 303 );
		exit;
	}
}
