<?php
/**
 * Static Administrative Review Workspace safety checks.
 */

$root       = dirname( __DIR__ );
$plugin     = $root . '/sustainable-catalyst-engagement-intake';
$main       = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$database   = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$schema     = file_get_contents( $plugin . '/includes/class-sc-ei-review-schema.php' );
$repository = file_get_contents( $plugin . '/includes/class-sc-ei-review-repository.php' );
$admin      = file_get_contents( $plugin . '/includes/class-sc-ei-review-admin.php' );
$table      = file_get_contents( $plugin . '/includes/class-sc-ei-review-list-table.php' );
$workspace  = file_get_contents( $plugin . '/admin/views/review-workspace.php' );
$detail     = file_get_contents( $plugin . '/admin/views/review-detail.php' );
$settings   = file_get_contents( $plugin . '/admin/views/settings.php' );
$privacy    = file_get_contents( $plugin . '/includes/class-sc-ei-privacy.php' );
$retention_engine = file_get_contents( $plugin . '/includes/class-sc-ei-retention-engine.php' );
$javascript = file_get_contents( $plugin . '/assets/js/admin.js' );

$checks = array(
	'review schema loaded'                 => strpos( $main, 'class-sc-ei-review-schema.php' ) !== false,
	'review repository loaded'             => strpos( $main, 'class-sc-ei-review-repository.php' ) !== false,
	'review admin loaded'                  => strpos( $main, 'class-sc-ei-review-admin.php' ) !== false,
	'reviews table declared'               => strpos( $database, '$sql_reviews') !== false,
	'reviews table installed'              => strpos( $database, 'dbDelta( $sql_reviews )') !== false,
	'review due backfill'                  => strpos( $database, 'backfill_review_defaults') !== false,
	'review current fields'                => strpos( $database, 'review_version int') !== false && strpos( $database, 'decision_rationale') !== false,
	'review snapshot fields'               => strpos( $database, 'snapshot_json') !== false,
	'manual taxonomies'                    => strpos( $schema, 'fit_decisions') !== false && strpos( $schema, 'risk_levels') !== false,
	'no scoring API'                       => strpos( $schema . $repository . $admin, 'fit_score') === false,
	'checklist completion safeguard'       => strpos( $repository, 'review_checklist_incomplete') !== false,
	'rationale safeguard'                  => strpos( $repository, 'review_rationale_required') !== false,
	'completed decision safeguard'         => strpos( $repository, 'review_decision_incomplete') !== false,
	'escalation reason safeguard'          => strpos( $repository, 'escalation_reason_required') !== false,
	'optimistic version check'             => strpos( $repository, 'expected_version') !== false && strpos( $repository, 'review_conflict') !== false,
	'transactional current and snapshot'   => strpos( $repository, 'START TRANSACTION') !== false && strpos( $repository, 'ROLLBACK') !== false && strpos( $repository, 'COMMIT') !== false,
	'immutable snapshot insert'            => strpos( $repository, 'insert_snapshot') !== false,
	'assignment validation'                => strpos( $repository, "user_can( \$assigned_user_id, 'sc_intake_review' )" ) !== false,
	'cross-inquiry review query'           => strpos( $repository, 'public static function query') !== false,
	'queue metrics'                        => strpos( $repository, 'public static function metrics') !== false,
	'document attention aggregation'       => strpos( $repository, 'document_attention_count') !== false,
	'private review packet'                => strpos( $repository, 'sc-engagement-intake-review-packet/1.0') !== false,
	'assignee edit restriction'            => strpos( $admin, 'restrict_review_to_assignee') !== false,
	'self assignment policy'               => strpos( $admin, 'reviewer_self_assignment') !== false,
	'bulk review capability'               => strpos( $admin, 'sc_intake_bulk_review_actions') !== false,
	'bulk review limit'                    => strpos( $admin, 'review_bulk_limit') !== false && strpos( $admin, 'min( 50') !== false,
	'packet export capability and nonce'   => strpos( $admin, 'sc_intake_export_review_packet') !== false && strpos( $admin, 'check_admin_referer') !== false,
	'queue filters'                        => strpos( $workspace, 'review_stage') !== false && strpos( $workspace, 'due_state') !== false,
	'human method boundary'                => strpos( $workspace, 'does not automatically accept, reject, score, or route') !== false,
	'manual fit panel'                     => strpos( $detail, 'Fit decision') !== false && strpos( $detail, 'Fit confidence') !== false,
	'review checklist UI'                  => strpos( $detail, 'Administrative review checklist') !== false,
	'escalation UI'                        => strpos( $detail, 'Escalation reason and resolution record') !== false,
	'status explicitly selected'           => strpos( $detail, 'Inquiry status') !== false,
	'no silent status inference copy'      => strpos( $detail, 'does not send a message, schedule a meeting, or change status') !== false,
	'structured history UI'                => strpos( $detail, 'Structured Review History') !== false,
	'read-only assigned review UI'         => strpos( $detail, 'Read-only review') !== false,
	'unsaved review warning'               => strpos( $javascript, 'beforeunload') !== false,
	'checklist progress JavaScript'        => strpos( $javascript, 'updateProgress') !== false,
	'review settings'                      => strpos( $settings, 'default_review_due_days') !== false && strpos( $settings, 'require_completion_checklist') !== false,
	'privacy review export'                => strpos( $privacy, 'Engagement Intake Administrative Reviews') !== false,
	'privacy review narrative erasure'     => strpos( $retention_engine, "SET summary = ''") !== false
		&& strpos( $privacy, 'queue-only eraser bridge') !== false,
);

$failed = array_keys( array_filter( $checks, static fn( bool $passed ): bool => ! $passed ) );
if ( $failed ) {
	fwrite( STDERR, 'Review operations checks failed: ' . implode( ', ', $failed ) . PHP_EOL );
	exit( 1 );
}

foreach ( $checks as $label => $passed ) {
	echo 'PASS: ' . $label . PHP_EOL;
}

echo "Engagement Intake v0.9.1 administrative review operation checks passed.\n";
