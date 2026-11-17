<?php
/**
 * Navigation helpers.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

/**
 * Print a menu for a theme location with a sensible fallback.
 *
 * @param string $location Registered location.
 * @param array  $args     wp_nav_menu overrides.
 */
function rivermere_menu( $location, $args = array() ) {
	$defaults = array(
		'theme_location' => $location,
		'container'      => false,
		'menu_class'     => 'menu menu--' . $location,
		'depth'          => 2,
		'fallback_cb'    => 'rivermere_menu_fallback',
	);
	wp_nav_menu( wp_parse_args( $args, $defaults ) );
}

/**
 * Fallback menu: published top-level pages.
 *
 * @param array $args wp_nav_menu args.
 */
function rivermere_menu_fallback( $args ) {
	$class = isset( $args['menu_class'] ) ? $args['menu_class'] : 'menu';
	$pages = get_pages(
		array(
			'parent'      => 0,
			'sort_column' => 'menu_order,post_title',
			'number'      => 8,
		)
	);
	echo '<ul class="' . esc_attr( $class ) . '">';
	echo '<li class="menu-item' . ( is_front_page() ? ' current-menu-item' : '' ) . '"><a href="' . esc_url( home_url( '/' ) ) . '"' . ( is_front_page() ? ' aria-current="page"' : '' ) . '>' . esc_html__( 'Home', 'rivermere-market' ) . '</a></li>';
	foreach ( $pages as $page ) {
		if ( (int) get_option( 'page_on_front' ) === $page->ID ) {
			continue;
		}
		echo '<li class="menu-item"><a href="' . esc_url( get_permalink( $page ) ) . '">' . esc_html( get_the_title( $page ) ) . '</a></li>';
	}
	echo '</ul>';
}

/**
 * Mark the current item with aria-current on links.
 *
 * @param array $atts Link attributes.
 * @param WP_Post $item Menu item.
 * @return array
 */
function rivermere_menu_link_atts( $atts, $item ) {
	if ( ! empty( $item->current ) ) {
		$atts['aria-current'] = 'page';
	}
	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'rivermere_menu_link_atts', 10, 2 );
