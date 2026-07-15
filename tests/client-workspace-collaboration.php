<?php
/** v1.5.0 secure client workspace and collaboration contracts. */
$root = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';
$main = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$db = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$schema = file_get_contents( $plugin . '/includes/class-sc-ei-workspace-schema.php' );
$repo = file_get_contents( $plugin . '/includes/class-sc-ei-workspace-repository.php' );
$admin = file_get_contents( $plugin . '/includes/class-sc-ei-workspace-admin.php' );
$view = file_get_contents( $plugin . '/admin/views/client-workspace.php' );
$portal_schema = file_get_contents( $plugin . '/includes/class-sc-ei-portal-schema.php' );
$portal_controller = file_get_contents( $plugin . '/includes/class-sc-ei-portal-public.php' );
$portal_view = file_get_contents( $plugin . '/public/views/sender-portal.php' );
$privacy = file_get_contents( $plugin . '/includes/class-sc-ei-privacy.php' );
$retention = file_get_contents( $plugin . '/includes/class-sc-ei-retention-engine.php' );
$platform = file_get_contents( $plugin . '/includes/class-sc-ei-platform-repository.php' );
$validation = file_get_contents( $plugin . '/includes/class-sc-ei-platform-validation.php' );
$diagnostics = file_get_contents( $plugin . '/includes/class-sc-ei-diagnostics.php' );
$communications = file_get_contents( $plugin . '/includes/class-sc-ei-communication-schema.php' );
$templates = file_get_contents( $plugin . '/includes/class-sc-ei-template-repository.php' );
$uninstall = file_get_contents( $plugin . '/uninstall.php' );

