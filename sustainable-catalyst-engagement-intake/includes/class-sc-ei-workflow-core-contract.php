<?php
/**
 * Signed, versioned cross-plugin Workflow Core handoff contracts.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Workflow_Core_Contract {

	public const SCHEMA_ID = 'sc-engagement-workflow-handoff/1.0';
	public const CONTRACT_VERSION = '1.0.0';

	public static function build(
		array $case,
		string $target,
		string $classification = 'operational_minimum',
		bool $include_personal_data = false
	) {
		$target = SC_EI_Workflow_Core_Schema::sanitize_target( $target );
		if ( '' === $target ) {
			return new WP_Error( 'workflow_core_target_invalid', __( 'Choose a registered Workflow Core handoff target.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$classification = SC_EI_Workflow_Core_Schema::sanitize_classification( $classification );
		if ( 'internal_private' !== $classification ) {
			$include_personal_data = false;
		}

		$inquiry_id = absint( $case['inquiry_id'] ?? 0 );
		$inquiry = $inquiry_id ? SC_EI_Inquiry_Repository::find( $inquiry_id ) : null;
		if ( ! $inquiry ) {
			return new WP_Error( 'workflow_core_inquiry_missing', __( 'The authoritative inquiry record could not be loaded.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$fit = ! empty( $inquiry['current_fit_assessment_id'] )
			? SC_EI_Fit_Repository::find( absint( $inquiry['current_fit_assessment_id'] ) )
			: null;
		$workflow = SC_EI_Workflow_Repository::export_for_inquiry( $inquiry_id );
		$engagement = SC_EI_Engagement_Repository::export_for_inquiry( $inquiry_id );
		$review_history = SC_EI_Review_Repository::history( $inquiry_id, 100 );
		$portal = SC_EI_Portal_Repository::export_for_inquiry( $inquiry_id );
		$portal_permissions = json_decode( (string) ( $portal['access']['permissions_json'] ?? '' ), true );
		if ( ! is_array( $portal_permissions ) ) {
			$portal_permissions = array();
		}

		$package = array(
			'schema'           => self::SCHEMA_ID,
			'contract_version' => self::CONTRACT_VERSION,
			'generated_at'     => current_time( 'mysql', true ),
			'target'           => $target,
			'classification'   => $classification,
			'personal_data_included' => $include_personal_data,
			'case' => array(
				'public_id'          => (string) ( $case['public_id'] ?? '' ),
				'inquiry_public_id'  => (string) ( $inquiry['public_id'] ?? '' ),
				'reference'          => (string) ( $inquiry['reference'] ?? '' ),
				'stage'              => (string) ( $case['current_stage'] ?? 'intake' ),
				'state'              => (string) ( $case['current_state'] ?? 'new' ),
				'terminal_state'     => (string) ( $case['terminal_state'] ?? '' ),
				'consistency_status' => (string) ( $case['consistency_status'] ?? 'consistent' ),
				'projection_version' => absint( $case['projection_version'] ?? 0 ),
				'projection_hash'    => (string) ( $case['projection_hash'] ?? '' ),
				'blocker_count'      => absint( $case['blocker_count'] ?? 0 ),
				'last_synced_at'     => (string) ( $case['last_synced_at'] ?? '' ),
			),
			'request' => array(
				'inquiry_type'       => (string) $inquiry['inquiry_type'],
				'service_interest'   => (string) $inquiry['service_interest'],
				'desired_start_date' => (string) $inquiry['desired_start_date'],
				'deadline_date'      => (string) $inquiry['deadline_date'],
				'preferred_contact_method' => (string) $inquiry['preferred_contact_method'],
				'meeting_requested'  => 'yes' === (string) $inquiry['meeting_request'],
				'privacy_status'     => (string) $inquiry['privacy_status'],
				'retention_policy'   => (string) $inquiry['retention_policy_key'],
				'legal_hold_count'   => absint( $inquiry['legal_hold_count'] ),
			),
			'review' => array(
				'stage'                 => (string) $inquiry['review_stage'],
				'priority'              => (string) $inquiry['review_priority'],
				'fit_decision'          => (string) $inquiry['fit_decision'],
				'risk_level'            => (string) $inquiry['risk_level'],
				'evidence_readiness'    => (string) $inquiry['evidence_readiness'],
				'scope_clarity'         => (string) $inquiry['scope_clarity'],
				'recommended_next_step' => (string) $inquiry['recommended_next_step'],
				'decision_at'           => (string) $inquiry['decision_at'],
				'review_completed_at'   => (string) $inquiry['review_completed_at'],
				'history_count'         => count( $review_history ),
			),
			'fit_assessment' => $fit ? array(
				'public_id'          => (string) $fit['public_id'],
				'status'             => (string) $fit['status'],
				'final_recommendation'=> (string) $fit['final_recommendation'],
				'risk_level'         => (string) $fit['risk_level'],
				'advisory_score'     => isset( $fit['advisory_score'] ) ? (float) $fit['advisory_score'] : null,
				'content_hash'       => (string) $fit['content_hash'],
				'finalized_at'       => (string) $fit['finalized_at'],
				'human_finalized'    => ! empty( $fit['finalized_by'] ),
			) : null,
			'scheduling_and_proposal' => self::workflow_summary( $workflow ),
			'engagement'              => self::engagement_summary( $engagement ),
			'portal' => array(
				'access_state'       => (string) ( $portal['access']['status'] ?? 'inactive' ),
				'permission_keys'    => array_values( array_map( 'sanitize_key', $portal_permissions ) ),
				'active_session_count'=> count( array_filter( (array) ( $portal['sessions'] ?? array() ), static fn( array $session ): bool => empty( $session['revoked_at'] ) ) ),
			),
			'integration' => array(
				'idempotency_scope'  => 'inquiry',
				'source_plugin'      => 'sustainable-catalyst-engagement-intake',
				'source_version'     => SC_EI_VERSION,
				'delivery_mode'      => 'wordpress_internal_adapter',
				'automatic_external_delivery' => false,
				'requires_human_acknowledgment'=> true,
			),
			'boundaries' => array(
				'automatic_acceptance'      => false,
				'automatic_fit_decision'    => false,
				'automatic_proposal'        => false,
				'automatic_contract'        => false,
				'automatic_activation'      => false,
				'automatic_project_creation'=> false,
				'electronic_signature'      => false,
				'payment_collection'        => false,
				'inbound_command_execution' => false,
			),
		);

		if ( $include_personal_data ) {
			$package['request']['contact'] = array(
				'name'         => (string) $inquiry['contact_name'],
				'email'        => (string) $inquiry['contact_email'],
				'organization' => (string) $inquiry['organization'],
				'role_title'   => (string) $inquiry['role_title'],
				'phone_number' => (string) $inquiry['phone_number'],
				'city'         => (string) $inquiry['city'],
				'country'      => (string) $inquiry['country'],
			);
			$package['request']['content'] = array(
				'subject'         => (string) $inquiry['subject'],
				'project_summary' => (string) $inquiry['project_summary'],
				'desired_outcome' => (string) $inquiry['desired_outcome'],
				'relevant_links'  => json_decode( (string) $inquiry['relevant_links'], true ) ?: array(),
			);
		}

		$sealed = self::seal_payload( $package, $target );
		if ( is_wp_error( $sealed ) ) {
			return $sealed;
		}
		$sealed['schema_id'] = self::SCHEMA_ID;
		$sealed['contract_version'] = self::CONTRACT_VERSION;
		return $sealed;
	}

	public static function seal_payload( array $package, string $target ) {
		$target = SC_EI_Workflow_Core_Schema::sanitize_target( $target );
		if ( '' === $target ) {
			return new WP_Error( 'workflow_core_target_invalid', __( 'Choose a registered Workflow Core handoff target.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$package = self::canonicalize( $package );
		$json = wp_json_encode( $package, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( false === $json ) {
			return new WP_Error( 'workflow_core_contract_encode_failed', __( 'The Workflow Core handoff package could not be encoded.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$hash = hash( 'sha256', $json );
		$signature = hash_hmac( 'sha256', self::SCHEMA_ID . '|' . $target . '|' . $hash, self::signing_key() );
		return array(
			'package'      => $package,
			'payload_json' => $json,
			'content_hash' => $hash,
			'signature'    => $signature,
		);
	}

	public static function verify( string $payload_json, string $target, string $content_hash, string $signature ): bool {
		if ( '' === $payload_json || '' === $content_hash || '' === $signature ) {
			return false;
		}
		$actual_hash = hash( 'sha256', $payload_json );
		if ( ! hash_equals( $content_hash, $actual_hash ) ) {
			return false;
		}
		$expected = hash_hmac(
			'sha256',
			self::SCHEMA_ID . '|' . SC_EI_Workflow_Core_Schema::sanitize_target( $target ) . '|' . $content_hash,
			self::signing_key()
		);
		return hash_equals( $expected, $signature );
	}

	public static function public_metadata( array $handoff ): array {
		return array(
			'public_id'       => (string) $handoff['public_id'],
			'handoff_key'     => (string) $handoff['handoff_key'],
			'target'          => (string) $handoff['target'],
			'schema_id'       => (string) $handoff['schema_id'],
			'contract_version'=> (string) $handoff['contract_version'],
			'classification'  => (string) $handoff['data_classification'],
			'status'          => (string) $handoff['status'],
			'content_hash'    => (string) $handoff['content_hash'],
			'signature_set'   => '' !== (string) $handoff['signature'],
			'prepared_at'     => (string) $handoff['prepared_at'],
			'acknowledged_at' => (string) $handoff['acknowledged_at'],
			'expires_at'      => (string) $handoff['expires_at'],
		);
	}

	private static function workflow_summary( array $workflow ): array {
		$meetings = (array) ( $workflow['meeting_offers'] ?? array() );
		$proposals = (array) ( $workflow['proposals'] ?? array() );
		$latest_meeting = $meetings[0] ?? array();
		$latest_proposal = $proposals[0] ?? array();

		return array(
			'meeting_count'       => count( $meetings ),
			'latest_meeting'      => $latest_meeting ? array(
				'public_id'      => (string) ( $latest_meeting['public_id'] ?? '' ),
				'offer_number'   => (string) ( $latest_meeting['offer_number'] ?? '' ),
				'status'         => (string) ( $latest_meeting['status'] ?? '' ),
				'selected_start_utc' => (string) ( $latest_meeting['selected_start_utc'] ?? '' ),
				'selected_end_utc'   => (string) ( $latest_meeting['selected_end_utc'] ?? '' ),
				'graph_sync_status'  => (string) ( $latest_meeting['graph_sync_status'] ?? 'not_requested' ),
			) : null,
			'proposal_count'      => count( $proposals ),
			'latest_proposal'     => $latest_proposal ? array(
				'public_id'      => (string) ( $latest_proposal['public_id'] ?? '' ),
				'proposal_number'=> (string) ( $latest_proposal['proposal_number'] ?? '' ),
				'status'         => (string) ( $latest_proposal['status'] ?? '' ),
				'version_number' => absint( $latest_proposal['version_number'] ?? 0 ),
				'content_hash'   => (string) ( $latest_proposal['content_hash'] ?? '' ),
				'currency'       => (string) ( $latest_proposal['currency'] ?? '' ),
				'total_minor'    => absint( $latest_proposal['total_minor'] ?? 0 ),
				'contract_reference' => (string) ( $latest_proposal['contract_reference'] ?? '' ),
			) : null,
		);
	}

	private static function engagement_summary( array $engagement_export ): array {
		$items = (array) ( $engagement_export['engagements'] ?? array() );
		$latest = $items[0]['engagement'] ?? array();
		return array(
			'count'  => count( $items ),
			'latest' => $latest ? array(
				'public_id'         => (string) ( $latest['public_id'] ?? '' ),
				'engagement_number' => (string) ( $latest['engagement_number'] ?? '' ),
				'status'            => (string) ( $latest['status'] ?? '' ),
				'proposed_start_date'=> (string) ( $latest['proposed_start_date'] ?? '' ),
				'target_end_date'   => (string) ( $latest['target_end_date'] ?? '' ),
				'kickoff_status'    => (string) ( $latest['kickoff_status'] ?? '' ),
				'workbench_handoff_status' => (string) ( $latest['workbench_handoff_status'] ?? 'not_requested' ),
				'decision_studio_handoff_status' => (string) ( $latest['decision_studio_handoff_status'] ?? 'not_requested' ),
			) : null,
		);
	}

	private static function canonicalize( $value ) {
		if ( is_array( $value ) ) {
			if ( self::is_associative( $value ) ) {
				ksort( $value, SORT_STRING );
			}
			foreach ( $value as $key => $item ) {
				$value[ $key ] = self::canonicalize( $item );
			}
		}
		return $value;
	}

	private static function is_associative( array $value ): bool {
		return array_keys( $value ) !== range( 0, count( $value ) - 1 );
	}

	private static function signing_key(): string {
		return hash_hmac(
			'sha256',
			'sc-ei-workflow-core-contract|' . SC_EI_WORKFLOW_CORE_SCHEMA_VERSION,
			wp_salt( 'secure_auth' ),
			true
		);
	}
}
