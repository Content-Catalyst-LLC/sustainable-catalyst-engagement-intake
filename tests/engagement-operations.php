<?php
$plugin = dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake';
$main = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$db = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$schema = file_get_contents( $plugin . '/includes/class-sc-ei-engagement-schema.php' );
$repo = file_get_contents( $plugin . '/includes/class-sc-ei-engagement-repository.php' );
$admin = file_get_contents( $plugin . '/includes/class-sc-ei-engagement-admin.php' );
$view = file_get_contents( $plugin . '/admin/views/engagement-handoff.php' );
$portal_schema = file_get_contents( $plugin . '/includes/class-sc-ei-portal-schema.php' );
$portal_public = file_get_contents( $plugin . '/includes/class-sc-ei-portal-public.php' );
$portal_view = file_get_contents( $plugin . '/public/views/sender-portal.php' );
$privacy = file_get_contents( $plugin . '/includes/class-sc-ei-privacy.php' );
$inventory = file_get_contents( $plugin . '/includes/class-sc-ei-privacy-repository.php' );
$retention = file_get_contents( $plugin . '/includes/class-sc-ei-retention-engine.php' );
$rest = file_get_contents( $plugin . '/includes/class-sc-ei-rest.php' );
$review = file_get_contents( $plugin . '/includes/class-sc-ei-review-repository.php' );
$diagnostics = file_get_contents( $plugin . '/includes/class-sc-ei-diagnostics.php' );
$caps = file_get_contents( $plugin . '/includes/class-sc-ei-capabilities.php' );
$workflow_view = file_get_contents( $plugin . '/admin/views/teams-proposals.php' );

