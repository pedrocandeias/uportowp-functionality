<?php

function porto_shortcode_template( $name = false ) {
	if ( ! $name ) {
		return false;
	}

	if ( $overridden_template = locate_template( 'vc_templates/' . $name . '.php' ) ) {
		return $overridden_template;
	} else {
		// If neither the child nor parent theme have overridden the template,
		// we load the template from the 'templates' sub-directory of the directory this file is in
		return PORTO_SHORTCODES_TEMPLATES . $name . '.php';
	}
}

function porto_shortcode_woo_template( $name = false ) {
	if ( ! $name ) {
		return false;
	}

	if ( $overridden_template = locate_template( 'vc_templates/' . $name . '.php' ) ) {
		return $overridden_template;
	} else {
		// If neither the child nor parent theme have overridden the template,
		// we load the template from the 'templates' sub-directory of the directory this file is in
		return PORTO_SHORTCODES_WOO_TEMPLATES . $name . '.php';
	}
}

function porto_shortcode_extract_class( $el_class ) {
	$output = '';
	if ( $el_class ) {
		$output = ' ' . str_replace( '.', '', $el_class );
	}

	return $output;
}

function porto_shortcode_end_block_comment( $string ) {
	return WP_DEBUG ? '<!-- END ' . $string . ' -->' : '';
}

function porto_shortcode_js_remove_wpautop( $content, $autop = false ) {

	if ( $autop ) {
		$content = wpautop( preg_replace( '/<\/?p\>/', "\n", $content ) . "\n" );
	}

	return do_shortcode( shortcode_unautop( $content ) );
}

function porto_shortcode_image_resize( $attach_id = null, $img_url = null, $width, $height, $crop = false ) {
	// this is an attachment, so we have the ID
	$image_src = array();
	if ( $attach_id ) {
		$image_src        = wp_get_attachment_image_src( $attach_id, 'full' );
		$actual_file_path = get_attached_file( $attach_id );
		// this is not an attachment, let's use the image url
	} elseif ( $img_url ) {
		$file_path        = parse_url( $img_url );
		$actual_file_path = $_SERVER['DOCUMENT_ROOT'] . $file_path['path'];
		$actual_file_path = ltrim( $file_path['path'], '/' );
		$actual_file_path = rtrim( ABSPATH, '/' ) . $file_path['path'];
		$orig_size        = getimagesize( $actual_file_path );
		$image_src[0]     = $img_url;
		$image_src[1]     = $orig_size[0];
		$image_src[2]     = $orig_size[1];
	}
	if ( ! empty( $actual_file_path ) ) {
		$file_info = pathinfo( $actual_file_path );
		$extension = '.' . $file_info['extension'];

		// the image path without the extension
		$no_ext_path = $file_info['dirname'] . '/' . $file_info['filename'];

		$cropped_img_path = $no_ext_path . '-' . $width . 'x' . $height . $extension;

		// checking if the file size is larger than the target size
		// if it is smaller or the same size, stop right here and return
		if ( $image_src[1] > $width || $image_src[2] > $height ) {

			// the file is larger, check if the resized version already exists (for $crop = true but will also work for $crop = false if the sizes match)
			if ( file_exists( $cropped_img_path ) ) {
				$cropped_img_url = str_replace( basename( $image_src[0] ), basename( $cropped_img_path ), $image_src[0] );
				$vt_image        = array(
					'url'    => $cropped_img_url,
					'width'  => $width,
					'height' => $height,
				);

				return $vt_image;
			}

			// $crop = false
			if ( ! $crop ) {
				// calculate the size proportionaly
				$proportional_size = wp_constrain_dimensions( $image_src[1], $image_src[2], $width, $height );
				$resized_img_path  = $no_ext_path . '-' . $proportional_size[0] . 'x' . $proportional_size[1] . $extension;

				// checking if the file already exists
				if ( file_exists( $resized_img_path ) ) {
					$resized_img_url = str_replace( basename( $image_src[0] ), basename( $resized_img_path ), $image_src[0] );

					$vt_image = array(
						'url'    => $resized_img_url,
						'width'  => $proportional_size[0],
						'height' => $proportional_size[1],
					);

					return $vt_image;
				}
			}

			// no cache files - let's finally resize it
			$img_editor = wp_get_image_editor( $actual_file_path );

			if ( is_wp_error( $img_editor ) || is_wp_error( $img_editor->resize( $width, $height, $crop ) ) ) {
				return array(
					'url'    => '',
					'width'  => '',
					'height' => '',
				);
			}

			$new_img_path = $img_editor->generate_filename();

			if ( is_wp_error( $img_editor->save( $new_img_path ) ) ) {
				return array(
					'url'    => '',
					'width'  => '',
					'height' => '',
				);
			}
			if ( ! is_string( $new_img_path ) ) {
				return array(
					'url'    => '',
					'width'  => '',
					'height' => '',
				);
			}

			$new_img_size = getimagesize( $new_img_path );
			$new_img      = str_replace( basename( $image_src[0] ), basename( $new_img_path ), $image_src[0] );

			// resized output
			$vt_image = array(
				'url'    => $new_img,
				'width'  => $new_img_size[0],
				'height' => $new_img_size[1],
			);

			return $vt_image;
		}

		// default output - without resizing
		$vt_image = array(
			'url'    => $image_src[0],
			'width'  => $image_src[1],
			'height' => $image_src[2],
		);

		return $vt_image;
	}
	return false;
}

function porto_shortcode_get_image_by_size(
	$params = array(
		'post_id'    => null,
		'attach_id'  => null,
		'thumb_size' => 'thumbnail',
		'class'      => '',
	)
) {
	//array( 'post_id' => $post_id, 'thumb_size' => $grid_thumb_size )
	if ( ( ! isset( $params['attach_id'] ) || null == $params['attach_id'] ) && ( ! isset( $params['post_id'] ) || null == $params['post_id'] ) ) {
		return false;
	}
	$post_id = isset( $params['post_id'] ) ? $params['post_id'] : 0;

	if ( $post_id ) {
		$attach_id = get_post_thumbnail_id( $post_id );
	} else {
		$attach_id = $params['attach_id'];
	}

	$thumb_size  = $params['thumb_size'];
	$thumb_class = ( isset( $params['class'] ) && $params['class'] ) ? $params['class'] . ' ' : '';

	global $_wp_additional_image_sizes;
	$thumbnail = '';

	if ( is_string( $thumb_size ) && ( ( ! empty( $_wp_additional_image_sizes[ $thumb_size ] ) && is_array( $_wp_additional_image_sizes[ $thumb_size ] ) ) || in_array(
		$thumb_size,
		array(
			'thumbnail',
			'thumb',
			'medium',
			'large',
			'full',
		)
	) )
	) {
		$thumbnail = wp_get_attachment_image( $attach_id, $thumb_size, false, array( 'class' => $thumb_class . 'attachment-' . $thumb_size ) );
	} elseif ( $attach_id ) {
		if ( is_string( $thumb_size ) ) {
			preg_match_all( '/\d+/', $thumb_size, $thumb_matches );
			if ( isset( $thumb_matches[0] ) ) {
				$thumb_size = array();
				if ( count( $thumb_matches[0] ) > 1 ) {
					$thumb_size[] = $thumb_matches[0][0]; // width
					$thumb_size[] = $thumb_matches[0][1]; // height
				} elseif ( count( $thumb_matches[0] ) > 0 && count( $thumb_matches[0] ) < 2 ) {
					$thumb_size[] = $thumb_matches[0][0]; // width
					$thumb_size[] = $thumb_matches[0][0]; // height
				} else {
					$thumb_size = false;
				}
			}
		}
		if ( is_array( $thumb_size ) ) {
			// Resize image to custom size
			$p_img      = porto_shortcode_image_resize( $attach_id, null, $thumb_size[0], $thumb_size[1], true );
			$alt        = trim( strip_tags( get_post_meta( $attach_id, '_wp_attachment_image_alt', true ) ) );
			$attachment = get_post( $attach_id );
			if ( ! empty( $attachment ) ) {
				$title = trim( strip_tags( $attachment->post_title ) );

				if ( empty( $alt ) ) {
					$alt = trim( strip_tags( $attachment->post_excerpt ) ); // If not, Use the Caption
				}
				if ( empty( $alt ) ) {
					$alt = $title;
				} // Finally, use the title
				if ( $p_img ) {
					$img_class = '';
					//if ( $grid_layout == 'thumbnail' ) $img_class = ' no_bottom_margin'; class="'.$img_class.'"
					$thumbnail = '<img class="' . esc_attr( $thumb_class ) . '" src="' . esc_attr( $p_img['url'] ) . '" width="' . esc_attr( $p_img['width'] ) . '" height="' . esc_attr( $p_img['height'] ) . '" alt="' . esc_attr( $alt ) . '" title="' . esc_attr( $title ) . '" />';
				}
			}
		}
	}

	$p_img_large = wp_get_attachment_image_src( $attach_id, 'large' );

	return apply_filters(
		'vc_wpb_getimagesize',
		array(
			'thumbnail'   => $thumbnail,
			'p_img_large' => $p_img_large,
		),
		$attach_id,
		$params
	);
}

function porto_vc_animation_type() {
	return array(
		'type'       => 'porto_animation_type',
		'heading'    => __( 'Animation Type', 'porto-functionality' ),
		'param_name' => 'animation_type',
		'group'      => __( 'Animation', 'porto-functionality' ),
	);
}

function porto_vc_animation_duration() {
	return array(
		'type'        => 'textfield',
		'heading'     => __( 'Animation Duration', 'porto-functionality' ),
		'param_name'  => 'animation_duration',
		'description' => __( 'numerical value (unit: milliseconds)', 'porto-functionality' ),
		'value'       => '1000',
		'group'       => __( 'Animation', 'porto-functionality' ),
	);
}

