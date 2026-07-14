<?php
/**
 * Public shortcodes and adaptive forms.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class SC_EI_Public {

	private static int $form_count = 0;
	private static bool $assets_enqueued = false;

	public static function register(): void {
		add_shortcode( 'sc_contact_hub', array( __CLASS__, 'contact_hub' ) );
		add_shortcode( 'sc_contact_form', array( __CLASS__, 'contact_form' ) );
		add_shortcode( 'sc_engagement_inquiry', array( __CLASS__, 'engagement_form' ) );
		add_shortcode( 'sc_support_request', array( __CLASS__, 'support_form' ) );
	}

	public static function contact_hub( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'mode'         => 'advanced',
				'source'       => 'contact-page',
				'entry_cta'    => 'contact-hub',
				'title'        => __( 'Contact Sustainable Catalyst', 'sustainable-catalyst-engagement-intake' ),
				'intro'        => __( 'Choose the inquiry path that best matches the question, project, collaboration, or engagement.', 'sustainable-catalyst-engagement-intake' ),
				'default_type' => 'general',
				'default_service' => '',
			),
			$atts,
			'sc_contact_hub'
		);

		return self::render_adaptive(
			'advanced',
			SC_EI_Form_Schema::all_public_types(),
			sanitize_text_field( $atts['title'] ),
			sanitize_textarea_field( $atts['intro'] ),
			sanitize_key( $atts['default_type'] ),
			sanitize_key( (string) $atts['default_service'] ),
			SC_EI_Conversion::sanitize_source( (string) $atts['source'] ),
			SC_EI_Conversion::sanitize_entry_cta( (string) $atts['entry_cta'] )
		);
	}

	public static function contact_form( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'mode'         => 'general',
				'source'       => 'contact-page',
				'entry_cta'    => 'general-contact-form',
				'title'        => __( 'Send a General Inquiry', 'sustainable-catalyst-engagement-intake' ),
				'intro'        => __( 'Use this form for general questions, research collaboration, media, speaking, open-source work, or another non-consulting inquiry.', 'sustainable-catalyst-engagement-intake' ),
				'default_type' => 'general',
			),
			$atts,
			'sc_contact_form'
		);

		return self::render_adaptive(
			'general',
			SC_EI_Form_Schema::general_types(),
			sanitize_text_field( $atts['title'] ),
			sanitize_textarea_field( $atts['intro'] ),
			sanitize_key( $atts['default_type'] ),
			'',
			SC_EI_Conversion::sanitize_source( (string) $atts['source'] ),
			SC_EI_Conversion::sanitize_entry_cta( (string) $atts['entry_cta'] )
		);
	}

	public static function engagement_form( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'mode'         => 'consulting',
				'source'       => 'consulting-page',
				'entry_cta'    => 'discuss-an-engagement',
				'title'        => __( 'Discuss an Engagement', 'sustainable-catalyst-engagement-intake' ),
				'intro'        => __( 'Share the problem, desired outcome, budget range, and preferred next step.', 'sustainable-catalyst-engagement-intake' ),
				'default_type' => 'consulting',
			),
			$atts,
			'sc_engagement_inquiry'
		);

		$mode = sanitize_key( (string) $atts['mode'] );
		if ( 'compact' === $mode ) {
			return self::render_compact(
				sanitize_text_field( $atts['title'] ),
				sanitize_textarea_field( $atts['intro'] ),
				SC_EI_Conversion::sanitize_source( (string) $atts['source'] ),
				SC_EI_Conversion::sanitize_entry_cta( (string) $atts['entry_cta'] )
			);
		}

		return self::render_adaptive(
			'consulting',
			SC_EI_Form_Schema::engagement_types(),
			sanitize_text_field( $atts['title'] ),
			sanitize_textarea_field( $atts['intro'] ),
			sanitize_key( $atts['default_type'] ),
			'',
			SC_EI_Conversion::sanitize_source( (string) $atts['source'] ),
			SC_EI_Conversion::sanitize_entry_cta( (string) $atts['entry_cta'] )
		);
	}


	public static function support_form( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'source'       => 'support-page',
				'entry_cta'    => 'support-request',
				'title'        => __( 'Report a Product or Platform Issue', 'sustainable-catalyst-engagement-intake' ),
				'intro'        => __( 'Provide the affected product, version, component, environment, exact error, and reproduction steps. Private files enter protected quarantine.', 'sustainable-catalyst-engagement-intake' ),
			),
			$atts,
			'sc_support_request'
		);
		return self::render_adaptive(
			'general',
			array( 'product_support' => SC_EI_Statuses::inquiry_types()['product_support'] ),
			sanitize_text_field( $atts['title'] ),
			sanitize_textarea_field( $atts['intro'] ),
			'product_support',
			'product_support',
			SC_EI_Conversion::sanitize_source( (string) $atts['source'] ),
			SC_EI_Conversion::sanitize_entry_cta( (string) $atts['entry_cta'] )
		);
	}

	private static function render_compact( string $title, string $intro, string $source, string $entry_cta ): string {
		self::protect_dynamic_form_page();
		self::enqueue_assets();

		self::$form_count++;
		$form_id                = 'sc-ei-compact-' . self::$form_count;
		$settings               = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() );
		$default_teams_duration = absint( $settings['default_teams_duration'] ?? 20 );
		$effective_upload_limits = SC_EI_Upload_Environment::effective_limits( $settings );
		$upload_max_files       = $effective_upload_limits['max_files'];
		$upload_max_mb          = max( 1, (int) floor( $effective_upload_limits['max_file_bytes'] / MB_IN_BYTES ) );
		$upload_total_max_bytes = $effective_upload_limits['max_total_bytes'];
		$upload_extensions      = array_values( array_intersect( array_keys( SC_EI_Upload_Validator::supported_extensions() ), (array) ( $settings['allowed_upload_extensions'] ?? array() ) ) );
		$request_id             = wp_generate_uuid4();
		$started_at             = time();
		$signature              = SC_EI_Form_Handler::timing_signature( $started_at, $form_id );
		$attribution_signature  = SC_EI_Form_Handler::attribution_signature( 'compact', $source, $entry_cta, $form_id );
		$result                 = isset( $_GET['sc_ei_result'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_result'] ) ) : '';
		$error                  = isset( $_GET['sc_ei_error'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_error'] ) ) : '';
		$reference              = isset( $_GET['sc_ei_reference'] ) ? sanitize_text_field( wp_unslash( $_GET['sc_ei_reference'] ) ) : '';
		$file_count             = isset( $_GET['sc_ei_files'] ) ? absint( $_GET['sc_ei_files'] ) : 0;
		$file_warning           = ! empty( $_GET['sc_ei_file_warning'] );

		ob_start();
		?>
		<div class="sc-ei-public sc-ei-public--compact" data-sc-ei-hub>
			<div class="sc-ei-public__header sc-ei-public__header--compact">
				<p class="sc-ei-public__eyebrow"><?php esc_html_e( 'Private Consulting Intake', 'sustainable-catalyst-engagement-intake' ); ?></p>
				<h2><?php echo esc_html( $title ); ?></h2>
				<p><?php echo esc_html( $intro ); ?></p>
			</div>

			<?php echo self::render_feedback( $result, $error, $reference, $file_count, $file_warning ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<form
				id="<?php echo esc_attr( $form_id ); ?>"
				class="sc-ei-form sc-ei-form--compact"
				method="post"
				enctype="multipart/form-data"
				action="<?php echo esc_url( add_query_arg( 'sc_ei_submission', '1', admin_url( 'admin-post.php' ) ) ); ?>"
				data-sc-ei-compact-form
				data-mode="compact"
				novalidate
			>
				<input type="hidden" name="action" value="sc_ei_submit">
				<input type="hidden" name="form_mode" value="compact">
				<input type="hidden" name="form_variant" value="compact">
				<input type="hidden" name="source_page" value="<?php echo esc_attr( $source ); ?>">
				<input type="hidden" name="entry_cta" value="<?php echo esc_attr( $entry_cta ); ?>">
				<input type="hidden" name="attribution_signature" value="<?php echo esc_attr( $attribution_signature ); ?>">
				<input type="hidden" name="inquiry_type" value="consulting">
				<input type="hidden" name="form_id" value="<?php echo esc_attr( $form_id ); ?>">
				<input type="hidden" name="form_started_at" value="<?php echo esc_attr( $started_at ); ?>">
				<input type="hidden" name="form_signature" value="<?php echo esc_attr( $signature ); ?>">
				<input type="hidden" name="request_id" value="<?php echo esc_attr( $request_id ); ?>">
				<input type="hidden" name="document_selection_count" value="0" data-sc-ei-document-count>
				<input type="hidden" name="document_selection_bytes" value="0" data-sc-ei-document-bytes>
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( self::current_url() ); ?>">
				<input type="hidden" name="source_url" value="<?php echo esc_url( self::current_url() ); ?>">
				<input type="hidden" name="preferred_duration" value="<?php echo esc_attr( $default_teams_duration ); ?>">
				<input type="hidden" name="participant_count" value="1">
				<?php wp_nonce_field( SC_EI_Form_Handler::nonce_action(), 'sc_ei_nonce' ); ?>

				<noscript>
					<style>
						#<?php echo esc_attr( $form_id ); ?> .sc-ei-controller-conditional[hidden] {
							display: block !important;
						}
					</style>
					<div class="sc-ei-feedback">
						<strong><?php esc_html_e( 'JavaScript is disabled.', 'sustainable-catalyst-engagement-intake' ); ?></strong>
						<span><?php esc_html_e( 'The optional Teams fields are visible. Complete them only when requesting a Teams fit call.', 'sustainable-catalyst-engagement-intake' ); ?></span>
					</div>
				</noscript>

				<div class="sc-ei-honeypot" aria-hidden="true">
					<label for="<?php echo esc_attr( $form_id . '-website' ); ?>">Website</label>
					<input id="<?php echo esc_attr( $form_id . '-website' ); ?>" type="text" name="company_website" tabindex="-1" autocomplete="off">
				</div>

				<div class="sc-ei-form__errors" data-sc-ei-errors role="alert" aria-live="assertive" hidden></div>

				<div class="sc-ei-field-grid">
					<div class="sc-ei-field">
						<label for="<?php echo esc_attr( $form_id . '-name' ); ?>"><?php esc_html_e( 'Name', 'sustainable-catalyst-engagement-intake' ); ?> <span aria-hidden="true">*</span></label>
						<input id="<?php echo esc_attr( $form_id . '-name' ); ?>" type="text" name="contact_name" maxlength="191" autocomplete="name" required>
					</div>
					<div class="sc-ei-field">
						<label for="<?php echo esc_attr( $form_id . '-email' ); ?>"><?php esc_html_e( 'Email', 'sustainable-catalyst-engagement-intake' ); ?> <span aria-hidden="true">*</span></label>
						<input id="<?php echo esc_attr( $form_id . '-email' ); ?>" type="email" name="contact_email" maxlength="191" autocomplete="email" required>
					</div>
				</div>

				<div class="sc-ei-field">
					<label for="<?php echo esc_attr( $form_id . '-organization' ); ?>"><?php esc_html_e( 'Organization', 'sustainable-catalyst-engagement-intake' ); ?></label>
					<input id="<?php echo esc_attr( $form_id . '-organization' ); ?>" type="text" name="organization" maxlength="191" autocomplete="organization">
				</div>

				<div class="sc-ei-field-grid">
					<div class="sc-ei-field">
						<label for="<?php echo esc_attr( $form_id . '-service' ); ?>"><?php esc_html_e( 'Best-fit engagement', 'sustainable-catalyst-engagement-intake' ); ?> <span aria-hidden="true">*</span></label>
						<select id="<?php echo esc_attr( $form_id . '-service' ); ?>" name="service_interest" required data-sc-ei-compact-service>
							<option value=""><?php esc_html_e( 'Choose the closest match', 'sustainable-catalyst-engagement-intake' ); ?></option>
							<?php foreach ( SC_EI_Form_Schema::compact_service_interests() as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="sc-ei-field">
						<label for="<?php echo esc_attr( $form_id . '-budget' ); ?>"><?php esc_html_e( 'Available budget range', 'sustainable-catalyst-engagement-intake' ); ?> <span aria-hidden="true">*</span></label>
						<select id="<?php echo esc_attr( $form_id . '-budget' ); ?>" name="budget_range" required data-sc-ei-compact-budget>
							<option value=""><?php esc_html_e( 'Choose a range', 'sustainable-catalyst-engagement-intake' ); ?></option>
							<?php foreach ( SC_EI_Form_Schema::compact_budget_ranges() as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $default_service, $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<div class="sc-ei-guidance sc-ei-guidance--compact" data-sc-ei-pricing-guidance aria-live="polite" hidden></div>

				<div class="sc-ei-field">
					<label for="<?php echo esc_attr( $form_id . '-project' ); ?>"><?php esc_html_e( 'What problem are you trying to solve?', 'sustainable-catalyst-engagement-intake' ); ?> <span aria-hidden="true">*</span></label>
					<textarea id="<?php echo esc_attr( $form_id . '-project' ); ?>" name="project_summary" rows="5" maxlength="12000" required></textarea>
				</div>

				<div class="sc-ei-field">
					<label for="<?php echo esc_attr( $form_id . '-outcome' ); ?>"><?php esc_html_e( 'What outcome or decision would make this useful?', 'sustainable-catalyst-engagement-intake' ); ?> <span aria-hidden="true">*</span></label>
					<textarea id="<?php echo esc_attr( $form_id . '-outcome' ); ?>" name="desired_outcome" rows="4" maxlength="12000" required></textarea>
				</div>

				<div class="sc-ei-field-grid">
					<div class="sc-ei-field">
						<label for="<?php echo esc_attr( $form_id . '-start' ); ?>"><?php esc_html_e( 'Desired start date', 'sustainable-catalyst-engagement-intake' ); ?></label>
						<input id="<?php echo esc_attr( $form_id . '-start' ); ?>" type="date" name="desired_start_date">
					</div>
					<div class="sc-ei-field">
						<label for="<?php echo esc_attr( $form_id . '-link' ); ?>"><?php esc_html_e( 'Most relevant public link', 'sustainable-catalyst-engagement-intake' ); ?></label>
						<input id="<?php echo esc_attr( $form_id . '-link' ); ?>" type="url" name="relevant_links" placeholder="https://">
					</div>
				</div>

				<?php echo self::render_document_fields( $form_id, $upload_max_files, $upload_max_mb, $upload_total_max_bytes, $upload_extensions, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

				<div class="sc-ei-field">
					<label for="<?php echo esc_attr( $form_id . '-next-step' ); ?>"><?php esc_html_e( 'Preferred next step', 'sustainable-catalyst-engagement-intake' ); ?> <span aria-hidden="true">*</span></label>
					<select id="<?php echo esc_attr( $form_id . '-next-step' ); ?>" name="compact_next_step" required data-sc-ei-compact-next-step>
						<option value="email_first"><?php esc_html_e( 'Continue by email first', 'sustainable-catalyst-engagement-intake' ); ?></option>
						<option value="teams_fit_call"><?php esc_html_e( 'Request a Microsoft Teams fit call', 'sustainable-catalyst-engagement-intake' ); ?></option>
					</select>
				</div>

				<div class="sc-ei-controller-conditional sc-ei-compact-teams" data-compact-next-step-show="teams_fit_call" hidden>
					<div class="sc-ei-field-grid">
						<div class="sc-ei-field">
							<label for="<?php echo esc_attr( $form_id . '-teams-email' ); ?>"><?php esc_html_e( 'Microsoft Teams email', 'sustainable-catalyst-engagement-intake' ); ?> <span aria-hidden="true">*</span></label>
							<input id="<?php echo esc_attr( $form_id . '-teams-email' ); ?>" type="email" name="teams_email" maxlength="191" autocomplete="email" data-required-when-visible>
						</div>
						<div class="sc-ei-field">
							<label for="<?php echo esc_attr( $form_id . '-timezone' ); ?>"><?php esc_html_e( 'Time zone', 'sustainable-catalyst-engagement-intake' ); ?> <span aria-hidden="true">*</span></label>
							<input id="<?php echo esc_attr( $form_id . '-timezone' ); ?>" type="text" name="timezone" maxlength="120" list="<?php echo esc_attr( $form_id . '-timezones' ); ?>" placeholder="America/Chicago" data-sc-ei-timezone data-required-when-visible>
							<datalist id="<?php echo esc_attr( $form_id . '-timezones' ); ?>">
								<?php foreach ( SC_EI_Teams::timezone_identifiers() as $timezone_id ) : ?>
									<option value="<?php echo esc_attr( $timezone_id ); ?>"></option>
								<?php endforeach; ?>
							</datalist>
						</div>
					</div>

					<div class="sc-ei-field">
						<label for="<?php echo esc_attr( $form_id . '-availability' ); ?>"><?php esc_html_e( 'General availability', 'sustainable-catalyst-engagement-intake' ); ?></label>
						<textarea id="<?php echo esc_attr( $form_id . '-availability' ); ?>" name="preferred_time_windows" rows="3" maxlength="12000" placeholder="<?php esc_attr_e( 'Example: Weekdays, 9:00 a.m.–1:00 p.m. America/Chicago', 'sustainable-catalyst-engagement-intake' ); ?>"></textarea>
					</div>

					<label class="sc-ei-check">
						<input type="checkbox" name="calendar_invite_consent" value="1" data-required-when-visible>
						<span><?php esc_html_e( 'Sustainable Catalyst may send a Microsoft Teams calendar invitation if the fit call is approved.', 'sustainable-catalyst-engagement-intake' ); ?> <b aria-hidden="true">*</b></span>
					</label>
				</div>

				<div class="sc-ei-privacy-box sc-ei-privacy-box--compact">
					<strong><?php esc_html_e( 'Private inquiry boundary', 'sustainable-catalyst-engagement-intake' ); ?></strong>
					<p><?php esc_html_e( 'Selected documents are validated and placed in protected quarantine. Do not submit passwords, payment-card data, regulated health records, highly sensitive personal data, export-controlled material, or files you are not authorized to share.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				</div>

				<label class="sc-ei-check">
					<input type="checkbox" name="privacy_consent" value="1" required>
					<span><?php esc_html_e( 'I authorize Sustainable Catalyst to process this inquiry and respond about relevant next steps.', 'sustainable-catalyst-engagement-intake' ); ?> <b aria-hidden="true">*</b></span>
				</label>
				<label class="sc-ei-check">
					<input type="checkbox" name="authorization_consent" value="1" required>
					<span><?php esc_html_e( 'I am authorized to share the information, public links, and documents included here.', 'sustainable-catalyst-engagement-intake' ); ?> <b aria-hidden="true">*</b></span>
				</label>
				<input type="hidden" name="follow_up_consent" value="1">

				<div class="sc-ei-actions">
					<button type="submit" class="sc-ei-button sc-ei-button--primary" data-sc-ei-submit>
						<span><?php esc_html_e( 'Submit Engagement Inquiry', 'sustainable-catalyst-engagement-intake' ); ?></span>
					</button>
				</div>

				<div class="sc-ei-success" data-sc-ei-success role="status" aria-live="polite" hidden>
					<p class="sc-ei-success__eyebrow"><?php esc_html_e( 'Engagement inquiry received', 'sustainable-catalyst-engagement-intake' ); ?></p>
					<h3><?php esc_html_e( 'Your private consulting inquiry has been recorded.', 'sustainable-catalyst-engagement-intake' ); ?></h3>
					<p><?php esc_html_e( 'Save this reference:', 'sustainable-catalyst-engagement-intake' ); ?></p>
					<strong data-sc-ei-reference></strong>
					<p data-sc-ei-attachment-summary hidden></p>
					<div class="sc-ei-success__warnings" data-sc-ei-attachment-warnings hidden></div>
					<p><?php esc_html_e( 'A Teams fit-call request remains pending until the inquiry is reviewed and approved.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				</div>
			</form>
		</div>
		<?php
		do_action( 'sc_ei_form_rendered', 'compact', $source, $entry_cta );
		return (string) ob_get_clean();
	}


	private static function render_adaptive( string $mode, array $types, string $title, string $intro, string $default_type, string $default_service, string $source, string $entry_cta ): string {
		self::protect_dynamic_form_page();
		self::enqueue_assets();

		self::$form_count++;
		$form_id = 'sc-ei-form-' . self::$form_count;
		if ( ! array_key_exists( $default_type, $types ) ) {
			$default_type = (string) array_key_first( $types );
		}
		if ( ! array_key_exists( $default_service, SC_EI_Form_Schema::service_interests() ) ) {
			$default_service = '';
		}

		$settings               = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() );
		$default_teams_duration = absint( $settings['default_teams_duration'] ?? 20 );
		$effective_upload_limits = SC_EI_Upload_Environment::effective_limits( $settings );
		$upload_max_files       = $effective_upload_limits['max_files'];
		$upload_max_mb          = max( 1, (int) floor( $effective_upload_limits['max_file_bytes'] / MB_IN_BYTES ) );
		$upload_total_max_bytes = $effective_upload_limits['max_total_bytes'];
		$upload_extensions      = array_values( array_intersect( array_keys( SC_EI_Upload_Validator::supported_extensions() ), (array) ( $settings['allowed_upload_extensions'] ?? array() ) ) );
		$request_id             = wp_generate_uuid4();
		$started_at             = time();
		$signature              = SC_EI_Form_Handler::timing_signature( $started_at, $form_id );
		$attribution_signature = SC_EI_Form_Handler::attribution_signature( $mode, $source, $entry_cta, $form_id );
		$result     = isset( $_GET['sc_ei_result'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_result'] ) ) : '';
		$error      = isset( $_GET['sc_ei_error'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_error'] ) ) : '';
		$reference  = isset( $_GET['sc_ei_reference'] ) ? sanitize_text_field( wp_unslash( $_GET['sc_ei_reference'] ) ) : '';
		$file_count = isset( $_GET['sc_ei_files'] ) ? absint( $_GET['sc_ei_files'] ) : 0;
		$file_warning = ! empty( $_GET['sc_ei_file_warning'] );

		ob_start();
		?>
		<div class="sc-ei-public sc-ei-public--<?php echo esc_attr( $mode ); ?>" data-sc-ei-hub data-form-variant="<?php echo esc_attr( $mode ); ?>" data-source-page="<?php echo esc_attr( $source ); ?>" data-default-service="<?php echo esc_attr( $default_service ); ?>">
			<div class="sc-ei-public__header">
				<p class="sc-ei-public__eyebrow"><?php esc_html_e( 'Private Contact and Engagement Intake', 'sustainable-catalyst-engagement-intake' ); ?></p>
				<h2><?php echo esc_html( $title ); ?></h2>
				<p><?php echo esc_html( $intro ); ?></p>
			</div>

			<?php echo self::render_feedback( $result, $error, $reference, $file_count, $file_warning ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<?php if ( 'advanced' === $mode ) : ?>
				<div class="sc-ei-route-grid" aria-label="<?php esc_attr_e( 'Choose an inquiry path', 'sustainable-catalyst-engagement-intake' ); ?>">
					<?php foreach ( $types as $key => $label ) : ?>
						<button type="button" class="sc-ei-route-card" data-sc-ei-route="<?php echo esc_attr( $key ); ?>">
							<span><?php echo esc_html( self::route_kicker( $key ) ); ?></span>
							<strong><?php echo esc_html( $label ); ?></strong>
							<em><?php echo esc_html( self::route_description( $key ) ); ?></em>
						</button>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<form
				id="<?php echo esc_attr( $form_id ); ?>"
				class="sc-ei-form"
				method="post"
				enctype="multipart/form-data"
				action="<?php echo esc_url( add_query_arg( 'sc_ei_submission', '1', admin_url( 'admin-post.php' ) ) ); ?>"
				data-sc-ei-form
				data-mode="<?php echo esc_attr( $mode ); ?>"
				novalidate
			>
				<input type="hidden" name="action" value="sc_ei_submit">
				<input type="hidden" name="form_mode" value="<?php echo esc_attr( $mode ); ?>">
				<input type="hidden" name="form_variant" value="<?php echo esc_attr( $mode ); ?>">
				<input type="hidden" name="source_page" value="<?php echo esc_attr( $source ); ?>">
				<input type="hidden" name="entry_cta" value="<?php echo esc_attr( $entry_cta ); ?>">
				<input type="hidden" name="attribution_signature" value="<?php echo esc_attr( $attribution_signature ); ?>">
				<input type="hidden" name="form_id" value="<?php echo esc_attr( $form_id ); ?>">
				<input type="hidden" name="form_started_at" value="<?php echo esc_attr( $started_at ); ?>">
				<input type="hidden" name="form_signature" value="<?php echo esc_attr( $signature ); ?>">
				<input type="hidden" name="request_id" value="<?php echo esc_attr( $request_id ); ?>">
				<input type="hidden" name="document_selection_count" value="0" data-sc-ei-document-count>
				<input type="hidden" name="document_selection_bytes" value="0" data-sc-ei-document-bytes>
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( self::current_url() ); ?>">
				<input type="hidden" name="source_url" value="<?php echo esc_url( self::current_url() ); ?>">
				<?php wp_nonce_field( SC_EI_Form_Handler::nonce_action(), 'sc_ei_nonce' ); ?>

				<noscript>
					<style>
						#<?php echo esc_attr( $form_id ); ?> .sc-ei-step[hidden],
						#<?php echo esc_attr( $form_id ); ?> .sc-ei-conditional[hidden],
						#<?php echo esc_attr( $form_id ); ?> .sc-ei-controller-conditional[hidden] {
							display: block !important;
						}
						#<?php echo esc_attr( $form_id ); ?> .sc-ei-progress,
						#<?php echo esc_attr( $form_id ); ?> [data-sc-ei-next],
						#<?php echo esc_attr( $form_id ); ?> [data-sc-ei-back] {
							display: none !important;
						}
					</style>
					<div class="sc-ei-feedback">
						<strong><?php esc_html_e( 'JavaScript is disabled.', 'sustainable-catalyst-engagement-intake' ); ?></strong>
						<span><?php esc_html_e( 'All fields are shown in one form. Complete the fields relevant to the selected inquiry type and submit normally.', 'sustainable-catalyst-engagement-intake' ); ?></span>
					</div>
				</noscript>

				<div class="sc-ei-honeypot" aria-hidden="true">
					<label for="<?php echo esc_attr( $form_id . '-website' ); ?>">Website</label>
					<input id="<?php echo esc_attr( $form_id . '-website' ); ?>" type="text" name="company_website" tabindex="-1" autocomplete="off">
				</div>

				<div class="sc-ei-progress" aria-label="<?php esc_attr_e( 'Form progress', 'sustainable-catalyst-engagement-intake' ); ?>">
					<div class="sc-ei-progress__bar"><span data-sc-ei-progress-bar></span></div>
					<ol>
						<li class="is-active" data-sc-ei-progress-step="1"><?php esc_html_e( 'Route', 'sustainable-catalyst-engagement-intake' ); ?></li>
						<li data-sc-ei-progress-step="2"><?php esc_html_e( 'Details', 'sustainable-catalyst-engagement-intake' ); ?></li>
						<li data-sc-ei-progress-step="3"><?php esc_html_e( 'Review', 'sustainable-catalyst-engagement-intake' ); ?></li>
					</ol>
				</div>

				<div class="sc-ei-form__errors" data-sc-ei-errors role="alert" aria-live="assertive" hidden></div>

				<fieldset class="sc-ei-step is-active" data-sc-ei-step="1">
					<legend><?php esc_html_e( 'Choose the inquiry path', 'sustainable-catalyst-engagement-intake' ); ?></legend>

					<div class="sc-ei-field sc-ei-field--wide">
						<label for="<?php echo esc_attr( $form_id . '-inquiry-type' ); ?>">
							<?php esc_html_e( 'Inquiry type', 'sustainable-catalyst-engagement-intake' ); ?> <span aria-hidden="true">*</span>
						</label>
						<select id="<?php echo esc_attr( $form_id . '-inquiry-type' ); ?>" name="inquiry_type" required data-sc-ei-type>
							<?php foreach ( $types as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $default_type, $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="sc-ei-help"><?php esc_html_e( 'The selection changes which details are requested. It does not automatically determine fit or acceptance.', 'sustainable-catalyst-engagement-intake' ); ?></p>
					</div>

					<div class="sc-ei-guidance sc-ei-guidance--route" data-sc-ei-route-guidance aria-live="polite"></div>

					<div class="sc-ei-field-grid">
						<div class="sc-ei-field">
							<label for="<?php echo esc_attr( $form_id . '-name' ); ?>"><?php esc_html_e( 'Name', 'sustainable-catalyst-engagement-intake' ); ?> <span aria-hidden="true">*</span></label>
							<input id="<?php echo esc_attr( $form_id . '-name' ); ?>" type="text" name="contact_name" maxlength="191" autocomplete="name" required>
						</div>

						<div class="sc-ei-field">
							<label for="<?php echo esc_attr( $form_id . '-email' ); ?>"><?php esc_html_e( 'Email', 'sustainable-catalyst-engagement-intake' ); ?> <span aria-hidden="true">*</span></label>
							<input id="<?php echo esc_attr( $form_id . '-email' ); ?>" type="email" name="contact_email" maxlength="191" autocomplete="email" required>
						</div>

						<div class="sc-ei-field">
							<label for="<?php echo esc_attr( $form_id . '-organization' ); ?>"><?php esc_html_e( 'Organization', 'sustainable-catalyst-engagement-intake' ); ?></label>
							<input id="<?php echo esc_attr( $form_id . '-organization' ); ?>" type="text" name="organization" maxlength="191" autocomplete="organization">
						</div>

						<div class="sc-ei-field">
							<label for="<?php echo esc_attr( $form_id . '-role' ); ?>"><?php esc_html_e( 'Role or title', 'sustainable-catalyst-engagement-intake' ); ?></label>
							<input id="<?php echo esc_attr( $form_id . '-role' ); ?>" type="text" name="role_title" maxlength="191" autocomplete="organization-title">
						</div>
					</div>

					<div class="sc-ei-communication-box">
						<h3><?php esc_html_e( 'Communication preference', 'sustainable-catalyst-engagement-intake' ); ?></h3>
						<p><?php esc_html_e( 'Email is the default. Microsoft Teams is the only supported live meeting platform in this release.', 'sustainable-catalyst-engagement-intake' ); ?></p>

						<div class="sc-ei-field-grid">
							<div class="sc-ei-field">
								<label for="<?php echo esc_attr( $form_id . '-contact-method' ); ?>"><?php esc_html_e( 'Preferred response method', 'sustainable-catalyst-engagement-intake' ); ?> <span aria-hidden="true">*</span></label>
								<select id="<?php echo esc_attr( $form_id . '-contact-method' ); ?>" name="preferred_contact_method" required data-sc-ei-contact-method>
									<?php foreach ( SC_EI_Form_Schema::contact_methods() as $key => $label ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php selected( 'email', $key ); ?>><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="sc-ei-field sc-ei-controller-conditional" data-contact-method-show="teams" hidden>
								<label for="<?php echo esc_attr( $form_id . '-teams-email' ); ?>"><?php esc_html_e( 'Microsoft Teams email', 'sustainable-catalyst-engagement-intake' ); ?> <span aria-hidden="true">*</span></label>
								<input id="<?php echo esc_attr( $form_id . '-teams-email' ); ?>" type="email" name="teams_email" maxlength="191" autocomplete="email" data-required-when-visible>
							</div>

							<div class="sc-ei-field sc-ei-controller-conditional" data-contact-method-show="phone" hidden>
								<label for="<?php echo esc_attr( $form_id . '-phone' ); ?>"><?php esc_html_e( 'Phone number', 'sustainable-catalyst-engagement-intake' ); ?> <span aria-hidden="true">*</span></label>
								<input id="<?php echo esc_attr( $form_id . '-phone' ); ?>" type="tel" name="phone_number" maxlength="80" autocomplete="tel" data-required-when-visible>
							</div>
						</div>
					</div>

					<div class="sc-ei-actions">
						<button type="button" class="sc-ei-button sc-ei-button--primary" data-sc-ei-next><?php esc_html_e( 'Continue to Details', 'sustainable-catalyst-engagement-intake' ); ?></button>
					</div>
				</fieldset>

				<fieldset class="sc-ei-step" data-sc-ei-step="2" hidden>
					<legend><?php esc_html_e( 'Describe the inquiry', 'sustainable-catalyst-engagement-intake' ); ?></legend>

					<div class="sc-ei-field">
						<label for="<?php echo esc_attr( $form_id . '-subject' ); ?>"><?php esc_html_e( 'Subject', 'sustainable-catalyst-engagement-intake' ); ?> <span aria-hidden="true">*</span></label>
						<input id="<?php echo esc_attr( $form_id . '-subject' ); ?>" type="text" name="subject" maxlength="255" required>
					</div>

					<div class="sc-ei-field">
						<label for="<?php echo esc_attr( $form_id . '-message' ); ?>"><?php esc_html_e( 'Question or request', 'sustainable-catalyst-engagement-intake' ); ?> <span aria-hidden="true">*</span></label>
						<textarea id="<?php echo esc_attr( $form_id . '-message' ); ?>" name="message" rows="7" maxlength="12000" required></textarea>
						<p class="sc-ei-help"><?php esc_html_e( 'Explain the context, the current issue, and the kind of response or conversation you are seeking.', 'sustainable-catalyst-engagement-intake' ); ?></p>
					</div>


					<div class="sc-ei-conditional" data-show-for="product_support">
						<div class="sc-ei-guidance" role="note"><strong><?php esc_html_e( 'Product support context', 'sustainable-catalyst-engagement-intake' ); ?></strong><span><?php esc_html_e( 'Do not submit passwords, API keys, authentication tokens, payment data, or regulated records. Use protected uploads only for files you are authorized to share.', 'sustainable-catalyst-engagement-intake' ); ?></span></div>
						<div class="sc-ei-field-grid">
							<div class="sc-ei-field"><label for="<?php echo esc_attr( $form_id . '-support-product' ); ?>"><?php esc_html_e( 'Affected product', 'sustainable-catalyst-engagement-intake' ); ?> <span aria-hidden="true">*</span></label><select id="<?php echo esc_attr( $form_id . '-support-product' ); ?>" name="support_product" data-required-when-visible><option value=""><?php esc_html_e( 'Choose a product', 'sustainable-catalyst-engagement-intake' ); ?></option><?php foreach ( SC_EI_Support_Schema::products() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></div>
							<div class="sc-ei-field"><label for="<?php echo esc_attr( $form_id . '-support-version' ); ?>"><?php esc_html_e( 'Installed product version', 'sustainable-catalyst-engagement-intake' ); ?></label><input id="<?php echo esc_attr( $form_id . '-support-version' ); ?>" name="support_product_version" maxlength="80" placeholder="e.g. 5.0.0"></div>
							<div class="sc-ei-field"><label for="<?php echo esc_attr( $form_id . '-support-component' ); ?>"><?php esc_html_e( 'Component or module', 'sustainable-catalyst-engagement-intake' ); ?></label><input id="<?php echo esc_attr( $form_id . '-support-component' ); ?>" name="support_component" maxlength="120"></div>
							<div class="sc-ei-field"><label for="<?php echo esc_attr( $form_id . '-support-issue-type' ); ?>"><?php esc_html_e( 'Issue category', 'sustainable-catalyst-engagement-intake' ); ?></label><select id="<?php echo esc_attr( $form_id . '-support-issue-type' ); ?>" name="support_issue_type"><?php foreach ( SC_EI_Support_Schema::issue_types() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></div>
							<div class="sc-ei-field"><label for="<?php echo esc_attr( $form_id . '-support-browser' ); ?>"><?php esc_html_e( 'Browser', 'sustainable-catalyst-engagement-intake' ); ?></label><input id="<?php echo esc_attr( $form_id . '-support-browser' ); ?>" name="support_browser" maxlength="191"></div>
							<div class="sc-ei-field"><label for="<?php echo esc_attr( $form_id . '-support-os' ); ?>"><?php esc_html_e( 'Operating system', 'sustainable-catalyst-engagement-intake' ); ?></label><input id="<?php echo esc_attr( $form_id . '-support-os' ); ?>" name="support_os" maxlength="191"></div>
							<div class="sc-ei-field"><label for="<?php echo esc_attr( $form_id . '-support-wp' ); ?>"><?php esc_html_e( 'WordPress version', 'sustainable-catalyst-engagement-intake' ); ?></label><input id="<?php echo esc_attr( $form_id . '-support-wp' ); ?>" name="support_wordpress_version" maxlength="80"></div>
							<div class="sc-ei-field"><label for="<?php echo esc_attr( $form_id . '-support-php' ); ?>"><?php esc_html_e( 'PHP version', 'sustainable-catalyst-engagement-intake' ); ?></label><input id="<?php echo esc_attr( $form_id . '-support-php' ); ?>" name="support_php_version" maxlength="80"></div>
						</div>
						<div class="sc-ei-field"><label for="<?php echo esc_attr( $form_id . '-support-error' ); ?>"><?php esc_html_e( 'Exact error message', 'sustainable-catalyst-engagement-intake' ); ?></label><textarea id="<?php echo esc_attr( $form_id . '-support-error' ); ?>" name="support_error_message" rows="4" maxlength="12000"></textarea></div>
						<div class="sc-ei-field"><label for="<?php echo esc_attr( $form_id . '-support-reproduction' ); ?>"><?php esc_html_e( 'Steps to reproduce', 'sustainable-catalyst-engagement-intake' ); ?> <span aria-hidden="true">*</span></label><textarea id="<?php echo esc_attr( $form_id . '-support-reproduction' ); ?>" name="support_reproduction_steps" rows="6" maxlength="12000" data-required-when-visible></textarea></div>
						<div class="sc-ei-field-grid"><div class="sc-ei-field"><label for="<?php echo esc_attr( $form_id . '-support-expected' ); ?>"><?php esc_html_e( 'Expected behavior', 'sustainable-catalyst-engagement-intake' ); ?></label><textarea id="<?php echo esc_attr( $form_id . '-support-expected' ); ?>" name="support_expected_behavior" rows="4" maxlength="12000"></textarea></div><div class="sc-ei-field"><label for="<?php echo esc_attr( $form_id . '-support-actual' ); ?>"><?php esc_html_e( 'Actual behavior', 'sustainable-catalyst-engagement-intake' ); ?></label><textarea id="<?php echo esc_attr( $form_id . '-support-actual' ); ?>" name="support_actual_behavior" rows="4" maxlength="12000"></textarea></div></div>
					</div>

					<div class="sc-ei-conditional" data-show-for="consulting,platform_technical,workshop_training,monthly_advisory,institutional_partnership">
						<div class="sc-ei-field-grid">
							<div class="sc-ei-field">
								<label for="<?php echo esc_attr( $form_id . '-service' ); ?>"><?php esc_html_e( 'Requested service or engagement', 'sustainable-catalyst-engagement-intake' ); ?> <span aria-hidden="true">*</span></label>
								<select id="<?php echo esc_attr( $form_id . '-service' ); ?>" name="service_interest" data-required-when-visible>
									<option value=""><?php esc_html_e( 'Choose the closest match', 'sustainable-catalyst-engagement-intake' ); ?></option>
									<?php foreach ( SC_EI_Form_Schema::service_interests() as $key => $label ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="sc-ei-field">
								<label for="<?php echo esc_attr( $form_id . '-budget' ); ?>"><?php esc_html_e( 'Available budget range', 'sustainable-catalyst-engagement-intake' ); ?> <span aria-hidden="true">*</span></label>
								<select id="<?php echo esc_attr( $form_id . '-budget' ); ?>" name="budget_range" data-required-when-visible>
									<option value=""><?php esc_html_e( 'Choose a range', 'sustainable-catalyst-engagement-intake' ); ?></option>
									<?php foreach ( SC_EI_Form_Schema::budget_ranges() as $key => $label ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						</div>

						<div class="sc-ei-field">
							<label for="<?php echo esc_attr( $form_id . '-project' ); ?>"><?php esc_html_e( 'Current project, system, or problem', 'sustainable-catalyst-engagement-intake' ); ?> <span aria-hidden="true">*</span></label>
							<textarea id="<?php echo esc_attr( $form_id . '-project' ); ?>" name="project_summary" rows="6" maxlength="12000" data-required-when-visible></textarea>
						</div>

						<div class="sc-ei-field">
							<label for="<?php echo esc_attr( $form_id . '-outcome' ); ?>"><?php esc_html_e( 'Desired outcome or decision', 'sustainable-catalyst-engagement-intake' ); ?> <span aria-hidden="true">*</span></label>
							<textarea id="<?php echo esc_attr( $form_id . '-outcome' ); ?>" name="desired_outcome" rows="5" maxlength="12000" data-required-when-visible></textarea>
						</div>

						<div class="sc-ei-field">
							<label for="<?php echo esc_attr( $form_id . '-materials' ); ?>"><?php esc_html_e( 'Current materials, systems, or evidence', 'sustainable-catalyst-engagement-intake' ); ?></label>
							<textarea id="<?php echo esc_attr( $form_id . '-materials' ); ?>" name="current_materials" rows="4" maxlength="12000"></textarea>
							<p class="sc-ei-help"><?php esc_html_e( 'Describe the materials here, then use the protected document section below for authorized supporting files.', 'sustainable-catalyst-engagement-intake' ); ?></p>
						</div>

						<div class="sc-ei-field">
							<label for="<?php echo esc_attr( $form_id . '-stakeholders' ); ?>"><?php esc_html_e( 'Key stakeholders or decision-makers', 'sustainable-catalyst-engagement-intake' ); ?></label>
							<textarea id="<?php echo esc_attr( $form_id . '-stakeholders' ); ?>" name="stakeholders" rows="3" maxlength="12000"></textarea>
						</div>

						<div class="sc-ei-field-grid">
							<div class="sc-ei-field">
								<label for="<?php echo esc_attr( $form_id . '-start-date' ); ?>"><?php esc_html_e( 'Desired start date', 'sustainable-catalyst-engagement-intake' ); ?></label>
								<input id="<?php echo esc_attr( $form_id . '-start-date' ); ?>" type="date" name="desired_start_date">
							</div>

							<div class="sc-ei-field">
								<label for="<?php echo esc_attr( $form_id . '-deadline' ); ?>"><?php esc_html_e( 'Final deadline or decision date', 'sustainable-catalyst-engagement-intake' ); ?></label>
								<input id="<?php echo esc_attr( $form_id . '-deadline' ); ?>" type="date" name="deadline_date">
							</div>
						</div>
					</div>

					<div class="sc-ei-conditional" data-show-for="speaking_media">
						<div class="sc-ei-field-grid">
							<div class="sc-ei-field">
								<label for="<?php echo esc_attr( $form_id . '-event-name' ); ?>"><?php esc_html_e( 'Event, publication, or program', 'sustainable-catalyst-engagement-intake' ); ?></label>
								<input id="<?php echo esc_attr( $form_id . '-event-name' ); ?>" type="text" name="event_name" maxlength="191">
							</div>

							<div class="sc-ei-field">
								<label for="<?php echo esc_attr( $form_id . '-event-date' ); ?>"><?php esc_html_e( 'Event or publication date', 'sustainable-catalyst-engagement-intake' ); ?></label>
								<input id="<?php echo esc_attr( $form_id . '-event-date' ); ?>" type="date" name="event_date">
							</div>

							<div class="sc-ei-field">
								<label for="<?php echo esc_attr( $form_id . '-event-format' ); ?>"><?php esc_html_e( 'Format', 'sustainable-catalyst-engagement-intake' ); ?></label>
								<input id="<?php echo esc_attr( $form_id . '-event-format' ); ?>" type="text" name="event_format" maxlength="120" placeholder="<?php esc_attr_e( 'Interview, panel, podcast, keynote, article…', 'sustainable-catalyst-engagement-intake' ); ?>">
							</div>

							<div class="sc-ei-field">
								<label for="<?php echo esc_attr( $form_id . '-audience' ); ?>"><?php esc_html_e( 'Audience', 'sustainable-catalyst-engagement-intake' ); ?></label>
								<input id="<?php echo esc_attr( $form_id . '-audience' ); ?>" type="text" name="audience" maxlength="191">
							</div>
						</div>
					</div>

					<section class="sc-ei-teams-request" aria-labelledby="<?php echo esc_attr( $form_id . '-teams-heading' ); ?>">
						<p class="sc-ei-section-kicker"><?php esc_html_e( 'Microsoft Teams Scheduling', 'sustainable-catalyst-engagement-intake' ); ?></p>
						<h3 id="<?php echo esc_attr( $form_id . '-teams-heading' ); ?>"><?php esc_html_e( 'Request or prepare for a Teams conversation', 'sustainable-catalyst-engagement-intake' ); ?></h3>
						<p><?php esc_html_e( 'Submitting availability does not book a meeting. Sustainable Catalyst reviews the inquiry first and sends a Teams invitation only when a live conversation is approved.', 'sustainable-catalyst-engagement-intake' ); ?></p>

						<div class="sc-ei-field">
							<label for="<?php echo esc_attr( $form_id . '-meeting-request' ); ?>"><?php esc_html_e( 'Would you like to request a Microsoft Teams meeting?', 'sustainable-catalyst-engagement-intake' ); ?> <span aria-hidden="true">*</span></label>
							<select id="<?php echo esc_attr( $form_id . '-meeting-request' ); ?>" name="meeting_request" required data-sc-ei-meeting-request>
								<?php foreach ( SC_EI_Form_Schema::meeting_requests() as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( 'no', $key ); ?>><?php echo esc_html( $label ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="sc-ei-controller-conditional sc-ei-teams-details" data-meeting-request-show="yes,unsure" hidden>
							<div class="sc-ei-field-grid">
								<div class="sc-ei-field">
									<label for="<?php echo esc_attr( $form_id . '-timezone' ); ?>"><?php esc_html_e( 'Time zone', 'sustainable-catalyst-engagement-intake' ); ?> <span aria-hidden="true">*</span></label>
									<input id="<?php echo esc_attr( $form_id . '-timezone' ); ?>" type="text" name="timezone" maxlength="120" list="<?php echo esc_attr( $form_id . '-timezones' ); ?>" placeholder="America/Chicago" data-sc-ei-timezone data-required-when-visible>
									<datalist id="<?php echo esc_attr( $form_id . '-timezones' ); ?>">
										<?php foreach ( SC_EI_Teams::timezone_identifiers() as $timezone_id ) : ?>
											<option value="<?php echo esc_attr( $timezone_id ); ?>"></option>
										<?php endforeach; ?>
									</datalist>
									<p class="sc-ei-help"><?php esc_html_e( 'Your browser will suggest an IANA time zone. You can change it.', 'sustainable-catalyst-engagement-intake' ); ?></p>
								</div>

								<div class="sc-ei-field">
									<label for="<?php echo esc_attr( $form_id . '-duration' ); ?>"><?php esc_html_e( 'Preferred meeting duration', 'sustainable-catalyst-engagement-intake' ); ?></label>
									<select id="<?php echo esc_attr( $form_id . '-duration' ); ?>" name="preferred_duration">
										<?php foreach ( SC_EI_Form_Schema::duration_options() as $key => $label ) : ?>
											<option value="<?php echo esc_attr( $key ); ?>" <?php selected( (string) $default_teams_duration, (string) $key ); ?>><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
								</div>

								<div class="sc-ei-field">
									<label for="<?php echo esc_attr( $form_id . '-city' ); ?>"><?php esc_html_e( 'City', 'sustainable-catalyst-engagement-intake' ); ?></label>
									<input id="<?php echo esc_attr( $form_id . '-city' ); ?>" type="text" name="city" maxlength="120" autocomplete="address-level2">
								</div>

								<div class="sc-ei-field">
									<label for="<?php echo esc_attr( $form_id . '-country' ); ?>"><?php esc_html_e( 'Country', 'sustainable-catalyst-engagement-intake' ); ?></label>
									<input id="<?php echo esc_attr( $form_id . '-country' ); ?>" type="text" name="country" maxlength="120" autocomplete="country-name">
								</div>
							</div>

							<fieldset class="sc-ei-checkbox-group">
								<legend><?php esc_html_e( 'Preferred weekdays', 'sustainable-catalyst-engagement-intake' ); ?></legend>
								<div>
									<?php foreach ( SC_EI_Form_Schema::weekdays() as $key => $label ) : ?>
										<label>
											<input type="checkbox" name="preferred_weekdays[]" value="<?php echo esc_attr( $key ); ?>">
											<span><?php echo esc_html( $label ); ?></span>
										</label>
									<?php endforeach; ?>
								</div>
							</fieldset>

							<div class="sc-ei-field">
								<label for="<?php echo esc_attr( $form_id . '-time-windows' ); ?>"><?php esc_html_e( 'Preferred dates and time windows', 'sustainable-catalyst-engagement-intake' ); ?></label>
								<textarea id="<?php echo esc_attr( $form_id . '-time-windows' ); ?>" name="preferred_time_windows" rows="4" maxlength="12000" placeholder="<?php esc_attr_e( 'Example: July 20–24, 9:00 a.m.–1:00 p.m. America/Chicago', 'sustainable-catalyst-engagement-intake' ); ?>"></textarea>
							</div>

							<div class="sc-ei-field-grid">
								<div class="sc-ei-field">
									<label for="<?php echo esc_attr( $form_id . '-participant-count' ); ?>"><?php esc_html_e( 'Expected participant count', 'sustainable-catalyst-engagement-intake' ); ?></label>
									<input id="<?php echo esc_attr( $form_id . '-participant-count' ); ?>" type="number" name="participant_count" min="1" max="50" value="1">
								</div>

								<div class="sc-ei-field">
									<label for="<?php echo esc_attr( $form_id . '-participant-emails' ); ?>"><?php esc_html_e( 'Additional participant emails', 'sustainable-catalyst-engagement-intake' ); ?></label>
									<textarea id="<?php echo esc_attr( $form_id . '-participant-emails' ); ?>" name="participant_emails" rows="3" placeholder="<?php esc_attr_e( 'One email per line', 'sustainable-catalyst-engagement-intake' ); ?>"></textarea>
								</div>
							</div>

							<div class="sc-ei-field">
								<label for="<?php echo esc_attr( $form_id . '-accessibility' ); ?>"><?php esc_html_e( 'Accessibility or accommodation needs', 'sustainable-catalyst-engagement-intake' ); ?></label>
								<textarea id="<?php echo esc_attr( $form_id . '-accessibility' ); ?>" name="accessibility_needs" rows="3" maxlength="12000"></textarea>
								<p class="sc-ei-help"><?php esc_html_e( 'Share only what is needed to prepare the meeting. This field is private and should not contain medical records.', 'sustainable-catalyst-engagement-intake' ); ?></p>
							</div>

							<div class="sc-ei-field">
								<label for="<?php echo esc_attr( $form_id . '-scheduling-notes' ); ?>"><?php esc_html_e( 'Additional scheduling notes', 'sustainable-catalyst-engagement-intake' ); ?></label>
								<textarea id="<?php echo esc_attr( $form_id . '-scheduling-notes' ); ?>" name="scheduling_notes" rows="3" maxlength="12000"></textarea>
							</div>

							<label class="sc-ei-check">
								<input type="checkbox" name="calendar_invite_consent" value="1" data-required-when-visible>
								<span><?php esc_html_e( 'Sustainable Catalyst may send a Microsoft Teams calendar invitation to me and the participant emails I supplied if the meeting is approved.', 'sustainable-catalyst-engagement-intake' ); ?> <b aria-hidden="true">*</b></span>
							</label>
						</div>
					</section>

					<div class="sc-ei-field">
						<label for="<?php echo esc_attr( $form_id . '-links' ); ?>"><?php esc_html_e( 'Relevant public links', 'sustainable-catalyst-engagement-intake' ); ?></label>
						<textarea id="<?php echo esc_attr( $form_id . '-links' ); ?>" name="relevant_links" rows="4" placeholder="<?php esc_attr_e( 'One URL per line', 'sustainable-catalyst-engagement-intake' ); ?>"></textarea>
						<p class="sc-ei-help"><?php esc_html_e( 'Do not include private download links, credentials, regulated records, or material you are not authorized to share.', 'sustainable-catalyst-engagement-intake' ); ?></p>
					</div>

					<?php echo self::render_document_fields( $form_id, $upload_max_files, $upload_max_mb, $upload_total_max_bytes, $upload_extensions, false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

					<div class="sc-ei-field">
						<label for="<?php echo esc_attr( $form_id . '-referral' ); ?>"><?php esc_html_e( 'How did you find Sustainable Catalyst?', 'sustainable-catalyst-engagement-intake' ); ?></label>
						<select id="<?php echo esc_attr( $form_id . '-referral' ); ?>" name="referral_source">
							<option value=""><?php esc_html_e( 'Optional', 'sustainable-catalyst-engagement-intake' ); ?></option>
							<?php foreach ( SC_EI_Form_Schema::referral_sources() as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="sc-ei-actions">
						<button type="button" class="sc-ei-button" data-sc-ei-back><?php esc_html_e( 'Back', 'sustainable-catalyst-engagement-intake' ); ?></button>
						<button type="button" class="sc-ei-button sc-ei-button--primary" data-sc-ei-next><?php esc_html_e( 'Review Inquiry', 'sustainable-catalyst-engagement-intake' ); ?></button>
					</div>
				</fieldset>

				<fieldset class="sc-ei-step" data-sc-ei-step="3" hidden>
					<legend><?php esc_html_e( 'Review and authorize', 'sustainable-catalyst-engagement-intake' ); ?></legend>

					<div class="sc-ei-review" data-sc-ei-review>
						<p><?php esc_html_e( 'Review the information before creating the private inquiry record.', 'sustainable-catalyst-engagement-intake' ); ?></p>
						<dl data-sc-ei-review-list></dl>
					</div>

					<div class="sc-ei-privacy-box">
						<strong><?php esc_html_e( 'Privacy and document boundary', 'sustainable-catalyst-engagement-intake' ); ?></strong>
						<p>
							<?php esc_html_e( 'Uploaded documents are structurally validated and stored in protected quarantine. Do not submit passwords, payment-card data, regulated health records, highly sensitive personal information, export-controlled material, or files you are not authorized to share.', 'sustainable-catalyst-engagement-intake' ); ?>
						</p>
					</div>

					<label class="sc-ei-check">
						<input type="checkbox" name="privacy_consent" value="1" required>
						<span><?php esc_html_e( 'I authorize Sustainable Catalyst to process this information for the purpose of reviewing and responding to the inquiry.', 'sustainable-catalyst-engagement-intake' ); ?> <b aria-hidden="true">*</b></span>
					</label>

					<label class="sc-ei-check">
						<input type="checkbox" name="authorization_consent" value="1" required>
						<span><?php esc_html_e( 'I am authorized to share the information, links, and documents included in this submission.', 'sustainable-catalyst-engagement-intake' ); ?> <b aria-hidden="true">*</b></span>
					</label>

					<label class="sc-ei-check">
						<input type="checkbox" name="follow_up_consent" value="1">
						<span><?php esc_html_e( 'Sustainable Catalyst may contact me about this inquiry and closely related next steps.', 'sustainable-catalyst-engagement-intake' ); ?></span>
					</label>

					<div class="sc-ei-actions">
						<button type="button" class="sc-ei-button" data-sc-ei-back><?php esc_html_e( 'Back', 'sustainable-catalyst-engagement-intake' ); ?></button>
						<button type="submit" class="sc-ei-button sc-ei-button--primary" data-sc-ei-submit>
							<span><?php esc_html_e( 'Submit Private Inquiry', 'sustainable-catalyst-engagement-intake' ); ?></span>
						</button>
					</div>
				</fieldset>

				<div class="sc-ei-success" data-sc-ei-success role="status" aria-live="polite" hidden>
					<p class="sc-ei-success__eyebrow"><?php esc_html_e( 'Inquiry received', 'sustainable-catalyst-engagement-intake' ); ?></p>
					<h3><?php esc_html_e( 'Your private inquiry record has been created. A Teams meeting request remains pending until it is reviewed and approved.', 'sustainable-catalyst-engagement-intake' ); ?></h3>
					<p><?php esc_html_e( 'Save this reference for future communication:', 'sustainable-catalyst-engagement-intake' ); ?></p>
					<strong data-sc-ei-reference></strong>
					<p data-sc-ei-attachment-summary hidden></p>
					<div class="sc-ei-success__warnings" data-sc-ei-attachment-warnings hidden></div>
					<p><?php esc_html_e( 'Submission does not create an engagement, confidentiality agreement, acceptance, or obligation to respond.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				</div>
			</form>
		</div>
		<?php
		do_action( 'sc_ei_form_rendered', $mode, $source, $entry_cta );
		return (string) ob_get_clean();
	}

	private static function render_feedback( string $result, string $error, string $reference, int $file_count = 0, bool $file_warning = false ): string {
		if ( 'success' === $result && $reference ) {
			$document_message = '';
			if ( $file_count > 0 ) {
				$document_message = ' ' . sprintf(
					_n( '%d document was placed in protected quarantine.', '%d documents were placed in protected quarantine.', $file_count, 'sustainable-catalyst-engagement-intake' ),
					$file_count
				);
			}
			if ( $file_warning ) {
				$document_message .= ' ' . __( 'At least one selected document was not accepted. Keep the inquiry reference and use it when providing a corrected document through an approved follow-up route.', 'sustainable-catalyst-engagement-intake' );
			}

			return sprintf(
				'<div class="sc-ei-feedback sc-ei-feedback--success" role="status"><strong>%1$s</strong><span>%2$s</span></div>',
				esc_html__( 'Inquiry received.', 'sustainable-catalyst-engagement-intake' ),
				esc_html( sprintf( __( 'Reference: %1$s.%2$s', 'sustainable-catalyst-engagement-intake' ), $reference, $document_message ) )
			);
		}

		if ( 'error' === $result ) {
			return sprintf(
				'<div class="sc-ei-feedback sc-ei-feedback--error" role="alert"><strong>%1$s</strong><span>%2$s</span></div>',
				esc_html__( 'The inquiry was not submitted.', 'sustainable-catalyst-engagement-intake' ),
				esc_html( self::error_message( $error ) )
			);
		}

		return '';
	}

	private static function error_message( string $code ): string {
		$messages = array(
			'security_check'       => __( 'The form security check expired. Reload the page and try again.', 'sustainable-catalyst-engagement-intake' ),
			'form_expired'         => __( 'The form session expired. Reload the page and try again.', 'sustainable-catalyst-engagement-intake' ),
			'too_fast'             => __( 'The form was submitted too quickly. Review the information and try again.', 'sustainable-catalyst-engagement-intake' ),
			'rate_limited'         => __( 'Too many submissions were sent in a short period. Try again later.', 'sustainable-catalyst-engagement-intake' ),
			'duplicate_submission' => __( 'This inquiry appears to have already been submitted.', 'sustainable-catalyst-engagement-intake' ),
			'submission_in_progress' => __( 'This inquiry is already being processed. Keep the page open and check for the confirmation before submitting again.', 'sustainable-catalyst-engagement-intake' ),
			'attribution_invalid'  => __( 'The form attribution check failed. Reload the page and try again.', 'sustainable-catalyst-engagement-intake' ),
			'request_too_large'    => __( 'The submission exceeded the server request-size limit. Reduce the combined document size and submit again.', 'sustainable-catalyst-engagement-intake' ),
			'upload_truncated'     => __( 'The server received fewer documents than the browser selected. Reduce the file count or size and submit again.', 'sustainable-catalyst-engagement-intake' ),
			'uploads_disabled'     => __( 'File uploads are disabled on the server. Remove the documents or contact the site administrator.', 'sustainable-catalyst-engagement-intake' ),
			'upload_temp_unavailable' => __( 'The server upload-temporary directory is unavailable. Remove the documents or contact the site administrator.', 'sustainable-catalyst-engagement-intake' ),
			'storage_error'        => __( 'The inquiry could not be stored. Try again or use another contact route.', 'sustainable-catalyst-engagement-intake' ),
		);

		return $messages[ $code ] ?? __( 'Review the required fields and try again.', 'sustainable-catalyst-engagement-intake' );
	}

	private static function route_kicker( string $type ): string {
		$map = array(
			'product_support'           => __( 'Support', 'sustainable-catalyst-engagement-intake' ),
			'general'                   => __( 'Question', 'sustainable-catalyst-engagement-intake' ),
			'consulting'                => __( 'Advisory', 'sustainable-catalyst-engagement-intake' ),
			'research_collaboration'    => __( 'Research', 'sustainable-catalyst-engagement-intake' ),
			'platform_technical'        => __( 'Platform', 'sustainable-catalyst-engagement-intake' ),
			'workshop_training'         => __( 'Learning', 'sustainable-catalyst-engagement-intake' ),
			'monthly_advisory'          => __( 'Ongoing', 'sustainable-catalyst-engagement-intake' ),
			'speaking_media'            => __( 'Media', 'sustainable-catalyst-engagement-intake' ),
			'open_source'               => __( 'Open source', 'sustainable-catalyst-engagement-intake' ),
			'institutional_partnership' => __( 'Partnership', 'sustainable-catalyst-engagement-intake' ),
			'other'                     => __( 'Other', 'sustainable-catalyst-engagement-intake' ),
		);
		return $map[ $type ] ?? __( 'Inquiry', 'sustainable-catalyst-engagement-intake' );
	}

	private static function route_description( string $type ): string {
		$map = array(
			'product_support'           => __( 'Product-specific troubleshooting, access, installation, runtime, documentation, or data issue.', 'sustainable-catalyst-engagement-intake' ),
			'general'                   => __( 'A concise question or request that does not require a project scope.', 'sustainable-catalyst-engagement-intake' ),
			'consulting'                => __( 'A defined advisory, diagnostic, strategy, architecture, or implementation need.', 'sustainable-catalyst-engagement-intake' ),
			'research_collaboration'    => __( 'Research, publication, evidence, academic, or public-interest collaboration.', 'sustainable-catalyst-engagement-intake' ),
			'platform_technical'        => __( 'Knowledge systems, analytical workflows, platform development, or technical implementation.', 'sustainable-catalyst-engagement-intake' ),
			'workshop_training'         => __( 'Private training, facilitated working sessions, workshops, or cohort learning.', 'sustainable-catalyst-engagement-intake' ),
			'monthly_advisory'          => __( 'Recurring research, strategy, documentation, systems, or implementation support.', 'sustainable-catalyst-engagement-intake' ),
			'speaking_media'            => __( 'Interview, podcast, panel, keynote, media, or public conversation.', 'sustainable-catalyst-engagement-intake' ),
			'open_source'               => __( 'Repository, documentation, implementation, or open-source project question.', 'sustainable-catalyst-engagement-intake' ),
			'institutional_partnership' => __( 'A larger relationship across research, platforms, programs, or institutional development.', 'sustainable-catalyst-engagement-intake' ),
			'other'                     => __( 'Use this when no other path fits clearly.', 'sustainable-catalyst-engagement-intake' ),
		);
		return $map[ $type ] ?? '';
	}

	private static function render_document_fields( string $form_id, int $max_files, int $max_mb, int $max_total_bytes, array $extensions, bool $compact ): string {
		$accept = implode( ',', array_map( static fn( string $extension ): string => '.' . $extension, $extensions ) );
		$allowed_label = implode( ', ', array_map( 'strtoupper', $extensions ) );

		ob_start();
		?>
		<section class="sc-ei-document-intake<?php echo $compact ? ' sc-ei-document-intake--compact' : ''; ?>" data-sc-ei-document-section>
			<p class="sc-ei-section-kicker"><?php esc_html_e( 'Protected Document Intake', 'sustainable-catalyst-engagement-intake' ); ?></p>
			<h3><?php echo esc_html( $compact ? __( 'Add supporting documents', 'sustainable-catalyst-engagement-intake' ) : __( 'Documents and supporting materials', 'sustainable-catalyst-engagement-intake' ) ); ?></h3>
			<p>
				<?php
				echo esc_html(
					sprintf(
						/* translators: 1: maximum files, 2: maximum megabytes per file, 3: extensions, 4: combined size */
						__( 'Optional. Up to %1$d files, %2$d MB each, with a combined limit of %4$s. Allowed: %3$s. Files are renamed internally, validated, and stored in protected quarantine rather than the public Media Library.', 'sustainable-catalyst-engagement-intake' ),
						$max_files,
						$max_mb,
						$allowed_label,
						size_format( $max_total_bytes, 1 )
					)
				);
				?>
			</p>

			<div class="sc-ei-field">
				<label for="<?php echo esc_attr( $form_id . '-documents' ); ?>"><?php esc_html_e( 'Select documents', 'sustainable-catalyst-engagement-intake' ); ?></label>
				<input
					id="<?php echo esc_attr( $form_id . '-documents' ); ?>"
					type="file"
					name="documents[]"
					multiple
					accept="<?php echo esc_attr( $accept ); ?>"
					data-sc-ei-files
					data-max-files="<?php echo esc_attr( $max_files ); ?>"
					data-max-bytes="<?php echo esc_attr( $max_mb * MB_IN_BYTES ); ?>"
					data-max-total-bytes="<?php echo esc_attr( $max_total_bytes ); ?>"
					data-allowed-extensions="<?php echo esc_attr( implode( ',', $extensions ) ); ?>"
				>
				<div class="sc-ei-file-summary" data-sc-ei-file-summary aria-live="polite"></div>
				<div class="sc-ei-upload-status" data-sc-ei-upload-status role="status" aria-live="polite" hidden></div>
			</div>

			<div class="sc-ei-field-grid">
				<div class="sc-ei-field">
					<label for="<?php echo esc_attr( $form_id . '-document-category' ); ?>"><?php esc_html_e( 'Document category', 'sustainable-catalyst-engagement-intake' ); ?></label>
					<select id="<?php echo esc_attr( $form_id . '-document-category' ); ?>" name="document_category">
						<?php foreach ( SC_EI_Form_Schema::document_categories() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( 'other', $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="sc-ei-field">
					<label for="<?php echo esc_attr( $form_id . '-document-confidentiality' ); ?>"><?php esc_html_e( 'Confidentiality classification', 'sustainable-catalyst-engagement-intake' ); ?></label>
					<select id="<?php echo esc_attr( $form_id . '-document-confidentiality' ); ?>" name="document_confidentiality">
						<?php foreach ( SC_EI_Form_Schema::document_confidentiality_options() as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( 'non_confidential', $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>

			<div class="sc-ei-field">
				<label for="<?php echo esc_attr( $form_id . '-document-notes' ); ?>"><?php esc_html_e( 'Document notes', 'sustainable-catalyst-engagement-intake' ); ?></label>
				<textarea id="<?php echo esc_attr( $form_id . '-document-notes' ); ?>" name="document_notes" rows="3" maxlength="12000" placeholder="<?php esc_attr_e( 'Explain what the files contain and how they relate to the inquiry.', 'sustainable-catalyst-engagement-intake' ); ?>"></textarea>
			</div>

			<div class="sc-ei-document-warning">
				<strong><?php esc_html_e( 'Do not upload', 'sustainable-catalyst-engagement-intake' ); ?></strong>
				<p><?php esc_html_e( 'Passwords, payment-card data, regulated health records, government identification, highly sensitive personal data, export-controlled material, executable code, ZIP archives, macro-enabled files, encrypted files, or documents you are not authorized to share.', 'sustainable-catalyst-engagement-intake' ); ?></p>
			</div>

			<label class="sc-ei-check">
				<input type="checkbox" name="document_upload_consent" value="1" data-sc-ei-document-consent>
				<span><?php esc_html_e( 'I am authorized to upload the selected documents and understand that accepted files will be stored in protected quarantine for authorized review and retention-controlled deletion.', 'sustainable-catalyst-engagement-intake' ); ?> <b aria-hidden="true" data-sc-ei-document-required hidden>*</b></span>
			</label>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	private static function protect_dynamic_form_page(): void {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		if ( ! headers_sent() ) {
			nocache_headers();
			SC_EI_Upload_Environment::send_no_cache_headers();
		}
	}

	private static function enqueue_assets(): void {
		if ( self::$assets_enqueued ) {
			return;
		}
		self::$assets_enqueued = true;
		$settings = wp_parse_args( get_option( 'sc_ei_settings', array() ), SC_EI_Admin::default_settings() );

		wp_enqueue_style(
			'sc-ei-public',
			SC_EI_URL . 'assets/css/public.css',
			array(),
			SC_EI_VERSION
		);

		wp_enqueue_script(
			'sc-ei-public',
			SC_EI_URL . 'assets/js/public.js',
			array(),
			SC_EI_VERSION,
			true
		);

		wp_localize_script(
			'sc-ei-public',
			'scEiPublic',
			array(
				'restUrl' => esc_url_raw( rest_url( 'sc-engagement-intake/v1/submit' ) ),
				'routeGuidance'  => SC_EI_Conversion::route_guidance(),
				'pricingGuidance'=> SC_EI_Conversion::compact_guidance(),
				'uploadConfig'   => array_merge(
					SC_EI_Upload_Environment::effective_limits( $settings ),
					array(
						'allowedExtensions' => array_values( (array) ( $settings['allowed_upload_extensions'] ?? array() ) ),
						'timeoutMilliseconds'=> 180000,
					)
				),
				'i18n'    => array(
					'validationHeading' => __( 'Review these fields:', 'sustainable-catalyst-engagement-intake' ),
					'submitting'        => __( 'Submitting…', 'sustainable-catalyst-engagement-intake' ),
					'submit'            => __( 'Submit Private Inquiry', 'sustainable-catalyst-engagement-intake' ),
					'genericError'      => __( 'The inquiry could not be submitted. Review the fields or try again.', 'sustainable-catalyst-engagement-intake' ),
					'compactSubmit'      => __( 'Submit Engagement Inquiry', 'sustainable-catalyst-engagement-intake' ),
					'fileCountError'     => __( 'Too many documents are selected.', 'sustainable-catalyst-engagement-intake' ),
					'fileSizeError'      => __( 'One or more documents exceed the per-file size limit.', 'sustainable-catalyst-engagement-intake' ),
					'fileTypeError'      => __( 'One or more selected document types are not allowed.', 'sustainable-catalyst-engagement-intake' ),
					'fileTotalError'     => __( 'The combined document size exceeds the safe request limit.', 'sustainable-catalyst-engagement-intake' ),
					'documentConsent'    => __( 'Confirm the protected document upload authorization.', 'sustainable-catalyst-engagement-intake' ),
					'documentsQuarantined'=> __( 'document(s) placed in protected quarantine', 'sustainable-catalyst-engagement-intake' ),
					'uploadingSecurely'    => __( 'Uploading and verifying the inquiry securely. Keep this page open.', 'sustainable-catalyst-engagement-intake' ),
					'uploadTimeout'        => __( 'The secure upload took too long to complete. The server may still be processing it; check for a confirmation before submitting again.', 'sustainable-catalyst-engagement-intake' ),
					'networkOffline'       => __( 'The browser is offline. Reconnect before submitting the inquiry.', 'sustainable-catalyst-engagement-intake' ),
					'draftRestored'        => __( 'Your unsent form details were restored in this browser tab.', 'sustainable-catalyst-engagement-intake' ),
				),
			)
		);
	}

	private static function current_url(): string {
		$scheme = is_ssl() ? 'https://' : 'http://';
		$host   = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : wp_parse_url( home_url(), PHP_URL_HOST );
		$uri    = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/contact/';
		$url    = $scheme . $host . $uri;
		return remove_query_arg( array( 'sc_ei_result', 'sc_ei_error', 'sc_ei_reference', 'sc_ei_files', 'sc_ei_file_warning' ), esc_url_raw( $url ) );
	}
}
