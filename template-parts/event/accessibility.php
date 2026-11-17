<?php
/**
 * Event accessibility information.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$text = $args['ev']['accessibility'];
if ( '' === trim( $text ) ) {
	return;
}
?>
<section class="event-section event-section--panel" aria-labelledby="event-access-title">
	<h2 id="event-access-title"><?php esc_html_e( 'Accessibility information', 'rivermere-market' ); ?></h2>
	<div class="entry-content"><?php echo wp_kses_post( $text ); ?></div>
</section>
