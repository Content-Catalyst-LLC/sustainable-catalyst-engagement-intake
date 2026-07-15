<?php
/** v1.3.0 Microsoft Teams and Calendar Coordination contracts. */
$root = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';
$main = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$db = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$schema = file_get_contents( $plugin . '/includes/class-sc-ei-calendar-schema.php' );
$repo = file_get_contents( $plugin . '/includes/class-sc-ei-calendar-repository.php' );
$admin = file_get_contents( $plugin . '/includes/class-sc-ei-calendar-admin.php' );
$workflow = file_get_contents( $plugin . '/includes/class-sc-ei-workflow-repository.php' );
$validation = file_get_contents( $plugin . '/includes/class-sc-ei-platform-validation.php' );
$platform = file_get_contents( $plugin . '/includes/class-sc-ei-platform-repository.php' );
$portal = file_get_contents( $plugin . '/public/views/sender-portal.php' );
$uninstall = file_get_contents( $plugin . '/uninstall.php' );
$privacy = file_get_contents( $plugin . '/includes/class-sc-ei-privacy.php' );
$privacy_repository = file_get_contents( $plugin . '/includes/class-sc-ei-privacy-repository.php' );
$communications = file_get_contents( $plugin . '/includes/class-sc-ei-communication-schema.php' );
$pilot = file_get_contents( $plugin . '/includes/class-sc-ei-pilot-operations.php' );
$view = file_get_contents( $plugin . '/admin/views/calendar-coordination.php' );

