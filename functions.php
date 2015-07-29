<?php

require_once 'inc/utils.php';
require_once 'inc/constants.php';
require_once 'inc/classes/tinymce.php';
require_once 'inc/classes/walkers.php';
require_once 'inc/widgets/snippet.php';
require_once 'inc/hooks.php';
require_once 'inc/tags.php';


$html_classes	=	array('no-js');
$body_classes	=	array();
#$mce_plugins	=	new MCEPlugins('sectionbreak');


#	Pointer to the top-most page in the currently-viewed page's ancestry.
if($top_page = wp_top_page()){
	define('TOP_PAGE_TITLE',	$top_page->post_title);
	define('TOP_PAGE_ID',		$top_page->ID);
}



#	Configure theme defaults and register WordPress features.
add_action('after_setup_theme', function(){

	add_editor_style();							#	Enables editor-style.css to style the visual editor.
	add_theme_support('post-thumbnails');		#	Enables featured images/post thumbnails.
	add_theme_support('automatic-feed-links');	#	Add default posts and comments RSS feed links to head
	#add_image_size('name', 360, 277);			#	Add a custom image size.


	#	Add support for a custom background.
	add_theme_support('custom-background', array(
		'default-color'	=>	'fff'
	));


	#	Define our menus.
	register_nav_menus(array(
		'primary'	=>	'Primary Navigation',
		'footer'	=>	'Footer Links'
	));
});



#	Register widget sidebars
add_action('widgets_init', function(){

	#	Default sidebar
	register_sidebar(array(
		'name'			=>	__('Sidebar Name', T_DOMAIN),
		'id'			=>	'sidebar',
		'description'	=>	'Descriptive text to be placed here',
		'before_widget'	=>	'',
		'after_widget'	=>	'',
		'before_title'	=>	'',
		'after_title'	=>	''
	));
});


/**	Load theme's text domain 
load_theme_textdomain(T_DOMAIN, TEMPLATEPATH . '/languages');*/
