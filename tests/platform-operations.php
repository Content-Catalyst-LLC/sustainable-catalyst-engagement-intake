<?php
/**
 * Unified platform operational and human-control checks.
 */
$root=dirname(__DIR__);$plugin=$root.'/sustainable-catalyst-engagement-intake';
$repo=file_get_contents($plugin.'/includes/class-sc-ei-platform-repository.php');
$admin=file_get_contents($plugin.'/includes/class-sc-ei-platform-admin.php');
$public=file_get_contents($plugin.'/includes/class-sc-ei-platform-public.php');
$view=file_get_contents($plugin.'/admin/views/platform-overview.php');
$rest=file_get_contents($plugin.'/includes/class-sc-ei-rest.php');
$hardening=file_get_contents($plugin.'/includes/class-sc-ei-hardening-repository.php');
$privacy=file_get_contents($plugin.'/includes/class-sc-ei-privacy-repository.php');
$checks=array(
 'connected readiness model'=>strpos($repo,"'database_tables'")!==false&&strpos($repo,"'protected_storage'")!==false&&strpos($repo,"'https'")!==false&&strpos($repo,"'cron_schedules'")!==false&&strpos($repo,"'public_entry'")!==false&&strpos($repo,"'portal_url'")!==false&&strpos($repo,"'workflow_core'")!==false,
 'production gate requires checks'=>strpos($repo,"'production' === \$state && empty( \$readiness['ready_for_production'] )")!==false,
 'launch remains human controlled'=>strpos($admin,"'SET PLATFORM ' . strtoupper( \$state )")!==false&&strpos($repo,"'automatic_launch' => false")!==false,
 'immutable readiness snapshots'=>strpos($repo,"'content_hash'      => hash( 'sha256', \$json )")!==false&&strpos($admin,"'SNAPSHOT PLATFORM'")!==false,
 'migration is non destructive'=>strpos($repo,"'no_destructive_migration' => true")!==false&&strpos($repo,"'destructive'   => false")!==false,
 'aggregate platform export'=>strpos($repo,"'schema'       => 'sc-unified-contact-engagement-platform/1.0'")!==false&&strpos($admin,'platform_report_exported')!==false,
 'unified public shortcode'=>strpos($public,"add_shortcode( 'sc_contact_engagement_platform'")!==false&&strpos($public,'SC_EI_Public::contact_hub')!==false,
 'public workflow boundary'=>strpos($public,'Submitting a form does not create a contract')!==false&&strpos($public,'cannot accept or reject an inquiry')!==false,
 'existing shortcodes retained'=>strpos($view,'[sc_contact_hub')!==false&&strpos($view,'[sc_engagement_inquiry')!==false&&strpos($view,'[sc_sender_portal')!==false,
 'typed settings and migration controls'=>strpos($admin,"'SAVE PLATFORM SETTINGS'")!==false&&strpos($admin,"'VERIFY PLATFORM MIGRATION'")!==false,
 'read only platform REST'=>strpos($rest,"'/platform/status'")!==false&&strpos($rest,"'read_only'    => true")!==false&&strpos($rest,'WP_REST_Server::READABLE')!==false,
 'platform reliability integration'=>strpos($hardening,"\$checks['platform_columns']")!==false&&strpos($hardening,"\$checks['platform_snapshot']")!==false,
 'platform private inventory'=>strpos($privacy,"'platform_snapshots', 'platform_migrations'")!==false,
 'no automated business mutation'=>strpos($repo,'SC_EI_Inquiry_Repository::update_status')===false&&strpos($repo,'SC_EI_Fit_Repository::finalize')===false&&strpos($repo,'SC_EI_Workflow_Repository::publish')===false&&strpos($repo,'SC_EI_Engagement_Repository::activate')===false,
 'no arbitrary external delivery'=>strpos($repo,'wp_remote_post')===false&&strpos($repo,'wp_remote_request')===false&&strpos($repo,'wp_mail(')===false,
 'dashboard discloses boundaries'=>strpos($view,'Stable human-control boundary')!==false&&strpos($view,'Production status is recorded only through an authorized typed action')!==false,
 'default settings purity'=>preg_match('/public static function default_settings\(\): array \{(.*?)public static function sanitize_settings/s',file_get_contents($plugin.'/includes/class-sc-ei-admin.php'),$m)&&strpos($m[1],'$value')===false,
);
$failed=array_keys(array_filter($checks,fn($v)=>!$v));if($failed){fwrite(STDERR,'Platform operation checks failed: '.implode(', ',$failed).PHP_EOL);exit(1);}foreach($checks as $k=>$v)echo 'PASS: '.$k.PHP_EOL;echo "Unified Contact and Engagement Platform v1.0.0 operation checks passed.\n";
