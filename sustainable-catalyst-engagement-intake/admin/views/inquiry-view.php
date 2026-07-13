<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$message = isset( $_GET['sc_ei_msg'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_msg'] ) ) : '';
?>
<div class="wrap sc-ei-admin">
	<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake' ) ); ?>">← <?php esc_html_e( 'Back to inquiries', 'sustainable-catalyst-engagement-intake' ); ?></a></p>

	<?php if ( 'status_updated' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Inquiry status updated.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'note_added' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Private internal note added.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'error' === $message ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'The requested update could not be completed.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php endif; ?>

	<div class="sc-ei-admin__header">
		<div>
			<p class="sc-ei-admin__eyebrow"><?php echo esc_html( $inquiry['reference'] ); ?></p>
			<h1><?php echo esc_html( $inquiry['subject'] ?: __( 'Private inquiry', 'sustainable-catalyst-engagement-intake' ) ); ?></h1>
		</div>
		<span class="sc-ei-status sc-ei-status--<?php echo esc_attr( $inquiry['status'] ); ?>">
			<?php echo esc_html( SC_EI_Statuses::label( $inquiry['status'] ) ); ?>
		</span>
	</div>

	<div class="sc-ei-admin__layout">
		<main class="sc-ei-admin__main">
			<section class="sc-ei-admin__card">
				<h2><?php esc_html_e( 'Inquiry details', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<dl class="sc-ei-admin__details">
					<dt><?php esc_html_e( 'Contact', 'sustainable-catalyst-engagement-intake' ); ?></dt>
					<dd><?php echo esc_html( $inquiry['contact_name'] ?: '—' ); ?><?php if ( $inquiry['contact_email'] ) : ?><br><a href="mailto:<?php echo esc_attr( $inquiry['contact_email'] ); ?>"><?php echo esc_html( $inquiry['contact_email'] ); ?></a><?php endif; ?></dd>

					<dt><?php esc_html_e( 'Organization and role', 'sustainable-catalyst-engagement-intake' ); ?></dt>
					<dd><?php echo esc_html( trim( $inquiry['organization'] . ( $inquiry['role_title'] ? ' · ' . $inquiry['role_title'] : '' ) ) ?: '—' ); ?></dd>

					<dt><?php esc_html_e( 'Inquiry type', 'sustainable-catalyst-engagement-intake' ); ?></dt>
					<dd><?php $types = SC_EI_Statuses::inquiry_types(); echo esc_html( $types[ $inquiry['inquiry_type'] ] ?? $inquiry['inquiry_type'] ); ?></dd>

					<dt><?php esc_html_e( 'Service interest', 'sustainable-catalyst-engagement-intake' ); ?></dt>
					<dd><?php echo esc_html( $inquiry['service_interest'] ?: '—' ); ?></dd>

					<dt><?php esc_html_e( 'Budget', 'sustainable-catalyst-engagement-intake' ); ?></dt>
					<dd><?php echo esc_html( $inquiry['budget_range'] ?: '—' ); ?></dd>

					<dt><?php esc_html_e( 'Timeline', 'sustainable-catalyst-engagement-intake' ); ?></dt>
					<dd><?php echo esc_html( trim( ( $inquiry['desired_start_date'] ?: '' ) . ( $inquiry['deadline_date'] ? ' → ' . $inquiry['deadline_date'] : '' ) ) ?: '—' ); ?></dd>

					<dt><?php esc_html_e( 'Received', 'sustainable-catalyst-engagement-intake' ); ?></dt>
					<dd><?php echo esc_html( get_date_from_gmt( $inquiry['created_at'], 'F j, Y g:i a' ) ); ?></dd>
				</dl>
			</section>

			<?php foreach ( array(
				'message'         => __( 'Message', 'sustainable-catalyst-engagement-intake' ),
				'project_summary' => __( 'Project summary', 'sustainable-catalyst-engagement-intake' ),
				'desired_outcome' => __( 'Desired outcome', 'sustainable-catalyst-engagement-intake' ),
			) as $key => $label ) : ?>
				<?php if ( ! empty( $inquiry[ $key ] ) ) : ?>
					<section class="sc-ei-admin__card">
						<h2><?php echo esc_html( $label ); ?></h2>
						<div class="sc-ei-admin__prose"><?php echo wpautop( esc_html( $inquiry[ $key ] ) ); ?></div>
					</section>
				<?php endif; ?>
			<?php endforeach; ?>

			<section class="sc-ei-admin__card">
				<h2><?php esc_html_e( 'Attachment foundation', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<?php if ( $attachments ) : ?>
					<ul>
						<?php foreach ( $attachments as $attachment ) : ?>
							<li><?php echo esc_html( $attachment['original_name'] ); ?> — <?php echo esc_html( $attachment['validation_status'] ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php else : ?>
					<p><?php esc_html_e( 'No attachment metadata exists for this inquiry. Secure upload handling is planned for v0.3.0.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				<?php endif; ?>
			</section>

			<section class="sc-ei-admin__card">
				<h2><?php esc_html_e( 'Audit history', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<?php if ( $audit_log ) : ?>
					<ol class="sc-ei-audit">
						<?php foreach ( $audit_log as $event ) : ?>
							<li>
								<strong><?php echo esc_html( ucwords( str_replace( '_', ' ', $event['event_type'] ) ) ); ?></strong>
								<span><?php echo esc_html( get_date_from_gmt( $event['created_at'], 'M j, Y g:i a' ) ); ?></span>
								<?php if ( $event['event_message'] ) : ?><p><?php echo esc_html( $event['event_message'] ); ?></p><?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ol>
				<?php else : ?>
					<p><?php esc_html_e( 'No audit events recorded.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				<?php endif; ?>
			</section>
		</main>

		<aside class="sc-ei-admin__aside">
			<?php if ( current_user_can( 'sc_intake_change_status' ) ) : ?>
				<section class="sc-ei-admin__card">
					<h2><?php esc_html_e( 'Change status', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="sc_ei_update_status">
						<input type="hidden" name="inquiry_id" value="<?php echo esc_attr( $inquiry['id'] ); ?>">
						<?php wp_nonce_field( 'sc_ei_update_status' ); ?>
						<p>
							<label for="sc-ei-status"><?php esc_html_e( 'Status', 'sustainable-catalyst-engagement-intake' ); ?></label><br>
							<select id="sc-ei-status" name="status" class="widefat">
								<?php foreach ( SC_EI_Statuses::all() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $inquiry['status'], $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</p>
						<p>
							<label for="sc-ei-status-note"><?php esc_html_e( 'Private status note', 'sustainable-catalyst-engagement-intake' ); ?></label>
							<textarea id="sc-ei-status-note" name="status_note" class="widefat" rows="4"></textarea>
						</p>
						<?php submit_button( __( 'Update status', 'sustainable-catalyst-engagement-intake' ), 'primary', 'submit', false ); ?>
					</form>
				</section>
			<?php endif; ?>

			<?php if ( current_user_can( 'sc_intake_add_notes' ) ) : ?>
				<section class="sc-ei-admin__card">
					<h2><?php esc_html_e( 'Private internal note', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Never shown to the sender.', 'sustainable-catalyst-engagement-intake' ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="sc_ei_add_note">
						<input type="hidden" name="inquiry_id" value="<?php echo esc_attr( $inquiry['id'] ); ?>">
						<?php wp_nonce_field( 'sc_ei_add_note' ); ?>
						<textarea name="internal_note" class="widefat" rows="6" required></textarea>
						<p><?php submit_button( __( 'Add private note', 'sustainable-catalyst-engagement-intake' ), 'secondary', 'submit', false ); ?></p>
					</form>
				</section>
			<?php endif; ?>
		</aside>
	</div>
</div>
