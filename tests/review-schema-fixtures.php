<?php
/**
 * Human review schema and timing fixtures without loading WordPress.
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'DAY_IN_SECONDS', 86400 );

$GLOBALS['sc_ei_settings'] = array();

function __( string $text, string $domain = '' ): string {
	return $text;
}
function apply_filters( string $hook, $value ) {
	return $value;
}
function sanitize_key( string $value ): string {
	return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ) ?: '';
}
function absint( $value ): int {
	return abs( (int) $value );
}
function wp_parse_args( $args, $defaults = array() ): array {
	return array_merge( (array) $defaults, (array) $args );
}
function get_option( string $name, $default = false ) {
	return 'sc_ei_settings' === $name ? $GLOBALS['sc_ei_settings'] : $default;
}

require_once dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake/includes/class-sc-ei-review-schema.php';

function fail_review_schema( string $message ): void {
	fwrite( STDERR, $message . PHP_EOL );
	exit( 1 );
}
function pass_review_schema( string $message ): void {
	echo 'PASS: ' . $message . PHP_EOL;
}

if ( count( SC_EI_Review_Schema::stages() ) < 7 ) {
	fail_review_schema( 'Review stages are incomplete.' );
}
pass_review_schema( 'review stage taxonomy present' );

if ( 'normal' !== SC_EI_Review_Schema::sanitize_choice( 'invalid', SC_EI_Review_Schema::priorities(), 'normal' ) ) {
	fail_review_schema( 'Invalid priority did not fall back safely.' );
}
pass_review_schema( 'invalid choices fall back safely' );

$checklist = SC_EI_Review_Schema::sanitize_checklist(
	array(
		'contact_verified'   => 1,
		'purpose_understood' => 1,
		'unknown_key'        => 1,
	)
);
if ( isset( $checklist['unknown_key'] ) || 2 !== array_sum( $checklist ) ) {
	fail_review_schema( 'Checklist allowlist failed.' );
}
pass_review_schema( 'checklist allowlist and values sanitized' );

$progress = SC_EI_Review_Schema::checklist_progress( $checklist );
if ( $progress['done'] !== 2 || $progress['total'] !== count( SC_EI_Review_Schema::checklist_items() ) || $progress['complete'] ) {
	fail_review_schema( 'Checklist progress failed.' );
}
pass_review_schema( 'checklist progress calculated' );

$complete = array_fill_keys( array_keys( SC_EI_Review_Schema::checklist_items() ), 1 );
if ( empty( SC_EI_Review_Schema::checklist_progress( $complete )['complete'] ) ) {
	fail_review_schema( 'Complete checklist not detected.' );
}
pass_review_schema( 'complete checklist detected' );

$GLOBALS['sc_ei_settings'] = array(
	'default_review_due_days'       => 3,
	'high_priority_review_due_days' => 1,
	'low_priority_review_due_days'  => 7,
	'urgent_review_due_hours'       => 4,
);
$now = time();
$urgent = strtotime( SC_EI_Review_Schema::default_due_at( 'urgent' ) . ' UTC' );
$normal = strtotime( SC_EI_Review_Schema::default_due_at( 'normal' ) . ' UTC' );
$low = strtotime( SC_EI_Review_Schema::default_due_at( 'low' ) . ' UTC' );

if ( abs( ( $urgent - $now ) - 4 * HOUR_IN_SECONDS ) > 5 ) {
	fail_review_schema( 'Urgent due window incorrect.' );
}
if ( abs( ( $normal - $now ) - 3 * DAY_IN_SECONDS ) > 5 ) {
	fail_review_schema( 'Normal due window incorrect.' );
}
if ( abs( ( $low - $now ) - 7 * DAY_IN_SECONDS ) > 5 ) {
	fail_review_schema( 'Low due window incorrect.' );
}
pass_review_schema( 'priority due windows calculated' );

$timing = SC_EI_Review_Schema::timing(
	array(
		'created_at'       => gmdate( 'Y-m-d H:i:s', time() - 5 * DAY_IN_SECONDS ),
		'updated_at'       => gmdate( 'Y-m-d H:i:s', time() - 3 * DAY_IN_SECONDS ),
		'last_reviewed_at' => '',
		'review_due_at'    => gmdate( 'Y-m-d H:i:s', time() - 2 * HOUR_IN_SECONDS ),
		'review_stage'     => 'triage',
	),
	array( 'stale_review_days' => 2 )
);
if ( 'overdue' !== $timing['due_state'] || empty( $timing['is_stale'] ) || $timing['age_days'] < 4 ) {
	fail_review_schema( 'Overdue or stale timing state failed.' );
}
pass_review_schema( 'overdue, age, and stale states calculated' );

$completed = SC_EI_Review_Schema::timing(
	array(
		'created_at'       => gmdate( 'Y-m-d H:i:s', time() - 10 * DAY_IN_SECONDS ),
		'updated_at'       => gmdate( 'Y-m-d H:i:s' ),
		'last_reviewed_at' => gmdate( 'Y-m-d H:i:s' ),
		'review_due_at'    => gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ),
		'review_stage'     => 'completed',
	)
);
if ( 'completed' !== $completed['due_state'] ) {
	fail_review_schema( 'Completed review timing state failed.' );
}
pass_review_schema( 'completed review suppresses overdue state' );

echo "Engagement Intake v0.9.1 review schema fixtures passed.\n";
