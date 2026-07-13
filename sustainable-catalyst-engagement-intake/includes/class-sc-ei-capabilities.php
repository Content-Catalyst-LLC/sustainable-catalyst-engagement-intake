<?php
/**
 * Roles and capabilities.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Capabilities {

	public const ALL = array(
		'sc_intake_view',
		'sc_intake_review',
		'sc_intake_manage_review',
		'sc_intake_assign_inquiries',
		'sc_intake_manage_review_priority',
		'sc_intake_escalate_review',
		'sc_intake_bulk_review_actions',
		'sc_intake_export_review_packet',
		'sc_intake_view_fit_assessments',
		'sc_intake_create_fit_assessments',
		'sc_intake_review_fit_assessments',
		'sc_intake_finalize_fit_assessments',
		'sc_intake_apply_fit_to_review',
		'sc_intake_manage_fit_settings',
		'sc_intake_export_fit_assessments',
		'sc_intake_download_files',
		'sc_intake_release_files',
		'sc_intake_manage_file_retention',
		'sc_intake_manage_scanner',
		'sc_intake_bulk_file_actions',
		'sc_intake_view_file_audit',
		'sc_intake_add_notes',
		'sc_intake_change_status',
		'sc_intake_communicate',
		'sc_intake_view_communications',
		'sc_intake_compose_communications',
		'sc_intake_send_communications',
		'sc_intake_record_inbound',
		'sc_intake_manage_templates',
		'sc_intake_manage_notifications',
		'sc_intake_export_communications',
		'sc_intake_view_privacy_center',
		'sc_intake_manage_privacy_requests',
		'sc_intake_manage_consent',
		'sc_intake_manage_legal_holds',
		'sc_intake_manage_retention_policies',
		'sc_intake_approve_retention_actions',
		'sc_intake_execute_retention_actions',
		'sc_intake_export_privacy_data',
		'sc_intake_export',
		'sc_intake_delete',
		'sc_intake_manage_settings',
	);

	private const REVIEWER = array(
		'read',
		'sc_intake_view',
		'sc_intake_review',
		'sc_intake_manage_review',
		'sc_intake_manage_review_priority',
		'sc_intake_escalate_review',
		'sc_intake_export_review_packet',
		'sc_intake_view_fit_assessments',
		'sc_intake_create_fit_assessments',
		'sc_intake_review_fit_assessments',
		'sc_intake_export_fit_assessments',
		'sc_intake_add_notes',
		'sc_intake_change_status',
		'sc_intake_communicate',
		'sc_intake_view_communications',
		'sc_intake_compose_communications',
		'sc_intake_send_communications',
		'sc_intake_record_inbound',
		'sc_intake_export_communications',
		'sc_intake_view_privacy_center',
	);

	private const MANAGER = array(
		'read',
		'sc_intake_view',
		'sc_intake_review',
		'sc_intake_manage_review',
		'sc_intake_assign_inquiries',
		'sc_intake_manage_review_priority',
		'sc_intake_escalate_review',
		'sc_intake_bulk_review_actions',
		'sc_intake_export_review_packet',
		'sc_intake_view_fit_assessments',
		'sc_intake_create_fit_assessments',
		'sc_intake_review_fit_assessments',
		'sc_intake_finalize_fit_assessments',
		'sc_intake_apply_fit_to_review',
		'sc_intake_manage_fit_settings',
		'sc_intake_export_fit_assessments',
		'sc_intake_download_files',
		'sc_intake_release_files',
		'sc_intake_manage_file_retention',
		'sc_intake_manage_scanner',
		'sc_intake_bulk_file_actions',
		'sc_intake_view_file_audit',
		'sc_intake_add_notes',
		'sc_intake_change_status',
		'sc_intake_communicate',
		'sc_intake_view_communications',
		'sc_intake_compose_communications',
		'sc_intake_send_communications',
		'sc_intake_record_inbound',
		'sc_intake_manage_templates',
		'sc_intake_manage_notifications',
		'sc_intake_export_communications',
		'sc_intake_view_privacy_center',
		'sc_intake_manage_privacy_requests',
		'sc_intake_manage_consent',
		'sc_intake_manage_legal_holds',
		'sc_intake_manage_retention_policies',
		'sc_intake_approve_retention_actions',
		'sc_intake_execute_retention_actions',
		'sc_intake_export_privacy_data',
		'sc_intake_export',
	);

	public static function install(): void {
		$administrator = get_role( 'administrator' );
		if ( $administrator ) {
			foreach ( self::ALL as $capability ) {
				$administrator->add_cap( $capability );
			}
		}

		add_role(
			'sc_engagement_reviewer',
			__( 'Engagement Reviewer', 'sustainable-catalyst-engagement-intake' ),
			array_fill_keys( self::REVIEWER, true )
		);
		add_role(
			'sc_engagement_manager',
			__( 'Engagement Manager', 'sustainable-catalyst-engagement-intake' ),
			array_fill_keys( self::MANAGER, true )
		);

		$reviewer = get_role( 'sc_engagement_reviewer' );
		if ( $reviewer ) {
			foreach ( self::REVIEWER as $capability ) {
				$reviewer->add_cap( $capability );
			}
		}

		$manager = get_role( 'sc_engagement_manager' );
		if ( $manager ) {
			foreach ( self::MANAGER as $capability ) {
				$manager->add_cap( $capability );
			}
		}
	}

	public static function uninstall(): void {
		foreach ( array( 'administrator', 'sc_engagement_reviewer', 'sc_engagement_manager' ) as $role_name ) {
			$role = get_role( $role_name );
			if ( $role ) {
				foreach ( self::ALL as $capability ) {
					$role->remove_cap( $capability );
				}
			}
		}

		remove_role( 'sc_engagement_reviewer' );
		remove_role( 'sc_engagement_manager' );
	}
}
