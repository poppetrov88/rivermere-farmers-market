<?php
/**
 * Single event details block.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$times    = rivermere_event_times( get_the_ID() );
$location = rivermere_field( 'event_location', get_the_ID(), '' );
$reg      = rivermere_field( 'event_registration_url', get_the_ID(), '' );
$fmt      = get_option( 'date_format' );
$tfmt     = get_option( 'time_format' );
?>
<?php if ( has_post_thumbnail() ) : ?>
	<div class="entry__thumb"><?php the_post_thumbnail( 'rivermere-feature', array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ); ?></div>
<?php endif; ?>
<dl class="meta-list">
	<?php if ( $times['start'] ) : ?>
		<div>
			<dt><?php esc_html_e( 'When', 'rivermere-market' ); ?></dt>
			<dd>
				<time datetime="<?php echo esc_attr( $times['start']->format( 'c' ) ); ?>"><?php echo esc_html( wp_date( $fmt . ', ' . $tfmt, $times['start']->getTimestamp() ) ); ?></time>
				<?php if ( $times['end'] ) : ?>
					– <time datetime="<?php echo esc_attr( $times['end']->format( 'c' ) ); ?>"><?php echo esc_html( wp_date( $tfmt, $times['end']->getTimestamp() ) ); ?></time>
				<?php endif; ?>
			</dd>
		</div>
	<?php endif; ?>
	<?php if ( $location ) : ?>
		<div><dt><?php esc_html_e( 'Where', 'rivermere-market' ); ?></dt><dd><?php echo esc_html( $location ); ?></dd></div>
	<?php endif; ?>
</dl>
<?php if ( $reg ) : ?>
	<p><?php echo rivermere_button( array( 'url' => $reg, 'title' => __( 'Register', 'rivermere-market' ), 'target' => '_blank' ), 'secondary' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
<?php endif; ?>
