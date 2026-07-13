<?php
/**
 * Static smoke checks that do not require WordPress.
 */

$root = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';

$required = array(
	$plugin . '/sustainable-catalyst-engagement-intake.php',
	$plugin . '/includes/class-sc-ei-database.php',
	$plugin . '/includes/class-sc-ei-statuses.php',
	$plugin . '/includes/class-sc-ei-capabilities.php',
	$plugin . '/includes/class-sc-ei-inquiry-repository.php',
	$plugin . '/includes/class-sc-ei-admin.php',
	$plugin . '/uninstall.php',
);

$failures = array();

foreach ( $required as $file ) {
	if ( ! is_file( $file ) ) {
		$failures[] = 'Missing: ' . $file;
	}
}

$main = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
if ( false === strpos( $main, 'Version:     0.1.0' ) ) {
	$failures[] = 'Version marker missing.';
}

$statuses = file_get_contents( $plugin . '/includes/class-sc-ei-statuses.php' );
foreach ( array( 'new', 'under_review', 'fit_call_recommended', 'proposal_sent', 'accepted', 'closed' ) as $status ) {
	if ( false === strpos( $statuses, "'" . $status . "'" ) ) {
		$failures[] = 'Status missing: ' . $status;
	}
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo "Engagement Intake v0.1.0 smoke checks passed." . PHP_EOL;
