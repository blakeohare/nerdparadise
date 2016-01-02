<?
	function execute($request) {
		
		if ($request['user_id'] == 0) {
			return build_response_forbidden('You must be <a href="/login">logged in</a> to post.');
		}
		
		$category_key = $request['path_parts'][1];
		$category_info = api_forum_get_category_info($request['user_id'], $request['is_admin'], $category_key, true);
		if ($category_info['ERROR']) {
			return build_response_not_found('Forum category not found.');
		}
		
		$path_parts = $request['path_parts'];
		if ($path_parts[3] == 'reply') {
			$type = 'reply';
			$thread_id = intval($path_parts[2]);
			$thread_info = api_forum_get_thread_info($request['user_id'], $request['is_admin'], $thread_id);
			if ($thread_info['is_locked']) {
				return build_response_forbidden("Cannot post in a locked thread.");
			}
		} else if ($path_parts[2] == 'post') {
			$type = 'create';
		} else {
			throw new Exception("Invalid post type.");
		}
		
		$thread_title = '';
		$post_body = '';
		$error_message = null;
		
		if ($request['method'] == "POST") {
			$thread_title = trim($request['form']['thread_title']);
			$post_body = trim($request['form']['post_body']);
			
			if ($type == 'create') {
				$result = api_forum_create_post(
					$request['user_id'],
					$request['is_admin'],
					$category_info['category_id'],
					$thread_title,
					0,
					null,
					$post_body);
			} else if ($type == 'reply') {
				$result = api_forum_create_post(
					$request['user_id'],
					$request['is_admin'],
					$category_info['category_id'],
					'',
					$thread_id,
					null,
					$post_body);
			} else {
				throw new Exception("Invalid post type.");
			}
			if ($result['OK']) {
				return build_response_moved_temporarily('/forum/'.$category_key.'/'.$result['thread_id'].'/new');
			} else {
				switch ($result['message']) {
					case 'BLANK_POST': $error_message = "Post cannot be blank."; break;
					case 'THREAD_TITLE_BLANK': $error_message = "Thread title cannot be blank."; break;
					default: $error_message = "Server returned error: ".$result['message']; break;
				}
			}
		}
		
		$html = array(
		
			$error_message != null 
				? '<div style="color:#f00;">'.htmlspecialchars($error_message).'</div>' 
				: '',
			
			'<form action="/'.implode('/', $request['path_parts']).'" method="post">',
				'<div>',
				$thread_id == 0
					? 'Title: <input type="text" name="thread_title" value="'.htmlspecialchars($thread_title).'" />'
					: '',
				'</div>',
				
				'<div>',
				'<textarea name="post_body" rows="12" style="width:900px">'.htmlspecialchars($post_body).'</textarea>',
				'</div>',
				
				'<div>',
				'<input type="submit" name="submit" value="Be Nice" />',
				'</div>',
				
			'</form>',
		);
		
		return build_response_ok("New Post", implode("\n", $html));
	}
?>