<?php
/**
 * Search form (theme-owned so the markup is accessible and valid).
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$rm_uid = wp_unique_id( 'search-form-' );
?>
<form role="search" class="search-form" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $rm_uid ); ?>"><?php esc_html_e( 'Search for:', 'rivermere-market' ); ?></label>
	<div class="search-form__row">
		<input class="field__control" type="search" id="<?php echo esc_attr( $rm_uid ); ?>" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search vendors, events, products…', 'rivermere-market' ); ?>" required>
		<button type="submit" class="btn btn--secondary"><span><?php esc_html_e( 'Search', 'rivermere-market' ); ?></span></button>
	</div>
</form>
