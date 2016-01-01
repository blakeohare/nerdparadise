<?
	
	function execute($request) {
		
		$language_info = api_autograder_get_language_info($request['path_parts'][1]);
		
		if ($language_info == null) {
			return build_response_not_found("Language not found.");
		}
		$language_key = $language_info['key'];
		$language_id = $language_info['language_id'];
		
		$problems = api_autograder_menu_get_problems($request['user_id'], 'practice', 0, $language_id);
		
		debug_print($problems);
		
		if ($problems['OK']) {
			$output = array(
				'<h1>'.htmlspecialchars($language_info['name']).' Practice Problems</h1>'
				);
			
			if (count($problems['ordered_problem_ids']) == 0) {
				array_push($output, "<div>Empty!</div>");
			}
			
			foreach ($problems['ordered_problem_ids'] as $problem_id) {
				$problem_info = $problems['problem_'.$problem_id];
				array_push($output, '<div><a href="/practice/'.$language_key.'/'.$problem_id.'">');
				array_push($output, htmlspecialchars($problem_info['title']));
				array_push($output, '</a></div>');
			}
			
			return build_response_ok($language_info['title']." Practice Problems", implode("\n", $output));
		} else if ($problems['message'] == 'INVALID_LANGUAGE') {
			return build_response_not_found("Language not found.");
		}
	}
	
?>