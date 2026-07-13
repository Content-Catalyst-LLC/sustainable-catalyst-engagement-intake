<?php
/**
 * Plain-text WordPress mail transport with lock, suppression, and honest acceptance state.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Mailer {

	private static ?WP_Error $last_error = null;

	public static function register(): void {
		add_action( 'wp_mail_failed', array( __CLASS__, 'capture_failure' ) );
	}

	public static function capture_failure( WP_Error $error ): void {
		self::$last_error = $error;
	}

	public static function send( int $communication_id, int $actor_user_id = 0 ) {
		$communication = SC_EI_Communication_Repository::find( $communication_id );
		if ( ! $communication ) {
			return new WP_Error( 'communication_not_found', __( 'The communication could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( ! in_array( $communication['status'], array( 'draft', 'approved', 'failed' ), true ) ) {
			return new WP_Error( 'communication_not_sendable', __( 'Only a draft, approved, or failed email can be sent or retried.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( 'email' !== $communication['channel'] ) {
			return new WP_Error( 'communication_channel_not_sendable', __( 'Only email is sent by the plugin. Teams, phone, video, and in-person interactions are recorded manually.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$inquiry = SC_EI_Inquiry_Repository::find( absint( $communication['inquiry_id'] ) );
		if ( ! $inquiry ) {
			return new WP_Error( 'communication_inquiry_not_found', __( 'The inquiry could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$is_sender_email = strtolower( (string) $communication['recipient_email'] ) === strtolower( (string) $inquiry['contact_email'] );
		if ( $is_sender_email && ! empty( $inquiry['do_not_email'] ) ) {
			SC_EI_Communication_Repository::transition(
				$communication_id,
				'suppressed',
				$actor_user_id,
				array(
					'error_code'    => 'do_not_email',
					'error_message' => sanitize_textarea_field( (string) $inquiry['do_not_email_reason'] ),
				),
				'send_suppressed',
				array( 'reason' => 'do_not_email' )
			);
			return new WP_Error( 'communication_suppressed', __( 'Email is suppressed for this inquiry. Clear the suppression deliberately before sending.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$recipient = sanitize_email( (string) $communication['recipient_email'] );
		if ( ! is_email( $recipient ) ) {
			return new WP_Error( 'communication_recipient_invalid', __( 'The recipient email is invalid.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$lock_key = 'sc_ei_mail_lock_' . $communication_id;
		if ( ! add_option( $lock_key, time(), '', false ) ) {
			$created = absint( get_option( $lock_key, 0 ) );
			if ( ! $created || $created > time() - 5 * MINUTE_IN_SECONDS ) {
				return new WP_Error( 'communication_send_locked', __( 'This communication is already being sent. Reload before retrying.', 'sustainable-catalyst-engagement-intake' ) );
			}
			delete_option( $lock_key );
			if ( ! add_option( $lock_key, time(), '', false ) ) {
				return new WP_Error( 'communication_send_locked', __( 'The send lock could not be acquired.', 'sustainable-catalyst-engagement-intake' ) );
			}
		}

		try {
			$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() );
			$from_name = sanitize_text_field( (string) ( $settings['communication_sender_name'] ?: get_bloginfo( 'name' ) ) );
			$from_email = sanitize_email( (string) ( $settings['communication_sender_email'] ?: get_option( 'admin_email' ) ) );
			$reply_to = sanitize_email( (string) ( $settings['communication_reply_to_email'] ?: $from_email ) );

			if ( ! is_email( $from_email ) || ! is_email( $reply_to ) ) {
				return new WP_Error( 'communication_sender_invalid', __( 'Configure a valid sender and reply-to email before sending.', 'sustainable-catalyst-engagement-intake' ) );
			}

			$attempt = absint( $communication['attempt_count'] ) + 1;
			$now = current_time( 'mysql', true );
			SC_EI_Communication_Repository::transition(
				$communication_id,
				'sending',
				$actor_user_id,
				array(
					'attempt_count'  => $attempt,
					'last_attempt_at'=> $now,
					'approved_by'    => $actor_user_id ?: ( $communication['approved_by'] ?: null ),
					'approved_at'    => $communication['approved_at'] ?: $now,
					'provider'       => 'wordpress_wp_mail',
					'error_code'     => '',
					'error_message'  => '',
				),
				'send_attempted',
				array( 'attempt' => $attempt )
			);

			$cc = SC_EI_Communication_Schema::sanitize_emails(
				json_decode( (string) $communication['cc_json'], true ) ?: array(),
				10
			);
			$headers = array(
				'Content-Type: text/plain; charset=UTF-8',
				sprintf( 'From: %s <%s>', self::header_name( $from_name ), $from_email ),
				sprintf( 'Reply-To: %s <%s>', self::header_name( $from_name ), $reply_to ),
				'X-SC-EI-Reference: ' . sanitize_text_field( (string) $inquiry['reference'] ),
				'X-SC-EI-Communication: ' . absint( $communication_id ),
			);
			foreach ( $cc as $email ) {
				if ( strtolower( $email ) !== strtolower( $recipient ) ) {
					$headers[] = 'Cc: ' . $email;
				}
			}

			self::$last_error = null;
			$accepted = wp_mail(
				$recipient,
				SC_EI_Communication_Schema::sanitize_subject( (string) $communication['subject'] ),
				SC_EI_Communication_Schema::sanitize_body( (string) $communication['body_text'] ),
				$headers
			);

			if ( $accepted ) {
				$accepted_at = current_time( 'mysql', true );
				$message_hash = hash(
					'sha256',
					strtolower( $recipient ) . "\n" . $communication['subject'] . "\n" . $communication['body_text']
				);
				SC_EI_Communication_Repository::transition(
					$communication_id,
					'accepted',
					$actor_user_id,
					array(
						'accepted_at'  => $accepted_at,
						'failed_at'    => null,
						'error_code'   => '',
						'error_message'=> '',
						'message_hash' => $message_hash,
						'provider'     => 'wordpress_wp_mail',
					),
					'mail_transport_accepted',
					array(
						'attempt'          => $attempt,
						'delivery_claim'   => 'accepted_by_mail_transport_not_proven_delivered',
						'recipient_domain' => self::email_domain( $recipient ),
					),
					'wordpress_wp_mail'
				);
				SC_EI_Communication_Repository::update_inquiry_aggregate(
					absint( $communication['inquiry_id'] ),
					(string) $communication['direction'],
					$accepted_at,
					false
				);
				if ( 'system' === $communication['direction'] ) {
					SC_EI_Communication_Repository::mark_notification_time( absint( $communication['inquiry_id'] ) );
				}
				SC_EI_Audit_Log::record(
					'communication_mail_accepted',
					'Email was accepted by the WordPress mail transport. Delivery was not independently confirmed.',
					array(
						'communication_id' => $communication_id,
						'attempt'          => $attempt,
						'type'             => $communication['communication_type'],
						'direction'        => $communication['direction'],
						'recipient_domain' => self::email_domain( $recipient ),
					),
					absint( $communication['inquiry_id'] ),
					null,
					$actor_user_id ?: null
				);
				return SC_EI_Communication_Repository::find( $communication_id );
			}

			$error = self::$last_error;
			$error_code = $error ? sanitize_key( $error->get_error_code() ) : 'wp_mail_returned_false';
			$error_message = $error
				? sanitize_textarea_field( $error->get_error_message() )
				: __( 'WordPress mail transport returned false without a detailed error.', 'sustainable-catalyst-engagement-intake' );

			SC_EI_Communication_Repository::transition(
				$communication_id,
				'failed',
				$actor_user_id,
				array(
					'failed_at'    => current_time( 'mysql', true ),
					'error_code'   => $error_code,
					'error_message'=> $error_message,
					'provider'     => 'wordpress_wp_mail',
				),
				'mail_transport_failed',
				array( 'attempt' => $attempt ),
				'wordpress_wp_mail',
				'',
				$error_code,
				$error_message
			);
			SC_EI_Audit_Log::record(
				'communication_mail_failed',
				'WordPress mail transport failed to accept an email.',
				array(
					'communication_id' => $communication_id,
					'attempt'          => $attempt,
					'error_code'       => $error_code,
					'recipient_domain' => self::email_domain( $recipient ),
				),
				absint( $communication['inquiry_id'] ),
				null,
				$actor_user_id ?: null
			);
			return new WP_Error( $error_code, $error_message );
		} finally {
			delete_option( $lock_key );
		}
	}

	private static function header_name( string $name ): string {
		return trim( preg_replace( '/[\r\n<>]+/', '', $name ) );
	}

	private static function email_domain( string $email ): string {
		$parts = explode( '@', strtolower( $email ) );
		return count( $parts ) === 2 ? sanitize_text_field( $parts[1] ) : '';
	}
}
