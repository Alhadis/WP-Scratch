<?php

get_header();

?>

		<main role="main"><?php
			$page404 = get_post(get_option(OPTION_404_PAGE));

			if($page404){ ?> 
			<h2><?= get_the_title($page404->ID) ?></h2>
			<?php echo apply_filters('the_content', $page404->post_content);
			}

			else{
		?> 
			<h2>Page not found</h2>
			<p>The page you requested was not found.</p>
			<a href="<?= SITE_URL ?>">Return to homepage</a><?php

			}
		?> 
		</main>

<?php

get_footer();
