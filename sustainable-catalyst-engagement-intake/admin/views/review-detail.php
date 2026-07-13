<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$message = isset( $_GET['sc_ei_msg'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_msg'] ) ) : '';
$due_local = '';
if ( $inquiry['review_due_at'] ) {
	try {
		$due_local = ( new DateTimeImmutable( $inquiry['review_due_at'], new DateTimeZone( 'UTC' ) ) )
			->setTimezone( wp_timezone() )
			->format( 'Y-m-d\TH:i' );
	} catch ( Throwable $exception ) {
		$due_local = '';
	}
}

$full_record_url = add_query_arg(
	array(
		'page'    => 'sc-engagement-intake',
		'action'  => 'view',
		'inquiry' => absint( $inquiry['id'] ),
	),
	admin_url( 'admin.php' )
);
$quarantine_url = add_query_arg(
	array(
		'page' => 'sc-engagement-intake-quarantine',
		'view' => 'queue',
		's'    => $inquiry['reference'],
	),
	admin_url( 'admin.php' )
);
$export_url = wp_nonce_url(
	add_query_arg(
		array(
			'action'  => 'sc_ei_export_review_packet',
			'inquiry' => absint( $inquiry['id'] ),
		),
		admin_url( 'admin-post.php' )
	),
	'sc_ei_export_review_packet_' . absint( $inquiry['id'] )
);

$guidance_flags = json_decode( (string) $inquiry['guidance_flags'], true );
$guidance_flags = is_array( $guidance_flags ) ? $guidance_flags : array();
$links = json_decode( (string) $inquiry['relevant_links'], true );
$links = is_array( $links ) ? $links : array();
?>
<div class="wrap sc-ei-admin sc-ei-review-detail">
	<p class="sc-ei-admin__breadcrumb">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-review' ) ); ?>">← <?php esc_html_e( 'Back to Review Workspace', 'sustainable-catalyst-engagement-intake' ); ?></a>
		· <a href="<?php echo esc_url( $full_record_url ); ?>"><?php esc_html_e( 'Full inquiry record', 'sustainable-catalyst-engagement-intake' ); ?></a>
		· <a href="<?php echo esc_url( SC_EI_Communication_Admin::thread_url( absint( $inquiry['id'] ) ) ); ?>"><?php esc_html_e( 'Communications', 'sustainable-catalyst-engagement-intake' ); ?></a>
		· <a href="<?php echo esc_url( SC_EI_Privacy_Admin::url( 'overview', array( 'inquiry' => absint( $inquiry['id'] ) ) ) ); ?>"><?php esc_html_e( 'Privacy Center', 'sustainable-catalyst-engagement-intake' ); ?></a>
		· <a href="<?php echo esc_url( $quarantine_url ); ?>"><?php esc_html_e( 'Related quarantine records', 'sustainable-catalyst-engagement-intake' ); ?></a>
	</p>

	<h1><?php echo esc_html( $inquiry['reference'] ); ?> · <?php esc_html_e( 'Administrative Review', 'sustainable-catalyst-engagement-intake' ); ?></h1>
	<p><?php echo esc_html( $inquiry['subject'] ?: $inquiry['project_summary'] ?: __( 'Private inquiry review', 'sustainable-catalyst-engagement-intake' ) ); ?></p>
	<?php if ( in_array( $inquiry['privacy_status'], array( 'restricted', 'erasure_requested' ), true ) || absint( $inquiry['legal_hold_count'] ) > 0 ) : ?>
		<div class="notice notice-warning"><p><strong><?php echo esc_html( SC_EI_Privacy_Schema::label( SC_EI_Privacy_Schema::privacy_statuses(), $inquiry['privacy_status'] ) ); ?></strong> · <?php echo esc_html( sprintf( __( '%d active legal hold(s). Review privacy controls before changing status or recommending external action.', 'sustainable-catalyst-engagement-intake' ), absint( $inquiry['legal_hold_count'] ) ) ); ?></p></div>
	<?php endif; ?>

	<?php if ( 'review_saved' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'The administrative review was saved and an immutable review snapshot was recorded.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'review_claimed' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'The inquiry is now assigned to you.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'review_unassigned' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'The review assignment was removed.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'review_conflict' === $message ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'Another reviewer changed this inquiry before your save completed. Review the current values and save again.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'review_rationale_required' === $message ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'A decision rationale is required for a fit decision, active escalation, or completed review.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'review_checklist_incomplete' === $message ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'Complete the review checklist before marking the review completed.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'review_decision_incomplete' === $message ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'A completed review requires an explicit fit decision and recommended next step.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'escalation_reason_required' === $message ) : ?>
		<div class="notice notice-error"><p><?php esc_html_e( 'An escalation reason is required.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'review_already_assigned' === $message ) : ?>
		<div class="notice notice-warning"><p><?php esc_html_e( 'Another reviewer already owns this inquiry. A manager can reassign it.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( $message ) : ?>
		<div class="notice notice-error"><p><?php echo esc_html( ucwords( str_replace( '_', ' ', $message ) ) ); ?></p></div>
	<?php endif; ?>

	<div class="sc-ei-review-detail-metrics">
		<div><strong><?php echo esc_html( SC_EI_Review_Schema::label( SC_EI_Review_Schema::stages(), $inquiry['review_stage'] ) ); ?></strong><span><?php esc_html_e( 'review stage', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div class="sc-ei-review-metric--<?php echo esc_attr( $timing['due_state'] ); ?>"><strong><?php echo $inquiry['review_due_at'] ? esc_html( get_date_from_gmt( $inquiry['review_due_at'], 'M j, Y g:i a' ) ) : esc_html__( 'No due date', 'sustainable-catalyst-engagement-intake' ); ?></strong><span><?php echo esc_html( ucwords( str_replace( '_', ' ', $timing['due_state'] ) ) ); ?></span></div>
		<div><strong><?php echo esc_html( $assigned_user ? $assigned_user->display_name : __( 'Unassigned', 'sustainable-catalyst-engagement-intake' ) ); ?></strong><span><?php esc_html_e( 'review owner', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div><strong><?php echo esc_html( $checklist['percent'] . '%' ); ?></strong><span><?php esc_html_e( 'checklist complete', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div class="<?php echo $document_summary['attention'] ? 'sc-ei-review-metric--danger' : ''; ?>"><strong><?php echo esc_html( $document_summary['total'] ); ?> / <?php echo esc_html( $document_summary['attention'] ); ?></strong><span><?php esc_html_e( 'documents / attention', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div><strong>v<?php echo esc_html( absint( $inquiry['review_version'] ) ); ?></strong><span><?php esc_html_e( 'review version', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
	</div>

	<div class="sc-ei-review-detail-layout">
		<main>
			<form class="sc-ei-admin__card sc-ei-review-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-sc-ei-review-form>
				<input type="hidden" name="action" value="sc_ei_save_review">
				<input type="hidden" name="inquiry_id" value="<?php echo esc_attr( $inquiry['id'] ); ?>">
				<input type="hidden" name="review_version" value="<?php echo esc_attr( $inquiry['review_version'] ); ?>">
				<?php wp_nonce_field( 'sc_ei_save_review_' . absint( $inquiry['id'] ) ); ?>

				<?php if ( ! $can_edit_review ) : ?>
					<div class="sc-ei-diagnostic-warning"><strong><?php esc_html_e( 'Read-only review:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'This inquiry is assigned to another reviewer. A manager can reassign it before you edit the current review state.', 'sustainable-catalyst-engagement-intake' ); ?></div>
				<?php endif; ?>

				<fieldset class="sc-ei-review-form__fieldset" <?php disabled( ! $can_edit_review ); ?>>
				<div class="sc-ei-review-form__header">
					<div>
						<p class="sc-ei-admin__card-kicker"><?php esc_html_e( 'Human-authored assessment', 'sustainable-catalyst-engagement-intake' ); ?></p>
						<h2><?php esc_html_e( 'Review Decision Record', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					</div>
					<span class="sc-ei-review-version"><?php echo esc_html( sprintf( __( 'Editing version %d', 'sustainable-catalyst-engagement-intake' ), absint( $inquiry['review_version'] ) ) ); ?></span>
				</div>

				<div class="sc-ei-review-form-grid">
					<?php if ( current_user_can( 'sc_intake_assign_inquiries' ) ) : ?>
						<label><span><?php esc_html_e( 'Assigned reviewer', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="assigned_user_id"><option value="0"><?php esc_html_e( 'Unassigned', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( $reviewers as $reviewer ) : ?><option value="<?php echo esc_attr( $reviewer->ID ); ?>" <?php selected( absint( $inquiry['assigned_user_id'] ), $reviewer->ID ); ?>><?php echo esc_html( $reviewer->display_name ); ?></option><?php endforeach; ?></select></label>
					<?php elseif ( empty( $inquiry['assigned_user_id'] ) && ! empty( $settings['reviewer_self_assignment'] ) ) : ?>
						<label class="sc-ei-review-checkbox"><input type="checkbox" name="claim_on_save" value="1"><span><?php esc_html_e( 'Assign this review to me when saving', 'sustainable-catalyst-engagement-intake' ); ?></span></label>
					<?php endif; ?>

					<label><span><?php esc_html_e( 'Review stage', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="review_stage"><?php foreach ( SC_EI_Review_Schema::stages() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $inquiry['review_stage'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>

					<label><span><?php esc_html_e( 'Priority', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="review_priority" <?php disabled( ! current_user_can( 'sc_intake_manage_review_priority' ) ); ?>><?php foreach ( SC_EI_Review_Schema::priorities() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $inquiry['review_priority'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>

					<label><span><?php esc_html_e( 'Review due', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="datetime-local" name="review_due_local" value="<?php echo esc_attr( $due_local ); ?>" <?php disabled( ! current_user_can( 'sc_intake_manage_review_priority' ) ); ?>></label>

					<label><span><?php esc_html_e( 'Inquiry status', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="inquiry_status" <?php disabled( ! current_user_can( 'sc_intake_change_status' ) ); ?>><?php foreach ( SC_EI_Statuses::all() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $inquiry['status'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>

					<label><span><?php esc_html_e( 'Fit decision', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="fit_decision"><?php foreach ( SC_EI_Review_Schema::fit_decisions() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $inquiry['fit_decision'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>

					<label><span><?php esc_html_e( 'Fit confidence', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="fit_confidence"><?php foreach ( SC_EI_Review_Schema::confidence_levels() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $inquiry['fit_confidence'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>

					<label><span><?php esc_html_e( 'Risk level', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="risk_level"><?php foreach ( SC_EI_Review_Schema::risk_levels() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $inquiry['risk_level'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>

					<label><span><?php esc_html_e( 'Evidence readiness', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="evidence_readiness"><?php foreach ( SC_EI_Review_Schema::evidence_readiness_levels() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $inquiry['evidence_readiness'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>

					<label><span><?php esc_html_e( 'Scope clarity', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="scope_clarity"><?php foreach ( SC_EI_Review_Schema::scope_clarity_levels() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $inquiry['scope_clarity'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>

					<label class="sc-ei-review-form-grid__wide"><span><?php esc_html_e( 'Recommended next step', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="recommended_next_step"><?php foreach ( SC_EI_Review_Schema::next_steps() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $inquiry['recommended_next_step'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select><small><?php esc_html_e( 'This recommendation does not send a message, schedule a meeting, or change status unless you explicitly select a status above.', 'sustainable-catalyst-engagement-intake' ); ?></small></label>
				</div>

				<div class="sc-ei-review-text-grid">
					<label><span><?php esc_html_e( 'Review summary', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="review_summary" rows="6"><?php echo esc_textarea( $inquiry['review_summary'] ); ?></textarea><small><?php esc_html_e( 'Concise internal synthesis of the request, alignment, constraints, and review state.', 'sustainable-catalyst-engagement-intake' ); ?></small></label>
					<label><span><?php esc_html_e( 'Decision rationale', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="decision_rationale" rows="6"><?php echo esc_textarea( $inquiry['decision_rationale'] ); ?></textarea><small><?php esc_html_e( 'Required for a fit decision, active escalation, or completed review.', 'sustainable-catalyst-engagement-intake' ); ?></small></label>
					<label><span><?php esc_html_e( 'Information gaps and questions', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="information_gaps" rows="5"><?php echo esc_textarea( $inquiry['information_gaps'] ); ?></textarea></label>
					<label><span><?php esc_html_e( 'Conflict, independence, privacy, or reputational notes', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="conflict_notes" rows="5"><?php echo esc_textarea( $inquiry['conflict_notes'] ); ?></textarea></label>
				</div>

				<fieldset class="sc-ei-review-checklist">
					<legend><?php esc_html_e( 'Administrative review checklist', 'sustainable-catalyst-engagement-intake' ); ?> <span><?php echo esc_html( $checklist['percent'] . '%' ); ?></span></legend>
					<?php foreach ( SC_EI_Review_Schema::checklist_items() as $key => $label ) : ?>
						<label><input type="checkbox" name="review_checklist[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $checklist['items'][ $key ] ) ); ?>><span><?php echo esc_html( $label ); ?></span></label>
					<?php endforeach; ?>
				</fieldset>

				<?php if ( current_user_can( 'sc_intake_escalate_review' ) ) : ?>
					<section class="sc-ei-review-escalation-panel">
						<h3><?php esc_html_e( 'Escalation', 'sustainable-catalyst-engagement-intake' ); ?></h3>
						<div class="sc-ei-review-form-grid">
							<label><span><?php esc_html_e( 'Escalation state', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="escalation_status"><?php foreach ( SC_EI_Review_Schema::escalation_statuses() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $inquiry['escalation_status'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
							<label class="sc-ei-review-form-grid__wide"><span><?php esc_html_e( 'Escalation reason and resolution record', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="escalation_reason" rows="4"><?php echo esc_textarea( $inquiry['escalation_reason'] ); ?></textarea></label>
						</div>
					</section>
				<?php endif; ?>

				<label class="sc-ei-review-event-note"><span><?php esc_html_e( 'Snapshot note', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="review_event_note" placeholder="<?php esc_attr_e( 'Optional: what changed in this review version', 'sustainable-catalyst-engagement-intake' ); ?>"></label>

				<div class="sc-ei-review-form__actions">
					<button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Save Review and Record Snapshot', 'sustainable-catalyst-engagement-intake' ); ?></button>
					<span class="description"><?php esc_html_e( 'A version conflict prevents silent overwrites when another reviewer saves first.', 'sustainable-catalyst-engagement-intake' ); ?></span>
				</div>
				</fieldset>
			</form>

			<section class="sc-ei-admin__card">
				<div class="sc-ei-review-section-header"><h2><?php esc_html_e( 'Request Context', 'sustainable-catalyst-engagement-intake' ); ?></h2><a href="<?php echo esc_url( $full_record_url ); ?>"><?php esc_html_e( 'Open full record', 'sustainable-catalyst-engagement-intake' ); ?></a></div>
				<dl class="sc-ei-admin__details sc-ei-review-context-details">
					<dt><?php esc_html_e( 'Contact', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $inquiry['contact_name'] ); ?> · <a href="mailto:<?php echo esc_attr( $inquiry['contact_email'] ); ?>"><?php echo esc_html( $inquiry['contact_email'] ); ?></a></dd>
					<dt><?php esc_html_e( 'Organization', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $inquiry['organization'] ?: '—' ); ?><?php echo $inquiry['role_title'] ? ' · ' . esc_html( $inquiry['role_title'] ) : ''; ?></dd>
					<dt><?php esc_html_e( 'Inquiry type', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Statuses::inquiry_types()[ $inquiry['inquiry_type'] ] ?? $inquiry['inquiry_type'] ); ?></dd>
					<dt><?php esc_html_e( 'Service interest', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $inquiry['service_interest'] ?: '—' ); ?></dd>
					<dt><?php esc_html_e( 'Budget', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $inquiry['budget_range'] ?: '—' ); ?></dd>
					<dt><?php esc_html_e( 'Timing', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $inquiry['desired_start_date'] ?: '—' ); ?> → <?php echo esc_html( $inquiry['deadline_date'] ?: '—' ); ?></dd>
					<dt><?php esc_html_e( 'Source', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Conversion::label( SC_EI_Conversion::variants(), $inquiry['form_variant'] ) ); ?> · <?php echo esc_html( SC_EI_Conversion::label( SC_EI_Conversion::sources(), $inquiry['source_page'] ) ); ?> · <?php echo esc_html( ucwords( str_replace( '_', ' ', $inquiry['conversion_route'] ) ) ); ?></dd>
					<dt><?php esc_html_e( 'Teams request', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Teams::label( SC_EI_Teams::meeting_requests(), $inquiry['meeting_request'] ) ); ?> · <?php echo esc_html( SC_EI_Teams::label( SC_EI_Teams::scheduling_statuses(), $inquiry['scheduling_status'] ) ); ?></dd>
				</dl>

				<?php foreach ( array(
					'Message'         => $inquiry['message'],
					'Project summary' => $inquiry['project_summary'],
					'Desired outcome' => $inquiry['desired_outcome'],
				) as $label => $value ) : ?>
					<?php if ( $value ) : ?><div class="sc-ei-review-context-block"><h3><?php echo esc_html( $label ); ?></h3><p><?php echo nl2br( esc_html( $value ) ); ?></p></div><?php endif; ?>
				<?php endforeach; ?>

				<?php if ( $guidance_flags ) : ?>
					<div class="sc-ei-review-context-block"><h3><?php esc_html_e( 'Deterministic intake guidance flags', 'sustainable-catalyst-engagement-intake' ); ?></h3><ul><?php foreach ( $guidance_flags as $flag ) : ?><li><?php echo esc_html( ucwords( str_replace( '_', ' ', (string) $flag ) ) ); ?></li><?php endforeach; ?></ul><p class="description"><?php esc_html_e( 'These routing flags are context, not a fit decision or automated recommendation.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
				<?php endif; ?>

				<?php if ( $links ) : ?>
					<div class="sc-ei-review-context-block"><h3><?php esc_html_e( 'Relevant links', 'sustainable-catalyst-engagement-intake' ); ?></h3><ul><?php foreach ( $links as $link ) : ?><li><a href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $link ); ?></a></li><?php endforeach; ?></ul></div>
				<?php endif; ?>
			</section>

			<section class="sc-ei-admin__card">
				<div class="sc-ei-review-section-header"><h2><?php esc_html_e( 'Structured Review History', 'sustainable-catalyst-engagement-intake' ); ?></h2><span><?php echo esc_html( sprintf( _n( '%d snapshot', '%d snapshots', count( $history ), 'sustainable-catalyst-engagement-intake' ), count( $history ) ) ); ?></span></div>
				<?php if ( $history ) : ?>
					<ol class="sc-ei-review-history">
						<?php foreach ( $history as $review ) : ?>
							<?php $snapshot = json_decode( (string) $review['snapshot_json'], true ); $snapshot = is_array( $snapshot ) ? $snapshot : array(); ?>
							<li>
								<div class="sc-ei-review-history__meta">
									<strong>v<?php echo esc_html( absint( $review['review_version'] ) ); ?> · <?php echo esc_html( ucwords( str_replace( '_', ' ', $review['event_type'] ) ) ); ?></strong>
									<span><?php echo esc_html( get_date_from_gmt( $review['created_at'], 'M j, Y g:i a' ) ); ?> · <?php echo esc_html( $review['reviewer_name'] ?: __( 'System', 'sustainable-catalyst-engagement-intake' ) ); ?></span>
								</div>
								<p><span class="sc-ei-review-stage"><?php echo esc_html( SC_EI_Review_Schema::label( SC_EI_Review_Schema::stages(), $review['to_stage'] ) ); ?></span> · <?php echo esc_html( SC_EI_Review_Schema::label( SC_EI_Review_Schema::fit_decisions(), $review['fit_decision'] ) ); ?> · <?php echo esc_html( SC_EI_Review_Schema::label( SC_EI_Review_Schema::next_steps(), $review['recommended_next_step'] ) ); ?></p>
								<?php if ( ! empty( $snapshot['event_note'] ) ) : ?><p><strong><?php esc_html_e( 'Snapshot note:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo esc_html( $snapshot['event_note'] ); ?></p><?php endif; ?>
								<?php if ( $review['summary'] ) : ?><p><?php echo nl2br( esc_html( $review['summary'] ) ); ?></p><?php endif; ?>
								<?php if ( $review['rationale'] ) : ?><details><summary><?php esc_html_e( 'Decision rationale', 'sustainable-catalyst-engagement-intake' ); ?></summary><p><?php echo nl2br( esc_html( $review['rationale'] ) ); ?></p></details><?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ol>
				<?php else : ?>
					<p><?php esc_html_e( 'No structured review snapshot has been recorded yet.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				<?php endif; ?>
			</section>
		</main>

		<aside>
			<section class="sc-ei-admin__card sc-ei-review-owner-card">
				<h2><?php esc_html_e( 'Ownership and Actions', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<p><strong><?php esc_html_e( 'Assigned:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo esc_html( $assigned_user ? $assigned_user->display_name : __( 'Unassigned', 'sustainable-catalyst-engagement-intake' ) ); ?></p>
				<p><strong><?php esc_html_e( 'Last reviewed:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo $inquiry['last_reviewed_at'] ? esc_html( get_date_from_gmt( $inquiry['last_reviewed_at'], 'M j, Y g:i a' ) ) : esc_html__( 'Never', 'sustainable-catalyst-engagement-intake' ); ?></p>
				<p><strong><?php esc_html_e( 'Age:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo esc_html( sprintf( __( '%1$d days · %2$d idle days', 'sustainable-catalyst-engagement-intake' ), $timing['age_days'], $timing['idle_days'] ) ); ?></p>

				<?php if ( empty( $inquiry['assigned_user_id'] ) && ( ! empty( $settings['reviewer_self_assignment'] ) || current_user_can( 'sc_intake_assign_inquiries' ) ) ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="sc_ei_claim_review">
						<input type="hidden" name="inquiry_id" value="<?php echo esc_attr( $inquiry['id'] ); ?>">
						<?php wp_nonce_field( 'sc_ei_claim_review_' . absint( $inquiry['id'] ) ); ?>
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Claim This Review', 'sustainable-catalyst-engagement-intake' ); ?></button>
					</form>
				<?php endif; ?>

				<?php if ( current_user_can( 'sc_intake_assign_inquiries' ) && ! empty( $inquiry['assigned_user_id'] ) ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="sc_ei_unassign_review">
						<input type="hidden" name="inquiry_id" value="<?php echo esc_attr( $inquiry['id'] ); ?>">
						<?php wp_nonce_field( 'sc_ei_unassign_review_' . absint( $inquiry['id'] ) ); ?>
						<button type="submit" class="button"><?php esc_html_e( 'Remove Assignment', 'sustainable-catalyst-engagement-intake' ); ?></button>
					</form>
				<?php endif; ?>

				<?php if ( current_user_can( 'sc_intake_export_review_packet' ) ) : ?>
					<p><a class="button" href="<?php echo esc_url( $export_url ); ?>"><?php esc_html_e( 'Export Private Review Packet', 'sustainable-catalyst-engagement-intake' ); ?></a></p>
					<p class="description"><?php esc_html_e( 'Exports JSON metadata and review history, not physical document contents.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				<?php endif; ?>
			</section>

			<section class="sc-ei-admin__card">
				<h2><?php esc_html_e( 'Review State', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<dl class="sc-ei-admin__details">
					<dt><?php esc_html_e( 'Fit', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Review_Schema::label( SC_EI_Review_Schema::fit_decisions(), $inquiry['fit_decision'] ) ); ?> · <?php echo esc_html( SC_EI_Review_Schema::label( SC_EI_Review_Schema::confidence_levels(), $inquiry['fit_confidence'] ) ); ?></dd>
					<dt><?php esc_html_e( 'Risk', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Review_Schema::label( SC_EI_Review_Schema::risk_levels(), $inquiry['risk_level'] ) ); ?></dd>
					<dt><?php esc_html_e( 'Evidence', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Review_Schema::label( SC_EI_Review_Schema::evidence_readiness_levels(), $inquiry['evidence_readiness'] ) ); ?></dd>
					<dt><?php esc_html_e( 'Scope', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Review_Schema::label( SC_EI_Review_Schema::scope_clarity_levels(), $inquiry['scope_clarity'] ) ); ?></dd>
					<dt><?php esc_html_e( 'Next step', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Review_Schema::label( SC_EI_Review_Schema::next_steps(), $inquiry['recommended_next_step'] ) ); ?></dd>
					<dt><?php esc_html_e( 'Escalation', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Review_Schema::label( SC_EI_Review_Schema::escalation_statuses(), $inquiry['escalation_status'] ) ); ?></dd>
				</dl>
			</section>

			<section class="sc-ei-admin__card">
				<h2><?php esc_html_e( 'Document Readiness', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<div class="sc-ei-review-document-summary">
					<div><strong><?php echo esc_html( $document_summary['total'] ); ?></strong><span><?php esc_html_e( 'active', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
					<div><strong><?php echo esc_html( $document_summary['clean'] ); ?></strong><span><?php esc_html_e( 'clean', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
					<div><strong><?php echo esc_html( $document_summary['approved'] ); ?></strong><span><?php esc_html_e( 'approved', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
					<div class="<?php echo $document_summary['attention'] ? 'sc-ei-summary-danger' : ''; ?>"><strong><?php echo esc_html( $document_summary['attention'] ); ?></strong><span><?php esc_html_e( 'attention', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
				</div>
				<p><a class="button" href="<?php echo esc_url( $quarantine_url ); ?>"><?php esc_html_e( 'Open Quarantine Records', 'sustainable-catalyst-engagement-intake' ); ?></a></p>
				<p class="description"><?php esc_html_e( 'Review Workspace summarizes document state. Download, scan, approval, retention, and deletion remain in the full inquiry and Quarantine workspaces.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			</section>

			<section class="sc-ei-admin__card">
				<h2><?php esc_html_e( 'Recent Audit Events', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<?php if ( $audit_log ) : ?><ol class="sc-ei-review-mini-audit"><?php foreach ( array_slice( $audit_log, 0, 12 ) as $event ) : ?><li><strong><?php echo esc_html( ucwords( str_replace( '_', ' ', $event['event_type'] ) ) ); ?></strong><span><?php echo esc_html( get_date_from_gmt( $event['created_at'], 'M j, g:i a' ) ); ?></span><p><?php echo esc_html( $event['event_message'] ); ?></p></li><?php endforeach; ?></ol><?php else : ?><p><?php esc_html_e( 'No audit events recorded.', 'sustainable-catalyst-engagement-intake' ); ?></p><?php endif; ?>
			</section>
		</aside>
	</div>
</div>
