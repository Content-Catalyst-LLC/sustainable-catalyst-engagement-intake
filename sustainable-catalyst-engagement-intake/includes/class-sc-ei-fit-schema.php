<?php
/**
 * Human-controlled fit assessment taxonomies and transparent calculations.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Fit_Schema {

	public static function statuses(): array {
		return array(
			'draft'                   => __( 'Draft', 'sustainable-catalyst-engagement-intake' ),
			'submitted'               => __( 'Submitted for Review', 'sustainable-catalyst-engagement-intake' ),
			'second_review_requested' => __( 'Second Review Requested', 'sustainable-catalyst-engagement-intake' ),
			'changes_requested'       => __( 'Changes Requested', 'sustainable-catalyst-engagement-intake' ),
			'ready_to_finalize'       => __( 'Ready to Finalize', 'sustainable-catalyst-engagement-intake' ),
			'finalized'               => __( 'Finalized', 'sustainable-catalyst-engagement-intake' ),
			'superseded'              => __( 'Superseded', 'sustainable-catalyst-engagement-intake' ),
			'withdrawn'               => __( 'Withdrawn', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function inquiry_statuses(): array {
		return array(
			'not_started'       => __( 'Not Started', 'sustainable-catalyst-engagement-intake' ),
			'draft'             => __( 'Draft', 'sustainable-catalyst-engagement-intake' ),
			'under_review'      => __( 'Under Review', 'sustainable-catalyst-engagement-intake' ),
			'second_review'     => __( 'Second Review', 'sustainable-catalyst-engagement-intake' ),
			'ready_to_finalize' => __( 'Ready to Finalize', 'sustainable-catalyst-engagement-intake' ),
			'finalized'         => __( 'Finalized', 'sustainable-catalyst-engagement-intake' ),
			'stale'             => __( 'Stale / Reassessment Recommended', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function recommendations(): array {
		return array(
			'undecided'           => __( 'Undecided', 'sustainable-catalyst-engagement-intake' ),
			'strong_fit'          => __( 'Strong Fit', 'sustainable-catalyst-engagement-intake' ),
			'possible_fit'        => __( 'Possible Fit', 'sustainable-catalyst-engagement-intake' ),
			'conditional_fit'     => __( 'Conditional Fit', 'sustainable-catalyst-engagement-intake' ),
			'needs_clarification' => __( 'Needs Clarification', 'sustainable-catalyst-engagement-intake' ),
			'limited_fit'         => __( 'Limited Fit', 'sustainable-catalyst-engagement-intake' ),
			'referral_candidate'  => __( 'Referral Candidate', 'sustainable-catalyst-engagement-intake' ),
			'not_a_fit'           => __( 'Not a Fit', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function confidence_levels(): array {
		return array(
			'unassessed' => __( 'Not Assessed', 'sustainable-catalyst-engagement-intake' ),
			'low'        => __( 'Low Confidence', 'sustainable-catalyst-engagement-intake' ),
			'moderate'   => __( 'Moderate Confidence', 'sustainable-catalyst-engagement-intake' ),
			'high'       => __( 'High Confidence', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function service_routes(): array {
		return array(
			'continue_review'                   => __( 'Continue Review', 'sustainable-catalyst-engagement-intake' ),
			'request_information'               => __( 'Request More Information', 'sustainable-catalyst-engagement-intake' ),
			'free_fit_call'                     => __( 'Free 20-Minute Fit Call', 'sustainable-catalyst-engagement-intake' ),
			'paid_consultation'                 => __( 'Paid Consultation', 'sustainable-catalyst-engagement-intake' ),
			'evidence_claims_audit'             => __( 'Evidence and Claims Audit', 'sustainable-catalyst-engagement-intake' ),
			'evidence_systems_diagnostic'       => __( 'Evidence Systems Diagnostic', 'sustainable-catalyst-engagement-intake' ),
			'knowledge_architecture'            => __( 'Knowledge Architecture', 'sustainable-catalyst-engagement-intake' ),
			'technical_storytelling'            => __( 'Technical Storytelling or Product Dossier', 'sustainable-catalyst-engagement-intake' ),
			'measurement_indicator_design'      => __( 'Measurement and Indicator Design', 'sustainable-catalyst-engagement-intake' ),
			'decision_dossier_systems_analysis' => __( 'Decision Dossier or Systems Analysis', 'sustainable-catalyst-engagement-intake' ),
			'responsible_ai_workflow'           => __( 'Responsible AI or Knowledge Workflow', 'sustainable-catalyst-engagement-intake' ),
			'strategy_sprint'                   => __( 'Strategy Sprint', 'sustainable-catalyst-engagement-intake' ),
			'workshop_training'                 => __( 'Workshop or Training', 'sustainable-catalyst-engagement-intake' ),
			'advisory_retainer'                 => __( 'Monthly Advisory Retainer', 'sustainable-catalyst-engagement-intake' ),
			'institutional_partnership'         => __( 'Institutional Partnership Discussion', 'sustainable-catalyst-engagement-intake' ),
			'referral'                          => __( 'Referral', 'sustainable-catalyst-engagement-intake' ),
			'not_yet'                           => __( 'Not Yet — Revisit Later', 'sustainable-catalyst-engagement-intake' ),
			'decline'                           => __( 'Decline', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function scope_boundaries(): array {
		return array(
			'within_scope'          => __( 'Within Scope', 'sustainable-catalyst-engagement-intake' ),
			'needs_reframing'       => __( 'Potentially In Scope After Reframing', 'sustainable-catalyst-engagement-intake' ),
			'capacity_constraint'   => __( 'Capacity Constraint', 'sustainable-catalyst-engagement-intake' ),
			'budget_mismatch'       => __( 'Budget or Resource Mismatch', 'sustainable-catalyst-engagement-intake' ),
			'timing_mismatch'       => __( 'Timing Mismatch', 'sustainable-catalyst-engagement-intake' ),
			'conflict_or_independence' => __( 'Conflict or Independence Concern', 'sustainable-catalyst-engagement-intake' ),
			'legal_regulatory'      => __( 'Requires Legal or Regulatory Expertise', 'sustainable-catalyst-engagement-intake' ),
			'medical_clinical'      => __( 'Requires Medical or Clinical Expertise', 'sustainable-catalyst-engagement-intake' ),
			'unsafe_or_prohibited'  => __( 'Unsafe, Prohibited, or Inappropriate Scope', 'sustainable-catalyst-engagement-intake' ),
			'outside_scope'         => __( 'Outside Scope', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function assistance_disclosures(): array {
		return array(
			'none'              => __( 'No Automated or AI Assistance Used', 'sustainable-catalyst-engagement-intake' ),
			'clerical_only'     => __( 'Clerical Assistance Only', 'sustainable-catalyst-engagement-intake' ),
			'summarization'     => __( 'Summarization Assistance Used', 'sustainable-catalyst-engagement-intake' ),
			'analysis_support'  => __( 'Analytical Support Used; Human Judgment Retained', 'sustainable-catalyst-engagement-intake' ),
			'other_disclosed'   => __( 'Other Assistance Disclosed', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function ratings(): array {
		return array(
			'not_assessed' => array( 'label' => __( 'Not Assessed', 'sustainable-catalyst-engagement-intake' ), 'value' => null ),
			'strong_concern'=> array( 'label' => __( 'Strong Concern', 'sustainable-catalyst-engagement-intake' ), 'value' => 0 ),
			'weak'         => array( 'label' => __( 'Weak', 'sustainable-catalyst-engagement-intake' ), 'value' => 1 ),
			'partial'      => array( 'label' => __( 'Partial', 'sustainable-catalyst-engagement-intake' ), 'value' => 2 ),
			'good'         => array( 'label' => __( 'Good', 'sustainable-catalyst-engagement-intake' ), 'value' => 3 ),
			'strong'       => array( 'label' => __( 'Strong', 'sustainable-catalyst-engagement-intake' ), 'value' => 4 ),
			'not_applicable'=> array( 'label' => __( 'Not Applicable', 'sustainable-catalyst-engagement-intake' ), 'value' => null ),
		);
	}

	public static function criterion_groups(): array {
		return array(
			'alignment'    => __( 'Mission and Service Alignment', 'sustainable-catalyst-engagement-intake' ),
			'clarity'      => __( 'Problem and Outcome Clarity', 'sustainable-catalyst-engagement-intake' ),
			'readiness'    => __( 'Evidence and Engagement Readiness', 'sustainable-catalyst-engagement-intake' ),
			'feasibility'  => __( 'Feasibility and Delivery Conditions', 'sustainable-catalyst-engagement-intake' ),
			'ethics_risk'  => __( 'Ethics, Independence, Privacy, and Risk', 'sustainable-catalyst-engagement-intake' ),
			'impact'       => __( 'Learning, Measurement, and Public Value', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function criteria(): array {
		return apply_filters(
			'sc_ei_fit_criteria',
			array(
				'mission_alignment' => array(
					'group' => 'alignment',
					'label'  => __( 'Mission and values alignment', 'sustainable-catalyst-engagement-intake' ),
					'description' => __( 'The work aligns with ethical strategy, systems thinking, public-interest knowledge, sustainability, responsible technology, or adjacent institutional goals.', 'sustainable-catalyst-engagement-intake' ),
					'weight' => 1.25,
				),
				'service_alignment' => array(
					'group' => 'alignment',
					'label'  => __( 'Service and capability alignment', 'sustainable-catalyst-engagement-intake' ),
					'description' => __( 'The request maps to a service Sustainable Catalyst can competently deliver or responsibly coordinate.', 'sustainable-catalyst-engagement-intake' ),
					'weight' => 1.50,
				),
				'problem_clarity' => array(
					'group' => 'clarity',
					'label'  => __( 'Problem clarity', 'sustainable-catalyst-engagement-intake' ),
					'description' => __( 'The underlying problem, decision, knowledge gap, or system challenge is sufficiently understood.', 'sustainable-catalyst-engagement-intake' ),
					'weight' => 1.00,
				),
				'outcome_clarity' => array(
					'group' => 'clarity',
					'label'  => __( 'Outcome and success clarity', 'sustainable-catalyst-engagement-intake' ),
					'description' => __( 'Desired outcomes, decisions, audiences, deliverables, or measures of success are sufficiently explicit.', 'sustainable-catalyst-engagement-intake' ),
					'weight' => 1.00,
				),
				'evidence_readiness' => array(
					'group' => 'readiness',
					'label'  => __( 'Evidence and information readiness', 'sustainable-catalyst-engagement-intake' ),
					'description' => __( 'Relevant records, sources, stakeholders, data, or documentation are available or can reasonably be developed.', 'sustainable-catalyst-engagement-intake' ),
					'weight' => 1.15,
				),
				'stakeholder_readiness' => array(
					'group' => 'readiness',
					'label'  => __( 'Stakeholder readiness', 'sustainable-catalyst-engagement-intake' ),
					'description' => __( 'Required sponsors, subject-matter experts, decision makers, users, or affected communities can participate appropriately.', 'sustainable-catalyst-engagement-intake' ),
					'weight' => 1.00,
				),
				'decision_authority' => array(
					'group' => 'readiness',
					'label'  => __( 'Decision authority and sponsorship', 'sustainable-catalyst-engagement-intake' ),
					'description' => __( 'The inquiry has an appropriate sponsor, owner, or path to decisions and implementation.', 'sustainable-catalyst-engagement-intake' ),
					'weight' => 1.00,
				),
				'budget_feasibility' => array(
					'group' => 'feasibility',
					'label'  => __( 'Budget and resource feasibility', 'sustainable-catalyst-engagement-intake' ),
					'description' => __( 'The available budget, staffing, access, and internal capacity are proportionate to the expected work.', 'sustainable-catalyst-engagement-intake' ),
					'weight' => 1.00,
				),
				'timing_feasibility' => array(
					'group' => 'feasibility',
					'label'  => __( 'Timing and delivery feasibility', 'sustainable-catalyst-engagement-intake' ),
					'description' => __( 'The schedule, dependencies, urgency, and review cycles are realistic.', 'sustainable-catalyst-engagement-intake' ),
					'weight' => 1.00,
				),
				'implementation_readiness' => array(
					'group' => 'feasibility',
					'label'  => __( 'Implementation readiness', 'sustainable-catalyst-engagement-intake' ),
					'description' => __( 'There is a credible path from analysis or strategy into decisions, adoption, testing, or implementation.', 'sustainable-catalyst-engagement-intake' ),
					'weight' => 0.90,
				),
				'ethics_public_interest' => array(
					'group' => 'ethics_risk',
					'label'  => __( 'Ethics and public-interest compatibility', 'sustainable-catalyst-engagement-intake' ),
					'description' => __( 'The purpose, methods, stakeholders, and likely impacts are compatible with responsible and public-interest practice.', 'sustainable-catalyst-engagement-intake' ),
					'weight' => 1.50,
				),
				'privacy_confidentiality' => array(
					'group' => 'ethics_risk',
					'label'  => __( 'Privacy, confidentiality, and data-handling feasibility', 'sustainable-catalyst-engagement-intake' ),
					'description' => __( 'The work can be performed with appropriate privacy, confidentiality, security, consent, and retention controls.', 'sustainable-catalyst-engagement-intake' ),
					'weight' => 1.25,
				),
				'conflict_independence' => array(
					'group' => 'ethics_risk',
					'label'  => __( 'Conflict and independence compatibility', 'sustainable-catalyst-engagement-intake' ),
					'description' => __( 'The work does not create an unmanaged conflict, misleading appearance of independence, or inappropriate advocacy role.', 'sustainable-catalyst-engagement-intake' ),
					'weight' => 1.50,
				),
				'risk_manageability' => array(
					'group' => 'ethics_risk',
					'label'  => __( 'Risk manageability', 'sustainable-catalyst-engagement-intake' ),
					'description' => __( 'Legal, safety, reputational, technical, political, financial, and implementation risks can be responsibly bounded.', 'sustainable-catalyst-engagement-intake' ),
					'weight' => 1.25,
				),
				'measurement_readiness' => array(
					'group' => 'impact',
					'label'  => __( 'Measurement and learning readiness', 'sustainable-catalyst-engagement-intake' ),
					'description' => __( 'The engagement can define evidence, indicators, learning questions, validation, or evaluation appropriate to its purpose.', 'sustainable-catalyst-engagement-intake' ),
					'weight' => 0.90,
				),
				'public_value' => array(
					'group' => 'impact',
					'label'  => __( 'Potential durable value', 'sustainable-catalyst-engagement-intake' ),
					'description' => __( 'The work has credible potential to improve decisions, knowledge systems, public understanding, institutional capacity, or responsible outcomes.', 'sustainable-catalyst-engagement-intake' ),
					'weight' => 1.00,
				),
			)
		);
	}

	public static function second_review_dispositions(): array {
		return array(
			'not_requested'      => __( 'Not Requested', 'sustainable-catalyst-engagement-intake' ),
			'agree'              => __( 'Agree', 'sustainable-catalyst-engagement-intake' ),
			'agree_with_changes' => __( 'Agree with Required Changes', 'sustainable-catalyst-engagement-intake' ),
			'disagree'           => __( 'Disagree', 'sustainable-catalyst-engagement-intake' ),
			'escalate'           => __( 'Escalate for Additional Review', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function default_settings(): array {
		return array(
			'fit_assessment_enabled'                  => 1,
			'fit_advisory_score_enabled'              => 1,
			'fit_require_human_attestation'           => 1,
			'fit_require_evidence_for_assessed_items' => 1,
			'fit_require_rationale_for_finalization'  => 1,
			'fit_require_second_review_high_risk'     => 1,
			'fit_require_second_review_conflict'      => 1,
			'fit_require_second_review_decline'       => 1,
			'fit_require_second_review_unsafe_scope'  => 1,
			'fit_distinct_second_reviewer'            => 1,
			'fit_assessment_stale_days'               => 30,
			'fit_assessment_queue_limit'              => 100,
		);
	}

	public static function sanitize_choice( string $value, array $options, string $fallback ): string {
		$value = sanitize_key( $value );
		return array_key_exists( $value, $options ) ? $value : $fallback;
	}

	public static function sanitize_rating( string $value ): string {
		return self::sanitize_choice( $value, self::ratings(), 'not_assessed' );
	}

	public static function rating_label( string $value ): string {
		$ratings = self::ratings();
		return $ratings[ $value ]['label'] ?? ucwords( str_replace( '_', ' ', $value ) );
	}

	public static function rating_value( string $value ): ?float {
		$ratings = self::ratings();
		return array_key_exists( $value, $ratings ) ? $ratings[ $value ]['value'] : null;
	}

	public static function calculate_score( array $items ): array {
		$criteria = self::criteria();
		$weighted = 0.0;
		$possible = 0.0;
		$assessed = 0;
		$applicable = 0;
		$material = 0;

		foreach ( $criteria as $key => $definition ) {
			$item = $items[ $key ] ?? array();
			$rating = self::sanitize_rating( (string) ( $item['rating'] ?? 'not_assessed' ) );
			$is_applicable = 'not_applicable' !== $rating && ! empty( $item['is_applicable'] ?? 1 );
			if ( ! $is_applicable ) {
				continue;
			}
			$applicable++;
			if ( ! empty( $item['is_material_concern'] ) ) {
				$material++;
			}
			$value = self::rating_value( $rating );
			if ( null === $value ) {
				continue;
			}
			$weight = max( 0.1, (float) ( $definition['weight'] ?? 1.0 ) );
			$weighted += $value * $weight;
			$possible += 4 * $weight;
			$assessed++;
		}

		return array(
			'score'            => $possible > 0 ? round( ( $weighted / $possible ) * 100, 2 ) : null,
			'score_complete'   => $applicable > 0 && $assessed === $applicable,
			'assessed_count'   => $assessed,
			'applicable_count' => $applicable,
			'material_concerns'=> $material,
		);
	}

	public static function second_review_reasons(
		string $recommendation,
		string $scope_boundary,
		array $items,
		array $settings
	): array {
		$reasons = array();
		if ( ! empty( $settings['fit_require_second_review_decline'] ) && 'not_a_fit' === $recommendation ) {
			$reasons[] = __( 'A not-a-fit recommendation requires a second human review.', 'sustainable-catalyst-engagement-intake' );
		}
		if ( ! empty( $settings['fit_require_second_review_conflict'] ) && 'conflict_or_independence' === $scope_boundary ) {
			$reasons[] = __( 'A conflict or independence concern requires a second human review.', 'sustainable-catalyst-engagement-intake' );
		}
		if ( ! empty( $settings['fit_require_second_review_unsafe_scope'] ) && 'unsafe_or_prohibited' === $scope_boundary ) {
			$reasons[] = __( 'Unsafe, prohibited, or inappropriate scope requires a second human review.', 'sustainable-catalyst-engagement-intake' );
		}
		if ( ! empty( $settings['fit_require_second_review_high_risk'] ) ) {
			foreach ( $items as $key => $item ) {
				if (
					! empty( $item['is_material_concern'] )
					&& in_array( $key, array( 'ethics_public_interest', 'privacy_confidentiality', 'conflict_independence', 'risk_manageability' ), true )
				) {
					$reasons[] = __( 'A material ethics, privacy, independence, or risk concern requires a second human review.', 'sustainable-catalyst-engagement-intake' );
					break;
				}
			}
		}
		return array_values( array_unique( $reasons ) );
	}

	public static function map_to_review( string $recommendation, string $service_route ): array {
		$fit_decision = match ( $recommendation ) {
			'strong_fit'          => 'strong_fit',
			'possible_fit',
			'conditional_fit'     => 'possible_fit',
			'needs_clarification' => 'needs_clarification',
			'limited_fit'         => 'limited_fit',
			'referral_candidate'  => 'referral_candidate',
			'not_a_fit'           => 'not_a_fit',
			default               => 'undecided',
		};

		$next_step = match ( $service_route ) {
			'request_information'               => 'request_information',
			'free_fit_call'                     => 'fit_call',
			'paid_consultation'                 => 'paid_consultation',
			'evidence_claims_audit'             => 'evidence_claims_audit',
			'evidence_systems_diagnostic'       => 'evidence_systems_diagnostic',
			'knowledge_architecture'            => 'knowledge_architecture',
			'technical_storytelling'            => 'technical_storytelling',
			'measurement_indicator_design'      => 'measurement_indicator_design',
			'decision_dossier_systems_analysis' => 'decision_dossier_systems_analysis',
			'responsible_ai_workflow'           => 'responsible_ai_workflows',
			'strategy_sprint'                   => 'strategy_sprint',
			'workshop_training'                 => 'workshop_training',
			'advisory_retainer'                 => 'monthly_advisory',
			'institutional_partnership'         => 'institutional_partnership',
			'referral'                          => 'referral',
			'decline'                           => 'decline',
			default                             => 'review',
		};

		return array(
			'fit_decision' => $fit_decision,
			'recommended_next_step' => $next_step,
		);
	}

	public static function label( array $options, string $value ): string {
		return $options[ $value ] ?? ucwords( str_replace( '_', ' ', $value ) );
	}
}
