<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$message = isset( $_GET['sc_ei_msg'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_msg'] ) ) : '';
?>
<div class="wrap sc-ei-admin sc-ei-fit-workspace">
	<h1><?php esc_html_e( 'Human-Controlled Fit Assessment', 'sustainable-catalyst-engagement-intake' ); ?></h1>
	<p><?php esc_html_e( 'Assess alignment, readiness, feasibility, ethics, independence, and delivery conditions through documented human judgment. Advisory scores have no acceptance or rejection threshold and never change an inquiry automatically.', 'sustainable-catalyst-engagement-intake' ); ?></p>

	<?php if ( 'fit_settings_saved' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Fit assessment settings saved.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( $message ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php echo esc_html( ucwords( str_replace( '_', ' ', $message ) ) ); ?></p></div>
	<?php endif; ?>

	<div class="sc-ei-fit-boundary">
		<strong><?php esc_html_e( 'Human-control boundary', 'sustainable-catalyst-engagement-intake' ); ?></strong>
		<span><?php esc_html_e( 'No automated acceptance, rejection, status change, communication, meeting, proposal, or referral occurs from this workspace.', 'sustainable-catalyst-engagement-intake' ); ?></span>
	</div>

	<div class="sc-ei-fit-metrics">
		<a href="<?php echo esc_url( SC_EI_Fit_Admin::url( 0, array( 'status' => 'draft' ) ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['draft_count'] ) ); ?></strong><span><?php esc_html_e( 'drafts', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( SC_EI_Fit_Admin::url( 0, array( 'status' => 'submitted' ) ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['submitted_count'] ) ); ?></strong><span><?php esc_html_e( 'submitted', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a class="<?php echo $metrics['second_review_count'] ? 'sc-ei-review-metric--attention' : ''; ?>" href="<?php echo esc_url( SC_EI_Fit_Admin::url( 0, array( 'status' => 'second_review_requested' ) ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['second_review_count'] ) ); ?></strong><span><?php esc_html_e( 'second reviews', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( SC_EI_Fit_Admin::url( 0, array( 'status' => 'changes_requested' ) ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['changes_count'] ) ); ?></strong><span><?php esc_html_e( 'changes requested', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( SC_EI_Fit_Admin::url( 0, array( 'status' => 'ready_to_finalize' ) ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['ready_count'] ) ); ?></strong><span><?php esc_html_e( 'ready to finalize', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( SC_EI_Fit_Admin::url( 0, array( 'status' => 'finalized' ) ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['finalized_count'] ) ); ?></strong><span><?php esc_html_e( 'finalized', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a class="<?php echo $metrics['stale_count'] ? 'sc-ei-review-metric--danger' : ''; ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['stale_count'] ) ); ?></strong><span><?php esc_html_e( 'stale open assessments', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a class="<?php echo $metrics['concern_count'] ? 'sc-ei-review-metric--attention' : ''; ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['concern_count'] ) ); ?></strong><span><?php esc_html_e( 'open material concerns', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
	</div>

	<div class="sc-ei-fit-toolbar">
		<form method="get" class="sc-ei-operation-filter-form">
			<input type="hidden" name="page" value="sc-engagement-intake-fit">
			<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search reference, contact, organization, or summary', 'sustainable-catalyst-engagement-intake' ); ?>">
			<select name="status">
				<option value=""><?php esc_html_e( 'All active states', 'sustainable-catalyst-engagement-intake' ); ?></option>
				<?php foreach ( SC_EI_Fit_Schema::statuses() as $key => $label ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<label><input type="checkbox" name="mine" value="1" <?php checked( ! empty( $_GET['mine'] ) ); ?>> <?php esc_html_e( 'My assessments or reviews', 'sustainable-catalyst-engagement-intake' ); ?></label>
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'sustainable-catalyst-engagement-intake' ); ?></button>
		</form>

		<?php if ( current_user_can( 'sc_intake_create_fit_assessments' ) ) : ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-fit-create-form">
				<input type="hidden" name="action" value="sc_ei_create_fit_assessment">
				<label><span><?php esc_html_e( 'Inquiry ID', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" name="inquiry_id" min="1" required data-sc-ei-fit-create-id></label>
				<?php wp_nonce_field( 'sc_ei_create_fit_assessment' ); ?>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Start or Open Assessment', 'sustainable-catalyst-engagement-intake' ); ?></button>
			</form>
			<p class="description"><?php esc_html_e( 'Open an inquiry first for its direct assessment link. The general form above is retained for administrators who know the private inquiry ID.', 'sustainable-catalyst-engagement-intake' ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( current_user_can( 'sc_intake_manage_fit_settings' ) ) : ?>
		<details class="sc-ei-admin__card sc-ei-admin__card--wide sc-ei-fit-settings-panel">
			<summary><strong><?php esc_html_e( 'Fit Assessment Policy Settings', 'sustainable-catalyst-engagement-intake' ); ?></strong></summary>
			<p><?php esc_html_e( 'These controls affect evidence and review workflow only. Human attestation and the prohibition on automated decisions remain fixed.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-fit-settings-form">
				<input type="hidden" name="action" value="sc_ei_save_fit_settings"><?php wp_nonce_field( 'sc_ei_save_fit_settings' ); ?>
				<label><input type="checkbox" name="fit_settings[fit_advisory_score_enabled]" value="1" <?php checked( $settings['fit_advisory_score_enabled'], 1 ); ?>> <?php esc_html_e( 'Show transparent advisory signal without thresholds', 'sustainable-catalyst-engagement-intake' ); ?></label>
				<label><input type="checkbox" name="fit_settings[fit_require_evidence_for_assessed_items]" value="1" <?php checked( $settings['fit_require_evidence_for_assessed_items'], 1 ); ?>> <?php esc_html_e( 'Require evidence or reasoning for assessed criteria', 'sustainable-catalyst-engagement-intake' ); ?></label>
				<label><input type="checkbox" name="fit_settings[fit_require_rationale_for_finalization]" value="1" <?php checked( $settings['fit_require_rationale_for_finalization'], 1 ); ?>> <?php esc_html_e( 'Require recommendation rationale', 'sustainable-catalyst-engagement-intake' ); ?></label>
				<label><input type="checkbox" name="fit_settings[fit_require_second_review_high_risk]" value="1" <?php checked( $settings['fit_require_second_review_high_risk'], 1 ); ?>> <?php esc_html_e( 'Require review for material ethics, privacy, independence, or risk concerns', 'sustainable-catalyst-engagement-intake' ); ?></label>
				<label><input type="checkbox" name="fit_settings[fit_require_second_review_conflict]" value="1" <?php checked( $settings['fit_require_second_review_conflict'], 1 ); ?>> <?php esc_html_e( 'Require review for conflict or independence boundaries', 'sustainable-catalyst-engagement-intake' ); ?></label>
				<label><input type="checkbox" name="fit_settings[fit_require_second_review_decline]" value="1" <?php checked( $settings['fit_require_second_review_decline'], 1 ); ?>> <?php esc_html_e( 'Require review for not-a-fit recommendations', 'sustainable-catalyst-engagement-intake' ); ?></label>
				<label><input type="checkbox" name="fit_settings[fit_require_second_review_unsafe_scope]" value="1" <?php checked( $settings['fit_require_second_review_unsafe_scope'], 1 ); ?>> <?php esc_html_e( 'Require review for unsafe or prohibited scope', 'sustainable-catalyst-engagement-intake' ); ?></label>
				<label><input type="checkbox" name="fit_settings[fit_distinct_second_reviewer]" value="1" <?php checked( $settings['fit_distinct_second_reviewer'], 1 ); ?>> <?php esc_html_e( 'Require a second reviewer different from the assessor', 'sustainable-catalyst-engagement-intake' ); ?></label>
				<label><span><?php esc_html_e( 'Stale interval in days', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="1" max="365" name="fit_settings[fit_assessment_stale_days]" value="<?php echo esc_attr( $settings['fit_assessment_stale_days'] ); ?>"></label>
				<label><span><?php esc_html_e( 'Queue limit', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="10" max="500" name="fit_settings[fit_assessment_queue_limit]" value="<?php echo esc_attr( $settings['fit_assessment_queue_limit'] ); ?>"></label>
				<p><button type="submit" class="button"><?php esc_html_e( 'Save Fit Policy Settings', 'sustainable-catalyst-engagement-intake' ); ?></button></p>
			</form>
		</details>
	<?php endif; ?>

	<section class="sc-ei-admin__card sc-ei-admin__card--wide">
		<h2><?php esc_html_e( 'Assessment Queue', 'sustainable-catalyst-engagement-intake' ); ?></h2>
		<table class="widefat striped sc-ei-fit-table">
			<thead><tr>
				<th><?php esc_html_e( 'Inquiry', 'sustainable-catalyst-engagement-intake' ); ?></th>
				<th><?php esc_html_e( 'Assessment', 'sustainable-catalyst-engagement-intake' ); ?></th>
				<th><?php esc_html_e( 'Human recommendation', 'sustainable-catalyst-engagement-intake' ); ?></th>
				<th><?php esc_html_e( 'Route / boundary', 'sustainable-catalyst-engagement-intake' ); ?></th>
				<th><?php esc_html_e( 'Advisory signal', 'sustainable-catalyst-engagement-intake' ); ?></th>
				<th><?php esc_html_e( 'Review ownership', 'sustainable-catalyst-engagement-intake' ); ?></th>
				<th><?php esc_html_e( 'Updated', 'sustainable-catalyst-engagement-intake' ); ?></th>
			</tr></thead>
			<tbody>
				<?php if ( ! $assessments ) : ?>
					<tr><td colspan="7"><?php esc_html_e( 'No fit assessments match the current filters.', 'sustainable-catalyst-engagement-intake' ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( $assessments as $assessment ) : ?>
					<tr>
						<td>
							<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'sc-engagement-intake', 'action' => 'view', 'inquiry' => absint( $assessment['inquiry_id'] ) ), admin_url( 'admin.php' ) ) ); ?>"><strong><?php echo esc_html( $assessment['reference'] ); ?></strong></a><br>
							<?php echo esc_html( $assessment['contact_name'] ); ?>
							<?php if ( $assessment['organization'] ) : ?><br><span class="description"><?php echo esc_html( $assessment['organization'] ); ?></span><?php endif; ?>
							<?php if ( in_array( $assessment['privacy_status'], array( 'restricted', 'erasure_requested' ), true ) || absint( $assessment['legal_hold_count'] ) > 0 ) : ?><br><span class="sc-ei-inline-warning"><?php esc_html_e( 'Privacy or hold attention', 'sustainable-catalyst-engagement-intake' ); ?></span><?php endif; ?>
						</td>
						<td>
							<a href="<?php echo esc_url( SC_EI_Fit_Admin::url( absint( $assessment['id'] ) ) ); ?>">v<?php echo esc_html( $assessment['assessment_version'] ); ?> · <?php echo esc_html( SC_EI_Fit_Schema::label( SC_EI_Fit_Schema::statuses(), $assessment['status'] ) ); ?></a><br>
							<span class="description">#<?php echo esc_html( $assessment['id'] ); ?> · row v<?php echo esc_html( $assessment['row_version'] ); ?></span>
						</td>
						<td><?php echo esc_html( SC_EI_Fit_Schema::label( SC_EI_Fit_Schema::recommendations(), $assessment['recommendation'] ) ); ?><br><span class="description"><?php echo esc_html( SC_EI_Fit_Schema::label( SC_EI_Fit_Schema::confidence_levels(), $assessment['confidence'] ) ); ?></span></td>
						<td><?php echo esc_html( SC_EI_Fit_Schema::label( SC_EI_Fit_Schema::service_routes(), $assessment['service_route'] ) ); ?><br><span class="description"><?php echo esc_html( SC_EI_Fit_Schema::label( SC_EI_Fit_Schema::scope_boundaries(), $assessment['scope_boundary'] ) ); ?></span></td>
						<td>
							<?php echo null === $assessment['advisory_score'] ? '—' : esc_html( number_format_i18n( (float) $assessment['advisory_score'], 2 ) . '/100' ); ?><br>
							<span class="description"><?php echo esc_html( sprintf( __( '%d material concern(s)', 'sustainable-catalyst-engagement-intake' ), absint( $assessment['material_concern_count'] ) ) ); ?></span>
						</td>
						<td><?php echo esc_html( $assessment['assessor_name'] ?: __( 'Unassigned', 'sustainable-catalyst-engagement-intake' ) ); ?><?php if ( $assessment['second_reviewer_name'] ) : ?><br><span class="description"><?php echo esc_html( sprintf( __( 'Second: %s', 'sustainable-catalyst-engagement-intake' ), $assessment['second_reviewer_name'] ) ); ?></span><?php endif; ?></td>
						<td><?php echo esc_html( get_date_from_gmt( $assessment['updated_at'], 'M j, Y g:i a' ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</section>
</div>
