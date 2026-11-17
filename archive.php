<?php
/**
 * Archives (posts, vendors, events, categories).
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="main-content" class="site-main">
	<?php get_template_part( 'template-parts/components/page-header', null, array( 'title' => wp_strip_all_tags( get_the_archive_title() ), 'text' => get_the_archive_description() ) ); ?>
	<div class="container section">
		<?php if ( have_posts() ) : ?>
			<?php $rm_type = get_post_type(); ?>
			<div class="<?php echo 'rm_vendor' === $rm_type ? 'vendor-grid' : 'card-grid'; ?>">
				<?php
				while ( have_posts() ) :
					the_post();
					if ( 'rm_vendor' === $rm_type ) {
						echo '<div>';
						get_template_part( 'template-parts/components/vendor-card', null, array( 'post' => get_post() ) );
						echo '</div>';
					} else {
						get_template_part( 'template-parts/content/card' );
					}
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
