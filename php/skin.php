<?
	function generate_header($title, $request, $js, $css, $onload) {
		$css = array_merge(array('styles.css'), $css);
		$js = array_merge(array('common.js', 'conway.js'), $js);
		$onload = array_merge(array('conway_init()'), $onload);
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
		
		// TODO: generate with JS
		$the_grid = array();
		array_push($the_grid, '<table cellspacing="1" cellpadding="0" border="0">');
		for ($y = 0; $y < 16; ++$y) {
			array_push($the_grid, '<tr>');
			for ($x = 0; $x < 16; ++$x) {
				array_push($the_grid, '<td class="tg" id="tg_'.$x.'_'.$y.'"></td>');
			}
			array_push($the_grid, '</tr>');
		}
		array_push($the_grid, '</table>');
		
		
		$output = array(
			'<!DOCTYPE html>',
			'<html>',
			'<head>',
			' <title>'.htmlspecialchars($title).'</title>',
			' <link rel="shortcut icon" href="/favicon.ico">',
			$css,
			' <link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet" type="text/css">',
			' <link href="https://fonts.googleapis.com/css?family=Share+Tech" rel="stylesheet" type="text/css">',
			$js,
			' <meta http-equiv="content-type" content="text/html;charset=utf-8" />',
			// TODO: put this in a style sheet (but that goes for all the other inline styles as well.
			' <style type="text/css">',
			'  td.tg { width:4px; height:4px; background-color:#fff; }',
			'  .hfont { font-family: "Share Tech", sans-serif; }',
			'  h1, h2, h3, h4, h5, h6 { margin:0px; }',
			'  body { background-color:#111; font-size:12px; text-align:justify; }',
			'  a:link, a:visited { text-decoration:none; color:#04f; }',
			'  a:hover { text-decoration:underline; color:#28d;  }',
			'  .main_nav { color:#ddd; font-size:11px; }',
			'  .main_nav a:link, .main_nav a:visited { font-weight: bold; text-decoration:none; color:#fff; }',
			'  .main_nav a:hover { font-weight: bold; text-decoration:underline; color:#ccc; }',
			'  .main_nav td { padding:8px; padding-right:70px;}',
			'  .fullblock { width:920px; background-color:#fff; padding:20px; }',
			'  .block { background-color:#fff; padding:20px; }',
			' </style>',
			
			'</head>',
			'<body'.(count($onload) == 0 ? '' : ' onload="'.implode(';', $onload).'"').'>',
			
			'<div id="top_host" style="height:160px;position:relative; background-color:#000;">',
			
			'<div id="top_canvas_host" style="position:absolute;width:100%;height:160px;">',
				'<canvas id="conways_game_of_life" style="width:100%;height:160px;" />',
			'</div>',
			
			'<div id="top_content_host" style="position:absolute;width:100%;height:160px;">',
			'<div style="color:#fff; height:60px;">',
			
			'<div class="hfont" style="font-size:48px;">',
			'Nerd Paradise',
			'<span style="color:#f00; font-size:14px; font-weight:bold;">(Alpha)</span>',
			'</div>',
			
			'</div>',
			
			'<div class="main_nav">');
			
		array_push($output,
			'<div style="float:right; width:400px; text-align:right; padding:8px;">');
		if ($request['user_id'] == 0) {
			array_push($output, '<a href="/login">Log In</a> | <a href="/register">Register</a>');
		} else {
			array_push($output,
				'Logged in as <a href="/profiles/'.$request['login_id'].'">'.
				htmlspecialchars($request['name']).'</a> | ',
				'<a href="/account">Account Settings</a> | ',
				'<a href="/logout">Log Out</a>');
			
			$avatar = '';
			if ($request['avatar'] != null) {
				$width = $request['avatar']['width'];
				$height = $request['avatar']['height'];
				if ($width > 0 && $height > 0) {
					$ratio = 1.0 * $width / $height;
					if ($width == $height) {
						$width = 25;
						$height = 25;
					} else if ($width > $height) {
						$height = intval($height * 25 / $width);
						$width = 25;
					} else {
						$width = intval($width * 25 / $height);
						$height = 25;
					}
					$avatar = ' <img style="margin-left:30px;" src="/uploads/avatars/'.$request['avatar']['path'].'" valign="middle" width="'.$width.'" height="'.$height.'" />';
					array_push($output, $avatar);
				}
			}
		}
		array_push($output, '</div>');
		
		array_push($output,
			
			'<table>',
				'<!-- Haters gonna hate -->',
				'<tr>',
					'<td><a href="/">Home</a></td>',
					'<td><a href="/tutorials">Tutorials</a></td>',
					'<td><a href="/golf">Code Golf</a></td>',
				'</tr>',
				'<tr>',
					'<td><a href="/about">About</a></td>',
					'<td><a href="/tinker">Tinker</a></td>',
					'<td><a href="/comp">Competitions</a></td>',
				'</tr>',
				'<tr>',
					'<td><a href="/forum">Forum</a></td>',
					'<td><a href="/practice">Practice</a></td>',
					'<td><a href="/jams">Game Jams</a></td>',
				'</tr>',
			'</table>',
			'</div>', // main_nav
			'</div>', // top_content_host
			'</div>', // top_host
			
			'<div style="clear:both; background-color:#eee;">',
			
			
			
			'<div class="fullblock">',
			
			'');
			
		return implode("\n", $output);
	}
	
	function generate_footer($request) {
		
		$output = array();
		array_push($output, '</div>');
		array_push($output, '</div>');
		
		array_push($output, '<div class="main_nav">');
		
		array_push($output, '<div style="padding:12px;">');
		array_push($output, "Join us on IRC: <a href=\"http://webchat.esper.net/?nick=".($request['user_id'] == 0 ? 'Guest_'.generate_gibberish(7) : $request['login_id'])."&channels=nerdparadise&prompt=1\">#nerdparadise on EsperNet</a>");
		array_push($output, '</div>');
		
		array_push($output, '<div style="padding:12px;">');
		array_push($output, "View the Nerd Paradise source code on <a href=\"http://github.com/blakeohare/nerdparadise\">GitHub</a>");
		array_push($output, '</div>');
		
		array_push($output, '<div style="padding:12px;">');
		array_push($output, 'Visit my other sites: <a href="http://blakeohare.com">blakeohare.com</a> | <a href="http://noisyprotozoa.com">Noisy Protozoa</a> | <a href="http://asdfjklsemicolon.com">asdfjkl;</a> | <a href="http://twocansandstring.com">Two Cans &amp; String</a> | <a href="http://crayonlang.org">CrayonLang.org</a>');
		array_push($output, '</div>');
		
		array_push($output, '</div>');
		
		array_push($output, '<div style="color:#666; font-size:11px; text-align:center;">');
		
		array_push($output, '<div style="padding:18px;">');
		array_push($output, 'Site mostly written at Zoka Coffee in Kirkland, WA.');
		array_push($output, '</div>');
		
		array_push($output, '<div style="padding:18px;">');
		array_push($output, '&copy; '.date("Y").' Nerd Paradise');
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