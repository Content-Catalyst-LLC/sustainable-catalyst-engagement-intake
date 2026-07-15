<?php
/** v1.4.1 proposal, Statement of Work, approval, and change-control contracts. */
$root = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';
$main = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$db = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$schema = file_get_contents( $plugin . '/includes/class-sc-ei-proposal-governance-schema.php' );
$repo = file_get_contents( $plugin . '/includes/class-sc-ei-proposal-governance-repository.php' );
$admin = file_get_contents( $plugin . '/includes/class-sc-ei-proposal-governance-admin.php' );
$view = file_get_contents( $plugin . '/admin/views/proposal-governance.php' );
$workflow_schema = file_get_contents( $plugin . '/includes/class-sc-ei-workflow-schema.php' );
$workflow = file_get_contents( $plugin . '/includes/class-sc-ei-workflow-repository.php' );
$portal_controller = file_get_contents( $plugin . '/includes/class-sc-ei-portal-public.php' );
$portal_view = file_get_contents( $plugin . '/public/views/sender-portal.php' );
$privacy = file_get_contents( $plugin . '/includes/class-sc-ei-privacy.php' );
$privacy_repo = file_get_contents( $plugin . '/includes/class-sc-ei-privacy-repository.php' );
$rest = file_get_contents( $plugin . '/includes/class-sc-ei-rest.php' );
$platform = file_get_contents( $plugin . '/includes/class-sc-ei-platform-repository.php' );
$validation = file_get_contents( $plugin . '/includes/class-sc-ei-platform-validation.php' );
$communications = file_get_contents( $plugin . '/includes/class-sc-ei-communication-schema.php' );
$uninstall = file_get_contents( $plugin . '/uninstall.php' );
$public_js = file_get_contents( $plugin . '/assets/js/public.js' );

