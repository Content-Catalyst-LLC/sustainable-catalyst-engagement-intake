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
					'form_variant' => array( 'sanitize_callback' => 'sanitize_key' ),
					'source_page'   => array( 'sanitize_callback' => 'sanitize_key' ),
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
			'/workflow-core/cases',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'workflow_core_cases' ),
				'permission_callback' => static fn(): bool => current_user_can( 'sc_intake_view_workflow_core' ),
				'args'                => array(
					'stage'       => array( 'sanitize_callback' => 'sanitize_key' ),
					'state'       => array( 'sanitize_callback' => 'sanitize_key' ),
					'consistency' => array( 'sanitize_callback' => 'sanitize_key' ),
					'search'      => array( 'sanitize_callback' => 'sanitize_text_field' ),
					'limit'       => array( 'sanitize_callback' => 'absint', 'default' => 100 ),
				),
			)
		);

		register_rest_route(
			'sc-engagement-intake/v1',
			'/workflow-core/cases/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'workflow_core_case' ),
				'permission_callback' => static fn(): bool => current_user_can( 'sc_intake_view_workflow_core' ),
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
				'form_variant' => $request->get_param( 'form_variant' ),
				'source_page'   => $request->get_param( 'source_page' ),
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

		if ( current_user_can( 'sc_intake_manage_review' ) ) {
			$record['review_history'] = SC_EI_Review_Repository::history( absint( $request['id'] ), 500 );
		}
		if ( current_user_can( 'sc_intake_view_communications' ) ) {
			$record['communications'] = SC_EI_Communication_Repository::for_inquiry( absint( $request['id'] ), 500, true );
		}
		if ( current_user_can( 'sc_intake_view_sender_portal' ) ) {
			$record['sender_portal'] = SC_EI_Portal_Repository::export_for_inquiry( absint( $request['id'] ) );
		}
		if ( current_user_can( 'sc_intake_view_workflow' ) ) {
			$record['teams_proposal_workflow'] = SC_EI_Workflow_Repository::export_for_inquiry( absint( $request['id'] ) );
		}
		if ( current_user_can( 'sc_intake_view_engagements' ) ) {
			$record['engagement_handoff'] = SC_EI_Engagement_Repository::export_for_inquiry( absint( $request['id'] ) );
		}
		if ( current_user_can( 'sc_intake_view_workflow_core' ) ) {
			$record['workflow_core'] = SC_EI_Workflow_Core_Repository::export_for_inquiry( absint( $request['id'] ) );
		}
		if ( current_user_can( 'sc_intake_view_fit_assessments' ) ) {
			$record['fit_assessment'] = ! empty( $record['current_fit_assessment_id'] )
				? SC_EI_Fit_Repository::find( absint( $record['current_fit_assessment_id'] ) )
				: null;
		}
		if ( current_user_can( 'sc_intake_view_privacy_center' ) ) {
			$record['privacy'] = array(
				'consent_events'   => SC_EI_Privacy_Repository::consent_events( array( 'inquiry_id' => absint( $request['id'] ), 'limit' => 500 ) ),
				'legal_holds'      => array_values(
					array_filter(
						SC_EI_Privacy_Repository::holds( array( 'search' => (string) $record['reference'], 'limit' => 500 ) ),
						static fn( array $hold ): bool => absint( $hold['inquiry_id'] ) === absint( $request['id'] )
					)
				),
				'retention_actions'=> array_values(
					array_filter(
						SC_EI_Privacy_Repository::retention_actions( array( 'search' => (string) $record['reference'], 'limit' => 500 ) ),
						static fn( array $action ): bool => absint( $action['inquiry_id'] ) === absint( $request['id'] )
					)
				),
			);
		}

		return new WP_REST_Response( $record );
	}

	public static function workflow_core_cases( WP_REST_Request $request ): WP_REST_Response {
		$cases = SC_EI_Workflow_Core_Repository::query_cases(
			array(
				'stage'       => $request->get_param( 'stage' ),
				'state'       => $request->get_param( 'state' ),
				'consistency' => $request->get_param( 'consistency' ),
				'search'      => $request->get_param( 'search' ),
				'limit'       => $request->get_param( 'limit' ),
			)
		);
		return new WP_REST_Response(
			array(
				'schema'      => 'sc-engagement-workflow-core-cases/1.0',
				'generated_at'=> current_time( 'mysql', true ),
				'count'       => count( $cases ),
				'cases'       => $cases,
				'read_only'   => true,
			)
		);
	}

	public static function workflow_core_case( WP_REST_Request $request ) {
		$case = SC_EI_Workflow_Core_Repository::find_case( absint( $request['id'] ) );
		if ( ! $case ) {
			return new WP_Error( 'sc_ei_workflow_core_not_found', __( 'Workflow Core case not found.', 'sustainable-catalyst-engagement-intake' ), array( 'status' => 404 ) );
		}
		return new WP_REST_Response(
			array(
				'schema'      => 'sc-engagement-workflow-core-case/1.0',
				'generated_at'=> current_time( 'mysql', true ),
				'case'        => $case,
				'commands'    => SC_EI_Workflow_Core_Repository::commands( absint( $case['id'] ), 250 ),
				'handoffs'    => array_map(
					static fn( array $handoff ): array => SC_EI_Workflow_Core_Contract::public_metadata( $handoff ),
					SC_EI_Workflow_Core_Repository::handoffs( absint( $case['id'] ), 250 )
				),
				'outbox'      => array_map(
					static function ( array $event ): array {
						unset( $event['payload_json'], $event['claim_token'], $event['error_message'] );
						return $event;
					},
					SC_EI_Workflow_Core_Repository::outbox( absint( $case['id'] ), 250 )
				),
				'read_only' => true,
			)
		);
	}

	public static function submit( WP_REST_Request $request ) {
		$result = SC_EI_Form_Handler::process( $request->get_params(), $request->get_file_params() );

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
				'form_variant'      => $result['form_variant'] ?? 'advanced',
				'conversion_route'  => $result['conversion_route'] ?? '',
				'attachment_count'  => absint( $result['attachment_count'] ?? 0 ),
				'attachments'       => $result['attachments'] ?? array(),
				'attachment_errors' => $result['attachment_errors'] ?? array(),
				'request_id'        => $result['request_id'] ?? '',
				'message'           => __( 'Your private inquiry record has been created.', 'sustainable-catalyst-engagement-intake' ),
			),
			201
		);
	}

	private static function status_for_error( string $code ): int {
		if ( in_array( $code, array( 'rate_limited' ), true ) ) {
			return 429;
		}
		if ( in_array( $code, array( 'submission_in_progress', 'duplicate_submission' ), true ) ) {
			return 409;
		}
		if ( in_array( $code, array( 'request_too_large', 'upload_truncated', 'file_too_large' ), true ) ) {
			return 413;
		}
		if ( in_array( $code, array( 'uploads_disabled', 'upload_temp_unavailable' ), true ) ) {
			return 503;
		}
		if ( in_array( $code, array( 'storage_error', 'storage_unavailable', 'storage_move_failed', 'storage_commit_failed' ), true ) ) {
			return 500;
		}
		return 400;
	}
}
