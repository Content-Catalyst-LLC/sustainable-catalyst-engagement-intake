<?php
/**
 * Current review state, assignment, queue queries, and immutable snapshots.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Review_Repository {

	public static function save_review( int $inquiry_id, array $input, int $actor_user_id, int $expected_version ) {
		global $wpdb;

		$current = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $current ) {
			return new WP_Error( 'review_not_found', __( 'The inquiry could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$current_version = absint( $current['review_version'] ?? 0 );
		if ( $expected_version !== $current_version ) {
			return new WP_Error(
				'review_conflict',
				__( 'This inquiry was changed by another reviewer. Reload the workspace before saving your review.', 'sustainable-catalyst-engagement-intake' )
			);
		}

		$settings = wp_parse_args(
			get_option( 'sc_ei_settings', array() ),
			array_merge( SC_EI_Admin::default_settings(), SC_EI_Review_Schema::default_review_settings() )
		);

		$stage = SC_EI_Review_Schema::sanitize_choice(
			(string) ( $input['review_stage'] ?? $current['review_stage'] ),
			SC_EI_Review_Schema::stages(),
			'intake'
		);
		$priority = SC_EI_Review_Schema::sanitize_choice(
			(string) ( $input['review_priority'] ?? $current['review_priority'] ),
			SC_EI_Review_Schema::priorities(),
			'normal'
		);
		$fit_decision = SC_EI_Review_Schema::sanitize_choice(
			(string) ( $input['fit_decision'] ?? $current['fit_decision'] ),
			SC_EI_Review_Schema::fit_decisions(),
			'undecided'
		);
		$fit_confidence = SC_EI_Review_Schema::sanitize_choice(
			(string) ( $input['fit_confidence'] ?? $current['fit_confidence'] ),
			SC_EI_Review_Schema::confidence_levels(),
			'unassessed'
		);
		$risk_level = SC_EI_Review_Schema::sanitize_choice(
			(string) ( $input['risk_level'] ?? $current['risk_level'] ),
			SC_EI_Review_Schema::risk_levels(),
			'unassessed'
		);
		$evidence_readiness = SC_EI_Review_Schema::sanitize_choice(
			(string) ( $input['evidence_readiness'] ?? $current['evidence_readiness'] ),
			SC_EI_Review_Schema::evidence_readiness_levels(),
			'not_assessed'
		);
		$scope_clarity = SC_EI_Review_Schema::sanitize_choice(
			(string) ( $input['scope_clarity'] ?? $current['scope_clarity'] ),
			SC_EI_Review_Schema::scope_clarity_levels(),
			'not_assessed'
		);
		$next_step = SC_EI_Review_Schema::sanitize_choice(
			(string) ( $input['recommended_next_step'] ?? $current['recommended_next_step'] ),
			SC_EI_Review_Schema::next_steps(),
			'review'
		);
		$escalation = SC_EI_Review_Schema::sanitize_choice(
			(string) ( $input['escalation_status'] ?? $current['escalation_status'] ),
			SC_EI_Review_Schema::escalation_statuses(),
			'none'
		);
		$inquiry_status = sanitize_key( (string) ( $input['inquiry_status'] ?? $current['status'] ) );
		if ( ! SC_EI_Statuses::is_valid( $inquiry_status ) ) {
			$inquiry_status = (string) $current['status'];
		}

		$assigned_user_id = array_key_exists( 'assigned_user_id', $input )
			? absint( $input['assigned_user_id'] )
			: absint( $current['assigned_user_id'] ?? 0 );
		if ( $assigned_user_id && ! user_can( $assigned_user_id, 'sc_intake_review' ) ) {
			return new WP_Error( 'invalid_reviewer', __( 'The selected assignee does not have permission to review inquiries.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$review_summary    = sanitize_textarea_field( (string) ( $input['review_summary'] ?? $current['review_summary'] ) );
		$rationale         = sanitize_textarea_field( (string) ( $input['decision_rationale'] ?? $current['decision_rationale'] ) );
		$information_gaps  = sanitize_textarea_field( (string) ( $input['information_gaps'] ?? $current['information_gaps'] ) );
		$conflict_notes    = sanitize_textarea_field( (string) ( $input['conflict_notes'] ?? $current['conflict_notes'] ) );
		$escalation_reason = sanitize_textarea_field( (string) ( $input['escalation_reason'] ?? $current['escalation_reason'] ) );
		$checklist         = SC_EI_Review_Schema::sanitize_checklist(
			$input['review_checklist'] ?? json_decode( (string) ( $current['review_checklist'] ?: '{}' ), true )
		);
		$checklist_progress= SC_EI_Review_Schema::checklist_progress( $checklist );
		$due_at            = self::sanitize_due_at( $input['review_due_local'] ?? null, $current['review_due_at'] ?? null );

		if (
			! empty( $settings['require_review_rationale'] )
			&& (
				'undecided' !== $fit_decision
				|| 'completed' === $stage
				|| in_array( $escalation, array( 'requested', 'under_review' ), true )
			)
			&& '' === trim( $rationale )
		) {
			return new WP_Error(
				'review_rationale_required',
				__( 'Record a decision rationale before saving this fit decision, escalation, or completed review.', 'sustainable-catalyst-engagement-intake' )
			);
		}

		if (
			'completed' === $stage
			&& ! empty( $settings['require_completion_checklist'] )
			&& ! $checklist_progress['complete']
		) {
			return new WP_Error(
				'review_checklist_incomplete',
				__( 'Complete the administrative review checklist before marking the review completed.', 'sustainable-catalyst-engagement-intake' )
			);
		}

		if ( 'completed' === $stage && ( 'undecided' === $fit_decision || 'review' === $next_step ) ) {
			return new WP_Error(
				'review_decision_incomplete',
				__( 'A completed review requires an explicit fit decision and recommended next step.', 'sustainable-catalyst-engagement-intake' )
			);
		}

		if ( in_array( $escalation, array( 'requested', 'under_review' ), true ) && '' === trim( $escalation_reason ) ) {
			return new WP_Error(
				'escalation_reason_required',
				__( 'Record an escalation reason before requesting or opening an escalation.', 'sustainable-catalyst-engagement-intake' )
			);
		}

		$now              = current_time( 'mysql', true );
		$assignment_changed = absint( $current['assigned_user_id'] ?? 0 ) !== $assigned_user_id;
		$fit_changed      = (string) $current['fit_decision'] !== $fit_decision;
		$stage_changed    = (string) $current['review_stage'] !== $stage;
		$status_changed   = (string) $current['status'] !== $inquiry_status;
		$escalation_changed = (string) $current['escalation_status'] !== $escalation;
		$new_version      = $current_version + 1;

		$closed_at = in_array( $inquiry_status, array( 'closed', 'not_a_fit', 'withdrawn' ), true )
			? ( $current['closed_at'] ?: $now )
			: null;

		$data = array(
			'assigned_user_id'        => $assigned_user_id ?: null,
			'assignment_at'           => $assignment_changed ? $now : ( $current['assignment_at'] ?: null ),
			'assignment_by'           => $assignment_changed ? $actor_user_id : ( $current['assignment_by'] ?: null ),
			'review_stage'            => $stage,
			'review_priority'         => $priority,
			'review_due_at'           => $due_at,
			'fit_decision'            => $fit_decision,
			'fit_confidence'          => $fit_confidence,
			'risk_level'              => $risk_level,
			'evidence_readiness'      => $evidence_readiness,
			'scope_clarity'           => $scope_clarity,
			'recommended_next_step'   => $next_step,
			'review_summary'          => $review_summary,
			'decision_rationale'      => $rationale,
			'information_gaps'        => $information_gaps,
			'conflict_notes'          => $conflict_notes,
			'review_checklist'        => wp_json_encode( $checklist ),
			'escalation_status'       => $escalation,
			'escalation_reason'       => $escalation_reason,
			'review_started_at'       => $current['review_started_at'] ?: $now,
			'last_reviewed_at'        => $now,
			'last_reviewed_by'        => $actor_user_id,
			'decision_at'             => $fit_changed && 'undecided' !== $fit_decision ? $now : ( $current['decision_at'] ?: null ),
			'review_completed_at'     => 'completed' === $stage ? ( $current['review_completed_at'] ?: $now ) : null,
			'review_version'          => $new_version,
			'status'                  => $inquiry_status,
			'closed_at'               => $closed_at,
			'updated_at'              => $now,
		);

		$integer_fields = array( 'assigned_user_id', 'assignment_by', 'last_reviewed_by', 'review_version' );
		$formats = array_map(
			static fn( string $key ): string => in_array( $key, $integer_fields, true ) ? '%d' : '%s',
			array_keys( $data )
		);

		$wpdb->query( 'START TRANSACTION' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		$updated = $wpdb->update(
			SC_EI_Database::table( 'inquiries' ),
			$data,
			array(
				'id'             => $inquiry_id,
				'review_version' => $current_version,
			),
			$formats,
			array( '%d', '%d' )
		);

		if ( 1 !== $updated ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			return new WP_Error(
				'review_conflict',
				__( 'The review could not be saved because the inquiry changed. Reload and try again.', 'sustainable-catalyst-engagement-intake' )
			);
		}

		$fresh = array_merge( $current, $data );
		$event_type = sanitize_key( (string) ( $input['event_type'] ?? 'review_saved' ) );
		$review_id = self::insert_snapshot(
			$fresh,
			$current,
			$actor_user_id,
			$event_type,
			sanitize_textarea_field( (string) ( $input['event_note'] ?? '' ) )
		);

		if ( ! $review_id ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			return new WP_Error(
				'review_snapshot_failed',
				__( 'The review state could not be recorded in the immutable review history.', 'sustainable-catalyst-engagement-intake' )
			);
		}

		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		SC_EI_Audit_Log::record(
			'review_saved',
			'Human-authored administrative review saved.',
			array(
				'review_id'          => $review_id,
				'old_stage'          => $current['review_stage'],
				'new_stage'          => $stage,
				'old_priority'       => $current['review_priority'],
				'new_priority'       => $priority,
				'old_fit_decision'   => $current['fit_decision'],
				'new_fit_decision'   => $fit_decision,
				'old_status'         => $current['status'],
				'new_status'         => $inquiry_status,
				'assignment_changed' => $assignment_changed,
				'assigned_user_id'   => $assigned_user_id,
				'escalation_changed' => $escalation_changed,
				'escalation_status'  => $escalation,
				'review_version'     => $new_version,
				'checklist_percent'  => $checklist_progress['percent'],
				'event_type'         => $event_type,
			),
			$inquiry_id,
			null,
			$actor_user_id
		);

		if ( $assignment_changed ) {
			SC_EI_Audit_Log::record(
				'review_assignment_changed',
				'Administrative review assignment changed.',
				array(
					'old_assigned_user_id' => absint( $current['assigned_user_id'] ?? 0 ),
					'new_assigned_user_id' => $assigned_user_id,
				),
				$inquiry_id,
				null,
				$actor_user_id
			);
		}
		if ( $stage_changed && 'completed' === $stage ) {
			SC_EI_Audit_Log::record(
				'review_completed',
				'Human administrative review completed.',
				array(
					'fit_decision'          => $fit_decision,
					'recommended_next_step' => $next_step,
					'inquiry_status'        => $inquiry_status,
				),
				$inquiry_id,
				null,
				$actor_user_id
			);
		}
		if ( $status_changed ) {
			SC_EI_Audit_Log::record(
				'status_changed',
				'Inquiry status explicitly changed from the administrative review workspace.',
				array(
					'old_status' => $current['status'],
					'new_status' => $inquiry_status,
				),
				$inquiry_id,
				null,
				$actor_user_id
			);
		}

		do_action( 'sc_ei_review_saved', $fresh, $current, $actor_user_id );

		return array(
			'ok'             => true,
			'inquiry_id'     => $inquiry_id,
			'review_id'      => $review_id,
			'review_version' => $new_version,
			'stage'          => $stage,
			'fit_decision'   => $fit_decision,
			'status'         => $inquiry_status,
		);
	}

	public static function assign(
		int $inquiry_id,
		int $assigned_user_id,
		int $actor_user_id,
		int $expected_version,
		string $note = ''
	) {
		$current = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $current ) {
			return new WP_Error( 'review_not_found', __( 'The inquiry could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}

		return self::save_review(
			$inquiry_id,
			array(
				'assigned_user_id' => $assigned_user_id,
				'event_type'       => $assigned_user_id ? 'assignment_changed' : 'assignment_removed',
				'event_note'       => $note,
			),
			$actor_user_id,
			$expected_version
		);
	}

	public static function history( int $inquiry_id, int $limit = 100 ): array {
		global $wpdb;

		$table = SC_EI_Database::table( 'reviews' );
		$users = $wpdb->users;
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.*, u.display_name AS reviewer_name, u.user_email AS reviewer_email
				FROM {$table} r
				LEFT JOIN {$users} u ON u.ID = r.reviewer_user_id
				WHERE r.inquiry_id = %d
				ORDER BY r.created_at DESC, r.id DESC
				LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$inquiry_id,
				max( 1, min( 500, $limit ) )
			),
			ARRAY_A
		);
	}

	public static function query( array $args = array() ): array {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			array(
				'view'              => 'queue',
				'review_stage'      => '',
				'review_priority'   => '',
				'fit_decision'      => '',
				'risk_level'        => '',
				'escalation_status' => '',
				'assignee'          => '',
				'due_state'         => '',
				'status'            => '',
				'inquiry_type'      => '',
				'source_page'       => '',
				'search'            => '',
				'current_user_id'   => get_current_user_id(),
				'page'              => 1,
				'per_page'          => 20,
				'orderby'           => 'review_due_at',
				'order'             => 'ASC',
			)
		);

		$inquiries   = SC_EI_Database::table( 'inquiries' );
		$attachments = SC_EI_Database::table( 'attachments' );
		$where       = array( '1=1' );
		$params      = array();
		$now         = current_time( 'mysql', true );
		$soon        = gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS );

		$view = sanitize_key( (string) $args['view'] );
		if ( 'mine' === $view ) {
			$where[]  = 'i.assigned_user_id = %d AND i.review_stage <> %s';
			$params[] = absint( $args['current_user_id'] );
			$params[] = 'completed';
		} elseif ( 'unassigned' === $view ) {
			$where[] = '(i.assigned_user_id IS NULL OR i.assigned_user_id = 0) AND i.review_stage <> %s';
			$params[]= 'completed';
		} elseif ( 'escalations' === $view ) {
			$where[] = 'i.escalation_status IN (%s, %s)';
			$params[]= 'requested';
			$params[]= 'under_review';
		} elseif ( 'completed' === $view ) {
			$where[] = 'i.review_stage = %s';
			$params[]= 'completed';
		} else {
			$where[] = 'i.review_stage <> %s';
			$params[]= 'completed';
		}

		$choices = array(
			'review_stage'      => SC_EI_Review_Schema::stages(),
			'review_priority'   => SC_EI_Review_Schema::priorities(),
			'fit_decision'      => SC_EI_Review_Schema::fit_decisions(),
			'risk_level'        => SC_EI_Review_Schema::risk_levels(),
			'escalation_status' => SC_EI_Review_Schema::escalation_statuses(),
		);
		foreach ( $choices as $field => $options ) {
			$value = sanitize_key( (string) $args[ $field ] );
			if ( array_key_exists( $value, $options ) ) {
				$where[]  = "i.{$field} = %s";
				$params[] = $value;
			}
		}

		$assignee = sanitize_text_field( (string) $args['assignee'] );
		if ( 'unassigned' === $assignee ) {
			$where[] = '(i.assigned_user_id IS NULL OR i.assigned_user_id = 0)';
		} elseif ( 'me' === $assignee ) {
			$where[]  = 'i.assigned_user_id = %d';
			$params[] = absint( $args['current_user_id'] );
		} elseif ( absint( $assignee ) ) {
			$where[]  = 'i.assigned_user_id = %d';
			$params[] = absint( $assignee );
		}

		$due_state = sanitize_key( (string) $args['due_state'] );
		if ( 'overdue' === $due_state ) {
			$where[]  = 'i.review_due_at IS NOT NULL AND i.review_due_at < %s AND i.review_stage <> %s';
			$params[] = $now;
			$params[] = 'completed';
		} elseif ( 'due_soon' === $due_state ) {
			$where[]  = 'i.review_due_at IS NOT NULL AND i.review_due_at >= %s AND i.review_due_at <= %s AND i.review_stage <> %s';
			$params[] = $now;
			$params[] = $soon;
			$params[] = 'completed';
		} elseif ( 'no_due' === $due_state ) {
			$where[] = 'i.review_due_at IS NULL';
		}

		$status = sanitize_key( (string) $args['status'] );
		if ( SC_EI_Statuses::is_valid( $status ) ) {
			$where[]  = 'i.status = %s';
			$params[] = $status;
		}

		$type = sanitize_key( (string) $args['inquiry_type'] );
		if ( array_key_exists( $type, SC_EI_Statuses::inquiry_types() ) ) {
			$where[]  = 'i.inquiry_type = %s';
			$params[] = $type;
		}

		if ( $args['source_page'] ) {
			$where[]  = 'i.source_page = %s';
			$params[] = SC_EI_Conversion::sanitize_source( (string) $args['source_page'] );
		}

		$search = sanitize_text_field( (string) $args['search'] );
		if ( '' !== $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$where[] = '(i.reference LIKE %s OR i.contact_name LIKE %s OR i.contact_email LIKE %s OR i.organization LIKE %s OR i.subject LIKE %s OR i.project_summary LIKE %s OR i.review_summary LIKE %s OR i.decision_rationale LIKE %s)';
			array_push( $params, $like, $like, $like, $like, $like, $like, $like, $like );
		}

		$allowed_orderby = array(
			'reference'       => 'i.reference',
			'contact_name'    => 'i.contact_name',
			'review_stage'    => 'i.review_stage',
			'review_priority' => 'i.review_priority',
			'review_due_at'   => 'i.review_due_at',
			'fit_decision'    => 'i.fit_decision',
			'risk_level'      => 'i.risk_level',
			'status'          => 'i.status',
			'created_at'      => 'i.created_at',
			'last_reviewed_at'=> 'i.last_reviewed_at',
		);
		$orderby = $allowed_orderby[ sanitize_key( (string) $args['orderby'] ) ] ?? 'i.review_due_at';
		$order   = 'DESC' === strtoupper( (string) $args['order'] ) ? 'DESC' : 'ASC';
		$page    = max( 1, absint( $args['page'] ) );
		$per_page= max( 1, min( 100, absint( $args['per_page'] ) ) );
		$offset  = ( $page - 1 ) * $per_page;
		$where_sql = implode( ' AND ', $where );

		$document_join = "LEFT JOIN (
			SELECT inquiry_id,
				COUNT(*) AS document_count,
				SUM(CASE WHEN scan_status IN ('infected','error','skipped','not_configured') OR storage_status <> 'healthy' OR validation_status <> 'validated' THEN 1 ELSE 0 END) AS document_attention_count,
				SUM(CASE WHEN quarantine_status = 'approved' THEN 1 ELSE 0 END) AS approved_document_count
			FROM {$attachments}
			WHERE deleted_at IS NULL
			GROUP BY inquiry_id
		) d ON d.inquiry_id = i.id";

		$joins = "LEFT JOIN {$wpdb->users} assignee ON assignee.ID = i.assigned_user_id
			LEFT JOIN {$wpdb->users} reviewer ON reviewer.ID = i.last_reviewed_by
			{$document_join}";

		$count_sql = "SELECT COUNT(*) FROM {$inquiries} i {$joins} WHERE {$where_sql}";
		$data_sql = "SELECT i.*,
			assignee.display_name AS assigned_name,
			assignee.user_email AS assigned_email,
			reviewer.display_name AS last_reviewer_name,
			COALESCE(d.document_count, 0) AS document_count,
			COALESCE(d.document_attention_count, 0) AS document_attention_count,
			COALESCE(d.approved_document_count, 0) AS approved_document_count
			FROM {$inquiries} i
			{$joins}
			WHERE {$where_sql}
			ORDER BY CASE WHEN i.review_priority = 'urgent' THEN 1 WHEN i.review_priority = 'high' THEN 2 WHEN i.review_priority = 'normal' THEN 3 ELSE 4 END ASC,
			{$orderby} {$order}, i.id {$order}
			LIMIT %d OFFSET %d";

		$count_query = $params ? $wpdb->prepare( $count_sql, $params ) : $count_sql;
		$total       = (int) $wpdb->get_var( $count_query );
		$data_params = array_merge( $params, array( $per_page, $offset ) );
		$items       = (array) $wpdb->get_results( $wpdb->prepare( $data_sql, $data_params ), ARRAY_A );

		return array(
			'items'       => $items,
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => max( 1, (int) ceil( $total / $per_page ) ),
		);
	}

	public static function metrics( int $current_user_id ): array {
		global $wpdb;

		$table = SC_EI_Database::table( 'inquiries' );
		$attachments = SC_EI_Database::table( 'attachments' );
		$now   = current_time( 'mysql', true );
		$soon  = gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS );
		$month = gmdate( 'Y-m-d H:i:s', time() - ( 30 * DAY_IN_SECONDS ) );

		$sql = $wpdb->prepare(
			"SELECT
				SUM(CASE WHEN review_stage <> 'completed' THEN 1 ELSE 0 END) AS open_reviews,
				SUM(CASE WHEN review_stage <> 'completed' AND (assigned_user_id IS NULL OR assigned_user_id = 0) THEN 1 ELSE 0 END) AS unassigned,
				SUM(CASE WHEN review_stage <> 'completed' AND assigned_user_id = %d THEN 1 ELSE 0 END) AS my_reviews,
				SUM(CASE WHEN review_stage <> 'completed' AND review_due_at IS NOT NULL AND review_due_at < %s THEN 1 ELSE 0 END) AS overdue,
				SUM(CASE WHEN review_stage <> 'completed' AND review_due_at >= %s AND review_due_at <= %s THEN 1 ELSE 0 END) AS due_soon,
				SUM(CASE WHEN escalation_status IN ('requested','under_review') THEN 1 ELSE 0 END) AS escalated,
				SUM(CASE WHEN review_stage = 'decision_ready' THEN 1 ELSE 0 END) AS decision_ready,
				SUM(CASE WHEN review_stage = 'completed' AND review_completed_at >= %s THEN 1 ELSE 0 END) AS completed_30d,
				SUM(CASE WHEN meeting_request <> 'no' AND scheduling_status IN ('requested','under_review','availability_confirmed') THEN 1 ELSE 0 END) AS meeting_attention
			FROM {$table}", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$current_user_id,
			$now,
			$now,
			$soon,
			$month
		);
		$row = (array) $wpdb->get_row( $sql, ARRAY_A );

		$document_attention = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT inquiry_id) FROM {$attachments}
			WHERE deleted_at IS NULL
			AND (scan_status IN ('infected','error','skipped','not_configured') OR storage_status <> 'healthy' OR validation_status <> 'validated')" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);

		$result = array();
		foreach ( array( 'open_reviews', 'unassigned', 'my_reviews', 'overdue', 'due_soon', 'escalated', 'decision_ready', 'completed_30d', 'meeting_attention' ) as $key ) {
			$result[ $key ] = absint( $row[ $key ] ?? 0 );
		}
		$result['document_attention'] = $document_attention;

		return $result;
	}

	public static function packet( int $inquiry_id ): ?array {
		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $inquiry ) {
			return null;
		}

		$attachments = array_map(
			static fn( array $attachment ): array => array(
				'id'                    => absint( $attachment['id'] ),
				'original_name'         => sanitize_file_name( (string) $attachment['original_name'] ),
				'extension'             => sanitize_key( (string) $attachment['extension'] ),
				'size_bytes'            => absint( $attachment['size_bytes'] ),
				'sha256'                => sanitize_text_field( (string) $attachment['sha256'] ),
				'document_category'     => sanitize_key( (string) $attachment['document_category'] ),
				'confidentiality'       => sanitize_key( (string) $attachment['confidentiality'] ),
				'quarantine_status'     => sanitize_key( (string) $attachment['quarantine_status'] ),
				'validation_status'     => sanitize_key( (string) $attachment['validation_status'] ),
				'scan_status'           => sanitize_key( (string) $attachment['scan_status'] ),
				'storage_status'        => sanitize_key( (string) $attachment['storage_status'] ),
				'integrity_status'      => sanitize_key( (string) $attachment['integrity_status'] ),
				'retention_until'       => sanitize_text_field( (string) $attachment['retention_until'] ),
				'uploaded_at'           => sanitize_text_field( (string) $attachment['uploaded_at'] ),
			),
			SC_EI_Attachment_Repository::for_inquiry( $inquiry_id, true )
		);

		return array(
			'schema'       => 'sc-engagement-intake-review-packet/1.0',
			'generated_at' => current_time( 'mysql', true ),
			'inquiry'      => $inquiry,
			'reviews'      => self::history( $inquiry_id, 500 ),
			'fit_assessment' => ! empty( $inquiry['current_fit_assessment_id'] )
				? SC_EI_Fit_Repository::find( absint( $inquiry['current_fit_assessment_id'] ) )
				: null,
			'sender_portal' => current_user_can( 'sc_intake_view_sender_portal' )
				? SC_EI_Portal_Repository::export_for_inquiry( $inquiry_id )
				: null,
			'teams_proposal_workflow' => current_user_can( 'sc_intake_view_workflow' )
				? SC_EI_Workflow_Repository::export_for_inquiry( $inquiry_id )
				: null,
			'engagement_handoff' => current_user_can( 'sc_intake_view_engagements' )
				? SC_EI_Engagement_Repository::export_for_inquiry( $inquiry_id )
				: null,
			'attachments'    => $attachments,
			'communications' => SC_EI_Communication_Repository::for_inquiry( $inquiry_id, 500, true ),
			'privacy'        => array(
				'consent_events' => SC_EI_Privacy_Repository::consent_events( array( 'inquiry_id' => $inquiry_id, 'limit' => 500 ) ),
				'legal_holds' => array_values(
					array_filter(
						SC_EI_Privacy_Repository::holds( array( 'search' => (string) $inquiry['reference'], 'limit' => 500 ) ),
						static fn( array $hold ): bool => absint( $hold['inquiry_id'] ) === $inquiry_id
					)
				),
				'retention_actions' => array_values(
					array_filter(
						SC_EI_Privacy_Repository::retention_actions( array( 'search' => (string) $inquiry['reference'], 'limit' => 500 ) ),
						static fn( array $action ): bool => absint( $action['inquiry_id'] ) === $inquiry_id
					)
				),
			),
			'audit'          => SC_EI_Audit_Log::for_inquiry( $inquiry_id, 500 ),
		);
	}

	private static function insert_snapshot(
		array $fresh,
		array $previous,
		int $actor_user_id,
		string $event_type,
		string $event_note
	): int {
		global $wpdb;

		$data = array(
			'inquiry_id'            => absint( $fresh['id'] ),
			'public_id'             => wp_generate_uuid4(),
			'reviewer_user_id'      => $actor_user_id ?: null,
			'event_type'            => $event_type ?: 'review_saved',
			'from_stage'            => sanitize_key( (string) $previous['review_stage'] ),
			'to_stage'              => sanitize_key( (string) $fresh['review_stage'] ),
			'priority'              => sanitize_key( (string) $fresh['review_priority'] ),
			'fit_decision'          => sanitize_key( (string) $fresh['fit_decision'] ),
			'fit_confidence'        => sanitize_key( (string) $fresh['fit_confidence'] ),
			'risk_level'            => sanitize_key( (string) $fresh['risk_level'] ),
			'evidence_readiness'    => sanitize_key( (string) $fresh['evidence_readiness'] ),
			'scope_clarity'         => sanitize_key( (string) $fresh['scope_clarity'] ),
			'recommended_next_step' => sanitize_key( (string) $fresh['recommended_next_step'] ),
			'summary'               => sanitize_textarea_field( (string) $fresh['review_summary'] ),
			'rationale'             => sanitize_textarea_field( (string) $fresh['decision_rationale'] ),
			'information_gaps'      => sanitize_textarea_field( (string) $fresh['information_gaps'] ),
			'conflict_notes'        => sanitize_textarea_field( (string) $fresh['conflict_notes'] ),
			'checklist_json'        => (string) $fresh['review_checklist'],
			'escalation_status'     => sanitize_key( (string) $fresh['escalation_status'] ),
			'escalation_reason'     => sanitize_textarea_field( (string) $fresh['escalation_reason'] ),
			'assigned_user_id'      => ! empty( $fresh['assigned_user_id'] ) ? absint( $fresh['assigned_user_id'] ) : null,
			'due_at'                => $fresh['review_due_at'] ?: null,
			'inquiry_status'        => sanitize_key( (string) $fresh['status'] ),
			'review_version'        => absint( $fresh['review_version'] ),
			'snapshot_json'         => wp_json_encode(
				array(
					'event_note'            => $event_note,
					'previous_assignment'   => absint( $previous['assigned_user_id'] ?? 0 ),
					'current_assignment'    => absint( $fresh['assigned_user_id'] ?? 0 ),
					'previous_status'       => sanitize_key( (string) $previous['status'] ),
					'current_status'        => sanitize_key( (string) $fresh['status'] ),
					'previous_escalation'   => sanitize_key( (string) $previous['escalation_status'] ),
					'current_escalation'    => sanitize_key( (string) $fresh['escalation_status'] ),
					'review_schema_version' => SC_EI_REVIEW_SCHEMA_VERSION,
				)
			),
			'created_at'             => current_time( 'mysql', true ),
		);

		$integer_fields = array( 'inquiry_id', 'reviewer_user_id', 'assigned_user_id', 'review_version' );
		$formats = array_map(
			static fn( string $key ): string => in_array( $key, $integer_fields, true ) ? '%d' : '%s',
			array_keys( $data )
		);

		$inserted = $wpdb->insert( SC_EI_Database::table( 'reviews' ), $data, $formats );
		return false === $inserted ? 0 : (int) $wpdb->insert_id;
	}

	private static function sanitize_due_at( $local_value, $fallback ): ?string {
		if ( null === $local_value ) {
			return $fallback ?: null;
		}

		$local_value = sanitize_text_field( (string) $local_value );
		if ( '' === $local_value ) {
			return null;
		}

		try {
			$date = new DateTimeImmutable( $local_value, wp_timezone() );
			return $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
		} catch ( Throwable $exception ) {
			return $fallback ?: null;
		}
	}
}
