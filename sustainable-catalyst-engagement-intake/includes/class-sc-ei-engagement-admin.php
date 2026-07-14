<?php
/**
 * Engagement handoff administration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Engagement_Admin {

	public static function register(): void {
		add_action( 'admin_post_sc_ei_create_engagement_handoff', array( __CLASS__, 'handle_create' ) );
		add_action( 'admin_post_sc_ei_update_engagement_profile', array( __CLASS__, 'handle_update_profile' ) );
		add_action( 'admin_post_sc_ei_add_engagement_requirement', array( __CLASS__, 'handle_add_requirement' ) );
		add_action( 'admin_post_sc_ei_update_engagement_requirement', array( __CLASS__, 'handle_update_requirement' ) );
		add_action( 'admin_post_sc_ei_mark_engagement_ready', array( __CLASS__, 'handle_mark_ready' ) );
		add_action( 'admin_post_sc_ei_activate_engagement', array( __CLASS__, 'handle_activate' ) );
		add_action( 'admin_post_sc_ei_change_engagement_status', array( __CLASS__, 'handle_change_status' ) );
		add_action( 'admin_post_sc_ei_export_engagement_handoff', array( __CLASS__, 'handle_export' ) );
	}

	public static function submenu(): void {
		add_submenu_page(
			'sc-engagement-intake',
			__( 'Proposal and Engagement Handoff', 'sustainable-catalyst-engagement-intake' ),
			__( 'Engagements', 'sustainable-catalyst-engagement-intake' ),
			'sc_intake_view_engagements',
			'sc-engagement-intake-engagements',
			array( __CLASS__, 'page' )
		);
	}

	public static function page(): void {
		self::require_cap( 'sc_intake_view_engagements' );
		$message = isset( $_GET['sc_ei_msg'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_msg'] ) ) : '';
		$status_filter = isset( $_GET['engagement_status'] ) ? sanitize_key( wp_unslash( $_GET['engagement_status'] ) ) : '';
		$engagement_id = absint( $_GET['engagement'] ?? 0 );
		$inquiry_id = absint( $_GET['inquiry'] ?? 0 );
		$proposal_id = absint( $_GET['proposal'] ?? 0 );

		$engagements = SC_EI_Engagement_Repository::all(
			array(
				'status' => $status_filter,
				'limit'  => 1000,
			)
		);
		$eligible_proposals = SC_EI_Engagement_Repository::eligible_proposals( $inquiry_id );
		$selected = $engagement_id ? SC_EI_Engagement_Repository::find( $engagement_id ) : null;
		if ( ! $selected && $proposal_id ) {
			$selected = SC_EI_Engagement_Repository::find_by_proposal( $proposal_id );
		}
		$snapshot = $selected ? SC_EI_Engagement_Repository::snapshot( absint( $selected['id'] ) ) : null;
		$requirements = $selected ? SC_EI_Engagement_Repository::requirements( absint( $selected['id'] ) ) : array();
		$events = $selected ? SC_EI_Engagement_Repository::events( absint( $selected['id'] ), 500 ) : array();
		$readiness = $selected ? SC_EI_Engagement_Repository::readiness( absint( $selected['id'] ) ) : array();
		$metrics = SC_EI_Engagement_Repository::metrics();
		$users = get_users(
			array(
				'fields'  => array( 'ID', 'display_name', 'user_email' ),
				'orderby' => 'display_name',
				'order'   => 'ASC',
			)
		);
		$settings = SC_EI_Engagement_Repository::settings();
		include SC_EI_DIR . 'admin/views/engagement-handoff.php';
	}

	public static function url( int $engagement_id = 0, array $args = array() ): string {
		$query = array_merge( array( 'page' => 'sc-engagement-intake-engagements' ), $args );
		if ( $engagement_id ) {
			$query['engagement'] = $engagement_id;
		}
		return add_query_arg( $query, admin_url( 'admin.php' ) );
	}

	public static function handle_create(): void {
		self::require_cap( 'sc_intake_create_engagement_handoffs' );
		$proposal_id = absint( $_POST['proposal_id'] ?? 0 );
		check_admin_referer( 'sc_ei_create_engagement_handoff_' . $proposal_id );
		$proposal = SC_EI_Workflow_Repository::find_proposal( $proposal_id, true );
		if ( ! $proposal ) {
			self::redirect( 0, 'engagement_proposal_not_found' );
		}
		self::require_confirmation(
			'HANDOFF ' . strtoupper( $proposal['proposal_number'] ),
			$_POST['engagement_confirmation'] ?? '',
			'engagement_handoff_confirmation_failed',
			0
		);
		$result = SC_EI_Engagement_Repository::create_from_contracted_proposal(
			$proposal_id,
			array(
				'engagement_title'          => wp_unslash( $_POST['engagement_title'] ?? '' ),
				'owner_user_id'             => absint( $_POST['owner_user_id'] ?? 0 ),
				'participant_user_ids'      => (array) ( $_POST['participant_user_ids'] ?? array() ),
				'proposed_start_date'       => wp_unslash( $_POST['proposed_start_date'] ?? '' ),
				'target_end_date'           => wp_unslash( $_POST['target_end_date'] ?? '' ),
				'kickoff_status'            => wp_unslash( $_POST['kickoff_status'] ?? 'not_scheduled' ),
				'kickoff_at'                => wp_unslash( $_POST['kickoff_at'] ?? '' ),
				'onboarding_summary'        => wp_unslash( $_POST['onboarding_summary'] ?? '' ),
				'sender_summary'            => wp_unslash( $_POST['sender_summary'] ?? '' ),
				'internal_notes'            => wp_unslash( $_POST['internal_notes'] ?? '' ),
				'external_project_reference'=> wp_unslash( $_POST['external_project_reference'] ?? '' ),
			),
			get_current_user_id()
		);
		self::redirect(
			is_wp_error( $result ) ? 0 : absint( $result['id'] ),
			is_wp_error( $result ) ? $result->get_error_code() : 'engagement_handoff_created'
		);
	}

	public static function handle_update_profile(): void {
		self::require_cap( 'sc_intake_manage_engagements' );
		$id = absint( $_POST['engagement_id'] ?? 0 );
		check_admin_referer( 'sc_ei_update_engagement_profile_' . $id );
		$result = SC_EI_Engagement_Repository::update_profile(
			$id,
			array(
				'engagement_title'              => wp_unslash( $_POST['engagement_title'] ?? '' ),
				'owner_user_id'                 => absint( $_POST['owner_user_id'] ?? 0 ),
				'participant_user_ids'          => (array) ( $_POST['participant_user_ids'] ?? array() ),
				'proposed_start_date'           => wp_unslash( $_POST['proposed_start_date'] ?? '' ),
				'target_end_date'               => wp_unslash( $_POST['target_end_date'] ?? '' ),
				'kickoff_status'                => wp_unslash( $_POST['kickoff_status'] ?? '' ),
				'kickoff_at'                    => wp_unslash( $_POST['kickoff_at'] ?? '' ),
				'onboarding_summary'            => wp_unslash( $_POST['onboarding_summary'] ?? '' ),
				'sender_summary'                => wp_unslash( $_POST['sender_summary'] ?? '' ),
				'internal_notes'                => wp_unslash( $_POST['internal_notes'] ?? '' ),
				'external_project_reference'    => wp_unslash( $_POST['external_project_reference'] ?? '' ),
				'workbench_handoff_status'      => wp_unslash( $_POST['workbench_handoff_status'] ?? '' ),
				'decision_studio_handoff_status'=> wp_unslash( $_POST['decision_studio_handoff_status'] ?? '' ),
			),
			get_current_user_id()
		);
		self::redirect( $id, is_wp_error( $result ) ? $result->get_error_code() : 'engagement_profile_updated' );
	}

	public static function handle_add_requirement(): void {
		self::require_cap( 'sc_intake_manage_engagements' );
		$id = absint( $_POST['engagement_id'] ?? 0 );
		check_admin_referer( 'sc_ei_add_engagement_requirement_' . $id );
		$result = SC_EI_Engagement_Repository::add_requirement(
			$id,
			array(
				'requirement_key'        => wp_unslash( $_POST['requirement_key'] ?? '' ),
				'requirement_title'      => wp_unslash( $_POST['requirement_title'] ?? '' ),
				'requirement_description'=> wp_unslash( $_POST['requirement_description'] ?? '' ),
				'requirement_category'   => wp_unslash( $_POST['requirement_category'] ?? '' ),
				'is_required'            => ! empty( $_POST['is_required'] ),
				'sender_visible'         => ! empty( $_POST['sender_visible'] ),
				'due_date'               => wp_unslash( $_POST['due_date'] ?? '' ),
				'assigned_user_id'       => absint( $_POST['assigned_user_id'] ?? 0 ),
				'sort_order'             => absint( $_POST['sort_order'] ?? 100 ),
			),
			get_current_user_id()
		);
		self::redirect( $id, is_wp_error( $result ) ? $result->get_error_code() : 'engagement_requirement_added' );
	}

	public static function handle_update_requirement(): void {
		self::require_cap( 'sc_intake_manage_engagements' );
		$requirement_id = absint( $_POST['requirement_id'] ?? 0 );
		$engagement_id = absint( $_POST['engagement_id'] ?? 0 );
		check_admin_referer( 'sc_ei_update_engagement_requirement_' . $requirement_id );
		$result = SC_EI_Engagement_Repository::update_requirement(
			$requirement_id,
			array(
				'requirement_status' => wp_unslash( $_POST['requirement_status'] ?? '' ),
				'is_required'        => ! empty( $_POST['is_required'] ),
				'sender_visible'     => ! empty( $_POST['sender_visible'] ),
				'due_date'           => wp_unslash( $_POST['due_date'] ?? '' ),
				'assigned_user_id'   => absint( $_POST['assigned_user_id'] ?? 0 ),
				'completion_note'    => wp_unslash( $_POST['completion_note'] ?? '' ),
				'evidence_reference' => wp_unslash( $_POST['evidence_reference'] ?? '' ),
			),
			get_current_user_id()
		);
		self::redirect( $engagement_id, is_wp_error( $result ) ? $result->get_error_code() : 'engagement_requirement_updated' );
	}

	public static function handle_mark_ready(): void {
		self::require_cap( 'sc_intake_manage_engagements' );
		$id = absint( $_POST['engagement_id'] ?? 0 );
		check_admin_referer( 'sc_ei_mark_engagement_ready_' . $id );
		$engagement = SC_EI_Engagement_Repository::find( $id );
		if ( ! $engagement ) {
			self::redirect( 0, 'engagement_not_found' );
		}
		self::require_confirmation(
			'READY ' . strtoupper( $engagement['engagement_number'] ),
			$_POST['engagement_confirmation'] ?? '',
			'engagement_ready_confirmation_failed',
			$id
		);
		$result = SC_EI_Engagement_Repository::mark_ready( $id, get_current_user_id() );
		self::redirect( $id, is_wp_error( $result ) ? $result->get_error_code() : 'engagement_marked_ready' );
	}

	public static function handle_activate(): void {
		self::require_cap( 'sc_intake_activate_engagements' );
		$id = absint( $_POST['engagement_id'] ?? 0 );
		check_admin_referer( 'sc_ei_activate_engagement_' . $id );
		$engagement = SC_EI_Engagement_Repository::find( $id );
		if ( ! $engagement ) {
			self::redirect( 0, 'engagement_not_found' );
		}
		self::require_confirmation(
			'ACTIVATE ' . strtoupper( $engagement['engagement_number'] ),
			$_POST['engagement_confirmation'] ?? '',
			'engagement_activation_confirmation_failed',
			$id
		);
		$result = SC_EI_Engagement_Repository::activate( $id, get_current_user_id() );
		self::redirect( $id, is_wp_error( $result ) ? $result->get_error_code() : 'engagement_activated' );
	}

	public static function handle_change_status(): void {
		self::require_cap( 'sc_intake_complete_engagements' );
		$id = absint( $_POST['engagement_id'] ?? 0 );
		check_admin_referer( 'sc_ei_change_engagement_status_' . $id );
		$engagement = SC_EI_Engagement_Repository::find( $id );
		if ( ! $engagement ) {
			self::redirect( 0, 'engagement_not_found' );
		}
		$status = sanitize_key( wp_unslash( $_POST['engagement_status'] ?? '' ) );
		$verb = array(
			'paused'    => 'PAUSE ',
			'active'    => 'RESUME ',
			'completed' => 'COMPLETE ',
			'canceled'  => 'CANCEL ',
		)[ $status ] ?? '';
		self::require_confirmation(
			$verb . strtoupper( $engagement['engagement_number'] ),
			$_POST['engagement_confirmation'] ?? '',
			'engagement_transition_confirmation_failed',
			$id
		);
		$result = SC_EI_Engagement_Repository::change_status(
			$id,
			$status,
			wp_unslash( $_POST['engagement_note'] ?? '' ),
			get_current_user_id()
		);
		self::redirect( $id, is_wp_error( $result ) ? $result->get_error_code() : 'engagement_status_updated' );
	}

	public static function handle_export(): void {
		self::require_cap( 'sc_intake_export_engagements' );
		$id = absint( $_GET['engagement'] ?? 0 );
		check_admin_referer( 'sc_ei_export_engagement_handoff_' . $id );
		$engagement = SC_EI_Engagement_Repository::find( $id );
		if ( ! $engagement ) {
			wp_die( esc_html__( 'The engagement could not be found.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 404 ) );
		}
		$payload = SC_EI_Engagement_Repository::handoff_package( $id );
		SC_EI_Audit_Log::record(
			'engagement_exported',
			'Authorized staff exported a private engagement handoff package.',
			array(
				'engagement_number' => $engagement['engagement_number'],
				'schema'            => $payload['schema'] ?? '',
			),
			absint( $engagement['inquiry_id'] ),
			null,
			get_current_user_id()
		);
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="engagement-handoff-' . sanitize_file_name( strtolower( $engagement['engagement_number'] ) ) . '.json"' );
		echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		exit;
	}

	private static function require_cap( string $capability ): void {
		if ( ! current_user_can( $capability ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this engagement action.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}
	}

	private static function require_confirmation( string $expected, $provided, string $error, int $engagement_id ): void {
		$provided = strtoupper( trim( sanitize_text_field( wp_unslash( (string) $provided ) ) ) );
		if ( ! hash_equals( $expected, $provided ) ) {
			self::redirect( $engagement_id, $error );
		}
	}

	private static function redirect( int $engagement_id, string $message ): void {
		wp_safe_redirect( self::url( $engagement_id, array( 'sc_ei_msg' => sanitize_key( $message ) ) ), 303 );
		exit;
	}
}
