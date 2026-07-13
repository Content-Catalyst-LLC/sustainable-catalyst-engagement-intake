<?php
/**
 * Privacy, consent, legal-hold, and retention taxonomies.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Privacy_Schema {

	public static function privacy_statuses(): array {
		return array(
			'active'            => __( 'Active', 'sustainable-catalyst-engagement-intake' ),
			'restricted'        => __( 'Processing Restricted', 'sustainable-catalyst-engagement-intake' ),
			'erasure_requested' => __( 'Erasure Requested', 'sustainable-catalyst-engagement-intake' ),
			'erased'            => __( 'Personal Data Erased', 'sustainable-catalyst-engagement-intake' ),
			'archived'          => __( 'Archived', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function request_types(): array {
		return array(
			'access'      => __( 'Access / Export', 'sustainable-catalyst-engagement-intake' ),
			'erasure'     => __( 'Erasure', 'sustainable-catalyst-engagement-intake' ),
			'restriction' => __( 'Restriction', 'sustainable-catalyst-engagement-intake' ),
			'correction'  => __( 'Correction', 'sustainable-catalyst-engagement-intake' ),
			'withdrawal'  => __( 'Consent Withdrawal', 'sustainable-catalyst-engagement-intake' ),
			'objection'   => __( 'Objection', 'sustainable-catalyst-engagement-intake' ),
			'portability' => __( 'Portability', 'sustainable-catalyst-engagement-intake' ),
			'other'       => __( 'Other Privacy Request', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function request_statuses(): array {
		return array(
			'received'            => __( 'Received', 'sustainable-catalyst-engagement-intake' ),
			'identity_pending'    => __( 'Identity Verification Pending', 'sustainable-catalyst-engagement-intake' ),
			'in_review'           => __( 'In Review', 'sustainable-catalyst-engagement-intake' ),
			'awaiting_information'=> __( 'Awaiting Information', 'sustainable-catalyst-engagement-intake' ),
			'approved'            => __( 'Approved', 'sustainable-catalyst-engagement-intake' ),
			'partially_completed' => __( 'Partially Completed', 'sustainable-catalyst-engagement-intake' ),
			'completed'           => __( 'Completed', 'sustainable-catalyst-engagement-intake' ),
			'denied'              => __( 'Denied', 'sustainable-catalyst-engagement-intake' ),
			'withdrawn'           => __( 'Withdrawn', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function identity_statuses(): array {
		return array(
			'unverified' => __( 'Unverified', 'sustainable-catalyst-engagement-intake' ),
			'pending'    => __( 'Pending', 'sustainable-catalyst-engagement-intake' ),
			'verified'   => __( 'Verified', 'sustainable-catalyst-engagement-intake' ),
			'failed'     => __( 'Failed', 'sustainable-catalyst-engagement-intake' ),
			'waived'     => __( 'Waived with Recorded Reason', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function request_sources(): array {
		return array(
			'admin'             => __( 'Administrative Entry', 'sustainable-catalyst-engagement-intake' ),
			'wordpress_privacy' => __( 'WordPress Privacy Tool', 'sustainable-catalyst-engagement-intake' ),
			'email'             => __( 'Email', 'sustainable-catalyst-engagement-intake' ),
			'phone'             => __( 'Phone', 'sustainable-catalyst-engagement-intake' ),
			'teams'             => __( 'Microsoft Teams', 'sustainable-catalyst-engagement-intake' ),
			'other'             => __( 'Other', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function consent_types(): array {
		return array(
			'privacy_notice'       => __( 'Privacy Notice Acknowledgment', 'sustainable-catalyst-engagement-intake' ),
			'request_processing'   => __( 'Inquiry Processing', 'sustainable-catalyst-engagement-intake' ),
			'calendar_invitation'  => __( 'Calendar Invitation', 'sustainable-catalyst-engagement-intake' ),
			'participant_contact'  => __( 'Participant Contact Authorization', 'sustainable-catalyst-engagement-intake' ),
			'communication'        => __( 'Communication Permission', 'sustainable-catalyst-engagement-intake' ),
			'document_processing'  => __( 'Private Document Processing', 'sustainable-catalyst-engagement-intake' ),
			'other'                => __( 'Other Consent or Authorization', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function consent_actions(): array {
		return array(
			'granted'   => __( 'Granted / Acknowledged', 'sustainable-catalyst-engagement-intake' ),
			'renewed'   => __( 'Renewed', 'sustainable-catalyst-engagement-intake' ),
			'withdrawn' => __( 'Withdrawn', 'sustainable-catalyst-engagement-intake' ),
			'corrected' => __( 'Corrected', 'sustainable-catalyst-engagement-intake' ),
			'expired'   => __( 'Expired', 'sustainable-catalyst-engagement-intake' ),
			'not_applicable' => __( 'Not Applicable', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function lawful_bases(): array {
		return array(
			'request_processing' => __( 'Process the Person’s Request', 'sustainable-catalyst-engagement-intake' ),
			'contract_steps'     => __( 'Steps Toward or Performance of an Agreement', 'sustainable-catalyst-engagement-intake' ),
			'legal_obligation'   => __( 'Legal or Regulatory Obligation', 'sustainable-catalyst-engagement-intake' ),
			'legitimate_interest'=> __( 'Documented Legitimate Interest', 'sustainable-catalyst-engagement-intake' ),
			'consent'            => __( 'Consent', 'sustainable-catalyst-engagement-intake' ),
			'public_interest'    => __( 'Public-Interest Purpose', 'sustainable-catalyst-engagement-intake' ),
			'other'              => __( 'Other Recorded Basis', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function hold_scopes(): array {
		return array(
			'inquiry'    => __( 'Entire Inquiry and Related Records', 'sustainable-catalyst-engagement-intake' ),
			'attachment' => __( 'Specific Private Document', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function hold_statuses(): array {
		return array(
			'active'   => __( 'Active', 'sustainable-catalyst-engagement-intake' ),
			'released' => __( 'Released', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function target_types(): array {
		return array(
			'attachment'    => __( 'Private Document', 'sustainable-catalyst-engagement-intake' ),
			'inquiry'       => __( 'Inquiry Personal Data', 'sustainable-catalyst-engagement-intake' ),
			'communication' => __( 'Communication Content', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function retention_action_types(): array {
		return array(
			'delete_attachment'     => __( 'Delete Private Document', 'sustainable-catalyst-engagement-intake' ),
			'redact_inquiry'        => __( 'Erase Inquiry Personal Data', 'sustainable-catalyst-engagement-intake' ),
			'redact_communication'  => __( 'Erase Communication Content', 'sustainable-catalyst-engagement-intake' ),
			'archive_only'          => __( 'Archive Without Erasure', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function retention_action_statuses(): array {
		return array(
			'queued'              => __( 'Queued for Review', 'sustainable-catalyst-engagement-intake' ),
			'blocked_hold'        => __( 'Blocked by Legal Hold', 'sustainable-catalyst-engagement-intake' ),
			'blocked_dependency'  => __( 'Blocked by Dependency', 'sustainable-catalyst-engagement-intake' ),
			'approved'            => __( 'Approved', 'sustainable-catalyst-engagement-intake' ),
			'executing'           => __( 'Executing', 'sustainable-catalyst-engagement-intake' ),
			'executed'            => __( 'Executed and Verified', 'sustainable-catalyst-engagement-intake' ),
			'failed'              => __( 'Failed', 'sustainable-catalyst-engagement-intake' ),
			'canceled'            => __( 'Canceled', 'sustainable-catalyst-engagement-intake' ),
			'skipped'             => __( 'Skipped / No Longer Applicable', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function policy_statuses(): array {
		return array(
			'active'   => __( 'Active', 'sustainable-catalyst-engagement-intake' ),
			'archived' => __( 'Archived', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function anchor_fields(): array {
		return array(
			'created_at'      => __( 'Record Created', 'sustainable-catalyst-engagement-intake' ),
			'updated_at'      => __( 'Record Last Updated', 'sustainable-catalyst-engagement-intake' ),
			'closed_at'       => __( 'Inquiry Closed', 'sustainable-catalyst-engagement-intake' ),
			'uploaded_at'     => __( 'Document Uploaded', 'sustainable-catalyst-engagement-intake' ),
			'accepted_at'     => __( 'Communication Accepted', 'sustainable-catalyst-engagement-intake' ),
			'occurred_at'     => __( 'Interaction Occurred', 'sustainable-catalyst-engagement-intake' ),
			'retention_until' => __( 'Explicit Retention Date', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function default_settings(): array {
		return array(
			'default_unaccepted_retention_days' => 365,
			'withdrawn_retention_days'          => 30,
			'closed_retention_days'             => 365,
			'accepted_retention_days'           => 2555,
			'communication_retention_days'      => 730,
			'attachment_retention_days'         => 180,
			'privacy_request_due_days'          => 30,
			'retention_queue_batch_limit'       => 100,
			'retention_execution_batch_limit'   => 25,
			'require_retention_approval'        => 1,
			'require_distinct_retention_approver'=> 0,
			'retention_cron_queue_only'         => 1,
			'retain_tombstones'                 => 1,
			'legal_hold_review_days'            => 90,
		);
	}

	public static function default_policies(): array {
		return array(
			'unaccepted_inquiry' => array(
				'name'           => __( 'Unaccepted Inquiry', 'sustainable-catalyst-engagement-intake' ),
				'target_type'    => 'inquiry',
				'status_scope'   => array( 'new', 'under_review', 'more_information_needed', 'fit_call_recommended', 'consultation_recommended', 'proposal_requested', 'proposal_sent' ),
				'retention_days' => 365,
				'anchor_field'   => 'created_at',
				'action_type'    => 'redact_inquiry',
				'legal_basis'    => 'request_processing',
				'description'    => __( 'Retain unresolved or unaccepted inquiry records for a configurable operational period, then queue erasure review.', 'sustainable-catalyst-engagement-intake' ),
				'is_system'      => 1,
			),
			'withdrawn_inquiry' => array(
				'name'           => __( 'Withdrawn Inquiry', 'sustainable-catalyst-engagement-intake' ),
				'target_type'    => 'inquiry',
				'status_scope'   => array( 'withdrawn' ),
				'retention_days' => 30,
				'anchor_field'   => 'updated_at',
				'action_type'    => 'redact_inquiry',
				'legal_basis'    => 'request_processing',
				'description'    => __( 'Queue withdrawn inquiries for early privacy review.', 'sustainable-catalyst-engagement-intake' ),
				'is_system'      => 1,
			),
			'closed_inquiry' => array(
				'name'           => __( 'Closed, Referred, or Not-a-Fit Inquiry', 'sustainable-catalyst-engagement-intake' ),
				'target_type'    => 'inquiry',
				'status_scope'   => array( 'not_a_fit', 'referred', 'closed' ),
				'retention_days' => 365,
				'anchor_field'   => 'closed_at',
				'action_type'    => 'redact_inquiry',
				'legal_basis'    => 'legitimate_interest',
				'description'    => __( 'Queue closed non-engagement records for privacy review after the configured period.', 'sustainable-catalyst-engagement-intake' ),
				'is_system'      => 1,
			),
			'accepted_inquiry' => array(
				'name'           => __( 'Accepted Engagement Record', 'sustainable-catalyst-engagement-intake' ),
				'target_type'    => 'inquiry',
				'status_scope'   => array( 'accepted' ),
				'retention_days' => 2555,
				'anchor_field'   => 'updated_at',
				'action_type'    => 'archive_only',
				'legal_basis'    => 'contract_steps',
				'description'    => __( 'Preserve accepted engagement records for a longer configurable period. The default action is archive review, not automatic erasure.', 'sustainable-catalyst-engagement-intake' ),
				'is_system'      => 1,
			),
			'private_attachment' => array(
				'name'           => __( 'Private Document', 'sustainable-catalyst-engagement-intake' ),
				'target_type'    => 'attachment',
				'status_scope'   => array(),
				'retention_days' => 180,
				'anchor_field'   => 'retention_until',
				'action_type'    => 'delete_attachment',
				'legal_basis'    => 'request_processing',
				'description'    => __( 'Queue private documents after their explicit retention date. Physical deletion requires approval and verification.', 'sustainable-catalyst-engagement-intake' ),
				'is_system'      => 1,
			),
			'communication_content' => array(
				'name'           => __( 'Communication Content', 'sustainable-catalyst-engagement-intake' ),
				'target_type'    => 'communication',
				'status_scope'   => array(),
				'retention_days' => 730,
				'anchor_field'   => 'created_at',
				'action_type'    => 'redact_communication',
				'legal_basis'    => 'legitimate_interest',
				'description'    => __( 'Queue communication-body redaction while retaining categorical transport events and timestamps.', 'sustainable-catalyst-engagement-intake' ),
				'is_system'      => 1,
			),
		);
	}

	public static function sanitize_choice( string $value, array $options, string $fallback ): string {
		$value = sanitize_key( $value );
		return array_key_exists( $value, $options ) ? $value : $fallback;
	}

	public static function sanitize_status_scope( $scope ): array {
		$valid = array_keys( SC_EI_Statuses::all() );
		$values = is_array( $scope ) ? $scope : preg_split( '/[\s,;]+/', (string) $scope );
		return array_values(
			array_unique(
				array_filter(
					array_map( 'sanitize_key', (array) $values ),
					static fn( string $value ): bool => in_array( $value, $valid, true )
				)
			)
		);
	}

	public static function label( array $options, string $value ): string {
		return $options[ $value ] ?? ucwords( str_replace( '_', ' ', $value ) );
	}
}
