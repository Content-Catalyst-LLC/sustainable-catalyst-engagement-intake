<?php
$plugin = dirname(__DIR__) . '/sustainable-catalyst-engagement-intake';
$repo=file_get_contents($plugin.'/includes/class-sc-ei-analytics-repository.php');
$admin=file_get_contents($plugin.'/includes/class-sc-ei-analytics-admin.php');
$view=file_get_contents($plugin.'/admin/views/analytics.php');
$caps=file_get_contents($plugin.'/includes/class-sc-ei-capabilities.php');
$checks=array(
 'connected funnel' => strpos($repo, '\'inquiries\'=>$total')!==false && strpos($repo, '\'proposal_contracted\'=>$contracted')!==false && strpos($repo, '\'engagement_activated\'=>$active')!==false,
 'mix analytics' => strpos($repo, '\'sources\'=>$sources')!==false && strpos($repo, '\'fit\'=>$fit')!==false && strpos($repo, '\'engagements\'=>$engagements')!==false,
 'cycle time medians' => strpos($repo,'median_hours_to_review_start')!==false && strpos($repo,'median_hours_proposal_to_contract')!==false,
 'operational alerts' => strpos($repo,'overdue_reviews')!==false && strpos($repo,'stale_open_inquiries')!==false && strpos($repo,'graph_permanent_failures')!==false && strpos($repo,'quarantined_documents')!==false,
 'aggregate boundaries' => strpos($repo,"'personal_data'=>false")!==false && strpos($repo,"'sender_ranking'=>false")!==false && strpos($repo,"'automated_decisions'=>false")!==false,
 'auditable snapshots' => strpos($repo,'create_snapshot')!==false && strpos($repo,'hash(\'sha256\',$json)')!==false && strpos($repo,'daily_snapshot')!==false,
 'human snapshot confirmation' => strpos($admin,'SNAPSHOT ANALYTICS')!==false && strpos($admin,'check_admin_referer')!==false,
 'aggregate export' => strpos($admin,'analytics_exported')!==false && strpos($admin,"'personal_data'=>false")!==false,
 'dashboard disclosure' => strpos($view,'No names, emails, message bodies, document contents, sender scores')!==false,
 'least privilege capabilities' => strpos($caps,'sc_intake_view_analytics')!==false && strpos($caps,'sc_intake_manage_analytics')!==false && strpos($caps,'sc_intake_export_analytics')!==false,
);
$failed=array_keys(array_filter($checks,fn($v)=>!$v)); if($failed){fwrite(STDERR,'Analytics operation checks failed: '.implode(', ',$failed).PHP_EOL);exit(1);} foreach($checks as $k=>$v) echo 'PASS: '.$k.PHP_EOL; echo "Engagement Intake v0.12.0 analytics operations passed.\n";
