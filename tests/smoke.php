<?php
/**
 * Static v0.7.0 release checks that do not load WordPress.
 */

$root   = dirname( __DIR__ );
$plugin = $root . '/sustainable-catalyst-engagement-intake';

$required = array(
	$plugin . '/sustainable-catalyst-engagement-intake.php',
	$plugin . '/includes/class-sc-ei-fit-schema.php',
	$plugin . '/includes/class-sc-ei-fit-repository.php',
	$plugin . '/includes/class-sc-ei-fit-admin.php',
	$plugin . '/admin/views/fit-assessment.php',
	$plugin . '/admin/views/fit-assessment-detail.php',
	$plugin . '/includes/class-sc-ei-privacy-schema.php',
	$plugin . '/includes/class-sc-ei-privacy-repository.php',
	$plugin . '/includes/class-sc-ei-retention-engine.php',
	$plugin . '/includes/class-sc-ei-communication-schema.php',
	$plugin . '/includes/class-sc-ei-review-schema.php',
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
	'fit_schema' => file_get_contents( $plugin . '/includes/class-sc-ei-fit-schema.php' ),
	'fit_repo'   => file_get_contents( $plugin . '/includes/class-sc-ei-fit-repository.php' ),
	'fit_admin'  => file_get_contents( $plugin . '/includes/class-sc-ei-fit-admin.php' ),
	'fit_view'   => file_get_contents( $plugin . '/admin/views/fit-assessment-detail.php' ),
	'privacy'    => file_get_contents( $plugin . '/includes/class-sc-ei-privacy.php' ),
	'engine'     => file_get_contents( $plugin . '/includes/class-sc-ei-retention-engine.php' ),
	'diagnostics'=> file_get_contents( $plugin . '/includes/class-sc-ei-diagnostics.php' ),
	'manager'    => file_get_contents( $plugin . '/includes/class-sc-ei-upload-manager.php' ),
	'public'     => file_get_contents( $plugin . '/includes/class-sc-ei-public.php' ),
	'storage'    => file_get_contents( $plugin . '/includes/class-sc-ei-storage.php' ),
	'javascript' => file_get_contents( $plugin . '/assets/js/admin.js' ),
);

$markers = array(
	'Version:     0.7.0'                           => $files['main'],
	"SC_EI_DB_VERSION', '0.7.0'"                  => $files['main'],
	"SC_EI_FIT_SCHEMA_VERSION', '1.0.0'"           => $files['main'],
	'class-sc-ei-fit-schema.php'                   => $files['main'],
	'class-sc-ei-fit-repository.php'               => $files['main'],
	'class-sc-ei-fit-admin.php'                    => $files['main'],
	'$sql_fit_assessments'                         => $files['database'],
	'$sql_fit_assessment_items'                    => $files['database'],
	'$sql_fit_assessment_reviews'                  => $files['database'],
	'fit_assessment_status varchar'                => $files['database'],
	'calculate_score'                              => $files['fit_schema'],
	'second_review_reasons'                        => $files['fit_schema'],
	'fit_assessor_only'                            => $files['fit_repo'],
	'fit_second_review_agreement_mismatch'         => $files['fit_repo'],
	'automatic_status_change'                      => $files['fit_repo'],
	"'FINALIZE ' . \$assessment_id"                => $files['fit_admin'],
	'Human judgment only'                         => $files['fit_view'],
	'Engagement Intake Human Fit Assessments'      => $files['privacy'],
	'SC_EI_Fit_Repository::redact_for_privacy'     => $files['engine'],
	'fit_human_control'                            => $files['diagnostics'],
	'FINALIZE ${assessmentId}'                     => $files['javascript'],
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
if ( false !== strpos( $files['fit_repo'], 'update_status(' ) ) {
	$failures[] = 'Fit repository contains automatic inquiry status mutation.';
}
if ( false !== strpos( $files['fit_repo'], 'SC_EI_Mailer::' ) || false !== strpos( $files['fit_repo'], 'wp_mail(' ) ) {
	$failures[] = 'Fit repository contains communication delivery.';
}
if (
	false !== strpos( $files['fit_repo'], 'score >= ' )
	|| false !== strpos( $files['fit_repo'], 'score > ' )
	|| false !== strpos( $files['fit_repo'], 'score <= ' )
	|| false !== strpos( $files['fit_repo'], 'score < ' )
) {
	$failures[] = 'Fit repository contains a score-based decision rule.';
}

if ( $failures ) {
	fwrite( STDERR, implode( PHP_EOL, $failures ) . PHP_EOL );
	exit( 1 );
}
echo "Engagement Intake v0.7.0 smoke checks passed." . PHP_EOL;
