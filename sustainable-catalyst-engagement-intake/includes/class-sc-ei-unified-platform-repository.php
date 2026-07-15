<?php
/**
 * Canonical engagement dossiers, cross-module relationships, timelines, and typed handoffs.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Unified_Platform_Repository {

	public const MIGRATION_KEY = 'v2_0_0_integrated_advisory_support_institutional_platform';
	public const SCHEMA_OPTION = 'sc_ei_unified_platform_schema_version';

	public static function register(): void {
		add_action( 'sc_ei_public_inquiry_created', array( __CLASS__, 'on_inquiry_created' ), 20, 1 );
		add_action( 'sc_ei_lifecycle_transitioned', array( __CLASS__, 'on_lifecycle_transitioned' ), 20, 1 );
		add_action( 'sc_ei_support_handoff_ingested', array( __CLASS__, 'on_support_handoff' ), 20, 1 );
		add_action( 'rest_api_init', array( __CLASS__, 'rest_routes' ) );
	}

	public static function maybe_upgrade(): void {
		$stored = (string) get_option( self::SCHEMA_OPTION, '' );
		if ( version_compare( $stored, SC_EI_UNIFIED_PLATFORM_SCHEMA_VERSION, '<' ) ) {
			SC_EI_Database::install();
		}
		self::record_migration( $stored );
		if ( ! in_array( false, SC_EI_Database::unified_platform_columns_exist(), true ) ) {
			update_option( self::SCHEMA_OPTION, SC_EI_UNIFIED_PLATFORM_SCHEMA_VERSION, false );
		}
	}

	public static function record_migration( string $from_schema = '' ) {
		global $wpdb;
		$table = SC_EI_Database::table( 'platform_migrations' );
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE migration_key = %s LIMIT 1", self::MIGRATION_KEY ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$columns = SC_EI_Database::unified_platform_columns_exist();
		$ok = ! in_array( false, $columns, true );
		$now = current_time( 'mysql', true );
		$data = array(
			'public_id'     => $existing['public_id'] ?? wp_generate_uuid4(),
			'migration_key' => self::MIGRATION_KEY,
			'from_version'  => sanitize_text_field( $from_schema ),
			'to_version'    => SC_EI_UNIFIED_PLATFORM_SCHEMA_VERSION,
			'status'        => $ok ? 'completed' : 'failed',
			'schema_hash'   => hash( 'sha256', wp_json_encode( array_keys( $columns ) ) ),
			'context_json'  => wp_json_encode(
				array(
					'release'                    => 'Integrated Advisory, Support, and Institutional Engagement Platform',
					'canonical_dossiers'         => true,
					'cross_module_relationships' => true,
					'unified_timeline'           => true,
					'typed_handoffs'             => SC_EI_Unified_Platform_Schema::HANDOFF_SCHEMA,
					'no_destructive_migration'   => true,
					'missing_contract_items'     => array_keys( array_filter( $columns, static fn( bool $value ): bool => ! $value ) ),
				),
				JSON_UNESCAPED_SLASHES
			),
			'started_at'    => $existing['started_at'] ?? $now,
			'completed_at'  => $ok ? $now : null,
			'error_code'    => $ok ? '' : 'unified_platform_schema_incomplete',
			'error_message' => $ok ? '' : 'The v2.0 unified platform database contract is incomplete.',
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
			return new WP_Error( 'unified_platform_migration_failed', __( 'The v2.0 platform migration journal could not be recorded.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( $ok ) {
			self::backfill( 1000, 0 );
		}
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function on_inquiry_created( array $record ): void {
		self::refresh_dossier( absint( $record['id'] ?? 0 ), 0, 'inquiry_created' );
	}

	public static function on_lifecycle_transitioned( int $inquiry_id ): void {
		self::refresh_dossier( $inquiry_id, 0, 'lifecycle_transitioned' );
	}

	public static function on_support_handoff( array $case ): void {
		self::refresh_dossier( absint( $case['inquiry_id'] ?? 0 ), 0, 'support_handoff_ingested' );
	}

	public static function refresh_dossier( int $inquiry_id, int $actor_user_id = 0, string $reason = 'manual_refresh' ) {
		global $wpdb;
		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $inquiry ) {
			return new WP_Error( 'dossier_inquiry_missing', __( 'The inquiry required for this engagement dossier was not found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$route = self::route_group( $inquiry );
		$phase = self::phase( $inquiry_id, $inquiry );
		$health = self::health( $inquiry_id, $inquiry );
		$relationships = self::entity_rows( $inquiry_id );
		$summary = array(
			'schema'          => SC_EI_Unified_Platform_Schema::DOSSIER_SCHEMA,
			'inquiry_id'      => $inquiry_id,
			'reference'       => (string) ( $inquiry['reference'] ?? '' ),
			'route_group'     => $route,
			'phase'           => $phase,
			'health_status'   => $health,
			'lifecycle_stage' => (string) ( $inquiry['lifecycle_stage'] ?? '' ),
			'entity_counts'   => array_count_values( array_column( $relationships, 'entity_type' ) ),
		);
		$content_hash = hash( 'sha256', wp_json_encode( $summary, JSON_UNESCAPED_SLASHES ) );
		$table = SC_EI_Database::table( 'engagement_dossiers' );
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE inquiry_id = %d LIMIT 1", $inquiry_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$now = current_time( 'mysql', true );
		$data = array(
			'public_id'            => $existing['public_id'] ?? wp_generate_uuid4(),
			'inquiry_id'           => $inquiry_id,
			'reference'            => sanitize_text_field( (string) ( $inquiry['reference'] ?? '' ) ),
			'route_group'          => $route,
			'phase'                => $phase,
			'health_status'        => $health,
			'owner_user_id'        => absint( $inquiry['lifecycle_owner_user_id'] ?? $inquiry['assigned_user_id'] ?? 0 ) ?: null,
			'sender_summary'       => sanitize_textarea_field( (string) ( $inquiry['sender_lifecycle_summary'] ?? '' ) ),
			'sender_next_step'     => sanitize_textarea_field( (string) ( $inquiry['next_action'] ?? '' ) ),
			'relationship_count'   => count( $relationships ),
			'activity_count'       => count( self::timeline( $inquiry_id, 500 ) ),
			'content_hash'         => $content_hash,
			'row_version'          => absint( $existing['row_version'] ?? 0 ) + 1,
			'last_refreshed_at'    => $now,
			'created_at'           => $existing['created_at'] ?? $now,
			'updated_at'           => $now,
		);
		$wpdb->query( 'START TRANSACTION' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ( $existing ) {
			$result = $wpdb->update( $table, $data, array( 'id' => absint( $existing['id'] ) ), self::formats( $data, array( 'inquiry_id', 'owner_user_id', 'relationship_count', 'activity_count', 'row_version' ) ), array( '%d' ) );
			$dossier_id = absint( $existing['id'] );
		} else {
			$result = $wpdb->insert( $table, $data, self::formats( $data, array( 'inquiry_id', 'owner_user_id', 'relationship_count', 'activity_count', 'row_version' ) ) );
			$dossier_id = (int) $wpdb->insert_id;
		}
		if ( false === $result || ! self::replace_relationships( $dossier_id, $inquiry_id, $relationships ) ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			SC_EI_Hardening_Repository::record_event( 'database', 'integrated_dossier_persistence_failed', 'critical', 'The canonical engagement dossier or one of its typed relationships could not be stored.', array( 'inquiry_id' => $inquiry_id, 'reason' => sanitize_key( $reason ) ) );
			return new WP_Error( 'dossier_persistence_failed', __( 'The integrated engagement dossier and its relationships could not be stored.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ( ! $existing || (string) ( $existing['content_hash'] ?? '' ) !== $content_hash ) {
			self::record_event( $dossier_id, $inquiry_id, 'dossier_refreshed', 'dossier', $dossier_id, 'internal', 'Integrated dossier refreshed after ' . sanitize_key( $reason ) . '.', $summary, $actor_user_id );
		}
		return self::find( $dossier_id );
	}

	public static function backfill( int $limit = 250, int $offset = 0 ): array {
		global $wpdb;
		$limit = max( 1, min( 5000, $limit ) );
		$offset = max( 0, $offset );
		$ids = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . SC_EI_Database::table( 'inquiries' ) . " WHERE privacy_status <> 'erased' ORDER BY id ASC LIMIT %d OFFSET %d", $limit, $offset ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$created = 0;
		$failed = 0;
		foreach ( (array) $ids as $id ) {
			$result = self::refresh_dossier( absint( $id ), 0, 'migration_backfill' );
			is_wp_error( $result ) ? ++$failed : ++$created;
		}
		return array( 'processed' => count( $ids ), 'refreshed' => $created, 'failed' => $failed );
	}

	public static function find( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'engagement_dossiers' ) . ' WHERE id = %d', $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $row ?: null;
	}

	public static function for_inquiry( int $inquiry_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'engagement_dossiers' ) . ' WHERE inquiry_id = %d LIMIT 1', $inquiry_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $row ?: null;
	}

	public static function dossiers( array $args = array() ): array {
		global $wpdb;
		$args = wp_parse_args( $args, array( 'route_group' => '', 'phase' => '', 'health_status' => '', 'search' => '', 'limit' => 200 ) );
		$where = array( '1=1' );
		$params = array();
		foreach ( array( 'route_group', 'phase', 'health_status' ) as $key ) {
			if ( '' !== (string) $args[ $key ] ) {
				$where[] = "{$key} = %s";
				$params[] = sanitize_key( (string) $args[ $key ] );
			}
		}
		if ( '' !== trim( (string) $args['search'] ) ) {
			$where[] = '(reference LIKE %s OR sender_summary LIKE %s OR sender_next_step LIKE %s)';
			$like = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			array_push( $params, $like, $like, $like );
		}
		$sql = 'SELECT * FROM ' . SC_EI_Database::table( 'engagement_dossiers' ) . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY updated_at DESC LIMIT %d';
		$params[] = max( 1, min( 500, absint( $args['limit'] ) ) );
		return $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	public static function dossier_export( int $dossier_id ): ?array {
		global $wpdb;
		$dossier = self::find( $dossier_id );
		if ( ! $dossier ) {
			return null;
		}
		$dossier['relationships'] = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'dossier_relationships' ) . ' WHERE dossier_id = %d ORDER BY entity_type, id', $dossier_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$dossier['timeline'] = self::timeline( absint( $dossier['inquiry_id'] ), 500 );
		$dossier['handoffs'] = $wpdb->get_results( $wpdb->prepare( 'SELECT public_id, schema, handoff_key, source_system, target_module, route_group, status, received_at, processed_at, error_code FROM ' . SC_EI_Database::table( 'platform_handoffs' ) . ' WHERE inquiry_id = %d ORDER BY received_at DESC LIMIT 100', absint( $dossier['inquiry_id'] ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $dossier;
	}

	public static function timeline( int $inquiry_id, int $limit = 200 ): array {
		global $wpdb;
		$events = array();
		$specs = array(
			array( 'dossier_events', 'occurred_at', "SELECT event_type AS type, summary, visibility, object_type, object_id, occurred_at AS occurred_at FROM %s WHERE inquiry_id = %d" ),
			array( 'lifecycle_events', 'occurred_at', "SELECT event_type AS type, CONCAT(from_stage,' → ',to_stage) AS summary, 'internal' AS visibility, 'lifecycle' AS object_type, id AS object_id, occurred_at AS occurred_at FROM %s WHERE inquiry_id = %d" ),
			array( 'support_case_events', 'occurred_at', "SELECT event_type AS type, CONCAT(from_stage,' → ',to_stage) AS summary, 'internal' AS visibility, 'support_case' AS object_type, support_case_id AS object_id, occurred_at AS occurred_at FROM %s WHERE inquiry_id = %d" ),
			array( 'workflow_events', 'created_at', "SELECT event_type AS type, CONCAT(from_status,' → ',to_status) AS summary, 'internal' AS visibility, object_type, object_id, created_at AS occurred_at FROM %s WHERE inquiry_id = %d" ),
			array( 'engagement_events', 'created_at', "SELECT event_type AS type, CONCAT(from_status,' → ',to_status) AS summary, 'internal' AS visibility, 'engagement' AS object_type, engagement_id AS object_id, created_at AS occurred_at FROM %s WHERE inquiry_id = %d" ),
			array( 'workspace_events', 'created_at', "SELECT event_type AS type, CONCAT(from_status,' → ',to_status) AS summary, 'internal' AS visibility, object_type, object_id, created_at AS occurred_at FROM %s WHERE inquiry_id = %d" ),
			array( 'billing_events', 'created_at', "SELECT event_type AS type, CONCAT(from_status,' → ',to_status) AS summary, 'internal' AS visibility, 'invoice' AS object_type, invoice_id AS object_id, created_at AS occurred_at FROM %s WHERE inquiry_id = %d" ),
			array( 'communications', 'occurred_at', "SELECT communication_type AS type, subject AS summary, IF(portal_visibility='published','sender','internal') AS visibility, 'communication' AS object_type, id AS object_id, occurred_at AS occurred_at FROM %s WHERE inquiry_id = %d AND deleted_at IS NULL" ),
		);
		foreach ( $specs as $spec ) {
			$table = SC_EI_Database::table( $spec[0] );
			$sql = sprintf( $spec[2], $table ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$rows = $wpdb->get_results( $wpdb->prepare( $sql, $inquiry_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( is_array( $rows ) ) {
				$events = array_merge( $events, $rows );
			}
		}
		usort( $events, static fn( array $a, array $b ): int => strcmp( (string) ( $b['occurred_at'] ?? '' ), (string) ( $a['occurred_at'] ?? '' ) ) );
		return array_slice( $events, 0, max( 1, min( 1000, $limit ) ) );
	}

	public static function ingest_handoff( array $data, int $actor_user_id ) {
		global $wpdb;
		$schema = sanitize_text_field( (string) ( $data['schema'] ?? '' ) );
		if ( SC_EI_Unified_Platform_Schema::HANDOFF_SCHEMA !== $schema ) {
			return new WP_Error( 'platform_handoff_schema_invalid', __( 'The platform handoff schema is not supported.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$source = SC_EI_Unified_Platform_Schema::sanitize_choice( (string) ( $data['source_system'] ?? '' ), SC_EI_Unified_Platform_Schema::source_systems(), 'manual' );
		$target = SC_EI_Unified_Platform_Schema::sanitize_choice( (string) ( $data['target_module'] ?? '' ), SC_EI_Unified_Platform_Schema::target_modules(), 'intake' );
		$payload = is_array( $data['payload'] ?? null ) ? $data['payload'] : array();
		if ( ! SC_EI_Unified_Platform_Schema::handoff_payload_is_safe( $payload ) ) {
			return new WP_Error( 'platform_handoff_private_data_rejected', __( 'Cross-product handoffs cannot contain sender identity, messages, files, credentials, or payment data.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$inquiry_id = absint( $data['inquiry_id'] ?? 0 );
		if ( $inquiry_id && ! SC_EI_Inquiry_Repository::find( $inquiry_id ) ) {
			return new WP_Error( 'platform_handoff_inquiry_missing', __( 'The related inquiry was not found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$handoff_key = sanitize_text_field( (string) ( $data['handoff_key'] ?? '' ) );
		if ( '' === $handoff_key ) {
			$handoff_key = hash( 'sha256', $source . '|' . $target . '|' . $inquiry_id . '|' . wp_json_encode( $payload, JSON_UNESCAPED_SLASHES ) );
		}
		$table = SC_EI_Database::table( 'platform_handoffs' );
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE handoff_key = %s LIMIT 1", $handoff_key ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $existing ) {
			return $existing;
		}
		$now = current_time( 'mysql', true );
		$payload_json = wp_json_encode( $payload, JSON_UNESCAPED_SLASHES );
		$row = array(
			'public_id'     => wp_generate_uuid4(),
			'schema'        => $schema,
			'handoff_key'   => $handoff_key,
			'source_system' => $source,
			'target_module' => $target,
			'inquiry_id'    => $inquiry_id ?: null,
			'route_group'   => SC_EI_Unified_Platform_Schema::sanitize_route_group( (string) ( $data['route_group'] ?? 'general' ) ),
			'status'        => 'accepted',
			'payload_json'  => $payload_json,
			'content_hash'  => hash( 'sha256', (string) $payload_json ),
			'received_by'   => $actor_user_id ?: null,
			'received_at'   => $now,
			'processed_at'  => $now,
			'error_code'    => '',
			'error_message' => '',
			'created_at'    => $now,
			'updated_at'    => $now,
		);
		$ok = $wpdb->insert( $table, $row, self::formats( $row, array( 'inquiry_id', 'received_by' ) ) );
		if ( false === $ok ) {
			$replayed = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE handoff_key = %s LIMIT 1", $handoff_key ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( $replayed ) {
				return $replayed;
			}
			SC_EI_Hardening_Repository::record_event( 'database', 'platform_handoff_store_failed', 'critical', 'A typed platform handoff receipt could not be stored.', array( 'source_system' => $source, 'target_module' => $target, 'inquiry_id' => $inquiry_id ) );
			return new WP_Error( 'platform_handoff_store_failed', __( 'The platform handoff receipt could not be stored.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$id = (int) $wpdb->insert_id;
		if ( $inquiry_id ) {
			$dossier = self::refresh_dossier( $inquiry_id, $actor_user_id, 'platform_handoff' );
			if ( ! is_wp_error( $dossier ) ) {
				self::record_event( absint( $dossier['id'] ), $inquiry_id, 'platform_handoff_accepted', 'handoff', $id, 'internal', 'Typed platform handoff accepted from ' . $source . ' to ' . $target . '.', array( 'handoff_key' => $handoff_key, 'content_hash' => $row['content_hash'] ), $actor_user_id );
			}
		}
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function dashboard(): array {
		global $wpdb;
		$table = SC_EI_Database::table( 'engagement_dossiers' );
		$counts = static function ( string $column ) use ( $wpdb, $table ): array {
			$rows = $wpdb->get_results( "SELECT {$column} AS label, COUNT(*) AS total FROM {$table} GROUP BY {$column} ORDER BY total DESC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$result = array();
			foreach ( (array) $rows as $row ) {
				$result[ (string) $row['label'] ] = absint( $row['total'] );
			}
			return $result;
		};
		$integrity = self::integrity();
		return array(
			'total'         => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'by_route'      => $counts( 'route_group' ),
			'by_phase'      => $counts( 'phase' ),
			'by_health'     => $counts( 'health_status' ),
			'integrity'     => $integrity,
			'pending_handoffs' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . SC_EI_Database::table( 'platform_handoffs' ) . " WHERE status = 'pending'" ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);
	}

	public static function integrity(): array {
		global $wpdb;
		$inquiries = SC_EI_Database::table( 'inquiries' );
		$dossiers = SC_EI_Database::table( 'engagement_dossiers' );
		$relationships = SC_EI_Database::table( 'dossier_relationships' );
		$handoffs = SC_EI_Database::table( 'platform_handoffs' );
		return array(
			'missing_dossiers' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$inquiries} i LEFT JOIN {$dossiers} d ON d.inquiry_id=i.id WHERE i.privacy_status <> 'erased' AND d.id IS NULL" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'orphan_dossiers' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$dossiers} d LEFT JOIN {$inquiries} i ON i.id=d.inquiry_id WHERE i.id IS NULL" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'orphan_relationships' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$relationships} r LEFT JOIN {$dossiers} d ON d.id=r.dossier_id WHERE d.id IS NULL" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'failed_handoffs' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$handoffs} WHERE status = 'rejected' OR error_code <> ''" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'stale_handoffs' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$handoffs} WHERE status = 'pending' AND received_at < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 1 DAY)" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	public static function export_for_inquiry( int $inquiry_id ): array {
		global $wpdb;
		$dossier = self::for_inquiry( $inquiry_id );
		return array(
			'dossier'       => $dossier ?: array(),
			'relationships' => $dossier ? (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'dossier_relationships' ) . ' WHERE dossier_id = %d ORDER BY id', absint( $dossier['id'] ) ), ARRAY_A ) : array(), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'events'        => $dossier ? (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'dossier_events' ) . ' WHERE dossier_id = %d ORDER BY occurred_at, id', absint( $dossier['id'] ) ), ARRAY_A ) : array(), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'handoffs'      => (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'platform_handoffs' ) . ' WHERE inquiry_id = %d ORDER BY received_at, id', $inquiry_id ), ARRAY_A ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);
	}

	public static function redact_for_privacy( int $inquiry_id, string $now ): bool {
		global $wpdb;
		$dossier = self::for_inquiry( $inquiry_id );
		$ok = true;
		if ( $dossier ) {
			$dossier_id = absint( $dossier['id'] );
			$ok = false !== $wpdb->query( $wpdb->prepare( "UPDATE " . SC_EI_Database::table( 'engagement_dossiers' ) . " SET route_group='general', phase='archived', health_status='archived', owner_user_id=NULL, sender_summary=%s, sender_next_step='', content_hash=%s, updated_at=%s WHERE id=%d", '[Dossier content erased through Privacy and Retention Center.]', hash( 'sha256', 'erased|' . $inquiry_id . '|' . $now ), $now, $dossier_id ) ) && $ok; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$ok = false !== $wpdb->query( $wpdb->prepare( "UPDATE " . SC_EI_Database::table( 'dossier_relationships' ) . " SET sender_visible=0, metadata_json='{}', updated_at=%s WHERE dossier_id=%d", $now, $dossier_id ) ) && $ok; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$ok = false !== $wpdb->query( $wpdb->prepare( "UPDATE " . SC_EI_Database::table( 'dossier_events' ) . " SET visibility='internal', summary=%s, context_json='{}', actor_user_id=NULL WHERE dossier_id=%d", '[Dossier event content erased]', $dossier_id ) ) && $ok; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		$ok = false !== $wpdb->query( $wpdb->prepare( "UPDATE " . SC_EI_Database::table( 'platform_handoffs' ) . " SET payload_json='{}', content_hash=%s, error_message=NULL, updated_at=%s WHERE inquiry_id=%d", hash( 'sha256', '{}' ), $now, $inquiry_id ) ) && $ok; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $ok;
	}

	public static function cleanup_for_inquiry( int $inquiry_id ): void {
		global $wpdb;
		$dossier = self::for_inquiry( $inquiry_id );
		if ( $dossier ) {
			$wpdb->delete( SC_EI_Database::table( 'dossier_relationships' ), array( 'dossier_id' => absint( $dossier['id'] ) ), array( '%d' ) );
			$wpdb->delete( SC_EI_Database::table( 'dossier_events' ), array( 'dossier_id' => absint( $dossier['id'] ) ), array( '%d' ) );
			$wpdb->delete( SC_EI_Database::table( 'engagement_dossiers' ), array( 'id' => absint( $dossier['id'] ) ), array( '%d' ) );
		}
		$wpdb->delete( SC_EI_Database::table( 'platform_handoffs' ), array( 'inquiry_id' => $inquiry_id ), array( '%d' ) );
	}

	public static function rest_routes(): void {
		register_rest_route( 'sc-engagement-intake/v2', '/status', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'rest_status' ), 'permission_callback' => static fn(): bool => current_user_can( 'sc_intake_view_platform' ) ) );
		register_rest_route( 'sc-engagement-intake/v2', '/dossiers', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'rest_dossiers' ), 'permission_callback' => static fn(): bool => current_user_can( 'sc_intake_view_platform' ) ) );
		register_rest_route( 'sc-engagement-intake/v2', '/dossiers/(?P<id>\d+)', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'rest_dossier' ), 'permission_callback' => static fn(): bool => current_user_can( 'sc_intake_view_platform' ), 'args' => array( 'id' => array( 'sanitize_callback' => 'absint' ) ) ) );
		register_rest_route( 'sc-engagement-intake/v2', '/handoffs', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( __CLASS__, 'rest_handoff' ), 'permission_callback' => static fn(): bool => current_user_can( 'sc_intake_manage_platform' ) ) );
	}

	public static function rest_status(): WP_REST_Response {
		return new WP_REST_Response( array( 'ok' => 0 === array_sum( self::integrity() ), 'version' => SC_EI_VERSION, 'schema' => SC_EI_UNIFIED_PLATFORM_SCHEMA_VERSION, 'dashboard' => self::dashboard() ) );
	}

	public static function rest_dossiers( WP_REST_Request $request ): WP_REST_Response {
		return new WP_REST_Response( self::dossiers( array( 'route_group' => $request->get_param( 'route_group' ), 'phase' => $request->get_param( 'phase' ), 'health_status' => $request->get_param( 'health_status' ), 'search' => $request->get_param( 'search' ), 'limit' => $request->get_param( 'limit' ) ?: 200 ) ) );
	}

	public static function rest_dossier( WP_REST_Request $request ) {
		$result = self::dossier_export( absint( $request['id'] ) );
		return $result ? new WP_REST_Response( $result ) : new WP_Error( 'dossier_not_found', __( 'Engagement dossier not found.', 'sustainable-catalyst-engagement-intake' ), array( 'status' => 404 ) );
	}

	public static function rest_handoff( WP_REST_Request $request ) {
		$result = self::ingest_handoff( (array) $request->get_json_params(), get_current_user_id() );
		return is_wp_error( $result ) ? $result : new WP_REST_Response( $result, 201 );
	}

	private static function route_group( array $inquiry ): string {
		$haystack = strtolower( implode( ' ', array( $inquiry['inquiry_type'] ?? '', $inquiry['service_interest'] ?? '', $inquiry['conversion_route'] ?? '', $inquiry['source_page'] ?? '' ) ) );
		foreach ( array( 'support' => array( 'support', 'technical issue', 'bug' ), 'advisory' => array( 'advisory', 'consulting', 'assurance' ), 'institutional' => array( 'institution', 'partnership', 'organization' ), 'research' => array( 'research', 'collaboration' ), 'media' => array( 'media', 'press', 'interview' ), 'technical' => array( 'technical', 'platform', 'implementation' ) ) as $group => $terms ) {
			foreach ( $terms as $term ) {
				if ( false !== strpos( $haystack, $term ) ) {
					return $group;
				}
			}
		}
		return 'general';
	}

	private static function phase( int $inquiry_id, array $inquiry ): string {
		global $wpdb;
		if ( 'erased' === (string) ( $inquiry['privacy_status'] ?? '' ) || in_array( (string) ( $inquiry['status'] ?? '' ), array( 'closed', 'archived' ), true ) ) {
			return 'archived';
		}
		$latest = static function ( string $table, string $status_column = 'status' ) use ( $wpdb, $inquiry_id ): string {
			return (string) $wpdb->get_var( $wpdb->prepare( 'SELECT ' . $status_column . ' FROM ' . SC_EI_Database::table( $table ) . ' WHERE inquiry_id = %d ORDER BY id DESC LIMIT 1', $inquiry_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		};
		$invoice = $latest( 'invoices' );
		if ( in_array( $invoice, array( 'issued', 'partially_paid', 'paid', 'overdue', 'disputed' ), true ) ) return 'billing';
		$workspace = $latest( 'client_workspaces' );
		if ( in_array( $workspace, array( 'active', 'paused', 'completed' ), true ) ) return 'delivery';
		$engagement = $latest( 'engagements' );
		if ( in_array( $engagement, array( 'active', 'paused', 'completed' ), true ) ) return 'engagement';
		$proposal = $latest( 'proposals' );
		if ( $proposal && ! in_array( $proposal, array( 'draft', 'withdrawn', 'declined', 'expired' ), true ) ) return 'proposal';
		$meeting = $latest( 'meeting_offers' );
		if ( in_array( $meeting, array( 'published', 'accepted', 'scheduled', 'completed' ), true ) ) return 'scheduling';
		$support = $latest( 'support_cases', 'workflow_stage' );
		if ( $support && ! in_array( $support, array( 'resolved', 'closed' ), true ) ) return 'support_resolution';
		if ( in_array( (string) ( $inquiry['lifecycle_stage'] ?? '' ), array( 'under_review', 'needs_information', 'qualified' ), true ) ) return 'review';
		if ( in_array( (string) ( $inquiry['lifecycle_stage'] ?? '' ), array( 'completed', 'declined', 'archived' ), true ) ) return 'complete';
		return 'intake';
	}

	private static function health( int $inquiry_id, array $inquiry ): string {
		global $wpdb;
		if ( 'erased' === (string) ( $inquiry['privacy_status'] ?? '' ) ) return 'archived';
		$overdue = ! empty( $inquiry['next_action_at'] ) && strtotime( (string) $inquiry['next_action_at'] . ' UTC' ) < time();
		$high_support = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . SC_EI_Database::table( 'support_cases' ) . " WHERE inquiry_id = %d AND severity IN ('high','critical') AND workflow_stage NOT IN ('resolved','closed')", $inquiry_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$overdue_invoice = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . SC_EI_Database::table( 'invoices' ) . " WHERE inquiry_id = %d AND status = 'overdue'", $inquiry_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( $high_support > 0 || $overdue_invoice > 0 ) return 'blocked';
		return $overdue ? 'attention' : 'healthy';
	}

	private static function entity_rows( int $inquiry_id ): array {
		global $wpdb;
		$rows = array();
		$specs = array(
			'support_case' => array( 'support_cases', 'public_id', 'workflow_stage', 'case_number' ),
			'meeting' => array( 'meeting_offers', 'public_id', 'status', 'offer_number' ),
			'proposal' => array( 'proposals', 'public_id', 'status', 'proposal_number' ),
			'statement_of_work' => array( 'statements_of_work', 'public_id', 'status', 'sow_number' ),
			'engagement' => array( 'engagements', 'public_id', 'status', 'engagement_number' ),
			'workspace' => array( 'client_workspaces', 'public_id', 'status', 'workspace_number' ),
			'invoice' => array( 'invoices', 'public_id', 'status', 'invoice_number' ),
			'attachment' => array( 'attachments', 'public_id', 'validation_status', 'original_name' ),
			'communication' => array( 'communications', 'public_id', 'status', 'subject' ),
			'lifecycle_task' => array( 'lifecycle_tasks', 'public_id', 'task_status', 'task_title' ),
		);
		foreach ( $specs as $entity_type => $spec ) {
			$table = SC_EI_Database::table( $spec[0] );
			$sql = "SELECT id, {$spec[1]} AS public_id, {$spec[2]} AS entity_status, {$spec[3]} AS label FROM {$table} WHERE inquiry_id = %d ORDER BY id";
			foreach ( (array) $wpdb->get_results( $wpdb->prepare( $sql, $inquiry_id ), ARRAY_A ) as $row ) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$rows[] = array( 'entity_type' => $entity_type, 'entity_id' => absint( $row['id'] ), 'entity_public_id' => sanitize_text_field( (string) $row['public_id'] ), 'relation_type' => 'belongs_to', 'entity_status' => sanitize_key( (string) $row['entity_status'] ), 'sender_visible' => 0, 'metadata' => array( 'label' => sanitize_text_field( (string) $row['label'] ) ) );
			}
		}
		return $rows;
	}

	private static function replace_relationships( int $dossier_id, int $inquiry_id, array $relationships ): bool {
		global $wpdb;
		$table = SC_EI_Database::table( 'dossier_relationships' );
		if ( false === $wpdb->delete( $table, array( 'dossier_id' => $dossier_id ), array( '%d' ) ) ) {
			return false;
		}
		$now = current_time( 'mysql', true );
		foreach ( $relationships as $relationship ) {
			$row = array(
				'public_id'        => wp_generate_uuid4(),
				'dossier_id'       => $dossier_id,
				'inquiry_id'       => $inquiry_id,
				'entity_type'      => sanitize_key( $relationship['entity_type'] ),
				'entity_id'        => absint( $relationship['entity_id'] ),
				'entity_public_id' => sanitize_text_field( $relationship['entity_public_id'] ),
				'relation_type'    => sanitize_key( $relationship['relation_type'] ),
				'entity_status'    => sanitize_key( $relationship['entity_status'] ),
				'sender_visible'   => empty( $relationship['sender_visible'] ) ? 0 : 1,
				'metadata_json'    => wp_json_encode( $relationship['metadata'] ?? array(), JSON_UNESCAPED_SLASHES ),
				'created_at'       => $now,
				'updated_at'       => $now,
			);
			if ( false === $wpdb->insert( $table, $row, self::formats( $row, array( 'dossier_id', 'inquiry_id', 'entity_id', 'sender_visible' ) ) ) ) {
				return false;
			}
		}
		return true;
	}

	private static function record_event( int $dossier_id, int $inquiry_id, string $event_type, string $object_type, int $object_id, string $visibility, string $summary, array $context, int $actor_user_id ): bool {
		global $wpdb;
		$row = array(
			'public_id'     => wp_generate_uuid4(),
			'dossier_id'    => $dossier_id,
			'inquiry_id'    => $inquiry_id,
			'event_type'    => sanitize_key( $event_type ),
			'object_type'   => sanitize_key( $object_type ),
			'object_id'     => $object_id ?: null,
			'visibility'    => in_array( $visibility, array( 'internal', 'sender' ), true ) ? $visibility : 'internal',
			'summary'       => sanitize_text_field( $summary ),
			'context_json'  => wp_json_encode( $context, JSON_UNESCAPED_SLASHES ),
			'actor_user_id' => $actor_user_id ?: null,
			'occurred_at'   => current_time( 'mysql', true ),
		);
		return false !== $wpdb->insert( SC_EI_Database::table( 'dossier_events' ), $row, self::formats( $row, array( 'dossier_id', 'inquiry_id', 'object_id', 'actor_user_id' ) ) );
	}

	private static function formats( array $data, array $integer_keys = array() ): array {
		return array_map( static fn( string $key ): string => in_array( $key, $integer_keys, true ) ? '%d' : '%s', array_keys( $data ) );
	}
}
