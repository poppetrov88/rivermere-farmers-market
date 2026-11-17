<?php
/**
 * Settings (WordPress Settings API) and event-type registry.
 *
 * @package Rivermere_Insights
 */

defined( 'ABSPATH' ) || exit;

const RI_OPTION = 'rivermere_insights';

/**
 * Default settings.
 *
 * @return array
 */
function ri_defaults() {
	return array(
		'enabled'         => 1,
		'track_pageviews' => 1,
		'track_cta'       => 1,
		'track_contact'   => 1,
		'track_social'    => 1,
		'track_external'  => 1,
		'retention_days'  => 365,
		'exclude_admins'  => 1,
		'exclude_pages'   => '',
	);
}

/**
 * Current settings.
 *
 * @return array
 */
function ri_settings() {
	return wp_parse_args( (array) get_option( RI_OPTION, array() ), ri_defaults() );
}

/**
 * Event types: type => array( group, label, kind ). kind: view|click|conversion.
 *
 * @return array<string,array<string,string>>
 */
function ri_event_types() {
	return array(
		'pageview'              => array( 'group' => 'pageviews', 'kind' => 'view', 'label' => __( 'Page view', 'rivermere-insights' ) ),
		'cta'                   => array( 'group' => 'cta', 'kind' => 'click', 'label' => __( 'CTA button', 'rivermere-insights' ) ),
		'apply_cta'             => array( 'group' => 'cta', 'kind' => 'click', 'label' => __( 'Vendor application CTA', 'rivermere-insights' ) ),
		'apply_attempt'         => array( 'group' => 'cta', 'kind' => 'click', 'label' => __( 'Application submit attempt', 'rivermere-insights' ) ),
		'register'              => array( 'group' => 'cta', 'kind' => 'click', 'label' => __( 'Event registration link', 'rivermere-insights' ) ),
		'newsletter_cta'        => array( 'group' => 'cta', 'kind' => 'click', 'label' => __( 'Newsletter button', 'rivermere-insights' ) ),
		'internal'              => array( 'group' => 'cta', 'kind' => 'click', 'label' => __( 'Internal link', 'rivermere-insights' ) ),
		'tel'                   => array( 'group' => 'contact', 'kind' => 'click', 'label' => __( 'Telephone', 'rivermere-insights' ) ),
		'email'                 => array( 'group' => 'contact', 'kind' => 'click', 'label' => __( 'Email', 'rivermere-insights' ) ),
		'whatsapp'              => array( 'group' => 'contact', 'kind' => 'click', 'label' => __( 'WhatsApp', 'rivermere-insights' ) ),
		'social'                => array( 'group' => 'social', 'kind' => 'click', 'label' => __( 'Social link', 'rivermere-insights' ) ),
		'external'              => array( 'group' => 'external', 'kind' => 'click', 'label' => __( 'External link', 'rivermere-insights' ) ),
		'download'              => array( 'group' => 'external', 'kind' => 'click', 'label' => __( 'Download', 'rivermere-insights' ) ),
		'application_submitted' => array( 'group' => 'conversions', 'kind' => 'conversion', 'label' => __( 'Vendor application (confirmed)', 'rivermere-insights' ) ),
		'newsletter_subscribed' => array( 'group' => 'conversions', 'kind' => 'conversion', 'label' => __( 'Newsletter subscription (confirmed)', 'rivermere-insights' ) ),
	);
}

/**
 * Is an event type currently allowed by the settings?
 *
 * @param string $type Event type.
 * @return bool
 */
function ri_type_enabled( $type ) {
	$types = ri_event_types();
	$s     = ri_settings();
	if ( empty( $s['enabled'] ) || ! isset( $types[ $type ] ) ) {
		return false;
	}
	$map = array(
		'pageviews'   => 'track_pageviews',
		'cta'         => 'track_cta',
		'contact'     => 'track_contact',
		'social'      => 'track_social',
		'external'    => 'track_external',
		'conversions' => 'enabled',
	);
	return ! empty( $s[ $map[ $types[ $type ]['group'] ] ] );
}

/**
 * Register the setting.
 */
function ri_register_settings() {
	register_setting(
		'ri_settings_group',
		RI_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'ri_sanitize_settings',
			'default'           => ri_defaults(),
		)
	);
}
add_action( 'admin_init', 'ri_register_settings' );

/**
 * Sanitize settings.
 *
 * @param mixed $input Raw input.
 * @return array
 */
function ri_sanitize_settings( $input ) {
	$input = is_array( $input ) ? $input : array();
	$out   = array();
	foreach ( array( 'enabled', 'track_pageviews', 'track_cta', 'track_contact', 'track_social', 'track_external', 'exclude_admins' ) as $key ) {
		$out[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
	}
	$days                  = isset( $input['retention_days'] ) ? (int) $input['retention_days'] : 365;
	$out['retention_days'] = in_array( $days, array( 30, 90, 180, 365, 730 ), true ) ? $days : 365;
	$paths                 = array();
	foreach ( preg_split( '/\r\n|\r|\n/', isset( $input['exclude_pages'] ) ? (string) $input['exclude_pages'] : '' ) as $line ) {
		$line = '/' . trim( wp_parse_url( trim( $line ), PHP_URL_PATH ) ?: '', '/' );
		if ( '/' !== $line ) {
			$paths[] = trailingslashit( sanitize_text_field( $line ) );
		}
	}
	$out['exclude_pages'] = implode( "\n", array_unique( $paths ) );
	return $out;
}
