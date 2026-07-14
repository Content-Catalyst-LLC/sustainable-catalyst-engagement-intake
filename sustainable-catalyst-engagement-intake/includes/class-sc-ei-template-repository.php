<?php
/**
 * Versioned plain-text communication templates.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Template_Repository {

	public static function seed_defaults(): void {
		global $wpdb;

		$table = SC_EI_Database::table( 'communication_templates' );
		foreach ( SC_EI_Communication_Schema::default_templates() as $key => $template ) {
			$exists = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE template_key = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$key
				)
			);
			if ( $exists ) {
				continue;
			}

			$now = current_time( 'mysql', true );
			$data = array(
				'template_key'       => sanitize_key( $key ),
				'version'            => 1,
				'name'               => sanitize_text_field( $template['name'] ),
				'communication_type' => SC_EI_Communication_Schema::sanitize_choice(
					(string) $template['communication_type'],
					SC_EI_Communication_Schema::types(),
					'general_response'
				),
				'subject_template'   => SC_EI_Communication_Schema::sanitize_subject( (string) $template['subject'] ),
				'body_template'      => SC_EI_Communication_Schema::sanitize_body( (string) $template['body'] ),
				'status'             => 'active',
				'is_system'          => empty( $template['is_system'] ) ? 0 : 1,
				'created_by'         => null,
				'created_at'         => $now,
				'updated_at'         => $now,
			);
			$integer_fields = array( 'version', 'is_system', 'created_by' );
			$formats = array_map(
				static fn( string $field ): string => in_array( $field, $integer_fields, true ) ? '%d' : '%s',
				array_keys( $data )
			);
			$wpdb->insert( $table, $data, $formats );
		}
	}

	public static function active_templates(): array {
		global $wpdb;

		$table = SC_EI_Database::table( 'communication_templates' );
		$sql = "SELECT t.*
			FROM {$table} t
			INNER JOIN (
				SELECT template_key, MAX(version) AS version
				FROM {$table}
				WHERE status = 'active'
				GROUP BY template_key
			) latest ON latest.template_key = t.template_key AND latest.version = t.version
			WHERE t.status = 'active'
			ORDER BY t.is_system ASC, t.name ASC";

		$rows = (array) $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$result = array();
		foreach ( $rows as $row ) {
			$result[ $row['template_key'] ] = $row;
		}
		return $result;
	}

	public static function active( string $key ): ?array {
		$templates = self::active_templates();
		$key = sanitize_key( $key );
		return $templates[ $key ] ?? null;
	}

	public static function versions( string $key ): array {
		global $wpdb;

		$table = SC_EI_Database::table( 'communication_templates' );
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE template_key = %s ORDER BY version DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				sanitize_key( $key )
			),
			ARRAY_A
		);
	}

	public static function create_version(
		string $key,
		string $name,
		string $type,
		string $subject,
		string $body,
		int $actor_user_id,
		bool $is_system = false
	) {
		global $wpdb;

		$key = sanitize_key( $key );
		if ( ! preg_match( '/^[a-z0-9][a-z0-9_-]{2,79}$/', $key ) ) {
			return new WP_Error( 'invalid_template_key', __( 'Use a template key containing 3–80 lowercase letters, numbers, hyphens, or underscores.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$name = sanitize_text_field( $name );
		$type = SC_EI_Communication_Schema::sanitize_choice( $type, SC_EI_Communication_Schema::types(), 'general_response' );
		$subject = SC_EI_Communication_Schema::sanitize_subject( $subject );
		$body = SC_EI_Communication_Schema::sanitize_body( $body );

		if ( '' === $name || '' === $subject || '' === $body ) {
			return new WP_Error( 'template_fields_required', __( 'Template name, subject, and body are required.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$unknown = self::unknown_variables( $subject . "\n" . $body );
		if ( $unknown ) {
			return new WP_Error(
				'unknown_template_variable',
				sprintf(
					__( 'Unknown template variables: %s', 'sustainable-catalyst-engagement-intake' ),
					implode( ', ', $unknown )
				)
			);
		}

		$table = SC_EI_Database::table( 'communication_templates' );
		$version = 1 + (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(MAX(version), 0) FROM {$table} WHERE template_key = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$key
			)
		);
		$now = current_time( 'mysql', true );

		$wpdb->query( 'START TRANSACTION' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->update(
			$table,
			array( 'status' => 'archived', 'updated_at' => $now ),
			array( 'template_key' => $key, 'status' => 'active' ),
			array( '%s', '%s' ),
			array( '%s', '%s' )
		);

		$data = array(
			'template_key'       => $key,
			'version'            => $version,
			'name'               => $name,
			'communication_type' => $type,
			'subject_template'   => $subject,
			'body_template'      => $body,
			'status'             => 'active',
			'is_system'          => $is_system ? 1 : 0,
			'created_by'         => $actor_user_id ?: null,
			'created_at'         => $now,
			'updated_at'         => $now,
		);
		$integer_fields = array( 'version', 'is_system', 'created_by' );
		$formats = array_map(
			static fn( string $field ): string => in_array( $field, $integer_fields, true ) ? '%d' : '%s',
			array_keys( $data )
		);
		$inserted = $wpdb->insert( $table, $data, $formats );

		if ( false === $inserted ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			return new WP_Error( 'template_save_failed', __( 'The template version could not be saved.', 'sustainable-catalyst-engagement-intake' ) );
		}

		$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		SC_EI_Audit_Log::record(
			'communication_template_version_created',
			'Versioned communication template created.',
			array(
				'template_key' => $key,
				'version'      => $version,
				'type'         => $type,
				'is_system'    => $is_system,
			),
			null,
			null,
			$actor_user_id
		);

		return array(
			'id'           => (int) $wpdb->insert_id,
			'template_key' => $key,
			'version'      => $version,
		);
	}

	public static function render( array $template, array $inquiry, int $actor_user_id = 0 ): array {
		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() );
		$reviewer = ! empty( $inquiry['assigned_user_id'] ) ? get_userdata( absint( $inquiry['assigned_user_id'] ) ) : false;
		$contact_name = trim( (string) ( $inquiry['contact_name'] ?? '' ) );
		$first_name = $contact_name ? preg_split( '/\s+/', $contact_name )[0] : '';
		$timezone = (string) ( $inquiry['scheduled_timezone'] ?: $inquiry['timezone'] ?? '' );
		$scheduled_start = '';
		if ( ! empty( $inquiry['scheduled_start_utc'] ) ) {
			try {
				$zone = $timezone && in_array( $timezone, timezone_identifiers_list(), true )
					? new DateTimeZone( $timezone )
					: wp_timezone();
				$scheduled_start = ( new DateTimeImmutable( $inquiry['scheduled_start_utc'], new DateTimeZone( 'UTC' ) ) )
					->setTimezone( $zone )
					->format( 'F j, Y g:i a' );
			} catch ( Throwable $exception ) {
				$scheduled_start = (string) $inquiry['scheduled_start_utc'];
			}
		}

		$variables = array(
			'{contact_name}'          => $contact_name,
			'{first_name}'            => $first_name,
			'{reference}'             => (string) ( $inquiry['reference'] ?? '' ),
			'{organization}'          => (string) ( $inquiry['organization'] ?? '' ),
			'{subject}'               => (string) ( $inquiry['subject'] ?? '' ),
			'{inquiry_type}'          => SC_EI_Statuses::inquiry_types()[ $inquiry['inquiry_type'] ?? '' ] ?? (string) ( $inquiry['inquiry_type'] ?? '' ),
			'{service_interest}'      => ucwords( str_replace( '_', ' ', (string) ( $inquiry['service_interest'] ?? '' ) ) ),
			'{review_stage}'          => SC_EI_Review_Schema::label( SC_EI_Review_Schema::stages(), (string) ( $inquiry['review_stage'] ?? 'intake' ) ),
			'{fit_decision}'          => SC_EI_Review_Schema::label( SC_EI_Review_Schema::fit_decisions(), (string) ( $inquiry['fit_decision'] ?? 'undecided' ) ),
			'{recommended_next_step}' => SC_EI_Review_Schema::label( SC_EI_Review_Schema::next_steps(), (string) ( $inquiry['recommended_next_step'] ?? 'review' ) ),
			'{teams_duration}'        => (string) absint( $inquiry['preferred_duration'] ?: $settings['default_teams_duration'] ?? 20 ),
			'{teams_meeting_url}'     => (string) ( $inquiry['teams_meeting_url'] ?? '' ),
			'{scheduled_start}'       => $scheduled_start,
			'{scheduled_timezone}'    => $timezone,
			'{site_name}'             => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			'{site_url}'              => home_url( '/' ),
			'{sender_name}'           => (string) ( $settings['communication_sender_name'] ?: get_bloginfo( 'name' ) ),
			'{reply_email}'           => (string) ( $settings['communication_reply_to_email'] ?: $settings['communication_sender_email'] ?: get_option( 'admin_email' ) ),
			'{reviewer_name}'         => $reviewer ? $reviewer->display_name : __( 'Unassigned', 'sustainable-catalyst-engagement-intake' ),
			'{review_due}'            => ! empty( $inquiry['review_due_at'] ) ? get_date_from_gmt( $inquiry['review_due_at'], 'F j, Y g:i a' ) : __( 'No due date', 'sustainable-catalyst-engagement-intake' ),
			'{next_follow_up}'        => ! empty( $inquiry['next_follow_up_at'] ) ? get_date_from_gmt( $inquiry['next_follow_up_at'], 'F j, Y g:i a' ) : __( 'No follow-up date', 'sustainable-catalyst-engagement-intake' ),
			'{lifecycle_stage}'       => SC_EI_Lifecycle_Schema::public_stage_labels()[ SC_EI_Lifecycle_Schema::sanitize_stage( (string) ( $inquiry['lifecycle_stage'] ?? 'new_inquiry' ) ) ] ?? __( 'Under Review', 'sustainable-catalyst-engagement-intake' ),
			'{lifecycle_next_action}' => (string) ( $inquiry['next_action'] ?? '' ),
			'{lifecycle_task}'        => (string) ( $inquiry['_lifecycle_task'] ?? '' ),
			'{lifecycle_task_due}'    => (string) ( $inquiry['_lifecycle_task_due'] ?? '' ),
		);

		$subject = strtr( (string) $template['subject_template'], $variables );
		$body = strtr( (string) $template['body_template'], $variables );

		return array(
			'subject' => SC_EI_Communication_Schema::sanitize_subject( $subject ),
			'body'    => SC_EI_Communication_Schema::sanitize_body( $body ),
			'variables' => $variables,
		);
	}

	private static function unknown_variables( string $content ): array {
		preg_match_all( '/\{[a-z0-9_]+\}/i', $content, $matches );
		$allowed = array_keys( SC_EI_Communication_Schema::template_variables() );
		return array_values( array_diff( array_unique( $matches[0] ), $allowed ) );
	}
}
