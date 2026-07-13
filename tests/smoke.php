<?php
/**
 * Static v0.6.0 release checks that do not load WordPress.
 */

$root   = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';

$required = array(
	$plugin . '/sustainable-catalyst-engagement-intake.php',
	$plugin . '/includes/class-sc-ei-privacy-schema.php',
	$plugin . '/includes/class-sc-ei-privacy-repository.php',
	$plugin . '/includes/class-sc-ei-retention-policy-repository.php',
	$plugin . '/includes/class-sc-ei-retention-engine.php',
	$plugin . '/includes/class-sc-ei-privacy-admin.php',
	$plugin . '/admin/views/privacy-center.php',
	$plugin . '/includes/class-sc-ei-communication-schema.php',
	$plugin . '/includes/class-sc-ei-communication-repository.php',
	$plugin . '/includes/class-sc-ei-mailer.php',
	$plugin . '/includes/class-sc-ei-review-schema.php',
	$plugin . '/includes/class-sc-ei-review-repository.php',
	$plugin . '/includes/class-sc-ei-file-scanner.php',
	$plugin . '/includes/class-sc-ei-storage.php',
	$plugin . '/includes/class-sc-ei-privacy.php',
	$plugin . '/includes/class-sc-ei-diagnostics.php',
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

$files = array(
	'main'       => file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' ),
	'database'   => file_get_contents( $plugin . '/includes/class-sc-ei-database.php' ),
	'privacy_schema' => file_get_contents( $plugin . '/includes/class-sc-ei-privacy-schema.php' ),
	'privacy_repo' => file_get_contents( $plugin . '/includes/class-sc-ei-privacy-repository.php' ),
	'policy_repo' => file_get_contents( $plugin . '/includes/class-sc-ei-retention-policy-repository.php' ),
	'engine'     => file_get_contents( $plugin . '/includes/class-sc-ei-retention-engine.php' ),
	'retention'  => file_get_contents( $plugin . '/includes/class-sc-ei-retention.php' ),
	'privacy_admin' => file_get_contents( $plugin . '/includes/class-sc-ei-privacy-admin.php' ),
	'privacy_view' => file_get_contents( $plugin . '/admin/views/privacy-center.php' ),
	'privacy'    => file_get_contents( $plugin . '/includes/class-sc-ei-privacy.php' ),
	'mailer'     => file_get_contents( $plugin . '/includes/class-sc-ei-mailer.php' ),
	'diagnostics'=> file_get_contents( $plugin . '/includes/class-sc-ei-diagnostics.php' ),
	'manager'    => file_get_contents( $plugin . '/includes/class-sc-ei-upload-manager.php' ),
	'public'     => file_get_contents( $plugin . '/includes/class-sc-ei-public.php' ),
	'storage'    => file_get_contents( $plugin . '/includes/class-sc-ei-storage.php' ),
	'javascript' => file_get_contents( $plugin . '/assets/js/admin.js' ),
);

$markers = array(
	'Version:     0.6.0'                           => $files['main'],
	"SC_EI_DB_VERSION', '0.6.0'"                  => $files['main'],
	"SC_EI_PRIVACY_SCHEMA_VERSION', '1.0.0'"       => $files['main'],
	'class-sc-ei-privacy-repository.php'           => $files['main'],
	'class-sc-ei-retention-policy-repository.php'  => $files['main'],
	'class-sc-ei-retention-engine.php'             => $files['main'],
	'class-sc-ei-privacy-admin.php'                => $files['main'],
	'$sql_privacy_requests'                        => $files['database'],
	'$sql_consent_events'                          => $files['database'],
	'$sql_legal_holds'                             => $files['database'],
	'$sql_retention_policies'                      => $files['database'],
	'$sql_retention_actions'                       => $files['database'],
	'privacy_version int'                          => $files['database'],
	'retention_cron_queue_only'                    => $files['privacy_schema'],
	'queue_candidates'                             => $files['retention'],
	'physical_absence_verified'                    => $files['engine'],
	'tombstone_preserved'                          => $files['engine'],
	"SET evidence_text = '', subject_email_hash = ''" => $files['engine'],
	"SET requester_name = '', requester_email = '', request_summary = ''" => $files['engine'],
	'event_context_redacted'                        => $files['engine'],
	'legal_hold_active'                            => $files['privacy_repo'],
	'if ( \'erased\' === $status )'                  => $files['privacy_repo'],
	'sc_ei_execute_retention_action'               => $files['privacy_admin'],
	'Privacy and Retention Center'                 => $files['privacy_view'],
	'queue-only eraser bridge'                     => $files['privacy'],
	'privacy_processing_restricted'                => $files['mailer'],
	'privacy_lifecycle'                            => $files['diagnostics'],
	'EXECUTE ${actionId}'                          => $files['javascript'],
);

foreach ( $markers as $marker => $contents ) {
	if ( false === strpos( $contents, $marker ) ) {
		$failures[] = 'Marker missing: ' . $marker;
	}
}

foreach ( array( 'wp_handle_upload', 'media_handle_upload', 'media_handle_sideload', 'wp_insert_attachment' ) as $media_marker ) {
	if ( false !== strpos( $files['public'] . $files['manager'] . $files['storage'], $media_marker ) ) {
		$failures[] = 'Media Library API appeared in secure pipeline: ' . $media_marker;
	}
}
if ( false !== strpos( $files['public'], 'Zoom' ) || false !== strpos( $files['public'], 'Google Meet' ) ) {
	$failures[] = 'Unsupported meeting platform appeared in the public form.';
}
if ( false !== strpos( $files['retention'], 'delete_file' ) || false !== strpos( $files['retention'], 'mark_deleted' ) ) {
	$failures[] = 'The daily retention compatibility layer contains a destructive operation.';
}
if ( false !== strpos( $files['privacy'], 'SC_EI_Storage::delete_file' ) ) {
	$failures[] = 'The WordPress privacy eraser bypasses reviewed execution.';
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}
echo "Engagement Intake v0.6.0 smoke checks passed." . PHP_EOL;
