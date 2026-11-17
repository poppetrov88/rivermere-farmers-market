<?php
/**
 * Template functions: section renderer, content queries, logo, misc.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

/**
 * Allow-list of Flexible Content layouts => template part slug.
 * Only layouts listed here can ever be rendered (no user-controlled paths).
 *
 * @return array<string,string>
 */
function rivermere_section_map() {
	return apply_filters(
		'rivermere_section_map',
		array(
			'hero'               => 'template-parts/sections/hero',
			'product_categories' => 'template-parts/sections/product-categories',
			'image_text'         => 'template-parts/sections/image-text',
			'event_banner'       => 'template-parts/sections/event-banner',
			'featured_vendors'   => 'template-parts/sections/featured-vendors',
			'cta'                => 'template-parts/sections/cta',
		)
	);
}

/**
 * Render the Flexible Content page builder for a post.
 *
 * @param int|null $post_id Post ID.
 * @return bool Whether any section rendered.
 */
function rivermere_render_sections( $post_id = null ) {
	if ( ! function_exists( 'have_rows' ) ) {
		return false;
	}
	$post_id = $post_id ? $post_id : get_the_ID();
	$map     = rivermere_section_map();
	$count   = 0;

	if ( have_rows( 'page_sections', $post_id ) ) {
		while ( have_rows( 'page_sections', $post_id ) ) {
			the_row();
			$layout = get_row_layout();
			if ( isset( $map[ $layout ] ) ) {
				$GLOBALS['rivermere_section_index'] = $count;
				get_template_part( $map[ $layout ] );
				++$count;
			}
		}
	}
	return $count > 0;
}

/**
 * Whether the current section is the first on the page (used for LCP image handling).
 *
 * @return bool
 */
function rivermere_is_first_section() {
	return isset( $GLOBALS['rivermere_section_index'] ) && 0 === $GLOBALS['rivermere_section_index'];
}

/**
 * Brand name.
 *
 * @return string
 */
function rivermere_market_name() {
	return (string) rivermere_option( 'market_name', get_bloginfo( 'name' ) );
}

/**
 * Print the logo (ACF → Customizer → bundled SVG). The bundled logo is an SVG: split in
 * mark + text so the market name stays real text.
 *
 * @param string $variant 'default' or 'alt'.
 */
function rivermere_logo( $variant = 'default' ) {
	$image = 'alt' === $variant ? rivermere_option( 'alt_logo', '' ) : '';
	if ( ! $image ) {
		$image = rivermere_option( 'site_logo', '' );
	}
	if ( $image && ! empty( $image['ID'] ) ) {
		echo wp_get_attachment_image( $image['ID'], 'medium', false, array( 'class' => 'brand__img', 'alt' => esc_attr( rivermere_market_name() ), 'loading' => 'eager', 'decoding' => 'async' ) );
		return;
	}
	if ( has_custom_logo() ) {
		echo wp_kses_post( get_custom_logo() );
		return;
	}
	// Fallback: bundled mark + market name as text.
	echo '<img class="brand__mark" src="' . esc_url( rivermere_asset_uri( 'images/logo-mark.svg' ) ) . '" width="60" height="60" alt="">';
	$name  = rivermere_market_name();
	$parts = preg_split( '/\s+/', trim( $name ), 2 );
	echo '<span class="brand__name">' . esc_html( $parts[0] );
	if ( ! empty( $parts[1] ) ) {
		echo '<br>' . esc_html( $parts[1] );
	}
	echo '</span>';
}

/**
 * Format heading text with " | " markers as line breaks.
 *
 * @param string $text Raw text.
 * @return string Escaped HTML.
 */
function rivermere_heading_html( $text ) {
	$parts = array_map( 'trim', explode( '|', (string) $text ) );
	return implode( '<br>', array_map( 'esc_html', $parts ) );
}

/**
 * Next upcoming (or ongoing) event.
 *
 * @param int $limit   Number of events.
 * @param int $exclude Event ID to exclude.
 * @return WP_Post[]
 */
function rivermere_get_upcoming_events( $limit = 1, $exclude = 0 ) {
	$now = current_time( 'Y-m-d H:i:s' );
	return get_posts(
		array(
			'post_type'      => 'rm_event',
			'posts_per_page' => $limit,
			'post_status'    => 'publish',
			'post__not_in'   => $exclude ? array( (int) $exclude ) : array(),
			'meta_key'       => 'event_start', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'orderby'        => 'meta_value',
			'order'          => 'ASC',
			'meta_type'      => 'DATETIME',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => 'event_start',
					'value'   => $now,
					'compare' => '>=',
					'type'    => 'DATETIME',
				),
			),
			'no_found_rows'  => true,
		)
	);
}

/**
 * Featured vendors.
 *
 * @param int $limit Number.
 * @return WP_Post[]
 */
function rivermere_get_featured_vendors( $limit = 4 ) {
	return get_posts(
		array(
			'post_type'      => 'rm_vendor',
			'posts_per_page' => $limit,
			'post_status'    => 'publish',
			'meta_key'       => 'vendor_featured', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
			'no_found_rows'  => true,
		)
	);
}

