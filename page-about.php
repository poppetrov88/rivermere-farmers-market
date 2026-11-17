<?php
/**
 * Template Name: About Us
 * Template Post Type: page
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

get_header();

$vendors_url = get_post_type_archive_link( 'rm_vendor' );
$events_url  = get_post_type_archive_link( 'rm_event' );
$market_id   = rivermere_find_page_id( 'page-market-info.php', 'market-info' );
$market_url  = $market_id ? get_permalink( $market_id ) : '';
?>
<main id="main-content" class="site-main site-main--about">
	<?php
	while ( have_posts() ) :
		the_post();
		$id = get_the_ID();

		// 1. Hero.
		get_template_part(
			'template-parts/components/landing-hero',
			null,
			array(
				'eyebrow' => rivermere_field( 'ap_eyebrow', $id, __( 'Our story', 'rivermere-market' ) ),
				'title'   => rivermere_field( 'ap_title', $id, get_the_title() ),
				'intro'   => rivermere_field( 'ap_intro', $id, '' ),
				'image'   => rivermere_field( 'ap_image', $id, array() ),
				'buttons' => array(
					array( 'label' => rivermere_field( 'ap_cta1', $id, __( 'Explore Our Vendors', 'rivermere-market' ) ), 'url' => $vendors_url, 'variant' => 'accent' ),
					array( 'label' => rivermere_field( 'ap_cta2', $id, __( 'Visit the Market', 'rivermere-market' ) ), 'url' => '#visit', 'variant' => 'outline', 'icon' => false ),
				),
			)
		);

		// 2. Our story.
		$story_title = rivermere_field( 'ap_story_title', $id, '' );
		$story_body  = rivermere_field( 'ap_story_body', $id, '' );
		$story_image = rivermere_field( 'ap_story_image', $id, array() );
		if ( $story_title || $story_body ) :
			?>
			<section class="section section--flow story" aria-labelledby="story-title">
				<div class="container story__grid">
					<?php if ( ! empty( $story_image['ID'] ) ) : ?>
						<figure class="story__media">
							<?php echo wp_get_attachment_image( $story_image['ID'], 'rivermere-feature', false, array( 'class' => 'story__img', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(min-width: 1024px) 560px, 100vw' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</figure>
					<?php endif; ?>
					<div class="story__text">
						<?php $eyebrow = rivermere_field( 'ap_story_eyebrow', $id, '' ); ?>
						<?php if ( $eyebrow ) : ?>
							<p class="eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
						<?php endif; ?>
						<h2 class="story__title" id="story-title"><?php echo esc_html( $story_title ); ?></h2>
						<div class="entry-content"><?php echo wp_kses_post( $story_body ); ?></div>
					</div>
				</div>
			</section>
			<?php
		endif;

		// 3. What we do.
		$link_map = array(
			'vendors' => $vendors_url,
			'events'  => $events_url,
			'market'  => $market_url,
		);
		$what     = array();
		foreach ( (array) rivermere_field( 'ap_what_cards', $id, array() ) as $card ) {
			$key    = $card['link'] ?? 'none';
			$what[] = array(
				'icon'  => $card['icon'] ?? 'leaf',
				'title' => $card['title'] ?? '',
				'text'  => $card['text'] ?? '',
				'url'   => ( 'none' !== $key && ! empty( $link_map[ $key ] ) ) ? $link_map[ $key ] : '',
				'label' => $card['link_label'] ?? '',
			);
		}
		get_template_part( 'template-parts/components/feature-cards', null, array( 'id' => 'what-we-do', 'tone' => 'alt', 'title' => rivermere_field( 'ap_what_title', $id, '' ), 'cards' => $what, 'columns' => 4 ) );

		// 4. Mission.
		$mission_title  = rivermere_field( 'ap_mission_title', $id, '' );
		$mission_points = array_filter( (array) rivermere_field( 'ap_mission_points', $id, array() ), static function ( $p ) {
			return ! empty( $p['text'] );
		} );
		if ( $mission_title ) :
			?>
			<section class="section section--flow mission" aria-labelledby="mission-title">
				<div class="container">
					<div class="mission__panel">
						<div class="mission__text">
							<p class="eyebrow eyebrow--accent"><?php echo esc_html( rivermere_field( 'ap_mission_eyebrow', $id, '' ) ); ?></p>
							<h2 class="mission__title" id="mission-title"><?php echo esc_html( $mission_title ); ?></h2>
							<p><?php echo esc_html( rivermere_field( 'ap_mission_text', $id, '' ) ); ?></p>
						</div>
						<?php if ( $mission_points ) : ?>
							<ul class="mission__points">
								<?php foreach ( $mission_points as $point ) : ?>
									<li><?php echo rivermere_icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> <span><?php echo esc_html( $point['text'] ); ?></span></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
					</div>
				</div>
			</section>
			<?php
		endif;

		// 5. Values.
		get_template_part( 'template-parts/components/feature-cards', null, array( 'id' => 'values', 'title' => rivermere_field( 'ap_values_title', $id, '' ), 'cards' => (array) rivermere_field( 'ap_values', $id, array() ), 'columns' => 3 ) );

		// 6. Featured vendors (dynamic).
		$vendors = rivermere_get_featured_vendors( 4 );
		if ( $vendors ) :
			?>
			<section class="section section--flow section--vendors about-vendors" aria-labelledby="about-vendors-title">
				<div class="container">
					<header class="feature-section__head">
						<h2 class="feature-section__title" id="about-vendors-title"><?php echo esc_html( rivermere_field( 'ap_vendors_title', $id, __( 'Meet the People Behind the Market', 'rivermere-market' ) ) ); ?></h2>
						<?php $vintro = rivermere_field( 'ap_vendors_intro', $id, '' ); ?>
						<?php if ( $vintro ) : ?>
							<p class="feature-section__intro"><?php echo esc_html( $vintro ); ?></p>
						<?php endif; ?>
					</header>
					<ul class="vendor-grid">
						<?php foreach ( $vendors as $vendor ) : ?>
							<li><?php get_template_part( 'template-parts/components/vendor-card', null, array( 'post' => $vendor ) ); ?></li>
						<?php endforeach; ?>
					</ul>
					<p class="section__more"><?php echo rivermere_button( array( 'url' => $vendors_url, 'title' => rivermere_field( 'ap_vendors_cta', $id, __( 'Explore All Vendors', 'rivermere-market' ) ) ), 'outline' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
				</div>
			</section>
			<?php
		endif;

		// 7. Seasons.
		$season_title = rivermere_field( 'ap_season_title', $id, '' );
		$season_image = rivermere_field( 'ap_season_image', $id, array() );
		if ( $season_title ) :
			?>
			<section class="section section--flow story story--reverse" aria-labelledby="season-title">
				<div class="container story__grid">
					<div class="story__text">
						<h2 class="story__title" id="season-title"><?php echo esc_html( $season_title ); ?></h2>
						<p class="story__lead"><?php echo esc_html( rivermere_field( 'ap_season_text', $id, '' ) ); ?></p>
						<p><?php echo rivermere_button( array( 'url' => $events_url, 'title' => rivermere_field( 'ap_season_cta', $id, __( 'See Upcoming Events', 'rivermere-market' ) ) ), 'secondary', array( 'icon' => 'calendar' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
					</div>
					<?php if ( ! empty( $season_image['ID'] ) ) : ?>
						<figure class="story__media">
							<?php echo wp_get_attachment_image( $season_image['ID'], 'rivermere-feature', false, array( 'class' => 'story__img', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(min-width: 1024px) 560px, 100vw' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						</figure>
					<?php endif; ?>
				</div>
			</section>
			<?php
		endif;

		get_template_part( 'template-parts/about/visit', null, array( 'post_id' => $id, 'events_url' => $events_url ) );
		get_template_part( 'template-parts/about/community-cta', null, array( 'post_id' => $id, 'vendors_url' => $vendors_url, 'market_url' => $market_url ) );

		rivermere_render_faq();
	endwhile;
	?>
</main>
<?php
get_footer();
