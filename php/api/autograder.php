<?
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
	
	
	function api_autograder_create_new_fiddle_item($user_id, $language, $code) {
		return api_autograder_create_new_item($user_id, 'fiddle', '', $language, $code, null, null);
	}
	
	function api_autograder_create_new_item(
		$user_id,
		$feature,
		$callback_arg,
		$language,
		$code,
		$test_input_array,
		$test_output_array) {
		
		switch ($language) {
			case 'crayon':
			case 'python':
				break;
			default:
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
			'language' => $language,
			'code' => $code,
			'tests' => '', // TODO: this
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
	
	function api_autograder_claim_by_grader($token, $key) {
		if (!api_autograder_is_key_valid($token, $key)) {
			return api_error("WRONG_KEY");
		}
		
		$item = sql_query_item("SELECT * FROM `auto_grader_queue` WHERE `token` = '".sql_sanitize_string($token)."' LIMIT 1");
		if ($item == null) return api_error("NOT_FOUND");

		$item_id = intval($item['item_id']);
		$language = $item['language'];
		$code = $item['code'];
		$tests = $item['tests'];
		$callback = $item['callback_arg'];
		$feature = $item['feature'];
		
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
			'tests' => $tests,
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
	
?>