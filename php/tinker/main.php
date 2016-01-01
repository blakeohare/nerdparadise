<?
	function execute($request) {
	
		$html = api_autograder_generate_client_html('tinker', null, '', 0);
	
		return build_response_ok("Code Tinker", $html, array('js' => 'autograder.js', 'onload' => "ag_init('tinker')"));
	}
?>