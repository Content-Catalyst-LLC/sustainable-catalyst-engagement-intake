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
		'sc_intake_view_sender_portal',
		'sc_intake_manage_sender_portal',
		'sc_intake_issue_portal_invites',
		'sc_intake_post_portal_messages',
		'sc_intake_revoke_portal_access',
		'sc_intake_manage_portal_settings',
		'sc_intake_export_portal_audit',
		'sc_intake_view_portal_recovery',
		'sc_intake_manage_portal_recovery',
		'sc_intake_view_workflow',
		'sc_intake_manage_workflow',
		'sc_intake_create_meeting_offers',
		'sc_intake_publish_meeting_offers',
		'sc_intake_finalize_meetings',
		'sc_intake_create_proposals',
		'sc_intake_publish_proposals',
		'sc_intake_record_contracts',
		'sc_intake_export_workflow',
		'sc_intake_view_graph',
		'sc_intake_manage_graph_settings',
		'sc_intake_create_graph_events',
		'sc_intake_reconcile_graph_events',
		'sc_intake_cancel_graph_events',
		'sc_intake_export_graph_operations',
		'sc_intake_view_engagements',
		'sc_intake_create_engagement_handoffs',
		'sc_intake_manage_engagements',
		'sc_intake_activate_engagements',
		'sc_intake_complete_engagements',
		'sc_intake_export_engagements',
		'sc_intake_view_lifecycle',
		'sc_intake_manage_lifecycle',
		'sc_intake_add_lifecycle_notes',
		'sc_intake_manage_lifecycle_tasks',
		'sc_intake_export_lifecycle',
		'sc_intake_view_support',
		'sc_intake_manage_support',
		'sc_intake_manage_support_links',
		'sc_intake_manage_support_intelligence',
		'sc_intake_ingest_support_handoffs',
		'sc_intake_export_support',
		'sc_intake_view_analytics',
		'sc_intake_view_reliability',
		'sc_intake_view_workflow_core',
		'sc_intake_view_platform',
		'sc_intake_manage_analytics',
		'sc_intake_export_analytics',
		'sc_intake_manage_reliability',
		'sc_intake_export_reliability',
		'sc_intake_manage_workflow_core',
		'sc_intake_prepare_workflow_handoffs',
		'sc_intake_dispatch_workflow_outbox',
		'sc_intake_acknowledge_workflow_handoffs',
		'sc_intake_export_workflow_core',
		'sc_intake_export_workflow_core_private',
		'sc_intake_manage_platform',
		'sc_intake_snapshot_platform',
		'sc_intake_export_platform',
		'sc_intake_launch_platform',
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
		'sc_intake_view_sender_portal',
		'sc_intake_post_portal_messages',
		'sc_intake_view_portal_recovery',
		'sc_intake_view_workflow',
		'sc_intake_create_meeting_offers',
		'sc_intake_create_proposals',
		'sc_intake_view_graph',
		'sc_intake_view_engagements',
		'sc_intake_view_lifecycle',
		'sc_intake_add_lifecycle_notes',
		'sc_intake_manage_lifecycle_tasks',
		'sc_intake_view_support',
		'sc_intake_manage_support',
		'sc_intake_manage_support_links',
		'sc_intake_view_analytics',
		'sc_intake_view_reliability',
		'sc_intake_view_workflow_core',
		'sc_intake_view_platform',
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
		'sc_intake_view_sender_portal',
		'sc_intake_manage_sender_portal',
		'sc_intake_issue_portal_invites',
		'sc_intake_post_portal_messages',
		'sc_intake_revoke_portal_access',
		'sc_intake_manage_portal_settings',
		'sc_intake_export_portal_audit',
		'sc_intake_view_portal_recovery',
		'sc_intake_manage_portal_recovery',
		'sc_intake_view_workflow',
		'sc_intake_manage_workflow',
		'sc_intake_create_meeting_offers',
		'sc_intake_publish_meeting_offers',
		'sc_intake_finalize_meetings',
		'sc_intake_create_proposals',
		'sc_intake_publish_proposals',
		'sc_intake_record_contracts',
		'sc_intake_export_workflow',
		'sc_intake_view_graph',
		'sc_intake_create_graph_events',
		'sc_intake_reconcile_graph_events',
		'sc_intake_cancel_graph_events',
		'sc_intake_export_graph_operations',
		'sc_intake_view_engagements',
		'sc_intake_create_engagement_handoffs',
		'sc_intake_manage_engagements',
		'sc_intake_activate_engagements',
		'sc_intake_complete_engagements',
		'sc_intake_export_engagements',
		'sc_intake_view_lifecycle',
		'sc_intake_manage_lifecycle',
		'sc_intake_add_lifecycle_notes',
		'sc_intake_manage_lifecycle_tasks',
		'sc_intake_export_lifecycle',
		'sc_intake_view_analytics',
		'sc_intake_manage_analytics',
		'sc_intake_export_analytics',
		'sc_intake_view_reliability',
		'sc_intake_manage_reliability',
		'sc_intake_export_reliability',
		'sc_intake_view_workflow_core',
		'sc_intake_manage_workflow_core',
		'sc_intake_prepare_workflow_handoffs',
		'sc_intake_dispatch_workflow_outbox',
		'sc_intake_acknowledge_workflow_handoffs',
		'sc_intake_export_workflow_core',
		'sc_intake_view_platform',
		'sc_intake_manage_platform',
		'sc_intake_snapshot_platform',
		'sc_intake_export_platform',
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
