<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap sc-ei-admin">
	<h1><?php esc_html_e( 'Engagement Intake Settings', 'sustainable-catalyst-engagement-intake' ); ?></h1>
	<p><?php esc_html_e( 'v0.2.0 keeps conservative data-preservation defaults while adding configurable public-form timing and rate-limit controls. Automated retention processing arrives in a later release.', 'sustainable-catalyst-engagement-intake' ); ?></p>

	<form method="post" action="options.php">
		<?php settings_fields( 'sc_ei_settings_group' ); ?>

		<section class="sc-ei-admin__card sc-ei-admin__settings-card">
			<h2><?php esc_html_e( 'Retention defaults', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="sc-ei-unaccepted-days"><?php esc_html_e( 'Unaccepted inquiries', 'sustainable-catalyst-engagement-intake' ); ?></label></th>
					<td><input id="sc-ei-unaccepted-days" type="number" min="30" max="3650" name="sc_ei_settings[default_unaccepted_retention_days]" value="<?php echo esc_attr( $settings['default_unaccepted_retention_days'] ); ?>"> <?php esc_html_e( 'days', 'sustainable-catalyst-engagement-intake' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="sc-ei-withdrawn-days"><?php esc_html_e( 'Withdrawn inquiries', 'sustainable-catalyst-engagement-intake' ); ?></label></th>
					<td><input id="sc-ei-withdrawn-days" type="number" min="1" max="365" name="sc_ei_settings[withdrawn_retention_days]" value="<?php echo esc_attr( $settings['withdrawn_retention_days'] ); ?>"> <?php esc_html_e( 'days', 'sustainable-catalyst-engagement-intake' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="sc-ei-draft-days"><?php esc_html_e( 'Abandoned drafts', 'sustainable-catalyst-engagement-intake' ); ?></label></th>
					<td><input id="sc-ei-draft-days" type="number" min="1" max="365" name="sc_ei_settings[abandoned_draft_days]" value="<?php echo esc_attr( $settings['abandoned_draft_days'] ); ?>"> <?php esc_html_e( 'days', 'sustainable-catalyst-engagement-intake' ); ?></td>
				</tr>
			</table>
		</section>

		<section class="sc-ei-admin__card sc-ei-admin__settings-card">
			<h2><?php esc_html_e( 'Public form controls', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="sc-ei-minimum-seconds"><?php esc_html_e( 'Minimum completion time', 'sustainable-catalyst-engagement-intake' ); ?></label></th>
					<td><input id="sc-ei-minimum-seconds" type="number" min="1" max="30" name="sc_ei_settings[minimum_completion_seconds]" value="<?php echo esc_attr( $settings['minimum_completion_seconds'] ); ?>"> <?php esc_html_e( 'seconds', 'sustainable-catalyst-engagement-intake' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="sc-ei-submissions-hour"><?php esc_html_e( 'Maximum submissions per email', 'sustainable-catalyst-engagement-intake' ); ?></label></th>
					<td><input id="sc-ei-submissions-hour" type="number" min="1" max="20" name="sc_ei_settings[submissions_per_hour]" value="<?php echo esc_attr( $settings['submissions_per_hour'] ); ?>"> <?php esc_html_e( 'per hour', 'sustainable-catalyst-engagement-intake' ); ?></td>
				</tr>
			</table>
			<p class="description"><?php esc_html_e( 'The public forms also use a nonce, hidden honeypot, signed form timing, duplicate detection, field limits, and conditional server-side validation.', 'sustainable-catalyst-engagement-intake' ); ?></p>
		</section>

		<section class="sc-ei-admin__card sc-ei-admin__settings-card">
			<h2><?php esc_html_e( 'Uninstall behavior', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<label>
				<input type="checkbox" name="sc_ei_settings[delete_data_on_uninstall]" value="1" <?php checked( $settings['delete_data_on_uninstall'], 1 ); ?>>
				<?php esc_html_e( 'Permanently delete all inquiry, attachment metadata, audit records, roles, and plugin settings when the plugin is uninstalled.', 'sustainable-catalyst-engagement-intake' ); ?>
			</label>
			<p class="description"><strong><?php esc_html_e( 'Default: off.', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'Deactivation never deletes private records.', 'sustainable-catalyst-engagement-intake' ); ?></p>
		</section>

		<?php submit_button(); ?>
	</form>
</div>
