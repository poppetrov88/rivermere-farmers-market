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
$consent = rivermere_option( 'newsletter_consent', __( 'I agree to receive market updates by email and can unsubscribe at any time.', 'rivermere-market' ) );
$privacy = rivermere_option( 'privacy_page', '' );
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

		<form class="newsletter__form" method="post" action="" novalidate data-newsletter-form>
			<div class="newsletter__row">
				<label class="screen-reader-text" for="newsletter-email"><?php esc_html_e( 'Email address', 'rivermere-market' ); ?></label>
				<div class="newsletter__field">
					<?php echo rivermere_icon( 'mail', 'newsletter__field-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<input type="email" id="newsletter-email" name="email" placeholder="<?php esc_attr_e( 'Enter your email address', 'rivermere-market' ); ?>" autocomplete="email" required aria-describedby="newsletter-status">
					<button type="submit" class="btn btn--accent btn--sm" data-newsletter-submit><span><?php esc_html_e( 'Subscribe', 'rivermere-market' ); ?></span></button>
				</div>
			</div>
			<div class="newsletter__consent">
				<input type="checkbox" id="newsletter-consent" name="consent" value="1" required>
				<label for="newsletter-consent">
					<?php echo esc_html( $consent ); ?>
					<?php if ( $privacy ) : ?>
						<a href="<?php echo esc_url( $privacy ); ?>"><?php esc_html_e( 'Privacy policy', 'rivermere-market' ); ?></a>
					<?php endif; ?>
				</label>
			</div>
			<div class="screen-reader-text" aria-hidden="true">
				<label for="newsletter-website"><?php esc_html_e( 'Leave this field empty', 'rivermere-market' ); ?></label>
				<input type="text" id="newsletter-website" name="website" tabindex="-1" autocomplete="off">
			</div>
			<input type="hidden" name="rm_ts" value="<?php echo esc_attr( (string) time() ); ?>">
			<p class="newsletter__status" id="newsletter-status" role="status" aria-live="polite" data-newsletter-status></p>
		</form>
	</div>
</section>
