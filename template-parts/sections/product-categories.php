<?php
/**
 * Section: Product categories (taxonomy-driven).
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$heading = rivermere_sub( 'heading', __( 'Explore What’s at the Market', 'rivermere-market' ) );
$terms   = rivermere_sub( 'categories', array() );
if ( empty( $terms ) ) {
	$terms = get_terms(
		array(
			'taxonomy'   => 'rm_product_category',
			'hide_empty' => false,
			'number'     => 12,
		)
	);
}
if ( is_wp_error( $terms ) || empty( $terms ) ) {
	return;
}
?>
<section class="section section--categories" aria-labelledby="cats-title">
	<div class="container">
		<h2 class="section-heading" id="cats-title"><?php echo esc_html( $heading ); ?></h2>
		<ul class="category-grid">
			<?php foreach ( $terms as $term ) : ?>
				<?php
				$icon = rivermere_field( 'category_icon', $term, 'leaf' );
				$link = get_term_link( $term );
				if ( is_wp_error( $link ) ) {
					continue;
				}
				?>
				<li>
					<a class="category-card" href="<?php echo esc_url( $link ); ?>">
						<?php echo rivermere_icon( $icon, 'category-card__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span class="category-card__title"><?php echo esc_html( $term->name ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
