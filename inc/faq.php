<?php
/**
 * Shared FAQ system: ONE resolver and ONE component for every FAQ location.
 *
 * Resolution (first non-empty wins):
 *  - Vendors archive / product category  -> Theme Settings: Global Vendors FAQ
 *  - Single vendor                       -> vendor_faqs (per vendor), else Global Vendors FAQ
 *  - Events archive                      -> Theme Settings: Global Events FAQ
 *  - Single event                        -> event_faqs (per event), else Global Events FAQ
 *  - About Us template                   -> ap_faq_items on the page
 *  - Become a Vendor template            -> vp_faq_items on the page
 *
 * The same resolved items feed the visible accordion AND the FAQPage JSON-LD (via the
 * `rivermere_current_faqs` filter, consumed by Rivermere Core), so markup can never drift
 * from what visitors can read.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

/**
 * Normalise repeater rows: trim, drop incomplete rows, remove duplicate questions.
 *
 * @param mixed $rows Repeater rows (question/answer).
 * @return array<int,array{q:string,a:string}>
 */
function rivermere_clean_faqs( $rows ) {
	$out  = array();
	$seen = array();
	foreach ( (array) $rows as $row ) {
		$q = trim( wp_strip_all_tags( (string) ( $row['question'] ?? '' ) ) );
		$a = trim( (string) ( $row['answer'] ?? '' ) );
		if ( '' === $q || '' === trim( wp_strip_all_tags( $a ) ) ) {
			continue;
		}
		$key = strtolower( preg_replace( '/\s+/', ' ', $q ) );
		if ( isset( $seen[ $key ] ) ) {
			continue;
		}
		$seen[ $key ] = true;
		$out[]        = array(
			'q' => $q,
			'a' => $a,
		);
	}
	return $out;
}

/**
 * Resolve FAQ data for the current request.
 *
 * @return array{title:string,intro:string,items:array,context:string}|null
 */
function rivermere_resolve_faq() {
	static $cache = array();
	$cache_key    = get_queried_object_id() . '|' . ( is_post_type_archive() ? get_query_var( 'post_type' ) : '' );
	if ( array_key_exists( $cache_key, $cache ) ) {
		return $cache[ $cache_key ];
	}

	$context = '';
	$title   = '';
	$intro   = '';
	$items   = array();

	if ( is_post_type_archive( 'rm_vendor' ) || is_tax( 'rm_product_category' ) ) {
		$context = 'vendors_archive';
		$items   = rivermere_clean_faqs( rivermere_option( 'faq_vendors_items', array() ) );
		$title   = (string) rivermere_option( 'faq_vendors_title', '' );
		$intro   = (string) rivermere_option( 'faq_vendors_intro', '' );
	} elseif ( is_singular( 'rm_vendor' ) ) {
		$context = 'vendor';
		$items   = rivermere_clean_faqs( rivermere_field( 'vendor_faqs', get_queried_object_id(), array() ) );
		if ( ! $items ) {
			$items = rivermere_clean_faqs( rivermere_option( 'faq_vendors_items', array() ) );
			$title = (string) rivermere_option( 'faq_vendors_title', '' );
			$intro = (string) rivermere_option( 'faq_vendors_intro', '' );
		}
	} elseif ( is_post_type_archive( 'rm_event' ) ) {
		$context = 'events_archive';
		$items   = rivermere_clean_faqs( rivermere_option( 'faq_events_items', array() ) );
		$title   = (string) rivermere_option( 'faq_events_title', '' );
		$intro   = (string) rivermere_option( 'faq_events_intro', '' );
	} elseif ( is_singular( 'rm_event' ) ) {
		$context = 'event';
		$items   = rivermere_clean_faqs( rivermere_field( 'event_faqs', get_queried_object_id(), array() ) );
		if ( ! $items ) {
			$items = rivermere_clean_faqs( rivermere_option( 'faq_events_items', array() ) );
			$title = (string) rivermere_option( 'faq_events_title', '' );
			$intro = (string) rivermere_option( 'faq_events_intro', '' );
		}
	} elseif ( is_page_template( 'page-about.php' ) ) {
		$context = 'about';
		$id      = get_queried_object_id();
		$items   = rivermere_clean_faqs( rivermere_field( 'ap_faq_items', $id, array() ) );
		$title   = (string) rivermere_field( 'ap_faq_title', $id, '' );
		$intro   = (string) rivermere_field( 'ap_faq_intro', $id, '' );
	} elseif ( is_page_template( 'page-become-a-vendor.php' ) ) {
		$context = 'vendor_application';
		$id      = get_queried_object_id();
		$items   = rivermere_clean_faqs( rivermere_field( 'vp_faq_items', $id, array() ) );
		$title   = (string) rivermere_field( 'vp_faq_title', $id, '' );
		$intro   = (string) rivermere_field( 'vp_faq_intro', $id, '' );
	}

	$cache[ $cache_key ] = $items ? array(
		'title'   => '' !== $title ? $title : __( 'Frequently asked questions', 'rivermere-market' ),
		'intro'   => $intro,
		'items'   => $items,
		'context' => $context,
	) : null;
	return $cache[ $cache_key ];
}

/**
 * Feed the resolved items to Rivermere Core's FAQPage schema (same data as the visible list).
 *
 * @param array $items Existing items.
 * @return array
 */
function rivermere_filter_current_faqs( $items ) {
	$faq = rivermere_resolve_faq();
	return $faq ? $faq['items'] : $items;
}
add_filter( 'rivermere_current_faqs', 'rivermere_filter_current_faqs' );

/**
 * Render the FAQ section for the current request (nothing when there is no valid content).
 *
 * @param array $args contained (bool): wrap in a full-width band + container.
 */
function rivermere_render_faq( $args = array() ) {
	$faq = rivermere_resolve_faq();
	if ( ! $faq ) {
		return;
	}
	get_template_part( 'template-parts/components/faq', null, array_merge( array( 'contained' => true ), $args, array( 'faq' => $faq ) ) );
}
