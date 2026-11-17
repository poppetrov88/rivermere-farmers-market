<?php
/**
 * FAQ accordion (native details/summary: keyboard and screen-reader friendly, no JavaScript).
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$faq       = $args['faq'];
$contained = ! empty( $args['contained'] );
$uid       = 'faq-' . wp_unique_id();
?>
<section class="faq-section<?php echo $contained ? ' faq-section--band' : ''; ?>" aria-labelledby="<?php echo esc_attr( $uid ); ?>" data-faq-context="<?php echo esc_attr( $faq['context'] ); ?>">
	<?php if ( $contained ) : ?>
	<div class="container container--narrow">
	<?php endif; ?>
		<header class="faq-section__head">
			<p class="eyebrow"><?php esc_html_e( 'FAQ', 'rivermere-market' ); ?></p>
			<h2 class="faq-section__title" id="<?php echo esc_attr( $uid ); ?>"><?php echo esc_html( $faq['title'] ); ?></h2>
			<?php if ( $faq['intro'] ) : ?>
				<p class="faq-section__intro"><?php echo esc_html( $faq['intro'] ); ?></p>
			<?php endif; ?>
		</header>
		<div class="faq">
			<?php foreach ( $faq['items'] as $item ) : ?>
				<details class="faq__item">
					<summary class="faq__question">
						<span><?php echo esc_html( $item['q'] ); ?></span>
						<span class="faq__icon-wrap" aria-hidden="true"><?php echo rivermere_icon( 'chevron', 'faq__icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					</summary>
					<div class="faq__answer entry-content"><?php echo wp_kses_post( wpautop( $item['a'] ) ); ?></div>
				</details>
			<?php endforeach; ?>
		</div>
	<?php if ( $contained ) : ?>
	</div>
	<?php endif; ?>
</section>
