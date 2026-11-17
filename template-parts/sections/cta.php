<?php
/**
 * Section: Call to action.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$heading = rivermere_sub( 'heading' );
if ( ! $heading ) {
	return;
}
$text = rivermere_sub( 'text' );
$link = rivermere_sub( 'link', array() );
?>
<section class="section section--cta" aria-labelledby="cta-title-<?php echo esc_attr( (string) get_row_index() ); ?>">
	<div class="container">
		<div class="cta-block">
			<h2 class="cta-block__title" id="cta-title-<?php echo esc_attr( (string) get_row_index() ); ?>"><?php echo esc_html( $heading ); ?></h2>
			<?php if ( $text ) : ?>
				<p><?php echo esc_html( $text ); ?></p>
			<?php endif; ?>
			<?php echo rivermere_button( $link, 'accent' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</div>
</section>
