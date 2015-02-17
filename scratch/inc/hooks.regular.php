<?php
/**
 *	Commonly-used hooks to facilitate general theme development.
 */


#	Remove inline styles printed when the gallery shortcode is used.
add_filter('use_default_gallery_style', '__return_false');


#	Replace the "[...]" automatically appended to generated excerpts with an ellipsis.
add_filter('excerpt_more', function(){ return ' &hellip;'; });


#	(Temporary) workaround for TinyMCE's hostility towards Schema.org attributes.
add_filter('the_content', function($content){
	return preg_replace('# data-(item(?:id|prop|ref|scope|type))\s*=#', ' $1=', $content);
}, 1, 1);


#	Add class attributes to prev/next pagination links.
add_filter('previous_posts_link_attributes',	function(){ return ' class="prev"';	});
add_filter('next_posts_link_attributes',		function(){ return ' class="next"';	});


#	Don't show both the last page number AND the button linking to the last page.
add_filter('wp_pagenavi', function($html){
	$last	=	'#<a class="last" href="([^"]+)">([^<]+)</a>#i';
	if(preg_match($last, $html, $matches) && substr_count($html, $matches[1]) > 1)
		$html	=	preg_replace($last, '', $html);
	return $html;
});


#	Tweaks the HTML returned by WP_PageNavi. 
add_filter('wp_pagenavi', function($input){
	#$input	=	preg_replace('#(^<div class=\'wp-pagenavi\'>|</div>$)#i', '', $input);
	$input	=	str_replace('class="previouspostslink"', 'class="prev"', $input);
	$input	=	str_replace('class="nextpostslink"', 'class="next"', $input);
	return $input;
});



#	Add an extra CSS class to active nav items for brevity's sake.
add_filter('nav_menu_css_class', function($classes, $item, $args){

	$is_active	=	$item->current || $item->current_item_ancestor || $item->current_item_parent;
	$post_type	=	get_post_type();


	#	Yeah, ~128 characters worth of redundant HTML classes per element? No thanks.
	$classes	=	array();


	#	Preserve any custom CSS classes specified in the menu editor.
	if(is_array($custom_classes = get_post_meta($item->ID, '_menu_item_classes', TRUE)))
		$classes	=	array_merge($classes, $custom_classes);

	#	News page
	if($item->object_id === PAGE_FOR_POSTS && $post_type === 'post' && (is_single() || is_archive()))
		$is_active	=	TRUE;

	#	Check for custom post types.
	$post_type	=	get_post_type_object($post_type);
	if(preg_match('#/?'.strtolower($post_type->label).'/?$#i', str_replace(trailingslashit(BLOG_URL), '', $item->url)))
		$is_active	=	TRUE;


	if($is_active)
		array_push($classes, 'active');
	return $classes;
}, 99, 3);



#	Roll back some redundant/unneeded class/ID attributes that just clutter the DOM and add to bandwidth.
add_filter('nav_menu_item_id',	'__return_empty_string');
add_filter('page_css_class',	'__return_empty_array');
add_filter('wp_nav_menu', 'clean_nav_source');
add_filter('wp_page_menu', 'clean_nav_source');
function clean_nav_source($html){
	return str_replace(' class=""', '', $html);
}



#	Exclude certain pages from search results.
add_filter('posts_where', function($query){
	global $wpdb;

	if(is_search()) $query	.=	$wpdb->prepare(
		"AND ID NOT IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %d)",
		META_SEARCH_EXCLUDE, 1
	);

	return $query;
});


#	Allow page/post metadata to override the title displayed to the user.
if(!is_admin()){
	add_filter('the_title', function($title, $id){
		return get_post_meta($id, META_CUSTOM_TITLE, TRUE) ?: $title;
	}, 11, 2);
}


/**
 * Ensure menu links with custom URLs starting with "/" really do point to the site's base URL.
 * 
 * Some installations (particularly those in development environments) might have the WordPress installed
 * in a directory that ISN'T the server's root directory, therefore directing the user opening the link outside
 * the actual site.
 */
add_filter('nav_menu_link_attributes', function($attr){
	if(preg_match('#^/\w#', $attr['href']))
		$attr['href']	=	untrailingslashit(BLOG_URL) . $attr['href'];
	return $attr;
}, 5);



/**
 * Includes any extra body classes that were passed into the $body_classes global.
 * @uses $body_classes
 */
add_filter('body_class', function($classes){
	global $body_classes;
	if(is_array($body_classes))
		$classes	=	array_merge($classes, $body_classes);
	return $classes;
});


/**
 * Enqueues any styles or scripts assigned to this page in its metadata fields, allowing
 * a means of attaching specific stylesheets or scripts to particular pages.
 */
