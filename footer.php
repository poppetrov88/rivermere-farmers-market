<?php
/**
 * Site footer.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;
?>
<footer class="site-footer">
	<?php get_template_part( 'template-parts/footer/newsletter' ); ?>
	<?php get_template_part( 'template-parts/footer/footer' ); ?>
</footer>

<?php get_template_part( 'template-parts/components/contact-widget' ); ?>
<div class="toast" role="status" aria-live="polite" data-toast></div>

<?php wp_footer(); ?>
</body>
</html>
