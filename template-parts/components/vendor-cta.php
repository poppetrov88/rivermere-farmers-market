<?php
/**
 * "Sell at the market" CTA: always links to the Become a Vendor page (via the URL helper).
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="section section--flow section--cta vendor-cta" aria-labelledby="vendor-cta-heading">
	<div class="container">
		<div class="cta-block">
			<h2 class="cta-block__title" id="vendor-cta-heading"><?php esc_html_e( 'Want to sell at Rivermere?', 'rivermere-market' ); ?></h2>
			<p><?php esc_html_e( 'Local growers, bakers and makers are welcome to apply. Find out what we look for and how to apply.', 'rivermere-market' ); ?></p>
			<div class="cta-block__actions">
				<?php echo rivermere_button( array( 'url' => rivermere_vendor_page_url(), 'title' => __( 'Become a Vendor', 'rivermere-market' ) ), 'accent' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
	</div>
</section>
