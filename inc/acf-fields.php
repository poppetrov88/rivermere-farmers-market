<?php
/**
 * ACF Pro field groups: Page Builder (Flexible Content) and taxonomy icon field.
 *
 * Field groups are registered in PHP with stable keys (no Local JSON for these groups,
 * which avoids duplicate registrations). See README "Editing ACF fields".
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

/**
 * Build a field definition with a stable key.
 *
 * @param string $scope Key scope (e.g. "hero").
 * @param string $type  ACF field type.
 * @param string $name  Field name.
 * @param string $label Field label.
 * @param array  $extra Extra settings.
 * @return array
 */
function rivermere_acf_field( $scope, $type, $name, $label, $extra = array() ) {
	return array_merge(
		array(
			'key'   => 'field_rm_' . $scope . '_' . $name,
			'label' => $label,
			'name'  => $name,
			'type'  => $type,
		),
		$extra
	);
}

/**
 * Half-width wrapper helper.
 *
 * @param int $width Width percent.
 * @return array
 */
function rivermere_acf_w( $width ) {
	return array( 'wrapper' => array( 'width' => (string) $width ) );
}

/**
 * Register the page builder group.
 */
function rivermere_register_acf_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$f    = 'rivermere_acf_field';
	$w    = 'rivermere_acf_w';
	$icon = array(
		'type'          => 'select',
		'choices'       => rivermere_icon_choices(),
		'allow_null'    => 0,
		'return_format' => 'value',
	);

	$layouts = array();

	// Hero.
	$layouts['layout_rm_hero'] = array(
		'key'        => 'layout_rm_hero',
		'name'       => 'hero',
		'label'      => __( 'Hero', 'rivermere-market' ),
		'display'    => 'block',
		'sub_fields' => array(
			$f( 'hero', 'text', 'eyebrow', 'Eyebrow text', array( 'instructions' => 'Small uppercase line above the heading. Separate phrases with "•".' ) ),
			$f( 'hero', 'text', 'heading', 'Heading', array( 'required' => 1, 'instructions' => 'Main page heading (H1). Type " | " where you want a line break.' ) ),
			$f( 'hero', 'textarea', 'description', 'Description', array( 'rows' => 3, 'new_lines' => '' ) ),
			$f( 'hero', 'link', 'primary_cta', 'Primary button', array_merge( $w( 50 ), array( 'return_format' => 'array' ) ) ),
			$f( 'hero', 'link', 'secondary_cta', 'Secondary button', array_merge( $w( 50 ), array( 'return_format' => 'array' ) ) ),
			$f( 'hero', 'image', 'image', 'Hero image', array(
				'return_format' => 'array',
				'preview_size'  => 'medium',
				'instructions'  => 'Landscape photo, at least 1200px wide. Appears on the right and is loaded eagerly (LCP).',
			) ),
			$f( 'hero', 'text', 'image_alt', 'Image alt text', array( 'instructions' => 'Describe the photo. Leave blank only if it is purely decorative.' ) ),
			$f( 'hero', 'repeater', 'highlights', 'Highlights', array(
				'layout'       => 'table',
				'min'          => 0,
				'max'          => 4,
				'button_label' => 'Add highlight',
				'sub_fields'   => array(
					array_merge( $f( 'hero_hl', 'select', 'icon', 'Icon', $icon ), $w( 30 ) ),
					array_merge( $f( 'hero_hl', 'text', 'label', 'Label' ), $w( 70 ) ),
				),
			) ),
		),
	);

	// Product categories.
	$layouts['layout_rm_categories'] = array(
		'key'        => 'layout_rm_categories',
		'name'       => 'product_categories',
		'label'      => __( 'Product Categories', 'rivermere-market' ),
		'display'    => 'block',
		'sub_fields' => array(
			$f( 'cats', 'text', 'heading', 'Heading', array( 'default_value' => 'Explore What’s at the Market' ) ),
			$f( 'cats', 'taxonomy', 'categories', 'Categories to show', array(
				'taxonomy'      => 'rm_product_category',
				'field_type'    => 'multi_select',
				'return_format' => 'object',
				'add_term'      => 0,
				'instructions'  => 'Pick and order the categories. Manage them (and their icons) under Vendors → Product Categories. Leave empty to show all.',
			) ),
		),
	);

	// Image + text (community).
	$layouts['layout_rm_image_text'] = array(
		'key'        => 'layout_rm_image_text',
		'name'       => 'image_text',
		'label'      => __( 'Image and Text', 'rivermere-market' ),
		'display'    => 'block',
		'sub_fields' => array(
			$f( 'imgtxt', 'text', 'eyebrow', 'Eyebrow text' ),
			$f( 'imgtxt', 'text', 'heading', 'Heading', array( 'required' => 1 ) ),
			$f( 'imgtxt', 'textarea', 'text', 'Text', array( 'rows' => 3, 'new_lines' => '' ) ),
			$f( 'imgtxt', 'repeater', 'details', 'Detail list', array(
				'layout'       => 'table',
				'max'          => 6,
				'button_label' => 'Add detail',
				'sub_fields'   => array(
					array_merge( $f( 'imgtxt_d', 'select', 'icon', 'Icon', $icon ), $w( 30 ) ),
					array_merge( $f( 'imgtxt_d', 'text', 'text', 'Text' ), $w( 70 ) ),
				),
			) ),
			$f( 'imgtxt', 'link', 'cta', 'Button', array( 'return_format' => 'array' ) ),
			$f( 'imgtxt', 'image', 'image', 'Image', array( 'return_format' => 'array', 'preview_size' => 'medium' ) ),
			$f( 'imgtxt', 'text', 'image_alt', 'Image alt text', array_merge( $w( 50 ) ) ),
			$f( 'imgtxt', 'button_group', 'image_position', 'Image position', array_merge( $w( 50 ), array(
				'choices'       => array(
					'left'  => 'Left',
					'right' => 'Right',
				),
				'default_value' => 'left',
			) ) ),
		),
	);

	// Event banner.
	$layouts['layout_rm_event_banner'] = array(
		'key'        => 'layout_rm_event_banner',
		'name'       => 'event_banner',
		'label'      => __( 'Upcoming Event Banner', 'rivermere-market' ),
		'display'    => 'block',
		'sub_fields' => array(
			$f( 'evb', 'text', 'label', 'Label', array( 'default_value' => 'Upcoming Event' ) ),
			$f( 'evb', 'button_group', 'source', 'Event source', array(
				'choices'       => array(
					'next'   => 'Next upcoming event (automatic)',
					'manual' => 'Choose an event',
				),
				'default_value' => 'next',
			) ),
			$f( 'evb', 'post_object', 'event', 'Event', array(
				'post_type'     => array( 'rm_event' ),
				'return_format' => 'id',
				'ui'            => 1,
				'conditional_logic' => array(
					array(
						array(
							'field'    => 'field_rm_evb_source',
							'operator' => '==',
							'value'    => 'manual',
						),
					),
				),
			) ),
			$f( 'evb', 'text', 'button_label', 'Button label', array( 'default_value' => 'Event Details' ) ),
		),
	);

	// Featured vendors.
	$layouts['layout_rm_vendors'] = array(
		'key'        => 'layout_rm_vendors',
		'name'       => 'featured_vendors',
		'label'      => __( 'Featured Vendors', 'rivermere-market' ),
		'display'    => 'block',
		'sub_fields' => array(
			$f( 'vend', 'text', 'heading', 'Heading', array( 'default_value' => 'Meet Our Featured Vendors' ) ),
			$f( 'vend', 'button_group', 'source', 'Vendor source', array(
				'choices'       => array(
					'featured' => 'Vendors marked "Featured"',
					'manual'   => 'Choose vendors',
				),
				'default_value' => 'featured',
			) ),
			$f( 'vend', 'relationship', 'vendors', 'Vendors', array(
				'post_type'     => array( 'rm_vendor' ),
				'return_format' => 'id',
				'max'           => 8,
				'conditional_logic' => array(
					array(
						array(
							'field'    => 'field_rm_vend_source',
							'operator' => '==',
							'value'    => 'manual',
						),
					),
				),
			) ),
			$f( 'vend', 'number', 'count', 'Number of vendors', array( 'default_value' => 4, 'min' => 1, 'max' => 8, 'instructions' => 'Used for the automatic "Featured" source.' ) ),
			$f( 'vend', 'link', 'all_link', 'Optional "All vendors" link', array( 'return_format' => 'array' ) ),
		),
	);

	// Generic CTA.
	$layouts['layout_rm_cta'] = array(
		'key'        => 'layout_rm_cta',
		'name'       => 'cta',
		'label'      => __( 'Call to Action', 'rivermere-market' ),
		'display'    => 'block',
		'sub_fields' => array(
			$f( 'cta', 'text', 'heading', 'Heading', array( 'required' => 1 ) ),
			$f( 'cta', 'textarea', 'text', 'Text', array( 'rows' => 2, 'new_lines' => '' ) ),
			$f( 'cta', 'link', 'link', 'Button', array( 'return_format' => 'array' ) ),
		),
	);

	acf_add_local_field_group(
		array(
			'key'                   => 'group_rm_page_builder',
			'title'                 => __( 'Page Builder', 'rivermere-market' ),
			'fields'                => array(
				array(
					'key'          => 'field_rm_page_sections',
					'label'        => __( 'Page sections', 'rivermere-market' ),
					'name'         => 'page_sections',
					'type'         => 'flexible_content',
					'instructions' => __( 'Add, drag to reorder, or duplicate sections. The newsletter band is configured in Theme Settings → Footer.', 'rivermere-market' ),
					'layouts'      => $layouts,
					'button_label' => __( 'Add section', 'rivermere-market' ),
				),
			),
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'page',
					),
				),
			),
			'menu_order'            => 0,
			'position'              => 'normal',
			'style'                 => 'default',
			'hide_on_screen'        => array(),
			'show_in_rest'          => 0,
		)
	);

	// Icon for product categories (term field).
	acf_add_local_field_group(
		array(
			'key'      => 'group_rm_category_icon',
			'title'    => __( 'Category Display', 'rivermere-market' ),
			'fields'   => array(
				$f( 'term', 'select', 'category_icon', 'Icon', array_merge( $icon, array( 'default_value' => 'leaf' ) ) ),
			),
			'location' => array(
				array(
					array(
						'param'    => 'taxonomy',
						'operator' => '==',
						'value'    => 'rm_product_category',
					),
				),
			),
		)
	);
}
add_action( 'acf/init', 'rivermere_register_acf_fields' );

/**
 * Keep the editor tidy: hide the content editor on pages that use the page builder.
 */
function rivermere_maybe_remove_editor() {
	$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $post_id && (int) get_option( 'page_on_front' ) === $post_id ) {
		remove_post_type_support( 'page', 'editor' );
	}
}
add_action( 'admin_init', 'rivermere_maybe_remove_editor' );
