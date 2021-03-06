<?php
/**
 * Generic functions for WordPress development
 */


/**
 * Simple wrapper for registering a generic AJAX callback in WordPress (applicable irrespective of whether a user's logged-in or not).
 * 
 * @param string $name - Name of the AJAX action to register.
 * @param callback $func - Callback function to trigger.
 */
function on_ajax($name, $func){
	add_action('wp_ajax_'.$name,        $func);
	add_action('wp_ajax_nopriv_'.$name, $func);
}


/**
 * Return a list of URLs from a page's metadata field, with duplicated values removed.
 *
 * If there's more than one field value assigned to the post, their values are merged
 * before being split and filtered. Furthermore, any instances of the following constants
 * are replaced with their respective values:
 *
 *    - SITE_URL
 *    - THEME_DIR
 *    - THEME_CSS_DIR
 *    - THEME_JS_DIR
 *    - UPLOADS_DIR
 *
 * @param string $key - Name of the metadata field
 * @param int $page_id
 * @return array
 */
function meta_urls($key, $page_id = NULL){

	# Default to current page if not given an ID.
	$page_id = $page_id ?: get_the_ID();


	# Page has at least one metadata field with this name.
	if($meta = get_post_meta($page_id, $key)){

		# Merge each background field and strip empty lines and trailing/leading whitespace.
		$meta = preg_replace('#(?:^[\x20\t]+)|(?:\n\s*)(?=\n)|\s+$|\n\n#m', '', join(PHP_EOL, $meta));

		# Expand some substrings
		$str  = array(
			'THEME_DIR'     => THEME_DIR,
			'SITE_URL'      => SITE_URL,
			'UPLOADS_DIR'   => SITE_URL . '/wp-content/uploads',
			'THEME_CSS_DIR' => THEME_CSS_DIR,
			'THEME_JS_DIR'  => THEME_JS_DIR
		);
		$meta = str_replace(array_keys($str), array_values($str), $meta);

		# Split by newline and filter blank/duplicate values
		$meta = array_unique(array_filter(explode(PHP_EOL, $meta)));
	}

	return $meta ?: array();
}




/**
 * Extract JSON-encoded information buried inside a category's description field.
 * 
 * @param object $category - Category data object
 * @return array JSON metadata decoded as an associative array, also stored on the category object itself.
 */
function category_metadata(&$category){

	# We already decoded this. Nothing to do here!
	if(is_array($category->description)) return $category->description;


	# Description didn't appear to be JSON; assign the description string to a value and return an associative array.
	if(!($json = @json_decode($category->description, TRUE)))
		return array('description' => $category->description);


	# Replace each attachment ID with an actual, interactive image object.
	if(is_array($images = $json['images'])){

		foreach($images as $key => $value)
			if(is_numeric($value)){
				$images[$key] = array_merge(
					array('ID' => $value),
					wp_get_attachment_metadata($value)
				);
			}

		$json['images'] = $images;
	}

	# Store the decoded result on the category object.
	$category->description =& $json;
	return $json;
}



/**
 * Wrapper for WordPress's "get_post_custom" function that returns an array
 * using only the first index of each metadata property it reads.
 * 
 * Useful for retrieving a whole mass of metadata properties without needing
 * to repeatedly pass a zero-index to access the first/only value.
 * 
 * @param int $id Post ID, defaults to current post.
 * @return array
 */
function get_flattened_metadata($id = 0){
	$custom      = get_post_custom($id);
	$flattened   = array();
	foreach($custom as $key => $value)
		$flattened[$key] = is_array($value) ? $value[0] : $value;
	return $flattened;
}



/**
 * Return a randomly-generated alphanumeric ID for a DOM element guaranteed to be unique.
 * 
 * @param string $prefix String to prepend to the generated ID. Defaults to "id_".
 * @return string
 */