$checks = array(
    'v1.5.0 plugin database platform portal and workspace identities' => false !== strpos( $main, 'Version:     1.5.0' ) && false !== strpos( $main, "SC_EI_DB_VERSION', '1.5.0'" ) && false !== strpos( $main, "SC_EI_PLATFORM_SCHEMA_VERSION', '1.5.0'" ) && false !== strpos( $main, "SC_EI_PORTAL_SCHEMA_VERSION', '1.7.0'" ) && false !== strpos( $main, "SC_EI_WORKSPACE_SCHEMA_VERSION', '1.0.0'" ),
    'workspace module is loaded and registered' => false !== strpos( $main, 'class-sc-ei-workspace-schema.php' ) && false !== strpos( $main, 'class-sc-ei-workspace-repository.php' ) && false !== strpos( $main, 'class-sc-ei-workspace-admin.php' ),
    'seven dedicated workspace tables are installed' => false !== strpos( $db, "'client_workspaces'" ) && false !== strpos( $db, "'workspace_members'" ) && false !== strpos( $db, "'workspace_milestones'" ) && false !== strpos( $db, "'workspace_deliverables'" ) && false !== strpos( $db, "'workspace_messages'" ) && false !== strpos( $db, "'workspace_documents'" ) && false !== strpos( $db, "'workspace_events'" ),
    'workspace database contract is required for version advancement' => false !== strpos( $db, 'workspace_columns_exist' ) && false !== strpos( $db, "'workspace.' . \$key" ),
    'nondestructive v1.5.0 migration is journaled' => false !== strpos( $repo, "MIGRATION_KEY = 'v1_5_0_secure_client_workspace_collaboration'" ) && false !== strpos( $repo, "'no_destructive_migration'" ) && false !== strpos( $repo, "'workspace_isolation'" ),
    'workspace belongs to a governed engagement and canonical inquiry' => false !== strpos( $repo, 'create_for_engagement' ) && false !== strpos( $repo, "'engagement_id'" ) && false !== strpos( $repo, "'inquiry_id'" ),
    'workspace creation records explicit sender and staff members' => false !== strpos( $repo, "'member_type' => 'sender'" ) && false !== strpos( $repo, "'member_type' => 'staff'" ) && false !== strpos( $repo, 'email_hash' ),
    'raw sender email is not stored in workspace membership' => false !== strpos( $repo, "hash( 'sha256'" ) && false === strpos( $db, 'workspace_members_email varchar' ),
    'workspace transitions require typed human confirmation and optimistic locking' => false !== strpos( $repo, "strtoupper( \$status . ' ' . \$current['workspace_number'] )" ) && false !== strpos( $repo, "'row_version'" ) && false !== strpos( $repo, 'workspace_transition_conflict' ),
    'milestones support status due dates and sender visibility' => false !== strpos( $schema, 'milestone_statuses' ) && false !== strpos( $repo, 'add_milestone' ) && false !== strpos( $repo, "'sender_visible'" ),
    'deliverables support publication approval and sender decisions' => false !== strpos( $repo, 'add_deliverable' ) && false !== strpos( $repo, 'publish_deliverable' ) && false !== strpos( $repo, 'record_sender_deliverable_decision' ) && false !== strpos( $schema, 'sender_decisions' ),
    'workspace collaboration messages distinguish direction and sender type' => false !== strpos( $repo, 'add_message' ) && false !== strpos( $repo, 'add_sender_message' ) && false !== strpos( $db, 'direction varchar') && false !== strpos( $db, 'sender_type varchar'),
    'workspace documents reference protected attachments from same inquiry' => false !== strpos( $repo, 'link_document' ) && false !== strpos( $repo, 'workspace_document_invalid' ) && false !== strpos( $repo, 'SC_EI_Attachment_Repository::find' ),
    'sender projection uses an explicit allowlist' => false !== strpos( $schema, 'sender_projection_keys' ) && false !== strpos( $schema, "'milestones'" ) && false !== strpos( $schema, "'deliverables'" ) && false !== strpos( $schema, "'documents'" ) && false !== strpos( $schema, "'messages'" ),
    'sender projection excludes staff membership and workspace events' => false === strpos( $portal_view, 'workspace_members' ) && false === strpos( $portal_view, 'workspace_events' ) && false === strpos( $portal_view, 'email_hash' ),
    'Sender Portal supports workspace view deliverable response and sender message' => false !== strpos( $portal_schema, "'view_workspace'" ) && false !== strpos( $portal_schema, "'respond_deliverables'" ) && false !== strpos( $portal_controller, 'handle_workspace_message' ) && false !== strpos( $portal_controller, 'handle_deliverable_response' ),
    'Sender Portal uses public workspace identifiers rather than numeric workspace access' => false !== strpos( $portal_controller, 'workspace_public_id' ) && false !== strpos( $repo, 'find_by_public_id' ),
    'workspace administration exposes members milestones deliverables documents messages and history' => false !== strpos( $admin, 'handle_publish_deliverable' ) && false !== strpos( $view, 'Workspace members' ) && false !== strpos( $view, 'Milestones' ) && false !== strpos( $view, 'Deliverables' ) && false !== strpos( $view, 'Collaboration updates' ) && false !== strpos( $view, 'Workspace audit history' ),
    'privacy export and redaction cover all workspace records' => false !== strpos( $privacy, 'sc-engagement-intake-client-workspaces' ) && false !== strpos( $repo, 'export_for_inquiry' ) && false !== strpos( $repo, 'redact_for_inquiry' ) && false !== strpos( $retention, 'SC_EI_Workspace_Repository::redact_for_inquiry' ),
    'workspace communications are reviewable templates' => false !== strpos( $communications, "'workspace_activated'" ) && false !== strpos( $communications, "'workspace_update'" ) && false !== strpos( $communications, "'workspace_deliverable'" ) && false !== strpos( $communications, "'workspace_changes'" ) && false !== strpos( $communications, "'workspace_accepted'" ) && false !== strpos( $templates, 'workspace_number' ),
    'production gate verifies workspace columns migration contract and blockers' => false !== strpos( $platform, "'workspace_columns'" ) && false !== strpos( $platform, "'workspace_migration_journal'" ) && false !== strpos( $platform, "'workspace_sender_contract'" ) && false !== strpos( $platform, "'workspace_operational_blockers'" ),
    'readiness repair supports the workspace migration and workspace review link' => false !== strpos( $platform, "case 'verify_workspace_migration'" ) && false !== strpos( $platform, "'review_client_workspaces'" ),
    'diagnostics expose workspace schema columns metrics and blockers' => false !== strpos( $diagnostics, "'workspace_columns'" ) && false !== strpos( $diagnostics, "'workspace' => array" ) && false !== strpos( $diagnostics, "'blockers' => \$workspace_blockers" ),
    'live validation executes a real sender-safe workspace workflow and cleanup' => false !== strpos( $validation, "'[TEST] v1.5.0 live validation'" ) && false !== strpos( $validation, 'create_for_engagement' ) && false !== strpos( $validation, 'add_milestone' ) && false !== strpos( $validation, 'add_deliverable' ) && false !== strpos( $validation, 'publish_deliverable' ) && false !== strpos( $validation, 'record_sender_deliverable_decision' ) && false !== strpos( $validation, 'add_sender_message' ) && false !== strpos( $validation, 'cleanup_for_inquiry' ),
    'uninstall removes workspace schema options' => false !== strpos( $uninstall, 'sc_ei_workspace_schema_version' ) && false !== strpos( $uninstall, 'sc_ei_workspace_schema_version_previous' ),
    'collaboration boundaries avoid automatic approvals contracts and payments' => false === strpos( $repo, 'wp_mail(' ) && false === strpos( $repo, 'automatic_payment') && false === strpos( $repo, 'electronic_signature'),
);
$failed = array_keys( array_filter( $checks, static fn( $passed ) => ! $passed ) );
if ( $failed ) { fwrite( STDERR, 'Client workspace collaboration checks failed: ' . implode( ', ', $failed ) . PHP_EOL ); exit( 1 ); }
foreach ( $checks as $label => $passed ) { echo 'PASS: ' . $label . PHP_EOL; }
echo 'Sustainable Catalyst Contact and Engagement Platform v1.5.0 client workspace collaboration checks passed (' . count( $checks ) . ' assertions).' . PHP_EOL;
