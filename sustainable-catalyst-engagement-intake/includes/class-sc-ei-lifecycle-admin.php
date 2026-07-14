<?php
/**
 * Advisory lifecycle administration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Lifecycle_Admin {

	public static function register(): void {
		add_action( 'admin_post_sc_ei_lifecycle_transition', array( __CLASS__, 'handle_transition' ) );
		add_action( 'admin_post_sc_ei_lifecycle_workspace', array( __CLASS__, 'handle_workspace' ) );
		add_action( 'admin_post_sc_ei_lifecycle_qualification', array( __CLASS__, 'handle_qualification' ) );
		add_action( 'admin_post_sc_ei_lifecycle_add_note', array( __CLASS__, 'handle_add_note' ) );
		add_action( 'admin_post_sc_ei_lifecycle_add_task', array( __CLASS__, 'handle_add_task' ) );
		add_action( 'admin_post_sc_ei_lifecycle_update_task', array( __CLASS__, 'handle_update_task' ) );
	}

	public static function submenu(): void {
		add_submenu_page(
			'sc-engagement-intake',
			__( 'Advisory Operations and Engagement Lifecycle', 'sustainable-catalyst-engagement-intake' ),
			__( 'Advisory Lifecycle', 'sustainable-catalyst-engagement-intake' ),
			'sc_intake_view_lifecycle',
			'sc-engagement-intake-lifecycle',
			array( __CLASS__, 'page' )
		);
	}

	public static function page(): void {
		self::require_cap( 'sc_intake_view_lifecycle' );
		$stage_filter = isset( $_GET['lifecycle_stage'] ) ? sanitize_key( wp_unslash( $_GET['lifecycle_stage'] ) ) : '';
		$priority_filter = isset( $_GET['lifecycle_priority'] ) ? sanitize_key( wp_unslash( $_GET['lifecycle_priority'] ) ) : '';
		$search = isset( $_GET['lifecycle_search'] ) ? sanitize_text_field( wp_unslash( $_GET['lifecycle_search'] ) ) : '';
		$inquiry_id = absint( $_GET['inquiry'] ?? 0 );
		$message = isset( $_GET['sc_ei_msg'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_msg'] ) ) : '';
		$items = SC_EI_Lifecycle_Repository::query(
			array(
				'stage'    => $stage_filter,
				'priority' => $priority_filter,
				'search'   => $search,
				'limit'    => 500,
			)
		);
		$selected = $inquiry_id ? SC_EI_Inquiry_Repository::find( $inquiry_id ) : ( $items[0] ?? null );
		$notes = $selected ? SC_EI_Lifecycle_Repository::notes( absint( $selected['id'] ) ) : array();
		$tasks = $selected ? SC_EI_Lifecycle_Repository::tasks( absint( $selected['id'] ) ) : array();
		$events = $selected ? SC_EI_Lifecycle_Repository::events( absint( $selected['id'] ) ) : array();
		$meeting_offers = $selected ? SC_EI_Workflow_Repository::meeting_offers_for_inquiry( absint( $selected['id'] ), false ) : array();
		$proposals = $selected ? SC_EI_Workflow_Repository::proposals_for_inquiry( absint( $selected['id'] ), false ) : array();
		$engagements = $selected ? SC_EI_Engagement_Repository::for_inquiry( absint( $selected['id'] ), false ) : array();
		$qualification = $selected ? json_decode( (string) ( $selected['qualification_json'] ?? '' ), true ) : array();
		$qualification = is_array( $qualification ) ? $qualification : array();
		$metrics = SC_EI_Lifecycle_Repository::metrics();
		$users = get_users(
			array(
				'fields'  => array( 'ID', 'display_name', 'user_email' ),
				'orderby' => 'display_name',
				'order'   => 'ASC',
			)
		);
		include SC_EI_DIR . 'admin/views/advisory-lifecycle.php';
	}

	public static function url( int $inquiry_id = 0, array $args = array() ): string {
		$query = array_merge( array( 'page' => 'sc-engagement-intake-lifecycle' ), $args );
		if ( $inquiry_id ) {
			$query['inquiry'] = $inquiry_id;
		}
		return add_query_arg( $query, admin_url( 'admin.php' ) );
	}

	public static function handle_transition(): void {
		self::require_cap( 'sc_intake_manage_lifecycle' );
		$id = absint( $_POST['inquiry_id'] ?? 0 );
		check_admin_referer( 'sc_ei_lifecycle_transition_' . $id );
		$inquiry = SC_EI_Inquiry_Repository::find( $id );
		if ( ! $inquiry ) {
			self::redirect( 0, 'lifecycle_inquiry_not_found' );
		}
		$stage = SC_EI_Lifecycle_Schema::sanitize_stage( (string) wp_unslash( $_POST['lifecycle_stage'] ?? '' ) );
		$expected = 'MOVE ' . strtoupper( (string) $inquiry['reference'] ) . ' TO ' . strtoupper( $stage );
		$provided = strtoupper( trim( sanitize_text_field( (string) wp_unslash( $_POST['lifecycle_confirmation'] ?? '' ) ) ) );
		if ( $expected !== $provided ) {
			self::redirect( $id, 'lifecycle_confirmation_failed' );
		}
		$result = SC_EI_Lifecycle_Repository::transition(
			$id,
			$stage,
			get_current_user_id(),
			array(
				'reason'         => wp_unslash( $_POST['transition_reason'] ?? '' ),
				'next_action'    => wp_unslash( $_POST['transition_next_action'] ?? '' ),
				'sender_visible' => ! empty( $_POST['transition_sender_visible'] ),
			)
		);
		self::redirect( $id, is_wp_error( $result ) ? $result->get_error_code() : 'lifecycle_stage_changed' );
	}

	public static function handle_workspace(): void {
		self::require_cap( 'sc_intake_manage_lifecycle' );
		$id = absint( $_POST['inquiry_id'] ?? 0 );
		check_admin_referer( 'sc_ei_lifecycle_workspace_' . $id );
		$result = SC_EI_Lifecycle_Repository::update_workspace(
			$id,
			array(
				'lifecycle_owner_user_id' => absint( $_POST['lifecycle_owner_user_id'] ?? 0 ),
				'lifecycle_priority'      => wp_unslash( $_POST['lifecycle_priority'] ?? 'normal' ),
				'next_action'             => wp_unslash( $_POST['next_action'] ?? '' ),
				'next_action_at'          => wp_unslash( $_POST['next_action_at'] ?? '' ),
				'sender_lifecycle_summary'=> wp_unslash( $_POST['sender_lifecycle_summary'] ?? '' ),
			),
			get_current_user_id()
		);
		self::redirect( $id, is_wp_error( $result ) ? $result->get_error_code() : 'lifecycle_workspace_updated' );
	}

	public static function handle_qualification(): void {
		self::require_cap( 'sc_intake_manage_lifecycle' );
		$id = absint( $_POST['inquiry_id'] ?? 0 );
		check_admin_referer( 'sc_ei_lifecycle_qualification_' . $id );
		$result = SC_EI_Lifecycle_Repository::update_qualification(
			$id,
			array(
				'qualification_status'     => wp_unslash( $_POST['qualification_status'] ?? '' ),
				'qualification_score'      => absint( $_POST['qualification_score'] ?? 0 ),
				'decision_authority'       => wp_unslash( $_POST['decision_authority'] ?? '' ),
				'funding_status'           => wp_unslash( $_POST['funding_status'] ?? '' ),
				'ai_assurance_applicable'  => wp_unslash( $_POST['ai_assurance_applicable'] ?? '' ),
				'teams_readiness'          => wp_unslash( $_POST['teams_readiness'] ?? '' ),
				'organizational_challenge'=> wp_unslash( $_POST['organizational_challenge'] ?? '' ),
				'desired_outcome'         => wp_unslash( $_POST['qualification_desired_outcome'] ?? '' ),
				'current_systems'         => wp_unslash( $_POST['current_systems'] ?? '' ),
				'constraints'             => wp_unslash( $_POST['constraints'] ?? '' ),
				'timeline_context'        => wp_unslash( $_POST['timeline_context'] ?? '' ),
				'privacy_security'        => wp_unslash( $_POST['privacy_security'] ?? '' ),
				'stakeholders'            => wp_unslash( $_POST['stakeholders'] ?? '' ),
				'qualification_rationale' => wp_unslash( $_POST['qualification_rationale'] ?? '' ),
			),
			get_current_user_id()
		);
		self::redirect( $id, is_wp_error( $result ) ? $result->get_error_code() : 'lifecycle_qualification_updated' );
	}

	public static function handle_add_note(): void {
		self::require_cap( 'sc_intake_add_lifecycle_notes' );
		$id = absint( $_POST['inquiry_id'] ?? 0 );
		check_admin_referer( 'sc_ei_lifecycle_add_note_' . $id );
		$result = SC_EI_Lifecycle_Repository::add_note(
			$id,
			(string) wp_unslash( $_POST['note_body'] ?? '' ),
			(string) wp_unslash( $_POST['note_type'] ?? 'internal' ),
			get_current_user_id(),
			! empty( $_POST['is_sensitive'] )
		);
		self::redirect( $id, is_wp_error( $result ) ? $result->get_error_code() : 'lifecycle_note_added' );
	}

	public static function handle_add_task(): void {
		self::require_cap( 'sc_intake_manage_lifecycle_tasks' );
		$id = absint( $_POST['inquiry_id'] ?? 0 );
		check_admin_referer( 'sc_ei_lifecycle_add_task_' . $id );
		$result = SC_EI_Lifecycle_Repository::add_task(
			$id,
			array(
				'title'            => wp_unslash( $_POST['task_title'] ?? '' ),
				'details'          => wp_unslash( $_POST['task_details'] ?? '' ),
				'priority'         => wp_unslash( $_POST['task_priority'] ?? 'normal' ),
				'due_at'           => wp_unslash( $_POST['task_due_at'] ?? '' ),
				'assigned_user_id' => absint( $_POST['task_assigned_user_id'] ?? 0 ),
				'reminder_policy'  => 'daily_when_due',
			),
			get_current_user_id()
		);
		self::redirect( $id, is_wp_error( $result ) ? $result->get_error_code() : 'lifecycle_task_added' );
	}

	public static function handle_update_task(): void {
		self::require_cap( 'sc_intake_manage_lifecycle_tasks' );
		$id = absint( $_POST['inquiry_id'] ?? 0 );
		$task_id = absint( $_POST['task_id'] ?? 0 );
		check_admin_referer( 'sc_ei_lifecycle_update_task_' . $task_id );
		$result = SC_EI_Lifecycle_Repository::update_task(
			$task_id,
			array(
				'task_status'      => wp_unslash( $_POST['task_status'] ?? '' ),
				'priority'         => wp_unslash( $_POST['task_priority'] ?? '' ),
				'due_at'           => wp_unslash( $_POST['task_due_at'] ?? '' ),
				'assigned_user_id' => absint( $_POST['task_assigned_user_id'] ?? 0 ),
			),
			get_current_user_id()
		);
		self::redirect( $id, is_wp_error( $result ) ? $result->get_error_code() : 'lifecycle_task_updated' );
	}

	private static function require_cap( string $capability ): void {
		if ( ! current_user_can( $capability ) ) {
			wp_die( esc_html__( 'You do not have permission to manage this lifecycle workspace.', 'sustainable-catalyst-engagement-intake' ) );
		}
	}

	private static function redirect( int $inquiry_id, string $message ): void {
		wp_safe_redirect( self::url( $inquiry_id, array( 'sc_ei_msg' => sanitize_key( $message ) ) ), 303 );
		exit;
	}
}
