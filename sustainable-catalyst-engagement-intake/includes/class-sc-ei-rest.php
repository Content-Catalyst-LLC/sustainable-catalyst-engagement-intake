<?php
/**
 * REST API.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_REST {

	public static function register(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes(): void {
		register_rest_route(
			'sc-engagement-intake/v1',
			'/status',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'status' ),
				'permission_callback' => static fn(): bool => current_user_can( 'sc_intake_manage_settings' ),
			)
		);

		register_rest_route(
			'sc-engagement-intake/v1',
			'/inquiries',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'inquiries' ),
				'permission_callback' => static fn(): bool => current_user_can( 'sc_intake_view' ),
				'args'                => array(
					'status'       => array( 'sanitize_callback' => 'sanitize_key' ),
					'inquiry_type' => array( 'sanitize_callback' => 'sanitize_key' ),
					'search'       => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'page'         => array( 'sanitize_callback' => 'absint', 'default' => 1 ),
					'per_page'     => array( 'sanitize_callback' => 'absint', 'default' => 20 ),
				),
			)
		);

		register_rest_route(
			'sc-engagement-intake/v1',
			'/inquiries/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'inquiry' ),
				'permission_callback' => static fn(): bool => current_user_can( 'sc_intake_view' ),
				'args'                => array(
					'id' => array( 'sanitize_callback' => 'absint' ),
				),
			)
		);

		register_rest_route(
			'sc-engagement-intake/v1',
			'/submit',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'submit' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public static function status(): WP_REST_Response {
		$results = SC_EI_Diagnostics::run();
		return new WP_REST_Response(
			array(
				'ok'      => 'healthy' === SC_EI_Diagnostics::overall_status( $results ),
				'version' => SC_EI_VERSION,
				'status'  => SC_EI_Diagnostics::overall_status( $results ),
				'checks'  => $results,
			)
		);
	}

	public static function inquiries( WP_REST_Request $request ): WP_REST_Response {
		$result = SC_EI_Inquiry_Repository::query(
			array(
				'status'       => $request->get_param( 'status' ),
				'inquiry_type' => $request->get_param( 'inquiry_type' ),
				'search'       => $request->get_param( 'search' ),
				'page'         => $request->get_param( 'page' ),
				'per_page'     => $request->get_param( 'per_page' ),
			)
		);

		return new WP_REST_Response( $result );
	}

	public static function inquiry( WP_REST_Request $request ) {
		$record = SC_EI_Inquiry_Repository::find( absint( $request['id'] ) );
		if ( ! $record ) {
			return new WP_Error( 'sc_ei_not_found', __( 'Inquiry not found.', 'sustainable-catalyst-engagement-intake' ), array( 'status' => 404 ) );
		}

		$record['attachments'] = SC_EI_Attachment_Repository::for_inquiry( absint( $request['id'] ) );
		$record['audit_log']   = SC_EI_Audit_Log::for_inquiry( absint( $request['id'] ) );

		return new WP_REST_Response( $record );
	}

	public static function submit( WP_REST_Request $request ) {
		$result = SC_EI_Form_Handler::process( $request->get_params() );

		if ( is_wp_error( $result ) ) {
			return new WP_Error(
				$result->get_error_code(),
				$result->get_error_message(),
				array( 'status' => self::status_for_error( $result->get_error_code() ) )
			);
		}

		return new WP_REST_Response(
			array(
				'ok'        => true,
				'reference' => $result['reference'],
				'status'            => $result['status'],
				'scheduling_status' => $result['scheduling_status'] ?? 'not_requested',
				'message'           => __( 'Your private inquiry record has been created.', 'sustainable-catalyst-engagement-intake' ),
			),
			201
		);
	}

	private static function status_for_error( string $code ): int {
		if ( in_array( $code, array( 'rate_limited' ), true ) ) {
			return 429;
		}
		if ( in_array( $code, array( 'storage_error' ), true ) ) {
			return 500;
		}
		return 400;
	}
}
