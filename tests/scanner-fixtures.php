<?php
/**
 * Scanner bridge and readiness fixtures without loading WordPress.
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'HOUR_IN_SECONDS', 3600 );

$GLOBALS['sc_ei_options'] = array();
$GLOBALS['sc_ei_scan_mode'] = 'clean';
$GLOBALS['sc_ei_probe_provider'] = 'fixture-scanner';
$GLOBALS['sc_ei_probe_version'] = '1.0.0';
$GLOBALS['sc_ei_attachments'] = array();
$GLOBALS['sc_ei_scan_updates'] = array();
$GLOBALS['sc_ei_deleted_records'] = array();
$GLOBALS['sc_ei_audit'] = array();

function __( string $text, string $domain = '' ): string {
	return $text;
}

function wp_parse_args( $args, $defaults = array() ): array {
	return array_merge( (array) $defaults, (array) $args );
}

function sanitize_key( string $value ): string {
	return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ) ?: '';
}

function sanitize_text_field( string $value ): string {
	return trim( strip_tags( $value ) );
}

function sanitize_textarea_field( string $value ): string {
	return trim( strip_tags( $value ) );
}

function sanitize_file_name( string $value ): string {
	return preg_replace( '/[^A-Za-z0-9._-]/', '-', basename( $value ) ) ?: '';
}

function absint( $value ): int {
	return abs( (int) $value );
}

function get_option( string $name, $default = false ) {
	return array_key_exists( $name, $GLOBALS['sc_ei_options'] ) ? $GLOBALS['sc_ei_options'][ $name ] : $default;
}

function update_option( string $name, $value, $autoload = null ): bool {
	$GLOBALS['sc_ei_options'][ $name ] = $value;
	return true;
}

function current_time( string $type, bool $gmt = false ): string {
	return gmdate( 'Y-m-d H:i:s' );
}

function wp_generate_uuid4(): string {
	$bytes = random_bytes( 16 );
	$bytes[6] = chr( ( ord( $bytes[6] ) & 0x0f ) | 0x40 );
	$bytes[8] = chr( ( ord( $bytes[8] ) & 0x3f ) | 0x80 );
	$hex = bin2hex( $bytes );
	return sprintf( '%s-%s-%s-%s-%s', substr( $hex, 0, 8 ), substr( $hex, 8, 4 ), substr( $hex, 12, 4 ), substr( $hex, 16, 4 ), substr( $hex, 20 ) );
}

function wp_tempnam( string $filename = '', string $dir = '' ): string {
	$path = tempnam( sys_get_temp_dir(), 'sc-ei-' );
	return false === $path ? '' : $path;
}

function wp_delete_file( string $path ): bool {
	return ! file_exists( $path ) || unlink( $path );
}

function apply_filters( string $hook, $value, ...$args ) {
	if ( 'sc_ei_scanner_probe' === $hook ) {
		if ( 'probe_exception' === $GLOBALS['sc_ei_scan_mode'] ) {
			throw new RuntimeException( 'Fixture probe exception.' );
		}
		return array(
			'configured'         => true,
			'provider'           => $GLOBALS['sc_ei_probe_provider'],
			'message'            => 'Fixture scanner configured.',
			'integration_version'=> $GLOBALS['sc_ei_probe_version'],
			'supports_test_file' => true,
		);
	}

	if ( 'sc_ei_scan_attachment' === $hook ) {
		if ( 'exception' === $GLOBALS['sc_ei_scan_mode'] ) {
			throw new RuntimeException( 'Fixture scan exception.' );
		}
		if ( 'invalid' === $GLOBALS['sc_ei_scan_mode'] ) {
			return array( 'status' => 'mystery', 'provider' => 'fixture-scanner', 'message' => 'Invalid status.' );
		}
		return array(
			'status'   => $GLOBALS['sc_ei_scan_mode'],
			'provider' => 'fixture-scanner',
			'message'  => 'Fixture result: ' . $GLOBALS['sc_ei_scan_mode'],
		);
	}

	return $value;
}

final class SC_EI_Admin {
	public static function default_settings(): array {
		return array(
			'require_external_scanner'     => 0,
			'scanner_test_freshness_hours' => 24,
		);
	}
}

final class SC_EI_Audit_Log {
	public static function record( string $event_type, string $message = '', array $context = array(), ?int $inquiry_id = null, ?int $attachment_id = null, ?int $actor_user_id = null ): int {
		$GLOBALS['sc_ei_audit'][] = compact( 'event_type', 'message', 'context', 'inquiry_id', 'attachment_id', 'actor_user_id' );
		return count( $GLOBALS['sc_ei_audit'] );
	}
}

final class SC_EI_Storage {
	public static function absolute_path( string $relative ): ?string {
		foreach ( $GLOBALS['sc_ei_attachments'] as $attachment ) {
			if ( $attachment['relative_path'] === $relative ) {
				return $attachment['absolute_path'];
			}
		}
		return null;
	}

	public static function delete_file( string $relative ): bool {
		$path = self::absolute_path( $relative );
		return $path ? wp_delete_file( $path ) : false;
	}
}

final class SC_EI_Attachment_Repository {
	public static function find( int $id ): ?array {
		return $GLOBALS['sc_ei_attachments'][ $id ] ?? null;
	}

	public static function verify_record( array $attachment, int $actor_user_id = 0, string $source = 'manual' ): array {
		$ok = is_file( $attachment['absolute_path'] );
		return array(
			'ok'      => $ok,
			'status'  => $ok ? 'healthy' : 'missing',
			'message' => $ok ? 'Fixture file healthy.' : 'Fixture file missing.',
		);
	}

	public static function update_scan_result( int $id, array $scan, int $actor_user_id, string $source = 'manual_retry' ): bool {
		$GLOBALS['sc_ei_scan_updates'][ $id ] = array(
			'scan'   => $scan,
			'actor'  => $actor_user_id,
			'source' => $source,
		);
		return true;
	}

	public static function mark_deleted( int $id, int $actor_user_id, string $reason, string $final_status = 'deleted' ): bool {
		$GLOBALS['sc_ei_deleted_records'][ $id ] = compact( 'actor_user_id', 'reason', 'final_status' );
		return true;
	}
}

require_once dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake/includes/class-sc-ei-file-scanner.php';
require_once dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake/includes/class-sc-ei-scanner-operations.php';

function fail_scanner( string $message ): void {
	fwrite( STDERR, $message . PHP_EOL );
	exit( 1 );
}

function pass_scanner( string $message ): void {
	echo 'PASS: ' . $message . PHP_EOL;
}

$sample = tempnam( sys_get_temp_dir(), 'sc-ei-scan-' );
file_put_contents( $sample, "fixture\n" );

$GLOBALS['sc_ei_scan_mode'] = 'clean';
$clean = SC_EI_File_Scanner::scan( $sample, array() );
if ( 'clean' !== $clean['status'] ) {
	fail_scanner( 'Clean scanner result failed.' );
}
pass_scanner( 'clean scanner result accepted' );

$GLOBALS['sc_ei_scan_mode'] = 'invalid';
$invalid = SC_EI_File_Scanner::scan( $sample, array() );
if ( 'error' !== $invalid['status'] ) {
	fail_scanner( 'Invalid scanner status did not fail closed.' );
}
pass_scanner( 'invalid scanner status converted to error' );

$GLOBALS['sc_ei_scan_mode'] = 'exception';
$exception = SC_EI_File_Scanner::scan( $sample, array() );
if ( 'error' !== $exception['status'] || 'integration_exception' !== $exception['provider'] ) {
	fail_scanner( 'Scanner exception was not contained.' );
}
pass_scanner( 'scanner exception contained' );

$GLOBALS['sc_ei_scan_mode'] = 'probe_exception';
$probe = SC_EI_File_Scanner::probe();
if ( $probe['configured'] || 'integration_exception' !== $probe['provider'] ) {
	fail_scanner( 'Probe exception was not contained.' );
}
pass_scanner( 'scanner probe exception contained' );

$GLOBALS['sc_ei_scan_mode'] = 'clean';
$test = SC_EI_Scanner_Operations::run_readiness_test( 7 );
$readiness = SC_EI_Scanner_Operations::readiness(
	array(
		'require_external_scanner'     => 0,
		'scanner_test_freshness_hours' => 24,
	)
);
if ( 'clean' !== $test['scan_status'] || empty( $readiness['ready'] ) || empty( $test['test_file_deleted'] ) ) {
	fail_scanner( 'Clean readiness test did not establish readiness.' );
}
pass_scanner( 'benign readiness test established clean readiness' );

$GLOBALS['sc_ei_probe_provider'] = 'changed-provider';
$changed = SC_EI_Scanner_Operations::readiness(
	array(
		'require_external_scanner'     => 0,
		'scanner_test_freshness_hours' => 24,
	)
);
if ( ! empty( $changed['ready'] ) || ! empty( $changed['configuration_match'] ) ) {
	fail_scanner( 'Provider change did not invalidate readiness.' );
}
pass_scanner( 'scanner provider change invalidated readiness' );

$GLOBALS['sc_ei_probe_provider'] = 'fixture-scanner';
$clean_path = tempnam( sys_get_temp_dir(), 'sc-ei-clean-' );
file_put_contents( $clean_path, "clean attachment\n" );
$GLOBALS['sc_ei_attachments'][1] = array(
	'id'                => 1,
	'inquiry_id'        => 101,
	'original_name'     => 'clean.txt',
	'relative_path'     => 'quarantine/clean.qtn',
	'absolute_path'     => $clean_path,
	'mime_type'         => 'text/plain',
	'detected_mime'     => 'text/plain',
	'extension'         => 'txt',
	'size_bytes'        => filesize( $clean_path ),
	'sha256'            => hash_file( 'sha256', $clean_path ),
	'quarantine_status' => 'quarantined',
	'deleted_at'        => null,
);

$GLOBALS['sc_ei_scan_mode'] = 'clean';
$rescan = SC_EI_Scanner_Operations::rescan_attachment( 1, 9 );
if ( empty( $rescan['ok'] ) || 'clean' !== $GLOBALS['sc_ei_scan_updates'][1]['scan']['status'] || ! is_file( $clean_path ) ) {
	fail_scanner( 'Clean attachment rescan failed.' );
}
pass_scanner( 'clean attachment rescan persisted without deletion' );

$infected_path = tempnam( sys_get_temp_dir(), 'sc-ei-infected-' );
file_put_contents( $infected_path, "fixture infected result\n" );
$GLOBALS['sc_ei_attachments'][2] = array(
	'id'                => 2,
	'inquiry_id'        => 102,
	'original_name'     => 'infected.txt',
	'relative_path'     => 'quarantine/infected.qtn',
	'absolute_path'     => $infected_path,
	'mime_type'         => 'text/plain',
	'detected_mime'     => 'text/plain',
	'extension'         => 'txt',
	'size_bytes'        => filesize( $infected_path ),
	'sha256'            => hash_file( 'sha256', $infected_path ),
	'quarantine_status' => 'quarantined',
	'deleted_at'        => null,
);

$GLOBALS['sc_ei_scan_mode'] = 'infected';
$infected = SC_EI_Scanner_Operations::rescan_attachment( 2, 9 );
if ( 'infected' !== $infected['status'] || empty( $infected['deleted'] ) || is_file( $infected_path ) || 'rejected' !== $GLOBALS['sc_ei_deleted_records'][2]['final_status'] ) {
	fail_scanner( 'Infected attachment was not deleted and rejected.' );
}
pass_scanner( 'infected attachment deleted and rejected' );

wp_delete_file( $sample );
wp_delete_file( $clean_path );

echo "Engagement Intake v0.3.2 scanner fixtures passed.\n";
