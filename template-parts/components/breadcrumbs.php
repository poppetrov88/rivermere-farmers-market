<?php
/**
 * Breadcrumb bar. The only breadcrumb navigation on a page (rendered from header.php).
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$rm_list = rivermere_breadcrumbs_html();
if ( '' === $rm_list ) {
	return;
}
?>
<div class="breadcrumbs-bar">
	<div class="container">
		<nav class="breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'rivermere-market' ); ?>">
			<?php echo $rm_list; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built and escaped in rivermere_breadcrumb_list_html(). ?>
		</nav>
	</div>
</div>