$types = array( 'advisory_discovery', 'ai_assurance_review', 'support_troubleshooting', 'research_collaboration', 'institutional', 'media_interview', 'workshop_planning', 'proposal_review', 'engagement_review', 'project_closeout' );
$all_types = true;
foreach ( $types as $type ) { $all_types = $all_types && false !== strpos( $schema, "'{$type}'" ); }
$checks = array(
    'v1.3.0 identity and schema versions' => false !== strpos( $main, 'Version:     1.4.0' ) && false !== strpos( $main, "SC_EI_DB_VERSION', '1.4.0'" ) && false !== strpos( $main, "SC_EI_CALENDAR_SCHEMA_VERSION', '1.0.1'" ) && false !== strpos( $main, "SC_EI_PLATFORM_SCHEMA_VERSION', '1.4.0'" ),
    'calendar module loaded and registered' => false !== strpos( $main, 'class-sc-ei-calendar-schema.php' ) && false !== strpos( $main, 'class-sc-ei-calendar-repository.php' ) && false !== strpos( $main, 'class-sc-ei-calendar-admin.php' ),
    'meeting categories cover advisory support and institutional workflows' => $all_types,
    'no public booking and no automatic reminder sending defaults' => false !== strpos( $schema, "'calendar_no_public_booking'" ) && false !== strpos( $schema, "'calendar_auto_send_reminders'        => 0" ),
    'meeting table stores coordination and rescheduling history' => false !== strpos( $db, 'meeting_type varchar(60)' ) && false !== strpos( $db, 'participant_emails_json longtext' ) && false !== strpos( $db, 'previous_start_utc datetime' ) && false !== strpos( $db, 'reschedule_count smallint(5)' ) && false !== strpos( $db, 'join_url_revoked_at datetime' ),
    'separate idempotent reminder evidence table' => false !== strpos( $db, '$sql_meeting_reminders' ) && false !== strpos( $db, 'idempotency_key char(64)' ) && false !== strpos( $db, 'UNIQUE KEY idempotency_key' ),
    'nondestructive calendar migration journal' => false !== strpos( $repo, "MIGRATION_KEY = 'v1_3_0_microsoft_teams_calendar_coordination'" ) && false !== strpos( $repo, "'destructive'                => false" ),
    'explicit Teams host timezone and participant validation' => false !== strpos( $repo, 'SC_EI_Teams::is_teams_url' ) && false !== strpos( $repo, 'SC_EI_Teams::valid_timezone' ) && false !== strpos( $repo, 'sanitize_participant_emails' ),
    'rescheduling preserves history and regenerates reminders' => false !== strpos( $repo, "'previous_start_utc'" ) && false !== strpos( $repo, "'meeting_rescheduled'" ) && false !== strpos( $repo, 'cancel_open_reminders' ) && false !== strpos( $repo, 'schedule_reminders( $meeting_id, true )' ),
    'cancellation revokes active join links' => false !== strpos( $workflow, "'teams_url'           => 'canceled' === \$status ? ''" ) && false !== strpos( $workflow, "'join_url_revoked_at'" ) && false !== strpos( $repo, "'meeting_canceled'" ),
    'reminders are records for human review not autonomous email' => false !== strpos( $repo, "'ready_for_review'" ) && false !== strpos( $repo, "do_action( 'sc_ei_calendar_reminder_due'" ) && false === strpos( $repo, 'wp_mail(' ),
    'post-meeting follow-up creates governed lifecycle task' => false !== strpos( $repo, 'SC_EI_Lifecycle_Repository::add_task' ) && false !== strpos( $repo, "'post_meeting_sender_summary'" ) && false !== strpos( $repo, "'follow_up_task_id'" ),
    'sender projection uses allowlist and excludes internal fields' => false !== strpos( $repo, 'public static function sender_snapshot' ) && false !== strpos( $repo, "'post_meeting_summary'" ) && false === strpos( $portal, 'post_meeting_internal_notes' ) && false === strpos( $portal, 'participant_emails_json' ),
    'sender portal shows approved agenda preparation and status' => false !== strpos( $portal, "'Agenda'" ) && false !== strpos( $portal, "'Preparation requested'" ) && false !== strpos( $portal, "'Meeting completed.'" ) && false !== strpos( $portal, "'Meeting canceled.'" ),
    'calendar administration requires existing capabilities and typed confirmation' => false !== strpos( $admin, "'sc_intake_finalize_meetings'" ) && false !== strpos( $admin, "'RESCHEDULE '" ) && false !== strpos( $admin, "'COMPLETE '" ) && false !== strpos( $admin, "'CANCEL '" ),
    'coordination workspace exposes agenda participants provider and reminder evidence' => false !== strpos( $view, 'participant_emails' ) && false !== strpos( $view, 'preparation_requests' ) && false !== strpos( $view, 'external_calendar_reference' ) && false !== strpos( $view, 'Reminder Evidence' ),
    'production gate checks schema migration cron and link integrity' => false !== strpos( $platform, "'calendar_columns'" ) && false !== strpos( $platform, "'calendar_migration_journal'" ) && false !== strpos( $platform, "'calendar_integrity'" ) && false !== strpos( $platform, 'SC_EI_Calendar_Repository::REMINDER_HOOK' ),
    'live validation exercises scheduling rescheduling cancellation and cleanup' => false !== strpos( $validation, "'[TEST] Teams coordination'" ) && false !== strpos( $validation, 'SC_EI_Calendar_Repository::reschedule' ) && false !== strpos( $validation, 'SC_EI_Calendar_Repository::cancel' ) && false !== strpos( $validation, "table( 'meeting_reminders' )" ) && false !== strpos( $validation, "sc-contact-engagement-live-validation/1.7" ),
    'privacy export and redaction include calendar coordination' => false !== strpos( $workflow, "'calendar_coordination'" ) && false !== strpos( $workflow, 'SC_EI_Calendar_Repository::redact_for_privacy' ) && false !== strpos( $repo, 'public static function export_for_inquiry' ) && false !== strpos( $privacy, 'sc-engagement-intake-meeting-reminders' ) && false !== strpos( $privacy_repository, "'meeting_reminders'" ),
    'reviewable calendar communication templates' => false !== strpos( $communications, "'teams_rescheduled'" ) && false !== strpos( $communications, "'teams_canceled'" ) && false !== strpos( $communications, "'teams_reminder'" ) && false !== strpos( $communications, "'teams_followup'" ),
    'calendar risks are public-launch blockers' => false !== strpos( $pilot, "['follow_up_overdue']" ) && false !== strpos( $pilot, "['graph_reconcile_due']" ) && false !== strpos( $pilot, "['missing_timezone']" ) && false !== strpos( $pilot, "['canceled_active_link']" ),
    'uninstall clears calendar schedule and options' => false !== strpos( $uninstall, 'sc_ei_calendar_process_reminders' ) && false !== strpos( $uninstall, 'sc_ei_calendar_schema_version' ) && false !== strpos( $uninstall, 'sc_ei_last_calendar_reminder_run' ),
    'calendar module performs no autonomous outbound network calls' => false === strpos( $repo, 'wp_remote_' ) && false === strpos( $repo, 'wp_mail(' ),
);
$failed = array_keys( array_filter( $checks, static fn( $passed ) => ! $passed ) );
if ( $failed ) { fwrite( STDERR, 'Calendar coordination checks failed: ' . implode( ', ', $failed ) . PHP_EOL ); exit( 1 ); }
foreach ( $checks as $label => $passed ) { echo 'PASS: ' . $label . PHP_EOL; }
echo "Sustainable Catalyst Contact and Engagement Platform v1.3.0 calendar coordination checks passed.\n";
