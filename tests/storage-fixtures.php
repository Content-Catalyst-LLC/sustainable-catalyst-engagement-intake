<?php
/**
 * Protected storage and atomic-commit fixture tests without loading WordPress.
 */

$temp_root = sys_get_temp_dir() . '/sc-ei-storage-' . bin2hex( random_bytes( 5 ) );
$web_root  = $temp_root . '/public';
$wp_root   = $web_root . '/wordpress';

mkdir( $wp_root, 0700, true );

define( 'ABSPATH', $wp_root . '/' );
define( 'WP_CONTENT_DIR', $wp_root . '/wp-content' );
define( 'SC_EI_VERSION', '0.3.1' );
define( 'HOUR_IN_SECONDS', 3600 );

$_SERVER['DOCUMENT_ROOT'] = $web_root;

$GLOBALS['sc_ei_test_options'] = array();

final class WP_Error {
	private string $code;
	private string $message;

	public function __construct( string $code, string $message ) {
		$this->code    = $code;
		$this->message = $message;
	}

	public function get_error_code(): string {
		return $this->code;
	}

	public function get_error_message(): string {
		return $this->message;
	}
}

function is_wp_error( $value ): bool {
	return $value instanceof WP_Error;
}

function __( string $text, string $domain = '' ): string {
	return $text;
}

function get_option( string $name, $default = false ) {
	return array_key_exists( $name, $GLOBALS['sc_ei_test_options'] )
		? $GLOBALS['sc_ei_test_options'][ $name ]
		: $default;
}

function add_option( string $name, $value, string $deprecated = '', $autoload = null ): bool {
	if ( array_key_exists( $name, $GLOBALS['sc_ei_test_options'] ) ) {
		return false;
	}
	$GLOBALS['sc_ei_test_options'][ $name ] = $value;
	return true;
}

function update_option( string $name, $value, $autoload = null ): bool {
	$GLOBALS['sc_ei_test_options'][ $name ] = $value;
	return true;
}

function current_time( string $type, bool $gmt = false ): string {
	return gmdate( 'Y-m-d H:i:s' );
}

function wp_parse_args( $args, $defaults = array() ): array {
	return array_merge( (array) $defaults, (array) $args );
}

function wp_unslash( $value ) {
	return $value;
}

function wp_normalize_path( string $path ): string {
	return str_replace( '\\', '/', $path );
}

function trailingslashit( string $path ): string {
	return rtrim( $path, '/\\' ) . '/';
}

function untrailingslashit( string $path ): string {
	return rtrim( $path, '/\\' );
}

function wp_mkdir_p( string $target ): bool {
	return is_dir( $target ) || mkdir( $target, 0700, true );
}

function sanitize_key( string $key ): string {
	return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $key ) ) ?: '';
}

function apply_filters( string $hook, $value, ...$args ) {
	if ( 'sc_ei_allow_non_http_upload_move' === $hook ) {
		return true;
	}
	return $value;
}

function wp_delete_file( string $path ): bool {
	return ! file_exists( $path ) || unlink( $path );
}

function remove_accents( string $value ): string {
	return $value;
}

function wp_generate_uuid4(): string {
	$bytes = random_bytes( 16 );
	$bytes[6] = chr( ( ord( $bytes[6] ) & 0x0f ) | 0x40 );
	$bytes[8] = chr( ( ord( $bytes[8] ) & 0x3f ) | 0x80 );
	$hex = bin2hex( $bytes );
	return sprintf(
		'%s-%s-%s-%s-%s',
		substr( $hex, 0, 8 ),
		substr( $hex, 8, 4 ),
		substr( $hex, 12, 4 ),
		substr( $hex, 16, 4 ),
		substr( $hex, 20 )
	);
}

require_once dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake/includes/class-sc-ei-storage.php';

function fail_storage( string $message ): void {
	fwrite( STDERR, $message . PHP_EOL );
	exit( 1 );
}

function pass_storage( string $message ): void {
	echo 'PASS: ' . $message . PHP_EOL;
}

$base = SC_EI_Storage::base_dir();
if ( str_starts_with( trailingslashit( $base ), trailingslashit( $web_root ) ) ) {
	fail_storage( 'Automatic storage path remained inside the document root.' );
}
pass_storage( 'automatic path outside document root' );

if ( ! SC_EI_Storage::ensure() ) {
	fail_storage( 'Protected storage initialization failed.' );
}

$health = SC_EI_Storage::storage_health();
foreach ( array( 'exists', 'writable', 'marker', 'outside_document_root', 'protection_files', 'quarantine_writable', 'approved_writable' ) as $control ) {
	if ( empty( $health[ $control ] ) ) {
		fail_storage( 'Storage health control failed: ' . $control );
	}
}
pass_storage( 'storage protections and writable areas created' );

if ( false !== get_option( 'sc_ei_storage_base_dir', false ) ) {
	fail_storage( 'Storage path locked before the first accepted document.' );
}
pass_storage( 'path remains configurable before first committed file' );

$temp_upload = $temp_root . '/incoming.txt';
$payload     = "private intake fixture\n";
file_put_contents( $temp_upload, $payload );
$relative = SC_EI_Storage::quarantine_relative_path(
	'11111111-1111-4111-8111-111111111111',
	'22222222-2222-4222-8222-222222222222'
);

