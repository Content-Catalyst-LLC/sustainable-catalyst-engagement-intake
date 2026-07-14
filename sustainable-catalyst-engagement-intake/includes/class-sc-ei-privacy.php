<?php
/**
 * WordPress privacy exporter and queue-only eraser bridge.
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

		$email = sanitize_email( $email_address );
		$inquiry_table = SC_EI_Database::table( 'inquiries' );
		$inquiries = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$inquiry_table} WHERE contact_email = %s ORDER BY created_at ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$email
			),
			ARRAY_A
		);

		$data = array();
		foreach ( $inquiries as $inquiry ) {
			$inquiry_id = absint( $inquiry['id'] );
			$data[] = array(
				'group_id'    => 'sc-engagement-intake',
				'group_label' => __( 'Engagement Intake', 'sustainable-catalyst-engagement-intake' ),
				'item_id'     => 'sc-ei-' . $inquiry_id,
				'data'        => self::export_fields( $inquiry, self::inquiry_export_fields() ),
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
								'event_type'         => 'Communication event',
								'from_status'         => 'Previous status',
								'to_status'           => 'New status',
								'provider'            => 'Provider',
								'provider_message_id' => 'Provider message ID',
								'error_code'          => 'Error code',
								'error_message'       => 'Error message',
								'context_json'        => 'Event context',
								'created_at'          => 'Event recorded at',
							)
						),
					);
				}
			}

			foreach ( SC_EI_Fit_Repository::for_inquiry( $inquiry_id, true ) as $assessment ) {
				$data[] = array(
					'group_id'    => 'sc-engagement-intake-fit-assessments',
					'group_label' => __( 'Engagement Intake Human Fit Assessments', 'sustainable-catalyst-engagement-intake' ),
					'item_id'     => 'sc-ei-fit-assessment-' . $assessment['id'],
					'data'        => self::export_fields(
						$assessment,
						array(
							'assessment_version'        => 'Assessment version',
							'status'                    => 'Assessment status',
							'recommendation'            => 'Human recommendation',
							'confidence'                => 'Confidence',
							'service_route'             => 'Service route',
							'scope_boundary'            => 'Scope boundary',
							'advisory_score'            => 'Advisory signal',
							'score_complete'            => 'Criteria complete',
							'material_concern_count'    => 'Material concern count',
							'second_review_required'    => 'Second review required',
							'second_review_reason'      => 'Second review reason',
							'second_review_disposition' => 'Second review disposition',
							'overall_summary'           => 'Overall summary',
							'recommendation_rationale'  => 'Recommendation rationale',
							'limitations_notes'         => 'Limitations and uncertainty',
							'conditions_for_fit'        => 'Conditions for fit',
							'referral_notes'            => 'Referral or decline notes',
							'human_attestation'         => 'Human attestation',
							'assistance_disclosure'     => 'Assistance disclosure',
							'assistance_notes'          => 'Assistance notes',
							'submitted_at'              => 'Submitted at',
							'finalized_at'              => 'Finalized at',
							'created_at'                => 'Created at',
							'updated_at'                => 'Updated at',
						)
					),
				);
				foreach ( $assessment['items'] as $item ) {
					$data[] = array(
						'group_id'    => 'sc-engagement-intake-fit-criteria',
						'group_label' => __( 'Engagement Intake Fit Assessment Criteria', 'sustainable-catalyst-engagement-intake' ),
						'item_id'     => 'sc-ei-fit-item-' . $item['id'],
						'data'        => self::export_fields(
							$item,
							array(
								'criterion_key'       => 'Criterion',
								'criterion_group'     => 'Criterion group',
								'rating'              => 'Human rating',
								'weight'              => 'Transparent weight',
								'numeric_value'       => 'Numeric rating value',
								'is_applicable'       => 'Applicable',
								'is_material_concern' => 'Material concern',
								'evidence_note'       => 'Evidence and reasoning',
								'concern_note'        => 'Concern or mitigation',
								'source_refs_json'    => 'Source references',
								'created_at'          => 'Created at',
								'updated_at'          => 'Updated at',
							)
						),
					);
				}
				foreach ( $assessment['second_reviews'] as $review ) {
					$data[] = array(
						'group_id'    => 'sc-engagement-intake-fit-reviews',
						'group_label' => __( 'Engagement Intake Fit Assessment Second Reviews', 'sustainable-catalyst-engagement-intake' ),
						'item_id'     => 'sc-ei-fit-review-' . $review['id'],
						'data'        => self::export_fields(
							$review,
							array(
								'disposition'        => 'Second-review disposition',
								'recommendation'     => 'Reviewed recommendation',
								'service_route'      => 'Reviewed service route',
								'scope_boundary'     => 'Reviewed scope boundary',
								'review_notes'       => 'Second-review notes',
								'required_changes'   => 'Required changes',
								'conflict_disclosure'=> 'Reviewer conflict disclosure',
								'human_attestation'  => 'Human attestation',
								'created_at'         => 'Recorded at',
							)
						),
					);
				}
			}

			$portal = SC_EI_Portal_Repository::export_for_inquiry( $inquiry_id );
			if ( ! empty( $portal['access'] ) ) {
				$data[] = array(
					'group_id'    => 'sc-engagement-intake-sender-portal-access',
					'group_label' => __( 'Engagement Intake Secure Sender Portal Access', 'sustainable-catalyst-engagement-intake' ),
					'item_id'     => 'sc-ei-portal-access-' . $portal['access']['id'],
					'data'        => self::export_fields(
						$portal['access'],
						array(
							'status'             => 'Portal access state',
							'invite_expires_at'  => 'Invitation expires at',
							'invite_used_at'     => 'Invitation used at',
							'permissions_json'   => 'Portal permissions',
							'terms_version'      => 'Terms version',
							'terms_accepted_at'  => 'Terms accepted at',
							'invitation_note'    => 'Invitation note',
							'activated_at'       => 'Activated at',
							'suspended_at'       => 'Suspended at',
							'revoked_at'         => 'Revoked at',
							'revocation_reason'  => 'Revocation reason',
							'last_access_at'     => 'Last access at',
							'failed_attempts'    => 'Failed activation attempts',
							'locked_until'       => 'Locked until',
							'created_at'         => 'Created at',
							'updated_at'         => 'Updated at',
						)
					),
				);
			}
			foreach ( (array) ( $portal['sessions'] ?? array() ) as $portal_session ) {
				$data[] = array(
					'group_id'    => 'sc-engagement-intake-sender-portal-sessions',
					'group_label' => __( 'Engagement Intake Secure Sender Portal Sessions', 'sustainable-catalyst-engagement-intake' ),
					'item_id'     => 'sc-ei-portal-session-' . $portal_session['id'],
					'data'        => self::export_fields(
						$portal_session,
						array(
							'status'          => 'Session state',
							'expires_at'      => 'Absolute expiration',
							'idle_expires_at' => 'Idle expiration',
							'last_seen_at'    => 'Last seen at',
							'activity_count'  => 'Activity count',
							'revoked_at'      => 'Revoked or expired at',
							'revoked_reason'  => 'Revocation reason',
							'created_at'      => 'Created at',
							'updated_at'      => 'Updated at',
						)
					),
				);
			}
			foreach ( (array) ( $portal['events'] ?? array() ) as $portal_event ) {
				$data[] = array(
					'group_id'    => 'sc-engagement-intake-sender-portal-events',
					'group_label' => __( 'Engagement Intake Secure Sender Portal Events', 'sustainable-catalyst-engagement-intake' ),
					'item_id'     => 'sc-ei-portal-event-' . $portal_event['id'],
					'data'        => self::export_fields(
						$portal_event,
						array(
							'event_type'   => 'Portal event type',
							'target_type'  => 'Target type',
							'target_id'    => 'Target ID',
							'outcome'      => 'Outcome',
							'context_json' => 'Event context',
							'created_at'   => 'Occurred at',
						)
					),
				);
			}

			foreach ( (array) ( $portal['recovery_requests'] ?? array() ) as $portal_recovery ) {
				$data[] = array(
					'group_id'    => 'sc-engagement-intake-sender-portal-recovery',
					'group_label' => __( 'Engagement Intake Sender Portal Recovery Requests', 'sustainable-catalyst-engagement-intake' ),
					'item_id'     => 'sc-ei-portal-recovery-' . $portal_recovery['id'],
					'data'        => self::export_fields(
						$portal_recovery,
						array(
							'status'            => 'Recovery state',
							'match_status'      => 'Recovery match state',
							'recovery_reason'   => 'Recovery reason',
							'request_count'     => 'Recovery submission count',
							'requested_at'      => 'Requested at',
							'last_requested_at' => 'Last requested at',
							'expires_at'        => 'Review expires at',
							'reviewed_at'       => 'Reviewed at',
							'decision_note'     => 'Human review note',
							'completed_at'      => 'Completed at',
							'created_at'        => 'Created at',
							'updated_at'        => 'Updated at',
						)
					),
				);
			}

			$workflow = SC_EI_Workflow_Repository::export_for_inquiry( $inquiry_id );
			foreach ( (array) ( $workflow['meeting_offers'] ?? array() ) as $meeting ) {
				$data[] = array(
					'group_id'    => 'sc-engagement-intake-teams-meetings',
					'group_label' => __( 'Engagement Intake Microsoft Teams Scheduling', 'sustainable-catalyst-engagement-intake' ),
					'item_id'     => 'sc-ei-meeting-' . $meeting['id'],
					'data'        => self::export_fields(
						$meeting,
						array(
							'offer_number'       => 'Meeting offer number',
							'status'             => 'Meeting state',
							'title'              => 'Meeting title',
							'purpose'            => 'Meeting purpose',
							'duration_minutes'   => 'Duration in minutes',
							'timezone'           => 'Timezone',
							'slots_json'         => 'Proposed times',
							'selected_start_utc' => 'Selected start UTC',
							'selected_end_utc'   => 'Selected end UTC',
							'teams_url'          => 'Microsoft Teams URL',
							'graph_sync_status'  => 'Microsoft Graph synchronization state',
							'graph_transaction_id'=> 'Graph idempotent transaction ID',
							'graph_i_cal_uid'    => 'Remote calendar UID',
							'graph_join_url'     => 'Remote Teams join URL',
							'graph_remote_start_utc' => 'Remote start UTC',
							'graph_remote_end_utc' => 'Remote end UTC',
							'graph_last_error_code' => 'Last Graph error code',
							'graph_last_request_id' => 'Last Graph request ID',
							'graph_last_success_at' => 'Last Graph success at',
							'sender_note'        => 'Sender response note',
							'alternative_request'=> 'Alternative time request',
							'expires_at'         => 'Offer expires at',
							'published_at'       => 'Published at',
							'responded_at'       => 'Responded at',
							'finalized_at'       => 'Finalized at',
							'completed_at'       => 'Completed at',
							'canceled_at'        => 'Canceled at',
							'cancellation_reason'=> 'Cancellation reason',
							'created_at'         => 'Created at',
							'updated_at'         => 'Updated at',
						)
					),
				);
			}
			foreach ( (array) ( $workflow['proposals'] ?? array() ) as $proposal ) {
				$data[] = array(
					'group_id'    => 'sc-engagement-intake-proposals',
					'group_label' => __( 'Engagement Intake Proposals', 'sustainable-catalyst-engagement-intake' ),
					'item_id'     => 'sc-ei-proposal-' . $proposal['id'],
					'data'        => self::export_fields(
						$proposal,
						array(
							'proposal_number'            => 'Proposal number',
							'status'                     => 'Proposal state',
							'version_number'             => 'Current version',
							'title'                      => 'Proposal title',
							'executive_summary'          => 'Executive summary',
							'scope_json'                 => 'Scope',
							'deliverables_json'          => 'Deliverables',
							'exclusions_json'            => 'Exclusions',
							'assumptions_json'           => 'Assumptions',
							'timeline_text'              => 'Timeline',
							'fee_summary'                => 'Fee summary',
							'payment_terms'              => 'Payment terms',
							'legal_terms'                => 'Terms and boundaries',
							'currency'                   => 'Currency',
							'total_minor'                => 'Total minor currency units',
							'expires_at'                 => 'Proposal expires at',
							'published_at'               => 'Published at',
							'sender_response'            => 'Sender response',
							'sender_response_note'       => 'Sender response note',
							'sender_authority_attested'  => 'Authority attested',
							'boundary_acknowledged'      => 'Non-contract boundary acknowledged',
							'responded_at'               => 'Responded at',
							'accepted_at'                => 'Accepted at',
							'declined_at'                => 'Declined at',
							'withdrawn_at'               => 'Withdrawn at',
							'contract_reference'         => 'External contract reference',
							'contracted_at'              => 'External contract recorded at',
							'content_hash'               => 'Current version content hash',
							'created_at'                 => 'Created at',
							'updated_at'                 => 'Updated at',
						)
					),
				);
			}
			foreach ( (array) ( $workflow['microsoft_graph']['operations'] ?? array() ) as $graph_operation ) {
				$data[] = array(
					'group_id'    => 'sc-engagement-intake-microsoft-graph',
					'group_label' => __( 'Engagement Intake Microsoft Graph Calendar Operations', 'sustainable-catalyst-engagement-intake' ),
					'item_id'     => 'sc-ei-graph-operation-' . $graph_operation['id'],
					'data'        => self::export_fields(
						$graph_operation,
						array(
							'operation_type'     => 'Operation type',
							'status'             => 'Operation state',
							'attempt_count'      => 'Attempt count',
							'max_attempts'       => 'Maximum attempts',
							'scheduled_at'       => 'Scheduled at',
							'next_retry_at'      => 'Next retry at',
							'started_at'         => 'Started at',
							'completed_at'       => 'Completed at',
							'response_status'    => 'HTTP response status',
							'graph_error_code'   => 'Graph error code',
							'graph_error_message'=> 'Graph error message',
							'retry_after_seconds'=> 'Retry delay in seconds',
							'request_id'         => 'Microsoft Graph request ID',
							'client_request_id'  => 'Client request ID',
							'created_at'         => 'Created at',
							'updated_at'         => 'Updated at',
							'payload_encrypted'  => 'Request payload stored encrypted',
						)
					),
				);
			}

			foreach ( (array) ( $workflow['events'] ?? array() ) as $workflow_event ) {
				$data[] = array(
					'group_id'    => 'sc-engagement-intake-workflow-events',
					'group_label' => __( 'Engagement Intake Scheduling and Proposal Events', 'sustainable-catalyst-engagement-intake' ),
					'item_id'     => 'sc-ei-workflow-event-' . $workflow_event['id'],
					'data'        => self::export_fields(
						$workflow_event,
						array(
							'actor_type'   => 'Actor type',
							'object_type'  => 'Object type',
							'object_id'    => 'Object ID',
							'event_type'   => 'Event type',
							'from_status'  => 'Previous state',
							'to_status'    => 'New state',
							'context_json' => 'Event context',
							'created_at'   => 'Occurred at',
						)
					),
				);
			}


			$engagement_export = SC_EI_Engagement_Repository::export_for_inquiry( $inquiry_id );
			foreach ( (array) ( $engagement_export['engagements'] ?? array() ) as $engagement_package ) {
				$engagement = (array) ( $engagement_package['engagement'] ?? array() );
				if ( $engagement ) {
					$data[] = array(
						'group_id'    => 'sc-engagement-intake-engagements',
						'group_label' => __( 'Engagement Intake Engagement Handoffs', 'sustainable-catalyst-engagement-intake' ),
						'item_id'     => 'sc-ei-engagement-' . $engagement['id'],
						'data'        => self::export_fields(
							$engagement,
							array(
								'engagement_number'              => 'Engagement number',
								'status'                         => 'Engagement state',
								'title'                          => 'Engagement title',
								'sender_organization'            => 'Sender organization',
								'contract_reference'             => 'External contract reference',
								'currency'                       => 'Currency',
								'total_minor'                    => 'Total minor currency units',
								'proposed_start_date'            => 'Proposed start date',
								'target_end_date'                => 'Target end date',
								'kickoff_status'                 => 'Kickoff state',
								'kickoff_at'                     => 'Kickoff at',
								'onboarding_summary'             => 'Onboarding summary',
								'sender_summary'                 => 'Sender-visible engagement summary',
								'external_project_reference'     => 'External project reference',
								'workbench_handoff_status'       => 'Workbench handoff state',
								'decision_studio_handoff_status' => 'Decision Studio handoff state',
								'handoff_prepared_at'            => 'Handoff prepared at',
								'ready_at'                       => 'Ready at',
								'activated_at'                   => 'Activated at',
								'paused_at'                      => 'Paused at',
								'pause_reason'                   => 'Pause reason',
								'completed_at'                   => 'Completed at',
								'completion_note'                => 'Completion note',
								'canceled_at'                    => 'Canceled at',
								'cancellation_reason'            => 'Cancellation reason',
								'created_at'                     => 'Created at',
								'updated_at'                     => 'Updated at',
							)
						),
					);
				}

				$snapshot = (array) ( $engagement_package['commercial_snapshot']['metadata'] ?? array() );
				if ( $snapshot ) {
					$data[] = array(
						'group_id'    => 'sc-engagement-intake-engagement-snapshots',
						'group_label' => __( 'Engagement Intake Commercial Handoff Snapshots', 'sustainable-catalyst-engagement-intake' ),
						'item_id'     => 'sc-ei-engagement-snapshot-' . $snapshot['id'],
						'data'        => self::export_fields(
							$snapshot,
							array(
								'snapshot_version'        => 'Snapshot version',
								'snapshot_type'           => 'Snapshot type',
								'proposal_number'         => 'Proposal number',
								'proposal_version_number' => 'Proposal version',
								'proposal_content_hash'   => 'Proposal content hash',
								'contract_reference'      => 'External contract reference',
								'payload_json'            => 'Commercial handoff snapshot',
								'content_hash'            => 'Snapshot content hash',
								'created_at'              => 'Created at',
							)
						),
					);
				}

				foreach ( (array) ( $engagement_package['onboarding_requirements'] ?? array() ) as $requirement ) {
					$data[] = array(
						'group_id'    => 'sc-engagement-intake-engagement-requirements',
						'group_label' => __( 'Engagement Intake Onboarding Requirements', 'sustainable-catalyst-engagement-intake' ),
						'item_id'     => 'sc-ei-engagement-requirement-' . $requirement['id'],
						'data'        => self::export_fields(
							$requirement,
							array(
								'requirement_key'    => 'Requirement key',
								'title'              => 'Requirement title',
								'description'        => 'Requirement description',
								'category'           => 'Requirement category',
								'status'             => 'Requirement state',
								'is_required'        => 'Required',
								'sender_visible'     => 'Sender visible',
								'due_date'           => 'Due date',
								'completion_note'    => 'Completion or waiver note',
								'evidence_reference' => 'Evidence reference',
								'completed_at'       => 'Completed at',
								'waived_at'          => 'Waived at',
								'created_at'         => 'Created at',
								'updated_at'         => 'Updated at',
							)
						),
					);
				}
			}


			$workflow_core = SC_EI_Workflow_Core_Repository::export_for_inquiry( $inquiry_id );
			$core_case = (array) ( $workflow_core['case'] ?? array() );
			if ( $core_case ) {
				$data[] = array(
					'group_id'    => 'sc-engagement-intake-workflow-core-cases',
					'group_label' => __( 'Engagement Intake Workflow Core Cases', 'sustainable-catalyst-engagement-intake' ),
					'item_id'     => 'sc-ei-workflow-core-case-' . $core_case['id'],
					'data'        => self::export_fields(
						$core_case,
						array(
							'public_id'             => 'Workflow Core public ID',
							'reference'             => 'Inquiry reference',
							'current_stage'         => 'Canonical stage',
							'current_state'         => 'Canonical state',
							'terminal_state'        => 'Terminal state',
							'priority'              => 'Priority',
							'source_updated_at'     => 'Authoritative source updated at',
							'projection_version'    => 'Projection version',
							'projection_hash'       => 'Projection SHA-256',
							'blocker_count'         => 'Consistency blocker count',
							'open_command_count'    => 'Open command count',
							'pending_handoff_count' => 'Pending handoff count',
							'last_event_at'         => 'Last authoritative event',
							'last_transition_at'    => 'Last canonical stage transition',
							'last_synced_at'        => 'Last synchronized at',
							'stale_after'           => 'Projection stale after',
							'consistency_status'    => 'Consistency status',
							'consistency_notes'     => 'Consistency review data',
							'created_at'            => 'Created at',
							'updated_at'            => 'Updated at',
						)
					),
				);
			}

			foreach ( (array) ( $workflow_core['commands'] ?? array() ) as $command ) {
				$data[] = array(
					'group_id'    => 'sc-engagement-intake-workflow-core-commands',
					'group_label' => __( 'Engagement Intake Workflow Core Commands', 'sustainable-catalyst-engagement-intake' ),
					'item_id'     => 'sc-ei-workflow-core-command-' . $command['id'],
					'data'        => self::export_fields(
						$command,
						array(
							'public_id'      => 'Command public ID',
							'command_key'    => 'Idempotency key',
							'command_type'   => 'Command type',
							'target_type'    => 'Target type',
							'target_id'      => 'Target ID',
							'expected_stage' => 'Expected canonical stage',
							'payload_hash'   => 'Command payload SHA-256',
							'status'         => 'Command status',
							'error_code'     => 'Error code',
							'created_at'     => 'Created at',
							'completed_at'   => 'Completed at',
							'updated_at'     => 'Updated at',
						)
					),
				);
			}

			foreach ( SC_EI_Workflow_Core_Repository::handoffs( absint( $core_case['id'] ?? 0 ), 1000 ) as $handoff ) {
				$data[] = array(
					'group_id'    => 'sc-engagement-intake-workflow-core-handoffs',
					'group_label' => __( 'Engagement Intake Workflow Core Handoffs', 'sustainable-catalyst-engagement-intake' ),
					'item_id'     => 'sc-ei-workflow-core-handoff-' . $handoff['id'],
					'data'        => self::export_fields(
						$handoff,
						array(
							'public_id'           => 'Handoff public ID',
							'handoff_key'         => 'Handoff idempotency key',
							'target'              => 'Integration target',
							'schema_id'           => 'Contract schema',
							'contract_version'    => 'Contract version',
							'data_classification' => 'Data classification',
							'status'              => 'Handoff status',
							'payload_json'        => 'Signed handoff payload',
							'content_hash'        => 'Handoff SHA-256',
							'signature'           => 'HMAC signature',
							'prepared_at'         => 'Prepared at',
							'dispatched_at'       => 'Dispatched at',
							'acknowledged_at'     => 'Acknowledged at',
							'failure_code'        => 'Failure code',
							'failure_message'     => 'Failure message',
							'expires_at'          => 'Expires at',
							'created_at'          => 'Created at',
							'updated_at'          => 'Updated at',
						)
					),
				);
			}

			foreach ( (array) ( $workflow_core['outbox'] ?? array() ) as $event ) {
				$data[] = array(
					'group_id'    => 'sc-engagement-intake-workflow-core-outbox',
					'group_label' => __( 'Engagement Intake Workflow Core Delivery Events', 'sustainable-catalyst-engagement-intake' ),
					'item_id'     => 'sc-ei-workflow-core-outbox-' . $event['id'],
					'data'        => self::export_fields(
						$event,
						array(
							'public_id'       => 'Outbox public ID',
							'event_key'       => 'Outbox idempotency key',
							'event_type'      => 'Event type',
							'aggregate_type'  => 'Aggregate type',
							'aggregate_id'    => 'Aggregate ID',
							'target'          => 'Integration target',
							'payload_hash'    => 'Payload SHA-256',
							'status'          => 'Delivery status',
							'available_at'    => 'Available at',
							'attempts'        => 'Delivery attempts',
							'max_attempts'    => 'Maximum delivery attempts',
							'dispatched_at'   => 'Dispatched at',
							'acknowledged_at' => 'Acknowledged at',
							'error_code'      => 'Error code',
							'created_at'      => 'Created at',
							'updated_at'      => 'Updated at',
						)
					),
				);
			}

			foreach ( SC_EI_Attachment_Repository::for_inquiry( $inquiry_id, true ) as $attachment ) {
				$data[] = array(
					'group_id'    => 'sc-engagement-intake-documents',
					'group_label' => __( 'Engagement Intake Documents', 'sustainable-catalyst-engagement-intake' ),
					'item_id'     => 'sc-ei-attachment-' . $attachment['id'],
					'data'        => self::export_fields(
						$attachment,
						array(
							'original_name'            => 'Original document name',
							'mime_type'                => 'Document MIME type',
							'extension'                => 'Document extension',
							'size_bytes'               => 'Document size in bytes',
							'document_category'        => 'Document category',
							'document_notes'           => 'Document notes',
							'confidentiality'          => 'Confidentiality classification',
							'quarantine_status'        => 'Quarantine status',
							'validation_status'        => 'Validation status',
							'scan_status'              => 'Malware scan status',
							'scanner_provider'         => 'Scanner provider',
							'scan_message'             => 'Scanner message',
							'scan_attempts'            => 'Scanner attempts',
							'last_scanned_at'          => 'Last scanned at',
							'storage_status'           => 'Storage status',
							'integrity_status'         => 'Integrity status',
							'last_verified_at'         => 'Last verified at',
							'last_verification_source' => 'Verification source',
							'retention_until'          => 'Retention until',
							'uploaded_at'              => 'Uploaded at',
							'deleted_at'               => 'Deleted at',
						)
					),
				);
			}

			foreach ( SC_EI_Privacy_Repository::consent_events( array( 'inquiry_id' => $inquiry_id, 'limit' => 1000 ) ) as $consent ) {
				$data[] = array(
					'group_id'    => 'sc-engagement-intake-consent',
					'group_label' => __( 'Engagement Intake Consent and Authorization Events', 'sustainable-catalyst-engagement-intake' ),
					'item_id'     => 'sc-ei-consent-' . $consent['id'],
					'data'        => self::export_fields(
						$consent,
						array(
							'consent_type'       => 'Consent or authorization type',
							'action'             => 'Action',
							'consent_version'    => 'Notice or consent version',
							'lawful_basis'       => 'Recorded processing basis',
							'source'             => 'Source',
							'evidence_text'      => 'Evidence note',
							'subject_email_hash' => 'Subject email SHA-256',
							'occurred_at'        => 'Occurred at',
							'created_at'         => 'Recorded at',
						)
					),
				);
			}

			foreach ( SC_EI_Privacy_Repository::holds( array( 'search' => (string) $inquiry['reference'], 'limit' => 500 ) ) as $hold ) {
				if ( absint( $hold['inquiry_id'] ) !== $inquiry_id ) {
					continue;
				}
				$data[] = array(
					'group_id'    => 'sc-engagement-intake-holds',
					'group_label' => __( 'Engagement Intake Legal Holds', 'sustainable-catalyst-engagement-intake' ),
					'item_id'     => 'sc-ei-hold-' . $hold['id'],
					'data'        => self::export_fields(
						$hold,
						array(
							'scope'          => 'Hold scope',
							'status'         => 'Hold status',
							'reason'         => 'Hold reason',
							'authority'      => 'Hold authority',
							'placed_at'      => 'Placed at',
							'review_at'      => 'Review at',
							'released_at'    => 'Released at',
							'release_reason' => 'Release reason',
						)
					),
				);
			}

			foreach ( SC_EI_Privacy_Repository::retention_actions( array( 'search' => (string) $inquiry['reference'], 'limit' => 1000 ) ) as $action ) {
				if ( absint( $action['inquiry_id'] ) !== $inquiry_id ) {
					continue;
				}
				$data[] = array(
					'group_id'    => 'sc-engagement-intake-retention-actions',
					'group_label' => __( 'Engagement Intake Retention Actions', 'sustainable-catalyst-engagement-intake' ),
					'item_id'     => 'sc-ei-retention-action-' . $action['id'],
					'data'        => self::export_fields(
						$action,
						array(
							'target_type'    => 'Target type',
							'target_id'      => 'Target ID',
							'policy_key'     => 'Policy key',
							'policy_version' => 'Policy version',
							'action_type'    => 'Action type',
							'status'         => 'Action status',
							'reason'         => 'Reason',
							'due_at'         => 'Due at',
							'proposed_at'    => 'Proposed at',
							'approved_at'    => 'Approved at',
							'executed_at'    => 'Executed at',
							'verified_at'    => 'Verified at',
							'failure_code'   => 'Failure code',
							'failure_message'=> 'Failure message',
						)
					),
				);
			}
		}

		$request_table = SC_EI_Database::table( 'privacy_requests' );
		$requests = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$request_table} WHERE requester_email = %s ORDER BY received_at ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$email
			),
			ARRAY_A
		);
		foreach ( $requests as $request ) {
			$data[] = array(
				'group_id'    => 'sc-engagement-intake-privacy-requests',
				'group_label' => __( 'Engagement Intake Privacy Requests', 'sustainable-catalyst-engagement-intake' ),
				'item_id'     => 'sc-ei-privacy-request-' . $request['id'],
				'data'        => self::export_fields(
					$request,
					array(
						'requester_name'     => 'Requester name',
						'requester_email'    => 'Requester email',
						'request_type'       => 'Request type',
						'status'             => 'Request status',
						'identity_status'    => 'Identity verification status',
						'source'             => 'Source',
						'received_at'        => 'Received at',
						'due_at'             => 'Due at',
						'request_summary'    => 'Request summary',
						'resolution_summary' => 'Resolution summary',
						'completed_at'       => 'Completed at',
						'created_at'         => 'Created at',
						'updated_at'         => 'Updated at',
					)
				),
			);
		}

		return array( 'data' => $data, 'done' => true );
	}

	/**
	 * WordPress eraser bridge.
	 *
	 * v1.0.0 retains the queue-only behavior introduced in v0.6.0 and does not erase synchronously. It creates a tracked case and queues
	 * legal-hold-aware lifecycle actions for human approval and verified execution.
	 */
	public static function erase_by_email( string $email_address, int $page = 1 ): array {
		global $wpdb;

		$email = sanitize_email( $email_address );
		if ( ! is_email( $email ) ) {
			return array(
				'items_removed'  => false,
				'items_retained' => true,
				'messages'       => array( __( 'A valid email address is required.', 'sustainable-catalyst-engagement-intake' ) ),
				'done'           => true,
			);
		}

		$table = SC_EI_Database::table( 'inquiries' );
		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE contact_email = %s ORDER BY created_at ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$email
			),
			ARRAY_A
		);

		if ( ! $rows ) {
			return array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);
		}

		$queued = 0;
		$blocked = 0;
		$existing = 0;
		$request_ids = array();

		foreach ( $rows as $inquiry ) {
			$inquiry_id = absint( $inquiry['id'] );
			$request_id = self::ensure_erasure_request( $inquiry, $email );
			if ( $request_id ) {
				$request_ids[] = $request_id;
			}

			SC_EI_Privacy_Repository::set_inquiry_privacy_state(
				$inquiry_id,
				'erasure_requested',
				'WordPress privacy eraser request queued for reviewed execution.',
				0
			);

			foreach ( SC_EI_Attachment_Repository::for_inquiry( $inquiry_id, false ) as $attachment ) {
				$result = SC_EI_Privacy_Repository::queue_action(
					array(
						'inquiry_id'     => $inquiry_id,
						'target_type'    => 'attachment',
						'target_id'      => absint( $attachment['id'] ),
						'policy_key'     => 'privacy_erasure_request',
						'policy_version' => 1,
						'action_type'    => 'delete_attachment',
						'due_at'         => current_time( 'mysql', true ),
						'dedupe_key'     => 'privacy-erasure:attachment:' . absint( $attachment['id'] ),
						'reason'         => 'Queued from the WordPress personal-data eraser. Human approval and physical absence verification are required.',
						'snapshot'       => array(
							'reference'      => $inquiry['reference'],
							'attachment_id'  => absint( $attachment['id'] ),
							'original_name'  => $attachment['original_name'],
							'sha256'         => $attachment['sha256'],
							'size_bytes'     => absint( $attachment['size_bytes'] ),
							'privacy_request'=> $request_id,
						),
					),
					0
				);
				self::count_queue_result( $result, $queued, $blocked, $existing );
			}

			$result = SC_EI_Privacy_Repository::queue_action(
				array(
					'inquiry_id'     => $inquiry_id,
					'target_type'    => 'inquiry',
					'target_id'      => $inquiry_id,
					'policy_key'     => 'privacy_erasure_request',
					'policy_version' => 1,
					'action_type'    => 'redact_inquiry',
					'due_at'         => current_time( 'mysql', true ),
					'dedupe_key'     => 'privacy-erasure:inquiry:' . $inquiry_id,
					'reason'         => 'Queued from the WordPress personal-data eraser. Private documents must be deleted and verified before inquiry redaction can execute.',
					'snapshot'       => array(
						'reference'       => $inquiry['reference'],
						'privacy_request' => $request_id,
						'email_hash'      => hash( 'sha256', strtolower( $email ) ),
					),
				),
				0
			);
			self::count_queue_result( $result, $queued, $blocked, $existing );

			SC_EI_Audit_Log::record(
				'wordpress_privacy_request_queued',
				'WordPress privacy eraser request was converted into reviewed lifecycle actions. No immediate erasure occurred.',
				array(
					'privacy_request_id' => $request_id,
					'email_hash'         => hash( 'sha256', strtolower( $email ) ),
				),
				$inquiry_id,
				null,
				0
			);
		}

		return array(
			'items_removed'  => false,
			'items_retained' => true,
			'messages'       => array(
				sprintf(
					__( 'The erasure request was queued in the Privacy and Retention Center: %1$d new action(s), %2$d existing action(s), and %3$d hold-blocked action(s). No data was silently deleted. An authorized reviewer must verify identity, resolve holds, approve each action, and execute it.', 'sustainable-catalyst-engagement-intake' ),
					$queued,
					$existing,
					$blocked
				),
			),
			'done'           => true,
		);
	}

	private static function ensure_erasure_request( array $inquiry, string $email ): int {
		global $wpdb;

		$table = SC_EI_Database::table( 'privacy_requests' );
		$existing = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table}
				WHERE inquiry_id = %d
					AND requester_email = %s
					AND request_type = 'erasure'
					AND status NOT IN ('completed','denied','withdrawn')
				ORDER BY id DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				absint( $inquiry['id'] ),
				$email
			)
		);
		if ( $existing ) {
			return $existing;
		}

		$request = SC_EI_Privacy_Repository::create_request(
			array(
				'inquiry_id'      => absint( $inquiry['id'] ),
				'requester_name'   => (string) $inquiry['contact_name'],
				'requester_email'  => $email,
				'request_type'     => 'erasure',
				'status'           => 'received',
				'identity_status'  => 'unverified',
				'source'           => 'wordpress_privacy',
				'request_summary'  => 'Personal-data erasure requested through the WordPress privacy tools. Identity verification, legal-hold review, approval, and verified execution are pending.',
			),
			0
		);
		return is_wp_error( $request ) ? 0 : absint( $request['id'] );
	}

	private static function count_queue_result( $result, int &$queued, int &$blocked, int &$existing ): void {
		if ( is_wp_error( $result ) ) {
			return;
		}
		if ( 'blocked_hold' === $result['status'] ) {
			$blocked++;
			return;
		}
		if ( strtotime( $result['created_at'] . ' UTC' ) < time() - 5 ) {
			$existing++;
		} else {
			$queued++;
		}
	}

	private static function export_fields( array $row, array $fields ): array {
		$data = array();
		foreach ( $fields as $key => $label ) {
			if ( array_key_exists( $key, $row ) && '' !== (string) $row[ $key ] ) {
				$data[] = array( 'name' => $label, 'value' => (string) $row[ $key ] );
			}
		}
		return $data;
	}

	private static function inquiry_export_fields(): array {
		return array(
			'reference'                 => 'Inquiry reference',
			'inquiry_type'              => 'Inquiry type',
			'status'                    => 'Status',
			'form_variant'              => 'Intake experience',
			'source_page'               => 'Source page',
			'entry_cta'                 => 'Entry CTA',
			'conversion_route'          => 'Conversion route',
			'contact_name'              => 'Name',
			'contact_email'             => 'Email',
			'organization'              => 'Organization',
			'role_title'                => 'Role',
			'subject'                   => 'Subject',
			'message'                   => 'Message',
			'project_summary'           => 'Project summary',
			'desired_outcome'           => 'Desired outcome',
			'service_interest'          => 'Service interest',
			'budget_range'              => 'Budget range',
			'desired_start_date'        => 'Desired start date',
			'deadline_date'             => 'Deadline',
			'preferred_contact_method'  => 'Preferred contact method',
			'teams_email'               => 'Microsoft Teams email',
			'phone_number'              => 'Phone number',
			'timezone'                  => 'Time zone',
			'city'                      => 'City',
			'country'                   => 'Country',
			'meeting_request'           => 'Microsoft Teams meeting request',
			'preferred_weekdays'        => 'Preferred weekdays',
			'preferred_time_windows'    => 'Preferred time windows',
			'preferred_duration'        => 'Preferred duration',
			'participant_count'         => 'Participant count',
			'participant_emails'        => 'Participant emails',
			'accessibility_needs'       => 'Accessibility needs',
			'calendar_invite_consent'   => 'Calendar invitation consent',
			'scheduling_notes'          => 'Scheduling notes',
			'scheduling_status'         => 'Scheduling status',
			'teams_meeting_url'         => 'Microsoft Teams meeting URL',
			'scheduled_start_utc'       => 'Scheduled start UTC',
			'scheduled_end_utc'         => 'Scheduled end UTC',
			'scheduled_timezone'        => 'Scheduled timezone',
			'assigned_user_id'          => 'Assigned reviewer user ID',
			'review_stage'              => 'Administrative review stage',
			'review_priority'           => 'Review priority',
			'review_due_at'             => 'Review due at',
			'fit_decision'              => 'Fit decision',
			'fit_confidence'            => 'Fit confidence',
			'risk_level'                => 'Risk level',
			'evidence_readiness'        => 'Evidence readiness',
			'scope_clarity'             => 'Scope clarity',
			'recommended_next_step'     => 'Recommended next step',
			'review_summary'            => 'Review summary',
			'decision_rationale'        => 'Decision rationale',
			'information_gaps'          => 'Information gaps',
			'conflict_notes'            => 'Conflict and independence notes',
			'escalation_status'         => 'Escalation status',
			'escalation_reason'         => 'Escalation reason',
			'review_started_at'         => 'Review started at',
			'last_reviewed_at'          => 'Last reviewed at',
			'decision_at'               => 'Decision recorded at',
			'review_completed_at'       => 'Review completed at',
			'review_version'            => 'Review version',
			'communication_status'      => 'Communication state',
			'next_follow_up_at'         => 'Next follow-up at',
			'last_communication_at'     => 'Last communication at',
			'last_outbound_at'          => 'Last outbound at',
			'last_inbound_at'           => 'Last inbound at',
			'last_notification_at'      => 'Last notification at',
			'communication_count'       => 'Communication count',
			'unread_inbound_count'      => 'Unread inbound count',
			'do_not_email'              => 'Email suppression enabled',
			'do_not_email_reason'       => 'Email suppression reason',
			'communication_version'     => 'Communication state version',
			'privacy_status'            => 'Privacy lifecycle state',
			'retention_policy_key'      => 'Retention policy key',
			'retention_until'           => 'Retention due date',
			'legal_hold_count'          => 'Active legal hold count',
			'privacy_restriction_reason'=> 'Privacy restriction reason',
			'last_privacy_review_at'    => 'Last privacy review at',
			'last_privacy_review_by'    => 'Last privacy reviewer user ID',
			'personal_data_erased_at'   => 'Personal data erased at',
			'privacy_version'           => 'Privacy state version',
			'fit_assessment_status'     => 'Fit assessment state',
			'current_fit_assessment_id' => 'Current fit assessment ID',
			'fit_assessment_updated_at' => 'Fit assessment updated at',
			'fit_assessment_finalized_at'=> 'Fit assessment finalized at',
			'fit_assessment_version'    => 'Fit assessment version',
			'portal_status'             => 'Sender portal state',
			'portal_access_id'          => 'Sender portal access ID',
			'portal_last_activity_at'   => 'Sender portal last activity',
			'portal_message_count'      => 'Sender portal message count',
			'portal_document_count'     => 'Sender portal document count',
			'portal_last_sender_message_at' => 'Last sender portal message',
			'sender_withdrawal_status'  => 'Sender withdrawal request state',
			'sender_withdrawal_requested_at' => 'Sender withdrawal requested at',
			'sender_withdrawal_reason'  => 'Sender withdrawal reason',
			'portal_version'            => 'Sender portal state version',
			'created_at'                => 'Submitted at',
			'updated_at'                => 'Last updated',
		);
	}
}
