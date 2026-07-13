<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$editable = current_user_can( 'sc_intake_create_fit_assessments' )
	&& in_array( $assessment['status'], array( 'draft', 'changes_requested', 'submitted', 'second_review_requested', 'ready_to_finalize' ), true );
$score_label = null === $assessment['advisory_score']
	? __( 'Not available', 'sustainable-catalyst-engagement-intake' )
	: number_format_i18n( (float) $assessment['advisory_score'], 2 ) . '/100';
$success_messages = array(
	'fit_assessment_created'   => __( 'Fit assessment draft created.', 'sustainable-catalyst-engagement-intake' ),
	'fit_assessment_saved'     => __( 'Human-authored fit assessment saved.', 'sustainable-catalyst-engagement-intake' ),
	'fit_assessment_submitted' => __( 'Assessment submitted into the human review workflow.', 'sustainable-catalyst-engagement-intake' ),
	'fit_second_review_saved'  => __( 'Independent second review recorded.', 'sustainable-catalyst-engagement-intake' ),
	'fit_assessment_finalized' => __( 'Assessment finalized. No inquiry status, communication, scheduling, proposal, or referral was changed automatically.', 'sustainable-catalyst-engagement-intake' ),
	'fit_assessment_applied'   => __( 'Fit conclusions were explicitly applied to the Review Workspace. Inquiry status was not changed.', 'sustainable-catalyst-engagement-intake' ),
);
?>
<div class="wrap sc-ei-admin sc-ei-fit-detail">
	<h1><?php echo esc_html( sprintf( __( 'Fit Assessment %1$s · v%2$d', 'sustainable-catalyst-engagement-intake' ), $inquiry['reference'], absint( $assessment['assessment_version'] ) ) ); ?></h1>
	<p>
		<a href="<?php echo esc_url( SC_EI_Fit_Admin::url() ); ?>"><?php esc_html_e( 'Fit Assessment Queue', 'sustainable-catalyst-engagement-intake' ); ?></a>
		· <a href="<?php echo esc_url( add_query_arg( array( 'page' => 'sc-engagement-intake', 'action' => 'view', 'inquiry' => absint( $inquiry['id'] ) ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Full Inquiry', 'sustainable-catalyst-engagement-intake' ); ?></a>
		· <a href="<?php echo esc_url( SC_EI_Review_Admin::detail_url( absint( $inquiry['id'] ) ) ); ?>"><?php esc_html_e( 'Review Workspace', 'sustainable-catalyst-engagement-intake' ); ?></a>
		· <a href="<?php echo esc_url( SC_EI_Privacy_Admin::url( 'overview', array( 'inquiry' => absint( $inquiry['id'] ) ) ) ); ?>"><?php esc_html_e( 'Privacy Center', 'sustainable-catalyst-engagement-intake' ); ?></a>
	</p>

	<?php if ( isset( $success_messages[ $message ] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $success_messages[ $message ] ); ?></p></div>
	<?php elseif ( $message ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php echo esc_html( ucwords( str_replace( '_', ' ', $message ) ) ); ?></p></div>
	<?php endif; ?>

	<div class="sc-ei-fit-boundary">
		<strong><?php esc_html_e( 'Human judgment only', 'sustainable-catalyst-engagement-intake' ); ?></strong>
		<span><?php esc_html_e( 'The score summarizes manually selected ratings. It has no threshold, does not create the recommendation, and cannot accept or reject the inquiry.', 'sustainable-catalyst-engagement-intake' ); ?></span>
	</div>

	<?php if ( in_array( $inquiry['privacy_status'], array( 'restricted', 'erasure_requested' ), true ) || absint( $inquiry['legal_hold_count'] ) > 0 ) : ?>
		<div class="notice notice-warning"><p><strong><?php echo esc_html( SC_EI_Privacy_Schema::label( SC_EI_Privacy_Schema::privacy_statuses(), $inquiry['privacy_status'] ) ); ?></strong> · <?php echo esc_html( sprintf( __( '%d active hold(s). Review privacy restrictions before adding or disclosing assessment evidence.', 'sustainable-catalyst-engagement-intake' ), absint( $inquiry['legal_hold_count'] ) ) ); ?></p></div>
	<?php endif; ?>

	<div class="sc-ei-fit-summary-grid">
		<div><strong><?php echo esc_html( SC_EI_Fit_Schema::label( SC_EI_Fit_Schema::statuses(), $assessment['status'] ) ); ?></strong><span><?php esc_html_e( 'workflow state', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div><strong><?php echo esc_html( SC_EI_Fit_Schema::label( SC_EI_Fit_Schema::recommendations(), $assessment['recommendation'] ) ); ?></strong><span><?php esc_html_e( 'human recommendation', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div><strong><?php echo esc_html( $score_label ); ?></strong><span><?php esc_html_e( 'advisory signal', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div><strong><?php echo esc_html( number_format_i18n( absint( $assessment['material_concern_count'] ) ) ); ?></strong><span><?php esc_html_e( 'material concerns', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div><strong><?php echo esc_html( $assessor ? $assessor->display_name : __( 'Unassigned', 'sustainable-catalyst-engagement-intake' ) ); ?></strong><span><?php esc_html_e( 'assessor', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div><strong><?php echo esc_html( $second_reviewer ? $second_reviewer->display_name : __( 'Not assigned', 'sustainable-catalyst-engagement-intake' ) ); ?></strong><span><?php esc_html_e( 'second reviewer', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
	</div>

	<div class="sc-ei-fit-detail-layout">
		<main>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-fit-assessment-form">
				<input type="hidden" name="action" value="sc_ei_save_fit_assessment">
				<input type="hidden" name="assessment_id" value="<?php echo esc_attr( $assessment['id'] ); ?>">
				<input type="hidden" name="row_version" value="<?php echo esc_attr( $assessment['row_version'] ); ?>">
				<?php wp_nonce_field( 'sc_ei_save_fit_assessment_' . absint( $assessment['id'] ) ); ?>

				<section class="sc-ei-admin__card sc-ei-admin__card--wide">
					<h2><?php esc_html_e( 'Overall Human Judgment', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<div class="sc-ei-review-form-grid">
						<label><span><?php esc_html_e( 'Recommendation', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="recommendation" <?php disabled( ! $editable ); ?>><?php foreach ( SC_EI_Fit_Schema::recommendations() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $assessment['recommendation'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
						<label><span><?php esc_html_e( 'Confidence', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="confidence" <?php disabled( ! $editable ); ?>><?php foreach ( SC_EI_Fit_Schema::confidence_levels() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $assessment['confidence'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
						<label><span><?php esc_html_e( 'Recommended service route', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="service_route" <?php disabled( ! $editable ); ?>><?php foreach ( SC_EI_Fit_Schema::service_routes() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $assessment['service_route'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
						<label><span><?php esc_html_e( 'Scope boundary', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="scope_boundary" <?php disabled( ! $editable ); ?>><?php foreach ( SC_EI_Fit_Schema::scope_boundaries() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $assessment['scope_boundary'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
						<label class="sc-ei-review-form-grid__wide"><span><?php esc_html_e( 'Overall assessment summary', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="overall_summary" rows="5" <?php disabled( ! $editable ); ?>><?php echo esc_textarea( $assessment['overall_summary'] ); ?></textarea></label>
						<label class="sc-ei-review-form-grid__wide"><span><?php esc_html_e( 'Recommendation rationale', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="recommendation_rationale" rows="6" <?php disabled( ! $editable ); ?>><?php echo esc_textarea( $assessment['recommendation_rationale'] ); ?></textarea></label>
						<label class="sc-ei-review-form-grid__wide"><span><?php esc_html_e( 'Conditions or clarification needed', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="conditions_for_fit" rows="4" <?php disabled( ! $editable ); ?>><?php echo esc_textarea( $assessment['conditions_for_fit'] ); ?></textarea></label>
						<label class="sc-ei-review-form-grid__wide"><span><?php esc_html_e( 'Limitations and uncertainty', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="limitations_notes" rows="4" <?php disabled( ! $editable ); ?>><?php echo esc_textarea( $assessment['limitations_notes'] ); ?></textarea></label>
						<label class="sc-ei-review-form-grid__wide"><span><?php esc_html_e( 'Referral or decline notes', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="referral_notes" rows="4" <?php disabled( ! $editable ); ?>><?php echo esc_textarea( $assessment['referral_notes'] ); ?></textarea></label>
					</div>
				</section>

				<?php foreach ( $groups as $group_key => $group_label ) : ?>
					<section class="sc-ei-admin__card sc-ei-admin__card--wide sc-ei-fit-criteria-group">
						<h2><?php echo esc_html( $group_label ); ?></h2>
						<?php foreach ( $criteria as $key => $criterion ) : ?>
							<?php if ( $criterion['group'] !== $group_key ) { continue; } ?>
							<?php $item = $assessment['items'][ $key ] ?? array( 'rating' => 'not_assessed', 'evidence_note' => '', 'concern_note' => '', 'source_refs' => array(), 'is_material_concern' => 0 ); ?>
							<fieldset class="sc-ei-fit-criterion">
								<legend><?php echo esc_html( $criterion['label'] ); ?> <span><?php echo esc_html( sprintf( __( 'weight %.2f', 'sustainable-catalyst-engagement-intake' ), (float) $criterion['weight'] ) ); ?></span></legend>
								<p class="description"><?php echo esc_html( $criterion['description'] ); ?></p>
								<div class="sc-ei-fit-criterion-grid">
									<label><span><?php esc_html_e( 'Human rating', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="fit_items[<?php echo esc_attr( $key ); ?>][rating]" <?php disabled( ! $editable ); ?>><?php foreach ( SC_EI_Fit_Schema::ratings() as $rating_key => $rating ) : ?><option value="<?php echo esc_attr( $rating_key ); ?>" <?php selected( $item['rating'], $rating_key ); ?>><?php echo esc_html( $rating['label'] ); ?></option><?php endforeach; ?></select></label>
									<label class="sc-ei-fit-material-check"><input type="checkbox" name="fit_items[<?php echo esc_attr( $key ); ?>][is_material_concern]" value="1" <?php checked( ! empty( $item['is_material_concern'] ) ); ?> <?php disabled( ! $editable ); ?>> <span><?php esc_html_e( 'Material concern requiring explicit attention', 'sustainable-catalyst-engagement-intake' ); ?></span></label>
									<label class="sc-ei-fit-wide"><span><?php esc_html_e( 'Evidence and human reasoning', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="fit_items[<?php echo esc_attr( $key ); ?>][evidence_note]" rows="4" <?php disabled( ! $editable ); ?>><?php echo esc_textarea( $item['evidence_note'] ); ?></textarea></label>
									<label class="sc-ei-fit-wide"><span><?php esc_html_e( 'Concern, limitation, or mitigation', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="fit_items[<?php echo esc_attr( $key ); ?>][concern_note]" rows="3" <?php disabled( ! $editable ); ?>><?php echo esc_textarea( $item['concern_note'] ); ?></textarea></label>
									<label class="sc-ei-fit-wide"><span><?php esc_html_e( 'Source references', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="fit_items[<?php echo esc_attr( $key ); ?>][source_refs]" rows="2" <?php disabled( ! $editable ); ?>><?php echo esc_textarea( implode( "\n", (array) ( $item['source_refs'] ?? array() ) ) ); ?></textarea><span class="description"><?php esc_html_e( 'Use inquiry fields, document names, page references, communication dates, or other private record pointers. Do not paste unnecessary sensitive content.', 'sustainable-catalyst-engagement-intake' ); ?></span></label>
								</div>
							</fieldset>
						<?php endforeach; ?>
					</section>
				<?php endforeach; ?>

				<section class="sc-ei-admin__card sc-ei-admin__card--wide">
					<h2><?php esc_html_e( 'Human Attestation and Assistance Disclosure', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<label><span><?php esc_html_e( 'Assistance disclosure', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="assistance_disclosure" <?php disabled( ! $editable ); ?>><?php foreach ( SC_EI_Fit_Schema::assistance_disclosures() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $assessment['assistance_disclosure'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
					<label><span><?php esc_html_e( 'Assistance notes', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="assistance_notes" rows="4" <?php disabled( ! $editable ); ?>><?php echo esc_textarea( $assessment['assistance_notes'] ); ?></textarea></label>
					<label class="sc-ei-fit-attestation"><input type="checkbox" name="human_attestation" value="1" <?php checked( ! empty( $assessment['human_attestation'] ) ); ?> <?php disabled( ! $editable ); ?>> <span><?php esc_html_e( 'I personally reviewed the inquiry and relevant private records. The ratings, recommendation, route, boundaries, and rationale represent my human judgment. Any assistance used is disclosed above.', 'sustainable-catalyst-engagement-intake' ); ?></span></label>
					<?php if ( $editable ) : ?><p><button type="submit" class="button button-primary"><?php esc_html_e( 'Save Assessment Draft', 'sustainable-catalyst-engagement-intake' ); ?></button></p><?php endif; ?>
				</section>
			</form>

			<?php if ( in_array( $assessment['status'], array( 'draft', 'changes_requested', 'submitted' ), true ) && current_user_can( 'sc_intake_create_fit_assessments' ) ) : ?>
				<section class="sc-ei-admin__card sc-ei-admin__card--wide">
					<h2><?php esc_html_e( 'Submit Saved Assessment', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<p><?php esc_html_e( 'Submission validates the saved criteria, evidence, recommendation, rationale, conditions, and attestation. It does not change the inquiry or contact the sender.', 'sustainable-catalyst-engagement-intake' ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="sc_ei_submit_fit_assessment"><input type="hidden" name="assessment_id" value="<?php echo esc_attr( $assessment['id'] ); ?>"><input type="hidden" name="row_version" value="<?php echo esc_attr( $assessment['row_version'] ); ?>"><?php wp_nonce_field( 'sc_ei_submit_fit_assessment_' . absint( $assessment['id'] ) ); ?>
						<button type="submit" class="button"><?php esc_html_e( 'Submit into Human Review', 'sustainable-catalyst-engagement-intake' ); ?></button>
					</form>
				</section>
			<?php endif; ?>

			<?php if ( in_array( $assessment['status'], array( 'submitted', 'second_review_requested', 'changes_requested', 'ready_to_finalize' ), true ) && current_user_can( 'sc_intake_review_fit_assessments' ) ) : ?>
				<section class="sc-ei-admin__card sc-ei-admin__card--wide">
					<h2><?php esc_html_e( 'Independent Second Review', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<?php if ( ! empty( $assessment['second_review_reason'] ) ) : ?><div class="sc-ei-diagnostic-warning"><strong><?php esc_html_e( 'Required because:', 'sustainable-catalyst-engagement-intake' ); ?></strong><br><?php echo nl2br( esc_html( $assessment['second_review_reason'] ) ); ?></div><?php endif; ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-review-form-grid">
						<input type="hidden" name="action" value="sc_ei_second_review_fit_assessment"><input type="hidden" name="assessment_id" value="<?php echo esc_attr( $assessment['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_second_review_fit_assessment_' . absint( $assessment['id'] ) ); ?>
						<label><span><?php esc_html_e( 'Disposition', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="disposition"><?php foreach ( SC_EI_Fit_Schema::second_review_dispositions() as $key => $label ) : ?><?php if ( 'not_requested' !== $key ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endif; ?><?php endforeach; ?></select></label>
						<label><span><?php esc_html_e( 'Recommendation reviewed', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="second_recommendation"><?php foreach ( SC_EI_Fit_Schema::recommendations() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $assessment['recommendation'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
						<label><span><?php esc_html_e( 'Service route reviewed', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="second_service_route"><?php foreach ( SC_EI_Fit_Schema::service_routes() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $assessment['service_route'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
						<label><span><?php esc_html_e( 'Scope boundary reviewed', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="second_scope_boundary"><?php foreach ( SC_EI_Fit_Schema::scope_boundaries() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $assessment['scope_boundary'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
						<label class="sc-ei-review-form-grid__wide"><span><?php esc_html_e( 'Second-review notes', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="second_review_notes" rows="5" required></textarea></label>
						<label class="sc-ei-review-form-grid__wide"><span><?php esc_html_e( 'Required changes or escalation reason', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="second_required_changes" rows="4"></textarea></label>
						<label class="sc-ei-review-form-grid__wide"><span><?php esc_html_e( 'Reviewer conflict disclosure', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="second_conflict_disclosure" rows="3"></textarea></label>
						<label class="sc-ei-review-form-grid__wide sc-ei-fit-attestation"><input type="checkbox" name="second_human_attestation" value="1" required> <span><?php esc_html_e( 'I independently reviewed the inquiry and assessment and am recording my own human judgment.', 'sustainable-catalyst-engagement-intake' ); ?></span></label>
						<p class="sc-ei-review-form-grid__wide"><button type="submit" class="button"><?php esc_html_e( 'Record Second Review', 'sustainable-catalyst-engagement-intake' ); ?></button></p>
					</form>
				</section>
			<?php endif; ?>

			<?php if ( 'ready_to_finalize' === $assessment['status'] && current_user_can( 'sc_intake_finalize_fit_assessments' ) ) : ?>
				<section class="sc-ei-admin__card sc-ei-admin__card--wide sc-ei-fit-finalize-zone">
					<h2><?php esc_html_e( 'Finalize Human Assessment', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<p><?php esc_html_e( 'Finalization freezes this assessment version. It does not change inquiry status, send correspondence, schedule a meeting, issue a proposal, or create a referral.', 'sustainable-catalyst-engagement-intake' ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-fit-confirm-form">
						<input type="hidden" name="action" value="sc_ei_finalize_fit_assessment"><input type="hidden" name="assessment_id" value="<?php echo esc_attr( $assessment['id'] ); ?>"><input type="hidden" name="row_version" value="<?php echo esc_attr( $assessment['row_version'] ); ?>"><?php wp_nonce_field( 'sc_ei_finalize_fit_assessment_' . absint( $assessment['id'] ) ); ?>
						<label><span><?php echo esc_html( sprintf( __( 'Type FINALIZE %d', 'sustainable-catalyst-engagement-intake' ), absint( $assessment['id'] ) ) ); ?></span><input type="text" name="confirm_finalize" required autocomplete="off"></label>
						<button type="submit" class="button button-primary"><?php esc_html_e( 'Finalize Assessment', 'sustainable-catalyst-engagement-intake' ); ?></button>
					</form>
				</section>
			<?php endif; ?>

			<?php if ( 'finalized' === $assessment['status'] ) : ?>
				<section class="sc-ei-admin__card sc-ei-admin__card--wide">
					<h2><?php esc_html_e( 'Finalized Assessment Actions', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<div class="sc-ei-fit-final-actions">
						<?php if ( current_user_can( 'sc_intake_apply_fit_to_review' ) ) : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-fit-confirm-form">
								<input type="hidden" name="action" value="sc_ei_apply_fit_assessment"><input type="hidden" name="assessment_id" value="<?php echo esc_attr( $assessment['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_apply_fit_assessment_' . absint( $assessment['id'] ) ); ?>
								<label><span><?php echo esc_html( sprintf( __( 'Type APPLY %d', 'sustainable-catalyst-engagement-intake' ), absint( $assessment['id'] ) ) ); ?></span><input type="text" name="confirm_apply" required autocomplete="off"></label>
								<button type="submit" class="button"><?php esc_html_e( 'Apply to Review Workspace', 'sustainable-catalyst-engagement-intake' ); ?></button>
							</form>
							<p class="description"><?php esc_html_e( 'This copies fit decision, confidence, next step, summary, and rationale into a new immutable Review Workspace snapshot. It does not change inquiry status.', 'sustainable-catalyst-engagement-intake' ); ?></p>
						<?php endif; ?>
						<?php if ( current_user_can( 'sc_intake_create_fit_assessments' ) ) : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="sc_ei_create_fit_assessment"><input type="hidden" name="inquiry_id" value="<?php echo esc_attr( $inquiry['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_create_fit_assessment' ); ?><button type="submit" class="button"><?php esc_html_e( 'Create New Assessment Version', 'sustainable-catalyst-engagement-intake' ); ?></button></form>
						<?php endif; ?>
					</div>
				</section>
			<?php endif; ?>
		</main>

		<aside>
			<section class="sc-ei-admin__card">
				<h2><?php esc_html_e( 'Inquiry Context', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<dl class="sc-ei-admin__details">
					<dt><?php esc_html_e( 'Contact', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $inquiry['contact_name'] ); ?></dd>
					<dt><?php esc_html_e( 'Organization', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $inquiry['organization'] ?: '—' ); ?></dd>
					<dt><?php esc_html_e( 'Inquiry type', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $inquiry['inquiry_type'] ); ?></dd>
					<dt><?php esc_html_e( 'Service interest', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $inquiry['service_interest'] ?: '—' ); ?></dd>
					<dt><?php esc_html_e( 'Budget', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $inquiry['budget_range'] ?: '—' ); ?></dd>
					<dt><?php esc_html_e( 'Review stage', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Review_Schema::label( SC_EI_Review_Schema::stages(), $inquiry['review_stage'] ) ); ?></dd>
					<dt><?php esc_html_e( 'Inquiry status', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Statuses::label( $inquiry['status'] ) ); ?></dd>
				</dl>
				<p><strong><?php esc_html_e( 'Project summary', 'sustainable-catalyst-engagement-intake' ); ?></strong><br><?php echo nl2br( esc_html( $inquiry['project_summary'] ?: $inquiry['message'] ) ); ?></p>
				<p><strong><?php esc_html_e( 'Desired outcome', 'sustainable-catalyst-engagement-intake' ); ?></strong><br><?php echo nl2br( esc_html( $inquiry['desired_outcome'] ?: '—' ) ); ?></p>
			</section>

			<section class="sc-ei-admin__card">
				<h2><?php esc_html_e( 'Assessment Integrity', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<dl class="sc-ei-admin__details">
					<dt><?php esc_html_e( 'Assessment ID', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $assessment['id'] ); ?></dd>
					<dt><?php esc_html_e( 'Assessment version', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $assessment['assessment_version'] ); ?></dd>
					<dt><?php esc_html_e( 'Row version', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $assessment['row_version'] ); ?></dd>
					<dt><?php esc_html_e( 'Score complete', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo $assessment['score_complete'] ? esc_html__( 'yes', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'no', 'sustainable-catalyst-engagement-intake' ); ?></dd>
					<dt><?php esc_html_e( 'Second review required', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo $assessment['second_review_required'] ? esc_html__( 'yes', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'no', 'sustainable-catalyst-engagement-intake' ); ?></dd>
					<dt><?php esc_html_e( 'Second-review disposition', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Fit_Schema::label( SC_EI_Fit_Schema::second_review_dispositions(), $assessment['second_review_disposition'] ) ); ?></dd>
					<dt><?php esc_html_e( 'Assistance disclosure', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Fit_Schema::label( SC_EI_Fit_Schema::assistance_disclosures(), $assessment['assistance_disclosure'] ) ); ?></dd>
					<dt><?php esc_html_e( 'Updated', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( get_date_from_gmt( $assessment['updated_at'], 'M j, Y g:i a' ) ); ?></dd>
					<dt><?php esc_html_e( 'Finalized', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo $assessment['finalized_at'] ? esc_html( get_date_from_gmt( $assessment['finalized_at'], 'M j, Y g:i a' ) ) : '—'; ?></dd>
				</dl>
				<?php if ( current_user_can( 'sc_intake_export_fit_assessments' ) ) : ?><p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sc_ei_export_fit_assessment&assessment=' . absint( $assessment['id'] ) ), 'sc_ei_export_fit_assessment_' . absint( $assessment['id'] ) ) ); ?>"><?php esc_html_e( 'Export Private Assessment JSON', 'sustainable-catalyst-engagement-intake' ); ?></a></p><?php endif; ?>
			</section>

			<?php if ( $assessment['second_reviews'] ) : ?>
				<section class="sc-ei-admin__card">
					<h2><?php esc_html_e( 'Second-Review History', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<?php foreach ( $assessment['second_reviews'] as $review ) : ?>
						<article class="sc-ei-fit-review-history">
							<strong><?php echo esc_html( $review['reviewer_name'] ?: __( 'Unknown reviewer', 'sustainable-catalyst-engagement-intake' ) ); ?></strong>
							<span><?php echo esc_html( SC_EI_Fit_Schema::label( SC_EI_Fit_Schema::second_review_dispositions(), $review['disposition'] ) ); ?></span>
							<p><?php echo nl2br( esc_html( $review['review_notes'] ) ); ?></p>
							<?php if ( $review['required_changes'] ) : ?><p><strong><?php esc_html_e( 'Changes:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo esc_html( $review['required_changes'] ); ?></p><?php endif; ?>
							<small><?php echo esc_html( get_date_from_gmt( $review['created_at'], 'M j, Y g:i a' ) ); ?></small>
						</article>
					<?php endforeach; ?>
				</section>
			<?php endif; ?>
		</aside>
	</div>
</div>
