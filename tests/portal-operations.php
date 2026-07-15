<?php
$root = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';

$main       = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$db         = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$repo       = file_get_contents( $plugin . '/includes/class-sc-ei-portal-repository.php' );
$session    = file_get_contents( $plugin . '/includes/class-sc-ei-portal-session.php' );
$public     = file_get_contents( $plugin . '/includes/class-sc-ei-portal-public.php' );
$admin      = file_get_contents( $plugin . '/includes/class-sc-ei-portal-admin.php' );
$privacy    = file_get_contents( $plugin . '/includes/class-sc-ei-privacy.php' );
$inventory  = file_get_contents( $plugin . '/includes/class-sc-ei-privacy-repository.php' );
$engine     = file_get_contents( $plugin . '/includes/class-sc-ei-retention-engine.php' );
$diag       = file_get_contents( $plugin . '/includes/class-sc-ei-diagnostics.php' );
$caps       = file_get_contents( $plugin . '/includes/class-sc-ei-capabilities.php' );
$login_view = file_get_contents( $plugin . '/public/views/sender-portal-login.php' );
$portal_view= file_get_contents( $plugin . '/public/views/sender-portal.php' );

$checks = array(
	'v1.1.1 plugin and database markers' => strpos( $main, 'Version:     1.7.0' ) !== false
		&& strpos( $main, "SC_EI_DB_VERSION', '1.7.0'" ) !== false,
	'portal schema 1.4.0' => strpos( $main, "SC_EI_PORTAL_SCHEMA_VERSION', '1.8.0'" ) !== false,
	'four portal tables declared' => strpos( $db, '$sql_portal_access' ) !== false
		&& strpos( $db, '$sql_portal_sessions' ) !== false
		&& strpos( $db, '$sql_portal_events' ) !== false
		&& strpos( $db, '$sql_portal_recovery_requests' ) !== false,
	'four portal tables installed' => strpos( $db, 'dbDelta( $sql_portal_access )' ) !== false
		&& strpos( $db, 'dbDelta( $sql_portal_sessions )' ) !== false
		&& strpos( $db, 'dbDelta( $sql_portal_events )' ) !== false
		&& strpos( $db, 'dbDelta( $sql_portal_recovery_requests )' ) !== false,
	'recovery schema diagnostics' => strpos( $db, "'portal_recovery_requests' => array(" ) !== false,
	'raw credentials not stored' => strpos( $repo, 'invite_token_hash' ) !== false
		&& strpos( $repo, 'session_hash' ) !== false
		&& strpos( $repo, "'raw_token' => \$raw_token" ) !== false,
	'HMAC credential hashing' => strpos( $repo, "hash_hmac( 'sha256'" ) !== false,
	'token validated before lockout' => strpos( $repo, 'invitation_token_rejected' ) !== false
		&& strpos( $repo, "'lockout_incremented' => false" ) !== false
		&& strpos( $repo, 'register_failed_activation( $access )' ) !== false,
	'email challenge owns lockout' => strpos( $repo, 'invitation_email_rejected' ) !== false
		&& strpos( $repo, 'portal_invite_locked' ) !== false,
	'atomic activation transaction' => strpos( $repo, "START TRANSACTION" ) !== false
		&& strpos( $repo, "'status'      => 'invited'" ) !== false
		&& strpos( $repo, 'create_session( $fresh_access, false )' ) !== false
		&& strpos( $repo, "ROLLBACK" ) !== false
		&& strpos( $repo, "COMMIT" ) !== false,
	'activation rollback preserves invitation' => strpos( $repo, 'portal_activation_retry' ) !== false
		&& strpos( $repo, 'activation_rolled_back' ) !== false,
	'invitation state inspection' => strpos( $repo, 'inspect_invitation' ) !== false
		&& strpos( $repo, "'state' => 'invalid'" ) !== false
		&& strpos( $repo, "'state'      => 'valid'" ) !== false,
	'__Host cookie' => strpos( $session, 'SC_EI_Portal_Schema::COOKIE_NAME' ) !== false
		&& strpos( $session, "'secure'   => true" ) !== false
		&& strpos( $session, "'httponly' => true" ) !== false
		&& strpos( $session, "'samesite' => 'Strict'" ) !== false,
	'legacy cookie migration' => strpos( $session, 'LEGACY_COOKIE_NAME' ) !== false
		&& strpos( $session, 'legacy_cookie_migrated' ) !== false
		&& strpos( $session, 'clear_legacy_cookie' ) !== false,
	'HTTPS authentication gate' => strpos( $repo, 'portal_https_required' ) !== false
		&& strpos( $session, 'secure_transport_available' ) !== false,
	'absolute and idle expiration' => strpos( $session, 'expires_at' ) !== false
		&& strpos( $session, 'idle_expires_at' ) !== false,
	'session CSRF' => strpos( $session, 'csrf_token' ) !== false
		&& strpos( $session, 'verify_csrf' ) !== false,
	'activation failure preserves invitation context' => strpos( $public, 'redirect_activation' ) !== false
		&& strpos( $public, "'sc_ei_portal_invite'" ) !== false
		&& strpos( $public, "'sc_ei_portal_token'" ) !== false,
	'activation form nonce fails safely' => strpos( $public, 'wp_verify_nonce' ) !== false
		&& strpos( $public, 'portal_activation_form_expired' ) !== false,
	'cookie failure reissues fresh invitation' => strpos( $public, 'Automatically reissued after browser session-cookie establishment failed.' ) !== false,
	'non-enumerating recovery response' => strpos( $repo, "'accepted' => true" ) !== false
		&& strpos( $repo, 'No access link is issued automatically.' ) !== false
		&& strpos( $login_view, 'response is intentionally identical' ) !== false,
	'recovery throttles matched and unmatched attempts' => strpos( $repo, "event_type IN ('recovery_requested','recovery_request_unmatched')" ) !== false
		&& strpos( $repo, 'recovery_request_throttled' ) !== false,
	'recovery approval claim' => strpos( $repo, "'status'        => 'processing'" ) !== false
		&& strpos( $repo, "'status'      => 'processing'" ) !== false
		&& strpos( $repo, 'Another reviewer already claimed' ) !== false,
	'recovery deduplication' => strpos( $repo, "'deduplicated'" ) !== false
		&& strpos( $repo, 'request_count' ) !== false,
	'human-only recovery decision' => strpos( $repo, 'review_recovery' ) !== false
		&& strpos( $admin, 'sc_intake_manage_portal_recovery' ) !== false
		&& strpos( $admin, "'RECOVER ' : 'DECLINE '" ) !== false,
	'recovery never auto-emails' => strpos( $repo, "'automatic_email_sent'     => false" ) !== false
		&& strpos( $repo, "'automatic_email' => false" ) !== false,
	'typed unlock' => strpos( $admin, "'UNLOCK ' . \$access_id" ) !== false
		&& strpos( $repo, 'unlock_access' ) !== false,
	'recovery privacy export' => strpos( $privacy, 'Engagement Intake Sender Portal Recovery Requests' ) !== false,
	'recovery private inventory' => strpos( $inventory, "'portal_recovery_requests'" ) !== false,
	'recovery approved erasure' => strpos( $repo, "SET reference_hash = '', email_hash = '', recovery_reason = ''" ) !== false
		&& strpos( $engine, 'SC_EI_Portal_Repository::redact_for_privacy' ) !== false,
	'recovery cleanup' => strpos( $repo, 'expired_recovery' ) !== false
		&& strpos( $repo, "WHERE status IN ('pending','processing') AND expires_at < %s" ) !== false,
	'recovery diagnostics' => strpos( $diag, "'atomic_activation'     => true" ) !== false
		&& strpos( $diag, "'wrong_token_lockout'   => false" ) !== false
		&& strpos( $diag, "'recovery_human_review' => true" ) !== false,
	'granular recovery capabilities' => strpos( $caps, 'sc_intake_view_portal_recovery' ) !== false
		&& strpos( $caps, 'sc_intake_manage_portal_recovery' ) !== false,
	'reviewer cannot approve recovery' => substr_count(
		substr(
			$caps,
			strpos( $caps, 'private const REVIEWER' ),
			strpos( $caps, 'private const MANAGER' ) - strpos( $caps, 'private const REVIEWER' )
		),
		'sc_intake_manage_portal_recovery'
	) === 0,
	'internal data boundary retained' => strpos( $portal_view, 'Internal review notes, fit assessments' ) !== false,
	'no public WordPress account creation' => strpos( $repo, 'wp_insert_user' ) === false
		&& strpos( $repo, 'wp_create_user' ) === false,
	'no automatic portal email' => strpos( $repo, 'wp_mail(' ) === false,
);

$failed = array_keys( array_filter( $checks, static fn( bool $passed ): bool => ! $passed ) );
if ( $failed ) {
	fwrite( STDERR, 'Portal authentication and recovery checks failed: ' . implode( ', ', $failed ) . PHP_EOL );
	exit( 1 );
}
foreach ( $checks as $label => $passed ) {
	echo 'PASS: ' . $label . PHP_EOL;
}
echo "Engagement Intake v1.0.0 portal authentication and recovery operation checks passed.\n";
