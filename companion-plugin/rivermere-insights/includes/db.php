<?php
/**
 * Storage: ONE aggregated table (daily counters). No row per click, no visitor identifiers.
 *
 * @package Rivermere_Insights
 */

defined( 'ABSPATH' ) || exit;

/**
 * Table name.
 *
 * @return string
 */
function ri_table() {
	global $wpdb;
	return $wpdb->prefix . 'ri_events';
}

/**
 * Create / upgrade the table (versioned).
 */
function ri_db_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$table   = ri_table();
	$charset = $wpdb->get_charset_collate();
	dbDelta(
		"CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			row_hash char(32) NOT NULL,
			day date NOT NULL,
			event_type varchar(24) NOT NULL,
			event_key varchar(80) NOT NULL DEFAULT '',
			page_path varchar(190) NOT NULL DEFAULT '',
			page_id bigint(20) unsigned NOT NULL DEFAULT 0,
			page_type varchar(20) NOT NULL DEFAULT '',
			hits int(10) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY row_hash (row_hash),
			KEY day_type (day,event_type),
			KEY type_key (event_type,event_key)
		) {$charset};"
	);
	update_option( 'ri_db_version', RI_VERSION, false );
}

/**
 * Per (day, type) row cap: bounds database growth even under abuse.
 *
 * @return int
 */
function ri_row_cap() {
	return (int) apply_filters( 'rivermere_insights_row_cap', 400 );
}

/**
 * Increment a daily counter.
 *
 * @param string $type  Event type (see ri_event_types()).
 * @param string $key   Event label (already sanitised).
 * @param string $path  Normalised page path.
 * @param int    $id    Page ID (0 if none).
 * @param string $ptype Page type.
 * @return bool
 */
function ri_record( $type, $key = '', $path = '', $id = 0, $ptype = '' ) {
	global $wpdb;
	if ( ! isset( ri_event_types()[ $type ] ) ) {
		return false;
	}
	$table = ri_table();
	$day   = gmdate( 'Y-m-d' );
	$key   = substr( $key, 0, 80 );
	$path  = substr( $path, 0, 190 );
	$hash  = md5( $day . '|' . $type . '|' . $key . '|' . $path );

	$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE row_hash = %s", $hash ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	if ( ! $exists ) {
		$rows = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE day = %s AND event_type = %s", $day, $type ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $rows >= ri_row_cap() ) {
			// Cardinality guard: fold excess distinct labels/paths into one bucket.
			$key  = '(other)';
			$path = '';
			$id   = 0;
			$hash = md5( $day . '|' . $type . '|' . $key . '|' . $path );
		}
	}
	$result = $wpdb->query(
		$wpdb->prepare(
			"INSERT INTO {$table} (row_hash, day, event_type, event_key, page_path, page_id, page_type, hits) VALUES (%s, %s, %s, %s, %s, %d, %s, 1)
			 ON DUPLICATE KEY UPDATE hits = hits + 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$hash,
			$day,
			$type,
			$key,
			$path,
			(int) $id,
			substr( $ptype, 0, 20 )
		)
	);
	return false !== $result;
}

/**
 * Retention cleanup.
 *
 * @return int Rows deleted.
 */
function ri_cleanup() {
	global $wpdb;
	$days   = (int) ri_settings()['retention_days'];
	$cutoff = gmdate( 'Y-m-d', time() - $days * DAY_IN_SECONDS );
	$table  = ri_table();
	return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE day < %s", $cutoff ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}
