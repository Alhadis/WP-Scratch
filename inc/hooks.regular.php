<?php
/**
 * Commonly-used hooks to facilitate general theme development.
 */


# Remove inline styles printed when the gallery shortcode is used.
add_filter('use_default_gallery_style', '__return_false');


# Replace the "[...]" automatically appended to generated excerpts with an ellipsis.
add_filter('excerpt_more', function(){ return ' &hellip;'; });


# If something happened less than a minute ago, don't display "1 minute"
add_filter('human_time_diff', function($since, $diff, $from, $to){
	if($diff < MINUTE_IN_SECONDS)
		$since = sprintf(_n('%s second', '%s seconds', $diff), $diff);
	return $since;
}, 99, 4);


# Check if the requesting agent supports WebP
add_action('init', function(){
	global $html_classes, $webp_supported;

	# User-Agent supports the WebP format. Yay!
	if(FALSE !== stripos($_SERVER['HTTP_ACCEPT'], 'image/webp')){
		$webp_supported = TRUE;
		$html_classes[] = 'webp';
	}
});


# Disable those sodding automated update e-mail notifications.
add_filter('auto_core_update_send_email', '__return_false');


# Workaround for TinyMCE's hostility towards Schema.org attributes.
add_filter('the_content', function($content){
	return preg_replace('# data-(item(?:id|prop|ref|scope|type))\s*=#', ' $1=', $content);
}, 1, 1);


# Add class attributes to prev/next pagination links.
add_filter('previous_posts_link_attributes',    function(){ return ' class="prev"'; });
add_filter('next_posts_link_attributes',        function(){ return ' class="next"'; });


# Tweak the HTML returned by WP_PageNavi.
add_filter('wp_pagenavi', function($html){

	# Replace "previouspostslink" and "nextpostslink" with simply "prev" and "next"
	$html = preg_replace('~class="(prev|next)(?:ious)?postslink"~', 'class="$1"', $html);


	# Don't show both the last page number AND the button linking to the last page
	$last = '#<a class="last" href="([^"]+)">([^<]+)</a>#i';
	if(preg_match($last, $html, $matches) && substr_count($html, $matches[1]) > 1)
		$html = preg_replace($last, '', $html);
	return $html;
});



# Add an extra CSS class to active nav items for brevity's sake
add_filter('nav_menu_css_class', function($classes, $item, $args){

	$is_active = $item->current || $item->current_item_ancestor || $item->current_item_parent;
	$post_type = get_post_type();

	# Yeah, ~128 characters worth of redundant HTML classes per element? No thanks.
	$classes = array();


	# Preserve any custom CSS classes specified in the menu editor
	if(is_array($custom_classes = get_post_meta($item->ID, '_menu_item_classes', TRUE)))
		$classes = array_merge($classes, $custom_classes);


	# Make sure a resource has been successfully loaded
	if(!is_404()){

		# News page
		if($item->object_id === NEWS_PAGE_ID && $post_type === 'post' && (is_single() || is_archive()))
			$is_active = TRUE;

		# Check for custom post types
		$post_type = get_post_type_object($post_type);
		if(preg_match('#/?'.strtolower($post_type->label).'/?$#i', str_replace(trailingslashit(SITE_URL), '', $item->url)))
			$is_active = TRUE;
	}


	if($is_active)
		array_push($classes, 'active');

	return $classes;
}, 99, 3);



# Roll back some redundant/unneeded class/ID attributes that just clutter the DOM and add to bandwidth.
add_filter('nav_menu_item_id',  '__return_empty_string');
add_filter('page_css_class',    '__return_empty_array');
add_filter('wp_nav_menu',       'clean_nav_source');
add_filter('wp_page_menu',      'clean_nav_source');
function clean_nav_source($html){
	return str_replace(' class=""', '', $html);
}



# Exclude certain pages from search results.
add_filter('posts_where', function($query){
	global $wpdb;

	if(is_search()) $query .= $wpdb->prepare(
		"AND ID NOT IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %d)",
		META_SEARCH_EXCLUDE, 1
	);

	return $query;
});



