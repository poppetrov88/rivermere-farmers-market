<?php
/**
 * Vendor application form (behaviour: src/js/vendor-form.js; storage: Rivermere Core).
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$id      = $args['post_id'];
$state   = function_exists( 'rivermere_app_is_open' ) ? rivermere_app_is_open() : array( 'open' => false, 'reason' => 'plugin' );
$fee_on  = rivermere_toggle( 'apps_fee_enabled', false );
$fee     = trim( (string) rivermere_option( 'apps_fee_amount', '' ) );
$fee_tx  = trim( (string) rivermere_option( 'apps_fee_description', '' ) );
$terms   = (string) rivermere_option( 'apps_terms', '' );
$instr   = (string) rivermere_option( 'apps_instructions', '' );
$privacy = get_privacy_policy_url();
$terms_n = get_terms( array( 'taxonomy' => 'rm_product_category', 'hide_empty' => false ) );
$cats    = is_wp_error( $terms_n ) ? array() : wp_list_pluck( $terms_n, 'name' );
$cats[]  = __( 'Other', 'rivermere-market' );
?>
<section class="section section--flow apply-section" id="apply" aria-labelledby="apply-title">
	<div class="container container--narrow">
		<header class="feature-section__head">
			<h2 class="feature-section__title" id="apply-title"><?php echo esc_html( rivermere_field( 'vp_form_title', $id, __( 'Apply to Become a Vendor', 'rivermere-market' ) ) ); ?></h2>
			<?php $intro = rivermere_field( 'vp_form_intro', $id, '' ); ?>
			<?php if ( $intro ) : ?>
				<p class="feature-section__intro"><?php echo esc_html( $intro ); ?></p>
			<?php endif; ?>
			<?php if ( $instr ) : ?>
				<p class="feature-section__intro"><?php echo esc_html( $instr ); ?></p>
			<?php endif; ?>
		</header>

		<?php if ( ! $state['open'] ) : ?>
			<div class="notice" role="status">
				<p><strong><?php echo 'deadline' === $state['reason'] ? esc_html__( 'The application deadline has passed.', 'rivermere-market' ) : esc_html__( 'Vendor applications are not open right now.', 'rivermere-market' ); ?></strong> <?php esc_html_e( 'Please check back soon or get in touch with the market team.', 'rivermere-market' ); ?></p>
			</div>
		<?php else : ?>
			<div class="apply-success" data-apply-success tabindex="-1" hidden>
				<span class="apply-success__icon"><?php echo rivermere_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<h3><?php esc_html_e( 'Application received', 'rivermere-market' ); ?></h3>
				<p data-apply-success-text></p>
			</div>

			<form class="apply-form" method="post" action="" novalidate data-apply-form>
				<div class="apply-form__summary notice notice--error" tabindex="-1" data-apply-summary hidden>
					<h3 class="apply-form__summary-title" id="apply-summary-title"><?php esc_html_e( 'Please fix the following', 'rivermere-market' ); ?></h3>
					<ul data-apply-summary-list></ul>
				</div>
				<p class="apply-form__note"><?php esc_html_e( 'Fields marked * are required.', 'rivermere-market' ); ?></p>

				<fieldset class="apply-form__group">
					<legend><?php esc_html_e( 'Business information', 'rivermere-market' ); ?></legend>
					<div class="field-row">
						<?php
						rivermere_form_field( array( 'id' => 'business', 'name' => 'business_name', 'label' => __( 'Business name', 'rivermere-market' ), 'required' => true, 'autocomplete' => 'organization', 'maxlength' => 120 ) );
						rivermere_form_field( array( 'id' => 'category', 'name' => 'category', 'label' => __( 'Product category', 'rivermere-market' ), 'type' => 'select', 'options' => $cats, 'half' => true ) );
						rivermere_form_field( array( 'id' => 'location', 'name' => 'location', 'label' => __( 'City or region', 'rivermere-market' ), 'autocomplete' => 'address-level2', 'maxlength' => 120, 'half' => true ) );
						rivermere_form_field( array( 'id' => 'products', 'name' => 'products', 'label' => __( 'What products do you sell?', 'rivermere-market' ), 'type' => 'textarea', 'required' => true, 'maxlength' => 1500, 'hint' => __( 'List your main products and how they are made or sourced.', 'rivermere-market' ) ) );
						?>
					</div>
				</fieldset>

				<fieldset class="apply-form__group">
					<legend><?php esc_html_e( 'Contact information', 'rivermere-market' ); ?></legend>
					<div class="field-row">
						<?php
						rivermere_form_field( array( 'id' => 'first', 'name' => 'first_name', 'label' => __( 'First name', 'rivermere-market' ), 'required' => true, 'autocomplete' => 'given-name', 'maxlength' => 60, 'half' => true ) );
						rivermere_form_field( array( 'id' => 'last', 'name' => 'last_name', 'label' => __( 'Last name', 'rivermere-market' ), 'required' => true, 'autocomplete' => 'family-name', 'maxlength' => 60, 'half' => true ) );
						rivermere_form_field( array( 'id' => 'email', 'name' => 'email', 'label' => __( 'Email address', 'rivermere-market' ), 'type' => 'email', 'required' => true, 'autocomplete' => 'email', 'maxlength' => 190, 'half' => true ) );
						rivermere_form_field( array( 'id' => 'phone', 'name' => 'phone', 'label' => __( 'Phone number', 'rivermere-market' ), 'type' => 'tel', 'required' => true, 'autocomplete' => 'tel', 'inputmode' => 'tel', 'maxlength' => 30, 'half' => true ) );
						?>
					</div>
				</fieldset>

				<fieldset class="apply-form__group">
					<legend><?php esc_html_e( 'Online presence', 'rivermere-market' ); ?></legend>
					<div class="field-row">
						<?php
						rivermere_form_field( array( 'id' => 'website', 'name' => 'website', 'label' => __( 'Business website', 'rivermere-market' ), 'type' => 'url', 'autocomplete' => 'url', 'maxlength' => 255, 'hint' => __( 'Starting with https://', 'rivermere-market' ), 'half' => true ) );
						rivermere_form_field( array( 'id' => 'social', 'name' => 'social', 'label' => __( 'Social media profile', 'rivermere-market' ), 'type' => 'url', 'maxlength' => 255, 'hint' => __( 'A link to your Facebook, Instagram or similar page.', 'rivermere-market' ), 'half' => true ) );
						?>
					</div>
				</fieldset>

				<fieldset class="apply-form__group">
					<legend><?php esc_html_e( 'Additional details', 'rivermere-market' ); ?></legend>
					<div class="field-row">
						<?php
						rivermere_form_field( array( 'id' => 'description', 'name' => 'description', 'label' => __( 'Tell us about your business', 'rivermere-market' ), 'type' => 'textarea', 'maxlength' => 3000 ) );
						rivermere_form_field( array( 'id' => 'experience', 'name' => 'experience', 'label' => __( 'Previous market experience', 'rivermere-market' ), 'type' => 'textarea', 'rows' => 3, 'maxlength' => 1500 ) );
						rivermere_form_field( array( 'id' => 'notes', 'name' => 'notes', 'label' => __( 'Anything else we should know?', 'rivermere-market' ), 'type' => 'textarea', 'rows' => 3, 'maxlength' => 2000 ) );
						?>
					</div>
				</fieldset>

				<fieldset class="apply-form__group">
					<legend><?php esc_html_e( 'Agreements', 'rivermere-market' ); ?></legend>
					<?php if ( $terms ) : ?>
						<p class="apply-form__terms"><?php echo nl2br( esc_html( $terms ) ); ?></p>
					<?php endif; ?>
					<?php if ( $fee_on && $fee ) : ?>
						<div class="field field--check" data-field="fee_ack">
							<input class="check__input" type="checkbox" id="va-fee" name="fee_ack" value="1" required aria-describedby="va-fee-error">
							<label class="check check--light" for="va-fee">
								<span class="check__box" aria-hidden="true"><?php echo rivermere_icon( 'check', 'check__tick' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
								<span class="check__text"><?php echo esc_html( sprintf( /* translators: 1: fee, 2: description */ __( 'I understand that an application fee applies: %1$s%2$s. No payment is taken on this website.', 'rivermere-market' ), $fee, $fee_tx ? ' (' . $fee_tx . ')' : '' ) ); ?></span>
							</label>
							<p class="field__error" id="va-fee-error" hidden></p>
						</div>
					<?php endif; ?>
					<div class="field field--check" data-field="consent">
						<input class="check__input" type="checkbox" id="va-consent" name="consent" value="1" required aria-describedby="va-consent-error">
						<label class="check check--light" for="va-consent">
							<span class="check__box" aria-hidden="true"><?php echo rivermere_icon( 'check', 'check__tick' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span class="check__text">
								<?php esc_html_e( 'I agree that Rivermere Farmers Market may use my information to review and respond to my vendor application. I have read the', 'rivermere-market' ); ?>
								<?php if ( $privacy ) : ?>
									<a href="<?php echo esc_url( $privacy ); ?>"><?php esc_html_e( 'Privacy Policy', 'rivermere-market' ); ?></a>.
								<?php else : ?>
									<?php esc_html_e( 'Privacy Policy.', 'rivermere-market' ); ?>
								<?php endif; ?>
								<span class="field__req" aria-hidden="true">*</span>
							</span>
						</label>
						<p class="field__error" id="va-consent-error" hidden></p>
					</div>
				</fieldset>

				<div class="screen-reader-text" aria-hidden="true">
					<label for="va-company-website"><?php esc_html_e( 'Leave this field empty', 'rivermere-market' ); ?></label>
					<input type="text" id="va-company-website" name="company_website" tabindex="-1" autocomplete="off">
				</div>
				<input type="hidden" name="rm_ts" value="<?php echo esc_attr( (string) time() ); ?>">
				<input type="hidden" name="submission_id" value="">

				<div class="apply-form__submit">
					<button type="submit" class="btn btn--secondary" data-apply-submit data-insights-event="apply_submit" data-insights-label="vendor-application"><span><?php esc_html_e( 'Submit Vendor Application', 'rivermere-market' ); ?></span></button>
					<p class="apply-form__status" role="status" aria-live="polite" data-apply-status></p>
				</div>
			</form>
		<?php endif; ?>
	</div>
</section>
