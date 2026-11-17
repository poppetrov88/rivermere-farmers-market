<?php
/**
 * Post types and taxonomies: Vendors, Events, Product Categories.
 *
 * @package Rivermere_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register content model.
 */
function rivermere_core_register_post_types() {
	register_post_type(
		'rm_vendor',
		array(
			'labels'       => array(
				'name'               => __( 'Vendors', 'rivermere-core' ),
				'singular_name'      => __( 'Vendor', 'rivermere-core' ),
				'add_new_item'       => __( 'Add New Vendor', 'rivermere-core' ),
				'edit_item'          => __( 'Edit Vendor', 'rivermere-core' ),
				'all_items'          => __( 'All Vendors', 'rivermere-core' ),
				'menu_name'          => __( 'Vendors', 'rivermere-core' ),
				'search_items'       => __( 'Search Vendors', 'rivermere-core' ),
				'not_found'          => __( 'No vendors found.', 'rivermere-core' ),
				'featured_image'     => __( 'Vendor photo', 'rivermere-core' ),
				'set_featured_image' => __( 'Set vendor photo', 'rivermere-core' ),
			),
			'public'       => true,
			'has_archive'  => 'vendors',
			'rewrite'      => array( 'slug' => 'vendors', 'with_front' => false ),
			'menu_icon'    => 'dashicons-store',
			'menu_position'=> 21,
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
			'taxonomies'   => array( 'rm_product_category' ),
		)
	);

	register_post_type(
		'rm_event',
		array(
			'labels'       => array(
				'name'               => __( 'Events', 'rivermere-core' ),
				'singular_name'      => __( 'Event', 'rivermere-core' ),
				'add_new_item'       => __( 'Add New Event', 'rivermere-core' ),
				'edit_item'          => __( 'Edit Event', 'rivermere-core' ),
				'all_items'          => __( 'All Events', 'rivermere-core' ),
				'menu_name'          => __( 'Events', 'rivermere-core' ),
				'search_items'       => __( 'Search Events', 'rivermere-core' ),
				'not_found'          => __( 'No events found.', 'rivermere-core' ),
			),
			'public'       => true,
			'has_archive'  => 'events',
			'rewrite'      => array( 'slug' => 'events', 'with_front' => false ),
			'menu_icon'    => 'dashicons-calendar-alt',
			'menu_position'=> 22,
			'show_in_rest' => true,
			'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		)
	);

	register_taxonomy(
		'rm_product_category',
		array( 'rm_vendor' ),
		array(
			'labels'            => array(
				'name'          => __( 'Product Categories', 'rivermere-core' ),
				'singular_name' => __( 'Product Category', 'rivermere-core' ),
				'add_new_item'  => __( 'Add New Product Category', 'rivermere-core' ),
				'menu_name'     => __( 'Product Categories', 'rivermere-core' ),
			),
			'public'            => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => array( 'slug' => 'products', 'with_front' => false ),
		)
	);
}
add_action( 'init', 'rivermere_core_register_post_types' );

/**
 * Add admin columns for events (start date) and vendors (featured).
 */
function rivermere_core_event_columns( $columns ) {
	$columns['event_start'] = __( 'Starts', 'rivermere-core' );
	return $columns;
}
add_filter( 'manage_rm_event_posts_columns', 'rivermere_core_event_columns' );

/**
 * Render custom column.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function rivermere_core_event_column_content( $column, $post_id ) {
	if ( 'event_start' === $column ) {
		$value = get_post_meta( $post_id, 'event_start', true );
		echo $value ? esc_html( $value ) : '—';
	}
}
add_action( 'manage_rm_event_posts_custom_column', 'rivermere_core_event_column_content', 10, 2 );
