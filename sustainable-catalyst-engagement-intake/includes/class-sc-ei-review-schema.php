<?php
/**
 * Human-authored administrative review definitions and timing helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Review_Schema {

	public static function stages(): array {
		return apply_filters(
			'sc_ei_review_stages',
			array(
				'intake'              => __( 'Intake Received', 'sustainable-catalyst-engagement-intake' ),
				'triage'              => __( 'Triage', 'sustainable-catalyst-engagement-intake' ),
				'substantive_review'  => __( 'Substantive Review', 'sustainable-catalyst-engagement-intake' ),
				'awaiting_information'=> __( 'Awaiting Information', 'sustainable-catalyst-engagement-intake' ),
				'decision_ready'      => __( 'Decision Ready', 'sustainable-catalyst-engagement-intake' ),
				'handoff_ready'       => __( 'Handoff Ready', 'sustainable-catalyst-engagement-intake' ),
				'completed'           => __( 'Review Completed', 'sustainable-catalyst-engagement-intake' ),
			)
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

	public static function fit_decisions(): array {
		return array(
			'undecided'           => __( 'Undecided', 'sustainable-catalyst-engagement-intake' ),
			'strong_fit'          => __( 'Strong Fit', 'sustainable-catalyst-engagement-intake' ),
			'possible_fit'        => __( 'Possible Fit', 'sustainable-catalyst-engagement-intake' ),
			'needs_clarification' => __( 'Needs Clarification', 'sustainable-catalyst-engagement-intake' ),
			'limited_fit'         => __( 'Limited Fit', 'sustainable-catalyst-engagement-intake' ),
			'not_a_fit'           => __( 'Not a Fit', 'sustainable-catalyst-engagement-intake' ),
			'referral_candidate'  => __( 'Referral Candidate', 'sustainable-catalyst-engagement-intake' ),
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

	public static function risk_levels(): array {
		return array(
			'unassessed' => __( 'Not Assessed', 'sustainable-catalyst-engagement-intake' ),
			'low'        => __( 'Low', 'sustainable-catalyst-engagement-intake' ),
			'moderate'   => __( 'Moderate', 'sustainable-catalyst-engagement-intake' ),
			'high'       => __( 'High', 'sustainable-catalyst-engagement-intake' ),
			'critical'   => __( 'Critical', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function evidence_readiness_levels(): array {
		return array(
			'not_assessed' => __( 'Not Assessed', 'sustainable-catalyst-engagement-intake' ),
			'insufficient' => __( 'Insufficient', 'sustainable-catalyst-engagement-intake' ),
			'partial'      => __( 'Partial', 'sustainable-catalyst-engagement-intake' ),
			'adequate'     => __( 'Adequate', 'sustainable-catalyst-engagement-intake' ),
			'strong'       => __( 'Strong', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function scope_clarity_levels(): array {
		return array(
			'not_assessed' => __( 'Not Assessed', 'sustainable-catalyst-engagement-intake' ),
			'unclear'      => __( 'Unclear', 'sustainable-catalyst-engagement-intake' ),
			'emerging'     => __( 'Emerging', 'sustainable-catalyst-engagement-intake' ),
			'clear'        => __( 'Clear', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function next_steps(): array {
		return array(
			'review'                      => __( 'Continue Review', 'sustainable-catalyst-engagement-intake' ),
			'request_information'         => __( 'Request More Information', 'sustainable-catalyst-engagement-intake' ),
			'fit_call'                    => __( 'Recommend Free Fit Call', 'sustainable-catalyst-engagement-intake' ),
			'paid_consultation'           => __( 'Recommend Paid Consultation', 'sustainable-catalyst-engagement-intake' ),
			'evidence_systems_diagnostic' => __( 'Evidence Systems Diagnostic', 'sustainable-catalyst-engagement-intake' ),
			'knowledge_architecture'      => __( 'Knowledge Architecture Engagement', 'sustainable-catalyst-engagement-intake' ),
			'technical_storytelling'      => __( 'Technical Storytelling Engagement', 'sustainable-catalyst-engagement-intake' ),
			'responsible_ai_workflows'    => __( 'Responsible AI Workflow Engagement', 'sustainable-catalyst-engagement-intake' ),
			'strategy_sprint'             => __( 'Strategy Sprint', 'sustainable-catalyst-engagement-intake' ),
			'workshop_training'           => __( 'Workshop or Training', 'sustainable-catalyst-engagement-intake' ),
			'monthly_advisory'            => __( 'Monthly Advisory', 'sustainable-catalyst-engagement-intake' ),
			'proposal'                    => __( 'Prepare Proposal', 'sustainable-catalyst-engagement-intake' ),
			'institutional_partnership'   => __( 'Institutional Partnership Discussion', 'sustainable-catalyst-engagement-intake' ),
			'referral'                    => __( 'Referral', 'sustainable-catalyst-engagement-intake' ),
			'decline'                     => __( 'Decline', 'sustainable-catalyst-engagement-intake' ),
			'internal_escalation'         => __( 'Internal Escalation', 'sustainable-catalyst-engagement-intake' ),
			'close'                       => __( 'Close Review', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function escalation_statuses(): array {
		return array(
			'none'         => __( 'No Escalation', 'sustainable-catalyst-engagement-intake' ),
			'requested'    => __( 'Escalation Requested', 'sustainable-catalyst-engagement-intake' ),
			'under_review' => __( 'Escalation Under Review', 'sustainable-catalyst-engagement-intake' ),
			'resolved'     => __( 'Escalation Resolved', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function checklist_items(): array {
		return array(
			'contact_verified'    => __( 'Contact details and organizational context reviewed', 'sustainable-catalyst-engagement-intake' ),
			'purpose_understood'  => __( 'Purpose and desired outcome understood', 'sustainable-catalyst-engagement-intake' ),
			'scope_reviewed'      => __( 'Scope and service alignment reviewed', 'sustainable-catalyst-engagement-intake' ),
			'budget_reviewed'     => __( 'Budget or resource expectations reviewed', 'sustainable-catalyst-engagement-intake' ),
			'timing_reviewed'     => __( 'Timing, deadline, and availability reviewed', 'sustainable-catalyst-engagement-intake' ),
			'documents_reviewed'  => __( 'Document state and evidence readiness reviewed', 'sustainable-catalyst-engagement-intake' ),
			'privacy_reviewed'    => __( 'Privacy, confidentiality, and retention considerations reviewed', 'sustainable-catalyst-engagement-intake' ),
			'conflict_checked'    => __( 'Conflict, independence, and reputational considerations checked', 'sustainable-catalyst-engagement-intake' ),
			'rationale_recorded'  => __( 'Decision rationale and next step recorded', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function sanitize_choice( string $value, array $options, string $default ): string {
		$value = sanitize_key( $value );
		return array_key_exists( $value, $options ) ? $value : $default;
	}

	public static function sanitize_checklist( $value ): array {
		$value  = is_array( $value ) ? $value : array();
		$result = array();

		foreach ( self::checklist_items() as $key => $label ) {
			$result[ $key ] = empty( $value[ $key ] ) ? 0 : 1;
		}

		return $result;
	}

	public static function checklist_progress( $value ): array {
		if ( is_string( $value ) ) {
			$value = json_decode( $value, true );
		}
		$value = self::sanitize_checklist( $value );
		$total = count( $value );
		$done  = array_sum( $value );

		return array(
			'items'   => $value,
			'done'    => $done,
			'total'   => $total,
			'percent' => $total ? (int) round( ( $done / $total ) * 100 ) : 0,
			'complete'=> $total > 0 && $done === $total,
		);
	}

	public static function default_due_at( string $priority = 'normal', ?array $settings = null ): string {
		$settings = $settings ?: wp_parse_args( get_option( 'sc_ei_settings', array() ), self::default_review_settings() );
		$priority = self::sanitize_choice( $priority, self::priorities(), 'normal' );

		$seconds = match ( $priority ) {
			'urgent' => max( 1, absint( $settings['urgent_review_due_hours'] ?? 4 ) ) * HOUR_IN_SECONDS,
			'high'   => max( 1, absint( $settings['high_priority_review_due_days'] ?? 1 ) ) * DAY_IN_SECONDS,
			'low'    => max( 1, absint( $settings['low_priority_review_due_days'] ?? 7 ) ) * DAY_IN_SECONDS,
			default  => max( 1, absint( $settings['default_review_due_days'] ?? 3 ) ) * DAY_IN_SECONDS,
		};

		return gmdate( 'Y-m-d H:i:s', time() + $seconds );
	}

	public static function default_review_settings(): array {
		return array(
			'default_review_due_days'       => 3,
			'high_priority_review_due_days' => 1,
			'low_priority_review_due_days'  => 7,
			'urgent_review_due_hours'       => 4,
			'stale_review_days'              => 7,
			'review_bulk_limit'              => 50,
			'reviewer_self_assignment'       => 1,
			'require_review_rationale'       => 1,
			'require_completion_checklist'   => 1,
		);
	}

	public static function timing( array $inquiry, ?array $settings = null ): array {
		$settings = $settings ?: wp_parse_args( get_option( 'sc_ei_settings', array() ), self::default_review_settings() );
		$now      = time();
		$created  = strtotime( (string) ( $inquiry['created_at'] ?? '' ) . ' UTC' ) ?: $now;
		$last_reviewed = ! empty( $inquiry['last_reviewed_at'] )
			? (string) $inquiry['last_reviewed_at']
			: (string) ( $inquiry['updated_at'] ?? '' );
		$updated  = strtotime( $last_reviewed . ' UTC' ) ?: $created;
		$due      = ! empty( $inquiry['review_due_at'] ) ? strtotime( $inquiry['review_due_at'] . ' UTC' ) : 0;
		$completed= 'completed' === (string) ( $inquiry['review_stage'] ?? '' );
		$age_hours= max( 0, (int) floor( ( $now - $created ) / HOUR_IN_SECONDS ) );
		$idle_days= max( 0, (int) floor( ( $now - $updated ) / DAY_IN_SECONDS ) );

		$due_state = 'no_due';
		if ( $completed ) {
			$due_state = 'completed';
		} elseif ( $due ) {
			if ( $due < $now ) {
				$due_state = 'overdue';
			} elseif ( $due <= $now + DAY_IN_SECONDS ) {
				$due_state = 'due_soon';
			} else {
				$due_state = 'on_track';
			}
		}

		return array(
			'age_hours'     => $age_hours,
			'age_days'      => (int) floor( $age_hours / 24 ),
			'idle_days'     => $idle_days,
			'is_stale'      => ! $completed && $idle_days >= max( 1, absint( $settings['stale_review_days'] ?? 7 ) ),
			'due_timestamp' => $due,
			'due_state'     => $due_state,
			'overdue_hours' => 'overdue' === $due_state ? max( 0, (int) floor( ( $now - $due ) / HOUR_IN_SECONDS ) ) : 0,
		);
	}

	public static function label( array $options, string $value ): string {
		return $options[ $value ] ?? ucwords( str_replace( '_', ' ', $value ) );
	}

	public static function reviewers(): array {
		$query = new WP_User_Query(
			array(
				'capability' => 'sc_intake_review',
				'orderby'    => 'display_name',
				'order'      => 'ASC',
				'fields'     => array( 'ID', 'display_name', 'user_email' ),
				'number'     => 250,
			)
		);

		return (array) $query->get_results();
	}
}
