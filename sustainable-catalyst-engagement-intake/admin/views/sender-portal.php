<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$success = array(
	'portal_settings_saved' => __( 'Sender portal settings saved.', 'sustainable-catalyst-engagement-intake' ),
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
		<span><?php esc_html_e( 'Raw invitation and session credentials are never stored. The portal uses one-time invitations, an email challenge, HttpOnly SameSite cookies, revocable sessions, CSRF protection, rate limits, no-store headers, no indexing, and hashed network fingerprints.', 'sustainable-catalyst-engagement-intake' ); ?></span>
	</div>

	<div class="sc-ei-fit-metrics sc-ei-portal-metrics">
		<a href="<?php echo esc_url( self::url( 0, array( 'status' => 'invited' ) ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['invited'] ) ); ?></strong><span><?php esc_html_e( 'invited', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( self::url( 0, array( 'status' => 'active' ) ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['active'] ) ); ?></strong><span><?php esc_html_e( 'active access', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a class="<?php echo $metrics['suspended'] ? 'sc-ei-review-metric--attention' : ''; ?>" href="<?php echo esc_url( self::url( 0, array( 'status' => 'suspended' ) ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['suspended'] ) ); ?></strong><span><?php esc_html_e( 'suspended', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( self::url( 0, array( 'status' => 'revoked' ) ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['revoked'] ) ); ?></strong><span><?php esc_html_e( 'revoked', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a><strong><?php echo esc_html( number_format_i18n( $metrics['active_sessions'] ) ); ?></strong><span><?php esc_html_e( 'active sessions', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a><strong><?php echo esc_html( number_format_i18n( $metrics['messages_today'] ) ); ?></strong><span><?php esc_html_e( 'portal messages today', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a class="<?php echo $metrics['failed_today'] ? 'sc-ei-review-metric--danger' : ''; ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['failed_today'] ) ); ?></strong><span><?php esc_html_e( 'security rejections today', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a class="<?php echo $metrics['locked'] ? 'sc-ei-review-metric--danger' : ''; ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['locked'] ) ); ?></strong><span><?php esc_html_e( 'locked invitations', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
	</div>

	<div class="sc-ei-fit-toolbar">
		<form method="get" class="sc-ei-operation-filter-form">
			<input type="hidden" name="page" value="sc-engagement-intake-portal">
			<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search reference, name, email, or organization', 'sustainable-catalyst-engagement-intake' ); ?>">
			<select name="status"><option value=""><?php esc_html_e( 'All access states', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( SC_EI_Portal_Schema::access_statuses() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
			<button type="submit" class="button"><?php esc_html_e( 'Filter', 'sustainable-catalyst-engagement-intake' ); ?></button>
		</form>
	</div>

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
				<div class="sc-ei-diagnostic-warning sc-ei-portal-admin-form__wide"><strong><?php esc_html_e( 'Fixed protections:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'Email challenge, terms acceptance, HttpOnly cookies, SameSite Strict, no-store, noindex, hashed fingerprints, no WordPress accounts, and no automatic invitation email cannot be disabled in v0.8.0.', 'sustainable-catalyst-engagement-intake' ); ?></div>
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
