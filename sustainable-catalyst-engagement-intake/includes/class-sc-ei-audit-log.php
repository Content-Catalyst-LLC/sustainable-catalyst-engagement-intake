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

	public static function file_event_types(): array {
		return array(
			'attachment_quarantined'             => __( 'Attachment Quarantined', 'sustainable-catalyst-engagement-intake' ),
			'attachment_scan_rejected'           => __( 'Upload Rejected by Scanner Policy', 'sustainable-catalyst-engagement-intake' ),
			'attachment_downloaded'              => __( 'Attachment Downloaded', 'sustainable-catalyst-engagement-intake' ),
			'attachment_integrity_mismatch'      => __( 'Download Blocked by Integrity Check', 'sustainable-catalyst-engagement-intake' ),
			'attachment_integrity_checked'       => __( 'Integrity Checked', 'sustainable-catalyst-engagement-intake' ),
			'attachment_scan_completed'          => __( 'Scanner Completed', 'sustainable-catalyst-engagement-intake' ),
			'attachment_status_changed'          => __( 'Quarantine Status Changed', 'sustainable-catalyst-engagement-intake' ),
			'attachment_retention_updated'       => __( 'Retention Updated', 'sustainable-catalyst-engagement-intake' ),
			'attachment_deleted'                 => __( 'Attachment Deleted', 'sustainable-catalyst-engagement-intake' ),
			'attachment_bulk_scan_completed'     => __( 'Bulk Scanner Retry', 'sustainable-catalyst-engagement-intake' ),
			'quarantine_bulk_action_completed'   => __( 'Bulk Quarantine Action', 'sustainable-catalyst-engagement-intake' ),
			'scanner_readiness_test_completed'   => __( 'Scanner Readiness Test', 'sustainable-catalyst-engagement-intake' ),
			'storage_reconciliation_completed'   => __( 'Storage Reconciliation', 'sustainable-catalyst-engagement-intake' ),
			'file_audit_exported'                  => __( 'File Audit Exported', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function query_file_events( array $args = array() ): array {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			array(
				'event_type' => '',
				'actor'      => 0,
				'date_from'  => '',
				'date_to'    => '',
				'search'     => '',
				'page'       => 1,
				'per_page'   => 25,
				'orderby'    => 'created_at',
				'order'      => 'DESC',
			)
		);

		$log         = SC_EI_Database::table( 'audit_log' );
		$attachments = SC_EI_Database::table( 'attachments' );
		$inquiries   = SC_EI_Database::table( 'inquiries' );
		$where       = array();
		$values      = array();
		$event_types = array_keys( self::file_event_types() );
		$placeholders= implode( ',', array_fill( 0, count( $event_types ), '%s' ) );
		$where[]     = "l.event_type IN ({$placeholders})";
		$values      = array_merge( $values, $event_types );

		$event_type = sanitize_key( (string) $args['event_type'] );
		if ( in_array( $event_type, $event_types, true ) ) {
			$where[]  = 'l.event_type = %s';
			$values[] = $event_type;
		}

		$actor = absint( $args['actor'] );
		if ( $actor ) {
			$where[]  = 'l.actor_user_id = %d';
			$values[] = $actor;
		}

		$date_from = sanitize_text_field( (string) $args['date_from'] );
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_from ) ) {
			$where[]  = 'l.created_at >= %s';
			$values[] = $date_from . ' 00:00:00';
		}

		$date_to = sanitize_text_field( (string) $args['date_to'] );
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_to ) ) {
			$where[]  = 'l.created_at <= %s';
			$values[] = $date_to . ' 23:59:59';
		}

		$search = sanitize_text_field( (string) $args['search'] );
		if ( '' !== $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$where[] = '(l.event_message LIKE %s OR a.original_name LIKE %s OR i.reference LIKE %s OR u.display_name LIKE %s OR u.user_email LIKE %s)';
			array_push( $values, $like, $like, $like, $like, $like );
		}

		$allowed_orderby = array(
			'created_at' => 'l.created_at',
			'event_type' => 'l.event_type',
			'actor'      => 'u.display_name',
			'reference'  => 'i.reference',
		);
		$orderby = $allowed_orderby[ sanitize_key( (string) $args['orderby'] ) ] ?? 'l.created_at';
		$order   = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';
		$page    = max( 1, absint( $args['page'] ) );
		$per_page= max( 1, min( 100, absint( $args['per_page'] ) ) );
		$offset  = ( $page - 1 ) * $per_page;
		$where_sql = implode( ' AND ', $where );

		$joins = "LEFT JOIN {$attachments} a ON a.id = l.attachment_id
			LEFT JOIN {$inquiries} i ON i.id = l.inquiry_id
			LEFT JOIN {$wpdb->users} u ON u.ID = l.actor_user_id";

		$count_sql = "SELECT COUNT(*) FROM {$log} l {$joins} WHERE {$where_sql}";
		$data_sql  = "SELECT l.*, a.original_name, a.quarantine_status, a.scan_status, i.reference, i.contact_name, u.display_name AS actor_name, u.user_email AS actor_email
			FROM {$log} l {$joins}
			WHERE {$where_sql}
			ORDER BY {$orderby} {$order}, l.id {$order}
			LIMIT %d OFFSET %d";

		$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $values ) );
		$data_values = array_merge( $values, array( $per_page, $offset ) );
		$items = (array) $wpdb->get_results( $wpdb->prepare( $data_sql, $data_values ), ARRAY_A );

		return array(
			'items'       => $items,
			'total'       => $total,
			'total_pages' => max( 1, (int) ceil( $total / $per_page ) ),
		);
	}

	public static function file_event_summary(): array {
		global $wpdb;

		$table       = SC_EI_Database::table( 'audit_log' );
		$event_types = array_keys( self::file_event_types() );
		$placeholders= implode( ',', array_fill( 0, count( $event_types ), '%s' ) );
		$sql = $wpdb->prepare(
			"SELECT event_type, COUNT(*) AS event_count, MAX(created_at) AS latest_at
			FROM {$table}
			WHERE event_type IN ({$placeholders})
			GROUP BY event_type
			ORDER BY event_count DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$event_types
		);

		$rows = (array) $wpdb->get_results( $sql, ARRAY_A );
		$result = array();
		foreach ( $rows as $row ) {
			$result[ $row['event_type'] ] = array(
				'count'     => absint( $row['event_count'] ),
				'latest_at' => sanitize_text_field( (string) $row['latest_at'] ),
			);
		}
		return $result;
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
