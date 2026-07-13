<?php
/**
 * Administrative Review Workspace controller.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Review_Admin {

	public static function register(): void {
		add_action( 'admin_post_sc_ei_save_review', array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_post_sc_ei_claim_review', array( __CLASS__, 'handle_claim' ) );
		add_action( 'admin_post_sc_ei_unassign_review', array( __CLASS__, 'handle_unassign' ) );
		add_action( 'admin_post_sc_ei_bulk_review', array( __CLASS__, 'handle_bulk' ) );
		add_action( 'admin_post_sc_ei_export_review_packet', array( __CLASS__, 'handle_export' ) );
		add_filter( 'set-screen-option', array( __CLASS__, 'screen_option' ), 20, 3 );
	}

	public static function submenu(): void {
		$hook = add_submenu_page(
			'sc-engagement-intake',
			__( 'Administrative Review Workspace', 'sustainable-catalyst-engagement-intake' ),
			__( 'Review Workspace', 'sustainable-catalyst-engagement-intake' ),
			'sc_intake_manage_review',
			'sc-engagement-intake-review',
			array( __CLASS__, 'page' )
		);

		add_action(
			"load-{$hook}",
			static function(): void {
				if ( empty( $_GET['inquiry'] ) ) {
					add_screen_option(
						'per_page',
						array(
							'label'   => __( 'Review inquiries per page', 'sustainable-catalyst-engagement-intake' ),
							'default' => 20,
							'option'  => 'sc_ei_reviews_per_page',
						)
					);
				}
			}
		);
	}

	public static function screen_option( $status, string $option, $value ) {
		if ( 'sc_ei_reviews_per_page' === $option ) {
			return max( 1, min( 100, absint( $value ) ) );
		}
		return $status;
	}

	public static function page(): void {
		if ( ! current_user_can( 'sc_intake_manage_review' ) ) {
			wp_die( esc_html__( 'You do not have permission to use the administrative review workspace.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		$inquiry_id = isset( $_GET['inquiry'] ) ? absint( $_GET['inquiry'] ) : 0;
		if ( $inquiry_id ) {
			self::detail_page( $inquiry_id );
			return;
		}

		$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'queue';
		if ( ! in_array( $view, array( 'queue', 'mine', 'unassigned', 'escalations', 'completed', 'method' ), true ) ) {
			$view = 'queue';
		}

		$metrics   = SC_EI_Review_Repository::metrics( get_current_user_id() );
		$reviewers = SC_EI_Review_Schema::reviewers();
		$list_table= null;

		if ( 'method' !== $view ) {
			$list_table = new SC_EI_Review_List_Table( $view );
			$list_table->prepare_items();
		}

		include SC_EI_DIR . 'admin/views/review-workspace.php';
	}

	public static function detail_url( int $inquiry_id, array $args = array() ): string {
		return add_query_arg(
			array_merge(
				array(
					'page'    => 'sc-engagement-intake-review',
					'inquiry' => $inquiry_id,
				),
				$args
			),
			admin_url( 'admin.php' )
		);
	}

	public static function handle_save(): void {
		if ( ! current_user_can( 'sc_intake_manage_review' ) ) {
			wp_die( esc_html__( 'You do not have permission to save administrative reviews.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		$inquiry_id = isset( $_POST['inquiry_id'] ) ? absint( $_POST['inquiry_id'] ) : 0;
		check_admin_referer( 'sc_ei_save_review_' . $inquiry_id );

		$current = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $current ) {
			self::redirect_detail( $inquiry_id, 'review_not_found' );
		}

		$settings = wp_parse_args(
			get_option( 'sc_ei_settings', array() ),
			array_merge( SC_EI_Admin::default_settings(), SC_EI_Review_Schema::default_review_settings() )
		);

		if (
			! empty( $settings['restrict_review_to_assignee'] )
			&& ! current_user_can( 'sc_intake_assign_inquiries' )
			&& ! empty( $current['assigned_user_id'] )
			&& absint( $current['assigned_user_id'] ) !== get_current_user_id()
		) {
			wp_die( esc_html__( 'This review is assigned to another reviewer.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		$assigned_user_id = absint( $current['assigned_user_id'] ?? 0 );
		if ( current_user_can( 'sc_intake_assign_inquiries' ) ) {
			$assigned_user_id = isset( $_POST['assigned_user_id'] ) ? absint( $_POST['assigned_user_id'] ) : 0;
		} elseif (
			! $assigned_user_id
			&& ! empty( $settings['reviewer_self_assignment'] )
			&& ! empty( $_POST['claim_on_save'] )
		) {
			$assigned_user_id = get_current_user_id();
		}

		$input = array(
			'assigned_user_id'        => $assigned_user_id,
			'review_stage'            => isset( $_POST['review_stage'] ) ? sanitize_key( wp_unslash( $_POST['review_stage'] ) ) : '',
			'review_priority'         => isset( $_POST['review_priority'] ) ? sanitize_key( wp_unslash( $_POST['review_priority'] ) ) : '',
			'review_due_local'        => isset( $_POST['review_due_local'] ) ? sanitize_text_field( wp_unslash( $_POST['review_due_local'] ) ) : '',
			'fit_decision'            => isset( $_POST['fit_decision'] ) ? sanitize_key( wp_unslash( $_POST['fit_decision'] ) ) : '',
			'fit_confidence'          => isset( $_POST['fit_confidence'] ) ? sanitize_key( wp_unslash( $_POST['fit_confidence'] ) ) : '',
			'risk_level'              => isset( $_POST['risk_level'] ) ? sanitize_key( wp_unslash( $_POST['risk_level'] ) ) : '',
			'evidence_readiness'      => isset( $_POST['evidence_readiness'] ) ? sanitize_key( wp_unslash( $_POST['evidence_readiness'] ) ) : '',
			'scope_clarity'           => isset( $_POST['scope_clarity'] ) ? sanitize_key( wp_unslash( $_POST['scope_clarity'] ) ) : '',
			'recommended_next_step'   => isset( $_POST['recommended_next_step'] ) ? sanitize_key( wp_unslash( $_POST['recommended_next_step'] ) ) : '',
			'review_summary'          => isset( $_POST['review_summary'] ) ? sanitize_textarea_field( wp_unslash( $_POST['review_summary'] ) ) : '',
			'decision_rationale'      => isset( $_POST['decision_rationale'] ) ? sanitize_textarea_field( wp_unslash( $_POST['decision_rationale'] ) ) : '',
			'information_gaps'        => isset( $_POST['information_gaps'] ) ? sanitize_textarea_field( wp_unslash( $_POST['information_gaps'] ) ) : '',
			'conflict_notes'          => isset( $_POST['conflict_notes'] ) ? sanitize_textarea_field( wp_unslash( $_POST['conflict_notes'] ) ) : '',
			'review_checklist'        => isset( $_POST['review_checklist'] ) ? (array) wp_unslash( $_POST['review_checklist'] ) : array(),
			'escalation_status'       => isset( $_POST['escalation_status'] ) ? sanitize_key( wp_unslash( $_POST['escalation_status'] ) ) : '',
			'escalation_reason'       => isset( $_POST['escalation_reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['escalation_reason'] ) ) : '',
			'inquiry_status'          => isset( $_POST['inquiry_status'] ) ? sanitize_key( wp_unslash( $_POST['inquiry_status'] ) ) : '',
			'event_type'              => 'review_saved',
			'event_note'              => isset( $_POST['review_event_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['review_event_note'] ) ) : '',
		);

		if ( ! current_user_can( 'sc_intake_manage_review_priority' ) ) {
			$input['review_priority']  = $current['review_priority'];
			$input['review_due_local'] = self::utc_to_local_input( $current['review_due_at'] );
		}
		if ( ! current_user_can( 'sc_intake_escalate_review' ) ) {
			$input['escalation_status'] = $current['escalation_status'];
			$input['escalation_reason'] = $current['escalation_reason'];
		}
		if ( ! current_user_can( 'sc_intake_change_status' ) ) {
			$input['inquiry_status'] = $current['status'];
		}

		$result = SC_EI_Review_Repository::save_review(
			$inquiry_id,
			$input,
			get_current_user_id(),
			isset( $_POST['review_version'] ) ? absint( $_POST['review_version'] ) : 0
		);

		if ( is_wp_error( $result ) ) {
			self::redirect_detail( $inquiry_id, $result->get_error_code() );
		}

		self::redirect_detail( $inquiry_id, 'review_saved' );
	}

	public static function handle_claim(): void {
		if ( ! current_user_can( 'sc_intake_manage_review' ) ) {
			wp_die( esc_html__( 'You do not have permission to claim review work.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		$inquiry_id = isset( $_POST['inquiry_id'] ) ? absint( $_POST['inquiry_id'] ) : 0;
		check_admin_referer( 'sc_ei_claim_review_' . $inquiry_id );

		$settings = wp_parse_args(
			get_option( 'sc_ei_settings', array() ),
			array_merge( SC_EI_Admin::default_settings(), SC_EI_Review_Schema::default_review_settings() )
		);
		if ( empty( $settings['reviewer_self_assignment'] ) && ! current_user_can( 'sc_intake_assign_inquiries' ) ) {
			wp_die( esc_html__( 'Reviewer self-assignment is disabled.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		$current = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $current ) {
			self::redirect_detail( $inquiry_id, 'review_not_found' );
		}
		if ( ! empty( $current['assigned_user_id'] ) && absint( $current['assigned_user_id'] ) !== get_current_user_id() ) {
			self::redirect_detail( $inquiry_id, 'review_already_assigned' );
		}

		$result = SC_EI_Review_Repository::assign(
			$inquiry_id,
			get_current_user_id(),
			get_current_user_id(),
			absint( $current['review_version'] ),
			'Reviewer claimed the inquiry from the Administrative Review Workspace.'
		);

		self::redirect_detail( $inquiry_id, is_wp_error( $result ) ? $result->get_error_code() : 'review_claimed' );
	}

	public static function handle_unassign(): void {
		if ( ! current_user_can( 'sc_intake_assign_inquiries' ) ) {
			wp_die( esc_html__( 'You do not have permission to remove review assignments.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		$inquiry_id = isset( $_POST['inquiry_id'] ) ? absint( $_POST['inquiry_id'] ) : 0;
		check_admin_referer( 'sc_ei_unassign_review_' . $inquiry_id );

		$current = SC_EI_Inquiry_Repository::find( $inquiry_id );
		$result  = $current
			? SC_EI_Review_Repository::assign(
				$inquiry_id,
				0,
				get_current_user_id(),
				absint( $current['review_version'] ),
				'Review assignment removed by an authorized manager.'
			)
			: new WP_Error( 'review_not_found', __( 'The inquiry could not be found.', 'sustainable-catalyst-engagement-intake' ) );

		self::redirect_detail( $inquiry_id, is_wp_error( $result ) ? $result->get_error_code() : 'review_unassigned' );
	}

	public static function handle_bulk(): void {
		if ( ! current_user_can( 'sc_intake_bulk_review_actions' ) ) {
			wp_die( esc_html__( 'You do not have permission to run bulk review operations.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'sc_ei_bulk_review' );

		$settings = wp_parse_args(
			get_option( 'sc_ei_settings', array() ),
			array_merge( SC_EI_Admin::default_settings(), SC_EI_Review_Schema::default_review_settings() )
		);
		$limit = max( 1, min( 50, absint( $settings['review_bulk_limit'] ?? 50 ) ) );
		$ids = isset( $_POST['inquiry_ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['inquiry_ids'] ) ) : array();
		$ids = array_slice( array_values( array_unique( array_filter( $ids ) ) ), 0, $limit );
		$operation = isset( $_POST['bulk_review_operation'] ) ? sanitize_key( wp_unslash( $_POST['bulk_review_operation'] ) ) : '';
		$allowed = array( 'assign', 'unassign', 'priority', 'stage', 'due', 'escalate', 'resolve_escalation' );

		if ( ! $ids || ! in_array( $operation, $allowed, true ) ) {
			self::redirect_workspace( 'bulk_review_error' );
		}

		$result = array(
			'operation' => $operation,
			'selected'  => count( $ids ),
			'processed' => 0,
			'succeeded' => 0,
			'failed'    => 0,
			'details'   => array(),
		);

		foreach ( $ids as $inquiry_id ) {
			$current = SC_EI_Inquiry_Repository::find( $inquiry_id );
			if ( ! $current ) {
				$result['failed']++;
				continue;
			}

			$input = array(
				'event_type' => 'bulk_review_action',
				'event_note' => isset( $_POST['bulk_review_reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['bulk_review_reason'] ) ) : '',
			);

			if ( 'assign' === $operation ) {
				$input['assigned_user_id'] = isset( $_POST['bulk_assigned_user_id'] ) ? absint( $_POST['bulk_assigned_user_id'] ) : 0;
				if ( ! $input['assigned_user_id'] ) {
					$result['failed']++;
					continue;
				}
			} elseif ( 'unassign' === $operation ) {
				$input['assigned_user_id'] = 0;
			} elseif ( 'priority' === $operation ) {
				$input['review_priority'] = isset( $_POST['bulk_review_priority'] ) ? sanitize_key( wp_unslash( $_POST['bulk_review_priority'] ) ) : '';
			} elseif ( 'stage' === $operation ) {
				$input['review_stage'] = isset( $_POST['bulk_review_stage'] ) ? sanitize_key( wp_unslash( $_POST['bulk_review_stage'] ) ) : '';
			} elseif ( 'due' === $operation ) {
				$input['review_due_local'] = isset( $_POST['bulk_review_due_local'] ) ? sanitize_text_field( wp_unslash( $_POST['bulk_review_due_local'] ) ) : '';
			} elseif ( 'escalate' === $operation ) {
				$input['escalation_status'] = 'requested';
				$input['escalation_reason'] = trim( (string) $input['event_note'] );
				$input['recommended_next_step'] = 'internal_escalation';
				if ( '' === $input['escalation_reason'] ) {
					$result['failed']++;
					continue;
				}
			} elseif ( 'resolve_escalation' === $operation ) {
				$input['escalation_status'] = 'resolved';
				$input['escalation_reason'] = $current['escalation_reason'];
			}

			$saved = SC_EI_Review_Repository::save_review(
				$inquiry_id,
				$input,
				get_current_user_id(),
				absint( $current['review_version'] )
			);
			$result['processed']++;
			if ( is_wp_error( $saved ) ) {
				$result['failed']++;
				$message = $saved->get_error_message();
			} else {
				$result['succeeded']++;
				$message = 'Saved';
			}

			if ( count( $result['details'] ) < 50 ) {
				$result['details'][] = array(
					'inquiry_id' => $inquiry_id,
					'reference'  => $current['reference'],
					'success'    => ! is_wp_error( $saved ),
					'message'    => sanitize_text_field( $message ),
				);
			}
		}

		SC_EI_Audit_Log::record(
			'bulk_review_action_completed',
			'Guarded bulk administrative review operation completed.',
			$result,
			null,
			null,
			get_current_user_id()
		);

		set_transient( 'sc_ei_bulk_review_result_' . get_current_user_id(), $result, 5 * MINUTE_IN_SECONDS );
		self::redirect_workspace( 'bulk_review_completed' );
	}

	public static function handle_export(): void {
		if ( ! current_user_can( 'sc_intake_export_review_packet' ) ) {
			wp_die( esc_html__( 'You do not have permission to export private review packets.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		$inquiry_id = isset( $_GET['inquiry'] ) ? absint( $_GET['inquiry'] ) : 0;
		check_admin_referer( 'sc_ei_export_review_packet_' . $inquiry_id );

		$packet = SC_EI_Review_Repository::packet( $inquiry_id );
		if ( ! $packet ) {
			wp_die( esc_html__( 'The review packet could not be generated.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 404 ) );
		}

		SC_EI_Audit_Log::record(
			'review_packet_exported',
			'Authorized user exported a private administrative review packet.',
			array(
				'review_count'     => count( $packet['reviews'] ),
				'attachment_count' => count( $packet['attachments'] ),
				'audit_count'      => count( $packet['audit'] ),
			),
			$inquiry_id,
			null,
			get_current_user_id()
		);

		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="engagement-intake-review-' . sanitize_file_name( $packet['inquiry']['reference'] ) . '-' . gmdate( 'Y-m-d-His' ) . '.json"' );
		header( 'X-Content-Type-Options: nosniff' );

		echo wp_json_encode( $packet, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
		exit;
	}

	private static function detail_page( int $inquiry_id ): void {
		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $inquiry ) {
			wp_die( esc_html__( 'Inquiry not found.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 404 ) );
		}

		$attachments = SC_EI_Attachment_Repository::for_inquiry( $inquiry_id, true );
		$history     = SC_EI_Review_Repository::history( $inquiry_id, 100 );
		$audit_log   = SC_EI_Audit_Log::for_inquiry( $inquiry_id, 40 );
		$reviewers   = SC_EI_Review_Schema::reviewers();
		$timing      = SC_EI_Review_Schema::timing( $inquiry );
		$checklist   = SC_EI_Review_Schema::checklist_progress( $inquiry['review_checklist'] );
		$settings    = wp_parse_args(
			get_option( 'sc_ei_settings', array() ),
			array_merge( SC_EI_Admin::default_settings(), SC_EI_Review_Schema::default_review_settings() )
		);
		$assigned_user = ! empty( $inquiry['assigned_user_id'] ) ? get_userdata( absint( $inquiry['assigned_user_id'] ) ) : false;
		$can_edit_review = current_user_can( 'sc_intake_assign_inquiries' )
			|| empty( $settings['restrict_review_to_assignee'] )
			|| empty( $inquiry['assigned_user_id'] )
			|| absint( $inquiry['assigned_user_id'] ) === get_current_user_id();
		$document_summary = array(
			'total'     => 0,
			'attention' => 0,
			'approved'  => 0,
			'clean'     => 0,
		);
		foreach ( $attachments as $attachment ) {
			if ( ! empty( $attachment['deleted_at'] ) ) {
				continue;
			}
			$document_summary['total']++;
			if ( 'approved' === $attachment['quarantine_status'] ) {
				$document_summary['approved']++;
			}
			if ( 'clean' === $attachment['scan_status'] ) {
				$document_summary['clean']++;
			}
			if (
				'infected' === $attachment['scan_status']
				|| 'healthy' !== $attachment['storage_status']
				|| 'validated' !== $attachment['validation_status']
			) {
				$document_summary['attention']++;
			}
		}

		include SC_EI_DIR . 'admin/views/review-detail.php';
	}

	private static function redirect_detail( int $inquiry_id, string $message ): void {
		wp_safe_redirect(
			self::detail_url(
				$inquiry_id,
				array( 'sc_ei_msg' => sanitize_key( $message ) )
			),
			303
		);
		exit;
	}

	private static function redirect_workspace( string $message ): void {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'      => 'sc-engagement-intake-review',
					'sc_ei_msg' => sanitize_key( $message ),
				),
				admin_url( 'admin.php' )
			),
			303
		);
		exit;
	}

	private static function utc_to_local_input( $utc ): string {
		if ( ! $utc ) {
			return '';
		}
		try {
			$date = new DateTimeImmutable( (string) $utc, new DateTimeZone( 'UTC' ) );
			return $date->setTimezone( wp_timezone() )->format( 'Y-m-d\TH:i' );
		} catch ( Throwable $exception ) {
			return '';
		}
	}
}
