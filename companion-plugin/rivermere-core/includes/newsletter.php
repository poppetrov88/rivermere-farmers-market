<?php
/**
 * Newsletter: storage (custom table), REST endpoint, unsubscribe flow.
 *
 * Single opt-in with explicit consent checkbox (NOT double opt-in): the stored consent
 * timestamp is the audit trail, and every email includes a working unsubscribe link.
 *
 * @package Rivermere_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Table name.
 *
 * @return string
 */
function rivermere_newsletter_table() {
	global $wpdb;
	return $wpdb->prefix . 'rm_subscribers';
}

/**
 * Create/upgrade the subscribers table.
 */
function rivermere_newsletter_install() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$table   = rivermere_newsletter_table();
	$charset = $wpdb->get_charset_collate();
	dbDelta(
		"CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			email varchar(190) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'subscribed',
			token char(40) NOT NULL,
			consent_at datetime NOT NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY email (email),
			UNIQUE KEY token (token),
			KEY status (status)
		) {$charset};"
	);
}

/**
 * Normalise an email: trim, lowercase, validate.
 *
 * @param string $email Raw email.
 * @return string Empty string if invalid.
 */
function rivermere_newsletter_normalize_email( $email ) {
	$email = strtolower( trim( sanitize_email( (string) $email ) ) );
	return ( $email && is_email( $email ) && strlen( $email ) <= 190 ) ? $email : '';
}

/**
 * Add or re-activate a subscriber.
 *
 * @param string $email Normalised email.
 * @return array{status:string,token:string} status: new|existing|resubscribed
 */
function rivermere_newsletter_subscribe( $email ) {
	global $wpdb;
	$table = rivermere_newsletter_table();
	$now   = current_time( 'mysql', true );
	$row   = $wpdb->get_row( $wpdb->prepare( "SELECT id, status, token FROM {$table} WHERE email = %s", $email ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

	if ( $row ) {
		if ( 'subscribed' === $row->status ) {
			return array( 'status' => 'existing', 'token' => $row->token );
		}
		$wpdb->update(
			$table,
			array( 'status' => 'subscribed', 'consent_at' => $now, 'updated_at' => $now ),
			array( 'id' => $row->id ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
		return array( 'status' => 'resubscribed', 'token' => $row->token );
	}

	$token = bin2hex( random_bytes( 20 ) );
	$wpdb->insert(
		$table,
		array(
			'email'      => $email,
			'status'     => 'subscribed',
			'token'      => $token,
			'consent_at' => $now,
			'created_at' => $now,
			'updated_at' => $now,
		),
		array( '%s', '%s', '%s', '%s', '%s', '%s' )
	);
	return array( 'status' => 'new', 'token' => $token );
}

/**
 * Unsubscribe URL for a token.
 *
 * @param string $token Token.
 * @return string
 */
function rivermere_newsletter_unsubscribe_url( $token ) {
	return add_query_arg( 'rm_unsubscribe', rawurlencode( $token ), home_url( '/' ) );
}

/**
 * Rate limit check by hashed IP (stored only in a short-lived transient).
 *
 * @return bool True if allowed.
 */
function rivermere_newsletter_rate_ok() {
	$ip   = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
	$key  = 'rm_nl_' . md5( $ip . wp_salt() );
	$hits = (int) get_transient( $key );
	if ( $hits >= 5 ) {
		return false;
	}
	set_transient( $key, $hits + 1, HOUR_IN_SECONDS );
	return true;
}

/**
 * REST route.
 */
function rivermere_newsletter_register_route() {
	register_rest_route(
		'rivermere/v1',
		'/newsletter',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'rivermere_newsletter_handle',
			'permission_callback' => 'rivermere_newsletter_permission',
			'args'                => array(
				'email'   => array( 'type' => 'string', 'required' => true ),
				'consent' => array( 'type' => 'boolean', 'default' => false ),
				'website' => array( 'type' => 'string', 'default' => '' ),
				'rm_ts'   => array( 'type' => 'string', 'default' => '' ),
			),
		)
	);
}
add_action( 'rest_api_init', 'rivermere_newsletter_register_route' );

/**
 * Public endpoint, guarded by a REST nonce (CSRF) in addition to honeypot/time/rate limits.
 *
 * @param WP_REST_Request $request Request.
 * @return true|WP_Error
 */
function rivermere_newsletter_permission( WP_REST_Request $request ) {
	$nonce = $request->get_header( 'X-WP-Nonce' );
	if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
		return new WP_Error( 'rm_bad_nonce', __( 'Your session expired. Please reload the page and try again.', 'rivermere-core' ), array( 'status' => 403 ) );
	}
	return true;
}

/**
 * Handle a subscription.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function rivermere_newsletter_handle( WP_REST_Request $request ) {
	$fail = static function ( $code, $message, $status = 400 ) {
		return new WP_REST_Response( array( 'success' => false, 'code' => $code, 'message' => $message ), $status );
	};

	// Honeypot: pretend success so bots learn nothing.
	if ( '' !== trim( (string) $request->get_param( 'website' ) ) ) {
		return new WP_REST_Response( array( 'success' => true, 'message' => __( 'Thanks for subscribing!', 'rivermere-core' ) ), 200 );
	}
	$ts = (int) $request->get_param( 'rm_ts' );
	if ( $ts > 0 && ( time() - $ts ) < 2 ) {
		return $fail( 'too_fast', __( 'Please wait a moment and try again.', 'rivermere-core' ), 429 );
	}
	if ( ! rivermere_newsletter_rate_ok() ) {
		return $fail( 'rate_limited', __( 'Too many attempts. Please try again later.', 'rivermere-core' ), 429 );
	}

	$email = rivermere_newsletter_normalize_email( $request->get_param( 'email' ) );
	if ( ! $email ) {
		return $fail( 'invalid_email', __( 'Please enter a valid email address.', 'rivermere-core' ) );
	}
	if ( ! rest_sanitize_boolean( $request->get_param( 'consent' ) ) ) {
		return $fail( 'no_consent', __( 'Please tick the consent box to subscribe.', 'rivermere-core' ) );
	}

	$result = rivermere_newsletter_subscribe( $email );

	if ( 'existing' !== $result['status'] ) {
		/**
		 * Fires once for a NEW or re-activated subscription (never for duplicates). No personal data.
		 *
		 * @param string $status 'new' or 'resubscribed'.
		 */
		do_action( 'rivermere_newsletter_subscribed', $result['status'] );
		$site    = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$subject = sprintf( /* translators: %s: site name */ __( 'You’re subscribed to %s', 'rivermere-core' ), $site );
		$body    = sprintf(
			/* translators: 1: site name, 2: unsubscribe URL */
			__( "Thanks for subscribing to updates from %1\$s.\n\nIf this wasn't you, or you change your mind, unsubscribe any time:\n%2\$s\n", 'rivermere-core' ),
			$site,
			rivermere_newsletter_unsubscribe_url( $result['token'] )
		);
		wp_mail( $email, $subject, $body );
	}

	return new WP_REST_Response(
		array(
			'success' => true,
			'message' => __( 'Thanks for subscribing! Check your inbox for a confirmation email.', 'rivermere-core' ),
		),
		200
	);
}

