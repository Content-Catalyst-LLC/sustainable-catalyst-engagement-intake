<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap sc-ei-admin">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Engagement Intake', 'sustainable-catalyst-engagement-intake' ); ?></h1>
	<p class="description">
		<?php esc_html_e( 'Private inquiry records are visible only to users with Engagement Intake capabilities. Adaptive public forms and Microsoft Teams scheduling readiness are active in v0.2.1.', 'sustainable-catalyst-engagement-intake' ); ?>
	</p>

	<div class="sc-ei-admin__notice">
		<strong><?php esc_html_e( 'v0.2.1 Teams scheduling readiness active', 'sustainable-catalyst-engagement-intake' ); ?></strong>
		<span><?php esc_html_e( 'Public forms create private inquiry records through validated, rate-limited submission routes. Secure document uploads remain disabled until v0.3.0.', 'sustainable-catalyst-engagement-intake' ); ?></span>
	</div>

	<form method="get">
		<input type="hidden" name="page" value="sc-engagement-intake">
		<?php
		$list_table->search_box( __( 'Search inquiries', 'sustainable-catalyst-engagement-intake' ), 'sc-ei-inquiry-search' );
		$list_table->display();
		?>
	</form>
</div>
