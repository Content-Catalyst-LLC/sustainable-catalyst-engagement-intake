<?php
/**
 * Secure sender portal persistence, invitations, messages, updates, and audit.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Portal_Repository {

	public static function register(): void {
		add_action( 'sc_ei_portal_cleanup', array( __CLASS__, 'handle_cleanup' ) );
	}

	public static function schedule(): void {
		if ( ! wp_next_scheduled( 'sc_ei_portal_cleanup' ) ) {
			wp_schedule_event( time() + 10 * MINUTE_IN_SECONDS, 'hourly', 'sc_ei_portal_cleanup' );
		}
	}

	public static function unschedule(): void {
		wp_clear_scheduled_hook( 'sc_ei_portal_cleanup' );
	}

	public static function handle_cleanup(): void {
		$report = self::expire_stale();
		update_option( 'sc_ei_last_portal_cleanup', $report, false );
	}

	public static function issue_invitation( int $inquiry_id, array $input, int $actor_user_id ) {
		global $wpdb;

		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $inquiry || ! is_email( (string) $inquiry['contact_email'] ) ) {
			return new WP_Error( 'portal_inquiry_invalid', __( 'The inquiry or sender email is unavailable.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( 'erased' === (string) $inquiry['privacy_status'] ) {
			return new WP_Error( 'portal_inquiry_erased', __( 'Portal access cannot be issued for an erased inquiry.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$settings = self::settings();
		$hours = max( 1, min( 720, absint( $input['invite_ttl_hours'] ?? $settings['portal_invite_ttl_hours'] ) ) );
		$permissions = SC_EI_Portal_Schema::sanitize_permissions( $input['permissions'] ?? $settings['portal_default_permissions'] );
		if ( ! $permissions ) {
			return new WP_Error( 'portal_permissions_required', __( 'Select at least one sender portal permission.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$raw_token = self::random_token( 32 );
		$token_hash = self::hash_secret( $raw_token );
		$now = current_time( 'mysql', true );
		$expires = gmdate( 'Y-m-d H:i:s', time() + $hours * HOUR_IN_SECONDS );
		$table = SC_EI_Database::table( 'portal_access' );
		$current = self::access_for_inquiry( $inquiry_id );
		$data = array(
			'status'               => 'invited',
			'sender_email_hash'    => self::email_hash( (string) $inquiry['contact_email'] ),
			'invite_token_hash'    => $token_hash,
			'invite_token_prefix'  => substr( $raw_token, 0, 12 ),
			'invite_expires_at'    => $expires,
			'invite_used_at'       => null,
			'permissions_json'     => wp_json_encode( $permissions ),
			'terms_version'        => sanitize_text_field( (string) $settings['portal_terms_version'] ),
			'terms_accepted_at'    => null,
			'invited_by'           => $actor_user_id ?: null,
			'invitation_note'      => sanitize_textarea_field( (string) ( $input['invitation_note'] ?? '' ) ),
			'activated_at'         => null,
			'suspended_at'         => null,
			'revoked_at'           => null,
			'revoked_by'           => null,
			'revocation_reason'    => '',
			'failed_attempts'      => 0,
			'locked_until'         => null,
			'row_version'          => $current ? absint( $current['row_version'] ) + 1 : 0,
			'updated_at'           => $now,
		);

		$wpdb->query( 'START TRANSACTION' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ( $current ) {
			$updated = $wpdb->update(
				$table,
				$data,
				array( 'id' => absint( $current['id'] ) ),
				self::formats( $data, self::access_integer_fields() ),
				array( '%d' )
			);
			if ( false === $updated ) {
				$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				return new WP_Error( 'portal_invite_update_failed', __( 'The sender portal invitation could not be reissued.', 'sustainable-catalyst-engagement-intake' ) );
			}
			$access_id = absint( $current['id'] );
			self::revoke_sessions( $access_id, 'Invitation reissued.', $actor_user_id, false );
			$event_type = 'invitation_reissued';
		} else {
			$data = array_merge(
				array(
					'public_id'  => wp_generate_uuid4(),
					'inquiry_id' => $inquiry_id,
					'created_at' => $now,
				),
				$data
			);
			$inserted = $wpdb->insert( $table, $data, self::formats( $data, self::access_integer_fields() ) );
			if ( false === $inserted ) {
				$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				return new WP_Error( 'portal_invite_create_failed', __( 'The sender portal invitation could not be created.', 'sustainable-catalyst-engagement-intake' ) );
			}
			$access_id = (int) $wpdb->insert_id;
			$event_type = 'invitation_issued';
		}

		$inquiry_updated = $wpdb->update(
			SC_EI_Database::table( 'inquiries' ),
			array(
				'portal_status'           => 'invited',
				'portal_access_id'        => $access_id,
				'portal_last_activity_at' => $now,
				'portal_version'          => absint( $inquiry['portal_version'] ?? 0 ) + 1,
				'updated_at'              => $now,
			),
			array( 'id' => $inquiry_id ),
			array( '%s', '%d', '%s', '%d', '%s' ),
			array( '%d' )
		);
		if ( false === $inquiry_updated ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			return new WP_Error( 'portal_inquiry_update_failed', __( 'The inquiry portal state could not be updated.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		$access = self::find_access( $access_id );
		$link = self::invitation_url( $access, $raw_token );
		self::record_event(
			$event_type,
			$inquiry_id,
			$access_id,
			0,
			'access',
			$access_id,
			'success',
			array(
				'expires_at'       => $expires,
				'permission_count' => count( $permissions ),
				'actor_user_id'    => $actor_user_id,
			)
		);
		SC_EI_Audit_Log::record(
			'portal_invitation_issued',
			'Secure sender portal invitation issued or reissued. Raw credentials were not stored.',
			array(
				'access_id'         => $access_id,
				'expires_at'        => $expires,
				'permission_count'  => count( $permissions ),
				'portal_schema_version' => SC_EI_PORTAL_SCHEMA_VERSION,
			),
			$inquiry_id,
			null,
			$actor_user_id
		);
		return array(
			'access'    => $access,
			'raw_token' => $raw_token,
			'url'       => $link,
			'expires_at'=> $expires,
		);
	}

	public static function activate_invitation(
		string $public_id,
		string $raw_token,
		string $email,
		bool $terms_accepted
	) {
		global $wpdb;

		$access = self::find_access_by_public_id( $public_id );
		$generic = new WP_Error( 'portal_activation_failed', __( 'The portal invitation could not be activated. Verify the link and email or request a new invitation.', 'sustainable-catalyst-engagement-intake' ) );
		if ( ! $access ) {
			return $generic;
		}
		$settings = self::settings();
		$now = current_time( 'mysql', true );
		if (
			'invited' !== $access['status']
			|| empty( $access['invite_token_hash'] )
			|| empty( $access['invite_expires_at'] )
			|| strtotime( $access['invite_expires_at'] . ' UTC' ) < time()
			|| ( ! empty( $access['locked_until'] ) && strtotime( $access['locked_until'] . ' UTC' ) > time() )
		) {
			self::record_event( 'invitation_failed', absint( $access['inquiry_id'] ), absint( $access['id'] ), 0, 'access', absint( $access['id'] ), 'rejected', array( 'reason' => 'state_or_expiry' ) );
			return $generic;
		}
		if (
			! hash_equals( (string) $access['invite_token_hash'], self::hash_secret( $raw_token ) )
			|| ( ! empty( $settings['portal_require_email_challenge'] ) && ! hash_equals( (string) $access['sender_email_hash'], self::email_hash( $email ) ) )
		) {
			self::register_failed_activation( $access );
			return $generic;
		}
		if ( ! empty( $settings['portal_require_terms_acceptance'] ) && ! $terms_accepted ) {
			return new WP_Error( 'portal_terms_required', __( 'Accept the secure portal terms to continue.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$inquiry = SC_EI_Inquiry_Repository::find( absint( $access['inquiry_id'] ) );
		if ( ! $inquiry || 'erased' === $inquiry['privacy_status'] ) {
			return $generic;
		}
		$updated = $wpdb->update(
			SC_EI_Database::table( 'portal_access' ),
			array(
				'status'              => 'active',
				'invite_token_hash'   => '',
				'invite_token_prefix' => '',
				'invite_used_at'      => $now,
				'terms_accepted_at'   => $terms_accepted ? $now : null,
				'activated_at'        => $access['activated_at'] ?: $now,
				'failed_attempts'     => 0,
				'locked_until'        => null,
				'last_access_at'      => $now,
				'last_ip_hash'        => self::request_ip_hash(),
				'last_user_agent_hash'=> self::request_user_agent_hash(),
				'row_version'         => absint( $access['row_version'] ) + 1,
				'updated_at'          => $now,
			),
			array( 'id' => absint( $access['id'] ), 'row_version' => absint( $access['row_version'] ) ),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s' ),
			array( '%d', '%d' )
		);
		if ( 1 !== $updated ) {
			return $generic;
		}
		$wpdb->update(
			SC_EI_Database::table( 'inquiries' ),
			array(
				'portal_status'           => 'active',
				'portal_access_id'        => absint( $access['id'] ),
				'portal_last_activity_at' => $now,
				'portal_version'          => absint( $inquiry['portal_version'] ) + 1,
				'updated_at'              => $now,
			),
			array( 'id' => absint( $inquiry['id'] ) ),
			array( '%s', '%d', '%s', '%d', '%s' ),
			array( '%d' )
		);

		$access = self::find_access( absint( $access['id'] ) );
		$session = self::create_session( $access );
		if ( is_wp_error( $session ) ) {
			return $session;
		}
		self::record_event( 'invitation_activated', absint( $access['inquiry_id'] ), absint( $access['id'] ), absint( $session['session']['id'] ), 'access', absint( $access['id'] ), 'success', array( 'terms_version' => $access['terms_version'] ) );
		SC_EI_Audit_Log::record(
			'portal_invitation_activated',
			'Sender activated secure portal access using a one-time invitation and email challenge.',
			array( 'access_id' => absint( $access['id'] ), 'session_id' => absint( $session['session']['id'] ) ),
			absint( $access['inquiry_id'] )
		);
		return $session;
	}

	public static function create_session( array $access ) {
		global $wpdb;

		if ( 'active' !== $access['status'] ) {
			return new WP_Error( 'portal_access_inactive', __( 'Sender portal access is not active.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$settings = self::settings();
		$max_sessions = max( 1, min( 10, absint( $settings['portal_max_active_sessions'] ) ) );
		$active = self::sessions( absint( $access['id'] ), true );
		while ( count( $active ) >= $max_sessions ) {
			$oldest = array_shift( $active );
			self::revoke_session( absint( $oldest['id'] ), 'Maximum active sessions exceeded.', 0 );
		}

		$raw = self::random_token( 48 );
		$now = current_time( 'mysql', true );
		$expires = gmdate( 'Y-m-d H:i:s', time() + max( 30, absint( $settings['portal_session_ttl_minutes'] ) ) * MINUTE_IN_SECONDS );
		$idle = gmdate( 'Y-m-d H:i:s', time() + max( 5, absint( $settings['portal_idle_timeout_minutes'] ) ) * MINUTE_IN_SECONDS );
		$data = array(
			'public_id'       => wp_generate_uuid4(),
			'access_id'       => absint( $access['id'] ),
			'inquiry_id'      => absint( $access['inquiry_id'] ),
			'session_hash'    => self::hash_secret( $raw ),
			'status'          => 'active',
			'expires_at'      => $expires,
			'idle_expires_at' => $idle,
			'last_seen_at'    => $now,
			'ip_hash'         => self::request_ip_hash(),
			'user_agent_hash' => self::request_user_agent_hash(),
			'activity_count'  => 0,
			'rotated_from_id' => null,
			'revoked_at'      => null,
			'revoked_reason'  => '',
			'created_at'      => $now,
			'updated_at'      => $now,
		);
		if ( false === $wpdb->insert( SC_EI_Database::table( 'portal_sessions' ), $data, self::formats( $data, self::session_integer_fields() ) ) ) {
			return new WP_Error( 'portal_session_create_failed', __( 'A secure portal session could not be created.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$id = (int) $wpdb->insert_id;
		$session = self::find_session( $id );
		self::record_event( 'session_created', absint( $access['inquiry_id'] ), absint( $access['id'] ), $id, 'session', $id, 'success' );
		return array(
			'session'   => $session,
			'raw_token' => $raw,
			'expires_at'=> $expires,
		);
	}

	public static function find_access( int $id ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'portal_access' );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $row ?: null;
	}

	public static function find_access_by_public_id( string $public_id ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'portal_access' );
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE public_id = %s", sanitize_text_field( $public_id ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
		return $row ?: null;
	}

	public static function access_for_inquiry( int $inquiry_id ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'portal_access' );
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE inquiry_id = %d", $inquiry_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
		return $row ?: null;
	}

	public static function find_session( int $id ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'portal_sessions' );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $row ?: null;
	}

	public static function find_session_by_hash( string $hash ): ?array {
		global $wpdb;
		$table = SC_EI_Database::table( 'portal_sessions' );
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE session_hash = %s LIMIT 1", sanitize_text_field( $hash ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
		return $row ?: null;
	}

	public static function sessions( int $access_id, bool $active_only = false ): array {
		global $wpdb;
		$table = SC_EI_Database::table( 'portal_sessions' );
		$where = $active_only ? " AND status = 'active' AND revoked_at IS NULL" : '';
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE access_id = %d{$where} ORDER BY created_at ASC, id ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$access_id
			),
			ARRAY_A
		);
	}

	public static function touch_session( array $session ): void {
		global $wpdb;

		$settings = self::settings();
		$now_ts = time();
		$last_seen = strtotime( $session['last_seen_at'] . ' UTC' );
		if ( $last_seen && $now_ts - $last_seen < max( 30, absint( $settings['portal_session_touch_seconds'] ) ) ) {
			return;
		}
		$now = current_time( 'mysql', true );
		$idle = gmdate( 'Y-m-d H:i:s', $now_ts + max( 5, absint( $settings['portal_idle_timeout_minutes'] ) ) * MINUTE_IN_SECONDS );
		$wpdb->update(
			SC_EI_Database::table( 'portal_sessions' ),
			array(
				'last_seen_at'    => $now,
				'idle_expires_at' => $idle,
				'activity_count'  => absint( $session['activity_count'] ) + 1,
				'updated_at'      => $now,
			),
			array( 'id' => absint( $session['id'] ), 'status' => 'active' ),
			array( '%s', '%s', '%d', '%s' ),
			array( '%d', '%s' )
		);
		$wpdb->update(
			SC_EI_Database::table( 'portal_access' ),
			array(
				'last_access_at'       => $now,
				'last_ip_hash'         => self::request_ip_hash(),
				'last_user_agent_hash' => self::request_user_agent_hash(),
				'updated_at'           => $now,
			),
			array( 'id' => absint( $session['access_id'] ) ),
			array( '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);
		$wpdb->update(
			SC_EI_Database::table( 'inquiries' ),
			array(
				'portal_last_activity_at' => $now,
				'updated_at'              => $now,
			),
			array( 'id' => absint( $session['inquiry_id'] ) ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	public static function revoke_session( int $session_id, string $reason, int $actor_user_id = 0 ): bool {
		global $wpdb;
		$session = self::find_session( $session_id );
		if ( ! $session || 'active' !== $session['status'] ) {
			return false;
		}
		$now = current_time( 'mysql', true );
		$updated = $wpdb->update(
			SC_EI_Database::table( 'portal_sessions' ),
			array(
				'status'         => 'revoked',
				'revoked_at'     => $now,
				'revoked_reason' => sanitize_textarea_field( $reason ),
				'updated_at'     => $now,
			),
			array( 'id' => $session_id ),
			array( '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);
		if ( false === $updated ) {
			return false;
		}
		self::record_event( 'session_revoked', absint( $session['inquiry_id'] ), absint( $session['access_id'] ), $session_id, 'session', $session_id, 'success', array( 'actor_user_id' => $actor_user_id, 'reason' => sanitize_textarea_field( $reason ) ) );
		return true;
	}

	public static function revoke_sessions( int $access_id, string $reason, int $actor_user_id = 0, bool $record_audit = true ): int {
		$count = 0;
		foreach ( self::sessions( $access_id, true ) as $session ) {
			if ( self::revoke_session( absint( $session['id'] ), $reason, $actor_user_id ) ) {
				$count++;
			}
		}
		if ( $record_audit && $count ) {
			$access = self::find_access( $access_id );
			SC_EI_Audit_Log::record(
				'portal_sessions_revoked',
				'Active sender portal sessions were revoked.',
				array( 'access_id' => $access_id, 'session_count' => $count, 'reason' => sanitize_textarea_field( $reason ) ),
				$access ? absint( $access['inquiry_id'] ) : null,
				null,
				$actor_user_id ?: null
			);
		}
		return $count;
	}

	public static function change_access_status( int $access_id, string $status, string $reason, int $actor_user_id ) {
		global $wpdb;

		$access = self::find_access( $access_id );
		if ( ! $access ) {
			return new WP_Error( 'portal_access_not_found', __( 'The sender portal access record could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$status = SC_EI_Portal_Schema::sanitize_access_status( $status, $access['status'] );
		if ( ! in_array( $status, array( 'active', 'suspended', 'revoked' ), true ) ) {
			return new WP_Error( 'portal_access_status_invalid', __( 'The requested portal access state is not allowed.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( 'active' === $status && ! in_array( $access['status'], array( 'active', 'suspended' ), true ) ) {
			return new WP_Error( 'portal_reinvite_required', __( 'Revoked or expired access requires a fresh one-time invitation.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( in_array( $status, array( 'suspended', 'revoked' ), true ) && '' === trim( $reason ) ) {
			return new WP_Error( 'portal_access_reason_required', __( 'Record a reason for suspending or revoking access.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$now = current_time( 'mysql', true );
		$data = array(
			'status'            => $status,
			'suspended_at'      => 'suspended' === $status ? $now : null,
			'revoked_at'        => 'revoked' === $status ? $now : null,
			'revoked_by'        => 'revoked' === $status ? $actor_user_id : null,
			'revocation_reason' => in_array( $status, array( 'suspended', 'revoked' ), true ) ? sanitize_textarea_field( $reason ) : '',
			'row_version'       => absint( $access['row_version'] ) + 1,
			'updated_at'        => $now,
		);
		$updated = $wpdb->update(
			SC_EI_Database::table( 'portal_access' ),
			$data,
			array( 'id' => $access_id, 'row_version' => absint( $access['row_version'] ) ),
			self::formats( $data, self::access_integer_fields() ),
			array( '%d', '%d' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'portal_access_conflict', __( 'The portal access record changed before it could be updated.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( in_array( $status, array( 'suspended', 'revoked' ), true ) ) {
			self::revoke_sessions( $access_id, ucfirst( $status ) . ': ' . $reason, $actor_user_id, false );
		}
		$inquiry = SC_EI_Inquiry_Repository::find( absint( $access['inquiry_id'] ) );
		$wpdb->update(
			SC_EI_Database::table( 'inquiries' ),
			array(
				'portal_status'           => $status,
				'portal_last_activity_at' => $now,
				'portal_version'          => absint( $inquiry['portal_version'] ?? 0 ) + 1,
				'updated_at'              => $now,
			),
			array( 'id' => absint( $access['inquiry_id'] ) ),
			array( '%s', '%s', '%d', '%s' ),
			array( '%d' )
		);
		$event = 'suspended' === $status ? 'access_suspended' : ( 'revoked' === $status ? 'access_revoked' : 'access_resumed' );
		self::record_event( $event, absint( $access['inquiry_id'] ), $access_id, 0, 'access', $access_id, 'success', array( 'actor_user_id' => $actor_user_id, 'reason' => sanitize_textarea_field( $reason ) ) );
		SC_EI_Audit_Log::record(
			'portal_access_' . $status,
			'Secure sender portal access state changed.',
			array( 'access_id' => $access_id, 'status' => $status, 'reason' => sanitize_textarea_field( $reason ) ),
			absint( $access['inquiry_id'] ),
			null,
			$actor_user_id
		);
		return self::find_access( $access_id );
	}

	public static function portal_messages( int $inquiry_id, int $limit = 250 ): array {
		global $wpdb;
		$table = SC_EI_Database::table( 'communications' );
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE inquiry_id = %d
					AND portal_visibility = 'visible'
					AND deleted_at IS NULL
					AND direction IN ('inbound','outbound')
				ORDER BY COALESCE(occurred_at, created_at) ASC, id ASC
				LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$inquiry_id,
				max( 1, min( 500, $limit ) )
			),
			ARRAY_A
		);
	}

	public static function create_portal_message(
		int $inquiry_id,
		string $direction,
		string $body,
		int $actor_user_id = 0,
		int $reply_to_id = 0
	) {
		global $wpdb;

		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $inquiry ) {
			return new WP_Error( 'portal_message_inquiry_missing', __( 'The inquiry could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$direction = 'outbound' === $direction ? 'outbound' : 'inbound';
		if ( 'outbound' === $direction ) {
			$access = self::access_for_inquiry( $inquiry_id );
			if ( ! $access || 'active' !== $access['status'] ) {
				return new WP_Error( 'portal_access_inactive', __( 'A secure staff reply requires active sender portal access.', 'sustainable-catalyst-engagement-intake' ) );
			}
		}
		$body = SC_EI_Communication_Schema::sanitize_body( $body );
		if ( '' === $body ) {
			return new WP_Error( 'portal_message_body_required', __( 'Write a secure message before submitting.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( $reply_to_id ) {
			$reply = SC_EI_Communication_Repository::find( $reply_to_id );
			if ( ! $reply || absint( $reply['inquiry_id'] ) !== $inquiry_id || 'visible' !== $reply['portal_visibility'] ) {
				$reply_to_id = 0;
			}
		}

		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() );
		$user = $actor_user_id ? get_userdata( $actor_user_id ) : null;
		$now = current_time( 'mysql', true );
		$is_outbound = 'outbound' === $direction;
		$subject = sprintf( __( 'Secure portal message — %s', 'sustainable-catalyst-engagement-intake' ), $inquiry['reference'] );
		$data = array(
			'inquiry_id'             => $inquiry_id,
			'public_id'              => wp_generate_uuid4(),
			'thread_key'             => SC_EI_Communication_Schema::thread_key( $inquiry ),
			'reply_to_id'            => $reply_to_id ?: null,
			'direction'              => $direction,
			'channel'                => 'sender_portal',
			'communication_type'     => 'portal_message',
			'status'                 => $is_outbound ? 'recorded' : 'received',
			'subject'                => $subject,
			'body_text'              => $body,
			'sender_user_id'         => $is_outbound ? ( $actor_user_id ?: null ) : null,
			'sender_name'            => $is_outbound
				? sanitize_text_field( $user ? $user->display_name : (string) $settings['communication_sender_name'] )
				: sanitize_text_field( (string) $inquiry['contact_name'] ),
			'sender_email'           => $is_outbound
				? sanitize_email( $user && is_email( $user->user_email ) ? $user->user_email : (string) $settings['communication_sender_email'] )
				: sanitize_email( (string) $inquiry['contact_email'] ),
			'recipient_name'         => $is_outbound
				? sanitize_text_field( (string) $inquiry['contact_name'] )
				: sanitize_text_field( (string) $settings['communication_sender_name'] ),
			'recipient_email'        => $is_outbound
				? sanitize_email( (string) $inquiry['contact_email'] )
				: sanitize_email( (string) $settings['communication_sender_email'] ),
			'cc_json'                => '[]',
			'template_key'           => '',
			'template_version'       => 0,
			'is_automated'           => 0,
			'requires_approval'      => 0,
			'approved_by'            => $is_outbound ? ( $actor_user_id ?: null ) : null,
			'approved_at'            => $is_outbound ? $now : null,
			'provider'               => 'sender_portal',
			'provider_message_id'    => '',
			'attempt_count'          => 0,
			'last_attempt_at'        => null,
			'accepted_at'            => $is_outbound ? $now : null,
			'failed_at'              => null,
			'error_code'             => '',
			'error_message'          => '',
			'occurred_at'            => $now,
			'scheduled_for'          => null,
			'privacy_classification' => 'private',
			'message_hash'           => hash( 'sha256', $body ),
			'dedupe_key'             => null,
			'metadata_json'          => wp_json_encode(
				array(
					'portal_schema_version' => SC_EI_PORTAL_SCHEMA_VERSION,
					'transport'             => 'authenticated_portal',
					'email_sent'            => false,
				)
			),
			'portal_visibility'      => 'visible',
			'portal_published_at'    => $now,
			'portal_published_by'    => $is_outbound ? ( $actor_user_id ?: null ) : null,
			'portal_source'          => $is_outbound ? 'staff_portal' : 'sender_portal',
			'row_version'            => 0,
			'created_at'             => $now,
			'updated_at'             => $now,
			'deleted_at'             => null,
		);
		$integer = array(
			'inquiry_id', 'reply_to_id', 'sender_user_id', 'template_version', 'is_automated',
			'requires_approval', 'approved_by', 'attempt_count', 'portal_published_by', 'row_version',
		);
		if ( false === $wpdb->insert( SC_EI_Database::table( 'communications' ), $data, self::formats( $data, $integer ) ) ) {
			return new WP_Error( 'portal_message_save_failed', __( 'The secure message could not be recorded.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$id = (int) $wpdb->insert_id;
		SC_EI_Communication_Repository::record_event(
			$id,
			$inquiry_id,
			'portal_message_recorded',
			'',
			$data['status'],
			$actor_user_id,
			array( 'portal_visibility' => 'visible', 'email_sent' => false )
		);
		SC_EI_Communication_Repository::update_inquiry_aggregate( $inquiry_id, $direction, $now, ! $is_outbound );
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE " . SC_EI_Database::table( 'inquiries' ) . "
				SET portal_message_count = portal_message_count + 1,
					portal_last_activity_at = %s,
					portal_last_sender_message_at = CASE WHEN %s = 'inbound' THEN %s ELSE portal_last_sender_message_at END,
					portal_version = portal_version + 1,
					updated_at = %s
				WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$now,
				$direction,
				$now,
				$now,
				$inquiry_id
			)
		);
		self::record_event(
			$is_outbound ? 'staff_message_created' : 'sender_message_created',
			$inquiry_id,
			absint( $inquiry['portal_access_id'] ),
			0,
			'communication',
			$id,
			'success',
			array( 'direction' => $direction, 'email_sent' => false )
		);
		SC_EI_Audit_Log::record(
			$is_outbound ? 'portal_staff_message_created' : 'portal_sender_message_created',
			'Authenticated secure portal message recorded without email transport.',
			array( 'communication_id' => $id, 'direction' => $direction, 'email_sent' => false ),
			$inquiry_id,
			null,
			$actor_user_id ?: null
		);
		return SC_EI_Communication_Repository::find( $id );
	}

	public static function publish_communication( int $communication_id, bool $visible, int $actor_user_id ) {
		global $wpdb;

		$communication = SC_EI_Communication_Repository::find( $communication_id );
		if (
			! $communication
			|| 'outbound' !== $communication['direction']
			|| in_array( $communication['status'], array( 'draft', 'failed', 'canceled', 'suppressed' ), true )
		) {
			return new WP_Error( 'portal_publish_invalid', __( 'Only a completed outbound communication can be published to the sender portal.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		$data = array(
			'portal_visibility'   => $visible ? 'visible' : 'hidden',
			'portal_published_at' => $visible ? $now : null,
			'portal_published_by' => $visible ? $actor_user_id : null,
			'portal_source'       => $visible ? 'published_existing' : '',
			'row_version'         => absint( $communication['row_version'] ) + 1,
			'updated_at'          => $now,
		);
		$updated = $wpdb->update(
			SC_EI_Database::table( 'communications' ),
			$data,
			array( 'id' => $communication_id, 'row_version' => absint( $communication['row_version'] ) ),
			self::formats( $data, array( 'portal_published_by', 'row_version' ) ),
			array( '%d', '%d' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'portal_publish_conflict', __( 'The communication changed before portal visibility could be updated.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::record_event(
			$visible ? 'communication_published' : 'communication_unpublished',
			absint( $communication['inquiry_id'] ),
			0,
			0,
			'communication',
			$communication_id,
			'success',
			array( 'actor_user_id' => $actor_user_id )
		);
		return SC_EI_Communication_Repository::find( $communication_id );
	}

	public static function register_document_upload_result(
		int $inquiry_id,
		array $result,
		int $session_id
	): void {
		global $wpdb;

		$count = absint( $result['count'] ?? 0 );
		if ( ! $count ) {
			return;
		}
		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $inquiry ) {
			return;
		}
		$now = current_time( 'mysql', true );
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE " . SC_EI_Database::table( 'inquiries' ) . "
				SET portal_document_count = portal_document_count + %d,
					portal_last_activity_at = %s,
					portal_version = portal_version + 1,
					updated_at = %s
				WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$count,
				$now,
				$now,
				$inquiry_id
			)
		);
		foreach ( (array) ( $result['accepted'] ?? array() ) as $attachment ) {
			self::record_event(
				'document_uploaded',
				$inquiry_id,
				absint( $inquiry['portal_access_id'] ),
				$session_id,
				'attachment',
				absint( $attachment['id'] ?? 0 ),
				'success',
				array(
					'size_bytes'  => absint( $attachment['size_bytes'] ?? 0 ),
					'scan_status' => sanitize_key( (string) ( $attachment['scan_status'] ?? '' ) ),
				)
			);
		}
		SC_EI_Audit_Log::record(
			'portal_documents_uploaded',
			'Sender uploaded private follow-up documents through the authenticated portal into protected quarantine.',
			array(
				'accepted_count' => $count,
				'error_count'    => count( (array) ( $result['errors'] ?? array() ) ),
			),
			$inquiry_id
		);
	}

	public static function update_contact(
		int $inquiry_id,
		array $input,
		int $expected_portal_version,
		int $session_id
	) {
		global $wpdb;

		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $inquiry || absint( $inquiry['portal_version'] ) !== $expected_portal_version ) {
			return new WP_Error( 'portal_update_conflict', __( 'The inquiry changed before your update was saved. Reload and try again.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$method = sanitize_key( (string) ( $input['preferred_contact_method'] ?? $inquiry['preferred_contact_method'] ) );
		if ( ! isset( SC_EI_Teams::contact_methods()[ $method ] ) ) {
			$method = 'email';
		}
		$links = array();
		foreach ( preg_split( '/[\r\n]+/', (string) ( $input['relevant_links'] ?? '' ) ) as $link ) {
			$link = esc_url_raw( trim( $link ) );
			if ( $link ) {
				$links[] = $link;
			}
		}
		$now = current_time( 'mysql', true );
		$data = array(
			'contact_name'             => sanitize_text_field( (string) ( $input['contact_name'] ?? $inquiry['contact_name'] ) ),
			'organization'             => sanitize_text_field( (string) ( $input['organization'] ?? $inquiry['organization'] ) ),
			'role_title'               => sanitize_text_field( (string) ( $input['role_title'] ?? $inquiry['role_title'] ) ),
			'preferred_contact_method' => $method,
			'phone_number'             => sanitize_text_field( (string) ( $input['phone_number'] ?? $inquiry['phone_number'] ) ),
			'city'                     => sanitize_text_field( (string) ( $input['city'] ?? $inquiry['city'] ) ),
			'country'                  => sanitize_text_field( (string) ( $input['country'] ?? $inquiry['country'] ) ),
			'relevant_links'           => wp_json_encode( array_slice( array_values( array_unique( $links ) ), 0, 20 ) ),
			'portal_last_activity_at'  => $now,
			'portal_version'           => $expected_portal_version + 1,
			'updated_at'               => $now,
		);
		$updated = $wpdb->update(
			SC_EI_Database::table( 'inquiries' ),
			$data,
			array( 'id' => $inquiry_id, 'portal_version' => $expected_portal_version ),
			self::formats( $data, array( 'portal_version' ) ),
			array( '%d', '%d' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'portal_update_conflict', __( 'The inquiry changed before your update was saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::record_event( 'contact_updated', $inquiry_id, absint( $inquiry['portal_access_id'] ), $session_id, 'inquiry', $inquiry_id, 'success', array( 'fields' => array_keys( $data ) ) );
		SC_EI_Audit_Log::record( 'portal_contact_updated', 'Sender updated contact preferences through the authenticated portal.', array( 'fields' => array_keys( $data ) ), $inquiry_id );
		return SC_EI_Inquiry_Repository::find( $inquiry_id );
	}

	public static function update_scheduling(
		int $inquiry_id,
		array $input,
		int $expected_portal_version,
		int $session_id
	) {
		global $wpdb;

		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $inquiry || absint( $inquiry['portal_version'] ) !== $expected_portal_version ) {
			return new WP_Error( 'portal_update_conflict', __( 'The inquiry changed before your scheduling update was saved. Reload and try again.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$timezone = sanitize_text_field( (string) ( $input['timezone'] ?? $inquiry['timezone'] ) );
		if ( $timezone && ! SC_EI_Teams::valid_timezone( $timezone ) ) {
			return new WP_Error( 'portal_timezone_invalid', __( 'Choose a valid IANA timezone.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$teams_email = sanitize_email( (string) ( $input['teams_email'] ?? $inquiry['teams_email'] ) );
		if ( $teams_email && ! is_email( $teams_email ) ) {
			return new WP_Error( 'portal_teams_email_invalid', __( 'Enter a valid Microsoft Teams email.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$meeting_request = sanitize_key( (string) ( $input['meeting_request'] ?? $inquiry['meeting_request'] ) );
		if ( ! isset( SC_EI_Teams::meeting_requests()[ $meeting_request ] ) ) {
			$meeting_request = 'no';
		}
		$duration = absint( $input['preferred_duration'] ?? $inquiry['preferred_duration'] );
		if ( ! isset( SC_EI_Teams::duration_options()[ (string) $duration ] ) ) {
			$duration = 0;
		}
		$weekdays = SC_EI_Teams::sanitize_weekdays( $input['preferred_weekdays'] ?? array() );
		$participants = SC_EI_Teams::sanitize_participant_emails( $input['participant_emails'] ?? '' );
		$calendar_consent = empty( $input['calendar_invite_consent'] ) ? 0 : 1;
		if ( 'yes' === $meeting_request && ! $calendar_consent ) {
			return new WP_Error( 'portal_calendar_consent_required', __( 'Calendar invitation consent is required when requesting a Microsoft Teams meeting.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		$data = array(
			'teams_email'             => $teams_email,
			'timezone'                => $timezone,
			'meeting_request'         => $meeting_request,
			'preferred_weekdays'      => wp_json_encode( $weekdays ),
			'preferred_time_windows'  => sanitize_textarea_field( (string) ( $input['preferred_time_windows'] ?? $inquiry['preferred_time_windows'] ) ),
			'preferred_duration'      => $duration,
			'participant_count'       => max( 1, min( 25, absint( $input['participant_count'] ?? $inquiry['participant_count'] ) ) ),
			'participant_emails'      => wp_json_encode( $participants ),
			'accessibility_needs'     => sanitize_textarea_field( (string) ( $input['accessibility_needs'] ?? $inquiry['accessibility_needs'] ) ),
			'calendar_invite_consent' => $calendar_consent,
			'scheduling_notes'        => sanitize_textarea_field( (string) ( $input['scheduling_notes'] ?? $inquiry['scheduling_notes'] ) ),
			'scheduling_status'       => 'yes' === $meeting_request && 'not_requested' === $inquiry['scheduling_status'] ? 'requested' : $inquiry['scheduling_status'],
			'portal_last_activity_at' => $now,
			'portal_version'          => $expected_portal_version + 1,
			'updated_at'              => $now,
		);
		$updated = $wpdb->update(
			SC_EI_Database::table( 'inquiries' ),
			$data,
			array( 'id' => $inquiry_id, 'portal_version' => $expected_portal_version ),
			self::formats( $data, array( 'preferred_duration', 'participant_count', 'calendar_invite_consent', 'portal_version' ) ),
			array( '%d', '%d' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'portal_update_conflict', __( 'The inquiry changed before your scheduling update was saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( $calendar_consent !== absint( $inquiry['calendar_invite_consent'] ) ) {
			SC_EI_Privacy_Repository::record_consent(
				$inquiry_id,
				array(
					'consent_type'    => 'calendar_invitation',
					'action'          => $calendar_consent ? 'granted' : 'withdrawn',
					'consent_version' => SC_EI_PORTAL_SCHEMA_VERSION,
					'lawful_basis'    => 'request_processing',
					'source'          => 'sender_portal',
					'evidence_text'   => $calendar_consent
						? 'Sender granted calendar invitation permission through authenticated portal.'
						: 'Sender withdrew calendar invitation permission through authenticated portal.',
				)
			);
		}
		self::record_event( 'scheduling_updated', $inquiry_id, absint( $inquiry['portal_access_id'] ), $session_id, 'inquiry', $inquiry_id, 'success', array( 'meeting_request' => $meeting_request, 'calendar_consent' => $calendar_consent ) );
		SC_EI_Audit_Log::record( 'portal_scheduling_updated', 'Sender updated Microsoft Teams scheduling preferences through the authenticated portal.', array( 'meeting_request' => $meeting_request, 'calendar_consent' => $calendar_consent ), $inquiry_id );
		return SC_EI_Inquiry_Repository::find( $inquiry_id );
	}

	public static function create_privacy_request(
		int $inquiry_id,
		string $request_type,
		string $summary,
		int $session_id
	) {
		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $inquiry ) {
			return new WP_Error( 'portal_inquiry_missing', __( 'The inquiry could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$result = SC_EI_Privacy_Repository::create_request(
			array(
				'inquiry_id'      => $inquiry_id,
				'requester_name'  => (string) $inquiry['contact_name'],
				'requester_email' => (string) $inquiry['contact_email'],
				'request_type'    => $request_type,
				'status'          => 'identity_pending',
				'identity_status' => 'pending',
				'source'          => 'sender_portal',
				'request_summary' => $summary,
			)
		);
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		self::record_event( 'privacy_request_created', $inquiry_id, absint( $inquiry['portal_access_id'] ), $session_id, 'privacy_request', absint( $result['id'] ), 'success', array( 'request_type' => $result['request_type'] ) );
		return $result;
	}

	public static function update_withdrawal(
		int $inquiry_id,
		bool $requested,
		string $reason,
		int $expected_portal_version,
		int $session_id
	) {
		global $wpdb;

		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $inquiry || absint( $inquiry['portal_version'] ) !== $expected_portal_version ) {
			return new WP_Error( 'portal_update_conflict', __( 'The inquiry changed before the withdrawal request was saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( $requested && '' === trim( $reason ) ) {
			return new WP_Error( 'portal_withdrawal_reason_required', __( 'Explain the withdrawal request briefly.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$now = current_time( 'mysql', true );
		$status = $requested ? 'requested' : 'canceled';
		$data = array(
			'sender_withdrawal_status'       => $status,
			'sender_withdrawal_requested_at' => $requested ? $now : $inquiry['sender_withdrawal_requested_at'],
			'sender_withdrawal_reason'       => $requested ? sanitize_textarea_field( $reason ) : '',
			'portal_last_activity_at'        => $now,
			'portal_version'                 => $expected_portal_version + 1,
			'updated_at'                     => $now,
		);
		$updated = $wpdb->update(
			SC_EI_Database::table( 'inquiries' ),
			$data,
			array( 'id' => $inquiry_id, 'portal_version' => $expected_portal_version ),
			self::formats( $data, array( 'portal_version' ) ),
			array( '%d', '%d' )
		);
		if ( 1 !== $updated ) {
			return new WP_Error( 'portal_update_conflict', __( 'The inquiry changed before the withdrawal request was saved.', 'sustainable-catalyst-engagement-intake' ) );
		}
		self::record_event( $requested ? 'withdrawal_requested' : 'withdrawal_canceled', $inquiry_id, absint( $inquiry['portal_access_id'] ), $session_id, 'inquiry', $inquiry_id, 'success' );
		SC_EI_Audit_Log::record(
			$requested ? 'portal_withdrawal_requested' : 'portal_withdrawal_canceled',
			$requested
				? 'Sender requested inquiry withdrawal through the authenticated portal. Inquiry status was not changed automatically.'
				: 'Sender canceled a pending inquiry withdrawal request through the authenticated portal.',
			array( 'automatic_inquiry_status_change' => false ),
			$inquiry_id
		);
		return SC_EI_Inquiry_Repository::find( $inquiry_id );
	}

	public static function query_access( array $args = array() ): array {
		global $wpdb;

		$args = wp_parse_args( $args, array( 'status' => '', 'search' => '', 'limit' => 100 ) );
		$access_table = SC_EI_Database::table( 'portal_access' );
		$inquiries = SC_EI_Database::table( 'inquiries' );
		$where = array( '1=1' );
		$params = array();
		$status = sanitize_key( (string) $args['status'] );
		if ( isset( SC_EI_Portal_Schema::access_statuses()[ $status ] ) ) {
			$where[] = 'a.status = %s';
			$params[] = $status;
		}
		$search = sanitize_text_field( (string) $args['search'] );
		if ( '' !== $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$where[] = '(i.reference LIKE %s OR i.contact_name LIKE %s OR i.contact_email LIKE %s OR i.organization LIKE %s)';
			array_push( $params, $like, $like, $like, $like );
		}
		$sql = "SELECT a.*, i.reference, i.contact_name, i.contact_email, i.organization,
				i.status AS inquiry_status, i.portal_status, i.portal_last_activity_at,
				i.portal_message_count, i.portal_document_count, i.sender_withdrawal_status,
				inviter.display_name AS invited_by_name,
				(SELECT COUNT(*) FROM " . SC_EI_Database::table( 'portal_sessions' ) . " s WHERE s.access_id = a.id AND s.status = 'active' AND s.revoked_at IS NULL) AS active_session_count
			FROM {$access_table} a
			INNER JOIN {$inquiries} i ON i.id = a.inquiry_id
			LEFT JOIN {$wpdb->users} inviter ON inviter.ID = a.invited_by
			WHERE " . implode( ' AND ', $where ) . "
			ORDER BY CASE a.status WHEN 'suspended' THEN 0 WHEN 'invited' THEN 1 WHEN 'active' THEN 2 WHEN 'expired' THEN 3 ELSE 4 END,
				COALESCE(a.last_access_at,a.updated_at) DESC
			LIMIT %d";
		$params[] = max( 1, min( 500, absint( $args['limit'] ) ) );
		return (array) $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
	}

	public static function metrics(): array {
		global $wpdb;

		$access = SC_EI_Database::table( 'portal_access' );
		$sessions = SC_EI_Database::table( 'portal_sessions' );
		$events = SC_EI_Database::table( 'portal_events' );
		$today = gmdate( 'Y-m-d 00:00:00' );
		$row = (array) $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					SUM(CASE WHEN status = 'invited' THEN 1 ELSE 0 END) AS invited,
					SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active,
					SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) AS suspended,
					SUM(CASE WHEN status = 'revoked' THEN 1 ELSE 0 END) AS revoked,
					SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) AS expired,
					SUM(CASE WHEN locked_until IS NOT NULL AND locked_until > %s THEN 1 ELSE 0 END) AS locked
				FROM {$access}", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				current_time( 'mysql', true )
			),
			ARRAY_A
		);
		$row['active_sessions'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$sessions} WHERE status = 'active' AND revoked_at IS NULL" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row['messages_today'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$events} WHERE event_type IN ('sender_message_created','staff_message_created') AND created_at >= %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$today
			)
		);
		$row['failed_today'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$events} WHERE event_type IN ('invitation_failed','csrf_rejected','permission_rejected','rate_limit_triggered') AND created_at >= %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$today
			)
		);
		foreach ( array( 'invited', 'active', 'suspended', 'revoked', 'expired', 'locked', 'active_sessions', 'messages_today', 'failed_today' ) as $key ) {
			$row[ $key ] = absint( $row[ $key ] ?? 0 );
		}
		return $row;
	}

	public static function record_event(
		string $event_type,
		int $inquiry_id = 0,
		int $access_id = 0,
		int $session_id = 0,
		string $target_type = '',
		int $target_id = 0,
		string $outcome = 'recorded',
		array $context = array()
	): int {
		global $wpdb;

		$event_type = sanitize_key( $event_type );
		if ( ! isset( SC_EI_Portal_Schema::event_types()[ $event_type ] ) ) {
			$event_type = 'session_seen';
		}
		$data = array(
			'public_id'       => wp_generate_uuid4(),
			'inquiry_id'      => $inquiry_id ?: null,
			'access_id'       => $access_id ?: null,
			'session_id'      => $session_id ?: null,
			'event_type'      => $event_type,
			'target_type'     => sanitize_key( $target_type ),
			'target_id'       => $target_id,
			'outcome'         => sanitize_key( $outcome ),
			'ip_hash'         => self::request_ip_hash(),
			'user_agent_hash' => self::request_user_agent_hash(),
			'context_json'    => wp_json_encode( self::sanitize_context( $context ) ),
			'created_at'      => current_time( 'mysql', true ),
		);
		$wpdb->insert( SC_EI_Database::table( 'portal_events' ), $data, self::formats( $data, array( 'inquiry_id', 'access_id', 'session_id', 'target_id' ) ) );
		return (int) $wpdb->insert_id;
	}

	public static function events( array $args = array() ): array {
		global $wpdb;

		$args = wp_parse_args( $args, array( 'inquiry_id' => 0, 'access_id' => 0, 'session_id' => 0, 'event_type' => '', 'limit' => 250 ) );
		$table = SC_EI_Database::table( 'portal_events' );
		$where = array( '1=1' );
		$params = array();
		foreach ( array( 'inquiry_id', 'access_id', 'session_id' ) as $field ) {
			if ( absint( $args[ $field ] ) ) {
				$where[] = "{$field} = %d";
				$params[] = absint( $args[ $field ] );
			}
		}
		$event_type = sanitize_key( (string) $args['event_type'] );
		if ( $event_type ) {
			$where[] = 'event_type = %s';
			$params[] = $event_type;
		}
		$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . " ORDER BY created_at DESC, id DESC LIMIT %d";
		$params[] = max( 1, min( 1000, absint( $args['limit'] ) ) );
		return (array) $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );
	}

	public static function rate_limited( int $session_id, array $event_types, int $limit, int $window_seconds = 3600 ): bool {
		global $wpdb;

		$event_types = array_values( array_filter( array_map( 'sanitize_key', $event_types ) ) );
		if ( ! $event_types ) {
			return false;
		}
		$placeholders = implode( ',', array_fill( 0, count( $event_types ), '%s' ) );
		$params = array_merge(
			array( $session_id ),
			$event_types,
			array( gmdate( 'Y-m-d H:i:s', time() - max( 60, $window_seconds ) ) )
		);
		$sql = "SELECT COUNT(*) FROM " . SC_EI_Database::table( 'portal_events' ) . "
			WHERE session_id = %d
				AND event_type IN ({$placeholders})
				AND created_at >= %s";
		$count = (int) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
		return $count >= max( 1, $limit );
	}

	public static function expire_stale(): array {
		global $wpdb;

		$now = current_time( 'mysql', true );
		$session_table = SC_EI_Database::table( 'portal_sessions' );
		$access_table = SC_EI_Database::table( 'portal_access' );
		$expired_sessions = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$session_table}
				SET status = 'expired', revoked_at = COALESCE(revoked_at,%s),
					revoked_reason = CASE WHEN revoked_reason = '' THEN 'Session expired.' ELSE revoked_reason END,
					updated_at = %s
				WHERE status = 'active' AND (expires_at < %s OR idle_expires_at < %s)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$now,
				$now,
				$now,
				$now
			)
		);
		$expired_access = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$access_table}
				SET status = 'expired', updated_at = %s, row_version = row_version + 1
				WHERE status = 'invited' AND invite_expires_at IS NOT NULL AND invite_expires_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$now,
				$now
			)
		);
		return array(
			'expired_sessions' => false === $expired_sessions ? 0 : absint( $expired_sessions ),
			'expired_access'   => false === $expired_access ? 0 : absint( $expired_access ),
			'completed_at'     => $now,
		);
	}

	public static function export_for_inquiry( int $inquiry_id ): array {
		$access = self::access_for_inquiry( $inquiry_id );
		return array(
			'access'   => $access,
			'sessions' => $access ? self::sessions( absint( $access['id'] ), false ) : array(),
			'events'   => self::events( array( 'inquiry_id' => $inquiry_id, 'limit' => 1000 ) ),
		);
	}

	public static function redact_for_privacy( int $inquiry_id, string $now ): bool {
		global $wpdb;

		$access = self::access_for_inquiry( $inquiry_id );
		if ( ! $access ) {
			return true;
		}
		self::revoke_sessions( absint( $access['id'] ), 'Personal data erasure approved.', 0, false );
		$sessions = self::sessions( absint( $access['id'] ), false );
		foreach ( $sessions as $session ) {
			$wpdb->update(
				SC_EI_Database::table( 'portal_sessions' ),
				array(
					'session_hash'    => hash( 'sha256', 'erased-session|' . $session['public_id'] ),
					'ip_hash'         => '',
					'user_agent_hash' => '',
					'revoked_reason'  => '',
					'updated_at'      => $now,
				),
				array( 'id' => absint( $session['id'] ) ),
				array( '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
		}
		$access_result = $wpdb->update(
			SC_EI_Database::table( 'portal_access' ),
			array(
				'status'               => 'revoked',
				'sender_email_hash'    => '',
				'invite_token_hash'    => '',
				'invite_token_prefix'  => '',
				'invitation_note'      => '',
				'revocation_reason'    => '',
				'last_ip_hash'         => '',
				'last_user_agent_hash' => '',
				'failed_attempts'      => 0,
				'locked_until'         => null,
				'revoked_at'           => $access['revoked_at'] ?: $now,
				'row_version'          => absint( $access['row_version'] ) + 1,
				'updated_at'           => $now,
			),
			array( 'id' => absint( $access['id'] ) ),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s' ),
			array( '%d' )
		);
		$events_result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE " . SC_EI_Database::table( 'portal_events' ) . "
				SET ip_hash = '', user_agent_hash = '', context_json = %s
				WHERE inquiry_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				wp_json_encode( array( 'personal_data_erased' => true, 'portal_schema_version' => SC_EI_PORTAL_SCHEMA_VERSION ) ),
				$inquiry_id
			)
		);
		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		$inquiry_result = $wpdb->update(
			SC_EI_Database::table( 'inquiries' ),
			array(
				'portal_status'             => 'revoked',
				'sender_withdrawal_reason'  => '',
				'portal_version'            => absint( $inquiry['portal_version'] ?? 0 ) + 1,
				'updated_at'                => $now,
			),
			array( 'id' => $inquiry_id ),
			array( '%s', '%s', '%d', '%s' ),
			array( '%d' )
		);
		return false !== $access_result && false !== $events_result && false !== $inquiry_result;
	}

	public static function invitation_url( array $access, string $raw_token ): string {
		$settings = self::settings();
		return add_query_arg(
			array(
				'sc_ei_portal_invite' => rawurlencode( (string) $access['public_id'] ),
				'sc_ei_portal_token'  => rawurlencode( $raw_token ),
			),
			SC_EI_Portal_Schema::sanitize_portal_page_url( (string) $settings['portal_page_url'] )
		);
	}

	public static function settings(): array {
		return wp_parse_args(
			get_option( 'sc_ei_settings', array() ),
			array_merge( SC_EI_Admin::default_settings(), SC_EI_Portal_Schema::default_settings() )
		);
	}

	public static function hash_secret( string $value ): string {
		return hash_hmac( 'sha256', $value, wp_salt( 'auth' ) );
	}

	public static function email_hash( string $email ): string {
		return hash_hmac( 'sha256', strtolower( trim( sanitize_email( $email ) ) ), wp_salt( 'secure_auth' ) );
	}

	public static function request_ip_hash(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return $ip ? hash_hmac( 'sha256', $ip, wp_salt( 'nonce' ) ) : '';
	}

	public static function request_user_agent_hash(): string {
		$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		return $agent ? hash_hmac( 'sha256', $agent, wp_salt( 'nonce' ) ) : '';
	}

	private static function register_failed_activation( array $access ): void {
		global $wpdb;

		$settings = self::settings();
		$attempts = absint( $access['failed_attempts'] ) + 1;
		$limit = max( 1, absint( $settings['portal_max_failed_attempts'] ) );
		$locked_until = $attempts >= $limit
			? gmdate( 'Y-m-d H:i:s', time() + max( 1, absint( $settings['portal_lockout_minutes'] ) ) * MINUTE_IN_SECONDS )
			: null;
		$wpdb->update(
			SC_EI_Database::table( 'portal_access' ),
			array(
				'failed_attempts' => $attempts,
				'locked_until'    => $locked_until,
				'row_version'     => absint( $access['row_version'] ) + 1,
				'updated_at'      => current_time( 'mysql', true ),
			),
			array( 'id' => absint( $access['id'] ) ),
			array( '%d', '%s', '%d', '%s' ),
			array( '%d' )
		);
		self::record_event( 'invitation_failed', absint( $access['inquiry_id'] ), absint( $access['id'] ), 0, 'access', absint( $access['id'] ), 'rejected', array( 'attempts' => $attempts, 'locked' => (bool) $locked_until ) );
	}

	private static function random_token( int $bytes ): string {
		return rtrim( strtr( base64_encode( random_bytes( max( 24, $bytes ) ) ), '+/', '-_' ), '=' );
	}

	private static function sanitize_context( array $context ): array {
		$result = array();
		foreach ( array_slice( $context, 0, 40, true ) as $key => $value ) {
			$key = sanitize_key( (string) $key );
			if ( is_bool( $value ) || is_int( $value ) || is_float( $value ) ) {
				$result[ $key ] = $value;
			} elseif ( is_array( $value ) ) {
				$result[ $key ] = array_slice( array_map( 'sanitize_text_field', $value ), 0, 30 );
			} else {
				$result[ $key ] = mb_substr( sanitize_text_field( (string) $value ), 0, 500 );
			}
		}
		return $result;
	}

	private static function access_integer_fields(): array {
		return array(
			'inquiry_id', 'invited_by', 'revoked_by', 'failed_attempts', 'row_version',
		);
	}

	private static function session_integer_fields(): array {
		return array(
			'access_id', 'inquiry_id', 'activity_count', 'rotated_from_id',
		);
	}

	private static function formats( array $data, array $integer_fields ): array {
		return array_map(
			static fn( string $key ): string => in_array( $key, $integer_fields, true ) ? '%d' : '%s',
			array_keys( $data )
		);
	}
}
