<?php


# Basic shortcode for outputting the site's base URL
add_shortcode('site-url', 'get_site_url');


# Basic iframe shortcode
add_shortcode('iframe', function($atts, $content = ''){

	/** Arbitrate default sizes */
	$width  =   (int) get_option('embed_size_w');
	$height =   (int) get_option('embed_size_h');
	if($width < 1 && $height > 0)
		$width  =   $height;


	extract(shortcode_atts(array(
		'width'     => $width   > 0 ? $width  : NULL,
		'height'    => $height  > 0 ? $height : NULL,
		'id'        => NULL,
		'class'     => NULL,
		'scrolling' => NULL
	), $atts));


	return build_tag('iframe', array(
		'width'             => $width,
		'height'            => $height,
		'id'                => $id,
		'class'             => $class,
		'allowtransparency' => 'true',
		'scrolling'         => 'no',
		'frameBorder'       => 0,
		'src'               => esc_attr($atts['src'] ?: $content)
	));
});



add_shortcode('subpages', function($atts = array()){
	$posts = get_posts(array(
		'post_parent'   => $atts['id'] ?: get_the_ID(),
		'orderby'       => 'menu_order',
		'order'         => 'ASC',
		'post_type'     => 'page'
	));


	# Return an empty string if there aren't any child pages
	if(!($count = count($posts))) return '';

	# Start capturing output
	ob_start();
?> 
	<ul class="category cells"><?php

		for($i = 0; $i < $count; ++$i){
			$id             = $posts[$i]->ID;
			$thumb_id       = get_post_thumbnail_id($id);
			$size_promo     = wp_get_attachment_image_src($thumb_id, 'promo');
			$size_medium    = wp_get_attachment_image_src($thumb_id, 'medium');

			?><li><a style="background-image: url('<?= esc_attr($size_promo[0]) ?>');" href="<?= get_permalink($posts[$i]); ?>"><span class="btn"><?= $posts[$i]->post_title ?></span><img src="<?= $size_medium[0] ?>" alt="" /></a></li><?php
		}
	?> 
	</ul><?php
	$html = ob_get_contents();
	ob_end_clean();

	return $html;
});
