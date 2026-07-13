<?php
/**
 * Human-controlled fit schema fixtures without loading WordPress.
 */

define( 'ABSPATH', __DIR__ . '/' );

function __( string $text, string $domain = '' ): string {
	return $text;
}
function sanitize_key( string $value ): string {
	return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ) ?: '';
}
function apply_filters( string $hook, $value ) {
	return $value;
}
function wp_parse_args( $args, $defaults = array() ): array {
	return array_merge( $defaults, is_array( $args ) ? $args : array() );
}

require_once dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake/includes/class-sc-ei-fit-schema.php';

function fail_fit_schema( string $message ): void {
	fwrite( STDERR, $message . PHP_EOL );
	exit( 1 );
}
function pass_fit_schema( string $message ): void {
	echo 'PASS: ' . $message . PHP_EOL;
}

$criteria = SC_EI_Fit_Schema::criteria();
if ( 16 !== count( $criteria ) ) {
	fail_fit_schema( 'Expected 16 human fit criteria, found ' . count( $criteria ) );
}
pass_fit_schema( 'sixteen evidence-backed human criteria present' );

$groups = SC_EI_Fit_Schema::criterion_groups();
foreach ( $criteria as $key => $criterion ) {
	if ( empty( $groups[ $criterion['group'] ] ) || (float) $criterion['weight'] <= 0 ) {
		fail_fit_schema( 'Invalid criterion group or weight: ' . $key );
	}
}
pass_fit_schema( 'all criteria have explicit groups and transparent positive weights' );

$items = array();
foreach ( $criteria as $key => $criterion ) {
	$items[ $key ] = array(
		'rating'              => 'good',
		'is_applicable'       => 1,
		'is_material_concern' => 0,
	);
}
$score = SC_EI_Fit_Schema::calculate_score( $items );
if ( 75.0 !== (float) $score['score'] || ! $score['score_complete'] || 16 !== $score['assessed_count'] ) {
	fail_fit_schema( 'Transparent advisory score calculation failed: ' . json_encode( $score ) );
}
pass_fit_schema( 'advisory score transparently summarizes human ratings' );

$items['privacy_confidentiality']['rating'] = 'strong_concern';
$items['privacy_confidentiality']['is_material_concern'] = 1;
$score = SC_EI_Fit_Schema::calculate_score( $items );
if ( 1 !== $score['material_concerns'] || $score['score'] >= 75 ) {
	fail_fit_schema( 'Material concern was not counted or reflected in score.' );
}
pass_fit_schema( 'material concern count is independent and visible' );

$settings = SC_EI_Fit_Schema::default_settings();
foreach ( array(
	'fit_require_human_attestation',
	'fit_require_evidence_for_assessed_items',
	'fit_require_rationale_for_finalization',
	'fit_require_second_review_high_risk',
	'fit_require_second_review_conflict',
	'fit_require_second_review_decline',
	'fit_require_second_review_unsafe_scope',
	'fit_distinct_second_reviewer',
) as $key ) {
	if ( empty( $settings[ $key ] ) ) {
		fail_fit_schema( 'Required human-control default disabled: ' . $key );
	}
}
pass_fit_schema( 'human attestation, evidence, rationale, and second-review safeguards default on' );

$reasons = SC_EI_Fit_Schema::second_review_reasons(
	'not_a_fit',
	'conflict_or_independence',
	$items,
	$settings
);
if ( count( $reasons ) < 3 ) {
	fail_fit_schema( 'Expected decline, conflict, and material-risk second-review reasons.' );
}
pass_fit_schema( 'decline, conflict, unsafe scope, and material risk can require second review' );

$mapping = SC_EI_Fit_Schema::map_to_review( 'strong_fit', 'measurement_indicator_design' );
if ( 'strong_fit' !== $mapping['fit_decision'] || 'measurement_indicator_design' !== $mapping['recommended_next_step'] ) {
	fail_fit_schema( 'Explicit Review Workspace mapping failed.' );
}
pass_fit_schema( 'service routes map explicitly without changing inquiry status' );

if ( null !== SC_EI_Fit_Schema::rating_value( 'not_assessed' ) || 4.0 !== (float) SC_EI_Fit_Schema::rating_value( 'strong' ) ) {
	fail_fit_schema( 'Rating values are not transparent or stable.' );
}
pass_fit_schema( 'rating scale is transparent and stable' );

if ( 'undecided' !== SC_EI_Fit_Schema::sanitize_choice( 'invalid', SC_EI_Fit_Schema::recommendations(), 'undecided' ) ) {
	fail_fit_schema( 'Invalid recommendation did not fail safely.' );
}
pass_fit_schema( 'invalid fit choices fail safely' );

echo "Engagement Intake v0.7.0 fit schema fixtures passed.\n";
