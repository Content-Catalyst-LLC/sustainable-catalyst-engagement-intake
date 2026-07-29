<?php
/** Executable v2.0.1 interrupted-migration recovery boundary. */
define( 'ABSPATH', __DIR__ . '/' );

final class SC_EI_Recovery_Fake_WPDB {
	public string $prefix = 'eu3_';
	public array $tables = array();
	public array $queries = array();
	private bool $suppressed = false;

	public function prepare( string $query, ...$args ): string {
		foreach ( $args as $arg ) {
			$query = preg_replace( '/%s/', "'" . str_replace( "'", "''", (string) $arg ) . "'", $query, 1 );
		}
		return $query;
	}

	public function esc_like( string $value ): string {
		return addcslashes( $value, '_%\\' );
	}

	public function get_var( string $query ) {
		$this->queries[] = $query;
		if ( preg_match( "/SHOW TABLES LIKE '([^']+)'/", $query, $matches ) ) {
			$pattern = str_replace( array( '\\_', '\\%' ), array( '_', '%' ), $matches[1] );
			return isset( $this->tables[ $pattern ] ) ? $pattern : null;
		}
		if ( preg_match( "/SHOW COLUMNS FROM ([^ ]+) LIKE '([^']+)'/", $query, $matches ) ) {
			return isset( $this->tables[ $matches[1] ] ) ? $matches[2] : null;
		}
		return null;
	}

	public function suppress_errors( bool $suppress = true ): bool {
		$previous = $this->suppressed;
		$this->suppressed = $suppress;
		return $previous;
	}

	public function query( string $query ) {
		$this->queries[] = $query;
		if ( preg_match( '/^CREATE TABLE IF NOT EXISTS ([^ ]+)/i', trim( $query ), $matches ) ) {
			$this->tables[ $matches[1] ] = true;
			return 1;
		}
		return 0;
	}
}

$wpdb = new SC_EI_Recovery_Fake_WPDB();
require dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake/includes/class-sc-ei-database.php';

$method = new ReflectionMethod( SC_EI_Database::class, 'create_table_if_missing' );
$method->setAccessible( true );
$table = 'eu3_sc_ei_proposal_approvals';
$created = $method->invoke( null, $table, "CREATE TABLE {$table} (id bigint unsigned NOT NULL AUTO_INCREMENT, PRIMARY KEY (id));" );

$wpdb->tables['eu3_sc_ei_statements_of_work'] = true;
$wpdb->tables['eu3_sc_ei_statement_of_work_versions'] = true;
$wpdb->tables['eu3_sc_ei_change_requests'] = true;
$wpdb->tables['eu3_sc_ei_engagement_dossiers'] = true;
$wpdb->tables['eu3_sc_ei_dossier_relationships'] = true;
$wpdb->tables['eu3_sc_ei_dossier_events'] = true;

$proposal_contract = SC_EI_Database::proposal_governance_columns_exist();
$unified_contract = SC_EI_Database::unified_platform_columns_exist();
$missing_table_column_queries = array_filter(
	$wpdb->queries,
	static fn( string $query ): bool => false !== strpos( $query, 'SHOW COLUMNS FROM eu3_sc_ei_platform_handoffs' )
);
$create_queries = array_filter(
	$wpdb->queries,
	static fn( string $query ): bool => str_starts_with( trim( $query ), 'CREATE TABLE IF NOT EXISTS eu3_sc_ei_proposal_approvals' )
);

$checks = array(
	'native recovery create succeeds' => true === $created && isset( $wpdb->tables[ $table ] ),
	'recovery uses CREATE TABLE IF NOT EXISTS' => 1 === count( $create_queries ),
	'created proposal table verifies without errors' => ! in_array( false, $proposal_contract, true ),
	'absent platform table fails closed' => in_array( false, $unified_contract, true ),
	'absent platform table is never queried for columns' => 0 === count( $missing_table_column_queries ),
);
$failed = array_keys( array_filter( $checks, static fn( bool $passed ): bool => ! $passed ) );
if ( $failed ) {
	fwrite( STDERR, 'Database recovery runtime checks failed: ' . implode( ', ', $failed ) . PHP_EOL );
	exit( 1 );
}
foreach ( $checks as $label => $passed ) {
	echo 'PASS: ' . $label . PHP_EOL;
}
echo 'Database recovery runtime checks passed (' . count( $checks ) . ' assertions).' . PHP_EOL;