function porto_vc_animation_delay() {
	return array(
		'type'        => 'textfield',
		'heading'     => __( 'Animation Delay', 'porto-functionality' ),
		'param_name'  => 'animation_delay',
		'description' => __( 'numerical value (unit: milliseconds)', 'porto-functionality' ),
		'value'       => '0',
		'group'       => __( 'Animation', 'porto-functionality' ),
	);
}

function porto_vc_custom_class() {
	return array(
		'type'        => 'textfield',
		'heading'     => __( 'Extra class name', 'porto-functionality' ),
		'param_name'  => 'el_class',
		'description' => __( 'If you wish to style particular content element differently, then use this field to add a class name and then refer to it in your css file.', 'porto-functionality' ),
	);
}

if ( ! function_exists( 'porto_sh_commons' ) ) {
	function porto_sh_commons( $asset = '' ) {
		switch ( $asset ) {
			case 'toggle_type':
				return Porto_ShSharedLibrary::getToggleType();
			case 'toggle_size':
				return Porto_ShSharedLibrary::getToggleSize();
			case 'align':
				return Porto_ShSharedLibrary::getTextAlign();
			case 'blog_layout':
				return Porto_ShSharedLibrary::getBlogLayout();
			case 'blog_grid_columns':
				return Porto_ShSharedLibrary::getBlogGridColumns();
			case 'portfolio_layout':
				return Porto_ShSharedLibrary::getPortfolioLayout();
			case 'portfolio_grid_columns':
				return Porto_ShSharedLibrary::getPortfolioGridColumns();
			case 'portfolio_grid_view':
				return Porto_ShSharedLibrary::getPortfolioGridView();
			case 'member_columns':
				return Porto_ShSharedLibrary::getMemberColumns();
			case 'member_view':
				return Porto_ShSharedLibrary::getMemberView();
			case 'custom_zoom':
				return Porto_ShSharedLibrary::getCustomZoom();
			case 'products_view_mode':
				return Porto_ShSharedLibrary::getProductsViewMode();
			case 'products_columns':
				return Porto_ShSharedLibrary::getProductsColumns();
			case 'products_column_width':
				return Porto_ShSharedLibrary::getProductsColumnWidth();
			case 'products_addlinks_pos':
				return Porto_ShSharedLibrary::getProductsAddlinksPos();
			case 'product_view_mode':
				return Porto_ShSharedLibrary::getProductViewMode();
			case 'content_boxes_bg_type':
				return Porto_ShSharedLibrary::getContentBoxesBgType();
			case 'content_boxes_style':
				return Porto_ShSharedLibrary::getContentBoxesStyle();
			case 'content_box_effect':
				return Porto_ShSharedLibrary::getContentBoxEffect();
			case 'colors':
				return Porto_ShSharedLibrary::getColors();
			case 'testimonial_styles':
				return Porto_ShSharedLibrary::getTestimonialStyles();
			case 'contextual':
				return Porto_ShSharedLibrary::getContextual();
			case 'position':
				return Porto_ShSharedLibrary::getPosition();
			case 'size':
				return Porto_ShSharedLibrary::getSize();
			case 'trigger':
				return Porto_ShSharedLibrary::getTrigger();
			case 'bootstrap_columns':
				return Porto_ShSharedLibrary::getBootstrapColumns();
			case 'price_boxes_style':
				return Porto_ShSharedLibrary::getPriceBoxesStyle();
			case 'price_boxes_size':
				return Porto_ShSharedLibrary::getPriceBoxesSize();
			case 'sort_style':
				return Porto_ShSharedLibrary::getSortStyle();
			case 'sort_by':
				return Porto_ShSharedLibrary::getSortBy();
			case 'grid_columns':
				return Porto_ShSharedLibrary::getGridColumns();
			case 'preview_time':
				return Porto_ShSharedLibrary::getPreviewTime();
			case 'preview_position':
				return Porto_ShSharedLibrary::getPreviewPosition();
			case 'popup_action':
				return Porto_ShSharedLibrary::getPopupAction();
			case 'feature_box_style':
				return Porto_ShSharedLibrary::getFeatureBoxStyle();
			case 'feature_box_dir':
				return Porto_ShSharedLibrary::getFeatureBoxDir();
			case 'section_skin':
				return Porto_ShSharedLibrary::getSectionSkin();
			case 'section_color_scale':
				return Porto_ShSharedLibrary::getSectionColorScale();
			case 'section_text_color':
				return Porto_ShSharedLibrary::getSectionTextColor();
			case 'separator_icon_style':
				return Porto_ShSharedLibrary::getSeparatorIconStyle();
			case 'separator_icon_size':
				return Porto_ShSharedLibrary::getSeparatorIconSize();
			case 'separator_icon_pos':
				return Porto_ShSharedLibrary::getSeparatorIconPosition();
			case 'carousel_nav_types':
				return Porto_ShSharedLibrary::getCarouselNavTypes();
			default:
				return array();
		}
	}
}

function porto_vc_woo_order_by() {
	return array(
		'',
		__( 'Date', 'js_composer' )          => 'date',
		__( 'ID', 'js_composer' )            => 'ID',
		__( 'Author', 'js_composer' )        => 'author',
		__( 'Title', 'js_composer' )         => 'title',
		__( 'Modified', 'js_composer' )      => 'modified',
		__( 'Random', 'js_composer' )        => 'rand',
		__( 'Comment count', 'js_composer' ) => 'comment_count',
		__( 'Menu order', 'js_composer' )    => 'menu_order',
	);
}

function porto_vc_woo_order_way() {
	return array(
		'',
		__( 'Descending', 'js_composer' ) => 'DESC',
		__( 'Ascending', 'js_composer' )  => 'ASC',
	);
}