$checks = array(
    'v1.4.1 plugin database platform and proposal identities' => false !== strpos( $main, 'Version:     1.6.0' ) && false !== strpos( $main, "SC_EI_DB_VERSION', '1.6.0'" ) && false !== strpos( $main, "SC_EI_PLATFORM_SCHEMA_VERSION', '1.6.0'" ) && false !== strpos( $main, "SC_EI_PROPOSAL_SCHEMA_VERSION', '1.0.1'" ),
    'proposal governance module loaded and registered' => false !== strpos( $main, 'class-sc-ei-proposal-governance-schema.php' ) && false !== strpos( $main, 'class-sc-ei-proposal-governance-repository.php' ) && false !== strpos( $main, 'class-sc-ei-proposal-governance-admin.php' ),
    'dedicated proposal governance tables are installed' => false !== strpos( $db, "'proposal_approvals'" ) && false !== strpos( $db, "'statements_of_work'" ) && false !== strpos( $db, "'statement_of_work_versions'" ) && false !== strpos( $db, "'change_requests'" ),
    'database contract verifies proposal governance columns' => false !== strpos( $db, 'proposal_governance_columns_exist' ) && false !== strpos( $db, 'immutable_hash char(64)' ) && false !== strpos( $db, 'content_hash char(64)' ),
    'nondestructive v1.4.0 migration is journaled' => false !== strpos( $repo, "MIGRATION_KEY = 'v1_4_0_proposals_statements_of_work_engagement_approvals'" ) && false !== strpos( $repo, "'no_destructive_migration'" ),
    'SOW versions are preserved rather than overwritten' => false !== strpos( $repo, 'add_sow_version' ) && false !== strpos( $repo, 'statement_of_work_versions' ) && false !== strpos( $repo, 'version_number' ) && false !== strpos( $repo, 'content_hash' ),
    'sender-approved SOW cannot be silently revised' => false !== strpos( $repo, "'sender_approved'" ) && false !== strpos( $repo, 'proposal_sow_not_editable' ),
    'immutable proposal and SOW actions retain integrity hashes' => false !== strpos( $repo, 'immutable_hash' ) && false !== strpos( $repo, 'record_sender_action' ) && false !== strpos( $schema, "APPROVAL_SCHEMA = 'sc-proposal-approval/1.0'" ),
    'current approved proposal version is required for sender action' => false !== strpos( $workflow, 'current_version_id' ) && false !== strpos( $repo, 'proposal_approval_version_stale' ),
    'sender proposal responses include receipt changes accept and decline' => false !== strpos( $workflow_schema, "'confirm_receipt'" ) && false !== strpos( $workflow_schema, "'request_changes'" ) && false !== strpos( $workflow_schema, "'accept'" ) && false !== strpos( $workflow_schema, "'decline'" ),
    'public JavaScript requires deliberate proposal confirmations' => false !== strpos( $public_js, 'REQUEST CHANGES' ) && false !== strpos( $public_js, 'ACCEPT' ) && false !== strpos( $public_js, 'DECLINE' ),
    'sender portal supports approved SOW review and action' => false !== strpos( $portal_controller, 'handle_sow_approval' ) && false !== strpos( $portal_controller, 'sender_snapshot' ) && false !== strpos( $portal_view, 'Statement of Work' ) && false !== strpos( $portal_view, 'Approve Statement of Work' ),
    'sender projection is allowlisted and excludes integrity internals' => false !== strpos( $schema, 'sender_projection_keys' ) && false === strpos( $portal_view, 'immutable_hash' ) && false === strpos( $portal_view, 'internal_notes' ),
    'engagement conversion requires contracted proposal and sender-approved SOW' => false !== strpos( $repo, "'contracted' !== (string) \$proposal['status']" ) && false !== strpos( $repo, "'sender_approved' !== (string) \$sow['status']" ) && false !== strpos( $repo, "'CONVERT '" ),
    'engagement conversion is idempotent' => false !== strpos( $repo, "'idempotent' => true" ) && false !== strpos( $repo, 'create_from_contracted_proposal' ),
    'admin workspace exposes typed SOW approval and engagement conversion' => false !== strpos( $admin, 'handle_approve_sow' ) && false !== strpos( $admin, 'handle_convert_engagement' ) && false !== strpos( $view, 'Convert to governed engagement' ) && false !== strpos( $view, 'CONVERT' ),
    'change requests preserve governed lifecycle and impact fields' => false !== strpos( $schema, "CHANGE_SCHEMA = 'sc-engagement-change-request/1.0'" ) && false !== strpos( $repo, 'create_change_request' ) && false !== strpos( $repo, 'transition_change_request' ) && false !== strpos( $repo, 'scope_impact' ) && false !== strpos( $repo, 'timeline_impact' ) && false !== strpos( $repo, 'fee_impact' ),
    'privacy export and inventory include governance records' => false !== strpos( $privacy, 'sc-engagement-intake-statements-of-work' ) && false !== strpos( $privacy, 'sc-engagement-intake-change-requests' ) && false !== strpos( $privacy, 'sc-engagement-intake-proposal-approvals' ) && false !== strpos( $privacy_repo, "'proposal_approvals'" ),
    'REST export requires proposal governance capability' => false !== strpos( $rest, 'sc_intake_view_proposal_governance' ) && false !== strpos( $rest, 'export_for_inquiry' ),
    'production gate checks proposal columns migration approvals and operations' => false !== strpos( $platform, "'proposal_governance_columns'" ) && false !== strpos( $platform, "'proposal_governance_migration_journal'" ) && false !== strpos( $platform, "'proposal_approval_contract'" ) && false !== strpos( $platform, "'proposal_governance_operations'" ),
    'live validation exercises proposal SOW approval conversion and change request cleanup' => false !== strpos( $validation, "'[TEST] v1.6.0 live validation'" ) && false !== strpos( $validation, 'create_sow_from_proposal' ) && false !== strpos( $validation, 'record_sender_action' ) && false !== strpos( $validation, 'convert_to_engagement' ) && false !== strpos( $validation, 'create_change_request' ) && false !== strpos( $validation, "table( 'proposal_approvals' )" ),
    'proposal communications are reviewable templates' => false !== strpos( $communications, "'proposal_ready'" ) && false !== strpos( $communications, "'proposal_changes'" ) && false !== strpos( $communications, "'proposal_revised'" ) && false !== strpos( $communications, "'proposal_accepted'" ) && false !== strpos( $communications, "'sow_ready'" ) && false !== strpos( $communications, "'engagement_activated'" ),
    'uninstall removes proposal governance schema options' => false !== strpos( $uninstall, 'sc_ei_proposal_governance_schema_version' ) && false !== strpos( $uninstall, 'sc_ei_proposal_governance_schema_version_previous' ),
    'no automatic contract signature payment or activation boundary' => false === strpos( $repo, 'wp_mail(' ) && false === strpos( $repo, 'electronic_signature' ) && false !== strpos( $repo, "'automatic_payment' => false" ) && false !== strpos( $repo, 'record_sender_action' ),
);
$failed = array_keys( array_filter( $checks, static fn( $passed ) => ! $passed ) );
if ( $failed ) { fwrite( STDERR, 'Proposal governance checks failed: ' . implode( ', ', $failed ) . PHP_EOL ); exit( 1 ); }
foreach ( $checks as $label => $passed ) { echo 'PASS: ' . $label . PHP_EOL; }
echo 'Sustainable Catalyst Contact and Engagement Platform v1.4.1 proposal governance checks passed (' . count( $checks ) . ' assertions).' . PHP_EOL;
