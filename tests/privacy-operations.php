<?php
/**
 * Static Privacy and Retention Center safety checks.
 */

$root       = dirname( __DIR__ );
$plugin     = $root . '/sustainable-catalyst-engagement-intake';
$main       = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$database   = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$schema     = file_get_contents( $plugin . '/includes/class-sc-ei-privacy-schema.php' );
$repository = file_get_contents( $plugin . '/includes/class-sc-ei-privacy-repository.php' );
$policies   = file_get_contents( $plugin . '/includes/class-sc-ei-retention-policy-repository.php' );
$engine     = file_get_contents( $plugin . '/includes/class-sc-ei-retention-engine.php' );
$retention  = file_get_contents( $plugin . '/includes/class-sc-ei-retention.php' );
$admin      = file_get_contents( $plugin . '/includes/class-sc-ei-privacy-admin.php' );
$view       = file_get_contents( $plugin . '/admin/views/privacy-center.php' );
$settings   = file_get_contents( $plugin . '/includes/class-sc-ei-admin.php' );
$privacy    = file_get_contents( $plugin . '/includes/class-sc-ei-privacy.php' );
$mailer     = file_get_contents( $plugin . '/includes/class-sc-ei-mailer.php' );
$diagnostics= file_get_contents( $plugin . '/includes/class-sc-ei-diagnostics.php' );
$rest       = file_get_contents( $plugin . '/includes/class-sc-ei-rest.php' );
$review     = file_get_contents( $plugin . '/includes/class-sc-ei-review-repository.php' );
$javascript = file_get_contents( $plugin . '/assets/js/admin.js' );
$uninstall  = file_get_contents( $plugin . '/uninstall.php' );

$default_start = strpos( $settings, 'public static function default_settings' );
$default_end   = strpos( $settings, 'public static function sanitize_settings', $default_start );
$default_block = substr( $settings, $default_start, $default_end - $default_start );

