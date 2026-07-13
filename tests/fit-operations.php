<?php
/**
 * Static human-control and fit-assessment safety checks.
 */

$root       = dirname( __DIR__ );
$plugin     = $root . '/sustainable-catalyst-engagement-intake';
$main       = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$database   = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$schema     = file_get_contents( $plugin . '/includes/class-sc-ei-fit-schema.php' );
$repository = file_get_contents( $plugin . '/includes/class-sc-ei-fit-repository.php' );
$admin      = file_get_contents( $plugin . '/includes/class-sc-ei-fit-admin.php' );
$queue_view = file_get_contents( $plugin . '/admin/views/fit-assessment.php' );
$detail     = file_get_contents( $plugin . '/admin/views/fit-assessment-detail.php' );
$settings   = file_get_contents( $plugin . '/includes/class-sc-ei-admin.php' );
$settings_view = file_get_contents( $plugin . '/admin/views/settings.php' );
$privacy    = file_get_contents( $plugin . '/includes/class-sc-ei-privacy.php' );
$engine     = file_get_contents( $plugin . '/includes/class-sc-ei-retention-engine.php' );
$diagnostics= file_get_contents( $plugin . '/includes/class-sc-ei-diagnostics.php' );
$rest       = file_get_contents( $plugin . '/includes/class-sc-ei-rest.php' );
$review     = file_get_contents( $plugin . '/includes/class-sc-ei-review-repository.php' );
$caps       = file_get_contents( $plugin . '/includes/class-sc-ei-capabilities.php' );
$javascript = file_get_contents( $plugin . '/assets/js/admin.js' );

