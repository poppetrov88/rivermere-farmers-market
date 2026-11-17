<?php
/**
 * Template Name: Become a Vendor
 * Template Post Type: page
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="main-content" class="site-main site-main--vendor-apply">
	<?php
	while ( have_posts() ) :
		the_post();
		$id = get_the_ID();

		$hero_image = rivermere_field( 'vp_image', $id, array() );
		get_template_part(
			'template-parts/components/landing-hero',
			null,
			array(
				'eyebrow' => rivermere_field( 'vp_eyebrow', $id, __( 'Grow with our community', 'rivermere-market' ) ),
				'title'   => rivermere_field( 'vp_title', $id, sprintf( /* translators: %s: market name */ __( 'Become a Vendor at %s', 'rivermere-market' ), rivermere_market_name() ) ),
				'intro'   => rivermere_field( 'vp_description', $id, '' ),
				'image'   => $hero_image,
				'buttons' => array(
					array( 'label' => rivermere_field( 'vp_cta_apply', $id, __( 'Apply Now', 'rivermere-market' ) ), 'url' => '#apply', 'variant' => 'accent', 'icon' => 'arrow' ),
					array( 'label' => rivermere_field( 'vp_cta_requirements', $id, __( 'Learn About Requirements', 'rivermere-market' ) ), 'url' => '#requirements', 'variant' => 'outline', 'icon' => false ),
				),
			)
		);

		$benefits = array_map(
			static function ( $b ) {
				return array( 'icon' => $b['icon'] ?? 'leaf', 'title' => $b['title'] ?? '', 'text' => $b['text'] ?? '' );
			},
			(array) rivermere_field( 'vp_benefits', $id, array() )
		);
		get_template_part( 'template-parts/components/feature-cards', null, array( 'id' => 'benefits', 'title' => rivermere_field( 'vp_benefits_title', $id, __( 'Why Join Our Market?', 'rivermere-market' ) ), 'cards' => $benefits, 'columns' => 4 ) );

		get_template_part( 'template-parts/vendor/steps', null, array( 'post_id' => $id ) );
		get_template_part( 'template-parts/vendor/requirements', null, array( 'post_id' => $id ) );
		get_template_part( 'template-parts/vendor/application-form', null, array( 'post_id' => $id ) );

		rivermere_render_faq();

		// Final CTA.
		?>
		<section class="section section--flow section--cta" aria-labelledby="vendor-cta-title">
			<div class="container">
				<div class="cta-block">
					<h2 class="cta-block__title" id="vendor-cta-title"><?php echo esc_html( rivermere_field( 'vp_cta_title', $id, __( 'Questions before you apply?', 'rivermere-market' ) ) ); ?></h2>
					<p><?php echo esc_html( rivermere_field( 'vp_cta_text', $id, __( 'See who already sells at the market, or get in touch and we will point you in the right direction.', 'rivermere-market' ) ) ); ?></p>
					<div class="cta-block__actions">
						<?php echo rivermere_button( array( 'url' => get_post_type_archive_link( 'rm_vendor' ), 'title' => __( 'Meet Our Vendors', 'rivermere-market' ) ), 'accent' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php
						$contact_email = rivermere_option( 'contact_email', '' );
						if ( is_email( $contact_email ) ) {
							echo rivermere_button( array( 'url' => 'mailto:' . $contact_email, 'title' => __( 'Contact Us', 'rivermere-market' ) ), 'outline-light', array( 'icon' => 'mail' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						} else {
							$market = rivermere_find_page_id( 'page-market-info.php', 'market-info' );
							if ( $market ) {
								echo rivermere_button( array( 'url' => get_permalink( $market ), 'title' => __( 'Contact Us', 'rivermere-market' ) ), 'outline-light', array( 'icon' => false ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
						}
						?>
					</div>
				</div>
			</div>
		</section>
		<?php
	endwhile;
	?>
</main>
<?php
get_footer();
