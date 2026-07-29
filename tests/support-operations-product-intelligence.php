<?php
/**
 * v1.2.0 Support Operations and Product Intelligence Integration contracts.
 */
$root = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';
$main = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$db = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$schema = file_get_contents( $plugin . '/includes/class-sc-ei-support-schema.php' );
$repo = file_get_contents( $plugin . '/includes/class-sc-ei-support-repository.php' );
$admin = file_get_contents( $plugin . '/includes/class-sc-ei-support-admin.php' );
$view = file_get_contents( $plugin . '/admin/views/support-cases.php' );
$public = file_get_contents( $plugin . '/includes/class-sc-ei-public.php' );
$rest = file_get_contents( $plugin . '/includes/class-sc-ei-rest.php' );
$portal = file_get_contents( $plugin . '/public/views/sender-portal.php' );
$privacy = file_get_contents( $plugin . '/includes/class-sc-ei-privacy.php' );
$retention = file_get_contents( $plugin . '/includes/class-sc-ei-retention-engine.php' );
$platform = file_get_contents( $plugin . '/includes/class-sc-ei-platform-repository.php' );
$validation = file_get_contents( $plugin . '/includes/class-sc-ei-platform-validation.php' );
$communication = file_get_contents( $plugin . '/includes/class-sc-ei-communication-schema.php' );
$capabilities = file_get_contents( $plugin . '/includes/class-sc-ei-capabilities.php' );
$uninstall = file_get_contents( $plugin . '/uninstall.php' );

