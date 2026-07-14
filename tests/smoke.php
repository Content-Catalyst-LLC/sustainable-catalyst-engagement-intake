<?php
$plugin = dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake';
$main = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$db = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$engagement = file_get_contents( $plugin . '/includes/class-sc-ei-engagement-repository.php' );
$admin = file_get_contents( $plugin . '/includes/class-sc-ei-engagement-admin.php' );
$hardening = file_get_contents( $plugin . '/includes/class-sc-ei-hardening-repository.php' );
$hardening_admin = file_get_contents( $plugin . '/includes/class-sc-ei-hardening-admin.php' );
$required = array(
	$plugin . '/includes/class-sc-ei-engagement-schema.php',
	$plugin . '/includes/class-sc-ei-engagement-repository.php',
	$plugin . '/includes/class-sc-ei-engagement-admin.php',
	$plugin . '/admin/views/engagement-handoff.php',
	$plugin . '/includes/class-sc-ei-hardening-schema.php',
	$plugin . '/includes/class-sc-ei-hardening-repository.php',
	$plugin . '/includes/class-sc-ei-hardening-admin.php',
	$plugin . '/admin/views/reliability.php',
);
$failures = array();
foreach ( $required as $file ) if ( ! is_file( $file ) ) $failures[] = 'Missing: ' . $file;
foreach ( array(
	'Version:     0.11.0' => $main,
	"SC_EI_DB_VERSION', '0.11.0'" => $main,
	"SC_EI_PORTAL_SCHEMA_VERSION', '1.3.0'" => $main,
	"SC_EI_ENGAGEMENT_SCHEMA_VERSION', '1.0.0'" => $main,
	"SC_EI_HARDENING_SCHEMA_VERSION', '1.0.0'" => $main,
	'$sql_engagements' => $db,
	'$sql_engagement_snapshots' => $db,
	'$sql_engagement_requirements' => $db,
	'$sql_engagement_events' => $db,
	'$sql_health_events' => $db,
	'$sql_rate_limits' => $db,
	'public static function create_from_contracted_proposal' => $engagement,
	'public static function readiness' => $engagement,
	'public static function activate' => $engagement,
	'sc_ei_create_engagement_handoff' => $admin,
	'sc_ei_activate_engagement' => $admin,
	'public static function watchdog' => $hardening,
	'public static function consume_rate_limit' => $hardening,
	'sc_ei_toggle_public_writes' => $hardening_admin,
) as $marker => $source ) if ( false === strpos( $source, $marker ) ) $failures[] = 'Marker missing: ' . $marker;
if ( false !== strpos( $engagement, 'wp_mail(' ) || false !== strpos( $engagement, 'wp_remote_' ) ) $failures[] = 'Engagement repository contains direct delivery or external API calls.';
if ( $failures ) { fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL ); exit( 1 ); }
echo "Engagement Intake v0.11.0 smoke checks passed.\n";
