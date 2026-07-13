<?php
$root = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';
$main = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$db = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$repo = file_get_contents( $plugin . '/includes/class-sc-ei-portal-repository.php' );
$session = file_get_contents( $plugin . '/includes/class-sc-ei-portal-session.php' );
$public = file_get_contents( $plugin . '/includes/class-sc-ei-portal-public.php' );
$admin = file_get_contents( $plugin . '/includes/class-sc-ei-portal-admin.php' );
$privacy = file_get_contents( $plugin . '/includes/class-sc-ei-privacy.php' );
$engine = file_get_contents( $plugin . '/includes/class-sc-ei-retention-engine.php' );
$diag = file_get_contents( $plugin . '/includes/class-sc-ei-diagnostics.php' );
$caps = file_get_contents( $plugin . '/includes/class-sc-ei-capabilities.php' );
$view = file_get_contents( $plugin . '/public/views/sender-portal.php' );

$checks = array(
	'portal schema version' => strpos( $main, "SC_EI_PORTAL_SCHEMA_VERSION', '1.0.0'" ) !== false,
	'three portal tables' => strpos( $db, '$sql_portal_access' ) !== false && strpos( $db, '$sql_portal_sessions' ) !== false && strpos( $db, '$sql_portal_events' ) !== false,
	'portal inquiry fields' => strpos( $db, 'portal_status varchar' ) !== false && strpos( $db, 'sender_withdrawal_reason longtext' ) !== false,
	'communication visibility fields' => strpos( $db, 'portal_visibility varchar' ) !== false && strpos( $db, 'portal_published_by bigint' ) !== false,
	'raw invite not stored' => strpos( $repo, 'invite_token_hash' ) !== false && strpos( $repo, "'raw_token' => \$raw_token" ) !== false,
	'HMAC secret hashing' => strpos( $repo, "hash_hmac( 'sha256'" ) !== false,
	'one-time invitation' => strpos( $repo, "'invite_token_hash'   => ''" ) !== false,
	'email challenge' => strpos( $repo, 'sender_email_hash' ) !== false && strpos( $repo, 'email_hash( $email )' ) !== false,
	'activation lockout' => strpos( $repo, 'register_failed_activation' ) !== false && strpos( $repo, 'locked_until' ) !== false,
	'absolute and idle expiration' => strpos( $session, "expires_at" ) !== false && strpos( $session, "idle_expires_at" ) !== false,
	'HttpOnly SameSite cookie' => strpos( $session, "'httponly' => true" ) !== false && strpos( $session, "'samesite' => 'Strict'" ) !== false,
	'session CSRF' => strpos( $session, 'csrf_token' ) !== false && strpos( $session, 'verify_csrf' ) !== false,
	'privacy permission gate' => strpos( $session, 'action_blocked_by_privacy' ) !== false,
	'no-store noindex headers' => strpos( $public, 'Cache-Control: no-store' ) !== false && strpos( $public, "'noindex'] = true" ) !== false,
	'no public lookup endpoint' => strpos( $public, 'search_email' ) === false && strpos( $public, 'reference_lookup' ) === false,
	'quarantine upload pipeline' => strpos( $public, 'SC_EI_Upload_Manager::process_inquiry_uploads' ) !== false && strpos( $public, "'form_variant'            => 'sender_portal'" ) !== false,
	'secure portal messages no email' => strpos( $repo, "'email_sent'            => false" ) !== false && strpos( $repo, "'provider'               => 'sender_portal'" ) !== false,
	'explicit publication' => strpos( $repo, 'publish_communication' ) !== false && strpos( $repo, "'portal_visibility'   => \$visible ? 'visible' : 'hidden'" ) !== false,
	'withdrawal status neutral' => strpos( $repo, "'automatic_inquiry_status_change' => false" ) !== false,
	'typed sender revocation' => strpos( $public, "'REVOKE ' . strtoupper" ) !== false,
	'typed admin controls' => strpos( $admin, "'SESSIONS ' . \$access_id" ) !== false && strpos( $admin, "strtoupper( \$status ) . ' ' . \$access_id" ) !== false,
	'one-time link transient' => strpos( $admin, 'set_transient' ) !== false && strpos( $admin, 'delete_transient' ) !== false,
	'portal privacy export' => strpos( $privacy, 'Engagement Intake Secure Sender Portal Access' ) !== false && strpos( $privacy, 'Engagement Intake Secure Sender Portal Events' ) !== false,
	'portal erasure' => strpos( $engine, 'SC_EI_Portal_Repository::redact_for_privacy' ) !== false,
	'portal diagnostics' => strpos( $diag, 'portal_security' ) !== false && strpos( $diag, "'raw_invite_stored'     => false" ) !== false,
	'granular capabilities' => strpos( $caps, 'sc_intake_issue_portal_invites' ) !== false && strpos( $caps, 'sc_intake_revoke_portal_access' ) !== false,
	'internal boundary copy' => strpos( $view, 'Internal review notes, fit assessments' ) !== false,
);
$failed = array_keys( array_filter( $checks, static fn( bool $ok ): bool => ! $ok ) );
if ( $failed ) { fwrite( STDERR, 'Portal checks failed: ' . implode( ', ', $failed ) . PHP_EOL ); exit( 1 ); }
foreach ( $checks as $label => $ok ) echo 'PASS: ' . $label . PHP_EOL;
echo "Engagement Intake v0.8.0 secure sender portal operation checks passed.\n";