if ( ! class_exists( 'Porto_ShSharedLibrary' ) ) {
	class Porto_ShSharedLibrary {

		public static function getTextAlign() {
			return array(
				__( 'None', 'porto-functionality' )    => '',
				__( 'Left', 'porto-functionality' )    => 'left',
				__( 'Right', 'porto-functionality' )   => 'right',
				__( 'Center', 'porto-functionality' )  => 'center',
				__( 'Justify', 'porto-functionality' ) => 'justify',
			);
		}

		public static function getToggleType() {
			return array(
				__( 'Default', 'porto-functionality' ) => '',
				__( 'Simple', 'porto-functionality' )  => 'toggle-simple',
			);
		}

		public static function getToggleSize() {
			return array(
				__( 'Default', 'porto-functionality' ) => '',
				__( 'Small', 'porto-functionality' )   => 'toggle-sm',
				__( 'Large', 'porto-functionality' )   => 'toggle-lg',
			);
		}

		public static function getBlogLayout() {
			return array(
				__( 'Full', 'porto-functionality' )       => 'full',
				__( 'Large', 'porto-functionality' )      => 'large',
				__( 'Large Alt', 'porto-functionality' )  => 'large-alt',
				__( 'Medium', 'porto-functionality' )     => 'medium',
				__( 'Medium Alt', 'porto-functionality' ) => 'medium-alt',
				__( 'Grid', 'porto-functionality' )       => 'grid',
				__( 'Masonry', 'porto-functionality' )    => 'masonry',
				__( 'Timeline', 'porto-functionality' )   => 'timeline',
			);
		}

		public static function getBlogGridColumns() {
			return array(
				__( '1', 'porto-functionality' ) => '1',
				__( '2', 'porto-functionality' ) => '2',
				__( '3', 'porto-functionality' ) => '3',
				__( '4', 'porto-functionality' ) => '4',
				__( '5', 'porto-functionality' ) => '5',
				__( '6', 'porto-functionality' ) => '6',
			);
		}

		public static function getPortfolioLayout() {
			return array(
				__( 'Grid', 'porto-functionality' )     => 'grid',
				__( 'Masonry', 'porto-functionality' )  => 'masonry',
				__( 'Timeline', 'porto-functionality' ) => 'timeline',
				__( 'Medium', 'porto-functionality' )   => 'medium',
				__( 'Large', 'porto-functionality' )    => 'large',
				__( 'Full', 'porto-functionality' )     => 'full',
			);
		}

		public static function getPortfolioGridColumns() {
			return array(
				__( '1', 'porto-functionality' ) => '1',
				__( '2', 'porto-functionality' ) => '2',
				__( '3', 'porto-functionality' ) => '3',
				__( '4', 'porto-functionality' ) => '4',
				__( '5', 'porto-functionality' ) => '5',
				__( '6', 'porto-functionality' ) => '6',
			);
		}

		public static function getPortfolioGridView() {
			return array(
				__( 'Standard', 'porto-functionality' )     => 'classic',
				__( 'Default', 'porto-functionality' )      => 'default',
				__( 'No Margin', 'porto-functionality' )    => 'full',
				__( 'Out of Image', 'porto-functionality' ) => 'outimage',
			);
		}

		public static function getMemberView() {
			return array(
				__( 'Standard', 'porto-functionality' )       => 'classic',
				__( 'Text On Image', 'porto-functionality' )  => 'onimage',
				__( 'Text Out Image', 'porto-functionality' ) => 'outimage',
				__( 'Text & Cat Out Image', 'porto-functionality' ) => 'outimage_cat',
				__( 'Simple & Out Image', 'porto-functionality' ) => 'simple',
			);
		}

		public static function getCustomZoom() {
			return array(
				__( 'Zoom', 'porto-functionality' )    => 'zoom',
				__( 'No_Zoom', 'porto-functionality' ) => 'no_zoom',
			);
		}

		public static function getMemberColumns() {
			return array(
				__( '2', 'porto-functionality' ) => '2',
				__( '3', 'porto-functionality' ) => '3',
				__( '4', 'porto-functionality' ) => '4',
				__( '5', 'porto-functionality' ) => '5',
				__( '6', 'porto-functionality' ) => '6',
			);
		}

		public static function getProductsViewMode() {
			return array(
				__( 'Grid', 'porto-functionality' )   => 'grid',
				__( 'List', 'porto-functionality' )   => 'list',
				__( 'Slider', 'porto-functionality' ) => 'products-slider',
			);
		}

		public static function getProductsColumns() {
			return array(
				'1' => 1,
				'2' => 2,
				'3' => 3,
				'4' => 4,
				'5' => 5,
				'6' => 6,
				'7 ' . __( '(without sidebar)', 'porto-functionality' ) => 7,
				'8 ' . __( '(without sidebar)', 'porto-functionality' ) => 8,
			);
		}

		public static function getProductsColumnWidth() {
			return array(
				__( 'Default', 'porto-functionality' ) => '',
				'1/1' . __( ' of content width', 'porto-functionality' ) => 1,
				'1/2' . __( ' of content width', 'porto-functionality' ) => 2,
				'1/3' . __( ' of content width', 'porto-functionality' ) => 3,
				'1/4' . __( ' of content width', 'porto-functionality' ) => 4,
				'1/5' . __( ' of content width', 'porto-functionality' ) => 5,
				'1/6' . __( ' of content width', 'porto-functionality' ) => 6,
				'1/7' . __( ' of content width (without sidebar)', 'porto-functionality' ) => 7,
				'1/8' . __( ' of content width (without sidebar)', 'porto-functionality' ) => 8,
			);
		}

		public static function getProductsAddlinksPos() {
			return array(
				__( 'Default', 'porto-functionality' )      => '',
				__( 'Out of Image', 'porto-functionality' ) => 'outimage',
				__( 'On Image', 'porto-functionality' )     => 'onimage',
				__( 'Wishlist, Quick View On Image', 'porto-functionality' ) => 'wq_onimage',
				__( 'Out of Image, Quick View On Image', 'porto-functionality' ) => 'outimage_q_onimage',
				__( 'Out of Image, Quick View On Image Alt', 'porto-functionality' ) => 'outimage_q_onimage_alt',
			);
		}

		public static function getProductViewMode() {
			return array(
				__( 'Grid', 'porto-functionality' ) => 'grid',
				__( 'List', 'porto-functionality' ) => 'list',
			);
		}

		public static function getColors() {
			return array(
				''                                     => 'custom',
				__( 'Primary', 'porto-functionality' )    => 'primary',
				__( 'Secondary', 'porto-functionality' )  => 'secondary',
				__( 'Tertiary', 'porto-functionality' )   => 'tertiary',
				__( 'Quaternary', 'porto-functionality' ) => 'quaternary',
				__( 'Dark', 'porto-functionality' )       => 'dark',
				__( 'Light', 'porto-functionality' )      => 'light',
			);
		}

		public static function getContentBoxesBgType() {
			return array(
				__( 'Default', 'porto-functionality' ) => '',
				__( 'Flat', 'porto-functionality' )    => 'featured-boxes-flat',
				__( 'Custom', 'porto-functionality' )  => 'featured-boxes-custom',
			);
		}

		public static function getContentBoxesStyle() {
			return array(
				__( 'Default', 'porto-functionality' ) => '',
				__( 'Style 1', 'porto-functionality' ) => 'featured-boxes-style-1',
				__( 'Style 2', 'porto-functionality' ) => 'featured-boxes-style-2',
				__( 'Style 3', 'porto-functionality' ) => 'featured-boxes-style-3',
				__( 'Style 4', 'porto-functionality' ) => 'featured-boxes-style-4',
				__( 'Style 5', 'porto-functionality' ) => 'featured-boxes-style-5',
				__( 'Style 6', 'porto-functionality' ) => 'featured-boxes-style-6',
				__( 'Style 7', 'porto-functionality' ) => 'featured-boxes-style-7',
				__( 'Style 8', 'porto-functionality' ) => 'featured-boxes-style-8',
			);
		}

		public static function getContentBoxEffect() {
			return array(
				__( 'Default', 'porto-functionality' )  => '',
				__( 'Effect 1', 'porto-functionality' ) => 'featured-box-effect-1',
				__( 'Effect 2', 'porto-functionality' ) => 'featured-box-effect-2',
				__( 'Effect 3', 'porto-functionality' ) => 'featured-box-effect-3',
				__( 'Effect 4', 'porto-functionality' ) => 'featured-box-effect-4',
				__( 'Effect 5', 'porto-functionality' ) => 'featured-box-effect-5',
				__( 'Effect 6', 'porto-functionality' ) => 'featured-box-effect-6',
				__( 'Effect 7', 'porto-functionality' ) => 'featured-box-effect-7',
			);
		}

		public static function getTestimonialStyles() {
			return array(
				__( 'Style 1', 'porto-functionality' ) => '',
				__( 'Style 2', 'porto-functionality' ) => 'testimonial-style-2',
				__( 'Style 3', 'porto-functionality' ) => 'testimonial-style-3',
				__( 'Style 4', 'porto-functionality' ) => 'testimonial-style-4',
				__( 'Style 5', 'porto-functionality' ) => 'testimonial-style-5',
				__( 'Style 6', 'porto-functionality' ) => 'testimonial-style-6',
			);
		}

		public static function getContextual() {
			return array(
				__( 'None', 'porto-functionality' )    => '',
				__( 'Success', 'porto-functionality' ) => 'success',
				__( 'Info', 'porto-functionality' )    => 'info',
				__( 'Warning', 'porto-functionality' ) => 'warning',
				__( 'Danger', 'porto-functionality' )  => 'danger',
			);
		}

		public static function getPosition() {
			return array(
				__( 'Top', 'porto-functionality' )    => 'top',
				__( 'Right', 'porto-functionality' )  => 'right',
				__( 'Bottom', 'porto-functionality' ) => 'bottom',
				__( 'Left', 'porto-functionality' )   => 'left',
			);
		}

		public static function getSize() {
			return array(
				__( 'Normal', 'porto-functionality' )      => '',
				__( 'Large', 'porto-functionality' )       => 'lg',
				__( 'Small', 'porto-functionality' )       => 'sm',
				__( 'Extra Small', 'porto-functionality' ) => 'xs',
			);
		}

		public static function getTrigger() {
			return array(
				__( 'Click', 'porto-functionality' ) => 'click',
				__( 'Hover', 'porto-functionality' ) => 'hover',
				__( 'Focus', 'porto-functionality' ) => 'focus',
			);
		}

		public static function getBootstrapColumns() {
			return array( 6, 4, 3, 2, 1 );
		}

		public static function getPriceBoxesStyle() {
			return array(
				__( 'Default', 'porto-functionality' )     => '',
				__( 'Alternative', 'porto-functionality' ) => 'flat',
				__( 'Classic', 'porto-functionality' )     => 'classic',
			);
		}

		public static function getPriceBoxesSize() {
			return array(
				__( 'Normal', 'porto-functionality' ) => '',
				__( 'Small', 'porto-functionality' )  => 'sm',
			);
		}

		public static function getSortStyle() {
			return array(
				__( 'Default', 'porto-functionality' ) => '',
				__( 'Style 2', 'porto-functionality' ) => 'style-2',
			);
		}

		public static function getSortBy() {
			return array(
				__( 'Original Order', 'porto-functionality' ) => 'original-order',
				__( 'Popular Value', 'porto-functionality' )  => 'popular',
			);
		}

		public static function getGridColumns() {
			return array(
				__( '12 columns - 1/1', 'porto-functionality' ) => '12',
				__( '11 columns - 11/12', 'porto-functionality' ) => '11',
				__( '10 columns - 5/6', 'porto-functionality' ) => '10',
				__( '9 columns - 3/4', 'porto-functionality' )  => '9',
				__( '8 columns - 2/3', 'porto-functionality' )  => '8',
				__( '7 columns - 7/12', 'porto-functionality' ) => '7',
				__( '6 columns - 1/2', 'porto-functionality' )  => '6',
				__( '5 columns - 5/12', 'porto-functionality' ) => '5',
				__( '4 columns - 1/3', 'porto-functionality' )  => '4',
				__( '3 columns - 1/4', 'porto-functionality' )  => '3',
				__( '2 columns - 1/6', 'porto-functionality' )  => '2',
				__( '1 columns - 1/12', 'porto-functionality' ) => '1',
			);
		}

		public static function getPreviewTime() {
			return array(
				__( 'Normal', 'porto-functionality' ) => '',
				__( 'Short', 'porto-functionality' )  => 'short',
				__( 'Long', 'porto-functionality' )   => 'long',
			);
		}

		public static function getPreviewPosition() {
			return array(
				__( 'Center', 'porto-functionality' ) => '',
				__( 'Top', 'porto-functionality' )    => 'top',
				__( 'Bottom', 'porto-functionality' ) => 'bottom',
			);
		}

		public static function getPopupAction() {
			return array(
				__( 'Open URL (Link)', 'porto-functionality' ) => 'open_link',
				__( 'Popup Video or Map', 'porto-functionality' ) => 'popup_iframe',
				__( 'Popup Block', 'porto-functionality' ) => 'popup_block',
			);
		}

		public static function getFeatureBoxStyle() {
			return array(
				__( 'Style 1', 'porto-functionality' ) => '',
				__( 'Style 2', 'porto-functionality' ) => 'feature-box-style-2',
				__( 'Style 3', 'porto-functionality' ) => 'feature-box-style-3',
				__( 'Style 4', 'porto-functionality' ) => 'feature-box-style-4',
				__( 'Style 5', 'porto-functionality' ) => 'feature-box-style-5',
				__( 'Style 6', 'porto-functionality' ) => 'feature-box-style-6',
			);
		}

		public static function getFeatureBoxDir() {
			return array(
				__( 'Default', 'porto-functionality' ) => '',
				__( 'Reverse', 'porto-functionality' ) => 'reverse',
			);
		}

		public static function getSectionSkin() {
			return array(
				__( 'Default', 'porto-functionality' )     => 'default',
				__( 'Transparent', 'porto-functionality' ) => 'parallax',
				__( 'Primary', 'porto-functionality' )     => 'primary',
				__( 'Secondary', 'porto-functionality' )   => 'secondary',
				__( 'Tertiary', 'porto-functionality' )    => 'tertiary',
				__( 'Quaternary', 'porto-functionality' )  => 'quaternary',
				__( 'Dark', 'porto-functionality' )        => 'dark',
				__( 'Light', 'porto-functionality' )       => 'light',
			);
		}

		public static function getSectionColorScale() {
			return array(
				__( 'Default', 'porto-functionality' ) => '',
				__( 'Scale 1', 'porto-functionality' ) => 'scale-1',
				__( 'Scale 2', 'porto-functionality' ) => 'scale-2',
				__( 'Scale 3', 'porto-functionality' ) => 'scale-3',
				__( 'Scale 4', 'porto-functionality' ) => 'scale-4',
				__( 'Scale 5', 'porto-functionality' ) => 'scale-5',
				__( 'Scale 6', 'porto-functionality' ) => 'scale-6',
				__( 'Scale 7', 'porto-functionality' ) => 'scale-7',
				__( 'Scale 8', 'porto-functionality' ) => 'scale-8',
				__( 'Scale 9', 'porto-functionality' ) => 'scale-9',
			);
		}

		public static function getSectionTextColor() {
			return array(
				__( 'Default', 'porto-functionality' ) => '',
				__( 'Dark', 'porto-functionality' )    => 'dark',
				__( 'Light', 'porto-functionality' )   => 'light',
			);
		}

		public static function getSeparatorIconStyle() {
			return array(
				__( 'Style 1', 'porto-functionality' ) => '',
				__( 'Style 2', 'porto-functionality' ) => 'style-2',
				__( 'Style 3', 'porto-functionality' ) => 'style-3',
				__( 'Style 4', 'porto-functionality' ) => 'style-4',
			);
		}

		public static function getSeparatorIconSize() {
			return array(
				__( 'Normal', 'porto-functionality' ) => '',
				__( 'Small', 'porto-functionality' )  => 'sm',
				__( 'Large', 'porto-functionality' )  => 'lg',
			);
		}

		public static function getSeparatorIconPosition() {
			return array(
				__( 'Center', 'porto-functionality' ) => '',
				__( 'Left', 'porto-functionality' )   => 'left',
				__( 'Right', 'porto-functionality' )  => 'right',
			);
		}

		public static function getCarouselNavTypes() {
			return array(
				__( 'Default', 'porto-functionality' )        => '',
				__( 'Rounded', 'porto-functionality' )        => 'rounded-nav',
				__( 'Big & Full Width', 'porto-functionality' ) => 'big-nav',
				__( 'Simple Arrow 1', 'porto-functionality' ) => 'nav-style-1',
				__( 'Simple Arrow 2', 'porto-functionality' ) => 'nav-style-2',
				__( 'Square Grey Arrow', 'porto-functionality' ) => 'nav-style-3',
			);
		}
	}
}

