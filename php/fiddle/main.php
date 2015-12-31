<?
	function execute($request) {
	
		$output = implode("\n", array(
			'<div>',
				'<h2>Write some code</h2>',
				
				'<div>',
				"Language: ",
				'<select id="fiddle_language">',
					'<option value="crayon">Crayon</option>',
					'<option value="python">Python</option>',
				'</select>',
				'</div>',
				
				'<div>',
				'<textarea id="fiddle_code" style="width:900px; height:400px;"></textarea>',
				'</div>',
				
				// mitigate spam bots, to at least a small degree, by populating the button via JavaScript
				'<div onload="fiddle_build_button()" id="fiddle_button_host"></div>',
			
			'</div>',
			
			'<div>',
				'<h2>Output</h2>',
				'<div id="fiddle_output_host"></div>',
			'</div>',
			));
	
		return build_response_ok("Code Fiddle", $output, array('js' => 'fiddle.js', 'onload' => 'fiddle_init()'));
	}
?>