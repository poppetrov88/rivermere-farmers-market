<?php
/**
 * Admin: Insights menu, dashboards, reports, settings, reset.
 *
 * @package Rivermere_Insights
 */

defined( 'ABSPATH' ) || exit;

/**
 * Menu.
 */
function ri_admin_menu() {
	$cap = 'manage_options';
	add_menu_page( __( 'Insights', 'rivermere-insights' ), __( 'Insights', 'rivermere-insights' ), $cap, 'rivermere-insights', 'ri_page_overview', 'dashicons-chart-area', 57 );
	add_submenu_page( 'rivermere-insights', __( 'Overview', 'rivermere-insights' ), __( 'Overview', 'rivermere-insights' ), $cap, 'rivermere-insights', 'ri_page_overview' );
	add_submenu_page( 'rivermere-insights', __( 'Click Tracking', 'rivermere-insights' ), __( 'Click Tracking', 'rivermere-insights' ), $cap, 'ri-clicks', 'ri_page_clicks' );
	add_submenu_page( 'rivermere-insights', __( 'Pages', 'rivermere-insights' ), __( 'Pages', 'rivermere-insights' ), $cap, 'ri-pages', 'ri_page_pages' );
	add_submenu_page( 'rivermere-insights', __( 'Conversions', 'rivermere-insights' ), __( 'Conversions', 'rivermere-insights' ), $cap, 'ri-conversions', 'ri_page_conversions' );
	add_submenu_page( 'rivermere-insights', __( 'Reports', 'rivermere-insights' ), __( 'Reports', 'rivermere-insights' ), $cap, 'ri-reports', 'ri_page_reports' );
	add_submenu_page( 'rivermere-insights', __( 'Settings', 'rivermere-insights' ), __( 'Settings', 'rivermere-insights' ), $cap, 'ri-settings', 'ri_page_settings' );
}
add_action( 'admin_menu', 'ri_admin_menu' );

/**
 * Admin CSS, only on our screens.
 *
 * @param string $hook Hook suffix.
 */
function ri_admin_assets( $hook ) {
	if ( false !== strpos( $hook, 'rivermere-insights' ) || false !== strpos( $hook, 'insights_page_ri-' ) ) {
		wp_enqueue_style( 'ri-admin', RI_URL . 'assets/admin.css', array(), RI_VERSION );
	}
}
add_action( 'admin_enqueue_scripts', 'ri_admin_assets' );

/**
 * Shared page header: title + date-range filter.
 *
 * @param string $title Title.
 * @param string $slug  Page slug.
 * @param bool   $filter Show the date filter.
 */
