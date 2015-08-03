<?php

define('IS_MOBILE',				is_mobile());
define('IN_DEVELOPMENT',		in_array($_SERVER['SERVER_NAME'], array('localhost', 'theonlinecircle.net.au')));
define('USE_MINIFIED',			IN_DEVELOPMENT ? FALSE : get_option('use_minified_assets'));

define('THEME_DIR',				get_bloginfo('template_directory'));
define('CHARSET',				get_bloginfo('charset'));
define('SITE_URL',				get_bloginfo('url'));
define('SITE_TITLE',			get_bloginfo('title'));
define('SITE_DESCRIPTION',		get_bloginfo('description'));
define('NEWS_PAGE_ID',			get_option('page_for_posts'));
define('MCE_PLUGIN_DIR',		'/src/js/mce/tiny_mce/plugins');
define('T_DOMAIN',				'scratch');


if(NEWS_PAGE_ID){
	$news_page = get_page($id = NEWS_PAGE_ID);
	define('NEWS_PAGE_URL',		SITE_URL . '/' . get_page_uri(NEWS_PAGE_ID));
	define('NEWS_PAGE_TITLE',	apply_filters('get_the_title', $news_page->post_title));
	define('NEWS_PAGE_CONTENT',	apply_filters('get_the_content', $news_page->post_content));
}
else{
	define('NEWS_PAGE_URL',		SITE_URL . '/');
	define('NEWS_PAGE_TITLE',	SITE_TITLE);
	define('NEWS_PAGE_CONTENT',	'');
}

#	Asset directories
define('THEME_CSS_DIR',			THEME_DIR . '/src/' . (USE_MINIFIED ? 'min' : 'css'));
define('THEME_JS_DIR',			THEME_DIR . '/src/' . (USE_MINIFIED ? 'min' : 'js'));


define('META_INCLUDE_CSS',		'include_css');
define('META_INCLUDE_JS',		'include_js');
define('META_ADDITIONAL_CSS',	'additional_css');
define('META_ADDITIONAL_JS',	'additional_js');
define('META_ADDITIONAL_HEAD',	'additional_html_head');
define('META_ADDITIONAL_FOOT',	'additional_html_foot');
define('META_HIDE_TITLE',		'hide_title');
define('META_CUSTOM_TITLE',		'custom_title');
define('META_FOOTER_TAGLINE',	'footer_tagline');
define('META_SEARCH_EXCLUDE',	'exclude_from_search_results');
define('OPTION_404_PAGE',		'options_page_404');


#	Job-specific
