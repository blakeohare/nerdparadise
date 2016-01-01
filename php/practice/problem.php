<?
	
	function execute($request) {
		$language_info = api_autograder_get_language_info($request['path_parts'][1]);
		$problem_id = intval($request['path_parts'][2]);
		if ($problem_id == 0 || $language_info == null) {
			return build_response_not_found("Problem not found.");
		}
		$problem = api_autograder_menu_get_problem($request['user_id'], $language_info['language_id'], 'practice', 0, $problem_id);
		
		if ($problem == null) {
			return build_response_not_found("Problem not found.");
		}
		
		$output = array(
			'<h1>'.htmlspecialchars($problem['title']).'</h1>',
			'<div>',
			nl2br(htmlspecialchars($problem['statement'])),
			'</div>',
			api_autograder_generate_client_html('practice', $language_info, $problem['template'], $problem_id)
			);
			
		
		return build_response_ok('Problem', implode("\n", $output), array('js' => 'autograder.js', 'onload' => "ag_init('practice')"));
	}
	
?>