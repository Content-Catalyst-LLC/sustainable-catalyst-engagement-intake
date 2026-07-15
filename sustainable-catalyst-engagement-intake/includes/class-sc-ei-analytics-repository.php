<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class SC_EI_Analytics_Repository {
 const DAILY_HOOK='sc_ei_analytics_daily_snapshot';
 public static function register(): void { add_action(self::DAILY_HOOK,array(__CLASS__,'daily_snapshot')); }
 public static function schedule(): void { if(!wp_next_scheduled(self::DAILY_HOOK)){wp_schedule_event(time()+HOUR_IN_SECONDS,'daily',self::DAILY_HOOK);} }
 public static function unschedule(): void { wp_clear_scheduled_hook(self::DAILY_HOOK); }
 public static function settings(): array { return wp_parse_args(get_option('sc_ei_settings',array()),SC_EI_Analytics_Schema::default_settings()); }
 public static function dashboard(int $days=90): array {
  global $wpdb; $days=SC_EI_Analytics_Schema::sanitize_days($days); $s=self::settings(); $min=max(2,absint($s['analytics_minimum_cohort']));
  $from=gmdate('Y-m-d H:i:s',time()-$days*DAY_IN_SECONDS); $now=current_time('mysql',true);
  $i=SC_EI_Database::table('inquiries'); $r=SC_EI_Database::table('reviews'); $f=SC_EI_Database::table('fit_assessments');
  $m=SC_EI_Database::table('meeting_offers'); $p=SC_EI_Database::table('proposals'); $e=SC_EI_Database::table('engagements');
  $c=SC_EI_Database::table('communications'); $a=SC_EI_Database::table('attachments');
  $total=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$i} WHERE created_at >= %s AND privacy_status <> 'erased'",$from));
  $reviewed=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$i} WHERE created_at >= %s AND review_started_at IS NOT NULL AND privacy_status <> 'erased'",$from));
  $decided=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$i} WHERE created_at >= %s AND decision_at IS NOT NULL AND privacy_status <> 'erased'",$from));
  $meetings=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT inquiry_id) FROM {$m} WHERE created_at >= %s AND status IN ('accepted_pending_link','scheduled','completed')",$from));
  $published=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT inquiry_id) FROM {$p} WHERE published_at >= %s",$from));
  $contracted=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT inquiry_id) FROM {$p} WHERE contracted_at >= %s",$from));
  $active=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT inquiry_id) FROM {$e} WHERE activated_at >= %s",$from));
  $funnel=array('inquiries'=>$total,'review_started'=>$reviewed,'decision_recorded'=>$decided,'meeting_progressed'=>$meetings,'proposal_published'=>$published,'proposal_contracted'=>$contracted,'engagement_activated'=>$active);
  $rates=array('review_start'=>SC_EI_Analytics_Schema::rate($reviewed,$total,$min),'decision'=>SC_EI_Analytics_Schema::rate($decided,$total,$min),'meeting'=>SC_EI_Analytics_Schema::rate($meetings,$total,$min),'proposal'=>SC_EI_Analytics_Schema::rate($published,$total,$min),'contract'=>SC_EI_Analytics_Schema::rate($contracted,$published,$min),'activation'=>SC_EI_Analytics_Schema::rate($active,$contracted,$min));
  $sources=self::group($wpdb,"SELECT source_page label, COUNT(*) count FROM {$i} WHERE created_at >= %s AND privacy_status <> 'erased' GROUP BY source_page ORDER BY count DESC",$from,$min);
  $types=self::group($wpdb,"SELECT inquiry_type label, COUNT(*) count FROM {$i} WHERE created_at >= %s AND privacy_status <> 'erased' GROUP BY inquiry_type ORDER BY count DESC",$from,$min);
  $services=self::group($wpdb,"SELECT service_interest label, COUNT(*) count FROM {$i} WHERE created_at >= %s AND privacy_status <> 'erased' AND service_interest<>'' GROUP BY service_interest ORDER BY count DESC",$from,$min);
  $statuses=self::group($wpdb,"SELECT status label, COUNT(*) count FROM {$i} WHERE created_at >= %s AND privacy_status <> 'erased' GROUP BY status ORDER BY count DESC",$from,$min);
  $fit=self::group($wpdb,"SELECT recommendation label, COUNT(*) count FROM {$f} WHERE finalized_at >= %s AND status='finalized' GROUP BY recommendation ORDER BY count DESC",$from,$min);
  $engagements=self::group($wpdb,"SELECT status label, COUNT(*) count FROM {$e} WHERE created_at >= %s GROUP BY status ORDER BY count DESC",$from,$min);
  $median_first=self::median($wpdb->get_col($wpdb->prepare("SELECT TIMESTAMPDIFF(HOUR,created_at,review_started_at) FROM {$i} WHERE created_at >= %s AND review_started_at IS NOT NULL AND privacy_status <> 'erased'",$from)));
  $median_decision=self::median($wpdb->get_col($wpdb->prepare("SELECT TIMESTAMPDIFF(HOUR,created_at,decision_at) FROM {$i} WHERE created_at >= %s AND decision_at IS NOT NULL AND privacy_status <> 'erased'",$from)));
  $median_contract=self::median($wpdb->get_col($wpdb->prepare("SELECT TIMESTAMPDIFF(HOUR,created_at,contracted_at) FROM {$p} WHERE created_at >= %s AND contracted_at IS NOT NULL",$from)));
  $overdue_reviews=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$i} WHERE review_due_at < %s AND review_completed_at IS NULL AND privacy_status <> 'erased'",$now));
  $stale_cutoff=gmdate('Y-m-d H:i:s',time()-max(1,absint($s['analytics_stale_inquiry_days']))*DAY_IN_SECONDS);
  $stale=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$i} WHERE updated_at < %s AND status NOT IN ('closed','declined','accepted','withdrawn') AND privacy_status <> 'erased'",$stale_cutoff));
  $followups=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$i} WHERE next_follow_up_at < %s AND communication_status='open' AND privacy_status <> 'erased'",$now));
  $unassigned=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$i} WHERE created_at >= %s AND assigned_user_id IS NULL AND status NOT IN ('closed','declined','withdrawn') AND privacy_status <> 'erased'",$from));
  $blocked_requirements=(int)$wpdb->get_var("SELECT COUNT(*) FROM ".SC_EI_Database::table('engagement_requirements')." WHERE is_required=1 AND status NOT IN ('complete','waived')");
  $graph_failures=(int)$wpdb->get_var("SELECT COUNT(*) FROM ".SC_EI_Database::table('graph_operations')." WHERE status='permanent_failure'");
  $quarantine=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$a} WHERE quarantine_status IN ('quarantined','scan_failed','manual_review')");
  $outbound=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$c} WHERE created_at >= %s AND direction='outbound'",$from));
  $inbound=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$c} WHERE created_at >= %s AND direction='inbound'",$from));
  $proposal_value=(int)$wpdb->get_var($wpdb->prepare("SELECT COALESCE(SUM(total_minor),0) FROM {$p} WHERE contracted_at >= %s",$from));
  return array('schema'=>'sc-engagement-intake-analytics/1.0','generated_at'=>$now,'range_days'=>$days,'from_utc'=>$from,'minimum_cohort'=>$min,
   'funnel'=>$funnel,'rates'=>$rates,'mix'=>array('sources'=>$sources,'types'=>$types,'services'=>$services,'statuses'=>$statuses,'fit'=>$fit,'engagements'=>$engagements),
   'timing'=>array('median_hours_to_review_start'=>$median_first,'median_hours_to_decision'=>$median_decision,'median_hours_proposal_to_contract'=>$median_contract),
   'operations'=>array('overdue_reviews'=>$overdue_reviews,'stale_open_inquiries'=>$stale,'overdue_followups'=>$followups,'unassigned_open_inquiries'=>$unassigned,'blocking_engagement_requirements'=>$blocked_requirements,'graph_permanent_failures'=>$graph_failures,'quarantined_documents'=>$quarantine),
   'communications'=>array('outbound'=>$outbound,'inbound'=>$inbound),'financial'=>array('contracted_total_minor'=>$proposal_value,'currency'=>'USD','included'=>!empty($s['analytics_include_financial_totals'])),
   'boundaries'=>array('aggregate_only'=>true,'minimum_cohort_suppression'=>true,'personal_data'=>false,'sender_ranking'=>false,'automated_decisions'=>false,'message_bodies'=>false,'document_contents'=>false));
 }
 private static function group($wpdb,string $sql,string $from,int $min): array { $rows=(array)$wpdb->get_results($wpdb->prepare($sql,$from),ARRAY_A); foreach($rows as &$row){$row['count']=(int)$row['count'];$row['suppressed']=SC_EI_Analytics_Schema::suppress($row['count'],$min);if($row['suppressed']){$row['label']='Small cohort';$row['count']=null;}} return $rows; }
 private static function median(array $values): ?float { $v=array_values(array_filter(array_map('floatval',$values),fn($x)=>$x>=0)); if(!$v)return null; sort($v);$n=count($v);$m=intdiv($n,2);return round($n%2?$v[$m]:($v[$m-1]+$v[$m])/2,1); }
 public static function create_snapshot(int $days,int $actor=0){ global $wpdb; $payload=self::dashboard($days); $json=wp_json_encode($payload,JSON_UNESCAPED_SLASHES); $hash=hash('sha256',$json); $data=array('public_id'=>wp_generate_uuid4(),'range_days'=>$days,'period_start'=>$payload['from_utc'],'period_end'=>$payload['generated_at'],'minimum_cohort'=>$payload['minimum_cohort'],'payload_json'=>$json,'content_hash'=>$hash,'generated_by'=>$actor?:null,'generated_at'=>$payload['generated_at']); $ok=$wpdb->insert(SC_EI_Database::table('analytics_snapshots'),$data,array('%s','%d','%s','%s','%d','%s','%s','%d','%s')); return false===$ok?new WP_Error('analytics_snapshot_failed','Analytics snapshot could not be saved.'):array_merge($data,array('id'=>(int)$wpdb->insert_id)); }
 public static function daily_snapshot(): void { $s=self::settings(); if(empty($s['analytics_enabled'])||empty($s['analytics_daily_snapshots']))return; if(class_exists('SC_EI_Service_Intelligence_Repository')){SC_EI_Service_Intelligence_Repository::create_snapshot(absint($s['analytics_default_days']),0);SC_EI_Service_Intelligence_Repository::prune_closed_findings();}else{self::create_snapshot(absint($s['analytics_default_days']),0);} self::purge_snapshots(); }
 public static function purge_snapshots(): int { global $wpdb;$days=max(30,absint(self::settings()['analytics_snapshot_retention_days']));$cut=gmdate('Y-m-d H:i:s',time()-$days*DAY_IN_SECONDS);$r=$wpdb->query($wpdb->prepare('DELETE FROM '.SC_EI_Database::table('analytics_snapshots').' WHERE generated_at < %s',$cut));return false===$r?0:(int)$r; }
 public static function snapshots(int $limit=100): array { global $wpdb; return (array)$wpdb->get_results($wpdb->prepare('SELECT id,public_id,range_days,period_start,period_end,minimum_cohort,content_hash,generated_by,generated_at FROM '.SC_EI_Database::table('analytics_snapshots').' ORDER BY generated_at DESC LIMIT %d',max(1,min(1000,$limit))),ARRAY_A); }
}
