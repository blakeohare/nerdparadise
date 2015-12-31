<?
	function fiddle_encode_response($values) {
		$output = array();
		foreach ($values as $key => $value) {
			array_push($output, string_to_hex($key).':'.string_to_hex($value));
		}
		return implode(',', $output);
	}
	
	$suppress_skin = true;
	function execute($request) {
		
		if ($request['method'] == 'POST') {
			$code = $request['form']['code'];
			$language = $request['form']['language'];
			
			$result = api_autograder_create_new_fiddle_item($request['user_id'], $language, $code);
			if ($result['ERROR']) {
				return build_response_ok('', fiddle_encode_response(array('type' => 'error', 'msg' => $result['message'])));
			}
			return build_response_ok('', fiddle_encode_response(array('type' => 'ok', 'token' => $result['token'])));
		}
		return build_response_ok('', fiddle_encode_response(array('type' => 'state', 'state' => 'spinning')));
	}
?>