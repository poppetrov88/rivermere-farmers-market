<?php
/**
 * Reporting queries (all prepared, all bounded by a validated date range).
 *
 * @package Rivermere_Insights
 */

defined( 'ABSPATH' ) || exit;

/**
 * Validated date range from the request (GET): range=today|7|30|90|custom, from, to.
 *
 * @return array{from:string,to:string,key:string,label:string}
 */
function ri_range() {
	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only filters on an admin-only screen.
	$key   = isset( $_GET['range'] ) ? sanitize_key( wp_unslash( $_GET['range'] ) ) : '30';
	$today = gmdate( 'Y-m-d' );
	$valid = static function ( $d ) {
		$dt = DateTime::createFromFormat( 'Y-m-d', (string) $d );
		return $dt && $dt->format( 'Y-m-d' ) === $d;
	};
	switch ( $key ) {
		case 'today':
			$from = $today;
			$to   = $today;
			break;
		case '7':
		case '30':
		case '90':
			$from = gmdate( 'Y-m-d', time() - ( (int) $key - 1 ) * DAY_IN_SECONDS );
			$to   = $today;
			break;
		case 'custom':
			$f    = isset( $_GET['from'] ) ? sanitize_text_field( wp_unslash( $_GET['from'] ) ) : '';
			$t    = isset( $_GET['to'] ) ? sanitize_text_field( wp_unslash( $_GET['to'] ) ) : '';
			$from = $valid( $f ) ? $f : gmdate( 'Y-m-d', time() - 29 * DAY_IN_SECONDS );
			$to   = $valid( $t ) ? $t : $today;
			if ( $from > $to ) {
				list( $from, $to ) = array( $to, $from );
			}
			break;
		default:
			$key  = '30';
			$from = gmdate( 'Y-m-d', time() - 29 * DAY_IN_SECONDS );
			$to   = $today;
	}
	// phpcs:enable
	return array(
		'from'  => $from,
		'to'    => $to,
		'key'   => $key,
		'label' => $from === $to ? $from : $from . ' → ' . $to,
	);
}

/**
 * Totals by event type.
 *
 * @param array $r Range.
 * @return array<string,int>
 */
function ri_totals( $r ) {
	global $wpdb;
	$table = ri_table();
	$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT event_type, SUM(hits) AS n FROM {$table} WHERE day BETWEEN %s AND %s GROUP BY event_type", $r['from'], $r['to'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$out   = array();
	foreach ( $rows as $row ) {
		$out[ $row->event_type ] = (int) $row->n;
	}
	return $out;
}

/**
 * Sum of totals for a set of types.
 *
 * @param array $totals Totals.
 * @param array $types  Types.
 * @return int
 */
function ri_sum( $totals, $types ) {
	$n = 0;
	foreach ( $types as $type ) {
		$n += $totals[ $type ] ?? 0;
	}
	return $n;
}

/**
 * Types by kind.
 *
 * @param string $kind view|click|conversion.
 * @return string[]
 */
function ri_types_of_kind( $kind ) {
	return array_keys( array_filter( ri_event_types(), static function ( $t ) use ( $kind ) {
		return $kind === $t['kind'];
	} ) );
}

/**
 * Placeholders for an IN() list.
 *
 * @param array $items Items.
 * @return string
 */
function ri_in( $items ) {
	return implode( ',', array_fill( 0, count( $items ), '%s' ) );
}

/**
 * Daily series: day => [views, clicks, conversions] (zero-filled).
 *
 * @param array $r Range.
 * @return array
 */
function ri_daily( $r ) {
	global $wpdb;
	$table = ri_table();
	$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT day, event_type, SUM(hits) AS n FROM {$table} WHERE day BETWEEN %s AND %s GROUP BY day, event_type", $r['from'], $r['to'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$kinds = array();
	foreach ( ri_event_types() as $type => $def ) {
		$kinds[ $type ] = $def['kind'];
	}
	$series = array();
	$cursor = strtotime( $r['from'] . ' UTC' );
	$end    = strtotime( $r['to'] . ' UTC' );
	for ( $t = $cursor; $t <= $end && count( $series ) < 400; $t += DAY_IN_SECONDS ) {
		$series[ gmdate( 'Y-m-d', $t ) ] = array( 'views' => 0, 'clicks' => 0, 'conversions' => 0 );
	}
	$map = array( 'view' => 'views', 'click' => 'clicks', 'conversion' => 'conversions' );
	foreach ( $rows as $row ) {
		if ( isset( $series[ $row->day ], $kinds[ $row->event_type ] ) ) {
			$series[ $row->day ][ $map[ $kinds[ $row->event_type ] ] ] += (int) $row->n;
		}
	}
	return $series;
}

/**
 * Top pages by views, with tracked interactions.
 *
 * @param array $r     Range.
 * @param int   $limit Limit.
 * @return array
 */
function ri_top_pages( $r, $limit = 10 ) {
	global $wpdb;
	$table  = ri_table();
	$clicks = ri_types_of_kind( 'click' );
	$sql    = "SELECT page_path, MAX(page_id) AS page_id,
		SUM(CASE WHEN event_type = 'pageview' THEN hits ELSE 0 END) AS views,
		SUM(CASE WHEN event_type IN (" . ri_in( $clicks ) . ") THEN hits ELSE 0 END) AS interactions
		FROM {$table} WHERE day BETWEEN %s AND %s AND page_path <> ''
		GROUP BY page_path ORDER BY views DESC, interactions DESC LIMIT %d";
	$rows   = $wpdb->get_results( $wpdb->prepare( $sql, array_merge( $clicks, array( $r['from'], $r['to'], (int) $limit ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	foreach ( $rows as $row ) {
		$row->title = $row->page_id && get_post( $row->page_id ) ? get_the_title( (int) $row->page_id ) : ( '/' === $row->page_path ? __( 'Home', 'rivermere-insights' ) : $row->page_path );
	}
	return $rows;
}

/**
 * Click rows (label, type, page, total).
 *
 * @param array $r     Range.
 * @param int   $limit Limit.
 * @param bool  $by_page Include the page in the grouping.
 * @return array
 */
function ri_click_rows( $r, $limit = 10, $by_page = false ) {
	global $wpdb;
	$table  = ri_table();
	$clicks = ri_types_of_kind( 'click' );
	$group  = $by_page ? 'event_type, event_key, page_path' : 'event_type, event_key';
	$select = $by_page ? 'event_type, event_key, page_path' : "event_type, event_key, '' AS page_path";
	$sql    = "SELECT {$select}, SUM(hits) AS n FROM {$table} WHERE day BETWEEN %s AND %s AND event_type IN (" . ri_in( $clicks ) . ") GROUP BY {$group} ORDER BY n DESC LIMIT %d";
	return $wpdb->get_results( $wpdb->prepare( $sql, array_merge( array( $r['from'], $r['to'] ), $clicks, array( (int) $limit ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

/**
 * Raw aggregated rows for export.
 *
 * @param array $r Range.
 * @return array
 */
function ri_export_rows( $r ) {
	global $wpdb;
	$table = ri_table();
	return $wpdb->get_results( $wpdb->prepare( "SELECT day, event_type, event_key, page_path, page_type, hits FROM {$table} WHERE day BETWEEN %s AND %s ORDER BY day, event_type, event_key", $r['from'], $r['to'] ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}
