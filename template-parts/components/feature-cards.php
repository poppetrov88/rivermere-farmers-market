<?php
/**
 * Reusable card grid (benefits, what we do, values).
 *
 * Args: id, title, intro, eyebrow, cards[ {icon,title,text,url,label} ], columns (3|4), tone ('', 'alt').
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$cards = array_filter( (array) ( $args['cards'] ?? array() ), static function ( $c ) {
	return ! empty( $c['title'] );
} );
if ( ! $cards ) {
	return;
}
$uid = ( $args['id'] ?? 'cards' ) . '-title';
?>
<section class="section section--flow feature-section<?php echo ! empty( $args['tone'] ) ? ' feature-section--' . esc_attr( $args['tone'] ) : ''; ?>" id="<?php echo esc_attr( $args['id'] ?? '' ); ?>" aria-labelledby="<?php echo esc_attr( $uid ); ?>">
	<div class="container">
		<header class="feature-section__head">
			<?php if ( ! empty( $args['eyebrow'] ) ) : ?>
				<p class="eyebrow"><?php echo esc_html( $args['eyebrow'] ); ?></p>
			<?php endif; ?>
			<h2 class="feature-section__title" id="<?php echo esc_attr( $uid ); ?>"><?php echo esc_html( $args['title'] ); ?></h2>
			<?php if ( ! empty( $args['intro'] ) ) : ?>
				<p class="feature-section__intro"><?php echo esc_html( $args['intro'] ); ?></p>
			<?php endif; ?>
		</header>
		<ul class="feature-grid feature-grid--<?php echo (int) ( $args['columns'] ?? 3 ); ?>">
			<?php foreach ( $cards as $card ) : ?>
				<li class="feature-card">
					<span class="feature-card__icon"><?php echo rivermere_icon( $card['icon'] ?? 'leaf' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<h3 class="feature-card__title"><?php echo esc_html( $card['title'] ); ?></h3>
					<?php if ( ! empty( $card['text'] ) ) : ?>
						<p class="feature-card__text"><?php echo esc_html( $card['text'] ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $card['url'] ) && ! empty( $card['label'] ) ) : ?>
						<a class="feature-card__link" href="<?php echo esc_url( $card['url'] ); ?>"><?php echo esc_html( $card['label'] ); ?> <?php echo rivermere_icon( 'arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></a>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
