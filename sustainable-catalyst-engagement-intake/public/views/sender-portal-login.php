<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$messages = array(
	'portal_signed_out'     => __( 'You have been signed out securely.', 'sustainable-catalyst-engagement-intake' ),
	'portal_access_revoked' => __( 'Portal access was revoked and all active sessions were ended.', 'sustainable-catalyst-engagement-intake' ),
);
$errors = array(
	'portal_activation_failed'      => __( 'The invitation could not be activated. Verify the invitation and email or ask Sustainable Catalyst to issue a new link.', 'sustainable-catalyst-engagement-intake' ),
	'portal_terms_required'         => __( 'Accept the secure portal terms to continue.', 'sustainable-catalyst-engagement-intake' ),
	'portal_cookie_failed'          => __( 'The browser could not establish a secure session cookie. Confirm cookies are allowed and try again.', 'sustainable-catalyst-engagement-intake' ),
	'portal_session_expired'        => __( 'The portal session expired. A new invitation is required.', 'sustainable-catalyst-engagement-intake' ),
	'portal_session_browser_changed'=> __( 'The browser identity changed. A new invitation is required.', 'sustainable-catalyst-engagement-intake' ),
);
?>
<section class="sc-ei-portal sc-ei-portal--login">
	<header class="sc-ei-portal__hero">
		<p class="sc-ei-portal__eyebrow"><?php esc_html_e( 'Private Engagement Workspace', 'sustainable-catalyst-engagement-intake' ); ?></p>
		<h2><?php echo esc_html( $atts['title'] ); ?></h2>
		<p><?php echo esc_html( $atts['intro'] ); ?></p>
	</header>

	<?php if ( isset( $messages[ $result_code ] ) ) : ?>
		<div class="sc-ei-portal-notice sc-ei-portal-notice--success" role="status"><?php echo esc_html( $messages[ $result_code ] ); ?></div>
	<?php endif; ?>
	<?php if ( $error_code ) : ?>
		<div class="sc-ei-portal-notice sc-ei-portal-notice--error" role="alert"><?php echo esc_html( $errors[ $error_code ] ?? __( 'Secure portal access could not be completed.', 'sustainable-catalyst-engagement-intake' ) ); ?></div>
	<?php endif; ?>

	<?php if ( $invite_public_id && $invite_token ) : ?>
		<div class="sc-ei-portal-card">
			<h3><?php esc_html_e( 'Activate your private invitation', 'sustainable-catalyst-engagement-intake' ); ?></h3>
			<p><?php esc_html_e( 'Confirm the same email used for the original inquiry. The invitation is single-use and the raw credential is never stored.', 'sustainable-catalyst-engagement-intake' ); ?></p>
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
	<?php else : ?>
		<div class="sc-ei-portal-card">
			<h3><?php esc_html_e( 'A private invitation is required', 'sustainable-catalyst-engagement-intake' ); ?></h3>
			<p><?php esc_html_e( 'The portal does not use public passwords, WordPress accounts, or an inquiry-reference lookup. Open the single-use invitation issued directly by Sustainable Catalyst.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<p><?php esc_html_e( 'For security, the page does not reveal whether an inquiry or email address exists.', 'sustainable-catalyst-engagement-intake' ); ?></p>
		</div>
	<?php endif; ?>

	<div class="sc-ei-portal-security-grid">
		<div><strong><?php esc_html_e( 'Passwordless', 'sustainable-catalyst-engagement-intake' ); ?></strong><span><?php esc_html_e( 'No public WordPress account or reusable password.', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div><strong><?php esc_html_e( 'Private by design', 'sustainable-catalyst-engagement-intake' ); ?></strong><span><?php esc_html_e( 'No indexing, caching, referrer sharing, or internal-review exposure.', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div><strong><?php esc_html_e( 'Revocable', 'sustainable-catalyst-engagement-intake' ); ?></strong><span><?php esc_html_e( 'Invitations and active sessions can be revoked immediately.', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
	</div>
</section>
