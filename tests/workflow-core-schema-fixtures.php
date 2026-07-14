<?php
$plugin = dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake';
$main = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$db = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$schema = file_get_contents( $plugin . '/includes/class-sc-ei-workflow-core-schema.php' );
$caps = file_get_contents( $plugin . '/includes/class-sc-ei-capabilities.php' );
$activator = file_get_contents( $plugin . '/includes/class-sc-ei-activator.php' );
$uninstall = file_get_contents( $plugin . '/uninstall.php' );
$checks = array(
	'v1.1.1 plugin markers' => false !== strpos( $main, 'Version:     1.2.0' )
		&& false !== strpos( $main, "SC_EI_DB_VERSION', '1.2.0'" )
		&& false !== strpos( $main, "SC_EI_WORKFLOW_CORE_SCHEMA_VERSION', '1.0.0'" ),
	'core components loaded' => false !== strpos( $main, 'class-sc-ei-workflow-core-schema.php' )
		&& false !== strpos( $main, 'class-sc-ei-workflow-core-contract.php' )
		&& false !== strpos( $main, 'class-sc-ei-workflow-core-repository.php' )
		&& false !== strpos( $main, 'class-sc-ei-workflow-core-service.php' )
		&& false !== strpos( $main, 'class-sc-ei-workflow-core-admin.php' ),
	'canonical case table' => false !== strpos( $db, '$sql_workflow_cases' )
		&& false !== strpos( $db, 'UNIQUE KEY inquiry_id' )
		&& false !== strpos( $db, 'projection_hash char(64)' )
		&& false !== strpos( $db, 'consistency_status varchar(20)' ),
	'idempotent command table' => false !== strpos( $db, '$sql_workflow_commands' )
		&& false !== strpos( $db, 'UNIQUE KEY command_key' )
		&& false !== strpos( $db, 'payload_hash char(64)' ),
	'signed handoff table' => false !== strpos( $db, '$sql_workflow_handoffs' )
		&& false !== strpos( $db, 'UNIQUE KEY handoff_key' )
		&& false !== strpos( $db, 'signature char(64)' ),
	'durable outbox table' => false !== strpos( $db, '$sql_workflow_outbox' )
		&& false !== strpos( $db, 'UNIQUE KEY event_key' )
		&& false !== strpos( $db, 'status_available (status, available_at)' ),
	'dbDelta installs core' => false !== strpos( $db, 'dbDelta( $sql_workflow_cases )' )
		&& false !== strpos( $db, 'dbDelta( $sql_workflow_commands )' )
		&& false !== strpos( $db, 'dbDelta( $sql_workflow_handoffs )' )
		&& false !== strpos( $db, 'dbDelta( $sql_workflow_outbox )' ),
	'exact diagnostics mapping' => false !== strpos( $db, 'public static function workflow_core_columns_exist' ),
	'fixed no-automation defaults' => false !== strpos( $schema, "'workflow_core_no_auto_acceptance'" )
		&& false !== strpos( $schema, "'workflow_core_no_auto_fit_decision'" )
		&& false !== strpos( $schema, "'workflow_core_no_auto_contract'" )
		&& false !== strpos( $schema, "'workflow_core_no_auto_activation'" )
		&& false !== strpos( $schema, "'workflow_core_no_auto_external_delivery'" )
		&& false !== strpos( $schema, "'workflow_core_no_unverified_inbound_commands'" ),
	'least privilege capabilities' => false !== strpos( $caps, 'sc_intake_view_workflow_core' )
		&& false !== strpos( $caps, 'sc_intake_manage_workflow_core' )
		&& false !== strpos( $caps, 'sc_intake_prepare_workflow_handoffs' )
		&& false !== strpos( $caps, 'sc_intake_dispatch_workflow_outbox' )
		&& false !== strpos( $caps, 'sc_intake_export_workflow_core_private' ),
	'activation and deactivation' => false !== strpos( $activator, 'SC_EI_Workflow_Core_Repository::schedule()' )
		&& false !== strpos( $activator, 'SC_EI_Workflow_Core_Repository::unschedule()' )
		&& false !== strpos( $activator, 'sc_ei_workflow_core_schema_version' ),
	'uninstall cleanup' => false !== strpos( $uninstall, 'sc_ei_workflow_core_sync' )
		&& false !== strpos( $uninstall, 'sc_ei_workflow_core_outbox' )
		&& false !== strpos( $uninstall, 'sc_ei_workflow_core_schema_version' ),
);
$failed = array_keys( array_filter( $checks, static fn( $value ) => ! $value ) );
if ( $failed ) {
	fwrite( STDERR, 'Workflow Core schema checks failed: ' . implode( ', ', $failed ) . PHP_EOL );
	exit( 1 );
}
foreach ( $checks as $label => $passed ) {
	echo 'PASS: ' . $label . PHP_EOL;
}
echo "Engagement Intake v1.0.0 Workflow Core schema fixtures passed.\n";
