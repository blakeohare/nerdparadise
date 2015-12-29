<?
	function generate_header($title, $request) {
		$output = array(
			'<!DOCTYPE html>',
			'<html>',
			'<head>',
			' <title>'.htmlspecialchars($title).'</title>',
			' <link rel="shortcut icon" href="/favicon.ico">',
			' <link rel="stylesheet" type="text/css" href="/css/styles.css" />',
			' <script type="text/javascript" src="/js/common.js"></script>',
			' <meta http-equiv="content-type" content="text/html;charset=utf-8" />',
			'</head>',
			'<body>',
			'<div>',
			'');
			
		return implode("\n", $output);
	}
	
	function generate_footer($request) {
		
		$output = array(
			'',
			'</div>',
			'<div style="font-family: &quot;Consola&quot;, monospace; font-size:10px; background-color:#eee; color:#444; padding:8px;">',
			nl2br(str_replace(' ', '&nbsp;', htmlspecialchars(universal_to_string($request)))),
			'</div>',
			'</body>',
			'</html>');
		
		return implode("\n", $output);
	}
?>