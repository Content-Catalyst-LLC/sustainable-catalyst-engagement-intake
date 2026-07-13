<?php
/**
 * Human-controlled fit assessment administration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Fit_Admin {

	public static function register(): void {
		add_action( 'admin_post_sc_ei_create_fit_assessment', array( __CLASS__, 'handle_create' ) );
		add_action( 'admin_post_sc_ei_save_fit_assessment', array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_post_sc_ei_submit_fit_assessment', array( __CLASS__, 'handle_submit' ) );
		add_action( 'admin_post_sc_ei_second_review_fit_assessment', array( __CLASS__, 'handle_second_review' ) );
		add_action( 'admin_post_sc_ei_finalize_fit_assessment', array( __CLASS__, 'handle_finalize' ) );
		add_action( 'admin_post_sc_ei_apply_fit_assessment', array( __CLASS__, 'handle_apply' ) );
		add_action( 'admin_post_sc_ei_export_fit_assessment', array( __CLASS__, 'handle_export' ) );
		add_action( 'admin_post_sc_ei_save_fit_settings', array( __CLASS__, 'handle_settings' ) );
	}

	public static function submenu(): void {
		add_submenu_page(
			'sc-engagement-intake',
			__( 'Human-Controlled Fit Assessment', 'sustainable-catalyst-engagement-intake' ),
			__( 'Fit Assessment', 'sustainable-catalyst-engagement-intake' ),
			'sc_intake_view_fit_assessments',
			'sc-engagement-intake-fit',
			array( __CLASS__, 'page' )
		);
	}

	public static function page(): void {
		if ( ! current_user_can( 'sc_intake_view_fit_assessments' ) ) {
			wp_die( esc_html__( 'You do not have permission to view fit assessments.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		$assessment_id = isset( $_GET['assessment'] ) ? absint( $_GET['assessment'] ) : 0;
		if ( $assessment_id ) {
			self::detail_page( $assessment_id );
			return;
		}

		$status = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$mine = ! empty( $_GET['mine'] ) ? get_current_user_id() : 0;
		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() );
		$metrics = SC_EI_Fit_Repository::metrics();
		$assessments = SC_EI_Fit_Repository::queue(
			array(
				'status'   => $status,
				'assessor' => $mine,
				'search'   => $search,
				'limit'    => absint( $settings['fit_assessment_queue_limit'] ?? 100 ),
			)
		);
		include SC_EI_DIR . 'admin/views/fit-assessment.php';
	}

	private static function detail_page( int $assessment_id ): void {
		$assessment = SC_EI_Fit_Repository::find( $assessment_id );
		if ( ! $assessment ) {
			wp_die( esc_html__( 'The fit assessment could not be found.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 404 ) );
		}
		$inquiry = SC_EI_Inquiry_Repository::find( absint( $assessment['inquiry_id'] ) );
		if ( ! $inquiry ) {
			wp_die( esc_html__( 'The linked inquiry could not be found.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 404 ) );
		}
		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() );
		$criteria = SC_EI_Fit_Schema::criteria();
		$groups = SC_EI_Fit_Schema::criterion_groups();
		$assessor = ! empty( $assessment['assessor_user_id'] ) ? get_userdata( absint( $assessment['assessor_user_id'] ) ) : null;
		$second_reviewer = ! empty( $assessment['second_reviewer_user_id'] ) ? get_userdata( absint( $assessment['second_reviewer_user_id'] ) ) : null;
		$message = isset( $_GET['sc_ei_msg'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_msg'] ) ) : '';
		include SC_EI_DIR . 'admin/views/fit-assessment-detail.php';
	}

	public static function url( int $assessment_id = 0, array $args = array() ): string {
		$query = array_merge( array( 'page' => 'sc-engagement-intake-fit' ), $args );
		if ( $assessment_id ) {
			$query['assessment'] = $assessment_id;
		}
		return add_query_arg( $query, admin_url( 'admin.php' ) );
	}

	public static function handle_create(): void {
		self::require_cap( 'sc_intake_create_fit_assessments' );
		$inquiry_id = absint( $_POST['inquiry_id'] ?? 0 );
		check_admin_referer( 'sc_ei_create_fit_assessment' );
		$result = SC_EI_Fit_Repository::create_draft( $inquiry_id, get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			self::redirect_queue( $result->get_error_code() );
		}
		wp_safe_redirect( self::url( absint( $result['id'] ), array( 'sc_ei_msg' => 'fit_assessment_created' ) ), 303 );
		exit;
	}

	public static function handle_save(): void {
		self::require_cap( 'sc_intake_create_fit_assessments' );
		$assessment_id = absint( $_POST['assessment_id'] ?? 0 );
		check_admin_referer( 'sc_ei_save_fit_assessment_' . $assessment_id );
		$result = SC_EI_Fit_Repository::save_draft(
			$assessment_id,
			self::assessment_input(),
			get_current_user_id(),
			absint( $_POST['row_version'] ?? 0 )
		);
		self::redirect_detail( $assessment_id, is_wp_error( $result ) ? $result->get_error_code() : 'fit_assessment_saved' );
	}

	public static function handle_submit(): void {
		self::require_cap( 'sc_intake_create_fit_assessments' );
		$assessment_id = absint( $_POST['assessment_id'] ?? 0 );
		check_admin_referer( 'sc_ei_submit_fit_assessment_' . $assessment_id );
		$result = SC_EI_Fit_Repository::submit(
			$assessment_id,
			get_current_user_id(),
			absint( $_POST['row_version'] ?? 0 )
		);
		self::redirect_detail( $assessment_id, is_wp_error( $result ) ? $result->get_error_code() : 'fit_assessment_submitted' );
	}

	public static function handle_second_review(): void {
		self::require_cap( 'sc_intake_review_fit_assessments' );
		$assessment_id = absint( $_POST['assessment_id'] ?? 0 );
		check_admin_referer( 'sc_ei_second_review_fit_assessment_' . $assessment_id );
		$result = SC_EI_Fit_Repository::record_second_review(
			$assessment_id,
			array(
				'disposition'        => isset( $_POST['disposition'] ) ? sanitize_key( wp_unslash( $_POST['disposition'] ) ) : 'agree',
				'recommendation'     => isset( $_POST['second_recommendation'] ) ? sanitize_key( wp_unslash( $_POST['second_recommendation'] ) ) : 'undecided',
				'service_route'      => isset( $_POST['second_service_route'] ) ? sanitize_key( wp_unslash( $_POST['second_service_route'] ) ) : 'continue_review',
				'scope_boundary'     => isset( $_POST['second_scope_boundary'] ) ? sanitize_key( wp_unslash( $_POST['second_scope_boundary'] ) ) : 'within_scope',
				'review_notes'       => isset( $_POST['second_review_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['second_review_notes'] ) ) : '',
				'required_changes'   => isset( $_POST['second_required_changes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['second_required_changes'] ) ) : '',
				'conflict_disclosure'=> isset( $_POST['second_conflict_disclosure'] ) ? sanitize_textarea_field( wp_unslash( $_POST['second_conflict_disclosure'] ) ) : '',
				'human_attestation'  => empty( $_POST['second_human_attestation'] ) ? 0 : 1,
			),
			get_current_user_id()
		);
		self::redirect_detail( $assessment_id, is_wp_error( $result ) ? $result->get_error_code() : 'fit_second_review_saved' );
	}

	public static function handle_finalize(): void {
		self::require_cap( 'sc_intake_finalize_fit_assessments' );
		$assessment_id = absint( $_POST['assessment_id'] ?? 0 );
		check_admin_referer( 'sc_ei_finalize_fit_assessment_' . $assessment_id );
		$provided = strtoupper( trim( (string) ( $_POST['confirm_finalize'] ?? '' ) ) );
		$expected = 'FINALIZE ' . $assessment_id;
		if ( $provided !== $expected ) {
			self::redirect_detail( $assessment_id, 'fit_finalize_confirmation_failed' );
		}
		$result = SC_EI_Fit_Repository::finalize(
			$assessment_id,
			get_current_user_id(),
			absint( $_POST['row_version'] ?? 0 )
		);
		self::redirect_detail( $assessment_id, is_wp_error( $result ) ? $result->get_error_code() : 'fit_assessment_finalized' );
	}

	public static function handle_apply(): void {
		self::require_cap( 'sc_intake_apply_fit_to_review' );
		$assessment_id = absint( $_POST['assessment_id'] ?? 0 );
		check_admin_referer( 'sc_ei_apply_fit_assessment_' . $assessment_id );
		$provided = strtoupper( trim( (string) ( $_POST['confirm_apply'] ?? '' ) ) );
		$expected = 'APPLY ' . $assessment_id;
		if ( $provided !== $expected ) {
			self::redirect_detail( $assessment_id, 'fit_apply_confirmation_failed' );
		}
		$result = SC_EI_Fit_Repository::apply_to_review( $assessment_id, get_current_user_id() );
		self::redirect_detail( $assessment_id, is_wp_error( $result ) ? $result->get_error_code() : 'fit_assessment_applied' );
	}

	public static function handle_settings(): void {
		self::require_cap( 'sc_intake_manage_fit_settings' );
		check_admin_referer( 'sc_ei_save_fit_settings' );

		$current = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() );
		$raw = isset( $_POST['fit_settings'] ) ? (array) wp_unslash( $_POST['fit_settings'] ) : array();
		$fit = array(
			'fit_assessment_enabled'                  => 1,
			'fit_advisory_score_enabled'              => empty( $raw['fit_advisory_score_enabled'] ) ? 0 : 1,
			'fit_require_human_attestation'           => 1,
			'fit_require_evidence_for_assessed_items' => empty( $raw['fit_require_evidence_for_assessed_items'] ) ? 0 : 1,
			'fit_require_rationale_for_finalization'  => empty( $raw['fit_require_rationale_for_finalization'] ) ? 0 : 1,
			'fit_require_second_review_high_risk'     => empty( $raw['fit_require_second_review_high_risk'] ) ? 0 : 1,
			'fit_require_second_review_conflict'      => empty( $raw['fit_require_second_review_conflict'] ) ? 0 : 1,
			'fit_require_second_review_decline'       => empty( $raw['fit_require_second_review_decline'] ) ? 0 : 1,
			'fit_require_second_review_unsafe_scope'  => empty( $raw['fit_require_second_review_unsafe_scope'] ) ? 0 : 1,
			'fit_distinct_second_reviewer'            => empty( $raw['fit_distinct_second_reviewer'] ) ? 0 : 1,
			'fit_assessment_stale_days'               => max( 1, min( 365, absint( $raw['fit_assessment_stale_days'] ?? $current['fit_assessment_stale_days'] ) ) ),
			'fit_assessment_queue_limit'              => max( 10, min( 500, absint( $raw['fit_assessment_queue_limit'] ?? $current['fit_assessment_queue_limit'] ) ) ),
		);
		update_option( 'sc_ei_settings', array_merge( $current, $fit ), false );
		SC_EI_Audit_Log::record(
			'fit_settings_updated',
			'Human-controlled fit assessment settings updated.',
			array(
				'advisory_score_enabled' => $fit['fit_advisory_score_enabled'],
				'evidence_required'      => $fit['fit_require_evidence_for_assessed_items'],
				'rationale_required'     => $fit['fit_require_rationale_for_finalization'],
				'distinct_reviewer'      => $fit['fit_distinct_second_reviewer'],
				'stale_days'             => $fit['fit_assessment_stale_days'],
				'queue_limit'            => $fit['fit_assessment_queue_limit'],
				'automatic_decision'     => false,
			),
			null,
			null,
			get_current_user_id()
		);
		self::redirect_queue( 'fit_settings_saved' );
	}

	public static function handle_export(): void {
		self::require_cap( 'sc_intake_export_fit_assessments' );
		$assessment_id = absint( $_GET['assessment'] ?? 0 );
		check_admin_referer( 'sc_ei_export_fit_assessment_' . $assessment_id );
		$packet = SC_EI_Fit_Repository::packet( $assessment_id );
		if ( ! $packet ) {
			wp_die( esc_html__( 'The fit assessment export could not be generated.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 404 ) );
		}
		SC_EI_Audit_Log::record(
			'fit_assessment_exported',
			'Authorized user exported a private fit assessment packet.',
			array( 'assessment_id' => $assessment_id, 'schema' => $packet['schema'] ),
			absint( $packet['assessment']['inquiry_id'] ),
			null,
			get_current_user_id()
		);
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="fit-assessment-' . absint( $assessment_id ) . '-' . gmdate( 'Y-m-d-His' ) . '.json"' );
		header( 'X-Content-Type-Options: nosniff' );
		echo wp_json_encode( $packet, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		exit;
	}

	private static function assessment_input(): array {
		$items = array();
		$raw_items = isset( $_POST['fit_items'] ) ? (array) wp_unslash( $_POST['fit_items'] ) : array();
		foreach ( SC_EI_Fit_Schema::criteria() as $key => $criterion ) {
			$raw = is_array( $raw_items[ $key ] ?? null ) ? $raw_items[ $key ] : array();
			$items[ $key ] = array(
				'rating'              => isset( $raw['rating'] ) ? sanitize_key( $raw['rating'] ) : 'not_assessed',
				'evidence_note'       => isset( $raw['evidence_note'] ) ? sanitize_textarea_field( $raw['evidence_note'] ) : '',
				'concern_note'        => isset( $raw['concern_note'] ) ? sanitize_textarea_field( $raw['concern_note'] ) : '',
				'source_refs'         => isset( $raw['source_refs'] ) ? sanitize_textarea_field( $raw['source_refs'] ) : '',
				'is_material_concern' => empty( $raw['is_material_concern'] ) ? 0 : 1,
			);
		}
		return array(
			'recommendation'           => isset( $_POST['recommendation'] ) ? sanitize_key( wp_unslash( $_POST['recommendation'] ) ) : 'undecided',
			'confidence'               => isset( $_POST['confidence'] ) ? sanitize_key( wp_unslash( $_POST['confidence'] ) ) : 'unassessed',
			'service_route'            => isset( $_POST['service_route'] ) ? sanitize_key( wp_unslash( $_POST['service_route'] ) ) : 'continue_review',
			'scope_boundary'           => isset( $_POST['scope_boundary'] ) ? sanitize_key( wp_unslash( $_POST['scope_boundary'] ) ) : 'within_scope',
			'overall_summary'          => isset( $_POST['overall_summary'] ) ? sanitize_textarea_field( wp_unslash( $_POST['overall_summary'] ) ) : '',
			'recommendation_rationale' => isset( $_POST['recommendation_rationale'] ) ? sanitize_textarea_field( wp_unslash( $_POST['recommendation_rationale'] ) ) : '',
			'limitations_notes'        => isset( $_POST['limitations_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['limitations_notes'] ) ) : '',
			'conditions_for_fit'       => isset( $_POST['conditions_for_fit'] ) ? sanitize_textarea_field( wp_unslash( $_POST['conditions_for_fit'] ) ) : '',
			'referral_notes'           => isset( $_POST['referral_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['referral_notes'] ) ) : '',
			'human_attestation'        => empty( $_POST['human_attestation'] ) ? 0 : 1,
			'assistance_disclosure'    => isset( $_POST['assistance_disclosure'] ) ? sanitize_key( wp_unslash( $_POST['assistance_disclosure'] ) ) : 'none',
			'assistance_notes'         => isset( $_POST['assistance_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['assistance_notes'] ) ) : '',
			'items'                    => $items,
		);
	}

	private static function require_cap( string $capability ): void {
		if ( ! current_user_can( $capability ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this fit-assessment operation.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}
	}

	private static function redirect_detail( int $assessment_id, string $message ): void {
		wp_safe_redirect( self::url( $assessment_id, array( 'sc_ei_msg' => sanitize_key( $message ) ) ), 303 );
		exit;
	}

	private static function redirect_queue( string $message ): void {
		wp_safe_redirect( self::url( 0, array( 'sc_ei_msg' => sanitize_key( $message ) ) ), 303 );
		exit;
	}
}
