<?php
/**
 * ACF field groups for Vendors and Events. Source of truth: plugin /acf-json (Local JSON), imported
 * into the database so they are editable under ACF → Field Groups. Independent of the theme.
 *
 * @package Rivermere_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Load the plugin's Local JSON, and save edits of the plugin's groups back to it
 * (the theme's groups keep saving to the theme's acf-json folder).
 *
 * @param array $paths Load paths.
 * @return array
 */
function rivermere_core_acf_load_json( $paths ) {
	$paths[] = RIVERMERE_CORE_DIR . 'acf-json';
	return $paths;
}
add_filter( 'acf/settings/load_json', 'rivermere_core_acf_load_json' );

/**
 * Per-group save path.
 */
foreach ( array( 'group_rmc_vendor', 'group_rmc_event' ) as $rivermere_core_group_key ) {
	add_filter(
		'acf/settings/save_json/key=' . $rivermere_core_group_key,
		static function () {
			return RIVERMERE_CORE_DIR . 'acf-json';
		}
	);
}
unset( $rivermere_core_group_key );

/**
 * Sync Local JSON into the database so groups show in ACF → Field Groups: imports missing groups
 * and updates DB copies whose JSON file is newer (same as clicking "Sync" in the ACF UI).
 *
 * @return string[] Log.
 */
function rivermere_core_acf_sync() {
	$log = array();
	if ( ! function_exists( 'acf_get_local_json_files' ) ) {
		return array( 'ACF Pro is not active.' );
	}
	foreach ( acf_get_local_json_files( 'acf-field-group' ) as $key => $file ) {
		$json = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( ! is_array( $json ) ) {
			$log[] = "$key: invalid JSON";
			continue;
		}
		$post = acf_get_field_group_post( $key );
		if ( ! $post ) {
			$json['ID'] = 0;
			acf_import_field_group( $json );
			$log[] = "$key imported";
		} elseif ( ! empty( $json['modified'] ) && (int) $json['modified'] > strtotime( $post->post_modified_gmt . ' UTC' ) ) {
			$json['ID'] = $post->ID;
			acf_import_field_group( $json );
			$log[] = "$key updated from JSON";
		} else {
			$log[] = "$key up to date";
		}
	}
	return $log;
}

/**
 * Validate that an event does not end before it starts.
 *
 * @param mixed $valid Validity.
 * @param mixed $value End value.
 * @return mixed
 */
function rivermere_core_validate_event_end( $valid, $value ) {
	if ( true !== $valid || '' === $value ) {
		return $valid;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- ACF verifies its nonce before validation.
	$start = isset( $_POST['acf']['field_rmc_event_start'] ) ? sanitize_text_field( wp_unslash( $_POST['acf']['field_rmc_event_start'] ) ) : '';
	if ( $start && strtotime( $value ) < strtotime( $start ) ) {
		return __( 'The end time must be after the start time.', 'rivermere-core' );
	}
	return $valid;
}
add_filter( 'acf/validate_value/key=field_rmc_event_end', 'rivermere_core_validate_event_end', 10, 2 );
