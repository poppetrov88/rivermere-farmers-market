<?php
/**
 * Decorative leaves (SVG).
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$class = isset( $args['class'] ) ? $args['class'] : 'leaves';
?>
<svg class="<?php echo esc_attr( $class ); ?>" viewBox="0 0 160 200" aria-hidden="true" focusable="false">
	<path d="M10 190C10 100 50 30 150 10c-4 90-40 150-120 178Z" fill="currentColor" opacity=".9"/>
	<path d="M20 185C50 130 90 80 140 22" fill="none" stroke="rgba(255,255,255,.28)" stroke-width="2"/>
	<path d="M60 195c0-50 25-85 80-100-2 50-25 85-70 105Z" fill="currentColor" opacity=".7"/>
</svg>
