<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$message = isset( $_GET['sc_ei_msg'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_msg'] ) ) : '';
$tabs = array(
	'history'       => __( 'All History', 'sustainable-catalyst-engagement-intake' ),
	'drafts'        => __( 'Drafts', 'sustainable-catalyst-engagement-intake' ),
	'failed'        => __( 'Failed', 'sustainable-catalyst-engagement-intake' ),
	'inbound'       => __( 'Inbound', 'sustainable-catalyst-engagement-intake' ),
	'follow_up'     => __( 'Follow-up Due', 'sustainable-catalyst-engagement-intake' ),
	'notifications' => __( 'Notifications', 'sustainable-catalyst-engagement-intake' ),
	'templates'     => __( 'Templates', 'sustainable-catalyst-engagement-intake' ),
	'policy'        => __( 'Notification Policy', 'sustainable-catalyst-engagement-intake' ),
);
$current = array(
	'status'             => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '',
	'direction'          => isset( $_GET['direction'] ) ? sanitize_key( wp_unslash( $_GET['direction'] ) ) : '',
	'channel'            => isset( $_GET['channel'] ) ? sanitize_key( wp_unslash( $_GET['channel'] ) ) : '',
	'communication_type' => isset( $_GET['communication_type'] ) ? sanitize_key( wp_unslash( $_GET['communication_type'] ) ) : '',
	'assignee'           => isset( $_GET['assignee'] ) ? sanitize_text_field( wp_unslash( $_GET['assignee'] ) ) : '',
	's'                  => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
);
?>
<div class="wrap sc-ei-admin sc-ei-communications">
	<h1><?php esc_html_e( 'Notifications and Communication History', 'sustainable-catalyst-engagement-intake' ); ?></h1>
	<p><?php esc_html_e( 'Draft, review, send, record, and audit private inquiry communications. Automated email policies are disabled by default. An “accepted” email was accepted by the configured WordPress mail transport; inbox delivery is not independently confirmed.', 'sustainable-catalyst-engagement-intake' ); ?></p>

	<?php if ( 'communication_template_saved' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'A new immutable template version was created and made active.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'notification_test_accepted' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'The notification test was accepted by the WordPress mail transport. This is not independent confirmation of inbox delivery.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( $message ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php echo esc_html( ucwords( str_replace( '_', ' ', $message ) ) ); ?></p></div>
	<?php endif; ?>

	<div class="sc-ei-comm-metrics">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-communications&view=drafts' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['drafts'] ) ); ?></strong><span><?php esc_html_e( 'drafts', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a class="<?php echo $metrics['failed'] ? 'sc-ei-review-metric--danger' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-communications&view=failed' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['failed'] ) ); ?></strong><span><?php esc_html_e( 'failed', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-communications&status=accepted' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['accepted_today'] ) ); ?></strong><span><?php esc_html_e( 'accepted today', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-communications&view=inbound' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['inbound_today'] ) ); ?></strong><span><?php esc_html_e( 'inbound today', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a class="<?php echo $metrics['unread_inbound'] ? 'sc-ei-review-metric--attention' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-communications&view=inbound' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['unread_inbound'] ) ); ?></strong><span><?php esc_html_e( 'unread inbound', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a class="<?php echo $metrics['follow_up_due'] ? 'sc-ei-review-metric--attention' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-communications&view=follow_up' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['follow_up_due'] ) ); ?></strong><span><?php esc_html_e( 'follow-up due', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-communications&view=notifications' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['notifications_today'] ) ); ?></strong><span><?php esc_html_e( 'notifications today', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-communications&status=suppressed' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['suppressed'] ) ); ?></strong><span><?php esc_html_e( 'suppressed records', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a class="<?php echo $metrics['do_not_email'] ? 'sc-ei-review-metric--attention' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-communications' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['do_not_email'] ) ); ?></strong><span><?php esc_html_e( 'email-suppressed inquiries', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
	</div>

	<nav class="nav-tab-wrapper sc-ei-operation-tabs" aria-label="<?php esc_attr_e( 'Communication workspace views', 'sustainable-catalyst-engagement-intake' ); ?>">
		<?php foreach ( $tabs as $key => $label ) : ?>
			<a class="nav-tab <?php echo $view === $key ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'sc-engagement-intake-communications', 'view' => $key ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</nav>

	<?php if ( 'templates' === $view ) : ?>
		<section class="sc-ei-admin__card sc-ei-admin__card--wide">
			<h2><?php esc_html_e( 'Versioned Plain-Text Templates', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><?php esc_html_e( 'Saving creates a new version and archives the previous active version. Sent history retains the template key and version used. Templates cannot attach files or evaluate arbitrary code.', 'sustainable-catalyst-engagement-intake' ); ?></p>

			<div class="sc-ei-template-grid">
				<?php foreach ( $templates as $template ) : ?>
					<article class="sc-ei-template-card">
						<div class="sc-ei-review-section-header">
							<h3><?php echo esc_html( $template['name'] ); ?></h3>
							<span>v<?php echo esc_html( absint( $template['version'] ) ); ?><?php if ( $template['is_system'] ) : ?> · <?php esc_html_e( 'system', 'sustainable-catalyst-engagement-intake' ); ?><?php endif; ?></span>
						</div>
						<p><code><?php echo esc_html( $template['template_key'] ); ?></code> · <?php echo esc_html( SC_EI_Communication_Schema::label( SC_EI_Communication_Schema::types(), $template['communication_type'] ) ); ?></p>
						<p><strong><?php esc_html_e( 'Subject:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo esc_html( $template['subject_template'] ); ?></p>
						<details><summary><?php esc_html_e( 'View body', 'sustainable-catalyst-engagement-intake' ); ?></summary><pre><?php echo esc_html( $template['body_template'] ); ?></pre></details>
					</article>
				<?php endforeach; ?>
			</div>

			<?php if ( current_user_can( 'sc_intake_manage_templates' ) ) : ?>
				<hr>
				<h2><?php esc_html_e( 'Create a Template Version', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-template-form">
					<input type="hidden" name="action" value="sc_ei_save_communication_template">
					<?php wp_nonce_field( 'sc_ei_save_communication_template' ); ?>
					<div class="sc-ei-review-form-grid">
						<label><span><?php esc_html_e( 'Template key', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="template_key" pattern="[a-z0-9][a-z0-9_-]{2,79}" required placeholder="general_response"></label>
						<label><span><?php esc_html_e( 'Template name', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="template_name" maxlength="191" required></label>
						<label><span><?php esc_html_e( 'Communication type', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="template_type"><?php foreach ( SC_EI_Communication_Schema::types() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
						<label class="sc-ei-review-checkbox"><input type="checkbox" name="template_is_system" value="1"><span><?php esc_html_e( 'Internal/system template', 'sustainable-catalyst-engagement-intake' ); ?></span></label>
						<label class="sc-ei-review-form-grid__wide"><span><?php esc_html_e( 'Subject template', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="subject_template" maxlength="255" required></label>
						<label class="sc-ei-review-form-grid__wide"><span><?php esc_html_e( 'Body template', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="body_template" rows="12" maxlength="50000" required></textarea></label>
					</div>
					<p><strong><?php esc_html_e( 'Allowed variables:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo esc_html( implode( ', ', array_keys( SC_EI_Communication_Schema::template_variables() ) ) ); ?></p>
					<?php submit_button( __( 'Create New Template Version', 'sustainable-catalyst-engagement-intake' ) ); ?>
				</form>
			<?php endif; ?>
		</section>
	<?php elseif ( 'policy' === $view ) : ?>
		<section class="sc-ei-admin__card sc-ei-admin__card--wide">
			<h2><?php esc_html_e( 'Notification Policy and Transport Readiness', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<div class="sc-ei-notification-policy-grid">
				<div><strong><?php echo ! empty( $settings['sender_acknowledgment_enabled'] ) ? esc_html__( 'Enabled', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'Disabled', 'sustainable-catalyst-engagement-intake' ); ?></strong><span><?php esc_html_e( 'sender acknowledgment', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
				<div><strong><?php echo ! empty( $settings['internal_new_inquiry_enabled'] ) ? esc_html__( 'Enabled', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'Disabled', 'sustainable-catalyst-engagement-intake' ); ?></strong><span><?php esc_html_e( 'new inquiry alert', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
				<div><strong><?php echo ! empty( $settings['review_due_reminders_enabled'] ) ? esc_html__( 'Enabled', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'Disabled', 'sustainable-catalyst-engagement-intake' ); ?></strong><span><?php esc_html_e( 'review reminders', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
				<div><strong><?php echo ! empty( $settings['follow_up_reminders_enabled'] ) ? esc_html__( 'Enabled', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'Disabled', 'sustainable-catalyst-engagement-intake' ); ?></strong><span><?php esc_html_e( 'follow-up reminders', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
				<div><strong><?php echo ! empty( $settings['escalation_notifications_enabled'] ) ? esc_html__( 'Enabled', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'Disabled', 'sustainable-catalyst-engagement-intake' ); ?></strong><span><?php esc_html_e( 'escalation alerts', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
				<div><strong><?php echo esc_html( wp_next_scheduled( SC_EI_Notification_Service::CRON_HOOK ) ? __( 'Scheduled', 'sustainable-catalyst-engagement-intake' ) : __( 'Missing', 'sustainable-catalyst-engagement-intake' ) ); ?></strong><span><?php esc_html_e( 'hourly reminder cron', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
			</div>
			<dl class="sc-ei-admin__details">
				<dt><?php esc_html_e( 'Sender name', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $settings['communication_sender_name'] ?: '—' ); ?></dd>
				<dt><?php esc_html_e( 'Sender email', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $settings['communication_sender_email'] ?: '—' ); ?></dd>
				<dt><?php esc_html_e( 'Reply-to email', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $settings['communication_reply_to_email'] ?: '—' ); ?></dd>
				<dt><?php esc_html_e( 'Internal recipients', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $settings['notification_internal_recipients'] ?: __( 'Fallback: WordPress admin email', 'sustainable-catalyst-engagement-intake' ) ); ?></dd>
				<dt><?php esc_html_e( 'Escalation recipients', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $settings['notification_escalation_recipients'] ?: __( 'Fallback: internal recipients', 'sustainable-catalyst-engagement-intake' ) ); ?></dd>
				<dt><?php esc_html_e( 'Last reminder run', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( get_option( 'sc_ei_last_notification_reminder_run', __( 'Never', 'sustainable-catalyst-engagement-intake' ) ) ); ?></dd>
			</dl>
			<div class="sc-ei-diagnostic-warning"><strong><?php esc_html_e( 'Transport boundary:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'WordPress wp_mail() acceptance is not delivery, inbox placement, opening, or reading confirmation. Configure and monitor the hosting mail transport separately.', 'sustainable-catalyst-engagement-intake' ); ?></div>
			<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-settings' ) ); ?>"><?php esc_html_e( 'Configure Notification Settings', 'sustainable-catalyst-engagement-intake' ); ?></a></p>
			<?php if ( current_user_can( 'sc_intake_manage_notifications' ) ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-notification-test">
					<input type="hidden" name="action" value="sc_ei_test_notification_transport">
					<?php wp_nonce_field( 'sc_ei_test_notification_transport' ); ?>
					<label><span><?php esc_html_e( 'Test recipient', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="email" name="test_recipient" required value="<?php echo esc_attr( get_current_user_id() ? wp_get_current_user()->user_email : '' ); ?>"></label>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Send Plain-Text Transport Test', 'sustainable-catalyst-engagement-intake' ); ?></button>
				</form>
			<?php endif; ?>
		</section>
	<?php else : ?>
		<section class="sc-ei-admin__card sc-ei-admin__card--wide sc-ei-communication-history-card">
			<h2><?php echo esc_html( $tabs[ $view ] ); ?></h2>
			<form method="get" class="sc-ei-operation-filter-form">
				<input type="hidden" name="page" value="sc-engagement-intake-communications">
				<input type="hidden" name="view" value="<?php echo esc_attr( $view ); ?>">
				<input type="search" name="s" value="<?php echo esc_attr( $current['s'] ); ?>" placeholder="<?php esc_attr_e( 'Search inquiry, sender, recipient, subject, or message text', 'sustainable-catalyst-engagement-intake' ); ?>">
				<select name="status"><option value=""><?php esc_html_e( 'All states', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( SC_EI_Communication_Schema::statuses() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current['status'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
				<select name="direction"><option value=""><?php esc_html_e( 'All directions', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( SC_EI_Communication_Schema::directions() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current['direction'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
				<select name="channel"><option value=""><?php esc_html_e( 'All channels', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( SC_EI_Communication_Schema::channels() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current['channel'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
				<select name="communication_type"><option value=""><?php esc_html_e( 'All types', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( SC_EI_Communication_Schema::types() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current['communication_type'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
				<select name="assignee"><option value=""><?php esc_html_e( 'All owners', 'sustainable-catalyst-engagement-intake' ); ?></option><option value="me" <?php selected( $current['assignee'], 'me' ); ?>><?php esc_html_e( 'Assigned to me', 'sustainable-catalyst-engagement-intake' ); ?></option><option value="unassigned" <?php selected( $current['assignee'], 'unassigned' ); ?>><?php esc_html_e( 'Unassigned', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( $reviewers as $reviewer ) : ?><option value="<?php echo esc_attr( $reviewer->ID ); ?>" <?php selected( $current['assignee'], (string) $reviewer->ID ); ?>><?php echo esc_html( $reviewer->display_name ); ?></option><?php endforeach; ?></select>
				<button type="submit" class="button"><?php esc_html_e( 'Apply Filters', 'sustainable-catalyst-engagement-intake' ); ?></button>
				<a class="button-link" href="<?php echo esc_url( add_query_arg( array( 'page' => 'sc-engagement-intake-communications', 'view' => $view ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Clear', 'sustainable-catalyst-engagement-intake' ); ?></a>
			</form>
			<?php $list_table->display(); ?>
		</section>
	<?php endif; ?>
</div>
