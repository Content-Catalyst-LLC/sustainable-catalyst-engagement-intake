<?php
$plugin=dirname(__DIR__).'/sustainable-catalyst-engagement-intake';
$repo=file_get_contents($plugin.'/includes/class-sc-ei-hardening-repository.php');
$admin=file_get_contents($plugin.'/includes/class-sc-ei-hardening-admin.php');
$form=file_get_contents($plugin.'/includes/class-sc-ei-form-handler.php');
$portal=file_get_contents($plugin.'/includes/class-sc-ei-portal-public.php');
$diagnostics=file_get_contents($plugin.'/includes/class-sc-ei-diagnostics.php');
$view=file_get_contents($plugin.'/admin/views/reliability.php');
$core_admin=file_get_contents($plugin.'/includes/class-sc-ei-admin.php');
$default_start=strpos($core_admin,'public static function default_settings(): array');
$sanitize_start=strpos($core_admin,'public static function sanitize_settings', $default_start);
$default_segment=substr($core_admin,$default_start,$sanitize_start-$default_start);
$checks=array(
 'request correlation'=>strpos($repo,'X-SC-EI-Request-ID')!==false && strpos($repo,'public static function request_id')!==false,
 'durable rate limiter'=>strpos($repo,'ON DUPLICATE KEY UPDATE hits = hits + 1')!==false && strpos($repo,'blocked_until')!==false && strpos($repo,'fail open for availability')!==false,
 'identity minimization'=>strpos($repo,'client_ip_hash')!==false && strpos($repo,'user_agent_hash')!==false && strpos($repo,"hash_hmac( 'sha256'")!==false,
 'deduplicated health ledger'=>strpos($repo,'ON DUPLICATE KEY UPDATE severity = VALUES(severity)')!==false && strpos($repo,"unset( \$fingerprint_context['request_id'] )")!==false,
 'secret-safe context'=>strpos($repo,"preg_match('/secret|token|password|authorization|cookie|email|name|message|body|content/i'")!==false && strpos($repo,'[redacted-token]')!==false,
 'fatal metadata only'=>strpos($repo,'capture_fatal')!==false && strpos($repo,"'file'=>basename(\$file)")!==false && strpos($repo,"'line'=>absint")!==false,
 'real watchdog hooks'=>strpos($repo,'SC_EI_Retention::CRON_HOOK')!==false && strpos($repo,'SC_EI_Notification_Service::CRON_HOOK')!==false && strpos($repo,"sc_ei_portal_cleanup")!==false && strpos($repo,"sc_ei_graph_catchup")!==false,
 'incident pause'=>strpos($repo,'public_writes_paused')!==false && strpos($admin,'PAUSE PUBLIC WRITES')!==false && strpos($admin,'RESUME PUBLIC WRITES')!==false,
 'read-only portal survives pause'=>strpos($portal,"\$read_only_permissions = array( 'view_meetings', 'view_proposals' )")!==false && strpos($portal,'This secure portal action is temporarily paused')!==false,
 'intake uses durable limiter'=>strpos($form,'SC_EI_Hardening_Repository::guard_public_write')!==false && strpos($form,'SC_EI_Hardening_Repository::consume_rate_limit')!==false,
 'human operations'=>strpos($admin,'RUN HARDENING CHECK')!==false && strpos($admin,'SAVE HARDENING SETTINGS')!==false && strpos($admin,"'RESOLVE '.\$id")!==false && strpos($admin,'check_admin_referer')!==false,
 'redacted export'=>strpos($admin,'sc-ei-hardening-report-')!==false && strpos($repo,"'secrets_included'=>false")!==false && strpos($repo,"'personal_content_included'=>false")!==false,
 'diagnostics integration'=>strpos($diagnostics,"'hardening_schema_version'")!==false && strpos($diagnostics,"'durable_rate_limits'    => true")!==false,
 'boundary disclosure'=>strpos($view,'does not inspect message bodies or documents')!==false && strpos($view,'does not')!==false,
 'default settings purity'=>strpos($default_segment,'SC_EI_Hardening_Schema::default_settings()')!==false && strpos($default_segment,'$value[')===false && strpos($default_segment,'$current[')===false,
);
$failed=array_keys(array_filter($checks,fn($v)=>!$v)); if($failed){fwrite(STDERR,'Hardening operation checks failed: '.implode(', ',$failed).PHP_EOL);exit(1);} foreach($checks as $k=>$v) echo 'PASS: '.$k.PHP_EOL; echo "Engagement Intake v0.11.0 hardening operations passed.\n";
