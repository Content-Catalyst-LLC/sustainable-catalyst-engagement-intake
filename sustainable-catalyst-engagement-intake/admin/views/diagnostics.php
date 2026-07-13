<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$message = isset( $_GET['sc_ei_msg'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_msg'] ) ) : '';
$reconciliation = $diagnostics['reconciliation'];
$recon_counts    = (array) ( $reconciliation['counts'] ?? array() );
$recon_issues    = (array) ( $reconciliation['issues'] ?? array() );
$retention       = $diagnostics['retention_preview'];
$retention_run   = $diagnostics['retention_run'];
$probe           = $diagnostics['storage_probe'];
$environment     = $diagnostics['environment'];
$effective       = $diagnostics['effective_limits'];
?>
<div class="wrap sc-ei-admin">
	<h1><?php esc_html_e( 'Engagement Intake Production Diagnostics', 'sustainable-catalyst-engagement-intake' ); ?></h1>
	<p><?php esc_html_e( 'Verify protected storage, PHP upload limits, cache bypass, database migrations, file integrity, reconciliation, and retention behavior before relying on production document intake.', 'sustainable-catalyst-engagement-intake' ); ?></p>

	<?php if ( 'storage_probe_passed' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Protected storage write, read, rename, and delete probe passed.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'storage_probe_failed' === $message ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'The protected storage probe failed. Review path, ownership, permissions, free space, and hosting restrictions.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'storage_repaired' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Storage directories, protection files, permissions, stale staging files, and the storage probe were repaired successfully.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'storage_repair_failed' === $message ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Storage repair did not complete successfully. No attachment records were deleted.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'reconciliation_clean' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Database-to-filesystem reconciliation completed without detected inconsistencies.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'reconciliation_attention' === $message ) : ?>
		<div class="notice notice-warning is-dismissible"><p><?php esc_html_e( 'Reconciliation found missing, altered, misplaced, unresolvable, or orphaned files. The scan was read-only; review the report below.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'retention_preview_ready' === $message ) : ?>
		<div class="notice notice-info is-dismissible"><p><?php esc_html_e( 'Retention cleanup preview generated. No files were deleted.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'retention_cleanup_completed' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Expired attachment cleanup completed. Review the run summary below.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'retention_confirmation_failed' === $message ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Retention cleanup was not run because the confirmation phrase did not match.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php endif; ?>

	<div class="sc-ei-health sc-ei-health--<?php echo esc_attr( $status ); ?>">
		<strong><?php echo esc_html( 'healthy' === $status ? __( 'Production intake healthy', 'sustainable-catalyst-engagement-intake' ) : __( 'Production attention required', 'sustainable-catalyst-engagement-intake' ) ); ?></strong>
		<span><?php echo esc_html( sprintf( __( 'Plugin %1$s · Database %2$s · Validator %3$s', 'sustainable-catalyst-engagement-intake' ), $diagnostics['plugin_version'], $diagnostics['database_version'] ?: 'not recorded', $diagnostics['validator_version'] ) ); ?></span>
	</div>

	<div class="sc-ei-diagnostics-grid sc-ei-diagnostics-grid--production">
		<section class="sc-ei-admin__card sc-ei-admin__card--wide">
			<p class="sc-ei-admin__card-kicker sc-ei-admin__card-kicker--documents"><?php esc_html_e( 'Protected Storage', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<h2><?php esc_html_e( 'Storage Health and Repair', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><code><?php echo esc_html( $diagnostics['storage']['path'] ); ?></code></p>
			<div class="sc-ei-diagnostic-metrics">
				<div><strong><?php echo esc_html( number_format_i18n( $diagnostics['storage']['managed_files'] ) ); ?></strong><span><?php esc_html_e( 'managed files', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
				<div><strong><?php echo esc_html( size_format( $diagnostics['storage']['managed_bytes'], 2 ) ); ?></strong><span><?php esc_html_e( 'managed storage', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
				<div><strong><?php echo esc_html( size_format( $diagnostics['storage']['disk_free_bytes'], 2 ) ); ?></strong><span><?php esc_html_e( 'free disk space', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
				<div><strong><?php echo esc_html( $diagnostics['storage']['base_permissions'] ?: 'unknown' ); ?></strong><span><?php esc_html_e( 'base permissions', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
			</div>

			<ul class="sc-ei-checks sc-ei-checks--columns">
				<?php foreach ( array(
					'exists'                => $diagnostics['storage']['exists'],
					'writable'              => $diagnostics['storage']['writable'],
					'private marker'         => $diagnostics['storage']['marker'],
					'protection files'       => $diagnostics['storage']['protection_files'],
					'quarantine writable'    => $diagnostics['storage']['quarantine_writable'],
					'approved writable'      => $diagnostics['storage']['approved_writable'],
					'outside document root'  => $diagnostics['storage']['outside_document_root'],
					'not a symbolic link'    => ! $diagnostics['storage']['base_is_symlink'],
				) as $label => $ok ) : ?>
					<li><span class="<?php echo $ok ? 'sc-ei-check--ok' : 'sc-ei-check--bad'; ?>">●</span> <?php echo esc_html( $label ); ?></li>
				<?php endforeach; ?>
			</ul>

			<?php if ( ! $diagnostics['storage']['outside_document_root'] ) : ?>
				<div class="sc-ei-diagnostic-warning"><strong><?php esc_html_e( 'Action required:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'Move the locked private path outside the server document root through a deliberate migration. Apache deny files are defense in depth, not a reliable Nginx or CDN boundary.', 'sustainable-catalyst-engagement-intake' ); ?></div>
			<?php endif; ?>
			<?php if ( $diagnostics['storage']['base_is_symlink'] ) : ?>
				<div class="sc-ei-diagnostic-warning"><strong><?php esc_html_e( 'Review required:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'The storage base is a symbolic link. Confirm the target is private, stable, backed up appropriately, and not publicly served.', 'sustainable-catalyst-engagement-intake' ); ?></div>
			<?php endif; ?>

			<div class="sc-ei-diagnostic-actions">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="sc_ei_run_storage_probe">
					<?php wp_nonce_field( 'sc_ei_run_storage_probe' ); ?>
					<button type="submit" class="button button-secondary"><?php esc_html_e( 'Run Storage Probe', 'sustainable-catalyst-engagement-intake' ); ?></button>
				</form>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('<?php echo esc_js( __( 'Repair protection files, directory permissions, and stale staging files?', 'sustainable-catalyst-engagement-intake' ) ); ?>');">
					<input type="hidden" name="action" value="sc_ei_repair_storage">
					<?php wp_nonce_field( 'sc_ei_repair_storage' ); ?>
					<button type="submit" class="button"><?php esc_html_e( 'Repair Storage Protections', 'sustainable-catalyst-engagement-intake' ); ?></button>
				</form>
			</div>

			<?php if ( $probe ) : ?>
				<p class="description">
					<?php echo esc_html( sprintf(
						__( 'Last probe: %1$s · write %2$s · read %3$s · rename %4$s · delete %5$s · %6$s', 'sustainable-catalyst-engagement-intake' ),
						$probe['completed_at'] ?? 'unknown',
						! empty( $probe['write'] ) ? 'pass' : 'fail',
						! empty( $probe['read'] ) ? 'pass' : 'fail',
						! empty( $probe['rename'] ) ? 'pass' : 'fail',
						! empty( $probe['delete'] ) ? 'pass' : 'fail',
						$probe['message'] ?? ''
					) ); ?>
				</p>
			<?php endif; ?>
		</section>

		<section class="sc-ei-admin__card">
			<h2><?php esc_html_e( 'PHP Upload Envelope', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<ul class="sc-ei-checks">
				<li><span class="<?php echo $environment['file_uploads_enabled'] ? 'sc-ei-check--ok' : 'sc-ei-check--bad'; ?>">●</span> <?php esc_html_e( 'PHP file uploads enabled', 'sustainable-catalyst-engagement-intake' ); ?></li>
				<li><span class="<?php echo $environment['temporary_exists'] ? 'sc-ei-check--ok' : 'sc-ei-check--bad'; ?>">●</span> <?php esc_html_e( 'temporary directory exists', 'sustainable-catalyst-engagement-intake' ); ?></li>
				<li><span class="<?php echo $environment['temporary_writable'] ? 'sc-ei-check--ok' : 'sc-ei-check--bad'; ?>">●</span> <?php esc_html_e( 'temporary directory writable', 'sustainable-catalyst-engagement-intake' ); ?></li>
				<li><span class="<?php echo $diagnostics['uploads']['finfo_available'] ? 'sc-ei-check--ok' : 'sc-ei-check--bad'; ?>">●</span> PHP Fileinfo</li>
				<li><span class="<?php echo $diagnostics['uploads']['ziparchive_available'] ? 'sc-ei-check--ok' : 'sc-ei-check--bad'; ?>">●</span> PHP ZipArchive</li>
			</ul>
			<dl class="sc-ei-admin__details">
				<dt>upload_max_filesize</dt><dd><?php echo esc_html( size_format( $environment['upload_max_bytes'], 2 ) ); ?></dd>
				<dt>post_max_size</dt><dd><?php echo esc_html( size_format( $environment['post_max_bytes'], 2 ) ); ?></dd>
				<dt>max_file_uploads</dt><dd><?php echo esc_html( $environment['max_file_uploads'] ); ?></dd>
				<dt><?php esc_html_e( 'Temporary directory', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><code><?php echo esc_html( $environment['temporary_directory'] ); ?></code></dd>
				<dt><?php esc_html_e( 'Effective plugin limit', 'sustainable-catalyst-engagement-intake' ); ?></dt>
				<dd><?php echo esc_html( sprintf( '%1$d files · %2$s each · %3$s combined', $effective['max_files'], size_format( $effective['max_file_bytes'], 1 ), size_format( $effective['max_total_bytes'], 1 ) ) ); ?></dd>
			</dl>
			<?php if ( ! $diagnostics['uploads']['ziparchive_available'] && preg_match( '/\b(?:docx|xlsx)\b/', $diagnostics['uploads']['allowed_extensions'] ) ) : ?>
				<div class="sc-ei-diagnostic-warning"><?php esc_html_e( 'DOCX/XLSX are enabled but PHP ZipArchive is unavailable. Disable those formats or enable ZipArchive before production use.', 'sustainable-catalyst-engagement-intake' ); ?></div>
			<?php endif; ?>
		</section>

		<section class="sc-ei-admin__card">
			<h2><?php esc_html_e( 'Cache and CDN Bypass', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><?php esc_html_e( 'Form pages and the submission endpoint issue explicit browser, proxy, CDN, Cloudflare, and surrogate no-store headers in addition to WordPress no-cache controls.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<dl class="sc-ei-admin__details">
				<?php foreach ( $diagnostics['cache_headers'] as $header => $value ) : ?>
					<dt><?php echo esc_html( $header ); ?></dt><dd><code><?php echo esc_html( $value ); ?></code></dd>
				<?php endforeach; ?>
				<dt><?php esc_html_e( 'Cloudflare detected on this request', 'sustainable-catalyst-engagement-intake' ); ?></dt>
				<dd><?php echo $environment['cloudflare_detected'] ? esc_html__( 'yes', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'not detected', 'sustainable-catalyst-engagement-intake' ); ?></dd>
			</dl>
			<p class="description"><?php esc_html_e( 'Cloudflare Cache Rules can still override application headers. Exclude the Consulting page, Contact page, /wp-json/sc-engagement-intake/v1/submit, and /wp-admin/admin-post.php from full-page caching.', 'sustainable-catalyst-engagement-intake' ); ?></p>
		</section>

		<section class="sc-ei-admin__card sc-ei-admin__card--wide">
			<h2><?php esc_html_e( 'Database-to-Filesystem Reconciliation', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><?php esc_html_e( 'The reconciliation scan is read-only. It verifies active database records, file existence, size, SHA-256, quarantine location, and unmanaged .qtn files.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="sc_ei_run_storage_reconciliation">
				<?php wp_nonce_field( 'sc_ei_run_storage_reconciliation' ); ?>
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Run Storage Reconciliation', 'sustainable-catalyst-engagement-intake' ); ?></button>
			</form>

			<?php if ( $reconciliation ) : ?>
				<div class="sc-ei-diagnostic-metrics">
					<?php foreach ( array(
						'records_checked' => __( 'records checked', 'sustainable-catalyst-engagement-intake' ),
						'healthy_records' => __( 'healthy records', 'sustainable-catalyst-engagement-intake' ),
						'missing_files'   => __( 'missing files', 'sustainable-catalyst-engagement-intake' ),
						'hash_mismatches' => __( 'hash mismatches', 'sustainable-catalyst-engagement-intake' ),
						'orphan_files'    => __( 'orphan files', 'sustainable-catalyst-engagement-intake' ),
					) as $key => $label ) : ?>
						<div><strong><?php echo esc_html( number_format_i18n( absint( $recon_counts[ $key ] ?? 0 ) ) ); ?></strong><span><?php echo esc_html( $label ); ?></span></div>
					<?php endforeach; ?>
				</div>
				<p class="description"><?php echo esc_html( sprintf( __( 'Completed %1$s in %2$s seconds.', 'sustainable-catalyst-engagement-intake' ), $reconciliation['completed_at_utc'] ?? 'unknown', $reconciliation['duration_seconds'] ?? '0' ) ); ?></p>

				<?php foreach ( $recon_issues as $issue_type => $items ) : ?>
					<?php if ( $items ) : ?>
						<details class="sc-ei-diagnostic-details">
							<summary><?php echo esc_html( ucwords( str_replace( '_', ' ', $issue_type ) ) . ' (' . count( $items ) . ')' ); ?></summary>
							<div class="sc-ei-diagnostic-table-wrap">
								<table class="widefat striped">
									<thead><tr><th><?php esc_html_e( 'Attachment / Path', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Issue', 'sustainable-catalyst-engagement-intake' ); ?></th></tr></thead>
									<tbody>
										<?php foreach ( $items as $item ) : ?>
											<tr>
												<td>
													<?php if ( ! empty( $item['attachment_id'] ) ) : ?>
														<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'sc-engagement-intake', 'action' => 'view', 'inquiry' => absint( $item['inquiry_id'] ) ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $item['original_name'] ?: 'Attachment #' . $item['attachment_id'] ); ?></a><br>
													<?php endif; ?>
													<code><?php echo esc_html( $item['relative_path'] ?? '' ); ?></code>
												</td>
												<td><?php echo esc_html( $item['message'] ?? '' ); ?></td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</details>
					<?php endif; ?>
				<?php endforeach; ?>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'No reconciliation report has been generated yet.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<?php endif; ?>
		</section>

		<section class="sc-ei-admin__card sc-ei-admin__card--wide">
			<h2><?php esc_html_e( 'Retention Cleanup Test and Execution', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><?php esc_html_e( 'Generate a preview first. The preview does not delete anything. Manual cleanup requires a separate deletion capability and an exact confirmation phrase.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<div class="sc-ei-diagnostic-actions">
				<?php if ( current_user_can( 'sc_intake_manage_file_retention' ) ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="sc_ei_preview_retention_cleanup">
						<?php wp_nonce_field( 'sc_ei_preview_retention_cleanup' ); ?>
						<button type="submit" class="button button-secondary"><?php esc_html_e( 'Preview Expired Cleanup', 'sustainable-catalyst-engagement-intake' ); ?></button>
					</form>
				<?php endif; ?>
			</div>

			<?php if ( $retention ) : ?>
				<p><strong><?php echo esc_html( sprintf( _n( '%d expired attachment', '%d expired attachments', absint( $retention['count'] ?? 0 ), 'sustainable-catalyst-engagement-intake' ), absint( $retention['count'] ?? 0 ) ) ); ?></strong> · <?php echo esc_html( size_format( absint( $retention['total_bytes'] ?? 0 ), 2 ) ); ?></p>
				<?php if ( ! empty( $retention['items'] ) ) : ?>
					<div class="sc-ei-diagnostic-table-wrap"><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Document', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Retention date', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Physical file', 'sustainable-catalyst-engagement-intake' ); ?></th></tr></thead><tbody>
						<?php foreach ( $retention['items'] as $item ) : ?>
							<tr><td><?php echo esc_html( $item['original_name'] ); ?></td><td><?php echo esc_html( $item['retention_until'] ); ?></td><td><?php echo ! empty( $item['file_exists'] ) ? esc_html__( 'present', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'missing', 'sustainable-catalyst-engagement-intake' ); ?></td></tr>
						<?php endforeach; ?>
					</tbody></table></div>
				<?php endif; ?>

				<?php if ( current_user_can( 'sc_intake_delete' ) && absint( $retention['count'] ?? 0 ) > 0 ) : ?>
					<form class="sc-ei-danger-zone" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="sc_ei_run_retention_cleanup">
						<?php wp_nonce_field( 'sc_ei_run_retention_cleanup' ); ?>
						<label><strong><?php esc_html_e( 'Type DELETE EXPIRED', 'sustainable-catalyst-engagement-intake' ); ?></strong><input type="text" name="cleanup_confirmation" autocomplete="off" required></label>
						<button type="submit" class="button button-link-delete"><?php esc_html_e( 'Delete Expired Private Files', 'sustainable-catalyst-engagement-intake' ); ?></button>
					</form>
				<?php endif; ?>
			<?php endif; ?>

			<?php if ( $retention_run ) : ?>
				<p class="description"><?php echo esc_html( sprintf( __( 'Last cleanup: %1$s · deleted %2$d files (%3$s) · %4$d failures.', 'sustainable-catalyst-engagement-intake' ), $retention_run['completed_at_utc'] ?? 'unknown', absint( $retention_run['deleted_count'] ?? 0 ), size_format( absint( $retention_run['deleted_bytes'] ?? 0 ), 2 ), absint( $retention_run['failed_count'] ?? 0 ) ) ); ?></p>
			<?php endif; ?>
		</section>

		<section class="sc-ei-admin__card sc-ei-admin__card--wide">
			<p class="sc-ei-admin__card-kicker"><?php esc_html_e( 'Administrative Review', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<h2><?php esc_html_e( 'Review Workspace Health', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<div class="sc-ei-diagnostic-metrics">
				<div><strong><?php echo esc_html( number_format_i18n( $diagnostics['review_metrics']['open_reviews'] ) ); ?></strong><span><?php esc_html_e( 'open reviews', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
				<div><strong><?php echo esc_html( number_format_i18n( $diagnostics['review_metrics']['unassigned'] ) ); ?></strong><span><?php esc_html_e( 'unassigned', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
				<div><strong><?php echo esc_html( number_format_i18n( $diagnostics['review_metrics']['overdue'] ) ); ?></strong><span><?php esc_html_e( 'overdue', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
				<div><strong><?php echo esc_html( number_format_i18n( $diagnostics['review_metrics']['escalated'] ) ); ?></strong><span><?php esc_html_e( 'escalated', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
				<div><strong><?php echo esc_html( number_format_i18n( $diagnostics['review_metrics']['decision_ready'] ) ); ?></strong><span><?php esc_html_e( 'decision ready', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
				<div><strong><?php echo esc_html( $diagnostics['review_schema_version'] ); ?></strong><span><?php esc_html_e( 'review schema', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
			</div>
			<ul class="sc-ei-checks sc-ei-checks--columns">
				<?php foreach ( $diagnostics['review_columns'] as $column => $ok ) : ?>
					<li><span class="<?php echo $ok ? 'sc-ei-check--ok' : 'sc-ei-check--bad'; ?>">●</span> <?php echo esc_html( $column ); ?></li>
				<?php endforeach; ?>
			</ul>
			<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-review' ) ); ?>"><?php esc_html_e( 'Open Administrative Review Workspace', 'sustainable-catalyst-engagement-intake' ); ?></a></p>
			<p class="description"><?php esc_html_e( 'Review metrics are operational signals. The plugin never creates an automated fit score or silently changes an inquiry status from these metrics.', 'sustainable-catalyst-engagement-intake' ); ?></p>
		</section>

		<section class="sc-ei-admin__card">
			<h2><?php esc_html_e( 'Database Migrations', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<h3><?php esc_html_e( 'Tables', 'sustainable-catalyst-engagement-intake' ); ?></h3>
			<ul class="sc-ei-checks"><?php foreach ( $diagnostics['tables'] as $name => $ok ) : ?><li><span class="<?php echo $ok ? 'sc-ei-check--ok' : 'sc-ei-check--bad'; ?>">●</span> <?php echo esc_html( $name ); ?></li><?php endforeach; ?></ul>
			<h3><?php esc_html_e( 'v0.4.0 attachment, scanner, and review fields', 'sustainable-catalyst-engagement-intake' ); ?></h3>
			<ul class="sc-ei-checks"><?php foreach ( $diagnostics['attachment_columns'] as $column => $ok ) : ?><li><span class="<?php echo $ok ? 'sc-ei-check--ok' : 'sc-ei-check--bad'; ?>">●</span> <?php echo esc_html( $column ); ?></li><?php endforeach; ?></ul>
		</section>

		<section class="sc-ei-admin__card">
			<h2><?php esc_html_e( 'Scanner and Retention Scheduler', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><strong><?php esc_html_e( 'Scanner configured:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo $diagnostics['scanner']['configured'] ? esc_html__( 'yes', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'no', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<p><strong><?php esc_html_e( 'Provider:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo esc_html( $diagnostics['scanner']['provider'] ); ?></p>
			<p><strong><?php esc_html_e( 'Readiness:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo $diagnostics['scanner_readiness']['ready'] ? esc_html__( 'ready', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'attention', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<p><strong><?php esc_html_e( 'Last benign test:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo esc_html( $diagnostics['scanner_readiness']['test']['completed_at_utc'] ?? __( 'never', 'sustainable-catalyst-engagement-intake' ) ); ?> · <?php echo esc_html( ucwords( str_replace( '_', ' ', (string) ( $diagnostics['scanner_readiness']['test']['scan_status'] ?? 'not_run' ) ) ) ); ?></p>
			<p><strong><?php esc_html_e( 'Clean scan required:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo $diagnostics['uploads']['scanner_required'] ? esc_html__( 'yes', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'no', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<p><strong><?php esc_html_e( 'Scan attention records:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo esc_html( number_format_i18n( $diagnostics['quarantine_operations']['scan_attention_count'] ) ); ?></p>
			<p><strong><?php esc_html_e( 'Daily retention cron:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo $diagnostics['uploads']['retention_cron_scheduled'] ? esc_html__( 'scheduled', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'missing', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-quarantine' ) ); ?>"><?php esc_html_e( 'Open Quarantine Operations', 'sustainable-catalyst-engagement-intake' ); ?></a></p>
		</section>

		<section class="sc-ei-admin__card">
			<h2><?php esc_html_e( 'Public Exposure Boundary', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><strong><?php esc_html_e( 'Public inquiry archive:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'None', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<p><strong><?php esc_html_e( 'Public file URLs:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'None', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<p><strong><?php esc_html_e( 'Downloads:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'Authenticated endpoint with capability, nonce, storage-state, and SHA-256 checks', 'sustainable-catalyst-engagement-intake' ); ?></p>
		</section>

		<section class="sc-ei-admin__card">
			<h2><?php esc_html_e( 'Runtime', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><?php echo esc_html( 'WordPress ' . $diagnostics['wordpress_version'] ); ?></p>
			<p><?php echo esc_html( 'PHP ' . $diagnostics['php_version'] ); ?></p>
			<p><?php echo esc_html( $diagnostics['multisite'] ? 'Multisite: yes' : 'Multisite: no' ); ?></p>
			<p><strong><?php esc_html_e( 'Active attachment records:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo esc_html( number_format_i18n( $diagnostics['database_totals']['active_count'] ) ); ?></p>
			<p><strong><?php esc_html_e( 'Recorded active bytes:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo esc_html( size_format( $diagnostics['database_totals']['active_bytes'], 2 ) ); ?></p>
		</section>
	</div>
</div>
