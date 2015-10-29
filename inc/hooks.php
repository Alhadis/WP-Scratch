<?php

require_once 'hooks.regular.php';
require_once 'hooks.settings.php';
require_once 'hooks.shortcodes.php';


# Admin-only hooks
if(is_admin()){
	# wp_enqueue_script('admin', THEME_DIR . '/src/js/admin.js');
	# wp_enqueue_style('admin', THEME_DIR . '/src/css/admin.css');
}
