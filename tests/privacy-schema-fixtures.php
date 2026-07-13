<?php
/**
 * Privacy schema fixtures without loading WordPress.
 */

define( 'ABSPATH', __DIR__ . '/' );

function __( string $text, string $domain = '' ): string {
	return $text;
}
function sanitize_key( string $value ): string {
	return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ) ?: '';
}
function absint( $value ): int {
	return abs( (int) $value );
}

final class SC_EI_Statuses {
	public static function all(): array {
		return array(
			'new'                      => 'New',
			'under_review'             => 'Under Review',
			'more_information_needed'  => 'More Information Needed',
			'fit_call_recommended'     => 'Fit Call Recommended',
			'consultation_recommended' => 'Consultation Recommended',
			'proposal_requested'       => 'Proposal Requested',
			'proposal_sent'            => 'Proposal Sent',
			'accepted'                 => 'Accepted',
			'not_a_fit'                => 'Not a Fit',
			'referred'                 => 'Referred',
			'withdrawn'                => 'Withdrawn',
			'closed'                   => 'Closed',
		);
	}
}

require_once dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake/includes/class-sc-ei-privacy-schema.php';

function fail_privacy_schema( string $message ): void {
	fwrite( STDERR, $message . PHP_EOL );
	exit( 1 );
}
function pass_privacy_schema( string $message ): void {
	echo 'PASS: ' . $message . PHP_EOL;
}

$settings = SC_EI_Privacy_Schema::default_settings();
foreach ( array(
	'default_unaccepted_retention_days',
	'withdrawn_retention_days',
	'closed_retention_days',
	'accepted_retention_days',
	'communication_retention_days',
	'attachment_retention_days',
	'privacy_request_due_days',
	'retention_queue_batch_limit',
	'retention_execution_batch_limit',
	'require_retention_approval',
	'retention_cron_queue_only',
	'retain_tombstones',
	'legal_hold_review_days',
) as $key ) {
	if ( ! array_key_exists( $key, $settings ) ) {
		fail_privacy_schema( 'Missing privacy setting: ' . $key );
	}
}
pass_privacy_schema( 'complete privacy lifecycle settings present' );

if ( 1 !== $settings['retention_cron_queue_only'] || 1 !== $settings['retain_tombstones'] || 1 !== $settings['require_retention_approval'] ) {
	fail_privacy_schema( 'Fixed safety defaults are not enabled.' );
}
pass_privacy_schema( 'queue-only cron, tombstones, and approval default enabled' );

$policies = SC_EI_Privacy_Schema::default_policies();
foreach ( array(
	'unaccepted_inquiry',
	'withdrawn_inquiry',
	'closed_inquiry',
	'accepted_inquiry',
	'private_attachment',
	'communication_content',
) as $key ) {
	if ( empty( $policies[ $key ] ) ) {
		fail_privacy_schema( 'Missing default policy: ' . $key );
	}
}
pass_privacy_schema( 'six lifecycle policy families present' );

if ( 'archive_only' !== $policies['accepted_inquiry']['action_type'] ) {
	fail_privacy_schema( 'Accepted inquiry policy should archive by default.' );
}
if ( 'delete_attachment' !== $policies['private_attachment']['action_type'] ) {
	fail_privacy_schema( 'Private attachment policy should queue verified deletion.' );
}
if ( 'retention_until' !== $policies['private_attachment']['anchor_field'] ) {
	fail_privacy_schema( 'Private attachment policy must use explicit retention date.' );
}
pass_privacy_schema( 'conservative accepted and private-document actions configured' );

$scope = SC_EI_Privacy_Schema::sanitize_status_scope(
	array( 'new', 'withdrawn', 'invalid', 'new', 'ACCEPTED' )
);
if ( array( 'new', 'withdrawn', 'accepted' ) !== $scope ) {
	fail_privacy_schema( 'Status scope allowlist failed: ' . json_encode( $scope ) );
}
pass_privacy_schema( 'inquiry status scopes are normalized and allowlisted' );

if ( 'active' !== SC_EI_Privacy_Schema::sanitize_choice( 'invalid', SC_EI_Privacy_Schema::privacy_statuses(), 'active' ) ) {
	fail_privacy_schema( 'Invalid privacy state did not fall back safely.' );
}
pass_privacy_schema( 'invalid privacy choices fall back safely' );

if ( ! isset(
	SC_EI_Privacy_Schema::request_types()['erasure'],
	SC_EI_Privacy_Schema::consent_actions()['withdrawn'],
	SC_EI_Privacy_Schema::hold_scopes()['attachment'],
	SC_EI_Privacy_Schema::retention_action_statuses()['blocked_hold'],
	SC_EI_Privacy_Schema::retention_action_statuses()['executed']
) ) {
	fail_privacy_schema( 'Privacy taxonomies are incomplete.' );
}
pass_privacy_schema( 'requests, consent, holds, and action-state taxonomies present' );

echo "Engagement Intake v0.9.1 privacy schema fixtures passed.\n";
