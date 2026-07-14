<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$messages = array(
	'engagement_handoff_created'              => __( 'Engagement handoff created from the contracted proposal.', 'sustainable-catalyst-engagement-intake' ),
	'engagement_profile_updated'              => __( 'Engagement ownership and onboarding profile updated.', 'sustainable-catalyst-engagement-intake' ),
	'engagement_requirement_added'            => __( 'Onboarding requirement added.', 'sustainable-catalyst-engagement-intake' ),
	'engagement_requirement_updated'          => __( 'Onboarding requirement updated.', 'sustainable-catalyst-engagement-intake' ),
	'engagement_marked_ready'                 => __( 'Engagement marked ready for setup.', 'sustainable-catalyst-engagement-intake' ),
	'engagement_activated'                    => __( 'Engagement activated by authorized staff.', 'sustainable-catalyst-engagement-intake' ),
	'engagement_status_updated'               => __( 'Engagement lifecycle state updated.', 'sustainable-catalyst-engagement-intake' ),
);
$errors = array(
	'engagement_handoff_confirmation_failed' => __( 'Type the required HANDOFF confirmation exactly.', 'sustainable-catalyst-engagement-intake' ),
	'engagement_duplicate_proposal'           => __( 'That contracted proposal already has an engagement handoff.', 'sustainable-catalyst-engagement-intake' ),
	'engagement_proposal_not_contracted'      => __( 'Only a contracted proposal can create an engagement handoff.', 'sustainable-catalyst-engagement-intake' ),
	'engagement_contract_reference_missing'  => __( 'Record the external contract reference first.', 'sustainable-catalyst-engagement-intake' ),
	'engagement_handoff_failed'               => __( 'The atomic handoff failed. The contracted proposal was not changed.', 'sustainable-catalyst-engagement-intake' ),
	'engagement_readiness_incomplete'         => __( 'Required onboarding items, owner assignment, snapshot integrity, proposal state, or privacy state are blocking readiness.', 'sustainable-catalyst-engagement-intake' ),
	'engagement_ready_confirmation_failed'    => __( 'Type the required READY confirmation exactly.', 'sustainable-catalyst-engagement-intake' ),
	'engagement_activation_confirmation_failed'=> __( 'Type the required ACTIVATE confirmation exactly.', 'sustainable-catalyst-engagement-intake' ),
	'engagement_transition_confirmation_failed'=> __( 'Type the required lifecycle confirmation exactly.', 'sustainable-catalyst-engagement-intake' ),
	'engagement_transition_note_required'     => __( 'Record a reason or completion note.', 'sustainable-catalyst-engagement-intake' ),
	'engagement_requirement_note_required'    => __( 'Completed, waived, and blocked requirements need a note.', 'sustainable-catalyst-engagement-intake' ),
	'engagement_profile_conflict'             => __( 'The engagement changed before the update was saved.', 'sustainable-catalyst-engagement-intake' ),
	'engagement_requirement_conflict'         => __( 'The requirement changed before the update was saved.', 'sustainable-catalyst-engagement-intake' ),
);
$is_error = $message && ! isset( $messages[ $message ] );
?>
<div class="wrap sc-ei-admin sc-ei-engagement-admin">
	<header class="sc-ei-admin__header">
		<div>
			<p class="sc-ei-admin__eyebrow"><?php esc_html_e( 'Controlled Operational Handoff', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<h1><?php esc_html_e( 'Proposal and Engagement Handoff', 'sustainable-catalyst-engagement-intake' ); ?></h1>
			<p><?php esc_html_e( 'Preserve the contracted proposal as an immutable commercial snapshot, complete onboarding readiness, and activate the engagement through a separate human decision.', 'sustainable-catalyst-engagement-intake' ); ?></p>
		</div>
		<div class="sc-ei-admin__version">v0.12.0</div>
	</header>

	<?php if ( $message ) : ?>
		<div class="notice <?php echo $is_error ? 'notice-error' : 'notice-success'; ?> is-dismissible"><p><?php echo esc_html( $messages[ $message ] ?? $errors[ $message ] ?? ucwords( str_replace( '_', ' ', $message ) ) ); ?></p></div>
	<?php endif; ?>

	<div class="sc-ei-portal-admin-boundary">
		<strong><?php esc_html_e( 'Fixed boundary', 'sustainable-catalyst-engagement-intake' ); ?></strong>
		<span><?php esc_html_e( 'A contracted proposal may create one handoff. Handoff creation does not activate work. Activation does not generate a contract, invoice, payment, electronic signature, Workbench project, Decision Studio packet, or external project automatically.', 'sustainable-catalyst-engagement-intake' ); ?></span>
	</div>

	<div class="sc-ei-review-metrics">
		<a><strong><?php echo esc_html( number_format_i18n( $metrics['total'] ) ); ?></strong><span><?php esc_html_e( 'engagement records', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a><strong><?php echo esc_html( number_format_i18n( $metrics['handoff_pending'] ) ); ?></strong><span><?php esc_html_e( 'handoff pending', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a><strong><?php echo esc_html( number_format_i18n( $metrics['ready_for_setup'] ) ); ?></strong><span><?php esc_html_e( 'ready for setup', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a><strong><?php echo esc_html( number_format_i18n( $metrics['active'] ) ); ?></strong><span><?php esc_html_e( 'active', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a><strong><?php echo esc_html( number_format_i18n( $metrics['paused'] ) ); ?></strong><span><?php esc_html_e( 'paused', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a class="<?php echo $metrics['blocking_required'] ? 'sc-ei-review-metric--danger' : ''; ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['blocking_required'] ) ); ?></strong><span><?php esc_html_e( 'blocking required items', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
	</div>

	<?php if ( ! $selected ) : ?>
		<div class="sc-ei-engagement-layout">
			<main>
				<section class="sc-ei-admin__card sc-ei-admin__card--wide">
					<h2><?php esc_html_e( 'Engagement Records', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<form method="get" class="sc-ei-operation-filter-form">
						<input type="hidden" name="page" value="sc-engagement-intake-engagements">
						<select name="engagement_status"><option value=""><?php esc_html_e( 'All engagement states', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( SC_EI_Engagement_Schema::statuses() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status_filter, $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
						<button class="button"><?php esc_html_e( 'Filter', 'sustainable-catalyst-engagement-intake' ); ?></button>
					</form>
					<table class="widefat striped">
						<thead><tr><th><?php esc_html_e( 'Engagement', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Sender', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Status', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Owner', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Commercial source', 'sustainable-catalyst-engagement-intake' ); ?></th></tr></thead>
						<tbody>
							<?php if ( ! $engagements ) : ?><tr><td colspan="5"><?php esc_html_e( 'No engagement records match this filter.', 'sustainable-catalyst-engagement-intake' ); ?></td></tr><?php endif; ?>
							<?php foreach ( $engagements as $item ) : ?>
								<tr>
									<td><a href="<?php echo esc_url( SC_EI_Engagement_Admin::url( absint( $item['id'] ) ) ); ?>"><strong><?php echo esc_html( $item['engagement_number'] ); ?></strong></a><br><?php echo esc_html( $item['title'] ); ?></td>
									<td><?php echo esc_html( $item['organization'] ?: $item['contact_name'] ); ?><br><span class="description"><?php echo esc_html( $item['reference'] ); ?></span></td>
									<td><span class="sc-ei-fit-state sc-ei-fit-state--<?php echo esc_attr( $item['status'] ); ?>"><?php echo esc_html( SC_EI_Engagement_Schema::label( SC_EI_Engagement_Schema::statuses(), $item['status'] ) ); ?></span></td>
									<td><?php echo esc_html( $item['owner_name'] ?: __( 'Unassigned', 'sustainable-catalyst-engagement-intake' ) ); ?></td>
									<td><?php echo esc_html( $item['proposal_number'] ); ?><br><span class="description"><?php echo esc_html( $item['contract_reference'] ); ?></span></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</section>
			</main>

			<aside>
				<section class="sc-ei-admin__card">
					<h2><?php esc_html_e( 'Eligible Contracted Proposals', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<?php if ( ! $eligible_proposals ) : ?><p><?php esc_html_e( 'No contracted proposal is waiting for handoff.', 'sustainable-catalyst-engagement-intake' ); ?></p><?php endif; ?>
					<?php foreach ( $eligible_proposals as $proposal ) : ?>
						<article class="sc-ei-engagement-eligible-proposal">
							<strong><?php echo esc_html( $proposal['proposal_number'] ); ?></strong>
							<span><?php echo esc_html( $proposal['title'] ); ?></span>
							<span><?php echo esc_html( SC_EI_Workflow_Schema::money_display( absint( $proposal['total_minor'] ), $proposal['currency'] ) ); ?></span>
							<a class="button" href="<?php echo esc_url( add_query_arg( array( 'page' => 'sc-engagement-intake-engagements', 'proposal' => absint( $proposal['id'] ), 'create' => 1 ), admin_url( 'admin.php' ) ) ); ?>"><?php esc_html_e( 'Prepare Handoff', 'sustainable-catalyst-engagement-intake' ); ?></a>
						</article>
					<?php endforeach; ?>
				</section>
			</aside>
		</div>

		<?php
		$create_proposal = null;
		if ( ! empty( $_GET['create'] ) && $proposal_id ) {
			foreach ( $eligible_proposals as $proposal ) {
				if ( absint( $proposal['id'] ) === $proposal_id ) {
					$create_proposal = $proposal;
					break;
				}
			}
		}
		?>
		<?php if ( $create_proposal && current_user_can( 'sc_intake_create_engagement_handoffs' ) ) : ?>
			<section class="sc-ei-admin__card sc-ei-admin__card--wide sc-ei-engagement-create">
				<h2><?php echo esc_html( sprintf( __( 'Create Handoff from %s', 'sustainable-catalyst-engagement-intake' ), $create_proposal['proposal_number'] ) ); ?></h2>
				<p><?php esc_html_e( 'This creates an immutable contracted-proposal snapshot and onboarding checklist. It does not activate the engagement.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-admin-form sc-ei-engagement-profile-form">
					<input type="hidden" name="action" value="sc_ei_create_engagement_handoff"><input type="hidden" name="proposal_id" value="<?php echo esc_attr( $create_proposal['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_create_engagement_handoff_' . absint( $create_proposal['id'] ) ); ?>
					<label class="sc-ei-portal-admin-form__wide"><span><?php esc_html_e( 'Engagement title', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="engagement_title" value="<?php echo esc_attr( $create_proposal['title'] ); ?>" required></label>
					<label><span><?php esc_html_e( 'Owner', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="owner_user_id"><option value="0"><?php esc_html_e( 'Unassigned', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( $users as $user ) : ?><option value="<?php echo esc_attr( $user->ID ); ?>"><?php echo esc_html( $user->display_name ); ?></option><?php endforeach; ?></select></label>
					<label><span><?php esc_html_e( 'Proposed start', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="date" name="proposed_start_date"></label>
					<label><span><?php esc_html_e( 'Target end', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="date" name="target_end_date"></label>
					<label><span><?php esc_html_e( 'Kickoff state', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="kickoff_status"><?php foreach ( SC_EI_Engagement_Schema::kickoff_statuses() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
					<label><span><?php esc_html_e( 'Kickoff UTC', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="datetime-local" name="kickoff_at"></label>
					<label class="sc-ei-portal-admin-form__wide"><span><?php esc_html_e( 'Internal participants', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="participant_user_ids[]" multiple size="5"><?php foreach ( $users as $user ) : ?><option value="<?php echo esc_attr( $user->ID ); ?>"><?php echo esc_html( $user->display_name ); ?></option><?php endforeach; ?></select></label>
					<label class="sc-ei-portal-admin-form__wide"><span><?php esc_html_e( 'Onboarding summary', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="onboarding_summary" rows="4"></textarea></label>
					<label class="sc-ei-portal-admin-form__wide"><span><?php esc_html_e( 'Sender-visible activation summary', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="sender_summary" rows="4"></textarea></label>
					<label><span><?php esc_html_e( 'External project reference', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="external_project_reference"></label>
					<label><span><?php echo esc_html( 'HANDOFF ' . strtoupper( $create_proposal['proposal_number'] ) ); ?></span><input type="text" name="engagement_confirmation" required autocomplete="off"></label>
					<label class="sc-ei-portal-admin-form__wide"><span><?php esc_html_e( 'Internal notes', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="internal_notes" rows="4"></textarea></label>
					<p class="sc-ei-portal-admin-form__wide"><button class="button button-primary"><?php esc_html_e( 'Create Pending Engagement Handoff', 'sustainable-catalyst-engagement-intake' ); ?></button></p>
				</form>
			</section>
		<?php endif; ?>
	<?php else : ?>
		<?php $participants = json_decode( (string) $selected['participant_user_ids_json'], true ) ?: array(); ?>
		<div class="sc-ei-engagement-detail-header">
			<div>
				<a href="<?php echo esc_url( SC_EI_Engagement_Admin::url() ); ?>">← <?php esc_html_e( 'All engagements', 'sustainable-catalyst-engagement-intake' ); ?></a>
				<h2><?php echo esc_html( $selected['engagement_number'] . ' · ' . $selected['title'] ); ?></h2>
				<p><?php echo esc_html( $selected['reference'] . ' · ' . ( $selected['organization'] ?: $selected['contact_name'] ) ); ?></p>
			</div>
			<span class="sc-ei-fit-state sc-ei-fit-state--<?php echo esc_attr( $selected['status'] ); ?>"><?php echo esc_html( SC_EI_Engagement_Schema::label( SC_EI_Engagement_Schema::statuses(), $selected['status'] ) ); ?></span>
		</div>

		<div class="sc-ei-engagement-layout">
			<main>
				<section class="sc-ei-admin__card sc-ei-admin__card--wide">
					<h2><?php esc_html_e( 'Commercial Handoff Snapshot', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<dl class="sc-ei-admin__details">
						<dt><?php esc_html_e( 'Proposal', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $selected['proposal_number'] ); ?></dd>
						<dt><?php esc_html_e( 'Proposal state', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $selected['proposal_status'] ); ?></dd>
						<dt><?php esc_html_e( 'Contract reference', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $selected['contract_reference'] ); ?></dd>
						<dt><?php esc_html_e( 'Commercial value', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Workflow_Schema::money_display( absint( $selected['total_minor'] ), $selected['currency'] ) ); ?></dd>
						<dt><?php esc_html_e( 'Snapshot version', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $snapshot['snapshot_version'] ?? '—' ); ?></dd>
						<dt><?php esc_html_e( 'Proposal content hash', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><code><?php echo esc_html( $snapshot['proposal_content_hash'] ?? '—' ); ?></code></dd>
						<dt><?php esc_html_e( 'Handoff content hash', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><code><?php echo esc_html( $snapshot['content_hash'] ?? '—' ); ?></code></dd>
						<dt><?php esc_html_e( 'Integrity', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo $snapshot && SC_EI_Engagement_Repository::verify_snapshot( $snapshot ) ? esc_html__( 'Verified', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'Failed or unavailable', 'sustainable-catalyst-engagement-intake' ); ?></dd>
					</dl>
					<?php if ( current_user_can( 'sc_intake_export_engagements' ) ) : ?><p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sc_ei_export_engagement_handoff&engagement=' . absint( $selected['id'] ) ), 'sc_ei_export_engagement_handoff_' . absint( $selected['id'] ) ) ); ?>"><?php esc_html_e( 'Export Private Handoff Package', 'sustainable-catalyst-engagement-intake' ); ?></a></p><?php endif; ?>
				</section>

				<?php if ( current_user_can( 'sc_intake_manage_engagements' ) && ! in_array( $selected['status'], array( 'completed', 'canceled' ), true ) ) : ?>
					<section class="sc-ei-admin__card sc-ei-admin__card--wide">
						<h2><?php esc_html_e( 'Ownership and Onboarding Profile', 'sustainable-catalyst-engagement-intake' ); ?></h2>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-admin-form sc-ei-engagement-profile-form">
							<input type="hidden" name="action" value="sc_ei_update_engagement_profile"><input type="hidden" name="engagement_id" value="<?php echo esc_attr( $selected['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_update_engagement_profile_' . absint( $selected['id'] ) ); ?>
							<label class="sc-ei-portal-admin-form__wide"><span><?php esc_html_e( 'Engagement title', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="engagement_title" value="<?php echo esc_attr( $selected['title'] ); ?>" required></label>
							<label><span><?php esc_html_e( 'Owner', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="owner_user_id"><option value="0"><?php esc_html_e( 'Unassigned', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( $users as $user ) : ?><option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( absint( $selected['owner_user_id'] ), absint( $user->ID ) ); ?>><?php echo esc_html( $user->display_name ); ?></option><?php endforeach; ?></select></label>
							<label><span><?php esc_html_e( 'Proposed start', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="date" name="proposed_start_date" value="<?php echo esc_attr( $selected['proposed_start_date'] ); ?>"></label>
							<label><span><?php esc_html_e( 'Target end', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="date" name="target_end_date" value="<?php echo esc_attr( $selected['target_end_date'] ); ?>"></label>
							<label><span><?php esc_html_e( 'Kickoff state', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="kickoff_status"><?php foreach ( SC_EI_Engagement_Schema::kickoff_statuses() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $selected['kickoff_status'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
							<label><span><?php esc_html_e( 'Kickoff UTC', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="datetime-local" name="kickoff_at" value="<?php echo esc_attr( $selected['kickoff_at'] ? gmdate( 'Y-m-d\TH:i', strtotime( $selected['kickoff_at'] . ' UTC' ) ) : '' ); ?>"></label>
							<label class="sc-ei-portal-admin-form__wide"><span><?php esc_html_e( 'Internal participants', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="participant_user_ids[]" multiple size="5"><?php foreach ( $users as $user ) : ?><option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( in_array( absint( $user->ID ), array_map( 'absint', $participants ), true ) ); ?>><?php echo esc_html( $user->display_name ); ?></option><?php endforeach; ?></select></label>
							<label class="sc-ei-portal-admin-form__wide"><span><?php esc_html_e( 'Onboarding summary', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="onboarding_summary" rows="4"><?php echo esc_textarea( $selected['onboarding_summary'] ); ?></textarea></label>
							<label class="sc-ei-portal-admin-form__wide"><span><?php esc_html_e( 'Sender-visible summary', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="sender_summary" rows="4"><?php echo esc_textarea( $selected['sender_summary'] ); ?></textarea></label>
							<label><span><?php esc_html_e( 'External project reference', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="external_project_reference" value="<?php echo esc_attr( $selected['external_project_reference'] ); ?>"></label>
							<label><span><?php esc_html_e( 'Workbench handoff', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="workbench_handoff_status"><?php foreach ( array( 'not_requested', 'prepared', 'exported', 'acknowledged' ) as $state ) : ?><option value="<?php echo esc_attr( $state ); ?>" <?php selected( $selected['workbench_handoff_status'], $state ); ?>><?php echo esc_html( ucwords( str_replace( '_', ' ', $state ) ) ); ?></option><?php endforeach; ?></select></label>
							<label><span><?php esc_html_e( 'Decision Studio handoff', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="decision_studio_handoff_status"><?php foreach ( array( 'not_requested', 'prepared', 'exported', 'acknowledged' ) as $state ) : ?><option value="<?php echo esc_attr( $state ); ?>" <?php selected( $selected['decision_studio_handoff_status'], $state ); ?>><?php echo esc_html( ucwords( str_replace( '_', ' ', $state ) ) ); ?></option><?php endforeach; ?></select></label>
							<label class="sc-ei-portal-admin-form__wide"><span><?php esc_html_e( 'Internal notes', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="internal_notes" rows="4"><?php echo esc_textarea( $selected['internal_notes'] ); ?></textarea></label>
							<p class="sc-ei-portal-admin-form__wide"><button class="button button-primary"><?php esc_html_e( 'Update Engagement Profile', 'sustainable-catalyst-engagement-intake' ); ?></button></p>
						</form>
					</section>
				<?php endif; ?>

				<section class="sc-ei-admin__card sc-ei-admin__card--wide">
					<h2><?php esc_html_e( 'Onboarding Requirements', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<div class="sc-ei-engagement-requirements">
						<?php foreach ( $requirements as $requirement ) : ?>
							<article class="sc-ei-engagement-requirement">
								<header><div><strong><?php echo esc_html( $requirement['title'] ); ?></strong><span><?php echo esc_html( SC_EI_Engagement_Schema::label( SC_EI_Engagement_Schema::requirement_categories(), $requirement['category'] ) ); ?></span></div><span class="sc-ei-fit-state sc-ei-fit-state--<?php echo esc_attr( $requirement['status'] ); ?>"><?php echo esc_html( SC_EI_Engagement_Schema::label( SC_EI_Engagement_Schema::requirement_statuses(), $requirement['status'] ) ); ?></span></header>
								<p><?php echo esc_html( $requirement['description'] ); ?></p>
								<?php if ( current_user_can( 'sc_intake_manage_engagements' ) && ! in_array( $selected['status'], array( 'completed', 'canceled' ), true ) ) : ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-admin-form sc-ei-engagement-requirement-form">
										<input type="hidden" name="action" value="sc_ei_update_engagement_requirement"><input type="hidden" name="engagement_id" value="<?php echo esc_attr( $selected['id'] ); ?>"><input type="hidden" name="requirement_id" value="<?php echo esc_attr( $requirement['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_update_engagement_requirement_' . absint( $requirement['id'] ) ); ?>
										<label><span><?php esc_html_e( 'State', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="requirement_status"><?php foreach ( SC_EI_Engagement_Schema::requirement_statuses() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $requirement['status'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
										<label><span><?php esc_html_e( 'Due date', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="date" name="due_date" value="<?php echo esc_attr( $requirement['due_date'] ); ?>"></label>
										<label><span><?php esc_html_e( 'Assignee', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="assigned_user_id"><option value="0"><?php esc_html_e( 'Unassigned', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( $users as $user ) : ?><option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( absint( $requirement['assigned_user_id'] ), absint( $user->ID ) ); ?>><?php echo esc_html( $user->display_name ); ?></option><?php endforeach; ?></select></label>
										<label class="sc-ei-check"><input type="checkbox" name="is_required" value="1" <?php checked( $requirement['is_required'], 1 ); ?>><span><?php esc_html_e( 'Required', 'sustainable-catalyst-engagement-intake' ); ?></span></label>
										<label class="sc-ei-check"><input type="checkbox" name="sender_visible" value="1" <?php checked( $requirement['sender_visible'], 1 ); ?>><span><?php esc_html_e( 'Sender visible', 'sustainable-catalyst-engagement-intake' ); ?></span></label>
										<label><span><?php esc_html_e( 'Evidence reference', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="evidence_reference" value="<?php echo esc_attr( $requirement['evidence_reference'] ); ?>"></label>
										<label class="sc-ei-portal-admin-form__wide"><span><?php esc_html_e( 'Completion, waiver, or blocker note', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="completion_note" rows="3"><?php echo esc_textarea( $requirement['completion_note'] ); ?></textarea></label>
										<p class="sc-ei-portal-admin-form__wide"><button class="button"><?php esc_html_e( 'Update Requirement', 'sustainable-catalyst-engagement-intake' ); ?></button></p>
									</form>
								<?php endif; ?>
							</article>
						<?php endforeach; ?>
					</div>

					<?php if ( current_user_can( 'sc_intake_manage_engagements' ) && ! in_array( $selected['status'], array( 'completed', 'canceled' ), true ) ) : ?>
						<details class="sc-ei-engagement-add-requirement"><summary><strong><?php esc_html_e( 'Add Requirement', 'sustainable-catalyst-engagement-intake' ); ?></strong></summary>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-admin-form">
								<input type="hidden" name="action" value="sc_ei_add_engagement_requirement"><input type="hidden" name="engagement_id" value="<?php echo esc_attr( $selected['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_add_engagement_requirement_' . absint( $selected['id'] ) ); ?>
								<label><span><?php esc_html_e( 'Key', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="requirement_key" placeholder="custom_requirement"></label>
								<label><span><?php esc_html_e( 'Title', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="requirement_title" required></label>
								<label><span><?php esc_html_e( 'Category', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="requirement_category"><?php foreach ( SC_EI_Engagement_Schema::requirement_categories() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></label>
								<label><span><?php esc_html_e( 'Due date', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="date" name="due_date"></label>
								<label><span><?php esc_html_e( 'Assignee', 'sustainable-catalyst-engagement-intake' ); ?></span><select name="assigned_user_id"><option value="0"><?php esc_html_e( 'Unassigned', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( $users as $user ) : ?><option value="<?php echo esc_attr( $user->ID ); ?>"><?php echo esc_html( $user->display_name ); ?></option><?php endforeach; ?></select></label>
								<label><span><?php esc_html_e( 'Sort order', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" name="sort_order" min="0" value="100"></label>
								<label class="sc-ei-check"><input type="checkbox" name="is_required" value="1" checked><span><?php esc_html_e( 'Required', 'sustainable-catalyst-engagement-intake' ); ?></span></label>
								<label class="sc-ei-check"><input type="checkbox" name="sender_visible" value="1"><span><?php esc_html_e( 'Sender visible', 'sustainable-catalyst-engagement-intake' ); ?></span></label>
								<label class="sc-ei-portal-admin-form__wide"><span><?php esc_html_e( 'Description', 'sustainable-catalyst-engagement-intake' ); ?></span><textarea name="requirement_description" rows="3"></textarea></label>
								<p class="sc-ei-portal-admin-form__wide"><button class="button"><?php esc_html_e( 'Add Requirement', 'sustainable-catalyst-engagement-intake' ); ?></button></p>
							</form>
						</details>
					<?php endif; ?>
				</section>
			</main>

			<aside>
				<section class="sc-ei-admin__card">
					<h2><?php esc_html_e( 'Readiness Gate', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<ul class="sc-ei-checks"><?php foreach ( (array) ( $readiness['checks'] ?? array() ) as $key => $passed ) : ?><li><span class="<?php echo $passed ? 'sc-ei-check--ok' : 'sc-ei-check--bad'; ?>">●</span> <?php echo esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?></li><?php endforeach; ?></ul>
					<p><strong><?php echo ! empty( $readiness['ready'] ) ? esc_html__( 'Ready', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'Blocked', 'sustainable-catalyst-engagement-intake' ); ?></strong></p>
					<?php if ( 'handoff_pending' === $selected['status'] && current_user_can( 'sc_intake_manage_engagements' ) ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-confirm-form"><input type="hidden" name="action" value="sc_ei_mark_engagement_ready"><input type="hidden" name="engagement_id" value="<?php echo esc_attr( $selected['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_mark_engagement_ready_' . absint( $selected['id'] ) ); ?><input type="text" name="engagement_confirmation" placeholder="<?php echo esc_attr( 'READY ' . strtoupper( $selected['engagement_number'] ) ); ?>" required><button class="button"><?php esc_html_e( 'Mark Ready for Setup', 'sustainable-catalyst-engagement-intake' ); ?></button></form>
					<?php elseif ( 'ready_for_setup' === $selected['status'] && current_user_can( 'sc_intake_activate_engagements' ) ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-confirm-form"><input type="hidden" name="action" value="sc_ei_activate_engagement"><input type="hidden" name="engagement_id" value="<?php echo esc_attr( $selected['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_activate_engagement_' . absint( $selected['id'] ) ); ?><input type="text" name="engagement_confirmation" placeholder="<?php echo esc_attr( 'ACTIVATE ' . strtoupper( $selected['engagement_number'] ) ); ?>" required><button class="button button-primary"><?php esc_html_e( 'Activate Engagement', 'sustainable-catalyst-engagement-intake' ); ?></button></form>
					<?php endif; ?>
				</section>

				<?php if ( current_user_can( 'sc_intake_complete_engagements' ) && in_array( $selected['status'], array( 'handoff_pending', 'ready_for_setup', 'active', 'paused' ), true ) ) : ?>
					<section class="sc-ei-admin__card">
						<h2><?php esc_html_e( 'Lifecycle Control', 'sustainable-catalyst-engagement-intake' ); ?></h2>
						<?php
						$actions = array();
						if ( 'active' === $selected['status'] ) {
							$actions = array( 'paused' => 'PAUSE ', 'completed' => 'COMPLETE ', 'canceled' => 'CANCEL ' );
						} elseif ( 'paused' === $selected['status'] ) {
							$actions = array( 'active' => 'RESUME ', 'completed' => 'COMPLETE ', 'canceled' => 'CANCEL ' );
						} else {
							$actions = array( 'canceled' => 'CANCEL ' );
						}
						?>
						<?php foreach ( $actions as $target => $verb ) : ?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-confirm-form sc-ei-engagement-transition-form"><input type="hidden" name="action" value="sc_ei_change_engagement_status"><input type="hidden" name="engagement_id" value="<?php echo esc_attr( $selected['id'] ); ?>"><input type="hidden" name="engagement_status" value="<?php echo esc_attr( $target ); ?>"><?php wp_nonce_field( 'sc_ei_change_engagement_status_' . absint( $selected['id'] ) ); ?><textarea name="engagement_note" rows="2" required placeholder="<?php esc_attr_e( 'Reason or completion note', 'sustainable-catalyst-engagement-intake' ); ?>"></textarea><input type="text" name="engagement_confirmation" placeholder="<?php echo esc_attr( $verb . strtoupper( $selected['engagement_number'] ) ); ?>" required><button class="button"><?php echo esc_html( SC_EI_Engagement_Schema::label( SC_EI_Engagement_Schema::statuses(), $target ) ); ?></button></form>
						<?php endforeach; ?>
					</section>
				<?php endif; ?>

				<section class="sc-ei-admin__card">
					<h2><?php esc_html_e( 'Integration Handoff', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<dl class="sc-ei-admin__details">
						<dt><?php esc_html_e( 'Workbench', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( ucwords( str_replace( '_', ' ', $selected['workbench_handoff_status'] ) ) ); ?></dd>
						<dt><?php esc_html_e( 'Decision Studio', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( ucwords( str_replace( '_', ' ', $selected['decision_studio_handoff_status'] ) ) ); ?></dd>
						<dt><?php esc_html_e( 'Automatic provisioning', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php esc_html_e( 'Disabled', 'sustainable-catalyst-engagement-intake' ); ?></dd>
					</dl>
				</section>

				<section class="sc-ei-admin__card">
					<h2><?php esc_html_e( 'Engagement Events', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<div class="sc-ei-workflow-event-list"><?php foreach ( array_slice( $events, 0, 40 ) as $event ) : ?><article><strong><?php echo esc_html( SC_EI_Engagement_Schema::label( SC_EI_Engagement_Schema::event_types(), $event['event_type'] ) ); ?></strong><span><?php echo esc_html( get_date_from_gmt( $event['created_at'], 'M j, Y g:i a' ) ); ?></span><span><?php echo esc_html( $event['actor_name'] ?: ucfirst( $event['actor_type'] ) ); ?></span></article><?php endforeach; ?></div>
				</section>
			</aside>
		</div>
	<?php endif; ?>
</div>
