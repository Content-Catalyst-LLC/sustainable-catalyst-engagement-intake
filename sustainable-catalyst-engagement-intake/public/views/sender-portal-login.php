<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$messages = array(
	'portal_signed_out'        => __( 'You have been signed out securely.', 'sustainable-catalyst-engagement-intake' ),
	'portal_access_revoked'    => __( 'Portal access was revoked and all active sessions were ended.', 'sustainable-catalyst-engagement-intake' ),
	'portal_recovery_received' => __( 'Your recovery request was received. If the details match an eligible portal record, Sustainable Catalyst will review it. No access link is issued automatically.', 'sustainable-catalyst-engagement-intake' ),
);
$errors = array(
	'portal_activation_failed'       => __( 'The invitation could not be activated. Verify the invitation email or submit a recovery request.', 'sustainable-catalyst-engagement-intake' ),
	'portal_terms_required'          => __( 'Accept the secure portal terms to continue.', 'sustainable-catalyst-engagement-intake' ),
	'portal_cookie_failed'           => __( 'The browser could not establish the secure session cookie. Confirm HTTPS and cookie support, then retry the fresh invitation shown by this page.', 'sustainable-catalyst-engagement-intake' ),
	'portal_session_expired'         => __( 'The portal session expired. Submit a recovery request for a fresh invitation.', 'sustainable-catalyst-engagement-intake' ),
	'portal_session_invalid'         => __( 'The portal session is no longer active. Submit a recovery request when continued access is appropriate.', 'sustainable-catalyst-engagement-intake' ),
	'portal_access_inactive'         => __( 'Portal access is not active. Submit a recovery request when continued access is appropriate.', 'sustainable-catalyst-engagement-intake' ),
	'portal_session_browser_changed' => __( 'The browser identity changed and the session was revoked. Submit a recovery request for a fresh invitation.', 'sustainable-catalyst-engagement-intake' ),
	'portal_https_required'          => __( 'A secure HTTPS connection is required before portal authentication can continue.', 'sustainable-catalyst-engagement-intake' ),
	'portal_invite_locked'           => __( 'The invitation is temporarily locked after failed email verification. Submit a recovery request or try again after the lockout expires.', 'sustainable-catalyst-engagement-intake' ),
	'portal_invite_expired'          => __( 'The invitation expired. Submit a recovery request for a fresh invitation.', 'sustainable-catalyst-engagement-intake' ),
	'portal_invite_inactive'         => __( 'The invitation is no longer active. Submit a recovery request for a fresh invitation.', 'sustainable-catalyst-engagement-intake' ),
	'portal_activation_conflict'     => __( 'The invitation changed before activation completed. Reload the original invitation or submit a recovery request.', 'sustainable-catalyst-engagement-intake' ),
	'portal_activation_retry'        => __( 'Activation was rolled back safely. The invitation remains usable; retry it below.', 'sustainable-catalyst-engagement-intake' ),
	'portal_activation_form_expired' => __( 'The activation form expired, but the invitation was preserved. Submit the form again.', 'sustainable-catalyst-engagement-intake' ),
);
$state = sanitize_key( (string) ( $invitation_state['state'] ?? 'invalid' ) );
$show_activation = $invite_public_id && $invite_token && 'valid' === $state;
$show_recovery = ! empty( $settings['portal_recovery_enabled'] ) && SC_EI_Portal_Schema::secure_transport_available();
?>
<section class="sc-ei-portal sc-ei-portal--login" data-sc-ei-portal-login>
	<header class="sc-ei-portal__hero">
		<div>
			<p class="sc-ei-portal__eyebrow"><?php esc_html_e( 'Private Engagement Workspace', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<h2><?php echo esc_html( $atts['title'] ); ?></h2>
			<p><?php echo esc_html( $atts['intro'] ); ?></p>
		</div>
		<div class="sc-ei-portal__status">
			<span><?php esc_html_e( 'Authentication patch', 'sustainable-catalyst-engagement-intake' ); ?></span>
			<strong><?php esc_html_e( 'v0.9.2 secure engagement-handoff workflow', 'sustainable-catalyst-engagement-intake' ); ?></strong>
		</div>
	</header>

	<?php if ( isset( $messages[ $result_code ] ) ) : ?>
		<div class="sc-ei-portal-notice sc-ei-portal-notice--success" role="status"><?php echo esc_html( $messages[ $result_code ] ); ?></div>
	<?php endif; ?>
	<?php if ( $error_code ) : ?>
		<div class="sc-ei-portal-notice sc-ei-portal-notice--error" role="alert"><?php echo esc_html( $errors[ $error_code ] ?? __( 'Secure portal authentication could not be completed.', 'sustainable-catalyst-engagement-intake' ) ); ?></div>
	<?php elseif ( isset( $errors[ $context_error ] ) ) : ?>
		<div class="sc-ei-portal-notice sc-ei-portal-notice--warning" role="status"><?php echo esc_html( $errors[ $context_error ] ); ?></div>
	<?php endif; ?>

	<?php if ( $show_activation ) : ?>
		<div class="sc-ei-portal-card" data-sc-ei-portal-invitation>
			<h3><?php esc_html_e( 'Activate your private invitation', 'sustainable-catalyst-engagement-intake' ); ?></h3>
			<p><?php esc_html_e( 'Confirm the same email used for the original inquiry. Incorrect invitation tokens do not increment lockout attempts; lockout is applied only after a valid invitation is paired with the wrong email challenge.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<?php if ( ! empty( $invitation_state['expires_at'] ) ) : ?><p class="sc-ei-portal-auth-meta"><?php echo esc_html( sprintf( __( 'Invitation expires %s UTC.', 'sustainable-catalyst-engagement-intake' ), $invitation_state['expires_at'] ) ); ?></p><?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-form">
				<input type="hidden" name="action" value="sc_ei_portal_activate">
				<input type="hidden" name="portal_public_id" value="<?php echo esc_attr( $invite_public_id ); ?>">
				<input type="hidden" name="portal_token" value="<?php echo esc_attr( $invite_token ); ?>">
				<?php wp_nonce_field( 'sc_ei_portal_activate' ); ?>
				<label>
					<span><?php esc_html_e( 'Inquiry email', 'sustainable-catalyst-engagement-intake' ); ?></span>
					<input type="email" name="portal_email" autocomplete="email" required maxlength="191">
				</label>
				<label class="sc-ei-check sc-ei-portal-check">
					<input type="checkbox" name="portal_terms" value="1" required>
					<span><?php echo esc_html( sprintf( __( 'I understand this is a private portal, will not share the invitation or session, and accept secure portal terms version %s.', 'sustainable-catalyst-engagement-intake' ), $settings['portal_terms_version'] ) ); ?></span>
				</label>
				<button type="submit" class="sc-ei-button sc-ei-button--primary"><?php esc_html_e( 'Activate Secure Portal', 'sustainable-catalyst-engagement-intake' ); ?></button>
			</form>
		</div>
	<?php elseif ( $invite_public_id && $invite_token ) : ?>
		<div class="sc-ei-portal-card">
			<h3><?php echo esc_html( SC_EI_Portal_Schema::label( SC_EI_Portal_Schema::invitation_states(), $state ) ); ?></h3>
			<?php if ( 'locked' === $state && ! empty( $invitation_state['locked_until'] ) ) : ?><p><?php echo esc_html( sprintf( __( 'The verified invitation is locked until %s UTC.', 'sustainable-catalyst-engagement-intake' ), $invitation_state['locked_until'] ) ); ?></p><?php else : ?><p><?php esc_html_e( 'This invitation cannot currently establish a session. Use the recovery form below rather than repeatedly retrying the same credential.', 'sustainable-catalyst-engagement-intake' ); ?></p><?php endif; ?>
		</div>
	<?php else : ?>
		<div class="sc-ei-portal-card">
			<h3><?php esc_html_e( 'A private invitation is required', 'sustainable-catalyst-engagement-intake' ); ?></h3>
			<p><?php esc_html_e( 'The portal does not use public passwords, WordPress accounts, or an inquiry-reference lookup. Open a single-use invitation issued directly by Sustainable Catalyst.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<p><?php esc_html_e( 'For security, this page never confirms whether an inquiry reference, email address, invitation, or recovery request exists.', 'sustainable-catalyst-engagement-intake' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( $show_recovery ) : ?>
		<div class="sc-ei-portal-card sc-ei-portal-card--recovery">
			<h3><?php esc_html_e( 'Request a fresh invitation', 'sustainable-catalyst-engagement-intake' ); ?></h3>
			<p><?php esc_html_e( 'Use this after an expired, consumed, locked, revoked, lost, or browser-bound session. The response is intentionally identical whether or not the details match. Recovery requires human review and never sends or issues an access link automatically.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-form">
				<input type="hidden" name="action" value="sc_ei_portal_recovery">
				<?php wp_nonce_field( 'sc_ei_portal_recovery' ); ?>
				<div class="sc-ei-portal-honeypot" aria-hidden="true">
					<label><span>Company website</span><input type="text" name="portal_company_website" tabindex="-1" autocomplete="off"></label>
				</div>
				<label>
					<span><?php esc_html_e( 'Inquiry reference', 'sustainable-catalyst-engagement-intake' ); ?></span>
					<input type="text" name="portal_reference" autocomplete="off" required maxlength="80">
				</label>
				<label>
					<span><?php esc_html_e( 'Inquiry email', 'sustainable-catalyst-engagement-intake' ); ?></span>
					<input type="email" name="portal_recovery_email" autocomplete="email" required maxlength="191">
				</label>
				<label>
					<span><?php esc_html_e( 'Why access needs to be recovered', 'sustainable-catalyst-engagement-intake' ); ?></span>
					<textarea name="portal_recovery_reason" rows="4" required minlength="<?php echo esc_attr( max( 0, absint( $settings['portal_recovery_min_reason_chars'] ) ) ); ?>" maxlength="5000"></textarea>
				</label>
				<button type="submit" class="sc-ei-button"><?php esc_html_e( 'Submit Recovery Request', 'sustainable-catalyst-engagement-intake' ); ?></button>
			</form>
		</div>
	<?php endif; ?>

	<div class="sc-ei-portal-security-grid">
		<div><strong><?php esc_html_e( 'Atomic activation', 'sustainable-catalyst-engagement-intake' ); ?></strong><span><?php esc_html_e( 'Invitation consumption rolls back unless access, inquiry, and session records all succeed.', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div><strong><?php esc_html_e( 'Private recovery', 'sustainable-catalyst-engagement-intake' ); ?></strong><span><?php esc_html_e( 'Generic responses prevent inquiry and email enumeration.', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div><strong><?php esc_html_e( 'Human approval', 'sustainable-catalyst-engagement-intake' ); ?></strong><span><?php esc_html_e( 'Recovery never issues or emails a link automatically.', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
	</div>
</section>
