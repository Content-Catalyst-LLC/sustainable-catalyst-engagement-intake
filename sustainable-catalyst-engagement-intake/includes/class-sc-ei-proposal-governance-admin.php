<?php
/**
 * Administrative proposal governance workspace.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Proposal_Governance_Admin {

	public static function register(): void {
		add_action( 'admin_post_sc_ei_create_sow', array( __CLASS__, 'handle_create_sow' ) );
		add_action( 'admin_post_sc_ei_add_sow_version', array( __CLASS__, 'handle_add_sow_version' ) );
		add_action( 'admin_post_sc_ei_approve_sow', array( __CLASS__, 'handle_approve_sow' ) );
		add_action( 'admin_post_sc_ei_create_change_request', array( __CLASS__, 'handle_create_change_request' ) );
		add_action( 'admin_post_sc_ei_transition_change_request', array( __CLASS__, 'handle_transition_change_request' ) );
		add_action( 'admin_post_sc_ei_convert_proposal_engagement', array( __CLASS__, 'handle_convert_engagement' ) );
	}

	public static function submenu(): void {
		add_submenu_page(
			'sc-engagement-intake',
			__( 'Proposals, Statements of Work, and Approvals', 'sustainable-catalyst-engagement-intake' ),
			__( 'Proposal Governance', 'sustainable-catalyst-engagement-intake' ),
			'sc_intake_view_proposal_governance',
			'sc-engagement-intake-proposal-governance',
			array( __CLASS__, 'page' )
		);
	}

	public static function page(): void {
		self::require_cap( 'sc_intake_view_proposal_governance' );
		$inquiry_id = absint( $_GET['inquiry'] ?? 0 );
		$message = sanitize_key( wp_unslash( $_GET['sc_ei_msg'] ?? '' ) );
		$inquiry = $inquiry_id ? SC_EI_Inquiry_Repository::find( $inquiry_id ) : null;
		$proposals = $inquiry ? SC_EI_Workflow_Repository::proposals_for_inquiry( $inquiry_id, false ) : array();
		$sows = $inquiry ? SC_EI_Proposal_Governance_Repository::sows_for_inquiry( $inquiry_id, false ) : array();
		$changes = $inquiry ? SC_EI_Proposal_Governance_Repository::change_requests_for_inquiry( $inquiry_id ) : array();
		$approvals = $inquiry ? SC_EI_Proposal_Governance_Repository::approvals_for_inquiry( $inquiry_id ) : array();
		$engagements = $inquiry ? SC_EI_Engagement_Repository::for_inquiry( $inquiry_id, false ) : array();
		$metrics = SC_EI_Proposal_Governance_Repository::metrics();
		include SC_EI_DIR . 'admin/views/proposal-governance.php';
	}

	public static function url( int $inquiry_id = 0, array $args = array() ): string {
		$query = array_merge( array( 'page' => 'sc-engagement-intake-proposal-governance' ), $args );
		if ( $inquiry_id ) {
			$query['inquiry'] = $inquiry_id;
		}
		return add_query_arg( $query, admin_url( 'admin.php' ) );
	}

	public static function handle_create_sow(): void {
		self::require_cap( 'sc_intake_manage_statements_of_work' );
		$proposal_id = absint( $_POST['proposal_id'] ?? 0 );
		check_admin_referer( 'sc_ei_create_sow_' . $proposal_id );
		$proposal = SC_EI_Workflow_Repository::find_proposal( $proposal_id );
		$result = SC_EI_Proposal_Governance_Repository::create_sow_from_proposal( $proposal_id, self::sow_input( $_POST ), get_current_user_id() );
		self::redirect( absint( $proposal['inquiry_id'] ?? 0 ), is_wp_error( $result ) ? $result->get_error_code() : 'proposal_sow_created' );
	}

	public static function handle_add_sow_version(): void {
		self::require_cap( 'sc_intake_manage_statements_of_work' );
		$sow_id = absint( $_POST['sow_id'] ?? 0 );
		check_admin_referer( 'sc_ei_add_sow_version_' . $sow_id );
		$sow = SC_EI_Proposal_Governance_Repository::find_sow( $sow_id );
		$result = SC_EI_Proposal_Governance_Repository::add_sow_version( $sow_id, self::sow_input( $_POST ), get_current_user_id() );
		self::redirect( absint( $sow['inquiry_id'] ?? 0 ), is_wp_error( $result ) ? $result->get_error_code() : 'proposal_sow_version_created' );
	}

	public static function handle_approve_sow(): void {
		self::require_cap( 'sc_intake_approve_statements_of_work' );
		$sow_id = absint( $_POST['sow_id'] ?? 0 );
		check_admin_referer( 'sc_ei_approve_sow_' . $sow_id );
		$sow = SC_EI_Proposal_Governance_Repository::find_sow( $sow_id );
		$result = SC_EI_Proposal_Governance_Repository::approve_sow( $sow_id, wp_unslash( $_POST['confirmation'] ?? '' ), get_current_user_id() );
		self::redirect( absint( $sow['inquiry_id'] ?? 0 ), is_wp_error( $result ) ? $result->get_error_code() : 'proposal_sow_approved' );
	}

	public static function handle_create_change_request(): void {
		self::require_cap( 'sc_intake_manage_change_requests' );
		$inquiry_id = absint( $_POST['inquiry_id'] ?? 0 );
		check_admin_referer( 'sc_ei_create_change_request_' . $inquiry_id );
		$result = SC_EI_Proposal_Governance_Repository::create_change_request(
			$inquiry_id,
			array(
				'proposal_id'         => absint( $_POST['proposal_id'] ?? 0 ),
				'proposal_version_id' => absint( $_POST['proposal_version_id'] ?? 0 ),
				'sow_id'              => absint( $_POST['sow_id'] ?? 0 ),
				'sow_version_id'      => absint( $_POST['sow_version_id'] ?? 0 ),
				'engagement_id'       => absint( $_POST['engagement_id'] ?? 0 ),
				'request_summary'     => wp_unslash( $_POST['request_summary'] ?? '' ),
				'reason'              => wp_unslash( $_POST['reason'] ?? '' ),
				'scope_impact'        => wp_unslash( $_POST['scope_impact'] ?? '' ),
				'timeline_impact'     => wp_unslash( $_POST['timeline_impact'] ?? '' ),
				'fee_impact'          => wp_unslash( $_POST['fee_impact'] ?? '' ),
				'currency'            => wp_unslash( $_POST['currency'] ?? 'USD' ),
			),
			'staff',
			get_current_user_id()
		);
		self::redirect( $inquiry_id, is_wp_error( $result ) ? $result->get_error_code() : 'proposal_change_created' );
	}

	public static function handle_transition_change_request(): void {
		self::require_cap( 'sc_intake_approve_change_requests' );
		$id = absint( $_POST['change_request_id'] ?? 0 );
		check_admin_referer( 'sc_ei_transition_change_request_' . $id );
		$request = SC_EI_Proposal_Governance_Repository::find_change_request( $id );
		$result = SC_EI_Proposal_Governance_Repository::transition_change_request(
			$id,
			wp_unslash( $_POST['status'] ?? '' ),
			wp_unslash( $_POST['note'] ?? '' ),
			wp_unslash( $_POST['confirmation'] ?? '' ),
			get_current_user_id()
		);
		self::redirect( absint( $request['inquiry_id'] ?? 0 ), is_wp_error( $result ) ? $result->get_error_code() : 'proposal_change_transitioned' );
	}

	public static function handle_convert_engagement(): void {
		self::require_cap( 'sc_intake_create_engagement_handoffs' );
		$proposal_id = absint( $_POST['proposal_id'] ?? 0 );
		check_admin_referer( 'sc_ei_convert_proposal_engagement_' . $proposal_id );
		$proposal = SC_EI_Workflow_Repository::find_proposal( $proposal_id );
		$result = SC_EI_Proposal_Governance_Repository::convert_to_engagement(
			$proposal_id,
			array(
				'engagement_title'           => wp_unslash( $_POST['engagement_title'] ?? '' ),
				'owner_user_id'              => absint( $_POST['owner_user_id'] ?? 0 ),
				'participant_user_ids'       => (array) ( $_POST['participant_user_ids'] ?? array() ),
				'proposed_start_date'        => wp_unslash( $_POST['proposed_start_date'] ?? '' ),
				'target_end_date'            => wp_unslash( $_POST['target_end_date'] ?? '' ),
				'kickoff_status'             => wp_unslash( $_POST['kickoff_status'] ?? 'not_scheduled' ),
				'onboarding_summary'         => wp_unslash( $_POST['onboarding_summary'] ?? '' ),
				'sender_summary'             => wp_unslash( $_POST['sender_summary'] ?? '' ),
				'internal_notes'             => wp_unslash( $_POST['internal_notes'] ?? '' ),
				'external_project_reference' => wp_unslash( $_POST['external_project_reference'] ?? '' ),
			),
			wp_unslash( $_POST['confirmation'] ?? '' ),
			get_current_user_id()
		);
		self::redirect( absint( $proposal['inquiry_id'] ?? 0 ), is_wp_error( $result ) ? $result->get_error_code() : 'proposal_engagement_converted' );
	}

	private static function sow_input( array $source ): array {
		return array(
			'title'                      => wp_unslash( $source['sow_title'] ?? '' ),
			'purpose_background'         => wp_unslash( $source['purpose_background'] ?? '' ),
			'scope'                      => wp_unslash( $source['sow_scope'] ?? '' ),
			'deliverables'               => wp_unslash( $source['sow_deliverables'] ?? '' ),
			'milestones'                 => wp_unslash( $source['sow_milestones'] ?? '' ),
			'responsibilities'            => wp_unslash( $source['sow_responsibilities'] ?? '' ),
			'dependencies'                => wp_unslash( $source['sow_dependencies'] ?? '' ),
			'acceptance_criteria'         => wp_unslash( $source['acceptance_criteria'] ?? '' ),
			'change_control'              => wp_unslash( $source['change_control'] ?? '' ),
			'communication_expectations'  => wp_unslash( $source['communication_expectations'] ?? '' ),
			'data_handling'               => wp_unslash( $source['data_handling'] ?? '' ),
			'ip_terms'                    => wp_unslash( $source['ip_terms'] ?? '' ),
			'open_source_boundaries'      => wp_unslash( $source['open_source_boundaries'] ?? '' ),
			'fees_payment'                => wp_unslash( $source['fees_payment'] ?? '' ),
			'start_date'                  => wp_unslash( $source['start_date'] ?? '' ),
			'target_end_date'             => wp_unslash( $source['target_end_date'] ?? '' ),
			'termination_conditions'      => wp_unslash( $source['termination_conditions'] ?? '' ),
			'attachment_ids'              => (array) ( $source['attachment_ids'] ?? array() ),
			'version_note'                => wp_unslash( $source['version_note'] ?? '' ),
		);
	}

	private static function require_cap( string $capability ): void {
		if ( ! current_user_can( $capability ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this proposal-governance action.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}
	}

	private static function redirect( int $inquiry_id, string $message ): void {
		wp_safe_redirect( self::url( $inquiry_id, array( 'sc_ei_msg' => sanitize_key( $message ) ) ), 303 );
		exit;
	}
}
