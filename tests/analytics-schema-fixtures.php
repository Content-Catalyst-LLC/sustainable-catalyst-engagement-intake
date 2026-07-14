<?php
$plugin = dirname(__DIR__) . '/sustainable-catalyst-engagement-intake';
$main = file_get_contents($plugin . '/sustainable-catalyst-engagement-intake.php');
$db = file_get_contents($plugin . '/includes/class-sc-ei-database.php');
$schema = file_get_contents($plugin . '/includes/class-sc-ei-analytics-schema.php');
$checks = array(
 'v1.1.0 plugin markers' => strpos($main,'Version:     1.1.0')!==false && strpos($main,"SC_EI_DB_VERSION', '1.1.0'")!==false && strpos($main,"SC_EI_ANALYTICS_SCHEMA_VERSION', '1.0.0'")!==false,
 'analytics snapshot table' => strpos($db,'$sql_analytics_snapshots')!==false && strpos($db,'content_hash char(64)')!==false && strpos($db,'minimum_cohort')!==false,
 'fixed privacy settings' => strpos($schema,"'analytics_no_automated_decisions'=>1")!==false && strpos($schema,"'analytics_no_sender_ranking'=>1")!==false && strpos($schema,"'analytics_no_personal_data'=>1")!==false,
 'cohort suppression' => strpos($schema,'public static function suppress')!==false && strpos($schema,'public static function rate')!==false,
);
$failed=array_keys(array_filter($checks,fn($v)=>!$v)); if($failed){fwrite(STDERR,'Analytics schema checks failed: '.implode(', ',$failed).PHP_EOL);exit(1);} foreach($checks as $k=>$v) echo 'PASS: '.$k.PHP_EOL; echo "Engagement Intake v1.0.0 analytics schema fixtures passed.\n";
