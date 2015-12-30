<?
	function generate_header($title, $request) {
		$output = array(
			'<!DOCTYPE html>',
			'<html>',
			'<head>',
			' <title>'.htmlspecialchars($title).'</title>',
			' <link rel="shortcut icon" href="/favicon.ico">',
			' <link rel="stylesheet" type="text/css" href="/css/styles.css" />',
			' <link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet" type="text/css">',
			' <script type="text/javascript" src="/js/common.js"></script>',
			' <meta http-equiv="content-type" content="text/html;charset=utf-8" />',
			'</head>',
			'<body style="background-color:#111;">',
			'<div style="color:#fff; height:80px; background-image:url(/images/top_gradient.png); border-bottom:1px solid #888;">',
			'<div style="float:right; width:300px;">',
			$request['user_id'] == 0
				? '<a href="/login">Log In</a> | <a href="/register">Register</a>'
				: 'Logged in as <a href="/profiles/'.$request['login_id'].'">'.htmlspecialchars($request['name']).'</a> | <a href="/logout">Log Out</a>',
			'</div>',
			'NP',
			'</div>',
			'<div style="clear:both; background-color:#eee;">',
			'<div style="width:960px; background-color:#fff; padding-top:20px;">',
			
			'');
			
		return implode("\n", $output);
	}
	
	function generate_footer($request) {
		
		$output = array(
			'',
			'</div>',
			'</div>',
			'<div style="font-family: &quot;Consola&quot;, monospace; font-size:10px; color:#444; padding:8px;">',
			nl2br(str_replace(' ', '&nbsp;', htmlspecialchars(universal_to_string($request)))),
			'</div>',
			'</body>',
			'</html>');
		
		return implode("\n", $output);
	}
?>