<?php
/**
 * v1.0.3 pilot findings and public-launch hardening contracts.
 */
$root = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';
$main = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$pilot = file_get_contents( $plugin . '/includes/class-sc-ei-pilot-operations.php' );
$platform = file_get_contents( $plugin . '/includes/class-sc-ei-platform-repository.php' );
$validation = file_get_contents( $plugin . '/includes/class-sc-ei-platform-validation.php' );
$public = file_get_contents( $plugin . '/includes/class-sc-ei-platform-public.php' );
$form = file_get_contents( $plugin . '/includes/class-sc-ei-public.php' );
$upload = file_get_contents( $plugin . '/includes/class-sc-ei-upload-validator.php' );
$js = file_get_contents( $plugin . '/assets/js/public.js' );
$view = file_get_contents( $plugin . '/admin/views/platform-overview.php' );
$uninstall = file_get_contents( $plugin . '/uninstall.php' );
$checks = array(
	'v1.1.0 identity and platform evidence schema' => false !== strpos( $main, 'Version:     2.0.1' ) && false !== strpos( $main, "SC_EI_PLATFORM_SCHEMA_VERSION', '2.0.0'" ),
	'pilot operations loaded' => false !== strpos( $main, 'class-sc-ei-pilot-operations.php' ) && false !== strpos( $pilot, 'final class SC_EI_Pilot_Operations' ),
	'routed public entries' => false !== strpos( $pilot, "'ai-assurance'" ) && false !== strpos( $pilot, "'advisory'" ) && false !== strpos( $public, 'current_route()' ) && false !== strpos( $form, "'default_service'" ),
	'pilot evidence gate' => false !== strpos( $platform, "'pilot_launch_evidence'" ) && false !== strpos( $pilot, 'pilot_complete_and_fresh' ),
	'external inbox evidence gate' => false !== strpos( $platform, "'external_mail_delivery'" ) && false !== strpos( $pilot, 'external_mail_confirmed_and_fresh' ),
	'operational blocker gate' => false !== strpos( $platform, "'operational_attention'" ) && false !== strpos( $pilot, 'failed communication(s)' ) && false !== strpos( $pilot, 'infected attachment(s)' ),
	'upload runtime rejection probe' => false !== strpos( $upload, 'runtime_security_probe' ) && false !== strpos( $validation, "'upload_security_runtime'" ) && false !== strpos( $upload, "'executable_signature'" ),
	'route runtime validation' => false !== strpos( $validation, "'routed_entry_contracts'" ) && false !== strpos( $pilot, 'route_contract_evidence' ),
	'browser-session draft recovery' => false !== strpos( $js, 'sessionStorage' ) && false !== strpos( $js, 'restoreDraft()' ) && false !== strpos( $js, 'clearDraft()' ),
	'operations dashboard and evidence forms' => false !== strpos( $view, 'Public Launch Operations' ) && false !== strpos( $view, 'External Email Evidence' ) && false !== strpos( $view, 'Pilot Launch Evidence' ),
	'new evidence cleanup' => false !== strpos( $uninstall, "sc_ei_platform_pilot_evidence" ) && false !== strpos( $uninstall, "sc_ei_platform_external_mail_evidence" ),
	'v1.0.3 nondestructive journal' => false !== strpos( $platform, 'v1_0_3_pilot_findings_public_launch_hardening' ) && false !== strpos( $platform, "'database_schema_changed' => false" ),
);
$failed = array_keys( array_filter( $checks, static fn( $value ) => ! $value ) );
if ( $failed ) {
	fwrite( STDERR, 'Pilot launch hardening checks failed: ' . implode( ', ', $failed ) . PHP_EOL );
	exit( 1 );
}
foreach ( $checks as $label => $passed ) {
	echo 'PASS: ' . $label . PHP_EOL;
}
echo "Sustainable Catalyst Contact and Engagement Platform v1.0.3 pilot launch hardening checks passed.\n";