$checks = array(
	'fit schema loaded'                    => strpos( $main, 'class-sc-ei-fit-schema.php' ) !== false,
	'fit repository loaded'                => strpos( $main, 'class-sc-ei-fit-repository.php' ) !== false,
	'fit admin loaded'                     => strpos( $main, 'class-sc-ei-fit-admin.php' ) !== false,
	'fit schema version'                   => strpos( $main, "SC_EI_FIT_SCHEMA_VERSION', '1.0.0'" ) !== false,
	'three fit tables declared'            => strpos( $database, '$sql_fit_assessments' ) !== false
		&& strpos( $database, '$sql_fit_assessment_items' ) !== false
		&& strpos( $database, '$sql_fit_assessment_reviews' ) !== false,
	'three fit tables installed'           => strpos( $database, 'dbDelta( $sql_fit_assessments )' ) !== false
		&& strpos( $database, 'dbDelta( $sql_fit_assessment_items )' ) !== false
		&& strpos( $database, 'dbDelta( $sql_fit_assessment_reviews )' ) !== false,
	'inquiry fit lifecycle fields'         => strpos( $database, 'fit_assessment_status varchar' ) !== false
		&& strpos( $database, 'current_fit_assessment_id bigint' ) !== false
		&& strpos( $database, 'fit_assessment_finalized_at datetime' ) !== false,
	'fit field diagnostics'                => strpos( $database, 'fit_columns_exist' ) !== false,
	'fit defaults backfilled'              => strpos( $database, 'backfill_fit_defaults' ) !== false,
	'sixteen criteria'                     => substr_count( $schema, "'group' =>" ) >= 16,
	'transparent weights'                  => strpos( $schema, "'weight' => 1.50" ) !== false
		&& strpos( $schema, 'calculate_score' ) !== false,
	'no recommendation threshold function' => strpos( $schema, 'threshold' ) === false,
	'score does not create recommendation' => strpos( $schema, 'calculate_score' ) !== false
		&& strpos( $repository, "\$recommendation = SC_EI_Fit_Schema::sanitize_choice" ) !== false,
	'atomic assessment creation'           => strpos( $repository, "START TRANSACTION" ) !== false
		&& strpos( $repository, 'fit_item_seed_failed' ) !== false
		&& strpos( $repository, "ROLLBACK" ) !== false,
	'evidence required'                    => strpos( $repository, 'fit_evidence_required' ) !== false,
	'material concern requires note'       => strpos( $repository, 'fit_material_concern_note_required' ) !== false,
	'optimistic row locking'               => strpos( $repository, "'row_version' => \$expected_version" ) !== false
		&& strpos( $repository, 'fit_assessment_conflict' ) !== false,
	'assessor ownership'                   => strpos( $repository, 'fit_assessor_only' ) !== false,
	'post-submission edits reset workflow' => strpos( $repository, "\$workflow_reset = 'draft' !== \$current['status'];" ) !== false
		&& strpos( $repository, "'second_review_disposition' => \$workflow_reset ? 'not_requested'" ) !== false,
	'second review trigger calculation'    => strpos( $schema, 'second_review_reasons' ) !== false,
	'distinct second reviewer'             => strpos( $repository, 'fit_distinct_reviewer_required' ) !== false,
	'agree cannot silently alter decision' => strpos( $repository, 'fit_second_review_agreement_mismatch' ) !== false,
	'second review attestation'            => strpos( $repository, 'fit_second_review_attestation_required' ) !== false,
	'human attestation required'           => strpos( $repository, 'fit_human_attestation_required' ) !== false,
	'criteria completion required'         => strpos( $repository, 'fit_criteria_incomplete' ) !== false,
	'conditions required when conditional' => strpos( $repository, 'fit_conditions_required' ) !== false,
	'referral or decline notes required'   => strpos( $repository, 'fit_referral_notes_required' ) !== false,
	'finalization requires ready state'     => strpos( $repository, "'ready_to_finalize' !== \$current['status']" ) !== false,
	'finalization typed confirmation'       => strpos( $admin, "'FINALIZE ' . \$assessment_id" ) !== false
		&& strpos( $detail, 'Type FINALIZE %d' ) !== false,
	'apply typed confirmation'             => strpos( $admin, "'APPLY ' . \$assessment_id" ) !== false
		&& strpos( $detail, 'Type APPLY %d' ) !== false,
	'finalization no automatic effects'    => strpos( $repository, "'automatic_status_change' => false" ) !== false
		&& strpos( $repository, "'automatic_communication' => false" ) !== false
		&& strpos( $repository, "'automatic_scheduling' => false" ) !== false,
	'apply is explicit and status neutral' => strpos( $repository, 'fit_assessment_applied_to_review' ) !== false
		&& strpos( $repository, "'inquiry_status_changed'=> false" ) !== false,
	'private JSON export'                  => strpos( $admin, 'sc_intake_export_fit_assessments' ) !== false
		&& strpos( $admin, 'check_admin_referer' ) !== false,
	'privacy export includes fit history'  => strpos( $privacy, 'Engagement Intake Human Fit Assessments' ) !== false
		&& strpos( $privacy, 'Engagement Intake Fit Assessment Criteria' ) !== false
		&& strpos( $privacy, 'Engagement Intake Fit Assessment Second Reviews' ) !== false,
	'privacy erasure redacts fit evidence' => strpos( $repository, 'redact_for_privacy' ) !== false
		&& strpos( $repository, "SET evidence_note = ''" ) !== false
		&& strpos( $repository, "SET review_notes = ''" ) !== false,
	'approved erasure calls fit redaction' => strpos( $engine, 'SC_EI_Fit_Repository::redact_for_privacy' ) !== false,
	'diagnostics human control'            => strpos( $diagnostics, 'fit_human_control' ) !== false
		&& strpos( $diagnostics, "'automatic_recommendation' => false" ) !== false
		&& strpos( $diagnostics, "'advisory_thresholds'      => false" ) !== false,
	'REST capability boundary'             => strpos( $rest, "current_user_can( 'sc_intake_view_fit_assessments' )" ) !== false,
	'review packet includes fit'           => strpos( $review, "'fit_assessment' =>" ) !== false,
	'granular fit capabilities'            => strpos( $caps, 'sc_intake_create_fit_assessments' ) !== false
		&& strpos( $caps, 'sc_intake_review_fit_assessments' ) !== false
		&& strpos( $caps, 'sc_intake_finalize_fit_assessments' ) !== false
		&& strpos( $caps, 'sc_intake_apply_fit_to_review' ) !== false,
	'reviewer cannot finalize by role'     => strpos( $caps, "private const REVIEWER" ) !== false
		&& substr_count( substr( $caps, strpos( $caps, 'private const REVIEWER' ), strpos( $caps, 'private const MANAGER' ) - strpos( $caps, 'private const REVIEWER' ) ), 'sc_intake_finalize_fit_assessments' ) === 0,
	'settings fixed human safeguards'      => strpos( $settings, "'fit_assessment_enabled'                  => 1" ) !== false
		&& strpos( $settings, "'fit_require_human_attestation'           => 1" ) !== false,
	'settings no automation copy'          => strpos( $settings_view, 'No automated decision or status change' ) !== false,
	'queue human boundary copy'            => strpos( $queue_view, 'No automated acceptance, rejection, status change, communication, meeting, proposal, or referral' ) !== false,
	'detail score limitation copy'         => strpos( $detail, 'It has no threshold, does not create the recommendation' ) !== false,
	'assistance disclosure present'        => strpos( $schema, 'assistance_disclosures' ) !== false
		&& strpos( $detail, 'Assistance Disclosure' ) !== false,
	'assistance notes required'            => strpos( $repository, 'fit_assistance_notes_required' ) !== false,
	'granular fit settings handler'        => strpos( $admin, 'handle_settings' ) !== false
		&& strpos( $admin, "sc_intake_manage_fit_settings" ) !== false
		&& strpos( $queue_view, 'Fit Assessment Policy Settings' ) !== false,
	'JavaScript confirms final actions'    => strpos( $javascript, 'FINALIZE ${assessmentId}' ) !== false
		&& strpos( $javascript, 'APPLY ${assessmentId}' ) !== false,
	'JavaScript requires criterion evidence'=> strpos( $javascript, 'evidence.required = Boolean(assessed)' ) !== false,
);

$failed = array_keys( array_filter( $checks, static fn( bool $passed ): bool => ! $passed ) );
if ( $failed ) {
	fwrite( STDERR, 'Fit operation checks failed: ' . implode( ', ', $failed ) . PHP_EOL );
	exit( 1 );
}
foreach ( $checks as $label => $passed ) {
	echo 'PASS: ' . $label . PHP_EOL;
}
echo "Engagement Intake v0.9.0 human-controlled fit operation checks passed.\n";