/**
 * Unsubscribe page (confirm with POST so link prefetchers cannot unsubscribe people).
 */
function rivermere_newsletter_unsubscribe_page() {
	global $wpdb;
	// phpcs:disable WordPress.Security.NonceVerification -- token acts as the secret.
	$token = '';
	if ( isset( $_REQUEST['rm_unsubscribe'] ) ) {
		$token = sanitize_text_field( wp_unslash( $_REQUEST['rm_unsubscribe'] ) );
	}
	if ( '' === $token || ! preg_match( '/^[a-f0-9]{40}$/', $token ) ) {
		return;
	}
	$table = rivermere_newsletter_table();
	$row   = $wpdb->get_row( $wpdb->prepare( "SELECT id, status FROM {$table} WHERE token = %s", $token ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$title = __( 'Unsubscribe', 'rivermere-core' );

	if ( ! $row ) {
		wp_die( esc_html__( 'This unsubscribe link is not valid.', 'rivermere-core' ), esc_html( $title ), array( 'response' => 404, 'back_link' => true ) );
	}

	if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
		$wpdb->update(
			$table,
			array( 'status' => 'unsubscribed', 'updated_at' => current_time( 'mysql', true ) ),
			array( 'id' => $row->id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
		wp_die( esc_html__( 'You have been unsubscribed. You will no longer receive our emails.', 'rivermere-core' ), esc_html( $title ), array( 'response' => 200, 'back_link' => true ) );
	}

	if ( 'unsubscribed' === $row->status ) {
		wp_die( esc_html__( 'You are already unsubscribed.', 'rivermere-core' ), esc_html( $title ), array( 'response' => 200, 'back_link' => true ) );
	}

	$form  = '<form method="post" action="' . esc_url( home_url( '/' ) ) . '">';
	$form .= '<p>' . esc_html__( 'Do you want to unsubscribe from market emails?', 'rivermere-core' ) . '</p>';
	$form .= '<input type="hidden" name="rm_unsubscribe" value="' . esc_attr( $token ) . '">';
	$form .= '<p><button type="submit" class="button button-primary">' . esc_html__( 'Yes, unsubscribe me', 'rivermere-core' ) . '</button></p></form>';
	wp_die( $form, esc_html( $title ), array( 'response' => 200 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
	// phpcs:enable
}
add_action( 'template_redirect', 'rivermere_newsletter_unsubscribe_page', 1 );
