<?php
/**
 * ACF field groups for Vendors and Events (stored with the content, independent of the theme).
 *
 * @package Rivermere_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register field groups.
 */
function rivermere_core_register_acf_groups() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	$field = static function ( $type, $name, $label, $extra = array() ) {
		return array_merge(
			array(
				'key'   => 'field_rmc_' . $name,
				'label' => $label,
				'name'  => $name,
				'type'  => $type,
			),
			$extra
		);
	};

	acf_add_local_field_group(
		array(
			'key'      => 'group_rmc_vendor',
			'title'    => __( 'Vendor details', 'rivermere-core' ),
			'fields'   => array(
				$field( 'text', 'vendor_tagline', 'Short description', array( 'instructions' => 'One line shown on cards, e.g. "Fresh, seasonal produce".', 'maxlength' => 80, 'required' => 1 ) ),
				$field( 'image', 'vendor_logo', 'Logo', array( 'return_format' => 'array', 'preview_size' => 'thumbnail', 'instructions' => 'Optional. The main photo is the Featured Image.' ) ),
				$field( 'true_false', 'vendor_featured', 'Featured vendor', array( 'ui' => 1, 'instructions' => 'Show on the homepage "Featured Vendors" section.' ) ),
				$field( 'text', 'vendor_stall', 'Stall / location at the market', array( 'wrapper' => array( 'width' => '50' ) ) ),
				$field( 'checkbox', 'vendor_days', 'Attends on', array(
					'choices' => array(
						'Saturday' => 'Saturday',
						'Sunday'   => 'Sunday',
						'Weekdays' => 'Weekdays',
					),
					'layout'  => 'horizontal',
					'wrapper' => array( 'width' => '50' ),
				) ),
				$field( 'url', 'vendor_website', 'Website', array( 'wrapper' => array( 'width' => '50' ) ) ),
				$field( 'email', 'vendor_email', 'Contact email', array( 'wrapper' => array( 'width' => '50' ) ) ),
				$field( 'text', 'vendor_phone', 'Phone', array( 'wrapper' => array( 'width' => '50' ) ) ),
				$field( 'url', 'vendor_instagram', 'Instagram URL', array( 'wrapper' => array( 'width' => '50' ) ) ),
				$field( 'url', 'vendor_facebook', 'Facebook URL', array( 'wrapper' => array( 'width' => '50' ) ) ),
			),
			'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'rm_vendor' ) ) ),
			'position' => 'normal',
		)
	);

	acf_add_local_field_group(
		array(
			'key'      => 'group_rmc_event',
			'title'    => __( 'Event details', 'rivermere-core' ),
			'fields'   => array(
				$field( 'text', 'event_tagline', 'Short tagline', array( 'instructions' => 'e.g. "Local Food • Live Music • Family Activities".' ) ),
				$field( 'date_time_picker', 'event_start', 'Starts', array( 'required' => 1, 'display_format' => 'd/m/Y g:i a', 'return_format' => 'Y-m-d H:i:s', 'first_day' => 1, 'wrapper' => array( 'width' => '50' ) ) ),
				$field( 'date_time_picker', 'event_end', 'Ends', array( 'display_format' => 'd/m/Y g:i a', 'return_format' => 'Y-m-d H:i:s', 'first_day' => 1, 'wrapper' => array( 'width' => '50' ) ) ),
				$field( 'text', 'event_location', 'Location' ),
				$field( 'url', 'event_registration_url', 'Registration URL', array( 'instructions' => 'Optional.' ) ),
				$field( 'true_false', 'event_featured', 'Featured event', array( 'ui' => 1 ) ),
			),
			'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'rm_event' ) ) ),
			'position' => 'normal',
		)
	);
}
add_action( 'acf/init', 'rivermere_core_register_acf_groups' );

/**
 * Validate that an event does not end before it starts.
 *
 * @param mixed $valid Validity.
 * @param mixed $value End value.
 * @return mixed
 */
function rivermere_core_validate_event_end( $valid, $value ) {
	if ( true !== $valid || '' === $value ) {
		return $valid;
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- ACF verifies its nonce before validation.
	$start = isset( $_POST['acf']['field_rmc_event_start'] ) ? sanitize_text_field( wp_unslash( $_POST['acf']['field_rmc_event_start'] ) ) : '';
	if ( $start && strtotime( $value ) < strtotime( $start ) ) {
		return __( 'The end time must be after the start time.', 'rivermere-core' );
	}
	return $valid;
}
add_filter( 'acf/validate_value/key=field_rmc_event_end', 'rivermere_core_validate_event_end', 10, 2 );
