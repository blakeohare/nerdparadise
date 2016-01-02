<?
	function execute($request) {
		$problem_id = intval($request['path_parts'][1]);
		$problem = api_autograder_menu_get_problem($request['user_id'], 'golf', 0, $problem_id);
		
		if ($problem == null) return build_response_not_found("Golf Problem not found.");
		
		$output = array(
			'<h1>',
			htmlspecialchars($problem['title']),
			'</h1>',
			
			'<div>',
			nl2br(htmlspecialchars($problem['statement'])),
			'</div>',
			
			api_autograder_generate_client_html('golf', null, '', $problem_id));
		
		return build_response_ok("Code Golf: " . $problem['title'], implode("\n", $output), array('js' => 'autograder.js', 'onload' => "ag_init('golf')"));
	}
?>