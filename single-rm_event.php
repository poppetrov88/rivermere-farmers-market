<?php
/**
 * Single event page: hero, two-column layout (content + sticky info card), related events.
 * All content is editable (post content + Event details fields).
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="main-content" class="site-main site-main--event">
	<?php
	while ( have_posts() ) :
		the_post();
		$rm_id = get_the_ID();
		$ev    = rivermere_event_data( $rm_id );
		get_template_part( 'template-parts/event/hero', null, array( 'ev' => $ev ) );
		?>
		<div class="container event-layout">
			<article <?php post_class( 'event-main' ); ?>>
				<?php if ( in_array( $ev['status'], array( 'cancelled', 'postponed' ), true ) ) : ?>
					<div class="notice notice--error" role="status">
						<p><strong><?php echo 'cancelled' === $ev['status'] ? esc_html__( 'This event has been cancelled.', 'rivermere-market' ) : esc_html__( 'This event has been postponed. A new date will be announced.', 'rivermere-market' ); ?></strong></p>
					</div>
				<?php endif; ?>

				<div class="entry-content event-content"><?php the_content(); ?></div>

				<?php get_template_part( 'template-parts/event/schedule', null, array( 'ev' => $ev ) ); ?>
				<?php get_template_part( 'template-parts/event/accessibility', null, array( 'ev' => $ev ) ); ?>
				<?php get_template_part( 'template-parts/event/vendors', null, array( 'ev' => $ev ) ); ?>
				<?php get_template_part( 'template-parts/event/gallery', null, array( 'ev' => $ev ) ); ?>
				<?php get_template_part( 'template-parts/event/faq', null, array( 'ev' => $ev ) ); ?>
			</article>

			<?php get_template_part( 'template-parts/event/sidebar', null, array( 'ev' => $ev ) ); ?>
		</div>

		<?php get_template_part( 'template-parts/event/related', null, array( 'ev' => $ev ) ); ?>
		<?php
	endwhile;
	?>
</main>
<?php
get_footer();
