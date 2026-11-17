<?php
/**
 * Generic content card (posts, pages, events, vendors in search).
 *
 * @package Rivermere_Market
 */

defined( 'ABSPATH' ) || exit;

$type_obj = get_post_type_object( get_post_type() );
?>
<article <?php post_class( 'card' ); ?>>
	<a class="card__link" href="<?php the_permalink(); ?>">
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="card__media"><?php the_post_thumbnail( 'rivermere-card', array( 'loading' => 'lazy', 'alt' => '' ) ); ?></div>
		<?php endif; ?>
		<div class="card__body">
			<?php if ( $type_obj && 'post' !== $type_obj->name ) : ?>
				<span class="badge"><?php echo esc_html( $type_obj->labels->singular_name ); ?></span>
			<?php endif; ?>
			<h2 class="card__title"><?php the_title(); ?></h2>
			<?php if ( 'rm_event' === get_post_type() && get_post_meta( get_the_ID(), 'event_start', true ) ) : ?>
				<?php $rm_times = rivermere_event_times( get_the_ID() ); ?>
				<?php if ( $rm_times['start'] ) : ?>
					<p class="card__meta"><time datetime="<?php echo esc_attr( $rm_times['start']->format( 'c' ) ); ?>"><?php echo esc_html( wp_date( get_option( 'date_format' ) . ' · ' . get_option( 'time_format' ), $rm_times['start']->getTimestamp() ) ); ?></time></p>
				<?php endif; ?>
			<?php endif; ?>
			<p class="card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24 ) ); ?></p>
		</div>
	</a>
</article>
