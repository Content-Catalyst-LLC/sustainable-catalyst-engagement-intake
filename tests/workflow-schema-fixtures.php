<?php
define( 'ABSPATH', __DIR__ . '/' );
define( 'MINUTE_IN_SECONDS', 60 );

function __( string $text, string $domain = '' ): string { return $text; }
function sanitize_key( string $value ): string { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ) ?: ''; }
function sanitize_text_field( string $value ): string { return trim( strip_tags( $value ) ); }
if ( ! function_exists( 'mb_substr' ) ) { function mb_substr( string $value, int $start, ?int $length = null ): string { return null === $length ? substr( $value, $start ) : substr( $value, $start, $length ); } }
function number_format_i18n( $number, int $decimals = 0 ): string { return number_format( (float) $number, $decimals ); }

final class SC_EI_Teams {
	public static function valid_timezone( string $timezone ): bool {
		return in_array( $timezone, timezone_identifiers_list(), true );
	}
	public static function local_to_utc( string $value, string $timezone ): ?string {
		try {
			$date = new DateTimeImmutable( $value, new DateTimeZone( $timezone ) );
			return $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
		} catch ( Throwable $error ) {
			return null;
		}
	}
}

require_once dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake/includes/class-sc-ei-workflow-schema.php';

function fail_workflow( string $message ): void { fwrite( STDERR, $message . PHP_EOL ); exit( 1 ); }
function pass_workflow( string $message ): void { echo 'PASS: ' . $message . PHP_EOL; }

foreach ( array(
	'draft', 'offered', 'accepted_pending_link', 'scheduled',
	'alternative_requested', 'declined', 'completed', 'canceled', 'expired', 'superseded',
) as $status ) {
	if ( ! isset( SC_EI_Workflow_Schema::meeting_statuses()[ $status ] ) ) {
		fail_workflow( 'Missing meeting status: ' . $status );
	}
}
pass_workflow( 'complete Microsoft Teams meeting-offer lifecycle present' );

foreach ( array(
	'draft', 'published', 'accepted_pending_contract', 'declined',
	'contracted', 'withdrawn', 'expired', 'superseded',
) as $status ) {
	if ( ! isset( SC_EI_Workflow_Schema::proposal_statuses()[ $status ] ) ) {
		fail_workflow( 'Missing proposal status: ' . $status );
	}
}
pass_workflow( 'complete proposal and external-contract lifecycle present' );

foreach ( array( 'accept', 'alternative_request', 'decline' ) as $response ) {
	if ( ! isset( SC_EI_Workflow_Schema::sender_meeting_responses()[ $response ] ) ) {
		fail_workflow( 'Missing sender meeting response: ' . $response );
	}
}
pass_workflow( 'sender meeting choices are explicit and limited' );

foreach ( array( 'accept', 'decline' ) as $response ) {
	if ( ! isset( SC_EI_Workflow_Schema::sender_proposal_responses()[ $response ] ) ) {
		fail_workflow( 'Missing sender proposal response: ' . $response );
	}
}
pass_workflow( 'sender proposal choices are explicit and limited' );

$settings = SC_EI_Workflow_Schema::default_settings();
foreach ( array(
	'workflow_enabled',
	'workflow_meeting_offer_expiry_days',
	'workflow_proposal_expiry_days',
	'workflow_max_meeting_slots',
	'workflow_allow_sender_ics',
	'workflow_allow_proposal_acceptance',
	'workflow_require_authority_attestation',
	'workflow_require_boundary_acknowledgment',
	'workflow_no_auto_calendar',
	'workflow_no_auto_contract',
	'workflow_no_auto_payment',
) as $key ) {
	if ( ! array_key_exists( $key, $settings ) ) {
		fail_workflow( 'Missing workflow setting: ' . $key );
	}
}
pass_workflow( 'workflow settings include all human-control boundaries' );

if (
	1 !== $settings['workflow_require_authority_attestation']
	|| 1 !== $settings['workflow_require_boundary_acknowledgment']
	|| 1 !== $settings['workflow_no_auto_calendar']
	|| 1 !== $settings['workflow_no_auto_contract']
	|| 1 !== $settings['workflow_no_auto_payment']
) {
	fail_workflow( 'Fixed workflow safeguards are not enabled.' );
}
pass_workflow( 'authority, boundary, no-calendar, no-contract, and no-payment safeguards fixed on' );

$future_local = ( new DateTimeImmutable( '+2 days', new DateTimeZone( 'America/Chicago' ) ) )->format( 'Y-m-d H:i:s' );
$slots = SC_EI_Workflow_Schema::sanitize_slots(
	array( $future_local, $future_local, 'not-a-date' ),
	'America/Chicago',
	30,
	5
);
if ( 1 !== count( $slots ) || 'slot_1' !== $slots[0]['key'] || empty( $slots[0]['start_utc'] ) || empty( $slots[0]['end_utc'] ) ) {
	fail_workflow( 'Slot validation, deduplication, or UTC conversion failed.' );
}
pass_workflow( 'meeting slots validate, deduplicate, and convert to UTC' );

$list = SC_EI_Workflow_Schema::sanitize_list( "First\nSecond\nFirst\n" );
if ( array( 'First', 'Second' ) !== $list ) {
	fail_workflow( 'Proposal list sanitization failed.' );
}
pass_workflow( 'proposal list content is normalized and deduplicated' );

if ( 12345 !== SC_EI_Workflow_Schema::money_minor( '123.45' ) ) {
	fail_workflow( 'Money conversion failed.' );
}
if ( 'USD' !== SC_EI_Workflow_Schema::sanitize_currency( 'xyz' ) ) {
	fail_workflow( 'Currency fallback failed.' );
}
pass_workflow( 'proposal currency and minor-unit value handling are deterministic' );

foreach ( array(
	'meeting_offer_published',
	'meeting_time_accepted',
	'meeting_finalized',
	'meeting_ics_downloaded',
	'proposal_version_created',
	'proposal_published',
	'proposal_accepted',
	'proposal_contracted',
	'proposal_print_viewed',
) as $event ) {
	if ( ! isset( SC_EI_Workflow_Schema::event_types()[ $event ] ) ) {
		fail_workflow( 'Missing workflow event: ' . $event );
	}
}
pass_workflow( 'scheduling, proposal, portal, and contract events are auditable' );

echo "Engagement Intake v0.9.0 workflow schema fixtures passed.\n";
