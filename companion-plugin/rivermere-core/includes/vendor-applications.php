<?php
/**
 * Vendor applications: private post type, REST submission endpoint, admin screens, notifications.
 *
 * Success hook (documented integration point, consumed by Rivermere Insights):
 *
 *     do_action( 'rivermere_vendor_application_saved', int $application_id, array $context );
 *
 * Fired once, only after the application is stored. $context contains NO personal data
 * (keys: category). Failed or duplicate submissions never fire it.
 *
 * @package Rivermere_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Setting from Theme Settings (ACF options) with a default.
 *
 * @param string $name    Field name.
 * @param mixed  $default Default.
 * @return mixed
 */
function rivermere_app_setting( $name, $default = '' ) {
	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}
	$value = get_field( $name, 'option' );
	return ( null === $value || '' === $value ) ? $default : $value;
}

/**
 * Success message (Theme Settings, with a built-in default).
 *
 * @return string
 */
function rivermere_app_success_message() {
	$msg = (string) rivermere_app_setting( 'apps_success', '' );
	return '' !== trim( $msg ) ? $msg : __( 'Thank you for applying! Your application has been received. Our team will review your information and contact you about the next steps.', 'rivermere-core' );
}

/**
 * Application statuses.
 *
 * @return array<string,string>
 */
function rivermere_app_statuses() {
	return array(
		'new'          => __( 'New', 'rivermere-core' ),
		'under_review' => __( 'Under Review', 'rivermere-core' ),
		'approved'     => __( 'Approved', 'rivermere-core' ),
		'rejected'     => __( 'Rejected', 'rivermere-core' ),
	);
}

/**
 * Whether applications are currently accepted.
 *
 * @return array{open:bool,reason:string}
 */
function rivermere_app_is_open() {
	if ( ! rivermere_app_setting( 'apps_enabled', true ) && null !== get_field( 'apps_enabled', 'option' ) ) {
		return array( 'open' => false, 'reason' => 'disabled' );
	}
	$deadline = (string) rivermere_app_setting( 'apps_deadline', '' );
	if ( $deadline && gmdate( 'Y-m-d', current_time( 'timestamp' ) ) > $deadline ) { // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		return array( 'open' => false, 'reason' => 'deadline' );
	}
	return array( 'open' => true, 'reason' => '' );
}

/**
 * Capability map for the private application post type.
 *
 * @return array<string,string>
 */
function rivermere_app_caps() {
	return array(
		'edit_post'              => 'edit_vendor_application',
		'read_post'              => 'read_vendor_application',
		'delete_post'            => 'delete_vendor_application',
		'edit_posts'             => 'edit_vendor_applications',
		'edit_others_posts'      => 'edit_others_vendor_applications',
		'delete_posts'           => 'delete_vendor_applications',
		'publish_posts'          => 'publish_vendor_applications',
		'read_private_posts'     => 'read_private_vendor_applications',
		'delete_private_posts'   => 'delete_private_vendor_applications',
		'delete_published_posts' => 'delete_published_vendor_applications',
		'delete_others_posts'    => 'delete_others_vendor_applications',
		'edit_private_posts'     => 'edit_private_vendor_applications',
		'edit_published_posts'   => 'edit_published_vendor_applications',
		'create_posts'           => 'do_not_allow', // Applications come only from the public form.
	);
}

/**
 * Grant the application capabilities to exactly the users who can manage_options (administrators).
 *
 * @param array $allcaps All capabilities of the user.
 * @return array
 */
function rivermere_app_grant_caps( $allcaps ) {
	if ( ! empty( $allcaps['manage_options'] ) ) {
		foreach ( rivermere_app_caps() as $cap ) {
			if ( 'do_not_allow' !== $cap ) {
				$allcaps[ $cap ] = true;
			}
		}
	}
	return $allcaps;
}
add_filter( 'user_has_cap', 'rivermere_app_grant_caps' );

/**
 * Register the private post type.
 */
