<?php
/** v2.0.0 Integrated Advisory, Support, and Institutional Engagement Platform contracts. */
$root = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';
$main = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$db = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$schema = file_get_contents( $plugin . '/includes/class-sc-ei-unified-platform-schema.php' );
$repo = file_get_contents( $plugin . '/includes/class-sc-ei-unified-platform-repository.php' );
$admin = file_get_contents( $plugin . '/includes/class-sc-ei-command-center-admin.php' );
$view = file_get_contents( $plugin . '/admin/views/command-center.php' );
$platform = file_get_contents( $plugin . '/includes/class-sc-ei-platform-repository.php' );
$validation = file_get_contents( $plugin . '/includes/class-sc-ei-platform-validation.php' );
$privacy = file_get_contents( $plugin . '/includes/class-sc-ei-privacy.php' );
$retention = file_get_contents( $plugin . '/includes/class-sc-ei-retention-engine.php' );
$rest = file_get_contents( $plugin . '/includes/class-sc-ei-rest.php' );
$uninstall = file_get_contents( $plugin . '/uninstall.php' );
$readme = file_get_contents( $plugin . '/readme.txt' );
$checks = array(
    'v2 identity and database contract' => false !== strpos( $main, 'Version:     2.0.1' ) && false !== strpos( $main, "SC_EI_DB_VERSION', '2.0.0'" ) && false !== strpos( $main, "SC_EI_PLATFORM_SCHEMA_VERSION', '2.0.0'" ) && false !== strpos( $main, "SC_EI_UNIFIED_PLATFORM_SCHEMA_VERSION', '2.0.0'" ),
    'stable tag advances' => false !== strpos( $readme, 'Stable tag: 2.0.1' ),
    'unified classes loaded' => false !== strpos( $main, 'class-sc-ei-unified-platform-schema.php' ) && false !== strpos( $main, 'class-sc-ei-unified-platform-repository.php' ) && false !== strpos( $main, 'class-sc-ei-command-center-admin.php' ),
    'typed dossier contracts' => false !== strpos( $schema, "DOSSIER_SCHEMA = 'sc-engagement-dossier/2.0'" ) && false !== strpos( $schema, "HANDOFF_SCHEMA = 'sc-engagement-platform-handoff/2.0'" ) && false !== strpos( $schema, "EVENT_SCHEMA = 'sc-engagement-dossier-event/2.0'" ),
    'route and phase normalization' => false !== strpos( $schema, 'route_groups()' ) && false !== strpos( $schema, 'phases()' ) && false !== strpos( $schema, 'health_states()' ),
    'privacy-safe handoff boundary' => false !== strpos( $schema, 'handoff_forbidden_keys' ) && false !== strpos( $schema, 'handoff_payload_is_safe' ) && false !== strpos( $schema, "'card_number'" ) && false !== strpos( $schema, "'access_token'" ),
    'four nondestructive tables' => false !== strpos( $db, '$sql_engagement_dossiers' ) && false !== strpos( $db, '$sql_dossier_relationships' ) && false !== strpos( $db, '$sql_dossier_events' ) && false !== strpos( $db, '$sql_platform_handoffs' ),
    'database contract verifies unified columns' => false !== strpos( $db, 'unified_platform_columns_exist' ) && false !== strpos( $db, "'engagement_dossiers'" ) && false !== strpos( $db, "'platform_handoffs'" ),
    'v2 migration journal' => false !== strpos( $repo, "MIGRATION_KEY = 'v2_0_0_integrated_advisory_support_institutional_platform'" ) && false !== strpos( $repo, 'record_migration' ),
    'canonical dossier refresh and backfill' => false !== strpos( $repo, 'refresh_dossier' ) && false !== strpos( $repo, 'backfill' ) && false !== strpos( $repo, 'replace_relationships' ),
    'cross-module entities linked' => false !== strpos( $repo, "'support_case'" ) && false !== strpos( $repo, "'meeting'" ) && false !== strpos( $repo, "'proposal'" ) && false !== strpos( $repo, "'workspace'" ) && false !== strpos( $repo, "'invoice'" ),
    'unified timeline aggregates modules' => false !== strpos( $repo, 'public static function timeline' ) && false !== strpos( $repo, "'lifecycle_events'" ) && false !== strpos( $repo, "'support_case_events'" ) && false !== strpos( $repo, "'billing_events'" ),
    'handoffs are idempotent' => false !== strpos( $repo, 'handoff_key' ) && false !== strpos( $repo, 'if ( $existing )' ) && false !== strpos( $repo, "'status'        => 'accepted'" ),
    'v2 REST resources' => false !== strpos( $repo, "'sc-engagement-intake/v2'" ) && false !== strpos( $repo, "'/dossiers'" ) && false !== strpos( $repo, "'/handoffs'" ),
    'command center administration' => false !== strpos( $admin, 'Integrated Engagement Command Center' ) && false !== strpos( $admin, 'backfill' ) && false !== strpos( $view, 'Unified activity timeline' ),
    'production readiness includes v2 integrity' => false !== strpos( $platform, 'unified_platform_columns' ) && false !== strpos( $platform, 'unified_platform_migration_journal' ) && false !== strpos( $platform, 'unified_platform_integrity' ),
    'repair center supports dossier recovery' => false !== strpos( $platform, "'verify_unified_platform_migration'" ) && false !== strpos( $platform, "'repair_unified_platform'" ),
    'live validation executes dossier and handoff' => false !== strpos( $validation, 'integrated_engagement_platform' ) && false !== strpos( $validation, 'SC_EI_Unified_Platform_Repository::refresh_dossier' ) && false !== strpos( $validation, 'platform_handoff_private_data_rejected' ) && false !== strpos( $validation, 'sc-contact-engagement-live-validation/3.0' ),
    'privacy export covers integrated records' => false !== strpos( $privacy, 'Integrated Engagement Dossier' ) && false !== strpos( $privacy, 'Integrated Platform Handoffs' ),
    'retention redacts dossier data' => false !== strpos( $retention, 'SC_EI_Unified_Platform_Repository::redact_for_privacy' ),
    'inquiry REST exposes authorized dossier' => false !== strpos( $rest, "'integrated_engagement_dossier'" ) && false !== strpos( $rest, "current_user_can( 'sc_intake_view_platform' )" ),
    'uninstall clears unified options' => false !== strpos( $uninstall, "delete_option( 'sc_ei_unified_platform_schema_version' )" ),
    'no autonomous decisions or cross-case merging' => false !== strpos( $schema, "'unified_platform_no_auto_decisions'" ) && false !== strpos( $schema, "'unified_platform_no_cross_case_merging'" ),
);
$failed = array_keys( array_filter( $checks, static fn( bool $passed ): bool => ! $passed ) );
if ( $failed ) { fwrite( STDERR, 'Integrated platform v2 checks failed: ' . implode( ', ', $failed ) . PHP_EOL ); exit( 1 ); }
foreach ( $checks as $name => $passed ) { echo 'PASS: ' . $name . PHP_EOL; }
echo 'Sustainable Catalyst Contact and Engagement Platform v2.0.0 integrated platform checks passed (' . count( $checks ) . ' assertions).' . PHP_EOL;
