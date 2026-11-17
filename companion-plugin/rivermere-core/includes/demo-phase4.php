<?php
/**
 * Demo content for FAQs, Become a Vendor and About Us (fictional; idempotent; only fills EMPTY fields).
 *
 * @package Rivermere_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Set an ACF field only when it currently has no value.
 *
 * @param string $name  Field name.
 * @param mixed  $value Value.
 * @param mixed  $where Post ID or 'option'.
 */
function rivermere_demo_fill( $name, $value, $where ) {
	$key     = 'field_rm_' . $name;
	$current = get_field( $name, $where );
	$ref     = 'option' === $where ? get_option( '_options_' . $name ) : get_post_meta( (int) $where, '_' . $name, true );
	if ( empty( $current ) || empty( $ref ) ) {
		update_field( $name, $value, $where );
		// Guarantee the field-reference meta so get_field() by NAME formats values (e.g. images).
		if ( 'option' === $where ) {
			update_option( '_options_' . $name, $key );
		} else {
			update_post_meta( (int) $where, '_' . $name, $key );
		}
	}
}

/**
 * FAQ rows helper.
 *
 * @param array $pairs question => answer.
 * @return array
 */
function rivermere_demo_faq_rows( $pairs ) {
	$rows = array();
	foreach ( $pairs as $q => $a ) {
		$rows[] = array( 'question' => $q, 'answer' => $a );
	}
	return $rows;
}

/**
 * Run all phase-4+ demo setup.
 *
 * @return string[] Log.
 */
