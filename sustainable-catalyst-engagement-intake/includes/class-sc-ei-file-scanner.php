<?php
/**
 * Optional malware-scanner bridge.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_File_Scanner {

	public static function scan( string $absolute_path, array $metadata ): array {
		$default = array(
			'status'   => 'not_configured',
			'provider' => 'none',
			'message'  => __( 'No external malware scanner is configured. The file remains quarantined for authorized review.', 'sustainable-catalyst-engagement-intake' ),
		);

		if ( ! is_file( $absolute_path ) || ! is_readable( $absolute_path ) ) {
			return array(
				'status'   => 'error',
				'provider' => 'storage',
				'message'  => __( 'The protected file is missing or unreadable, so it could not be scanned.', 'sustainable-catalyst-engagement-intake' ),
			);
		}

		try {
			$result = apply_filters( 'sc_ei_scan_attachment', $default, $absolute_path, $metadata );
		} catch ( Throwable $exception ) {
			return array(
				'status'   => 'error',
				'provider' => 'integration_exception',
				'message'  => sanitize_text_field( $exception->getMessage() ?: __( 'The scanner integration raised an exception.', 'sustainable-catalyst-engagement-intake' ) ),
			);
		}

		$result = is_array( $result ) ? wp_parse_args( $result, $default ) : $default;

		$allowed = array( 'not_configured', 'clean', 'infected', 'error', 'skipped' );
		$status  = sanitize_key( (string) $result['status'] );

		if ( ! in_array( $status, $allowed, true ) ) {
			$status = 'error';
		}

		$provider = sanitize_text_field( (string) $result['provider'] );
		if ( '' === $provider ) {
			$provider = 'unknown';
		}

		return array(
			'status'   => $status,
			'provider' => $provider,
			'message'  => sanitize_textarea_field( (string) $result['message'] ),
		);
	}

	public static function probe(): array {
		$default = array(
			'configured'        => false,
			'provider'          => 'none',
			'message'           => __( 'No external malware scanner integration reported itself as configured.', 'sustainable-catalyst-engagement-intake' ),
			'integration_version'=> '',
			'supports_test_file'=> false,
		);

		try {
			$probe = apply_filters( 'sc_ei_scanner_probe', $default );
		} catch ( Throwable $exception ) {
			$probe = array(
				'configured' => false,
				'provider'   => 'integration_exception',
				'message'    => $exception->getMessage() ?: __( 'The scanner probe raised an exception.', 'sustainable-catalyst-engagement-intake' ),
			);
		}

		$probe = is_array( $probe ) ? wp_parse_args( $probe, $default ) : $default;

		return array(
			'configured'         => ! empty( $probe['configured'] ),
			'provider'           => sanitize_text_field( (string) $probe['provider'] ) ?: 'none',
			'message'            => sanitize_text_field( (string) $probe['message'] ),
			'integration_version'=> sanitize_text_field( (string) $probe['integration_version'] ),
			'supports_test_file' => ! empty( $probe['supports_test_file'] ),
		);
	}
}
