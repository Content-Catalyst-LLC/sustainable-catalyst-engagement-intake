<?php
/**
 * Human-controlled fit assessment persistence and workflow.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Fit_Repository {

	public static function current_for_inquiry( int $inquiry_id ): ?array {
		global $wpdb;

		$table = SC_EI_Database::table( 'fit_assessments' );
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE inquiry_id = %d
					AND status <> 'superseded'
				ORDER BY assessment_version DESC, id DESC
				LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$inquiry_id
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}
		$row['items'] = self::items( absint( $row['id'] ) );
		$row['second_reviews'] = self::second_reviews( absint( $row['id'] ) );
		return $row;
	}

	public static function for_inquiry( int $inquiry_id, bool $include_details = true ): array {
		global $wpdb;

		$table = SC_EI_Database::table( 'fit_assessments' );
		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE inquiry_id = %d
				ORDER BY assessment_version ASC, id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$inquiry_id
			),
			ARRAY_A
		);
		if ( ! $include_details ) {
			return $rows;
		}
		foreach ( $rows as &$row ) {
			$row['items'] = self::items( absint( $row['id'] ) );
			$row['second_reviews'] = self::second_reviews( absint( $row['id'] ) );
		}
		unset( $row );
		return $rows;
	}

	public static function find( int $assessment_id ): ?array {
		global $wpdb;

		$table = SC_EI_Database::table( 'fit_assessments' );
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $assessment_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
		if ( ! $row ) {
			return null;
		}
		$row['items'] = self::items( $assessment_id );
		$row['second_reviews'] = self::second_reviews( $assessment_id );
		return $row;
	}

	public static function create_draft( int $inquiry_id, int $actor_user_id ) {
		global $wpdb;

		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $inquiry ) {
			return new WP_Error( 'fit_inquiry_not_found', __( 'The inquiry could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$current = self::current_for_inquiry( $inquiry_id );
		if ( $current && ! in_array( $current['status'], array( 'finalized', 'withdrawn', 'superseded' ), true ) ) {
			return $current;
		}

		$table = SC_EI_Database::table( 'fit_assessments' );
		$version = 1 + (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(MAX(assessment_version),0) FROM {$table} WHERE inquiry_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$inquiry_id
			)
		);
		$now = current_time( 'mysql', true );
		$data = array(
			'public_id'                  => wp_generate_uuid4(),
			'inquiry_id'                 => $inquiry_id,
			'assessment_version'         => $version,
			'parent_assessment_id'       => $current ? absint( $current['id'] ) : null,
			'assessor_user_id'           => $actor_user_id ?: null,
			'status'                     => 'draft',
			'recommendation'             => 'undecided',
			'confidence'                 => 'unassessed',
			'service_route'              => 'continue_review',
			'scope_boundary'             => 'within_scope',
			'advisory_score'             => null,
			'score_complete'             => 0,
			'material_concern_count'     => 0,
			'second_review_required'     => 0,
			'second_review_reason'       => '',
			'second_reviewer_user_id'    => null,
			'second_review_disposition'  => 'not_requested',
			'second_reviewed_at'         => null,
			'overall_summary'            => '',
			'recommendation_rationale'   => '',
			'limitations_notes'          => '',
			'conditions_for_fit'         => '',
			'referral_notes'             => '',
			'human_attestation'          => 0,
			'assistance_disclosure'      => 'none',
			'assistance_notes'           => '',
			'submitted_at'               => null,
			'finalized_by'               => null,
			'finalized_at'               => null,
			'superseded_at'              => null,
			'row_version'                => 0,
			'created_at'                 => $now,
			'updated_at'                 => $now,
		);
		$wpdb->query( 'START TRANSACTION' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ( false === $wpdb->insert( $table, $data, self::formats( $data, self::assessment_integer_fields() ) ) ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			return new WP_Error( 'fit_create_failed', __( 'The fit assessment draft could not be created.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$assessment_id = (int) $wpdb->insert_id;
		$seeded = self::seed_items( $assessment_id );
		if ( is_wp_error( $seeded ) ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			return $seeded;
		}

		$inquiry_updated = $wpdb->update(
			SC_EI_Database::table( 'inquiries' ),
			array(
				'fit_assessment_status'     => 'draft',
				'current_fit_assessment_id' => $assessment_id,
				'fit_assessment_updated_at' => $now,
				'fit_assessment_version'    => absint( $inquiry['fit_assessment_version'] ?? 0 ) + 1,
				'updated_at'                => $now,
			),
			array( 'id' => $inquiry_id ),
			array( '%s', '%d', '%s', '%d', '%s' ),
			array( '%d' )
		);
		if ( false === $inquiry_updated ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			return new WP_Error( 'fit_inquiry_state_failed', __( 'The inquiry fit state could not be initialized.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		SC_EI_Audit_Log::record(
			'fit_assessment_created',
			'Human-controlled fit assessment draft created.',
			array(
				'assessment_id'      => $assessment_id,
				'assessment_version' => $version,
				'fit_schema_version' => SC_EI_FIT_SCHEMA_VERSION,
			),
			$inquiry_id,
			null,
			$actor_user_id ?: null
		);

		return self::find( $assessment_id );
	}

	public static function save_draft(
		int $assessment_id,
		array $input,
		int $actor_user_id,
		int $expected_version
	) {
		global $wpdb;

		$current = self::find( $assessment_id );
		if ( ! $current ) {
			return new WP_Error( 'fit_assessment_not_found', __( 'The fit assessment could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if (
			! empty( $current['assessor_user_id'] )
			&& absint( $current['assessor_user_id'] ) !== $actor_user_id
			&& ! current_user_can( 'sc_intake_finalize_fit_assessments' )
		) {
			return new WP_Error( 'fit_assessor_only', __( 'Only the assigned assessor or an authorized manager can edit this assessment.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( ! in_array( $current['status'], array( 'draft', 'changes_requested', 'submitted', 'second_review_requested', 'ready_to_finalize' ), true ) ) {
			return new WP_Error( 'fit_assessment_immutable', __( 'This assessment is no longer editable. Create a new version to reassess the inquiry.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( absint( $current['row_version'] ) !== $expected_version ) {
			return new WP_Error( 'fit_assessment_conflict', __( 'Another reviewer changed this fit assessment. Reload before saving.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Fit_Schema::default_settings() );
		$recommendation = SC_EI_Fit_Schema::sanitize_choice(
			(string) ( $input['recommendation'] ?? $current['recommendation'] ),
			SC_EI_Fit_Schema::recommendations(),
			'undecided'
		);
		$confidence = SC_EI_Fit_Schema::sanitize_choice(
			(string) ( $input['confidence'] ?? $current['confidence'] ),
			SC_EI_Fit_Schema::confidence_levels(),
			'unassessed'
		);
		$service_route = SC_EI_Fit_Schema::sanitize_choice(
			(string) ( $input['service_route'] ?? $current['service_route'] ),
			SC_EI_Fit_Schema::service_routes(),
			'continue_review'
		);
		$scope_boundary = SC_EI_Fit_Schema::sanitize_choice(
			(string) ( $input['scope_boundary'] ?? $current['scope_boundary'] ),
			SC_EI_Fit_Schema::scope_boundaries(),
			'within_scope'
		);
		$assistance = SC_EI_Fit_Schema::sanitize_choice(
			(string) ( $input['assistance_disclosure'] ?? $current['assistance_disclosure'] ),
			SC_EI_Fit_Schema::assistance_disclosures(),
			'none'
		);
		$assistance_notes = sanitize_textarea_field( (string) ( $input['assistance_notes'] ?? $current['assistance_notes'] ) );
		if ( 'none' !== $assistance && '' === trim( $assistance_notes ) ) {
			return new WP_Error( 'fit_assistance_notes_required', __( 'Describe how assistance was used and how human judgment was retained.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$items_input = is_array( $input['items'] ?? null ) ? $input['items'] : array();
		$items = array();

		foreach ( SC_EI_Fit_Schema::criteria() as $key => $criterion ) {
			$submitted = is_array( $items_input[ $key ] ?? null ) ? $items_input[ $key ] : array();
			$rating = SC_EI_Fit_Schema::sanitize_rating( (string) ( $submitted['rating'] ?? 'not_assessed' ) );
			$is_applicable = 'not_applicable' === $rating ? 0 : 1;
			$evidence = sanitize_textarea_field( (string) ( $submitted['evidence_note'] ?? '' ) );
			$concern = sanitize_textarea_field( (string) ( $submitted['concern_note'] ?? '' ) );
			$material = empty( $submitted['is_material_concern'] ) ? 0 : 1;
			$refs = self::sanitize_source_refs( $submitted['source_refs'] ?? '' );

			if (
				! empty( $settings['fit_require_evidence_for_assessed_items'] )
				&& ! in_array( $rating, array( 'not_assessed', 'not_applicable' ), true )
				&& '' === trim( $evidence )
			) {
				return new WP_Error(
					'fit_evidence_required',
					sprintf(
						__( 'Evidence or reasoning is required for “%s.”', 'sustainable-catalyst-engagement-intake' ),
						$criterion['label']
					)
				);
			}
			if ( $material && '' === trim( $concern ) ) {
				return new WP_Error(
					'fit_material_concern_note_required',
					sprintf(
						__( 'A material concern note is required for “%s.”', 'sustainable-catalyst-engagement-intake' ),
						$criterion['label']
					)
				);
			}

			$items[ $key ] = array(
				'criterion_key'       => $key,
				'criterion_group'     => sanitize_key( (string) $criterion['group'] ),
				'rating'              => $rating,
				'weight'              => (float) $criterion['weight'],
				'numeric_value'       => SC_EI_Fit_Schema::rating_value( $rating ),
				'is_applicable'       => $is_applicable,
				'is_material_concern' => $material,
				'evidence_note'       => $evidence,
				'concern_note'        => $concern,
				'source_refs_json'    => wp_json_encode( $refs ),
			);
		}

		$score = SC_EI_Fit_Schema::calculate_score( $items );
		$reasons = SC_EI_Fit_Schema::second_review_reasons(
			$recommendation,
			$scope_boundary,
			$items,
			$settings
		);
		$second_review_required = $reasons ? 1 : 0;
		$now = current_time( 'mysql', true );
		$workflow_reset = 'draft' !== $current['status'];
		$data = array(
			'assessor_user_id'          => absint( $current['assessor_user_id'] ) ?: $actor_user_id ?: null,
			'status'                    => $workflow_reset ? 'draft' : $current['status'],
			'recommendation'            => $recommendation,
			'confidence'                => $confidence,
			'service_route'             => $service_route,
			'scope_boundary'            => $scope_boundary,
			'advisory_score'            => ! empty( $settings['fit_advisory_score_enabled'] ) ? $score['score'] : null,
			'score_complete'            => $score['score_complete'] ? 1 : 0,
			'material_concern_count'    => absint( $score['material_concerns'] ),
			'second_review_required'    => $second_review_required,
			'second_review_reason'      => implode( "\n", $reasons ),
			'second_reviewer_user_id'   => $workflow_reset ? null : ( absint( $current['second_reviewer_user_id'] ) ?: null ),
			'second_review_disposition' => $workflow_reset ? 'not_requested' : $current['second_review_disposition'],
			'second_reviewed_at'        => $workflow_reset ? null : ( $current['second_reviewed_at'] ?: null ),
			'submitted_at'              => $workflow_reset ? null : ( $current['submitted_at'] ?: null ),
			'overall_summary'           => sanitize_textarea_field( (string) ( $input['overall_summary'] ?? $current['overall_summary'] ) ),
			'recommendation_rationale'  => sanitize_textarea_field( (string) ( $input['recommendation_rationale'] ?? $current['recommendation_rationale'] ) ),
			'limitations_notes'         => sanitize_textarea_field( (string) ( $input['limitations_notes'] ?? $current['limitations_notes'] ) ),
			'conditions_for_fit'        => sanitize_textarea_field( (string) ( $input['conditions_for_fit'] ?? $current['conditions_for_fit'] ) ),
			'referral_notes'            => sanitize_textarea_field( (string) ( $input['referral_notes'] ?? $current['referral_notes'] ) ),
			'human_attestation'         => empty( $input['human_attestation'] ) ? 0 : 1,
			'assistance_disclosure'     => $assistance,
			'assistance_notes'          => $assistance_notes,
			'row_version'               => $expected_version + 1,
			'updated_at'                => $now,
		);

		$wpdb->query( 'START TRANSACTION' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$updated = $wpdb->update(
			SC_EI_Database::table( 'fit_assessments' ),
			$data,
			array( 'id' => $assessment_id, 'row_version' => $expected_version ),
			self::formats( $data, self::assessment_integer_fields() ),
			array( '%d', '%d' )
		);
		if ( 1 !== $updated ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			return new WP_Error( 'fit_assessment_conflict', __( 'The fit assessment changed before it could be saved.', 'sustainable-catalyst-engagement-intake' ) );
		}

		foreach ( $items as $key => $item ) {
			$result = self::upsert_item( $assessment_id, $item );
			if ( is_wp_error( $result ) ) {
				$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				return $result;
			}
		}

		$inquiry_status = self::inquiry_status_for_assessment( array_merge( $current, $data ) );
		$inquiry_updated = $wpdb->update(
			SC_EI_Database::table( 'inquiries' ),
			array(
				'fit_assessment_status'     => $inquiry_status,
				'current_fit_assessment_id' => $assessment_id,
				'fit_assessment_updated_at' => $now,
				'fit_assessment_version'    => absint( $current['assessment_version'] ),
				'updated_at'                => $now,
			),
			array( 'id' => absint( $current['inquiry_id'] ) ),
			array( '%s', '%d', '%s', '%d', '%s' ),
			array( '%d' )
		);
		if ( false === $inquiry_updated ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			return new WP_Error( 'fit_inquiry_state_failed', __( 'The inquiry fit state could not be updated.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		SC_EI_Audit_Log::record(
			'fit_assessment_saved',
			'Human-authored fit assessment draft saved.',
			array(
				'assessment_id'         => $assessment_id,
				'assessment_version'    => absint( $current['assessment_version'] ),
				'row_version'           => $expected_version + 1,
				'recommendation'        => $recommendation,
				'service_route'         => $service_route,
				'scope_boundary'        => $scope_boundary,
				'advisory_score'        => $data['advisory_score'],
				'score_complete'        => $data['score_complete'],
				'material_concerns'     => $data['material_concern_count'],
				'second_review_required'=> $second_review_required,
				'assistance_disclosure' => $assistance,
				'fit_schema_version'    => SC_EI_FIT_SCHEMA_VERSION,
				'automatic_decision'    => false,
			),
			absint( $current['inquiry_id'] ),
			null,
			$actor_user_id
		);

		return self::find( $assessment_id );
	}

	public static function submit( int $assessment_id, int $actor_user_id, int $expected_version ) {
		$current = self::find( $assessment_id );
		if ( ! $current ) {
			return new WP_Error( 'fit_assessment_not_found', __( 'The fit assessment could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( absint( $current['row_version'] ) !== $expected_version ) {
			return new WP_Error( 'fit_assessment_conflict', __( 'Reload the assessment before submitting it.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if (
			! empty( $current['assessor_user_id'] )
			&& absint( $current['assessor_user_id'] ) !== $actor_user_id
			&& ! current_user_can( 'sc_intake_finalize_fit_assessments' )
		) {
			return new WP_Error( 'fit_assessor_only', __( 'Only the assigned assessor or an authorized manager can submit this assessment.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$validation = self::validate_for_submission( $current );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
		$status = ! empty( $current['second_review_required'] ) ? 'second_review_requested' : 'ready_to_finalize';
		return self::transition(
			$current,
			$status,
			$actor_user_id,
			array(
				'submitted_at' => current_time( 'mysql', true ),
				'second_review_disposition' => ! empty( $current['second_review_required'] ) ? 'not_requested' : $current['second_review_disposition'],
			),
			'fit_assessment_submitted'
		);
	}

	public static function record_second_review(
		int $assessment_id,
		array $input,
		int $actor_user_id
	) {
		global $wpdb;

		$current = self::find( $assessment_id );
		if ( ! $current ) {
			return new WP_Error( 'fit_assessment_not_found', __( 'The fit assessment could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( ! in_array( $current['status'], array( 'submitted', 'second_review_requested', 'changes_requested', 'ready_to_finalize' ), true ) ) {
			return new WP_Error( 'fit_second_review_unavailable', __( 'This assessment is not open for second review.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Fit_Schema::default_settings() );
		if (
			! empty( $settings['fit_distinct_second_reviewer'] )
			&& absint( $current['assessor_user_id'] ) === $actor_user_id
		) {
			return new WP_Error( 'fit_distinct_reviewer_required', __( 'The second reviewer must be different from the original assessor.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$disposition = SC_EI_Fit_Schema::sanitize_choice(
			(string) ( $input['disposition'] ?? 'agree' ),
			SC_EI_Fit_Schema::second_review_dispositions(),
			'agree'
		);
		$recommendation = SC_EI_Fit_Schema::sanitize_choice(
			(string) ( $input['recommendation'] ?? $current['recommendation'] ),
			SC_EI_Fit_Schema::recommendations(),
			$current['recommendation']
		);
		$service_route = SC_EI_Fit_Schema::sanitize_choice(
			(string) ( $input['service_route'] ?? $current['service_route'] ),
			SC_EI_Fit_Schema::service_routes(),
			$current['service_route']
		);
		$scope_boundary = SC_EI_Fit_Schema::sanitize_choice(
			(string) ( $input['scope_boundary'] ?? $current['scope_boundary'] ),
			SC_EI_Fit_Schema::scope_boundaries(),
			$current['scope_boundary']
		);
		$notes = sanitize_textarea_field( (string) ( $input['review_notes'] ?? '' ) );
		$changes = sanitize_textarea_field( (string) ( $input['required_changes'] ?? '' ) );
		$conflict = sanitize_textarea_field( (string) ( $input['conflict_disclosure'] ?? '' ) );
		$attestation = empty( $input['human_attestation'] ) ? 0 : 1;

		if ( ! $attestation ) {
			return new WP_Error( 'fit_second_review_attestation_required', __( 'The second reviewer must attest that they personally reviewed the inquiry and assessment.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( '' === trim( $notes ) ) {
			return new WP_Error( 'fit_second_review_notes_required', __( 'Second-review notes are required.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if (
			'agree' === $disposition
			&& (
				$recommendation !== $current['recommendation']
				|| $service_route !== $current['service_route']
				|| $scope_boundary !== $current['scope_boundary']
			)
		) {
			return new WP_Error( 'fit_second_review_agreement_mismatch', __( 'An “Agree” review must confirm the submitted recommendation, route, and scope boundary. Use “Agree with Required Changes” when proposing changes.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( in_array( $disposition, array( 'agree_with_changes', 'disagree', 'escalate' ), true ) && '' === trim( $changes ) ) {
			return new WP_Error( 'fit_second_review_changes_required', __( 'Required changes or escalation reasons are required for this disposition.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$now = current_time( 'mysql', true );
		$data = array(
			'public_id'           => wp_generate_uuid4(),
			'assessment_id'       => $assessment_id,
			'reviewer_user_id'    => $actor_user_id ?: null,
			'disposition'         => $disposition,
			'recommendation'      => $recommendation,
			'service_route'       => $service_route,
			'scope_boundary'      => $scope_boundary,
			'review_notes'        => $notes,
			'required_changes'    => $changes,
			'conflict_disclosure' => $conflict,
			'human_attestation'   => 1,
			'created_at'          => $now,
		);
		if ( false === $wpdb->insert( SC_EI_Database::table( 'fit_assessment_reviews' ), $data, self::formats( $data, array( 'assessment_id', 'reviewer_user_id', 'human_attestation' ) ) ) ) {
			return new WP_Error( 'fit_second_review_failed', __( 'The second review could not be saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$review_id = (int) $wpdb->insert_id;
		$new_status = in_array( $disposition, array( 'agree', 'agree_with_changes' ), true )
			? ( 'agree' === $disposition ? 'ready_to_finalize' : 'changes_requested' )
			: ( 'disagree' === $disposition ? 'changes_requested' : 'second_review_requested' );

		$update = array(
			'second_reviewer_user_id'   => $actor_user_id,
			'second_review_disposition' => $disposition,
			'second_reviewed_at'        => $now,
			'status'                    => $new_status,
			'row_version'               => absint( $current['row_version'] ) + 1,
			'updated_at'                => $now,
		);
		$wpdb->update(
			SC_EI_Database::table( 'fit_assessments' ),
			$update,
			array( 'id' => $assessment_id ),
			self::formats( $update, array( 'second_reviewer_user_id', 'row_version' ) ),
			array( '%d' )
		);
		self::sync_inquiry_state( absint( $current['inquiry_id'] ), $assessment_id, $new_status );

		SC_EI_Audit_Log::record(
			'fit_second_review_recorded',
			'Independent human second review recorded.',
			array(
				'assessment_id' => $assessment_id,
				'review_id'     => $review_id,
				'disposition'   => $disposition,
				'new_status'    => $new_status,
				'automatic_decision' => false,
			),
			absint( $current['inquiry_id'] ),
			null,
			$actor_user_id
		);

		return self::find( $assessment_id );
	}

	public static function finalize( int $assessment_id, int $actor_user_id, int $expected_version ) {
		global $wpdb;

		$current = self::find( $assessment_id );
		if ( ! $current ) {
			return new WP_Error( 'fit_assessment_not_found', __( 'The fit assessment could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( 'ready_to_finalize' !== $current['status'] ) {
			return new WP_Error( 'fit_not_ready_to_finalize', __( 'The assessment is not ready to finalize.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( absint( $current['row_version'] ) !== $expected_version ) {
			return new WP_Error( 'fit_assessment_conflict', __( 'Reload the assessment before finalizing it.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$validation = self::validate_for_finalization( $current );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$now = current_time( 'mysql', true );
		$updated = $wpdb->update(
			SC_EI_Database::table( 'fit_assessments' ),
			array(
				'status'       => 'finalized',
				'finalized_by' => $actor_user_id,
				'finalized_at' => $now,
				'row_version'  => $expected_version + 1,
				'updated_at'   => $now,
			),
			array( 'id' => $assessment_id, 'row_version' => $expected_version ),
			array( '%s', '%d', '%s', '%d', '%s' ),
			array( '%d', '%d' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'fit_assessment_conflict', __( 'The assessment changed before finalization.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$wpdb->update(
			SC_EI_Database::table( 'inquiries' ),
			array(
				'fit_assessment_status'       => 'finalized',
				'current_fit_assessment_id'   => $assessment_id,
				'fit_assessment_updated_at'   => $now,
				'fit_assessment_finalized_at' => $now,
				'fit_assessment_version'      => absint( $current['assessment_version'] ),
				'updated_at'                  => $now,
			),
			array( 'id' => absint( $current['inquiry_id'] ) ),
			array( '%s', '%d', '%s', '%s', '%d', '%s' ),
			array( '%d' )
		);

		SC_EI_Audit_Log::record(
			'fit_assessment_finalized',
			'Human-controlled fit assessment finalized without changing inquiry status or sending communication.',
			array(
				'assessment_id'      => $assessment_id,
				'assessment_version' => absint( $current['assessment_version'] ),
				'recommendation'     => $current['recommendation'],
				'service_route'      => $current['service_route'],
				'scope_boundary'     => $current['scope_boundary'],
				'advisory_score'     => $current['advisory_score'],
				'automatic_status_change' => false,
				'automatic_communication' => false,
				'automatic_scheduling' => false,
			),
			absint( $current['inquiry_id'] ),
			null,
			$actor_user_id
		);

		return self::find( $assessment_id );
	}

	public static function apply_to_review( int $assessment_id, int $actor_user_id ) {
		$current = self::find( $assessment_id );
		if ( ! $current || 'finalized' !== $current['status'] ) {
			return new WP_Error( 'fit_finalized_required', __( 'Only a finalized assessment can be applied to the Review Workspace.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$inquiry = SC_EI_Inquiry_Repository::find( absint( $current['inquiry_id'] ) );
		if ( ! $inquiry ) {
			return new WP_Error( 'fit_inquiry_not_found', __( 'The inquiry could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$mapping = SC_EI_Fit_Schema::map_to_review( $current['recommendation'], $current['service_route'] );
		$summary = trim(
			(string) $current['overall_summary']
			. "\n\nFit assessment v" . absint( $current['assessment_version'] )
			. ': ' . SC_EI_Fit_Schema::label( SC_EI_Fit_Schema::recommendations(), $current['recommendation'] )
			. '; route: ' . SC_EI_Fit_Schema::label( SC_EI_Fit_Schema::service_routes(), $current['service_route'] )
		);
		$result = SC_EI_Review_Repository::save_review(
			absint( $current['inquiry_id'] ),
			array(
				'fit_decision'          => $mapping['fit_decision'],
				'fit_confidence'        => $current['confidence'],
				'recommended_next_step' => $mapping['recommended_next_step'],
				'review_summary'        => $summary,
				'decision_rationale'    => (string) $current['recommendation_rationale'],
				'conflict_notes'        => 'conflict_or_independence' === $current['scope_boundary']
					? (string) $current['limitations_notes']
					: (string) $inquiry['conflict_notes'],
				'event_type'            => 'fit_assessment_applied',
				'event_note'            => sprintf(
					'Authorized reviewer explicitly applied finalized fit assessment #%d. Inquiry status was not changed.',
					$assessment_id
				),
			),
			$actor_user_id,
			absint( $inquiry['review_version'] )
		);
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		SC_EI_Audit_Log::record(
			'fit_assessment_applied_to_review',
			'Authorized reviewer explicitly applied fit conclusions to the Review Workspace.',
			array(
				'assessment_id'       => $assessment_id,
				'fit_decision'        => $mapping['fit_decision'],
				'recommended_next_step'=> $mapping['recommended_next_step'],
				'inquiry_status_changed'=> false,
			),
			absint( $current['inquiry_id'] ),
			null,
			$actor_user_id
		);
		return true;
	}

	public static function queue( array $args = array() ): array {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			array(
				'status'   => '',
				'assessor' => 0,
				'search'   => '',
				'limit'    => 100,
			)
		);
		$table = SC_EI_Database::table( 'fit_assessments' );
		$inquiries = SC_EI_Database::table( 'inquiries' );
		$where = array( "a.status <> 'superseded'" );
		$values = array();
		$status = sanitize_key( (string) $args['status'] );
		if ( array_key_exists( $status, SC_EI_Fit_Schema::statuses() ) ) {
			$where[] = 'a.status = %s';
			$values[] = $status;
		}
		if ( absint( $args['assessor'] ) ) {
			$where[] = '(a.assessor_user_id = %d OR a.second_reviewer_user_id = %d)';
			$values[] = absint( $args['assessor'] );
			$values[] = absint( $args['assessor'] );
		}
		$search = sanitize_text_field( (string) $args['search'] );
		if ( '' !== $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$where[] = '(i.reference LIKE %s OR i.contact_name LIKE %s OR i.organization LIKE %s OR i.subject LIKE %s OR a.overall_summary LIKE %s)';
			array_push( $values, $like, $like, $like, $like, $like );
		}
		$sql = "SELECT a.*, i.reference, i.contact_name, i.organization, i.subject,
				i.review_stage, i.review_priority, i.privacy_status, i.legal_hold_count,
				u.display_name AS assessor_name, sr.display_name AS second_reviewer_name
			FROM {$table} a
			INNER JOIN {$inquiries} i ON i.id = a.inquiry_id
			LEFT JOIN {$wpdb->users} u ON u.ID = a.assessor_user_id
			LEFT JOIN {$wpdb->users} sr ON sr.ID = a.second_reviewer_user_id
			WHERE " . implode( ' AND ', $where ) . "
			ORDER BY CASE a.status
				WHEN 'second_review_requested' THEN 0
				WHEN 'changes_requested' THEN 1
				WHEN 'ready_to_finalize' THEN 2
				WHEN 'submitted' THEN 3
				WHEN 'draft' THEN 4
				WHEN 'finalized' THEN 5
				ELSE 6 END,
				a.updated_at ASC
			LIMIT %d";
		$values[] = max( 1, min( 500, absint( $args['limit'] ) ) );
		return (array) $wpdb->get_results( $wpdb->prepare( $sql, $values ), ARRAY_A );
	}

	public static function metrics(): array {
		global $wpdb;

		$table = SC_EI_Database::table( 'fit_assessments' );
		$stale_days = max( 1, absint( wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Fit_Schema::default_settings() )['fit_assessment_stale_days'] ?? 30 ) );
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - $stale_days * DAY_IN_SECONDS );
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS draft_count,
					SUM(CASE WHEN status = 'submitted' THEN 1 ELSE 0 END) AS submitted_count,
					SUM(CASE WHEN status = 'second_review_requested' THEN 1 ELSE 0 END) AS second_review_count,
					SUM(CASE WHEN status = 'changes_requested' THEN 1 ELSE 0 END) AS changes_count,
					SUM(CASE WHEN status = 'ready_to_finalize' THEN 1 ELSE 0 END) AS ready_count,
					SUM(CASE WHEN status = 'finalized' THEN 1 ELSE 0 END) AS finalized_count,
					SUM(CASE WHEN status <> 'finalized' AND updated_at < %s THEN 1 ELSE 0 END) AS stale_count,
					SUM(CASE WHEN material_concern_count > 0 AND status <> 'finalized' THEN 1 ELSE 0 END) AS concern_count
				FROM {$table}
				WHERE status <> 'superseded'", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$cutoff
			),
			ARRAY_A
		);
		$result = array();
		foreach ( array( 'draft_count', 'submitted_count', 'second_review_count', 'changes_count', 'ready_count', 'finalized_count', 'stale_count', 'concern_count' ) as $key ) {
			$result[ $key ] = absint( $row[ $key ] ?? 0 );
		}
		return $result;
	}

	public static function packet( int $assessment_id ): ?array {
		$assessment = self::find( $assessment_id );
		if ( ! $assessment ) {
			return null;
		}
		$inquiry = SC_EI_Inquiry_Repository::find( absint( $assessment['inquiry_id'] ) );
		return array(
			'schema'       => 'sc-engagement-intake-fit-assessment/1.0',
			'generated_at' => current_time( 'mysql', true ),
			'fit_schema_version' => SC_EI_FIT_SCHEMA_VERSION,
			'human_control' => array(
				'automatic_recommendation' => false,
				'automatic_acceptance'     => false,
				'automatic_rejection'      => false,
				'automatic_status_change'  => false,
				'automatic_communication'  => false,
				'advisory_score_thresholds'=> false,
			),
			'inquiry'      => $inquiry,
			'assessment'   => $assessment,
			'criteria'     => SC_EI_Fit_Schema::criteria(),
			'audit'        => SC_EI_Audit_Log::for_inquiry( absint( $assessment['inquiry_id'] ), 500 ),
		);
	}

	public static function items( int $assessment_id ): array {
		global $wpdb;

		$table = SC_EI_Database::table( 'fit_assessment_items' );
		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE assessment_id = %d ORDER BY id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$assessment_id
			),
			ARRAY_A
		);
		$result = array();
		foreach ( $rows as $row ) {
			$row['source_refs'] = json_decode( (string) $row['source_refs_json'], true ) ?: array();
			$result[ $row['criterion_key'] ] = $row;
		}
		return $result;
	}

	public static function second_reviews( int $assessment_id ): array {
		global $wpdb;

		$table = SC_EI_Database::table( 'fit_assessment_reviews' );
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.*, u.display_name AS reviewer_name
				FROM {$table} r
				LEFT JOIN {$wpdb->users} u ON u.ID = r.reviewer_user_id
				WHERE r.assessment_id = %d
				ORDER BY r.created_at ASC, r.id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$assessment_id
			),
			ARRAY_A
		);
	}

	public static function redact_for_privacy( int $inquiry_id, string $now ): bool {
		global $wpdb;

		$assessments = SC_EI_Database::table( 'fit_assessments' );
		$items = SC_EI_Database::table( 'fit_assessment_items' );
		$reviews = SC_EI_Database::table( 'fit_assessment_reviews' );
		$assessment_ids = (array) $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$assessments} WHERE inquiry_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$inquiry_id
			)
		);
		if ( ! $assessment_ids ) {
			return true;
		}
		$placeholders = implode( ',', array_fill( 0, count( $assessment_ids ), '%d' ) );
		$assessment_result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$assessments}
				SET overall_summary = '',
					recommendation_rationale = '',
					limitations_notes = '',
					conditions_for_fit = '',
					referral_notes = '',
					second_review_reason = '',
					assistance_notes = '',
					updated_at = %s
				WHERE id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				array_merge( array( $now ), array_map( 'absint', $assessment_ids ) )
			)
		);
		$item_result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$items}
				SET evidence_note = '',
					concern_note = '',
					source_refs_json = '[]',
					updated_at = %s
				WHERE assessment_id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				array_merge( array( $now ), array_map( 'absint', $assessment_ids ) )
			)
		);
		$review_result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$reviews}
				SET review_notes = '',
					required_changes = '',
					conflict_disclosure = ''
				WHERE assessment_id IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				array_map( 'absint', $assessment_ids )
			)
		);
		return false !== $assessment_result && false !== $item_result && false !== $review_result;
	}

	private static function seed_items( int $assessment_id ) {
		global $wpdb;

		$table = SC_EI_Database::table( 'fit_assessment_items' );
		$now = current_time( 'mysql', true );
		foreach ( SC_EI_Fit_Schema::criteria() as $key => $criterion ) {
			$data = array(
				'assessment_id'       => $assessment_id,
				'criterion_key'       => sanitize_key( $key ),
				'criterion_group'     => sanitize_key( (string) $criterion['group'] ),
				'rating'              => 'not_assessed',
				'weight'              => (float) $criterion['weight'],
				'numeric_value'       => null,
				'is_applicable'       => 1,
				'is_material_concern' => 0,
				'evidence_note'       => '',
				'concern_note'        => '',
				'source_refs_json'    => '[]',
				'row_version'         => 0,
				'created_at'          => $now,
				'updated_at'          => $now,
			);
			if ( false === $wpdb->insert( $table, $data, self::formats( $data, self::item_integer_fields() ) ) ) {
				return new WP_Error(
					'fit_item_seed_failed',
					sprintf(
						__( 'The criterion “%s” could not be initialized.', 'sustainable-catalyst-engagement-intake' ),
						$criterion['label']
					)
				);
			}
		}
		return true;
	}

	private static function upsert_item( int $assessment_id, array $item ) {
		global $wpdb;

		$table = SC_EI_Database::table( 'fit_assessment_items' );
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE assessment_id = %d AND criterion_key = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$assessment_id,
				$item['criterion_key']
			),
			ARRAY_A
		);
		$now = current_time( 'mysql', true );
		$data = array(
			'criterion_group'     => $item['criterion_group'],
			'rating'              => $item['rating'],
			'weight'              => $item['weight'],
			'numeric_value'       => $item['numeric_value'],
			'is_applicable'       => $item['is_applicable'],
			'is_material_concern' => $item['is_material_concern'],
			'evidence_note'       => $item['evidence_note'],
			'concern_note'        => $item['concern_note'],
			'source_refs_json'    => $item['source_refs_json'],
			'row_version'         => $existing ? absint( $existing['row_version'] ) + 1 : 0,
			'updated_at'          => $now,
		);
		if ( $existing ) {
			$updated = $wpdb->update(
				$table,
				$data,
				array( 'id' => absint( $existing['id'] ) ),
				self::formats( $data, self::item_integer_fields() ),
				array( '%d' )
			);
			return false === $updated
				? new WP_Error( 'fit_item_update_failed', __( 'A fit criterion could not be updated.', 'sustainable-catalyst-engagement-intake' ) )
				: true;
		}
		$data = array_merge(
			array(
				'assessment_id' => $assessment_id,
				'criterion_key' => $item['criterion_key'],
				'created_at'    => $now,
			),
			$data
		);
		return false === $wpdb->insert( $table, $data, self::formats( $data, self::item_integer_fields() ) )
			? new WP_Error( 'fit_item_insert_failed', __( 'A fit criterion could not be created.', 'sustainable-catalyst-engagement-intake' ) )
			: true;
	}

	private static function transition(
		array $current,
		string $status,
		int $actor_user_id,
		array $extra,
		string $event_type
	) {
		global $wpdb;

		$status = SC_EI_Fit_Schema::sanitize_choice( $status, SC_EI_Fit_Schema::statuses(), $current['status'] );
		$data = array_merge(
			array(
				'status'      => $status,
				'row_version' => absint( $current['row_version'] ) + 1,
				'updated_at'  => current_time( 'mysql', true ),
			),
			$extra
		);
		$updated = $wpdb->update(
			SC_EI_Database::table( 'fit_assessments' ),
			$data,
			array( 'id' => absint( $current['id'] ), 'row_version' => absint( $current['row_version'] ) ),
			self::formats( $data, self::assessment_integer_fields() ),
			array( '%d', '%d' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'fit_assessment_conflict', __( 'The fit assessment changed before the workflow transition.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::sync_inquiry_state(
			absint( $current['inquiry_id'] ),
			absint( $current['id'] ),
			$status
		);
		SC_EI_Audit_Log::record(
			$event_type,
			'Human-controlled fit assessment workflow state changed.',
			array(
				'assessment_id' => absint( $current['id'] ),
				'old_status'    => $current['status'],
				'new_status'    => $status,
				'automatic_decision' => false,
			),
			absint( $current['inquiry_id'] ),
			null,
			$actor_user_id
		);
		return self::find( absint( $current['id'] ) );
	}

	private static function validate_for_submission( array $assessment ) {
		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Fit_Schema::default_settings() );
		if ( 'undecided' === $assessment['recommendation'] || 'unassessed' === $assessment['confidence'] ) {
			return new WP_Error( 'fit_recommendation_required', __( 'Select a human recommendation and confidence level before submission.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( '' === trim( (string) $assessment['overall_summary'] ) ) {
			return new WP_Error( 'fit_summary_required', __( 'An overall human assessment summary is required.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if (
			! empty( $settings['fit_require_rationale_for_finalization'] )
			&& '' === trim( (string) $assessment['recommendation_rationale'] )
		) {
			return new WP_Error( 'fit_rationale_required', __( 'A recommendation rationale is required.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( ! empty( $settings['fit_require_human_attestation'] ) && empty( $assessment['human_attestation'] ) ) {
			return new WP_Error( 'fit_human_attestation_required', __( 'The assessor must attest that the recommendation is their own human judgment.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( empty( $assessment['score_complete'] ) ) {
			return new WP_Error( 'fit_criteria_incomplete', __( 'Assess or mark every criterion not applicable before submission.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if (
			in_array( $assessment['recommendation'], array( 'conditional_fit', 'needs_clarification' ), true )
			&& '' === trim( (string) $assessment['conditions_for_fit'] )
		) {
			return new WP_Error( 'fit_conditions_required', __( 'Conditions or clarification requirements are required for this recommendation.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if (
			in_array( $assessment['recommendation'], array( 'referral_candidate', 'not_a_fit' ), true )
			&& in_array( $assessment['service_route'], array( 'referral', 'decline' ), true )
			&& '' === trim( (string) $assessment['referral_notes'] )
		) {
			return new WP_Error( 'fit_referral_notes_required', __( 'Referral or decline notes are required.', 'sustainable-catalyst-engagement-intake' ) );
		}
		return true;
	}

	private static function validate_for_finalization( array $assessment ) {
		$validation = self::validate_for_submission( $assessment );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
		if (
			! empty( $assessment['second_review_required'] )
			&& ! in_array( $assessment['second_review_disposition'], array( 'agree' ), true )
		) {
			return new WP_Error( 'fit_second_review_unresolved', __( 'The required second review must agree before finalization.', 'sustainable-catalyst-engagement-intake' ) );
		}
		return true;
	}

	private static function inquiry_status_for_assessment( array $assessment ): string {
		return match ( $assessment['status'] ) {
			'draft', 'changes_requested'       => 'draft',
			'submitted'                        => 'under_review',
			'second_review_requested'          => 'second_review',
			'ready_to_finalize'                => 'ready_to_finalize',
			'finalized'                        => 'finalized',
			default                            => 'not_started',
		};
	}

	private static function sync_inquiry_state( int $inquiry_id, int $assessment_id, string $assessment_status ): void {
		global $wpdb;

		$assessment = self::find( $assessment_id );
		$now = current_time( 'mysql', true );
		$wpdb->update(
			SC_EI_Database::table( 'inquiries' ),
			array(
				'fit_assessment_status'     => self::inquiry_status_for_assessment( array( 'status' => $assessment_status ) ),
				'current_fit_assessment_id' => $assessment_id,
				'fit_assessment_updated_at' => $now,
				'fit_assessment_version'    => $assessment ? absint( $assessment['assessment_version'] ) : 0,
				'updated_at'                => $now,
			),
			array( 'id' => $inquiry_id ),
			array( '%s', '%d', '%s', '%d', '%s' ),
			array( '%d' )
		);
	}

	private static function sanitize_source_refs( $value ): array {
		if ( is_string( $value ) ) {
			$value = preg_split( '/[\r\n,]+/', $value );
		}
		$result = array();
		foreach ( (array) $value as $ref ) {
			$ref = sanitize_text_field( (string) $ref );
			if ( '' !== $ref ) {
				$result[] = mb_substr( $ref, 0, 500 );
			}
		}
		return array_slice( array_values( array_unique( $result ) ), 0, 20 );
	}

	private static function assessment_integer_fields(): array {
		return array(
			'inquiry_id', 'assessment_version', 'parent_assessment_id', 'assessor_user_id',
			'score_complete', 'material_concern_count', 'second_review_required',
			'second_reviewer_user_id', 'human_attestation', 'finalized_by', 'row_version',
		);
	}

	private static function item_integer_fields(): array {
		return array(
			'assessment_id', 'is_applicable', 'is_material_concern', 'row_version',
		);
	}

	private static function formats( array $data, array $integer_fields ): array {
		return array_map(
			static fn( string $key ): string => in_array( $key, $integer_fields, true ) ? '%d' : '%s',
			array_keys( $data )
		);
	}
}
