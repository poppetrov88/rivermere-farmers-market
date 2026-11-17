<?php
/**
 * Rivermere SEO Integration.
 *
 * Rank Math remains the SEO engine (titles, meta, canonical, robots, OG/Twitter, sitemaps, schema).
 * This module ONLY: (1) stores market-identity data, (2) enriches Rank Math's JSON-LD graph through
 * its `rank_math/json_ld` filter, (3) supplies a fallback social image, (4) optionally loads GA4.
 * When Rank Math is inactive nothing is output (no duplicate/competing SEO tags).
 *
 * @package Rivermere_Core
 */

defined( 'ABSPATH' ) || exit;

const RIVERMERE_SEO_OPTION = 'rivermere_seo';

/**
 * Get settings with defaults.
 *
 * @return array
 */
function rivermere_seo_settings() {
	$defaults = array(
		'default_image' => '',
		'org_name'      => '',
		'description'   => '',
		'telephone'     => '',
		'email'         => '',
		'street'        => '',
		'locality'      => '',
		'region'        => '',
		'postal'        => '',
		'country'       => '',
		'lat'           => '',
		'lng'           => '',
		'price_range'   => '',
		'hours'         => '',
		'social'        => '',
		'ga4_id'        => '',
		'ga4_consent'   => '1',
	);
	return wp_parse_args( (array) get_option( RIVERMERE_SEO_OPTION, array() ), $defaults );
}

/**
 * Sanitize settings.
 *
 * @param array $input Raw input.
 * @return array
 */
function rivermere_seo_sanitize( $input ) {
	$input = is_array( $input ) ? $input : array();
	$out   = array();
	$text  = array( 'org_name', 'telephone', 'street', 'locality', 'region', 'postal', 'country', 'price_range' );
	foreach ( $text as $key ) {
		$out[ $key ] = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : '';
	}
	$out['description']   = isset( $input['description'] ) ? sanitize_textarea_field( $input['description'] ) : '';
	$out['email']         = isset( $input['email'] ) ? sanitize_email( $input['email'] ) : '';
	$out['default_image'] = isset( $input['default_image'] ) ? esc_url_raw( $input['default_image'] ) : '';
	$out['lat']           = isset( $input['lat'] ) && is_numeric( $input['lat'] ) && abs( (float) $input['lat'] ) <= 90 ? (string) (float) $input['lat'] : '';
	$out['lng']           = isset( $input['lng'] ) && is_numeric( $input['lng'] ) && abs( (float) $input['lng'] ) <= 180 ? (string) (float) $input['lng'] : '';
	$out['hours']         = isset( $input['hours'] ) ? sanitize_textarea_field( $input['hours'] ) : '';

	$urls = array();
	foreach ( preg_split( '/\r\n|\r|\n/', isset( $input['social'] ) ? (string) $input['social'] : '' ) as $line ) {
		$url = esc_url_raw( trim( $line ), array( 'http', 'https' ) );
		if ( $url ) {
			$urls[] = $url;
		}
	}
	$out['social'] = implode( "\n", $urls );

	$ga = isset( $input['ga4_id'] ) ? strtoupper( trim( $input['ga4_id'] ) ) : '';
	if ( '' !== $ga && ! preg_match( '/^G-[A-Z0-9]{6,12}$/', $ga ) ) {
		add_settings_error( RIVERMERE_SEO_OPTION, 'ga4', __( 'The GA4 measurement ID must look like G-XXXXXXXXXX. It was not saved.', 'rivermere-core' ) );
		$ga = '';
	}
	$out['ga4_id']      = $ga;
	$out['ga4_consent'] = ! empty( $input['ga4_consent'] ) ? '1' : '0';
	return $out;
}

/**
 * Register settings and page.
 */
function rivermere_seo_admin() {
	add_options_page(
		__( 'Rivermere SEO Integration', 'rivermere-core' ),
		__( 'Rivermere SEO', 'rivermere-core' ),
		'manage_options',
		'rivermere-seo',
		'rivermere_seo_page'
	);
}
add_action( 'admin_menu', 'rivermere_seo_admin' );
add_action( 'admin_init', static function () {
	register_setting( 'rivermere_seo_group', RIVERMERE_SEO_OPTION, array( 'sanitize_callback' => 'rivermere_seo_sanitize' ) );
} );

