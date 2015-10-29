<?php


# Define our custom settings
add_action('admin_init', function(){

	# "Skip Attachments" setting
	add_settings_field('skip-attach', '<label for="skip_attachments">'.__('Skip Attachment Pages?').'</label>', function(){
		?> 
		<input type="checkbox" id="skip_attachments" name="skip_attachments" value="1"<?php checked(get_option('skip_attachments')); ?> />
		<span class="description">
			<label for="skip_attachments">Attachment pages will redirect visitors to the actual attached file instead of a metadata page.</label>
		</span><?php
	}, 'reading');

	register_setting('reading', 'skip_attachments');



	# Setting for removing the injected emoji-detection script and related styling added in WordPress 4.2+
	add_settings_field('disable-emoji', '<label for="disable_emoji">'.__('Disable Emoji').'</label>', function(){
		?> 
		<input type="checkbox" id="disable_emoji" name="disable_emoji" value="1"<?php checked(get_option('disable_emoji', TRUE)); ?> />
		<span class="description">
			<label for="disable_emoji">Disables WordPress's automatic emoji detection, which is useless unless emoji are being used on the site (or visitors can submit comments).</label>
		</span><?php
	}, 'discussion');

	register_setting('discussion', 'disable_emoji');
});



# Disable WordPress's emoji DOM-juice if settings dictate we'll never need/want it
if(get_option('disable_emoji', TRUE)){
	remove_action('wp_head', 'print_emoji_detection_script', 7);
	remove_action('wp_print_styles', 'print_emoji_styles');
}



if(get_option('skip_attachments'))
	add_action('template_redirect', function(){
		if(is_attachment()){
			wp_redirect(wp_get_attachment_url($GLOBALS['post']->ID));
			exit;
		}
	});
