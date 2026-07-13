<?php
/**
 * WordPress privacy exporter and eraser.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Privacy {

	public static function register(): void {
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'exporters' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'erasers' ) );
	}

	public static function exporters( array $exporters ): array {
		$exporters['sc-engagement-intake'] = array(
			'exporter_friendly_name' => __( 'Sustainable Catalyst Engagement Intake', 'sustainable-catalyst-engagement-intake' ),
			'callback'               => array( __CLASS__, 'export_by_email' ),
		);
		return $exporters;
	}

	public static function erasers( array $erasers ): array {
		$erasers['sc-engagement-intake'] = array(
			'eraser_friendly_name' => __( 'Sustainable Catalyst Engagement Intake', 'sustainable-catalyst-engagement-intake' ),
			'callback'             => array( __CLASS__, 'erase_by_email' ),
		);
		return $erasers;
	}

	public static function export_by_email( string $email_address, int $page = 1 ): array {
		global $wpdb;

		$table   = SC_EI_Database::table( 'inquiries' );
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE contact_email = %s ORDER BY created_at ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				sanitize_email( $email_address )
			),
			ARRAY_A
		);

		$data = array();
		foreach ( $results as $row ) {
			$item_data = array();
			foreach (
				array(
					'reference'          => 'Inquiry reference',
					'inquiry_type'       => 'Inquiry type',
					'status'             => 'Status',
					'contact_name'       => 'Name',
					'contact_email'      => 'Email',
					'organization'       => 'Organization',
					'role_title'         => 'Role',
					'subject'            => 'Subject',
					'message'            => 'Message',
					'project_summary'    => 'Project summary',
					'desired_outcome'    => 'Desired outcome',
					'service_interest'   => 'Service interest',
					'budget_range'       => 'Budget range',
					'desired_start_date' => 'Desired start date',
					'deadline_date'      => 'Deadline',
					'preferred_contact_method' => 'Preferred contact method',
					'teams_email'        => 'Microsoft Teams email',
					'phone_number'       => 'Phone number',
					'timezone'           => 'Time zone',
					'city'               => 'City',
					'country'            => 'Country',
					'meeting_request'    => 'Microsoft Teams meeting request',
					'preferred_weekdays' => 'Preferred weekdays',
					'preferred_time_windows' => 'Preferred time windows',
					'preferred_duration' => 'Preferred duration',
					'participant_count'  => 'Participant count',
					'participant_emails' => 'Participant emails',
					'accessibility_needs'=> 'Accessibility needs',
					'calendar_invite_consent' => 'Calendar invitation consent',
					'scheduling_notes'   => 'Scheduling notes',
					'scheduling_status'  => 'Scheduling status',
					'created_at'         => 'Submitted at',
					'updated_at'         => 'Last updated',
				) as $key => $label
			) {
				if ( isset( $row[ $key ] ) && '' !== (string) $row[ $key ] ) {
					$item_data[] = array(
						'name'  => $label,
						'value' => (string) $row[ $key ],
					);
				}
			}

			$data[] = array(
				'group_id'    => 'sc-engagement-intake',
				'group_label' => __( 'Engagement Intake', 'sustainable-catalyst-engagement-intake' ),
				'item_id'     => 'sc-ei-' . $row['id'],
				'data'        => $item_data,
			);
		}

		return array(
			'data' => $data,
			'done' => true,
		);
	}

	public static function erase_by_email( string $email_address, int $page = 1 ): array {
		global $wpdb;

		$table = SC_EI_Database::table( 'inquiries' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, reference FROM {$table} WHERE contact_email = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				sanitize_email( $email_address )
			),
			ARRAY_A
		);

		$removed = false;
		foreach ( $rows as $row ) {
			$anonymous_email = 'deleted+' . absint( $row['id'] ) . '@example.invalid';
			$updated         = $wpdb->update(
				$table,
				array(
					'contact_name'    => '',
					'contact_email'   => $anonymous_email,
					'organization'    => '',
					'role_title'      => '',
					'teams_email'     => '',
					'phone_number'    => '',
					'city'            => '',
					'country'         => '',
					'participant_emails' => '[]',
					'accessibility_needs' => '',
					'scheduling_notes' => '',
					'message'         => '[Personal data erased through WordPress privacy tools.]',
					'project_summary' => '',
					'desired_outcome' => '',
					'relevant_links'  => '[]',
					'metadata_json'   => '{}',
					'updated_at'      => current_time( 'mysql', true ),
				),
				array( 'id' => absint( $row['id'] ) ),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);

			if ( false !== $updated ) {
				$removed = true;
				SC_EI_Audit_Log::record(
					'personal_data_erased',
					'Personal data erased through WordPress privacy tools.',
					array( 'reference' => $row['reference'] ),
					absint( $row['id'] )
				);
			}
		}

		return array(
			'items_removed'  => $removed,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}
}