/**
 * Settings page.
 */
function rivermere_seo_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$o    = rivermere_seo_settings();
	$name = RIVERMERE_SEO_OPTION;
	$row  = static function ( $key, $label, $type = 'text', $help = '', $attrs = '' ) use ( $o, $name ) {
		printf( '<tr><th scope="row"><label for="rm-%1$s">%2$s</label></th><td>', esc_attr( $key ), esc_html( $label ) );
		if ( 'textarea' === $type ) {
			printf( '<textarea id="rm-%1$s" name="%2$s[%1$s]" rows="5" class="large-text">%3$s</textarea>', esc_attr( $key ), esc_attr( $name ), esc_textarea( $o[ $key ] ) );
		} else {
			printf( '<input id="rm-%1$s" type="%4$s" name="%2$s[%1$s]" value="%3$s" class="regular-text" %5$s>', esc_attr( $key ), esc_attr( $name ), esc_attr( $o[ $key ] ), esc_attr( $type ), $attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static attrs.
		}
		if ( $help ) {
			echo '<p class="description">' . esc_html( $help ) . '</p>';
		}
		echo '</td></tr>';
	};
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Rivermere SEO Integration', 'rivermere-core' ); ?></h1>
		<?php if ( ! class_exists( 'RankMath' ) ) : ?>
			<div class="notice notice-warning"><p><?php esc_html_e( 'Rank Math SEO is not active. These settings only extend Rank Math’s output, so nothing is output until Rank Math is active.', 'rivermere-core' ); ?></p></div>
		<?php endif; ?>
		<p><?php esc_html_e( 'Page titles, meta descriptions, canonicals, robots and social tags are managed in Rank Math on each page. Use this screen for market-wide identity data used to extend Rank Math’s structured data.', 'rivermere-core' ); ?></p>
		<?php settings_errors( RIVERMERE_SEO_OPTION ); ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'rivermere_seo_group' ); ?>
			<h2><?php esc_html_e( 'Market identity', 'rivermere-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				$row( 'default_image', __( 'Default social sharing image URL', 'rivermere-core' ), 'url', __( 'Used only when a page has no image of its own (1200×630 recommended). Copy the URL from the Media Library.', 'rivermere-core' ) );
				$row( 'org_name', __( 'Market name', 'rivermere-core' ), 'text', __( 'Leave blank to keep the name set in Rank Math.', 'rivermere-core' ) );
				$row( 'description', __( 'Business description', 'rivermere-core' ), 'textarea', __( 'One or two factual sentences about the market (added to the Organization entity if Rank Math has none).', 'rivermere-core' ) );
				$row( 'telephone', __( 'Telephone', 'rivermere-core' ) );
				$row( 'email', __( 'Contact email', 'rivermere-core' ), 'email' );
				$row( 'price_range', __( 'Price range', 'rivermere-core' ), 'text', __( 'Optional, e.g. "$$". Leave blank if unsure.', 'rivermere-core' ) );
				?>
			</table>
			<h2><?php esc_html_e( 'Address and location', 'rivermere-core' ); ?></h2>
			<table class="form-table" role="presentation">
				<?php
				$row( 'street', __( 'Street address', 'rivermere-core' ) );
				$row( 'locality', __( 'City / town', 'rivermere-core' ) );
				$row( 'region', __( 'State / region', 'rivermere-core' ) );
				$row( 'postal', __( 'Postal code', 'rivermere-core' ) );
				$row( 'country', __( 'Country (2-letter code)', 'rivermere-core' ), 'text', '', 'maxlength="2"' );
				$row( 'lat', __( 'Latitude', 'rivermere-core' ), 'text', __( 'Only add real coordinates.', 'rivermere-core' ) );
				$row( 'lng', __( 'Longitude', 'rivermere-core' ) );
				$row( 'hours', __( 'Opening hours', 'rivermere-core' ), 'textarea', __( 'One per line: "Sunday 09:00-13:00" or "Saturday,Sunday 08:00-14:00".', 'rivermere-core' ) );
				$row( 'social', __( 'Social profile URLs', 'rivermere-core' ), 'textarea', __( 'One URL per line (added as sameAs).', 'rivermere-core' ) );
				?>
			</table>
			<h2><?php esc_html_e( 'Analytics', 'rivermere-core' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Prefer Google Site Kit if you already use it, and do not enable both. Nothing loads unless a valid ID is entered, and the tag loads only after the visitor accepts analytics cookies in the cookie banner.', 'rivermere-core' ); ?></p>
			<table class="form-table" role="presentation">
				<?php $row( 'ga4_id', __( 'GA4 measurement ID', 'rivermere-core' ), 'text', __( 'Format G-XXXXXXXXXX.', 'rivermere-core' ), 'placeholder="G-XXXXXXXXXX"' ); ?>
			</table>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/**
 * Convert "Sunday 09:00-13:00" lines to OpeningHoursSpecification.
 *
 * @param string $text Lines.
 * @return array
 */
function rivermere_seo_hours( $text ) {
	$days = array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday' );
	$out  = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $text ) as $line ) {
		if ( ! preg_match( '/^\s*([A-Za-z,\s]+?)\s+(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})\s*$/', $line, $m ) ) {
			continue;
		}
		$found = array();
		foreach ( explode( ',', $m[1] ) as $day ) {
			$day = ucfirst( strtolower( trim( $day ) ) );
			if ( in_array( $day, $days, true ) ) {
				$found[] = $day;
			}
		}
		if ( $found ) {
			$out[] = array(
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => $found,
				'opens'     => $m[2],
				'closes'    => $m[3],
			);
		}
	}
	return $out;
}

