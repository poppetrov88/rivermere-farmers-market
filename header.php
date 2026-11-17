<?php
/**
 * Site header.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<?php if ( rivermere_toggle( 'ui_progress', true ) ) : ?>
<div class="scroll-progress" aria-hidden="true"><span class="scroll-progress__bar" data-scroll-progress></span></div>
<?php endif; ?>
<a class="skip-link screen-reader-text" href="#main-content"><?php esc_html_e( 'Skip to content', 'rivermere-market' ); ?></a>

<?php get_template_part( 'template-parts/header/announcement' ); ?>
<?php get_template_part( 'template-parts/header/utility-bar' ); ?>

<header class="site-header" id="site-header">
	<?php get_template_part( 'template-parts/header/header' ); ?>
</header>

<?php get_template_part( 'template-parts/header/search-overlay' ); ?>

<?php rivermere_breadcrumbs(); ?>
