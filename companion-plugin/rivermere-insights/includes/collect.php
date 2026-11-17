<?php
/**
 * Ingestion endpoint: POST /wp-json/rivermere-insights/v1/collect
 *
 * Anti-abuse layers (a public nonce alone would not be enough):
 *  1. tracking enabled + type allow-list + per-type settings;
 *  2. valid analytics consent cookie (server-side, same contract as the browser);
 *  3. same-origin check (Origin / Referer / Sec-Fetch-Site);
 *  4. body size limit (2 KB) and max 5 events per request;
 *  5. per-client rate limit (hashed IP, transient; the IP is never stored);
 *  6. page paths resolved from real public posts, free-form paths validated and cardinality-capped.
 *
 * @package Rivermere_Insights
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register route.
 */
function ri_register_route() {
	register_rest_route(
		'rivermere-insights/v1',
		'/collect',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'ri_collect',
			'permission_callback' => 'ri_collect_permission',
		)
	);
}
add_action( 'rest_api_init', 'ri_register_route' );

/**
 * Same-origin check.
 *
 * @return bool
 */
function ri_same_origin() {
	$home = wp_parse_url( home_url(), PHP_URL_HOST );
	$site = isset( $_SERVER['HTTP_SEC_FETCH_SITE'] ) ? sanitize_key( wp_unslash( $_SERVER['HTTP_SEC_FETCH_SITE'] ) ) : '';
	if ( $site && ! in_array( $site, array( 'same-origin', 'same-site' ), true ) ) {
		return false;
	}
	foreach ( array( 'HTTP_ORIGIN', 'HTTP_REFERER' ) as $header ) {
		if ( ! empty( $_SERVER[ $header ] ) ) {
			$host = wp_parse_url( esc_url_raw( wp_unslash( $_SERVER[ $header ] ) ), PHP_URL_HOST );
			return $host === $home;
		}
	}
	return false; // Browsers always send one of them for same-origin POSTs; its absence is suspicious.
}

/**
 * Permission: everything that can be decided before reading the body.
 *
 * @param WP_REST_Request $request Request.
 * @return true|WP_Error
 */
function ri_collect_permission( WP_REST_Request $request ) {
	$s = ri_settings();
	if ( empty( $s['enabled'] ) ) {
		return new WP_Error( 'ri_disabled', 'disabled', array( 'status' => 404 ) );
	}
	if ( strlen( (string) $request->get_body() ) > 2048 ) {
		return new WP_Error( 'ri_too_large', 'too large', array( 'status' => 413 ) );
	}
	if ( ! ri_same_origin() ) {
		return new WP_Error( 'ri_origin', 'forbidden', array( 'status' => 403 ) );
	}
	return true;
}

/**
 * Normalise a client-supplied path: no query string/hash, safe characters, bounded length.
 *
 * @param string $path Raw path.
 * @return string '' when invalid.
 */
function ri_clean_path( $path ) {
	$path = (string) wp_parse_url( (string) $path, PHP_URL_PATH );
	if ( '' === $path || '/' !== $path[0] || strlen( $path ) > 150 || ! preg_match( '#^/[A-Za-z0-9\-_/%.]*$#', $path ) ) {
		return '';
	}
	return $path;
}

/**
 * Is a path on the "exclude pages" list?
 *
 * @param string $path Path.
 * @return bool
 */
function ri_path_excluded( $path ) {
	$list = array_filter( preg_split( '/\r\n|\r|\n/', (string) ri_settings()['exclude_pages'] ) );
	return $path && in_array( trailingslashit( $path ), $list, true );
}

/**
 * Handle a batch of events.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function ri_collect( WP_REST_Request $request ) {
	$ok = new WP_REST_Response( null, 204 );

	// Consent first: without it nothing is processed or stored.
	if ( ! ri_has_consent() ) {
		return $ok;
	}

	// Rate limit per hashed client (60 events/minute).
	$key  = 'ri_rl_' . substr( hash_hmac( 'sha256', isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'x', wp_salt() ), 0, 20 );
	$hits = (int) get_transient( $key );
	if ( $hits >= 60 ) {
		return new WP_REST_Response( array( 'error' => 'rate_limited' ), 429 );
	}
	set_transient( $key, $hits + 1, MINUTE_IN_SECONDS );

	$params = $request->get_json_params();
	$events = isset( $params['events'] ) && is_array( $params['events'] ) ? array_slice( $params['events'], 0, 5 ) : array();
	$types  = ri_event_types();
	$ptypes = array( 'home', 'page', 'post', 'archive', 'search', '404', 'vendor', 'event', 'other' );

	foreach ( $events as $event ) {
		if ( ! is_array( $event ) ) {
			continue;
		}
		$type = isset( $event['t'] ) ? sanitize_key( $event['t'] ) : '';
		// Conversions are server-side only; the browser may never claim them.
		if ( ! isset( $types[ $type ] ) || 'conversion' === $types[ $type ]['kind'] || ! ri_type_enabled( $type ) ) {
			continue;
		}
		$label = isset( $event['k'] ) ? strtolower( preg_replace( '/[^a-zA-Z0-9\-_.:]/', '', (string) $event['k'] ) ) : '';
		$label = substr( $label, 0, 60 );
		// Privacy guard: contact events carry only a fixed label, and anything that looks like a phone
		// number or an email address is never stored, whatever a client sends.
		$fixed = array( 'tel' => 'phone', 'email' => 'email', 'whatsapp' => 'whatsapp' );
		if ( isset( $fixed[ $type ] ) ) {
			$label = $fixed[ $type ];
		} elseif ( preg_match( '/\d{6,}/', $label ) || preg_match( '/@|\bat[a-z0-9-]+\.(com|net|org|io|co)\b/', $label ) || preg_match( '/[a-z0-9]+\.[a-z0-9]+\.(com|net|org|io|co)/', $label ) ) {
			$label = '(redacted)';
		}

		$id    = isset( $event['id'] ) ? absint( $event['id'] ) : 0;
		$path  = '';
		$ptype = isset( $event['pt'] ) && in_array( $event['pt'], $ptypes, true ) ? $event['pt'] : 'other';
		if ( $id ) {
			$post = get_post( $id );
			if ( $post && 'publish' === $post->post_status && is_post_type_viewable( $post->post_type ) ) {
				$path = ri_clean_path( get_permalink( $post ) );
			} else {
				$id = 0;
			}
		}
		if ( '' === $path ) {
			$path = ri_clean_path( isset( $event['p'] ) ? (string) $event['p'] : '' );
		}
		if ( ri_path_excluded( $path ) ) {
			continue;
		}
		ri_record( $type, $label, $path, $id, $ptype );
	}
	return $ok;
}
