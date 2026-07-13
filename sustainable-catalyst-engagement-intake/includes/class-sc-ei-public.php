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
	}

	public static function contact_hub( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'title'        => __( 'Contact Sustainable Catalyst', 'sustainable-catalyst-engagement-intake' ),
				'intro'        => __( 'Choose the inquiry path that best matches the question, project, collaboration, or engagement.', 'sustainable-catalyst-engagement-intake' ),
				'default_type' => 'general',
			),
			$atts,
			'sc_contact_hub'
		);

		$types = SC_EI_Form_Schema::all_public_types();
		return self::render(
			'hub',
			$types,
			sanitize_text_field( $atts['title'] ),
			sanitize_textarea_field( $atts['intro'] ),
			sanitize_key( $atts['default_type'] )
		);
	}

	public static function contact_form( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'mode'         => 'general',
				'title'        => __( 'Send a General Inquiry', 'sustainable-catalyst-engagement-intake' ),
				'intro'        => __( 'Use this form for general questions, research collaboration, media, speaking, open-source work, or another non-consulting inquiry.', 'sustainable-catalyst-engagement-intake' ),
				'default_type' => 'general',
			),
			$atts,
			'sc_contact_form'
		);

		return self::render(
			'general',
			SC_EI_Form_Schema::general_types(),
			sanitize_text_field( $atts['title'] ),
			sanitize_textarea_field( $atts['intro'] ),
			sanitize_key( $atts['default_type'] )
		);
	}

	public static function engagement_form( array $atts = array() ): string {
		$atts = shortcode_atts(
			array(
				'mode'         => 'consulting',
				'title'        => __( 'Discuss an Engagement', 'sustainable-catalyst-engagement-intake' ),
				'intro'        => __( 'Use this private intake for advisory, diagnostics, strategy sprints, platform work, workshops, monthly advisory, or an institutional partnership.', 'sustainable-catalyst-engagement-intake' ),
				'default_type' => 'consulting',
			),
			$atts,
			'sc_engagement_inquiry'
		);

		return self::render(
			'consulting',
			SC_EI_Form_Schema::engagement_types(),
			sanitize_text_field( $atts['title'] ),
			sanitize_textarea_field( $atts['intro'] ),
			sanitize_key( $atts['default_type'] )
		);
	}

	private static function render( string $mode, array $types, string $title, string $intro, string $default_type ): string {
		self::protect_dynamic_form_page();
		self::enqueue_assets();

		self::$form_count++;
		$form_id = 'sc-ei-form-' . self::$form_count;
		if ( ! array_key_exists( $default_type, $types ) ) {
			$default_type = (string) array_key_first( $types );
		}

		$started_at = time();
		$signature  = SC_EI_Form_Handler::timing_signature( $started_at, $form_id );
		$result     = isset( $_GET['sc_ei_result'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_result'] ) ) : '';
		$error      = isset( $_GET['sc_ei_error'] ) ? sanitize_key( wp_unslash( $_GET['sc_ei_error'] ) ) : '';
		$reference  = isset( $_GET['sc_ei_reference'] ) ? sanitize_text_field( wp_unslash( $_GET['sc_ei_reference'] ) ) : '';

		ob_start();
		?>
		<div class="sc-ei-public sc-ei-public--<?php echo esc_attr( $mode ); ?>" data-sc-ei-hub>
			<div class="sc-ei-public__header">
				<p class="sc-ei-public__eyebrow"><?php esc_html_e( 'Private Contact and Engagement Intake', 'sustainable-catalyst-engagement-intake' ); ?></p>
				<h2><?php echo esc_html( $title ); ?></h2>
				<p><?php echo esc_html( $intro ); ?></p>
			</div>

			<?php echo self::render_feedback( $result, $error, $reference ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<?php if ( 'hub' === $mode ) : ?>
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
				action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
				data-sc-ei-form
				data-mode="<?php echo esc_attr( $mode ); ?>"
				novalidate
			>
				<input type="hidden" name="action" value="sc_ei_submit">
				<input type="hidden" name="form_mode" value="<?php echo esc_attr( $mode ); ?>">
				<input type="hidden" name="form_id" value="<?php echo esc_attr( $form_id ); ?>">
				<input type="hidden" name="form_started_at" value="<?php echo esc_attr( $started_at ); ?>">
				<input type="hidden" name="form_signature" value="<?php echo esc_attr( $signature ); ?>">
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( self::current_url() ); ?>">
				<input type="hidden" name="source_url" value="<?php echo esc_url( self::current_url() ); ?>">
				<?php wp_nonce_field( SC_EI_Form_Handler::nonce_action(), 'sc_ei_nonce' ); ?>

				<noscript>
					<style>
						#<?php echo esc_attr( $form_id ); ?> .sc-ei-step[hidden],
						#<?php echo esc_attr( $form_id ); ?> .sc-ei-conditional[hidden] {
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
							<p class="sc-ei-help"><?php esc_html_e( 'Secure document uploads arrive in v0.3.0. For now, describe the materials and add non-confidential links below.', 'sustainable-catalyst-engagement-intake' ); ?></p>
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

					<div class="sc-ei-field">
						<label for="<?php echo esc_attr( $form_id . '-links' ); ?>"><?php esc_html_e( 'Relevant public links', 'sustainable-catalyst-engagement-intake' ); ?></label>
						<textarea id="<?php echo esc_attr( $form_id . '-links' ); ?>" name="relevant_links" rows="4" placeholder="<?php esc_attr_e( 'One URL per line', 'sustainable-catalyst-engagement-intake' ); ?>"></textarea>
						<p class="sc-ei-help"><?php esc_html_e( 'Do not include private download links, credentials, regulated records, or material you are not authorized to share.', 'sustainable-catalyst-engagement-intake' ); ?></p>
					</div>

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
							<?php esc_html_e( 'Do not submit passwords, payment-card data, regulated health records, highly sensitive personal information, export-controlled material, or confidential documents through this v0.2.0 form. Secure document intake is introduced in v0.3.0.', 'sustainable-catalyst-engagement-intake' ); ?>
						</p>
					</div>

					<label class="sc-ei-check">
						<input type="checkbox" name="privacy_consent" value="1" required>
						<span><?php esc_html_e( 'I authorize Sustainable Catalyst to process this information for the purpose of reviewing and responding to the inquiry.', 'sustainable-catalyst-engagement-intake' ); ?> <b aria-hidden="true">*</b></span>
					</label>

					<label class="sc-ei-check">
						<input type="checkbox" name="authorization_consent" value="1" required>
						<span><?php esc_html_e( 'I am authorized to share the information and links included in this submission.', 'sustainable-catalyst-engagement-intake' ); ?> <b aria-hidden="true">*</b></span>
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
					<h3><?php esc_html_e( 'Your private inquiry record has been created.', 'sustainable-catalyst-engagement-intake' ); ?></h3>
					<p><?php esc_html_e( 'Save this reference for future communication:', 'sustainable-catalyst-engagement-intake' ); ?></p>
					<strong data-sc-ei-reference></strong>
					<p><?php esc_html_e( 'Submission does not create an engagement, confidentiality agreement, acceptance, or obligation to respond.', 'sustainable-catalyst-engagement-intake' ); ?></p>
				</div>
			</form>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	private static function render_feedback( string $result, string $error, string $reference ): string {
		if ( 'success' === $result && $reference ) {
			return sprintf(
				'<div class="sc-ei-feedback sc-ei-feedback--success" role="status"><strong>%1$s</strong><span>%2$s</span></div>',
				esc_html__( 'Inquiry received.', 'sustainable-catalyst-engagement-intake' ),
				esc_html( sprintf( __( 'Reference: %s', 'sustainable-catalyst-engagement-intake' ), $reference ) )
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
			'storage_error'        => __( 'The inquiry could not be stored. Try again or use another contact route.', 'sustainable-catalyst-engagement-intake' ),
		);

		return $messages[ $code ] ?? __( 'Review the required fields and try again.', 'sustainable-catalyst-engagement-intake' );
	}

	private static function route_kicker( string $type ): string {
		$map = array(
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

	private static function protect_dynamic_form_page(): void {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		if ( ! headers_sent() ) {
			nocache_headers();
		}
	}

	private static function enqueue_assets(): void {
		if ( self::$assets_enqueued ) {
			return;
		}
		self::$assets_enqueued = true;

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
				'i18n'    => array(
					'validationHeading' => __( 'Review these fields:', 'sustainable-catalyst-engagement-intake' ),
					'submitting'        => __( 'Submitting…', 'sustainable-catalyst-engagement-intake' ),
					'submit'            => __( 'Submit Private Inquiry', 'sustainable-catalyst-engagement-intake' ),
					'genericError'      => __( 'The inquiry could not be submitted. Review the fields or try again.', 'sustainable-catalyst-engagement-intake' ),
				),
			)
		);
	}

	private static function current_url(): string {
		$scheme = is_ssl() ? 'https://' : 'http://';
		$host   = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : wp_parse_url( home_url(), PHP_URL_HOST );
		$uri    = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/contact/';
		$url    = $scheme . $host . $uri;
		return remove_query_arg( array( 'sc_ei_result', 'sc_ei_error', 'sc_ei_reference' ), esc_url_raw( $url ) );
	}
}
