<?php
$plugin=dirname(__DIR__).'/sustainable-catalyst-engagement-intake';
$repo=file_get_contents($plugin.'/includes/class-sc-ei-hardening-repository.php');
$admin=file_get_contents($plugin.'/assets/js/admin.js');
$public=file_get_contents($plugin.'/assets/js/public.js');
$admin_css=file_get_contents($plugin.'/assets/css/admin.css');
$public_css=file_get_contents($plugin.'/assets/css/public.css');
$view=file_get_contents($plugin.'/admin/views/reliability.php');
$portal=file_get_contents($plugin.'/includes/class-sc-ei-portal-public.php');
$checks=array(
 'security headers'=>strpos($repo,'X-Content-Type-Options: nosniff')!==false && strpos($repo,'Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()')!==false && strpos($repo,'Content-Security-Policy-Report-Only')!==false,
 'portal no-store retained'=>strpos($portal,'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private')!==false && strpos($portal,'X-Frame-Options: DENY')!==false,
 'skip link and primary target'=>strpos($repo,'sc-ei-skip-link')!==false && strpos($view,'id="sc-ei-primary-content"')!==false,
 'polite live region'=>strpos($repo,'aria-live="polite"')!==false && strpos($admin,'scEiAnnounce')!==false && strpos($public,'scEiAnnounce')!==false,
 'invalid-field announcements'=>strpos($admin,'document.addEventListener("invalid"')!==false && strpos($public,'document.addEventListener("invalid"')!==false,
 'busy submit state'=>strpos($admin,'aria-busy')!==false && strpos($public,'aria-busy')!==false,
 'keyboard table region'=>strpos($view,'sc-ei-table-scroll')!==false && strpos($view,'tabindex="0"')!==false,
 'reduced motion'=>strpos($admin_css,'prefers-reduced-motion')!==false && strpos($public_css,'prefers-reduced-motion')!==false,
 'forced colors'=>strpos($admin_css,'forced-colors: active')!==false && strpos($public_css,'forced-colors: active')!==false,
 'visible focus'=>strpos($admin_css,':focus-visible')!==false && strpos($public_css,':focus-visible')!==false,
 'table headers scoped'=>strpos($view,'<th scope="col">')!==false,
);
$failed=array_keys(array_filter($checks,fn($v)=>!$v)); if($failed){fwrite(STDERR,'Accessibility/security checks failed: '.implode(', ',$failed).PHP_EOL);exit(1);} foreach($checks as $k=>$v) echo 'PASS: '.$k.PHP_EOL; echo "Engagement Intake v1.0.0 accessibility and security checks passed.\n";
