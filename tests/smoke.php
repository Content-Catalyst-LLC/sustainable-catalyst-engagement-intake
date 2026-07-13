<?php
/**
 * Static release checks that do not load WordPress.
 */

$root   = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';

$required = array(
	$plugin . '/sustainable-catalyst-engagement-intake.php',
	$plugin . '/includes/class-sc-ei-review-schema.php',
	$plugin . '/includes/class-sc-ei-review-repository.php',
	$plugin . '/includes/class-sc-ei-review-list-table.php',
	$plugin . '/includes/class-sc-ei-review-admin.php',
	$plugin . '/admin/views/review-workspace.php',
	$plugin . '/admin/views/review-detail.php',
	$plugin . '/includes/class-sc-ei-upload-environment.php',
	$plugin . '/includes/class-sc-ei-storage.php',
	$plugin . '/includes/class-sc-ei-storage-reconciler.php',
	$plugin . '/includes/class-sc-ei-file-scanner.php',
	$plugin . '/includes/class-sc-ei-scanner-operations.php',
	$plugin . '/includes/class-sc-ei-upload-validator.php',
	$plugin . '/includes/class-sc-ei-upload-manager.php',
	$plugin . '/includes/class-sc-ei-retention.php',
	$plugin . '/includes/class-sc-ei-attachment-repository.php',
	$plugin . '/includes/class-sc-ei-form-handler.php',
	$plugin . '/includes/class-sc-ei-public.php',
	$plugin . '/includes/class-sc-ei-admin.php',
	$plugin . '/includes/class-sc-ei-diagnostics.php',
	$plugin . '/includes/class-sc-ei-privacy.php',
	$plugin . '/includes/class-sc-ei-quarantine-list-table.php',
	$plugin . '/includes/class-sc-ei-file-access-list-table.php',
	$plugin . '/admin/views/quarantine.php',
	$plugin . '/assets/js/public.js',
	$plugin . '/assets/js/admin.js',
	$plugin . '/assets/css/public.css',
	$plugin . '/assets/css/admin.css',
	$plugin . '/uninstall.php',
);

$failures = array();
foreach ( $required as $file ) {
	if ( ! is_file( $file ) ) {
		$failures[] = 'Missing: ' . $file;
	}
}

$main       = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$database   = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$review     = file_get_contents( $plugin . '/includes/class-sc-ei-review-repository.php' );
$review_admin = file_get_contents( $plugin . '/includes/class-sc-ei-review-admin.php' );
$review_view  = file_get_contents( $plugin . '/admin/views/review-detail.php' );
$privacy    = file_get_contents( $plugin . '/includes/class-sc-ei-privacy.php' );
$manager    = file_get_contents( $plugin . '/includes/class-sc-ei-upload-manager.php' );
$public     = file_get_contents( $plugin . '/includes/class-sc-ei-public.php' );
$storage    = file_get_contents( $plugin . '/includes/class-sc-ei-storage.php' );
$javascript = file_get_contents( $plugin . '/assets/js/admin.js' );

$markers = array(
	'Version:     0.4.0'                          => $main,
	"SC_EI_DB_VERSION', '0.4.0'"                 => $main,
	"SC_EI_REVIEW_SCHEMA_VERSION', '1.0.0'"      => $main,
	'class-sc-ei-review-schema.php'               => $main,
	'class-sc-ei-review-repository.php'           => $main,
	'class-sc-ei-review-list-table.php'           => $main,
	'class-sc-ei-review-admin.php'                => $main,
	'$sql_reviews'                                => $database,
	'backfill_review_defaults'                    => $database,
	'review_version int'                          => $database,
	'insert_snapshot'                             => $review,
	'review_conflict'                             => $review,
	'review_checklist_incomplete'                 => $review,
	'sc-engagement-intake-review-packet/1.0'      => $review,
	'sc_ei_bulk_review'                           => $review_admin,
	'sc_ei_export_review_packet'                  => $review_admin,
	'restrict_review_to_assignee'                 => $review_admin,
	'Administrative review checklist'             => $review_view,
	'Structured Review History'                   => $review_view,
	'Engagement Intake Administrative Reviews'    => $privacy,
	'beforeunload'                                => $javascript,
	'data-sc-ei-review-bulk'                      => $javascript,
);

foreach ( $markers as $marker => $contents ) {
	if ( false === strpos( $contents, $marker ) ) {
		$failures[] = 'Marker missing: ' . $marker;
	}
}

foreach ( array( 'wp_handle_upload', 'media_handle_upload', 'media_handle_sideload', 'wp_insert_attachment' ) as $media_marker ) {
	if ( false !== strpos( $public . $manager . $storage, $media_marker ) ) {
		$failures[] = 'Media Library API appeared in secure pipeline: ' . $media_marker;
	}
}

if ( false !== strpos( $public, 'Zoom' ) || false !== strpos( $public, 'Google Meet' ) ) {
	$failures[] = 'Unsupported meeting platform appeared in the public form.';
}

if ( false !== strpos( $review . $review_admin, 'fit_score' ) ) {
	$failures[] = 'Automated fit scoring marker appeared in human review layer.';
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo "Engagement Intake v0.4.0 smoke checks passed." . PHP_EOL;
