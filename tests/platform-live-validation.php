<?php
/**
 * v1.0.3 production readiness and live-validation contracts.
 */
$root   = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';
$main   = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$repo   = file_get_contents( $plugin . '/includes/class-sc-ei-platform-repository.php' );
$valid  = file_get_contents( $plugin . '/includes/class-sc-ei-platform-validation.php' );
$form   = file_get_contents( $plugin . '/includes/class-sc-ei-form-handler.php' );
$admin  = file_get_contents( $plugin . '/includes/class-sc-ei-platform-admin.php' );
$view   = file_get_contents( $plugin . '/admin/views/platform-overview.php' );
$uninstall = file_get_contents( $plugin . '/uninstall.php' );

$checks = array(
	'v1.1.1 identity and evidence schema' => false !== strpos( $main, 'Version:     1.1.1' )
		&& false !== strpos( $main, "SC_EI_PLATFORM_SCHEMA_VERSION', '1.1.1'" )
		&& false !== strpos( $main, 'class-sc-ei-platform-validation.php' ),
	'nondestructive patch migration' => false !== strpos( $repo, "PATCH_MIGRATION_KEY = 'v1_0_2_production_readiness_live_validation'" )
		&& false !== strpos( $repo, 'record_patch_migration' )
		&& false !== strpos( $repo, "'destructive'   => false" ),
	'published page shortcode verification' => false !== strpos( $repo, 'url_to_postid' )
		&& false !== strpos( $repo, 'get_post' )
		&& false !== strpos( $repo, 'has_shortcode' )
		&& false !== strpos( $repo, "'sc_contact_engagement_platform'" )
		&& false !== strpos( $repo, "'sc_sender_portal'" ),
	'cron runtime and callback evidence' => false !== strpos( $repo, 'wp_next_scheduled' )
		&& false !== strpos( $repo, 'has_action' )
		&& false !== strpos( $repo, 'cron_evidence' ),
	'no hard-coded adapter or accessibility passes' => false !== strpos( $repo, 'SC_EI_Workflow_Core_Service::registered_targets()' )
		&& false !== strpos( $repo, 'accessibility_evidence()' )
		&& false !== strpos( $repo, 'SC_EI_Platform_Public::shortcode' ),
	'strict production gate' => false !== strpos( $repo, '100 === $score && empty( $required_failures ) && empty( $warnings )' )
		&& false !== strpos( $repo, "'production_blockers'" )
		&& false !== strpos( $repo, "'live_validation'" )
		&& false !== strpos( $repo, "'backup_attestation'" ),
	'guided bounded repair actions' => false !== strpos( $repo, 'public static function repair(' )
		&& false !== strpos( $repo, "'refresh_version'" )
		&& false !== strpos( $repo, "'repair_database'" )
		&& false !== strpos( $repo, "'repair_storage'" )
		&& false !== strpos( $repo, "'repair_crons'" )
		&& false !== strpos( $admin, 'admin_post_sc_ei_platform_repair' ),
	'live validation lifecycle coverage' => false !== strpos( $valid, 'SC_EI_Inquiry_Repository::create' )
		&& false !== strpos( $valid, 'SC_EI_Lifecycle_Repository::transition' )
		&& false !== strpos( $valid, 'SC_EI_Lifecycle_Repository::sender_snapshot' )
		&& false !== strpos( $valid, 'SC_EI_Portal_Repository::issue_invitation' )
		&& false !== strpos( $valid, 'SC_EI_Storage::store_uploaded_file_verified' )
		&& false !== strpos( $valid, 'SC_EI_Notification_Service::test_notification' )
		&& false !== strpos( $valid, "'test_cleanup'" ),
	'duplicate and request-lock validation' => false !== strpos( $form, 'public static function validation_duplicate_controls(): array' )
		&& false !== strpos( $form, 'self::duplicate_key' )
		&& false !== strpos( $form, 'self::acquire_request_lock' )
		&& false !== strpos( $valid, "'duplicate_controls'" ),
	'typed validation and backup controls' => false !== strpos( $admin, "'RUN LIVE VALIDATION'" )
		&& false !== strpos( $admin, "'ATTEST PLATFORM BACKUPS'" )
		&& false !== strpos( $view, 'RUN LIVE VALIDATION' )
		&& false !== strpos( $view, 'ATTEST PLATFORM BACKUPS' ),
	'validation evidence cleanup on uninstall' => false !== strpos( $uninstall, "sc_ei_platform_live_validation" )
		&& false !== strpos( $uninstall, "sc_ei_platform_backup_attestation" ),
	'live validation remains administrator initiated' => false === strpos( $valid, 'add_action(' )
		&& false === strpos( $valid, 'wp_schedule_event' )
		&& false !== strpos( $admin, 'current_user_can' ),
);
$failed = array_keys( array_filter( $checks, static fn( $value ) => ! $value ) );
if ( $failed ) {
	fwrite( STDERR, 'Platform live-validation checks failed: ' . implode( ', ', $failed ) . PHP_EOL );
	exit( 1 );
}
foreach ( $checks as $name => $passed ) {
	echo 'PASS: ' . $name . PHP_EOL;
}
echo "Sustainable Catalyst Contact and Engagement Platform v1.1.1 live-validation checks passed.\n";
