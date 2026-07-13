<?php
/**
 * Static v0.7.0 schema, repository, and privacy mapping checks.
 */

$root       = dirname( __DIR__ );
$plugin     = $root . '/sustainable-catalyst-engagement-intake';
$database   = file_get_contents( $plugin . '/includes/class-sc-ei-database.php' );
$inquiries  = file_get_contents( $plugin . '/includes/class-sc-ei-inquiry-repository.php' );
$attachments= file_get_contents( $plugin . '/includes/class-sc-ei-attachment-repository.php' );
$reviews    = file_get_contents( $plugin . '/includes/class-sc-ei-review-repository.php' );
$fit_repository = file_get_contents( $plugin . '/includes/class-sc-ei-fit-repository.php' );
$communications = file_get_contents( $plugin . '/includes/class-sc-ei-communication-repository.php' );
$templates  = file_get_contents( $plugin . '/includes/class-sc-ei-template-repository.php' );
$mailer     = file_get_contents( $plugin . '/includes/class-sc-ei-mailer.php' );
$privacy_repository = file_get_contents( $plugin . '/includes/class-sc-ei-privacy-repository.php' );
$policy_repository  = file_get_contents( $plugin . '/includes/class-sc-ei-retention-policy-repository.php' );
$engine     = file_get_contents( $plugin . '/includes/class-sc-ei-retention-engine.php' );
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
		'/^\s*([a-z0-9_]+)\s+(?:bigint|char|varchar|longtext|text|date|datetime|decimal|smallint|tinyint|int)\b/im',
		$segment,
		$matches
	);
	return array_values( array_unique( $matches[1] ) );
}

function function_segment( string $source, string $start_needle, string $end_needle ): string {
	$start = strpos( $source, $start_needle );
	$end   = strpos( $source, $end_needle, false === $start ? 0 : $start + 1 );
	return false === $start ? '' : substr( $source, $start, false === $end ? null : $end - $start );
}

function first_data_keys( string $segment, int $tabs = 2 ): array {
	$data_start = strpos( $segment, '$data = array(' );
	if ( false === $data_start ) {
		return array();
	}
	$data_end = strpos( $segment, "\n\t\t);", $data_start );
	$data = substr(
		$segment,
		$data_start,
		false === $data_end ? null : $data_end - $data_start
	);
	$pattern = '/^\t{' . $tabs . "}'([^']+)'\\s*=>/m";
	preg_match_all( $pattern, $data, $matches );
	return array_values( array_unique( $matches[1] ) );
}

function create_data_keys( string $repository ): array {
	return first_data_keys( function_segment( $repository, 'public static function create', 'public static function find' ), 3 );
}

function snapshot_data_keys( string $repository ): array {
	return first_data_keys( function_segment( $repository, 'private static function insert_snapshot', 'private static function sanitize_due_at' ), 3 );
}

function communication_event_keys( string $repository ): array {
	return first_data_keys( function_segment( $repository, 'public static function record_event', 'public static function update_inquiry_aggregate' ), 4 );
}

function method_data_keys( string $source, string $method_start, string $next_method, int $tabs = 2 ): array {
	return first_data_keys( function_segment( $source, $method_start, $next_method ), $tabs );
}


function keys_at_indentation( string $segment, int $tabs ): array {
	$pattern = '/^\t{' . $tabs . "}'([^']+)'\\s*=>/m";
	preg_match_all( $pattern, $segment, $matches );
	return array_values( array_unique( $matches[1] ) );
}

function assert_mapping( string $label, array $columns, array $keys ): void {
	$columns = array_values( array_diff( $columns, array( 'id' ) ) );
	$missing = array_values( array_diff( $columns, $keys ) );
	$extra   = array_values( array_diff( $keys, $columns ) );
	if ( $missing || $extra ) {
		fwrite(
			STDERR,
			$label . " mapping failed.\nMissing: " . implode( ', ', $missing ) .
			"\nExtra: " . implode( ', ', $extra ) . PHP_EOL
		);
		exit( 1 );
	}
	echo 'PASS: ' . $label . ' schema mapping (' . count( $columns ) . " fields)\n";
}

