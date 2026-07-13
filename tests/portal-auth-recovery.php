<?php
/**
 * Focused static safety assertions for v0.9.0 authentication and recovery.
 */

$root = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';

$repository = file_get_contents( $plugin . '/includes/class-sc-ei-portal-repository.php' );
$session    = file_get_contents( $plugin . '/includes/class-sc-ei-portal-session.php' );
$public     = file_get_contents( $plugin . '/includes/class-sc-ei-portal-public.php' );
$schema     = file_get_contents( $plugin . '/includes/class-sc-ei-portal-schema.php' );
$admin      = file_get_contents( $plugin . '/includes/class-sc-ei-portal-admin.php' );
$login      = file_get_contents( $plugin . '/public/views/sender-portal-login.php' );

$activation_start = strpos( $repository, 'public static function activate_invitation' );
$activation_end   = strpos( $repository, 'public static function create_session', $activation_start );
$activation       = substr( $repository, $activation_start, $activation_end - $activation_start );

$wrong_token_position = strpos( $activation, 'invitation_token_rejected' );
$failed_activation_position = strpos( $activation, 'register_failed_activation' );
if ( false === $wrong_token_position || false === $failed_activation_position || $wrong_token_position > $failed_activation_position ) {
	fwrite( STDERR, "Wrong-token rejection is not separated from email-challenge lockout.\n" );
	exit( 1 );
}
echo "PASS: wrong invitation token is rejected before any lockout increment\n";

if (
	false === strpos( $activation, "START TRANSACTION" )
	|| false === strpos( $activation, "create_session( \$fresh_access, false )" )
	|| false === strpos( $activation, "ROLLBACK" )
	|| false === strpos( $activation, "COMMIT" )
) {
	fwrite( STDERR, "Atomic activation boundaries are incomplete.\n" );
	exit( 1 );
}
echo "PASS: activation transaction covers access, inquiry, and session creation\n";

if (
	false === strpos( $activation, "'status'      => 'invited'" )
	|| false === strpos( $activation, "'row_version' => absint( \$access['row_version'] )" )
) {
	fwrite( STDERR, "Activation optimistic lock does not preserve invitation state.\n" );
	exit( 1 );
}
echo "PASS: activation uses optimistic invited-state locking\n";

if (
	false === strpos( $public, 'redirect_activation' )
	|| false === strpos( $public, 'portal_activation_form_expired' )
	|| false === strpos( $repository, 'portal_activation_retry' )
) {
	fwrite( STDERR, "Correctable activation failures do not preserve invitation context.\n" );
	exit( 1 );
}
echo "PASS: correctable activation failures return to the same invitation\n";

if (
	false === strpos( $schema, "__Host-sc_ei_sender_session" )
	|| false === strpos( $session, "'secure'   => true" )
	|| false === strpos( $session, "'path'     => '/'" )
	|| false !== strpos( $session, "'domain'" )
) {
	fwrite( STDERR, "__Host cookie constraints are incomplete.\n" );
	exit( 1 );
}
echo "PASS: production session cookie satisfies __Host constraints\n";

if (
	false === strpos( $session, 'LEGACY_COOKIE_NAME' )
	|| false === strpos( $session, 'legacy_cookie_migrated' )
	|| false === strpos( $session, 'clear_legacy_cookie' )
) {
	fwrite( STDERR, "Legacy v0.9.0 session migration is missing.\n" );
	exit( 1 );
}
echo "PASS: active v0.8.0 sessions can migrate to the __Host cookie\n";

if (
	false === strpos( $repository, 'request_recovery' )
	|| false === strpos( $repository, "'accepted' => true" )
	|| false === strpos( $login, 'never confirms whether' )
) {
	fwrite( STDERR, "Recovery response can reveal account or inquiry existence.\n" );
	exit( 1 );
}
echo "PASS: recovery response remains non-enumerating\n";

if (
	false === strpos( $repository, "event_type IN ('recovery_requested','recovery_request_unmatched')" )
	|| false === strpos( $repository, 'recovery_request_throttled' )
) {
	fwrite( STDERR, "Unmatched recovery attempts do not share the rate limit.\n" );
	exit( 1 );
}
echo "PASS: matched and unmatched recovery attempts share keyed-IP throttling\n";

if (
	false === strpos( $repository, "'automatic_invite_issued'  => false" )
	|| false === strpos( $repository, "'automatic_email_sent'     => false" )
	|| false === strpos( $admin, 'sc_intake_manage_portal_recovery' )
) {
	fwrite( STDERR, "Recovery is not fully human-controlled.\n" );
	exit( 1 );
}
echo "PASS: public recovery never issues or emails access automatically\n";

if (
	false === strpos( $repository, "'status'        => 'processing'" )
	|| false === strpos( $repository, 'Another reviewer already claimed' )
	|| false === strpos( $repository, "'status'      => 'processing'" )
) {
	fwrite( STDERR, "Recovery approval does not claim the request before invitation issuance.\n" );
	exit( 1 );
}
echo "PASS: concurrent recovery approvals are serialized through a processing claim\n";

if (
	false === strpos( $admin, "'RECOVER ' : 'DECLINE '" )
	|| false === strpos( $admin, "'UNLOCK ' . \$access_id" )
) {
	fwrite( STDERR, "Typed recovery and unlock confirmations are missing.\n" );
	exit( 1 );
}
echo "PASS: recovery approval, decline, and unlock require deliberate typed actions\n";

echo "Engagement Intake v0.9.0 focused authentication and recovery checks passed.\n";
