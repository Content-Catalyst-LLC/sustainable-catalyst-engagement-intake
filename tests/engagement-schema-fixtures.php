<?php
define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );

function __( string $text, string $domain = '' ): string { return $text; }
function sanitize_key( string $value ): string { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ) ?: ''; }
function sanitize_text_field( string $value ): string { return trim( strip_tags( $value ) ); }
function absint( $value ): int { return abs( (int) $value ); }
function wp_parse_args( $args, array $defaults = array() ): array { return array_merge( $defaults, is_array( $args ) ? $args : array() ); }
if ( ! function_exists( 'mb_substr' ) ) {
	function mb_substr( string $value, int $start, ?int $length = null ): string {
		return null === $length ? substr( $value, $start ) : substr( $value, $start, $length );
	}
}

require_once dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake/includes/class-sc-ei-engagement-schema.php';

function fail_engagement_schema( string $message ): void { fwrite( STDERR, $message . PHP_EOL ); exit( 1 ); }
function pass_engagement_schema( string $message ): void { echo 'PASS: ' . $message . PHP_EOL; }

foreach ( array( 'handoff_pending', 'ready_for_setup', 'active', 'paused', 'completed', 'canceled' ) as $status ) {
	if ( ! isset( SC_EI_Engagement_Schema::statuses()[ $status ] ) ) fail_engagement_schema( 'Missing engagement status: ' . $status );
}
pass_engagement_schema( 'complete engagement lifecycle taxonomy present' );

foreach ( array( 'pending', 'in_progress', 'complete', 'waived', 'blocked' ) as $status ) {
	if ( ! isset( SC_EI_Engagement_Schema::requirement_statuses()[ $status ] ) ) fail_engagement_schema( 'Missing requirement status: ' . $status );
}
pass_engagement_schema( 'complete onboarding requirement taxonomy present' );

$settings = SC_EI_Engagement_Schema::default_settings();
foreach ( array(
	'engagement_require_all_required_items', 'engagement_require_owner',
	'engagement_require_contract_reference', 'engagement_require_snapshot_hash',
	'engagement_no_auto_activation', 'engagement_no_auto_provisioning',
	'engagement_no_auto_invoice', 'engagement_no_auto_payment',
	'engagement_no_auto_signature',
) as $key ) {
	if ( 1 !== absint( $settings[ $key ] ?? 0 ) ) fail_engagement_schema( 'Fixed safeguard disabled: ' . $key );
}
pass_engagement_schema( 'readiness and no-automation safeguards default on' );

$engagement = array( 'contract_reference' => 'AGR-100', 'owner_user_id' => 42 );
$requirements = SC_EI_Engagement_Schema::default_requirements( $engagement );
if ( count( $requirements ) < 6 ) fail_engagement_schema( 'Default requirement set is incomplete.' );
$keys = array_column( $requirements, 'requirement_key' );
foreach ( array( 'contract_reference_verified', 'proposal_snapshot_verified', 'engagement_owner_confirmed', 'kickoff_plan_confirmed', 'access_and_data_reviewed', 'delivery_workspace_reviewed' ) as $key ) {
	if ( ! in_array( $key, $keys, true ) ) fail_engagement_schema( 'Missing default requirement: ' . $key );
}
pass_engagement_schema( 'default handoff readiness checklist is complete' );

if ( 'handoff_pending' !== SC_EI_Engagement_Schema::sanitize_status( 'unknown' ) ) fail_engagement_schema( 'Unknown engagement status did not fail closed.' );
if ( 'pending' !== SC_EI_Engagement_Schema::sanitize_requirement_status( 'unknown' ) ) fail_engagement_schema( 'Unknown requirement status did not fail closed.' );
pass_engagement_schema( 'unknown lifecycle inputs fail closed' );

$users = SC_EI_Engagement_Schema::sanitize_user_ids( array( '7', 7, 9, 0, -4 ) );
if ( array( 7, 9, 4 ) !== $users ) fail_engagement_schema( 'User ID sanitization or deduplication failed.' );
$lines = SC_EI_Engagement_Schema::sanitize_lines( "One\nTwo\nOne\n" );
if ( array( 'One', 'Two' ) !== $lines ) fail_engagement_schema( 'Line sanitization failed.' );
pass_engagement_schema( 'participant and list sanitizers are deterministic' );

foreach ( array( 'engagement_handoff_created', 'engagement_snapshot_created', 'engagement_ready', 'engagement_activated', 'engagement_paused', 'engagement_resumed', 'engagement_completed', 'engagement_canceled', 'engagement_privacy_redacted' ) as $event ) {
	if ( ! isset( SC_EI_Engagement_Schema::event_types()[ $event ] ) ) fail_engagement_schema( 'Missing event type: ' . $event );
}
pass_engagement_schema( 'engagement lifecycle and governance events are auditable' );

echo "Engagement Intake v0.9.2 engagement schema fixtures passed.\n";
