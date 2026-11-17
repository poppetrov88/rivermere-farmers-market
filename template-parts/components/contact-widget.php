<?php
/**
 * Floating contact widget (right edge). Details come only from Theme Settings.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

if ( ! rivermere_toggle( 'floating_enabled', true ) ) {
	return;
}

$items = array();

$phone = trim( (string) rivermere_option( 'phone', '' ) );
$tel   = preg_replace( '/[^0-9+]/', '', $phone );
if ( rivermere_toggle( 'floating_phone', true ) && strlen( preg_replace( '/\D/', '', $tel ) ) >= 5 ) {
	$items[] = array(
		'key'   => 'phone',
		'icon'  => 'phone',
		'href'  => 'tel:' . $tel,
		'label' => $phone,
		'name'  => sprintf( /* translators: %s: phone */ __( 'Call us: %s', 'rivermere-market' ), $phone ),
		'ext'   => false,
	);
}

$email = (string) rivermere_option( 'contact_email', '' );
if ( rivermere_toggle( 'floating_email', true ) && is_email( $email ) ) {
	$items[] = array(
		'key'   => 'email',
		'icon'  => 'mail',
		'href'  => 'mailto:' . $email,
		'label' => $email,
		'name'  => sprintf( /* translators: %s: email */ __( 'Email us: %s', 'rivermere-market' ), $email ),
		'ext'   => false,
	);
}

$wa_raw    = (string) rivermere_option( 'whatsapp_number', '' );
$wa_digits = rivermere_whatsapp_digits( $wa_raw );
if ( rivermere_toggle( 'floating_whatsapp', true ) && $wa_digits ) {
	$items[] = array(
		'key'   => 'whatsapp',
		'icon'  => 'whatsapp',
		'href'  => 'https://wa.me/' . $wa_digits,
		'label' => $wa_raw,
		'name'  => sprintf( /* translators: %s: number */ __( 'Chat on WhatsApp: %s', 'rivermere-market' ), $wa_raw ),
		'ext'   => true,
	);
}

if ( ! $items ) {
	return;
}

$variant  = rivermere_option( 'floating_variant', 'green' );
$variant  = in_array( $variant, array( 'green', 'yellow', 'dark' ), true ) ? $variant : 'green';
$position = 'bottom' === rivermere_option( 'floating_position', 'middle' ) ? 'bottom' : 'middle';
?>
<aside class="contact-widget contact-widget--<?php echo esc_attr( $variant ); ?> contact-widget--<?php echo esc_attr( $position ); ?>" aria-label="<?php esc_attr_e( 'Quick contact', 'rivermere-market' ); ?>" data-contact-widget>
	<button type="button" class="contact-widget__toggle" aria-expanded="false" aria-controls="contact-widget-list" data-contact-toggle>
		<?php echo rivermere_icon( 'phone' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span class="screen-reader-text"><?php esc_html_e( 'Contact options', 'rivermere-market' ); ?></span>
	</button>
	<ul class="contact-widget__list" id="contact-widget-list">
		<?php foreach ( $items as $item ) : ?>
			<li>
				<a class="contact-widget__link contact-widget__link--<?php echo esc_attr( $item['key'] ); ?>" href="<?php echo esc_url( $item['href'], array( 'http', 'https', 'tel', 'mailto' ) ); ?>" aria-label="<?php echo esc_attr( $item['name'] ); ?>"<?php echo $item['ext'] ? ' target="_blank" rel="noopener noreferrer"' : ''; ?>>
					<?php echo rivermere_icon( $item['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<span class="contact-widget__label" aria-hidden="true"><?php echo esc_html( $item['label'] ); ?></span>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</aside>