$checks = array(
	'v1.1.1 release markers' => strpos( $main, 'Version:     1.5.0' ) !== false
		&& strpos( $main, "SC_EI_DB_VERSION', '1.5.0'" ) !== false
		&& strpos( $main, "SC_EI_PORTAL_SCHEMA_VERSION', '1.7.0'" ) !== false
		&& strpos( $main, "SC_EI_ENGAGEMENT_SCHEMA_VERSION', '1.2.0'" ) !== false,
	'four engagement tables declared' => strpos( $db, '$sql_engagements' ) !== false
		&& strpos( $db, '$sql_engagement_snapshots' ) !== false
		&& strpos( $db, '$sql_engagement_requirements' ) !== false
		&& strpos( $db, '$sql_engagement_events' ) !== false,
	'four engagement tables installed' => strpos( $db, 'dbDelta( $sql_engagements )' ) !== false
		&& strpos( $db, 'dbDelta( $sql_engagement_snapshots )' ) !== false
		&& strpos( $db, 'dbDelta( $sql_engagement_requirements )' ) !== false
		&& strpos( $db, 'dbDelta( $sql_engagement_events )' ) !== false,
	'one engagement per proposal' => strpos( $db, 'UNIQUE KEY proposal_id (proposal_id)' ) !== false
		&& strpos( $repo, 'find_by_proposal( $proposal_id )' ) !== false
		&& strpos( $repo, 'engagement_duplicate_proposal' ) !== false,
	'contracted proposal required' => strpos( $repo, "'contracted' !== \$proposal['status']" ) !== false
		&& strpos( $repo, 'engagement_contract_reference_missing' ) !== false,
	'atomic handoff transaction' => strpos( $repo, "START TRANSACTION" ) !== false
		&& strpos( $repo, "ROLLBACK" ) !== false
		&& strpos( $repo, "COMMIT" ) !== false
		&& strpos( $repo, 'The contracted proposal remains unchanged.' ) !== false,
	'immutable commercial snapshot' => strpos( $repo, "hash( 'sha256', \$snapshot_json )" ) !== false
		&& strpos( $repo, "'snapshot_type'           => 'contracted_proposal_handoff'" ) !== false
		&& strpos( $repo, "'proposal_content_hash'" ) !== false
		&& strpos( $db, 'UNIQUE KEY engagement_snapshot' ) !== false,
	'snapshot integrity verification' => strpos( $repo, 'public static function verify_snapshot' ) !== false
		&& strpos( $repo, 'hash_equals' ) !== false,
	'privacy tombstone remains internally verifiable' => strpos( $repo, '$tombstone_hash = hash' ) !== false
		&& strpos( $repo, 'SET payload_json = %s, content_hash = %s' ) !== false,
	'readiness is comprehensive' => strpos( $repo, "'contract_reference'" ) !== false
		&& strpos( $repo, "'owner_assigned'" ) !== false
		&& strpos( $repo, "'snapshot_integrity'" ) !== false
		&& strpos( $repo, "'required_items'" ) !== false
		&& strpos( $repo, "'proposal_contracted'" ) !== false
		&& strpos( $repo, "'privacy_state'" ) !== false,
	'handoff and activation are separate' => strpos( $repo, "'status'                          => 'handoff_pending'" ) !== false
		&& strpos( $repo, "'ready_for_setup'" ) !== false
		&& strpos( $repo, 'public static function activate' ) !== false,
	'typed human controls' => strpos( $admin, "'HANDOFF ' . strtoupper" ) !== false
		&& strpos( $admin, "'READY ' . strtoupper" ) !== false
		&& strpos( $admin, "'ACTIVATE ' . strtoupper" ) !== false
		&& strpos( $admin, "'PAUSE '" ) !== false
		&& strpos( $admin, "'COMPLETE '" ) !== false,
	'activation reruns readiness' => strpos( $repo, 'engagement_readiness_changed' ) !== false
		&& substr_count( $repo, 'self::readiness( $engagement_id )' ) >= 2,
	'fixed no-automation boundary' => strpos( $schema, "'engagement_no_auto_activation'" ) !== false
		&& strpos( $schema, "'engagement_no_auto_provisioning'" ) !== false
		&& strpos( $schema, "'engagement_no_auto_invoice'" ) !== false
		&& strpos( $schema, "'engagement_no_auto_payment'" ) !== false
		&& strpos( $schema, "'engagement_no_auto_signature'" ) !== false,
	'integration handoff does not provision' => strpos( $repo, "'provisioned'   => false" ) !== false
		&& strpos( $repo, "'automatic'     => false" ) !== false
		&& strpos( $repo, "'workbench'" ) !== false
		&& strpos( $repo, "'decision_studio'" ) !== false,
	'sender portal permission and data' => strpos( $portal_schema, "'view_engagements'" ) !== false
		&& strpos( $portal_public, 'SC_EI_Engagement_Repository::for_inquiry' ) !== false
		&& strpos( $portal_public, 'SC_EI_Engagement_Repository::requirements' ) !== false,
	'sender portal states contract boundary' => strpos( $portal_view, 'The separately executed agreement remains the binding commercial record.' ) !== false
		&& strpos( $portal_view, 'No invoice, payment, signature, or external project is created by this portal.' ) !== false,
	'sender portal excludes internal notes' => strpos( function_exists( 'substr' ) ? substr( $portal_view, strpos( $portal_view, "'engagement' === \$view" ), strpos( $portal_view, "'preferences' === \$view" ) - strpos( $portal_view, "'engagement' === \$view" ) ) : '', 'internal_notes' ) === false,
	'private export and review context' => strpos( $rest, "['engagement_handoff']" ) !== false
		&& strpos( $review, "'engagement_handoff'" ) !== false
		&& strpos( $repo, 'sc-engagement-handoff-package/1.0' ) !== false,
	'privacy inventory export and erasure' => strpos( $inventory, "'engagements'" ) !== false
		&& strpos( $privacy, 'Engagement Intake Engagement Handoffs' ) !== false
		&& strpos( $retention, 'SC_EI_Engagement_Repository::redact_for_privacy' ) !== false,
	'granular capabilities' => strpos( $caps, 'sc_intake_view_engagements' ) !== false
		&& strpos( $caps, 'sc_intake_create_engagement_handoffs' ) !== false
		&& strpos( $caps, 'sc_intake_activate_engagements' ) !== false
		&& strpos( $caps, 'sc_intake_export_engagements' ) !== false,
	'reviewer is view-only' => strpos( substr( $caps, strpos( $caps, 'private const REVIEWER' ), strpos( $caps, 'private const MANAGER' ) - strpos( $caps, 'private const REVIEWER' ) ), 'sc_intake_view_engagements' ) !== false
		&& strpos( substr( $caps, strpos( $caps, 'private const REVIEWER' ), strpos( $caps, 'private const MANAGER' ) - strpos( $caps, 'private const REVIEWER' ) ), 'sc_intake_activate_engagements' ) === false,
	'proposal workspace links handoff' => strpos( $workflow_view, 'Prepare Engagement Handoff' ) !== false
		&& strpos( $workflow_view, 'SC_EI_Engagement_Repository::find_by_proposal' ) !== false,
	'diagnostics enforce handoff boundary' => strpos( $diagnostics, "'unique_proposal_handoff'    => true" ) !== false
		&& strpos( $diagnostics, "'human_activation_required'  => true" ) !== false
		&& strpos( $diagnostics, "'automatic_provisioning'     => false" ) !== false,
	'no direct mail or external API in engagement repository' => strpos( $repo, 'wp_mail(' ) === false
		&& strpos( $repo, 'wp_remote_' ) === false
		&& strpos( $repo, 'graph.microsoft.com' ) === false,
	'admin workspace exposes integrity and gates' => strpos( $view, 'Commercial Handoff Snapshot' ) !== false
		&& strpos( $view, 'Readiness Gate' ) !== false
		&& strpos( $view, 'Activate Engagement' ) !== false,
);

$failed = array_keys( array_filter( $checks, static fn( bool $passed ): bool => ! $passed ) );
if ( $failed ) {
	fwrite( STDERR, 'Engagement operation checks failed: ' . implode( ', ', $failed ) . PHP_EOL );
	exit( 1 );
}
foreach ( $checks as $label => $passed ) echo 'PASS: ' . $label . PHP_EOL;
echo "Engagement Intake v1.0.0 proposal and engagement handoff checks passed.\n";
