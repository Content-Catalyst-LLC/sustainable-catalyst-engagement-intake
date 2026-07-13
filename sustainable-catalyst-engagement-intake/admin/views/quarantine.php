<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$message     = isset( $_GET['sc_ei_msg'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_msg'] ) ) : '';
$bulk_result = get_transient( 'sc_ei_quarantine_bulk_result_' . get_current_user_id() );
if ( $bulk_result ) {
	delete_transient( 'sc_ei_quarantine_bulk_result_' . get_current_user_id() );
}
$bulk_result = is_array( $bulk_result ) ? $bulk_result : array();

$queue_url = add_query_arg( array( 'page' => 'sc-engagement-intake-quarantine', 'view' => 'queue' ), admin_url( 'admin.php' ) );
$audit_url = add_query_arg( array( 'page' => 'sc-engagement-intake-quarantine', 'view' => 'access' ), admin_url( 'admin.php' ) );
$guide_url = add_query_arg( array( 'page' => 'sc-engagement-intake-quarantine', 'view' => 'guidance' ), admin_url( 'admin.php' ) );

$current_filters = array(
	'quarantine_status' => isset( $_GET['quarantine_status'] ) ? sanitize_key( wp_unslash( $_GET['quarantine_status'] ) ) : '',
	'scan_status'       => isset( $_GET['scan_status'] ) ? sanitize_key( wp_unslash( $_GET['scan_status'] ) ) : '',
	'validation_status' => isset( $_GET['validation_status'] ) ? sanitize_key( wp_unslash( $_GET['validation_status'] ) ) : '',
	'storage_status'    => isset( $_GET['storage_status'] ) ? sanitize_key( wp_unslash( $_GET['storage_status'] ) ) : '',
	'document_category' => isset( $_GET['document_category'] ) ? sanitize_key( wp_unslash( $_GET['document_category'] ) ) : '',
	'confidentiality'   => isset( $_GET['confidentiality'] ) ? sanitize_key( wp_unslash( $_GET['confidentiality'] ) ) : '',
	'retention'         => isset( $_GET['retention'] ) ? sanitize_key( wp_unslash( $_GET['retention'] ) ) : '',
	's'                 => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
);
?>
<div class="wrap sc-ei-admin sc-ei-quarantine-operations">
	<h1><?php esc_html_e( 'Quarantine Operations and Scanner Readiness', 'sustainable-catalyst-engagement-intake' ); ?></h1>
	<p><?php esc_html_e( 'Review private documents across all inquiries, verify storage state, retry external scans, manage retention, audit access, and apply human-controlled quarantine decisions.', 'sustainable-catalyst-engagement-intake' ); ?></p>

	<?php if ( 'scanner_test_clean' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'The generated benign scanner-readiness file was reported clean and deleted successfully.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'scanner_test_attention' === $message ) : ?>
		<div class="notice notice-warning is-dismissible"><p><?php esc_html_e( 'The scanner readiness test did not establish clean required-mode readiness. Review the provider, result, and deletion state below.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'single_scan_clean' === $message ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'The selected attachment was rescanned and reported clean.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'single_scan_attention' === $message ) : ?>
		<div class="notice notice-warning is-dismissible"><p><?php esc_html_e( 'The selected attachment scan requires attention. Review its scanner and storage states.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php elseif ( 'bulk_completed' === $message && $bulk_result ) : ?>
		<div class="notice <?php echo empty( $bulk_result['failed'] ) ? 'notice-success' : 'notice-warning'; ?> is-dismissible">
			<p>
				<?php
				echo esc_html(
					sprintf(
						__( 'Bulk action %1$s completed: %2$d processed, %3$d succeeded, %4$d failed, %5$d skipped.', 'sustainable-catalyst-engagement-intake' ),
						ucwords( str_replace( '_', ' ', (string) ( $bulk_result['operation'] ?? '' ) ) ),
						absint( $bulk_result['processed'] ?? 0 ),
						absint( $bulk_result['succeeded'] ?? 0 ),
						absint( $bulk_result['failed'] ?? 0 ),
						absint( $bulk_result['skipped'] ?? 0 )
					)
				);
				?>
			</p>
		</div>
	<?php elseif ( 'bulk_error' === $message ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'The bulk quarantine operation was rejected. Select documents, review permissions, and provide any required date or confirmation phrase.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php endif; ?>

	<nav class="nav-tab-wrapper sc-ei-operation-tabs" aria-label="<?php esc_attr_e( 'Quarantine operations views', 'sustainable-catalyst-engagement-intake' ); ?>">
		<a class="nav-tab <?php echo 'queue' === $view ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $queue_url ); ?>"><?php esc_html_e( 'Quarantine Queue', 'sustainable-catalyst-engagement-intake' ); ?></a>
		<?php if ( current_user_can( 'sc_intake_view_file_audit' ) ) : ?>
			<a class="nav-tab <?php echo 'access' === $view ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $audit_url ); ?>"><?php esc_html_e( 'Access and Operations Audit', 'sustainable-catalyst-engagement-intake' ); ?></a>
		<?php endif; ?>
		<a class="nav-tab <?php echo 'guidance' === $view ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $guide_url ); ?>"><?php esc_html_e( 'Isolation Guidance', 'sustainable-catalyst-engagement-intake' ); ?></a>
	</nav>

	<div class="sc-ei-quarantine-summary">
		<div><strong><?php echo esc_html( number_format_i18n( $summary['active_count'] ) ); ?></strong><span><?php esc_html_e( 'active documents', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div><strong><?php echo esc_html( number_format_i18n( $summary['quarantined_count'] ) ); ?></strong><span><?php esc_html_e( 'quarantined', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div><strong><?php echo esc_html( number_format_i18n( $summary['clean_count'] ) ); ?></strong><span><?php esc_html_e( 'scanner clean', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div class="<?php echo $summary['scan_attention_count'] ? 'sc-ei-summary-attention' : ''; ?>"><strong><?php echo esc_html( number_format_i18n( $summary['scan_attention_count'] ) ); ?></strong><span><?php esc_html_e( 'scan attention', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div class="<?php echo $summary['storage_attention_count'] ? 'sc-ei-summary-danger' : ''; ?>"><strong><?php echo esc_html( number_format_i18n( $summary['storage_attention_count'] ) ); ?></strong><span><?php esc_html_e( 'storage attention', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div class="<?php echo $summary['expired_count'] ? 'sc-ei-summary-attention' : ''; ?>"><strong><?php echo esc_html( number_format_i18n( $summary['expired_count'] ) ); ?></strong><span><?php esc_html_e( 'retention expired', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div><strong><?php echo esc_html( size_format( $summary['active_bytes'], 2 ) ); ?></strong><span><?php esc_html_e( 'recorded active bytes', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div><strong><?php echo esc_html( number_format_i18n( $summary['total_downloads'] ) ); ?></strong><span><?php esc_html_e( 'authorized downloads', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
	</div>

	<div class="sc-ei-operation-grid">
		<section class="sc-ei-admin__card sc-ei-scanner-readiness-card">
			<p class="sc-ei-admin__card-kicker sc-ei-admin__card-kicker--documents"><?php esc_html_e( 'External Scanner', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<h2><?php esc_html_e( 'Scanner Readiness', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<div class="sc-ei-readiness-state sc-ei-readiness-state--<?php echo esc_attr( $readiness['ready'] ? 'ready' : 'attention' ); ?>">
				<strong><?php echo $readiness['ready'] ? esc_html__( 'Ready for clean-required mode', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'Readiness not established', 'sustainable-catalyst-engagement-intake' ); ?></strong>
				<span><?php echo esc_html( $readiness['probe']['provider'] ); ?></span>
			</div>
			<dl class="sc-ei-admin__details">
				<dt><?php esc_html_e( 'Integration configured', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo $readiness['probe']['configured'] ? esc_html__( 'yes', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'no', 'sustainable-catalyst-engagement-intake' ); ?></dd>
				<dt><?php esc_html_e( 'Probe message', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $readiness['probe']['message'] ); ?></dd>
				<dt><?php esc_html_e( 'Last test', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $readiness['test']['completed_at_utc'] ?? __( 'Never', 'sustainable-catalyst-engagement-intake' ) ); ?></dd>
				<dt><?php esc_html_e( 'Test result', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( ucwords( str_replace( '_', ' ', (string) ( $readiness['test']['scan_status'] ?? 'not_run' ) ) ) ); ?></dd>
				<dt><?php esc_html_e( 'Test freshness', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo $readiness['test_fresh'] ? esc_html__( 'current', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'expired or missing', 'sustainable-catalyst-engagement-intake' ); ?> · <?php echo esc_html( sprintf( __( '%d-hour policy', 'sustainable-catalyst-engagement-intake' ), $readiness['freshness_hours'] ) ); ?></dd>
				<dt><?php esc_html_e( 'Clean-required mode', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo $readiness['require_clean_enabled'] ? esc_html__( 'enabled', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'disabled', 'sustainable-catalyst-engagement-intake' ); ?></dd>
			</dl>
			<?php if ( current_user_can( 'sc_intake_manage_scanner' ) ) : ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="sc_ei_run_scanner_readiness_test">
					<?php wp_nonce_field( 'sc_ei_run_scanner_readiness_test' ); ?>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Run Benign Scanner Test', 'sustainable-catalyst-engagement-intake' ); ?></button>
				</form>
			<?php endif; ?>
			<p class="description"><?php esc_html_e( 'The generated test file contains no submitted content. A clean, recent result is required before clean-required mode can be newly enabled.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<?php if ( $readiness['require_clean_enabled'] && ! $readiness['ready'] ) : ?>
				<div class="sc-ei-diagnostic-warning"><strong><?php esc_html_e( 'Fail-closed state:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'New uploads not reported clean will be rejected and deleted. Restore scanner readiness immediately or disable clean-required mode deliberately in Settings.', 'sustainable-catalyst-engagement-intake' ); ?></div>
			<?php endif; ?>
		</section>

		<section class="sc-ei-admin__card">
			<p class="sc-ei-admin__card-kicker"><?php esc_html_e( 'Private Storage', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<h2><?php esc_html_e( 'Storage Utilization', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<dl class="sc-ei-admin__details">
				<dt><?php esc_html_e( 'Managed files', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( number_format_i18n( $storage['managed_files'] ) ); ?></dd>
				<dt><?php esc_html_e( 'Managed bytes', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( size_format( $storage['managed_bytes'], 2 ) ); ?></dd>
				<dt><?php esc_html_e( 'Free disk space', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( size_format( $storage['disk_free_bytes'], 2 ) ); ?></dd>
				<dt><?php esc_html_e( 'Staging files', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( number_format_i18n( $storage['staging_files'] ) ); ?></dd>
				<dt><?php esc_html_e( 'Inventory capped', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo $storage['inventory_capped'] ? esc_html__( 'yes', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'no', 'sustainable-catalyst-engagement-intake' ); ?></dd>
			</dl>
			<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-diagnostics' ) ); ?>"><?php esc_html_e( 'Open Storage Diagnostics', 'sustainable-catalyst-engagement-intake' ); ?></a></p>
		</section>
	</div>

	<?php if ( 'queue' === $view ) : ?>
		<section class="sc-ei-admin__card sc-ei-admin__card--wide sc-ei-quarantine-queue-card">
			<h2><?php esc_html_e( 'Cross-Inquiry Quarantine Queue', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<form method="get" class="sc-ei-operation-filter-form">
				<input type="hidden" name="page" value="sc-engagement-intake-quarantine">
				<input type="hidden" name="view" value="queue">
				<input type="search" name="s" value="<?php echo esc_attr( $current_filters['s'] ); ?>" placeholder="<?php esc_attr_e( 'Search file, inquiry, contact, organization, or SHA-256', 'sustainable-catalyst-engagement-intake' ); ?>">
				<select name="quarantine_status"><option value=""><?php esc_html_e( 'All quarantine states', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( array( 'quarantined', 'approved', 'replacement_requested' ) as $key ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_filters['quarantine_status'], $key ); ?>><?php echo esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?></option><?php endforeach; ?></select>
				<select name="scan_status"><option value=""><?php esc_html_e( 'All scanner states', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( array( 'not_configured', 'clean', 'infected', 'error', 'skipped' ) as $key ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_filters['scan_status'], $key ); ?>><?php echo esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?></option><?php endforeach; ?></select>
				<select name="validation_status"><option value=""><?php esc_html_e( 'All validation states', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( array( 'validated', 'rejected', 'error' ) as $key ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_filters['validation_status'], $key ); ?>><?php echo esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?></option><?php endforeach; ?></select>
				<select name="storage_status"><option value=""><?php esc_html_e( 'All storage states', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( array( 'healthy', 'unverified', 'missing', 'hash_mismatch', 'size_mismatch', 'misplaced', 'unresolvable' ) as $key ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_filters['storage_status'], $key ); ?>><?php echo esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?></option><?php endforeach; ?></select>
				<select name="document_category"><option value=""><?php esc_html_e( 'All categories', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( SC_EI_Form_Schema::document_categories() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_filters['document_category'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
				<select name="confidentiality"><option value=""><?php esc_html_e( 'All confidentiality levels', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( SC_EI_Form_Schema::document_confidentiality_options() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current_filters['confidentiality'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
				<select name="retention"><option value=""><?php esc_html_e( 'All retention states', 'sustainable-catalyst-engagement-intake' ); ?></option><option value="expired" <?php selected( $current_filters['retention'], 'expired' ); ?>><?php esc_html_e( 'Expired', 'sustainable-catalyst-engagement-intake' ); ?></option><option value="due_30" <?php selected( $current_filters['retention'], 'due_30' ); ?>><?php esc_html_e( 'Due within 30 days', 'sustainable-catalyst-engagement-intake' ); ?></option><option value="future" <?php selected( $current_filters['retention'], 'future' ); ?>><?php esc_html_e( 'More than 30 days', 'sustainable-catalyst-engagement-intake' ); ?></option><option value="none" <?php selected( $current_filters['retention'], 'none' ); ?>><?php esc_html_e( 'No date', 'sustainable-catalyst-engagement-intake' ); ?></option></select>
				<button type="submit" class="button"><?php esc_html_e( 'Apply Filters', 'sustainable-catalyst-engagement-intake' ); ?></button>
				<a class="button-link" href="<?php echo esc_url( $queue_url ); ?>"><?php esc_html_e( 'Clear', 'sustainable-catalyst-engagement-intake' ); ?></a>
			</form>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="sc_ei_quarantine_bulk">
				<?php wp_nonce_field( 'sc_ei_quarantine_bulk' ); ?>
				<?php $quarantine_table->display(); ?>
			</form>
		</section>
	<?php elseif ( 'access' === $view && current_user_can( 'sc_intake_view_file_audit' ) ) : ?>
		<section class="sc-ei-admin__card sc-ei-admin__card--wide">
			<h2><?php esc_html_e( 'Private Document Access and Operations Audit', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><?php esc_html_e( 'This report includes authorized downloads, integrity checks, scanner operations, quarantine decisions, retention changes, deletions, readiness tests, and reconciliation events. It does not export file contents.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<div class="sc-ei-audit-summary">
				<?php foreach ( $audit_summary as $event_type => $event_data ) : ?>
					<div><strong><?php echo esc_html( number_format_i18n( $event_data['count'] ) ); ?></strong><span><?php echo esc_html( SC_EI_Audit_Log::file_event_types()[ $event_type ] ?? ucwords( str_replace( '_', ' ', $event_type ) ) ); ?></span></div>
				<?php endforeach; ?>
			</div>
			<form method="get" class="sc-ei-operation-filter-form">
				<input type="hidden" name="page" value="sc-engagement-intake-quarantine">
				<input type="hidden" name="view" value="access">
				<input type="search" name="s" value="<?php echo isset( $_GET['s'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['s'] ) ) ) : ''; ?>" placeholder="<?php esc_attr_e( 'Search event, file, inquiry, or actor', 'sustainable-catalyst-engagement-intake' ); ?>">
				<select name="event_type"><option value=""><?php esc_html_e( 'All file events', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( SC_EI_Audit_Log::file_event_types() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( isset( $_GET['event_type'] ) ? sanitize_key( wp_unslash( $_GET['event_type'] ) ) : '', $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
				<?php $sc_ei_audit_users = get_users( array( 'fields' => array( 'ID', 'display_name' ), 'orderby' => 'display_name', 'number' => 100 ) ); ?>
				<select name="actor"><option value=""><?php esc_html_e( 'All actors', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( $sc_ei_audit_users as $user ) : ?><option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( isset( $_GET['actor'] ) ? absint( $_GET['actor'] ) : 0, $user->ID ); ?>><?php echo esc_html( $user->display_name ); ?></option><?php endforeach; ?></select>
				<input type="date" name="date_from" value="<?php echo isset( $_GET['date_from'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) ) : ''; ?>" aria-label="<?php esc_attr_e( 'From date', 'sustainable-catalyst-engagement-intake' ); ?>">
				<input type="date" name="date_to" value="<?php echo isset( $_GET['date_to'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) ) : ''; ?>" aria-label="<?php esc_attr_e( 'Through date', 'sustainable-catalyst-engagement-intake' ); ?>">
				<button type="submit" class="button"><?php esc_html_e( 'Apply Filters', 'sustainable-catalyst-engagement-intake' ); ?></button>
				<a class="button-link" href="<?php echo esc_url( $audit_url ); ?>"><?php esc_html_e( 'Clear', 'sustainable-catalyst-engagement-intake' ); ?></a>
				<?php
				$export_url = wp_nonce_url(
					add_query_arg(
						array(
							'action'     => 'sc_ei_export_file_audit',
							'event_type' => isset( $_GET['event_type'] ) ? sanitize_key( wp_unslash( $_GET['event_type'] ) ) : '',
							'actor'      => isset( $_GET['actor'] ) ? absint( $_GET['actor'] ) : 0,
							'date_from'  => isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '',
							'date_to'    => isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '',
							's'          => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
						),
						admin_url( 'admin-post.php' )
					),
					'sc_ei_export_file_audit'
				);
				?>
				<a class="button button-secondary" href="<?php echo esc_url( $export_url ); ?>"><?php esc_html_e( 'Export Filtered CSV', 'sustainable-catalyst-engagement-intake' ); ?></a>
			</form>
			<?php $access_table->display(); ?>
		</section>
	<?php else : ?>
		<section class="sc-ei-admin__card sc-ei-admin__card--wide sc-ei-isolation-guidance">
			<h2><?php esc_html_e( 'Untrusted Document Isolation Guidance', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<div class="sc-ei-guidance-grid">
				<div>
					<h3><?php esc_html_e( 'Before download', 'sustainable-catalyst-engagement-intake' ); ?></h3>
					<ul>
						<li><?php esc_html_e( 'Confirm storage status is healthy and integrity is verified.', 'sustainable-catalyst-engagement-intake' ); ?></li>
						<li><?php esc_html_e( 'Prefer a current clean external scan result.', 'sustainable-catalyst-engagement-intake' ); ?></li>
						<li><?php esc_html_e( 'Treat not-configured, skipped, and error scan states as untrusted.', 'sustainable-catalyst-engagement-intake' ); ?></li>
						<li><?php esc_html_e( 'Do not download infected, missing, altered, or unresolvable files.', 'sustainable-catalyst-engagement-intake' ); ?></li>
					</ul>
				</div>
				<div>
					<h3><?php esc_html_e( 'Review workstation', 'sustainable-catalyst-engagement-intake' ); ?></h3>
					<ul>
						<li><?php esc_html_e( 'Use a patched, isolated workstation or disposable virtual machine.', 'sustainable-catalyst-engagement-intake' ); ?></li>
						<li><?php esc_html_e( 'Disable macros, external templates, automatic links, and protected-view bypasses.', 'sustainable-catalyst-engagement-intake' ); ?></li>
						<li><?php esc_html_e( 'Do not sign in to privileged accounts from the review environment.', 'sustainable-catalyst-engagement-intake' ); ?></li>
						<li><?php esc_html_e( 'Avoid copying untrusted files into synchronized public or team folders.', 'sustainable-catalyst-engagement-intake' ); ?></li>
					</ul>
				</div>
				<div>
					<h3><?php esc_html_e( 'After review', 'sustainable-catalyst-engagement-intake' ); ?></h3>
					<ul>
						<li><?php esc_html_e( 'Approve only when the document is necessary and its provenance is understood.', 'sustainable-catalyst-engagement-intake' ); ?></li>
						<li><?php esc_html_e( 'Request a replacement when encryption, corruption, ambiguity, or suspicious behavior prevents safe review.', 'sustainable-catalyst-engagement-intake' ); ?></li>
						<li><?php esc_html_e( 'Reject and delete confirmed unsafe or unnecessary files.', 'sustainable-catalyst-engagement-intake' ); ?></li>
						<li><?php esc_html_e( 'Record material decisions in the private inquiry and audit history.', 'sustainable-catalyst-engagement-intake' ); ?></li>
					</ul>
				</div>
			</div>
			<div class="sc-ei-diagnostic-warning"><strong><?php esc_html_e( 'Boundary:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'Built-in validation and a clean external scan reduce risk but do not prove a document is safe. Human review and endpoint isolation remain required for higher-risk submissions.', 'sustainable-catalyst-engagement-intake' ); ?></div>
		</section>
	<?php endif; ?>
</div>
