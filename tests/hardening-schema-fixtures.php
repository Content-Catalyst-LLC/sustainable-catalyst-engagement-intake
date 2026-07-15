<?php
$plugin=dirname(__DIR__).'/sustainable-catalyst-engagement-intake';
$main=file_get_contents($plugin.'/sustainable-catalyst-engagement-intake.php');
$db=file_get_contents($plugin.'/includes/class-sc-ei-database.php');
$schema=file_get_contents($plugin.'/includes/class-sc-ei-hardening-schema.php');
$caps=file_get_contents($plugin.'/includes/class-sc-ei-capabilities.php');
$activator=file_get_contents($plugin.'/includes/class-sc-ei-activator.php');
$uninstall=file_get_contents($plugin.'/uninstall.php');
$checks=array(
 'v1.1.1 plugin markers'=>strpos($main,'Version:     2.0.0')!==false && strpos($main,"SC_EI_DB_VERSION', '2.0.0'")!==false && strpos($main,"SC_EI_HARDENING_SCHEMA_VERSION', '1.0.0'")!==false,
 'hardening components loaded'=>strpos($main,'class-sc-ei-hardening-schema.php')!==false && strpos($main,'class-sc-ei-hardening-repository.php')!==false && strpos($main,'class-sc-ei-hardening-admin.php')!==false,
 'health event table'=>strpos($db,'$sql_health_events')!==false && strpos($db,'UNIQUE KEY fingerprint')!==false && strpos($db,'occurrences bigint(20)')!==false && strpos($db,'resolved_at datetime')!==false,
 'durable rate table'=>strpos($db,'$sql_rate_limits')!==false && strpos($db,'UNIQUE KEY scope_bucket_window')!==false && strpos($db,'blocked_until datetime')!==false,
 'dbDelta installs hardening'=>strpos($db,'dbDelta( $sql_health_events )')!==false && strpos($db,'dbDelta( $sql_rate_limits )')!==false,
 'hardening diagnostics mapping'=>strpos($db,'public static function hardening_columns_exist')!==false,
 'fixed defaults'=>strpos($schema,"'hardening_no_secret_context'          => 1")!==false && strpos($schema,"'hardening_no_automatic_decisions'     => 1")!==false && strpos($schema,"'hardening_no_automatic_deletion'      => 1")!==false,
 'least privilege capabilities'=>strpos($caps,'sc_intake_view_reliability')!==false && strpos($caps,'sc_intake_manage_reliability')!==false && strpos($caps,'sc_intake_export_reliability')!==false,
 'activation schedules hardening'=>strpos($activator,'SC_EI_Hardening_Repository::schedule()')!==false && strpos($activator,"sc_ei_hardening_schema_version")!==false,
 'deactivation and uninstall cleanup'=>strpos($activator,'SC_EI_Hardening_Repository::unschedule()')!==false && strpos($uninstall,"sc_ei_hardening_watchdog")!==false && strpos($uninstall,"sc_ei_hardening_prune")!==false && strpos($uninstall,"sc_ei_hardening_lock_")!==false,
);
$failed=array_keys(array_filter($checks,fn($v)=>!$v)); if($failed){fwrite(STDERR,'Hardening schema checks failed: '.implode(', ',$failed).PHP_EOL);exit(1);} foreach($checks as $k=>$v) echo 'PASS: '.$k.PHP_EOL; echo "Engagement Intake v1.0.0 hardening schema fixtures passed.\n";
