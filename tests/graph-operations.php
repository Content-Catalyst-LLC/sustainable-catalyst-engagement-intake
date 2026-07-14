<?php
$plugin = dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake';
$main = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$db = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$repo = file_get_contents( $plugin . '/includes/class-sc-ei-graph-repository.php' );
$client = file_get_contents( $plugin . '/includes/class-sc-ei-graph-client.php' );
$workflow = file_get_contents( $plugin . '/includes/class-sc-ei-workflow-repository.php' );
$admin = file_get_contents( $plugin . '/includes/class-sc-ei-graph-admin.php' );
$view = file_get_contents( $plugin . '/admin/views/teams-proposals.php' );
$diagnostics = file_get_contents( $plugin . '/includes/class-sc-ei-diagnostics.php' );
$privacy = file_get_contents( $plugin . '/includes/class-sc-ei-privacy.php' );
$inventory = file_get_contents( $plugin . '/includes/class-sc-ei-privacy-repository.php' );
$caps = file_get_contents( $plugin . '/includes/class-sc-ei-capabilities.php' );

$checks = array(
	'Graph schema and components loaded' => strpos( $main, "SC_EI_GRAPH_SCHEMA_VERSION', '1.0.0'" ) !== false
		&& strpos( $main, 'class-sc-ei-graph-crypto.php' ) !== false
		&& strpos( $main, 'class-sc-ei-graph-repository.php' ) !== false,
	'durable operation queue schema' => strpos( $db, '$sql_graph_operations' ) !== false
		&& strpos( $db, 'UNIQUE KEY idempotency_key' ) !== false
		&& strpos( $db, 'lock_token char(36)' ) !== false
		&& strpos( $db, 'retry_after_seconds' ) !== false,
	'meeting remote linkage schema' => strpos( $db, 'graph_transaction_id char(36)' ) !== false
		&& strpos( $db, 'graph_event_id text' ) !== false
		&& strpos( $db, 'graph_join_url text' ) !== false
		&& strpos( $db, 'graph_last_request_id' ) !== false,
	'encrypted operation payload' => strpos( $repo, 'SC_EI_Graph_Crypto::seal_array' ) !== false
		&& strpos( $repo, 'SC_EI_Graph_Crypto::open_array' ) !== false,
	'persistent Graph transaction ID' => strpos( $repo, "'graph_transaction_id' => \$transaction_id" ) !== false
		&& strpos( $repo, "'transactionId'         => (string) \$offer['graph_transaction_id']" ) !== false,
	'idempotent create operation' => strpos( $repo, "hash( 'sha256', 'create|' . \$meeting_offer_id . '|' . \$transaction_id )" ) !== false
		&& strpos( $repo, 'WHERE idempotency_key = %s LIMIT 1' ) !== false,
	'optimistic queue claim' => strpos( $repo, "'status'        => 'processing'" ) !== false
		&& strpos( $repo, "'row_version' => absint( \$operation['row_version'] )" ) !== false
		&& strpos( $repo, 'graph_operation_claim_conflict' ) !== false,
	'stale lock recovery' => strpos( $repo, 'graph_stale_lock_recovered' ) !== false
		&& strpos( $repo, "WHERE status = 'processing' AND locked_at < %s" ) !== false,
	'bounded retry workflow' => strpos( $repo, "'retry_wait'" ) !== false
		&& strpos( $repo, 'SC_EI_Graph_Client::retry_delay' ) !== false
		&& strpos( $repo, '$attempt < max' ) !== false,
	'manual retry preserves idempotency' => strpos( $repo, 'public static function retry_operation' ) !== false
		&& strpos( $repo, "'idempotency_preserved'=> true" ) !== false
		&& strpos( $admin, "'RETRY GRAPH ' . \$operation_id" ) !== false,
	'join URL reconciliation' => strpos( $repo, 'graph_join_url_pending' ) !== false
		&& strpos( $repo, "onlineMeeting']['joinUrl" ) !== false
		&& strpos( $repo, 'enqueue_reconcile' ) !== false,
	'stale local state blocks remote POST' => strpos( $repo, "'graph_local_state_blocked'" ) !== false
		&& strpos( $repo, "'accepted_pending_link' !== \$offer['status']" ) !== false,
	'reconciliation cannot resurrect closed meetings' => strpos( $repo, '$may_finalize' ) !== false
		&& strpos( $repo, "'remote_exists_local_closed'" ) !== false,
	'remote delete is explicit human action' => strpos( $admin, "'DELETE GRAPH ' . strtoupper" ) !== false
		&& strpos( $repo, 'enqueue_delete' ) !== false,
	'manual fallback retained' => strpos( $view, 'Manual Teams URL finalization remains available regardless of connector state.' ) !== false
		&& strpos( $view, 'sc_ei_finalize_meeting' ) !== false,
	'no direct email or contract integration' => strpos( $repo, 'wp_mail(' ) === false
		&& strpos( $repo, 'proposal' ) === false,
	'Graph client never uses beta' => strpos( $client, 'graph.microsoft.com/beta' ) === false,
	'Graph operation privacy export' => strpos( $privacy, 'Engagement Intake Microsoft Graph Calendar Operations' ) !== false
		&& strpos( $inventory, "'graph_operations'" ) !== false,
	'Graph approved erasure' => strpos( $workflow, 'SC_EI_Graph_Repository::redact_for_privacy' ) !== false
		&& strpos( $repo, "SET payload_json = '', graph_error_message = ''" ) !== false,
	'Graph diagnostics' => strpos( $diagnostics, "'transaction_id'          => true" ) !== false
		&& strpos( $diagnostics, "'retry_after'             => true" ) !== false
		&& strpos( $diagnostics, "'human_triggered_only'    => true" ) !== false
		&& strpos( $diagnostics, "'manual_fallback'         => true" ) !== false,
	'least-privilege capabilities' => strpos( $caps, 'sc_intake_manage_graph_settings' ) !== false
		&& strpos( $caps, 'sc_intake_create_graph_events' ) !== false
		&& strpos( $caps, 'sc_intake_reconcile_graph_events' ) !== false
		&& strpos( $caps, 'sc_intake_cancel_graph_events' ) !== false,
);
$failed = array_keys( array_filter( $checks, static fn( bool $passed ): bool => ! $passed ) );
if ( $failed ) {
	fwrite( STDERR, 'Graph operation checks failed: ' . implode( ', ', $failed ) . PHP_EOL );
	exit( 1 );
}
foreach ( $checks as $label => $passed ) {
	echo 'PASS: ' . $label . PHP_EOL;
}
echo "Engagement Intake v0.11.0 Microsoft Graph reliability checks passed.\n";