function ri_header( $title, $slug, $filter = true ) {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to view this page.', 'rivermere-insights' ), 403 );
	}
	$r = ri_range();
	echo '<div class="wrap ri"><h1>' . esc_html( $title ) . '</h1>';
	if ( ! ri_settings()['enabled'] ) {
		echo '<div class="notice notice-warning inline"><p>' . esc_html__( 'Tracking is switched off in Insights → Settings. Existing data is still shown.', 'rivermere-insights' ) . '</p></div>';
	}
	if ( $filter ) {
		$opts = array( 'today' => __( 'Today', 'rivermere-insights' ), '7' => __( 'Last 7 days', 'rivermere-insights' ), '30' => __( 'Last 30 days', 'rivermere-insights' ), '90' => __( 'Last 90 days', 'rivermere-insights' ), 'custom' => __( 'Custom range', 'rivermere-insights' ) );
		echo '<form method="get" class="ri-filter"><input type="hidden" name="page" value="' . esc_attr( $slug ) . '">';
		echo '<label for="ri-range">' . esc_html__( 'Date range', 'rivermere-insights' ) . '</label> <select id="ri-range" name="range">';
		foreach ( $opts as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '"' . selected( $r['key'], (string) $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select> <label for="ri-from">' . esc_html__( 'From', 'rivermere-insights' ) . '</label> <input type="date" id="ri-from" name="from" value="' . esc_attr( $r['from'] ) . '"> <label for="ri-to">' . esc_html__( 'To', 'rivermere-insights' ) . '</label> <input type="date" id="ri-to" name="to" value="' . esc_attr( $r['to'] ) . '"> ';
		submit_button( __( 'Apply', 'rivermere-insights' ), 'secondary', '', false );
		echo ' <span class="description">' . esc_html( sprintf( /* translators: %s: date range */ __( 'Showing %s (UTC days)', 'rivermere-insights' ), $r['label'] ) ) . '</span></form>';
	}
}

/**
 * A stat card.
 *
 * @param string $label Label.
 * @param int    $value Value.
 * @param string $note  Note.
 */
function ri_card( $label, $value, $note = '' ) {
	echo '<div class="ri-card"><div class="ri-card__label">' . esc_html( $label ) . '</div><div class="ri-card__value">' . esc_html( number_format_i18n( $value ) ) . '</div>';
	if ( $note ) {
		echo '<div class="ri-card__note">' . esc_html( $note ) . '</div>';
	}
	echo '</div>';
}

/**
 * Native SVG line chart with a data-table fallback.
 *
 * @param array $series Daily series.
 */
function ri_chart( $series ) {
	$w    = 860;
	$h    = 240;
	$pad  = array( 'l' => 44, 'r' => 12, 't' => 14, 'b' => 28 );
	$days = array_keys( $series );
	$n    = count( $days );
	$max  = 1;
	foreach ( $series as $row ) {
		$max = max( $max, $row['views'], $row['clicks'], $row['conversions'] );
	}
	$x = static function ( $i ) use ( $n, $w, $pad ) {
		return $pad['l'] + ( $n > 1 ? $i * ( $w - $pad['l'] - $pad['r'] ) / ( $n - 1 ) : ( $w - $pad['l'] - $pad['r'] ) / 2 );
	};
	$y = static function ( $v ) use ( $max, $h, $pad ) {
		return $h - $pad['b'] - ( $v / $max ) * ( $h - $pad['t'] - $pad['b'] );
	};
	$colors = array( 'views' => '#1d6806', 'clicks' => '#e08a1e', 'conversions' => '#2271b1' );
	$names  = array( 'views' => __( 'Page views', 'rivermere-insights' ), 'clicks' => __( 'Tracked clicks', 'rivermere-insights' ), 'conversions' => __( 'Confirmed conversions', 'rivermere-insights' ) );
	echo '<figure class="ri-chart"><svg viewBox="0 0 ' . (int) $w . ' ' . (int) $h . '" role="img" aria-labelledby="ri-chart-t ri-chart-d"><title id="ri-chart-t">' . esc_html__( 'Daily trend', 'rivermere-insights' ) . '</title><desc id="ri-chart-d">' . esc_html__( 'Line chart of page views, tracked clicks and confirmed conversions per day. A data table follows.', 'rivermere-insights' ) . '</desc>';
	for ( $g = 0; $g <= 4; $g++ ) {
		$val = (int) round( $max * $g / 4 );
		$yy  = $y( $val );
		echo '<line x1="' . (int) $pad['l'] . '" x2="' . (int) ( $w - $pad['r'] ) . '" y1="' . esc_attr( round( $yy, 1 ) ) . '" y2="' . esc_attr( round( $yy, 1 ) ) . '" stroke="#dcdcde"/><text x="' . ( (int) $pad['l'] - 6 ) . '" y="' . esc_attr( round( $yy + 4, 1 ) ) . '" text-anchor="end" font-size="11" fill="#50575e">' . (int) $val . '</text>';
	}
	foreach ( $colors as $key => $color ) {
		$points = array();
		foreach ( array_values( $series ) as $i => $row ) {
			$points[] = round( $x( $i ), 1 ) . ',' . round( $y( $row[ $key ] ), 1 );
		}
		echo '<polyline fill="none" stroke="' . esc_attr( $color ) . '" stroke-width="2.5" stroke-linejoin="round" points="' . esc_attr( implode( ' ', $points ) ) . '"/>';
		foreach ( $points as $pt ) {
			list( $cx, $cy ) = explode( ',', $pt );
			if ( $n <= 45 ) {
				echo '<circle cx="' . esc_attr( $cx ) . '" cy="' . esc_attr( $cy ) . '" r="3" fill="' . esc_attr( $color ) . '"/>';
			}
		}
	}
	foreach ( array( 0, (int) floor( ( $n - 1 ) / 2 ), $n - 1 ) as $i ) {
		if ( isset( $days[ $i ] ) ) {
			echo '<text x="' . esc_attr( round( $x( $i ), 1 ) ) . '" y="' . ( (int) $h - 8 ) . '" text-anchor="middle" font-size="11" fill="#50575e">' . esc_html( $days[ $i ] ) . '</text>';
		}
	}
	echo '</svg><figcaption>';
	foreach ( $colors as $key => $color ) {
		echo '<span class="ri-key"><i style="background:' . esc_attr( $color ) . '"></i>' . esc_html( $names[ $key ] ) . '</span>';
	}
	echo '</figcaption></figure>';
	echo '<details class="ri-table-details"><summary>' . esc_html__( 'Show data table', 'rivermere-insights' ) . '</summary><table class="widefat striped"><thead><tr><th>' . esc_html__( 'Day (UTC)', 'rivermere-insights' ) . '</th><th>' . esc_html( $names['views'] ) . '</th><th>' . esc_html( $names['clicks'] ) . '</th><th>' . esc_html( $names['conversions'] ) . '</th></tr></thead><tbody>';
	foreach ( array_reverse( $series, true ) as $day => $row ) {
		echo '<tr><td>' . esc_html( $day ) . '</td><td>' . (int) $row['views'] . '</td><td>' . (int) $row['clicks'] . '</td><td>' . (int) $row['conversions'] . '</td></tr>';
	}
	echo '</tbody></table></details>';
}

/**
 * Label for an event type.
 *
 * @param string $type Type.
 * @return string
 */
function ri_type_label( $type ) {
	return ri_event_types()[ $type ]['label'] ?? $type;
}

/* ------------------------------------------------------------------ Pages */

/**
 * Overview.
 */
function ri_page_overview() {
	ri_header( __( 'Insights – Overview', 'rivermere-insights' ), 'rivermere-insights' );
	$r      = ri_range();
	$t      = ri_totals( $r );
	$clicks = ri_sum( $t, ri_types_of_kind( 'click' ) );
	echo '<p class="description">' . esc_html__( 'Page views count page loads from visitors who accepted analytics cookies; they are not unique visitors. Clicks are button/link interactions, not conversions. Conversions are applications or subscriptions that were actually saved.', 'rivermere-insights' ) . '</p>';
	echo '<div class="ri-cards">';
	ri_card( __( 'Total page views', 'rivermere-insights' ), $t['pageview'] ?? 0, __( 'Not unique visitors', 'rivermere-insights' ) );
	ri_card( __( 'Tracked clicks', 'rivermere-insights' ), $clicks );
	ri_card( __( 'Vendor applications submitted', 'rivermere-insights' ), $t['application_submitted'] ?? 0, __( 'Confirmed (stored)', 'rivermere-insights' ) );
	ri_card( __( 'Newsletter subscriptions', 'rivermere-insights' ), $t['newsletter_subscribed'] ?? 0, __( 'Confirmed new', 'rivermere-insights' ) );
	ri_card( __( 'Contact clicks', 'rivermere-insights' ), ri_sum( $t, array( 'tel', 'email', 'whatsapp' ) ), __( 'Phone, email, WhatsApp', 'rivermere-insights' ) );
	ri_card( __( 'Social media clicks', 'rivermere-insights' ), $t['social'] ?? 0 );
	echo '</div><h2>' . esc_html__( 'Daily trend', 'rivermere-insights' ) . '</h2>';
	ri_chart( ri_daily( $r ) );

	echo '<div class="ri-two"><div><h2>' . esc_html__( 'Most popular pages', 'rivermere-insights' ) . '</h2>';
	$pages = ri_top_pages( $r, 8 );
	if ( $pages ) {
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Page', 'rivermere-insights' ) . '</th><th>' . esc_html__( 'Views', 'rivermere-insights' ) . '</th></tr></thead><tbody>';
		foreach ( $pages as $p ) {
			echo '<tr><td>' . esc_html( $p->title ) . '<br><span class="description">' . esc_html( $p->page_path ) . '</span></td><td>' . (int) $p->views . '</td></tr>';
		}
		echo '</tbody></table>';
	} else {
		echo '<p>' . esc_html__( 'No page views recorded in this range yet.', 'rivermere-insights' ) . '</p>';
	}
	echo '</div><div><h2>' . esc_html__( 'Most clicked buttons and links', 'rivermere-insights' ) . '</h2>';
	$rows = ri_click_rows( $r, 8 );
	if ( $rows ) {
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Label', 'rivermere-insights' ) . '</th><th>' . esc_html__( 'Type', 'rivermere-insights' ) . '</th><th>' . esc_html__( 'Clicks', 'rivermere-insights' ) . '</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			echo '<tr><td>' . esc_html( $row->event_key ) . '</td><td>' . esc_html( ri_type_label( $row->event_type ) ) . '</td><td>' . (int) $row->n . '</td></tr>';
		}
		echo '</tbody></table>';
	} else {
		echo '<p>' . esc_html__( 'No clicks recorded in this range yet.', 'rivermere-insights' ) . '</p>';
	}
	echo '</div></div></div>';
}

