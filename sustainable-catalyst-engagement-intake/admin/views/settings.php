<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap sc-ei-admin">
	<h1><?php esc_html_e( 'Engagement Intake Settings', 'sustainable-catalyst-engagement-intake' ); ?></h1>
	<p><?php esc_html_e( 'v0.3.1 stabilizes protected document intake with atomic storage commits, request-envelope checks, server-aware limits, reconciliation, storage probes, retention previews, explicit cache bypass headers, and v0.3.0 workflow capabilities.', 'sustainable-catalyst-engagement-intake' ); ?></p>

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
			<h2><?php esc_html_e( 'Secure document intake', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><?php esc_html_e( 'Files are stored outside the public Media Library with randomized internal names. The configured path should be outside the public web root whenever hosting permits.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="sc-ei-upload-max-files"><?php esc_html_e( 'Maximum files per inquiry', 'sustainable-catalyst-engagement-intake' ); ?></label></th>
					<td><input id="sc-ei-upload-max-files" type="number" min="1" max="10" name="sc_ei_settings[upload_max_files]" value="<?php echo esc_attr( $settings['upload_max_files'] ); ?>"></td>
				</tr>
				<tr>
					<th scope="row"><label for="sc-ei-upload-max-mb"><?php esc_html_e( 'Maximum size per file', 'sustainable-catalyst-engagement-intake' ); ?></label></th>
					<td><input id="sc-ei-upload-max-mb" type="number" min="1" max="100" name="sc_ei_settings[upload_max_file_mb]" value="<?php echo esc_attr( $settings['upload_max_file_mb'] ); ?>"> <?php esc_html_e( 'MB', 'sustainable-catalyst-engagement-intake' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Allowed extensions', 'sustainable-catalyst-engagement-intake' ); ?></th>
					<td>
						<fieldset>
							<?php foreach ( SC_EI_Upload_Validator::supported_extensions() as $extension => $label ) : ?>
								<label class="sc-ei-settings-check">
									<input type="checkbox" name="sc_ei_settings[allowed_upload_extensions][]" value="<?php echo esc_attr( $extension ); ?>" <?php checked( in_array( $extension, (array) $settings['allowed_upload_extensions'], true ) ); ?>>
									<?php echo esc_html( strtoupper( $extension ) . ' — ' . $label ); ?>
								</label><br>
							<?php endforeach; ?>
						</fieldset>
						<p class="description"><?php esc_html_e( 'Executable files, archives, macros, encrypted documents, active-content PDFs, and extension/MIME mismatches remain blocked regardless of this selection.', 'sustainable-catalyst-engagement-intake' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="sc-ei-attachment-retention"><?php esc_html_e( 'Default attachment retention', 'sustainable-catalyst-engagement-intake' ); ?></label></th>
					<td><input id="sc-ei-attachment-retention" type="number" min="7" max="3650" name="sc_ei_settings[attachment_retention_days]" value="<?php echo esc_attr( $settings['attachment_retention_days'] ); ?>"> <?php esc_html_e( 'days', 'sustainable-catalyst-engagement-intake' ); ?></td>
				</tr>
				<tr>
					<th scope="row"><label for="sc-ei-private-storage"><?php esc_html_e( 'Private storage path', 'sustainable-catalyst-engagement-intake' ); ?></label></th>
					<td>
						<?php $sc_ei_locked_storage_path = (string) get_option( 'sc_ei_storage_base_dir', '' ); ?>
						<input id="sc-ei-private-storage" type="text" class="large-text code" name="sc_ei_settings[private_storage_path]" value="<?php echo esc_attr( $sc_ei_locked_storage_path ?: $settings['private_storage_path'] ); ?>" placeholder="<?php echo esc_attr( dirname( ABSPATH ) . '/sc-engagement-intake-private' ); ?>" <?php echo $sc_ei_locked_storage_path ? 'readonly' : ''; ?>>
						<p class="description"><?php esc_html_e( 'Optional absolute server path. Leave empty for automatic selection. The selected path is locked when the first accepted document is stored so existing files do not become orphaned. SC_EI_PRIVATE_STORAGE_PATH overrides the lock and should only be changed with a deliberate storage migration.', 'sustainable-catalyst-engagement-intake' ); ?></p>
						<?php if ( get_option( 'sc_ei_storage_base_dir', '' ) ) : ?>
							<p><strong><?php esc_html_e( 'Locked effective path:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <code><?php echo esc_html( get_option( 'sc_ei_storage_base_dir', '' ) ); ?></code></p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'External scanner requirement', 'sustainable-catalyst-engagement-intake' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="sc_ei_settings[require_external_scanner]" value="1" <?php checked( $settings['require_external_scanner'], 1 ); ?>>
							<?php esc_html_e( 'Reject and delete uploads unless an integrated scanner reports them clean.', 'sustainable-catalyst-engagement-intake' ); ?>
						</label>
						<p class="description"><?php esc_html_e( 'Leave off until a scanner integration uses the sc_ei_scan_attachment and sc_ei_scanner_probe filters.', 'sustainable-catalyst-engagement-intake' ); ?></p>
					</td>
				</tr>
			</table>
		</section>

		<section class="sc-ei-admin__card sc-ei-admin__settings-card">
			<h2><?php esc_html_e( 'Microsoft Teams scheduling readiness', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><?php esc_html_e( 'Microsoft Teams is the only supported live meeting platform. v0.3.1 stores preferences and approved meeting records; Microsoft Graph event creation is not enabled yet.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="sc-ei-teams-organizer"><?php esc_html_e( 'Teams organizer email', 'sustainable-catalyst-engagement-intake' ); ?></label></th>
					<td>
						<input id="sc-ei-teams-organizer" type="email" class="regular-text" name="sc_ei_settings[teams_organizer_email]" value="<?php echo esc_attr( $settings['teams_organizer_email'] ); ?>">
						<p class="description"><?php esc_html_e( 'Optional administrative organizer identity for future Microsoft Graph integration.', 'sustainable-catalyst-engagement-intake' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="sc-ei-default-teams-duration"><?php esc_html_e( 'Default Teams duration', 'sustainable-catalyst-engagement-intake' ); ?></label></th>
					<td>
						<select id="sc-ei-default-teams-duration" name="sc_ei_settings[default_teams_duration]">
							<?php foreach ( array( 20, 30, 45, 60, 90 ) as $minutes ) : ?>
								<option value="<?php echo esc_attr( $minutes ); ?>" <?php selected( $settings['default_teams_duration'], $minutes ); ?>><?php echo esc_html( sprintf( __( '%d minutes', 'sustainable-catalyst-engagement-intake' ), $minutes ) ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>
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
