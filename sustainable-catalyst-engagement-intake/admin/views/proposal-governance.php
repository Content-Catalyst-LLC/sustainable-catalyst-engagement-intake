<?php
/** Proposal governance workspace. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$labels = SC_EI_Proposal_Governance_Schema::sow_statuses();
$change_labels = SC_EI_Proposal_Governance_Schema::change_statuses();
?>
<div class="wrap sc-ei-admin-wrap">
	<h1><?php esc_html_e( 'Proposals, Statements of Work, and Engagement Approvals', 'sustainable-catalyst-engagement-intake' ); ?></h1>
	<p><?php esc_html_e( 'Govern proposal versions, sender decisions, Statements of Work, change control, and deliberate engagement conversion. This workspace does not provide legal advice or automatic contracting.', 'sustainable-catalyst-engagement-intake' ); ?></p>

	<?php if ( $message ) : ?>
		<div class="notice notice-info is-dismissible"><p><?php echo esc_html( ucwords( str_replace( '_', ' ', $message ) ) ); ?></p></div>
	<?php endif; ?>

	<div class="sc-ei-card-grid">
		<div class="sc-ei-card"><strong><?php echo esc_html( number_format_i18n( $metrics['draft_sows'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'SOWs in preparation', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div class="sc-ei-card"><strong><?php echo esc_html( number_format_i18n( $metrics['approved_sows'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Ready for sender review', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div class="sc-ei-card"><strong><?php echo esc_html( number_format_i18n( $metrics['sender_approved_sows'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Sender-approved SOWs', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div class="sc-ei-card"><strong><?php echo esc_html( number_format_i18n( $metrics['open_change_requests'] ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Open change requests', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
	</div>

	<form method="get" class="sc-ei-toolbar">
		<input type="hidden" name="page" value="sc-engagement-intake-proposal-governance">
		<label for="sc-ei-inquiry-id"><?php esc_html_e( 'Inquiry ID', 'sustainable-catalyst-engagement-intake' ); ?></label>
		<input id="sc-ei-inquiry-id" type="number" min="1" name="inquiry" value="<?php echo esc_attr( $inquiry_id ); ?>">
		<button class="button button-primary"><?php esc_html_e( 'Open workspace', 'sustainable-catalyst-engagement-intake' ); ?></button>
	</form>

	<?php if ( ! $inquiry ) : ?>
		<div class="notice notice-warning"><p><?php esc_html_e( 'Enter a valid inquiry ID to manage proposal governance.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php else : ?>
		<h2><?php echo esc_html( sprintf( __( 'Inquiry %s', 'sustainable-catalyst-engagement-intake' ), $inquiry['reference'] ) ); ?></h2>

		<section class="sc-ei-panel">
			<h2><?php esc_html_e( 'Proposals and Statements of Work', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<?php if ( ! $proposals ) : ?><p><?php esc_html_e( 'No proposal records are available for this inquiry.', 'sustainable-catalyst-engagement-intake' ); ?></p><?php endif; ?>
			<?php foreach ( $proposals as $proposal ) : $sow = SC_EI_Proposal_Governance_Repository::sow_for_proposal( absint( $proposal['id'] ) ); ?>
				<article class="sc-ei-card">
					<h3><?php echo esc_html( $proposal['proposal_number'] . ' — ' . $proposal['title'] ); ?></h3>
					<p><strong><?php esc_html_e( 'Proposal state:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo esc_html( SC_EI_Proposal_Governance_Schema::proposal_statuses()[ $proposal['status'] ] ?? $proposal['status'] ); ?> · <strong><?php esc_html_e( 'Current version:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo esc_html( (string) ( $proposal['version_number'] ?? '—' ) ); ?></p>
					<?php if ( $sow ) : ?>
						<p><strong><?php esc_html_e( 'Statement of Work:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo esc_html( $sow['sow_number'] ); ?> · <?php echo esc_html( $labels[ $sow['status'] ] ?? $sow['status'] ); ?> · v<?php echo esc_html( (string) ( $sow['version_number'] ?? '1' ) ); ?></p>
						<?php if ( current_user_can( 'sc_intake_approve_statements_of_work' ) && ! empty( $sow['pending_version_id'] ) ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="sc_ei_approve_sow"><input type="hidden" name="sow_id" value="<?php echo esc_attr( $sow['id'] ); ?>">
							<?php wp_nonce_field( 'sc_ei_approve_sow_' . $sow['id'] ); ?>
							<label><?php echo esc_html( sprintf( __( 'Type APPROVE %s', 'sustainable-catalyst-engagement-intake' ), strtoupper( $sow['sow_number'] ) ) ); ?><input class="regular-text" name="confirmation" required></label>
							<button class="button button-primary"><?php esc_html_e( 'Approve for sender review', 'sustainable-catalyst-engagement-intake' ); ?></button>
						</form>
						<?php endif; ?>
					<?php elseif ( current_user_can( 'sc_intake_manage_statements_of_work' ) && ! empty( $proposal['current_version_id'] ) ) : ?>
						<details><summary><?php esc_html_e( 'Create Statement of Work', 'sustainable-catalyst-engagement-intake' ); ?></summary>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-form-grid">
							<input type="hidden" name="action" value="sc_ei_create_sow"><input type="hidden" name="proposal_id" value="<?php echo esc_attr( $proposal['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_create_sow_' . $proposal['id'] ); ?>
							<label><?php esc_html_e( 'SOW title', 'sustainable-catalyst-engagement-intake' ); ?><input class="large-text" name="sow_title" required value="<?php echo esc_attr( $proposal['title'] ); ?>"></label>
							<label><?php esc_html_e( 'Purpose and background', 'sustainable-catalyst-engagement-intake' ); ?><textarea class="large-text" name="purpose_background" rows="3"></textarea></label>
							<label><?php esc_html_e( 'Scope — one item per line', 'sustainable-catalyst-engagement-intake' ); ?><textarea class="large-text" name="sow_scope" rows="5" required><?php echo esc_textarea( implode( "\n", json_decode( (string) $proposal['scope_json'], true ) ?: array() ) ); ?></textarea></label>
							<label><?php esc_html_e( 'Deliverables — one item per line', 'sustainable-catalyst-engagement-intake' ); ?><textarea class="large-text" name="sow_deliverables" rows="5" required><?php echo esc_textarea( implode( "\n", json_decode( (string) $proposal['deliverables_json'], true ) ?: array() ) ); ?></textarea></label>
							<label><?php esc_html_e( 'Milestones', 'sustainable-catalyst-engagement-intake' ); ?><textarea class="large-text" name="sow_milestones" rows="4"></textarea></label>
							<label><?php esc_html_e( 'Responsibilities', 'sustainable-catalyst-engagement-intake' ); ?><textarea class="large-text" name="sow_responsibilities" rows="4"></textarea></label>
							<label><?php esc_html_e( 'Dependencies', 'sustainable-catalyst-engagement-intake' ); ?><textarea class="large-text" name="sow_dependencies" rows="4"></textarea></label>
							<label><?php esc_html_e( 'Acceptance criteria', 'sustainable-catalyst-engagement-intake' ); ?><textarea class="large-text" name="acceptance_criteria" rows="4" required></textarea></label>
							<label><?php esc_html_e( 'Change control', 'sustainable-catalyst-engagement-intake' ); ?><textarea class="large-text" name="change_control" rows="3"></textarea></label>
							<label><?php esc_html_e( 'Communication expectations', 'sustainable-catalyst-engagement-intake' ); ?><textarea class="large-text" name="communication_expectations" rows="3"></textarea></label>
							<label><?php esc_html_e( 'Data and document handling', 'sustainable-catalyst-engagement-intake' ); ?><textarea class="large-text" name="data_handling" rows="3"></textarea></label>
							<label><?php esc_html_e( 'Intellectual-property terms', 'sustainable-catalyst-engagement-intake' ); ?><textarea class="large-text" name="ip_terms" rows="3"></textarea></label>
							<label><?php esc_html_e( 'Open-source boundaries', 'sustainable-catalyst-engagement-intake' ); ?><textarea class="large-text" name="open_source_boundaries" rows="3"></textarea></label>
							<label><?php esc_html_e( 'Fees and payment schedule', 'sustainable-catalyst-engagement-intake' ); ?><textarea class="large-text" name="fees_payment" rows="3"></textarea></label>
							<label><?php esc_html_e( 'Start date', 'sustainable-catalyst-engagement-intake' ); ?><input type="date" name="start_date"></label>
							<label><?php esc_html_e( 'Target end date', 'sustainable-catalyst-engagement-intake' ); ?><input type="date" name="target_end_date"></label>
							<label><?php esc_html_e( 'Termination conditions', 'sustainable-catalyst-engagement-intake' ); ?><textarea class="large-text" name="termination_conditions" rows="3"></textarea></label>
							<label><?php esc_html_e( 'Version note', 'sustainable-catalyst-engagement-intake' ); ?><textarea class="large-text" name="version_note" rows="2"></textarea></label>
							<p><button class="button button-primary"><?php esc_html_e( 'Create SOW draft', 'sustainable-catalyst-engagement-intake' ); ?></button></p>
						</form></details>
					<?php endif; ?>
					<?php if ( current_user_can( 'sc_intake_create_engagement_handoffs' ) && 'contracted' === (string) $proposal['status'] && $sow && 'sender_approved' === (string) $sow['status'] ) : ?>
						<details><summary><?php esc_html_e( 'Convert to governed engagement', 'sustainable-catalyst-engagement-intake' ); ?></summary>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-form-grid">
							<input type="hidden" name="action" value="sc_ei_convert_proposal_engagement"><input type="hidden" name="proposal_id" value="<?php echo esc_attr( $proposal['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_convert_proposal_engagement_' . $proposal['id'] ); ?>
							<label><?php esc_html_e( 'Engagement title', 'sustainable-catalyst-engagement-intake' ); ?><input class="large-text" name="engagement_title" required value="<?php echo esc_attr( $proposal['title'] ); ?>"></label>
							<label><?php esc_html_e( 'Owner user ID', 'sustainable-catalyst-engagement-intake' ); ?><input type="number" min="1" name="owner_user_id" required value="<?php echo esc_attr( get_current_user_id() ); ?>"></label>
							<label><?php esc_html_e( 'Proposed start date', 'sustainable-catalyst-engagement-intake' ); ?><input type="date" name="proposed_start_date"></label>
							<label><?php esc_html_e( 'Target end date', 'sustainable-catalyst-engagement-intake' ); ?><input type="date" name="target_end_date"></label>
							<label><?php esc_html_e( 'Sender-facing onboarding summary', 'sustainable-catalyst-engagement-intake' ); ?><textarea class="large-text" name="sender_summary" rows="3"></textarea></label>
							<label><?php esc_html_e( 'Internal onboarding notes', 'sustainable-catalyst-engagement-intake' ); ?><textarea class="large-text" name="internal_notes" rows="3"></textarea></label>
							<label><?php esc_html_e( 'External project reference', 'sustainable-catalyst-engagement-intake' ); ?><input class="regular-text" name="external_project_reference"></label>
							<label><?php echo esc_html( sprintf( __( 'Type CONVERT %s', 'sustainable-catalyst-engagement-intake' ), strtoupper( $proposal['proposal_number'] ) ) ); ?><input class="regular-text" name="confirmation" required></label>
							<p><button class="button button-primary"><?php esc_html_e( 'Create engagement handoff', 'sustainable-catalyst-engagement-intake' ); ?></button></p>
						</form></details>
					<?php endif; ?>
				</article>
			<?php endforeach; ?>
		</section>

		<section class="sc-ei-panel">
			<h2><?php esc_html_e( 'Change Requests', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<?php foreach ( $changes as $change ) : ?>
				<article class="sc-ei-card"><h3><?php echo esc_html( $change['change_number'] ); ?></h3><p><?php echo esc_html( $change_labels[ $change['status'] ] ?? $change['status'] ); ?> — <?php echo esc_html( $change['request_summary'] ); ?></p>
				<?php if ( current_user_can( 'sc_intake_approve_change_requests' ) && in_array( $change['status'], array( 'requested', 'under_review', 'approved' ), true ) ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="sc_ei_transition_change_request"><input type="hidden" name="change_request_id" value="<?php echo esc_attr( $change['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_transition_change_request_' . $change['id'] ); ?>
					<select name="status"><?php foreach ( $change_labels as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
					<input class="regular-text" name="note" placeholder="Decision or application note"><input class="regular-text" name="confirmation" placeholder="STATUS <?php echo esc_attr( $change['change_number'] ); ?>" required>
					<button class="button"><?php esc_html_e( 'Record transition', 'sustainable-catalyst-engagement-intake' ); ?></button>
				</form><?php endif; ?></article>
			<?php endforeach; ?>
			<?php if ( current_user_can( 'sc_intake_manage_change_requests' ) ) : ?>
			<details><summary><?php esc_html_e( 'Create change request', 'sustainable-catalyst-engagement-intake' ); ?></summary>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-form-grid">
				<input type="hidden" name="action" value="sc_ei_create_change_request"><input type="hidden" name="inquiry_id" value="<?php echo esc_attr( $inquiry_id ); ?>"><?php wp_nonce_field( 'sc_ei_create_change_request_' . $inquiry_id ); ?>
				<label><?php esc_html_e( 'Proposal', 'sustainable-catalyst-engagement-intake' ); ?><select name="proposal_id"><option value="0">—</option><?php foreach ( $proposals as $proposal ) : ?><option value="<?php echo esc_attr( $proposal['id'] ); ?>"><?php echo esc_html( $proposal['proposal_number'] ); ?></option><?php endforeach; ?></select></label>
				<label><?php esc_html_e( 'Engagement', 'sustainable-catalyst-engagement-intake' ); ?><select name="engagement_id"><option value="0">—</option><?php foreach ( $engagements as $engagement ) : ?><option value="<?php echo esc_attr( $engagement['id'] ); ?>"><?php echo esc_html( $engagement['engagement_number'] ); ?></option><?php endforeach; ?></select></label>
				<label><?php esc_html_e( 'Requested change', 'sustainable-catalyst-engagement-intake' ); ?><textarea name="request_summary" required></textarea></label>
				<label><?php esc_html_e( 'Reason', 'sustainable-catalyst-engagement-intake' ); ?><textarea name="reason" required></textarea></label>
				<label><?php esc_html_e( 'Scope impact', 'sustainable-catalyst-engagement-intake' ); ?><textarea name="scope_impact"></textarea></label>
				<label><?php esc_html_e( 'Timeline impact', 'sustainable-catalyst-engagement-intake' ); ?><textarea name="timeline_impact"></textarea></label>
				<label><?php esc_html_e( 'Fee impact', 'sustainable-catalyst-engagement-intake' ); ?><input name="fee_impact" inputmode="decimal"></label>
				<p><button class="button button-primary"><?php esc_html_e( 'Create change request', 'sustainable-catalyst-engagement-intake' ); ?></button></p>
			</form></details><?php endif; ?>
		</section>

		<section class="sc-ei-panel">
			<h2><?php esc_html_e( 'Immutable Approval History', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Time', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Action', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Version', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Actor', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Integrity', 'sustainable-catalyst-engagement-intake' ); ?></th></tr></thead><tbody>
			<?php foreach ( $approvals as $approval ) : ?><tr><td><?php echo esc_html( $approval['created_at'] ); ?></td><td><?php echo esc_html( $approval['action'] ); ?></td><td><?php echo esc_html( $approval['proposal_version_id'] ); ?></td><td><?php echo esc_html( $approval['actor_type'] ); ?></td><td><code><?php echo esc_html( substr( $approval['immutable_hash'], 0, 12 ) ); ?>…</code></td></tr><?php endforeach; ?>
			<?php if ( ! $approvals ) : ?><tr><td colspan="5"><?php esc_html_e( 'No immutable approval records have been created.', 'sustainable-catalyst-engagement-intake' ); ?></td></tr><?php endif; ?>
			</tbody></table>
		</section>
	<?php endif; ?>
</div>