# Admin-only hooks
if(!is_admin()){

	# Allow page/post metadata to override the title displayed to the user.
	add_filter('the_title', function($title, $id){
		return get_post_meta($id, META_CUSTOM_TITLE, TRUE) ?: $title;
	}, 11, 2);


	# Hide links to the admin panel for users who aren't logged in.
	add_filter('wp_get_nav_menu_items', function($items){
	
		foreach($items as $key => $value)
			if('/wp-admin' === $value->url && !is_user_logged_in())
				unset($items[$key]);
	
		return $items;
	}, 5, 3);
}



# Ensure ACF gets listed *under* the CPT UI plugin in the admin menu
add_filter('custom_menu_order', '__return_true');
add_filter('menu_order', function($menu){
	$acf          = 'edit.php?post_type=acf';
	$cpt          = 'cptui_main_menu';
	$acf_index    = array_search($acf, $menu);
	$cpt_index    = array_search($cpt, $menu);

	if(FALSE !== $acf_index){
		unset($menu[$acf_index]);
		array_splice($menu, $cpt_index, 0, array($acf));
	}
	return $menu;
});




/**
 * Ensure menu links with custom URLs starting with "/" really do point to the site's base URL.
 * 
 * Some installations (particularly those in development environments) might have the WordPress installed
 * in a directory that ISN'T the server's root directory, therefore directing the user opening the link outside
 * the actual site.
 */
add_filter('nav_menu_link_attributes', function($attr){
	if(preg_match('#^/\w#', $attr['href']))
		$attr['href'] = untrailingslashit(SITE_URL) . $attr['href'];
	return $attr;
}, 5);



/**
 * Include any extra body classes that were passed into the $body_classes global.
 * @uses $body_classes
 */
add_filter('body_class', function($classes){
	global $body_classes;
	if(is_array($body_classes))
		$classes = array_merge($classes, $body_classes);
	return $classes;
});



/**
 * Enqueue any styles or scripts assigned to this page in its metadata fields, allowing
 * a means of attaching specific stylesheets or scripts to particular pages.
 */
add_action('wp_head', function(){
	global $css_version, $js_version;

	# Bail if there's no metadata, or this isn't a WordPress page.
	if(!is_array($custom = get_post_custom()) || !is_singular()) return NULL;


	$cc         = '#^\s*\[if\s+([^\]]+)\]\s+(.*)$#i';
	$non_word   = '#[^A-Za-z0-9\-_]+#';


	foreach(meta_urls(META_INCLUDE_CSS) as $path){

		if(preg_match($cc, $path, $matches)){
			$conditional  = $matches[1];
			$path         = $matches[2];
		} else unset($conditional);

		$name = 'add-' . preg_replace($non_word, '-', basename($path, '.css'));
		wp_enqueue_style($name, $path, NULL, $css_version);

		if($conditional)
			wp_style_add_data($name, 'conditional', $conditional);
	}


	foreach(meta_urls(META_INCLUDE_JS) as $path){
		$name           = 'add-' . preg_replace($non_word, '-', basename($path, '.js'));
		$in_footer      = TRUE;

		# If the line starts with "[in-header]" or "[header]", don't queue the script in the footer.
		if(preg_match('#^\s*\[(?:in[-_])?header\]\s+(.*)$#i', $path, $matches)){
			$path       = $matches[1];
			$in_footer  = FALSE;
		}

		wp_enqueue_script($name, $path, NULL, $js_version, $in_footer);
	}
}, 1);




/**
 * Output any inline snippets of CSS or JavaScript in the page's head.
 *
 * Useful for small, trivial code blocks that're neither worthy of a
 * separate resource nor inclusion in a global resource.
 */