function porto_shortcode_widget_title( $params = array( 'title' => '' ) ) {
	if ( '' == $params['title'] ) {
		return '';
	}

	$extraclass = ( isset( $params['extraclass'] ) ) ? ' ' . $params['extraclass'] : '';
	$output     = '<h4 class="wpb_heading' . $extraclass . '">' . $params['title'] . '</h4>';

	return apply_filters( 'wpb_widget_title', $output, $params );
}

if ( function_exists( 'vc_add_shortcode_param' ) ) {
	vc_add_shortcode_param( 'porto_animation_type', 'porto_theme_vc_animation_type_field' );
	vc_add_shortcode_param( 'porto_theme_animation_type', 'porto_theme_vc_animation_type_field' );
}

function porto_theme_vc_animation_type_field( $settings, $value ) {
	$param_line = '<select name="' . $settings['param_name'] . '" class="wpb_vc_param_value dropdown wpb-input wpb-select ' . $settings['param_name'] . ' ' . $settings['type'] . '">';

	$param_line .= '<option value="">none</option>';

	$param_line .= '<optgroup label="' . __( 'Attention Seekers', 'porto-functionality' ) . '">';
	$options     = array( 'bounce', 'flash', 'pulse', 'rubberBand', 'shake', 'swing', 'tada', 'wobble', 'zoomIn' );
	foreach ( $options as $option ) {
		$selected = '';
		if ( $option == $value ) {
			$selected = ' selected="selected"';
		}
		$param_line .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
	}
	$param_line .= '</optgroup>';

	$param_line .= '<optgroup label="' . __( 'Bouncing Entrances', 'porto-functionality' ) . '">';
	$options     = array( 'bounceIn', 'bounceInDown', 'bounceInLeft', 'bounceInRight', 'bounceInUp' );
	foreach ( $options as $option ) {
		$selected = '';
		if ( $option == $value ) {
			$selected = ' selected="selected"';
		}
		$param_line .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
	}
	$param_line .= '</optgroup>';

	$param_line .= '<optgroup label="' . __( 'Bouncing Exits', 'porto-functionality' ) . '">';
	$options     = array( 'bounceOut', 'bounceOutDown', 'bounceOutLeft', 'bounceOutRight', 'bounceOutUp' );
	foreach ( $options as $option ) {
		$selected = '';
		if ( $option == $value ) {
			$selected = ' selected="selected"';
		}
		$param_line .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
	}
	$param_line .= '</optgroup>';

	$param_line .= '<optgroup label="' . __( 'Fading Entrances', 'porto-functionality' ) . '">';
	$options     = array( 'fadeIn', 'fadeInDown', 'fadeInDownBig', 'fadeInLeft', 'fadeInLeftBig', 'fadeInRight', 'fadeInRightBig', 'fadeInUp', 'fadeInUpBig' );
	foreach ( $options as $option ) {
		$selected = '';
		if ( $option == $value ) {
			$selected = ' selected="selected"';
		}
		$param_line .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
	}
	$param_line .= '</optgroup>';

	$param_line .= '<optgroup label="' . __( 'Fading Exits', 'porto-functionality' ) . '">';
	$options     = array( 'fadeOut', 'fadeOutDown', 'fadeOutDownBig', 'fadeOutLeft', 'fadeOutLeftBig', 'fadeOutRight', 'fadeOutRightBig', 'fadeOutUp', 'fadeOutUpBig' );
	foreach ( $options as $option ) {
		$selected = '';
		if ( $option == $value ) {
			$selected = ' selected="selected"';
		}
		$param_line .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
	}
	$param_line .= '</optgroup>';

	$param_line .= '<optgroup label="' . __( 'Flippers', 'porto-functionality' ) . '">';
	$options     = array( 'flip', 'flipInX', 'flipInY', 'flipOutX', 'flipOutY' );
	foreach ( $options as $option ) {
		$selected = '';
		if ( $option == $value ) {
			$selected = ' selected="selected"';
		}
		$param_line .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
	}
	$param_line .= '</optgroup>';

	$param_line .= '<optgroup label="' . __( 'Lightspeed', 'porto-functionality' ) . '">';
	$options     = array( 'lightSpeedIn', 'lightSpeedOut' );
	foreach ( $options as $option ) {
		$selected = '';
		if ( $option == $value ) {
			$selected = ' selected="selected"';
		}
		$param_line .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
	}
	$param_line .= '</optgroup>';

	$param_line .= '<optgroup label="' . __( 'Rotating Entrances', 'porto-functionality' ) . '">';
	$options     = array( 'rotateIn', 'rotateInDownLeft', 'rotateInDownRight', 'rotateInUpLeft', 'rotateInUpRight' );
	foreach ( $options as $option ) {
		$selected = '';
		if ( $option == $value ) {
			$selected = ' selected="selected"';
		}
		$param_line .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
	}
	$param_line .= '</optgroup>';

	$param_line .= '<optgroup label="' . __( 'Rotating Exits', 'porto-functionality' ) . '">';
	$options     = array( 'rotateOut', 'rotateOutDownLeft', 'rotateOutDownRight', 'rotateOutUpLeft', 'rotateOutUpRight' );
	foreach ( $options as $option ) {
		$selected = '';
		if ( $option == $value ) {
			$selected = ' selected="selected"';
		}
		$param_line .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
	}
	$param_line .= '</optgroup>';

	$param_line .= '<optgroup label="' . __( 'Sliding Entrances', 'porto-functionality' ) . '">';
	$options     = array( 'slideInUp', 'slideInDown', 'slideInLeft', 'slideInRight', 'maskUp' );
	foreach ( $options as $option ) {
		$selected = '';
		if ( $option == $value ) {
			$selected = ' selected="selected"';
		}
		$param_line .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
	}
	$param_line .= '</optgroup>';

	$param_line .= '<optgroup label="' . __( 'Sliding Exit', 'porto-functionality' ) . '">';
	$options     = array( 'slideOutUp', 'slideOutDown', 'slideOutLeft', 'slideOutRight' );
	foreach ( $options as $option ) {
		$selected = '';
		if ( $option == $value ) {
			$selected = ' selected="selected"';
		}
		$param_line .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
	}
	$param_line .= '</optgroup>';

	$param_line .= '<optgroup label="' . __( 'Specials', 'porto-functionality' ) . '">';
	$options     = array( 'hinge', 'rollIn', 'rollOut' );
	foreach ( $options as $option ) {
		$selected = '';
		if ( $option == $value ) {
			$selected = ' selected="selected"';
		}
		$param_line .= '<option value="' . $option . '"' . $selected . '>' . $option . '</option>';
	}
	$param_line .= '</optgroup>';

	$param_line .= '</select>';

	return $param_line;
}

function porto_getCategoryChildsFull( $parent_id, $pos, $array, $level, &$dropdown ) {

	for ( $i = $pos; $i < count( $array ); $i ++ ) {
		if ( $array[ $i ]->category_parent == $parent_id ) {
			$name       = str_repeat( '- ', $level ) . $array[ $i ]->name;
			$value      = $array[ $i ]->slug;
			$dropdown[] = array(
				'label' => $name,
				'value' => $value,
			);
			porto_getCategoryChildsFull( $array[ $i ]->term_id, $i, $array, $level + 1, $dropdown );
		}
	}
}

