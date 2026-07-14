<?php
/**
 * Stable 1.0 product-surface and backward-compatibility checks.
 */
$root=dirname(__DIR__);$plugin=$root.'/sustainable-catalyst-engagement-intake';
$main=file_get_contents($plugin.'/sustainable-catalyst-engagement-intake.php');
$admin=file_get_contents($plugin.'/includes/class-sc-ei-admin.php');
$public=file_get_contents($plugin.'/includes/class-sc-ei-public.php');
$portal=file_get_contents($plugin.'/includes/class-sc-ei-portal-public.php');
$platform_public=file_get_contents($plugin.'/includes/class-sc-ei-platform-public.php');
$admin_css=file_get_contents($plugin.'/assets/css/admin.css');
$public_css=file_get_contents($plugin.'/assets/css/public.css');
$readme=file_get_contents($plugin.'/readme.txt');
$checks=array(
 'stable product identity'=>strpos($main,'Sustainable Catalyst Contact and Engagement Platform')!==false,
 'unified parent navigation'=>strpos($admin,"__( 'Contact & Engagement'")!==false&&strpos($admin,"array( 'SC_EI_Platform_Admin', 'page' )")!==false,
 'dedicated inquiries workspace'=>strpos($admin,"'sc-engagement-intake-inquiries'")!==false,
 'reviewer receives unified read visibility'=>preg_match('/private const REVIEWER = array\((.*?)private const MANAGER/s',file_get_contents($plugin.'/includes/class-sc-ei-capabilities.php'),$reviewer_match)&&strpos($reviewer_match[1],"'sc_intake_view_reliability'")!==false&&strpos($reviewer_match[1],"'sc_intake_view_workflow_core'")!==false&&strpos($reviewer_match[1],"'sc_intake_view_platform'")!==false,
 'legacy inquiry routing preserved'=>strpos(file_get_contents($plugin.'/includes/class-sc-ei-platform-admin.php'),'SC_EI_Admin::inquiries_page()')!==false,
 'workflow core inquiry link uses unified workspace'=>strpos(file_get_contents($plugin.'/admin/views/workflow-core.php'),'page=sc-engagement-intake-inquiries&action=view&inquiry=')!==false&&strpos(file_get_contents($plugin.'/admin/views/workflow-core.php'),'page=sc-engagement-intake-view')===false,
 'legacy contact shortcodes retained'=>strpos($public,"add_shortcode( 'sc_contact_hub'")!==false&&strpos($public,"add_shortcode( 'sc_contact_form'")!==false&&strpos($public,"add_shortcode( 'sc_engagement_inquiry'")!==false,
 'legacy portal shortcode retained'=>strpos($portal,"add_shortcode( 'sc_sender_portal'")!==false,
 'new unified shortcode composes existing intake'=>strpos($platform_public,'SC_EI_Public::contact_hub')!==false,
 'responsive platform administration'=>strpos($admin_css,'.sc-ei-platform-layout')!==false&&strpos($admin_css,'@media (max-width: 640px)')!==false,
 'responsive public entry'=>strpos($public_css,'.sc-ei-platform-public__routes')!==false&&strpos($public_css,'@media (max-width: 900px)')!==false,
 'platform accessibility styles'=>strpos($public_css,'prefers-reduced-motion')!==false&&strpos($public_css,'forced-colors')!==false&&strpos($admin_css,':focus-visible')!==false,
 'readme stable tag'=>strpos($readme,'Stable tag: 1.1.1')!==false,
 'no schema reset'=>strpos($main,"SC_EI_PORTAL_SCHEMA_VERSION', '1.4.0'")!==false&&strpos($main,"SC_EI_WORKFLOW_SCHEMA_VERSION', '1.1.0'")!==false&&strpos($main,"SC_EI_WORKFLOW_CORE_SCHEMA_VERSION', '1.0.0'")!==false,
 'workflow core REST redacts command bodies'=>strpos(file_get_contents($plugin.'/includes/class-sc-ei-rest.php'),"unset( \$command['payload_json'], \$command['result_json'], \$command['reason'], \$command['error_message'] )")!==false,
 'same plugin slug and text domain'=>strpos($main,'Text Domain: sustainable-catalyst-engagement-intake')!==false&&basename($plugin)==='sustainable-catalyst-engagement-intake',
);
$failed=array_keys(array_filter($checks,fn($v)=>!$v));if($failed){fwrite(STDERR,'Unified release checks failed: '.implode(', ',$failed).PHP_EOL);exit(1);}foreach($checks as $k=>$v)echo 'PASS: '.$k.PHP_EOL;echo "Unified Contact and Engagement Platform v1.1.0 release checks passed.\n";