add_action('wp_head', function(){
	$custom = get_post_custom();

	if(!is_array($custom) || !is_singular())
		return NULL;

	$css = @$custom[META_ADDITIONAL_CSS];
	if($count = count($css)){ ?> 
<style><?php
	for($i = 0; $i < $count; ++$i)
		echo PHP_EOL, $css[$i], PHP_EOL;
?></style><?php
	echo PHP_EOL;
	}
	
	
	$js = @$custom[META_ADDITIONAL_JS];
	if($count = count($js)){ ?> 
<script><?php
	for($i = 0; $i < $count; ++$i)
		echo PHP_EOL, $js[$i], PHP_EOL;
?></script><?php
	echo PHP_EOL;
	}


	$head = @$custom[META_ADDITIONAL_HEAD];
	if($count = count($head))
		for($i = 0; $i < $count; ++$i)
			echo PHP_EOL, str_replace('THEME_DIR', THEME_DIR, $head[$i]);

}, 999);



# Output any page-specific footer content.
add_action('wp_print_footer_scripts', function(){
	$custom  = get_post_custom();

	if(!is_array($custom) || !is_singular())
		return NULL;

	$foot    = @$custom[META_ADDITIONAL_FOOT];
	if($count = count($foot))
		for($i = 0; $i < $count; ++$i)
			echo PHP_EOL, str_replace('THEME_DIR', THEME_DIR, $foot[$i]), PHP_EOL;
}, 999);




# Allow a quicker means of editing the page's Contact Form 7 entry.
if(defined('WPCF7_VERSION')){

	add_action('admin_bar_menu', function($menu){
		global $post, $wpdb, $current_screen;


		# In admin panel: check if we're editing an existing CF7 entry.
		if(is_admin() && @('toplevel_page_wpcf7' === $GLOBALS['current_screen']->id)){

			if($page = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$wpdb->posts.' WHERE (post_type = \'page\' OR post_type = \'post\') AND post_content LIKE \'%%[contact-form-7 id="%d"%%\'', $_GET['post']))){
				$type       = get_post_type_object($page->post_type);

				$menu->add_node(array(
					'id'    =>  'edit-wpcf7-page',
					'title' =>  $type->labels->edit_item,
					'href'  =>  get_edit_post_link($page->ID),
					'meta'  =>  array(
						'class' => 'ab-icon-edit',
						'title' => 'Edit the page using this contact form'
					)
				));
			}
			return;
		}


		# Sanity check: bail if there's no $post global available.
		if(!$post) return;


		# Post's content contains at least one CF7 shortcode; parse it and grab its ID.
		if(preg_match('#\[contact-form-7[^\]]+id="(\d+)"[^\]]*\]#', $post->post_content, $matches)){
			$id = $matches[1];

			$menu->add_node(array(
				'id'    => 'edit-wpcf7',
				'title' => 'Contact Form',
				'href'  => admin_url('admin.php?page=wpcf7&post=' . $id . '&action=edit'),
				'meta'  => array('class' => 'ab-icon-email')
			));
		}
	}, 90);


	# DOM juice for adding icons to inserted admin-bar links.
	add_action('wp_after_admin_bar_render', function(){ ?> 
		<style>.ab-icon-email > a::before{content: "\F466"; top: 2px;}.ab-icon-edit > a::before{content:"\F464"; top: 2px;}</style>
		<?php
	});
}



# Stop Contact Form 7 from actually sending an e-mail if it's been flagged as silent.
add_filter('wpcf7_skip_mail', function($send = FALSE, $form = NULL){

	$silent = $form->additional_setting('silent');
	if(count($silent) && intval($silent[0]))
		return TRUE;

}, 11, 2);




# Check if we're running locally and we're serving the page to somebody over an internal network IP
$dev_ips    = (array) get_option('developer_ip');
$host_ip    = $_SERVER['HTTP_HOST'];

if($dev_ips && in_array($host_ip, $dev_ips)){
	ob_start();

	# Hold WordPress's flusher function until we're finished
	remove_action('shutdown', 'wp_ob_end_flush_all', 1);

	$dev_url = get_option('developer_url', '%1$s%2$s');

	add_action('shutdown', function() use ($host_ip, $dev_url){
		$html = ob_get_contents();
		ob_end_clean();
		preg_match('#^\s*https?://#i', SITE_URL, $protocol);
		echo str_replace(SITE_URL, sprintf($dev_url, array_shift($protocol), $host_ip), $html);

		wp_ob_end_flush_all();
	});
}
