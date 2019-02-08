<?php

// Porto Blog
if ( function_exists( 'register_block_type' ) ) {
	register_block_type(
		'porto/porto-blog',
		array(
			'editor_script'   => 'porto_blocks',
			'render_callback' => 'porto_shortcode_blog',
		)
	);
}
add_shortcode( 'porto_blog', 'porto_shortcode_blog' );
add_action( 'vc_after_init', 'porto_load_blog_shortcode' );

function porto_shortcode_blog( $atts, $content = null ) {
	ob_start();
	if ( $template = porto_shortcode_template( 'porto_blog' ) ) {
		include $template;
	}
	return ob_get_clean();
}

function porto_load_blog_shortcode() {
	$animation_type     = porto_vc_animation_type();
	$animation_duration = porto_vc_animation_duration();
	$animation_delay    = porto_vc_animation_delay();
	$custom_class       = porto_vc_custom_class();
	$order_by_values    = porto_vc_woo_order_by();
	$order_way_values   = porto_vc_woo_order_way();

	vc_map(
		array(
			'name'     => 'Porto ' . __( 'Blog', 'porto-functionality' ),
			'base'     => 'porto_blog',
			'category' => __( 'Porto', 'porto-functionality' ),
			'icon'     => 'porto_vc_blog',
			'params'   => array(
				array(
					'type'        => 'textfield',
					'heading'     => __( 'Title', 'porto-functionality' ),
					'param_name'  => 'title',
					'admin_label' => true,
				),
				array(
					'type'        => 'dropdown',
					'heading'     => __( 'Blog Layout', 'porto-functionality' ),
					'param_name'  => 'post_layout',
					'std'         => 'timeline',
					'value'       => porto_sh_commons( 'blog_layout' ),
					'admin_label' => true,
				),
				array(
					'type'       => 'dropdown',
					'heading'    => __( 'Post Style', 'porto-functionality' ),
					'param_name' => 'post_style',
					'dependency' => array(
						'element' => 'post_layout',
						'value'   => array( 'grid', 'masonry', 'timeline' ),
					),
					'value'      => array(
						__( 'Default', 'porto-functionality' ) => 'default',
						__( 'Post Carousel Style', 'porto-functionality' ) => 'related',
						__( 'Hover Info', 'porto-functionality' ) => 'hover_info',
						__( 'No Margin & Hover Info', 'porto-functionality' ) => 'no_margin',
						__( 'With Borders', 'porto-functionality' ) => 'padding',
					),
				),
				array(
					'type'       => 'dropdown',
					'heading'    => __( 'Columns', 'porto-functionality' ),
					'param_name' => 'columns',
					'dependency' => array(
						'element' => 'post_layout',
						'value'   => array( 'grid', 'masonry' ),
					),
					'std'        => '3',
					'value'      => porto_sh_commons( 'blog_grid_columns' ),
				),
				array(
					'type'        => 'textfield',
					'heading'     => __( 'Category IDs', 'porto-functionality' ),
					'description' => __( 'comma separated list of category ids', 'porto-functionality' ),
					'param_name'  => 'cats',
					'admin_label' => true,
				),
				array(
					'type'        => 'textfield',
					'heading'     => __( 'Post IDs', 'porto-functionality' ),
					'description' => __( 'comma separated list of post ids', 'porto-functionality' ),
					'param_name'  => 'post_in',
				),
				array(
					'type'       => 'textfield',
					'heading'    => __( 'Posts Count', 'porto-functionality' ),
					'param_name' => 'number',
					'value'      => '8',
				),
				array(
					'type'        => 'dropdown',
					'heading'     => __( 'Order by', 'porto-functionality' ),
					'param_name'  => 'orderby',
					'value'       => $order_by_values,
					/* translators: %s: Wordpres codex page */
					'description' => sprintf( __( 'Select how to sort retrieved posts. More at %s.', 'porto-functionality' ), '<a href="http://codex.wordpress.org/Class_Reference/WP_Query#Order_.26_Orderby_Parameters" target="_blank">WordPress codex page</a>' ),
				),
				array(
					'type'        => 'dropdown',
					'heading'     => __( 'Order way', 'porto-functionality' ),
					'param_name'  => 'order',
					'value'       => $order_way_values,
					/* translators: %s: Wordpres codex page */
					'description' => sprintf( __( 'Designates the ascending or descending order. More at %s.', 'porto-functionality' ), '<a href="http://codex.wordpress.org/Class_Reference/WP_Query#Order_.26_Orderby_Parameters" target="_blank">WordPress codex page</a>' ),
				),
				array(
					'type'       => 'textfield',
					'heading'    => __( 'Excerpt Length', 'porto-functionality' ),
					'param_name' => 'excerpt_length',
				),
				array(
					'type'       => 'dropdown',
					'heading'    => __( 'Pagination Style', 'porto-functionality' ),
					'param_name' => 'view_more',
					'value'      => array(
						__( 'No Pagination', 'porto-functionality' ) => '',
						__( 'Show Pagination', 'porto-functionality' ) => 'show',
						__( 'Show Blog Page Link', 'porto-functionality' ) => 'link',
					),
				),
				array(
					'type'       => 'textfield',
					'heading'    => __( 'Extra class name for Archive Link', 'porto-functionality' ),
					'param_name' => 'view_more_class',
					'dependency' => array(
						'element' => 'view_more',
						'value'   => array( 'link' ),
					),
				),
				$custom_class,
				$animation_type,
				$animation_duration,
				$animation_delay,
			),
		)
	);

	if ( ! class_exists( 'WPBakeryShortCode_Porto_Blog' ) ) {
		class WPBakeryShortCode_Porto_Blog extends WPBakeryShortCode {
		}
	}
}
