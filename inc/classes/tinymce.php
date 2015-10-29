<?php


class MCEPlugins{
	const PLUGIN_DIR = '/src/js/mce/tiny_mce/plugins';


	/** @var array List of TinyMCE plugins to load by WordPress */
	var $plugins	=	array();



	function construct(){
		add_action('init', array($this, 'init'));

		$args		=	func_get_args();


		/** Multiple strings (the easiest and recommended approach). */
		if(($arg_count = count($args)) > 1){
			for($i = 0; $i < $arg_count; ++$i)
				$this->add($args[$i]);
		}


		/** One argument: allow plugins to be specified as either an array or a string of space-delimited IDs. */
		else if($arg_count === 1){
			$list		=	is_string($args[0]) ? explode(' ', $args[0]) : (array) $args[0];
			$count		=	count($list);

			for($i = 0; $i < $count; ++$i)
				$this->add($list[$i]);
		}
	}



	function init(){

		/**	Don't bother doing anything if we haven't adequate permission. */
		if(!current_user_can('edit_posts') && !current_user_can('edit_pages')) return;


		/** Add only in Rich Editing Mode */
		if(get_user_option('rich_editing') == 'true'){
			add_filter('mce_external_plugins', array($this, 'load'));
			add_filter('mce_buttons', array($this, 'buttons'));
		}

		if(is_admin())
			wp_enqueue_style('mce_plugins', THEME_DIR . '/src/js/mce/style.css');
	}



	function css(){
		if(is_admin()) : ?> 
<link rel="stylesheet" type="text/css" href="<?= THEME_DIR ?>/src/js/mce/style.css" media="screen, projection, tv" /><?php
		echo PHP_EOL;
		endif;
	}


	/**	Load TinyMCE plugins : editor_plugin.js (wp2.5) */
	function load($plugin_array){
		foreach($this->plugins as $plugin)
			$plugin_array[$plugin]	=	THEME_DIR . '/' . $this::PLUGIN_DIR . '/' . $plugin . '/editor_plugin.js';
		return $plugin_array;
	}


	/**	Add TinyMCE Buttons */
	function buttons($buttons){
		$buttons[]	=	'separator';
		return array_merge($buttons, $this->plugins);
	}
	
	
	/**
	 * Registers a new TinyMCE plugin.
	 *
	 * @param string $plugin Plugin ID, typically a websafe string in lowercase.
	 * @return bool TRUE if the plugin was successfully added; FALSE otherwise.
	*/
	function add($plugin){
		if(in_array($plugin, $this->plugins))
			return false;

		$this->plugins[]	=	$plugin;
		@include_once(TEMPLATEPATH . '/' . $this::PLUGIN_DIR . '/' . $plugin . '/plugin.php');
		return true;
	}
}
