<?php
/**
 * Search results.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="main-content" class="site-main">
	<?php
	get_template_part(
		'template-parts/components/page-header',
		null,
		array(
			'title' => sprintf( /* translators: %s: search query */ __( 'Search results for “%s”', 'rivermere-market' ), get_search_query() ),
		)
	);
	?>
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
