<?php
define( 'ABSPATH', __DIR__ . '/' );

final class WP_Error {
	private string $code;
	private string $message;
	public function __construct( string $code = '', string $message = '' ) { $this->code = $code; $this->message = $message; }
	public function get_error_code(): string { return $this->code; }
	public function get_error_message(): string { return $this->message; }
}
function is_wp_error( $value ): bool { return $value instanceof WP_Error; }
function absint( $value ): int { return abs( (int) $value ); }
function __( string $text, string $domain = '' ): string { return $text; }
function wp_json_encode( $value, int $flags = 0 ) { return json_encode( $value, $flags ); }
function wp_salt( string $scheme = 'auth' ): string { return 'fixture-' . $scheme . '-a-very-long-stable-secret-value'; }

require_once dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake/includes/class-sc-ei-graph-crypto.php';

function fail_graph_crypto( string $message ): void { fwrite( STDERR, $message . PHP_EOL ); exit( 1 ); }
function pass_graph_crypto( string $message ): void { echo 'PASS: ' . $message . PHP_EOL; }

$status = SC_EI_Graph_Crypto::status();
if ( empty( $status['available'] ) ) {
	fail_graph_crypto( 'Build environment has neither sodium nor OpenSSL AES-256-GCM.' );
}
pass_graph_crypto( 'authenticated encryption backend is available' );

$source = array(
	'tenant_id'     => '00000000-0000-0000-0000-000000000001',
	'client_id'     => '00000000-0000-0000-0000-000000000002',
	'client_secret' => 'fixture-secret-value-that-must-not-appear-in-plaintext',
	'expires_at'    => time() + 3600,
);
$sealed = SC_EI_Graph_Crypto::seal_array( $source );
if ( is_wp_error( $sealed ) || ! is_string( $sealed ) || '' === $sealed ) {
	fail_graph_crypto( 'Array encryption failed.' );
}
if ( false !== strpos( $sealed, $source['client_secret'] ) ) {
	fail_graph_crypto( 'Encrypted envelope contains plaintext secret.' );
}
pass_graph_crypto( 'credential payload is encrypted without plaintext leakage' );

$opened = SC_EI_Graph_Crypto::open_array( $sealed );
if ( is_wp_error( $opened ) || $opened !== $source ) {
	fail_graph_crypto( 'Encrypted payload did not round-trip exactly.' );
}
pass_graph_crypto( 'credential payload round-trips through authenticated encryption' );

$payload = json_decode( $sealed, true );
$payload['data'][0] = 'A' === $payload['data'][0] ? 'B' : 'A';
$tampered = json_encode( $payload, JSON_UNESCAPED_SLASHES );
$opened_tampered = SC_EI_Graph_Crypto::open_array( $tampered );
if ( ! is_wp_error( $opened_tampered ) ) {
	fail_graph_crypto( 'Tampered envelope was accepted.' );
}
pass_graph_crypto( 'tampered ciphertext is rejected' );

$fingerprint_a = SC_EI_Graph_Crypto::fingerprint( 'secret-a' );
$fingerprint_b = SC_EI_Graph_Crypto::fingerprint( 'secret-b' );
if ( 12 !== strlen( $fingerprint_a ) || $fingerprint_a === $fingerprint_b ) {
	fail_graph_crypto( 'Secret fingerprints are not stable, short, and distinct.' );
}
pass_graph_crypto( 'secret fingerprints support safe rotation diagnostics' );

echo "Engagement Intake v0.9.2 Graph crypto fixtures passed.\n";
