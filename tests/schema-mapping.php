<?php
/**
 * Static schema/repository mapping checks.
 */

$root       = dirname( __DIR__ );
$plugin     = $root . '/sustainable-catalyst-engagement-intake';
$database   = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$inquiries  = file_get_contents( $plugin . '/includes/class-sc-ei-inquiry-repository.php' );
$attachments= file_get_contents( $plugin . '/includes/class-sc-ei-attachment-repository.php' );
$privacy    = file_get_contents( $plugin . '/includes/class-sc-ei-privacy.php' );

function schema_columns( string $database, string $variable ): array {
	$start = strpos( $database, '$' . $variable . ' = "CREATE TABLE ' );
	if ( false === $start ) {
		return array();
	}

	$segment = substr( $database, $start );
	$end     = strpos( $segment, '";' );
	$segment = substr( $segment, 0, $end );

	preg_match_all(
		'/^\s*([a-z0-9_]+)\s+(?:bigint|char|varchar|longtext|text|date|datetime|smallint|tinyint|int)\b/im',
		$segment,
		$matches
	);

	return array_values( array_unique( $matches[1] ) );
}

function create_data_keys( string $repository ): array {
	$start = strpos( $repository, 'public static function create' );
	$end   = strpos( $repository, 'public static function find', $start );
	$part  = substr( $repository, $start, $end - $start );

	$data_start = strpos( $part, '$data = array(' );
	$data_end   = strpos( $part, "\n\t\t);", $data_start );
	$data       = substr( $part, $data_start, $data_end - $data_start );

	preg_match_all( "/^\s*'([^']+)'\s*=>/m", $data, $matches );
	return array_values( array_unique( $matches[1] ) );
}

function assert_mapping( string $label, array $columns, array $keys ): void {
	$columns = array_values( array_diff( $columns, array( 'id' ) ) );
	$missing = array_values( array_diff( $columns, $keys ) );
	$extra   = array_values( array_diff( $keys, $columns ) );

	if ( $missing || $extra ) {
		fwrite(
			STDERR,
			$label . " mapping failed.\nMissing: " . implode( ', ', $missing ) . "\nExtra: " . implode( ', ', $extra ) . PHP_EOL
		);
		exit( 1 );
	}

	echo 'PASS: ' . $label . ' schema mapping (' . count( $columns ) . " fields)\n";
}

assert_mapping(
	'Inquiry',
	schema_columns( $database, 'sql_inquiries' ),
	create_data_keys( $inquiries )
);

assert_mapping(
	'Attachment',
	schema_columns( $database, 'sql_attachments' ),
	create_data_keys( $attachments )
);

if ( false === strpos( $inquiries, 'array_keys( $data )' ) || false === strpos( $attachments, 'array_keys( $data )' ) ) {
	fwrite( STDERR, "Repository insert formats are not derived from data keys.\n" );
	exit( 1 );
}
echo "PASS: repository insert formats are key-derived\n";

function assert_update_formats( string $source, string $needle, string $label ): void {
	$start = strpos( $source, $needle );
	if ( false === $start ) {
		fwrite( STDERR, $label . " update block not found.\n" );
		exit( 1 );
	}

	$block = substr( $source, $start );
	if (
		! preg_match(
			'/\$wpdb->update\(\s*.+?,\s*array\((.*?)\),\s*array\(\s*\'id\'\s*=>.*?\),\s*array\((.*?)\),\s*array\(\s*\'%d\'\s*\)\s*\)/s',
			$block,
			$matches
		)
	) {
		fwrite( STDERR, $label . " update structure could not be parsed.\n" );
		exit( 1 );
	}

	preg_match_all( "/^\\s*'[^']+'\\s*=>/m", $matches[1], $data_matches );
	preg_match_all( "/'%[sdif]'/", $matches[2], $format_matches );

	if ( count( $data_matches[0] ) !== count( $format_matches[0] ) ) {
		fwrite(
			STDERR,
			$label . ' format mismatch: ' . count( $data_matches[0] ) . '/' . count( $format_matches[0] ) . PHP_EOL
		);
		exit( 1 );
	}

	echo 'PASS: ' . $label . ' field/format mapping (' . count( $data_matches[0] ) . " fields)\n";
}

assert_update_formats( $privacy, '$attachment_updated = $wpdb->update(', 'Attachment privacy eraser' );
assert_update_formats( $privacy, '$updated         = $wpdb->update(', 'Inquiry privacy eraser' );

echo "Engagement Intake v0.3.1 schema checks passed.\n";
