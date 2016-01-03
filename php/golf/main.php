<?
	function execute($request) {
		$languages = api_autograder_get_language_infos(true);
		$problems_and_scores = api_autograder_menu_get_problems($request['user_id'], 'golf', 0, true);
		$ordered_problem_ids = $problems_and_scores['ordered_problem_ids'];
		$output = array('<h1>Code Golf</h1>',
			"<p><a href=\"https://en.wikipedia.org/wiki/Code_golf\">Code Golf</a> is a competition to see who can solve a programming problem using the fewest [key] \"strokes\".</p>",
			'<p>'.
				"A new problem is posted every 2 weeks. ".
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
			);
		
		
		
		array_push($output, '<table cellspacing="0" cellpadding="4"><tr style="font-size:14px; font-weight:bold;"><td></td>');
		foreach ($languages as $language) {
			array_push($output, '<td style="padding-right:30px;">');
			array_push($output, '<img src="/images/languages/'.htmlspecialchars($language['key']).'_small.png" valign="middle" />');
			array_push($output, htmlspecialchars($language['name']));
			array_push($output, '</td>');
		}
		array_push($output ,'</tr>');
		
		$alt = true;
		foreach ($ordered_problem_ids as $problem_id) {
			$problem_info = $problems_and_scores['problem_'.$problem_id];
			
			$alt = !$alt;
			array_push($output, '<tr style="text-align:center;background-color:#'.($alt ? 'fff' : 'eee').'"><td style="text-align:left;"><a href="/golf/'.$problem_id.'">');
			array_push($output, htmlspecialchars($problem_info['title']));
			array_push($output, '</td>');
			foreach ($languages as $language) {
				$score = $problems_and_scores['score_'.$problem_id.'_'.$language['language_id']];
				if (intval($score['code_size']) > 0) {
					array_push($output, '<td>');
					array_push($output, $score['code_size']); // TODO: some sort of indication of your rank, which is probably more interesting
				} else {
					array_push($output, '<td style="color:#888;">');
					array_push($output, 'N/A');
				}
				array_push($output, '</td>');
			}
			array_push($output, '</tr>');
		}
		
		array_push($output, '</table>');
		
		return build_response_ok("Code Golf", implode("\n", $output));
	}
?>