function rivermere_demo_phase4() {
	$log = array();
	if ( ! function_exists( 'update_field' ) ) {
		return $log;
	}

	// ---- Rank Math: describe the site as an Organization (a market is not a person).
	$titles = get_option( 'rank-math-options-titles' );
	if ( is_array( $titles ) && 'person' === ( $titles['knowledgegraph_type'] ?? '' ) ) {
		$titles['knowledgegraph_type'] = 'company';
		if ( empty( $titles['knowledgegraph_name'] ) ) {
			$titles['knowledgegraph_name'] = get_bloginfo( 'name' );
		}
		update_option( 'rank-math-options-titles', $titles );
		$log[] = 'Rank Math: set Knowledge Graph type to Organization';
	}

	// Static pages are WebPages, not Articles (Rank Math defaults to Article + Person author).
	$titles = get_option( 'rank-math-options-titles' );
	if ( is_array( $titles ) && 'article' === ( $titles['pt_page_default_rich_snippet'] ?? '' ) ) {
		$titles['pt_page_default_rich_snippet'] = 'off';
		update_option( 'rank-math-options-titles', $titles );
		$log[] = 'Rank Math: pages no longer default to Article schema';
	}

	// ---- Global FAQs (Theme Settings).
	rivermere_demo_fill( 'faq_vendors_intro', 'Quick answers about the vendors you will meet at Rivermere and how to become one.', 'option' );
	rivermere_demo_fill( 'faq_vendors_items', rivermere_demo_faq_rows( array(
		'How can I become a vendor?' => 'Visit our <a href="' . esc_url( rivermere_demo_vendor_url() ) . '">Become a Vendor</a> page, read the requirements and complete the application form. The market team reviews every application.',
		'What types of vendors participate?' => 'Growers, bakers, food producers, plant and garden sellers and independent makers. The exact mix changes with the season.',
		'Are products locally sourced?' => 'Vendors are asked to describe where and how their products are made or grown when they apply. Ask a vendor at their stall if you want to know more about a particular product.',
		'Can I contact vendors directly?' => 'Many vendors list a website, email or social profile on their page. Otherwise, speak to them at their stall during market hours.',
		'Are applications open year-round?' => 'Applications can be submitted through the form on the Become a Vendor page whenever it is open. If it is closed for a period, the page says so.',
		'Do vendors accept card payments?' => 'It varies by vendor. Some accept cards and some are cash only, so it is a good idea to bring a little cash.',
	) ), 'option' );
	rivermere_demo_fill( 'faq_events_intro', 'What to know before you come to a market event.', 'option' );
	rivermere_demo_fill( 'faq_events_items', rivermere_demo_faq_rows( array(
		'Are events free?' => 'Admission details are listed on each event page under "Admission". Food, drink and goods are paid for directly with vendors.',
		'Is registration required?' => 'Only when an event page shows a Register button. Otherwise you can simply turn up.',
		'Are events family-friendly?' => 'Most market events are designed for all ages. Each event page lists the activities that are planned.',
		'Are pets allowed?' => 'Policies can differ by event. Check the event page for details; for example, well-behaved dogs on a lead are welcome at the Harvest Festival.',
		'What happens during bad weather?' => 'If an event is changed, postponed or cancelled, the status at the top of its event page is updated. Check the page before you travel.',
		'Is parking available?' => 'Parking arrangements differ by event. The "Getting there" information on each event page explains the options, and walking, cycling or sharing a ride is encouraged.',
		'Is the event accessible?' => 'Each event page includes an accessibility section where the organizers have provided information. If you need extra support, contact them using the details on the page.',
	) ), 'option' );

	// ---- Vendor application settings (Theme Settings).
	rivermere_demo_fill( 'apps_instructions', 'Tell us about your business and products. Fields marked * are required.', 'option' );
	rivermere_demo_fill( 'apps_success', 'Thank you for applying! Your application has been received. Our team will review your information and contact you about the next steps.', 'option' );
	rivermere_demo_fill( 'apps_booth', 'Stall and equipment requirements are shared with approved applicants.', 'option' );

	// ---- Become a Vendor page.
	list( $vp, $vp_new ) = rivermere_demo_post( 'page-become-a-vendor', array( 'post_type' => 'page', 'post_title' => 'Become a Vendor', 'post_name' => 'become-a-vendor' ) );
	if ( $vp ) {
		update_post_meta( $vp, '_wp_page_template', 'page-become-a-vendor.php' );
		// Headings and labels (ACF default_value only applies in the admin, so set them explicitly).
		foreach ( array(
			'vp_eyebrow'          => 'Grow with our community',
			'vp_title'            => 'Become a Vendor at Rivermere Farmers Market',
			'vp_description'      => 'Share your products, connect with local shoppers and become part of a community celebrating fresh food, local makers and independent businesses.',
			'vp_cta_apply'        => 'Apply Now',
			'vp_cta_requirements' => 'Learn About Requirements',
			'vp_benefits_title'   => 'Why Join Our Market?',
			'vp_steps_title'      => 'How It Works',
			'vp_req_title'        => 'Vendor Requirements',
			'vp_form_title'       => 'Apply to Become a Vendor',
			'vp_faq_title'        => 'Vendor application questions',
			'vp_cta_title'        => 'Questions before you apply?',
			'vp_cta_text'         => 'See who already sells at the market, or get in touch and we will point you in the right direction.',
		) as $vp_name => $vp_value ) {
			rivermere_demo_fill( $vp_name, $vp_value, $vp );
		}
		$image = rivermere_demo_image( 'placeholder-community.webp', 'Shoppers walking between market stalls beside a chalkboard sign (placeholder)' );
		if ( $image ) {
			rivermere_demo_fill( 'vp_image', $image, $vp );
		}
		rivermere_demo_fill( 'vp_benefits', array(
			array( 'icon' => 'people', 'title' => 'Reach Local Customers', 'text' => 'Meet shoppers who come to the market looking for fresh, local and handmade products.' ),
			array( 'icon' => 'plant', 'title' => 'Grow Your Business', 'text' => 'Build a regular customer base and get direct feedback on what people love.' ),
			array( 'icon' => 'heart', 'title' => 'Join a Supportive Community', 'text' => 'Work alongside other growers, makers and food producers who share your values.' ),
			array( 'icon' => 'basket', 'title' => 'Showcase Your Products', 'text' => 'Tell your story in person and let people see, smell and taste what you make.' ),
		), $vp );
		rivermere_demo_fill( 'vp_steps', array(
			array( 'title' => 'Complete the application', 'text' => 'Fill in the form below with details about your business and products.' ),
			array( 'title' => 'The market team reviews it', 'text' => 'We read every application and consider how it fits the market.' ),
			array( 'title' => 'We contact you', 'text' => 'We get in touch by email or phone about the next steps.' ),
			array( 'title' => 'Participation details', 'text' => 'Approved applicants receive the information they need to take part.' ),
		), $vp );
		rivermere_demo_fill( 'vp_req_intro', 'Every applicant is considered individually. Here is what we usually ask about.', $vp );
		rivermere_demo_fill( 'vp_requirements', array(
			array( 'title' => 'Business details', 'text' => 'Your business name and contact details so we can respond to your application.' ),
			array( 'title' => 'Product information', 'text' => 'What you sell, with a clear description of your main products.' ),
			array( 'title' => 'How products are made or sourced', 'text' => 'Where your products are grown, made or sourced.' ),
			array( 'title' => 'Market participation', 'text' => 'Which market days you hope to attend and any previous market experience.' ),
			array( 'title' => 'Permits and licences', 'text' => 'If your products or activities require them, you will need to hold the permits that apply to you.' ),
			array( 'title' => 'Insurance', 'text' => 'If required for your type of business, you may be asked to confirm you are insured.' ),
			array( 'title' => 'Stall setup and market policies', 'text' => 'Approved vendors follow the market’s stall and conduct policies, which are shared when you are approved.' ),
		), $vp );
		rivermere_demo_fill( 'vp_form_intro', 'Complete the form below to apply. Submitting it does not guarantee a place at the market.', $vp );
		rivermere_demo_fill( 'vp_faq_intro', 'Answers to common questions about applying.', $vp );
		rivermere_demo_fill( 'vp_faq_items', rivermere_demo_faq_rows( array(
			'Who can apply?' => 'Local growers, food producers, bakers, plant sellers and independent makers are welcome to apply. Each application is reviewed individually.',
			'What products are accepted?' => 'We look for products that fit the market: fresh produce, baked and prepared food, plants, and handmade goods. Describe your products in the form and we will let you know.',
			'Is there an application fee?' => 'Any application fee is stated in the Vendor Requirements section of this page. If none is listed, no fee is requested for applying.',
			'Are permits required?' => 'It depends on what you sell and the rules that apply where you operate. You are responsible for holding any permits or licences your products require.',
			'How long does review take?' => 'We cannot promise a fixed timeframe. The team reads applications as they arrive and contacts applicants about next steps.',
			'What happens after I submit?' => 'You will see a confirmation on the page once your application has been received. The team then reviews it and contacts you. Receiving an acknowledgement does not mean the application is approved.',
		) ), $vp );
		update_post_meta( $vp, 'rank_math_title', 'Become a Vendor | Rivermere Farmers Market' );
		update_post_meta( $vp, 'rank_math_description', 'Interested in selling at Rivermere Farmers Market? Explore vendor opportunities, application requirements and how to apply to join our market.' );
		if ( $vp_new ) {
			$log[] = 'Created Become a Vendor page';
		}
	}

	// ---- About Us page (renames the original "About" page, keeping its ID).
	$about_id = rivermere_demo_find( 'page-about', 'page' );
	if ( $about_id ) {
		$about = get_post( $about_id );
		if ( 'about' === $about->post_name ) {
			wp_update_post( array( 'ID' => $about_id, 'post_name' => 'about-us', 'post_title' => 'About Us' ) );
			$log[] = 'Renamed About page to About Us (/about-us/)';
		}
	} else {
		list( $about_id ) = rivermere_demo_post( 'page-about', array( 'post_type' => 'page', 'post_title' => 'About Us', 'post_name' => 'about-us' ) );
	}
	if ( $about_id ) {
		update_post_meta( $about_id, '_wp_page_template', 'page-about.php' );
		foreach ( array(
			'ap_eyebrow'        => 'Our story',
			'ap_title'          => 'More Than a Market. A Community Growing Together.',
			'ap_cta1'           => 'Explore Our Vendors',
			'ap_cta2'           => 'Visit the Market',
			'ap_story_title'    => 'Rooted in Local. Growing Together.',
			'ap_what_title'     => 'What You’ll Find at Rivermere',
			'ap_mission_eyebrow' => 'What drives us',
			'ap_mission_title'  => 'Good Food. Strong Connections. Thriving Communities.',
			'ap_values_title'   => 'What We Value',
			'ap_vendors_title'  => 'Meet the People Behind the Market',
			'ap_vendors_cta'    => 'Explore All Vendors',
			'ap_season_title'   => 'Something Fresh Every Season',
			'ap_season_cta'     => 'See Upcoming Events',
			'ap_visit_title'    => 'Plan Your Visit',
			'ap_cta_title'      => 'There’s a Place for Everyone at Rivermere',
			'ap_cta_visitor_label' => 'Explore the Market',
			'ap_cta_vendor_label'  => 'Become a Vendor',
			'ap_faq_title'      => 'About the market: your questions',
		) as $ap_name => $ap_value ) {
			rivermere_demo_fill( $ap_name, $ap_value, $about_id );
		}
		$hero  = rivermere_demo_image( 'placeholder-woman-crate.webp', 'Vendor holding a crate of fresh vegetables (placeholder)' );
		$story = rivermere_demo_image( 'placeholder-community.webp', 'Market stalls (placeholder)' );
		if ( $hero ) {
			rivermere_demo_fill( 'ap_image', $hero, $about_id );
		}
		if ( $story ) {
			rivermere_demo_fill( 'ap_story_image', $story, $about_id );
			rivermere_demo_fill( 'ap_season_image', $story, $about_id );
		}
		rivermere_demo_fill( 'ap_intro', 'At Rivermere Farmers Market, we believe great food brings people together. Our market celebrates local growers, independent makers and the simple joy of discovering fresh, seasonal products close to home.', $about_id );
		rivermere_demo_fill( 'ap_story_body', '<p>A farmers market is more than a place to buy food. It is where the people who grow and make things meet the people who enjoy them, and where a conversation over a crate of vegetables can turn into a regular habit.</p><p>Shopping directly from growers means you can ask how something was grown, hear what is in season and take home produce that has not travelled far. For growers and independent makers, the market is a way to reach neighbours, share their craft and build a loyal following.</p><p>Eating with the seasons keeps shopping interesting: spring greens give way to summer fruit, autumn squash and winter roots. And because every stall belongs to a small, independent business, the money you spend stays close to home.</p><p>Above all, the market is a gathering place. People come for food and stay for the music, the events and the company.</p><p><em>Rivermere is a fictional demonstration market. This story describes what the market aims to offer and does not claim any history, awards or visitor numbers.</em></p>', $about_id );
		rivermere_demo_fill( 'ap_what_cards', array(
			array( 'icon' => 'apple', 'title' => 'Fresh Seasonal Produce', 'text' => 'Fruit, vegetables and herbs that change through the year, so there is always something new to try.', 'link' => 'vendors', 'link_label' => 'Browse vendors' ),
			array( 'icon' => 'plant', 'title' => 'Local Farmers and Growers', 'text' => 'Meet the people who grow your food and ask them anything about how it was produced.', 'link' => 'vendors', 'link_label' => 'Meet the vendors' ),
			array( 'icon' => 'croissant', 'title' => 'Independent Artisan Vendors', 'text' => 'Bakers, cheesemakers, potters and other small businesses showing what they make by hand.', 'link' => 'vendors', 'link_label' => 'See who is here' ),
			array( 'icon' => 'music', 'title' => 'Community Events', 'text' => 'Seasonal celebrations, music and family activities that turn market day into an occasion.', 'link' => 'events', 'link_label' => 'View events' ),
		), $about_id );
		rivermere_demo_fill( 'ap_mission_text', 'We want the market to be a place where good food is easy to find and everyone feels welcome. Our aim is to connect people with the producers behind their food and to make it easier for small, local businesses to thrive.', $about_id );
		rivermere_demo_fill( 'ap_mission_points', array(
			array( 'text' => 'Support local agriculture' ),
			array( 'text' => 'Help independent businesses grow' ),
			array( 'text' => 'Encourage seasonal shopping' ),
			array( 'text' => 'Create welcoming community experiences' ),
			array( 'text' => 'Connect people with food producers' ),
		), $about_id );
		rivermere_demo_fill( 'ap_values', array(
			array( 'icon' => 'pin', 'title' => 'Local First', 'text' => 'We favour growers and makers from our own region.' ),
			array( 'icon' => 'people', 'title' => 'Community Connection', 'text' => 'The market is a place to meet neighbours as well as shop.' ),
			array( 'icon' => 'leaf', 'title' => 'Fresh & Seasonal', 'text' => 'We celebrate what is in season and the people who grow it.' ),
			array( 'icon' => 'heart', 'title' => 'Welcoming Experiences', 'text' => 'Families, friends and curious first-timers are all welcome.' ),
			array( 'icon' => 'basket', 'title' => 'Supporting Small Businesses', 'text' => 'Every stall is an independent business with a story to tell.' ),
			array( 'icon' => 'plant', 'title' => 'Growing Together', 'text' => 'We learn from vendors and visitors and keep improving the market.' ),
		), $about_id );
		rivermere_demo_fill( 'ap_vendors_intro', 'A few of the growers and makers you can meet at the market.', $about_id );
		rivermere_demo_fill( 'ap_season_text', 'Through the year the market follows the seasons: fresh produce, artisan goods, local food, community gatherings and special events. What is available changes from week to week, so every visit is a little different.', $about_id );
		rivermere_demo_fill( 'ap_visit_intro', 'Find out when and where the market takes place.', $about_id );
		rivermere_demo_fill( 'ap_cta_visitor_title', 'For visitors', $about_id );
		rivermere_demo_fill( 'ap_cta_visitor_text', 'Come and see what is fresh this week, meet the vendors and enjoy the atmosphere.', $about_id );
		rivermere_demo_fill( 'ap_cta_vendor_title', 'For vendors', $about_id );
		rivermere_demo_fill( 'ap_cta_vendor_text', 'Grow, bake or make something special? Find out how to apply to join the market.', $about_id );
		rivermere_demo_fill( 'ap_faq_intro', 'Quick answers about the market.', $about_id );
		rivermere_demo_fill( 'ap_faq_items', rivermere_demo_faq_rows( array(
			'What is Rivermere Farmers Market?' => 'A community-focused market where local growers, makers and food producers sell directly to visitors. (Rivermere is a fictional demonstration market.)',
			'What products can visitors find?' => 'Fresh seasonal produce, baked goods, local foods, plants, handmade goods and more. The mix changes with the season and the vendors attending.',
			'Who participates?' => 'Independent growers, bakers, artisans and small businesses. You can see featured vendors on the <a href="' . esc_url( get_post_type_archive_link( 'rm_vendor' ) ) . '">Vendors page</a>.',
			'Does the market host events?' => 'Yes. Seasonal celebrations and community events are listed on the <a href="' . esc_url( get_post_type_archive_link( 'rm_event' ) ) . '">Events page</a>.',
			'How can vendors apply?' => 'Through the form on the <a href="' . esc_url( rivermere_demo_vendor_url() ) . '">Become a Vendor</a> page.',
			'Where can visitors find opening hours?' => 'Market days and hours are shown in the "Plan Your Visit" section above and in the website footer.',
		) ), $about_id );
		update_post_meta( $about_id, 'rank_math_title', 'About Rivermere Farmers Market | Local Food & Community' );
		update_post_meta( $about_id, 'rank_math_description', 'Discover Rivermere Farmers Market, a community-focused market bringing together local farmers, fresh seasonal produce, artisan vendors and memorable market experiences.' );
	}

	// ---- Global CTAs and menus: every vendor CTA -> Become a Vendor; About links -> About Us.
	$vendor_url = rivermere_demo_vendor_url();
	$header     = get_field( 'header_cta', 'option' );
	if ( empty( $header['url'] ) || false !== strpos( $header['url'], '/about' ) ) {
		update_field( 'header_cta', array( 'url' => $vendor_url, 'title' => 'Become a Vendor', 'target' => '' ), 'option' );
		$log[] = 'Header CTA now links to Become a Vendor';
	}
	if ( empty( get_field( 'footer_cta', 'option' ) ) ) {
		update_field( 'footer_cta', array( 'url' => $vendor_url, 'title' => 'Become a Vendor', 'target' => '' ), 'option' );
	}
	if ( $about_id ) {
		$about_url = get_permalink( $about_id );
		foreach ( get_posts( array( 'post_type' => 'nav_menu_item', 'numberposts' => -1, 'post_status' => 'any' ) ) as $item ) {
			$url = get_post_meta( $item->ID, '_menu_item_url', true );
			if ( $url && preg_match( '#/about/?$#', $url ) ) {
				update_post_meta( $item->ID, '_menu_item_url', $about_url );
				$log[] = 'Menu item "' . $item->post_title . '" now points to About Us';
			}
		}
	}
	return $log;
}

/**
 * Become a Vendor URL for demo content (works before the theme helper is loaded).
 *
 * @return string
 */
function rivermere_demo_vendor_url() {
	$id = rivermere_demo_find( 'page-become-a-vendor', 'page' );
	return $id ? get_permalink( $id ) : home_url( '/become-a-vendor/' );
}