// Add simple line icon font
if ( ! function_exists( 'vc_iconpicker_type_simpleline' ) ) {
	add_filter( 'vc_iconpicker-type-simpleline', 'vc_iconpicker_type_simpleline' );

	function vc_iconpicker_type_simpleline( $icons ) {
		$simpleline_icons = array(
			array( 'Simple-Line-Icons-user' => 'User' ),
			array( 'Simple-Line-Icons-people' => 'People' ),
			array( 'Simple-Line-Icons-user-female' => 'User Female' ),
			array( 'Simple-Line-Icons-user-follow' => 'User Follow' ),
			array( 'Simple-Line-Icons-user-following' => 'User Following' ),
			array( 'Simple-Line-Icons-user-unfollow' => 'User Unfollow' ),
			array( 'Simple-Line-Icons-login' => 'Login' ),
			array( 'Simple-Line-Icons-logout' => 'Logout' ),
			array( 'Simple-Line-Icons-emotsmile' => 'Emotsmile' ),
			array( 'Simple-Line-Icons-phone' => 'Phone' ),
			array( 'Simple-Line-Icons-call-end' => 'Call End' ),
			array( 'Simple-Line-Icons-call-in' => 'Call In' ),
			array( 'Simple-Line-Icons-call-out' => 'Call Out' ),
			array( 'Simple-Line-Icons-map' => 'Map' ),
			array( 'Simple-Line-Icons-location-pin' => 'Location Pin' ),
			array( 'Simple-Line-Icons-direction' => 'Direction' ),
			array( 'Simple-Line-Icons-directions' => 'Directions' ),
			array( 'Simple-Line-Icons-compass' => 'Compass' ),
			array( 'Simple-Line-Icons-layers' => 'Layers' ),
			array( 'Simple-Line-Icons-menu' => 'Menu' ),
			array( 'Simple-Line-Icons-list' => 'List' ),
			array( 'Simple-Line-Icons-options-vertical' => 'Options Vertical' ),
			array( 'Simple-Line-Icons-options' => 'Options' ),
			array( 'Simple-Line-Icons-arrow-down' => 'Arrow Down' ),
			array( 'Simple-Line-Icons-arrow-left' => 'Arrow Left' ),
			array( 'Simple-Line-Icons-arrow-right' => 'Arrow Right' ),
			array( 'Simple-Line-Icons-arrow-up' => 'Arrow Up' ),
			array( 'Simple-Line-Icons-arrow-up-circle' => 'Arrow Up Circle' ),
			array( 'Simple-Line-Icons-arrow-left-circle' => 'Arrow Left Circle' ),
			array( 'Simple-Line-Icons-arrow-right-circle' => 'Arrow Right Circle' ),
			array( 'Simple-Line-Icons-arrow-down-circle' => 'Arrow Down Circle' ),
			array( 'Simple-Line-Icons-check' => 'Check' ),
			array( 'Simple-Line-Icons-clock' => 'Clock' ),
			array( 'Simple-Line-Icons-plus' => 'Plus' ),
			array( 'Simple-Line-Icons-minus' => 'Minus' ),
			array( 'Simple-Line-Icons-close' => 'Close' ),
			array( 'Simple-Line-Icons-event' => 'Event' ),
			array( 'Simple-Line-Icons-exclamation' => 'Exclamation' ),
			array( 'Simple-Line-Icons-organization' => 'Organization' ),
			array( 'Simple-Line-Icons-trophy' => 'Trophy' ),
			array( 'Simple-Line-Icons-screen-smartphone' => 'Smartphone' ),
			array( 'Simple-Line-Icons-screen-desktop' => 'Desktop' ),
			array( 'Simple-Line-Icons-plane' => 'Plane' ),
			array( 'Simple-Line-Icons-notebook' => 'Notebook' ),
			array( 'Simple-Line-Icons-mustache' => 'Mustache' ),
			array( 'Simple-Line-Icons-mouse' => 'Mouse' ),
			array( 'Simple-Line-Icons-magnet' => 'Magnet' ),
			array( 'Simple-Line-Icons-energy' => 'Energy' ),
			array( 'Simple-Line-Icons-disc' => 'Disc' ),
			array( 'Simple-Line-Icons-cursor' => 'Cursor' ),
			array( 'Simple-Line-Icons-cursor-move' => 'Cursor Move' ),
			array( 'Simple-Line-Icons-crop' => 'Crop' ),
			array( 'Simple-Line-Icons-chemistry' => 'Chemistry' ),
			array( 'Simple-Line-Icons-speedometer' => 'Speedometer' ),
			array( 'Simple-Line-Icons-shield' => 'Shield' ),
			array( 'Simple-Line-Icons-screen-tablet' => 'Tablet' ),
			array( 'Simple-Line-Icons-magic-wand' => 'Magic Wand' ),
			array( 'Simple-Line-Icons-hourglass' => 'Hourglass' ),
			array( 'Simple-Line-Icons-graduation' => 'Graduation' ),
			array( 'Simple-Line-Icons-ghost' => 'Ghost' ),
			array( 'Simple-Line-Icons-game-controller' => 'Game Controller' ),
			array( 'Simple-Line-Icons-fire' => 'Fire' ),
			array( 'Simple-Line-Icons-eyeglass' => 'Eyeglass' ),
			array( 'Simple-Line-Icons-envelope-open' => 'Envelope Open' ),
			array( 'Simple-Line-Icons-envelope-letter' => 'Envelope Letter' ),
			array( 'Simple-Line-Icons-bell' => 'Bell' ),
			array( 'Simple-Line-Icons-badge' => 'Badge' ),
			array( 'Simple-Line-Icons-anchor' => 'Anchor' ),
			array( 'Simple-Line-Icons-wallet' => 'Wallet' ),
			array( 'Simple-Line-Icons-vector' => 'Vector' ),
			array( 'Simple-Line-Icons-speech' => 'Speech' ),
			array( 'Simple-Line-Icons-puzzle' => 'Puzzle' ),
			array( 'Simple-Line-Icons-printer' => 'Printer' ),
			array( 'Simple-Line-Icons-present' => 'Present' ),
			array( 'Simple-Line-Icons-playlist' => 'Playlist' ),
			array( 'Simple-Line-Icons-pin' => 'Pin' ),
			array( 'Simple-Line-Icons-picture' => 'Picture' ),
			array( 'Simple-Line-Icons-handbag' => 'Handbag' ),
			array( 'Simple-Line-Icons-globe-alt' => 'Globe Alt' ),
			array( 'Simple-Line-Icons-globe' => 'Globe' ),
			array( 'Simple-Line-Icons-folder-alt' => 'Folder Alt' ),
			array( 'Simple-Line-Icons-folder' => 'Folder' ),
			array( 'Simple-Line-Icons-film' => 'Film' ),
			array( 'Simple-Line-Icons-feed' => 'Feed' ),
			array( 'Simple-Line-Icons-drop' => 'Drop' ),
			array( 'Simple-Line-Icons-drawer' => 'Drawer' ),
			array( 'Simple-Line-Icons-docs' => 'Docs' ),
			array( 'Simple-Line-Icons-doc' => 'Doc' ),
			array( 'Simple-Line-Icons-diamond' => 'Diamond' ),
			array( 'Simple-Line-Icons-cup' => 'Cup' ),
			array( 'Simple-Line-Icons-calculator' => 'Calculator' ),
			array( 'Simple-Line-Icons-bubbles' => 'Bubbles' ),
			array( 'Simple-Line-Icons-briefcase' => 'Briefcase' ),
			array( 'Simple-Line-Icons-book-open' => 'Book Open' ),
			array( 'Simple-Line-Icons-basket-loaded' => 'Basket Loaded' ),
			array( 'Simple-Line-Icons-basket' => 'Basket' ),
			array( 'Simple-Line-Icons-bag' => 'Bag' ),
			array( 'Simple-Line-Icons-action-undo' => 'Action Undo' ),
			array( 'Simple-Line-Icons-action-redo' => 'Action Redo' ),
			array( 'Simple-Line-Icons-wrench' => 'Wrench' ),
			array( 'Simple-Line-Icons-umbrella' => 'Umbrella' ),
			array( 'Simple-Line-Icons-trash' => 'Trash' ),
			array( 'Simple-Line-Icons-tag' => 'Tag' ),
			array( 'Simple-Line-Icons-support' => 'Support' ),
			array( 'Simple-Line-Icons-frame' => 'Frame' ),
			array( 'Simple-Line-Icons-size-fullscreen' => 'Size Fullscreen' ),
			array( 'Simple-Line-Icons-size-actual' => 'Size Actual' ),
			array( 'Simple-Line-Icons-shuffle' => 'Shuffle' ),
			array( 'Simple-Line-Icons-share-alt' => 'Share Alt' ),
			array( 'Simple-Line-Icons-share' => 'Share' ),
			array( 'Simple-Line-Icons-rocket' => 'Rocket' ),
			array( 'Simple-Line-Icons-question' => 'Question' ),
			array( 'Simple-Line-Icons-pie-chart' => 'Pie Chart' ),
			array( 'Simple-Line-Icons-pencil' => 'Pencil' ),
			array( 'Simple-Line-Icons-note' => 'Note' ),
			array( 'Simple-Line-Icons-loop' => 'Loop' ),
			array( 'Simple-Line-Icons-home' => 'Home' ),
			array( 'Simple-Line-Icons-grid' => 'Grid' ),
			array( 'Simple-Line-Icons-graph' => 'Graph' ),
			array( 'Simple-Line-Icons-microphone' => 'Microphone' ),
			array( 'Simple-Line-Icons-music-tone-alt' => 'Music Tone Alt' ),
			array( 'Simple-Line-Icons-music-tone' => 'Music Tone' ),
			array( 'Simple-Line-Icons-earphones-alt' => 'Earphones Alt' ),
			array( 'Simple-Line-Icons-earphones' => 'Earphones' ),
			array( 'Simple-Line-Icons-equalizer' => 'Equalizer' ),
			array( 'Simple-Line-Icons-like' => 'Like' ),
			array( 'Simple-Line-Icons-dislike' => 'Dislike' ),
			array( 'Simple-Line-Icons-control-start' => 'Control Start' ),
			array( 'Simple-Line-Icons-control-rewind' => 'Control Rewind' ),
			array( 'Simple-Line-Icons-control-play' => 'Control Play' ),
			array( 'Simple-Line-Icons-control-pause' => 'Control Pause' ),
			array( 'Simple-Line-Icons-control-forward' => 'Control Forward' ),
			array( 'Simple-Line-Icons-control-end' => 'Control End' ),
			array( 'Simple-Line-Icons-volume-1' => 'Volume 1' ),
			array( 'Simple-Line-Icons-volume-2' => 'Volume 2' ),
			array( 'Simple-Line-Icons-volume-off' => 'Volume Off' ),
			array( 'Simple-Line-Icons-calendar' => 'Calendar' ),
			array( 'Simple-Line-Icons-bulb' => 'Bulb' ),
			array( 'Simple-Line-Icons-chart' => 'Chart' ),
			array( 'Simple-Line-Icons-ban' => 'Ban' ),
			array( 'Simple-Line-Icons-bubble' => 'Bubble' ),
			array( 'Simple-Line-Icons-camcorder' => 'Camcorder' ),
			array( 'Simple-Line-Icons-camera' => 'Camera' ),
			array( 'Simple-Line-Icons-cloud-download' => 'Cloud Download' ),
			array( 'Simple-Line-Icons-cloud-upload' => 'Cloud Upload' ),
			array( 'Simple-Line-Icons-envelope' => 'Envelope' ),
			array( 'Simple-Line-Icons-eye' => 'Eye' ),
			array( 'Simple-Line-Icons-flag' => 'Flag' ),
			array( 'Simple-Line-Icons-heart' => 'Heart' ),
			array( 'Simple-Line-Icons-info' => 'Info' ),
			array( 'Simple-Line-Icons-key' => 'Key' ),
			array( 'Simple-Line-Icons-link' => 'Link' ),
			array( 'Simple-Line-Icons-lock' => 'Lock' ),
			array( 'Simple-Line-Icons-lock-open' => 'Lock Open' ),
			array( 'Simple-Line-Icons-magnifier' => 'Magnifier' ),
			array( 'Simple-Line-Icons-magnifier-add' => 'Magnifier Add' ),
			array( 'Simple-Line-Icons-magnifier-remove' => 'Magnifier Remove' ),
			array( 'Simple-Line-Icons-paper-clip' => 'Paper Clip' ),
			array( 'Simple-Line-Icons-paper-plane' => 'Paper Plane' ),
			array( 'Simple-Line-Icons-power' => 'Power' ),
			array( 'Simple-Line-Icons-refresh' => 'Refresh' ),
			array( 'Simple-Line-Icons-reload' => 'Reload' ),
			array( 'Simple-Line-Icons-settings' => 'Settings' ),
			array( 'Simple-Line-Icons-star' => 'Star' ),
			array( 'Simple-Line-Icons-symbol-female' => 'Symbol Female' ),
			array( 'Simple-Line-Icons-symbol-male' => 'Symbol Male' ),
			array( 'Simple-Line-Icons-target' => 'Target' ),
			array( 'Simple-Line-Icons-credit-card' => 'Credit Card' ),
			array( 'Simple-Line-Icons-paypal' => 'Paypal' ),
			array( 'Simple-Line-Icons-social-tumblr' => 'Tumblr' ),
			array( 'Simple-Line-Icons-social-twitter' => 'Twitter' ),
			array( 'Simple-Line-Icons-social-facebook' => 'Facebook' ),
			array( 'Simple-Line-Icons-social-instagram' => 'Instagram' ),
			array( 'Simple-Line-Icons-social-linkedin' => 'Linkedin' ),
			array( 'Simple-Line-Icons-social-pinterest' => 'Pinterest' ),
			array( 'Simple-Line-Icons-social-github' => 'Github' ),
			array( 'Simple-Line-Icons-social-google' => 'Google' ),
			array( 'Simple-Line-Icons-social-reddit' => 'Reddit' ),
			array( 'Simple-Line-Icons-social-skype' => 'Skype' ),
			array( 'Simple-Line-Icons-social-dribbble' => 'Dribbble' ),
			array( 'Simple-Line-Icons-social-behance' => 'Behance' ),
			array( 'Simple-Line-Icons-social-foursqare' => 'Foursqare' ),
			array( 'Simple-Line-Icons-social-soundcloud' => 'Soundcloud' ),
			array( 'Simple-Line-Icons-social-spotify' => 'Spotify' ),
			array( 'Simple-Line-Icons-social-stumbleupon' => 'Stumbleupon' ),
			array( 'Simple-Line-Icons-social-youtube' => 'Youtube' ),
			array( 'Simple-Line-Icons-social-dropbox' => 'Dropbox' ),
			array( 'Simple-Line-Icons-social-vkontakte' => 'Vkontakte' ),
			array( 'Simple-Line-Icons-social-steam' => 'Steam' ),
			array( 'Simple-Line-Icons-moustache' => 'Moustache' ),
			array( 'Simple-Line-Icons-bar-chart' => 'Bar Chart' ),
			array( 'Simple-Line-Icons-pointer' => 'Pointer' ),
			array( 'Simple-Line-Icons-users' => 'Users' ),
			array( 'Simple-Line-Icons-eyeglasses' => 'Eyeglasses' ),
			array( 'Simple-Line-Icons-symbol-fermale' => 'Symbol Fermale' ),
		);

		return array_merge( $icons, $simpleline_icons );
	}
}

