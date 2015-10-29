<?php


/**
 * Return the HTML class attribute for the document's <html> element.
 * 
 * Performs a similar role as WordPress's "body_class" function, except
 * the generated attribute is returned instead of displayed.
 * 
 * @uses $html_classes
 * @param string|array $classes One or more classes to add to the class list.
 * @return string
 */
function html_class($classes = ''){
	global $html_classes;
	if(!is_array($html_classes)) return;

	if(!is_array($classes))
		$classes = explode(' ', $classes);

	$attr     = trim(join(' ', array_unique(array_merge($html_classes, array_map('sanitize_html_class', $classes)))));
	$output   = $attr ? ' class="'.$attr.'"' : '';
	return apply_filters('html_class', $output, $classes);
}



/**
 * Return an array of breadcrumb objects.
 *
 * Each crumb is expressed as an associative array with the following properties:
 *    name:     Human-readable form of the breadcrumb
 *    url:      Breadcrumb's permalink
 *    object:   Original WP object that the name/url properties were sourced from (usually a post or taxonomy object).
 * 
 * @return array
 */
function get_breadcrumbs(){
	global $post;
	$crumbs = array();

	# These are search results that're being displayed
	if(is_search()) $crumbs[] = array(
		'name'  => 'Search results',
		'url'   => ''
	);


	# Make sure the $post global is ready
	else if($post){

		if(is_singular()) $crumbs[] = array(
			'name'    => $post->post_title,
			'url'     => get_permalink($post->id),
			'object'  => $post
		);


		# Current post's of a hierarchial nature, such as a page. Display its ancestors.
		$post_type = get_post_type_object($post->post_type);
		if($post_type->hierarchical){
			$ancestry      = get_post_ancestors($post->ID);
			$count         = count($ancestry);

			for($i = 0; $i < $count; ++$i){
				$ancestor  = get_post($ancestry[$i]);
				$crumbs[]  = array(
					'name'   => $ancestor->post_title,
					'url'    => get_permalink($ancestor->ID),
					'object' => $ancestor
				);
			}
		}


		# Otherwise, it's something else entirely.
		else{
			$taxonomies = get_post_taxonomies($post->ID);

			# There's one or more taxonomies assigned to this post object.
			if($count = count($taxonomies)){

				for($i = 0; $i < $count; ++$i){
					# TODO: Include taxonomy's ancestors in breadcrumb chain?
					$taxonomy       = get_taxonomy($taxonomies[$i]);
					$taxonomies[$i] = $taxonomy;
				}

				# In the case of multiple taxonomies, we'd ideally use the one that has an ancestry. Since that's
				# not implemented yet, we'll just fly with using the first taxonomy found associated with a post.
				$terms = get_the_terms($post->ID, $taxonomies[0]->name);


				# Bail if there aren't any terms 
				if($terms){

					# We might have multiple terms assigned, we'll just use the first.
					$term     = reset($terms);
					$crumbs[] = array(
						'name'      => $term->name,
						'url'       => get_term_link($term),
						'object'    => $term
					);

					# If this is a custom post type, include its base in the breadcrumb trail.
					if(!$post_type->_builtin) $crumbs[] = array(
						'name'     => $post_type->labels->name,
						'url'      => home_url('/'.($taxonomies[0]->rewrite['slug'])),
						'object'   => $post_type
					);
				}
			}

			# No taxonomies found, so we'll just use the post type itself (assuming it's set to be public)
			else if($post_type->show_ui)
				$crumbs[]  = array(
					'name'      => $post_type->labels->name,
					'url'       => home_url('/' . $post_type->rewrite['slug']),
					'object'    => $post_type
				);
		}
	}

	return array_reverse($crumbs);
}



/** Display an HTML breadcrumb trail. */
function breadcrumbs(){
	$crumbs = get_breadcrumbs();
?> 
		<p class="breadcrumbs" itemscope="itemscope" itemtype="http://data-vocabulary.org/Breadcrumb">
			<a itemprop="url" href="<?= SITE_URL ?>"><span itemprop="title">Home</span></a><?php foreach($crumbs as $crumb): ?> &gt;
			<a itemprop="url" href="<?= $crumb['url'] ?>"><span itemprop="title"><?= $crumb['name'] ?></span></a><?php endforeach; ?> 
		</p><?php
}




/**
 * Display navigation to next/previous pages where applicable.
 * 
 * Supports the WP_PageNavi and WP_Paginate plugins.
 *
 * @uses $wp_query
 */
function pagination(){
	global $wp_query;

	if($wp_query->max_num_pages < 2) return;


	/**	WP-PageNavi Plugin */
	if(function_exists('wp_pagenavi')): ?> 
		<div class="pagenav">
			<?php wp_pagenavi(); ?> 
		</div><?php


	/**	WP-Paginate Plugin */
	elseif(function_exists('wp_paginate')): ?> 
		<div class="pagenav wp-paginate">
			<?php wp_paginate(); ?> 
		</div><?php


	/** Default Theme Navigation */
	else: ?> 
		<div class="pagenav default">
			<?= get_previous_posts_link('Back') ?: '<a class="prev">Back</a>'; ?> 
			<?= get_next_posts_link('Next')     ?: '<a class="next">Next</a>'; ?> 
		</div><?php

	endif;
	echo PHP_EOL;
}
