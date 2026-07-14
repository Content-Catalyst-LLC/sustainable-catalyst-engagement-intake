<?php
/**
 * Inquiry statuses and types.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Statuses {

	public static function all(): array {
		return apply_filters(
			'sc_ei_statuses',
			array(
				'new'                      => __( 'New', 'sustainable-catalyst-engagement-intake' ),
				'under_review'             => __( 'Under Review', 'sustainable-catalyst-engagement-intake' ),
				'more_information_needed'  => __( 'More Information Needed', 'sustainable-catalyst-engagement-intake' ),
				'fit_call_recommended'     => __( 'Fit Call Recommended', 'sustainable-catalyst-engagement-intake' ),
				'consultation_recommended' => __( 'Consultation Recommended', 'sustainable-catalyst-engagement-intake' ),
				'proposal_requested'       => __( 'Proposal Requested', 'sustainable-catalyst-engagement-intake' ),
				'proposal_sent'            => __( 'Proposal Sent', 'sustainable-catalyst-engagement-intake' ),
				'accepted'                 => __( 'Accepted', 'sustainable-catalyst-engagement-intake' ),
				'not_a_fit'                => __( 'Not a Fit', 'sustainable-catalyst-engagement-intake' ),
				'referred'                 => __( 'Referred', 'sustainable-catalyst-engagement-intake' ),
				'withdrawn'                => __( 'Withdrawn', 'sustainable-catalyst-engagement-intake' ),
				'closed'                   => __( 'Closed', 'sustainable-catalyst-engagement-intake' ),
			)
		);
	}

	public static function inquiry_types(): array {
		return apply_filters(
			'sc_ei_inquiry_types',
			array(
				'general'                   => __( 'General Question', 'sustainable-catalyst-engagement-intake' ),
				'consulting'                => __( 'Consulting or Advisory', 'sustainable-catalyst-engagement-intake' ),
				'research_collaboration'    => __( 'Research Collaboration', 'sustainable-catalyst-engagement-intake' ),
				'product_support'          => __( 'Product Support', 'sustainable-catalyst-engagement-intake' ),
				'platform_technical'        => __( 'Platform or Technical Work', 'sustainable-catalyst-engagement-intake' ),
				'workshop_training'         => __( 'Workshop or Training', 'sustainable-catalyst-engagement-intake' ),
				'monthly_advisory'          => __( 'Monthly Advisory', 'sustainable-catalyst-engagement-intake' ),
				'speaking_media'            => __( 'Speaking, Media, or Press', 'sustainable-catalyst-engagement-intake' ),
				'open_source'               => __( 'Open-Source Inquiry', 'sustainable-catalyst-engagement-intake' ),
				'institutional_partnership' => __( 'Institutional Partnership', 'sustainable-catalyst-engagement-intake' ),
				'other'                     => __( 'Other', 'sustainable-catalyst-engagement-intake' ),
			)
		);
	}

	public static function is_valid( string $status ): bool {
		return array_key_exists( $status, self::all() );
	}

	public static function label( string $status ): string {
		$statuses = self::all();
		return $statuses[ $status ] ?? $status;
	}
}
