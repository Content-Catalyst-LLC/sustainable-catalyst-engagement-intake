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

	public static function attribution_signature( string $variant, string $source, string $entry_cta, string $form_id ): string {
		$payload = implode(
			'|',
			array(
				SC_EI_Conversion::sanitize_variant( $variant ),
				SC_EI_Conversion::sanitize_source( $source ),
				SC_EI_Conversion::sanitize_entry_cta( $entry_cta ),
				sanitize_key( $form_id ),
			)
		);

		return hash_hmac( 'sha256', $payload, wp_salt( 'auth' ) );
	}

	public static function handle_post(): void {
		$result = self::process( wp_unslash( $_POST ), $_FILES );
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
					'sc_ei_reference'    => rawurlencode( $result['reference'] ),
					'sc_ei_files'        => absint( $result['attachment_count'] ?? 0 ),
					'sc_ei_file_warning' => empty( $result['attachment_errors'] ) ? 0 : 1,
				),
				$target
			);
		}

		wp_safe_redirect( $target, 303 );
		exit;
	}

	public static function process( array $raw, array $files = array() ) {
		$nonce = isset( $raw['sc_ei_nonce'] ) ? sanitize_text_field( (string) $raw['sc_ei_nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return new WP_Error( 'security_check', __( 'The form security check expired. Please reload the page and try again.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$envelope = SC_EI_Upload_Environment::validate_request_envelope( $raw, $files );
		if ( is_wp_error( $envelope ) ) {
			return $envelope;
		}

		if ( ! empty( $raw['company_website'] ) ) {
			return new WP_Error( 'submission_rejected', __( 'The submission could not be accepted.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$timing = self::validate_timing( $raw );
		if ( is_wp_error( $timing ) ) {
			return $timing;
		}

		$mode = sanitize_key( (string) ( $raw['form_mode'] ?? 'advanced' ) );
		$mode_variants = array(
			'compact'    => 'compact',
			'advanced'   => 'advanced',
			'general'    => 'general',
			'consulting' => 'consulting',
		);
		if ( ! isset( $mode_variants[ $mode ] ) ) {
			return new WP_Error( 'invalid_form_mode', __( 'The intake form mode is invalid.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$form_variant = $mode_variants[ $mode ];
		$source_page  = SC_EI_Conversion::sanitize_source( (string) ( $raw['source_page'] ?? 'other' ) );
		$entry_cta    = SC_EI_Conversion::sanitize_entry_cta( (string) ( $raw['entry_cta'] ?? 'unspecified' ) );
		$form_id      = sanitize_key( (string) ( $raw['form_id'] ?? '' ) );
		$request_id   = sanitize_text_field( (string) ( $raw['request_id'] ?? '' ) );
		if ( ! preg_match( '/^[a-f0-9-]{36}$/i', $request_id ) ) {
			$request_id = wp_generate_uuid4();
		}
		$attribution_signature = sanitize_text_field( (string) ( $raw['attribution_signature'] ?? '' ) );
		$expected_attribution  = self::attribution_signature( $form_variant, $source_page, $entry_cta, $form_id );

		if ( ! $attribution_signature || ! hash_equals( $expected_attribution, $attribution_signature ) ) {
			return new WP_Error( 'attribution_invalid', __( 'The form attribution check failed. Reload the page and try again.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$inquiry_type     = sanitize_key( (string) ( $raw['inquiry_type'] ?? 'general' ) );
		$allowed          = SC_EI_Form_Schema::all_public_types();
		$service_interest = sanitize_key( (string) ( $raw['service_interest'] ?? '' ) );
		$budget_range     = sanitize_key( (string) ( $raw['budget_range'] ?? '' ) );
		$project_summary  = self::clean_textarea( $raw['project_summary'] ?? '' );
		$desired_outcome  = self::clean_textarea( $raw['desired_outcome'] ?? '' );
		if ( ! array_key_exists( $inquiry_type, $allowed ) ) {
			return new WP_Error( 'invalid_inquiry_type', __( 'Choose a valid inquiry path.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( 'general' === $mode && ! array_key_exists( $inquiry_type, SC_EI_Form_Schema::general_types() ) ) {
			return new WP_Error( 'invalid_inquiry_type', __( 'Choose a valid general inquiry path.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( 'consulting' === $mode && ! array_key_exists( $inquiry_type, SC_EI_Form_Schema::engagement_types() ) ) {
			return new WP_Error( 'invalid_inquiry_type', __( 'Choose a valid engagement inquiry path.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( 'compact' === $mode && 'consulting' !== $inquiry_type ) {
			return new WP_Error( 'invalid_inquiry_type', __( 'The compact Consulting form can create consulting inquiries only.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$name    = self::clean_single( $raw['contact_name'] ?? '', 191 );
		$email   = sanitize_email( (string) ( $raw['contact_email'] ?? '' ) );
		$subject = self::clean_single( $raw['subject'] ?? '', 255 );
		$message = self::clean_textarea( $raw['message'] ?? '' );

		if ( 'compact' === $mode ) {
			$compact_services = SC_EI_Form_Schema::compact_service_interests();
			if ( '' === $subject ) {
				$subject = sprintf(
					__( 'Consulting inquiry — %s', 'sustainable-catalyst-engagement-intake' ),
					$compact_services[ $service_interest ] ?? __( 'Best starting point requested', 'sustainable-catalyst-engagement-intake' )
				);
			}
			if ( '' === $message ) {
				$message = $project_summary;
			}
		}

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

		if ( SC_EI_Form_Schema::type_requires_engagement_fields( $inquiry_type ) ) {
			$service_choices = 'compact' === $mode ? SC_EI_Form_Schema::compact_service_interests() : SC_EI_Form_Schema::service_interests();
			$budget_choices  = 'compact' === $mode ? SC_EI_Form_Schema::compact_budget_ranges() : SC_EI_Form_Schema::budget_ranges();

			if ( ! array_key_exists( $service_interest, $service_choices ) ) {
				return new WP_Error( 'service_required', __( 'Choose the service or engagement that best matches the request.', 'sustainable-catalyst-engagement-intake' ) );
			}
			if ( ! array_key_exists( $budget_range, $budget_choices ) ) {
				return new WP_Error( 'budget_required', __( 'Choose the closest available budget range.', 'sustainable-catalyst-engagement-intake' ) );
			}
			if ( '' === $project_summary ) {
				return new WP_Error( 'project_required', __( 'Describe the current project, system, or problem.', 'sustainable-catalyst-engagement-intake' ) );
			}
			if ( '' === $desired_outcome ) {
				return new WP_Error( 'outcome_required', __( 'Describe the outcome or decision the work should support.', 'sustainable-catalyst-engagement-intake' ) );
			}
		}

		$compact_next_step = sanitize_key( (string) ( $raw['compact_next_step'] ?? 'email_first' ) );
		if ( 'compact' === $mode ) {
			if ( ! in_array( $compact_next_step, array( 'email_first', 'teams_fit_call' ), true ) ) {
				return new WP_Error( 'next_step_required', __( 'Choose email follow-up or a Microsoft Teams fit-call request.', 'sustainable-catalyst-engagement-intake' ) );
			}
			$contact_method = 'teams_fit_call' === $compact_next_step ? 'teams' : 'email';
			$meeting_request = 'teams_fit_call' === $compact_next_step ? 'yes' : 'no';
		} else {
			$contact_method = sanitize_key( (string) ( $raw['preferred_contact_method'] ?? 'email' ) );
			$meeting_request = sanitize_key( (string) ( $raw['meeting_request'] ?? 'no' ) );
		}

		if ( ! array_key_exists( $contact_method, SC_EI_Teams::contact_methods() ) ) {
			return new WP_Error( 'contact_method_required', __( 'Choose a valid preferred response method.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$teams_email = sanitize_email( (string) ( $raw['teams_email'] ?? '' ) );
		$phone       = self::clean_single( $raw['phone_number'] ?? '', 80 );

		if ( 'compact' === $mode && 'teams' === $contact_method && ! $teams_email ) {
			$teams_email = $email;
		}

		if ( 'teams' === $contact_method && ! is_email( $teams_email ) ) {
			return new WP_Error( 'teams_email_required', __( 'Enter the email address associated with Microsoft Teams.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( 'phone' === $contact_method && '' === $phone ) {
			return new WP_Error( 'phone_required', __( 'Enter a phone number or choose another response method.', 'sustainable-catalyst-engagement-intake' ) );
		}

		if ( ! array_key_exists( $meeting_request, SC_EI_Teams::meeting_requests() ) ) {
			return new WP_Error( 'meeting_request_required', __( 'Choose whether you are requesting a Microsoft Teams meeting.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$timezone = self::clean_single( $raw['timezone'] ?? '', 120 );
		$calendar_invite_consent = empty( $raw['calendar_invite_consent'] ) ? 0 : 1;
		$meeting_requested = in_array( $meeting_request, array( 'yes', 'unsure' ), true );

		if ( $meeting_requested ) {
			if ( ! SC_EI_Teams::valid_timezone( $timezone ) ) {
				return new WP_Error( 'timezone_required', __( 'Enter a valid IANA time zone, such as America/Chicago.', 'sustainable-catalyst-engagement-intake' ) );
			}
			if ( ! $calendar_invite_consent ) {
				return new WP_Error( 'calendar_consent_required', __( 'Confirm that Sustainable Catalyst may send a Microsoft Teams calendar invitation if a meeting is approved.', 'sustainable-catalyst-engagement-intake' ) );
			}
			if ( ! $teams_email ) {
				$teams_email = $email;
			}
		} elseif ( '' === $timezone ) {
			$timezone = '';
		}

		$preferred_duration = absint( $raw['preferred_duration'] ?? 0 );
		if ( ! array_key_exists( (string) $preferred_duration, SC_EI_Teams::duration_options() ) ) {
			$preferred_duration = 0;
		}

		$participant_count = max( 1, min( 50, absint( $raw['participant_count'] ?? 1 ) ) );
		$weekdays          = SC_EI_Teams::sanitize_weekdays( $raw['preferred_weekdays'] ?? array() );
		$participant_emails= SC_EI_Teams::sanitize_participant_emails( $raw['participant_emails'] ?? '' );

		$document_category = sanitize_key( (string) ( $raw['document_category'] ?? 'other' ) );
		if ( ! array_key_exists( $document_category, SC_EI_Form_Schema::document_categories() ) ) {
			$document_category = 'other';
		}

		$document_confidentiality = sanitize_key( (string) ( $raw['document_confidentiality'] ?? 'non_confidential' ) );
		if ( ! array_key_exists( $document_confidentiality, SC_EI_Form_Schema::document_confidentiality_options() ) ) {
			$document_confidentiality = 'non_confidential';
		}

		$document_notes = self::clean_textarea( $raw['document_notes'] ?? '' );
		$upload_items   = SC_EI_Upload_Manager::normalize_files( $files, 'documents' );

		if ( $upload_items && empty( $raw['document_upload_consent'] ) ) {
			return new WP_Error( 'document_consent_required', __( 'Confirm that you are authorized to upload the selected documents and understand that they will be quarantined for review.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$successful_response = get_transient( self::request_success_key( $request_id ) );
		if ( is_array( $successful_response ) && ! empty( $successful_response['reference'] ) ) {
			return $successful_response;
		}

		$rate_check = self::check_rate_limit( $email );
		if ( is_wp_error( $rate_check ) ) {
			return $rate_check;
		}

		$duplicate_key = self::duplicate_key( $email, $subject, $message );
		if ( get_transient( $duplicate_key ) ) {
			return new WP_Error( 'duplicate_submission', __( 'This inquiry appears to have already been submitted. Check for the confirmation reference before sending it again.', 'sustainable-catalyst-engagement-intake' ) );
		}

		if ( ! self::acquire_request_lock( $request_id ) ) {
			return new WP_Error( 'submission_in_progress', __( 'This inquiry is already being processed. Keep the page open and check for the confirmation before submitting again.', 'sustainable-catalyst-engagement-intake' ) );
		}

		try {
		$metadata = array(
			'form_mode'          => $mode,
			'form_variant'       => $form_variant,
			'source_page'        => $source_page,
			'entry_cta'          => $entry_cta,
			'conversion_route'   => SC_EI_Conversion::route( $inquiry_type, $service_interest, $form_variant ),
			'compact_next_step'  => 'compact' === $mode ? $compact_next_step : '',
			'stakeholders'       => self::clean_textarea( $raw['stakeholders'] ?? '' ),
			'current_materials'  => self::clean_textarea( $raw['current_materials'] ?? '' ),
			'referral_source'    => sanitize_key( (string) ( $raw['referral_source'] ?? '' ) ),
			'event_name'         => self::clean_single( $raw['event_name'] ?? '', 191 ),
			'event_date'         => self::clean_date( $raw['event_date'] ?? '' ),
			'event_format'       => self::clean_single( $raw['event_format'] ?? '', 120 ),
			'audience'           => self::clean_single( $raw['audience'] ?? '', 191 ),
			'follow_up_consent'  => empty( $raw['follow_up_consent'] ) ? 'no' : 'yes',
			'source_url'         => esc_url_raw( (string) ( $raw['source_url'] ?? '' ) ),
			'privacy_notice'     => 'engagement-intake-v0.9.1',
			'meeting_platform'   => 'microsoft_teams',
			'request_id'         => $request_id,
			'documents_selected' => count( $upload_items ),
			'document_selection_count' => absint( $raw['document_selection_count'] ?? 0 ),
			'document_selection_bytes' => absint( $raw['document_selection_bytes'] ?? 0 ),
			'request_content_length'   => SC_EI_Upload_Environment::content_length(),
			'document_category'  => $document_category,
			'document_confidentiality' => $document_confidentiality,
		);

		$links = self::clean_links( $raw['relevant_links'] ?? '' );

		try {
			$id = SC_EI_Inquiry_Repository::create(
				array(
					'inquiry_type'            => $inquiry_type,
					'status'                  => 'new',
					'form_variant'            => $form_variant,
					'source_page'             => $source_page,
					'entry_cta'               => $entry_cta,
					'contact_name'            => $name,
					'contact_email'           => $email,
					'organization'            => self::clean_single( $raw['organization'] ?? '', 191 ),
					'role_title'              => self::clean_single( $raw['role_title'] ?? '', 191 ),
					'subject'                 => $subject,
					'message'                 => $message,
					'project_summary'         => $project_summary,
					'desired_outcome'         => $desired_outcome,
					'service_interest'        => $service_interest,
					'budget_range'            => $budget_range,
					'desired_start_date'      => self::clean_date( $raw['desired_start_date'] ?? '' ),
					'deadline_date'           => self::clean_date( $raw['deadline_date'] ?? '' ),
					'preferred_contact_method'=> $contact_method,
					'teams_email'             => $teams_email,
					'phone_number'            => $phone,
					'timezone'                => $timezone,
					'city'                    => self::clean_single( $raw['city'] ?? '', 120 ),
					'country'                 => self::clean_single( $raw['country'] ?? '', 120 ),
					'meeting_request'         => $meeting_request,
					'preferred_weekdays'      => $weekdays,
					'preferred_time_windows'  => self::clean_textarea( $raw['preferred_time_windows'] ?? '' ),
					'preferred_duration'      => $preferred_duration,
					'participant_count'       => $participant_count,
					'participant_emails'      => $participant_emails,
					'accessibility_needs'     => self::clean_textarea( $raw['accessibility_needs'] ?? '' ),
					'calendar_invite_consent' => $calendar_invite_consent,
					'scheduling_notes'        => self::clean_textarea( $raw['scheduling_notes'] ?? '' ),
					'scheduling_status'       => $meeting_requested ? 'requested' : 'not_requested',
					'relevant_links'          => $links,
					'metadata'                => $metadata,
					'consent_version'         => 'engagement-intake-v0.9.1',
					'consent_at'              => current_time( 'mysql', true ),
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
				'form_mode'                => $mode,
				'form_variant'             => $form_variant,
				'source_page'              => $source_page,
				'entry_cta'                => $entry_cta,
				'conversion_route'         => $record['conversion_route'] ?? '',
				'inquiry_type'             => $inquiry_type,
				'preferred_contact_method' => $contact_method,
				'meeting_request'          => $meeting_request,
				'timezone'                 => $timezone,
				'request_id'               => $request_id,
				'documents_selected'       => count( $upload_items ),
				'request_content_length'   => SC_EI_Upload_Environment::content_length(),
			),
			$id,
			null,
			null
		);

		if ( $meeting_requested ) {
			SC_EI_Audit_Log::record(
				'teams_meeting_requested',
				'The sender requested or asked for guidance about a Microsoft Teams meeting.',
				array(
					'scheduling_status' => 'requested',
					'preferred_duration'=> $preferred_duration,
					'participant_count' => $participant_count,
				),
				$id,
				null,
				null
			);
		}

		$attachment_result = SC_EI_Upload_Manager::process_inquiry_uploads(
			$record,
			$files,
			array(
				'form_variant'             => $form_variant,
				'source_page'              => $source_page,
				'document_category'        => $document_category,
				'document_notes'           => $document_notes,
				'document_confidentiality' => $document_confidentiality,
				'request_id'                => $request_id,
			)
		);

		set_transient( $duplicate_key, 1, 10 * MINUTE_IN_SECONDS );
		self::increment_rate_limit( $email );

		$response = array(
			'id'                => $id,
			'reference'         => $record['reference'],
			'status'            => $record['status'],
			'scheduling_status' => $record['scheduling_status'],
			'form_variant'      => $record['form_variant'],
			'conversion_route'  => $record['conversion_route'],
			'attachment_count'  => $attachment_result['count'],
			'attachments'       => $attachment_result['accepted'],
			'attachment_errors' => $attachment_result['errors'],
			'request_id'        => $request_id,
		);

		set_transient( self::request_success_key( $request_id ), $response, 15 * MINUTE_IN_SECONDS );

		do_action( 'sc_ei_public_inquiry_created', $record, $raw );
		do_action(
			'sc_ei_conversion_routed',
			$record,
			array(
				'form_variant'     => $form_variant,
				'source_page'      => $source_page,
				'entry_cta'        => $entry_cta,
				'conversion_route' => $record['conversion_route'] ?? '',
				'guidance_flags'   => json_decode( (string) ( $record['guidance_flags'] ?? '[]' ), true ),
			)
		);

		return $response;
		} finally {
			self::release_request_lock( $request_id );
		}
	}

	private static function acquire_request_lock( string $request_id ): bool {
		$key      = self::request_lock_key( $request_id );
		$acquired = add_option( $key, time(), '', false );

		if ( $acquired ) {
			return true;
		}

		$created_at = absint( get_option( $key, 0 ) );
		if ( $created_at > 0 && $created_at < time() - 15 * MINUTE_IN_SECONDS ) {
			delete_option( $key );
			return add_option( $key, time(), '', false );
		}

		return false;
	}

	private static function release_request_lock( string $request_id ): void {
		delete_option( self::request_lock_key( $request_id ) );
	}

	private static function request_lock_key( string $request_id ): string {
		return 'sc_ei_lock_' . substr( hash_hmac( 'sha256', strtolower( $request_id ), wp_salt( 'auth' ) ), 0, 32 );
	}

	private static function request_success_key( string $request_id ): string {
		return 'sc_ei_success_' . substr( hash_hmac( 'sha256', strtolower( $request_id ), wp_salt( 'auth' ) ), 0, 32 );
	}

	private static function validate_timing( array $raw ) {
		$started   = isset( $raw['form_started_at'] ) ? absint( $raw['form_started_at'] ) : 0;
		$form_id   = sanitize_key( (string) ( $raw['form_id'] ?? '' ) );
		$signature = sanitize_text_field( (string) ( $raw['form_signature'] ?? '' ) );

		if ( ! $started || ! $form_id || ! hash_equals( self::timing_signature( $started, $form_id ), $signature ) ) {
			return new WP_Error( 'form_expired', __( 'The form session is invalid. Reload the page and try again.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() );
		$minimum  = max( 1, absint( $settings['minimum_completion_seconds'] ?? 3 ) );
		$elapsed  = time() - $started;

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
