<?php

/**
 * Walker for outputting an extremely simple, flattened list of anchor tags.
 *
 * This allows WordPress's nav-menu editor to manage lists of inline links
 * without forcing a developer to modify their styles/document structure
 * to use HTML lists.
 */
class Flattened_Walker extends Walker_Nav_Menu{

	var $trim_whitespace	=	TRUE;



	function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0){
		parent::start_el($output, $item, $depth, $args);

		# No filter for modifying ul/li tags, eh WordPress? Suit yourself, we'll do this the ugly way.
		$output	=	preg_replace('#<(?:ul|li)[^>]*>#', '', $output);

		# Strip whitespace between links unless told not to.
		if($this->trim_whitespace)
			$output	=	trim($output);
	}



	function end_el(&$output, $item, $depth = 0, $args = array()){
		parent::end_el($output, $item, $depth, $args);

		# Strip enclosing ul/li tags from our output.
		$output	=	preg_replace('#</(?:ul|li)>#', '', $output);

		# Trim whitespace if desired.
		if($this->trim_whitespace)
			$output	=	trim($output);
	}


	# Simple noop to prevent superclass's method appending unwanted whitespace
	function start_lvl(&$output, $depth = 0, $args = array()){ }


	# Snip off a tab that somehow sneaks itself before a link
	function end_lvl(&$output, $depth = 0, $args = array()){
		if($this->trim_whitespace)
			$output	=	preg_replace('#>\t+#', '>', $output);
	}
}


# Move any CSS classes that would've been applied to the <li> element to the <a> tag instead.
add_filter('nav_menu_link_attributes', function($attr, $item, $args, $depth){
	$walker	=	$args->walker;
	if($walker && is_a($walker, 'Flattened_Walker')){
		$classes		=	$item->classes;
		$attr['class']	=	join(' ', apply_filters('nav_menu_css_class', array_filter($classes), $item, $args, $depth));
	}

	return $attr;	
}, 9, 4);
