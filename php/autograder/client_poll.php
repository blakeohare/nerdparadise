<?
	function client_poll_encode_response($values) {
		$output = array();
		foreach ($values as $key => $value) {
			array_push($output, string_to_hex($key).':'.string_to_hex($value));
		}
		return implode(',', $output);
	}
	
	$suppress_skin = true;
	function execute($request) {
		
		if ($request['method'] == 'POST') {
			switch (strtolower(trim($request['form']['action']))) {
				case 'create':
					
					$feature = strtolower(trim($request['form']['feature']));
					$code = $request['form']['code'];
					$language = $request['form']['language'];
					$problem_id = intval($request['form']['problem_id']);
					switch ($feature) {
						case 'tinker':
							$result = api_autograder_create_new_tinker_item($request['user_id'], $language, $code);
							break;
						case 'practice':
							$result = api_autograder_create_new_practice_item($request['user_id'], $language, $code, $problem_id);
							break;
						case 'golf':
							$result = api_autograder_create_new_golf_item($request['user_id'], $language, $code, $problem_id);
							break;
						default:
							return build_response_ok('', client_poll_encode_response(array('type' => 'error', 'msg' => 'what just happened?')));
					}
					if ($result['ERROR']) {
						return build_response_ok('', client_poll_encode_response(array('type' => 'error', 'msg' => $result['message'])));
					}
					return build_response_ok('', client_poll_encode_response(array('type' => 'ok', 'token' => $result['token'])));
				
				case 'poll':
					$token = $request['form']['token'];
					$result = api_autograder_get_item_status_for_client($token);
					if ($result['ERROR']) return build_response_ok('', client_poll_encode_response(array('type' => 'error', 'msg' => $result['message'])));
					$type = 'info';
					$message = $result['state'];
					if  ($result['state'] == 'DONE') {
						$message = $result['output'];
						$type = 'output';
					} else if (strpos($result['state'], 'ERROR_') === 0) {
						$type = 'error';
					} else {
						$type = 'state';
					}
					return build_response_ok('', client_poll_encode_response(array('type' => $type, 'msg' => $message, 'token' => $token)));
					
				default:
					break;
			}
		}
		return build_response_ok('', 'wat?');
	}
?>