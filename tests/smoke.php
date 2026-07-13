<?php
/**
 * Static smoke checks that do not require WordPress.
 */

$root   = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';

$required = array(
	$plugin . '/sustainable-catalyst-engagement-intake.php',
	$plugin . '/includes/class-sc-ei-database.php',
	$plugin . '/includes/class-sc-ei-teams.php',
	$plugin . '/includes/class-sc-ei-form-handler.php',
	$plugin . '/includes/class-sc-ei-public.php',
	$plugin . '/includes/class-sc-ei-inquiry-repository.php',
	$plugin . '/admin/views/inquiry-view.php',
	$plugin . '/assets/css/public.css',
	$plugin . '/assets/js/public.js',
	$plugin . '/uninstall.php',
);

$failures = array();

foreach ( $required as $file ) {
	if ( ! is_file( $file ) ) {
		$failures[] = 'Missing: ' . $file;
	}
}

$main = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
if ( false === strpos( $main, 'Version:     0.2.1' ) ) {
	$failures[] = 'Version marker missing.';
}
if ( false === strpos( $main, 'class-sc-ei-teams.php' ) ) {
	$failures[] = 'Teams helper not loaded.';
}

$database = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
foreach ( array( 'preferred_contact_method', 'teams_email', 'timezone', 'meeting_request', 'scheduling_status', 'teams_meeting_url', 'scheduled_start_utc' ) as $column ) {
	if ( false === strpos( $database, $column ) ) {
		$failures[] = 'Database column missing: ' . $column;
	}
}

$handler = file_get_contents( $plugin . '/includes/class-sc-ei-form-handler.php' );
foreach ( array( 'preferred_contact_method', 'teams_email_required', 'timezone_required', 'calendar_consent_required', 'teams_meeting_requested' ) as $control ) {
	if ( false === strpos( $handler, $control ) ) {
		$failures[] = 'Teams form control missing: ' . $control;
	}
}

$public = file_get_contents( $plugin . '/includes/class-sc-ei-public.php' );
foreach ( array( 'Microsoft Teams email', 'data-sc-ei-meeting-request', 'data-sc-ei-timezone', 'preferred_weekdays[]', 'calendar_invite_consent' ) as $marker ) {
	if ( false === strpos( $public, $marker ) ) {
		$failures[] = 'Public Teams field missing: ' . $marker;
	}
}

$repository = file_get_contents( $plugin . '/includes/class-sc-ei-inquiry-repository.php' );
foreach ( array( 'update_scheduling', 'teams_scheduling_updated', 'local_to_utc' ) as $marker ) {
	if ( false === strpos( $repository, $marker ) ) {
		$failures[] = 'Scheduling repository feature missing: ' . $marker;
	}
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo "Engagement Intake v0.2.1 smoke checks passed." . PHP_EOL;
