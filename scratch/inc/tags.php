<?php


/**
 * Returns the HTML class attribute for the document's <html> element.
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
		$classes	=	explode(' ', $classes);

	$attr	=	trim(join(' ', array_unique(array_merge($html_classes, array_map('sanitize_html_class', $classes)))));
	$output	=	$attr ? ' class="'.$attr.'"' : '';
	return apply_filters('html_class', $output, $classes);
}


/**
 * Returns an array of breadcrumb objects, each expressed as an associative array with the following properties:
 * 	• name:		Human-readable form of the breadcrumb
 *	• url:		Breadcrumb's permalink
 *	• object:	Original WP object that the name/url properties were sourced from (usually a post or taxonomy object).
 * 
 * @return {String}
 */
function get_breadcrumbs(){
	global $post;

	$crumbs	=	array();


	#	These are search results that're being displayed.
	if(is_search()){
		$crumbs[]	=	array(
			'name'	=>	'Search results',
			'url'	=>	''
		);
	}


	#	Make sure the $post global is ready.
	else if($post){

		if(is_singular())
			$crumbs[]	=	array(
				'name'		=>	$post->post_title,
				'url'		=>	get_permalink($post->id),
				'object'	=>	$post
			);


		#	Current post's of a hierarchial nature, such as a page. Display its ancestors.
		$post_type		=	get_post_type_object($post->post_type);
		if($post_type->hierarchical){
			$ancestry	=	get_post_ancestors($post->ID);
			$count		=	count($ancestry);

			for($i = 0; $i < $count; ++$i){
				$ancestor	=	get_post($ancestry[$i]);
				$crumbs[]	=	array(
					'name'		=>	$ancestor->post_title,
					'url'		=>	get_permalink($ancestor->ID),
					'object'	=>	$ancestor
				);
			}
		}


		#	Otherwise, it's something else entirely.
		else{
			$taxonomies	=	get_post_taxonomies($post->ID);


			#	There's one or more taxonomies assigned to this post object.
			if($count = count($taxonomies)){

				for($i = 0; $i < $count; ++$i){
					#	TODO: Include taxonomy's ancestors in breadcrumb chain?
					$taxonomy		=	get_taxonomy($taxonomies[$i]);
					$taxonomies[$i]	=	$taxonomy;
				}
	
	
				/**
				 * In the case of multiple taxonomies, we'd ideally use the one that has an ancestry. Since that's not
				 * implemented yet, we'll just fly with using the first taxonomy found associated with a post. */
				$terms	=	get_the_terms($post->ID, $taxonomies[0]->name);


				# Bail if there aren't any terms 
				if($terms){
					#	We might have multiple terms assigned, we'll just use the first.
					$term	=	reset($terms);
		
		
					$crumbs[]	=	array(
						'name'		=>	$term->name,
						'url'		=>	get_term_link($term),
						'object'	=>	$term
					);
		
		
					#	If this is a custom post type, include its base in the breadcrumb trail. 
					if(!$post_type->_builtin){
						$crumbs[]	=	array(
							'name'		=>	$post_type->labels->name,
							'url'		=>	home_url('/'.($taxonomies[0]->rewrite['slug'])),
							'object'	=>	$post_type
						);
					}
				}
			}



			#	No taxonomies found, so we'll just use the post type itself (assuming it's set to be public)
			else if($post_type->show_ui)
				$crumbs[]	=	array(
					'name'		=>	$post_type->labels->name,
					'url'		=>	home_url('/' . $post_type->rewrite['slug']),
					'object'	=>	$post_type
				);
		}
	}

	return array_reverse($crumbs);
}



/** Displays an HTML breadcrumb trail. */
function breadcrumbs(){
	$crumbs	=	get_breadcrumbs();
?> 
		<p class="breadcrumbs" itemscope="itemscope" itemtype="http://data-vocabulary.org/Breadcrumb">
			<a itemprop="url" href="<?= BLOG_URL ?>"><span itemprop="title">Home</span></a><?php foreach($crumbs as $crumb): ?> &gt;
			<a itemprop="url" href="<?= $crumb['url'] ?>"><span itemprop="title"><?= $crumb['name'] ?></span></a><?php endforeach; ?> 
		</p><?php
}




/** Returns the text for the page's title tag. */
function title($separator = ' &bull; '){
	$news	=	NEWS_PAGE_TITLE ? NEWS_PAGE_TITLE : BLOG_DESCRIPTION;

	if(IS_MOBILE):				return BLOG_TITLE;
	elseif(is_single()):		return BLOG_TITLE . $separator . $news . $separator . single_post_title('', FALSE);
	elseif(is_home()):			return BLOG_TITLE . $separator . $news;
	elseif(is_front_page()):	return BLOG_TITLE . $separator . single_post_title('', FALSE);
	elseif(is_page()):	 		return BLOG_TITLE . $separator . single_post_title('', FALSE);
	elseif(is_archive()):		return BLOG_TITLE . $separator . current(archive_titles());
	elseif(is_search()):		return BLOG_TITLE . $separator . sprintf(__('Search Results for %s', T_DOMAIN), '"'.get_search_query().'"');
	elseif(is_404()):			return BLOG_TITLE . $separator . __('Not Found', T_DOMAIN);
	else:						return BLOG_TITLE;
	endif;

	return $title;
}




/**
 * Returns the titles for the post archive being displayed
 * @return array - A 2-value indexed array with the archive's title and subtitle, respectively. 
 */
function archive_titles(){
	static $titles;
	if(!$titles){
		$titles	=	array();
	
		/**	If $post global wasn't found, queue the first post to retrieve what sort of Archive was requested. */
		if(empty($post)){
			if(have_posts()) the_post();
			$rewind	=	true;
		}
	
		if(is_day())			$titles	=	array(__('Daily Archives',		T_DOMAIN),	sprintf(__('Showing all posts from %s.', T_DOMAIN), get_the_date()));
		elseif(is_month())		$titles	=	array(__('Monthly Archives',	T_DOMAIN),	sprintf(__('Showing all posts from %s.', T_DOMAIN), get_the_date('F Y')));
		elseif(is_year())		$titles	=	array(__('Yearly Archives',		T_DOMAIN),	sprintf(__('Showing all posts from %s.', T_DOMAIN), get_the_date('Y')));
		elseif(is_category())	$titles	=	array(__('Category Search',		T_DOMAIN),	sprintf(__('Showing all posts filed under "%s".', T_DOMAIN), single_cat_title('', false)));
		elseif(is_tag())		$titles	=	array(__('Tag Search',			T_DOMAIN),	sprintf(__('Showing all posts tagged with "%s".', T_DOMAIN), single_tag_title('', false)));
		elseif(is_author())		$titles	=	array(__('Author Search',		T_DOMAIN),	sprintf(__('Showing all posts written by %s.', T_DOMAIN), get_the_author()));
		else					$titles	=	array(__('Archives',			T_DOMAIN),	'');
	
		/**	Reset the loop if it was started in head of function. */
		if($rewind) rewind_posts();
	}

	return $titles;
}

/** Returns the title for an archive page. */
function archive_title(){
	return reset(archive_titles());
}

/** Returns the subtitle/description of an archive page. */
function archive_subtitle(){
	$o	=	archive_titles();
	return $o[1];
}





/**
 * Displays navigation to next/previous pages where applicable
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
	else:	?> 
		<div class="pagenav default">
			<?= get_previous_posts_link('Back')	?: '<a class="prev">Back</a>'; ?> 
			<?= get_next_posts_link('Next')		?: '<a class="next">Next</a>';	?> 
		</div><?php


	endif;
	echo PHP_EOL;
}

