<?php
/**
 * Event card (date badge, title, location).
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$event = isset( $args['post'] ) ? $args['post'] : get_post();
if ( ! $event ) {
	return;
}
$times    = rivermere_event_times( $event->ID );
$location = rivermere_field( 'event_location', $event->ID, '' );
$tagline  = rivermere_field( 'event_tagline', $event->ID, '' );
$start    = $times['start'];
$tag      = isset( $args['heading'] ) && in_array( $args['heading'], array( 'h2', 'h3' ), true ) ? $args['heading'] : 'h3';
?>
<article class="event-card-sm">
	<a class="event-card-sm__link" href="<?php echo esc_url( get_permalink( $event ) ); ?>">
		<?php if ( $start ) : ?>
			<time class="event-card-sm__date" datetime="<?php echo esc_attr( $start->format( 'c' ) ); ?>">
				<span class="event-card-sm__month"><?php echo esc_html( wp_date( 'M', $start->getTimestamp() ) ); ?></span>
				<span class="event-card-sm__day"><?php echo esc_html( wp_date( 'j', $start->getTimestamp() ) ); ?></span>
			</time>
		<?php endif; ?>
		<div class="event-card-sm__body">
			<<?php echo esc_html( $tag ); ?> class="event-card-sm__title"><?php echo esc_html( get_the_title( $event ) ); ?></<?php echo esc_html( $tag ); ?>>
			<?php if ( $tagline ) : ?>
				<p class="event-card-sm__tagline"><?php echo esc_html( $tagline ); ?></p>
			<?php endif; ?>
			<?php if ( $location ) : ?>
				<p class="event-card-sm__meta"><?php echo rivermere_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <?php echo esc_html( $location ); ?></p>
			<?php endif; ?>
		</div>
		<?php echo rivermere_icon( 'arrow', 'event-card-sm__arrow' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</a>
</article>
