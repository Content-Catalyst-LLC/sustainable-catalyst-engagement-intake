<?php
/**
 * Attachment retention cleanup.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Retention {

	public const CRON_HOOK = 'sc_ei_cleanup_expired_attachments';

	public static function register(): void {
		add_action( self::CRON_HOOK, array( __CLASS__, 'cleanup' ) );
		add_action( 'init', array( __CLASS__, 'schedule' ) );
	}

	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	public static function unschedule(): void {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		while ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
			$timestamp = wp_next_scheduled( self::CRON_HOOK );
		}
	}

	public static function preview( int $limit = 100 ): array {
		$items = SC_EI_Attachment_Repository::expired( $limit );
		return array(
			'generated_at_utc' => current_time( 'mysql', true ),
			'count'            => count( $items ),
			'total_bytes'      => array_sum( array_map( static fn( array $item ): int => absint( $item['size_bytes'] ?? 0 ), $items ) ),
			'items'            => array_map(
				static fn( array $item ): array => array(
					'id'              => absint( $item['id'] ),
					'inquiry_id'      => absint( $item['inquiry_id'] ),
					'original_name'   => sanitize_file_name( (string) $item['original_name'] ),
					'size_bytes'      => absint( $item['size_bytes'] ),
					'retention_until' => sanitize_text_field( (string) $item['retention_until'] ),
					'file_exists'     => (bool) ( SC_EI_Storage::absolute_path( (string) $item['relative_path'] ) && is_file( SC_EI_Storage::absolute_path( (string) $item['relative_path'] ) ) ),
				),
				array_slice( $items, 0, 100 )
			),
		);
	}

	public static function latest_run(): array {
		$run = get_option( 'sc_ei_last_retention_run', array() );
		return is_array( $run ) ? $run : array();
	}

	public static function cleanup( int $limit = 100 ): int {
		if ( get_transient( 'sc_ei_retention_cleanup_lock' ) ) {
			return 0;
		}
		set_transient( 'sc_ei_retention_cleanup_lock', 1, 15 * MINUTE_IN_SECONDS );

		$count  = 0;
		$failed = 0;
		$bytes  = 0;

		try {
			foreach ( SC_EI_Attachment_Repository::expired( $limit ) as $attachment ) {
				$deleted = SC_EI_Storage::delete_file( (string) $attachment['relative_path'] );
				if ( ! $deleted ) {
					SC_EI_Audit_Log::record(
						'attachment_retention_delete_failed',
						'Expired attachment could not be deleted from protected storage.',
						array( 'retention_until' => $attachment['retention_until'] ),
						(int) $attachment['inquiry_id'],
						(int) $attachment['id'],
						0
					);
					$failed++;
					continue;
				}

				if ( SC_EI_Attachment_Repository::mark_deleted(
					(int) $attachment['id'],
					0,
					'Private attachment deleted automatically after its retention date.',
					'deleted'
				) ) {
					$count++;
					$bytes += absint( $attachment['size_bytes'] ?? 0 );
				} else {
					$failed++;
				}
			}

			$report = array(
				'completed_at_utc' => current_time( 'mysql', true ),
				'deleted_count'    => $count,
				'deleted_bytes'    => $bytes,
				'failed_count'     => $failed,
				'limit'            => $limit,
			);
			update_option( 'sc_ei_last_retention_run', $report, false );

			return $count;
		} finally {
			delete_transient( 'sc_ei_retention_cleanup_lock' );
		}
	}
}