/**
 * Enrich Rank Math's publisher entity and add Event entities. Runs after Rank Math builds the graph.
 *
 * @param array $data JSON-LD graph.
 * @return array
 */
function rivermere_seo_jsonld( $data ) {
	if ( ! is_array( $data ) ) {
		return $data;
	}
	$o = rivermere_seo_settings();

	// Make sure the core entities exist on every page type (Rank Math omits them on single CPT pages).
	// Only added when missing, with the same @ids Rank Math uses, so nothing is ever duplicated.
	if ( empty( $data['publisher'] ) ) {
		$titles = (array) get_option( 'rank-math-options-titles', array() );
		$data['publisher'] = array(
			'@type' => 'Organization',
			'@id'   => home_url( '/#organization' ),
			'name'  => ! empty( $titles['knowledgegraph_name'] ) ? $titles['knowledgegraph_name'] : get_bloginfo( 'name' ),
			'url'   => home_url( '/' ),
		);
	}
	if ( empty( $data['WebSite'] ) ) {
		$data['WebSite'] = array(
			'@type'           => 'WebSite',
			'@id'             => home_url( '/#website' ),
			'url'             => home_url(),
			'name'            => get_bloginfo( 'name' ),
			'publisher'       => array( '@id' => $data['publisher']['@id'] ),
			'inLanguage'      => get_bloginfo( 'language' ),
			// The theme provides real WordPress search at /?s=.
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => home_url( '/?s={search_term_string}' ),
				'query-input' => 'required name=search_term_string',
			),
		);
	}
	if ( empty( $data['WebPage'] ) && is_singular() ) {
		$permalink       = get_permalink( get_queried_object_id() );
		$data['WebPage'] = array(
			'@type'      => 'WebPage',
			'@id'        => $permalink . '#webpage',
			'url'        => $permalink,
			'name'       => wp_get_document_title(),
			'isPartOf'   => array( '@id' => $data['WebSite']['@id'] ),
			'inLanguage' => get_bloginfo( 'language' ),
		);
		if ( ! empty( $data['BreadcrumbList']['@id'] ) ) {
			$data['WebPage']['breadcrumb'] = array( '@id' => $data['BreadcrumbList']['@id'] );
		}
	}

	// Organization / LocalBusiness enrichment (only fills gaps; never duplicates the entity).
	if ( isset( $data['publisher'] ) && is_array( $data['publisher'] ) ) {
		$pub = $data['publisher'];

		if ( $o['telephone'] && empty( $pub['telephone'] ) ) {
			$pub['telephone'] = $o['telephone'];
		}
		if ( $o['email'] && empty( $pub['email'] ) ) {
			$pub['email'] = $o['email'];
		}
		if ( $o['price_range'] && empty( $pub['priceRange'] ) ) {
			$pub['priceRange'] = $o['price_range'];
		}
		if ( $o['street'] && $o['locality'] && empty( $pub['address'] ) ) {
			$address = array_filter(
				array(
					'@type'           => 'PostalAddress',
					'streetAddress'   => $o['street'],
					'addressLocality' => $o['locality'],
					'addressRegion'   => $o['region'],
					'postalCode'      => $o['postal'],
					'addressCountry'  => $o['country'],
				)
			);
			$pub['address'] = $address;
		}
		if ( $o['lat'] && $o['lng'] && empty( $pub['geo'] ) ) {
			$pub['geo'] = array(
				'@type'     => 'GeoCoordinates',
				'latitude'  => (float) $o['lat'],
				'longitude' => (float) $o['lng'],
			);
		}
		$hours = rivermere_seo_hours( $o['hours'] );
		if ( $hours && empty( $pub['openingHoursSpecification'] ) ) {
			$pub['openingHoursSpecification'] = $hours;
		}
		$social = array_filter( preg_split( '/\r\n|\r|\n/', $o['social'] ) );
		if ( $social ) {
			$pub['sameAs'] = array_values( array_unique( array_merge( isset( $pub['sameAs'] ) ? (array) $pub['sameAs'] : array(), $social ) ) );
		}
		$has_local = $o['street'] || $o['hours'] || $o['telephone'];
		if ( $has_local && isset( $pub['@type'] ) ) {
			$types = (array) $pub['@type'];
			if ( ! in_array( 'LocalBusiness', $types, true ) && in_array( 'Organization', $types, true ) ) {
				$types[] = 'LocalBusiness';
			}
			$pub['@type'] = count( $types ) > 1 ? $types : $types[0];
		}

		// Reuse verified data already managed in Theme Settings (no second source of truth).
		if ( function_exists( 'get_field' ) ) {
			if ( empty( $pub['telephone'] ) && get_field( 'phone', 'option' ) ) {
				$pub['telephone'] = (string) get_field( 'phone', 'option' );
			}
			if ( empty( $pub['email'] ) && is_email( (string) get_field( 'contact_email', 'option' ) ) ) {
				$pub['email'] = (string) get_field( 'contact_email', 'option' );
			}
			$same = array();
			foreach ( array( 'facebook_url', 'instagram_url', 'linkedin_url' ) as $field_name ) {
				$url = esc_url_raw( (string) get_field( $field_name, 'option' ), array( 'http', 'https' ) );
				if ( $url ) {
					$same[] = $url;
				}
			}
			if ( $same ) {
				$pub['sameAs'] = array_values( array_unique( array_merge( isset( $pub['sameAs'] ) ? (array) $pub['sameAs'] : array(), $same ) ) );
			}
			if ( empty( $pub['openingHoursSpecification'] ) ) {
				$spec = array();
				foreach ( (array) get_field( 'opening_hours', 'option' ) as $row ) {
					if ( ! empty( $row['days'] ) && ! empty( $row['opens'] ) && ! empty( $row['closes'] ) ) {
						$spec[] = array(
							'@type'     => 'OpeningHoursSpecification',
							'dayOfWeek' => array_values( (array) $row['days'] ),
							'opens'     => $row['opens'],
							'closes'    => $row['closes'],
						);
					}
				}
				if ( $spec ) {
					$pub['openingHoursSpecification'] = $spec;
				}
			}
			if ( empty( $pub['logo'] ) ) {
				$logo = get_field( 'site_logo', 'option' );
				if ( ! empty( $logo['url'] ) ) {
					$pub['logo'] = array(
						'@type'  => 'ImageObject',
						'url'    => esc_url_raw( $logo['url'] ),
						'width'  => (int) ( $logo['width'] ?? 0 ),
						'height' => (int) ( $logo['height'] ?? 0 ),
					);
				}
			}
		}
		if ( $o['description'] && empty( $pub['description'] ) ) {
			$pub['description'] = $o['description'];
		}
		$data['publisher'] = $pub;
	}

	// Page types: About page => AboutPage (Rank Math's WebPage entity keeps its @id, so references stay intact).
	if ( isset( $data['WebPage'] ) && is_array( $data['WebPage'] ) && is_page_template( 'page-about.php' ) ) {
		$data['WebPage']['@type'] = 'AboutPage';
	}
	if ( isset( $data['WebPage'] ) && is_array( $data['WebPage'] ) && is_singular( 'rm_event' ) ) {
		$data['WebPage']['about'] = array( '@id' => get_permalink( get_queried_object_id() ) . '#event' );
	}

	// FAQPage: built from exactly the FAQ items rendered on the page (see theme inc/faq.php).
	$faq_items = apply_filters( 'rivermere_current_faqs', array() );
	if ( $faq_items && ! isset( $data['rivermere_faq'] ) ) {
		$has_faq = false;
		foreach ( $data as $entity ) {
			if ( is_array( $entity ) && isset( $entity['@type'] ) && in_array( 'FAQPage', (array) $entity['@type'], true ) ) {
				$has_faq = true; // Another source already provides it.
			}
		}
		if ( ! $has_faq ) {
			$entities = array();
			foreach ( $faq_items as $item ) {
				$entities[] = array(
					'@type'          => 'Question',
					'name'           => $item['q'],
					'acceptedAnswer' => array(
						'@type' => 'Answer',
						'text'  => trim( wp_strip_all_tags( $item['a'] ) ),
					),
				);
			}
			$page_id = isset( $data['WebPage']['@id'] ) ? $data['WebPage']['@id'] : '';
			$entity  = array(
				'@type'      => 'FAQPage',
				'@id'        => trailingslashit( get_permalink( get_queried_object_id() ) ? get_permalink( get_queried_object_id() ) : home_url( add_query_arg( array() ) ) ) . '#faq',
				'mainEntity' => $entities,
			);
			if ( $page_id ) {
				$entity['isPartOf'] = array( '@id' => $page_id );
			}
			$data['rivermere_faq'] = $entity;
		}
	}

	// Event entity for single events, unless Rank Math's schema module already emitted one.
	if ( is_singular( 'rm_event' ) ) {
		$has_event = false;
		foreach ( $data as $entity ) {
			if ( is_array( $entity ) && isset( $entity['@type'] ) && 'Event' === $entity['@type'] ) {
				$has_event = true;
			}
		}
		if ( ! $has_event ) {
			$event = rivermere_seo_event_entity( get_queried_object(), $o, $data );
			if ( $event ) {
				$data['rivermere_event'] = $event;
			}
		}
	}

	return $data;
}
add_filter( 'rank_math/json_ld', 'rivermere_seo_jsonld', 20 );

