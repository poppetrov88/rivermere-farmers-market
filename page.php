<?php
/**
 * Default page template. Pages with page-builder sections render them; otherwise the editor content.
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
		if ( rivermere_render_sections() ) {
			continue;
		}
		get_template_part( 'template-parts/components/page-header', null, array( 'title' => get_the_title() ) );
		?>
		<div class="container container--narrow section">
			<article <?php post_class( 'entry' ); ?>>
				<div class="entry-content"><?php the_content(); ?></div>
			</article>
		</div>
		<?php
	endwhile;
	?>
</main>
<?php
get_footer();