$checks = array(
	'privacy schema loaded'                => strpos( $main, 'class-sc-ei-privacy-schema.php' ) !== false,
	'privacy repository loaded'            => strpos( $main, 'class-sc-ei-privacy-repository.php' ) !== false,
	'retention policy repository loaded'   => strpos( $main, 'class-sc-ei-retention-policy-repository.php' ) !== false,
	'retention engine loaded'              => strpos( $main, 'class-sc-ei-retention-engine.php' ) !== false,
	'privacy admin loaded'                 => strpos( $main, 'class-sc-ei-privacy-admin.php' ) !== false,
	'privacy schema version'               => strpos( $main, "SC_EI_PRIVACY_SCHEMA_VERSION', '1.0.0'") !== false,
	'five lifecycle tables declared'       => strpos( $database, '$sql_privacy_requests') !== false
		&& strpos( $database, '$sql_consent_events') !== false
		&& strpos( $database, '$sql_legal_holds') !== false
		&& strpos( $database, '$sql_retention_policies') !== false
		&& strpos( $database, '$sql_retention_actions') !== false,
	'five lifecycle tables installed'      => strpos( $database, 'dbDelta( $sql_privacy_requests )') !== false
		&& strpos( $database, 'dbDelta( $sql_consent_events )') !== false
		&& strpos( $database, 'dbDelta( $sql_legal_holds )') !== false
		&& strpos( $database, 'dbDelta( $sql_retention_policies )') !== false
		&& strpos( $database, 'dbDelta( $sql_retention_actions )') !== false,
	'inquiry lifecycle fields'             => strpos( $database, 'privacy_status varchar') !== false
		&& strpos( $database, 'retention_policy_key varchar') !== false
		&& strpos( $database, 'legal_hold_count int') !== false
		&& strpos( $database, 'personal_data_erased_at datetime') !== false,
	'privacy field diagnostics'            => strpos( $database, 'privacy_columns_exist') !== false,
	'privacy defaults backfilled'          => strpos( $database, 'backfill_privacy_defaults') !== false,
	'policy versions transactional'        => strpos( $policies, 'START TRANSACTION') !== false
		&& strpos( $policies, 'ROLLBACK') !== false
		&& strpos( $policies, 'COMMIT') !== false,
	'policies preserve old versions'       => strpos( $policies, "'status' => 'archived'") !== false,
	'attachment policy anchor enforced'    => strpos( $policies, 'attachment_anchor_invalid') !== false,
	'consent capture hook'                 => strpos( $repository, 'sc_ei_public_inquiry_created') !== false
		&& strpos( $repository, 'capture_public_consent') !== false,
	'consent email evidence hashed'        => strpos( $repository, "hash( 'sha256'") !== false
		&& strpos( $repository, 'subject_email_hash') !== false,
	'withdrawal restricts processing'      => strpos( $repository, "'withdrawn' === \$action" ) !== false
		&& strpos( $repository, "'restricted'") !== false,
	'privacy request deadline'             => strpos( $repository, 'privacy_request_due_days') !== false,
	'privacy resolution required'          => strpos( $repository, 'privacy_resolution_required') !== false,
	'legal hold requires authority/reason' => strpos( $repository, 'hold_reason_required') !== false,
	'legal hold blocks queue actions'       => strpos( $repository, 'block_queued_actions_for_hold') !== false,
	'legal hold checked on approval'        => strpos( $repository, 'legal_hold_active') !== false
		&& strpos( $repository, 'approve_action') !== false,
	'any related hold blocks inquiry action' => strpos( $repository, "ORDER BY CASE WHEN scope = 'inquiry' THEN 0 ELSE 1 END") !== false,
	'distinct approver option'             => strpos( $repository, 'distinct_approver_required') !== false,
	'retention dedupe key'                 => strpos( $repository, 'find_action_by_dedupe') !== false
		&& strpos( $database, 'UNIQUE KEY dedupe_key') !== false,
	'cron is queue only'                   => strpos( $retention, 'queue_candidates') !== false
		&& strpos( $retention, 'delete_file') === false
		&& strpos( $retention, 'mark_deleted') === false,
	'preview is read only'                 => strpos( $engine, 'public static function preview') !== false,
	'candidate scan deterministic'         => strpos( $engine, 'dedupe_key') !== false
		&& strpos( $engine, 'policy_version') !== false,
	'execution requires action state'      => strpos( $engine, 'retention_action_not_executable') !== false,
	'execution rechecks legal hold'        => strpos( $engine, 'active_hold') !== false
		&& strpos( $engine, 'blocked_hold') !== false,
	'attachment deletion verifies absence'=> strpos( $engine, 'physical_delete_not_verified') !== false
		&& strpos( $engine, 'physical_absence_verified') !== false,
	'attachment tombstone recorded'       => strpos( $engine, 'mark_deleted') !== false,
	'inquiry erasure blocks attachments'  => strpos( $engine, 'attachments_remaining') !== false,
	'inquiry redaction transactional'      => strpos( $engine, 'START TRANSACTION') !== false
		&& strpos( $engine, 'ROLLBACK') !== false
		&& strpos( $engine, 'COMMIT') !== false,
	'inquiry tombstone preserved'          => strpos( $engine, 'tombstone_preserved') !== false,
	'consent evidence redacted'             => strpos( $engine, "SET evidence_text = '', subject_email_hash = ''") !== false,
	'privacy request identifiers redacted'  => strpos( $engine, "SET requester_name = '', requester_email = '', request_summary = ''") !== false,
	'hold narratives redacted'              => strpos( $engine, "SET reason = '', authority = '', release_reason = ''") !== false,
	'lifecycle snapshots redacted'          => strpos( $engine, "SET reason = '', failure_message = '', snapshot_json = %s") !== false,
	'communication event context redacted'  => strpos( $engine, 'WHERE communication_id = %d') !== false
		&& strpos( $engine, 'event_context_redacted') !== false,
	'communication redaction supported'    => strpos( $engine, 'redact_communication') !== false
		&& strpos( $engine, 'Communication content erased through Privacy and Retention Center') !== false,
	'approval and execution separate'      => strpos( $admin, 'handle_approve_action') !== false
		&& strpos( $admin, 'handle_execute_action') !== false,
	'typed execution required'             => strpos( $admin, "'EXECUTE ' . \$id" ) !== false
		&& strpos( $view, 'Type EXECUTE %d') !== false,
	'queue confirmation required'          => strpos( $admin, "'QUEUE'") !== false
		&& strpos( $view, 'Type QUEUE') !== false,
	'destructive capabilities separate'    => strpos( $admin, 'sc_intake_approve_retention_actions') !== false
		&& strpos( $admin, 'sc_intake_execute_retention_actions') !== false,
	'privacy inventory private export'     => strpos( $admin, 'sc_intake_export_privacy_data') !== false
		&& strpos( $admin, 'check_admin_referer') !== false,
	'WordPress eraser queues only'         => strpos( $privacy, 'queue-only eraser bridge') !== false
		&& strpos( $privacy, 'SC_EI_Privacy_Repository::queue_action') !== false
		&& strpos( $privacy, 'SC_EI_Storage::delete_file') === false,
	'WordPress eraser creates case'        => strpos( $privacy, 'ensure_erasure_request') !== false,
	'WordPress eraser reports retained'    => strpos( $privacy, "'items_removed'  => false") !== false
		&& strpos( $privacy, "'items_retained' => true") !== false,
	'privacy export includes lifecycle'    => strpos( $privacy, 'Engagement Intake Consent and Authorization Events') !== false
		&& strpos( $privacy, 'Engagement Intake Legal Holds') !== false
		&& strpos( $privacy, 'Engagement Intake Retention Actions') !== false
		&& strpos( $privacy, 'Engagement Intake Privacy Requests') !== false,
	'privacy-state mail suppression'       => strpos( $mailer, 'privacy_processing_restricted') !== false,
	'REST privacy capability boundary'     => strpos( $rest, "current_user_can( 'sc_intake_view_privacy_center' )") !== false,
	'review packet includes privacy'       => strpos( $review, "'privacy'        => array(") !== false,
	'diagnostics include lifecycle health' => strpos( $diagnostics, 'privacy_lifecycle') !== false
		&& strpos( $diagnostics, 'retention_policies') !== false,
	'fixed queue and tombstone settings'   => strpos( $settings, "'retention_cron_queue_only'          => 1") !== false
		&& strpos( $settings, "'retain_tombstones'                  => 1") !== false,
	'approval fixed in sanitization'        => strpos( $settings, "'require_retention_approval'         => 1") !== false,
	'direct erased state blocked'           => strpos( $repository, 'if ( \'erased\' === $status )' ) !== false,
	'default settings bug repaired'        => strpos( $default_block, '$value') === false,
	'UI states no silent deletion'         => strpos( $view, 'No silent deletion') !== false,
	'UI legal advice boundary'             => strpos( $view, 'Not legal advice') !== false,
	'JS typed execution check'             => strpos( $javascript, 'EXECUTE ${actionId}') !== false,
	'uninstall lifecycle cleanup'          => strpos( $uninstall, 'sc_ei_last_privacy_retention_preview') !== false
		&& strpos( $uninstall, 'sc_ei_last_retention_queue_run') !== false,
);

$failed = array_keys( array_filter( $checks, static fn( bool $passed ): bool => ! $passed ) );
if ( $failed ) {
	fwrite( STDERR, 'Privacy operation checks failed: ' . implode( ', ', $failed ) . PHP_EOL );
	exit( 1 );
}
foreach ( $checks as $label => $passed ) {
	echo 'PASS: ' . $label . PHP_EOL;
}
echo "Engagement Intake v0.8.0 privacy operation checks passed.\n";
