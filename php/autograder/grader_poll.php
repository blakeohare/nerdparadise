<?
	$suppress_skin = true;
	function execute($request) {
		if ($request['method'] == 'GET' && $request['path'] == '/autograder/graderpoll') {
			$tokens = api_autograder_get_work_queue();
			return build_response_ok('', 'OK,'.count($tokens).','.implode(',', $tokens));
		}
		
		if ($request['method'] == 'POST' && $request['path_parts'][1] == 'graderpoll' && count($request['path_parts']) == 3) {
			$action = $request['path_parts'][2];
			$key = strtolower(trim($request['form']['key']));
			$token = trim($request['form']['token']);
			
			switch ($action) {
				case 'claim':
					$result = api_autograder_claim_by_grader($token, $key);
					if ($result['ERROR']) return build_response_ok('ERR,'.$result['message']);
					return build_response_ok('', 
						implode(',', array(
							'OK', 
							string_to_hex($result['language']),
							string_to_hex($result['code']),
							string_to_hex($result['callback']),
							string_to_hex($result['tests']),
							string_to_hex($result['feature']))));
							
				case 'setstatus':
					$result = api_autograder_set_status($token, $key, $request['form']['status']);
					if ($result['ERROR']) return build_response_ok('ERR,'.$result['message']);
					return build_response_ok('', 'OK');
				
				case 'finish':
					$result = api_autograder_report_conclusion($token, $key, $request['form']['output'], $request['form']['callback']);
					if ($result['ERROR']) return build_response_ok('ERR,'.$result['message']);
					return build_response_ok('', 'OK');
					
				default:
					return build_response_ok('', 'ERR,not found');
			}
		}
		
		return build_response_ok('', 'ERR,not found');
	}
?>