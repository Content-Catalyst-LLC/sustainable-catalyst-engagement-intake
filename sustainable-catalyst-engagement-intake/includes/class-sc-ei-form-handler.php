<?php
/**
 * Public form processing.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Form_Handler {

	private const NONCE_ACTION = 'sc_ei_public_submit';
	private const MAX_TEXTAREA = 12000;
	private const MAX_LINKS = 10;

	public static function register(): void {
		add_action( 'admin_post_nopriv_sc_ei_submit', array( __CLASS__, 'handle_post' ) );
		add_action( 'admin_post_sc_ei_submit', array( __CLASS__, 'handle_post' ) );
	}

	public static function nonce_action(): string {
		return self::NONCE_ACTION;
	}

	public static function timing_signature( int $started_at, string $form_id ): string {
		return hash_hmac(
			'sha256',
			$started_at . '|' . sanitize_key( $form_id ),
			wp_salt( 'nonce' )
		);
	}

	public static function handle_post(): void {
		$result = self::process( wp_unslash( $_POST ) );
		$target = self::safe_redirect_target( $_POST['redirect_to'] ?? '' );

		if ( is_wp_error( $result ) ) {
			$target = add_query_arg(
				array(
					'sc_ei_result' => 'error',
					'sc_ei_error'  => sanitize_key( $result->get_error_code() ),
				),
				$target
			);
		} else {
			$target = add_query_arg(
				array(
					'sc_ei_result'    => 'success',
					'sc_ei_reference' => rawurlencode( $result['reference'] ),
				),
				$target
			);
		}

		wp_safe_redirect( $target );
		exit;
	}

	public static function process( array $raw ) {
		$nonce = isset( $raw['sc_ei_nonce'] ) ? sanitize_text_field( (string) $raw['sc_ei_nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return new WP_Error( 'security_check', __( 'The form security check expired. Please reload the page and try again.', 'sustainable-catalyst-engagement-intake' ) );
		}

		if ( ! empty( $raw['company_website'] ) ) {
			return new WP_Error( 'submission_rejected', __( 'The submission could not be accepted.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$timing = self::validate_timing( $raw );
		if ( is_wp_error( $timing ) ) {
			return $timing;
		}

		$mode         = sanitize_key( (string) ( $raw['form_mode'] ?? 'hub' ) );
		$inquiry_type = sanitize_key( (string) ( $raw['inquiry_type'] ?? 'general' ) );
		$allowed      = SC_EI_Form_Schema::all_public_types();

		if ( ! array_key_exists( $inquiry_type, $allowed ) ) {
			return new WP_Error( 'invalid_inquiry_type', __( 'Choose a valid inquiry path.', 'sustainable-catalyst-engagement-intake' ) );
		}

		if ( 'general' === $mode && ! array_key_exists( $inquiry_type, SC_EI_Form_Schema::general_types() ) ) {
			return new WP_Error( 'invalid_inquiry_type', __( 'Choose a valid general inquiry path.', 'sustainable-catalyst-engagement-intake' ) );
		}

		if ( 'consulting' === $mode && ! array_key_exists( $inquiry_type, SC_EI_Form_Schema::engagement_types() ) ) {
			return new WP_Error( 'invalid_inquiry_type', __( 'Choose a valid engagement inquiry path.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$name    = self::clean_single( $raw['contact_name'] ?? '', 191 );
		$email   = sanitize_email( (string) ( $raw['contact_email'] ?? '' ) );
		$subject = self::clean_single( $raw['subject'] ?? '', 255 );
		$message = self::clean_textarea( $raw['message'] ?? '' );

		if ( '' === $name ) {
			return new WP_Error( 'name_required', __( 'Enter your name.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'email_required', __( 'Enter a valid email address.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( '' === $subject ) {
			return new WP_Error( 'subject_required', __( 'Enter a short subject for the inquiry.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( '' === $message ) {
			return new WP_Error( 'message_required', __( 'Describe the question, project, or request.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( empty( $raw['privacy_consent'] ) ) {
			return new WP_Error( 'consent_required', __( 'Confirm that Sustainable Catalyst may process and respond to this inquiry.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( empty( $raw['authorization_consent'] ) ) {
			return new WP_Error( 'authorization_required', __( 'Confirm that you are authorized to share the information included in the inquiry.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$service_interest = sanitize_key( (string) ( $raw['service_interest'] ?? '' ) );
		$budget_range     = sanitize_key( (string) ( $raw['budget_range'] ?? '' ) );
		$project_summary  = self::clean_textarea( $raw['project_summary'] ?? '' );
		$desired_outcome  = self::clean_textarea( $raw['desired_outcome'] ?? '' );

		if ( SC_EI_Form_Schema::type_requires_engagement_fields( $inquiry_type ) ) {
			if ( ! array_key_exists( $service_interest, SC_EI_Form_Schema::service_interests() ) ) {
				return new WP_Error( 'service_required', __( 'Choose the service or engagement that best matches the request.', 'sustainable-catalyst-engagement-intake' ) );
			}
			if ( ! array_key_exists( $budget_range, SC_EI_Form_Schema::budget_ranges() ) ) {
				return new WP_Error( 'budget_required', __( 'Choose the closest available budget range.', 'sustainable-catalyst-engagement-intake' ) );
			}
			if ( '' === $project_summary ) {
				return new WP_Error( 'project_required', __( 'Describe the current project, system, or problem.', 'sustainable-catalyst-engagement-intake' ) );
			}
			if ( '' === $desired_outcome ) {
				return new WP_Error( 'outcome_required', __( 'Describe the outcome or decision the work should support.', 'sustainable-catalyst-engagement-intake' ) );
			}
		}

		$rate_check = self::check_rate_limit( $email );
		if ( is_wp_error( $rate_check ) ) {
			return $rate_check;
		}

		$duplicate_key = self::duplicate_key( $email, $subject, $message );
		if ( get_transient( $duplicate_key ) ) {
			return new WP_Error( 'duplicate_submission', __( 'This inquiry appears to have already been submitted. Check for the confirmation reference before sending it again.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$metadata = array(
			'form_mode'          => $mode,
			'stakeholders'       => self::clean_textarea( $raw['stakeholders'] ?? '' ),
			'current_materials'  => self::clean_textarea( $raw['current_materials'] ?? '' ),
			'referral_source'    => sanitize_key( (string) ( $raw['referral_source'] ?? '' ) ),
			'event_name'         => self::clean_single( $raw['event_name'] ?? '', 191 ),
			'event_date'         => self::clean_date( $raw['event_date'] ?? '' ),
			'event_format'       => self::clean_single( $raw['event_format'] ?? '', 120 ),
			'audience'           => self::clean_single( $raw['audience'] ?? '', 191 ),
			'follow_up_consent'  => empty( $raw['follow_up_consent'] ) ? 'no' : 'yes',
			'source_url'         => esc_url_raw( (string) ( $raw['source_url'] ?? '' ) ),
			'privacy_notice'     => 'engagement-intake-v0.2.0',
		);

		$links = self::clean_links( $raw['relevant_links'] ?? '' );

		try {
			$id = SC_EI_Inquiry_Repository::create(
				array(
					'inquiry_type'       => $inquiry_type,
					'status'             => 'new',
					'contact_name'       => $name,
					'contact_email'      => $email,
					'organization'       => self::clean_single( $raw['organization'] ?? '', 191 ),
					'role_title'         => self::clean_single( $raw['role_title'] ?? '', 191 ),
					'subject'            => $subject,
					'message'            => $message,
					'project_summary'    => $project_summary,
					'desired_outcome'    => $desired_outcome,
					'service_interest'   => $service_interest,
					'budget_range'       => $budget_range,
					'desired_start_date' => self::clean_date( $raw['desired_start_date'] ?? '' ),
					'deadline_date'      => self::clean_date( $raw['deadline_date'] ?? '' ),
					'relevant_links'     => $links,
					'metadata'           => $metadata,
					'consent_version'    => 'engagement-intake-v0.2.0',
					'consent_at'         => current_time( 'mysql', true ),
				)
			);
		} catch ( Throwable $exception ) {
			return new WP_Error( 'storage_error', __( 'The inquiry could not be stored. Please try again or use another contact route.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$record = SC_EI_Inquiry_Repository::find( $id );
		if ( ! $record ) {
			return new WP_Error( 'storage_error', __( 'The inquiry was stored but its confirmation record could not be loaded.', 'sustainable-catalyst-engagement-intake' ) );
		}

		SC_EI_Audit_Log::record(
			'public_form_submitted',
			'Inquiry submitted through the adaptive public contact form.',
			array(
				'form_mode'    => $mode,
				'inquiry_type' => $inquiry_type,
			),
			$id,
			null,
			null
		);

		set_transient( $duplicate_key, 1, 10 * MINUTE_IN_SECONDS );
		self::increment_rate_limit( $email );

		do_action( 'sc_ei_public_inquiry_created', $record, $raw );

		return array(
			'id'        => $id,
			'reference' => $record['reference'],
			'status'    => $record['status'],
		);
	}

	private static function validate_timing( array $raw ) {
		$started   = isset( $raw['form_started_at'] ) ? absint( $raw['form_started_at'] ) : 0;
		$form_id   = sanitize_key( (string) ( $raw['form_id'] ?? '' ) );
		$signature = sanitize_text_field( (string) ( $raw['form_signature'] ?? '' ) );

		if ( ! $started || ! $form_id || ! hash_equals( self::timing_signature( $started, $form_id ), $signature ) ) {
			return new WP_Error( 'form_expired', __( 'The form session is invalid. Reload the page and try again.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$settings    = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() );
		$minimum     = max( 1, absint( $settings['minimum_completion_seconds'] ?? 3 ) );
		$elapsed     = time() - $started;

		if ( $elapsed < $minimum ) {
			return new WP_Error( 'too_fast', __( 'The form was submitted too quickly. Review the information and try again.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( $elapsed > DAY_IN_SECONDS ) {
			return new WP_Error( 'form_expired', __( 'The form session expired. Reload the page and try again.', 'sustainable-catalyst-engagement-intake' ) );
		}

		return true;
	}

	private static function check_rate_limit( string $email ) {
		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() );
		$maximum  = max( 1, min( 20, absint( $settings['submissions_per_hour'] ?? 5 ) ) );
		$key      = self::rate_key( $email );
		$count    = absint( get_transient( $key ) );

		if ( $count >= $maximum ) {
			return new WP_Error( 'rate_limited', __( 'Too many inquiries were submitted from this email address in a short period. Please try again later.', 'sustainable-catalyst-engagement-intake' ) );
		}

		return true;
	}

	private static function increment_rate_limit( string $email ): void {
		$key   = self::rate_key( $email );
		$count = absint( get_transient( $key ) );
		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
	}

	private static function rate_key( string $email ): string {
		return 'sc_ei_rate_' . substr( hash_hmac( 'sha256', strtolower( $email ), wp_salt( 'auth' ) ), 0, 32 );
	}

	private static function duplicate_key( string $email, string $subject, string $message ): string {
		$value = strtolower( $email ) . '|' . $subject . '|' . substr( $message, 0, 500 );
		return 'sc_ei_dup_' . substr( hash_hmac( 'sha256', $value, wp_salt( 'auth' ) ), 0, 32 );
	}

	private static function clean_single( $value, int $limit ): string {
		$value = sanitize_text_field( (string) $value );
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $limit ) : substr( $value, 0, $limit );
	}

	private static function clean_textarea( $value ): string {
		$value = sanitize_textarea_field( (string) $value );
		return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, self::MAX_TEXTAREA ) : substr( $value, 0, self::MAX_TEXTAREA );
	}

	private static function clean_date( $value ): ?string {
		$value = sanitize_text_field( (string) $value );
		if ( '' === $value ) {
			return null;
		}
		$date = DateTimeImmutable::createFromFormat( 'Y-m-d', $value );
		return $date && $date->format( 'Y-m-d' ) === $value ? $value : null;
	}

	private static function clean_links( $value ): array {
		$lines = preg_split( '/\R+/', (string) $value );
		$links = array();
		foreach ( (array) $lines as $line ) {
			$url = esc_url_raw( trim( $line ) );
			if ( $url ) {
				$links[] = $url;
			}
			if ( count( $links ) >= self::MAX_LINKS ) {
				break;
			}
		}
		return array_values( array_unique( $links ) );
	}

	private static function safe_redirect_target( $target ): string {
		$fallback = home_url( '/contact/' );
		$target   = esc_url_raw( (string) $target );
		return wp_validate_redirect( $target, $fallback );
	}
}
