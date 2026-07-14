<?php
/**
 * Prevent cross-class access to private or protected constants.
 */
$root = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';
$classes = array();

$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $plugin ) );
foreach ( $iterator as $file ) {
	if ( ! $file->isFile() || 'php' !== strtolower( $file->getExtension() ) ) {
		continue;
	}
	$source = file_get_contents( $file->getPathname() );
	if ( ! preg_match( '/\b(?:final\s+)?class\s+(\w+)/', $source, $class_match ) ) {
		continue;
	}
	preg_match_all( '/\b(private|protected)\s+const\s+(\w+)/', $source, $constant_matches, PREG_SET_ORDER );
	$constants = array();
	foreach ( $constant_matches as $constant_match ) {
		$constants[ $constant_match[2] ] = $constant_match[1];
	}
	$classes[ $class_match[1] ] = array(
		'file'      => $file->getPathname(),
		'constants' => $constants,
	);
}

$violations = array();
$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $plugin ) );
foreach ( $iterator as $file ) {
	if ( ! $file->isFile() || 'php' !== strtolower( $file->getExtension() ) ) {
		continue;
	}
	$source = file_get_contents( $file->getPathname() );
	$own_class = '';
	if ( preg_match( '/\b(?:final\s+)?class\s+(\w+)/', $source, $own_match ) ) {
		$own_class = $own_match[1];
	}
	preg_match_all( '/\b(\w+)::(\w+)\b/', $source, $references, PREG_SET_ORDER );
	foreach ( $references as $reference ) {
		$class_name = $reference[1];
		$constant_name = $reference[2];
		if ( $class_name === $own_class || empty( $classes[ $class_name ]['constants'][ $constant_name ] ) ) {
			continue;
		}
		$violations[] = sprintf(
			'%s accesses %s %s::%s declared in %s',
			$file->getPathname(),
			$classes[ $class_name ]['constants'][ $constant_name ],
			$class_name,
			$constant_name,
			$classes[ $class_name ]['file']
		);
	}
}

if ( $violations ) {
	fwrite( STDERR, "Visibility contract violations:\n" . implode( "\n", $violations ) . "\n" );
	exit( 1 );
}

// Verify the specific watchdog contract at runtime without loading WordPress.
define( 'ABSPATH', __DIR__ . '/' );
require_once $plugin . '/includes/class-sc-ei-hardening-repository.php';
if ( 'sc_ei_hardening_watchdog' !== SC_EI_Hardening_Repository::watchdog_hook() ) {
	fwrite( STDERR, "Watchdog hook accessor returned an unexpected value.\n" );
	exit( 1 );
}

echo "PASS: no cross-class private/protected constant access\n";
echo "PASS: watchdog hook accessor runtime contract\n";
echo "Visibility contract checks passed.\n";
