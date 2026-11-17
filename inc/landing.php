<?php
/**
 * Landing-page helpers: page URL lookups (no hard-coded URLs), form field renderer.
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

/**
 * Find the page that uses a template (cached), falling back to a slug.
 *
 * @param string $template Template file name.
 * @param string $slug     Fallback slug.
 * @return int Page ID or 0.
 */
function rivermere_find_page_id( $template, $slug ) {
	static $cache = array();
	if ( isset( $cache[ $template ] ) ) {
		return $cache[ $template ];
	}
	$ids = get_posts(
		array(
			'post_type'      => 'page',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $template, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);
	if ( ! $ids ) {
		$page = get_page_by_path( $slug );
		$ids  = $page ? array( $page->ID ) : array();
	}
	$cache[ $template ] = $ids ? (int) $ids[0] : 0;
	return $cache[ $template ];
}

/**
 * URL of the Become a Vendor page. Use this for EVERY vendor-application CTA.
 *
 * @param string $anchor Optional fragment (e.g. 'apply').
 * @return string
 */
function rivermere_vendor_page_url( $anchor = '' ) {
	$id  = rivermere_find_page_id( 'page-become-a-vendor.php', 'become-a-vendor' );
	$url = $id ? get_permalink( $id ) : home_url( '/become-a-vendor/' );
	return $anchor ? $url . '#' . ltrim( $anchor, '#' ) : $url;
}

/**
 * URL of the About Us page.
 *
 * @return string
 */
function rivermere_about_page_url() {
	$id = rivermere_find_page_id( 'page-about.php', 'about-us' );
	return $id ? get_permalink( $id ) : home_url( '/about-us/' );
}

/**
 * Render one form field (label, control, hint, inline error slot).
 *
 * @param array $a id,name,label,type(text|email|tel|url|textarea|select),required,hint,autocomplete,maxlength,rows,options,inputmode,half.
 */
function rivermere_form_field( $a ) {
	$a   = wp_parse_args(
		$a,
		array(
			'type'         => 'text',
			'required'     => false,
			'hint'         => '',
			'autocomplete' => '',
			'maxlength'    => '',
			'rows'         => 4,
			'options'      => array(),
			'inputmode'    => '',
			'half'         => false,
		)
	);
	$id  = 'va-' . $a['id'];
	$des = trim( ( $a['hint'] ? $id . '-hint ' : '' ) . $id . '-error' );
	?>
	<div class="field<?php echo $a['half'] ? ' field--half' : ''; ?>" data-field="<?php echo esc_attr( $a['name'] ); ?>">
		<label class="field__label" for="<?php echo esc_attr( $id ); ?>">
			<?php echo esc_html( $a['label'] ); ?>
			<?php if ( $a['required'] ) : ?>
				<span class="field__req" aria-hidden="true">*</span><span class="screen-reader-text"> <?php esc_html_e( '(required)', 'rivermere-market' ); ?></span>
			<?php else : ?>
				<span class="field__opt"><?php esc_html_e( '(optional)', 'rivermere-market' ); ?></span>
			<?php endif; ?>
		</label>
		<?php if ( 'textarea' === $a['type'] ) : ?>
			<textarea class="field__control" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $a['name'] ); ?>" rows="<?php echo (int) $a['rows']; ?>"<?php echo $a['maxlength'] ? ' maxlength="' . (int) $a['maxlength'] . '"' : ''; ?><?php echo $a['required'] ? ' required' : ''; ?> aria-describedby="<?php echo esc_attr( $des ); ?>"></textarea>
		<?php elseif ( 'select' === $a['type'] ) : ?>
			<select class="field__control" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $a['name'] ); ?>"<?php echo $a['required'] ? ' required' : ''; ?> aria-describedby="<?php echo esc_attr( $des ); ?>">
				<option value=""><?php esc_html_e( 'Select…', 'rivermere-market' ); ?></option>
				<?php foreach ( $a['options'] as $option ) : ?>
					<option value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $option ); ?></option>
				<?php endforeach; ?>
			</select>
		<?php else : ?>
			<input class="field__control" type="<?php echo esc_attr( $a['type'] ); ?>" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $a['name'] ); ?>"<?php echo $a['autocomplete'] ? ' autocomplete="' . esc_attr( $a['autocomplete'] ) . '"' : ''; ?><?php echo $a['inputmode'] ? ' inputmode="' . esc_attr( $a['inputmode'] ) . '"' : ''; ?><?php echo $a['maxlength'] ? ' maxlength="' . (int) $a['maxlength'] . '"' : ''; ?><?php echo $a['required'] ? ' required' : ''; ?> aria-describedby="<?php echo esc_attr( $des ); ?>">
		<?php endif; ?>
		<?php if ( $a['hint'] ) : ?>
			<p class="field__hint" id="<?php echo esc_attr( $id ); ?>-hint"><?php echo esc_html( $a['hint'] ); ?></p>
		<?php endif; ?>
		<p class="field__error" id="<?php echo esc_attr( $id ); ?>-error" hidden></p>
	</div>
	<?php
}
