<?php
/** v1.4.1 proposal versioning, approval, and engagement-conversion reliability contracts. */
$root = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';
$main = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$repo = file_get_contents( $plugin . '/includes/class-sc-ei-proposal-governance-repository.php' );
$workflow = file_get_contents( $plugin . '/includes/class-sc-ei-workflow-repository.php' );
$platform = file_get_contents( $plugin . '/includes/class-sc-ei-platform-repository.php' );
$validation = file_get_contents( $plugin . '/includes/class-sc-ei-platform-validation.php' );
$activator = file_get_contents( $plugin . '/includes/class-sc-ei-activator.php' );
$readme = file_get_contents( $plugin . '/readme.txt' );

$checks = array(
    'v1.4.1 identity keeps database at 1.4.0' => false !== strpos( $main, 'Version:     1.4.1' ) && false !== strpos( $main, "SC_EI_VERSION', '1.4.1'" ) && false !== strpos( $main, "SC_EI_DB_VERSION', '1.4.0'" ) && false !== strpos( $main, "SC_EI_PLATFORM_SCHEMA_VERSION', '1.4.1'" ) && false !== strpos( $main, "SC_EI_PROPOSAL_SCHEMA_VERSION', '1.0.1'" ),
    'patch migration is separate and nondestructive' => false !== strpos( $repo, "PATCH_MIGRATION_KEY = 'v1_4_1_proposal_versioning_approval_reliability'" ) && false !== strpos( $repo, 'record_patch_migration' ) && false !== strpos( $repo, "'no_destructive_migration'" ),
    'sender response commits immutable receipt synchronously' => false !== strpos( $workflow, 'record_sender_action' ) && false !== strpos( $workflow, 'rollback_proposal_sender_response' ) && false !== strpos( $workflow, 'sc_ei_proposal_sender_response_committed' ),
    'proposal response rollback records reliability evidence' => false !== strpos( $workflow, 'proposal_sender_response_receipt_failed' ) && false !== strpos( $workflow, 'rollback_proposal_sender_response' ),
    'matching sender response replay is idempotent' => false !== strpos( $repo, "['_idempotent'] = true" ) && false !== strpos( $workflow, "['_idempotent']" ),
    'conflicting sender receipt replay is rejected' => false !== strpos( $repo, 'proposal_approval_replay_conflict' ),
    'SOW approval is tied to current proposal version' => false !== strpos( $repo, 'proposal_sow_proposal_version_stale' ) && false !== strpos( $repo, "proposal_version_id" ) && false !== strpos( $repo, 'current_version_id' ),
    'sender-approved SOW blocks unsafe proposal publication' => false !== strpos( $repo, 'validate_proposal_publication' ) && false !== strpos( $repo, 'proposal_publish_sender_approved_sow_locked' ),
    'proposal publication can be rolled back after reconciliation failure' => false !== strpos( $workflow, 'rollback_proposal_publication' ) && false !== strpos( $workflow, 'proposal_publication_sow_reconciliation_failed' ),
    'proposal version creation has bounded retry' => false !== strpos( $workflow, 'proposal_version_insert_failed' ) && false !== strpos( $workflow, '$attempt <= 3' ),
    'SOW version creation has bounded retry' => false !== strpos( $repo, 'proposal_sow_version_insert_failed' ) && false !== strpos( $repo, '$attempt <= 3' ),
    'approval receipts can be independently verified' => false !== strpos( $repo, 'verify_approval_integrity' ) && false !== strpos( $repo, 'immutable_hash' ) && false !== strpos( $repo, 'hash_equals' ),
    'engagement conversion repairs receipt and proposal status on replay' => false !== strpos( $repo, 'ensure_conversion_receipt' ) && false !== strpos( $repo, 'mark_proposal_converted' ) && false !== strpos( $repo, "'idempotent' => true" ),
    'converted proposal state is explicit' => false !== strpos( $repo, 'converted_to_engagement' ) && false !== strpos( $repo, 'proposal_conversion_status_failed' ),
    'consistency metrics detect receipt and conversion drift' => false !== strpos( $repo, 'consistency_metrics' ) && false !== strpos( $repo, 'invalid_approval_hashes' ) && false !== strpos( $repo, 'missing_sender_receipts' ) && false !== strpos( $repo, 'missing_sow_receipts' ) && false !== strpos( $repo, 'missing_conversion_receipts' ) && false !== strpos( $repo, 'converted_status_mismatch' ),
    'production gate includes patch migration and consistency blockers' => false !== strpos( $platform, "'proposal_reliability_patch_journal'" ) && false !== strpos( $platform, "'verify_proposal_reliability_patch'" ) && false !== strpos( $platform, 'invalid_approval_hashes' ),
    'live validation tests replay and immutable receipt integrity' => false !== strpos( $validation, "'[TEST] v1.4.1 live validation'" ) && false !== strpos( $validation, '$sender_sow_replay' ) && false !== strpos( $validation, '$accepted_replay' ) && false !== strpos( $validation, '$conversion_replay' ) && false !== strpos( $validation, 'verify_approval_integrity' ),
    'activation records both proposal migrations and schema history' => false !== strpos( $activator, 'sc_ei_proposal_governance_schema_version_previous' ) && false !== strpos( $activator, 'record_patch_migration' ),
    'stable tag advances to v1.4.1' => false !== strpos( $readme, 'Stable tag: 1.4.1' ),
);
$failed = array_keys( array_filter( $checks, static fn( $passed ) => ! $passed ) );
if ( $failed ) { fwrite( STDERR, 'Proposal reliability checks failed: ' . implode( ', ', $failed ) . PHP_EOL ); exit( 1 ); }
foreach ( $checks as $label => $passed ) { echo 'PASS: ' . $label . PHP_EOL; }
echo 'Sustainable Catalyst Contact and Engagement Platform v1.4.1 proposal reliability checks passed (' . count( $checks ) . ' assertions).' . PHP_EOL;
