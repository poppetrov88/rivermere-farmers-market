<?php
/**
 * Vendor requirements, booth requirements and (optional) fee notice.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$id     = $args['post_id'];
$items  = array_filter( (array) rivermere_field( 'vp_requirements', $id, array() ), static function ( $r ) {
	return ! empty( $r['title'] );
} );
$booth  = (string) rivermere_option( 'apps_booth', '' );
$fee_on = rivermere_toggle( 'apps_fee_enabled', false );
$fee    = trim( (string) rivermere_option( 'apps_fee_amount', '' ) );
$fee_tx = trim( (string) rivermere_option( 'apps_fee_description', '' ) );
if ( ! $items && ! $booth && ! ( $fee_on && $fee ) ) {
	return;
}
?>
<section class="section section--flow requirements-section" id="requirements" aria-labelledby="requirements-title">
	<div class="container container--narrow">
		<header class="feature-section__head">
			<h2 class="feature-section__title" id="requirements-title"><?php echo esc_html( rivermere_field( 'vp_req_title', $id, __( 'Vendor Requirements', 'rivermere-market' ) ) ); ?></h2>
			<?php $intro = rivermere_field( 'vp_req_intro', $id, '' ); ?>
			<?php if ( $intro ) : ?>
				<p class="feature-section__intro"><?php echo esc_html( $intro ); ?></p>
			<?php endif; ?>
		</header>
		<?php if ( $items ) : ?>
			<ul class="req-list">
				<?php foreach ( $items as $item ) : ?>
					<li class="req-list__item">
						<span class="req-list__icon"><?php echo rivermere_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<div>
							<h3 class="req-list__title"><?php echo esc_html( $item['title'] ); ?></h3>
							<?php if ( ! empty( $item['text'] ) ) : ?>
								<p><?php echo esc_html( $item['text'] ); ?></p>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php if ( $booth ) : ?>
			<div class="notice req-note">
				<h3 class="req-note__title"><?php esc_html_e( 'Stall / booth requirements', 'rivermere-market' ); ?></h3>
				<p><?php echo nl2br( esc_html( $booth ) ); ?></p>
			</div>
		<?php endif; ?>
		<?php if ( $fee_on && $fee ) : ?>
			<div class="notice req-note">
				<h3 class="req-note__title"><?php esc_html_e( 'Application fee', 'rivermere-market' ); ?></h3>
				<p><?php echo esc_html( $fee . ( $fee_tx ? ' – ' . $fee_tx : '' ) ); ?></p>
			</div>
		<?php endif; ?>
	</div>
</section>
