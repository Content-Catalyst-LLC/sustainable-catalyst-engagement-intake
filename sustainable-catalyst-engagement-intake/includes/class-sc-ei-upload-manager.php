<?php
/**
 * Secure public upload processing.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Upload_Manager {

	public static function process_inquiry_uploads( array $inquiry, array $files, array $context = array() ): array {
		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() );
		$items    = self::normalize_files( $files, 'documents' );

		$result = array(
			'accepted' => array(),
			'errors'   => array(),
			'count'    => 0,
		);

		if ( ! $items ) {
			return $result;
		}

		if ( ! SC_EI_Storage::ensure() ) {
			$result['errors'][] = __( 'Private document storage is unavailable. The inquiry was received without attachments.', 'sustainable-catalyst-engagement-intake' );
			SC_EI_Audit_Log::record(
				'attachment_storage_unavailable',
				'Public submission included documents, but protected storage could not be initialized.',
				array(),
				(int) $inquiry['id']
			);
			return $result;
		}

		$effective = SC_EI_Upload_Environment::effective_limits( $settings );
		$maximum   = $effective['max_files'];
		if ( count( $items ) > $maximum ) {
			$result['errors'][] = sprintf(
				/* translators: %d: maximum files */
				__( 'Only the first %d files were evaluated because the attachment limit was exceeded.', 'sustainable-catalyst-engagement-intake' ),
				$maximum
			);
			$items = array_slice( $items, 0, $maximum );
		}

		$seen             = array();
		$total_bytes      = 0;
		$aggregate_limit = $effective['max_total_bytes'];

		foreach ( $items as $position => $file ) {
			$file_size = absint( $file['size'] ?? 0 );
			if ( $total_bytes + $file_size > $aggregate_limit ) {
				$result['errors'][] = self::public_error(
					sanitize_file_name( (string) ( $file['name'] ?? '' ) ),
					__( 'The combined document size exceeds the safe request limit.', 'sustainable-catalyst-engagement-intake' )
				);
				continue;
			}
			$original_name = sanitize_file_name( (string) ( $file['name'] ?? '' ) );
			$validation    = SC_EI_Upload_Validator::validate( $file, $settings );

			if ( is_wp_error( $validation ) ) {
				$result['errors'][] = self::public_error( $original_name, $validation->get_error_message() );
				SC_EI_Audit_Log::record(
					'attachment_validation_rejected',
					'An uploaded document was rejected before private storage.',
					array(
						'position'      => $position + 1,
						'original_name' => $original_name,
						'error_code'    => $validation->get_error_code(),
					),
					(int) $inquiry['id']
				);
				continue;
			}

			$sha256 = $validation['sha256'];
			if ( isset( $seen[ $sha256 ] ) || SC_EI_Attachment_Repository::active_duplicate( (int) $inquiry['id'], $sha256 ) ) {
				$result['errors'][] = self::public_error( $original_name, __( 'A duplicate copy of this document was not stored.', 'sustainable-catalyst-engagement-intake' ) );
				SC_EI_Audit_Log::record(
					'attachment_duplicate_rejected',
					'A duplicate private attachment was rejected.',
					array(
						'original_name' => $original_name,
						'sha256'       => $sha256,
					),
					(int) $inquiry['id']
				);
				continue;
			}
			$seen[ $sha256 ] = true;
			$total_bytes += $file_size;

			$attachment_public_id = wp_generate_uuid4();
			$relative_path        = SC_EI_Storage::quarantine_relative_path( (string) $inquiry['public_id'], $attachment_public_id );
			$stored_name          = basename( $relative_path );

			$storage_result = SC_EI_Storage::store_uploaded_file_verified(
				(string) $file['tmp_name'],
				$relative_path,
				(int) $validation['size_bytes'],
				(string) $validation['sha256']
			);

			if ( is_wp_error( $storage_result ) ) {
				$result['errors'][] = self::public_error( $original_name, $storage_result->get_error_message() );
				SC_EI_Audit_Log::record(
					'attachment_storage_failed',
					'A validated document failed the reliable protected-storage transaction.',
					array(
						'original_name' => $original_name,
						'error_code'    => $storage_result->get_error_code(),
						'request_id'    => sanitize_text_field( (string) ( $context['request_id'] ?? '' ) ),
					),
					(int) $inquiry['id']
				);
				continue;
			}

			$absolute = (string) $storage_result['absolute_path'];
			$scan     = $absolute
				? SC_EI_File_Scanner::scan( $absolute, array_merge( $validation, $context ) )
				: array( 'status' => 'error', 'provider' => 'none', 'message' => 'Stored file path could not be resolved.' );

			$scanner_required = ! empty( $settings['require_external_scanner'] );
			$must_delete      = 'infected' === $scan['status'] || ( $scanner_required && 'clean' !== $scan['status'] );
			$now              = current_time( 'mysql', true );
			$retention_until  = gmdate(
				'Y-m-d H:i:s',
				time() + max( 7, absint( $settings['attachment_retention_days'] ?? 180 ) ) * DAY_IN_SECONDS
			);

			$metadata = array(
				'security_flags'  => $validation['security_flags'],
				'validation_meta' => $validation['validation_meta'],
				'client_mime'     => sanitize_mime_type( (string) ( $file['type'] ?? '' ) ),
				'form_variant'    => sanitize_key( (string) ( $context['form_variant'] ?? '' ) ),
				'source_page'     => sanitize_key( (string) ( $context['source_page'] ?? '' ) ),
				'request_id'      => sanitize_text_field( (string) ( $context['request_id'] ?? '' ) ),
				'post_move_verified' => 'yes',
			);

			$record_data = array(
				'inquiry_id'        => (int) $inquiry['id'],
				'public_id'         => $attachment_public_id,
				'original_name'     => $validation['original_name'],
				'stored_name'       => $stored_name,
				'relative_path'     => $relative_path,
				'mime_type'         => $validation['mime_type'],
				'detected_mime'     => $validation['detected_mime'],
				'extension'         => $validation['extension'],
				'size_bytes'        => $validation['size_bytes'],
				'sha256'            => $validation['sha256'],
				'signature_type'    => $validation['signature_type'],
				'validator_version' => SC_EI_VALIDATOR_VERSION,
				'document_category' => sanitize_key( (string) ( $context['document_category'] ?? 'other' ) ),
				'document_notes'    => sanitize_textarea_field( (string) ( $context['document_notes'] ?? '' ) ),
				'confidentiality'   => sanitize_key( (string) ( $context['document_confidentiality'] ?? 'non_confidential' ) ),
				'quarantine_status' => $must_delete ? 'rejected' : 'quarantined',
				'validation_status' => 'validated',
				'scan_status'       => $scan['status'],
				'scanner_provider'  => $scan['provider'],
				'scan_message'      => $scan['message'],
				'integrity_status'          => $must_delete ? 'deleted' : 'verified',
				'storage_status'            => $must_delete ? 'deleted' : 'healthy',
				'last_verified_at'          => $now,
				'last_verified_by'          => 0,
				'last_verification_source'  => 'upload',
				'last_verification_message' => $must_delete
					? 'File deleted immediately after scanner policy rejection.'
					: 'Atomic protected-storage transaction and SHA-256 verification passed.',
				'retention_until'           => $retention_until,
				'metadata'          => $metadata,
				'rejected_by'       => $must_delete ? 0 : null,
				'rejected_at'       => $must_delete ? $now : null,
				'uploaded_at'       => $now,
				'deleted_at'        => $must_delete ? $now : null,
			);

			if ( $must_delete ) {
				SC_EI_Storage::delete_file( $relative_path );
			}

			try {
				$attachment_id = SC_EI_Attachment_Repository::create( $record_data );
			} catch ( Throwable $exception ) {
				if ( ! $must_delete ) {
					SC_EI_Storage::delete_file( $relative_path );
				}
				$result['errors'][] = self::public_error( $original_name, __( 'The attachment metadata could not be recorded.', 'sustainable-catalyst-engagement-intake' ) );
				SC_EI_Audit_Log::record(
					'attachment_record_failed',
					'A protected file was removed because its metadata record could not be created.',
					array( 'original_name' => $original_name ),
					(int) $inquiry['id']
				);
				continue;
			}

			if ( $must_delete ) {
				$result['errors'][] = self::public_error(
					$original_name,
					'infected' === $scan['status']
						? __( 'The document was rejected by the configured malware scanner.', 'sustainable-catalyst-engagement-intake' )
						: __( 'The document was rejected because a clean external scan is required.', 'sustainable-catalyst-engagement-intake' )
				);

				SC_EI_Audit_Log::record(
					'attachment_scan_rejected',
					'An uploaded document was deleted after the scanner requirement was not satisfied.',
					array(
						'scan_status' => $scan['status'],
						'provider'    => $scan['provider'],
						'sha256'      => $validation['sha256'],
					),
					(int) $inquiry['id'],
					$attachment_id
				);
				continue;
			}

			SC_EI_Audit_Log::record(
				'attachment_quarantined',
				'A validated document was stored in protected quarantine.',
				array(
					'original_name'   => $validation['original_name'],
					'mime_type'       => $validation['mime_type'],
					'size_bytes'      => $validation['size_bytes'],
					'sha256'          => $validation['sha256'],
					'scan_status'     => $scan['status'],
					'security_flags'  => $validation['security_flags'],
					'retention_until' => $retention_until,
				),
				(int) $inquiry['id'],
				$attachment_id
			);

			$result['accepted'][] = array(
				'id'                => $attachment_id,
				'name'              => $validation['original_name'],
				'size_bytes'        => $validation['size_bytes'],
				'quarantine_status' => 'quarantined',
				'scan_status'       => $scan['status'],
			);
		}

		$result['count'] = count( $result['accepted'] );
		return $result;
	}

	public static function normalize_files( array $files, string $field ): array {
		if ( empty( $files[ $field ] ) || ! is_array( $files[ $field ] ) ) {
			return array();
		}

		$group = $files[ $field ];

		if ( ! is_array( $group['name'] ?? null ) ) {
			return array( $group );
		}

		$normalized = array();
		$count      = count( $group['name'] );

		for ( $index = 0; $index < $count; $index++ ) {
			if ( UPLOAD_ERR_NO_FILE === (int) ( $group['error'][ $index ] ?? UPLOAD_ERR_NO_FILE ) ) {
				continue;
			}

			$normalized[] = array(
				'name'     => $group['name'][ $index ] ?? '',
				'full_path'=> $group['full_path'][ $index ] ?? '',
				'type'     => $group['type'][ $index ] ?? '',
				'tmp_name' => $group['tmp_name'][ $index ] ?? '',
				'error'    => $group['error'][ $index ] ?? UPLOAD_ERR_NO_FILE,
				'size'     => $group['size'][ $index ] ?? 0,
			);
		}

		return $normalized;
	}

	private static function public_error( string $filename, string $message ): string {
		$filename = sanitize_file_name( $filename );
		if ( '' === $filename ) {
			return sanitize_text_field( $message );
		}
		return sprintf( '%1$s: %2$s', $filename, sanitize_text_field( $message ) );
	}
}
