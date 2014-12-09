<?php
/**
 * Generic Functions for WordPress Development
 *
 * @author John Gardner <gardnerjohng@gmail.com>
 * @version 1.0.2
 */


/**
 * Simple wrapper for registering a generic AJAX callback in WordPress (applicable irrespective of whether a user's logged-in or not).
 * 
 * @param string $name - Name of the AJAX action to register.
 * @param callback $func - Callback function to trigger.
 */
function on_ajax($name, $func){
	add_action('wp_ajax_'.$name,		$func);
	add_action('wp_ajax_nopriv_'.$name,	$func);
}






/**
 * Extracts JSON-encoded information buried inside a category's description field.
 * 
 * @param object $category - Category data object
 * @return array JSON metadata decoded as an associative array, also stored on the category object itself.
 */
function category_metadata(&$category){

	#	We already decoded this. Nothing to do here!
	if(is_array($category->description)) return $category->description;


	#	Description didn't appear to be JSON; assign the description string to a value and return an associative array. 
	if(!($json = @json_decode($category->description, TRUE)))
		return array('description'	=>	$category->description);


	#	Replace each attachment ID with an actual, interactive image object.
	if(is_array($images = $json['images'])){

		foreach($images as $key => $value)
			if(is_numeric($value)){
				$images[$key] =	array_merge(
					array('ID' => $value),
					wp_get_attachment_metadata($value)
				);
			}

		$json['images']	=	$images;
	}

	#	Store the decoded result on the category object.
	$category->description	=&	$json;
	return $json;
}



/*
 * Returns a randomly-generated alphanumeric ID for a DOM element guaranteed to be unique.
 * 
 * @param string $prefix String to prepend to the generated ID. Defaults to 'id_'.
 * @return string
 */
function unique_id($prefix = 'id_'){
	static $ids	=	array();

	$id	=	NULL;
	while($id === NULL || $ids[$id])
		$id	=	uniqid($prefix);

	$ids[$id]	=	TRUE;
	return $id;
}



$priority_shortcodes	=	array();
function add_priority_shortcode($tag, $func, $priority = 10){
	if(!is_callable($func)) return FALSE;

	add_filter('the_content', function($content) use($tag, $func){
		global $shortcode_tags;

		$yoink				=	$shortcode_tags;
		$shortcode_tags		=	array($tag => $func);
		$content			=	do_shortcode($content);
		$shortcode_tags		=	$yoink;
		return $content;
	}, $priority);
	return TRUE;
}



/**
 * Returns the top-most ancestor in a page's hierarchy.
 *
 * @uses $post
 * @param int $id Page ID
 * @return mixed A WP page object.
 */
function wp_top_page($id = NULL){
	global $post;
	$p		=	empty($id) ? get_page($post) : (is_numeric($id) ? get_page($id) : $id);
	$par	=	$p->post_parent;
	trace($par);
	return $par > 0 ? wp_top_page($par) : $p;
}
	
	
/**
 * Returns a month's name by integer.
 *
 * @uses $wp_locale
 * @param int $value Queried month. Defaults to current month if unsupplied.
 * @return string
 */
function wp_month_name($value = NULL){
	global $wp_locale;
	if(NULL === $value)
		$value	=	get_query_var('monthnum');
	return $wp_locale->get_month($value);
}


/**
 * Returns the full path to a scaled attachment.
 *
 * @param int $id Attachment ID
 * @param string $size Size of the scaled attachment. Possible values are "thumbnail", "medium" or "large".
 * @return string The absolute URL to the scaled image, or NULL if none was found.
 */
