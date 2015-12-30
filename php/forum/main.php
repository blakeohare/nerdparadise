<?

	function forum_main_render_category($category_info) {
		$output = array(
			'<div>',
				'<div>',
				'<a href="/forum/'.$category_info['key'].'">',
				htmlspecialchars($category_info['name']),
				'</a>',
				'</div>',
				
				'<div>',
				htmlspecialchars($category_info['description']),
				'</div>',
			'</div>',
		);
		return implode('', $output);
	}
	function execute($request) {
		$categories = api_forum_get_top_level_categories($request['user_id'], $request['is_admin']);
		
		//debug_print($categories);
		
		// TODO: show categories along left, show recent posts along right
		
		$output = array(
			'<h2>General</h2>',
				forum_main_render_category($categories['announcements']),
				forum_main_render_category($categories['general']),
				forum_main_render_category($categories['touchytouchy']),
				forum_main_render_category($categories['introductions']),
				forum_main_render_category($categories['forumgames']),
				$request['is_admin'] ? forum_main_render_category($categories['admin']) : '',
			"<h2>Software Discussion/Help</h2>",
				forum_main_render_category($categories['projects']),
				forum_main_render_category($categories['generalprogramming']),
				forum_main_render_category($categories['gamedev']),
				forum_main_render_category($categories['web']),
				forum_main_render_category($categories['ux']),
				forum_main_render_category($categories['interviewquestions']),
			'<h2>Site Topics</h2>',
				forum_main_render_category($categories['codegolfing']),
				forum_main_render_category($categories['competitions']),
				forum_main_render_category($categories['gamejams']),
				forum_main_render_category($categories['content']),
		);
		
		
		return build_response_ok("Forum", implode("\n", $output));
	}
?>