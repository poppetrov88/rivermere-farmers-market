<?php
/**
 * Section: Upcoming event banner.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$label  = rivermere_sub( 'label', __( 'Upcoming Event', 'rivermere-market' ) );
$btn    = rivermere_sub( 'button_label', __( 'Event Details', 'rivermere-market' ) );
$source = rivermere_sub( 'source', 'next' );
$event  = null;

if ( 'manual' === $source && rivermere_sub( 'event' ) ) {
	$event = get_post( (int) rivermere_sub( 'event' ) );
	$event = ( $event && 'publish' === $event->post_status ) ? $event : null;
} else {
	$events = rivermere_get_upcoming_events( 1 );
	$event  = $events ? $events[0] : null;
}
if ( ! $event ) {
	return;
}

$times    = rivermere_event_times( $event->ID );
$tagline  = rivermere_field( 'event_tagline', $event->ID, '' );
$location = rivermere_field( 'event_location', $event->ID, '' );
$url      = get_permalink( $event );
$thumb    = get_the_post_thumbnail( $event->ID, 'rivermere-card', array( 'class' => 'event-banner__img', 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async' ) );
$start    = $times['start'];
$end      = $times['end'];
$time_fmt = get_option( 'time_format' );
?>
<section class="section section--flow section--event" aria-labelledby="event-banner-title">
	<div class="container">
		<article class="event-banner">
			<?php if ( $thumb ) : ?>
				<div class="event-banner__media"><?php echo $thumb; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<?php endif; ?>
			<div class="event-banner__main">
				<p class="eyebrow eyebrow--accent"><?php echo rivermere_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $label ); ?></p>
				<h2 class="event-banner__title" id="event-banner-title"><?php echo esc_html( get_the_title( $event ) ); ?></h2>
				<?php if ( $tagline ) : ?>
					<p class="event-banner__tagline"><?php echo esc_html( $tagline ); ?></p>
				<?php endif; ?>
			</div>
			<div class="event-banner__when">
				<?php if ( $start ) : ?>
					<p><?php echo rivermere_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <time datetime="<?php echo esc_attr( $start->format( 'c' ) ); ?>"><?php echo esc_html( wp_date( 'D, M j', $start->getTimestamp() ) ); ?></time></p>
					<p><?php echo rivermere_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><time datetime="<?php echo esc_attr( $start->format( 'c' ) ); ?>"><?php echo esc_html( wp_date( $time_fmt, $start->getTimestamp() ) ); ?></time><?php if ( $end ) : ?> – <time datetime="<?php echo esc_attr( $end->format( 'c' ) ); ?>"><?php echo esc_html( wp_date( $time_fmt, $end->getTimestamp() ) ); ?></time><?php endif; ?></span></p>
				<?php endif; ?>
				<?php if ( $location && ! $start ) : ?>
					<p><?php echo rivermere_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $location ); ?></p>
				<?php endif; ?>
			</div>
			<div class="event-banner__cta">
				<?php echo rivermere_button( array( 'url' => $url, 'title' => $btn ), 'accent', array( 'class' => 'btn--sm' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</article>
	</div>
</section>
