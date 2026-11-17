<?php
/**
 * Plugin Name:       Rivermere Insights
 * Description:       Privacy-minded, consent-aware analytics for the Rivermere Market theme: page views, key clicks and confirmed conversions, stored as daily aggregates in your own database.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Rivermere
 * License:           GPL-2.0-or-later
 * Text Domain:       rivermere-insights
 *
 * @package Rivermere_Insights
 */

defined( 'ABSPATH' ) || exit;

define( 'RI_VERSION', '1.0.0' );
define( 'RI_FILE', __FILE__ );
define( 'RI_DIR', plugin_dir_path( __FILE__ ) );
define( 'RI_URL', plugin_dir_url( __FILE__ ) );

require_once RI_DIR . 'includes/settings.php';
require_once RI_DIR . 'includes/db.php';
require_once RI_DIR . 'includes/consent.php';
require_once RI_DIR . 'includes/collect.php';
require_once RI_DIR . 'includes/conversions.php';
require_once RI_DIR . 'includes/frontend.php';
require_once RI_DIR . 'includes/queries.php';
require_once RI_DIR . 'includes/admin.php';
require_once RI_DIR . 'includes/export.php';

/**
 * Activation: tables + daily cleanup schedule.
 */
function ri_activate() {
	ri_db_install();
	if ( ! wp_next_scheduled( 'ri_cleanup_daily' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'ri_cleanup_daily' );
	}
}
register_activation_hook( __FILE__, 'ri_activate' );

/**
 * Deactivation: unschedule (data is kept; use Insights → Settings → Reset to delete it).
 */
function ri_deactivate() {
	wp_clear_scheduled_hook( 'ri_cleanup_daily' );
}
register_deactivation_hook( __FILE__, 'ri_deactivate' );

/**
 * Schema upgrades and cron self-heal.
 */
function ri_maybe_upgrade() {
	if ( get_option( 'ri_db_version' ) !== RI_VERSION ) {
		ri_db_install();
	}
	if ( ! wp_next_scheduled( 'ri_cleanup_daily' ) ) {
		wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'ri_cleanup_daily' );
	}
}
add_action( 'plugins_loaded', 'ri_maybe_upgrade' );
add_action( 'ri_cleanup_daily', 'ri_cleanup' );
