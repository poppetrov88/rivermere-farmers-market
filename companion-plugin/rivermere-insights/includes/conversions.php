<?php
/**
 * Confirmed conversions from Rivermere Core success hooks.
 *
 * Only fired AFTER the record is stored (never for failures, never for duplicate newsletter
 * sign-ups), and only with valid analytics consent on the same request. No name, email, phone
 * or any applicant data is recorded: just a counter increment.
 *
 * @package Rivermere_Insights
 */

defined( 'ABSPATH' ) || exit;

/**
 * Record a server-side conversion if (and only if) tracking and consent allow it.
 *
 * @param string $type  Conversion type.
 * @param string $label Neutral label.
 */
function ri_record_conversion( $type, $label ) {
	if ( ! ri_type_enabled( $type ) || ! ri_has_consent() ) {
		return;
	}
	ri_record( $type, $label, '', 0, 'server' );
}

add_action(
	'rivermere_vendor_application_saved',
	static function () {
		ri_record_conversion( 'application_submitted', 'vendor-application' );
	}
);

add_action(
	'rivermere_newsletter_subscribed',
	static function ( $status ) {
		ri_record_conversion( 'newsletter_subscribed', 'resubscribed' === $status ? 'newsletter-resubscribed' : 'newsletter-new' );
	}
);
