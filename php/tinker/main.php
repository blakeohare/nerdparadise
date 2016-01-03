<?
	function execute($request) {
		
		$html = api_autograder_generate_client_html('tinker', null, '', 0);
	
		$output = array(
			'<h1>Code Tinker</h1>',
			
			"<p>",
			"Write some code. Run it. See the output. ",
			"All code runs in a remote sandbox. ",
			"For more guided practice, see the <a href=\"/practice\">practice problems</a> or <a href=\"/tutorials\">tutorials</a> if you're just starting out. ",
			"</p>",
			
			$html,
			
			);
		
		return build_response_ok(
			"Code Tinker",
			implode("\n", $output),
			array('js' => 'autograder.js', 'onload' => "ag_init('tinker')"));
	}
?>