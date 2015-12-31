<?
	$suppress_skin = true;
	function execute($request) {
		if ($request['method'] == 'GET' && $request['path'] == '/autograder/poll') {
			$tokens = api_autograder_get_work_queue();
			return build_response_ok('', 'OK,'.count($tokens).','.implode(',', $tokens));
		}
		
		return build_response_not_found('', 'invalid');
	}
?>