<?php
if ( ! defined( 'ABSPATH' ) ) define( 'ABSPATH', __DIR__ . '/' );
if ( ! defined( 'SC_EI_WORKFLOW_CORE_SCHEMA_VERSION' ) ) define( 'SC_EI_WORKFLOW_CORE_SCHEMA_VERSION', '1.0.0' );
class WP_Error {
	private string $code;
	private string $message;
	public function __construct( string $code = '', string $message = '' ) { $this->code = $code; $this->message = $message; }
	public function get_error_code(): string { return $this->code; }
	public function get_error_message(): string { return $this->message; }
}
function is_wp_error( $value ): bool { return $value instanceof WP_Error; }
function __( string $value, string $domain = '' ): string { return $value; }
function sanitize_key( string $value ): string { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', $value ) ); }
function wp_json_encode( $value, int $flags = 0 ) { return json_encode( $value, $flags ); }
function wp_salt( string $scheme = 'auth' ): string { return 'fixture-site-salt-' . $scheme; }
function apply_filters( string $hook, $value ) { return $value; }

$plugin = dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake';
require_once $plugin . '/includes/class-sc-ei-workflow-core-schema.php';
require_once $plugin . '/includes/class-sc-ei-workflow-core-contract.php';

function fixture_assert( bool $condition, string $label ): void {
	if ( ! $condition ) { fwrite( STDERR, 'FAIL: ' . $label . PHP_EOL ); exit( 1 ); }
	echo 'PASS: ' . $label . PHP_EOL;
}

$payload_a = array(
	'zeta' => 4,
	'alpha' => array( 'second' => 2, 'first' => 1 ),
	'list' => array( 'b', 'a' ),
);
$payload_b = array(
	'list' => array( 'b', 'a' ),
	'alpha' => array( 'first' => 1, 'second' => 2 ),
	'zeta' => 4,
);
$sealed_a = SC_EI_Workflow_Core_Contract::seal_payload( $payload_a, 'workbench' );
$sealed_b = SC_EI_Workflow_Core_Contract::seal_payload( $payload_b, 'workbench' );
fixture_assert( ! is_wp_error( $sealed_a ) && ! is_wp_error( $sealed_b ), 'canonical payloads seal successfully' );
fixture_assert( $sealed_a['payload_json'] === $sealed_b['payload_json'], 'associative key order is canonical' );
fixture_assert( $sealed_a['content_hash'] === $sealed_b['content_hash'], 'canonical payloads have stable SHA-256 hashes' );
fixture_assert( $sealed_a['signature'] === $sealed_b['signature'], 'canonical payloads have stable HMAC signatures' );
fixture_assert( 64 === strlen( $sealed_a['content_hash'] ) && 64 === strlen( $sealed_a['signature'] ), 'hash and signature use 64-character SHA-256 hex encoding' );
fixture_assert(
	SC_EI_Workflow_Core_Contract::verify( $sealed_a['payload_json'], 'workbench', $sealed_a['content_hash'], $sealed_a['signature'] ),
	'valid sealed handoff verifies'
);
fixture_assert(
	! SC_EI_Workflow_Core_Contract::verify( $sealed_a['payload_json'] . ' ', 'workbench', $sealed_a['content_hash'], $sealed_a['signature'] ),
	'tampered payload is rejected'
);
fixture_assert(
	! SC_EI_Workflow_Core_Contract::verify( $sealed_a['payload_json'], 'decision_studio', $sealed_a['content_hash'], $sealed_a['signature'] ),
	'target substitution is rejected'
);
fixture_assert(
	! SC_EI_Workflow_Core_Contract::verify( $sealed_a['payload_json'], 'workbench', $sealed_a['content_hash'], str_repeat( '0', 64 ) ),
	'tampered signature is rejected'
);
$changed = SC_EI_Workflow_Core_Contract::seal_payload( array_merge( $payload_a, array( 'new' => true ) ), 'workbench' );
fixture_assert( $changed['content_hash'] !== $sealed_a['content_hash'], 'payload changes produce a new content hash' );
echo "Engagement Intake v1.0.0 Workflow Core contract fixtures passed.\n";
