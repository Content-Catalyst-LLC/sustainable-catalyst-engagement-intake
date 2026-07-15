<?php
/**
 * Unified platform readiness, launch state, migrations, snapshots, and exports.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Platform_Repository {

	public const SNAPSHOT_HOOK = 'sc_ei_platform_readiness_snapshot';
	public const MIGRATION_KEY = 'v1_0_0_unified_contact_engagement_platform';
	public const PATCH_MIGRATION_KEY = 'v1_0_2_production_readiness_live_validation';
	public const LAUNCH_MIGRATION_KEY = 'v1_0_3_pilot_findings_public_launch_hardening';
	public const PERSISTENCE_PATCH_MIGRATION_KEY = 'v1_1_1_inquiry_persistence_lifecycle_reliability';

	public static function maybe_upgrade(): void {
		$stored_version = (string) get_option( 'sc_ei_version', '' );
		$stored_schema = (string) get_option( 'sc_ei_platform_schema_version', '' );
		$upgrade_required = version_compare( $stored_version, SC_EI_VERSION, '<' ) || SC_EI_PLATFORM_SCHEMA_VERSION !== $stored_schema;
		if ( $upgrade_required ) {
			update_option( 'sc_ei_version_previous', $stored_version, false );
			update_option( 'sc_ei_version', SC_EI_VERSION, false );
			update_option( 'sc_ei_platform_schema_version', SC_EI_PLATFORM_SCHEMA_VERSION, false );
			self::run_migrations( $stored_version );
		}
		self::record_patch_migration( $stored_version );
		self::record_launch_migration( $stored_version );
		self::record_persistence_patch_migration( $stored_version );
		SC_EI_Support_Repository::record_migration( $stored_version );
		SC_EI_Calendar_Repository::record_migration( (string) get_option( 'sc_ei_calendar_schema_version_previous', '' ) );
		self::schedule_all();
	}

	public static function register(): void {
		add_action( self::SNAPSHOT_HOOK, array( __CLASS__, 'daily_snapshot' ) );
	}

	public static function settings(): array {
		return wp_parse_args(
			get_option( 'sc_ei_settings', array() ),
			SC_EI_Platform_Schema::default_settings()
		);
	}

	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::SNAPSHOT_HOOK ) ) {
			wp_schedule_event( time() + 2 * HOUR_IN_SECONDS, 'daily', self::SNAPSHOT_HOOK );
		}
	}

	public static function unschedule(): void {
		wp_clear_scheduled_hook( self::SNAPSHOT_HOOK );
	}

	public static function daily_snapshot(): void {
		$settings = self::settings();
		if ( empty( $settings['platform_enabled'] ) || empty( $settings['platform_readiness_snapshot_daily'] ) ) {
			return;
		}
		self::create_snapshot( 0, 'scheduled' );
		self::purge_snapshots();
	}

	public static function run_migrations( string $from_version = '' ) {
		global $wpdb;
		$table = SC_EI_Database::table( 'platform_migrations' );
		$existing = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE migration_key = %s LIMIT 1", self::MIGRATION_KEY ),
			ARRAY_A
		);
		if ( $existing && 'completed' === $existing['status'] ) {
			return $existing;
		}

		$now = current_time( 'mysql', true );
		$schema_hash = self::schema_hash();
		$context = array(
			'from_version' => sanitize_text_field( $from_version ),
			'to_version'   => SC_EI_VERSION,
			'database'     => SC_EI_DB_VERSION,
			'schemas'      => self::schema_versions(),
			'no_destructive_migration' => true,
		);
		$data = array(
			'public_id'       => wp_generate_uuid4(),
			'migration_key'   => self::MIGRATION_KEY,
			'from_version'    => sanitize_text_field( $from_version ),
			'to_version'      => SC_EI_VERSION,
			'status'          => 'processing',
			'schema_hash'     => $schema_hash,
			'context_json'    => wp_json_encode( $context, JSON_UNESCAPED_SLASHES ),
			'started_at'      => $now,
			'completed_at'    => null,
			'error_code'      => '',
			'error_message'   => '',
			'created_at'      => $now,
			'updated_at'      => $now,
		);
		if ( $existing ) {
			$wpdb->update( $table, $data, array( 'id' => absint( $existing['id'] ) ), self::formats( $data, array() ), array( '%d' ) );
			$id = absint( $existing['id'] );
		} else {
			$inserted = $wpdb->insert( $table, $data, self::formats( $data, array() ) );
			if ( false === $inserted ) {
				return new WP_Error( 'platform_migration_journal_failed', __( 'The platform migration journal could not be created.', 'sustainable-catalyst-engagement-intake' ) );
			}
			$id = (int) $wpdb->insert_id;
		}

		$tables = SC_EI_Database::tables_exist();
		$columns = SC_EI_Database::platform_columns_exist();
		$ok = ! in_array( false, $tables, true ) && ! in_array( false, $columns, true );
		$completed = current_time( 'mysql', true );
		$wpdb->update(
			$table,
			array(
				'status'        => $ok ? 'completed' : 'failed',
				'completed_at'  => $completed,
				'error_code'    => $ok ? '' : 'platform_schema_incomplete',
				'error_message' => $ok ? '' : 'One or more required platform tables or columns are unavailable.',
				'updated_at'    => $completed,
			),
			array( 'id' => $id ),
			array( '%s', '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);
		$result = self::find_migration( $id );
		SC_EI_Audit_Log::record(
			$ok ? 'platform_migration_completed' : 'platform_migration_failed',
			$ok ? 'Unified platform migration journal completed without destructive data conversion.' : 'Unified platform migration verification found an incomplete schema.',
			array(
				'migration_key' => self::MIGRATION_KEY,
				'from_version'  => sanitize_text_field( $from_version ),
				'to_version'    => SC_EI_VERSION,
				'schema_hash'   => $schema_hash,
				'destructive'   => false,
			)
		);
		return $ok ? $result : new WP_Error( 'platform_schema_incomplete', __( 'The unified platform migration requires attention in Diagnostics.', 'sustainable-catalyst-engagement-intake' ), $result );
	}


	public static function record_patch_migration( string $from_version = '' ) {
		global $wpdb;
		$table = SC_EI_Database::table( 'platform_migrations' );
		$existing = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE migration_key = %s LIMIT 1", self::PATCH_MIGRATION_KEY ),
			ARRAY_A
		);
		if ( $existing && 'completed' === (string) $existing['status'] ) {
			return $existing;
		}
		$now = current_time( 'mysql', true );
		$data = array(
			'public_id'     => $existing['public_id'] ?? wp_generate_uuid4(),
			'migration_key' => self::PATCH_MIGRATION_KEY,
			'from_version'  => sanitize_text_field( $from_version ),
			'to_version'    => '1.0.2',
			'status'        => 'completed',
			'schema_hash'   => self::schema_hash(),
			'context_json'  => wp_json_encode(
				array(
					'release'                  => 'Production Readiness and Live Validation',
					'option_backed_evidence'   => true,
					'database_schema_changed'  => false,
					'no_destructive_migration' => true,
				),
				JSON_UNESCAPED_SLASHES
			),
			'started_at'    => $existing['started_at'] ?? $now,
			'completed_at'  => $now,
			'error_code'    => '',
			'error_message' => '',
			'created_at'    => $existing['created_at'] ?? $now,
			'updated_at'    => $now,
		);
		if ( $existing ) {
			$ok = $wpdb->update( $table, $data, array( 'id' => absint( $existing['id'] ) ), self::formats( $data, array() ), array( '%d' ) );
			$id = absint( $existing['id'] );
		} else {
			$ok = $wpdb->insert( $table, $data, self::formats( $data, array() ) );
			$id = (int) $wpdb->insert_id;
		}
		if ( false === $ok ) {
			return new WP_Error( 'platform_patch_migration_journal_failed', __( 'The v1.0.2 release journal could not be recorded.', 'sustainable-catalyst-engagement-intake' ) );
		}
		SC_EI_Audit_Log::record(
			'platform_patch_upgrade_recorded',
			'Production readiness and live-validation release upgrade recorded without a destructive database migration.',
			array( 'migration_key' => self::PATCH_MIGRATION_KEY, 'from_version' => $from_version, 'to_version' => '1.0.2' ),
			null,
			null,
			get_current_user_id()
		);
		return self::find_migration( $id );
	}


	public static function record_launch_migration( string $from_version = '' ) {
		global $wpdb;
		$table = SC_EI_Database::table( 'platform_migrations' );
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE migration_key = %s LIMIT 1", self::LAUNCH_MIGRATION_KEY ), ARRAY_A );
		if ( $existing && 'completed' === (string) $existing['status'] ) {
			return $existing;
		}
		$now = current_time( 'mysql', true );
		$data = array(
			'public_id' => $existing['public_id'] ?? wp_generate_uuid4(),
			'migration_key' => self::LAUNCH_MIGRATION_KEY,
			'from_version' => sanitize_text_field( $from_version ),
			'to_version' => '1.0.3',
			'status' => 'completed',
			'schema_hash' => self::schema_hash(),
			'context_json' => wp_json_encode( array( 'release' => 'Pilot Findings and Public Launch Hardening', 'routed_public_entries' => true, 'pilot_evidence' => true, 'external_mail_confirmation' => true, 'database_schema_changed' => false, 'no_destructive_migration' => true ), JSON_UNESCAPED_SLASHES ),
			'started_at' => $existing['started_at'] ?? $now,
			'completed_at' => $now,
			'error_code' => '',
			'error_message' => '',
			'created_at' => $existing['created_at'] ?? $now,
			'updated_at' => $now,
		);
		if ( $existing ) {
			$ok = $wpdb->update( $table, $data, array( 'id' => absint( $existing['id'] ) ), self::formats( $data, array() ), array( '%d' ) );
			$id = absint( $existing['id'] );
		} else {
			$ok = $wpdb->insert( $table, $data, self::formats( $data, array() ) );
			$id = (int) $wpdb->insert_id;
		}
		if ( false === $ok ) {
			return new WP_Error( 'platform_launch_migration_journal_failed', __( 'The v1.0.3 release journal could not be recorded.', 'sustainable-catalyst-engagement-intake' ) );
		}
		SC_EI_Audit_Log::record( 'platform_launch_hardening_upgrade_recorded', 'Pilot findings and public-launch hardening upgrade recorded without a destructive database migration.', array( 'migration_key' => self::LAUNCH_MIGRATION_KEY, 'from_version' => $from_version, 'to_version' => '1.0.3' ), null, null, get_current_user_id() );
		return self::find_migration( $id );
	}


	public static function record_persistence_patch_migration( string $from_version = '' ) {
		global $wpdb;
		$table = SC_EI_Database::table( 'platform_migrations' );
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE migration_key = %s LIMIT 1", self::PERSISTENCE_PATCH_MIGRATION_KEY ), ARRAY_A );
		if ( $existing && 'completed' === (string) $existing['status'] ) {
			return $existing;
		}
		$contract = SC_EI_Database::required_contract();
		$ok = ! in_array( false, $contract, true );
		$now = current_time( 'mysql', true );
		$data = array(
			'public_id' => $existing['public_id'] ?? wp_generate_uuid4(),
			'migration_key' => self::PERSISTENCE_PATCH_MIGRATION_KEY,
			'from_version' => sanitize_text_field( $from_version ),
			'to_version' => '1.1.1',
			'status' => $ok ? 'completed' : 'failed',
			'schema_hash' => self::schema_hash(),
			'context_json' => wp_json_encode(
				array(
					'release' => 'Inquiry Persistence and Lifecycle Reliability Patch',
					'qualification_score_default' => 0,
					'database_contract_verified' => $ok,
					'missing_contract_items' => array_keys( array_filter( $contract, static fn( bool $available ): bool => ! $available ) ),
					'database_schema_changed' => false,
					'no_destructive_migration' => true,
				),
				JSON_UNESCAPED_SLASHES
			),
			'started_at' => $existing['started_at'] ?? $now,
			'completed_at' => $now,
			'error_code' => $ok ? '' : 'inquiry_persistence_contract_incomplete',
			'error_message' => $ok ? '' : 'The inquiry persistence contract is incomplete.',
			'created_at' => $existing['created_at'] ?? $now,
			'updated_at' => $now,
		);
		if ( $existing ) {
			$write = $wpdb->update( $table, $data, array( 'id' => absint( $existing['id'] ) ), self::formats( $data, array() ), array( '%d' ) );
			$id = absint( $existing['id'] );
		} else {
			$write = $wpdb->insert( $table, $data, self::formats( $data, array() ) );
			$id = (int) $wpdb->insert_id;
		}
		if ( false === $write ) {
			return new WP_Error( 'platform_persistence_patch_journal_failed', __( 'The v1.1.1 persistence reliability journal could not be recorded.', 'sustainable-catalyst-engagement-intake' ) );
		}
		SC_EI_Audit_Log::record(
			$ok ? 'platform_persistence_patch_recorded' : 'platform_persistence_patch_failed',
			$ok ? 'Inquiry persistence and lifecycle reliability patch recorded after runtime database-contract verification.' : 'Inquiry persistence patch found an incomplete runtime database contract.',
			array( 'migration_key' => self::PERSISTENCE_PATCH_MIGRATION_KEY, 'from_version' => $from_version, 'to_version' => '1.1.1', 'database_schema_changed' => false ),
			null,
			null,
			get_current_user_id()
		);
		return $ok ? self::find_migration( $id ) : new WP_Error( 'inquiry_persistence_contract_incomplete', __( 'The v1.1.1 inquiry persistence contract requires database repair.', 'sustainable-catalyst-engagement-intake' ), self::find_migration( $id ) );
	}

	public static function readiness(): array {
		$settings = self::settings();
		$storage = SC_EI_Storage::storage_health();
		$tables = SC_EI_Database::tables_exist();
		$platform_columns = SC_EI_Database::platform_columns_exist();
		$inquiry_columns = SC_EI_Database::inquiry_columns_exist();
		$lifecycle_columns = SC_EI_Database::lifecycle_columns_exist();
		$support_columns = SC_EI_Database::support_columns_exist();
		$calendar_columns = SC_EI_Database::calendar_columns_exist();
		$lifecycle_metrics = SC_EI_Lifecycle_Repository::metrics();
		$support_metrics = SC_EI_Support_Repository::metrics();
		$calendar_metrics = SC_EI_Calendar_Repository::metrics();
		$hardening = SC_EI_Hardening_Repository::metrics();
		$core = SC_EI_Workflow_Core_Repository::metrics();
		$portal_url = self::effective_url( 'platform_portal_page_url', 'portal_page_url' );
		$contact_url = self::effective_url( 'platform_contact_page_url', '' );
		$engagement_url = self::effective_url( 'platform_engagement_page_url', '' );
		$privacy_url = self::effective_url( 'platform_privacy_page_url', '' );
		$entry_pages = self::discover_entry_pages();
		$page_evidence = self::page_contract_evidence();
		$cron_evidence = self::cron_evidence();
		$accessibility = self::accessibility_evidence();
		$targets = SC_EI_Workflow_Core_Service::registered_targets();
		$expected_targets = SC_EI_Workflow_Core_Schema::handoff_targets();
		$registry_ok = is_array( $targets ) && array_keys( $targets ) === array_keys( $expected_targets );
		$registered_count = count( array_filter( $targets, static fn( array $target ): bool => ! empty( $target['registered'] ) ) );
		$live_validation = SC_EI_Platform_Validation::latest();
		$backup_attestation = SC_EI_Platform_Validation::backup_attestation();
		$pilot_evidence = SC_EI_Pilot_Operations::pilot_evidence();
		$mail_evidence = SC_EI_Pilot_Operations::external_mail_evidence();
		$operations = SC_EI_Pilot_Operations::operational_summary();
		$route_evidence = SC_EI_Pilot_Operations::route_contract_evidence();

		$checks = array();
		$checks[] = self::check( 'platform_version', 'platform', __( 'Installed platform version state', 'sustainable-catalyst-engagement-intake' ), SC_EI_VERSION === (string) get_option( 'sc_ei_version', '' ), true, SC_EI_VERSION . ' / ' . (string) get_option( 'sc_ei_version', '' ), 'refresh_version' );
		$checks[] = self::check( 'database_version', 'data', __( 'Database version', 'sustainable-catalyst-engagement-intake' ), SC_EI_DB_VERSION === (string) get_option( 'sc_ei_db_version', '' ), true, (string) get_option( 'sc_ei_db_version', '' ), 'repair_database' );
		$checks[] = self::check( 'database_tables', 'data', __( 'Required database tables', 'sustainable-catalyst-engagement-intake' ), ! in_array( false, $tables, true ), true, sprintf( '%d/%d', count( array_filter( $tables ) ), count( $tables ) ), 'repair_database' );
		$checks[] = self::check( 'platform_columns', 'data', __( 'Platform governance schema', 'sustainable-catalyst-engagement-intake' ), ! in_array( false, $platform_columns, true ), true, sprintf( '%d/%d', count( array_filter( $platform_columns ) ), count( $platform_columns ) ), 'repair_database' );
		$checks[] = self::check( 'inquiry_columns', 'data', __( 'Inquiry persistence schema', 'sustainable-catalyst-engagement-intake' ), ! in_array( false, $inquiry_columns, true ), true, sprintf( '%d/%d', count( array_filter( $inquiry_columns ) ), count( $inquiry_columns ) ), 'repair_database' );
		$checks[] = self::check( 'lifecycle_columns', 'data', __( 'Advisory lifecycle schema', 'sustainable-catalyst-engagement-intake' ), ! in_array( false, $lifecycle_columns, true ), true, sprintf( '%d/%d', count( array_filter( $lifecycle_columns ) ), count( $lifecycle_columns ) ), 'repair_database' );
		$checks[] = self::check( 'support_columns', 'data', __( 'Product support operations schema', 'sustainable-catalyst-engagement-intake' ), ! in_array( false, $support_columns, true ), true, sprintf( '%d/%d', count( array_filter( $support_columns ) ), count( $support_columns ) ), 'repair_database' );
		$checks[] = self::check( 'calendar_columns', 'data', __( 'Microsoft Teams and calendar-coordination schema', 'sustainable-catalyst-engagement-intake' ), ! in_array( false, $calendar_columns, true ), true, sprintf( '%d/%d', count( array_filter( $calendar_columns ) ), count( $calendar_columns ) ), 'repair_database' );
		$checks[] = self::check( 'migration_journal', 'data', __( 'v1.0 base migration journal', 'sustainable-catalyst-engagement-intake' ), self::migration_completed( self::MIGRATION_KEY ), true, self::MIGRATION_KEY, 'verify_migration' );
		$checks[] = self::check( 'patch_migration_journal', 'data', __( 'v1.0.2 upgrade journal', 'sustainable-catalyst-engagement-intake' ), self::migration_completed( self::PATCH_MIGRATION_KEY ), true, self::PATCH_MIGRATION_KEY, 'verify_patch_migration' );
		$checks[] = self::check( 'launch_migration_journal', 'data', __( 'v1.0.3 launch-hardening journal', 'sustainable-catalyst-engagement-intake' ), self::migration_completed( self::LAUNCH_MIGRATION_KEY ), true, self::LAUNCH_MIGRATION_KEY, 'verify_launch_migration' );
		$checks[] = self::check( 'lifecycle_migration_journal', 'data', __( 'v1.1.0 advisory-lifecycle migration journal', 'sustainable-catalyst-engagement-intake' ), self::migration_completed( SC_EI_Lifecycle_Repository::MIGRATION_KEY ), true, SC_EI_Lifecycle_Repository::MIGRATION_KEY, 'verify_lifecycle_migration' );
		$checks[] = self::check( 'persistence_patch_migration_journal', 'data', __( 'v1.1.1 inquiry-persistence reliability journal', 'sustainable-catalyst-engagement-intake' ), self::migration_completed( self::PERSISTENCE_PATCH_MIGRATION_KEY ), true, self::PERSISTENCE_PATCH_MIGRATION_KEY, 'verify_persistence_patch_migration' );
		$checks[] = self::check( 'support_migration_journal', 'data', __( 'v1.2.0 support-operations migration journal', 'sustainable-catalyst-engagement-intake' ), self::migration_completed( SC_EI_Support_Repository::MIGRATION_KEY ), true, SC_EI_Support_Repository::MIGRATION_KEY, 'verify_support_migration' );
		$checks[] = self::check( 'support_reliability_patch', 'data', __( 'v1.2.1 support reliability migration journal', 'sustainable-catalyst-engagement-intake' ), self::migration_completed( SC_EI_Support_Repository::PATCH_MIGRATION_KEY ), true, SC_EI_Support_Repository::PATCH_MIGRATION_KEY, 'verify_support_reliability_patch' );
		$checks[] = self::check( 'calendar_migration_journal', 'data', __( 'v1.3.0 Teams and calendar-coordination migration journal', 'sustainable-catalyst-engagement-intake' ), self::migration_completed( SC_EI_Calendar_Repository::MIGRATION_KEY ), true, SC_EI_Calendar_Repository::MIGRATION_KEY, 'verify_calendar_migration' );
		$checks[] = self::check( 'support_handoff_contract', 'integrations', __( 'Product-support handoff privacy contract', 'sustainable-catalyst-engagement-intake' ), SC_EI_Support_Schema::HANDOFF_SCHEMA === 'sc-product-support-handoff/1.0' && is_wp_error( SC_EI_Support_Schema::signal_payload( array( 'product' => 'workbench', 'email' => 'private@example.com' ) ) ), true, SC_EI_Support_Schema::HANDOFF_SCHEMA, 'review_support' );
		$checks[] = self::check( 'support_handoff_reliability', 'integrations', __( 'Cross-product handoff reliability', 'sustainable-catalyst-engagement-intake' ), 0 === absint( $support_metrics['handoff_reliability_open'] ?? 0 ), true, sprintf( '%d historical failure(s); last failure %s; last success %s', absint( $support_metrics['failed_handoffs'] ?? 0 ), (string) get_option( 'sc_ei_support_last_handoff_failure_at', 'not recorded' ), (string) get_option( 'sc_ei_support_last_handoff_at', 'not recorded' ) ), 'review_support' );
		$checks[] = self::check( 'support_product_context', 'operations', __( 'Open support product context', 'sustainable-catalyst-engagement-intake' ), 0 === absint( $support_metrics['missing_product'] ?? 0 ), true, sprintf( '%d missing product; %d missing version; %d missing component', absint( $support_metrics['missing_product'] ?? 0 ), absint( $support_metrics['missing_version'] ?? 0 ), absint( $support_metrics['missing_component'] ?? 0 ) ), 'review_support' );
		$calendar_integrity_ok = 0 === absint( $calendar_metrics['missing_timezone'] ?? 0 ) && 0 === absint( $calendar_metrics['canceled_active_link'] ?? 0 );
		$checks[] = self::check( 'calendar_integrity', 'integrations', __( 'Calendar timezone and canceled-link integrity', 'sustainable-catalyst-engagement-intake' ), $calendar_integrity_ok, true, sprintf( '%d scheduled meeting(s) missing timezone; %d canceled meeting(s) retaining an active join link', absint( $calendar_metrics['missing_timezone'] ?? 0 ), absint( $calendar_metrics['canceled_active_link'] ?? 0 ) ), 'review_calendar' );
		$storage_ok = ! empty( $storage['exists'] ) && ! empty( $storage['writable'] ) && ! empty( $storage['marker'] ) && ! empty( $storage['protection_files'] ) && empty( $storage['base_is_symlink'] );
		$checks[] = self::check( 'protected_storage', 'security', __( 'Protected document storage', 'sustainable-catalyst-engagement-intake' ), $storage_ok, ! empty( $settings['platform_require_protected_storage'] ), (string) ( $storage['path'] ?? '' ), 'repair_storage' );
		$https_ok = is_ssl() || SC_EI_Portal_Schema::secure_transport_available();
		$checks[] = self::check( 'https', 'security', __( 'HTTPS and secure portal transport', 'sustainable-catalyst-engagement-intake' ), $https_ok, ! empty( $settings['platform_require_https'] ), $https_ok ? 'available' : 'unavailable', 'review_https' );
		$checks[] = self::check( 'critical_health', 'operations', __( 'Open critical reliability events', 'sustainable-catalyst-engagement-intake' ), 0 === absint( $hardening['open_critical'] ?? 0 ), true, (string) absint( $hardening['open_critical'] ?? 0 ), 'review_reliability' );
		$checks[] = self::check( 'public_writes', 'operations', __( 'Public writes incident state', 'sustainable-catalyst-engagement-intake' ), ! SC_EI_Hardening_Repository::public_writes_paused(), true, SC_EI_Hardening_Repository::public_writes_paused() ? 'paused' : 'open', 'review_reliability' );
		$checks[] = self::check( 'cron_schedules', 'operations', __( 'Required scheduled jobs and callbacks', 'sustainable-catalyst-engagement-intake' ), ! empty( $cron_evidence['passed'] ), true, (string) $cron_evidence['detail'], 'repair_crons' );
		$checks[] = self::check( 'public_entry', 'public_entry', __( 'Published public contact or engagement entry', 'sustainable-catalyst-engagement-intake' ), ! empty( $page_evidence['public_entry']['passed'] ), ! empty( $settings['platform_require_public_entry'] ), (string) $page_evidence['public_entry']['detail'], 'configure_pages' );
		$checks[] = self::check( 'routed_entries', 'public_entry', __( 'Advisory and campaign entry-route contracts', 'sustainable-catalyst-engagement-intake' ), ! empty( $route_evidence['passed'] ), true, (string) ( $route_evidence['detail'] ?? '' ), 'review_routed_entries' );
		$checks[] = self::check( 'portal_url', 'portal', __( 'Published secure sender portal page', 'sustainable-catalyst-engagement-intake' ), ! empty( $page_evidence['portal']['passed'] ), ! empty( $settings['platform_require_portal_url'] ), (string) $page_evidence['portal']['detail'], 'configure_pages' );
		$checks[] = self::check( 'support_email', 'operations', __( 'Platform support email', 'sustainable-catalyst-engagement-intake' ), is_email( (string) $settings['platform_support_email'] ), true, (string) $settings['platform_support_email'], 'configure_settings' );
		$checks[] = self::check( 'workflow_core', 'integrations', __( 'Workflow Core consistency', 'sustainable-catalyst-engagement-intake' ), 0 === absint( $core['blocked_cases'] ?? 0 ), false, sprintf( '%d blocked, %d warning', absint( $core['blocked_cases'] ?? 0 ), absint( $core['warning_cases'] ?? 0 ) ), 'review_workflow_core' );
		$checks[] = self::check( 'adapter_registry', 'integrations', __( 'Internal adapter registry initialization', 'sustainable-catalyst-engagement-intake' ), $registry_ok, true, sprintf( '%d optional adapter(s) registered across %d known target(s)', $registered_count, count( $targets ) ), 'review_workflow_core' );
		$checks[] = self::check( 'privacy_url', 'security', __( 'Published privacy guidance page', 'sustainable-catalyst-engagement-intake' ), ! empty( $page_evidence['privacy']['passed'] ), false, (string) $page_evidence['privacy']['detail'], 'configure_pages' );
		$checks[] = self::check( 'accessibility_controls', 'accessibility', __( 'Rendered accessibility controls', 'sustainable-catalyst-engagement-intake' ), ! empty( $accessibility['passed'] ), true, (string) $accessibility['detail'], 'review_accessibility' );
		$checks[] = self::check( 'live_validation', 'operations', __( 'Recent successful live validation', 'sustainable-catalyst-engagement-intake' ), SC_EI_Platform_Validation::successful_and_fresh(), true, ! empty( $live_validation['completed_at'] ) ? sprintf( '%s · %d%%', $live_validation['completed_at'], absint( $live_validation['score'] ?? 0 ) ) : 'not run', 'run_live_validation' );
		$checks[] = self::check( 'backup_attestation', 'security', __( 'Recent database and protected-storage backup attestation', 'sustainable-catalyst-engagement-intake' ), SC_EI_Platform_Validation::backups_fresh(), true, ! empty( $backup_attestation['attested_at'] ) ? (string) $backup_attestation['attested_at'] : 'not attested', 'attest_backups' );
		$checks[] = self::check( 'external_mail_delivery', 'operations', __( 'Externally confirmed email delivery', 'sustainable-catalyst-engagement-intake' ), SC_EI_Pilot_Operations::external_mail_confirmed_and_fresh(), true, ! empty( $mail_evidence['confirmed_at'] ) ? sprintf( '%s · %s', (string) $mail_evidence['confirmed_at'], (string) $mail_evidence['reference'] ) : 'not confirmed', 'confirm_external_mail' );
		$checks[] = self::check( 'pilot_launch_evidence', 'operations', __( 'Completed controlled pilot and launch checklist', 'sustainable-catalyst-engagement-intake' ), SC_EI_Pilot_Operations::pilot_complete_and_fresh(), true, ! empty( $pilot_evidence['recorded_at'] ) ? sprintf( '%s · %d controlled inquiries', (string) $pilot_evidence['recorded_at'], absint( $pilot_evidence['controlled_inquiry_count'] ?? 0 ) ) : 'not recorded', 'record_pilot_evidence' );
		$checks[] = self::check( 'lifecycle_operations', 'operations', __( 'Advisory lifecycle operational queue', 'sustainable-catalyst-engagement-intake' ), 0 === absint( $lifecycle_metrics['overdue_tasks'] ?? 0 ) && 0 === absint( $lifecycle_metrics['next_actions_due'] ?? 0 ), true, sprintf( '%d overdue task(s); %d next action(s) due', absint( $lifecycle_metrics['overdue_tasks'] ?? 0 ), absint( $lifecycle_metrics['next_actions_due'] ?? 0 ) ), 'review_lifecycle' );
		$checks[] = self::check( 'support_operations', 'operations', __( 'Product support operational queue', 'sustainable-catalyst-engagement-intake' ), 0 === absint( $support_metrics['high_priority'] ?? 0 ), true, sprintf( '%d untriaged; %d high-priority unresolved; %d awaiting sender; %d open intelligence signal(s)', absint( $support_metrics['untriaged'] ?? 0 ), absint( $support_metrics['high_priority'] ?? 0 ), absint( $support_metrics['awaiting_sender'] ?? 0 ), absint( $support_metrics['signals_open'] ?? 0 ) ), 'review_support' );
		$checks[] = self::check( 'operational_attention', 'operations', __( 'No unresolved public-launch operational blockers', 'sustainable-catalyst-engagement-intake' ), ! empty( $operations['clear'] ), true, ! empty( $operations['blockers'] ) ? implode( '; ', (array) $operations['blockers'] ) : 'no failed communications, overdue follow-ups, quarantine or file-integrity issues, portal lockouts or failures, or critical events', 'review_operations' );

		$required_failures = array_values( array_filter( $checks, static fn( array $check ): bool => ! empty( $check['required'] ) && 'fail' === $check['status'] ) );
		$warnings = array_values( array_filter( $checks, static fn( array $check ): bool => 'warn' === $check['status'] ) );
		$passed_count = count( array_filter( $checks, static fn( array $check ): bool => 'pass' === $check['status'] ) );
		$score = $checks ? (int) round( 100 * $passed_count / count( $checks ) ) : 0;
		$ready_for_production = 100 === $score && empty( $required_failures ) && empty( $warnings );

		return array(
			'schema'               => 'sc-unified-contact-engagement-platform-readiness/1.5',
			'generated_at'         => current_time( 'mysql', true ),
			'plugin_version'       => SC_EI_VERSION,
			'database_version'     => (string) get_option( 'sc_ei_db_version', '' ),
			'platform_schema'      => SC_EI_PLATFORM_SCHEMA_VERSION,
			'launch_state'         => SC_EI_Platform_Schema::sanitize_launch_state( (string) $settings['platform_launch_state'] ),
			'score'                => $score,
			'ready_for_production' => $ready_for_production,
			'required_failures'    => $required_failures,
			'warnings'             => $warnings,
			'production_blockers'  => array_values( array_merge( $required_failures, $warnings ) ),
			'checks'               => $checks,
			'entry_pages'          => $entry_pages,
			'page_evidence'        => $page_evidence,
			'urls'                 => array( 'contact' => $contact_url, 'engagement' => $engagement_url, 'portal' => $portal_url, 'privacy' => $privacy_url ),
			'cron'                 => $cron_evidence,
			'live_validation'      => $live_validation,
			'backup_attestation'   => $backup_attestation,
			'pilot_evidence'        => $pilot_evidence,
			'external_mail_evidence'=> $mail_evidence,
			'operations'            => $operations,
			'lifecycle_metrics'     => $lifecycle_metrics,
			'support_metrics'       => $support_metrics,
			'route_evidence'        => $route_evidence,
			'boundaries'           => self::boundaries(),
		);
	}

	public static function platform_summary(): array {
		global $wpdb;
		$readiness = self::readiness();
		$inquiries = SC_EI_Database::table( 'inquiries' );
		return array(
			'readiness' => $readiness,
			'metrics' => array(
				'inquiries_total'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$inquiries} WHERE privacy_status <> 'erased'" ),
				'new_inquiries'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$inquiries} WHERE status = 'new' AND privacy_status <> 'erased'" ),
				'review'            => SC_EI_Review_Repository::metrics( get_current_user_id() ),
				'portal'            => SC_EI_Portal_Repository::metrics(),
				'workflow'          => SC_EI_Workflow_Repository::metrics(),
				'engagement'        => SC_EI_Engagement_Repository::metrics(),
				'analytics'         => SC_EI_Analytics_Repository::dashboard( 30 ),
				'reliability'       => SC_EI_Hardening_Repository::metrics(),
				'workflow_core'     => SC_EI_Workflow_Core_Repository::metrics(),
				'lifecycle'         => SC_EI_Lifecycle_Repository::metrics(),
			),
			'migrations' => self::migrations( 20 ),
			'snapshots'  => self::snapshots( 20 ),
			'targets'    => SC_EI_Workflow_Core_Service::registered_targets(),
			'pilot_operations' => SC_EI_Pilot_Operations::operational_summary(),
		);
	}

	public static function create_snapshot( int $actor_user_id, string $source = 'manual' ) {
		global $wpdb;
		$readiness = self::readiness();
		$json = wp_json_encode( $readiness, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( false === $json ) {
			return new WP_Error( 'platform_snapshot_encode_failed', __( 'The platform readiness snapshot could not be encoded.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		$data = array(
			'public_id'         => wp_generate_uuid4(),
			'snapshot_version'  => 1,
			'launch_state'      => $readiness['launch_state'],
			'readiness_score'   => absint( $readiness['score'] ),
			'required_failures' => count( $readiness['required_failures'] ),
			'warning_count'     => count( $readiness['warnings'] ),
			'payload_json'      => $json,
			'content_hash'      => hash( 'sha256', $json ),
			'source'            => sanitize_key( $source ),
			'generated_by'      => $actor_user_id ?: null,
			'generated_at'      => $now,
		);
		$inserted = $wpdb->insert( SC_EI_Database::table( 'platform_snapshots' ), $data, self::formats( $data, array( 'snapshot_version', 'readiness_score', 'required_failures', 'warning_count', 'generated_by' ) ) );
		if ( false === $inserted ) {
			return new WP_Error( 'platform_snapshot_save_failed', __( 'The platform readiness snapshot could not be saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		SC_EI_Audit_Log::record(
			'platform_readiness_snapshot_created',
			'Authorized staff or the scheduled platform job stored an immutable readiness snapshot.',
			array(
				'snapshot_id'       => (int) $wpdb->insert_id,
				'readiness_score'   => absint( $readiness['score'] ),
				'required_failures' => count( $readiness['required_failures'] ),
				'content_hash'      => $data['content_hash'],
				'source'            => sanitize_key( $source ),
				'automatic_launch'  => false,
			),
			null,
			null,
			$actor_user_id ?: null
		);
		return array_merge( $data, array( 'id' => (int) $wpdb->insert_id ) );
	}

	public static function set_launch_state( string $state, string $note, int $actor_user_id ) {
		$state = SC_EI_Platform_Schema::sanitize_launch_state( $state );
		$note = sanitize_textarea_field( $note );
		if ( '' === trim( $note ) ) {
			return new WP_Error( 'platform_launch_note_required', __( 'Record why the platform launch state is changing.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$readiness = self::readiness();
		if ( 'production' === $state && empty( $readiness['ready_for_production'] ) ) {
			return new WP_Error( 'platform_not_ready_for_production', __( 'Required readiness checks must pass before the platform can be marked Production.', 'sustainable-catalyst-engagement-intake' ), $readiness );
		}
		$settings = self::settings();
		$from = SC_EI_Platform_Schema::sanitize_launch_state( (string) $settings['platform_launch_state'] );
		$settings['platform_launch_state'] = $state;
		update_option( 'sc_ei_settings', $settings, false );
		update_option(
			'sc_ei_platform_launch_record',
			array(
				'from'       => $from,
				'to'         => $state,
				'note'       => $note,
				'changed_by' => $actor_user_id,
				'changed_at' => current_time( 'mysql', true ),
				'readiness_hash' => hash( 'sha256', wp_json_encode( $readiness, JSON_UNESCAPED_SLASHES ) ),
			),
			false
		);
		SC_EI_Audit_Log::record(
			'platform_launch_state_changed',
			'Authorized staff changed the human-controlled unified platform launch state.',
			array(
				'from'             => $from,
				'to'               => $state,
				'note'             => $note,
				'readiness_score'  => absint( $readiness['score'] ),
				'automatic_launch' => false,
			),
			null,
			null,
			$actor_user_id
		);
		return self::readiness();
	}

	public static function export(): array {
		$summary = self::platform_summary();
		return array(
			'schema'       => 'sc-unified-contact-engagement-platform/1.0',
			'generated_at' => current_time( 'mysql', true ),
			'platform'     => array(
				'name'            => (string) self::settings()['platform_display_name'],
				'plugin_version'  => SC_EI_VERSION,
				'database_version'=> (string) get_option( 'sc_ei_db_version', '' ),
				'schema_versions' => self::schema_versions(),
				'launch_record'   => get_option( 'sc_ei_platform_launch_record', array() ),
			),
			'readiness'    => $summary['readiness'],
			'metrics'      => $summary['metrics'],
			'migrations'   => $summary['migrations'],
			'snapshots'    => $summary['snapshots'],
			'targets'      => $summary['targets'],
			'boundaries'   => self::boundaries(),
		);
	}

	public static function snapshots( int $limit = 100 ): array {
		global $wpdb;
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, public_id, snapshot_version, launch_state, readiness_score, required_failures, warning_count, content_hash, source, generated_by, generated_at FROM ' . SC_EI_Database::table( 'platform_snapshots' ) . ' ORDER BY generated_at DESC, id DESC LIMIT %d',
				max( 1, min( 1000, $limit ) )
			),
			ARRAY_A
		);
	}

	public static function migrations( int $limit = 100 ): array {
		global $wpdb;
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id, public_id, migration_key, from_version, to_version, status, schema_hash, started_at, completed_at, error_code, created_at FROM ' . SC_EI_Database::table( 'platform_migrations' ) . ' ORDER BY created_at DESC, id DESC LIMIT %d',
				max( 1, min( 1000, $limit ) )
			),
			ARRAY_A
		);
	}

	public static function purge_snapshots(): int {
		global $wpdb;
		$days = max( 30, absint( self::settings()['platform_snapshot_retention_days'] ?? 365 ) );
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
		$result = $wpdb->query(
			$wpdb->prepare( 'DELETE FROM ' . SC_EI_Database::table( 'platform_snapshots' ) . ' WHERE generated_at < %s', $cutoff )
		);
		return false === $result ? 0 : (int) $result;
	}

	public static function schema_versions(): array {
		return array(
			'review'        => SC_EI_REVIEW_SCHEMA_VERSION,
			'communication' => SC_EI_COMMUNICATION_SCHEMA_VERSION,
			'privacy'       => SC_EI_PRIVACY_SCHEMA_VERSION,
			'fit'           => SC_EI_FIT_SCHEMA_VERSION,
			'portal'        => SC_EI_PORTAL_SCHEMA_VERSION,
			'workflow'      => SC_EI_WORKFLOW_SCHEMA_VERSION,
			'calendar'      => SC_EI_CALENDAR_SCHEMA_VERSION,
			'graph'         => SC_EI_GRAPH_SCHEMA_VERSION,
			'engagement'    => SC_EI_ENGAGEMENT_SCHEMA_VERSION,
			'analytics'     => SC_EI_ANALYTICS_SCHEMA_VERSION,
			'hardening'     => SC_EI_HARDENING_SCHEMA_VERSION,
			'workflow_core' => SC_EI_WORKFLOW_CORE_SCHEMA_VERSION,
			'platform'      => SC_EI_PLATFORM_SCHEMA_VERSION,
			'lifecycle'     => SC_EI_LIFECYCLE_SCHEMA_VERSION,
			'support'       => SC_EI_SUPPORT_SCHEMA_VERSION,
		);
	}

	public static function boundaries(): array {
		return array(
			'human_launch_state'             => true,
			'automatic_launch'               => false,
			'automatic_acceptance'           => false,
			'automatic_fit_decision'         => false,
			'automatic_proposal'             => false,
			'automatic_contract'             => false,
			'automatic_engagement_activation'=> false,
			'automatic_project_provisioning' => false,
			'automatic_payment'              => false,
			'public_calendar_booking'         => false,
			'automatic_meeting_reminders'     => false,
			'unverified_external_commands'   => false,
			'arbitrary_webhook_delivery'     => false,
		);
	}

	public static function discover_entry_pages(): array {
		global $wpdb;
		$map = array(
			'unified'     => 'sc_contact_engagement_platform',
			'contact_hub' => 'sc_contact_hub',
			'engagement'  => 'sc_engagement_inquiry',
			'portal'      => 'sc_sender_portal',
		);
		$result = array();
		foreach ( $map as $key => $shortcode ) {
			$like = '%[' . $wpdb->esc_like( $shortcode ) . '%';
			$rows = (array) $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID, post_title, post_status FROM {$wpdb->posts} WHERE post_type IN ('page','post') AND post_status IN ('publish','private') AND post_content LIKE %s ORDER BY post_status = 'publish' DESC, ID DESC LIMIT 10",
					$like
				),
				ARRAY_A
			);
			$result[ $key ] = array_map(
				static fn( array $row ): array => array(
					'id'     => absint( $row['ID'] ),
					'title'  => (string) $row['post_title'],
					'status' => (string) $row['post_status'],
					'url'    => get_permalink( absint( $row['ID'] ) ) ?: '',
				),
				$rows
			);
		}
		return $result;
	}


	public static function schedule_all(): void {
		SC_EI_Retention::schedule();
		SC_EI_Notification_Service::schedule();
		SC_EI_Portal_Repository::schedule();
		SC_EI_Workflow_Repository::schedule();
		SC_EI_Calendar_Repository::schedule();
		SC_EI_Graph_Repository::schedule();
		SC_EI_Analytics_Repository::schedule();
		SC_EI_Hardening_Repository::schedule();
		SC_EI_Workflow_Core_Repository::schedule();
		SC_EI_Lifecycle_Repository::schedule();
		SC_EI_Support_Repository::schedule();
		self::schedule();
	}

	public static function repair( string $repair_key, int $actor_user_id ) {
		$repair_key = sanitize_key( $repair_key );
		switch ( $repair_key ) {
			case 'refresh_version':
				update_option( 'sc_ei_version', SC_EI_VERSION, false );
				update_option( 'sc_ei_platform_schema_version', SC_EI_PLATFORM_SCHEMA_VERSION, false );
				$result = true;
				break;
			case 'repair_database':
				SC_EI_Database::maybe_upgrade();
				$result = ! in_array( false, SC_EI_Database::tables_exist(), true ) && ! in_array( false, SC_EI_Database::platform_columns_exist(), true ) && ! in_array( false, SC_EI_Database::inquiry_columns_exist(), true ) && ! in_array( false, SC_EI_Database::lifecycle_columns_exist(), true ) && ! in_array( false, SC_EI_Database::support_columns_exist(), true ) && ! in_array( false, SC_EI_Database::calendar_columns_exist(), true );
				break;
			case 'verify_migration':
				$result = self::run_migrations( (string) get_option( 'sc_ei_version_previous', '' ) );
				break;
			case 'verify_patch_migration':
				$result = self::record_patch_migration( (string) get_option( 'sc_ei_version_previous', '' ) );
				break;
			case 'verify_launch_migration':
				$result = self::record_launch_migration( (string) get_option( 'sc_ei_version_previous', '' ) );
				break;
			case 'verify_lifecycle_migration':
				SC_EI_Lifecycle_Repository::backfill_defaults();
				$result = SC_EI_Lifecycle_Repository::record_migration( (string) get_option( 'sc_ei_lifecycle_schema_version_previous', '' ) );
				break;
			case 'verify_support_migration':
				$result = SC_EI_Support_Repository::record_migration( (string) get_option( 'sc_ei_version_previous', '' ) );
				break;
			case 'verify_support_reliability_patch':
				$result = SC_EI_Support_Repository::record_patch_migration( (string) get_option( 'sc_ei_version_previous', '' ) );
				break;
			case 'verify_calendar_migration':
				$result = SC_EI_Calendar_Repository::record_migration( (string) get_option( 'sc_ei_calendar_schema_version_previous', '' ) );
				break;
			case 'repair_storage':
				$result = SC_EI_Storage::repair();
				if ( empty( $result['ok'] ) ) {
					$result = new WP_Error( 'platform_storage_repair_failed', __( 'Protected storage repair did not pass its runtime probe.', 'sustainable-catalyst-engagement-intake' ), $result );
				}
				break;
			case 'repair_crons':
				self::schedule_all();
				$result = self::cron_evidence();
				if ( empty( $result['passed'] ) ) {
					$result = new WP_Error( 'platform_cron_repair_incomplete', __( 'One or more required scheduled jobs or callbacks remain unavailable.', 'sustainable-catalyst-engagement-intake' ), $result );
				}
				break;
			default:
				return new WP_Error( 'platform_repair_not_supported', __( 'This readiness item requires configuration or manual review rather than an automatic repair.', 'sustainable-catalyst-engagement-intake' ) );
		}
		SC_EI_Audit_Log::record( 'platform_readiness_repair_run', 'Authorized staff ran a bounded readiness repair action.', array( 'repair_key' => $repair_key, 'success' => ! is_wp_error( $result ) ), null, null, $actor_user_id );
		return $result;
	}

	public static function cron_evidence(): array {
		$jobs = array(
			'portal_cleanup'       => array( 'hook' => 'sc_ei_portal_cleanup', 'callback' => array( 'SC_EI_Portal_Repository', 'handle_cleanup' ) ),
			'workflow_cleanup'     => array( 'hook' => 'sc_ei_workflow_cleanup', 'callback' => array( 'SC_EI_Workflow_Repository', 'handle_cleanup' ) ),
			'calendar_reminders'    => array( 'hook' => SC_EI_Calendar_Repository::REMINDER_HOOK, 'callback' => array( 'SC_EI_Calendar_Repository', 'process_due_reminders' ) ),
			'retention'            => array( 'hook' => SC_EI_Retention::CRON_HOOK, 'callback' => array( 'SC_EI_Retention', 'cleanup' ) ),
			'notifications'        => array( 'hook' => SC_EI_Notification_Service::CRON_HOOK, 'callback' => array( 'SC_EI_Notification_Service', 'run_reminders' ) ),
			'graph_catchup'        => array( 'hook' => 'sc_ei_graph_catchup', 'callback' => array( 'SC_EI_Graph_Repository', 'handle_catchup' ) ),
			'analytics'            => array( 'hook' => SC_EI_Analytics_Repository::DAILY_HOOK, 'callback' => array( 'SC_EI_Analytics_Repository', 'daily_snapshot' ) ),
			'hardening'            => array( 'hook' => SC_EI_Hardening_Repository::watchdog_hook(), 'callback' => array( 'SC_EI_Hardening_Repository', 'watchdog' ) ),
			'workflow_core_sync'   => array( 'hook' => SC_EI_Workflow_Core_Repository::SYNC_HOOK, 'callback' => array( 'SC_EI_Workflow_Core_Repository', 'scheduled_sync' ) ),
			'workflow_core_outbox' => array( 'hook' => SC_EI_Workflow_Core_Repository::OUTBOX_HOOK, 'callback' => array( 'SC_EI_Workflow_Core_Repository', 'scheduled_outbox' ) ),
			'platform_snapshot'    => array( 'hook' => self::SNAPSHOT_HOOK, 'callback' => array( __CLASS__, 'daily_snapshot' ) ),
			'lifecycle_reminders'   => array( 'hook' => SC_EI_Lifecycle_Repository::REMINDER_HOOK, 'callback' => array( 'SC_EI_Lifecycle_Repository', 'process_due_tasks' ) ),
			'support_signal_digest' => array( 'hook' => SC_EI_Support_Repository::SIGNAL_DIGEST_HOOK, 'callback' => array( 'SC_EI_Support_Repository', 'scheduled_signal_digest' ) ),
		);
		$missing = array();
		$evidence = array();
		foreach ( $jobs as $key => $job ) {
			$timestamp = wp_next_scheduled( $job['hook'] );
			$callback_registered = false !== has_action( $job['hook'], $job['callback'] );
			$evidence[ $key ] = array(
				'hook'                => $job['hook'],
				'next_run_utc'        => $timestamp ? gmdate( 'Y-m-d H:i:s', (int) $timestamp ) : '',
				'scheduled'           => (bool) $timestamp,
				'callback_registered' => $callback_registered,
			);
			if ( ! $timestamp || ! $callback_registered ) {
				$missing[] = $key . ( ! $timestamp ? ':schedule' : '' ) . ( ! $callback_registered ? ':callback' : '' );
			}
		}
		return array(
			'passed' => empty( $missing ),
			'detail' => empty( $missing ) ? sprintf( '%d/%d scheduled with callbacks', count( $jobs ), count( $jobs ) ) : 'Missing ' . implode( ', ', $missing ),
			'jobs'   => $evidence,
		);
	}

	public static function page_contract_evidence(): array {
		$settings = self::settings();
		$discovered = self::discover_entry_pages();
		$public = self::page_evidence_for(
			array_filter( array( (string) ( $settings['platform_contact_page_url'] ?? '' ), (string) ( $settings['platform_engagement_page_url'] ?? '' ) ) ),
			array( 'sc_contact_engagement_platform', 'sc_contact_hub', 'sc_engagement_inquiry' ),
			array_merge( $discovered['unified'] ?? array(), $discovered['contact_hub'] ?? array(), $discovered['engagement'] ?? array() ),
			true
		);
		$portal = self::page_evidence_for(
			array_filter( array( self::effective_url( 'platform_portal_page_url', 'portal_page_url' ) ) ),
			array( 'sc_sender_portal' ),
			$discovered['portal'] ?? array(),
			true
		);
		$privacy = self::page_evidence_for(
			array_filter( array( (string) ( $settings['platform_privacy_page_url'] ?? '' ) ) ),
			array(),
			array(),
			false
		);
		return array(
			'public_entry' => $public,
			'portal'       => $portal,
			'privacy'      => $privacy,
			'summary'      => sprintf( 'public entry %s; portal %s; privacy %s', $public['passed'] ? 'pass' : 'fail', $portal['passed'] ? 'pass' : 'fail', $privacy['passed'] ? 'pass' : 'fail' ),
		);
	}

	public static function accessibility_evidence(): array {
		if ( ! shortcode_exists( 'sc_contact_engagement_platform' ) || ! shortcode_exists( 'sc_sender_portal' ) ) {
			return array( 'passed' => false, 'detail' => 'Required public shortcodes are not registered.' );
		}
		$html = SC_EI_Platform_Public::shortcode( array( 'show_form' => 'no', 'show_portal' => 'no', 'show_privacy' => 'no' ) );
		$required = array( 'aria-labelledby=', 'aria-label=', 'role="note"', '<h2' );
		$missing = array_values( array_filter( $required, static fn( string $needle ): bool => false === strpos( $html, $needle ) ) );
		return array(
			'passed' => empty( $missing ),
			'detail' => empty( $missing ) ? 'Rendered platform shell includes heading relationships, route label, and workflow note.' : 'Missing rendered markers: ' . implode( ', ', $missing ),
		);
	}

	private static function page_evidence_for( array $configured_urls, array $shortcodes, array $discovered, bool $shortcode_required ): array {
		$candidates = array();
		foreach ( $configured_urls as $url ) {
			$url = esc_url_raw( (string) $url );
			if ( $url ) {
				$candidates[] = array( 'url' => $url, 'source' => 'configured' );
			}
		}
		foreach ( $discovered as $page ) {
			if ( ! empty( $page['url'] ) ) {
				$candidates[] = array( 'url' => (string) $page['url'], 'source' => 'discovered', 'id' => absint( $page['id'] ?? 0 ) );
			}
		}
		$seen = array();
		foreach ( $candidates as $candidate ) {
			$url = $candidate['url'];
			if ( isset( $seen[ $url ] ) ) {
				continue;
			}
			$seen[ $url ] = true;
			$post_id = ! empty( $candidate['id'] ) ? absint( $candidate['id'] ) : url_to_postid( $url );
			$post = $post_id ? get_post( $post_id ) : null;
			if ( ! $post || 'publish' !== (string) $post->post_status || ! in_array( (string) $post->post_type, array( 'page', 'post' ), true ) ) {
				continue;
			}
			$has_contract = ! $shortcode_required;
			foreach ( $shortcodes as $shortcode ) {
				if ( has_shortcode( (string) $post->post_content, $shortcode ) ) {
					$has_contract = true;
					break;
				}
			}
			if ( $has_contract ) {
				return array( 'passed' => true, 'detail' => sprintf( '%s (#%d, %s)', get_the_title( $post_id ), $post_id, $candidate['source'] ), 'url' => get_permalink( $post_id ) ?: $url, 'post_id' => $post_id );
			}
		}
		return array( 'passed' => false, 'detail' => $shortcode_required ? 'No published local page with the required shortcode was found.' : 'No published local page was found for the configured URL.', 'url' => '', 'post_id' => 0 );
	}

	private static function check( string $key, string $group, string $label, bool $passed, bool $required, string $detail, string $repair = '' ): array {
		return array(
			'key'      => sanitize_key( $key ),
			'group'    => sanitize_key( $group ),
			'label'    => sanitize_text_field( $label ),
			'status'   => $passed ? 'pass' : ( $required ? 'fail' : 'warn' ),
			'required' => $required,
			'detail'   => mb_substr( sanitize_text_field( $detail ), 0, 1000 ),
			'repair'   => sanitize_key( $repair ),
			'repair_label' => self::repair_label( $repair ),
		);
	}


	private static function repair_label( string $repair ): string {
		$labels = array(
			'refresh_version'       => __( 'Refresh version state', 'sustainable-catalyst-engagement-intake' ),
			'repair_database'       => __( 'Repair database contract', 'sustainable-catalyst-engagement-intake' ),
			'verify_migration'      => __( 'Verify base migration', 'sustainable-catalyst-engagement-intake' ),
			'verify_patch_migration'=> __( 'Record v1.0.2 upgrade', 'sustainable-catalyst-engagement-intake' ),
			'verify_launch_migration'=> __( 'Record v1.0.3 upgrade', 'sustainable-catalyst-engagement-intake' ),
			'repair_storage'        => __( 'Repair and probe storage', 'sustainable-catalyst-engagement-intake' ),
			'repair_crons'          => __( 'Repair scheduled jobs', 'sustainable-catalyst-engagement-intake' ),
			'verify_lifecycle_migration' => __( 'Verify v1.1.0 lifecycle migration', 'sustainable-catalyst-engagement-intake' ),
			'verify_support_migration' => __( 'Verify v1.2.0 support migration', 'sustainable-catalyst-engagement-intake' ),
			'verify_support_reliability_patch' => __( 'Verify v1.2.1 support reliability patch', 'sustainable-catalyst-engagement-intake' ),
			'verify_calendar_migration' => __( 'Verify v1.3.0 calendar migration', 'sustainable-catalyst-engagement-intake' ),
			'review_calendar'       => __( 'Open Calendar Coordination', 'sustainable-catalyst-engagement-intake' ),
			'review_support'        => __( 'Open Support Cases', 'sustainable-catalyst-engagement-intake' ),
			'review_lifecycle'      => __( 'Open Advisory Lifecycle', 'sustainable-catalyst-engagement-intake' ),
			'configure_pages'       => __( 'Configure public pages', 'sustainable-catalyst-engagement-intake' ),
			'configure_settings'    => __( 'Configure platform settings', 'sustainable-catalyst-engagement-intake' ),
			'review_https'          => __( 'Review HTTPS configuration', 'sustainable-catalyst-engagement-intake' ),
			'review_reliability'    => __( 'Open Reliability', 'sustainable-catalyst-engagement-intake' ),
			'review_workflow_core'  => __( 'Open Workflow Core', 'sustainable-catalyst-engagement-intake' ),
			'review_accessibility'  => __( 'Review rendered interface', 'sustainable-catalyst-engagement-intake' ),
			'run_live_validation'   => __( 'Run live validation', 'sustainable-catalyst-engagement-intake' ),
			'attest_backups'        => __( 'Record backup evidence', 'sustainable-catalyst-engagement-intake' ),
			'confirm_external_mail' => __( 'Confirm inbox delivery', 'sustainable-catalyst-engagement-intake' ),
			'record_pilot_evidence' => __( 'Record pilot evidence', 'sustainable-catalyst-engagement-intake' ),
			'review_operations'     => __( 'Review launch operations', 'sustainable-catalyst-engagement-intake' ),
			'review_routed_entries' => __( 'Review routed entry URLs', 'sustainable-catalyst-engagement-intake' ),
		);
		return $labels[ $repair ] ?? '';
	}

	private static function effective_url( string $platform_key, string $legacy_key ): string {
		$settings = self::settings();
		$url = esc_url_raw( (string) ( $settings[ $platform_key ] ?? '' ) );
		if ( '' === $url && $legacy_key ) {
			$url = esc_url_raw( (string) ( $settings[ $legacy_key ] ?? '' ) );
		}
		return $url;
	}

	private static function migration_completed( string $migration_key ): bool {
		global $wpdb;
		$status = $wpdb->get_var(
			$wpdb->prepare( 'SELECT status FROM ' . SC_EI_Database::table( 'platform_migrations' ) . ' WHERE migration_key = %s LIMIT 1', $migration_key )
		);
		return 'completed' === $status;
	}

	private static function find_migration( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . SC_EI_Database::table( 'platform_migrations' ) . ' WHERE id = %d', $id ),
			ARRAY_A
		);
		return $row ?: null;
	}

	private static function schema_hash(): string {
		return hash( 'sha256', wp_json_encode( self::schema_versions(), JSON_UNESCAPED_SLASHES ) );
	}

	private static function formats( array $data, array $integer_fields ): array {
		return array_map(
			static fn( string $key ): string => in_array( $key, $integer_fields, true ) ? '%d' : '%s',
			array_keys( $data )
		);
	}
}