$stored = SC_EI_Storage::store_uploaded_file_verified(
	$temp_upload,
	$relative,
	strlen( $payload ),
	hash( 'sha256', $payload )
);
if ( is_wp_error( $stored ) ) {
	fail_storage( 'Atomic verified storage failed: ' . $stored->get_error_code() );
}
pass_storage( 'atomic staging, commit, size, and SHA-256 verification passed' );

if ( get_option( 'sc_ei_storage_base_dir', '' ) !== $base ) {
	fail_storage( 'Storage path was not locked after the first committed file.' );
}
pass_storage( 'path locks after first committed file' );

$absolute = SC_EI_Storage::absolute_path( $relative );
if ( ! $absolute || ! is_file( $absolute ) ) {
	fail_storage( 'Stored file could not be resolved.' );
}

if ( ! SC_EI_Storage::verify_integrity( $relative, hash( 'sha256', $payload ) ) ) {
	fail_storage( 'Stored file integrity check failed.' );
}
pass_storage( 'stored file integrity verified' );

$bad_upload = $temp_root . '/bad.txt';
file_put_contents( $bad_upload, 'different payload' );
$bad_relative = SC_EI_Storage::quarantine_relative_path(
	'11111111-1111-4111-8111-111111111111',
	'33333333-3333-4333-8333-333333333333'
);
$bad_result = SC_EI_Storage::store_uploaded_file_verified(
	$bad_upload,
	$bad_relative,
	filesize( $bad_upload ),
	str_repeat( 'a', 64 )
);
if ( ! is_wp_error( $bad_result ) || 'post_move_verification_failed' !== $bad_result->get_error_code() ) {
	fail_storage( 'Post-move mismatch was not rejected.' );
}
if ( is_file( SC_EI_Storage::absolute_path( $bad_relative ) ) ) {
	fail_storage( 'Failed atomic transaction left a committed file.' );
}
pass_storage( 'post-move mismatch removed before commit' );

if ( null !== SC_EI_Storage::absolute_path( '../escape.txt' ) ) {
	fail_storage( 'Path traversal was not rejected.' );
}
if ( SC_EI_Storage::delete_file( '../escape.txt' ) ) {
	fail_storage( 'Invalid deletion path was reported as successful.' );
}
pass_storage( 'path traversal and invalid deletion rejected' );

$approved = SC_EI_Storage::move_to_approved( $relative );
if ( ! $approved || ! str_starts_with( $approved, 'approved/' ) || ! is_file( SC_EI_Storage::absolute_path( $approved ) ) ) {
	fail_storage( 'Move to approved storage failed.' );
}
pass_storage( 'quarantine file moved to approved storage' );

$quarantined = SC_EI_Storage::move_to_quarantine( $approved );
if ( ! $quarantined || ! str_starts_with( $quarantined, 'quarantine/' ) || ! is_file( SC_EI_Storage::absolute_path( $quarantined ) ) ) {
	fail_storage( 'Return to quarantine failed.' );
}
pass_storage( 'approved file returned to quarantine' );

$probe = SC_EI_Storage::probe();
if ( empty( $probe['ok'] ) || empty( $probe['write'] ) || empty( $probe['read'] ) || empty( $probe['rename'] ) || empty( $probe['delete'] ) ) {
	fail_storage( 'Storage probe failed.' );
}
pass_storage( 'write/read/rename/delete storage probe passed' );

$stale = trailingslashit( $base ) . 'quarantine/stale.part-test';
file_put_contents( $stale, 'stale' );
touch( $stale, time() - 7200 );
if ( SC_EI_Storage::cleanup_stale_staging_files() < 1 || file_exists( $stale ) ) {
	fail_storage( 'Stale staging cleanup failed.' );
}
pass_storage( 'stale staging cleanup passed' );

$inventory = SC_EI_Storage::managed_file_inventory();
if ( 1 !== count( $inventory['files'] ) || $inventory['files'][0]['relative_path'] !== $quarantined ) {
	fail_storage( 'Managed file inventory did not match the committed file.' );
}
pass_storage( 'managed file inventory passed' );

$repair = SC_EI_Storage::repair();
if ( empty( $repair['ok'] ) || empty( $repair['probe']['ok'] ) ) {
	fail_storage( 'Storage repair failed.' );
}
pass_storage( 'storage repair and post-repair probe passed' );

if ( ! SC_EI_Storage::delete_file( $quarantined ) || is_file( SC_EI_Storage::absolute_path( $quarantined ) ) ) {
	fail_storage( 'Protected file deletion failed.' );
}
pass_storage( 'protected file deleted' );

if ( ! SC_EI_Storage::delete_storage_tree() || is_dir( $base ) ) {
	fail_storage( 'Private storage tree deletion failed.' );
}
pass_storage( 'marked storage tree deleted safely' );

$iterator = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator( $temp_root, FilesystemIterator::SKIP_DOTS ),
	RecursiveIteratorIterator::CHILD_FIRST
);
foreach ( $iterator as $item ) {
	$item->isDir() ? rmdir( $item->getPathname() ) : unlink( $item->getPathname() );
}
rmdir( $temp_root );

echo "Engagement Intake v0.3.1 storage fixtures passed.\n";
