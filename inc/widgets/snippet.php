<?php

/**
 * Widget to output a post's content.
 */
class Snippet extends WP_Widget{
	
	
	function __construct(){
		$this->WP_Widget('snip', 'Snippet', array(
			'description' => 'Insert content into a sidebar using a Page ID.',
			'classname'   => 'snip'
		));
	}

	
	/**
	 * Display widget.
	 *
	 * @param array $args
	 * @param array $instance
	 */
	function widget($args, $instance){
		extract(apply_filters('snippet_parse_args', $args, $instance));

		# Unravel the arguments stored in this widget instance
		$title          = esc_attr($instance['title']);
		$page_id        = intval($instance['page_id']);
		$apply_filters  = intval($instance['apply_filters']);


		# Retrieve the page instance by the requested ID. If it doesn't exist, don't display the widget.
		if(!$page = get_page($page_id))
			return NULL;


		# Start preparing the output
		$content = $page->post_content;


		if($apply_filters)
			$content = str_replace(']]>', ']]&gt;', apply_filters('the_content', $content));


		# If there's no content to display... don't bother
		if(!$content) return NULL;

		echo "\n$before_widget\n";

		if($title)
			echo $before_title, $title, $after_title, PHP_EOL;

		echo $content, PHP_EOL, $after_widget, PHP_EOL;
	}
	
	
	/** Update widget's options using the submitted control form */
	function update($new_instance, $old_instance){
		if(!isset($new_instance['submit']))
			return false;

		$instance                   = $old_instance;
		$instance['title']          = strip_tags($new_instance['title']);
		$instance['page_id']        = intval($new_instance['page_id']);
		$instance['apply_filters']  = intval($new_instance['apply_filters']);
		return $instance;
	}
	
	
	/**
	 * Filter the output from wp_dropdown_pages to apply an additional class to <select> tag.
	 * @access private
	 */
	function filter_dropdown($input){
		return str_replace('<select ', '<select class="widefat" ', $input);
	}
	
	
	
	/**
	 * Display the widget's control form.
	 *
	 * @access private
	 * @uses $wpdb
	 */
	function form($instance){
		global $wpdb;

		$instance = wp_parse_args((array) $instance, array(
			'title'         => '',
			'page_id'       => 0,
			'apply_filters' => 0
		));

		$title          = esc_attr($instance['title']);
		$page_id        = intval($instance['page_id']);
		$apply_filters  = intval($instance['apply_filters']);
		
		$f_title        = $this->get_field_id('title');
		$f_id           = $this->get_field_id('page_id');
		$f_apply        = $this->get_field_id('apply_filters');
?> 
			<label for="<?= $f_title ?>">
				<?= __('Title:') ?>
				<input class="widefat" id="<?= $f_title ?>" name="<?= $this->get_field_name('title') ?>" type="text" value="<?= $title ?>" />
			</label>

			<label for="<?= $f_id ?>"><?php
				_e('Post ID:');

				add_filter('wp_dropdown_pages', array(&$this, 'filter_dropdown'));
				wp_dropdown_pages(array(
					'name'               => $this->get_field_name('page_id'),
					'show_option_none'   => __('&mdash; Select &mdash;'),
					'option_none_value'  => '0',
					'selected'           => $page_id,
					'post_status'        => array('publish', 'private')
				));
				remove_filter('wp_dropdown_pages', array(&$this, 'filter_dropdown'));
			?> 
			</label>

			<label for="<?= $f_apply ?>">
				<input type="checkbox" id="<?= $f_apply ?>" name="<?= $this->get_field_name('apply_filters') ?>" value="1" <?php checked($instance['apply_filters'], true) ?> />
				<?php _e('Apply Filters') ?> 
			</label>

			<input type="hidden" id="<?= $this->get_field_id('submit') ?>" name="<?= $this->get_field_name('submit') ?>" value="1" /><?php
		echo PHP_EOL;
	}
}


# Register the widget with WordPress
add_action('widgets_init', function(){
	register_widget('Snippet');
});
