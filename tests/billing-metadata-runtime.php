<?php
/** Executable privacy boundary for v1.7.0 payment metadata. */
define( 'ABSPATH', __DIR__ );
function __( $value, $domain = null ) { return $value; }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $value ) ); }
function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
function is_email( $value ) { return false !== filter_var( $value, FILTER_VALIDATE_EMAIL ); }
require dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake/includes/class-sc-ei-billing-schema.php';
$checks = array(
    'clean aggregate metadata accepted' => SC_EI_Billing_Schema::payment_metadata_is_safe( array( 'environment' => 'production', 'event' => 'invoice.settled', 'attempt' => 1 ) ),
    'card key rejected' => ! SC_EI_Billing_Schema::payment_metadata_is_safe( array( 'card_number' => '4242424242424242' ) ),
    'card-like value rejected' => ! SC_EI_Billing_Schema::payment_metadata_is_safe( array( 'reference' => '4242 4242 4242 4242' ) ),
    'email value rejected' => ! SC_EI_Billing_Schema::payment_metadata_is_safe( array( 'reference' => 'person@example.com' ) ),
    'IP value rejected' => ! SC_EI_Billing_Schema::payment_metadata_is_safe( array( 'reference' => '192.168.1.10' ) ),
    'secret key rejected' => ! SC_EI_Billing_Schema::payment_metadata_is_safe( array( 'client_secret' => 'do-not-store' ) ),
    'excessive nesting rejected' => ! SC_EI_Billing_Schema::payment_metadata_is_safe( array( 'a' => array( 'b' => array( 'c' => array( 'd' => array( 'e' => array( 'f' => array( 'g' => 1 ) ) ) ) ) ) ) ),
    'currency normalized' => 'USD' === SC_EI_Billing_Schema::sanitize_currency( 'usd' ),
    'invalid currency defaults' => 'USD' === SC_EI_Billing_Schema::sanitize_currency( 'US' ),
);
$failed = array_keys( array_filter( $checks, static fn( bool $passed ): bool => ! $passed ) );
if ( $failed ) { fwrite( STDERR, 'Billing metadata runtime checks failed: ' . implode( ', ', $failed ) . PHP_EOL ); exit( 1 ); }
echo 'Billing metadata runtime checks passed (' . count( $checks ) . ' assertions).' . PHP_EOL;
