<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$messages = array(
	'workflow_core_case_synchronized'       => __( 'The canonical case projection was synchronized.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_core_all_synchronized'        => __( 'All eligible Workflow Core cases were synchronized.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_core_handoff_prepared'        => __( 'A signed cross-plugin handoff was prepared and queued.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_core_outbox_dispatched'       => __( 'Pending Workflow Core outbox events were dispatched to registered internal adapters.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_core_handoff_acknowledged'    => __( 'The Workflow Core handoff acknowledgment was recorded.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_core_handoff_canceled'        => __( 'The Workflow Core handoff was canceled.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_core_consistency_resolved'    => __( 'The consistency warning was reviewed and marked resolved.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_core_settings_saved'          => __( 'Workflow Core settings were saved.', 'sustainable-catalyst-engagement-intake' ),
);
$errors = array(
	'workflow_core_case_not_found'                 => __( 'The Workflow Core case was not found.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_core_sync_confirmation_failed'       => __( 'Type the required SYNC CASE confirmation exactly.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_core_sync_all_confirmation_failed'   => __( 'Type SYNC WORKFLOW CORE exactly.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_core_handoff_confirmation_failed'    => __( 'Type the required HANDOFF confirmation exactly.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_core_dispatch_confirmation_failed'   => __( 'Type DISPATCH OUTBOX exactly.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_core_ack_confirmation_failed'        => __( 'Type the required ACK HANDOFF confirmation exactly.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_core_cancel_confirmation_failed'     => __( 'Type the required CANCEL HANDOFF confirmation exactly.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_core_resolve_confirmation_failed'    => __( 'Type the required RESOLVE CASE confirmation exactly.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_core_settings_confirmation_failed'   => __( 'Type SAVE WORKFLOW CORE SETTINGS exactly.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_core_case_blocked'                   => __( 'Resolve the canonical consistency blockers before preparing a handoff.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_core_private_export_forbidden'       => __( 'Private personal-data handoffs require the private export capability.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_core_stage_changed'                  => __( 'The authoritative case stage changed before the command executed. Synchronize and review again.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_core_handoff_integrity_failed'       => __( 'The signed handoff failed integrity verification.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_core_outbox_partial'                 => __( 'Some outbox events failed or were rescheduled. Review the event history.', 'sustainable-catalyst-engagement-intake' ),
	'workflow_core_sync_partial'                   => __( 'Some case projections could not be synchronized. Review Reliability and Diagnostics.', 'sustainable-catalyst-engagement-intake' ),
);
$is_error = $message && ! isset( $messages[ $message ] );
?>
<div class="wrap sc-ei-admin sc-ei-workflow-core-admin" id="sc-ei-primary-content">
	<header class="sc-ei-admin__header">
		<div>
			<p class="sc-ei-admin__eyebrow"><?php esc_html_e( 'Canonical Case and Cross-Plugin Integration', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<h1><?php esc_html_e( 'Workflow Core', 'sustainable-catalyst-engagement-intake' ); ?></h1>
			<p><?php esc_html_e( 'Reconcile authoritative intake records into one canonical case projection and move signed, versioned handoffs through registered internal WordPress adapters.', 'sustainable-catalyst-engagement-intake' ); ?></p>
		</div>
		<div class="sc-ei-admin__version">v1.0.0</div>
	</header>

	<?php if ( $message ) : ?>
		<div class="notice <?php echo $is_error ? 'notice-error' : 'notice-success'; ?> is-dismissible"><p><?php echo esc_html( $messages[ $message ] ?? $errors[ $message ] ?? ucwords( str_replace( '_', ' ', $message ) ) ); ?></p></div>
	<?php endif; ?>

	<div class="sc-ei-portal-admin-boundary">
		<strong><?php esc_html_e( 'Fixed integration boundary', 'sustainable-catalyst-engagement-intake' ); ?></strong>
		<span><?php esc_html_e( 'Workflow Core derives and transports state. It cannot accept or reject inquiries, decide fit, publish proposals, record contracts, activate engagements, create external projects, call arbitrary webhooks, or execute unverified inbound commands.', 'sustainable-catalyst-engagement-intake' ); ?></span>
	</div>

	<div class="sc-ei-review-metrics">
		<a><strong><?php echo esc_html( number_format_i18n( $metrics['cases'] ) ); ?></strong><span><?php esc_html_e( 'canonical cases', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a class="<?php echo $metrics['blocked_cases'] ? 'sc-ei-review-metric--danger' : ''; ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['blocked_cases'] ) ); ?></strong><span><?php esc_html_e( 'blocked cases', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a><strong><?php echo esc_html( number_format_i18n( $metrics['warning_cases'] ) ); ?></strong><span><?php esc_html_e( 'warning cases', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a><strong><?php echo esc_html( number_format_i18n( $metrics['pending_commands'] ) ); ?></strong><span><?php esc_html_e( 'open commands', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a><strong><?php echo esc_html( number_format_i18n( $metrics['prepared_handoffs'] ) ); ?></strong><span><?php esc_html_e( 'prepared handoffs', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a class="<?php echo $metrics['failed_outbox'] ? 'sc-ei-review-metric--danger' : ''; ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['pending_outbox'] ) ); ?> / <?php echo esc_html( number_format_i18n( $metrics['failed_outbox'] ) ); ?></strong><span><?php esc_html_e( 'pending / failed outbox', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
	</div>

	<?php if ( ! $selected ) : ?>
		<div class="sc-ei-workflow-core-layout">
			<main>
				<section class="sc-ei-admin__card sc-ei-admin__card--wide">
					<div class="sc-ei-card-heading-row">
						<div><h2><?php esc_html_e( 'Canonical Cases', 'sustainable-catalyst-engagement-intake' ); ?></h2><p><?php esc_html_e( 'The case projection is a synchronized view. Authoritative records remain in their existing domain tables.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
						<?php if ( current_user_can( 'sc_intake_manage_workflow_core' ) ) : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-confirm-form">
								<input type="hidden" name="action" value="sc_ei_workflow_core_sync_all">
								<?php wp_nonce_field( 'sc_ei_workflow_core_sync_all' ); ?>
								<input type="text" name="workflow_core_confirmation" required autocomplete="off" placeholder="SYNC WORKFLOW CORE">
								<button class="button"><?php esc_html_e( 'Synchronize All', 'sustainable-catalyst-engagement-intake' ); ?></button>
							</form>
						<?php endif; ?>
					</div>
					<form method="get" class="sc-ei-operation-filter-form">
						<input type="hidden" name="page" value="sc-engagement-intake-workflow-core">
						<select name="core_stage"><option value=""><?php esc_html_e( 'All stages', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( SC_EI_Workflow_Core_Schema::stages() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $stage, $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
						<select name="core_consistency"><option value=""><?php esc_html_e( 'All consistency states', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( SC_EI_Workflow_Core_Schema::consistency_states() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $consistency, $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
						<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Reference, sender, organization', 'sustainable-catalyst-engagement-intake' ); ?>">
						<button class="button"><?php esc_html_e( 'Filter', 'sustainable-catalyst-engagement-intake' ); ?></button>
					</form>
					<div class="sc-ei-table-scroll" role="region" tabindex="0" aria-label="<?php esc_attr_e( 'Workflow Core cases', 'sustainable-catalyst-engagement-intake' ); ?>">
						<table class="widefat striped">
							<thead><tr><th scope="col"><?php esc_html_e( 'Case', 'sustainable-catalyst-engagement-intake' ); ?></th><th scope="col"><?php esc_html_e( 'Stage and state', 'sustainable-catalyst-engagement-intake' ); ?></th><th scope="col"><?php esc_html_e( 'Consistency', 'sustainable-catalyst-engagement-intake' ); ?></th><th scope="col"><?php esc_html_e( 'Owner', 'sustainable-catalyst-engagement-intake' ); ?></th><th scope="col"><?php esc_html_e( 'Projection', 'sustainable-catalyst-engagement-intake' ); ?></th></tr></thead>
							<tbody>
								<?php if ( ! $cases ) : ?><tr><td colspan="5"><?php esc_html_e( 'No canonical cases match this filter. Run synchronization to create or refresh projections.', 'sustainable-catalyst-engagement-intake' ); ?></td></tr><?php endif; ?>
								<?php foreach ( $cases as $item ) : ?>
									<tr>
										<td><a href="<?php echo esc_url( SC_EI_Workflow_Core_Admin::url( absint( $item['id'] ) ) ); ?>"><strong><?php echo esc_html( $item['reference'] ); ?></strong></a><br><span class="description"><?php echo esc_html( $item['organization'] ?: $item['contact_name'] ); ?></span></td>
										<td><strong><?php echo esc_html( SC_EI_Workflow_Core_Schema::label( SC_EI_Workflow_Core_Schema::stages(), $item['current_stage'] ) ); ?></strong><br><span><?php echo esc_html( SC_EI_Workflow_Core_Schema::label( SC_EI_Workflow_Core_Schema::states(), $item['current_state'] ) ); ?></span></td>
										<td><span class="sc-ei-fit-state sc-ei-fit-state--<?php echo esc_attr( $item['consistency_status'] ); ?>"><?php echo esc_html( SC_EI_Workflow_Core_Schema::label( SC_EI_Workflow_Core_Schema::consistency_states(), $item['consistency_status'] ) ); ?></span><br><span class="description"><?php echo esc_html( sprintf( __( '%d blocker(s)', 'sustainable-catalyst-engagement-intake' ), absint( $item['blocker_count'] ) ) ); ?></span></td>
										<td><?php echo esc_html( $item['owner_name'] ?: __( 'Unassigned', 'sustainable-catalyst-engagement-intake' ) ); ?><br><span class="description"><?php echo esc_html( ucfirst( $item['priority'] ) ); ?></span></td>
										<td>v<?php echo esc_html( absint( $item['projection_version'] ) ); ?><br><code><?php echo esc_html( substr( $item['projection_hash'], 0, 12 ) ); ?></code><br><span class="description"><?php echo esc_html( $item['last_synced_at'] ); ?> UTC</span></td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</section>
			</main>
			<aside>
				<section class="sc-ei-admin__card">
					<h2><?php esc_html_e( 'Core Runtime', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<dl class="sc-ei-admin__details">
						<dt><?php esc_html_e( 'Core', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo ! empty( $settings['workflow_core_enabled'] ) ? esc_html__( 'enabled', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'disabled', 'sustainable-catalyst-engagement-intake' ); ?></dd>
						<dt><?php esc_html_e( 'Audit synchronization', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo ! empty( $settings['workflow_core_auto_sync_on_audit'] ) ? esc_html__( 'enabled', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'disabled', 'sustainable-catalyst-engagement-intake' ); ?></dd>
						<dt><?php esc_html_e( 'Next sync', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php $next = wp_next_scheduled( SC_EI_Workflow_Core_Repository::SYNC_HOOK ); echo esc_html( $next ? gmdate( 'Y-m-d H:i:s', $next ) . ' UTC' : __( 'not scheduled', 'sustainable-catalyst-engagement-intake' ) ); ?></dd>
						<dt><?php esc_html_e( 'Next outbox', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php $next = wp_next_scheduled( SC_EI_Workflow_Core_Repository::OUTBOX_HOOK ); echo esc_html( $next ? gmdate( 'Y-m-d H:i:s', $next ) . ' UTC' : __( 'not scheduled', 'sustainable-catalyst-engagement-intake' ) ); ?></dd>
						<dt><?php esc_html_e( 'Last sync', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $last_sync['completed_at'] ?: __( 'not run', 'sustainable-catalyst-engagement-intake' ) ); ?></dd>
						<dt><?php esc_html_e( 'Last outbox', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $last_outbox['completed_at'] ?: __( 'not run', 'sustainable-catalyst-engagement-intake' ) ); ?></dd>
					</dl>
				</section>

				<section class="sc-ei-admin__card">
					<h2><?php esc_html_e( 'Internal Adapters', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<ul class="sc-ei-checks">
						<?php foreach ( $targets as $key => $target ) : ?>
							<li><span class="<?php echo $target['registered'] ? 'sc-ei-check--ok' : ''; ?>">●</span> <strong><?php echo esc_html( $target['label'] ); ?></strong> — <?php echo $target['registered'] ? esc_html__( 'registered', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'not installed or not registered', 'sustainable-catalyst-engagement-intake' ); ?></li>
						<?php endforeach; ?>
					</ul>
					<p><?php esc_html_e( 'No arbitrary URL or webhook field is exposed. Adapters register inside WordPress and receive verified internal events.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				</section>

				<?php if ( current_user_can( 'sc_intake_manage_workflow_core' ) ) : ?>
					<section class="sc-ei-admin__card">
						<h2><?php esc_html_e( 'Core Settings', 'sustainable-catalyst-engagement-intake' ); ?></h2>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-settings-form">
							<input type="hidden" name="action" value="sc_ei_workflow_core_save_settings">
							<?php wp_nonce_field( 'sc_ei_workflow_core_save_settings' ); ?>
							<label class="sc-ei-check"><input type="checkbox" name="workflow_core_settings[workflow_core_enabled]" value="1" <?php checked( ! empty( $settings['workflow_core_enabled'] ) ); ?>><span><?php esc_html_e( 'Enable Workflow Core', 'sustainable-catalyst-engagement-intake' ); ?></span></label>
							<label class="sc-ei-check"><input type="checkbox" name="workflow_core_settings[workflow_core_auto_sync_on_audit]" value="1" <?php checked( ! empty( $settings['workflow_core_auto_sync_on_audit'] ) ); ?>><span><?php esc_html_e( 'Queue synchronization after authoritative audit events', 'sustainable-catalyst-engagement-intake' ); ?></span></label>
							<label class="sc-ei-check"><input type="checkbox" name="workflow_core_settings[workflow_core_outbox_enabled]" value="1" <?php checked( ! empty( $settings['workflow_core_outbox_enabled'] ) ); ?>><span><?php esc_html_e( 'Enable internal adapter outbox', 'sustainable-catalyst-engagement-intake' ); ?></span></label>
							<label><span><?php esc_html_e( 'Stale after hours', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="1" max="720" name="workflow_core_settings[workflow_core_stale_after_hours]" value="<?php echo esc_attr( $settings['workflow_core_stale_after_hours'] ); ?>"></label>
							<label><span><?php esc_html_e( 'Outbox batch', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="1" max="250" name="workflow_core_settings[workflow_core_outbox_batch_limit]" value="<?php echo esc_attr( $settings['workflow_core_outbox_batch_limit'] ); ?>"></label>
							<label><span><?php esc_html_e( 'Maximum attempts', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="1" max="20" name="workflow_core_settings[workflow_core_outbox_max_attempts]" value="<?php echo esc_attr( $settings['workflow_core_outbox_max_attempts'] ); ?>"></label>
							<label><span><?php esc_html_e( 'Handoff expiry days', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="1" max="365" name="workflow_core_settings[workflow_core_handoff_expiry_days]" value="<?php echo esc_attr( $settings['workflow_core_handoff_expiry_days'] ); ?>"></label>
							<label><span><?php esc_html_e( 'Default classification', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="workflow_core_settings[workflow_core_default_classification]"><?php foreach ( SC_EI_Workflow_Core_Schema::data_classifications() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $settings['workflow_core_default_classification'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
							<label><span><?php esc_html_e( 'Typed confirmation', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="workflow_core_confirmation" required autocomplete="off" placeholder="SAVE WORKFLOW CORE SETTINGS"></label>
							<button class="button"><?php esc_html_e( 'Save Workflow Core Settings', 'sustainable-catalyst-engagement-intake' ); ?></button>
						</form>
					</section>
				<?php endif; ?>
			</aside>
		</div>
	<?php else : ?>
		<div class="sc-ei-workflow-core-detail-header">
			<div>
				<a href="<?php echo esc_url( SC_EI_Workflow_Core_Admin::url() ); ?>">← <?php esc_html_e( 'All canonical cases', 'sustainable-catalyst-engagement-intake' ); ?></a>
				<h2><?php echo esc_html( $selected['reference'] . ' · ' . ( $selected['organization'] ?: $selected['contact_name'] ) ); ?></h2>
				<p><?php echo esc_html( SC_EI_Workflow_Core_Schema::label( SC_EI_Workflow_Core_Schema::stages(), $selected['current_stage'] ) . ' · ' . SC_EI_Workflow_Core_Schema::label( SC_EI_Workflow_Core_Schema::states(), $selected['current_state'] ) ); ?></p>
			</div>
			<span class="sc-ei-fit-state sc-ei-fit-state--<?php echo esc_attr( $selected['consistency_status'] ); ?>"><?php echo esc_html( SC_EI_Workflow_Core_Schema::label( SC_EI_Workflow_Core_Schema::consistency_states(), $selected['consistency_status'] ) ); ?></span>
		</div>

		<div class="sc-ei-workflow-core-layout">
			<main>
				<section class="sc-ei-admin__card sc-ei-admin__card--wide">
					<h2><?php esc_html_e( 'Canonical Projection', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<dl class="sc-ei-admin__details">
						<dt><?php esc_html_e( 'Case public ID', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><code><?php echo esc_html( $selected['public_id'] ); ?></code></dd>
						<dt><?php esc_html_e( 'Authoritative inquiry', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-inquiries&action=view&inquiry=' . absint( $selected['inquiry_id'] ) ) ); ?>"><?php echo esc_html( $selected['reference'] ); ?></a></dd>
						<dt><?php esc_html_e( 'Stage', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Workflow_Core_Schema::label( SC_EI_Workflow_Core_Schema::stages(), $selected['current_stage'] ) ); ?></dd>
						<dt><?php esc_html_e( 'State', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Workflow_Core_Schema::label( SC_EI_Workflow_Core_Schema::states(), $selected['current_state'] ) ); ?></dd>
						<dt><?php esc_html_e( 'Terminal state', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $selected['terminal_state'] ?: '—' ); ?></dd>
						<dt><?php esc_html_e( 'Projection version', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( absint( $selected['projection_version'] ) ); ?></dd>
						<dt><?php esc_html_e( 'Projection hash', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><code><?php echo esc_html( $selected['projection_hash'] ); ?></code></dd>
						<dt><?php esc_html_e( 'Last synchronized', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $selected['last_synced_at'] . ' UTC' ); ?></dd>
						<dt><?php esc_html_e( 'Stale after', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $selected['stale_after'] . ' UTC' ); ?></dd>
					</dl>
					<?php if ( current_user_can( 'sc_intake_export_workflow_core' ) ) : ?><p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sc_ei_workflow_core_export_case&case=' . absint( $selected['id'] ) ), 'sc_ei_workflow_core_export_case_' . absint( $selected['id'] ) ) ); ?>"><?php esc_html_e( 'Export Redacted Core Record', 'sustainable-catalyst-engagement-intake' ); ?></a></p><?php endif; ?>
				</section>

				<section class="sc-ei-admin__card sc-ei-admin__card--wide">
					<h2><?php esc_html_e( 'Consistency Review', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<div class="sc-ei-core-consistency-grid">
						<div><h3><?php esc_html_e( 'Blockers', 'sustainable-catalyst-engagement-intake' ); ?></h3><?php if ( empty( $notes['blockers'] ) ) : ?><p><?php esc_html_e( 'No canonical blockers detected.', 'sustainable-catalyst-engagement-intake' ); ?></p><?php else : ?><ul><?php foreach ( $notes['blockers'] as $blocker ) : ?><li><?php echo esc_html( ucwords( str_replace( '_', ' ', $blocker ) ) ); ?></li><?php endforeach; ?></ul><?php endif; ?></div>
						<div><h3><?php esc_html_e( 'Warnings', 'sustainable-catalyst-engagement-intake' ); ?></h3><?php if ( empty( $notes['warnings'] ) ) : ?><p><?php esc_html_e( 'No canonical warnings detected.', 'sustainable-catalyst-engagement-intake' ); ?></p><?php else : ?><ul><?php foreach ( $notes['warnings'] as $warning ) : ?><li><?php echo esc_html( ucwords( str_replace( '_', ' ', $warning ) ) ); ?></li><?php endforeach; ?></ul><?php endif; ?></div>
					</div>
					<?php if ( current_user_can( 'sc_intake_manage_workflow_core' ) ) : ?>
						<div class="sc-ei-core-action-grid">
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-confirm-form">
								<input type="hidden" name="action" value="sc_ei_workflow_core_sync_case"><input type="hidden" name="case_id" value="<?php echo esc_attr( $selected['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_workflow_core_sync_case_' . absint( $selected['id'] ) ); ?>
								<input type="text" name="workflow_core_confirmation" required autocomplete="off" placeholder="<?php echo esc_attr( 'SYNC CASE ' . strtoupper( $selected['reference'] ) ); ?>">
								<button class="button"><?php esc_html_e( 'Synchronize from Authoritative Records', 'sustainable-catalyst-engagement-intake' ); ?></button>
							</form>
							<?php if ( in_array( $selected['consistency_status'], array( 'warning', 'blocked', 'stale' ), true ) ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-confirm-form">
									<input type="hidden" name="action" value="sc_ei_workflow_core_resolve_consistency"><input type="hidden" name="case_id" value="<?php echo esc_attr( $selected['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_workflow_core_resolve_' . absint( $selected['id'] ) ); ?>
									<textarea name="resolution_note" rows="3" required placeholder="<?php esc_attr_e( 'Describe the human review. This does not alter authoritative records.', 'sustainable-catalyst-engagement-intake' ); ?>"></textarea>
									<input type="text" name="workflow_core_confirmation" required autocomplete="off" placeholder="<?php echo esc_attr( 'RESOLVE CASE ' . strtoupper( $selected['reference'] ) ); ?>">
									<button class="button"><?php esc_html_e( 'Record Consistency Review', 'sustainable-catalyst-engagement-intake' ); ?></button>
								</form>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</section>

				<section class="sc-ei-admin__card sc-ei-admin__card--wide">
					<h2><?php esc_html_e( 'Signed Cross-Plugin Handoffs', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<?php if ( current_user_can( 'sc_intake_prepare_workflow_handoffs' ) ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-admin-form sc-ei-core-handoff-form">
							<input type="hidden" name="action" value="sc_ei_workflow_core_prepare_handoff"><input type="hidden" name="case_id" value="<?php echo esc_attr( $selected['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_workflow_core_prepare_handoff_' . absint( $selected['id'] ) ); ?>
							<label><span><?php esc_html_e( 'Target', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="handoff_target" required><?php foreach ( $targets as $key => $target ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $target['label'] . ( $target['registered'] ? ' · registered' : ' · not registered' ) ); ?></option><?php endforeach; ?></select></label>
							<label><span><?php esc_html_e( 'Data classification', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="data_classification"><?php foreach ( SC_EI_Workflow_Core_Schema::data_classifications() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
							<?php if ( current_user_can( 'sc_intake_export_workflow_core_private' ) ) : ?><label class="sc-ei-check"><input type="checkbox" name="include_personal_data" value="1"><span><?php esc_html_e( 'Include private personal data. Use only for an authorized internal target.', 'sustainable-catalyst-engagement-intake' ); ?></span></label><?php endif; ?>
							<label class="sc-ei-portal-admin-form__wide"><span><?php esc_html_e( 'Handoff purpose', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="handoff_reason" rows="3" required></textarea></label>
							<label class="sc-ei-portal-admin-form__wide"><span><?php esc_html_e( 'Typed confirmation', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="workflow_core_confirmation" required autocomplete="off" placeholder="<?php echo esc_attr( 'HANDOFF ' . strtoupper( $selected['reference'] ) . ' TARGET' ); ?>"></label>
							<p class="sc-ei-portal-admin-form__wide description"><?php esc_html_e( 'Replace TARGET with the selected target key, such as WORKBENCH or DECISION_STUDIO.', 'sustainable-catalyst-engagement-intake' ); ?></p>
							<p class="sc-ei-portal-admin-form__wide"><button class="button button-primary" <?php disabled( 'blocked' === $selected['consistency_status'] ); ?>><?php esc_html_e( 'Prepare Signed Handoff', 'sustainable-catalyst-engagement-intake' ); ?></button></p>
						</form>
					<?php endif; ?>

					<div class="sc-ei-table-scroll" role="region" tabindex="0" aria-label="<?php esc_attr_e( 'Workflow Core handoffs', 'sustainable-catalyst-engagement-intake' ); ?>">
						<table class="widefat striped">
							<thead><tr><th scope="col"><?php esc_html_e( 'Handoff', 'sustainable-catalyst-engagement-intake' ); ?></th><th scope="col"><?php esc_html_e( 'Target', 'sustainable-catalyst-engagement-intake' ); ?></th><th scope="col"><?php esc_html_e( 'State', 'sustainable-catalyst-engagement-intake' ); ?></th><th scope="col"><?php esc_html_e( 'Integrity', 'sustainable-catalyst-engagement-intake' ); ?></th><th scope="col"><?php esc_html_e( 'Actions', 'sustainable-catalyst-engagement-intake' ); ?></th></tr></thead>
							<tbody>
								<?php if ( ! $handoffs ) : ?><tr><td colspan="5"><?php esc_html_e( 'No handoffs have been prepared for this case.', 'sustainable-catalyst-engagement-intake' ); ?></td></tr><?php endif; ?>
								<?php foreach ( $handoffs as $handoff ) : ?>
									<?php $verified = SC_EI_Workflow_Core_Contract::verify( (string) $handoff['payload_json'], (string) $handoff['target'], (string) $handoff['content_hash'], (string) $handoff['signature'] ); ?>
									<tr>
										<td><strong>#<?php echo esc_html( $handoff['id'] ); ?></strong><br><span class="description"><?php echo esc_html( $handoff['public_id'] ); ?></span><br><span class="description"><?php echo esc_html( $handoff['prepared_at'] ); ?> UTC</span></td>
										<td><?php echo esc_html( SC_EI_Workflow_Core_Schema::label( SC_EI_Workflow_Core_Schema::handoff_targets(), $handoff['target'] ) ); ?><br><span class="description"><?php echo esc_html( SC_EI_Workflow_Core_Schema::label( SC_EI_Workflow_Core_Schema::data_classifications(), $handoff['data_classification'] ) ); ?></span></td>
										<td><span class="sc-ei-fit-state sc-ei-fit-state--<?php echo esc_attr( $handoff['status'] ); ?>"><?php echo esc_html( SC_EI_Workflow_Core_Schema::label( SC_EI_Workflow_Core_Schema::handoff_statuses(), $handoff['status'] ) ); ?></span><br><span class="description"><?php echo esc_html( sprintf( __( 'Expires %s UTC', 'sustainable-catalyst-engagement-intake' ), $handoff['expires_at'] ) ); ?></span></td>
										<td><?php echo $verified ? esc_html__( 'Verified', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'Failed', 'sustainable-catalyst-engagement-intake' ); ?><br><code><?php echo esc_html( substr( $handoff['content_hash'], 0, 16 ) ); ?></code></td>
										<td>
											<?php if ( current_user_can( 'sc_intake_export_workflow_core' ) && ( 'internal_private' !== $handoff['data_classification'] || current_user_can( 'sc_intake_export_workflow_core_private' ) ) ) : ?><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sc_ei_workflow_core_export_handoff&handoff=' . absint( $handoff['id'] ) ), 'sc_ei_workflow_core_export_handoff_' . absint( $handoff['id'] ) ) ); ?>"><?php esc_html_e( 'Export', 'sustainable-catalyst-engagement-intake' ); ?></a><?php endif; ?>
											<?php if ( current_user_can( 'sc_intake_acknowledge_workflow_handoffs' ) && in_array( $handoff['status'], array( 'prepared', 'dispatched' ), true ) ) : ?>
												<details><summary><?php esc_html_e( 'Acknowledge', 'sustainable-catalyst-engagement-intake' ); ?></summary><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-confirm-form"><input type="hidden" name="action" value="sc_ei_workflow_core_acknowledge"><input type="hidden" name="case_id" value="<?php echo esc_attr( $selected['id'] ); ?>"><input type="hidden" name="handoff_id" value="<?php echo esc_attr( $handoff['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_workflow_core_acknowledge_' . absint( $handoff['id'] ) ); ?><input type="text" name="handoff_receipt" required placeholder="<?php esc_attr_e( 'Receipt or target reference', 'sustainable-catalyst-engagement-intake' ); ?>"><input type="text" name="workflow_core_confirmation" required placeholder="<?php echo esc_attr( 'ACK HANDOFF ' . absint( $handoff['id'] ) ); ?>"><button class="button"><?php esc_html_e( 'Record Acknowledgment', 'sustainable-catalyst-engagement-intake' ); ?></button></form></details>
											<?php endif; ?>
											<?php if ( current_user_can( 'sc_intake_manage_workflow_core' ) && ! in_array( $handoff['status'], array( 'acknowledged', 'canceled', 'expired' ), true ) ) : ?>
												<details><summary><?php esc_html_e( 'Cancel', 'sustainable-catalyst-engagement-intake' ); ?></summary><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-confirm-form"><input type="hidden" name="action" value="sc_ei_workflow_core_cancel_handoff"><input type="hidden" name="case_id" value="<?php echo esc_attr( $selected['id'] ); ?>"><input type="hidden" name="handoff_id" value="<?php echo esc_attr( $handoff['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_workflow_core_cancel_' . absint( $handoff['id'] ) ); ?><textarea name="handoff_reason" rows="2" required placeholder="<?php esc_attr_e( 'Cancellation reason', 'sustainable-catalyst-engagement-intake' ); ?>"></textarea><input type="text" name="workflow_core_confirmation" required placeholder="<?php echo esc_attr( 'CANCEL HANDOFF ' . absint( $handoff['id'] ) ); ?>"><button class="button"><?php esc_html_e( 'Cancel Handoff', 'sustainable-catalyst-engagement-intake' ); ?></button></form></details>
											<?php endif; ?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				</section>

				<section class="sc-ei-admin__card sc-ei-admin__card--wide">
					<h2><?php esc_html_e( 'Command Ledger', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<table class="widefat striped"><thead><tr><th scope="col"><?php esc_html_e( 'Command', 'sustainable-catalyst-engagement-intake' ); ?></th><th scope="col"><?php esc_html_e( 'State', 'sustainable-catalyst-engagement-intake' ); ?></th><th scope="col"><?php esc_html_e( 'Actor', 'sustainable-catalyst-engagement-intake' ); ?></th><th scope="col"><?php esc_html_e( 'Result', 'sustainable-catalyst-engagement-intake' ); ?></th></tr></thead><tbody><?php if ( ! $commands ) : ?><tr><td colspan="4"><?php esc_html_e( 'No commands recorded.', 'sustainable-catalyst-engagement-intake' ); ?></td></tr><?php endif; ?><?php foreach ( $commands as $command ) : ?><tr><td><strong>#<?php echo esc_html( $command['id'] ); ?> · <?php echo esc_html( SC_EI_Workflow_Core_Schema::label( SC_EI_Workflow_Core_Schema::command_types(), $command['command_type'] ) ); ?></strong><br><code><?php echo esc_html( substr( $command['command_key'], 0, 16 ) ); ?></code></td><td><?php echo esc_html( SC_EI_Workflow_Core_Schema::label( SC_EI_Workflow_Core_Schema::command_statuses(), $command['status'] ) ); ?><br><span class="description"><?php echo esc_html( $command['created_at'] ); ?> UTC</span></td><td><?php echo esc_html( $command['requested_by_name'] ?: __( 'System', 'sustainable-catalyst-engagement-intake' ) ); ?></td><td><?php if ( $command['error_code'] ) : ?><strong><?php echo esc_html( $command['error_code'] ); ?></strong><br><?php echo esc_html( $command['error_message'] ); ?><?php else : ?><?php echo esc_html( $command['completed_at'] ?: '—' ); ?><?php endif; ?></td></tr><?php endforeach; ?></tbody></table>
				</section>
			</main>

			<aside>
				<section class="sc-ei-admin__card">
					<h2><?php esc_html_e( 'Case Counters', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<dl class="sc-ei-admin__details">
						<dt><?php esc_html_e( 'Blockers', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( absint( $selected['blocker_count'] ) ); ?></dd>
						<dt><?php esc_html_e( 'Open commands', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( absint( $selected['open_command_count'] ) ); ?></dd>
						<dt><?php esc_html_e( 'Pending handoffs', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( absint( $selected['pending_handoff_count'] ) ); ?></dd>
						<dt><?php esc_html_e( 'Last authoritative event', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $selected['last_event_at'] ?: '—' ); ?></dd>
						<dt><?php esc_html_e( 'Last stage transition', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $selected['last_transition_at'] ?: '—' ); ?></dd>
					</dl>
				</section>

				<?php if ( current_user_can( 'sc_intake_dispatch_workflow_outbox' ) ) : ?>
					<section class="sc-ei-admin__card">
						<h2><?php esc_html_e( 'Outbox Control', 'sustainable-catalyst-engagement-intake' ); ?></h2>
						<p><?php esc_html_e( 'Dispatch invokes registered internal WordPress adapters only. No arbitrary external request is made.', 'sustainable-catalyst-engagement-intake' ); ?></p>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-confirm-form">
							<input type="hidden" name="action" value="sc_ei_workflow_core_dispatch"><input type="hidden" name="case_id" value="<?php echo esc_attr( $selected['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_workflow_core_dispatch_' . absint( $selected['id'] ) ); ?>
							<input type="text" name="workflow_core_confirmation" required autocomplete="off" placeholder="DISPATCH OUTBOX">
							<button class="button"><?php esc_html_e( 'Dispatch Due Events', 'sustainable-catalyst-engagement-intake' ); ?></button>
						</form>
					</section>
				<?php endif; ?>

				<section class="sc-ei-admin__card">
					<h2><?php esc_html_e( 'Outbox History', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<div class="sc-ei-core-event-list">
						<?php if ( ! $outbox ) : ?><p><?php esc_html_e( 'No outbox events recorded.', 'sustainable-catalyst-engagement-intake' ); ?></p><?php endif; ?>
						<?php foreach ( array_slice( $outbox, 0, 50 ) as $event ) : ?>
							<article><strong>#<?php echo esc_html( $event['id'] ); ?> · <?php echo esc_html( ucwords( str_replace( '_', ' ', $event['event_type'] ) ) ); ?></strong><span><?php echo esc_html( $event['target'] . ' · ' . $event['status'] ); ?></span><span><?php echo esc_html( absint( $event['attempts'] ) . '/' . absint( $event['max_attempts'] ) . ' attempts' ); ?></span><?php if ( $event['error_code'] ) : ?><small><?php echo esc_html( $event['error_code'] ); ?></small><?php endif; ?></article>
						<?php endforeach; ?>
					</div>
				</section>
			</aside>
		</div>
	<?php endif; ?>
</div>
