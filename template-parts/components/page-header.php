<?php
/**
 * Page header (H1 + breadcrumbs) for inner pages.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$title   = isset( $args['title'] ) ? $args['title'] : get_the_title();
$eyebrow = isset( $args['eyebrow'] ) ? $args['eyebrow'] : '';
$text    = isset( $args['text'] ) ? $args['text'] : '';
?>
<header class="page-header">
	<div class="container">
		<?php if ( $eyebrow ) : ?>
			<p class="eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
		<?php endif; ?>
		<h1 class="page-header__title"><?php echo esc_html( $title ); ?></h1>
		<?php if ( $text ) : ?>
			<div class="page-header__text"><?php echo wp_kses_post( $text ); ?></div>
		<?php endif; ?>
	</div>
</header>
