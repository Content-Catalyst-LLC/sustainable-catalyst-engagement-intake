<?php
/**
 * Static v0.5.0 release checks that do not load WordPress.
 */

$root   = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';

$required = array(
	$plugin . '/sustainable-catalyst-engagement-intake.php',
	$plugin . '/includes/class-sc-ei-communication-schema.php',
	$plugin . '/includes/class-sc-ei-template-repository.php',
	$plugin . '/includes/class-sc-ei-communication-repository.php',
	$plugin . '/includes/class-sc-ei-mailer.php',
	$plugin . '/includes/class-sc-ei-notification-service.php',
	$plugin . '/includes/class-sc-ei-communication-list-table.php',
	$plugin . '/includes/class-sc-ei-communication-admin.php',
	$plugin . '/admin/views/communications.php',
	$plugin . '/admin/views/communication-thread.php',
	$plugin . '/includes/class-sc-ei-review-schema.php',
	$plugin . '/includes/class-sc-ei-review-repository.php',
	$plugin . '/admin/views/review-workspace.php',
	$plugin . '/includes/class-sc-ei-file-scanner.php',
	$plugin . '/includes/class-sc-ei-storage.php',
	$plugin . '/includes/class-sc-ei-privacy.php',
	$plugin . '/includes/class-sc-ei-diagnostics.php',
	$plugin . '/assets/js/public.js',
	$plugin . '/assets/js/admin.js',
	$plugin . '/assets/css/public.css',
	$plugin . '/assets/css/admin.css',
	$plugin . '/uninstall.php',
);

$failures = array();
foreach ( $required as $file ) {
	if ( ! is_file( $file ) ) {
		$failures[] = 'Missing: ' . $file;
	}
}

$files = array(
	'main'       => file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' ),
	'database'   => file_get_contents( $plugin . '/includes/class-sc-ei-database.php' ),
	'repository' => file_get_contents( $plugin . '/includes/class-sc-ei-communication-repository.php' ),
	'mailer'     => file_get_contents( $plugin . '/includes/class-sc-ei-mailer.php' ),
	'service'    => file_get_contents( $plugin . '/includes/class-sc-ei-notification-service.php' ),
	'admin'      => file_get_contents( $plugin . '/includes/class-sc-ei-communication-admin.php' ),
	'thread'     => file_get_contents( $plugin . '/admin/views/communication-thread.php' ),
	'privacy'    => file_get_contents( $plugin . '/includes/class-sc-ei-privacy.php' ),
	'diagnostics'=> file_get_contents( $plugin . '/includes/class-sc-ei-diagnostics.php' ),
	'manager'    => file_get_contents( $plugin . '/includes/class-sc-ei-upload-manager.php' ),
	'public'     => file_get_contents( $plugin . '/includes/class-sc-ei-public.php' ),
	'storage'    => file_get_contents( $plugin . '/includes/class-sc-ei-storage.php' ),
	'javascript' => file_get_contents( $plugin . '/assets/js/admin.js' ),
);

$markers = array(
	'Version:     0.5.0'                         => $files['main'],
	"SC_EI_DB_VERSION', '0.5.0'"                => $files['main'],
	"SC_EI_COMMUNICATION_SCHEMA_VERSION', '1.0.0'" => $files['main'],
	'class-sc-ei-communication-repository.php'   => $files['main'],
	'class-sc-ei-mailer.php'                     => $files['main'],
	'class-sc-ei-notification-service.php'       => $files['main'],
	'$sql_communications'                        => $files['database'],
	'$sql_communication_events'                  => $files['database'],
	'$sql_communication_templates'               => $files['database'],
	'communication_version int'                  => $files['database'],
	'communication_conflict'                     => $files['repository'],
	'create_system_notification'                 => $files['repository'],
	'mail_transport_accepted'                    => $files['mailer'],
	'accepted_by_mail_transport_not_proven_delivered' => $files['mailer'],
	'sender_acknowledgment_enabled'              => $files['service'],
	'sc_ei_save_communication_draft'             => $files['admin'],
	'sc_ei_send_communication'                   => $files['admin'],
	'Reviewed Send Action'                       => $files['thread'],
	'Engagement Intake Communications'           => $files['privacy'],
	'communication_columns'                      => $files['diagnostics'],
	'data-sc-ei-compose-form'                    => $files['javascript'],
);

foreach ( $markers as $marker => $contents ) {
	if ( false === strpos( $contents, $marker ) ) {
		$failures[] = 'Marker missing: ' . $marker;
	}
}

foreach ( array( 'wp_handle_upload', 'media_handle_upload', 'media_handle_sideload', 'wp_insert_attachment' ) as $media_marker ) {
	if ( false !== strpos( $files['public'] . $files['manager'] . $files['storage'], $media_marker ) ) {
		$failures[] = 'Media Library API appeared in secure pipeline: ' . $media_marker;
	}
}
if ( false !== strpos( $files['public'], 'Zoom' ) || false !== strpos( $files['public'], 'Google Meet' ) ) {
	$failures[] = 'Unsupported meeting platform appeared in the public form.';
}
if ( false !== strpos( $files['mailer'] . $files['admin'], 'wp_mail_attachment' ) ) {
	$failures[] = 'Email attachment support appeared in the communication layer.';
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}
echo "Engagement Intake v0.5.0 smoke checks passed." . PHP_EOL;
