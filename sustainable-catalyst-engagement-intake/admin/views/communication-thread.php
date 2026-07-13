<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$message = isset( $_GET['sc_ei_msg'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_msg'] ) ) : '';
$draft_values = array(
	'id'                     => absint( $draft['id'] ?? 0 ),
	'row_version'            => absint( $draft['row_version'] ?? 0 ),
	'direction'              => (string) ( $draft['direction'] ?? 'outbound' ),
	'communication_type'     => (string) ( $draft['communication_type'] ?? 'general_response' ),
	'subject'                => (string) ( $draft['subject'] ?? '' ),
	'body_text'              => (string) ( $draft['body_text'] ?? '' ),
	'recipient_name'         => (string) ( $draft['recipient_name'] ?? $inquiry['contact_name'] ),
	'recipient_email'        => (string) ( $draft['recipient_email'] ?? $inquiry['contact_email'] ),
	'cc'                     => implode( ', ', json_decode( (string) ( $draft['cc_json'] ?? '[]' ), true ) ?: array() ),
	'template_key'           => (string) ( $draft['template_key'] ?? '' ),
	'template_version'       => absint( $draft['template_version'] ?? 0 ),
	'reply_to_id'            => absint( $draft['reply_to_id'] ?? 0 ),
	'privacy_classification' => (string) ( $draft['privacy_classification'] ?? 'private' ),
);
$export_url = wp_nonce_url(
	add_query_arg(
		array(
			'action'  => 'sc_ei_export_communication_history',
			'inquiry' => absint( $inquiry['id'] ),
		),
		admin_url( 'admin-post.php' )
	),
	'sc_ei_export_communication_history_' . absint( $inquiry['id'] )
);
?>
<div class="wrap sc-ei-admin sc-ei-communication-thread">
	<p class="sc-ei-admin__breadcrumb">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-communications' ) ); ?>">← <?php esc_html_e( 'Back to Communications', 'sustainable-catalyst-engagement-intake' ); ?></a>
		· <a href="<?php echo esc_url( SC_EI_Review_Admin::detail_url( absint( $inquiry['id'] ) ) ); ?>"><?php esc_html_e( 'Administrative Review', 'sustainable-catalyst-engagement-intake' ); ?></a>
		· <a href="<?php echo esc_url( add_query_arg( array( 'page' => 'sc-engagement-intake', 'action' => 'view', 'inquiry' => absint( $inquiry['id'] ) ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Full Inquiry Record', 'sustainable-catalyst-engagement-intake' ); ?></a>
		· <a href="<?php echo esc_url( SC_EI_Privacy_Admin::url( 'overview', array( 'inquiry' => absint( $inquiry['id'] ) ) ) ); ?>"><?php esc_html_e( 'Privacy Center', 'sustainable-catalyst-engagement-intake' ); ?></a>
		· <?php if ( ! empty( $inquiry['portal_access_id'] ) ) : ?><a href="<?php echo esc_url( SC_EI_Portal_Admin::url( absint( $inquiry['portal_access_id'] ) ) ); ?>"><?php esc_html_e( 'Sender Portal', 'sustainable-catalyst-engagement-intake' ); ?></a><?php endif; ?>
	</p>
	<h1><?php echo esc_html( $inquiry['reference'] ); ?> · <?php esc_html_e( 'Communication History', 'sustainable-catalyst-engagement-intake' ); ?></h1>
	<p><?php echo esc_html( $inquiry['contact_name'] ); ?> · <?php echo esc_html( $inquiry['contact_email'] ); ?><?php echo $inquiry['organization'] ? ' · ' . esc_html( $inquiry['organization'] ) : ''; ?></p>
	<?php if ( in_array( $inquiry['privacy_status'], array( 'restricted', 'erasure_requested' ), true ) || absint( $inquiry['legal_hold_count'] ) > 0 ) : ?>
		<div class="notice notice-warning"><p><strong><?php echo esc_html( SC_EI_Privacy_Schema::label( SC_EI_Privacy_Schema::privacy_statuses(), $inquiry['privacy_status'] ) ); ?></strong> · <?php esc_html_e( 'Review the Privacy Center before sending or recording new external communication.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php endif; ?>

	<?php if ( 'communication_draft_saved' === $message ) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Draft saved. Review the exact recipient, subject, and body before using the separate Send action.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'communication_mail_accepted' === $message ) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'The email was accepted by the WordPress mail transport. Delivery to an inbox was not independently confirmed.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'communication_interaction_recorded' === $message ) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'The external or inbound interaction was added to the private communication history.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'communication_thread_updated' === $message ) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Communication state, follow-up, unread count, or email suppression was updated.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'communication_canceled' === $message ) : ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'The communication draft was canceled. Its audit and event history remain.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'communication_suppressed' === $message ) : ?><div class="notice notice-warning"><p><?php esc_html_e( 'The email was suppressed by this inquiry’s do-not-email control.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'send_confirmation_required' === $message ) : ?><div class="notice notice-error"><p><?php esc_html_e( 'Confirm that you reviewed the recipient and content before sending.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'communication_conflict' === $message ) : ?><div class="notice notice-error"><p><?php esc_html_e( 'The draft changed in another browser session. Reload the current draft before saving.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( $message ) : ?><div class="notice notice-error is-dismissible"><p><?php echo esc_html( ucwords( str_replace( '_', ' ', $message ) ) ); ?></p></div><?php endif; ?>

	<div class="sc-ei-comm-thread-metrics">
		<div><strong><?php echo esc_html( number_format_i18n( $inquiry['communication_count'] ) ); ?></strong><span><?php esc_html_e( 'recorded communications', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div class="<?php echo $inquiry['unread_inbound_count'] ? 'sc-ei-review-metric--attention' : ''; ?>"><strong><?php echo esc_html( number_format_i18n( $inquiry['unread_inbound_count'] ) ); ?></strong><span><?php esc_html_e( 'unread inbound', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div><strong><?php echo esc_html( SC_EI_Communication_Schema::label( SC_EI_Communication_Schema::communication_states(), $inquiry['communication_status'] ) ); ?></strong><span><?php esc_html_e( 'thread state', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div class="<?php echo $inquiry['next_follow_up_at'] && strtotime( $inquiry['next_follow_up_at'] . ' UTC' ) <= time() ? 'sc-ei-review-metric--attention' : ''; ?>"><strong><?php echo $inquiry['next_follow_up_at'] ? esc_html( get_date_from_gmt( $inquiry['next_follow_up_at'], 'M j, Y g:i a' ) ) : esc_html__( 'Not set', 'sustainable-catalyst-engagement-intake' ); ?></strong><span><?php esc_html_e( 'next follow-up', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div class="<?php echo $inquiry['do_not_email'] ? 'sc-ei-review-metric--danger' : ''; ?>"><strong><?php echo $inquiry['do_not_email'] ? esc_html__( 'Suppressed', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'Allowed', 'sustainable-catalyst-engagement-intake' ); ?></strong><span><?php esc_html_e( 'sender email', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div><strong><?php echo $inquiry['last_communication_at'] ? esc_html( get_date_from_gmt( $inquiry['last_communication_at'], 'M j, Y g:i a' ) ) : esc_html__( 'None', 'sustainable-catalyst-engagement-intake' ); ?></strong><span><?php esc_html_e( 'last communication', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
	</div>

	<div class="sc-ei-comm-thread-layout">
		<main>
			<?php if ( current_user_can( 'sc_intake_compose_communications' ) ) : ?>
				<section class="sc-ei-admin__card sc-ei-compose-card">
					<div class="sc-ei-review-section-header"><h2><?php echo $draft ? esc_html__( 'Edit Communication Draft', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'Compose a Communication Draft', 'sustainable-catalyst-engagement-intake' ); ?></h2><span><?php echo $draft ? esc_html( 'v' . absint( $draft['row_version'] ) ) : esc_html__( 'new draft', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
					<p><?php esc_html_e( 'Drafts never send automatically. Saving and sending are separate actions. Email attachments are not supported; never copy private document contents into an email merely to bypass quarantine.', 'sustainable-catalyst-engagement-intake' ); ?></p>
					<?php if ( $inquiry['do_not_email'] ) : ?><div class="sc-ei-diagnostic-warning"><strong><?php esc_html_e( 'Do not email:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo esc_html( $inquiry['do_not_email_reason'] ); ?></div><?php endif; ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-sc-ei-compose-form>
						<input type="hidden" name="action" value="sc_ei_save_communication_draft">
						<input type="hidden" name="inquiry_id" value="<?php echo esc_attr( $inquiry['id'] ); ?>">
						<input type="hidden" name="communication_id" value="<?php echo esc_attr( $draft_values['id'] ); ?>">
						<input type="hidden" name="row_version" value="<?php echo esc_attr( $draft_values['row_version'] ); ?>">
						<input type="hidden" name="template_version" value="<?php echo esc_attr( $draft_values['template_version'] ); ?>" data-sc-ei-template-version>
						<?php wp_nonce_field( 'sc_ei_save_communication_draft_' . absint( $inquiry['id'] ) ); ?>
						<div class="sc-ei-review-form-grid">
							<label><span><?php esc_html_e( 'Template', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="template_key" data-sc-ei-template-select><option value=""><?php esc_html_e( 'No template / preserve current draft', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( $templates as $key => $template ) : ?><?php if ( ! $template['is_system'] ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $draft_values['template_key'], $key ); ?>><?php echo esc_html( $template['name'] . ' · v' . $template['version'] ); ?></option><?php endif; ?><?php endforeach; ?></select><small><?php esc_html_e( 'Selecting a template replaces subject and body in the browser; review every word before saving.', 'sustainable-catalyst-engagement-intake' ); ?></small></label>
							<label><span><?php esc_html_e( 'Direction', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="direction"><option value="outbound" <?php selected( $draft_values['direction'], 'outbound' ); ?>><?php esc_html_e( 'Outbound to sender', 'sustainable-catalyst-engagement-intake' ); ?></option><option value="internal" <?php selected( $draft_values['direction'], 'internal' ); ?>><?php esc_html_e( 'Internal email', 'sustainable-catalyst-engagement-intake' ); ?></option></select></label>
							<label><span><?php esc_html_e( 'Communication type', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="communication_type" data-sc-ei-communication-type><?php foreach ( SC_EI_Communication_Schema::types() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $draft_values['communication_type'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
							<label><span><?php esc_html_e( 'Privacy classification', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="privacy_classification"><?php foreach ( SC_EI_Communication_Schema::privacy_levels() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $draft_values['privacy_classification'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
							<label><span><?php esc_html_e( 'Recipient name', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="recipient_name" maxlength="191" value="<?php echo esc_attr( $draft_values['recipient_name'] ); ?>"></label>
							<label><span><?php esc_html_e( 'Recipient email', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="email" name="recipient_email" required value="<?php echo esc_attr( $draft_values['recipient_email'] ); ?>"></label>
							<label class="sc-ei-review-form-grid__wide"><span><?php esc_html_e( 'CC email addresses', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="cc" value="<?php echo esc_attr( $draft_values['cc'] ); ?>" placeholder="<?php esc_attr_e( 'Comma-separated; maximum 10', 'sustainable-catalyst-engagement-intake' ); ?>"></label>
							<label class="sc-ei-review-form-grid__wide"><span><?php esc_html_e( 'Subject', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="subject" maxlength="255" required value="<?php echo esc_attr( $draft_values['subject'] ); ?>" data-sc-ei-subject></label>
							<label class="sc-ei-review-form-grid__wide"><span><?php esc_html_e( 'Plain-text message', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="body_text" rows="16" maxlength="50000" required data-sc-ei-body><?php echo esc_textarea( $draft_values['body_text'] ); ?></textarea></label>
						</div>
						<p><button type="submit" class="button button-primary"><?php esc_html_e( 'Save Draft', 'sustainable-catalyst-engagement-intake' ); ?></button> <span class="description"><?php esc_html_e( 'Saving does not send.', 'sustainable-catalyst-engagement-intake' ); ?></span></p>
					</form>
					<script type="application/json" id="sc-ei-template-data"><?php echo wp_json_encode( $template_payload ); ?></script>

					<?php if ( $draft && in_array( $draft['status'], array( 'draft', 'approved', 'failed' ), true ) ) : ?>
						<div class="sc-ei-send-review">
							<h3><?php esc_html_e( 'Reviewed Send Action', 'sustainable-catalyst-engagement-intake' ); ?></h3>
							<p><strong><?php esc_html_e( 'To:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo esc_html( $draft['recipient_name'] ); ?> &lt;<?php echo esc_html( $draft['recipient_email'] ); ?>&gt;</p>
							<p><strong><?php esc_html_e( 'Subject:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo esc_html( $draft['subject'] ); ?></p>
							<pre><?php echo esc_html( $draft['body_text'] ); ?></pre>
							<?php if ( current_user_can( 'sc_intake_send_communications' ) ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<input type="hidden" name="action" value="sc_ei_send_communication">
									<input type="hidden" name="communication_id" value="<?php echo esc_attr( $draft['id'] ); ?>">
									<?php wp_nonce_field( 'sc_ei_send_communication_' . absint( $draft['id'] ) ); ?>
									<label class="sc-ei-review-checkbox"><input type="checkbox" name="confirm_send" value="1" required><span><?php esc_html_e( 'I reviewed the recipient, subject, body, privacy classification, and suppression state. I understand acceptance by wp_mail is not delivery confirmation.', 'sustainable-catalyst-engagement-intake' ); ?></span></label>
									<button type="submit" class="button button-primary button-large"><?php echo 'failed' === $draft['status'] ? esc_html__( 'Retry Email', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'Send Email Now', 'sustainable-catalyst-engagement-intake' ); ?></button>
								</form>
							<?php endif; ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<input type="hidden" name="action" value="sc_ei_cancel_communication">
								<input type="hidden" name="communication_id" value="<?php echo esc_attr( $draft['id'] ); ?>">
								<?php wp_nonce_field( 'sc_ei_cancel_communication_' . absint( $draft['id'] ) ); ?>
								<button type="submit" class="button"><?php esc_html_e( 'Cancel Draft', 'sustainable-catalyst-engagement-intake' ); ?></button>
							</form>
						</div>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<section class="sc-ei-admin__card">
				<div class="sc-ei-review-section-header"><h2><?php esc_html_e( 'Private Communication Timeline', 'sustainable-catalyst-engagement-intake' ); ?></h2><span><?php echo esc_html( sprintf( _n( '%d record', '%d records', count( $communications ), 'sustainable-catalyst-engagement-intake' ), count( $communications ) ) ); ?></span></div>
				<?php if ( current_user_can( 'sc_intake_export_communications' ) ) : ?><p><a class="button" href="<?php echo esc_url( $export_url ); ?>"><?php esc_html_e( 'Export Communication History CSV', 'sustainable-catalyst-engagement-intake' ); ?></a></p><?php endif; ?>
				<?php if ( $communications ) : ?>
					<ol class="sc-ei-communication-timeline">
						<?php foreach ( $communications as $communication ) : ?>
							<li class="sc-ei-communication-item sc-ei-communication-item--<?php echo esc_attr( $communication['direction'] ); ?>">
								<div class="sc-ei-review-section-header">
									<div><span class="sc-ei-comm-direction sc-ei-comm-direction--<?php echo esc_attr( $communication['direction'] ); ?>"><?php echo esc_html( SC_EI_Communication_Schema::label( SC_EI_Communication_Schema::directions(), $communication['direction'] ) ); ?></span> <span class="sc-ei-comm-status sc-ei-comm-status--<?php echo esc_attr( $communication['status'] ); ?>"><?php echo esc_html( SC_EI_Communication_Schema::label( SC_EI_Communication_Schema::statuses(), $communication['status'] ) ); ?></span></div>
									<span><?php echo esc_html( get_date_from_gmt( $communication['occurred_at'] ?: $communication['accepted_at'] ?: $communication['created_at'], 'M j, Y g:i a' ) ); ?></span>
								</div>
								<h3><?php echo esc_html( $communication['subject'] ); ?></h3>
								<p class="description"><?php echo esc_html( SC_EI_Communication_Schema::label( SC_EI_Communication_Schema::types(), $communication['communication_type'] ) ); ?> · <?php echo esc_html( SC_EI_Communication_Schema::label( SC_EI_Communication_Schema::channels(), $communication['channel'] ) ); ?> · <?php echo esc_html( $communication['recipient_email'] ); ?></p>
								<pre><?php echo esc_html( $communication['body_text'] ); ?></pre>
								<?php if ( 'accepted' === $communication['status'] ) : ?><p class="description"><?php esc_html_e( 'Accepted by WordPress mail transport; no inbox delivery or reading confirmation is available.', 'sustainable-catalyst-engagement-intake' ); ?></p><?php endif; ?>
								<?php if ( 'failed' === $communication['status'] ) : ?><div class="sc-ei-diagnostic-warning"><strong><?php echo esc_html( $communication['error_code'] ); ?></strong> <?php echo esc_html( $communication['error_message'] ); ?></div><?php endif; ?>
								<?php if ( in_array( $communication['status'], array( 'draft', 'approved', 'failed' ), true ) && current_user_can( 'sc_intake_compose_communications' ) ) : ?><p><a class="button" href="<?php echo esc_url( SC_EI_Communication_Admin::thread_url( absint( $inquiry['id'] ), array( 'draft' => absint( $communication['id'] ) ) ) ); ?>"><?php esc_html_e( 'Open Draft', 'sustainable-catalyst-engagement-intake' ); ?></a></p><?php endif; ?>
								<?php if ( ! empty( $events[ $communication['id'] ] ) ) : ?><details><summary><?php esc_html_e( 'Delivery and change events', 'sustainable-catalyst-engagement-intake' ); ?></summary><ol class="sc-ei-comm-events"><?php foreach ( $events[ $communication['id'] ] as $event ) : ?><li><strong><?php echo esc_html( ucwords( str_replace( '_', ' ', $event['event_type'] ) ) ); ?></strong> · <?php echo esc_html( $event['from_status'] ); ?> → <?php echo esc_html( $event['to_status'] ); ?> · <?php echo esc_html( get_date_from_gmt( $event['created_at'], 'M j, Y g:i a' ) ); ?><?php if ( $event['error_code'] ) : ?><br><span class="sc-ei-inline-warning"><?php echo esc_html( $event['error_code'] . ': ' . $event['error_message'] ); ?></span><?php endif; ?></li><?php endforeach; ?></ol></details><?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ol>
				<?php else : ?><p><?php esc_html_e( 'No communication has been recorded for this inquiry.', 'sustainable-catalyst-engagement-intake' ); ?></p><?php endif; ?>
			</section>
		</main>

		<aside>
			<section class="sc-ei-admin__card">
				<h2><?php esc_html_e( 'Thread Controls', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="sc_ei_update_communication_thread">
					<input type="hidden" name="inquiry_id" value="<?php echo esc_attr( $inquiry['id'] ); ?>">
					<?php wp_nonce_field( 'sc_ei_update_communication_thread_' . absint( $inquiry['id'] ) ); ?>
					<p><label><strong><?php esc_html_e( 'Communication state', 'sustainable-catalyst-engagement-intake' ); ?></strong><select name="communication_status"><?php foreach ( SC_EI_Communication_Schema::communication_states() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $inquiry['communication_status'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label></p>
					<p><label><strong><?php esc_html_e( 'Next follow-up', 'sustainable-catalyst-engagement-intake' ); ?></strong><input type="datetime-local" name="next_follow_up_local" value="<?php echo esc_attr( $follow_up_local ); ?>"></label></p>
					<p><label class="sc-ei-review-checkbox"><input type="checkbox" name="mark_inbound_read" value="1"><span><?php esc_html_e( 'Mark all inbound records read', 'sustainable-catalyst-engagement-intake' ); ?></span></label></p>
					<p><label class="sc-ei-review-checkbox"><input type="checkbox" name="do_not_email" value="1" <?php checked( $inquiry['do_not_email'], 1 ); ?> data-sc-ei-do-not-email><span><?php esc_html_e( 'Suppress email to the sender', 'sustainable-catalyst-engagement-intake' ); ?></span></label></p>
					<p><label><strong><?php esc_html_e( 'Suppression reason', 'sustainable-catalyst-engagement-intake' ); ?></strong><textarea name="do_not_email_reason" rows="4"><?php echo esc_textarea( $inquiry['do_not_email_reason'] ); ?></textarea></label></p>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Update Thread Controls', 'sustainable-catalyst-engagement-intake' ); ?></button>
				</form>
			</section>

			<?php if ( current_user_can( 'sc_intake_record_inbound' ) ) : ?>
				<section class="sc-ei-admin__card">
					<h2><?php esc_html_e( 'Record an External Interaction', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<p><?php esc_html_e( 'Use this for inbound email, phone calls, Teams messages or meetings, and outbound communication completed outside WordPress.', 'sustainable-catalyst-engagement-intake' ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="sc_ei_record_interaction">
						<input type="hidden" name="inquiry_id" value="<?php echo esc_attr( $inquiry['id'] ); ?>">
						<?php wp_nonce_field( 'sc_ei_record_interaction_' . absint( $inquiry['id'] ) ); ?>
						<p><label><strong><?php esc_html_e( 'Direction', 'sustainable-catalyst-engagement-intake' ); ?></strong><select name="interaction_direction"><option value="inbound"><?php esc_html_e( 'Inbound', 'sustainable-catalyst-engagement-intake' ); ?></option><option value="outbound"><?php esc_html_e( 'Outbound outside WordPress', 'sustainable-catalyst-engagement-intake' ); ?></option><option value="internal"><?php esc_html_e( 'Internal note or conversation', 'sustainable-catalyst-engagement-intake' ); ?></option></select></label></p>
						<p><label><strong><?php esc_html_e( 'Channel', 'sustainable-catalyst-engagement-intake' ); ?></strong><select name="interaction_channel"><?php foreach ( SC_EI_Communication_Schema::channels() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label></p>
						<p><label><strong><?php esc_html_e( 'Type', 'sustainable-catalyst-engagement-intake' ); ?></strong><select name="interaction_type"><?php foreach ( SC_EI_Communication_Schema::types() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, 'manual_interaction' ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label></p>
						<p><label><strong><?php esc_html_e( 'Contact or participant name', 'sustainable-catalyst-engagement-intake' ); ?></strong><input type="text" name="interaction_party_name" value="<?php echo esc_attr( $inquiry['contact_name'] ); ?>"></label></p>
						<p><label><strong><?php esc_html_e( 'Contact email', 'sustainable-catalyst-engagement-intake' ); ?></strong><input type="email" name="interaction_party_email" value="<?php echo esc_attr( $inquiry['contact_email'] ); ?>"></label></p>
						<p><label><strong><?php esc_html_e( 'Occurred at', 'sustainable-catalyst-engagement-intake' ); ?></strong><input type="datetime-local" name="interaction_occurred_at" value="<?php echo esc_attr( $now_local ); ?>"></label></p>
						<p><label><strong><?php esc_html_e( 'Subject', 'sustainable-catalyst-engagement-intake' ); ?></strong><input type="text" name="interaction_subject" maxlength="255"></label></p>
						<p><label><strong><?php esc_html_e( 'Message or summary', 'sustainable-catalyst-engagement-intake' ); ?></strong><textarea name="interaction_body" rows="8" maxlength="50000" required></textarea></label></p>
						<p><label><strong><?php esc_html_e( 'Privacy', 'sustainable-catalyst-engagement-intake' ); ?></strong><select name="interaction_privacy"><?php foreach ( SC_EI_Communication_Schema::privacy_levels() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label></p>
						<p><label class="sc-ei-review-checkbox"><input type="checkbox" name="interaction_needs_response" value="1"><span><?php esc_html_e( 'This inbound interaction needs a response', 'sustainable-catalyst-engagement-intake' ); ?></span></label></p>
						<button type="submit" class="button"><?php esc_html_e( 'Record Interaction', 'sustainable-catalyst-engagement-intake' ); ?></button>
					</form>
				</section>
			<?php endif; ?>

			<section class="sc-ei-admin__card">
				<h2><?php esc_html_e( 'Inquiry Context', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<dl class="sc-ei-admin__details">
					<dt><?php esc_html_e( 'Subject', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $inquiry['subject'] ); ?></dd>
					<dt><?php esc_html_e( 'Preferred contact', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Teams::label( SC_EI_Teams::contact_methods(), $inquiry['preferred_contact_method'] ) ); ?></dd>
					<dt><?php esc_html_e( 'Teams request', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Teams::label( SC_EI_Teams::meeting_requests(), $inquiry['meeting_request'] ) ); ?> · <?php echo esc_html( SC_EI_Teams::label( SC_EI_Teams::scheduling_statuses(), $inquiry['scheduling_status'] ) ); ?></dd>
					<dt><?php esc_html_e( 'Review stage', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Review_Schema::label( SC_EI_Review_Schema::stages(), $inquiry['review_stage'] ) ); ?></dd>
					<dt><?php esc_html_e( 'Human fit decision', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Review_Schema::label( SC_EI_Review_Schema::fit_decisions(), $inquiry['fit_decision'] ) ); ?></dd>
					<dt><?php esc_html_e( 'Recommended next step', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Review_Schema::label( SC_EI_Review_Schema::next_steps(), $inquiry['recommended_next_step'] ) ); ?></dd>
				</dl>
			</section>
		</aside>
	</div>
</div>
