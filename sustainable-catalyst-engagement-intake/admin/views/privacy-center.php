<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$message = isset( $_GET['sc_ei_msg'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_msg'] ) ) : '';
$tabs = array(
	'overview' => __( 'Overview', 'sustainable-catalyst-engagement-intake' ),
	'requests' => __( 'Privacy Requests', 'sustainable-catalyst-engagement-intake' ),
	'consent'  => __( 'Consent Ledger', 'sustainable-catalyst-engagement-intake' ),
	'holds'    => __( 'Legal Holds', 'sustainable-catalyst-engagement-intake' ),
	'queue'    => __( 'Retention Queue', 'sustainable-catalyst-engagement-intake' ),
	'policies' => __( 'Policies', 'sustainable-catalyst-engagement-intake' ),
	'method'   => __( 'Operating Method', 'sustainable-catalyst-engagement-intake' ),
);
$success_messages = array(
	'privacy_request_created'        => __( 'Privacy request case created.', 'sustainable-catalyst-engagement-intake' ),
	'privacy_request_updated'        => __( 'Privacy request case updated.', 'sustainable-catalyst-engagement-intake' ),
	'consent_event_recorded'         => __( 'Consent or authorization event recorded.', 'sustainable-catalyst-engagement-intake' ),
	'legal_hold_placed'              => __( 'Legal hold placed and matching queued actions blocked.', 'sustainable-catalyst-engagement-intake' ),
	'legal_hold_released'            => __( 'Legal hold released. Blocked actions still require fresh review and approval.', 'sustainable-catalyst-engagement-intake' ),
	'retention_preview_ready'        => __( 'Retention preview generated. No records or files were deleted.', 'sustainable-catalyst-engagement-intake' ),
	'retention_candidates_queued'    => __( 'Retention candidates were queued for human review. No deletion was performed.', 'sustainable-catalyst-engagement-intake' ),
	'retention_action_approved'      => __( 'Retention action approved. Execution remains a separate operation.', 'sustainable-catalyst-engagement-intake' ),
	'retention_action_executed'      => __( 'Retention action executed and verification recorded.', 'sustainable-catalyst-engagement-intake' ),
	'retention_action_canceled'      => __( 'Retention action canceled; its audit history remains.', 'sustainable-catalyst-engagement-intake' ),
	'retention_policy_saved'         => __( 'A new immutable retention policy version was created.', 'sustainable-catalyst-engagement-intake' ),
	'inquiry_privacy_state_updated'  => __( 'Inquiry privacy state updated.', 'sustainable-catalyst-engagement-intake' ),
);
?>
<div class="wrap sc-ei-admin sc-ei-privacy-center">
	<h1><?php esc_html_e( 'Privacy and Retention Center', 'sustainable-catalyst-engagement-intake' ); ?></h1>
	<p><?php esc_html_e( 'Manage data inventory, privacy requests, consent evidence, legal holds, retention policy versions, and verified lifecycle actions. Daily automation only queues candidates. It never silently deletes records or private files.', 'sustainable-catalyst-engagement-intake' ); ?></p>

	<?php if ( isset( $success_messages[ $message ] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $success_messages[ $message ] ); ?></p></div>
	<?php elseif ( $message ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php echo esc_html( ucwords( str_replace( '_', ' ', $message ) ) ); ?></p></div>
	<?php endif; ?>

	<div class="sc-ei-privacy-metrics">
		<a href="<?php echo esc_url( SC_EI_Privacy_Admin::url( 'requests' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['open_requests'] ) ); ?></strong><span><?php esc_html_e( 'open requests', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a class="<?php echo $metrics['overdue_requests'] ? 'sc-ei-review-metric--danger' : ''; ?>" href="<?php echo esc_url( SC_EI_Privacy_Admin::url( 'requests' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['overdue_requests'] ) ); ?></strong><span><?php esc_html_e( 'overdue requests', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a class="<?php echo $metrics['active_holds'] ? 'sc-ei-review-metric--attention' : ''; ?>" href="<?php echo esc_url( SC_EI_Privacy_Admin::url( 'holds', array( 'status' => 'active' ) ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['active_holds'] ) ); ?></strong><span><?php esc_html_e( 'active holds', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a class="<?php echo $metrics['holds_due_review'] ? 'sc-ei-review-metric--danger' : ''; ?>" href="<?php echo esc_url( SC_EI_Privacy_Admin::url( 'holds', array( 'status' => 'active' ) ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['holds_due_review'] ) ); ?></strong><span><?php esc_html_e( 'holds due review', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( SC_EI_Privacy_Admin::url( 'queue', array( 'status' => 'queued' ) ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['queued_actions'] ) ); ?></strong><span><?php esc_html_e( 'queued actions', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( SC_EI_Privacy_Admin::url( 'queue', array( 'status' => 'approved' ) ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['approved_actions'] ) ); ?></strong><span><?php esc_html_e( 'approved actions', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a class="<?php echo $metrics['blocked_actions'] ? 'sc-ei-review-metric--attention' : ''; ?>" href="<?php echo esc_url( SC_EI_Privacy_Admin::url( 'queue' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['blocked_actions'] ) ); ?></strong><span><?php esc_html_e( 'blocked actions', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a class="<?php echo $metrics['failed_actions'] ? 'sc-ei-review-metric--danger' : ''; ?>" href="<?php echo esc_url( SC_EI_Privacy_Admin::url( 'queue', array( 'status' => 'failed' ) ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['failed_actions'] ) ); ?></strong><span><?php esc_html_e( 'failed actions', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( SC_EI_Privacy_Admin::url( 'overview' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['restricted_inquiries'] ) ); ?></strong><span><?php esc_html_e( 'restricted inquiries', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( SC_EI_Privacy_Admin::url( 'consent' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['consent_events'] ) ); ?></strong><span><?php esc_html_e( 'consent events', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
	</div>

	<nav class="nav-tab-wrapper sc-ei-operation-tabs" aria-label="<?php esc_attr_e( 'Privacy Center views', 'sustainable-catalyst-engagement-intake' ); ?>">
		<?php foreach ( $tabs as $key => $label ) : ?>
			<a class="nav-tab <?php echo $view === $key ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( SC_EI_Privacy_Admin::url( $key ) ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</nav>

	<?php if ( 'overview' === $view ) : ?>
		<div class="sc-ei-privacy-layout">
			<main>
				<section class="sc-ei-admin__card sc-ei-admin__card--wide">
					<div class="sc-ei-review-section-header">
						<h2><?php esc_html_e( 'Private Data Inventory', 'sustainable-catalyst-engagement-intake' ); ?></h2>
						<span><?php echo esc_html( SC_EI_PRIVACY_SCHEMA_VERSION ); ?></span>
					</div>
					<p><?php esc_html_e( 'Counts represent records in the private plugin database. Active document bytes represent undeleted files tracked in protected storage.', 'sustainable-catalyst-engagement-intake' ); ?></p>
					<div class="sc-ei-data-inventory-grid">
						<?php foreach ( $inventory as $name => $count ) : ?>
							<div><strong><?php echo 'active_attachment_bytes' === $name ? esc_html( size_format( absint( $count ), 2 ) ) : esc_html( number_format_i18n( $count ) ); ?></strong><span><?php echo esc_html( ucwords( str_replace( '_', ' ', $name ) ) ); ?></span></div>
						<?php endforeach; ?>
					</div>
					<?php if ( current_user_can( 'sc_intake_export_privacy_data' ) ) : ?>
						<p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sc_ei_export_privacy_inventory' ), 'sc_ei_export_privacy_inventory' ) ); ?>"><?php esc_html_e( 'Export Private Inventory JSON', 'sustainable-catalyst-engagement-intake' ); ?></a></p>
					<?php endif; ?>
				</section>

				<section class="sc-ei-admin__card">
					<h2><?php esc_html_e( 'Lifecycle Safety Model', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<ol class="sc-ei-method-list">
						<li><?php esc_html_e( 'Version and activate a policy.', 'sustainable-catalyst-engagement-intake' ); ?></li>
						<li><?php esc_html_e( 'Preview deterministic candidates.', 'sustainable-catalyst-engagement-intake' ); ?></li>
						<li><?php esc_html_e( 'Queue candidates without deleting anything.', 'sustainable-catalyst-engagement-intake' ); ?></li>
						<li><?php esc_html_e( 'Resolve legal holds and dependencies.', 'sustainable-catalyst-engagement-intake' ); ?></li>
						<li><?php esc_html_e( 'Approve the exact action.', 'sustainable-catalyst-engagement-intake' ); ?></li>
						<li><?php esc_html_e( 'Type the action-specific execution phrase.', 'sustainable-catalyst-engagement-intake' ); ?></li>
						<li><?php esc_html_e( 'Verify physical deletion or database redaction.', 'sustainable-catalyst-engagement-intake' ); ?></li>
						<li><?php esc_html_e( 'Preserve a non-personal tombstone and audit event.', 'sustainable-catalyst-engagement-intake' ); ?></li>
					</ol>
				</section>

				<section class="sc-ei-admin__card">
					<h2><?php esc_html_e( 'Current Policy Defaults', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<dl class="sc-ei-admin__details">
						<dt><?php esc_html_e( 'Unaccepted inquiry', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $settings['default_unaccepted_retention_days'] ); ?> <?php esc_html_e( 'days', 'sustainable-catalyst-engagement-intake' ); ?></dd>
						<dt><?php esc_html_e( 'Withdrawn inquiry', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $settings['withdrawn_retention_days'] ); ?> <?php esc_html_e( 'days', 'sustainable-catalyst-engagement-intake' ); ?></dd>
						<dt><?php esc_html_e( 'Closed inquiry', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $settings['closed_retention_days'] ); ?> <?php esc_html_e( 'days', 'sustainable-catalyst-engagement-intake' ); ?></dd>
						<dt><?php esc_html_e( 'Accepted engagement', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $settings['accepted_retention_days'] ); ?> <?php esc_html_e( 'days', 'sustainable-catalyst-engagement-intake' ); ?></dd>
						<dt><?php esc_html_e( 'Communication content', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $settings['communication_retention_days'] ); ?> <?php esc_html_e( 'days', 'sustainable-catalyst-engagement-intake' ); ?></dd>
						<dt><?php esc_html_e( 'Private documents', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $settings['attachment_retention_days'] ); ?> <?php esc_html_e( 'days', 'sustainable-catalyst-engagement-intake' ); ?></dd>
						<dt><?php esc_html_e( 'Cron behavior', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php esc_html_e( 'Queue only; no deletion', 'sustainable-catalyst-engagement-intake' ); ?></dd>
						<dt><?php esc_html_e( 'Tombstones', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php esc_html_e( 'Always retained', 'sustainable-catalyst-engagement-intake' ); ?></dd>
					</dl>
					<p><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-settings' ) ); ?>"><?php esc_html_e( 'Configure Lifecycle Settings', 'sustainable-catalyst-engagement-intake' ); ?></a></p>
				</section>
			</main>

			<aside>
				<?php if ( current_user_can( 'sc_intake_manage_privacy_requests' ) ) : ?>
					<section class="sc-ei-admin__card">
						<h2><?php esc_html_e( 'Restrict Inquiry Processing', 'sustainable-catalyst-engagement-intake' ); ?></h2>
						<p><?php esc_html_e( 'Use an inquiry ID from the private inquiry list. Erasure itself must use an approved queue action.', 'sustainable-catalyst-engagement-intake' ); ?></p>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<input type="hidden" name="action" value="sc_ei_update_inquiry_privacy_state">
							<?php wp_nonce_field( 'sc_ei_update_inquiry_privacy_state' ); ?>
							<label><span><?php esc_html_e( 'Inquiry ID', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="1" name="inquiry_id" required value="<?php echo esc_attr( absint( $_GET['inquiry'] ?? 0 ) ?: '' ); ?>"></label>
							<label><span><?php esc_html_e( 'Privacy state', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="privacy_status"><?php foreach ( SC_EI_Privacy_Schema::privacy_statuses() as $key => $label ) : ?><?php if ( 'erased' !== $key ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endif; ?><?php endforeach; ?></select></label>
							<label><span><?php esc_html_e( 'Reason', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="privacy_reason" rows="5" required></textarea></label>
							
							<button type="submit" class="button"><?php esc_html_e( 'Update Privacy State', 'sustainable-catalyst-engagement-intake' ); ?></button>
						</form>
					</section>
				<?php endif; ?>
				<section class="sc-ei-admin__card">
					<h2><?php esc_html_e( 'Important Boundary', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<p><?php esc_html_e( 'This workspace supports operational privacy governance. It does not determine which laws apply, replace legal advice, or guarantee that a configured period satisfies a legal obligation.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				</section>
			</aside>
		</div>

	<?php elseif ( 'requests' === $view ) : ?>
		<section class="sc-ei-admin__card sc-ei-admin__card--wide">
			<h2><?php esc_html_e( 'Privacy Request Cases', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<form method="get" class="sc-ei-operation-filter-form">
				<input type="hidden" name="page" value="sc-engagement-intake-privacy"><input type="hidden" name="view" value="requests">
				<input type="search" name="s" value="<?php echo esc_attr( $_GET['s'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Search requester, inquiry, or summary', 'sustainable-catalyst-engagement-intake' ); ?>">
				<select name="status"><option value=""><?php esc_html_e( 'All statuses', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( SC_EI_Privacy_Schema::request_statuses() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $_GET['status'] ?? '', $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
				<select name="type"><option value=""><?php esc_html_e( 'All request types', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( SC_EI_Privacy_Schema::request_types() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $_GET['type'] ?? '', $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
				<button type="submit" class="button"><?php esc_html_e( 'Filter', 'sustainable-catalyst-engagement-intake' ); ?></button>
			</form>

			<?php if ( current_user_can( 'sc_intake_manage_privacy_requests' ) ) : ?>
				<details class="sc-ei-privacy-create-panel"><summary><?php esc_html_e( 'Create Privacy Request Case', 'sustainable-catalyst-engagement-intake' ); ?></summary>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-review-form-grid">
						<input type="hidden" name="action" value="sc_ei_create_privacy_request"><?php wp_nonce_field( 'sc_ei_create_privacy_request' ); ?>
						<label><span><?php esc_html_e( 'Inquiry ID (optional)', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="1" name="inquiry_id"></label>
						<label><span><?php esc_html_e( 'Requester name', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="requester_name" maxlength="191"></label>
						<label><span><?php esc_html_e( 'Requester email', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="email" name="requester_email" required></label>
						<label><span><?php esc_html_e( 'Request type', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="request_type"><?php foreach ( SC_EI_Privacy_Schema::request_types() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
						<label><span><?php esc_html_e( 'Identity state', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="identity_status"><?php foreach ( SC_EI_Privacy_Schema::identity_statuses() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
						<label><span><?php esc_html_e( 'Source', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="source"><?php foreach ( SC_EI_Privacy_Schema::request_sources() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
						<label><span><?php esc_html_e( 'Assigned owner', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="assigned_user_id"><option value="0"><?php esc_html_e( 'Unassigned', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( $reviewers as $reviewer ) : ?><option value="<?php echo esc_attr( $reviewer->ID ); ?>"><?php echo esc_html( $reviewer->display_name ); ?></option><?php endforeach; ?></select></label>
						<label><span><?php esc_html_e( 'Due date', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="datetime-local" name="due_at"></label>
						<label class="sc-ei-review-form-grid__wide"><span><?php esc_html_e( 'Request summary', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="request_summary" rows="5" required></textarea></label>
						<p class="sc-ei-review-form-grid__wide"><button type="submit" class="button button-primary"><?php esc_html_e( 'Create Case', 'sustainable-catalyst-engagement-intake' ); ?></button></p>
					</form>
				</details>
			<?php endif; ?>

			<div class="sc-ei-privacy-records">
				<?php foreach ( $requests['items'] ?? array() as $request ) : ?>
					<article class="sc-ei-privacy-record <?php echo $request['due_at'] && strtotime( $request['due_at'] . ' UTC' ) < time() && ! in_array( $request['status'], array( 'completed', 'denied', 'withdrawn' ), true ) ? 'sc-ei-privacy-record--danger' : ''; ?>">
						<div class="sc-ei-review-section-header">
							<h3><?php echo esc_html( SC_EI_Privacy_Schema::label( SC_EI_Privacy_Schema::request_types(), $request['request_type'] ) ); ?> · <?php echo esc_html( $request['requester_email'] ); ?></h3>
							<span><?php echo esc_html( SC_EI_Privacy_Schema::label( SC_EI_Privacy_Schema::request_statuses(), $request['status'] ) ); ?></span>
						</div>
						<p><strong><?php esc_html_e( 'Case:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo esc_html( $request['public_id'] ); ?><?php if ( $request['reference'] ) : ?> · <a href="<?php echo esc_url( add_query_arg( array( 'page' => 'sc-engagement-intake', 'action' => 'view', 'inquiry' => absint( $request['inquiry_id'] ) ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $request['reference'] ); ?></a><?php endif; ?></p>
						<p><?php echo nl2br( esc_html( $request['request_summary'] ) ); ?></p>
						<p class="description"><?php echo esc_html( sprintf( __( 'Identity: %1$s · Due: %2$s · Owner: %3$s', 'sustainable-catalyst-engagement-intake' ), SC_EI_Privacy_Schema::label( SC_EI_Privacy_Schema::identity_statuses(), $request['identity_status'] ), $request['due_at'] ? get_date_from_gmt( $request['due_at'], 'M j, Y g:i a' ) : '—', $request['assigned_name'] ?: __( 'Unassigned', 'sustainable-catalyst-engagement-intake' ) ) ); ?></p>
						<?php if ( current_user_can( 'sc_intake_manage_privacy_requests' ) ) : ?>
							<details><summary><?php esc_html_e( 'Update case', 'sustainable-catalyst-engagement-intake' ); ?></summary>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-review-form-grid">
									<input type="hidden" name="action" value="sc_ei_update_privacy_request"><input type="hidden" name="request_id" value="<?php echo esc_attr( $request['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_update_privacy_request_' . absint( $request['id'] ) ); ?>
									<label><span><?php esc_html_e( 'Status', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="status"><?php foreach ( SC_EI_Privacy_Schema::request_statuses() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $request['status'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
									<label><span><?php esc_html_e( 'Identity', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="identity_status"><?php foreach ( SC_EI_Privacy_Schema::identity_statuses() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $request['identity_status'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
									<label><span><?php esc_html_e( 'Owner', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="assigned_user_id"><option value="0"><?php esc_html_e( 'Unassigned', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( $reviewers as $reviewer ) : ?><option value="<?php echo esc_attr( $reviewer->ID ); ?>" <?php selected( absint( $request['assigned_user_id'] ), $reviewer->ID ); ?>><?php echo esc_html( $reviewer->display_name ); ?></option><?php endforeach; ?></select></label>
									<label><span><?php esc_html_e( 'Due', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="datetime-local" name="due_at" value="<?php echo esc_attr( $request['due_at'] ? get_date_from_gmt( $request['due_at'], 'Y-m-d\TH:i' ) : '' ); ?>"></label>
									<label class="sc-ei-review-form-grid__wide"><span><?php esc_html_e( 'Request summary', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="request_summary" rows="4"><?php echo esc_textarea( $request['request_summary'] ); ?></textarea></label>
									<label class="sc-ei-review-form-grid__wide"><span><?php esc_html_e( 'Resolution summary', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="resolution_summary" rows="5"><?php echo esc_textarea( $request['resolution_summary'] ); ?></textarea></label>
									<p class="sc-ei-review-form-grid__wide"><button type="submit" class="button"><?php esc_html_e( 'Save Case', 'sustainable-catalyst-engagement-intake' ); ?></button></p>
								</form>
							</details>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		</section>

	<?php elseif ( 'consent' === $view ) : ?>
		<section class="sc-ei-admin__card sc-ei-admin__card--wide">
			<h2><?php esc_html_e( 'Consent and Authorization Ledger', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><?php esc_html_e( 'The ledger records what was acknowledged, granted, corrected, renewed, withdrawn, or marked not applicable. It does not manufacture consent or determine the correct legal basis.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<?php if ( current_user_can( 'sc_intake_manage_consent' ) ) : ?>
				<details class="sc-ei-privacy-create-panel"><summary><?php esc_html_e( 'Record Consent or Authorization Event', 'sustainable-catalyst-engagement-intake' ); ?></summary>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-review-form-grid">
						<input type="hidden" name="action" value="sc_ei_record_consent_event"><?php wp_nonce_field( 'sc_ei_record_consent_event' ); ?>
						<label><span><?php esc_html_e( 'Inquiry ID', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="1" name="inquiry_id" required></label>
						<label><span><?php esc_html_e( 'Consent type', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="consent_type"><?php foreach ( SC_EI_Privacy_Schema::consent_types() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
						<label><span><?php esc_html_e( 'Action', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="consent_action"><?php foreach ( SC_EI_Privacy_Schema::consent_actions() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
						<label><span><?php esc_html_e( 'Version', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="consent_version" maxlength="80"></label>
						<label><span><?php esc_html_e( 'Recorded basis', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="lawful_basis"><?php foreach ( SC_EI_Privacy_Schema::lawful_bases() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
						<label><span><?php esc_html_e( 'Source', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="source" value="admin" maxlength="40"></label>
						<label><span><?php esc_html_e( 'Occurred at', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="datetime-local" name="occurred_at"></label>
						<label class="sc-ei-review-form-grid__wide"><span><?php esc_html_e( 'Evidence note', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="evidence_text" rows="5"></textarea></label>
						<p class="sc-ei-review-form-grid__wide"><button type="submit" class="button button-primary"><?php esc_html_e( 'Record Event', 'sustainable-catalyst-engagement-intake' ); ?></button></p>
					</form>
				</details>
			<?php endif; ?>
			<table class="widefat striped sc-ei-privacy-table">
				<thead><tr><th><?php esc_html_e( 'Inquiry', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Type / Action', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Version / Basis', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Evidence', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Occurred', 'sustainable-catalyst-engagement-intake' ); ?></th></tr></thead>
				<tbody><?php foreach ( $consents as $event ) : ?><tr>
					<td><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'sc-engagement-intake', 'action' => 'view', 'inquiry' => absint( $event['inquiry_id'] ) ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $event['reference'] ); ?></a><br><span class="description"><?php echo esc_html( $event['contact_name'] ); ?></span></td>
					<td><strong><?php echo esc_html( SC_EI_Privacy_Schema::label( SC_EI_Privacy_Schema::consent_types(), $event['consent_type'] ) ); ?></strong><br><?php echo esc_html( SC_EI_Privacy_Schema::label( SC_EI_Privacy_Schema::consent_actions(), $event['action'] ) ); ?></td>
					<td><?php echo esc_html( $event['consent_version'] ?: '—' ); ?><br><span class="description"><?php echo esc_html( SC_EI_Privacy_Schema::label( SC_EI_Privacy_Schema::lawful_bases(), $event['lawful_basis'] ) ); ?></span></td>
					<td><?php echo esc_html( wp_trim_words( $event['evidence_text'], 22, '…' ) ); ?></td>
					<td><?php echo esc_html( get_date_from_gmt( $event['occurred_at'], 'M j, Y g:i a' ) ); ?><br><span class="description"><?php echo esc_html( $event['source'] ); ?></span></td>
				</tr><?php endforeach; ?></tbody>
			</table>
		</section>

	<?php elseif ( 'holds' === $view ) : ?>
		<section class="sc-ei-admin__card sc-ei-admin__card--wide">
			<h2><?php esc_html_e( 'Legal and Operational Holds', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><?php esc_html_e( 'An active inquiry hold blocks every related retention action. A document hold blocks the selected document and is also considered when an inquiry erasure would depend on deleting that document.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<?php if ( current_user_can( 'sc_intake_manage_legal_holds' ) ) : ?>
				<details class="sc-ei-privacy-create-panel"><summary><?php esc_html_e( 'Place Legal Hold', 'sustainable-catalyst-engagement-intake' ); ?></summary>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-review-form-grid">
						<input type="hidden" name="action" value="sc_ei_place_legal_hold"><?php wp_nonce_field( 'sc_ei_place_legal_hold' ); ?>
						<label><span><?php esc_html_e( 'Inquiry ID', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="1" name="inquiry_id" required></label>
						<label><span><?php esc_html_e( 'Scope', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="scope" data-sc-ei-hold-scope><?php foreach ( SC_EI_Privacy_Schema::hold_scopes() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
						<label data-sc-ei-attachment-hold-field hidden><span><?php esc_html_e( 'Attachment ID', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="1" name="attachment_id"></label>
						<label><span><?php esc_html_e( 'Authority', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="authority" required maxlength="191" placeholder="<?php esc_attr_e( 'Counsel, contract, investigation, preservation instruction', 'sustainable-catalyst-engagement-intake' ); ?>"></label>
						<label><span><?php esc_html_e( 'Review date', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="datetime-local" name="review_at"></label>
						<label class="sc-ei-review-form-grid__wide"><span><?php esc_html_e( 'Hold reason', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="reason" rows="5" required></textarea></label>
						<p class="sc-ei-review-form-grid__wide"><button type="submit" class="button button-primary"><?php esc_html_e( 'Place Hold', 'sustainable-catalyst-engagement-intake' ); ?></button></p>
					</form>
				</details>
			<?php endif; ?>
			<div class="sc-ei-privacy-records">
				<?php foreach ( $holds as $hold ) : ?>
					<article class="sc-ei-privacy-record <?php echo 'active' === $hold['status'] && $hold['review_at'] && strtotime( $hold['review_at'] . ' UTC' ) <= time() ? 'sc-ei-privacy-record--danger' : ''; ?>">
						<div class="sc-ei-review-section-header"><h3><?php echo esc_html( $hold['reference'] ?: __( 'Unlinked hold', 'sustainable-catalyst-engagement-intake' ) ); ?> · <?php echo esc_html( SC_EI_Privacy_Schema::label( SC_EI_Privacy_Schema::hold_scopes(), $hold['scope'] ) ); ?></h3><span><?php echo esc_html( ucfirst( $hold['status'] ) ); ?></span></div>
						<p><strong><?php esc_html_e( 'Authority:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo esc_html( $hold['authority'] ); ?><?php if ( $hold['original_name'] ) : ?> · <?php echo esc_html( $hold['original_name'] ); ?><?php endif; ?></p>
						<p><?php echo nl2br( esc_html( $hold['reason'] ) ); ?></p>
						<p class="description"><?php echo esc_html( sprintf( __( 'Placed %1$s by %2$s · Review %3$s', 'sustainable-catalyst-engagement-intake' ), get_date_from_gmt( $hold['placed_at'], 'M j, Y g:i a' ), $hold['placed_by_name'] ?: __( 'System', 'sustainable-catalyst-engagement-intake' ), $hold['review_at'] ? get_date_from_gmt( $hold['review_at'], 'M j, Y g:i a' ) : '—' ) ); ?></p>
						<?php if ( 'active' === $hold['status'] && current_user_can( 'sc_intake_manage_legal_holds' ) ) : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-action-form">
								<input type="hidden" name="action" value="sc_ei_release_legal_hold"><input type="hidden" name="hold_id" value="<?php echo esc_attr( $hold['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_release_legal_hold_' . absint( $hold['id'] ) ); ?>
								<textarea name="release_reason" rows="3" required placeholder="<?php esc_attr_e( 'Required release reason', 'sustainable-catalyst-engagement-intake' ); ?>"></textarea>
								<button type="submit" class="button"><?php esc_html_e( 'Release Hold', 'sustainable-catalyst-engagement-intake' ); ?></button>
							</form>
						<?php elseif ( $hold['release_reason'] ) : ?><p><strong><?php esc_html_e( 'Release:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo esc_html( $hold['release_reason'] ); ?></p><?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		</section>

	<?php elseif ( 'queue' === $view ) : ?>
		<section class="sc-ei-admin__card sc-ei-admin__card--wide">
			<h2><?php esc_html_e( 'Retention and Erasure Queue', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<div class="sc-ei-diagnostic-warning"><strong><?php esc_html_e( 'No silent deletion:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'Preview and queue operations never erase data. Approval and execution are separate capability-checked operations. Execution requires typing the exact action phrase.', 'sustainable-catalyst-engagement-intake' ); ?></div>
			<div class="sc-ei-retention-controls">
				<?php if ( current_user_can( 'sc_intake_manage_retention_policies' ) ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="sc_ei_preview_retention_center"><?php wp_nonce_field( 'sc_ei_preview_retention_center' ); ?><button type="submit" class="button"><?php esc_html_e( 'Preview Candidates', 'sustainable-catalyst-engagement-intake' ); ?></button></form>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="sc_ei_queue_retention_center"><?php wp_nonce_field( 'sc_ei_queue_retention_center' ); ?><label><span><?php esc_html_e( 'Type QUEUE', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="confirm_queue" required pattern="[Qq][Uu][Ee][Uu][Ee]"></label><button type="submit" class="button button-primary"><?php esc_html_e( 'Queue Candidates', 'sustainable-catalyst-engagement-intake' ); ?></button></form>
				<?php endif; ?>
			</div>
			<?php if ( ! empty( $preview['items'] ) ) : ?>
				<details><summary><?php echo esc_html( sprintf( __( 'Latest preview: %1$d candidates · %2$s', 'sustainable-catalyst-engagement-intake' ), absint( $preview['count'] ), size_format( absint( $preview['total_bytes'] ), 2 ) ) ); ?></summary>
					<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Reference', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Target', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Policy', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Due', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Hold', 'sustainable-catalyst-engagement-intake' ); ?></th></tr></thead><tbody><?php foreach ( $preview['items'] as $item ) : ?><tr><td><?php echo esc_html( $item['reference'] ); ?></td><td><?php echo esc_html( SC_EI_Privacy_Schema::label( SC_EI_Privacy_Schema::target_types(), $item['target_type'] ) . ': ' . $item['label'] ); ?></td><td><?php echo esc_html( $item['policy_key'] . ' v' . $item['policy_version'] ); ?></td><td><?php echo esc_html( $item['due_at'] ); ?></td><td><?php echo $item['hold_active'] ? esc_html__( 'Blocked', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'Clear', 'sustainable-catalyst-engagement-intake' ); ?></td></tr><?php endforeach; ?></tbody></table>
				</details>
			<?php endif; ?>
			<?php if ( $last_queue ) : ?><p class="description"><?php echo esc_html( sprintf( __( 'Last queue scan %1$s: %2$d queued, %3$d existing, %4$d blocked, %5$d failed.', 'sustainable-catalyst-engagement-intake' ), $last_queue['completed_at_utc'] ?? '—', absint( $last_queue['queued_count'] ?? 0 ), absint( $last_queue['existing_count'] ?? 0 ), absint( $last_queue['blocked_count'] ?? 0 ), absint( $last_queue['failed_count'] ?? 0 ) ) ); ?></p><?php endif; ?>

			<form method="get" class="sc-ei-operation-filter-form"><input type="hidden" name="page" value="sc-engagement-intake-privacy"><input type="hidden" name="view" value="queue"><input type="search" name="s" value="<?php echo esc_attr( $_GET['s'] ?? '' ); ?>" placeholder="<?php esc_attr_e( 'Search reference, target, policy, or reason', 'sustainable-catalyst-engagement-intake' ); ?>"><select name="status"><option value=""><?php esc_html_e( 'All states', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( SC_EI_Privacy_Schema::retention_action_statuses() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $_GET['status'] ?? '', $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select><select name="target_type"><option value=""><?php esc_html_e( 'All targets', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( SC_EI_Privacy_Schema::target_types() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $_GET['target_type'] ?? '', $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select><button type="submit" class="button"><?php esc_html_e( 'Filter', 'sustainable-catalyst-engagement-intake' ); ?></button></form>

			<div class="sc-ei-privacy-records">
				<?php foreach ( $actions as $action ) : ?>
					<article class="sc-ei-privacy-record sc-ei-retention-action--<?php echo esc_attr( $action['status'] ); ?>">
						<div class="sc-ei-review-section-header"><h3>#<?php echo esc_html( $action['id'] ); ?> · <?php echo esc_html( $action['reference'] ); ?> · <?php echo esc_html( SC_EI_Privacy_Schema::label( SC_EI_Privacy_Schema::retention_action_types(), $action['action_type'] ) ); ?></h3><span><?php echo esc_html( SC_EI_Privacy_Schema::label( SC_EI_Privacy_Schema::retention_action_statuses(), $action['status'] ) ); ?></span></div>
						<p><strong><?php esc_html_e( 'Target:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo esc_html( SC_EI_Privacy_Schema::label( SC_EI_Privacy_Schema::target_types(), $action['target_type'] ) ); ?> #<?php echo esc_html( $action['target_id'] ); ?><?php if ( $action['original_name'] ) : ?> · <?php echo esc_html( $action['original_name'] ); ?><?php endif; ?></p>
						<p><strong><?php esc_html_e( 'Policy:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo esc_html( $action['policy_key'] . ' v' . $action['policy_version'] ); ?> · <strong><?php esc_html_e( 'Due:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php echo esc_html( $action['due_at'] ?: '—' ); ?></p>
						<p><?php echo nl2br( esc_html( $action['reason'] ) ); ?></p>
						<?php if ( $action['failure_code'] ) : ?><div class="sc-ei-diagnostic-warning"><strong><?php echo esc_html( $action['failure_code'] ); ?></strong> <?php echo esc_html( $action['failure_message'] ); ?></div><?php endif; ?>
						<p class="description"><?php echo esc_html( sprintf( __( 'Proposed by %1$s · Approved by %2$s · Executed by %3$s · action v%4$d', 'sustainable-catalyst-engagement-intake' ), $action['proposed_name'] ?: __( 'System', 'sustainable-catalyst-engagement-intake' ), $action['approved_name'] ?: '—', $action['executed_name'] ?: '—', absint( $action['action_version'] ) ) ); ?></p>
						<div class="sc-ei-retention-action-buttons">
							<?php if ( current_user_can( 'sc_intake_approve_retention_actions' ) && in_array( $action['status'], array( 'queued', 'failed', 'blocked_hold', 'blocked_dependency' ), true ) ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="sc_ei_approve_retention_action"><input type="hidden" name="action_id" value="<?php echo esc_attr( $action['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_approve_retention_action_' . absint( $action['id'] ) ); ?><button type="submit" class="button"><?php esc_html_e( 'Approve', 'sustainable-catalyst-engagement-intake' ); ?></button></form>
							<?php endif; ?>
							<?php if ( current_user_can( 'sc_intake_execute_retention_actions' ) && 'approved' === $action['status'] ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-execute-form"><input type="hidden" name="action" value="sc_ei_execute_retention_action"><input type="hidden" name="action_id" value="<?php echo esc_attr( $action['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_execute_retention_action_' . absint( $action['id'] ) ); ?><label><span><?php echo esc_html( sprintf( __( 'Type EXECUTE %d', 'sustainable-catalyst-engagement-intake' ), absint( $action['id'] ) ) ); ?></span><input type="text" name="confirm_execute" required autocomplete="off"></label><button type="submit" class="button button-primary"><?php esc_html_e( 'Execute and Verify', 'sustainable-catalyst-engagement-intake' ); ?></button></form>
							<?php endif; ?>
							<?php if ( current_user_can( 'sc_intake_approve_retention_actions' ) && ! in_array( $action['status'], array( 'executed', 'canceled' ), true ) ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="sc_ei_cancel_retention_action"><input type="hidden" name="action_id" value="<?php echo esc_attr( $action['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_cancel_retention_action_' . absint( $action['id'] ) ); ?><input type="text" name="cancel_reason" required placeholder="<?php esc_attr_e( 'Cancellation reason', 'sustainable-catalyst-engagement-intake' ); ?>"><button type="submit" class="button"><?php esc_html_e( 'Cancel', 'sustainable-catalyst-engagement-intake' ); ?></button></form>
							<?php endif; ?>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		</section>

	<?php elseif ( 'policies' === $view ) : ?>
		<section class="sc-ei-admin__card sc-ei-admin__card--wide">
			<h2><?php esc_html_e( 'Versioned Retention Policies', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><?php esc_html_e( 'A new save creates a new active version and archives the prior version. Existing queued actions keep their original policy key and version.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<div class="sc-ei-template-grid">
				<?php foreach ( $policies as $policy ) : ?>
					<article class="sc-ei-template-card">
						<div class="sc-ei-review-section-header"><h3><?php echo esc_html( $policy['name'] ); ?></h3><span>v<?php echo esc_html( $policy['version'] ); ?><?php if ( $policy['is_system'] ) : ?> · <?php esc_html_e( 'system', 'sustainable-catalyst-engagement-intake' ); ?><?php endif; ?></span></div>
						<p><code><?php echo esc_html( $policy['policy_key'] ); ?></code></p>
						<dl class="sc-ei-admin__details">
							<dt><?php esc_html_e( 'Target', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Privacy_Schema::label( SC_EI_Privacy_Schema::target_types(), $policy['target_type'] ) ); ?></dd>
							<dt><?php esc_html_e( 'Period', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $policy['retention_days'] ); ?> <?php esc_html_e( 'days', 'sustainable-catalyst-engagement-intake' ); ?></dd>
							<dt><?php esc_html_e( 'Anchor', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Privacy_Schema::label( SC_EI_Privacy_Schema::anchor_fields(), $policy['anchor_field'] ) ); ?></dd>
							<dt><?php esc_html_e( 'Action', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Privacy_Schema::label( SC_EI_Privacy_Schema::retention_action_types(), $policy['action_type'] ) ); ?></dd>
							<dt><?php esc_html_e( 'Recorded basis', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Privacy_Schema::label( SC_EI_Privacy_Schema::lawful_bases(), $policy['legal_basis'] ) ); ?></dd>
							<dt><?php esc_html_e( 'Inquiry statuses', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $policy['status_scope_array'] ? implode( ', ', $policy['status_scope_array'] ) : '—' ); ?></dd>
						</dl>
						<p><?php echo esc_html( $policy['description'] ); ?></p>
					</article>
				<?php endforeach; ?>
			</div>
			<?php if ( current_user_can( 'sc_intake_manage_retention_policies' ) ) : ?>
				<hr><h2><?php esc_html_e( 'Create Policy Version', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-review-form-grid">
					<input type="hidden" name="action" value="sc_ei_save_retention_policy"><?php wp_nonce_field( 'sc_ei_save_retention_policy' ); ?>
					<label><span><?php esc_html_e( 'Policy key', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="policy_key" required pattern="[a-z0-9][a-z0-9_-]{2,79}"></label>
					<label><span><?php esc_html_e( 'Name', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="policy_name" required maxlength="191"></label>
					<label><span><?php esc_html_e( 'Target', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="target_type"><?php foreach ( SC_EI_Privacy_Schema::target_types() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
					<label><span><?php esc_html_e( 'Retention days', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" name="retention_days" min="1" max="36500" required value="365"></label>
					<label><span><?php esc_html_e( 'Anchor', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="anchor_field"><?php foreach ( SC_EI_Privacy_Schema::anchor_fields() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
					<label><span><?php esc_html_e( 'Action', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="action_type"><?php foreach ( SC_EI_Privacy_Schema::retention_action_types() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
					<label><span><?php esc_html_e( 'Recorded basis', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="legal_basis"><?php foreach ( SC_EI_Privacy_Schema::lawful_bases() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
					<fieldset class="sc-ei-review-form-grid__wide"><legend><?php esc_html_e( 'Inquiry status scope', 'sustainable-catalyst-engagement-intake' ); ?></legend><div class="sc-ei-policy-status-grid"><?php foreach ( SC_EI_Statuses::all() as $key => $label ) : ?><label><input type="checkbox" name="status_scope[]" value="<?php echo esc_attr( $key ); ?>"> <?php echo esc_html( $label ); ?></label><?php endforeach; ?></div></fieldset>
					<label class="sc-ei-review-form-grid__wide"><span><?php esc_html_e( 'Description and rationale', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="description" rows="6" required></textarea></label>
					<p class="sc-ei-review-form-grid__wide"><button type="submit" class="button button-primary"><?php esc_html_e( 'Create New Policy Version', 'sustainable-catalyst-engagement-intake' ); ?></button></p>
				</form>
			<?php endif; ?>
		</section>

	<?php else : ?>
		<section class="sc-ei-admin__card sc-ei-admin__card--wide">
			<h2><?php esc_html_e( 'Privacy and Retention Operating Method', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<div class="sc-ei-method-grid">
				<article><h3><?php esc_html_e( 'Data minimization', 'sustainable-catalyst-engagement-intake' ); ?></h3><p><?php esc_html_e( 'Collect only information needed for the inquiry, review, communication, secure document, scheduling, and engagement workflow. Do not use narrative fields as a general archive.', 'sustainable-catalyst-engagement-intake' ); ?></p></article>
				<article><h3><?php esc_html_e( 'Identity verification', 'sustainable-catalyst-engagement-intake' ); ?></h3><p><?php esc_html_e( 'Before disclosing or erasing personal data, record how identity was verified or why verification was deliberately waived. Never place identity documents in public media or ordinary email.', 'sustainable-catalyst-engagement-intake' ); ?></p></article>
				<article><h3><?php esc_html_e( 'Consent evidence', 'sustainable-catalyst-engagement-intake' ); ?></h3><p><?php esc_html_e( 'Record the notice or authorization version, action, source, time, basis, and concise evidence. A ledger entry is evidence of what the system recorded, not a legal conclusion.', 'sustainable-catalyst-engagement-intake' ); ?></p></article>
				<article><h3><?php esc_html_e( 'Legal holds', 'sustainable-catalyst-engagement-intake' ); ?></h3><p><?php esc_html_e( 'Place a hold before preservation-sensitive records become due. Review active holds periodically. Release only with an explicit reason and authority.', 'sustainable-catalyst-engagement-intake' ); ?></p></article>
				<article><h3><?php esc_html_e( 'Queue before execution', 'sustainable-catalyst-engagement-intake' ); ?></h3><p><?php esc_html_e( 'Daily cron may create candidates, but only authorized people can approve and execute them. An inquiry cannot be erased while undeleted private documents remain.', 'sustainable-catalyst-engagement-intake' ); ?></p></article>
				<article><h3><?php esc_html_e( 'Verification and tombstones', 'sustainable-catalyst-engagement-intake' ); ?></h3><p><?php esc_html_e( 'Document deletion verifies physical absence. Redaction preserves references, categorical states, timestamps, hashes where appropriate, and audit evidence without retaining the erased narrative.', 'sustainable-catalyst-engagement-intake' ); ?></p></article>
			</div>
			<div class="sc-ei-diagnostic-warning"><strong><?php esc_html_e( 'Not legal advice:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'Retention periods and privacy workflows must be reviewed for the organization, jurisdictions, agreements, insurance, tax, employment, litigation, and professional obligations that actually apply.', 'sustainable-catalyst-engagement-intake' ); ?></div>
		</section>
	<?php endif; ?>
</div>
