<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$message = isset( $_GET['sc_ei_msg'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_msg'] ) ) : '';
$bulk_result = get_transient( 'sc_ei_bulk_review_result_' . get_current_user_id() );
if ( $bulk_result ) {
	delete_transient( 'sc_ei_bulk_review_result_' . get_current_user_id() );
}
$bulk_result = is_array( $bulk_result ) ? $bulk_result : array();

$tabs = array(
	'queue'       => __( 'Open Queue', 'sustainable-catalyst-engagement-intake' ),
	'mine'        => __( 'My Reviews', 'sustainable-catalyst-engagement-intake' ),
	'unassigned'  => __( 'Unassigned', 'sustainable-catalyst-engagement-intake' ),
	'escalations' => __( 'Escalations', 'sustainable-catalyst-engagement-intake' ),
	'completed'   => __( 'Completed', 'sustainable-catalyst-engagement-intake' ),
	'method'      => __( 'Review Method', 'sustainable-catalyst-engagement-intake' ),
);

$current = array(
	'review_stage'      => isset( $_GET['review_stage'] ) ? sanitize_key( wp_unslash( $_GET['review_stage'] ) ) : '',
	'review_priority'   => isset( $_GET['review_priority'] ) ? sanitize_key( wp_unslash( $_GET['review_priority'] ) ) : '',
	'fit_decision'      => isset( $_GET['fit_decision'] ) ? sanitize_key( wp_unslash( $_GET['fit_decision'] ) ) : '',
	'risk_level'        => isset( $_GET['risk_level'] ) ? sanitize_key( wp_unslash( $_GET['risk_level'] ) ) : '',
	'escalation_status' => isset( $_GET['escalation_status'] ) ? sanitize_key( wp_unslash( $_GET['escalation_status'] ) ) : '',
	'assignee'          => isset( $_GET['assignee'] ) ? sanitize_text_field( wp_unslash( $_GET['assignee'] ) ) : '',
	'due_state'         => isset( $_GET['due_state'] ) ? sanitize_key( wp_unslash( $_GET['due_state'] ) ) : '',
	'status'            => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '',
	'inquiry_type'      => isset( $_GET['inquiry_type'] ) ? sanitize_key( wp_unslash( $_GET['inquiry_type'] ) ) : '',
	'source_page'       => isset( $_GET['source_page'] ) ? sanitize_key( wp_unslash( $_GET['source_page'] ) ) : '',
	's'                 => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
);
?>
<div class="wrap sc-ei-admin sc-ei-review-workspace">
	<h1><?php esc_html_e( 'Administrative Review Workspace', 'sustainable-catalyst-engagement-intake' ); ?></h1>
	<p><?php esc_html_e( 'Assign, assess, document, escalate, and hand off private inquiries through an auditable human review process. The workspace provides structure and visibility but does not automatically accept, reject, score, or route an inquiry.', 'sustainable-catalyst-engagement-intake' ); ?></p>

	<?php if ( 'bulk_review_completed' === $message && $bulk_result ) : ?>
		<div class="notice <?php echo empty( $bulk_result['failed'] ) ? 'notice-success' : 'notice-warning'; ?> is-dismissible">
			<p>
				<?php
				echo esc_html(
					sprintf(
						__( 'Bulk review action %1$s completed: %2$d processed, %3$d succeeded, %4$d failed.', 'sustainable-catalyst-engagement-intake' ),
						ucwords( str_replace( '_', ' ', (string) ( $bulk_result['operation'] ?? '' ) ) ),
						absint( $bulk_result['processed'] ?? 0 ),
						absint( $bulk_result['succeeded'] ?? 0 ),
						absint( $bulk_result['failed'] ?? 0 )
					)
				);
				?>
			</p>
		</div>
	<?php elseif ( 'bulk_review_error' === $message ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'The bulk review operation was rejected. Select inquiries and provide every required action field.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
	<?php endif; ?>

	<div class="sc-ei-review-metrics">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-review&view=queue' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['open_reviews'] ) ); ?></strong><span><?php esc_html_e( 'open reviews', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-review&view=unassigned' ) ); ?>" class="<?php echo $metrics['unassigned'] ? 'sc-ei-review-metric--attention' : ''; ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['unassigned'] ) ); ?></strong><span><?php esc_html_e( 'unassigned', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-review&view=mine' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['my_reviews'] ) ); ?></strong><span><?php esc_html_e( 'assigned to me', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-review&due_state=overdue' ) ); ?>" class="<?php echo $metrics['overdue'] ? 'sc-ei-review-metric--danger' : ''; ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['overdue'] ) ); ?></strong><span><?php esc_html_e( 'overdue', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-review&due_state=due_soon' ) ); ?>" class="<?php echo $metrics['due_soon'] ? 'sc-ei-review-metric--attention' : ''; ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['due_soon'] ) ); ?></strong><span><?php esc_html_e( 'due in 24 hours', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-review&view=escalations' ) ); ?>" class="<?php echo $metrics['escalated'] ? 'sc-ei-review-metric--danger' : ''; ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['escalated'] ) ); ?></strong><span><?php esc_html_e( 'escalated', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-review&review_stage=decision_ready' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['decision_ready'] ) ); ?></strong><span><?php esc_html_e( 'decision ready', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-review&view=completed' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['completed_30d'] ) ); ?></strong><span><?php esc_html_e( 'completed in 30 days', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-review&view=queue' ) ); ?>" class="<?php echo $metrics['document_attention'] ? 'sc-ei-review-metric--attention' : ''; ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['document_attention'] ) ); ?></strong><span><?php esc_html_e( 'document attention', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=sc-engagement-intake-review&view=queue' ) ); ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['meeting_attention'] ) ); ?></strong><span><?php esc_html_e( 'Teams requests pending', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
	</div>

	<nav class="nav-tab-wrapper sc-ei-operation-tabs" aria-label="<?php esc_attr_e( 'Administrative review workspace views', 'sustainable-catalyst-engagement-intake' ); ?>">
		<?php foreach ( $tabs as $key => $label ) : ?>
			<a class="nav-tab <?php echo $view === $key ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( array( 'page' => 'sc-engagement-intake-review', 'view' => $key ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</nav>

	<?php if ( 'method' === $view ) : ?>
		<section class="sc-ei-admin__card sc-ei-admin__card--wide sc-ei-review-method">
			<h2><?php esc_html_e( 'Human Review Method', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><?php esc_html_e( 'The review workspace records professional judgment. It is not an automated fit score, eligibility engine, or final contracting system.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<div class="sc-ei-review-method-grid">
				<div><span>1</span><h3><?php esc_html_e( 'Claim and orient', 'sustainable-catalyst-engagement-intake' ); ?></h3><p><?php esc_html_e( 'Confirm ownership, urgency, sender context, requested service, timing, and any immediate document or privacy alerts.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
				<div><span>2</span><h3><?php esc_html_e( 'Clarify the problem', 'sustainable-catalyst-engagement-intake' ); ?></h3><p><?php esc_html_e( 'Identify the decision, outcome, audience, evidence, constraints, stakeholders, and boundaries of the requested work.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
				<div><span>3</span><h3><?php esc_html_e( 'Assess alignment', 'sustainable-catalyst-engagement-intake' ); ?></h3><p><?php esc_html_e( 'Record a manual fit judgment, confidence, risk level, scope clarity, and evidence readiness without converting them into a hidden score.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
				<div><span>4</span><h3><?php esc_html_e( 'Document rationale', 'sustainable-catalyst-engagement-intake' ); ?></h3><p><?php esc_html_e( 'State why the recommendation is appropriate, what remains unknown, and what conflicts, independence issues, or reputational considerations were checked.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
				<div><span>5</span><h3><?php esc_html_e( 'Choose the next step', 'sustainable-catalyst-engagement-intake' ); ?></h3><p><?php esc_html_e( 'Explicitly select the operational next step and inquiry status. The plugin does not infer or apply a status from the fit decision.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
				<div><span>6</span><h3><?php esc_html_e( 'Complete or escalate', 'sustainable-catalyst-engagement-intake' ); ?></h3><p><?php esc_html_e( 'Finish the checklist and rationale before completion, or request escalation with a specific reason and accountable assignee.', 'sustainable-catalyst-engagement-intake' ); ?></p></div>
			</div>
			<div class="sc-ei-diagnostic-warning"><strong><?php esc_html_e( 'Decision boundary:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'A completed administrative review is an internal recommendation. It does not send a message, schedule a meeting, accept a contract, create a proposal, or disclose private documents.', 'sustainable-catalyst-engagement-intake' ); ?></div>
		</section>
	<?php else : ?>
		<section class="sc-ei-admin__card sc-ei-admin__card--wide sc-ei-review-queue-card">
			<h2><?php echo esc_html( $tabs[ $view ] ); ?></h2>

			<form method="get" class="sc-ei-operation-filter-form sc-ei-review-filter-form">
				<input type="hidden" name="page" value="sc-engagement-intake-review">
				<input type="hidden" name="view" value="<?php echo esc_attr( $view ); ?>">
				<input type="search" name="s" value="<?php echo esc_attr( $current['s'] ); ?>" placeholder="<?php esc_attr_e( 'Search reference, contact, organization, scope, or review rationale', 'sustainable-catalyst-engagement-intake' ); ?>">

				<select name="review_stage"><option value=""><?php esc_html_e( 'All review stages', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( SC_EI_Review_Schema::stages() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current['review_stage'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
				<select name="review_priority"><option value=""><?php esc_html_e( 'All priorities', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( SC_EI_Review_Schema::priorities() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current['review_priority'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
				<select name="fit_decision"><option value=""><?php esc_html_e( 'All fit decisions', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( SC_EI_Review_Schema::fit_decisions() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current['fit_decision'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
				<select name="risk_level"><option value=""><?php esc_html_e( 'All risk levels', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( SC_EI_Review_Schema::risk_levels() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current['risk_level'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
				<select name="assignee"><option value=""><?php esc_html_e( 'All assignees', 'sustainable-catalyst-engagement-intake' ); ?></option><option value="unassigned" <?php selected( $current['assignee'], 'unassigned' ); ?>><?php esc_html_e( 'Unassigned', 'sustainable-catalyst-engagement-intake' ); ?></option><option value="me" <?php selected( $current['assignee'], 'me' ); ?>><?php esc_html_e( 'Assigned to me', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( $reviewers as $reviewer ) : ?><option value="<?php echo esc_attr( $reviewer->ID ); ?>" <?php selected( $current['assignee'], (string) $reviewer->ID ); ?>><?php echo esc_html( $reviewer->display_name ); ?></option><?php endforeach; ?></select>
				<select name="due_state"><option value=""><?php esc_html_e( 'All due states', 'sustainable-catalyst-engagement-intake' ); ?></option><option value="overdue" <?php selected( $current['due_state'], 'overdue' ); ?>><?php esc_html_e( 'Overdue', 'sustainable-catalyst-engagement-intake' ); ?></option><option value="due_soon" <?php selected( $current['due_state'], 'due_soon' ); ?>><?php esc_html_e( 'Due within 24 hours', 'sustainable-catalyst-engagement-intake' ); ?></option><option value="no_due" <?php selected( $current['due_state'], 'no_due' ); ?>><?php esc_html_e( 'No due date', 'sustainable-catalyst-engagement-intake' ); ?></option></select>
				<select name="status"><option value=""><?php esc_html_e( 'All inquiry statuses', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( SC_EI_Statuses::all() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current['status'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
				<select name="inquiry_type"><option value=""><?php esc_html_e( 'All inquiry types', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( SC_EI_Statuses::inquiry_types() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current['inquiry_type'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>

				<button type="submit" class="button"><?php esc_html_e( 'Apply Filters', 'sustainable-catalyst-engagement-intake' ); ?></button>
				<a class="button-link" href="<?php echo esc_url( add_query_arg( array( 'page' => 'sc-engagement-intake-review', 'view' => $view ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Clear', 'sustainable-catalyst-engagement-intake' ); ?></a>
			</form>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="sc_ei_bulk_review">
				<?php wp_nonce_field( 'sc_ei_bulk_review' ); ?>
				<?php $list_table->display(); ?>
			</form>
		</section>
	<?php endif; ?>
</div>
