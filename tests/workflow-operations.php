<?php
$root = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';

$main       = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$db         = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$schema     = file_get_contents( $plugin . '/includes/class-sc-ei-workflow-schema.php' );
$portal_schema = file_get_contents( $plugin . '/includes/class-sc-ei-portal-schema.php' );
$repo       = file_get_contents( $plugin . '/includes/class-sc-ei-workflow-repository.php' );
$admin      = file_get_contents( $plugin . '/includes/class-sc-ei-workflow-admin.php' );
$portal     = file_get_contents( $plugin . '/includes/class-sc-ei-portal-public.php' );
$portal_ui  = file_get_contents( $plugin . '/public/views/sender-portal.php' );
$print      = file_get_contents( $plugin . '/public/views/proposal-print.php' );
$privacy    = file_get_contents( $plugin . '/includes/class-sc-ei-privacy.php' );
$inventory  = file_get_contents( $plugin . '/includes/class-sc-ei-privacy-repository.php' );
$retention  = file_get_contents( $plugin . '/includes/class-sc-ei-retention-engine.php' );
$diagnostic = file_get_contents( $plugin . '/includes/class-sc-ei-diagnostics.php' );
$caps       = file_get_contents( $plugin . '/includes/class-sc-ei-capabilities.php' );
$admin_view = file_get_contents( $plugin . '/admin/views/teams-proposals.php' );

