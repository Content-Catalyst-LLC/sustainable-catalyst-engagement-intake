<?php
/**
 * Passwordless sender portal session and CSRF controls.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Portal_Session {

	public static function current( bool $touch = true ) {
		$raw = isset( $_COOKIE[ SC_EI_Portal_Schema::COOKIE_NAME ] )
			? sanitize_text_field( wp_unslash( $_COOKIE[ SC_EI_Portal_Schema::COOKIE_NAME ] ) )
			: '';
		if ( '' === $raw ) {
			return new WP_Error( 'portal_session_missing', __( 'Secure sender portal access is required.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$session = SC_EI_Portal_Repository::find_session_by_hash( SC_EI_Portal_Repository::hash_secret( $raw ) );
		if ( ! $session || 'active' !== $session['status'] || ! empty( $session['revoked_at'] ) ) {
			self::clear_cookie();
			return new WP_Error( 'portal_session_invalid', __( 'The secure portal session is no longer active.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = time();
		if (
			strtotime( $session['expires_at'] . ' UTC' ) < $now
			|| strtotime( $session['idle_expires_at'] . ' UTC' ) < $now
		) {
			SC_EI_Portal_Repository::revoke_session( absint( $session['id'] ), 'Session expired.', 0 );
			SC_EI_Portal_Repository::record_event( 'session_expired', absint( $session['inquiry_id'] ), absint( $session['access_id'] ), absint( $session['id'] ), 'session', absint( $session['id'] ), 'expired' );
			self::clear_cookie();
			return new WP_Error( 'portal_session_expired', __( 'The secure portal session expired. Use a new invitation to continue.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$access = SC_EI_Portal_Repository::find_access( absint( $session['access_id'] ) );
		if ( ! $access || 'active' !== $access['status'] ) {
			self::clear_cookie();
			return new WP_Error( 'portal_access_inactive', __( 'Sender portal access is not active.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$current_agent = SC_EI_Portal_Repository::request_user_agent_hash();
		if ( $session['user_agent_hash'] && ! hash_equals( (string) $session['user_agent_hash'], $current_agent ) ) {
			SC_EI_Portal_Repository::record_event( 'session_revoked', absint( $session['inquiry_id'] ), absint( $session['access_id'] ), absint( $session['id'] ), 'session', absint( $session['id'] ), 'rejected', array( 'reason' => 'user_agent_changed' ) );
			SC_EI_Portal_Repository::revoke_session( absint( $session['id'] ), 'Browser identity changed.', 0 );
			self::clear_cookie();
			return new WP_Error( 'portal_session_browser_changed', __( 'The browser identity changed. Request a new portal invitation.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$inquiry = SC_EI_Inquiry_Repository::find( absint( $session['inquiry_id'] ) );
		if ( ! $inquiry || 'erased' === $inquiry['privacy_status'] ) {
			self::clear_cookie();
			return new WP_Error( 'portal_inquiry_unavailable', __( 'The linked inquiry is unavailable.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$current_ip = SC_EI_Portal_Repository::request_ip_hash();
		if ( $session['ip_hash'] && $current_ip && ! hash_equals( (string) $session['ip_hash'], $current_ip ) ) {
			SC_EI_Portal_Repository::record_event( 'session_seen', absint( $session['inquiry_id'] ), absint( $session['access_id'] ), absint( $session['id'] ), 'session', absint( $session['id'] ), 'recorded', array( 'ip_changed' => true ) );
		}
		if ( $touch ) {
			SC_EI_Portal_Repository::touch_session( $session );
		}
		return array(
			'access'             => $access,
			'session'            => $session,
			'inquiry'            => $inquiry,
			'permissions'        => json_decode( (string) $access['permissions_json'], true ) ?: array(),
			'_raw_session_token' => $raw,
		);
	}

	public static function has_permission( array $context, string $permission ): bool {
		return SC_EI_Portal_Schema::has_permission( $context['access'], $permission );
	}

	public static function require_permission( array $context, string $permission ) {
		if ( ! self::has_permission( $context, $permission ) ) {
			SC_EI_Portal_Repository::record_event(
				'permission_rejected',
				absint( $context['inquiry']['id'] ),
				absint( $context['access']['id'] ),
				absint( $context['session']['id'] ),
				'permission',
				0,
				'rejected',
				array( 'permission' => sanitize_key( $permission ) )
			);
			return new WP_Error( 'portal_permission_denied', __( 'This portal access does not permit that action.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( SC_EI_Portal_Schema::action_blocked_by_privacy( $context['inquiry'], $permission ) ) {
			SC_EI_Portal_Repository::record_event(
				'privacy_state_rejected',
				absint( $context['inquiry']['id'] ),
				absint( $context['access']['id'] ),
				absint( $context['session']['id'] ),
				'permission',
				0,
				'rejected',
				array(
					'permission'     => sanitize_key( $permission ),
					'privacy_status' => sanitize_key( $context['inquiry']['privacy_status'] ),
				)
			);
			return new WP_Error( 'portal_privacy_restriction', __( 'The inquiry privacy state currently blocks that action.', 'sustainable-catalyst-engagement-intake' ) );
		}
		return true;
	}

	public static function csrf_token( array $context ): string {
		$raw = (string) ( $context['_raw_session_token'] ?? '' );
		$session_public_id = (string) ( $context['session']['public_id'] ?? '' );
		return hash_hmac( 'sha256', 'csrf|' . $session_public_id, $raw . '|' . wp_salt( 'nonce' ) );
	}

	public static function verify_csrf( array $context, string $provided ): bool {
		$expected = self::csrf_token( $context );
		$valid = $provided && hash_equals( $expected, sanitize_text_field( $provided ) );
		if ( ! $valid ) {
			SC_EI_Portal_Repository::record_event(
				'csrf_rejected',
				absint( $context['inquiry']['id'] ),
				absint( $context['access']['id'] ),
				absint( $context['session']['id'] ),
				'session',
				absint( $context['session']['id'] ),
				'rejected'
			);
		}
		return $valid;
	}

	public static function set_cookie( string $raw_token, string $expires_at ): bool {
		$expires = strtotime( $expires_at . ' UTC' );
		if ( ! $expires ) {
			return false;
		}
		$options = array(
			'expires'  => $expires,
			'path'     => '/',
			'domain'   => defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
			'secure'   => is_ssl(),
			'httponly' => true,
			'samesite' => 'Strict',
		);
		$set = setcookie( SC_EI_Portal_Schema::COOKIE_NAME, $raw_token, $options );
		if ( $set ) {
			$_COOKIE[ SC_EI_Portal_Schema::COOKIE_NAME ] = $raw_token;
		}
		return $set;
	}

	public static function clear_cookie(): void {
		setcookie(
			SC_EI_Portal_Schema::COOKIE_NAME,
			'',
			array(
				'expires'  => time() - HOUR_IN_SECONDS,
				'path'     => '/',
				'domain'   => defined( 'COOKIE_DOMAIN' ) && COOKIE_DOMAIN ? COOKIE_DOMAIN : '',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Strict',
			)
		);
		unset( $_COOKIE[ SC_EI_Portal_Schema::COOKIE_NAME ] );
	}
}
