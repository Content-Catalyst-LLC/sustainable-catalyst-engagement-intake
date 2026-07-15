<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class SC_EI_Analytics_Schema {
 public static function default_settings(): array { return array(
  'analytics_enabled'=>1,'analytics_minimum_cohort'=>5,'analytics_default_days'=>90,
  'analytics_stale_inquiry_days'=>7,'analytics_overdue_followup_hours'=>24,
  'analytics_snapshot_retention_days'=>365,'analytics_daily_snapshots'=>1,
  'analytics_include_financial_totals'=>1,'analytics_no_automated_decisions'=>1,
  'analytics_no_sender_ranking'=>1,'analytics_no_personal_data'=>1,
  'analytics_intelligence_enabled'=>1,'analytics_auto_candidate_findings'=>0,
  'analytics_finding_review_days'=>14,'analytics_snapshot_fresh_days'=>7,
  'analytics_intelligence_retention_days'=>730,'analytics_include_support_intelligence'=>1,
  'analytics_include_workspace_intelligence'=>1,'analytics_include_proposal_intelligence'=>1,
  'analytics_human_review_required'=>1,
 ); }
 public static function ranges(): array { return array(7=>'7 days',30=>'30 days',90=>'90 days',180=>'180 days',365=>'365 days'); }
 public static function sanitize_days($v): int { $v=absint($v); return in_array($v,array_keys(self::ranges()),true)?$v:90; }
 public static function suppress(int $count,int $minimum): bool { return $count>0 && $count<$minimum; }
 public static function rate(int $num,int $den,int $minimum): array {
  $suppressed=self::suppress($den,$minimum);
  return array('numerator'=>$num,'denominator'=>$den,'suppressed'=>$suppressed,'percent'=>$suppressed||$den<1?null:round(($num/$den)*100,1));
 }
}
