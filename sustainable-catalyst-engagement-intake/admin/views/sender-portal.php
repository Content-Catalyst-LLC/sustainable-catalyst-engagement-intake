<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$success = array(
	'portal_settings_saved'       => __( 'Sender portal settings saved.', 'sustainable-catalyst-engagement-intake' ),
	'portal_recovery_completed'   => __( 'Recovery approved and a fresh one-time invitation was issued.', 'sustainable-catalyst-engagement-intake' ),
	'portal_recovery_declined'    => __( 'Recovery request declined.', 'sustainable-catalyst-engagement-intake' ),
	'portal_invitation_unlocked'  => __( 'Invitation lockout reset.', 'sustainable-catalyst-engagement-intake' ),
);
?>
<div class="wrap sc-ei-admin sc-ei-portal-admin">
	<h1><?php esc_html_e( 'Secure Sender Portal', 'sustainable-catalyst-engagement-intake' ); ?></h1>
	<p><?php esc_html_e( 'Issue private passwordless invitations, supervise access and sessions, publish only sender-safe messages, and audit every portal action without creating public WordPress accounts.', 'sustainable-catalyst-engagement-intake' ); ?></p>

	<?php if ( isset( $success[ $message ] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $success[ $message ] ); ?></p></div>
	<?php elseif ( $message ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php echo esc_html( ucwords( str_replace( '_', ' ', $message ) ) ); ?></p></div>
	<?php endif; ?>

	<div class="sc-ei-portal-admin-boundary">
		<strong><?php esc_html_e( 'Security boundary', 'sustainable-catalyst-engagement-intake' ); ?></strong>
		<span><?php esc_html_e( 'Raw invitation and session credentials are never stored. v1.0.1 retains atomic authentication, recovery, Teams, proposals, Graph synchronization, engagement visibility, incident-safe read-only access, durable abuse controls, and accessibility hardening while adding canonical Workflow Core synchronization.', 'sustainable-catalyst-engagement-intake' ); ?></span>
	</div>

	<div class="sc-ei-fit-metrics sc-ei-portal-metrics">
		<a href="<?php echo esc_url( self::url( 0, array( 'status' => 'invited' ) ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['invited'] ) ); ?></strong><span><?php esc_html_e( 'invited', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( self::url( 0, array( 'status' => 'active' ) ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['active'] ) ); ?></strong><span><?php esc_html_e( 'active access', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a class="<?php echo $metrics['suspended'] ? 'sc-ei-review-metric--attention' : ''; ?>" href="<?php echo esc_url( self::url( 0, array( 'status' => 'suspended' ) ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['suspended'] ) ); ?></strong><span><?php esc_html_e( 'suspended', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( self::url( 0, array( 'status' => 'revoked' ) ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['revoked'] ) ); ?></strong><span><?php esc_html_e( 'revoked', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a><strong><?php echo esc_html( number_format_i18n( $metrics['active_sessions'] ) ); ?></strong><span><?php esc_html_e( 'active sessions', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a><strong><?php echo esc_html( number_format_i18n( $metrics['messages_today'] ) ); ?></strong><span><?php esc_html_e( 'portal messages today', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a class="<?php echo $metrics['pending_recovery'] ? 'sc-ei-review-metric--attention' : ''; ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['pending_recovery'] ) ); ?></strong><span><?php esc_html_e( 'pending recovery', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a><strong><?php echo esc_html( number_format_i18n( $metrics['recovery_today'] ) ); ?></strong><span><?php esc_html_e( 'recovery requests today', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a class="<?php echo $metrics['failed_today'] ? 'sc-ei-review-metric--danger' : ''; ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['failed_today'] ) ); ?></strong><span><?php esc_html_e( 'security rejections today', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a class="<?php echo $metrics['locked'] ? 'sc-ei-review-metric--danger' : ''; ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['locked'] ) ); ?></strong><span><?php esc_html_e( 'locked invitations', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a class="<?php echo $metrics['activation_rollbacks_today'] ? 'sc-ei-review-metric--attention' : ''; ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['activation_rollbacks_today'] ) ); ?></strong><span><?php esc_html_e( 'safe activation rollbacks', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
	</div>

	<div class="sc-ei-fit-toolbar">
		<form method="get" class="sc-ei-operation-filter-form">
			<input type="hidden" name="page" value="sc-engagement-intake-portal">
			<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search reference, name, email, or organization', 'sustainable-catalyst-engagement-intake' ); ?>">
			<select name="status"><option value=""><?php esc_html_e( 'All access states', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( SC_EI_Portal_Schema::access_statuses() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'sustainable-catalyst-engagement-intake' ); ?></button>
		</form>
	</div>

	<?php if ( current_user_can( 'sc_intake_view_portal_recovery' ) ) : ?>
		<section class="sc-ei-admin__card sc-ei-admin__card--wide sc-ei-portal-recovery-queue">
			<h2><?php esc_html_e( 'Authentication Recovery Queue', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><?php esc_html_e( 'Public recovery responses never confirm a match. Only matched, unexpired requests appear here. Completing recovery issues a fresh one-time invitation but never sends it automatically.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<form method="get" class="sc-ei-operation-filter-form">
				<input type="hidden" name="page" value="sc-engagement-intake-portal">
				<select name="recovery_status"><?php foreach ( SC_EI_Portal_Schema::recovery_statuses() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $recovery_status, $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
				<button type="submit" class="button"><?php esc_html_e( 'Filter Recovery', 'sustainable-catalyst-engagement-intake' ); ?></button>
			</form>
			<table class="widefat striped sc-ei-portal-recovery-table">
				<thead><tr><th><?php esc_html_e( 'Request', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Inquiry', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Reason', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Activity', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Human decision', 'sustainable-catalyst-engagement-intake' ); ?></th></tr></thead>
				<tbody>
					<?php if ( ! $recovery_requests ) : ?><tr><td colspan="5"><?php esc_html_e( 'No recovery requests match this state.', 'sustainable-catalyst-engagement-intake' ); ?></td></tr><?php endif; ?>
					<?php foreach ( $recovery_requests as $recovery ) : ?>
						<tr>
							<td><strong>#<?php echo esc_html( $recovery['id'] ); ?></strong><br><span class="sc-ei-fit-state sc-ei-fit-state--<?php echo esc_attr( $recovery['status'] ); ?>"><?php echo esc_html( SC_EI_Portal_Schema::label( SC_EI_Portal_Schema::recovery_statuses(), $recovery['status'] ) ); ?></span><br><span class="description"><?php echo esc_html( get_date_from_gmt( $recovery['requested_at'], 'M j, Y g:i a' ) ); ?></span></td>
							<td><?php if ( $recovery['access_id'] ) : ?><a href="<?php echo esc_url( self::url( absint( $recovery['access_id'] ) ) ); ?>"><strong><?php echo esc_html( $recovery['reference'] ?: '#' . $recovery['inquiry_id'] ); ?></strong></a><?php else : ?>—<?php endif; ?><br><?php echo esc_html( $recovery['contact_name'] ?: '' ); ?><br><span class="description"><?php echo esc_html( $recovery['contact_email'] ?: '' ); ?></span></td>
							<td><?php echo nl2br( esc_html( $recovery['recovery_reason'] ) ); ?></td>
							<td><?php echo esc_html( sprintf( __( '%1$d requests · expires %2$s UTC', 'sustainable-catalyst-engagement-intake' ), absint( $recovery['request_count'] ), $recovery['expires_at'] ) ); ?></td>
							<td>
								<?php if ( 'pending' === $recovery['status'] && current_user_can( 'sc_intake_manage_portal_recovery' ) ) : ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-recovery-form">
										<input type="hidden" name="action" value="sc_ei_review_portal_recovery"><input type="hidden" name="recovery_id" value="<?php echo esc_attr( $recovery['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_review_portal_recovery_' . absint( $recovery['id'] ) ); ?>
										<select name="recovery_decision"><option value="complete"><?php esc_html_e( 'Approve and issue fresh invitation', 'sustainable-catalyst-engagement-intake' ); ?></option><option value="decline"><?php esc_html_e( 'Decline recovery', 'sustainable-catalyst-engagement-intake' ); ?></option></select>
										<textarea name="recovery_decision_note" rows="3" required placeholder="<?php esc_attr_e( 'Human review rationale', 'sustainable-catalyst-engagement-intake' ); ?>"></textarea>
										<input type="text" name="recovery_confirmation" required autocomplete="off" placeholder="<?php echo esc_attr( 'RECOVER ' . absint( $recovery['id'] ) ); ?>">
										<button type="submit" class="button"><?php esc_html_e( 'Record Decision', 'sustainable-catalyst-engagement-intake' ); ?></button>
									</form>
								<?php else : ?>
									<?php echo esc_html( $recovery['decision_note'] ?: '—' ); ?><?php if ( $recovery['reviewed_by_name'] ) : ?><br><span class="description"><?php echo esc_html( $recovery['reviewed_by_name'] ); ?></span><?php endif; ?>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>
	<?php endif; ?>

	<?php if ( current_user_can( 'sc_intake_issue_portal_invites' ) ) : ?>
		<section class="sc-ei-admin__card sc-ei-admin__card--wide">
			<h2><?php esc_html_e( 'Issue or Reissue an Invitation', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><?php esc_html_e( 'Issuing a new invitation invalidates existing sessions for that inquiry. The raw link is displayed once for five minutes and is not written to the database.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-admin-form">
				<input type="hidden" name="action" value="sc_ei_issue_portal_invite"><?php wp_nonce_field( 'sc_ei_issue_portal_invite' ); ?>
				<label><span><?php esc_html_e( 'Inquiry ID', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" name="inquiry_id" min="1" required></label>
				<label><span><?php esc_html_e( 'Invitation lifetime in hours', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" name="invite_ttl_hours" min="1" max="720" value="<?php echo esc_attr( $settings['portal_invite_ttl_hours'] ); ?>"></label>
				<fieldset class="sc-ei-portal-admin-form__wide"><legend><?php esc_html_e( 'Sender permissions', 'sustainable-catalyst-engagement-intake' ); ?></legend><div class="sc-ei-portal-permission-grid"><?php foreach ( SC_EI_Portal_Schema::permissions() as $key => $label ) : ?><label><input type="checkbox" name="portal_permissions[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $settings['portal_default_permissions'], true ) ); ?>> <?php echo esc_html( $label ); ?></label><?php endforeach; ?></div></fieldset>
				<label class="sc-ei-portal-admin-form__wide"><span><?php esc_html_e( 'Internal invitation note', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="invitation_note" rows="3"></textarea></label>
				<p class="sc-ei-portal-admin-form__wide"><button type="submit" class="button button-primary"><?php esc_html_e( 'Issue One-Time Invitation', 'sustainable-catalyst-engagement-intake' ); ?></button></p>
			</form>
		</section>
	<?php endif; ?>

	<?php if ( current_user_can( 'sc_intake_manage_portal_settings' ) ) : ?>
		<details class="sc-ei-admin__card sc-ei-admin__card--wide sc-ei-portal-settings">
			<summary><strong><?php esc_html_e( 'Portal Security and Feature Settings', 'sustainable-catalyst-engagement-intake' ); ?></strong></summary>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-admin-form">
				<input type="hidden" name="action" value="sc_ei_save_portal_settings"><?php wp_nonce_field( 'sc_ei_save_portal_settings' ); ?>
				<label class="sc-ei-portal-admin-form__wide"><span><?php esc_html_e( 'Portal page URL', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="url" name="portal_settings[portal_page_url]" value="<?php echo esc_attr( $settings['portal_page_url'] ); ?>" required></label>
				<label><span><?php esc_html_e( 'Default invitation hours', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="1" max="720" name="portal_settings[portal_invite_ttl_hours]" value="<?php echo esc_attr( $settings['portal_invite_ttl_hours'] ); ?>"></label>
				<label><span><?php esc_html_e( 'Absolute session minutes', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="30" max="4320" name="portal_settings[portal_session_ttl_minutes]" value="<?php echo esc_attr( $settings['portal_session_ttl_minutes'] ); ?>"></label>
				<label><span><?php esc_html_e( 'Idle timeout minutes', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="5" max="1440" name="portal_settings[portal_idle_timeout_minutes]" value="<?php echo esc_attr( $settings['portal_idle_timeout_minutes'] ); ?>"></label>
				<label><span><?php esc_html_e( 'Maximum active sessions', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="1" max="10" name="portal_settings[portal_max_active_sessions]" value="<?php echo esc_attr( $settings['portal_max_active_sessions'] ); ?>"></label>
				<label><span><?php esc_html_e( 'Failed attempts before lockout', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="1" max="20" name="portal_settings[portal_max_failed_attempts]" value="<?php echo esc_attr( $settings['portal_max_failed_attempts'] ); ?>"></label>
				<label><span><?php esc_html_e( 'Lockout minutes', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="1" max="1440" name="portal_settings[portal_lockout_minutes]" value="<?php echo esc_attr( $settings['portal_lockout_minutes'] ); ?>"></label>
				<label><span><?php esc_html_e( 'Message limit per hour', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="1" max="100" name="portal_settings[portal_message_rate_limit_hour]" value="<?php echo esc_attr( $settings['portal_message_rate_limit_hour'] ); ?>"></label>
				<label><span><?php esc_html_e( 'Update limit per hour', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="1" max="200" name="portal_settings[portal_update_rate_limit_hour]" value="<?php echo esc_attr( $settings['portal_update_rate_limit_hour'] ); ?>"></label>
				<label><span><?php esc_html_e( 'Audit retention days', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="30" max="3650" name="portal_settings[portal_event_retention_days]" value="<?php echo esc_attr( $settings['portal_event_retention_days'] ); ?>"></label>
				<label><span><?php esc_html_e( 'Recovery requests per hour', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="1" max="20" name="portal_settings[portal_recovery_requests_per_hour]" value="<?php echo esc_attr( $settings['portal_recovery_requests_per_hour'] ); ?>"></label>
				<label><span><?php esc_html_e( 'Recovery deduplication minutes', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="1" max="1440" name="portal_settings[portal_recovery_cooldown_minutes]" value="<?php echo esc_attr( $settings['portal_recovery_cooldown_minutes'] ); ?>"></label>
				<label><span><?php esc_html_e( 'Recovery review expiry days', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="1" max="90" name="portal_settings[portal_recovery_expiry_days]" value="<?php echo esc_attr( $settings['portal_recovery_expiry_days'] ); ?>"></label>
				<label><span><?php esc_html_e( 'Minimum recovery-reason characters', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="0" max="500" name="portal_settings[portal_recovery_min_reason_chars]" value="<?php echo esc_attr( $settings['portal_recovery_min_reason_chars'] ); ?>"></label>
				<label><span><?php esc_html_e( 'Terms version', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="portal_settings[portal_terms_version]" value="<?php echo esc_attr( $settings['portal_terms_version'] ); ?>"></label>
				<fieldset class="sc-ei-portal-admin-form__wide"><legend><?php esc_html_e( 'Enabled sender capabilities', 'sustainable-catalyst-engagement-intake' ); ?></legend>
					<label><input type="checkbox" name="portal_settings[portal_allow_messages]" value="1" <?php checked( $settings['portal_allow_messages'], 1 ); ?>> <?php esc_html_e( 'Secure messages', 'sustainable-catalyst-engagement-intake' ); ?></label>
					<label><input type="checkbox" name="portal_settings[portal_allow_documents]" value="1" <?php checked( $settings['portal_allow_documents'], 1 ); ?>> <?php esc_html_e( 'Follow-up documents', 'sustainable-catalyst-engagement-intake' ); ?></label>
					<label><input type="checkbox" name="portal_settings[portal_allow_contact_updates]" value="1" <?php checked( $settings['portal_allow_contact_updates'], 1 ); ?>> <?php esc_html_e( 'Contact updates', 'sustainable-catalyst-engagement-intake' ); ?></label>
					<label><input type="checkbox" name="portal_settings[portal_allow_scheduling_updates]" value="1" <?php checked( $settings['portal_allow_scheduling_updates'], 1 ); ?>> <?php esc_html_e( 'Teams preference updates', 'sustainable-catalyst-engagement-intake' ); ?></label>
					<label><input type="checkbox" name="portal_settings[portal_allow_privacy_requests]" value="1" <?php checked( $settings['portal_allow_privacy_requests'], 1 ); ?>> <?php esc_html_e( 'Privacy requests', 'sustainable-catalyst-engagement-intake' ); ?></label>
					<label><input type="checkbox" name="portal_settings[portal_allow_withdrawal_requests]" value="1" <?php checked( $settings['portal_allow_withdrawal_requests'], 1 ); ?>> <?php esc_html_e( 'Withdrawal requests', 'sustainable-catalyst-engagement-intake' ); ?></label>
				</fieldset>
				<fieldset class="sc-ei-portal-admin-form__wide"><legend><?php esc_html_e( 'Default invitation permissions', 'sustainable-catalyst-engagement-intake' ); ?></legend><div class="sc-ei-portal-permission-grid"><?php foreach ( SC_EI_Portal_Schema::permissions() as $key => $label ) : ?><label><input type="checkbox" name="portal_settings[portal_default_permissions][]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $settings['portal_default_permissions'], true ) ); ?>> <?php echo esc_html( $label ); ?></label><?php endforeach; ?></div></fieldset>
				<div class="sc-ei-diagnostic-warning sc-ei-portal-admin-form__wide"><strong><?php esc_html_e( 'Fixed protections:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'Email challenge, terms acceptance, HttpOnly cookies, SameSite Strict, no-store, noindex, hashed fingerprints, no WordPress accounts, and no automatic invitation email plus HTTPS, __Host cookie use, legacy-cookie migration, atomic activation, wrong-token lockout protection, generic recovery responses, and human-approved reissue cannot be disabled in v1.0.1.', 'sustainable-catalyst-engagement-intake' ); ?></div>
				<p class="sc-ei-portal-admin-form__wide"><button type="submit" class="button"><?php esc_html_e( 'Save Portal Settings', 'sustainable-catalyst-engagement-intake' ); ?></button></p>
			</form>
		</details>
	<?php endif; ?>

	<section class="sc-ei-admin__card sc-ei-admin__card--wide">
		<h2><?php esc_html_e( 'Portal Access Records', 'sustainable-catalyst-engagement-intake' ); ?></h2>
		<table class="widefat striped sc-ei-portal-access-table">
			<thead><tr>
				<th><?php esc_html_e( 'Inquiry', 'sustainable-catalyst-engagement-intake' ); ?></th>
				<th><?php esc_html_e( 'Access', 'sustainable-catalyst-engagement-intake' ); ?></th>
				<th><?php esc_html_e( 'Sessions', 'sustainable-catalyst-engagement-intake' ); ?></th>
				<th><?php esc_html_e( 'Portal activity', 'sustainable-catalyst-engagement-intake' ); ?></th>
				<th><?php esc_html_e( 'Withdrawal', 'sustainable-catalyst-engagement-intake' ); ?></th>
				<th><?php esc_html_e( 'Last access', 'sustainable-catalyst-engagement-intake' ); ?></th>
			</tr></thead>
			<tbody>
				<?php if ( ! $access_records ) : ?><tr><td colspan="6"><?php esc_html_e( 'No sender portal records match the filters.', 'sustainable-catalyst-engagement-intake' ); ?></td></tr><?php endif; ?>
				<?php foreach ( $access_records as $record ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'sc-engagement-intake', 'action' => 'view', 'inquiry' => absint( $record['inquiry_id'] ) ), admin_url( 'admin.php' ) ) ); ?>"><strong><?php echo esc_html( $record['reference'] ); ?></strong></a><br><?php echo esc_html( $record['contact_name'] ); ?><br><span class="description"><?php echo esc_html( $record['contact_email'] ); ?><?php echo $record['organization'] ? ' · ' . esc_html( $record['organization'] ) : ''; ?></span></td>
						<td><a href="<?php echo esc_url( self::url( absint( $record['id'] ) ) ); ?>"><span class="sc-ei-fit-state sc-ei-fit-state--<?php echo esc_attr( $record['status'] ); ?>"><?php echo esc_html( SC_EI_Portal_Schema::label( SC_EI_Portal_Schema::access_statuses(), $record['status'] ) ); ?></span></a><br><span class="description">#<?php echo esc_html( $record['id'] ); ?> · row v<?php echo esc_html( $record['row_version'] ); ?></span></td>
						<td><?php echo esc_html( number_format_i18n( absint( $record['active_session_count'] ) ) ); ?></td>
						<td><?php echo esc_html( sprintf( __( '%1$d messages · %2$d documents', 'sustainable-catalyst-engagement-intake' ), absint( $record['portal_message_count'] ), absint( $record['portal_document_count'] ) ) ); ?></td>
						<td><?php echo esc_html( SC_EI_Portal_Schema::label( SC_EI_Portal_Schema::withdrawal_statuses(), $record['sender_withdrawal_status'] ) ); ?></td>
						<td><?php echo $record['last_access_at'] ? esc_html( get_date_from_gmt( $record['last_access_at'], 'M j, Y g:i a' ) ) : '—'; ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</section>
</div>
