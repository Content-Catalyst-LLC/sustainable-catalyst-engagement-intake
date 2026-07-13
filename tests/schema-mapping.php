<?php
/**
 * Static schema/repository/privacy mapping checks.
 */

$root       = dirname( __DIR__ );
$plugin     = $root . '/sustainable-catalyst-engagement-intake';
$database   = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$inquiries  = file_get_contents( $plugin . '/includes/class-sc-ei-inquiry-repository.php' );
$attachments= file_get_contents( $plugin . '/includes/class-sc-ei-attachment-repository.php' );
$reviews    = file_get_contents( $plugin . '/includes/class-sc-ei-review-repository.php' );
$communications = file_get_contents( $plugin . '/includes/class-sc-ei-communication-repository.php' );
$templates  = file_get_contents( $plugin . '/includes/class-sc-ei-template-repository.php' );
$mailer     = file_get_contents( $plugin . '/includes/class-sc-ei-mailer.php' );
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

function function_segment( string $source, string $start_needle, string $end_needle ): string {
	$start = strpos( $source, $start_needle );
	$end   = strpos( $source, $end_needle, $start + 1 );
	return false === $start ? '' : substr( $source, $start, false === $end ? null : $end - $start );
}

function first_data_keys( string $segment, string $indent_pattern = '\\s*' ): array {
	$data_start = strpos( $segment, '$data = array(' );
	$data_end   = strpos( $segment, "\n\t\t);", $data_start );
	$data       = substr( $segment, $data_start, $data_end - $data_start );
	preg_match_all( "/^{$indent_pattern}'([^']+)'\\s*=>/m", $data, $matches );
	return array_values( array_unique( $matches[1] ) );
}

function create_data_keys( string $repository ): array {
	return first_data_keys( function_segment( $repository, 'public static function create', 'public static function find' ) );
}

function snapshot_data_keys( string $repository ): array {
	return first_data_keys( function_segment( $repository, 'private static function insert_snapshot', 'private static function sanitize_due_at' ), '\\t\\t\\t' );
}

function array_keys_in_segment( string $segment ): array {
	preg_match_all( "/^\\t{4}'([^']+)'\\s*=>/m", $segment, $matches );
	return array_values( array_unique( $matches[1] ) );
}

function keys_between( string $source, string $start_needle, string $end_needle ): array {
	$start = strpos( $source, $start_needle );
	$end   = strpos( $source, $end_needle, $start + 1 );
	$segment = false === $start ? '' : substr( $source, $start, false === $end ? null : $end - $start );
	return array_keys_in_segment( $segment );
}

function assert_mapping( string $label, array $columns, array $keys ): void {
	$columns = array_values( array_diff( $columns, array( 'id' ) ) );
	$missing = array_values( array_diff( $columns, $keys ) );
	$extra   = array_values( array_diff( $keys, $columns ) );
	if ( $missing || $extra ) {
		fwrite( STDERR, $label . " mapping failed.\nMissing: " . implode( ', ', $missing ) . "\nExtra: " . implode( ', ', $extra ) . PHP_EOL );
		exit( 1 );
	}
	echo 'PASS: ' . $label . ' schema mapping (' . count( $columns ) . " fields)\n";
}

assert_mapping( 'Inquiry', schema_columns( $database, 'sql_inquiries' ), create_data_keys( $inquiries ) );
assert_mapping( 'Attachment', schema_columns( $database, 'sql_attachments' ), create_data_keys( $attachments ) );
assert_mapping( 'Review snapshot', schema_columns( $database, 'sql_reviews' ), snapshot_data_keys( $reviews ) );

$event_segment = function_segment( $communications, 'public static function record_event', 'public static function update_inquiry_aggregate' );
assert_mapping( 'Communication event', schema_columns( $database, 'sql_communication_events' ), array_keys_in_segment( $event_segment ) );

assert_mapping(
	'Communication template',
	schema_columns( $database, 'sql_communication_templates' ),
	first_data_keys( function_segment( $templates, 'public static function create_version', 'public static function render' ) )
);

$communication_columns = array_values( array_diff( schema_columns( $database, 'sql_communications' ), array( 'id' ) ) );
$coverage_source = $communications . $mailer;
$missing_coverage = array();
foreach ( $communication_columns as $column ) {
	if ( false === strpos( $coverage_source, "'" . $column . "'" ) ) {
		$missing_coverage[] = $column;
	}
}
if ( $missing_coverage ) {
	fwrite( STDERR, 'Communication schema fields lack repository/mailer coverage: ' . implode( ', ', $missing_coverage ) . PHP_EOL );
	exit( 1 );
}
echo 'PASS: Communication schema operational coverage (' . count( $communication_columns ) . " fields)\n";

foreach ( array( $inquiries, $attachments, $reviews, $communications, $templates ) as $source ) {
	if ( false === strpos( $source, 'array_keys( $data )' ) && false === strpos( $source, 'array_keys( $fields )' ) ) {
		fwrite( STDERR, "Repository insert/update formats are not key-derived.\n" );
		exit( 1 );
	}
}
echo "PASS: repository insert/update formats are key-derived\n";

if (
	false === strpos( $privacy, 'formats_for( $attachment_data' )
	|| false === strpos( $privacy, 'formats_for( $inquiry_data' )
	|| false === strpos( $privacy, "SET subject = %s")
	|| false === strpos( $privacy, "context_json = %s")
) {
	fwrite( STDERR, "Privacy mapping or communication erasure markers missing.\n" );
	exit( 1 );
}
echo "PASS: privacy eraser formats are key-derived and communication narratives are redacted\n";

echo "Engagement Intake v0.5.0 schema checks passed.\n";
