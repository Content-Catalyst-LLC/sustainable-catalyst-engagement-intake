<?php
/**
 * Attachment metadata foundation.
 *
 * Physical upload handling is intentionally deferred to v0.3.0.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Attachment_Repository {

	public static function for_inquiry( int $inquiry_id ): array {
		global $wpdb;

		$table = SC_EI_Database::table( 'attachments' );
		$sql   = $wpdb->prepare(
			"SELECT * FROM {$table} WHERE inquiry_id = %d ORDER BY uploaded_at DESC, id DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$inquiry_id
		);

		return (array) $wpdb->get_results( $sql, ARRAY_A );
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
}
