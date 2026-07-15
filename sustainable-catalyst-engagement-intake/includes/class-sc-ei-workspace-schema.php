<?php
/**
 * Secure client workspace and collaboration schema.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Workspace_Schema {

	public const HANDOFF_SCHEMA = 'sc-client-workspace/1.0';

	public static function workspace_statuses(): array {
		return array(
			'draft'     => __( 'Draft', 'sustainable-catalyst-engagement-intake' ),
			'active'    => __( 'Active', 'sustainable-catalyst-engagement-intake' ),
			'paused'    => __( 'Paused', 'sustainable-catalyst-engagement-intake' ),
			'completed' => __( 'Completed', 'sustainable-catalyst-engagement-intake' ),
			'archived'  => __( 'Archived', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function milestone_statuses(): array {
		return array(
			'planned'     => __( 'Planned', 'sustainable-catalyst-engagement-intake' ),
			'in_progress' => __( 'In Progress', 'sustainable-catalyst-engagement-intake' ),
			'blocked'     => __( 'Blocked', 'sustainable-catalyst-engagement-intake' ),
			'completed'   => __( 'Completed', 'sustainable-catalyst-engagement-intake' ),
			'canceled'    => __( 'Canceled', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function deliverable_statuses(): array {
		return array(
			'draft'             => __( 'Draft', 'sustainable-catalyst-engagement-intake' ),
			'in_review'         => __( 'Internal Review', 'sustainable-catalyst-engagement-intake' ),
			'published'         => __( 'Available to Client', 'sustainable-catalyst-engagement-intake' ),
			'changes_requested' => __( 'Changes Requested', 'sustainable-catalyst-engagement-intake' ),
			'accepted'          => __( 'Accepted', 'sustainable-catalyst-engagement-intake' ),
			'superseded'        => __( 'Superseded', 'sustainable-catalyst-engagement-intake' ),
			'withdrawn'         => __( 'Withdrawn', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function sender_decisions(): array {
		return array(
			'pending'           => __( 'Decision Pending', 'sustainable-catalyst-engagement-intake' ),
			'accepted'          => __( 'Accepted', 'sustainable-catalyst-engagement-intake' ),
			'changes_requested' => __( 'Changes Requested', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function member_permissions(): array {
		return array(
			'view_workspace', 'view_milestones', 'view_deliverables', 'respond_deliverables',
			'view_documents', 'upload_documents', 'view_messages', 'send_messages',
		);
	}

	public static function sanitize_status( string $value, array $allowed, string $fallback ): string {
		$value = sanitize_key( $value );
		return isset( $allowed[ $value ] ) ? $value : $fallback;
	}

	public static function sender_projection_keys(): array {
		return array(
			'public_id', 'workspace_number', 'title', 'status', 'summary', 'next_step', 'milestones',
			'deliverables', 'documents', 'messages', 'updated_at',
		);
	}
}
