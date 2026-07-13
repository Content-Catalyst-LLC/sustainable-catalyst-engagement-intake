<?php
/**
 * Static release checks that do not load WordPress.
 */

$root   = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';

$required = array(
	$plugin . '/sustainable-catalyst-engagement-intake.php',
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
	$plugin . '/admin/views/inquiry-view.php',
	$plugin . '/admin/views/diagnostics.php',
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
$scanner    = file_get_contents( $plugin . '/includes/class-sc-ei-scanner-operations.php' );
$repository = file_get_contents( $plugin . '/includes/class-sc-ei-attachment-repository.php' );
$audit      = file_get_contents( $plugin . '/includes/class-sc-ei-audit-log.php' );
$admin      = file_get_contents( $plugin . '/includes/class-sc-ei-admin.php' );
$view       = file_get_contents( $plugin . '/admin/views/quarantine.php' );
$manager    = file_get_contents( $plugin . '/includes/class-sc-ei-upload-manager.php' );
$public     = file_get_contents( $plugin . '/includes/class-sc-ei-public.php' );
$storage    = file_get_contents( $plugin . '/includes/class-sc-ei-storage.php' );
$javascript = file_get_contents( $plugin . '/assets/js/admin.js' );

$markers = array(
	'Version:     0.3.2'                       => $main,
	"SC_EI_DB_VERSION', '0.3.2'"              => $main,
	'class-sc-ei-scanner-operations.php'       => $main,
	'class-sc-ei-quarantine-list-table.php'    => $main,
	'class-sc-ei-file-access-list-table.php'   => $main,
	'scan_attempts'                            => $database,
	'last_scanned_at'                          => $database,
	'last_scanned_by'                          => $database,
	'run_readiness_test'                       => $scanner,
	'bulk_rescan'                              => $scanner,
	'configuration_match'                      => $scanner,
	'query_operations'                         => $repository,
	'operational_summary'                      => $repository,
	'update_scan_result'                       => $repository,
	'query_file_events'                        => $audit,
	'file_event_summary'                       => $audit,
	'sc_ei_run_scanner_readiness_test'         => $admin,
	'sc_ei_quarantine_bulk'                    => $admin,
	'REJECT SELECTED'                          => $admin,
	'handle_file_audit_export'                 => $admin,
	'Quarantine Operations and Scanner Readiness' => $view,
	'Untrusted Document Isolation Guidance'    => $view,
	'data-sc-ei-bulk-controls'                 => $javascript,
	'scan_attempts'                            => $manager,
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

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo "Engagement Intake v0.3.2 smoke checks passed." . PHP_EOL;
