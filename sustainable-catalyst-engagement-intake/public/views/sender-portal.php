<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$result_messages = array(
	'portal_activated'               => __( 'Secure portal access activated.', 'sustainable-catalyst-engagement-intake' ),
	'portal_message_sent'            => __( 'Your secure message was recorded. It was not sent through ordinary email.', 'sustainable-catalyst-engagement-intake' ),
	'portal_documents_uploaded'      => __( 'Accepted documents were placed in protected quarantine for review.', 'sustainable-catalyst-engagement-intake' ),
	'portal_contact_updated'         => __( 'Contact preferences were updated.', 'sustainable-catalyst-engagement-intake' ),
	'portal_scheduling_updated'      => __( 'Microsoft Teams scheduling preferences were updated. No meeting was booked automatically.', 'sustainable-catalyst-engagement-intake' ),
	'portal_meeting_response_saved'  => __( 'Your meeting response was recorded. No external calendar event was created automatically.', 'sustainable-catalyst-engagement-intake' ),
	'portal_proposal_response_saved' => __( 'Your proposal response was recorded. Acceptance is pending external contracting and is not an electronic signature.', 'sustainable-catalyst-engagement-intake' ),
	'portal_privacy_request_created' => __( 'Your privacy request was recorded for identity and human review.', 'sustainable-catalyst-engagement-intake' ),
	'portal_withdrawal_requested'    => __( 'Your withdrawal request was recorded. It does not erase records or bypass legal holds.', 'sustainable-catalyst-engagement-intake' ),
	'portal_withdrawal_canceled'     => __( 'The pending withdrawal request was canceled.', 'sustainable-catalyst-engagement-intake' ),
);
$error_messages = array(
	'portal_permission_denied'              => __( 'This invitation does not permit that action.', 'sustainable-catalyst-engagement-intake' ),
	'portal_privacy_restriction'            => __( 'The current privacy state blocks that action.', 'sustainable-catalyst-engagement-intake' ),
	'portal_csrf_failed'                    => __( 'The security token was not valid. Reload the page and try again.', 'sustainable-catalyst-engagement-intake' ),
	'portal_rate_limited'                   => __( 'Too many secure portal actions were submitted in a short period. Try again later.', 'sustainable-catalyst-engagement-intake' ),
	'portal_message_body_required'          => __( 'Write a message before submitting.', 'sustainable-catalyst-engagement-intake' ),
	'portal_message_save_failed'            => __( 'The secure message could not be recorded.', 'sustainable-catalyst-engagement-intake' ),
	'portal_upload_rejected'                => __( 'No document was accepted. Review file type, size, and scanner requirements.', 'sustainable-catalyst-engagement-intake' ),
	'portal_no_documents'                   => __( 'Select at least one document.', 'sustainable-catalyst-engagement-intake' ),
	'portal_update_conflict'                => __( 'The inquiry changed before the update was saved. Reload and try again.', 'sustainable-catalyst-engagement-intake' ),
	'portal_timezone_invalid'               => __( 'Choose a valid IANA timezone.', 'sustainable-catalyst-engagement-intake' ),
	'portal_teams_email_invalid'            => __( 'Enter a valid Microsoft Teams email.', 'sustainable-catalyst-engagement-intake' ),
	'portal_calendar_consent_required'      => __( 'Calendar invitation permission is required when requesting a Teams meeting.', 'sustainable-catalyst-engagement-intake' ),
	'privacy_request_summary_required'      => __( 'Describe the privacy request.', 'sustainable-catalyst-engagement-intake' ),
	'portal_withdrawal_reason_required'     => __( 'Explain the withdrawal request briefly.', 'sustainable-catalyst-engagement-intake' ),
	'portal_withdrawal_confirmation_failed' => __( 'The withdrawal confirmation did not match.', 'sustainable-catalyst-engagement-intake' ),
	'portal_revoke_confirmation_failed'     => __( 'The access-revocation confirmation did not match.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_meeting_unavailable'            => __( 'This meeting offer is unavailable or no longer open.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_meeting_expired'                => __( 'This meeting offer expired.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_slot_invalid'                   => __( 'Choose one of the offered meeting times.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_slot_elapsed'                   => __( 'That proposed meeting time has already passed.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_alternative_note_required'      => __( 'Describe the alternative time you need.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_meeting_response_invalid'       => __( 'Choose a valid meeting response.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_ics_unavailable'                => __( 'The calendar file is not available for this meeting.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_proposal_unavailable'           => __( 'This proposal is unavailable or no longer open.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_proposal_expired'               => __( 'This proposal expired.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_proposal_response_disabled'     => __( 'Proposal responses are temporarily disabled.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_proposal_confirmation_failed'   => __( 'The typed proposal confirmation did not match.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_proposal_authority_required'    => __( 'Confirm that you are authorized to respond for the sender organization.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_proposal_boundary_required'     => __( 'Acknowledge that portal acceptance is not an executed contract.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_proposal_note_required'         => __( 'Add a brief response note.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_proposal_response_invalid'      => __( 'Choose a valid proposal response.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_proposal_conflict'              => __( 'The proposal changed before your response was recorded. Reload and try again.', 'sustainable-catalyst-engagement-intake' ),
);
$weekdays = json_decode( (string) $inquiry['preferred_weekdays'], true ) ?: array();
$participant_emails = json_decode( (string) $inquiry['participant_emails'], true ) ?: array();
$relevant_links = json_decode( (string) $inquiry['relevant_links'], true ) ?: array();
$permissions = array_fill_keys( (array) $context['permissions'], true );
$privacy_restricted = in_array( $inquiry['privacy_status'], array( 'restricted', 'erasure_requested' ), true );
$scheduled = 'scheduled' === $inquiry['scheduling_status']
	&& ! empty( $inquiry['scheduled_start_utc'] )
	&& SC_EI_Teams::is_teams_url( (string) $inquiry['teams_meeting_url'] );
?>
<section class="sc-ei-portal sc-ei-portal--authenticated">
	<header class="sc-ei-portal__hero">
		<div>
			<p class="sc-ei-portal__eyebrow"><?php esc_html_e( 'Private Engagement Workspace', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<h2><?php echo esc_html( $atts['title'] ); ?></h2>
			<p><?php echo esc_html( sprintf( __( 'Inquiry reference %s', 'sustainable-catalyst-engagement-intake' ), $inquiry['reference'] ) ); ?></p>
		</div>
		<div class="sc-ei-portal__status">
			<span><?php esc_html_e( 'Sender-safe status', 'sustainable-catalyst-engagement-intake' ); ?></span>
			<strong><?php echo esc_html( SC_EI_Portal_Schema::public_status_label( (string) $inquiry['status'] ) ); ?></strong>
		</div>
	</header>

	<?php if ( isset( $result_messages[ $result_code ] ) ) : ?>
		<div class="sc-ei-portal-notice sc-ei-portal-notice--success" role="status"><?php echo esc_html( $result_messages[ $result_code ] ); ?></div>
	<?php endif; ?>
	<?php if ( $error_code ) : ?>
		<div class="sc-ei-portal-notice sc-ei-portal-notice--error" role="alert"><?php echo esc_html( $error_messages[ $error_code ] ?? __( 'The secure portal action could not be completed.', 'sustainable-catalyst-engagement-intake' ) ); ?></div>
	<?php endif; ?>
	<?php if ( $privacy_restricted ) : ?>
		<div class="sc-ei-portal-notice sc-ei-portal-notice--warning"><strong><?php esc_html_e( 'Processing restriction active.', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'Status viewing, existing secure messages, meeting, proposal, and engagement records, privacy requests, and access revocation remain available. New messages, responses, documents, and preference changes are blocked.', 'sustainable-catalyst-engagement-intake' ); ?></div>
	<?php endif; ?>

	<nav class="sc-ei-portal-nav" aria-label="<?php esc_attr_e( 'Sender portal sections', 'sustainable-catalyst-engagement-intake' ); ?>">
		<?php foreach ( SC_EI_Portal_Schema::views() as $key => $label ) : ?>
			<?php
			$permission_map = array(
				'overview' => 'view_status',
				'messages' => 'view_messages',
				'documents' => 'view_documents',
				'meetings' => 'view_meetings',
				'proposals' => 'view_proposals',
				'engagement' => 'view_engagements',
				'preferences' => 'update_contact',
				'privacy' => 'privacy_requests',
				'access' => 'revoke_access',
			);
			if ( empty( $permissions[ $permission_map[ $key ] ] ) && ! in_array( $key, array( 'overview', 'access' ), true ) ) {
				continue;
			}
			?>
			<a class="<?php echo $view === $key ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'portal_view', $key, $portal_url ) ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</nav>

	<?php if ( 'overview' === $view ) : ?>
		<div class="sc-ei-portal-grid">
			<main>
				<section class="sc-ei-portal-card">
					<h3><?php esc_html_e( 'Inquiry overview', 'sustainable-catalyst-engagement-intake' ); ?></h3>
					<dl class="sc-ei-portal-details">
						<dt><?php esc_html_e( 'Reference', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $inquiry['reference'] ); ?></dd>
						<dt><?php esc_html_e( 'Submitted', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( get_date_from_gmt( $inquiry['created_at'], 'M j, Y' ) ); ?></dd>
						<dt><?php esc_html_e( 'Inquiry type', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( ucwords( str_replace( '_', ' ', $inquiry['inquiry_type'] ) ) ); ?></dd>
						<dt><?php esc_html_e( 'Service interest', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $inquiry['service_interest'] ? ucwords( str_replace( '_', ' ', $inquiry['service_interest'] ) ) : __( 'Not specified', 'sustainable-catalyst-engagement-intake' ) ); ?></dd>
						<dt><?php esc_html_e( 'Engagement stage', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $lifecycle_snapshot['label'] ?? __( 'Under Review', 'sustainable-catalyst-engagement-intake' ) ); ?></dd>
						<?php if ( ! empty( $lifecycle_snapshot['next_step'] ) ) : ?><dt><?php esc_html_e( 'Published next step', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $lifecycle_snapshot['next_step'] ); ?></dd><?php endif; ?>
						<dt><?php esc_html_e( 'Secure messages', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( number_format_i18n( count( $messages ) ) ); ?></dd>
						<dt><?php esc_html_e( 'Active private documents', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( number_format_i18n( count( $attachments ) ) ); ?></dd>
						<dt><?php esc_html_e( 'Meeting records', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( number_format_i18n( count( $meeting_offers ) ) ); ?></dd>
						<dt><?php esc_html_e( 'Proposal records', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( number_format_i18n( count( $proposals ) ) ); ?></dd>
						<dt><?php esc_html_e( 'Withdrawal state', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Portal_Schema::label( SC_EI_Portal_Schema::withdrawal_statuses(), $inquiry['sender_withdrawal_status'] ) ); ?></dd>
					</dl>
					<?php if ( ! empty( $lifecycle_snapshot['summary'] ) ) : ?>
						<div class="sc-ei-portal-notice sc-ei-portal-notice--info"><strong><?php esc_html_e( 'Current update', 'sustainable-catalyst-engagement-intake' ); ?></strong><br><?php echo nl2br( esc_html( $lifecycle_snapshot['summary'] ) ); ?></div>
					<?php endif; ?>
					<?php if ( ! empty( $support_snapshot ) ) : ?>
						<section class="sc-ei-portal-notice sc-ei-portal-notice--info" aria-labelledby="sc-ei-support-case-title">
							<h4 id="sc-ei-support-case-title"><?php esc_html_e( 'Product support case', 'sustainable-catalyst-engagement-intake' ); ?></h4>
							<dl class="sc-ei-portal-details">
								<dt><?php esc_html_e( 'Case', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $support_snapshot['case_number'] ); ?></dd>
								<dt><?php esc_html_e( 'Product', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $support_snapshot['product'] ); ?><?php echo ! empty( $support_snapshot['version'] ) ? ' · ' . esc_html( $support_snapshot['version'] ) : ''; ?></dd>
								<?php if ( ! empty( $support_snapshot['component'] ) ) : ?><dt><?php esc_html_e( 'Component', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $support_snapshot['component'] ); ?></dd><?php endif; ?>
								<dt><?php esc_html_e( 'Support status', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $support_snapshot['status'] ); ?></dd>
								<?php if ( ! empty( $support_snapshot['known_issue'] ) ) : ?><dt><?php esc_html_e( 'Known issue', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $support_snapshot['known_issue'] ); ?></dd><?php endif; ?>
							</dl>
							<?php if ( ! empty( $support_snapshot['summary'] ) ) : ?><p><strong><?php esc_html_e( 'Approved update', 'sustainable-catalyst-engagement-intake' ); ?></strong><br><?php echo nl2br( esc_html( $support_snapshot['summary'] ) ); ?></p><?php endif; ?>
							<?php if ( ! empty( $support_snapshot['next_step'] ) ) : ?><p><strong><?php esc_html_e( 'Next step', 'sustainable-catalyst-engagement-intake' ); ?></strong><br><?php echo nl2br( esc_html( $support_snapshot['next_step'] ) ); ?></p><?php endif; ?>
							<?php if ( ! empty( $support_snapshot['links'] ) ) : ?>
								<ul>
									<?php foreach ( $support_snapshot['links'] as $support_link ) : ?>
										<li><?php echo esc_html( $support_link['title'] ?: ucwords( str_replace( '_', ' ', $support_link['related_type'] ) ) ); ?>: <?php echo esc_html( $support_link['related_reference'] ); ?></li>
									<?php endforeach; ?>
								</ul>
							<?php endif; ?>
						</section>
					<?php endif; ?>
					<?php if ( $inquiry['project_summary'] ) : ?><h4><?php esc_html_e( 'Submitted project summary', 'sustainable-catalyst-engagement-intake' ); ?></h4><p><?php echo nl2br( esc_html( $inquiry['project_summary'] ) ); ?></p><?php endif; ?>
				</section>

				<?php if ( $scheduled ) : ?>
					<section class="sc-ei-portal-card sc-ei-portal-card--meeting">
						<h3><?php esc_html_e( 'Microsoft Teams meeting', 'sustainable-catalyst-engagement-intake' ); ?></h3>
						<p><strong><?php echo esc_html( SC_EI_Teams::format_utc_for_input( $inquiry['scheduled_start_utc'], $inquiry['scheduled_timezone'] ?: $inquiry['timezone'] ) ); ?></strong></p>
						<p><?php echo esc_html( $inquiry['scheduled_timezone'] ?: $inquiry['timezone'] ); ?></p>
						<p><a class="sc-ei-button sc-ei-button--primary" href="<?php echo esc_url( $inquiry['teams_meeting_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open Microsoft Teams', 'sustainable-catalyst-engagement-intake' ); ?></a></p>
					</section>
				<?php endif; ?>

				<?php if ( $messages ) : ?>
					<section class="sc-ei-portal-card">
						<h3><?php esc_html_e( 'Latest secure message', 'sustainable-catalyst-engagement-intake' ); ?></h3>
						<?php $latest = end( $messages ); ?>
						<p class="sc-ei-portal-message__meta"><?php echo 'inbound' === $latest['direction'] ? esc_html__( 'You', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'Sustainable Catalyst', 'sustainable-catalyst-engagement-intake' ); ?> · <?php echo esc_html( get_date_from_gmt( $latest['occurred_at'] ?: $latest['created_at'], 'M j, Y g:i a' ) ); ?></p>
						<p><?php echo nl2br( esc_html( $latest['body_text'] ) ); ?></p>
						<a href="<?php echo esc_url( add_query_arg( 'portal_view', 'messages', $portal_url ) ); ?>"><?php esc_html_e( 'Open secure message history', 'sustainable-catalyst-engagement-intake' ); ?></a>
					</section>
				<?php endif; ?>
			</main>
			<aside>
				<section class="sc-ei-portal-card">
					<h3><?php esc_html_e( 'Portal boundary', 'sustainable-catalyst-engagement-intake' ); ?></h3>
					<p><?php esc_html_e( 'This portal shows sender-safe records only. Internal review notes, fit assessments, audit narratives, legal-hold details, administrative assignments, and private operational reasoning are never exposed here.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				</section>
				<section class="sc-ei-portal-card">
					<h3><?php esc_html_e( 'No automatic commitments', 'sustainable-catalyst-engagement-intake' ); ?></h3>
					<p><?php esc_html_e( 'A message, document, scheduling preference, or portal status does not create an engagement, book a meeting, approve scope, or authorize paid work.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				</section>
			</aside>
		</div>
	<?php elseif ( 'messages' === $view ) : ?>
		<section class="sc-ei-portal-card">
			<h3><?php esc_html_e( 'Secure message history', 'sustainable-catalyst-engagement-intake' ); ?></h3>
			<div class="sc-ei-portal-thread">
				<?php if ( ! $messages ) : ?><p><?php esc_html_e( 'No secure portal messages have been published yet.', 'sustainable-catalyst-engagement-intake' ); ?></p><?php endif; ?>
				<?php foreach ( $messages as $message ) : ?>
					<article class="sc-ei-portal-message sc-ei-portal-message--<?php echo esc_attr( $message['direction'] ); ?>">
						<p class="sc-ei-portal-message__meta"><strong><?php echo 'inbound' === $message['direction'] ? esc_html__( 'You', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'Sustainable Catalyst', 'sustainable-catalyst-engagement-intake' ); ?></strong> · <?php echo esc_html( get_date_from_gmt( $message['occurred_at'] ?: $message['created_at'], 'M j, Y g:i a' ) ); ?></p>
						<div><?php echo nl2br( esc_html( $message['body_text'] ) ); ?></div>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php if ( ! empty( $permissions['send_messages'] ) && ! $privacy_restricted && ! empty( $settings['portal_allow_messages'] ) ) : ?>
			<section class="sc-ei-portal-card">
				<h3><?php esc_html_e( 'Send a secure message', 'sustainable-catalyst-engagement-intake' ); ?></h3>
				<p><?php esc_html_e( 'This message stays in the private portal and Communication History. It is not sent as ordinary email.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-form">
					<input type="hidden" name="action" value="sc_ei_portal_send_message">
					<input type="hidden" name="portal_csrf" value="<?php echo esc_attr( $csrf_token ); ?>">
					<label><span><?php esc_html_e( 'Message', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="portal_message" rows="7" maxlength="50000" required></textarea></label>
					<button type="submit" class="sc-ei-button sc-ei-button--primary"><?php esc_html_e( 'Record Secure Message', 'sustainable-catalyst-engagement-intake' ); ?></button>
				</form>
			</section>
		<?php endif; ?>
	<?php elseif ( 'documents' === $view ) : ?>
		<section class="sc-ei-portal-card">
			<h3><?php esc_html_e( 'Private document record', 'sustainable-catalyst-engagement-intake' ); ?></h3>
			<p><?php esc_html_e( 'The portal displays document metadata only. Private files are never placed in the public Media Library and are not returned through public download links.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<div class="sc-ei-portal-documents">
				<?php if ( ! $attachments ) : ?><p><?php esc_html_e( 'No active private documents are recorded.', 'sustainable-catalyst-engagement-intake' ); ?></p><?php endif; ?>
				<?php foreach ( $attachments as $attachment ) : ?>
					<article>
						<strong><?php echo esc_html( $attachment['original_name'] ); ?></strong>
						<span><?php echo esc_html( size_format( absint( $attachment['size_bytes'] ), 2 ) ); ?> · <?php echo esc_html( get_date_from_gmt( $attachment['uploaded_at'], 'M j, Y' ) ); ?></span>
						<span><?php echo 'rejected' === $attachment['quarantine_status'] ? esc_html__( 'Rejected and not retained', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'Stored in protected review workflow', 'sustainable-catalyst-engagement-intake' ); ?></span>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
		<?php if ( ! empty( $permissions['upload_documents'] ) && ! $privacy_restricted && ! empty( $settings['portal_allow_documents'] ) ) : ?>
			<section class="sc-ei-portal-card">
				<h3><?php esc_html_e( 'Upload follow-up documents', 'sustainable-catalyst-engagement-intake' ); ?></h3>
				<p><?php echo esc_html( sprintf( __( 'Up to %1$d files, %2$s each, %3$s combined. Allowed: %4$s.', 'sustainable-catalyst-engagement-intake' ), $effective_upload_limits['max_files'], size_format( $effective_upload_limits['max_file_bytes'], 0 ), size_format( $effective_upload_limits['max_total_bytes'], 0 ), implode( ', ', $upload_extensions ) ) ); ?></p>
				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-form">
					<input type="hidden" name="action" value="sc_ei_portal_upload_documents">
					<input type="hidden" name="portal_csrf" value="<?php echo esc_attr( $csrf_token ); ?>">
					<label><span><?php esc_html_e( 'Document category', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="document_category"><option value="supporting_document"><?php esc_html_e( 'Supporting document', 'sustainable-catalyst-engagement-intake' ); ?></option><option value="brief"><?php esc_html_e( 'Brief or scope', 'sustainable-catalyst-engagement-intake' ); ?></option><option value="data"><?php esc_html_e( 'Data or evidence', 'sustainable-catalyst-engagement-intake' ); ?></option><option value="other"><?php esc_html_e( 'Other', 'sustainable-catalyst-engagement-intake' ); ?></option></select></label>
					<label><span><?php esc_html_e( 'Confidentiality', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="document_confidentiality"><option value="confidential"><?php esc_html_e( 'Confidential', 'sustainable-catalyst-engagement-intake' ); ?></option><option value="restricted"><?php esc_html_e( 'Restricted', 'sustainable-catalyst-engagement-intake' ); ?></option><option value="non_confidential"><?php esc_html_e( 'Non-confidential', 'sustainable-catalyst-engagement-intake' ); ?></option></select></label>
					<label><span><?php esc_html_e( 'Document notes', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="document_notes" rows="3" maxlength="5000"></textarea></label>
					<label><span><?php esc_html_e( 'Files', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="file" name="documents[]" multiple required accept="<?php echo esc_attr( implode( ',', array_map( static fn( string $ext ): string => '.' . $ext, $upload_extensions ) ) ); ?>"></label>
					<div class="sc-ei-portal-notice sc-ei-portal-notice--warning"><?php esc_html_e( 'Do not upload passwords, payment-card data, regulated health records, export-controlled material, highly sensitive personal data, or files you are not authorized to share.', 'sustainable-catalyst-engagement-intake' ); ?></div>
					<button type="submit" class="sc-ei-button sc-ei-button--primary"><?php esc_html_e( 'Upload to Protected Quarantine', 'sustainable-catalyst-engagement-intake' ); ?></button>
				</form>
			</section>
		<?php endif; ?>
	<?php elseif ( 'meetings' === $view ) : ?>
		<section class="sc-ei-portal-card">
			<h3><?php esc_html_e( 'Microsoft Teams meetings', 'sustainable-catalyst-engagement-intake' ); ?></h3>
			<p><?php esc_html_e( 'Sustainable Catalyst may offer human-approved times. Selecting a time records your choice; it does not create an external Microsoft calendar event automatically.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<?php if ( ! $meeting_offers ) : ?><p><?php esc_html_e( 'No meeting offers or scheduled meetings are available.', 'sustainable-catalyst-engagement-intake' ); ?></p><?php endif; ?>
			<div class="sc-ei-workflow-portal-list">
				<?php foreach ( $meeting_offers as $meeting ) : $slots = json_decode( (string) $meeting['slots_json'], true ) ?: array(); ?>
					<article class="sc-ei-portal-card sc-ei-portal-workflow-card">
						<header class="sc-ei-portal-workflow-header">
							<div><p class="sc-ei-portal__eyebrow"><?php echo esc_html( $meeting['offer_number'] ); ?></p><h4><?php echo esc_html( $meeting['title'] ); ?></h4></div>
							<span class="sc-ei-portal-workflow-state"><?php echo esc_html( SC_EI_Workflow_Schema::label( SC_EI_Workflow_Schema::meeting_statuses(), $meeting['status'] ) ); ?></span>
						</header>
						<p><?php echo nl2br( esc_html( $meeting['purpose'] ) ); ?></p>
						<dl class="sc-ei-portal-details">
							<dt><?php esc_html_e( 'Duration', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( absint( $meeting['duration_minutes'] ) . ' minutes' ); ?></dd>
							<dt><?php esc_html_e( 'Timezone', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $meeting['timezone'] ); ?></dd>
							<dt><?php esc_html_e( 'Offer expires', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( get_date_from_gmt( $meeting['expires_at'], 'M j, Y g:i a' ) . ' UTC' ); ?></dd>
						</dl>
						<?php if ( 'offered' === $meeting['status'] && ! $privacy_restricted && ! empty( $permissions['respond_meetings'] ) ) : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-form sc-ei-portal-meeting-response">
								<input type="hidden" name="action" value="sc_ei_portal_respond_meeting"><input type="hidden" name="portal_csrf" value="<?php echo esc_attr( $csrf_token ); ?>"><input type="hidden" name="meeting_offer_id" value="<?php echo esc_attr( $meeting['id'] ); ?>">
								<fieldset><legend><?php esc_html_e( 'Choose a proposed time', 'sustainable-catalyst-engagement-intake' ); ?></legend>
									<?php foreach ( $slots as $slot ) : ?>
										<label class="sc-ei-portal-slot"><input type="radio" name="meeting_slot_key" value="<?php echo esc_attr( $slot['key'] ); ?>"><span><strong><?php echo esc_html( SC_EI_Teams::format_utc_for_input( $slot['start_utc'], $meeting['timezone'] ) ); ?></strong><small><?php echo esc_html( $meeting['timezone'] ); ?></small></span></label>
									<?php endforeach; ?>
								</fieldset>
								<label><span><?php esc_html_e( 'Response', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="meeting_response" data-sc-ei-meeting-response><?php foreach ( SC_EI_Workflow_Schema::sender_meeting_responses() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
								<label><span><?php esc_html_e( 'Note or alternative time request', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="meeting_note" rows="4" maxlength="5000"></textarea></label>
								<button type="submit" class="sc-ei-button sc-ei-button--primary"><?php esc_html_e( 'Record Meeting Response', 'sustainable-catalyst-engagement-intake' ); ?></button>
							</form>
						<?php elseif ( in_array( $meeting['status'], array( 'accepted_pending_link', 'scheduled' ), true ) ) : ?>
							<div class="sc-ei-portal-notice sc-ei-portal-notice--success">
								<strong><?php esc_html_e( 'Selected time:', 'sustainable-catalyst-engagement-intake' ); ?></strong>
								<?php echo esc_html( SC_EI_Teams::format_utc_for_input( $meeting['selected_start_utc'], $meeting['timezone'] ) . ' · ' . $meeting['timezone'] ); ?>
							</div>
							<?php if ( 'scheduled' === $meeting['status'] && SC_EI_Teams::is_teams_url( (string) $meeting['teams_url'] ) ) : ?>
								<div class="sc-ei-portal-action-row">
									<a class="sc-ei-button sc-ei-button--primary" href="<?php echo esc_url( $meeting['teams_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open Microsoft Teams', 'sustainable-catalyst-engagement-intake' ); ?></a>
									<?php if ( ! empty( $workflow_settings['workflow_allow_sender_ics'] ) ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="sc_ei_portal_download_meeting_ics"><input type="hidden" name="portal_csrf" value="<?php echo esc_attr( $csrf_token ); ?>"><input type="hidden" name="meeting_offer_id" value="<?php echo esc_attr( $meeting['id'] ); ?>"><button type="submit" class="sc-ei-button"><?php esc_html_e( 'Download Calendar File', 'sustainable-catalyst-engagement-intake' ); ?></button></form><?php endif; ?>
								</div>
							<?php else : ?>
								<p><?php esc_html_e( 'Your selected time is recorded. Sustainable Catalyst must add and finalize the Microsoft Teams link.', 'sustainable-catalyst-engagement-intake' ); ?></p>
							<?php endif; ?>
						<?php elseif ( 'alternative_requested' === $meeting['status'] ) : ?>
							<div class="sc-ei-portal-notice sc-ei-portal-notice--warning"><strong><?php esc_html_e( 'Alternative requested.', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo nl2br( esc_html( $meeting['alternative_request'] ) ); ?></div>
						<?php elseif ( 'declined' === $meeting['status'] ) : ?>
							<p><?php esc_html_e( 'You declined this meeting offer.', 'sustainable-catalyst-engagement-intake' ); ?></p>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
	<?php elseif ( 'proposals' === $view ) : ?>
		<section class="sc-ei-portal-card">
			<h3><?php esc_html_e( 'Proposals', 'sustainable-catalyst-engagement-intake' ); ?></h3>
			<div class="sc-ei-portal-notice sc-ei-portal-notice--warning"><strong><?php esc_html_e( 'Important boundary:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'Portal acceptance records intent to proceed to contracting. It is not an electronic signature, executed contract, payment authorization, or active engagement.', 'sustainable-catalyst-engagement-intake' ); ?></div>
			<?php if ( ! $proposals ) : ?><p><?php esc_html_e( 'No proposals are available.', 'sustainable-catalyst-engagement-intake' ); ?></p><?php endif; ?>
			<div class="sc-ei-workflow-portal-list">
				<?php foreach ( $proposals as $proposal ) : $scope = json_decode( (string) $proposal['scope_json'], true ) ?: array(); $deliverables = json_decode( (string) $proposal['deliverables_json'], true ) ?: array(); $exclusions = json_decode( (string) $proposal['exclusions_json'], true ) ?: array(); $assumptions = json_decode( (string) $proposal['assumptions_json'], true ) ?: array(); ?>
					<article class="sc-ei-portal-card sc-ei-portal-workflow-card">
						<header class="sc-ei-portal-workflow-header">
							<div><p class="sc-ei-portal__eyebrow"><?php echo esc_html( $proposal['proposal_number'] . ' · v' . absint( $proposal['version_number'] ) ); ?></p><h4><?php echo esc_html( $proposal['title'] ); ?></h4></div>
							<span class="sc-ei-portal-workflow-state"><?php echo esc_html( SC_EI_Workflow_Schema::label( SC_EI_Workflow_Schema::proposal_statuses(), $proposal['status'] ) ); ?></span>
						</header>
						<p><?php echo nl2br( esc_html( $proposal['executive_summary'] ) ); ?></p>
						<dl class="sc-ei-portal-details"><dt><?php esc_html_e( 'Value', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Workflow_Schema::money_display( absint( $proposal['total_minor'] ), $proposal['currency'] ) ); ?></dd><dt><?php esc_html_e( 'Expires', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( get_date_from_gmt( $proposal['expires_at'], 'M j, Y g:i a' ) . ' UTC' ); ?></dd><dt><?php esc_html_e( 'Content integrity', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><code><?php echo esc_html( substr( $proposal['content_hash'], 0, 16 ) ); ?>…</code></dd></dl>
						<div class="sc-ei-portal-proposal-columns">
							<div><h5><?php esc_html_e( 'Scope', 'sustainable-catalyst-engagement-intake' ); ?></h5><ul><?php foreach ( $scope as $item ) : ?><li><?php echo esc_html( $item ); ?></li><?php endforeach; ?></ul></div>
							<div><h5><?php esc_html_e( 'Deliverables', 'sustainable-catalyst-engagement-intake' ); ?></h5><ul><?php foreach ( $deliverables as $item ) : ?><li><?php echo esc_html( $item ); ?></li><?php endforeach; ?></ul></div>
						</div>
						<?php if ( $exclusions ) : ?><details><summary><?php esc_html_e( 'Exclusions', 'sustainable-catalyst-engagement-intake' ); ?></summary><ul><?php foreach ( $exclusions as $item ) : ?><li><?php echo esc_html( $item ); ?></li><?php endforeach; ?></ul></details><?php endif; ?>
						<?php if ( $assumptions ) : ?><details><summary><?php esc_html_e( 'Assumptions', 'sustainable-catalyst-engagement-intake' ); ?></summary><ul><?php foreach ( $assumptions as $item ) : ?><li><?php echo esc_html( $item ); ?></li><?php endforeach; ?></ul></details><?php endif; ?>
						<?php foreach ( array( 'timeline_text' => __( 'Timeline', 'sustainable-catalyst-engagement-intake' ), 'fee_summary' => __( 'Fee summary', 'sustainable-catalyst-engagement-intake' ), 'payment_terms' => __( 'Payment terms', 'sustainable-catalyst-engagement-intake' ), 'legal_terms' => __( 'Terms and boundaries', 'sustainable-catalyst-engagement-intake' ) ) as $field => $label ) : if ( empty( $proposal[ $field ] ) ) continue; ?><details><summary><?php echo esc_html( $label ); ?></summary><p><?php echo nl2br( esc_html( $proposal[ $field ] ) ); ?></p></details><?php endforeach; ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-print-form"><input type="hidden" name="action" value="sc_ei_portal_print_proposal"><input type="hidden" name="portal_csrf" value="<?php echo esc_attr( $csrf_token ); ?>"><input type="hidden" name="proposal_id" value="<?php echo esc_attr( $proposal['id'] ); ?>"><button type="submit" class="sc-ei-button"><?php esc_html_e( 'Open Print View', 'sustainable-catalyst-engagement-intake' ); ?></button></form>
						<?php if ( 'published' === $proposal['status'] && ! $privacy_restricted && ! empty( $permissions['respond_proposals'] ) && ! empty( $workflow_settings['workflow_allow_proposal_acceptance'] ) ) : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-form sc-ei-portal-proposal-response" data-proposal-number="<?php echo esc_attr( strtoupper( $proposal['proposal_number'] ) ); ?>">
								<input type="hidden" name="action" value="sc_ei_portal_respond_proposal"><input type="hidden" name="portal_csrf" value="<?php echo esc_attr( $csrf_token ); ?>"><input type="hidden" name="proposal_id" value="<?php echo esc_attr( $proposal['id'] ); ?>">
								<label><span><?php esc_html_e( 'Response', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="proposal_response" data-sc-ei-proposal-response><?php foreach ( SC_EI_Workflow_Schema::sender_proposal_responses() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
								<label><span><?php esc_html_e( 'Response note', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="proposal_response_note" rows="4" maxlength="5000"></textarea></label>
								<label class="sc-ei-check"><input type="checkbox" name="proposal_authority_attested" value="1"><span><?php esc_html_e( 'I am authorized to record this response for the sender organization.', 'sustainable-catalyst-engagement-intake' ); ?></span></label>
								<label class="sc-ei-check"><input type="checkbox" name="proposal_boundary_acknowledged" value="1"><span><?php esc_html_e( 'I understand acceptance is pending external contracting and is not a signature, contract, payment, or active engagement.', 'sustainable-catalyst-engagement-intake' ); ?></span></label>
								<label><span data-sc-ei-proposal-confirm-label><?php echo esc_html( 'Type ACCEPT ' . strtoupper( $proposal['proposal_number'] ) ); ?></span><input type="text" name="proposal_confirmation" required autocomplete="off"></label>
								<button type="submit" class="sc-ei-button sc-ei-button--primary"><?php esc_html_e( 'Record Proposal Response', 'sustainable-catalyst-engagement-intake' ); ?></button>
							</form>
						<?php elseif ( 'accepted_pending_contract' === $proposal['status'] ) : ?>
							<div class="sc-ei-portal-notice sc-ei-portal-notice--success"><strong><?php esc_html_e( 'Accepted for contracting.', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'No engagement is active until Sustainable Catalyst records an executed external contract.', 'sustainable-catalyst-engagement-intake' ); ?></div>
						<?php elseif ( 'contracted' === $proposal['status'] ) : ?>
							<div class="sc-ei-portal-notice sc-ei-portal-notice--success"><strong><?php esc_html_e( 'External contract recorded.', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'Refer to the separately executed agreement for binding terms.', 'sustainable-catalyst-engagement-intake' ); ?></div>
						<?php elseif ( 'declined' === $proposal['status'] ) : ?>
							<p><?php esc_html_e( 'You declined this proposal.', 'sustainable-catalyst-engagement-intake' ); ?></p>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
	<?php elseif ( 'engagement' === $view ) : ?>
		<section class="sc-ei-portal-card">
			<h3><?php esc_html_e( 'Engagement handoff and lifecycle', 'sustainable-catalyst-engagement-intake' ); ?></h3>
			<p><?php esc_html_e( 'This section shows sender-safe operational status. The separately executed agreement remains the binding commercial record. No invoice, payment, signature, or external project is created by this portal.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<div class="sc-ei-portal-engagement-list">
				<?php if ( empty( $engagements ) ) : ?><p><?php esc_html_e( 'No engagement handoff has been created for this inquiry.', 'sustainable-catalyst-engagement-intake' ); ?></p><?php endif; ?>
				<?php foreach ( $engagements as $engagement ) : ?>
					<article class="sc-ei-portal-engagement">
						<header>
							<div>
								<strong><?php echo esc_html( $engagement['engagement_number'] ); ?></strong>
								<span><?php echo esc_html( $engagement['title'] ); ?></span>
							</div>
							<span class="sc-ei-portal-status"><?php echo esc_html( SC_EI_Engagement_Schema::label( SC_EI_Engagement_Schema::statuses(), $engagement['status'] ) ); ?></span>
						</header>
						<?php if ( $engagement['sender_summary'] ) : ?><p><?php echo nl2br( esc_html( $engagement['sender_summary'] ) ); ?></p><?php endif; ?>
						<dl class="sc-ei-portal-details">
							<dt><?php esc_html_e( 'Contract reference', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $engagement['contract_reference'] ?: '—' ); ?></dd>
							<dt><?php esc_html_e( 'Engagement owner', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $engagement['owner_name'] ?: __( 'Being assigned', 'sustainable-catalyst-engagement-intake' ) ); ?></dd>
							<dt><?php esc_html_e( 'Proposed start', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $engagement['proposed_start_date'] ?: '—' ); ?></dd>
							<dt><?php esc_html_e( 'Target end', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $engagement['target_end_date'] ?: '—' ); ?></dd>
							<dt><?php esc_html_e( 'Kickoff', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Engagement_Schema::label( SC_EI_Engagement_Schema::kickoff_statuses(), $engagement['kickoff_status'] ) ); ?></dd>
							<dt><?php esc_html_e( 'Activated', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $engagement['activated_at'] ?: '—' ); ?></dd>
						</dl>
						<?php $visible_requirements = $engagement_requirements[ $engagement['id'] ] ?? array(); ?>
						<?php if ( $visible_requirements ) : ?>
							<h4><?php esc_html_e( 'Onboarding items visible to you', 'sustainable-catalyst-engagement-intake' ); ?></h4>
							<ul class="sc-ei-portal-engagement-requirements">
								<?php foreach ( $visible_requirements as $requirement ) : ?>
									<li><strong><?php echo esc_html( $requirement['title'] ); ?></strong><span><?php echo esc_html( SC_EI_Engagement_Schema::label( SC_EI_Engagement_Schema::requirement_statuses(), $requirement['status'] ) ); ?></span><?php if ( $requirement['due_date'] ) : ?><small><?php echo esc_html( sprintf( __( 'Due %s', 'sustainable-catalyst-engagement-intake' ), $requirement['due_date'] ) ); ?></small><?php endif; ?></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<?php if ( 'handoff_pending' === $engagement['status'] ) : ?><div class="sc-ei-portal-notice sc-ei-portal-notice--warning"><?php esc_html_e( 'A handoff record exists, but delivery has not been activated.', 'sustainable-catalyst-engagement-intake' ); ?></div><?php endif; ?>
						<?php if ( 'ready_for_setup' === $engagement['status'] ) : ?><div class="sc-ei-portal-notice sc-ei-portal-notice--warning"><?php esc_html_e( 'The handoff is ready for final internal activation. Work has not started automatically.', 'sustainable-catalyst-engagement-intake' ); ?></div><?php endif; ?>
						<?php if ( 'active' === $engagement['status'] ) : ?><div class="sc-ei-portal-notice sc-ei-portal-notice--success"><?php esc_html_e( 'This engagement is active under the separately executed agreement.', 'sustainable-catalyst-engagement-intake' ); ?></div><?php endif; ?>
						<?php if ( 'paused' === $engagement['status'] ) : ?><div class="sc-ei-portal-notice sc-ei-portal-notice--warning"><?php esc_html_e( 'This engagement is currently paused. Use secure messages for clarification.', 'sustainable-catalyst-engagement-intake' ); ?></div><?php endif; ?>
						<?php if ( 'completed' === $engagement['status'] ) : ?><div class="sc-ei-portal-notice sc-ei-portal-notice--success"><?php esc_html_e( 'This engagement is recorded as completed.', 'sustainable-catalyst-engagement-intake' ); ?></div><?php endif; ?>
						<?php if ( 'canceled' === $engagement['status'] ) : ?><div class="sc-ei-portal-notice sc-ei-portal-notice--warning"><?php esc_html_e( 'This engagement record is closed.', 'sustainable-catalyst-engagement-intake' ); ?></div><?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
	<?php elseif ( 'preferences' === $view ) : ?>
		<?php if ( ! $privacy_restricted && ! empty( $permissions['update_contact'] ) && ! empty( $settings['portal_allow_contact_updates'] ) ) : ?>
			<section class="sc-ei-portal-card">
				<h3><?php esc_html_e( 'Contact preferences', 'sustainable-catalyst-engagement-intake' ); ?></h3>
				<p><?php esc_html_e( 'The inquiry email cannot be changed in the portal because it is part of the access challenge. Contact Sustainable Catalyst for an email change and new invitation.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-form sc-ei-portal-form--grid">
					<input type="hidden" name="action" value="sc_ei_portal_update_contact"><input type="hidden" name="portal_csrf" value="<?php echo esc_attr( $csrf_token ); ?>"><input type="hidden" name="portal_version" value="<?php echo esc_attr( $inquiry['portal_version'] ); ?>">
					<label><span><?php esc_html_e( 'Name', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="contact_name" value="<?php echo esc_attr( $inquiry['contact_name'] ); ?>" required maxlength="191"></label>
					<label><span><?php esc_html_e( 'Organization', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="organization" value="<?php echo esc_attr( $inquiry['organization'] ); ?>" maxlength="191"></label>
					<label><span><?php esc_html_e( 'Role or title', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="role_title" value="<?php echo esc_attr( $inquiry['role_title'] ); ?>" maxlength="191"></label>
					<label><span><?php esc_html_e( 'Preferred contact method', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="preferred_contact_method"><?php foreach ( SC_EI_Teams::contact_methods() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $inquiry['preferred_contact_method'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
					<label><span><?php esc_html_e( 'Phone', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="phone_number" value="<?php echo esc_attr( $inquiry['phone_number'] ); ?>" maxlength="80"></label>
					<label><span><?php esc_html_e( 'City', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="city" value="<?php echo esc_attr( $inquiry['city'] ); ?>" maxlength="120"></label>
					<label><span><?php esc_html_e( 'Country', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="country" value="<?php echo esc_attr( $inquiry['country'] ); ?>" maxlength="120"></label>
					<label class="sc-ei-portal-form__wide"><span><?php esc_html_e( 'Relevant public links', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="relevant_links" rows="4"><?php echo esc_textarea( implode( "\n", $relevant_links ) ); ?></textarea></label>
					<p class="sc-ei-portal-form__wide"><button type="submit" class="sc-ei-button sc-ei-button--primary"><?php esc_html_e( 'Update Contact Preferences', 'sustainable-catalyst-engagement-intake' ); ?></button></p>
				</form>
			</section>
		<?php endif; ?>

		<?php if ( ! $privacy_restricted && ! empty( $permissions['update_scheduling'] ) && ! empty( $settings['portal_allow_scheduling_updates'] ) ) : ?>
			<section class="sc-ei-portal-card">
				<h3><?php esc_html_e( 'Microsoft Teams scheduling preferences', 'sustainable-catalyst-engagement-intake' ); ?></h3>
				<p><?php esc_html_e( 'Updating preferences does not approve or schedule a meeting. Only Microsoft Teams is supported.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-form sc-ei-portal-form--grid">
					<input type="hidden" name="action" value="sc_ei_portal_update_scheduling"><input type="hidden" name="portal_csrf" value="<?php echo esc_attr( $csrf_token ); ?>"><input type="hidden" name="portal_version" value="<?php echo esc_attr( $inquiry['portal_version'] ); ?>">
					<label><span><?php esc_html_e( 'Meeting request', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="meeting_request"><?php foreach ( SC_EI_Teams::meeting_requests() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $inquiry['meeting_request'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
					<label><span><?php esc_html_e( 'Microsoft Teams email', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="email" name="teams_email" value="<?php echo esc_attr( $inquiry['teams_email'] ); ?>" maxlength="191"></label>
					<label><span><?php esc_html_e( 'Timezone', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="timezone" value="<?php echo esc_attr( $inquiry['timezone'] ); ?>" list="sc-ei-portal-timezones" maxlength="120"><datalist id="sc-ei-portal-timezones"><?php foreach ( SC_EI_Teams::timezone_identifiers() as $timezone_id ) : ?><option value="<?php echo esc_attr( $timezone_id ); ?>"></option><?php endforeach; ?></datalist></label>
					<label><span><?php esc_html_e( 'Preferred duration', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="preferred_duration"><?php foreach ( SC_EI_Teams::duration_options() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( (string) $inquiry['preferred_duration'], (string) $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
					<fieldset class="sc-ei-portal-form__wide"><legend><?php esc_html_e( 'Preferred weekdays', 'sustainable-catalyst-engagement-intake' ); ?></legend><div class="sc-ei-portal-check-grid"><?php foreach ( SC_EI_Teams::weekdays() as $key => $label ) : ?><label><input type="checkbox" name="preferred_weekdays[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $weekdays, true ) ); ?>> <?php echo esc_html( $label ); ?></label><?php endforeach; ?></div></fieldset>
					<label class="sc-ei-portal-form__wide"><span><?php esc_html_e( 'Preferred time windows', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="preferred_time_windows" rows="4"><?php echo esc_textarea( $inquiry['preferred_time_windows'] ); ?></textarea></label>
					<label><span><?php esc_html_e( 'Participant count', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" name="participant_count" min="1" max="25" value="<?php echo esc_attr( max( 1, absint( $inquiry['participant_count'] ) ) ); ?>"></label>
					<label class="sc-ei-portal-form__wide"><span><?php esc_html_e( 'Participant emails', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="participant_emails" rows="3"><?php echo esc_textarea( implode( "\n", $participant_emails ) ); ?></textarea></label>
					<label class="sc-ei-portal-form__wide"><span><?php esc_html_e( 'Accessibility needs', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="accessibility_needs" rows="3"><?php echo esc_textarea( $inquiry['accessibility_needs'] ); ?></textarea></label>
					<label class="sc-ei-portal-form__wide"><span><?php esc_html_e( 'Scheduling notes', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="scheduling_notes" rows="3"><?php echo esc_textarea( $inquiry['scheduling_notes'] ); ?></textarea></label>
					<label class="sc-ei-check sc-ei-portal-form__wide"><input type="checkbox" name="calendar_invite_consent" value="1" <?php checked( $inquiry['calendar_invite_consent'], 1 ); ?>><span><?php esc_html_e( 'Sustainable Catalyst may send a Microsoft Teams calendar invitation if a meeting is approved.', 'sustainable-catalyst-engagement-intake' ); ?></span></label>
					<p class="sc-ei-portal-form__wide"><button type="submit" class="sc-ei-button sc-ei-button--primary"><?php esc_html_e( 'Update Scheduling Preferences', 'sustainable-catalyst-engagement-intake' ); ?></button></p>
				</form>
			</section>
		<?php endif; ?>
	<?php elseif ( 'privacy' === $view ) : ?>
		<?php if ( ! empty( $permissions['privacy_requests'] ) && ! empty( $settings['portal_allow_privacy_requests'] ) ) : ?>
			<section class="sc-ei-portal-card">
				<h3><?php esc_html_e( 'Submit a privacy request', 'sustainable-catalyst-engagement-intake' ); ?></h3>
				<p><?php esc_html_e( 'Portal authentication helps connect the request to this inquiry but does not complete identity verification. A human reviewer may request additional verification.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-form">
					<input type="hidden" name="action" value="sc_ei_portal_privacy_request"><input type="hidden" name="portal_csrf" value="<?php echo esc_attr( $csrf_token ); ?>">
					<label><span><?php esc_html_e( 'Request type', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="request_type"><?php foreach ( SC_EI_Privacy_Schema::request_types() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
					<label><span><?php esc_html_e( 'Request summary', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="request_summary" rows="6" required maxlength="12000"></textarea></label>
					<button type="submit" class="sc-ei-button sc-ei-button--primary"><?php esc_html_e( 'Record Privacy Request', 'sustainable-catalyst-engagement-intake' ); ?></button>
				</form>
			</section>
		<?php endif; ?>

		<?php if ( ! empty( $permissions['request_withdrawal'] ) && ! empty( $settings['portal_allow_withdrawal_requests'] ) ) : ?>
			<section class="sc-ei-portal-card sc-ei-portal-card--danger">
				<h3><?php esc_html_e( 'Inquiry withdrawal request', 'sustainable-catalyst-engagement-intake' ); ?></h3>
				<p><?php esc_html_e( 'Withdrawal stops the requested engagement workflow after human review. It does not automatically erase records, cancel legal holds, or override retention obligations.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-form">
					<input type="hidden" name="action" value="sc_ei_portal_withdrawal"><input type="hidden" name="portal_csrf" value="<?php echo esc_attr( $csrf_token ); ?>"><input type="hidden" name="portal_version" value="<?php echo esc_attr( $inquiry['portal_version'] ); ?>">
					<?php if ( 'requested' === $inquiry['sender_withdrawal_status'] ) : ?>
						<input type="hidden" name="withdrawal_action" value="cancel">
						<label><span><?php echo esc_html( sprintf( __( 'Type CANCEL %s', 'sustainable-catalyst-engagement-intake' ), strtoupper( $inquiry['reference'] ) ) ); ?></span><input type="text" name="withdrawal_confirmation" required autocomplete="off"></label>
						<button type="submit" class="sc-ei-button"><?php esc_html_e( 'Cancel Withdrawal Request', 'sustainable-catalyst-engagement-intake' ); ?></button>
					<?php else : ?>
						<input type="hidden" name="withdrawal_action" value="request">
						<label><span><?php esc_html_e( 'Reason', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="withdrawal_reason" rows="4" required maxlength="12000"></textarea></label>
						<label><span><?php echo esc_html( sprintf( __( 'Type WITHDRAW %s', 'sustainable-catalyst-engagement-intake' ), strtoupper( $inquiry['reference'] ) ) ); ?></span><input type="text" name="withdrawal_confirmation" required autocomplete="off"></label>
						<button type="submit" class="sc-ei-button sc-ei-button--danger"><?php esc_html_e( 'Request Inquiry Withdrawal', 'sustainable-catalyst-engagement-intake' ); ?></button>
					<?php endif; ?>
				</form>
			</section>
		<?php endif; ?>
	<?php elseif ( 'access' === $view ) : ?>
		<section class="sc-ei-portal-card">
			<h3><?php esc_html_e( 'Current session', 'sustainable-catalyst-engagement-intake' ); ?></h3>
			<dl class="sc-ei-portal-details">
				<dt><?php esc_html_e( 'Access state', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Portal_Schema::label( SC_EI_Portal_Schema::access_statuses(), $access['status'] ) ); ?></dd>
				<dt><?php esc_html_e( 'Activated', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo $access['activated_at'] ? esc_html( get_date_from_gmt( $access['activated_at'], 'M j, Y g:i a' ) ) : '—'; ?></dd>
				<dt><?php esc_html_e( 'Session expires', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( get_date_from_gmt( $session['expires_at'], 'M j, Y g:i a' ) ); ?></dd>
				<dt><?php esc_html_e( 'Idle expiration', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( get_date_from_gmt( $session['idle_expires_at'], 'M j, Y g:i a' ) ); ?></dd>
				<dt><?php esc_html_e( 'Terms version', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $access['terms_version'] ); ?></dd>
			</dl>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="sc_ei_portal_logout"><input type="hidden" name="portal_csrf" value="<?php echo esc_attr( $csrf_token ); ?>"><button type="submit" class="sc-ei-button"><?php esc_html_e( 'Sign Out This Session', 'sustainable-catalyst-engagement-intake' ); ?></button></form>
		</section>

		<?php if ( ! empty( $permissions['revoke_access'] ) ) : ?>
			<section class="sc-ei-portal-card sc-ei-portal-card--danger">
				<h3><?php esc_html_e( 'Revoke all portal access', 'sustainable-catalyst-engagement-intake' ); ?></h3>
				<p><?php esc_html_e( 'This revokes the invitation and every active session. It does not withdraw or erase the inquiry.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-form">
					<input type="hidden" name="action" value="sc_ei_portal_revoke_access"><input type="hidden" name="portal_csrf" value="<?php echo esc_attr( $csrf_token ); ?>">
					<label><span><?php echo esc_html( sprintf( __( 'Type REVOKE %s', 'sustainable-catalyst-engagement-intake' ), strtoupper( $inquiry['reference'] ) ) ); ?></span><input type="text" name="revoke_confirmation" required autocomplete="off"></label>
					<button type="submit" class="sc-ei-button sc-ei-button--danger"><?php esc_html_e( 'Revoke Portal Access', 'sustainable-catalyst-engagement-intake' ); ?></button>
				</form>
			</section>
		<?php endif; ?>
	<?php endif; ?>

	<footer class="sc-ei-portal-footer">
		<p><?php esc_html_e( 'Private information remains subject to documented privacy, retention, security, legal-hold, and human-review controls.', 'sustainable-catalyst-engagement-intake' ); ?></p>
	</footer>
</section>
