<?php
/**
 * Single vendor details block.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$id      = get_the_ID();
$tagline = rivermere_field( 'vendor_tagline', $id, '' );
$website = rivermere_field( 'vendor_website', $id, '' );
$email   = rivermere_field( 'vendor_email', $id, '' );
$phone   = rivermere_field( 'vendor_phone', $id, '' );
$stall   = rivermere_field( 'vendor_stall', $id, '' );
$days    = (array) rivermere_field( 'vendor_days', $id, array() );
$cats    = get_the_terms( $id, 'rm_product_category' );
?>
<?php if ( has_post_thumbnail() ) : ?>
	<div class="entry__thumb"><?php the_post_thumbnail( 'rivermere-feature', array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ); ?></div>
<?php endif; ?>
<?php if ( $tagline ) : ?>
	<p class="lead"><?php echo esc_html( $tagline ); ?></p>
<?php endif; ?>
<?php if ( $cats && ! is_wp_error( $cats ) ) : ?>
	<ul class="tag-list">
		<?php foreach ( $cats as $cat ) : ?>
			<li><a class="badge" href="<?php echo esc_url( get_term_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a></li>
		<?php endforeach; ?>
	</ul>
<?php endif; ?>
<dl class="meta-list">
	<?php if ( $stall ) : ?>
		<div><dt><?php esc_html_e( 'Stall', 'rivermere-market' ); ?></dt><dd><?php echo esc_html( $stall ); ?></dd></div>
	<?php endif; ?>
	<?php if ( $days ) : ?>
		<div><dt><?php esc_html_e( 'Market days', 'rivermere-market' ); ?></dt><dd><?php echo esc_html( implode( ', ', $days ) ); ?></dd></div>
	<?php endif; ?>
	<?php if ( $website ) : ?>
		<div><dt><?php esc_html_e( 'Website', 'rivermere-market' ); ?></dt><dd><a href="<?php echo esc_url( $website ); ?>" rel="noopener"><?php echo esc_html( wp_parse_url( $website, PHP_URL_HOST ) ); ?></a></dd></div>
	<?php endif; ?>
	<?php if ( $email ) : ?>
		<div><dt><?php esc_html_e( 'Email', 'rivermere-market' ); ?></dt><dd><a href="mailto:<?php echo esc_attr( antispambot( $email ) ); ?>"><?php echo esc_html( antispambot( $email ) ); ?></a></dd></div>
	<?php endif; ?>
	<?php if ( $phone ) : ?>
		<div><dt><?php esc_html_e( 'Phone', 'rivermere-market' ); ?></dt><dd><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a></dd></div>
	<?php endif; ?>
</dl>
