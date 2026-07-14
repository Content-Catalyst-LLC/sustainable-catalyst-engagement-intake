<?php
/**
 * Engagement handoff taxonomies, settings, and sanitizers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Engagement_Schema {

	public static function statuses(): array {
		return array(
			'handoff_pending' => __( 'Handoff Pending', 'sustainable-catalyst-engagement-intake' ),
			'ready_for_setup' => __( 'Ready for Setup', 'sustainable-catalyst-engagement-intake' ),
			'active'          => __( 'Active', 'sustainable-catalyst-engagement-intake' ),
			'paused'          => __( 'Paused', 'sustainable-catalyst-engagement-intake' ),
			'completed'       => __( 'Completed', 'sustainable-catalyst-engagement-intake' ),
			'canceled'        => __( 'Canceled', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function kickoff_statuses(): array {
		return array(
			'not_scheduled' => __( 'Not Scheduled', 'sustainable-catalyst-engagement-intake' ),
			'proposed'      => __( 'Proposed', 'sustainable-catalyst-engagement-intake' ),
			'scheduled'     => __( 'Scheduled', 'sustainable-catalyst-engagement-intake' ),
			'completed'     => __( 'Completed', 'sustainable-catalyst-engagement-intake' ),
			'not_required'  => __( 'Not Required', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function requirement_statuses(): array {
		return array(
			'pending'     => __( 'Pending', 'sustainable-catalyst-engagement-intake' ),
			'in_progress' => __( 'In Progress', 'sustainable-catalyst-engagement-intake' ),
			'complete'    => __( 'Complete', 'sustainable-catalyst-engagement-intake' ),
			'waived'      => __( 'Waived', 'sustainable-catalyst-engagement-intake' ),
			'blocked'     => __( 'Blocked', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function requirement_categories(): array {
		return array(
			'commercial'  => __( 'Commercial', 'sustainable-catalyst-engagement-intake' ),
			'governance'  => __( 'Governance', 'sustainable-catalyst-engagement-intake' ),
			'kickoff'     => __( 'Kickoff', 'sustainable-catalyst-engagement-intake' ),
			'access'      => __( 'Access and Data', 'sustainable-catalyst-engagement-intake' ),
			'delivery'    => __( 'Delivery', 'sustainable-catalyst-engagement-intake' ),
			'privacy'     => __( 'Privacy and Security', 'sustainable-catalyst-engagement-intake' ),
			'other'       => __( 'Other', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function event_types(): array {
		return array(
			'engagement_handoff_created'      => __( 'Engagement Handoff Created', 'sustainable-catalyst-engagement-intake' ),
			'engagement_snapshot_created'     => __( 'Immutable Handoff Snapshot Created', 'sustainable-catalyst-engagement-intake' ),
			'engagement_owner_updated'        => __( 'Engagement Ownership Updated', 'sustainable-catalyst-engagement-intake' ),
			'engagement_requirement_created'  => __( 'Onboarding Requirement Created', 'sustainable-catalyst-engagement-intake' ),
			'engagement_requirement_updated'  => __( 'Onboarding Requirement Updated', 'sustainable-catalyst-engagement-intake' ),
			'engagement_ready'                => __( 'Engagement Ready for Setup', 'sustainable-catalyst-engagement-intake' ),
			'engagement_activated'            => __( 'Engagement Activated', 'sustainable-catalyst-engagement-intake' ),
			'engagement_paused'               => __( 'Engagement Paused', 'sustainable-catalyst-engagement-intake' ),
			'engagement_resumed'              => __( 'Engagement Resumed', 'sustainable-catalyst-engagement-intake' ),
			'engagement_completed'            => __( 'Engagement Completed', 'sustainable-catalyst-engagement-intake' ),
			'engagement_canceled'             => __( 'Engagement Canceled', 'sustainable-catalyst-engagement-intake' ),
			'engagement_kickoff_updated'       => __( 'Kickoff State Updated', 'sustainable-catalyst-engagement-intake' ),
			'engagement_exported'              => __( 'Engagement Handoff Exported', 'sustainable-catalyst-engagement-intake' ),
			'engagement_portal_viewed'         => __( 'Engagement Viewed in Sender Portal', 'sustainable-catalyst-engagement-intake' ),
			'engagement_privacy_redacted'      => __( 'Engagement Personal Data Redacted', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function default_settings(): array {
		return array(
			'engagement_enabled'                    => 1,
			'engagement_require_all_required_items' => 1,
			'engagement_require_owner'              => 1,
			'engagement_require_contract_reference' => 1,
			'engagement_require_snapshot_hash'      => 1,
			'engagement_sender_portal_enabled'      => 1,
			'engagement_default_kickoff_days'       => 7,
			'engagement_default_requirement_days'   => 14,
			'engagement_allow_workbench_export'     => 1,
			'engagement_allow_decision_studio_export'=> 1,
			'engagement_no_auto_activation'         => 1,
			'engagement_no_auto_provisioning'       => 1,
			'engagement_no_auto_invoice'            => 1,
			'engagement_no_auto_payment'            => 1,
			'engagement_no_auto_signature'          => 1,
		);
	}

	public static function sanitize_status( string $value ): string {
		$value = sanitize_key( $value );
		return isset( self::statuses()[ $value ] ) ? $value : 'handoff_pending';
	}

	public static function sanitize_kickoff_status( string $value ): string {
		$value = sanitize_key( $value );
		return isset( self::kickoff_statuses()[ $value ] ) ? $value : 'not_scheduled';
	}

	public static function sanitize_requirement_status( string $value ): string {
		$value = sanitize_key( $value );
		return isset( self::requirement_statuses()[ $value ] ) ? $value : 'pending';
	}

	public static function sanitize_requirement_category( string $value ): string {
		$value = sanitize_key( $value );
		return isset( self::requirement_categories()[ $value ] ) ? $value : 'other';
	}

	public static function sanitize_user_ids( $value ): array {
		$items = is_array( $value ) ? $value : preg_split( '/[\s,;]+/', (string) $value );
		$result = array();
		foreach ( (array) $items as $item ) {
			$id = absint( $item );
			if ( $id > 0 ) {
				$result[ $id ] = $id;
			}
		}
		return array_values( $result );
	}

	public static function sanitize_lines( $value, int $limit = 100 ): array {
		$items = is_array( $value ) ? $value : preg_split( '/\r\n|\r|\n/', (string) $value );
		$result = array();
		foreach ( (array) $items as $item ) {
			$item = trim( sanitize_text_field( (string) $item ) );
			if ( '' === $item ) {
				continue;
			}
			$key = strtolower( $item );
			if ( ! isset( $result[ $key ] ) ) {
				$result[ $key ] = mb_substr( $item, 0, 500 );
			}
			if ( count( $result ) >= max( 1, $limit ) ) {
				break;
			}
		}
		return array_values( $result );
	}

	public static function label( array $labels, string $key ): string {
		return $labels[ $key ] ?? ucwords( str_replace( '_', ' ', $key ) );
	}

	public static function default_requirements( array $engagement, array $settings = array() ): array {
		$settings = wp_parse_args( $settings, self::default_settings() );
		$default_due = gmdate(
			'Y-m-d',
			time() + max( 1, absint( $settings['engagement_default_requirement_days'] ) ) * DAY_IN_SECONDS
		);
		return array(
			array(
				'requirement_key' => 'contract_reference_verified',
				'title'           => __( 'Verify external contract reference', 'sustainable-catalyst-engagement-intake' ),
				'description'     => __( 'Confirm that the external agreement reference recorded on the proposal matches the executed agreement used for this handoff.', 'sustainable-catalyst-engagement-intake' ),
				'category'        => 'commercial',
				'status'          => ! empty( $engagement['contract_reference'] ) ? 'complete' : 'pending',
				'is_required'     => 1,
				'sender_visible'  => 0,
				'due_date'        => $default_due,
				'sort_order'      => 10,
			),
			array(
				'requirement_key' => 'proposal_snapshot_verified',
				'title'           => __( 'Verify immutable proposal snapshot', 'sustainable-catalyst-engagement-intake' ),
				'description'     => __( 'Confirm that scope, deliverables, exclusions, assumptions, fees, timeline, and proposal terms are preserved in the handoff snapshot.', 'sustainable-catalyst-engagement-intake' ),
				'category'        => 'governance',
				'status'          => 'complete',
				'is_required'     => 1,
				'sender_visible'  => 0,
				'due_date'        => $default_due,
				'sort_order'      => 20,
			),
			array(
				'requirement_key' => 'engagement_owner_confirmed',
				'title'           => __( 'Confirm engagement owner', 'sustainable-catalyst-engagement-intake' ),
				'description'     => __( 'Assign the accountable internal owner for delivery and sender communication.', 'sustainable-catalyst-engagement-intake' ),
				'category'        => 'governance',
				'status'          => ! empty( $engagement['owner_user_id'] ) ? 'complete' : 'pending',
				'is_required'     => 1,
				'sender_visible'  => 1,
				'due_date'        => $default_due,
				'sort_order'      => 30,
			),
			array(
				'requirement_key' => 'kickoff_plan_confirmed',
				'title'           => __( 'Confirm kickoff plan', 'sustainable-catalyst-engagement-intake' ),
				'description'     => __( 'Record whether a kickoff meeting is scheduled, proposed, completed, or not required.', 'sustainable-catalyst-engagement-intake' ),
				'category'        => 'kickoff',
				'status'          => 'pending',
				'is_required'     => 1,
				'sender_visible'  => 1,
				'due_date'        => $default_due,
				'sort_order'      => 40,
			),
			array(
				'requirement_key' => 'access_and_data_reviewed',
				'title'           => __( 'Review access and data requirements', 'sustainable-catalyst-engagement-intake' ),
				'description'     => __( 'Document systems, documents, datasets, confidentiality restrictions, and access dependencies required for delivery.', 'sustainable-catalyst-engagement-intake' ),
				'category'        => 'access',
				'status'          => 'pending',
				'is_required'     => 1,
				'sender_visible'  => 1,
				'due_date'        => $default_due,
				'sort_order'      => 50,
			),
			array(
				'requirement_key' => 'delivery_workspace_reviewed',
				'title'           => __( 'Review delivery workspace requirements', 'sustainable-catalyst-engagement-intake' ),
				'description'     => __( 'Decide whether Workbench, Decision Studio, private document exchange, or another delivery environment will be used. This does not provision any system automatically.', 'sustainable-catalyst-engagement-intake' ),
				'category'        => 'delivery',
				'status'          => 'pending',
				'is_required'     => 1,
				'sender_visible'  => 0,
				'due_date'        => $default_due,
				'sort_order'      => 60,
			),
		);
	}
}
