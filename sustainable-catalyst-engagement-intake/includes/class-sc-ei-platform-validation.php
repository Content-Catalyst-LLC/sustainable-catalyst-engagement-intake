<?php
/**
 * Admin-only live validation and backup evidence for the production gate.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Platform_Validation {

	private const RESULT_OPTION = 'sc_ei_platform_live_validation';
	private const BACKUP_OPTION = 'sc_ei_platform_backup_attestation';
	private const MAX_AGE_DAYS = 7;

	public static function latest(): array {
		$value = get_option( self::RESULT_OPTION, array() );
		return is_array( $value ) ? $value : array();
	}

	public static function backup_attestation(): array {
		$value = get_option( self::BACKUP_OPTION, array() );
		return is_array( $value ) ? $value : array();
	}

	public static function successful_and_fresh(): bool {
		$result = self::latest();
		return ! empty( $result['passed'] )
			&& SC_EI_VERSION === (string) ( $result['plugin_version'] ?? '' )
			&& self::fresh_timestamp( (string) ( $result['completed_at'] ?? '' ) );
	}

	public static function backups_fresh(): bool {
		$attestation = self::backup_attestation();
		return ! empty( $attestation['database_confirmed'] )
			&& ! empty( $attestation['storage_confirmed'] )
			&& SC_EI_VERSION === (string) ( $attestation['plugin_version'] ?? '' )
			&& self::fresh_timestamp( (string) ( $attestation['attested_at'] ?? '' ) );
	}

	public static function attest_backups( string $database_reference, string $storage_reference, int $actor_user_id ) {
		$database_reference = sanitize_text_field( $database_reference );
		$storage_reference = sanitize_text_field( $storage_reference );
		if ( strlen( trim( $database_reference ) ) < 3 || strlen( trim( $storage_reference ) ) < 3 ) {
			return new WP_Error( 'platform_backup_reference_required', __( 'Record a short reference for both the database backup and protected-storage backup.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$value = array(
			'schema'               => 'sc-platform-backup-attestation/1.0',
			'plugin_version'       => SC_EI_VERSION,
			'database_confirmed'   => true,
			'storage_confirmed'    => true,
			'database_reference'   => $database_reference,
			'storage_reference'    => $storage_reference,
			'attested_by'          => $actor_user_id,
			'attested_at'          => current_time( 'mysql', true ),
		);
		update_option( self::BACKUP_OPTION, $value, false );
		SC_EI_Audit_Log::record(
			'platform_backups_attested',
			'Authorized staff attested that current database and protected-storage backups are available.',
			array(
				'plugin_version'     => SC_EI_VERSION,
				'database_reference' => $database_reference,
				'storage_reference'  => $storage_reference,
			),
			null,
			null,
			$actor_user_id
		);
		return $value;
	}

	public static function run( string $mail_recipient, int $actor_user_id ) : array {
		global $wpdb;

		$mail_recipient = sanitize_email( $mail_recipient );
		$started = microtime( true );
		$checks = array();
		$inquiry_id = 0;
		$access_id = 0;
		$support_case_id = 0;
		$support_signal_id = 0;
		$support_link_ids = array();
		$meeting_id = 0;
		$proposal_id = 0;
		$sow_id = 0;
		$change_request_id = 0;
		$engagement_id = 0;
		$workspace_id = 0;
		$workspace_deliverable_id = 0;
		$service_intelligence_finding_id = 0;
		$billing_profile_id = 0;
		$invoice_id = 0;
		$payment_handoff_id = 0;
		$relative_path = '';
		$temp_path = '';
		$cleanup_ok = true;

		self::add( $checks, 'version_state', __( 'Installed version state', 'sustainable-catalyst-engagement-intake' ), SC_EI_VERSION === (string) get_option( 'sc_ei_version', '' ), SC_EI_VERSION . ' / ' . (string) get_option( 'sc_ei_version', '' ) );
		$tables = SC_EI_Database::tables_exist();
		$columns = SC_EI_Database::platform_columns_exist();
		$inquiry_columns = SC_EI_Database::inquiry_columns_exist();
		$lifecycle_columns = SC_EI_Database::lifecycle_columns_exist();
		$support_columns = SC_EI_Database::support_columns_exist();
		$calendar_columns = SC_EI_Database::calendar_columns_exist();
		$proposal_columns = SC_EI_Database::proposal_governance_columns_exist();
		$workspace_columns = SC_EI_Database::workspace_columns_exist();
		$service_intelligence_columns = SC_EI_Database::service_intelligence_columns_exist();
		$billing_columns = SC_EI_Database::billing_columns_exist();
		self::add( $checks, 'database_contract', __( 'Database tables, platform, lifecycle, support, calendar, proposal-governance, client-workspace, service-intelligence, and billing schema', 'sustainable-catalyst-engagement-intake' ), ! in_array( false, $tables, true ) && ! in_array( false, $columns, true ) && ! in_array( false, $inquiry_columns, true ) && ! in_array( false, $lifecycle_columns, true ) && ! in_array( false, $support_columns, true ) && ! in_array( false, $calendar_columns, true ) && ! in_array( false, $proposal_columns, true ) && ! in_array( false, $workspace_columns, true ) && ! in_array( false, $service_intelligence_columns, true ) && ! in_array( false, $billing_columns, true ), sprintf( '%d/%d tables; %d/%d platform columns; %d/%d inquiry columns; %d/%d lifecycle columns; %d/%d support columns; %d/%d calendar columns; %d/%d proposal-governance columns; %d/%d workspace columns; %d/%d service-intelligence columns; %d/%d billing columns', count( array_filter( $tables ) ), count( $tables ), count( array_filter( $columns ) ), count( $columns ), count( array_filter( $inquiry_columns ) ), count( $inquiry_columns ), count( array_filter( $lifecycle_columns ) ), count( $lifecycle_columns ), count( array_filter( $support_columns ) ), count( $support_columns ), count( array_filter( $calendar_columns ) ), count( $calendar_columns ), count( array_filter( $proposal_columns ) ), count( $proposal_columns ), count( array_filter( $workspace_columns ) ), count( $workspace_columns ), count( array_filter( $service_intelligence_columns ) ), count( $service_intelligence_columns ), count( array_filter( $billing_columns ) ), count( $billing_columns ) ) );
		$lifecycle_migration = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM " . SC_EI_Database::table( 'platform_migrations' ) . " WHERE migration_key = %s LIMIT 1", SC_EI_Lifecycle_Repository::MIGRATION_KEY ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		self::add( $checks, 'lifecycle_migration', __( 'v1.1.0 advisory lifecycle migration journal', 'sustainable-catalyst-engagement-intake' ), 'completed' === (string) $lifecycle_migration, (string) ( $lifecycle_migration ?: 'missing' ) );
		$support_migration = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM " . SC_EI_Database::table( 'platform_migrations' ) . " WHERE migration_key = %s LIMIT 1", SC_EI_Support_Repository::MIGRATION_KEY ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		self::add( $checks, 'support_migration', __( 'v1.2.0 support operations migration journal', 'sustainable-catalyst-engagement-intake' ), 'completed' === (string) $support_migration, (string) ( $support_migration ?: 'missing' ) );
		$support_patch_migration = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM " . SC_EI_Database::table( 'platform_migrations' ) . " WHERE migration_key = %s LIMIT 1", SC_EI_Support_Repository::PATCH_MIGRATION_KEY ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		self::add( $checks, 'support_reliability_patch', __( 'v1.2.1 support reliability migration journal', 'sustainable-catalyst-engagement-intake' ), 'completed' === (string) $support_patch_migration, (string) ( $support_patch_migration ?: 'missing' ) );
		$calendar_migration = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM " . SC_EI_Database::table( 'platform_migrations' ) . " WHERE migration_key = %s LIMIT 1", SC_EI_Calendar_Repository::MIGRATION_KEY ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		self::add( $checks, 'calendar_migration', __( 'v1.3.0 Microsoft Teams and calendar coordination migration journal', 'sustainable-catalyst-engagement-intake' ), 'completed' === (string) $calendar_migration, (string) ( $calendar_migration ?: 'missing' ) );
		$calendar_patch_migration = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM " . SC_EI_Database::table( 'platform_migrations' ) . " WHERE migration_key = %s LIMIT 1", SC_EI_Calendar_Repository::PATCH_MIGRATION_KEY ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		self::add( $checks, 'calendar_reliability_patch', __( 'v1.3.1 scheduling, reminder, and time-zone reliability journal', 'sustainable-catalyst-engagement-intake' ), 'completed' === (string) $calendar_patch_migration, (string) ( $calendar_patch_migration ?: 'missing' ) );
		$proposal_migration = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM " . SC_EI_Database::table( 'platform_migrations' ) . " WHERE migration_key = %s LIMIT 1", SC_EI_Proposal_Governance_Repository::MIGRATION_KEY ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		self::add( $checks, 'proposal_governance_migration', __( 'v1.4.0 proposal, Statement of Work, approval, and change-request migration journal', 'sustainable-catalyst-engagement-intake' ), 'completed' === (string) $proposal_migration, (string) ( $proposal_migration ?: 'missing' ) );
		$proposal_patch_migration = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM " . SC_EI_Database::table( 'platform_migrations' ) . " WHERE migration_key = %s LIMIT 1", SC_EI_Proposal_Governance_Repository::PATCH_MIGRATION_KEY ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$workspace_migration = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM " . SC_EI_Database::table( 'platform_migrations' ) . " WHERE migration_key = %s LIMIT 1", SC_EI_Workspace_Repository::MIGRATION_KEY ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		self::add( $checks, 'workspace_migration', __( 'v1.5.0 secure client workspace and collaboration migration', 'sustainable-catalyst-engagement-intake' ), 'completed' === (string) $workspace_migration && SC_EI_WORKSPACE_SCHEMA_VERSION === (string) get_option( 'sc_ei_workspace_schema_version', '' ), (string) ( $workspace_migration ?: 'missing' ) . ' / ' . (string) get_option( 'sc_ei_workspace_schema_version', 'missing' ) );
		$service_intelligence_migration = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM " . SC_EI_Database::table( 'platform_migrations' ) . " WHERE migration_key = %s LIMIT 1", SC_EI_Service_Intelligence_Repository::MIGRATION_KEY ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		self::add( $checks, 'service_intelligence_migration', __( 'v1.6.0 engagement analytics and service-intelligence migration', 'sustainable-catalyst-engagement-intake' ), 'completed' === (string) $service_intelligence_migration && SC_EI_SERVICE_INTELLIGENCE_SCHEMA_VERSION === (string) get_option( 'sc_ei_service_intelligence_schema_version', '' ), (string) ( $service_intelligence_migration ?: 'missing' ) . ' / ' . (string) get_option( 'sc_ei_service_intelligence_schema_version', 'missing' ) );
		$billing_migration = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM " . SC_EI_Database::table( 'platform_migrations' ) . " WHERE migration_key = %s LIMIT 1", SC_EI_Billing_Repository::MIGRATION_KEY ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		self::add( $checks, 'billing_migration', __( 'v1.7.0 billing, invoicing, and payment-handoff migration', 'sustainable-catalyst-engagement-intake' ), 'completed' === (string) $billing_migration && SC_EI_BILLING_SCHEMA_VERSION === (string) get_option( 'sc_ei_billing_schema_version', '' ), (string) ( $billing_migration ?: 'missing' ) . ' / ' . (string) get_option( 'sc_ei_billing_schema_version', 'missing' ) );
		self::add( $checks, 'proposal_reliability_patch', __( 'v1.4.1 proposal-versioning, approval, and engagement-conversion reliability journal', 'sustainable-catalyst-engagement-intake' ), 'completed' === (string) $proposal_patch_migration, (string) ( $proposal_patch_migration ?: 'missing' ) );
		$timezone_runtime = SC_EI_Calendar_Repository::timezone_runtime_evidence();
		self::add( $checks, 'calendar_timezone_runtime', __( 'Strict daylight-saving time validation', 'sustainable-catalyst-engagement-intake' ), ! in_array( false, $timezone_runtime, true ), wp_json_encode( $timezone_runtime ) );

		$page_evidence = SC_EI_Platform_Repository::page_contract_evidence();
		self::add( $checks, 'public_page_contracts', __( 'Published public-entry and portal page contracts', 'sustainable-catalyst-engagement-intake' ), ! empty( $page_evidence['public_entry']['passed'] ) && ! empty( $page_evidence['portal']['passed'] ), (string) ( $page_evidence['summary'] ?? '' ) );

		$cron_evidence = SC_EI_Platform_Repository::cron_evidence();
		self::add( $checks, 'cron_runtime', __( 'Scheduled jobs and registered callbacks', 'sustainable-catalyst-engagement-intake' ), ! empty( $cron_evidence['passed'] ), (string) ( $cron_evidence['detail'] ?? '' ) );

		$accessibility = SC_EI_Platform_Repository::accessibility_evidence();
		self::add( $checks, 'accessibility_runtime', __( 'Rendered accessibility contract', 'sustainable-catalyst-engagement-intake' ), ! empty( $accessibility['passed'] ), (string) ( $accessibility['detail'] ?? '' ) );

		$duplicate_controls = SC_EI_Form_Handler::validation_duplicate_controls();
		self::add(
			$checks,
			'duplicate_controls',
			__( 'Duplicate-submission and concurrent-request controls', 'sustainable-catalyst-engagement-intake' ),
			! empty( $duplicate_controls['passed'] ),
			! empty( $duplicate_controls['passed'] ) ? 'duplicate fingerprint and request lock both blocked a second attempt and cleaned up' : wp_json_encode( $duplicate_controls )
		);

		$route_evidence = SC_EI_Pilot_Operations::route_contract_evidence();
		self::add( $checks, 'routed_entry_contracts', __( 'Advisory, AI Assurance, collaboration, media, and technical entry routes', 'sustainable-catalyst-engagement-intake' ), ! empty( $route_evidence['passed'] ), (string) ( $route_evidence['detail'] ?? '' ) );

		$upload_security = SC_EI_Upload_Validator::runtime_security_probe();
		self::add( $checks, 'upload_security_runtime', __( 'Upload validator clean-file acceptance and executable rejection', 'sustainable-catalyst-engagement-intake' ), ! empty( $upload_security['passed'] ), (string) ( $upload_security['detail'] ?? '' ) );

		$storage_probe = SC_EI_Storage::probe();
		$storage_health = SC_EI_Storage::storage_health();
		$storage_secure = ! empty( $storage_health['outside_document_root'] ) || ! empty( $storage_health['protection_files'] );
		self::add( $checks, 'protected_storage_runtime', __( 'Protected storage write/read/rename/delete and exposure posture', 'sustainable-catalyst-engagement-intake' ), ! empty( $storage_probe['ok'] ) && $storage_secure, (string) ( $storage_probe['message'] ?? '' ) . '; ' . ( $storage_secure ? 'exposure controls present' : 'exposure controls unavailable' ) );

		try {
			$validation_email = is_email( $mail_recipient ) ? $mail_recipient : (string) get_option( 'admin_email' );
			$inquiry_id = SC_EI_Inquiry_Repository::create(
				array(
					'inquiry_type'    => 'general',
					'contact_name'    => 'Platform Validation',
					'contact_email'   => $validation_email,
					'subject'         => '[TEST] v1.6.0 live validation',
					'message'         => 'Temporary administrator-generated validation record. Safe to remove.',
					'form_variant'    => 'advanced',
					'source_page'     => 'platform-validation',
					'entry_cta'       => 'admin-live-validation',
					'metadata'        => array( 'platform_validation' => true, 'plugin_version' => SC_EI_VERSION ),
					'consent_version' => 'admin-validation',
					'consent_at'      => current_time( 'mysql', true ),
				)
			);
			$created = $inquiry_id > 0 && null !== SC_EI_Inquiry_Repository::find( $inquiry_id );
			$transition_result = $created ? SC_EI_Lifecycle_Repository::transition(
				$inquiry_id,
				'under_review',
				$actor_user_id,
				array(
					'reason'      => 'Platform live validation lifecycle transition.',
					'next_action' => 'Complete the temporary validation review.',
				)
			) : new WP_Error( 'platform_validation_inquiry_missing', 'Validation inquiry unavailable.' );
			$transitioned = ! is_wp_error( $transition_result );
			$fresh = $transitioned ? SC_EI_Inquiry_Repository::find( $inquiry_id ) : null;
			$lifecycle_events = $transitioned ? SC_EI_Lifecycle_Repository::events( $inquiry_id, 20 ) : array();
			$sender_snapshot = $transitioned ? SC_EI_Lifecycle_Repository::sender_snapshot( $inquiry_id ) : array();
			$lifecycle_ok = $created
				&& $transitioned
				&& 'under_review' === (string) ( $fresh['status'] ?? '' )
				&& 'under_review' === (string) ( $fresh['lifecycle_stage'] ?? '' )
				&& ! empty( $lifecycle_events )
				&& '' !== trim( (string) ( $sender_snapshot['label'] ?? '' ) )
				&& 'Complete the temporary validation review.' === (string) ( $sender_snapshot['next_step'] ?? '' );
			self::add( $checks, 'inquiry_lifecycle', __( 'Inquiry persistence, audited lifecycle transition, and sender-safe projection', 'sustainable-catalyst-engagement-intake' ), $lifecycle_ok, $lifecycle_ok ? 'temporary inquiry created, transitioned through the governed lifecycle, audited, and safely projected' : ( is_wp_error( $transition_result ) ? $transition_result->get_error_message() : 'temporary lifecycle validation failed' ) );

			$support_case = $created ? SC_EI_Support_Repository::create_for_inquiry(
				$inquiry_id,
				array(
					'product'            => 'workbench',
					'product_version'    => 'validation',
					'component'          => 'live-validation',
					'issue_type'         => 'runtime_error',
					'error_message'      => 'Temporary support validation fixture.',
					'reproduction_steps' => 'Run the administrator live validation suite.',
					'source_system'      => 'platform_validation',
				),
				$actor_user_id
			) : new WP_Error( 'platform_validation_inquiry_missing', 'Validation inquiry unavailable.' );
			if ( ! is_wp_error( $support_case ) ) {
				$support_case_id = absint( $support_case['id'] ?? 0 );
				$support_transition = SC_EI_Support_Repository::transition(
					$support_case_id,
					'triage',
					'MOVE ' . strtoupper( (string) $support_case['case_number'] ) . ' TO TRIAGE',
					'Administrator live validation support transition.',
					$actor_user_id
				);
			} else {
				$support_transition = $support_case;
			}
			$support_snapshot = $support_case_id ? SC_EI_Support_Repository::sender_snapshot( $inquiry_id ) : array();
			$private_rejection = SC_EI_Support_Schema::signal_payload( array( 'product' => 'workbench', 'email' => 'private@example.com' ) );
			$handoff_id = 'validation-' . wp_generate_uuid4();
			$handoff_payload = array(
				'schema' => SC_EI_Support_Schema::HANDOFF_SCHEMA,
				'handoff_id' => $handoff_id,
				'inquiry_id' => $inquiry_id,
				'source_system' => 'feature_suggestions',
				'source_reference' => 'validation',
				'context' => array(
					'product' => 'workbench',
					'product_version' => 'validation',
					'component' => 'live-validation',
					'issue_type' => 'documentation',
					'search_query' => 'temporary validation query',
					'article_ids' => array( 101 ),
					'known_issue' => 'WB-VALIDATION',
					'feature_suggestion' => 'FS-VALIDATION',
					'product_release' => 'WB-vNext',
					'resolution_attempted' => true,
					'source_url' => home_url( '/support/' ),
				),
			);
			$handoff_first = SC_EI_Support_Repository::ingest_handoff( $handoff_payload, $actor_user_id );
			$handoff_second = SC_EI_Support_Repository::ingest_handoff( $handoff_payload, $actor_user_id );
			if ( ! is_wp_error( $handoff_first ) ) {
				$links = SC_EI_Support_Repository::links( $support_case_id );
				$support_link_ids = array_map( 'absint', wp_list_pluck( $links, 'id' ) );
				$signal_row = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM " . SC_EI_Database::table( 'support_signals' ) . " WHERE product = %s AND product_version = %s AND component = %s ORDER BY id DESC LIMIT 1", 'workbench', 'validation', 'live-validation' ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$support_signal_id = absint( $signal_row['id'] ?? 0 );
			}
			$unsafe_keys = array_intersect( array_keys( $support_snapshot ), array( 'error_message', 'reproduction_steps', 'assigned_user_id', 'environment_json' ) );
			$support_ok = ! is_wp_error( $support_case )
				&& ! is_wp_error( $support_transition )
				&& 'triage' === (string) ( $support_transition['workflow_stage'] ?? '' )
				&& ! empty( $support_snapshot['case_number'] )
				&& 'Under Review' === (string) ( $support_snapshot['status'] ?? '' )
				&& empty( $unsafe_keys )
				&& is_wp_error( $private_rejection )
				&& ! is_wp_error( $handoff_first )
				&& ! is_wp_error( $handoff_second )
				&& empty( $handoff_first['idempotent'] )
				&& ! empty( $handoff_second['idempotent'] )
				&& count( $support_link_ids ) >= 4;
			self::add( $checks, 'support_operations', __( 'Support persistence, governed transition, Sender Portal isolation, typed relationships, and idempotent privacy-safe handoff', 'sustainable-catalyst-engagement-intake' ), $support_ok, $support_ok ? 'temporary support case created; sender projection isolated; known-issue, feature, release, and handoff relationships created; duplicate handoff replayed idempotently' : ( is_wp_error( $support_transition ) ? $support_transition->get_error_message() : ( is_wp_error( $handoff_first ) ? $handoff_first->get_error_message() : ( is_wp_error( $handoff_second ) ? $handoff_second->get_error_message() : 'support operations validation failed' ) ) ) );

			$portal_result = $created ? SC_EI_Portal_Repository::issue_invitation(
				$inquiry_id,
				array(
					'invite_ttl_hours' => 1,
					'permissions'      => SC_EI_Portal_Schema::default_settings()['portal_default_permissions'],
					'invitation_note'  => 'Temporary live-validation invitation; not emailed.',
				),
				$actor_user_id
			) : new WP_Error( 'platform_validation_inquiry_missing', 'Validation inquiry unavailable.' );
			$portal_ok = ! is_wp_error( $portal_result ) && ! empty( $portal_result['raw_token'] ) && ! empty( $portal_result['access']['public_id'] );
			if ( $portal_ok ) {
				$access_id = absint( $portal_result['access']['id'] ?? 0 );
				$inspection = SC_EI_Portal_Repository::inspect_invitation( (string) $portal_result['access']['public_id'], (string) $portal_result['raw_token'] );
				$portal_ok = ! empty( $inspection['verified'] );
			}
			self::add( $checks, 'portal_invitation', __( 'Sender portal token issue and verification', 'sustainable-catalyst-engagement-intake' ), $portal_ok, $portal_ok ? 'temporary invitation verified without storing the raw token' : ( is_wp_error( $portal_result ) ? $portal_result->get_error_message() : 'portal token verification failed' ) );

			if ( $portal_ok ) {
				$proposal_input = array(
					'title'             => '[TEST] Governed advisory proposal',
					'executive_summary' => 'Temporary proposal created by administrator Live Validation.',
					'scope'             => array( 'Validate governed proposal revisions', 'Validate Statement of Work approvals' ),
					'deliverables'      => array( 'Temporary validation record', 'Complete cleanup evidence' ),
					'exclusions'        => array( 'No real services are authorized' ),
					'assumptions'       => array( 'This record is temporary' ),
					'timeline_text'     => 'Temporary validation only.',
					'fee_summary'       => '$0 validation fixture.',
					'payment_terms'     => 'No payment is due.',
					'legal_terms'       => 'Not a contract.',
					'version_note'      => 'Initial validation version.',
					'currency'          => 'USD',
					'total'             => 0,
					'expires_at'        => gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ),
				);
				$proposal = SC_EI_Workflow_Repository::create_proposal( $inquiry_id, $proposal_input, $actor_user_id, true );
				if ( ! is_wp_error( $proposal ) ) {
					$proposal_id = absint( $proposal['id'] ?? 0 );
					$changes_requested = SC_EI_Workflow_Repository::respond_to_proposal( $proposal_id, 'request_changes', 'Please publish a temporary revised version.', true, true, 'REQUEST CHANGES ' . strtoupper( (string) $proposal['proposal_number'] ), $access_id );
					$revised_input = $proposal_input;
					$revised_input['executive_summary'] = 'Revised temporary proposal created by administrator Live Validation.';
					$revised_input['version_note'] = 'Revision preserves the initial proposal version.';
					$revised = ! is_wp_error( $changes_requested ) ? SC_EI_Workflow_Repository::add_proposal_version( $proposal_id, $revised_input, $actor_user_id ) : $changes_requested;
					$published = ! is_wp_error( $revised ) ? SC_EI_Workflow_Repository::publish_proposal( $proposal_id, $actor_user_id ) : $revised;
					$sow_input = array(
						'title' => '[TEST] Statement of Work',
						'purpose_background' => 'Temporary SOW for Live Validation.',
						'scope' => array( 'Validate SOW versioning and sender projection' ),
						'deliverables' => array( 'Temporary SOW record' ),
						'milestones' => array( 'Create', 'Approve', 'Clean up' ),
						'responsibilities' => array( 'Administrator removes all temporary records' ),
						'dependencies' => array( 'Published proposal version' ),
						'acceptance_criteria' => 'The current version is approved, sender-visible, immutable after sender approval, and removed during cleanup.',
						'change_control' => 'Changes require a new version or governed change request.',
						'communication_expectations' => 'No external communication is sent.',
						'data_handling' => 'Temporary validation data only.',
						'ip_terms' => 'No intellectual-property transfer.',
						'open_source_boundaries' => 'Open-source software remains under its existing license.',
						'fees_payment' => 'No fee or payment.',
						'start_date' => gmdate( 'Y-m-d' ),
						'target_end_date' => gmdate( 'Y-m-d', time() + DAY_IN_SECONDS ),
						'termination_conditions' => 'Removed automatically after validation.',
						'version_note' => 'Initial validation SOW.',
					);
					$sow = ! is_wp_error( $published ) ? SC_EI_Proposal_Governance_Repository::create_sow_from_proposal( $proposal_id, $sow_input, $actor_user_id ) : $published;
					if ( ! is_wp_error( $sow ) ) { $sow_id = absint( $sow['id'] ?? 0 ); }
					$sow_approved = $sow_id ? SC_EI_Proposal_Governance_Repository::approve_sow( $sow_id, 'APPROVE ' . strtoupper( (string) $sow['sow_number'] ), $actor_user_id ) : $sow;
					$current_proposal = SC_EI_Workflow_Repository::find_proposal( $proposal_id, true );
					$sender_sow_approval = ! is_wp_error( $sow_approved ) && $current_proposal ? SC_EI_Proposal_Governance_Repository::record_sender_action( $proposal_id, absint( $current_proposal['current_version_id'] ), 'sow_approved', 'Temporary sender SOW approval.', true, true, 'APPROVE ' . strtoupper( (string) $sow_approved['sow_number'] ), $access_id, $sow_id ) : $sow_approved;
					$sender_sow_replay = ! is_wp_error( $sender_sow_approval ) ? SC_EI_Proposal_Governance_Repository::record_sender_action( $proposal_id, absint( $current_proposal['current_version_id'] ), 'sow_approved', 'Temporary sender SOW approval.', true, true, 'APPROVE ' . strtoupper( (string) $sow_approved['sow_number'] ), $access_id, $sow_id ) : $sender_sow_approval;
					$accepted = ! is_wp_error( $sender_sow_replay ) ? SC_EI_Workflow_Repository::respond_to_proposal( $proposal_id, 'accept', 'Temporary acceptance for validation.', true, true, 'ACCEPT ' . strtoupper( (string) $current_proposal['proposal_number'] ), $access_id ) : $sender_sow_replay;
					$accepted_replay = ! is_wp_error( $accepted ) ? SC_EI_Workflow_Repository::respond_to_proposal( $proposal_id, 'accept', 'Temporary acceptance for validation.', true, true, 'ACCEPT ' . strtoupper( (string) $current_proposal['proposal_number'] ), $access_id ) : $accepted;
					$contracted = ! is_wp_error( $accepted_replay ) ? SC_EI_Workflow_Repository::change_proposal_status( $proposal_id, 'contracted', 'Temporary external contract evidence for validation.', 'VALIDATION-CONTRACT-' . $proposal_id, $actor_user_id ) : $accepted_replay;
					$conversion = ! is_wp_error( $contracted ) ? SC_EI_Proposal_Governance_Repository::convert_to_engagement( $proposal_id, array( 'engagement_title' => '[TEST] Validation engagement', 'proposed_start_date' => gmdate( 'Y-m-d' ), 'target_end_date' => gmdate( 'Y-m-d', time() + DAY_IN_SECONDS ), 'sender_summary' => 'Temporary sender-visible validation engagement.' ), 'CONVERT ' . strtoupper( (string) $current_proposal['proposal_number'] ), $actor_user_id ) : $contracted;
					$conversion_replay = ! is_wp_error( $conversion ) ? SC_EI_Proposal_Governance_Repository::convert_to_engagement( $proposal_id, array( 'engagement_title' => '[TEST] Validation engagement', 'proposed_start_date' => gmdate( 'Y-m-d' ), 'target_end_date' => gmdate( 'Y-m-d', time() + DAY_IN_SECONDS ), 'sender_summary' => 'Temporary sender-visible validation engagement.' ), 'CONVERT ' . strtoupper( (string) $current_proposal['proposal_number'] ), $actor_user_id ) : $conversion;
					if ( ! is_wp_error( $conversion_replay ) ) { $engagement_id = absint( $conversion_replay['engagement']['id'] ?? 0 ); }
					$change = ! is_wp_error( $conversion ) ? SC_EI_Proposal_Governance_Repository::create_change_request( $inquiry_id, array( 'proposal_id' => $proposal_id, 'proposal_version_id' => absint( $current_proposal['current_version_id'] ), 'sow_id' => $sow_id, 'sow_version_id' => absint( $sow_approved['current_version_id'] ?? 0 ), 'engagement_id' => $engagement_id, 'request_summary' => 'Apply a temporary validation change.', 'reason' => 'Exercise governed change control.', 'scope_impact' => 'No real scope impact.', 'timeline_impact' => 'No real timeline impact.', 'fee_impact' => 0, 'currency' => 'USD' ), 'staff', $actor_user_id ) : $conversion;
					if ( ! is_wp_error( $change ) ) { $change_request_id = absint( $change['id'] ?? 0 ); }
					$change_review = $change_request_id ? SC_EI_Proposal_Governance_Repository::transition_change_request( $change_request_id, 'under_review', 'Temporary validation review.', 'UNDER_REVIEW ' . strtoupper( (string) $change['change_number'] ), $actor_user_id ) : $change;
					$change_approved = ! is_wp_error( $change_review ) ? SC_EI_Proposal_Governance_Repository::transition_change_request( $change_request_id, 'approved', 'Temporary validation approval.', 'APPROVED ' . strtoupper( (string) $change['change_number'] ), $actor_user_id ) : $change_review;
					$change_applied = ! is_wp_error( $change_approved ) ? SC_EI_Proposal_Governance_Repository::transition_change_request( $change_request_id, 'applied', 'Temporary validation application.', 'APPLIED ' . strtoupper( (string) $change['change_number'] ), $actor_user_id ) : $change_approved;
					$sender_sows = SC_EI_Proposal_Governance_Repository::sender_snapshot( $inquiry_id );
					$unsafe_sow_keys = $sender_sows ? array_intersect( array_keys( $sender_sows[0] ), array( 'approved_by', 'created_by', 'row_version', 'immutable_hash', 'actor_id', 'decision_note' ) ) : array( 'missing_snapshot' );
					$approvals = SC_EI_Proposal_Governance_Repository::approvals_for_inquiry( $inquiry_id );
					$approval_count = count( $approvals );
					$approvals_valid = ! empty( $approvals );
					foreach ( $approvals as $approval ) {
						if ( ! SC_EI_Proposal_Governance_Repository::verify_approval_integrity( $approval ) ) {
							$approvals_valid = false;
							break;
						}
					}
					$version_count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM " . SC_EI_Database::table( 'proposal_versions' ) . " WHERE proposal_id = %d", $proposal_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$final_proposal = SC_EI_Workflow_Repository::find_proposal( $proposal_id, true );
					$proposal_governance_ok = ! is_wp_error( $changes_requested ) && ! is_wp_error( $revised ) && ! is_wp_error( $published ) && $version_count >= 2
						&& ! is_wp_error( $sow_approved ) && ! is_wp_error( $sender_sow_approval ) && ! empty( $sender_sow_replay['_idempotent'] )
						&& ! is_wp_error( $accepted ) && ! empty( $accepted_replay['_idempotent'] ) && ! is_wp_error( $contracted )
						&& ! is_wp_error( $conversion ) && ! empty( $conversion_replay['idempotent'] ) && ! empty( $engagement_id )
						&& 'converted_to_engagement' === (string) ( $final_proposal['status'] ?? '' )
						&& ! is_wp_error( $change_applied ) && 'applied' === (string) ( $change_applied['status'] ?? '' )
						&& $approval_count >= 4 && $approvals_valid && ! empty( $sender_sows ) && empty( $unsafe_sow_keys );
				} else {
					$proposal_governance_ok = false;
				}
				self::add( $checks, 'proposal_governance', __( 'Proposal revisions, Statement of Work approval, immutable sender decisions, engagement conversion, and governed change control', 'sustainable-catalyst-engagement-intake' ), $proposal_governance_ok, $proposal_governance_ok ? 'temporary proposal revised without overwriting version one; SOW and proposal approvals replayed idempotently; immutable receipts verified; engagement conversion repaired safely on replay; change request applied' : ( is_wp_error( $proposal ) ? $proposal->get_error_message() : 'proposal governance validation failed' ) );
			} else {
				self::add( $checks, 'proposal_governance', __( 'Proposal revisions, Statement of Work approval, immutable sender decisions, engagement conversion, and governed change control', 'sustainable-catalyst-engagement-intake' ), false, 'Sender Portal access was unavailable for proposal-governance validation.' );
			}


			if ( $portal_ok && $engagement_id ) {
				$workspace = SC_EI_Workspace_Repository::create_for_engagement(
					$engagement_id,
					array(
						'title'            => '[TEST] Secure client workspace',
						'owner_user_id'    => $actor_user_id,
						'sender_summary'   => 'Temporary sender-visible workspace summary.',
						'sender_next_step' => 'Review the temporary validation deliverable.',
						'sender_visible'   => true,
					),
					$actor_user_id
				);
				if ( ! is_wp_error( $workspace ) ) {
					$workspace_id = absint( $workspace['id'] ?? 0 );
					$activated_workspace = SC_EI_Workspace_Repository::transition( $workspace_id, 'active', 'ACTIVE ' . strtoupper( (string) $workspace['workspace_number'] ), 'Temporary Live Validation activation.', $actor_user_id );
					$milestone = ! is_wp_error( $activated_workspace ) ? SC_EI_Workspace_Repository::add_milestone( $workspace_id, array( 'title' => 'Validation milestone', 'description' => 'Temporary sender-visible milestone.', 'status' => 'in_progress', 'due_date' => gmdate( 'Y-m-d', time() + DAY_IN_SECONDS ), 'sender_visible' => true ), $actor_user_id ) : $activated_workspace;
					$deliverable = ! is_wp_error( $milestone ) ? SC_EI_Workspace_Repository::add_deliverable( $workspace_id, array( 'title' => 'Validation deliverable', 'description' => 'Temporary sender-visible deliverable.', 'due_date' => gmdate( 'Y-m-d', time() + DAY_IN_SECONDS ), 'sender_visible' => true, 'approval_required' => true ), $actor_user_id ) : $milestone;
					if ( ! is_wp_error( $deliverable ) ) {
						$workspace_deliverable_id = absint( $deliverable['id'] ?? 0 );
					}
					$published_deliverable = $workspace_deliverable_id ? SC_EI_Workspace_Repository::publish_deliverable( $workspace_deliverable_id, true, $actor_user_id ) : $deliverable;
					$sender_decision = ! is_wp_error( $published_deliverable ) ? SC_EI_Workspace_Repository::record_sender_deliverable_decision( $workspace_deliverable_id, 'accepted', 'Temporary sender acceptance.', $inquiry_id ) : $published_deliverable;
					$staff_message = ! is_wp_error( $sender_decision ) ? SC_EI_Workspace_Repository::add_message( $workspace_id, 'Temporary reviewed staff update.', true, $actor_user_id, $workspace_deliverable_id ) : $sender_decision;
					$sender_message = ! is_wp_error( $staff_message ) ? SC_EI_Workspace_Repository::add_sender_message( $workspace_id, $inquiry_id, 'Temporary sender collaboration response.' ) : $staff_message;
					$workspace_snapshot = SC_EI_Workspace_Repository::sender_snapshot( $inquiry_id );
					$workspace_members = SC_EI_Workspace_Repository::members( $workspace_id );
					$workspace_projection_keys = $workspace_snapshot ? array_keys( $workspace_snapshot[0] ) : array();
					$unsafe_workspace_keys = array_diff( $workspace_projection_keys, SC_EI_Workspace_Schema::sender_projection_keys() );
					$workspace_ok = ! is_wp_error( $activated_workspace )
						&& ! is_wp_error( $milestone )
						&& ! is_wp_error( $published_deliverable )
						&& ! is_wp_error( $sender_decision )
						&& ! is_wp_error( $staff_message )
						&& ! is_wp_error( $sender_message )
						&& ! empty( $workspace_snapshot[0]['deliverables'] )
						&& 'accepted' === (string) ( $workspace_snapshot[0]['deliverables'][0]['sender_decision'] ?? '' )
						&& count( $workspace_snapshot[0]['messages'] ?? array() ) >= 2
						&& count( $workspace_members ) >= 2
						&& empty( $unsafe_workspace_keys );
				} else {
					$workspace_ok = false;
				}
				self::add( $checks, 'client_workspace_collaboration', __( 'Secure client workspace, membership isolation, milestones, deliverables, sender decisions, and collaboration messages', 'sustainable-catalyst-engagement-intake' ), $workspace_ok, $workspace_ok ? 'temporary engagement workspace activated; staff and sender membership recorded; milestone and deliverable published; sender decision and two-way collaboration recorded through an explicit projection allowlist' : ( is_wp_error( $workspace ) ? $workspace->get_error_message() : 'secure client workspace validation failed' ) );
			} else {
				self::add( $checks, 'client_workspace_collaboration', __( 'Secure client workspace, membership isolation, milestones, deliverables, sender decisions, and collaboration messages', 'sustainable-catalyst-engagement-intake' ), false, 'A validated Sender Portal invitation and converted engagement are required for workspace validation.' );
			}


			if ( $portal_ok ) {
				$timezone = SC_EI_Teams::valid_timezone( wp_timezone_string() ) ? wp_timezone_string() : 'America/Chicago';
				$zone = new DateTimeZone( $timezone );
				$slot = new DateTime( '+3 days 10:00', $zone );
				$meeting = SC_EI_Workflow_Repository::create_meeting_offer(
					$inquiry_id,
					array(
						'title'                => '[TEST] Teams coordination',
						'meeting_type'         => 'support_troubleshooting',
						'purpose'              => 'Temporary Microsoft Teams and calendar-coordination validation.',
						'duration_minutes'     => 30,
						'timezone'             => $timezone,
						'slots'                => array( $slot->format( 'Y-m-d\TH:i' ) ),
						'teams_url'            => 'https://teams.microsoft.com/l/meetup-join/sc-validation',
						'agenda'               => 'Validate scheduling, rescheduling, cancellation, reminders, and portal isolation.',
						'preparation_requests' => 'No preparation is required for this temporary validation.',
						'sender_summary'       => 'Temporary sender-visible validation summary.',
						'sender_next_step'     => 'Choose the offered time.',
					),
					$actor_user_id,
					true
				);
				if ( ! is_wp_error( $meeting ) ) {
					$meeting_id = absint( $meeting['id'] ?? 0 );
					$accepted = SC_EI_Workflow_Repository::respond_to_meeting( $meeting_id, 'accept', 'slot_1', 'Temporary validation acceptance.', 0 );
					$coordination = ! is_wp_error( $accepted ) ? SC_EI_Calendar_Repository::save_coordination( $meeting_id, array( 'organizer_name' => 'Sustainable Catalyst', 'organizer_email' => $validation_email, 'participant_emails' => array( $validation_email ), 'calendar_provider' => 'manual' ), $actor_user_id ) : $accepted;
					$new_start = new DateTime( '+4 days 11:00', $zone );
					$new_end = clone $new_start;
					$new_end->modify( '+30 minutes' );
					$rescheduled = ! is_wp_error( $coordination ) ? SC_EI_Calendar_Repository::reschedule( $meeting_id, $new_start->format( 'Y-m-d\TH:i' ), $new_end->format( 'Y-m-d\TH:i' ), $timezone, 'Temporary validation reschedule.', $actor_user_id ) : $coordination;
					$snapshot_rows = ! is_wp_error( $rescheduled ) ? SC_EI_Calendar_Repository::sender_snapshot( $inquiry_id ) : array();
					$snapshot = array();
					foreach ( $snapshot_rows as $candidate ) {
						if ( absint( $candidate['id'] ?? 0 ) === $meeting_id ) { $snapshot = $candidate; break; }
					}
					$unsafe = array_intersect( array_keys( $snapshot ), array( 'organizer_email', 'participant_emails_json', 'post_meeting_internal_notes', 'decisions', 'open_questions', 'created_by' ) );
					$canceled = ! is_wp_error( $rescheduled ) ? SC_EI_Calendar_Repository::cancel( $meeting_id, 'Temporary validation cancellation.', $actor_user_id ) : $rescheduled;
					if ( ! is_wp_error( $canceled ) ) { SC_EI_Calendar_Repository::process_due_reminders(); }
					$reminders = $meeting_id ? SC_EI_Calendar_Repository::reminders_for_meeting( $meeting_id ) : array();
					$cancellation_ready = false; $stale_open = false;
					foreach ( $reminders as $reminder ) {
						if ( 'canceled' === (string) $reminder['reminder_type'] && 'ready_for_review' === (string) $reminder['status'] ) { $cancellation_ready = true; }
						if ( in_array( (string) $reminder['reminder_type'], array( 'invitation', 'twenty_four_hour', 'one_hour', 'rescheduled' ), true ) && in_array( (string) $reminder['status'], array( 'pending', 'ready_for_review' ), true ) ) { $stale_open = true; }
					}
					$calendar_ok = ! is_wp_error( $accepted ) && 'scheduled' === (string) ( $accepted['status'] ?? '' )
						&& ! is_wp_error( $coordination ) && ! is_wp_error( $rescheduled ) && absint( $rescheduled['reschedule_count'] ?? 0 ) === 1
						&& ! empty( $snapshot['agenda'] ) && empty( $unsafe )
						&& ! is_wp_error( $canceled ) && 'canceled' === (string) ( $canceled['status'] ?? '' ) && empty( $canceled['teams_url'] )
						&& $cancellation_ready && ! $stale_open && count( $reminders ) >= 3;
				} else {
					$calendar_ok = false;
				}
				self::add( $checks, 'calendar_coordination', __( 'Microsoft Teams scheduling, rescheduling, reminder idempotency, cancellation safety, and Sender Portal isolation', 'sustainable-catalyst-engagement-intake' ), $calendar_ok, $calendar_ok ? 'temporary Teams meeting scheduled, rescheduled with strict time-zone handling, projected through a sender allowlist, canceled with join link revoked, stale reminders closed, and cancellation notice moved to review' : ( is_wp_error( $meeting ) ? $meeting->get_error_message() : 'calendar coordination validation failed' ) );
			} else {
				self::add( $checks, 'calendar_coordination', __( 'Microsoft Teams scheduling, rescheduling, reminder idempotency, cancellation safety, and Sender Portal isolation', 'sustainable-catalyst-engagement-intake' ), false, 'Sender Portal access was unavailable for the temporary meeting record.' );
			}

			$minimum_cohort = max( 5, absint( SC_EI_Analytics_Repository::settings()['analytics_minimum_cohort'] ?? 5 ) );
			$unsafe_finding = SC_EI_Service_Intelligence_Repository::create_finding(
				array(
					'finding_type' => 'service_demand',
					'title' => '[TEST] Personal data rejection',
					'cohort_count' => $minimum_cohort,
					'evidence' => array( 'email' => 'private@example.com', 'count' => $minimum_cohort ),
				),
				$actor_user_id
			);
			$finding = SC_EI_Service_Intelligence_Repository::create_finding(
				array(
					'finding_type' => 'service_demand',
					'severity' => 'watch',
					'title' => '[TEST] v1.6.0 live validation aggregate finding',
					'service_key' => 'advisory',
					'product_key' => 'contact-engagement-platform',
					'component_key' => 'service-intelligence',
					'cohort_count' => $minimum_cohort,
					'metric_value' => $minimum_cohort,
					'metric_unit' => 'count',
					'period_start' => gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS ),
					'period_end' => current_time( 'mysql', true ),
					'evidence' => array( 'metric' => 'validation_cohort', 'count' => $minimum_cohort, 'schema' => SC_EI_Service_Intelligence_Schema::SNAPSHOT_SCHEMA ),
				),
				$actor_user_id
			);
			$finding_transition = $finding;
			$finding_events = array();
			$finding_hash_ok = false;
			if ( ! is_wp_error( $finding ) ) {
				$service_intelligence_finding_id = absint( $finding['id'] ?? 0 );
				$finding_transition = SC_EI_Service_Intelligence_Repository::transition_finding( $service_intelligence_finding_id, 'reviewing', 'REVIEWING ' . strtoupper( (string) $finding['public_id'] ), 'Administrator validation review.', 'No service action is created automatically.', $actor_user_id );
				$finding_events = SC_EI_Service_Intelligence_Repository::events( $service_intelligence_finding_id );
				$evidence_json = (string) ( $finding['evidence_json'] ?? '' );
				$finding_hash_ok = hash_equals( (string) ( $finding['evidence_hash'] ?? '' ), hash( 'sha256', $evidence_json ) );
			}
			$snapshot = SC_EI_Service_Intelligence_Repository::create_snapshot( 30, $actor_user_id );
			$intelligence_ok = is_wp_error( $unsafe_finding )
				&& 'service_intelligence_personal_data_rejected' === $unsafe_finding->get_error_code()
				&& ! is_wp_error( $finding )
				&& ! is_wp_error( $finding_transition )
				&& 'reviewing' === (string) ( $finding_transition['status'] ?? '' )
				&& count( $finding_events ) >= 2
				&& $finding_hash_ok
				&& ! is_wp_error( $snapshot );
			self::add( $checks, 'engagement_analytics_service_intelligence', __( 'Aggregate analytics, minimum-cohort privacy, immutable evidence, human-reviewed findings, and auditable snapshot', 'sustainable-catalyst-engagement-intake' ), $intelligence_ok, $intelligence_ok ? 'personal-data payload rejected; aggregate finding created and moved to human review; evidence hash verified; current service-intelligence snapshot recorded' : ( is_wp_error( $finding_transition ) ? $finding_transition->get_error_message() : ( is_wp_error( $snapshot ) ? $snapshot->get_error_message() : 'service-intelligence validation failed' ) ) );


			$unsafe_payment_metadata = SC_EI_Billing_Schema::payment_metadata_is_safe( array( 'card_number' => '4242424242424242' ) );
			if ( $engagement_id ) {
				$billing_profile = SC_EI_Billing_Repository::create_profile( $engagement_id, array( 'organization_name' => 'Validation Organization', 'currency' => 'USD', 'payment_terms_days' => 30, 'sender_visible' => true ), $actor_user_id );
				if ( ! is_wp_error( $billing_profile ) ) {
					$billing_profile_id = absint( $billing_profile['id'] ?? 0 );
					$invoice = SC_EI_Billing_Repository::create_invoice( $engagement_id, array( 'billing_profile_id' => $billing_profile_id, 'currency' => 'USD', 'memo' => 'Temporary validation invoice.' ), $actor_user_id );
					if ( ! is_wp_error( $invoice ) ) {
						$invoice_id = absint( $invoice['id'] ?? 0 );
						$item = SC_EI_Billing_Repository::add_item( $invoice_id, array( 'description' => 'Temporary validation service', 'quantity' => 1, 'unit_amount_minor' => 10000 ), $actor_user_id );
						$invoice = SC_EI_Billing_Repository::find_invoice( $invoice_id );
						$reviewed = ! is_wp_error( $item ) ? SC_EI_Billing_Repository::transition( $invoice_id, 'internal_review', 'INTERNAL_REVIEW ' . strtoupper( (string) $invoice['invoice_number'] ), 'Temporary review.', $actor_user_id ) : $item;
						$invoice = SC_EI_Billing_Repository::find_invoice( $invoice_id );
						$approved = ! is_wp_error( $reviewed ) ? SC_EI_Billing_Repository::transition( $invoice_id, 'approved_to_issue', 'APPROVED_TO_ISSUE ' . strtoupper( (string) $invoice['invoice_number'] ), 'Temporary approval.', $actor_user_id ) : $reviewed;
						$invoice = SC_EI_Billing_Repository::find_invoice( $invoice_id );
						$issued = ! is_wp_error( $approved ) ? SC_EI_Billing_Repository::transition( $invoice_id, 'issued', 'ISSUED ' . strtoupper( (string) $invoice['invoice_number'] ), 'Temporary issue.', $actor_user_id ) : $approved;
						$handoff = ! is_wp_error( $issued ) ? SC_EI_Billing_Repository::create_payment_handoff( $invoice_id, array( 'provider' => 'manual', 'provider_reference' => 'validation', 'checkout_url' => 'https://example.com/pay/validation', 'amount_minor' => 10000, 'currency' => 'USD', 'idempotency_key' => 'validation-' . $invoice_id, 'sender_visible' => true, 'metadata' => array( 'environment' => 'validation' ) ), $actor_user_id ) : $issued;
						$handoff_replay = ! is_wp_error( $handoff ) ? SC_EI_Billing_Repository::create_payment_handoff( $invoice_id, array( 'provider' => 'manual', 'provider_reference' => 'validation', 'checkout_url' => 'https://example.com/pay/validation', 'amount_minor' => 10000, 'currency' => 'USD', 'idempotency_key' => 'validation-' . $invoice_id, 'sender_visible' => true, 'metadata' => array( 'environment' => 'validation' ) ), $actor_user_id ) : $handoff;
						if ( ! is_wp_error( $handoff ) ) { $payment_handoff_id = absint( $handoff['id'] ?? 0 ); }
						$settled = $payment_handoff_id ? SC_EI_Billing_Repository::record_payment_status( $payment_handoff_id, 'settled', 'validation-settled-' . $payment_handoff_id, array( 'environment' => 'validation' ), $actor_user_id ) : $handoff;
						$snapshot = SC_EI_Billing_Repository::sender_snapshot( $inquiry_id );
						$snapshot_json = wp_json_encode( $snapshot );
						$billing_ok = false === $unsafe_payment_metadata && ! is_wp_error( $settled ) && ! is_wp_error( $handoff_replay ) && absint( $handoff_replay['id'] ?? 0 ) === $payment_handoff_id && ! empty( $snapshot ) && false === strpos( (string) $snapshot_json, 'metadata_json' ) && 'paid' === (string) ( SC_EI_Billing_Repository::find_invoice( $invoice_id )['status'] ?? '' );
						self::add( $checks, 'billing_invoicing_payment_handoffs', __( 'Invoice lifecycle, privacy-safe external payment handoff, idempotent replay, settlement, and Sender Portal projection', 'sustainable-catalyst-engagement-intake' ), $billing_ok, $billing_ok ? 'temporary invoice issued; sensitive metadata rejected; handoff replay reused the same record; settled invoice projected safely' : 'billing validation failed' );
					}
				}
			} else {
				self::add( $checks, 'billing_invoicing_payment_handoffs', __( 'Invoice lifecycle, privacy-safe external payment handoff, idempotent replay, settlement, and Sender Portal projection', 'sustainable-catalyst-engagement-intake' ), false, 'A governed engagement was unavailable for billing validation.' );
			}


			$temp_path = wp_tempnam( 'sc-ei-platform-validation.txt' );
			if ( $temp_path ) {
				$content = 'Sustainable Catalyst platform validation ' . wp_generate_uuid4();
				file_put_contents( $temp_path, $content, LOCK_EX );
				$relative_path = 'quarantine/validation/' . wp_generate_uuid4() . '.qtn';
				$stored = SC_EI_Storage::store_uploaded_file_verified( $temp_path, $relative_path, strlen( $content ), hash( 'sha256', $content ) );
				$file_ok = ! is_wp_error( $stored ) && SC_EI_Storage::verify_integrity( $relative_path, hash( 'sha256', $content ) );
				self::add( $checks, 'private_file_lifecycle', __( 'Private file store, integrity verification, and deletion', 'sustainable-catalyst-engagement-intake' ), $file_ok, $file_ok ? 'temporary private file stored and hash verified' : ( is_wp_error( $stored ) ? $stored->get_error_message() : 'private file verification failed' ) );
			} else {
				self::add( $checks, 'private_file_lifecycle', __( 'Private file store, integrity verification, and deletion', 'sustainable-catalyst-engagement-intake' ), false, 'temporary file creation failed' );
			}
		} catch ( Throwable $error ) {
			self::add( $checks, 'runtime_exception', __( 'Runtime exception boundary', 'sustainable-catalyst-engagement-intake' ), false, get_class( $error ) . ': ' . $error->getMessage() );
		}

		if ( is_email( $mail_recipient ) ) {
			$mail_result = SC_EI_Notification_Service::test_notification( $mail_recipient, $actor_user_id );
			self::add( $checks, 'mail_transport', __( 'WordPress mail transport acceptance', 'sustainable-catalyst-engagement-intake' ), ! is_wp_error( $mail_result ) && ! empty( $mail_result['accepted'] ), is_wp_error( $mail_result ) ? $mail_result->get_error_message() : 'WordPress accepted the test message; inbox delivery must still be confirmed manually' );
		} else {
			self::add( $checks, 'mail_transport', __( 'WordPress mail transport acceptance', 'sustainable-catalyst-engagement-intake' ), false, 'A valid test recipient is required.' );
		}

		if ( $relative_path ) {
			$cleanup_ok = SC_EI_Storage::delete_file( $relative_path ) && $cleanup_ok;
		}
		if ( $temp_path && file_exists( $temp_path ) ) {
			$cleanup_ok = wp_delete_file( $temp_path ) && $cleanup_ok;
		}
		if ( $service_intelligence_finding_id ) {
			$wpdb->delete( SC_EI_Database::table( 'service_intelligence_events' ), array( 'finding_id' => $service_intelligence_finding_id ), array( '%d' ) );
			$wpdb->delete( SC_EI_Database::table( 'service_intelligence_findings' ), array( 'id' => $service_intelligence_finding_id ), array( '%d' ) );
		}
		SC_EI_Service_Intelligence_Repository::cleanup_validation_records();
		if ( $support_signal_id ) {
			$wpdb->delete( SC_EI_Database::table( 'support_signals' ), array( 'id' => $support_signal_id ), array( '%d' ) );
		}
		if ( $meeting_id ) {
			$wpdb->delete( SC_EI_Database::table( 'meeting_reminders' ), array( 'meeting_offer_id' => $meeting_id ), array( '%d' ) );
			$wpdb->delete( SC_EI_Database::table( 'workflow_events' ), array( 'object_type' => 'meeting', 'object_id' => $meeting_id ), array( '%s', '%d' ) );
			$wpdb->delete( SC_EI_Database::table( 'meeting_offers' ), array( 'id' => $meeting_id ), array( '%d' ) );
		}
		if ( $inquiry_id ) {
			SC_EI_Billing_Repository::cleanup_for_inquiry( $inquiry_id );
			SC_EI_Workspace_Repository::cleanup_for_inquiry( $inquiry_id );
			$wpdb->delete( SC_EI_Database::table( 'change_requests' ), array( 'inquiry_id' => $inquiry_id ), array( '%d' ) );
			$wpdb->delete( SC_EI_Database::table( 'proposal_approvals' ), array( 'inquiry_id' => $inquiry_id ), array( '%d' ) );
			$sow_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM " . SC_EI_Database::table( 'statements_of_work' ) . " WHERE inquiry_id = %d", $inquiry_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			foreach ( (array) $sow_ids as $temporary_sow_id ) {
				$wpdb->delete( SC_EI_Database::table( 'statement_of_work_versions' ), array( 'sow_id' => absint( $temporary_sow_id ) ), array( '%d' ) );
			}
			$wpdb->delete( SC_EI_Database::table( 'statements_of_work' ), array( 'inquiry_id' => $inquiry_id ), array( '%d' ) );
			$engagement_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM " . SC_EI_Database::table( 'engagements' ) . " WHERE inquiry_id = %d", $inquiry_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			foreach ( (array) $engagement_ids as $temporary_engagement_id ) {
				$wpdb->delete( SC_EI_Database::table( 'engagement_requirements' ), array( 'engagement_id' => absint( $temporary_engagement_id ) ), array( '%d' ) );
				$wpdb->delete( SC_EI_Database::table( 'engagement_events' ), array( 'engagement_id' => absint( $temporary_engagement_id ) ), array( '%d' ) );
				$wpdb->delete( SC_EI_Database::table( 'engagement_snapshots' ), array( 'engagement_id' => absint( $temporary_engagement_id ) ), array( '%d' ) );
			}
			$wpdb->delete( SC_EI_Database::table( 'engagements' ), array( 'inquiry_id' => $inquiry_id ), array( '%d' ) );
			$proposal_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM " . SC_EI_Database::table( 'proposals' ) . " WHERE inquiry_id = %d", $inquiry_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			foreach ( (array) $proposal_ids as $temporary_proposal_id ) {
				$wpdb->delete( SC_EI_Database::table( 'proposal_versions' ), array( 'proposal_id' => absint( $temporary_proposal_id ) ), array( '%d' ) );
			}
			$wpdb->delete( SC_EI_Database::table( 'proposals' ), array( 'inquiry_id' => $inquiry_id ), array( '%d' ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM " . SC_EI_Database::table( 'workflow_events' ) . " WHERE inquiry_id = %d AND object_type IN ('proposal','sow','change_request','engagement')", $inquiry_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$wpdb->delete( SC_EI_Database::table( 'support_case_links' ), array( 'inquiry_id' => $inquiry_id ), array( '%d' ) );
			$wpdb->delete( SC_EI_Database::table( 'support_case_events' ), array( 'inquiry_id' => $inquiry_id ), array( '%d' ) );
			$wpdb->delete( SC_EI_Database::table( 'support_cases' ), array( 'inquiry_id' => $inquiry_id ), array( '%d' ) );
			$wpdb->delete( SC_EI_Database::table( 'lifecycle_tasks' ), array( 'inquiry_id' => $inquiry_id ), array( '%d' ) );
			$wpdb->delete( SC_EI_Database::table( 'lifecycle_notes' ), array( 'inquiry_id' => $inquiry_id ), array( '%d' ) );
			$wpdb->delete( SC_EI_Database::table( 'lifecycle_events' ), array( 'inquiry_id' => $inquiry_id ), array( '%d' ) );
			if ( $access_id ) {
				$wpdb->delete( SC_EI_Database::table( 'portal_sessions' ), array( 'access_id' => $access_id ), array( '%d' ) );
			}
			$wpdb->delete( SC_EI_Database::table( 'portal_events' ), array( 'inquiry_id' => $inquiry_id ), array( '%d' ) );
			$wpdb->delete( SC_EI_Database::table( 'portal_access' ), array( 'inquiry_id' => $inquiry_id ), array( '%d' ) );
			$wpdb->delete( SC_EI_Database::table( 'audit_log' ), array( 'inquiry_id' => $inquiry_id ), array( '%d' ) );
			$wpdb->delete( SC_EI_Database::table( 'inquiries' ), array( 'id' => $inquiry_id ), array( '%d' ) );
			wp_clear_scheduled_hook( SC_EI_Workflow_Core_Service::SYNC_INQUIRY_HOOK, array( $inquiry_id ) );
			$cleanup_ok = null === SC_EI_Inquiry_Repository::find( $inquiry_id ) && $cleanup_ok;
		}
		self::add( $checks, 'test_cleanup', __( 'Validation test-data cleanup', 'sustainable-catalyst-engagement-intake' ), $cleanup_ok, $cleanup_ok ? 'temporary records and files removed' : 'one or more temporary artifacts may require review' );

		$failures = array_values( array_filter( $checks, static fn( array $check ): bool => 'pass' !== $check['status'] ) );
		$result = array(
			'schema'         => 'sc-contact-engagement-live-validation/2.0',
			'plugin_version' => SC_EI_VERSION,
			'passed'         => empty( $failures ),
			'score'          => $checks ? (int) round( 100 * ( count( $checks ) - count( $failures ) ) / count( $checks ) ) : 0,
			'checks'         => $checks,
			'failure_count'  => count( $failures ),
			'mail_recipient_domain' => self::email_domain( $mail_recipient ),
			'run_by'         => $actor_user_id,
			'completed_at'   => current_time( 'mysql', true ),
			'duration_seconds' => round( microtime( true ) - $started, 4 ),
		);
		$result['content_hash'] = hash( 'sha256', wp_json_encode( $result, JSON_UNESCAPED_SLASHES ) );
		update_option( self::RESULT_OPTION, $result, false );
		SC_EI_Audit_Log::record(
			'platform_live_validation_completed',
			$result['passed'] ? 'Admin-only production live validation passed.' : 'Admin-only production live validation found one or more failures.',
			array(
				'passed'        => $result['passed'],
				'score'         => $result['score'],
				'failure_count' => $result['failure_count'],
				'content_hash'  => $result['content_hash'],
			),
			null,
			null,
			$actor_user_id
		);
		return $result;
	}

	private static function add( array &$checks, string $key, string $label, bool $passed, string $detail ): void {
		$checks[] = array(
			'key'    => sanitize_key( $key ),
			'label'  => sanitize_text_field( $label ),
			'status' => $passed ? 'pass' : 'fail',
			'detail' => mb_substr( sanitize_text_field( $detail ), 0, 1000 ),
		);
	}

	private static function fresh_timestamp( string $timestamp ): bool {
		if ( '' === $timestamp ) {
			return false;
		}
		$unix = strtotime( $timestamp . ' UTC' );
		return false !== $unix && $unix >= time() - self::MAX_AGE_DAYS * DAY_IN_SECONDS;
	}

	private static function email_domain( string $email ): string {
		$parts = explode( '@', sanitize_email( $email ) );
		return count( $parts ) === 2 ? strtolower( $parts[1] ) : '';
	}
}
