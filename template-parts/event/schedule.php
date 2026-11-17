<?php
/**
 * Event schedule timeline.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$items = $args['ev']['schedule'];
if ( ! $items ) {
	return;
}
?>
<section class="event-section" aria-labelledby="event-schedule-title">
	<h2 id="event-schedule-title"><?php esc_html_e( 'Event schedule', 'rivermere-market' ); ?></h2>
	<ol class="timeline">
		<?php foreach ( $items as $item ) : ?>
			<li class="timeline__item">
				<span class="timeline__time"><?php echo esc_html( $item['time'] ?? '' ); ?></span>
				<div class="timeline__body">
					<h3 class="timeline__title"><?php echo esc_html( $item['title'] ?? '' ); ?></h3>
					<?php if ( ! empty( $item['description'] ) ) : ?>
						<p><?php echo esc_html( $item['description'] ); ?></p>
					<?php endif; ?>
				</div>
			</li>
		<?php endforeach; ?>
	</ol>
</section>
