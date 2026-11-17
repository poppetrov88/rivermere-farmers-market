<?php
/**
 * Language selector — UI only. English stays the real site language; other choices only show a
 * "Coming soon" message. Hook `rivermere_languages` / replace this part when real multilingual
 * support (e.g. per-language URLs) is added.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$languages = apply_filters(
	'rivermere_languages',
	array(
		'en' => array( 'EN', __( 'English', 'rivermere-market' ), true ),
		'fr' => array( 'FR', __( 'French', 'rivermere-market' ), false ),
		'de' => array( 'DE', __( 'German', 'rivermere-market' ), false ),
		'el' => array( 'EL', __( 'Greek', 'rivermere-market' ), false ),
		'sr' => array( 'SR', __( 'Serbian', 'rivermere-market' ), false ),
		'ru' => array( 'RU', __( 'Russian', 'rivermere-market' ), false ),
	)
);
$uid = 'lang-' . wp_unique_id();
?>
<div class="lang" data-lang>
	<button type="button" class="lang__toggle" aria-expanded="false" aria-controls="<?php echo esc_attr( $uid ); ?>" data-lang-toggle>
		<?php echo rivermere_icon( 'globe', 'lang__globe' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<span class="screen-reader-text"><?php esc_html_e( 'Language:', 'rivermere-market' ); ?> </span>
		<span class="lang__current" data-lang-current>EN</span>
		<?php echo rivermere_icon( 'chevron', 'lang__chevron' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</button>
	<ul class="lang__menu" id="<?php echo esc_attr( $uid ); ?>" hidden>
		<?php foreach ( $languages as $code => $lang ) : ?>
			<li>
				<button type="button" class="lang__option" data-lang-option="<?php echo esc_attr( $code ); ?>" data-lang-ready="<?php echo $lang[2] ? '1' : '0'; ?>" lang="<?php echo esc_attr( $code ); ?>"<?php echo $lang[2] ? ' aria-current="true"' : ''; ?>>
					<span class="lang__name"><?php echo esc_html( $lang[1] ); ?></span>
					<span class="lang__code" aria-hidden="true"><?php echo esc_html( $lang[0] ); ?></span>
				</button>
			</li>
		<?php endforeach; ?>
	</ul>
</div>
