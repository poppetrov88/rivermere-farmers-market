<?php
/**
 * How it works: numbered steps.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$id    = $args['post_id'];
$steps = array_filter( (array) rivermere_field( 'vp_steps', $id, array() ), static function ( $s ) {
	return ! empty( $s['title'] );
} );
if ( ! $steps ) {
	return;
}
?>
<section class="section section--flow steps-section" id="how-it-works" aria-labelledby="steps-title">
	<div class="container">
		<h2 class="section-heading" id="steps-title"><?php echo esc_html( rivermere_field( 'vp_steps_title', $id, __( 'How It Works', 'rivermere-market' ) ) ); ?></h2>
		<ol class="steps">
			<?php foreach ( $steps as $i => $step ) : ?>
				<li class="steps__item">
					<span class="steps__num" aria-hidden="true"><?php echo (int) $i + 1; ?></span>
					<h3 class="steps__title"><span class="screen-reader-text"><?php echo esc_html( sprintf( /* translators: %d: step number */ __( 'Step %d:', 'rivermere-market' ), $i + 1 ) ); ?> </span><?php echo esc_html( $step['title'] ); ?></h3>
					<?php if ( ! empty( $step['text'] ) ) : ?>
						<p><?php echo esc_html( $step['text'] ); ?></p>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
