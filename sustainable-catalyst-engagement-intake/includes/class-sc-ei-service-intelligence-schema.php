<?php
/**
 * Privacy-safe engagement analytics and service-intelligence contracts.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Service_Intelligence_Schema {

	public const SNAPSHOT_SCHEMA = 'sc-engagement-service-intelligence/1.0';
	public const FINDING_SCHEMA  = 'sc-service-intelligence-finding/1.0';

	public static function finding_types(): array {
		return array(
			'service_demand'       => __( 'Service demand', 'sustainable-catalyst-engagement-intake' ),
			'product_friction'     => __( 'Product friction', 'sustainable-catalyst-engagement-intake' ),
			'documentation_gap'    => __( 'Documentation gap', 'sustainable-catalyst-engagement-intake' ),
			'conversion_bottleneck'=> __( 'Conversion bottleneck', 'sustainable-catalyst-engagement-intake' ),
			'capacity_risk'        => __( 'Capacity risk', 'sustainable-catalyst-engagement-intake' ),
			'cycle_time'           => __( 'Cycle-time concern', 'sustainable-catalyst-engagement-intake' ),
			'collaboration_signal' => __( 'Collaboration signal', 'sustainable-catalyst-engagement-intake' ),
			'quality_signal'       => __( 'Quality signal', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function finding_statuses(): array {
		return array(
			'candidate' => __( 'Candidate', 'sustainable-catalyst-engagement-intake' ),
			'reviewing' => __( 'Reviewing', 'sustainable-catalyst-engagement-intake' ),
			'actioned'  => __( 'Actioned', 'sustainable-catalyst-engagement-intake' ),
			'dismissed' => __( 'Dismissed', 'sustainable-catalyst-engagement-intake' ),
			'closed'    => __( 'Closed', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function severities(): array {
		return array(
			'info'     => __( 'Information', 'sustainable-catalyst-engagement-intake' ),
			'watch'    => __( 'Watch', 'sustainable-catalyst-engagement-intake' ),
			'attention'=> __( 'Attention', 'sustainable-catalyst-engagement-intake' ),
			'critical' => __( 'Critical', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function sanitize_enum( string $value, array $allowed, string $default ): string {
		$value = sanitize_key( $value );
		return array_key_exists( $value, $allowed ) ? $value : $default;
	}

	public static function forbidden_evidence_keys(): array {
		return array(
			'name', 'contact_name', 'email', 'contact_email', 'phone', 'phone_number', 'organization',
			'message', 'message_body', 'body', 'document', 'document_body', 'file', 'attachment',
			'token', 'password', 'ip', 'ip_address', 'address', 'sender_id', 'inquiry_id', 'case_id',
		);
	}

	public static function evidence_is_aggregate( array $evidence ): bool {
		$encoded = wp_json_encode( $evidence, JSON_UNESCAPED_SLASHES );
		if ( false === $encoded || strlen( $encoded ) > 20000 ) {
			return false;
		}
		$walk = static function ( array $value, int $depth = 0 ) use ( &$walk ): bool {
			if ( $depth > 6 ) {
				return false;
			}
			foreach ( $value as $key => $item ) {
				$key = sanitize_key( (string) $key );
				if ( in_array( $key, SC_EI_Service_Intelligence_Schema::forbidden_evidence_keys(), true ) ) {
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
					if ( preg_match( '/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i', $item ) || preg_match( '/\b(?:\+?1[-.\s]?)?(?:\(?\d{3}\)?[-.\s]?)\d{3}[-.\s]?\d{4}\b/', $item ) || preg_match( '/\b(?:\d{1,3}\.){3}\d{1,3}\b/', $item ) ) {
						return false;
					}
				}
			}
			return true;
		};
		return $walk( $evidence );
	}

	public static function normalize_dimension( string $value ): string {
		$value = sanitize_key( $value );
		return mb_substr( $value, 0, 120 );
	}

	public static function default_settings(): array {
		return array(
			'analytics_intelligence_enabled'          => 1,
			'analytics_auto_candidate_findings'       => 0,
			'analytics_finding_review_days'           => 14,
			'analytics_snapshot_fresh_days'            => 7,
			'analytics_intelligence_retention_days'    => 730,
			'analytics_include_support_intelligence'   => 1,
			'analytics_include_workspace_intelligence' => 1,
			'analytics_include_proposal_intelligence'  => 1,
			'analytics_human_review_required'          => 1,
			'analytics_no_personal_data'               => 1,
			'analytics_no_sender_ranking'              => 1,
			'analytics_no_automated_decisions'         => 1,
		);
	}
}
