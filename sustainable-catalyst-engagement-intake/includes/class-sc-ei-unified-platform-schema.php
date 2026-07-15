<?php
/**
 * v2.0 integrated advisory, support, and institutional engagement contracts.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Unified_Platform_Schema {

	public const DOSSIER_SCHEMA = 'sc-engagement-dossier/2.0';
	public const HANDOFF_SCHEMA = 'sc-engagement-platform-handoff/2.0';
	public const EVENT_SCHEMA = 'sc-engagement-dossier-event/2.0';

	public static function route_groups(): array {
		return array(
			'general'       => __( 'General Contact', 'sustainable-catalyst-engagement-intake' ),
			'advisory'      => __( 'Advisory', 'sustainable-catalyst-engagement-intake' ),
			'support'       => __( 'Product Support', 'sustainable-catalyst-engagement-intake' ),
			'institutional' => __( 'Institutional Engagement', 'sustainable-catalyst-engagement-intake' ),
			'research'      => __( 'Research Collaboration', 'sustainable-catalyst-engagement-intake' ),
			'media'         => __( 'Media and Public Conversation', 'sustainable-catalyst-engagement-intake' ),
			'technical'     => __( 'Technical and Platform Work', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function phases(): array {
		return array(
			'intake'             => __( 'Intake', 'sustainable-catalyst-engagement-intake' ),
			'review'             => __( 'Review and Qualification', 'sustainable-catalyst-engagement-intake' ),
			'support_resolution' => __( 'Support Resolution', 'sustainable-catalyst-engagement-intake' ),
			'scheduling'         => __( 'Scheduling', 'sustainable-catalyst-engagement-intake' ),
			'proposal'           => __( 'Proposal and Approval', 'sustainable-catalyst-engagement-intake' ),
			'engagement'         => __( 'Active Engagement', 'sustainable-catalyst-engagement-intake' ),
			'delivery'           => __( 'Client Delivery', 'sustainable-catalyst-engagement-intake' ),
			'billing'            => __( 'Billing and Payment Handoff', 'sustainable-catalyst-engagement-intake' ),
			'complete'           => __( 'Completed', 'sustainable-catalyst-engagement-intake' ),
			'archived'           => __( 'Archived', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function health_states(): array {
		return array(
			'healthy'   => __( 'Healthy', 'sustainable-catalyst-engagement-intake' ),
			'attention' => __( 'Needs Attention', 'sustainable-catalyst-engagement-intake' ),
			'blocked'   => __( 'Blocked', 'sustainable-catalyst-engagement-intake' ),
			'archived'  => __( 'Archived', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function entity_types(): array {
		return array(
			'inquiry', 'support_case', 'meeting', 'proposal', 'statement_of_work', 'engagement',
			'workspace', 'invoice', 'attachment', 'communication', 'lifecycle_task', 'privacy_request',
		);
	}

	public static function source_systems(): array {
		return array(
			'contact_engagement', 'feature_suggestions', 'support_knowledge_base', 'workbench',
			'decision_studio', 'research_lab', 'knowledge_library', 'site_intelligence',
			'research_librarian', 'platform_core', 'manual',
		);
	}

	public static function target_modules(): array {
		return array( 'intake', 'advisory', 'support', 'calendar', 'proposal', 'workspace', 'billing', 'analytics' );
	}

	public static function sanitize_choice( string $value, array $allowed, string $default ): string {
		$value = sanitize_key( $value );
		return in_array( $value, $allowed, true ) ? $value : $default;
	}

	public static function sanitize_route_group( string $value ): string {
		return self::sanitize_choice( $value, array_keys( self::route_groups() ), 'general' );
	}

	public static function sanitize_phase( string $value ): string {
		return self::sanitize_choice( $value, array_keys( self::phases() ), 'intake' );
	}

	public static function handoff_forbidden_keys(): array {
		return array(
			'name', 'full_name', 'first_name', 'last_name', 'email', 'phone', 'address', 'organization',
			'message', 'body', 'notes', 'attachment', 'attachments', 'file', 'files', 'document', 'documents',
			'token', 'password', 'secret', 'api_key', 'access_token', 'refresh_token', 'cookie', 'session',
			'ip', 'ip_address', 'card', 'card_number', 'cvv', 'cvc', 'bank_account', 'routing_number',
		);
	}

	public static function handoff_payload_is_safe( array $payload ): bool {
		$encoded = wp_json_encode( $payload, JSON_UNESCAPED_SLASHES );
		if ( false === $encoded || strlen( $encoded ) > 16000 ) {
			return false;
		}
		$walk = static function ( array $value, int $depth = 0 ) use ( &$walk ): bool {
			if ( $depth > 5 ) {
				return false;
			}
			foreach ( $value as $key => $item ) {
				$key = sanitize_key( (string) $key );
				if ( in_array( $key, SC_EI_Unified_Platform_Schema::handoff_forbidden_keys(), true ) ) {
					return false;
				}
				if ( is_array( $item ) ) {
					if ( ! $walk( $item, $depth + 1 ) ) {
						return false;
					}
					continue;
				}
				if ( is_object( $item ) || is_resource( $item ) ) {
					return false;
				}
				if ( is_string( $item ) ) {
					if ( strlen( $item ) > 2000 || is_email( trim( $item ) ) ) {
						return false;
					}
					if ( preg_match( '/\b(?:\d{1,3}\.){3}\d{1,3}\b/', $item ) || preg_match( '/\+?\d[\d\s().-]{7,}\d/', $item ) ) {
						return false;
					}
				}
			}
			return true;
		};
		return $walk( $payload );
	}

	public static function default_settings(): array {
		return array(
			'unified_platform_enabled'               => 1,
			'unified_platform_auto_refresh'          => 1,
			'unified_platform_handoff_enabled'       => 1,
			'unified_platform_require_dossiers'      => 1,
			'unified_platform_sender_projection'     => 1,
			'unified_platform_no_auto_decisions'     => 1,
			'unified_platform_no_cross_case_merging' => 1,
		);
	}
}