function rivermere_register_application_cpt() {
	register_post_type(
		'vendor_application',
		array(
			'labels'              => array(
				'name'          => __( 'Vendor Applications', 'rivermere-core' ),
				'singular_name' => __( 'Vendor Application', 'rivermere-core' ),
				'menu_name'     => __( 'Vendor Applications', 'rivermere-core' ),
				'all_items'     => __( 'All Applications', 'rivermere-core' ),
				'edit_item'     => __( 'Review Application', 'rivermere-core' ),
				'search_items'  => __( 'Search Applications', 'rivermere-core' ),
				'not_found'     => __( 'No applications yet.', 'rivermere-core' ),
			),
			'public'              => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => false,
			'show_in_admin_bar'   => false,
			'show_in_rest'        => false,
			'has_archive'         => false,
			'rewrite'             => false,
			'query_var'           => false,
			'can_export'          => false,
			'menu_icon'           => 'dashicons-clipboard',
			'menu_position'       => 23,
			'supports'            => array( 'title' ),
			'map_meta_cap'        => true,
			// Dedicated capability names. NEVER map meta caps (edit_post, ...) onto a primitive such as
			// 'manage_options': WordPress would then treat manage_options itself as a per-post meta cap.
			'capabilities'        => rivermere_app_caps(),
		)
	);
}
add_action( 'init', 'rivermere_register_application_cpt' );

/* ------------------------------------------------------------------ REST */

/**
 * Register the public submission route.
 */
function rivermere_app_register_route() {
	register_rest_route(
		'rivermere/v1',
		'/vendor-application',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'rivermere_app_handle',
			'permission_callback' => 'rivermere_app_permission',
		)
	);
}
add_action( 'rest_api_init', 'rivermere_app_register_route' );

/**
 * CSRF + size guard. Public endpoint, so a nonce is only one layer (see handler for the rest).
 *
 * @param WP_REST_Request $request Request.
 * @return true|WP_Error
 */
function rivermere_app_permission( WP_REST_Request $request ) {
	if ( strlen( (string) $request->get_body() ) > 20000 ) {
		return new WP_Error( 'rm_too_large', __( 'The submission is too large.', 'rivermere-core' ), array( 'status' => 413 ) );
	}
	$nonce = $request->get_header( 'X-WP-Nonce' );
	if ( ! $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
		return new WP_Error( 'rm_bad_nonce', __( 'Your session expired. Please reload the page and try again.', 'rivermere-core' ), array( 'status' => 403 ) );
	}
	return true;
}

/**
 * Field limits and rules.
 *
 * @return array<string,array>
 */
function rivermere_app_fields() {
	return array(
		'business_name' => array( 'label' => __( 'Business name', 'rivermere-core' ), 'max' => 120, 'required' => true ),
		'first_name'    => array( 'label' => __( 'First name', 'rivermere-core' ), 'max' => 60, 'required' => true ),
		'last_name'     => array( 'label' => __( 'Last name', 'rivermere-core' ), 'max' => 60, 'required' => true ),
		'email'         => array( 'label' => __( 'Email address', 'rivermere-core' ), 'max' => 190, 'required' => true ),
		'phone'         => array( 'label' => __( 'Phone number', 'rivermere-core' ), 'max' => 30, 'required' => true ),
		'products'      => array( 'label' => __( 'Products', 'rivermere-core' ), 'max' => 1500, 'required' => true, 'multiline' => true ),
		'website'       => array( 'label' => __( 'Business website', 'rivermere-core' ), 'max' => 255 ),
		'social'        => array( 'label' => __( 'Social media profile', 'rivermere-core' ), 'max' => 255 ),
		'description'   => array( 'label' => __( 'Business description', 'rivermere-core' ), 'max' => 3000, 'multiline' => true ),
		'category'      => array( 'label' => __( 'Product category', 'rivermere-core' ), 'max' => 80 ),
		'location'      => array( 'label' => __( 'City or region', 'rivermere-core' ), 'max' => 120 ),
		'experience'    => array( 'label' => __( 'Previous market experience', 'rivermere-core' ), 'max' => 1500, 'multiline' => true ),
		'notes'         => array( 'label' => __( 'Additional information', 'rivermere-core' ), 'max' => 2000, 'multiline' => true ),
	);
}

