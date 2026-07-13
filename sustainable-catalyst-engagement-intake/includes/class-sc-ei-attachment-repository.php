<?php
/**
 * Private attachment metadata persistence.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Attachment_Repository {

	public static function create( array $input ): int {
		global $wpdb;

		$now = current_time( 'mysql', true );
		$data = array(
			'inquiry_id'               => absint( $input['inquiry_id'] ?? 0 ),
			'public_id'                => sanitize_text_field( $input['public_id'] ?? wp_generate_uuid4() ),
			'original_name'            => sanitize_file_name( $input['original_name'] ?? '' ),
			'stored_name'              => sanitize_file_name( $input['stored_name'] ?? '' ),
			'relative_path'            => sanitize_text_field( $input['relative_path'] ?? '' ),
			'mime_type'                => sanitize_mime_type( $input['mime_type'] ?? 'application/octet-stream' ),
			'detected_mime'            => sanitize_mime_type( $input['detected_mime'] ?? 'application/octet-stream' ),
			'extension'                => sanitize_key( $input['extension'] ?? '' ),
			'size_bytes'               => absint( $input['size_bytes'] ?? 0 ),
			'sha256'                   => strtolower( sanitize_text_field( $input['sha256'] ?? '' ) ),
			'signature_type'           => sanitize_key( $input['signature_type'] ?? '' ),
			'validator_version'        => sanitize_text_field( $input['validator_version'] ?? SC_EI_VALIDATOR_VERSION ),
			'document_category'        => sanitize_key( $input['document_category'] ?? 'other' ),
			'document_notes'           => sanitize_textarea_field( $input['document_notes'] ?? '' ),
			'confidentiality'          => sanitize_key( $input['confidentiality'] ?? 'non_confidential' ),
			'quarantine_status'        => sanitize_key( $input['quarantine_status'] ?? 'quarantined' ),
			'validation_status'        => sanitize_key( $input['validation_status'] ?? 'validated' ),
			'scan_status'              => sanitize_key( $input['scan_status'] ?? 'not_configured' ),
			'scanner_provider'         => sanitize_text_field( $input['scanner_provider'] ?? 'none' ),
			'scan_message'             => sanitize_textarea_field( $input['scan_message'] ?? '' ),
			'integrity_status'         => sanitize_key( $input['integrity_status'] ?? 'verified' ),
			'storage_status'           => sanitize_key( $input['storage_status'] ?? 'unverified' ),
			'last_verified_at'         => self::sanitize_datetime( $input['last_verified_at'] ?? null ),
			'last_verified_by'         => ! empty( $input['last_verified_by'] ) ? absint( $input['last_verified_by'] ) : null,
			'last_verification_source' => sanitize_key( $input['last_verification_source'] ?? '' ),
			'last_verification_message'=> sanitize_textarea_field( $input['last_verification_message'] ?? '' ),
			'retention_until'          => self::sanitize_datetime( $input['retention_until'] ?? null ),
			'metadata_json'            => wp_json_encode( self::sanitize_metadata( $input['metadata'] ?? array() ) ),
			'approved_by'              => ! empty( $input['approved_by'] ) ? absint( $input['approved_by'] ) : null,
			'approved_at'              => self::sanitize_datetime( $input['approved_at'] ?? null ),
			'rejected_by'              => ! empty( $input['rejected_by'] ) ? absint( $input['rejected_by'] ) : null,
			'rejected_at'              => self::sanitize_datetime( $input['rejected_at'] ?? null ),
			'replacement_requested_at' => self::sanitize_datetime( $input['replacement_requested_at'] ?? null ),
			'deleted_by'               => ! empty( $input['deleted_by'] ) ? absint( $input['deleted_by'] ) : null,
			'downloaded_count'         => absint( $input['downloaded_count'] ?? 0 ),
			'last_downloaded_at'       => self::sanitize_datetime( $input['last_downloaded_at'] ?? null ),
			'uploaded_at'              => self::sanitize_datetime( $input['uploaded_at'] ?? $now ) ?: $now,
			'deleted_at'               => self::sanitize_datetime( $input['deleted_at'] ?? null ),
		);

		$integer_fields = array(
			'inquiry_id',
			'size_bytes',
			'approved_by',
			'rejected_by',
			'deleted_by',
			'last_verified_by',
			'downloaded_count',
		);
		$formats = array_map(
			static fn( string $key ): string => in_array( $key, $integer_fields, true ) ? '%d' : '%s',
			array_keys( $data )
		);

		$inserted = $wpdb->insert( SC_EI_Database::table( 'attachments' ), $data, $formats );
		if ( false === $inserted ) {
			throw new RuntimeException( 'Unable to create private attachment record.' );
		}

		return (int) $wpdb->insert_id;
	}

	public static function find( int $id ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'attachments' );
		$row   = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
		return $row ?: null;
	}

	public static function for_inquiry( int $inquiry_id, bool $include_deleted = true ): array {
		global $wpdb;

		$table = SC_EI_Database::table( 'attachments' );
		$where = $include_deleted ? '' : ' AND deleted_at IS NULL';
		$sql   = $wpdb->prepare(
			"SELECT * FROM {$table} WHERE inquiry_id = %d{$where} ORDER BY uploaded_at DESC, id DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$inquiry_id
		);

		return (array) $wpdb->get_results( $sql, ARRAY_A );
	}

	public static function active_duplicate( int $inquiry_id, string $sha256 ): ?array {
		global $wpdb;

		$table = SC_EI_Database::table( 'attachments' );
		$row   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE inquiry_id = %d AND sha256 = %s AND deleted_at IS NULL LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$inquiry_id,
				strtolower( $sha256 )
			),
			ARRAY_A
		);

		return $row ?: null;
	}

	public static function count_for_inquiry( int $inquiry_id ): int {
		global $wpdb;

		$table = SC_EI_Database::table( 'attachments' );
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE inquiry_id = %d AND deleted_at IS NULL", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$inquiry_id
			)
		);
	}

	public static function for_reconciliation( int $limit = 500 ): array {
		global $wpdb;

		$table = SC_EI_Database::table( 'attachments' );
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE deleted_at IS NULL ORDER BY id ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				max( 1, min( 5000, $limit ) )
			),
			ARRAY_A
		);
	}

	public static function active_relative_paths(): array {
		global $wpdb;

		$table = SC_EI_Database::table( 'attachments' );
		$paths = $wpdb->get_col(
			"SELECT relative_path FROM {$table} WHERE deleted_at IS NULL AND relative_path IS NOT NULL AND relative_path <> ''", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			0
		);

		return array_values(
			array_filter(
				array_map( 'sanitize_text_field', (array) $paths ),
				static fn( string $path ): bool => '' !== $path
			)
		);
	}

	public static function storage_totals(): array {
		global $wpdb;

		$table = SC_EI_Database::table( 'attachments' );
		$row   = $wpdb->get_row(
			"SELECT COUNT(*) AS active_count, COALESCE(SUM(size_bytes), 0) AS active_bytes FROM {$table} WHERE deleted_at IS NULL", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return array(
			'active_count' => absint( $row['active_count'] ?? 0 ),
			'active_bytes' => absint( $row['active_bytes'] ?? 0 ),
		);
	}

	public static function record_verification(
		int $id,
		string $storage_status,
		string $message,
		int $actor_user_id = 0,
		string $source = 'manual'
	): bool {
		global $wpdb;

		$allowed = array( 'healthy', 'missing', 'hash_mismatch', 'size_mismatch', 'misplaced', 'unresolvable', 'deleted' );
		$storage_status = sanitize_key( $storage_status );
		if ( ! in_array( $storage_status, $allowed, true ) ) {
			$storage_status = 'unresolvable';
		}

		$integrity_status = match ( $storage_status ) {
			'healthy'       => 'verified',
			'hash_mismatch' => 'mismatch',
			'missing'       => 'missing',
			'deleted'       => 'deleted',
			default         => 'attention',
		};

		$updated = $wpdb->update(
			SC_EI_Database::table( 'attachments' ),
			array(
				'storage_status'            => $storage_status,
				'integrity_status'          => $integrity_status,
				'last_verified_at'          => current_time( 'mysql', true ),
				'last_verified_by'          => $actor_user_id,
				'last_verification_source'  => sanitize_key( $source ),
				'last_verification_message' => sanitize_textarea_field( $message ),
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return false;
		}

		if ( 'manual' === $source || 'download' === $source ) {
			$current = self::find( $id );
			SC_EI_Audit_Log::record(
				'attachment_integrity_checked',
				'Private attachment storage and integrity check completed.',
				array(
					'storage_status'   => $storage_status,
					'integrity_status' => $integrity_status,
					'source'           => $source,
					'message'          => $message,
				),
				(int) ( $current['inquiry_id'] ?? 0 ),
				$id,
				$actor_user_id
			);
		}

		return true;
	}

	public static function verify_record( array $attachment, int $actor_user_id = 0, string $source = 'manual' ): array {
		$relative = (string) ( $attachment['relative_path'] ?? '' );
		$absolute = SC_EI_Storage::absolute_path( $relative );
		$status   = 'healthy';
		$message  = 'File, size, location, and SHA-256 fingerprint are consistent.';

		if ( ! $absolute ) {
			$status  = 'unresolvable';
			$message = 'The stored relative path could not be resolved safely.';
		} elseif ( ! is_file( $absolute ) ) {
			$status  = 'missing';
			$message = 'The attachment record points to a missing physical file.';
		} else {
			$expected_area = 'approved' === (string) ( $attachment['quarantine_status'] ?? '' ) ? 'approved/' : 'quarantine/';
			$actual_size   = (int) filesize( $absolute );

			if ( ! SC_EI_Storage::verify_integrity( $relative, (string) ( $attachment['sha256'] ?? '' ) ) ) {
				$status  = 'hash_mismatch';
				$message = 'The physical file does not match the recorded SHA-256 fingerprint.';
			} elseif ( $actual_size !== (int) ( $attachment['size_bytes'] ?? 0 ) ) {
				$status  = 'size_mismatch';
				$message = 'The physical file size differs from the recorded size.';
			} elseif ( ! str_starts_with( $relative, $expected_area ) ) {
				$status  = 'misplaced';
				$message = 'The physical storage area does not match the quarantine status.';
			}
		}

		self::record_verification(
			absint( $attachment['id'] ?? 0 ),
			$status,
			$message,
			$actor_user_id,
			$source
		);

		return array(
			'status'  => $status,
			'message' => $message,
			'ok'      => 'healthy' === $status,
		);
	}

	public static function update_quarantine_status( int $id, string $status, int $actor_user_id, string $note = '' ): bool {
		global $wpdb;

		$allowed = array( 'quarantined', 'approved', 'rejected', 'replacement_requested' );
		$status  = sanitize_key( $status );
		if ( ! in_array( $status, $allowed, true ) ) {
			return false;
		}

		$current = self::find( $id );
		if ( ! $current || ! empty( $current['deleted_at'] ) ) {
			return false;
		}

		$now  = current_time( 'mysql', true );
		$data = array(
			'quarantine_status' => $status,
		);

		if ( 'approved' === $status ) {
			$data['approved_by'] = $actor_user_id;
			$data['approved_at'] = $now;
		}
		if ( 'rejected' === $status ) {
			$data['rejected_by'] = $actor_user_id;
			$data['rejected_at'] = $now;
		}
		if ( 'replacement_requested' === $status ) {
			$data['replacement_requested_at'] = $now;
		}

		$integer_fields = array( 'approved_by', 'rejected_by' );
		$formats        = array_map(
			static fn( string $key ): string => in_array( $key, $integer_fields, true ) ? '%d' : '%s',
			array_keys( $data )
		);

		$updated = $wpdb->update(
			SC_EI_Database::table( 'attachments' ),
			$data,
			array( 'id' => $id ),
			$formats,
			array( '%d' )
		);

		if ( false === $updated ) {
			return false;
		}

		SC_EI_Audit_Log::record(
			'attachment_status_changed',
			$note ?: 'Private attachment quarantine status changed.',
			array(
				'old_status' => $current['quarantine_status'],
				'new_status' => $status,
			),
			(int) $current['inquiry_id'],
			$id
		);

		return true;
	}

	public static function update_relative_path( int $id, string $relative_path ): bool {
		global $wpdb;

		return false !== $wpdb->update(
			SC_EI_Database::table( 'attachments' ),
			array( 'relative_path' => sanitize_text_field( $relative_path ) ),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	public static function update_retention( int $id, ?string $retention_until, int $actor_user_id ): bool {
		global $wpdb;

		$current = self::find( $id );
		if ( ! $current ) {
			return false;
		}

		$value   = self::sanitize_datetime( $retention_until );
		$updated = $wpdb->update(
			SC_EI_Database::table( 'attachments' ),
			array( 'retention_until' => $value ),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return false;
		}

		SC_EI_Audit_Log::record(
			'attachment_retention_updated',
			'Private attachment retention date updated.',
			array(
				'old_retention_until' => $current['retention_until'],
				'new_retention_until' => $value,
			),
			(int) $current['inquiry_id'],
			$id,
			$actor_user_id
		);

		return true;
	}

	public static function mark_deleted( int $id, int $actor_user_id, string $reason, string $final_status = 'deleted' ): bool {
		global $wpdb;

		$current = self::find( $id );
		if ( ! $current || ! empty( $current['deleted_at'] ) ) {
			return false;
		}

		$now  = current_time( 'mysql', true );
		$data = array(
			'quarantine_status'        => sanitize_key( $final_status ),
			'storage_status'           => 'deleted',
			'integrity_status'         => 'deleted',
			'last_verified_at'         => $now,
			'last_verified_by'         => $actor_user_id,
			'last_verification_source' => 'deletion',
			'last_verification_message'=> 'Physical file deleted or confirmed absent through an authorized deletion workflow.',
			'deleted_by'               => $actor_user_id,
			'deleted_at'               => $now,
		);

		if ( 'rejected' === $final_status ) {
			$data['rejected_by'] = $actor_user_id;
			$data['rejected_at'] = $now;
		}

		$integer_fields = array( 'deleted_by', 'rejected_by', 'last_verified_by' );
		$formats        = array_map(
			static fn( string $key ): string => in_array( $key, $integer_fields, true ) ? '%d' : '%s',
			array_keys( $data )
		);

		$updated = $wpdb->update(
			SC_EI_Database::table( 'attachments' ),
			$data,
			array( 'id' => $id ),
			$formats,
			array( '%d' )
		);

		if ( false === $updated ) {
			return false;
		}

		SC_EI_Audit_Log::record(
			'attachment_deleted',
			sanitize_textarea_field( $reason ) ?: 'Private attachment deleted.',
			array(
				'original_name' => $current['original_name'],
				'final_status'  => $final_status,
				'sha256'       => $current['sha256'],
			),
			(int) $current['inquiry_id'],
			$id,
			$actor_user_id
		);

		return true;
	}

	public static function record_download( int $id, int $actor_user_id, string $integrity_status ): bool {
		global $wpdb;

		$current = self::find( $id );
		if ( ! $current ) {
			return false;
		}

		$updated = $wpdb->update(
			SC_EI_Database::table( 'attachments' ),
			array(
				'downloaded_count'   => absint( $current['downloaded_count'] ) + 1,
				'last_downloaded_at' => current_time( 'mysql', true ),
				'integrity_status'          => sanitize_key( $integrity_status ),
				'storage_status'            => 'healthy',
				'last_verified_at'          => current_time( 'mysql', true ),
				'last_verified_by'          => $actor_user_id,
				'last_verification_source'  => 'download',
				'last_verification_message' => 'Integrity verified immediately before authorized download.',
			),
			array( 'id' => $id ),
			array( '%d', '%s', '%s', '%s', '%d', '%s', '%s' ),
			array( '%d' )
		);

		if ( false === $updated ) {
			return false;
		}

		SC_EI_Audit_Log::record(
			'attachment_downloaded',
			'Authorized user downloaded a private attachment.',
			array(
				'integrity_status' => $integrity_status,
				'download_count'   => absint( $current['downloaded_count'] ) + 1,
			),
			(int) $current['inquiry_id'],
			$id,
			$actor_user_id
		);

		return true;
	}

	public static function expired( int $limit = 100 ): array {
		global $wpdb;

		$table = SC_EI_Database::table( 'attachments' );
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE deleted_at IS NULL AND retention_until IS NOT NULL AND retention_until <= %s ORDER BY retention_until ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				current_time( 'mysql', true ),
				max( 1, min( 500, $limit ) )
			),
			ARRAY_A
		);
	}

	private static function sanitize_datetime( $value ): ?string {
		if ( empty( $value ) ) {
			return null;
		}

		$value = sanitize_text_field( (string) $value );
		$date  = DateTimeImmutable::createFromFormat( 'Y-m-d H:i:s', $value, new DateTimeZone( 'UTC' ) );
		return $date && $date->format( 'Y-m-d H:i:s' ) === $value ? $value : null;
	}

	private static function sanitize_metadata( $metadata ): array {
		$clean = array();
		foreach ( (array) $metadata as $key => $value ) {
			$key = sanitize_key( (string) $key );
			if ( '' === $key ) {
				continue;
			}
			if ( is_scalar( $value ) || null === $value ) {
				$clean[ $key ] = sanitize_text_field( (string) $value );
			} elseif ( is_array( $value ) ) {
				$clean[ $key ] = array_map( 'sanitize_text_field', $value );
			}
		}
		return $clean;
	}
}
