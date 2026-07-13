<?php
/**
 * Microsoft Teams communication and scheduling helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Teams {

	public static function contact_methods(): array {
		return apply_filters(
			'sc_ei_contact_methods',
			array(
				'email'         => __( 'Email', 'sustainable-catalyst-engagement-intake' ),
				'teams'         => __( 'Microsoft Teams', 'sustainable-catalyst-engagement-intake' ),
				'phone'         => __( 'Phone', 'sustainable-catalyst-engagement-intake' ),
				'no_preference' => __( 'No preference', 'sustainable-catalyst-engagement-intake' ),
			)
		);
	}

	public static function meeting_requests(): array {
		return array(
			'no'     => __( 'No meeting requested', 'sustainable-catalyst-engagement-intake' ),
			'yes'    => __( 'Yes, request a Microsoft Teams meeting', 'sustainable-catalyst-engagement-intake' ),
			'unsure' => __( 'Unsure — recommend the best next step', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function scheduling_statuses(): array {
		return apply_filters(
			'sc_ei_scheduling_statuses',
			array(
				'not_requested'  => __( 'Not Requested', 'sustainable-catalyst-engagement-intake' ),
				'requested'      => __( 'Requested', 'sustainable-catalyst-engagement-intake' ),
				'under_review'   => __( 'Under Review', 'sustainable-catalyst-engagement-intake' ),
				'approved'       => __( 'Approved', 'sustainable-catalyst-engagement-intake' ),
				'times_proposed' => __( 'Times Proposed', 'sustainable-catalyst-engagement-intake' ),
				'scheduled'      => __( 'Scheduled', 'sustainable-catalyst-engagement-intake' ),
				'completed'      => __( 'Completed', 'sustainable-catalyst-engagement-intake' ),
				'declined'       => __( 'Declined', 'sustainable-catalyst-engagement-intake' ),
				'cancelled'      => __( 'Cancelled', 'sustainable-catalyst-engagement-intake' ),
			)
		);
	}

	public static function duration_options(): array {
		return array(
			'20' => __( '20-minute initial fit call', 'sustainable-catalyst-engagement-intake' ),
			'30' => __( '30-minute conversation', 'sustainable-catalyst-engagement-intake' ),
			'45' => __( '45-minute working discussion', 'sustainable-catalyst-engagement-intake' ),
			'60' => __( '60-minute meeting', 'sustainable-catalyst-engagement-intake' ),
			'90' => __( '90-minute paid consultation', 'sustainable-catalyst-engagement-intake' ),
			'0'  => __( 'Not sure', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function weekdays(): array {
		return array(
			'monday'    => __( 'Monday', 'sustainable-catalyst-engagement-intake' ),
			'tuesday'   => __( 'Tuesday', 'sustainable-catalyst-engagement-intake' ),
			'wednesday' => __( 'Wednesday', 'sustainable-catalyst-engagement-intake' ),
			'thursday'  => __( 'Thursday', 'sustainable-catalyst-engagement-intake' ),
			'friday'    => __( 'Friday', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function timezone_identifiers(): array {
		return DateTimeZone::listIdentifiers();
	}

	public static function valid_timezone( string $timezone ): bool {
		return in_array( $timezone, self::timezone_identifiers(), true );
	}

	public static function sanitize_weekdays( $values ): array {
		$allowed = self::weekdays();
		$clean   = array();

		foreach ( (array) $values as $value ) {
			$key = sanitize_key( (string) $value );
			if ( array_key_exists( $key, $allowed ) ) {
				$clean[] = $key;
			}
		}

		return array_values( array_unique( $clean ) );
	}

	public static function sanitize_participant_emails( $value ): array {
		if ( is_array( $value ) ) {
			$parts = $value;
		} else {
			$parts = preg_split( '/[\r\n,;]+/', (string) $value );
		}

		$emails = array();
		foreach ( (array) $parts as $part ) {
			$email = sanitize_email( trim( (string) $part ) );
			if ( $email && is_email( $email ) ) {
				$emails[] = strtolower( $email );
			}
		}

		return array_slice( array_values( array_unique( $emails ) ), 0, 25 );
	}

	public static function is_teams_url( string $url ): bool {
		$url  = esc_url_raw( $url );
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );

		if ( '' === $host ) {
			return false;
		}

		$allowed = apply_filters(
			'sc_ei_teams_url_hosts',
			array(
				'teams.microsoft.com',
				'teams.live.com',
				'teams.cloud.microsoft',
			)
		);

		foreach ( $allowed as $allowed_host ) {
			$allowed_host = strtolower( trim( (string) $allowed_host ) );
			if ( $host === $allowed_host || str_ends_with( $host, '.' . $allowed_host ) ) {
				return true;
			}
		}

		return false;
	}

	public static function local_to_utc( string $value, string $timezone ): ?string {
		$value    = sanitize_text_field( $value );
		$timezone = sanitize_text_field( $timezone );

		if ( '' === $value || ! self::valid_timezone( $timezone ) ) {
			return null;
		}

		try {
			$local = new DateTimeImmutable( $value, new DateTimeZone( $timezone ) );
			return $local->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
		} catch ( Throwable $exception ) {
			return null;
		}
	}

	public static function format_utc_for_input( ?string $value, string $timezone ): string {
		if ( empty( $value ) || ! self::valid_timezone( $timezone ) ) {
			return '';
		}

		try {
			$utc = new DateTimeImmutable( $value, new DateTimeZone( 'UTC' ) );
			return $utc->setTimezone( new DateTimeZone( $timezone ) )->format( 'Y-m-d\TH:i' );
		} catch ( Throwable $exception ) {
			return '';
		}
	}

	public static function label( array $choices, string $value ): string {
		return $choices[ $value ] ?? $value;
	}
}
