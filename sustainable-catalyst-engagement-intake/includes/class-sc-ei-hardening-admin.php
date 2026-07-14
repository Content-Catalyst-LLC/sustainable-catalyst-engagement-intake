<?php
/** Reliability administration. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class SC_EI_Hardening_Admin {
	public static function register(): void {
		add_action('admin_post_sc_ei_run_hardening_watchdog',array(__CLASS__,'handle_watchdog'));
		add_action('admin_post_sc_ei_toggle_public_writes',array(__CLASS__,'handle_toggle_writes'));
		add_action('admin_post_sc_ei_resolve_health_event',array(__CLASS__,'handle_resolve'));
		add_action('admin_post_sc_ei_prune_hardening',array(__CLASS__,'handle_prune'));
		add_action('admin_post_sc_ei_export_hardening_report',array(__CLASS__,'handle_export'));
		add_action('admin_post_sc_ei_save_hardening_settings',array(__CLASS__,'handle_save_settings'));
	}
	public static function submenu(): void { add_submenu_page('sc-engagement-intake',__('Reliability and Security','sustainable-catalyst-engagement-intake'),__('Reliability','sustainable-catalyst-engagement-intake'),'sc_intake_view_reliability','sc-engagement-intake-reliability',array(__CLASS__,'page')); }
	public static function url(array $args=array()): string { return add_query_arg(array_merge(array('page'=>'sc-engagement-intake-reliability'),$args),admin_url('admin.php')); }
	public static function page(): void { self::cap('sc_intake_view_reliability'); $message=isset($_GET['sc_ei_msg'])?sanitize_key(wp_unslash($_GET['sc_ei_msg'])):''; $severity=isset($_GET['severity'])?sanitize_key(wp_unslash($_GET['severity'])):''; $component=isset($_GET['component'])?sanitize_key(wp_unslash($_GET['component'])):''; $open_only=!isset($_GET['show_resolved']); $settings=SC_EI_Hardening_Repository::settings(); $metrics=SC_EI_Hardening_Repository::metrics(); $watchdog=SC_EI_Hardening_Repository::last_watchdog(); $events=SC_EI_Hardening_Repository::events(array('severity'=>$severity,'component'=>$component,'open_only'=>$open_only,'limit'=>1000)); include SC_EI_DIR.'admin/views/reliability.php'; }
	public static function handle_save_settings(): void {
		self::cap('sc_intake_manage_reliability'); check_admin_referer('sc_ei_save_hardening_settings'); self::confirm('SAVE HARDENING SETTINGS',$_POST['confirmation']??'');
		$raw=isset($_POST['hardening'])?(array)wp_unslash($_POST['hardening']):array();
		$current=wp_parse_args(get_option('sc_ei_settings',array()),SC_EI_Admin::default_settings());
		$updates=array(
			'hardening_watchdog_enabled'=>empty($raw['watchdog_enabled'])?0:1,
			'hardening_event_retention_days'=>max(7,min(365,absint($raw['event_retention_days']??90))),
			'hardening_resolved_retention_days'=>max(1,min(180,absint($raw['resolved_retention_days']??30))),
			'hardening_rate_limit_retention_days'=>max(1,min(30,absint($raw['rate_limit_retention_days']??7))),
			'hardening_intake_ip_limit_hour'=>max(1,min(100,absint($raw['intake_ip_limit_hour']??15))),
			'hardening_intake_identity_limit_hour'=>max(1,min(50,absint($raw['intake_identity_limit_hour']??5))),
			'hardening_portal_edge_limit_15m'=>max(5,min(500,absint($raw['portal_edge_limit_15m']??60))),
			'hardening_recovery_edge_limit_hour'=>max(1,min(100,absint($raw['recovery_edge_limit_hour']??10))),
			'hardening_fatal_capture_enabled'=>empty($raw['fatal_capture_enabled'])?0:1,
			'hardening_security_headers_enabled'=>empty($raw['security_headers_enabled'])?0:1,
			'hardening_csp_report_only_enabled'=>empty($raw['csp_report_only_enabled'])?0:1,
			'hardening_accessibility_helpers'=>empty($raw['accessibility_helpers'])?0:1,
			'hardening_no_secret_context'=>1,'hardening_no_automatic_decisions'=>1,'hardening_no_automatic_deletion'=>1,
		);
		update_option('sc_ei_settings',array_merge($current,$updates),false); SC_EI_Hardening_Repository::schedule();
		SC_EI_Audit_Log::record('hardening_settings_updated','Authorized administrator updated reliability and security thresholds.',array('request_id'=>SC_EI_Hardening_Repository::request_id(),'public_writes_paused'=>!empty($current['hardening_public_writes_paused'])),null,null,get_current_user_id()); self::redirect('hardening_settings_saved');
	}

	public static function handle_watchdog(): void { self::cap('sc_intake_manage_reliability'); check_admin_referer('sc_ei_run_hardening_watchdog'); self::confirm('RUN HARDENING CHECK',$_POST['confirmation']??''); SC_EI_Hardening_Repository::watchdog(true); self::redirect('hardening_check_complete'); }
	public static function handle_toggle_writes(): void { self::cap('sc_intake_manage_reliability'); check_admin_referer('sc_ei_toggle_public_writes'); $pause=!empty($_POST['pause']); self::confirm($pause?'PAUSE PUBLIC WRITES':'RESUME PUBLIC WRITES',$_POST['confirmation']??''); $settings=wp_parse_args(get_option('sc_ei_settings',array()),SC_EI_Admin::default_settings()); $settings['hardening_public_writes_paused']=$pause?1:0; update_option('sc_ei_settings',$settings,false); SC_EI_Audit_Log::record($pause?'public_writes_paused':'public_writes_resumed','Authorized administrator changed the public mutation incident-control state.',array('paused'=>$pause,'request_id'=>SC_EI_Hardening_Repository::request_id()),null,null,get_current_user_id()); self::redirect($pause?'public_writes_paused':'public_writes_resumed'); }
	public static function handle_resolve(): void { self::cap('sc_intake_manage_reliability'); $id=absint($_POST['event_id']??0); check_admin_referer('sc_ei_resolve_health_event_'.$id); self::confirm('RESOLVE '.$id,$_POST['confirmation']??''); $result=SC_EI_Hardening_Repository::resolve_event($id,wp_unslash($_POST['resolution_note']??''),get_current_user_id()); self::redirect(is_wp_error($result)?$result->get_error_code():'health_event_resolved'); }
	public static function handle_prune(): void { self::cap('sc_intake_manage_reliability'); check_admin_referer('sc_ei_prune_hardening'); self::confirm('PRUNE HARDENING DATA',$_POST['confirmation']??''); SC_EI_Hardening_Repository::prune(); self::redirect('hardening_data_pruned'); }
	public static function handle_export(): void { self::cap('sc_intake_export_reliability'); check_admin_referer('sc_ei_export_hardening_report'); $payload=SC_EI_Hardening_Repository::report(); nocache_headers(); header('Content-Type: application/json; charset=utf-8'); header('Content-Disposition: attachment; filename="sc-ei-hardening-report-'.gmdate('Ymd-His').'.json"'); header('X-Content-Type-Options: nosniff'); header('Cache-Control: no-store, private'); echo wp_json_encode($payload,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES); exit; }
	private static function cap(string $cap): void { if(!current_user_can($cap)) wp_die(esc_html__('You do not have permission to perform this reliability action.','sustainable-catalyst-engagement-intake'),'',array('response'=>403)); }
	private static function confirm(string $expected,$provided): void { $provided=strtoupper(trim(sanitize_text_field(wp_unslash((string)$provided)))); if(!hash_equals($expected,$provided)) self::redirect('hardening_confirmation_failed'); }
	private static function redirect(string $message): void { wp_safe_redirect(self::url(array('sc_ei_msg'=>sanitize_key($message))),303); exit; }
}
