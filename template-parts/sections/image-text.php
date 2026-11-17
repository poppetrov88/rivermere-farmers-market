<?php
/**
 * Section: Image and text (community gathering).
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$eyebrow  = rivermere_sub( 'eyebrow' );
$heading  = rivermere_sub( 'heading' );
$text     = rivermere_sub( 'text' );
$details  = rivermere_sub( 'details', array() );
$cta      = rivermere_sub( 'cta', array() );
$image    = rivermere_sub( 'image', array() );
$alt      = (string) rivermere_sub( 'image_alt', '' );
$position = rivermere_sub( 'image_position', 'left' );
if ( ! $heading ) {
	return;
}
$uid = 'imgtxt-' . wp_unique_id();
?>
<section class="section section--flow section--image-text image-text image-text--<?php echo esc_attr( 'right' === $position ? 'right' : 'left' ); ?>" aria-labelledby="<?php echo esc_attr( $uid ); ?>">
	<div class="container image-text__grid">
		<?php if ( ! empty( $image['ID'] ) ) : ?>
			<div class="image-text__media">
				<?php
				echo wp_get_attachment_image(
					$image['ID'],
					'rivermere-feature',
					false,
					array(
						'class'    => 'image-text__img',
												'loading'  => 'lazy',
						'decoding' => 'async',
						'sizes'    => '(min-width: 1024px) 55vw, 100vw',
					)
				);
				?>
			</div>
		<?php endif; ?>
		<div class="image-text__body">
			<span class="image-text__blob" aria-hidden="true"></span>
			<?php get_template_part( 'template-parts/components/leaves', null, array( 'class' => 'image-text__leaves' ) ); ?>
			<div class="image-text__content">
				<?php if ( $eyebrow ) : ?>
					<p class="eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
				<?php endif; ?>
				<h2 class="image-text__title" id="<?php echo esc_attr( $uid ); ?>"><?php echo rivermere_heading_html( $heading ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h2>
				<?php if ( $text ) : ?>
					<p class="image-text__text"><?php echo esc_html( $text ); ?></p>
				<?php endif; ?>
				<?php if ( $details ) : ?>
					<ul class="detail-list">
						<?php foreach ( $details as $detail ) : ?>
							<li><?php echo rivermere_icon( $detail['icon'] ?? 'leaf' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <span><?php echo esc_html( $detail['text'] ?? '' ); ?></span></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<?php if ( ! empty( $cta['url'] ) ) : ?>
					<p class="image-text__cta"><?php echo rivermere_button( $cta, 'secondary' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
