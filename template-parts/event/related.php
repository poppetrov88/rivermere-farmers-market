<?php
/**
 * Related events + closing CTA.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$ev      = $args['ev'];
$related = rivermere_get_upcoming_events( 3, get_the_ID() );
?>
<?php if ( $related ) : ?>
	<section class="section section--related" aria-labelledby="related-title">
		<div class="container">
			<h2 class="section-heading" id="related-title"><?php esc_html_e( 'More events at the market', 'rivermere-market' ); ?></h2>
			<ul class="event-grid">
				<?php foreach ( $related as $event ) : ?>
					<li><?php get_template_part( 'template-parts/components/event-card', null, array( 'post' => $event ) ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
<?php endif; ?>

<section class="section section--cta event-cta" aria-labelledby="event-cta-title">
	<div class="container">
		<div class="cta-block">
			<h2 class="cta-block__title" id="event-cta-title"><?php echo esc_html( sprintf( /* translators: %s: event title */ __( 'Plan your visit to %s', 'rivermere-market' ), get_the_title() ) ); ?></h2>
			<p><?php esc_html_e( 'Save the date, bring a friend and come hungry.', 'rivermere-market' ); ?></p>
			<div class="cta-block__actions">
				<?php if ( $ev['times']['start'] ) : ?>
					<?php echo rivermere_button( array( 'url' => rivermere_event_ics_url( get_the_ID() ), 'title' => __( 'Add to calendar', 'rivermere-market' ) ), 'accent', array( 'icon' => 'calendar' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endif; ?>
				<?php echo rivermere_button( array( 'url' => get_post_type_archive_link( 'rm_event' ), 'title' => __( 'All events', 'rivermere-market' ) ), 'outline-light', array( 'icon' => false ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
		</div>
	</div>
</section>
