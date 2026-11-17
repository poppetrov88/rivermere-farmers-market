<?php
/**
 * Event calendar download: /events/<slug>/?rm-ics=1 returns a valid RFC 5545 .ics file.
 *
 * @package Rivermere_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Escape a TEXT value per RFC 5545.
 *
 * @param string $text Raw text.
 * @return string
 */
function rivermere_ics_escape( $text ) {
	$text = str_replace( array( '\\', ';', ',' ), array( '\\\\', '\\;', '\\,' ), (string) $text );
	return str_replace( array( "\r\n", "\n", "\r" ), '\\n', $text );
}

/**
 * Fold a content line at 75 octets (RFC 5545 3.1).
 *
 * @param string $line Line.
 * @return string
 */
function rivermere_ics_fold( $line ) {
	$out = '';
	while ( strlen( $line ) > 75 ) {
		$cut = 75;
		// Do not split a multibyte character.
		while ( $cut > 0 && ( ord( $line[ $cut ] ) & 0xC0 ) === 0x80 ) {
			--$cut;
		}
		$out  .= substr( $line, 0, $cut ) . "\r\n ";
		$line  = substr( $line, $cut );
	}
	return $out . $line;
}

/**
 * Serve the .ics for a single event.
 */
function rivermere_event_ics() {
	if ( ! is_singular( 'rm_event' ) || ! isset( $_GET['rm-ics'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public, read-only download.
		return;
	}
	$post  = get_queried_object();
	$start = get_post_meta( $post->ID, 'event_start', true );
	$tz    = wp_timezone();
	$s     = $start ? date_create_immutable_from_format( 'Y-m-d H:i:s', $start, $tz ) : false;
	if ( ! $s ) {
		status_header( 404 );
		nocache_headers();
		wp_die( esc_html__( 'This event has no start date.', 'rivermere-core' ), '', array( 'response' => 404 ) );
	}
	$utc = new DateTimeZone( 'UTC' );
	$end = get_post_meta( $post->ID, 'event_end', true );
	$e   = $end ? date_create_immutable_from_format( 'Y-m-d H:i:s', $end, $tz ) : false;

	$loc  = trim( get_post_meta( $post->ID, 'event_location', true ) );
	$addr = trim( wp_strip_all_tags( str_replace( array( '<br />', '<br>', "\r\n", "\n" ), ', ', (string) get_post_meta( $post->ID, 'event_address', true ) ) ) );
	$desc = get_post_meta( $post->ID, 'event_tagline', true );
	if ( has_excerpt( $post ) ) {
		$desc = get_the_excerpt( $post );
	}
	$status = get_post_meta( $post->ID, 'event_status', true );

	$lines = array(
		'BEGIN:VCALENDAR',
		'VERSION:2.0',
		'PRODID:-//Rivermere Core//Event//EN',
		'CALSCALE:GREGORIAN',
		'METHOD:PUBLISH',
		'BEGIN:VEVENT',
		'UID:event-' . $post->ID . '@' . wp_parse_url( home_url(), PHP_URL_HOST ),
		'DTSTAMP:' . gmdate( 'Ymd\THis\Z' ),
		'DTSTART:' . $s->setTimezone( $utc )->format( 'Ymd\THis\Z' ),
	);
	if ( $e && $e > $s ) {
		$lines[] = 'DTEND:' . $e->setTimezone( $utc )->format( 'Ymd\THis\Z' );
	}
	$lines[] = 'SUMMARY:' . rivermere_ics_escape( get_the_title( $post ) );
	if ( $desc ) {
		$lines[] = 'DESCRIPTION:' . rivermere_ics_escape( $desc );
	}
	$where = trim( $loc . ( $loc && $addr ? ', ' : '' ) . $addr );
	if ( $where ) {
		$lines[] = 'LOCATION:' . rivermere_ics_escape( $where );
	}
	$lines[] = 'URL:' . get_permalink( $post );
	$lines[] = 'STATUS:' . ( 'cancelled' === $status ? 'CANCELLED' : 'CONFIRMED' );
	$lines[] = 'END:VEVENT';
	$lines[] = 'END:VCALENDAR';

	nocache_headers();
	header( 'Content-Type: text/calendar; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $post->post_name ) . '.ics"' );
	echo implode( "\r\n", array_map( 'rivermere_ics_fold', $lines ) ) . "\r\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- iCalendar text, escaped per RFC 5545.
	exit;
}
add_action( 'template_redirect', 'rivermere_event_ics' );
