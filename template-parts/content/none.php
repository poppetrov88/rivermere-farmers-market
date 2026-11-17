<?php
/**
 * No results.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="notice notice--info" role="status">
	<p><?php esc_html_e( 'Nothing found. Try a different search.', 'rivermere-market' ); ?></p>
</div>
<?php get_search_form(); ?>