$checks = array(
	'v1.1.1 release markers' => strpos( $main, 'Version:     1.1.1' ) !== false
		&& strpos( $main, "SC_EI_DB_VERSION', '1.1.0'" ) !== false
		&& strpos( $main, "SC_EI_PORTAL_SCHEMA_VERSION', '1.4.0'" ) !== false
		&& strpos( $main, "SC_EI_WORKFLOW_SCHEMA_VERSION', '1.1.0'" ) !== false
		&& strpos( $main, "SC_EI_GRAPH_SCHEMA_VERSION', '1.0.0'" ) !== false,
	'four workflow tables declared' => strpos( $db, '$sql_meeting_offers' ) !== false
		&& strpos( $db, '$sql_proposals' ) !== false
		&& strpos( $db, '$sql_proposal_versions' ) !== false
		&& strpos( $db, '$sql_workflow_events' ) !== false,
	'four workflow tables installed' => strpos( $db, 'dbDelta( $sql_meeting_offers )' ) !== false
		&& strpos( $db, 'dbDelta( $sql_proposals )' ) !== false
		&& strpos( $db, 'dbDelta( $sql_proposal_versions )' ) !== false
		&& strpos( $db, 'dbDelta( $sql_workflow_events )' ) !== false,
	'published and pending proposal versions separated' => strpos( $db, 'current_version_id bigint' ) !== false
		&& strpos( $db, 'pending_version_id bigint' ) !== false
		&& strpos( $repo, 'COALESCE(p.pending_version_id, p.current_version_id)' ) !== false
		&& strpos( $repo, "\$version_join = \$portal_visible" ) !== false,
	'proposal versions are immutable and hashed' => strpos( $repo, 'version_number' ) !== false
		&& strpos( $repo, "'content_hash'       => hash( 'sha256'" ) !== false
		&& strpos( $repo, 'create_proposal_version' ) !== false,
	'published proposal remains visible during revision' => strpos( $repo, "'pending_version_id' => absint( \$version['id'] )" ) !== false
		&& strpos( $repo, "'current_version_id' => absint( \$proposal['pending_version_id'] )" ) !== false
		&& strpos( $admin_view, 'A published proposal remains visible to the sender until the new version is deliberately published.' ) !== false,
	'meeting offer requires validated slots' => strpos( $repo, 'sanitize_slots' ) !== false
		&& strpos( $repo, 'workflow_meeting_slots_required' ) !== false,
	'Teams URL validation' => strpos( $repo, 'SC_EI_Teams::is_teams_url' ) !== false
		&& strpos( $repo, 'workflow_teams_url_invalid' ) !== false,
	'human-triggered Graph and manual fallback' => strpos( $schema, "'workflow_no_auto_calendar'         => 1" ) !== false
		&& strpos( $diagnostic, "'graph_human_triggered'   => true" ) !== false
		&& strpos( $diagnostic, "'graph_manual_fallback'   => true" ) !== false
		&& strpos( $diagnostic, "'automatic_calendar'      => false" ) !== false,
	'meeting sender response uses optimistic status lock' => strpos( $repo, "'status'      => 'offered'" ) !== false
		&& strpos( $repo, 'workflow_meeting_conflict' ) !== false,
	'meeting finalization is human controlled' => strpos( $admin, "'SCHEDULE ' . strtoupper" ) !== false
		&& strpos( $repo, 'finalize_meeting' ) !== false
		&& strpos( $repo, "'automatic_calendar' => false" ) !== false,
	'ICS only after final Teams scheduling' => strpos( $portal, "'scheduled' !== \$offer['status']" ) !== false
		&& strpos( $portal, 'meeting_ics' ) !== false
		&& strpos( $repo, 'METHOD:PUBLISH' ) !== false,
	'proposal acceptance is not a contract' => strpos( $repo, "'status'          => 'accepted_pending_contract'" ) !== false
		&& strpos( $repo, "'automatic_contract'  => false" ) !== false
		&& strpos( $portal_ui, 'It is not an electronic signature, executed contract, payment authorization, or active engagement.' ) !== false,
	'proposal acceptance requires authority and boundary acknowledgment' => strpos( $repo, 'workflow_proposal_authority_required' ) !== false
		&& strpos( $repo, 'workflow_proposal_boundary_required' ) !== false
		&& strpos( $portal_ui, 'proposal_authority_attested' ) !== false
		&& strpos( $portal_ui, 'proposal_boundary_acknowledged' ) !== false,
	'proposal response uses typed confirmation' => strpos( $repo, "'ACCEPT ' . strtoupper" ) !== false
		&& strpos( $repo, "'DECLINE ' . strtoupper" ) !== false,
	'external contract requires administrative attestation' => strpos( $admin, "'CONTRACT ' : 'WITHDRAW '" ) !== false
		&& strpos( $repo, 'contract_reference' ) !== false
		&& strpos( $repo, 'proposal_contracted' ) !== false,
	'no automatic contract, payment, or email' => strpos( $repo, "'automatic_contract'=> false" ) !== false
		&& strpos( $repo, "'automatic_payment' => false" ) !== false
		&& strpos( $diagnostic, "'automatic_email'         => false" ) !== false
		&& strpos( $repo, 'wp_mail(' ) === false,
	'portal workflow permissions' => strpos( $portal_schema, "'view_meetings'" ) !== false
		&& strpos( $portal_schema, "'respond_meetings'" ) !== false
		&& strpos( $portal_schema, "'view_proposals'" ) !== false
		&& strpos( $portal_schema, "'respond_proposals'" ) !== false,
	'portal workflow actions are CSRF and permission gated' => strpos( $portal, "require_context( 'respond_meetings' )" ) !== false
		&& strpos( $portal, "require_context( 'respond_proposals' )" ) !== false
		&& strpos( $portal, 'valid_csrf' ) !== false,
	'proposal print remains authenticated and CSP protected' => strpos( $portal, 'handle_proposal_print' ) !== false
		&& strpos( $portal, "Content-Security-Policy" ) !== false
		&& strpos( $print, 'onclick=' ) === false,
	'workflow privacy export' => strpos( $privacy, 'Engagement Intake Microsoft Teams Scheduling' ) !== false
		&& strpos( $privacy, 'Engagement Intake Proposals' ) !== false,
	'workflow private inventory' => strpos( $inventory, "'meeting_offers'" ) !== false
		&& strpos( $inventory, "'proposal_versions'" ) !== false
		&& strpos( $inventory, "'workflow_events'" ) !== false,
	'workflow approved erasure' => strpos( $retention, 'SC_EI_Workflow_Repository::redact_for_privacy' ) !== false
		&& strpos( $repo, "SET sender_note = '', alternative_request = '', admin_note = ''" ) !== false,
	'workflow expiration cleanup' => strpos( $repo, 'sc_ei_workflow_cleanup' ) !== false
		&& strpos( $repo, 'expired_meetings' ) !== false
		&& strpos( $repo, 'expired_proposals' ) !== false,
	'workflow diagnostics' => strpos( $diagnostic, "'microsoft_teams_only'    => true" ) !== false
		&& strpos( $diagnostic, "'proposal_version_hash'   => true" ) !== false
		&& strpos( $diagnostic, "'human_contract_attestation'=> true" ) !== false,
	'granular workflow capabilities' => strpos( $caps, 'sc_intake_create_meeting_offers' ) !== false
		&& strpos( $caps, 'sc_intake_finalize_meetings' ) !== false
		&& strpos( $caps, 'sc_intake_publish_proposals' ) !== false
		&& strpos( $caps, 'sc_intake_record_contracts' ) !== false,
	'reviewers cannot publish or contract' => substr_count(
		substr(
			$caps,
			strpos( $caps, 'private const REVIEWER' ),
			strpos( $caps, 'private const MANAGER' ) - strpos( $caps, 'private const REVIEWER' )
		),
		'sc_intake_publish_proposals'
	) === 0
		&& substr_count(
			substr(
				$caps,
				strpos( $caps, 'private const REVIEWER' ),
				strpos( $caps, 'private const MANAGER' ) - strpos( $caps, 'private const REVIEWER' )
			),
			'sc_intake_record_contracts'
		) === 0,
);

$failed = array_keys( array_filter( $checks, static fn( bool $passed ): bool => ! $passed ) );
if ( $failed ) {
	fwrite( STDERR, 'Workflow checks failed: ' . implode( ', ', $failed ) . PHP_EOL );
	exit( 1 );
}
foreach ( $checks as $label => $passed ) {
	echo 'PASS: ' . $label . PHP_EOL;
}
echo "Engagement Intake v1.0.0 Teams scheduling and proposal workflow checks passed.\n";
