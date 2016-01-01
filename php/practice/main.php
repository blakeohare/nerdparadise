<?
	function execute($request) {
		$output = array(
			'<h1>',
			"Coding practice problems",
			'</h1>',
			);
		
		$languages = sql_query("SELECT * FROM `languages` WHERE `auto_grader_supported` = 1 ORDER BY `name`");
		
		for ($i = 0; $i < $languages->num_rows; ++$i) {
			$language = $languages->fetch_assoc();
			if ($language['key'] == 'python2x') continue; 
			
			array_push($output, '<div><a href="/practice/'.$language['key'].'">'.htmlspecialchars($language['name']).'</a></div>');
		}
			
		return build_response_ok("Practice Problems", implode("\n", $output));
	}
?>