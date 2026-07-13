<?php
/**
 * Human-controlled Microsoft Teams scheduling and proposal workflow repository.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Workflow_Repository {

	public static function register(): void {
		add_action( 'sc_ei_workflow_cleanup', array( __CLASS__, 'handle_cleanup' ) );
	}

	public static function schedule(): void {
		if ( ! wp_next_scheduled( 'sc_ei_workflow_cleanup' ) ) {
			wp_schedule_event( time() + 20 * MINUTE_IN_SECONDS, 'hourly', 'sc_ei_workflow_cleanup' );
		}
	}

	public static function unschedule(): void {
		wp_clear_scheduled_hook( 'sc_ei_workflow_cleanup' );
	}

	public static function handle_cleanup(): void {
		update_option( 'sc_ei_last_workflow_cleanup', self::expire_stale(), false );
	}

	public static function settings(): array {
		return wp_parse_args(
			get_option( 'sc_ei_settings', array() ),
			SC_EI_Workflow_Schema::default_settings()
		);
	}

	public static function create_meeting_offer( int $inquiry_id, array $input, int $actor_user_id, bool $publish = true ) {
		global $wpdb;

		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $inquiry || 'erased' === $inquiry['privacy_status'] ) {
			return new WP_Error( 'workflow_inquiry_unavailable', __( 'The inquiry is unavailable for scheduling.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$access = SC_EI_Portal_Repository::access_for_inquiry( $inquiry_id );
		if ( ! $access || ! in_array( $access['status'], array( 'active', 'invited' ), true ) ) {
			return new WP_Error( 'workflow_portal_required', __( 'Active or invited sender portal access is required before publishing meeting times.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$settings = self::settings();
		$timezone = sanitize_text_field( (string) ( $input['timezone'] ?? $inquiry['timezone'] ?: wp_timezone_string() ) );
		$duration = max( 15, min( 240, absint( $input['duration_minutes'] ?? $inquiry['preferred_duration'] ?: 30 ) ) );
		$slots = SC_EI_Workflow_Schema::sanitize_slots(
			$input['slots'] ?? array(),
			$timezone,
			$duration,
			absint( $settings['workflow_max_meeting_slots'] )
		);
		if ( ! $slots ) {
			return new WP_Error( 'workflow_meeting_slots_required', __( 'Provide at least one valid future meeting time.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$teams_url = isset( $input['teams_url'] ) ? esc_url_raw( (string) $input['teams_url'] ) : '';
		if ( $teams_url && ! SC_EI_Teams::is_teams_url( $teams_url ) ) {
			return new WP_Error( 'workflow_teams_url_invalid', __( 'The meeting link must be a supported Microsoft Teams URL.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( $publish && ! empty( $settings['workflow_require_teams_url'] ) && ! $teams_url ) {
			return new WP_Error( 'workflow_teams_url_required', __( 'A Microsoft Teams URL is required before publishing this offer.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$now = current_time( 'mysql', true );
		$expires_at = self::future_datetime(
			(string) ( $input['expires_at'] ?? '' ),
			absint( $settings['workflow_meeting_offer_expiry_days'] )
		);
		$temporary_number = 'MTG-TMP-' . strtoupper( substr( str_replace( '-', '', wp_generate_uuid4() ), 0, 16 ) );
		$data = array(
			'public_id'          => wp_generate_uuid4(),
			'inquiry_id'         => $inquiry_id,
			'access_id'          => absint( $access['id'] ),
			'offer_number'       => $temporary_number,
			'status'             => $publish ? 'offered' : 'draft',
			'title'              => sanitize_text_field( (string) ( $input['title'] ?? 'Microsoft Teams conversation' ) ),
			'purpose'            => sanitize_textarea_field( (string) ( $input['purpose'] ?? '' ) ),
			'duration_minutes'   => $duration,
			'timezone'           => $timezone,
			'slots_json'         => wp_json_encode( $slots ),
			'selected_slot_key'  => '',
			'selected_start_utc' => null,
			'selected_end_utc'   => null,
			'teams_url'          => $teams_url,
			'graph_sync_status'  => 'not_requested',
			'graph_transaction_id'=> '',
			'graph_event_id'     => '',
			'graph_i_cal_uid'    => '',
			'graph_change_key'   => '',
			'graph_etag'         => '',
			'graph_web_link'     => '',
			'graph_join_url'     => '',
			'graph_organizer'    => '',
			'graph_calendar_id'  => '',
			'graph_payload_hash' => '',
			'graph_remote_start_utc'=> null,
			'graph_remote_end_utc'=> null,
			'graph_last_request_id'=> '',
			'graph_last_client_request_id'=> '',
			'graph_last_error_code'=> '',
			'graph_last_error_message'=> '',
			'graph_attempt_count'=> 0,
			'graph_last_attempt_at'=> null,
			'graph_last_success_at'=> null,
			'graph_next_retry_at'=> null,
			'graph_reconciled_at'=> null,
			'graph_deleted_at'  => null,
			'sender_note'        => '',
			'alternative_request'=> '',
			'admin_note'         => sanitize_textarea_field( (string) ( $input['admin_note'] ?? '' ) ),
			'expires_at'         => $expires_at,
			'published_by'       => $publish ? $actor_user_id : null,
			'published_at'       => $publish ? $now : null,
			'responded_at'       => null,
			'finalized_by'       => null,
			'finalized_at'       => null,
			'completed_at'       => null,
			'canceled_at'        => null,
			'cancellation_reason'=> '',
			'row_version'        => 0,
			'created_by'         => $actor_user_id,
			'created_at'         => $now,
			'updated_at'         => $now,
		);
		$inserted = $wpdb->insert(
			SC_EI_Database::table( 'meeting_offers' ),
			$data,
			self::formats( $data, self::meeting_integer_fields() )
		);
		if ( false === $inserted ) {
			return new WP_Error( 'workflow_meeting_save_failed', __( 'The meeting offer could not be saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$id = (int) $wpdb->insert_id;
		$offer_number = 'MTG-' . gmdate( 'Ym' ) . '-' . str_pad( (string) $id, 5, '0', STR_PAD_LEFT );
		$wpdb->update(
			SC_EI_Database::table( 'meeting_offers' ),
			array( 'offer_number' => $offer_number ),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);

		self::record_event(
			$inquiry_id,
			'staff',
			$actor_user_id,
			'meeting',
			$id,
			$publish ? 'meeting_offer_published' : 'meeting_draft_created',
			'',
			$data['status'],
			array(
				'offer_number'      => $offer_number,
				'slot_count'        => count( $slots ),
				'automatic_booking' => false,
				'automatic_email'   => false,
			)
		);
		if ( $publish ) {
			self::supersede_other_meeting_offers( $inquiry_id, $id );
			self::update_inquiry_scheduling(
				$inquiry,
				array(
					'scheduling_status' => 'times_proposed',
					'meeting_request'    => 'yes',
				)
			);
			SC_EI_Portal_Repository::create_portal_message(
				$inquiry_id,
				'outbound',
				sprintf(
					/* translators: %s meeting offer number. */
					__( 'Microsoft Teams meeting times are available in the Meetings section. Offer: %s. No meeting is booked until you select a time.', 'sustainable-catalyst-engagement-intake' ),
					$offer_number
				),
				$actor_user_id
			);
		}
		SC_EI_Audit_Log::record(
			$publish ? 'workflow_meeting_offer_published' : 'workflow_meeting_draft_created',
			$publish
				? 'Authorized staff published human-approved Microsoft Teams meeting times to the sender portal.'
				: 'Authorized staff created a draft Microsoft Teams meeting offer.',
			array(
				'meeting_offer_id' => $id,
				'offer_number'     => $offer_number,
				'automatic_booking'=> false,
				'automatic_email'  => false,
			),
			$inquiry_id,
			null,
			$actor_user_id
		);
		return self::find_meeting_offer( $id );
	}

	public static function publish_meeting_offer( int $id, int $actor_user_id ) {
		global $wpdb;

		$offer = self::find_meeting_offer( $id );
		if ( ! $offer || 'draft' !== $offer['status'] ) {
			return new WP_Error( 'workflow_meeting_not_draft', __( 'Only a draft meeting offer can be published.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$teams_url = (string) $offer['teams_url'];
		if ( $teams_url && ! SC_EI_Teams::is_teams_url( $teams_url ) ) {
			return new WP_Error( 'workflow_teams_url_invalid', __( 'The stored Microsoft Teams URL is not valid.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( ! empty( self::settings()['workflow_require_teams_url'] ) && ! $teams_url ) {
			return new WP_Error( 'workflow_teams_url_required', __( 'Add a Microsoft Teams URL before publishing.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		$updated = $wpdb->update(
			SC_EI_Database::table( 'meeting_offers' ),
			array(
				'status'       => 'offered',
				'published_by' => $actor_user_id,
				'published_at' => $now,
				'row_version'  => absint( $offer['row_version'] ) + 1,
				'updated_at'   => $now,
			),
			array(
				'id'          => $id,
				'row_version' => absint( $offer['row_version'] ),
				'status'      => 'draft',
			),
			array( '%s', '%d', '%s', '%d', '%s' ),
			array( '%d', '%d', '%s' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'workflow_meeting_conflict', __( 'The meeting offer changed before publication.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::supersede_other_meeting_offers( absint( $offer['inquiry_id'] ), $id );
		$inquiry = SC_EI_Inquiry_Repository::find( absint( $offer['inquiry_id'] ) );
		if ( $inquiry ) {
			self::update_inquiry_scheduling( $inquiry, array( 'scheduling_status' => 'times_proposed', 'meeting_request' => 'yes' ) );
		}
		self::record_event( absint( $offer['inquiry_id'] ), 'staff', $actor_user_id, 'meeting', $id, 'meeting_offer_published', 'draft', 'offered', array( 'automatic_booking' => false ) );
		return self::find_meeting_offer( $id );
	}

	public static function respond_to_meeting( int $id, string $response, string $slot_key, string $note, int $session_id ) {
		global $wpdb;

		$offer = self::find_meeting_offer( $id );
		if ( ! $offer || 'offered' !== $offer['status'] ) {
			return new WP_Error( 'workflow_meeting_unavailable', __( 'This meeting offer is no longer available.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( strtotime( $offer['expires_at'] . ' UTC' ) < time() ) {
			self::expire_meeting( $offer );
			return new WP_Error( 'workflow_meeting_expired', __( 'This meeting offer expired.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$response = sanitize_key( $response );
		$note = sanitize_textarea_field( $note );
		$from = $offer['status'];
		$now = current_time( 'mysql', true );
		$data = array(
			'responded_at' => $now,
			'sender_note'  => $note,
			'row_version'  => absint( $offer['row_version'] ) + 1,
			'updated_at'   => $now,
		);
		$event = '';
		if ( 'accept' === $response ) {
			$slot = self::find_slot( $offer, $slot_key );
			if ( ! $slot ) {
				return new WP_Error( 'workflow_slot_invalid', __( 'Choose one of the offered meeting times.', 'sustainable-catalyst-engagement-intake' ) );
			}
			if ( strtotime( $slot['start_utc'] . ' UTC' ) <= time() ) {
				return new WP_Error( 'workflow_slot_elapsed', __( 'That meeting time has already passed.', 'sustainable-catalyst-engagement-intake' ) );
			}
			$status = $offer['teams_url'] && SC_EI_Teams::is_teams_url( $offer['teams_url'] )
				? 'scheduled'
				: 'accepted_pending_link';
			$data += array(
				'status'             => $status,
				'selected_slot_key'  => $slot['key'],
				'selected_start_utc' => $slot['start_utc'],
				'selected_end_utc'   => $slot['end_utc'],
				'alternative_request'=> '',
			);
			$event = 'meeting_time_accepted';
		} elseif ( 'alternative_request' === $response ) {
			if ( mb_strlen( $note ) < 5 ) {
				return new WP_Error( 'workflow_alternative_note_required', __( 'Describe the alternative time you need.', 'sustainable-catalyst-engagement-intake' ) );
			}
			$data += array(
				'status'              => 'alternative_requested',
				'alternative_request' => $note,
			);
			$event = 'meeting_alternative_requested';
		} elseif ( 'decline' === $response ) {
			$data += array( 'status' => 'declined' );
			$event = 'meeting_declined';
		} else {
			return new WP_Error( 'workflow_meeting_response_invalid', __( 'Choose a valid meeting response.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$updated = $wpdb->update(
			SC_EI_Database::table( 'meeting_offers' ),
			$data,
			array(
				'id'          => $id,
				'row_version' => absint( $offer['row_version'] ),
				'status'      => 'offered',
			),
			self::formats( $data, self::meeting_integer_fields() ),
			array( '%d', '%d', '%s' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'workflow_meeting_conflict', __( 'The meeting offer changed before your response was saved.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$new = self::find_meeting_offer( $id );
		$inquiry = SC_EI_Inquiry_Repository::find( absint( $offer['inquiry_id'] ) );
		if ( $inquiry ) {
			$inquiry_data = array();
			if ( 'accept' === $response ) {
				$inquiry_data = array(
					'scheduling_status'  => 'scheduled' === $new['status'] ? 'scheduled' : 'approved',
					'scheduled_start_utc'=> $new['selected_start_utc'],
					'scheduled_end_utc'  => $new['selected_end_utc'],
					'scheduled_timezone' => $new['timezone'],
					'teams_meeting_url'  => $new['teams_url'],
				);
			} elseif ( 'alternative_request' === $response ) {
				$inquiry_data = array( 'scheduling_status' => 'under_review', 'scheduling_notes' => $note );
			} else {
				$inquiry_data = array( 'scheduling_status' => 'declined' );
			}
			self::update_inquiry_scheduling( $inquiry, $inquiry_data );
		}

		self::record_event(
			absint( $offer['inquiry_id'] ),
			'sender',
			$session_id,
			'meeting',
			$id,
			$event,
			$from,
			$new['status'],
			array(
				'slot_key'          => $slot_key,
				'automatic_booking' => false,
				'teams_link_ready'  => ! empty( $new['teams_url'] ),
			)
		);
		SC_EI_Audit_Log::record(
			'workflow_' . $event,
			'Sender recorded a Microsoft Teams scheduling response in the secure portal.',
			array(
				'meeting_offer_id' => $id,
				'response'         => $response,
				'automatic_booking'=> false,
			),
			absint( $offer['inquiry_id'] )
		);
		return $new;
	}

	public static function finalize_meeting( int $id, string $teams_url, int $actor_user_id ) {
		global $wpdb;

		$offer = self::find_meeting_offer( $id );
		if ( ! $offer || ! in_array( $offer['status'], array( 'accepted_pending_link', 'scheduled' ), true ) ) {
			return new WP_Error( 'workflow_meeting_not_accepted', __( 'The sender must accept a proposed time before final scheduling.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$teams_url = esc_url_raw( $teams_url );
		if ( ! SC_EI_Teams::is_teams_url( $teams_url ) ) {
			return new WP_Error( 'workflow_teams_url_invalid', __( 'Provide a supported Microsoft Teams URL.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		$data = array(
			'status'       => 'scheduled',
			'teams_url'    => $teams_url,
			'finalized_by' => $actor_user_id,
			'finalized_at' => $now,
			'row_version'  => absint( $offer['row_version'] ) + 1,
			'updated_at'   => $now,
		);
		$updated = $wpdb->update(
			SC_EI_Database::table( 'meeting_offers' ),
			$data,
			array( 'id' => $id, 'row_version' => absint( $offer['row_version'] ) ),
			self::formats( $data, self::meeting_integer_fields() ),
			array( '%d', '%d' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'workflow_meeting_conflict', __( 'The meeting changed before it could be finalized.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$inquiry = SC_EI_Inquiry_Repository::find( absint( $offer['inquiry_id'] ) );
		if ( $inquiry ) {
			self::update_inquiry_scheduling(
				$inquiry,
				array(
					'scheduling_status'   => 'scheduled',
					'teams_meeting_url'   => $teams_url,
					'scheduled_start_utc' => $offer['selected_start_utc'],
					'scheduled_end_utc'   => $offer['selected_end_utc'],
					'scheduled_timezone'  => $offer['timezone'],
				)
			);
		}
		self::record_event( absint( $offer['inquiry_id'] ), 'staff', $actor_user_id, 'meeting', $id, 'meeting_finalized', $offer['status'], 'scheduled', array( 'automatic_calendar' => false ) );
		SC_EI_Portal_Repository::create_portal_message(
			absint( $offer['inquiry_id'] ),
			'outbound',
			sprintf(
				/* translators: %s meeting offer number. */
				__( 'Your Microsoft Teams meeting is finalized. Open the Meetings section for the link and calendar file. Offer: %s.', 'sustainable-catalyst-engagement-intake' ),
				$offer['offer_number']
			),
			$actor_user_id
		);
		return self::find_meeting_offer( $id );
	}

	public static function change_meeting_status( int $id, string $status, string $reason, int $actor_user_id ) {
		global $wpdb;

		$offer = self::find_meeting_offer( $id );
		if ( ! $offer ) {
			return new WP_Error( 'workflow_meeting_not_found', __( 'The meeting offer could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$status = sanitize_key( $status );
		if ( ! in_array( $status, array( 'completed', 'canceled', 'superseded' ), true ) ) {
			return new WP_Error( 'workflow_meeting_status_invalid', __( 'Choose a valid administrative meeting status.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$reason = sanitize_textarea_field( $reason );
		if ( 'canceled' === $status && '' === trim( $reason ) ) {
			return new WP_Error( 'workflow_meeting_reason_required', __( 'Record why the meeting was canceled.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		$data = array(
			'status'              => $status,
			'cancellation_reason' => 'canceled' === $status ? $reason : $offer['cancellation_reason'],
			'graph_sync_status'   => ( 'canceled' === $status && ! empty( $offer['graph_event_id'] ) && empty( $offer['graph_deleted_at'] ) )
				? 'cancel_required'
				: $offer['graph_sync_status'],
			'completed_at'        => 'completed' === $status ? $now : $offer['completed_at'],
			'canceled_at'         => 'canceled' === $status ? $now : $offer['canceled_at'],
			'row_version'         => absint( $offer['row_version'] ) + 1,
			'updated_at'          => $now,
		);
		$updated = $wpdb->update(
			SC_EI_Database::table( 'meeting_offers' ),
			$data,
			array( 'id' => $id, 'row_version' => absint( $offer['row_version'] ) ),
			self::formats( $data, self::meeting_integer_fields() ),
			array( '%d', '%d' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'workflow_meeting_conflict', __( 'The meeting changed before the status was saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$event = 'completed' === $status ? 'meeting_completed' : ( 'canceled' === $status ? 'meeting_canceled' : 'meeting_canceled' );
		self::record_event( absint( $offer['inquiry_id'] ), 'staff', $actor_user_id, 'meeting', $id, $event, $offer['status'], $status, array( 'reason' => $reason ) );
		$inquiry = SC_EI_Inquiry_Repository::find( absint( $offer['inquiry_id'] ) );
		if ( $inquiry ) {
			self::update_inquiry_scheduling( $inquiry, array( 'scheduling_status' => 'canceled' === $status ? 'cancelled' : $status ) );
		}
		return self::find_meeting_offer( $id );
	}

	public static function create_proposal( int $inquiry_id, array $input, int $actor_user_id, bool $publish = false ) {
		global $wpdb;

		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $inquiry || 'erased' === $inquiry['privacy_status'] ) {
			return new WP_Error( 'workflow_inquiry_unavailable', __( 'The inquiry is unavailable for a proposal.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$access = SC_EI_Portal_Repository::access_for_inquiry( $inquiry_id );
		if ( ! $access || ! in_array( $access['status'], array( 'active', 'invited' ), true ) ) {
			return new WP_Error( 'workflow_portal_required', __( 'Sender portal access is required before publishing a proposal.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$title = sanitize_text_field( (string) ( $input['title'] ?? '' ) );
		$summary = sanitize_textarea_field( (string) ( $input['executive_summary'] ?? '' ) );
		$scope = SC_EI_Workflow_Schema::sanitize_list( $input['scope'] ?? '' );
		$deliverables = SC_EI_Workflow_Schema::sanitize_list( $input['deliverables'] ?? '' );
		if ( '' === $title || '' === $summary || ! $scope || ! $deliverables ) {
			return new WP_Error( 'workflow_proposal_content_required', __( 'Proposal title, summary, scope, and deliverables are required.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$settings = self::settings();
		$now = current_time( 'mysql', true );
		$temporary_number = 'PROP-TMP-' . strtoupper( substr( str_replace( '-', '', wp_generate_uuid4() ), 0, 16 ) );
		$currency = SC_EI_Workflow_Schema::sanitize_currency( (string) ( $input['currency'] ?? 'USD' ) );
		$total_minor = SC_EI_Workflow_Schema::money_minor( $input['total'] ?? 0 );
		$data = array(
			'public_id'                  => wp_generate_uuid4(),
			'inquiry_id'                 => $inquiry_id,
			'access_id'                  => absint( $access['id'] ),
			'proposal_number'            => $temporary_number,
			'status'                     => 'draft',
			'current_version_id'         => null,
			'pending_version_id'         => null,
			'currency'                   => $currency,
			'total_minor'                => $total_minor,
			'expires_at'                 => self::future_datetime( (string) ( $input['expires_at'] ?? '' ), absint( $settings['workflow_proposal_expiry_days'] ) ),
			'published_by'               => null,
			'published_at'               => null,
			'sender_response'            => '',
			'sender_response_note'       => '',
			'sender_authority_attested'  => 0,
			'boundary_acknowledged'      => 0,
			'responded_at'               => null,
			'accepted_at'                => null,
			'declined_at'                => null,
			'withdrawn_at'               => null,
			'superseded_by_id'           => null,
			'contract_reference'         => '',
			'contracted_by'              => null,
			'contracted_at'              => null,
			'row_version'                => 0,
			'created_by'                 => $actor_user_id,
			'created_at'                 => $now,
			'updated_at'                 => $now,
		);
		$inserted = $wpdb->insert(
			SC_EI_Database::table( 'proposals' ),
			$data,
			self::formats( $data, self::proposal_integer_fields() )
		);
		if ( false === $inserted ) {
			return new WP_Error( 'workflow_proposal_save_failed', __( 'The proposal could not be created.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$id = (int) $wpdb->insert_id;
		$proposal_number = 'PROP-' . gmdate( 'Ym' ) . '-' . str_pad( (string) $id, 5, '0', STR_PAD_LEFT );
		$wpdb->update(
			SC_EI_Database::table( 'proposals' ),
			array( 'proposal_number' => $proposal_number ),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);
		$version = self::create_proposal_version( $id, $input, $actor_user_id );
		if ( is_wp_error( $version ) ) {
			$wpdb->delete( SC_EI_Database::table( 'proposals' ), array( 'id' => $id ), array( '%d' ) );
			return $version;
		}
		$wpdb->update(
			SC_EI_Database::table( 'proposals' ),
			array( 'pending_version_id' => absint( $version['id'] ), 'updated_at' => $now ),
			array( 'id' => $id ),
			array( '%d', '%s' ),
			array( '%d' )
		);
		self::record_event( $inquiry_id, 'staff', $actor_user_id, 'proposal', $id, 'proposal_draft_created', '', 'draft', array( 'proposal_number' => $proposal_number, 'automatic_contract' => false ) );
		$proposal = self::find_proposal( $id );
		if ( $publish ) {
			return self::publish_proposal( $id, $actor_user_id );
		}
		return $proposal;
	}

	public static function add_proposal_version( int $proposal_id, array $input, int $actor_user_id ) {
		global $wpdb;

		$proposal = self::find_proposal( $proposal_id );
		if ( ! $proposal || ! in_array( $proposal['status'], array( 'draft', 'published' ), true ) ) {
			return new WP_Error( 'workflow_proposal_not_editable', __( 'This proposal cannot receive another version.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$version = self::create_proposal_version( $proposal_id, $input, $actor_user_id );
		if ( is_wp_error( $version ) ) {
			return $version;
		}
		$data = array(
			'pending_version_id' => absint( $version['id'] ),
			'currency'           => SC_EI_Workflow_Schema::sanitize_currency( (string) ( $input['currency'] ?? $proposal['currency'] ) ),
			'total_minor'        => SC_EI_Workflow_Schema::money_minor( $input['total'] ?? ( $proposal['total_minor'] / 100 ) ),
			'expires_at'         => self::future_datetime( (string) ( $input['expires_at'] ?? '' ), absint( self::settings()['workflow_proposal_expiry_days'] ) ),
			'row_version'        => absint( $proposal['row_version'] ) + 1,
			'updated_at'         => current_time( 'mysql', true ),
		);
		$updated = $wpdb->update(
			SC_EI_Database::table( 'proposals' ),
			$data,
			array( 'id' => $proposal_id, 'row_version' => absint( $proposal['row_version'] ) ),
			self::formats( $data, self::proposal_integer_fields() ),
			array( '%d', '%d' )
		);
		if ( 1 !== $updated ) {
			$wpdb->delete(
				SC_EI_Database::table( 'proposal_versions' ),
				array( 'id' => absint( $version['id'] ) ),
				array( '%d' )
			);
			return new WP_Error( 'workflow_proposal_conflict', __( 'The proposal changed before the new version was attached.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::record_event( absint( $proposal['inquiry_id'] ), 'staff', $actor_user_id, 'proposal', $proposal_id, 'proposal_version_created', $proposal['status'], $proposal['status'], array( 'version_number' => $version['version_number'] ) );
		return self::find_proposal( $proposal_id );
	}

	public static function publish_proposal( int $id, int $actor_user_id ) {
		global $wpdb;

		$proposal = self::find_proposal( $id );
		if (
			! $proposal
			|| ! in_array( $proposal['status'], array( 'draft', 'published' ), true )
			|| empty( $proposal['pending_version_id'] )
		) {
			return new WP_Error( 'workflow_proposal_not_draft', __( 'A complete unpublished proposal version is required before publication.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$version = self::find_proposal_version( absint( $proposal['pending_version_id'] ) );
		if ( ! $version ) {
			return new WP_Error( 'workflow_proposal_version_missing', __( 'The proposal version could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		$previous_status = $proposal['status'];
		$data = array(
			'status'             => 'published',
			'current_version_id' => absint( $proposal['pending_version_id'] ),
			'pending_version_id' => null,
			'published_by'       => $actor_user_id,
			'published_at'       => $now,
			'row_version'  => absint( $proposal['row_version'] ) + 1,
			'updated_at'   => $now,
		);
		$updated = $wpdb->update(
			SC_EI_Database::table( 'proposals' ),
			$data,
			array( 'id' => $id, 'row_version' => absint( $proposal['row_version'] ), 'status' => $previous_status ),
			self::formats( $data, self::proposal_integer_fields() ),
			array( '%d', '%d', '%s' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'workflow_proposal_conflict', __( 'The proposal changed before publication.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::supersede_other_proposals( absint( $proposal['inquiry_id'] ), $id );
		self::record_event(
			absint( $proposal['inquiry_id'] ),
			'staff',
			$actor_user_id,
			'proposal',
			$id,
			'proposal_published',
			$previous_status,
			'published',
			array(
				'proposal_number'   => $proposal['proposal_number'],
				'version_number'    => $version['version_number'],
				'automatic_contract'=> false,
				'automatic_payment' => false,
			)
		);
		SC_EI_Portal_Repository::create_portal_message(
			absint( $proposal['inquiry_id'] ),
			'outbound',
			sprintf(
				/* translators: %s proposal number. */
				__( 'A proposal is available in the Proposals section. Proposal: %s. Portal acceptance records intent to proceed to contracting; it is not an executed contract.', 'sustainable-catalyst-engagement-intake' ),
				$proposal['proposal_number']
			),
			$actor_user_id
		);
		self::update_inquiry_status( absint( $proposal['inquiry_id'] ), 'proposal_sent' );
		SC_EI_Audit_Log::record(
			'workflow_proposal_published',
			'Authorized staff published a versioned proposal to the sender portal.',
			array(
				'proposal_id'       => $id,
				'proposal_number'   => $proposal['proposal_number'],
				'version_number'    => $version['version_number'],
				'automatic_contract'=> false,
				'automatic_email'   => false,
			),
			absint( $proposal['inquiry_id'] ),
			null,
			$actor_user_id
		);
		return self::find_proposal( $id );
	}

	public static function respond_to_proposal(
		int $id,
		string $response,
		string $note,
		bool $authority_attested,
		bool $boundary_acknowledged,
		string $confirmation,
		int $session_id
	) {
		global $wpdb;

		$proposal = self::find_proposal( $id, true );
		if ( ! $proposal || 'published' !== $proposal['status'] ) {
			return new WP_Error( 'workflow_proposal_unavailable', __( 'This proposal is no longer available for response.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( strtotime( $proposal['expires_at'] . ' UTC' ) < time() ) {
			self::expire_proposal( $proposal );
			return new WP_Error( 'workflow_proposal_expired', __( 'This proposal expired.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$response = sanitize_key( $response );
		$note = sanitize_textarea_field( $note );
		$confirmation = strtoupper( trim( sanitize_text_field( $confirmation ) ) );
		$settings = self::settings();
		$now = current_time( 'mysql', true );
		$data = array(
			'sender_response_note'      => $note,
			'sender_authority_attested' => $authority_attested ? 1 : 0,
			'boundary_acknowledged'     => $boundary_acknowledged ? 1 : 0,
			'responded_at'              => $now,
			'row_version'               => absint( $proposal['row_version'] ) + 1,
			'updated_at'                => $now,
		);
		if ( 'accept' === $response ) {
			$expected = 'ACCEPT ' . strtoupper( $proposal['proposal_number'] );
			if ( ! hash_equals( $expected, $confirmation ) ) {
				return new WP_Error( 'workflow_proposal_confirmation_failed', __( 'The proposal acceptance confirmation did not match.', 'sustainable-catalyst-engagement-intake' ) );
			}
			if ( ! empty( $settings['workflow_require_authority_attestation'] ) && ! $authority_attested ) {
				return new WP_Error( 'workflow_proposal_authority_required', __( 'Confirm that you are authorized to respond for the sender organization.', 'sustainable-catalyst-engagement-intake' ) );
			}
			if ( ! empty( $settings['workflow_require_boundary_acknowledgment'] ) && ! $boundary_acknowledged ) {
				return new WP_Error( 'workflow_proposal_boundary_required', __( 'Acknowledge that portal acceptance is not an executed contract.', 'sustainable-catalyst-engagement-intake' ) );
			}
			$data += array(
				'status'          => 'accepted_pending_contract',
				'sender_response' => 'accept',
				'accepted_at'     => $now,
			);
			$event = 'proposal_accepted';
		} elseif ( 'decline' === $response ) {
			$expected = 'DECLINE ' . strtoupper( $proposal['proposal_number'] );
			if ( ! hash_equals( $expected, $confirmation ) ) {
				return new WP_Error( 'workflow_proposal_confirmation_failed', __( 'The proposal decline confirmation did not match.', 'sustainable-catalyst-engagement-intake' ) );
			}
			if ( mb_strlen( $note ) < 3 ) {
				return new WP_Error( 'workflow_proposal_note_required', __( 'Add a brief decline note.', 'sustainable-catalyst-engagement-intake' ) );
			}
			$data += array(
				'status'          => 'declined',
				'sender_response' => 'decline',
				'declined_at'     => $now,
			);
			$event = 'proposal_declined';
		} else {
			return new WP_Error( 'workflow_proposal_response_invalid', __( 'Choose a valid proposal response.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$updated = $wpdb->update(
			SC_EI_Database::table( 'proposals' ),
			$data,
			array( 'id' => $id, 'row_version' => absint( $proposal['row_version'] ), 'status' => 'published' ),
			self::formats( $data, self::proposal_integer_fields() ),
			array( '%d', '%d', '%s' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'workflow_proposal_conflict', __( 'The proposal changed before your response was saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$new = self::find_proposal( $id, true );
		self::record_event(
			absint( $proposal['inquiry_id'] ),
			'sender',
			$session_id,
			'proposal',
			$id,
			$event,
			'published',
			$new['status'],
			array(
				'authority_attested'  => $authority_attested,
				'boundary_acknowledged'=> $boundary_acknowledged,
				'automatic_contract'  => false,
				'automatic_payment'   => false,
			)
		);
		if ( 'accept' === $response ) {
			self::update_inquiry_status( absint( $proposal['inquiry_id'] ), 'proposal_sent' );
		}
		SC_EI_Audit_Log::record(
			'workflow_' . $event,
			'Sender recorded a proposal response through the secure portal.',
			array(
				'proposal_id'       => $id,
				'response'          => $response,
				'automatic_contract'=> false,
				'automatic_payment' => false,
			),
			absint( $proposal['inquiry_id'] )
		);
		return $new;
	}

	public static function change_proposal_status( int $id, string $status, string $note, string $contract_reference, int $actor_user_id ) {
		global $wpdb;

		$proposal = self::find_proposal( $id );
		if ( ! $proposal ) {
			return new WP_Error( 'workflow_proposal_not_found', __( 'The proposal could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$status = sanitize_key( $status );
		if ( ! in_array( $status, array( 'withdrawn', 'contracted' ), true ) ) {
			return new WP_Error( 'workflow_proposal_status_invalid', __( 'Choose a valid proposal state.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$note = sanitize_textarea_field( $note );
		$contract_reference = sanitize_text_field( $contract_reference );
		if ( 'withdrawn' === $status && '' === trim( $note ) ) {
			return new WP_Error( 'workflow_proposal_note_required', __( 'Record why the proposal was withdrawn.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( 'contracted' === $status ) {
			if ( 'accepted_pending_contract' !== $proposal['status'] ) {
				return new WP_Error( 'workflow_proposal_not_accepted', __( 'Record an external contract only after sender acceptance.', 'sustainable-catalyst-engagement-intake' ) );
			}
			if ( '' === trim( $contract_reference ) ) {
				return new WP_Error( 'workflow_contract_reference_required', __( 'Record the external contract reference.', 'sustainable-catalyst-engagement-intake' ) );
			}
		}
		$now = current_time( 'mysql', true );
		$data = array(
			'status'             => $status,
			'withdrawn_at'       => 'withdrawn' === $status ? $now : $proposal['withdrawn_at'],
			'contract_reference' => 'contracted' === $status ? $contract_reference : $proposal['contract_reference'],
			'contracted_by'      => 'contracted' === $status ? $actor_user_id : $proposal['contracted_by'],
			'contracted_at'      => 'contracted' === $status ? $now : $proposal['contracted_at'],
			'row_version'        => absint( $proposal['row_version'] ) + 1,
			'updated_at'         => $now,
		);
		$updated = $wpdb->update(
			SC_EI_Database::table( 'proposals' ),
			$data,
			array( 'id' => $id, 'row_version' => absint( $proposal['row_version'] ) ),
			self::formats( $data, self::proposal_integer_fields() ),
			array( '%d', '%d' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'workflow_proposal_conflict', __( 'The proposal changed before the administrative state was saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$event = 'contracted' === $status ? 'proposal_contracted' : 'proposal_withdrawn';
		self::record_event( absint( $proposal['inquiry_id'] ), 'staff', $actor_user_id, 'proposal', $id, $event, $proposal['status'], $status, array( 'note' => $note, 'contract_reference' => $contract_reference, 'external_contract' => true ) );
		if ( 'contracted' === $status ) {
			self::update_inquiry_status( absint( $proposal['inquiry_id'] ), 'accepted' );
		}
		return self::find_proposal( $id );
	}

	public static function find_meeting_offer( int $id ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'meeting_offers' );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $row ?: null;
	}

	public static function meeting_offers_for_inquiry( int $inquiry_id, bool $portal_visible = false ): array {
		global $wpdb;
		$table = SC_EI_Database::table( 'meeting_offers' );
		$where = $portal_visible
			? "AND status IN ('offered','accepted_pending_link','scheduled','alternative_requested','declined','completed','canceled','expired')"
			: '';
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE inquiry_id = %d {$where} ORDER BY created_at DESC, id DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$inquiry_id
			),
			ARRAY_A
		);
	}

	public static function find_proposal( int $id, bool $portal_visible = false ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'proposals' );
		$version_join = $portal_visible
			? 'p.current_version_id'
			: "CASE
				WHEN p.status IN ('draft','published') THEN COALESCE(p.pending_version_id, p.current_version_id)
				ELSE p.current_version_id
			END";
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT p.*, v.version_number, v.title, v.executive_summary, v.scope_json,
					v.deliverables_json, v.exclusions_json, v.assumptions_json, v.timeline_text,
					v.fee_summary, v.payment_terms, v.legal_terms, v.version_note, v.content_hash
				FROM {$table} p
				LEFT JOIN " . SC_EI_Database::table( 'proposal_versions' ) . " v ON v.id = {$version_join}
				WHERE p.id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$id
			),
			ARRAY_A
		);
		return $row ?: null;
	}

	public static function proposals_for_inquiry( int $inquiry_id, bool $portal_visible = false ): array {
		global $wpdb;
		$table = SC_EI_Database::table( 'proposals' );
		$versions = SC_EI_Database::table( 'proposal_versions' );
		$where = $portal_visible
			? "AND p.status IN ('published','accepted_pending_contract','declined','contracted','withdrawn','expired','superseded')"
			: '';
		$version_join = $portal_visible
			? 'p.current_version_id'
			: "CASE
				WHEN p.status IN ('draft','published') THEN COALESCE(p.pending_version_id, p.current_version_id)
				ELSE p.current_version_id
			END";
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.*, v.version_number, v.title, v.executive_summary, v.scope_json,
					v.deliverables_json, v.exclusions_json, v.assumptions_json, v.timeline_text,
					v.fee_summary, v.payment_terms, v.legal_terms, v.version_note, v.content_hash
				FROM {$table} p
				LEFT JOIN {$versions} v ON v.id = {$version_join}
				WHERE p.inquiry_id = %d {$where}
				ORDER BY p.created_at DESC, p.id DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$inquiry_id
			),
			ARRAY_A
		);
	}

	public static function proposal_versions( int $proposal_id ): array {
		global $wpdb;
		$table = SC_EI_Database::table( 'proposal_versions' );
		return (array) $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE proposal_id = %d ORDER BY version_number DESC", $proposal_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
	}

	public static function find_proposal_version( int $id ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'proposal_versions' );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $row ?: null;
	}

	public static function note_portal_activity(
		int $inquiry_id,
		int $session_id,
		string $object_type,
		int $object_id,
		string $event_type,
		array $context = array()
	): void {
		self::record_event(
			$inquiry_id,
			'sender',
			$session_id,
			$object_type,
			$object_id,
			$event_type,
			'',
			'',
			$context
		);
	}

	public static function events_for_inquiry( int $inquiry_id, int $limit = 500 ): array {
		global $wpdb;
		$table = SC_EI_Database::table( 'workflow_events' );
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT e.*, u.display_name AS actor_name
				FROM {$table} e
				LEFT JOIN {$wpdb->users} u ON e.actor_type = 'staff' AND u.ID = e.actor_id
				WHERE e.inquiry_id = %d
				ORDER BY e.created_at DESC, e.id DESC
				LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$inquiry_id,
				max( 1, min( 2000, $limit ) )
			),
			ARRAY_A
		);
	}

	public static function metrics(): array {
		global $wpdb;
		$meetings = SC_EI_Database::table( 'meeting_offers' );
		$proposals = SC_EI_Database::table( 'proposals' );
		$now = current_time( 'mysql', true );
		return array(
			'meeting_offered'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$meetings} WHERE status = 'offered'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'meeting_scheduled'=> (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$meetings} WHERE status = 'scheduled'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'meeting_followup' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$meetings} WHERE status IN ('accepted_pending_link','alternative_requested')" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'proposal_draft'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$proposals} WHERE status = 'draft'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'proposal_open'    => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$proposals} WHERE status = 'published' AND expires_at > %s", $now ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'proposal_accepted'=> (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$proposals} WHERE status = 'accepted_pending_contract'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'contracted'       => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$proposals} WHERE status = 'contracted'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	public static function export_for_inquiry( int $inquiry_id ): array {
		return array(
			'meeting_offers'   => self::meeting_offers_for_inquiry( $inquiry_id, false ),
			'proposals'        => self::proposals_for_inquiry( $inquiry_id, false ),
			'proposal_versions'=> array_reduce(
				self::proposals_for_inquiry( $inquiry_id, false ),
				static function ( array $carry, array $proposal ): array {
					$carry[ $proposal['id'] ] = self::proposal_versions( absint( $proposal['id'] ) );
					return $carry;
				},
				array()
			),
			'events'           => self::events_for_inquiry( $inquiry_id, 2000 ),
			'microsoft_graph'  => SC_EI_Graph_Repository::export_for_inquiry( $inquiry_id ),
		);
	}

	public static function redact_for_privacy( int $inquiry_id, string $now ): bool {
		global $wpdb;
		$meetings = $wpdb->query(
			$wpdb->prepare(
				"UPDATE " . SC_EI_Database::table( 'meeting_offers' ) . "
				SET sender_note = '', alternative_request = '', admin_note = '',
					cancellation_reason = '', updated_at = %s
				WHERE inquiry_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$now,
				$inquiry_id
			)
		);
		$proposals = $wpdb->query(
			$wpdb->prepare(
				"UPDATE " . SC_EI_Database::table( 'proposals' ) . "
				SET sender_response_note = '', contract_reference = '', updated_at = %s
				WHERE inquiry_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$now,
				$inquiry_id
			)
		);
		$events = $wpdb->query(
			$wpdb->prepare(
				"UPDATE " . SC_EI_Database::table( 'workflow_events' ) . "
				SET context_json = %s
				WHERE inquiry_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				wp_json_encode( array( 'personal_data_erased' => true, 'workflow_schema_version' => SC_EI_WORKFLOW_SCHEMA_VERSION ) ),
				$inquiry_id
			)
		);
		$graph = SC_EI_Graph_Repository::redact_for_privacy( $inquiry_id, $now );
		return false !== $meetings && false !== $proposals && false !== $events && $graph;
	}

	public static function expire_stale(): array {
		global $wpdb;
		$now = current_time( 'mysql', true );
		$meetings = SC_EI_Database::table( 'meeting_offers' );
		$proposals = SC_EI_Database::table( 'proposals' );
		$expired_meetings = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$meetings}
				SET status = 'expired', updated_at = %s, row_version = row_version + 1
				WHERE status = 'offered' AND expires_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$now,
				$now
			)
		);
		$expired_proposals = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$proposals}
				SET status = 'expired', updated_at = %s, row_version = row_version + 1
				WHERE status = 'published' AND expires_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$now,
				$now
			)
		);
		return array(
			'expired_meetings'  => false === $expired_meetings ? 0 : absint( $expired_meetings ),
			'expired_proposals' => false === $expired_proposals ? 0 : absint( $expired_proposals ),
			'completed_at'      => $now,
		);
	}

	public static function meeting_ics( array $offer, array $inquiry ): string {
		$uid = sanitize_text_field( $offer['public_id'] ) . '@sustainablecatalyst.com';
		$description = trim( wp_strip_all_tags( $offer['purpose'] ) );
		if ( $offer['teams_url'] ) {
			$description .= ( $description ? "\n\n" : '' ) . 'Microsoft Teams: ' . $offer['teams_url'];
		}
		$lines = array(
			'BEGIN:VCALENDAR',
			'VERSION:2.0',
			'PRODID:-//Sustainable Catalyst//Engagement Intake//EN',
			'CALSCALE:GREGORIAN',
			'METHOD:PUBLISH',
			'BEGIN:VEVENT',
			'UID:' . self::ics_escape( $uid ),
			'DTSTAMP:' . gmdate( 'Ymd\THis\Z' ),
			'DTSTART:' . gmdate( 'Ymd\THis\Z', strtotime( $offer['selected_start_utc'] . ' UTC' ) ),
			'DTEND:' . gmdate( 'Ymd\THis\Z', strtotime( $offer['selected_end_utc'] . ' UTC' ) ),
			'SUMMARY:' . self::ics_escape( $offer['title'] ),
			'DESCRIPTION:' . self::ics_escape( $description ),
			'LOCATION:' . self::ics_escape( 'Microsoft Teams' ),
			'URL:' . self::ics_escape( $offer['teams_url'] ),
			'STATUS:CONFIRMED',
			'END:VEVENT',
			'END:VCALENDAR',
		);
		return implode( "\r\n", $lines ) . "\r\n";
	}

	private static function create_proposal_version( int $proposal_id, array $input, int $actor_user_id ) {
		global $wpdb;

		$title = sanitize_text_field( (string) ( $input['title'] ?? '' ) );
		$summary = sanitize_textarea_field( (string) ( $input['executive_summary'] ?? '' ) );
		$scope = SC_EI_Workflow_Schema::sanitize_list( $input['scope'] ?? '' );
		$deliverables = SC_EI_Workflow_Schema::sanitize_list( $input['deliverables'] ?? '' );
		if ( '' === $title || '' === $summary || ! $scope || ! $deliverables ) {
			return new WP_Error( 'workflow_proposal_content_required', __( 'Proposal title, summary, scope, and deliverables are required.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$table = SC_EI_Database::table( 'proposal_versions' );
		$version_number = 1 + (int) $wpdb->get_var(
			$wpdb->prepare( "SELECT COALESCE(MAX(version_number), 0) FROM {$table} WHERE proposal_id = %d", $proposal_id ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
		$content = array(
			'title'             => $title,
			'executive_summary' => $summary,
			'scope'             => $scope,
			'deliverables'      => $deliverables,
			'exclusions'        => SC_EI_Workflow_Schema::sanitize_list( $input['exclusions'] ?? '' ),
			'assumptions'       => SC_EI_Workflow_Schema::sanitize_list( $input['assumptions'] ?? '' ),
			'timeline_text'     => sanitize_textarea_field( (string) ( $input['timeline_text'] ?? '' ) ),
			'fee_summary'       => sanitize_textarea_field( (string) ( $input['fee_summary'] ?? '' ) ),
			'payment_terms'     => sanitize_textarea_field( (string) ( $input['payment_terms'] ?? '' ) ),
			'legal_terms'       => sanitize_textarea_field( (string) ( $input['legal_terms'] ?? '' ) ),
			'version_note'      => sanitize_textarea_field( (string) ( $input['version_note'] ?? '' ) ),
		);
		$now = current_time( 'mysql', true );
		$data = array(
			'public_id'          => wp_generate_uuid4(),
			'proposal_id'        => $proposal_id,
			'version_number'     => $version_number,
			'title'              => $content['title'],
			'executive_summary'  => $content['executive_summary'],
			'scope_json'         => wp_json_encode( $content['scope'] ),
			'deliverables_json'  => wp_json_encode( $content['deliverables'] ),
			'exclusions_json'    => wp_json_encode( $content['exclusions'] ),
			'assumptions_json'   => wp_json_encode( $content['assumptions'] ),
			'timeline_text'      => $content['timeline_text'],
			'fee_summary'        => $content['fee_summary'],
			'payment_terms'      => $content['payment_terms'],
			'legal_terms'        => $content['legal_terms'],
			'version_note'       => $content['version_note'],
			'content_hash'       => hash( 'sha256', wp_json_encode( $content ) ),
			'created_by'         => $actor_user_id,
			'created_at'         => $now,
		);
		if ( false === $wpdb->insert( $table, $data, self::formats( $data, array( 'proposal_id', 'version_number', 'created_by' ) ) ) ) {
			return new WP_Error( 'workflow_proposal_version_failed', __( 'The proposal version could not be saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		return self::find_proposal_version( (int) $wpdb->insert_id );
	}

	private static function find_slot( array $offer, string $slot_key ): ?array {
		$slots = json_decode( (string) $offer['slots_json'], true ) ?: array();
		foreach ( $slots as $slot ) {
			if ( isset( $slot['key'] ) && hash_equals( (string) $slot['key'], sanitize_key( $slot_key ) ) ) {
				return $slot;
			}
		}
		return null;
	}

	private static function supersede_other_meeting_offers( int $inquiry_id, int $except_id ): void {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE " . SC_EI_Database::table( 'meeting_offers' ) . "
				SET status = 'superseded', updated_at = %s, row_version = row_version + 1
				WHERE inquiry_id = %d AND id <> %d AND status IN ('draft','offered')", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				current_time( 'mysql', true ),
				$inquiry_id,
				$except_id
			)
		);
	}

	private static function supersede_other_proposals( int $inquiry_id, int $except_id ): void {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE " . SC_EI_Database::table( 'proposals' ) . "
				SET status = 'superseded', superseded_by_id = %d, updated_at = %s, row_version = row_version + 1
				WHERE inquiry_id = %d AND id <> %d AND status IN ('draft','published')", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$except_id,
				current_time( 'mysql', true ),
				$inquiry_id,
				$except_id
			)
		);
	}

	private static function expire_meeting( array $offer ): void {
		global $wpdb;
		$wpdb->update(
			SC_EI_Database::table( 'meeting_offers' ),
			array(
				'status'      => 'expired',
				'row_version' => absint( $offer['row_version'] ) + 1,
				'updated_at'  => current_time( 'mysql', true ),
			),
			array( 'id' => absint( $offer['id'] ), 'row_version' => absint( $offer['row_version'] ) ),
			array( '%s', '%d', '%s' ),
			array( '%d', '%d' )
		);
	}

	private static function expire_proposal( array $proposal ): void {
		global $wpdb;
		$wpdb->update(
			SC_EI_Database::table( 'proposals' ),
			array(
				'status'      => 'expired',
				'row_version' => absint( $proposal['row_version'] ) + 1,
				'updated_at'  => current_time( 'mysql', true ),
			),
			array( 'id' => absint( $proposal['id'] ), 'row_version' => absint( $proposal['row_version'] ) ),
			array( '%s', '%d', '%s' ),
			array( '%d', '%d' )
		);
	}

	private static function update_inquiry_scheduling( array $inquiry, array $data ): void {
		global $wpdb;
		$data['updated_at'] = current_time( 'mysql', true );
		$wpdb->update(
			SC_EI_Database::table( 'inquiries' ),
			$data,
			array( 'id' => absint( $inquiry['id'] ) ),
			self::formats( $data, array() ),
			array( '%d' )
		);
	}

	private static function update_inquiry_status( int $inquiry_id, string $status ): void {
		global $wpdb;
		$wpdb->update(
			SC_EI_Database::table( 'inquiries' ),
			array( 'status' => sanitize_key( $status ), 'updated_at' => current_time( 'mysql', true ) ),
			array( 'id' => $inquiry_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	public static function note_graph_event(
		int $inquiry_id,
		int $actor_user_id,
		int $meeting_offer_id,
		string $event_type,
		string $from_status,
		string $to_status,
		array $context = array()
	): void {
		self::record_event(
			$inquiry_id,
			$actor_user_id > 0 ? 'staff' : 'system',
			$actor_user_id,
			'meeting',
			$meeting_offer_id,
			$event_type,
			$from_status,
			$to_status,
			$context
		);
	}

	private static function record_event(
		int $inquiry_id,
		string $actor_type,
		int $actor_id,
		string $object_type,
		int $object_id,
		string $event_type,
		string $from_status,
		string $to_status,
		array $context = array()
	): void {
		global $wpdb;
		$data = array(
			'public_id'   => wp_generate_uuid4(),
			'inquiry_id'  => $inquiry_id,
			'actor_type'  => sanitize_key( $actor_type ),
			'actor_id'    => $actor_id,
			'object_type' => sanitize_key( $object_type ),
			'object_id'   => $object_id,
			'event_type'  => sanitize_key( $event_type ),
			'from_status' => sanitize_key( $from_status ),
			'to_status'   => sanitize_key( $to_status ),
			'context_json'=> wp_json_encode( $context ),
			'created_at'  => current_time( 'mysql', true ),
		);
		$wpdb->insert(
			SC_EI_Database::table( 'workflow_events' ),
			$data,
			self::formats( $data, array( 'inquiry_id', 'actor_id', 'object_id' ) )
		);
	}

	private static function future_datetime( string $value, int $default_days ): string {
		$value = sanitize_text_field( $value );
		if ( $value ) {
			try {
				$date = new DateTimeImmutable( $value, wp_timezone() );
				$utc = $date->setTimezone( new DateTimeZone( 'UTC' ) );
				if ( $utc->getTimestamp() > time() ) {
					return $utc->format( 'Y-m-d H:i:s' );
				}
			} catch ( Throwable $exception ) {
				// Use the controlled default below.
			}
		}
		return gmdate( 'Y-m-d H:i:s', time() + max( 1, $default_days ) * DAY_IN_SECONDS );
	}

	private static function meeting_integer_fields(): array {
		return array( 'inquiry_id', 'access_id', 'duration_minutes', 'graph_attempt_count', 'published_by', 'finalized_by', 'row_version', 'created_by' );
	}

	private static function proposal_integer_fields(): array {
		return array(
			'inquiry_id', 'access_id', 'current_version_id', 'pending_version_id', 'total_minor',
			'published_by', 'sender_authority_attested', 'boundary_acknowledged',
			'superseded_by_id', 'contracted_by', 'row_version', 'created_by',
		);
	}

	private static function formats( array $data, array $integer_fields ): array {
		return array_map(
			static fn( string $key ): string => in_array( $key, $integer_fields, true ) ? '%d' : '%s',
			array_keys( $data )
		);
	}

	private static function ics_escape( string $value ): string {
		return str_replace(
			array( '\\', ';', ',', "\r\n", "\r", "\n" ),
			array( '\\\\', '\\;', '\\,', '\\n', '\\n', '\\n' ),
			$value
		);
	}
}
