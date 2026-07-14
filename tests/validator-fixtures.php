<?php
/**
 * Executable validator fixtures without loading WordPress.
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'MB_IN_BYTES', 1024 * 1024 );

final class WP_Error {
	private string $code;
	private string $message;

	public function __construct( string $code, string $message ) {
		$this->code    = $code;
		$this->message = $message;
	}

	public function get_error_code(): string {
		return $this->code;
	}

	public function get_error_message(): string {
		return $this->message;
	}
}

function is_wp_error( $value ): bool {
	return $value instanceof WP_Error;
}

function __( string $text, string $domain = '' ): string {
	return $text;
}

function absint( $value ): int {
	return abs( (int) $value );
}

function wp_unslash( $value ) {
	return $value;
}

function sanitize_file_name( string $name ): string {
	$name = basename( str_replace( '\\', '/', $name ) );
	return preg_replace( '/[^A-Za-z0-9._-]+/', '-', $name ) ?: '';
}

function sanitize_key( string $key ): string {
	return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $key ) ) ?: '';
}

function wp_check_filetype_and_ext( string $path, string $filename, array $mimes ): array {
	$extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
	return array(
		'ext'             => $extension,
		'type'            => '',
		'proper_filename' => false,
	);
}

require_once dirname( __DIR__ ) . '/sustainable-catalyst-engagement-intake/includes/class-sc-ei-upload-validator.php';

$temp = sys_get_temp_dir() . '/sc-ei-validator-' . bin2hex( random_bytes( 5 ) );
mkdir( $temp, 0700, true );

register_shutdown_function(
	static function() use ( $temp ): void {
		if ( ! is_dir( $temp ) ) {
			return;
		}
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $temp, FilesystemIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $iterator as $item ) {
			$item->isDir() ? rmdir( $item->getPathname() ) : unlink( $item->getPathname() );
		}
		rmdir( $temp );
	}
);

$settings = array(
	'upload_max_file_mb'        => 20,
	'allowed_upload_extensions' => array_keys( SC_EI_Upload_Validator::supported_extensions() ),
);

function fixture_file( string $path, string $name ): array {
	return array(
		'name'     => $name,
		'tmp_name' => $path,
		'type'     => '',
		'error'    => UPLOAD_ERR_OK,
		'size'     => filesize( $path ),
	);
}

function assert_ok( string $label, $result ): void {
	if ( is_wp_error( $result ) ) {
		fwrite( STDERR, $label . ' failed: ' . $result->get_error_code() . ' — ' . $result->get_error_message() . PHP_EOL );
		exit( 1 );
	}
	echo 'PASS: ' . $label . PHP_EOL;
}

function assert_error( string $label, $result, string $expected_code ): void {
	if ( ! is_wp_error( $result ) || $result->get_error_code() !== $expected_code ) {
		$actual = is_wp_error( $result ) ? $result->get_error_code() : 'accepted';
		fwrite( STDERR, $label . ' expected ' . $expected_code . ' but received ' . $actual . PHP_EOL );
		exit( 1 );
	}
	echo 'PASS: ' . $label . PHP_EOL;
}

$txt = $temp . '/brief.txt';
file_put_contents( $txt, "Project brief\nPublic-interest systems research.\n" );
assert_ok( 'plain text accepted', SC_EI_Upload_Validator::validate( fixture_file( $txt, 'brief.txt' ), $settings ) );

$exe = $temp . '/renamed.txt';
file_put_contents( $exe, "MZ\x90\x00fake executable" );
assert_error( 'renamed executable rejected', SC_EI_Upload_Validator::validate( fixture_file( $exe, 'renamed.txt' ), $settings ), 'executable_signature' );

$pdf = $temp . '/brief.pdf';
file_put_contents( $pdf, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\n%%EOF\n" );
assert_ok( 'basic PDF accepted', SC_EI_Upload_Validator::validate( fixture_file( $pdf, 'brief.pdf' ), $settings ) );

$active_pdf = $temp . '/active.pdf';
file_put_contents( $active_pdf, "%PDF-1.4\n1 0 obj\n<< /JavaScript 2 0 R >>\nendobj\n%%EOF\n" );
assert_error( 'active PDF rejected', SC_EI_Upload_Validator::validate( fixture_file( $active_pdf, 'active.pdf' ), $settings ), 'pdf_javascript' );

$csv = $temp . '/data.csv';
file_put_contents( $csv, "name,value\nexample,=1+1\n" );
$csv_result = SC_EI_Upload_Validator::validate( fixture_file( $csv, 'data.csv' ), $settings );
assert_ok( 'CSV accepted', $csv_result );
if ( ! in_array( 'csv_formula_content', $csv_result['security_flags'], true ) ) {
	fwrite( STDERR, "CSV formula flag missing.\n" );
	exit( 1 );
}
echo "PASS: CSV formula content flagged\n";

$png = $temp . '/image.png';
file_put_contents(
	$png,
	base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9Zr4sAAAAASUVORK5CYII=', true )
);
assert_ok( 'PNG accepted', SC_EI_Upload_Validator::validate( fixture_file( $png, 'image.png' ), $settings ) );

if ( class_exists( 'ZipArchive' ) ) {
	$docx = $temp . '/safe.docx';
	$zip  = new ZipArchive();
	$zip->open( $docx, ZipArchive::CREATE | ZipArchive::OVERWRITE );
	$zip->addFromString( '[Content_Types].xml', '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"></Types>' );
	$zip->addFromString( '_rels/.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"></Relationships>' );
	$zip->addFromString( 'word/document.xml', '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body/></w:document>' );
	$zip->close();
	assert_ok( 'basic DOCX accepted', SC_EI_Upload_Validator::validate( fixture_file( $docx, 'safe.docx' ), $settings ) );

	$macro = $temp . '/macro.docx';
	copy( $docx, $macro );
	$zip = new ZipArchive();
	$zip->open( $macro );
	$zip->addFromString( 'word/vbaProject.bin', 'macro' );
	$zip->close();
	assert_error( 'macro Office file rejected', SC_EI_Upload_Validator::validate( fixture_file( $macro, 'macro.docx' ), $settings ), 'ooxml_active_content' );

	$link = $temp . '/hyperlink.docx';
	copy( $docx, $link );
	$zip = new ZipArchive();
	$zip->open( $link );
	$zip->addFromString(
		'word/_rels/document.xml.rels',
		'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/hyperlink" Target="https://example.com/" TargetMode="External"/></Relationships>'
	);
	$zip->close();
	$link_result = SC_EI_Upload_Validator::validate( fixture_file( $link, 'hyperlink.docx' ), $settings );
	assert_ok( 'safe Office hyperlink accepted', $link_result );
	if ( ! in_array( 'external_hyperlinks', $link_result['security_flags'], true ) ) {
		fwrite( STDERR, "Office hyperlink review flag missing.\n" );
		exit( 1 );
	}
	echo "PASS: Office hyperlink flagged\n";

	$template = $temp . '/template.docx';
	copy( $docx, $template );
	$zip = new ZipArchive();
	$zip->open( $template );
	$zip->addFromString(
		'word/_rels/settings.xml.rels',
		'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/attachedTemplate" Target="https://example.com/template.dotm" TargetMode="External"/></Relationships>'
	);
	$zip->close();
	assert_error( 'external Office template rejected', SC_EI_Upload_Validator::validate( fixture_file( $template, 'template.docx' ), $settings ), 'ooxml_external_content' );
} else {
	echo "SKIP: DOCX fixtures because ZipArchive is unavailable\n";
}

echo "Engagement Intake v0.11.0 validator fixtures passed.\n";
