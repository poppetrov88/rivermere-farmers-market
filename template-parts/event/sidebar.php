<?php
/**
 * Event information card (sticky on desktop).
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$ev    = $args['ev'];
$id    = get_the_ID();
$start = $ev['times']['start'];
$end   = $ev['times']['end'];
$tfmt  = get_option( 'time_format' );
$url   = get_permalink( $id );
$title = get_the_title( $id );

$share = array(
	'facebook' => array( __( 'Facebook', 'rivermere-market' ), 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url ) ),
	'linkedin' => array( __( 'LinkedIn', 'rivermere-market' ), 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( $url ) ),
	'mail'     => array( __( 'Email', 'rivermere-market' ), 'mailto:?subject=' . rawurlencode( $title ) . '&body=' . rawurlencode( $url ) ),
);
?>
<aside class="event-sidebar" aria-labelledby="event-info-title">
	<div class="event-card">
		<h2 class="event-card__title" id="event-info-title"><?php esc_html_e( 'Event information', 'rivermere-market' ); ?></h2>

		<dl class="event-card__list">
			<?php if ( $start ) : ?>
				<div class="event-card__row">
					<dt><?php echo rivermere_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Date', 'rivermere-market' ); ?></dt>
					<dd><time datetime="<?php echo esc_attr( $start->format( 'Y-m-d' ) ); ?>"><?php echo esc_html( wp_date( 'l, F j, Y', $start->getTimestamp() ) ); ?></time></dd>
				</div>
				<div class="event-card__row">
					<dt><?php echo rivermere_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Starts', 'rivermere-market' ); ?></dt>
					<dd><time datetime="<?php echo esc_attr( $start->format( 'c' ) ); ?>"><?php echo esc_html( wp_date( $tfmt, $start->getTimestamp() ) ); ?></time></dd>
				</div>
			<?php endif; ?>
			<?php if ( $end ) : ?>
				<div class="event-card__row">
					<dt><?php echo rivermere_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Ends', 'rivermere-market' ); ?></dt>
					<dd><time datetime="<?php echo esc_attr( $end->format( 'c' ) ); ?>"><?php echo esc_html( wp_date( $tfmt, $end->getTimestamp() ) ); ?></time></dd>
				</div>
			<?php endif; ?>
			<?php if ( $ev['location'] || $ev['address'] ) : ?>
				<div class="event-card__row">
					<dt><?php echo rivermere_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Location', 'rivermere-market' ); ?></dt>
					<dd>
						<?php if ( $ev['location'] ) : ?>
							<strong><?php echo esc_html( $ev['location'] ); ?></strong>
						<?php endif; ?>
						<?php if ( $ev['address'] ) : ?>
							<address><?php echo wp_kses( $ev['address'], array( 'br' => array() ) ); ?></address>
						<?php endif; ?>
					</dd>
				</div>
			<?php endif; ?>
			<?php if ( $ev['admission'] ) : ?>
				<div class="event-card__row">
					<dt><?php echo rivermere_icon( 'basket' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Admission', 'rivermere-market' ); ?></dt>
					<dd><?php echo esc_html( $ev['admission'] ); ?></dd>
				</div>
			<?php endif; ?>
			<?php if ( $ev['organizer'] ) : ?>
				<div class="event-card__row">
					<dt><?php echo rivermere_icon( 'people' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Organizer', 'rivermere-market' ); ?></dt>
					<dd><?php echo esc_html( $ev['organizer'] ); ?></dd>
				</div>
			<?php endif; ?>
			<?php if ( $ev['email'] || $ev['phone'] ) : ?>
				<div class="event-card__row">
					<dt><?php echo rivermere_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Contact', 'rivermere-market' ); ?></dt>
					<dd>
						<?php if ( is_email( $ev['email'] ) ) : ?>
							<a href="mailto:<?php echo esc_attr( $ev['email'] ); ?>"><?php echo esc_html( $ev['email'] ); ?></a><br>
						<?php endif; ?>
						<?php if ( $ev['phone'] ) : ?>
							<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $ev['phone'] ) ); ?>"><?php echo esc_html( $ev['phone'] ); ?></a>
						<?php endif; ?>
					</dd>
				</div>
			<?php endif; ?>
		</dl>

		<div class="event-card__actions">
			<?php if ( $ev['registration'] ) : ?>
				<?php echo rivermere_button( array( 'url' => $ev['registration'], 'title' => __( 'Register', 'rivermere-market' ), 'target' => '_blank' ), 'accent', array( 'class' => 'btn--block' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
			<?php if ( $ev['directions'] ) : ?>
				<?php echo rivermere_button( array( 'url' => $ev['directions'], 'title' => __( 'Get directions', 'rivermere-market' ), 'target' => '_blank' ), 'secondary', array( 'icon' => 'pin', 'class' => 'btn--block' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
			<?php if ( $start ) : ?>
				<?php echo rivermere_button( array( 'url' => rivermere_event_ics_url( $id ), 'title' => __( 'Add to calendar', 'rivermere-market' ) ), 'outline', array( 'icon' => 'calendar', 'class' => 'btn--block' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
		</div>

		<div class="event-card__share">
			<h3 class="event-card__share-title"><?php esc_html_e( 'Share this event', 'rivermere-market' ); ?></h3>
			<ul class="share-list">
				<?php foreach ( $share as $key => $item ) : ?>
					<li>
						<a class="share-list__link" href="<?php echo esc_url( $item[1], array( 'https', 'mailto' ) ); ?>"<?php echo 'mail' === $key ? '' : ' target="_blank" rel="noopener noreferrer"'; ?>>
							<?php echo rivermere_icon( 'mail' === $key ? 'mail' : $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<span class="screen-reader-text"><?php echo esc_html( sprintf( /* translators: %s: network */ __( 'Share on %s', 'rivermere-market' ), $item[0] ) ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
				<li>
					<button type="button" class="share-list__link" data-copy-link="<?php echo esc_url( $url ); ?>" data-copied="<?php esc_attr_e( 'Link copied to clipboard', 'rivermere-market' ); ?>">
						<?php echo rivermere_icon( 'external' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span class="screen-reader-text"><?php esc_html_e( 'Copy link', 'rivermere-market' ); ?></span>
					</button>
				</li>
			</ul>
		</div>
	</div>
</aside>
