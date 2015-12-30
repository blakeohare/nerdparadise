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
			
			'<div style="background-color:#999;">'
			);
		
		$thread_ids = $threads['thread_order'];
		if (count($thread_ids) > 0) {
			$i = 0;
			foreach ($thread_ids as $thread_id) {
				$thread_info = $threads['thread_'.$thread_id];
				$last_post_info = $threads['post_'.$thread_info['last_post_id']];
				$last_post_user_info = $threads['user_'.$last_post_info['user_id']];
				
				$row = implode("", array(
				
					'<div style="margin:1px; background-color:#'.($i % 2 == 0 ? 'eee' : 'fff').'">',
						
						'<div style="width:600px; float:left;">',
							'<a href="/forum/'.$category_key.'/'.$thread_info['thread_id'].'">',
							htmlspecialchars($thread_info['title']),
							'</a>',
						'</div>',
						
						'<div style="width:80px; float:left; text-align:center; font-size:11px;">',
							'<div>'.($thread_info['post_count'] - 1).'</div>',
							'<div>replies</div>',
						'</div>',
						
						'<div style="width:80px; float:left; text-align:center; font-size:11px;">',
							'<div>'.$thread_info['view_count'].'</div>',
							'<div>views</div>',
						'</div>',
						
						'<div style="width:150px; float:left;">',
							'<a href="/profiles/'.$last_post_user_info['login_id'].'">',
							htmlspecialchars($last_post_user_info['name']),
							'</a>',
						'</div>',
					
					'<div style="clear:left;"></div>',
					'</div>',
				));
				
				array_push($output, $row);
				++$i;
			}
		} else {
			array_push($output, '<tr><td>No posts</td></tr>');
		}
		
		array_push($output, '</div>');
		
		return build_response_ok("Forum category", implode("\n", $output));
	}
?>