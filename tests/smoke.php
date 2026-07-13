<?php
$root = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';

$main = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$db = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$workflow = file_get_contents( $plugin . '/includes/class-sc-ei-workflow-repository.php' );
$portal = file_get_contents( $plugin . '/includes/class-sc-ei-portal-public.php' );

$required = array(
	$plugin . '/includes/class-sc-ei-workflow-schema.php',
	$plugin . '/includes/class-sc-ei-workflow-repository.php',
	$plugin . '/includes/class-sc-ei-workflow-admin.php',
	$plugin . '/admin/views/teams-proposals.php',
	$plugin . '/public/views/proposal-print.php',
);
$failures = array();
foreach ( $required as $file ) {
	if ( ! is_file( $file ) ) {
		$failures[] = 'Missing: ' . $file;
	}
}
foreach ( array(
	'Version:     0.9.0'                         => $main,
	"SC_EI_DB_VERSION', '0.9.0'"                => $main,
	"SC_EI_PORTAL_SCHEMA_VERSION', '1.2.0'"      => $main,
	"SC_EI_WORKFLOW_SCHEMA_VERSION', '1.0.0'"    => $main,
	'$sql_meeting_offers'                        => $db,
	'$sql_proposals'                              => $db,
	'$sql_proposal_versions'                      => $db,
	'$sql_workflow_events'                        => $db,
	'pending_version_id'                          => $db,
	'public static function create_meeting_offer' => $workflow,
	'public static function create_proposal'      => $workflow,
	'public static function respond_to_proposal'  => $workflow,
	'public static function meeting_ics'          => $workflow,
	'sc_ei_portal_respond_meeting'                => $portal,
	'sc_ei_portal_respond_proposal'               => $portal,
) as $marker => $source ) {
	if ( false === strpos( $source, $marker ) ) {
		$failures[] = 'Marker missing: ' . $marker;
	}
}
if ( false !== strpos( $workflow, 'wp_mail(' ) ) {
	$failures[] = 'Workflow repository sends automatic email.';
}
if ( false !== strpos( $workflow, 'graph.microsoft.com' ) ) {
	$failures[] = 'Workflow repository performs Microsoft Graph booking.';
}
if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}
echo "Engagement Intake v0.9.0 smoke checks passed.\n";
