<?php
/**
 * 404 template.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="main-content" class="site-main">
	<?php get_template_part( 'template-parts/components/page-header', null, array( 'title' => __( 'Page not found', 'rivermere-market' ) ) ); ?>
	<div class="container container--narrow section">
		<p><?php esc_html_e( 'Sorry, we couldn’t find that page. Try searching, or head back to the homepage.', 'rivermere-market' ); ?></p>
		<?php get_search_form(); ?>
		<p><a class="btn btn--secondary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><span><?php esc_html_e( 'Back to home', 'rivermere-market' ); ?></span></a></p>
	</div>
</main>
<?php
get_footer();
