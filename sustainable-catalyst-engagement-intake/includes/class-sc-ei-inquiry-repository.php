<?php
/**
 * Inquiry persistence.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Inquiry_Repository {

	public static function create( array $input ): int {
		global $wpdb;

		$now    = current_time( 'mysql', true );
		$status = sanitize_key( $input['status'] ?? 'new' );
		$type   = sanitize_key( $input['inquiry_type'] ?? 'general' );

		if ( ! SC_EI_Statuses::is_valid( $status ) ) {
			$status = 'new';
		}
		if ( ! array_key_exists( $type, SC_EI_Statuses::inquiry_types() ) ) {
			$type = 'other';
		}

		$form_variant     = SC_EI_Conversion::sanitize_variant( (string) ( $input['form_variant'] ?? 'advanced' ) );
		$source_page      = SC_EI_Conversion::sanitize_source( (string) ( $input['source_page'] ?? 'other' ) );
		$entry_cta        = SC_EI_Conversion::sanitize_entry_cta( (string) ( $input['entry_cta'] ?? 'unspecified' ) );
		$conversion_route = SC_EI_Conversion::route( $type, (string) ( $input['service_interest'] ?? '' ), $form_variant );
		$guidance_flags   = SC_EI_Conversion::guidance_flags(
			(string) ( $input['service_interest'] ?? '' ),
			(string) ( $input['budget_range'] ?? '' ),
			(string) ( $input['message'] ?? '' )
		);

		$contact_method   = sanitize_key( $input['preferred_contact_method'] ?? 'email' );
		$meeting_request  = sanitize_key( $input['meeting_request'] ?? 'no' );
		$scheduling_state = sanitize_key( $input['scheduling_status'] ?? ( 'no' === $meeting_request ? 'not_requested' : 'requested' ) );

		if ( ! array_key_exists( $contact_method, SC_EI_Teams::contact_methods() ) ) {
			$contact_method = 'email';
		}
		if ( ! array_key_exists( $meeting_request, SC_EI_Teams::meeting_requests() ) ) {
			$meeting_request = 'no';
		}
		if ( ! array_key_exists( $scheduling_state, SC_EI_Teams::scheduling_statuses() ) ) {
			$scheduling_state = 'no' === $meeting_request ? 'not_requested' : 'requested';
		}

		$weekdays          = SC_EI_Teams::sanitize_weekdays( $input['preferred_weekdays'] ?? array() );
		$participant_emails= SC_EI_Teams::sanitize_participant_emails( $input['participant_emails'] ?? array() );
		$timezone          = sanitize_text_field( $input['timezone'] ?? '' );
		if ( $timezone && ! SC_EI_Teams::valid_timezone( $timezone ) ) {
			$timezone = '';
		}

		$initial_assignee = ! empty( $input['assigned_user_id'] ) ? absint( $input['assigned_user_id'] ) : 0;
		$initial_priority = SC_EI_Review_Schema::sanitize_choice(
			(string) ( $input['review_priority'] ?? 'normal' ),
			SC_EI_Review_Schema::priorities(),
			'normal'
		);

		$privacy_settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Privacy_Schema::default_settings() );
		$initial_retention_until = gmdate(
			'Y-m-d H:i:s',
			time() + max( 30, absint( $privacy_settings['default_unaccepted_retention_days'] ?? 365 ) ) * DAY_IN_SECONDS
		);

		$data = array(
			'public_id'               => wp_generate_uuid4(),
			'reference'               => self::generate_reference(),
			'inquiry_type'            => $type,
			'status'                  => $status,
			'form_variant'            => $form_variant,
			'source_page'             => $source_page,
			'entry_cta'               => $entry_cta,
			'conversion_route'        => $conversion_route,
			'guidance_flags'          => wp_json_encode( $guidance_flags ),
			'contact_name'            => sanitize_text_field( $input['contact_name'] ?? '' ),
			'contact_email'           => sanitize_email( $input['contact_email'] ?? '' ),
			'organization'            => sanitize_text_field( $input['organization'] ?? '' ),
			'role_title'              => sanitize_text_field( $input['role_title'] ?? '' ),
			'subject'                 => sanitize_text_field( $input['subject'] ?? '' ),
			'message'                 => sanitize_textarea_field( $input['message'] ?? '' ),
			'project_summary'         => sanitize_textarea_field( $input['project_summary'] ?? '' ),
			'desired_outcome'         => sanitize_textarea_field( $input['desired_outcome'] ?? '' ),
			'service_interest'        => sanitize_text_field( $input['service_interest'] ?? '' ),
			'budget_range'            => sanitize_text_field( $input['budget_range'] ?? '' ),
			'desired_start_date'      => self::sanitize_date( $input['desired_start_date'] ?? null ),
			'deadline_date'           => self::sanitize_date( $input['deadline_date'] ?? null ),
			'preferred_contact_method'=> $contact_method,
			'teams_email'             => sanitize_email( $input['teams_email'] ?? '' ),
			'phone_number'            => sanitize_text_field( $input['phone_number'] ?? '' ),
			'timezone'                => $timezone,
			'city'                    => sanitize_text_field( $input['city'] ?? '' ),
			'country'                 => sanitize_text_field( $input['country'] ?? '' ),
			'meeting_request'         => $meeting_request,
			'preferred_weekdays'      => wp_json_encode( $weekdays ),
			'preferred_time_windows'  => sanitize_textarea_field( $input['preferred_time_windows'] ?? '' ),
			'preferred_duration'      => max( 0, min( 180, absint( $input['preferred_duration'] ?? 0 ) ) ),
			'participant_count'       => max( 1, min( 50, absint( $input['participant_count'] ?? 1 ) ) ),
			'participant_emails'      => wp_json_encode( $participant_emails ),
			'accessibility_needs'     => sanitize_textarea_field( $input['accessibility_needs'] ?? '' ),
			'calendar_invite_consent' => empty( $input['calendar_invite_consent'] ) ? 0 : 1,
			'scheduling_notes'        => sanitize_textarea_field( $input['scheduling_notes'] ?? '' ),
			'scheduling_status'       => $scheduling_state,
			'teams_meeting_url'       => '',
			'scheduled_start_utc'     => null,
			'scheduled_end_utc'       => null,
			'scheduled_timezone'      => $timezone,
			'calendar_event_id'       => '',
			'relevant_links'          => self::sanitize_links_json( $input['relevant_links'] ?? array() ),
			'metadata_json'           => wp_json_encode( self::sanitize_metadata( $input['metadata'] ?? array() ) ),
			'consent_version'         => sanitize_text_field( $input['consent_version'] ?? '' ),
			'consent_at'              => ! empty( $input['consent_at'] ) ? sanitize_text_field( $input['consent_at'] ) : null,
			'assigned_user_id'        => $initial_assignee ?: null,
			'assignment_at'           => $initial_assignee ? $now : null,
			'assignment_by'           => $initial_assignee ? absint( $input['assignment_by'] ?? 0 ) : null,
			'review_stage'            => 'intake',
			'review_priority'         => $initial_priority,
			'review_due_at'           => SC_EI_Review_Schema::default_due_at( $initial_priority ),
			'fit_decision'            => 'undecided',
			'fit_confidence'          => 'unassessed',
			'risk_level'              => 'unassessed',
			'evidence_readiness'      => 'not_assessed',
			'scope_clarity'           => 'not_assessed',
			'recommended_next_step'   => 'review',
			'review_summary'          => '',
			'decision_rationale'      => '',
			'information_gaps'        => '',
			'conflict_notes'          => '',
			'review_checklist'        => wp_json_encode( SC_EI_Review_Schema::sanitize_checklist( array() ) ),
			'escalation_status'       => 'none',
			'escalation_reason'       => '',
			'review_started_at'       => null,
			'last_reviewed_at'        => null,
			'last_reviewed_by'        => null,
			'decision_at'             => null,
			'review_completed_at'     => null,
			'review_version'          => 0,
			'communication_status'    => 'open',
			'next_follow_up_at'       => null,
			'last_communication_at'   => null,
			'last_outbound_at'        => null,
			'last_inbound_at'         => null,
			'last_notification_at'    => null,
			'communication_count'     => 0,
			'unread_inbound_count'    => 0,
			'do_not_email'            => 0,
			'do_not_email_reason'     => '',
			'communication_version'   => 0,
			'privacy_status'          => 'active',
			'retention_policy_key'    => 'unaccepted_inquiry',
			'retention_until'         => $initial_retention_until,
			'legal_hold_count'        => 0,
			'privacy_restriction_reason' => '',
			'last_privacy_review_at'  => null,
			'last_privacy_review_by'  => null,
			'personal_data_erased_at' => null,
			'privacy_version'         => 0,
			'fit_assessment_status'   => 'not_started',
			'current_fit_assessment_id' => null,
			'fit_assessment_updated_at' => null,
			'fit_assessment_finalized_at' => null,
			'fit_assessment_version'  => 0,
			'portal_status'           => 'inactive',
			'portal_access_id'        => null,
			'portal_last_activity_at' => null,
			'portal_message_count'    => 0,
			'portal_document_count'   => 0,
			'portal_last_sender_message_at' => null,
			'sender_withdrawal_status' => 'none',
			'sender_withdrawal_requested_at' => null,
			'sender_withdrawal_reason'=> '',
			'portal_version'          => 0,
			'lifecycle_stage'          => SC_EI_Lifecycle_Schema::map_legacy_status( $status ),
			'lifecycle_owner_user_id'  => $initial_assignee ?: null,
			'lifecycle_priority'       => $initial_priority,
			'next_action'              => __( 'Review the new inquiry and determine the next human action.', 'sustainable-catalyst-engagement-intake' ),
			'next_action_at'           => SC_EI_Review_Schema::default_due_at( $initial_priority ),
			'qualification_status'     => 'not_started',
			'qualification_score'      => 0,
			'qualification_json'       => wp_json_encode( array(), JSON_UNESCAPED_SLASHES ),
			'decision_authority'       => 'unknown',
			'funding_status'           => 'unknown',
			'stakeholder_summary'      => '',
			'systems_constraints'      => '',
			'data_security_requirements'=> '',
			'ai_assurance_applicable' => 'unknown',
			'teams_readiness'         => 'not_assessed',
			'sender_lifecycle_summary'=> __( 'Your inquiry has been received and is awaiting review.', 'sustainable-catalyst-engagement-intake' ),
			'lifecycle_version'        => 0,
			'lifecycle_updated_at'     => $now,
			'lifecycle_updated_by'     => null,
			'created_at'              => $now,
			'updated_at'              => $now,
			'closed_at'               => null,
		);

		$integer_fields = array(
			'preferred_duration',
			'participant_count',
			'calendar_invite_consent',
			'assigned_user_id',
			'assignment_by',
			'last_reviewed_by',
			'review_version',
			'communication_count',
			'unread_inbound_count',
			'do_not_email',
			'communication_version',
			'legal_hold_count',
			'last_privacy_review_by',
			'privacy_version',
			'current_fit_assessment_id',
			'fit_assessment_version',
			'portal_access_id',
			'portal_message_count',
			'portal_document_count',
			'portal_version',
			'lifecycle_owner_user_id',
			'qualification_score',
			'lifecycle_version',
			'lifecycle_updated_by',
		);
		$formats = array_map(
			static fn( string $key ): string => in_array( $key, $integer_fields, true ) ? '%d' : '%s',
			array_keys( $data )
		);

		$inserted = $wpdb->insert( SC_EI_Database::table( 'inquiries' ), $data, $formats );
		if ( false === $inserted ) {
			$request_id = class_exists( 'SC_EI_Hardening_Repository' )
				? SC_EI_Hardening_Repository::request_id()
				: wp_generate_uuid4();
			$database_error = isset( $wpdb->last_error ) ? trim( (string) $wpdb->last_error ) : '';
			if ( class_exists( 'SC_EI_Hardening_Repository' ) ) {
				SC_EI_Hardening_Repository::record_event(
					'database',
					'inquiry_insert_failed',
					'critical',
					'An inquiry record could not be written to the private inquiry table.',
					array(
						'request_id'          => $request_id,
						'database_error'      => mb_substr( $database_error, 0, 500 ),
						'database_error_hash' => hash( 'sha256', $database_error ),
						'plugin_version'      => defined( 'SC_EI_VERSION' ) ? SC_EI_VERSION : '',
						'database_version'    => defined( 'SC_EI_DB_VERSION' ) ? SC_EI_DB_VERSION : '',
					)
				);
			}
			throw new RuntimeException( 'Unable to create inquiry record. Reliability reference: ' . $request_id );
		}

		$id = (int) $wpdb->insert_id;

		SC_EI_Audit_Log::record(
			'inquiry_created',
			'Private inquiry record created.',
			array(
				'reference'                => $data['reference'],
				'inquiry_type'             => $type,
				'status'                   => $status,
				'form_variant'             => $form_variant,
				'source_page'              => $source_page,
				'entry_cta'                => $entry_cta,
				'conversion_route'         => $conversion_route,
				'guidance_flags'           => $guidance_flags,
				'preferred_contact_method' => $contact_method,
				'meeting_request'          => $meeting_request,
				'scheduling_status'        => $scheduling_state,
				'review_stage'             => 'intake',
				'review_priority'          => $initial_priority,
				'review_due_at'            => $data['review_due_at'],
				'communication_status'      => 'open',
				'privacy_status'            => 'active',
				'fit_assessment_status'     => 'not_started',
				'portal_status'             => 'inactive',
				'lifecycle_stage'           => SC_EI_Lifecycle_Schema::map_legacy_status( $status ),
				'lifecycle_priority'        => $initial_priority,
				'qualification_status'      => 'not_started',
				'sender_withdrawal_status'  => 'none',
				'retention_policy_key'      => 'unaccepted_inquiry',
				'retention_until'           => $initial_retention_until,
			),
			$id
		);

		return $id;
	}

	public static function find( int $id ): ?array {
		global $wpdb;

		$table = SC_EI_Database::table( 'inquiries' );
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return $row ?: null;
	}

	public static function find_by_reference( string $reference ): ?array {
		global $wpdb;

		$table = SC_EI_Database::table( 'inquiries' );
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE reference = %s", sanitize_text_field( $reference ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return $row ?: null;
	}

	public static function query( array $args = array() ): array {
		global $wpdb;

		$defaults = array(
			'status'            => '',
			'inquiry_type'      => '',
			'scheduling_status' => '',
			'form_variant'      => '',
			'source_page'       => '',
			'conversion_route'  => '',
			'lifecycle_stage'   => '',
			'lifecycle_owner_user_id' => 0,
			'lifecycle_priority'=> '',
			'search'            => '',
			'page'              => 1,
			'per_page'          => 20,
			'orderby'           => 'created_at',
			'order'             => 'DESC',
		);
		$args     = wp_parse_args( $args, $defaults );
		$table    = SC_EI_Database::table( 'inquiries' );
		$where    = array( '1=1' );
		$params   = array();

		if ( $args['status'] && SC_EI_Statuses::is_valid( sanitize_key( $args['status'] ) ) ) {
			$where[]  = 'status = %s';
			$params[] = sanitize_key( $args['status'] );
		}
		if ( $args['inquiry_type'] && array_key_exists( sanitize_key( $args['inquiry_type'] ), SC_EI_Statuses::inquiry_types() ) ) {
			$where[]  = 'inquiry_type = %s';
			$params[] = sanitize_key( $args['inquiry_type'] );
		}
		if ( $args['scheduling_status'] && array_key_exists( sanitize_key( $args['scheduling_status'] ), SC_EI_Teams::scheduling_statuses() ) ) {
			$where[]  = 'scheduling_status = %s';
			$params[] = sanitize_key( $args['scheduling_status'] );
		}
		if ( $args['form_variant'] && array_key_exists( sanitize_key( $args['form_variant'] ), SC_EI_Conversion::variants() ) ) {
			$where[]  = 'form_variant = %s';
			$params[] = sanitize_key( $args['form_variant'] );
		}
		if ( $args['source_page'] ) {
			$where[]  = 'source_page = %s';
			$params[] = SC_EI_Conversion::sanitize_source( (string) $args['source_page'] );
		}
		if ( $args['conversion_route'] ) {
			$where[]  = 'conversion_route = %s';
			$params[] = sanitize_key( $args['conversion_route'] );
		}
		if ( $args['lifecycle_stage'] && isset( SC_EI_Lifecycle_Schema::stages()[ sanitize_key( $args['lifecycle_stage'] ) ] ) ) {
			$where[] = 'lifecycle_stage = %s';
			$params[] = sanitize_key( $args['lifecycle_stage'] );
		}
		if ( ! empty( $args['lifecycle_owner_user_id'] ) ) {
			$where[] = 'lifecycle_owner_user_id = %d';
			$params[] = absint( $args['lifecycle_owner_user_id'] );
		}
		if ( $args['lifecycle_priority'] && isset( SC_EI_Lifecycle_Schema::priorities()[ sanitize_key( $args['lifecycle_priority'] ) ] ) ) {
			$where[] = 'lifecycle_priority = %s';
			$params[] = sanitize_key( $args['lifecycle_priority'] );
		}
		if ( $args['search'] ) {
			$like     = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where[]  = '(reference LIKE %s OR contact_name LIKE %s OR contact_email LIKE %s OR teams_email LIKE %s OR organization LIKE %s OR subject LIKE %s OR source_page LIKE %s OR conversion_route LIKE %s)';
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
			$params[] = $like;
		}

		$allowed_orderby = array( 'created_at', 'updated_at', 'status', 'scheduling_status', 'form_variant', 'source_page', 'conversion_route', 'contact_name', 'organization', 'reference', 'review_stage', 'review_priority', 'review_due_at', 'fit_decision', 'risk_level', 'last_reviewed_at', 'communication_status', 'next_follow_up_at', 'last_communication_at', 'privacy_status', 'retention_until', 'legal_hold_count', 'fit_assessment_status', 'fit_assessment_updated_at', 'fit_assessment_finalized_at', 'portal_status', 'portal_last_activity_at', 'portal_last_sender_message_at', 'sender_withdrawal_status', 'lifecycle_stage', 'lifecycle_owner_user_id', 'lifecycle_priority', 'next_action_at', 'qualification_status', 'lifecycle_updated_at' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
		$per_page        = max( 1, min( 100, absint( $args['per_page'] ) ) );
		$page            = max( 1, absint( $args['page'] ) );
		$offset          = ( $page - 1 ) * $per_page;

		$where_sql = implode( ' AND ', $where );
		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$data_sql  = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$count_params = $params;
		$data_params  = array_merge( $params, array( $per_page, $offset ) );

		$total = $params
			? (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $count_params ) ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			: (int) $wpdb->get_var( $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$items = (array) $wpdb->get_results(
			$wpdb->prepare( $data_sql, $data_params ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			ARRAY_A
		);

		return array(
			'items'       => $items,
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => (int) ceil( $total / $per_page ),
		);
	}

	public static function update_status( int $id, string $new_status, string $note = '' ): bool {
		global $wpdb;

		$new_status = sanitize_key( $new_status );
		if ( ! SC_EI_Statuses::is_valid( $new_status ) ) {
			return false;
		}

		$current = self::find( $id );
		if ( ! $current ) {
			return false;
		}

		$now       = current_time( 'mysql', true );
		$closed_at = in_array( $new_status, array( 'closed', 'not_a_fit', 'referred', 'withdrawn' ), true ) ? $now : null;
		$privacy_settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Privacy_Schema::default_settings() );

		if ( 'withdrawn' === $new_status ) {
			$policy_key = 'withdrawn_inquiry';
			$retention_days = absint( $privacy_settings['withdrawn_retention_days'] ?? 30 );
		} elseif ( in_array( $new_status, array( 'closed', 'not_a_fit', 'referred' ), true ) ) {
			$policy_key = 'closed_inquiry';
			$retention_days = absint( $privacy_settings['closed_retention_days'] ?? 365 );
		} elseif ( 'accepted' === $new_status ) {
			$policy_key = 'accepted_inquiry';
			$retention_days = absint( $privacy_settings['accepted_retention_days'] ?? 2555 );
		} else {
			$policy_key = 'unaccepted_inquiry';
			$retention_days = absint( $privacy_settings['default_unaccepted_retention_days'] ?? 365 );
		}
		$retention_until = gmdate( 'Y-m-d H:i:s', time() + max( 1, $retention_days ) * DAY_IN_SECONDS );

		$updated = $wpdb->update(
			SC_EI_Database::table( 'inquiries' ),
			array(
				'status'               => $new_status,
				'updated_at'           => $now,
				'closed_at'            => $closed_at,
				'retention_policy_key' => $policy_key,
				'retention_until'      => $retention_until,
				'privacy_version'      => absint( $current['privacy_version'] ?? 0 ) + 1,
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%s', '%s', '%s', '%d' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return false;
		}

		SC_EI_Audit_Log::record(
			'status_changed',
			$note ? $note : 'Inquiry status changed.',
			array(
				'old_status' => $current['status'],
				'new_status'       => $new_status,
				'retention_policy' => $policy_key,
				'retention_until'  => $retention_until,
			),
			$id
		);

		return true;
	}

	public static function update_scheduling( int $id, array $input ): bool {
		global $wpdb;

		$current = self::find( $id );
		if ( ! $current ) {
			return false;
		}

		$status = sanitize_key( $input['scheduling_status'] ?? '' );
		if ( ! array_key_exists( $status, SC_EI_Teams::scheduling_statuses() ) ) {
			return false;
		}

		$timezone = sanitize_text_field( $input['scheduled_timezone'] ?? $current['timezone'] ?? '' );
		if ( ! SC_EI_Teams::valid_timezone( $timezone ) ) {
			$timezone = wp_timezone_string();
		}
		if ( ! SC_EI_Teams::valid_timezone( $timezone ) ) {
			$timezone = 'UTC';
		}

		$teams_url = esc_url_raw( $input['teams_meeting_url'] ?? '' );
		if ( $teams_url && ! SC_EI_Teams::is_teams_url( $teams_url ) ) {
			return false;
		}

		$start_utc = SC_EI_Teams::local_to_utc( (string) ( $input['scheduled_start_local'] ?? '' ), $timezone );
		$end_utc   = SC_EI_Teams::local_to_utc( (string) ( $input['scheduled_end_local'] ?? '' ), $timezone );

		if ( $start_utc && $end_utc && strtotime( $end_utc . ' UTC' ) <= strtotime( $start_utc . ' UTC' ) ) {
			return false;
		}

		if ( 'scheduled' === $status ) {
			if ( empty( $current['calendar_invite_consent'] ) || ! $teams_url || ! $start_utc || ! $end_utc ) {
				return false;
			}
		}

		$data = array(
			'scheduling_status'   => $status,
			'teams_meeting_url'   => $teams_url,
			'scheduled_start_utc' => $start_utc,
			'scheduled_end_utc'   => $end_utc,
			'scheduled_timezone'  => $timezone,
			'calendar_event_id'   => sanitize_text_field( $input['calendar_event_id'] ?? '' ),
			'updated_at'          => current_time( 'mysql', true ),
		);

		$updated = $wpdb->update(
			SC_EI_Database::table( 'inquiries' ),
			$data,
			array( 'id' => $id ),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return false;
		}

		SC_EI_Audit_Log::record(
			'teams_scheduling_updated',
			sanitize_textarea_field( $input['scheduling_admin_note'] ?? 'Microsoft Teams scheduling record updated.' ),
			array(
				'old_status'          => $current['scheduling_status'],
				'new_status'          => $status,
				'has_teams_url'       => ! empty( $teams_url ),
				'scheduled_start_utc' => $start_utc,
				'scheduled_end_utc'   => $end_utc,
				'scheduled_timezone'  => $timezone,
			),
			$id
		);

		return true;
	}

	public static function add_internal_note( int $id, string $note ): int {
		$note = sanitize_textarea_field( $note );
		if ( '' === $note ) {
			return 0;
		}

		return SC_EI_Audit_Log::record(
			'internal_note',
			$note,
			array( 'visibility' => 'private' ),
			$id
		);
	}


	/**
	 * Roll back a newly inserted public inquiry when its required support-case companion cannot be persisted.
	 *
	 * The request identifier must match the inquiry metadata and the record must still be in its initial state.
	 */
	public static function rollback_public_create( int $inquiry_id, string $request_id, string $reason ): bool {
		global $wpdb;
		$record = self::find( $inquiry_id );
		if ( ! $record || 'new' !== (string) ( $record['status'] ?? '' ) ) {
			return false;
		}
		$metadata = json_decode( (string) ( $record['metadata_json'] ?? '{}' ), true );
		$stored_request_id = is_array( $metadata ) ? (string) ( $metadata['request_id'] ?? '' ) : '';
		if ( '' === $request_id || ! hash_equals( $stored_request_id, $request_id ) ) {
			return false;
		}
		$deleted = $wpdb->delete( SC_EI_Database::table( 'inquiries' ), array( 'id' => $inquiry_id ), array( '%d' ) );
		if ( 1 === $deleted ) {
			SC_EI_Hardening_Repository::record_event(
				'database',
				'public_support_inquiry_rolled_back',
				'warning',
				'A newly inserted inquiry was removed because its required support case could not be persisted.',
				array( 'request_id' => $request_id, 'reason' => sanitize_key( $reason ) )
			);
			return true;
		}
		return false;
	}

	private static function generate_reference(): string {
		global $wpdb;

		$table = SC_EI_Database::table( 'inquiries' );
		for ( $attempt = 0; $attempt < 10; $attempt++ ) {
			$token     = strtoupper( wp_generate_password( 6, false, false ) );
			$reference = 'SC-' . gmdate( 'Ymd' ) . '-' . $token;
			$exists    = $wpdb->get_var(
				$wpdb->prepare( "SELECT id FROM {$table} WHERE reference = %s LIMIT 1", $reference ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			);
			if ( ! $exists ) {
				return $reference;
			}
		}

		return 'SC-' . gmdate( 'YmdHis' ) . '-' . wp_rand( 1000, 9999 );
	}

	private static function sanitize_date( $value ): ?string {
		if ( empty( $value ) ) {
			return null;
		}

		$value = sanitize_text_field( (string) $value );
		$date  = DateTimeImmutable::createFromFormat( 'Y-m-d', $value );
		return $date && $date->format( 'Y-m-d' ) === $value ? $value : null;
	}

	private static function sanitize_links_json( $links ): string {
		if ( is_string( $links ) ) {
			$links = preg_split( '/\R+/', $links );
		}
		$clean = array();
		foreach ( (array) $links as $link ) {
			$url = esc_url_raw( trim( (string) $link ) );
			if ( $url ) {
				$clean[] = $url;
			}
		}
		return wp_json_encode( array_values( array_unique( $clean ) ) );
	}

	private static function sanitize_metadata( $metadata ): array {
		$clean = array();
		foreach ( (array) $metadata as $key => $value ) {
			$key = sanitize_key( (string) $key );
			if ( '' === $key ) {
				continue;
			}
			if ( is_scalar( $value ) || null === $value ) {
				$clean[ $key ] = sanitize_text_field( (string) $value );
			}
		}
		return $clean;
	}
}
