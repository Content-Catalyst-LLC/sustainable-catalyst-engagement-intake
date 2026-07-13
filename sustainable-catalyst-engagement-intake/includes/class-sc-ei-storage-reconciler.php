<?php
/**
 * Read-only database-to-filesystem reconciliation.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Storage_Reconciler {

	private const REPORT_OPTION = 'sc_ei_last_storage_reconciliation';

	public static function run( int $record_limit = 500, int $file_limit = 3000 ): array {
		$started = microtime( true );
		$records = SC_EI_Attachment_Repository::for_reconciliation( $record_limit );
		$totals  = SC_EI_Attachment_Repository::storage_totals();
		$expected_paths = array_fill_keys( SC_EI_Attachment_Repository::active_relative_paths(), true );
		$issues = array(
			'missing_files'      => array(),
			'hash_mismatches'    => array(),
			'size_mismatches'    => array(),
			'misplaced_files'    => array(),
			'unresolvable_paths' => array(),
			'orphan_files'       => array(),
		);

		$counts = array(
			'records_checked' => 0,
			'files_seen'      => 0,
			'healthy_records' => 0,
			'missing_files'   => 0,
			'hash_mismatches' => 0,
			'size_mismatches' => 0,
			'misplaced_files' => 0,
			'unresolvable_paths' => 0,
			'orphan_files'    => 0,
		);

		foreach ( $records as $attachment ) {
			$counts['records_checked']++;
			$id       = absint( $attachment['id'] );
			$relative = (string) $attachment['relative_path'];
			$absolute = SC_EI_Storage::absolute_path( $relative );
			$status   = 'healthy';
			$message  = 'File and metadata are consistent.';

			if ( ! $absolute ) {
				$status = 'unresolvable';
				$message = 'The stored relative path could not be resolved safely.';
				self::append_issue( $issues['unresolvable_paths'], $attachment, $message );
				$counts['unresolvable_paths']++;
			} elseif ( ! is_file( $absolute ) ) {
				$status = 'missing';
				$message = 'The database record points to a missing physical file.';
				self::append_issue( $issues['missing_files'], $attachment, $message );
				$counts['missing_files']++;
			} else {
				$actual_size = (int) filesize( $absolute );
				$size_ok     = $actual_size === (int) $attachment['size_bytes'];
				$hash_ok     = SC_EI_Storage::verify_integrity( $relative, (string) $attachment['sha256'] );
				$expected_area = 'approved' === $attachment['quarantine_status'] ? 'approved/' : 'quarantine/';
				$location_ok   = str_starts_with( $relative, $expected_area );

				if ( ! $hash_ok ) {
					$status = 'hash_mismatch';
					$message = 'The physical file does not match its recorded SHA-256 fingerprint.';
					self::append_issue( $issues['hash_mismatches'], $attachment, $message );
					$counts['hash_mismatches']++;
				} elseif ( ! $size_ok ) {
					$status = 'size_mismatch';
					$message = 'The physical file size differs from the recorded size.';
					self::append_issue( $issues['size_mismatches'], $attachment, $message );
					$counts['size_mismatches']++;
				} elseif ( ! $location_ok ) {
					$status = 'misplaced';
					$message = 'The physical area does not match the quarantine status.';
					self::append_issue( $issues['misplaced_files'], $attachment, $message );
					$counts['misplaced_files']++;
				} else {
					$counts['healthy_records']++;
				}
			}

			SC_EI_Attachment_Repository::record_verification(
				$id,
				$status,
				$message,
				0,
				'reconciliation'
			);
		}

		$inventory = SC_EI_Storage::managed_file_inventory( $file_limit );
		$counts['files_seen'] = count( $inventory['files'] );

		foreach ( $inventory['files'] as $file ) {
			if ( isset( $expected_paths[ $file['relative_path'] ] ) ) {
				continue;
			}
			$counts['orphan_files']++;
			if ( count( $issues['orphan_files'] ) < 100 ) {
				$issues['orphan_files'][] = array(
					'relative_path' => $file['relative_path'],
					'size_bytes'    => $file['size_bytes'],
					'modified_at'   => $file['modified_at'],
					'message'       => 'Managed .qtn file has no active attachment record.',
				);
			}
		}

		$report = array(
			'version'          => SC_EI_VERSION,
			'completed_at_utc' => current_time( 'mysql', true ),
			'duration_seconds' => round( microtime( true ) - $started, 4 ),
			'record_limit'     => $record_limit,
			'file_limit'       => $file_limit,
			'records_truncated'=> absint( $totals['active_count'] ?? 0 ) > count( $records ),
			'files_truncated'  => ! empty( $inventory['truncated'] ),
			'counts'           => $counts,
			'issues'           => $issues,
			'storage_path'     => SC_EI_Storage::base_dir(),
		);

		update_option( self::REPORT_OPTION, $report, false );

		SC_EI_Audit_Log::record(
			'storage_reconciliation_completed',
			'Protected storage reconciliation completed.',
			array(
				'counts'            => $counts,
				'duration_seconds'  => $report['duration_seconds'],
				'records_truncated' => $report['records_truncated'],
				'files_truncated'   => $report['files_truncated'],
			),
			null,
			null,
			get_current_user_id()
		);

		return $report;
	}

	public static function latest(): array {
		$report = get_option( self::REPORT_OPTION, array() );
		return is_array( $report ) ? $report : array();
	}

	private static function append_issue( array &$bucket, array $attachment, string $message ): void {
		if ( count( $bucket ) >= 100 ) {
			return;
		}

		$bucket[] = array(
			'attachment_id' => absint( $attachment['id'] ),
			'inquiry_id'    => absint( $attachment['inquiry_id'] ),
			'original_name' => sanitize_file_name( (string) $attachment['original_name'] ),
			'relative_path' => sanitize_text_field( (string) $attachment['relative_path'] ),
			'status'        => sanitize_key( (string) $attachment['quarantine_status'] ),
			'message'       => sanitize_text_field( $message ),
		);
	}
}
