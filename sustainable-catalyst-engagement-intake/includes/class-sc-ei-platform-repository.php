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

	public static function maybe_upgrade(): void {
		$stored_version = (string) get_option( 'sc_ei_version', '' );
		$stored_schema = (string) get_option( 'sc_ei_platform_schema_version', '' );
		if ( version_compare( $stored_version, SC_EI_VERSION, '<' ) || SC_EI_PLATFORM_SCHEMA_VERSION !== $stored_schema ) {
			update_option( 'sc_ei_version_previous', $stored_version, false );
			update_option( 'sc_ei_version', SC_EI_VERSION, false );
			update_option( 'sc_ei_platform_schema_version', SC_EI_PLATFORM_SCHEMA_VERSION, false );
			self::run_migrations( $stored_version );
		}
		self::schedule();
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

	public static function readiness(): array {
		$settings = self::settings();
		$storage = SC_EI_Storage::storage_health();
		$tables = SC_EI_Database::tables_exist();
		$platform_columns = SC_EI_Database::platform_columns_exist();
		$hardening = SC_EI_Hardening_Repository::metrics();
		$core = SC_EI_Workflow_Core_Repository::metrics();
		$portal_url = self::effective_url( 'platform_portal_page_url', 'portal_page_url' );
		$contact_url = self::effective_url( 'platform_contact_page_url', '' );
		$engagement_url = self::effective_url( 'platform_engagement_page_url', '' );
		$privacy_url = self::effective_url( 'platform_privacy_page_url', '' );
		$entry_pages = self::discover_entry_pages();
		$required_crons = array(
			'portal_cleanup'      => wp_next_scheduled( 'sc_ei_portal_cleanup' ),
			'workflow_cleanup'    => wp_next_scheduled( 'sc_ei_workflow_cleanup' ),
			'retention'           => wp_next_scheduled( SC_EI_Retention::CRON_HOOK ),
			'notifications'       => wp_next_scheduled( SC_EI_Notification_Service::CRON_HOOK ),
			'graph_catchup'       => wp_next_scheduled( 'sc_ei_graph_catchup' ),
			'analytics'           => wp_next_scheduled( SC_EI_Analytics_Repository::DAILY_HOOK ),
			'hardening'           => wp_next_scheduled( SC_EI_Hardening_Repository::WATCHDOG_HOOK ),
			'workflow_core_sync'  => wp_next_scheduled( SC_EI_Workflow_Core_Repository::SYNC_HOOK ),
			'workflow_core_outbox'=> wp_next_scheduled( SC_EI_Workflow_Core_Repository::OUTBOX_HOOK ),
			'platform_snapshot'   => wp_next_scheduled( self::SNAPSHOT_HOOK ),
		);

		$checks = array();
		$checks[] = self::check( 'platform_version', 'platform', __( 'Stable platform version', 'sustainable-catalyst-engagement-intake' ), '1.0.0' === SC_EI_VERSION && '1.0.0' === (string) get_option( 'sc_ei_version', '' ), true, SC_EI_VERSION );
		$checks[] = self::check( 'database_version', 'data', __( 'Database version', 'sustainable-catalyst-engagement-intake' ), SC_EI_DB_VERSION === (string) get_option( 'sc_ei_db_version', '' ), true, (string) get_option( 'sc_ei_db_version', '' ) );
		$checks[] = self::check( 'database_tables', 'data', __( 'Required database tables', 'sustainable-catalyst-engagement-intake' ), ! in_array( false, $tables, true ), true, sprintf( '%d/%d', count( array_filter( $tables ) ), count( $tables ) ) );
		$checks[] = self::check( 'platform_columns', 'data', __( 'Platform governance schema', 'sustainable-catalyst-engagement-intake' ), ! in_array( false, $platform_columns, true ), true, sprintf( '%d/%d', count( array_filter( $platform_columns ) ), count( $platform_columns ) ) );
		$checks[] = self::check( 'migration_journal', 'data', __( 'v1.0 migration journal', 'sustainable-catalyst-engagement-intake' ), self::migration_completed(), true, self::MIGRATION_KEY );
		$storage_ok = ! empty( $storage['exists'] ) && ! empty( $storage['writable'] ) && ! empty( $storage['marker'] ) && ! empty( $storage['protection_files'] );
		$checks[] = self::check( 'protected_storage', 'security', __( 'Protected document storage', 'sustainable-catalyst-engagement-intake' ), $storage_ok, ! empty( $settings['platform_require_protected_storage'] ), $storage['path'] ?? '' );
		$https_ok = is_ssl() || SC_EI_Portal_Schema::secure_transport_available();
		$checks[] = self::check( 'https', 'security', __( 'HTTPS and secure portal transport', 'sustainable-catalyst-engagement-intake' ), $https_ok, ! empty( $settings['platform_require_https'] ), $https_ok ? 'available' : 'unavailable' );
		$checks[] = self::check( 'critical_health', 'operations', __( 'Open critical reliability events', 'sustainable-catalyst-engagement-intake' ), 0 === absint( $hardening['open_critical'] ?? 0 ), true, (string) absint( $hardening['open_critical'] ?? 0 ) );
		$checks[] = self::check( 'public_writes', 'operations', __( 'Public writes incident state', 'sustainable-catalyst-engagement-intake' ), ! SC_EI_Hardening_Repository::public_writes_paused(), true, SC_EI_Hardening_Repository::public_writes_paused() ? 'paused' : 'open' );
		$checks[] = self::check( 'cron_schedules', 'operations', __( 'Required scheduled jobs', 'sustainable-catalyst-engagement-intake' ), ! in_array( false, array_map( 'boolval', $required_crons ), true ), true, sprintf( '%d/%d', count( array_filter( $required_crons ) ), count( $required_crons ) ) );
		$entry_ready = (bool) $contact_url || (bool) $engagement_url || ! empty( $entry_pages['unified'] ) || ! empty( $entry_pages['contact_hub'] ) || ! empty( $entry_pages['engagement'] );
		$checks[] = self::check( 'public_entry', 'public_entry', __( 'Published public contact or engagement entry', 'sustainable-catalyst-engagement-intake' ), $entry_ready, ! empty( $settings['platform_require_public_entry'] ), $entry_ready ? 'configured' : 'not detected' );
		$checks[] = self::check( 'portal_url', 'portal', __( 'Secure sender portal URL', 'sustainable-catalyst-engagement-intake' ), '' !== $portal_url || ! empty( $entry_pages['portal'] ), ! empty( $settings['platform_require_portal_url'] ), $portal_url ?: ( $entry_pages['portal'][0]['url'] ?? 'not configured' ) );
		$checks[] = self::check( 'support_email', 'operations', __( 'Platform support email', 'sustainable-catalyst-engagement-intake' ), is_email( (string) $settings['platform_support_email'] ), true, (string) $settings['platform_support_email'] );
		$checks[] = self::check( 'workflow_core', 'integrations', __( 'Workflow Core consistency', 'sustainable-catalyst-engagement-intake' ), 0 === absint( $core['blocked_cases'] ?? 0 ), false, sprintf( '%d blocked, %d warning', absint( $core['blocked_cases'] ?? 0 ), absint( $core['warning_cases'] ?? 0 ) ) );
		$checks[] = self::check( 'adapter_registry', 'integrations', __( 'Registered internal adapters', 'sustainable-catalyst-engagement-intake' ), true, false, sprintf( '%d registered', count( array_filter( SC_EI_Workflow_Core_Service::registered_targets(), static fn( array $target ): bool => ! empty( $target['registered'] ) ) ) ) );
		$checks[] = self::check( 'privacy_url', 'security', __( 'Public privacy guidance URL', 'sustainable-catalyst-engagement-intake' ), '' !== $privacy_url, false, $privacy_url ?: 'not configured' );
		$checks[] = self::check( 'accessibility_controls', 'accessibility', __( 'Accessible interface controls', 'sustainable-catalyst-engagement-intake' ), true, true, 'skip links, live regions, focus, reduced motion, forced colors' );

		$required_failures = array_values( array_filter( $checks, static fn( array $check ): bool => ! empty( $check['required'] ) && 'fail' === $check['status'] ) );
		$warnings = array_values( array_filter( $checks, static fn( array $check ): bool => 'warn' === $check['status'] ) );
		$weighted = array_filter( $checks, static fn( array $check ): bool => 'info' !== $check['status'] );
		$earned = 0;
		foreach ( $weighted as $check ) {
			$earned += 'pass' === $check['status'] ? 1 : ( 'warn' === $check['status'] ? 0.5 : 0 );
		}
		$score = $weighted ? (int) round( 100 * $earned / count( $weighted ) ) : 0;

		return array(
			'schema'             => 'sc-unified-contact-engagement-platform-readiness/1.0',
			'generated_at'       => current_time( 'mysql', true ),
			'plugin_version'     => SC_EI_VERSION,
			'database_version'   => (string) get_option( 'sc_ei_db_version', '' ),
			'platform_schema'    => SC_EI_PLATFORM_SCHEMA_VERSION,
			'launch_state'       => SC_EI_Platform_Schema::sanitize_launch_state( (string) $settings['platform_launch_state'] ),
			'score'              => $score,
			'ready_for_production'=> empty( $required_failures ),
			'required_failures'  => $required_failures,
			'warnings'           => $warnings,
			'checks'             => $checks,
			'entry_pages'        => $entry_pages,
			'urls'               => array(
				'contact'    => $contact_url,
				'engagement' => $engagement_url,
				'portal'     => $portal_url,
				'privacy'    => $privacy_url,
			),
			'cron'               => array_map( static fn( $timestamp ) => $timestamp ? gmdate( 'Y-m-d H:i:s', (int) $timestamp ) : null, $required_crons ),
			'boundaries'         => self::boundaries(),
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
			),
			'migrations' => self::migrations( 20 ),
			'snapshots'  => self::snapshots( 20 ),
			'targets'    => SC_EI_Workflow_Core_Service::registered_targets(),
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
			'graph'         => SC_EI_GRAPH_SCHEMA_VERSION,
			'engagement'    => SC_EI_ENGAGEMENT_SCHEMA_VERSION,
			'analytics'     => SC_EI_ANALYTICS_SCHEMA_VERSION,
			'hardening'     => SC_EI_HARDENING_SCHEMA_VERSION,
			'workflow_core' => SC_EI_WORKFLOW_CORE_SCHEMA_VERSION,
			'platform'      => SC_EI_PLATFORM_SCHEMA_VERSION,
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

	private static function check( string $key, string $group, string $label, bool $passed, bool $required, string $detail ): array {
		return array(
			'key'      => sanitize_key( $key ),
			'group'    => sanitize_key( $group ),
			'label'    => sanitize_text_field( $label ),
			'status'   => $passed ? 'pass' : ( $required ? 'fail' : 'warn' ),
			'required' => $required,
			'detail'   => mb_substr( sanitize_text_field( $detail ), 0, 1000 ),
		);
	}

	private static function effective_url( string $platform_key, string $legacy_key ): string {
		$settings = self::settings();
		$url = esc_url_raw( (string) ( $settings[ $platform_key ] ?? '' ) );
		if ( '' === $url && $legacy_key ) {
			$url = esc_url_raw( (string) ( $settings[ $legacy_key ] ?? '' ) );
		}
		return $url;
	}

	private static function migration_completed(): bool {
		global $wpdb;
		$status = $wpdb->get_var(
			$wpdb->prepare( 'SELECT status FROM ' . SC_EI_Database::table( 'platform_migrations' ) . ' WHERE migration_key = %s LIMIT 1', self::MIGRATION_KEY )
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
