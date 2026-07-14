<?php
/**
 * Product support case vocabulary and typed handoff contract.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Support_Schema {

	public const HANDOFF_SCHEMA = 'sc-product-support-handoff/1.0';

	public static function products(): array {
		return apply_filters(
			'sc_ei_support_products',
			array(
				'workbench'          => __( 'Workbench', 'sustainable-catalyst-engagement-intake' ),
				'decision-studio'    => __( 'Decision Studio', 'sustainable-catalyst-engagement-intake' ),
				'research-lab'       => __( 'Research Lab', 'sustainable-catalyst-engagement-intake' ),
				'knowledge-library'  => __( 'Knowledge Library', 'sustainable-catalyst-engagement-intake' ),
				'site-intelligence'  => __( 'Site Intelligence', 'sustainable-catalyst-engagement-intake' ),
				'research-librarian' => __( 'Research Librarian', 'sustainable-catalyst-engagement-intake' ),
				'platform-core'      => __( 'Platform Core', 'sustainable-catalyst-engagement-intake' ),
				'feature-suggestions'=> __( 'Feature Suggestions', 'sustainable-catalyst-engagement-intake' ),
				'contact-engagement' => __( 'Contact and Engagement Platform', 'sustainable-catalyst-engagement-intake' ),
				'other'              => __( 'Other Sustainable Catalyst Product', 'sustainable-catalyst-engagement-intake' ),
			)
		);
	}

	public static function stages(): array {
		return array(
			'new_support_request' => __( 'New Support Request', 'sustainable-catalyst-engagement-intake' ),
			'triage'              => __( 'Triage', 'sustainable-catalyst-engagement-intake' ),
			'needs_information'   => __( 'Needs Information', 'sustainable-catalyst-engagement-intake' ),
			'reproducing'         => __( 'Reproducing', 'sustainable-catalyst-engagement-intake' ),
			'known_issue'         => __( 'Known Issue', 'sustainable-catalyst-engagement-intake' ),
			'workaround_provided' => __( 'Workaround Provided', 'sustainable-catalyst-engagement-intake' ),
			'fix_planned'         => __( 'Fix Planned', 'sustainable-catalyst-engagement-intake' ),
			'resolved'            => __( 'Resolved', 'sustainable-catalyst-engagement-intake' ),
			'closed'              => __( 'Closed', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function allowed_transitions(): array {
		return array(
			'new_support_request' => array( 'triage', 'needs_information', 'closed' ),
			'triage'              => array( 'needs_information', 'reproducing', 'known_issue', 'workaround_provided', 'fix_planned', 'resolved', 'closed' ),
			'needs_information'   => array( 'triage', 'reproducing', 'closed' ),
			'reproducing'         => array( 'needs_information', 'known_issue', 'workaround_provided', 'fix_planned', 'resolved', 'closed' ),
			'known_issue'         => array( 'workaround_provided', 'fix_planned', 'resolved', 'closed' ),
			'workaround_provided' => array( 'reproducing', 'known_issue', 'fix_planned', 'resolved', 'closed' ),
			'fix_planned'         => array( 'workaround_provided', 'resolved', 'closed' ),
			'resolved'            => array( 'triage', 'closed' ),
			'closed'              => array( 'triage' ),
		);
	}

	public static function issue_types(): array {
		return array(
			'installation_update' => __( 'Installation or Update', 'sustainable-catalyst-engagement-intake' ),
			'runtime_error'       => __( 'Runtime Error', 'sustainable-catalyst-engagement-intake' ),
			'feature_failure'     => __( 'Feature Not Working', 'sustainable-catalyst-engagement-intake' ),
			'access_portal'       => __( 'Access or Sender Portal', 'sustainable-catalyst-engagement-intake' ),
			'upload_document'     => __( 'Private Document Upload', 'sustainable-catalyst-engagement-intake' ),
			'notification_email'  => __( 'Notification or Email', 'sustainable-catalyst-engagement-intake' ),
			'data_indexing'       => __( 'Data or Indexing', 'sustainable-catalyst-engagement-intake' ),
			'accessibility'       => __( 'Accessibility', 'sustainable-catalyst-engagement-intake' ),
			'privacy_security'    => __( 'Privacy or Security Concern', 'sustainable-catalyst-engagement-intake' ),
			'documentation'       => __( 'Documentation Question', 'sustainable-catalyst-engagement-intake' ),
			'feature_request'     => __( 'Potential Feature Request', 'sustainable-catalyst-engagement-intake' ),
			'other'               => __( 'Other Support Issue', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function severities(): array {
		return array(
			'low'      => __( 'Low', 'sustainable-catalyst-engagement-intake' ),
			'normal'   => __( 'Normal', 'sustainable-catalyst-engagement-intake' ),
			'high'     => __( 'High', 'sustainable-catalyst-engagement-intake' ),
			'critical' => __( 'Critical', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function relationship_types(): array {
		return array(
			'knowledge_article' => __( 'Knowledge Base Article', 'sustainable-catalyst-engagement-intake' ),
			'known_issue'       => __( 'Known Issue', 'sustainable-catalyst-engagement-intake' ),
			'feature_suggestion'=> __( 'Feature Suggestion', 'sustainable-catalyst-engagement-intake' ),
			'product_release'   => __( 'Product Release', 'sustainable-catalyst-engagement-intake' ),
			'reliability_event' => __( 'Reliability Event', 'sustainable-catalyst-engagement-intake' ),
			'duplicate_case'    => __( 'Related or Duplicate Case', 'sustainable-catalyst-engagement-intake' ),
			'external_reference'=> __( 'External Reference', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function signal_types(): array {
		return array(
			'documentation_gap'       => __( 'Documentation Gap', 'sustainable-catalyst-engagement-intake' ),
			'unhelpful_article'       => __( 'Unhelpful Support Article', 'sustainable-catalyst-engagement-intake' ),
			'zero_result_search'      => __( 'Zero-Result Search', 'sustainable-catalyst-engagement-intake' ),
			'recurring_issue'         => __( 'Recurring Product Issue', 'sustainable-catalyst-engagement-intake' ),
			'potential_known_issue'   => __( 'Potential Known Issue', 'sustainable-catalyst-engagement-intake' ),
			'potential_feature_request'=> __( 'Potential Feature Request', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function sanitize_product( string $value ): string {
		$value = sanitize_title( $value );
		return isset( self::products()[ $value ] ) ? $value : 'other';
	}

	public static function sanitize_stage( string $value ): string {
		$value = sanitize_key( $value );
		return isset( self::stages()[ $value ] ) ? $value : 'new_support_request';
	}

	public static function sanitize_issue_type( string $value ): string {
		$value = sanitize_key( $value );
		return isset( self::issue_types()[ $value ] ) ? $value : 'other';
	}

	public static function sanitize_severity( string $value ): string {
		$value = sanitize_key( $value );
		return isset( self::severities()[ $value ] ) ? $value : 'normal';
	}

	public static function label( array $choices, string $value ): string {
		return $choices[ $value ] ?? $value;
	}

	public static function public_stage( string $stage ): string {
		$map = array(
			'new_support_request' => __( 'Received', 'sustainable-catalyst-engagement-intake' ),
			'triage'              => __( 'Under Review', 'sustainable-catalyst-engagement-intake' ),
			'needs_information'   => __( 'Information Requested', 'sustainable-catalyst-engagement-intake' ),
			'reproducing'         => __( 'Being Investigated', 'sustainable-catalyst-engagement-intake' ),
			'known_issue'         => __( 'Known Issue Identified', 'sustainable-catalyst-engagement-intake' ),
			'workaround_provided' => __( 'Workaround Available', 'sustainable-catalyst-engagement-intake' ),
			'fix_planned'         => __( 'Fix Planned', 'sustainable-catalyst-engagement-intake' ),
			'resolved'            => __( 'Resolution Available', 'sustainable-catalyst-engagement-intake' ),
			'closed'              => __( 'Closed', 'sustainable-catalyst-engagement-intake' ),
		);
		return $map[ self::sanitize_stage( $stage ) ] ?? __( 'Under Review', 'sustainable-catalyst-engagement-intake' );
	}

	/**
	 * Reject personal or private content from product-intelligence signals.
	 */
	public static function signal_payload( array $input ) {
		$forbidden = array( 'name', 'email', 'contact', 'message', 'body', 'attachment', 'file', 'token', 'password', 'ip', 'phone', 'organization' );
		$walk = static function ( $value, string $path = '' ) use ( &$walk, $forbidden ) {
			if ( ! is_array( $value ) ) {
				return true;
			}
			foreach ( $value as $key => $child ) {
				$key = strtolower( (string) $key );
				foreach ( $forbidden as $needle ) {
					if ( false !== strpos( $key, $needle ) ) {
						return new WP_Error( 'support_signal_personal_data_rejected', __( 'Product-intelligence signals cannot contain sender identity, messages, files, credentials, or private contact data.', 'sustainable-catalyst-engagement-intake' ), array( 'field' => ltrim( $path . '.' . $key, '.' ) ) );
					}
				}
				$result = $walk( $child, ltrim( $path . '.' . $key, '.' ) );
				if ( is_wp_error( $result ) ) {
					return $result;
				}
			}
			return true;
		};
		$valid = $walk( $input );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		return array(
			'product'       => self::sanitize_product( (string) ( $input['product'] ?? 'other' ) ),
			'product_version'=> substr( sanitize_text_field( (string) ( $input['product_version'] ?? '' ) ), 0, 80 ),
			'component'     => substr( sanitize_title( (string) ( $input['component'] ?? '' ) ), 0, 120 ),
			'issue_type'    => self::sanitize_issue_type( (string) ( $input['issue_type'] ?? 'other' ) ),
			'search_query'  => substr( sanitize_text_field( (string) ( $input['search_query'] ?? '' ) ), 0, 255 ),
			'article_ids'   => array_values( array_filter( array_map( 'absint', (array) ( $input['article_ids'] ?? array() ) ) ) ),
			'known_issue'   => substr( sanitize_text_field( (string) ( $input['known_issue'] ?? '' ) ), 0, 191 ),
			'resolution_attempted' => empty( $input['resolution_attempted'] ) ? false : true,
			'article_helpful'=> isset( $input['article_helpful'] ) ? (bool) $input['article_helpful'] : null,
			'source_url'    => esc_url_raw( (string) ( $input['source_url'] ?? '' ) ),
		);
	}
}
