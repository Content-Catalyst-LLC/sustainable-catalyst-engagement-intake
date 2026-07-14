<?php
/**
 * Advisory lifecycle stages, qualification fields, service routes, and task taxonomies.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Lifecycle_Schema {

	public static function stages(): array {
		return array(
			'new_inquiry'          => __( 'New Inquiry', 'sustainable-catalyst-engagement-intake' ),
			'under_review'         => __( 'Under Review', 'sustainable-catalyst-engagement-intake' ),
			'needs_information'    => __( 'Needs Information', 'sustainable-catalyst-engagement-intake' ),
			'qualified'            => __( 'Qualified', 'sustainable-catalyst-engagement-intake' ),
			'meeting_requested'    => __( 'Meeting Requested', 'sustainable-catalyst-engagement-intake' ),
			'meeting_scheduled'    => __( 'Meeting Scheduled', 'sustainable-catalyst-engagement-intake' ),
			'proposal_preparation' => __( 'Proposal in Preparation', 'sustainable-catalyst-engagement-intake' ),
			'proposal_sent'        => __( 'Proposal Sent', 'sustainable-catalyst-engagement-intake' ),
			'accepted'             => __( 'Accepted', 'sustainable-catalyst-engagement-intake' ),
			'active_engagement'    => __( 'Active Engagement', 'sustainable-catalyst-engagement-intake' ),
			'completed'            => __( 'Completed', 'sustainable-catalyst-engagement-intake' ),
			'declined'             => __( 'Declined', 'sustainable-catalyst-engagement-intake' ),
			'archived'             => __( 'Archived', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function public_stage_labels(): array {
		return array(
			'new_inquiry'          => __( 'Received', 'sustainable-catalyst-engagement-intake' ),
			'under_review'         => __( 'Under Review', 'sustainable-catalyst-engagement-intake' ),
			'needs_information'    => __( 'Additional Information Requested', 'sustainable-catalyst-engagement-intake' ),
			'qualified'            => __( 'Qualified for Next-Step Review', 'sustainable-catalyst-engagement-intake' ),
			'meeting_requested'    => __( 'Meeting Coordination', 'sustainable-catalyst-engagement-intake' ),
			'meeting_scheduled'    => __( 'Meeting Scheduled', 'sustainable-catalyst-engagement-intake' ),
			'proposal_preparation' => __( 'Proposal Preparation', 'sustainable-catalyst-engagement-intake' ),
			'proposal_sent'        => __( 'Proposal Available', 'sustainable-catalyst-engagement-intake' ),
			'accepted'             => __( 'Engagement Accepted', 'sustainable-catalyst-engagement-intake' ),
			'active_engagement'    => __( 'Engagement Active', 'sustainable-catalyst-engagement-intake' ),
			'completed'            => __( 'Engagement Completed', 'sustainable-catalyst-engagement-intake' ),
			'declined'             => __( 'Closed After Review', 'sustainable-catalyst-engagement-intake' ),
			'archived'             => __( 'Archived', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function allowed_transitions(): array {
		return array(
			'new_inquiry'          => array( 'under_review', 'needs_information', 'declined', 'archived' ),
			'under_review'         => array( 'needs_information', 'qualified', 'meeting_requested', 'declined', 'archived' ),
			'needs_information'    => array( 'under_review', 'qualified', 'declined', 'archived' ),
			'qualified'            => array( 'meeting_requested', 'meeting_scheduled', 'proposal_preparation', 'declined', 'archived' ),
			'meeting_requested'    => array( 'meeting_scheduled', 'qualified', 'needs_information', 'declined', 'archived' ),
			'meeting_scheduled'    => array( 'proposal_preparation', 'qualified', 'declined', 'archived' ),
			'proposal_preparation' => array( 'proposal_sent', 'qualified', 'declined', 'archived' ),
			'proposal_sent'        => array( 'accepted', 'proposal_preparation', 'declined', 'archived' ),
			'accepted'             => array( 'active_engagement', 'proposal_sent', 'declined', 'archived' ),
			'active_engagement'    => array( 'completed', 'archived' ),
			'completed'            => array( 'archived' ),
			'declined'             => array( 'under_review', 'archived' ),
			'archived'             => array( 'under_review' ),
		);
	}

	public static function qualification_statuses(): array {
		return array(
			'not_started' => __( 'Not Started', 'sustainable-catalyst-engagement-intake' ),
			'in_progress' => __( 'In Progress', 'sustainable-catalyst-engagement-intake' ),
			'complete'    => __( 'Complete', 'sustainable-catalyst-engagement-intake' ),
			'waived'      => __( 'Waived', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function priorities(): array {
		return array(
			'low'    => __( 'Low', 'sustainable-catalyst-engagement-intake' ),
			'normal' => __( 'Normal', 'sustainable-catalyst-engagement-intake' ),
			'high'   => __( 'High', 'sustainable-catalyst-engagement-intake' ),
			'urgent' => __( 'Urgent', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function task_statuses(): array {
		return array(
			'open'        => __( 'Open', 'sustainable-catalyst-engagement-intake' ),
			'in_progress' => __( 'In Progress', 'sustainable-catalyst-engagement-intake' ),
			'completed'   => __( 'Completed', 'sustainable-catalyst-engagement-intake' ),
			'canceled'    => __( 'Canceled', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function note_types(): array {
		return array(
			'internal'       => __( 'Internal Note', 'sustainable-catalyst-engagement-intake' ),
			'qualification'  => __( 'Qualification Note', 'sustainable-catalyst-engagement-intake' ),
			'meeting'        => __( 'Meeting Note', 'sustainable-catalyst-engagement-intake' ),
			'proposal'       => __( 'Proposal Note', 'sustainable-catalyst-engagement-intake' ),
			'delivery'       => __( 'Delivery Note', 'sustainable-catalyst-engagement-intake' ),
			'risk'           => __( 'Risk or Escalation Note', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function service_routes(): array {
		return array(
			'general' => array(
				'label' => __( 'General Inquiry', 'sustainable-catalyst-engagement-intake' ),
				'inquiry_type' => 'general',
				'service_interest' => '',
			),
			'advisory' => array(
				'label' => __( 'Advisory', 'sustainable-catalyst-engagement-intake' ),
				'inquiry_type' => 'consulting',
				'service_interest' => 'strategic_consultation',
			),
			'ai-assurance' => array(
				'label' => __( 'Sustainable AI Assurance', 'sustainable-catalyst-engagement-intake' ),
				'inquiry_type' => 'consulting',
				'service_interest' => 'sustainable_ai_assurance',
			),
			'evidence-systems' => array(
				'label' => __( 'Evidence Systems Diagnostic', 'sustainable-catalyst-engagement-intake' ),
				'inquiry_type' => 'consulting',
				'service_interest' => 'evidence_systems_diagnostic',
			),
			'knowledge-architecture' => array(
				'label' => __( 'Knowledge Architecture', 'sustainable-catalyst-engagement-intake' ),
				'inquiry_type' => 'consulting',
				'service_interest' => 'knowledge_architecture',
			),
			'technical-storytelling' => array(
				'label' => __( 'Technical Storytelling', 'sustainable-catalyst-engagement-intake' ),
				'inquiry_type' => 'consulting',
				'service_interest' => 'technical_storytelling',
			),
			'responsible-ai' => array(
				'label' => __( 'Responsible AI Workflows', 'sustainable-catalyst-engagement-intake' ),
				'inquiry_type' => 'consulting',
				'service_interest' => 'responsible_ai_workflows',
			),
			'collaboration' => array(
				'label' => __( 'Research Collaboration', 'sustainable-catalyst-engagement-intake' ),
				'inquiry_type' => 'research_collaboration',
				'service_interest' => 'research_collaboration',
			),
			'media' => array(
				'label' => __( 'Media or Speaking', 'sustainable-catalyst-engagement-intake' ),
				'inquiry_type' => 'speaking_media',
				'service_interest' => 'media_speaking',
			),
			'technical' => array(
				'label' => __( 'Platform or Technical Work', 'sustainable-catalyst-engagement-intake' ),
				'inquiry_type' => 'platform_technical',
				'service_interest' => 'open_source_technical',
			),
			'partnership' => array(
				'label' => __( 'Institutional Partnership', 'sustainable-catalyst-engagement-intake' ),
				'inquiry_type' => 'institutional_partnership',
				'service_interest' => 'institutional_partnership',
			),
			'workshop' => array(
				'label' => __( 'Workshop or Briefing', 'sustainable-catalyst-engagement-intake' ),
				'inquiry_type' => 'workshop_training',
				'service_interest' => 'workshop_briefing',
			),
			'monthly-advisory' => array(
				'label' => __( 'Monthly Advisory Support', 'sustainable-catalyst-engagement-intake' ),
				'inquiry_type' => 'monthly_advisory',
				'service_interest' => 'monthly_advisory',
			),
		);
	}

	public static function decision_authority_options(): array {
		return array(
			'unknown'       => __( 'Unknown', 'sustainable-catalyst-engagement-intake' ),
			'informational' => __( 'Informational Contact', 'sustainable-catalyst-engagement-intake' ),
			'influencer'    => __( 'Influencer or Recommender', 'sustainable-catalyst-engagement-intake' ),
			'co_decider'    => __( 'Co-decision Maker', 'sustainable-catalyst-engagement-intake' ),
			'decision_maker'=> __( 'Decision Maker', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function funding_statuses(): array {
		return array(
			'unknown'       => __( 'Unknown', 'sustainable-catalyst-engagement-intake' ),
			'not_discussed' => __( 'Not Discussed', 'sustainable-catalyst-engagement-intake' ),
			'exploratory'   => __( 'Exploratory', 'sustainable-catalyst-engagement-intake' ),
			'budgeted'      => __( 'Budget Allocated', 'sustainable-catalyst-engagement-intake' ),
			'funded'        => __( 'Funded and Authorized', 'sustainable-catalyst-engagement-intake' ),
			'pro_bono'      => __( 'Pro Bono or Public-Interest Review', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function assessment_options(): array {
		return array(
			'not_assessed' => __( 'Not Assessed', 'sustainable-catalyst-engagement-intake' ),
			'no'           => __( 'No', 'sustainable-catalyst-engagement-intake' ),
			'possible'     => __( 'Possible', 'sustainable-catalyst-engagement-intake' ),
			'yes'          => __( 'Yes', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function teams_readiness_options(): array {
		return array(
			'not_assessed'       => __( 'Not Assessed', 'sustainable-catalyst-engagement-intake' ),
			'not_ready'          => __( 'Not Ready', 'sustainable-catalyst-engagement-intake' ),
			'needs_information'  => __( 'Needs Information', 'sustainable-catalyst-engagement-intake' ),
			'ready_to_coordinate'=> __( 'Ready to Coordinate', 'sustainable-catalyst-engagement-intake' ),
			'scheduled'          => __( 'Scheduled', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function default_settings(): array {
		return array(
			'lifecycle_enabled'                    => 1,
			'lifecycle_default_follow_up_days'      => 3,
			'lifecycle_task_reminders_enabled'      => 1,
			'lifecycle_task_email_enabled'          => 0,
			'lifecycle_task_batch_limit'            => 50,
			'lifecycle_require_transition_reason'   => 1,
			'lifecycle_require_owner_for_qualified' => 1,
			'lifecycle_sender_summary_enabled'      => 1,
			'lifecycle_no_automatic_rejection'      => 1,
			'lifecycle_no_automatic_commitment'     => 1,
		);
	}

	public static function sanitize_stage( string $value ): string {
		$value = sanitize_key( $value );
		return isset( self::stages()[ $value ] ) ? $value : 'new_inquiry';
	}

	public static function sanitize_priority( string $value ): string {
		$value = sanitize_key( $value );
		return isset( self::priorities()[ $value ] ) ? $value : 'normal';
	}

	public static function sanitize_task_status( string $value ): string {
		$value = sanitize_key( $value );
		return isset( self::task_statuses()[ $value ] ) ? $value : 'open';
	}

	public static function sanitize_qualification_status( string $value ): string {
		$value = sanitize_key( $value );
		return isset( self::qualification_statuses()[ $value ] ) ? $value : 'not_started';
	}

	public static function can_transition( string $from, string $to ): bool {
		$from = self::sanitize_stage( $from );
		$to = self::sanitize_stage( $to );
		return $from === $to || in_array( $to, self::allowed_transitions()[ $from ] ?? array(), true );
	}

	public static function map_legacy_status( string $status ): string {
		$map = array(
			'new'                      => 'new_inquiry',
			'under_review'             => 'under_review',
			'more_information_needed'  => 'needs_information',
			'fit_call_recommended'     => 'meeting_requested',
			'consultation_recommended' => 'qualified',
			'proposal_requested'       => 'proposal_preparation',
			'proposal_sent'            => 'proposal_sent',
			'accepted'                 => 'accepted',
			'not_a_fit'                => 'declined',
			'referred'                 => 'declined',
			'withdrawn'                => 'archived',
			'closed'                   => 'archived',
		);
		return $map[ sanitize_key( $status ) ] ?? 'new_inquiry';
	}

	public static function legacy_status_for_stage( string $stage ): string {
		$map = array(
			'new_inquiry'          => 'new',
			'under_review'         => 'under_review',
			'needs_information'    => 'more_information_needed',
			'qualified'            => 'consultation_recommended',
			'meeting_requested'    => 'fit_call_recommended',
			'meeting_scheduled'    => 'fit_call_recommended',
			'proposal_preparation' => 'proposal_requested',
			'proposal_sent'        => 'proposal_sent',
			'accepted'             => 'accepted',
			'active_engagement'    => 'accepted',
			'completed'            => 'closed',
			'declined'             => 'not_a_fit',
			'archived'             => 'closed',
		);
		return $map[ self::sanitize_stage( $stage ) ] ?? 'new';
	}

	public static function label( array $labels, string $key ): string {
		return $labels[ $key ] ?? ucwords( str_replace( '_', ' ', $key ) );
	}
}
