<?
	function execute($request) {
		$path_parts = $request['path_parts'];
		$category_key = $path_parts[1];
		$thread_id = intval($path_parts[2]);
		$page_id = 0;
		
		// returns null for admin-only threads if not an admin
		$thread_info = api_forum_get_thread_info($request['user_id'], $request['is_admin'], $thread_id, true);
		if ($thread_info == null ||
			$thread_info['category_info']['key'] != $category_key) {
			return build_response_not_found("Thread not found.");
		}
		
		$total_posts = $thread_info['post_count'];
		
		if (count($path_parts) > 3 && substr($path_parts[3], 0, strlen('page')) == 'page') {
			$page_id = intval(substr($path_parts[3], strlen('page'))) - 1;
			if ($page_id < 0) $page_id = 0;
		} else if ($path_parts[3] == 'new') {
			// TODO: per-user new post tracking
		}
		
		$current_page = $page_id + 1;
		$total_pages = intval(($total_posts - 1) / 25) + 1;
		
		// List of integers including 1-indexed page numbers or -1 for ellipses.
		// Links to first 3 pages and last 3 pages should always be available, along with pages within 2 of the current page.
		$paginator_links = array();
		$first_range = 3;
		$end_range = $total_pages - 2;
		$mid_begin_range = $current_page - 2;
		$mid_end_range = $current_page + 2;
		$last_item_is_ellipsis = false;
		for ($i = 1; $i <= $total_pages; ++$i) {
			if ($i <= $first_range || 
				$i >= $end_range ||
				($i >= $mid_begin_range && $i <= $mid_end_range)) {
				array_push($paginator_links, $i);
				$last_item_is_ellipses = false;
			} else if (!$last_item_is_ellipses) {
				array_push($paginator_links, -1);
				$last_item_is_ellipses = true;
			}
		}
		
		$starting_post_index = $page_id * 25;
		if ($starting_post_index >= $total_posts) {
			
		}
		
		$forum_posts = api_forum_fetch_posts_for_thread($request['user_id'], $category_key, $thread_id, $page_id);
		$post_ids = $forum_posts['ordered_post_ids'];
		$thread_info = $forum_posts['thread_'.$thread_id];
		$category_info = $forum_posts['category_'.$thread_info['category_id']];
		
		if ($category_info == null) {
			return build_response_not_found("Thread not found.");
		}
		
		$output = array();
		
		array_push($output,
			'<h1 style="font-size:16px;">',
			'<a href="/forum">Forum</a> &gt; ',
			'<a href="/forum/'.$category_key.'">'.htmlspecialchars($category_info['name']).'</a> &gt; ',
			htmlspecialchars($thread_info['title']),
			'</h1>');
		
		$paginator_html = array();
		
		if ($total_pages > 1) {
			array_push($paginator_html, '<div style="text-align:right;">');
			
			if ($current_page > 1) {
				array_push($paginator_html, '<a href="/forum/'.$category_key.'/'.$thread_id.'/page'.($current_page - 1).'">Prev</a> ');
			}
			
			foreach ($paginator_links as $page) {
				if ($page === -1) {
					array_push($paginator_html, ' ... ');
				} else if ($page == $current_page) {
					array_push($paginator_html, '['.$current_page.']');
				} else {
					array_push($paginator_html, '<a href="/forum/'.$category_key.'/'.$thread_id.'/page'.$page.'">'.$page.'</a>');
				}
			}
			
			if ($current_page < $total_pages) {
				array_push($paginator_html, '<a href="/forum/'.$category_key.'/'.$thread_id.'/page'.($current_page + 1).'">Next</a> ');
			}
			
			array_push($paginator_html, '</div>');
		}
		
		$paginator_html = implode("\n", $paginator_html);
		
		array_push($output, $paginator_html);
		
		if (count($post_ids) == 0) {
			return build_response_not_found("No posts found.");
		}
		
		array_push($output, '</div>');
		
		//array_push($output, '<div style="margin-bottom:20px;">');
		
		foreach ($post_ids as $post_id) {
			$post = $forum_posts['post_'.$post_id];
			$user = $forum_posts['user_'.$post['user_id']];
			array_push($output, 
				//'<div style="margin:8px; background-color:#f8f8f8; border:1px solid #ccc; padding:8px;">',
				
				'<div style="clear:both; padding-top:20px;">',
		
				'<div class="block" style="float:left; width:120px; margin-right:20px;">',
					'<div style="text-align:center;">',
					strlen($user['image_id']) > 0
						? '<img src="/uploads/avatars/'.$user['image_id'].'" />'
						: ":'(", // TODO: stub avatar
					'</div>',
				'</div>',
				
				'<div style="float:left; width:780px; background-color:#fff;">',
					'<div style="background-color:#ddd;font-weight:bold; padding:8px; font-size:12px;">',
						
						'<div title="'.date("M j, Y g:i:s A", $post['time']).'" style="float:right;width:300px;text-align:right;font-weight:normal;color:#555;">',
							unix_to_scaling_time($post['time']),
						'</div>',
						
						'<a href="/profiles/'.$user['login_id'].'">'.
						htmlspecialchars($user['name']).
						'</a>',
					'</div>',
					'<div style="clear:right;padding:20px;">',
					nl2br(htmlspecialchars($post['content_raw'])),
					'</div>',
				'</div>',
				
				'</div>'
				//'<div style="clear:both;"></div>',
				//'</div>'
				);
		}
		
		//array_push($output, '</div>');
		
		
		
		array_push($output, 
			'<div class="fullblock" style="clear:both;margin-top:20px;">');
		
		array_push($output, $paginator_html);
		
		array_push($output, 
			'<div><a href="/forum/'.$category_key.'/'.$thread_id.'/reply">Reply</a></div>');
		
		return build_response_ok("Forum thread", implode("\n", $output));
	}
?>