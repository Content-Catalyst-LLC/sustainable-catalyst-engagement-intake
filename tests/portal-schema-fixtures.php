<?php
define( 'ABSPATH', __DIR__ . '/' );
function __( string $text, string $domain = '' ): string { return $text; }
function sanitize_key( string $value ): string { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ) ?: ''; }
function esc_url_raw( string $value ): string { return trim( $value ); }
function home_url( string $path = '' ): string { return 'https://example.test' . $path; }

require_once dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake/includes/class-sc-ei-portal-schema.php';

function fail_portal( string $message ): void { fwrite( STDERR, $message . PHP_EOL ); exit( 1 ); }
function pass_portal( string $message ): void { echo 'PASS: ' . $message . PHP_EOL; }

$settings = SC_EI_Portal_Schema::default_settings();
foreach ( array(
	'portal_invite_ttl_hours', 'portal_session_ttl_minutes', 'portal_idle_timeout_minutes',
	'portal_max_active_sessions', 'portal_max_failed_attempts', 'portal_lockout_minutes',
	'portal_message_rate_limit_hour', 'portal_update_rate_limit_hour',
	'portal_require_email_challenge', 'portal_require_terms_acceptance',
	'portal_cookie_samesite', 'portal_cookie_httponly', 'portal_noindex', 'portal_no_store',
) as $key ) {
	if ( ! array_key_exists( $key, $settings ) ) fail_portal( 'Missing portal setting: ' . $key );
}
pass_portal( 'complete portal security settings present' );

if (
	1 !== $settings['portal_require_email_challenge']
	|| 1 !== $settings['portal_require_terms_acceptance']
	|| 1 !== $settings['portal_cookie_httponly']
	|| 'Strict' !== $settings['portal_cookie_samesite']
	|| 1 !== $settings['portal_noindex']
	|| 1 !== $settings['portal_no_store']
) fail_portal( 'Fixed portal security controls are not enabled.' );
pass_portal( 'email challenge, terms, HttpOnly, SameSite Strict, noindex, and no-store fixed on' );

$permissions = SC_EI_Portal_Schema::default_permissions();
if ( count( $permissions ) < 10 || ! in_array( 'revoke_access', $permissions, true ) ) fail_portal( 'Portal permission model incomplete.' );
pass_portal( 'granular sender permission model present' );

$restricted = array( 'privacy_status' => 'restricted' );
if (
	! SC_EI_Portal_Schema::action_blocked_by_privacy( $restricted, 'send_messages' )
	|| SC_EI_Portal_Schema::action_blocked_by_privacy( $restricted, 'privacy_requests' )
) fail_portal( 'Privacy-aware action blocking failed.' );
pass_portal( 'restricted inquiries block new processing but retain privacy access' );

if ( 'Closed After Review' !== SC_EI_Portal_Schema::public_status_label( 'not_a_fit' ) ) fail_portal( 'Sender-safe status mapping failed.' );
pass_portal( 'internal status labels are translated into sender-safe language' );

echo "Engagement Intake v0.8.0 portal schema fixtures passed.\n";