function wp_image_by_size($id, $size = 'full'){
	$meta	=	wp_get_attachment_metadata($id, true);
	if(empty($meta)) return NULL;
	
	$uploads	=	wp_upload_dir();
	$baseurl	=	trailingslashit($uploads['baseurl']);
	
	/**	Requested size doesn't exist or was bigger than file's original size; return original. */
	if(!$size = $meta['sizes'][$size])
		return $baseurl . $meta['file'];
	
	/**	Otherwise, return URL of attachment's downscaled version. */
	else return $baseurl . ((($subdir = dirname($meta['file'])) && '.' != $subdir) ? trailingslashit($subdir) : '') . $size['file'];
}



/**
 * Reregisters jQuery libraries with the newest versions hosted on Google's CDN.
 *
 * @param string $jquery		jQuery version string
 * @param string $jquery_ui		jQuery UI version string
 * @param bool $in_footer		If TRUE, scripts will be queued to load in the site's footer.
 */
function wp_upgrade_scripts($jquery, $jquery_ui, $in_footer = TRUE){
	# return NULL;
	if(!is_admin()){
		wp_deregister_script('jquery');
		wp_register_script('jquery', '//ajax.googleapis.com/ajax/libs/jquery/'.$jquery.'/jquery.min.js', false, NULL, $in_footer);
	}

	# Upgrade jQuery UI
	wp_deregister_script('jquery-ui-core');
	wp_register_script('jquery-ui-core', '//ajax.googleapis.com/ajax/libs/jqueryui/'.$jquery_ui.'/jquery-ui.min.js', array('jquery'), NULL, $in_footer);

	$ui_scripts	=	array(
		'jquery-ui-tabs',
		'jquery-ui-sortable',
		'jquery-ui-draggable',
		'jquery-ui-droppable',
		'jquery-ui-selectable',
		'jquery-ui-resizable',
		'jquery-ui-dialog'
	);

	foreach($ui_scripts as $ui_script){
		wp_deregister_script($ui_script);
		wp_register_script($ui_script, '', array('jquery-ui-core'), NULL, $in_footer);
	}	unset($ui_script);
}






/**
 * Gets the excerpt of the given post.
 *
 * Synonymous with get_the_excerpt(), except the function accepts a post ID as a parameter.
 *
 * @param int $id Post ID. If unsupplied, current post will be used instead.
 * @return string Excerpt of given post, or the trimmed content if none was explicitly declared
*/
function get_excerpt($id = 0){
	global $post;
	$id		=	empty($id) ? $post->ID : $id;
	$p		=	get_post($id);
	return has_excerpt($id) ? $p->post_excerpt : wp_trim_excerpt($p->post_content);
}



/**
 * Detects a mobile device by sniffing the user agent string.
 * 
 * @param string $ua - Overrides the HTTP_USER_AGENT header if set.
 * @link http://detectmobilebrowsers.com/
 * @version 2014-08-01
 * @return bool
 */
function is_mobile($ua = NULL){
	$ua		=	$ua ?: $_SERVER['HTTP_USER_AGENT'];
	return preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$ua)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($ua,0,4));
}



/**
 * Constructs an HTML tag using the given name and attributes.
 *
 * If either $close is FALSE or a self-closing tag is specified,
 * the $inner_html parameter will have no effect.
 *
 * @param string $name Tag name
 * @param array $attr Array of attributes
 * @param string $inner_html HTML to insert between the tags.
 * @param bool $close Whether to append the $inner_html and closing tag, or return just the opening tag.
 *
 * @return string The compiled HTML tag, complete with attributes and wrapped content (if applicable)
 */	
function build_tag($name, $attr = NULL, $inner_html = '', $close = true){
	$att	=	$out	=	'';
	$name	=	strtolower($name);
	
	/** Concatenate tag attributes */
	if(is_array($attr)) foreach($attr as $key => $value)
		if(isset($value)) $att	.=	sprintf(' %1$s="%2$s"', $key, esc_attr($value));
	
	/**	Check if tag name matches a self-closing element */
	if(in_array($name, array('area', 'base', 'basefont', 'br', 'col', 'frame', 'hr', 'img', 'input', 'link', 'meta', 'param')))
		$out	=	'<' . $name . $att . ' />';
	
	else
		$out	=	'<' . $name . $att . '>'.($close ? ($inner_html . '</'.$name.'>') : '');
	
	return apply_filters('build_tag', $out, $name, $attr, $inner_html, $close);
}




