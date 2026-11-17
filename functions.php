<?php
/**
 * Rivermere Market theme bootstrap.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

define( 'RIVERMERE_VERSION', wp_get_theme()->get( 'Version' ) );
define( 'RIVERMERE_DIR', get_template_directory() );
define( 'RIVERMERE_URI', get_template_directory_uri() );

$rivermere_includes = array(
	'helpers',
	'theme-setup',
	'assets',
	'navigation',
	'acf-fields',
	'acf-options',
	'template-functions',
);

foreach ( $rivermere_includes as $rivermere_include ) {
	require_once RIVERMERE_DIR . '/inc/' . $rivermere_include . '.php';
}
unset( $rivermere_includes, $rivermere_include );
