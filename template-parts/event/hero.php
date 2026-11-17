<?php
/**
 * Event hero: breadcrumbs, label, title, intro, key facts and image.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$ev    = $args['ev'];
$id    = get_the_ID();
$start = $ev['times']['start'];
$end   = $ev['times']['end'];
$tfmt  = get_option( 'time_format' );
$image = ! empty( $ev['hero']['ID'] ) ? (int) $ev['hero']['ID'] : (int) get_post_thumbnail_id( $id );
$label = $ev['label'] ? $ev['label'] : __( 'Market event', 'rivermere-market' );
$badge = array(
	'cancelled' => __( 'Cancelled', 'rivermere-market' ),
	'postponed' => __( 'Postponed', 'rivermere-market' ),
);
?>
<header class="event-hero">
	<div class="container">
		<?php rivermere_breadcrumbs(); ?>
		<a class="event-hero__back" href="<?php echo esc_url( get_post_type_archive_link( 'rm_event' ) ); ?>">
			<?php echo rivermere_icon( 'arrow', 'event-hero__back-icon' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php esc_html_e( 'Back to all events', 'rivermere-market' ); ?>
		</a>

		<p class="eyebrow">
			<?php echo esc_html( $label ); ?>
			<?php if ( isset( $badge[ $ev['status'] ] ) ) : ?>
				<span class="badge badge--alert"><?php echo esc_html( $badge[ $ev['status'] ] ); ?></span>
			<?php endif; ?>
		</p>
		<h1 class="event-hero__title"><?php the_title(); ?></h1>
		<?php if ( $ev['intro'] ) : ?>
			<p class="event-hero__intro"><?php echo esc_html( $ev['intro'] ); ?></p>
		<?php endif; ?>

		<ul class="event-facts">
			<?php if ( $start ) : ?>
				<li>
					<?php echo rivermere_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><span class="screen-reader-text"><?php esc_html_e( 'Date:', 'rivermere-market' ); ?> </span><time datetime="<?php echo esc_attr( $start->format( 'Y-m-d' ) ); ?>"><?php echo esc_html( wp_date( 'l, F j, Y', $start->getTimestamp() ) ); ?></time></span>
				</li>
				<li>
					<?php echo rivermere_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><span class="screen-reader-text"><?php esc_html_e( 'Time:', 'rivermere-market' ); ?> </span><time datetime="<?php echo esc_attr( $start->format( 'c' ) ); ?>"><?php echo esc_html( wp_date( $tfmt, $start->getTimestamp() ) ); ?></time><?php if ( $end ) : ?> – <time datetime="<?php echo esc_attr( $end->format( 'c' ) ); ?>"><?php echo esc_html( wp_date( $tfmt, $end->getTimestamp() ) ); ?></time><?php endif; ?></span>
				</li>
			<?php endif; ?>
			<?php if ( $ev['location'] ) : ?>
				<li>
					<?php echo rivermere_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span><span class="screen-reader-text"><?php esc_html_e( 'Location:', 'rivermere-market' ); ?> </span><?php echo esc_html( $ev['location'] ); ?></span>
				</li>
			<?php endif; ?>
		</ul>

		<?php if ( $image ) : ?>
			<figure class="event-hero__media">
				<?php
				echo wp_get_attachment_image(
					$image,
					'rivermere-feature',
					false,
					array(
						'class'         => 'event-hero__img',
						'loading'       => 'eager',
						'fetchpriority' => 'high',
						'decoding'      => 'async',
						'sizes'         => '(min-width: 1280px) 1200px, 100vw',
					)
				);
				?>
			</figure>
		<?php endif; ?>
	</div>
</header>
