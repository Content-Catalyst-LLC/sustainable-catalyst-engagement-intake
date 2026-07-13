<?php
/**
 * Notifications and Communication History administration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Communication_Admin {

	public static function register(): void {
		add_action( 'admin_post_sc_ei_save_communication_draft', array( __CLASS__, 'handle_save_draft' ) );
		add_action( 'admin_post_sc_ei_send_communication', array( __CLASS__, 'handle_send' ) );
		add_action( 'admin_post_sc_ei_cancel_communication', array( __CLASS__, 'handle_cancel' ) );
		add_action( 'admin_post_sc_ei_record_interaction', array( __CLASS__, 'handle_record_interaction' ) );
		add_action( 'admin_post_sc_ei_update_communication_thread', array( __CLASS__, 'handle_update_thread' ) );
		add_action( 'admin_post_sc_ei_save_communication_template', array( __CLASS__, 'handle_save_template' ) );
		add_action( 'admin_post_sc_ei_test_notification_transport', array( __CLASS__, 'handle_test_notification' ) );
		add_action( 'admin_post_sc_ei_export_communication_history', array( __CLASS__, 'handle_export' ) );
		add_filter( 'set-screen-option', array( __CLASS__, 'screen_option' ), 30, 3 );
	}

	public static function submenu(): void {
		$hook = add_submenu_page(
			'sc-engagement-intake',
			__( 'Notifications and Communication History', 'sustainable-catalyst-engagement-intake' ),
			__( 'Communications', 'sustainable-catalyst-engagement-intake' ),
			'sc_intake_view_communications',
			'sc-engagement-intake-communications',
			array( __CLASS__, 'page' )
		);

		add_action(
			"load-{$hook}",
			static function(): void {
				if ( empty( $_GET['inquiry'] ) && ! in_array( sanitize_key( (string) ( $_GET['view'] ?? '' ) ), array( 'templates', 'policy' ), true ) ) {
					add_screen_option(
						'per_page',
						array(
							'label'   => __( 'Communications per page', 'sustainable-catalyst-engagement-intake' ),
							'default' => 25,
							'option'  => 'sc_ei_communications_per_page',
						)
					);
				}
			}
		);
	}

	public static function screen_option( $status, string $option, $value ) {
		if ( 'sc_ei_communications_per_page' === $option ) {
			return max( 1, min( 100, absint( $value ) ) );
		}
		return $status;
	}

	public static function page(): void {
		if ( ! current_user_can( 'sc_intake_view_communications' ) ) {
			wp_die( esc_html__( 'You do not have permission to view private communication history.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		$inquiry_id = isset( $_GET['inquiry'] ) ? absint( $_GET['inquiry'] ) : 0;
		if ( $inquiry_id ) {
			self::thread_page( $inquiry_id );
			return;
		}

		$view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'history';
		if ( ! in_array( $view, array( 'history', 'drafts', 'failed', 'inbound', 'follow_up', 'notifications', 'templates', 'policy' ), true ) ) {
			$view = 'history';
		}

		$metrics = SC_EI_Communication_Repository::metrics();
		$reviewers = SC_EI_Review_Schema::reviewers();
		$templates = SC_EI_Template_Repository::active_templates();
		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() );
		$list_table = null;
		if ( ! in_array( $view, array( 'templates', 'policy' ), true ) ) {
			$list_table = new SC_EI_Communication_List_Table( $view );
			$list_table->prepare_items();
		}

		include SC_EI_DIR . 'admin/views/communications.php';
	}

	public static function thread_url( int $inquiry_id, array $args = array() ): string {
		return add_query_arg(
			array_merge(
				array(
					'page'    => 'sc-engagement-intake-communications',
					'inquiry' => $inquiry_id,
				),
				$args
			),
			admin_url( 'admin.php' )
		);
	}

	public static function handle_save_draft(): void {
		if ( ! current_user_can( 'sc_intake_compose_communications' ) ) {
			wp_die( esc_html__( 'You do not have permission to compose private communications.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		$inquiry_id = isset( $_POST['inquiry_id'] ) ? absint( $_POST['inquiry_id'] ) : 0;
		check_admin_referer( 'sc_ei_save_communication_draft_' . $inquiry_id );

		$result = SC_EI_Communication_Repository::save_draft(
			$inquiry_id,
			array(
				'direction'              => isset( $_POST['direction'] ) ? sanitize_key( wp_unslash( $_POST['direction'] ) ) : 'outbound',
				'channel'                => 'email',
				'communication_type'     => isset( $_POST['communication_type'] ) ? sanitize_key( wp_unslash( $_POST['communication_type'] ) ) : 'general_response',
				'subject'                => isset( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '',
				'body_text'              => isset( $_POST['body_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['body_text'] ) ) : '',
				'recipient_name'         => isset( $_POST['recipient_name'] ) ? sanitize_text_field( wp_unslash( $_POST['recipient_name'] ) ) : '',
				'recipient_email'        => isset( $_POST['recipient_email'] ) ? sanitize_email( wp_unslash( $_POST['recipient_email'] ) ) : '',
				'cc'                     => isset( $_POST['cc'] ) ? sanitize_text_field( wp_unslash( $_POST['cc'] ) ) : '',
				'template_key'           => isset( $_POST['template_key'] ) ? sanitize_key( wp_unslash( $_POST['template_key'] ) ) : '',
				'template_version'       => isset( $_POST['template_version'] ) ? absint( $_POST['template_version'] ) : 0,
				'reply_to_id'            => isset( $_POST['reply_to_id'] ) ? absint( $_POST['reply_to_id'] ) : 0,
				'privacy_classification' => isset( $_POST['privacy_classification'] ) ? sanitize_key( wp_unslash( $_POST['privacy_classification'] ) ) : 'private',
			),
			get_current_user_id(),
			isset( $_POST['communication_id'] ) ? absint( $_POST['communication_id'] ) : 0,
			isset( $_POST['row_version'] ) ? absint( $_POST['row_version'] ) : 0
		);

		if ( is_wp_error( $result ) ) {
			self::redirect_thread( $inquiry_id, $result->get_error_code(), isset( $_POST['communication_id'] ) ? absint( $_POST['communication_id'] ) : 0 );
		}
		self::redirect_thread( $inquiry_id, 'communication_draft_saved', absint( $result['id'] ) );
	}

	public static function handle_send(): void {
		if ( ! current_user_can( 'sc_intake_send_communications' ) ) {
			wp_die( esc_html__( 'You do not have permission to send private communications.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		$id = isset( $_POST['communication_id'] ) ? absint( $_POST['communication_id'] ) : 0;
		check_admin_referer( 'sc_ei_send_communication_' . $id );
		$communication = SC_EI_Communication_Repository::find( $id );
		if ( ! $communication ) {
			wp_die( esc_html__( 'Communication not found.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 404 ) );
		}
		if ( empty( $_POST['confirm_send'] ) ) {
			self::redirect_thread( absint( $communication['inquiry_id'] ), 'send_confirmation_required', $id );
		}

		$result = SC_EI_Mailer::send( $id, get_current_user_id() );
		self::redirect_thread(
			absint( $communication['inquiry_id'] ),
			is_wp_error( $result ) ? $result->get_error_code() : 'communication_mail_accepted',
			$id
		);
	}

	public static function handle_cancel(): void {
		if ( ! current_user_can( 'sc_intake_compose_communications' ) ) {
			wp_die( esc_html__( 'You do not have permission to cancel communication drafts.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		$id = isset( $_POST['communication_id'] ) ? absint( $_POST['communication_id'] ) : 0;
		check_admin_referer( 'sc_ei_cancel_communication_' . $id );
		$communication = SC_EI_Communication_Repository::find( $id );
		$success = $communication && SC_EI_Communication_Repository::cancel_draft( $id, get_current_user_id() );
		self::redirect_thread( absint( $communication['inquiry_id'] ?? 0 ), $success ? 'communication_canceled' : 'communication_cancel_failed' );
	}

	public static function handle_record_interaction(): void {
		if ( ! current_user_can( 'sc_intake_record_inbound' ) ) {
			wp_die( esc_html__( 'You do not have permission to record communication interactions.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		$inquiry_id = isset( $_POST['inquiry_id'] ) ? absint( $_POST['inquiry_id'] ) : 0;
		check_admin_referer( 'sc_ei_record_interaction_' . $inquiry_id );

		$result = SC_EI_Communication_Repository::record_interaction(
			$inquiry_id,
			array(
				'direction'              => isset( $_POST['interaction_direction'] ) ? sanitize_key( wp_unslash( $_POST['interaction_direction'] ) ) : 'inbound',
				'channel'                => isset( $_POST['interaction_channel'] ) ? sanitize_key( wp_unslash( $_POST['interaction_channel'] ) ) : 'email',
				'communication_type'     => isset( $_POST['interaction_type'] ) ? sanitize_key( wp_unslash( $_POST['interaction_type'] ) ) : 'manual_interaction',
				'subject'                => isset( $_POST['interaction_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['interaction_subject'] ) ) : '',
				'body_text'              => isset( $_POST['interaction_body'] ) ? sanitize_textarea_field( wp_unslash( $_POST['interaction_body'] ) ) : '',
				'party_name'             => isset( $_POST['interaction_party_name'] ) ? sanitize_text_field( wp_unslash( $_POST['interaction_party_name'] ) ) : '',
				'party_email'            => isset( $_POST['interaction_party_email'] ) ? sanitize_email( wp_unslash( $_POST['interaction_party_email'] ) ) : '',
				'occurred_at_local'      => isset( $_POST['interaction_occurred_at'] ) ? sanitize_text_field( wp_unslash( $_POST['interaction_occurred_at'] ) ) : '',
				'needs_response'         => empty( $_POST['interaction_needs_response'] ) ? 0 : 1,
				'privacy_classification' => isset( $_POST['interaction_privacy'] ) ? sanitize_key( wp_unslash( $_POST['interaction_privacy'] ) ) : 'private',
			),
			get_current_user_id()
		);

		self::redirect_thread( $inquiry_id, is_wp_error( $result ) ? $result->get_error_code() : 'communication_interaction_recorded' );
	}

	public static function handle_update_thread(): void {
		if ( ! current_user_can( 'sc_intake_communicate' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage communication follow-up state.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		$inquiry_id = isset( $_POST['inquiry_id'] ) ? absint( $_POST['inquiry_id'] ) : 0;
		check_admin_referer( 'sc_ei_update_communication_thread_' . $inquiry_id );

		$success = SC_EI_Communication_Repository::update_thread_state(
			$inquiry_id,
			array(
				'communication_status' => isset( $_POST['communication_status'] ) ? sanitize_key( wp_unslash( $_POST['communication_status'] ) ) : 'open',
				'next_follow_up_local'  => isset( $_POST['next_follow_up_local'] ) ? sanitize_text_field( wp_unslash( $_POST['next_follow_up_local'] ) ) : '',
				'do_not_email'          => empty( $_POST['do_not_email'] ) ? 0 : 1,
				'do_not_email_reason'   => isset( $_POST['do_not_email_reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['do_not_email_reason'] ) ) : '',
				'mark_inbound_read'     => empty( $_POST['mark_inbound_read'] ) ? 0 : 1,
			),
			get_current_user_id()
		);
		self::redirect_thread( $inquiry_id, $success ? 'communication_thread_updated' : 'communication_thread_update_failed' );
	}

	public static function handle_save_template(): void {
		if ( ! current_user_can( 'sc_intake_manage_templates' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage communication templates.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'sc_ei_save_communication_template' );
		$result = SC_EI_Template_Repository::create_version(
			isset( $_POST['template_key'] ) ? sanitize_key( wp_unslash( $_POST['template_key'] ) ) : '',
			isset( $_POST['template_name'] ) ? sanitize_text_field( wp_unslash( $_POST['template_name'] ) ) : '',
			isset( $_POST['template_type'] ) ? sanitize_key( wp_unslash( $_POST['template_type'] ) ) : 'general_response',
			isset( $_POST['subject_template'] ) ? sanitize_text_field( wp_unslash( $_POST['subject_template'] ) ) : '',
			isset( $_POST['body_template'] ) ? sanitize_textarea_field( wp_unslash( $_POST['body_template'] ) ) : '',
			get_current_user_id(),
			! empty( $_POST['template_is_system'] )
		);

		self::redirect_overview(
			'templates',
			is_wp_error( $result ) ? $result->get_error_code() : 'communication_template_saved'
		);
	}

	public static function handle_test_notification(): void {
		if ( ! current_user_can( 'sc_intake_manage_notifications' ) ) {
			wp_die( esc_html__( 'You do not have permission to test notification delivery.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'sc_ei_test_notification_transport' );
		$recipient = isset( $_POST['test_recipient'] ) ? sanitize_email( wp_unslash( $_POST['test_recipient'] ) ) : '';
		$result = SC_EI_Notification_Service::test_notification( $recipient, get_current_user_id() );
		self::redirect_overview(
			'policy',
			is_wp_error( $result ) ? $result->get_error_code() : 'notification_test_accepted'
		);
	}

	public static function handle_export(): void {
		if ( ! current_user_can( 'sc_intake_export_communications' ) ) {
			wp_die( esc_html__( 'You do not have permission to export communication history.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		$inquiry_id = isset( $_GET['inquiry'] ) ? absint( $_GET['inquiry'] ) : 0;
		check_admin_referer( 'sc_ei_export_communication_history_' . $inquiry_id );
		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $inquiry ) {
			wp_die( esc_html__( 'Inquiry not found.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 404 ) );
		}

		$rows = SC_EI_Communication_Repository::for_inquiry( $inquiry_id, 1000, true );
		SC_EI_Audit_Log::record(
			'communication_history_exported',
			'Authorized user exported private communication history.',
			array( 'row_count' => count( $rows ) ),
			$inquiry_id,
			null,
			get_current_user_id()
		);

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="engagement-intake-communications-' . sanitize_file_name( $inquiry['reference'] ) . '-' . gmdate( 'Y-m-d-His' ) . '.csv"' );
		header( 'X-Content-Type-Options: nosniff' );
		$output = fopen( 'php://output', 'wb' );
		if ( false === $output ) {
			wp_die( esc_html__( 'The export stream could not be opened.', 'sustainable-catalyst-engagement-intake' ) );
		}

		fputcsv(
			$output,
			array(
				'id', 'occurred_or_created_utc', 'direction', 'channel', 'type', 'status',
				'subject', 'body_text', 'sender_name', 'sender_email', 'recipient_name',
				'recipient_email', 'cc_json', 'template_key', 'template_version', 'is_automated',
				'provider', 'attempt_count', 'accepted_at_utc', 'failed_at_utc', 'error_code',
				'privacy_classification', 'message_hash',
			),
			',',
			'"',
			''
		);
		foreach ( $rows as $row ) {
			$values = array(
				$row['id'],
				$row['occurred_at'] ?: $row['accepted_at'] ?: $row['created_at'],
				$row['direction'],
				$row['channel'],
				$row['communication_type'],
				$row['status'],
				$row['subject'],
				$row['body_text'],
				$row['sender_name'],
				$row['sender_email'],
				$row['recipient_name'],
				$row['recipient_email'],
				$row['cc_json'],
				$row['template_key'],
				$row['template_version'],
				$row['is_automated'],
				$row['provider'],
				$row['attempt_count'],
				$row['accepted_at'],
				$row['failed_at'],
				$row['error_code'],
				$row['privacy_classification'],
				$row['message_hash'],
			);
			fputcsv(
				$output,
				array_map( array( __CLASS__, 'csv_cell' ), $values ),
				',',
				'"',
				''
			);
		}
		fclose( $output );
		exit;
	}

	private static function thread_page( int $inquiry_id ): void {
		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $inquiry ) {
			wp_die( esc_html__( 'Inquiry not found.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 404 ) );
		}

		$communications = SC_EI_Communication_Repository::for_inquiry( $inquiry_id, 500, true );
		$draft_id = isset( $_GET['draft'] ) ? absint( $_GET['draft'] ) : 0;
		$draft = $draft_id ? SC_EI_Communication_Repository::find( $draft_id ) : null;
		if ( $draft && absint( $draft['inquiry_id'] ) !== $inquiry_id ) {
			$draft = null;
		}

		$templates = SC_EI_Template_Repository::active_templates();
		$template_payload = array();
		foreach ( $templates as $key => $template ) {
			$rendered = SC_EI_Template_Repository::render( $template, $inquiry, get_current_user_id() );
			$template_payload[ $key ] = array(
				'key'     => $key,
				'version' => absint( $template['version'] ),
				'type'    => $template['communication_type'],
				'name'    => $template['name'],
				'subject' => $rendered['subject'],
				'body'    => $rendered['body'],
				'system'  => absint( $template['is_system'] ),
			);
		}

		$follow_up_local = self::utc_to_local_input( $inquiry['next_follow_up_at'] );
		$now_local = ( new DateTimeImmutable( 'now', wp_timezone() ) )->format( 'Y-m-d\TH:i' );
		$events = array();
		foreach ( $communications as $communication ) {
			$events[ $communication['id'] ] = SC_EI_Communication_Repository::events( absint( $communication['id'] ), 100 );
		}
		include SC_EI_DIR . 'admin/views/communication-thread.php';
	}

	private static function redirect_thread( int $inquiry_id, string $message, int $draft_id = 0 ): void {
		$args = array( 'sc_ei_msg' => sanitize_key( $message ) );
		if ( $draft_id ) {
			$args['draft'] = $draft_id;
		}
		wp_safe_redirect( self::thread_url( $inquiry_id, $args ), 303 );
		exit;
	}

	private static function redirect_overview( string $view, string $message ): void {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'      => 'sc-engagement-intake-communications',
					'view'      => sanitize_key( $view ),
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
			return ( new DateTimeImmutable( (string) $utc, new DateTimeZone( 'UTC' ) ) )
				->setTimezone( wp_timezone() )
				->format( 'Y-m-d\TH:i' );
		} catch ( Throwable $exception ) {
			return '';
		}
	}

	private static function csv_cell( $value ): string {
		$value = is_scalar( $value ) || null === $value ? (string) $value : wp_json_encode( $value );
		$value = str_replace( "\0", '', $value );
		return preg_match( '/^[=+\-@]/', $value ) ? "'" . $value : $value;
	}
}