function unique_id($prefix = 'id_'){
	static $ids = array();

	$id = NULL;
	while($id === NULL || $ids[$id])
		$id = uniqid($prefix);

	$ids[$id] = TRUE;
	return $id;
}



$priority_shortcodes = array();
function add_priority_shortcode($tag, $func, $priority = 10){
	if(!is_callable($func)) return FALSE;

	add_filter('the_content', function($content) use($tag, $func){
		global $shortcode_tags;

		$yoink              = $shortcode_tags;
		$shortcode_tags     = array($tag => $func);
		$content            = do_shortcode($content);
		$shortcode_tags     = $yoink;
		return $content;
	}, $priority);
	return TRUE;
}



/**
 * Return the top-most ancestor in a page's hierarchy.
 *
 * @uses $post
 * @param int $id Page ID
 * @return mixed A WP page object
 */
function wp_top_page($id = NULL){
	global $post;
	$page     = empty($id) ? get_page($post) : (is_numeric($id) ? get_page($id) : $id);
	if(!$page) return NULL;
	$parent   = $page->post_parent;
	return $parent > 0 ? wp_top_page($parent) : $page;
}


/**
 * Return a month's name by integer.
 *
 * @uses $wp_locale
 * @param int $value Queried month. Defaults to current month if unsupplied.
 * @return string
 */
function wp_month_name($value = NULL){
	global $wp_locale;
	if(NULL === $value)
		$value = get_query_var('monthnum');
	return $wp_locale->get_month($value);
}



/**
 * Return the full path to a scaled attachment.
 *
 * @param int     $id    Attachment ID
 * @param string  $size  Size of the scaled attachment. Possible values are "thumbnail", "medium" or "large".
 * @return string The absolute URL to the scaled image, or NULL if none was found.
 */
function wp_image_by_size($id, $size = 'full'){
	$meta = wp_get_attachment_metadata($id, true);
	if(empty($meta)) return NULL;

	$uploads    = wp_upload_dir();
	$baseurl    = trailingslashit($uploads['baseurl']);

	# Requested size doesn't exist or was bigger than file's original size; return original.
	if(!$size = $meta['sizes'][$size])
		return $baseurl . $meta['file'];

	# Otherwise, return URL of attachment's downscaled version.
	else return $baseurl . ((($subdir = dirname($meta['file'])) && '.' != $subdir) ? trailingslashit($subdir) : '') . $size['file'];
}






/**
 * Retrieve the excerpt of a given post.
 *
 * Synonymous with get_the_excerpt(), except the function accepts a post ID as a parameter.
 *
 * @param int $id Post ID. If unsupplied, current post will be used instead.
 * @return string Excerpt of given post, or the trimmed content if none was explicitly declared
*/
function get_excerpt($id = 0){
	global $post;
	$id     = empty($id) ? $post->ID : $id;
	$p      = get_post($id);
	return has_excerpt($id) ? $p->post_excerpt : wp_trim_excerpt($p->post_content);
}



/**
 * Detect a mobile device by sniffing the user agent string.
 * 
 * @param string $ua - Overrides the HTTP_USER_AGENT header if set.
 * @link http://detectmobilebrowsers.com/
 * @version 2014-08-01
 * @return bool
 */
function is_mobile($ua = NULL){
	$ua = $ua ?: $_SERVER['HTTP_USER_AGENT'];
	return preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$ua)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($ua,0,4));
}



/**
 * Construct an HTML tag using the given name and attributes.
 *
 * If either $close is FALSE or a self-closing tag is specified,
 * the $inner_html parameter will have no effect.
 *
 * @param  string  $name        Tag name
 * @param  array   $attr        Array of attributes
 * @param  string  $inner_html  HTML to insert between the tags.
 * @param  bool    $close       Whether to append $inner_html and closing tag, or return only the opening tag.
 *
 * @return string  The compiled HTML tag, complete with attributes and wrapped content (if applicable)
 */	
