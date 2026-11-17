<?php
/**
 * Breadcrumbs: one reusable component for every inner page.
 *
 * - Rank Math active AND its breadcrumb module enabled: the trail comes from the official
 *   rank_math_the_breadcrumbs() (labels, hierarchy and the single BreadcrumbList JSON-LD stay
 *   Rank Math's); only the markup is replaced with an accessible <ol> via Rank Math's HTML filter.
 * - Otherwise: a native fallback built from the queried object (page hierarchy, post type
 *   archives, taxonomies). The fallback prints NO structured data, so nothing can duplicate.
 *
 * Rendered once, by header.php (template-parts/components/breadcrumbs.php); never on the front page.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether Rank Math can currently supply breadcrumbs.
 *
 * @return bool
 */
function rivermere_rank_math_breadcrumbs_active() {
	if ( ! function_exists( 'rank_math_the_breadcrumbs' ) || ! class_exists( '\RankMath\Helper' ) ) {
		return false;
	}
	return (bool) \RankMath\Helper::is_breadcrumbs_enabled();
}

/**
 * Build the <ol> for a list of crumbs. The last crumb is the current page.
 *
 * @param array $crumbs List of array( label, url ) (url may be empty).
 * @return string Escaped HTML ('' when there is nothing useful to show).
 */
function rivermere_breadcrumb_list_html( $crumbs ) {
	$crumbs = array_values( array_filter( (array) $crumbs, static function ( $crumb ) {
		return ! empty( $crumb[0] );
	} ) );
	if ( count( $crumbs ) < 2 ) {
		return '';
	}
	$last = count( $crumbs ) - 1;
	$html = '<ol class="breadcrumbs__list">';
	foreach ( $crumbs as $i => $crumb ) {
		$label = esc_html( wp_strip_all_tags( $crumb[0] ) );
		if ( $i === $last || empty( $crumb[1] ) ) {
			$html .= '<li class="breadcrumbs__item"><span class="breadcrumbs__current" aria-current="page">' . $label . '</span></li>';
		} else {
			$html .= '<li class="breadcrumbs__item"><a class="breadcrumbs__link" href="' . esc_url( $crumb[1] ) . '">' . $label . '</a></li>';
		}
	}
	return $html . '</ol>';
}

/**
 * Rank Math filter callback: swap its <nav><p> output for our <ol> (no nested navigation).
 *
 * @param string $html   Rank Math HTML.
 * @param array  $crumbs Rank Math crumbs (label, url, flags).
 * @return string
 */
function rivermere_rank_math_breadcrumb_html( $html, $crumbs ) {
	return rivermere_breadcrumb_list_html( $crumbs );
}

/**
 * Native fallback trail.
 *
 * @return array List of array( label, url ).
 */
function rivermere_fallback_crumbs() {
	$crumbs = array( array( __( 'Home', 'rivermere-market' ), home_url( '/' ) ) );

	$add_ancestors = static function ( $post_id ) use ( &$crumbs ) {
		foreach ( array_reverse( get_post_ancestors( $post_id ) ) as $ancestor ) {
			$crumbs[] = array( get_the_title( $ancestor ), get_permalink( $ancestor ) );
		}
	};
	$add_archive = static function ( $post_type ) use ( &$crumbs ) {
		$object = get_post_type_object( $post_type );
		if ( $object && $object->has_archive ) {
			$crumbs[] = array( $object->labels->name, get_post_type_archive_link( $post_type ) );
		} elseif ( 'post' === $post_type && (int) get_option( 'page_for_posts' ) ) {
			$crumbs[] = array( get_the_title( (int) get_option( 'page_for_posts' ) ), get_permalink( (int) get_option( 'page_for_posts' ) ) );
		}
	};

	if ( is_404() ) {
		$crumbs[] = array( __( 'Page not found', 'rivermere-market' ), '' );
	} elseif ( is_search() ) {
		$crumbs[] = array( __( 'Search Results', 'rivermere-market' ), '' );
	} elseif ( is_home() ) {
		$crumbs[] = array( single_post_title( '', false ) ? single_post_title( '', false ) : __( 'Blog', 'rivermere-market' ), '' );
	} elseif ( is_singular() ) {
		$post = get_queried_object();
		if ( is_page() ) {
			$add_ancestors( $post->ID );
		} else {
			$add_archive( $post->post_type );
			if ( 'post' === $post->post_type ) {
				$cats = get_the_category( $post->ID );
				if ( $cats ) {
					$crumbs[] = array( $cats[0]->name, get_category_link( $cats[0] ) );
				}
			}
		}
		$crumbs[] = array( get_the_title( $post ), '' );
	} elseif ( is_post_type_archive() ) {
		$object = get_queried_object();
		$crumbs[] = array( $object ? $object->labels->name : post_type_archive_title( '', false ), '' );
	} elseif ( is_tax() || is_category() || is_tag() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term ) {
			$tax = get_taxonomy( $term->taxonomy );
			if ( $tax && ! empty( $tax->object_type ) ) {
				$add_archive( $tax->object_type[0] );
			}
			foreach ( array_reverse( get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) ) as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, $term->taxonomy );
				if ( $ancestor && ! is_wp_error( $ancestor ) ) {
					$crumbs[] = array( $ancestor->name, get_term_link( $ancestor ) );
				}
			}
			$crumbs[] = array( $term->name, '' );
		}
	} elseif ( is_author() ) {
		$crumbs[] = array( get_the_author_meta( 'display_name', get_queried_object_id() ), '' );
	} elseif ( is_archive() ) {
		$crumbs[] = array( wp_strip_all_tags( get_the_archive_title() ), '' );
	}

	return apply_filters( 'rivermere_breadcrumb_crumbs', $crumbs );
}

/**
 * Get the breadcrumb <ol> markup for the current request.
 *
 * @return string Escaped HTML or ''.
 */
function rivermere_breadcrumbs_html() {
	if ( is_front_page() ) {
		return '';
	}
	if ( rivermere_rank_math_breadcrumbs_active() ) {
		add_filter( 'rank_math/frontend/breadcrumb/html', 'rivermere_rank_math_breadcrumb_html', 99, 2 );
		ob_start();
		rank_math_the_breadcrumbs();
		$html = trim( (string) ob_get_clean() );
		remove_filter( 'rank_math/frontend/breadcrumb/html', 'rivermere_rank_math_breadcrumb_html', 99 );
		if ( '' !== $html ) {
			return $html;
		}
	}
	return rivermere_breadcrumb_list_html( rivermere_fallback_crumbs() );
}

/**
 * Print the breadcrumb bar (container + nav landmark).
 */
function rivermere_breadcrumbs() {
	get_template_part( 'template-parts/components/breadcrumbs' );
}
