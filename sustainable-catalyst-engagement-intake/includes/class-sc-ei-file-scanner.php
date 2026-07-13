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

		$result = apply_filters( 'sc_ei_scan_attachment', $default, $absolute_path, $metadata );
		$result = is_array( $result ) ? wp_parse_args( $result, $default ) : $default;

		$allowed = array( 'not_configured', 'clean', 'infected', 'error', 'skipped' );
		$status  = sanitize_key( (string) $result['status'] );

		if ( ! in_array( $status, $allowed, true ) ) {
			$status = 'error';
		}

		return array(
			'status'   => $status,
			'provider' => sanitize_text_field( (string) $result['provider'] ),
			'message'  => sanitize_textarea_field( (string) $result['message'] ),
		);
	}

	public static function probe(): array {
		$probe = apply_filters(
			'sc_ei_scanner_probe',
			array(
				'configured' => false,
				'provider'   => 'none',
				'message'    => __( 'No external malware scanner integration reported itself as configured.', 'sustainable-catalyst-engagement-intake' ),
			)
		);

		$probe = is_array( $probe ) ? $probe : array();

		return array(
			'configured' => ! empty( $probe['configured'] ),
			'provider'   => sanitize_text_field( (string) ( $probe['provider'] ?? 'none' ) ),
			'message'    => sanitize_text_field( (string) ( $probe['message'] ?? '' ) ),
		);
	}
}
