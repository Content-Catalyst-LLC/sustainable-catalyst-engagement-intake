<?php
$root = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';

$main    = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$db      = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$repo    = file_get_contents( $plugin . '/includes/class-sc-ei-portal-repository.php' );
$session = file_get_contents( $plugin . '/includes/class-sc-ei-portal-session.php' );
$schema  = file_get_contents( $plugin . '/includes/class-sc-ei-portal-schema.php' );
$public  = file_get_contents( $plugin . '/includes/class-sc-ei-portal-public.php' );

$required = array(
	$plugin . '/includes/class-sc-ei-portal-schema.php',
	$plugin . '/includes/class-sc-ei-portal-repository.php',
	$plugin . '/includes/class-sc-ei-portal-session.php',
	$plugin . '/includes/class-sc-ei-portal-public.php',
	$plugin . '/includes/class-sc-ei-portal-admin.php',
	$plugin . '/public/views/sender-portal-login.php',
	$plugin . '/public/views/sender-portal.php',
	$plugin . '/admin/views/sender-portal.php',
	$plugin . '/admin/views/sender-portal-detail.php',
);

$failures = array();
foreach ( $required as $file ) {
	if ( ! is_file( $file ) ) {
		$failures[] = 'Missing: ' . $file;
	}
}

foreach ( array(
	'Version:     0.8.1'                           => $main,
	"SC_EI_DB_VERSION', '0.8.1'"                  => $main,
	"SC_EI_PORTAL_SCHEMA_VERSION', '1.1.0'"        => $main,
	'$sql_portal_access'                           => $db,
	'$sql_portal_sessions'                         => $db,
	'$sql_portal_events'                           => $db,
	'$sql_portal_recovery_requests'                => $db,
	'public static function inspect_invitation'    => $repo,
	'public static function request_recovery'      => $repo,
	'public static function review_recovery'       => $repo,
	'activation_rolled_back'                       => $repo,
	'__Host-sc_ei_sender_session'                  => $schema,
	'LEGACY_COOKIE_NAME'                           => $session,
	'SC_EI_Upload_Manager::process_inquiry_uploads'=> $public,
	'redirect_activation'                          => $public,
) as $marker => $source ) {
	if ( false === strpos( $source, $marker ) ) {
		$failures[] = 'Marker missing: ' . $marker;
	}
}

if ( false !== strpos( $repo, 'wp_insert_user' ) || false !== strpos( $repo, 'wp_create_user' ) ) {
	$failures[] = 'Portal creates WordPress users.';
}
if ( false !== strpos( $repo, 'wp_mail(' ) ) {
	$failures[] = 'Portal repository sends automatic email.';
}
if ( false !== strpos( $repo, "'lockout_incremented' => true" ) ) {
	$failures[] = 'Wrong-token path can increment lockout.';
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo "Engagement Intake v0.8.1 smoke checks passed.\n";
