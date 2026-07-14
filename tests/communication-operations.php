<?php
/**
 * Static Notifications and Communication History safety checks.
 */

$root       = dirname( __DIR__ );
$plugin     = $root . '/sustainable-catalyst-engagement-intake';
$main       = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$database   = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$capabilities = file_get_contents( $plugin . '/includes/class-sc-ei-capabilities.php' );
$schema     = file_get_contents( $plugin . '/includes/class-sc-ei-communication-schema.php' );
$templates  = file_get_contents( $plugin . '/includes/class-sc-ei-template-repository.php' );
$repository = file_get_contents( $plugin . '/includes/class-sc-ei-communication-repository.php' );
$mailer     = file_get_contents( $plugin . '/includes/class-sc-ei-mailer.php' );
$service    = file_get_contents( $plugin . '/includes/class-sc-ei-notification-service.php' );
$admin      = file_get_contents( $plugin . '/includes/class-sc-ei-communication-admin.php' );
$list       = file_get_contents( $plugin . '/includes/class-sc-ei-communication-list-table.php' );
$overview   = file_get_contents( $plugin . '/admin/views/communications.php' );
$thread     = file_get_contents( $plugin . '/admin/views/communication-thread.php' );
$settings   = file_get_contents( $plugin . '/includes/class-sc-ei-admin.php' );
$privacy    = file_get_contents( $plugin . '/includes/class-sc-ei-privacy.php' );
$engine     = file_get_contents( $plugin . '/includes/class-sc-ei-retention-engine.php' );
$diagnostics= file_get_contents( $plugin . '/includes/class-sc-ei-diagnostics.php' );
$rest       = file_get_contents( $plugin . '/includes/class-sc-ei-rest.php' );
$javascript = file_get_contents( $plugin . '/assets/js/admin.js' );

