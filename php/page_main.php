<?
	
	function main_page_int_to_hex($n) {
		$hex = '0123456789abcdef';
		$n = intval($n);
		if ($n > 255) $n = 255;
		if ($n < 0) $n = 0;
		
		return $hex[$n >> 4].$hex[$n & 15];
	}
	
	function main_page_wrap_widget($title, $link, $color_name, $html) {
		$color = array(255, 0, 255);
		switch ($color_name) {
			case 'red': $color = array(160, 20, 64); break;
			case 'green': $color = array(20, 160, 64); break;
			case 'blue': $color = array(40, 80, 160); break;
			default: break;
		}
		
		$lighter = array(
			(255 - (255 - $color[0]) * .5) * .5 + 127,
			(255 - (255 - $color[1]) * .5) * .5 + 127,
			(255 - (255 - $color[2]) * .5) * .5 + 127);
		
		$header_color = main_page_int_to_hex($color[0]).
						main_page_int_to_hex($color[1]).
						main_page_int_to_hex($color[2]);
		
		$bar_color = main_page_int_to_hex($lighter[0]).
					main_page_int_to_hex($lighter[1]).
					main_page_int_to_hex($lighter[2]);
		
		
		return implode("\n", array(
			//'<div style="margin-left:8px; margin-bottom:20px; border-left:8px solid #'.$bar_color.';">',
			'<div style="padding-left:8px;">',
			'<h2 class="hfont">',
			'<a href="'.$link.'" style="text-decoration:none; color:#'.$header_color.'">'.$title.'</a>',
			'</h2>',
			'<div style="font-size:12px; padding-bottom:25px;">',
			$html,
			'</div>',
			'</div>',
			//'</div>'
			));
	}
	
	function main_page_about_widget($request) {
		$shpiel = array(
			"Nerd Paradise started in 1999 as my dumping ground for my video game MIDI collection and Rubik's Cube solutions. Somehow it's now a site for programmers. You can read all about it <a href=\"/about\">here</a>.",
			);
		return main_page_wrap_widget('About', '/about', 'red', '<p>'.implode('</p><p>', $shpiel).'</p>');
	}
	
	function main_page_achievements_widget($request) {
		return main_page_wrap_widget('Achievements', '/achievements', 'red', 'Lorem ipsum dolar sit amet.');
	}
	
	function main_page_forum_widget($request) {
		return main_page_wrap_widget('Forum', '/forum', 'red', 'Lorem ipsum dolar sit amet.');
	}

	function main_page_tutorials_widget($request) {
		return main_page_wrap_widget('Tutorials', '/tutorials', 'green', 'Lorem ipsum dolar sit amet.');
	}

	function main_page_practice_widget($request) {
		return main_page_wrap_widget('Practice', '/practice', 'green', 'Lorem ipsum dolar sit amet.');
	}

	function main_page_tinker_widget($request) {
		return main_page_wrap_widget('Tinker', '/tinker', 'green', 'Lorem ipsum dolar sit amet.');
	}

	function main_page_code_golf_widget($request) {
		return main_page_wrap_widget('Code Golf', '/golf', 'blue', 'Lorem ipsum dolar sit amet.');
	}

	function main_page_competitions_widget($request) {
		return main_page_wrap_widget('Competitions', '/comps', 'blue', 'Lorem ipsum dolar sit amet.');
	}

	function main_page_game_jam_widget($request) {
		return main_page_wrap_widget('Game Jams', '/gamejams', 'blue', 'Lorem ipsum dolar sit amet.');
	}

	function main_page_render_marquee($request) {
		$output = array(
			// dynamically set the background color (and possibly fade it) based on the active marquee image.
			'<div style="height:330px;background-color:#000;">',
			
			'<div style="float:left; background-color:#008; width:600px; height:300px;">',
			// background image
			'Image',
			'</div>',
			
			'<div style="float:right; width:300px; padding:8px;">',
			'Text content2',
			'</div>',
			
			'<div style="clear:both; background-color:#888; color:#fff; height:30px; text-align:center;">',
			// these'll be images
			'o o o o o O o',
			'</div>',
			'</div>'
			);
		
		return implode("\n", $output);
	}

	function execute($request) {
		$output = array(
			'<div>',
			main_page_render_marquee($request),
			'</div>',
			
			'</div>', // end main block
			
			'<div style="padding-top:20px;">',
			
				// column 1
				'<div style="float:left; width:306px;">',
				
					// About
					'<div class="block">',
					$request['user_id'] == 0
						? main_page_about_widget($request)
						: main_page_achievements_widget($request),
					'</div>',
					
					// Forum
					'<div class="block" style="margin-top:20px;">',
					main_page_forum_widget($request),
					'</div>',
				
				'</div>',
				
				// column 2
				'<div style="float:left; width:327px; margin-left:20px;">',
				
					// Tutorials
					'<div class="block">',
					main_page_tutorials_widget($request),
					'</div>',
					
					// Practice
					'<div class="block" style="margin-top:20px;">',
					main_page_practice_widget($request),
					'</div>',
				
					// Tinker
					'<div class="block" style="margin-top:20px;">',
					main_page_tinker_widget($request),
					'</div>',
				
				'</div>',
				
				// column 2
				'<div style="float:left; width:326px; margin-left:20px;">',
				
					// Code Golf
					'<div class="block">',
					main_page_code_golf_widget($request),
					'</div>',
					
					// Competitions
					'<div class="block" style="margin-top:20px;">',
					main_page_competitions_widget($request),
					'</div>',
					
					// Game Jams
					'<div class="block" style="margin-top:20px;">',
					main_page_game_jam_widget($request),
					'</div>',
				'</div>',
				
			
				'<div style="clear:both;"></div>',
			'</div>',
			
			// re-open main block
			'<div class="fullblock" style="margin-top:20px;">',
			'lorem ipsum',
		);
		
		return build_response_ok("Nerd Paradise", implode("\n", $output));
	}
?>