<?
	function execute($request) {
		$category_key = $request['path_parts'][1];
		$category_info = api_forum_get_category_info($request['user_id'], $request['is_admin'], $category_key, false);
		
		if ($category_info['ERROR']) return not_found_impl($request);
		
		$page = 0;
		if (substr($request['path_parts'][2], 0, strlen('page')) == 'page') {
			$page = intval(substr($request['path_parts'][2], strlen('page')));
			if ($page < 0) $page = 0;
		}
		
		$threads = api_forum_get_threads($category_info['category_id'], $page);
		
		$output = array(
			'<h2>',
				'<a href="/forum">Forum</a>',
				' &gt; ',
				htmlspecialchars($category_info['name']).
			'</h2>',
			
			'<div>',
			'<a href="/forum/'.$category_key.'/post">Create new thread</a>',
			'</div>',
			
			'<table border="1">'
			);
		
		$thread_ids = $threads['thread_order'];
		if (count($thread_ids) > 0) {
			foreach ($thread_ids as $thread_id) {
				$thread_info = $threads['thread_'.$thread_id];
				array_push($output, '<tr>');
				array_push($output, '<td>'.htmlspecialchars($thread_info['title']).'</td>');
				array_push($output, '</tr>');
			}
		} else {
			array_push($output, '<tr><td>No posts</td></tr>');
		}
		
		array_push($output, '</table>');
		
		return build_response_ok("Forum category", implode("\n", $output));
	}
?>