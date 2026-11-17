<?php
/**
 * Newsletter band (design: green footer band with leaves). Single instance site-wide.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

if ( ! rivermere_toggle( 'newsletter_enabled', true ) ) {
	return;
}
$heading = rivermere_option( 'newsletter_heading', __( 'Stay in the Loop', 'rivermere-market' ) );
$desc    = rivermere_option( 'newsletter_description', '' );
$consent = str_replace( '{market}', rivermere_market_name(), (string) rivermere_option( 'newsletter_consent', 'I agree to receive email updates from {market}. I can unsubscribe at any time.' ) );
$privacy = get_privacy_policy_url();
$privacy = $privacy ? $privacy : (string) rivermere_option( 'privacy_page', '' );
?>
<section class="newsletter" aria-labelledby="newsletter-title">
	<?php get_template_part( 'template-parts/components/leaves', null, array( 'class' => 'newsletter__leaves newsletter__leaves--left' ) ); ?>
	<?php get_template_part( 'template-parts/components/leaves', null, array( 'class' => 'newsletter__leaves newsletter__leaves--right' ) ); ?>
	<div class="container newsletter__inner">
		<div class="newsletter__intro">
			<?php echo rivermere_icon( 'mail', 'newsletter__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<div>
				<h2 class="newsletter__title" id="newsletter-title"><?php echo esc_html( $heading ); ?></h2>
				<?php if ( $desc ) : ?>
					<p><?php echo esc_html( $desc ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<form class="newsletter__form" method="post" novalidate data-newsletter-form>
			<div class="newsletter__row">
				<label class="screen-reader-text" for="newsletter-email"><?php esc_html_e( 'Email address', 'rivermere-market' ); ?></label>
				<div class="newsletter__field">
					<?php echo rivermere_icon( 'mail', 'newsletter__field-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<input type="email" id="newsletter-email" name="email" placeholder="<?php esc_attr_e( 'Enter your email address', 'rivermere-market' ); ?>" autocomplete="email" required aria-describedby="newsletter-status">
					<button type="submit" class="btn btn--accent btn--sm" data-newsletter-submit data-insights-event="newsletter_cta" data-insights-label="newsletter-footer"><span><?php esc_html_e( 'Subscribe', 'rivermere-market' ); ?></span></button>
				</div>
			</div>
			<div class="newsletter__consent">
				<input class="check__input" type="checkbox" id="newsletter-consent" name="consent" value="1" required aria-describedby="newsletter-consent-error">
				<label class="check" for="newsletter-consent">
					<span class="check__box" aria-hidden="true"><?php echo rivermere_icon( 'check', 'check__tick' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="check__text"><?php echo esc_html( $consent ); ?>
						<?php if ( $privacy ) : ?>
							<a href="<?php echo esc_url( $privacy ); ?>"><?php esc_html_e( 'Read our Privacy Policy', 'rivermere-market' ); ?></a>.
						<?php endif; ?>
					</span>
				</label>
			</div>
			<p class="newsletter__error" id="newsletter-consent-error" data-consent-error hidden><?php esc_html_e( 'Please tick the box to confirm you agree to receive our emails.', 'rivermere-market' ); ?></p>
			<div class="screen-reader-text" aria-hidden="true">
				<label for="newsletter-website"><?php esc_html_e( 'Leave this field empty', 'rivermere-market' ); ?></label>
				<input type="text" id="newsletter-website" name="website" tabindex="-1" autocomplete="off">
			</div>
			<input type="hidden" name="rm_ts" value="<?php echo esc_attr( (string) time() ); ?>">
			<p class="newsletter__status" id="newsletter-status" role="status" aria-live="polite" data-newsletter-status></p>
		</form>
	</div>
</section>
