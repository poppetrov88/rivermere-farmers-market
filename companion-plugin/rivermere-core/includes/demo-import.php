<?php
/**
 * Idempotent demo content importer (WP-CLI: `wp rivermere demo import`, or Tools → Rivermere Demo).
 *
 * Every created object is tagged with `_rivermere_demo` meta (or term meta) and looked up
 * by that key, so running the import repeatedly never creates duplicates and never overwrites
 * content an editor has changed (existing objects are left untouched unless --force).
 * ALL content is fictional demonstration data.
 *
 * @package Rivermere_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Find a demo object by key.
 *
 * @param string $key       Demo key.
 * @param string $post_type Post type.
 * @return int Post ID or 0.
 */
function rivermere_demo_find( $key, $post_type ) {
	$ids = get_posts(
		array(
			'post_type'      => $post_type,
			'post_status'    => 'any',
			'meta_key'       => '_rivermere_demo', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'fields'         => 'ids',
			'posts_per_page' => 1,
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

/**
 * Import an image from the theme's assets as a media library attachment (once per key).
 *
 * @param string $file Filename in assets/images.
 * @param string $alt  Alt text.
 * @return int Attachment ID or 0.
 */
function rivermere_demo_image( $file, $alt ) {
	$existing = rivermere_demo_find( 'img-' . $file, 'attachment' );
	if ( $existing ) {
		rivermere_demo_apply_image_meta( $existing, $file );
		return $existing;
	}
	$source = trailingslashit( get_theme_root() ) . 'rivermere-market/assets/images/' . $file;
	if ( ! file_exists( $source ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$tmp = wp_tempnam( $file );
	copy( $source, $tmp );
	$id = media_handle_sideload( array( 'name' => $file, 'tmp_name' => $tmp ), 0, null, array( 'post_title' => $alt ) );
	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );
		return 0;
	}
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	update_post_meta( $id, '_rivermere_demo', 'img-' . $file );
	rivermere_demo_apply_image_meta( $id, $file );
	return (int) $id;
}

/**
 * Descriptive metadata for the bundled placeholder images (what is actually visible in each).
 * These are crops of the design mock-up, NOT licensed photography.
 *
 * @return array<string,array<string,string>>
 */
function rivermere_demo_image_meta() {
	$note = ' Placeholder image cropped from the project design mock-up; not licensed photography. Replace before launch.';
	return array(
		'placeholder-hero.webp'            => array(
			'title'       => 'Vendor holding a crate of fresh vegetables at an outdoor market',
			'alt'         => 'Smiling woman in a straw hat holding a wooden crate of lettuce, carrots, radishes and tomatoes at an outdoor farmers market',
			'caption'     => '',
			'description' => 'A smiling market vendor in a straw hat and green apron holds a wooden crate of fresh produce, with market tents and shoppers blurred in the background.' . $note,
		),
		'placeholder-woman-crate.webp'     => array(
			'title'       => 'Vendor holding a crate of fresh vegetables',
			'alt'         => 'Smiling woman in a straw hat holding a wooden crate of lettuce, carrots, radishes and tomatoes',
			'caption'     => '',
			'description' => 'A smiling market vendor in a straw hat holds a wooden crate of fresh produce. Tighter crop of the hero placeholder.' . $note,
		),
		'placeholder-community.webp'       => array(
			'title'       => 'Shoppers and stalls beside a Farmers Market chalkboard sign',
			'alt'         => 'Shoppers walking between white-canopy market stalls, with sunflowers and a chalkboard sign reading Farmers Market, This Sunday, 9 AM to 1 PM',
			'caption'     => 'A busy Sunday at the market.',
			'description' => 'A paved market lane lined with white canopies and flower stalls. A chalkboard sign in the foreground announces the Sunday opening hours.' . $note,
		),
		'placeholder-event.webp'           => array(
			'title'       => 'Sunflowers in bloom',
			'alt'         => 'Bright yellow sunflowers with green leaves',
			'caption'     => 'Sunflowers in bloom.',
			'description' => 'Close view of several large yellow sunflowers and leaves, used as the thumbnail for the Harvest Festival.' . $note,
		),
		'placeholder-vendor-produce.webp'  => array(
			'title'       => 'Fresh tomatoes and leafy greens',
			'alt'         => 'Ripe red tomatoes and leafy green vegetables on a market stall',
			'caption'     => '',
			'description' => 'Red tomatoes and green leaves piled on a stall, representing the GreenField Farm produce vendor.' . $note,
		),
		'placeholder-vendor-dairy.webp'    => array(
			'title'       => 'Artisan cheeses on a board',
			'alt'         => 'Several wedges and blocks of artisan cheese on a wooden board',
			'caption'     => '',
			'description' => 'Wedges of pale cheese with rinds on a wooden board, representing the Lakeside Creamery vendor.' . $note,
		),
		'placeholder-vendor-honey.webp'    => array(
			'title'       => 'Jars of golden honey',
			'alt'         => 'Glass jars of golden honey lined up on a table',
			'caption'     => '',
			'description' => 'Several glass jars filled with amber honey, representing the Rivermere Honey Co. vendor.' . $note,
		),
		'placeholder-vendor-crafts.webp'   => array(
			'title'       => 'Handmade glazed pottery',
			'alt'         => 'Hand-made glazed ceramic jugs and mugs on a wooden shelf',
			'caption'     => '',
			'description' => 'Glazed ceramic jugs and mugs in muted blue and cream tones, representing the Willow Creek Crafts vendor.' . $note,
		),
	);
}

/**
 * Apply title, alt, caption and description to a demo attachment (only when its description is
 * still empty, so editor changes are never overwritten).
 *
 * @param int    $id   Attachment ID.
 * @param string $file Filename key.
 */
function rivermere_demo_apply_image_meta( $id, $file ) {
	$all = rivermere_demo_image_meta();
	if ( ! $id || ! isset( $all[ $file ] ) || '' !== trim( (string) get_post_field( 'post_content', $id ) ) ) {
		return;
	}
	$m = $all[ $file ];
	wp_update_post(
		array(
			'ID'           => $id,
			'post_title'   => $m['title'],
			'post_excerpt' => $m['caption'],
			'post_content' => $m['description'],
		)
	);
	update_post_meta( $id, '_wp_attachment_image_alt', $m['alt'] );
}

/**
 * Create a post if the demo key does not yet exist.
 *
 * @param string $key  Demo key.
 * @param array  $args wp_insert_post args.
 * @return array{0:int,1:bool} ID and whether created.
 */
function rivermere_demo_post( $key, $args ) {
	$id = rivermere_demo_find( $key, $args['post_type'] );
	if ( $id ) {
		return array( $id, false );
	}
	$id = wp_insert_post( array_merge( array( 'post_status' => 'publish' ), $args ), true );
	if ( is_wp_error( $id ) ) {
		return array( 0, false );
	}
	update_post_meta( $id, '_rivermere_demo', $key );
	return array( (int) $id, true );
}

/**
 * Run the import.
 *
 * @return string[] Log lines.
 */
function rivermere_demo_import() {
	$log = array();

	if ( ! function_exists( 'update_field' ) ) {
		return array( 'ACF Pro is required (update_field missing).' );
	}
	if ( ! post_type_exists( 'rm_vendor' ) ) {
		return array( 'Post types are not registered; activate the Rivermere Core plugin.' );
	}

	// 0. Image metadata for any already-imported placeholder images.
	foreach ( array_keys( rivermere_demo_image_meta() ) as $img_file ) {
		rivermere_demo_apply_image_meta( rivermere_demo_find( 'img-' . $img_file, 'attachment' ), $img_file );
	}

	// 1. Product categories (term meta marks demo terms).
	$cats  = array(
		'fresh-produce' => array( 'Fresh Produce', 'apple' ),
		'baked-goods'   => array( 'Baked Goods', 'croissant' ),
		'local-foods'   => array( 'Local Foods', 'cheese' ),
		'plants-garden' => array( 'Plants & Garden', 'plant' ),
		'handmade'      => array( 'Handmade Goods', 'basket' ),
		'live-events'   => array( 'Live Events', 'music' ),
	);
	$terms = array();
	foreach ( $cats as $slug => $info ) {
		$term = get_term_by( 'slug', $slug, 'rm_product_category' );
		if ( ! $term ) {
			$res = wp_insert_term( $info[0], 'rm_product_category', array( 'slug' => $slug ) );
			if ( is_wp_error( $res ) ) {
				continue;
			}
			$term = get_term( $res['term_id'], 'rm_product_category' );
			update_field( 'category_icon', $info[1], $term );
			$log[] = 'Created category: ' . $info[0];
		}
		$terms[ $slug ] = $term;
	}

	// 2. Vendors.
	$vendors = array(
		'greenfield'  => array( 'GreenField Farm', 'Fresh, seasonal produce', 'placeholder-vendor-produce.webp', 'fresh-produce', 'Family-run farm growing seasonal vegetables and herbs just outside town.' ),
		'lakeside'    => array( 'Lakeside Creamery', 'Artisan cheeses & dairy', 'placeholder-vendor-dairy.webp', 'local-foods', 'Small-batch cheeses made from local milk.' ),
		'honey'       => array( 'Rivermere Honey Co.', 'Local honey & bee products', 'placeholder-vendor-honey.webp', 'local-foods', 'Raw honey, beeswax candles and more from nearby hives.' ),
		'willowcreek' => array( 'Willow Creek Crafts', 'Handmade pottery & gifts', 'placeholder-vendor-crafts.webp', 'handmade', 'Hand-thrown pottery and gifts by local makers.' ),
	);
	$order = 0;
	foreach ( $vendors as $key => $v ) {
		list( $id, $created ) = rivermere_demo_post(
			'vendor-' . $key,
			array(
				'post_type'    => 'rm_vendor',
				'post_title'   => $v[0],
				'post_excerpt' => $v[4],
				'post_content' => '<p>' . $v[4] . '</p><p>This is fictional demonstration content. Replace it with your real vendor details.</p>',
				'menu_order'   => ++$order,
			)
		);
		if ( $id && $created ) {
			$img = rivermere_demo_image( $v[2], $v[0] . ' stall (placeholder)' );
			if ( $img ) {
				set_post_thumbnail( $id, $img );
			}
			update_field( 'vendor_tagline', $v[1], $id );
			update_field( 'vendor_featured', 1, $id );
			update_field( 'vendor_days', array( 'Sunday' ), $id );
			if ( isset( $terms[ $v[3] ] ) ) {
				wp_set_object_terms( $id, array( (int) $terms[ $v[3] ]->term_id ), 'rm_product_category' );
			}
			$log[] = 'Created vendor: ' . $v[0];
		}
	}

	// 3. Events — dated relative to today so the "upcoming" banner always has data.
	$events = array(
		'harvest' => array( 'Harvest Festival', 'Local Food • Live Music • Family Activities', '+21 days', '09:00:00', '15:00:00', 'Rivermere Town Square', 'placeholder-event.webp' ),
		'spring'  => array( 'Seed & Seedling Swap', 'Plants • Garden Advice • Free Seeds', '+49 days', '09:00:00', '12:00:00', 'Rivermere Town Square', '' ),
	);
	foreach ( $events as $key => $e ) {
		list( $id, $created ) = rivermere_demo_post(
			'event-' . $key,
			array(
				'post_type'    => 'rm_event',
				'post_title'   => $e[0],
				'post_excerpt' => $e[1],
				'post_content' => '<p>Fictional demonstration event. Replace with real details.</p>',
			)
		);
		if ( $id && $created ) {
			$ts  = strtotime( $e[2] );
			$ts += ( ( 7 - (int) wp_date( 'w', $ts ) ) % 7 ) * DAY_IN_SECONDS; // Move to the following Sunday.
			$day = wp_date( 'Y-m-d', $ts );
			update_field( 'event_tagline', $e[1], $id );
			update_field( 'event_start', $day . ' ' . $e[3], $id );
			update_field( 'event_end', $day . ' ' . $e[4], $id );
			update_field( 'event_location', $e[5], $id );
			if ( $e[6] ) {
				$img = rivermere_demo_image( $e[6], 'Sunflowers (placeholder)' );
				if ( $img ) {
					set_post_thumbnail( $id, $img );
				}
			}
			$log[] = 'Created event: ' . $e[0];
		}
	}

	// Harvest Festival: rich demo details (only fills a still-empty event).
	$harvest = rivermere_demo_find( 'event-harvest', 'rm_event' );
	if ( $harvest && function_exists( 'rivermere_demo_event_details' ) && rivermere_demo_event_details( $harvest ) ) {
		$log[] = 'Added Harvest Festival details';
	}

	// 4. Pages.
	list( $home, $home_new ) = rivermere_demo_post(
		'page-home',
		array(
			'post_type'  => 'page',
			'post_title' => 'Home',
			'post_name'  => 'home',
		)
	);
	if ( $home && $home_new ) {
		$hero  = rivermere_demo_image( 'placeholder-hero.webp', 'Smiling vendor holding a crate of fresh vegetables (placeholder)' );
		$comm  = rivermere_demo_image( 'placeholder-community.webp', 'Shoppers walking between market stalls beside a chalkboard sign (placeholder)' );
		$rows  = array(
			array(
				'acf_fc_layout' => 'hero',
				'eyebrow'       => 'Local Farmers • Fresh Food • Stronger Community',
				'heading'       => 'Your Local | Farmers Market',
				'description'   => 'Fresh, seasonal produce, homemade goods, and local products — all in one welcoming community market.',
				'primary_cta'   => array( 'url' => home_url( '/vendors/' ), 'title' => 'Visit the Market', 'target' => '' ),
				'secondary_cta' => array( 'url' => home_url( '/vendors/' ), 'title' => 'Meet Our Vendors', 'target' => '' ),
				'image'         => $hero,
				'image_alt'     => 'Smiling vendor holding a crate of fresh vegetables at a farmers market',
				'highlights'    => array(
					array( 'icon' => 'leaf', 'label' => 'Locally Grown' ),
					array( 'icon' => 'people', 'label' => 'Support Small Business' ),
					array( 'icon' => 'heart', 'label' => 'Community Focused' ),
				),
			),
			array(
				'acf_fc_layout' => 'product_categories',
				'heading'       => 'Explore What’s at the Market',
				'categories'    => array_map( static function ( $t ) { return (int) $t->term_id; }, array_values( $terms ) ),
			),
			array(
				'acf_fc_layout'  => 'image_text',
				'eyebrow'        => 'This Week at Rivermere',
				'heading'        => 'A Community | Gathering for | Local Living',
				'text'           => 'Shop fresh, meet local farmers, enjoy live music, and support our community.',
				'details'        => array(
					array( 'icon' => 'calendar', 'text' => 'Every Sunday, 9 AM – 1 PM' ),
					array( 'icon' => 'pin', 'text' => 'Rivermere Town Square' ),
					array( 'icon' => 'people', 'text' => 'Family & Pet Friendly' ),
				),
				'cta'            => array( 'url' => home_url( '/market-info/' ), 'title' => 'View Market Info', 'target' => '' ),
				'image'          => $comm,
				'image_alt'      => 'Shoppers walking between market stalls beside a chalkboard sign',
				'image_position' => 'left',
			),
			array(
				'acf_fc_layout' => 'event_banner',
				'label'         => 'Upcoming Event',
				'source'        => 'next',
				'button_label'  => 'Event Details',
			),
			array(
				'acf_fc_layout' => 'featured_vendors',
				'heading'       => 'Meet Our Featured Vendors',
				'source'        => 'featured',
				'count'         => 4,
			),
		);
		update_field( 'page_sections', $rows, $home );
		// Meta description is owned by Rank Math (stored in its own post meta).
		update_post_meta( $home, 'rank_math_description', 'Rivermere Farmers Market: fresh, seasonal produce, homemade goods and local products from nearby farmers and makers. (Fictional demo site.)' );
		$log[] = 'Created Home page with page-builder sections';
	}

	$stub_pages = array(
		'vendors'     => array( 'Vendors', 'Browse our vendors on the vendor archive.' ),
		'market-info' => array( 'Market Info', 'Opening hours, location and how to get here. (Demo content — edit me.)' ),
		'events'      => array( 'Events', 'See upcoming events at the market.' ),
		'about'       => array( 'About', 'Rivermere Farmers Market is a fictional demonstration market. (Demo content — edit me.)' ),
	);
	foreach ( $stub_pages as $slug => $p ) {
		if ( 'vendors' === $slug || 'events' === $slug ) {
			continue; // These slugs belong to the post type archives.
		}
		list( , $created ) = rivermere_demo_post(
			'page-' . $slug,
			array(
				'post_type'    => 'page',
				'post_title'   => $p[0],
				'post_name'    => $slug,
				'post_content' => '<p>' . $p[1] . '</p>',
			)
		);
		if ( $created ) {
			$log[] = 'Created page: ' . $p[0];
		}
	}

	// Legal pages (Privacy, Terms, Cookies).
	if ( function_exists( 'rivermere_demo_legal' ) ) {
		$log = array_merge( $log, rivermere_demo_legal() );
	}

	// Rank Math breadcrumbs: the theme's breadcrumb bar uses Rank Math when this module is on.
	// Only switched on when it is still at its default (off); explicit choices are never overridden.
	$rm_general = get_option( 'rank-math-options-general' );
	if ( is_array( $rm_general ) && 'on' !== ( $rm_general['breadcrumbs'] ?? 'off' ) ) {
		$rm_general['breadcrumbs']               = 'on';
		$rm_general['breadcrumbs_separator']     = '/';
		$rm_general['breadcrumbs_home']          = 'on';
		$rm_general['breadcrumbs_home_label']    = 'Home';
		$rm_general['breadcrumbs_search_format'] = 'Search Results';
		$rm_general['breadcrumbs_404_label']     = 'Page not found';
		update_option( 'rank-math-options-general', $rm_general );
		$log[] = 'Enabled Rank Math breadcrumbs';
	}

	// Phase 4+: FAQs, Become a Vendor, About Us, CTAs.
	if ( function_exists( 'rivermere_demo_phase4' ) ) {
		$log = array_merge( $log, rivermere_demo_phase4() );
	}

	// 5. Front page setting (only if no front page is configured yet).
	if ( $home && 'posts' === get_option( 'show_on_front' ) ) {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $home );
		$log[] = 'Set Home as the static front page';
	}

	// 6. Menu (only created once).
	if ( ! wp_get_nav_menu_object( 'Primary' ) ) {
		$menu_id = wp_create_nav_menu( 'Primary' );
		if ( ! is_wp_error( $menu_id ) ) {
			$items = array(
				array( 'Home', home_url( '/' ) ),
				array( 'Vendors', get_post_type_archive_link( 'rm_vendor' ) ),
				array( 'Market Info', home_url( '/market-info/' ) ),
				array( 'Events', get_post_type_archive_link( 'rm_event' ) ),
				array( 'About', home_url( '/about/' ) ),
			);
			foreach ( $items as $item ) {
				wp_update_nav_menu_item(
					$menu_id,
					0,
					array(
						'menu-item-title'  => $item[0],
						'menu-item-url'    => $item[1],
						'menu-item-status' => 'publish',
						'menu-item-type'   => 'custom',
					)
				);
			}
			$locations            = get_theme_mod( 'nav_menu_locations', array() );
			$locations['primary'] = $menu_id;
			$locations['footer']  = $menu_id;
			set_theme_mod( 'nav_menu_locations', $locations );
			$log[] = 'Created Primary menu';
		}
	}

	// 7. Theme Settings (only fills empty fields).
	$defaults = array(
		'contact_email'      => 'hello@rivermere-market.example',
		'phone'              => '(555) 010-0199',
		'address'            => "Rivermere Town Square\n12 Market Street\nRivermere",
		'market_days'        => 'Every Sunday, 9 AM – 1 PM',
		'footer_description' => 'A welcoming weekly market bringing local farmers, makers and neighbours together. (Fictional demo content.)',
		'header_cta'         => array( 'url' => home_url( '/about/' ), 'title' => 'Become a Vendor', 'target' => '' ),
	);
	foreach ( $defaults as $name => $value ) {
		if ( ! get_field( $name, 'option' ) ) {
			update_field( $name, $value, 'option' );
		}
	}
	if ( ! get_option( 'rivermere_demo_imported' ) ) {
		update_option( 'rivermere_demo_imported', gmdate( 'c' ), false );
		flush_rewrite_rules();
	}

	return $log ? $log : array( 'Nothing to do — demo content already exists.' );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	/**
	 * Rivermere helper commands.
	 */
	class Rivermere_CLI {
		/**
		 * Import ACF Local JSON groups into the database (so they show in ACF → Field Groups).
		 *
		 * @subcommand acf-sync
		 */
		public function acf_sync() {
			foreach ( rivermere_core_acf_sync() as $line ) {
				WP_CLI::log( $line );
			}
			WP_CLI::success( 'ACF sync finished.' );
		}

		/**
		 * Import (idempotent) fictional demo content.
		 *
		 * ## EXAMPLES
		 *     wp rivermere import-demo
		 *
		 * @subcommand import-demo
		 */
		public function import_demo() {
			foreach ( rivermere_demo_import() as $line ) {
				WP_CLI::log( $line );
			}
			WP_CLI::success( 'Demo import finished.' );
		}
	}
	WP_CLI::add_command( 'rivermere', 'Rivermere_CLI' );
}

/**
 * Tools page.
 */
function rivermere_demo_menu() {
	add_management_page( __( 'Rivermere Demo', 'rivermere-core' ), __( 'Rivermere Demo', 'rivermere-core' ), 'manage_options', 'rivermere-demo', 'rivermere_demo_page' );
}
add_action( 'admin_menu', 'rivermere_demo_menu' );

/**
 * Render tools page and process import.
 */
function rivermere_demo_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$log = array();
	if ( isset( $_POST['rivermere_demo_run'] ) ) {
		check_admin_referer( 'rivermere_demo_import' );
		$log = rivermere_demo_import();
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Rivermere Demo Content', 'rivermere-core' ); ?></h1>
		<p><?php esc_html_e( 'Imports fictional vendors, events, categories, a Home page and a menu. Safe to run repeatedly: existing demo items are never duplicated or overwritten.', 'rivermere-core' ); ?></p>
		<?php if ( $log ) : ?>
			<div class="notice notice-success"><ul><?php foreach ( $log as $line ) { echo '<li>' . esc_html( $line ) . '</li>'; } ?></ul></div>
		<?php endif; ?>
		<form method="post">
			<?php wp_nonce_field( 'rivermere_demo_import' ); ?>
			<?php submit_button( __( 'Import demo content', 'rivermere-core' ), 'primary', 'rivermere_demo_run' ); ?>
		</form>
	</div>
	<?php
}
