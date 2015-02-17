<?php

require_once('hooks.regular.php');
require_once('hooks.settings.php');
require_once('hooks.shortcodes.php');


/*
#	Admin DOM juice
add_action('admin_enqueue_scripts', function(){
	wp_enqueue_script('admin', THEME_DIR . '/src/js/admin.js');
});

add_action('admin_head', function(){
	wp_enqueue_style('admin', THEME_DIR . '/src/css/admin.css');
});
*/




if(!is_admin()){
	#	Hide links to the admin panel for users who aren't logged in.
	add_filter('wp_get_nav_menu_items', function($items){
	
		foreach($items as $key => $value)
			if('/wp-admin' === $value->url && !is_user_logged_in())
				unset($items[$key]);
	
		return $items;
	}, 5, 3);
}