/**
 * Event date/time bits for display.
 *
 * @param int $event_id Event post ID.
 * @return array{start:?DateTimeImmutable,end:?DateTimeImmutable}
 */
function rivermere_event_times( $event_id ) {
	$tz    = wp_timezone();
	$parse = static function ( $value ) use ( $tz ) {
		if ( ! $value ) {
			return null;
		}
		$date = date_create_immutable_from_format( 'Y-m-d H:i:s', $value, $tz );
		return $date ? $date : null;
	};
	return array(
		'start' => $parse( get_post_meta( $event_id, 'event_start', true ) ),
		'end'   => $parse( get_post_meta( $event_id, 'event_end', true ) ),
	);
}

/**
 * Archive/search wrapper: posts per page tweaks.
 *
 * @param WP_Query $query Query.
 */
function rivermere_pre_get_posts( $query ) {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}
	if ( $query->is_post_type_archive( 'rm_event' ) ) {
		$query->set( 'meta_key', 'event_start' ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		$query->set( 'orderby', 'meta_value' );
		$query->set( 'meta_type', 'DATETIME' );
		$query->set( 'order', 'ASC' );
	}
	if ( $query->is_post_type_archive( 'rm_vendor' ) || $query->is_tax( 'rm_product_category' ) ) {
		$query->set( 'orderby', 'title' );
		$query->set( 'order', 'ASC' );
		$query->set( 'posts_per_page', 12 );
	}
	if ( $query->is_search() ) {
		$query->set( 'post_type', array( 'post', 'page', 'rm_vendor', 'rm_event' ) );
	}
}
add_action( 'pre_get_posts', 'rivermere_pre_get_posts' );

/**
 * Copyright line.
 *
 * @return string
 */
function rivermere_copyright() {
	$text = (string) rivermere_option( 'copyright_text', '' );
	if ( '' === $text ) {
		$text = '© {year} ' . rivermere_market_name();
	}
	return str_replace( '{year}', gmdate( 'Y' ), $text );
}

/**
 * Pagination wrapper.
 */
function rivermere_pagination() {
	the_posts_pagination(
		array(
			'mid_size'  => 1,
			'prev_text' => '<span class="screen-reader-text">' . esc_html__( 'Previous page', 'rivermere-market' ) . '</span><span aria-hidden="true">←</span>',
			'next_text' => '<span class="screen-reader-text">' . esc_html__( 'Next page', 'rivermere-market' ) . '</span><span aria-hidden="true">→</span>',
		)
	);
}

/**
 * Eyebrow text for single templates.
 *
 * @return string
 */
function rivermere_single_eyebrow() {
	$type = get_post_type_object( get_post_type() );
	return ( $type && 'post' !== $type->name ) ? $type->labels->singular_name : get_the_date();
}

/**
 * All event fields for the single event template, with safe defaults.
 *
 * @param int $id Event ID.
 * @return array
 */
function rivermere_event_data( $id ) {
	$times = rivermere_event_times( $id );
	$data  = array(
		'times'         => $times,
		'label'         => (string) rivermere_field( 'event_label', $id, '' ),
		'status'        => (string) rivermere_field( 'event_status', $id, 'scheduled' ),
		'tagline'       => (string) rivermere_field( 'event_tagline', $id, '' ),
		'intro'         => (string) rivermere_field( 'event_intro', $id, '' ),
		'hero'          => rivermere_field( 'event_hero_image', $id, array() ),
		'location'      => (string) rivermere_field( 'event_location', $id, '' ),
		'address'       => (string) rivermere_field( 'event_address', $id, '' ),
		'directions'    => (string) rivermere_field( 'event_directions_url', $id, '' ),
		'admission'     => (string) rivermere_field( 'event_admission', $id, '' ),
		'organizer'     => (string) rivermere_field( 'event_organizer', $id, '' ),
		'email'         => (string) rivermere_field( 'event_contact_email', $id, '' ),
		'phone'         => (string) rivermere_field( 'event_contact_phone', $id, '' ),
		'registration'  => (string) rivermere_field( 'event_registration_url', $id, '' ),
		'accessibility' => (string) rivermere_field( 'event_accessibility', $id, '' ),
		'schedule'      => (array) rivermere_field( 'event_schedule', $id, array() ),
		'faqs'          => (array) rivermere_field( 'event_faqs', $id, array() ),
		'gallery'       => (array) rivermere_field( 'event_gallery', $id, array() ),
		'vendors'       => array_filter( array_map( 'absint', (array) rivermere_field( 'event_vendors', $id, array() ) ) ),
	);
	if ( '' === $data['directions'] ) {
		$query = trim( preg_replace( '/\s+/', ' ',wp_strip_all_tags( str_replace( '<br />', ', ', $data['address'] ) ) ) );
		if ( '' === $query ) {
			$query = $data['location'];
		}
		if ( '' !== $query ) {
			$data['directions'] = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $query );
		}
	}
	return $data;
}

/**
 * Calendar (.ics) download URL, served by the Rivermere Core plugin.
 *
 * @param int $id Event ID.
 * @return string
 */
function rivermere_event_ics_url( $id ) {
	return add_query_arg( 'rm-ics', '1', get_permalink( $id ) );
}
