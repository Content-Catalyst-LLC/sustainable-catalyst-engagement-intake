<?php
/** Executable v2.0 handoff privacy and normalization boundary. */
define( 'ABSPATH', __DIR__ );
function __( $value, $domain = '' ) { return $value; }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) ); }
function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
function is_email( $value ) { return false !== filter_var( $value, FILTER_VALIDATE_EMAIL ); }
require dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake/includes/class-sc-ei-unified-platform-schema.php';
$checks = array(
    'clean product context accepted' => SC_EI_Unified_Platform_Schema::handoff_payload_is_safe( array( 'product' => 'workbench', 'product_version' => '5.0.0', 'component' => 'graph-studio', 'resolution_attempted' => true ) ),
    'email key rejected' => ! SC_EI_Unified_Platform_Schema::handoff_payload_is_safe( array( 'email' => 'person@example.com' ) ),
    'email value rejected' => ! SC_EI_Unified_Platform_Schema::handoff_payload_is_safe( array( 'reference' => 'person@example.com' ) ),
    'phone-like value rejected' => ! SC_EI_Unified_Platform_Schema::handoff_payload_is_safe( array( 'reference' => '+1 (312) 555-1234' ) ),
    'IP value rejected' => ! SC_EI_Unified_Platform_Schema::handoff_payload_is_safe( array( 'reference' => '192.168.1.10' ) ),
    'credential key rejected' => ! SC_EI_Unified_Platform_Schema::handoff_payload_is_safe( array( 'access_token' => 'private' ) ),
    'payment key rejected' => ! SC_EI_Unified_Platform_Schema::handoff_payload_is_safe( array( 'card_number' => '4242424242424242' ) ),
    'excessive nesting rejected' => ! SC_EI_Unified_Platform_Schema::handoff_payload_is_safe( array( 'a' => array( 'b' => array( 'c' => array( 'd' => array( 'e' => array( 'f' => array( 'g' => 1 ) ) ) ) ) ) ) ),
    'unknown route defaults to general' => 'general' === SC_EI_Unified_Platform_Schema::sanitize_route_group( 'unknown-route' ),
    'support route retained' => 'support' === SC_EI_Unified_Platform_Schema::sanitize_route_group( 'support' ),
    'unknown phase defaults to intake' => 'intake' === SC_EI_Unified_Platform_Schema::sanitize_phase( 'unknown-phase' ),
);
$failed = array_keys( array_filter( $checks, static fn( bool $passed ): bool => ! $passed ) );
if ( $failed ) { fwrite( STDERR, 'Integrated platform handoff runtime checks failed: ' . implode( ', ', $failed ) . PHP_EOL ); exit( 1 ); }
echo 'Integrated platform handoff runtime checks passed (' . count( $checks ) . ' assertions).' . PHP_EOL;
