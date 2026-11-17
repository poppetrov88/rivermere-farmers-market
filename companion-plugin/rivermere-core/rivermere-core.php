<?php
/**
 * Plugin Name:       Rivermere Core
 * Description:       Content model (Vendors, Events, Product Categories), newsletter subscribers and SEO integration for the Rivermere Market theme. Keeps content available if the theme changes.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            Rivermere
 * License:           GPL-2.0-or-later
 * Text Domain:       rivermere-core
 *
 * @package Rivermere_Core
 */

defined( 'ABSPATH' ) || exit;

define( 'RIVERMERE_CORE_VERSION', '1.0.0' );
define( 'RIVERMERE_CORE_FILE', __FILE__ );
define( 'RIVERMERE_CORE_DIR', plugin_dir_path( __FILE__ ) );

require_once RIVERMERE_CORE_DIR . 'includes/post-types.php';
require_once RIVERMERE_CORE_DIR . 'includes/acf-cpt-fields.php';
require_once RIVERMERE_CORE_DIR . 'includes/event-ics.php';
require_once RIVERMERE_CORE_DIR . 'includes/vendor-applications.php';
require_once RIVERMERE_CORE_DIR . 'includes/newsletter.php';
require_once RIVERMERE_CORE_DIR . 'includes/newsletter-admin.php';
require_once RIVERMERE_CORE_DIR . 'includes/seo-integration.php';
require_once RIVERMERE_CORE_DIR . 'includes/demo-import.php';
require_once RIVERMERE_CORE_DIR . 'includes/demo-legal.php';
require_once RIVERMERE_CORE_DIR . 'includes/demo-event.php';
require_once RIVERMERE_CORE_DIR . 'includes/demo-phase4.php';

/**
 * Activation: create tables and flush rewrite rules once post types exist.
 */
function rivermere_core_activate() {
	rivermere_core_register_post_types();
	rivermere_newsletter_install();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'rivermere_core_activate' );

/**
 * Deactivation: flush rewrite rules.
 */
function rivermere_core_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'rivermere_core_deactivate' );

/**
 * Upgrade routine for DB changes.
 */
function rivermere_core_maybe_upgrade() {
	if ( get_option( 'rivermere_core_db_version' ) !== RIVERMERE_CORE_VERSION ) {
		rivermere_newsletter_install();
		update_option( 'rivermere_core_db_version', RIVERMERE_CORE_VERSION );
	}
}
add_action( 'plugins_loaded', 'rivermere_core_maybe_upgrade' );