add_action('wp_head', function(){
	$custom	=	get_post_custom();

	if(!is_array($custom) || !is_singular()) return NULL;


	$cc		=	'#^\s*\[if\s+([^\]]+)\]\s+(.*)$#i';

	$css	=	@$custom[META_INCLUDE_CSS];
	if($count = count($css))
		for($i = 0; $i < $count; ++$i){
			$paths	=	array_filter(explode("\n", str_replace('THEME_DIR', THEME_DIR, $css[$i])));
			foreach($paths as $path){

				if(preg_match($cc, $path, $matches)){
					$conditional	=	$matches[1];
					$path			=	$matches[2];
				}
				else unset($conditional);
				
				$name	=	'add-' . basename($path, '.css');
				wp_enqueue_style($name, $path);

				if($conditional)
					wp_style_add_data($name, 'conditional', $conditional);
			}
		}


	$js		=	@$custom[META_INCLUDE_JS];
	if($count = count($js))
		for($i = 0; $i < $count; ++$i){
			$paths	=	array_filter(explode("\n", str_replace('THEME_DIR', THEME_DIR, $js[$i])));
			foreach($paths as $path)
				wp_enqueue_script('add-' . basename($path, '.js'), $path);
		}
}, 1);




/**
 * Outputs any inline snippets of CSS or JavaScript in the page's head. Useful for small, trivial
 * code blocks that're neither worthy of a separate resource nor inclusion in a global resource.
 */
add_action('wp_head', function(){
	$custom	=	get_post_custom();

	if(!is_array($custom) || !is_singular())
		return NULL;

	$css	=	@$custom[META_ADDITIONAL_CSS];
	if($count = count($css)): ?> 
<style type="text/css"><?php
	for($i = 0; $i < $count; ++$i)
		echo PHP_EOL, $css[$i], PHP_EOL;
?></style><?php
	echo PHP_EOL;
	endif;
	
	
	$js	=	@$custom[META_ADDITIONAL_JS];
	if($count = count($js)): ?> 
<script type="text/javascript"><?php
	for($i = 0; $i < $count; ++$i)
		echo PHP_EOL, $js[$i], PHP_EOL;
?></script><?php
	echo PHP_EOL;
	endif;
	
	


	$head	=	@$custom[META_ADDITIONAL_HEAD];
	if($count = count($head)) for($i = 0; $i < $count; ++$i)
		echo PHP_EOL, str_replace('THEME_DIR', THEME_DIR, $head[$i]);

}, 999);




#	Output any page-specific footer content.
add_action('wp_print_footer_scripts', function(){
	$custom	=	get_post_custom();

	if(!is_array($custom) || !is_singular())
		return null;

	$foot	=	@$custom[META_ADDITIONAL_FOOT];
	if($count = count($foot)) for($i = 0; $i < $count; ++$i)
		echo PHP_EOL, str_replace('THEME_DIR', THEME_DIR, $foot[$i]), PHP_EOL;	
}, 999);



#	Allow a quicker means of editing the page's Contact Form 7 entry.
if(defined('WPCF7_VERSION')){

	add_action('admin_bar_menu', function($menu){
		global $post, $wpdb, $current_screen;


		#	In admin panel: check if we're editing an existing CF7 entry.
		if(is_admin() && @('toplevel_page_wpcf7' === $GLOBALS['current_screen']->id)){

			if($page = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$wpdb->posts.' WHERE (post_type = \'page\' OR post_type = \'post\') AND post_content LIKE \'%%[contact-form-7 id="%d"%%\'', $_GET['post']))){
				$type	=	get_post_type_object($page->post_type);
				
				$menu->add_node(array(
					'id'	=>	'edit-wpcf7-page',
					'title'	=>	$type->labels->edit_item,
					'href'	=>	get_edit_post_link($page->ID),
					'meta'	=>	array('class' => 'ab-icon-edit', 'title' => 'Edit the page using this contact form')
				));
			}
			return;
		}


		#	Sanity check: bail if there's no $post global available.
		if(!$post) return;


		#	Post's content contains at least one CF7 shortcode; parse it and grab its ID.
		if(preg_match('#\[contact-form-7[^\]]+id="(\d+)"[^\]]*\]#', $post->post_content, $matches)){
			$id	=	$matches[1];

			$menu->add_node(array(
				'id'	=>	'edit-wpcf7',
				'title'	=>	'Contact Form',
				'href'	=>	admin_url('admin.php?page=wpcf7&post=' . $id . '&action=edit'),
				'meta'	=>	array('class' => 'ab-icon-email')
			));
		}
	}, 90);


	#	DOM juice for adding icons to inserted admin-bar links.
	add_action('wp_after_admin_bar_render', function(){
		?>
		<style type="text/css">.ab-icon-email > a::before{content: "\F466"; top: 2px;}.ab-icon-edit > a::before{content:"\F464"; top: 2px;}</style>
		<?php
	});
}



#	Stop Contact Form 7 from actually sending an e-mail if it's been flagged as silent.
add_filter('wpcf7_skip_mail', function($send = FALSE, $form = NULL){

	$silent	=	$form->additional_setting('silent');
	if(count($silent) && intval($silent[0]))
		return TRUE;

}, 11, 2);
