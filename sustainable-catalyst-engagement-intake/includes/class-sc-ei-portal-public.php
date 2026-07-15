<?php
/**
 * Secure sender portal shortcode and public actions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Portal_Public {

	private static bool $assets_enqueued = false;

	public static function register(): void {
		add_shortcode( 'sc_sender_portal', array( __CLASS__, 'shortcode' ) );
		add_action( 'template_redirect', array( __CLASS__, 'protect_portal_page' ), 1 );

		foreach (
			array(
				'sc_ei_portal_activate'          => 'handle_activate',
				'sc_ei_portal_recovery'          => 'handle_recovery',
				'sc_ei_portal_logout'            => 'handle_logout',
				'sc_ei_portal_send_message'      => 'handle_message',
				'sc_ei_portal_upload_documents'  => 'handle_upload',
				'sc_ei_portal_update_contact'    => 'handle_contact',
				'sc_ei_portal_update_scheduling' => 'handle_scheduling',
				'sc_ei_portal_respond_meeting'   => 'handle_meeting_response',
				'sc_ei_portal_download_meeting_ics' => 'handle_meeting_ics',
				'sc_ei_portal_respond_proposal'  => 'handle_proposal_response',
				'sc_ei_portal_approve_sow'        => 'handle_sow_approval',
				'sc_ei_portal_print_proposal'    => 'handle_proposal_print',
				'sc_ei_portal_privacy_request'   => 'handle_privacy_request',
				'sc_ei_portal_withdrawal'        => 'handle_withdrawal',
				'sc_ei_portal_revoke_access'     => 'handle_revoke_access',
			) as $action => $method
		) {
			add_action( 'admin_post_nopriv_' . $action, array( __CLASS__, $method ) );
			add_action( 'admin_post_' . $action, array( __CLASS__, $method ) );
		}
	}

	public static function protect_portal_page(): void {
		global $post;

		$is_portal = ! empty( $_GET['sc_ei_portal_invite'] )
			|| ! empty( $_COOKIE[ SC_EI_Portal_Schema::COOKIE_NAME ] )
			|| ! empty( $_COOKIE[ SC_EI_Portal_Schema::LEGACY_COOKIE_NAME ] )
			|| ( $post instanceof WP_Post && has_shortcode( (string) $post->post_content, 'sc_sender_portal' ) );
		if ( ! $is_portal ) {
			return;
		}
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		if ( ! headers_sent() ) {
			nocache_headers();
			header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private' );
			header( 'Pragma: no-cache' );
			header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT' );
			header( 'Referrer-Policy: no-referrer' );
			header( 'X-Content-Type-Options: nosniff' );
			header( 'X-Frame-Options: DENY' );
			header( 'Cross-Origin-Opener-Policy: same-origin' );
			header( 'Cross-Origin-Resource-Policy: same-origin' );
		}
		add_filter(
			'wp_robots',
			static function ( array $robots ): array {
				$robots['noindex'] = true;
				$robots['nofollow'] = true;
				$robots['noarchive'] = true;
				$robots['nosnippet'] = true;
				return $robots;
			}
		);
	}

	public static function shortcode( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'title' => __( 'Secure Sender Portal', 'sustainable-catalyst-engagement-intake' ),
				'intro' => __( 'Use your private invitation to view sender-safe status, exchange secure messages, provide follow-up documents, update preferences, and manage privacy requests.', 'sustainable-catalyst-engagement-intake' ),
			),
			$atts,
			'sc_sender_portal'
		);
		self::enqueue_assets();
		self::protect_portal_page();

		$settings = SC_EI_Portal_Repository::settings();
		$result_code = isset( $_GET['sc_ei_portal_result'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_portal_result'] ) ) : '';
		$error_code = isset( $_GET['sc_ei_portal_error'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_portal_error'] ) ) : '';
		$invite_public_id = isset( $_GET['sc_ei_portal_invite'] ) ? sanitize_text_field( wp_unslash( $_GET['sc_ei_portal_invite'] ) ) : '';
		$invite_token = isset( $_GET['sc_ei_portal_token'] ) ? sanitize_text_field( wp_unslash( $_GET['sc_ei_portal_token'] ) ) : '';
		$invitation_state = SC_EI_Portal_Repository::inspect_invitation( $invite_public_id, $invite_token );

		$context = SC_EI_Portal_Session::current();
		if ( is_wp_error( $context ) ) {
			$context_error = $context->get_error_code();
			ob_start();
			include SC_EI_DIR . 'public/views/sender-portal-login.php';
			return (string) ob_get_clean();
		}

		$view = SC_EI_Portal_Schema::sanitize_view( isset( $_GET['portal_view'] ) ? wp_unslash( $_GET['portal_view'] ) : 'overview' );
		$inquiry = $context['inquiry'];
		$access = $context['access'];
		$session = $context['session'];
		$csrf_token = SC_EI_Portal_Session::csrf_token( $context );
		$messages = SC_EI_Portal_Repository::portal_messages( absint( $inquiry['id'] ), 500 );
		$attachments = SC_EI_Attachment_Repository::for_inquiry( absint( $inquiry['id'] ), false );
		$workflow_settings = SC_EI_Workflow_Repository::settings();
		$meeting_offers = SC_EI_Workflow_Repository::meeting_offers_for_inquiry( absint( $inquiry['id'] ), true );
		$proposals = SC_EI_Workflow_Repository::proposals_for_inquiry( absint( $inquiry['id'] ), true );
		$statements_of_work = SC_EI_Proposal_Governance_Repository::sender_snapshot( absint( $inquiry['id'] ) );
		$lifecycle_snapshot = SC_EI_Lifecycle_Repository::sender_snapshot( absint( $inquiry['id'] ) );
		$support_snapshot = SC_EI_Support_Repository::sender_snapshot( absint( $inquiry['id'] ) );
		$engagement_settings = SC_EI_Engagement_Repository::settings();
		$engagements = ! empty( $engagement_settings['engagement_sender_portal_enabled'] )
			? SC_EI_Engagement_Repository::for_inquiry( absint( $inquiry['id'] ), true )
			: array();
		$engagement_requirements = array();
		foreach ( $engagements as $engagement_record ) {
			$engagement_requirements[ $engagement_record['id'] ] = SC_EI_Engagement_Repository::requirements( absint( $engagement_record['id'] ), true );
		}
		$portal_url = SC_EI_Portal_Schema::sanitize_portal_page_url( (string) $settings['portal_page_url'] );
		$effective_upload_limits = SC_EI_Upload_Environment::effective_limits( $settings );
		$upload_extensions = array_values(
			array_intersect(
				array_keys( SC_EI_Upload_Validator::supported_extensions() ),
				(array) ( $settings['allowed_upload_extensions'] ?? array() )
			)
		);
		ob_start();
		include SC_EI_DIR . 'public/views/sender-portal.php';
		return (string) ob_get_clean();
	}

	public static function handle_activate(): void {
		$edge = SC_EI_Hardening_Repository::guard_public_write( 'portal_activation', 20, 15 * MINUTE_IN_SECONDS );
		if ( is_wp_error( $edge ) ) { self::redirect_activation( '', '', $edge->get_error_code() ); }
		$public_id = isset( $_POST['portal_public_id'] ) ? sanitize_text_field( wp_unslash( $_POST['portal_public_id'] ) ) : '';
		$token = isset( $_POST['portal_token'] ) ? sanitize_text_field( wp_unslash( $_POST['portal_token'] ) ) : '';
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'sc_ei_portal_activate' ) ) {
			self::redirect_activation( $public_id, $token, 'portal_activation_form_expired' );
		}

		$email = isset( $_POST['portal_email'] ) ? sanitize_email( wp_unslash( $_POST['portal_email'] ) ) : '';
		$terms = ! empty( $_POST['portal_terms'] );
		$result = SC_EI_Portal_Repository::activate_invitation( $public_id, $token, $email, $terms );
		if ( is_wp_error( $result ) ) {
			self::redirect_activation( $public_id, $token, $result->get_error_code() );
		}

		if ( ! SC_EI_Portal_Session::set_cookie( $result['raw_token'], $result['expires_at'] ) ) {
			SC_EI_Portal_Repository::revoke_session( absint( $result['session']['id'] ), 'Session cookie could not be established.', 0 );
			$access = SC_EI_Portal_Repository::find_access( absint( $result['session']['access_id'] ) );
			$permissions = $access ? json_decode( (string) $access['permissions_json'], true ) : array();
			$reissued = $access
				? SC_EI_Portal_Repository::issue_invitation(
					absint( $access['inquiry_id'] ),
					array(
						'invite_ttl_hours' => SC_EI_Portal_Repository::settings()['portal_invite_ttl_hours'],
						'permissions'      => is_array( $permissions ) ? $permissions : SC_EI_Portal_Schema::default_permissions(),
						'invitation_note'  => 'Automatically reissued after browser session-cookie establishment failed.',
					),
					0
				)
				: new WP_Error( 'portal_cookie_failed' );
			if ( ! is_wp_error( $reissued ) ) {
				wp_safe_redirect(
					add_query_arg(
						'sc_ei_portal_error',
						'portal_cookie_failed',
						$reissued['url']
					),
					303
				);
				exit;
			}
			self::redirect( 'overview', '', 'portal_cookie_failed' );
		}
		self::redirect( 'overview', 'portal_activated' );
	}

	public static function handle_recovery(): void {
		$settings = SC_EI_Hardening_Repository::settings();
		$edge = SC_EI_Hardening_Repository::guard_public_write( 'portal_recovery', absint( $settings['hardening_recovery_edge_limit_hour'] ?? 10 ), HOUR_IN_SECONDS );
		if ( is_wp_error( $edge ) ) { self::redirect( 'overview', 'portal_recovery_received' ); }
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
		$honeypot = isset( $_POST['portal_company_website'] ) ? trim( (string) wp_unslash( $_POST['portal_company_website'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'sc_ei_portal_recovery' ) || '' !== $honeypot ) {
			self::redirect( 'overview', 'portal_recovery_received' );
		}
		SC_EI_Portal_Repository::request_recovery(
			isset( $_POST['portal_reference'] ) ? wp_unslash( $_POST['portal_reference'] ) : '',
			isset( $_POST['portal_recovery_email'] ) ? wp_unslash( $_POST['portal_recovery_email'] ) : '',
			isset( $_POST['portal_recovery_reason'] ) ? wp_unslash( $_POST['portal_recovery_reason'] ) : ''
		);
		self::redirect( 'overview', 'portal_recovery_received' );
	}

	public static function handle_logout(): void {
		$context = self::require_context();
		if ( is_wp_error( $context ) ) {
			self::redirect( 'overview', '', $context->get_error_code() );
		}
		if ( ! self::valid_csrf( $context ) ) {
			self::redirect( 'overview', '', 'portal_csrf_failed' );
		}
		SC_EI_Portal_Repository::revoke_session( absint( $context['session']['id'] ), 'Sender signed out.', 0 );
		SC_EI_Portal_Session::clear_cookie();
		self::redirect( 'overview', 'portal_signed_out' );
	}

	public static function handle_message(): void {
		$context = self::require_context( 'send_messages' );
		if ( is_wp_error( $context ) ) {
			self::redirect( 'messages', '', $context->get_error_code() );
		}
		if ( ! self::valid_csrf( $context ) ) {
			self::redirect( 'messages', '', 'portal_csrf_failed' );
		}
		$settings = SC_EI_Portal_Repository::settings();
		if (
			SC_EI_Portal_Repository::rate_limited(
				absint( $context['session']['id'] ),
				array( 'sender_message_created' ),
				absint( $settings['portal_message_rate_limit_hour'] )
			)
		) {
			SC_EI_Portal_Repository::record_event( 'rate_limit_triggered', absint( $context['inquiry']['id'] ), absint( $context['access']['id'] ), absint( $context['session']['id'] ), 'message', 0, 'rejected', array( 'limit' => 'message_hour' ) );
			self::redirect( 'messages', '', 'portal_rate_limited' );
		}
		$result = SC_EI_Portal_Repository::create_portal_message(
			absint( $context['inquiry']['id'] ),
			'inbound',
			isset( $_POST['portal_message'] ) ? wp_unslash( $_POST['portal_message'] ) : '',
			0,
			absint( $_POST['reply_to_id'] ?? 0 )
		);
		self::redirect( 'messages', is_wp_error( $result ) ? '' : 'portal_message_sent', is_wp_error( $result ) ? $result->get_error_code() : '' );
	}

	public static function handle_upload(): void {
		$context = self::require_context( 'upload_documents' );
		if ( is_wp_error( $context ) ) {
			self::redirect( 'documents', '', $context->get_error_code() );
		}
		if ( ! self::valid_csrf( $context ) ) {
			self::redirect( 'documents', '', 'portal_csrf_failed' );
		}
		$settings = SC_EI_Portal_Repository::settings();
		if (
			SC_EI_Portal_Repository::rate_limited(
				absint( $context['session']['id'] ),
				array( 'document_uploaded' ),
				absint( $settings['portal_update_rate_limit_hour'] )
			)
		) {
			SC_EI_Portal_Repository::record_event( 'rate_limit_triggered', absint( $context['inquiry']['id'] ), absint( $context['access']['id'] ), absint( $context['session']['id'] ), 'attachment', 0, 'rejected', array( 'limit' => 'update_hour' ) );
			self::redirect( 'documents', '', 'portal_rate_limited' );
		}
		$result = SC_EI_Upload_Manager::process_inquiry_uploads(
			$context['inquiry'],
			$_FILES,
			array(
				'form_variant'            => 'sender_portal',
				'source_page'             => 'sender-portal',
				'request_id'              => wp_generate_uuid4(),
				'document_category'       => isset( $_POST['document_category'] ) ? sanitize_key( wp_unslash( $_POST['document_category'] ) ) : 'supporting_document',
				'document_notes'          => isset( $_POST['document_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['document_notes'] ) ) : '',
				'document_confidentiality'=> isset( $_POST['document_confidentiality'] ) ? sanitize_key( wp_unslash( $_POST['document_confidentiality'] ) ) : 'confidential',
				'portal_session_id'       => absint( $context['session']['id'] ),
			)
		);
		SC_EI_Portal_Repository::register_document_upload_result( absint( $context['inquiry']['id'] ), $result, absint( $context['session']['id'] ) );
		$error = $result['count'] ? '' : ( $result['errors'] ? 'portal_upload_rejected' : 'portal_no_documents' );
		self::redirect( 'documents', $result['count'] ? 'portal_documents_uploaded' : '', $error );
	}

	public static function handle_contact(): void {
		$context = self::require_context( 'update_contact' );
		if ( is_wp_error( $context ) ) {
			self::redirect( 'preferences', '', $context->get_error_code() );
		}
		if ( ! self::valid_csrf( $context ) ) {
			self::redirect( 'preferences', '', 'portal_csrf_failed' );
		}
		if ( self::update_rate_limited( $context ) ) {
			self::redirect( 'preferences', '', 'portal_rate_limited' );
		}
		$result = SC_EI_Portal_Repository::update_contact(
			absint( $context['inquiry']['id'] ),
			wp_unslash( $_POST ),
			absint( $_POST['portal_version'] ?? 0 ),
			absint( $context['session']['id'] )
		);
		self::redirect( 'preferences', is_wp_error( $result ) ? '' : 'portal_contact_updated', is_wp_error( $result ) ? $result->get_error_code() : '' );
	}

	public static function handle_scheduling(): void {
		$context = self::require_context( 'update_scheduling' );
		if ( is_wp_error( $context ) ) {
			self::redirect( 'preferences', '', $context->get_error_code() );
		}
		if ( ! self::valid_csrf( $context ) ) {
			self::redirect( 'preferences', '', 'portal_csrf_failed' );
		}
		if ( self::update_rate_limited( $context ) ) {
			self::redirect( 'preferences', '', 'portal_rate_limited' );
		}
		$result = SC_EI_Portal_Repository::update_scheduling(
			absint( $context['inquiry']['id'] ),
			wp_unslash( $_POST ),
			absint( $_POST['portal_version'] ?? 0 ),
			absint( $context['session']['id'] )
		);
		self::redirect( 'preferences', is_wp_error( $result ) ? '' : 'portal_scheduling_updated', is_wp_error( $result ) ? $result->get_error_code() : '' );
	}

	public static function handle_meeting_response(): void {
		$context = self::require_context( 'respond_meetings' );
		if ( is_wp_error( $context ) ) {
			self::redirect( 'meetings', '', $context->get_error_code() );
		}
		if ( ! self::valid_csrf( $context ) ) {
			self::redirect( 'meetings', '', 'portal_csrf_failed' );
		}
		if ( self::update_rate_limited( $context ) ) {
			self::redirect( 'meetings', '', 'portal_rate_limited' );
		}
		$id = absint( $_POST['meeting_offer_id'] ?? 0 );
		$offer = SC_EI_Workflow_Repository::find_meeting_offer( $id );
		if ( ! $offer || absint( $offer['inquiry_id'] ) !== absint( $context['inquiry']['id'] ) ) {
			self::redirect( 'meetings', '', 'workflow_meeting_unavailable' );
		}
		$result = SC_EI_Workflow_Repository::respond_to_meeting(
			$id,
			isset( $_POST['meeting_response'] ) ? sanitize_key( wp_unslash( $_POST['meeting_response'] ) ) : '',
			isset( $_POST['meeting_slot_key'] ) ? sanitize_key( wp_unslash( $_POST['meeting_slot_key'] ) ) : '',
			isset( $_POST['meeting_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['meeting_note'] ) ) : '',
			absint( $context['session']['id'] )
		);
		if ( ! is_wp_error( $result ) ) {
			SC_EI_Portal_Repository::record_event(
				'meeting_response_recorded',
				absint( $context['inquiry']['id'] ),
				absint( $context['access']['id'] ),
				absint( $context['session']['id'] ),
				'meeting',
				$id,
				'success',
				array( 'status' => $result['status'], 'automatic_booking' => false )
			);
		}
		self::redirect( 'meetings', is_wp_error( $result ) ? '' : 'portal_meeting_response_saved', is_wp_error( $result ) ? $result->get_error_code() : '' );
	}

	public static function handle_meeting_ics(): void {
		$context = self::require_context( 'view_meetings' );
		if ( is_wp_error( $context ) ) {
			self::redirect( 'meetings', '', $context->get_error_code() );
		}
		if ( ! self::valid_csrf( $context ) ) {
			self::redirect( 'meetings', '', 'portal_csrf_failed' );
		}
		$id = absint( $_POST['meeting_offer_id'] ?? 0 );
		$offer = SC_EI_Workflow_Repository::find_meeting_offer( $id );
		if (
			! $offer
			|| absint( $offer['inquiry_id'] ) !== absint( $context['inquiry']['id'] )
			|| 'scheduled' !== $offer['status']
			|| empty( $offer['selected_start_utc'] )
			|| empty( $offer['selected_end_utc'] )
			|| ! SC_EI_Teams::is_teams_url( (string) $offer['teams_url'] )
			|| empty( SC_EI_Workflow_Repository::settings()['workflow_allow_sender_ics'] )
		) {
			self::redirect( 'meetings', '', 'workflow_ics_unavailable' );
		}
		SC_EI_Workflow_Repository::note_portal_activity(
			absint( $offer['inquiry_id'] ),
			absint( $context['session']['id'] ),
			'meeting',
			$id,
			'meeting_ics_downloaded',
			array( 'offer_number' => $offer['offer_number'] )
		);
		SC_EI_Portal_Repository::record_event(
			'meeting_ics_downloaded',
			absint( $context['inquiry']['id'] ),
			absint( $context['access']['id'] ),
			absint( $context['session']['id'] ),
			'meeting',
			$id,
			'success'
		);
		nocache_headers();
		header( 'Content-Type: text/calendar; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( strtolower( $offer['offer_number'] ) . '.ics' ) . '"' );
		header( 'X-Content-Type-Options: nosniff' );
		echo SC_EI_Workflow_Repository::meeting_ics( $offer, $context['inquiry'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	public static function handle_proposal_response(): void {
		$context = self::require_context( 'respond_proposals' );
		if ( is_wp_error( $context ) ) {
			self::redirect( 'proposals', '', $context->get_error_code() );
		}
		if ( ! self::valid_csrf( $context ) ) {
			self::redirect( 'proposals', '', 'portal_csrf_failed' );
		}
		if ( self::update_rate_limited( $context ) ) {
			self::redirect( 'proposals', '', 'portal_rate_limited' );
		}
		if ( empty( SC_EI_Workflow_Repository::settings()['workflow_allow_proposal_acceptance'] ) ) {
			self::redirect( 'proposals', '', 'workflow_proposal_response_disabled' );
		}
		$id = absint( $_POST['proposal_id'] ?? 0 );
		$proposal = SC_EI_Workflow_Repository::find_proposal( $id, true );
		if ( ! $proposal || absint( $proposal['inquiry_id'] ) !== absint( $context['inquiry']['id'] ) ) {
			self::redirect( 'proposals', '', 'workflow_proposal_unavailable' );
		}
		$result = SC_EI_Workflow_Repository::respond_to_proposal(
			$id,
			isset( $_POST['proposal_response'] ) ? sanitize_key( wp_unslash( $_POST['proposal_response'] ) ) : '',
			isset( $_POST['proposal_response_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['proposal_response_note'] ) ) : '',
			! empty( $_POST['proposal_authority_attested'] ),
			! empty( $_POST['proposal_boundary_acknowledged'] ),
			isset( $_POST['proposal_confirmation'] ) ? sanitize_text_field( wp_unslash( $_POST['proposal_confirmation'] ) ) : '',
			absint( $context['session']['id'] )
		);
		if ( ! is_wp_error( $result ) ) {
			SC_EI_Portal_Repository::record_event(
				'proposal_response_recorded',
				absint( $context['inquiry']['id'] ),
				absint( $context['access']['id'] ),
				absint( $context['session']['id'] ),
				'proposal',
				$id,
				'success',
				array( 'status' => $result['status'], 'automatic_contract' => false, 'automatic_payment' => false )
			);
		}
		self::redirect( 'proposals', is_wp_error( $result ) ? '' : 'portal_proposal_response_saved', is_wp_error( $result ) ? $result->get_error_code() : '' );
	}


	public static function handle_sow_approval(): void {
		$context = self::require_context( 'respond_proposals' );
		if ( is_wp_error( $context ) ) { self::redirect( 'proposals', '', $context->get_error_code() ); }
		if ( ! self::valid_csrf( $context ) ) { self::redirect( 'proposals', '', 'portal_csrf_failed' ); }
		if ( self::update_rate_limited( $context ) ) { self::redirect( 'proposals', '', 'portal_rate_limited' ); }
		$sow_id = absint( $_POST['sow_id'] ?? 0 );
		$sow = SC_EI_Proposal_Governance_Repository::find_sow( $sow_id, true );
		if ( ! $sow || absint( $sow['inquiry_id'] ) !== absint( $context['inquiry']['id'] ) || 'approved' !== (string) $sow['status'] ) {
			self::redirect( 'proposals', '', 'proposal_sow_unavailable' );
		}
		$confirmation = sanitize_text_field( wp_unslash( $_POST['sow_confirmation'] ?? '' ) );
		$expected = 'APPROVE ' . strtoupper( (string) $sow['sow_number'] );
		if ( ! hash_equals( $expected, strtoupper( trim( $confirmation ) ) ) ) {
			self::redirect( 'proposals', '', 'proposal_sow_confirmation_failed' );
		}
		if ( empty( $_POST['proposal_authority_attested'] ) || empty( $_POST['proposal_boundary_acknowledged'] ) ) {
			self::redirect( 'proposals', '', 'proposal_sow_attestation_required' );
		}
		$result = SC_EI_Proposal_Governance_Repository::record_sender_action(
			absint( $sow['proposal_id'] ),
			absint( $sow['proposal_version_id'] ),
			'sow_approved',
			sanitize_textarea_field( wp_unslash( $_POST['sow_response_note'] ?? '' ) ),
			true,
			true,
			$confirmation,
			absint( $context['session']['id'] ),
			$sow_id
		);
		if ( ! is_wp_error( $result ) ) {
			SC_EI_Portal_Repository::record_event( 'proposal_response_recorded', absint( $context['inquiry']['id'] ), absint( $context['access']['id'] ), absint( $context['session']['id'] ), 'statement_of_work', $sow_id, 'success', array( 'action' => 'sow_approved', 'automatic_contract' => false ) );
		}
		self::redirect( 'proposals', is_wp_error( $result ) ? '' : 'portal_sow_approval_saved', is_wp_error( $result ) ? $result->get_error_code() : '' );
	}

	public static function handle_proposal_print(): void {
		$context = self::require_context( 'view_proposals' );
		if ( is_wp_error( $context ) ) {
			self::redirect( 'proposals', '', $context->get_error_code() );
		}
		if ( ! self::valid_csrf( $context ) ) {
			self::redirect( 'proposals', '', 'portal_csrf_failed' );
		}
		$id = absint( $_POST['proposal_id'] ?? 0 );
		$proposal = SC_EI_Workflow_Repository::find_proposal( $id, true );
		if (
			! $proposal
			|| absint( $proposal['inquiry_id'] ) !== absint( $context['inquiry']['id'] )
			|| ! in_array( $proposal['status'], array( 'published', 'accepted_pending_contract', 'declined', 'contracted', 'withdrawn', 'expired', 'superseded' ), true )
		) {
			self::redirect( 'proposals', '', 'workflow_proposal_unavailable' );
		}
		SC_EI_Workflow_Repository::note_portal_activity(
			absint( $proposal['inquiry_id'] ),
			absint( $context['session']['id'] ),
			'proposal',
			$id,
			'proposal_print_viewed',
			array( 'proposal_number' => $proposal['proposal_number'] )
		);
		SC_EI_Portal_Repository::record_event(
			'proposal_print_viewed',
			absint( $context['inquiry']['id'] ),
			absint( $context['access']['id'] ),
			absint( $context['session']['id'] ),
			'proposal',
			$id,
			'success'
		);
		nocache_headers();
		header( 'Content-Type: text/html; charset=utf-8' );
		header( 'Content-Security-Policy: default-src \'none\'; style-src \'unsafe-inline\'; img-src data:; base-uri \'none\'; form-action \'none\'; frame-ancestors \'none\'' );
		header( 'Referrer-Policy: no-referrer' );
		header( 'X-Frame-Options: DENY' );
		$inquiry = $context['inquiry'];
		include SC_EI_DIR . 'public/views/proposal-print.php';
		exit;
	}

	public static function handle_privacy_request(): void {
		$context = self::require_context( 'privacy_requests' );
		if ( is_wp_error( $context ) ) {
			self::redirect( 'privacy', '', $context->get_error_code() );
		}
		if ( ! self::valid_csrf( $context ) ) {
			self::redirect( 'privacy', '', 'portal_csrf_failed' );
		}
		if ( self::update_rate_limited( $context ) ) {
			self::redirect( 'privacy', '', 'portal_rate_limited' );
		}
		$result = SC_EI_Portal_Repository::create_privacy_request(
			absint( $context['inquiry']['id'] ),
			isset( $_POST['request_type'] ) ? sanitize_key( wp_unslash( $_POST['request_type'] ) ) : 'access',
			isset( $_POST['request_summary'] ) ? sanitize_textarea_field( wp_unslash( $_POST['request_summary'] ) ) : '',
			absint( $context['session']['id'] )
		);
		self::redirect( 'privacy', is_wp_error( $result ) ? '' : 'portal_privacy_request_created', is_wp_error( $result ) ? $result->get_error_code() : '' );
	}

	public static function handle_withdrawal(): void {
		$context = self::require_context( 'request_withdrawal' );
		if ( is_wp_error( $context ) ) {
			self::redirect( 'privacy', '', $context->get_error_code() );
		}
		if ( ! self::valid_csrf( $context ) ) {
			self::redirect( 'privacy', '', 'portal_csrf_failed' );
		}
		$requested = 'request' === sanitize_key( (string) ( $_POST['withdrawal_action'] ?? 'request' ) );
		$expected = $requested
			? 'WITHDRAW ' . strtoupper( (string) $context['inquiry']['reference'] )
			: 'CANCEL ' . strtoupper( (string) $context['inquiry']['reference'] );
		$provided = strtoupper( trim( (string) ( $_POST['withdrawal_confirmation'] ?? '' ) ) );
		if ( ! hash_equals( $expected, $provided ) ) {
			self::redirect( 'privacy', '', 'portal_withdrawal_confirmation_failed' );
		}
		$result = SC_EI_Portal_Repository::update_withdrawal(
			absint( $context['inquiry']['id'] ),
			$requested,
			isset( $_POST['withdrawal_reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['withdrawal_reason'] ) ) : '',
			absint( $_POST['portal_version'] ?? 0 ),
			absint( $context['session']['id'] )
		);
		self::redirect( 'privacy', is_wp_error( $result ) ? '' : ( $requested ? 'portal_withdrawal_requested' : 'portal_withdrawal_canceled' ), is_wp_error( $result ) ? $result->get_error_code() : '' );
	}

	public static function handle_revoke_access(): void {
		$context = self::require_context( 'revoke_access' );
		if ( is_wp_error( $context ) ) {
			self::redirect( 'access', '', $context->get_error_code() );
		}
		if ( ! self::valid_csrf( $context ) ) {
			self::redirect( 'access', '', 'portal_csrf_failed' );
		}
		$expected = 'REVOKE ' . strtoupper( (string) $context['inquiry']['reference'] );
		$provided = strtoupper( trim( (string) ( $_POST['revoke_confirmation'] ?? '' ) ) );
		if ( ! hash_equals( $expected, $provided ) ) {
			self::redirect( 'access', '', 'portal_revoke_confirmation_failed' );
		}
		$result = SC_EI_Portal_Repository::change_access_status(
			absint( $context['access']['id'] ),
			'revoked',
			'Sender revoked portal access.',
			0
		);
		if ( is_wp_error( $result ) ) {
			self::redirect( 'access', '', $result->get_error_code() );
		}
		SC_EI_Portal_Session::clear_cookie();
		self::redirect( 'overview', 'portal_access_revoked' );
	}

	private static function require_context( string $permission = '' ) {
		$settings = SC_EI_Hardening_Repository::settings();
		$read_only_permissions = array( 'view_meetings', 'view_proposals' );
		if ( $permission && ! in_array( $permission, $read_only_permissions, true ) && SC_EI_Hardening_Repository::public_writes_paused() ) {
			return new WP_Error( 'service_temporarily_paused', __( 'This secure portal action is temporarily paused for maintenance. Read-only portal access remains available.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$edge = SC_EI_Hardening_Repository::consume_rate_limit( 'portal_authenticated_action', array( SC_EI_Hardening_Repository::client_ip_hash(), SC_EI_Hardening_Repository::user_agent_hash() ), absint( $settings['hardening_portal_edge_limit_15m'] ?? 60 ), 15 * MINUTE_IN_SECONDS );
		if ( is_wp_error( $edge ) ) return $edge;
		$context = SC_EI_Portal_Session::current();
		if ( is_wp_error( $context ) ) {
			return $context;
		}
		if ( $permission ) {
			$allowed = SC_EI_Portal_Session::require_permission( $context, $permission );
			if ( is_wp_error( $allowed ) ) {
				return $allowed;
			}
		}
		return $context;
	}

	private static function valid_csrf( array $context ): bool {
		$provided = isset( $_POST['portal_csrf'] ) ? sanitize_text_field( wp_unslash( $_POST['portal_csrf'] ) ) : '';
		return SC_EI_Portal_Session::verify_csrf( $context, $provided );
	}

	private static function update_rate_limited( array $context ): bool {
		$settings = SC_EI_Portal_Repository::settings();
		$limited = SC_EI_Portal_Repository::rate_limited(
			absint( $context['session']['id'] ),
			array( 'contact_updated', 'scheduling_updated', 'meeting_response_recorded', 'proposal_response_recorded', 'privacy_request_created', 'withdrawal_requested', 'withdrawal_canceled', 'document_uploaded' ),
			absint( $settings['portal_update_rate_limit_hour'] )
		);
		if ( $limited ) {
			SC_EI_Portal_Repository::record_event( 'rate_limit_triggered', absint( $context['inquiry']['id'] ), absint( $context['access']['id'] ), absint( $context['session']['id'] ), 'session', absint( $context['session']['id'] ), 'rejected', array( 'limit' => 'update_hour' ) );
		}
		return $limited;
	}

	private static function redirect_activation( string $public_id, string $token, string $error ): void {
		$settings = SC_EI_Portal_Repository::settings();
		$args = array(
			'portal_view'         => 'overview',
			'sc_ei_portal_error' => sanitize_key( $error ),
		);
		if ( '' !== $public_id && '' !== $token ) {
			$args['sc_ei_portal_invite'] = rawurlencode( $public_id );
			$args['sc_ei_portal_token'] = rawurlencode( $token );
		}
		wp_safe_redirect(
			add_query_arg(
				$args,
				SC_EI_Portal_Schema::sanitize_portal_page_url( (string) $settings['portal_page_url'] )
			),
			303
		);
		exit;
	}

	private static function redirect( string $view, string $result = '', string $error = '' ): void {
		$settings = SC_EI_Portal_Repository::settings();
		$args = array( 'portal_view' => SC_EI_Portal_Schema::sanitize_view( $view ) );
		if ( $result ) {
			$args['sc_ei_portal_result'] = sanitize_key( $result );
		}
		if ( $error ) {
			$args['sc_ei_portal_error'] = sanitize_key( $error );
		}
		wp_safe_redirect(
			add_query_arg(
				$args,
				SC_EI_Portal_Schema::sanitize_portal_page_url( (string) $settings['portal_page_url'] )
			),
			303
		);
		exit;
	}

	private static function enqueue_assets(): void {
		if ( self::$assets_enqueued ) {
			return;
		}
		wp_enqueue_style( 'sc-ei-public', SC_EI_URL . 'assets/css/public.css', array(), SC_EI_VERSION );
		wp_enqueue_script( 'sc-ei-public', SC_EI_URL . 'assets/js/public.js', array(), SC_EI_VERSION, true );
		self::$assets_enqueued = true;
	}
}
