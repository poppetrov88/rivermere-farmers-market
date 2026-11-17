<?php
/**
 * Fallback template (blog index).
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="main-content" class="site-main">
	<?php get_template_part( 'template-parts/components/page-header', null, array( 'title' => is_home() && ! is_front_page() ? single_post_title( '', false ) : __( 'Latest news', 'rivermere-market' ) ) ); ?>
	<div class="container section">
		<?php if ( have_posts() ) : ?>
			<div class="card-grid">
				<?php
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/content/card' );
				endwhile;
				?>
			</div>
			<?php rivermere_pagination(); ?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/content/none' ); ?>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
