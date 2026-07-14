<?php
/** @var array $items */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$messages = array(
	'lifecycle_stage_changed' => __( 'Lifecycle stage changed and audited.', 'sustainable-catalyst-engagement-intake' ),
	'lifecycle_workspace_updated' => __( 'Lifecycle workspace updated.', 'sustainable-catalyst-engagement-intake' ),
	'lifecycle_qualification_updated' => __( 'Qualification record updated.', 'sustainable-catalyst-engagement-intake' ),
	'lifecycle_note_added' => __( 'Private internal note added.', 'sustainable-catalyst-engagement-intake' ),
	'lifecycle_task_added' => __( 'Follow-up task added.', 'sustainable-catalyst-engagement-intake' ),
	'lifecycle_task_updated' => __( 'Follow-up task updated.', 'sustainable-catalyst-engagement-intake' ),
);
$stage = $selected ? SC_EI_Lifecycle_Schema::sanitize_stage( (string) ( $selected['lifecycle_stage'] ?: SC_EI_Lifecycle_Schema::map_legacy_status( (string) $selected['status'] ) ) ) : '';
$owner_id = $selected ? absint( $selected['lifecycle_owner_user_id'] ?: $selected['assigned_user_id'] ) : 0;
$dt_value = static function( $value ): string {
	if ( ! $value ) { return ''; }
	try { return ( new DateTimeImmutable( (string) $value, new DateTimeZone( 'UTC' ) ) )->setTimezone( wp_timezone() )->format( 'Y-m-d\TH:i' ); } catch ( Throwable $e ) { return ''; }
};
?>
<div class="wrap sc-ei-admin-wrap">
	<h1><?php esc_html_e( 'Advisory Operations and Engagement Lifecycle', 'sustainable-catalyst-engagement-intake' ); ?></h1>
	<p><?php esc_html_e( 'Manage human-reviewed qualification, lifecycle stages, Microsoft Teams coordination, proposals, private notes, follow-up tasks, and engagement handoff from one governed workspace.', 'sustainable-catalyst-engagement-intake' ); ?></p>
	<?php if ( $message ) : ?><div class="notice <?php echo isset( $messages[ $message ] ) ? 'notice-success' : 'notice-error'; ?> is-dismissible"><p><?php echo esc_html( $messages[ $message ] ?? ucwords( str_replace( '_', ' ', $message ) ) ); ?></p></div><?php endif; ?>

	<div class="sc-ei-metric-grid">
		<div class="sc-ei-metric"><strong><?php echo esc_html( number_format_i18n( $metrics['total'] ) ); ?></strong><span><?php esc_html_e( 'Lifecycle records', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div class="sc-ei-metric"><strong><?php echo esc_html( number_format_i18n( $metrics['qualified'] ) ); ?></strong><span><?php esc_html_e( 'Qualified or advanced', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div class="sc-ei-metric"><strong><?php echo esc_html( number_format_i18n( $metrics['open_tasks'] ) ); ?></strong><span><?php esc_html_e( 'Open tasks', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div class="sc-ei-metric"><strong><?php echo esc_html( number_format_i18n( $metrics['overdue_tasks'] ) ); ?></strong><span><?php esc_html_e( 'Overdue tasks', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div class="sc-ei-metric"><strong><?php echo esc_html( number_format_i18n( $metrics['unassigned'] ) ); ?></strong><span><?php esc_html_e( 'Unassigned', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
		<div class="sc-ei-metric"><strong><?php echo esc_html( number_format_i18n( $metrics['active_engagements'] ) ); ?></strong><span><?php esc_html_e( 'Active engagements', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
	</div>

	<form method="get" class="sc-ei-filter-bar">
		<input type="hidden" name="page" value="sc-engagement-intake-lifecycle">
		<select name="lifecycle_stage"><option value=""><?php esc_html_e( 'All stages', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( SC_EI_Lifecycle_Schema::stages() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $stage_filter, $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
		<select name="lifecycle_priority"><option value=""><?php esc_html_e( 'All priorities', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( SC_EI_Lifecycle_Schema::priorities() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $priority_filter, $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
		<input type="search" name="lifecycle_search" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Reference, person, organization…', 'sustainable-catalyst-engagement-intake' ); ?>">
		<button class="button"><?php esc_html_e( 'Filter', 'sustainable-catalyst-engagement-intake' ); ?></button>
	</form>

	<div class="sc-ei-lifecycle-layout">
		<section class="sc-ei-panel sc-ei-lifecycle-list">
			<h2><?php esc_html_e( 'Lifecycle queue', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<?php if ( ! $items ) : ?><p><?php esc_html_e( 'No inquiries match the current filters.', 'sustainable-catalyst-engagement-intake' ); ?></p><?php endif; ?>
			<?php foreach ( $items as $item ) : $item_stage = SC_EI_Lifecycle_Schema::sanitize_stage( (string) ( $item['lifecycle_stage'] ?: SC_EI_Lifecycle_Schema::map_legacy_status( (string) $item['status'] ) ) ); ?>
				<a class="sc-ei-lifecycle-row <?php echo $selected && absint( $selected['id'] ) === absint( $item['id'] ) ? 'is-selected' : ''; ?>" href="<?php echo esc_url( SC_EI_Lifecycle_Admin::url( absint( $item['id'] ), array( 'lifecycle_stage' => $stage_filter, 'lifecycle_priority' => $priority_filter, 'lifecycle_search' => $search ) ) ); ?>">
					<strong><?php echo esc_html( $item['reference'] . ' · ' . ( $item['organization'] ?: $item['contact_name'] ) ); ?></strong>
					<span><?php echo esc_html( SC_EI_Lifecycle_Schema::label( SC_EI_Lifecycle_Schema::stages(), $item_stage ) ); ?> · <?php echo esc_html( SC_EI_Lifecycle_Schema::label( SC_EI_Lifecycle_Schema::priorities(), (string) $item['lifecycle_priority'] ) ); ?></span>
					<small><?php echo esc_html( $item['next_action'] ?: __( 'No next action recorded', 'sustainable-catalyst-engagement-intake' ) ); ?></small>
				</a>
			<?php endforeach; ?>
		</section>

		<?php if ( $selected ) : ?>
		<main class="sc-ei-lifecycle-workspace">
			<section class="sc-ei-panel">
				<div class="sc-ei-panel__header"><div><p class="sc-ei-eyebrow"><?php echo esc_html( $selected['reference'] ); ?></p><h2><?php echo esc_html( $selected['organization'] ?: $selected['contact_name'] ); ?></h2></div><span class="sc-ei-status-pill"><?php echo esc_html( SC_EI_Lifecycle_Schema::label( SC_EI_Lifecycle_Schema::stages(), $stage ) ); ?></span></div>
				<dl class="sc-ei-details-grid"><dt><?php esc_html_e( 'Contact', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $selected['contact_name'] . ' · ' . $selected['contact_email'] ); ?></dd><dt><?php esc_html_e( 'Route', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $selected['conversion_route'] ?: $selected['inquiry_type'] ); ?></dd><dt><?php esc_html_e( 'Service', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( ucwords( str_replace( '_', ' ', (string) $selected['service_interest'] ) ) ); ?></dd><dt><?php esc_html_e( 'Subject', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $selected['subject'] ); ?></dd></dl>
				<?php if ( $selected['project_summary'] ) : ?><h3><?php esc_html_e( 'Submitted summary', 'sustainable-catalyst-engagement-intake' ); ?></h3><p><?php echo nl2br( esc_html( $selected['project_summary'] ) ); ?></p><?php endif; ?>
			</section>

			<div class="sc-ei-two-column">
			<section class="sc-ei-panel">
				<h2><?php esc_html_e( 'Internal lifecycle workspace', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-admin-form">
					<input type="hidden" name="action" value="sc_ei_lifecycle_workspace"><input type="hidden" name="inquiry_id" value="<?php echo esc_attr( $selected['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_lifecycle_workspace_' . absint( $selected['id'] ) ); ?>
					<label><span><?php esc_html_e( 'Lifecycle owner', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="lifecycle_owner_user_id"><option value="0"><?php esc_html_e( 'Unassigned', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( $users as $user ) : ?><option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( $owner_id, $user->ID ); ?>><?php echo esc_html( $user->display_name . ' · ' . $user->user_email ); ?></option><?php endforeach; ?></select></label>
					<label><span><?php esc_html_e( 'Priority', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="lifecycle_priority"><?php foreach ( SC_EI_Lifecycle_Schema::priorities() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $selected['lifecycle_priority'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
					<label><span><?php esc_html_e( 'Next action', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="next_action" maxlength="255" value="<?php echo esc_attr( $selected['next_action'] ); ?>"></label>
					<label><span><?php esc_html_e( 'Next action due', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="datetime-local" name="next_action_at" value="<?php echo esc_attr( $dt_value( $selected['next_action_at'] ) ); ?>"></label>
					<label><span><?php esc_html_e( 'Sender-safe lifecycle summary', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="sender_lifecycle_summary" rows="5" maxlength="12000"><?php echo esc_textarea( $selected['sender_lifecycle_summary'] ); ?></textarea></label>
					<button class="button button-primary"><?php esc_html_e( 'Update Workspace', 'sustainable-catalyst-engagement-intake' ); ?></button>
				</form>
			</section>

			<section class="sc-ei-panel">
				<h2><?php esc_html_e( 'Lifecycle transition', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<p><?php esc_html_e( 'Transitions are human-authorized, reasoned, versioned, and recorded in the audit timeline. They never automatically reject an inquiry, schedule a meeting, send a proposal, sign an agreement, or activate paid work.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-admin-form">
					<input type="hidden" name="action" value="sc_ei_lifecycle_transition"><input type="hidden" name="inquiry_id" value="<?php echo esc_attr( $selected['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_lifecycle_transition_' . absint( $selected['id'] ) ); ?>
					<label><span><?php esc_html_e( 'New stage', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="lifecycle_stage"><?php foreach ( SC_EI_Lifecycle_Schema::stages() as $key => $label ) : if ( ! SC_EI_Lifecycle_Schema::can_transition( $stage, $key ) ) { continue; } ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $stage, $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
					<label><span><?php esc_html_e( 'Reason', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="transition_reason" rows="4" required maxlength="12000"></textarea></label>
					<label><span><?php esc_html_e( 'Related next action', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="transition_next_action" maxlength="255"></label>
					<label class="sc-ei-check"><input type="checkbox" name="transition_sender_visible" value="1"><span><?php esc_html_e( 'The reason is suitable for a future sender-facing summary. Internal text is still not published automatically.', 'sustainable-catalyst-engagement-intake' ); ?></span></label>
					<label><span><?php echo esc_html( sprintf( __( 'Type MOVE %1$s TO stage_key', 'sustainable-catalyst-engagement-intake' ), strtoupper( $selected['reference'] ) ) ); ?></span><input type="text" name="lifecycle_confirmation" required autocomplete="off" placeholder="<?php echo esc_attr( 'MOVE ' . strtoupper( $selected['reference'] ) . ' TO ' . strtoupper( $stage ) ); ?>"></label>
					<button class="button button-primary"><?php esc_html_e( 'Record Transition', 'sustainable-catalyst-engagement-intake' ); ?></button>
				</form>
			</section>
			</div>

			<section class="sc-ei-panel">
				<h2><?php esc_html_e( 'Structured advisory qualification', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<p><?php esc_html_e( 'Qualification guides human review. It does not automatically reject, rank, price, or commit to an inquiry.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-form-grid">
					<input type="hidden" name="action" value="sc_ei_lifecycle_qualification"><input type="hidden" name="inquiry_id" value="<?php echo esc_attr( $selected['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_lifecycle_qualification_' . absint( $selected['id'] ) ); ?>
					<label><span><?php esc_html_e( 'Qualification status', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="qualification_status"><?php foreach ( SC_EI_Lifecycle_Schema::qualification_statuses() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $selected['qualification_status'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
					<label><span><?php esc_html_e( 'Human-assigned score (0–100)', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="0" max="100" name="qualification_score" value="<?php echo esc_attr( absint( $selected['qualification_score'] ) ); ?>"></label>
					<label><span><?php esc_html_e( 'Decision authority', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="decision_authority"><?php foreach ( SC_EI_Lifecycle_Schema::decision_authority_options() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $selected['decision_authority'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
					<label><span><?php esc_html_e( 'Funding status', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="funding_status"><?php foreach ( SC_EI_Lifecycle_Schema::funding_statuses() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $selected['funding_status'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
					<label><span><?php esc_html_e( 'Sustainable AI Assurance applicability', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="ai_assurance_applicable"><?php foreach ( SC_EI_Lifecycle_Schema::assessment_options() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $selected['ai_assurance_applicable'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
					<label><span><?php esc_html_e( 'Teams conversation readiness', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="teams_readiness"><?php foreach ( SC_EI_Lifecycle_Schema::teams_readiness_options() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $selected['teams_readiness'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
					<label class="sc-ei-form-grid__wide"><span><?php esc_html_e( 'Organizational challenge', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="organizational_challenge" rows="4"><?php echo esc_textarea( $qualification['organizational_challenge'] ?? '' ); ?></textarea></label>
					<label class="sc-ei-form-grid__wide"><span><?php esc_html_e( 'Desired outcome', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="qualification_desired_outcome" rows="4"><?php echo esc_textarea( $qualification['desired_outcome'] ?? $selected['desired_outcome'] ); ?></textarea></label>
					<label class="sc-ei-form-grid__wide"><span><?php esc_html_e( 'Current systems', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="current_systems" rows="4"><?php echo esc_textarea( $qualification['current_systems'] ?? '' ); ?></textarea></label>
					<label class="sc-ei-form-grid__wide"><span><?php esc_html_e( 'Constraints and dependencies', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="constraints" rows="4"><?php echo esc_textarea( $qualification['constraints'] ?? '' ); ?></textarea></label>
					<label class="sc-ei-form-grid__wide"><span><?php esc_html_e( 'Timeline context', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="timeline_context" rows="3"><?php echo esc_textarea( $qualification['timeline_context'] ?? '' ); ?></textarea></label>
					<label class="sc-ei-form-grid__wide"><span><?php esc_html_e( 'Data, privacy, and security requirements', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="privacy_security" rows="4"><?php echo esc_textarea( $qualification['privacy_security'] ?? '' ); ?></textarea></label>
					<label class="sc-ei-form-grid__wide"><span><?php esc_html_e( 'Stakeholders', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="stakeholders" rows="3"><?php echo esc_textarea( $qualification['stakeholders'] ?? '' ); ?></textarea></label>
					<label class="sc-ei-form-grid__wide"><span><?php esc_html_e( 'Qualification rationale', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="qualification_rationale" rows="5"><?php echo esc_textarea( $qualification['qualification_rationale'] ?? '' ); ?></textarea></label>
					<p class="sc-ei-form-grid__wide"><button class="button button-primary"><?php esc_html_e( 'Save Qualification', 'sustainable-catalyst-engagement-intake' ); ?></button></p>
				</form>
			</section>

			<div class="sc-ei-two-column">
			<section class="sc-ei-panel">
				<h2><?php esc_html_e( 'Private internal notes', 'sustainable-catalyst-engagement-intake' ); ?></h2><p><strong><?php esc_html_e( 'Never shown in the Sender Portal.', 'sustainable-catalyst-engagement-intake' ); ?></strong></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-admin-form"><input type="hidden" name="action" value="sc_ei_lifecycle_add_note"><input type="hidden" name="inquiry_id" value="<?php echo esc_attr( $selected['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_lifecycle_add_note_' . absint( $selected['id'] ) ); ?><label><span><?php esc_html_e( 'Note type', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="note_type"><?php foreach ( SC_EI_Lifecycle_Schema::note_types() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label><label><span><?php esc_html_e( 'Note', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="note_body" rows="5" required maxlength="50000"></textarea></label><label class="sc-ei-check"><input type="checkbox" name="is_sensitive" value="1"><span><?php esc_html_e( 'Mark as particularly sensitive internal material', 'sustainable-catalyst-engagement-intake' ); ?></span></label><button class="button"><?php esc_html_e( 'Add Private Note', 'sustainable-catalyst-engagement-intake' ); ?></button></form>
				<div class="sc-ei-timeline"><?php foreach ( $notes as $note ) : ?><article><strong><?php echo esc_html( SC_EI_Lifecycle_Schema::label( SC_EI_Lifecycle_Schema::note_types(), $note['note_type'] ) ); ?></strong><small><?php echo esc_html( get_date_from_gmt( $note['created_at'], 'M j, Y g:i a' ) ); ?><?php echo $note['is_sensitive'] ? ' · ' . esc_html__( 'Sensitive', 'sustainable-catalyst-engagement-intake' ) : ''; ?></small><p><?php echo nl2br( esc_html( $note['note_body'] ) ); ?></p></article><?php endforeach; ?></div>
			</section>

			<section class="sc-ei-panel">
				<h2><?php esc_html_e( 'Follow-up tasks', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-admin-form"><input type="hidden" name="action" value="sc_ei_lifecycle_add_task"><input type="hidden" name="inquiry_id" value="<?php echo esc_attr( $selected['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_lifecycle_add_task_' . absint( $selected['id'] ) ); ?><label><span><?php esc_html_e( 'Task', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="task_title" required maxlength="255"></label><label><span><?php esc_html_e( 'Details', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="task_details" rows="3"></textarea></label><label><span><?php esc_html_e( 'Due', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="datetime-local" name="task_due_at"></label><label><span><?php esc_html_e( 'Priority', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="task_priority"><?php foreach ( SC_EI_Lifecycle_Schema::priorities() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label><label><span><?php esc_html_e( 'Assignee', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="task_assigned_user_id"><option value="0"><?php esc_html_e( 'Unassigned', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( $users as $user ) : ?><option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( $owner_id, $user->ID ); ?>><?php echo esc_html( $user->display_name ); ?></option><?php endforeach; ?></select></label><button class="button"><?php esc_html_e( 'Add Task', 'sustainable-catalyst-engagement-intake' ); ?></button></form>
				<?php foreach ( $tasks as $task ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-task-card"><input type="hidden" name="action" value="sc_ei_lifecycle_update_task"><input type="hidden" name="inquiry_id" value="<?php echo esc_attr( $selected['id'] ); ?>"><input type="hidden" name="task_id" value="<?php echo esc_attr( $task['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_lifecycle_update_task_' . absint( $task['id'] ) ); ?><strong><?php echo esc_html( $task['task_title'] ); ?></strong><p><?php echo nl2br( esc_html( $task['task_details'] ) ); ?></p><select name="task_status"><?php foreach ( SC_EI_Lifecycle_Schema::task_statuses() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $task['task_status'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select><select name="task_priority"><?php foreach ( SC_EI_Lifecycle_Schema::priorities() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $task['priority'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select><input type="datetime-local" name="task_due_at" value="<?php echo esc_attr( $dt_value( $task['due_at'] ) ); ?>"><select name="task_assigned_user_id"><option value="0"><?php esc_html_e( 'Unassigned', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( $users as $user ) : ?><option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( $task['assigned_user_id'], $user->ID ); ?>><?php echo esc_html( $user->display_name ); ?></option><?php endforeach; ?></select><button class="button button-small"><?php esc_html_e( 'Update', 'sustainable-catalyst-engagement-intake' ); ?></button></form><?php endforeach; ?>
			</section>
			</div>

			<section class="sc-ei-panel"><h2><?php esc_html_e( 'Connected operational records', 'sustainable-catalyst-engagement-intake' ); ?></h2><div class="sc-ei-metric-grid"><div class="sc-ei-metric"><strong><?php echo esc_html( count( $meeting_offers ) ); ?></strong><span><?php esc_html_e( 'Teams meeting records', 'sustainable-catalyst-engagement-intake' ); ?></span></div><div class="sc-ei-metric"><strong><?php echo esc_html( count( $proposals ) ); ?></strong><span><?php esc_html_e( 'Proposal records', 'sustainable-catalyst-engagement-intake' ); ?></span></div><div class="sc-ei-metric"><strong><?php echo esc_html( count( $engagements ) ); ?></strong><span><?php esc_html_e( 'Engagement handoffs', 'sustainable-catalyst-engagement-intake' ); ?></span></div></div><p><a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'sc-engagement-intake-workflow', 'inquiry' => absint( $selected['id'] ) ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Open Teams and Proposals', 'sustainable-catalyst-engagement-intake' ); ?></a> <a class="button" href="<?php echo esc_url( SC_EI_Engagement_Admin::url( 0, array( 'inquiry' => absint( $selected['id'] ) ) ) ); ?>"><?php esc_html_e( 'Open Engagement Handoff', 'sustainable-catalyst-engagement-intake' ); ?></a></p></section>

			<section class="sc-ei-panel"><h2><?php esc_html_e( 'Lifecycle audit timeline', 'sustainable-catalyst-engagement-intake' ); ?></h2><div class="sc-ei-timeline"><?php foreach ( $events as $event ) : $payload = json_decode( (string) $event['payload_json'], true ); ?><article><strong><?php echo esc_html( ucwords( str_replace( '_', ' ', $event['event_type'] ) ) ); ?></strong><small><?php echo esc_html( get_date_from_gmt( $event['occurred_at'], 'M j, Y g:i a' ) ); ?></small><?php if ( $event['from_stage'] || $event['to_stage'] ) : ?><p><?php echo esc_html( ( $event['from_stage'] ?: '—' ) . ' → ' . ( $event['to_stage'] ?: '—' ) ); ?></p><?php endif; ?><?php if ( is_array( $payload ) && ! empty( $payload['reason'] ) ) : ?><p><?php echo nl2br( esc_html( $payload['reason'] ) ); ?></p><?php endif; ?></article><?php endforeach; ?></div></section>
		</main>
		<?php endif; ?>
	</div>
</div>
