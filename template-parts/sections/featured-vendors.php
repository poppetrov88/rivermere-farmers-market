<?php
/**
 * Section: Featured vendors.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$heading = rivermere_sub( 'heading', __( 'Meet Our Featured Vendors', 'rivermere-market' ) );
$source  = rivermere_sub( 'source', 'featured' );
$count   = max( 1, min( 8, (int) rivermere_sub( 'count', 4 ) ) );
$all     = rivermere_sub( 'all_link', array() );

if ( 'manual' === $source ) {
	$ids     = array_filter( array_map( 'absint', (array) rivermere_sub( 'vendors', array() ) ) );
	$vendors = $ids ? get_posts(
		array(
			'post_type'      => 'rm_vendor',
			'post__in'       => $ids,
			'orderby'        => 'post__in',
			'posts_per_page' => count( $ids ),
			'no_found_rows'  => true,
		)
	) : array();
} else {
	$vendors = rivermere_get_featured_vendors( $count );
}
if ( empty( $vendors ) ) {
	return;
}
?>
<section class="section section--vendors" aria-labelledby="vendors-title">
	<div class="container">
		<h2 class="section-heading" id="vendors-title"><?php echo esc_html( $heading ); ?></h2>
		<ul class="vendor-grid">
			<?php foreach ( $vendors as $vendor ) : ?>
				<li>
					<?php get_template_part( 'template-parts/components/vendor-card', null, array( 'post' => $vendor ) ); ?>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php if ( ! empty( $all['url'] ) ) : ?>
			<p class="section__more"><?php echo rivermere_button( $all, 'outline' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		<?php endif; ?>
	</div>
</section>
