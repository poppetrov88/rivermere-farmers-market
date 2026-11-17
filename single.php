<?php
/**
 * Single post / vendor / event.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="main-content" class="site-main">
	<?php
	while ( have_posts() ) :
		the_post();
		get_template_part( 'template-parts/components/page-header', null, array( 'title' => get_the_title(), 'eyebrow' => rivermere_single_eyebrow() ) );
		?>
		<div class="container container--narrow section">
			<article <?php post_class( 'entry' ); ?>>
				<?php
				if ( 'rm_event' === get_post_type() ) {
					get_template_part( 'template-parts/content/event-details' );
				} elseif ( 'rm_vendor' === get_post_type() ) {
					get_template_part( 'template-parts/content/vendor-details' );
				} elseif ( has_post_thumbnail() ) {
					echo '<div class="entry__thumb">' . get_the_post_thumbnail( null, 'rivermere-feature', array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
				?>
				<div class="entry-content"><?php the_content(); ?></div>
			</article>
			<?php
			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
			?>
		</div>
		<?php
	endwhile;
	?>
	<?php rivermere_render_faq(); ?>
	<?php if ( is_singular( 'rm_vendor' ) ) : ?>
		<?php get_template_part( 'template-parts/components/vendor-cta' ); ?>
	<?php endif; ?>
</main>
<?php
get_footer();
