<?php
/**
 * Administrative Teams scheduling and proposal workflow.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Workflow_Admin {

	public static function register(): void {
		add_action( 'admin_post_sc_ei_create_meeting_offer', array( __CLASS__, 'handle_create_meeting' ) );
		add_action( 'admin_post_sc_ei_publish_meeting_offer', array( __CLASS__, 'handle_publish_meeting' ) );
		add_action( 'admin_post_sc_ei_finalize_meeting', array( __CLASS__, 'handle_finalize_meeting' ) );
		add_action( 'admin_post_sc_ei_change_meeting_status', array( __CLASS__, 'handle_meeting_status' ) );
		add_action( 'admin_post_sc_ei_create_proposal', array( __CLASS__, 'handle_create_proposal' ) );
		add_action( 'admin_post_sc_ei_add_proposal_version', array( __CLASS__, 'handle_add_proposal_version' ) );
		add_action( 'admin_post_sc_ei_publish_proposal', array( __CLASS__, 'handle_publish_proposal' ) );
		add_action( 'admin_post_sc_ei_change_proposal_status', array( __CLASS__, 'handle_proposal_status' ) );
		add_action( 'admin_post_sc_ei_export_workflow', array( __CLASS__, 'handle_export' ) );
	}

	public static function submenu(): void {
		add_submenu_page(
			'sc-engagement-intake',
			__( 'Teams Scheduling and Proposals', 'sustainable-catalyst-engagement-intake' ),
			__( 'Teams & Proposals', 'sustainable-catalyst-engagement-intake' ),
			'sc_intake_view_workflow',
			'sc-engagement-intake-workflow',
			array( __CLASS__, 'page' )
		);
	}

	public static function page(): void {
		if ( ! current_user_can( 'sc_intake_view_workflow' ) ) {
			wp_die( esc_html__( 'You do not have permission to view Teams scheduling and proposals.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}
		$inquiry_id = isset( $_GET['inquiry'] ) ? absint( $_GET['inquiry'] ) : 0;
		$message = isset( $_GET['sc_ei_msg'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_msg'] ) ) : '';
		$metrics = SC_EI_Workflow_Repository::metrics();
		$inquiry = $inquiry_id ? SC_EI_Inquiry_Repository::find( $inquiry_id ) : null;
		$meetings = $inquiry ? SC_EI_Workflow_Repository::meeting_offers_for_inquiry( $inquiry_id, false ) : array();
		$proposals = $inquiry ? SC_EI_Workflow_Repository::proposals_for_inquiry( $inquiry_id, false ) : array();
		$events = $inquiry ? SC_EI_Workflow_Repository::events_for_inquiry( $inquiry_id, 500 ) : array();
		$settings = SC_EI_Workflow_Repository::settings();
		$graph_settings = SC_EI_Graph_Repository::settings();
		$graph_credentials = SC_EI_Graph_Credentials::public_status();
		$graph_circuit = SC_EI_Graph_Client::circuit_status();
		$graph_health = SC_EI_Graph_Repository::last_health();
		$graph_operations = array();
		foreach ( $meetings as $meeting_record ) {
			$graph_operations[ $meeting_record['id'] ] = SC_EI_Graph_Repository::operations_for_meeting( absint( $meeting_record['id'] ), 50 );
		}
		include SC_EI_DIR . 'admin/views/teams-proposals.php';
	}

	public static function url( int $inquiry_id = 0, array $args = array() ): string {
		$query = array_merge( array( 'page' => 'sc-engagement-intake-workflow' ), $args );
		if ( $inquiry_id ) {
			$query['inquiry'] = $inquiry_id;
		}
		return add_query_arg( $query, admin_url( 'admin.php' ) );
	}

	public static function handle_create_meeting(): void {
		self::require_cap( 'sc_intake_create_meeting_offers' );
		check_admin_referer( 'sc_ei_create_meeting_offer' );
		$inquiry_id = absint( $_POST['inquiry_id'] ?? 0 );
		$publish = ! empty( $_POST['publish_now'] );
		if ( $publish ) {
			self::require_cap( 'sc_intake_publish_meeting_offers' );
			$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
			$expected = 'PUBLISH ' . strtoupper( (string) ( $inquiry['reference'] ?? '' ) );
			self::require_confirmation( $expected, $_POST['publish_confirmation'] ?? '', $inquiry_id, 'workflow_publish_confirmation_failed' );
		}
		$result = SC_EI_Workflow_Repository::create_meeting_offer(
			$inquiry_id,
			array(
				'title'            => wp_unslash( $_POST['meeting_title'] ?? '' ),
				'meeting_type'     => wp_unslash( $_POST['meeting_type'] ?? 'other' ),
				'purpose'          => wp_unslash( $_POST['meeting_purpose'] ?? '' ),
				'duration_minutes' => absint( $_POST['duration_minutes'] ?? 30 ),
				'timezone'         => wp_unslash( $_POST['meeting_timezone'] ?? '' ),
				'slots'            => isset( $_POST['meeting_slots'] ) ? (array) wp_unslash( $_POST['meeting_slots'] ) : array(),
				'teams_url'        => wp_unslash( $_POST['teams_url'] ?? '' ),
				'expires_at'       => wp_unslash( $_POST['meeting_expires_at'] ?? '' ),
				'admin_note'       => wp_unslash( $_POST['meeting_admin_note'] ?? '' ),
			),
			get_current_user_id(),
			$publish
		);
		self::redirect( $inquiry_id, is_wp_error( $result ) ? $result->get_error_code() : ( $publish ? 'meeting_offer_published' : 'meeting_draft_created' ) );
	}

	public static function handle_publish_meeting(): void {
		self::require_cap( 'sc_intake_publish_meeting_offers' );
		$id = absint( $_POST['meeting_offer_id'] ?? 0 );
		check_admin_referer( 'sc_ei_publish_meeting_offer_' . $id );
		$offer = SC_EI_Workflow_Repository::find_meeting_offer( $id );
		if ( ! $offer ) {
			self::redirect( 0, 'workflow_meeting_not_found' );
		}
		self::require_confirmation( 'PUBLISH ' . strtoupper( $offer['offer_number'] ), $_POST['publish_confirmation'] ?? '', absint( $offer['inquiry_id'] ), 'workflow_publish_confirmation_failed' );
		$result = SC_EI_Workflow_Repository::publish_meeting_offer( $id, get_current_user_id() );
		self::redirect( absint( $offer['inquiry_id'] ), is_wp_error( $result ) ? $result->get_error_code() : 'meeting_offer_published' );
	}

	public static function handle_finalize_meeting(): void {
		self::require_cap( 'sc_intake_finalize_meetings' );
		$id = absint( $_POST['meeting_offer_id'] ?? 0 );
		check_admin_referer( 'sc_ei_finalize_meeting_' . $id );
		$offer = SC_EI_Workflow_Repository::find_meeting_offer( $id );
		if ( ! $offer ) {
			self::redirect( 0, 'workflow_meeting_not_found' );
		}
		self::require_confirmation( 'SCHEDULE ' . strtoupper( $offer['offer_number'] ), $_POST['schedule_confirmation'] ?? '', absint( $offer['inquiry_id'] ), 'workflow_schedule_confirmation_failed' );
		$result = SC_EI_Workflow_Repository::finalize_meeting(
			$id,
			wp_unslash( $_POST['teams_url'] ?? '' ),
			get_current_user_id()
		);
		self::redirect( absint( $offer['inquiry_id'] ), is_wp_error( $result ) ? $result->get_error_code() : 'meeting_finalized' );
	}

	public static function handle_meeting_status(): void {
		self::require_cap( 'sc_intake_finalize_meetings' );
		$id = absint( $_POST['meeting_offer_id'] ?? 0 );
		check_admin_referer( 'sc_ei_change_meeting_status_' . $id );
		$offer = SC_EI_Workflow_Repository::find_meeting_offer( $id );
		if ( ! $offer ) {
			self::redirect( 0, 'workflow_meeting_not_found' );
		}
		$status = sanitize_key( wp_unslash( $_POST['meeting_status'] ?? '' ) );
		$verb = 'completed' === $status ? 'COMPLETE ' : 'CANCEL ';
		self::require_confirmation( $verb . strtoupper( $offer['offer_number'] ), $_POST['meeting_confirmation'] ?? '', absint( $offer['inquiry_id'] ), 'workflow_meeting_confirmation_failed' );
		$result = SC_EI_Workflow_Repository::change_meeting_status(
			$id,
			$status,
			wp_unslash( $_POST['meeting_reason'] ?? '' ),
			get_current_user_id()
		);
		self::redirect( absint( $offer['inquiry_id'] ), is_wp_error( $result ) ? $result->get_error_code() : 'meeting_status_updated' );
	}

	public static function handle_create_proposal(): void {
		self::require_cap( 'sc_intake_create_proposals' );
		check_admin_referer( 'sc_ei_create_proposal' );
		$inquiry_id = absint( $_POST['inquiry_id'] ?? 0 );
		$publish = ! empty( $_POST['publish_now'] );
		if ( $publish ) {
			self::require_cap( 'sc_intake_publish_proposals' );
			$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
			self::require_confirmation( 'PUBLISH ' . strtoupper( (string) ( $inquiry['reference'] ?? '' ) ), $_POST['publish_confirmation'] ?? '', $inquiry_id, 'workflow_publish_confirmation_failed' );
		}
		$result = SC_EI_Workflow_Repository::create_proposal(
			$inquiry_id,
			self::proposal_input( $_POST ),
			get_current_user_id(),
			$publish
		);
		self::redirect( $inquiry_id, is_wp_error( $result ) ? $result->get_error_code() : ( $publish ? 'proposal_published' : 'proposal_draft_created' ) );
	}

	public static function handle_add_proposal_version(): void {
		self::require_cap( 'sc_intake_create_proposals' );
		$id = absint( $_POST['proposal_id'] ?? 0 );
		check_admin_referer( 'sc_ei_add_proposal_version_' . $id );
		$proposal = SC_EI_Workflow_Repository::find_proposal( $id );
		if ( ! $proposal ) {
			self::redirect( 0, 'workflow_proposal_not_found' );
		}
		$result = SC_EI_Workflow_Repository::add_proposal_version( $id, self::proposal_input( $_POST ), get_current_user_id() );
		self::redirect( absint( $proposal['inquiry_id'] ), is_wp_error( $result ) ? $result->get_error_code() : 'proposal_version_created' );
	}

	public static function handle_publish_proposal(): void {
		self::require_cap( 'sc_intake_publish_proposals' );
		$id = absint( $_POST['proposal_id'] ?? 0 );
		check_admin_referer( 'sc_ei_publish_proposal_' . $id );
		$proposal = SC_EI_Workflow_Repository::find_proposal( $id );
		if ( ! $proposal ) {
			self::redirect( 0, 'workflow_proposal_not_found' );
		}
		self::require_confirmation( 'PUBLISH ' . strtoupper( $proposal['proposal_number'] ), $_POST['publish_confirmation'] ?? '', absint( $proposal['inquiry_id'] ), 'workflow_publish_confirmation_failed' );
		$result = SC_EI_Workflow_Repository::publish_proposal( $id, get_current_user_id() );
		self::redirect( absint( $proposal['inquiry_id'] ), is_wp_error( $result ) ? $result->get_error_code() : 'proposal_published' );
	}

	public static function handle_proposal_status(): void {
		$id = absint( $_POST['proposal_id'] ?? 0 );
		$proposal = SC_EI_Workflow_Repository::find_proposal( $id );
		if ( ! $proposal ) {
			self::redirect( 0, 'workflow_proposal_not_found' );
		}
		$status = sanitize_key( wp_unslash( $_POST['proposal_status'] ?? '' ) );
		$cap = 'contracted' === $status ? 'sc_intake_record_contracts' : 'sc_intake_publish_proposals';
		self::require_cap( $cap );
		check_admin_referer( 'sc_ei_change_proposal_status_' . $id );
		$verb = 'contracted' === $status ? 'CONTRACT ' : 'WITHDRAW ';
		self::require_confirmation( $verb . strtoupper( $proposal['proposal_number'] ), $_POST['proposal_confirmation'] ?? '', absint( $proposal['inquiry_id'] ), 'workflow_proposal_confirmation_failed' );
		$result = SC_EI_Workflow_Repository::change_proposal_status(
			$id,
			$status,
			wp_unslash( $_POST['proposal_note'] ?? '' ),
			wp_unslash( $_POST['contract_reference'] ?? '' ),
			get_current_user_id()
		);
		self::redirect( absint( $proposal['inquiry_id'] ), is_wp_error( $result ) ? $result->get_error_code() : 'proposal_status_updated' );
	}

	public static function handle_export(): void {
		self::require_cap( 'sc_intake_export_workflow' );
		$inquiry_id = absint( $_GET['inquiry'] ?? 0 );
		check_admin_referer( 'sc_ei_export_workflow_' . $inquiry_id );
		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $inquiry ) {
			wp_die( esc_html__( 'The workflow export could not be generated.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 404 ) );
		}
		$packet = array(
			'schema'          => 'sc-engagement-intake-teams-proposal-workflow/1.0',
			'generated_at'    => current_time( 'mysql', true ),
			'workflow_schema' => SC_EI_WORKFLOW_SCHEMA_VERSION,
			'boundaries'      => array(
				'automatic_calendar' => false,
				'automatic_contract' => false,
				'automatic_payment'  => false,
				'portal_acceptance_is_signature' => false,
			),
			'inquiry'         => $inquiry,
			'workflow'        => SC_EI_Workflow_Repository::export_for_inquiry( $inquiry_id ),
		);
		SC_EI_Audit_Log::record( 'workflow_exported', 'Authorized user exported Teams scheduling and proposal workflow records.', array(), $inquiry_id, null, get_current_user_id() );
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="teams-proposal-workflow-' . sanitize_file_name( $inquiry['reference'] ) . '-' . gmdate( 'Y-m-d-His' ) . '.json"' );
		header( 'X-Content-Type-Options: nosniff' );
		echo wp_json_encode( $packet, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		exit;
	}

	private static function proposal_input( array $source ): array {
		return array(
			'title'              => wp_unslash( $source['proposal_title'] ?? '' ),
			'executive_summary'  => wp_unslash( $source['executive_summary'] ?? '' ),
			'scope'              => wp_unslash( $source['proposal_scope'] ?? '' ),
			'deliverables'       => wp_unslash( $source['proposal_deliverables'] ?? '' ),
			'exclusions'         => wp_unslash( $source['proposal_exclusions'] ?? '' ),
			'assumptions'        => wp_unslash( $source['proposal_assumptions'] ?? '' ),
			'timeline_text'      => wp_unslash( $source['timeline_text'] ?? '' ),
			'fee_summary'        => wp_unslash( $source['fee_summary'] ?? '' ),
			'payment_terms'      => wp_unslash( $source['payment_terms'] ?? '' ),
			'legal_terms'        => wp_unslash( $source['legal_terms'] ?? '' ),
			'version_note'       => wp_unslash( $source['version_note'] ?? '' ),
			'currency'           => wp_unslash( $source['proposal_currency'] ?? 'USD' ),
			'total'              => wp_unslash( $source['proposal_total'] ?? '0' ),
			'expires_at'         => wp_unslash( $source['proposal_expires_at'] ?? '' ),
		);
	}

	private static function require_confirmation( string $expected, $provided, int $inquiry_id, string $error ): void {
		$provided = strtoupper( trim( sanitize_text_field( wp_unslash( $provided ) ) ) );
		if ( ! hash_equals( $expected, $provided ) ) {
			self::redirect( $inquiry_id, $error );
		}
	}

	private static function require_cap( string $capability ): void {
		if ( ! current_user_can( $capability ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this workflow action.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}
	}

	private static function redirect( int $inquiry_id, string $message ): void {
		wp_safe_redirect( self::url( $inquiry_id, array( 'sc_ei_msg' => sanitize_key( $message ) ) ), 303 );
		exit;
	}
}
