<?
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
	
?>