/**
 * Click tracking.
 */
function ri_page_clicks() {
	ri_header( __( 'Insights – Click Tracking', 'rivermere-insights' ), 'ri-clicks' );
	$rows = ri_click_rows( ri_range(), 200, true );
	if ( ! $rows ) {
		echo '<p>' . esc_html__( 'No clicks recorded in this range yet.', 'rivermere-insights' ) . '</p></div>';
		return;
	}
	echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Event label', 'rivermere-insights' ) . '</th><th>' . esc_html__( 'Event type', 'rivermere-insights' ) . '</th><th>' . esc_html__( 'Page', 'rivermere-insights' ) . '</th><th>' . esc_html__( 'Total clicks', 'rivermere-insights' ) . '</th></tr></thead><tbody>';
	foreach ( $rows as $row ) {
		echo '<tr><td>' . esc_html( $row->event_key ) . '</td><td>' . esc_html( ri_type_label( $row->event_type ) ) . '</td><td>' . esc_html( $row->page_path ? $row->page_path : '—' ) . '</td><td>' . (int) $row->n . '</td></tr>';
	}
	echo '</tbody></table></div>';
}

/**
 * Pages.
 */
function ri_page_pages() {
	ri_header( __( 'Insights – Pages', 'rivermere-insights' ), 'ri-pages' );
	$rows = ri_top_pages( ri_range(), 100 );
	if ( ! $rows ) {
		echo '<p>' . esc_html__( 'No page data in this range yet.', 'rivermere-insights' ) . '</p></div>';
		return;
	}
	echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Page title', 'rivermere-insights' ) . '</th><th>' . esc_html__( 'Path', 'rivermere-insights' ) . '</th><th>' . esc_html__( 'Page views', 'rivermere-insights' ) . '</th><th>' . esc_html__( 'Tracked interactions', 'rivermere-insights' ) . '</th></tr></thead><tbody>';
	foreach ( $rows as $row ) {
		echo '<tr><td>' . esc_html( $row->title ) . '</td><td>' . esc_html( $row->page_path ) . '</td><td>' . (int) $row->views . '</td><td>' . (int) $row->interactions . '</td></tr>';
	}
	echo '</tbody></table></div>';
}

