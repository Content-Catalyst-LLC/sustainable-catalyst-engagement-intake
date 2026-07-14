<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$messages = array(
	'platform_snapshot_created'       => __( 'Platform readiness snapshot created.', 'sustainable-catalyst-engagement-intake' ),
	'platform_launch_state_updated'   => __( 'Platform launch state updated by authorized staff.', 'sustainable-catalyst-engagement-intake' ),
	'platform_settings_saved'         => __( 'Unified platform settings saved.', 'sustainable-catalyst-engagement-intake' ),
	'platform_migration_verified'     => __( 'The v1.0 platform migration journal was verified.', 'sustainable-catalyst-engagement-intake' ),
);
$errors = array(
	'platform_snapshot_confirmation_failed' => __( 'Type SNAPSHOT PLATFORM exactly.', 'sustainable-catalyst-engagement-intake' ),
	'platform_launch_confirmation_failed'   => __( 'Type the required SET PLATFORM confirmation exactly.', 'sustainable-catalyst-engagement-intake' ),
	'platform_settings_confirmation_failed' => __( 'Type SAVE PLATFORM SETTINGS exactly.', 'sustainable-catalyst-engagement-intake' ),
	'platform_migration_confirmation_failed'=> __( 'Type VERIFY PLATFORM MIGRATION exactly.', 'sustainable-catalyst-engagement-intake' ),
	'platform_not_ready_for_production'     => __( 'Required readiness checks must pass before Production can be recorded.', 'sustainable-catalyst-engagement-intake' ),
	'platform_launch_note_required'         => __( 'Record why the launch state is changing.', 'sustainable-catalyst-engagement-intake' ),
	'platform_schema_incomplete'            => __( 'The platform schema is incomplete. Review Diagnostics and run the migration verification again.', 'sustainable-catalyst-engagement-intake' ),
);
$is_error = $message && ! isset( $messages[ $message ] );
$metrics = $summary['metrics'];
?>
<div class="wrap sc-ei-admin sc-ei-platform-admin" id="sc-ei-primary-content">
	<header class="sc-ei-admin__header sc-ei-platform-admin__header">
		<div>
			<p class="sc-ei-admin__eyebrow"><?php esc_html_e( 'Sustainable Catalyst', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<h1><?php esc_html_e( 'Unified Contact and Engagement Platform', 'sustainable-catalyst-engagement-intake' ); ?></h1>
			<p><?php esc_html_e( 'One governed operating layer for public contact, engagement intake, secure sender collaboration, review, fit, Teams scheduling, proposals, engagement handoff, analytics, reliability, privacy, and cross-plugin Workflow Core integration.', 'sustainable-catalyst-engagement-intake' ); ?></p>
		</div>
		<div class="sc-ei-platform-admin__release"><span>v1.0.1</span><strong><?php echo esc_html( SC_EI_Platform_Schema::label( SC_EI_Platform_Schema::launch_states(), $readiness['launch_state'] ) ); ?></strong></div>
	</header>

	<?php if ( $message ) : ?><div class="notice <?php echo $is_error ? 'notice-error' : 'notice-success'; ?> is-dismissible"><p><?php echo esc_html( $messages[ $message ] ?? $errors[ $message ] ?? ucwords( str_replace( '_', ' ', $message ) ) ); ?></p></div><?php endif; ?>

	<div class="sc-ei-portal-admin-boundary">
		<strong><?php esc_html_e( 'Stable human-control boundary', 'sustainable-catalyst-engagement-intake' ); ?></strong>
		<span><?php esc_html_e( 'Platform readiness can be measured and snapshotted, but Production status is recorded only through an authorized typed action. The platform cannot accept an inquiry, determine fit, publish a proposal, record a contract, activate an engagement, provision a project, or collect payment automatically.', 'sustainable-catalyst-engagement-intake' ); ?></span>
	</div>

	<div class="sc-ei-platform-score" aria-label="<?php esc_attr_e( 'Platform readiness score', 'sustainable-catalyst-engagement-intake' ); ?>">
		<div><strong><?php echo esc_html( absint( $readiness['score'] ) ); ?>%</strong><span><?php esc_html_e( 'readiness score', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div><strong><?php echo esc_html( count( $readiness['required_failures'] ) ); ?></strong><span><?php esc_html_e( 'required failures', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div><strong><?php echo esc_html( count( $readiness['warnings'] ) ); ?></strong><span><?php esc_html_e( 'warnings', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div><strong><?php echo ! empty( $readiness['ready_for_production'] ) ? esc_html__( 'Ready', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'Not ready', 'sustainable-catalyst-engagement-intake' ); ?></strong><span><?php esc_html_e( 'production gate', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
	</div>

	<div class="sc-ei-review-metrics">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-inquiries' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['inquiries_total'] ) ); ?></strong><span><?php esc_html_e( 'private inquiries', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-review' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['review']['open_reviews'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'open reviews', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-portal' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['portal']['active'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'active portal access', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-workflow' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['workflow']['proposal_open'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'published proposals', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-engagements' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['engagement']['active'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'active engagements', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-workflow-core' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['workflow_core']['blocked_cases'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'blocked core cases', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
	</div>

	<div class="sc-ei-platform-layout">
		<main>
			<section class="sc-ei-admin__card sc-ei-admin__card--wide">
				<div class="sc-ei-card-heading-row"><div><h2><?php esc_html_e( 'Platform Readiness', 'sustainable-catalyst-engagement-intake' ); ?></h2><p><?php echo esc_html( sprintf( __( 'Generated %s UTC', 'sustainable-catalyst-engagement-intake' ), $readiness['generated_at'] ) ); ?></p></div><?php if ( current_user_can( 'sc_intake_export_platform' ) ) : ?><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sc_ei_platform_export' ), 'sc_ei_platform_export' ) ); ?>"><?php esc_html_e( 'Export Platform Report', 'sustainable-catalyst-engagement-intake' ); ?></a><?php endif; ?></div>
				<div class="sc-ei-platform-checks">
					<?php foreach ( SC_EI_Platform_Schema::check_groups() as $group_key => $group_label ) : ?>
						<?php $group_checks = array_values( array_filter( $readiness['checks'], static fn( array $check ): bool => $group_key === $check['group'] ) ); if ( ! $group_checks ) { continue; } ?>
						<section><h3><?php echo esc_html( $group_label ); ?></h3><?php foreach ( $group_checks as $check ) : ?><article class="sc-ei-platform-check sc-ei-platform-check--<?php echo esc_attr( $check['status'] ); ?>"><span aria-hidden="true"><?php echo 'pass' === $check['status'] ? '●' : ( 'fail' === $check['status'] ? '■' : '▲' ); ?></span><div><strong><?php echo esc_html( $check['label'] ); ?></strong><small><?php echo esc_html( $check['detail'] ); ?><?php if ( $check['required'] ) : ?> · <?php esc_html_e( 'required', 'sustainable-catalyst-engagement-intake' ); ?><?php endif; ?></small></div></article><?php endforeach; ?></section>
					<?php endforeach; ?>
				</div>
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

			<section class="sc-ei-admin__card sc-ei-admin__card--wide">
				<h2><?php esc_html_e( 'Recommended Public Entry', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<pre><code>[sc_contact_engagement_platform title="Contact and Engagement Platform"]</code></pre>
				<p><?php esc_html_e( 'This unified entry routes new requests and secure returning senders while preserving separate private workflows. Existing shortcodes remain supported.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				<details><summary><?php esc_html_e( 'Existing supported shortcodes', 'sustainable-catalyst-engagement-intake' ); ?></summary><pre><code>[sc_contact_hub mode="advanced" source="contact-page" entry_cta="contact-hub"]
[sc_engagement_inquiry mode="compact" source="advisory-page" entry_cta="discuss-an-engagement"]
[sc_sender_portal title="Secure Sender Portal"]</code></pre></details>
			</section>
		</main>

		<aside>
			<section class="sc-ei-admin__card">
				<h2><?php esc_html_e( 'Launch Governance', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<p><?php echo esc_html( sprintf( __( 'Current state: %s', 'sustainable-catalyst-engagement-intake' ), SC_EI_Platform_Schema::label( SC_EI_Platform_Schema::launch_states(), $readiness['launch_state'] ) ) ); ?></p>
				<?php if ( $launch_record ) : ?><p class="description"><?php echo esc_html( sprintf( __( 'Last changed %1$s by user %2$d.', 'sustainable-catalyst-engagement-intake' ), $launch_record['changed_at'] ?? '', absint( $launch_record['changed_by'] ?? 0 ) ) ); ?></p><?php endif; ?>
				<?php if ( current_user_can( 'sc_intake_launch_platform' ) ) : ?><?php foreach ( SC_EI_Platform_Schema::launch_states() as $state => $label ) : if ( $state === $readiness['launch_state'] ) { continue; } ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-confirm-form"><input type="hidden" name="action" value="sc_ei_platform_launch_state"><input type="hidden" name="platform_launch_state" value="<?php echo esc_attr( $state ); ?>"><?php wp_nonce_field( 'sc_ei_platform_launch_state_' . $state ); ?><textarea name="platform_launch_note" rows="2" required placeholder="<?php esc_attr_e( 'Reason for state change', 'sustainable-catalyst-engagement-intake' ); ?>"></textarea><input type="text" name="platform_confirmation" required autocomplete="off" placeholder="<?php echo esc_attr( 'SET PLATFORM ' . strtoupper( $state ) ); ?>"><button class="button" <?php disabled( 'production' === $state && empty( $readiness['ready_for_production'] ) ); ?>><?php echo esc_html( sprintf( __( 'Set %s', 'sustainable-catalyst-engagement-intake' ), $label ) ); ?></button></form><?php endforeach; ?><?php endif; ?>
			</section>

			<?php if ( current_user_can( 'sc_intake_snapshot_platform' ) ) : ?><section class="sc-ei-admin__card"><h2><?php esc_html_e( 'Readiness Baseline', 'sustainable-catalyst-engagement-intake' ); ?></h2><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-confirm-form"><input type="hidden" name="action" value="sc_ei_platform_snapshot"><?php wp_nonce_field( 'sc_ei_platform_snapshot' ); ?><input type="text" name="platform_confirmation" required autocomplete="off" placeholder="SNAPSHOT PLATFORM"><button class="button"><?php esc_html_e( 'Create Immutable Snapshot', 'sustainable-catalyst-engagement-intake' ); ?></button></form><p class="description"><?php echo esc_html( sprintf( __( '%d snapshot(s) retained.', 'sustainable-catalyst-engagement-intake' ), count( $summary['snapshots'] ) ) ); ?></p></section><?php endif; ?>

			<?php if ( current_user_can( 'sc_intake_manage_platform' ) ) : ?><section class="sc-ei-admin__card"><h2><?php esc_html_e( 'Migration Journal', 'sustainable-catalyst-engagement-intake' ); ?></h2><?php foreach ( $summary['migrations'] as $migration ) : ?><p><strong><?php echo esc_html( $migration['migration_key'] ); ?></strong><br><span><?php echo esc_html( $migration['from_version'] . ' → ' . $migration['to_version'] . ' · ' . $migration['status'] ); ?></span></p><?php endforeach; ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-confirm-form"><input type="hidden" name="action" value="sc_ei_platform_verify_migration"><?php wp_nonce_field( 'sc_ei_platform_verify_migration' ); ?><input type="text" name="platform_confirmation" required autocomplete="off" placeholder="VERIFY PLATFORM MIGRATION"><button class="button"><?php esc_html_e( 'Verify Migration', 'sustainable-catalyst-engagement-intake' ); ?></button></form></section><?php endif; ?>

			<?php if ( current_user_can( 'sc_intake_manage_platform' ) ) : ?><section class="sc-ei-admin__card"><h2><?php esc_html_e( 'Platform Settings', 'sustainable-catalyst-engagement-intake' ); ?></h2><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-settings-form"><input type="hidden" name="action" value="sc_ei_platform_save_settings"><?php wp_nonce_field( 'sc_ei_platform_save_settings' ); ?><label class="sc-ei-check"><input type="checkbox" name="platform_settings[platform_enabled]" value="1" <?php checked( ! empty( $settings['platform_enabled'] ) ); ?>><span><?php esc_html_e( 'Enable unified platform layer', 'sustainable-catalyst-engagement-intake' ); ?></span></label><label><span><?php esc_html_e( 'Display name', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="platform_settings[platform_display_name]" value="<?php echo esc_attr( $settings['platform_display_name'] ); ?>"></label><label><span><?php esc_html_e( 'Support email', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="email" name="platform_settings[platform_support_email]" value="<?php echo esc_attr( $settings['platform_support_email'] ); ?>"></label><label><span><?php esc_html_e( 'Contact page URL', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="url" name="platform_settings[platform_contact_page_url]" value="<?php echo esc_attr( $settings['platform_contact_page_url'] ); ?>"></label><label><span><?php esc_html_e( 'Engagement page URL', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="url" name="platform_settings[platform_engagement_page_url]" value="<?php echo esc_attr( $settings['platform_engagement_page_url'] ); ?>"></label><label><span><?php esc_html_e( 'Portal page URL', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="url" name="platform_settings[platform_portal_page_url]" value="<?php echo esc_attr( $settings['platform_portal_page_url'] ); ?>"></label><label><span><?php esc_html_e( 'Privacy page URL', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="url" name="platform_settings[platform_privacy_page_url]" value="<?php echo esc_attr( $settings['platform_privacy_page_url'] ); ?>"></label><label class="sc-ei-check"><input type="checkbox" name="platform_settings[platform_readiness_snapshot_daily]" value="1" <?php checked( ! empty( $settings['platform_readiness_snapshot_daily'] ) ); ?>><span><?php esc_html_e( 'Create a daily readiness snapshot', 'sustainable-catalyst-engagement-intake' ); ?></span></label><label><span><?php esc_html_e( 'Snapshot retention days', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="30" max="3650" name="platform_settings[platform_snapshot_retention_days]" value="<?php echo esc_attr( $settings['platform_snapshot_retention_days'] ); ?>"></label><label><span><?php esc_html_e( 'Typed confirmation', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="platform_confirmation" required autocomplete="off" placeholder="SAVE PLATFORM SETTINGS"></label><button class="button"><?php esc_html_e( 'Save Platform Settings', 'sustainable-catalyst-engagement-intake' ); ?></button></form></section><?php endif; ?>
		</aside>
	</div>
</div>
