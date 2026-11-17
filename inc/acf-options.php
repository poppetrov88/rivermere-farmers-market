<?php
/**
 * ACF options page: Theme Settings.
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
 * Register the Theme Settings field group.
 */
function rivermere_register_options_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}
	$f   = 'rivermere_acf_field';
	$w   = 'rivermere_acf_w';
	$tab = static function ( $key, $label ) {
		return array(
			'key'       => 'field_rm_tab_' . $key,
			'label'     => $label,
			'name'      => '',
			'type'      => 'tab',
			'placement' => 'top',
		);
	};
	$img = array(
		'return_format' => 'array',
		'preview_size'  => 'medium',
	);
	$hex = static function ( $default ) {
		return array(
			'default_value' => $default,
			'enable_opacity' => 0,
			'return_format'  => 'string',
		);
	};

	acf_add_local_field_group(
		array(
			'key'      => 'group_rm_theme_settings',
			'title'    => __( 'Theme Settings', 'rivermere-market' ),
			'fields'   => array(
				// General.
				$tab( 'general', 'General' ),
				$f( 'opt', 'image', 'site_logo', 'Site logo', array_merge( $img, array( 'instructions' => 'Used in the header. If empty, the WordPress Customizer logo, then a built-in logo, is used. Tip: set the browser Site Icon (favicon) under Appearance → Customize → Site Identity.' ) ) ),
				$f( 'opt', 'image', 'alt_logo', 'Alternate logo (light version)', array_merge( $img, array( 'instructions' => 'Optional version for dark backgrounds, used in the footer.' ) ) ),
				$f( 'opt', 'text', 'market_name', 'Market name', array( 'instructions' => 'Defaults to the Site Title.' ) ),
				$f( 'opt', 'email', 'contact_email', 'Contact email', $w( 50 ) ),
				$f( 'opt', 'text', 'phone', 'Phone number', $w( 50 ) ),
				$f( 'opt', 'textarea', 'address', 'Address', array( 'rows' => 3, 'new_lines' => 'br', 'instructions' => 'Street, town, postcode — one line each.' ) ),
				$f( 'opt', 'url', 'maps_url', 'Google Maps / directions URL' ),
				$f( 'opt', 'text', 'market_days', 'Market days (short)', array( 'instructions' => 'e.g. "Every Sunday, 9 AM – 1 PM". Shown in highlights and the footer.' ) ),
				$f( 'opt', 'repeater', 'opening_hours', 'Opening hours', array(
					'layout'       => 'table',
					'button_label' => 'Add hours',
					'instructions' => 'Used in the footer and for structured data.',
					'sub_fields'   => array(
						array_merge( $f( 'opt_oh', 'checkbox', 'days', 'Days', array(
							'choices' => array(
								'Monday'    => 'Mon',
								'Tuesday'   => 'Tue',
								'Wednesday' => 'Wed',
								'Thursday'  => 'Thu',
								'Friday'    => 'Fri',
								'Saturday'  => 'Sat',
								'Sunday'    => 'Sun',
							),
							'layout'  => 'horizontal',
						) ), $w( 60 ) ),
						array_merge( $f( 'opt_oh', 'time_picker', 'opens', 'Opens', array( 'display_format' => 'H:i', 'return_format' => 'H:i' ) ), $w( 20 ) ),
						array_merge( $f( 'opt_oh', 'time_picker', 'closes', 'Closes', array( 'display_format' => 'H:i', 'return_format' => 'H:i' ) ), $w( 20 ) ),
					),
				) ),
				$f( 'opt', 'text', 'whatsapp_number', 'WhatsApp number', array_merge( $w( 50 ), array( 'instructions' => 'International format, e.g. +44 7700 900123. Used for the wa.me link. Leave empty to hide WhatsApp.' ) ) ),
				$f( 'opt', 'url', 'facebook_url', 'Facebook URL', array_merge( $w( 33 ), array( 'instructions' => 'Leave empty until the profile exists; the icon then shows a “Coming soon” message.' ) ) ),
				$f( 'opt', 'url', 'instagram_url', 'Instagram URL', $w( 33 ) ),
				$f( 'opt', 'url', 'linkedin_url', 'LinkedIn URL', $w( 34 ) ),

				// Header.
				$tab( 'header', 'Header' ),
				$f( 'opt', 'true_false', 'header_cta_show', 'Show header button', array( 'ui' => 1, 'default_value' => 1, 'wrapper' => array( 'width' => '50' ) ) ),
				$f( 'opt', 'link', 'header_cta', 'Header button', array( 'return_format' => 'array', 'instructions' => 'e.g. "Become a Vendor". Leave empty to hide.', 'wrapper' => array( 'width' => '50' ) ) ),
				$f( 'opt', 'true_false', 'header_social', 'Show social icons (utility bar)', array( 'ui' => 1, 'default_value' => 1, 'wrapper' => array( 'width' => '50' ) ) ),
				$f( 'opt', 'true_false', 'header_language', 'Show language selector (UI only)', array( 'ui' => 1, 'default_value' => 1, 'wrapper' => array( 'width' => '50' ) ) ),
				$f( 'opt', 'true_false', 'header_sticky', 'Sticky header', array( 'ui' => 1, 'default_value' => 1, 'wrapper' => array( 'width' => '50' ) ) ),
				$f( 'opt', 'true_false', 'header_search', 'Show search button', array( 'ui' => 1, 'default_value' => 1, 'wrapper' => array( 'width' => '50' ) ) ),
				$f( 'opt', 'true_false', 'announcement_enabled', 'Show announcement bar', array( 'ui' => 1, 'default_value' => 0 ) ),
				$f( 'opt', 'text', 'announcement_text', 'Announcement text', array( 'conditional_logic' => array( array( array( 'field' => 'field_rm_opt_announcement_enabled', 'operator' => '==', 'value' => '1' ) ) ) ) ),
				$f( 'opt', 'link', 'announcement_link', 'Announcement link', array( 'return_format' => 'array', 'conditional_logic' => array( array( array( 'field' => 'field_rm_opt_announcement_enabled', 'operator' => '==', 'value' => '1' ) ) ) ) ),

				// Footer.
				$tab( 'footer', 'Footer' ),
				$f( 'opt', 'textarea', 'footer_description', 'Footer description', array( 'rows' => 3, 'new_lines' => '' ) ),
				$f( 'opt', 'link', 'footer_cta', 'Footer button' , array( 'return_format' => 'array' ) ),
				$f( 'opt', 'true_false', 'newsletter_enabled', 'Show newsletter band', array( 'ui' => 1, 'default_value' => 1 ) ),
				$f( 'opt', 'text', 'newsletter_heading', 'Newsletter heading', array( 'default_value' => 'Stay in the Loop' ) ),
				$f( 'opt', 'textarea', 'newsletter_description', 'Newsletter description', array( 'rows' => 2, 'new_lines' => '', 'default_value' => 'Get market updates, special events, and seasonal highlights delivered to your inbox.' ) ),
				$f( 'opt', 'text', 'newsletter_consent', 'Newsletter consent text', array( 'default_value' => 'I agree to receive market updates by email and can unsubscribe at any time.', 'instructions' => 'Shown beside the consent checkbox.' ) ),
				$f( 'opt', 'page_link', 'privacy_page', 'Privacy policy page', array( 'post_type' => array( 'page' ), 'allow_null' => 1 ) ),
				$f( 'opt', 'text', 'copyright_text', 'Copyright text', array( 'instructions' => 'Use {year} for the current year. Defaults to "© {year} Site title".' ) ),

				// Floating contact.
				$tab( 'floating', 'Floating Contact' ),
				$f( 'opt', 'message', 'floating_note', 'About', array( 'message' => 'Uses the telephone, email and WhatsApp numbers from the General tab. A method is hidden automatically when its detail is empty.', 'new_lines' => 'wpautop' ) ),
				$f( 'opt', 'true_false', 'floating_enabled', 'Enable floating contact widget', array( 'ui' => 1, 'default_value' => 1 ) ),
				$f( 'opt', 'true_false', 'floating_phone', 'Show telephone', array( 'ui' => 1, 'default_value' => 1, 'wrapper' => array( 'width' => '33' ) ) ),
				$f( 'opt', 'true_false', 'floating_email', 'Show email', array( 'ui' => 1, 'default_value' => 1, 'wrapper' => array( 'width' => '33' ) ) ),
				$f( 'opt', 'true_false', 'floating_whatsapp', 'Show WhatsApp', array( 'ui' => 1, 'default_value' => 1, 'wrapper' => array( 'width' => '34' ) ) ),
				$f( 'opt', 'button_group', 'floating_variant', 'Colour variant', array( 'choices' => array( 'green' => 'Green', 'yellow' => 'Yellow', 'dark' => 'Dark' ), 'default_value' => 'green', 'wrapper' => array( 'width' => '50' ) ) ),
				$f( 'opt', 'button_group', 'floating_position', 'Vertical position', array( 'choices' => array( 'middle' => 'Middle', 'bottom' => 'Lower' ), 'default_value' => 'middle', 'wrapper' => array( 'width' => '50' ) ) ),

				// UI.
				$tab( 'ui', 'UI Features' ),
				$f( 'opt', 'true_false', 'ui_scrollbar', 'Enable custom green scrollbar', array( 'ui' => 1, 'default_value' => 1 ) ),
				$f( 'opt', 'true_false', 'ui_progress', 'Enable scroll progress bar', array( 'ui' => 1, 'default_value' => 1 ) ),
				$f( 'opt', 'true_false', 'ui_back_to_top', 'Enable footer back-to-top button', array( 'ui' => 1, 'default_value' => 1 ) ),
				$f( 'opt', 'true_false', 'ui_console', 'Enable browser console welcome message', array( 'ui' => 1, 'default_value' => 1 ) ),

				// Appearance.
				$tab( 'appearance', 'Appearance' ),
				$f( 'opt', 'color_picker', 'color_primary', 'Primary (deep green)', array_merge( $hex( '#053120' ), $w( 33 ) ) ),
				$f( 'opt', 'color_picker', 'color_secondary', 'Secondary (action green)', array_merge( $hex( '#1d6806' ), $w( 33 ) ) ),
				$f( 'opt', 'color_picker', 'color_accent', 'Accent (yellow)', array_merge( $hex( '#fdd13f' ), $w( 34 ) ) ),
				$f( 'opt', 'button_group', 'button_style', 'Button style', array(
					'choices'       => array(
						'pill'    => 'Pill',
						'rounded' => 'Rounded',
						'square'  => 'Square',
					),
					'default_value' => 'pill',
				) ),
			),
			'location' => array(
				array(
					array(
						'param'    => 'options_page',
						'operator' => '==',
						'value'    => 'rivermere-theme-settings',
					),
				),
			),
		)
	);
}
add_action( 'acf/init', 'rivermere_register_options_fields' );

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
