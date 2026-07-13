<?php
/**
 * Upload request-envelope and server-limit fixtures without loading WordPress.
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'MB_IN_BYTES', 1024 * 1024 );

final class WP_Error {
	private string $code;
	private string $message;

	public function __construct( string $code, string $message, array $data = array() ) {
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

function absint( $value ): int {
	return abs( (int) $value );
}

function sanitize_key( string $key ): string {
	return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $key ) ) ?: '';
}

function sanitize_text_field( string $value ): string {
	return trim( strip_tags( $value ) );
}

function wp_unslash( $value ) {
	return $value;
}

function wp_normalize_path( string $path ): string {
	return str_replace( '\\', '/', $path );
}

final class SC_EI_Upload_Manager {
	public static function normalize_files( array $files, string $field ): array {
		if ( empty( $files[ $field ]['name'] ) ) {
			return array();
		}
		$names = (array) $files[ $field ]['name'];
		return array_map( static fn( string $name ): array => array( 'name' => $name ), $names );
	}
}

require_once dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake/includes/class-sc-ei-upload-environment.php';

function fail_environment( string $message ): void {
	fwrite( STDERR, $message . PHP_EOL );
	exit( 1 );
}

function pass_environment( string $message ): void {
	echo 'PASS: ' . $message . PHP_EOL;
}

$parses = array(
	'1K'   => 1024,
	'2M'   => 2 * 1024 * 1024,
	'1.5G' => (int) round( 1.5 * 1024 * 1024 * 1024 ),
	'512'  => 512,
	'-1'   => 0,
);
foreach ( $parses as $value => $expected ) {
	if ( SC_EI_Upload_Environment::ini_bytes( $value ) !== $expected ) {
		fail_environment( 'INI byte parsing failed for ' . $value );
	}
}
pass_environment( 'PHP INI size parsing passed' );

$settings = array(
	'upload_max_files'   => 5,
	'upload_max_file_mb' => 20,
);
$effective = SC_EI_Upload_Environment::effective_limits( $settings );
if ( $effective['max_files'] < 1 || $effective['max_file_bytes'] < MB_IN_BYTES || $effective['max_total_bytes'] < MB_IN_BYTES ) {
	fail_environment( 'Effective limits were not usable.' );
}
pass_environment( 'effective plugin/server limits passed' );

$_SERVER['CONTENT_LENGTH'] = 0;
$ok = SC_EI_Upload_Environment::validate_request_envelope(
	array( 'document_selection_count' => 1 ),
	array( 'documents' => array( 'name' => array( 'one.pdf' ) ) )
);
if ( is_wp_error( $ok ) ) {
	fail_environment( 'Matching selected/received file counts were rejected.' );
}
pass_environment( 'matching browser/server file counts accepted' );

$truncated = SC_EI_Upload_Environment::validate_request_envelope(
	array( 'document_selection_count' => 2 ),
	array( 'documents' => array( 'name' => array( 'one.pdf' ) ) )
);
if ( ! is_wp_error( $truncated ) || 'upload_truncated' !== $truncated->get_error_code() ) {
	fail_environment( 'Truncated upload request was not detected.' );
}
pass_environment( 'truncated upload request detected' );

$post_max = SC_EI_Upload_Environment::ini_bytes( (string) ini_get( 'post_max_size' ) );
if ( $post_max > 0 ) {
	$_SERVER['CONTENT_LENGTH'] = $post_max + 1;
	if ( ! SC_EI_Upload_Environment::request_exceeds_post_max() ) {
		fail_environment( 'Oversized request was not detected.' );
	}
	pass_environment( 'post_max_size overrun detected' );
}
$_SERVER['CONTENT_LENGTH'] = 0;

$headers = SC_EI_Upload_Environment::no_cache_headers();
foreach ( array( 'Cache-Control', 'CDN-Cache-Control', 'Cloudflare-CDN-Cache-Control', 'Surrogate-Control', 'Vary' ) as $header ) {
	if ( empty( $headers[ $header ] ) ) {
		fail_environment( 'Missing no-cache header: ' . $header );
	}
}
pass_environment( 'browser, CDN, Cloudflare, and surrogate no-store headers present' );

echo "Engagement Intake v0.8.1 upload-environment fixtures passed.\n";
