<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$message = sanitize_key( $_GET['sc_ei_msg'] ?? '' );
$base = (array) ( $analytics['base'] ?? array() );
$rate = static function ( array $value ): string {
	return ! empty( $value['suppressed'] ) ? __( 'Suppressed', 'sustainable-catalyst-engagement-intake' ) : ( null === ( $value['percent'] ?? null ) ? '—' : (string) $value['percent'] . '%' );
};
$group = static function ( string $title, array $rows ): void {
	echo '<section class="sc-ei-admin__card"><h2>' . esc_html( $title ) . '</h2><div class="sc-ei-analytics-bars">';
	if ( ! $rows ) echo '<p>' . esc_html__( 'No aggregate data.', 'sustainable-catalyst-engagement-intake' ) . '</p>';
	foreach ( $rows as $row ) {
		$count = ! empty( $row['suppressed'] ) ? '&lt; minimum' : number_format_i18n( (int) $row['count'] );
		echo '<div><span>' . esc_html( ucwords( str_replace( array( '_', '-' ), ' ', (string) $row['label'] ) ) ) . '</span><strong>' . wp_kses_post( $count ) . '</strong></div>';
	}
	echo '</div></section>';
};
?>
<div class="wrap sc-ei-admin sc-ei-analytics-admin">
<header class="sc-ei-admin__header"><div><p class="sc-ei-admin__eyebrow">Aggregate Engagement Intelligence</p><h1>Engagement Analytics and Service Intelligence</h1><p>Understand demand, conversion, support friction, collaboration progress, service capacity, and operational bottlenecks without ranking senders or automating service decisions.</p></div><div class="sc-ei-admin__version">v1.6.0</div></header>
<?php if ( $message ) : ?><div class="notice notice-success"><p><?php echo esc_html( ucwords( str_replace( '_', ' ', $message ) ) ); ?></p></div><?php endif; ?>
<div class="sc-ei-portal-admin-boundary"><strong>Privacy and decision boundary</strong><span>Aggregate counts only. Cohorts below <?php echo esc_html( $analytics['minimum_cohort'] ); ?> are suppressed. Findings require human review. No names, emails, messages, file contents, sender scores, or automatic service decisions appear here.</span></div>
<form method="get" class="sc-ei-operation-filter-form"><input type="hidden" name="page" value="sc-engagement-intake-analytics"><select name="days"><?php foreach ( SC_EI_Analytics_Schema::ranges() as $days => $label ) : ?><option value="<?php echo esc_attr( $days ); ?>" <?php selected( $analytics['range_days'], $days ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select><button class="button">Update range</button><?php if ( current_user_can( 'sc_intake_export_analytics' ) ) : ?><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sc_ei_service_intelligence_export&days=' . $analytics['range_days'] ), 'sc_ei_service_intelligence_export_' . $analytics['range_days'] ) ); ?>">Export aggregate JSON</a><?php endif; ?></form>

<div class="sc-ei-review-metrics">
<?php foreach ( array(
	'Inquiries' => (int) ( $base['funnel']['inquiries'] ?? 0 ),
	'Support cases' => (int) ( $analytics['support']['counts']['cases'] ?? 0 ),
	'Engagements' => (int) ( $analytics['commercial']['counts']['engagements'] ?? 0 ),
	'Active workspaces' => (int) ( $analytics['collaboration']['counts']['active_workspaces'] ?? 0 ),
	'Open findings' => (int) ( ( $metrics['candidate'] ?? 0 ) + ( $metrics['reviewing'] ?? 0 ) ),
) as $label => $value ) : ?><a><strong><?php echo esc_html( number_format_i18n( $value ) ); ?></strong><span><?php echo esc_html( $label ); ?></span></a><?php endforeach; ?>
</div>

<div class="sc-ei-analytics-grid">
<?php $group( 'Support demand by product', (array) ( $analytics['support']['mix']['products'] ?? array() ) ); ?>
<?php $group( 'Support demand by component', (array) ( $analytics['support']['mix']['components'] ?? array() ) ); ?>
<?php $group( 'Product-intelligence signals', (array) ( $analytics['support']['mix']['signals'] ?? array() ) ); ?>
<?php $group( 'Service interests', (array) ( $base['mix']['services'] ?? array() ) ); ?>
</div>

<div class="sc-ei-analytics-grid">
<section class="sc-ei-admin__card"><h2>Support performance</h2><dl class="sc-ei-admin__details"><dt>Triage rate</dt><dd><?php echo esc_html( $rate( (array) $analytics['support']['rates']['triage'] ) ); ?></dd><dt>Resolution rate</dt><dd><?php echo esc_html( $rate( (array) $analytics['support']['rates']['resolution'] ) ); ?></dd><dt>Known-issue match rate</dt><dd><?php echo esc_html( $rate( (array) $analytics['support']['rates']['known_issue_match'] ) ); ?></dd><dt>Median hours to triage</dt><dd><?php echo esc_html( $analytics['support']['timing']['median_hours_to_triage'] ?? '—' ); ?></dd><dt>Median hours to resolution</dt><dd><?php echo esc_html( $analytics['support']['timing']['median_hours_to_resolution'] ?? '—' ); ?></dd></dl></section>
<section class="sc-ei-admin__card"><h2>Collaboration progress</h2><dl class="sc-ei-admin__details"><dt>Workspace completion</dt><dd><?php echo esc_html( $rate( (array) $analytics['collaboration']['rates']['workspace_completion'] ) ); ?></dd><dt>Milestone completion</dt><dd><?php echo esc_html( $rate( (array) $analytics['collaboration']['rates']['milestone_completion'] ) ); ?></dd><dt>Deliverable acceptance</dt><dd><?php echo esc_html( $rate( (array) $analytics['collaboration']['rates']['deliverable_acceptance'] ) ); ?></dd><dt>Changes requested</dt><dd><?php echo esc_html( number_format_i18n( (int) ( $analytics['collaboration']['counts']['deliverable_changes_requested'] ?? 0 ) ) ); ?></dd></dl></section>
<section class="sc-ei-admin__card"><h2>Commercial and institutional flow</h2><dl class="sc-ei-admin__details"><dt>Meeting completion</dt><dd><?php echo esc_html( $rate( (array) $analytics['commercial']['rates']['meeting_completion'] ) ); ?></dd><dt>Proposal acceptance</dt><dd><?php echo esc_html( $rate( (array) $analytics['commercial']['rates']['proposal_acceptance'] ) ); ?></dd><dt>Engagement activation</dt><dd><?php echo esc_html( $rate( (array) $analytics['commercial']['rates']['engagement_activation'] ) ); ?></dd></dl></section>
</div>

<section class="sc-ei-admin__card sc-ei-admin__card--wide"><h2>Operational attention</h2><div class="sc-ei-review-metrics"><?php foreach ( (array) $analytics['operations'] as $key => $value ) : ?><a><strong><?php echo esc_html( number_format_i18n( (int) $value ) ); ?></strong><span><?php echo esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?></span></a><?php endforeach; ?></div></section>

<?php if ( current_user_can( 'sc_intake_manage_analytics' ) ) : ?>
<div class="sc-ei-analytics-grid">
<section class="sc-ei-admin__card"><h2>Auditable aggregate snapshot</h2><p>Save the current v1.6.0 payload and SHA-256 evidence. No direct identifiers or content bodies are stored.</p><p><strong>Current evidence:</strong> <?php echo esc_html( $snapshot_evidence['detail'] ); ?></p><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="sc_ei_service_intelligence_snapshot"><input type="hidden" name="days" value="<?php echo esc_attr( $analytics['range_days'] ); ?>"><?php wp_nonce_field( 'sc_ei_service_intelligence_snapshot' ); ?><input type="text" name="confirmation" placeholder="SNAPSHOT SERVICE INTELLIGENCE" required><button class="button button-primary">Save snapshot</button></form></section>
<section class="sc-ei-admin__card"><h2>Create human-reviewed finding</h2><p>Use aggregate evidence only. Candidate findings do not automatically change services, priorities, roadmaps, or sender outcomes.</p><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="sc_ei_service_intelligence_create_finding"><input type="hidden" name="days" value="<?php echo esc_attr( $analytics['range_days'] ); ?>"><?php wp_nonce_field( 'sc_ei_service_intelligence_create_finding' ); ?><p><input class="regular-text" type="text" name="title" placeholder="Aggregate finding title" required></p><p><select name="finding_type"><?php foreach ( SC_EI_Service_Intelligence_Schema::finding_types() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select> <select name="severity"><?php foreach ( SC_EI_Service_Intelligence_Schema::severities() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></p><p><input type="text" name="service_key" placeholder="service key"> <input type="text" name="product_key" placeholder="product key"> <input type="text" name="component_key" placeholder="component key"></p><p><input type="number" min="0" name="cohort_count" placeholder="cohort count"> <input type="number" step="0.01" name="metric_value" placeholder="metric value"> <input type="text" name="metric_unit" value="count"></p><p><textarea class="large-text" name="aggregate_evidence" rows="4" placeholder="Aggregate evidence and interpretation"></textarea></p><p><input type="text" name="confirmation" placeholder="CREATE AGGREGATE FINDING" required></p><button class="button button-primary">Create candidate finding</button></form></section>
</div>
<?php endif; ?>

<section class="sc-ei-admin__card sc-ei-admin__card--wide"><h2>Service-intelligence findings</h2><table class="widefat striped"><thead><tr><th>Finding</th><th>Type</th><th>Severity</th><th>Status</th><th>Cohort</th><th>Review due</th><th>Human review</th></tr></thead><tbody><?php if ( ! $findings ) : ?><tr><td colspan="7">No aggregate findings recorded.</td></tr><?php endif; ?><?php foreach ( $findings as $finding ) : ?><tr><td><strong><?php echo esc_html( $finding['title'] ); ?></strong><br><code><?php echo esc_html( $finding['public_id'] ); ?></code></td><td><?php echo esc_html( ucwords( str_replace( '_', ' ', $finding['finding_type'] ) ) ); ?></td><td><?php echo esc_html( ucfirst( $finding['severity'] ) ); ?></td><td><?php echo esc_html( ucfirst( $finding['status'] ) ); ?></td><td><?php echo esc_html( number_format_i18n( (int) $finding['cohort_count'] ) ); ?></td><td><?php echo esc_html( $finding['review_due_at'] ); ?> UTC</td><td><?php if ( current_user_can( 'sc_intake_manage_analytics' ) ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="sc_ei_service_intelligence_transition"><input type="hidden" name="finding_id" value="<?php echo esc_attr( $finding['id'] ); ?>"><input type="hidden" name="days" value="<?php echo esc_attr( $analytics['range_days'] ); ?>"><?php wp_nonce_field( 'sc_ei_service_intelligence_transition_' . $finding['id'] ); ?><select name="status"><?php foreach ( SC_EI_Service_Intelligence_Schema::finding_statuses() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $finding['status'], $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select><textarea name="decision_note" rows="2" placeholder="Review decision"></textarea><textarea name="action_summary" rows="2" placeholder="Action summary"></textarea><input type="text" name="confirmation" placeholder="STATUS UUID" required><button class="button">Save reviewed state</button></form><?php else : ?>—<?php endif; ?></td></tr><?php endforeach; ?></tbody></table></section>
</div>
