<?php
/**
 * Proposal-to-engagement handoff repository.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Engagement_Repository {

	public static function settings(): array {
		return wp_parse_args(
			get_option( 'sc_ei_settings', array() ),
			SC_EI_Engagement_Schema::default_settings()
		);
	}

	public static function create_from_contracted_proposal(
		int $proposal_id,
		array $input,
		int $actor_user_id
	) {
		global $wpdb;

		$settings = self::settings();
		if ( empty( $settings['engagement_enabled'] ) ) {
			return new WP_Error( 'engagement_disabled', __( 'Engagement handoff is disabled.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$proposal = SC_EI_Workflow_Repository::find_proposal( $proposal_id, true );
		if ( ! $proposal || 'contracted' !== $proposal['status'] ) {
			return new WP_Error( 'engagement_proposal_not_contracted', __( 'Only a contracted proposal can create an engagement handoff.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( empty( $proposal['current_version_id'] ) || empty( $proposal['content_hash'] ) ) {
			return new WP_Error( 'engagement_proposal_snapshot_missing', __( 'The contracted proposal does not have a complete published version.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( ! empty( $settings['engagement_require_contract_reference'] ) && empty( $proposal['contract_reference'] ) ) {
			return new WP_Error( 'engagement_contract_reference_missing', __( 'Record the external contract reference before creating the handoff.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$existing = self::find_by_proposal( $proposal_id );
		if ( $existing ) {
			return new WP_Error(
				'engagement_duplicate_proposal',
				__( 'This contracted proposal already has an engagement handoff.', 'sustainable-catalyst-engagement-intake' ),
				array( 'engagement_id' => absint( $existing['id'] ) )
			);
		}

		$inquiry = SC_EI_Inquiry_Repository::find( absint( $proposal['inquiry_id'] ) );
		if ( ! $inquiry || 'erased' === $inquiry['privacy_status'] ) {
			return new WP_Error( 'engagement_inquiry_unavailable', __( 'The inquiry is unavailable for engagement handoff.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$owner_user_id = absint( $input['owner_user_id'] ?? 0 );
		if ( $owner_user_id && ! get_userdata( $owner_user_id ) ) {
			return new WP_Error( 'engagement_owner_invalid', __( 'Choose a valid WordPress user as engagement owner.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$participant_ids = SC_EI_Engagement_Schema::sanitize_user_ids( $input['participant_user_ids'] ?? array() );
		$participant_ids = array_values( array_filter( $participant_ids, static fn( int $id ): bool => (bool) get_userdata( $id ) ) );
		$access = SC_EI_Portal_Repository::access_for_inquiry( absint( $proposal['inquiry_id'] ) );
		$public_id = wp_generate_uuid4();
		$engagement_number = 'SC-ENG-' . gmdate( 'Y' ) . '-' . strtoupper( substr( str_replace( '-', '', $public_id ), 0, 10 ) );
		$now = current_time( 'mysql', true );

		$snapshot_payload = self::snapshot_payload( $proposal, $inquiry );
		$snapshot_json = wp_json_encode( $snapshot_payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( false === $snapshot_json ) {
			return new WP_Error( 'engagement_snapshot_encode_failed', __( 'The engagement handoff snapshot could not be encoded.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$snapshot_hash = hash( 'sha256', $snapshot_json );

		$engagement = array(
			'public_id'                       => $public_id,
			'engagement_number'               => $engagement_number,
			'inquiry_id'                      => absint( $proposal['inquiry_id'] ),
			'proposal_id'                     => $proposal_id,
			'proposal_version_id'             => absint( $proposal['current_version_id'] ),
			'access_id'                       => $access ? absint( $access['id'] ) : 0,
			'current_snapshot_id'             => null,
			'status'                          => 'handoff_pending',
			'title'                           => sanitize_text_field( (string) ( $input['engagement_title'] ?? $proposal['title'] ) ),
			'sender_organization'             => sanitize_text_field( (string) $inquiry['organization'] ),
			'contract_reference'              => sanitize_text_field( (string) $proposal['contract_reference'] ),
			'currency'                        => SC_EI_Workflow_Schema::sanitize_currency( (string) $proposal['currency'] ),
			'total_minor'                     => absint( $proposal['total_minor'] ),
			'owner_user_id'                   => $owner_user_id ?: null,
			'participant_user_ids_json'       => wp_json_encode( $participant_ids ),
			'proposed_start_date'             => self::sanitize_date( (string) ( $input['proposed_start_date'] ?? '' ) ),
			'target_end_date'                 => self::sanitize_date( (string) ( $input['target_end_date'] ?? '' ) ),
			'kickoff_status'                  => SC_EI_Engagement_Schema::sanitize_kickoff_status( (string) ( $input['kickoff_status'] ?? 'not_scheduled' ) ),
			'kickoff_at'                      => self::sanitize_datetime( (string) ( $input['kickoff_at'] ?? '' ) ),
			'onboarding_summary'              => sanitize_textarea_field( (string) ( $input['onboarding_summary'] ?? '' ) ),
			'sender_summary'                  => sanitize_textarea_field( (string) ( $input['sender_summary'] ?? '' ) ),
			'internal_notes'                  => sanitize_textarea_field( (string) ( $input['internal_notes'] ?? '' ) ),
			'external_project_reference'      => sanitize_text_field( (string) ( $input['external_project_reference'] ?? '' ) ),
			'workbench_handoff_status'        => 'not_requested',
			'decision_studio_handoff_status'  => 'not_requested',
			'handoff_prepared_by'             => $actor_user_id,
			'handoff_prepared_at'             => $now,
			'ready_by'                        => null,
			'ready_at'                        => null,
			'activated_by'                    => null,
			'activated_at'                    => null,
			'paused_by'                       => null,
			'paused_at'                       => null,
			'pause_reason'                    => '',
			'completed_by'                    => null,
			'completed_at'                    => null,
			'completion_note'                 => '',
			'canceled_by'                     => null,
			'canceled_at'                     => null,
			'cancellation_reason'             => '',
			'row_version'                     => 0,
			'created_by'                      => $actor_user_id,
			'created_at'                      => $now,
			'updated_at'                      => $now,
		);

		$wpdb->query( 'START TRANSACTION' );
		try {
			$inserted = $wpdb->insert(
				SC_EI_Database::table( 'engagements' ),
				$engagement,
				self::formats( $engagement, self::engagement_integer_fields() )
			);
			if ( false === $inserted ) {
				throw new RuntimeException( 'engagement_insert_failed' );
			}
			$engagement_id = (int) $wpdb->insert_id;

			$snapshot = array(
				'public_id'               => wp_generate_uuid4(),
				'engagement_id'           => $engagement_id,
				'inquiry_id'              => absint( $proposal['inquiry_id'] ),
				'proposal_id'             => $proposal_id,
				'proposal_version_id'     => absint( $proposal['current_version_id'] ),
				'snapshot_version'        => 1,
				'snapshot_type'           => 'contracted_proposal_handoff',
				'proposal_number'         => sanitize_text_field( (string) $proposal['proposal_number'] ),
				'proposal_version_number' => absint( $proposal['version_number'] ),
				'proposal_content_hash'   => sanitize_text_field( (string) $proposal['content_hash'] ),
				'contract_reference'      => sanitize_text_field( (string) $proposal['contract_reference'] ),
				'payload_json'            => $snapshot_json,
				'content_hash'            => $snapshot_hash,
				'created_by'              => $actor_user_id,
				'created_at'              => $now,
			);
			if ( false === $wpdb->insert(
				SC_EI_Database::table( 'engagement_snapshots' ),
				$snapshot,
				self::formats( $snapshot, array( 'engagement_id', 'inquiry_id', 'proposal_id', 'proposal_version_id', 'snapshot_version', 'proposal_version_number', 'created_by' ) )
			) ) {
				throw new RuntimeException( 'engagement_snapshot_insert_failed' );
			}
			$snapshot_id = (int) $wpdb->insert_id;

			if ( 1 !== $wpdb->update(
				SC_EI_Database::table( 'engagements' ),
				array( 'current_snapshot_id' => $snapshot_id, 'row_version' => 1, 'updated_at' => $now ),
				array( 'id' => $engagement_id, 'row_version' => 0 ),
				array( '%d', '%d', '%s' ),
				array( '%d', '%d' )
			) ) {
				throw new RuntimeException( 'engagement_snapshot_attach_failed' );
			}

			$engagement['id'] = $engagement_id;
			$engagement['current_snapshot_id'] = $snapshot_id;
			foreach ( SC_EI_Engagement_Schema::default_requirements( $engagement, $settings ) as $requirement ) {
				$created = self::insert_requirement( $engagement, $requirement, $actor_user_id, false );
				if ( is_wp_error( $created ) ) {
					throw new RuntimeException( $created->get_error_code() );
				}
			}

			self::record_event(
				$engagement_id,
				absint( $proposal['inquiry_id'] ),
				'staff',
				$actor_user_id,
				'engagement_handoff_created',
				'engagement',
				$engagement_id,
				'',
				'handoff_pending',
				array(
					'proposal_number'      => $proposal['proposal_number'],
					'proposal_version'     => absint( $proposal['version_number'] ),
					'proposal_content_hash'=> $proposal['content_hash'],
					'snapshot_hash'        => $snapshot_hash,
					'contract_reference'   => $proposal['contract_reference'],
					'automatic_activation' => false,
					'automatic_provisioning'=> false,
				)
			);
			self::record_event(
				$engagement_id,
				absint( $proposal['inquiry_id'] ),
				'staff',
				$actor_user_id,
				'engagement_snapshot_created',
				'snapshot',
				$snapshot_id,
				'',
				'sealed',
				array(
					'snapshot_version' => 1,
					'content_hash'     => $snapshot_hash,
				)
			);
			$wpdb->query( 'COMMIT' );
		} catch ( Throwable $error ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error(
				'engagement_handoff_failed',
				__( 'The engagement handoff could not be created atomically. The contracted proposal remains unchanged.', 'sustainable-catalyst-engagement-intake' ),
				array( 'internal_code' => sanitize_key( $error->getMessage() ) )
			);
		}

		SC_EI_Audit_Log::record(
			'engagement_handoff_created',
			'Authorized staff created a controlled engagement handoff from a contracted proposal.',
			array(
				'engagement_number'   => $engagement_number,
				'proposal_id'         => $proposal_id,
				'proposal_version_id' => absint( $proposal['current_version_id'] ),
				'snapshot_hash'       => $snapshot_hash,
				'automatic_activation'=> false,
			),
			absint( $proposal['inquiry_id'] ),
			null,
			$actor_user_id
		);
		return self::find( $engagement_id );
	}

	public static function update_profile( int $engagement_id, array $input, int $actor_user_id ) {
		global $wpdb;

		$engagement = self::find( $engagement_id );
		if ( ! $engagement || in_array( $engagement['status'], array( 'completed', 'canceled' ), true ) ) {
			return new WP_Error( 'engagement_profile_locked', __( 'This engagement can no longer be edited.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$owner_user_id = absint( $input['owner_user_id'] ?? $engagement['owner_user_id'] );
		if ( $owner_user_id && ! get_userdata( $owner_user_id ) ) {
			return new WP_Error( 'engagement_owner_invalid', __( 'Choose a valid engagement owner.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$participants = SC_EI_Engagement_Schema::sanitize_user_ids( $input['participant_user_ids'] ?? json_decode( (string) $engagement['participant_user_ids_json'], true ) ?: array() );
		$participants = array_values( array_filter( $participants, static fn( int $id ): bool => (bool) get_userdata( $id ) ) );
		$now = current_time( 'mysql', true );
		$data = array(
			'title'                          => sanitize_text_field( (string) ( $input['engagement_title'] ?? $engagement['title'] ) ),
			'owner_user_id'                  => $owner_user_id ?: null,
			'participant_user_ids_json'      => wp_json_encode( $participants ),
			'proposed_start_date'            => self::sanitize_date( (string) ( $input['proposed_start_date'] ?? $engagement['proposed_start_date'] ) ),
			'target_end_date'                => self::sanitize_date( (string) ( $input['target_end_date'] ?? $engagement['target_end_date'] ) ),
			'kickoff_status'                 => SC_EI_Engagement_Schema::sanitize_kickoff_status( (string) ( $input['kickoff_status'] ?? $engagement['kickoff_status'] ) ),
			'kickoff_at'                     => self::sanitize_datetime( (string) ( $input['kickoff_at'] ?? $engagement['kickoff_at'] ) ),
			'onboarding_summary'             => sanitize_textarea_field( (string) ( $input['onboarding_summary'] ?? $engagement['onboarding_summary'] ) ),
			'sender_summary'                 => sanitize_textarea_field( (string) ( $input['sender_summary'] ?? $engagement['sender_summary'] ) ),
			'internal_notes'                 => sanitize_textarea_field( (string) ( $input['internal_notes'] ?? $engagement['internal_notes'] ) ),
			'external_project_reference'     => sanitize_text_field( (string) ( $input['external_project_reference'] ?? $engagement['external_project_reference'] ) ),
			'workbench_handoff_status'       => self::sanitize_handoff_status( (string) ( $input['workbench_handoff_status'] ?? $engagement['workbench_handoff_status'] ) ),
			'decision_studio_handoff_status' => self::sanitize_handoff_status( (string) ( $input['decision_studio_handoff_status'] ?? $engagement['decision_studio_handoff_status'] ) ),
			'row_version'                    => absint( $engagement['row_version'] ) + 1,
			'updated_at'                     => $now,
		);
		$updated = $wpdb->update(
			SC_EI_Database::table( 'engagements' ),
			$data,
			array( 'id' => $engagement_id, 'row_version' => absint( $engagement['row_version'] ) ),
			self::formats( $data, self::engagement_integer_fields() ),
			array( '%d', '%d' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'engagement_profile_conflict', __( 'The engagement changed before the profile update was saved.', 'sustainable-catalyst-engagement-intake' ) );
		}

		if ( $owner_user_id ) {
			self::auto_complete_requirement( $engagement_id, 'engagement_owner_confirmed', $actor_user_id, 'Engagement owner assigned.' );
		}
		if ( in_array( $data['kickoff_status'], array( 'scheduled', 'completed', 'not_required' ), true ) ) {
			self::auto_complete_requirement( $engagement_id, 'kickoff_plan_confirmed', $actor_user_id, 'Kickoff status confirmed.' );
		}
		self::record_event(
			$engagement_id,
			absint( $engagement['inquiry_id'] ),
			'staff',
			$actor_user_id,
			'engagement_owner_updated',
			'engagement',
			$engagement_id,
			$engagement['status'],
			$engagement['status'],
			array(
				'owner_user_id'    => $owner_user_id,
				'participant_count'=> count( $participants ),
				'kickoff_status'   => $data['kickoff_status'],
			)
		);
		return self::find( $engagement_id );
	}

	public static function add_requirement( int $engagement_id, array $input, int $actor_user_id ) {
		$engagement = self::find( $engagement_id );
		if ( ! $engagement || in_array( $engagement['status'], array( 'completed', 'canceled' ), true ) ) {
			return new WP_Error( 'engagement_requirement_locked', __( 'Requirements cannot be added to this engagement.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$title = sanitize_text_field( (string) ( $input['requirement_title'] ?? '' ) );
		if ( '' === $title ) {
			return new WP_Error( 'engagement_requirement_title_required', __( 'Enter a requirement title.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$key = sanitize_key( (string) ( $input['requirement_key'] ?? '' ) );
		if ( '' === $key ) {
			$key = 'custom_' . substr( hash( 'sha256', strtolower( $title ) . '|' . wp_generate_uuid4() ), 0, 16 );
		}
		return self::insert_requirement(
			$engagement,
			array(
				'requirement_key' => $key,
				'title'           => $title,
				'description'     => sanitize_textarea_field( (string) ( $input['requirement_description'] ?? '' ) ),
				'category'        => SC_EI_Engagement_Schema::sanitize_requirement_category( (string) ( $input['requirement_category'] ?? 'other' ) ),
				'status'          => 'pending',
				'is_required'     => empty( $input['is_required'] ) ? 0 : 1,
				'sender_visible'  => empty( $input['sender_visible'] ) ? 0 : 1,
				'due_date'        => self::sanitize_date( (string) ( $input['due_date'] ?? '' ) ),
				'assigned_user_id'=> absint( $input['assigned_user_id'] ?? 0 ),
				'sort_order'      => absint( $input['sort_order'] ?? 100 ),
			),
			$actor_user_id,
			true
		);
	}

	public static function update_requirement( int $requirement_id, array $input, int $actor_user_id ) {
		global $wpdb;

		$requirement = self::find_requirement( $requirement_id );
		if ( ! $requirement ) {
			return new WP_Error( 'engagement_requirement_not_found', __( 'The onboarding requirement was not found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$engagement = self::find( absint( $requirement['engagement_id'] ) );
		if ( ! $engagement || in_array( $engagement['status'], array( 'completed', 'canceled' ), true ) ) {
			return new WP_Error( 'engagement_requirement_locked', __( 'This onboarding requirement is locked.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$status = SC_EI_Engagement_Schema::sanitize_requirement_status( (string) ( $input['requirement_status'] ?? $requirement['status'] ) );
		$note = sanitize_textarea_field( (string) ( $input['completion_note'] ?? $requirement['completion_note'] ) );
		if ( in_array( $status, array( 'complete', 'waived', 'blocked' ), true ) && '' === trim( $note ) ) {
			return new WP_Error( 'engagement_requirement_note_required', __( 'Add a note for completed, waived, or blocked requirements.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		$data = array(
			'status'             => $status,
			'is_required'        => empty( $input['is_required'] ) ? 0 : 1,
			'sender_visible'     => empty( $input['sender_visible'] ) ? 0 : 1,
			'due_date'           => self::sanitize_date( (string) ( $input['due_date'] ?? $requirement['due_date'] ) ),
			'assigned_user_id'   => absint( $input['assigned_user_id'] ?? $requirement['assigned_user_id'] ) ?: null,
			'completion_note'    => $note,
			'evidence_reference' => sanitize_text_field( (string) ( $input['evidence_reference'] ?? $requirement['evidence_reference'] ) ),
			'completed_by'       => 'complete' === $status ? $actor_user_id : null,
			'completed_at'       => 'complete' === $status ? $now : null,
			'waived_by'          => 'waived' === $status ? $actor_user_id : null,
			'waived_at'          => 'waived' === $status ? $now : null,
			'row_version'        => absint( $requirement['row_version'] ) + 1,
			'updated_at'         => $now,
		);
		$updated = $wpdb->update(
			SC_EI_Database::table( 'engagement_requirements' ),
			$data,
			array( 'id' => $requirement_id, 'row_version' => absint( $requirement['row_version'] ) ),
			self::formats( $data, self::requirement_integer_fields() ),
			array( '%d', '%d' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'engagement_requirement_conflict', __( 'The requirement changed before the update was saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::record_event(
			absint( $requirement['engagement_id'] ),
			absint( $requirement['inquiry_id'] ),
			'staff',
			$actor_user_id,
			'engagement_requirement_updated',
			'requirement',
			$requirement_id,
			$requirement['status'],
			$status,
			array(
				'requirement_key'  => $requirement['requirement_key'],
				'is_required'      => ! empty( $data['is_required'] ),
				'sender_visible'   => ! empty( $data['sender_visible'] ),
				'evidence_reference'=> $data['evidence_reference'],
			)
		);
		return self::find_requirement( $requirement_id );
	}

	public static function readiness( int $engagement_id ): array {
		$engagement = self::find( $engagement_id );
		if ( ! $engagement ) {
			return array( 'ready' => false, 'checks' => array(), 'blocking' => array( 'engagement_not_found' ) );
		}
		$settings = self::settings();
		$snapshot = self::snapshot( $engagement_id );
		$integrity = $snapshot ? self::verify_snapshot( $snapshot ) : false;
		$requirements = self::requirements( $engagement_id );
		$blocking_requirements = array_values(
			array_filter(
				$requirements,
				static fn( array $item ): bool =>
					! empty( $item['is_required'] )
					&& ! in_array( $item['status'], array( 'complete', 'waived' ), true )
			)
		);
		$checks = array(
			'contract_reference' => empty( $settings['engagement_require_contract_reference'] ) || ! empty( $engagement['contract_reference'] ),
			'owner_assigned'     => empty( $settings['engagement_require_owner'] ) || ! empty( $engagement['owner_user_id'] ),
			'snapshot_present'   => ! empty( $snapshot ),
			'snapshot_integrity' => empty( $settings['engagement_require_snapshot_hash'] ) || $integrity,
			'required_items'     => empty( $settings['engagement_require_all_required_items'] ) || empty( $blocking_requirements ),
			'proposal_contracted'=> 'contracted' === (string) $engagement['proposal_status'],
			'proposal_version'   => absint( $engagement['proposal_version_id'] ) === absint( $engagement['snapshot_proposal_version_id'] ),
			'privacy_state'      => ! in_array( $engagement['privacy_status'], array( 'restricted', 'erasure_requested', 'erased' ), true ),
		);
		return array(
			'ready'                 => ! in_array( false, $checks, true ),
			'checks'                => $checks,
			'blocking'              => array_keys( array_filter( $checks, static fn( bool $value ): bool => ! $value ) ),
			'blocking_requirements' => $blocking_requirements,
			'requirement_count'     => count( $requirements ),
			'snapshot_hash'         => $snapshot['content_hash'] ?? '',
		);
	}

	public static function mark_ready( int $engagement_id, int $actor_user_id ) {
		$engagement = self::find( $engagement_id );
		if ( ! $engagement || 'handoff_pending' !== $engagement['status'] ) {
			return new WP_Error( 'engagement_not_handoff_pending', __( 'Only a pending handoff can be marked ready.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$readiness = self::readiness( $engagement_id );
		if ( empty( $readiness['ready'] ) ) {
			return new WP_Error(
				'engagement_readiness_incomplete',
				__( 'Complete the blocking handoff requirements before marking this engagement ready.', 'sustainable-catalyst-engagement-intake' ),
				$readiness
			);
		}
		return self::transition(
			$engagement,
			'ready_for_setup',
			$actor_user_id,
			'engagement_ready',
			array(
				'ready_by' => $actor_user_id,
				'ready_at' => current_time( 'mysql', true ),
			),
			array( 'readiness' => $readiness )
		);
	}

	public static function activate( int $engagement_id, int $actor_user_id ) {
		$engagement = self::find( $engagement_id );
		if ( ! $engagement || 'ready_for_setup' !== $engagement['status'] ) {
			return new WP_Error( 'engagement_not_ready', __( 'Only a ready engagement can be activated.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$readiness = self::readiness( $engagement_id );
		if ( empty( $readiness['ready'] ) ) {
			return new WP_Error( 'engagement_readiness_changed', __( 'Readiness changed before activation. Review the handoff again.', 'sustainable-catalyst-engagement-intake' ), $readiness );
		}
		global $wpdb;
		$wpdb->query( 'START TRANSACTION' );
		$result = self::transition(
			$engagement,
			'active',
			$actor_user_id,
			'engagement_activated',
			array(
				'activated_by' => $actor_user_id,
				'activated_at' => current_time( 'mysql', true ),
			),
			array(
				'automatic_provisioning' => false,
				'automatic_invoice'      => false,
				'automatic_payment'      => false,
			)
		);
		if ( is_wp_error( $result ) ) {
			$wpdb->query( 'ROLLBACK' );
			return $result;
		}
		$status_updated = SC_EI_Inquiry_Repository::update_status(
			absint( $engagement['inquiry_id'] ),
			'accepted',
			'Engagement activated by authorized staff after contracted-proposal handoff and readiness review.'
		);
		if ( ! $status_updated ) {
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'engagement_activation_rollback', __( 'Activation could not update the inquiry atomically. The engagement remains ready for setup.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$wpdb->query( 'COMMIT' );
		SC_EI_Portal_Repository::create_portal_message(
			absint( $engagement['inquiry_id'] ),
			'outbound',
			sprintf(
				/* translators: %s engagement number. */
				__( 'Your engagement has been formally activated. Reference: %s. The separately executed agreement remains the binding commercial record.', 'sustainable-catalyst-engagement-intake' ),
				$engagement['engagement_number']
			),
			$actor_user_id
		);
		return self::find( $engagement_id );
	}

	public static function change_status(
		int $engagement_id,
		string $target_status,
		string $note,
		int $actor_user_id
	) {
		$engagement = self::find( $engagement_id );
		if ( ! $engagement ) {
			return new WP_Error( 'engagement_not_found', __( 'The engagement was not found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$target_status = SC_EI_Engagement_Schema::sanitize_status( $target_status );
		$allowed = array(
			'active' => array( 'paused', 'completed', 'canceled' ),
			'paused' => array( 'active', 'completed', 'canceled' ),
			'handoff_pending' => array( 'canceled' ),
			'ready_for_setup' => array( 'canceled' ),
		);
		if ( empty( $allowed[ $engagement['status'] ] ) || ! in_array( $target_status, $allowed[ $engagement['status'] ], true ) ) {
			return new WP_Error( 'engagement_transition_invalid', __( 'That engagement transition is not permitted.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$note = sanitize_textarea_field( $note );
		if ( '' === trim( $note ) ) {
			return new WP_Error( 'engagement_transition_note_required', __( 'Record a reason or completion note.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		$extra = array();
		$event = '';
		if ( 'paused' === $target_status ) {
			$event = 'engagement_paused';
			$extra = array( 'paused_by' => $actor_user_id, 'paused_at' => $now, 'pause_reason' => $note );
		} elseif ( 'active' === $target_status && 'paused' === $engagement['status'] ) {
			$event = 'engagement_resumed';
			$extra = array( 'paused_by' => null, 'paused_at' => null, 'pause_reason' => '' );
		} elseif ( 'completed' === $target_status ) {
			$event = 'engagement_completed';
			$extra = array( 'completed_by' => $actor_user_id, 'completed_at' => $now, 'completion_note' => $note );
		} elseif ( 'canceled' === $target_status ) {
			$event = 'engagement_canceled';
			$extra = array( 'canceled_by' => $actor_user_id, 'canceled_at' => $now, 'cancellation_reason' => $note );
		}
		$result = self::transition(
			$engagement,
			$target_status,
			$actor_user_id,
			$event,
			$extra,
			array( 'note' => $note )
		);
		if ( ! is_wp_error( $result ) && 'canceled' === $target_status && 'active' !== $engagement['status'] && 'paused' !== $engagement['status'] ) {
			SC_EI_Portal_Repository::create_portal_message(
				absint( $engagement['inquiry_id'] ),
				'outbound',
				sprintf(
					/* translators: %s engagement number. */
					__( 'The engagement handoff %s was closed before activation. Contact Sustainable Catalyst through the secure portal for context.', 'sustainable-catalyst-engagement-intake' ),
					$engagement['engagement_number']
				),
				$actor_user_id
			);
		}
		return $result;
	}

	public static function find( int $id ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'engagements' );
		$proposals = SC_EI_Database::table( 'proposals' );
		$snapshots = SC_EI_Database::table( 'engagement_snapshots' );
		$inquiries = SC_EI_Database::table( 'inquiries' );
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT e.*, p.status AS proposal_status, p.proposal_number,
					p.contracted_at, s.content_hash AS snapshot_content_hash,
					s.proposal_version_id AS snapshot_proposal_version_id,
					s.proposal_content_hash, s.snapshot_version,
					i.reference, i.contact_name, i.contact_email, i.organization,
					i.privacy_status, u.display_name AS owner_name
				FROM {$table} e
				LEFT JOIN {$proposals} p ON p.id = e.proposal_id
				LEFT JOIN {$snapshots} s ON s.id = e.current_snapshot_id
				LEFT JOIN {$inquiries} i ON i.id = e.inquiry_id
				LEFT JOIN {$wpdb->users} u ON u.ID = e.owner_user_id
				WHERE e.id = %d",
				$id
			),
			ARRAY_A
		);
		return $row ?: null;
	}

	public static function find_by_proposal( int $proposal_id ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'engagements' );
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE proposal_id = %d LIMIT 1", $proposal_id ) );
		return $id ? self::find( absint( $id ) ) : null;
	}

	public static function for_inquiry( int $inquiry_id, bool $portal_visible = false ): array {
		global $wpdb;
		$table = SC_EI_Database::table( 'engagements' );
		$where = $portal_visible ? "AND status IN ('handoff_pending','ready_for_setup','active','paused','completed','canceled')" : '';
		$ids = (array) $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE inquiry_id = %d {$where} ORDER BY created_at DESC, id DESC",
				$inquiry_id
			)
		);
		return array_values( array_filter( array_map( array( __CLASS__, 'find' ), array_map( 'absint', $ids ) ) ) );
	}

	public static function eligible_proposals( int $inquiry_id = 0 ): array {
		global $wpdb;
		$proposals = SC_EI_Database::table( 'proposals' );
		$versions = SC_EI_Database::table( 'proposal_versions' );
		$engagements = SC_EI_Database::table( 'engagements' );
		$where = "p.status = 'contracted' AND p.current_version_id IS NOT NULL AND e.id IS NULL";
		$params = array();
		if ( $inquiry_id > 0 ) {
			$where .= ' AND p.inquiry_id = %d';
			$params[] = $inquiry_id;
		}
		$sql = "SELECT p.*, v.version_number, v.title, v.content_hash
			FROM {$proposals} p
			LEFT JOIN {$versions} v ON v.id = p.current_version_id
			LEFT JOIN {$engagements} e ON e.proposal_id = p.id
			WHERE {$where}
			ORDER BY p.contracted_at DESC, p.id DESC
			LIMIT 500";
		return $params
			? (array) $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A )
			: (array) $wpdb->get_results( $sql, ARRAY_A );
	}

	public static function all( array $args = array() ): array {
		global $wpdb;
		$args = wp_parse_args( $args, array( 'status' => '', 'owner_user_id' => 0, 'limit' => 500 ) );
		$table = SC_EI_Database::table( 'engagements' );
		$where = array( '1=1' );
		$params = array();
		$status = sanitize_key( (string) $args['status'] );
		if ( isset( SC_EI_Engagement_Schema::statuses()[ $status ] ) ) {
			$where[] = 'status = %s';
			$params[] = $status;
		}
		if ( absint( $args['owner_user_id'] ) ) {
			$where[] = 'owner_user_id = %d';
			$params[] = absint( $args['owner_user_id'] );
		}
		$sql = "SELECT id FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY updated_at DESC, id DESC LIMIT %d';
		$params[] = max( 1, min( 2000, absint( $args['limit'] ) ) );
		$ids = (array) $wpdb->get_col( $wpdb->prepare( $sql, $params ) );
		return array_values( array_filter( array_map( array( __CLASS__, 'find' ), array_map( 'absint', $ids ) ) ) );
	}

	public static function snapshot( int $engagement_id ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'engagement_snapshots' );
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE engagement_id = %d ORDER BY snapshot_version DESC LIMIT 1",
				$engagement_id
			),
			ARRAY_A
		);
		return $row ?: null;
	}

	public static function requirements( int $engagement_id, bool $sender_visible_only = false ): array {
		global $wpdb;
		$table = SC_EI_Database::table( 'engagement_requirements' );
		$visibility = $sender_visible_only ? 'AND sender_visible = 1' : '';
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.*, u.display_name AS assigned_name
				FROM {$table} r
				LEFT JOIN {$wpdb->users} u ON u.ID = r.assigned_user_id
				WHERE r.engagement_id = %d {$visibility}
				ORDER BY r.sort_order ASC, r.id ASC",
				$engagement_id
			),
			ARRAY_A
		);
	}

	public static function find_requirement( int $id ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'engagement_requirements' );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A );
		return $row ?: null;
	}

	public static function events( int $engagement_id, int $limit = 500 ): array {
		global $wpdb;
		$table = SC_EI_Database::table( 'engagement_events' );
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT e.*, u.display_name AS actor_name
				FROM {$table} e
				LEFT JOIN {$wpdb->users} u ON u.ID = e.actor_id
				WHERE e.engagement_id = %d
				ORDER BY e.created_at DESC, e.id DESC
				LIMIT %d",
				$engagement_id,
				max( 1, min( 2000, $limit ) )
			),
			ARRAY_A
		);
	}

	public static function metrics(): array {
		global $wpdb;
		$table = SC_EI_Database::table( 'engagements' );
		$requirements = SC_EI_Database::table( 'engagement_requirements' );
		return array(
			'total'             => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ),
			'handoff_pending'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'handoff_pending'" ),
			'ready_for_setup'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'ready_for_setup'" ),
			'active'            => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'active'" ),
			'paused'            => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'paused'" ),
			'completed'         => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'completed'" ),
			'canceled'          => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'canceled'" ),
			'blocking_required' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$requirements} WHERE is_required = 1 AND status NOT IN ('complete','waived')" ),
		);
	}

	public static function export_for_inquiry( int $inquiry_id ): array {
		$engagements = self::for_inquiry( $inquiry_id, false );
		$result = array();
		foreach ( $engagements as $engagement ) {
			$result[] = self::handoff_package( absint( $engagement['id'] ) );
		}
		return array(
			'schema'      => 'sc-engagement-intake-engagement-handoff/1.0',
			'generated_at'=> current_time( 'mysql', true ),
			'engagements' => $result,
		);
	}

	public static function handoff_package( int $engagement_id ): array {
		$engagement = self::find( $engagement_id );
		if ( ! $engagement ) {
			return array();
		}
		$snapshot = self::snapshot( $engagement_id );
		$requirements = self::requirements( $engagement_id );
		$events = self::events( $engagement_id, 2000 );
		$payload = $snapshot ? json_decode( (string) $snapshot['payload_json'], true ) : array();
		$settings = self::settings();
		return array(
			'schema'                  => 'sc-engagement-handoff-package/1.0',
			'engagement'              => $engagement,
			'commercial_snapshot'     => array(
				'metadata' => $snapshot,
				'payload'  => is_array( $payload ) ? $payload : array(),
				'integrity_verified' => $snapshot ? self::verify_snapshot( $snapshot ) : false,
			),
			'onboarding_requirements' => $requirements,
			'events'                  => $events,
			'integration_targets'     => array(
				'workbench' => array(
					'enabled'       => ! empty( $settings['engagement_allow_workbench_export'] ),
					'status'        => $engagement['workbench_handoff_status'],
					'provisioned'   => false,
					'automatic'     => false,
					'handoff_key'   => $engagement['public_id'],
				),
				'decision_studio' => array(
					'enabled'       => ! empty( $settings['engagement_allow_decision_studio_export'] ),
					'status'        => $engagement['decision_studio_handoff_status'],
					'provisioned'   => false,
					'automatic'     => false,
					'handoff_key'   => $engagement['public_id'],
				),
			),
			'fixed_boundaries' => array(
				'automatic_activation'   => false,
				'automatic_provisioning' => false,
				'automatic_invoice'      => false,
				'automatic_payment'      => false,
				'electronic_signature'   => false,
				'contract_generation'    => false,
			),
		);
	}

	public static function redact_for_privacy( int $inquiry_id, string $now ): bool {
		global $wpdb;
		$engagements = $wpdb->query(
			$wpdb->prepare(
				"UPDATE " . SC_EI_Database::table( 'engagements' ) . "
				SET sender_organization = '', onboarding_summary = '', sender_summary = '',
					internal_notes = '', external_project_reference = '',
					pause_reason = '', completion_note = '', cancellation_reason = '',
					updated_at = %s
				WHERE inquiry_id = %d",
				$now,
				$inquiry_id
			)
		);
		$tombstone_json = wp_json_encode(
			array(
				'personal_data_erased'      => true,
				'engagement_schema_version' => SC_EI_ENGAGEMENT_SCHEMA_VERSION,
				'commercial_hash_retained'  => true,
			),
			JSON_UNESCAPED_SLASHES
		);
		$tombstone_hash = hash( 'sha256', (string) $tombstone_json );
		$snapshots = $wpdb->query(
			$wpdb->prepare(
				"UPDATE " . SC_EI_Database::table( 'engagement_snapshots' ) . "
				SET payload_json = %s, content_hash = %s
				WHERE inquiry_id = %d",
				$tombstone_json,
				$tombstone_hash,
				$inquiry_id
			)
		);
		$requirements = $wpdb->query(
			$wpdb->prepare(
				"UPDATE " . SC_EI_Database::table( 'engagement_requirements' ) . "
				SET description = '', completion_note = '', evidence_reference = '', updated_at = %s
				WHERE inquiry_id = %d",
				$now,
				$inquiry_id
			)
		);
		$events = $wpdb->query(
			$wpdb->prepare(
				"UPDATE " . SC_EI_Database::table( 'engagement_events' ) . "
				SET context_json = %s
				WHERE inquiry_id = %d",
				wp_json_encode(
					array(
						'personal_data_erased'      => true,
						'engagement_schema_version' => SC_EI_ENGAGEMENT_SCHEMA_VERSION,
					)
				),
				$inquiry_id
			)
		);
		return false !== $engagements && false !== $snapshots && false !== $requirements && false !== $events;
	}

	public static function verify_snapshot( array $snapshot ): bool {
		if ( empty( $snapshot['payload_json'] ) || empty( $snapshot['content_hash'] ) ) {
			return false;
		}
		return hash_equals( (string) $snapshot['content_hash'], hash( 'sha256', (string) $snapshot['payload_json'] ) );
	}

	public static function note_portal_view( int $engagement_id, int $inquiry_id, int $session_id ): void {
		self::record_event(
			$engagement_id,
			$inquiry_id,
			'sender',
			$session_id,
			'engagement_portal_viewed',
			'engagement',
			$engagement_id,
			'',
			'viewed',
			array( 'sender_safe' => true )
		);
	}

	private static function snapshot_payload( array $proposal, array $inquiry ): array {
		return array(
			'schema' => 'sc-engagement-contracted-proposal-snapshot/1.0',
			'captured_at' => current_time( 'mysql', true ),
			'inquiry' => array(
				'id'              => absint( $inquiry['id'] ),
				'public_id'       => $inquiry['public_id'],
				'reference'       => $inquiry['reference'],
				'inquiry_type'    => $inquiry['inquiry_type'],
				'organization'    => $inquiry['organization'],
				'service_interest'=> $inquiry['service_interest'],
				'project_summary' => $inquiry['project_summary'],
				'desired_outcome' => $inquiry['desired_outcome'],
				'desired_start_date'=> $inquiry['desired_start_date'],
				'deadline_date'   => $inquiry['deadline_date'],
			),
			'proposal' => array(
				'id'                    => absint( $proposal['id'] ),
				'public_id'             => $proposal['public_id'],
				'proposal_number'       => $proposal['proposal_number'],
				'proposal_status'       => $proposal['status'],
				'proposal_version_id'   => absint( $proposal['current_version_id'] ),
				'proposal_version'      => absint( $proposal['version_number'] ),
				'proposal_content_hash' => $proposal['content_hash'],
				'title'                 => $proposal['title'],
				'executive_summary'     => $proposal['executive_summary'],
				'scope'                 => json_decode( (string) $proposal['scope_json'], true ) ?: array(),
				'deliverables'          => json_decode( (string) $proposal['deliverables_json'], true ) ?: array(),
				'exclusions'            => json_decode( (string) $proposal['exclusions_json'], true ) ?: array(),
				'assumptions'           => json_decode( (string) $proposal['assumptions_json'], true ) ?: array(),
				'timeline'              => $proposal['timeline_text'],
				'fee_summary'           => $proposal['fee_summary'],
				'payment_terms'         => $proposal['payment_terms'],
				'proposal_terms'        => $proposal['legal_terms'],
				'currency'              => $proposal['currency'],
				'total_minor'           => absint( $proposal['total_minor'] ),
				'sender_response'       => $proposal['sender_response'],
				'sender_authority_attested'=> ! empty( $proposal['sender_authority_attested'] ),
				'boundary_acknowledged' => ! empty( $proposal['boundary_acknowledged'] ),
				'accepted_at'           => $proposal['accepted_at'],
				'contract_reference'    => $proposal['contract_reference'],
				'contracted_by'         => absint( $proposal['contracted_by'] ),
				'contracted_at'         => $proposal['contracted_at'],
			),
			'boundaries' => array(
				'binding_record'          => 'external_contract',
				'portal_acceptance_signature'=> false,
				'automatic_activation'    => false,
				'automatic_provisioning'  => false,
				'automatic_invoice'       => false,
				'automatic_payment'       => false,
			),
		);
	}

	private static function insert_requirement(
		array $engagement,
		array $input,
		int $actor_user_id,
		bool $record_event
	) {
		global $wpdb;
		$key = sanitize_key( (string) ( $input['requirement_key'] ?? '' ) );
		if ( '' === $key ) {
			return new WP_Error( 'engagement_requirement_key_required', __( 'A requirement key is required.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$assigned_user_id = absint( $input['assigned_user_id'] ?? 0 );
		if ( $assigned_user_id && ! get_userdata( $assigned_user_id ) ) {
			return new WP_Error( 'engagement_requirement_assignee_invalid', __( 'Choose a valid requirement assignee.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		$status = SC_EI_Engagement_Schema::sanitize_requirement_status( (string) ( $input['status'] ?? 'pending' ) );
		$data = array(
			'public_id'          => wp_generate_uuid4(),
			'engagement_id'      => absint( $engagement['id'] ),
			'inquiry_id'         => absint( $engagement['inquiry_id'] ),
			'requirement_key'    => $key,
			'title'              => sanitize_text_field( (string) ( $input['title'] ?? '' ) ),
			'description'        => sanitize_textarea_field( (string) ( $input['description'] ?? '' ) ),
			'category'           => SC_EI_Engagement_Schema::sanitize_requirement_category( (string) ( $input['category'] ?? 'other' ) ),
			'status'             => $status,
			'is_required'        => empty( $input['is_required'] ) ? 0 : 1,
			'sender_visible'     => empty( $input['sender_visible'] ) ? 0 : 1,
			'due_date'           => self::sanitize_date( (string) ( $input['due_date'] ?? '' ) ),
			'assigned_user_id'   => $assigned_user_id ?: null,
			'completion_note'    => sanitize_textarea_field( (string) ( $input['completion_note'] ?? '' ) ),
			'evidence_reference' => sanitize_text_field( (string) ( $input['evidence_reference'] ?? '' ) ),
			'sort_order'         => absint( $input['sort_order'] ?? 100 ),
			'completed_by'       => 'complete' === $status ? $actor_user_id : null,
			'completed_at'       => 'complete' === $status ? $now : null,
			'waived_by'          => 'waived' === $status ? $actor_user_id : null,
			'waived_at'          => 'waived' === $status ? $now : null,
			'row_version'        => 0,
			'created_by'         => $actor_user_id,
			'created_at'         => $now,
			'updated_at'         => $now,
		);
		$inserted = $wpdb->insert(
			SC_EI_Database::table( 'engagement_requirements' ),
			$data,
			self::formats( $data, self::requirement_integer_fields() )
		);
		if ( false === $inserted ) {
			return new WP_Error( 'engagement_requirement_save_failed', __( 'The onboarding requirement could not be saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$id = (int) $wpdb->insert_id;
		if ( $record_event ) {
			self::record_event(
				absint( $engagement['id'] ),
				absint( $engagement['inquiry_id'] ),
				'staff',
				$actor_user_id,
				'engagement_requirement_created',
				'requirement',
				$id,
				'',
				$status,
				array(
					'requirement_key' => $key,
					'is_required'     => ! empty( $data['is_required'] ),
					'sender_visible'  => ! empty( $data['sender_visible'] ),
				)
			);
		}
		return self::find_requirement( $id );
	}

	private static function auto_complete_requirement( int $engagement_id, string $key, int $actor_user_id, string $note ): void {
		global $wpdb;
		$table = SC_EI_Database::table( 'engagement_requirements' );
		$requirement = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE engagement_id = %d AND requirement_key = %s LIMIT 1",
				$engagement_id,
				$key
			),
			ARRAY_A
		);
		if ( ! $requirement || in_array( $requirement['status'], array( 'complete', 'waived' ), true ) ) {
			return;
		}
		self::update_requirement(
			absint( $requirement['id'] ),
			array(
				'requirement_status' => 'complete',
				'is_required'        => ! empty( $requirement['is_required'] ),
				'sender_visible'     => ! empty( $requirement['sender_visible'] ),
				'due_date'           => $requirement['due_date'],
				'assigned_user_id'   => $requirement['assigned_user_id'],
				'completion_note'    => $note,
				'evidence_reference' => $requirement['evidence_reference'],
			),
			$actor_user_id
		);
	}

	private static function transition(
		array $engagement,
		string $target_status,
		int $actor_user_id,
		string $event_type,
		array $extra,
		array $context
	) {
		global $wpdb;
		$now = current_time( 'mysql', true );
		$data = array_merge(
			array(
				'status'      => $target_status,
				'row_version' => absint( $engagement['row_version'] ) + 1,
				'updated_at'  => $now,
			),
			$extra
		);
		$updated = $wpdb->update(
			SC_EI_Database::table( 'engagements' ),
			$data,
			array(
				'id'          => absint( $engagement['id'] ),
				'row_version' => absint( $engagement['row_version'] ),
				'status'      => $engagement['status'],
			),
			self::formats( $data, self::engagement_integer_fields() ),
			array( '%d', '%d', '%s' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'engagement_transition_conflict', __( 'The engagement changed before the transition was saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::record_event(
			absint( $engagement['id'] ),
			absint( $engagement['inquiry_id'] ),
			'staff',
			$actor_user_id,
			$event_type,
			'engagement',
			absint( $engagement['id'] ),
			$engagement['status'],
			$target_status,
			$context
		);
		SC_EI_Audit_Log::record(
			$event_type,
			'Authorized staff changed the engagement lifecycle state.',
			array(
				'engagement_number' => $engagement['engagement_number'],
				'from_status'       => $engagement['status'],
				'to_status'         => $target_status,
				'automatic'         => false,
			),
			absint( $engagement['inquiry_id'] ),
			null,
			$actor_user_id
		);
		return self::find( absint( $engagement['id'] ) );
	}

	private static function record_event(
		int $engagement_id,
		int $inquiry_id,
		string $actor_type,
		int $actor_id,
		string $event_type,
		string $object_type,
		int $object_id,
		string $from_status,
		string $to_status,
		array $context
	): void {
		global $wpdb;
		$wpdb->insert(
			SC_EI_Database::table( 'engagement_events' ),
			array(
				'public_id'    => wp_generate_uuid4(),
				'engagement_id'=> $engagement_id,
				'inquiry_id'   => $inquiry_id,
				'actor_type'   => sanitize_key( $actor_type ),
				'actor_id'     => $actor_id ?: null,
				'event_type'   => sanitize_key( $event_type ),
				'object_type'  => sanitize_key( $object_type ),
				'object_id'    => $object_id,
				'from_status'  => sanitize_key( $from_status ),
				'to_status'    => sanitize_key( $to_status ),
				'context_json' => wp_json_encode( self::sanitize_context( $context ) ),
				'created_at'   => current_time( 'mysql', true ),
			),
			array( '%s', '%d', '%d', '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
		);
	}

	private static function sanitize_context( array $context ): array {
		$clean = array();
		foreach ( $context as $key => $value ) {
			$key = sanitize_key( (string) $key );
			if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
				$clean[ $key ] = $value;
			} elseif ( is_array( $value ) ) {
				$clean[ $key ] = array_map(
					static fn( $item ) => is_scalar( $item ) ? mb_substr( sanitize_text_field( (string) $item ), 0, 500 ) : '',
					array_slice( $value, 0, 100 )
				);
			} elseif ( is_scalar( $value ) ) {
				$clean[ $key ] = mb_substr( sanitize_text_field( (string) $value ), 0, 1000 );
			}
		}
		return $clean;
	}

	private static function sanitize_date( string $value ): ?string {
		$value = trim( sanitize_text_field( $value ) );
		if ( '' === $value ) {
			return null;
		}
		$timestamp = strtotime( $value . ' UTC' );
		return $timestamp ? gmdate( 'Y-m-d', $timestamp ) : null;
	}

	private static function sanitize_datetime( string $value ): ?string {
		$value = trim( sanitize_text_field( $value ) );
		if ( '' === $value ) {
			return null;
		}
		$timestamp = strtotime( $value . ' UTC' );
		return $timestamp ? gmdate( 'Y-m-d H:i:s', $timestamp ) : null;
	}

	private static function sanitize_handoff_status( string $value ): string {
		$value = sanitize_key( $value );
		return in_array( $value, array( 'not_requested', 'prepared', 'exported', 'acknowledged' ), true )
			? $value
			: 'not_requested';
	}

	private static function engagement_integer_fields(): array {
		return array(
			'inquiry_id', 'proposal_id', 'proposal_version_id', 'access_id',
			'current_snapshot_id', 'total_minor', 'owner_user_id', 'handoff_prepared_by',
			'ready_by', 'activated_by', 'paused_by', 'completed_by', 'canceled_by',
			'row_version', 'created_by',
		);
	}

	private static function requirement_integer_fields(): array {
		return array(
			'engagement_id', 'inquiry_id', 'is_required', 'sender_visible',
			'assigned_user_id', 'sort_order', 'completed_by', 'waived_by',
			'row_version', 'created_by',
		);
	}

	private static function formats( array $data, array $integer_fields ): array {
		return array_map(
			static fn( string $key ): string => in_array( $key, $integer_fields, true ) ? '%d' : '%s',
			array_keys( $data )
		);
	}
}
