<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$scope = json_decode( (string) $proposal['scope_json'], true ) ?: array();
$deliverables = json_decode( (string) $proposal['deliverables_json'], true ) ?: array();
$exclusions = json_decode( (string) $proposal['exclusions_json'], true ) ?: array();
$assumptions = json_decode( (string) $proposal['assumptions_json'], true ) ?: array();
?><!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title><?php echo esc_html( $proposal['proposal_number'] . ' · ' . $proposal['title'] ); ?></title>
	<meta name="robots" content="noindex,nofollow,noarchive,nosnippet">
	<style>
		:root{font-family:Arial,Helvetica,sans-serif;color:#171717;background:#fff}
		body{max-width:900px;margin:0 auto;padding:40px 28px;line-height:1.55}
		header{border-top:8px solid #6d1b2b;border-bottom:1px solid #d8d0c2;padding:24px 0;margin-bottom:28px}
		.eyebrow{color:#6d1b2b;text-transform:uppercase;letter-spacing:.08em;font-size:12px;font-weight:700}
		h1{font-size:34px;line-height:1.15;margin:.25rem 0}
		h2{margin-top:28px;font-size:20px}
		.meta{display:grid;grid-template-columns:180px 1fr;gap:8px 16px}
		.meta dt{font-weight:700}.meta dd{margin:0}
		.grid{display:grid;grid-template-columns:1fr 1fr;gap:28px}
		.notice{border-left:5px solid #a66a00;background:#fff8e8;padding:14px 16px;margin:24px 0}
		.integrity{font-family:monospace;word-break:break-all;font-size:12px}
		footer{margin-top:40px;border-top:1px solid #d8d0c2;padding-top:18px;font-size:12px;color:#555}
		@media print{body{max-width:none;padding:0}.no-print{display:none}}
		@media(max-width:700px){.grid{grid-template-columns:1fr}.meta{grid-template-columns:1fr}}
	</style>
</head>
<body>
	<header>
		<p class="eyebrow"><?php esc_html_e( 'Sustainable Catalyst Proposal', 'sustainable-catalyst-engagement-intake' ); ?></p>
		<h1><?php echo esc_html( $proposal['title'] ); ?></h1>
		<p><?php echo esc_html( $proposal['proposal_number'] . ' · Version ' . absint( $proposal['version_number'] ) ); ?></p>
	</header>
	<div class="notice"><strong><?php esc_html_e( 'Non-contractual portal record:', 'sustainable-catalyst-engagement-intake' ); ?></strong> <?php esc_html_e( 'This print view is a proposal record. Portal acceptance is not an electronic signature, executed contract, payment authorization, or active engagement.', 'sustainable-catalyst-engagement-intake' ); ?></div>
	<dl class="meta">
		<dt><?php esc_html_e( 'Inquiry', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $inquiry['reference'] ); ?></dd>
		<dt><?php esc_html_e( 'Prepared for', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $inquiry['contact_name'] . ( $inquiry['organization'] ? ' · ' . $inquiry['organization'] : '' ) ); ?></dd>
		<dt><?php esc_html_e( 'Status', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Workflow_Schema::label( SC_EI_Workflow_Schema::proposal_statuses(), $proposal['status'] ) ); ?></dd>
		<dt><?php esc_html_e( 'Value', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( SC_EI_Workflow_Schema::money_display( absint( $proposal['total_minor'] ), $proposal['currency'] ) ); ?></dd>
		<dt><?php esc_html_e( 'Expires', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( get_date_from_gmt( $proposal['expires_at'], 'M j, Y g:i a' ) . ' UTC' ); ?></dd>
		<dt><?php esc_html_e( 'Content hash', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd class="integrity"><?php echo esc_html( $proposal['content_hash'] ); ?></dd>
	</dl>
	<h2><?php esc_html_e( 'Executive summary', 'sustainable-catalyst-engagement-intake' ); ?></h2>
	<p><?php echo nl2br( esc_html( $proposal['executive_summary'] ) ); ?></p>
	<div class="grid">
		<section><h2><?php esc_html_e( 'Scope', 'sustainable-catalyst-engagement-intake' ); ?></h2><ul><?php foreach ( $scope as $item ) : ?><li><?php echo esc_html( $item ); ?></li><?php endforeach; ?></ul></section>
		<section><h2><?php esc_html_e( 'Deliverables', 'sustainable-catalyst-engagement-intake' ); ?></h2><ul><?php foreach ( $deliverables as $item ) : ?><li><?php echo esc_html( $item ); ?></li><?php endforeach; ?></ul></section>
	</div>
	<?php if ( $exclusions ) : ?><section><h2><?php esc_html_e( 'Exclusions', 'sustainable-catalyst-engagement-intake' ); ?></h2><ul><?php foreach ( $exclusions as $item ) : ?><li><?php echo esc_html( $item ); ?></li><?php endforeach; ?></ul></section><?php endif; ?>
	<?php if ( $assumptions ) : ?><section><h2><?php esc_html_e( 'Assumptions', 'sustainable-catalyst-engagement-intake' ); ?></h2><ul><?php foreach ( $assumptions as $item ) : ?><li><?php echo esc_html( $item ); ?></li><?php endforeach; ?></ul></section><?php endif; ?>
	<?php foreach ( array( 'timeline_text' => __( 'Timeline', 'sustainable-catalyst-engagement-intake' ), 'fee_summary' => __( 'Fee summary', 'sustainable-catalyst-engagement-intake' ), 'payment_terms' => __( 'Payment terms', 'sustainable-catalyst-engagement-intake' ), 'legal_terms' => __( 'Terms and boundaries', 'sustainable-catalyst-engagement-intake' ) ) as $field => $label ) : if ( empty( $proposal[ $field ] ) ) continue; ?><section><h2><?php echo esc_html( $label ); ?></h2><p><?php echo nl2br( esc_html( $proposal[ $field ] ) ); ?></p></section><?php endforeach; ?>
	<p class="no-print"><?php esc_html_e( 'Use your browser’s Print command to print or save this authenticated proposal view as PDF.', 'sustainable-catalyst-engagement-intake' ); ?></p>
	<footer>
		<p><?php esc_html_e( 'Generated from the authenticated Sustainable Catalyst sender portal. Binding terms must appear in a separately executed agreement.', 'sustainable-catalyst-engagement-intake' ); ?></p>
	</footer>
</body>
</html>
