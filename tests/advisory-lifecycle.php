<?php
/**
 * v1.1.0 Advisory Operations and Engagement Lifecycle contracts.
 */
$root = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';
$main = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$schema = file_get_contents( $plugin . '/includes/class-sc-ei-lifecycle-schema.php' );
$repository = file_get_contents( $plugin . '/includes/class-sc-ei-lifecycle-repository.php' );
$admin = file_get_contents( $plugin . '/includes/class-sc-ei-lifecycle-admin.php' );
$view = file_get_contents( $plugin . '/admin/views/advisory-lifecycle.php' );
$db = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$portal_public = file_get_contents( $plugin . '/includes/class-sc-ei-portal-public.php' );
$portal_view = file_get_contents( $plugin . '/public/views/sender-portal.php' );
$notification = file_get_contents( $plugin . '/includes/class-sc-ei-notification-service.php' );
$communication_schema = file_get_contents( $plugin . '/includes/class-sc-ei-communication-schema.php' );
$privacy = file_get_contents( $plugin . '/includes/class-sc-ei-privacy.php' );
$retention = file_get_contents( $plugin . '/includes/class-sc-ei-retention-engine.php' );
$platform = file_get_contents( $plugin . '/includes/class-sc-ei-platform-repository.php' );
$validation = file_get_contents( $plugin . '/includes/class-sc-ei-platform-validation.php' );
$pilot = file_get_contents( $plugin . '/includes/class-sc-ei-pilot-operations.php' );
$capabilities = file_get_contents( $plugin . '/includes/class-sc-ei-capabilities.php' );
$uninstall = file_get_contents( $plugin . '/uninstall.php' );
$docs = file_exists( $root . '/docs/ADVISORY-LIFECYCLE.md' ) ? file_get_contents( $root . '/docs/ADVISORY-LIFECYCLE.md' ) : '';
$migration = file_exists( $root . '/docs/MIGRATION-v1.1.0.md' ) ? file_get_contents( $root . '/docs/MIGRATION-v1.1.0.md' ) : '';

$stage_keys = array( 'new_inquiry', 'under_review', 'needs_information', 'qualified', 'meeting_requested', 'meeting_scheduled', 'proposal_preparation', 'proposal_sent', 'accepted', 'active_engagement', 'completed', 'declined', 'archived' );
$all_stages_present = true;
foreach ( $stage_keys as $stage ) {
	$all_stages_present = $all_stages_present && false !== strpos( $schema, "'{$stage}'" );
}
$service_routes = array( 'advisory', 'ai-assurance', 'evidence-systems', 'knowledge-architecture', 'technical-storytelling', 'responsible-ai', 'collaboration', 'workshop', 'monthly-advisory' );
$all_routes_present = true;
foreach ( $service_routes as $route ) {
	$all_routes_present = $all_routes_present && false !== strpos( $schema, "'{$route}'" );
}

