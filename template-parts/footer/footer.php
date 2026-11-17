<?php
/**
 * Footer: brand, description, menus, contact, social, legal.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$desc      = rivermere_option( 'footer_description', '' );
$cta       = rivermere_option( 'footer_cta', array() );
$email     = rivermere_option( 'contact_email', '' );
$phone     = rivermere_option( 'phone', '' );
$address   = rivermere_option( 'address', '' );
$maps      = rivermere_option( 'maps_url', '' );
$days      = rivermere_option( 'market_days', '' );
$socials   = rivermere_option( 'social_links', array() );
$networks  = rivermere_social_networks();
$privacy   = get_privacy_policy_url();
$privacy   = $privacy ? $privacy : rivermere_option( 'privacy_page', '' );
?>
<div class="footer-main">
	<div class="container footer-main__grid">
		<div class="footer-brand">
			<a class="brand brand--light" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( rivermere_market_name() ); ?>">
				<?php rivermere_logo( 'alt' ); ?>
			</a>
			<?php if ( $desc ) : ?>
				<p class="footer-brand__desc"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $cta['url'] ) ) : ?>
				<p><?php echo rivermere_button( $cta, 'accent', array( 'class' => 'btn--sm' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
			<?php endif; ?>
		</div>

		<nav class="footer-nav" aria-labelledby="footer-nav-title">
			<h2 class="footer-title" id="footer-nav-title"><?php esc_html_e( 'Explore', 'rivermere-market' ); ?></h2>
			<?php rivermere_menu( 'footer', array( 'depth' => 1 ) ); ?>
		</nav>

		<div class="footer-contact">
			<h2 class="footer-title"><?php esc_html_e( 'Visit & contact', 'rivermere-market' ); ?></h2>
			<address>
				<?php if ( $days ) : ?>
					<p><?php echo rivermere_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <span><?php echo esc_html( $days ); ?></span></p>
				<?php endif; ?>
				<?php if ( $address ) : ?>
					<p><?php echo rivermere_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <span><?php echo wp_kses( $address, array( 'br' => array() ) ); ?>
						<?php if ( $maps ) : ?>
							<br><a href="<?php echo esc_url( $maps ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Get directions', 'rivermere-market' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'rivermere-market' ); ?></span></a>
						<?php endif; ?></span></p>
				<?php endif; ?>
				<?php if ( $email ) : ?>
					<p><?php echo rivermere_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <a href="mailto:<?php echo esc_attr( antispambot( $email ) ); ?>"><?php echo esc_html( antispambot( $email ) ); ?></a></p>
				<?php endif; ?>
				<?php if ( $phone ) : ?>
					<p><?php echo rivermere_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></p>
				<?php endif; ?>
			</address>

			<?php if ( $socials ) : ?>
				<ul class="social-list">
					<?php foreach ( $socials as $social ) : ?>
						<?php
						if ( empty( $social['url'] ) || empty( $social['network'] ) || ! isset( $networks[ $social['network'] ] ) ) {
							continue;
						}
						?>
						<li>
							<a class="social-list__link" href="<?php echo esc_url( $social['url'] ); ?>" target="_blank" rel="noopener me">
								<?php echo rivermere_icon( $social['network'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span class="screen-reader-text"><?php echo esc_html( $networks[ $social['network'] ] ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
	</div>
</div>

<div class="footer-bottom">
	<div class="container footer-bottom__inner">
		<p><?php echo esc_html( rivermere_copyright() ); ?></p>
		<nav aria-label="<?php esc_attr_e( 'Legal', 'rivermere-market' ); ?>">
			<?php
			if ( has_nav_menu( 'legal' ) ) {
				rivermere_menu( 'legal', array( 'depth' => 1, 'fallback_cb' => false ) );
			} elseif ( $privacy ) {
				echo '<ul class="menu"><li><a href="' . esc_url( $privacy ) . '">' . esc_html__( 'Privacy policy', 'rivermere-market' ) . '</a></li></ul>';
			}
			?>
		</nav>
	</div>
</div>
