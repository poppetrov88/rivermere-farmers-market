<?php
/**
 * ACF options page: Theme Settings (page registered here; fields are in Local JSON).
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

/**
 * Add the options page.
 */
function rivermere_register_options_page() {
	if ( ! function_exists( 'acf_add_options_page' ) ) {
		return;
	}
	acf_add_options_page(
		array(
			'page_title'      => __( 'Theme Settings', 'rivermere-market' ),
			'menu_title'      => __( 'Theme Settings', 'rivermere-market' ),
			'menu_slug'       => 'rivermere-theme-settings',
			'capability'      => 'manage_options',
			'icon_url'        => 'dashicons-palmtree',
			'position'        => 59,
			'redirect'        => false,
			'update_button'   => __( 'Save settings', 'rivermere-market' ),
			'updated_message' => __( 'Theme settings saved.', 'rivermere-market' ),
		)
	);
}
add_action( 'acf/init', 'rivermere_register_options_page', 5 );

/**
 * The Theme Settings field group lives in acf-json/group_rm_theme_settings.json (editable via ACF → Field Groups).
 */

/**
 * Validate sanity of colour values on save (reject non-hex).
 *
 * @param mixed $valid Validation state.
 * @param mixed $value Value.
 * @return mixed
 */
function rivermere_validate_color( $valid, $value ) {
	if ( $valid && '' !== $value && ! sanitize_hex_color( $value ) ) {
		return __( 'Please enter a valid hex colour such as #053120.', 'rivermere-market' );
	}
	return $valid;
}
foreach ( array( 'color_primary', 'color_secondary', 'color_accent' ) as $rivermere_color_field ) {
	add_filter( 'acf/validate_value/key=field_rm_opt_' . $rivermere_color_field, 'rivermere_validate_color', 10, 2 );
}
unset( $rivermere_color_field );
