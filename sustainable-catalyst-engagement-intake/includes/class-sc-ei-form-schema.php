<?php
/**
 * Public form choices and conditional routing.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Form_Schema {

	public static function general_types(): array {
		$types = SC_EI_Statuses::inquiry_types();
		$keys  = array(
			'general',
			'research_collaboration',
			'speaking_media',
			'open_source',
			'other',
		);

		return array_intersect_key( $types, array_flip( $keys ) );
	}

	public static function engagement_types(): array {
		$types = SC_EI_Statuses::inquiry_types();
		$keys  = array(
			'consulting',
			'platform_technical',
			'workshop_training',
			'monthly_advisory',
			'institutional_partnership',
		);

		return array_intersect_key( $types, array_flip( $keys ) );
	}

	public static function all_public_types(): array {
		return array_merge( self::general_types(), self::engagement_types() );
	}

	public static function service_interests(): array {
		return apply_filters(
			'sc_ei_service_interests',
			array(
				'initial_fit_call'            => __( 'Initial Fit Call', 'sustainable-catalyst-engagement-intake' ),
				'strategic_consultation'      => __( 'Strategic Advisory Consultation', 'sustainable-catalyst-engagement-intake' ),
				'evidence_systems_diagnostic' => __( 'Evidence and Systems Diagnostic', 'sustainable-catalyst-engagement-intake' ),
				'strategy_architecture_sprint'=> __( 'Strategy and Architecture Sprint', 'sustainable-catalyst-engagement-intake' ),
				'knowledge_platform_build'    => __( 'Knowledge Platform or Workflow Build', 'sustainable-catalyst-engagement-intake' ),
				'training_workshop'           => __( 'Training or Workshop', 'sustainable-catalyst-engagement-intake' ),
				'monthly_advisory'            => __( 'Monthly Advisory Partnership', 'sustainable-catalyst-engagement-intake' ),
				'research_collaboration'      => __( 'Research Collaboration', 'sustainable-catalyst-engagement-intake' ),
				'speaking_media'              => __( 'Speaking, Media, or Press', 'sustainable-catalyst-engagement-intake' ),
				'open_source_technical'       => __( 'Open-Source or Technical Inquiry', 'sustainable-catalyst-engagement-intake' ),
				'other'                       => __( 'Other or Unsure', 'sustainable-catalyst-engagement-intake' ),
			)
		);
	}

	public static function budget_ranges(): array {
		return apply_filters(
			'sc_ei_budget_ranges',
			array(
				'not_applicable' => __( 'Not applicable / general inquiry', 'sustainable-catalyst-engagement-intake' ),
				'under_1000'     => __( 'Under $1,000', 'sustainable-catalyst-engagement-intake' ),
				'1000_2500'      => __( '$1,000–$2,500', 'sustainable-catalyst-engagement-intake' ),
				'2500_5000'      => __( '$2,500–$5,000', 'sustainable-catalyst-engagement-intake' ),
				'5000_10000'     => __( '$5,000–$10,000', 'sustainable-catalyst-engagement-intake' ),
				'10000_25000'    => __( '$10,000–$25,000', 'sustainable-catalyst-engagement-intake' ),
				'25000_plus'     => __( '$25,000+', 'sustainable-catalyst-engagement-intake' ),
				'not_sure'       => __( 'Not sure yet', 'sustainable-catalyst-engagement-intake' ),
			)
		);
	}

	public static function referral_sources(): array {
		return apply_filters(
			'sc_ei_referral_sources',
			array(
				'search'      => __( 'Search engine', 'sustainable-catalyst-engagement-intake' ),
				'linkedin'    => __( 'LinkedIn', 'sustainable-catalyst-engagement-intake' ),
				'publication' => __( 'Sustainable Catalyst publication or article', 'sustainable-catalyst-engagement-intake' ),
				'github'      => __( 'GitHub or open-source project', 'sustainable-catalyst-engagement-intake' ),
				'referral'    => __( 'Personal or professional referral', 'sustainable-catalyst-engagement-intake' ),
				'event'       => __( 'Event, workshop, or media appearance', 'sustainable-catalyst-engagement-intake' ),
				'other'       => __( 'Other', 'sustainable-catalyst-engagement-intake' ),
			)
		);
	}

	public static function type_requires_engagement_fields( string $type ): bool {
		return array_key_exists( $type, self::engagement_types() );
	}

	public static function type_requires_event_fields( string $type ): bool {
		return 'speaking_media' === $type;
	}
}
