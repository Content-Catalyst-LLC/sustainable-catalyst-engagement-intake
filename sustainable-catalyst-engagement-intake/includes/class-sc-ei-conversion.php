<?php
/**
 * Form experience, source attribution, and conversion-routing helpers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Conversion {

	public static function variants(): array {
		return array(
			'compact'    => __( 'Compact Consulting Intake', 'sustainable-catalyst-engagement-intake' ),
			'advanced'   => __( 'Advanced Contact Hub', 'sustainable-catalyst-engagement-intake' ),
			'general'    => __( 'General Contact Form', 'sustainable-catalyst-engagement-intake' ),
			'consulting' => __( 'Standard Engagement Form', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function sources(): array {
		return apply_filters(
			'sc_ei_sources',
			array(
				'consulting-page' => __( 'Consulting Page', 'sustainable-catalyst-engagement-intake' ),
				'support-page'    => __( 'Support Page', 'sustainable-catalyst-engagement-intake' ),
				'contact-page'    => __( 'Contact Page', 'sustainable-catalyst-engagement-intake' ),
				'research-page'   => __( 'Research Page', 'sustainable-catalyst-engagement-intake' ),
				'platform-page'   => __( 'Platform Page', 'sustainable-catalyst-engagement-intake' ),
				'lab-page'        => __( 'Research Lab Page', 'sustainable-catalyst-engagement-intake' ),
				'workbench-page'  => __( 'Workbench Page', 'sustainable-catalyst-engagement-intake' ),
				'media-page'      => __( 'Media or Channel Page', 'sustainable-catalyst-engagement-intake' ),
				'other'           => __( 'Other Source', 'sustainable-catalyst-engagement-intake' ),
			)
		);
	}

	public static function sanitize_variant( string $variant ): string {
		$variant = sanitize_key( $variant );
		return array_key_exists( $variant, self::variants() ) ? $variant : 'advanced';
	}

	public static function sanitize_source( string $source ): string {
		$source = sanitize_key( $source );
		if ( '' === $source ) {
			return 'other';
		}
		return substr( $source, 0, 80 );
	}

	public static function sanitize_entry_cta( string $cta ): string {
		$cta = sanitize_key( $cta );
		return substr( $cta ?: 'unspecified', 0, 120 );
	}

	public static function route( string $inquiry_type, string $service_interest, string $variant ): string {
		$service_interest = sanitize_key( $service_interest );
		$inquiry_type     = sanitize_key( $inquiry_type );
		$variant          = self::sanitize_variant( $variant );

		if ( $service_interest ) {
			return substr( $service_interest, 0, 120 );
		}

		if ( 'compact' === $variant ) {
			return 'consulting_unspecified';
		}

		return substr( $inquiry_type ?: 'general', 0, 120 );
	}

	public static function guidance_flags( string $service_interest, string $budget_range, string $message = '' ): array {
		$service = sanitize_key( $service_interest );
		$budget  = sanitize_key( $budget_range );
		$message = strtolower( sanitize_textarea_field( $message ) );
		$flags   = array();

		$lower_budgets = array( 'under_1500', 'under_1000', '1000_2500', '1500_5000', '2500_5000', 'not_sure' );

		if ( 'knowledge_platform_build' === $service && in_array( $budget, $lower_budgets, true ) ) {
			$flags[] = 'platform_build_budget_guidance';
		}
		if ( 'strategy_architecture_sprint' === $service && in_array( $budget, array( 'under_1500', 'under_1000', '1000_2500', '1500_5000' ), true ) ) {
			$flags[] = 'sprint_budget_guidance';
		}
		if ( 'initial_fit_call' === $service && preg_match( '/\b(review|audit|analy[sz]e|read)\b.{0,40}\b(document|report|proposal|materials?)\b/i', $message ) ) {
			$flags[] = 'fit_call_scope_guidance';
		}
		if ( 'not_sure' === $service || 'other' === $service ) {
			$flags[] = 'route_recommendation_requested';
		}

		return array_values( array_unique( $flags ) );
	}

	public static function compact_guidance(): array {
		return array(
			'initial_fit_call' => array(
				'default' => __( 'The free 20-minute fit call is for alignment and next-step guidance. It does not include substantive document review or written recommendations.', 'sustainable-catalyst-engagement-intake' ),
			),
			'strategic_consultation' => array(
				'default' => __( 'The strategic consultation is a focused paid advisory session. The published consultation fee is $375.', 'sustainable-catalyst-engagement-intake' ),
			),
			'evidence_systems_diagnostic' => array(
				'default' => __( 'The evidence and systems diagnostic begins at $1,500 and is suited to a bounded review with findings and recommendations.', 'sustainable-catalyst-engagement-intake' ),
			),
			'strategy_architecture_sprint' => array(
				'default'    => __( 'Strategy and architecture sprints are typically scoped between $5,000 and $8,500.', 'sustainable-catalyst-engagement-intake' ),
				'low_budget' => __( 'A paid consultation or diagnostic may be the more realistic starting point for this budget range.', 'sustainable-catalyst-engagement-intake' ),
			),
			'knowledge_platform_build' => array(
				'default'    => __( 'Knowledge platform and workflow builds generally begin at $12,000.', 'sustainable-catalyst-engagement-intake' ),
				'low_budget' => __( 'A consultation, diagnostic, or architecture sprint may be the best first phase before a full platform build.', 'sustainable-catalyst-engagement-intake' ),
			),
			'training_workshop' => array(
				'default' => __( 'Workshops are generally scoped between $1,500 and $4,500 depending on format, preparation, and audience.', 'sustainable-catalyst-engagement-intake' ),
			),
			'monthly_advisory' => array(
				'default' => __( 'Monthly advisory engagements are generally structured at $2,500, $4,000, $6,000, or a custom level.', 'sustainable-catalyst-engagement-intake' ),
			),
			'institutional_partnership' => array(
				'default' => __( 'Institutional partnerships are custom-scoped around the relationship, governance, systems, and delivery requirements.', 'sustainable-catalyst-engagement-intake' ),
			),
			'not_sure' => array(
				'default' => __( 'Describe the problem and desired outcome. The inquiry can be routed to the most appropriate starting point after review.', 'sustainable-catalyst-engagement-intake' ),
			),
		);
	}

	public static function route_guidance(): array {
		return array(
			'product_support'           => __( 'Include the product, version, component, environment, exact error message, reproduction steps, expected behavior, and actual behavior. Do not include passwords or secrets.', 'sustainable-catalyst-engagement-intake' ),
			'general'                   => __( 'Keep the request concise. General inquiries do not need project, budget, or procurement details.', 'sustainable-catalyst-engagement-intake' ),
			'consulting'                => __( 'Include the current problem, desired outcome, budget range, timeline, and the decision the engagement should support.', 'sustainable-catalyst-engagement-intake' ),
			'research_collaboration'    => __( 'Explain the research topic, proposed contribution, current stage, methodology, and intended public or institutional outcome.', 'sustainable-catalyst-engagement-intake' ),
			'platform_technical'        => __( 'Include the current technical environment, repository or platform link, constraints, and expected implementation or architecture outcome.', 'sustainable-catalyst-engagement-intake' ),
			'workshop_training'         => __( 'Include the audience, group size, desired learning outcomes, delivery format, and preferred timing.', 'sustainable-catalyst-engagement-intake' ),
			'monthly_advisory'          => __( 'Describe the recurring decisions, research, systems, documentation, or implementation support required each month.', 'sustainable-catalyst-engagement-intake' ),
			'speaking_media'            => __( 'Include the topic, format, audience, recording expectations, event date, and publication or production deadline.', 'sustainable-catalyst-engagement-intake' ),
			'open_source'               => __( 'Include the repository, issue, implementation context, and whether the request is support, collaboration, documentation, or contribution-related.', 'sustainable-catalyst-engagement-intake' ),
			'institutional_partnership' => __( 'Include the internal sponsor, stakeholders, funding or procurement status, desired relationship, and institutional timeline.', 'sustainable-catalyst-engagement-intake' ),
			'other'                     => __( 'Describe the request clearly and explain why none of the other paths fits.', 'sustainable-catalyst-engagement-intake' ),
		);
	}

	public static function label( array $choices, string $value ): string {
		return $choices[ $value ] ?? $value;
	}
}