/**
 * Truncates a string to a specified number of words.
 *
 * @param string $input String to operate on.
 * @param int $length Desired word count. Must be positive.
 * @param string $more String appended to indicate cutoff to the reader (such as "..."). Blank by default.
 *
 * @return string Input wrapped to $length number of words.
 */
function word_limit($input, $length, $more = ''){
	$words				=	preg_split('/[\n\r\t ]+/', $input, $length+1, PREG_SPLIT_NO_EMPTY);
	if(count($words) > $length){
		array_pop($words);
		$text	=	implode(' ', $words) . $more;
	}
	else $text	=	implode(' ', $words);
	return $text;
}




/**
 * Prevents any occurances of a designated string value from being wrapped inside HTML tags.
 *
 * @param string $input Block of HTML to operate on
 * @param string $search String to push from any containing elements
 * @param bool $before Whether the unnested string gets inserted before or after the topmost containing element.
 *
 * @return string The block of HTML with any instances of $search pushed outside any containing HTML tags.
 */
function unnest($input, $search, $before = true){

	/* Bail early if no matches were found */
	if(stripos($input, $search) === FALSE)
		return $input;

	$re		=	'#(<([\w]+)[^>]*?>.*?)('.$search.')(.*?(<\/\2>))#ims';
	if($before)	while(preg_match($re, $input, $r))	$input	=	str_replace($r[0], $r[3].$r[1].$r[4], $input);
		else	while(preg_match($re, $input, $r))	$input	=	str_replace($r[0], $r[1].$r[4].$r[3], $input);
	return $input;
}



/**
 * Invokes sprintf on a string multiple times, returning the formatted results as an indexed array.
 * 
 * Strings are formatted using the loop counter as the first argument, followed by any additional
 * vaues passed to the optional $args parameter. E.g., sprintf_repeat('#%1$s: %2$s', 10, array('name'))
 * will return ['#1: name', '#2: name'...] and so forth.
 * 
 * @param string $str String to repeatedly format.
 * @param int $mult Number of times iterations.
 * @param array $args Any additional arguments to pass to sprintf during the loop.
 * @param int $offset Value to start counting from. Used for specifying different starting points.
 * @return array
 */
function sprintf_repeat($str, $mult = 10, $args = NULL, $offset = 1){
	$output	=	array();
	$args	=	$args ?: array();
	for($i = 0; $i < $mult; ++$i)
		$output[]	=	call_user_func_array('sprintf', array_merge(array($str, $i+$offset), $args));
	return $output;
}



/**
 * Exchanges the values of two keys in an array.
 *
 * @param array $array Array to target
 * @param string|int $arg1
 * @param string|int $arg2
 * @return array Array with the targeted key values swapped
 */
function array_swap($array, $arg1, $arg2){
	$a1	=	$array[$arg1];
	$a2	=	$array[$arg2];
	$array[$arg1]	=	$a2;
	$array[$arg2]	=	$a1;
	return $array;
}



/**
 * Encodes all standalone ampersands as named HTML Entities
 *
 * Useful for preventing validation errors and other potentially volatile
 * behaviour without risk of double-encoding previously encoded entities.
 *
 * @author alif
 * @link http://www.php.net/manual/en/function.htmlspecialchars.php#96159
 *
 * @return string A string with standalone ampersands encoded.
 */
function safeamp($string){
	return preg_replace('~&(?![A-Za-z0-9#]{1,7};)~', '&amp;', $string);
}




/**
 * Formats a number of seconds into a human readable format (e.g., "3 weeks ago")
 *
 * @param int $secs Seconds to format
 * @param bool $maxyear Whether units of measurement beyond years (decades, centuries, millenia) should be used
 * @return string Number of seconds formatted as a human-readable interpretation (e.g., "3 weeks ago")
 */
