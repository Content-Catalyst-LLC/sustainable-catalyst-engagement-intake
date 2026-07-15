<?php
/**
 * Governed proposals, Statements of Work, approvals, engagement conversion, and change requests.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Proposal_Governance_Repository {

	public const MIGRATION_KEY = 'v1_4_0_proposals_statements_of_work_engagement_approvals';
	public const PATCH_MIGRATION_KEY = 'v1_4_1_proposal_versioning_approval_reliability';
	private const BASE_SCHEMA_VERSION = '1.0.0';
	private const OPTION_SCHEMA = 'sc_ei_proposal_governance_schema_version';

	public static function register(): void {
		// Proposal responses are committed directly by the workflow repository so
		// proposal state and immutable approval evidence cannot diverge.
	}

	public static function maybe_upgrade(): void {
		$stored = (string) get_option( self::OPTION_SCHEMA, '' );
		if ( version_compare( $stored, SC_EI_PROPOSAL_SCHEMA_VERSION, '<' ) ) {
			SC_EI_Database::install();
		}
		self::record_migration( $stored );
		self::record_patch_migration( $stored );
		if ( ! in_array( false, SC_EI_Database::proposal_governance_columns_exist(), true ) ) {
			update_option( self::OPTION_SCHEMA, SC_EI_PROPOSAL_SCHEMA_VERSION, false );
		}
	}

	public static function record_migration( string $from_schema = '' ) {
		return self::record_migration_row(
			self::MIGRATION_KEY,
			$from_schema,
			self::BASE_SCHEMA_VERSION,
			'Proposals, Statements of Work, and Engagement Approvals',
			true
		);
	}

	public static function record_patch_migration( string $from_schema = '' ) {
		return self::record_migration_row(
			self::PATCH_MIGRATION_KEY,
			$from_schema,
			SC_EI_PROPOSAL_SCHEMA_VERSION,
			'Proposal Versioning, Approval, and Engagement Conversion Reliability Patch',
			false
		);
	}

	private static function record_migration_row( string $migration_key, string $from_schema, string $to_schema, string $release, bool $database_schema_changed ) {
		global $wpdb;
		$table = SC_EI_Database::table( 'platform_migrations' );
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE migration_key = %s LIMIT 1", $migration_key ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$columns = SC_EI_Database::proposal_governance_columns_exist();
		$ok = ! in_array( false, $columns, true );
		$now = current_time( 'mysql', true );
		$context = array(
			'release'                         => $release,
			'from_schema'                     => $from_schema,
			'to_schema'                       => $to_schema,
			'database_schema_changed'         => $database_schema_changed,
			'no_destructive_migration'        => true,
			'proposal_versions_preserved'     => true,
			'approval_records_immutable'      => true,
			'approval_replay_idempotent'      => ! $database_schema_changed,
			'sow_approval_atomic'             => ! $database_schema_changed,
			'engagement_conversion_repairable'=> ! $database_schema_changed,
			'missing_contract_items'          => array_keys( array_filter( $columns, static fn( bool $value ): bool => ! $value ) ),
		);
		$data = array(
			'public_id'     => $existing['public_id'] ?? wp_generate_uuid4(),
			'migration_key' => $migration_key,
			'from_version'  => $from_schema,
			'to_version'    => $to_schema,
			'status'        => $ok ? 'completed' : 'failed',
			'context_json'  => wp_json_encode( $context, JSON_UNESCAPED_SLASHES ),
			'started_at'    => $existing['started_at'] ?? $now,
			'completed_at'  => $ok ? $now : null,
			'error_code'    => $ok ? '' : 'proposal_governance_schema_incomplete',
			'error_message' => $ok ? '' : 'The proposal-governance database contract is incomplete.',
			'created_at'    => $existing['created_at'] ?? $now,
			'updated_at'    => $now,
		);
		if ( $existing ) {
			$result = $wpdb->update( $table, $data, array( 'id' => absint( $existing['id'] ) ), self::formats( $data ), array( '%d' ) );
			$id = absint( $existing['id'] );
		} else {
			$result = $wpdb->insert( $table, $data, self::formats( $data ) );
			$id = (int) $wpdb->insert_id;
		}
		if ( false === $result ) {
			return new WP_Error( 'proposal_governance_migration_journal_failed', __( 'The proposal-governance migration journal could not be recorded.', 'sustainable-catalyst-engagement-intake' ) );
		}
		SC_EI_Audit_Log::record(
			$ok ? 'proposal_governance_migration_completed' : 'proposal_governance_migration_failed',
			$ok ? $release . ' recorded without destructive conversion.' : $release . ' found an incomplete schema.',
			$context,
			null,
			null,
			get_current_user_id()
		);
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function create_sow_from_proposal( int $proposal_id, array $input, int $actor_user_id ) {
		global $wpdb;
		$proposal = SC_EI_Workflow_Repository::find_proposal( $proposal_id );
		if ( ! $proposal || empty( $proposal['current_version_id'] ) ) {
			return new WP_Error( 'proposal_sow_proposal_unavailable', __( 'A published proposal version is required before creating a Statement of Work.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$existing = self::sow_for_proposal( $proposal_id );
		if ( $existing && ! in_array( (string) $existing['status'], array( 'withdrawn', 'superseded' ), true ) ) {
			return new WP_Error( 'proposal_sow_already_exists', __( 'This proposal already has an active Statement of Work.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		if ( $existing ) {
			$version = self::create_sow_version( absint( $existing['id'] ), $input, $actor_user_id );
			if ( is_wp_error( $version ) ) {
				return $version;
			}
			$data = array(
				'proposal_version_id' => absint( $proposal['current_version_id'] ),
				'current_version_id'  => null,
				'pending_version_id'  => absint( $version['id'] ),
				'status'              => 'draft',
				'sender_visible'      => 0,
				'approved_by'         => null,
				'approved_at'         => null,
				'sender_approved_at'  => null,
				'row_version'         => absint( $existing['row_version'] ) + 1,
				'updated_at'          => $now,
			);
			$updated = $wpdb->update(
				SC_EI_Database::table( 'statements_of_work' ),
				$data,
				array( 'id' => absint( $existing['id'] ), 'row_version' => absint( $existing['row_version'] ), 'status' => (string) $existing['status'] ),
				self::formats( $data, self::sow_integer_fields() ),
				array( '%d', '%d', '%s' )
			);
			if ( 1 !== $updated ) {
				$wpdb->delete( SC_EI_Database::table( 'statement_of_work_versions' ), array( 'id' => absint( $version['id'] ) ), array( '%d' ) );
				return new WP_Error( 'proposal_sow_reactivation_conflict', __( 'The superseded Statement of Work changed before it could be reactivated.', 'sustainable-catalyst-engagement-intake' ) );
			}
			self::event( absint( $proposal['inquiry_id'] ), 'sow', absint( $existing['id'] ), 'sow_reactivated_for_proposal_version', (string) $existing['status'], 'draft', $actor_user_id, array( 'proposal_version_id' => absint( $proposal['current_version_id'] ), 'version_number' => absint( $version['version_number'] ) ) );
			return self::find_sow( absint( $existing['id'] ) );
		}
		$data = array(
			'public_id'          => wp_generate_uuid4(),
			'sow_number'         => 'SOW-TMP-' . strtoupper( substr( str_replace( '-', '', wp_generate_uuid4() ), 0, 16 ) ),
			'inquiry_id'         => absint( $proposal['inquiry_id'] ),
			'proposal_id'        => $proposal_id,
			'proposal_version_id'=> absint( $proposal['current_version_id'] ),
			'current_version_id' => null,
			'pending_version_id' => null,
			'status'             => 'draft',
			'sender_visible'     => 0,
			'approved_by'        => null,
			'approved_at'        => null,
			'sender_approved_at' => null,
			'row_version'        => 0,
			'created_by'         => $actor_user_id,
			'created_at'         => $now,
			'updated_at'         => $now,
		);
		$inserted = $wpdb->insert( SC_EI_Database::table( 'statements_of_work' ), $data, self::formats( $data, self::sow_integer_fields() ) );
		if ( false === $inserted ) {
			return new WP_Error( 'proposal_sow_save_failed', __( 'The Statement of Work could not be created.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$id = (int) $wpdb->insert_id;
		$sow_number = 'SOW-' . gmdate( 'Ym' ) . '-' . str_pad( (string) $id, 5, '0', STR_PAD_LEFT );
		$number_updated = $wpdb->update( SC_EI_Database::table( 'statements_of_work' ), array( 'sow_number' => $sow_number ), array( 'id' => $id ), array( '%s' ), array( '%d' ) );
		if ( 1 !== $number_updated ) {
			$wpdb->delete( SC_EI_Database::table( 'statements_of_work' ), array( 'id' => $id ), array( '%d' ) );
			return new WP_Error( 'proposal_sow_number_failed', __( 'The Statement of Work number could not be finalized.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$version = self::create_sow_version( $id, $input, $actor_user_id );
		if ( is_wp_error( $version ) ) {
			$wpdb->delete( SC_EI_Database::table( 'statements_of_work' ), array( 'id' => $id ), array( '%d' ) );
			return $version;
		}
		$attached = $wpdb->update(
			SC_EI_Database::table( 'statements_of_work' ),
			array( 'pending_version_id' => absint( $version['id'] ), 'updated_at' => $now ),
			array( 'id' => $id, 'row_version' => 0 ),
			array( '%d', '%s' ),
			array( '%d', '%d' )
		);
		if ( 1 !== $attached ) {
			$wpdb->delete( SC_EI_Database::table( 'statement_of_work_versions' ), array( 'id' => absint( $version['id'] ) ), array( '%d' ) );
			$wpdb->delete( SC_EI_Database::table( 'statements_of_work' ), array( 'id' => $id ), array( '%d' ) );
			return new WP_Error( 'proposal_sow_version_attach_failed', __( 'The Statement of Work version could not be attached atomically.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::event( absint( $proposal['inquiry_id'] ), 'sow', $id, 'sow_draft_created', '', 'draft', $actor_user_id, array( 'sow_number' => $sow_number, 'proposal_number' => $proposal['proposal_number'] ) );
		return self::find_sow( $id );
	}

	public static function add_sow_version( int $sow_id, array $input, int $actor_user_id ) {
		global $wpdb;
		$sow = self::find_sow( $sow_id );
		if ( ! $sow ) {
			return new WP_Error( 'proposal_sow_not_found', __( 'The Statement of Work could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( 'sender_approved' === (string) $sow['status'] ) {
			return new WP_Error( 'proposal_sow_immutable', __( 'A sender-approved Statement of Work cannot be edited. Create a governed change request instead.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( ! in_array( (string) $sow['status'], array( 'draft', 'internal_review', 'approved' ), true ) ) {
			return new WP_Error( 'proposal_sow_not_editable', __( 'This Statement of Work cannot receive another version.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$proposal = SC_EI_Workflow_Repository::find_proposal( absint( $sow['proposal_id'] ) );
		if ( ! $proposal || empty( $proposal['current_version_id'] ) ) {
			return new WP_Error( 'proposal_sow_current_proposal_missing', __( 'The current proposal version is unavailable.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$version = self::create_sow_version( $sow_id, $input, $actor_user_id );
		if ( is_wp_error( $version ) ) {
			return $version;
		}
		$data = array(
			'proposal_version_id'=> absint( $proposal['current_version_id'] ),
			'pending_version_id' => absint( $version['id'] ),
			'status'             => 'draft',
			'sender_visible'     => 0,
			'approved_by'        => null,
			'approved_at'        => null,
			'sender_approved_at' => null,
			'row_version'        => absint( $sow['row_version'] ) + 1,
			'updated_at'         => current_time( 'mysql', true ),
		);
		$updated = $wpdb->update(
			SC_EI_Database::table( 'statements_of_work' ),
			$data,
			array( 'id' => $sow_id, 'row_version' => absint( $sow['row_version'] ) ),
			self::formats( $data, self::sow_integer_fields() ),
			array( '%d', '%d' )
		);
		if ( 1 !== $updated ) {
			$wpdb->delete( SC_EI_Database::table( 'statement_of_work_versions' ), array( 'id' => absint( $version['id'] ) ), array( '%d' ) );
			return new WP_Error( 'proposal_sow_conflict', __( 'The Statement of Work changed before the version was attached.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::event( absint( $sow['inquiry_id'] ), 'sow', $sow_id, 'sow_version_created', (string) $sow['status'], 'draft', $actor_user_id, array( 'version_number' => $version['version_number'], 'proposal_version_id' => absint( $proposal['current_version_id'] ) ) );
		return self::find_sow( $sow_id );
	}

	public static function approve_sow( int $sow_id, string $confirmation, int $actor_user_id ) {
		global $wpdb;
		$sow = self::find_sow( $sow_id );
		if ( ! $sow || empty( $sow['pending_version_id'] ) || ! in_array( (string) $sow['status'], array( 'draft', 'internal_review', 'approved' ), true ) ) {
			return new WP_Error( 'proposal_sow_approval_unavailable', __( 'A complete pending Statement of Work version is required.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$proposal = SC_EI_Workflow_Repository::find_proposal( absint( $sow['proposal_id'] ) );
		if ( ! $proposal || absint( $proposal['current_version_id'] ) !== absint( $sow['proposal_version_id'] ) ) {
			return new WP_Error( 'proposal_sow_proposal_version_stale', __( 'Revise this Statement of Work against the current proposal version before approval.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$pending = self::find_sow_version( absint( $sow['pending_version_id'] ) );
		if ( ! $pending || absint( $pending['sow_id'] ) !== $sow_id || empty( $pending['content_hash'] ) ) {
			return new WP_Error( 'proposal_sow_pending_version_invalid', __( 'The pending Statement of Work version failed its integrity check.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$expected = 'APPROVE ' . strtoupper( (string) $sow['sow_number'] );
		if ( ! hash_equals( $expected, strtoupper( trim( sanitize_text_field( $confirmation ) ) ) ) ) {
			return new WP_Error( 'proposal_sow_confirmation_failed', __( 'The Statement of Work confirmation did not match.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		$data = array(
			'current_version_id' => absint( $sow['pending_version_id'] ),
			'pending_version_id' => null,
			'status'             => 'approved',
			'sender_visible'     => 1,
			'approved_by'        => $actor_user_id,
			'approved_at'        => $now,
			'sender_approved_at' => null,
			'row_version'        => absint( $sow['row_version'] ) + 1,
			'updated_at'         => $now,
		);
		$updated = $wpdb->update(
			SC_EI_Database::table( 'statements_of_work' ),
			$data,
			array( 'id' => $sow_id, 'row_version' => absint( $sow['row_version'] ) ),
			self::formats( $data, self::sow_integer_fields() ),
			array( '%d', '%d' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'proposal_sow_conflict', __( 'The Statement of Work changed before approval was saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::event( absint( $sow['inquiry_id'] ), 'sow', $sow_id, 'sow_approved_for_sender', (string) $sow['status'], 'approved', $actor_user_id, array( 'version_id' => absint( $sow['pending_version_id'] ), 'content_hash' => (string) $pending['content_hash'] ) );
		return self::find_sow( $sow_id, true );
	}

	public static function action_for_workflow_response( string $response ): string {
		$map = array(
			'confirm_receipt' => 'receipt_confirmed',
			'request_changes' => 'changes_requested',
			'accept'          => 'proposal_accepted',
			'decline'         => 'proposal_declined',
		);
		return $map[ sanitize_key( $response ) ] ?? '';
	}

	public static function record_sender_action( int $proposal_id, int $proposal_version_id, string $action, string $note, bool $authority_attested, bool $boundary_acknowledged, string $confirmation, int $session_id, int $sow_id = 0 ) {
		global $wpdb;
		$proposal = SC_EI_Workflow_Repository::find_proposal( $proposal_id, true );
		if ( ! $proposal || absint( $proposal['current_version_id'] ) !== $proposal_version_id ) {
			return new WP_Error( 'proposal_approval_version_stale', __( 'Only the current published proposal version can receive a sender decision.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$action = sanitize_key( $action );
		if ( ! isset( SC_EI_Proposal_Governance_Schema::approval_actions()[ $action ] ) || ! in_array( $action, array( 'receipt_confirmed', 'changes_requested', 'proposal_accepted', 'proposal_declined', 'sow_approved' ), true ) ) {
			return new WP_Error( 'proposal_approval_action_invalid', __( 'Choose a valid sender proposal action.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( $session_id < 1 ) {
			return new WP_Error( 'proposal_approval_session_required', __( 'A valid Sender Portal session is required.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$note = sanitize_textarea_field( $note );
		$confirmation = strtoupper( trim( sanitize_text_field( $confirmation ) ) );
		$sow = null;
		if ( 'sow_approved' === $action ) {
			$sow = self::find_sow( $sow_id, true );
			if (
				! $sow
				|| absint( $sow['proposal_id'] ) !== $proposal_id
				|| absint( $sow['inquiry_id'] ) !== absint( $proposal['inquiry_id'] )
				|| absint( $sow['proposal_version_id'] ) !== $proposal_version_id
				|| ! in_array( (string) $sow['status'], array( 'approved', 'sender_approved' ), true )
				|| empty( $sow['current_version_id'] )
			) {
				return new WP_Error( 'proposal_sow_not_available', __( 'The approved Statement of Work is not available for the current proposal version.', 'sustainable-catalyst-engagement-intake' ) );
			}
		}
		$expected = self::expected_sender_confirmation( $proposal, $action, $sow );
		if ( '' === $expected || ! hash_equals( $expected, $confirmation ) ) {
			return new WP_Error( 'proposal_approval_confirmation_failed', __( 'The proposal or Statement of Work confirmation did not match.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( in_array( $action, array( 'proposal_accepted', 'sow_approved' ), true ) && ( ! $authority_attested || ! $boundary_acknowledged ) ) {
			return new WP_Error( 'proposal_approval_attestation_required', __( 'Authority and workflow-boundary acknowledgments are required for this action.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( 'changes_requested' === $action && mb_strlen( trim( $note ) ) < 5 ) {
			return new WP_Error( 'proposal_approval_note_required', __( 'Describe the requested proposal changes.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( 'proposal_declined' === $action && mb_strlen( trim( $note ) ) < 3 ) {
			return new WP_Error( 'proposal_approval_note_required', __( 'Add a brief decline note.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$confirmation_hash = hash( 'sha256', $confirmation );
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM " . SC_EI_Database::table( 'proposal_approvals' ) . " WHERE proposal_id = %d AND proposal_version_id = %d AND action = %s AND actor_type = 'sender' AND COALESCE(sow_id,0) = %d LIMIT 1",
				$proposal_id,
				$proposal_version_id,
				$action,
				$sow_id
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( $existing ) {
			$matching = absint( $existing['actor_id'] ) === $session_id
				&& (string) $existing['note'] === $note
				&& absint( $existing['authority_attested'] ) === ( $authority_attested ? 1 : 0 )
				&& absint( $existing['boundary_acknowledged'] ) === ( $boundary_acknowledged ? 1 : 0 )
				&& hash_equals( (string) $existing['confirmation_hash'], $confirmation_hash )
				&& self::verify_approval_integrity( $existing );
			if ( $matching ) {
				$existing['_idempotent'] = true;
				return $existing;
			}
			return new WP_Error( 'proposal_approval_replay_conflict', __( 'A different action receipt already exists for this proposal version.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( 'sow_approved' === $action && $sow && 'approved' !== (string) $sow['status'] ) {
			return new WP_Error( 'proposal_sow_approval_receipt_missing', __( 'The Statement of Work is marked sender-approved, but its immutable approval receipt is missing or does not match. Repair the approval evidence before retrying.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$payload = array(
			'schema'                => SC_EI_Proposal_Governance_Schema::APPROVAL_SCHEMA,
			'inquiry_id'            => absint( $proposal['inquiry_id'] ),
			'proposal_id'           => $proposal_id,
			'proposal_version_id'   => $proposal_version_id,
			'sow_id'                => $sow_id ?: null,
			'action'                => $action,
			'actor_type'            => 'sender',
			'actor_id'              => $session_id,
			'note'                  => $note,
			'authority_attested'    => $authority_attested ? 1 : 0,
			'boundary_acknowledged' => $boundary_acknowledged ? 1 : 0,
			'confirmation_hash'     => $confirmation_hash,
			'created_at'            => current_time( 'mysql', true ),
		);
		$payload['immutable_hash'] = SC_EI_Proposal_Governance_Schema::canonical_hash( $payload );
		$approval_data = array_merge( array( 'public_id' => wp_generate_uuid4() ), $payload );
		$wpdb->query( 'START TRANSACTION' );
		$inserted = $wpdb->insert( SC_EI_Database::table( 'proposal_approvals' ), $approval_data, self::formats( $approval_data, self::approval_integer_fields() ) );
		if ( false === $inserted ) {
			$wpdb->query( 'ROLLBACK' );
			self::record_reliability_failure( 'proposal_approval_insert_failed', $proposal_id, array( 'action' => $action, 'proposal_version_id' => $proposal_version_id ) );
			return new WP_Error( 'proposal_approval_save_failed', __( 'The immutable proposal action could not be recorded.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$id = (int) $wpdb->insert_id;
		if ( 'sow_approved' === $action && $sow ) {
			$now = current_time( 'mysql', true );
			$updated = $wpdb->update(
				SC_EI_Database::table( 'statements_of_work' ),
				array( 'status' => 'sender_approved', 'sender_approved_at' => $now, 'row_version' => absint( $sow['row_version'] ) + 1, 'updated_at' => $now ),
				array( 'id' => $sow_id, 'row_version' => absint( $sow['row_version'] ), 'status' => 'approved', 'proposal_version_id' => $proposal_version_id ),
				array( '%s', '%s', '%d', '%s' ),
				array( '%d', '%d', '%s', '%d' )
			);
			if ( 1 !== $updated ) {
				$wpdb->delete( SC_EI_Database::table( 'proposal_approvals' ), array( 'id' => $id ), array( '%d' ) );
				$wpdb->query( 'ROLLBACK' );
				self::record_reliability_failure( 'proposal_sow_sender_approval_transition_failed', $proposal_id, array( 'sow_id' => $sow_id, 'approval_id' => $id ) );
				return new WP_Error( 'proposal_sow_approval_conflict', __( 'The Statement of Work changed before sender approval was committed.', 'sustainable-catalyst-engagement-intake' ) );
			}
		}
		$wpdb->query( 'COMMIT' );
		self::event( absint( $proposal['inquiry_id'] ), 'proposal', $proposal_id, 'proposal_approval_recorded', (string) $proposal['status'], (string) $proposal['status'], 0, array( 'approval_id' => $id, 'action' => $action, 'proposal_version_id' => $proposal_version_id ) );
		SC_EI_Audit_Log::record( 'proposal_approval_recorded', 'An immutable sender proposal or Statement of Work action was recorded.', array( 'approval_id' => $id, 'proposal_id' => $proposal_id, 'proposal_version_id' => $proposal_version_id, 'action' => $action, 'automatic_contract' => false, 'automatic_payment' => false ), absint( $proposal['inquiry_id'] ) );
		$result = self::find_approval( $id );
		if ( $result ) {
			$result['_idempotent'] = false;
		}
		return $result ?: new WP_Error( 'proposal_approval_readback_failed', __( 'The proposal action was recorded but could not be read back.', 'sustainable-catalyst-engagement-intake' ) );
	}

	public static function capture_workflow_sender_response( array $proposal, array $context ): void {
		// Retained as a no-op compatibility boundary. v1.4.1 commits immutable
		// approval evidence synchronously inside respond_to_proposal().
	}

	public static function validate_proposal_publication( array $proposal, int $pending_version_id ) {
		$sow = self::sow_for_proposal( absint( $proposal['id'] ) );
		if ( ! $sow || absint( $sow['proposal_version_id'] ) === $pending_version_id ) {
			return true;
		}
		if ( 'sender_approved' === (string) $sow['status'] ) {
			return new WP_Error( 'proposal_publish_sender_approved_sow_locked', __( 'A sender-approved Statement of Work is tied to the current proposal version. Use governed change control or create a new proposal instead of overwriting that agreement boundary.', 'sustainable-catalyst-engagement-intake' ) );
		}
		return true;
	}

	public static function reconcile_after_proposal_publication( int $proposal_id, int $published_version_id, int $actor_user_id ) {
		global $wpdb;
		$sow = self::sow_for_proposal( $proposal_id );
		if ( ! $sow || absint( $sow['proposal_version_id'] ) === $published_version_id ) {
			return true;
		}
		if ( 'sender_approved' === (string) $sow['status'] ) {
			return new WP_Error( 'proposal_publish_sender_approved_sow_locked', __( 'The sender-approved Statement of Work cannot be superseded by a proposal revision.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		$data = array(
			'status'             => 'superseded',
			'sender_visible'     => 0,
			'approved_by'        => null,
			'approved_at'        => null,
			'sender_approved_at' => null,
			'row_version'        => absint( $sow['row_version'] ) + 1,
			'updated_at'         => $now,
		);
		$updated = $wpdb->update(
			SC_EI_Database::table( 'statements_of_work' ),
			$data,
			array( 'id' => absint( $sow['id'] ), 'row_version' => absint( $sow['row_version'] ) ),
			self::formats( $data, self::sow_integer_fields() ),
			array( '%d', '%d' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'proposal_publish_sow_reconcile_failed', __( 'The prior Statement of Work could not be safely superseded.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::event( absint( $sow['inquiry_id'] ), 'sow', absint( $sow['id'] ), 'sow_superseded_by_proposal_revision', (string) $sow['status'], 'superseded', $actor_user_id, array( 'previous_proposal_version_id' => absint( $sow['proposal_version_id'] ), 'published_proposal_version_id' => $published_version_id ) );
		return true;
	}

	public static function create_change_request( int $inquiry_id, array $input, string $requester_type, int $requester_id ) {
		global $wpdb;
		if ( ! SC_EI_Inquiry_Repository::find( $inquiry_id ) ) {
			return new WP_Error( 'proposal_change_inquiry_missing', __( 'The related inquiry could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$summary = sanitize_textarea_field( (string) ( $input['request_summary'] ?? '' ) );
		$reason = sanitize_textarea_field( (string) ( $input['reason'] ?? '' ) );
		if ( mb_strlen( trim( $summary ) ) < 5 || mb_strlen( trim( $reason ) ) < 5 ) {
			return new WP_Error( 'proposal_change_content_required', __( 'Describe the requested change and why it is needed.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		$data = array(
			'public_id'            => wp_generate_uuid4(),
			'change_number'        => 'CHG-TMP-' . strtoupper( substr( str_replace( '-', '', wp_generate_uuid4() ), 0, 16 ) ),
			'inquiry_id'           => $inquiry_id,
			'proposal_id'          => absint( $input['proposal_id'] ?? 0 ) ?: null,
			'proposal_version_id'  => absint( $input['proposal_version_id'] ?? 0 ) ?: null,
			'sow_id'               => absint( $input['sow_id'] ?? 0 ) ?: null,
			'sow_version_id'       => absint( $input['sow_version_id'] ?? 0 ) ?: null,
			'engagement_id'        => absint( $input['engagement_id'] ?? 0 ) ?: null,
			'status'               => 'requested',
			'requester_type'       => in_array( $requester_type, array( 'sender', 'staff' ), true ) ? $requester_type : 'staff',
			'requester_id'         => $requester_id,
			'request_summary'      => $summary,
			'reason'               => $reason,
			'scope_impact'         => sanitize_textarea_field( (string) ( $input['scope_impact'] ?? '' ) ),
			'timeline_impact'      => sanitize_textarea_field( (string) ( $input['timeline_impact'] ?? '' ) ),
			'fee_impact_minor'     => SC_EI_Proposal_Governance_Schema::money_minor( $input['fee_impact'] ?? 0 ),
			'currency'             => SC_EI_Workflow_Schema::sanitize_currency( (string) ( $input['currency'] ?? 'USD' ) ),
			'decision_note'        => '',
			'decided_by'           => null,
			'decided_at'           => null,
			'applied_by'           => null,
			'applied_at'           => null,
			'row_version'          => 0,
			'created_at'           => $now,
			'updated_at'           => $now,
		);
		$inserted = $wpdb->insert( SC_EI_Database::table( 'change_requests' ), $data, self::formats( $data, self::change_integer_fields() ) );
		if ( false === $inserted ) {
			return new WP_Error( 'proposal_change_save_failed', __( 'The change request could not be created.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$id = (int) $wpdb->insert_id;
		$number = 'CHG-' . gmdate( 'Ym' ) . '-' . str_pad( (string) $id, 5, '0', STR_PAD_LEFT );
		$wpdb->update( SC_EI_Database::table( 'change_requests' ), array( 'change_number' => $number ), array( 'id' => $id ), array( '%s' ), array( '%d' ) );
		self::event( $inquiry_id, 'change_request', $id, 'change_request_created', '', 'requested', $requester_type === 'staff' ? $requester_id : 0, array( 'change_number' => $number, 'requester_type' => $requester_type ) );
		return self::find_change_request( $id );
	}

	public static function transition_change_request( int $id, string $status, string $note, string $confirmation, int $actor_user_id ) {
		global $wpdb;
		$request = self::find_change_request( $id );
		if ( ! $request ) {
			return new WP_Error( 'proposal_change_not_found', __( 'The change request could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$status = SC_EI_Proposal_Governance_Schema::sanitize_status( $status, SC_EI_Proposal_Governance_Schema::change_statuses(), 'requested' );
		$allowed = array(
			'requested'    => array( 'under_review', 'withdrawn' ),
			'under_review' => array( 'approved', 'declined', 'withdrawn' ),
			'approved'     => array( 'applied', 'withdrawn' ),
			'declined'     => array(),
			'applied'      => array(),
			'withdrawn'    => array(),
		);
		if ( ! in_array( $status, $allowed[ $request['status'] ] ?? array(), true ) ) {
			return new WP_Error( 'proposal_change_transition_invalid', __( 'That change-request transition is not allowed.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$expected = strtoupper( $status . ' ' . $request['change_number'] );
		if ( ! hash_equals( $expected, strtoupper( trim( sanitize_text_field( $confirmation ) ) ) ) ) {
			return new WP_Error( 'proposal_change_confirmation_failed', __( 'The change-request confirmation did not match.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$note = sanitize_textarea_field( $note );
		if ( in_array( $status, array( 'approved', 'declined', 'applied', 'withdrawn' ), true ) && mb_strlen( trim( $note ) ) < 3 ) {
			return new WP_Error( 'proposal_change_note_required', __( 'Record a brief decision or application note.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		$data = array(
			'status'        => $status,
			'decision_note' => in_array( $status, array( 'approved', 'declined', 'withdrawn' ), true ) ? $note : (string) $request['decision_note'],
			'decided_by'    => in_array( $status, array( 'approved', 'declined', 'withdrawn' ), true ) ? $actor_user_id : $request['decided_by'],
			'decided_at'    => in_array( $status, array( 'approved', 'declined', 'withdrawn' ), true ) ? $now : $request['decided_at'],
			'applied_by'    => 'applied' === $status ? $actor_user_id : $request['applied_by'],
			'applied_at'    => 'applied' === $status ? $now : $request['applied_at'],
			'row_version'   => absint( $request['row_version'] ) + 1,
			'updated_at'    => $now,
		);
		$updated = $wpdb->update( SC_EI_Database::table( 'change_requests' ), $data, array( 'id' => $id, 'row_version' => absint( $request['row_version'] ) ), self::formats( $data, self::change_integer_fields() ), array( '%d', '%d' ) );
		if ( 1 !== $updated ) {
			return new WP_Error( 'proposal_change_conflict', __( 'The change request changed before the transition was saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::event( absint( $request['inquiry_id'] ), 'change_request', $id, 'change_request_' . $status, (string) $request['status'], $status, $actor_user_id, array( 'note' => $note ) );
		return self::find_change_request( $id );
	}

	public static function convert_to_engagement( int $proposal_id, array $input, string $confirmation, int $actor_user_id ) {
		global $wpdb;
		$proposal = SC_EI_Workflow_Repository::find_proposal( $proposal_id );
		if ( ! $proposal || ! in_array( (string) $proposal['status'], array( 'contracted', 'converted_to_engagement' ), true ) ) {
			return new WP_Error( 'proposal_conversion_contract_required', __( 'An accepted proposal with a recorded external contract is required before engagement conversion.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$sow = self::sow_for_proposal( $proposal_id, true );
		if (
			! $sow
			|| 'sender_approved' !== (string) $sow['status']
			|| absint( $sow['proposal_version_id'] ) !== absint( $proposal['current_version_id'] )
			|| empty( $sow['current_version_id'] )
		) {
			return new WP_Error( 'proposal_conversion_sow_required', __( 'A sender-approved Statement of Work for the current proposal version is required before engagement conversion.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$expected = 'CONVERT ' . strtoupper( (string) $proposal['proposal_number'] );
		if ( ! hash_equals( $expected, strtoupper( trim( sanitize_text_field( $confirmation ) ) ) ) ) {
			return new WP_Error( 'proposal_conversion_confirmation_failed', __( 'The engagement-conversion confirmation did not match.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$existing = SC_EI_Engagement_Repository::for_inquiry( absint( $proposal['inquiry_id'] ), false );
		foreach ( $existing as $engagement ) {
			if ( absint( $engagement['proposal_id'] ?? 0 ) === $proposal_id ) {
				$receipt = self::ensure_conversion_receipt( $proposal, $sow, $actor_user_id );
				if ( is_wp_error( $receipt ) ) {
					return new WP_Error( 'proposal_conversion_receipt_failed', __( 'The engagement exists, but its conversion receipt could not be repaired.', 'sustainable-catalyst-engagement-intake' ), array( 'engagement_id' => absint( $engagement['id'] ), 'cause' => $receipt->get_error_code() ) );
				}
				$status = self::mark_proposal_converted( $proposal );
				if ( is_wp_error( $status ) ) {
					return new WP_Error( 'proposal_conversion_status_failed', __( 'The engagement exists, but the proposal conversion status could not be repaired.', 'sustainable-catalyst-engagement-intake' ), array( 'engagement_id' => absint( $engagement['id'] ), 'cause' => $status->get_error_code() ) );
				}
				return array( 'engagement' => $engagement, 'approval' => $receipt, 'idempotent' => true );
			}
		}
		if ( 'contracted' !== (string) $proposal['status'] ) {
			return new WP_Error( 'proposal_conversion_engagement_missing', __( 'The proposal is marked converted, but its engagement record could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$result = SC_EI_Engagement_Repository::create_from_contracted_proposal( $proposal_id, $input, $actor_user_id );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$receipt = self::ensure_conversion_receipt( $proposal, $sow, $actor_user_id );
		if ( is_wp_error( $receipt ) ) {
			self::record_reliability_failure( 'proposal_conversion_receipt_failed', $proposal_id, array( 'engagement_id' => absint( $result['id'] ?? 0 ) ) );
			return new WP_Error( 'proposal_conversion_receipt_failed', __( 'The engagement was created, but its immutable conversion receipt requires repair. Retry the conversion action; no duplicate engagement will be created.', 'sustainable-catalyst-engagement-intake' ), array( 'engagement_id' => absint( $result['id'] ?? 0 ), 'cause' => $receipt->get_error_code() ) );
		}
		$status = self::mark_proposal_converted( $proposal );
		if ( is_wp_error( $status ) ) {
			self::record_reliability_failure( 'proposal_conversion_status_failed', $proposal_id, array( 'engagement_id' => absint( $result['id'] ?? 0 ) ) );
			return new WP_Error( 'proposal_conversion_status_failed', __( 'The engagement was created, but the proposal status requires repair. Retry the conversion action; no duplicate engagement will be created.', 'sustainable-catalyst-engagement-intake' ), array( 'engagement_id' => absint( $result['id'] ?? 0 ), 'cause' => $status->get_error_code() ) );
		}
		self::event( absint( $proposal['inquiry_id'] ), 'proposal', $proposal_id, 'proposal_converted_to_engagement', 'contracted', 'converted_to_engagement', $actor_user_id, array( 'engagement_id' => absint( $result['id'] ?? 0 ), 'approval_id' => absint( $receipt['id'] ?? 0 ) ) );
		return array( 'engagement' => $result, 'approval' => $receipt, 'idempotent' => false );
	}

	public static function sender_snapshot( int $inquiry_id ): array {
		$sows = self::sows_for_inquiry( $inquiry_id, true );
		$result = array();
		$allowed = array_flip( SC_EI_Proposal_Governance_Schema::sender_projection_keys() );
		foreach ( $sows as $sow ) {
			$result[] = array_intersect_key( $sow, $allowed );
		}
		return $result;
	}

	public static function metrics(): array {
		global $wpdb;
		$sows = SC_EI_Database::table( 'statements_of_work' );
		$approvals = SC_EI_Database::table( 'proposal_approvals' );
		$changes = SC_EI_Database::table( 'change_requests' );
		$metrics = array(
			'draft_sows'             => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$sows} WHERE status IN ('draft','internal_review')" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'approved_sows'          => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$sows} WHERE status = 'approved'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'sender_approved_sows'   => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$sows} WHERE status = 'sender_approved'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'immutable_approvals'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$approvals}" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'open_change_requests'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$changes} WHERE status IN ('requested','under_review','approved')" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'approved_not_applied'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$changes} WHERE status = 'approved'" ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
		return array_merge( $metrics, self::consistency_metrics() );
	}

	public static function consistency_metrics(): array {
		global $wpdb;
		$approvals_table = SC_EI_Database::table( 'proposal_approvals' );
		$proposals_table = SC_EI_Database::table( 'proposals' );
		$sows_table = SC_EI_Database::table( 'statements_of_work' );
		$engagements_table = SC_EI_Database::table( 'engagements' );
		$invalid_hashes = 0;
		foreach ( (array) $wpdb->get_results( "SELECT * FROM {$approvals_table}", ARRAY_A ) as $approval ) { // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( ! self::verify_approval_integrity( $approval ) ) {
				$invalid_hashes++;
			}
		}
		$missing_sender_receipts = 0;
		$proposal_actions = array(
			'confirm_receipt' => 'receipt_confirmed',
			'request_changes' => 'changes_requested',
			'accept'          => 'proposal_accepted',
			'decline'         => 'proposal_declined',
		);
		$responded = (array) $wpdb->get_results( "SELECT id, current_version_id, sender_response FROM {$proposals_table} WHERE sender_response <> '' AND current_version_id IS NOT NULL", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		foreach ( $responded as $proposal ) {
			$action = $proposal_actions[ (string) $proposal['sender_response'] ] ?? '';
			if ( '' === $action ) {
				continue;
			}
			$found = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$approvals_table} WHERE proposal_id = %d AND proposal_version_id = %d AND action = %s AND actor_type = 'sender' LIMIT 1", absint( $proposal['id'] ), absint( $proposal['current_version_id'] ), $action ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( ! $found ) {
				$missing_sender_receipts++;
			}
		}
		$missing_sow_receipts = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$sows_table} s LEFT JOIN {$approvals_table} a ON a.sow_id = s.id AND a.proposal_version_id = s.proposal_version_id AND a.action = 'sow_approved' AND a.actor_type = 'sender' WHERE s.status = 'sender_approved' AND a.id IS NULL"
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$stale_active_sows = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$sows_table} s INNER JOIN {$proposals_table} p ON p.id = s.proposal_id WHERE s.status IN ('approved','sender_approved') AND s.proposal_version_id <> p.current_version_id"
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$missing_conversion_receipts = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$engagements_table} e LEFT JOIN {$approvals_table} a ON a.proposal_id = e.proposal_id AND a.proposal_version_id = e.proposal_version_id AND a.action = 'engagement_converted' AND a.actor_type = 'staff' WHERE a.id IS NULL"
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$converted_status_mismatch = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$engagements_table} e INNER JOIN {$proposals_table} p ON p.id = e.proposal_id WHERE p.status <> 'converted_to_engagement'"
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return array(
			'invalid_approval_hashes'       => $invalid_hashes,
			'missing_sender_receipts'       => $missing_sender_receipts,
			'missing_sow_receipts'          => $missing_sow_receipts,
			'stale_active_sows'             => $stale_active_sows,
			'missing_conversion_receipts'   => $missing_conversion_receipts,
			'converted_status_mismatch'     => $converted_status_mismatch,
		);
	}

	public static function operational_blockers(): array {
		$metrics = self::metrics();
		return array(
			'approved_change_requests_not_applied' => absint( $metrics['approved_not_applied'] ?? 0 ),
			'invalid_approval_hashes'               => absint( $metrics['invalid_approval_hashes'] ?? 0 ),
			'missing_sender_receipts'               => absint( $metrics['missing_sender_receipts'] ?? 0 ),
			'missing_sow_receipts'                  => absint( $metrics['missing_sow_receipts'] ?? 0 ),
			'stale_active_sows'                     => absint( $metrics['stale_active_sows'] ?? 0 ),
			'missing_conversion_receipts'           => absint( $metrics['missing_conversion_receipts'] ?? 0 ),
			'converted_status_mismatch'             => absint( $metrics['converted_status_mismatch'] ?? 0 ),
		);
	}

	public static function export_for_inquiry( int $inquiry_id ): array {
		return array(
			'schema'          => 'sc-proposal-governance-export/1.0',
			'statements_of_work' => self::sows_for_inquiry( $inquiry_id, false ),
			'change_requests' => self::change_requests_for_inquiry( $inquiry_id ),
			'approvals'       => self::approvals_for_inquiry( $inquiry_id ),
		);
	}

	public static function find_sow( int $id, bool $sender_visible = false ): ?array {
		global $wpdb;
		$sow_table = SC_EI_Database::table( 'statements_of_work' );
		$version_table = SC_EI_Database::table( 'statement_of_work_versions' );
		$version_join = $sender_visible ? 's.current_version_id' : 'COALESCE(s.pending_version_id, s.current_version_id)';
		$where = $sender_visible ? "AND s.sender_visible = 1 AND s.status IN ('approved','sender_approved')" : '';
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT s.*, p.proposal_number, v.version_number, v.title, v.purpose_background, v.scope_json, v.deliverables_json, v.milestones_json, v.responsibilities_json, v.dependencies_json, v.acceptance_criteria, v.change_control, v.communication_expectations, v.data_handling, v.ip_terms, v.open_source_boundaries, v.fees_payment, v.start_date, v.target_end_date, v.termination_conditions, v.attachment_ids_json, v.version_note, v.content_hash
				FROM {$sow_table} s
				LEFT JOIN " . SC_EI_Database::table( 'proposals' ) . " p ON p.id = s.proposal_id
				LEFT JOIN {$version_table} v ON v.id = {$version_join}
				WHERE s.id = %d {$where}",
				$id
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $row ?: null;
	}

	public static function find_sow_version( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . SC_EI_Database::table( 'statement_of_work_versions' ) . " WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $row ?: null;
	}

	public static function sow_for_proposal( int $proposal_id, bool $sender_visible = false ): ?array {
		global $wpdb;
		$id = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM " . SC_EI_Database::table( 'statements_of_work' ) . " WHERE proposal_id = %d ORDER BY id DESC LIMIT 1", $proposal_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $id ? self::find_sow( $id, $sender_visible ) : null;
	}

	public static function sows_for_inquiry( int $inquiry_id, bool $sender_visible = false ): array {
		global $wpdb;
		$table = SC_EI_Database::table( 'statements_of_work' );
		$where = $sender_visible ? "AND sender_visible = 1 AND status IN ('approved','sender_approved')" : '';
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$table} WHERE inquiry_id = %d {$where} ORDER BY id DESC", $inquiry_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return array_values( array_filter( array_map( static fn( $id ) => self::find_sow( absint( $id ), $sender_visible ), (array) $ids ) ) );
	}

	public static function find_approval( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . SC_EI_Database::table( 'proposal_approvals' ) . " WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $row ?: null;
	}

	public static function approvals_for_inquiry( int $inquiry_id ): array {
		global $wpdb;
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . SC_EI_Database::table( 'proposal_approvals' ) . " WHERE inquiry_id = %d ORDER BY created_at DESC, id DESC", $inquiry_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	public static function find_change_request( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . SC_EI_Database::table( 'change_requests' ) . " WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $row ?: null;
	}

	public static function change_requests_for_inquiry( int $inquiry_id ): array {
		global $wpdb;
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . SC_EI_Database::table( 'change_requests' ) . " WHERE inquiry_id = %d ORDER BY created_at DESC, id DESC", $inquiry_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	private static function create_sow_version( int $sow_id, array $input, int $actor_user_id ) {
		global $wpdb;
		$title = sanitize_text_field( (string) ( $input['title'] ?? '' ) );
		$scope = SC_EI_Proposal_Governance_Schema::sanitize_lines( $input['scope'] ?? '' );
		$deliverables = SC_EI_Proposal_Governance_Schema::sanitize_lines( $input['deliverables'] ?? '' );
		$acceptance = sanitize_textarea_field( (string) ( $input['acceptance_criteria'] ?? '' ) );
		if ( '' === trim( $title ) || ! $scope || ! $deliverables || '' === trim( $acceptance ) ) {
			return new WP_Error( 'proposal_sow_content_required', __( 'Statement of Work title, scope, deliverables, and acceptance criteria are required.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$start_date = self::date_or_null( $input['start_date'] ?? '' );
		$target_end_date = self::date_or_null( $input['target_end_date'] ?? '' );
		if ( ! empty( $input['start_date'] ) && null === $start_date ) {
			return new WP_Error( 'proposal_sow_start_date_invalid', __( 'Enter a valid Statement of Work start date.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( ! empty( $input['target_end_date'] ) && null === $target_end_date ) {
			return new WP_Error( 'proposal_sow_target_date_invalid', __( 'Enter a valid Statement of Work target completion date.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( $start_date && $target_end_date && $target_end_date < $start_date ) {
			return new WP_Error( 'proposal_sow_date_order_invalid', __( 'The Statement of Work target completion date cannot precede its start date.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$payload = array(
			'title'                      => $title,
			'purpose_background'         => sanitize_textarea_field( (string) ( $input['purpose_background'] ?? '' ) ),
			'scope_json'                 => wp_json_encode( $scope ),
			'deliverables_json'          => wp_json_encode( $deliverables ),
			'milestones_json'            => wp_json_encode( SC_EI_Proposal_Governance_Schema::sanitize_lines( $input['milestones'] ?? '' ) ),
			'responsibilities_json'       => wp_json_encode( SC_EI_Proposal_Governance_Schema::sanitize_lines( $input['responsibilities'] ?? '' ) ),
			'dependencies_json'           => wp_json_encode( SC_EI_Proposal_Governance_Schema::sanitize_lines( $input['dependencies'] ?? '' ) ),
			'acceptance_criteria'         => $acceptance,
			'change_control'              => sanitize_textarea_field( (string) ( $input['change_control'] ?? '' ) ),
			'communication_expectations'  => sanitize_textarea_field( (string) ( $input['communication_expectations'] ?? '' ) ),
			'data_handling'               => sanitize_textarea_field( (string) ( $input['data_handling'] ?? '' ) ),
			'ip_terms'                    => sanitize_textarea_field( (string) ( $input['ip_terms'] ?? '' ) ),
			'open_source_boundaries'      => sanitize_textarea_field( (string) ( $input['open_source_boundaries'] ?? '' ) ),
			'fees_payment'                => sanitize_textarea_field( (string) ( $input['fees_payment'] ?? '' ) ),
			'start_date'                  => $start_date,
			'target_end_date'             => $target_end_date,
			'termination_conditions'      => sanitize_textarea_field( (string) ( $input['termination_conditions'] ?? '' ) ),
			'attachment_ids_json'         => wp_json_encode( array_values( array_filter( array_map( 'absint', (array) ( $input['attachment_ids'] ?? array() ) ) ) ) ),
			'version_note'                => sanitize_textarea_field( (string) ( $input['version_note'] ?? '' ) ),
		);
		$table = SC_EI_Database::table( 'statement_of_work_versions' );
		for ( $attempt = 1; $attempt <= 3; $attempt++ ) {
			$number = 1 + (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(MAX(version_number),0) FROM {$table} WHERE sow_id = %d", $sow_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$data = array_merge(
				array( 'public_id' => wp_generate_uuid4(), 'sow_id' => $sow_id, 'version_number' => $number ),
				$payload,
				array( 'content_hash' => SC_EI_Proposal_Governance_Schema::canonical_hash( $payload ), 'created_by' => $actor_user_id, 'created_at' => current_time( 'mysql', true ) )
			);
			if ( false !== $wpdb->insert( $table, $data, self::formats( $data, array( 'sow_id', 'version_number', 'created_by' ) ) ) ) {
				return self::find_sow_version( (int) $wpdb->insert_id );
			}
		}
		self::record_reliability_failure( 'proposal_sow_version_insert_failed', 0, array( 'sow_id' => $sow_id, 'attempts' => 3 ) );
		return new WP_Error( 'proposal_sow_version_save_failed', __( 'The Statement of Work version could not be saved after bounded retry.', 'sustainable-catalyst-engagement-intake' ) );
	}

	private static function expected_sender_confirmation( array $proposal, string $action, ?array $sow = null ): string {
		$number = strtoupper( (string) ( $proposal['proposal_number'] ?? '' ) );
		$map = array(
			'receipt_confirmed' => 'CONFIRM ' . $number,
			'changes_requested' => 'REQUEST CHANGES ' . $number,
			'proposal_accepted' => 'ACCEPT ' . $number,
			'proposal_declined' => 'DECLINE ' . $number,
			'sow_approved'      => $sow ? 'APPROVE ' . strtoupper( (string) $sow['sow_number'] ) : '',
		);
		return $map[ $action ] ?? '';
	}

	public static function verify_approval_integrity( array $approval ): bool {
		$payload = array(
			'schema'                => (string) ( $approval['schema'] ?? '' ),
			'inquiry_id'            => absint( $approval['inquiry_id'] ?? 0 ),
			'proposal_id'           => absint( $approval['proposal_id'] ?? 0 ),
			'proposal_version_id'   => absint( $approval['proposal_version_id'] ?? 0 ),
			'sow_id'                => absint( $approval['sow_id'] ?? 0 ) ?: null,
			'action'                => (string) ( $approval['action'] ?? '' ),
			'actor_type'            => (string) ( $approval['actor_type'] ?? '' ),
			'actor_id'              => absint( $approval['actor_id'] ?? 0 ) ?: null,
			'note'                  => (string) ( $approval['note'] ?? '' ),
			'authority_attested'    => absint( $approval['authority_attested'] ?? 0 ),
			'boundary_acknowledged' => absint( $approval['boundary_acknowledged'] ?? 0 ),
			'confirmation_hash'     => (string) ( $approval['confirmation_hash'] ?? '' ),
			'created_at'            => (string) ( $approval['created_at'] ?? '' ),
		);
		if ( 'staff' === $payload['actor_type'] ) {
			$payload = array_merge( array( 'public_id' => (string) ( $approval['public_id'] ?? '' ) ), $payload );
		}
		$expected = SC_EI_Proposal_Governance_Schema::canonical_hash( $payload );
		return '' !== (string) ( $approval['immutable_hash'] ?? '' ) && hash_equals( (string) $approval['immutable_hash'], $expected );
	}

	private static function ensure_conversion_receipt( array $proposal, array $sow, int $actor_user_id ) {
		return self::record_staff_approval( $proposal, 'engagement_converted', 'Converted the accepted proposal and approved Statement of Work into an engagement handoff.', $actor_user_id, absint( $sow['id'] ) );
	}

	private static function mark_proposal_converted( array $proposal ) {
		global $wpdb;
		if ( 'converted_to_engagement' === (string) $proposal['status'] ) {
			return $proposal;
		}
		if ( 'contracted' !== (string) $proposal['status'] ) {
			return new WP_Error( 'proposal_conversion_status_invalid', __( 'The proposal is not in a convertible state.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		$updated = $wpdb->update(
			SC_EI_Database::table( 'proposals' ),
			array( 'status' => 'converted_to_engagement', 'row_version' => absint( $proposal['row_version'] ) + 1, 'updated_at' => $now ),
			array( 'id' => absint( $proposal['id'] ), 'row_version' => absint( $proposal['row_version'] ), 'status' => 'contracted' ),
			array( '%s', '%d', '%s' ),
			array( '%d', '%d', '%s' )
		);
		if ( 1 !== $updated ) {
			$fresh = SC_EI_Workflow_Repository::find_proposal( absint( $proposal['id'] ) );
			if ( $fresh && 'converted_to_engagement' === (string) $fresh['status'] ) {
				return $fresh;
			}
			return new WP_Error( 'proposal_conversion_status_conflict', __( 'The proposal changed before conversion status was saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		return SC_EI_Workflow_Repository::find_proposal( absint( $proposal['id'] ) );
	}

	private static function record_staff_approval( array $proposal, string $action, string $note, int $actor_user_id, int $sow_id = 0 ) {
		global $wpdb;
		$table = SC_EI_Database::table( 'proposal_approvals' );
		$existing = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE proposal_id = %d AND proposal_version_id = %d AND action = %s AND actor_type = 'staff' AND COALESCE(sow_id,0) = %d LIMIT 1", absint( $proposal['id'] ), absint( $proposal['current_version_id'] ), $action, $sow_id ),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $existing ) {
			if ( self::verify_approval_integrity( $existing ) ) {
				$existing['_idempotent'] = true;
				return $existing;
			}
			return new WP_Error( 'proposal_staff_approval_integrity_failed', __( 'The existing staff approval receipt failed integrity verification.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$payload = array(
			'public_id'             => wp_generate_uuid4(),
			'schema'                => SC_EI_Proposal_Governance_Schema::APPROVAL_SCHEMA,
			'inquiry_id'            => absint( $proposal['inquiry_id'] ),
			'proposal_id'           => absint( $proposal['id'] ),
			'proposal_version_id'   => absint( $proposal['current_version_id'] ),
			'sow_id'                => $sow_id ?: null,
			'action'                => $action,
			'actor_type'            => 'staff',
			'actor_id'              => $actor_user_id,
			'note'                  => sanitize_textarea_field( $note ),
			'authority_attested'    => 1,
			'boundary_acknowledged' => 1,
			'confirmation_hash'     => '',
			'created_at'            => current_time( 'mysql', true ),
		);
		$payload['immutable_hash'] = SC_EI_Proposal_Governance_Schema::canonical_hash( $payload );
		if ( false === $wpdb->insert( $table, $payload, self::formats( $payload, self::approval_integer_fields() ) ) ) {
			return new WP_Error( 'proposal_staff_approval_save_failed', __( 'The staff approval receipt could not be recorded.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$result = self::find_approval( (int) $wpdb->insert_id );
		if ( $result ) {
			$result['_idempotent'] = false;
		}
		return $result ?: new WP_Error( 'proposal_staff_approval_readback_failed', __( 'The staff approval receipt could not be read back.', 'sustainable-catalyst-engagement-intake' ) );
	}

	private static function record_reliability_failure( string $event_type, int $proposal_id, array $context = array() ): void {
		if ( class_exists( 'SC_EI_Hardening_Repository' ) ) {
			SC_EI_Hardening_Repository::record_event(
				'proposal_governance',
				$event_type,
				'critical',
				'Proposal-governance reliability compensation or manual review is required.',
				array_merge( array( 'proposal_id' => $proposal_id, 'request_id' => SC_EI_Hardening_Repository::request_id() ), $context )
			);
		}
	}

	private static function event( int $inquiry_id, string $object_type, int $object_id, string $event_type, string $from_status, string $to_status, int $actor_user_id, array $context = array() ): void {
		global $wpdb;
		$wpdb->insert(
			SC_EI_Database::table( 'workflow_events' ),
			array(
				'public_id'    => wp_generate_uuid4(),
				'inquiry_id'   => $inquiry_id,
				'actor_type'   => $actor_user_id ? 'staff' : 'system',
				'actor_id'     => $actor_user_id ?: null,
				'object_type'  => $object_type,
				'object_id'    => $object_id,
				'event_type'   => $event_type,
				'from_status'  => $from_status,
				'to_status'    => $to_status,
				'context_json' => wp_json_encode( $context, JSON_UNESCAPED_SLASHES ),
				'created_at'   => current_time( 'mysql', true ),
			),
			array( '%s', '%d', '%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
		);
	}

	private static function date_or_null( $value ): ?string {
		$value = sanitize_text_field( (string) $value );
		if ( '' === $value ) {
			return null;
		}
		$date = DateTimeImmutable::createFromFormat( '!Y-m-d', $value, new DateTimeZone( 'UTC' ) );
		return $date && $date->format( 'Y-m-d' ) === $value ? $value : null;
	}

	private static function formats( array $data, array $integer_fields = array() ): array {
		return array_map( static fn( string $key ): string => in_array( $key, $integer_fields, true ) ? '%d' : '%s', array_keys( $data ) );
	}

	private static function sow_integer_fields(): array {
		return array( 'inquiry_id', 'proposal_id', 'proposal_version_id', 'current_version_id', 'pending_version_id', 'sender_visible', 'approved_by', 'row_version', 'created_by' );
	}

	private static function approval_integer_fields(): array {
		return array( 'inquiry_id', 'proposal_id', 'proposal_version_id', 'sow_id', 'actor_id', 'authority_attested', 'boundary_acknowledged' );
	}

	private static function change_integer_fields(): array {
		return array( 'inquiry_id', 'proposal_id', 'proposal_version_id', 'sow_id', 'sow_version_id', 'engagement_id', 'requester_id', 'fee_impact_minor', 'decided_by', 'applied_by', 'row_version' );
	}
}
