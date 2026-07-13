<?php
/**
 * Secure sender portal administration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Portal_Admin {

	public static function register(): void {
		add_action( 'admin_post_sc_ei_issue_portal_invite', array( __CLASS__, 'handle_issue' ) );
		add_action( 'admin_post_sc_ei_change_portal_access', array( __CLASS__, 'handle_access' ) );
		add_action( 'admin_post_sc_ei_revoke_portal_sessions', array( __CLASS__, 'handle_sessions' ) );
		add_action( 'admin_post_sc_ei_post_portal_reply', array( __CLASS__, 'handle_reply' ) );
		add_action( 'admin_post_sc_ei_publish_portal_communication', array( __CLASS__, 'handle_publish' ) );
		add_action( 'admin_post_sc_ei_export_portal_audit', array( __CLASS__, 'handle_export' ) );
		add_action( 'admin_post_sc_ei_save_portal_settings', array( __CLASS__, 'handle_settings' ) );
		add_action( 'admin_post_sc_ei_review_portal_recovery', array( __CLASS__, 'handle_recovery' ) );
		add_action( 'admin_post_sc_ei_unlock_portal_access', array( __CLASS__, 'handle_unlock' ) );
	}

	public static function submenu(): void {
		add_submenu_page(
			'sc-engagement-intake',
			__( 'Secure Sender Portal', 'sustainable-catalyst-engagement-intake' ),
			__( 'Sender Portal', 'sustainable-catalyst-engagement-intake' ),
			'sc_intake_view_sender_portal',
			'sc-engagement-intake-portal',
			array( __CLASS__, 'page' )
		);
	}

	public static function page(): void {
		if ( ! current_user_can( 'sc_intake_view_sender_portal' ) ) {
			wp_die( esc_html__( 'You do not have permission to view sender portal records.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}
		$access_id = isset( $_GET['access'] ) ? absint( $_GET['access'] ) : 0;
		if ( $access_id ) {
			self::detail( $access_id );
			return;
		}

		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$recovery_status = isset( $_GET['recovery_status'] ) ? sanitize_key( wp_unslash( $_GET['recovery_status'] ) ) : 'pending';
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$message = isset( $_GET['sc_ei_msg'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_msg'] ) ) : '';
		$settings = SC_EI_Portal_Repository::settings();
		$metrics = SC_EI_Portal_Repository::metrics();
		$access_records = SC_EI_Portal_Repository::query_access(
			array(
				'status' => $status,
				'search' => $search,
				'limit'  => 250,
			)
		);
		$recovery_requests = current_user_can( 'sc_intake_view_portal_recovery' )
			? SC_EI_Portal_Repository::recovery_requests(
				array(
					'status' => $recovery_status,
					'limit'  => 250,
				)
			)
			: array();
		include SC_EI_DIR . 'admin/views/sender-portal.php';
	}

	private static function detail( int $access_id ): void {
		$access = SC_EI_Portal_Repository::find_access( $access_id );
		if ( ! $access ) {
			wp_die( esc_html__( 'The sender portal access record could not be found.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 404 ) );
		}
		$inquiry = SC_EI_Inquiry_Repository::find( absint( $access['inquiry_id'] ) );
		if ( ! $inquiry ) {
			wp_die( esc_html__( 'The linked inquiry could not be found.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 404 ) );
		}
		$message = isset( $_GET['sc_ei_msg'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_msg'] ) ) : '';
		$settings = SC_EI_Portal_Repository::settings();
		$sessions = SC_EI_Portal_Repository::sessions( $access_id, false );
		$events = SC_EI_Portal_Repository::events( array( 'access_id' => $access_id, 'limit' => 500 ) );
		$recovery_requests = current_user_can( 'sc_intake_view_portal_recovery' )
			? SC_EI_Portal_Repository::recovery_requests( array( 'access_id' => $access_id, 'limit' => 250 ) )
			: array();
		$portal_messages = SC_EI_Portal_Repository::portal_messages( absint( $inquiry['id'] ), 500 );
		$all_communications = SC_EI_Communication_Repository::for_inquiry( absint( $inquiry['id'] ), 500, false );
		$publishable = array_values(
			array_filter(
				$all_communications,
				static fn( array $communication ): bool =>
					'outbound' === $communication['direction']
					&& 'hidden' === ( $communication['portal_visibility'] ?? 'hidden' )
					&& ! in_array( $communication['status'], array( 'draft', 'failed', 'canceled', 'suppressed' ), true )
			)
		);
		$one_time_link = get_transient( self::link_transient_key( $access_id ) );
		if ( $one_time_link ) {
			delete_transient( self::link_transient_key( $access_id ) );
		}
		include SC_EI_DIR . 'admin/views/sender-portal-detail.php';
	}

	public static function url( int $access_id = 0, array $args = array() ): string {
		$query = array_merge( array( 'page' => 'sc-engagement-intake-portal' ), $args );
		if ( $access_id ) {
			$query['access'] = $access_id;
		}
		return add_query_arg( $query, admin_url( 'admin.php' ) );
	}

	public static function handle_issue(): void {
		self::require_cap( 'sc_intake_issue_portal_invites' );
		check_admin_referer( 'sc_ei_issue_portal_invite' );
		$inquiry_id = absint( $_POST['inquiry_id'] ?? 0 );
		$result = SC_EI_Portal_Repository::issue_invitation(
			$inquiry_id,
			array(
				'invite_ttl_hours' => absint( $_POST['invite_ttl_hours'] ?? 72 ),
				'permissions'      => isset( $_POST['portal_permissions'] ) ? (array) wp_unslash( $_POST['portal_permissions'] ) : SC_EI_Portal_Schema::default_permissions(),
				'invitation_note'  => isset( $_POST['invitation_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['invitation_note'] ) ) : '',
			),
			get_current_user_id()
		);
		if ( is_wp_error( $result ) ) {
			self::redirect( $redirect_access_id, $result->get_error_code() );
		}
		$access_id = absint( $result['access']['id'] );
		set_transient(
			self::link_transient_key( $access_id ),
			array(
				'url'        => esc_url_raw( $result['url'] ),
				'expires_at' => sanitize_text_field( $result['expires_at'] ),
			),
			5 * MINUTE_IN_SECONDS
		);
		self::redirect( $access_id, 'portal_invitation_issued' );
	}

	public static function handle_access(): void {
		self::require_cap( 'sc_intake_revoke_portal_access' );
		$access_id = absint( $_POST['access_id'] ?? 0 );
		check_admin_referer( 'sc_ei_change_portal_access_' . $access_id );
		$status = isset( $_POST['portal_status'] ) ? sanitize_key( wp_unslash( $_POST['portal_status'] ) ) : '';
		$reason = isset( $_POST['access_reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['access_reason'] ) ) : '';
		if ( in_array( $status, array( 'suspended', 'revoked' ), true ) ) {
			$expected = strtoupper( $status ) . ' ' . $access_id;
			$provided = strtoupper( trim( (string) ( $_POST['access_confirmation'] ?? '' ) ) );
			if ( ! hash_equals( $expected, $provided ) ) {
				self::redirect( $access_id, 'portal_access_confirmation_failed' );
			}
		}
		$result = SC_EI_Portal_Repository::change_access_status( $access_id, $status, $reason, get_current_user_id() );
		self::redirect( $access_id, is_wp_error( $result ) ? $result->get_error_code() : 'portal_access_updated' );
	}

	public static function handle_sessions(): void {
		self::require_cap( 'sc_intake_revoke_portal_access' );
		$access_id = absint( $_POST['access_id'] ?? 0 );
		check_admin_referer( 'sc_ei_revoke_portal_sessions_' . $access_id );
		$expected = 'SESSIONS ' . $access_id;
		$provided = strtoupper( trim( (string) ( $_POST['session_confirmation'] ?? '' ) ) );
		if ( ! hash_equals( $expected, $provided ) ) {
			self::redirect( $access_id, 'portal_session_confirmation_failed' );
		}
		SC_EI_Portal_Repository::revoke_sessions(
			$access_id,
			isset( $_POST['session_reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['session_reason'] ) ) : 'Administrative session revocation.',
			get_current_user_id()
		);
		self::redirect( $access_id, 'portal_sessions_revoked' );
	}

	public static function handle_recovery(): void {
		self::require_cap( 'sc_intake_manage_portal_recovery' );
		$recovery_id = absint( $_POST['recovery_id'] ?? 0 );
		check_admin_referer( 'sc_ei_review_portal_recovery_' . $recovery_id );
		$existing_recovery = SC_EI_Portal_Repository::find_recovery( $recovery_id );
		$redirect_access_id = absint( $existing_recovery['access_id'] ?? 0 );
		$decision = isset( $_POST['recovery_decision'] ) ? sanitize_key( wp_unslash( $_POST['recovery_decision'] ) ) : '';
		$expected = ( 'complete' === $decision ? 'RECOVER ' : 'DECLINE ' ) . $recovery_id;
		$provided = strtoupper( trim( (string) ( $_POST['recovery_confirmation'] ?? '' ) ) );
		if ( ! hash_equals( $expected, $provided ) ) {
			self::redirect( $redirect_access_id, 'portal_recovery_confirmation_failed' );
		}
		$note = isset( $_POST['recovery_decision_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['recovery_decision_note'] ) ) : '';
		$result = SC_EI_Portal_Repository::review_recovery( $recovery_id, $decision, $note, get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			self::redirect( 0, $result->get_error_code() );
		}
		$recovery = $result['recovery'] ?? SC_EI_Portal_Repository::find_recovery( $recovery_id );
		$access_id = absint( $recovery['access_id'] ?? 0 );
		if ( ! empty( $result['invitation'] ) && $access_id ) {
			set_transient(
				self::link_transient_key( $access_id ),
				array(
					'url'        => esc_url_raw( $result['invitation']['url'] ),
					'expires_at' => sanitize_text_field( $result['invitation']['expires_at'] ),
				),
				5 * MINUTE_IN_SECONDS
			);
		}
		$message = 'complete' === $decision ? 'portal_recovery_completed' : 'portal_recovery_declined';
		if ( ! empty( $result['warning'] ) ) {
			$message = sanitize_key( $result['warning'] );
		}
		self::redirect( $access_id, $message );
	}

	public static function handle_unlock(): void {
		self::require_cap( 'sc_intake_manage_portal_recovery' );
		$access_id = absint( $_POST['access_id'] ?? 0 );
		check_admin_referer( 'sc_ei_unlock_portal_access_' . $access_id );
		$expected = 'UNLOCK ' . $access_id;
		$provided = strtoupper( trim( (string) ( $_POST['unlock_confirmation'] ?? '' ) ) );
		if ( ! hash_equals( $expected, $provided ) ) {
			self::redirect( $access_id, 'portal_unlock_confirmation_failed' );
		}
		$result = SC_EI_Portal_Repository::unlock_access(
			$access_id,
			isset( $_POST['unlock_reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['unlock_reason'] ) ) : '',
			get_current_user_id()
		);
		self::redirect( $access_id, is_wp_error( $result ) ? $result->get_error_code() : 'portal_invitation_unlocked' );
	}

	public static function handle_reply(): void {
		self::require_cap( 'sc_intake_post_portal_messages' );
		$access_id = absint( $_POST['access_id'] ?? 0 );
		check_admin_referer( 'sc_ei_post_portal_reply_' . $access_id );
		$access = SC_EI_Portal_Repository::find_access( $access_id );
		if ( ! $access ) {
			self::redirect( 0, 'portal_access_not_found' );
		}
		$result = SC_EI_Portal_Repository::create_portal_message(
			absint( $access['inquiry_id'] ),
			'outbound',
			isset( $_POST['portal_reply'] ) ? wp_unslash( $_POST['portal_reply'] ) : '',
			get_current_user_id(),
			absint( $_POST['reply_to_id'] ?? 0 )
		);
		self::redirect( $access_id, is_wp_error( $result ) ? $result->get_error_code() : 'portal_reply_recorded' );
	}

	public static function handle_publish(): void {
		self::require_cap( 'sc_intake_post_portal_messages' );
		$communication_id = absint( $_POST['communication_id'] ?? 0 );
		$access_id = absint( $_POST['access_id'] ?? 0 );
		check_admin_referer( 'sc_ei_publish_portal_communication_' . $communication_id );
		$visible = ! empty( $_POST['portal_visible'] );
		$result = SC_EI_Portal_Repository::publish_communication( $communication_id, $visible, get_current_user_id() );
		self::redirect( $access_id, is_wp_error( $result ) ? $result->get_error_code() : ( $visible ? 'portal_communication_published' : 'portal_communication_hidden' ) );
	}

	public static function handle_export(): void {
		self::require_cap( 'sc_intake_export_portal_audit' );
		$access_id = absint( $_GET['access'] ?? 0 );
		check_admin_referer( 'sc_ei_export_portal_audit_' . $access_id );
		$access = SC_EI_Portal_Repository::find_access( $access_id );
		if ( ! $access ) {
			wp_die( esc_html__( 'The portal audit export could not be generated.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 404 ) );
		}
		$inquiry = SC_EI_Inquiry_Repository::find( absint( $access['inquiry_id'] ) );
		$packet = array(
			'schema'        => 'sc-engagement-intake-sender-portal-audit/1.1',
			'generated_at'  => current_time( 'mysql', true ),
			'portal_schema' => SC_EI_PORTAL_SCHEMA_VERSION,
			'security'      => array(
				'raw_invite_stored'   => false,
				'raw_session_stored'  => false,
				'ip_plaintext_stored' => false,
				'user_agent_plaintext_stored' => false,
				'wordpress_account_required' => false,
				'automatic_email'     => false,
				'atomic_activation'   => true,
				'wrong_token_lockout' => false,
				'human_recovery'      => true,
				'cookie_name'         => SC_EI_Portal_Schema::COOKIE_NAME,
			),
			'inquiry'       => $inquiry,
			'portal'        => SC_EI_Portal_Repository::export_for_inquiry( absint( $access['inquiry_id'] ) ),
			'messages'      => SC_EI_Portal_Repository::portal_messages( absint( $access['inquiry_id'] ), 500 ),
		);
		SC_EI_Portal_Repository::record_event( 'audit_exported', absint( $access['inquiry_id'] ), $access_id, 0, 'access', $access_id, 'success', array( 'actor_user_id' => get_current_user_id() ) );
		SC_EI_Audit_Log::record( 'portal_audit_exported', 'Authorized user exported private sender portal audit data.', array( 'access_id' => $access_id ), absint( $access['inquiry_id'] ), null, get_current_user_id() );
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="sender-portal-audit-' . sanitize_file_name( $inquiry['reference'] ) . '-' . gmdate( 'Y-m-d-His' ) . '.json"' );
		header( 'X-Content-Type-Options: nosniff' );
		echo wp_json_encode( $packet, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		exit;
	}

	public static function handle_settings(): void {
		self::require_cap( 'sc_intake_manage_portal_settings' );
		check_admin_referer( 'sc_ei_save_portal_settings' );
		$current = SC_EI_Portal_Repository::settings();
		$raw = isset( $_POST['portal_settings'] ) ? (array) wp_unslash( $_POST['portal_settings'] ) : array();
		$settings = array(
			'portal_enabled'                   => 1,
			'portal_page_url'                  => SC_EI_Portal_Schema::sanitize_portal_page_url( (string) ( $raw['portal_page_url'] ?? $current['portal_page_url'] ) ),
			'portal_invite_ttl_hours'          => max( 1, min( 720, absint( $raw['portal_invite_ttl_hours'] ?? $current['portal_invite_ttl_hours'] ) ) ),
			'portal_session_ttl_minutes'       => max( 30, min( 4320, absint( $raw['portal_session_ttl_minutes'] ?? $current['portal_session_ttl_minutes'] ) ) ),
			'portal_idle_timeout_minutes'      => max( 5, min( 1440, absint( $raw['portal_idle_timeout_minutes'] ?? $current['portal_idle_timeout_minutes'] ) ) ),
			'portal_max_active_sessions'       => max( 1, min( 10, absint( $raw['portal_max_active_sessions'] ?? $current['portal_max_active_sessions'] ) ) ),
			'portal_max_failed_attempts'       => max( 1, min( 20, absint( $raw['portal_max_failed_attempts'] ?? $current['portal_max_failed_attempts'] ) ) ),
			'portal_lockout_minutes'           => max( 1, min( 1440, absint( $raw['portal_lockout_minutes'] ?? $current['portal_lockout_minutes'] ) ) ),
			'portal_message_rate_limit_hour'   => max( 1, min( 100, absint( $raw['portal_message_rate_limit_hour'] ?? $current['portal_message_rate_limit_hour'] ) ) ),
			'portal_update_rate_limit_hour'    => max( 1, min( 200, absint( $raw['portal_update_rate_limit_hour'] ?? $current['portal_update_rate_limit_hour'] ) ) ),
			'portal_event_retention_days'      => max( 30, min( 3650, absint( $raw['portal_event_retention_days'] ?? $current['portal_event_retention_days'] ) ) ),
			'portal_recovery_enabled'           => 1,
			'portal_recovery_requests_per_hour' => max( 1, min( 20, absint( $raw['portal_recovery_requests_per_hour'] ?? $current['portal_recovery_requests_per_hour'] ) ) ),
			'portal_recovery_cooldown_minutes'  => max( 1, min( 1440, absint( $raw['portal_recovery_cooldown_minutes'] ?? $current['portal_recovery_cooldown_minutes'] ) ) ),
			'portal_recovery_expiry_days'       => max( 1, min( 90, absint( $raw['portal_recovery_expiry_days'] ?? $current['portal_recovery_expiry_days'] ) ) ),
			'portal_recovery_min_reason_chars'  => max( 0, min( 500, absint( $raw['portal_recovery_min_reason_chars'] ?? $current['portal_recovery_min_reason_chars'] ) ) ),
			'portal_require_https'              => 1,
			'portal_allow_legacy_cookie'        => 1,
			'portal_allow_messages'            => empty( $raw['portal_allow_messages'] ) ? 0 : 1,
			'portal_allow_documents'           => empty( $raw['portal_allow_documents'] ) ? 0 : 1,
			'portal_allow_contact_updates'     => empty( $raw['portal_allow_contact_updates'] ) ? 0 : 1,
			'portal_allow_scheduling_updates'  => empty( $raw['portal_allow_scheduling_updates'] ) ? 0 : 1,
			'portal_allow_privacy_requests'    => empty( $raw['portal_allow_privacy_requests'] ) ? 0 : 1,
			'portal_allow_withdrawal_requests' => empty( $raw['portal_allow_withdrawal_requests'] ) ? 0 : 1,
			'portal_require_email_challenge'   => 1,
			'portal_require_terms_acceptance'  => 1,
			'portal_terms_version'             => sanitize_text_field( (string) ( $raw['portal_terms_version'] ?? $current['portal_terms_version'] ) ),
			'portal_default_permissions'       => SC_EI_Portal_Schema::sanitize_permissions( $raw['portal_default_permissions'] ?? $current['portal_default_permissions'] ),
			'portal_cookie_samesite'           => 'Strict',
			'portal_cookie_httponly'           => 1,
			'portal_noindex'                   => 1,
			'portal_no_store'                  => 1,
		);
		update_option( 'sc_ei_settings', array_merge( $current, $settings ), false );
		SC_EI_Audit_Log::record(
			'portal_settings_updated',
			'Secure sender portal settings updated.',
			array(
				'page_url'        => $settings['portal_page_url'],
				'invite_hours'    => $settings['portal_invite_ttl_hours'],
				'session_minutes' => $settings['portal_session_ttl_minutes'],
				'idle_minutes'    => $settings['portal_idle_timeout_minutes'],
				'email_challenge' => true,
				'httponly'        => true,
				'samesite'        => 'Strict',
				'automatic_email' => false,
				'recovery_enabled'=> true,
				'recovery_limit'  => $settings['portal_recovery_requests_per_hour'],
				'require_https'   => true,
				'host_cookie'     => SC_EI_Portal_Schema::COOKIE_NAME,
			),
			null,
			null,
			get_current_user_id()
		);
		self::redirect( 0, 'portal_settings_saved' );
	}

	private static function link_transient_key( int $access_id ): string {
		return 'sc_ei_portal_link_' . get_current_user_id() . '_' . $access_id;
	}

	private static function require_cap( string $capability ): void {
		if ( ! current_user_can( $capability ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this sender portal operation.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}
	}

	private static function redirect( int $access_id, string $message ): void {
		wp_safe_redirect( self::url( $access_id, array( 'sc_ei_msg' => sanitize_key( $message ) ) ), 303 );
		exit;
	}
}
