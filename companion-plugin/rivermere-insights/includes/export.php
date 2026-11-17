<?php
/**
 * CSV export (admins only, nonce-protected, respects the selected date range, formula-injection safe).
 *
 * @package Rivermere_Insights
 */

defined( 'ABSPATH' ) || exit;

/**
 * Export handler.
 */
function ri_export() {
	check_admin_referer( 'ri_export' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'rivermere-insights' ), 403 );
	}
	$r = ri_range();
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="insights-' . $r['from'] . '_' . $r['to'] . '.csv"' );
	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'day_utc', 'event_type', 'event_label', 'page_path', 'page_type', 'count' ), ',', '"', '\\' );
	foreach ( ri_export_rows( $r ) as $row ) {
		$row = array_map( static function ( $cell ) {
			return preg_match( '/^[=+\-@\t\r]/', (string) $cell ) ? "'" . $cell : $cell;
		}, $row );
		fputcsv( $out, $row, ',', '"', '\\' );
	}
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	exit;
}
add_action( 'admin_post_ri_export', 'ri_export' );
