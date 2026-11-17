<?php
/**
 * Plan your visit: data comes ONLY from Theme Settings; empty items are hidden.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$id      = $args['post_id'];
$name    = rivermere_market_name();
$address = (string) rivermere_option( 'address', '' );
$days    = (string) rivermere_option( 'market_days', '' );
$hours   = (array) rivermere_option( 'opening_hours', array() );
$email   = (string) rivermere_option( 'contact_email', '' );
$phone   = (string) rivermere_option( 'phone', '' );
$maps    = (string) rivermere_option( 'maps_url', '' );

if ( '' === $maps && '' !== $address ) {
	$maps = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $address ) ) ) );
}
$hours_lines = array();
foreach ( $hours as $row ) {
	if ( ! empty( $row['days'] ) && ! empty( $row['opens'] ) && ! empty( $row['closes'] ) ) {
		$hours_lines[] = implode( ', ', array_map( static function ( $d ) {
			return substr( $d, 0, 3 );
		}, (array) $row['days'] ) ) . ' ' . $row['opens'] . '–' . $row['closes'];
	}
}
$has_details = $address || $days || $hours_lines || is_email( $email ) || $phone;
?>
<section class="section section--flow visit" id="visit" aria-labelledby="visit-title">
	<div class="container">
		<div class="visit__panel">
			<div class="visit__intro">
				<h2 class="feature-section__title" id="visit-title"><?php echo esc_html( rivermere_field( 'ap_visit_title', $id, __( 'Plan Your Visit', 'rivermere-market' ) ) ); ?></h2>
				<?php $intro = rivermere_field( 'ap_visit_intro', $id, '' ); ?>
				<?php if ( $intro ) : ?>
					<p><?php echo esc_html( $intro ); ?></p>
				<?php endif; ?>
				<div class="visit__actions">
					<?php if ( $maps ) : ?>
						<?php echo rivermere_button( array( 'url' => $maps, 'title' => __( 'Get Directions', 'rivermere-market' ), 'target' => '_blank' ), 'secondary', array( 'icon' => 'pin' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>
					<?php echo rivermere_button( array( 'url' => $args['events_url'], 'title' => __( 'Explore Events', 'rivermere-market' ) ), 'outline', array( 'icon' => 'calendar' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php if ( is_email( $email ) ) : ?>
						<?php echo rivermere_button( array( 'url' => 'mailto:' . $email, 'title' => __( 'Contact Us', 'rivermere-market' ) ), 'outline', array( 'icon' => 'mail' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php endif; ?>
				</div>
			</div>
			<?php if ( $has_details ) : ?>
				<div class="visit__card">
					<h3 class="visit__name"><?php echo esc_html( $name ); ?></h3>
					<dl class="visit__list">
						<?php if ( $days ) : ?>
							<div><dt><?php echo rivermere_icon( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Market days', 'rivermere-market' ); ?></dt><dd><?php echo esc_html( $days ); ?></dd></div>
						<?php endif; ?>
						<?php if ( $hours_lines ) : ?>
							<div><dt><?php echo rivermere_icon( 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Opening hours', 'rivermere-market' ); ?></dt><dd><?php echo esc_html( implode( ' · ', $hours_lines ) ); ?></dd></div>
						<?php endif; ?>
						<?php if ( $address ) : ?>
							<div><dt><?php echo rivermere_icon( 'pin' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Address', 'rivermere-market' ); ?></dt><dd><?php echo wp_kses( $address, array( 'br' => array() ) ); ?></dd></div>
						<?php endif; ?>
						<?php if ( is_email( $email ) ) : ?>
							<div><dt><?php echo rivermere_icon( 'mail' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Email', 'rivermere-market' ); ?></dt><dd><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></dd></div>
						<?php endif; ?>
						<?php if ( $phone ) : ?>
							<div><dt><?php echo rivermere_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php esc_html_e( 'Telephone', 'rivermere-market' ); ?></dt><dd><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></dd></div>
						<?php endif; ?>
					</dl>
				</div>
			<?php endif; ?>
		</div>
	</div>
</section>