/**
 * Conversions.
 */
function ri_page_conversions() {
	ri_header( __( 'Insights – Conversions', 'rivermere-insights' ), 'ri-conversions' );
	$r      = ri_range();
	$t      = ri_totals( $r );
	$series = ri_daily( $r );
	echo '<p class="description">' . esc_html__( 'Only server-confirmed events are counted: an application or subscription is counted after it has been saved, never for failed submissions or duplicate newsletter sign-ups, and only when the visitor accepted analytics cookies. Conversions made without that consent are not recorded here, so these figures are a lower bound and no conversion rates are shown.', 'rivermere-insights' ) . '</p><div class="ri-cards">';
	ri_card( __( 'Vendor applications', 'rivermere-insights' ), $t['application_submitted'] ?? 0 );
	ri_card( __( 'Newsletter subscriptions', 'rivermere-insights' ), $t['newsletter_subscribed'] ?? 0 );
	echo '</div><h2>' . esc_html__( 'Trend', 'rivermere-insights' ) . '</h2>';
	global $wpdb;
	$table = ri_table();
	$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT day, event_type, SUM(hits) AS n FROM {$table} WHERE day BETWEEN %s AND %s AND event_type IN ('application_submitted','newsletter_subscribed') GROUP BY day, event_type ORDER BY day DESC", $r['from'], $r['to'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	unset( $series );
	if ( $rows ) {
		echo '<table class="widefat striped"><thead><tr><th>' . esc_html__( 'Day (UTC)', 'rivermere-insights' ) . '</th><th>' . esc_html__( 'Conversion', 'rivermere-insights' ) . '</th><th>' . esc_html__( 'Count', 'rivermere-insights' ) . '</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			echo '<tr><td>' . esc_html( $row->day ) . '</td><td>' . esc_html( ri_type_label( $row->event_type ) ) . '</td><td>' . (int) $row->n . '</td></tr>';
		}
		echo '</tbody></table>';
	} else {
		echo '<p>' . esc_html__( 'No conversions recorded in this range yet.', 'rivermere-insights' ) . '</p>';
	}
	echo '</div>';
}

