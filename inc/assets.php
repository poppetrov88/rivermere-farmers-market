<?php
/**
 * Front-end assets, brand colour variables, font preload.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether to serve unminified dev builds (set SCRIPT_DEBUG and run `npm run dev`).
 *
 * @return bool
 */
function rivermere_use_dev_assets() {
	return defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG
		&& file_exists( RIVERMERE_DIR . '/assets/css/main.css' )
		&& file_exists( RIVERMERE_DIR . '/assets/js/main.js' );
}

/**
 * Enqueue compiled CSS and JS.
 */
function rivermere_enqueue_assets() {
	$dev = rivermere_use_dev_assets();
	$css = $dev ? 'assets/css/main.css' : 'assets/css/main.min.css';
	$js  = $dev ? 'assets/js/main.js' : 'assets/js/main.min.js';

	wp_enqueue_style( 'rivermere-main', RIVERMERE_URI . '/' . $css, array(), (string) filemtime( RIVERMERE_DIR . '/' . $css ) );

	wp_enqueue_script(
		'rivermere-main',
		RIVERMERE_URI . '/' . $js,
		array(),
		(string) filemtime( RIVERMERE_DIR . '/' . $js ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	wp_localize_script(
		'rivermere-main',
		'rivermereData',
		array(
			'newsletterEndpoint' => esc_url_raw( rest_url( 'rivermere/v1/newsletter' ) ),
			'restNonce'          => wp_create_nonce( 'wp_rest' ),
			'vendorEndpoint'      => esc_url_raw( rest_url( 'rivermere/v1/vendor-application' ) ),
			'siteName'           => rivermere_market_name(),
			'siteUrl'            => home_url( '/' ),
			'consoleMessage'     => rivermere_toggle( 'ui_console', true ),
			'i18n'               => array(
				'sending'      => __( 'Subscribing…', 'rivermere-market' ),
				'invalidEmail' => __( 'Please enter a valid email address.', 'rivermere-market' ),
				'consent'      => __( 'Please tick the box to confirm you agree to receive our emails.', 'rivermere-market' ),
				'genericError' => __( 'Something went wrong. Please try again later.', 'rivermere-market' ),
				'subscribe'    => __( 'Subscribe', 'rivermere-market' ),
				'vBusiness'     => __( 'Business name is required.', 'rivermere-market' ),
				'vFirst'        => __( 'First name is required.', 'rivermere-market' ),
				'vLast'         => __( 'Last name is required.', 'rivermere-market' ),
				'vProducts'     => __( 'Please tell us what products you sell.', 'rivermere-market' ),
				'vEmailRequired' => __( 'Email address is required.', 'rivermere-market' ),
				'vPhoneRequired' => __( 'Phone number is required.', 'rivermere-market' ),
				'vPhone'        => __( 'Please enter a valid phone number (digits, spaces, + ( ) - only).', 'rivermere-market' ),
				'vUrl'          => __( 'Please enter a valid web address starting with https://', 'rivermere-market' ),
				'vFee'          => __( 'Please acknowledge the application fee.', 'rivermere-market' ),
				'vConsent'      => __( 'Please confirm the privacy consent to submit your application.', 'rivermere-market' ),
				'vSubmit'       => __( 'Submit Vendor Application', 'rivermere-market' ),
				'langSoon'     => __( 'Translations are coming soon. The site is currently available in English.', 'rivermere-market' ),
			),
		)
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'rivermere_enqueue_assets' );

/**
 * Preload the primary font (critical for LCP text) and declare @font-face inline-free via CSS.
 */
function rivermere_preload_font() {
	printf(
		'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n",
		esc_url( rivermere_asset_uri( 'fonts/nunito-var-latin.woff2' ) )
	);
}
add_action( 'wp_head', 'rivermere_preload_font', 1 );

/**
 * Output brand colour CSS variables from Theme Settings (validated hex only).
 */
function rivermere_brand_css() {
	$map = array(
		'color_primary'   => '--c-primary-900',
		'color_secondary' => '--c-secondary-600',
		'color_accent'    => '--c-accent-400',
	);
	$css = '';
	foreach ( $map as $field => $var ) {
		$value = sanitize_hex_color( (string) rivermere_option( $field, '' ) );
		if ( $value ) {
			$css .= $var . ':' . $value . ';';
		}
	}
	$radius = rivermere_option( 'button_style', 'pill' );
	$radii  = array(
		'pill'    => '999px',
		'rounded' => '12px',
		'square'  => '4px',
	);
	if ( isset( $radii[ $radius ] ) && 'pill' !== $radius ) {
		$css .= '--radius-btn:' . $radii[ $radius ] . ';';
	}
	if ( $css ) {
		echo '<style id="rivermere-brand">:root{' . esc_html( $css ) . '}</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- values validated above.
	}
}
add_action( 'wp_head', 'rivermere_brand_css', 5 );

/**
 * Body classes.
 *
 * @param string[] $classes Classes.
 * @return string[]
 */
function rivermere_body_classes( $classes ) {
	if ( rivermere_toggle( 'header_sticky', true ) ) {
		$classes[] = 'has-sticky-header';
	}
	if ( rivermere_toggle( 'ui_scrollbar', true ) ) {
		$classes[] = 'has-custom-scrollbar';
	}
	return $classes;
}
add_filter( 'body_class', 'rivermere_body_classes' );
