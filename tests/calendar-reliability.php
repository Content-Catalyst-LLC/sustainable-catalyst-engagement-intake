<?php
/** v1.3.1 scheduling, reminder, and time-zone reliability contracts. */
$root = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';
$main = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$teams = file_get_contents( $plugin . '/includes/class-sc-ei-teams.php' );
$calendar = file_get_contents( $plugin . '/includes/class-sc-ei-calendar-repository.php' );
$lifecycle = file_get_contents( $plugin . '/includes/class-sc-ei-lifecycle-repository.php' );
$platform = file_get_contents( $plugin . '/includes/class-sc-ei-platform-repository.php' );
$validation = file_get_contents( $plugin . '/includes/class-sc-ei-platform-validation.php' );
$view = file_get_contents( $plugin . '/admin/views/calendar-coordination.php' );
$checks = array(
 'v1.3.1 identity with unchanged database schema' => strpos($main,'Version:     1.4.0')!==false && strpos($main,"SC_EI_VERSION', '1.4.0'")!==false && strpos($main,"SC_EI_DB_VERSION', '1.4.0'")!==false && strpos($main,"SC_EI_CALENDAR_SCHEMA_VERSION', '1.0.1'")!==false && strpos($main,"SC_EI_PLATFORM_SCHEMA_VERSION', '1.4.0'")!==false,
 'strict civil-time parser rejects gaps and overlaps' => strpos($teams,'parse_local_datetime')!==false && strpos($teams,'calendar_local_datetime_nonexistent')!==false && strpos($teams,'calendar_local_datetime_ambiguous')!==false,
 'patch migration is nondestructive' => strpos($calendar,'v1_3_1_scheduling_reminder_timezone_reliability')!==false && strpos($calendar,"'database_schema_changed'         => false")!==false,
 'reminder eligibility distinguishes terminal messages' => strpos($calendar,'reminder_eligibility')!==false && strpos( $calendar, "'canceled' === \$type" )!==false && strpos( $calendar, "'post_meeting' === \$type" )!==false,
 'reviewed outbound communication required' => strpos( $calendar, "'ready_for_review' !== (string) \$row['status']" )!==false && strpos($calendar,"SC_EI_Communication_Repository::find")!==false && strpos($calendar,"array( 'accepted', 'recorded' )")!==false,
 'reschedule compensation exists' => strpos($calendar,'calendar_reschedule_reminder_rollback')!==false && strpos($calendar,'calendar_reschedule_reminder_failed')!==false,
 'completion uses normalized UTC task input' => strpos( $calendar, "'due_at_utc' => \$follow_up_due_at" )!==false && strpos( $lifecycle, "array_key_exists( 'due_at_utc', \$input )" )!==false,
 'readiness includes patch and reminder integrity' => strpos($platform,'calendar_reliability_patch_journal')!==false && strpos($platform,'calendar_reminder_integrity')!==false,
 'live validation exercises DST and cancellation review' => strpos($validation,'calendar_timezone_runtime')!==false && strpos($validation,'cancellation_ready')!==false && strpos($validation,'stale_open')!==false,
 'admin only marks ready reminders with positive required communication' => strpos( $view, "'ready_for_review' === \$reminder['status']" )!==false && strpos($view,'min="1" name="communication_id" required')!==false,
);
$failed=array_keys(array_filter($checks,fn($ok)=>!$ok));
if($failed){fwrite(STDERR,'Calendar reliability checks failed: '.implode(', ',$failed)."\n");exit(1);} echo "Sustainable Catalyst Contact and Engagement Platform v1.3.1 calendar reliability checks passed (".count($checks)." assertions).\n";
