<?php
/**
 * Public side: enqueue the tiny tracker (no jQuery, no external requests, no cookies).
 * The script stays dormant until analytics consent exists.
 *
 * @package Rivermere_Insights
 */

defined( 'ABSPATH' ) || exit;

/**
 * Page context for the tracker.
 *
 * @return array
 */
function ri_page_context() {
	$type = 'other';
	if ( is_front_page() ) {
		$type = 'home';
	} elseif ( is_singular( 'rm_vendor' ) ) {
		$type = 'vendor';
	} elseif ( is_singular( 'rm_event' ) ) {
		$type = 'event';
	} elseif ( is_page() ) {
		$type = 'page';
	} elseif ( is_singular() ) {
		$type = 'post';
	} elseif ( is_search() ) {
		$type = 'search';
	} elseif ( is_404() ) {
		$type = '404';
	} elseif ( is_archive() || is_home() ) {
		$type = 'archive';
	}
	$path = (string) wp_parse_url( home_url( add_query_arg( array() ) ), PHP_URL_PATH ); // No query string.
	return array(
		'id'   => is_singular() ? (int) get_queried_object_id() : 0,
		'pt'   => $type,
		'path' => $path ? $path : '/',
	);
}

/**
 * Enqueue.
 */
function ri_enqueue() {
	$s = ri_settings();
	if ( empty( $s['enabled'] ) ) {
		return;
	}
	if ( ! empty( $s['exclude_admins'] ) && current_user_can( 'manage_options' ) ) {
		return;
	}
	$ctx = ri_page_context();
	if ( ri_path_excluded( $ctx['path'] ) ) {
		return;
	}
	wp_enqueue_script( 'rivermere-insights', RI_URL . 'assets/insights.js', array(), RI_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
	$vendor_path = function_exists( 'rivermere_vendor_page_url' ) ? (string) wp_parse_url( rivermere_vendor_page_url(), PHP_URL_PATH ) : '/become-a-vendor/';
	wp_add_inline_script(
		'rivermere-insights',
		'window.rivermereInsights=' . wp_json_encode(
			array(
				'endpoint'   => esc_url_raw( rest_url( 'rivermere-insights/v1/collect' ) ),
				'host'       => wp_parse_url( home_url(), PHP_URL_HOST ),
				'page'       => $ctx,
				'vendorPath' => $vendor_path,
				'track'      => array(
					'pageviews' => (bool) $s['track_pageviews'],
					'cta'       => (bool) $s['track_cta'],
					'contact'   => (bool) $s['track_contact'],
					'social'    => (bool) $s['track_social'],
					'external'  => (bool) $s['track_external'],
				),
			)
		) . ';',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'ri_enqueue' );
