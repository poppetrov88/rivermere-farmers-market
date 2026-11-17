<?php
/**
 * Event image gallery (captions shown when set).
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$images = $args['ev']['gallery'];
if ( ! $images ) {
	return;
}
?>
<section class="event-section" aria-labelledby="event-gallery-title">
	<h2 id="event-gallery-title"><?php esc_html_e( 'Gallery', 'rivermere-market' ); ?></h2>
	<ul class="gallery-grid">
		<?php foreach ( $images as $image ) : ?>
			<li>
				<figure class="gallery-grid__item">
					<?php echo wp_get_attachment_image( $image['ID'], 'rivermere-card', false, array( 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(min-width: 1024px) 30vw, 50vw' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php if ( ! empty( $image['caption'] ) ) : ?>
						<figcaption><?php echo esc_html( $image['caption'] ); ?></figcaption>
					<?php endif; ?>
				</figure>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
