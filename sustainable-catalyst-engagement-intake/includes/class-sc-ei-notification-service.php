<?php
/**
 * Opt-in sender acknowledgments and internal operational reminders.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Notification_Service {

	public const CRON_HOOK = 'sc_ei_notification_reminders';

	public static function register(): void {
		add_action( 'sc_ei_public_inquiry_created', array( __CLASS__, 'new_inquiry' ), 20, 2 );
		add_action( 'sc_ei_review_saved', array( __CLASS__, 'review_saved' ), 20, 3 );
		add_action( self::CRON_HOOK, array( __CLASS__, 'run_reminders' ) );
	}

	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', self::CRON_HOOK );
		}
	}

	public static function unschedule(): void {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		while ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
			$timestamp = wp_next_scheduled( self::CRON_HOOK );
		}
	}

	public static function new_inquiry( array $inquiry, array $raw = array() ): void {
		$settings = self::settings();

		if ( ! empty( $settings['sender_acknowledgment_enabled'] ) && empty( $inquiry['do_not_email'] ) ) {
			self::create_and_send(
				absint( $inquiry['id'] ),
				'acknowledgment',
				(string) $inquiry['contact_name'],
				(string) $inquiry['contact_email'],
				'external-ack-' . absint( $inquiry['id'] ),
				array(
					'audience' => 'external_sender',
					'trigger'  => 'new_inquiry',
				)
			);
		}

		if ( ! empty( $settings['internal_new_inquiry_enabled'] ) ) {
			foreach ( self::internal_recipients( $settings ) as $email ) {
				self::create_and_send(
					absint( $inquiry['id'] ),
					'internal_new_inquiry',
					__( 'Engagement Intake Reviewer', 'sustainable-catalyst-engagement-intake' ),
					$email,
					'internal-new-' . absint( $inquiry['id'] ) . '-' . substr( hash( 'sha256', strtolower( $email ) ), 0, 16 ),
					array(
						'audience' => 'internal',
						'trigger'  => 'new_inquiry',
					)
				);
			}
		}
	}

	public static function review_saved( array $fresh, array $previous, int $actor_user_id ): void {
		$settings = self::settings();
		$new_escalation = (string) ( $fresh['escalation_status'] ?? 'none' );
		$old_escalation = (string) ( $previous['escalation_status'] ?? 'none' );

		if (
			! empty( $settings['escalation_notifications_enabled'] )
			&& in_array( $new_escalation, array( 'requested', 'under_review' ), true )
			&& $new_escalation !== $old_escalation
		) {
			$recipients = self::escalation_recipients( $settings );
			foreach ( $recipients as $email ) {
				self::create_and_send(
					absint( $fresh['id'] ),
					'internal_escalation',
					__( 'Engagement Intake Manager', 'sustainable-catalyst-engagement-intake' ),
					$email,
					'internal-escalation-' . absint( $fresh['id'] ) . '-' . absint( $fresh['review_version'] ) . '-' . substr( hash( 'sha256', strtolower( $email ) ), 0, 16 ),
					array(
						'audience'       => 'internal',
						'trigger'        => 'review_escalation',
						'review_version' => absint( $fresh['review_version'] ),
						'actor_user_id'  => $actor_user_id,
					)
				);
			}
		}
	}

	public static function run_reminders(): void {
		$settings = self::settings();
		if (
			empty( $settings['review_due_reminders_enabled'] )
			&& empty( $settings['follow_up_reminders_enabled'] )
		) {
			return;
		}

		if ( ! self::acquire_cron_lock() ) {
			return;
		}

		try {
			if ( ! empty( $settings['review_due_reminders_enabled'] ) ) {
				self::run_review_due_reminders( $settings );
			}
			if ( ! empty( $settings['follow_up_reminders_enabled'] ) ) {
				self::run_follow_up_reminders( $settings );
			}
			update_option( 'sc_ei_last_notification_reminder_run', current_time( 'mysql', true ), false );
		} finally {
			delete_option( 'sc_ei_notification_cron_lock' );
		}
	}

	public static function test_notification( string $recipient, int $actor_user_id ) {
		$recipient = sanitize_email( $recipient );
		if ( ! is_email( $recipient ) ) {
			return new WP_Error( 'notification_test_recipient_invalid', __( 'Enter a valid test recipient email.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$settings = self::settings();
		$sender_name = trim( (string) ( $settings['communication_sender_name'] ?? '' ) );
		$sender_email = sanitize_email( (string) ( $settings['communication_sender_email'] ?? '' ) );
		$reply_email = sanitize_email( (string) ( $settings['communication_reply_to_email'] ?? '' ) );
		if ( '' === $sender_name || ! is_email( $sender_email ) || ! is_email( $reply_email ) ) {
			return new WP_Error(
				'notification_sender_invalid',
				__( 'Configure a valid sender name, sender email, and reply-to email before testing the mail transport.', 'sustainable-catalyst-engagement-intake' )
			);
		}

		$subject = sprintf(
			__( 'Engagement Intake v%s notification test', 'sustainable-catalyst-engagement-intake' ),
			SC_EI_VERSION
		);
		$body = __(
			"This is a plain-text Engagement Intake notification transport test.\n\nA successful result means WordPress accepted the message for its configured mail transport. It does not independently prove inbox delivery.\n\nNo inquiry data or private document content is included.",
			'sustainable-catalyst-engagement-intake'
		);

		$headers = array(
			'Content-Type: text/plain; charset=UTF-8',
			sprintf(
				'From: %s <%s>',
				preg_replace( '/[\r\n<>]+/', '', $sender_name ),
				$sender_email
			),
			sprintf(
				'Reply-To: %s',
				$reply_email
			),
		);

		$error = null;
		$callback = static function( WP_Error $mail_error ) use ( &$error ): void {
			$error = $mail_error;
		};
		add_action( 'wp_mail_failed', $callback );
		$accepted = wp_mail( $recipient, $subject, $body, $headers );
		remove_action( 'wp_mail_failed', $callback );

		SC_EI_Audit_Log::record(
			'notification_transport_tested',
			$accepted
				? 'Notification test accepted by the WordPress mail transport.'
				: 'Notification test failed at the WordPress mail transport.',
			array(
				'recipient_domain' => self::email_domain( $recipient ),
				'accepted'         => $accepted,
				'error_code'       => $error ? $error->get_error_code() : '',
			),
			null,
			null,
			$actor_user_id
		);

		return $accepted
			? array( 'accepted' => true )
			: new WP_Error(
				$error ? sanitize_key( $error->get_error_code() ) : 'wp_mail_returned_false',
				$error ? $error->get_error_message() : __( 'WordPress mail transport returned false.', 'sustainable-catalyst-engagement-intake' )
			);
	}

	private static function run_review_due_reminders( array $settings ): void {
		global $wpdb;

		$table = SC_EI_Database::table( 'inquiries' );
		$lead_hours = max( 0, min( 168, absint( $settings['review_reminder_lead_hours'] ?? 24 ) ) );
		$lead = gmdate( 'Y-m-d H:i:s', time() + ( $lead_hours * HOUR_IN_SECONDS ) );
		$limit = max( 1, min( 100, absint( $settings['notification_batch_limit'] ?? 25 ) ) );

		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE review_stage <> %s
				AND review_due_at IS NOT NULL
				AND review_due_at <= %s
				AND assigned_user_id IS NOT NULL
				AND assigned_user_id > 0
				ORDER BY review_due_at ASC
				LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'completed',
				$lead,
				$limit
			),
			ARRAY_A
		);

		$day = gmdate( 'Y-m-d' );
		foreach ( $rows as $inquiry ) {
			$user = get_userdata( absint( $inquiry['assigned_user_id'] ) );
			if ( ! $user || ! is_email( $user->user_email ) ) {
				continue;
			}
			self::create_and_send(
				absint( $inquiry['id'] ),
				'internal_review_due',
				$user->display_name,
				$user->user_email,
				'internal-review-due-' . absint( $inquiry['id'] ) . '-' . $day,
				array(
					'audience' => 'internal',
					'trigger'  => 'review_due',
					'due_at'   => $inquiry['review_due_at'],
				)
			);
		}
	}

	private static function run_follow_up_reminders( array $settings ): void {
		global $wpdb;

		$table = SC_EI_Database::table( 'inquiries' );
		$limit = max( 1, min( 100, absint( $settings['notification_batch_limit'] ?? 25 ) ) );
		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE next_follow_up_at IS NOT NULL
				AND next_follow_up_at <= %s
				AND communication_status <> %s
				ORDER BY next_follow_up_at ASC
				LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				current_time( 'mysql', true ),
				'closed',
				$limit
			),
			ARRAY_A
		);

		$day = gmdate( 'Y-m-d' );
		foreach ( $rows as $inquiry ) {
			$recipients = array();
			if ( ! empty( $inquiry['assigned_user_id'] ) ) {
				$user = get_userdata( absint( $inquiry['assigned_user_id'] ) );
				if ( $user && is_email( $user->user_email ) ) {
					$recipients[] = array( $user->display_name, $user->user_email );
				}
			}
			if ( ! $recipients ) {
				foreach ( self::internal_recipients( $settings ) as $email ) {
					$recipients[] = array( __( 'Engagement Intake Reviewer', 'sustainable-catalyst-engagement-intake' ), $email );
				}
			}

			foreach ( $recipients as $recipient ) {
				self::create_and_send(
					absint( $inquiry['id'] ),
					'internal_follow_up_due',
					$recipient[0],
					$recipient[1],
					'internal-follow-up-' . absint( $inquiry['id'] ) . '-' . $day . '-' . substr( hash( 'sha256', strtolower( $recipient[1] ) ), 0, 16 ),
					array(
						'audience'      => 'internal',
						'trigger'       => 'follow_up_due',
						'follow_up_at'  => $inquiry['next_follow_up_at'],
					)
				);
			}
		}
	}

	private static function create_and_send(
		int $inquiry_id,
		string $template_key,
		string $recipient_name,
		string $recipient_email,
		string $dedupe_key,
		array $metadata
	): void {
		$communication = SC_EI_Communication_Repository::create_system_notification(
			$inquiry_id,
			$template_key,
			$recipient_name,
			$recipient_email,
			$dedupe_key,
			$metadata
		);
		if ( is_wp_error( $communication ) ) {
			SC_EI_Audit_Log::record(
				'notification_creation_failed',
				'An enabled notification could not be created.',
				array(
					'template_key' => $template_key,
					'error_code'   => $communication->get_error_code(),
				),
				$inquiry_id
			);
			return;
		}

		if ( 'accepted' === $communication['status'] || 'suppressed' === $communication['status'] ) {
			return;
		}
		SC_EI_Mailer::send( absint( $communication['id'] ), 0 );
	}

	private static function settings(): array {
		return wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() );
	}

	private static function internal_recipients( array $settings ): array {
		$recipients = SC_EI_Communication_Schema::sanitize_emails(
			$settings['notification_internal_recipients'] ?? '',
			10
		);
		if ( ! $recipients && is_email( get_option( 'admin_email' ) ) ) {
			$recipients[] = sanitize_email( get_option( 'admin_email' ) );
		}
		return $recipients;
	}

	private static function escalation_recipients( array $settings ): array {
		$recipients = SC_EI_Communication_Schema::sanitize_emails(
			$settings['notification_escalation_recipients'] ?? '',
			10
		);
		return $recipients ?: self::internal_recipients( $settings );
	}

	private static function acquire_cron_lock(): bool {
		if ( add_option( 'sc_ei_notification_cron_lock', time(), '', false ) ) {
			return true;
		}
		$created = absint( get_option( 'sc_ei_notification_cron_lock', 0 ) );
		if ( $created && $created < time() - 30 * MINUTE_IN_SECONDS ) {
			delete_option( 'sc_ei_notification_cron_lock' );
			return add_option( 'sc_ei_notification_cron_lock', time(), '', false );
		}
		return false;
	}

	private static function email_domain( string $email ): string {
		$parts = explode( '@', strtolower( $email ) );
		return count( $parts ) === 2 ? sanitize_text_field( $parts[1] ) : '';
	}
}
