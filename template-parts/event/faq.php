<?php
/**
 * Event FAQ (native details/summary: keyboard and screen-reader friendly).
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$faqs = array_filter(
	$args['ev']['faqs'],
	static function ( $faq ) {
		return ! empty( $faq['question'] ) && ! empty( $faq['answer'] );
	}
);
if ( ! $faqs ) {
	return;
}
?>
<section class="event-section" aria-labelledby="event-faq-title">
	<h2 id="event-faq-title"><?php esc_html_e( 'Frequently asked questions', 'rivermere-market' ); ?></h2>
	<div class="faq">
		<?php foreach ( $faqs as $faq ) : ?>
			<details class="faq__item">
				<summary class="faq__question"><?php echo esc_html( $faq['question'] ); ?><?php echo rivermere_icon( 'chevron', 'faq__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></summary>
				<div class="faq__answer entry-content"><?php echo wp_kses_post( $faq['answer'] ); ?></div>
			</details>
		<?php endforeach; ?>
	</div>
</section>