$checks = array(
	'v1.1.0 plugin and schema identity' => false !== strpos( $main, 'Version:     1.3.1' ) && false !== strpos( $main, "SC_EI_DB_VERSION', '1.3.0'" ) && false !== strpos( $main, "SC_EI_LIFECYCLE_SCHEMA_VERSION', '1.0.0'" ),
	'lifecycle classes and administrator loaded' => false !== strpos( $main, 'class-sc-ei-lifecycle-schema.php' ) && false !== strpos( $main, 'class-sc-ei-lifecycle-repository.php' ) && false !== strpos( $main, 'class-sc-ei-lifecycle-admin.php' ),
	'thirteen governed lifecycle stages' => $all_stages_present && false !== strpos( $schema, 'allowed_transitions()' ) && false !== strpos( $schema, 'can_transition' ),
	'nondestructive legacy status mapping' => false !== strpos( $schema, 'map_legacy_status' ) && false !== strpos( $repository, 'backfill_defaults' ) && false !== strpos( $repository, 'WHERE lifecycle_stage IS NULL' ),
	'three append-only lifecycle support tables' => false !== strpos( $db, "table( 'lifecycle_events' )" ) && false !== strpos( $db, "table( 'lifecycle_notes' )" ) && false !== strpos( $db, "table( 'lifecycle_tasks' )" ) && false !== strpos( $db, '$sql_lifecycle_events' ) && false !== strpos( $db, '$sql_lifecycle_notes' ) && false !== strpos( $db, '$sql_lifecycle_tasks' ),
	'lifecycle fields and optimistic versioning' => false !== strpos( $db, 'lifecycle_stage varchar(60)' ) && false !== strpos( $db, 'qualification_json longtext' ) && false !== strpos( $db, 'sender_lifecycle_summary longtext' ) && false !== strpos( $db, 'lifecycle_version int(10)' ) && false !== strpos( $repository, "array( 'id' => \$inquiry_id, 'lifecycle_version'" ),
	'human-confirmed transitions' => false !== strpos( $admin, "'MOVE ' . strtoupper" ) && false !== strpos( $admin, 'lifecycle_confirmation_failed' ) && false !== strpos( $repository, "'automatic' => false" ) && false === strpos( $repository, 'wp_schedule_single_event' ),
	'transition reasons owners and audit events' => false !== strpos( $repository, 'lifecycle_transition_reason_required' ) && false !== strpos( $repository, 'lifecycle_owner_required' ) && false !== strpos( $repository, 'lifecycle_stage_changed' ) && false !== strpos( $repository, 'SC_EI_Audit_Log::record' ),
	'qualification workspace fields' => false !== strpos( $admin, 'decision_authority' ) && false !== strpos( $admin, 'funding_status' ) && false !== strpos( $admin, 'ai_assurance_applicable' ) && false !== strpos( $admin, 'teams_readiness' ) && false !== strpos( $admin, 'qualification_rationale' ),
	'internal notes isolated from sender portal' => false !== strpos( $repository, 'add_note' ) && false !== strpos( $repository, 'is_sensitive' ) && false === strpos( $portal_view, 'note_body' ) && false === strpos( $portal_view, 'qualification_rationale' ) && false === strpos( $portal_view, 'lifecycle_owner_user_id' ),
	'sender portal exposes deliberate safe snapshot' => false !== strpos( $repository, 'sender_snapshot' ) && false !== strpos( $repository, "'label'" ) && false !== strpos( $repository, "'summary'" ) && false !== strpos( $repository, "'next_step'" ) && false !== strpos( $portal_public, '$lifecycle_snapshot' ) && false !== strpos( $portal_view, 'Engagement stage' ),
	'follow-up tasks and idempotent reminders' => false !== strpos( $repository, 'add_task' ) && false !== strpos( $repository, 'update_task' ) && false !== strpos( $repository, 'process_due_tasks' ) && false !== strpos( $repository, 'last_reminded_at' ) && false !== strpos( $repository, "'daily_when_due'" ) && false !== strpos( $notification, 'sc_ei_lifecycle_task_due' ),
	'task reminder email is opt-in' => false !== strpos( $schema, "'lifecycle_task_email_enabled'" ) && false !== strpos( $schema, "=> 0" ) && false !== strpos( $notification, "empty( \$settings['lifecycle_task_email_enabled'] )" ),
	'Teams proposals and engagement records stay linked' => false !== strpos( $admin, 'meeting_offers_for_inquiry' ) && false !== strpos( $admin, 'proposals_for_inquiry' ) && false !== strpos( $admin, 'SC_EI_Engagement_Repository::for_inquiry' ) && false !== strpos( $view, 'Microsoft Teams' ) && false !== strpos( $view, 'Proposals' ),
	'first-class advisory service routes' => $all_routes_present && false !== strpos( $pilot, 'SC_EI_Lifecycle_Schema::service_routes' ),
	'lifecycle communication templates are reviewable' => false !== strpos( $communication_schema, 'lifecycle_information_request' ) && false !== strpos( $communication_schema, 'lifecycle_meeting_invitation' ) && false !== strpos( $communication_schema, 'lifecycle_proposal_sent' ) && false !== strpos( $communication_schema, 'lifecycle_engagement_accepted' ) && false !== strpos( $communication_schema, 'lifecycle_completed' ),
	'privacy export and approved redaction include lifecycle' => false !== strpos( $repository, 'export_for_inquiry' ) && false !== strpos( $repository, 'redact_for_privacy' ) && false !== strpos( $privacy, 'SC_EI_Lifecycle_Repository::export_for_inquiry' ) && false !== strpos( $retention, 'SC_EI_Lifecycle_Repository::redact_for_privacy' ),
	'capabilities separate viewing notes tasks and transitions' => false !== strpos( $capabilities, "'sc_intake_view_lifecycle'" ) && false !== strpos( $capabilities, "'sc_intake_add_lifecycle_notes'" ) && false !== strpos( $capabilities, "'sc_intake_manage_lifecycle_tasks'" ) && false !== strpos( $capabilities, "'sc_intake_manage_lifecycle'" ),
	'readiness includes schema migration cron and overdue operations' => false !== strpos( $platform, "'lifecycle_columns'" ) && false !== strpos( $platform, "'lifecycle_migration_journal'" ) && false !== strpos( $platform, "'lifecycle_operations'" ) && false !== strpos( $platform, "'lifecycle_reminders'" ) && false !== strpos( $pilot, 'overdue lifecycle task(s)' ),
	'live validation exercises governed lifecycle and cleanup' => false !== strpos( $validation, 'SC_EI_Lifecycle_Repository::transition' ) && false !== strpos( $validation, 'SC_EI_Lifecycle_Repository::sender_snapshot' ) && false !== strpos( $validation, "table( 'lifecycle_events' )" ) && false !== strpos( $validation, "sc-contact-engagement-live-validation/1.6" ),
	'analytics cover stages sources qualification and timing' => false !== strpos( $repository, "'by_stage'" ) && false !== strpos( $repository, "'by_source_last_30_days'" ) && false !== strpos( $repository, "'qualification_rate'" ) && false !== strpos( $repository, "'proposal_rate'" ) && false !== strpos( $repository, "'acceptance_rate'" ) && false !== strpos( $repository, "'average_first_review_hours'" ),
	'uninstall clears lifecycle schedules and evidence options' => false !== strpos( $uninstall, 'sc_ei_lifecycle_task_reminders' ) && false !== strpos( $uninstall, 'sc_ei_lifecycle_schema_version' ) && false !== strpos( $uninstall, 'sc_ei_lifecycle_schema_version_previous' ),
	'no autonomous outbound network or mail from lifecycle repository' => false === strpos( $repository, 'wp_remote_' ) && false === strpos( $repository, 'wp_mail(' ),
	'v1.1.0 lifecycle and migration documentation' => false !== strpos( $docs, 'Advisory Operations and Engagement Lifecycle' ) && false !== strpos( $migration, 'nondestructive' ) && false !== strpos( $migration, '1.1.0' ),
);

$failed = array_keys( array_filter( $checks, static fn( $value ) => ! $value ) );
if ( $failed ) {
	fwrite( STDERR, 'Advisory lifecycle checks failed: ' . implode( ', ', $failed ) . PHP_EOL );
	exit( 1 );
}
foreach ( $checks as $label => $passed ) {
	echo 'PASS: ' . $label . PHP_EOL;
}
echo "Sustainable Catalyst Contact and Engagement Platform v1.1.1 advisory lifecycle compatibility checks passed.\n";
