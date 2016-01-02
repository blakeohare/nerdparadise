<?
	function execute($request) {
		$path_parts = $request['path_parts'];
		$category_key = $path_parts[1];
		$thread_id = intval($path_parts[2]);
		$page_id = 0;
		
		if (count($path_parts) > 3 && substr($path_parts[3], 0, strlen('page')) == 'page') {
			$page_id = intval(substr($path_parts[3], strlen('page'))) - 1;
			if ($page_id < 0) $page_id = 0;
		}
		
		$forum_posts = api_forum_fetch_posts_for_thread($request['user_id'], $category_key, $thread_id, $page_id);
		$thread_info = $forum_posts['thread_'.$thread_id];
		$output = array(
			'<div>',
			
			'<div><a href="/forum/'.$category_key.'">'.$category_key.'</a></div>',
			
			'<h1>',
			htmlspecialchars($thread_info['title']),
			'</h1>',
			'</div>',
		);
		
		
		
		array_push($output, '<div>');
		
		foreach ($forum_posts['ordered_post_ids'] as $post_id) {
			$post = $forum_posts['post_'.$post_id];
			$user = $forum_posts['user_'.$post['user_id']];
			array_push($output, 
				'<div>',
				
				'<div style="float:left; width:200px;">',
					'<a href="/profiles/'.$user['login_id'].'">',
					htmlspecialchars($user['name']),
					'</a>',
				'</div>',
				
				'<div style="float:right; width:700px;">',
					nl2br(htmlspecialchars($post['content_raw'])),
				'</div>',
				
				'<div style="clear:both;"></div>',
				'</div>');
		}
		
		array_push($output, '</div>');
		
		array_push($output, '<div><a href="/forum/'.$category_key.'/'.$thread_id.'/reply">Reply</a></div>');
		
		return build_response_ok("Forum thread", implode("\n", $output));
	}
?>