assert_mapping( 'Inquiry', schema_columns( $database, 'sql_inquiries' ), create_data_keys( $inquiries ) );
assert_mapping( 'Attachment', schema_columns( $database, 'sql_attachments' ), create_data_keys( $attachments ) );
assert_mapping( 'Review snapshot', schema_columns( $database, 'sql_reviews' ), snapshot_data_keys( $reviews ) );
assert_mapping(
	'Fit assessment',
	schema_columns( $database, 'sql_fit_assessments' ),
	method_data_keys( $fit_repository, 'public static function create_draft', 'public static function save_draft', 3 )
);
assert_mapping(
	'Fit criterion item',
	schema_columns( $database, 'sql_fit_assessment_items' ),
	method_data_keys( $fit_repository, 'private static function seed_items', 'private static function upsert_item', 4 )
);
assert_mapping(
	'Fit second review',
	schema_columns( $database, 'sql_fit_assessment_reviews' ),
	method_data_keys( $fit_repository, 'public static function record_second_review', 'public static function finalize', 3 )
);
assert_mapping(
	'Communication event',
	schema_columns( $database, 'sql_communication_events' ),
	keys_at_indentation( function_segment( $communications, 'public static function record_event', 'public static function update_inquiry_aggregate' ), 4 )
);
assert_mapping(
	'Communication template',
	schema_columns( $database, 'sql_communication_templates' ),
	method_data_keys( $templates, 'public static function create_version', 'public static function render', 3 )
);
assert_mapping(
	'Privacy request',
	schema_columns( $database, 'sql_privacy_requests' ),
	method_data_keys( $privacy_repository, 'public static function create_request', 'public static function update_request', 3 )
);
assert_mapping(
	'Consent event',
	schema_columns( $database, 'sql_consent_events' ),
	method_data_keys( $privacy_repository, 'public static function record_consent', 'public static function consent_events', 3 )
);
assert_mapping(
	'Legal hold',
	schema_columns( $database, 'sql_legal_holds' ),
	method_data_keys( $privacy_repository, 'public static function place_hold', 'public static function release_hold', 3 )
);
assert_mapping(
	'Retention policy',
	schema_columns( $database, 'sql_retention_policies' ),
	first_data_keys( substr( $policy_repository, strpos( $policy_repository, 'public static function create_version' ) ), 3 )
);
assert_mapping(
	'Retention action',
	schema_columns( $database, 'sql_retention_actions' ),
	method_data_keys( $privacy_repository, 'public static function queue_action', 'public static function find_action', 3 )
);

$communication_columns = array_values( array_diff( schema_columns( $database, 'sql_communications' ), array( 'id' ) ) );
$communication_coverage = $communications . $mailer . $engine;
$missing_communication = array();
foreach ( $communication_columns as $column ) {
	if ( false === strpos( $communication_coverage, "'" . $column . "'" ) ) {
		$missing_communication[] = $column;
	}
}
if ( $missing_communication ) {
	fwrite( STDERR, 'Communication fields lack operational coverage: ' . implode( ', ', $missing_communication ) . PHP_EOL );
	exit( 1 );
}
echo 'PASS: Communication schema operational coverage (' . count( $communication_columns ) . " fields)\n";

foreach ( array( $inquiries, $attachments, $reviews, $fit_repository, $communications, $templates, $privacy_repository, $policy_repository ) as $source ) {
	if ( false === strpos( $source, 'array_keys( $data )' ) && false === strpos( $source, 'array_keys( $fields )' ) ) {
		fwrite( STDERR, "Repository insert/update formats are not key-derived.\n" );
		exit( 1 );
	}
}
echo "PASS: repository insert/update formats are key-derived\n";

if (
	false === strpos( $fit_repository, "'automatic_status_change' => false" )
	|| false === strpos( $fit_repository, "'automatic_communication' => false" )
	|| false === strpos( $fit_repository, "'automatic_scheduling' => false" )
	|| false !== strpos( $fit_repository, 'SC_EI_Inquiry_Repository::update_status' )
	|| false !== strpos( $fit_repository, 'wp_mail(' )
) {
	fwrite( STDERR, "Fit assessment repository violates the human-control boundary.\n" );
	exit( 1 );
}
echo "PASS: fit assessment repository preserves human-controlled, status-neutral operation\n";

if (
	false === strpos( $privacy, 'queue-only eraser bridge' )
	|| false !== strpos( $privacy, 'SC_EI_Storage::delete_file' )
	|| false === strpos( $privacy, "'items_removed'  => false")
	|| false === strpos( $privacy, "'items_retained' => true")
	|| false === strpos( $privacy, 'Engagement Intake Retention Actions' )
	|| false === strpos( $privacy, 'Engagement Intake Privacy Requests' )
) {
	fwrite( STDERR, "WordPress privacy bridge does not preserve reviewed queue-only execution.\n" );
	exit( 1 );
}
echo "PASS: WordPress privacy eraser queues reviewed actions and exporter includes lifecycle records\n";

if (
	false === strpos( $engine, 'physical_absence_verified' )
	|| false === strpos( $engine, 'tombstone_preserved' )
	|| false === strpos( $engine, 'attachments_remaining' )
) {
	fwrite( STDERR, "Retention execution verification markers are missing.\n" );
	exit( 1 );
}
echo "PASS: physical deletion verification, dependency blocking, and tombstone markers present\n";

echo "Engagement Intake v0.7.0 schema checks passed.\n";
