<?php
/**
 * Admin-only live validation and backup evidence for the production gate.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Platform_Validation {

	private const RESULT_OPTION = 'sc_ei_platform_live_validation';
	private const BACKUP_OPTION = 'sc_ei_platform_backup_attestation';
	private const MAX_AGE_DAYS = 7;

	public static function latest(): array {
		$value = get_option( self::RESULT_OPTION, array() );
		return is_array( $value ) ? $value : array();
	}

	public static function backup_attestation(): array {
		$value = get_option( self::BACKUP_OPTION, array() );
		return is_array( $value ) ? $value : array();
	}

	public static function successful_and_fresh(): bool {
		$result = self::latest();
		return ! empty( $result['passed'] )
			&& SC_EI_VERSION === (string) ( $result['plugin_version'] ?? '' )
			&& self::fresh_timestamp( (string) ( $result['completed_at'] ?? '' ) );
	}

	public static function backups_fresh(): bool {
		$attestation = self::backup_attestation();
		return ! empty( $attestation['database_confirmed'] )
			&& ! empty( $attestation['storage_confirmed'] )
			&& SC_EI_VERSION === (string) ( $attestation['plugin_version'] ?? '' )
			&& self::fresh_timestamp( (string) ( $attestation['attested_at'] ?? '' ) );
	}

	public static function attest_backups( string $database_reference, string $storage_reference, int $actor_user_id ) {
		$database_reference = sanitize_text_field( $database_reference );
		$storage_reference = sanitize_text_field( $storage_reference );
		if ( strlen( trim( $database_reference ) ) < 3 || strlen( trim( $storage_reference ) ) < 3 ) {
			return new WP_Error( 'platform_backup_reference_required', __( 'Record a short reference for both the database backup and protected-storage backup.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$value = array(
			'schema'               => 'sc-platform-backup-attestation/1.0',
			'plugin_version'       => SC_EI_VERSION,
			'database_confirmed'   => true,
			'storage_confirmed'    => true,
			'database_reference'   => $database_reference,
			'storage_reference'    => $storage_reference,
			'attested_by'          => $actor_user_id,
			'attested_at'          => current_time( 'mysql', true ),
		);
		update_option( self::BACKUP_OPTION, $value, false );
		SC_EI_Audit_Log::record(
			'platform_backups_attested',
			'Authorized staff attested that current database and protected-storage backups are available.',
			array(
				'plugin_version'     => SC_EI_VERSION,
				'database_reference' => $database_reference,
				'storage_reference'  => $storage_reference,
			),
			null,
			null,
			$actor_user_id
		);
		return $value;
	}

	public static function run( string $mail_recipient, int $actor_user_id ) : array {
		global $wpdb;

		$mail_recipient = sanitize_email( $mail_recipient );
		$started = microtime( true );
		$checks = array();
		$inquiry_id = 0;
		$access_id = 0;
		$support_case_id = 0;
		$support_signal_id = 0;
		$support_link_ids = array();
		$relative_path = '';
		$temp_path = '';
		$cleanup_ok = true;

		self::add( $checks, 'version_state', __( 'Installed version state', 'sustainable-catalyst-engagement-intake' ), SC_EI_VERSION === (string) get_option( 'sc_ei_version', '' ), SC_EI_VERSION . ' / ' . (string) get_option( 'sc_ei_version', '' ) );
		$tables = SC_EI_Database::tables_exist();
		$columns = SC_EI_Database::platform_columns_exist();
		$inquiry_columns = SC_EI_Database::inquiry_columns_exist();
		$lifecycle_columns = SC_EI_Database::lifecycle_columns_exist();
		$support_columns = SC_EI_Database::support_columns_exist();
		self::add( $checks, 'database_contract', __( 'Database tables, platform, lifecycle, and support schema', 'sustainable-catalyst-engagement-intake' ), ! in_array( false, $tables, true ) && ! in_array( false, $columns, true ) && ! in_array( false, $inquiry_columns, true ) && ! in_array( false, $lifecycle_columns, true ) && ! in_array( false, $support_columns, true ), sprintf( '%d/%d tables; %d/%d platform columns; %d/%d inquiry columns; %d/%d lifecycle columns; %d/%d support columns', count( array_filter( $tables ) ), count( $tables ), count( array_filter( $columns ) ), count( $columns ), count( array_filter( $inquiry_columns ) ), count( $inquiry_columns ), count( array_filter( $lifecycle_columns ) ), count( $lifecycle_columns ), count( array_filter( $support_columns ) ), count( $support_columns ) ) );
		$lifecycle_migration = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM " . SC_EI_Database::table( 'platform_migrations' ) . " WHERE migration_key = %s LIMIT 1", SC_EI_Lifecycle_Repository::MIGRATION_KEY ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		self::add( $checks, 'lifecycle_migration', __( 'v1.1.0 advisory lifecycle migration journal', 'sustainable-catalyst-engagement-intake' ), 'completed' === (string) $lifecycle_migration, (string) ( $lifecycle_migration ?: 'missing' ) );
		$support_migration = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM " . SC_EI_Database::table( 'platform_migrations' ) . " WHERE migration_key = %s LIMIT 1", SC_EI_Support_Repository::MIGRATION_KEY ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		self::add( $checks, 'support_migration', __( 'v1.2.0 support operations migration journal', 'sustainable-catalyst-engagement-intake' ), 'completed' === (string) $support_migration, (string) ( $support_migration ?: 'missing' ) );
		$support_patch_migration = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM " . SC_EI_Database::table( 'platform_migrations' ) . " WHERE migration_key = %s LIMIT 1", SC_EI_Support_Repository::PATCH_MIGRATION_KEY ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		self::add( $checks, 'support_reliability_patch', __( 'v1.2.1 support reliability migration journal', 'sustainable-catalyst-engagement-intake' ), 'completed' === (string) $support_patch_migration, (string) ( $support_patch_migration ?: 'missing' ) );

		$page_evidence = SC_EI_Platform_Repository::page_contract_evidence();
		self::add( $checks, 'public_page_contracts', __( 'Published public-entry and portal page contracts', 'sustainable-catalyst-engagement-intake' ), ! empty( $page_evidence['public_entry']['passed'] ) && ! empty( $page_evidence['portal']['passed'] ), (string) ( $page_evidence['summary'] ?? '' ) );

		$cron_evidence = SC_EI_Platform_Repository::cron_evidence();
		self::add( $checks, 'cron_runtime', __( 'Scheduled jobs and registered callbacks', 'sustainable-catalyst-engagement-intake' ), ! empty( $cron_evidence['passed'] ), (string) ( $cron_evidence['detail'] ?? '' ) );

		$accessibility = SC_EI_Platform_Repository::accessibility_evidence();
		self::add( $checks, 'accessibility_runtime', __( 'Rendered accessibility contract', 'sustainable-catalyst-engagement-intake' ), ! empty( $accessibility['passed'] ), (string) ( $accessibility['detail'] ?? '' ) );

		$duplicate_controls = SC_EI_Form_Handler::validation_duplicate_controls();
		self::add(
			$checks,
			'duplicate_controls',
			__( 'Duplicate-submission and concurrent-request controls', 'sustainable-catalyst-engagement-intake' ),
			! empty( $duplicate_controls['passed'] ),
			! empty( $duplicate_controls['passed'] ) ? 'duplicate fingerprint and request lock both blocked a second attempt and cleaned up' : wp_json_encode( $duplicate_controls )
		);

		$route_evidence = SC_EI_Pilot_Operations::route_contract_evidence();
		self::add( $checks, 'routed_entry_contracts', __( 'Advisory, AI Assurance, collaboration, media, and technical entry routes', 'sustainable-catalyst-engagement-intake' ), ! empty( $route_evidence['passed'] ), (string) ( $route_evidence['detail'] ?? '' ) );

		$upload_security = SC_EI_Upload_Validator::runtime_security_probe();
		self::add( $checks, 'upload_security_runtime', __( 'Upload validator clean-file acceptance and executable rejection', 'sustainable-catalyst-engagement-intake' ), ! empty( $upload_security['passed'] ), (string) ( $upload_security['detail'] ?? '' ) );

		$storage_probe = SC_EI_Storage::probe();
		$storage_health = SC_EI_Storage::storage_health();
		$storage_secure = ! empty( $storage_health['outside_document_root'] ) || ! empty( $storage_health['protection_files'] );
		self::add( $checks, 'protected_storage_runtime', __( 'Protected storage write/read/rename/delete and exposure posture', 'sustainable-catalyst-engagement-intake' ), ! empty( $storage_probe['ok'] ) && $storage_secure, (string) ( $storage_probe['message'] ?? '' ) . '; ' . ( $storage_secure ? 'exposure controls present' : 'exposure controls unavailable' ) );

		try {
			$validation_email = is_email( $mail_recipient ) ? $mail_recipient : (string) get_option( 'admin_email' );
			$inquiry_id = SC_EI_Inquiry_Repository::create(
				array(
					'inquiry_type'    => 'general',
					'contact_name'    => 'Platform Validation',
					'contact_email'   => $validation_email,
					'subject'         => '[TEST] v1.2.1 live validation',
					'message'         => 'Temporary administrator-generated validation record. Safe to remove.',
					'form_variant'    => 'advanced',
					'source_page'     => 'platform-validation',
					'entry_cta'       => 'admin-live-validation',
					'metadata'        => array( 'platform_validation' => true, 'plugin_version' => SC_EI_VERSION ),
					'consent_version' => 'admin-validation',
					'consent_at'      => current_time( 'mysql', true ),
				)
			);
			$created = $inquiry_id > 0 && null !== SC_EI_Inquiry_Repository::find( $inquiry_id );
			$transition_result = $created ? SC_EI_Lifecycle_Repository::transition(
				$inquiry_id,
				'under_review',
				$actor_user_id,
				array(
					'reason'      => 'Platform live validation lifecycle transition.',
					'next_action' => 'Complete the temporary validation review.',
				)
			) : new WP_Error( 'platform_validation_inquiry_missing', 'Validation inquiry unavailable.' );
			$transitioned = ! is_wp_error( $transition_result );
			$fresh = $transitioned ? SC_EI_Inquiry_Repository::find( $inquiry_id ) : null;
			$lifecycle_events = $transitioned ? SC_EI_Lifecycle_Repository::events( $inquiry_id, 20 ) : array();
			$sender_snapshot = $transitioned ? SC_EI_Lifecycle_Repository::sender_snapshot( $inquiry_id ) : array();
			$lifecycle_ok = $created
				&& $transitioned
				&& 'under_review' === (string) ( $fresh['status'] ?? '' )
				&& 'under_review' === (string) ( $fresh['lifecycle_stage'] ?? '' )
				&& ! empty( $lifecycle_events )
				&& '' !== trim( (string) ( $sender_snapshot['label'] ?? '' ) )
				&& 'Complete the temporary validation review.' === (string) ( $sender_snapshot['next_step'] ?? '' );
			self::add( $checks, 'inquiry_lifecycle', __( 'Inquiry persistence, audited lifecycle transition, and sender-safe projection', 'sustainable-catalyst-engagement-intake' ), $lifecycle_ok, $lifecycle_ok ? 'temporary inquiry created, transitioned through the governed lifecycle, audited, and safely projected' : ( is_wp_error( $transition_result ) ? $transition_result->get_error_message() : 'temporary lifecycle validation failed' ) );

			$support_case = $created ? SC_EI_Support_Repository::create_for_inquiry(
				$inquiry_id,
				array(
					'product'            => 'workbench',
					'product_version'    => 'validation',
					'component'          => 'live-validation',
					'issue_type'         => 'runtime_error',
					'error_message'      => 'Temporary support validation fixture.',
					'reproduction_steps' => 'Run the administrator live validation suite.',
					'source_system'      => 'platform_validation',
				),
				$actor_user_id
			) : new WP_Error( 'platform_validation_inquiry_missing', 'Validation inquiry unavailable.' );
			if ( ! is_wp_error( $support_case ) ) {
				$support_case_id = absint( $support_case['id'] ?? 0 );
				$support_transition = SC_EI_Support_Repository::transition(
					$support_case_id,
					'triage',
					'MOVE ' . strtoupper( (string) $support_case['case_number'] ) . ' TO TRIAGE',
					'Administrator live validation support transition.',
					$actor_user_id
				);
			} else {
				$support_transition = $support_case;
			}
			$support_snapshot = $support_case_id ? SC_EI_Support_Repository::sender_snapshot( $inquiry_id ) : array();
			$private_rejection = SC_EI_Support_Schema::signal_payload( array( 'product' => 'workbench', 'email' => 'private@example.com' ) );
			$handoff_id = 'validation-' . wp_generate_uuid4();
			$handoff_payload = array(
				'schema' => SC_EI_Support_Schema::HANDOFF_SCHEMA,
				'handoff_id' => $handoff_id,
				'inquiry_id' => $inquiry_id,
				'source_system' => 'feature_suggestions',
				'source_reference' => 'validation',
				'context' => array(
					'product' => 'workbench',
					'product_version' => 'validation',
					'component' => 'live-validation',
					'issue_type' => 'documentation',
					'search_query' => 'temporary validation query',
					'article_ids' => array( 101 ),
					'known_issue' => 'WB-VALIDATION',
					'feature_suggestion' => 'FS-VALIDATION',
					'product_release' => 'WB-vNext',
					'resolution_attempted' => true,
					'source_url' => home_url( '/support/' ),
				),
			);
			$handoff_first = SC_EI_Support_Repository::ingest_handoff( $handoff_payload, $actor_user_id );
			$handoff_second = SC_EI_Support_Repository::ingest_handoff( $handoff_payload, $actor_user_id );
			if ( ! is_wp_error( $handoff_first ) ) {
				$links = SC_EI_Support_Repository::links( $support_case_id );
				$support_link_ids = array_map( 'absint', wp_list_pluck( $links, 'id' ) );
				$signal_row = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM " . SC_EI_Database::table( 'support_signals' ) . " WHERE product = %s AND product_version = %s AND component = %s ORDER BY id DESC LIMIT 1", 'workbench', 'validation', 'live-validation' ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$support_signal_id = absint( $signal_row['id'] ?? 0 );
			}
			$unsafe_keys = array_intersect( array_keys( $support_snapshot ), array( 'error_message', 'reproduction_steps', 'assigned_user_id', 'environment_json' ) );
			$support_ok = ! is_wp_error( $support_case )
				&& ! is_wp_error( $support_transition )
				&& 'triage' === (string) ( $support_transition['workflow_stage'] ?? '' )
				&& ! empty( $support_snapshot['case_number'] )
				&& 'Under Review' === (string) ( $support_snapshot['status'] ?? '' )
				&& empty( $unsafe_keys )
				&& is_wp_error( $private_rejection )
				&& ! is_wp_error( $handoff_first )
				&& ! is_wp_error( $handoff_second )
				&& empty( $handoff_first['idempotent'] )
				&& ! empty( $handoff_second['idempotent'] )
				&& count( $support_link_ids ) >= 4;
			self::add( $checks, 'support_operations', __( 'Support persistence, governed transition, Sender Portal isolation, typed relationships, and idempotent privacy-safe handoff', 'sustainable-catalyst-engagement-intake' ), $support_ok, $support_ok ? 'temporary support case created; sender projection isolated; known-issue, feature, release, and handoff relationships created; duplicate handoff replayed idempotently' : ( is_wp_error( $support_transition ) ? $support_transition->get_error_message() : ( is_wp_error( $handoff_first ) ? $handoff_first->get_error_message() : ( is_wp_error( $handoff_second ) ? $handoff_second->get_error_message() : 'support operations validation failed' ) ) ) );

			$portal_result = $created ? SC_EI_Portal_Repository::issue_invitation(
				$inquiry_id,
				array(
					'invite_ttl_hours' => 1,
					'permissions'      => SC_EI_Portal_Schema::default_settings()['portal_default_permissions'],
					'invitation_note'  => 'Temporary live-validation invitation; not emailed.',
				),
				$actor_user_id
			) : new WP_Error( 'platform_validation_inquiry_missing', 'Validation inquiry unavailable.' );
			$portal_ok = ! is_wp_error( $portal_result ) && ! empty( $portal_result['raw_token'] ) && ! empty( $portal_result['access']['public_id'] );
			if ( $portal_ok ) {
				$access_id = absint( $portal_result['access']['id'] ?? 0 );
				$inspection = SC_EI_Portal_Repository::inspect_invitation( (string) $portal_result['access']['public_id'], (string) $portal_result['raw_token'] );
				$portal_ok = ! empty( $inspection['verified'] );
			}
			self::add( $checks, 'portal_invitation', __( 'Sender portal token issue and verification', 'sustainable-catalyst-engagement-intake' ), $portal_ok, $portal_ok ? 'temporary invitation verified without storing the raw token' : ( is_wp_error( $portal_result ) ? $portal_result->get_error_message() : 'portal token verification failed' ) );

			$temp_path = wp_tempnam( 'sc-ei-platform-validation.txt' );
			if ( $temp_path ) {
				$content = 'Sustainable Catalyst platform validation ' . wp_generate_uuid4();
				file_put_contents( $temp_path, $content, LOCK_EX );
				$relative_path = 'quarantine/validation/' . wp_generate_uuid4() . '.qtn';
				$stored = SC_EI_Storage::store_uploaded_file_verified( $temp_path, $relative_path, strlen( $content ), hash( 'sha256', $content ) );
				$file_ok = ! is_wp_error( $stored ) && SC_EI_Storage::verify_integrity( $relative_path, hash( 'sha256', $content ) );
				self::add( $checks, 'private_file_lifecycle', __( 'Private file store, integrity verification, and deletion', 'sustainable-catalyst-engagement-intake' ), $file_ok, $file_ok ? 'temporary private file stored and hash verified' : ( is_wp_error( $stored ) ? $stored->get_error_message() : 'private file verification failed' ) );
			} else {
				self::add( $checks, 'private_file_lifecycle', __( 'Private file store, integrity verification, and deletion', 'sustainable-catalyst-engagement-intake' ), false, 'temporary file creation failed' );
			}
		} catch ( Throwable $error ) {
			self::add( $checks, 'runtime_exception', __( 'Runtime exception boundary', 'sustainable-catalyst-engagement-intake' ), false, get_class( $error ) . ': ' . $error->getMessage() );
		}

		if ( is_email( $mail_recipient ) ) {
			$mail_result = SC_EI_Notification_Service::test_notification( $mail_recipient, $actor_user_id );
			self::add( $checks, 'mail_transport', __( 'WordPress mail transport acceptance', 'sustainable-catalyst-engagement-intake' ), ! is_wp_error( $mail_result ) && ! empty( $mail_result['accepted'] ), is_wp_error( $mail_result ) ? $mail_result->get_error_message() : 'WordPress accepted the test message; inbox delivery must still be confirmed manually' );
		} else {
			self::add( $checks, 'mail_transport', __( 'WordPress mail transport acceptance', 'sustainable-catalyst-engagement-intake' ), false, 'A valid test recipient is required.' );
		}

		if ( $relative_path ) {
			$cleanup_ok = SC_EI_Storage::delete_file( $relative_path ) && $cleanup_ok;
		}
		if ( $temp_path && file_exists( $temp_path ) ) {
			$cleanup_ok = wp_delete_file( $temp_path ) && $cleanup_ok;
		}
		if ( $support_signal_id ) {
			$wpdb->delete( SC_EI_Database::table( 'support_signals' ), array( 'id' => $support_signal_id ), array( '%d' ) );
		}
		if ( $inquiry_id ) {
			$wpdb->delete( SC_EI_Database::table( 'support_case_links' ), array( 'inquiry_id' => $inquiry_id ), array( '%d' ) );
			$wpdb->delete( SC_EI_Database::table( 'support_case_events' ), array( 'inquiry_id' => $inquiry_id ), array( '%d' ) );
			$wpdb->delete( SC_EI_Database::table( 'support_cases' ), array( 'inquiry_id' => $inquiry_id ), array( '%d' ) );
			$wpdb->delete( SC_EI_Database::table( 'lifecycle_tasks' ), array( 'inquiry_id' => $inquiry_id ), array( '%d' ) );
			$wpdb->delete( SC_EI_Database::table( 'lifecycle_notes' ), array( 'inquiry_id' => $inquiry_id ), array( '%d' ) );
			$wpdb->delete( SC_EI_Database::table( 'lifecycle_events' ), array( 'inquiry_id' => $inquiry_id ), array( '%d' ) );
			if ( $access_id ) {
				$wpdb->delete( SC_EI_Database::table( 'portal_sessions' ), array( 'access_id' => $access_id ), array( '%d' ) );
			}
			$wpdb->delete( SC_EI_Database::table( 'portal_events' ), array( 'inquiry_id' => $inquiry_id ), array( '%d' ) );
			$wpdb->delete( SC_EI_Database::table( 'portal_access' ), array( 'inquiry_id' => $inquiry_id ), array( '%d' ) );
			$wpdb->delete( SC_EI_Database::table( 'audit_log' ), array( 'inquiry_id' => $inquiry_id ), array( '%d' ) );
			$wpdb->delete( SC_EI_Database::table( 'inquiries' ), array( 'id' => $inquiry_id ), array( '%d' ) );
			wp_clear_scheduled_hook( SC_EI_Workflow_Core_Service::SYNC_INQUIRY_HOOK, array( $inquiry_id ) );
			$cleanup_ok = null === SC_EI_Inquiry_Repository::find( $inquiry_id ) && $cleanup_ok;
		}
		self::add( $checks, 'test_cleanup', __( 'Validation test-data cleanup', 'sustainable-catalyst-engagement-intake' ), $cleanup_ok, $cleanup_ok ? 'temporary records and files removed' : 'one or more temporary artifacts may require review' );

		$failures = array_values( array_filter( $checks, static fn( array $check ): bool => 'pass' !== $check['status'] ) );
		$result = array(
			'schema'         => 'sc-contact-engagement-live-validation/1.4',
			'plugin_version' => SC_EI_VERSION,
			'passed'         => empty( $failures ),
			'score'          => $checks ? (int) round( 100 * ( count( $checks ) - count( $failures ) ) / count( $checks ) ) : 0,
			'checks'         => $checks,
			'failure_count'  => count( $failures ),
			'mail_recipient_domain' => self::email_domain( $mail_recipient ),
			'run_by'         => $actor_user_id,
			'completed_at'   => current_time( 'mysql', true ),
			'duration_seconds' => round( microtime( true ) - $started, 4 ),
		);
		$result['content_hash'] = hash( 'sha256', wp_json_encode( $result, JSON_UNESCAPED_SLASHES ) );
		update_option( self::RESULT_OPTION, $result, false );
		SC_EI_Audit_Log::record(
			'platform_live_validation_completed',
			$result['passed'] ? 'Admin-only production live validation passed.' : 'Admin-only production live validation found one or more failures.',
			array(
				'passed'        => $result['passed'],
				'score'         => $result['score'],
				'failure_count' => $result['failure_count'],
				'content_hash'  => $result['content_hash'],
			),
			null,
			null,
			$actor_user_id
		);
		return $result;
	}

	private static function add( array &$checks, string $key, string $label, bool $passed, string $detail ): void {
		$checks[] = array(
			'key'    => sanitize_key( $key ),
			'label'  => sanitize_text_field( $label ),
			'status' => $passed ? 'pass' : 'fail',
			'detail' => mb_substr( sanitize_text_field( $detail ), 0, 1000 ),
		);
	}

	private static function fresh_timestamp( string $timestamp ): bool {
		if ( '' === $timestamp ) {
			return false;
		}
		$unix = strtotime( $timestamp . ' UTC' );
		return false !== $unix && $unix >= time() - self::MAX_AGE_DAYS * DAY_IN_SECONDS;
	}

	private static function email_domain( string $email ): string {
		$parts = explode( '@', sanitize_email( $email ) );
		return count( $parts ) === 2 ? strtolower( $parts[1] ) : '';
	}
}
