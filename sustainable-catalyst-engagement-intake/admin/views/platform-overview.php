<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$messages = array(
	'platform_snapshot_created'       => __( 'Platform readiness snapshot created.', 'sustainable-catalyst-engagement-intake' ),
	'platform_launch_state_updated'   => __( 'Platform launch state updated by authorized staff.', 'sustainable-catalyst-engagement-intake' ),
	'platform_settings_saved'         => __( 'Unified platform settings saved.', 'sustainable-catalyst-engagement-intake' ),
	'platform_migration_verified'     => __( 'The platform migration journal was verified.', 'sustainable-catalyst-engagement-intake' ),
	'platform_repair_completed'       => __( 'The bounded readiness repair completed. Review the refreshed evidence below.', 'sustainable-catalyst-engagement-intake' ),
	'platform_live_validation_passed' => __( 'Live validation passed and current evidence was recorded.', 'sustainable-catalyst-engagement-intake' ),
	'platform_backups_attested'       => __( 'Current database and protected-storage backup evidence was recorded.', 'sustainable-catalyst-engagement-intake' ),
	'platform_external_mail_confirmed'=> __( 'External inbox delivery evidence was recorded.', 'sustainable-catalyst-engagement-intake' ),
	'platform_pilot_evidence_recorded'=> __( 'Controlled pilot and public-launch evidence was recorded.', 'sustainable-catalyst-engagement-intake' ),
);
$errors = array(
	'platform_snapshot_confirmation_failed'     => __( 'Type SNAPSHOT PLATFORM exactly.', 'sustainable-catalyst-engagement-intake' ),
	'platform_launch_confirmation_failed'       => __( 'Type the required SET PLATFORM confirmation exactly.', 'sustainable-catalyst-engagement-intake' ),
	'platform_settings_confirmation_failed'     => __( 'Type SAVE PLATFORM SETTINGS exactly.', 'sustainable-catalyst-engagement-intake' ),
	'platform_migration_confirmation_failed'    => __( 'Type VERIFY PLATFORM MIGRATION exactly.', 'sustainable-catalyst-engagement-intake' ),
	'platform_live_validation_confirmation_failed' => __( 'Type RUN LIVE VALIDATION exactly.', 'sustainable-catalyst-engagement-intake' ),
	'platform_backup_confirmation_failed'       => __( 'Type ATTEST PLATFORM BACKUPS exactly.', 'sustainable-catalyst-engagement-intake' ),
	'platform_not_ready_for_production'         => __( 'Production requires 100% readiness, no warnings, recent live validation and backups, confirmed external email delivery, completed pilot evidence, and no unresolved launch blockers.', 'sustainable-catalyst-engagement-intake' ),
	'platform_launch_note_required'             => __( 'Record why the launch state is changing.', 'sustainable-catalyst-engagement-intake' ),
	'platform_schema_incomplete'                => __( 'The platform schema is incomplete. Review Diagnostics and run the migration verification again.', 'sustainable-catalyst-engagement-intake' ),
	'platform_live_validation_failed'           => __( 'Live validation completed with one or more failures. Review the evidence panel before retrying.', 'sustainable-catalyst-engagement-intake' ),
	'platform_backup_reference_required'        => __( 'Enter a short reference for both the database backup and protected-storage backup.', 'sustainable-catalyst-engagement-intake' ),
	'platform_storage_repair_failed'            => __( 'Protected storage repair did not pass its runtime probe.', 'sustainable-catalyst-engagement-intake' ),
	'platform_cron_repair_incomplete'           => __( 'One or more required scheduled jobs or callbacks remain unavailable.', 'sustainable-catalyst-engagement-intake' ),
	'platform_repair_not_supported'             => __( 'This readiness item requires configuration or manual review rather than an automatic repair.', 'sustainable-catalyst-engagement-intake' ),
	'platform_external_mail_confirmation_failed'=> __( 'Type CONFIRM EXTERNAL MAIL exactly.', 'sustainable-catalyst-engagement-intake' ),
	'external_mail_evidence_invalid'             => __( 'Enter a monitored recipient and a useful inbox, message, or provider reference.', 'sustainable-catalyst-engagement-intake' ),
	'platform_pilot_evidence_confirmation_failed'=> __( 'Type RECORD PILOT EVIDENCE exactly.', 'sustainable-catalyst-engagement-intake' ),
	'pilot_evidence_incomplete'                  => __( 'Complete every pilot test, record at least five controlled inquiries, and add an evidence reference.', 'sustainable-catalyst-engagement-intake' ),
);
$is_error = $message && ! isset( $messages[ $message ] );
$metrics = $summary['metrics'];
$live_validation = is_array( $readiness['live_validation'] ?? null ) ? $readiness['live_validation'] : array();
$backup_attestation = is_array( $readiness['backup_attestation'] ?? null ) ? $readiness['backup_attestation'] : array();
$auto_repairs = array( 'refresh_version', 'repair_database', 'verify_migration', 'verify_patch_migration', 'verify_launch_migration', 'repair_storage', 'repair_crons' );
?>
<div class="wrap sc-ei-admin sc-ei-platform-admin" id="sc-ei-primary-content">
	<header class="sc-ei-admin__header sc-ei-platform-admin__header">
		<div>
			<p class="sc-ei-admin__eyebrow"><?php esc_html_e( 'Sustainable Catalyst', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<h1><?php esc_html_e( 'Contact and Engagement Platform', 'sustainable-catalyst-engagement-intake' ); ?></h1>
			<p><?php esc_html_e( 'Pilot findings, public-launch operations, routed public intake, secure sender collaboration, human review, Teams scheduling, engagement handoff, analytics, reliability, privacy, and Workflow Core integration.', 'sustainable-catalyst-engagement-intake' ); ?></p>
		</div>
		<div class="sc-ei-platform-admin__release"><span>v1.0.3</span><strong><?php echo esc_html( SC_EI_Platform_Schema::label( SC_EI_Platform_Schema::launch_states(), $readiness['launch_state'] ) ); ?></strong></div>
	</header>

	<?php if ( $message ) : ?>
		<div class="notice <?php echo $is_error ? 'notice-error' : 'notice-success'; ?> is-dismissible"><p><?php echo esc_html( $messages[ $message ] ?? $errors[ $message ] ?? ucwords( str_replace( '_', ' ', $message ) ) ); ?></p></div>
	<?php endif; ?>

	<div class="sc-ei-portal-admin-boundary">
		<strong><?php esc_html_e( 'Stable human-control boundary', 'sustainable-catalyst-engagement-intake' ); ?></strong>
		<span><?php esc_html_e( 'Production status is recorded only through an authorized typed action. Readiness repairs and validation cannot accept an inquiry, determine fit, publish a proposal, record a contract, activate an engagement, provision a project, or collect payment automatically.', 'sustainable-catalyst-engagement-intake' ); ?></span>
	</div>

	<div class="sc-ei-platform-score" aria-label="<?php esc_attr_e( 'Platform readiness score', 'sustainable-catalyst-engagement-intake' ); ?>">
		<div><strong><?php echo esc_html( absint( $readiness['score'] ) ); ?>%</strong><span><?php esc_html_e( 'readiness score', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div><strong><?php echo esc_html( count( $readiness['required_failures'] ) ); ?></strong><span><?php esc_html_e( 'required failures', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div><strong><?php echo esc_html( count( $readiness['warnings'] ) ); ?></strong><span><?php esc_html_e( 'warnings', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div><strong><?php echo ! empty( $readiness['ready_for_production'] ) ? esc_html__( 'Ready', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'Not ready', 'sustainable-catalyst-engagement-intake' ); ?></strong><span><?php esc_html_e( 'production gate', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
	</div>

	<?php if ( empty( $readiness['ready_for_production'] ) ) : ?>
		<section class="sc-ei-admin__card sc-ei-admin__card--wide sc-ei-platform-repair-center" aria-labelledby="sc-ei-repair-title">
			<div class="sc-ei-card-heading-row">
				<div><h2 id="sc-ei-repair-title"><?php esc_html_e( 'Production Readiness Repair Center', 'sustainable-catalyst-engagement-intake' ); ?></h2><p><?php esc_html_e( 'Resolve every failure and warning below. Production remains unavailable until the evidence reaches 100%.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
			</div>
			<div class="sc-ei-platform-repair-list">
				<?php foreach ( $readiness['production_blockers'] as $check ) : ?>
					<article class="sc-ei-platform-repair-item sc-ei-platform-repair-item--<?php echo esc_attr( $check['status'] ); ?>">
						<div><strong><?php echo esc_html( $check['label'] ); ?></strong><p><?php echo esc_html( $check['detail'] ); ?></p></div>
						<?php if ( in_array( $check['repair'], $auto_repairs, true ) && current_user_can( 'sc_intake_manage_platform' ) ) : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<input type="hidden" name="action" value="sc_ei_platform_repair">
								<input type="hidden" name="platform_repair_key" value="<?php echo esc_attr( $check['repair'] ); ?>">
								<?php wp_nonce_field( 'sc_ei_platform_repair_' . $check['repair'] ); ?>
								<button class="button"><?php echo esc_html( $check['repair_label'] ); ?></button>
							</form>
						<?php elseif ( 'run_live_validation' === $check['repair'] ) : ?>
							<a class="button" href="#sc-ei-live-validation"><?php echo esc_html( $check['repair_label'] ); ?></a>
						<?php elseif ( 'attest_backups' === $check['repair'] ) : ?>
							<a class="button" href="#sc-ei-backup-evidence"><?php echo esc_html( $check['repair_label'] ); ?></a>
						<?php elseif ( 'confirm_external_mail' === $check['repair'] ) : ?>
							<a class="button" href="#sc-ei-external-mail-evidence"><?php echo esc_html( $check['repair_label'] ); ?></a>
						<?php elseif ( 'record_pilot_evidence' === $check['repair'] ) : ?>
							<a class="button" href="#sc-ei-pilot-evidence"><?php echo esc_html( $check['repair_label'] ); ?></a>
						<?php elseif ( 'review_operations' === $check['repair'] ) : ?>
							<a class="button" href="#sc-ei-launch-operations"><?php echo esc_html( $check['repair_label'] ); ?></a>
						<?php elseif ( 'review_routed_entries' === $check['repair'] ) : ?>
							<a class="button" href="#sc-ei-routed-entry-urls"><?php echo esc_html( $check['repair_label'] ); ?></a>
						<?php elseif ( in_array( $check['repair'], array( 'configure_pages', 'configure_settings' ), true ) ) : ?>
							<a class="button" href="#sc-ei-platform-settings"><?php echo esc_html( $check['repair_label'] ); ?></a>
						<?php elseif ( 'review_reliability' === $check['repair'] ) : ?>
							<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-reliability' ) ); ?>"><?php echo esc_html( $check['repair_label'] ); ?></a>
						<?php elseif ( 'review_workflow_core' === $check['repair'] ) : ?>
							<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-workflow-core' ) ); ?>"><?php echo esc_html( $check['repair_label'] ); ?></a>
						<?php elseif ( 'review_accessibility' === $check['repair'] ) : ?>
							<a class="button" href="#sc-ei-platform-readiness"><?php echo esc_html( $check['repair_label'] ); ?></a>
						<?php else : ?>
							<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-diagnostics' ) ); ?>"><?php esc_html_e( 'Open Diagnostics', 'sustainable-catalyst-engagement-intake' ); ?></a>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>

	<div class="sc-ei-review-metrics">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-inquiries' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['inquiries_total'] ) ); ?></strong><span><?php esc_html_e( 'private inquiries', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-review' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['review']['open_reviews'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'open reviews', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-portal' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['portal']['active'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'active portal access', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-workflow' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['workflow']['proposal_open'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'published proposals', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-engagements' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['engagement']['active'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'active engagements', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-workflow-core' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['workflow_core']['blocked_cases'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'blocked core cases', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
	</div>

	<section class="sc-ei-admin__card sc-ei-admin__card--wide" id="sc-ei-launch-operations">
		<div class="sc-ei-card-heading-row"><div><h2><?php esc_html_e( 'Public Launch Operations', 'sustainable-catalyst-engagement-intake' ); ?></h2><p><?php esc_html_e( 'A single launch view for inquiry workload, mail failures, private-file attention, portal failures, and critical reliability events.', 'sustainable-catalyst-engagement-intake' ); ?></p></div><span class="sc-ei-platform-evidence-badge sc-ei-platform-evidence-badge--<?php echo ! empty( $pilot_operations['clear'] ) ? 'pass' : 'fail'; ?>"><?php echo ! empty( $pilot_operations['clear'] ) ? esc_html__( 'Clear', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'Action needed', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div class="sc-ei-review-metrics sc-ei-platform-operations-grid">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-inquiries&status=new' ) ); ?>"><strong><?php echo esc_html( absint( $pilot_operations['inquiries']['new_count'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'new inquiries', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-communications&status=failed' ) ); ?>"><strong><?php echo esc_html( absint( $pilot_operations['communications']['failed'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'failed emails', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-quarantine' ) ); ?>"><strong><?php echo esc_html( absint( $pilot_operations['attachments']['quarantined_count'] ?? 0 ) + absint( $pilot_operations['attachments']['infected_count'] ?? 0 ) + absint( $pilot_operations['attachments']['scan_attention_count'] ?? 0 ) + absint( $pilot_operations['attachments']['storage_attention_count'] ?? 0 ) + absint( $pilot_operations['attachments']['expired_count'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'file blockers', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-portal' ) ); ?>"><strong><?php echo esc_html( absint( $pilot_operations['portal']['locked'] ?? 0 ) + absint( $pilot_operations['portal']['failed_today'] ?? 0 ) + absint( $pilot_operations['portal']['activation_rollbacks_today'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'portal blockers', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-reliability' ) ); ?>"><strong><?php echo esc_html( absint( $pilot_operations['hardening']['open_critical'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'critical events', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		</div>
		<?php if ( ! empty( $pilot_operations['blockers'] ) ) : ?><ul class="sc-ei-platform-operation-blockers"><?php foreach ( $pilot_operations['blockers'] as $blocker ) : ?><li><?php echo esc_html( $blocker ); ?></li><?php endforeach; ?></ul><?php else : ?><p class="description"><?php esc_html_e( 'No failed communications, overdue follow-ups, quarantine or file-integrity issues, portal lockouts or failures, or open critical reliability events were found.', 'sustainable-catalyst-engagement-intake' ); ?></p><?php endif; ?>
	</section>

	<div class="sc-ei-platform-layout">
		<main>
			<section class="sc-ei-admin__card sc-ei-admin__card--wide" id="sc-ei-platform-readiness">
				<div class="sc-ei-card-heading-row"><div><h2><?php esc_html_e( 'Runtime-Backed Readiness Evidence', 'sustainable-catalyst-engagement-intake' ); ?></h2><p><?php echo esc_html( sprintf( __( 'Generated %s UTC', 'sustainable-catalyst-engagement-intake' ), $readiness['generated_at'] ) ); ?></p></div><?php if ( current_user_can( 'sc_intake_export_platform' ) ) : ?><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sc_ei_platform_export' ), 'sc_ei_platform_export' ) ); ?>"><?php esc_html_e( 'Export Platform Report', 'sustainable-catalyst-engagement-intake' ); ?></a><?php endif; ?></div>
				<div class="sc-ei-platform-checks">
					<?php foreach ( SC_EI_Platform_Schema::check_groups() as $group_key => $group_label ) : ?>
						<?php $group_checks = array_values( array_filter( $readiness['checks'], static fn( array $check ): bool => $group_key === $check['group'] ) ); if ( ! $group_checks ) { continue; } ?>
						<section><h3><?php echo esc_html( $group_label ); ?></h3><?php foreach ( $group_checks as $check ) : ?><article class="sc-ei-platform-check sc-ei-platform-check--<?php echo esc_attr( $check['status'] ); ?>"><span aria-hidden="true"><?php echo 'pass' === $check['status'] ? '●' : ( 'fail' === $check['status'] ? '■' : '▲' ); ?></span><div><strong><?php echo esc_html( $check['label'] ); ?></strong><small><?php echo esc_html( $check['detail'] ); ?><?php if ( $check['required'] ) : ?> · <?php esc_html_e( 'required', 'sustainable-catalyst-engagement-intake' ); ?><?php endif; ?></small></div></article><?php endforeach; ?></section>
					<?php endforeach; ?>
				</div>
			</section>

			<section class="sc-ei-admin__card sc-ei-admin__card--wide" id="sc-ei-live-validation">
				<div class="sc-ei-card-heading-row"><div><h2><?php esc_html_e( 'Live Validation Suite', 'sustainable-catalyst-engagement-intake' ); ?></h2><p><?php esc_html_e( 'Runs temporary inquiry, status-transition, portal-token, private-file, upload-rejection, routed-entry, storage, page, cron, accessibility, and WordPress mail-transport checks. Test records and files are removed.', 'sustainable-catalyst-engagement-intake' ); ?></p></div><?php if ( $live_validation ) : ?><span class="sc-ei-platform-evidence-badge sc-ei-platform-evidence-badge--<?php echo ! empty( $live_validation['passed'] ) ? 'pass' : 'fail'; ?>"><?php echo esc_html( absint( $live_validation['score'] ?? 0 ) ); ?>%</span><?php endif; ?></div>
				<?php if ( $live_validation ) : ?>
					<p class="description"><?php echo esc_html( sprintf( __( 'Last run %1$s UTC by user %2$d. Evidence hash: %3$s', 'sustainable-catalyst-engagement-intake' ), $live_validation['completed_at'] ?? '', absint( $live_validation['run_by'] ?? 0 ), substr( (string) ( $live_validation['content_hash'] ?? '' ), 0, 16 ) ) ); ?></p>
					<div class="sc-ei-platform-validation-results">
						<?php foreach ( (array) ( $live_validation['checks'] ?? array() ) as $check ) : ?><article class="sc-ei-platform-check sc-ei-platform-check--<?php echo esc_attr( $check['status'] ); ?>"><span aria-hidden="true"><?php echo 'pass' === $check['status'] ? '●' : '■'; ?></span><div><strong><?php echo esc_html( $check['label'] ); ?></strong><small><?php echo esc_html( $check['detail'] ); ?></small></div></article><?php endforeach; ?>
					</div>
				<?php endif; ?>
				<?php if ( current_user_can( 'sc_intake_manage_platform' ) ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-settings-form sc-ei-platform-validation-form">
						<input type="hidden" name="action" value="sc_ei_platform_live_validation">
						<?php wp_nonce_field( 'sc_ei_platform_live_validation' ); ?>
						<label><span><?php esc_html_e( 'Test email recipient', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="email" name="platform_test_email" required value="<?php echo esc_attr( $settings['platform_support_email'] ); ?>"><small><?php esc_html_e( 'A plain-text transport test will be sent. WordPress acceptance does not independently prove inbox delivery.', 'sustainable-catalyst-engagement-intake' ); ?></small></label>
						<label><span><?php esc_html_e( 'Typed confirmation', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="platform_confirmation" required autocomplete="off" placeholder="RUN LIVE VALIDATION"></label>
						<button class="button button-primary"><?php esc_html_e( 'Run Live Validation', 'sustainable-catalyst-engagement-intake' ); ?></button>
					</form>
				<?php endif; ?>
			</section>

			<section class="sc-ei-admin__card sc-ei-admin__card--wide">
				<h2><?php esc_html_e( 'Platform Workspaces', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<div class="sc-ei-platform-modules">
					<?php $modules = array(
						array( 'Inquiries', 'Public contact and engagement records.', 'sc-engagement-intake-inquiries' ),
						array( 'Review', 'Administrative review, assignments, evidence, and decisions.', 'sc-engagement-intake-review' ),
						array( 'Fit Assessment', 'Human-controlled structured fit evaluation.', 'sc-engagement-intake-fit' ),
						array( 'Sender Portal', 'Secure sender authentication, messages, files, and privacy.', 'sc-engagement-intake-portal' ),
						array( 'Teams & Proposals', 'Consultation offers, Graph-backed Teams meetings, and proposals.', 'sc-engagement-intake-workflow' ),
						array( 'Engagements', 'Contracted-proposal handoff, onboarding, and lifecycle.', 'sc-engagement-intake-engagements' ),
						array( 'Workflow Core', 'Canonical projections, signed handoffs, and internal adapters.', 'sc-engagement-intake-workflow-core' ),
						array( 'Analytics', 'Aggregate funnel, timing, and workload intelligence.', 'sc-engagement-intake-analytics' ),
						array( 'Reliability', 'Health events, abuse protection, incident controls, and watchdogs.', 'sc-engagement-intake-reliability' ),
						array( 'Privacy', 'Consent, retention, legal holds, export, and erasure review.', 'sc-engagement-intake-privacy' ),
					); foreach ( $modules as $module ) : ?><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $module[2] ) ); ?>"><strong><?php echo esc_html( $module[0] ); ?></strong><span><?php echo esc_html( $module[1] ); ?></span></a><?php endforeach; ?>
				</div>
			</section>

			<section class="sc-ei-admin__card sc-ei-admin__card--wide" id="sc-ei-routed-entry-urls">
				<h2><?php esc_html_e( 'Routed Public Entry URLs', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<pre><code>[sc_contact_engagement_platform title="Contact and Engagement Platform"]</code></pre>
				<p><?php esc_html_e( 'Use one canonical Contact page and route specialized calls to action into the correct inquiry type and service selection.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				<pre><code>/contact/?engagement=advisory
/contact/?engagement=ai-assurance
/contact/?engagement=collaboration
/contact/?engagement=media
/contact/?engagement=technical
/contact/?engagement=partnership
/contact/?engagement=workshop
/contact/?engagement=monthly-advisory</code></pre>
				<details><summary><?php esc_html_e( 'Existing supported shortcodes', 'sustainable-catalyst-engagement-intake' ); ?></summary><pre><code>[sc_contact_hub mode="advanced" source="contact-page" entry_cta="contact-hub"]
[sc_engagement_inquiry mode="compact" source="advisory-page" entry_cta="discuss-an-engagement"]
[sc_sender_portal title="Secure Sender Portal"]</code></pre></details>
			</section>
		</main>

		<aside>
			<section class="sc-ei-admin__card">
				<h2><?php esc_html_e( 'Launch Governance', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<p><?php echo esc_html( sprintf( __( 'Current state: %s', 'sustainable-catalyst-engagement-intake' ), SC_EI_Platform_Schema::label( SC_EI_Platform_Schema::launch_states(), $readiness['launch_state'] ) ) ); ?></p>
				<p class="description"><?php esc_html_e( 'Production requires a 100% score, no warnings, recent passing live validation, and current backup evidence.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				<?php if ( $launch_record ) : ?><p class="description"><?php echo esc_html( sprintf( __( 'Last changed %1$s by user %2$d.', 'sustainable-catalyst-engagement-intake' ), $launch_record['changed_at'] ?? '', absint( $launch_record['changed_by'] ?? 0 ) ) ); ?></p><?php endif; ?>
				<?php if ( current_user_can( 'sc_intake_launch_platform' ) ) : ?><?php foreach ( SC_EI_Platform_Schema::launch_states() as $state => $label ) : if ( $state === $readiness['launch_state'] ) { continue; } ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-confirm-form"><input type="hidden" name="action" value="sc_ei_platform_launch_state"><input type="hidden" name="platform_launch_state" value="<?php echo esc_attr( $state ); ?>"><?php wp_nonce_field( 'sc_ei_platform_launch_state_' . $state ); ?><textarea name="platform_launch_note" rows="2" required placeholder="<?php esc_attr_e( 'Reason for state change', 'sustainable-catalyst-engagement-intake' ); ?>"></textarea><input type="text" name="platform_confirmation" required autocomplete="off" placeholder="<?php echo esc_attr( 'SET PLATFORM ' . strtoupper( $state ) ); ?>"><button class="button" <?php disabled( 'production' === $state && empty( $readiness['ready_for_production'] ) ); ?>><?php echo esc_html( sprintf( __( 'Set %s', 'sustainable-catalyst-engagement-intake' ), $label ) ); ?></button></form><?php endforeach; ?><?php endif; ?>
			</section>

			<?php if ( current_user_can( 'sc_intake_manage_platform' ) ) : ?>
				<section class="sc-ei-admin__card" id="sc-ei-external-mail-evidence">
					<h2><?php esc_html_e( 'External Email Evidence', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Record this only after the live-validation or pilot message is visibly present in the monitored external inbox.', 'sustainable-catalyst-engagement-intake' ); ?></p>
					<?php if ( $external_mail_evidence ) : ?><p class="description"><?php echo esc_html( sprintf( __( 'Confirmed %1$s UTC for %2$s. Reference: %3$s', 'sustainable-catalyst-engagement-intake' ), $external_mail_evidence['confirmed_at'] ?? '', $external_mail_evidence['recipient'] ?? '', $external_mail_evidence['reference'] ?? '' ) ); ?></p><?php endif; ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-settings-form">
						<input type="hidden" name="action" value="sc_ei_platform_external_mail"><?php wp_nonce_field( 'sc_ei_platform_external_mail' ); ?>
						<label><span><?php esc_html_e( 'Monitored recipient', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="email" name="external_mail_recipient" required value="<?php echo esc_attr( $settings['platform_support_email'] ); ?>"></label>
						<label><span><?php esc_html_e( 'Inbox or message reference', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="external_mail_reference" required placeholder="Inbox timestamp, message ID, or provider reference"></label>
						<label><span><?php esc_html_e( 'Typed confirmation', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="platform_confirmation" required autocomplete="off" placeholder="CONFIRM EXTERNAL MAIL"></label>
						<button class="button"><?php esc_html_e( 'Confirm Inbox Delivery', 'sustainable-catalyst-engagement-intake' ); ?></button>
					</form>
				</section>

				<section class="sc-ei-admin__card" id="sc-ei-pilot-evidence">
					<h2><?php esc_html_e( 'Pilot Launch Evidence', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<?php if ( $pilot_evidence ) : ?><p class="description"><?php echo esc_html( sprintf( __( 'Last recorded %1$s UTC with %2$d controlled inquiries.', 'sustainable-catalyst-engagement-intake' ), $pilot_evidence['recorded_at'] ?? '', absint( $pilot_evidence['controlled_inquiry_count'] ?? 0 ) ) ); ?></p><?php endif; ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-settings-form">
						<input type="hidden" name="action" value="sc_ei_platform_pilot_evidence"><?php wp_nonce_field( 'sc_ei_platform_pilot_evidence' ); ?>
						<label><span><?php esc_html_e( 'Controlled inquiry count', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="5" max="1000" name="pilot_evidence[controlled_inquiry_count]" required value="<?php echo esc_attr( absint( $pilot_evidence['controlled_inquiry_count'] ?? 5 ) ); ?>"></label>
						<?php $pilot_labels = array( 'general_inquiry' => 'General inquiry completed', 'advisory_inquiry' => 'Advisory route completed', 'ai_assurance_inquiry' => 'AI Assurance route completed', 'private_upload' => 'Private upload and anonymous-access denial verified', 'admin_notification' => 'Administrative notification received', 'sender_acknowledgment' => 'Sender acknowledgment received', 'portal_isolation' => 'Sender Portal isolation verified', 'mobile_browser' => 'Mobile and browser testing completed', 'rollback_verified' => 'Plugin rollback procedure verified' ); foreach ( $pilot_labels as $pilot_key => $pilot_label ) : ?><label class="sc-ei-check"><input type="checkbox" name="pilot_evidence[<?php echo esc_attr( $pilot_key ); ?>]" value="1" <?php checked( ! empty( $pilot_evidence['checks'][ $pilot_key ] ) ); ?>><span><?php echo esc_html( $pilot_label ); ?></span></label><?php endforeach; ?>
						<label><span><?php esc_html_e( 'Evidence reference', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="pilot_evidence[reference]" rows="3" required placeholder="Test references, date range, devices, browsers, and rollback archive"><?php echo esc_textarea( (string) ( $pilot_evidence['reference'] ?? '' ) ); ?></textarea></label>
						<label><span><?php esc_html_e( 'Typed confirmation', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="platform_confirmation" required autocomplete="off" placeholder="RECORD PILOT EVIDENCE"></label>
						<button class="button"><?php esc_html_e( 'Record Pilot Evidence', 'sustainable-catalyst-engagement-intake' ); ?></button>
					</form>
				</section>
			<?php endif; ?>

			<?php if ( current_user_can( 'sc_intake_manage_platform' ) ) : ?>
				<section class="sc-ei-admin__card" id="sc-ei-backup-evidence">
					<h2><?php esc_html_e( 'Backup Evidence', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<?php if ( $backup_attestation ) : ?><p class="description"><?php echo esc_html( sprintf( __( 'Last attested %1$s UTC by user %2$d.', 'sustainable-catalyst-engagement-intake' ), $backup_attestation['attested_at'] ?? '', absint( $backup_attestation['attested_by'] ?? 0 ) ) ); ?></p><?php endif; ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-settings-form">
						<input type="hidden" name="action" value="sc_ei_platform_backup_attestation">
						<?php wp_nonce_field( 'sc_ei_platform_backup_attestation' ); ?>
						<label><span><?php esc_html_e( 'Database backup reference', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="database_backup_reference" required placeholder="Host backup date or archive name"></label>
						<label><span><?php esc_html_e( 'Protected-storage backup reference', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="storage_backup_reference" required placeholder="Storage backup date or archive name"></label>
						<label><span><?php esc_html_e( 'Typed confirmation', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="platform_confirmation" required autocomplete="off" placeholder="ATTEST PLATFORM BACKUPS"></label>
						<button class="button"><?php esc_html_e( 'Record Backup Evidence', 'sustainable-catalyst-engagement-intake' ); ?></button>
					</form>
				</section>
			<?php endif; ?>

			<?php if ( current_user_can( 'sc_intake_snapshot_platform' ) ) : ?><section class="sc-ei-admin__card"><h2><?php esc_html_e( 'Readiness Baseline', 'sustainable-catalyst-engagement-intake' ); ?></h2><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-confirm-form"><input type="hidden" name="action" value="sc_ei_platform_snapshot"><?php wp_nonce_field( 'sc_ei_platform_snapshot' ); ?><input type="text" name="platform_confirmation" required autocomplete="off" placeholder="SNAPSHOT PLATFORM"><button class="button"><?php esc_html_e( 'Create Immutable Snapshot', 'sustainable-catalyst-engagement-intake' ); ?></button></form><p class="description"><?php echo esc_html( sprintf( __( '%d snapshot(s) retained.', 'sustainable-catalyst-engagement-intake' ), count( $summary['snapshots'] ) ) ); ?></p></section><?php endif; ?>

			<?php if ( current_user_can( 'sc_intake_manage_platform' ) ) : ?><section class="sc-ei-admin__card"><h2><?php esc_html_e( 'Migration Journal', 'sustainable-catalyst-engagement-intake' ); ?></h2><?php foreach ( $summary['migrations'] as $migration ) : ?><p><strong><?php echo esc_html( $migration['migration_key'] ); ?></strong><br><span><?php echo esc_html( $migration['from_version'] . ' → ' . $migration['to_version'] . ' · ' . $migration['status'] ); ?></span></p><?php endforeach; ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-confirm-form"><input type="hidden" name="action" value="sc_ei_platform_verify_migration"><?php wp_nonce_field( 'sc_ei_platform_verify_migration' ); ?><input type="text" name="platform_confirmation" required autocomplete="off" placeholder="VERIFY PLATFORM MIGRATION"><button class="button"><?php esc_html_e( 'Verify Migration', 'sustainable-catalyst-engagement-intake' ); ?></button></form></section><?php endif; ?>

			<?php if ( current_user_can( 'sc_intake_manage_platform' ) ) : ?>
				<section class="sc-ei-admin__card" id="sc-ei-platform-settings">
					<h2><?php esc_html_e( 'Platform Settings', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<p class="description"><?php esc_html_e( 'URLs must resolve to published local pages. Contact and portal pages must contain the supported shortcode.', 'sustainable-catalyst-engagement-intake' ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-settings-form">
						<input type="hidden" name="action" value="sc_ei_platform_save_settings"><?php wp_nonce_field( 'sc_ei_platform_save_settings' ); ?>
						<label class="sc-ei-check"><input type="checkbox" name="platform_settings[platform_enabled]" value="1" <?php checked( ! empty( $settings['platform_enabled'] ) ); ?>><span><?php esc_html_e( 'Enable unified platform layer', 'sustainable-catalyst-engagement-intake' ); ?></span></label>
						<label><span><?php esc_html_e( 'Display name', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="platform_settings[platform_display_name]" value="<?php echo esc_attr( $settings['platform_display_name'] ); ?>"></label>
						<label><span><?php esc_html_e( 'Support email', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="email" name="platform_settings[platform_support_email]" value="<?php echo esc_attr( $settings['platform_support_email'] ); ?>"></label>
						<label><span><?php esc_html_e( 'Contact page URL', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="url" name="platform_settings[platform_contact_page_url]" value="<?php echo esc_attr( $settings['platform_contact_page_url'] ); ?>"></label>
						<label><span><?php esc_html_e( 'Engagement page URL', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="url" name="platform_settings[platform_engagement_page_url]" value="<?php echo esc_attr( $settings['platform_engagement_page_url'] ); ?>"></label>
						<label><span><?php esc_html_e( 'Portal page URL', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="url" name="platform_settings[platform_portal_page_url]" value="<?php echo esc_attr( $settings['platform_portal_page_url'] ); ?>"></label>
						<label><span><?php esc_html_e( 'Privacy page URL', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="url" name="platform_settings[platform_privacy_page_url]" value="<?php echo esc_attr( $settings['platform_privacy_page_url'] ); ?>"></label>
						<label class="sc-ei-check"><input type="checkbox" name="platform_settings[platform_readiness_snapshot_daily]" value="1" <?php checked( ! empty( $settings['platform_readiness_snapshot_daily'] ) ); ?>><span><?php esc_html_e( 'Create a daily readiness snapshot', 'sustainable-catalyst-engagement-intake' ); ?></span></label>
						<label><span><?php esc_html_e( 'Snapshot retention days', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="30" max="3650" name="platform_settings[platform_snapshot_retention_days]" value="<?php echo esc_attr( $settings['platform_snapshot_retention_days'] ); ?>"></label>
						<label><span><?php esc_html_e( 'Typed confirmation', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="platform_confirmation" required autocomplete="off" placeholder="SAVE PLATFORM SETTINGS"></label>
						<button class="button"><?php esc_html_e( 'Save Platform Settings', 'sustainable-catalyst-engagement-intake' ); ?></button>
					</form>
				</section>
			<?php endif; ?>
		</aside>
	</div>
</div>
