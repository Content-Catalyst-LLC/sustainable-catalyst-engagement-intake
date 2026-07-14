<?php
/**
 * Execute SC_EI_Inquiry_Repository::create() against a strict fake database.
 */
define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'SC_EI_VERSION', '1.1.1' );
define( 'SC_EI_DB_VERSION', '1.1.0' );

function current_time( $type, $gmt = false ) { return '2026-07-14 23:00:00'; }
function sanitize_key( $v ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $v ) ); }
function sanitize_text_field( $v ) { return trim( (string) $v ); }
function sanitize_textarea_field( $v ) { return trim( (string) $v ); }
function sanitize_email( $v ) { return strtolower( trim( (string) $v ) ); }
function esc_url_raw( $v ) { return trim( (string) $v ); }
function absint( $v ) { return abs( (int) $v ); }
function wp_json_encode( $v, $flags = 0 ) { return json_encode( $v, $flags ); }
function wp_generate_uuid4() { return '11111111-1111-4111-8111-111111111111'; }
function wp_generate_password( $length = 12, $special = true, $extra = false ) { return substr( 'ABC123XYZ789', 0, $length ); }
function wp_rand( $min, $max ) { return $min; }
function wp_parse_args( $args, $defaults = array() ) { return array_merge( $defaults, is_array( $args ) ? $args : array() ); }
function get_option( $key, $default = false ) { return $default; }
function __( $text, $domain = null ) { return $text; }

final class SC_EI_Statuses {
    public static function is_valid( $status ) { return in_array( $status, array( 'new' ), true ); }
    public static function inquiry_types() { return array( 'general' => 'General', 'other' => 'Other' ); }
}
final class SC_EI_Conversion {
    public static function sanitize_variant( $v ) { return 'advanced'; }
    public static function sanitize_source( $v ) { return 'platform-validation'; }
    public static function sanitize_entry_cta( $v ) { return 'admin-live-validation'; }
    public static function route( $type, $service, $variant ) { return 'general'; }
    public static function guidance_flags( $service, $budget, $message ) { return array(); }
}
final class SC_EI_Teams {
    public static function contact_methods() { return array( 'email' => 'Email' ); }
    public static function meeting_requests() { return array( 'no' => 'No' ); }
    public static function scheduling_statuses() { return array( 'not_requested' => 'Not requested' ); }
    public static function sanitize_weekdays( $v ) { return array(); }
    public static function sanitize_participant_emails( $v ) { return array(); }
    public static function valid_timezone( $v ) { return true; }
}
final class SC_EI_Review_Schema {
    public static function sanitize_choice( $value, $choices, $fallback ) { return $fallback; }
    public static function priorities() { return array( 'normal' => 'Normal' ); }
    public static function default_due_at( $priority ) { return '2026-07-17 23:00:00'; }
    public static function sanitize_checklist( $items ) { return array(); }
}
final class SC_EI_Privacy_Schema {
    public static function default_settings() { return array( 'default_unaccepted_retention_days' => 365 ); }
}
final class SC_EI_Lifecycle_Schema {
    public static function map_legacy_status( $status ) { return 'new_inquiry'; }
}
final class SC_EI_Database {
    public static function table( $name ) { return 'wp_sc_ei_' . $name; }
}
final class SC_EI_Audit_Log {
    public static function record( ...$args ) { return true; }
}
final class SC_EI_Hardening_Repository {
    public static array $events = array();
    public static function request_id() { return '22222222-2222-4222-8222-222222222222'; }
    public static function record_event( $component, $event_type, $severity, $message, $context = array() ) { self::$events[] = compact( 'component', 'event_type', 'severity', 'message', 'context' ); }
}
final class StrictFakeWpdb {
    public int $insert_id = 0;
    public string $last_error = '';
    public array $inserted_data = array();
    public array $inserted_formats = array();
    public function prepare( $query, ...$args ) { return $query; }
    public function get_var( $query ) { return null; }
    public function insert( $table, $data, $formats ) {
        if ( ! array_key_exists( 'qualification_score', $data ) || null === $data['qualification_score'] ) {
            $this->last_error = "Column 'qualification_score' cannot be null";
            return false;
        }
        if ( 0 !== $data['qualification_score'] ) {
            $this->last_error = 'qualification_score must initialize to zero';
            return false;
        }
        $key = array_search( 'qualification_score', array_keys( $data ), true );
        if ( false === $key || '%d' !== $formats[ $key ] ) {
            $this->last_error = 'qualification_score must use integer format';
            return false;
        }
        $this->inserted_data = $data;
        $this->inserted_formats = $formats;
        $this->insert_id = 91;
        return 1;
    }
}

$wpdb = new StrictFakeWpdb();
require dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake/includes/class-sc-ei-inquiry-repository.php';
$id = SC_EI_Inquiry_Repository::create(
    array(
        'inquiry_type' => 'general',
        'contact_name' => 'Runtime Test',
        'contact_email' => 'runtime@example.com',
        'subject' => 'Strict persistence test',
        'message' => 'Verify non-null lifecycle defaults.',
        'form_variant' => 'advanced',
        'source_page' => 'platform-validation',
        'entry_cta' => 'admin-live-validation',
    )
);
$checks = array(
    'create returns inserted id' => 91 === $id,
    'qualification score persisted as zero' => 0 === $wpdb->inserted_data['qualification_score'],
    'qualification status remains not started' => 'not_started' === $wpdb->inserted_data['qualification_status'],
    'qualification score uses integer format' => '%d' === $wpdb->inserted_formats[ array_search( 'qualification_score', array_keys( $wpdb->inserted_data ), true ) ],
    'strict adapter reported no error' => '' === $wpdb->last_error,
    'no reliability failure event emitted' => array() === SC_EI_Hardening_Repository::$events,
);
$failed = array_keys( array_filter( $checks, static fn( $value ) => ! $value ) );
if ( $failed ) {
    fwrite( STDERR, 'Inquiry persistence runtime test failed: ' . implode( ', ', $failed ) . PHP_EOL );
    exit( 1 );
}
foreach ( $checks as $label => $passed ) {
    echo 'PASS: ' . $label . PHP_EOL;
}
echo "Strict inquiry persistence runtime regression passed.\n";
