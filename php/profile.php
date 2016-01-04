<?
	function execute($request) {
		
		$user_info = api_account_lookup_user_by_name($request['path_parts'][1]);
		
		if ($user_info == null) {
			return build_response_not_found("Not account by that name exists.");
		}
		$user_id = $user_info['user_id'];
		
		$output = array('<h1>'.htmlspecialchars($user_info['name']).'</h1>');
		
		$profile = sql_query_item("SELECT * FROM `user_profiles` WHERE `user_id` = $user_id LIMIT 1");
		
		if ($profile == null) {
			$profile = array();
		}
		
		if (strlen($user_info['image_id']) > 0) {
			array_push($output,
				'<div>',
				'<img src="/uploads/avatars/'.$user_info['image_id'].'" />',
				'</div>');
		}
		
		$blurb = trim($profile['blurb']);
		if (strlen($blurb) > 0) {
			array_push($output, 
				'<div>',
				nl2br(htmlspecialchars($blurb)),
				'</div>');
		}
		
		array_push($output, 
			'<div style="padding-top:100px; font-style:italic; color:#888;">',
			"More interesting stuff will be put here, I promise.",
			'</div>');
		
		return build_response_ok($user_info['name'], implode("\n", $output));
	}
?>