<?php
/**
 * Theme setup: supports, menus, image sizes, widgets.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register theme supports and menus.
 */
function rivermere_setup() {
	load_theme_textdomain( 'rivermere-market', RIVERMERE_DIR . '/languages' );

	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'custom-logo', array(
		'height'      => 120,
		'width'       => 360,
		'flex-height' => true,
		'flex-width'  => true,
	) );

	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'rivermere-market' ),
			'footer'  => __( 'Footer Menu', 'rivermere-market' ),
			'legal'   => __( 'Legal Menu', 'rivermere-market' ),
		)
	);

	add_image_size( 'rivermere-hero', 1200, 900, true );
	add_image_size( 'rivermere-card', 640, 400, true );
	add_image_size( 'rivermere-feature', 1000, 640, true );
}
add_action( 'after_setup_theme', 'rivermere_setup' );

/**
 * Content width.
 */
function rivermere_content_width() {
	$GLOBALS['content_width'] = 1200;
}
add_action( 'after_setup_theme', 'rivermere_content_width', 0 );

/**
 * Hide the ACF dependency's absence behind an admin notice.
 */
function rivermere_acf_notice() {
	if ( function_exists( 'acf_add_local_field_group' ) || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	printf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		esc_html__( 'Rivermere Market requires Advanced Custom Fields Pro for its page builder and Theme Settings. Please install and activate it.', 'rivermere-market' )
	);
}
add_action( 'admin_notices', 'rivermere_acf_notice' );

/**
 * Remove emoji scripts/styles (performance).
 */
function rivermere_disable_emoji() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
}
add_action( 'init', 'rivermere_disable_emoji' );

/**
 * Remove block-library styles on the front end; the theme is classic and ships its own CSS.
 */
function rivermere_dequeue_block_css() {
	if ( is_singular() && ! is_front_page() ) {
		return; // Keep block styles for editor-authored content.
	}
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'global-styles' );
	wp_dequeue_style( 'classic-theme-styles' );
}
add_action( 'wp_enqueue_scripts', 'rivermere_dequeue_block_css', 100 );