// Add porto icon font
if ( ! function_exists( 'vc_iconpicker_type_porto' ) ) {
	add_filter( 'vc_iconpicker-type-porto', 'vc_iconpicker_type_porto' );

	function vc_iconpicker_type_porto( $icons ) {
		$porto_icons = array(
			array( 'porto-icon-spin1' => 'Spin1' ),
			array( 'porto-icon-spin2' => 'Spin2' ),
			array( 'porto-icon-spin3' => 'Spin3' ),
			array( 'porto-icon-spin4' => 'Spin4' ),
			array( 'porto-icon-spin5' => 'Spin5' ),
			array( 'porto-icon-spin6' => 'Spin6' ),
			array( 'porto-icon-firefox' => 'Firefox' ),
			array( 'porto-icon-chrome' => 'Chrome' ),
			array( 'porto-icon-opera' => 'Opera' ),
			array( 'porto-icon-ie' => 'Ie' ),
			array( 'porto-icon-phone' => 'Phone' ),
			array( 'porto-icon-down-dir' => 'Down Dir' ),
			array( 'porto-icon-cart' => 'Cart' ),
			array( 'porto-icon-up-dir' => 'Up Dir' ),
			array( 'porto-icon-mode-grid' => 'Mode Grid' ),
			array( 'porto-icon-mode-list' => 'Mode List' ),
			array( 'porto-icon-compare' => 'Compare' ),
			array( 'porto-icon-wishlist' => 'Wishlist' ),
			array( 'porto-icon-search' => 'Search' ),
			array( 'porto-icon-left-dir' => 'Left Dir' ),
			array( 'porto-icon-right-dir' => 'Right Dir' ),
			array( 'porto-icon-down-open' => 'Down Open' ),
			array( 'porto-icon-left-open' => 'Left Open' ),
			array( 'porto-icon-right-open' => 'Right Open' ),
			array( 'porto-icon-up-open' => 'Up Open' ),
			array( 'porto-icon-angle-left' => 'Angle Left' ),
			array( 'porto-icon-angle-right' => 'Angle Right' ),
			array( 'porto-icon-angle-up' => 'Angle Up' ),
			array( 'porto-icon-angle-down' => 'Angle Down' ),
			array( 'porto-icon-down' => 'Down' ),
			array( 'porto-icon-left' => 'Left' ),
			array( 'porto-icon-right' => 'Right' ),
			array( 'porto-icon-up' => 'Up' ),
			array( 'porto-icon-angle-double-left' => 'Angle Double Left' ),
			array( 'porto-icon-angle-double-right' => 'Angle Double Right' ),
			array( 'porto-icon-angle-double-up' => 'Angle Double Up' ),
			array( 'porto-icon-angle-double-down' => 'Angle Double Down' ),
			array( 'porto-icon-mail' => 'Mail' ),
			array( 'porto-icon-location' => 'Location' ),
			array( 'porto-icon-skype' => 'Skype' ),
			array( 'porto-icon-right-open-big' => 'Right Open Big' ),
			array( 'porto-icon-left-open-big' => 'Left Open Big' ),
			array( 'porto-icon-down-open-big' => 'Down Open Big' ),
			array( 'porto-icon-up-open-big' => 'Up Open Big' ),
			array( 'porto-icon-cancel' => 'Cancel' ),
			array( 'porto-icon-user' => 'User' ),
			array( 'porto-icon-mail-alt' => 'Mail Alt' ),
			array( 'porto-icon-fax' => 'Fax' ),
			array( 'porto-icon-lock' => 'Lock' ),
			array( 'porto-icon-company' => 'Company' ),
			array( 'porto-icon-city' => 'City' ),
			array( 'porto-icon-post' => 'Post' ),
			array( 'porto-icon-country' => 'Country' ),
			array( 'porto-icon-calendar' => 'Calendar' ),
			array( 'porto-icon-doc' => 'Doc' ),
			array( 'porto-icon-mobile' => 'Mobile' ),
			array( 'porto-icon-clock' => 'Clock' ),
			array( 'porto-icon-chat' => 'Chat' ),
			array( 'porto-icon-tag' => 'Tag' ),
			array( 'porto-icon-folder' => 'Folder' ),
			array( 'porto-icon-folder-open' => 'Folder Open' ),
			array( 'porto-icon-forward' => 'Forward' ),
			array( 'porto-icon-reply' => 'Reply' ),
			array( 'porto-icon-cog' => 'Cog' ),
			array( 'porto-icon-cog-alt' => 'Cog Alt' ),
			array( 'porto-icon-wrench' => 'Wrench' ),
			array( 'porto-icon-quote-left' => 'Quote Left' ),
			array( 'porto-icon-quote-right' => 'Quote Right' ),
			array( 'porto-icon-gift' => 'Gift' ),
			array( 'porto-icon-dollar' => 'Dollar' ),
			array( 'porto-icon-euro' => 'Euro' ),
			array( 'porto-icon-pound' => 'Pound' ),
			array( 'porto-icon-rupee' => 'Rupee' ),
			array( 'porto-icon-yen' => 'Yen' ),
			array( 'porto-icon-rouble' => 'Rouble' ),
			array( 'porto-icon-try' => 'Try' ),
			array( 'porto-icon-won' => 'Won' ),
			array( 'porto-icon-bitcoin' => 'Bitcoin' ),
			array( 'porto-icon-ok' => 'Ok' ),
			array( 'porto-icon-chevron-left' => 'Chevron Left' ),
			array( 'porto-icon-chevron-right' => 'Chevron Right' ),
			array( 'porto-icon-export' => 'Export' ),
			array( 'porto-icon-star' => 'Star' ),
			array( 'porto-icon-star-empty' => 'Star Empty' ),
			array( 'porto-icon-plus-squared' => 'Plus Squared' ),
			array( 'porto-icon-minus-squared' => 'Minus Squared' ),
			array( 'porto-icon-plus-squared-alt' => 'Plus Squared Alt' ),
			array( 'porto-icon-minus-squared-alt' => 'Minus Squared Alt' ),
			array( 'porto-icon-truck' => 'Truck' ),
			array( 'porto-icon-lifebuoy' => 'Lifebuoy' ),
			array( 'porto-icon-pencil' => 'Pencil' ),
			array( 'porto-icon-users' => 'Users' ),
			array( 'porto-icon-video' => 'Video' ),
			array( 'porto-icon-menu' => 'Menu' ),
			array( 'porto-icon-desktop' => 'Desktop' ),
			array( 'porto-icon-doc-inv' => 'Doc Inv' ),
			array( 'porto-icon-circle' => 'Circle' ),
			array( 'porto-icon-circle-empty' => 'Circle Empty' ),
			array( 'porto-icon-circle-thin' => 'Circle Thin' ),
			array( 'porto-icon-mini-cart' => 'Mini Cart' ),
			array( 'porto-icon-paper-plane' => 'Paper Plane' ),
			array( 'porto-icon-attention-alt' => 'Attention Alt' ),
			array( 'porto-icon-info' => 'Info' ),
			array( 'porto-icon-compare-link' => 'Compare Link' ),
			array( 'porto-icon-cat-default' => 'Cat Default' ),
			array( 'porto-icon-cat-computer' => 'Cat Computer' ),
			array( 'porto-icon-cat-couch' => 'Cat Couch' ),
			array( 'porto-icon-cat-garden' => 'Cat Garden' ),
			array( 'porto-icon-cat-gift' => 'Cat Gift' ),
			array( 'porto-icon-cat-shirt' => 'Cat Shirt' ),
			array( 'porto-icon-cat-sport' => 'Cat Sport' ),
			array( 'porto-icon-cat-toys' => 'Cat Toys' ),
			array( 'porto-icon-tag-line' => 'Tag L`ine' ),
			array( 'porto-icon-bag' => 'Bag' ),
			array( 'porto-icon-search-1' => 'Search-1' ),
			array( 'porto-icon-plus' => 'Plus' ),
			array( 'porto-icon-minus' => 'Minus' ),
			array( 'porto-icon-search-2' => 'Search-2' ),
			array( 'porto-icon-bag-1' => 'Bag-1' ),
			array( 'porto-icon-online-support' => 'Online Support' ),
			array( 'porto-icon-shopping-bag' => 'Shopping Bag' ),
			array( 'porto-icon-us-dollar' => 'Us Dollar' ),
			array( 'porto-icon-shipped' => 'Shipped' ),
			array( 'porto-icon-list' => 'List' ),
			array( 'porto-icon-money' => 'Money' ),
			array( 'porto-icon-shipping' => 'Shipping' ),
			array( 'porto-icon-support' => 'Support' ),
			array( 'porto-icon-bag-2' => 'Bag-2' ),
			array( 'porto-icon-grid' => 'Grid' ),
			array( 'porto-icon-bag-3' => 'Bag-3' ),
			array( 'porto-icon-direction' => 'Direction' ),
			array( 'porto-icon-home' => 'Home' ),
			array( 'porto-icon-magnifier' => 'Magnifier' ),
			array( 'porto-icon-magnifier-add' => 'Magnifier Add' ),
			array( 'porto-icon-magnifier-remove' => 'Magnifier Remove' ),
			array( 'porto-icon-phone-1' => 'Phone-1' ),
			array( 'porto-icon-clock-1' => 'Clock-1' ),
			array( 'porto-icon-heart' => 'Heart' ),
			array( 'porto-icon-heart-1' => 'Heart-1' ),
			array( 'porto-icon-earphones-alt' => 'Earphones Alt' ),
			array( 'porto-icon-credit-card' => 'Credit Card' ),
			array( 'porto-icon-action-undo' => 'Action Undo' ),
			array( 'porto-icon-envolope' => 'Envolope' ),
			array( 'porto-icon-twitter' => 'Twitter' ),
			array( 'porto-icon-facebook' => 'Facebook' ),
			array( 'porto-icon-spinner' => 'Spinner' ),
			array( 'porto-icon-instagram' => 'Instagram' ),
			array( 'porto-icon-check-empty' => 'Check Empty' ),
			array( 'porto-icon-check' => 'Check' ),
		);

		return array_merge( $icons, $porto_icons );
	}
}

