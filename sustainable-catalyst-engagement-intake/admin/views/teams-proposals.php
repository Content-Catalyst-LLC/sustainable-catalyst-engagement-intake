<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$success = array(
	'meeting_draft_created'    => __( 'Meeting offer draft created.', 'sustainable-catalyst-engagement-intake' ),
	'meeting_offer_published'  => __( 'Meeting times published to the sender portal.', 'sustainable-catalyst-engagement-intake' ),
	'meeting_finalized'        => __( 'Microsoft Teams meeting finalized.', 'sustainable-catalyst-engagement-intake' ),
	'meeting_status_updated'   => __( 'Meeting status updated.', 'sustainable-catalyst-engagement-intake' ),
	'proposal_draft_created'   => __( 'Proposal draft created.', 'sustainable-catalyst-engagement-intake' ),
	'proposal_version_created' => __( 'A new proposal version was created as a draft.', 'sustainable-catalyst-engagement-intake' ),
	'proposal_published'       => __( 'Proposal published to the sender portal.', 'sustainable-catalyst-engagement-intake' ),
	'proposal_status_updated'  => __( 'Proposal status updated.', 'sustainable-catalyst-engagement-intake' ),
);
?>
<div class="wrap sc-ei-admin sc-ei-workflow-admin">
	<h1><?php esc_html_e( 'Teams Scheduling and Proposal Workflow', 'sustainable-catalyst-engagement-intake' ); ?></h1>
	<p><?php esc_html_e( 'Create human-approved Microsoft Teams time offers and versioned proposals. Sender responses are auditable, but no calendar event, contract, payment, or active engagement is created automatically.', 'sustainable-catalyst-engagement-intake' ); ?></p>

	<?php if ( isset( $success[ $message ] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $success[ $message ] ); ?></p></div>
	<?php elseif ( $message ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php echo esc_html( ucwords( str_replace( '_', ' ', $message ) ) ); ?></p></div>
	<?php endif; ?>

	<div class="sc-ei-portal-admin-boundary">
		<strong><?php esc_html_e( 'Human-control boundary', 'sustainable-catalyst-engagement-intake' ); ?></strong>
		<span><?php esc_html_e( 'Publishing requires authorized staff and deliberate typed confirmation. Sender meeting acceptance selects an approved slot only. Proposal acceptance records intent to proceed to contracting and is not an electronic signature or executed agreement.', 'sustainable-catalyst-engagement-intake' ); ?></span>
	</div>

	<div class="sc-ei-fit-metrics">
		<div><strong><?php echo esc_html( number_format_i18n( $metrics['meeting_offered'] ) ); ?></strong><span><?php esc_html_e( 'meeting offers open', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div><strong><?php echo esc_html( number_format_i18n( $metrics['meeting_scheduled'] ) ); ?></strong><span><?php esc_html_e( 'meetings scheduled', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div class="<?php echo $metrics['meeting_followup'] ? 'sc-ei-review-metric--attention' : ''; ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['meeting_followup'] ) ); ?></strong><span><?php esc_html_e( 'meeting follow-up', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div><strong><?php echo esc_html( number_format_i18n( $metrics['proposal_draft'] ) ); ?></strong><span><?php esc_html_e( 'proposal drafts', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div><strong><?php echo esc_html( number_format_i18n( $metrics['proposal_open'] ) ); ?></strong><span><?php esc_html_e( 'published proposals', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div class="<?php echo $metrics['proposal_accepted'] ? 'sc-ei-review-metric--attention' : ''; ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['proposal_accepted'] ) ); ?></strong><span><?php esc_html_e( 'contracting follow-up', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div><strong><?php echo esc_html( number_format_i18n( $metrics['contracted'] ) ); ?></strong><span><?php esc_html_e( 'external contracts recorded', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
	</div>

	<section class="sc-ei-admin__card sc-ei-admin__card--wide">
		<h2><?php esc_html_e( 'Open an Inquiry Workflow', 'sustainable-catalyst-engagement-intake' ); ?></h2>
		<form method="get" class="sc-ei-operation-filter-form">
			<input type="hidden" name="page" value="sc-engagement-intake-workflow">
			<label><span class="screen-reader-text"><?php esc_html_e( 'Inquiry ID', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" name="inquiry" min="1" value="<?php echo esc_attr( $inquiry_id ?: '' ); ?>" placeholder="<?php esc_attr_e( 'Inquiry ID', 'sustainable-catalyst-engagement-intake' ); ?>" required></label>
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Open Workflow', 'sustainable-catalyst-engagement-intake' ); ?></button>
		</form>
	</section>

	<?php if ( ! $inquiry_id ) : ?>
		<div class="sc-ei-admin__card sc-ei-admin__card--wide"><p><?php esc_html_e( 'Enter an inquiry ID to create or review meeting offers and proposals.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( ! $inquiry ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'The inquiry could not be found.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php else : ?>
		<header class="sc-ei-admin__card sc-ei-admin__card--wide sc-ei-workflow-inquiry-header">
			<div>
				<p class="sc-ei-admin__card-kicker"><?php esc_html_e( 'Selected Inquiry', 'sustainable-catalyst-engagement-intake' ); ?></p>
				<h2><?php echo esc_html( $inquiry['reference'] . ' · ' . $inquiry['contact_name'] ); ?></h2>
				<p><?php echo esc_html( $inquiry['contact_email'] ); ?><?php echo $inquiry['organization'] ? ' · ' . esc_html( $inquiry['organization'] ) : ''; ?></p>
			</div>
			<p>
				<a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'sc-engagement-intake', 'action' => 'view', 'inquiry' => $inquiry_id ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Open Inquiry', 'sustainable-catalyst-engagement-intake' ); ?></a>
				<?php if ( current_user_can( 'sc_intake_export_workflow' ) ) : ?>
					<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sc_ei_export_workflow&inquiry=' . $inquiry_id ), 'sc_ei_export_workflow_' . $inquiry_id ) ); ?>"><?php esc_html_e( 'Export Workflow JSON', 'sustainable-catalyst-engagement-intake' ); ?></a>
				<?php endif; ?>
			</p>
		</header>

		<div class="sc-ei-workflow-layout">
			<main>
				<?php if ( current_user_can( 'sc_intake_create_meeting_offers' ) ) : ?>
					<section class="sc-ei-admin__card sc-ei-admin__card--wide">
						<h2><?php esc_html_e( 'Create Microsoft Teams Time Offer', 'sustainable-catalyst-engagement-intake' ); ?></h2>
						<p><?php esc_html_e( 'Offer up to five future times in one timezone. Publishing creates a portal notice but does not book a calendar event or send email.', 'sustainable-catalyst-engagement-intake' ); ?></p>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-admin-form">
							<input type="hidden" name="action" value="sc_ei_create_meeting_offer"><input type="hidden" name="inquiry_id" value="<?php echo esc_attr( $inquiry_id ); ?>"><?php wp_nonce_field( 'sc_ei_create_meeting_offer' ); ?>
							<label><span><?php esc_html_e( 'Title', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="meeting_title" value="Microsoft Teams conversation" required maxlength="255"></label>
							<label><span><?php esc_html_e( 'Duration', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="duration_minutes"><?php foreach ( SC_EI_Teams::duration_options() as $key => $label ) : if ( '0' === (string) $key ) continue; ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( (string) ( $inquiry['preferred_duration'] ?: $settings['default_teams_duration'] ?? 30 ), (string) $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
							<label class="sc-ei-portal-admin-form__wide"><span><?php esc_html_e( 'Purpose', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="meeting_purpose" rows="4" required></textarea></label>
							<label><span><?php esc_html_e( 'Timezone', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="meeting_timezone" value="<?php echo esc_attr( $inquiry['timezone'] ?: wp_timezone_string() ); ?>" list="sc-ei-workflow-timezones" required><datalist id="sc-ei-workflow-timezones"><?php foreach ( SC_EI_Teams::timezone_identifiers() as $zone ) : ?><option value="<?php echo esc_attr( $zone ); ?>"></option><?php endforeach; ?></datalist></label>
							<label><span><?php esc_html_e( 'Offer expires', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="datetime-local" name="meeting_expires_at"></label>
							<fieldset class="sc-ei-portal-admin-form__wide"><legend><?php esc_html_e( 'Proposed local times', 'sustainable-catalyst-engagement-intake' ); ?></legend><div class="sc-ei-workflow-slot-grid"><?php for ( $i = 0; $i < 5; $i++ ) : ?><input type="datetime-local" name="meeting_slots[]" <?php echo 0 === $i ? 'required' : ''; ?>><?php endfor; ?></div></fieldset>
							<label class="sc-ei-portal-admin-form__wide"><span><?php esc_html_e( 'Microsoft Teams URL', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="url" name="teams_url" placeholder="https://teams.microsoft.com/..."><small><?php esc_html_e( 'May be added after the sender accepts a time unless your policy requires it before publication.', 'sustainable-catalyst-engagement-intake' ); ?></small></label>
							<label class="sc-ei-portal-admin-form__wide"><span><?php esc_html_e( 'Internal note', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="meeting_admin_note" rows="3"></textarea></label>
							<?php if ( current_user_can( 'sc_intake_publish_meeting_offers' ) ) : ?>
								<label class="sc-ei-check"><input type="checkbox" name="publish_now" value="1"><span><?php esc_html_e( 'Publish these human-approved times to the sender portal now.', 'sustainable-catalyst-engagement-intake' ); ?></span></label>
								<label><span><?php echo esc_html( sprintf( __( 'To publish now, type PUBLISH %s', 'sustainable-catalyst-engagement-intake' ), strtoupper( $inquiry['reference'] ) ) ); ?></span><input type="text" name="publish_confirmation" autocomplete="off"></label>
							<?php endif; ?>
							<p class="sc-ei-portal-admin-form__wide"><button type="submit" class="button button-primary"><?php esc_html_e( 'Save Meeting Offer', 'sustainable-catalyst-engagement-intake' ); ?></button></p>
						</form>
					</section>
				<?php endif; ?>

				<section class="sc-ei-admin__card sc-ei-admin__card--wide">
					<h2><?php esc_html_e( 'Meeting Offer History', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<?php if ( ! $meetings ) : ?><p><?php esc_html_e( 'No meeting offers recorded.', 'sustainable-catalyst-engagement-intake' ); ?></p><?php endif; ?>
					<?php foreach ( $meetings as $meeting ) : $slots = json_decode( (string) $meeting['slots_json'], true ) ?: array(); ?>
						<article class="sc-ei-workflow-record">
							<header><div><strong><?php echo esc_html( $meeting['offer_number'] . ' · ' . $meeting['title'] ); ?></strong><br><span class="sc-ei-fit-state sc-ei-fit-state--<?php echo esc_attr( $meeting['status'] ); ?>"><?php echo esc_html( SC_EI_Workflow_Schema::label( SC_EI_Workflow_Schema::meeting_statuses(), $meeting['status'] ) ); ?></span></div><span><?php echo esc_html( get_date_from_gmt( $meeting['created_at'], 'M j, Y g:i a' ) ); ?></span></header>
							<p><?php echo nl2br( esc_html( $meeting['purpose'] ) ); ?></p>
							<ul><?php foreach ( $slots as $slot ) : ?><li><?php echo esc_html( SC_EI_Teams::format_utc_for_input( $slot['start_utc'], $meeting['timezone'] ) . ' · ' . $meeting['timezone'] ); ?><?php echo $meeting['selected_slot_key'] === $slot['key'] ? ' · ' . esc_html__( 'selected', 'sustainable-catalyst-engagement-intake' ) : ''; ?></li><?php endforeach; ?></ul>
							<?php if ( $meeting['alternative_request'] ) : ?><div class="sc-ei-diagnostic-warning"><strong><?php esc_html_e( 'Alternative requested:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo nl2br( esc_html( $meeting['alternative_request'] ) ); ?></div><?php endif; ?>
							<?php if ( 'draft' === $meeting['status'] && current_user_can( 'sc_intake_publish_meeting_offers' ) ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-confirm-form"><input type="hidden" name="action" value="sc_ei_publish_meeting_offer"><input type="hidden" name="meeting_offer_id" value="<?php echo esc_attr( $meeting['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_publish_meeting_offer_' . absint( $meeting['id'] ) ); ?><input type="text" name="publish_confirmation" placeholder="<?php echo esc_attr( 'PUBLISH ' . strtoupper( $meeting['offer_number'] ) ); ?>" required><button class="button"><?php esc_html_e( 'Publish Offer', 'sustainable-catalyst-engagement-intake' ); ?></button></form>
							<?php endif; ?>
							<?php if ( 'accepted_pending_link' === $meeting['status'] && current_user_can( 'sc_intake_finalize_meetings' ) ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-admin-form sc-ei-workflow-finalize"><input type="hidden" name="action" value="sc_ei_finalize_meeting"><input type="hidden" name="meeting_offer_id" value="<?php echo esc_attr( $meeting['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_finalize_meeting_' . absint( $meeting['id'] ) ); ?><label><span><?php esc_html_e( 'Microsoft Teams URL', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="url" name="teams_url" required></label><label><span><?php echo esc_html( 'SCHEDULE ' . strtoupper( $meeting['offer_number'] ) ); ?></span><input type="text" name="schedule_confirmation" required></label><button class="button button-primary"><?php esc_html_e( 'Finalize Meeting', 'sustainable-catalyst-engagement-intake' ); ?></button></form>
							<?php endif; ?>
							<?php if ( in_array( $meeting['status'], array( 'scheduled', 'accepted_pending_link', 'alternative_requested' ), true ) && current_user_can( 'sc_intake_finalize_meetings' ) ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-confirm-form"><input type="hidden" name="action" value="sc_ei_change_meeting_status"><input type="hidden" name="meeting_offer_id" value="<?php echo esc_attr( $meeting['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_change_meeting_status_' . absint( $meeting['id'] ) ); ?><select name="meeting_status"><option value="completed"><?php esc_html_e( 'Mark completed', 'sustainable-catalyst-engagement-intake' ); ?></option><option value="canceled"><?php esc_html_e( 'Cancel meeting', 'sustainable-catalyst-engagement-intake' ); ?></option></select><input type="text" name="meeting_reason" placeholder="<?php esc_attr_e( 'Reason when canceling', 'sustainable-catalyst-engagement-intake' ); ?>"><input type="text" name="meeting_confirmation" placeholder="<?php echo esc_attr( 'COMPLETE ' . strtoupper( $meeting['offer_number'] ) ); ?>" required><button class="button"><?php esc_html_e( 'Update Meeting', 'sustainable-catalyst-engagement-intake' ); ?></button></form>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</section>

				<?php if ( current_user_can( 'sc_intake_create_proposals' ) ) : ?>
					<section class="sc-ei-admin__card sc-ei-admin__card--wide">
						<h2><?php esc_html_e( 'Create Versioned Proposal', 'sustainable-catalyst-engagement-intake' ); ?></h2>
						<p><?php esc_html_e( 'Use one line per scope, deliverable, exclusion, or assumption. Publishing exposes the current immutable version to the sender portal.', 'sustainable-catalyst-engagement-intake' ); ?></p>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-admin-form">
							<input type="hidden" name="action" value="sc_ei_create_proposal"><input type="hidden" name="inquiry_id" value="<?php echo esc_attr( $inquiry_id ); ?>"><?php wp_nonce_field( 'sc_ei_create_proposal' ); ?>
							<label class="sc-ei-portal-admin-form__wide"><span><?php esc_html_e( 'Proposal title', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="proposal_title" required maxlength="255"></label>
							<label class="sc-ei-portal-admin-form__wide"><span><?php esc_html_e( 'Executive summary', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="executive_summary" rows="5" required></textarea></label>
							<label><span><?php esc_html_e( 'Scope', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="proposal_scope" rows="7" required></textarea></label>
							<label><span><?php esc_html_e( 'Deliverables', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="proposal_deliverables" rows="7" required></textarea></label>
							<label><span><?php esc_html_e( 'Exclusions', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="proposal_exclusions" rows="5"></textarea></label>
							<label><span><?php esc_html_e( 'Assumptions', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="proposal_assumptions" rows="5"></textarea></label>
							<label><span><?php esc_html_e( 'Timeline', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="timeline_text" rows="4"></textarea></label>
							<label><span><?php esc_html_e( 'Fee summary', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="fee_summary" rows="4"></textarea></label>
							<label><span><?php esc_html_e( 'Payment terms', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="payment_terms" rows="4"></textarea></label>
							<label><span><?php esc_html_e( 'Proposal terms and boundaries', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="legal_terms" rows="4"></textarea></label>
							<label><span><?php esc_html_e( 'Currency', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="proposal_currency"><?php foreach ( SC_EI_Workflow_Schema::currencies() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
							<label><span><?php esc_html_e( 'Total proposal value', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" name="proposal_total" min="0" step="0.01" value="0"></label>
							<label><span><?php esc_html_e( 'Expires', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="datetime-local" name="proposal_expires_at"></label>
							<label><span><?php esc_html_e( 'Version note', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="version_note" value="Initial proposal"></label>
							<?php if ( current_user_can( 'sc_intake_publish_proposals' ) ) : ?>
								<label class="sc-ei-check"><input type="checkbox" name="publish_now" value="1"><span><?php esc_html_e( 'Publish the current proposal version to the sender portal now.', 'sustainable-catalyst-engagement-intake' ); ?></span></label>
								<label><span><?php echo esc_html( sprintf( __( 'To publish now, type PUBLISH %s', 'sustainable-catalyst-engagement-intake' ), strtoupper( $inquiry['reference'] ) ) ); ?></span><input type="text" name="publish_confirmation"></label>
							<?php endif; ?>
							<p class="sc-ei-portal-admin-form__wide"><button type="submit" class="button button-primary"><?php esc_html_e( 'Save Proposal', 'sustainable-catalyst-engagement-intake' ); ?></button></p>
						</form>
					</section>
				<?php endif; ?>

				<section class="sc-ei-admin__card sc-ei-admin__card--wide">
					<h2><?php esc_html_e( 'Proposal History', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<?php if ( ! $proposals ) : ?><p><?php esc_html_e( 'No proposals recorded.', 'sustainable-catalyst-engagement-intake' ); ?></p><?php endif; ?>
					<?php foreach ( $proposals as $proposal ) : ?>
						<article class="sc-ei-workflow-record sc-ei-workflow-proposal-record">
							<header><div><strong><?php echo esc_html( $proposal['proposal_number'] . ' · ' . $proposal['title'] ); ?></strong><br><span class="sc-ei-fit-state sc-ei-fit-state--<?php echo esc_attr( $proposal['status'] ); ?>"><?php echo esc_html( SC_EI_Workflow_Schema::label( SC_EI_Workflow_Schema::proposal_statuses(), $proposal['status'] ) ); ?></span></div><span><?php echo esc_html( 'v' . absint( $proposal['version_number'] ) . ' · ' . SC_EI_Workflow_Schema::money_display( absint( $proposal['total_minor'] ), $proposal['currency'] ) ); ?><?php if ( ! empty( $proposal['pending_version_id'] ) ) : ?><br><em><?php esc_html_e( 'Unpublished revision', 'sustainable-catalyst-engagement-intake' ); ?></em><?php endif; ?></span></header>
							<p><?php echo nl2br( esc_html( $proposal['executive_summary'] ) ); ?></p>
							<p><strong><?php esc_html_e( 'Expires:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo esc_html( get_date_from_gmt( $proposal['expires_at'], 'M j, Y g:i a' ) ); ?> UTC</p>
							<?php if ( $proposal['sender_response_note'] ) : ?><div class="sc-ei-diagnostic-warning"><strong><?php esc_html_e( 'Sender response:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo nl2br( esc_html( $proposal['sender_response_note'] ) ); ?></div><?php endif; ?>
							<?php if ( in_array( $proposal['status'], array( 'draft', 'published' ), true ) && current_user_can( 'sc_intake_create_proposals' ) ) : ?>
								<details class="sc-ei-workflow-version-editor">
									<summary><strong><?php esc_html_e( 'Create New Proposal Version', 'sustainable-catalyst-engagement-intake' ); ?></strong></summary>
									<p><?php esc_html_e( 'Saving creates an immutable version with a new SHA-256 content hash. A published proposal remains visible to the sender until the new version is deliberately published.', 'sustainable-catalyst-engagement-intake' ); ?></p>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-admin-form sc-ei-workflow-proposal-form">
										<input type="hidden" name="action" value="sc_ei_add_proposal_version">
										<input type="hidden" name="proposal_id" value="<?php echo esc_attr( $proposal['id'] ); ?>">
										<?php wp_nonce_field( 'sc_ei_add_proposal_version_' . absint( $proposal['id'] ) ); ?>
										<label class="sc-ei-portal-admin-form__wide"><span><?php esc_html_e( 'Proposal title', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="proposal_title" value="<?php echo esc_attr( $proposal['title'] ); ?>" required></label>
										<label class="sc-ei-portal-admin-form__wide"><span><?php esc_html_e( 'Executive summary', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="executive_summary" rows="5" required><?php echo esc_textarea( $proposal['executive_summary'] ); ?></textarea></label>
										<label><span><?php esc_html_e( 'Scope — one item per line', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="proposal_scope" rows="6" required><?php echo esc_textarea( implode( "\n", json_decode( (string) $proposal['scope_json'], true ) ?: array() ) ); ?></textarea></label>
										<label><span><?php esc_html_e( 'Deliverables — one item per line', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="proposal_deliverables" rows="6" required><?php echo esc_textarea( implode( "\n", json_decode( (string) $proposal['deliverables_json'], true ) ?: array() ) ); ?></textarea></label>
										<label><span><?php esc_html_e( 'Exclusions', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="proposal_exclusions" rows="4"><?php echo esc_textarea( implode( "\n", json_decode( (string) $proposal['exclusions_json'], true ) ?: array() ) ); ?></textarea></label>
										<label><span><?php esc_html_e( 'Assumptions', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="proposal_assumptions" rows="4"><?php echo esc_textarea( implode( "\n", json_decode( (string) $proposal['assumptions_json'], true ) ?: array() ) ); ?></textarea></label>
										<label><span><?php esc_html_e( 'Timeline', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="timeline_text" rows="4"><?php echo esc_textarea( $proposal['timeline_text'] ); ?></textarea></label>
										<label><span><?php esc_html_e( 'Fee summary', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="fee_summary" rows="4"><?php echo esc_textarea( $proposal['fee_summary'] ); ?></textarea></label>
										<label><span><?php esc_html_e( 'Payment terms', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="payment_terms" rows="4"><?php echo esc_textarea( $proposal['payment_terms'] ); ?></textarea></label>
										<label><span><?php esc_html_e( 'Proposal terms and boundaries', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="legal_terms" rows="4"><?php echo esc_textarea( $proposal['legal_terms'] ); ?></textarea></label>
										<label><span><?php esc_html_e( 'Currency', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="proposal_currency"><?php foreach ( SC_EI_Workflow_Schema::currencies() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $proposal['currency'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
										<label><span><?php esc_html_e( 'Total proposal value', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" name="proposal_total" min="0" step="0.01" value="<?php echo esc_attr( number_format( absint( $proposal['total_minor'] ) / 100, 2, '.', '' ) ); ?>"></label>
										<label><span><?php esc_html_e( 'Expires', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="datetime-local" name="proposal_expires_at" value="<?php echo esc_attr( gmdate( 'Y-m-d\TH:i', strtotime( $proposal['expires_at'] . ' UTC' ) ) ); ?>"></label>
										<label><span><?php esc_html_e( 'Version note', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="version_note" value="<?php echo esc_attr( 'Revision after v' . absint( $proposal['version_number'] ) ); ?>"></label>
										<p class="sc-ei-portal-admin-form__wide"><button type="submit" class="button"><?php esc_html_e( 'Save Unpublished Version', 'sustainable-catalyst-engagement-intake' ); ?></button></p>
									</form>
								</details>
							<?php endif; ?>
							<?php if ( ! empty( $proposal['pending_version_id'] ) && in_array( $proposal['status'], array( 'draft', 'published' ), true ) && current_user_can( 'sc_intake_publish_proposals' ) ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-confirm-form"><input type="hidden" name="action" value="sc_ei_publish_proposal"><input type="hidden" name="proposal_id" value="<?php echo esc_attr( $proposal['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_publish_proposal_' . absint( $proposal['id'] ) ); ?><input type="text" name="publish_confirmation" placeholder="<?php echo esc_attr( 'PUBLISH ' . strtoupper( $proposal['proposal_number'] ) ); ?>" required><button class="button"><?php esc_html_e( 'Publish Proposal', 'sustainable-catalyst-engagement-intake' ); ?></button></form>
							<?php endif; ?>
							<?php if ( 'accepted_pending_contract' === $proposal['status'] && current_user_can( 'sc_intake_record_contracts' ) ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-admin-form sc-ei-workflow-contract"><input type="hidden" name="action" value="sc_ei_change_proposal_status"><input type="hidden" name="proposal_id" value="<?php echo esc_attr( $proposal['id'] ); ?>"><input type="hidden" name="proposal_status" value="contracted"><?php wp_nonce_field( 'sc_ei_change_proposal_status_' . absint( $proposal['id'] ) ); ?><label><span><?php esc_html_e( 'External contract reference', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="contract_reference" required></label><label><span><?php echo esc_html( 'CONTRACT ' . strtoupper( $proposal['proposal_number'] ) ); ?></span><input type="text" name="proposal_confirmation" required></label><label class="sc-ei-portal-admin-form__wide"><span><?php esc_html_e( 'Administrative note', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="proposal_note" rows="3"></textarea></label><button class="button button-primary"><?php esc_html_e( 'Record External Contract', 'sustainable-catalyst-engagement-intake' ); ?></button></form>
							<?php endif; ?>
							<?php if ( in_array( $proposal['status'], array( 'draft', 'published', 'accepted_pending_contract' ), true ) && current_user_can( 'sc_intake_publish_proposals' ) ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-confirm-form"><input type="hidden" name="action" value="sc_ei_change_proposal_status"><input type="hidden" name="proposal_id" value="<?php echo esc_attr( $proposal['id'] ); ?>"><input type="hidden" name="proposal_status" value="withdrawn"><?php wp_nonce_field( 'sc_ei_change_proposal_status_' . absint( $proposal['id'] ) ); ?><input type="text" name="proposal_note" placeholder="<?php esc_attr_e( 'Withdrawal reason', 'sustainable-catalyst-engagement-intake' ); ?>" required><input type="text" name="proposal_confirmation" placeholder="<?php echo esc_attr( 'WITHDRAW ' . strtoupper( $proposal['proposal_number'] ) ); ?>" required><button class="button"><?php esc_html_e( 'Withdraw Proposal', 'sustainable-catalyst-engagement-intake' ); ?></button></form>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</section>
			</main>

			<aside>
				<section class="sc-ei-admin__card">
					<h2><?php esc_html_e( 'Sender Preferences', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<dl class="sc-ei-admin__details">
						<dt><?php esc_html_e( 'Meeting request', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Teams::label( SC_EI_Teams::meeting_requests(), $inquiry['meeting_request'] ) ); ?></dd>
						<dt><?php esc_html_e( 'Scheduling status', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Teams::label( SC_EI_Teams::scheduling_statuses(), $inquiry['scheduling_status'] ) ); ?></dd>
						<dt><?php esc_html_e( 'Timezone', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $inquiry['timezone'] ?: '—' ); ?></dd>
						<dt><?php esc_html_e( 'Duration', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $inquiry['preferred_duration'] ? $inquiry['preferred_duration'] . ' minutes' : '—' ); ?></dd>
						<dt><?php esc_html_e( 'Calendar consent', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo $inquiry['calendar_invite_consent'] ? esc_html__( 'Granted', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'Not granted', 'sustainable-catalyst-engagement-intake' ); ?></dd>
					</dl>
				</section>
				<section class="sc-ei-admin__card">
					<h2><?php esc_html_e( 'Operational Boundaries', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<ul>
						<li><?php esc_html_e( 'Microsoft Teams only.', 'sustainable-catalyst-engagement-intake' ); ?></li>
						<li><?php esc_html_e( 'No Graph API or automatic calendar booking.', 'sustainable-catalyst-engagement-intake' ); ?></li>
						<li><?php esc_html_e( 'No automatic email delivery.', 'sustainable-catalyst-engagement-intake' ); ?></li>
						<li><?php esc_html_e( 'No electronic signature.', 'sustainable-catalyst-engagement-intake' ); ?></li>
						<li><?php esc_html_e( 'No payment collection.', 'sustainable-catalyst-engagement-intake' ); ?></li>
						<li><?php esc_html_e( 'No active engagement until an external contract is recorded by authorized staff.', 'sustainable-catalyst-engagement-intake' ); ?></li>
					</ul>
				</section>
				<section class="sc-ei-admin__card">
					<h2><?php esc_html_e( 'Workflow Events', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<div class="sc-ei-workflow-event-list">
						<?php if ( ! $events ) : ?><p><?php esc_html_e( 'No workflow events.', 'sustainable-catalyst-engagement-intake' ); ?></p><?php endif; ?>
						<?php foreach ( array_slice( $events, 0, 30 ) as $event ) : ?><article><strong><?php echo esc_html( SC_EI_Workflow_Schema::label( SC_EI_Workflow_Schema::event_types(), $event['event_type'] ) ); ?></strong><span><?php echo esc_html( get_date_from_gmt( $event['created_at'], 'M j, Y g:i a' ) ); ?></span><span><?php echo esc_html( $event['actor_name'] ?: ucfirst( $event['actor_type'] ) ); ?></span></article><?php endforeach; ?>
					</div>
				</section>
			</aside>
		</div>
	<?php endif; ?>
</div>
