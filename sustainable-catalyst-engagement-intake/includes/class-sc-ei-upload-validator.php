<?php
/**
 * Fail-closed upload validation.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Upload_Validator {

	public static function supported_extensions(): array {
		return array(
			'pdf'  => __( 'PDF document', 'sustainable-catalyst-engagement-intake' ),
			'docx' => __( 'Microsoft Word document', 'sustainable-catalyst-engagement-intake' ),
			'xlsx' => __( 'Microsoft Excel workbook', 'sustainable-catalyst-engagement-intake' ),
			'csv'  => __( 'CSV data file', 'sustainable-catalyst-engagement-intake' ),
			'txt'  => __( 'Plain text file', 'sustainable-catalyst-engagement-intake' ),
			'png'  => __( 'PNG image', 'sustainable-catalyst-engagement-intake' ),
			'jpg'  => __( 'JPEG image', 'sustainable-catalyst-engagement-intake' ),
			'jpeg' => __( 'JPEG image', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function wordpress_mimes( array $extensions ): array {
		$map = array(
			'pdf'       => 'application/pdf',
			'docx'      => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			'xlsx'      => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'csv'       => 'text/csv',
			'txt'       => 'text/plain',
			'png'       => 'image/png',
			'jpg|jpeg'  => 'image/jpeg',
		);

		$allowed = array();
		foreach ( $map as $pattern => $mime ) {
			$parts = explode( '|', $pattern );
			if ( array_intersect( $parts, $extensions ) ) {
				$allowed[ $pattern ] = $mime;
			}
		}
		return $allowed;
	}

	public static function validate( array $file, array $settings ) {
		$error = isset( $file['error'] ) ? absint( $file['error'] ) : UPLOAD_ERR_NO_FILE;
		if ( UPLOAD_ERR_OK !== $error ) {
			return self::upload_error( $error );
		}

		$tmp_name = isset( $file['tmp_name'] ) ? (string) $file['tmp_name'] : '';
		$name     = isset( $file['name'] ) ? sanitize_file_name( wp_unslash( (string) $file['name'] ) ) : '';
		$size     = isset( $file['size'] ) ? absint( $file['size'] ) : 0;

		if ( '' === $tmp_name || ! is_file( $tmp_name ) || '' === $name ) {
			return new WP_Error( 'invalid_upload', __( 'The uploaded file is incomplete or unavailable.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$max_bytes = max( 1, absint( $settings['upload_max_file_mb'] ?? 20 ) ) * MB_IN_BYTES;
		if ( $size < 1 ) {
			return new WP_Error( 'empty_file', __( 'Empty files are not accepted.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( $size > $max_bytes ) {
			return new WP_Error(
				'file_too_large',
				sprintf(
					/* translators: %d: maximum megabytes */
					__( 'The file exceeds the %d MB limit.', 'sustainable-catalyst-engagement-intake' ),
					absint( $settings['upload_max_file_mb'] ?? 20 )
				)
			);
		}

		$extension = strtolower( (string) pathinfo( $name, PATHINFO_EXTENSION ) );
		$allowed   = array_values( array_intersect(
			array_keys( self::supported_extensions() ),
			array_map( 'sanitize_key', (array) ( $settings['allowed_upload_extensions'] ?? array() ) )
		) );

		if ( ! in_array( $extension, $allowed, true ) ) {
			return new WP_Error( 'extension_not_allowed', __( 'This file type is not allowed.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$first_bytes = self::read_bytes( $tmp_name, 0, 32 );
		$executable  = self::executable_signature( $first_bytes );
		if ( $executable ) {
			return new WP_Error( 'executable_signature', __( 'The file has an executable or script signature and was rejected.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$detected_mime = self::detected_mime( $tmp_name );
		$wp_check      = wp_check_filetype_and_ext( $tmp_name, $name, self::wordpress_mimes( $allowed ) );

		if ( ! empty( $wp_check['ext'] ) ) {
			$wp_ext = strtolower( (string) $wp_check['ext'] );
			if ( ! self::extensions_equivalent( $extension, $wp_ext ) ) {
				return new WP_Error( 'extension_mismatch', __( 'The filename extension does not match the detected file type.', 'sustainable-catalyst-engagement-intake' ) );
			}
		}

		$validation = match ( $extension ) {
			'pdf'         => self::validate_pdf( $tmp_name, $detected_mime ),
			'docx'        => self::validate_ooxml( $tmp_name, 'docx', $detected_mime, $max_bytes ),
			'xlsx'        => self::validate_ooxml( $tmp_name, 'xlsx', $detected_mime, $max_bytes ),
			'png'         => self::validate_image( $tmp_name, IMAGETYPE_PNG, 'image/png', $detected_mime ),
			'jpg', 'jpeg' => self::validate_image( $tmp_name, IMAGETYPE_JPEG, 'image/jpeg', $detected_mime ),
			'csv'         => self::validate_text( $tmp_name, 'csv', $detected_mime ),
			'txt'         => self::validate_text( $tmp_name, 'txt', $detected_mime ),
			default       => new WP_Error( 'unsupported_type', __( 'The file type is unsupported.', 'sustainable-catalyst-engagement-intake' ) ),
		};

		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$sha256 = hash_file( 'sha256', $tmp_name );
		if ( ! is_string( $sha256 ) || ! preg_match( '/^[a-f0-9]{64}$/', $sha256 ) ) {
			return new WP_Error( 'hash_failed', __( 'The file integrity fingerprint could not be generated.', 'sustainable-catalyst-engagement-intake' ) );
		}

		return array(
			'original_name'   => $name,
			'extension'       => $extension,
			'mime_type'       => $validation['mime_type'],
			'detected_mime'   => $detected_mime,
			'size_bytes'      => $size,
			'sha256'          => $sha256,
			'signature_type'  => $validation['signature_type'],
			'security_flags'  => $validation['security_flags'] ?? array(),
			'validation_meta' => $validation['metadata'] ?? array(),
		);
	}

	/**
	 * Side-effect-bounded runtime probe for clean text and disguised executable rejection.
	 */
	public static function runtime_security_probe(): array {
		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() );
		$settings['allowed_upload_extensions'] = array_values( array_unique( array_merge( (array) $settings['allowed_upload_extensions'], array( 'txt' ) ) ) );
		$clean = wp_tempnam( 'sc-ei-clean.txt' );
		$blocked = wp_tempnam( 'sc-ei-blocked.txt' );
		if ( ! $clean || ! $blocked ) {
			if ( $clean && is_file( $clean ) ) unlink( $clean );
			if ( $blocked && is_file( $blocked ) ) unlink( $blocked );
			return array( 'passed' => false, 'detail' => 'temporary probe files could not be created' );
		}
		try {
			file_put_contents( $clean, "Sustainable Catalyst upload validation probe\n", LOCK_EX );
			file_put_contents( $blocked, "<?php echo 'unsafe'; ?>", LOCK_EX );
			$clean_result = self::validate( array( 'name' => 'probe.txt', 'tmp_name' => $clean, 'size' => filesize( $clean ), 'error' => UPLOAD_ERR_OK ), $settings );
			$blocked_result = self::validate( array( 'name' => 'notes.txt', 'tmp_name' => $blocked, 'size' => filesize( $blocked ), 'error' => UPLOAD_ERR_OK ), $settings );
			$blocked_code = is_wp_error( $blocked_result ) ? $blocked_result->get_error_code() : '';
			$passed = ! is_wp_error( $clean_result ) && 'executable_signature' === $blocked_code;
			return array(
				'passed' => $passed,
				'clean_accepted' => ! is_wp_error( $clean_result ),
				'executable_rejected' => 'executable_signature' === $blocked_code,
				'detail' => $passed ? 'clean text accepted and disguised executable rejected' : 'upload validator runtime probe failed',
			);
		} finally {
			if ( is_file( $clean ) ) unlink( $clean );
			if ( is_file( $blocked ) ) unlink( $blocked );
		}
	}

	private static function validate_pdf( string $path, string $detected_mime ) {
		$header = self::read_bytes( $path, 0, 8 );
		if ( ! str_starts_with( $header, '%PDF-' ) ) {
			return new WP_Error( 'pdf_signature', __( 'The PDF signature is invalid.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( ! in_array( $detected_mime, array( 'application/pdf', 'application/octet-stream' ), true ) ) {
			return new WP_Error( 'pdf_mime', __( 'The detected MIME type does not match a PDF document.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$contents = file_get_contents( $path );
		if ( false === $contents ) {
			return new WP_Error( 'pdf_read_failed', __( 'The PDF could not be inspected.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$blocked = array(
			'/\/Encrypt\b/i'      => 'encrypted_pdf',
			'/\/JavaScript\b/i'   => 'pdf_javascript',
			'/\/JS\b/i'           => 'pdf_javascript',
			'/\/Launch\b/i'       => 'pdf_launch_action',
			'/\/EmbeddedFile\b/i' => 'pdf_embedded_file',
			'/\/RichMedia\b/i'    => 'pdf_rich_media',
			'/\/XFA\b/i'          => 'pdf_xfa',
		);

		foreach ( $blocked as $pattern => $code ) {
			if ( preg_match( $pattern, $contents ) ) {
				return new WP_Error( $code, __( 'Encrypted, scripted, embedded, or active-content PDFs are not accepted.', 'sustainable-catalyst-engagement-intake' ) );
			}
		}

		if ( false === strrpos( $contents, '%%EOF' ) ) {
			return new WP_Error( 'pdf_eof', __( 'The PDF appears incomplete or malformed.', 'sustainable-catalyst-engagement-intake' ) );
		}

		return array(
			'mime_type'      => 'application/pdf',
			'signature_type' => 'pdf',
			'security_flags' => array(),
			'metadata'       => array(),
		);
	}

	private static function validate_ooxml( string $path, string $type, string $detected_mime, int $max_bytes ) {
		if ( ! class_exists( 'ZipArchive' ) ) {
			return new WP_Error( 'ziparchive_required', __( 'DOCX and XLSX validation requires the PHP ZipArchive extension.', 'sustainable-catalyst-engagement-intake' ) );
		}

		if ( ! in_array(
			$detected_mime,
			array(
				'application/zip',
				'application/x-zip',
				'application/x-zip-compressed',
				'application/octet-stream',
				'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			),
			true
		) ) {
			return new WP_Error( 'ooxml_mime', __( 'The detected MIME type does not match an Office Open XML document.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$signature = self::read_bytes( $path, 0, 4 );
		if ( ! str_starts_with( $signature, "PK\x03\x04" ) && ! str_starts_with( $signature, "PK\x05\x06" ) ) {
			return new WP_Error( 'ooxml_signature', __( 'The Office document container signature is invalid.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$zip_flags = defined( 'ZipArchive::RDONLY' ) ? ZipArchive::RDONLY : 0;
		$zip       = new ZipArchive();
		$open      = $zip->open( $path, $zip_flags );
		if ( true !== $open ) {
			return new WP_Error( 'ooxml_open', __( 'The Office document container could not be opened.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$required = 'docx' === $type
			? array( '[Content_Types].xml', '_rels/.rels', 'word/document.xml' )
			: array( '[Content_Types].xml', '_rels/.rels', 'xl/workbook.xml' );

		$locate_flags = defined( 'ZipArchive::FL_NOCASE' ) ? ZipArchive::FL_NOCASE : 0;

		foreach ( $required as $entry ) {
			$index = $zip->locateName( $entry, $locate_flags );
			$stat  = false === $index ? false : $zip->statIndex( $index );

			if ( false === $index || ! is_array( $stat ) || (int) ( $stat['size'] ?? 0 ) > 20 * MB_IN_BYTES || false === $zip->getFromIndex( $index ) ) {
				$zip->close();
				return new WP_Error( 'ooxml_required_entry', __( 'The Office document is missing, encrypted, or contains an oversized required component.', 'sustainable-catalyst-engagement-intake' ) );
			}
		}

		$blocked_patterns = array(
			'vbaproject.bin',
			'/macrosheets/',
			'/activex/',
			'/embeddings/',
			'/customui/',
			'/externallinks/',
			'connections.xml',
			'oleobject',
		);

		$entry_count        = $zip->numFiles;
		$uncompressed_total = 0;
		$compressed_total   = 0;
		$security_flags     = array();

		if ( $entry_count > 2000 ) {
			$zip->close();
			return new WP_Error( 'archive_entry_limit', __( 'The Office document contains too many archive entries.', 'sustainable-catalyst-engagement-intake' ) );
		}

		for ( $index = 0; $index < $entry_count; $index++ ) {
			$stat = $zip->statIndex( $index );
			if ( ! is_array( $stat ) ) {
				$zip->close();
				return new WP_Error( 'archive_stat_failed', __( 'The Office document could not be fully inspected.', 'sustainable-catalyst-engagement-intake' ) );
			}

			$name = strtolower( str_replace( '\\', '/', (string) ( $stat['name'] ?? '' ) ) );
			foreach ( $blocked_patterns as $pattern ) {
				if ( str_contains( $name, $pattern ) ) {
					$zip->close();
					return new WP_Error( 'ooxml_active_content', __( 'Macro-enabled, embedded-object, external-link, or active Office content is not accepted.', 'sustainable-catalyst-engagement-intake' ) );
				}
			}

			if ( str_ends_with( $name, '.rels' ) || str_ends_with( $name, '.xml' ) ) {
				$entry_contents = $zip->getFromIndex( $index );
				if ( false === $entry_contents ) {
					$zip->close();
					return new WP_Error( 'ooxml_entry_read', __( 'An Office document component could not be inspected and may be encrypted.', 'sustainable-catalyst-engagement-intake' ) );
				}

				if ( preg_match( '/(?:javascript|file|ms-msdt|shell):/i', $entry_contents ) || preg_match( '/\bDDE(?:AUTO)?\b/i', $entry_contents ) ) {
					$zip->close();
					return new WP_Error( 'ooxml_unsafe_content', __( 'Office documents with DDE fields or unsafe URI schemes are not accepted.', 'sustainable-catalyst-engagement-intake' ) );
				}

				if ( 'docx' === $type && preg_match( '/<w:instrText\b[^>]*>[^<]*\b(?:INCLUDETEXT|INCLUDEPICTURE|LINK)\b/i', $entry_contents ) ) {
					$zip->close();
					return new WP_Error( 'ooxml_remote_field', __( 'Word documents with remote include or linked field instructions are not accepted.', 'sustainable-catalyst-engagement-intake' ) );
				}

				if ( 'xlsx' === $type && preg_match( '/<f\b[^>]*>[^<]*\b(?:WEBSERVICE|RTD|FILTERXML|IMAGE)\s*\(/i', $entry_contents ) ) {
					$zip->close();
					return new WP_Error( 'ooxml_remote_formula', __( 'Excel workbooks with remote-data or network-capable formulas are not accepted.', 'sustainable-catalyst-engagement-intake' ) );
				}

				if ( 'xlsx' === $type && preg_match( '/<f\b[^>]*>[^<]*\bHYPERLINK\s*\(/i', $entry_contents ) ) {
					$security_flags[] = 'spreadsheet_hyperlink_formulas';
				}

				if ( str_ends_with( $name, '.rels' ) && preg_match_all( '/<Relationship\b([^>]*)\/?\s*>/i', $entry_contents, $relationships ) ) {
					foreach ( $relationships[1] as $attributes ) {
						if ( ! preg_match( '/TargetMode\s*=\s*["\']External["\']/i', $attributes ) ) {
							continue;
						}

						preg_match( '/Type\s*=\s*["\']([^"\']+)["\']/i', $attributes, $type_match );
						preg_match( '/Target\s*=\s*["\']([^"\']+)["\']/i', $attributes, $target_match );

						$relationship_type = strtolower( (string) ( $type_match[1] ?? '' ) );
						$target            = html_entity_decode( (string) ( $target_match[1] ?? '' ), ENT_QUOTES | ENT_XML1, 'UTF-8' );
						$is_hyperlink      = str_ends_with( $relationship_type, '/hyperlink' );
						$is_safe_target    = (bool) preg_match( '#^(?:https?://|mailto:)#i', $target );

						if ( $is_hyperlink && $is_safe_target ) {
							$security_flags[] = 'external_hyperlinks';
							continue;
						}

						$zip->close();
						return new WP_Error( 'ooxml_external_content', __( 'Office documents with external templates, linked data, remote media, or other non-hyperlink external relationships are not accepted.', 'sustainable-catalyst-engagement-intake' ) );
					}
				}
			}

			if ( isset( $stat['encryption_method'] ) && 0 !== (int) $stat['encryption_method'] ) {
				$zip->close();
				return new WP_Error( 'ooxml_encrypted', __( 'Password-protected or encrypted Office documents are not accepted.', 'sustainable-catalyst-engagement-intake' ) );
			}

			$uncompressed_total += max( 0, (int) ( $stat['size'] ?? 0 ) );
			$compressed_total   += max( 0, (int) ( $stat['comp_size'] ?? 0 ) );
		}

		$zip->close();

		$uncompressed_limit = max( 100 * MB_IN_BYTES, $max_bytes * 5 );
		if ( $uncompressed_total > $uncompressed_limit ) {
			return new WP_Error( 'archive_expansion_limit', __( 'The Office document expands beyond the safe inspection limit.', 'sustainable-catalyst-engagement-intake' ) );
		}

		if ( $compressed_total > 0 && ( $uncompressed_total / $compressed_total ) > 100 ) {
			return new WP_Error( 'archive_ratio_limit', __( 'The Office document has an unsafe compression ratio.', 'sustainable-catalyst-engagement-intake' ) );
		}

		return array(
			'mime_type'      => 'docx' === $type
				? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
				: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
			'signature_type' => $type,
			'security_flags' => array_values( array_unique( $security_flags ) ),
			'metadata'       => array(
				'archive_entries'            => $entry_count,
				'archive_uncompressed_bytes' => $uncompressed_total,
				'archive_compressed_bytes'   => $compressed_total,
			),
		);
	}

	private static function validate_image( string $path, int $expected_type, string $expected_mime, string $detected_mime ) {
		if ( $detected_mime !== $expected_mime ) {
			return new WP_Error( 'image_mime', __( 'The detected MIME type does not match the image extension.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$info = @getimagesize( $path );
		if ( ! is_array( $info ) || (int) ( $info[2] ?? 0 ) !== $expected_type ) {
			return new WP_Error( 'image_signature', __( 'The image could not be decoded as the declared file type.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$pixels = (int) $info[0] * (int) $info[1];
		if ( $pixels < 1 || $pixels > 50000000 ) {
			return new WP_Error( 'image_dimensions', __( 'The image dimensions exceed the safe processing limit.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$sample = self::read_bytes( $path, 0, min( 1024 * 1024, (int) filesize( $path ) ) );
		if ( false !== stripos( $sample, '<?php' ) || false !== stripos( $sample, '<script' ) ) {
			return new WP_Error( 'image_polyglot', __( 'The image contains embedded script markers and was rejected.', 'sustainable-catalyst-engagement-intake' ) );
		}

		return array(
			'mime_type'      => $expected_mime,
			'signature_type' => IMAGETYPE_PNG === $expected_type ? 'png' : 'jpeg',
			'security_flags' => array(),
			'metadata'       => array(
				'width'  => (int) $info[0],
				'height' => (int) $info[1],
			),
		);
	}

	private static function validate_text( string $path, string $type, string $detected_mime ) {
		if ( ! str_starts_with( $detected_mime, 'text/' ) && ! in_array( $detected_mime, array( 'application/csv', 'application/vnd.ms-excel', 'application/octet-stream' ), true ) ) {
			return new WP_Error( 'text_mime', __( 'The detected MIME type does not match a text or CSV file.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$sample = self::read_bytes( $path, 0, min( 1024 * 1024, (int) filesize( $path ) ) );
		if ( str_contains( $sample, "\0" ) ) {
			return new WP_Error( 'binary_text', __( 'The text file contains binary null bytes and was rejected.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$normalized_sample = preg_replace( '/^\xEF\xBB\xBF/', '', $sample );
		if ( preg_match( '/^\s*(?:#!|<\?php|<script|MZ)/i', (string) $normalized_sample ) ) {
			return new WP_Error( 'script_text', __( 'Script or executable text files are not accepted.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$flags = array();
		if ( 'csv' === $type && preg_match( '/(?:^|[\r\n,;\t])\s*(?:[=@+]|\-(?=\s*[A-Za-z@]))\s*[^,\r\n;\t]+/m', (string) $normalized_sample ) ) {
			$flags[] = 'csv_formula_content';
		}

		return array(
			'mime_type'      => 'csv' === $type ? 'text/csv' : 'text/plain',
			'signature_type' => $type,
			'security_flags' => $flags,
			'metadata'       => array(),
		);
	}

	private static function executable_signature( string $bytes ): string {
		if ( str_starts_with( $bytes, 'MZ' ) ) {
			return 'windows_executable';
		}
		if ( str_starts_with( $bytes, "\x7fELF" ) ) {
			return 'elf_executable';
		}
		if ( in_array( substr( $bytes, 0, 4 ), array( "\xCF\xFA\xED\xFE", "\xCE\xFA\xED\xFE", "\xFE\xED\xFA\xCF", "\xFE\xED\xFA\xCE" ), true ) ) {
			return 'mach_o_executable';
		}
		if ( preg_match( '/^\s*(?:#!|<\?php)/i', $bytes ) ) {
			return 'script';
		}
		return '';
	}

	private static function detected_mime( string $path ): string {
		if ( class_exists( 'finfo' ) ) {
			$finfo = new finfo( FILEINFO_MIME_TYPE );
			$mime  = $finfo->file( $path );
			if ( is_string( $mime ) && '' !== $mime ) {
				return strtolower( trim( $mime ) );
			}
		}

		if ( function_exists( 'mime_content_type' ) ) {
			$mime = mime_content_type( $path );
			if ( is_string( $mime ) && '' !== $mime ) {
				return strtolower( trim( $mime ) );
			}
		}

		return 'application/octet-stream';
	}

	private static function read_bytes( string $path, int $offset, int $length ): string {
		$handle = @fopen( $path, 'rb' );
		if ( false === $handle ) {
			return '';
		}
		if ( $offset > 0 ) {
			fseek( $handle, $offset );
		}
		$data = fread( $handle, max( 1, $length ) );
		fclose( $handle );
		return is_string( $data ) ? $data : '';
	}

	private static function extensions_equivalent( string $first, string $second ): bool {
		$jpeg = array( 'jpg', 'jpeg' );
		if ( in_array( $first, $jpeg, true ) && in_array( $second, $jpeg, true ) ) {
			return true;
		}
		return $first === $second;
	}

	private static function upload_error( int $error ): WP_Error {
		$messages = array(
			UPLOAD_ERR_INI_SIZE   => __( 'The file exceeds the server upload limit.', 'sustainable-catalyst-engagement-intake' ),
			UPLOAD_ERR_FORM_SIZE  => __( 'The file exceeds the form upload limit.', 'sustainable-catalyst-engagement-intake' ),
			UPLOAD_ERR_PARTIAL    => __( 'The file was only partially uploaded.', 'sustainable-catalyst-engagement-intake' ),
			UPLOAD_ERR_NO_FILE    => __( 'No file was uploaded.', 'sustainable-catalyst-engagement-intake' ),
			UPLOAD_ERR_NO_TMP_DIR => __( 'The server upload directory is unavailable.', 'sustainable-catalyst-engagement-intake' ),
			UPLOAD_ERR_CANT_WRITE => __( 'The server could not write the uploaded file.', 'sustainable-catalyst-engagement-intake' ),
			UPLOAD_ERR_EXTENSION  => __( 'A server extension stopped the upload.', 'sustainable-catalyst-engagement-intake' ),
		);

		return new WP_Error( 'upload_error_' . $error, $messages[ $error ] ?? __( 'The file upload failed.', 'sustainable-catalyst-engagement-intake' ) );
	}
}
