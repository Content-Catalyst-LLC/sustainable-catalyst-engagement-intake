<?php
/**
 * Communication drafts, immutable history, delivery events, queues, and inquiry aggregates.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Communication_Repository {

	public static function find( int $id ): ?array {
		global $wpdb;

		$table = SC_EI_Database::table( 'communications' );
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
		return $row ?: null;
	}

	public static function find_by_dedupe_key( string $key ): ?array {
		global $wpdb;

		$key = sanitize_text_field( $key );
		if ( '' === $key ) {
			return null;
		}

		$table = SC_EI_Database::table( 'communications' );
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE dedupe_key = %s", $key ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);
		return $row ?: null;
	}

	public static function save_draft(
		int $inquiry_id,
		array $input,
		int $actor_user_id,
		int $communication_id = 0,
		int $expected_version = 0
	) {
		global $wpdb;

		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $inquiry ) {
			return new WP_Error( 'communication_inquiry_not_found', __( 'The inquiry could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$current = $communication_id ? self::find( $communication_id ) : null;
		if ( $communication_id && ( ! $current || absint( $current['inquiry_id'] ) !== $inquiry_id ) ) {
			return new WP_Error( 'communication_not_found', __( 'The communication draft could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( $current && ! in_array( $current['status'], array( 'draft', 'failed', 'approved' ), true ) ) {
			return new WP_Error( 'communication_immutable', __( 'Accepted, received, recorded, canceled, and suppressed communications cannot be edited.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( $current && absint( $current['row_version'] ) !== $expected_version ) {
			return new WP_Error( 'communication_conflict', __( 'This draft changed in another browser session. Reload it before saving.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$direction = SC_EI_Communication_Schema::sanitize_choice(
			(string) ( $input['direction'] ?? 'outbound' ),
			SC_EI_Communication_Schema::directions(),
			'outbound'
		);
		if ( ! in_array( $direction, array( 'outbound', 'internal' ), true ) ) {
			$direction = 'outbound';
		}

		$channel = SC_EI_Communication_Schema::sanitize_choice(
			(string) ( $input['channel'] ?? 'email' ),
			SC_EI_Communication_Schema::channels(),
			'email'
		);
		$type = SC_EI_Communication_Schema::sanitize_choice(
			(string) ( $input['communication_type'] ?? 'general_response' ),
			SC_EI_Communication_Schema::types(),
			'general_response'
		);
		$privacy = SC_EI_Communication_Schema::sanitize_choice(
			(string) ( $input['privacy_classification'] ?? 'private' ),
			SC_EI_Communication_Schema::privacy_levels(),
			'private'
		);

		$subject = SC_EI_Communication_Schema::sanitize_subject( (string) ( $input['subject'] ?? '' ) );
		$body = SC_EI_Communication_Schema::sanitize_body( (string) ( $input['body_text'] ?? '' ) );
		$recipient_name = sanitize_text_field( (string) ( $input['recipient_name'] ?? $inquiry['contact_name'] ) );
		$recipient_email = sanitize_email( (string) ( $input['recipient_email'] ?? $inquiry['contact_email'] ) );
		$cc = SC_EI_Communication_Schema::sanitize_emails( $input['cc'] ?? array(), 10 );

		if ( '' === $subject || '' === $body ) {
			return new WP_Error( 'communication_content_required', __( 'A subject and message body are required.', 'sustainable-catalyst-engagement-intake' ) );
		}
		if ( 'email' === $channel && ( ! $recipient_email || ! is_email( $recipient_email ) ) ) {
			return new WP_Error( 'communication_recipient_required', __( 'A valid recipient email is required for an email draft.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$sender = get_userdata( $actor_user_id );
		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() );
		$sender_name = $sender ? $sender->display_name : (string) $settings['communication_sender_name'];
		$sender_email = $sender && is_email( $sender->user_email )
			? $sender->user_email
			: (string) $settings['communication_sender_email'];

		$template_key = sanitize_key( (string) ( $input['template_key'] ?? '' ) );
		$template_version = absint( $input['template_version'] ?? 0 );
		$reply_to_id = absint( $input['reply_to_id'] ?? 0 );
		if ( $reply_to_id ) {
			$reply_to = self::find( $reply_to_id );
			if ( ! $reply_to || absint( $reply_to['inquiry_id'] ) !== $inquiry_id ) {
				$reply_to_id = 0;
			}
		}

		$now = current_time( 'mysql', true );
		$new_version = $current ? absint( $current['row_version'] ) + 1 : 0;
		$data = array(
			'inquiry_id'            => $inquiry_id,
			'public_id'             => $current['public_id'] ?? wp_generate_uuid4(),
			'thread_key'            => SC_EI_Communication_Schema::thread_key( $inquiry ),
			'reply_to_id'           => $reply_to_id ?: null,
			'direction'             => $direction,
			'channel'               => $channel,
			'communication_type'    => $type,
			'status'                => 'draft',
			'subject'               => $subject,
			'body_text'             => $body,
			'sender_user_id'        => $actor_user_id ?: null,
			'sender_name'           => sanitize_text_field( $sender_name ),
			'sender_email'          => sanitize_email( $sender_email ),
			'recipient_name'        => $recipient_name,
			'recipient_email'       => $recipient_email,
			'cc_json'               => wp_json_encode( $cc ),
			'template_key'          => $template_key,
			'template_version'      => $template_version,
			'is_automated'          => 0,
			'requires_approval'     => 1,
			'approved_by'           => null,
			'approved_at'           => null,
			'scheduled_for'         => null,
			'privacy_classification'=> $privacy,
			'message_hash'          => '',
			'dedupe_key'            => null,
			'metadata_json'         => wp_json_encode(
				array(
					'communication_schema_version' => SC_EI_COMMUNICATION_SCHEMA_VERSION,
					'draft_source'                 => sanitize_key( (string) ( $input['draft_source'] ?? 'communications_workspace' ) ),
				)
			),
			'row_version'           => $new_version,
			'updated_at'            => $now,
			'deleted_at'            => null,
		);

		$integer_fields = array(
			'inquiry_id', 'reply_to_id', 'sender_user_id', 'template_version', 'is_automated',
			'requires_approval', 'approved_by', 'row_version',
		);
		$formats = array_map(
			static fn( string $key ): string => in_array( $key, $integer_fields, true ) ? '%d' : '%s',
			array_keys( $data )
		);

		if ( $current ) {
			$updated = $wpdb->update(
				SC_EI_Database::table( 'communications' ),
				$data,
				array(
					'id'          => $communication_id,
					'row_version' => $expected_version,
				),
				$formats,
				array( '%d', '%d' )
			);
			if ( 1 !== $updated ) {
				return new WP_Error( 'communication_conflict', __( 'The draft changed before it could be saved. Reload and try again.', 'sustainable-catalyst-engagement-intake' ) );
			}
			$id = $communication_id;
			$event = 'draft_updated';
			$from_status = (string) $current['status'];
		} else {
			$data['created_at'] = $now;
			$formats[] = '%s';
			$inserted = $wpdb->insert( SC_EI_Database::table( 'communications' ), $data, $formats );
			if ( false === $inserted ) {
				return new WP_Error( 'communication_save_failed', __( 'The communication draft could not be saved.', 'sustainable-catalyst-engagement-intake' ) );
			}
			$id = (int) $wpdb->insert_id;
			$event = 'draft_created';
			$from_status = '';
		}

		self::record_event(
			$id,
			$inquiry_id,
			$event,
			$from_status,
			'draft',
			$actor_user_id,
			array(
				'channel'          => $channel,
				'direction'        => $direction,
				'type'             => $type,
				'template_key'     => $template_key,
				'template_version' => $template_version,
				'row_version'      => $new_version,
			)
		);

		SC_EI_Audit_Log::record(
			'communication_draft_saved',
			'Private communication draft saved.',
			array(
				'communication_id' => $id,
				'type'             => $type,
				'channel'          => $channel,
				'direction'        => $direction,
				'row_version'      => $new_version,
			),
			$inquiry_id,
			null,
			$actor_user_id
		);

		return self::find( $id );
	}

	public static function create_system_notification(
		int $inquiry_id,
		string $template_key,
		string $recipient_name,
		string $recipient_email,
		string $dedupe_key,
		array $metadata = array()
	) {
		global $wpdb;

		$existing = self::find_by_dedupe_key( $dedupe_key );
		if ( $existing ) {
			return $existing;
		}

		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		$template = SC_EI_Template_Repository::active( $template_key );
		if ( ! $inquiry || ! $template ) {
			return new WP_Error( 'notification_context_missing', __( 'The notification context or template is unavailable.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$rendered = SC_EI_Template_Repository::render( $template, $inquiry, 0 );
		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() );
		$now = current_time( 'mysql', true );
		$recipient_email = sanitize_email( $recipient_email );
		if ( ! is_email( $recipient_email ) ) {
			return new WP_Error( 'notification_recipient_invalid', __( 'The notification recipient email is invalid.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$notification_direction = 'external_sender' === sanitize_key( (string) ( $metadata['audience'] ?? '' ) )
			? 'outbound'
			: 'system';

		$data = array(
			'inquiry_id'            => $inquiry_id,
			'public_id'             => wp_generate_uuid4(),
			'thread_key'            => SC_EI_Communication_Schema::thread_key( $inquiry ),
			'reply_to_id'           => null,
			'direction'             => $notification_direction,
			'channel'               => 'email',
			'communication_type'    => sanitize_key( $template['communication_type'] ),
			'status'                => 'approved',
			'subject'               => $rendered['subject'],
			'body_text'             => $rendered['body'],
			'sender_user_id'        => null,
			'sender_name'           => sanitize_text_field( (string) $settings['communication_sender_name'] ),
			'sender_email'          => sanitize_email( (string) $settings['communication_sender_email'] ),
			'recipient_name'        => sanitize_text_field( $recipient_name ),
			'recipient_email'       => $recipient_email,
			'cc_json'               => '[]',
			'template_key'          => sanitize_key( $template['template_key'] ),
			'template_version'      => absint( $template['version'] ),
			'is_automated'          => 1,
			'requires_approval'     => 0,
			'approved_by'           => null,
			'approved_at'           => $now,
			'provider'              => '',
			'provider_message_id'   => '',
			'attempt_count'         => 0,
			'scheduled_for'         => null,
			'privacy_classification'=> 'private',
			'message_hash'          => '',
			'dedupe_key'            => sanitize_text_field( $dedupe_key ),
			'metadata_json'         => wp_json_encode(
				array_merge(
					array(
						'communication_schema_version' => SC_EI_COMMUNICATION_SCHEMA_VERSION,
						'automation_source'            => 'notification_service',
					),
					$metadata
				)
			),
			'row_version'           => 0,
			'created_at'            => $now,
			'updated_at'            => $now,
			'deleted_at'            => null,
		);

		$integer_fields = array(
			'inquiry_id', 'reply_to_id', 'sender_user_id', 'template_version', 'is_automated',
			'requires_approval', 'approved_by', 'attempt_count', 'row_version',
		);
		$formats = array_map(
			static fn( string $key ): string => in_array( $key, $integer_fields, true ) ? '%d' : '%s',
			array_keys( $data )
		);

		$inserted = $wpdb->insert( SC_EI_Database::table( 'communications' ), $data, $formats );
		if ( false === $inserted ) {
			$existing = self::find_by_dedupe_key( $dedupe_key );
			return $existing ?: new WP_Error( 'notification_create_failed', __( 'The notification record could not be created.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$id = (int) $wpdb->insert_id;
		self::record_event(
			$id,
			$inquiry_id,
			'notification_created',
			'',
			'approved',
			0,
			array(
				'template_key' => $template_key,
				'dedupe_key'   => $dedupe_key,
			)
		);

		return self::find( $id );
	}

	public static function record_interaction( int $inquiry_id, array $input, int $actor_user_id ) {
		global $wpdb;

		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $inquiry ) {
			return new WP_Error( 'communication_inquiry_not_found', __( 'The inquiry could not be found.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$direction = SC_EI_Communication_Schema::sanitize_choice(
			(string) ( $input['direction'] ?? 'inbound' ),
			SC_EI_Communication_Schema::directions(),
			'inbound'
		);
		if ( ! in_array( $direction, array( 'inbound', 'outbound', 'internal' ), true ) ) {
			$direction = 'inbound';
		}
		$channel = SC_EI_Communication_Schema::sanitize_choice(
			(string) ( $input['channel'] ?? 'email' ),
			SC_EI_Communication_Schema::channels(),
			'email'
		);
		$type = SC_EI_Communication_Schema::sanitize_choice(
			(string) ( $input['communication_type'] ?? 'manual_interaction' ),
			SC_EI_Communication_Schema::types(),
			'manual_interaction'
		);
		$privacy = SC_EI_Communication_Schema::sanitize_choice(
			(string) ( $input['privacy_classification'] ?? 'private' ),
			SC_EI_Communication_Schema::privacy_levels(),
			'private'
		);
		$subject = SC_EI_Communication_Schema::sanitize_subject( (string) ( $input['subject'] ?? '' ) );
		$body = SC_EI_Communication_Schema::sanitize_body( (string) ( $input['body_text'] ?? '' ) );

		if ( '' === $body ) {
			return new WP_Error( 'communication_body_required', __( 'Record a communication summary or message body.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$occurred_at = self::sanitize_local_datetime( $input['occurred_at_local'] ?? '' ) ?: current_time( 'mysql', true );
		$party_name = sanitize_text_field( (string) ( $input['party_name'] ?? $inquiry['contact_name'] ) );
		$party_email = sanitize_email( (string) ( $input['party_email'] ?? $inquiry['contact_email'] ) );
		$actor = get_userdata( $actor_user_id );
		$now = current_time( 'mysql', true );
		$status = 'inbound' === $direction ? 'received' : 'recorded';

		$data = array(
			'inquiry_id'            => $inquiry_id,
			'public_id'             => wp_generate_uuid4(),
			'thread_key'            => SC_EI_Communication_Schema::thread_key( $inquiry ),
			'reply_to_id'           => absint( $input['reply_to_id'] ?? 0 ) ?: null,
			'direction'             => $direction,
			'channel'               => $channel,
			'communication_type'    => $type,
			'status'                => $status,
			'subject'               => $subject ?: SC_EI_Communication_Schema::label( SC_EI_Communication_Schema::types(), $type ),
			'body_text'             => $body,
			'sender_user_id'        => 'inbound' === $direction ? null : ( $actor_user_id ?: null ),
			'sender_name'           => 'inbound' === $direction ? $party_name : ( $actor ? $actor->display_name : '' ),
			'sender_email'          => 'inbound' === $direction ? $party_email : ( $actor ? $actor->user_email : '' ),
			'recipient_name'        => 'inbound' === $direction ? ( $actor ? $actor->display_name : '' ) : $party_name,
			'recipient_email'       => 'inbound' === $direction ? ( $actor ? $actor->user_email : '' ) : $party_email,
			'cc_json'               => '[]',
			'template_key'          => '',
			'template_version'      => 0,
			'is_automated'          => 0,
			'requires_approval'     => 0,
			'approved_by'           => $actor_user_id ?: null,
			'approved_at'           => $now,
			'provider'              => 'manual_record',
			'provider_message_id'   => '',
			'attempt_count'         => 0,
			'occurred_at'           => $occurred_at,
			'scheduled_for'         => null,
			'privacy_classification'=> $privacy,
			'message_hash'          => hash( 'sha256', $subject . "\n" . $body ),
			'dedupe_key'            => null,
			'metadata_json'         => wp_json_encode(
				array(
					'communication_schema_version' => SC_EI_COMMUNICATION_SCHEMA_VERSION,
					'needs_response'               => empty( $input['needs_response'] ) ? 0 : 1,
					'recorded_manually'            => true,
				)
			),
			'row_version'           => 0,
			'created_at'            => $now,
			'updated_at'            => $now,
			'deleted_at'            => null,
		);

		$integer_fields = array(
			'inquiry_id', 'reply_to_id', 'sender_user_id', 'template_version', 'is_automated',
			'requires_approval', 'approved_by', 'attempt_count', 'row_version',
		);
		$formats = array_map(
			static fn( string $key ): string => in_array( $key, $integer_fields, true ) ? '%d' : '%s',
			array_keys( $data )
		);

		$inserted = $wpdb->insert( SC_EI_Database::table( 'communications' ), $data, $formats );
		if ( false === $inserted ) {
			return new WP_Error( 'communication_record_failed', __( 'The interaction could not be recorded.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$id = (int) $wpdb->insert_id;
		self::record_event( $id, $inquiry_id, 'interaction_recorded', '', $status, $actor_user_id, array( 'direction' => $direction, 'channel' => $channel ) );
		self::update_inquiry_aggregate( $inquiry_id, $direction, $occurred_at, ! empty( $input['needs_response'] ) );

		SC_EI_Audit_Log::record(
			'communication_interaction_recorded',
			'Communication interaction recorded manually.',
			array(
				'communication_id' => $id,
				'direction'        => $direction,
				'channel'          => $channel,
				'type'             => $type,
				'needs_response'   => empty( $input['needs_response'] ) ? 0 : 1,
			),
			$inquiry_id,
			null,
			$actor_user_id
		);

		return self::find( $id );
	}

	public static function transition(
		int $communication_id,
		string $new_status,
		int $actor_user_id,
		array $fields = array(),
		string $event_type = 'status_changed',
		array $context = array()
	): bool {
		global $wpdb;

		$current = self::find( $communication_id );
		if ( ! $current ) {
			return false;
		}

		$new_status = SC_EI_Communication_Schema::sanitize_choice(
			$new_status,
			SC_EI_Communication_Schema::statuses(),
			$current['status']
		);
		$fields['status'] = $new_status;
		$fields['updated_at'] = current_time( 'mysql', true );
		$integer_fields = array( 'attempt_count', 'approved_by', 'row_version' );
		$formats = array_map(
			static fn( string $key ): string => in_array( $key, $integer_fields, true ) ? '%d' : '%s',
			array_keys( $fields )
		);

		$updated = $wpdb->update(
			SC_EI_Database::table( 'communications' ),
			$fields,
			array( 'id' => $communication_id ),
			$formats,
			array( '%d' )
		);
		if ( false === $updated ) {
			return false;
		}

		self::record_event(
			$communication_id,
			absint( $current['inquiry_id'] ),
			$event_type,
			(string) $current['status'],
			$new_status,
			$actor_user_id,
			$context,
			(string) ( $fields['provider'] ?? $current['provider'] ),
			(string) ( $fields['provider_message_id'] ?? $current['provider_message_id'] ),
			(string) ( $fields['error_code'] ?? '' ),
			(string) ( $fields['error_message'] ?? '' )
		);
		return true;
	}

	public static function cancel_draft( int $communication_id, int $actor_user_id ): bool {
		$current = self::find( $communication_id );
		if ( ! $current || ! in_array( $current['status'], array( 'draft', 'failed', 'approved' ), true ) ) {
			return false;
		}

		$success = self::transition(
			$communication_id,
			'canceled',
			$actor_user_id,
			array( 'deleted_at' => current_time( 'mysql', true ) ),
			'draft_canceled'
		);
		if ( $success ) {
			SC_EI_Audit_Log::record(
				'communication_draft_canceled',
				'Communication draft canceled.',
				array( 'communication_id' => $communication_id ),
				absint( $current['inquiry_id'] ),
				null,
				$actor_user_id
			);
		}
		return $success;
	}

	public static function for_inquiry( int $inquiry_id, int $limit = 250, bool $include_canceled = false ): array {
		global $wpdb;

		$table = SC_EI_Database::table( 'communications' );
		$where = $include_canceled ? '' : "AND status <> 'canceled'";
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table}
				WHERE inquiry_id = %d {$where}
				ORDER BY COALESCE(occurred_at, accepted_at, created_at) DESC, id DESC
				LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$inquiry_id,
				max( 1, min( 1000, $limit ) )
			),
			ARRAY_A
		);
	}

	public static function events( int $communication_id, int $limit = 100 ): array {
		global $wpdb;

		$table = SC_EI_Database::table( 'communication_events' );
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT e.*, u.display_name AS actor_name, u.user_email AS actor_email
				FROM {$table} e
				LEFT JOIN {$wpdb->users} u ON u.ID = e.actor_user_id
				WHERE e.communication_id = %d
				ORDER BY e.created_at ASC, e.id ASC
				LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$communication_id,
				max( 1, min( 500, $limit ) )
			),
			ARRAY_A
		);
	}

	public static function query( array $args = array() ): array {
		global $wpdb;

		$args = wp_parse_args(
			$args,
			array(
				'view'               => 'history',
				'status'             => '',
				'direction'          => '',
				'channel'            => '',
				'communication_type' => '',
				'assignee'           => '',
				'search'             => '',
				'page'               => 1,
				'per_page'           => 25,
				'orderby'            => 'created_at',
				'order'              => 'DESC',
			)
		);

		$communications = SC_EI_Database::table( 'communications' );
		$inquiries = SC_EI_Database::table( 'inquiries' );
		$where = array( 'c.deleted_at IS NULL' );
		$params = array();
		$now = current_time( 'mysql', true );

		$view = sanitize_key( (string) $args['view'] );
		if ( 'drafts' === $view ) {
			$where[] = "c.status IN ('draft','approved')";
		} elseif ( 'failed' === $view ) {
			$where[] = "c.status = 'failed'";
		} elseif ( 'inbound' === $view ) {
			$where[] = "c.direction = 'inbound'";
		} elseif ( 'follow_up' === $view ) {
			$where[] = 'i.next_follow_up_at IS NOT NULL AND i.next_follow_up_at <= %s AND i.communication_status <> %s';
			$params[] = $now;
			$params[] = 'closed';
		} elseif ( 'notifications' === $view ) {
			$where[] = "c.direction = 'system'";
		}

		$status = sanitize_key( (string) $args['status'] );
		if ( array_key_exists( $status, SC_EI_Communication_Schema::statuses() ) ) {
			$where[] = 'c.status = %s';
			$params[] = $status;
		}
		$direction = sanitize_key( (string) $args['direction'] );
		if ( array_key_exists( $direction, SC_EI_Communication_Schema::directions() ) ) {
			$where[] = 'c.direction = %s';
			$params[] = $direction;
		}
		$channel = sanitize_key( (string) $args['channel'] );
		if ( array_key_exists( $channel, SC_EI_Communication_Schema::channels() ) ) {
			$where[] = 'c.channel = %s';
			$params[] = $channel;
		}
		$type = sanitize_key( (string) $args['communication_type'] );
		if ( array_key_exists( $type, SC_EI_Communication_Schema::types() ) ) {
			$where[] = 'c.communication_type = %s';
			$params[] = $type;
		}

		$assignee = sanitize_text_field( (string) $args['assignee'] );
		if ( 'me' === $assignee ) {
			$where[] = 'i.assigned_user_id = %d';
			$params[] = get_current_user_id();
		} elseif ( 'unassigned' === $assignee ) {
			$where[] = '(i.assigned_user_id IS NULL OR i.assigned_user_id = 0)';
		} elseif ( absint( $assignee ) ) {
			$where[] = 'i.assigned_user_id = %d';
			$params[] = absint( $assignee );
		}

		$search = sanitize_text_field( (string) $args['search'] );
		if ( '' !== $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$where[] = '(i.reference LIKE %s OR i.contact_name LIKE %s OR i.contact_email LIKE %s OR i.organization LIKE %s OR c.subject LIKE %s OR c.body_text LIKE %s OR c.recipient_email LIKE %s)';
			array_push( $params, $like, $like, $like, $like, $like, $like, $like );
		}

		$allowed_orderby = array(
			'created_at'         => 'c.created_at',
			'accepted_at'        => 'c.accepted_at',
			'status'             => 'c.status',
			'direction'          => 'c.direction',
			'channel'            => 'c.channel',
			'communication_type' => 'c.communication_type',
			'reference'          => 'i.reference',
			'next_follow_up_at'  => 'i.next_follow_up_at',
		);
		$orderby = $allowed_orderby[ sanitize_key( (string) $args['orderby'] ) ] ?? 'c.created_at';
		$order = 'ASC' === strtoupper( (string) $args['order'] ) ? 'ASC' : 'DESC';
		$page = max( 1, absint( $args['page'] ) );
		$per_page = max( 1, min( 100, absint( $args['per_page'] ) ) );
		$offset = ( $page - 1 ) * $per_page;
		$where_sql = implode( ' AND ', $where );

		$joins = "INNER JOIN {$inquiries} i ON i.id = c.inquiry_id
			LEFT JOIN {$wpdb->users} assignee ON assignee.ID = i.assigned_user_id
			LEFT JOIN {$wpdb->users} sender ON sender.ID = c.sender_user_id";
		$count_sql = "SELECT COUNT(*) FROM {$communications} c {$joins} WHERE {$where_sql}";
		$data_sql = "SELECT c.*, i.reference, i.contact_name, i.contact_email, i.organization,
			i.communication_status, i.next_follow_up_at, i.do_not_email, i.assigned_user_id,
			assignee.display_name AS assigned_name, sender.display_name AS sender_user_name
			FROM {$communications} c {$joins}
			WHERE {$where_sql}
			ORDER BY {$orderby} {$order}, c.id {$order}
			LIMIT %d OFFSET %d";

		$count_query = $params ? $wpdb->prepare( $count_sql, $params ) : $count_sql;
		$total = (int) $wpdb->get_var( $count_query );
		$data_params = array_merge( $params, array( $per_page, $offset ) );
		$items = (array) $wpdb->get_results( $wpdb->prepare( $data_sql, $data_params ), ARRAY_A );

		return array(
			'items'       => $items,
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => max( 1, (int) ceil( $total / $per_page ) ),
		);
	}

	public static function metrics(): array {
		global $wpdb;

		$communications = SC_EI_Database::table( 'communications' );
		$inquiries = SC_EI_Database::table( 'inquiries' );
		$now = current_time( 'mysql', true );
		$today = gmdate( 'Y-m-d 00:00:00' );

		$row = (array) $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					SUM(CASE WHEN c.status = 'draft' THEN 1 ELSE 0 END) AS drafts,
					SUM(CASE WHEN c.status = 'failed' THEN 1 ELSE 0 END) AS failed,
					SUM(CASE WHEN c.status = 'accepted' AND c.accepted_at >= %s THEN 1 ELSE 0 END) AS accepted_today,
					SUM(CASE WHEN c.direction = 'inbound' AND c.created_at >= %s THEN 1 ELSE 0 END) AS inbound_today,
					SUM(CASE WHEN c.direction = 'system' AND c.status = 'accepted' AND c.accepted_at >= %s THEN 1 ELSE 0 END) AS notifications_today,
					SUM(CASE WHEN c.status = 'suppressed' THEN 1 ELSE 0 END) AS suppressed
				FROM {$communications} c
				WHERE c.deleted_at IS NULL", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$today,
				$today,
				$today
			),
			ARRAY_A
		);
		$row['follow_up_due'] = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$inquiries}
				WHERE next_follow_up_at IS NOT NULL
				AND next_follow_up_at <= %s
				AND communication_status <> %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$now,
				'closed'
			)
		);
		$row['unread_inbound'] = (int) $wpdb->get_var( "SELECT COALESCE(SUM(unread_inbound_count),0) FROM {$inquiries}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row['do_not_email'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$inquiries} WHERE do_not_email = 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		foreach ( array( 'drafts', 'failed', 'accepted_today', 'inbound_today', 'notifications_today', 'suppressed', 'follow_up_due', 'unread_inbound', 'do_not_email' ) as $key ) {
			$row[ $key ] = absint( $row[ $key ] ?? 0 );
		}
		return $row;
	}

	public static function update_thread_state( int $inquiry_id, array $input, int $actor_user_id ): bool {
		global $wpdb;

		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $inquiry ) {
			return false;
		}

		$status = SC_EI_Communication_Schema::sanitize_choice(
			(string) ( $input['communication_status'] ?? $inquiry['communication_status'] ),
			SC_EI_Communication_Schema::communication_states(),
			'open'
		);
		$follow_up = self::sanitize_local_datetime( $input['next_follow_up_local'] ?? '' );
		$do_not_email = empty( $input['do_not_email'] ) ? 0 : 1;
		$reason = sanitize_textarea_field( (string) ( $input['do_not_email_reason'] ?? '' ) );
		if ( $do_not_email && '' === trim( $reason ) ) {
			return false;
		}
		if ( ! $do_not_email ) {
			$reason = '';
		}

		$updated = $wpdb->update(
			SC_EI_Database::table( 'inquiries' ),
			array(
				'communication_status' => $status,
				'next_follow_up_at'     => $follow_up,
				'do_not_email'          => $do_not_email,
				'do_not_email_reason'   => $reason,
				'unread_inbound_count'  => empty( $input['mark_inbound_read'] ) ? absint( $inquiry['unread_inbound_count'] ) : 0,
				'communication_version' => absint( $inquiry['communication_version'] ) + 1,
				'updated_at'            => current_time( 'mysql', true ),
			),
			array( 'id' => $inquiry_id ),
			array( '%s', '%s', '%d', '%s', '%d', '%d', '%s' ),
			array( '%d' )
		);
		if ( false === $updated ) {
			return false;
		}

		SC_EI_Audit_Log::record(
			'communication_thread_state_updated',
			'Communication status, follow-up, or email suppression state updated.',
			array(
				'old_status'        => $inquiry['communication_status'],
				'new_status'        => $status,
				'old_follow_up'     => $inquiry['next_follow_up_at'],
				'new_follow_up'     => $follow_up,
				'old_do_not_email'  => absint( $inquiry['do_not_email'] ),
				'new_do_not_email'  => $do_not_email,
				'marked_read'       => empty( $input['mark_inbound_read'] ) ? 0 : 1,
			),
			$inquiry_id,
			null,
			$actor_user_id
		);
		return true;
	}

	public static function mark_notification_time( int $inquiry_id ): void {
		global $wpdb;
		$wpdb->update(
			SC_EI_Database::table( 'inquiries' ),
			array(
				'last_notification_at' => current_time( 'mysql', true ),
				'updated_at'           => current_time( 'mysql', true ),
			),
			array( 'id' => $inquiry_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	public static function record_event(
		int $communication_id,
		int $inquiry_id,
		string $event_type,
		string $from_status,
		string $to_status,
		int $actor_user_id = 0,
		array $context = array(),
		string $provider = '',
		string $provider_message_id = '',
		string $error_code = '',
		string $error_message = ''
	): int {
		global $wpdb;

		$wpdb->insert(
			SC_EI_Database::table( 'communication_events' ),
			array(
				'communication_id'  => $communication_id,
				'inquiry_id'        => $inquiry_id,
				'actor_user_id'     => $actor_user_id ?: null,
				'event_type'        => sanitize_key( $event_type ),
				'from_status'       => sanitize_key( $from_status ),
				'to_status'         => sanitize_key( $to_status ),
				'provider'          => sanitize_text_field( $provider ),
				'provider_message_id'=> sanitize_text_field( $provider_message_id ),
				'error_code'        => sanitize_key( $error_code ),
				'error_message'     => sanitize_textarea_field( $error_message ),
				'context_json'      => wp_json_encode( $context ),
				'created_at'        => current_time( 'mysql', true ),
			),
			array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
		return (int) $wpdb->insert_id;
	}

	public static function update_inquiry_aggregate(
		int $inquiry_id,
		string $direction,
		string $occurred_at,
		bool $needs_response = false
	): void {
		global $wpdb;

		$inquiry = SC_EI_Inquiry_Repository::find( $inquiry_id );
		if ( ! $inquiry ) {
			return;
		}

		$data = array(
			'last_communication_at' => $occurred_at,
			'communication_count'   => absint( $inquiry['communication_count'] ) + 1,
			'communication_version' => absint( $inquiry['communication_version'] ) + 1,
			'updated_at'            => current_time( 'mysql', true ),
		);
		if ( 'inbound' === $direction ) {
			$data['last_inbound_at'] = $occurred_at;
			$data['unread_inbound_count'] = absint( $inquiry['unread_inbound_count'] ) + 1;
			if ( $needs_response ) {
				$data['communication_status'] = 'waiting_on_internal';
			}
		} elseif ( 'outbound' === $direction ) {
			$data['last_outbound_at'] = $occurred_at;
		}

		$integer_fields = array( 'communication_count', 'communication_version', 'unread_inbound_count' );
		$formats = array_map(
			static fn( string $key ): string => in_array( $key, $integer_fields, true ) ? '%d' : '%s',
			array_keys( $data )
		);
		$wpdb->update(
			SC_EI_Database::table( 'inquiries' ),
			$data,
			array( 'id' => $inquiry_id ),
			$formats,
			array( '%d' )
		);
	}

	private static function sanitize_local_datetime( $value ): ?string {
		$value = sanitize_text_field( (string) $value );
		if ( '' === $value ) {
			return null;
		}
		try {
			$date = new DateTimeImmutable( $value, wp_timezone() );
			return $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
		} catch ( Throwable $exception ) {
			return null;
		}
	}
}