$checks = array(
	'v1.2.0 identity and support schema' => false !== strpos( $main, 'Version:     2.0.2' )
		&& false !== strpos( $main, "SC_EI_DB_VERSION', '2.0.0'" )
		&& false !== strpos( $main, "SC_EI_PLATFORM_SCHEMA_VERSION', '2.0.0'" )
		&& false !== strpos( $main, "SC_EI_SUPPORT_SCHEMA_VERSION', '1.0.1'" ),
	'four support tables installed' => false !== strpos( $db, '$sql_support_cases' )
		&& false !== strpos( $db, '$sql_support_case_events' )
		&& false !== strpos( $db, '$sql_support_case_links' )
		&& false !== strpos( $db, '$sql_support_signals' )
		&& 4 === substr_count( $db, 'dbDelta( $sql_support_' ),
	'canonical inquiry remains authority' => false !== strpos( $db, 'UNIQUE KEY inquiry_id (inquiry_id)' )
		&& false !== strpos( $repo, 'SC_EI_Inquiry_Repository::find' )
		&& false !== strpos( $repo, 'create_for_inquiry' ),
	'governed support stages and typed transitions' => false !== strpos( $schema, "'new_support_request'" )
		&& false !== strpos( $schema, "'fix_planned'" )
		&& false !== strpos( $schema, "'resolved'" )
		&& false !== strpos( $repo, "'MOVE ' . strtoupper" )
		&& false !== strpos( $repo, 'support_transition_confirmation_failed' ),
	'product and diagnostic context' => false !== strpos( $schema, "'workbench'" )
		&& false !== strpos( $schema, "'decision-studio'" )
		&& false !== strpos( $schema, "'research-lab'" )
		&& false !== strpos( $schema, "'knowledge-library'" )
		&& false !== strpos( $db, 'environment_json longtext' )
		&& false !== strpos( $db, 'reproduction_steps longtext' ),
	'typed handoff contract' => false !== strpos( $schema, "HANDOFF_SCHEMA = 'sc-product-support-handoff/1.0'" )
		&& false !== strpos( $repo, 'public static function ingest_handoff' )
		&& false !== strpos( $rest, "'/support/handoffs'" ),
	'product intelligence rejects personal data' => false !== strpos( $schema, "'support_signal_personal_data_rejected'" )
		&& false !== strpos( $schema, "'email'" )
		&& false !== strpos( $schema, "'attachment'" )
		&& false !== strpos( $repo, "'contains_personal_data'=> false" )
		&& false !== strpos( $db, 'contains_personal_data tinyint(1) unsigned NOT NULL DEFAULT 0' ),
	'case relationships remain typed and auditable' => false !== strpos( $schema, "'knowledge_article'" )
		&& false !== strpos( $schema, "'known_issue'" )
		&& false !== strpos( $schema, "'feature_suggestion'" )
		&& false !== strpos( $schema, "'product_release'" )
		&& false !== strpos( $repo, 'support_relationship_added' ),
	'dedicated support administration workspace' => false !== strpos( $admin, 'Support Cases' )
		&& false !== strpos( $view, 'Support Operations and Product Intelligence' )
		&& false !== strpos( $view, 'Product Intelligence' ),
	'focused public support request' => false !== strpos( $public, "add_shortcode( 'sc_support_request'" )
		&& false !== strpos( $public, 'support_product' )
		&& false !== strpos( $public, 'support_reproduction_steps' ),
	'sender portal is deliberately limited' => false !== strpos( $repo, 'public static function sender_snapshot' )
		&& false !== strpos( $portal, 'Product support case' )
		&& false === strpos( $portal, "support_snapshot['error_message']" )
		&& false === strpos( $portal, "support_snapshot['reproduction_steps']" ),
	'support REST capabilities are separated' => false !== strpos( $rest, "'/support/cases'" )
		&& false !== strpos( $rest, "'/support/cases/(?P<id>\\d+)'" )
		&& false !== strpos( $capabilities, "'sc_intake_view_support'" )
		&& false !== strpos( $capabilities, "'sc_intake_ingest_support_handoffs'" ),
	'privacy export and approved redaction include support' => false !== strpos( $privacy, 'SC_EI_Support_Repository::export_for_inquiry' )
		&& false !== strpos( $repo, 'public static function redact_for_privacy' )
		&& false !== strpos( $retention, 'SC_EI_Support_Repository::redact_for_privacy' ),
	'reviewable support communication templates' => false !== strpos( $communication, "'support_received'" )
		&& false !== strpos( $communication, "'support_known_issue'" )
		&& false !== strpos( $communication, "'support_workaround'" )
		&& false !== strpos( $communication, "'support_fix_released'" )
		&& false !== strpos( $communication, "'support_closed'" ),
	'readiness migration cron and operations integration' => false !== strpos( $platform, "'support_columns'" )
		&& false !== strpos( $platform, "'support_migration_journal'" )
		&& false !== strpos( $platform, "'support_signal_digest'" )
		&& false !== strpos( $platform, "'support_operations'" ),
	'live validation covers support privacy and cleanup' => false !== strpos( $validation, 'SC_EI_Support_Repository::create_for_inquiry' )
		&& false !== strpos( $validation, 'SC_EI_Support_Repository::transition' )
		&& false !== strpos( $validation, 'private@example.com' )
		&& false !== strpos( $validation, "table( 'support_case_events' )" )
		&& false !== strpos( $validation, "table( 'support_cases' )" ),
	'uninstall removes support schedules and options' => false !== strpos( $uninstall, 'sc_ei_support_signal_digest' )
		&& false !== strpos( $uninstall, 'sc_ei_support_schema_version' ),
	'no autonomous outbound support commitments' => false === strpos( $repo, 'wp_mail(' )
		&& false === strpos( $repo, 'wp_remote_post(' ), 
);

$failed = array_keys( array_filter( $checks, static fn( $passed ) => ! $passed ) );
if ( $failed ) {
	fwrite( STDERR, 'Support operations and product intelligence checks failed: ' . implode( ', ', $failed ) . PHP_EOL );
	exit( 1 );
}
foreach ( $checks as $label => $passed ) {
	echo 'PASS: ' . $label . PHP_EOL;
}
echo "Sustainable Catalyst Contact and Engagement Platform v1.2.0 support operations checks passed.\n";
