<?php
/**
 * Front page: renders the ACF Flexible Content page builder.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="main-content" class="site-main site-main--front">
	<?php
	while ( have_posts() ) :
		the_post();
		if ( ! rivermere_render_sections() ) {
			get_template_part( 'template-parts/components/page-header', null, array( 'title' => get_the_title() ) );
			echo '<div class="container section entry-content">';
			the_content();
			echo '</div>';
		}
	endwhile;
	?>
</main>
<?php
get_footer();
