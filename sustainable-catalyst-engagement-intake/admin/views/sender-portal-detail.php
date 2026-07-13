<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$success = array(
	'portal_invitation_issued'       => __( 'A one-time portal invitation was issued. Copy it now; it will not be displayed again.', 'sustainable-catalyst-engagement-intake' ),
	'portal_access_updated'          => __( 'Portal access state updated.', 'sustainable-catalyst-engagement-intake' ),
	'portal_sessions_revoked'        => __( 'Active portal sessions revoked.', 'sustainable-catalyst-engagement-intake' ),
	'portal_reply_recorded'          => __( 'Secure staff reply recorded in the portal. No email was sent.', 'sustainable-catalyst-engagement-intake' ),
	'portal_communication_published' => __( 'The outbound communication is now visible in the sender portal.', 'sustainable-catalyst-engagement-intake' ),
	'portal_communication_hidden'    => __( 'The communication is no longer visible in the sender portal.', 'sustainable-catalyst-engagement-intake' ),
);
$permissions = json_decode( (string) $access['permissions_json'], true ) ?: array();
?>
<div class="wrap sc-ei-admin sc-ei-portal-admin">
	<h1><?php echo esc_html( sprintf( __( 'Sender Portal · %s', 'sustainable-catalyst-engagement-intake' ), $inquiry['reference'] ) ); ?></h1>
	<p>
		<a href="<?php echo esc_url( self::url() ); ?>"><?php esc_html_e( 'Portal Records', 'sustainable-catalyst-engagement-intake' ); ?></a>
		· <a href="<?php echo esc_url( add_query_arg( array( 'page' => 'sc-engagement-intake', 'action' => 'view', 'inquiry' => absint( $inquiry['id'] ) ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Full Inquiry', 'sustainable-catalyst-engagement-intake' ); ?></a>
		· <a href="<?php echo esc_url( SC_EI_Communication_Admin::thread_url( absint( $inquiry['id'] ) ) ); ?>"><?php esc_html_e( 'Communication History', 'sustainable-catalyst-engagement-intake' ); ?></a>
		· <a href="<?php echo esc_url( SC_EI_Privacy_Admin::url( 'overview', array( 'inquiry' => absint( $inquiry['id'] ) ) ) ); ?>"><?php esc_html_e( 'Privacy Center', 'sustainable-catalyst-engagement-intake' ); ?></a>
	</p>

	<?php if ( isset( $success[ $message ] ) ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $success[ $message ] ); ?></p></div><?php elseif ( $message ) : ?><div class="notice notice-error is-dismissible"><p><?php echo esc_html( ucwords( str_replace( '_', ' ', $message ) ) ); ?></p></div><?php endif; ?>

	<?php if ( $one_time_link ) : ?>
		<section class="sc-ei-admin__card sc-ei-admin__card--wide sc-ei-portal-one-time-link">
			<h2><?php esc_html_e( 'One-Time Invitation Link', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<div class="sc-ei-diagnostic-warning"><strong><?php esc_html_e( 'Copy now.', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'The raw credential is not stored and this display is deleted after the page is loaded.', 'sustainable-catalyst-engagement-intake' ); ?></div>
			<div class="sc-ei-portal-copy-row"><input type="text" readonly value="<?php echo esc_attr( $one_time_link['url'] ); ?>" data-sc-ei-portal-copy-source><button type="button" class="button button-primary" data-sc-ei-portal-copy><?php esc_html_e( 'Copy Invitation', 'sustainable-catalyst-engagement-intake' ); ?></button></div>
			<p class="description"><?php echo esc_html( sprintf( __( 'Expires %s UTC. Send only through an approved channel and do not place it in a public record.', 'sustainable-catalyst-engagement-intake' ), $one_time_link['expires_at'] ) ); ?></p>
		</section>
	<?php endif; ?>

	<div class="sc-ei-portal-admin-layout">
		<main>
			<section class="sc-ei-admin__card sc-ei-admin__card--wide">
				<h2><?php esc_html_e( 'Portal Access', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<dl class="sc-ei-admin__details">
					<dt><?php esc_html_e( 'State', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Portal_Schema::label( SC_EI_Portal_Schema::access_statuses(), $access['status'] ) ); ?></dd>
					<dt><?php esc_html_e( 'Sender', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $inquiry['contact_name'] . ' · ' . $inquiry['contact_email'] ); ?></dd>
					<dt><?php esc_html_e( 'Invitation expires', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo $access['invite_expires_at'] ? esc_html( get_date_from_gmt( $access['invite_expires_at'], 'M j, Y g:i a' ) ) : '—'; ?></dd>
					<dt><?php esc_html_e( 'Activated', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo $access['activated_at'] ? esc_html( get_date_from_gmt( $access['activated_at'], 'M j, Y g:i a' ) ) : '—'; ?></dd>
					<dt><?php esc_html_e( 'Last access', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo $access['last_access_at'] ? esc_html( get_date_from_gmt( $access['last_access_at'], 'M j, Y g:i a' ) ) : '—'; ?></dd>
					<dt><?php esc_html_e( 'Failed attempts', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( number_format_i18n( absint( $access['failed_attempts'] ) ) ); ?></dd>
					<dt><?php esc_html_e( 'Locked until', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo $access['locked_until'] ? esc_html( get_date_from_gmt( $access['locked_until'], 'M j, Y g:i a' ) ) : '—'; ?></dd>
					<dt><?php esc_html_e( 'Terms version', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $access['terms_version'] ); ?></dd>
					<dt><?php esc_html_e( 'Permissions', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( implode( ', ', array_map( static fn( string $key ): string => SC_EI_Portal_Schema::label( SC_EI_Portal_Schema::permissions(), $key ), $permissions ) ) ); ?></dd>
				</dl>
			</section>

			<?php if ( current_user_can( 'sc_intake_issue_portal_invites' ) ) : ?>
				<section class="sc-ei-admin__card sc-ei-admin__card--wide">
					<h2><?php esc_html_e( 'Issue a Fresh One-Time Invitation', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<p><?php esc_html_e( 'This invalidates all current sessions. It does not send email automatically.', 'sustainable-catalyst-engagement-intake' ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-admin-form">
						<input type="hidden" name="action" value="sc_ei_issue_portal_invite"><input type="hidden" name="inquiry_id" value="<?php echo esc_attr( $inquiry['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_issue_portal_invite' ); ?>
						<label><span><?php esc_html_e( 'Invitation lifetime in hours', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" name="invite_ttl_hours" min="1" max="720" value="<?php echo esc_attr( $settings['portal_invite_ttl_hours'] ); ?>"></label>
						<fieldset class="sc-ei-portal-admin-form__wide"><legend><?php esc_html_e( 'Permissions', 'sustainable-catalyst-engagement-intake' ); ?></legend><div class="sc-ei-portal-permission-grid"><?php foreach ( SC_EI_Portal_Schema::permissions() as $key => $label ) : ?><label><input type="checkbox" name="portal_permissions[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $permissions, true ) ); ?>> <?php echo esc_html( $label ); ?></label><?php endforeach; ?></div></fieldset>
						<label class="sc-ei-portal-admin-form__wide"><span><?php esc_html_e( 'Internal invitation note', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="invitation_note" rows="3"><?php echo esc_textarea( $access['invitation_note'] ); ?></textarea></label>
						<p class="sc-ei-portal-admin-form__wide"><button type="submit" class="button"><?php esc_html_e( 'Reissue Invitation and Revoke Sessions', 'sustainable-catalyst-engagement-intake' ); ?></button></p>
					</form>
				</section>
			<?php endif; ?>

			<?php if ( current_user_can( 'sc_intake_post_portal_messages' ) ) : ?>
				<section class="sc-ei-admin__card sc-ei-admin__card--wide">
					<h2><?php esc_html_e( 'Secure Portal Thread', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<div class="sc-ei-portal-admin-thread">
						<?php if ( ! $portal_messages ) : ?><p><?php esc_html_e( 'No portal-visible messages yet.', 'sustainable-catalyst-engagement-intake' ); ?></p><?php endif; ?>
						<?php foreach ( $portal_messages as $portal_message ) : ?>
							<article class="sc-ei-portal-message sc-ei-portal-message--<?php echo esc_attr( $portal_message['direction'] ); ?>">
								<p class="sc-ei-portal-message__meta"><strong><?php echo 'inbound' === $portal_message['direction'] ? esc_html__( 'Sender', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'Sustainable Catalyst', 'sustainable-catalyst-engagement-intake' ); ?></strong> · <?php echo esc_html( get_date_from_gmt( $portal_message['occurred_at'] ?: $portal_message['created_at'], 'M j, Y g:i a' ) ); ?></p>
								<div><?php echo nl2br( esc_html( $portal_message['body_text'] ) ); ?></div>
								<?php if ( 'outbound' === $portal_message['direction'] ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-form"><input type="hidden" name="action" value="sc_ei_publish_portal_communication"><input type="hidden" name="access_id" value="<?php echo esc_attr( $access['id'] ); ?>"><input type="hidden" name="communication_id" value="<?php echo esc_attr( $portal_message['id'] ); ?>"><input type="hidden" name="portal_visible" value="0"><?php wp_nonce_field( 'sc_ei_publish_portal_communication_' . absint( $portal_message['id'] ) ); ?><button type="submit" class="button-link"><?php esc_html_e( 'Hide from portal', 'sustainable-catalyst-engagement-intake' ); ?></button></form><?php endif; ?>
							</article>
						<?php endforeach; ?>
					</div>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-admin-form">
						<input type="hidden" name="action" value="sc_ei_post_portal_reply"><input type="hidden" name="access_id" value="<?php echo esc_attr( $access['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_post_portal_reply_' . absint( $access['id'] ) ); ?>
						<label class="sc-ei-portal-admin-form__wide"><span><?php esc_html_e( 'Secure reply', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="portal_reply" rows="7" required maxlength="50000"></textarea></label>
						<p class="sc-ei-portal-admin-form__wide"><button type="submit" class="button button-primary"><?php esc_html_e( 'Record Portal Reply', 'sustainable-catalyst-engagement-intake' ); ?></button> <span class="description"><?php esc_html_e( 'No email is sent.', 'sustainable-catalyst-engagement-intake' ); ?></span></p>
					</form>
				</section>
			<?php endif; ?>

			<?php if ( $publishable && current_user_can( 'sc_intake_post_portal_messages' ) ) : ?>
				<section class="sc-ei-admin__card sc-ei-admin__card--wide">
					<h2><?php esc_html_e( 'Publish Existing Outbound Communications', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<p><?php esc_html_e( 'Review every message before publication. Internal notes and drafts are never eligible.', 'sustainable-catalyst-engagement-intake' ); ?></p>
					<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Date', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Type', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Subject', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Action', 'sustainable-catalyst-engagement-intake' ); ?></th></tr></thead><tbody>
						<?php foreach ( $publishable as $communication ) : ?><tr><td><?php echo esc_html( get_date_from_gmt( $communication['occurred_at'] ?: $communication['created_at'], 'M j, Y' ) ); ?></td><td><?php echo esc_html( SC_EI_Communication_Schema::label( SC_EI_Communication_Schema::types(), $communication['communication_type'] ) ); ?></td><td><?php echo esc_html( $communication['subject'] ); ?></td><td><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="sc_ei_publish_portal_communication"><input type="hidden" name="access_id" value="<?php echo esc_attr( $access['id'] ); ?>"><input type="hidden" name="communication_id" value="<?php echo esc_attr( $communication['id'] ); ?>"><input type="hidden" name="portal_visible" value="1"><?php wp_nonce_field( 'sc_ei_publish_portal_communication_' . absint( $communication['id'] ) ); ?><button type="submit" class="button"><?php esc_html_e( 'Publish to Portal', 'sustainable-catalyst-engagement-intake' ); ?></button></form></td></tr><?php endforeach; ?>
					</tbody></table>
				</section>
			<?php endif; ?>

			<section class="sc-ei-admin__card sc-ei-admin__card--wide">
				<h2><?php esc_html_e( 'Portal Access Events', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<p><?php esc_html_e( 'Network and browser identifiers are stored only as keyed hashes. Raw invitation and session credentials are never logged.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Time', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Event', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Target', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Outcome', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Context', 'sustainable-catalyst-engagement-intake' ); ?></th></tr></thead><tbody>
					<?php if ( ! $events ) : ?><tr><td colspan="5"><?php esc_html_e( 'No portal events recorded.', 'sustainable-catalyst-engagement-intake' ); ?></td></tr><?php endif; ?>
					<?php foreach ( $events as $event ) : ?><tr><td><?php echo esc_html( get_date_from_gmt( $event['created_at'], 'M j, Y g:i:s a' ) ); ?></td><td><?php echo esc_html( SC_EI_Portal_Schema::label( SC_EI_Portal_Schema::event_types(), $event['event_type'] ) ); ?></td><td><?php echo esc_html( $event['target_type'] . ( $event['target_id'] ? ' #' . $event['target_id'] : '' ) ); ?></td><td><?php echo esc_html( $event['outcome'] ); ?></td><td><code><?php echo esc_html( mb_substr( (string) $event['context_json'], 0, 500 ) ); ?></code></td></tr><?php endforeach; ?>
				</tbody></table>
			</section>
		</main>

		<aside>
			<section class="sc-ei-admin__card">
				<h2><?php esc_html_e( 'Inquiry State', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<dl class="sc-ei-admin__details">
					<dt><?php esc_html_e( 'Internal status', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Statuses::label( $inquiry['status'] ) ); ?></dd>
					<dt><?php esc_html_e( 'Sender-safe label', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Portal_Schema::public_status_label( $inquiry['status'] ) ); ?></dd>
					<dt><?php esc_html_e( 'Privacy state', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Privacy_Schema::label( SC_EI_Privacy_Schema::privacy_statuses(), $inquiry['privacy_status'] ) ); ?></dd>
					<dt><?php esc_html_e( 'Active holds', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( number_format_i18n( absint( $inquiry['legal_hold_count'] ) ) ); ?></dd>
					<dt><?php esc_html_e( 'Withdrawal request', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Portal_Schema::label( SC_EI_Portal_Schema::withdrawal_statuses(), $inquiry['sender_withdrawal_status'] ) ); ?></dd>
				</dl>
				<?php if ( 'requested' === $inquiry['sender_withdrawal_status'] ) : ?><div class="sc-ei-diagnostic-warning"><strong><?php esc_html_e( 'Human action required:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo esc_html( $inquiry['sender_withdrawal_reason'] ); ?></div><?php endif; ?>
			</section>

			<?php if ( current_user_can( 'sc_intake_revoke_portal_access' ) ) : ?>
				<section class="sc-ei-admin__card sc-ei-portal-danger-zone">
					<h2><?php esc_html_e( 'Access Controls', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-admin-form">
						<input type="hidden" name="action" value="sc_ei_change_portal_access"><input type="hidden" name="access_id" value="<?php echo esc_attr( $access['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_change_portal_access_' . absint( $access['id'] ) ); ?>
						<label><span><?php esc_html_e( 'New access state', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="portal_status"><option value="active"><?php esc_html_e( 'Resume Active Access', 'sustainable-catalyst-engagement-intake' ); ?></option><option value="suspended"><?php esc_html_e( 'Suspend Access', 'sustainable-catalyst-engagement-intake' ); ?></option><option value="revoked"><?php esc_html_e( 'Revoke Access', 'sustainable-catalyst-engagement-intake' ); ?></option></select></label>
						<label><span><?php esc_html_e( 'Reason', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="access_reason" rows="3"></textarea></label>
						<label><span><?php echo esc_html( sprintf( __( 'For suspension or revocation type SUSPENDED %1$d or REVOKED %1$d', 'sustainable-catalyst-engagement-intake' ), absint( $access['id'] ) ) ); ?></span><input type="text" name="access_confirmation" autocomplete="off"></label>
						<button type="submit" class="button"><?php esc_html_e( 'Change Access State', 'sustainable-catalyst-engagement-intake' ); ?></button>
					</form>
				</section>

				<section class="sc-ei-admin__card sc-ei-portal-danger-zone">
					<h2><?php esc_html_e( 'Revoke All Sessions', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-admin-form">
						<input type="hidden" name="action" value="sc_ei_revoke_portal_sessions"><input type="hidden" name="access_id" value="<?php echo esc_attr( $access['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_revoke_portal_sessions_' . absint( $access['id'] ) ); ?>
						<label><span><?php esc_html_e( 'Reason', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="session_reason" rows="3" required></textarea></label>
						<label><span><?php echo esc_html( sprintf( __( 'Type SESSIONS %d', 'sustainable-catalyst-engagement-intake' ), absint( $access['id'] ) ) ); ?></span><input type="text" name="session_confirmation" required autocomplete="off"></label>
						<button type="submit" class="button"><?php esc_html_e( 'Revoke Active Sessions', 'sustainable-catalyst-engagement-intake' ); ?></button>
					</form>
				</section>
			<?php endif; ?>

			<section class="sc-ei-admin__card">
				<h2><?php esc_html_e( 'Session History', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<?php if ( ! $sessions ) : ?><p><?php esc_html_e( 'No sessions recorded.', 'sustainable-catalyst-engagement-intake' ); ?></p><?php endif; ?>
				<?php foreach ( array_reverse( $sessions ) as $portal_session ) : ?><article class="sc-ei-portal-session-record"><strong><?php echo esc_html( SC_EI_Portal_Schema::label( SC_EI_Portal_Schema::session_statuses(), $portal_session['status'] ) ); ?></strong><span>#<?php echo esc_html( $portal_session['id'] ); ?> · <?php echo esc_html( get_date_from_gmt( $portal_session['created_at'], 'M j, Y g:i a' ) ); ?></span><span><?php echo esc_html( sprintf( __( '%d activities', 'sustainable-catalyst-engagement-intake' ), absint( $portal_session['activity_count'] ) ) ); ?></span></article><?php endforeach; ?>
			</section>

			<?php if ( current_user_can( 'sc_intake_export_portal_audit' ) ) : ?><section class="sc-ei-admin__card"><h2><?php esc_html_e( 'Private Audit Export', 'sustainable-catalyst-engagement-intake' ); ?></h2><p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sc_ei_export_portal_audit&access=' . absint( $access['id'] ) ), 'sc_ei_export_portal_audit_' . absint( $access['id'] ) ) ); ?>"><?php esc_html_e( 'Export Portal Audit JSON', 'sustainable-catalyst-engagement-intake' ); ?></a></p></section><?php endif; ?>
		</aside>
	</div>
</div>