/**
 * Hashed client key for throttling (IP is never stored).
 *
 * @param string $scope Scope.
 * @return string
 */
function rivermere_client_key( $scope ) {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
	return 'rm_' . $scope . '_' . substr( hash_hmac( 'sha256', $ip, wp_salt() ), 0, 24 );
}

/**
 * Handle a submission.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function rivermere_app_handle( WP_REST_Request $request ) {
	$fail = static function ( $code, $message, $status = 400, $errors = array() ) {
		return new WP_REST_Response( array( 'success' => false, 'code' => $code, 'message' => $message, 'errors' => $errors ), $status );
	};
	$p = $request->get_json_params();
	$p = is_array( $p ) ? $p : array();

	$state = rivermere_app_is_open();
	if ( ! $state['open'] ) {
		return $fail( 'closed', __( 'Applications are currently closed.', 'rivermere-core' ), 403 );
	}

	// Honeypot: pretend success so bots learn nothing; nothing is stored.
	if ( '' !== trim( (string) ( $p['company_website'] ?? '' ) ) ) {
		return new WP_REST_Response( array( 'success' => true, 'message' => rivermere_app_success_message() ), 200 );
	}
	$ts = (int) ( $p['rm_ts'] ?? 0 );
	if ( $ts > 0 && ( time() - $ts ) < 3 ) {
		return $fail( 'too_fast', __( 'Please take a moment to complete the form and try again.', 'rivermere-core' ), 429 );
	}

	// Idempotency: a repeated submission id returns the first result without a second record.
	$submission_id = isset( $p['submission_id'] ) ? preg_replace( '/[^a-zA-Z0-9\-]/', '', (string) $p['submission_id'] ) : '';
	$idem_key      = $submission_id ? 'rm_appid_' . md5( $submission_id ) : '';
	if ( $idem_key && get_transient( $idem_key ) ) {
		return new WP_REST_Response( array( 'success' => true, 'duplicate' => true, 'message' => rivermere_app_success_message() ), 200 );
	}

	// Rate limit: 3 stored/attempted submissions per hour per client.
	$rl_key = rivermere_client_key( 'app' );
	$hits   = (int) get_transient( $rl_key );
	if ( $hits >= 5 ) {
		return $fail( 'rate_limited', __( 'Too many attempts. Please try again later.', 'rivermere-core' ), 429 );
	}
	set_transient( $rl_key, $hits + 1, HOUR_IN_SECONDS );

	// Validate + sanitise.
	$data   = array();
	$errors = array();
	foreach ( rivermere_app_fields() as $key => $rule ) {
		$raw   = isset( $p[ $key ] ) && is_scalar( $p[ $key ] ) ? (string) $p[ $key ] : '';
		$value = ! empty( $rule['multiline'] ) ? sanitize_textarea_field( $raw ) : sanitize_text_field( $raw );
		$value = trim( $value );
		if ( ! empty( $rule['required'] ) && '' === $value ) {
			/* translators: %s: field label */
			$errors[ $key ] = sprintf( __( '%s is required.', 'rivermere-core' ), $rule['label'] );
		} elseif ( function_exists( 'mb_strlen' ) ? mb_strlen( $value ) > $rule['max'] : strlen( $value ) > $rule['max'] ) {
			/* translators: 1: field label, 2: max length */
			$errors[ $key ] = sprintf( __( '%1$s must be %2$d characters or fewer.', 'rivermere-core' ), $rule['label'], $rule['max'] );
		}
		$data[ $key ] = $value;
	}
	if ( empty( $errors['email'] ) ) {
		$data['email'] = strtolower( sanitize_email( $data['email'] ) );
		if ( ! is_email( $data['email'] ) ) {
			$errors['email'] = __( 'Please enter a valid email address.', 'rivermere-core' );
		}
	}
	if ( empty( $errors['phone'] ) && ! preg_match( '/^\+?[0-9][0-9 ().\-]{5,28}[0-9]$/', $data['phone'] ) ) {
		$errors['phone'] = __( 'Please enter a valid phone number (digits, spaces, + ( ) - only).', 'rivermere-core' );
	}
	if ( empty( $errors['products'] ) && strlen( $data['products'] ) < 3 ) {
		$errors['products'] = __( 'Please tell us what products you sell.', 'rivermere-core' );
	}
	foreach ( array( 'website', 'social' ) as $url_field ) {
		if ( '' !== $data[ $url_field ] && empty( $errors[ $url_field ] ) ) {
			$url = esc_url_raw( $data[ $url_field ], array( 'http', 'https' ) );
			if ( ! $url || ! wp_http_validate_url( $url ) ) {
				/* translators: %s: field label */
				$errors[ $url_field ] = sprintf( __( 'Please enter a valid web address for %s (starting with https://).', 'rivermere-core' ), strtolower( rivermere_app_fields()[ $url_field ]['label'] ) );
			} else {
				$data[ $url_field ] = $url;
			}
		}
	}
	if ( empty( $p['consent'] ) ) {
		$errors['consent'] = __( 'Please confirm the privacy consent to submit your application.', 'rivermere-core' );
	}
	$fee_on = (bool) rivermere_app_setting( 'apps_fee_enabled', false );
	if ( $fee_on && empty( $p['fee_ack'] ) ) {
		$errors['fee_ack'] = __( 'Please acknowledge the application fee.', 'rivermere-core' );
	}
	if ( $errors ) {
		return $fail( 'validation', __( 'Please correct the highlighted fields.', 'rivermere-core' ), 422, $errors );
	}

	// Duplicate prevention: same applicant email + business within 30 days.
	global $wpdb;
	$dup = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT p.ID FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} e ON e.post_id = p.ID AND e.meta_key = '_app_email' AND e.meta_value = %s
			 INNER JOIN {$wpdb->postmeta} b ON b.post_id = p.ID AND b.meta_key = '_app_business_name' AND b.meta_value = %s
			 WHERE p.post_type = 'vendor_application' AND p.post_status <> 'trash' AND p.post_date_gmt > %s LIMIT 1",
			$data['email'],
			$data['business_name'],
			gmdate( 'Y-m-d H:i:s', time() - 30 * DAY_IN_SECONDS )
		)
	);
	if ( $dup ) {
		return $fail( 'duplicate', __( 'It looks like we have already received an application from this business and email address. If you need to add or change anything, please contact us.', 'rivermere-core' ), 409 );
	}

	$id = wp_insert_post(
		array(
			'post_type'   => 'vendor_application',
			'post_status' => 'publish',
			'post_title'  => $data['business_name'],
		),
		true
	);
	if ( is_wp_error( $id ) || ! $id ) {
		return $fail( 'storage', __( 'Sorry, we could not save your application. Please try again later.', 'rivermere-core' ), 500 );
	}
	foreach ( $data as $key => $value ) {
		update_post_meta( $id, '_app_' . $key, $value );
	}
	$privacy_url = get_privacy_policy_url();
	update_post_meta(
		$id,
		'_app_consent',
		wp_json_encode(
			array(
				'privacy'     => true,
				'privacy_url' => $privacy_url,
				'text'        => 'I agree that the market may use my information to review and respond to my vendor application. I have read the Privacy Policy.',
				'fee_ack'     => $fee_on ? true : null,
				'at_utc'      => gmdate( 'c' ),
				'version'     => 1,
			)
		)
	);
	update_post_meta( $id, '_app_status', 'new' );
	if ( $idem_key ) {
		set_transient( $idem_key, 1, DAY_IN_SECONDS );
	}

	// Notifications never decide the outcome: the application is already stored.
	$mail = rivermere_app_notify( $id, $data );
	update_post_meta( $id, '_app_mail_status', $mail );

	/**
	 * Fires once after a vendor application was stored. No personal data in $context.
	 *
	 * @param int   $id      Application post ID.
	 * @param array $context array( 'category' => string ).
	 */
	do_action( 'rivermere_vendor_application_saved', $id, array( 'category' => $data['category'] ) );

	return new WP_REST_Response( array( 'success' => true, 'message' => rivermere_app_success_message() ), 200 );
}

