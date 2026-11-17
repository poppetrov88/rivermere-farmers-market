<?php
/**
 * Community CTA: one card for visitors, one for vendors (links to the Become a Vendor page).
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$id    = $args['post_id'];
$title = rivermere_field( 'ap_cta_title', $id, '' );
if ( ! $title ) {
	return;
}
$visitor_url = $args['market_url'] ? $args['market_url'] : $args['vendors_url'];
?>
<section class="section section--flow community-cta" aria-labelledby="community-cta-title">
	<div class="container">
		<h2 class="section-heading" id="community-cta-title"><?php echo esc_html( $title ); ?></h2>
		<div class="community-cta__grid">
			<article class="community-card">
				<span class="community-card__icon"><?php echo rivermere_icon( 'basket' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<h3><?php echo esc_html( rivermere_field( 'ap_cta_visitor_title', $id, __( 'For visitors', 'rivermere-market' ) ) ); ?></h3>
				<p><?php echo esc_html( rivermere_field( 'ap_cta_visitor_text', $id, '' ) ); ?></p>
				<?php echo rivermere_button( array( 'url' => $visitor_url, 'title' => rivermere_field( 'ap_cta_visitor_label', $id, __( 'Explore the Market', 'rivermere-market' ) ) ), 'secondary' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</article>
			<article class="community-card community-card--vendor">
				<span class="community-card__icon"><?php echo rivermere_icon( 'plant' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<h3><?php echo esc_html( rivermere_field( 'ap_cta_vendor_title', $id, __( 'For vendors', 'rivermere-market' ) ) ); ?></h3>
				<p><?php echo esc_html( rivermere_field( 'ap_cta_vendor_text', $id, '' ) ); ?></p>
				<?php echo rivermere_button( array( 'url' => rivermere_vendor_page_url(), 'title' => rivermere_field( 'ap_cta_vendor_label', $id, __( 'Become a Vendor', 'rivermere-market' ) ) ), 'accent' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</article>
		</div>
	</div>
</section>
