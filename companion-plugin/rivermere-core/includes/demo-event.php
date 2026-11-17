<?php
/**
 * Demo content for the Harvest Festival event (fictional). Applied once, only to empty fields,
 * so it never overwrites edits made in the admin.
 *
 * @package Rivermere_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main body for the demo event (editable in the normal editor).
 *
 * @return string
 */
function rivermere_demo_event_body() {
	return <<<'HTML'
<h2>About the Harvest Festival</h2>
<p>Every autumn, Rivermere Town Square fills with the colours and smells of the season. The Harvest Festival is our biggest market day of the year: a full day to celebrate the farmers, bakers and makers who feed the neighbourhood, with more stalls than a regular Sunday and plenty to do for every age.</p>
<p><em>Please note: this is fictional demonstration content for a sample website. All names, times and details are examples and should be replaced with real information.</em></p>

<h2>What to expect</h2>
<p>Come for breakfast, stay for lunch. The festival runs from morning until mid-afternoon and combines the usual weekly market with extra attractions around the square.</p>
<ul>
<li>Extended market with seasonal produce stalls, bakers and artisan makers</li>
<li>Cooking demonstrations using ingredients bought on the day</li>
<li>Live music on the bandstand from late morning</li>
<li>Free activities for children and a sunflower photo corner</li>
<li>A community table where neighbours can share recipes and swap seeds</li>
</ul>

<h2>Seasonal produce and local vendors</h2>
<p>Autumn is when the harvest is at its best. Expect crisp apples and pears, squashes and pumpkins, root vegetables, late tomatoes, fresh herbs and the last of the season's berries. Cheesemakers, honey producers and potters join our regular growers, and many vendors bring limited festival-only items.</p>
<p>Bring a reusable bag or basket: most stalls are happy to skip the packaging.</p>

<h3>Family-friendly activities</h3>
<p>Younger visitors can try apple bobbing, pumpkin decorating and a scarecrow-building workshop. A shaded quiet area with seating is available for families who need a break from the crowds, and dogs on a lead are welcome throughout the square.</p>

<h3>Live entertainment</h3>
<p>Local musicians play acoustic sets across the day, from folk and bluegrass to children's singalongs. Performers are announced on the schedule below, and each set lasts about 40 minutes.</p>

<h3>Food and refreshments</h3>
<p>Hot food stalls serve breakfast pastries, soups, wood-fired flatbreads and sweet treats made with festival ingredients. Vegetarian, vegan and gluten-free options are available, and water refill points are located next to the bandstand. Hot drinks and fresh-pressed apple juice are sold throughout the day.</p>

<h2>Getting there</h2>
<p>The festival takes place in and around Rivermere Town Square, in the centre of town. The square is a short walk from the main bus stop, and bicycle racks are available at all four corners. Limited parking is available on nearby streets; we recommend walking, cycling or sharing a ride, as spaces fill quickly on festival days. Use the directions button in the event information card for a map.</p>
HTML;
}

/**
 * Populate the Harvest Festival with demo details (only empty fields).
 *
 * @param int $id Event ID.
 * @return bool Whether anything was written.
 */
function rivermere_demo_event_details( $id ) {
	if ( get_field( 'event_intro', $id ) ) {
		return false;
	}

	$post = get_post( $id );
	if ( $post && false !== strpos( $post->post_content, 'Fictional demonstration event' ) ) {
		wp_update_post( array( 'ID' => $id, 'post_content' => rivermere_demo_event_body() ) );
	}

	$hero_img = rivermere_demo_image( 'placeholder-community.webp', 'Shoppers walking between market stalls beside a chalkboard sign (placeholder)' );
	$vendors  = get_posts( array( 'post_type' => 'rm_vendor', 'fields' => 'ids', 'posts_per_page' => 8, 'orderby' => 'menu_order', 'order' => 'ASC' ) );

	$fields = array(
		'event_label'         => 'Seasonal festival',
		'event_status'        => 'scheduled',
		'event_intro'         => 'A full day of local food, live music and family activities in the heart of Rivermere, celebrating the autumn harvest.',
		'event_location'      => 'Rivermere Town Square',
		'event_address'       => "12 Market Street\nRivermere",
		'event_admission'     => 'Free entry',
		'event_organizer'     => 'Rivermere Farmers Market',
		'event_contact_email' => 'events@rivermere-market.example',
		'event_contact_phone' => '(555) 010-0199',
		'event_accessibility' => "The square is step-free with paved paths throughout.\nAccessible toilets and a quiet seating area are located beside the bandstand.\nAssistance dogs are welcome and a limited number of accessible parking spaces are reserved on Market Street. Contact the organizers in advance if you need any extra support.",
		'event_schedule'      => array(
			array( 'time' => '9:00 am', 'title' => 'Market opens', 'description' => 'All stalls open, with fresh bread, coffee and breakfast pastries ready.' ),
			array( 'time' => '10:30 am', 'title' => 'Cooking demonstration', 'description' => 'A local chef cooks a three-ingredient seasonal dish using produce from the market.' ),
			array( 'time' => '11:30 am', 'title' => 'Live music: folk session', 'description' => 'An acoustic set on the bandstand.' ),
			array( 'time' => '12:30 pm', 'title' => 'Family workshop', 'description' => 'Scarecrow building and pumpkin decorating for children and their grown-ups.' ),
			array( 'time' => '1:30 pm', 'title' => 'Seed and recipe swap', 'description' => 'Bring a recipe or spare seeds to share at the community table.' ),
			array( 'time' => '2:30 pm', 'title' => 'Closing music and raffle', 'description' => 'A final set of songs, followed by the prize draw.' ),
		),
		'event_faqs'          => array(
			array( 'question' => 'Is the festival free to attend?', 'answer' => 'Entry to the square is free. Food, drink and goods are paid for directly with the vendors.' ),
			array( 'question' => 'What happens if it rains?', 'answer' => 'The festival goes ahead in light rain, and several stalls are under cover. If the event has to be changed or cancelled, the status at the top of this page is updated.' ),
			array( 'question' => 'Are dogs allowed?', 'answer' => 'Well-behaved dogs on a lead are welcome in the square. Please keep them away from food stalls.' ),
			array( 'question' => 'Can I pay by card?', 'answer' => 'Many vendors accept cards, but some are cash only, so we suggest bringing a little cash.' ),
		),
		'event_vendors'       => $vendors,
	);
	foreach ( $fields as $name => $value ) {
		update_field( $name, $value, $id );
	}
	if ( $hero_img ) {
		update_field( 'event_hero_image', $hero_img, $id );
		$gallery = array_filter( array( $hero_img, rivermere_demo_image( 'placeholder-event.webp', 'Sunflowers (placeholder)' ) ) );
		update_field( 'event_gallery', array_values( $gallery ), $id );
	}
	update_post_meta( $id, 'rank_math_description', 'Join the Rivermere Harvest Festival: seasonal produce, live music, cooking demonstrations and family activities in Rivermere Town Square. (Fictional demo event.)' );
	return true;
}
