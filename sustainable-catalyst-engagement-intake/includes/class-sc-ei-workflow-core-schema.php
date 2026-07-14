<?php
/**
 * Canonical Workflow Core stages, commands, contracts, and fixed boundaries.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Workflow_Core_Schema {

	public static function stages(): array {
		return array(
			'intake'             => __( 'Intake', 'sustainable-catalyst-engagement-intake' ),
			'review'             => __( 'Administrative Review', 'sustainable-catalyst-engagement-intake' ),
			'fit'                => __( 'Fit Assessment', 'sustainable-catalyst-engagement-intake' ),
			'consultation'       => __( 'Consultation and Scheduling', 'sustainable-catalyst-engagement-intake' ),
			'proposal'           => __( 'Proposal', 'sustainable-catalyst-engagement-intake' ),
			'contracted'         => __( 'External Contract Recorded', 'sustainable-catalyst-engagement-intake' ),
			'engagement_handoff' => __( 'Engagement Handoff', 'sustainable-catalyst-engagement-intake' ),
			'active_engagement'  => __( 'Active Engagement', 'sustainable-catalyst-engagement-intake' ),
			'completed'          => __( 'Completed', 'sustainable-catalyst-engagement-intake' ),
			'closed'             => __( 'Closed', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function states(): array {
		return array(
			'new'                       => __( 'New', 'sustainable-catalyst-engagement-intake' ),
			'review_pending'            => __( 'Review Pending', 'sustainable-catalyst-engagement-intake' ),
			'review_in_progress'        => __( 'Review in Progress', 'sustainable-catalyst-engagement-intake' ),
			'more_information_needed'   => __( 'More Information Needed', 'sustainable-catalyst-engagement-intake' ),
			'fit_draft'                 => __( 'Fit Assessment Draft', 'sustainable-catalyst-engagement-intake' ),
			'fit_submitted'             => __( 'Fit Assessment Submitted', 'sustainable-catalyst-engagement-intake' ),
			'fit_finalized'             => __( 'Fit Assessment Finalized', 'sustainable-catalyst-engagement-intake' ),
			'consultation_recommended'  => __( 'Consultation Recommended', 'sustainable-catalyst-engagement-intake' ),
			'meeting_offered'           => __( 'Meeting Offered', 'sustainable-catalyst-engagement-intake' ),
			'meeting_accepted'          => __( 'Meeting Accepted', 'sustainable-catalyst-engagement-intake' ),
			'meeting_scheduled'         => __( 'Meeting Scheduled', 'sustainable-catalyst-engagement-intake' ),
			'meeting_completed'         => __( 'Meeting Completed', 'sustainable-catalyst-engagement-intake' ),
			'proposal_draft'            => __( 'Proposal Draft', 'sustainable-catalyst-engagement-intake' ),
			'proposal_published'        => __( 'Proposal Published', 'sustainable-catalyst-engagement-intake' ),
			'proposal_accepted'         => __( 'Proposal Accepted for Contracting', 'sustainable-catalyst-engagement-intake' ),
			'contract_recorded'         => __( 'Contract Recorded', 'sustainable-catalyst-engagement-intake' ),
			'handoff_pending'           => __( 'Handoff Pending', 'sustainable-catalyst-engagement-intake' ),
			'ready_for_setup'           => __( 'Ready for Setup', 'sustainable-catalyst-engagement-intake' ),
			'active'                    => __( 'Active', 'sustainable-catalyst-engagement-intake' ),
			'paused'                    => __( 'Paused', 'sustainable-catalyst-engagement-intake' ),
			'completed'                 => __( 'Completed', 'sustainable-catalyst-engagement-intake' ),
			'not_a_fit'                 => __( 'Not a Fit', 'sustainable-catalyst-engagement-intake' ),
			'referred'                  => __( 'Referred', 'sustainable-catalyst-engagement-intake' ),
			'withdrawn'                 => __( 'Withdrawn', 'sustainable-catalyst-engagement-intake' ),
			'canceled'                  => __( 'Canceled', 'sustainable-catalyst-engagement-intake' ),
			'closed'                    => __( 'Closed', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function consistency_states(): array {
		return array(
			'consistent' => __( 'Consistent', 'sustainable-catalyst-engagement-intake' ),
			'warning'    => __( 'Warning', 'sustainable-catalyst-engagement-intake' ),
			'blocked'    => __( 'Blocked', 'sustainable-catalyst-engagement-intake' ),
			'stale'      => __( 'Stale', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function command_types(): array {
		return array(
			'sync_case'           => __( 'Synchronize Case Projection', 'sustainable-catalyst-engagement-intake' ),
			'rebuild_case'        => __( 'Rebuild Case Projection', 'sustainable-catalyst-engagement-intake' ),
			'prepare_handoff'     => __( 'Prepare Cross-Plugin Handoff', 'sustainable-catalyst-engagement-intake' ),
			'dispatch_outbox'     => __( 'Dispatch Pending Outbox Events', 'sustainable-catalyst-engagement-intake' ),
			'acknowledge_handoff' => __( 'Acknowledge Handoff', 'sustainable-catalyst-engagement-intake' ),
			'cancel_handoff'      => __( 'Cancel Handoff', 'sustainable-catalyst-engagement-intake' ),
			'resolve_consistency' => __( 'Resolve Consistency Warning', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function command_statuses(): array {
		return array(
			'pending'    => __( 'Pending', 'sustainable-catalyst-engagement-intake' ),
			'processing' => __( 'Processing', 'sustainable-catalyst-engagement-intake' ),
			'succeeded'  => __( 'Succeeded', 'sustainable-catalyst-engagement-intake' ),
			'failed'     => __( 'Failed', 'sustainable-catalyst-engagement-intake' ),
			'canceled'   => __( 'Canceled', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function handoff_targets(): array {
		return apply_filters(
			'sc_ei_workflow_core_handoff_targets',
			array(
				'workbench'          => __( 'Sustainable Catalyst Workbench', 'sustainable-catalyst-engagement-intake' ),
				'decision_studio'    => __( 'Decision Studio', 'sustainable-catalyst-engagement-intake' ),
				'site_intelligence'  => __( 'Site Intelligence', 'sustainable-catalyst-engagement-intake' ),
				'research_librarian' => __( 'Research Librarian', 'sustainable-catalyst-engagement-intake' ),
				'platform_core'      => __( 'Sustainable Catalyst Platform Core', 'sustainable-catalyst-engagement-intake' ),
				'generic_internal'   => __( 'Generic Internal Adapter', 'sustainable-catalyst-engagement-intake' ),
			)
		);
	}

	public static function handoff_statuses(): array {
		return array(
			'prepared'     => __( 'Prepared', 'sustainable-catalyst-engagement-intake' ),
			'dispatched'   => __( 'Dispatched', 'sustainable-catalyst-engagement-intake' ),
			'acknowledged' => __( 'Acknowledged', 'sustainable-catalyst-engagement-intake' ),
			'failed'       => __( 'Failed', 'sustainable-catalyst-engagement-intake' ),
			'canceled'     => __( 'Canceled', 'sustainable-catalyst-engagement-intake' ),
			'expired'      => __( 'Expired', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function outbox_statuses(): array {
		return array(
			'pending'      => __( 'Pending', 'sustainable-catalyst-engagement-intake' ),
			'processing'   => __( 'Processing', 'sustainable-catalyst-engagement-intake' ),
			'dispatched'   => __( 'Dispatched', 'sustainable-catalyst-engagement-intake' ),
			'acknowledged' => __( 'Acknowledged', 'sustainable-catalyst-engagement-intake' ),
			'retry_wait'   => __( 'Retry Waiting', 'sustainable-catalyst-engagement-intake' ),
			'failed'       => __( 'Failed', 'sustainable-catalyst-engagement-intake' ),
			'canceled'     => __( 'Canceled', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function data_classifications(): array {
		return array(
			'operational_minimum' => __( 'Operational Minimum', 'sustainable-catalyst-engagement-intake' ),
			'internal_private'    => __( 'Internal Private', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function default_settings(): array {
		return array(
			'workflow_core_enabled'                        => 1,
			'workflow_core_auto_sync_on_audit'             => 1,
			'workflow_core_sync_interval_minutes'          => 60,
			'workflow_core_stale_after_hours'              => 24,
			'workflow_core_outbox_enabled'                 => 1,
			'workflow_core_outbox_batch_limit'             => 25,
			'workflow_core_outbox_max_attempts'            => 6,
			'workflow_core_handoff_expiry_days'            => 30,
			'workflow_core_default_classification'         => 'operational_minimum',
			'workflow_core_require_typed_commands'         => 1,
			'workflow_core_require_handoff_signature'      => 1,
			'workflow_core_include_personal_data_default'  => 0,
			'workflow_core_no_auto_acceptance'             => 1,
			'workflow_core_no_auto_fit_decision'           => 1,
			'workflow_core_no_auto_proposal'               => 1,
			'workflow_core_no_auto_contract'               => 1,
			'workflow_core_no_auto_activation'             => 1,
			'workflow_core_no_auto_external_delivery'      => 1,
			'workflow_core_no_unverified_inbound_commands' => 1,
		);
	}

	public static function sanitize_stage( string $value ): string {
		$value = sanitize_key( $value );
		return isset( self::stages()[ $value ] ) ? $value : 'intake';
	}

	public static function sanitize_state( string $value ): string {
		$value = sanitize_key( $value );
		return isset( self::states()[ $value ] ) ? $value : 'new';
	}

	public static function sanitize_command_type( string $value ): string {
		$value = sanitize_key( $value );
		return isset( self::command_types()[ $value ] ) ? $value : '';
	}

	public static function sanitize_target( string $value ): string {
		$value = sanitize_key( $value );
		return isset( self::handoff_targets()[ $value ] ) ? $value : '';
	}

	public static function sanitize_classification( string $value ): string {
		$value = sanitize_key( $value );
		return isset( self::data_classifications()[ $value ] ) ? $value : 'operational_minimum';
	}

	public static function label( array $choices, string $value ): string {
		return $choices[ $value ] ?? ucwords( str_replace( '_', ' ', $value ) );
	}
}
