<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$message = isset( $_GET['sc_ei_msg'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_msg'] ) ) : '';

$contact_methods    = SC_EI_Teams::contact_methods();
$meeting_requests   = SC_EI_Teams::meeting_requests();
$scheduling_statuses= SC_EI_Teams::scheduling_statuses();
$duration_options   = SC_EI_Teams::duration_options();
$weekday_options    = SC_EI_Teams::weekdays();

$preferred_weekdays = json_decode( (string) ( $inquiry['preferred_weekdays'] ?? '[]' ), true );
$preferred_weekdays = is_array( $preferred_weekdays ) ? $preferred_weekdays : array();
$weekday_labels     = array();
foreach ( $preferred_weekdays as $weekday ) {
	if ( isset( $weekday_options[ $weekday ] ) ) {
		$weekday_labels[] = $weekday_options[ $weekday ];
	}
}

$participant_emails = json_decode( (string) ( $inquiry['participant_emails'] ?? '[]' ), true );
$participant_emails = is_array( $participant_emails ) ? $participant_emails : array();

$guidance_flags = json_decode( (string) ( $inquiry['guidance_flags'] ?? '[]' ), true );
$guidance_flags = is_array( $guidance_flags ) ? $guidance_flags : array();
$metadata       = json_decode( (string) ( $inquiry['metadata_json'] ?? '{}' ), true );
$metadata       = is_array( $metadata ) ? $metadata : array();
$variants       = SC_EI_Conversion::variants();
$sources        = SC_EI_Conversion::sources();
$service_labels = array_merge( SC_EI_Form_Schema::service_interests(), SC_EI_Form_Schema::compact_service_interests() );
$budget_labels  = array_merge( SC_EI_Form_Schema::budget_ranges(), SC_EI_Form_Schema::compact_budget_ranges() );

$display_timezone = $inquiry['scheduled_timezone'] ?: $inquiry['timezone'];
if ( ! SC_EI_Teams::valid_timezone( $display_timezone ) ) {
	$display_timezone = wp_timezone_string();
}
if ( ! SC_EI_Teams::valid_timezone( $display_timezone ) ) {
	$display_timezone = 'UTC';
}

$scheduled_start_input = SC_EI_Teams::format_utc_for_input( $inquiry['scheduled_start_utc'] ?? null, $display_timezone );
$scheduled_end_input   = SC_EI_Teams::format_utc_for_input( $inquiry['scheduled_end_utc'] ?? null, $display_timezone );
?>
<div class="wrap sc-ei-admin">
	<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake' ) ); ?>">← <?php esc_html_e( 'Back to inquiries', 'sustainable-catalyst-engagement-intake' ); ?></a></p>

	<?php if ( 'status_updated' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Inquiry status updated.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'note_added' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Private internal note added.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'scheduling_updated' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Microsoft Teams scheduling record updated.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'scheduling_error' === $message ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'The Teams scheduling update was rejected. Check the status, time zone, meeting URL, and meeting times.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'error' === $message ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'The requested update could not be completed.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php endif; ?>

	<div class="sc-ei-admin__header">
		<div>
			<p class="sc-ei-admin__eyebrow"><?php echo esc_html( $inquiry['reference'] ); ?></p>
			<h1><?php echo esc_html( $inquiry['subject'] ?: __( 'Private inquiry', 'sustainable-catalyst-engagement-intake' ) ); ?></h1>
		</div>
		<div class="sc-ei-admin__status-stack">
			<span class="sc-ei-status sc-ei-status--<?php echo esc_attr( $inquiry['status'] ); ?>">
				<?php echo esc_html( SC_EI_Statuses::label( $inquiry['status'] ) ); ?>
			</span>
			<span class="sc-ei-status sc-ei-status--teams-<?php echo esc_attr( $inquiry['scheduling_status'] ); ?>">
				<?php echo esc_html( SC_EI_Teams::label( $scheduling_statuses, $inquiry['scheduling_status'] ) ); ?>
			</span>
		</div>
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
					<dd><?php echo esc_html( $inquiry['service_interest'] ? ( $service_labels[ $inquiry['service_interest'] ] ?? ucwords( str_replace( '_', ' ', $inquiry['service_interest'] ) ) ) : '—' ); ?></dd>

					<dt><?php esc_html_e( 'Budget', 'sustainable-catalyst-engagement-intake' ); ?></dt>
					<dd><?php echo esc_html( $inquiry['budget_range'] ? ( $budget_labels[ $inquiry['budget_range'] ] ?? ucwords( str_replace( '_', ' ', $inquiry['budget_range'] ) ) ) : '—' ); ?></dd>

					<dt><?php esc_html_e( 'Project timeline', 'sustainable-catalyst-engagement-intake' ); ?></dt>
					<dd><?php echo esc_html( trim( ( $inquiry['desired_start_date'] ?: '' ) . ( $inquiry['deadline_date'] ? ' → ' . $inquiry['deadline_date'] : '' ) ) ?: '—' ); ?></dd>

					<dt><?php esc_html_e( 'Received', 'sustainable-catalyst-engagement-intake' ); ?></dt>
					<dd><?php echo esc_html( get_date_from_gmt( $inquiry['created_at'], 'F j, Y g:i a' ) ); ?></dd>
				</dl>
			</section>

			<section class="sc-ei-admin__card sc-ei-admin__card--conversion">
				<p class="sc-ei-admin__card-kicker sc-ei-admin__card-kicker--conversion"><?php esc_html_e( 'Conversion Routing', 'sustainable-catalyst-engagement-intake' ); ?></p>
				<h2><?php esc_html_e( 'Intake Experience and Origin', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<dl class="sc-ei-admin__details">
					<dt><?php esc_html_e( 'Form experience', 'sustainable-catalyst-engagement-intake' ); ?></dt>
					<dd><?php echo esc_html( SC_EI_Conversion::label( $variants, $inquiry['form_variant'] ?? 'advanced' ) ); ?></dd>

					<dt><?php esc_html_e( 'Source page', 'sustainable-catalyst-engagement-intake' ); ?></dt>
					<dd><?php echo esc_html( SC_EI_Conversion::label( $sources, $inquiry['source_page'] ?? 'other' ) ); ?></dd>

					<dt><?php esc_html_e( 'Entry CTA', 'sustainable-catalyst-engagement-intake' ); ?></dt>
					<dd><?php echo esc_html( $inquiry['entry_cta'] ? ucwords( str_replace( '-', ' ', $inquiry['entry_cta'] ) ) : '—' ); ?></dd>

					<dt><?php esc_html_e( 'Conversion route', 'sustainable-catalyst-engagement-intake' ); ?></dt>
					<dd><?php echo esc_html( $inquiry['conversion_route'] ? ucwords( str_replace( '_', ' ', $inquiry['conversion_route'] ) ) : '—' ); ?></dd>

					<dt><?php esc_html_e( 'Referring form URL', 'sustainable-catalyst-engagement-intake' ); ?></dt>
					<dd>
						<?php if ( ! empty( $metadata['source_url'] ) ) : ?>
							<a href="<?php echo esc_url( $metadata['source_url'] ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $metadata['source_url'] ); ?></a>
						<?php else : ?>
							—
						<?php endif; ?>
					</dd>
				</dl>

				<?php if ( $guidance_flags ) : ?>
					<div class="sc-ei-admin__guidance-flags">
						<strong><?php esc_html_e( 'Non-blocking guidance flags', 'sustainable-catalyst-engagement-intake' ); ?></strong>
						<ul>
							<?php foreach ( $guidance_flags as $flag ) : ?>
								<li><?php echo esc_html( ucwords( str_replace( '_', ' ', $flag ) ) ); ?></li>
							<?php endforeach; ?>
						</ul>
						<p class="description"><?php esc_html_e( 'These flags record guidance shown or implied by the selected service and budget. They do not approve, reject, or score the inquiry.', 'sustainable-catalyst-engagement-intake' ); ?></p>
					</div>
				<?php endif; ?>
			</section>

			<section class="sc-ei-admin__card sc-ei-admin__card--teams">
				<p class="sc-ei-admin__card-kicker"><?php esc_html_e( 'Communication', 'sustainable-catalyst-engagement-intake' ); ?></p>
				<h2><?php esc_html_e( 'Response and Microsoft Teams Preferences', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<dl class="sc-ei-admin__details">
					<dt><?php esc_html_e( 'Preferred response', 'sustainable-catalyst-engagement-intake' ); ?></dt>
					<dd><?php echo esc_html( SC_EI_Teams::label( $contact_methods, $inquiry['preferred_contact_method'] ) ); ?></dd>

					<dt><?php esc_html_e( 'Teams email', 'sustainable-catalyst-engagement-intake' ); ?></dt>
					<dd><?php if ( $inquiry['teams_email'] ) : ?><a href="mailto:<?php echo esc_attr( $inquiry['teams_email'] ); ?>"><?php echo esc_html( $inquiry['teams_email'] ); ?></a><?php else : ?>—<?php endif; ?></dd>

					<dt><?php esc_html_e( 'Phone', 'sustainable-catalyst-engagement-intake' ); ?></dt>
					<dd><?php echo esc_html( $inquiry['phone_number'] ?: '—' ); ?></dd>

					<dt><?php esc_html_e( 'Meeting request', 'sustainable-catalyst-engagement-intake' ); ?></dt>
					<dd><?php echo esc_html( SC_EI_Teams::label( $meeting_requests, $inquiry['meeting_request'] ) ); ?></dd>

					<dt><?php esc_html_e( 'Time zone', 'sustainable-catalyst-engagement-intake' ); ?></dt>
					<dd><?php echo esc_html( $inquiry['timezone'] ?: '—' ); ?></dd>

					<dt><?php esc_html_e( 'Location', 'sustainable-catalyst-engagement-intake' ); ?></dt>
					<dd><?php echo esc_html( trim( $inquiry['city'] . ( $inquiry['country'] ? ', ' . $inquiry['country'] : '' ) ) ?: '—' ); ?></dd>

					<dt><?php esc_html_e( 'Preferred weekdays', 'sustainable-catalyst-engagement-intake' ); ?></dt>
					<dd><?php echo esc_html( $weekday_labels ? implode( ', ', $weekday_labels ) : '—' ); ?></dd>

					<dt><?php esc_html_e( 'Preferred time windows', 'sustainable-catalyst-engagement-intake' ); ?></dt>
					<dd><?php echo $inquiry['preferred_time_windows'] ? nl2br( esc_html( $inquiry['preferred_time_windows'] ) ) : '—'; ?></dd>

					<dt><?php esc_html_e( 'Preferred duration', 'sustainable-catalyst-engagement-intake' ); ?></dt>
					<dd><?php echo esc_html( SC_EI_Teams::label( $duration_options, (string) $inquiry['preferred_duration'] ) ); ?></dd>

					<dt><?php esc_html_e( 'Participants', 'sustainable-catalyst-engagement-intake' ); ?></dt>
					<dd><?php echo esc_html( (string) $inquiry['participant_count'] ); ?><?php if ( $participant_emails ) : ?><br><?php echo esc_html( implode( ', ', $participant_emails ) ); ?><?php endif; ?></dd>

					<dt><?php esc_html_e( 'Calendar consent', 'sustainable-catalyst-engagement-intake' ); ?></dt>
					<dd><?php echo $inquiry['calendar_invite_consent'] ? esc_html__( 'Granted', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'Not granted', 'sustainable-catalyst-engagement-intake' ); ?></dd>
				</dl>

				<?php if ( $inquiry['accessibility_needs'] ) : ?>
					<div class="sc-ei-admin__sensitive">
						<strong><?php esc_html_e( 'Private accessibility or accommodation information', 'sustainable-catalyst-engagement-intake' ); ?></strong>
						<p><?php echo nl2br( esc_html( $inquiry['accessibility_needs'] ) ); ?></p>
					</div>
				<?php endif; ?>

				<?php if ( $inquiry['scheduling_notes'] ) : ?>
					<h3><?php esc_html_e( 'Sender scheduling notes', 'sustainable-catalyst-engagement-intake' ); ?></h3>
					<div class="sc-ei-admin__prose"><?php echo wpautop( esc_html( $inquiry['scheduling_notes'] ) ); ?></div>
				<?php endif; ?>
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
			<?php if ( current_user_can( 'sc_intake_review' ) ) : ?>
				<section class="sc-ei-admin__card sc-ei-admin__card--teams">
					<p class="sc-ei-admin__card-kicker"><?php esc_html_e( 'Microsoft Teams', 'sustainable-catalyst-engagement-intake' ); ?></p>
					<h2><?php esc_html_e( 'Scheduling Record', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<p class="description"><?php esc_html_e( 'This release records approved Teams scheduling details. It does not yet create the event through Microsoft Graph.', 'sustainable-catalyst-engagement-intake' ); ?></p>

					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="sc_ei_update_scheduling">
						<input type="hidden" name="inquiry_id" value="<?php echo esc_attr( $inquiry['id'] ); ?>">
						<?php wp_nonce_field( 'sc_ei_update_scheduling' ); ?>

						<p>
							<label for="sc-ei-scheduling-status"><strong><?php esc_html_e( 'Scheduling status', 'sustainable-catalyst-engagement-intake' ); ?></strong></label>
							<select id="sc-ei-scheduling-status" name="scheduling_status" class="widefat">
								<?php foreach ( $scheduling_statuses as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $inquiry['scheduling_status'], $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</p>

						<p>
							<label for="sc-ei-teams-url"><strong><?php esc_html_e( 'Microsoft Teams meeting URL', 'sustainable-catalyst-engagement-intake' ); ?></strong></label>
							<input id="sc-ei-teams-url" type="url" name="teams_meeting_url" class="widefat" value="<?php echo esc_attr( $inquiry['teams_meeting_url'] ); ?>" placeholder="https://teams.microsoft.com/l/meetup-join/...">
						</p>

						<p>
							<label for="sc-ei-scheduled-timezone"><strong><?php esc_html_e( 'Meeting time zone', 'sustainable-catalyst-engagement-intake' ); ?></strong></label>
							<input id="sc-ei-scheduled-timezone" type="text" name="scheduled_timezone" class="widefat" value="<?php echo esc_attr( $display_timezone ); ?>" list="sc-ei-admin-timezones">
							<datalist id="sc-ei-admin-timezones">
								<?php foreach ( SC_EI_Teams::timezone_identifiers() as $timezone_id ) : ?>
									<option value="<?php echo esc_attr( $timezone_id ); ?>"></option>
								<?php endforeach; ?>
							</datalist>
						</p>

						<p>
							<label for="sc-ei-start-local"><strong><?php esc_html_e( 'Start', 'sustainable-catalyst-engagement-intake' ); ?></strong></label>
							<input id="sc-ei-start-local" type="datetime-local" name="scheduled_start_local" class="widefat" value="<?php echo esc_attr( $scheduled_start_input ); ?>">
						</p>

						<p>
							<label for="sc-ei-end-local"><strong><?php esc_html_e( 'End', 'sustainable-catalyst-engagement-intake' ); ?></strong></label>
							<input id="sc-ei-end-local" type="datetime-local" name="scheduled_end_local" class="widefat" value="<?php echo esc_attr( $scheduled_end_input ); ?>">
						</p>

						<p>
							<label for="sc-ei-calendar-event"><strong><?php esc_html_e( 'Calendar event ID', 'sustainable-catalyst-engagement-intake' ); ?></strong></label>
							<input id="sc-ei-calendar-event" type="text" name="calendar_event_id" class="widefat" value="<?php echo esc_attr( $inquiry['calendar_event_id'] ); ?>">
						</p>

						<p>
							<label for="sc-ei-scheduling-note"><strong><?php esc_html_e( 'Private scheduling note', 'sustainable-catalyst-engagement-intake' ); ?></strong></label>
							<textarea id="sc-ei-scheduling-note" name="scheduling_admin_note" class="widefat" rows="4"></textarea>
						</p>

						<?php submit_button( __( 'Update Teams Scheduling', 'sustainable-catalyst-engagement-intake' ), 'primary', 'submit', false ); ?>
					</form>

					<?php if ( $inquiry['teams_meeting_url'] ) : ?>
						<p><a class="button button-secondary" href="<?php echo esc_url( $inquiry['teams_meeting_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Open Teams Meeting', 'sustainable-catalyst-engagement-intake' ); ?></a></p>
					<?php endif; ?>
				</section>
			<?php endif; ?>

			<?php if ( current_user_can( 'sc_intake_change_status' ) ) : ?>
				<section class="sc-ei-admin__card">
					<h2><?php esc_html_e( 'Change inquiry status', 'sustainable-catalyst-engagement-intake' ); ?></h2>
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
						<?php submit_button( __( 'Update Inquiry Status', 'sustainable-catalyst-engagement-intake' ), 'secondary', 'submit', false ); ?>
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
