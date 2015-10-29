<?php

# Option/metadata names
define('META_ADDITIONAL_CSS',   'additional_css');
define('META_ADDITIONAL_FOOT',  'additional_html_foot');
define('META_ADDITIONAL_HEAD',  'additional_html_head');
define('META_ADDITIONAL_JS',    'additional_js');
define('META_CUSTOM_TITLE',     'custom_title');
define('META_HIDE_TITLE',       'hide_title');
define('META_INCLUDE_CSS',      'include_css');
define('META_INCLUDE_JS',       'include_js');
define('META_SEARCH_EXCLUDE',   'exclude_from_search_results');
define('OPTION_404_PAGE',       'options_page_404');
define('OPTION_CSS_VERSION',    'css_version');
define('OPTION_JS_VERSION',     'js_version');
define('OPTION_USE_MINIFIED',   'use_minified_assets');


# Cached settings
define('IS_MOBILE',             is_mobile());
define('NEWS_PAGE_ID',          get_option('page_for_posts'));
define('SITE_URL',              get_bloginfo('url'));
define('USE_MINIFIED',          get_option(OPTION_USE_MINIFIED));


# Theme directories
define('THEME_DIR',             get_bloginfo('template_directory'));
define('THEME_CSS_DIR',         THEME_DIR . '/src/' . (USE_MINIFIED ? 'min' : 'css'));
define('THEME_JS_DIR',          THEME_DIR . '/src/' . (USE_MINIFIED ? 'min' : 'js'));



# Job-specific
