<?php
/** Execute strict local civil-time parsing without loading WordPress. */
define( 'ABSPATH', __DIR__ );
define( 'DAY_IN_SECONDS', 86400 );
function sanitize_text_field( $value ) { return trim( (string) $value ); }
function __( $value, $domain = '' ) { return $value; }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function apply_filters( $hook, $value ) { return $value; }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
function sanitize_email( $value ) { return trim( (string) $value ); }
function is_email( $value ) { return false !== filter_var( $value, FILTER_VALIDATE_EMAIL ); }
function esc_url_raw( $value ) { return trim( (string) $value ); }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
class WP_Error {
 private string $code; private string $message;
 public function __construct( string $code, string $message = '' ) { $this->code=$code; $this->message=$message; }
 public function get_error_code(): string { return $this->code; }
 public function get_error_message(): string { return $this->message; }
}
require dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake/includes/class-sc-ei-teams.php';
$valid=SC_EI_Teams::parse_local_datetime('2026-07-15T10:30','America/Chicago');
$gap=SC_EI_Teams::parse_local_datetime('2026-03-08T02:30','America/Chicago');
$overlap=SC_EI_Teams::parse_local_datetime('2026-11-01T01:30','America/Chicago');
$checks=array(
 'valid summer time converts deterministically'=>!is_wp_error($valid)&&'2026-07-15 15:30:00'===$valid['utc'],
 'spring-forward gap rejected'=>is_wp_error($gap)&&'calendar_local_datetime_nonexistent'===$gap->get_error_code(),
 'fall-back repeated time rejected'=>is_wp_error($overlap)&&'calendar_local_datetime_ambiguous'===$overlap->get_error_code(),
 'approved Teams domain accepted'=>SC_EI_Teams::is_teams_url('https://teams.microsoft.com/l/meetup-join/test'),
 'lookalike Teams domain rejected'=>!SC_EI_Teams::is_teams_url('https://teams.microsoft.com.example.org/meeting'),
);
$failed=array_keys(array_filter($checks,fn($ok)=>!$ok));
if($failed){fwrite(STDERR,'Calendar timezone runtime checks failed: '.implode(', ',$failed).PHP_EOL);exit(1);} echo 'Sustainable Catalyst v1.3.1 calendar timezone runtime checks passed ('.count($checks).' assertions).'.PHP_EOL;
