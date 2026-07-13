<?php
$root = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';

$main = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$db = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$graph_client = file_get_contents( $plugin . '/includes/class-sc-ei-graph-client.php' );
$graph_repo = file_get_contents( $plugin . '/includes/class-sc-ei-graph-repository.php' );
$graph_admin = file_get_contents( $plugin . '/includes/class-sc-ei-graph-admin.php' );

$required = array(
	$plugin . '/includes/class-sc-ei-graph-crypto.php',
	$plugin . '/includes/class-sc-ei-graph-credentials.php',
	$plugin . '/includes/class-sc-ei-graph-client.php',
	$plugin . '/includes/class-sc-ei-graph-repository.php',
	$plugin . '/includes/class-sc-ei-graph-admin.php',
	$plugin . '/admin/views/microsoft-graph.php',
);
$failures = array();
foreach ( $required as $file ) {
	if ( ! is_file( $file ) ) {
		$failures[] = 'Missing: ' . $file;
	}
}
foreach ( array(
	'Version:     0.9.1'                         => $main,
	"SC_EI_DB_VERSION', '0.9.1'"                => $main,
	"SC_EI_PORTAL_SCHEMA_VERSION', '1.2.0'"      => $main,
	"SC_EI_WORKFLOW_SCHEMA_VERSION', '1.1.0'"    => $main,
	"SC_EI_GRAPH_SCHEMA_VERSION', '1.0.0'"       => $main,
	'$sql_graph_operations'                       => $db,
	'graph_transaction_id char(36)'               => $db,
	'graph_join_url text'                         => $db,
	'GRAPH_RESOURCE'                              => $graph_client,
	"GRAPH_RESOURCE . '/.default'"                => $graph_client,
	'public static function enqueue_create'       => $graph_repo,
	'public static function retry_operation'      => $graph_repo,
	'public static function enqueue_reconcile'    => $graph_repo,
	'public static function enqueue_delete'       => $graph_repo,
	'sc_ei_create_graph_event'                    => $graph_admin,
	'sc_ei_retry_graph_operation'                 => $graph_admin,
) as $marker => $source ) {
	if ( false === strpos( $source, $marker ) ) {
		$failures[] = 'Marker missing: ' . $marker;
	}
}
if ( false !== strpos( $graph_repo, 'wp_mail(' ) ) {
	$failures[] = 'Graph repository sends mail directly.';
}
if ( false !== strpos( $graph_client, 'https://graph.microsoft.com/beta' ) ) {
	$failures[] = 'Graph connector uses beta APIs.';
}
if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}
echo "Engagement Intake v0.9.1 smoke checks passed.\n";