/**
 * Build a valid Event entity from real event data.
 *
 * @param WP_Post $post Event post.
 * @param array   $o    Settings.
 * @param array   $data Graph (for organizer link).
 * @return array|null
 */
function rivermere_seo_event_entity( $post, $o, $data ) {
	if ( ! $post instanceof WP_Post ) {
		return null;
	}
	$start = get_post_meta( $post->ID, 'event_start', true );
	if ( ! $start ) {
		return null; // startDate is required for Event.
	}
	$tz    = wp_timezone();
	$to_iso = static function ( $value ) use ( $tz ) {
		$d = date_create_immutable_from_format( 'Y-m-d H:i:s', (string) $value, $tz );
		return $d ? $d->format( 'c' ) : '';
	};
	$loc_name = get_post_meta( $post->ID, 'event_location', true );
	$status   = get_post_meta( $post->ID, 'event_status', true );
	$map      = array(
		'cancelled' => 'https://schema.org/EventCancelled',
		'postponed' => 'https://schema.org/EventPostponed',
	);
	$event    = array(
		'@type'               => 'Event',
		'@id'                 => get_permalink( $post ) . '#event',
		'name'                => get_the_title( $post ),
		'url'                 => get_permalink( $post ),
		'startDate'           => $to_iso( $start ),
		'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
		'eventStatus'         => isset( $map[ $status ] ) ? $map[ $status ] : 'https://schema.org/EventScheduled',
	);
	$end = get_post_meta( $post->ID, 'event_end', true );
	if ( $end ) {
		$event['endDate'] = $to_iso( $end );
	}
	$intro = get_post_meta( $post->ID, 'event_intro', true );
	$desc  = $intro ? $intro : ( has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( $post->post_content ), 40 ) );
	if ( $desc ) {
		$event['description'] = $desc;
	}
	$hero_id = (int) get_post_meta( $post->ID, 'event_hero_image', true );
	$img_url = $hero_id ? wp_get_attachment_image_url( $hero_id, 'large' ) : ( has_post_thumbnail( $post ) ? get_the_post_thumbnail_url( $post, 'large' ) : '' );
	if ( $img_url ) {
		$event['image'] = $img_url;
	}
	$address_text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( str_replace( array( '<br />', '<br>', "\n" ), ', ', (string) get_post_meta( $post->ID, 'event_address', true ) ) ) ) );
	if ( $loc_name || $address_text ) {
		$place = array( '@type' => 'Place', 'name' => $loc_name ? $loc_name : $address_text );
		if ( $address_text ) {
			// Free-text address from the event; structured only when the market identity matches.
			$place['address'] = $address_text;
		} elseif ( $o['street'] && $o['locality'] ) {
			$place['address'] = array_filter( array( '@type' => 'PostalAddress', 'streetAddress' => $o['street'], 'addressLocality' => $o['locality'], 'addressRegion' => $o['region'], 'postalCode' => $o['postal'], 'addressCountry' => $o['country'] ) );
		}
		$event['location'] = $place;
	}
	$organizer = get_post_meta( $post->ID, 'event_organizer', true );
	if ( $organizer ) {
		$event['organizer'] = array( '@type' => 'Organization', 'name' => $organizer, 'url' => home_url( '/' ) );
	} elseif ( isset( $data['publisher']['@id'] ) ) {
		$event['organizer'] = array( '@id' => $data['publisher']['@id'] );
	}
	// No Offer is emitted: the site stores no ticket price/currency, and a registration link alone is not an offer.
	return $event;
}