/* 4.0 */
// extra shortcodes added in 4.0
if ( function_exists( 'vc_add_shortcode_param' ) ) {
	vc_add_shortcode_param( 'porto_param_heading', 'porto_param_heading_callback' );
	if ( ! class_exists( 'Ultimate_VC_Addons' ) ) {
		vc_add_shortcode_param( 'number', 'porto_number_settings_field' );
		vc_add_shortcode_param( 'datetimepicker', 'porto_datetimepicker', plugins_url( '../assets/js/bootstrap-datetimepicker.min.js', __FILE__ ) );
	}
	vc_add_shortcode_param( 'porto_boxshadow', 'porto_boxshadow_callback', plugins_url( '../assets/js/box-shadow-param.js', __FILE__ ) );
}
function porto_param_heading_callback( $settings, $value ) {
	$dependency = '';
	$param_name = isset( $settings['param_name'] ) ? $settings['param_name'] : '';
	$class      = isset( $settings['class'] ) ? ' ' . $settings['class'] : '';
	$text       = isset( $settings['text'] ) ? $settings['text'] : '';
	$output     = '<h4 ' . $dependency . ' class="porto-admin-shortcodes-heading' . esc_attr( $class ) . '">' . esc_html( $text ) . '</h4>';
	$output    .= '<input type="hidden" name="' . esc_attr( $settings['param_name'] ) . '" class="wpb_vc_param_value porto-param-heading ' . esc_attr( $settings['param_name'] ) . ' ' . esc_attr( $settings['type'] ) . '_field" value="' . esc_attr( $value ) . '" ' . $dependency . '/>';
	return $output;
}
function porto_number_settings_field( $settings, $value ) {
	$dependency = '';
	$param_name = isset( $settings['param_name'] ) ? $settings['param_name'] : '';
	$type       = isset( $settings['type'] ) ? $settings['type'] : '';
	$min        = isset( $settings['min'] ) ? $settings['min'] : '';
	$max        = isset( $settings['max'] ) ? $settings['max'] : '';
	$step       = isset( $settings['step'] ) ? $settings['step'] : '';
	$suffix     = isset( $settings['suffix'] ) ? $settings['suffix'] : '';
	$class      = isset( $settings['class'] ) ? $settings['class'] : '';
	$output     = '<input type="number" min="' . esc_attr( $min ) . '" max="' . esc_attr( $max ) . '" step="' . esc_attr( $step ) . '" class="wpb_vc_param_value ' . esc_attr( $param_name ) . ' ' . esc_attr( $type ) . ' ' . esc_attr( $class ) . '" name="' . esc_attr( $param_name ) . '" value="' . esc_attr( $value ) . '" style="max-width:100px; margin-right: 10px;" />' . esc_html( $suffix );
	return $output;
}
function porto_boxshadow_callback( $settings, $value ) {
	$dependency   = '';
	$positions    = $settings['positions'];
	$enable_color = isset( $settings['enable_color'] ) ? $settings['enable_color'] : true;
	$unit         = isset( $settings['unit'] ) ? $settings['unit'] : 'px';

	$uid  = 'porto-boxshadow-' . rand( 1000, 9999 );
	$html = '<div class="porto-boxshadow" id="' . esc_attr( $uid ) . '" data-unit="' . esc_attr( $unit ) . '" >';

	$label = 'Shadow Style';
	if ( isset( $settings['label_style'] ) && $settings['label_style'] ) {
		$label = $settings['label_style'];
	}
	$html             .= '<div class="porto-bs-select-block">';
		$html         .= '<div class="porto-bs-select-wrap">';
			$html     .= '<select class="porto-bs-select" >';
				$html .= '<option value="none">' . esc_html__( 'None', 'porto-functionality' ) . '</option>';
				$html .= '<option value="inherit"' . ( isset( $settings['default_style'] ) && 'inherit' == $settings['default_style'] ? ' selected="selected"' : '' ) . '>' . esc_html__( 'Inherit', 'porto-functionality' ) . '</option>';
				$html .= '<option value="inset"' . ( isset( $settings['default_style'] ) && 'inset' == $settings['default_style'] ? ' selected="selected"' : '' ) . '>' . esc_html__( 'Inset', 'porto-functionality' ) . '</option>';
				$html .= '<option value="outset"' . ( isset( $settings['default_style'] ) && 'outset' == $settings['default_style'] ? ' selected="selected"' : '' ) . '>' . esc_html__( 'Outset', 'porto-functionality' ) . '</option>';
			$html     .= '</select>';
		$html         .= '</div>';
	$html             .= '</div>';

	$html .= '<div class="porto-bs-input-block" >';
	foreach ( $positions as $key => $default_value ) {
		switch ( $key ) {
			case 'Horizontal':
				$dashicon = 'dashicons dashicons-leftright';
				$html    .= porto_boxshadow_param_item( $dashicon, $unit, $default_value, $key );
				break;
			case 'Vertical':
				$dashicon = 'dashicons dashicons-sort';
				$html    .= porto_boxshadow_param_item( $dashicon, $unit, $default_value, $key );
				break;
			case 'Blur':
				$dashicon = 'dashicons dashicons-visibility';
				$html    .= porto_boxshadow_param_item( $dashicon, $unit, $default_value, $key );
				break;
			case 'Spread':
				$dashicon = 'dashicons dashicons-location';
				$html    .= porto_boxshadow_param_item( $dashicon, $unit, $default_value, $key );
				break;
		}
	}
	$html .= porto_bs_get_units( $unit );
	$html .= '</div>';

	if ( $enable_color ) {
		$label = __( 'Box Shadow Color', 'porto-functionality' );
		if ( isset( $settings['label_color'] ) && $settings['label_color'] ) {
			$label = $settings['label_color'];
		}
		$html         .= '<div class="porto-bs-colorpicker-block">';
			$html     .= '<div class="label wpb_element_label">';
				$html .= esc_html( $label );
			$html     .= '</div>';
			$html     .= '<div class="porto-bs-colorpicker-wrap">';
				$html .= '<input name="" class="porto-bs-colorpicker cs-wp-color-picker" type="text" value="" />';
			$html     .= '</div>';
		$html         .= '</div>';
	}

	$html .= '  <input type="hidden" data-unit="' . esc_attr( $unit ) . '" name="' . esc_attr( $settings['param_name'] ) . '" class="wpb_vc_param_value porto-bs-result-value ' . esc_attr( $settings['param_name'] ) . ' ' . esc_attr( $settings['type'] ) . '_field" value="' . esc_attr( $value ) . '" ' . $dependency . ' />';
	$html .= '</div>';
	return $html;
}
function porto_boxshadow_param_item( $dashicon, $unit, $default_value, $key ) {
	$html          = '<div class="porto-bs-input-wrap">';
		$html     .= '<span class="porto-bs-icon">';
			$html .= '<span class="porto-bs-tooltip">' . esc_html( $key ) . '</span>';
			$html .= '<i class="' . esc_attr( $dashicon ) . '"></i>';
		$html     .= '</span>';
		$html     .= '<input type="number" class="porto-bs-input" data-unit="' . esc_attr( $unit ) . '" data-id="' . strtolower( esc_attr( $key ) ) . '" data-default="' . esc_attr( $default_value ) . '" placeholder="' . esc_attr( $key ) . '" />';
	$html         .= '</div>';
	return $html;
}
function porto_bs_get_units( $unit ) {
	$html      = '<div class="porto-bs-unit">';
		$html .= '<label>' . esc_html( $unit ) . '</label>';
	$html     .= '</div>';
	return $html;
}

