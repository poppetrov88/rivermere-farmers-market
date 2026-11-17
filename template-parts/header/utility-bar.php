<?php
/**
 * Slim utility bar above the header: market days (left), social + language (right).
 * Keeps the main header identical to the reference design.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$show_social = rivermere_toggle( 'header_social', true );
$show_lang   = rivermere_toggle( 'header_language', true );
if ( ! $show_social && ! $show_lang ) {
	return;
}
$days = rivermere_option( 'market_days', '' );
?>
<div class="utility-bar">
	<div class="container utility-bar__inner">
		<p class="utility-bar__text">
			<?php if ( $days ) : ?>
				<?php echo rivermere_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span><?php echo esc_html( $days ); ?></span>
			<?php endif; ?>
		</p>
		<div class="utility-bar__group">
			<?php
			if ( $show_social ) {
				get_template_part( 'template-parts/components/social-links', null, array( 'class' => 'social-list--header' ) );
			}
			if ( $show_lang ) {
				get_template_part( 'template-parts/components/language-switcher' );
			}
			?>
		</div>
	</div>
</div>
