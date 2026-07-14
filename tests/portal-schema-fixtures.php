<?php
define( 'ABSPATH', __DIR__ . '/' );

function __( string $text, string $domain = '' ): string { return $text; }
function sanitize_key( string $value ): string { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ) ?: ''; }
function sanitize_text_field( string $value ): string { return trim( $value ); }
function wp_unslash( $value ) { return $value; }
function esc_url_raw( string $value ): string { return trim( $value ); }
function home_url( string $path = '' ): string { return 'https://example.test' . $path; }
function is_ssl(): bool { return true; }
function wp_get_environment_type(): string { return 'production'; }
function apply_filters( string $hook, $value ) { return $value; }

require_once dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake/includes/class-sc-ei-portal-schema.php';

function fail_portal( string $message ): void { fwrite( STDERR, $message . PHP_EOL ); exit( 1 ); }
function pass_portal( string $message ): void { echo 'PASS: ' . $message . PHP_EOL; }

$settings = SC_EI_Portal_Schema::default_settings();
foreach ( array(
	'portal_invite_ttl_hours',
	'portal_session_ttl_minutes',
	'portal_idle_timeout_minutes',
	'portal_max_active_sessions',
	'portal_max_failed_attempts',
	'portal_lockout_minutes',
	'portal_message_rate_limit_hour',
	'portal_update_rate_limit_hour',
	'portal_recovery_enabled',
	'portal_recovery_requests_per_hour',
	'portal_recovery_cooldown_minutes',
	'portal_recovery_expiry_days',
	'portal_recovery_min_reason_chars',
	'portal_require_https',
	'portal_allow_legacy_cookie',
	'portal_require_email_challenge',
	'portal_require_terms_acceptance',
	'portal_cookie_samesite',
	'portal_cookie_httponly',
	'portal_noindex',
	'portal_no_store',
) as $key ) {
	if ( ! array_key_exists( $key, $settings ) ) {
		fail_portal( 'Missing portal setting: ' . $key );
	}
}
pass_portal( 'complete authentication and recovery settings present' );

if (
	1 !== $settings['portal_require_https']
	|| 1 !== $settings['portal_recovery_enabled']
	|| 1 !== $settings['portal_require_email_challenge']
	|| 1 !== $settings['portal_require_terms_acceptance']
	|| 1 !== $settings['portal_cookie_httponly']
	|| 'Strict' !== $settings['portal_cookie_samesite']
	|| 1 !== $settings['portal_noindex']
	|| 1 !== $settings['portal_no_store']
) {
	fail_portal( 'Fixed portal security and recovery controls are not enabled.' );
}
pass_portal( 'HTTPS, recovery, email challenge, terms, HttpOnly, SameSite Strict, noindex, and no-store fixed on' );

if ( '__Host-sc_ei_sender_session' !== SC_EI_Portal_Schema::COOKIE_NAME ) {
	fail_portal( 'The production session cookie is not __Host-prefixed.' );
}
if ( 'sc_ei_sender_session' !== SC_EI_Portal_Schema::LEGACY_COOKIE_NAME ) {
	fail_portal( 'The v0.9.1 compatibility cookie is unavailable.' );
}
pass_portal( '__Host production cookie and explicit legacy migration cookie defined' );

$recovery_statuses = SC_EI_Portal_Schema::recovery_statuses();
foreach ( array( 'pending', 'processing', 'completed', 'declined', 'expired', 'canceled' ) as $status ) {
	if ( ! isset( $recovery_statuses[ $status ] ) ) {
		fail_portal( 'Missing recovery status: ' . $status );
	}
}
pass_portal( 'human-reviewed recovery lifecycle is explicit' );

$invitation_states = SC_EI_Portal_Schema::invitation_states();
foreach ( array( 'valid', 'expired', 'locked', 'inactive', 'invalid', 'https' ) as $state ) {
	if ( ! isset( $invitation_states[ $state ] ) ) {
		fail_portal( 'Missing invitation state: ' . $state );
	}
}
pass_portal( 'verified invitation states support clear recovery UX' );

$events = SC_EI_Portal_Schema::event_types();
foreach ( array(
	'invitation_token_rejected',
	'invitation_email_rejected',
	'activation_rolled_back',
	'legacy_cookie_migrated',
	'recovery_requested',
	'recovery_request_throttled',
	'recovery_completed',
	'recovery_declined',
) as $event ) {
	if ( ! isset( $events[ $event ] ) ) {
		fail_portal( 'Missing authentication or recovery event: ' . $event );
	}
}
pass_portal( 'authentication and recovery events are auditable' );

if ( ! SC_EI_Portal_Schema::secure_transport_available() ) {
	fail_portal( 'HTTPS transport fixture was not recognized.' );
}
pass_portal( 'secure transport helper recognizes HTTPS' );

$permissions = SC_EI_Portal_Schema::default_permissions();
if ( count( $permissions ) < 10 || ! in_array( 'revoke_access', $permissions, true ) ) {
	fail_portal( 'Portal permission model incomplete.' );
}
pass_portal( 'granular sender permission model remains intact' );

$restricted = array( 'privacy_status' => 'restricted' );
if (
	! SC_EI_Portal_Schema::action_blocked_by_privacy( $restricted, 'send_messages' )
	|| SC_EI_Portal_Schema::action_blocked_by_privacy( $restricted, 'privacy_requests' )
) {
	fail_portal( 'Privacy-aware action blocking failed.' );
}
pass_portal( 'privacy restrictions preserve recovery-adjacent privacy access without new processing' );

echo "Engagement Intake v0.9.2 portal schema fixtures passed.\n";
