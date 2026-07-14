<?php
/**
 * Production reliability, security, rate-limit, and incident controls.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class SC_EI_Hardening_Repository {
	private const WATCHDOG_HOOK = 'sc_ei_hardening_watchdog';
	private const PRUNE_HOOK = 'sc_ei_hardening_prune';
	private const LAST_RUN_OPTION = 'sc_ei_last_hardening_watchdog';
	private const REQUEST_ID_HEADER = 'X-SC-EI-Request-ID';
	private static string $request_id = '';
	private static bool $fatal_registered = false;

	public static function register(): void {
		self::$request_id = self::valid_uuid( $_SERVER['HTTP_X_REQUEST_ID'] ?? '' ) ?: wp_generate_uuid4();
		add_action( self::WATCHDOG_HOOK, array( __CLASS__, 'watchdog' ) );
		add_action( self::PRUNE_HOOK, array( __CLASS__, 'prune' ) );
		add_action( 'send_headers', array( __CLASS__, 'send_security_headers' ), 100 );
		add_filter( 'wp_headers', array( __CLASS__, 'filter_headers' ), 100 );
		add_filter( 'admin_body_class', array( __CLASS__, 'admin_body_class' ) );
		add_action( 'admin_notices', array( __CLASS__, 'admin_skip_link' ), 1 );
		add_action( 'admin_footer', array( __CLASS__, 'accessibility_live_region' ), 1 );
		add_action( 'wp_footer', array( __CLASS__, 'accessibility_live_region' ), 1 );
		if ( ! self::$fatal_registered ) {
			register_shutdown_function( array( __CLASS__, 'capture_fatal' ) );
			self::$fatal_registered = true;
		}
	}

	public static function settings(): array {
		return wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Hardening_Schema::default_settings() );
	}

	public static function request_id(): string { return self::$request_id ?: wp_generate_uuid4(); }

	/**
	 * Return the scheduled hook used by the production-readiness watchdog.
	 *
	 * The underlying constant remains private so other components do not depend
	 * on the repository's internal storage details.
	 */
	public static function watchdog_hook(): string {
		return self::WATCHDOG_HOOK;
	}

	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::WATCHDOG_HOOK ) ) {
			wp_schedule_event( time() + 5 * MINUTE_IN_SECONDS, 'hourly', self::WATCHDOG_HOOK );
		}
		if ( ! wp_next_scheduled( self::PRUNE_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::PRUNE_HOOK );
		}
	}

	public static function unschedule(): void {
		wp_clear_scheduled_hook( self::WATCHDOG_HOOK );
		wp_clear_scheduled_hook( self::PRUNE_HOOK );
	}

	public static function public_writes_paused(): bool {
		return ! empty( self::settings()['hardening_public_writes_paused'] );
	}

	public static function guard_public_write( string $scope, int $limit, int $window_seconds, array $identifiers = array() ) {
		if ( self::public_writes_paused() ) {
			self::record_event( 'security', 'public_write_paused', 'warning', 'Public mutation was rejected while incident pause mode was active.', array( 'scope' => $scope ) );
			return new WP_Error( 'service_temporarily_paused', __( 'This secure submission service is temporarily paused for maintenance. Please try again later.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$identifiers[] = self::client_ip_hash();
		$identifiers[] = self::user_agent_hash();
		return self::consume_rate_limit( $scope, $identifiers, $limit, $window_seconds );
	}

	public static function consume_rate_limit( string $scope, array $identifiers, int $limit, int $window_seconds ) {
		global $wpdb;
		$scope = sanitize_key( $scope );
		$limit = max( 1, min( 10000, $limit ) );
		$window_seconds = max( 60, min( DAY_IN_SECONDS, $window_seconds ) );
		$identity = implode( '|', array_filter( array_map( 'strval', $identifiers ) ) );
		$bucket_hash = hash_hmac( 'sha256', $scope . '|' . $identity, wp_salt( 'secure_auth' ) );
		$window_start_ts = (int) floor( time() / $window_seconds ) * $window_seconds;
		$window_start = gmdate( 'Y-m-d H:i:s', $window_start_ts );
		$now = current_time( 'mysql', true );
		$table = SC_EI_Database::table( 'rate_limits' );
		$sql = $wpdb->prepare(
			"INSERT INTO {$table} (bucket_hash, scope, window_start, window_seconds, hits, blocked_until, created_at, updated_at)
			 VALUES (%s, %s, %s, %d, 1, NULL, %s, %s)
			 ON DUPLICATE KEY UPDATE hits = hits + 1, updated_at = VALUES(updated_at)",
			$bucket_hash, $scope, $window_start, $window_seconds, $now, $now
		);
		$result = $wpdb->query( $sql );
		if ( false === $result ) {
			self::record_event( 'database', 'rate_limit_write_failed', 'warning', 'Durable rate-limit counter could not be updated.', array( 'scope' => $scope ) );
			return true; // fail open for availability; existing workflow validation still applies.
		}
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT hits, blocked_until FROM {$table} WHERE scope = %s AND bucket_hash = %s AND window_start = %s", $scope, $bucket_hash, $window_start ), ARRAY_A );
		$blocked = ! empty( $row['blocked_until'] ) && strtotime( $row['blocked_until'] . ' UTC' ) > time();
		if ( $blocked || absint( $row['hits'] ?? 0 ) > $limit ) {
			$blocked_until = gmdate( 'Y-m-d H:i:s', $window_start_ts + $window_seconds );
			$wpdb->update( $table, array( 'blocked_until' => $blocked_until, 'updated_at' => $now ), array( 'scope' => $scope, 'bucket_hash' => $bucket_hash, 'window_start' => $window_start ), array( '%s', '%s' ), array( '%s', '%s', '%s' ) );
			self::record_event( 'security', 'rate_limit_triggered', 'warning', 'A durable public request rate limit was triggered.', array( 'scope' => $scope, 'limit' => $limit, 'window_seconds' => $window_seconds ) );
			return new WP_Error( 'rate_limited', __( 'Too many requests were received in a short period. Please try again later.', 'sustainable-catalyst-engagement-intake' ), array( 'retry_after' => max( 1, $window_start_ts + $window_seconds - time() ) ) );
		}
		return true;
	}

	public static function record_event( string $component, string $event_type, string $severity, string $message, array $context = array() ): void {
		global $wpdb;
		$component = SC_EI_Hardening_Schema::sanitize_component( $component );
		$event_type = sanitize_key( $event_type );
		$severity = SC_EI_Hardening_Schema::sanitize_severity( $severity );
		$message = self::redact( $message );
		$context = self::safe_context( $context );
		$fingerprint_context = $context;
		unset( $fingerprint_context['request_id'] );
		$fingerprint = hash( 'sha256', $component . '|' . $event_type . '|' . wp_json_encode( $fingerprint_context ) );
		$now = current_time( 'mysql', true );
		$table = SC_EI_Database::table( 'health_events' );
		$sql = $wpdb->prepare(
			"INSERT INTO {$table} (public_id, fingerprint, component, event_type, severity, message, context_json, occurrences, first_seen_at, last_seen_at, resolved_at, resolved_by, resolution_note)
			 VALUES (%s, %s, %s, %s, %s, %s, %s, 1, %s, %s, NULL, NULL, '')
			 ON DUPLICATE KEY UPDATE severity = VALUES(severity), message = VALUES(message), context_json = VALUES(context_json), occurrences = occurrences + 1, last_seen_at = VALUES(last_seen_at), resolved_at = NULL, resolved_by = NULL, resolution_note = ''",
			wp_generate_uuid4(), $fingerprint, $component, $event_type, $severity, $message, wp_json_encode( $context ), $now, $now
		);
		$wpdb->query( $sql );
	}

	public static function resolve_event( int $event_id, string $note, int $actor_user_id ) {
		global $wpdb;
		$note = self::redact( $note );
		if ( '' === trim( $note ) ) return new WP_Error( 'hardening_resolution_note_required', __( 'Record how the issue was reviewed or resolved.', 'sustainable-catalyst-engagement-intake' ) );
		$updated = $wpdb->update( SC_EI_Database::table( 'health_events' ), array( 'resolved_at' => current_time( 'mysql', true ), 'resolved_by' => $actor_user_id, 'resolution_note' => $note ), array( 'id' => $event_id ), array( '%s','%d','%s' ), array( '%d' ) );
		return 1 === $updated ? true : new WP_Error( 'hardening_event_resolve_failed', __( 'The reliability event could not be resolved.', 'sustainable-catalyst-engagement-intake' ) );
	}

	public static function events( array $args = array() ): array {
		global $wpdb;
		$args = wp_parse_args( $args, array( 'severity'=>'', 'component'=>'', 'open_only'=>true, 'limit'=>500 ) );
		$table = SC_EI_Database::table( 'health_events' );
		$where=array('1=1'); $params=array();
		if ( isset( SC_EI_Hardening_Schema::severities()[ sanitize_key($args['severity']) ] ) ) { $where[]='severity=%s'; $params[]=sanitize_key($args['severity']); }
		if ( isset( SC_EI_Hardening_Schema::components()[ sanitize_key($args['component']) ] ) ) { $where[]='component=%s'; $params[]=sanitize_key($args['component']); }
		if ( ! empty($args['open_only']) ) $where[]='resolved_at IS NULL';
		$sql="SELECT e.*, u.display_name AS resolved_by_name FROM {$table} e LEFT JOIN {$wpdb->users} u ON u.ID=e.resolved_by WHERE ".implode(' AND ',$where)." ORDER BY FIELD(severity,'critical','warning','info'), last_seen_at DESC LIMIT %d";
		$params[]=max(1,min(2000,absint($args['limit'])));
		return (array)$wpdb->get_results($wpdb->prepare($sql,$params),ARRAY_A);
	}

	public static function metrics(): array {
		global $wpdb;
		$table=SC_EI_Database::table('health_events');
		$rate=SC_EI_Database::table('rate_limits');
		return array(
			'open_critical'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE resolved_at IS NULL AND severity='critical'"),
			'open_warning'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE resolved_at IS NULL AND severity='warning'"),
			'open_info'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE resolved_at IS NULL AND severity='info'"),
			'resolved'=>(int)$wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE resolved_at IS NOT NULL"),
			'blocked_buckets'=>(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$rate} WHERE blocked_until > %s", current_time('mysql',true))),
		);
	}

	public static function watchdog( bool $manual = false ): array {
		$settings=self::settings();
		if ( empty($settings['hardening_watchdog_enabled']) && ! $manual ) return array('skipped'=>true);
		$lock=self::acquire_lock('watchdog',10*MINUTE_IN_SECONDS);
		if ( is_wp_error($lock) ) return array('locked'=>true);
		$checks=array();
		try {
			$checks['database_tables']=!in_array(false,SC_EI_Database::tables_exist(),true);
			$checks['hardening_columns']=!in_array(false,SC_EI_Database::hardening_columns_exist(),true);
			$checks['workflow_core_columns']=!in_array(false,SC_EI_Database::workflow_core_columns_exist(),true);
			$checks['platform_columns']=!in_array(false,SC_EI_Database::platform_columns_exist(),true);
			$checks['inquiry_columns']=!in_array(false,SC_EI_Database::inquiry_columns_exist(),true);
			$checks['lifecycle_columns']=!in_array(false,SC_EI_Database::lifecycle_columns_exist(),true);
			$storage=SC_EI_Storage::storage_health();
			$checks['storage_ready']=!empty($storage['exists']) && !empty($storage['writable']) && !empty($storage['marker']) && !empty($storage['protection_files']);
			$checks['portal_cleanup']=(bool)wp_next_scheduled('sc_ei_portal_cleanup');
			$checks['workflow_cleanup']=(bool)wp_next_scheduled('sc_ei_workflow_cleanup');
			$checks['retention']=(bool)wp_next_scheduled(SC_EI_Retention::CRON_HOOK);
			$checks['notification']=(bool)wp_next_scheduled(SC_EI_Notification_Service::CRON_HOOK);
			$checks['graph_catchup']=(bool)wp_next_scheduled('sc_ei_graph_catchup');
			$checks['analytics_snapshot']=(bool)wp_next_scheduled('sc_ei_analytics_daily_snapshot');
			$checks['hardening_watchdog']=(bool)wp_next_scheduled(self::WATCHDOG_HOOK);
			$checks['hardening_prune']=(bool)wp_next_scheduled(self::PRUNE_HOOK);
			$checks['workflow_core_sync']=(bool)wp_next_scheduled(SC_EI_Workflow_Core_Repository::SYNC_HOOK);
			$checks['workflow_core_outbox']=(bool)wp_next_scheduled(SC_EI_Workflow_Core_Repository::OUTBOX_HOOK);
			$checks['platform_snapshot']=(bool)wp_next_scheduled(SC_EI_Platform_Repository::SNAPSHOT_HOOK);
			$checks['secure_transport']=SC_EI_Portal_Schema::secure_transport_available();
			$checks['crypto']=SC_EI_Graph_Crypto::available();
			foreach($checks as $key=>$passed) if(!$passed) self::record_event(in_array($key,array('database_tables','hardening_columns','workflow_core_columns','platform_columns','inquiry_columns','lifecycle_columns'),true)?'database':(str_contains($key,'storage')?'storage':(str_contains($key,'transport')||str_contains($key,'crypto')?'security':'cron')), 'watchdog_'.$key, in_array($key,array('database_tables','hardening_columns','workflow_core_columns','platform_columns','inquiry_columns','lifecycle_columns','storage_ready'),true)?'critical':'warning', 'Reliability watchdog detected a failed production readiness check.', array('check'=>$key));
			$summary=array('checked_at'=>current_time('mysql',true),'request_id'=>self::request_id(),'manual'=>$manual,'checks'=>$checks,'passed'=>count(array_filter($checks)),'total'=>count($checks));
			update_option(self::LAST_RUN_OPTION,$summary,false);
			return $summary;
		} catch(Throwable $e) {
			self::record_event('plugin','watchdog_exception','critical','Reliability watchdog encountered an exception.',array('exception'=>get_class($e)));
			return array('error'=>'watchdog_exception');
		} finally { self::release_lock('watchdog',$lock); }
	}

	public static function last_watchdog(): array { return wp_parse_args(get_option(self::LAST_RUN_OPTION,array()),array('checked_at'=>'','request_id'=>'','manual'=>false,'checks'=>array(),'passed'=>0,'total'=>0)); }

	public static function prune(): array {
		global $wpdb; $settings=self::settings(); $now=current_time('mysql',true);
		$open_cut=gmdate('Y-m-d H:i:s',time()-max(7,absint($settings['hardening_event_retention_days']))*DAY_IN_SECONDS);
		$resolved_cut=gmdate('Y-m-d H:i:s',time()-max(1,absint($settings['hardening_resolved_retention_days']))*DAY_IN_SECONDS);
		$rate_cut=gmdate('Y-m-d H:i:s',time()-max(1,absint($settings['hardening_rate_limit_retention_days']))*DAY_IN_SECONDS);
		$events=$wpdb->query($wpdb->prepare("DELETE FROM ".SC_EI_Database::table('health_events')." WHERE (resolved_at IS NOT NULL AND resolved_at < %s) OR (resolved_at IS NULL AND last_seen_at < %s AND severity='info')",$resolved_cut,$open_cut));
		$rates=$wpdb->query($wpdb->prepare("DELETE FROM ".SC_EI_Database::table('rate_limits')." WHERE updated_at < %s",$rate_cut));
		return array('events'=>false===$events?0:$events,'rate_limits'=>false===$rates?0:$rates,'pruned_at'=>$now);
	}

	public static function report(): array {
		return array(
			'schema'=>'sc-engagement-intake-hardening-report/1.0',
			'generated_at'=>current_time('mysql',true),
			'request_id'=>self::request_id(),
			'versions'=>array('plugin'=>SC_EI_VERSION,'database'=>(string)get_option('sc_ei_db_version',''),'hardening'=>SC_EI_HARDENING_SCHEMA_VERSION),
			'metrics'=>self::metrics(),
			'watchdog'=>self::last_watchdog(),
			'events'=>self::events(array('open_only'=>false,'limit'=>2000)),
			'boundaries'=>array('secrets_included'=>false,'personal_content_included'=>false,'automatic_decisions'=>false,'automatic_deletion'=>false),
		);
	}

	public static function capture_fatal(): void {
		$settings=self::settings(); if(empty($settings['hardening_fatal_capture_enabled'])) return;
		$error=error_get_last(); if(!$error || !in_array($error['type']??0,array(E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR,E_USER_ERROR,E_RECOVERABLE_ERROR),true)) return;
		$file=(string)($error['file']??'');
		if ( defined('SC_EI_DIR') && $file && !str_starts_with($file,SC_EI_DIR) ) return;
		self::record_event('php','fatal_error','critical','A fatal PHP error occurred inside the Engagement Intake plugin.',array('error_type'=>absint($error['type']??0),'file'=>basename($file),'line'=>absint($error['line']??0),'request_id'=>self::request_id()));
	}

	public static function send_security_headers(): void {
		if(headers_sent()||empty(self::settings()['hardening_security_headers_enabled'])) return;
		header(self::REQUEST_ID_HEADER.': '.self::request_id());
		header('X-Content-Type-Options: nosniff');
		header('Referrer-Policy: strict-origin-when-cross-origin');
		header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
		if(!empty(self::settings()['hardening_csp_report_only_enabled'])) header("Content-Security-Policy-Report-Only: default-src 'self'; object-src 'none'; base-uri 'self'; frame-ancestors 'self'");
	}
	public static function filter_headers(array $headers): array { if(!empty(self::settings()['hardening_security_headers_enabled'])) { $headers[self::REQUEST_ID_HEADER]=self::request_id(); $headers['X-Content-Type-Options']='nosniff'; $headers['Referrer-Policy']='strict-origin-when-cross-origin'; $headers['Permissions-Policy']='camera=(), microphone=(), geolocation=(), payment=()'; } return $headers; }
	public static function admin_body_class(string $classes): string { return $classes.' sc-ei-a11y-enabled'; }
	public static function admin_skip_link(): void { if(!empty(self::settings()['hardening_accessibility_helpers']) && isset($_GET['page']) && str_starts_with(sanitize_key(wp_unslash($_GET['page'])),'sc-engagement-intake')) echo '<a class="sc-ei-skip-link" href="#sc-ei-primary-content">'.esc_html__('Skip to Engagement Intake content','sustainable-catalyst-engagement-intake').'</a>'; }
	public static function accessibility_live_region(): void { if(!empty(self::settings()['hardening_accessibility_helpers'])) echo '<div class="screen-reader-text" id="sc-ei-live-region" aria-live="polite" aria-atomic="true"></div>'; }

	public static function acquire_lock(string $name,int $ttl) { $key='sc_ei_hardening_lock_'.sanitize_key($name); $token=wp_generate_uuid4(); $payload=array('token'=>$token,'expires'=>time()+max(30,$ttl)); if(add_option($key,$payload,'',false)) return $token; $current=get_option($key,array()); if(absint($current['expires']??0)<time()) { delete_option($key); if(add_option($key,$payload,'',false)) return $token; } return new WP_Error('hardening_lock_busy',__('Another reliability operation is already running.','sustainable-catalyst-engagement-intake')); }
	public static function release_lock(string $name,$token): void { if(is_wp_error($token)) return; $key='sc_ei_hardening_lock_'.sanitize_key($name); $current=get_option($key,array()); if(is_array($current)&&hash_equals((string)($current['token']??''),(string)$token)) delete_option($key); }
	public static function client_ip_hash(): string { $ip=(string)($_SERVER['REMOTE_ADDR']??'unknown'); return hash_hmac('sha256',$ip,wp_salt('secure_auth')); }
	public static function user_agent_hash(): string { return hash_hmac('sha256',substr((string)($_SERVER['HTTP_USER_AGENT']??''),0,500),wp_salt('secure_auth')); }
	private static function formats( array $data, array $integer_fields = array() ): array {
		return array_map(
			static fn( string $key ): string => in_array( $key, $integer_fields, true ) ? '%d' : '%s',
			array_keys( $data )
		);
	}
	private static function valid_uuid($value): string { $value=strtolower(trim(sanitize_text_field((string)$value))); return preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[1-5][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/',$value)?$value:''; }
	private static function safe_context(array $context): array { $clean=array(); foreach($context as $key=>$value){ $key=sanitize_key((string)$key); if(preg_match('/secret|token|password|authorization|cookie|email|name|message|body|content/i',$key)) continue; if(is_bool($value)||is_int($value)||is_float($value)) $clean[$key]=$value; elseif(is_scalar($value)) $clean[$key]=substr(self::redact((string)$value),0,500); } $clean['request_id']=self::request_id(); return $clean; }
	private static function redact(string $value): string { $value=preg_replace('/Bearer\\s+[A-Za-z0-9._~+\\/=-]+/i','Bearer [redacted]',$value); $value=preg_replace('/eyJ[A-Za-z0-9_-]{20,}\\.[A-Za-z0-9_-]{20,}\\.[A-Za-z0-9_-]{10,}/','[redacted-token]',$value); $value=preg_replace('/(?:secret|password|token|api[_-]?key)\\s*[=:]\\s*[^\\s,;]+/i','$1=[redacted]',$value); return substr(sanitize_textarea_field($value),0,2000); }
}
