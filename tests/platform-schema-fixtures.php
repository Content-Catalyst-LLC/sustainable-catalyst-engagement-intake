<?php
/**
 * Unified platform schema and installation checks.
 */
$root=dirname(__DIR__);$plugin=$root.'/sustainable-catalyst-engagement-intake';
$main=file_get_contents($plugin.'/sustainable-catalyst-engagement-intake.php');
$db=file_get_contents($plugin.'/includes/class-sc-ei-database.php');
$schema=file_get_contents($plugin.'/includes/class-sc-ei-platform-schema.php');
$repo=file_get_contents($plugin.'/includes/class-sc-ei-platform-repository.php');
$caps=file_get_contents($plugin.'/includes/class-sc-ei-capabilities.php');
$activator=file_get_contents($plugin.'/includes/class-sc-ei-activator.php');
$boot=file_get_contents($plugin.'/includes/class-sc-ei-plugin.php');
$uninstall=file_get_contents($plugin.'/uninstall.php');
$checks=array(
 'v1.1.0 stable markers'=>strpos($main,'Plugin Name: Sustainable Catalyst Contact and Engagement Platform')!==false&&strpos($main,'Version:     1.4.1')!==false&&strpos($main,"SC_EI_DB_VERSION', '1.4.0'")!==false&&strpos($main,"SC_EI_PLATFORM_SCHEMA_VERSION', '1.4.1'")!==false,
 'platform components loaded'=>strpos($main,'class-sc-ei-platform-schema.php')!==false&&strpos($main,'class-sc-ei-platform-repository.php')!==false&&strpos($main,'class-sc-ei-platform-validation.php')!==false&&strpos($main,'class-sc-ei-platform-public.php')!==false&&strpos($main,'class-sc-ei-platform-admin.php')!==false,
 'readiness snapshot table'=>strpos($db,'$sql_platform_snapshots')!==false&&strpos($db,'readiness_score int')!==false&&strpos($db,'content_hash char(64)')!==false,
 'migration journal table'=>strpos($db,'$sql_platform_migrations')!==false&&strpos($db,'UNIQUE KEY migration_key')!==false&&strpos($db,'schema_hash char(64)')!==false,
 'platform tables installed'=>strpos($db,'dbDelta( $sql_platform_snapshots )')!==false&&strpos($db,'dbDelta( $sql_platform_migrations )')!==false,
 'platform table discovery'=>strpos($db,"'platform_snapshots', 'platform_migrations'")!==false,
 'exact platform diagnostics'=>strpos($db,'public static function platform_columns_exist')!==false&&strpos($db,"'platform_snapshots' => array(")!==false&&strpos($db,"'platform_migrations' => array(")!==false,
 'launch lifecycle taxonomy'=>strpos($schema,"'setup'")!==false&&strpos($schema,"'pilot'")!==false&&strpos($schema,"'production'")!==false&&strpos($schema,"'maintenance'")!==false,
 'fixed platform defaults'=>strpos($schema,"'platform_no_auto_launch'")!==false&&strpos($schema,"'platform_no_auto_acceptance'")!==false&&strpos($schema,"'platform_no_auto_fit_decision'")!==false&&strpos($schema,"'platform_no_auto_contract'")!==false&&strpos($schema,"'platform_no_auto_activation'")!==false&&strpos($schema,"'platform_no_auto_payment'")!==false,
 'idempotent migration key'=>strpos($repo,"public const MIGRATION_KEY = 'v1_0_0_unified_contact_engagement_platform'")!==false&&strpos($repo,'WHERE migration_key = %s')!==false&&strpos($repo,"'completed' === \$existing['status']")!==false,
 'schema hash provenance'=>strpos($repo,"hash( 'sha256', wp_json_encode( self::schema_versions()")!==false,
 'platform scheduling'=>strpos($repo,"public const SNAPSHOT_HOOK = 'sc_ei_platform_readiness_snapshot'")!==false&&strpos($activator,'SC_EI_Platform_Repository::schedule()')!==false&&strpos($activator,'SC_EI_Platform_Repository::unschedule()')!==false,
 'platform upgrade runtime'=>strpos($boot,'SC_EI_Platform_Repository::maybe_upgrade()')!==false&&strpos($boot,'SC_EI_Platform_Public::register()')!==false,
 'least privilege capabilities'=>strpos($caps,"'sc_intake_view_platform'")!==false&&strpos($caps,"'sc_intake_manage_platform'")!==false&&strpos($caps,"'sc_intake_snapshot_platform'")!==false&&strpos($caps,"'sc_intake_export_platform'")!==false&&strpos($caps,"'sc_intake_launch_platform'")!==false,
 'uninstall cleanup'=>strpos($uninstall,"sc_ei_platform_readiness_snapshot")!==false&&strpos($uninstall,"sc_ei_platform_schema_version")!==false&&strpos($uninstall,"sc_ei_platform_launch_record")!==false&&strpos($uninstall,"sc_ei_platform_live_validation")!==false&&strpos($uninstall,"sc_ei_platform_backup_attestation")!==false,
);
$failed=array_keys(array_filter($checks,fn($v)=>!$v));if($failed){fwrite(STDERR,'Platform schema checks failed: '.implode(', ',$failed).PHP_EOL);exit(1);}foreach($checks as $k=>$v)echo 'PASS: '.$k.PHP_EOL;echo "Sustainable Catalyst Contact and Engagement Platform v1.0.3 schema fixtures passed.\n";
