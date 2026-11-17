<?php
/**
 * Header: brand, navigation, search trigger, CTA, mobile toggle.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$cta         = rivermere_toggle( 'header_cta_show', true ) ? rivermere_option( 'header_cta', array() ) : array();
$show_search = rivermere_toggle( 'header_search', true );
?>
<div class="container site-header__inner">
	<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: market name */ __( '%s – home', 'rivermere-market' ), rivermere_market_name() ) ); ?>">
		<?php rivermere_logo(); ?>
	</a>

	<nav class="primary-nav" id="primary-nav" aria-label="<?php esc_attr_e( 'Primary', 'rivermere-market' ); ?>">
		<?php rivermere_menu( 'primary', array( 'menu_id' => 'primary-menu' ) ); ?>
		<?php if ( ! empty( $cta['url'] ) ) : ?>
			<div class="primary-nav__cta">
				<?php echo rivermere_button( $cta, 'secondary', array( 'class' => 'btn--sm' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		<?php endif; ?>
	</nav>

	<div class="site-header__actions">
		<?php if ( $show_search ) : ?>
			<button type="button" class="icon-btn" data-search-open aria-haspopup="dialog">
				<?php echo rivermere_icon( 'search' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span class="screen-reader-text"><?php esc_html_e( 'Search', 'rivermere-market' ); ?></span>
			</button>
		<?php endif; ?>
		<?php if ( ! empty( $cta['url'] ) ) : ?>
			<span class="site-header__cta">
				<?php echo rivermere_button( $cta, 'secondary', array( 'class' => 'btn--sm' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</span>
		<?php endif; ?>
		<button type="button" class="icon-btn nav-toggle" aria-expanded="false" aria-controls="primary-nav" data-nav-toggle>
			<?php echo rivermere_icon( 'menu', 'nav-toggle__open' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php echo rivermere_icon( 'close', 'nav-toggle__close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'rivermere-market' ); ?></span>
		</button>
	</div>
</div>
