<?php
/**
 * Aggregate engagement analytics and human-reviewed service intelligence.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Service_Intelligence_Repository {

	public const MIGRATION_KEY = 'v1_6_0_engagement_analytics_service_intelligence';
	private const OPTION_SCHEMA = 'sc_ei_service_intelligence_schema_version';

	public static function register(): void {}

	public static function maybe_upgrade(): void {
		$stored = (string) get_option( self::OPTION_SCHEMA, '' );
		if ( version_compare( $stored, SC_EI_SERVICE_INTELLIGENCE_SCHEMA_VERSION, '<' ) ) {
			SC_EI_Database::install();
		}
		self::record_migration( $stored );
		if ( ! in_array( false, SC_EI_Database::service_intelligence_columns_exist(), true ) ) {
			update_option( self::OPTION_SCHEMA, SC_EI_SERVICE_INTELLIGENCE_SCHEMA_VERSION, false );
		}
	}

	public static function record_migration( string $from_schema = '' ) {
		global $wpdb;
		$table = SC_EI_Database::table( 'platform_migrations' );
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE migration_key = %s LIMIT 1", self::MIGRATION_KEY ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$columns = SC_EI_Database::service_intelligence_columns_exist();
		$ok = ! in_array( false, $columns, true );
		$now = current_time( 'mysql', true );
		$context = array(
			'release'                  => 'Engagement Analytics and Service Intelligence',
			'from_schema'              => $from_schema,
			'to_schema'                => SC_EI_SERVICE_INTELLIGENCE_SCHEMA_VERSION,
			'aggregate_only'           => true,
			'minimum_cohort_suppression'=> true,
			'no_sender_ranking'        => true,
			'no_automated_decisions'   => true,
			'human_review_required'    => true,
			'no_destructive_migration' => true,
			'missing_contract_items'   => array_keys( array_filter( $columns, static fn( bool $value ): bool => ! $value ) ),
		);
		$data = array(
			'public_id'     => $existing['public_id'] ?? wp_generate_uuid4(),
			'migration_key' => self::MIGRATION_KEY,
			'from_version'  => $from_schema,
			'to_version'    => SC_EI_SERVICE_INTELLIGENCE_SCHEMA_VERSION,
			'status'        => $ok ? 'completed' : 'failed',
			'context_json'  => wp_json_encode( $context, JSON_UNESCAPED_SLASHES ),
			'started_at'    => $existing['started_at'] ?? $now,
			'completed_at'  => $ok ? $now : null,
			'error_code'    => $ok ? '' : 'service_intelligence_schema_incomplete',
			'error_message' => $ok ? '' : 'The service-intelligence database contract is incomplete.',
			'created_at'    => $existing['created_at'] ?? $now,
			'updated_at'    => $now,
		);
		if ( $existing ) {
			$result = $wpdb->update( $table, $data, array( 'id' => absint( $existing['id'] ) ), self::formats( $data ), array( '%d' ) );
			$id = absint( $existing['id'] );
		} else {
			$result = $wpdb->insert( $table, $data, self::formats( $data ) );
			$id = (int) $wpdb->insert_id;
		}
		if ( false === $result ) {
			return new WP_Error( 'service_intelligence_migration_failed', __( 'The service-intelligence migration journal could not be recorded.', 'sustainable-catalyst-engagement-intake' ) );
		}
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function dashboard( int $days = 90 ): array {
		global $wpdb;
		$base = SC_EI_Analytics_Repository::dashboard( $days );
		$days = absint( $base['range_days'] ?? 90 );
		$from = (string) ( $base['from_utc'] ?? gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS ) );
		$min = max( 2, absint( $base['minimum_cohort'] ?? 5 ) );
		$now = current_time( 'mysql', true );

		$support = SC_EI_Database::table( 'support_cases' );
		$signals = SC_EI_Database::table( 'support_signals' );
		$workspaces = SC_EI_Database::table( 'client_workspaces' );
		$milestones = SC_EI_Database::table( 'workspace_milestones' );
		$deliverables = SC_EI_Database::table( 'workspace_deliverables' );
		$proposals = SC_EI_Database::table( 'proposals' );
		$meetings = SC_EI_Database::table( 'meeting_offers' );
		$engagements = SC_EI_Database::table( 'engagements' );
		$lifecycle_tasks = SC_EI_Database::table( 'lifecycle_tasks' );

		$support_total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$support} WHERE created_at >= %s", $from ) );
		$support_resolved = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$support} WHERE created_at >= %s AND workflow_stage IN ('resolved','closed')", $from ) );
		$support_triaged = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$support} WHERE created_at >= %s AND workflow_stage <> 'new_support_request'", $from ) );
		$known_issues = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$support} WHERE created_at >= %s AND known_issue_reference <> ''", $from ) );
		$signal_total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$signals} WHERE created_at >= %s", $from ) );
		$product_mix = self::group_query( "SELECT product label, COUNT(*) count FROM {$support} WHERE created_at >= %s AND product <> '' GROUP BY product ORDER BY count DESC", $from, $min );
		$component_mix = self::group_query( "SELECT component label, COUNT(*) count FROM {$support} WHERE created_at >= %s AND component <> '' GROUP BY component ORDER BY count DESC", $from, $min );
		$signal_mix = self::group_query( "SELECT signal_type label, COUNT(*) count FROM {$signals} WHERE created_at >= %s GROUP BY signal_type ORDER BY count DESC", $from, $min );
		$median_triage = self::median( $wpdb->get_col( $wpdb->prepare( "SELECT TIMESTAMPDIFF(HOUR,created_at,updated_at) FROM {$support} WHERE created_at >= %s AND workflow_stage <> 'new_support_request'", $from ) ) );
		$median_resolution = self::median( $wpdb->get_col( $wpdb->prepare( "SELECT TIMESTAMPDIFF(HOUR,created_at,resolved_at) FROM {$support} WHERE created_at >= %s AND resolved_at IS NOT NULL", $from ) ) );

		$workspace_total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$workspaces} WHERE created_at >= %s", $from ) );
		$workspace_active = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$workspaces} WHERE created_at >= %s AND status = 'active'", $from ) );
		$workspace_completed = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$workspaces} WHERE created_at >= %s AND status = 'completed'", $from ) );
		$milestone_total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$milestones} WHERE created_at >= %s", $from ) );
		$milestone_completed = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$milestones} WHERE created_at >= %s AND status = 'completed'", $from ) );
		$deliverable_total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$deliverables} WHERE created_at >= %s", $from ) );
		$deliverable_accepted = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$deliverables} WHERE created_at >= %s AND sender_decision = 'accepted'", $from ) );
		$deliverable_changes = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$deliverables} WHERE created_at >= %s AND sender_decision = 'changes_requested'", $from ) );

		$proposal_total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$proposals} WHERE created_at >= %s", $from ) );
		$proposal_sent = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$proposals} WHERE created_at >= %s AND status IN ('published','viewed','changes_requested','accepted_pending_contract','contracted','converted_to_engagement')", $from ) );
		$proposal_accepted = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$proposals} WHERE created_at >= %s AND status IN ('accepted_pending_contract','contracted','converted_to_engagement')", $from ) );
		$meeting_total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$meetings} WHERE created_at >= %s", $from ) );
		$meeting_completed = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$meetings} WHERE created_at >= %s AND status = 'completed'", $from ) );
		$engagement_total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$engagements} WHERE created_at >= %s", $from ) );
		$engagement_active = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$engagements} WHERE created_at >= %s AND status = 'active'", $from ) );
		$overdue_tasks = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$lifecycle_tasks} WHERE due_at < %s AND status IN ('open','in_progress')", $now ) );

		$weekly = self::weekly_series( $from, $min );
		$findings = self::metrics();

		return array(
			'schema'          => SC_EI_Service_Intelligence_Schema::SNAPSHOT_SCHEMA,
			'generated_at'    => $now,
			'range_days'      => $days,
			'from_utc'        => $from,
			'minimum_cohort'  => $min,
			'base'            => $base,
			'support'         => array(
				'counts' => array( 'cases' => $support_total, 'triaged' => $support_triaged, 'resolved' => $support_resolved, 'known_issue_matches' => $known_issues, 'signals' => $signal_total ),
				'rates'  => array( 'triage' => SC_EI_Analytics_Schema::rate( $support_triaged, $support_total, $min ), 'resolution' => SC_EI_Analytics_Schema::rate( $support_resolved, $support_total, $min ), 'known_issue_match' => SC_EI_Analytics_Schema::rate( $known_issues, $support_total, $min ) ),
				'timing' => array( 'median_hours_to_triage' => $median_triage, 'median_hours_to_resolution' => $median_resolution ),
				'mix'    => array( 'products' => $product_mix, 'components' => $component_mix, 'signals' => $signal_mix ),
			),
			'collaboration'   => array(
				'counts' => array( 'workspaces' => $workspace_total, 'active_workspaces' => $workspace_active, 'completed_workspaces' => $workspace_completed, 'milestones' => $milestone_total, 'completed_milestones' => $milestone_completed, 'deliverables' => $deliverable_total, 'accepted_deliverables' => $deliverable_accepted, 'deliverable_changes_requested' => $deliverable_changes ),
				'rates'  => array( 'workspace_completion' => SC_EI_Analytics_Schema::rate( $workspace_completed, $workspace_total, $min ), 'milestone_completion' => SC_EI_Analytics_Schema::rate( $milestone_completed, $milestone_total, $min ), 'deliverable_acceptance' => SC_EI_Analytics_Schema::rate( $deliverable_accepted, $deliverable_total, $min ) ),
			),
			'commercial'      => array(
				'counts' => array( 'meetings' => $meeting_total, 'completed_meetings' => $meeting_completed, 'proposals' => $proposal_total, 'proposals_sent' => $proposal_sent, 'proposals_accepted' => $proposal_accepted, 'engagements' => $engagement_total, 'active_engagements' => $engagement_active ),
				'rates'  => array( 'meeting_completion' => SC_EI_Analytics_Schema::rate( $meeting_completed, $meeting_total, $min ), 'proposal_acceptance' => SC_EI_Analytics_Schema::rate( $proposal_accepted, $proposal_sent, $min ), 'engagement_activation' => SC_EI_Analytics_Schema::rate( $engagement_active, $engagement_total, $min ) ),
			),
			'operations'      => array_merge( (array) ( $base['operations'] ?? array() ), array( 'overdue_lifecycle_tasks' => $overdue_tasks ) ),
			'weekly_series'   => $weekly,
			'finding_metrics' => $findings,
			'boundaries'      => array( 'aggregate_only' => true, 'minimum_cohort_suppression' => true, 'human_review_required' => true, 'personal_data' => false, 'sender_ranking' => false, 'automated_decisions' => false, 'message_bodies' => false, 'document_contents' => false ),
		);
	}

	public static function create_snapshot( int $days, int $actor_user_id = 0 ) {
		global $wpdb;
		$payload = self::dashboard( $days );
		$json = wp_json_encode( $payload, JSON_UNESCAPED_SLASHES );
		if ( false === $json ) {
			return new WP_Error( 'service_intelligence_snapshot_encode_failed', __( 'The aggregate service-intelligence snapshot could not be encoded.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$hash = hash( 'sha256', $json );
		$data = array(
			'public_id' => wp_generate_uuid4(),
			'range_days' => absint( $payload['range_days'] ),
			'period_start' => (string) $payload['from_utc'],
			'period_end' => (string) $payload['generated_at'],
			'minimum_cohort' => absint( $payload['minimum_cohort'] ),
			'payload_json' => $json,
			'content_hash' => $hash,
			'generated_by' => $actor_user_id ?: null,
			'generated_at' => (string) $payload['generated_at'],
		);
		if ( false === $wpdb->insert( SC_EI_Database::table( 'analytics_snapshots' ), $data, array( '%s','%d','%s','%s','%d','%s','%s','%d','%s' ) ) ) {
			return new WP_Error( 'service_intelligence_snapshot_failed', __( 'The aggregate service-intelligence snapshot could not be saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		update_option( 'sc_ei_service_intelligence_last_snapshot', array( 'generated_at' => $payload['generated_at'], 'range_days' => $days, 'content_hash' => $hash, 'plugin_version' => SC_EI_VERSION, 'snapshot_id' => (int) $wpdb->insert_id ), false );
		SC_EI_Audit_Log::record( 'service_intelligence_snapshot_created', 'Authorized staff stored a privacy-safe aggregate service-intelligence snapshot.', array( 'range_days' => $days, 'content_hash' => $hash, 'schema' => SC_EI_Service_Intelligence_Schema::SNAPSHOT_SCHEMA, 'personal_data' => false ), null, null, $actor_user_id ?: null );
		return array_merge( $data, array( 'id' => (int) $wpdb->insert_id ) );
	}

	public static function create_finding( array $input, int $actor_user_id ) {
		global $wpdb;
		$evidence = is_array( $input['evidence'] ?? null ) ? $input['evidence'] : array();
		if ( ! SC_EI_Service_Intelligence_Schema::evidence_is_aggregate( $evidence ) ) {
			return new WP_Error( 'service_intelligence_personal_data_rejected', __( 'Service-intelligence evidence must contain aggregate, nonpersonal data only.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$cohort = absint( $input['cohort_count'] ?? 0 );
		$minimum = max( 2, absint( SC_EI_Analytics_Repository::settings()['analytics_minimum_cohort'] ?? 5 ) );
		if ( $cohort > 0 && $cohort < $minimum ) {
			return new WP_Error( 'service_intelligence_small_cohort_rejected', __( 'Small cohorts cannot be stored as service-intelligence findings.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Service_Intelligence_Schema::default_settings() );
		$review_due = gmdate( 'Y-m-d H:i:s', time() + max( 1, absint( $settings['analytics_finding_review_days'] ?? 14 ) ) * DAY_IN_SECONDS );
		$evidence_json = wp_json_encode( $evidence, JSON_UNESCAPED_SLASHES );
		$data = array(
			'public_id'       => wp_generate_uuid4(),
			'schema'          => SC_EI_Service_Intelligence_Schema::FINDING_SCHEMA,
			'finding_type'    => SC_EI_Service_Intelligence_Schema::sanitize_enum( (string) ( $input['finding_type'] ?? 'service_demand' ), SC_EI_Service_Intelligence_Schema::finding_types(), 'service_demand' ),
			'severity'        => SC_EI_Service_Intelligence_Schema::sanitize_enum( (string) ( $input['severity'] ?? 'watch' ), SC_EI_Service_Intelligence_Schema::severities(), 'watch' ),
			'status'          => 'candidate',
			'title'           => sanitize_text_field( (string) ( $input['title'] ?? '' ) ),
			'service_key'     => SC_EI_Service_Intelligence_Schema::normalize_dimension( (string) ( $input['service_key'] ?? '' ) ),
			'product_key'     => SC_EI_Service_Intelligence_Schema::normalize_dimension( (string) ( $input['product_key'] ?? '' ) ),
			'component_key'   => SC_EI_Service_Intelligence_Schema::normalize_dimension( (string) ( $input['component_key'] ?? '' ) ),
			'period_start'    => self::sanitize_datetime( (string) ( $input['period_start'] ?? $now ) ),
			'period_end'      => self::sanitize_datetime( (string) ( $input['period_end'] ?? $now ) ),
			'cohort_count'    => $cohort,
			'metric_value'    => is_numeric( $input['metric_value'] ?? null ) ? (float) $input['metric_value'] : null,
			'metric_unit'     => sanitize_key( (string) ( $input['metric_unit'] ?? 'count' ) ),
			'evidence_json'   => $evidence_json,
			'evidence_hash'   => hash( 'sha256', $evidence_json ),
			'owner_user_id'   => absint( $input['owner_user_id'] ?? 0 ) ?: null,
			'review_due_at'   => $review_due,
			'action_summary'  => '',
			'decision_note'   => '',
			'row_version'     => 0,
			'created_by'      => $actor_user_id ?: null,
			'reviewed_by'     => null,
			'reviewed_at'     => null,
			'closed_at'       => null,
			'created_at'      => $now,
			'updated_at'      => $now,
		);
		if ( '' === $data['title'] ) {
			return new WP_Error( 'service_intelligence_title_required', __( 'Enter a finding title.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( false === $wpdb->insert( SC_EI_Database::table( 'service_intelligence_findings' ), $data, self::formats( $data, array( 'cohort_count','owner_user_id','row_version','created_by','reviewed_by' ), array( 'metric_value' ) ) ) ) {
			return new WP_Error( 'service_intelligence_save_failed', __( 'The service-intelligence finding could not be saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$id = (int) $wpdb->insert_id;
		if ( ! self::event( $id, 'finding_created', '', 'candidate', $actor_user_id, array( 'evidence_hash' => $data['evidence_hash'] ) ) ) {
			$wpdb->delete( SC_EI_Database::table( 'service_intelligence_findings' ), array( 'id' => $id ), array( '%d' ) );
			return new WP_Error( 'service_intelligence_event_failed', __( 'The finding audit event could not be saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		return self::find( $id );
	}

	public static function transition_finding( int $finding_id, string $status, string $confirmation, string $decision_note, string $action_summary, int $actor_user_id ) {
		global $wpdb;
		$current = self::find( $finding_id );
		if ( ! $current ) {
			return new WP_Error( 'service_intelligence_finding_missing', __( 'The service-intelligence finding could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$status = SC_EI_Service_Intelligence_Schema::sanitize_enum( $status, SC_EI_Service_Intelligence_Schema::finding_statuses(), (string) $current['status'] );
		$expected = strtoupper( $status . ' ' . $current['public_id'] );
		if ( ! hash_equals( $expected, strtoupper( trim( $confirmation ) ) ) ) {
			return new WP_Error( 'service_intelligence_confirmation_failed', sprintf( __( 'Type %s to confirm.', 'sustainable-catalyst-engagement-intake' ), $expected ) );
		}
		$now = current_time( 'mysql', true );
		$data = array(
			'status'         => $status,
			'decision_note'  => sanitize_textarea_field( $decision_note ),
			'action_summary' => sanitize_textarea_field( $action_summary ),
			'row_version'    => absint( $current['row_version'] ) + 1,
			'reviewed_by'    => $actor_user_id ?: null,
			'reviewed_at'    => $now,
			'closed_at'      => in_array( $status, array( 'closed', 'dismissed' ), true ) ? $now : null,
			'updated_at'     => $now,
		);
		$updated = $wpdb->update( SC_EI_Database::table( 'service_intelligence_findings' ), $data, array( 'id' => $finding_id, 'row_version' => absint( $current['row_version'] ) ), self::formats( $data, array( 'row_version','reviewed_by' ) ), array( '%d','%d' ) );
		if ( 1 !== $updated ) {
			return new WP_Error( 'service_intelligence_transition_conflict', __( 'The finding changed before the review was saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( ! self::event( $finding_id, 'finding_transitioned', (string) $current['status'], $status, $actor_user_id, array( 'decision_note' => $data['decision_note'], 'action_summary' => $data['action_summary'] ) ) ) {
			$wpdb->update(
				SC_EI_Database::table( 'service_intelligence_findings' ),
				array(
					'status'         => (string) $current['status'],
					'decision_note'  => (string) $current['decision_note'],
					'action_summary' => (string) $current['action_summary'],
					'row_version'    => absint( $current['row_version'] ),
					'reviewed_by'    => $current['reviewed_by'] ? absint( $current['reviewed_by'] ) : null,
					'reviewed_at'    => $current['reviewed_at'],
					'closed_at'      => $current['closed_at'],
					'updated_at'     => (string) $current['updated_at'],
				),
				array( 'id' => $finding_id, 'row_version' => absint( $current['row_version'] ) + 1 ),
				array( '%s','%s','%s','%d','%d','%s','%s','%s' ),
				array( '%d','%d' )
			);
			return new WP_Error( 'service_intelligence_event_failed', __( 'The finding audit event could not be saved; the transition was rolled back.', 'sustainable-catalyst-engagement-intake' ) );
		}
		return self::find( $finding_id );
	}

	public static function findings( int $limit = 100, string $status = '' ): array {
		global $wpdb;
		$table = SC_EI_Database::table( 'service_intelligence_findings' );
		$limit = max( 1, min( 500, $limit ) );
		if ( $status ) {
			return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status = %s ORDER BY created_at DESC LIMIT %d", sanitize_key( $status ), $limit ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d", $limit ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function find( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'service_intelligence_findings' ) . ' WHERE id = %d', $id ), ARRAY_A );
		return is_array( $row ) ? $row : null;
	}

	public static function events( int $finding_id ): array {
		global $wpdb;
		return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'service_intelligence_events' ) . ' WHERE finding_id = %d ORDER BY created_at ASC, id ASC', $finding_id ), ARRAY_A );
	}

	public static function metrics(): array {
		global $wpdb;
		$table = SC_EI_Database::table( 'service_intelligence_findings' );
		$now = current_time( 'mysql', true );
		return array(
			'total'               => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'candidate'           => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'candidate'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'reviewing'           => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'reviewing'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'actioned'            => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status = 'actioned'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'overdue_review'      => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE review_due_at < %s AND status IN ('candidate','reviewing')", $now ) ),
			'critical_unresolved' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE severity = 'critical' AND status NOT IN ('closed','dismissed')" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	public static function operational_blockers(): array {
		$metrics = self::metrics();
		return array_filter(
			array(
				'overdue_service_intelligence_reviews' => absint( $metrics['overdue_review'] ?? 0 ),
				'critical_unresolved_findings'         => absint( $metrics['critical_unresolved'] ?? 0 ),
			),
			static fn( int $value ): bool => $value > 0
		);
	}

	public static function latest_snapshot_evidence(): array {
		$latest = get_option( 'sc_ei_service_intelligence_last_snapshot', array() );
		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Service_Intelligence_Schema::default_settings() );
		$fresh_days = max( 1, absint( $settings['analytics_snapshot_fresh_days'] ?? 7 ) );
		$generated = (string) ( $latest['generated_at'] ?? '' );
		$timestamp = $generated ? strtotime( $generated . ' UTC' ) : false;
		$fresh = $timestamp && $timestamp >= time() - $fresh_days * DAY_IN_SECONDS && (string) ( $latest['plugin_version'] ?? '' ) === SC_EI_VERSION;
		return array( 'passed' => (bool) $fresh, 'detail' => $fresh ? $generated . ' UTC' : 'No current v' . SC_EI_VERSION . ' service-intelligence snapshot within ' . $fresh_days . ' days.', 'snapshot' => is_array( $latest ) ? $latest : array() );
	}

	public static function prune_closed_findings(): int {
		global $wpdb;
		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Service_Intelligence_Schema::default_settings() );
		$days = max( 90, absint( $settings['analytics_intelligence_retention_days'] ?? 730 ) );
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
		$findings = SC_EI_Database::table( 'service_intelligence_findings' );
		$events = SC_EI_Database::table( 'service_intelligence_events' );
		$ids = array_map( 'absint', (array) $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$findings} WHERE status IN ('closed','dismissed') AND closed_at IS NOT NULL AND closed_at < %s", $cutoff ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( $ids as $id ) {
			$wpdb->delete( $events, array( 'finding_id' => $id ), array( '%d' ) );
			$wpdb->delete( $findings, array( 'id' => $id ), array( '%d' ) );
		}
		return count( $ids );
	}

	public static function cleanup_validation_records( string $prefix = '[TEST]' ): void {
		global $wpdb;
		$findings = SC_EI_Database::table( 'service_intelligence_findings' );
		$events = SC_EI_Database::table( 'service_intelligence_events' );
		$ids = (array) $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$findings} WHERE title LIKE %s", $wpdb->esc_like( $prefix ) . '%' ) );
		foreach ( $ids as $id ) {
			$wpdb->delete( $events, array( 'finding_id' => absint( $id ) ), array( '%d' ) );
			$wpdb->delete( $findings, array( 'id' => absint( $id ) ), array( '%d' ) );
		}
	}

	private static function event( int $finding_id, string $event_type, string $from_status, string $to_status, int $actor_user_id, array $context ): bool {
		global $wpdb;
		$result = $wpdb->insert(
			SC_EI_Database::table( 'service_intelligence_events' ),
			array(
				'public_id'   => wp_generate_uuid4(),
				'finding_id'  => $finding_id,
				'event_type'  => sanitize_key( $event_type ),
				'from_status' => sanitize_key( $from_status ),
				'to_status'   => sanitize_key( $to_status ),
				'actor_type'  => $actor_user_id ? 'staff' : 'system',
				'actor_id'    => $actor_user_id ?: null,
				'context_json'=> wp_json_encode( $context, JSON_UNESCAPED_SLASHES ),
				'created_at'  => current_time( 'mysql', true ),
			),
			array( '%s','%d','%s','%s','%s','%s','%d','%s','%s' )
		);
		return false !== $result;
	}

	private static function group_query( string $sql, string $from, int $minimum ): array {
		global $wpdb;
		$rows = (array) $wpdb->get_results( $wpdb->prepare( $sql, $from ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $rows as &$row ) {
			$row['count'] = (int) $row['count'];
			$row['suppressed'] = SC_EI_Analytics_Schema::suppress( $row['count'], $minimum );
			if ( $row['suppressed'] ) {
				$row['label'] = 'Small cohort';
				$row['count'] = null;
			}
		}
		return $rows;
	}

	private static function weekly_series( string $from, int $minimum ): array {
		global $wpdb;
		$i = SC_EI_Database::table( 'inquiries' );
		$s = SC_EI_Database::table( 'support_cases' );
		$e = SC_EI_Database::table( 'engagements' );
		$rows = array();
		foreach ( array( 'inquiries' => $i, 'support_cases' => $s, 'engagements' => $e ) as $key => $table ) {
			$result = (array) $wpdb->get_results( $wpdb->prepare( "SELECT DATE_FORMAT(created_at, '%%x-W%%v') period, COUNT(*) count FROM {$table} WHERE created_at >= %s GROUP BY period ORDER BY period ASC", $from ), ARRAY_A );
			foreach ( $result as $row ) {
				$period = (string) $row['period'];
				$rows[ $period ]['period'] = $period;
				$count = (int) $row['count'];
				$rows[ $period ][ $key ] = SC_EI_Analytics_Schema::suppress( $count, $minimum ) ? null : $count;
			}
		}
		ksort( $rows );
		return array_values( $rows );
	}

	private static function median( array $values ): ?float {
		$values = array_values( array_filter( array_map( 'floatval', $values ), static fn( float $value ): bool => $value >= 0 ) );
		if ( ! $values ) return null;
		sort( $values );
		$count = count( $values );
		$middle = intdiv( $count, 2 );
		return round( $count % 2 ? $values[ $middle ] : ( $values[ $middle - 1 ] + $values[ $middle ] ) / 2, 1 );
	}

	private static function sanitize_datetime( string $value ): string {
		$timestamp = strtotime( $value . ' UTC' );
		return $timestamp ? gmdate( 'Y-m-d H:i:s', $timestamp ) : current_time( 'mysql', true );
	}

	private static function formats( array $data, array $integers = array(), array $floats = array() ): array {
		return array_map( static function ( string $key ) use ( $integers, $floats ): string {
			if ( in_array( $key, $integers, true ) ) return '%d';
			if ( in_array( $key, $floats, true ) ) return '%f';
			return '%s';
		}, array_keys( $data ) );
	}
}
