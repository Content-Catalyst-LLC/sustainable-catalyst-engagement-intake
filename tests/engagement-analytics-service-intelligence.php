<?php
/** v1.6.0 Engagement Analytics and Service Intelligence contracts. */
$root = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';
$main = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$db = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$schema = file_get_contents( $plugin . '/includes/class-sc-ei-service-intelligence-schema.php' );
$repo = file_get_contents( $plugin . '/includes/class-sc-ei-service-intelligence-repository.php' );
$admin = file_get_contents( $plugin . '/includes/class-sc-ei-service-intelligence-admin.php' );
$view = file_get_contents( $plugin . '/admin/views/service-intelligence.php' );
$platform = file_get_contents( $plugin . '/includes/class-sc-ei-platform-repository.php' );
$validation = file_get_contents( $plugin . '/includes/class-sc-ei-platform-validation.php' );
$diagnostics = file_get_contents( $plugin . '/includes/class-sc-ei-diagnostics.php' );
$privacy = file_get_contents( $plugin . '/includes/class-sc-ei-privacy-repository.php' );
$uninstall = file_get_contents( $plugin . '/uninstall.php' );
$analytics = file_get_contents( $plugin . '/includes/class-sc-ei-analytics-repository.php' );
$readme = file_get_contents( $plugin . '/readme.txt' );
$checks = array(
    'v1.6.0 release identity' => false !== strpos( $main, 'Version:     2.0.2' ) && false !== strpos( $main, "SC_EI_DB_VERSION', '2.0.0'" ) && false !== strpos( $main, "SC_EI_PLATFORM_SCHEMA_VERSION', '2.0.0'" ) && false !== strpos( $main, "SC_EI_ANALYTICS_SCHEMA_VERSION', '1.1.0'" ) && false !== strpos( $main, "SC_EI_SERVICE_INTELLIGENCE_SCHEMA_VERSION', '1.0.0'" ),
    'service intelligence modules loaded' => false !== strpos( $main, 'class-sc-ei-service-intelligence-schema.php' ) && false !== strpos( $main, 'class-sc-ei-service-intelligence-repository.php' ) && false !== strpos( $main, 'class-sc-ei-service-intelligence-admin.php' ),
    'two aggregate intelligence tables installed' => false !== strpos( $db, '$sql_service_intelligence_findings' ) && false !== strpos( $db, '$sql_service_intelligence_events' ) && false !== strpos( $db, 'service_intelligence_columns_exist' ),
    'nondestructive v1.6.0 migration journal' => false !== strpos( $repo, "MIGRATION_KEY = 'v1_6_0_engagement_analytics_service_intelligence'" ) && false !== strpos( $repo, "'no_destructive_migration' => true" ),
    'aggregate-only service intelligence schemas' => false !== strpos( $schema, 'sc-engagement-service-intelligence/1.0' ) && false !== strpos( $schema, 'sc-service-intelligence-finding/1.0' ),
    'privacy key rejection' => false !== strpos( $schema, 'forbidden_evidence_keys' ) && false !== strpos( $schema, "'email'" ) && false !== strpos( $schema, "'attachment'" ) && false !== strpos( $schema, "'inquiry_id'" ),
    'privacy value rejection and bounded evidence' => false !== strpos( $schema, 'strlen( $encoded ) > 20000' ) && false !== strpos( $schema, 'is_email' ) && false !== strpos( $schema, 'preg_match' ),
    'minimum cohort suppression retained' => false !== strpos( $repo, 'analytics_minimum_cohort' ) && false !== strpos( $repo, 'service_intelligence_small_cohort_rejected' ) && false !== strpos( $repo, 'SC_EI_Analytics_Schema::suppress' ),
    'support service demand metrics' => false !== strpos( $repo, "'support'" ) && false !== strpos( $repo, 'median_hours_to_triage' ) && false !== strpos( $repo, 'known_issue_match' ) && false !== strpos( $repo, "'products' => \$product_mix" ),
    'collaboration intelligence metrics' => false !== strpos( $repo, "'collaboration'" ) && false !== strpos( $repo, 'workspace_completion' ) && false !== strpos( $repo, 'deliverable_acceptance' ),
    'commercial funnel intelligence metrics' => false !== strpos( $repo, "'commercial'" ) && false !== strpos( $repo, 'proposal_acceptance' ) && false !== strpos( $repo, 'engagement_activation' ),
    'time series remains aggregate' => false !== strpos( $repo, 'weekly_series' ) && false !== strpos( $repo, "DATE_FORMAT(created_at, '%%x-W%%v')" ),
    'no sender ranking or automated decisions' => false !== strpos( $repo, "'sender_ranking' => false" ) && false !== strpos( $repo, "'automated_decisions' => false" ) && false !== strpos( $schema, "'analytics_no_sender_ranking'" ),
    'human-reviewed finding state machine' => false !== strpos( $schema, "'candidate'" ) && false !== strpos( $schema, "'reviewing'" ) && false !== strpos( $schema, "'actioned'" ) && false !== strpos( $repo, 'transition_finding' ),
    'typed human confirmation and optimistic locking' => false !== strpos( $repo, '$expected = strtoupper( $status' ) && false !== strpos( $repo, "'row_version'    => absint( \$current['row_version'] ) + 1" ) && false !== strpos( $repo, 'service_intelligence_transition_conflict' ),
    'finding event compensation' => false !== strpos( $repo, 'service_intelligence_event_failed' ) && false !== strpos( $repo, 'the transition was rolled back' ) && false !== strpos( $repo, 'return false !== $result' ),
    'auditable aggregate snapshots' => false !== strpos( $repo, 'create_snapshot' ) && false !== strpos( $repo, 'content_hash' ) && false !== strpos( $repo, 'service_intelligence_snapshot_created' ),
    'retention applies only to closed findings' => false !== strpos( $repo, 'prune_closed_findings' ) && false !== strpos( $repo, "status IN ('closed','dismissed')" ) && false !== strpos( $analytics, 'SC_EI_Service_Intelligence_Repository::prune_closed_findings' ),
    'least privilege administrative actions' => false !== strpos( $admin, "current_user_can( 'sc_intake_view_analytics' )" ) && false !== strpos( $admin, "current_user_can( 'sc_intake_manage_analytics' )" ) && false !== strpos( $admin, "current_user_can( 'sc_intake_export_analytics' )" ),
    'human snapshot and finding confirmation' => false !== strpos( $admin, 'SNAPSHOT SERVICE INTELLIGENCE' ) && false !== strpos( $admin, 'CREATE AGGREGATE FINDING' ),
    'aggregate JSON export disclosure' => false !== strpos( $admin, 'service_intelligence_exported' ) && false !== strpos( $admin, "'personal_data' => false" ) && false !== strpos( $view, 'Export aggregate JSON' ),
    'readiness requires schema migration and blocker review' => false !== strpos( $platform, 'service_intelligence_columns' ) && false !== strpos( $platform, 'service_intelligence_migration_journal' ) && false !== strpos( $platform, 'service_intelligence_operational_blockers' ),
    'diagnostics expose aggregate evidence only' => false !== strpos( $diagnostics, 'service_intelligence_schema_version' ) && false !== strpos( $diagnostics, "'metrics' => \$service_intelligence_metrics" ) && false !== strpos( $diagnostics, "'blockers' => \$service_intelligence_blockers" ),
    'live validation exercises privacy finding audit and snapshot' => false !== strpos( $validation, 'service_intelligence_personal_data_rejected' ) && false !== strpos( $validation, 'transition_finding' ) && false !== strpos( $validation, 'evidence_hash' ) && false !== strpos( $validation, 'create_snapshot' ) && false !== strpos( $validation, 'sc-contact-engagement-live-validation/3.0' ),
    'privacy inventory and uninstall cleanup' => false !== strpos( $privacy, "'analytics_snapshots', 'service_intelligence_findings', 'service_intelligence_events'" ) && false !== strpos( $uninstall, "delete_option( 'sc_ei_service_intelligence_schema_version' )" ) && false !== strpos( $uninstall, "delete_option( 'sc_ei_service_intelligence_last_snapshot' )" ),
    'stable tag advances to v1.6.0' => false !== strpos( $readme, 'Stable tag: 2.0.2' ),
);
$failed = array_keys( array_filter( $checks, static fn( bool $passed ): bool => ! $passed ) );
if ( $failed ) {
    fwrite( STDERR, 'Engagement analytics and service intelligence checks failed: ' . implode( ', ', $failed ) . PHP_EOL );
    exit( 1 );
}
echo 'Sustainable Catalyst Contact and Engagement Platform v1.6.0 analytics and service intelligence checks passed (' . count( $checks ) . ' assertions).' . PHP_EOL;
