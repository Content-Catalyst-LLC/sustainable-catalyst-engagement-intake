<?php
/**
 * Administrative Microsoft Teams and calendar coordination workspace.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Calendar_Admin {

	public static function register(): void {
		add_action( 'admin_post_sc_ei_calendar_save_coordination', array( __CLASS__, 'handle_save_coordination' ) );
		add_action( 'admin_post_sc_ei_calendar_reschedule', array( __CLASS__, 'handle_reschedule' ) );
		add_action( 'admin_post_sc_ei_calendar_complete', array( __CLASS__, 'handle_complete' ) );
		add_action( 'admin_post_sc_ei_calendar_cancel', array( __CLASS__, 'handle_cancel' ) );
		add_action( 'admin_post_sc_ei_calendar_mark_reminder_sent', array( __CLASS__, 'handle_mark_reminder_sent' ) );
	}

	public static function submenu(): void {
		add_submenu_page(
			'sc-engagement-intake',
			__( 'Microsoft Teams and Calendar Coordination', 'sustainable-catalyst-engagement-intake' ),
			__( 'Calendar Coordination', 'sustainable-catalyst-engagement-intake' ),
			'sc_intake_view_workflow',
			'sc-engagement-intake-calendar',
			array( __CLASS__, 'page' )
		);
	}

	public static function page(): void {
		if ( ! current_user_can( 'sc_intake_view_workflow' ) ) {
			wp_die( esc_html__( 'You do not have permission to view calendar coordination.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}
		$inquiry_id = absint( $_GET['inquiry'] ?? 0 );
		$message = sanitize_key( wp_unslash( $_GET['sc_ei_msg'] ?? '' ) );
		$inquiry = $inquiry_id ? SC_EI_Inquiry_Repository::find( $inquiry_id ) : null;
		$meetings = $inquiry ? SC_EI_Workflow_Repository::meeting_offers_for_inquiry( $inquiry_id, false ) : array();
		$reminders = array();
		foreach ( $meetings as $meeting ) {
			$reminders[ $meeting['id'] ] = SC_EI_Calendar_Repository::reminders_for_meeting( absint( $meeting['id'] ) );
		}
		$metrics = SC_EI_Calendar_Repository::metrics();
		$settings = SC_EI_Calendar_Repository::settings();
		include SC_EI_DIR . 'admin/views/calendar-coordination.php';
	}

	public static function url( int $inquiry_id = 0, array $args = array() ): string {
		$query = array_merge( array( 'page' => 'sc-engagement-intake-calendar' ), $args );
		if ( $inquiry_id ) {
			$query['inquiry'] = $inquiry_id;
		}
		return add_query_arg( $query, admin_url( 'admin.php' ) );
	}

	public static function handle_save_coordination(): void {
		self::require_cap( 'sc_intake_finalize_meetings' );
		$id = absint( $_POST['meeting_offer_id'] ?? 0 );
		check_admin_referer( 'sc_ei_calendar_save_coordination_' . $id );
		$meeting = SC_EI_Workflow_Repository::find_meeting_offer( $id );
		if ( ! $meeting ) {
			self::redirect( 0, 'calendar_meeting_not_found' );
		}
		$result = SC_EI_Calendar_Repository::save_coordination(
			$id,
			array(
				'meeting_type'               => wp_unslash( $_POST['meeting_type'] ?? '' ),
				'timezone'                   => wp_unslash( $_POST['meeting_timezone'] ?? '' ),
				'teams_url'                  => wp_unslash( $_POST['teams_url'] ?? '' ),
				'organizer_name'             => wp_unslash( $_POST['organizer_name'] ?? '' ),
				'organizer_email'            => wp_unslash( $_POST['organizer_email'] ?? '' ),
				'participant_emails'         => wp_unslash( $_POST['participant_emails'] ?? '' ),
				'agenda'                     => wp_unslash( $_POST['agenda'] ?? '' ),
				'preparation_requests'       => wp_unslash( $_POST['preparation_requests'] ?? '' ),
				'sender_summary'             => wp_unslash( $_POST['sender_summary'] ?? '' ),
				'sender_next_step'           => wp_unslash( $_POST['sender_next_step'] ?? '' ),
				'related_document_ids'       => wp_unslash( $_POST['related_document_ids'] ?? '' ),
				'calendar_provider'          => wp_unslash( $_POST['calendar_provider'] ?? 'manual' ),
				'external_calendar_reference'=> wp_unslash( $_POST['external_calendar_reference'] ?? '' ),
			),
			get_current_user_id()
		);
		self::redirect( absint( $meeting['inquiry_id'] ), is_wp_error( $result ) ? $result->get_error_code() : 'calendar_coordination_saved' );
	}

	public static function handle_reschedule(): void {
		self::require_cap( 'sc_intake_finalize_meetings' );
		$id = absint( $_POST['meeting_offer_id'] ?? 0 );
		check_admin_referer( 'sc_ei_calendar_reschedule_' . $id );
		$meeting = SC_EI_Workflow_Repository::find_meeting_offer( $id );
		if ( ! $meeting ) {
			self::redirect( 0, 'calendar_meeting_not_found' );
		}
		self::require_confirmation( 'RESCHEDULE ' . strtoupper( (string) $meeting['offer_number'] ), $_POST['confirmation'] ?? '', absint( $meeting['inquiry_id'] ) );
		$result = SC_EI_Calendar_Repository::reschedule( $id, wp_unslash( $_POST['start_local'] ?? '' ), wp_unslash( $_POST['end_local'] ?? '' ), wp_unslash( $_POST['timezone'] ?? '' ), wp_unslash( $_POST['reason'] ?? '' ), get_current_user_id() );
		self::redirect( absint( $meeting['inquiry_id'] ), is_wp_error( $result ) ? $result->get_error_code() : 'calendar_meeting_rescheduled' );
	}

	public static function handle_complete(): void {
		self::require_cap( 'sc_intake_finalize_meetings' );
		$id = absint( $_POST['meeting_offer_id'] ?? 0 );
		check_admin_referer( 'sc_ei_calendar_complete_' . $id );
		$meeting = SC_EI_Workflow_Repository::find_meeting_offer( $id );
		if ( ! $meeting ) {
			self::redirect( 0, 'calendar_meeting_not_found' );
		}
		self::require_confirmation( 'COMPLETE ' . strtoupper( (string) $meeting['offer_number'] ), $_POST['confirmation'] ?? '', absint( $meeting['inquiry_id'] ) );
		$result = SC_EI_Calendar_Repository::complete(
			$id,
			array(
				'internal_notes'          => wp_unslash( $_POST['internal_notes'] ?? '' ),
				'sender_summary'          => wp_unslash( $_POST['sender_summary'] ?? '' ),
				'decisions'               => wp_unslash( $_POST['decisions'] ?? '' ),
				'open_questions'          => wp_unslash( $_POST['open_questions'] ?? '' ),
				'follow_up_title'         => wp_unslash( $_POST['follow_up_title'] ?? '' ),
				'follow_up_details'       => wp_unslash( $_POST['follow_up_details'] ?? '' ),
				'follow_up_owner_user_id' => absint( $_POST['follow_up_owner_user_id'] ?? 0 ),
				'follow_up_due_at'        => wp_unslash( $_POST['follow_up_due_at'] ?? '' ),
			),
			get_current_user_id()
		);
		self::redirect( absint( $meeting['inquiry_id'] ), is_wp_error( $result ) ? $result->get_error_code() : 'calendar_meeting_completed' );
	}

	public static function handle_cancel(): void {
		self::require_cap( 'sc_intake_finalize_meetings' );
		$id = absint( $_POST['meeting_offer_id'] ?? 0 );
		check_admin_referer( 'sc_ei_calendar_cancel_' . $id );
		$meeting = SC_EI_Workflow_Repository::find_meeting_offer( $id );
		if ( ! $meeting ) {
			self::redirect( 0, 'calendar_meeting_not_found' );
		}
		self::require_confirmation( 'CANCEL ' . strtoupper( (string) $meeting['offer_number'] ), $_POST['confirmation'] ?? '', absint( $meeting['inquiry_id'] ) );
		$result = SC_EI_Calendar_Repository::cancel( $id, wp_unslash( $_POST['reason'] ?? '' ), get_current_user_id() );
		self::redirect( absint( $meeting['inquiry_id'] ), is_wp_error( $result ) ? $result->get_error_code() : 'calendar_meeting_canceled' );
	}

	public static function handle_mark_reminder_sent(): void {
		self::require_cap( 'sc_intake_send_communications' );
		$id = absint( $_POST['reminder_id'] ?? 0 );
		check_admin_referer( 'sc_ei_calendar_mark_reminder_sent_' . $id );
		$meeting_id = absint( $_POST['meeting_offer_id'] ?? 0 );
		$meeting = SC_EI_Workflow_Repository::find_meeting_offer( $meeting_id );
		$result = SC_EI_Calendar_Repository::mark_reminder_sent( $id, absint( $_POST['communication_id'] ?? 0 ), get_current_user_id() );
		self::redirect( absint( $meeting['inquiry_id'] ?? 0 ), is_wp_error( $result ) ? $result->get_error_code() : 'calendar_reminder_marked_sent' );
	}

	private static function require_confirmation( string $expected, $provided, int $inquiry_id ): void {
		$provided = strtoupper( trim( sanitize_text_field( wp_unslash( $provided ) ) ) );
		if ( ! hash_equals( $expected, $provided ) ) {
			self::redirect( $inquiry_id, 'calendar_confirmation_failed' );
		}
	}

	private static function require_cap( string $capability ): void {
		if ( ! current_user_can( $capability ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this calendar action.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}
	}

	private static function redirect( int $inquiry_id, string $message ): void {
		wp_safe_redirect( self::url( $inquiry_id, array( 'sc_ei_msg' => sanitize_key( $message ) ) ), 303 );
		exit;
	}
}
