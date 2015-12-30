<?
	function execute($request) {
		$categories = api_forum_get_top_level_categories($request['user_id'], $request['is_admin']);
		
		debug_print($categories);
		return build_response_ok("Forum", 'forum main<br />'.universal_to_string($categories));
	}
?>