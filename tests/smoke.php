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
	$plugin . '/includes/class-sc-ei-upload-validator.php',
	$plugin . '/includes/class-sc-ei-upload-manager.php',
	$plugin . '/includes/class-sc-ei-retention.php',
	$plugin . '/includes/class-sc-ei-attachment-repository.php',
	$plugin . '/includes/class-sc-ei-form-handler.php',
	$plugin . '/includes/class-sc-ei-public.php',
	$plugin . '/includes/class-sc-ei-admin.php',
	$plugin . '/includes/class-sc-ei-diagnostics.php',
	$plugin . '/includes/class-sc-ei-privacy.php',
	$plugin . '/admin/views/inquiry-view.php',
	$plugin . '/admin/views/diagnostics.php',
	$plugin . '/assets/js/public.js',
	$plugin . '/assets/css/public.css',
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
$environment= file_get_contents( $plugin . '/includes/class-sc-ei-upload-environment.php' );
$storage    = file_get_contents( $plugin . '/includes/class-sc-ei-storage.php' );
$reconciler = file_get_contents( $plugin . '/includes/class-sc-ei-storage-reconciler.php' );
$validator  = file_get_contents( $plugin . '/includes/class-sc-ei-upload-validator.php' );
$manager    = file_get_contents( $plugin . '/includes/class-sc-ei-upload-manager.php' );
$retention  = file_get_contents( $plugin . '/includes/class-sc-ei-retention.php' );
$handler    = file_get_contents( $plugin . '/includes/class-sc-ei-form-handler.php' );
$public     = file_get_contents( $plugin . '/includes/class-sc-ei-public.php' );
$admin      = file_get_contents( $plugin . '/includes/class-sc-ei-admin.php' );
$privacy    = file_get_contents( $plugin . '/includes/class-sc-ei-privacy.php' );
$diagnostic = file_get_contents( $plugin . '/includes/class-sc-ei-diagnostics.php' );
$javascript = file_get_contents( $plugin . '/assets/js/public.js' );

$markers = array(
	'Version:     0.3.1'                         => $main,
	"SC_EI_DB_VERSION', '0.3.1'"                => $main,
	"SC_EI_VALIDATOR_VERSION', '1.0.1'"         => $main,
	'class-sc-ei-upload-environment.php'         => $main,
	'class-sc-ei-storage-reconciler.php'         => $main,
	'storage_status'                             => $database,
	'last_verified_at'                           => $database,
	'last_verification_message'                  => $database,
	'request_exceeds_post_max'                   => $environment,
	'Cloudflare-CDN-Cache-Control'                => $environment,
	'upload_truncated'                            => $environment,
	'store_uploaded_file_verified'                => $storage,
	'post_move_verification_failed'               => $storage,
	'storage_lock_race'                           => $storage,
	'managed_file_inventory'                      => $storage,
	'cleanup_stale_staging_files'                 => $storage,
	'storage_reconciliation_completed'            => $reconciler,
	'orphan_files'                                => $reconciler,
	'pdf_javascript'                              => $validator,
	'ooxml_active_content'                        => $validator,
	'archive_ratio_limit'                         => $validator,
	'post_move_verified'                          => $manager,
	'request_id'                                  => $manager,
	'public static function preview'              => $retention,
	'sc_ei_last_retention_run'                    => $retention,
	'validate_request_envelope'                   => $handler,
	'acquire_request_lock'                         => $handler,
	'request_success_key'                          => $handler,
	'engagement-intake-v0.3.1'                    => $handler,
	'document_selection_count'                    => $public,
	'sc_ei_submission'                            => $public,
	'SC_EI_Upload_Environment::send_no_cache_headers' => $public,
	'transition_attachment_storage_status'          => $admin,
	'sc_ei_verify_attachment_integrity'           => $admin,
	'sc_ei_run_storage_reconciliation'            => $admin,
	'sc_ei_preview_retention_cleanup'             => $admin,
	'sc_ei_run_retention_cleanup'                 => $admin,
	'SC_EI_Storage::delete_file'                  => $privacy,
	'storage_probe'                               => $diagnostic,
	'reconciliation'                              => $diagnostic,
	'cache_headers'                               => $diagnostic,
	'AbortController'                             => $javascript,
	'isSubmitting'                                => $javascript,
	'maxTotalBytes'                               => $javascript,
	'requestId'                                   => $javascript,
);

foreach ( $markers as $marker => $contents ) {
	if ( false === strpos( $contents, $marker ) ) {
		$failures[] = 'Marker missing: ' . $marker;
	}
}

if ( false !== strpos( $public, 'Zoom' ) || false !== strpos( $public, 'Google Meet' ) ) {
	$failures[] = 'Unsupported meeting platform appeared in the public form.';
}

foreach ( array( 'wp_handle_upload', 'media_handle_upload', 'media_handle_sideload', 'wp_insert_attachment' ) as $media_marker ) {
	if ( false !== strpos( $public . $manager . $storage, $media_marker ) ) {
		$failures[] = 'Media Library API appeared in secure pipeline: ' . $media_marker;
	}
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}

echo "Engagement Intake v0.3.1 smoke checks passed." . PHP_EOL;
