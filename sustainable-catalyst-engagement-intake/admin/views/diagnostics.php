<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap sc-ei-admin">
	<h1><?php esc_html_e( 'Engagement Intake Diagnostics', 'sustainable-catalyst-engagement-intake' ); ?></h1>
	<p><?php esc_html_e( 'Foundation checks for private records, permissions, privacy tools, and future secure intake services.', 'sustainable-catalyst-engagement-intake' ); ?></p>

	<div class="sc-ei-health sc-ei-health--<?php echo esc_attr( $status ); ?>">
		<strong><?php echo esc_html( 'healthy' === $status ? __( 'Foundation healthy', 'sustainable-catalyst-engagement-intake' ) : __( 'Attention required', 'sustainable-catalyst-engagement-intake' ) ); ?></strong>
		<span><?php echo esc_html( sprintf( __( 'Plugin %1$s · Database %2$s', 'sustainable-catalyst-engagement-intake' ), $diagnostics['plugin_version'], $diagnostics['database_version'] ?: 'not recorded' ) ); ?></span>
	</div>

	<div class="sc-ei-diagnostics-grid">
		<section class="sc-ei-admin__card">
			<h2><?php esc_html_e( 'Database tables', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<ul class="sc-ei-checks">
				<?php foreach ( $diagnostics['tables'] as $name => $ok ) : ?>
					<li><span class="<?php echo $ok ? 'sc-ei-check--ok' : 'sc-ei-check--bad'; ?>"><?php echo $ok ? '●' : '●'; ?></span> <?php echo esc_html( $name ); ?></li>
				<?php endforeach; ?>
			</ul>
		</section>

		<section class="sc-ei-admin__card">
			<h2><?php esc_html_e( 'Administrator capabilities', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<ul class="sc-ei-checks">
				<?php foreach ( $diagnostics['capabilities'] as $cap => $ok ) : ?>
					<li><span class="<?php echo $ok ? 'sc-ei-check--ok' : 'sc-ei-check--bad'; ?>">●</span> <?php echo esc_html( $cap ); ?></li>
				<?php endforeach; ?>
			</ul>
		</section>

		<section class="sc-ei-admin__card">
			<h2><?php esc_html_e( 'Public exposure', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><strong><?php esc_html_e( 'Public forms:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'Enabled through adaptive shortcodes', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<p><strong><?php esc_html_e( 'Public inquiry archive:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'None', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<p><strong><?php esc_html_e( 'REST API:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'Authenticated capability checks required', 'sustainable-catalyst-engagement-intake' ); ?></p>
		</section>

		<section class="sc-ei-admin__card">
			<h2><?php esc_html_e( 'Public shortcodes', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<?php foreach ( $diagnostics['public_shortcodes'] as $shortcode ) : ?>
				<p><code><?php echo esc_html( $shortcode ); ?></code></p>
			<?php endforeach; ?>
		</section>

		<section class="sc-ei-admin__card">
			<h2><?php esc_html_e( 'Submission safeguards', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<ul class="sc-ei-checks">
				<?php foreach ( $diagnostics['spam_controls'] as $control => $ok ) : ?>
					<li><span class="<?php echo $ok ? 'sc-ei-check--ok' : 'sc-ei-check--bad'; ?>">●</span> <?php echo esc_html( ucwords( str_replace( '_', ' ', $control ) ) ); ?></li>
				<?php endforeach; ?>
			</ul>
			<p><strong><?php esc_html_e( 'Page cache:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'Dynamic form rendering requests no-cache headers and sets DONOTCACHEPAGE when supported.', 'sustainable-catalyst-engagement-intake' ); ?></p>
		</section>

		<section class="sc-ei-admin__card">
			<h2><?php esc_html_e( 'Teams database migration', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<ul class="sc-ei-checks">
				<?php foreach ( $diagnostics['teams_columns'] as $column => $ok ) : ?>
					<li><span class="<?php echo $ok ? 'sc-ei-check--ok' : 'sc-ei-check--bad'; ?>">●</span> <?php echo esc_html( $column ); ?></li>
				<?php endforeach; ?>
			</ul>
		</section>

		<section class="sc-ei-admin__card">
			<h2><?php esc_html_e( 'Microsoft Teams readiness', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<ul class="sc-ei-checks">
				<?php foreach ( $diagnostics['teams'] as $control => $value ) : ?>
					<li>
						<?php if ( is_bool( $value ) ) : ?>
							<span class="<?php echo $value ? 'sc-ei-check--ok' : 'sc-ei-check--bad'; ?>">●</span>
						<?php endif; ?>
						<?php echo esc_html( ucwords( str_replace( '_', ' ', $control ) ) ); ?>:
						<?php echo esc_html( is_bool( $value ) ? ( $value ? 'yes' : 'no' ) : $value ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="description"><?php esc_html_e( 'Graph API connected: no is expected in v0.2.1. This release prepares and records Teams scheduling without creating external events.', 'sustainable-catalyst-engagement-intake' ); ?></p>
		</section>

		<section class="sc-ei-admin__card">
			<h2><?php esc_html_e( 'Secure uploads', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><?php echo esc_html( $diagnostics['upload_note'] ); ?></p>
			<p><strong><?php esc_html_e( 'Current state:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'Descriptions and public links only; physical uploads remain disabled.', 'sustainable-catalyst-engagement-intake' ); ?></p>
		</section>

		<section class="sc-ei-admin__card">
			<h2><?php esc_html_e( 'Runtime', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><?php echo esc_html( 'WordPress ' . $diagnostics['wordpress_version'] ); ?></p>
			<p><?php echo esc_html( 'PHP ' . $diagnostics['php_version'] ); ?></p>
			<p><?php echo esc_html( $diagnostics['multisite'] ? 'Multisite: yes' : 'Multisite: no' ); ?></p>
		</section>

		<section class="sc-ei-admin__card">
			<h2><?php esc_html_e( 'Privacy integration', 'sustainable-catalyst-engagement-intake' ); ?></h2>
			<p><?php esc_html_e( 'WordPress personal-data exporter: registered', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<p><?php esc_html_e( 'WordPress personal-data eraser: registered', 'sustainable-catalyst-engagement-intake' ); ?></p>
		</section>
	</div>
</div>
