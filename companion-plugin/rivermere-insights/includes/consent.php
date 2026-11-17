<?php
/**
 * Consent integration with the Rivermere cookie manager.
 *
 * Contract (documented integration layer, defined by the theme's consent.js):
 *  - cookie `rm_consent` = URI-encoded JSON { v, n, a, m, t }, `a` = 1 when analytics is allowed;
 *  - browser: window.rivermereConsent = { analytics, marketing } and the `rivermere:consent` event;
 *  - server: this file reads the same cookie from the current request.
 *
 * No consent cookie, an invalid cookie, or a = 0 means NO analytics record is created, whether the
 * event comes from the browser or from a server-side hook.
 *
 * @package Rivermere_Insights
 */

defined( 'ABSPATH' ) || exit;

/**
 * Does the current request carry valid analytics consent?
 *
 * @return bool
 */
function ri_has_consent() {
	$allowed = false;
	if ( isset( $_COOKIE['rm_consent'] ) ) {
		$raw  = rawurldecode( wp_unslash( $_COOKIE['rm_consent'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- decoded JSON is validated below.
		$data = json_decode( $raw, true );
		if ( is_array( $data ) && isset( $data['a'], $data['t'] ) && 1 === (int) $data['a'] ) {
			$allowed = true;
		}
	}
	/**
	 * Filter consent (e.g. to integrate another consent manager). Must return true only for explicit opt-in.
	 *
	 * @param bool $allowed Whether analytics consent was found.
	 */
	return (bool) apply_filters( 'rivermere_insights_has_consent', $allowed );
}
