<?php
/**
 * Participating vendors.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$ids = $args['ev']['vendors'];
if ( ! $ids ) {
	return;
}
$vendors = get_posts(
	array(
		'post_type'      => 'rm_vendor',
		'post__in'       => $ids,
		'orderby'        => 'post__in',
		'posts_per_page' => count( $ids ),
		'no_found_rows'  => true,
	)
);
if ( ! $vendors ) {
	return;
}
?>
<section class="event-section" aria-labelledby="event-vendors-title">
	<h2 id="event-vendors-title"><?php esc_html_e( 'Vendors at this event', 'rivermere-market' ); ?></h2>
	<ul class="vendor-grid vendor-grid--compact">
		<?php foreach ( $vendors as $vendor ) : ?>
			<li><?php get_template_part( 'template-parts/components/vendor-card', null, array( 'post' => $vendor ) ); ?></li>
		<?php endforeach; ?>
	</ul>
</section>
