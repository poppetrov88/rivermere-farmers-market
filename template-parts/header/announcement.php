<?php
/**
 * Optional announcement bar.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

if ( ! rivermere_toggle( 'announcement_enabled', false ) ) {
	return;
}
$text = rivermere_option( 'announcement_text', '' );
if ( '' === $text ) {
	return;
}
$link = rivermere_option( 'announcement_link', array() );
?>
<div class="announcement">
	<div class="container announcement__inner">
		<p><?php echo esc_html( $text ); ?>
			<?php if ( ! empty( $link['url'] ) ) : ?>
				<a href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_html( $link['title'] ? $link['title'] : __( 'Learn more', 'rivermere-market' ) ); ?></a>
			<?php endif; ?>
		</p>
	</div>
</div>
