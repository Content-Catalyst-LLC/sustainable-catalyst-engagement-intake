<?php
/**
 * Microsoft Teams and calendar-coordination vocabulary and settings.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Calendar_Schema {

	public static function meeting_types(): array {
		return array(
			'advisory_discovery'     => __( 'Advisory Discovery', 'sustainable-catalyst-engagement-intake' ),
			'ai_assurance_review'     => __( 'Sustainable AI Assurance Review', 'sustainable-catalyst-engagement-intake' ),
			'support_troubleshooting' => __( 'Support Troubleshooting', 'sustainable-catalyst-engagement-intake' ),
			'research_collaboration'  => __( 'Research Collaboration', 'sustainable-catalyst-engagement-intake' ),
			'institutional'           => __( 'Institutional Discussion', 'sustainable-catalyst-engagement-intake' ),
			'media_interview'         => __( 'Media or Interview', 'sustainable-catalyst-engagement-intake' ),
			'workshop_planning'       => __( 'Workshop Planning', 'sustainable-catalyst-engagement-intake' ),
			'proposal_review'         => __( 'Proposal Review', 'sustainable-catalyst-engagement-intake' ),
			'engagement_review'       => __( 'Engagement Review', 'sustainable-catalyst-engagement-intake' ),
			'project_closeout'        => __( 'Project Closeout', 'sustainable-catalyst-engagement-intake' ),
			'other'                   => __( 'Other Coordinated Meeting', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function providers(): array {
		return array(
			'manual'          => __( 'Manual Calendar Coordination', 'sustainable-catalyst-engagement-intake' ),
			'microsoft_graph' => __( 'Microsoft Graph', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function reminder_types(): array {
		return array(
			'invitation'       => __( 'Meeting Confirmation', 'sustainable-catalyst-engagement-intake' ),
			'twenty_four_hour' => __( '24-Hour Reminder', 'sustainable-catalyst-engagement-intake' ),
			'one_hour'         => __( 'One-Hour Reminder', 'sustainable-catalyst-engagement-intake' ),
			'rescheduled'      => __( 'Rescheduled Meeting Notice', 'sustainable-catalyst-engagement-intake' ),
			'canceled'         => __( 'Cancellation Notice', 'sustainable-catalyst-engagement-intake' ),
			'post_meeting'     => __( 'Post-Meeting Follow-Up', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function reminder_statuses(): array {
		return array(
			'pending'          => __( 'Pending', 'sustainable-catalyst-engagement-intake' ),
			'ready_for_review' => __( 'Ready for Review', 'sustainable-catalyst-engagement-intake' ),
			'sent'             => __( 'Sent', 'sustainable-catalyst-engagement-intake' ),
			'canceled'         => __( 'Canceled', 'sustainable-catalyst-engagement-intake' ),
			'failed'           => __( 'Failed', 'sustainable-catalyst-engagement-intake' ),
			'skipped'          => __( 'Skipped', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function default_settings(): array {
		return array(
			'calendar_coordination_enabled'       => 1,
			'calendar_default_timezone'           => wp_timezone_string() ?: 'America/Chicago',
			'calendar_create_confirmation_record' => 1,
			'calendar_create_24_hour_reminder'    => 1,
			'calendar_create_1_hour_reminder'     => 1,
			'calendar_sender_portal_enabled'      => 1,
			'calendar_auto_send_reminders'        => 0,
			'calendar_followup_default_days'      => 2,
			'calendar_no_public_booking'           => 1,
			'calendar_require_explicit_timezone'  => 1,
			'calendar_require_teams_url_host'      => 1,
		);
	}

	public static function sanitize_meeting_type( string $value ): string {
		$value = sanitize_key( $value );
		return isset( self::meeting_types()[ $value ] ) ? $value : 'other';
	}

	public static function sanitize_provider( string $value ): string {
		$value = sanitize_key( $value );
		return isset( self::providers()[ $value ] ) ? $value : 'manual';
	}

	public static function sanitize_reminder_status( string $value ): string {
		$value = sanitize_key( $value );
		return isset( self::reminder_statuses()[ $value ] ) ? $value : 'pending';
	}

	public static function sanitize_document_ids( $value ): array {
		$items = is_array( $value ) ? $value : preg_split( '/[\s,;]+/', (string) $value );
		$result = array();
		foreach ( (array) $items as $item ) {
			$id = absint( $item );
			if ( $id > 0 ) {
				$result[ $id ] = $id;
			}
		}
		return array_slice( array_values( $result ), 0, 100 );
	}

	public static function label( array $choices, string $value ): string {
		return $choices[ $value ] ?? ucwords( str_replace( '_', ' ', $value ) );
	}
}
