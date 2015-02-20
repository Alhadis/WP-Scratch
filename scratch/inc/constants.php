<?php

	define('JQUERY_VERSION',		'1.11.1');
	define('JQUERY_UI_VERSION',		'1.11.1');
	define('IS_MOBILE',				is_mobile());
	define('IN_DEVELOPMENT',		in_array($_SERVER['SERVER_NAME'], array('localhost', 'theonlinecircle.net.au')));
	define('USE_MINIFIED',			!IN_DEVELOPMENT);

	define('THEME_DIR',				get_bloginfo('template_directory'));
	define('CHARSET',				get_bloginfo('charset'));
	define('PAGE_FOR_POSTS',		get_option('page_for_posts'));
	define('BLOG_URL',				get_bloginfo('url'));
	define('BLOG_TITLE',			get_bloginfo('title'));
	define('BLOG_DESCRIPTION',		get_bloginfo('description'));
	define('META_SEP',				' <span class="meta-sep">|</span> ');
	define('MCE_PLUGIN_DIR',		'/src/js/mce/tiny_mce/plugins');
	define('T_DOMAIN',				'scratch');


	if(PAGE_FOR_POSTS){
		$news_page		=	get_page($id = PAGE_FOR_POSTS);
		define('NEWS_PAGE_URL',		BLOG_URL . '/' . get_page_uri(PAGE_FOR_POSTS));
		define('NEWS_PAGE_TITLE',	apply_filters('get_the_title', $news_page->post_title));
		define('NEWS_PAGE_CONTENT',	apply_filters('get_the_content', $news_page->post_content));
	}
	else{
		define('NEWS_PAGE_URL',		BLOG_URL . '/');
		define('NEWS_PAGE_TITLE',	BLOG_TITLE);
		define('NEWS_PAGE_CONTENT',	'');
	}



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
