
			<div id="comments"><?php
				echo PHP_EOL;

				if(post_password_required()): ?> 
				<p><?= __('This post is password protected. Enter the password to view any comments.') ?></p>
			</div><?php

				# Prevent further inclusion
				return;
			endif;


			if(have_comments()): ?> 
			<h3 id="comments-title"><?php
				$num_comments    = get_comments_number();
				$comments_title  = _n('One Response to %2$s', '%1$s Responses to %2$s', $num_comments);
				$post_title      = '<em>'.get_the_title().'</em>';

				printf($comments_title, number_format_i18n($num_comments), $post_title);
			?></h3>


			<ol class="comment-list">
				<?php wp_list_comments(); ?> 
			</ol><?php


			# Are there comments to navigate through?
			if(get_comment_pages_count() > 1 && get_option('page_comments')): ?> 
			<div class="comment-nav">
				<div class="nav-previous"><?php   previous_comments_link(  __('<span class="meta-nav">&larr;</span> Older Comments')); ?></div>
				<div class="nav-next"><?php       next_comments_link(      __('Newer Comments <span class="meta-nav">&rarr;</span>')); ?></div>
			</div><?php

			# End check for comment navigation
			endif;


			# Or, if we don't have comments:
			else:
				if(!comments_open()):   echo '<p>' . __('Comments are closed.').'</p>';
				else:                   echo '<p>' . __('No comments have been posted yet.').'</p>';
				endif;
			endif;


			# Display the comment submission field
			if(comments_open()):

				comment_form(array(
					'comment_notes_before'  => '',
					'comment_notes_after'   => '',
					'title_reply'           => '<span>Leave a Reply</span>',
					'title_reply_to'        => '<span>Reply to %s</span>',
					'label_submit'          => 'Post Feedback'
				));

			else: ?> 
				<div id="respond">
					<h3 id="reply-title"><span><?= __('Leave a Reply') ?></span></h3>
					<div id="commentform"><?= __('This entry has been closed from feedback.') ?></div>
				</div><?php
			endif; ?> 
		</div>
		
