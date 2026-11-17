<?php
/**
 * Full-screen search overlay (native <dialog>: focus trap, Esc, inert background, focus restore).
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

if ( ! rivermere_toggle( 'header_search', true ) ) {
	return;
}
?>
<dialog class="search-overlay" id="search-overlay" aria-labelledby="search-overlay-title">
	<div class="search-overlay__inner container">
		<button type="button" class="icon-btn search-overlay__close" data-search-close>
			<?php echo rivermere_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span class="screen-reader-text"><?php esc_html_e( 'Close search', 'rivermere-market' ); ?></span>
		</button>
		<form role="search" class="search-overlay__form" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
			<h2 class="search-overlay__title" id="search-overlay-title"><?php esc_html_e( 'Search the market', 'rivermere-market' ); ?></h2>
			<label class="screen-reader-text" for="search-overlay-input"><?php esc_html_e( 'Search for vendors, events and more', 'rivermere-market' ); ?></label>
			<div class="search-overlay__field">
				<input type="search" id="search-overlay-input" name="s" placeholder="<?php esc_attr_e( 'Search vendors, events, products…', 'rivermere-market' ); ?>" autocomplete="off" required>
				<button type="submit" class="btn btn--accent"><span><?php esc_html_e( 'Search', 'rivermere-market' ); ?></span></button>
			</div>
		</form>
	</div>
</dialog>