function build_tag($name, $attr = NULL, $inner_html = '', $close = true){
	$att    = $out = '';
	$name   = strtolower($name);

	# Concatenate tag attributes
	if(is_array($attr)) foreach($attr as $key => $value)
		if(isset($value)) $att .= sprintf(' %1$s="%2$s"', $key, esc_attr($value));

	# Check if tag name matches a self-closing element
	$self_closing = array('area', 'base', 'basefont', 'br', 'col', 'frame', 'hr', 'img', 'input', 'link', 'meta', 'param');
	if(in_array($name, $self_closing))
		$out    = "<${name}${att} />";

	else $out   = '<' . $name . $att . '>'.($close ? ($inner_html . '</'.$name.'>') : '');

	return apply_filters('build_tag', $out, $name, $attr, $inner_html, $close);
}




/**
 * Truncate a string to a designated word count.
 *
 * @param string  $input    String to operate on.
 * @param int     $length   Desired word count. Must be positive.
 * @param string  $more     String appended to indicate cutoff to the reader (such as "..."). Blank by default.
 *
 * @return string Input wrapped to $length number of words.
 */
function word_limit($input, $length, $more = ''){
	$words = preg_split('/[\n\r\t ]+/', $input, $length+1, PREG_SPLIT_NO_EMPTY);

	if(count($words) > $length){
		array_pop($words);
		$text = implode(' ', $words) . $more;
	}
	else $text = implode(' ', $words);
	return $text;
}



/**
 * Prevent any occurrences of a designated string from being wrapped inside HTML tags.
 *
 * @param string  $input    Block of HTML to operate on
 * @param string  $search   String to push from any containing elements
 * @param bool    $before   Whether the unnested string gets inserted before or after the topmost containing element.
 *
 * @return string The block of HTML with any instances of $search pushed outside any containing HTML tags.
 */
function unnest($input, $search, $before = true){

	# Bail early if no matches were found
	if(stripos($input, $search) === FALSE)
		return $input;

	$re = '#(<([\w]+)[^>]*?>.*?)('.$search.')(.*?(<\/\2>))#ims';
	if($before) while(preg_match($re, $input, $r)) $input = str_replace($r[0], $r[3].$r[1].$r[4], $input);
		else    while(preg_match($re, $input, $r)) $input = str_replace($r[0], $r[1].$r[4].$r[3], $input);
	return $input;
}



/**
 * Invoke sprintf on a string multiple times, returning the formatted results as an indexed array.
 * 
 * Strings are formatted using the loop counter as the first argument, followed by any additional
 * vaues passed to the optional $args parameter. E.g., sprintf_repeat('#%1$s: %2$s', 10, array('name'))
 * will return ['#1: name', '#2: name'...] and so forth.
 * 
 * @param string  $str      String to repeatedly format
 * @param int     $mult     Number of times iterations
 * @param array   $args     Any additional arguments to pass to sprintf during the loop
 * @param int     $offset   Value to start counting from; use to specify a different starting point
 *
 * @return array
 */
function sprintf_repeat($str, $mult = 10, $args = NULL, $offset = 1){
	$output = array();
	$args   = $args ?: array();
	for($i = 0; $i < $mult; ++$i)
		$output[] = call_user_func_array('sprintf', array_merge(array($str, $i+$offset), $args));
	return $output;
}



/**
 * Exchange the values of two keys in an array.
 *
 * @param array        $array
 * @param string|int   $k1
 * @param string|int   $k2
 *
 * @return array Array with the targeted key values swapped
 */
function array_swap($array, $k1, $k2){
	$value1     = $array[$k1];
	$value2     = $array[$k2];
	$array[$k1] = $value2;
	$array[$k2] = $value1;
	return $array;
}



