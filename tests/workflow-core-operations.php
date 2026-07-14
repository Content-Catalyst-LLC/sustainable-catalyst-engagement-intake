<?php
$plugin = dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake';
$repo = file_get_contents( $plugin . '/includes/class-sc-ei-workflow-core-repository.php' );
$contract = file_get_contents( $plugin . '/includes/class-sc-ei-workflow-core-contract.php' );
$service = file_get_contents( $plugin . '/includes/class-sc-ei-workflow-core-service.php' );
$admin = file_get_contents( $plugin . '/includes/class-sc-ei-workflow-core-admin.php' );
$rest = file_get_contents( $plugin . '/includes/class-sc-ei-rest.php' );
$privacy = file_get_contents( $plugin . '/includes/class-sc-ei-privacy.php' );
$retention = file_get_contents( $plugin . '/includes/class-sc-ei-retention-engine.php' );
$diagnostics = file_get_contents( $plugin . '/includes/class-sc-ei-diagnostics.php' );
$hardening = file_get_contents( $plugin . '/includes/class-sc-ei-hardening-repository.php' );
$view = file_get_contents( $plugin . '/admin/views/workflow-core.php' );
$core_admin = file_get_contents( $plugin . '/includes/class-sc-ei-admin.php' );
$default_start = strpos( $core_admin, 'public static function default_settings(): array' );
$sanitize_start = strpos( $core_admin, 'public static function sanitize_settings', $default_start );
$default_segment = substr( $core_admin, $default_start, $sanitize_start - $default_start );
$checks = array(
	'authoritative projection only' => false !== strpos( $repo, 'private static function derive_projection' )
		&& false !== strpos( $repo, "SC_EI_Database::table( 'meeting_offers' )" )
		&& false !== strpos( $repo, "SC_EI_Database::table( 'proposals' )" )
		&& false !== strpos( $repo, "SC_EI_Database::table( 'engagements' )" )
		&& false !== strpos( $repo, 'SC_EI_Fit_Repository::current_for_inquiry' ),
	'projection fingerprint covers authoritative changes' => false !== strpos( $repo, "'contract_reference_hash'" )
		&& false !== strpos( $repo, "'graph_sync_status'" )
		&& false !== strpos( $repo, "'current_snapshot_id'" )
		&& false !== strpos( $repo, '$source_updated_at' ),
	'idempotent command ledger' => false !== strpos( $repo, '$command_key = hash(' )
		&& false !== strpos( $repo, 'command_by_key' )
		&& false !== strpos( $repo, 'workflow_core_command_claim_conflict' ),
	'idempotent signed handoffs' => false !== strpos( $repo, '$handoff_key = hash(' )
		&& false !== strpos( $repo, 'handoff_by_key' )
		&& false !== strpos( $contract, 'hash_hmac(' )
		&& false !== strpos( $contract, 'public static function verify' ),
	'durable outbox recovery' => false !== strpos( $repo, 'recover_stale_outbox_claims' )
		&& false !== strpos( $repo, 'retry_wait' )
		&& false !== strpos( $repo, '$delay = min( HOUR_IN_SECONDS' )
		&& false !== strpos( $repo, 'claim_token' ),
	'explicit adapter registry' => false !== strpos( $service, 'public static function register_adapter' )
		&& false !== strpos( $service, 'workflow_core_adapter_unavailable' )
		&& false !== strpos( $repo, 'sc_ei_workflow_core_event_dispatched' )
		&& false === strpos( $repo, "do_action( 'sc_ei_workflow_core_event'," ),
	'no direct external delivery' => false === strpos( $repo, 'wp_remote_' )
		&& false === strpos( $repo, 'wp_mail(' )
		&& false === strpos( $service, 'wp_remote_' )
		&& false === strpos( $service, 'wp_mail(' ),
	'no authoritative decision mutation' => false === strpos( $repo, 'SC_EI_Inquiry_Repository::update_status' )
		&& false === strpos( $repo, 'SC_EI_Fit_Repository::finalize' )
		&& false === strpos( $repo, 'SC_EI_Engagement_Repository::activate' )
		&& false === strpos( $repo, 'record_contract' ),
	'typed human commands' => false !== strpos( $admin, 'SYNC WORKFLOW CORE' )
		&& false !== strpos( $admin, "'HANDOFF ' . strtoupper" )
		&& false !== strpos( $admin, 'DISPATCH OUTBOX' )
		&& false !== strpos( $admin, "'ACK HANDOFF '" )
		&& false !== strpos( $admin, "'CANCEL HANDOFF '" )
		&& false !== strpos( $admin, 'check_admin_referer' ),
	'private data capability gate' => false !== strpos( $repo, "current_user_can( 'sc_intake_export_workflow_core_private' )" )
		&& false !== strpos( $admin, "self::require_cap( 'sc_intake_export_workflow_core_private' )" )
		&& false !== strpos( $contract, "'operational_minimum'" ),
	'read-only REST integration' => false !== strpos( $rest, "'/workflow-core/cases'" )
		&& false !== strpos( $rest, "'read_only'   => true" )
		&& false === strpos( $rest, "'/workflow-core/commands'" ),
	'audit-driven deferred synchronization' => false !== strpos( $service, 'sc_ei_audit_recorded' )
		&& false !== strpos( $service, 'wp_schedule_single_event' )
		&& false !== strpos( $service, "str_starts_with( \$event_type, 'workflow_core_' )" ),
	'privacy export and integrity-preserving erasure' => false !== strpos( $privacy, 'Engagement Intake Workflow Core Handoffs' )
		&& false !== strpos( $retention, 'SC_EI_Workflow_Core_Repository::redact_for_privacy' )
		&& false !== strpos( $repo, 'SC_EI_Workflow_Core_Contract::seal_payload' )
		&& false !== strpos( $repo, "'original_payload_hash'" ),
	'diagnostics and reliability integration' => false !== strpos( $diagnostics, "'workflow_core_schema_version'" )
		&& false !== strpos( $diagnostics, "'internal_adapters_only'" )
		&& false !== strpos( $hardening, "'workflow_core_sync'" )
		&& false !== strpos( $hardening, "'workflow_core_outbox'" ),
	'boundary disclosure' => false !== strpos( $view, 'cannot accept or reject inquiries' )
		&& false !== strpos( $view, 'No arbitrary URL or webhook field is exposed' )
		&& false !== strpos( $view, 'registered internal WordPress adapters only' ),
	'default settings purity' => false !== strpos( $default_segment, 'SC_EI_Workflow_Core_Schema::default_settings()' )
		&& false === strpos( $default_segment, '$value[' )
		&& false === strpos( $default_segment, '$current[' ),
);
$failed = array_keys( array_filter( $checks, static fn( $value ) => ! $value ) );
if ( $failed ) {
	fwrite( STDERR, 'Workflow Core operation checks failed: ' . implode( ', ', $failed ) . PHP_EOL );
	exit( 1 );
}
foreach ( $checks as $label => $passed ) {
	echo 'PASS: ' . $label . PHP_EOL;
}
echo "Engagement Intake v1.0.0 Workflow Core operation checks passed.\n";
