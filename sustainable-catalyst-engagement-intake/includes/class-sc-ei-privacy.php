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
			$item_data = array();
			foreach ( self::inquiry_export_fields() as $key => $label ) {
				if ( isset( $row[ $key ] ) && '' !== (string) $row[ $key ] ) {
					$item_data[] = array(
						'name'  => $label,
						'value' => (string) $row[ $key ],
					);
				}
			}

			$data[] = array(
				'group_id'    => 'sc-engagement-intake',
				'group_label' => __( 'Engagement Intake', 'sustainable-catalyst-engagement-intake' ),
				'item_id'     => 'sc-ei-' . $row['id'],
				'data'        => $item_data,
			);

			foreach ( SC_EI_Review_Repository::history( absint( $row['id'] ), 500 ) as $review ) {
				$review_data = array();
				foreach (
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
					) as $key => $label
				) {
					if ( isset( $review[ $key ] ) && '' !== (string) $review[ $key ] ) {
						$review_data[] = array(
							'name'  => $label,
							'value' => (string) $review[ $key ],
						);
					}
				}

				$data[] = array(
					'group_id'    => 'sc-engagement-intake-reviews',
					'group_label' => __( 'Engagement Intake Administrative Reviews', 'sustainable-catalyst-engagement-intake' ),
					'item_id'     => 'sc-ei-review-' . $review['id'],
					'data'        => $review_data,
				);
			}

			foreach ( SC_EI_Attachment_Repository::for_inquiry( absint( $row['id'] ), true ) as $attachment ) {
				$attachment_data = array();
				foreach (
					array(
						'original_name'     => 'Original document name',
						'mime_type'         => 'Document MIME type',
						'extension'         => 'Document extension',
						'size_bytes'        => 'Document size in bytes',
						'document_category' => 'Document category',
						'document_notes'    => 'Document notes',
						'confidentiality'   => 'Confidentiality classification',
						'quarantine_status'       => 'Quarantine status',
						'validation_status'       => 'Validation status',
						'scan_status'             => 'Malware scan status',
						'scanner_provider'        => 'Scanner provider',
						'scan_message'            => 'Scanner message',
						'scan_attempts'           => 'Scanner attempts',
						'last_scanned_at'         => 'Last scanned at',
						'storage_status'          => 'Storage status',
						'integrity_status'        => 'Integrity status',
						'last_verified_at'        => 'Last verified at',
						'last_verification_source'=> 'Verification source',
						'retention_until'         => 'Retention until',
						'uploaded_at'       => 'Uploaded at',
						'deleted_at'        => 'Deleted at',
					) as $key => $label
				) {
					if ( isset( $attachment[ $key ] ) && '' !== (string) $attachment[ $key ] ) {
						$attachment_data[] = array(
							'name'  => $label,
							'value' => (string) $attachment[ $key ],
						);
					}
				}

				$data[] = array(
					'group_id'    => 'sc-engagement-intake-documents',
					'group_label' => __( 'Engagement Intake Documents', 'sustainable-catalyst-engagement-intake' ),
					'item_id'     => 'sc-ei-attachment-' . $attachment['id'],
					'data'        => $attachment_data,
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

				$attachment_updated = $wpdb->update(
					SC_EI_Database::table( 'attachments' ),
					array(
						'original_name'              => '[erased]',
						'document_notes'             => '',
						'metadata_json'              => '{}',
						'quarantine_status'          => 'deleted',
						'storage_status'             => 'deleted',
						'integrity_status'           => 'deleted',
						'last_verified_at'           => $now,
						'last_verified_by'           => 0,
						'last_verification_source'   => 'privacy_erasure',
						'last_verification_message'  => 'Physical file deleted or confirmed absent during privacy erasure.',
						'deleted_by'                 => 0,
						'deleted_at'                 => $attachment['deleted_at'] ?: $now,
					),
					array( 'id' => absint( $attachment['id'] ) ),
					array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s' ),
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

			$anonymous_email = 'deleted+' . $inquiry_id . '@example.invalid';
			$updated         = $wpdb->update(
				$table,
				array(
					'contact_name'       => '',
					'contact_email'      => $anonymous_email,
					'organization'       => '',
					'role_title'         => '',
					'teams_email'        => '',
					'phone_number'       => '',
					'city'               => '',
					'country'            => '',
					'participant_emails' => '[]',
					'accessibility_needs'=> '',
					'scheduling_notes'   => '',
					'message'            => '[Personal data erased through WordPress privacy tools.]',
					'project_summary'    => '',
					'desired_outcome'    => '',
					'relevant_links'     => '[]',
					'metadata_json'      => '{}',
					'review_summary'     => '',
					'decision_rationale' => '',
					'information_gaps'   => '',
					'conflict_notes'     => '',
					'escalation_reason'  => '',
					'updated_at'         => $now,
				),
				array( 'id' => $inquiry_id ),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);

			if ( false !== $updated ) {
				$removed = true;
				SC_EI_Audit_Log::record(
					'personal_data_erased',
					'Personal data erased through WordPress privacy tools.',
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
			'created_at'               => 'Submitted at',
			'updated_at'               => 'Last updated',
		);
	}
}
