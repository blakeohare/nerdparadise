<?
	/*
		Three uses:
		- create new thread
		- reply to thread
		- message user(s)
		
		First creates a new post, which may possibly be orphaned (no parent thread)
		If it was not orphpaned, the bookkeeping to the parent thread is automatically handled by create_post_impl
		If it is orphaned, create the new thread. If creating the new thread fails, delete the post. Also update the category.
	*/
	function api_forum_create_post(
		$user_id,
		$is_admin,
		$category_id,
		$thread_title_if_new,
		$thread_id,
		$message_user_ids,
		$content) {
		
		if ($user_id == 0) return api_error("NOT_LOGGED_IN");
		$content = trim($content);
		if (strlen($content) == 0) return api_error("BLANK_POST");
		
		$thread_info = null;
		$post = null;
		
		if ($message_user_ids != null) {
			array_push($message_user_ids, $user_id);
			$message_user_ids = sort_and_remove_duplicates($message_user_ids);
			if (count($message_user_ids) < 2) {
				return api_error("NO_TARGET");
			}
			// TODO: check to see if you've been blocked by a user
			
			// message a user
			$thread_info = api_forum_get_user_message_thread($message_user_ids);
			if ($thread_info == null) {
				// create a new thread between/among these users
				$post = api_forum_create_post_impl($user_id, 0, 0, $content);
				if ($post['ERROR']) return $post;
			} else {
				// append to existing thread
				$post = api_forum_create_post_impl($user_id, 0, $thread_info['thread_id'], $content);
			}
		} else if ($category_id > 0) {
			// if the user is not allowed to post in a category, that will be reflected in the error message of create_post_impl
			
			if ($thread_id == 0) {
				// new thread
				$post = api_forum_create_post_impl($user_id, $is_admin, $cateogry_id, 0, $content);
			} else {
				// reply to thread
				$post = api_forum_create_post_impl($user_id, $is_admin, $cateogry_id, $thread_info['thread_id'], $content);
			}
		} else if ($category_id == 0) {
			return api_error("NO_CATEGORY_DEFINED");
		}
		
		if ($post == null) return api_error('POST_FAILED');
		
		if ($post['ERROR']) return $post;
		
		if ($post['thread_id'] == 0) {
			if ($thread_info != null) return api_error('THREAD_STATE_INCONSISTENCY');
			
			if ($message_user_ids != null) {
				$thread_info = api_forum_create_new_thread(0, '', $post['post_id'], 1);
				if ($thread_info['ERROR']) {
					api_forum_delete_posts(array($post['post_id']));
					return $thread_info;
				}
				sql_insert('forum_message_user_to_thread_id', array(
					'user_id_cluster' => api_forum_encode_user_id_cluster($message_user_ids),
					'thread_id' => $thread_info['thread_id']));
			} else {
				$thread_info = api_forum_create_new_thread($category_id, $thread_title_if_new, $post['post_id'], 1);
				if ($thread_info['ERROR']) {
					api_forum_delete_posts(array($post['post_id']));
					return $thread_info;
				}
			}
			
			sql_update("UPDATE `forum_posts` SET `thread_id` = ".$thread_info['thread_id']." WHERE `post_id` = ".$post['post_id']." LIMIT 1");
		}
		
		return api_success(array(
			'thread_id' => $thread_info['thread_id'],
			'post_id' => $post['post_id'],
			'category_id' => $thread_info['category_id']));
	}
	
	// If no thread is defined, create an orphaned post. It is the caller's responsibility to un-orphan it.
	// Thread infor is already confirmed if thread_id is non-zero.
	function api_forum_create_post_impl($user_id, $is_admin, $cateogry_id, $thread_id, $content) {
		$post_id = sql_insert('forum_posts', array(
			'thread_id' => $thread_id,
			'user_id' => $user_id,
			'time' => time(),
			'content_raw' => $content,
			'content_parsed' => 'TODO: parse content',
			'content_search' => 'TODO: searchable content',
			));
		
		if ($thread_id > 0) {
			sql_query("UPDATE `forum_threads` SET `last_post_id` = $post_id, `post_count` = `post_count` + 1 WHERE `thread_id` = $thread_id LIMIT 1");
			if ($category_id > 0) {
				sql_query("UPDATE `forum_categories` SET `post_count` = `post_count` + 1 WHERE `category_id` = $category_id LIMIT 1");
			}
		}
	}
	
	function api_forum_get_category_info($user_id, $is_admin, $category_id, $for_posting) {
		$category_id = intval($category_id);
		$category_info = sql_query_item("SELECT * FROM `forum_category` WHERE `category_id` = $category_id LIMIT 1");
		
		$is_admin_visible = false;
		$flags = $category_info['flags'];
		for ($i = 0; $i < strlen($flags); ++$i) {
			switch ($flags[$i]) {
				case 'A': $is_admin_visible = true; break;
				default: break;
			}
		}
		$category_info['is_admin_visible'] = $is_admin_visible;
		
		if ($category_info == null) return api_error('CATEGORY_NOT_FOUND');
		if (!$is_admin && $category_info['is_admin_visible']) return api_error('CATEGORY_NOT_FOUND');
		return $category_info;
	}
	
	// called by create post
	function api_forum_create_new_thread($user_id, $is_admin, $category_id, $thread_title, $post_id, $post_count) {
		$category_id = intval($category_id);
		$post_count = intval($post_count);
		$post_id = intval($post_id);
		
		$category_info = null;
		if ($category_id > 0) {
			$category_info = api_forum_get_category_info($user_id, $is_admin, $cateogry_id, true);
		}
		
		$thread_id = sql_insert('forum_threads', array(
			'category_id' => $category_id,
			'title' => $thread_title.'',
			'first_post_id' => $post_id,
			'last_post_id' => $post_id,
			'post_count' => $post_count));
		
		if ($category_info != null) {
			$last_post_id = ($category_info['last_post_id'] < $post_id) ? $post_id : $category_info['last_post_id'];
			sql_query("
				UPDATE `forum_categories`
				SET
					`thread_count` = `thread_count` + 1,
					`post_count` = `post_count` + $post_count,
					`last_post_id` = $last_post_id
				WHERE
					`category_id` = $category_id
				LIMIT 1");
		}
	}
	
	function api_forum_encode_user_id_cluster($user_ids) {
		$user_ids = sort_and_remove_duplicates($user_ids);
		$b64 = array();
		foreach ($user_ids as $user_id) {
			array_push($b64, int_to_base64($user_id));
		}
		return implode("|", $b64);
	}
	
	function api_forum_get_thread_for_user_cluster($user_ids) {
		$key = api_forum_encode_user_ids($user_ids);
		$thread_info = sql_query_item("
			SELECT `thread_id`
			FROM `forum_message_user_to_thread_id` 
			WHERE `user_id_cluster` = '$key'
			LIMIT 1");
		if ($thread_info == null) {
			return 0;
		}
		return $thread_info['thread_id'];
	}
	
	function api_forum_canonicalize_category_db_entry($category) {
		$is_admin_visible = false;
		$is_front_page = false;
		$is_admin_editable = false;
		$flags = $category['flags'];
		for ($i = strlen($flags) - 1; $i >= 0; --$i) {
			switch ($flags[$i]) {
				case 'F': $is_front_page = true; break;
				case 'A': $is_admin_visible = true; break;
				case 'O': $is_admin_editable = true; break;
				default: break;
			}
		}
		
		$category['is_admin_visible'] = $is_admin_visible;
		$category['is_admin_editable'] = $is_admin_editable;
		$category['is_front_page'] = $is_front_page;
		return $category;
	}
	
	function api_forum_get_top_level_categories($user_id, $is_admin) {
		$output = array();
		$categories = sql_query("SELECT * FROM `forum_categories`");
		for ($i = 0; $i < $categories->num_rows; ++$i) {
			$category = api_forum_canonicalize_category_db_entry($categories->fetch_assoc());
			if ($category['is_front_page']) {
				if ($is_admin || !$category['is_admin_visible']) {
					$output[$category['key']] = $category;
				}
			}
		}
		return $output;
	}
	
	function api_forum_canonicalize_post_db_entry($post_info) {
		return $post_info; // TODO: this
	}
	
	function api_forum_get_posts($post_ids, $fetch_thread_info_too = false, $fetch_user_info_too = false) {
		$post_infos = array();
		$post_ids = sort_and_remove_duplicates($post_ids);
		if (count($post_ids) > 0) {
			$posts = sql_query("SELECT * FROM `forum_posts` WHERE `post_id` IN (".implode(',', $post_ids).")");
			$thread_ids = array();
			$user_ids = array();
			for ($i = 0; $i < $posts->num_rows; ++$i) {
				$post = api_forum_canonicalize_post_db_entry($post->fetch_assoc());
				$post_infos['post_'.$post['post_id']] = $post;
				array_push($thread_ids, $post['thread_id']);
				array_push($user_ids, $post['user_id']);
			}
			
			if ($fetch_thread_info_too) {
				// TODO: fetch thread info and place in output array with thread_ prefix
			}
			
			if ($fetch_user_info_too) {
				// TODO: this as well with user_ prefix
			}
		}
		return $post_infos;
	}
	
	function api_forum_get_threads($category_id, $page_index) {
		$category_id = intval($category_id);
		$thread_order = array();
		$output = array();
		
		if ($category_id != 0) {
		
			$per_page = 25;
			$start_index = $page_index * $per_page;
			$threads = sql_query("
				SELECT * 
				FROM `forum_threads`
				WHERE `category_id` = $category_id
				ORDER BY `last_post_id` DESC
				LIMIT $start_index, $per_page");
			
			$post_ids = array();
			$user_ids = array();
			$threads = array();
			for ($i = 0; $i < $threads->num_rows; ++$i) {
				$thread = api_forum_canonicalize_thread_db_entry($threads->fetch_assoc());
				array_push($post_ids, intval($thread['last_post_id']));
				array_push($post_ids, intval($thread['first_post_id']));
				array_push($thread_order, $thread['thread_id']);
				$output['thread_'.$thread['thread_id']] = $thread;
			}
			$post_infos = api_forum_get_posts($post_ids, false, true);
			
			$output = array_merge($post_infos, $output);
		}
		
		$output['thread_order'] = $thread_order;
		return $output;
	}
?>