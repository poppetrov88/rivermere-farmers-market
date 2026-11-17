<?php
/**
 * Social links. Configured networks render as external links; unconfigured ones render as
 * buttons that announce "Coming soon" (never a dead or invented URL).
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$networks = rivermere_social_networks();
$urls     = rivermere_social_urls();
$class    = isset( $args['class'] ) ? $args['class'] : '';
?>
<ul class="social-list <?php echo esc_attr( $class ); ?>">
	<?php foreach ( $networks as $key => $label ) : ?>
		<li>
			<?php if ( $urls[ $key ] ) : ?>
				<a class="social-list__link" href="<?php echo esc_url( $urls[ $key ] ); ?>" target="_blank" rel="noopener noreferrer">
					<?php echo rivermere_icon( $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span class="screen-reader-text"><?php echo esc_html( sprintf( /* translators: %s: network */ __( '%s (opens in a new tab)', 'rivermere-market' ), $label ) ); ?></span>
				</a>
			<?php else : ?>
				<button type="button" class="social-list__link is-soon" data-coming-soon="<?php echo esc_attr( sprintf( /* translators: %s: network */ __( '%s page coming soon', 'rivermere-market' ), $label ) ); ?>">
					<?php echo rivermere_icon( $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span class="screen-reader-text"><?php echo esc_html( sprintf( /* translators: %s: network */ __( '%s – coming soon', 'rivermere-market' ), $label ) ); ?></span>
				</button>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ul>
