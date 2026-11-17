<?php
/**
 * Cookie consent: banner + preferences dialog (behaviour in src/js/consent.js).
 * Rendered hidden; JS reveals the banner only when no valid choice is stored.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

if ( ! rivermere_toggle( 'ui_cookie_banner', true ) ) {
	return;
}

$policy = (string) rivermere_option( 'cookie_policy_page', '' );
if ( '' === $policy ) {
	$page   = get_page_by_path( 'cookie-policy' );
	$policy = $page ? get_permalink( $page ) : '';
}

$categories = array(
	'necessary' => array(
		'label'    => __( 'Strictly necessary', 'rivermere-market' ),
		'desc'     => __( 'Remember your cookie choices and keep the site working. Always on.', 'rivermere-market' ),
		'checked'  => true,
		'disabled' => true,
	),
	'analytics' => array(
		'label'    => __( 'Analytics', 'rivermere-market' ),
		'desc'     => __( 'Help us understand which pages are useful so we can improve the market website. Off unless you allow it.', 'rivermere-market' ),
		'checked'  => false,
		'disabled' => false,
	),
	'marketing' => array(
		'label'    => __( 'Marketing', 'rivermere-market' ),
		'desc'     => __( 'Used to show relevant promotions elsewhere. This website does not currently use any marketing cookies.', 'rivermere-market' ),
		'checked'  => false,
		'disabled' => false,
	),
);
?>
<div class="cookie-banner" role="region" aria-labelledby="cookie-banner-title" data-cookie-banner hidden>
	<div class="cookie-banner__text">
		<h2 class="cookie-banner__title" id="cookie-banner-title"><?php esc_html_e( 'Your cookie choices', 'rivermere-market' ); ?></h2>
		<p>
			<?php esc_html_e( 'We use essential cookies to make this site work. With your permission we would also like to use analytics cookies to improve it.', 'rivermere-market' ); ?>
			<?php if ( $policy ) : ?>
				<a href="<?php echo esc_url( $policy ); ?>"><?php esc_html_e( 'Cookie Policy', 'rivermere-market' ); ?></a>
			<?php endif; ?>
		</p>
	</div>
	<div class="cookie-banner__actions">
		<button type="button" class="btn btn--secondary btn--sm" data-cookie-accept><span><?php esc_html_e( 'Accept all', 'rivermere-market' ); ?></span></button>
		<button type="button" class="btn btn--outline btn--sm" data-cookie-reject><span><?php esc_html_e( 'Reject optional', 'rivermere-market' ); ?></span></button>
		<button type="button" class="btn btn--outline btn--sm" data-cookie-open aria-haspopup="dialog"><span><?php esc_html_e( 'Cookie settings', 'rivermere-market' ); ?></span></button>
	</div>
</div>

<dialog class="cookie-dialog" id="cookie-dialog" aria-labelledby="cookie-dialog-title" data-cookie-dialog>
	<form class="cookie-dialog__form" method="dialog" data-cookie-form>
		<div class="cookie-dialog__head">
			<h2 id="cookie-dialog-title"><?php esc_html_e( 'Cookie settings', 'rivermere-market' ); ?></h2>
			<button type="button" class="icon-btn" data-cookie-close>
				<?php echo rivermere_icon( 'close' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<span class="screen-reader-text"><?php esc_html_e( 'Close without saving', 'rivermere-market' ); ?></span>
			</button>
		</div>
		<p class="cookie-dialog__intro"><?php esc_html_e( 'Choose which optional cookies you allow. You can change this at any time from the footer.', 'rivermere-market' ); ?></p>

		<fieldset class="cookie-dialog__fieldset">
			<legend class="screen-reader-text"><?php esc_html_e( 'Cookie categories', 'rivermere-market' ); ?></legend>
			<?php foreach ( $categories as $key => $cat ) : ?>
				<div class="cookie-option">
					<input class="check__input" type="checkbox" id="cookie-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="1" aria-describedby="cookie-<?php echo esc_attr( $key ); ?>-desc"<?php echo $cat['checked'] ? ' checked' : ''; ?><?php echo $cat['disabled'] ? ' disabled' : ''; ?>>
					<label class="check check--light" for="cookie-<?php echo esc_attr( $key ); ?>">
						<span class="check__box" aria-hidden="true"><?php echo rivermere_icon( 'check', 'check__tick' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<span class="check__text"><strong><?php echo esc_html( $cat['label'] ); ?></strong></span>
					</label>
					<p class="cookie-option__desc" id="cookie-<?php echo esc_attr( $key ); ?>-desc"><?php echo esc_html( $cat['desc'] ); ?></p>
				</div>
			<?php endforeach; ?>
		</fieldset>

		<div class="cookie-dialog__actions">
			<button type="submit" class="btn btn--secondary"><span><?php esc_html_e( 'Save preferences', 'rivermere-market' ); ?></span></button>
			<button type="button" class="btn btn--outline" data-cookie-accept><span><?php esc_html_e( 'Accept all', 'rivermere-market' ); ?></span></button>
		</div>
	</form>
</dialog>
