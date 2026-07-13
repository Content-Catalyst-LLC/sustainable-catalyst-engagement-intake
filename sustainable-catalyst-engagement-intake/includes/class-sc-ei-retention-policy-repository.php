<?php
/**
 * Versioned retention policy storage.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Retention_Policy_Repository {

	public static function seed_defaults(): void {
		global $wpdb;

		$table = SC_EI_Database::table( 'retention_policies' );
		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Privacy_Schema::default_settings() );
		$day_overrides = array(
			'unaccepted_inquiry'  => absint( $settings['default_unaccepted_retention_days'] ?? 365 ),
			'withdrawn_inquiry'   => absint( $settings['withdrawn_retention_days'] ?? 30 ),
			'closed_inquiry'      => absint( $settings['closed_retention_days'] ?? 365 ),
			'accepted_inquiry'    => absint( $settings['accepted_retention_days'] ?? 2555 ),
			'private_attachment'  => absint( $settings['attachment_retention_days'] ?? 180 ),
			'communication_content'=> absint( $settings['communication_retention_days'] ?? 730 ),
		);

		foreach ( SC_EI_Privacy_Schema::default_policies() as $key => $policy ) {
			$exists = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE policy_key = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$key
				)
			);
			if ( $exists ) {
				continue;
			}
			$now = current_time( 'mysql', true );
			$data = array(
				'policy_key'     => sanitize_key( $key ),
				'version'        => 1,
				'name'           => sanitize_text_field( $policy['name'] ),
				'target_type'    => SC_EI_Privacy_Schema::sanitize_choice( $policy['target_type'], SC_EI_Privacy_Schema::target_types(), 'inquiry' ),
				'status_scope'   => wp_json_encode( SC_EI_Privacy_Schema::sanitize_status_scope( $policy['status_scope'] ) ),
				'retention_days' => max( 1, min( 36500, $day_overrides[ $key ] ?? absint( $policy['retention_days'] ) ) ),
				'anchor_field'   => SC_EI_Privacy_Schema::sanitize_choice( $policy['anchor_field'], SC_EI_Privacy_Schema::anchor_fields(), 'created_at' ),
				'action_type'    => SC_EI_Privacy_Schema::sanitize_choice( $policy['action_type'], SC_EI_Privacy_Schema::retention_action_types(), 'archive_only' ),
				'status'         => 'active',
				'legal_basis'    => SC_EI_Privacy_Schema::sanitize_choice( $policy['legal_basis'], SC_EI_Privacy_Schema::lawful_bases(), 'other' ),
				'description'    => sanitize_textarea_field( $policy['description'] ),
				'is_system'      => empty( $policy['is_system'] ) ? 0 : 1,
				'created_by'     => null,
				'created_at'     => $now,
				'updated_at'     => $now,
			);
			$integer = array( 'version', 'retention_days', 'is_system', 'created_by' );
			$formats = array_map(
				static fn( string $field ): string => in_array( $field, $integer, true ) ? '%d' : '%s',
				array_keys( $data )
			);
			$wpdb->insert( $table, $data, $formats );
		}
	}

	public static function active(): array {
		global $wpdb;

		$table = SC_EI_Database::table( 'retention_policies' );
		$sql = "SELECT p.*
			FROM {$table} p
			INNER JOIN (
				SELECT policy_key, MAX(version) AS version
				FROM {$table}
				WHERE status = 'active'
				GROUP BY policy_key
			) latest ON latest.policy_key = p.policy_key AND latest.version = p.version
			WHERE p.status = 'active'
			ORDER BY p.target_type ASC, p.name ASC";
		$rows = (array) $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = array();
		foreach ( $rows as $row ) {
			$row['status_scope_array'] = json_decode( (string) $row['status_scope'], true ) ?: array();
			$result[ $row['policy_key'] ] = $row;
		}
		return $result;
	}

	public static function find_active( string $key ): ?array {
		$policies = self::active();
		return $policies[ sanitize_key( $key ) ] ?? null;
	}

	public static function versions( string $key ): array {
		global $wpdb;

		$table = SC_EI_Database::table( 'retention_policies' );
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE policy_key = %s ORDER BY version DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				sanitize_key( $key )
			),
			ARRAY_A
		);
	}

	public static function create_version( array $input, int $actor_user_id ) {
		global $wpdb;

		$key = sanitize_key( (string) ( $input['policy_key'] ?? '' ) );
		if ( ! preg_match( '/^[a-z0-9][a-z0-9_-]{2,79}$/', $key ) ) {
			return new WP_Error( 'invalid_policy_key', __( 'Use a policy key containing 3–80 lowercase letters, numbers, hyphens, or underscores.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$name = sanitize_text_field( (string) ( $input['name'] ?? '' ) );
		$target_type = SC_EI_Privacy_Schema::sanitize_choice(
			(string) ( $input['target_type'] ?? 'inquiry' ),
			SC_EI_Privacy_Schema::target_types(),
			'inquiry'
		);
		$scope = SC_EI_Privacy_Schema::sanitize_status_scope( $input['status_scope'] ?? array() );
		$days = max( 1, min( 36500, absint( $input['retention_days'] ?? 365 ) ) );
		$anchor = SC_EI_Privacy_Schema::sanitize_choice(
			(string) ( $input['anchor_field'] ?? 'created_at' ),
			SC_EI_Privacy_Schema::anchor_fields(),
			'created_at'
		);
		$action = SC_EI_Privacy_Schema::sanitize_choice(
			(string) ( $input['action_type'] ?? 'archive_only' ),
			SC_EI_Privacy_Schema::retention_action_types(),
			'archive_only'
		);
		$basis = SC_EI_Privacy_Schema::sanitize_choice(
			(string) ( $input['legal_basis'] ?? 'other' ),
			SC_EI_Privacy_Schema::lawful_bases(),
			'other'
		);
		$description = sanitize_textarea_field( (string) ( $input['description'] ?? '' ) );
		if ( '' === $name || '' === $description ) {
			return new WP_Error( 'policy_fields_required', __( 'Policy name and description are required.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( 'inquiry' !== $target_type ) {
			$scope = array();
		}
		if ( 'attachment' === $target_type && 'retention_until' !== $anchor ) {
			return new WP_Error( 'attachment_anchor_invalid', __( 'Private-document policies must use the explicit retention date as their anchor.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$table = SC_EI_Database::table( 'retention_policies' );
		$version = 1 + (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(MAX(version),0) FROM {$table} WHERE policy_key = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$key
			)
		);
		$existing = self::find_active( $key );
		$now = current_time( 'mysql', true );
		$wpdb->query( 'START TRANSACTION' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->update(
			$table,
			array( 'status' => 'archived', 'updated_at' => $now ),
			array( 'policy_key' => $key, 'status' => 'active' ),
			array( '%s', '%s' ),
			array( '%s', '%s' )
		);
		$data = array(
			'policy_key'     => $key,
			'version'        => $version,
			'name'           => $name,
			'target_type'    => $target_type,
			'status_scope'   => wp_json_encode( $scope ),
			'retention_days' => $days,
			'anchor_field'   => $anchor,
			'action_type'    => $action,
			'status'         => 'active',
			'legal_basis'    => $basis,
			'description'    => $description,
			'is_system'      => $existing ? absint( $existing['is_system'] ) : 0,
			'created_by'     => $actor_user_id ?: null,
			'created_at'     => $now,
			'updated_at'     => $now,
		);
		$integer = array( 'version', 'retention_days', 'is_system', 'created_by' );
		$formats = array_map(
			static fn( string $field ): string => in_array( $field, $integer, true ) ? '%d' : '%s',
			array_keys( $data )
		);
		$inserted = $wpdb->insert( $table, $data, $formats );
		if ( false === $inserted ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			return new WP_Error( 'policy_save_failed', __( 'The retention policy version could not be saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		SC_EI_Audit_Log::record(
			'retention_policy_version_created',
			'Versioned retention policy created.',
			array(
				'policy_key' => $key,
				'version'    => $version,
				'target_type'=> $target_type,
				'action_type'=> $action,
				'days'       => $days,
			),
			null,
			null,
			$actor_user_id
		);

		return array( 'id' => (int) $wpdb->insert_id, 'policy_key' => $key, 'version' => $version );
	}
}
