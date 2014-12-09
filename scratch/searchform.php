
			<form role="search" method="get" action="<?= esc_url(home_url('/')) ?>">
				<input id="search-query" type="search" name="s" placeholder="Search&hellip;" value="<?= get_search_query(); ?>" />
				<input id="search-submit" type="submit" value="&#x2315;" />
			</form>