/**
 * Encode all standalone ampersands as named HTML entities.
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
 * Format a number of seconds into a human readable format (e.g., "3 weeks ago")
 *
 * @param int   $s         Seconds to format
 * @param bool  $maxyear   Whether units of measurement beyond years (decades, centuries, millenia) should be used
 *
 * @return string Number of seconds formatted as a human-readable interpretation (e.g., "3 weeks ago")
 */
function time_since($s, $maxyear = FALSE){
	/* Seconds */    if($s < 60)                         return sprintf(($s < 2 ? 'Just now'        : '%s seconds ago'),   $s);
	/* Minutes */    if(($t = $s / 60) < 60)             return sprintf(($t < 2 ? '%s minute ago'   : '%s minutes ago'),   floor($t));
	/* Hours */      if(($t = $t / 60) < 24)             return sprintf(($t < 2 ? 'An hour ago'     : '%s hours ago'),     floor($t));
	/* Days */       if(($t = $t / 24) < 7)              return sprintf(($t < 2 ? 'Yesterday'       : '%s days ago'),      floor($t));
	/* Weeks */      if(($t = $t / 7) < 4.345238)        return sprintf(($t < 2 ? 'Last week'       : '%s weeks ago'),     floor($t));
	/* Months */     if(($t = $t / 4.345238) < 12)       return sprintf(($t < 2 ? 'Last month'      : '%s months ago'),    floor($t));
	/* Years */      if(($t = $t / 12) < 10 || $maxyear) return sprintf(($t < 2 ? 'Last year'       : '%s years ago'),     floor($t));
	/* Decades */    if(($t = $t / 10) < 10)             return sprintf(($t < 2 ? 'A decade ago'    : '%s decades ago'),   floor($t));
	/* Centuries */  if(($t = $t / 10) < 10)             return sprintf(($t < 2 ? 'A century ago'   : '%s centuries ago'), floor($t));
	/* Millennia */  $t = $t / 10;                       return sprintf(($t < 2 ? 'A millenium ago' : '%s millennia ago'), floor($t));
}



/**
 * Ascertain if a string matches a recognised "Boolean-ish" value.
 *
 * Recognised values include "TRUE, FALSE, YES, NO, ON, OFF", and so forth. 
 *
 * @param mixed   $value   A string to match against a list of recognised boolean value names.
 * @param string  $more    An optional list of extra boolean strings to check. Useful for i18n.
 * @return bool
 */
