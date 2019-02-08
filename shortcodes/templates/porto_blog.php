<?php

global $porto_settings;

$output = $title = $post_layout = $post_style = $columns = $cat = $cats = $post_in = $number = $view_more = $view_more_class = $animation_type = $animation_duration = $animation_delay = $el_class = '';
extract(
	shortcode_atts(
		array(
			'title'              => '',
			'post_layout'        => 'timeline',
			'post_style'         => 'default',
			'columns'            => '3',
			'cats'               => '',
			'cat'                => '',
			'post_in'            => '',
			'number'             => 8,
			'orderby'            => '',
			'order'              => '',
			'view_more'          => '',
			'view_more_class'    => '',
			'excerpt_length'     => '',
			'animation_type'     => '',
			'animation_duration' => 1000,
			'animation_delay'    => 0,
			'el_class'           => '',
			'className'          => '',
		),
		$atts
	)
);

if ( ! $excerpt_length && defined( 'PORTO_DEMO' ) && PORTO_DEMO && ( 'grid' == $post_layout || 'masonry' == $post_layout || 'timeline' == $post_layout ) ) {
	$excerpt_length = 20;
}

$args = array(
	'post_type'      => 'post',
	'posts_per_page' => $number,
);

if ( ! $cats ) {
	$cats = $cat;
}

if ( $cats ) {
	$args['cat'] = $cats;
}

if ( $post_in ) {
	$args['post__in'] = explode( ',', $post_in );
	$args['orderby']  = 'post__in';
}

if ( 'show' === $view_more ) {
	if ( is_front_page() ) {
		$paged = get_query_var( 'page' );
	} else {
		$paged = get_query_var( 'paged' );
	}
	if ( $paged ) {
		$args['paged'] = $paged;
	}
}

if ( $orderby ) {
	$args['orderby'] = $orderby;
}
if ( $order ) {
	$args['order'] = $order;
}

$posts = new WP_Query( $args );

if ( $posts->have_posts() ) {
	$el_class = porto_shortcode_extract_class( $el_class );

	if ( $className ) {
		if ( $el_class ) {
			$el_class .= ' ' . $className;
		} else {
			$el_class = $className;
		}
	}

	$output = '<div class="porto-blog wpb_content_element ' . esc_attr( $el_class ) . '"';
	if ( $animation_type ) {
		$output .= ' data-appear-animation="' . esc_attr( $animation_type ) . '"';
		if ( $animation_delay ) {
			$output .= ' data-appear-animation-delay="' . esc_attr( $animation_delay ) . '"';
		}
		if ( $animation_duration && 1000 != $animation_duration ) {
			$output .= ' data-appear-animation-duration="' . esc_attr( $animation_duration ) . '"';
		}
	}
	$output .= '>';

	$output .= porto_shortcode_widget_title(
		array(
			'title'      => $title,
			'extraclass' => '',
		)
	);

	global $porto_blog_columns;

	$porto_blog_columns = $columns;

	ob_start();

	if ( 'timeline' == $post_layout ) {
		global $prev_post_year, $prev_post_month, $first_timeline_loop, $post_count, $porto_post_style;

		$prev_post_year      = null;
		$prev_post_month     = null;
		$first_timeline_loop = false;
		$post_count          = 1;
		$porto_post_style    = $post_style;
		?>

		<div class="blog-posts posts-<?php echo esc_attr( $post_layout ); ?><?php echo ! empty( $post_style ) ? ' blog-posts-' . esc_attr( $post_style ) : ''; ?>">
			<section class="timeline">
				<div class="timeline-body">

		<?php
	} elseif ( 'grid' == $post_layout || 'masonry' == $post_layout ) {
		global $porto_post_style;

		$porto_post_style = $post_style;
		?>

		<div class="blog-posts posts-<?php echo esc_attr( $post_layout ); ?><?php echo ! empty( $post_style ) ? ' blog-posts-' . esc_attr( $post_style ) : ''; ?>">
			<div class="posts-container row">

	<?php } else { ?>

		<div class="blog-posts posts-<?php echo esc_attr( $post_layout ); ?>">

	<?php } ?>

	<?php
	if ( $excerpt_length ) {
		$global_excerpt_length                 = $porto_settings['blog-excerpt-length'];
		$porto_settings['blog-excerpt-length'] = $excerpt_length;
	}
	while ( $posts->have_posts() ) {
		$posts->the_post();
		get_template_part( 'content', 'blog-' . $post_layout );
	}
	if ( $excerpt_length ) {
		$porto_settings['blog-excerpt-length'] = $global_excerpt_length;
	}
	?>

	<?php if ( 'timeline' == $post_layout ) { ?>

				</div>
			</section>
		</div>

	<?php } elseif ( 'grid' == $post_layout || 'masonry' == $post_layout ) { ?>

			</div>
		</div>

	<?php } else { ?>

		</div>

	<?php } ?>

	<?php if ( 'show' === $view_more ) : ?>
		<?php porto_pagination( $posts->max_num_pages, ( 'load_more' === $view_more ), $posts ); ?>
	<?php elseif ( get_option( 'show_on_front' ) == 'page' && $view_more ) : ?>
		<div class="<?php echo 'timeline' == $post_layout ? 'm-t-n-xxl' : 'push-top'; ?> m-b-xxl text-center">
			<a class="btn btn-primary<?php echo ! empty( $view_more_class ) ? ' ' . str_replace( '.', '', $view_more_class ) : ''; ?>" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>"><?php esc_html_e( 'View More', 'porto-functionality' ); ?></a>
		</div>
	<?php endif; ?>

	<?php
		$output .= ob_get_clean();

		$porto_blog_columns = $porto_post_style = '';

		$output .= '</div>';

		echo porto_filter_output( $output );
}

wp_reset_postdata();
