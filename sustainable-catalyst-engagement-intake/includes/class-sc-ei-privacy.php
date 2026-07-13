<?php
/**
 * WordPress privacy exporter and eraser.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Privacy {

	public static function register(): void {
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'exporters' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'erasers' ) );
	}

	public static function exporters( array $exporters ): array {
		$exporters['sc-engagement-intake'] = array(
			'exporter_friendly_name' => __( 'Sustainable Catalyst Engagement Intake', 'sustainable-catalyst-engagement-intake' ),
			'callback'               => array( __CLASS__, 'export_by_email' ),
		);
		return $exporters;
	}

	public static function erasers( array $erasers ): array {
		$erasers['sc-engagement-intake'] = array(
			'eraser_friendly_name' => __( 'Sustainable Catalyst Engagement Intake', 'sustainable-catalyst-engagement-intake' ),
			'callback'             => array( __CLASS__, 'erase_by_email' ),
		);
		return $erasers;
	}

	public static function export_by_email( string $email_address, int $page = 1 ): array {
		global $wpdb;

		$table   = SC_EI_Database::table( 'inquiries' );
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE contact_email = %s ORDER BY created_at ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				sanitize_email( $email_address )
			),
			ARRAY_A
		);

		$data = array();
		foreach ( $results as $row ) {
			$inquiry_id = absint( $row['id'] );
			$data[] = array(
				'group_id'    => 'sc-engagement-intake',
				'group_label' => __( 'Engagement Intake', 'sustainable-catalyst-engagement-intake' ),
				'item_id'     => 'sc-ei-' . $inquiry_id,
				'data'        => self::export_fields( $row, self::inquiry_export_fields() ),
			);

			foreach ( SC_EI_Review_Repository::history( $inquiry_id, 500 ) as $review ) {
				$data[] = array(
					'group_id'    => 'sc-engagement-intake-reviews',
					'group_label' => __( 'Engagement Intake Administrative Reviews', 'sustainable-catalyst-engagement-intake' ),
					'item_id'     => 'sc-ei-review-' . $review['id'],
					'data'        => self::export_fields(
						$review,
						array(
							'event_type'            => 'Review event',
							'from_stage'            => 'Previous review stage',
							'to_stage'              => 'Review stage',
							'priority'              => 'Review priority',
							'fit_decision'          => 'Fit decision',
							'fit_confidence'        => 'Fit confidence',
							'risk_level'            => 'Risk level',
							'evidence_readiness'    => 'Evidence readiness',
							'scope_clarity'         => 'Scope clarity',
							'recommended_next_step' => 'Recommended next step',
							'summary'               => 'Review summary',
							'rationale'             => 'Decision rationale',
							'information_gaps'      => 'Information gaps',
							'conflict_notes'        => 'Conflict and independence notes',
							'escalation_status'     => 'Escalation status',
							'escalation_reason'     => 'Escalation reason',
							'due_at'                => 'Review due at',
							'inquiry_status'        => 'Inquiry status',
							'review_version'        => 'Review version',
							'created_at'            => 'Review recorded at',
						)
					),
				);
			}

			foreach ( SC_EI_Communication_Repository::for_inquiry( $inquiry_id, 1000, true ) as $communication ) {
				$data[] = array(
					'group_id'    => 'sc-engagement-intake-communications',
					'group_label' => __( 'Engagement Intake Communications', 'sustainable-catalyst-engagement-intake' ),
					'item_id'     => 'sc-ei-communication-' . $communication['id'],
					'data'        => self::export_fields(
						$communication,
						array(
							'direction'              => 'Direction',
							'channel'                => 'Channel',
							'communication_type'     => 'Communication type',
							'status'                 => 'Communication status',
							'subject'                => 'Subject',
							'body_text'              => 'Message or interaction summary',
							'sender_name'            => 'Sender name',
							'sender_email'           => 'Sender email',
							'recipient_name'         => 'Recipient name',
							'recipient_email'        => 'Recipient email',
							'cc_json'                => 'CC recipients',
							'template_key'           => 'Template key',
							'template_version'       => 'Template version',
							'is_automated'           => 'Automated policy event',
							'provider'               => 'Mail or record provider',
							'provider_message_id'    => 'Provider message ID',
							'attempt_count'          => 'Send attempts',
							'last_attempt_at'        => 'Last send attempt',
							'accepted_at'            => 'Accepted by mail transport at',
							'failed_at'              => 'Mail transport failure at',
							'error_code'             => 'Error code',
							'error_message'          => 'Error message',
							'occurred_at'             => 'Interaction occurred at',
							'privacy_classification' => 'Privacy classification',
							'message_hash'           => 'Message SHA-256',
							'created_at'             => 'Record created at',
							'updated_at'             => 'Record updated at',
							'deleted_at'             => 'Canceled or deleted at',
						)
					),
				);

				foreach ( SC_EI_Communication_Repository::events( absint( $communication['id'] ), 500 ) as $event ) {
					$data[] = array(
						'group_id'    => 'sc-engagement-intake-communication-events',
						'group_label' => __( 'Engagement Intake Communication Events', 'sustainable-catalyst-engagement-intake' ),
						'item_id'     => 'sc-ei-communication-event-' . $event['id'],
						'data'        => self::export_fields(
							$event,
							array(
								'event_type'          => 'Communication event',
								'from_status'          => 'Previous status',
								'to_status'            => 'New status',
								'provider'             => 'Provider',
								'provider_message_id'  => 'Provider message ID',
								'error_code'           => 'Error code',
								'error_message'        => 'Error message',
								'context_json'         => 'Event context',
								'created_at'           => 'Event recorded at',
							)
						),
					);
				}
			}

			foreach ( SC_EI_Attachment_Repository::for_inquiry( $inquiry_id, true ) as $attachment ) {
				$data[] = array(
					'group_id'    => 'sc-engagement-intake-documents',
					'group_label' => __( 'Engagement Intake Documents', 'sustainable-catalyst-engagement-intake' ),
					'item_id'     => 'sc-ei-attachment-' . $attachment['id'],
					'data'        => self::export_fields(
						$attachment,
						array(
							'original_name'             => 'Original document name',
							'mime_type'                 => 'Document MIME type',
							'extension'                 => 'Document extension',
							'size_bytes'                => 'Document size in bytes',
							'document_category'         => 'Document category',
							'document_notes'            => 'Document notes',
							'confidentiality'           => 'Confidentiality classification',
							'quarantine_status'         => 'Quarantine status',
							'validation_status'         => 'Validation status',
							'scan_status'               => 'Malware scan status',
							'scanner_provider'          => 'Scanner provider',
							'scan_message'              => 'Scanner message',
							'scan_attempts'             => 'Scanner attempts',
							'last_scanned_at'           => 'Last scanned at',
							'storage_status'            => 'Storage status',
							'integrity_status'          => 'Integrity status',
							'last_verified_at'          => 'Last verified at',
							'last_verification_source'  => 'Verification source',
							'retention_until'           => 'Retention until',
							'uploaded_at'               => 'Uploaded at',
							'deleted_at'                => 'Deleted at',
						)
					),
				);
			}
		}

		return array(
			'data' => $data,
			'done' => true,
		);
	}

	public static function erase_by_email( string $email_address, int $page = 1 ): array {
		global $wpdb;

		$table = SC_EI_Database::table( 'inquiries' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, reference FROM {$table} WHERE contact_email = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				sanitize_email( $email_address )
			),
			ARRAY_A
		);

		$removed  = false;
		$retained = false;
		$messages = array();

		foreach ( $rows as $row ) {
			$inquiry_id = absint( $row['id'] );
			$now        = current_time( 'mysql', true );

			foreach ( SC_EI_Attachment_Repository::for_inquiry( $inquiry_id, true ) as $attachment ) {
				$file_deleted = ! empty( $attachment['deleted_at'] )
					|| SC_EI_Storage::delete_file( (string) $attachment['relative_path'] );

				if ( ! $file_deleted ) {
					$retained   = true;
					$messages[] = __( 'At least one private document could not be deleted from protected storage and requires administrative review.', 'sustainable-catalyst-engagement-intake' );
					continue;
				}

				$attachment_data = array(
					'original_name'             => '[erased]',
					'document_notes'            => '',
					'metadata_json'             => '{}',
					'quarantine_status'         => 'deleted',
					'storage_status'            => 'deleted',
					'integrity_status'          => 'deleted',
					'last_verified_at'          => $now,
					'last_verified_by'          => 0,
					'last_verification_source'  => 'privacy_erasure',
					'last_verification_message' => 'Physical file deleted or confirmed absent during privacy erasure.',
					'deleted_by'                 => 0,
					'deleted_at'                 => $attachment['deleted_at'] ?: $now,
				);
				$attachment_updated = $wpdb->update(
					SC_EI_Database::table( 'attachments' ),
					$attachment_data,
					array( 'id' => absint( $attachment['id'] ) ),
					self::formats_for( $attachment_data, array( 'last_verified_by', 'deleted_by' ) ),
					array( '%d' )
				);

				if ( false === $attachment_updated ) {
					$retained = true;
				} else {
					$removed = true;
					SC_EI_Audit_Log::record(
						'attachment_personal_data_erased',
						'Private document deleted and identifying attachment metadata erased through WordPress privacy tools.',
						array(),
						$inquiry_id,
						absint( $attachment['id'] ),
						0
					);
				}
			}

			$review_rows_updated = $wpdb->query(
				$wpdb->prepare(
					"UPDATE " . SC_EI_Database::table( 'reviews' ) . "
					SET summary = '',
						rationale = '',
						information_gaps = '',
						conflict_notes = '',
						escalation_reason = '',
						snapshot_json = %s
					WHERE inquiry_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					wp_json_encode(
						array(
							'personal_data_erased'  => true,
							'review_schema_version' => SC_EI_REVIEW_SCHEMA_VERSION,
						)
					),
					$inquiry_id
				)
			);
			if ( false === $review_rows_updated ) {
				$retained   = true;
				$messages[] = __( 'Administrative review narratives could not be erased and require administrator attention.', 'sustainable-catalyst-engagement-intake' );
			} elseif ( $review_rows_updated > 0 ) {
				$removed = true;
			}

			$communication_rows_updated = $wpdb->query(
				$wpdb->prepare(
					"UPDATE " . SC_EI_Database::table( 'communications' ) . "
					SET subject = %s,
						body_text = %s,
						sender_name = '',
						sender_email = '',
						recipient_name = '',
						recipient_email = '',
						cc_json = '[]',
						provider_message_id = '',
						error_message = '',
						message_hash = '',
						dedupe_key = NULL,
						metadata_json = %s,
						updated_at = %s
					WHERE inquiry_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					'[Personal data erased]',
					'[Communication content erased through WordPress privacy tools.]',
					wp_json_encode(
						array(
							'personal_data_erased'           => true,
							'communication_schema_version'   => SC_EI_COMMUNICATION_SCHEMA_VERSION,
						)
					),
					$now,
					$inquiry_id
				)
			);
			if ( false === $communication_rows_updated ) {
				$retained   = true;
				$messages[] = __( 'Communication content could not be erased and requires administrator attention.', 'sustainable-catalyst-engagement-intake' );
			} elseif ( $communication_rows_updated > 0 ) {
				$removed = true;
			}

			$communication_event_rows_updated = $wpdb->query(
				$wpdb->prepare(
					"UPDATE " . SC_EI_Database::table( 'communication_events' ) . "
					SET provider_message_id = '',
						error_message = '',
						context_json = %s
					WHERE inquiry_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					wp_json_encode(
						array(
							'personal_data_erased'         => true,
							'communication_schema_version' => SC_EI_COMMUNICATION_SCHEMA_VERSION,
						)
					),
					$inquiry_id
				)
			);
			if ( false === $communication_event_rows_updated ) {
				$retained   = true;
				$messages[] = __( 'Communication event context could not be erased and requires administrator attention.', 'sustainable-catalyst-engagement-intake' );
			} elseif ( $communication_event_rows_updated > 0 ) {
				$removed = true;
			}

			$anonymous_email = 'deleted+' . $inquiry_id . '@example.invalid';
			$inquiry_data = array(
				'contact_name'         => '',
				'contact_email'        => $anonymous_email,
				'organization'         => '',
				'role_title'           => '',
				'subject'              => '[Personal data erased]',
				'teams_email'          => '',
				'phone_number'         => '',
				'timezone'             => '',
				'city'                 => '',
				'country'              => '',
				'preferred_weekdays'   => '[]',
				'preferred_time_windows'=> '',
				'participant_emails'   => '[]',
				'accessibility_needs'  => '',
				'scheduling_notes'     => '',
				'teams_meeting_url'    => '',
				'scheduled_start_utc'  => null,
				'scheduled_end_utc'    => null,
				'scheduled_timezone'   => '',
				'calendar_event_id'    => '',
				'message'              => '[Personal data erased through WordPress privacy tools.]',
				'project_summary'      => '',
				'desired_outcome'      => '',
				'relevant_links'       => '[]',
				'metadata_json'        => '{}',
				'review_summary'       => '',
				'decision_rationale'   => '',
				'information_gaps'     => '',
				'conflict_notes'       => '',
				'escalation_reason'    => '',
				'do_not_email_reason'  => '',
				'updated_at'           => $now,
			);

			$updated = $wpdb->update(
				$table,
				$inquiry_data,
				array( 'id' => $inquiry_id ),
				self::formats_for( $inquiry_data ),
				array( '%d' )
			);

			if ( false !== $updated ) {
				$removed = true;
				SC_EI_Audit_Log::record(
					'personal_data_erased',
					'Personal data, communication content, and review narratives erased through WordPress privacy tools.',
					array( 'reference' => $row['reference'] ),
					$inquiry_id,
					null,
					0
				);
			} else {
				$retained = true;
			}
		}

		return array(
			'items_removed'  => $removed,
			'items_retained' => $retained,
			'messages'       => array_values( array_unique( $messages ) ),
			'done'           => true,
		);
	}

	private static function export_fields( array $row, array $fields ): array {
		$data = array();
		foreach ( $fields as $key => $label ) {
			if ( isset( $row[ $key ] ) && '' !== (string) $row[ $key ] ) {
				$data[] = array(
					'name'  => $label,
					'value' => (string) $row[ $key ],
				);
			}
		}
		return $data;
	}

	private static function formats_for( array $data, array $integer_fields = array() ): array {
		return array_map(
			static fn( string $key ): string => in_array( $key, $integer_fields, true ) ? '%d' : '%s',
			array_keys( $data )
		);
	}

	private static function inquiry_export_fields(): array {
		return array(
			'reference'                => 'Inquiry reference',
			'inquiry_type'             => 'Inquiry type',
			'status'                   => 'Status',
			'form_variant'             => 'Intake experience',
			'source_page'              => 'Source page',
			'entry_cta'                => 'Entry CTA',
			'conversion_route'         => 'Conversion route',
			'contact_name'             => 'Name',
			'contact_email'            => 'Email',
			'organization'             => 'Organization',
			'role_title'               => 'Role',
			'subject'                  => 'Subject',
			'message'                  => 'Message',
			'project_summary'          => 'Project summary',
			'desired_outcome'          => 'Desired outcome',
			'service_interest'         => 'Service interest',
			'budget_range'             => 'Budget range',
			'desired_start_date'       => 'Desired start date',
			'deadline_date'            => 'Deadline',
			'preferred_contact_method' => 'Preferred contact method',
			'teams_email'              => 'Microsoft Teams email',
			'phone_number'             => 'Phone number',
			'timezone'                 => 'Time zone',
			'city'                     => 'City',
			'country'                  => 'Country',
			'meeting_request'          => 'Microsoft Teams meeting request',
			'preferred_weekdays'       => 'Preferred weekdays',
			'preferred_time_windows'   => 'Preferred time windows',
			'preferred_duration'       => 'Preferred duration',
			'participant_count'        => 'Participant count',
			'participant_emails'       => 'Participant emails',
			'accessibility_needs'      => 'Accessibility needs',
			'calendar_invite_consent'  => 'Calendar invitation consent',
			'scheduling_notes'         => 'Scheduling notes',
			'scheduling_status'        => 'Scheduling status',
			'teams_meeting_url'        => 'Microsoft Teams meeting URL',
			'scheduled_start_utc'      => 'Scheduled start UTC',
			'scheduled_end_utc'        => 'Scheduled end UTC',
			'scheduled_timezone'       => 'Scheduled timezone',
			'assigned_user_id'         => 'Assigned reviewer user ID',
			'review_stage'             => 'Administrative review stage',
			'review_priority'          => 'Review priority',
			'review_due_at'            => 'Review due at',
			'fit_decision'             => 'Fit decision',
			'fit_confidence'           => 'Fit confidence',
			'risk_level'               => 'Risk level',
			'evidence_readiness'       => 'Evidence readiness',
			'scope_clarity'            => 'Scope clarity',
			'recommended_next_step'    => 'Recommended next step',
			'review_summary'           => 'Review summary',
			'decision_rationale'       => 'Decision rationale',
			'information_gaps'         => 'Information gaps',
			'conflict_notes'           => 'Conflict and independence notes',
			'escalation_status'        => 'Escalation status',
			'escalation_reason'        => 'Escalation reason',
			'review_started_at'        => 'Review started at',
			'last_reviewed_at'         => 'Last reviewed at',
			'decision_at'              => 'Decision recorded at',
			'review_completed_at'      => 'Review completed at',
			'review_version'           => 'Review version',
			'communication_status'     => 'Communication state',
			'next_follow_up_at'        => 'Next follow-up at',
			'last_communication_at'    => 'Last communication at',
			'last_outbound_at'         => 'Last outbound at',
			'last_inbound_at'          => 'Last inbound at',
			'last_notification_at'     => 'Last notification at',
			'communication_count'      => 'Communication count',
			'unread_inbound_count'     => 'Unread inbound count',
			'do_not_email'             => 'Email suppression enabled',
			'do_not_email_reason'      => 'Email suppression reason',
			'communication_version'    => 'Communication state version',
			'created_at'               => 'Submitted at',
			'updated_at'               => 'Last updated',
		);
	}
}
