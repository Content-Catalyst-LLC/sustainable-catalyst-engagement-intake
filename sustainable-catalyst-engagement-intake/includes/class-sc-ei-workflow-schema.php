<?php
/**
 * Teams scheduling and proposal workflow schema.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Workflow_Schema {

	public static function meeting_statuses(): array {
		return array(
			'draft'                  => __( 'Draft', 'sustainable-catalyst-engagement-intake' ),
			'offered'                => __( 'Times Offered', 'sustainable-catalyst-engagement-intake' ),
			'accepted_pending_link'  => __( 'Accepted — Teams Link Pending', 'sustainable-catalyst-engagement-intake' ),
			'scheduled'              => __( 'Scheduled', 'sustainable-catalyst-engagement-intake' ),
			'alternative_requested'  => __( 'Alternative Requested', 'sustainable-catalyst-engagement-intake' ),
			'declined'               => __( 'Declined', 'sustainable-catalyst-engagement-intake' ),
			'completed'              => __( 'Completed', 'sustainable-catalyst-engagement-intake' ),
			'canceled'               => __( 'Canceled', 'sustainable-catalyst-engagement-intake' ),
			'expired'                => __( 'Expired', 'sustainable-catalyst-engagement-intake' ),
			'superseded'             => __( 'Superseded', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function proposal_statuses(): array {
		return array(
			'draft'                     => __( 'Draft', 'sustainable-catalyst-engagement-intake' ),
			'published'                 => __( 'Published to Portal', 'sustainable-catalyst-engagement-intake' ),
			'accepted_pending_contract' => __( 'Accepted — Contract Pending', 'sustainable-catalyst-engagement-intake' ),
			'declined'                  => __( 'Declined', 'sustainable-catalyst-engagement-intake' ),
			'contracted'                => __( 'External Contract Recorded', 'sustainable-catalyst-engagement-intake' ),
			'withdrawn'                 => __( 'Withdrawn', 'sustainable-catalyst-engagement-intake' ),
			'expired'                   => __( 'Expired', 'sustainable-catalyst-engagement-intake' ),
			'superseded'                => __( 'Superseded', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function sender_meeting_responses(): array {
		return array(
			'accept'              => __( 'Accept selected time', 'sustainable-catalyst-engagement-intake' ),
			'alternative_request' => __( 'Request another time', 'sustainable-catalyst-engagement-intake' ),
			'decline'             => __( 'Decline meeting', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function sender_proposal_responses(): array {
		return array(
			'accept'  => __( 'Accept for contracting', 'sustainable-catalyst-engagement-intake' ),
			'decline' => __( 'Decline proposal', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function event_types(): array {
		return array(
			'meeting_draft_created'        => __( 'Meeting Draft Created', 'sustainable-catalyst-engagement-intake' ),
			'meeting_offer_published'      => __( 'Meeting Offer Published', 'sustainable-catalyst-engagement-intake' ),
			'meeting_time_accepted'        => __( 'Meeting Time Accepted', 'sustainable-catalyst-engagement-intake' ),
			'meeting_alternative_requested'=> __( 'Meeting Alternative Requested', 'sustainable-catalyst-engagement-intake' ),
			'meeting_declined'             => __( 'Meeting Declined', 'sustainable-catalyst-engagement-intake' ),
			'meeting_finalized'            => __( 'Meeting Finalized', 'sustainable-catalyst-engagement-intake' ),
			'meeting_completed'            => __( 'Meeting Completed', 'sustainable-catalyst-engagement-intake' ),
			'meeting_canceled'             => __( 'Meeting Canceled', 'sustainable-catalyst-engagement-intake' ),
			'meeting_expired'              => __( 'Meeting Offer Expired', 'sustainable-catalyst-engagement-intake' ),
			'meeting_ics_downloaded'       => __( 'Meeting Calendar File Downloaded', 'sustainable-catalyst-engagement-intake' ),
			'proposal_draft_created'       => __( 'Proposal Draft Created', 'sustainable-catalyst-engagement-intake' ),
			'proposal_version_created'     => __( 'Proposal Version Created', 'sustainable-catalyst-engagement-intake' ),
			'proposal_published'           => __( 'Proposal Published', 'sustainable-catalyst-engagement-intake' ),
			'proposal_accepted'            => __( 'Proposal Accepted for Contracting', 'sustainable-catalyst-engagement-intake' ),
			'proposal_declined'            => __( 'Proposal Declined', 'sustainable-catalyst-engagement-intake' ),
			'proposal_withdrawn'           => __( 'Proposal Withdrawn', 'sustainable-catalyst-engagement-intake' ),
			'proposal_expired'             => __( 'Proposal Expired', 'sustainable-catalyst-engagement-intake' ),
			'proposal_contracted'          => __( 'External Contract Recorded', 'sustainable-catalyst-engagement-intake' ),
			'proposal_print_viewed'        => __( 'Proposal Print View Opened', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function currencies(): array {
		return array(
			'USD' => __( 'USD — US Dollar', 'sustainable-catalyst-engagement-intake' ),
			'EUR' => __( 'EUR — Euro', 'sustainable-catalyst-engagement-intake' ),
			'GBP' => __( 'GBP — British Pound', 'sustainable-catalyst-engagement-intake' ),
			'CAD' => __( 'CAD — Canadian Dollar', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function default_settings(): array {
		return array(
			'workflow_enabled'                  => 1,
			'workflow_meeting_offer_expiry_days'=> 7,
			'workflow_proposal_expiry_days'     => 14,
			'workflow_max_meeting_slots'        => 5,
			'workflow_require_teams_url'        => 0,
			'workflow_allow_sender_ics'         => 1,
			'workflow_allow_proposal_acceptance'=> 1,
			'workflow_require_authority_attestation' => 1,
			'workflow_require_boundary_acknowledgment'=> 1,
			'workflow_no_auto_calendar'         => 1,
			'workflow_no_auto_contract'         => 1,
			'workflow_no_auto_payment'          => 1,
		);
	}

	public static function sanitize_slots( $values, string $timezone, int $duration_minutes, int $limit = 5 ): array {
		if ( ! SC_EI_Teams::valid_timezone( $timezone ) ) {
			return array();
		}
		$duration_minutes = max( 15, min( 240, $duration_minutes ) );
		$slots = array();
		foreach ( array_slice( (array) $values, 0, max( 1, min( 10, $limit ) ) ) as $index => $value ) {
			$utc = SC_EI_Teams::local_to_utc( (string) $value, $timezone );
			if ( ! $utc || strtotime( $utc . ' UTC' ) <= time() ) {
				continue;
			}
			$end = gmdate( 'Y-m-d H:i:s', strtotime( $utc . ' UTC' ) + $duration_minutes * MINUTE_IN_SECONDS );
			$slots[] = array(
				'key'       => 'slot_' . ( count( $slots ) + 1 ),
				'start_utc' => $utc,
				'end_utc'   => $end,
			);
		}
		usort( $slots, static fn( array $a, array $b ): int => strcmp( $a['start_utc'], $b['start_utc'] ) );
		$seen = array();
		return array_values(
			array_filter(
				$slots,
				static function ( array $slot ) use ( &$seen ): bool {
					if ( isset( $seen[ $slot['start_utc'] ] ) ) {
						return false;
					}
					$seen[ $slot['start_utc'] ] = true;
					return true;
				}
			)
		);
	}

	public static function sanitize_list( $value, int $limit = 50 ): array {
		$parts = is_array( $value ) ? $value : preg_split( '/[\r\n]+/', (string) $value );
		$clean = array();
		foreach ( (array) $parts as $part ) {
			$item = sanitize_text_field( trim( (string) $part ) );
			if ( '' !== $item ) {
				$clean[] = mb_substr( $item, 0, 500 );
			}
		}
		return array_slice( array_values( array_unique( $clean ) ), 0, $limit );
	}

	public static function sanitize_currency( string $currency ): string {
		$currency = strtoupper( sanitize_text_field( $currency ) );
		return isset( self::currencies()[ $currency ] ) ? $currency : 'USD';
	}

	public static function money_minor( $value ): int {
		$value = preg_replace( '/[^0-9.\-]/', '', (string) $value );
		if ( '' === $value || ! is_numeric( $value ) ) {
			return 0;
		}
		return max( 0, (int) round( (float) $value * 100 ) );
	}

	public static function money_display( int $minor, string $currency ): string {
		return strtoupper( $currency ) . ' ' . number_format_i18n( $minor / 100, 2 );
	}

	public static function label( array $choices, string $value ): string {
		return $choices[ $value ] ?? $value;
	}
}
