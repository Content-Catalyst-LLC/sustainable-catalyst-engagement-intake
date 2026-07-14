<?php
/**
 * Communication taxonomy and sanitization fixtures without loading WordPress.
 */

define( 'ABSPATH', __DIR__ . '/' );

function __( string $text, string $domain = '' ): string {
	return $text;
}
function sanitize_key( string $value ): string {
	return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ) ?: '';
}
function sanitize_email( string $value ): string {
	return filter_var( trim( $value ), FILTER_SANITIZE_EMAIL ) ?: '';
}
function is_email( string $value ): bool {
	return false !== filter_var( $value, FILTER_VALIDATE_EMAIL );
}
function sanitize_text_field( string $value ): string {
	return trim( strip_tags( preg_replace( '/[\r\n\t]+/', ' ', $value ) ) );
}
function sanitize_textarea_field( string $value ): string {
	return trim( strip_tags( str_replace( "\0", '', $value ) ) );
}

require_once dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake/includes/class-sc-ei-communication-schema.php';

function fail_communication_schema( string $message ): void {
	fwrite( STDERR, $message . PHP_EOL );
	exit( 1 );
}
function pass_communication_schema( string $message ): void {
	echo 'PASS: ' . $message . PHP_EOL;
}

if ( ! isset( SC_EI_Communication_Schema::statuses()['accepted'] ) ) {
	fail_communication_schema( 'Accepted transport state missing.' );
}
if ( str_contains( strtolower( SC_EI_Communication_Schema::statuses()['accepted'] ), 'delivered' ) ) {
	fail_communication_schema( 'Accepted state overclaims delivery.' );
}
pass_communication_schema( 'transport acceptance is distinct from delivery' );

$channels = SC_EI_Communication_Schema::channels();
if ( ! isset( $channels['teams_message'], $channels['teams_meeting'] ) || isset( $channels['zoom'], $channels['google_meet'] ) ) {
	fail_communication_schema( 'Teams-only channel boundary failed.' );
}
pass_communication_schema( 'Teams channels present without Zoom or Google Meet' );

$emails = SC_EI_Communication_Schema::sanitize_emails(
	'A@example.com, invalid, a@example.com; b@example.org c@example.net',
	2
);
if ( array( 'a@example.com', 'b@example.org' ) !== $emails ) {
	fail_communication_schema( 'Email sanitization, deduplication, or limit failed: ' . json_encode( $emails ) );
}
pass_communication_schema( 'email recipients sanitized, deduplicated, and limited' );

$subject = SC_EI_Communication_Schema::sanitize_subject( "Hello\r\nBcc: attacker@example.com" );
if ( str_contains( $subject, "\r" ) || str_contains( $subject, "\n" ) || strlen( $subject ) > 255 ) {
	fail_communication_schema( 'Subject header injection protection failed.' );
}
pass_communication_schema( 'subject line strips header-breaking newlines' );

$body = SC_EI_Communication_Schema::sanitize_body( str_repeat( 'x', 51000 ) );
if ( strlen( $body ) !== 50000 ) {
	fail_communication_schema( 'Body length cap failed.' );
}
pass_communication_schema( 'message body capped at 50000 characters' );

if ( 'normal' !== SC_EI_Communication_Schema::sanitize_choice( 'invalid', array( 'normal' => 'Normal' ), 'normal' ) ) {
	fail_communication_schema( 'Choice fallback failed.' );
}
pass_communication_schema( 'invalid communication choices fall back safely' );

$thread = SC_EI_Communication_Schema::thread_key( array( 'reference' => 'SC-20260713-A1B2C3' ) );
if ( 'sc-ei-sc-20260713-a1b2c3' !== $thread ) {
	fail_communication_schema( 'Thread key normalization failed: ' . $thread );
}
pass_communication_schema( 'stable inquiry thread key generated' );

$templates = SC_EI_Communication_Schema::default_templates();
if ( count( $templates ) < 10 || ! isset( $templates['acknowledgment'], $templates['internal_review_due'], $templates['teams_confirmation'] ) ) {
	fail_communication_schema( 'Default communication templates are incomplete.' );
}
pass_communication_schema( 'default sender and internal templates present' );

$allowed = array_keys( SC_EI_Communication_Schema::template_variables() );
foreach ( $templates as $key => $template ) {
	preg_match_all( '/\{[a-z0-9_]+\}/i', $template['subject'] . "\n" . $template['body'], $matches );
	$unknown = array_diff( array_unique( $matches[0] ), $allowed );
	if ( $unknown ) {
		fail_communication_schema( 'Unknown variables in ' . $key . ': ' . implode( ', ', $unknown ) );
	}
}
pass_communication_schema( 'all default template variables are allowlisted' );

foreach ( $templates as $template ) {
	if ( str_contains( $template['body'], '<script' ) || str_contains( $template['body'], '<img' ) ) {
		fail_communication_schema( 'HTML content appeared in plain-text templates.' );
	}
}
pass_communication_schema( 'default templates remain plain text' );

echo "Engagement Intake v1.0.0 communication schema fixtures passed.\n";
