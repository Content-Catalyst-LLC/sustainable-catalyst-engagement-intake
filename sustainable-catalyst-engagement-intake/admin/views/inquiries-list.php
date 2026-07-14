<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap sc-ei-admin">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Engagement Intake', 'sustainable-catalyst-engagement-intake' ); ?></h1>
	<p class="description">
		<?php esc_html_e( 'Private inquiry records are visible only to users with Engagement Intake capabilities. A compact Consulting intake and an advanced Contact Hub now share one private inquiry, Teams, privacy, and audit system.', 'sustainable-catalyst-engagement-intake' ); ?>
	</p>

	<div class="sc-ei-admin__notice">
		<strong><?php esc_html_e( 'v0.12.0 Workflow Core Integration active', 'sustainable-catalyst-engagement-intake' ); ?></strong>
		<span><?php esc_html_e( 'Reviewed plain-text messages, opt-in notifications, communication timelines, follow-up and suppression controls, human review, quarantine operations, Teams readiness, and protected storage are active.', 'sustainable-catalyst-engagement-intake' ); ?></span>
	</div>

	<form method="get">
		<input type="hidden" name="page" value="sc-engagement-intake">
		<?php
		$list_table->search_box( __( 'Search inquiries', 'sustainable-catalyst-engagement-intake' ), 'sc-ei-inquiry-search' );
		$list_table->display();
		?>
	</form>
</div>