/**
 * Send admin (and optional applicant) notifications. Returns a short status for the admin screen.
 *
 * @param int   $id   Application ID.
 * @param array $data Sanitised data.
 * @return string
 */
function rivermere_app_notify( $id, $data ) {
	$to = sanitize_email( (string) rivermere_app_setting( 'apps_notify_email', '' ) );
	$to = is_email( $to ) ? $to : get_option( 'admin_email' );
	$site = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );

	$admin_sent = wp_mail(
		$to,
		sprintf( /* translators: %s: business name */ __( 'New vendor application: %s', 'rivermere-core' ), wp_strip_all_tags( $data['business_name'] ) ),
		sprintf(
			/* translators: 1: site name, 2: admin URL */
			__( "A new vendor application was received on %1\$s.\n\nReview it here (login required):\n%2\$s\n\nApplicant details are intentionally not included in this email.", 'rivermere-core' ),
			$site,
			admin_url( 'post.php?post=' . (int) $id . '&action=edit' )
		)
	);
	$status = $admin_sent ? 'admin: sent' : 'admin: FAILED';

	if ( rivermere_app_setting( 'apps_ack_applicant', false ) ) {
		$ack = wp_mail(
			$data['email'],
			sprintf( /* translators: %s: site name */ __( 'We received your vendor application – %s', 'rivermere-core' ), $site ),
			sprintf( /* translators: %s: site name */ __( "Thank you for applying to sell at %s.\n\nThis message confirms that we received your application. Our team will review it and contact you about the next steps. This is an automatic message, and receiving it does not mean the application has been approved.", 'rivermere-core' ), $site )
		);
		$status .= $ack ? '; applicant: sent' : '; applicant: FAILED';
	}
	return $status;
}