/**
 * Fallback social image when Rank Math found none.
 *
 * @param object $image Rank Math OpenGraph Image object.
 */
function rivermere_seo_default_image( $image ) {
	$o = rivermere_seo_settings();
	if ( $o['default_image'] && is_object( $image ) && method_exists( $image, 'has_images' ) && ! $image->has_images() ) {
		$image->add_image_by_url( $o['default_image'] );
	}
}
add_action( 'rank_math/opengraph/facebook/add_additional_images', 'rivermere_seo_default_image' );
add_action( 'rank_math/opengraph/twitter/add_additional_images', 'rivermere_seo_default_image' );

/**
 * GA4 configuration handed to the theme's consent manager (assets/js/main.min.js).
 *
 * Nothing is printed or loaded here: the Google tag is injected client-side ONLY after the visitor
 * grants analytics consent (cookie banner), and is skipped for editors and when Site Kit is active.
 * Without a valid measurement ID nothing at all is output.
 */
function rivermere_seo_analytics() {
	$o = rivermere_seo_settings();
	if ( ! $o['ga4_id'] || ! wp_script_is( 'rivermere-main', 'enqueued' ) ) {
		return;
	}
	if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
		return;
	}
	if ( defined( 'GOOGLESITEKIT_VERSION' ) && apply_filters( 'rivermere_analytics_skip_when_sitekit', true ) ) {
		return;
	}
	wp_add_inline_script( 'rivermere-main', 'window.rivermereAnalytics=' . wp_json_encode( array( 'id' => $o['ga4_id'] ) ) . ';', 'before' );
}
add_action( 'wp_enqueue_scripts', 'rivermere_seo_analytics', 30 );

/**
 * Final pass (after Rank Math's own graph merging at priority 99): About page = AboutPage
 * (+ FAQPage when it has a visible FAQ). FAQPage and AboutPage are both WebPage subtypes.
 *
 * @param array $data JSON-LD graph.
 * @return array
 */
function rivermere_seo_jsonld_final( $data ) {
	if ( is_array( $data ) && ! empty( $data['WebPage'] ) && is_page_template( 'page-about.php' ) ) {
		$types = array_diff( (array) $data['WebPage']['@type'], array( 'WebPage' ) );
		array_unshift( $types, 'AboutPage' );
		$types                    = array_values( array_unique( $types ) );
		$data['WebPage']['@type'] = 1 === count( $types ) ? $types[0] : $types;
	}
	return $data;
}
add_filter( 'rank_math/json_ld', 'rivermere_seo_jsonld_final', 100 );