function time_since($secs, $maxyear = FALSE){
	/* Seconds */		if($secs < 60)								return sprintf(($secs < 2 ? 'Just now' : '%s seconds ago'), $secs);
	/* Minutes */		if(($time = $secs / 60) < 60)				return sprintf(($time < 2 ? '%s minute ago' : '%s minutes ago'), floor($time));
	/* Hours */			if(($time = $time / 60) < 24)				return sprintf(($time < 2 ? 'An hour ago' : '%s hours ago'), floor($time));
	/* Days */			if(($time = $time / 24) < 7)				return sprintf(($time < 2 ? 'Yesterday' : '%s days ago'), floor($time));
	/* Weeks */			if(($time = $time / 7) < 4.345238)			return sprintf(($time < 2 ? 'Last week' : '%s weeks ago'), floor($time));
	/* Months */		if(($time = $time / 4.345238) < 12)			return sprintf(($time < 2 ? 'Last month' : '%s months ago'), floor($time));
	/* Years */			if(($time = $time / 12) < 10 || $maxyear)	return sprintf(($time < 2 ? 'Last year' : '%s years ago'), floor($time));
	/* Decades */		if(($time = $time / 10) < 10)				return sprintf(($time < 2 ? 'A decade ago' : '%s decades ago'), floor($time));
	/* Centuries */		if(($time = $time / 10) < 10)				return sprintf(($time < 2 ? 'A century ago' : '%s centuries ago'), floor($time));
	/* Millennia */		$time	=	$time / 10;						return sprintf(($time < 2 ? 'A millenium ago' : '%s millennia ago'), floor($time));
}





/**
 * Ascertains if a string matches a recognised "Boolean-ish" value.
 *
 * Recognised values include "TRUE, FALSE, YES, NO, ON, OFF", and so forth. 
 *
 * @param mixed $value - A string to match against a list of recognised boolean value names.
 * @param string $more - An optional list of extra boolean strings to check. Useful for i18n.
 * @return bool
 */
function is_boolean($value, $more = ''){

	#	Bail if provided an empty value.
	if(!$value)	return FALSE;

	#	Check for numerical values.
	if(is_numeric($value)) return intval(abs($value));

	$tokens =	'TRUE FALSE YES NO ON OFF ENABLED DISABLED ENABLE DISABLE' . ($more ? ' '.$more : '');
	return in_array(strtoupper($value), explode(' ', $tokens));
}



/**
 * Unicode-compatible version of PHP's chr function.
 * 
 * @param int $ascii The Unicode codepoint of the character to generate.
 * @return string
 */
function mb_chr($ascii){
	return mb_convert_encoding('&#' . intval($ascii) . ';', 'UTF-8', 'HTML-ENTITIES');
}



if(!function_exists('trace')){

/**
 * Caveman debugging function well-suited for irritable web developers.
 * Takes a variable number of arguments and spits their string representations into error_log.
 */
function trace(){
	$spaces	=	str_repeat(' ', 4);
	foreach(func_get_args() as $a)
		error_log(str_replace($spaces, "\t", print_r($a, true)));
}
}


if(!function_exists('dump')){

/**
 * An even uglier variant of the trace() function. Doesn't even bother spitting the traced values
 * into an error_log, instead opting to shove them onto the page and cancel script execution. 
 */
function dump(){
	$spaces	=	str_repeat(' ', 4);
	
	$output	=	'';
	foreach(func_get_args() as $a)
		$output .= preg_replace('#(</?)pre>#i', '$1 pre >', str_replace($spaces, "\t", print_r($a, TRUE)));


	!headers_sent() ? header('Content-Type: text/plain') : ($output = '<pre>' . $output . '</pre>');
	echo $output;
	exit;
}

}