<?php
/**
 * Scanner readiness, test-file, retry, and required-mode safeguards.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Scanner_Operations {

	private const READINESS_OPTION = 'sc_ei_scanner_readiness';

	public static function readiness( ?array $settings = null ): array {
		$settings = $settings ?: wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() );
		$probe    = SC_EI_File_Scanner::probe();
		$test     = get_option( self::READINESS_OPTION, array() );
		$test     = is_array( $test ) ? $test : array();
		$hours    = max( 1, min( 168, absint( $settings['scanner_test_freshness_hours'] ?? 24 ) ) );
		$tested   = isset( $test['completed_at_timestamp'] ) ? absint( $test['completed_at_timestamp'] ) : 0;
		$fresh    = $tested > 0 && $tested >= time() - ( $hours * HOUR_IN_SECONDS );
		$clean    = 'clean' === sanitize_key( (string) ( $test['scan_status'] ?? '' ) );
		$provider_match = sanitize_text_field( (string) ( $test['probe_provider'] ?? '' ) ) === sanitize_text_field( (string) $probe['provider'] );
		$tested_version  = sanitize_text_field( (string) ( $test['probe_integration_version'] ?? '' ) );
		$current_version = sanitize_text_field( (string) ( $probe['integration_version'] ?? '' ) );
		$version_match   = '' === $tested_version || '' === $current_version || $tested_version === $current_version;
		$config_match    = $provider_match && $version_match;
		$ready           = ! empty( $probe['configured'] ) && $fresh && $clean && $config_match && ! empty( $test['test_file_deleted'] );

		return array(
			'ready'                   => $ready,
			'probe'                   => $probe,
			'test'                    => $test,
			'test_fresh'              => $fresh,
			'test_clean'              => $clean,
			'configuration_match'     => $config_match,
			'freshness_hours'         => $hours,
			'require_clean_enabled'   => ! empty( $settings['require_external_scanner'] ),
			'policy_state'            => $ready ? 'ready' : ( ! empty( $probe['configured'] ) ? 'test_required' : 'not_configured' ),
		);
	}

	public static function can_enable_required_mode( ?array $settings = null ): bool {
		$readiness = self::readiness( $settings );
		return ! empty( $readiness['ready'] );
	}

	public static function run_readiness_test( int $actor_user_id ): array {
		$probe = SC_EI_File_Scanner::probe();
		$path  = wp_tempnam( 'sc-ei-scanner-readiness.txt' );

		if ( ! $path ) {
			$result = self::store_readiness_result(
				$probe,
				array(
					'status'   => 'error',
					'provider' => 'temporary_storage',
					'message'  => __( 'A temporary scanner-readiness file could not be created.', 'sustainable-catalyst-engagement-intake' ),
				),
				false,
				$actor_user_id
			);
			return $result;
		}

		try {
			$token   = wp_generate_uuid4();
			$content = "Sustainable Catalyst Engagement Intake scanner readiness test.\n"
				. "Request: {$token}\n"
				. "This is a benign generated test file and contains no submitted user content.\n";

			$written = @file_put_contents( $path, $content, LOCK_EX );
			if ( false === $written || (int) $written !== strlen( $content ) ) {
				return self::store_readiness_result(
					$probe,
					array(
						'status'   => 'error',
						'provider' => 'temporary_storage',
						'message'  => __( 'The temporary scanner-readiness file could not be written completely.', 'sustainable-catalyst-engagement-intake' ),
					),
					wp_delete_file( $path ),
					$actor_user_id
				);
			}

			$scan = SC_EI_File_Scanner::scan(
				$path,
				array(
					'test_mode'         => 'scanner_readiness',
					'original_name'     => 'sc-ei-scanner-readiness.txt',
					'mime_type'         => 'text/plain',
					'extension'         => 'txt',
					'size_bytes'        => strlen( $content ),
					'sha256'            => hash( 'sha256', $content ),
					'generated_by'      => 'engagement-intake-v0.8.0',
					'contains_user_data'=> 'no',
				)
			);

			$deleted = wp_delete_file( $path );
			return self::store_readiness_result( $probe, $scan, $deleted, $actor_user_id );
		} catch ( Throwable $exception ) {
			$deleted = ! file_exists( $path ) || wp_delete_file( $path );
			return self::store_readiness_result(
				$probe,
				array(
					'status'   => 'error',
					'provider' => 'test_exception',
					'message'  => $exception->getMessage() ?: __( 'The readiness test raised an exception.', 'sustainable-catalyst-engagement-intake' ),
				),
				$deleted,
				$actor_user_id
			);
		}
	}

	public static function rescan_attachment( int $attachment_id, int $actor_user_id, string $source = 'manual_retry' ): array {
		$attachment = SC_EI_Attachment_Repository::find( $attachment_id );
		if ( ! $attachment || ! empty( $attachment['deleted_at'] ) ) {
			return array(
				'ok'      => false,
				'status'  => 'unavailable',
				'message' => __( 'The attachment is unavailable for scanning.', 'sustainable-catalyst-engagement-intake' ),
			);
		}

		$verification = SC_EI_Attachment_Repository::verify_record( $attachment, $actor_user_id, 'scanner_retry' );
		if ( empty( $verification['ok'] ) ) {
			SC_EI_Attachment_Repository::update_scan_result(
				$attachment_id,
				array(
					'status'   => 'error',
					'provider' => 'storage_verification',
					'message'  => $verification['message'],
				),
				$actor_user_id,
				$source
			);

			return array(
				'ok'      => false,
				'status'  => 'error',
				'message' => $verification['message'],
			);
		}

		$absolute = SC_EI_Storage::absolute_path( (string) $attachment['relative_path'] );
		if ( ! $absolute || ! is_file( $absolute ) ) {
			return array(
				'ok'      => false,
				'status'  => 'error',
				'message' => __( 'The protected file could not be resolved for scanning.', 'sustainable-catalyst-engagement-intake' ),
			);
		}

		$scan = SC_EI_File_Scanner::scan(
			$absolute,
			array(
				'attachment_id'     => $attachment_id,
				'inquiry_id'        => absint( $attachment['inquiry_id'] ),
				'original_name'     => $attachment['original_name'],
				'mime_type'         => $attachment['mime_type'],
				'detected_mime'     => $attachment['detected_mime'],
				'extension'         => $attachment['extension'],
				'size_bytes'        => absint( $attachment['size_bytes'] ),
				'sha256'            => $attachment['sha256'],
				'quarantine_status' => $attachment['quarantine_status'],
				'operation_source'  => sanitize_key( $source ),
				'request_mode'      => 'administrative_rescan',
			)
		);

		$updated = SC_EI_Attachment_Repository::update_scan_result(
			$attachment_id,
			$scan,
			$actor_user_id,
			$source
		);

		if ( ! $updated ) {
			return array(
				'ok'      => false,
				'status'  => 'error',
				'message' => __( 'The scan completed, but its result could not be stored.', 'sustainable-catalyst-engagement-intake' ),
			);
		}

		if ( 'infected' === $scan['status'] ) {
			$deleted = SC_EI_Storage::delete_file( (string) $attachment['relative_path'] );
			if ( $deleted ) {
				SC_EI_Attachment_Repository::mark_deleted(
					$attachment_id,
					$actor_user_id,
					__( 'External scanner identified the attachment as infected; the physical file was deleted and the record was rejected.', 'sustainable-catalyst-engagement-intake' ),
					'rejected'
				);
			}

			return array(
				'ok'       => $deleted,
				'status'   => 'infected',
				'deleted'  => $deleted,
				'message'  => $deleted
					? __( 'The scanner reported infected. The physical file was deleted and rejected.', 'sustainable-catalyst-engagement-intake' )
					: __( 'The scanner reported infected, but the physical file could not be deleted. Downloads remain blocked and immediate administrative review is required.', 'sustainable-catalyst-engagement-intake' ),
			);
		}

		return array(
			'ok'      => 'clean' === $scan['status'],
			'status'  => $scan['status'],
			'message' => $scan['message'],
		);
	}

	public static function bulk_rescan( array $attachment_ids, int $actor_user_id, int $limit = 25 ): array {
		$ids = array_slice(
			array_values( array_unique( array_filter( array_map( 'absint', $attachment_ids ) ) ) ),
			0,
			max( 1, min( 50, $limit ) )
		);

		$result = array(
			'processed' => 0,
			'clean'     => 0,
			'infected'  => 0,
			'error'     => 0,
			'skipped'   => 0,
			'details'   => array(),
		);

		foreach ( $ids as $id ) {
			$scan = self::rescan_attachment( $id, $actor_user_id, 'bulk_retry' );
			$result['processed']++;
			$status = sanitize_key( (string) ( $scan['status'] ?? 'error' ) );
			if ( isset( $result[ $status ] ) ) {
				$result[ $status ]++;
			} else {
				$result['error']++;
			}
			if ( count( $result['details'] ) < 50 ) {
				$result['details'][] = array(
					'attachment_id' => $id,
					'status'        => $status,
					'message'       => sanitize_text_field( (string) ( $scan['message'] ?? '' ) ),
				);
			}
		}

		SC_EI_Audit_Log::record(
			'attachment_bulk_scan_completed',
			'Bulk attachment scanner retry completed.',
			$result,
			null,
			null,
			$actor_user_id
		);

		return $result;
	}

	private static function store_readiness_result( array $probe, array $scan, bool $deleted, int $actor_user_id ): array {
		$result = array(
			'completed_at_utc'       => current_time( 'mysql', true ),
			'completed_at_timestamp' => time(),
			'probe_configured'       => ! empty( $probe['configured'] ),
			'probe_provider'         => sanitize_text_field( (string) ( $probe['provider'] ?? 'none' ) ),
			'probe_message'          => sanitize_text_field( (string) ( $probe['message'] ?? '' ) ),
			'probe_integration_version'=> sanitize_text_field( (string) ( $probe['integration_version'] ?? '' ) ),
			'scan_status'            => sanitize_key( (string) ( $scan['status'] ?? 'error' ) ),
			'scan_provider'          => sanitize_text_field( (string) ( $scan['provider'] ?? 'unknown' ) ),
			'scan_message'           => sanitize_textarea_field( (string) ( $scan['message'] ?? '' ) ),
			'test_file_deleted'      => $deleted,
			'actor_user_id'          => $actor_user_id,
		);

		update_option( self::READINESS_OPTION, $result, false );

		SC_EI_Audit_Log::record(
			'scanner_readiness_test_completed',
			'External scanner readiness test completed with a generated benign file.',
			$result,
			null,
			null,
			$actor_user_id
		);

		return $result;
	}
}