$checks = array(
	'communication schema loaded'          => strpos( $main, 'class-sc-ei-communication-schema.php' ) !== false,
	'communication repositories loaded'    => strpos( $main, 'class-sc-ei-communication-repository.php' ) !== false && strpos( $main, 'class-sc-ei-template-repository.php' ) !== false,
	'mailer and notification service loaded'=> strpos( $main, 'class-sc-ei-mailer.php' ) !== false && strpos( $main, 'class-sc-ei-notification-service.php' ) !== false,
	'communications table'                 => strpos( $database, '$sql_communications') !== false && strpos( $database, 'dbDelta( $sql_communications )') !== false,
	'delivery events table'                => strpos( $database, '$sql_communication_events') !== false,
	'versioned templates table'            => strpos( $database, '$sql_communication_templates') !== false,
	'inquiry communication state'          => strpos( $database, 'communication_status') !== false && strpos( $database, 'next_follow_up_at') !== false && strpos( $database, 'do_not_email') !== false,
	'communication schema check'            => strpos( $database, 'communication_columns_exist') !== false,
	'accepted is transport state'           => strpos( $schema, 'Accepted by Mail Transport') !== false,
	'no delivery overclaim'                 => strpos( $mailer, 'accepted_by_mail_transport_not_proven_delivered') !== false,
	'plain text only'                       => strpos( $mailer, 'Content-Type: text/plain') !== false,
	'no attachment send API'                => strpos( $mailer . $admin, 'attachments') === false && strpos( $mailer . $admin, 'wp_mail_attachment') === false,
	'message header safety'                 => strpos( $schema, "preg_replace( '/[\\r\\n]+/'") !== false,
	'draft optimistic locking'              => strpos( $repository, 'expected_version') !== false && strpos( $repository, 'communication_conflict') !== false,
	'sent records immutable'                => strpos( $repository, 'communication_immutable') !== false,
	'immutable delivery events'             => strpos( $repository, 'communication_events') !== false && strpos( $repository, 'record_event') !== false,
	'notification dedupe key'               => strpos( $repository, 'dedupe_key') !== false && strpos( $service, 'internal-review-due-') !== false,
	'mail send lock'                        => strpos( $mailer, 'sc_ei_mail_lock_') !== false,
	'mail failure capture'                  => strpos( $mailer, 'wp_mail_failed') !== false,
	'do not email enforcement'              => strpos( $mailer, 'communication_suppressed') !== false && strpos( $mailer, 'do_not_email') !== false,
	'separate reviewed send confirmation'   => strpos( $admin, 'confirm_send') !== false && strpos( $thread, 'I reviewed the recipient') !== false,
	'save does not send boundary'           => strpos( $thread, 'Saving does not send') !== false,
	'manual external interaction log'       => strpos( $repository, 'record_interaction') !== false && strpos( $thread, 'Record an External Interaction') !== false,
	'Teams interactions are records'        => strpos( $schema, 'teams_message') !== false && strpos( $schema, 'teams_meeting') !== false,
	'no Zoom or Google Meet channel'         => strpos( $schema, "'zoom'") === false && strpos( $schema, "'google_meet'") === false,
	'versioned template transaction'        => strpos( $templates, 'START TRANSACTION') !== false && strpos( $templates, 'template_version') !== false,
	'template variable allowlist'           => strpos( $templates, 'unknown_variables') !== false,
	'automations default off'               => preg_match( "/'sender_acknowledgment_enabled'\s*=>\s*0/", $settings ) === 1
		&& preg_match( "/'review_due_reminders_enabled'\s*=>\s*0/", $settings ) === 1,
	'automation sender readiness gate'      => strpos( $settings, 'communication_sender_required') !== false,
	'notification cron lock'                => strpos( $service, 'sc_ei_notification_cron_lock') !== false,
	'notification cron scheduled'           => strpos( $service, 'wp_schedule_event') !== false,
	'sender acknowledgment opt in'          => strpos( $service, 'sender_acknowledgment_enabled') !== false,
	'internal due and follow-up reminders'  => strpos( $service, 'run_review_due_reminders') !== false && strpos( $service, 'run_follow_up_reminders') !== false,
	'escalation notification hook'          => strpos( $service, 'review_saved') !== false && strpos( $service, 'internal_escalation') !== false,
	'cross inquiry communication queue'     => strpos( $repository, 'public static function query') !== false && strpos( $list, 'Open thread') !== false,
	'communication metrics'                 => strpos( $repository, 'public static function metrics') !== false,
	'follow up and suppression controls'    => strpos( $thread, 'Next follow-up') !== false && strpos( $thread, 'Suppress email to the sender') !== false,
	'CSV formula neutralization'            => strpos( $admin, "/^[=+\\-@]/") !== false,
	'CSV explicit escape'                   => strpos( $admin, "fputcsv(") !== false && strpos( $admin, "\n\t\t\t''\n") !== false,
	'privacy communication export'          => strpos( $privacy, 'Engagement Intake Communications') !== false,
	'privacy communication erasure'         => strpos( $engine, "SET subject = %s") !== false
		&& strpos( $engine, "context_json = %s") !== false
		&& strpos( $privacy, 'queue-only eraser bridge') !== false,
	'diagnostics communication readiness'   => strpos( $diagnostics, 'communication_columns') !== false && strpos( $diagnostics, 'automation_enabled') !== false,
	'REST capability boundary'              => strpos( $rest, "current_user_can( 'sc_intake_view_communications' )") !== false,
	'granular communication capabilities'   => strpos( $capabilities, 'sc_intake_send_communications') !== false && strpos( $capabilities, 'sc_intake_manage_notifications') !== false,
	'template browser replacement warning'  => strpos( $javascript, 'Replace the current subject and message') !== false,
	'compose unsaved warning'               => strpos( $javascript, 'beforeunload') !== false,
	'communication policy UI'               => strpos( $overview, 'Notification Policy and Transport Readiness') !== false,
	'transport test truthfulness'           => strpos( $service, 'does not independently prove inbox delivery') !== false,
	'no automatic inquiry status changes'   => strpos( $service . $mailer, 'SC_EI_Inquiry_Repository::update_status' ) === false && strpos( $service . $mailer, 'SC_EI_Review_Repository::save_review' ) === false,
);

$failed = array_keys( array_filter( $checks, static fn( bool $passed ): bool => ! $passed ) );
if ( $failed ) {
	fwrite( STDERR, 'Communication operation checks failed: ' . implode( ', ', $failed ) . PHP_EOL );
	exit( 1 );
}
foreach ( $checks as $label => $passed ) {
	echo 'PASS: ' . $label . PHP_EOL;
}
echo "Engagement Intake v0.9.2 communication operation checks passed.\n";
