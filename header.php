<?php
global $css_version, $js_version;

# Enqueue styles
if(USE_MINIFIED)
	wp_enqueue_style('main',    THEME_DIR . '/src/min/min.css',     NULL, $css_version);

else{
	wp_enqueue_style('fonts',   THEME_DIR . '/src/css/fonts.css',   NULL,               $css_version);
	wp_enqueue_style('global',  THEME_DIR . '/src/css/global.css',  array('fonts'),     $css_version);
	wp_enqueue_style('main',    THEME_DIR . '/src/css/main.css',    array('global'),    $css_version);
}

wp_enqueue_style('ie-lte9',     THEME_DIR . '/src/css/ie.lte-9.css', array('main'),     $css_version);
wp_style_add_data('ie-lte9',    'conditional', 'lte IE 9');


# Enqueue scripts
wp_enqueue_script('main',   THEME_DIR . '/src/js/main.js', NULL, $js_version, TRUE);

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?><?= html_class(); ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo('charset'); ?>" />
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
<meta name="viewport" content="initial-scale=1, minimum-scale=1, user-scalable=no" />

<!--[if lt IE 9]><script type="text/javascript" src="//html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
<title><?= title(); ?></title>
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />

<?php wp_head(); ?> 
</head>



<body <?php body_class(); ?>>
