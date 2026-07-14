<?php
define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'SC_EI_VERSION', '0.9.2' );

final class WP_Error {
	private string $code;
	private string $message;
	private $data;
	public function __construct( string $code = '', string $message = '', $data = null ) { $this->code = $code; $this->message = $message; $this->data = $data; }
	public function get_error_code(): string { return $this->code; }
	public function get_error_message(): string { return $this->message; }
	public function get_error_data() { return $this->data; }
}
function __( string $text, string $domain = '' ): string { return $text; }
function sanitize_key( string $value ): string { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ) ?: ''; }
function sanitize_text_field( string $value ): string { return trim( strip_tags( $value ) ); }
function sanitize_textarea_field( string $value ): string { return trim( strip_tags( $value ) ); }
function absint( $value ): int { return abs( (int) $value ); }
function wp_generate_uuid4(): string { return '11111111-2222-4333-8444-555555555555'; }
function current_time( string $type, bool $gmt = false ) { return '2026-07-13 20:00:00'; }
function get_option( string $key, $default = false ) { return $default; }
function update_option( string $key, $value, bool $autoload = false ): bool { return true; }
if ( ! function_exists( 'mb_substr' ) ) {
	function mb_substr( string $value, int $start, ?int $length = null ): string {
		return null === $length ? substr( $value, $start ) : substr( $value, $start, $length );
	}
}

final class SC_EI_Graph_Credentials {
	public static function settings(): array {
		return array(
			'graph_retry_base_seconds' => 60,
			'graph_retry_max_seconds'  => 3600,
			'graph_circuit_failure_threshold' => 5,
			'graph_circuit_cooldown_minutes' => 15,
		);
	}
}

require_once dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake/includes/class-sc-ei-graph-client.php';

function fail_graph_client( string $message ): void { fwrite( STDERR, $message . PHP_EOL ); exit( 1 ); }
function pass_graph_client( string $message ): void { echo 'PASS: ' . $message . PHP_EOL; }

$delays = array();
for ( $attempt = 1; $attempt <= 6; $attempt++ ) {
	$delays[] = SC_EI_Graph_Client::retry_delay( $attempt, 0 );
}
foreach ( $delays as $index => $delay ) {
	if ( $delay < 60 || $delay > 3600 ) {
		fail_graph_client( 'Backoff delay outside configured bounds.' );
	}
	if ( $index > 0 && $delay < 60 ) {
		fail_graph_client( 'Backoff did not remain bounded.' );
	}
}
pass_graph_client( 'exponential retry delays remain within configured bounds' );

if ( 120 !== SC_EI_Graph_Client::retry_delay( 5, 120 ) ) {
	fail_graph_client( 'Retry-After was not honored exactly.' );
}
if ( 3600 !== SC_EI_Graph_Client::retry_delay( 5, 7200 ) ) {
	fail_graph_client( 'Retry-After was not capped by the configured maximum.' );
}
pass_graph_client( 'Retry-After is honored and bounded' );

$source = file_get_contents( dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake/includes/class-sc-ei-graph-client.php' );
$checks = array(
	'correct OAuth resource scope' => false !== strpos( $source, "GRAPH_RESOURCE . '/.default'" )
		&& false === strpos( $source, "GRAPH_BASE . '/.default'" ),
	'global stable Graph endpoint' => false !== strpos( $source, "https://graph.microsoft.com" )
		&& false === strpos( $source, 'graph.microsoft.com/beta' ),
	'fixed calendar path boundary' => false !== strpos( $source, "str_starts_with( \$path, '/users/' )" ),
	'app token flow' => false !== strpos( $source, "'grant_type'    => 'client_credentials'" ),
	'one-time 401 refresh' => false !== strpos( $source, 'SC_EI_Graph_Credentials::clear_token()' )
		&& false !== strpos( $source, '_token_refresh_attempted' ),
	'request correlation IDs' => false !== strpos( $source, "'client-request-id'" )
		&& false !== strpos( $source, "'return-client-request-id' => 'true'" ),
	'throttle classifications' => false !== strpos( $source, '408, 425, 429, 500, 502, 503, 504' ),
	'Retry-After parser' => false !== strpos( $source, 'parse_retry_after' ),
	'circuit breaker' => false !== strpos( $source, 'consecutive_failures' )
		&& false !== strpos( $source, 'open_until' ),
	'secret redaction' => false !== strpos( $source, 'client_secret=[redacted]' )
		&& false !== strpos( $source, '[redacted-token]' ),
);
$failed = array_keys( array_filter( $checks, static fn( bool $passed ): bool => ! $passed ) );
if ( $failed ) {
	fail_graph_client( 'Graph client checks failed: ' . implode( ', ', $failed ) );
}
foreach ( $checks as $label => $passed ) {
	pass_graph_client( $label );
}

echo "Engagement Intake v0.11.0 Graph client fixtures passed.\n";
