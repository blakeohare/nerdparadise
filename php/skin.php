<?
	function generate_header($title, $request, $js, $css, $onload) {
		$css = array_merge(array('styles.css'), $css);
		$js = array_merge(array('common.js'), $js);
		$css_html = array();
		$js_html = array();
		foreach ($css as $css_item) {
			array_push($css_html, ' <link rel="stylesheet" type="text/css" href="/css/'.$css_item.'" />');
		}
		foreach ($js as $js_item) {
			array_push($js_html, ' <script type="text/javascript" src="/js/'.$js_item.'"></script>');
		}
		$css = implode("\n", $css_html);
		$js = implode("\n", $js_html);
		
		$output = array(
			'<!DOCTYPE html>',
			'<html>',
			'<head>',
			' <title>'.htmlspecialchars($title).'</title>',
			' <link rel="shortcut icon" href="/favicon.ico">',
			$css,
			' <link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet" type="text/css">',
			$js,
			' <meta http-equiv="content-type" content="text/html;charset=utf-8" />',
			'</head>',
			'<body style="background-color:#111;"'.(count($onload) == 0 ? '' : 'onload="'.implode(';', $onload).'"').'>',
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
		
		$output = array();
		array_push($output, '</div>');
		array_push($output, '</div>');
		
		if ($request['is_admin']) {
			array_push($output, '<div style="font-family: &quot;Consola&quot;, monospace; font-size:10px; color:#444; padding:8px;">');
			array_push($output, nl2br(str_replace(' ', '&nbsp;', htmlspecialchars(universal_to_string($request)))));
			
			array_push($output, '<br /><br />');
			array_push($output, 'Queries:<table border="1">');
			foreach (sql_get_logged_queries() as $query) {
				$sql = $query[0];
				$time = $query[1];
				$bg_color = '#000';
				$fg_color = '#888';
				if ($time > .01) {
					$bg_color = '#f00';
					$fg_color = '#000';
				}
				array_push($output, '<tr style="color:'.$fg_color.'; background-color:'.$bg_color.'"><td>'.htmlspecialchars($sql).'</td><td>'.$time.'</td></tr>');
			}
			array_push($output, '</table>');
			array_push($output, '</div>');
		}
		array_push($output, '</body>');
		array_push($output, '</html>');
		
		return implode("\n", $output);
	}
?>