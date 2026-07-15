<?php
/**
 * Proposal, Statement of Work, approval, and change-request governance schema.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Proposal_Governance_Schema {

	public const APPROVAL_SCHEMA = 'sc-proposal-approval/1.0';
	public const SOW_SCHEMA = 'sc-statement-of-work/1.0';
	public const CHANGE_SCHEMA = 'sc-engagement-change-request/1.0';

	public static function proposal_statuses(): array {
		return array(
			'draft'                     => __( 'Draft', 'sustainable-catalyst-engagement-intake' ),
			'internal_review'           => __( 'Internal Review', 'sustainable-catalyst-engagement-intake' ),
			'approved_to_send'          => __( 'Approved to Send', 'sustainable-catalyst-engagement-intake' ),
			'published'                 => __( 'Sent', 'sustainable-catalyst-engagement-intake' ),
			'viewed'                    => __( 'Viewed', 'sustainable-catalyst-engagement-intake' ),
			'changes_requested'         => __( 'Changes Requested', 'sustainable-catalyst-engagement-intake' ),
			'accepted_pending_contract' => __( 'Accepted — Contract Pending', 'sustainable-catalyst-engagement-intake' ),
			'declined'                  => __( 'Declined', 'sustainable-catalyst-engagement-intake' ),
			'contracted'                => __( 'External Contract Recorded', 'sustainable-catalyst-engagement-intake' ),
			'expired'                   => __( 'Expired', 'sustainable-catalyst-engagement-intake' ),
			'withdrawn'                 => __( 'Withdrawn', 'sustainable-catalyst-engagement-intake' ),
			'superseded'                => __( 'Superseded', 'sustainable-catalyst-engagement-intake' ),
			'converted_to_engagement'   => __( 'Converted to Engagement', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function approval_actions(): array {
		return array(
			'receipt_confirmed'    => __( 'Receipt Confirmed', 'sustainable-catalyst-engagement-intake' ),
			'changes_requested'    => __( 'Changes Requested', 'sustainable-catalyst-engagement-intake' ),
			'proposal_accepted'    => __( 'Proposal Accepted', 'sustainable-catalyst-engagement-intake' ),
			'proposal_declined'    => __( 'Proposal Declined', 'sustainable-catalyst-engagement-intake' ),
			'sow_approved'         => __( 'Statement of Work Approved', 'sustainable-catalyst-engagement-intake' ),
			'proposal_withdrawn'   => __( 'Proposal Withdrawn', 'sustainable-catalyst-engagement-intake' ),
			'engagement_converted' => __( 'Converted to Engagement', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function sow_statuses(): array {
		return array(
			'draft'          => __( 'Draft', 'sustainable-catalyst-engagement-intake' ),
			'internal_review'=> __( 'Internal Review', 'sustainable-catalyst-engagement-intake' ),
			'approved'       => __( 'Approved for Sender Review', 'sustainable-catalyst-engagement-intake' ),
			'sender_approved'=> __( 'Sender Approved', 'sustainable-catalyst-engagement-intake' ),
			'superseded'     => __( 'Superseded', 'sustainable-catalyst-engagement-intake' ),
			'withdrawn'      => __( 'Withdrawn', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function change_statuses(): array {
		return array(
			'requested'    => __( 'Requested', 'sustainable-catalyst-engagement-intake' ),
			'under_review' => __( 'Under Review', 'sustainable-catalyst-engagement-intake' ),
			'approved'     => __( 'Approved', 'sustainable-catalyst-engagement-intake' ),
			'declined'     => __( 'Declined', 'sustainable-catalyst-engagement-intake' ),
			'applied'      => __( 'Applied', 'sustainable-catalyst-engagement-intake' ),
			'withdrawn'    => __( 'Withdrawn', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function sender_actions(): array {
		return array(
			'confirm_receipt' => __( 'Confirm receipt', 'sustainable-catalyst-engagement-intake' ),
			'request_changes' => __( 'Request changes', 'sustainable-catalyst-engagement-intake' ),
			'accept'          => __( 'Accept proposal', 'sustainable-catalyst-engagement-intake' ),
			'decline'         => __( 'Decline proposal', 'sustainable-catalyst-engagement-intake' ),
			'approve_sow'     => __( 'Approve Statement of Work', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function sanitize_status( string $status, array $choices, string $default ): string {
		$status = sanitize_key( $status );
		return isset( $choices[ $status ] ) ? $status : $default;
	}

	public static function sanitize_lines( $value, int $limit = 100 ): array {
		$values = is_array( $value ) ? $value : preg_split( '/\r\n|\r|\n/', (string) $value );
		$result = array();
		foreach ( (array) $values as $line ) {
			$line = trim( sanitize_text_field( (string) $line ) );
			if ( '' === $line ) {
				continue;
			}
			$result[ strtolower( $line ) ] = mb_substr( $line, 0, 500 );
			if ( count( $result ) >= max( 1, $limit ) ) {
				break;
			}
		}
		return array_values( $result );
	}

	public static function money_minor( $value ): int {
		$value = preg_replace( '/[^0-9.\-]/', '', (string) $value );
		return '' !== $value && is_numeric( $value ) ? max( 0, (int) round( (float) $value * 100 ) ) : 0;
	}

	public static function canonical_hash( array $payload ): string {
		ksort( $payload );
		return hash( 'sha256', wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
	}

	public static function sender_projection_keys(): array {
		return array(
			'id', 'public_id', 'sow_number', 'proposal_id', 'proposal_number', 'status', 'version_number', 'title',
			'purpose_background', 'scope_json', 'deliverables_json', 'milestones_json', 'responsibilities_json',
			'dependencies_json', 'acceptance_criteria', 'change_control', 'communication_expectations',
			'data_handling', 'ip_terms', 'open_source_boundaries', 'fees_payment', 'start_date', 'target_end_date',
			'termination_conditions', 'attachment_ids_json', 'content_hash', 'approved_at', 'sender_approved_at',
		);
	}
}
