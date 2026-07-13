<?php
/**
 * Static smoke checks that do not require WordPress.
 */

$root   = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';

$required = array(
	$plugin . '/sustainable-catalyst-engagement-intake.php',
	$plugin . '/includes/class-sc-ei-database.php',
	$plugin . '/includes/class-sc-ei-form-schema.php',
	$plugin . '/includes/class-sc-ei-form-handler.php',
	$plugin . '/includes/class-sc-ei-public.php',
	$plugin . '/includes/class-sc-ei-rest.php',
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
if ( false === strpos( $main, 'Version:     0.2.0' ) ) {
	$failures[] = 'Version marker missing.';
}

$public = file_get_contents( $plugin . '/includes/class-sc-ei-public.php' );
foreach ( array( 'sc_contact_hub', 'sc_contact_form', 'sc_engagement_inquiry' ) as $shortcode ) {
	if ( false === strpos( $public, "'" . $shortcode . "'" ) ) {
		$failures[] = 'Shortcode missing: ' . $shortcode;
	}
}

foreach ( array( 'DONOTCACHEPAGE', '<noscript>' ) as $marker ) {
	if ( false === strpos( $public, $marker ) ) {
		$failures[] = 'Public form marker missing: ' . $marker;
	}
}

$handler = file_get_contents( $plugin . '/includes/class-sc-ei-form-handler.php' );
foreach ( array( 'wp_verify_nonce', 'company_website', 'check_rate_limit', 'duplicate_submission', 'privacy_consent' ) as $control ) {
	if ( false === strpos( $handler, $control ) ) {
		$failures[] = 'Form control missing: ' . $control;
	}
}

$rest = file_get_contents( $plugin . '/includes/class-sc-ei-rest.php' );
if ( false === strpos( $rest, "'/submit'" ) || false === strpos( $rest, "'permission_callback' => '__return_true'" ) ) {
	$failures[] = 'Public write-only REST submission route missing.';
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo "Engagement Intake v0.2.0 smoke checks passed." . PHP_EOL;
