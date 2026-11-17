<?php
/**
 * One-time / maintenance helper: write every PHP-registered ("local") ACF field group to Local JSON.
 * Run: wp eval-file tools/acf-export.php
 * Theme groups -> theme/acf-json, plugin groups (group_rmc_*) -> plugin/acf-json.
 */
$theme  = get_template_directory() . '/acf-json';
$plugin = WP_PLUGIN_DIR . '/rivermere-core/acf-json';
foreach ( acf_get_local_field_groups() as $group ) {
	$key  = $group['key'];
	$dest = 0 === strpos( $key, 'group_rmc_' ) ? $plugin : $theme;
	acf_update_setting( 'save_json', $dest );
	$full           = acf_get_field_group( $key );
	$full['fields'] = acf_get_fields( $key );
	$full['modified'] = time();
	acf_write_json_field_group( $full );
	echo "wrote $key -> $dest\n";
}
