<?php
/**
 * Queue-only retention scheduling compatibility layer.
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
		return SC_EI_Retention_Engine::preview( $limit );
	}

	public static function latest_run(): array {
		$run = get_option( 'sc_ei_last_retention_queue_run', array() );
		return is_array( $run ) ? $run : array();
	}

	/**
	 * Legacy method name retained for compatibility.
	 *
	 * v0.9.1 retains the v0.6.0 safety boundary and never deletes here. It queues candidate actions for human review.
	 */
	public static function cleanup( int $limit = 100 ): int {
		if ( get_transient( 'sc_ei_retention_cleanup_lock' ) ) {
			return 0;
		}
		set_transient( 'sc_ei_retention_cleanup_lock', 1, 15 * MINUTE_IN_SECONDS );

		try {
			$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Privacy_Schema::default_settings() );
			$limit = min(
				max( 1, absint( $limit ) ),
				max( 1, min( 1000, absint( $settings['retention_queue_batch_limit'] ?? 100 ) ) )
			);
			$report = SC_EI_Retention_Engine::queue_candidates( $limit, 0, 'daily_cron' );
			return absint( $report['queued_count'] ?? 0 );
		} finally {
			delete_transient( 'sc_ei_retention_cleanup_lock' );
		}
	}
}
