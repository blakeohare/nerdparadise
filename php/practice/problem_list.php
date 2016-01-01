<?
	
	function execute($request) {
		
		$language_key = $request['path_parts'][1];
		
		$language_info = api_practice_get_problems($request['user_id'], $request['path_parts'][1]);
		
		if ($problems['OK']) {
			$output = array();
			$language = $language_info['name'];
			
			foreach ($language_info['ordered_problem_ids'] as $problem_id) {
				$problem_info = $language['problem_'.$problem_id];
				array_push($output, '<div><a href="/practice/'.$language_key.'/'.$problem_id.'">');
				array_push($output, htmlspecialchars($problem_info['title']));
				array_push($output, '</a></div>');
			}
			
			
			return build_response_ok('.', '.');
		} else if ($problems['message'] == 'INVALID_LANGUAGE') {
			return build_response_not_found("Language not found.");
		}
	}
	
?>