<?php
/**
 * Workflow Core integration service and internal adapter registry.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Workflow_Core_Service {

	public const SYNC_INQUIRY_HOOK = 'sc_ei_workflow_core_sync_inquiry';

	private static array $adapters = array();

	public static function register(): void {
		add_action( 'sc_ei_audit_recorded', array( __CLASS__, 'on_audit_recorded' ), 10, 4 );
		add_action( self::SYNC_INQUIRY_HOOK, array( __CLASS__, 'sync_scheduled_inquiry' ), 10, 1 );
		add_filter( 'sc_ei_workflow_core_dispatch', array( __CLASS__, 'dispatch_to_adapter' ), 10, 3 );
	}

	public static function register_adapter( string $target, callable $adapter ): bool {
		$target = SC_EI_Workflow_Core_Schema::sanitize_target( $target );
		if ( '' === $target ) {
			return false;
		}
		self::$adapters[ $target ] = $adapter;
		do_action( 'sc_ei_workflow_core_adapter_registered', $target );
		return true;
	}

	public static function registered_targets(): array {
		$targets = array();
		foreach ( SC_EI_Workflow_Core_Schema::handoff_targets() as $key => $label ) {
			$targets[ $key ] = array(
				'label'      => $label,
				'registered' => isset( self::$adapters[ $key ] ),
			);
		}
		return $targets;
	}

	public static function on_audit_recorded(
		string $event_type,
		?int $inquiry_id,
		array $context,
		int $audit_id
	): void {
		if (
			! $inquiry_id
			|| empty( SC_EI_Workflow_Core_Repository::settings()['workflow_core_enabled'] )
			|| empty( SC_EI_Workflow_Core_Repository::settings()['workflow_core_auto_sync_on_audit'] )
			|| str_starts_with( $event_type, 'workflow_core_' )
			|| in_array(
				$event_type,
				array(
					'attachment_downloaded',
					'file_audit_exported',
					'communication_history_exported',
					'privacy_inventory_exported',
					'portal_audit_exported',
					'fit_assessment_exported',
					'engagement_exported',
					'graph_operations_exported',
				),
				true
			)
		) {
			return;
		}

		$args = array( absint( $inquiry_id ) );
		if ( ! wp_next_scheduled( self::SYNC_INQUIRY_HOOK, $args ) ) {
			wp_schedule_single_event( time() + 5, self::SYNC_INQUIRY_HOOK, $args );
		}
	}

	public static function sync_scheduled_inquiry( int $inquiry_id ): void {
		$result = SC_EI_Workflow_Core_Repository::sync_inquiry( $inquiry_id, 0, 'audit_event' );
		if ( is_wp_error( $result ) ) {
			SC_EI_Hardening_Repository::record_event(
				'plugin',
				'workflow_core_sync_failed',
				'warning',
				'Workflow Core could not synchronize an inquiry after an authoritative audit event.',
				array(
					'inquiry_id' => $inquiry_id,
					'error_code' => $result->get_error_code(),
				)
			);
		}
	}

	public static function dispatch_to_adapter( $result, array $event, array $payload ) {
		$target = SC_EI_Workflow_Core_Schema::sanitize_target( (string) ( $event['target'] ?? '' ) );
		if ( '' === $target || ! isset( self::$adapters[ $target ] ) ) {
			return new WP_Error(
				'workflow_core_adapter_unavailable',
				__( 'The selected Workflow Core target has not registered an internal adapter.', 'sustainable-catalyst-engagement-intake' ),
				array( 'target' => $target )
			);
		}
		try {
			$adapter_result = call_user_func( self::$adapters[ $target ], $event, $payload );
		} catch ( Throwable $error ) {
			return new WP_Error(
				'workflow_core_adapter_exception',
				__( 'The registered Workflow Core adapter raised an exception.', 'sustainable-catalyst-engagement-intake' ),
				array(
					'target'     => $target,
					'error_type' => get_class( $error ),
				)
			);
		}
		if ( is_wp_error( $adapter_result ) ) {
			return $adapter_result;
		}
		if ( true === $adapter_result ) {
			return array( 'acknowledged' => true );
		}
		return is_array( $adapter_result ) ? $adapter_result : array( 'acknowledged' => false );
	}

	public static function acknowledge_from_adapter(
		string $handoff_public_id,
		string $target,
		string $content_hash,
		string $receipt
	) {
		global $wpdb;

		$target = SC_EI_Workflow_Core_Schema::sanitize_target( $target );
		if ( '' === $target || ! isset( self::$adapters[ $target ] ) ) {
			return new WP_Error( 'workflow_core_adapter_not_registered', __( 'The Workflow Core target adapter is not registered.', 'sustainable-catalyst-engagement-intake' ) );
		}
		$table = SC_EI_Database::table( 'workflow_handoffs' );
		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE public_id = %s AND target = %s LIMIT 1",
				sanitize_text_field( $handoff_public_id ),
				$target
			)
		);
		$handoff = $id ? SC_EI_Workflow_Core_Repository::find_handoff( absint( $id ) ) : null;
		if ( ! $handoff || ! hash_equals( (string) $handoff['content_hash'], sanitize_text_field( $content_hash ) ) ) {
			return new WP_Error( 'workflow_core_adapter_ack_invalid', __( 'The Workflow Core acknowledgment did not match the prepared handoff.', 'sustainable-catalyst-engagement-intake' ) );
		}
		return SC_EI_Workflow_Core_Repository::acknowledge_handoff( absint( $handoff['id'] ), $receipt, 0 );
	}

	public static function case_context( int $inquiry_id ): array {
		$case = SC_EI_Workflow_Core_Repository::case_for_inquiry( $inquiry_id );
		if ( ! $case ) {
			$case = SC_EI_Workflow_Core_Repository::sync_inquiry( $inquiry_id, 0, 'context_request' );
		}
		if ( is_wp_error( $case ) || ! $case ) {
			return array();
		}
		return array(
			'case'     => $case,
			'blockers' => json_decode( (string) $case['consistency_notes'], true ) ?: array(),
			'targets'  => self::registered_targets(),
		);
	}
}
