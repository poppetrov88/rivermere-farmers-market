<?php
/**
 * Vendor card.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$vendor = isset( $args['post'] ) ? $args['post'] : get_post();
if ( ! $vendor ) {
	return;
}
$tagline = rivermere_field( 'vendor_tagline', $vendor->ID, '' );
$title   = get_the_title( $vendor );
$tag     = isset( $args['heading'] ) && in_array( $args['heading'], array( 'h2', 'h3' ), true ) ? $args['heading'] : 'h3';
?>
<article class="vendor-card">
	<a class="vendor-card__link" href="<?php echo esc_url( get_permalink( $vendor ) ); ?>">
		<div class="vendor-card__media">
			<?php if ( has_post_thumbnail( $vendor ) ) : ?>
				<?php echo get_the_post_thumbnail( $vendor, 'rivermere-card', array( 'class' => 'vendor-card__img', 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(min-width: 1024px) 25vw, (min-width: 600px) 50vw, 100vw' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php else : ?>
				<span class="vendor-card__ph" aria-hidden="true"><?php echo rivermere_icon( 'basket' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<?php endif; ?>
		</div>
		<div class="vendor-card__body">
			<div>
				<<?php echo esc_html( $tag ); ?> class="vendor-card__title"><?php echo esc_html( $title ); ?></<?php echo esc_html( $tag ); ?>>
				<?php if ( $tagline ) : ?>
					<p class="vendor-card__tagline"><?php echo esc_html( $tagline ); ?></p>
				<?php endif; ?>
			</div>
			<span class="vendor-card__arrow" aria-hidden="true"><?php echo rivermere_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		</div>
	</a>
</article>
