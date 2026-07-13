<?php
/**
 * Audit history.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Audit_Log {

	public static function record(
		string $event_type,
		string $message = '',
		array $context = array(),
		?int $inquiry_id = null,
		?int $attachment_id = null,
		?int $actor_user_id = null
	): int {
		global $wpdb;

		if ( null === $actor_user_id && is_user_logged_in() ) {
			$actor_user_id = get_current_user_id();
		}

		$wpdb->insert(
			SC_EI_Database::table( 'audit_log' ),
			array(
				'inquiry_id'    => $inquiry_id,
				'attachment_id' => $attachment_id,
				'actor_user_id' => $actor_user_id,
				'event_type'    => sanitize_key( $event_type ),
				'event_message' => sanitize_textarea_field( $message ),
				'context_json'  => wp_json_encode( $context ),
				'created_at'    => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%d', '%s', '%s', '%s', '%s' )
		);

		return (int) $wpdb->insert_id;
	}

	public static function for_inquiry( int $inquiry_id, int $limit = 100 ): array {
		global $wpdb;

		$table = SC_EI_Database::table( 'audit_log' );
		$limit = max( 1, min( 500, $limit ) );

		$sql = $wpdb->prepare(
			"SELECT * FROM {$table} WHERE inquiry_id = %d ORDER BY created_at DESC, id DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$inquiry_id,
			$limit
		);

		return (array) $wpdb->get_results( $sql, ARRAY_A );
	}
}