/* ------------------------------------------------------------------ Admin */

/**
 * Meta boxes.
 */
function rivermere_app_metaboxes() {
	add_meta_box( 'rm_app_details', __( 'Application details', 'rivermere-core' ), 'rivermere_app_box_details', 'vendor_application', 'normal', 'high' );
	add_meta_box( 'rm_app_status', __( 'Review', 'rivermere-core' ), 'rivermere_app_box_status', 'vendor_application', 'side', 'high' );
}
add_action( 'add_meta_boxes_vendor_application', 'rivermere_app_metaboxes' );

/**
 * Details box (read-only, escaped).
 *
 * @param WP_Post $post Post.
 */
function rivermere_app_box_details( $post ) {
	echo '<table class="widefat striped"><tbody>';
	foreach ( rivermere_app_fields() as $key => $rule ) {
		$value = (string) get_post_meta( $post->ID, '_app_' . $key, true );
		echo '<tr><th style="width:200px">' . esc_html( $rule['label'] ) . '</th><td>';
		if ( in_array( $key, array( 'website', 'social' ), true ) && $value ) {
			echo '<a href="' . esc_url( $value ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $value ) . '</a>';
		} elseif ( 'email' === $key && $value ) {
			echo '<a href="mailto:' . esc_attr( $value ) . '">' . esc_html( $value ) . '</a>';
		} else {
			echo '' !== $value ? nl2br( esc_html( $value ) ) : '—';
		}
		echo '</td></tr>';
	}
	$consent = json_decode( (string) get_post_meta( $post->ID, '_app_consent', true ), true );
	echo '<tr><th>' . esc_html__( 'Submitted', 'rivermere-core' ) . '</th><td>' . esc_html( get_the_date( 'Y-m-d H:i', $post ) ) . '</td></tr>';
	echo '<tr><th>' . esc_html__( 'Consent record', 'rivermere-core' ) . '</th><td>';
	if ( is_array( $consent ) ) {
		echo esc_html( sprintf( 'Privacy consent: %s · Fee acknowledged: %s · At (UTC): %s', ! empty( $consent['privacy'] ) ? 'yes' : 'no', null === ( $consent['fee_ack'] ?? null ) ? 'n/a' : ( $consent['fee_ack'] ? 'yes' : 'no' ), $consent['at_utc'] ?? '' ) );
		if ( ! empty( $consent['text'] ) ) {
			echo '<br><em>' . esc_html( $consent['text'] ) . '</em>';
		}
	}
	echo '</td></tr><tr><th>' . esc_html__( 'Notification emails', 'rivermere-core' ) . '</th><td>' . esc_html( (string) get_post_meta( $post->ID, '_app_mail_status', true ) ) . '</td></tr>';
	echo '</tbody></table>';
}

