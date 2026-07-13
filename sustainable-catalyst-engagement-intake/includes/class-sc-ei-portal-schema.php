<?php
/**
 * Secure sender portal taxonomies, defaults, permissions, and public labels.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Portal_Schema {

	public const COOKIE_NAME = '__Host-sc_ei_sender_session';
	public const LEGACY_COOKIE_NAME = 'sc_ei_sender_session';

	public static function access_statuses(): array {
		return array(
			'invited'   => __( 'Invitation Issued', 'sustainable-catalyst-engagement-intake' ),
			'active'    => __( 'Active', 'sustainable-catalyst-engagement-intake' ),
			'suspended' => __( 'Suspended', 'sustainable-catalyst-engagement-intake' ),
			'revoked'   => __( 'Revoked', 'sustainable-catalyst-engagement-intake' ),
			'expired'   => __( 'Expired', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function session_statuses(): array {
		return array(
			'active'  => __( 'Active', 'sustainable-catalyst-engagement-intake' ),
			'revoked' => __( 'Revoked', 'sustainable-catalyst-engagement-intake' ),
			'expired' => __( 'Expired', 'sustainable-catalyst-engagement-intake' ),
		);
	}


	public static function recovery_statuses(): array {
		return array(
			'pending'    => __( 'Pending Human Review', 'sustainable-catalyst-engagement-intake' ),
			'processing' => __( 'Recovery Processing', 'sustainable-catalyst-engagement-intake' ),
			'completed' => __( 'Fresh Invitation Issued', 'sustainable-catalyst-engagement-intake' ),
			'declined'  => __( 'Declined', 'sustainable-catalyst-engagement-intake' ),
			'expired'   => __( 'Expired', 'sustainable-catalyst-engagement-intake' ),
			'canceled'  => __( 'Canceled', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function recovery_match_statuses(): array {
		return array(
			'matched'   => __( 'Matched Existing Inquiry', 'sustainable-catalyst-engagement-intake' ),
			'unmatched' => __( 'No Match Recorded', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function invitation_states(): array {
		return array(
			'valid'      => __( 'Ready to Activate', 'sustainable-catalyst-engagement-intake' ),
			'expired'    => __( 'Invitation Expired', 'sustainable-catalyst-engagement-intake' ),
			'locked'     => __( 'Invitation Temporarily Locked', 'sustainable-catalyst-engagement-intake' ),
			'inactive'   => __( 'Invitation No Longer Active', 'sustainable-catalyst-engagement-intake' ),
			'invalid'    => __( 'Invitation Could Not Be Verified', 'sustainable-catalyst-engagement-intake' ),
			'https'      => __( 'Secure Connection Required', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function withdrawal_statuses(): array {
		return array(
			'none'         => __( 'No Withdrawal Request', 'sustainable-catalyst-engagement-intake' ),
			'requested'    => __( 'Withdrawal Requested', 'sustainable-catalyst-engagement-intake' ),
			'canceled'     => __( 'Withdrawal Request Canceled', 'sustainable-catalyst-engagement-intake' ),
			'acknowledged' => __( 'Withdrawal Acknowledged', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function views(): array {
		return array(
			'overview'    => __( 'Overview', 'sustainable-catalyst-engagement-intake' ),
			'messages'    => __( 'Secure Messages', 'sustainable-catalyst-engagement-intake' ),
			'documents'   => __( 'Private Documents', 'sustainable-catalyst-engagement-intake' ),
			'preferences' => __( 'Contact and Scheduling', 'sustainable-catalyst-engagement-intake' ),
			'privacy'     => __( 'Privacy and Withdrawal', 'sustainable-catalyst-engagement-intake' ),
			'access'      => __( 'Access and Security', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function permissions(): array {
		return array(
			'view_status'       => __( 'View sender-safe inquiry status', 'sustainable-catalyst-engagement-intake' ),
			'view_messages'     => __( 'View secure portal messages', 'sustainable-catalyst-engagement-intake' ),
			'send_messages'     => __( 'Send secure portal messages', 'sustainable-catalyst-engagement-intake' ),
			'view_documents'    => __( 'View private document metadata', 'sustainable-catalyst-engagement-intake' ),
			'upload_documents'  => __( 'Upload private follow-up documents', 'sustainable-catalyst-engagement-intake' ),
			'update_contact'    => __( 'Update contact preferences', 'sustainable-catalyst-engagement-intake' ),
			'update_scheduling' => __( 'Update Microsoft Teams scheduling preferences', 'sustainable-catalyst-engagement-intake' ),
			'privacy_requests'  => __( 'Submit privacy requests', 'sustainable-catalyst-engagement-intake' ),
			'request_withdrawal'=> __( 'Request or cancel inquiry withdrawal', 'sustainable-catalyst-engagement-intake' ),
			'revoke_access'     => __( 'Revoke own portal access', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function default_permissions(): array {
		return array_keys( self::permissions() );
	}

	public static function event_types(): array {
		return array(
			'invitation_issued'          => __( 'Invitation Issued', 'sustainable-catalyst-engagement-intake' ),
			'invitation_reissued'        => __( 'Invitation Reissued', 'sustainable-catalyst-engagement-intake' ),
			'invitation_failed'          => __( 'Invitation Activation Failed', 'sustainable-catalyst-engagement-intake' ),
			'invitation_token_rejected'  => __( 'Invitation Token Rejected', 'sustainable-catalyst-engagement-intake' ),
			'invitation_email_rejected'  => __( 'Invitation Email Challenge Rejected', 'sustainable-catalyst-engagement-intake' ),
			'invitation_locked'          => __( 'Invitation Locked', 'sustainable-catalyst-engagement-intake' ),
			'invitation_unlocked'        => __( 'Invitation Unlocked', 'sustainable-catalyst-engagement-intake' ),
			'invitation_activated'       => __( 'Invitation Activated', 'sustainable-catalyst-engagement-intake' ),
			'activation_rolled_back'     => __( 'Activation Rolled Back Safely', 'sustainable-catalyst-engagement-intake' ),
			'legacy_cookie_migrated'     => __( 'Legacy Session Cookie Migrated', 'sustainable-catalyst-engagement-intake' ),
			'recovery_requested'         => __( 'Portal Recovery Requested', 'sustainable-catalyst-engagement-intake' ),
			'recovery_request_unmatched' => __( 'Unmatched Recovery Attempt', 'sustainable-catalyst-engagement-intake' ),
			'recovery_request_throttled' => __( 'Recovery Request Throttled', 'sustainable-catalyst-engagement-intake' ),
			'recovery_completed'         => __( 'Portal Recovery Completed', 'sustainable-catalyst-engagement-intake' ),
			'recovery_declined'          => __( 'Portal Recovery Declined', 'sustainable-catalyst-engagement-intake' ),
			'recovery_expired'           => __( 'Portal Recovery Expired', 'sustainable-catalyst-engagement-intake' ),
			'session_created'            => __( 'Session Created', 'sustainable-catalyst-engagement-intake' ),
			'session_seen'               => __( 'Session Activity', 'sustainable-catalyst-engagement-intake' ),
			'session_revoked'            => __( 'Session Revoked', 'sustainable-catalyst-engagement-intake' ),
			'session_expired'            => __( 'Session Expired', 'sustainable-catalyst-engagement-intake' ),
			'access_suspended'           => __( 'Portal Access Suspended', 'sustainable-catalyst-engagement-intake' ),
			'access_resumed'             => __( 'Portal Access Resumed', 'sustainable-catalyst-engagement-intake' ),
			'access_revoked'             => __( 'Portal Access Revoked', 'sustainable-catalyst-engagement-intake' ),
			'sender_message_created'     => __( 'Sender Message Created', 'sustainable-catalyst-engagement-intake' ),
			'staff_message_created'      => __( 'Staff Portal Message Created', 'sustainable-catalyst-engagement-intake' ),
			'communication_published'    => __( 'Communication Published to Portal', 'sustainable-catalyst-engagement-intake' ),
			'communication_unpublished'  => __( 'Communication Hidden from Portal', 'sustainable-catalyst-engagement-intake' ),
			'document_uploaded'          => __( 'Portal Document Uploaded', 'sustainable-catalyst-engagement-intake' ),
			'contact_updated'            => __( 'Contact Preferences Updated', 'sustainable-catalyst-engagement-intake' ),
			'scheduling_updated'         => __( 'Scheduling Preferences Updated', 'sustainable-catalyst-engagement-intake' ),
			'privacy_request_created'    => __( 'Portal Privacy Request Created', 'sustainable-catalyst-engagement-intake' ),
			'withdrawal_requested'       => __( 'Inquiry Withdrawal Requested', 'sustainable-catalyst-engagement-intake' ),
			'withdrawal_canceled'        => __( 'Inquiry Withdrawal Canceled', 'sustainable-catalyst-engagement-intake' ),
			'rate_limit_triggered'       => __( 'Portal Rate Limit Triggered', 'sustainable-catalyst-engagement-intake' ),
			'csrf_rejected'              => __( 'Portal CSRF Rejected', 'sustainable-catalyst-engagement-intake' ),
			'permission_rejected'        => __( 'Portal Permission Rejected', 'sustainable-catalyst-engagement-intake' ),
			'privacy_state_rejected'     => __( 'Portal Privacy State Rejected', 'sustainable-catalyst-engagement-intake' ),
			'audit_exported'             => __( 'Portal Audit Exported', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function public_status_labels(): array {
		return array(
			'new'                      => __( 'Received', 'sustainable-catalyst-engagement-intake' ),
			'under_review'             => __( 'Under Review', 'sustainable-catalyst-engagement-intake' ),
			'more_information_needed'  => __( 'More Information Requested', 'sustainable-catalyst-engagement-intake' ),
			'fit_call_recommended'     => __( 'Fit Call Recommended', 'sustainable-catalyst-engagement-intake' ),
			'consultation_recommended' => __( 'Consultation Recommended', 'sustainable-catalyst-engagement-intake' ),
			'proposal_requested'       => __( 'Proposal Preparation', 'sustainable-catalyst-engagement-intake' ),
			'proposal_sent'            => __( 'Proposal Sent', 'sustainable-catalyst-engagement-intake' ),
			'accepted'                 => __( 'Engagement Accepted', 'sustainable-catalyst-engagement-intake' ),
			'not_a_fit'                => __( 'Closed After Review', 'sustainable-catalyst-engagement-intake' ),
			'referred'                 => __( 'Referral Guidance Available', 'sustainable-catalyst-engagement-intake' ),
			'withdrawn'                => __( 'Withdrawn', 'sustainable-catalyst-engagement-intake' ),
			'closed'                   => __( 'Closed', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function public_status_label( string $status ): string {
		$labels = self::public_status_labels();
		return $labels[ $status ] ?? __( 'In Review', 'sustainable-catalyst-engagement-intake' );
	}

	public static function default_settings(): array {
		return array(
			'portal_enabled'                    => 1,
			'portal_page_url'                   => home_url( '/sender-portal/' ),
			'portal_invite_ttl_hours'           => 72,
			'portal_session_ttl_minutes'        => 480,
			'portal_idle_timeout_minutes'       => 45,
			'portal_max_active_sessions'        => 3,
			'portal_max_failed_attempts'        => 5,
			'portal_lockout_minutes'            => 30,
			'portal_message_rate_limit_hour'    => 10,
			'portal_update_rate_limit_hour'     => 20,
			'portal_session_touch_seconds'      => 60,
			'portal_event_retention_days'       => 365,
			'portal_recovery_enabled'            => 1,
			'portal_recovery_requests_per_hour'  => 3,
			'portal_recovery_cooldown_minutes'   => 30,
			'portal_recovery_expiry_days'        => 7,
			'portal_recovery_min_reason_chars'   => 10,
			'portal_require_https'               => 1,
			'portal_allow_legacy_cookie'         => 1,
			'portal_allow_messages'             => 1,
			'portal_allow_documents'            => 1,
			'portal_allow_contact_updates'      => 1,
			'portal_allow_scheduling_updates'   => 1,
			'portal_allow_privacy_requests'     => 1,
			'portal_allow_withdrawal_requests'  => 1,
			'portal_require_email_challenge'    => 1,
			'portal_require_terms_acceptance'   => 1,
			'portal_terms_version'              => '1.0',
			'portal_default_permissions'        => self::default_permissions(),
			'portal_cookie_samesite'            => 'Strict',
			'portal_cookie_httponly'            => 1,
			'portal_noindex'                    => 1,
			'portal_no_store'                   => 1,
		);
	}

	public static function sanitize_permissions( $value ): array {
		$allowed = self::permissions();
		$items = is_array( $value ) ? $value : preg_split( '/[\s,;]+/', (string) $value );
		$result = array();
		foreach ( (array) $items as $item ) {
			$key = sanitize_key( (string) $item );
			if ( isset( $allowed[ $key ] ) ) {
				$result[ $key ] = $key;
			}
		}
		return array_values( $result );
	}

	public static function has_permission( array $access, string $permission ): bool {
		$permissions = json_decode( (string) ( $access['permissions_json'] ?? '[]' ), true );
		if ( ! is_array( $permissions ) ) {
			$permissions = array();
		}
		return in_array( sanitize_key( $permission ), $permissions, true );
	}

	public static function sanitize_access_status( string $status, string $fallback = 'invited' ): string {
		$status = sanitize_key( $status );
		return isset( self::access_statuses()[ $status ] ) ? $status : $fallback;
	}

	public static function sanitize_session_status( string $status, string $fallback = 'active' ): string {
		$status = sanitize_key( $status );
		return isset( self::session_statuses()[ $status ] ) ? $status : $fallback;
	}

	public static function sanitize_view( string $view ): string {
		$view = sanitize_key( $view );
		return isset( self::views()[ $view ] ) ? $view : 'overview';
	}

	public static function sanitize_portal_page_url( string $value ): string {
		$value = esc_url_raw( trim( $value ) );
		return $value ?: home_url( '/sender-portal/' );
	}

	public static function action_blocked_by_privacy( array $inquiry, string $action ): bool {
		$status = sanitize_key( (string) ( $inquiry['privacy_status'] ?? 'active' ) );
		if ( 'erased' === $status ) {
			return true;
		}
		if ( in_array( $status, array( 'restricted', 'erasure_requested' ), true ) ) {
			return ! in_array( $action, array( 'view_status', 'view_messages', 'privacy_requests', 'revoke_access' ), true );
		}
		return false;
	}

	public static function sanitize_recovery_status( string $status, string $fallback = 'pending' ): string {
		$status = sanitize_key( $status );
		return isset( self::recovery_statuses()[ $status ] ) ? $status : $fallback;
	}

	public static function secure_transport_available(): bool {
		if ( is_ssl() ) {
			return true;
		}
		$host = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) : '';
		$environment = function_exists( 'wp_get_environment_type' )
			? wp_get_environment_type()
			: ( defined( 'WP_ENVIRONMENT_TYPE' ) ? WP_ENVIRONMENT_TYPE : 'production' );
		$is_local_host = in_array( $host, array( 'localhost', '127.0.0.1', '[::1]' ), true )
			|| str_ends_with( $host, '.test' )
			|| str_ends_with( $host, '.local' );
		$is_local_environment = in_array( $environment, array( 'local', 'development' ), true );
		return (bool) apply_filters( 'sc_ei_portal_allow_insecure_local', $is_local_host && $is_local_environment );
	}

	public static function label( array $options, string $value ): string {
		return $options[ $value ] ?? ucwords( str_replace( '_', ' ', $value ) );
	}
}
