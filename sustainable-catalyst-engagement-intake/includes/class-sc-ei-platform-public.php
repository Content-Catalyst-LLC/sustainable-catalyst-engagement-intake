<?php
/**
 * Unified public contact and engagement entry point.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Platform_Public {

	public static function register(): void {
		add_shortcode( 'sc_contact_engagement_platform', array( __CLASS__, 'shortcode' ) );
	}

	public static function shortcode( array $atts = array() ): string {
		$settings = SC_EI_Platform_Repository::settings();
		$atts = shortcode_atts(
			array(
				'title'          => __( 'Contact and Engagement Platform', 'sustainable-catalyst-engagement-intake' ),
				'intro'          => __( 'Use one governed entry point for general questions, research collaboration, speaking, partnerships, and substantive advisory engagements.', 'sustainable-catalyst-engagement-intake' ),
				'portal_url'     => (string) ( ! empty( $settings['platform_portal_page_url'] ) ? $settings['platform_portal_page_url'] : ( $settings['portal_page_url'] ?? '' ) ),
				'privacy_url'    => (string) $settings['platform_privacy_page_url'],
				'source'         => 'unified-platform',
				'entry_cta'      => 'contact-engagement-platform',
				'default_type'   => 'general',
				'show_form'      => 'yes',
				'show_portal'    => 'yes',
				'show_privacy'   => 'yes',
			),
			$atts,
			'sc_contact_engagement_platform'
		);

		$portal_url = esc_url_raw( (string) $atts['portal_url'] );
		$privacy_url = esc_url_raw( (string) $atts['privacy_url'] );
		$show_form = 'yes' === strtolower( sanitize_text_field( (string) $atts['show_form'] ) );
		$show_portal = 'yes' === strtolower( sanitize_text_field( (string) $atts['show_portal'] ) );
		$show_privacy = 'yes' === strtolower( sanitize_text_field( (string) $atts['show_privacy'] ) );

		$form = '';
		if ( $show_form ) {
			$form = SC_EI_Public::contact_hub(
				array(
					'mode'         => 'advanced',
					'source'       => sanitize_key( (string) $atts['source'] ),
					'entry_cta'    => sanitize_key( (string) $atts['entry_cta'] ),
					'title'        => __( 'Start a Contact or Engagement Request', 'sustainable-catalyst-engagement-intake' ),
					'intro'        => __( 'Choose the inquiry type that best matches your request. The form will show only the fields needed for that path.', 'sustainable-catalyst-engagement-intake' ),
					'default_type' => sanitize_key( (string) $atts['default_type'] ),
				)
			);
		}

		ob_start();
		?>
		<section class="sc-ei-platform-public" aria-labelledby="sc-ei-platform-title">
			<header class="sc-ei-platform-public__hero">
				<p class="sc-ei-platform-public__eyebrow"><?php esc_html_e( 'Sustainable Catalyst', 'sustainable-catalyst-engagement-intake' ); ?></p>
				<h2 id="sc-ei-platform-title"><?php echo esc_html( sanitize_text_field( (string) $atts['title'] ) ); ?></h2>
				<p><?php echo esc_html( sanitize_textarea_field( (string) $atts['intro'] ) ); ?></p>
			</header>

			<div class="sc-ei-platform-public__routes" aria-label="<?php esc_attr_e( 'Contact and engagement paths', 'sustainable-catalyst-engagement-intake' ); ?>">
				<article>
					<p class="sc-ei-platform-public__number">01</p>
					<h3><?php esc_html_e( 'Start a Request', 'sustainable-catalyst-engagement-intake' ); ?></h3>
					<p><?php esc_html_e( 'General contact, research collaboration, media, speaking, partnerships, technical questions, or a substantive engagement.', 'sustainable-catalyst-engagement-intake' ); ?></p>
					<?php if ( $show_form ) : ?><a class="sc-ei-platform-public__button sc-ei-platform-public__button--primary" href="#sc-ei-platform-form"><?php esc_html_e( 'Choose a Request Path', 'sustainable-catalyst-engagement-intake' ); ?></a><?php endif; ?>
				</article>
				<article>
					<p class="sc-ei-platform-public__number">02</p>
					<h3><?php esc_html_e( 'Return Securely', 'sustainable-catalyst-engagement-intake' ); ?></h3>
					<p><?php esc_html_e( 'Existing senders can review messages, meeting offers, proposals, engagement status, and privacy options through the secure sender portal.', 'sustainable-catalyst-engagement-intake' ); ?></p>
					<?php if ( $show_portal && $portal_url ) : ?><a class="sc-ei-platform-public__button" href="<?php echo esc_url( $portal_url ); ?>" rel="nofollow"><?php esc_html_e( 'Open Secure Sender Portal', 'sustainable-catalyst-engagement-intake' ); ?></a><?php else : ?><span class="sc-ei-platform-public__unavailable"><?php esc_html_e( 'Portal URL is being configured.', 'sustainable-catalyst-engagement-intake' ); ?></span><?php endif; ?>
				</article>
				<article>
					<p class="sc-ei-platform-public__number">03</p>
					<h3><?php esc_html_e( 'Understand the Process', 'sustainable-catalyst-engagement-intake' ); ?></h3>
					<p><?php esc_html_e( 'Every request is reviewed by a person. Submitting a form does not create a contract, approve an engagement, schedule a meeting, or authorize payment.', 'sustainable-catalyst-engagement-intake' ); ?></p>
					<?php if ( $show_privacy && $privacy_url ) : ?><a class="sc-ei-platform-public__button" href="<?php echo esc_url( $privacy_url ); ?>"><?php esc_html_e( 'Read Privacy Guidance', 'sustainable-catalyst-engagement-intake' ); ?></a><?php endif; ?>
				</article>
			</div>

			<div class="sc-ei-platform-public__boundary" role="note">
				<strong><?php esc_html_e( 'Human-controlled workflow', 'sustainable-catalyst-engagement-intake' ); ?></strong>
				<span><?php esc_html_e( 'AI or automation may assist with organization and analysis, but cannot accept or reject an inquiry, determine fit, publish a proposal, record a contract, or activate an engagement.', 'sustainable-catalyst-engagement-intake' ); ?></span>
			</div>

			<?php if ( $show_form ) : ?><div id="sc-ei-platform-form" class="sc-ei-platform-public__form"><?php echo $form; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><?php endif; ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}
