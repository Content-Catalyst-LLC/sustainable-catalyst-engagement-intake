<?php
/**
 * WordPress administration.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Admin {

	public static function register(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_init', array( __CLASS__, 'settings' ) );
		add_action( 'admin_post_sc_ei_update_status', array( __CLASS__, 'handle_status' ) );
		add_action( 'admin_post_sc_ei_add_note', array( __CLASS__, 'handle_note' ) );
		add_action( 'admin_post_sc_ei_update_scheduling', array( __CLASS__, 'handle_scheduling' ) );
		add_action( 'admin_post_sc_ei_download_attachment', array( __CLASS__, 'handle_attachment_download' ) );
		add_action( 'admin_post_sc_ei_update_attachment_status', array( __CLASS__, 'handle_attachment_status' ) );
		add_action( 'admin_post_sc_ei_update_attachment_retention', array( __CLASS__, 'handle_attachment_retention' ) );
		add_action( 'admin_post_sc_ei_delete_attachment', array( __CLASS__, 'handle_attachment_delete' ) );
		add_action( 'admin_post_sc_ei_verify_attachment_integrity', array( __CLASS__, 'handle_attachment_integrity' ) );
		add_action( 'admin_post_sc_ei_run_storage_probe', array( __CLASS__, 'handle_storage_probe' ) );
		add_action( 'admin_post_sc_ei_repair_storage', array( __CLASS__, 'handle_storage_repair' ) );
		add_action( 'admin_post_sc_ei_run_storage_reconciliation', array( __CLASS__, 'handle_storage_reconciliation' ) );
		add_action( 'admin_post_sc_ei_preview_retention_cleanup', array( __CLASS__, 'handle_retention_preview' ) );
		add_action( 'admin_post_sc_ei_run_retention_cleanup', array( __CLASS__, 'handle_retention_cleanup' ) );
		add_filter( 'set-screen-option', array( __CLASS__, 'screen_option' ), 10, 3 );
		add_filter( 'plugin_action_links_' . SC_EI_BASENAME, array( __CLASS__, 'plugin_links' ) );
	}

	public static function menu(): void {
		$hook = add_menu_page(
			__( 'Engagement Intake', 'sustainable-catalyst-engagement-intake' ),
			__( 'Engagement Intake', 'sustainable-catalyst-engagement-intake' ),
			'sc_intake_view',
			'sc-engagement-intake',
			array( __CLASS__, 'inquiries_page' ),
			'dashicons-id-alt',
			27
		);

		add_action(
			"load-{$hook}",
			static function(): void {
				add_screen_option(
					'per_page',
					array(
						'label'   => __( 'Inquiries per page', 'sustainable-catalyst-engagement-intake' ),
						'default' => 20,
						'option'  => 'sc_ei_inquiries_per_page',
					)
				);
			}
		);

		add_submenu_page(
			'sc-engagement-intake',
			__( 'Inquiries', 'sustainable-catalyst-engagement-intake' ),
			__( 'Inquiries', 'sustainable-catalyst-engagement-intake' ),
			'sc_intake_view',
			'sc-engagement-intake',
			array( __CLASS__, 'inquiries_page' )
		);

		add_submenu_page(
			'sc-engagement-intake',
			__( 'Diagnostics', 'sustainable-catalyst-engagement-intake' ),
			__( 'Diagnostics', 'sustainable-catalyst-engagement-intake' ),
			'sc_intake_manage_settings',
			'sc-engagement-intake-diagnostics',
			array( __CLASS__, 'diagnostics_page' )
		);

		add_submenu_page(
			'sc-engagement-intake',
			__( 'Settings', 'sustainable-catalyst-engagement-intake' ),
			__( 'Settings', 'sustainable-catalyst-engagement-intake' ),
			'sc_intake_manage_settings',
			'sc-engagement-intake-settings',
			array( __CLASS__, 'settings_page' )
		);
	}

	public static function assets( string $hook ): void {
		if ( false === strpos( $hook, 'sc-engagement-intake' ) ) {
			return;
		}

		wp_enqueue_style(
			'sc-ei-admin',
			SC_EI_URL . 'assets/css/admin.css',
			array(),
			SC_EI_VERSION
		);
	}

	public static function settings(): void {
		register_setting(
			'sc_ei_settings_group',
			'sc_ei_settings',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => self::default_settings(),
			)
		);
	}

	public static function default_settings(): array {
		return array(
			'delete_data_on_uninstall'         => 0,
			'default_unaccepted_retention_days'=> 365,
			'withdrawn_retention_days'         => 30,
			'abandoned_draft_days'              => 30,
			'minimum_completion_seconds'        => 3,
			'submissions_per_hour'              => 5,
			'teams_organizer_email'              => '',
			'default_teams_duration'              => 20,
			'upload_max_files'                    => 5,
			'upload_max_file_mb'                  => 20,
			'allowed_upload_extensions'           => array( 'pdf', 'docx', 'xlsx', 'csv', 'txt', 'png', 'jpg', 'jpeg' ),
			'attachment_retention_days'           => 180,
			'require_external_scanner'            => 0,
			'private_storage_path'                => '',
		);
	}

	public static function sanitize_settings( $value ): array {
		$value = is_array( $value ) ? $value : array();

		return array(
			'delete_data_on_uninstall'          => empty( $value['delete_data_on_uninstall'] ) ? 0 : 1,
			'default_unaccepted_retention_days' => max( 30, min( 3650, absint( $value['default_unaccepted_retention_days'] ?? 365 ) ) ),
			'withdrawn_retention_days'          => max( 1, min( 365, absint( $value['withdrawn_retention_days'] ?? 30 ) ) ),
			'abandoned_draft_days'              => max( 1, min( 365, absint( $value['abandoned_draft_days'] ?? 30 ) ) ),
			'minimum_completion_seconds'        => max( 1, min( 30, absint( $value['minimum_completion_seconds'] ?? 3 ) ) ),
			'submissions_per_hour'              => max( 1, min( 20, absint( $value['submissions_per_hour'] ?? 5 ) ) ),
			'teams_organizer_email'              => sanitize_email( $value['teams_organizer_email'] ?? '' ),
			'default_teams_duration'             => in_array( absint( $value['default_teams_duration'] ?? 20 ), array( 20, 30, 45, 60, 90 ), true ) ? absint( $value['default_teams_duration'] ?? 20 ) : 20,
			'upload_max_files'                   => max( 1, min( 10, absint( $value['upload_max_files'] ?? 5 ) ) ),
			'upload_max_file_mb'                 => max( 1, min( 100, absint( $value['upload_max_file_mb'] ?? 20 ) ) ),
			'allowed_upload_extensions'          => self::sanitize_upload_extensions( $value['allowed_upload_extensions'] ?? array() ),
			'attachment_retention_days'          => max( 7, min( 3650, absint( $value['attachment_retention_days'] ?? 180 ) ) ),
			'require_external_scanner'           => empty( $value['require_external_scanner'] ) ? 0 : 1,
			'private_storage_path'               => self::sanitize_private_storage_path( (string) ( $value['private_storage_path'] ?? '' ) ),
		);
	}

	private static function sanitize_upload_extensions( $extensions ): array {
		$supported = array_keys( SC_EI_Upload_Validator::supported_extensions() );
		$clean     = array_values( array_intersect( $supported, array_map( 'sanitize_key', (array) $extensions ) ) );

		return $clean ?: $supported;
	}

	private static function sanitize_private_storage_path( string $path ): string {
		$locked = get_option( 'sc_ei_storage_base_dir', '' );
		if ( is_string( $locked ) && '' !== trim( $locked ) ) {
			return untrailingslashit( wp_normalize_path( $locked ) );
		}

		$path = trim( wp_normalize_path( $path ) );
		if ( '' === $path ) {
			return '';
		}

		if ( str_contains( $path, "\0" ) || str_contains( $path, '../' ) || str_contains( $path, '/..' ) ) {
			return '';
		}

		$is_unix_absolute    = str_starts_with( $path, '/' );
		$is_windows_absolute = (bool) preg_match( '/^[A-Za-z]:\//', $path );

		return ( $is_unix_absolute || $is_windows_absolute ) ? untrailingslashit( $path ) : '';
	}

	public static function screen_option( $status, string $option, $value ) {
		if ( 'sc_ei_inquiries_per_page' === $option ) {
			return max( 1, min( 100, absint( $value ) ) );
		}
		return $status;
	}

	public static function plugin_links( array $links ): array {
		array_unshift(
			$links,
			sprintf(
				'<a href="%s">%s</a>',
				esc_url( admin_url( 'admin.php?page=sc-engagement-intake' ) ),
				esc_html__( 'Inquiries', 'sustainable-catalyst-engagement-intake' )
			)
		);
		return $links;
	}

	public static function inquiries_page(): void {
		if ( ! current_user_can( 'sc_intake_view' ) ) {
			wp_die( esc_html__( 'You do not have permission to view private inquiries.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$action = isset( $_GET['action'] ) ? sanitize_key( wp_unslash( $_GET['action'] ) ) : '';
		$id     = isset( $_GET['inquiry'] ) ? absint( $_GET['inquiry'] ) : 0;

		if ( 'view' === $action && $id ) {
			self::render_inquiry( $id );
			return;
		}

		$list_table = new SC_EI_Admin_List_Table();
		$list_table->prepare_items();
		include SC_EI_DIR . 'admin/views/inquiries-list.php';
	}

	private static function render_inquiry( int $id ): void {
		$inquiry = SC_EI_Inquiry_Repository::find( $id );
		if ( ! $inquiry ) {
			wp_die( esc_html__( 'Inquiry not found.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$attachments = SC_EI_Attachment_Repository::for_inquiry( $id );
		$audit_log   = SC_EI_Audit_Log::for_inquiry( $id );
		include SC_EI_DIR . 'admin/views/inquiry-view.php';
	}

	public static function diagnostics_page(): void {
		if ( ! current_user_can( 'sc_intake_manage_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to view diagnostics.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$diagnostics = SC_EI_Diagnostics::run();
		$status      = SC_EI_Diagnostics::overall_status( $diagnostics );
		include SC_EI_DIR . 'admin/views/diagnostics.php';
	}

	public static function settings_page(): void {
		if ( ! current_user_can( 'sc_intake_manage_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage settings.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), self::default_settings() );
		include SC_EI_DIR . 'admin/views/settings.php';
	}

	public static function handle_status(): void {
		if ( ! current_user_can( 'sc_intake_change_status' ) ) {
			wp_die( esc_html__( 'You do not have permission to change inquiry status.', 'sustainable-catalyst-engagement-intake' ) );
		}

		check_admin_referer( 'sc_ei_update_status' );

		$id     = isset( $_POST['inquiry_id'] ) ? absint( $_POST['inquiry_id'] ) : 0;
		$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		$note   = isset( $_POST['status_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['status_note'] ) ) : '';

		$success = $id && SC_EI_Inquiry_Repository::update_status( $id, $status, $note );

		$url = add_query_arg(
			array(
				'page'      => 'sc-engagement-intake',
				'action'    => 'view',
				'inquiry'   => $id,
				'sc_ei_msg' => $success ? 'status_updated' : 'error',
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}


	public static function handle_scheduling(): void {
		if ( ! current_user_can( 'sc_intake_review' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage Microsoft Teams scheduling.', 'sustainable-catalyst-engagement-intake' ) );
		}

		check_admin_referer( 'sc_ei_update_scheduling' );

		$id = isset( $_POST['inquiry_id'] ) ? absint( $_POST['inquiry_id'] ) : 0;
		$input = array(
			'scheduling_status'      => isset( $_POST['scheduling_status'] ) ? sanitize_key( wp_unslash( $_POST['scheduling_status'] ) ) : '',
			'teams_meeting_url'      => isset( $_POST['teams_meeting_url'] ) ? esc_url_raw( wp_unslash( $_POST['teams_meeting_url'] ) ) : '',
			'scheduled_start_local'  => isset( $_POST['scheduled_start_local'] ) ? sanitize_text_field( wp_unslash( $_POST['scheduled_start_local'] ) ) : '',
			'scheduled_end_local'    => isset( $_POST['scheduled_end_local'] ) ? sanitize_text_field( wp_unslash( $_POST['scheduled_end_local'] ) ) : '',
			'scheduled_timezone'     => isset( $_POST['scheduled_timezone'] ) ? sanitize_text_field( wp_unslash( $_POST['scheduled_timezone'] ) ) : '',
			'calendar_event_id'      => isset( $_POST['calendar_event_id'] ) ? sanitize_text_field( wp_unslash( $_POST['calendar_event_id'] ) ) : '',
			'scheduling_admin_note'  => isset( $_POST['scheduling_admin_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['scheduling_admin_note'] ) ) : '',
		);

		$success = $id && SC_EI_Inquiry_Repository::update_scheduling( $id, $input );

		$url = add_query_arg(
			array(
				'page'      => 'sc-engagement-intake',
				'action'    => 'view',
				'inquiry'   => $id,
				'sc_ei_msg' => $success ? 'scheduling_updated' : 'scheduling_error',
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	public static function handle_attachment_download(): void {
		if ( ! current_user_can( 'sc_intake_download_files' ) ) {
			wp_die( esc_html__( 'You do not have permission to download private attachments.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		$id = isset( $_GET['attachment'] ) ? absint( $_GET['attachment'] ) : 0;
		check_admin_referer( 'sc_ei_download_attachment_' . $id );

		$attachment = SC_EI_Attachment_Repository::find( $id );
		if ( ! $attachment || ! empty( $attachment['deleted_at'] ) || in_array( $attachment['quarantine_status'], array( 'deleted', 'rejected' ), true ) ) {
			wp_die( esc_html__( 'The private attachment is unavailable.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 404 ) );
		}

		if ( 'infected' === $attachment['scan_status'] ) {
			wp_die( esc_html__( 'The attachment was identified as infected and cannot be downloaded.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		$verification = SC_EI_Attachment_Repository::verify_record( $attachment, get_current_user_id(), 'download' );
		if ( empty( $verification['ok'] ) ) {
			SC_EI_Audit_Log::record(
				'attachment_integrity_mismatch',
				'Download blocked because the stored file did not match its SHA-256 fingerprint.',
				array(
					'sha256'        => $attachment['sha256'],
					'storage_status'=> $verification['status'] ?? 'attention',
				),
				(int) $attachment['inquiry_id'],
				$id
			);
			wp_die( esc_html__( 'The attachment failed its integrity check and cannot be downloaded.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 409 ) );
		}

		SC_EI_Attachment_Repository::record_download( $id, get_current_user_id(), 'verified' );
		SC_EI_Storage::stream_download( $attachment );
	}

	public static function handle_attachment_integrity(): void {
		if ( ! current_user_can( 'sc_intake_download_files' ) ) {
			wp_die( esc_html__( 'You do not have permission to verify private attachments.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		$id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
		check_admin_referer( 'sc_ei_verify_attachment_integrity_' . $id );

		$attachment = SC_EI_Attachment_Repository::find( $id );
		$result     = $attachment
			? SC_EI_Attachment_Repository::verify_record( $attachment, get_current_user_id(), 'manual' )
			: array( 'ok' => false );

		self::redirect_to_inquiry(
			(int) ( $attachment['inquiry_id'] ?? 0 ),
			! empty( $result['ok'] ) ? 'attachment_verified' : 'attachment_verification_failed'
		);
	}

	public static function handle_storage_probe(): void {
		self::require_diagnostics_capability();
		check_admin_referer( 'sc_ei_run_storage_probe' );

		$probe = SC_EI_Storage::probe();
		self::redirect_to_diagnostics( ! empty( $probe['ok'] ) ? 'storage_probe_passed' : 'storage_probe_failed' );
	}

	public static function handle_storage_repair(): void {
		self::require_diagnostics_capability();
		check_admin_referer( 'sc_ei_repair_storage' );

		$result = SC_EI_Storage::repair();
		SC_EI_Audit_Log::record(
			'storage_repair_completed',
			'Protected storage repair and probe completed.',
			array(
				'ok'                    => ! empty( $result['ok'] ),
				'stale_staging_deleted' => absint( $result['stale_staging_deleted'] ?? 0 ),
			),
			null,
			null,
			get_current_user_id()
		);

		self::redirect_to_diagnostics( ! empty( $result['ok'] ) ? 'storage_repaired' : 'storage_repair_failed' );
	}

	public static function handle_storage_reconciliation(): void {
		self::require_diagnostics_capability();
		check_admin_referer( 'sc_ei_run_storage_reconciliation' );

		$report = SC_EI_Storage_Reconciler::run( 1000, 5000 );
		$issues = array_sum(
			array_intersect_key(
				(array) ( $report['counts'] ?? array() ),
				array_flip( array( 'missing_files', 'hash_mismatches', 'size_mismatches', 'misplaced_files', 'unresolvable_paths', 'orphan_files' ) )
			)
		);

		self::redirect_to_diagnostics( $issues > 0 ? 'reconciliation_attention' : 'reconciliation_clean' );
	}

	public static function handle_retention_preview(): void {
		if ( ! current_user_can( 'sc_intake_manage_file_retention' ) ) {
			wp_die( esc_html__( 'You do not have permission to preview attachment cleanup.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'sc_ei_preview_retention_cleanup' );
		update_option( 'sc_ei_last_retention_preview', SC_EI_Retention::preview( 250 ), false );
		self::redirect_to_diagnostics( 'retention_preview_ready' );
	}

	public static function handle_retention_cleanup(): void {
		if ( ! current_user_can( 'sc_intake_delete' ) ) {
			wp_die( esc_html__( 'You do not have permission to delete expired private attachments.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( 'sc_ei_run_retention_cleanup' );
		$confirmation = isset( $_POST['cleanup_confirmation'] ) ? sanitize_text_field( wp_unslash( $_POST['cleanup_confirmation'] ) ) : '';

		if ( 'DELETE EXPIRED' !== $confirmation ) {
			self::redirect_to_diagnostics( 'retention_confirmation_failed' );
		}

		$deleted = SC_EI_Retention::cleanup( 250 );
		SC_EI_Audit_Log::record(
			'manual_retention_cleanup_completed',
			'Authorized administrator ran the expired-attachment cleanup manually.',
			array( 'deleted_count' => $deleted ),
			null,
			null,
			get_current_user_id()
		);

		self::redirect_to_diagnostics( 'retention_cleanup_completed' );
	}

	private static function require_diagnostics_capability(): void {
		if ( ! current_user_can( 'sc_intake_manage_settings' ) ) {
			wp_die( esc_html__( 'You do not have permission to run intake diagnostics.', 'sustainable-catalyst-engagement-intake' ), '', array( 'response' => 403 ) );
		}
	}

	private static function redirect_to_diagnostics( string $message ): void {
		$url = add_query_arg(
			array(
				'page'      => 'sc-engagement-intake-diagnostics',
				'sc_ei_msg' => sanitize_key( $message ),
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url, 303 );
		exit;
	}

	private static function transition_attachment_storage_status(
		array $attachment,
		string $target_status,
		string $note
	): bool {
		$id           = absint( $attachment['id'] ?? 0 );
		$old_relative = (string) ( $attachment['relative_path'] ?? '' );
		$old_status   = sanitize_key( (string) ( $attachment['quarantine_status'] ?? 'quarantined' ) );
		$target_area  = 'approved' === $target_status ? 'approved/' : 'quarantine/';
		$current_area = str_starts_with( $old_relative, 'approved/' ) ? 'approved/' : 'quarantine/';
		$new_relative = $old_relative;
		$moved        = false;

		if ( $target_area !== $current_area ) {
			$new_relative = 'approved/' === $target_area
				? SC_EI_Storage::move_to_approved( $old_relative )
				: SC_EI_Storage::move_to_quarantine( $old_relative );

			if ( ! $new_relative ) {
				return false;
			}
			$moved = true;
		}

		$new_absolute = SC_EI_Storage::absolute_path( $new_relative );
		$size_ok      = $new_absolute
			&& is_file( $new_absolute )
			&& (int) filesize( $new_absolute ) === absint( $attachment['size_bytes'] ?? 0 );
		$hash_ok      = SC_EI_Storage::verify_integrity( $new_relative, (string) ( $attachment['sha256'] ?? '' ) );

		if ( ! $size_ok || ! $hash_ok ) {
			if ( $moved ) {
				'approved/' === $target_area
					? SC_EI_Storage::move_to_quarantine( $new_relative )
					: SC_EI_Storage::move_to_approved( $new_relative );
			}
			return false;
		}

		if ( $moved && ! SC_EI_Attachment_Repository::update_relative_path( $id, $new_relative ) ) {
			'approved/' === $target_area
				? SC_EI_Storage::move_to_quarantine( $new_relative )
				: SC_EI_Storage::move_to_approved( $new_relative );
			return false;
		}

		$updated = SC_EI_Attachment_Repository::update_quarantine_status(
			$id,
			$target_status,
			get_current_user_id(),
			$note
		);

		if ( ! $updated ) {
			if ( $moved ) {
				$rolled_back = 'approved/' === $target_area
					? SC_EI_Storage::move_to_quarantine( $new_relative )
					: SC_EI_Storage::move_to_approved( $new_relative );

				if ( $rolled_back ) {
					SC_EI_Attachment_Repository::update_relative_path( $id, $old_relative );
				}
			}
			return false;
		}

		$fresh = SC_EI_Attachment_Repository::find( $id );
		if ( $fresh ) {
			SC_EI_Attachment_Repository::verify_record( $fresh, get_current_user_id(), 'manual' );
		}

		return true;
	}

	public static function handle_attachment_status(): void {
		if ( ! current_user_can( 'sc_intake_release_files' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage private attachment quarantine status.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
		check_admin_referer( 'sc_ei_update_attachment_status_' . $id );

		$status = isset( $_POST['attachment_status'] ) ? sanitize_key( wp_unslash( $_POST['attachment_status'] ) ) : '';
		$note   = isset( $_POST['attachment_status_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['attachment_status_note'] ) ) : '';

		$attachment = SC_EI_Attachment_Repository::find( $id );
		$success    = false;

		if ( $attachment && empty( $attachment['deleted_at'] ) ) {
			if ( 'approved' === $status ) {
				$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), self::default_settings() );
				$scan_ok  = 'infected' !== $attachment['scan_status'];
				if ( ! empty( $settings['require_external_scanner'] ) ) {
					$scan_ok = 'clean' === $attachment['scan_status'];
				}

				$verification = SC_EI_Attachment_Repository::verify_record( $attachment, get_current_user_id(), 'manual' );
				if ( 'validated' === $attachment['validation_status'] && $scan_ok && ! empty( $verification['ok'] ) ) {
					$success = self::transition_attachment_storage_status(
						$attachment,
						'approved',
						$note ?: 'Private attachment approved for controlled use.'
					);
				}
			} elseif ( 'replacement_requested' === $status ) {
				$success = self::transition_attachment_storage_status(
					$attachment,
					'replacement_requested',
					$note ?: 'A replacement document was requested.'
				);
			} elseif ( 'quarantined' === $status ) {
				$success = self::transition_attachment_storage_status(
					$attachment,
					'quarantined',
					$note ?: 'Attachment returned to quarantine review.'
				);
			} elseif ( 'rejected' === $status ) {
				$deleted = SC_EI_Storage::delete_file( (string) $attachment['relative_path'] );
				if ( $deleted ) {
					$success = SC_EI_Attachment_Repository::mark_deleted(
						$id,
						get_current_user_id(),
						$note ?: 'Private attachment rejected and removed from protected storage.',
						'rejected'
					);
				}
			}
		}

		self::redirect_to_inquiry(
			(int) ( $attachment['inquiry_id'] ?? 0 ),
			$success ? 'attachment_updated' : 'attachment_error'
		);
	}

	public static function handle_attachment_retention(): void {
		if ( ! current_user_can( 'sc_intake_manage_file_retention' ) ) {
			wp_die( esc_html__( 'You do not have permission to change attachment retention.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
		check_admin_referer( 'sc_ei_update_attachment_retention_' . $id );

		$attachment = SC_EI_Attachment_Repository::find( $id );
		$date_value = isset( $_POST['retention_date'] ) ? sanitize_text_field( wp_unslash( $_POST['retention_date'] ) ) : '';
		$retention  = null;

		if ( $date_value ) {
			try {
				$local     = new DateTimeImmutable( $date_value . ' 23:59:59', wp_timezone() );
				$retention = $local->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
			} catch ( Throwable $exception ) {
				$retention = null;
			}
		}

		$success = $attachment && SC_EI_Attachment_Repository::update_retention( $id, $retention, get_current_user_id() );
		self::redirect_to_inquiry(
			(int) ( $attachment['inquiry_id'] ?? 0 ),
			$success ? 'attachment_retention_updated' : 'attachment_error'
		);
	}

	public static function handle_attachment_delete(): void {
		if ( ! current_user_can( 'sc_intake_delete' ) ) {
			wp_die( esc_html__( 'You do not have permission to permanently delete private attachments.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$id = isset( $_POST['attachment_id'] ) ? absint( $_POST['attachment_id'] ) : 0;
		check_admin_referer( 'sc_ei_delete_attachment_' . $id );

		$attachment = SC_EI_Attachment_Repository::find( $id );
		$reason     = isset( $_POST['delete_reason'] ) ? sanitize_textarea_field( wp_unslash( $_POST['delete_reason'] ) ) : '';

		$success = false;
		if ( $attachment && empty( $attachment['deleted_at'] ) && SC_EI_Storage::delete_file( (string) $attachment['relative_path'] ) ) {
			$success = SC_EI_Attachment_Repository::mark_deleted(
				$id,
				get_current_user_id(),
				$reason ?: 'Private attachment permanently deleted by an authorized administrator.',
				'deleted'
			);
		}

		self::redirect_to_inquiry(
			(int) ( $attachment['inquiry_id'] ?? 0 ),
			$success ? 'attachment_deleted' : 'attachment_error'
		);
	}

	private static function redirect_to_inquiry( int $inquiry_id, string $message ): void {
		$url = add_query_arg(
			array(
				'page'      => 'sc-engagement-intake',
				'action'    => 'view',
				'inquiry'   => $inquiry_id,
				'sc_ei_msg' => sanitize_key( $message ),
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}

	public static function handle_note(): void {
		if ( ! current_user_can( 'sc_intake_add_notes' ) ) {
			wp_die( esc_html__( 'You do not have permission to add private notes.', 'sustainable-catalyst-engagement-intake' ) );
		}

		check_admin_referer( 'sc_ei_add_note' );

		$id   = isset( $_POST['inquiry_id'] ) ? absint( $_POST['inquiry_id'] ) : 0;
		$note = isset( $_POST['internal_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['internal_note'] ) ) : '';

		$success = $id && SC_EI_Inquiry_Repository::add_internal_note( $id, $note );

		$url = add_query_arg(
			array(
				'page'      => 'sc-engagement-intake',
				'action'    => 'view',
				'inquiry'   => $id,
				'sc_ei_msg' => $success ? 'note_added' : 'error',
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $url );
		exit;
	}
}
