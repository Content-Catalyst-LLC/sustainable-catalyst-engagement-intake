<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$scanner_readiness = SC_EI_Scanner_Operations::readiness( $settings );
?>
<div class="wrap sc-ei-admin">
	<h1><?php esc_html_e( 'Engagement Intake Settings', 'sustainable-catalyst-engagement-intake' ); ?></h1>
	<p><?php esc_html_e( 'v0.11.0 adds reliability monitoring, durable abuse controls, incident-safe public write pause, security headers, accessibility helpers, and recovery diagnostics while retaining engagement handoff, analytics, Microsoft Graph, Teams scheduling, versioned proposals, the Secure Sender Portal, Human-Controlled Fit Assessment, and the Privacy and Retention Center.', 'sustainable-catalyst-engagement-intake' ); ?></p>

	<form method="post" action="options.php">
		<?php settings_fields( 'sc_ei_settings_group' ); ?>

		<section class="sc-ei-admin__card sc-ei-admin__settings-card">
			<h2><?php esc_html_e( 'Privacy and retention lifecycle defaults', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><?php esc_html_e( 'These values seed and guide policy versions. Daily cron is permanently queue-only in v0.11.0; it cannot physically delete files or erase inquiry data.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="sc-ei-unaccepted-days"><?php esc_html_e( 'Unaccepted inquiries', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-unaccepted-days" type="number" min="30" max="3650" name="sc_ei_settings[default_unaccepted_retention_days]" value="<?php echo esc_attr( $settings['default_unaccepted_retention_days'] ); ?>"> <?php esc_html_e( 'days', 'sustainable-catalyst-engagement-intake' ); ?></td></tr>
				<tr><th scope="row"><label for="sc-ei-withdrawn-days"><?php esc_html_e( 'Withdrawn inquiries', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-withdrawn-days" type="number" min="1" max="3650" name="sc_ei_settings[withdrawn_retention_days]" value="<?php echo esc_attr( $settings['withdrawn_retention_days'] ); ?>"> <?php esc_html_e( 'days', 'sustainable-catalyst-engagement-intake' ); ?></td></tr>
				<tr><th scope="row"><label for="sc-ei-closed-days"><?php esc_html_e( 'Closed, referred, or not-a-fit inquiries', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-closed-days" type="number" min="30" max="3650" name="sc_ei_settings[closed_retention_days]" value="<?php echo esc_attr( $settings['closed_retention_days'] ); ?>"> <?php esc_html_e( 'days', 'sustainable-catalyst-engagement-intake' ); ?></td></tr>
				<tr><th scope="row"><label for="sc-ei-accepted-days"><?php esc_html_e( 'Accepted engagement records', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-accepted-days" type="number" min="365" max="36500" name="sc_ei_settings[accepted_retention_days]" value="<?php echo esc_attr( $settings['accepted_retention_days'] ); ?>"> <?php esc_html_e( 'days', 'sustainable-catalyst-engagement-intake' ); ?><p class="description"><?php esc_html_e( 'The seeded accepted-engagement policy archives by default rather than erasing.', 'sustainable-catalyst-engagement-intake' ); ?></p></td></tr>
				<tr><th scope="row"><label for="sc-ei-communication-days"><?php esc_html_e( 'Communication content', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-communication-days" type="number" min="30" max="36500" name="sc_ei_settings[communication_retention_days]" value="<?php echo esc_attr( $settings['communication_retention_days'] ); ?>"> <?php esc_html_e( 'days', 'sustainable-catalyst-engagement-intake' ); ?></td></tr>
				<tr><th scope="row"><label for="sc-ei-attachment-retention-top"><?php esc_html_e( 'Private documents', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-attachment-retention-top" type="number" min="7" max="3650" name="sc_ei_settings[attachment_retention_days]" value="<?php echo esc_attr( $settings['attachment_retention_days'] ); ?>"> <?php esc_html_e( 'days', 'sustainable-catalyst-engagement-intake' ); ?></td></tr>
				<tr><th scope="row"><label for="sc-ei-privacy-request-days"><?php esc_html_e( 'Privacy request target', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-privacy-request-days" type="number" min="1" max="365" name="sc_ei_settings[privacy_request_due_days]" value="<?php echo esc_attr( $settings['privacy_request_due_days'] ); ?>"> <?php esc_html_e( 'days', 'sustainable-catalyst-engagement-intake' ); ?><p class="description"><?php esc_html_e( 'Operational target only; review the deadline that actually applies.', 'sustainable-catalyst-engagement-intake' ); ?></p></td></tr>
				<tr><th scope="row"><label for="sc-ei-hold-review-days"><?php esc_html_e( 'Legal hold review interval', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-hold-review-days" type="number" min="1" max="3650" name="sc_ei_settings[legal_hold_review_days]" value="<?php echo esc_attr( $settings['legal_hold_review_days'] ); ?>"> <?php esc_html_e( 'days', 'sustainable-catalyst-engagement-intake' ); ?></td></tr>
				<tr><th scope="row"><label for="sc-ei-retention-queue-limit"><?php esc_html_e( 'Candidate queue batch', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-retention-queue-limit" type="number" min="1" max="1000" name="sc_ei_settings[retention_queue_batch_limit]" value="<?php echo esc_attr( $settings['retention_queue_batch_limit'] ); ?>"> <?php esc_html_e( 'candidates per scan', 'sustainable-catalyst-engagement-intake' ); ?></td></tr>
				<tr><th scope="row"><label for="sc-ei-retention-execution-limit"><?php esc_html_e( 'Execution batch ceiling', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-retention-execution-limit" type="number" min="1" max="50" name="sc_ei_settings[retention_execution_batch_limit]" value="<?php echo esc_attr( $settings['retention_execution_batch_limit'] ); ?>"> <?php esc_html_e( 'actions', 'sustainable-catalyst-engagement-intake' ); ?><p class="description"><?php esc_html_e( 'v0.11.0 interface executes one typed-confirmation action at a time; this ceiling is reserved for future guarded batch execution.', 'sustainable-catalyst-engagement-intake' ); ?></p></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Approval safeguards', 'sustainable-catalyst-engagement-intake' ); ?></th><td>
					<strong><?php esc_html_e( 'Approval before execution is permanently required in v0.11.0.', 'sustainable-catalyst-engagement-intake' ); ?></strong><br>
					<label class="sc-ei-settings-check"><input type="checkbox" name="sc_ei_settings[require_distinct_retention_approver]" value="1" <?php checked( $settings['require_distinct_retention_approver'], 1 ); ?>> <?php esc_html_e( 'Require approval by an authorized person other than the action proposer', 'sustainable-catalyst-engagement-intake' ); ?></label>
				</td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Fixed safety controls', 'sustainable-catalyst-engagement-intake' ); ?></th><td><strong><?php esc_html_e( 'Queue-only cron: enabled', 'sustainable-catalyst-engagement-intake' ); ?></strong><br><strong><?php esc_html_e( 'Non-personal tombstones: retained', 'sustainable-catalyst-engagement-intake' ); ?></strong><p class="description"><?php esc_html_e( 'These controls cannot be disabled in v0.11.0.', 'sustainable-catalyst-engagement-intake' ); ?></p></td></tr>
				<tr><th scope="row"><label for="sc-ei-draft-days"><?php esc_html_e( 'Abandoned drafts', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-draft-days" type="number" min="1" max="365" name="sc_ei_settings[abandoned_draft_days]" value="<?php echo esc_attr( $settings['abandoned_draft_days'] ); ?>"> <?php esc_html_e( 'days', 'sustainable-catalyst-engagement-intake' ); ?></td></tr>
			</table>
			<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-privacy' ) ); ?>"><?php esc_html_e( 'Open Privacy and Retention Center', 'sustainable-catalyst-engagement-intake' ); ?></a></p>
		</section>

		<section class="sc-ei-admin__card sc-ei-admin__settings-card">
			<h2><?php esc_html_e( 'Public form controls', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="sc-ei-minimum-seconds"><?php esc_html_e( 'Minimum completion time', 'sustainable-catalyst-engagement-intake' ); ?></label></th>
					<td><input id="sc-ei-minimum-seconds" type="number" min="1" max="30" name="sc_ei_settings[minimum_completion_seconds]" value="<?php echo esc_attr( $settings['minimum_completion_seconds'] ); ?>"> <?php esc_html_e( 'seconds', 'sustainable-catalyst-engagement-intake' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="sc-ei-submissions-hour"><?php esc_html_e( 'Maximum submissions per email', 'sustainable-catalyst-engagement-intake' ); ?></label></th>
					<td><input id="sc-ei-submissions-hour" type="number" min="1" max="20" name="sc_ei_settings[submissions_per_hour]" value="<?php echo esc_attr( $settings['submissions_per_hour'] ); ?>"> <?php esc_html_e( 'per hour', 'sustainable-catalyst-engagement-intake' ); ?></td>
				</tr>
			</table>
			<p class="description"><?php esc_html_e( 'The public forms also use a nonce, hidden honeypot, signed form timing, duplicate detection, field limits, and conditional server-side validation.', 'sustainable-catalyst-engagement-intake' ); ?></p>
		</section>

		<section class="sc-ei-admin__card sc-ei-admin__settings-card">
			<h2><?php esc_html_e( 'Notifications and communication transport', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><?php esc_html_e( 'All automatic policies default to off. Email is plain text and contains no file attachments. An accepted wp_mail() result is not proof of inbox delivery.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="sc-ei-communication-sender-name"><?php esc_html_e( 'Sender name', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-communication-sender-name" type="text" class="regular-text" name="sc_ei_settings[communication_sender_name]" value="<?php echo esc_attr( $settings['communication_sender_name'] ); ?>" required></td></tr>
				<tr><th scope="row"><label for="sc-ei-communication-sender-email"><?php esc_html_e( 'Sender email', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-communication-sender-email" type="email" class="regular-text" name="sc_ei_settings[communication_sender_email]" value="<?php echo esc_attr( $settings['communication_sender_email'] ); ?>" required><p class="description"><?php esc_html_e( 'Use an address authorized by the site’s mail transport and domain policy.', 'sustainable-catalyst-engagement-intake' ); ?></p></td></tr>
				<tr><th scope="row"><label for="sc-ei-communication-reply-email"><?php esc_html_e( 'Reply-to email', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-communication-reply-email" type="email" class="regular-text" name="sc_ei_settings[communication_reply_to_email]" value="<?php echo esc_attr( $settings['communication_reply_to_email'] ); ?>" required></td></tr>
				<tr><th scope="row"><label for="sc-ei-notification-internal"><?php esc_html_e( 'Internal notification recipients', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-notification-internal" type="text" class="large-text" name="sc_ei_settings[notification_internal_recipients]" value="<?php echo esc_attr( $settings['notification_internal_recipients'] ); ?>" placeholder="reviewer@example.com, manager@example.com"><p class="description"><?php esc_html_e( 'Maximum 10. If empty, new-inquiry and unassigned follow-up alerts fall back to the WordPress administration email.', 'sustainable-catalyst-engagement-intake' ); ?></p></td></tr>
				<tr><th scope="row"><label for="sc-ei-notification-escalation"><?php esc_html_e( 'Escalation recipients', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-notification-escalation" type="text" class="large-text" name="sc_ei_settings[notification_escalation_recipients]" value="<?php echo esc_attr( $settings['notification_escalation_recipients'] ); ?>"><p class="description"><?php esc_html_e( 'Maximum 10. Falls back to the internal recipient list.', 'sustainable-catalyst-engagement-intake' ); ?></p></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Automatic policies', 'sustainable-catalyst-engagement-intake' ); ?></th><td>
					<label class="sc-ei-settings-check"><input type="checkbox" name="sc_ei_settings[sender_acknowledgment_enabled]" value="1" <?php checked( $settings['sender_acknowledgment_enabled'], 1 ); ?>> <?php esc_html_e( 'Send a plain-text acknowledgment to the sender after successful intake', 'sustainable-catalyst-engagement-intake' ); ?></label><br>
					<label class="sc-ei-settings-check"><input type="checkbox" name="sc_ei_settings[internal_new_inquiry_enabled]" value="1" <?php checked( $settings['internal_new_inquiry_enabled'], 1 ); ?>> <?php esc_html_e( 'Send an internal new-inquiry alert', 'sustainable-catalyst-engagement-intake' ); ?></label><br>
					<label class="sc-ei-settings-check"><input type="checkbox" name="sc_ei_settings[review_due_reminders_enabled]" value="1" <?php checked( $settings['review_due_reminders_enabled'], 1 ); ?>> <?php esc_html_e( 'Send internal review due and overdue reminders to the assigned reviewer', 'sustainable-catalyst-engagement-intake' ); ?></label><br>
					<label class="sc-ei-settings-check"><input type="checkbox" name="sc_ei_settings[follow_up_reminders_enabled]" value="1" <?php checked( $settings['follow_up_reminders_enabled'], 1 ); ?>> <?php esc_html_e( 'Send internal reminders when a communication follow-up is due', 'sustainable-catalyst-engagement-intake' ); ?></label><br>
					<label class="sc-ei-settings-check"><input type="checkbox" name="sc_ei_settings[escalation_notifications_enabled]" value="1" <?php checked( $settings['escalation_notifications_enabled'], 1 ); ?>> <?php esc_html_e( 'Send an internal alert when a review enters an active escalation state', 'sustainable-catalyst-engagement-intake' ); ?></label>
					<p class="description"><?php esc_html_e( 'Enabling a policy does not bypass email suppression, deduplication, capabilities, or message history.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				</td></tr>
				<tr><th scope="row"><label for="sc-ei-review-reminder-lead"><?php esc_html_e( 'Review reminder lead', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-review-reminder-lead" type="number" min="0" max="168" name="sc_ei_settings[review_reminder_lead_hours]" value="<?php echo esc_attr( $settings['review_reminder_lead_hours'] ); ?>"> <?php esc_html_e( 'hours before due', 'sustainable-catalyst-engagement-intake' ); ?></td></tr>
				<tr><th scope="row"><label for="sc-ei-notification-batch"><?php esc_html_e( 'Reminder batch limit', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-notification-batch" type="number" min="1" max="100" name="sc_ei_settings[notification_batch_limit]" value="<?php echo esc_attr( $settings['notification_batch_limit'] ); ?>"> <?php esc_html_e( 'records per hourly run', 'sustainable-catalyst-engagement-intake' ); ?></td></tr>
			</table>
			<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-communications&view=policy' ) ); ?>"><?php esc_html_e( 'Open Notification Policy and Transport Test', 'sustainable-catalyst-engagement-intake' ); ?></a></p>
		</section>

		<section class="sc-ei-admin__card sc-ei-admin__settings-card">
			<h2><?php esc_html_e( 'Human-controlled fit assessment', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><?php esc_html_e( 'The fit system is reviewer-led. Scores are advisory summaries of human ratings and never create recommendations, thresholds, acceptance, rejection, communication, or status changes.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><?php esc_html_e( 'Fixed safeguards', 'sustainable-catalyst-engagement-intake' ); ?></th><td><strong><?php esc_html_e( 'Human attestation required', 'sustainable-catalyst-engagement-intake' ); ?></strong><br><strong><?php esc_html_e( 'No automated decision or status change', 'sustainable-catalyst-engagement-intake' ); ?></strong><br><strong><?php esc_html_e( 'Finalization and Review Workspace application are separate actions', 'sustainable-catalyst-engagement-intake' ); ?></strong></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Advisory signal', 'sustainable-catalyst-engagement-intake' ); ?></th><td><label><input type="checkbox" name="sc_ei_settings[fit_advisory_score_enabled]" value="1" <?php checked( $settings['fit_advisory_score_enabled'], 1 ); ?>> <?php esc_html_e( 'Show the transparent weighted rating summary', 'sustainable-catalyst-engagement-intake' ); ?></label><p class="description"><?php esc_html_e( 'No thresholds or automatic recommendation rules are used.', 'sustainable-catalyst-engagement-intake' ); ?></p></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Evidence quality', 'sustainable-catalyst-engagement-intake' ); ?></th><td>
					<label><input type="checkbox" name="sc_ei_settings[fit_require_evidence_for_assessed_items]" value="1" <?php checked( $settings['fit_require_evidence_for_assessed_items'], 1 ); ?>> <?php esc_html_e( 'Require evidence or reasoning for every assessed criterion', 'sustainable-catalyst-engagement-intake' ); ?></label><br>
					<label><input type="checkbox" name="sc_ei_settings[fit_require_rationale_for_finalization]" value="1" <?php checked( $settings['fit_require_rationale_for_finalization'], 1 ); ?>> <?php esc_html_e( 'Require a recommendation rationale', 'sustainable-catalyst-engagement-intake' ); ?></label>
				</td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Second-review triggers', 'sustainable-catalyst-engagement-intake' ); ?></th><td>
					<label><input type="checkbox" name="sc_ei_settings[fit_require_second_review_high_risk]" value="1" <?php checked( $settings['fit_require_second_review_high_risk'], 1 ); ?>> <?php esc_html_e( 'Material ethics, privacy, independence, or risk concern', 'sustainable-catalyst-engagement-intake' ); ?></label><br>
					<label><input type="checkbox" name="sc_ei_settings[fit_require_second_review_conflict]" value="1" <?php checked( $settings['fit_require_second_review_conflict'], 1 ); ?>> <?php esc_html_e( 'Conflict or independence boundary', 'sustainable-catalyst-engagement-intake' ); ?></label><br>
					<label><input type="checkbox" name="sc_ei_settings[fit_require_second_review_decline]" value="1" <?php checked( $settings['fit_require_second_review_decline'], 1 ); ?>> <?php esc_html_e( 'Not-a-fit recommendation', 'sustainable-catalyst-engagement-intake' ); ?></label><br>
					<label><input type="checkbox" name="sc_ei_settings[fit_require_second_review_unsafe_scope]" value="1" <?php checked( $settings['fit_require_second_review_unsafe_scope'], 1 ); ?>> <?php esc_html_e( 'Unsafe, prohibited, or inappropriate scope', 'sustainable-catalyst-engagement-intake' ); ?></label><br>
					<label><input type="checkbox" name="sc_ei_settings[fit_distinct_second_reviewer]" value="1" <?php checked( $settings['fit_distinct_second_reviewer'], 1 ); ?>> <?php esc_html_e( 'Require a second reviewer different from the original assessor', 'sustainable-catalyst-engagement-intake' ); ?></label>
				</td></tr>
				<tr><th scope="row"><label for="sc-ei-fit-stale-days"><?php esc_html_e( 'Stale assessment interval', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-fit-stale-days" type="number" min="1" max="365" name="sc_ei_settings[fit_assessment_stale_days]" value="<?php echo esc_attr( $settings['fit_assessment_stale_days'] ); ?>"> <?php esc_html_e( 'days', 'sustainable-catalyst-engagement-intake' ); ?></td></tr>
				<tr><th scope="row"><label for="sc-ei-fit-queue-limit"><?php esc_html_e( 'Assessment queue limit', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-fit-queue-limit" type="number" min="10" max="500" name="sc_ei_settings[fit_assessment_queue_limit]" value="<?php echo esc_attr( $settings['fit_assessment_queue_limit'] ); ?>"></td></tr>
			</table>
			<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-fit' ) ); ?>"><?php esc_html_e( 'Open Fit Assessment Workspace', 'sustainable-catalyst-engagement-intake' ); ?></a></p>
		</section>

		<section class="sc-ei-admin__card sc-ei-admin__settings-card">
			<h2><?php esc_html_e( 'Administrative review controls', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><?php esc_html_e( 'These controls set review deadlines and completion safeguards. They do not generate automatic fit decisions or send notifications.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><label for="sc-ei-review-normal-days"><?php esc_html_e( 'Normal-priority due window', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-review-normal-days" type="number" min="1" max="30" name="sc_ei_settings[default_review_due_days]" value="<?php echo esc_attr( $settings['default_review_due_days'] ); ?>"> <?php esc_html_e( 'days', 'sustainable-catalyst-engagement-intake' ); ?></td></tr>
				<tr><th scope="row"><label for="sc-ei-review-high-days"><?php esc_html_e( 'High-priority due window', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-review-high-days" type="number" min="1" max="14" name="sc_ei_settings[high_priority_review_due_days]" value="<?php echo esc_attr( $settings['high_priority_review_due_days'] ); ?>"> <?php esc_html_e( 'days', 'sustainable-catalyst-engagement-intake' ); ?></td></tr>
				<tr><th scope="row"><label for="sc-ei-review-low-days"><?php esc_html_e( 'Low-priority due window', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-review-low-days" type="number" min="1" max="60" name="sc_ei_settings[low_priority_review_due_days]" value="<?php echo esc_attr( $settings['low_priority_review_due_days'] ); ?>"> <?php esc_html_e( 'days', 'sustainable-catalyst-engagement-intake' ); ?></td></tr>
				<tr><th scope="row"><label for="sc-ei-review-urgent-hours"><?php esc_html_e( 'Urgent-priority due window', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-review-urgent-hours" type="number" min="1" max="72" name="sc_ei_settings[urgent_review_due_hours]" value="<?php echo esc_attr( $settings['urgent_review_due_hours'] ); ?>"> <?php esc_html_e( 'hours', 'sustainable-catalyst-engagement-intake' ); ?></td></tr>
				<tr><th scope="row"><label for="sc-ei-review-stale-days"><?php esc_html_e( 'Stale review threshold', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-review-stale-days" type="number" min="1" max="90" name="sc_ei_settings[stale_review_days]" value="<?php echo esc_attr( $settings['stale_review_days'] ); ?>"> <?php esc_html_e( 'idle days', 'sustainable-catalyst-engagement-intake' ); ?></td></tr>
				<tr><th scope="row"><label for="sc-ei-review-bulk-limit"><?php esc_html_e( 'Bulk review limit', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-review-bulk-limit" type="number" min="1" max="50" name="sc_ei_settings[review_bulk_limit]" value="<?php echo esc_attr( $settings['review_bulk_limit'] ); ?>"> <?php esc_html_e( 'inquiries per operation', 'sustainable-catalyst-engagement-intake' ); ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Assignment controls', 'sustainable-catalyst-engagement-intake' ); ?></th><td>
					<label class="sc-ei-settings-check"><input type="checkbox" name="sc_ei_settings[reviewer_self_assignment]" value="1" <?php checked( $settings['reviewer_self_assignment'], 1 ); ?>> <?php esc_html_e( 'Allow reviewers to claim unassigned inquiries', 'sustainable-catalyst-engagement-intake' ); ?></label><br>
					<label class="sc-ei-settings-check"><input type="checkbox" name="sc_ei_settings[restrict_review_to_assignee]" value="1" <?php checked( $settings['restrict_review_to_assignee'], 1 ); ?>> <?php esc_html_e( 'Restrict reviewer editing to the assigned reviewer; managers can edit or reassign any review', 'sustainable-catalyst-engagement-intake' ); ?></label>
				</td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Completion safeguards', 'sustainable-catalyst-engagement-intake' ); ?></th><td>
					<label class="sc-ei-settings-check"><input type="checkbox" name="sc_ei_settings[require_review_rationale]" value="1" <?php checked( $settings['require_review_rationale'], 1 ); ?>> <?php esc_html_e( 'Require rationale for fit decisions, escalations, and completed reviews', 'sustainable-catalyst-engagement-intake' ); ?></label><br>
					<label class="sc-ei-settings-check"><input type="checkbox" name="sc_ei_settings[require_completion_checklist]" value="1" <?php checked( $settings['require_completion_checklist'], 1 ); ?>> <?php esc_html_e( 'Require the full checklist before review completion', 'sustainable-catalyst-engagement-intake' ); ?></label>
				</td></tr>
			</table>
			<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-review' ) ); ?>"><?php esc_html_e( 'Open Review Workspace', 'sustainable-catalyst-engagement-intake' ); ?></a></p>
		</section>

		<section class="sc-ei-admin__card sc-ei-admin__settings-card">
			<h2><?php esc_html_e( 'Secure document intake', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><?php esc_html_e( 'Files are stored outside the public Media Library with randomized internal names. The configured path should be outside the public web root whenever hosting permits.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="sc-ei-upload-max-files"><?php esc_html_e( 'Maximum files per inquiry', 'sustainable-catalyst-engagement-intake' ); ?></label></th>
					<td><input id="sc-ei-upload-max-files" type="number" min="1" max="10" name="sc_ei_settings[upload_max_files]" value="<?php echo esc_attr( $settings['upload_max_files'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="sc-ei-upload-max-mb"><?php esc_html_e( 'Maximum size per file', 'sustainable-catalyst-engagement-intake' ); ?></label></th>
					<td><input id="sc-ei-upload-max-mb" type="number" min="1" max="100" name="sc_ei_settings[upload_max_file_mb]" value="<?php echo esc_attr( $settings['upload_max_file_mb'] ); ?>"> <?php esc_html_e( 'MB', 'sustainable-catalyst-engagement-intake' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Allowed extensions', 'sustainable-catalyst-engagement-intake' ); ?></th>
					<td>
						<fieldset>
							<?php foreach ( SC_EI_Upload_Validator::supported_extensions() as $extension => $label ) : ?>
								<label class="sc-ei-settings-check">
									<input type="checkbox" name="sc_ei_settings[allowed_upload_extensions][]" value="<?php echo esc_attr( $extension ); ?>" <?php checked( in_array( $extension, (array) $settings['allowed_upload_extensions'], true ) ); ?>>
									<?php echo esc_html( strtoupper( $extension ) . ' — ' . $label ); ?>
								</label><br>
							<?php endforeach; ?>
						</fieldset>
						<p class="description"><?php esc_html_e( 'Executable files, archives, macros, encrypted documents, active-content PDFs, and extension/MIME mismatches remain blocked regardless of this selection.', 'sustainable-catalyst-engagement-intake' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="sc-ei-attachment-retention"><?php esc_html_e( 'Default attachment retention', 'sustainable-catalyst-engagement-intake' ); ?></label></th>
					<td><strong><?php echo esc_html( $settings['attachment_retention_days'] ); ?> <?php esc_html_e( 'days', 'sustainable-catalyst-engagement-intake' ); ?></strong><p class="description"><?php esc_html_e( 'Configured in Privacy and retention lifecycle defaults above.', 'sustainable-catalyst-engagement-intake' ); ?></p></td>
				</tr>
				<tr>
					<th scope="row"><label for="sc-ei-private-storage"><?php esc_html_e( 'Private storage path', 'sustainable-catalyst-engagement-intake' ); ?></label></th>
					<td>
						<?php $sc_ei_locked_storage_path = (string) get_option( 'sc_ei_storage_base_dir', '' ); ?>
						<input id="sc-ei-private-storage" type="text" class="large-text code" name="sc_ei_settings[private_storage_path]" value="<?php echo esc_attr( $sc_ei_locked_storage_path ?: $settings['private_storage_path'] ); ?>" placeholder="<?php echo esc_attr( dirname( ABSPATH ) . '/sc-engagement-intake-private' ); ?>" <?php echo $sc_ei_locked_storage_path ? 'readonly' : ''; ?>>
						<p class="description"><?php esc_html_e( 'Optional absolute server path. Leave empty for automatic selection. The selected path is locked when the first accepted document is stored so existing files do not become orphaned. SC_EI_PRIVATE_STORAGE_PATH overrides the lock and should only be changed with a deliberate storage migration.', 'sustainable-catalyst-engagement-intake' ); ?></p>
						<?php if ( get_option( 'sc_ei_storage_base_dir', '' ) ) : ?>
							<p><strong><?php esc_html_e( 'Locked effective path:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <code><?php echo esc_html( get_option( 'sc_ei_storage_base_dir', '' ) ); ?></code></p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'External scanner requirement', 'sustainable-catalyst-engagement-intake' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="sc_ei_settings[require_external_scanner]" value="1" <?php checked( $settings['require_external_scanner'], 1 ); ?>>
							<?php esc_html_e( 'Reject and delete uploads unless an integrated scanner reports them clean.', 'sustainable-catalyst-engagement-intake' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Newly enabling this fail-closed policy requires a configured integration and a recent clean benign readiness test. An already-enabled policy is not silently disabled when readiness later degrades.', 'sustainable-catalyst-engagement-intake' ); ?></p>
						<p>
							<strong><?php esc_html_e( 'Current readiness:', 'sustainable-catalyst-engagement-intake' ); ?></strong>
							<span class="sc-ei-readiness-inline sc-ei-readiness-inline--<?php echo esc_attr( $scanner_readiness['ready'] ? 'ready' : 'attention' ); ?>">
								<?php echo $scanner_readiness['ready'] ? esc_html__( 'ready', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'not established', 'sustainable-catalyst-engagement-intake' ); ?>
							</span>
							· <?php echo esc_html( $scanner_readiness['probe']['provider'] ); ?>
							· <?php echo esc_html( ucwords( str_replace( '_', ' ', (string) ( $scanner_readiness['test']['scan_status'] ?? 'not_run' ) ) ) ); ?>
						</p>
						<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-quarantine' ) ); ?>"><?php esc_html_e( 'Open Scanner Readiness', 'sustainable-catalyst-engagement-intake' ); ?></a></p>
						<?php if ( $scanner_readiness['require_clean_enabled'] && ! $scanner_readiness['ready'] ) : ?>
							<div class="sc-ei-diagnostic-warning"><strong><?php esc_html_e( 'Fail-closed attention:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'New uploads that are not reported clean will be deleted and rejected. Restore the scanner or deliberately turn this policy off.', 'sustainable-catalyst-engagement-intake' ); ?></div>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="sc-ei-scanner-freshness"><?php esc_html_e( 'Scanner test freshness', 'sustainable-catalyst-engagement-intake' ); ?></label></th>
					<td>
						<input id="sc-ei-scanner-freshness" type="number" min="1" max="168" name="sc_ei_settings[scanner_test_freshness_hours]" value="<?php echo esc_attr( $settings['scanner_test_freshness_hours'] ); ?>"> <?php esc_html_e( 'hours', 'sustainable-catalyst-engagement-intake' ); ?>
						<p class="description"><?php esc_html_e( 'A clean test older than this window no longer establishes clean-required readiness.', 'sustainable-catalyst-engagement-intake' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="sc-ei-scanner-bulk-limit"><?php esc_html_e( 'Bulk scanner retry limit', 'sustainable-catalyst-engagement-intake' ); ?></label></th>
					<td>
						<input id="sc-ei-scanner-bulk-limit" type="number" min="1" max="50" name="sc_ei_settings[scanner_bulk_retry_limit]" value="<?php echo esc_attr( $settings['scanner_bulk_retry_limit'] ); ?>"> <?php esc_html_e( 'documents per operation', 'sustainable-catalyst-engagement-intake' ); ?>
						<p class="description"><?php esc_html_e( 'Use a conservative value when the external scanner has rate, CPU, memory, or API constraints.', 'sustainable-catalyst-engagement-intake' ); ?></p>
					</td>
				</tr>
			</table>
		</section>

		<section class="sc-ei-admin__card sc-ei-admin__settings-card">
			<h2><?php esc_html_e( 'Microsoft Teams scheduling readiness', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><?php esc_html_e( 'Microsoft Teams remains the only supported live meeting platform. v0.11.0 retains optional human-triggered Microsoft Graph calendar creation and manual Teams finalization; neither path activates an engagement automatically.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="sc-ei-teams-organizer"><?php esc_html_e( 'Teams organizer email', 'sustainable-catalyst-engagement-intake' ); ?></label></th>
					<td>
						<input id="sc-ei-teams-organizer" type="email" class="regular-text" name="sc_ei_settings[teams_organizer_email]" value="<?php echo esc_attr( $settings['teams_organizer_email'] ); ?>">
						<p class="description"><?php esc_html_e( 'Optional administrative organizer identity for future Microsoft Graph integration.', 'sustainable-catalyst-engagement-intake' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="sc-ei-default-teams-duration"><?php esc_html_e( 'Default Teams duration', 'sustainable-catalyst-engagement-intake' ); ?></label></th>
					<td>
						<select id="sc-ei-default-teams-duration" name="sc_ei_settings[default_teams_duration]">
							<?php foreach ( array( 20, 30, 45, 60, 90 ) as $minutes ) : ?>
								<option value="<?php echo esc_attr( $minutes ); ?>" <?php selected( $settings['default_teams_duration'], $minutes ); ?>><?php echo esc_html( sprintf( __( '%d minutes', 'sustainable-catalyst-engagement-intake' ), $minutes ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>
		</section>

		<section class="sc-ei-admin__card sc-ei-admin__settings-card">
			<h2><?php esc_html_e( 'Proposal and engagement handoff', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><?php esc_html_e( 'These options control availability and sender visibility. Commercial integrity, required readiness checks, human activation, and the no-provisioning/no-invoice/no-payment/no-signature safeguards remain fixed.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<table class="form-table" role="presentation">
				<tr><th scope="row"><?php esc_html_e( 'Handoff workspace', 'sustainable-catalyst-engagement-intake' ); ?></th><td><label><input type="checkbox" name="sc_ei_settings[engagement_enabled]" value="1" <?php checked( $settings['engagement_enabled'], 1 ); ?>> <?php esc_html_e( 'Enable controlled handoff creation from contracted proposals.', 'sustainable-catalyst-engagement-intake' ); ?></label></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Sender portal', 'sustainable-catalyst-engagement-intake' ); ?></th><td><label><input type="checkbox" name="sc_ei_settings[engagement_sender_portal_enabled]" value="1" <?php checked( $settings['engagement_sender_portal_enabled'], 1 ); ?>> <?php esc_html_e( 'Allow sender-safe engagement status when portal access includes view_engagements.', 'sustainable-catalyst-engagement-intake' ); ?></label></td></tr>
				<tr><th scope="row"><label for="sc-ei-engagement-kickoff-days"><?php esc_html_e( 'Default kickoff planning window', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-engagement-kickoff-days" type="number" min="1" max="90" name="sc_ei_settings[engagement_default_kickoff_days]" value="<?php echo esc_attr( $settings['engagement_default_kickoff_days'] ); ?>"> <?php esc_html_e( 'days', 'sustainable-catalyst-engagement-intake' ); ?></td></tr>
				<tr><th scope="row"><label for="sc-ei-engagement-requirement-days"><?php esc_html_e( 'Default requirement due window', 'sustainable-catalyst-engagement-intake' ); ?></label></th><td><input id="sc-ei-engagement-requirement-days" type="number" min="1" max="365" name="sc_ei_settings[engagement_default_requirement_days]" value="<?php echo esc_attr( $settings['engagement_default_requirement_days'] ); ?>"> <?php esc_html_e( 'days', 'sustainable-catalyst-engagement-intake' ); ?></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Private integration exports', 'sustainable-catalyst-engagement-intake' ); ?></th><td><label><input type="checkbox" name="sc_ei_settings[engagement_allow_workbench_export]" value="1" <?php checked( $settings['engagement_allow_workbench_export'], 1 ); ?>> <?php esc_html_e( 'Include Workbench handoff metadata.', 'sustainable-catalyst-engagement-intake' ); ?></label><br><label><input type="checkbox" name="sc_ei_settings[engagement_allow_decision_studio_export]" value="1" <?php checked( $settings['engagement_allow_decision_studio_export'], 1 ); ?>> <?php esc_html_e( 'Include Decision Studio handoff metadata.', 'sustainable-catalyst-engagement-intake' ); ?></label><p class="description"><?php esc_html_e( 'Exports never provision either system.', 'sustainable-catalyst-engagement-intake' ); ?></p></td></tr>
				<tr><th scope="row"><?php esc_html_e( 'Fixed readiness and activation controls', 'sustainable-catalyst-engagement-intake' ); ?></th><td><strong><?php esc_html_e( 'Contract reference, owner, snapshot integrity, required onboarding items, proposal state, exact proposal version, and privacy state are checked.', 'sustainable-catalyst-engagement-intake' ); ?></strong><p class="description"><?php esc_html_e( 'Handoff, readiness, activation, pause, resume, completion, and cancellation remain typed human actions.', 'sustainable-catalyst-engagement-intake' ); ?></p></td></tr>
			</table>
		</section>

		<section class="sc-ei-admin__card sc-ei-admin__settings-card">
			<h2><?php esc_html_e( 'Uninstall behavior', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<label>
				<input type="checkbox" name="sc_ei_settings[delete_data_on_uninstall]" value="1" <?php checked( $settings['delete_data_on_uninstall'], 1 ); ?>>
				<?php esc_html_e( 'Permanently delete all inquiry, attachment metadata, audit records, roles, and plugin settings when the plugin is uninstalled.', 'sustainable-catalyst-engagement-intake' ); ?>
			</label>
			<p class="description"><strong><?php esc_html_e( 'Default: off.', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'Deactivation never deletes private records.', 'sustainable-catalyst-engagement-intake' ); ?></p>
		</section>

		<?php submit_button(); ?>
	</form>
</div>