/**
 * Status box.
 *
 * @param WP_Post $post Post.
 */
function rivermere_app_box_status( $post ) {
	wp_nonce_field( 'rm_app_save_' . $post->ID, 'rm_app_nonce' );
	$status = (string) get_post_meta( $post->ID, '_app_status', true );
	$status = isset( rivermere_app_statuses()[ $status ] ) ? $status : 'new';
	echo '<p><label for="rm_app_status_field"><strong>' . esc_html__( 'Status', 'rivermere-core' ) . '</strong></label><br><select name="rm_app_status" id="rm_app_status_field" style="width:100%">';
	foreach ( rivermere_app_statuses() as $key => $label ) {
		echo '<option value="' . esc_attr( $key ) . '"' . selected( $status, $key, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select></p>';
	echo '<p><label for="rm_app_note"><strong>' . esc_html__( 'Internal note (never shown publicly)', 'rivermere-core' ) . '</strong></label><br><textarea name="rm_app_note" id="rm_app_note" rows="4" style="width:100%">' . esc_textarea( (string) get_post_meta( $post->ID, '_app_note', true ) ) . '</textarea></p>';

	$vendor_id = (int) get_post_meta( $post->ID, '_app_vendor_id', true );
	if ( $vendor_id && get_post( $vendor_id ) ) {
		echo '<p>' . esc_html__( 'Draft vendor profile:', 'rivermere-core' ) . ' <a href="' . esc_url( get_edit_post_link( $vendor_id ) ) . '">#' . (int) $vendor_id . '</a></p>';
	} elseif ( 'approved' === $status ) {
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=rivermere_app_to_vendor&application=' . (int) $post->ID ), 'rm_app_to_vendor_' . $post->ID );
		echo '<p><a class="button" href="' . esc_url( $url ) . '">' . esc_html__( 'Create DRAFT vendor profile', 'rivermere-core' ) . '</a></p><p class="description">' . esc_html__( 'Nothing is published automatically; review the draft first.', 'rivermere-core' ) . '</p>';
	}
}

/**
 * Save status + note.
 *
 * @param int $post_id Post ID.
 */
function rivermere_app_save_review( $post_id ) {
	if ( ! isset( $_POST['rm_app_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rm_app_nonce'] ) ), 'rm_app_save_' . $post_id ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
		return;
	}
	$status = isset( $_POST['rm_app_status'] ) ? sanitize_key( wp_unslash( $_POST['rm_app_status'] ) ) : 'new';
	if ( isset( rivermere_app_statuses()[ $status ] ) && $status !== get_post_meta( $post_id, '_app_status', true ) ) {
		update_post_meta( $post_id, '_app_status', $status );
		update_post_meta( $post_id, '_app_status_changed', gmdate( 'c' ) );
	}
	if ( isset( $_POST['rm_app_note'] ) ) {
		update_post_meta( $post_id, '_app_note', sanitize_textarea_field( wp_unslash( $_POST['rm_app_note'] ) ) );
	}
}
add_action( 'save_post_vendor_application', 'rivermere_app_save_review' );

/**
 * Explicit admin action: create a DRAFT public vendor profile from an approved application.
 */
function rivermere_app_to_vendor() {
	$id = isset( $_GET['application'] ) ? absint( $_GET['application'] ) : 0;
	check_admin_referer( 'rm_app_to_vendor_' . $id );
	if ( ! current_user_can( 'manage_options' ) || 'vendor_application' !== get_post_type( $id ) || 'approved' !== get_post_meta( $id, '_app_status', true ) ) {
		wp_die( esc_html__( 'Not allowed.', 'rivermere-core' ), 403 );
	}
	$vendor = wp_insert_post(
		array(
			'post_type'    => 'rm_vendor',
			'post_status'  => 'draft',
			'post_title'   => get_the_title( $id ),
			'post_content' => (string) get_post_meta( $id, '_app_description', true ),
		)
	);
	if ( $vendor && ! is_wp_error( $vendor ) ) {
		update_post_meta( $id, '_app_vendor_id', $vendor );
		if ( function_exists( 'update_field' ) ) {
			update_field( 'vendor_tagline', mb_substr( (string) get_post_meta( $id, '_app_products', true ), 0, 80 ), $vendor );
			update_field( 'vendor_website', (string) get_post_meta( $id, '_app_website', true ), $vendor );
		}
	}
	wp_safe_redirect( get_edit_post_link( (int) $vendor, 'url' ) ?: admin_url( 'edit.php?post_type=vendor_application' ) );
	exit;
}
add_action( 'admin_post_rivermere_app_to_vendor', 'rivermere_app_to_vendor' );

/**
 * List table columns.
 *
 * @param array $columns Columns.
 * @return array
 */
function rivermere_app_columns( $columns ) {
	return array(
		'cb'       => $columns['cb'],
		'title'    => __( 'Business', 'rivermere-core' ),
		'applicant' => __( 'Applicant', 'rivermere-core' ),
		'products' => __( 'Products', 'rivermere-core' ),
		'status'   => __( 'Status', 'rivermere-core' ),
		'date'     => __( 'Submitted', 'rivermere-core' ),
	);
}
add_filter( 'manage_vendor_application_posts_columns', 'rivermere_app_columns' );

/**
 * Column content.
 *
 * @param string $column  Column.
 * @param int    $post_id Post ID.
 */
function rivermere_app_column_content( $column, $post_id ) {
	if ( 'applicant' === $column ) {
		echo esc_html( trim( get_post_meta( $post_id, '_app_first_name', true ) . ' ' . get_post_meta( $post_id, '_app_last_name', true ) ) ) . '<br><span class="description">' . esc_html( (string) get_post_meta( $post_id, '_app_email', true ) ) . '</span>';
	} elseif ( 'products' === $column ) {
		echo esc_html( wp_trim_words( (string) get_post_meta( $post_id, '_app_products', true ), 14 ) );
	} elseif ( 'status' === $column ) {
		$s = (string) get_post_meta( $post_id, '_app_status', true );
		echo esc_html( rivermere_app_statuses()[ $s ] ?? __( 'New', 'rivermere-core' ) );
	}
}
add_action( 'manage_vendor_application_posts_custom_column', 'rivermere_app_column_content', 10, 2 );

/**
 * Status filter dropdown + export button above the list.
 *
 * @param string $post_type Post type.
 */
function rivermere_app_list_controls( $post_type ) {
	if ( 'vendor_application' !== $post_type ) {
		return;
	}
	$current = isset( $_GET['app_status'] ) ? sanitize_key( wp_unslash( $_GET['app_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	echo '<label class="screen-reader-text" for="app_status">' . esc_html__( 'Filter by status', 'rivermere-core' ) . '</label><select name="app_status" id="app_status"><option value="">' . esc_html__( 'All statuses', 'rivermere-core' ) . '</option>';
	foreach ( rivermere_app_statuses() as $key => $label ) {
		echo '<option value="' . esc_attr( $key ) . '"' . selected( $current, $key, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select>';
	$export = wp_nonce_url( admin_url( 'admin-post.php?action=rivermere_export_applications' . ( $current ? '&app_status=' . $current : '' ) ), 'rm_export_applications' );
	echo ' <a class="button" href="' . esc_url( $export ) . '">' . esc_html__( 'Export CSV', 'rivermere-core' ) . '</a>';
}
add_action( 'restrict_manage_posts', 'rivermere_app_list_controls' );

/**
 * Apply status filter and meta search on the admin list.
 *
 * @param WP_Query $query Query.
 */
function rivermere_app_admin_query( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() || 'vendor_application' !== $query->get( 'post_type' ) ) {
		return;
	}
	$status = isset( $_GET['app_status'] ) ? sanitize_key( wp_unslash( $_GET['app_status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $status && isset( rivermere_app_statuses()[ $status ] ) ) {
		$query->set( 'meta_query', array( array( 'key' => '_app_status', 'value' => $status ) ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	}
}
add_action( 'pre_get_posts', 'rivermere_app_admin_query' );

/**
 * Search applicant fields (email, name, products) in addition to the title.
 *
 * @param string   $search Search SQL.
 * @param WP_Query $query  Query.
 * @return string
 */
function rivermere_app_search( $search, $query ) {
	global $wpdb;
	if ( ! is_admin() || ! $query->is_main_query() || 'vendor_application' !== $query->get( 'post_type' ) || '' === (string) $query->get( 's' ) ) {
		return $search;
	}
	$like = '%' . $wpdb->esc_like( (string) $query->get( 's' ) ) . '%';
	return $wpdb->prepare(
		" AND ({$wpdb->posts}.post_title LIKE %s OR EXISTS (SELECT 1 FROM {$wpdb->postmeta} m WHERE m.post_id = {$wpdb->posts}.ID AND m.meta_key IN ('_app_email','_app_first_name','_app_last_name','_app_products','_app_location') AND m.meta_value LIKE %s))",
		$like,
		$like
	);
}
add_filter( 'posts_search', 'rivermere_app_search', 10, 2 );

/**
 * CSV export (admins only, nonce, formula-injection safe).
 */
function rivermere_app_export() {
	check_admin_referer( 'rm_export_applications' );
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Not allowed.', 'rivermere-core' ), 403 );
	}
	$status = isset( $_GET['app_status'] ) ? sanitize_key( wp_unslash( $_GET['app_status'] ) ) : '';
	$args   = array( 'post_type' => 'vendor_application', 'post_status' => 'any', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC', 'no_found_rows' => true );
	if ( $status && isset( rivermere_app_statuses()[ $status ] ) ) {
		$args['meta_query'] = array( array( 'key' => '_app_status', 'value' => $status ) ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	}
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="vendor-applications-' . gmdate( 'Y-m-d' ) . '.csv"' );
	$out    = fopen( 'php://output', 'w' );
	$keys   = array_keys( rivermere_app_fields() );
	fputcsv( $out, array_merge( array( 'id', 'submitted', 'status' ), $keys, array( 'consent_at_utc' ) ), ',', '"', '\\' );
	foreach ( get_posts( $args ) as $post ) {
		$consent = json_decode( (string) get_post_meta( $post->ID, '_app_consent', true ), true );
		$row     = array( $post->ID, $post->post_date_gmt, (string) get_post_meta( $post->ID, '_app_status', true ) );
		foreach ( $keys as $key ) {
			$row[] = (string) get_post_meta( $post->ID, '_app_' . $key, true );
		}
		$row[] = is_array( $consent ) ? (string) ( $consent['at_utc'] ?? '' ) : '';
		$row   = array_map( static function ( $cell ) {
			return preg_match( '/^[=+\-@\t\r]/', (string) $cell ) ? "'" . $cell : $cell;
		}, $row );
		fputcsv( $out, $row, ',', '"', '\\' );
	}
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	exit;
}
add_action( 'admin_post_rivermere_export_applications', 'rivermere_app_export' );

/**
 * Keep applications out of public REST, sitemaps and search (belt and braces on top of public=false).
 *
 * @param array $types Rank Math sitemap post types.
 * @return array
 */
function rivermere_app_exclude_from_sitemap( $types ) {
	return array_diff( (array) $types, array( 'vendor_application' ) );
}
add_filter( 'rank_math/sitemap/post_types', 'rivermere_app_exclude_from_sitemap' );
add_filter( 'rank_math/excluded_post_types', static function ( $types ) {
	$types   = (array) $types;
	$types[] = 'vendor_application';
	return $types;
} );
