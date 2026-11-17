<?php
/**
 * Section: Hero.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$eyebrow    = rivermere_sub( 'eyebrow' );
$heading    = rivermere_sub( 'heading', get_the_title() );
$desc       = rivermere_sub( 'description' );
$primary    = rivermere_sub( 'primary_cta', array() );
$secondary  = rivermere_sub( 'secondary_cta', array() );
$image      = rivermere_sub( 'image', array() );
$alt        = (string) rivermere_sub( 'image_alt', '' );
$highlights = rivermere_sub( 'highlights', array() );
$is_first   = rivermere_is_first_section();
?>
<section class="hero" aria-labelledby="hero-title">
	<?php if ( ! empty( $image['ID'] ) ) : ?>
		<div class="hero__media">
			<?php
			echo wp_get_attachment_image(
				$image['ID'],
				'rivermere-hero',
				false,
				array(
					'class'         => 'hero__img',
					'alt'           => $alt,
					'loading'       => $is_first ? 'eager' : 'lazy',
					'fetchpriority' => $is_first ? 'high' : 'auto',
					'decoding'      => 'async',
					'sizes'         => '(min-width: 1024px) 56vw, 100vw',
				)
			);
			?>
		</div>
	<?php endif; ?>

	<svg class="hero__shape" viewBox="0 0 1440 640" preserveAspectRatio="none" aria-hidden="true" focusable="false">
		<defs>
			<linearGradient id="hero-grad" x1="0" y1="0" x2="1" y2="0">
				<stop offset="0" stop-color="#053120"/>
				<stop offset="1" stop-color="#0e3e21"/>
			</linearGradient>
		</defs>
		<path d="M0 0H752C768 70 748 140 718 210 690 274 640 330 640 400 640 480 690 590 740 640H0Z" fill="url(#hero-grad)"/>
	</svg>

	<div class="container hero__inner">
		<div class="hero__content">
			<?php if ( $eyebrow ) : ?>
				<p class="eyebrow eyebrow--light"><?php echo esc_html( $eyebrow ); ?></p>
			<?php endif; ?>
			<h1 class="hero__title" id="hero-title"><?php echo rivermere_heading_html( $heading ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></h1>
			<?php if ( $desc ) : ?>
				<p class="hero__desc"><?php echo esc_html( $desc ); ?></p>
			<?php endif; ?>
			<?php if ( ! empty( $primary['url'] ) || ! empty( $secondary['url'] ) ) : ?>
				<div class="hero__actions">
					<?php echo rivermere_button( $primary, 'accent' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo rivermere_button( $secondary, 'outline-light', array( 'icon' => false ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( $highlights ) : ?>
			<ul class="hero__highlights">
				<?php foreach ( $highlights as $item ) : ?>
					<li>
						<?php echo rivermere_icon( $item['icon'] ?? 'leaf', 'hero__hl-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( $item['label'] ?? '' ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>

	<svg class="hero__wave" viewBox="0 0 1440 40" preserveAspectRatio="none" aria-hidden="true" focusable="false">
		<path d="M0 20C240 40 480 0 720 14S1200 40 1440 16V40H0Z" fill="#fbf8ee"/>
	</svg>
</section>
