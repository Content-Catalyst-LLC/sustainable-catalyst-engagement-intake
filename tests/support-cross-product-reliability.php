<?php
/** v1.2.1 Support Operations and Cross-Product Reliability Patch contracts. */
$root = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';
$main = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$schema = file_get_contents( $plugin . '/includes/class-sc-ei-support-schema.php' );
$repo = file_get_contents( $plugin . '/includes/class-sc-ei-support-repository.php' );
$form = file_get_contents( $plugin . '/includes/class-sc-ei-form-handler.php' );
$inquiries = file_get_contents( $plugin . '/includes/class-sc-ei-inquiry-repository.php' );
$platform = file_get_contents( $plugin . '/includes/class-sc-ei-platform-repository.php' );
$validation = file_get_contents( $plugin . '/includes/class-sc-ei-platform-validation.php' );
$view = file_get_contents( $plugin . '/admin/views/support-cases.php' );
$checks = array(
    'patch identity with unchanged database schema' => false !== strpos( $main, 'Version:     1.5.0' ) && false !== strpos( $main, "SC_EI_VERSION', '1.5.0'" ) && false !== strpos( $main, "SC_EI_DB_VERSION', '1.5.0'" ) && false !== strpos( $main, "SC_EI_SUPPORT_SCHEMA_VERSION', '1.0.1'" ),
    'nondestructive patch migration' => false !== strpos( $repo, "PATCH_MIGRATION_KEY = 'v1_2_1_support_operations_cross_product_reliability'" ) && false !== strpos( $repo, "'database_schema_changed' => false" ) && false !== strpos( $repo, 'record_patch_migration' ),
    'atomic public support persistence' => false !== strpos( $form, 'SC_EI_Support_Repository::ensure_public_case' ) && false !== strpos( $form, 'SC_EI_Inquiry_Repository::rollback_public_create' ) && false !== strpos( $inquiries, 'public static function rollback_public_create' ) && false !== strpos( $form, "'support_storage_error'" ),
    'recoverable case and audit persistence' => false !== strpos( $repo, 'for ( $attempt = 1; $attempt <= 3; $attempt++ )' ) && false !== strpos( $repo, "'support_case_event_create_failed'" ) && false !== strpos( $repo, 'database_error_hash' ),
    'strict cross-product identities' => false !== strpos( $schema, 'public static function strict_product' ) && false !== strpos( $schema, 'public static function handoff_id' ) && false !== strpos( $schema, 'public static function source_systems' ) && false !== strpos( $repo, 'SC_EI_Support_Schema::strict_product' ),
    'idempotent handoffs and relationships' => false !== strpos( $repo, "'handoff_receipt'" ) && false !== strpos( $repo, "'idempotent' => true" ) && false !== strpos( $repo, 'public static function find_link' ) && false !== strpos( $repo, 'SELECT id FROM {$table} WHERE support_case_id' ),
    'privacy-safe release and suggestion context' => false !== strpos( $schema, "'feature_suggestion'" ) && false !== strpos( $schema, "'product_release'" ) && false !== strpos( $repo, "'context_from_handoff'" ) && false !== strpos( $repo, "'affected_release'" ),
    'production reliability gates' => false !== strpos( $platform, "'support_reliability_patch'" ) && false !== strpos( $platform, "'support_handoff_reliability'" ) && false !== strpos( $platform, "'support_product_context'" ),
    'live replay and portal isolation validation' => false !== strpos( $validation, '$handoff_first' ) && false !== strpos( $validation, '$handoff_second' ) && false !== strpos( $validation, "'idempotent'" ) && false !== strpos( $validation, '$unsafe_keys' ) && false !== strpos( $validation, "'feature_suggestion' => 'FS-VALIDATION'" ),
    'operations reliability metrics' => false !== strpos( $view, "'Missing product'" ) && false !== strpos( $view, "'Missing version'" ) && false !== strpos( $view, "'Failed handoffs'" ),
    'no autonomous external product action' => false === strpos( $repo, 'wp_remote_post(' ) && false === strpos( $repo, 'wp_mail(' ),
);
$failed = array_keys( array_filter( $checks, static fn( $passed ) => ! $passed ) );
if ( $failed ) { fwrite( STDERR, 'Support cross-product reliability checks failed: ' . implode( ', ', $failed ) . PHP_EOL ); exit( 1 ); }
foreach ( $checks as $label => $passed ) { echo 'PASS: ' . $label . PHP_EOL; }
echo "Sustainable Catalyst Contact and Engagement Platform v1.2.1 support reliability checks passed.\n";
