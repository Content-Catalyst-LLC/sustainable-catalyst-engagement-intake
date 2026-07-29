<?php
/** v2.0.1 interrupted-migration recovery release contracts. */
$root = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';
$main = file_get_contents( $plugin . '/sustainable-catalyst-engagement-intake.php' );
$db = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$coordinator = file_get_contents( $plugin . '/includes/class-sc-ei-plugin.php' );
$activator = file_get_contents( $plugin . '/includes/class-sc-ei-activator.php' );
$readme = file_get_contents( $plugin . '/readme.txt' );
$migration = file_get_contents( $root . '/docs/MIGRATION-v2.0.1.md' );

$checks = array(
	'v2.0.1 plugin identity with unchanged database schema' => false !== strpos( $main, 'Version:     2.0.2' )
		&& false !== strpos( $main, "SC_EI_VERSION', '2.0.2'" )
		&& false !== strpos( $main, "SC_EI_DB_VERSION', '2.0.0'" ),
	'targeted critical table recovery precedes dbDelta' => false !== strpos( $db, 'create_table_if_missing( $proposal_approvals, $sql_proposal_approvals )' )
		&& false !== strpos( $db, 'create_table_if_missing( $platform_handoffs, $sql_platform_handoffs )' )
		&& strpos( $db, 'create_table_if_missing( $proposal_approvals' ) < strpos( $db, 'dbDelta( $sql_proposal_approvals )' ),
	'native create and error suppression are present' => false !== strpos( $db, "'CREATE TABLE IF NOT EXISTS '" )
		&& false !== strpos( $db, '$wpdb->suppress_errors( true )' ),
	'missing tables fail closed before column probes' => false !== strpos( $db, 'private static function column_exists' )
		&& false !== strpos( $db, 'if ( ! self::physical_table_exists( $table ) )' )
		&& false !== strpos( $db, 'proposal_governance_columns_exist' )
		&& false !== strpos( $db, 'unified_platform_columns_exist' ),
	'upgrade lock and critical-table retry are present' => false !== strpos( $db, "'sc_ei_database_upgrade_lock'" )
		&& false !== strpos( $db, '5 * MINUTE_IN_SECONDS' )
		&& false !== strpos( $db, 'critical_tables_exist()' ),
	'activation fails safely when recovery cannot complete' => false !== strpos( $activator, 'Activation stopped because required database tables could not be created' )
		&& false !== strpos( $activator, 'deactivate_plugins( SC_EI_BASENAME )' ),
	'active runtime pauses instead of looping' => false !== strpos( $coordinator, 'database_recovery_notice' )
		&& false !== strpos( $coordinator, "in_array( false, SC_EI_Database::critical_tables_exist(), true )" ),
	'migration documentation is nondestructive' => false !== strpos( $migration, 'nondestructive' )
		&& false !== strpos( $migration, 'No existing tables are dropped or renamed' ),
	'WordPress stable tag updated' => false !== strpos( $readme, 'Stable tag: 2.0.2' ),
);
$failed = array_keys( array_filter( $checks, static fn( bool $passed ): bool => ! $passed ) );
if ( $failed ) {
	fwrite( STDERR, 'Database recovery contract checks failed: ' . implode( ', ', $failed ) . PHP_EOL );
	exit( 1 );
}
foreach ( $checks as $label => $passed ) {
	echo 'PASS: ' . $label . PHP_EOL;
}
echo 'Database recovery contract checks passed (' . count( $checks ) . ' assertions).' . PHP_EOL;
