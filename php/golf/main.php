<?
	function execute($request) {
		$languages = api_autograder_get_language_infos(true);
		$problems_and_scores = api_autograder_menu_get_problems($request['user_id'], $request['is_admin'], 'golf', 0, true);
		$ordered_problem_ids = $problems_and_scores['ordered_problem_ids'];
		$output = array('<h1>Code Golf</h1>',
			"<p><a href=\"https://en.wikipedia.org/wiki/Code_golf\">Code Golf</a> is a competition to see who can solve a programming problem using the fewest [key] \"strokes\".</p>",
			'</div>');
		
		$now = time();
		// TODO: migrate to api layer
		$current_challenge = api_autograder_canonicalize_problem(sql_query_item("SELECT * FROM `code_problems` WHERE `type` = 'golf' AND `golf_start_time` <= $now AND `golf_end_time` > $now LIMIT 1"));
		
		array_push($output,
			'<div style="padding-top:20px; margin-bottom:20px;">',
			
			'<div class="block" style="float:left; width:460px;">',
			'<p>'.
				"A new problem is posted every <s>2 weeks</s> once in a while. ".
				"During that time you can submit solutions. ".
				"Once time is up, the highest ranking (shortest) solutions will be awarded points. ".
				"You may still submit solutions after time is up for practice, but they won't be recorded for scores.".
			'</p>',
			'<p>Points are granted as follows on a per-language basis:</p>',
			'<ul>',
			'<li>First place: 3 points</li>',
			'<li>Second place: 2 points</li>',
			'<li>Third through fifth: 1 point</li>',
			'</ul>',
			'<p>Preference is given to earlier solutions in the event of ties. The maximum points you can receive is 3 &times; {number of languages}.</p>',
			'<p>More about <a href="/about#points">NP points</a>.</p>',
			'<p>Want a reminder every 2 weeks? New Golf questions will be announced via <a href="https://twitter.com/nerdparadise">twitter</a>.</p>',
			'</div>',
			
			'<div class="block" style="float:left; margin-left:20px; width:400px;">');
		if ($current_challenge == null) {
			array_push($output, 
				'<h2>Current Challenge: None</h2>',
				'<div>Check back soon or poke <a href="/profiles/blake">Blake</a></div>');
		} else {
			array_push($output,
				'<h2>Current Challenge: <a href="/golf/'.$current_challenge['problem_id'].'">'.htmlspecialchars($current_challenge['title']).'</a></h2>',
				'<div><span style="color:#048; font-weight:bold;">'.seconds_to_duration($current_challenge['golf_end_time'] - time()).'</span> Remain.</div>',
				'');
		}
		array_push($output, 
			
			'</div>',
			
			'</div>' // non-block div
			);
		
		array_push($output, 
			'<div style="clear:left; padding-top:20px;">',
			'<div class="fullblock">',
			'<h2>All Challenges</h2>');
		
		
		array_push($output, '<table cellspacing="0" cellpadding="4"><tr style="font-size:14px; font-weight:bold;"><td></td><td></td>');
		foreach ($languages as $language) {
			array_push($output, '<td style="padding-right:30px;">');
			array_push($output, '<img src="/images/languages/'.htmlspecialchars($language['key']).'_small.png" valign="middle" />');
			array_push($output, htmlspecialchars($language['name']));
			array_push($output, '</td>');
		}
		array_push($output ,'</tr>');
		
		$now = time();
		$alt = true;
		foreach ($ordered_problem_ids as $problem_id) {
			$problem_info = $problems_and_scores['problem_'.$problem_id];
			
			$is_active = $now < $problem_info['golf_end_time'];
			
			$alt = !$alt;
			
			$bg_color = $is_active ? 'cde' : ($alt ? 'fff' : 'eee');
			
			array_push($output,
				'<tr style="'.($is_active ? 'font-weight:bold;' : '').'text-align:center;background-color:#'.$bg_color.';">',
				'<td style="text-align:left;"><a href="/golf/'.$problem_id.'">',
				htmlspecialchars($problem_info['title']),
				'</a></td>',
				'<td>');
			
			if ($is_active) {
				array_push($output, "Ends: ".unix_to_scaling_time($problem_info['golf_end_time']));
			} else {
				array_push($output, "Ended: ".unix_to_scaling_time($problem_info['golf_start_time']));
			}
			
			array_push($output, '</td>');
			foreach ($languages as $language) {
				$score = $problems_and_scores['score_'.$problem_id.'_'.$language['language_id']];
				if (intval($score['code_size']) > 0) {
					array_push($output, '<td>');
					array_push($output, $score['code_size']);
					array_push($output, ' (#'.$score['integer_rank'].')'); // TODO: little trophy images.
				} else {
					array_push($output, '<td style="color:#888;">');
					array_push($output, 'N/A');
				}
				array_push($output, '</td>');
			}
			array_push($output, '</tr>');
		}
		
		array_push($output, '</table>');
		
		array_push($output, '</div>');
		
		return build_response_ok("Code Golf", implode("\n", $output));
	}
?>