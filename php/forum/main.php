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
		
		$output = array(
			'<h1>Forum</h1>',
			'<p>Please read the <a href="/TODO-write-forum-rules">forum rules</a> before posting.</p>',
			'</div>',
			);
		
		$topics = array(
			array(
				'header' => "General",
				'keys' => array('announcements', 'general', 'touchytouchy', 'introductions', 'forumgames')),
			array(
				'header' => "Software Discussion",
				'keys' => array('projects', 'generalprogramming', 'gamedev', 'web', 'ux', 'interviewquestions')),
			array(
				'header' => "Site Topics",
				'keys' => array('codegolfing', 'competitions', 'gamejams', 'content')),
			);
		
		array_push($output, '<div style="padding-top:20px; padding-bottom:20px;">');
		
		array_push($output, '<div style="float:left; width:600px; margin-right:20px;">');
		
		foreach ($topics as $topic) {
			array_push($output, '<div class="block" style="margin-bottom:20px;">');
			array_push($output, '<h2>'.htmlspecialchars($topic['header']).'</h2>');
			foreach ($topic['keys'] as $category_key) {
				$category = $categories[$category_key];
				array_push($output, '<div style="margin-top:10px;">');
				
				$threads = $category['thread_count'];
				$posts = $category['post_count'];
				array_push($output, '<div style="width:100px; float:right; color:#aaa; font-size:11px;">');
				array_push($output, $threads.' thread'.($threads == 1 ? '' : 's').'<br />');
				array_push($output, $posts.' post'.($posts == 1 ? '' : 's'));
				array_push($output, '</div>');
				
				array_push($output, '<h3><a href="/forum/'.$category_key.'">');
				array_push($output, htmlspecialchars($category['name']));
				array_push($output, '</a></h3>');
				
				array_push($output, '<div style="width:400px;">');
				array_push($output, htmlspecialchars($category['description']));
				array_push($output, '</div>');
				
				array_push($output, '</div>');
			}
			array_push($output, '</div>');
		}
		
		array_push($output, '</div>');
		
		
		
		array_push($output, '<div style="float:left; width:340px;">');
		array_push($output, '<div class="block">');
		
		array_push($output, '<h2>Recent Activity</h2>');
		
		$recent_threads = api_forum_get_recent_threads();
		$user_info = $recent_threads['user_info'];
		foreach ($recent_threads['threads'] as $thread_info) {
			array_push($output, '<div style="margin-bottom:10px;">');
			
			$user = $user_info['user_'.$thread_info['user_id']];
			
			array_push($output, '<h3>');
			array_push($output, '<a href="/forum/'.$thread_info['category_key'].'/'.$thread_info['thread_id'].'/new">');
			array_push($output, htmlspecialchars($thread_info['title']));
			array_push($output, '</a>');
			array_push($output, '</h3>');
			
			array_push($output, '<div style="color:#888; font-style:italic;">');
			array_push($output, "Lorem ipsum dolar sit amet...");
			array_push($output, '</div>');
			
			array_push($output, '<div>');
			array_push($output, 'by <a href="/profiles/'.$user['login_id'].'">');
			array_push($output, htmlspecialchars($user['name']));
			array_push($output, '</a>');
			array_push($output, ' in <a href="/forum/'.$thread_info['category_key'].'">');
			array_push($output, htmlspecialchars($thread_info['category_name']));
			array_push($output, '</a>');
			array_push($output, '</div>');
			
			array_push($output, '</div>');
		}
		
		array_push($output, '</div>');
		array_push($output, '</div>');
		
		array_push($output, '</div>');
		
		array_push($output, '<div class="fullblock" style="clear:both;">');
		
		array_push($output, '<div>Users Online: ');
		$users_online = api_forum_get_users_online();
		$first = true;
		foreach ($users_online['ordered_user_keys'] as $login_id) {
			$user_id = $users_online['keys_to_user_ids'][$login_id];
			$user_info = $users_online['user_'.$user_id];
			if ($first) {
				$first = false;
			} else {
				array_push($output, ', ');
			}
			
			array_push($output, '<a href="/profiles/'.$login_id.'">');
			array_push($output, htmlspecialchars($user_info['name']));
			array_push($output, '</a>');
		}
		
		if ($first) {
			array_push($output, "No one!");
		}
		array_push($output, '</div>');
		
		return build_response_ok("Forum", implode("\n", $output));
	}
?>