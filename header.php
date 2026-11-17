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
<a class="skip-link screen-reader-text" href="#main-content"><?php esc_html_e( 'Skip to content', 'rivermere-market' ); ?></a>

<?php get_template_part( 'template-parts/header/announcement' ); ?>

<header class="site-header" id="site-header">
	<?php get_template_part( 'template-parts/header/header' ); ?>
</header>

<?php get_template_part( 'template-parts/header/search-overlay' ); ?>