function porto_datetimepicker( $settings, $value ) {
	$dependency = '';
	$param_name = isset( $settings['param_name'] ) ? $settings['param_name'] : '';
	$type       = isset( $settings['type'] ) ? $settings['type'] : '';
	$class      = isset( $settings['class'] ) ? $settings['class'] : '';
	$uni        = uniqid( 'datetimepicker-' . rand() );
	$output     = '<div id="porto-date-time' . esc_attr( $uni ) . '" class="porto-datetime"><input data-format="yyyy/MM/dd hh:mm:ss" readonly class="wpb_vc_param_value ' . esc_attr( $param_name ) . ' ' . esc_attr( $type ) . ' ' . esc_attr( $class ) . '" name="' . esc_attr( $param_name ) . '" style="width:258px;" value="' . esc_attr( $value ) . '" ' . $dependency . '/><div class="add-on" > <i data-time-icon="fa fa-calendar-o" data-date-icon="fa fa-calendar-o"></i></div></div>';
	$output    .= '<script type="text/javascript"></script>';
	return $output;
}

// functions used in extra shortcodes
function porto_get_box_shadow( $content = null, $data = '' ) {

	$result = '';
	if ( $content ) {
		$mainstr = explode( '|', $content );
		$string  = '';
		$mainarr = array();
		if ( ! empty( $mainstr ) && is_array( $mainstr ) ) {
			foreach ( $mainstr as $key => $value ) {
				if ( ! empty( $value ) ) {
					$string = explode( ':', $value );
					if ( is_array( $string ) ) {
						if ( ! empty( $string[1] ) && 'outset' != $string[1] ) {
							$mainarr[ $string[0] ] = $string[1];
						}
					}
				}
			}
		}

		$strkeys = '';
		if ( ! empty( $mainarr ) ) {
			if ( isset( $mainarr['color'] ) && $mainarr['color'] ) {
				$strkeys .= isset( $mainarr['horizontal'] ) && 'px' != $mainarr['horizontal'] ? $mainarr['horizontal'] : '0';
				$strkeys .= ' ';
				$strkeys .= isset( $mainarr['vertical'] ) && 'px' != $mainarr['vertical'] ? $mainarr['vertical'] : '0';
				$strkeys .= ' ';
				$strkeys .= isset( $mainarr['blur'] ) && 'px' != $mainarr['blur'] ? $mainarr['blur'] : '0';
				$strkeys .= ' ';
				$strkeys .= isset( $mainarr['spread'] ) && 'px' != $mainarr['spread'] ? $mainarr['spread'] : '0';
				$strkeys .= ' ';
				$strkeys .= $mainarr['color'];
				$strkeys .= isset( $mainarr['style'] ) && $mainarr['style'] ? ' ' . $mainarr['style'] : '';
			}
		}

		if ( $data ) {
			switch ( $data ) {
				case 'data':
					$result = $strkeys;
					break;
				case 'array':
					$result = $mainarr;
					break;
				case 'css':
				default:
					$result = 'box-shadow:' . $strkeys . ';';
					break;
			}
		} else {
			$result = 'box-shadow:' . $strkeys . ';';
		}
	}

	return $result;
}

function porto_sc_parse_google_font( $fonts_string ) {
	if ( ! class_exists( 'Vc_Google_Fonts' ) ) {
		return false;
	}
	$google_fonts_param = new Vc_Google_Fonts();
	$field_settings     = array();
	$fonts_data         = $fonts_string ? $google_fonts_param->_vc_google_fonts_parse_attributes( $field_settings, $fonts_string ) : '';
	return $fonts_data;
}
function porto_sc_google_font_styles( $fonts_data ) {

	$inline_style = '';
	if ( $fonts_data ) {
		$styles      = array();
		$font_family = explode( ':', $fonts_data['values']['font_family'] );
		$styles[]    = 'font-family:' . $font_family[0];
		$font_styles = explode( ':', $fonts_data['values']['font_style'] );
		$styles[]    = 'font-weight:' . $font_styles[1];
		$styles[]    = 'font-style:' . $font_styles[2];

		foreach ( $styles as $attribute ) {
			$inline_style .= $attribute . '; ';
		}
	}

	return $inline_style;
}
function porto_sc_enqueue_google_fonts( $fonts_data ) {

	global $porto_settings, $porto_google_fonts;

	if ( ! isset( $porto_google_fonts ) ) {
		$fonts              = porto_settings_google_fonts();
		$porto_google_fonts = array();
		foreach ( $fonts as $option ) {
			if ( isset( $porto_settings[ $option . '-font' ]['google'] ) && 'false' !== $porto_settings[ $option . '-font' ]['google'] ) {
				if ( isset( $porto_settings[ $option . '-font' ]['font-family'] ) && $porto_settings[ $option . '-font' ]['font-family'] && ! in_array( $porto_settings[ $option . '-font' ]['font-family'], $porto_google_fonts ) ) {
					$porto_google_fonts[] = $porto_settings[ $option . '-font' ]['font-family'];
				}
			}
		}
	}

	$fonts_str  = '';
	$fonts_name = '';
	foreach ( $fonts_data as $font_data ) {

		if ( ! isset( $font_data['values']['font_family'] ) ) {
			continue;
		}
		$font_family = explode( ':', $font_data['values']['font_family'] );
		if ( in_array( $font_family[0], $porto_google_fonts ) ) {
			continue;
		}
		$porto_google_fonts[] = $font_family[0];
		if ( $fonts_str ) {
			$fonts_str .= '%7C';
		}
		$fonts_str  .= $font_data['values']['font_family'];
		$fonts_name .= $font_family[0];
	}
	if ( ! $fonts_str ) {
		return;
	}

	// Get extra subsets for settings (latin/cyrillic/etc)
	$charsets = array();
	$subsets  = '';
	if ( isset( $porto_settings['select-google-charset'] ) && $porto_settings['select-google-charset'] && isset( $porto_settings['google-charsets'] ) && $porto_settings['google-charsets'] ) {
		foreach ( $porto_settings['google-charsets'] as $charset ) {
			if ( $charset && ! in_array( $charset, $charsets ) ) {
				$charsets[] = $charset;
			}
		}
	}
	if ( ! empty( $charsets ) ) {
		$subsets = '&subset=' . implode( ',', $charsets );
	}

	// We also need to enqueue font from googleapis
	wp_enqueue_style(
		'porto_sc_google_fonts_' . urlencode( $fonts_name ),
		'//fonts.googleapis.com/css?family=' . $fonts_str . $subsets
	);
}
