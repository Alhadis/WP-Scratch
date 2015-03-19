<?php


	/** Enqueue Styles */
	if(USE_MINIFIED)
		wp_enqueue_style('main',		THEME_DIR . '/src/css/min.css',			NULL, $css_version, 'all');

	else{
		wp_enqueue_style('fonts',		THEME_DIR . '/src/css/fonts.css',		NULL,				NULL,	'all');
		wp_enqueue_style('global',		THEME_DIR . '/src/css/global.css',		array('fonts'),		NULL,	'all');
		wp_enqueue_style('main',		THEME_DIR . '/src/css/main.css',		array('global'),	NULL,	'all');
	}

	wp_enqueue_style('ie-lte8',		THEME_DIR . '/src/css/compat/ie.lte-8.css',	array('main'), NULL);
	wp_style_add_data('ie-lte8',	'conditional', 'lte IE 8');


	/** Enqueue Scripts */
	wp_enqueue_script('main',	THEME_DIR . '/src/js/main.js', NULL, $js_version, TRUE);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?><?= html_class(); ?>>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?= CHARSET ?>" />
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
<meta name="viewport" content="initial-scale=1, minimum-scale=1, user-scalable=no" />

<!--[if lt IE 9]><script type="text/javascript" src="//html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
<title><?= title(); ?></title>
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />

<?php wp_head(); ?> 
</head>



<body <?php body_class(); ?>>
