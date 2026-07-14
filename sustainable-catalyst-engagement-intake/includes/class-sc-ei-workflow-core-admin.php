<?php
/**
 * Workflow Core administration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Workflow_Core_Admin {

	public static function register(): void {
		add_action( 'admin_post_sc_ei_workflow_core_sync_case', array( __CLASS__, 'handle_sync_case' ) );
		add_action( 'admin_post_sc_ei_workflow_core_sync_all', array( __CLASS__, 'handle_sync_all' ) );
		add_action( 'admin_post_sc_ei_workflow_core_prepare_handoff', array( __CLASS__, 'handle_prepare_handoff' ) );
		add_action( 'admin_post_sc_ei_workflow_core_dispatch', array( __CLASS__, 'handle_dispatch' ) );
		add_action( 'admin_post_sc_ei_workflow_core_acknowledge', array( __CLASS__, 'handle_acknowledge' ) );
		add_action( 'admin_post_sc_ei_workflow_core_cancel_handoff', array( __CLASS__, 'handle_cancel_handoff' ) );
		add_action( 'admin_post_sc_ei_workflow_core_resolve_consistency', array( __CLASS__, 'handle_resolve_consistency' ) );
		add_action( 'admin_post_sc_ei_workflow_core_export_case', array( __CLASS__, 'handle_export_case' ) );
		add_action( 'admin_post_sc_ei_workflow_core_export_handoff', array( __CLASS__, 'handle_export_handoff' ) );
		add_action( 'admin_post_sc_ei_workflow_core_save_settings', array( __CLASS__, 'handle_save_settings' ) );
	}

	public static function submenu(): void {
		add_submenu_page(
			'sc-engagement-intake',
			__( 'Workflow Core Integration', 'sustainable-catalyst-engagement-intake' ),
			__( 'Workflow Core', 'sustainable-catalyst-engagement-intake' ),
			'sc_intake_view_workflow_core',
			'sc-engagement-intake-workflow-core',
			array( __CLASS__, 'page' )
		);
	}

	public static function page(): void {
		self::require_cap( 'sc_intake_view_workflow_core' );

		$message = isset( $_GET['sc_ei_msg'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_msg'] ) ) : '';
		$case_id = absint( $_GET['case'] ?? 0 );
		$stage = isset( $_GET['core_stage'] ) ? sanitize_key( wp_unslash( $_GET['core_stage'] ) ) : '';
		$state = isset( $_GET['core_state'] ) ? sanitize_key( wp_unslash( $_GET['core_state'] ) ) : '';
		$consistency = isset( $_GET['core_consistency'] ) ? sanitize_key( wp_unslash( $_GET['core_consistency'] ) ) : '';
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

		$metrics = SC_EI_Workflow_Core_Repository::metrics();
		$cases = SC_EI_Workflow_Core_Repository::query_cases(
			array(
				'stage'       => $stage,
				'state'       => $state,
				'consistency' => $consistency,
				'search'      => $search,
				'limit'       => 1000,
			)
		);
		$selected = $case_id ? SC_EI_Workflow_Core_Repository::find_case( $case_id ) : null;
		$commands = $selected ? SC_EI_Workflow_Core_Repository::commands( $case_id, 250 ) : array();
		$handoffs = $selected ? SC_EI_Workflow_Core_Repository::handoffs( $case_id, 250 ) : array();
		$outbox = $selected ? SC_EI_Workflow_Core_Repository::outbox( $case_id, 250 ) : array();
		$notes = $selected ? json_decode( (string) $selected['consistency_notes'], true ) ?: array() : array();
		$settings = SC_EI_Workflow_Core_Repository::settings();
		$targets = SC_EI_Workflow_Core_Service::registered_targets();
		$last_sync = wp_parse_args( get_option( 'sc_ei_workflow_core_last_sync', array() ), array( 'completed_at' => '', 'requested' => 0, 'succeeded' => 0, 'failed' => 0 ) );
		$last_outbox = wp_parse_args( get_option( 'sc_ei_workflow_core_last_outbox', array() ), array( 'completed_at' => '', 'requested' => 0, 'dispatched' => 0, 'acknowledged' => 0, 'failed' => 0 ) );

		include SC_EI_DIR . 'admin/views/workflow-core.php';
	}

	public static function url( int $case_id = 0, array $args = array() ): string {
		$query = array_merge( array( 'page' => 'sc-engagement-intake-workflow-core' ), $args );
		if ( $case_id ) {
			$query['case'] = $case_id;
		}
		return add_query_arg( $query, admin_url( 'admin.php' ) );
	}

	public static function handle_sync_case(): void {
		self::require_cap( 'sc_intake_manage_workflow_core' );
		$case_id = absint( $_POST['case_id'] ?? 0 );
		check_admin_referer( 'sc_ei_workflow_core_sync_case_' . $case_id );
		$case = SC_EI_Workflow_Core_Repository::find_case( $case_id );
		if ( ! $case ) {
			self::redirect( 0, 'workflow_core_case_not_found' );
		}
		self::require_confirmation(
			'SYNC CASE ' . strtoupper( $case['reference'] ),
			$_POST['workflow_core_confirmation'] ?? '',
			$case_id,
			'workflow_core_sync_confirmation_failed'
		);
		$result = SC_EI_Workflow_Core_Repository::submit_command(
			$case_id,
			'sync_case',
			array(),
			'Authorized case synchronization.',
			get_current_user_id()
		);
		self::redirect( $case_id, is_wp_error( $result ) ? $result->get_error_code() : 'workflow_core_case_synchronized' );
	}

	public static function handle_sync_all(): void {
		self::require_cap( 'sc_intake_manage_workflow_core' );
		check_admin_referer( 'sc_ei_workflow_core_sync_all' );
		self::require_confirmation(
			'SYNC WORKFLOW CORE',
			$_POST['workflow_core_confirmation'] ?? '',
			0,
			'workflow_core_sync_all_confirmation_failed'
		);
		$result = SC_EI_Workflow_Core_Repository::sync_batch( 1000 );
		self::redirect( 0, empty( $result['failed'] ) ? 'workflow_core_all_synchronized' : 'workflow_core_sync_partial' );
	}

	public static function handle_prepare_handoff(): void {
		self::require_cap( 'sc_intake_prepare_workflow_handoffs' );
		$case_id = absint( $_POST['case_id'] ?? 0 );
		check_admin_referer( 'sc_ei_workflow_core_prepare_handoff_' . $case_id );
		$case = SC_EI_Workflow_Core_Repository::find_case( $case_id );
		if ( ! $case ) {
			self::redirect( 0, 'workflow_core_case_not_found' );
		}
		$target = SC_EI_Workflow_Core_Schema::sanitize_target( wp_unslash( $_POST['handoff_target'] ?? '' ) );
		self::require_confirmation(
			'HANDOFF ' . strtoupper( $case['reference'] ) . ' ' . strtoupper( $target ),
			$_POST['workflow_core_confirmation'] ?? '',
			$case_id,
			'workflow_core_handoff_confirmation_failed'
		);
		$classification = SC_EI_Workflow_Core_Schema::sanitize_classification( wp_unslash( $_POST['data_classification'] ?? 'operational_minimum' ) );
		$include_personal = ! empty( $_POST['include_personal_data'] );
		$result = SC_EI_Workflow_Core_Repository::submit_command(
			$case_id,
			'prepare_handoff',
			array(
				'target'                => $target,
				'classification'        => $classification,
				'include_personal_data' => $include_personal,
			),
			wp_unslash( $_POST['handoff_reason'] ?? '' ),
			get_current_user_id()
		);
		self::redirect( $case_id, is_wp_error( $result ) ? $result->get_error_code() : 'workflow_core_handoff_prepared' );
	}

	public static function handle_dispatch(): void {
		self::require_cap( 'sc_intake_dispatch_workflow_outbox' );
		$case_id = absint( $_POST['case_id'] ?? 0 );
		check_admin_referer( 'sc_ei_workflow_core_dispatch_' . $case_id );
		self::require_confirmation(
			'DISPATCH OUTBOX',
			$_POST['workflow_core_confirmation'] ?? '',
			$case_id,
			'workflow_core_dispatch_confirmation_failed'
		);
		$result = SC_EI_Workflow_Core_Repository::process_outbox( 100, true );
		self::redirect( $case_id, empty( $result['failed'] ) ? 'workflow_core_outbox_dispatched' : 'workflow_core_outbox_partial' );
	}

	public static function handle_acknowledge(): void {
		self::require_cap( 'sc_intake_acknowledge_workflow_handoffs' );
		$handoff_id = absint( $_POST['handoff_id'] ?? 0 );
		$case_id = absint( $_POST['case_id'] ?? 0 );
		check_admin_referer( 'sc_ei_workflow_core_acknowledge_' . $handoff_id );
		self::require_confirmation(
			'ACK HANDOFF ' . $handoff_id,
			$_POST['workflow_core_confirmation'] ?? '',
			$case_id,
			'workflow_core_ack_confirmation_failed'
		);
		$result = SC_EI_Workflow_Core_Repository::submit_command(
			$case_id,
			'acknowledge_handoff',
			array(
				'handoff_id' => $handoff_id,
				'receipt'    => wp_unslash( $_POST['handoff_receipt'] ?? '' ),
			),
			'Authorized handoff acknowledgment.',
			get_current_user_id()
		);
		self::redirect( $case_id, is_wp_error( $result ) ? $result->get_error_code() : 'workflow_core_handoff_acknowledged' );
	}

	public static function handle_cancel_handoff(): void {
		self::require_cap( 'sc_intake_manage_workflow_core' );
		$handoff_id = absint( $_POST['handoff_id'] ?? 0 );
		$case_id = absint( $_POST['case_id'] ?? 0 );
		check_admin_referer( 'sc_ei_workflow_core_cancel_' . $handoff_id );
		self::require_confirmation(
			'CANCEL HANDOFF ' . $handoff_id,
			$_POST['workflow_core_confirmation'] ?? '',
			$case_id,
			'workflow_core_cancel_confirmation_failed'
		);
		$result = SC_EI_Workflow_Core_Repository::submit_command(
			$case_id,
			'cancel_handoff',
			array(
				'handoff_id' => $handoff_id,
				'reason'     => wp_unslash( $_POST['handoff_reason'] ?? '' ),
			),
			wp_unslash( $_POST['handoff_reason'] ?? '' ),
			get_current_user_id()
		);
		self::redirect( $case_id, is_wp_error( $result ) ? $result->get_error_code() : 'workflow_core_handoff_canceled' );
	}

	public static function handle_resolve_consistency(): void {
		self::require_cap( 'sc_intake_manage_workflow_core' );
		$case_id = absint( $_POST['case_id'] ?? 0 );
		check_admin_referer( 'sc_ei_workflow_core_resolve_' . $case_id );
		$case = SC_EI_Workflow_Core_Repository::find_case( $case_id );
		if ( ! $case ) {
			self::redirect( 0, 'workflow_core_case_not_found' );
		}
		self::require_confirmation(
			'RESOLVE CASE ' . strtoupper( $case['reference'] ),
			$_POST['workflow_core_confirmation'] ?? '',
			$case_id,
			'workflow_core_resolve_confirmation_failed'
		);
		$result = SC_EI_Workflow_Core_Repository::submit_command(
			$case_id,
			'resolve_consistency',
			array( 'note' => wp_unslash( $_POST['resolution_note'] ?? '' ) ),
			wp_unslash( $_POST['resolution_note'] ?? '' ),
			get_current_user_id()
		);
		self::redirect( $case_id, is_wp_error( $result ) ? $result->get_error_code() : 'workflow_core_consistency_resolved' );
	}

	public static function handle_export_case(): void {
		self::require_cap( 'sc_intake_export_workflow_core' );
		$case_id = absint( $_GET['case'] ?? 0 );
		check_admin_referer( 'sc_ei_workflow_core_export_case_' . $case_id );
		$case = SC_EI_Workflow_Core_Repository::find_case( $case_id );
		if ( ! $case ) {
			wp_die( esc_html__( 'The Workflow Core case was not found.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 404 ) );
		}
		$payload = SC_EI_Workflow_Core_Repository::export_for_inquiry( absint( $case['inquiry_id'] ) );
		SC_EI_Audit_Log::record(
			'workflow_core_case_exported',
			'Authorized staff exported a redacted Workflow Core case record.',
			array( 'case_id' => $case_id, 'schema' => $payload['schema'] ?? '' ),
			absint( $case['inquiry_id'] ),
			null,
			get_current_user_id()
		);
		self::download_json( 'workflow-core-' . strtolower( $case['reference'] ) . '.json', $payload );
	}

	public static function handle_export_handoff(): void {
		self::require_cap( 'sc_intake_export_workflow_core' );
		$handoff_id = absint( $_GET['handoff'] ?? 0 );
		check_admin_referer( 'sc_ei_workflow_core_export_handoff_' . $handoff_id );
		$handoff = SC_EI_Workflow_Core_Repository::find_handoff( $handoff_id );
		if ( ! $handoff ) {
			wp_die( esc_html__( 'The Workflow Core handoff was not found.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 404 ) );
		}
		if ( 'internal_private' === $handoff['data_classification'] ) {
			self::require_cap( 'sc_intake_export_workflow_core_private' );
		}
		if ( ! SC_EI_Workflow_Core_Contract::verify(
			(string) $handoff['payload_json'],
			(string) $handoff['target'],
			(string) $handoff['content_hash'],
			(string) $handoff['signature']
		) ) {
			wp_die( esc_html__( 'The Workflow Core handoff failed its signature check.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 409 ) );
		}
		SC_EI_Audit_Log::record(
			'workflow_core_handoff_exported',
			'Authorized staff exported a signed Workflow Core handoff package.',
			array(
				'handoff_id'     => $handoff_id,
				'target'         => $handoff['target'],
				'classification' => $handoff['data_classification'],
				'content_hash'   => $handoff['content_hash'],
			),
			absint( $handoff['inquiry_id'] ),
			null,
			get_current_user_id()
		);
		$package = json_decode( (string) $handoff['payload_json'], true ) ?: array();
		$package['_integrity'] = array(
			'content_hash' => $handoff['content_hash'],
			'signature'    => $handoff['signature'],
			'verified'     => true,
		);
		self::download_json( 'workflow-handoff-' . $handoff['target'] . '-' . $handoff['public_id'] . '.json', $package );
	}

	public static function handle_save_settings(): void {
		self::require_cap( 'sc_intake_manage_workflow_core' );
		check_admin_referer( 'sc_ei_workflow_core_save_settings' );
		self::require_confirmation(
			'SAVE WORKFLOW CORE SETTINGS',
			$_POST['workflow_core_confirmation'] ?? '',
			0,
			'workflow_core_settings_confirmation_failed'
		);
		$current = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() );
		$raw = isset( $_POST['workflow_core_settings'] ) ? (array) wp_unslash( $_POST['workflow_core_settings'] ) : array();
		$updates = array(
			'workflow_core_enabled'                => empty( $raw['workflow_core_enabled'] ) ? 0 : 1,
			'workflow_core_auto_sync_on_audit'     => empty( $raw['workflow_core_auto_sync_on_audit'] ) ? 0 : 1,
			'workflow_core_sync_interval_minutes'  => max( 15, min( 1440, absint( $raw['workflow_core_sync_interval_minutes'] ?? 60 ) ) ),
			'workflow_core_stale_after_hours'      => max( 1, min( 720, absint( $raw['workflow_core_stale_after_hours'] ?? 24 ) ) ),
			'workflow_core_outbox_enabled'         => empty( $raw['workflow_core_outbox_enabled'] ) ? 0 : 1,
			'workflow_core_outbox_batch_limit'     => max( 1, min( 250, absint( $raw['workflow_core_outbox_batch_limit'] ?? 25 ) ) ),
			'workflow_core_outbox_max_attempts'    => max( 1, min( 20, absint( $raw['workflow_core_outbox_max_attempts'] ?? 6 ) ) ),
			'workflow_core_handoff_expiry_days'    => max( 1, min( 365, absint( $raw['workflow_core_handoff_expiry_days'] ?? 30 ) ) ),
			'workflow_core_default_classification'=> SC_EI_Workflow_Core_Schema::sanitize_classification( (string) ( $raw['workflow_core_default_classification'] ?? 'operational_minimum' ) ),
			'workflow_core_require_typed_commands' => 1,
			'workflow_core_require_handoff_signature'=> 1,
			'workflow_core_include_personal_data_default'=> 0,
			'workflow_core_no_auto_acceptance'     => 1,
			'workflow_core_no_auto_fit_decision'   => 1,
			'workflow_core_no_auto_proposal'       => 1,
			'workflow_core_no_auto_contract'       => 1,
			'workflow_core_no_auto_activation'     => 1,
			'workflow_core_no_auto_external_delivery'=> 1,
			'workflow_core_no_unverified_inbound_commands'=> 1,
		);
		update_option( 'sc_ei_settings', array_merge( $current, $updates ), false );
		SC_EI_Workflow_Core_Repository::unschedule();
		SC_EI_Workflow_Core_Repository::schedule();
		self::redirect( 0, 'workflow_core_settings_saved' );
	}

	private static function require_cap( string $capability ): void {
		if ( ! current_user_can( $capability ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this Workflow Core action.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}
	}

	private static function require_confirmation( string $expected, $provided, int $case_id, string $error ): void {
		$provided = strtoupper( trim( sanitize_text_field( wp_unslash( (string) $provided ) ) ) );
		if ( ! hash_equals( $expected, $provided ) ) {
			self::redirect( $case_id, $error );
		}
	}

	private static function redirect( int $case_id, string $message ): void {
		wp_safe_redirect( self::url( $case_id, array( 'sc_ei_msg' => sanitize_key( $message ) ) ), 303 );
		exit;
	}

	private static function download_json( string $filename, array $payload ): void {
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		exit;
	}
}