function is_boolean($value, $more = ''){

	# Bail if provided an empty value.
	if(!$value) return FALSE;

	# Check for numerical values.
	if(is_numeric($value)) return intval(abs($value));

	$tokens = 'TRUE FALSE YES NO ON OFF ENABLED DISABLED ENABLE DISABLE' . ($more ? ' '.$more : '');
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



/**
 * Add Word-style fraction autoformatting, and semantic text markup where needed.
 *
 * @param string $text - An individual recipe ingredient
 * @return string
 */
function format_ingredient($text){

	# Replace fraction slashes with regular slashes.
	$text   = preg_replace('#(?<=\d)\x{2044}(?=\d)#mui', '/', $text);
	$fractions  =   array(
		'0/3'   =>  '↉',
		'1/10'  =>  '⅒',
		'1/2'   =>  '½',
		'1/3'   =>  '⅓',
		'1/4'   =>  '¼',
		'1/5'   =>  '⅕',
		'1/6'   =>  '⅙',
		'1/7'   =>  '⅐',
		'1/8'   =>  '⅛',
		'1/9'   =>  '⅑',
		'2/3'   =>  '⅔',
		'2/5'   =>  '⅖',
		'3/4'   =>  '¾',
		'3/5'   =>  '⅗',
		'3/8'   =>  '⅜',
		'4/5'   =>  '⅘',
		'5/6'   =>  '⅚',
		'5/8'   =>  '⅝',
		'7/8'   =>  '⅞'
	);

	# Autoformat vulgar fractions
	$from  = array_keys($fractions);
	$to    = array_values($fractions);
	$text  = str_replace($from, $to, $text);


	# Replace consecutive sequences of numbers + weight units with <abbr> tags enclosing the latter.
	$regex = '#(?<=\s|^)([\d' . implode('', $to) . ']+\s*)((?:[km]?gs?|oz|lbs?)\.?)(?=\s|$)#i';
	$text  = preg_replace_callback($regex, function($s){
		list($match, $amount, $unit_abbr) = $s;

		$pluralise = intval($amount) !== 1;
		$unit_names = array(
			'mg'    => 'milligram',
			'g'     => 'gram',
			'kg'    => 'kilogram',
			'oz'    => 'ounce',
			'lb'    => 'pound',
			'lbs'   => 'pound'
		);

		if(!$unit = $unit_names[preg_replace('#[s\.]+$#', '', strtolower($unit_abbr))])
			return $match;

		return sprintf('%1$s<abbr title="%2$s%3$s">%4$s</abbr>', $amount, $unit, $pluralise ? 's' : '', $unit_abbr);
	}, $text);

	# Use actual multiplication signs instead of the letter "x" where quantities are being expressed
	$text = preg_replace('#(?<=\s|^)(\d+\s*)x(?=\s)#', '$1&times;', $text);
	return $text;
}




/**
 * Recursively iterate through an array and replace any scalar values equating to
 * FALSE with a PHP-compatible string representation of their literal value.
 * 
 * Used by the trace/dump caveman debugging functions below. Not expected to be used anywhere else.
 * 
 * @param array $array - Top-level array to iterate over
 * @return $array - Array with modified descendants
 * @access private 
 */
function array_disambiguate_empty_values($array){
	if(!is_array($array) || $GLOBALS === $array) return $array;
	foreach($array as $key => $value)
		if(is_array($value))                        $array[$key]    = call_user_func('array_disambiguate_empty_values', $value);
		else if(is_bool($value) || $value === NULL) $array[$key]    = var_export($value, TRUE);
	return $array;
}



if(!function_exists('trace')){

/**
 * Caveman debugging function well-suited for irritable web developers.
 * Takes a variable number of arguments and spits their string representations into error_log.
 *
 * @return mixed - The first argument passed to the function
 */
function trace(){
	$spaces     = str_repeat(' ', 4);

	$divider    = '';
	# Custom log dividers
	if(defined('DEBUG_TRACE_DIVIDER'))
		$divider = DEBUG_TRACE_DIVIDER;


	# Optional file to write traced data to (otherwise uses error_log's default)
	$log_type = 0;
	$log_path = '';
	if(defined('DEBUG_TRACE_PATH')){
		$log_type = 3;
		$log_path = DEBUG_TRACE_PATH;
	}

	$output = '';
	foreach(func_get_args() as $a)
		$output .= str_replace($spaces, "\t", print_r(((is_bool($a) || $a === NULL) ? var_export($a, TRUE) : call_user_func('array_disambiguate_empty_values', $a)), TRUE)) . "\n\n";

	# Ensure there's only one trailing newline
	$output = preg_replace('#\n+$#ms', "\n", $output);

	# Append the divider if there was one
	if($divider)
		$output .= $divider;

	error_log($output, $log_type, $log_path);
	return func_get_arg(0);
}
}


if(!function_exists('dump')){

/**
 * An even uglier variant of the trace() function. Doesn't even bother spitting the traced values
 * into an error_log, instead opting to shove them onto the page and cancel script execution. 
 */
function dump(){
	$spaces = str_repeat(' ', 4);
	$output = '';
	foreach(func_get_args() as $a)
		$output .= preg_replace('#(</?)pre>#i', '$1 pre >', str_replace($spaces, "\t", print_r(call_user_func('array_disambiguate_empty_values', $a), TRUE)));
	!headers_sent() ? header('Content-Type: text/plain; charset=UTF-8') : ($output = '<pre>' . $output . '</pre>');
	echo $output;
	exit;
}

}
