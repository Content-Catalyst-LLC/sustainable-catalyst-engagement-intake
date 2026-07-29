<?php
/**
 * v1.1.1 inquiry persistence and lifecycle reliability contracts.
 */
$root = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';
$main = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$inquiry = file_get_contents( $plugin . '/includes/class-sc-ei-inquiry-repository.php' );
$db = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$platform = file_get_contents( $plugin . '/includes/class-sc-ei-platform-repository.php' );
$validation = file_get_contents( $plugin . '/includes/class-sc-ei-platform-validation.php' );
$hardening = file_get_contents( $plugin . '/includes/class-sc-ei-hardening-repository.php' );
$readme = file_get_contents( $plugin . '/readme.txt' );
$migration = file_get_contents( $root . '/docs/MIGRATION-v1.1.1.md' );

$checks = array(
    'v1.1.1 identity with unchanged database schema' => false !== strpos( $main, 'Version:     2.0.1' )
        && false !== strpos( $main, "SC_EI_VERSION', '2.0.1'" )
        && false !== strpos( $main, "SC_EI_DB_VERSION', '2.0.0'" )
        && false !== strpos( $main, "SC_EI_PLATFORM_SCHEMA_VERSION', '2.0.0'" ),
    'qualification score uses non-null database default' => false !== strpos( $inquiry, "'qualification_score'      => 0" )
        && false === strpos( $inquiry, "'qualification_score'      => null" )
        && false !== strpos( $db, 'qualification_score smallint(5) unsigned NOT NULL DEFAULT 0' ),
    'failed inserts record protected diagnostics' => false !== strpos( $inquiry, "'inquiry_insert_failed'" )
        && false !== strpos( $inquiry, "'database_error_hash'" )
        && false !== strpos( $inquiry, 'Reliability reference:' ),
    'database version advances only after contract verification' => false !== strpos( $db, 'public static function required_contract(): array' )
        && false !== strpos( $db, 'if ( ! in_array( false, $contract, true ) )' )
        && false !== strpos( $db, "update_option( 'sc_ei_db_version', SC_EI_DB_VERSION, false )" )
        && false !== strpos( $db, "'database_contract_incomplete'" ),
    'readiness verifies inquiry columns and patch journal' => false !== strpos( $platform, "'inquiry_columns'" )
        && false !== strpos( $platform, "PERSISTENCE_PATCH_MIGRATION_KEY = 'v1_1_1_inquiry_persistence_lifecycle_reliability'" )
        && false !== strpos( $platform, "'persistence_patch_migration_journal'" ),
    'live validation includes inquiry write-path columns' => false !== strpos( $validation, 'SC_EI_Database::inquiry_columns_exist()' )
        && false !== strpos( $validation, '%d/%d inquiry columns' )
        && false !== strpos( $validation, "'[TEST] v1.6.0 live validation'" ),
    'watchdog includes inquiry and lifecycle columns' => false !== strpos( $hardening, "checks['inquiry_columns']" )
        && false !== strpos( $hardening, "checks['lifecycle_columns']" ),
    'nondestructive migration documentation' => false !== strpos( $migration, 'nondestructive' )
        && false !== strpos( $migration, 'database schema version remains `1.1.0`' ),
    'WordPress stable tag updated' => false !== strpos( $readme, 'Stable tag: 2.0.1' ),
);
$failed = array_keys( array_filter( $checks, static fn( $value ) => ! $value ) );
if ( $failed ) {
    fwrite( STDERR, 'Inquiry persistence reliability checks failed: ' . implode( ', ', $failed ) . PHP_EOL );
    exit( 1 );
}
foreach ( $checks as $label => $passed ) {
    echo 'PASS: ' . $label . PHP_EOL;
}
echo "Sustainable Catalyst Contact and Engagement Platform v1.1.1 persistence reliability checks passed.\n";
