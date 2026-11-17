<?php
/**
 * ACF field groups for the theme (Page Builder, Theme Settings, Category icon).
 *
 * Source of truth: Local JSON in /acf-json (ACF loads and saves there automatically for the
 * active theme). Because the JSON files are also imported into the database, the groups are
 * listed and editable under ACF → Field Groups; every edit is written back to /acf-json and
 * should be committed. Field keys are stable (field_rm_*, group_rm_*) — never change them,
 * existing content is stored against these keys.
 *
 * Why this replaced PHP registration: groups registered with acf_add_local_field_group() are
 * "local only" — they render in the editor but never appear in the Field Groups admin list.
 * The original PHP definitions are in git history (commit a9bdadd..fda993a) and can be
 * regenerated to JSON with tools/acf-export.php.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hide the content editor on the front page: it is built with the page builder.
 */
function rivermere_maybe_remove_editor() {
	$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $post_id && (int) get_option( 'page_on_front' ) === $post_id ) {
		remove_post_type_support( 'page', 'editor' );
	}
}
add_action( 'admin_init', 'rivermere_maybe_remove_editor' );

/**
 * Keep the icon dropdowns in sync with the icon library (rivermere_icons()) at runtime,
 * even though the JSON stores a snapshot of the choices.
 *
 * @param array $field ACF field.
 * @return array
 */
function rivermere_sync_icon_choices( $field ) {
	if ( ! is_admin() || 'select' !== $field['type'] ) {
		return $field;
	}
	if ( in_array( $field['name'], array( 'icon', 'category_icon' ), true ) ) {
		$field['choices'] = rivermere_icon_choices();
	}
	return $field;
}
add_filter( 'acf/load_field', 'rivermere_sync_icon_choices' );
