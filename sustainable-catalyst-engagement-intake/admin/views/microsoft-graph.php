<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$messages = array(
	'graph_settings_saved'          => __( 'Microsoft Graph connector settings saved and enabled.', 'sustainable-catalyst-engagement-intake' ),
	'graph_settings_saved_disabled' => __( 'Microsoft Graph connector settings saved. The connector remains disabled.', 'sustainable-catalyst-engagement-intake' ),
	'graph_test_succeeded'          => __( 'Microsoft Graph calendar health check succeeded.', 'sustainable-catalyst-engagement-intake' ),
	'graph_circuit_reset'           => __( 'Microsoft Graph circuit breaker reset.', 'sustainable-catalyst-engagement-intake' ),
	'graph_token_cleared'           => __( 'Encrypted Microsoft Graph token cache cleared.', 'sustainable-catalyst-engagement-intake' ),
	'graph_queue_processed'         => __( 'Due Microsoft Graph operations were processed.', 'sustainable-catalyst-engagement-intake' ),
	'graph_operation_retried'       => __( 'The failed operation was retried with its original idempotency key and encrypted payload.', 'sustainable-catalyst-engagement-intake' ),
);
$errors = array(
	'graph_crypto_unavailable'         => __( 'Sodium or OpenSSL AES-256-GCM is required before credentials can be stored.', 'sustainable-catalyst-engagement-intake' ),
	'graph_credentials_incomplete'     => __( 'Tenant, client, secret, and organizer details are required.', 'sustainable-catalyst-engagement-intake' ),
	'graph_secret_expired'             => __( 'The configured client secret is marked expired.', 'sustainable-catalyst-engagement-intake' ),
	'graph_disabled'                   => __( 'The Microsoft Graph connector is disabled.', 'sustainable-catalyst-engagement-intake' ),
	'graph_circuit_open'               => __( 'The connector circuit is temporarily open after repeated failures.', 'sustainable-catalyst-engagement-intake' ),
	'graph_token_transport_failed'     => __( 'The Microsoft identity token endpoint could not be reached.', 'sustainable-catalyst-engagement-intake' ),
	'graph_transport_failed'           => __( 'Microsoft Graph did not return a calendar response.', 'sustainable-catalyst-engagement-intake' ),
	'graph_settings_confirmation_failed'=> __( 'Type SAVE GRAPH SETTINGS exactly.', 'sustainable-catalyst-engagement-intake' ),
	'graph_test_confirmation_failed'   => __( 'Type TEST GRAPH exactly.', 'sustainable-catalyst-engagement-intake' ),
);
$is_error = $message && ! isset( $messages[ $message ] );
?>
<div class="wrap sc-ei-admin">
	<header class="sc-ei-admin__header">
		<div>
			<p class="sc-ei-admin__eyebrow"><?php esc_html_e( 'Optional Calendar Connector', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<h1><?php esc_html_e( 'Microsoft Graph Reliability', 'sustainable-catalyst-engagement-intake' ); ?></h1>
			<p><?php esc_html_e( 'Create calendar-backed Teams events only after a sender selects an approved time and an authorized staff member deliberately queues the operation. The existing manual Teams-link workflow remains the fallback.', 'sustainable-catalyst-engagement-intake' ); ?></p>
		</div>
		<div class="sc-ei-admin__version">v0.11.0</div>
	</header>

	<?php if ( $message ) : ?>
		<div class="notice <?php echo $is_error ? 'notice-error' : 'notice-success'; ?> is-dismissible"><p><?php echo esc_html( $messages[ $message ] ?? $errors[ $message ] ?? ucwords( str_replace( '_', ' ', $message ) ) ); ?></p></div>
	<?php endif; ?>

	<div class="sc-ei-portal-admin-boundary">
		<strong><?php esc_html_e( 'Fixed operating boundary', 'sustainable-catalyst-engagement-intake' ); ?></strong>
		<span><?php esc_html_e( 'Application-only Microsoft Graph access. Global Microsoft cloud only. No automatic event creation, no automatic retry after permanent failure, no credential display, no Graph-created proposal or contract, and no removal of the manual Teams workflow.', 'sustainable-catalyst-engagement-intake' ); ?></span>
	</div>

	<div class="sc-ei-review-metrics">
		<a><strong><?php echo $settings['graph_enabled'] ? esc_html__( 'Enabled', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'Disabled', 'sustainable-catalyst-engagement-intake' ); ?></strong><span><?php esc_html_e( 'connector state', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a><strong><?php echo $credentials['configured'] ? esc_html__( 'Ready', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'Incomplete', 'sustainable-catalyst-engagement-intake' ); ?></strong><span><?php esc_html_e( 'credential vault', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a class="<?php echo $circuit['open'] ? 'sc-ei-review-metric--danger' : ''; ?>"><strong><?php echo $circuit['open'] ? esc_html__( 'Open', 'sustainable-catalyst-engagement-intake' ) : esc_html__( 'Closed', 'sustainable-catalyst-engagement-intake' ); ?></strong><span><?php esc_html_e( 'circuit breaker', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a><strong><?php echo esc_html( number_format_i18n( $metrics['queued'] ) ); ?></strong><span><?php esc_html_e( 'queued or retrying', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a><strong><?php echo esc_html( number_format_i18n( $metrics['synced_meetings'] ) ); ?></strong><span><?php esc_html_e( 'synced meetings', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
		<a class="<?php echo $metrics['failed'] ? 'sc-ei-review-metric--danger' : ''; ?>"><strong><?php echo esc_html( number_format_i18n( $metrics['failed'] ) ); ?></strong><span><?php esc_html_e( 'permanent failures', 'sustainable-catalyst-engagement-intake' ); ?></span></a>
	</div>

	<div class="sc-ei-graph-admin-layout">
		<main>
			<section class="sc-ei-admin__card sc-ei-admin__card--wide">
				<h2><?php esc_html_e( 'Encrypted Connector Configuration', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<p><?php esc_html_e( 'The client secret and cached access token are stored in authenticated encryption envelopes derived from WordPress salts. The secret is never redisplayed.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				<?php if ( current_user_can( 'sc_intake_manage_graph_settings' ) ) : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-portal-admin-form sc-ei-graph-settings-form">
						<input type="hidden" name="action" value="sc_ei_save_graph_settings">
						<?php wp_nonce_field( 'sc_ei_save_graph_settings' ); ?>
						<label class="sc-ei-check sc-ei-portal-admin-form__wide"><input type="checkbox" name="graph_settings[graph_enabled]" value="1" <?php checked( ! empty( $settings['graph_enabled'] ) ); ?>><span><?php esc_html_e( 'Enable Microsoft Graph after the credential set is complete.', 'sustainable-catalyst-engagement-intake' ); ?></span></label>
						<label><span><?php esc_html_e( 'Microsoft Entra tenant ID or tenant domain', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="graph_settings[graph_tenant_id]" value="<?php echo esc_attr( $settings['graph_tenant_id'] ); ?>" autocomplete="off"></label>
						<label><span><?php esc_html_e( 'Application client ID', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="graph_settings[graph_client_id]" value="<?php echo esc_attr( $settings['graph_client_id'] ); ?>" autocomplete="off"></label>
						<label><span><?php esc_html_e( 'Client secret', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="password" name="graph_settings[graph_client_secret]" value="" autocomplete="new-password" placeholder="<?php echo esc_attr( $credentials['secret_set'] ? __( 'Stored — enter only to replace', 'sustainable-catalyst-engagement-intake' ) : __( 'Required before enabling', 'sustainable-catalyst-engagement-intake' ) ); ?>"></label>
						<label><span><?php esc_html_e( 'Secret expiry date', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="date" name="graph_settings[graph_secret_expires_at]" value="<?php echo esc_attr( $settings['graph_secret_expires_at'] ? gmdate( 'Y-m-d', strtotime( $settings['graph_secret_expires_at'] . ' UTC' ) ) : '' ); ?>"></label>
						<label><span><?php esc_html_e( 'Organizer user principal name', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="email" name="graph_settings[graph_organizer_user]" value="<?php echo esc_attr( $settings['graph_organizer_user'] ); ?>" autocomplete="off"></label>
						<label><span><?php esc_html_e( 'Calendar ID', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="graph_settings[graph_calendar_id]" value="<?php echo esc_attr( $settings['graph_calendar_id'] ); ?>" placeholder="<?php esc_attr_e( 'Leave blank for the organizer default calendar', 'sustainable-catalyst-engagement-intake' ); ?>"></label>
						<label class="sc-ei-check"><input type="checkbox" name="graph_settings[graph_include_sender_attendee]" value="1" <?php checked( ! empty( $settings['graph_include_sender_attendee'] ) ); ?>><span><?php esc_html_e( 'Include the sender as a required attendee only when calendar consent is recorded.', 'sustainable-catalyst-engagement-intake' ); ?></span></label>
						<label class="sc-ei-check"><input type="checkbox" name="graph_settings[graph_allow_remote_cancel]" value="1" <?php checked( ! empty( $settings['graph_allow_remote_cancel'] ) ); ?>><span><?php esc_html_e( 'Allow authorized staff to delete a linked remote event. This may send a cancellation to attendees.', 'sustainable-catalyst-engagement-intake' ); ?></span></label>
						<label class="sc-ei-check"><input type="checkbox" name="graph_settings[graph_retry_enabled]" value="1" <?php checked( ! empty( $settings['graph_retry_enabled'] ) ); ?>><span><?php esc_html_e( 'Retry retryable idempotent operations.', 'sustainable-catalyst-engagement-intake' ); ?></span></label>
						<label><span><?php esc_html_e( 'Maximum attempts', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="1" max="20" name="graph_settings[graph_max_attempts]" value="<?php echo esc_attr( $settings['graph_max_attempts'] ); ?>"></label>
						<label><span><?php esc_html_e( 'Base retry seconds', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="15" max="900" name="graph_settings[graph_retry_base_seconds]" value="<?php echo esc_attr( $settings['graph_retry_base_seconds'] ); ?>"></label>
						<label><span><?php esc_html_e( 'Maximum retry seconds', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="60" max="86400" name="graph_settings[graph_retry_max_seconds]" value="<?php echo esc_attr( $settings['graph_retry_max_seconds'] ); ?>"></label>
						<label><span><?php esc_html_e( 'Request timeout seconds', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="10" max="60" name="graph_settings[graph_request_timeout_seconds]" value="<?php echo esc_attr( $settings['graph_request_timeout_seconds'] ); ?>"></label>
						<label><span><?php esc_html_e( 'Circuit failure threshold', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="2" max="20" name="graph_settings[graph_circuit_failure_threshold]" value="<?php echo esc_attr( $settings['graph_circuit_failure_threshold'] ); ?>"></label>
						<label><span><?php esc_html_e( 'Circuit cooldown minutes', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="1" max="1440" name="graph_settings[graph_circuit_cooldown_minutes]" value="<?php echo esc_attr( $settings['graph_circuit_cooldown_minutes'] ); ?>"></label>
						<label><span><?php esc_html_e( 'Join URL reconciliation delay', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="number" min="10" max="900" name="graph_settings[graph_reconcile_delay_seconds]" value="<?php echo esc_attr( $settings['graph_reconcile_delay_seconds'] ); ?>"></label>
						<label class="sc-ei-check"><input type="checkbox" name="graph_settings[graph_clear_client_secret]" value="1"><span><?php esc_html_e( 'Clear the stored client secret and disable readiness.', 'sustainable-catalyst-engagement-intake' ); ?></span></label>
						<label class="sc-ei-portal-admin-form__wide"><span><?php esc_html_e( 'Typed confirmation', 'sustainable-catalyst-engagement-intake' ); ?></span><input type="text" name="graph_confirmation" required autocomplete="off" placeholder="SAVE GRAPH SETTINGS"></label>
						<p class="sc-ei-portal-admin-form__wide"><button type="submit" class="button button-primary"><?php esc_html_e( 'Save Encrypted Graph Settings', 'sustainable-catalyst-engagement-intake' ); ?></button></p>
					</form>
				<?php else : ?>
					<p><?php esc_html_e( 'Only administrators with the Graph settings capability can change application credentials.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				<?php endif; ?>
			</section>

			<section class="sc-ei-admin__card sc-ei-admin__card--wide">
				<h2><?php esc_html_e( 'Operation Queue', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<form method="get" class="sc-ei-operation-filter-form">
					<input type="hidden" name="page" value="sc-engagement-intake-graph">
					<select name="graph_status"><option value=""><?php esc_html_e( 'All states', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( array( 'pending', 'retry_wait', 'processing', 'succeeded', 'permanent_failure' ) as $state ) : ?><option value="<?php echo esc_attr( $state ); ?>" <?php selected( $status_filter, $state ); ?>><?php echo esc_html( ucwords( str_replace( '_', ' ', $state ) ) ); ?></option><?php endforeach; ?></select>
					<select name="graph_type"><option value=""><?php esc_html_e( 'All operations', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( array( 'create', 'reconcile', 'delete' ) as $type ) : ?><option value="<?php echo esc_attr( $type ); ?>" <?php selected( $type_filter, $type ); ?>><?php echo esc_html( ucfirst( $type ) ); ?></option><?php endforeach; ?></select>
					<button class="button"><?php esc_html_e( 'Filter', 'sustainable-catalyst-engagement-intake' ); ?></button>
				</form>
				<table class="widefat striped sc-ei-graph-operation-table">
					<thead><tr><th><?php esc_html_e( 'Operation', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Inquiry / meeting', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'State', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Attempts', 'sustainable-catalyst-engagement-intake' ); ?></th><th><?php esc_html_e( 'Diagnostics', 'sustainable-catalyst-engagement-intake' ); ?></th></tr></thead>
					<tbody>
						<?php if ( ! $operations ) : ?><tr><td colspan="5"><?php esc_html_e( 'No Graph operations match this filter.', 'sustainable-catalyst-engagement-intake' ); ?></td></tr><?php endif; ?>
						<?php foreach ( $operations as $operation ) : ?>
							<tr>
								<td><strong>#<?php echo esc_html( $operation['id'] ); ?> · <?php echo esc_html( ucfirst( $operation['operation_type'] ) ); ?></strong><br><span class="description"><?php echo esc_html( get_date_from_gmt( $operation['created_at'], 'M j, Y g:i a' ) ); ?></span></td>
								<td><?php if ( $operation['inquiry_id'] ) : ?><a href="<?php echo esc_url( SC_EI_Workflow_Admin::url( absint( $operation['inquiry_id'] ) ) ); ?>"><strong><?php echo esc_html( $operation['reference'] ?: '#' . $operation['inquiry_id'] ); ?></strong></a><?php else : ?>—<?php endif; ?><br><?php echo esc_html( $operation['offer_number'] ?: '#' . $operation['meeting_offer_id'] ); ?></td>
								<td><span class="sc-ei-fit-state sc-ei-fit-state--<?php echo esc_attr( $operation['status'] ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $operation['status'] ) ) ); ?></span><?php if ( $operation['next_retry_at'] ) : ?><br><span class="description"><?php echo esc_html( sprintf( __( 'Next: %s UTC', 'sustainable-catalyst-engagement-intake' ), $operation['next_retry_at'] ) ); ?></span><?php endif; ?></td>
								<td><?php echo esc_html( absint( $operation['attempt_count'] ) . ' / ' . absint( $operation['max_attempts'] ) ); ?><br><span class="description"><?php echo esc_html( $operation['actor_name'] ?: __( 'System', 'sustainable-catalyst-engagement-intake' ) ); ?></span></td>
								<td><?php if ( $operation['graph_error_code'] ) : ?><strong><?php echo esc_html( $operation['graph_error_code'] ); ?></strong><br><?php echo esc_html( $operation['graph_error_message'] ); ?><br><?php endif; ?><span class="description"><?php echo esc_html( $operation['request_id'] ? 'request-id ' . $operation['request_id'] : '' ); ?></span>
									<?php if ( 'permanent_failure' === $operation['status'] && current_user_can( 'sc_intake_reconcile_graph_events' ) ) : ?>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-confirm-form sc-ei-graph-retry-form">
											<input type="hidden" name="action" value="sc_ei_retry_graph_operation"><input type="hidden" name="graph_operation_id" value="<?php echo esc_attr( $operation['id'] ); ?>"><?php wp_nonce_field( 'sc_ei_retry_graph_operation_' . absint( $operation['id'] ) ); ?>
											<input type="text" name="graph_confirmation" required autocomplete="off" placeholder="<?php echo esc_attr( 'RETRY GRAPH ' . absint( $operation['id'] ) ); ?>">
											<button class="button"><?php esc_html_e( 'Retry Same Operation', 'sustainable-catalyst-engagement-intake' ); ?></button>
										</form>
									<?php endif; ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</section>
		</main>

		<aside>
			<section class="sc-ei-admin__card">
				<h2><?php esc_html_e( 'Connector Status', 'sustainable-catalyst-engagement-intake' ); ?></h2>
				<dl class="sc-ei-admin__details">
					<dt><?php esc_html_e( 'Encryption', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $crypto['available'] ? $crypto['preferred'] : __( 'unavailable', 'sustainable-catalyst-engagement-intake' ) ); ?></dd>
					<dt><?php esc_html_e( 'Tenant', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $credentials['tenant_id_masked'] ?: '—' ); ?></dd>
					<dt><?php esc_html_e( 'Client', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $credentials['client_id_masked'] ?: '—' ); ?></dd>
					<dt><?php esc_html_e( 'Secret fingerprint', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $credentials['secret_fingerprint'] ?: '—' ); ?></dd>
					<dt><?php esc_html_e( 'Secret expiry', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $credentials['secret_expires_at'] ?: '—' ); ?></dd>
					<dt><?php esc_html_e( 'Organizer', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $credentials['organizer_user'] ?: '—' ); ?></dd>
					<dt><?php esc_html_e( 'Last health', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $health['checked_at'] ?: __( 'not run', 'sustainable-catalyst-engagement-intake' ) ); ?></dd>
					<dt><?php esc_html_e( 'Health result', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo $health['ok'] ? esc_html__( 'passed', 'sustainable-catalyst-engagement-intake' ) : esc_html( $health['error_code'] ?: __( 'unknown', 'sustainable-catalyst-engagement-intake' ) ); ?></dd>
					<dt><?php esc_html_e( 'Next catch-up', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $next_catchup ? gmdate( 'Y-m-d H:i:s', $next_catchup ) . ' UTC' : __( 'not scheduled', 'sustainable-catalyst-engagement-intake' ) ); ?></dd>
					<dt><?php esc_html_e( 'Next queue run', 'sustainable-catalyst-engagement-intake' ); ?></dt><dd><?php echo esc_html( $next_process ? gmdate( 'Y-m-d H:i:s', $next_process ) . ' UTC' : __( 'none', 'sustainable-catalyst-engagement-intake' ) ); ?></dd>
				</dl>
			</section>

			<?php if ( current_user_can( 'sc_intake_manage_graph_settings' ) ) : ?>
				<section class="sc-ei-admin__card">
					<h2><?php esc_html_e( 'Connector Controls', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<?php foreach ( array(
						array( 'action' => 'sc_ei_test_graph', 'nonce' => 'sc_ei_test_graph', 'confirmation' => 'TEST GRAPH', 'label' => __( 'Test Calendar Access', 'sustainable-catalyst-engagement-intake' ) ),
						array( 'action' => 'sc_ei_reset_graph_circuit', 'nonce' => 'sc_ei_reset_graph_circuit', 'confirmation' => 'RESET GRAPH CIRCUIT', 'label' => __( 'Reset Circuit Breaker', 'sustainable-catalyst-engagement-intake' ) ),
						array( 'action' => 'sc_ei_clear_graph_token', 'nonce' => 'sc_ei_clear_graph_token', 'confirmation' => 'CLEAR GRAPH TOKEN', 'label' => __( 'Clear Token Cache', 'sustainable-catalyst-engagement-intake' ) ),
					) as $control ) : ?>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-confirm-form">
							<input type="hidden" name="action" value="<?php echo esc_attr( $control['action'] ); ?>">
							<?php wp_nonce_field( $control['nonce'] ); ?>
							<input type="text" name="graph_confirmation" required autocomplete="off" placeholder="<?php echo esc_attr( $control['confirmation'] ); ?>">
							<button class="button"><?php echo esc_html( $control['label'] ); ?></button>
						</form>
					<?php endforeach; ?>
				</section>
			<?php endif; ?>

			<?php if ( current_user_can( 'sc_intake_reconcile_graph_events' ) ) : ?>
				<section class="sc-ei-admin__card">
					<h2><?php esc_html_e( 'Queue Recovery', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="sc-ei-inline-confirm-form">
						<input type="hidden" name="action" value="sc_ei_process_graph_queue">
						<?php wp_nonce_field( 'sc_ei_process_graph_queue' ); ?>
						<input type="text" name="graph_confirmation" required autocomplete="off" placeholder="PROCESS GRAPH QUEUE">
						<button class="button"><?php esc_html_e( 'Process Due Operations', 'sustainable-catalyst-engagement-intake' ); ?></button>
					</form>
				</section>
			<?php endif; ?>

			<?php if ( current_user_can( 'sc_intake_export_graph_operations' ) ) : ?>
				<section class="sc-ei-admin__card">
					<h2><?php esc_html_e( 'Redacted Export', 'sustainable-catalyst-engagement-intake' ); ?></h2>
					<p><?php esc_html_e( 'Exports operation state and diagnostics without encrypted payloads, credentials, secrets, or access tokens.', 'sustainable-catalyst-engagement-intake' ); ?></p>
					<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sc_ei_export_graph_operations' ), 'sc_ei_export_graph_operations' ) ); ?>"><?php esc_html_e( 'Export Graph Operations', 'sustainable-catalyst-engagement-intake' ); ?></a>
				</section>
			<?php endif; ?>
		</aside>
	</div>
</div>
