<?
	function execute($request) {
		$languages = api_autograder_get_language_infos(true);
		$problems_and_scores = api_autograder_menu_get_problems($request['user_id'], 'golf', 0, true);
		$ordered_problem_ids = $problems_and_scores['ordered_problem_ids'];
		$output = array('<h1>Code Golf</h1>');
		
		array_push($output, '<table><tr><td></td>');
		foreach ($languages as $language) {
			array_push($output, '<td><img src="/images/languages/'.htmlspecialchars($language['key']).'_small.png" /></td>');
		}
		array_push($output ,'</tr>');
		
		foreach ($ordered_problem_ids as $problem_id) {
			$problem_info = $problems_and_scores['problem_'.$problem_id];
			array_push($output, '<tr><td><a href="/golf/'.$problem_id.'">');
			array_push($output, htmlspecialchars($problem_info['title']));
			array_push($output, '</td>');
			foreach ($languages as $language) {
				$score = $problems_and_scores['score_'.$problem_id.'_'.$language['language_id']];
				array_push($output, '<td>');
				if (intval($score['code_size']) > 0) {
					array_push($output, $score['code_size']); // TODO: some sort of indication of your rank, which is probably more interesting
				}
				array_push($output, '</td>');
			}
			array_push($output, '</tr>');
		}
		
		array_push($output, '</table>');
		
		return build_response_ok("Code Golf", implode("\n", $output));
	}
?>