<?php
/**
 * Static smoke checks that do not require WordPress.
 */

$root   = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';

$required = array(
	$plugin . '/sustainable-catalyst-engagement-intake.php',
	$plugin . '/includes/class-sc-ei-database.php',
	$plugin . '/includes/class-sc-ei-conversion.php',
	$plugin . '/includes/class-sc-ei-form-handler.php',
	$plugin . '/includes/class-sc-ei-public.php',
	$plugin . '/includes/class-sc-ei-inquiry-repository.php',
	$plugin . '/includes/class-sc-ei-admin-list-table.php',
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
if ( false === strpos( $main, 'Version:     0.2.2' ) ) {
	$failures[] = 'Version marker missing.';
}
if ( false === strpos( $main, 'class-sc-ei-conversion.php' ) ) {
	$failures[] = 'Conversion helper not loaded.';
}

$database = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
foreach ( array( 'form_variant', 'source_page', 'entry_cta', 'conversion_route', 'guidance_flags' ) as $column ) {
	if ( false === strpos( $database, $column ) ) {
		$failures[] = 'Conversion column missing: ' . $column;
	}
}

$public = file_get_contents( $plugin . '/includes/class-sc-ei-public.php' );
foreach ( array(
	"mode=\"compact\"",
	'render_compact',
	'render_adaptive',
	'compact_service_interests',
	'data-sc-ei-pricing-guidance',
	'data-sc-ei-route-guidance',
	'name="source_page"',
	'name="entry_cta"'
) as $marker ) {
	if ( false === strpos( $public, $marker ) ) {
		$failures[] = 'Dual intake marker missing: ' . $marker;
	}
}

$handler = file_get_contents( $plugin . '/includes/class-sc-ei-form-handler.php' );
foreach ( array(
	'compact_next_step',
	'SC_EI_Conversion::sanitize_variant',
	'SC_EI_Conversion::sanitize_source',
	'sc_ei_conversion_routed',
	'engagement-intake-v0.2.2'
) as $marker ) {
	if ( false === strpos( $handler, $marker ) ) {
		$failures[] = 'Conversion handling missing: ' . $marker;
	}
}

$javascript = file_get_contents( $plugin . '/assets/js/public.js' );
foreach ( array(
	'class CompactForm',
	'class AdaptiveForm',
	'scEi:',
	'compactServiceSelected',
	'routeSelected',
	'submissionSuccess'
) as $marker ) {
	if ( false === strpos( $javascript, $marker ) ) {
		$failures[] = 'Browser event or experience missing: ' . $marker;
	}
}

if ( false !== strpos( $public, 'Zoom' ) || false !== strpos( $public, 'Google Meet' ) ) {
	$failures[] = 'Unsupported meeting platform appeared in public form.';
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo "Engagement Intake v0.2.2 smoke checks passed." . PHP_EOL;
