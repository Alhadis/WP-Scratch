<?php

/**
 * TODO:
 * -	Refactor this crap into a CacheBuster class.
 * -	Reset timestamp options if cachebust is called with reset as a parameter.
 */


define('OPTION_CSS_VERSION',	'theme_css_version');
define('OPTION_JS_VERSION',		'theme_js_version');


function update_version_if_modified($type){
	$type				=	strtolower($type);
	$option_version		=	sprintf('theme_%1$s_version',	$type);
	$option_modified	=	sprintf('theme_%1$s_modified',	$type);


	$last_modified		=	0;
	$modified			=	intval(get_option($option_modified));
	$files				=	array_merge(
		rscandir(TEMPLATEPATH.'/src/'.$type),
		rscandir(TEMPLATEPATH.'/src/min')
	);

	#	Check if any files have been modified recently.
	foreach($files as $file){
		if(preg_match('#.\.'.$type.'$#i', $file))
			$last_modified	=	max(intval(filemtime($file)), $modified);
	}

	#	At least one file appeared to be newer than the modification time stored in the database.
	if($last_modified != $modified){
		$version	=	get_option($option_version, 0);
		update_option($option_modified, $last_modified);
		update_option($option_version, $version+1);
		return $version+1;
	}
	return FALSE;
}

on_ajax('cachebust', function(){
	$message =	'';

	if($reset = strtolower($_GET['reset'] ?: '')){
		$reset		=	'all' === $reset ? array('css', 'js') : explode(',', $reset);
		$css_reset	=	in_array('css', $reset)	? update_option(OPTION_CSS_VERSION, FALSE)	: 0;
		$js_reset	=	in_array('js', $reset)	? update_option(OPTION_JS_VERSION, FALSE)	: 0;

		if(!$css_reset && !$js_reset)	$message	=	'No version change executed or necessary.';
		else if($css_reset)				$message	=	'CSS version reset.';
		else if($js_reset)				$message	=	'JS version reset.';
		wp_die($message . PHP_EOL);
	}


	$css_version	=	update_version_if_modified('css');
	$js_version		=	update_version_if_modified('js');
	
	if(!$css_version && !$js_version)
		$message	=	'No JS or CSS modifications detected.' . PHP_EOL;

	if($css_version)	$message	=	'CSS version updated to ' . $css_version . '.'.PHP_EOL;
	if($js_version)		$message	.=	'JS version updated to ' . $js_version . '.'.PHP_EOL;
	wp_die($message);
});

