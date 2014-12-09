<?php
/**
	* @package WordPress
	* @subpackage Scratch
*/


#	Define our custom settings.
add_action('admin_init', function(){

	#	Skip Attachments Setting
	add_settings_field('skip-attach', '<label for="skip_attachments">'.__('Skip Attachment Pages?').'</label>', function(){
		?> 
		<input type="checkbox" id="skip_attachments" name="skip_attachments" value="1"<?php checked(get_option('skip_attachments')); ?> />
		<span class="description">
			<label for="skip_attachments">Attachment pages will redirect visitors to the actual attached file instead of a metadata page.</label>
		</span><?php
	}, 'reading');

	register_setting('reading', 'skip_attachments');
});






if(get_option('skip_attachments'))
	add_action('template_redirect', function(){
		if(is_attachment()){
			wp_redirect(wp_get_attachment_url($GLOBALS['post']->ID));
			exit;
		}
	});

