<?php
/**
 * Upload request envelope, server limits, and cache/CDN reliability controls.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Upload_Environment {

	public static function register(): void {
		add_action( 'admin_init', array( __CLASS__, 'intercept_oversized_admin_post' ), 0 );
		add_filter( 'rest_pre_dispatch', array( __CLASS__, 'intercept_oversized_rest_request' ), 5, 3 );
		add_filter( 'rest_post_dispatch', array( __CLASS__, 'protect_rest_response' ), 20, 3 );
		add_action( 'init', array( __CLASS__, 'cleanup_stale_request_locks' ), 20 );
	}

	public static function limits(): array {
		$post_max      = self::ini_bytes( (string) ini_get( 'post_max_size' ) );
		$upload_max    = self::ini_bytes( (string) ini_get( 'upload_max_filesize' ) );
		$memory_limit  = self::ini_bytes( (string) ini_get( 'memory_limit' ) );
		$max_files     = max( 1, absint( ini_get( 'max_file_uploads' ) ) ?: 20 );
		$tmp_dir       = self::temporary_directory();
		$content_length= self::content_length();

		return array(
			'file_uploads_enabled' => self::ini_boolean( (string) ini_get( 'file_uploads' ) ),
			'post_max_bytes'       => $post_max,
			'upload_max_bytes'     => $upload_max,
			'memory_limit_bytes'   => $memory_limit,
			'max_file_uploads'     => $max_files,
			'max_input_time'       => (int) ini_get( 'max_input_time' ),
			'max_execution_time'   => (int) ini_get( 'max_execution_time' ),
			'content_length'       => $content_length,
			'temporary_directory'  => $tmp_dir,
			'temporary_exists'     => is_dir( $tmp_dir ),
			'temporary_writable'   => is_dir( $tmp_dir ) && is_writable( $tmp_dir ),
			'request_exceeds_post' => self::request_exceeds_post_max(),
			'cloudflare_detected'  => ! empty( $_SERVER['HTTP_CF_RAY'] ),
			'cloudflare_cache'     => sanitize_text_field( (string) ( $_SERVER['HTTP_CF_CACHE_STATUS'] ?? '' ) ),
			'request_method'       => sanitize_key( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ),
		);
	}

	public static function effective_limits( array $settings ): array {
		$server      = self::limits();
		$plugin_files= max( 1, min( 10, absint( $settings['upload_max_files'] ?? 5 ) ) );
		$plugin_bytes= max( MB_IN_BYTES, absint( $settings['upload_max_file_mb'] ?? 20 ) * MB_IN_BYTES );
		$post_budget = $server['post_max_bytes'] > 0
			? max( MB_IN_BYTES, (int) floor( $server['post_max_bytes'] * 0.90 ) )
			: $plugin_files * $plugin_bytes;

		$max_total = min( $plugin_files * $plugin_bytes, $post_budget );
		$max_file  = $server['upload_max_bytes'] > 0
			? min( $plugin_bytes, $server['upload_max_bytes'], $max_total )
			: min( $plugin_bytes, $max_total );

		return array(
			'max_files'       => min( $plugin_files, $server['max_file_uploads'] ),
			'max_file_bytes'  => max( MB_IN_BYTES, $max_file ),
			'max_total_bytes' => max( MB_IN_BYTES, $max_total ),
		);
	}

	public static function validate_request_envelope( array $raw, array $files ) {
		if ( self::request_exceeds_post_max() ) {
			return new WP_Error(
				'request_too_large',
				__( 'The submission exceeded the server request-size limit before WordPress could read it. Reduce the combined document size and submit again.', 'sustainable-catalyst-engagement-intake' )
			);
		}

		$limits = self::limits();
		if ( ! $limits['file_uploads_enabled'] && self::request_declares_documents( $raw ) ) {
			return new WP_Error(
				'uploads_disabled',
				__( 'File uploads are disabled on the server. The inquiry can be submitted after removing the selected documents.', 'sustainable-catalyst-engagement-intake' )
			);
		}

		if ( self::request_declares_documents( $raw ) && ! $limits['temporary_writable'] ) {
			return new WP_Error(
				'upload_temp_unavailable',
				__( 'The server upload-temporary directory is unavailable or not writable. The inquiry can be submitted after removing the selected documents.', 'sustainable-catalyst-engagement-intake' )
			);
		}

		$expected = max( 0, absint( $raw['document_selection_count'] ?? 0 ) );
		$received = count( SC_EI_Upload_Manager::normalize_files( $files, 'documents' ) );

		if ( $expected > $received ) {
			return new WP_Error(
				'upload_truncated',
				sprintf(
					/* translators: 1: selected files, 2: files received */
					__( 'The browser selected %1$d documents, but the server received only %2$d. Reduce the number or size of the files and submit again.', 'sustainable-catalyst-engagement-intake' ),
					$expected,
					$received
				)
			);
		}

		return true;
	}

	public static function request_exceeds_post_max(): bool {
		$post_max = self::ini_bytes( (string) ini_get( 'post_max_size' ) );
		$length   = self::content_length();

		return $post_max > 0 && $length > $post_max;
	}

	public static function content_length(): int {
		return max( 0, (int) ( $_SERVER['CONTENT_LENGTH'] ?? 0 ) );
	}

	public static function temporary_directory(): string {
		$configured = trim( (string) ini_get( 'upload_tmp_dir' ) );
		if ( '' !== $configured ) {
			return wp_normalize_path( $configured );
		}

		return wp_normalize_path( sys_get_temp_dir() );
	}

	public static function no_cache_headers(): array {
		return array(
			'Cache-Control'                => 'private, no-store, no-cache, must-revalidate, max-age=0',
			'Pragma'                       => 'no-cache',
			'Expires'                      => 'Wed, 11 Jan 1984 05:00:00 GMT',
			'CDN-Cache-Control'            => 'no-store',
			'Cloudflare-CDN-Cache-Control' => 'no-store',
			'Surrogate-Control'            => 'no-store',
			'Vary'                         => 'Cookie',
		);
	}

	public static function send_no_cache_headers(): void {
		if ( headers_sent() ) {
			return;
		}

		foreach ( self::no_cache_headers() as $name => $value ) {
			header( $name . ': ' . $value, 'Vary' !== $name );
		}
	}

	public static function intercept_oversized_admin_post(): void {
		if ( 'POST' !== strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) || ! self::request_exceeds_post_max() ) {
			return;
		}

		$script = basename( (string) ( $_SERVER['SCRIPT_NAME'] ?? '' ) );
		$is_intake_submission = isset( $_GET['sc_ei_submission'] ) && '1' === sanitize_text_field( wp_unslash( (string) $_GET['sc_ei_submission'] ) );
		if ( 'admin-post.php' !== $script || ! $is_intake_submission ) {
			return;
		}

		$redirect = wp_get_referer();
		if ( ! $redirect ) {
			$redirect = home_url( '/' );
		}

		$redirect = add_query_arg(
			array(
				'sc_ei_result' => 'error',
				'sc_ei_error'  => 'request_too_large',
			),
			$redirect
		);

		wp_safe_redirect( $redirect, 303 );
		exit;
	}

	public static function intercept_oversized_rest_request( $result, WP_REST_Server $server, WP_REST_Request $request ) {
		if ( '/sc-engagement-intake/v1/submit' !== $request->get_route() || ! self::request_exceeds_post_max() ) {
			return $result;
		}

		return new WP_Error(
			'request_too_large',
			__( 'The submission exceeded the server request-size limit before WordPress could read it. Reduce the combined document size and submit again.', 'sustainable-catalyst-engagement-intake' ),
			array( 'status' => 413 )
		);
	}

	public static function protect_rest_response( $response, WP_REST_Server $server, WP_REST_Request $request ) {
		if ( '/sc-engagement-intake/v1/submit' !== $request->get_route() || ! $response instanceof WP_REST_Response ) {
			return $response;
		}

		foreach ( self::no_cache_headers() as $name => $value ) {
			$response->header( $name, $value );
		}

		return $response;
	}

	public static function cleanup_stale_request_locks(): int {
		if ( get_transient( 'sc_ei_request_lock_cleanup_throttle' ) ) {
			return 0;
		}

		set_transient( 'sc_ei_request_lock_cleanup_throttle', 1, HOUR_IN_SECONDS );

		global $wpdb;
		$like = $wpdb->esc_like( 'sc_ei_lock_' ) . '%';
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s LIMIT 500", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$like
			),
			ARRAY_A
		);

		$deleted = 0;
		$cutoff  = time() - HOUR_IN_SECONDS;

		foreach ( (array) $rows as $row ) {
			if ( absint( $row['option_value'] ?? 0 ) >= $cutoff ) {
				continue;
			}
			if ( delete_option( (string) $row['option_name'] ) ) {
				$deleted++;
			}
		}

		return $deleted;
	}

	public static function ini_bytes( string $value ): int {
		$value = trim( $value );
		if ( '' === $value || '-1' === $value ) {
			return 0;
		}

		$unit   = strtolower( substr( $value, -1 ) );
		$number = (float) $value;

		return match ( $unit ) {
			'g'     => (int) round( $number * 1024 * 1024 * 1024 ),
			'm'     => (int) round( $number * 1024 * 1024 ),
			'k'     => (int) round( $number * 1024 ),
			default => max( 0, (int) $number ),
		};
	}

	private static function request_declares_documents( array $raw ): bool {
		return absint( $raw['document_selection_count'] ?? 0 ) > 0
			|| ! empty( $raw['document_upload_consent'] );
	}

	private static function ini_boolean( string $value ): bool {
		$value = strtolower( trim( $value ) );
		return ! in_array( $value, array( '', '0', 'off', 'false', 'no', 'none' ), true );
	}
}