/**
 * Reports (CSV).
 */
function ri_page_reports() {
	ri_header( __( 'Insights – Reports', 'rivermere-insights' ), 'ri-reports' );
	$r   = ri_range();
	$url = wp_nonce_url( add_query_arg( array( 'action' => 'ri_export', 'range' => $r['key'], 'from' => $r['from'], 'to' => $r['to'] ), admin_url( 'admin-post.php' ) ), 'ri_export' );
	echo '<p>' . esc_html__( 'Download the aggregated daily counters for the selected date range. The file contains no personal data: only day, event type, label, page path and count.', 'rivermere-insights' ) . '</p>';
	echo '<p><a class="button button-primary" href="' . esc_url( $url ) . '">' . esc_html( sprintf( /* translators: %s: range */ __( 'Export CSV (%s)', 'rivermere-insights' ), $r['label'] ) ) . '</a></p></div>';
}

/**
 * Settings page.
 */
function ri_page_settings() {
	ri_header( __( 'Insights – Settings', 'rivermere-insights' ), 'ri-settings', false );
	$s    = ri_settings();
	$name = RI_OPTION;
	$chk  = static function ( $key, $label, $help = '' ) use ( $s, $name ) {
		echo '<tr><th scope="row">' . esc_html( $label ) . '</th><td><label><input type="checkbox" name="' . esc_attr( $name . '[' . $key . ']' ) . '" value="1" ' . checked( ! empty( $s[ $key ] ), true, false ) . '> ' . esc_html__( 'Enabled', 'rivermere-insights' ) . '</label>';
		if ( $help ) {
			echo '<p class="description">' . esc_html( $help ) . '</p>';
		}
		echo '</td></tr>';
	};
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['reset'] ) ) {
		echo '<div class="notice notice-success"><p>' . esc_html__( 'All analytics data was deleted.', 'rivermere-insights' ) . '</p></div>';
	}
	echo '<form method="post" action="options.php">';
	settings_fields( 'ri_settings_group' );
	echo '<table class="form-table" role="presentation">';
	$chk( 'enabled', __( 'Tracking', 'rivermere-insights' ), __( 'Master switch. When off, no tracker is loaded and nothing is recorded.', 'rivermere-insights' ) );
	$chk( 'track_pageviews', __( 'Page views', 'rivermere-insights' ) );
	$chk( 'track_cta', __( 'CTA / button tracking', 'rivermere-insights' ) );
	$chk( 'track_contact', __( 'Contact tracking', 'rivermere-insights' ), __( 'Phone, email and WhatsApp clicks (only the type is stored, never the number or address).', 'rivermere-insights' ) );
	$chk( 'track_social', __( 'Social tracking', 'rivermere-insights' ) );
	$chk( 'track_external', __( 'External link and download tracking', 'rivermere-insights' ), __( 'Only the destination host is stored, never the full URL.', 'rivermere-insights' ) );
	$chk( 'exclude_admins', __( 'Exclude administrators', 'rivermere-insights' ), __( 'Do not load the tracker for logged-in administrators.', 'rivermere-insights' ) );
	echo '<tr><th scope="row"><label for="ri-ret">' . esc_html__( 'Data retention', 'rivermere-insights' ) . '</label></th><td><select id="ri-ret" name="' . esc_attr( $name ) . '[retention_days]">';
	foreach ( array( 30, 90, 180, 365, 730 ) as $d ) {
		echo '<option value="' . (int) $d . '"' . selected( (int) $s['retention_days'], $d, false ) . '>' . esc_html( sprintf( /* translators: %d: days */ _n( '%d day', '%d days', $d, 'rivermere-insights' ), $d ) ) . '</option>';
	}
	echo '</select><p class="description">' . esc_html__( 'Older daily counters are deleted automatically once a day.', 'rivermere-insights' ) . '</p></td></tr>';
	echo '<tr><th scope="row"><label for="ri-ex">' . esc_html__( 'Exclude pages', 'rivermere-insights' ) . '</label></th><td><textarea id="ri-ex" class="large-text" rows="4" name="' . esc_attr( $name ) . '[exclude_pages]">' . esc_textarea( $s['exclude_pages'] ) . '</textarea><p class="description">' . esc_html__( 'One path per line, e.g. /privacy-policy/', 'rivermere-insights' ) . '</p></td></tr>';
	$banner = function_exists( 'rivermere_toggle' ) ? rivermere_toggle( 'ui_cookie_banner', true ) : null;
	echo '<tr><th scope="row">' . esc_html__( 'Consent integration', 'rivermere-insights' ) . '</th><td>';
	if ( null === $banner ) {
		echo '<span class="ri-status ri-status--warn">' . esc_html__( 'The Rivermere theme is not active, so the cookie banner status cannot be checked.', 'rivermere-insights' ) . '</span>';
	} elseif ( $banner ) {
		echo '<span class="ri-status ri-status--ok">' . esc_html__( 'Connected to the Rivermere cookie banner (cookie rm_consent).', 'rivermere-insights' ) . '</span>';
	} else {
		echo '<span class="ri-status ri-status--warn">' . esc_html__( 'The cookie banner is switched off in Theme Settings, so analytics consent cannot be collected and nothing will be tracked.', 'rivermere-insights' ) . '</span>';
	}
	echo '<p class="description">' . esc_html__( 'Tracking only runs when the visitor accepted Analytics cookies. This applies to page views, clicks and server-side conversions alike. Insights itself sets no cookies and stores no visitor identifier.', 'rivermere-insights' ) . '</p></td></tr></table>';
	submit_button();
	echo '</form><hr><h2>' . esc_html__( 'Reset analytics data', 'rivermere-insights' ) . '</h2>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="ri_reset">';
	wp_nonce_field( 'ri_reset' );
	echo '<p><label for="ri-confirm">' . esc_html__( 'Type RESET to permanently delete all collected analytics data:', 'rivermere-insights' ) . '</label> <input type="text" id="ri-confirm" name="confirm" autocomplete="off"> ';
	submit_button( __( 'Delete all data', 'rivermere-insights' ), 'delete', '', false );
	echo '</p></form></div>';
}

/**
 * Reset handler: nonce + capability + typed confirmation.
 */
function ri_reset() {
	check_admin_referer( 'ri_reset' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'rivermere-insights' ), 403 );
	}
	$confirm = isset( $_POST['confirm'] ) ? sanitize_text_field( wp_unslash( $_POST['confirm'] ) ) : '';
	if ( 'RESET' === $confirm ) {
		global $wpdb;
		$wpdb->query( 'TRUNCATE TABLE ' . ri_table() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery
		wp_safe_redirect( admin_url( 'admin.php?page=ri-settings&reset=1' ) );
	} else {
		wp_safe_redirect( admin_url( 'admin.php?page=ri-settings' ) );
	}
	exit;
}
add_action( 'admin_post_ri_reset', 'ri_reset' );
