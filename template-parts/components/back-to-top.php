<?php
/**
 * Back-to-top button (footer).
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

if ( ! rivermere_toggle( 'ui_back_to_top', true ) ) {
	return;
}
?>
<button type="button" class="back-to-top" data-back-to-top>
	<?php echo rivermere_icon( 'arrow-up', 'back-to-top__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<span><?php esc_html_e( 'Back to top', 'rivermere-market' ); ?></span>
</button>
