<?php
/**
 * Landing hero (Become a Vendor, About Us): text column aligned to the header container + image.
 *
 * Args: eyebrow, title, intro, image (ACF image array or attachment ID), buttons[ {label,url,variant,icon,attrs} ].
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$image_id = is_array( $args['image'] ?? null ) ? (int) ( $args['image']['ID'] ?? 0 ) : (int) ( $args['image'] ?? 0 );
?>
<section class="landing-hero" aria-labelledby="landing-hero-title">
	<div class="container landing-hero__grid">
		<div class="landing-hero__text">
			<?php if ( ! empty( $args['eyebrow'] ) ) : ?>
				<p class="eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p>
			<?php endif; ?>
			<h1 class="landing-hero__title" id="landing-hero-title"><?php echo esc_html( $args['title'] ); ?></h1>
			<?php if ( ! empty( $args['intro'] ) ) : ?>
				<p class="landing-hero__intro"><?php echo esc_html( $args['intro'] ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $args['buttons'] ) ) : ?>
				<div class="landing-hero__actions">
					<?php foreach ( $args['buttons'] as $btn ) : ?>
						<?php echo rivermere_button( array( 'url' => $btn['url'], 'title' => $btn['label'] ), $btn['variant'] ?? 'secondary', array( 'icon' => $btn['icon'] ?? 'arrow' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php if ( $image_id ) : ?>
			<figure class="landing-hero__media">
				<?php
				echo wp_get_attachment_image(
					$image_id,
					'rivermere-feature',
					false,
					array(
						'class'         => 'landing-hero__img',
						'loading'       => 'eager',
						'fetchpriority' => 'high',
						'decoding'      => 'async',
						'sizes'         => '(min-width: 1024px) 560px, 100vw',
					)
				);
				?>
			</figure>
		<?php endif; ?>
	</div>
</section>
