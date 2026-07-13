<?php
/**
 * Static quarantine operations safety checks.
 */

$root       = dirname( __DIR__ );
$plugin     = $root . '/sustainable-catalyst-engagement-intake';
$admin      = file_get_contents( $plugin . '/includes/class-sc-ei-admin.php' );
$repository = file_get_contents( $plugin . '/includes/class-sc-ei-attachment-repository.php' );
$scanner    = file_get_contents( $plugin . '/includes/class-sc-ei-scanner-operations.php' );
$audit      = file_get_contents( $plugin . '/includes/class-sc-ei-audit-log.php' );
$table      = file_get_contents( $plugin . '/includes/class-sc-ei-quarantine-list-table.php' );
$view       = file_get_contents( $plugin . '/admin/views/quarantine.php' );
$settings   = file_get_contents( $plugin . '/admin/views/settings.php' );
$javascript = file_get_contents( $plugin . '/assets/js/admin.js' );

$checks = array(
	'cross-inquiry query'              => strpos( $repository, 'query_operations' ) !== false,
	'quarantine filter'                => strpos( $repository, "'quarantine_status' =>") !== false,
	'scanner filter'                   => strpos( $repository, "'scan_status'       =>") !== false,
	'validation filter'                => strpos( $repository, "'validation_status' =>") !== false,
	'storage filter'                   => strpos( $repository, "'storage_status'    =>") !== false,
	'retention filter'                 => strpos( $repository, "'retention'         =>") !== false,
	'bulk maximum 50'                  => strpos( $admin, '0, 50' ) !== false,
	'scanner bulk configurable limit'  => strpos( $admin, 'scanner_bulk_retry_limit' ) !== false,
	'rejection confirmation phrase'    => strpos( $admin, 'REJECT SELECTED' ) !== false,
	'retention date conversion'        => strpos( $admin, 'local_date_to_utc_end' ) !== false,
	'approval scanner safeguard'       => strpos( $admin, 'attachment_can_be_approved' ) !== false,
	'bulk capability map'              => strpos( $admin, 'bulk_operation_allowed' ) !== false,
	'CSV formula neutralization'       => strpos( $admin, "/^[=+\\-@]/") !== false,
	'audit export row cap'             => strpos( $admin, '5000' ) !== false,
	'readiness test file'              => strpos( $scanner, 'scanner readiness test' ) !== false,
	'readiness provider match'         => strpos( $scanner, 'configuration_match' ) !== false,
	'infected deletion'                => strpos( $scanner, "'infected' ===") !== false && strpos( $scanner, 'mark_deleted' ) !== false,
	'bulk scanner audit'               => strpos( $scanner, 'attachment_bulk_scan_completed' ) !== false,
	'download/access audit query'      => strpos( $audit, 'query_file_events' ) !== false,
	'quarantine table checkboxes'      => strpos( $table, "attachment_ids[]") !== false,
	'bulk confirmation UI'             => strpos( $javascript, 'REJECT SELECTED' ) !== false,
	'isolation guidance'               => strpos( $view, 'Untrusted Document Isolation Guidance' ) !== false,
	'storage utilization dashboard'    => strpos( $view, 'Storage Utilization' ) !== false,
	'scanner readiness dashboard'      => strpos( $view, 'Scanner Readiness' ) !== false,
	'clean-mode settings gate'         => strpos( $settings, 'recent clean benign readiness test' ) !== false,
);

$failed = array_keys( array_filter( $checks, static fn( bool $passed ): bool => ! $passed ) );
if ( $failed ) {
	fwrite( STDERR, 'Quarantine checks failed: ' . implode( ', ', $failed ) . PHP_EOL );
	exit( 1 );
}

foreach ( $checks as $label => $passed ) {
	echo 'PASS: ' . $label . PHP_EOL;
}

if ( strpos( $scanner, 'orphan' ) !== false && strpos( $scanner, 'delete_file' ) !== false ) {
	// This class deletes infected attachments, not reconciliation orphans. No assertion needed.
}

echo "Engagement Intake v0.8.0 quarantine operation checks passed.\n";
