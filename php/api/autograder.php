<?
	
	function api_autograder_generate_client_html($type, $nullable_language_info, $template_code, $problem_id) {
		$include_language_picker = $nullable_language_info == null;
		
		if ($include_language_picker) {
			$picker_html = array('<div>Language: <select id="ag_language">');
			$languages = sql_query("SELECT `key`,`name` FROM `languages` WHERE `auto_grader_supported` = 1 ORDER BY `name`");
			for ($i = 0; $i < $languages->num_rows; ++$i) {
				$language = $languages->fetch_assoc();
				array_push($picker_html, '<option value="'.$language['key'].'">');
				array_push($picker_html, htmlspecialchars($language['name']));
				array_push($picker_html, '</option>');
			}
			array_push($picker_html, '</select></div>');
			$picker_html = implode("\n", $picker_html);
		} else {
			$picker_html = '<input type="hidden" id="ag_language" value="'.htmlspecialchars($nullable_language_info['key']).'" />';
		}
		
		return implode("\n", array(
			'<div>',
				'<h2>Write some code</h2>',
				
				'<input type="hidden" id="ag_problem_id" value="'.intval($problem_id).'" />',
				$picker_html,
				
				'<div style="float:left;width:460px;">',
					'<div>',
					'<textarea id="ag_code" style="width:100%; height:400px;" spellcheck="false">'.htmlspecialchars($template_code).'</textarea>',
					'</div>',
				'</div>',
				
				'<div style="float:right;width:460px;font-size:11px;font-family:&quot;Lucida Console&quot;,monospace;">',
					'<div id="ag_output_host">Output will appear here.</div>',
				'</div>',
				
				// mitigate spam bots, to at least a small degree, by populating the button via JavaScript
				'<div style="clear:both;" onload="ag_build_button()" id="ag_button_host"></div>',
			
			'</div>',
			
			));
	}
	
	function api_autograder_get_item_status_for_client($token) {
		$item = sql_query_item("SELECT * FROM `auto_grader_queue` WHERE `token` = '".sql_sanitize_string($token)."' LIMIT 1");
		if ($item == null) return api_error('NOT_FOUND');
		return api_success($item);
	}
	
	function api_autograder_get_work_queue() {
		$items = sql_query("SELECT `token` FROM `auto_grader_queue` WHERE `is_not_started` = 1 LIMIT 4");
		$output = array();
		for ($i = 0; $i < $items->num_rows; ++$i) {
			$row = $items->fetch_assoc();
			array_push($output, $row['token']);
		}
		return $output;
	}
	
	function api_autograder_create_new_practice_item($user_id, $language_key_or_id, $code, $problem_id) {
		return api_autograder_create_new_item($user_id, 'practice', 'PRACTICE:'.$problem_id, $language_key_or_id, $code, $problem_id);
	}
	
	function api_autograder_create_new_golf_item($user_id, $language_key_or_id, $code, $problem_id) {
		return api_autograder_create_new_item($user_id, 'golf', 'GOLF:'.$problem_id, $language_key_or_id, $code, $problem_id);
	}
	
	function api_autograder_create_new_tinker_item($user_id, $language_key_or_id, $code) {
		return api_autograder_create_new_item($user_id, 'tinker', '', $language_key_or_id, $code, 0);
	}
	
	function api_autograder_create_new_item(
		$user_id,
		$feature,
		$callback_arg,
		$language_key_or_id,
		$code,
		$problem_id) {
		
		$language_info = api_autograder_get_language_info($language_key_or_id);
		if ($language_info == null || !$language_info['auto_grader_supported']) {
			return api_error('LANG_NOT_SUPPORTED');
		}
		
		$token = api_autograder_get_new_token();
		
		$item_id = sql_insert('auto_grader_queue', array(
			'user_id' => $user_id,
			'token' => $token,
			'state' => 'NOT_STARTED',
			'is_not_started' => 1,
			'time_created' => time(),
			'time_processed' => 0,
			'time_finished' => 0,
			'language_id' => $language_info['language_id'],
			'code' => $code,
			'problem_id' => $problem_id,
			'output' => '',
			'callback_arg' => $callback_arg,
			'feature' => $feature,
			));
		
		return api_success(array(
			'item_id' => $item_id,
			'token' => $token));
	}
	
	function api_autograder_get_new_token() {
		$token = generate_gibberish(10);
		for ($i = 0; $i < 20; ++$i) {
			$existing = sql_query_item("SELECT `item_id` FROM `auto_grader_queue` WHERE `token` = '".sql_sanitize_string($token)."' LIMIT 1");
			if ($existing == null) {
				return $token;
			}
		}
		return 'waaaaaaaaa'; // no reason for this to happen. But SQL queries in infinite loops make me nervous.
	}
	
	function api_autograder_is_key_valid($token, $key) {
		global $CREDENTIALS;
		$expected_key = sha1($token.$CREDENTIALS['autograder_secret']);
		return strtolower($expected_key) == strtolower($key);
	}
	
	function api_autograder_canonicalize_problem($problem) {
		if ($problem == null) return null;
		
		$metadata = $problem['metadata'];
		$headers = explode("\n", $metadata);
		
		$languages = null;
		$expected_function_name = 'myFunction';
		$expected_return_type = 'int';
		$expected_arg_types = array();
		$expected_arg_names = array();
		
		foreach ($headers as $header) {
			$parts = explode(':', $header);
			$name = trim($parts[0]);
			$value = $parts[1];
			for ($i = 2; $i < count($parts); ++$i) {
				$value .= ':'.$parts[$i];
			}
			$value = trim($value);
			switch (strtoupper($name)) {
				case 'FUNC': $expected_function_name = $value; break;
				case 'ARGS': $expected_arg_names = explode(',', $value); break;
				case 'ARG_TYPES': $expected_arg_types = explode(',', $value); break;
				case 'RETURN_TYPE': $expected_return_type = $value; break;
				case 'LANGUAGES': $languages = $value == 'all' ? null : explode(',', $value); break;
				default: break;
			}
		}
		
		$problem['languages'] = $languages;
		$problem['expected_function_name'] = $expected_function_name;
		$problem['expected_arg_names'] = $expected_arg_names;
		$problem['expected_arg_types'] = $expected_arg_types;
		$problem['expected_arg_count'] = count($expected_arg_names);
		$problem['expected_return_type'] = $expected_return_type;
		
		return $problem;
	}
	
	function api_autograder_claim_by_grader($token, $key) {
		if (!api_autograder_is_key_valid($token, $key)) {
			return api_error("WRONG_KEY");
		}
		
		$item = sql_query_item("
			SELECT
				ag.*,
				lang.`key` AS 'language'
			FROM `auto_grader_queue` ag
			INNER JOIN `languages` lang ON (lang.`language_id` = ag.`language_id`)
			WHERE ag.`token` = '".sql_sanitize_string($token)."' 
			LIMIT 1");
		if ($item == null) return api_error("NOT_FOUND");

		$item_id = intval($item['item_id']);
		$language = $item['language'];
		$code = $item['code'];
		$callback = $item['callback_arg'];
		$feature = $item['feature'];
		$problem_id = intval($item['problem_id']);
		$problem = null;
		if ($problem_id > 0) {
			$problem = api_autograder_canonicalize_problem(sql_query_item("SELECT * FROM `code_problems` WHERE `problem_id` = $problem_id LIMIT 1"));
			
		}
		
		if ($problem == null && $feature != 'tinker') {
			return api_error("NOT_FOUND");
		}
		
		sql_query("
			UPDATE `auto_grader_queue`
			SET
				`state` = 'PENDING',
				`time_processed` = ".time().",
				`is_not_started` = 0
			WHERE
				`item_id` = $item_id
			LIMIT 1");
		
		return api_success(array(
			'language' => $language,
			'code' => $code,
			'feature' => $feature,
			'expected_function_name' => $problem['expected_function_name'],
			'expected_arg_count' => $problem['expected_arg_count'],
			'arg_types' => $problem['expected_arg_types'],
			'return_type' => $problem['expected_return_type'],
			'test_json' => $problem['test_json'],
			'callback' => $callback,
		));
	}
	
	function api_autograder_set_status($token, $key, $status) {
		// TODO: refactor this. it's copy and pasted in 3 places.
		if (!api_autograder_is_key_valid($token, $key)) {
			return api_error("WRONG_KEY");
		}
		$item = sql_query_item("SELECT * FROM `auto_grader_queue` WHERE `token` = '".sql_sanitize_string($token)."' LIMIT 1");
		if ($item == null) return api_error("NOT_FOUND");
		
		$item_id = intval($item['item_id']);
		
		$status = strtoupper(trim($status));
		
		switch ($status) {
			case 'NOT_STARTED':
			case 'SETTING_UP':
			case 'RUNNING':
			case 'DONE':
			case 'ERROR_BANNED_CODE':
			case 'ERROR_COMPILE':
			case 'ERROR_RUNTIME':
			case 'ERROR_TIMED_OUT':
			case 'ERROR_MEMORY_EXCEEDED':
				break;
			default:
				return api_error('INVALID_STATE');
		}
		
		sql_query("UPDATE `auto_grader_queue` SET `state` = '".sql_sanitize_string($status)."' WHERE `item_id` = $item_id LIMIT 1");
		return api_success();
	}
	
	function api_autograder_report_conclusion($token, $key, $output, $callback) {
		if (!api_autograder_is_key_valid($token, $key)) {
			return api_error("WRONG_KEY");
		}
		
		$item = sql_query_item("SELECT * FROM `auto_grader_queue` WHERE `token` = '".sql_sanitize_string($token)."' LIMIT 1");
		if ($item == null) return api_error("NOT_FOUND");
		
		$item_id = intval($item['item_id']);
		
		$state = 'DONE';
		sql_query("
			UPDATE `auto_grader_queue`
			SET
				`state` = '".sql_sanitize_string($state)."',
				`output` = '".sql_sanitize_string($output)."',
				`time_finished` = ".time().",
				`is_output_truncated` = 0
			WHERE `item_id` = $item_id
			LIMIT 1");
		
		return api_success();
	}
	
	function api_autograder_menu_get_problem($user_id, $type, $competition_id, $problem_id) {
		$problem = api_autograder_canonicalize_problem(sql_query_item("SELECT * FROM `code_problems` WHERE `problem_id` = ".intval($problem_id)." LIMIT 1"));
		if ($problem == null) return null;
		if ($problem['type'] != $type) return null;
		if ($problem['competition_id'] != $competition_id) return null;
		return $problem;
	}
	
	function api_autograder_menu_get_problems($user_id, $type, $competition_id, $show_golf_scores) {
		$user_id = intval($user_id);
		$language_id = intval($language_id);
		if ($type != 'golf' && $type != 'practice' && $type != 'competition') return api_error("INVALID_TYPE");
		
		if ($type == 'competition') {
			$competition_id = intval($competition_id);
			$where = "`competition_id` = $competition_id";
		} else {
			$where = "`type` = '$type'";
		}
		
		$query = "
			SELECT
				`problem_id`,
				`title`,
				`metadata`
			FROM `code_problems`
			WHERE $where
			ORDER BY
				`problem_id` DESC";
		
		$problems = sql_query($query);
		
		$output = array();
		$ordered_problem_ids = array();
		$user_ids = array();
		for ($i = 0; $i < $problems->num_rows; ++$i) {
			$problem = api_autograder_canonicalize_problem($problems->fetch_assoc());
			$id = $problem['problem_id'];
			array_push($ordered_problem_ids, $id);
			$output['problem_'.$id] = $problem;
		}
		$output['ordered_problem_ids'] = $ordered_problem_ids;
		if (count($user_ids) > 0) {
			$user_infos = api_account_fetch_mini_profiles($user_ids);
			$output = array_merge($output, $user_infos);
		}
		
		if ($show_golf_scores && count($ordered_problem_ids) > 0 && $user_id > 0) {
			$scores = sql_query("SELECT `problem_id`,`language_id`,`code_size` FROM `code_solutions` WHERE `user_id` = ".$user_id." AND `problem_id` IN (".implode(', ', $ordered_problem_ids).")");
			for ($i = 0; $i < $scores->num_rows; ++$i) {
				$score = $scores->fetch_assoc();
				$output['score_'.$score['problem_id'].'_'.$score['language_id']] = $score;
			}
		}
		return api_success($output);
	}
	
	function api_autograder_get_language_info($language_key_or_id) {
		if (''.$language_key_or_id == ''.intval($language_key_or_id)) {
			return sql_query_item("SELECT * FROM `languages` WHERE `language_id` = ".intval($language_key_or_id)." LIMIT 1");
		} else {
			return sql_query_item("SELECT * FROM `languages` WHERE `key` = '".sql_sanitize_string($language_key_or_id)."' LIMIT 1");
		}
	}
	
	function api_autograder_get_language_infos($auto_graded_only = false) {
		$languages = sql_query("SELECT * FROM `languages` ".(!!$auto_graded_only ? 'WHERE `auto_grader_supported` = 1' : '')." ORDER BY `name`");
		$output = array();
		for ($i = 0; $i < $languages->num_rows; ++$i) {
			$language = $languages->fetch_assoc();
			array_push($output, $language);
		}
		return $output;
	}
?>