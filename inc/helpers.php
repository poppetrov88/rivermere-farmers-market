<?php
/**
 * Generic helpers: icons, ACF wrappers, buttons, images.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

/**
 * Icon library (24x24 stroke icons). Values are inner SVG markup (trusted, static).
 *
 * @return array<string,string>
 */
function rivermere_icons() {
	static $icons = null;
	if ( null !== $icons ) {
		return $icons;
	}
	$icons = array(
		'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'close'    => '<path d="M6 6l12 12M18 6 6 18"/>',
		'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'leaf'     => '<path d="M5 20c0-9 5-15 15-16 0 10-5 15-14 15"/><path d="M5 20c2-6 6-9 10-11"/>',
		'people'   => '<circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.4"/><path d="M3 19c0-3.3 2.7-6 6-6s6 2.7 6 6M15.5 14.2c3 0 5.5 1.9 5.5 4.8"/>',
		'heart'    => '<path d="M12 20s-8-4.7-8-10.5C4 6.5 6 5 8 5c1.7 0 3 .9 4 2.4C13 5.9 14.300 5 16 5c2 0 4 1.500 4 4.500C20 15.300 12 20 12 20Z"/>',
		'apple'    => '<path d="M12 8c-2-1.500-6-1-6 4 0 4 2.500 8 5 8 .6 0 .9-.3 1-.3s.4.3 1 .3c2.500 0 5-4 5-8 0-5-4-5.500-6-4Z"/><path d="M12 8c0-2 1-3.500 3-4"/>',
		'croissant'=> '<path d="M3 15c0-4 3-8 9-8s9 4 9 8c0 1.500-1 2-2 2-1 0-1.500-1-2.500-1s-1.500 1-2.500 1h-4c-1 0-1.500-1-2.500-1S6 17 5 17c-1 0-2-.5-2-2Z"/><path d="M9 8l1.500 6M15 8l-1.500 6M12 7v6"/>',
		'cheese'   => '<path d="M3 9l9-4 9 4v9H3Z"/><path d="M3 9l9 3 9-3"/><circle cx="8" cy="15" r="1.200"/><circle cx="14" cy="16" r="1.600"/>',
		'plant'    => '<path d="M12 21v-8"/><path d="M12 13c0-4-2-6-7-6 0 4 2 6 7 6ZM12 11c0-4 2-6 7-6 0 4-2 6-7 6Z"/><path d="M9 21h6"/>',
		'basket'   => '<path d="M3 10h18l-2 10H5Z"/><path d="M8 10l3-6M16 10l-3-6M9 14v3M12 14v3M15 14v3"/>',
		'music'    => '<path d="M9 18V5l10-2v13"/><circle cx="6.500" cy="18" r="2.500"/><circle cx="16.500" cy="16" r="2.500"/>',
		'calendar' => '<rect x="3.500" y="5" width="17" height="15" rx="2"/><path d="M3.500 10h17M8 3v4M16 3v4"/>',
		'pin'      => '<path d="M12 21s7-6.200 7-11.500a7 7 0 0 0-14 0C5 14.800 12 21 12 21Z"/><circle cx="12" cy="9.500" r="2.500"/>',
		'clock'    => '<circle cx="12" cy="12" r="8.500"/><path d="M12 7.500V12l3 2"/>',
		'mail'     => '<rect x="3" y="5.500" width="18" height="13" rx="2"/><path d="m4 7 8 6 8-6"/>',
		'phone'    => '<path d="M5 4h4l2 5-2.500 1.500a11 11 0 0 0 5 5L15 13l5 2v4a1 1 0 0 1-1 1C10.700 20 4 13.300 4 5a1 1 0 0 1 1-1Z"/>',
		'facebook' => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8.500c0-.3.200-.5.500-.5Z"/>',
		'instagram'=> '<rect x="4" y="4" width="16" height="16" rx="4.500"/><circle cx="12" cy="12" r="3.500"/><circle cx="17" cy="7" r=".6" fill="currentColor"/>',
		'x'        => '<path d="M4 4l16 16M20 4 4 20"/>',
		'youtube'  => '<rect x="3" y="6" width="18" height="12" rx="3.500"/><path d="m10 9.500 5 2.500-5 2.500Z"/>',
		'tiktok'   => '<path d="M14 4v10.500a3.500 3.500 0 1 1-3.500-3.500M14 4c.500 2.500 2 4 5 4"/>',
		'external' => '<path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
	);
	return $icons;
}

