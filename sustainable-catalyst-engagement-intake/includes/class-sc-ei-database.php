<?php
/**
 * Database schema and migrations.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Database {

	public static function table( string $name ): string {
		global $wpdb;

		$allowed = array( 'inquiries', 'attachments', 'audit_log' );
		if ( ! in_array( $name, $allowed, true ) ) {
			throw new InvalidArgumentException( 'Unknown Engagement Intake table.' );
		}

		return $wpdb->prefix . 'sc_ei_' . $name;
	}

	public static function install(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$inquiries      = self::table( 'inquiries' );
		$attachments    = self::table( 'attachments' );
		$audit_log      = self::table( 'audit_log' );

		$sql_inquiries = "CREATE TABLE {$inquiries} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_id char(36) NOT NULL,
			reference varchar(40) NOT NULL,
			inquiry_type varchar(80) NOT NULL DEFAULT 'general',
			status varchar(80) NOT NULL DEFAULT 'new',
			form_variant varchar(40) NOT NULL DEFAULT 'advanced',
			source_page varchar(80) NOT NULL DEFAULT 'other',
			entry_cta varchar(120) NOT NULL DEFAULT 'unspecified',
			conversion_route varchar(120) NOT NULL DEFAULT '',
			guidance_flags longtext NULL,
			contact_name varchar(191) NOT NULL DEFAULT '',
			contact_email varchar(191) NOT NULL DEFAULT '',
			organization varchar(191) NOT NULL DEFAULT '',
			role_title varchar(191) NOT NULL DEFAULT '',
			subject varchar(255) NOT NULL DEFAULT '',
			message longtext NULL,
			project_summary longtext NULL,
			desired_outcome longtext NULL,
			service_interest varchar(120) NOT NULL DEFAULT '',
			budget_range varchar(120) NOT NULL DEFAULT '',
			desired_start_date date NULL,
			deadline_date date NULL,
			preferred_contact_method varchar(40) NOT NULL DEFAULT 'email',
			teams_email varchar(191) NOT NULL DEFAULT '',
			phone_number varchar(80) NOT NULL DEFAULT '',
			timezone varchar(120) NOT NULL DEFAULT '',
			city varchar(120) NOT NULL DEFAULT '',
			country varchar(120) NOT NULL DEFAULT '',
			meeting_request varchar(20) NOT NULL DEFAULT 'no',
			preferred_weekdays varchar(255) NOT NULL DEFAULT '[]',
			preferred_time_windows longtext NULL,
			preferred_duration smallint(5) unsigned NOT NULL DEFAULT 0,
			participant_count smallint(5) unsigned NOT NULL DEFAULT 1,
			participant_emails longtext NULL,
			accessibility_needs longtext NULL,
			calendar_invite_consent tinyint(1) unsigned NOT NULL DEFAULT 0,
			scheduling_notes longtext NULL,
			scheduling_status varchar(40) NOT NULL DEFAULT 'not_requested',
			teams_meeting_url text NULL,
			scheduled_start_utc datetime NULL,
			scheduled_end_utc datetime NULL,
			scheduled_timezone varchar(120) NOT NULL DEFAULT '',
			calendar_event_id varchar(191) NOT NULL DEFAULT '',
			relevant_links longtext NULL,
			metadata_json longtext NULL,
			consent_version varchar(80) NOT NULL DEFAULT '',
			consent_at datetime NULL,
			assigned_user_id bigint(20) unsigned NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			closed_at datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			UNIQUE KEY reference (reference),
			KEY status (status),
			KEY inquiry_type (inquiry_type),
			KEY form_variant (form_variant),
			KEY source_page (source_page),
			KEY conversion_route (conversion_route),
			KEY contact_email (contact_email),
			KEY preferred_contact_method (preferred_contact_method),
			KEY meeting_request (meeting_request),
			KEY scheduling_status (scheduling_status),
			KEY assigned_user_id (assigned_user_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		$sql_attachments = "CREATE TABLE {$attachments} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			inquiry_id bigint(20) unsigned NOT NULL,
			public_id char(36) NOT NULL,
			original_name varchar(255) NOT NULL DEFAULT '',
			stored_name varchar(255) NOT NULL DEFAULT '',
			relative_path text NULL,
			mime_type varchar(191) NOT NULL DEFAULT '',
			detected_mime varchar(191) NOT NULL DEFAULT '',
			extension varchar(32) NOT NULL DEFAULT '',
			size_bytes bigint(20) unsigned NOT NULL DEFAULT 0,
			sha256 char(64) NOT NULL DEFAULT '',
			signature_type varchar(80) NOT NULL DEFAULT '',
			validator_version varchar(40) NOT NULL DEFAULT '',
			document_category varchar(80) NOT NULL DEFAULT 'other',
			document_notes text NULL,
			confidentiality varchar(40) NOT NULL DEFAULT 'non_confidential',
			quarantine_status varchar(80) NOT NULL DEFAULT 'quarantined',
			validation_status varchar(80) NOT NULL DEFAULT 'validated',
			scan_status varchar(80) NOT NULL DEFAULT 'not_configured',
			scanner_provider varchar(120) NOT NULL DEFAULT 'none',
			scan_message text NULL,
			scan_attempts int(10) unsigned NOT NULL DEFAULT 0,
			last_scanned_at datetime NULL,
			last_scanned_by bigint(20) unsigned NULL,
			integrity_status varchar(80) NOT NULL DEFAULT 'verified',
			storage_status varchar(40) NOT NULL DEFAULT 'unverified',
			last_verified_at datetime NULL,
			last_verified_by bigint(20) unsigned NULL,
			last_verification_source varchar(40) NOT NULL DEFAULT '',
			last_verification_message text NULL,
			retention_until datetime NULL,
			metadata_json longtext NULL,
			approved_by bigint(20) unsigned NULL,
			approved_at datetime NULL,
			rejected_by bigint(20) unsigned NULL,
			rejected_at datetime NULL,
			replacement_requested_at datetime NULL,
			deleted_by bigint(20) unsigned NULL,
			downloaded_count int(10) unsigned NOT NULL DEFAULT 0,
			last_downloaded_at datetime NULL,
			uploaded_at datetime NOT NULL,
			deleted_at datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_id (public_id),
			KEY inquiry_id (inquiry_id),
			KEY sha256 (sha256),
			KEY quarantine_status (quarantine_status),
			KEY validation_status (validation_status),
			KEY scan_status (scan_status),
			KEY last_scanned_at (last_scanned_at),
			KEY storage_status (storage_status),
			KEY retention_until (retention_until),
			KEY deleted_at (deleted_at)
		) {$charset_collate};";

		$sql_audit = "CREATE TABLE {$audit_log} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			inquiry_id bigint(20) unsigned NULL,
			attachment_id bigint(20) unsigned NULL,
			actor_user_id bigint(20) unsigned NULL,
			event_type varchar(100) NOT NULL,
			event_message text NULL,
			context_json longtext NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY inquiry_id (inquiry_id),
			KEY attachment_id (attachment_id),
			KEY actor_user_id (actor_user_id),
			KEY event_type (event_type),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $sql_inquiries );
		dbDelta( $sql_attachments );
		dbDelta( $sql_audit );

		update_option( 'sc_ei_db_version', SC_EI_DB_VERSION, false );
	}

	public static function maybe_upgrade(): void {
		$current = (string) get_option( 'sc_ei_db_version', '' );
		if ( version_compare( $current, SC_EI_DB_VERSION, '<' ) ) {
			self::install();
		}
	}

	public static function tables_exist(): array {
		global $wpdb;

		$result = array();
		foreach ( array( 'inquiries', 'attachments', 'audit_log' ) as $name ) {
			$table           = self::table( $name );
			$found           = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
			$result[ $name ] = ( $found === $table );
		}

		return $result;
	}

	public static function inquiry_columns_exist(): array {
		global $wpdb;

		$table   = self::table( 'inquiries' );
		$columns = array(
			'form_variant',
			'source_page',
			'entry_cta',
			'conversion_route',
			'guidance_flags',
			'preferred_contact_method',
			'teams_email',
			'timezone',
			'meeting_request',
			'scheduling_status',
			'teams_meeting_url',
			'scheduled_start_utc',
		);

		$result = array();
		foreach ( $columns as $column ) {
			$found             = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", $column ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$result[ $column ] = ( $found === $column );
		}

		return $result;
	}

	public static function attachment_columns_exist(): array {
		global $wpdb;

		$table   = self::table( 'attachments' );
		$columns = array(
			'detected_mime',
			'signature_type',
			'validator_version',
			'document_category',
			'confidentiality',
			'scanner_provider',
			'scan_message',
			'scan_attempts',
			'last_scanned_at',
			'last_scanned_by',
			'integrity_status',
			'storage_status',
			'last_verified_at',
			'last_verified_by',
			'last_verification_source',
			'last_verification_message',
			'retention_until',
			'approved_by',
			'approved_at',
			'rejected_by',
			'rejected_at',
			'replacement_requested_at',
			'deleted_by',
			'downloaded_count',
			'last_downloaded_at',
		);

		$result = array();
		foreach ( $columns as $column ) {
			$found             = $wpdb->get_var( $wpdb->prepare( "SHOW COLUMNS FROM {$table} LIKE %s", $column ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$result[ $column ] = ( $found === $column );
		}

		return $result;
	}

	public static function drop_all(): void {
		global $wpdb;

		foreach ( array( 'audit_log', 'attachments', 'inquiries' ) as $name ) {
			$table = self::table( $name );
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}
	}
}