/**
 * Icon names usable in ACF select fields.
 *
 * @return array<string,string>
 */
function rivermere_icon_choices() {
	$choices = array();
	foreach ( array_keys( rivermere_icons() ) as $name ) {
		$choices[ $name ] = ucfirst( $name );
	}
	return $choices;
}

/**
 * Render an inline SVG icon (decorative).
 *
 * @param string $name  Icon key.
 * @param string $class Extra classes.
 * @return string
 */
function rivermere_icon( $name, $class = '' ) {
	$icons = rivermere_icons();
	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}
	$classes = trim( 'icon icon--' . sanitize_html_class( $name ) . ' ' . $class );
	return sprintf(
		'<svg class="%s" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%s</svg>',
		esc_attr( $classes ),
		$icons[ $name ] // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted SVG.
	);
}

/**
 * Safe ACF get_field wrapper.
 *
 * @param string $selector Field name.
 * @param mixed  $post_id  Post ID or 'option'.
 * @param mixed  $default  Fallback when empty.
 * @return mixed
 */
function rivermere_field( $selector, $post_id = false, $default = '' ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}
	$value = get_field( $selector, $post_id );
	return ( null === $value || '' === $value || false === $value || array() === $value ) ? $default : $value;
}

/**
 * Theme Settings option accessor (ACF options page).
 *
 * @param string $name    Field name.
 * @param mixed  $default Fallback.
 * @return mixed
 */
function rivermere_option( $name, $default = '' ) {
	return rivermere_field( $name, 'option', $default );
}

/**
 * Sub-field accessor inside Flexible Content layouts.
 *
 * @param string $name    Sub field name.
 * @param mixed  $default Fallback.
 * @return mixed
 */
function rivermere_sub( $name, $default = '' ) {
	if ( ! function_exists( 'get_sub_field' ) ) {
		return $default;
	}
	$value = get_sub_field( $name );
	return ( null === $value || '' === $value || false === $value || array() === $value ) ? $default : $value;
}

/**
 * Print an ACF link array as a button/link element.
 *
 * @param array|null $link    ACF link array (url,title,target).
 * @param string     $variant primary|secondary|outline|accent|text.
 * @param array      $args    Options: icon (string|false), class, label.
 * @return string HTML (escaped).
 */
function rivermere_button( $link, $variant = 'primary', $args = array() ) {
	if ( empty( $link['url'] ) ) {
		return '';
	}
	$args  = wp_parse_args(
		$args,
		array(
			'icon'  => 'arrow',
			'class' => '',
			'label' => '',
		)
	);
	$label = '' !== $args['label'] ? $args['label'] : ( $link['title'] ?? '' );
	if ( '' === $label ) {
		return '';
	}
	$target = ! empty( $link['target'] ) ? ' target="' . esc_attr( $link['target'] ) . '" rel="noopener"' : '';
	$class  = trim( 'btn btn--' . sanitize_html_class( $variant ) . ' ' . $args['class'] );

	return sprintf(
		'<a class="%1$s" href="%2$s"%3$s><span>%4$s</span>%5$s</a>',
		esc_attr( $class ),
		esc_url( $link['url'] ),
		$target, // Already escaped above.
		esc_html( $label ),
		$args['icon'] ? rivermere_icon( $args['icon'], 'btn__icon' ) : ''
	);
}

/**
 * Render an attachment image with responsive attributes.
 *
 * @param int|array|null $image Attachment ID or ACF image array.
 * @param string         $size  Image size.
 * @param array          $attr  Extra attributes.
 * @return string
 */
function rivermere_image( $image, $size = 'large', $attr = array() ) {
	$id = is_array( $image ) ? ( $image['ID'] ?? 0 ) : absint( $image );
	if ( ! $id ) {
		return '';
	}
	return wp_get_attachment_image( $id, $size, false, $attr );
}

/**
 * Theme asset URL (images, icons).
 *
 * @param string $path Relative path within assets/.
 * @return string
 */
function rivermere_asset_uri( $path ) {
	return RIVERMERE_URI . '/assets/' . ltrim( $path, '/' );
}

/**
 * Social networks supported in settings.
 *
 * @return array<string,string>
 */
function rivermere_social_networks() {
	return array(
		'facebook'  => 'Facebook',
		'instagram' => 'Instagram',
		'x'         => 'X (Twitter)',
		'youtube'   => 'YouTube',
		'tiktok'    => 'TikTok',
	);
}
