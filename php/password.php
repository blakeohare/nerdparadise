<?
	function execute($request) {
		$is_post = $request['method'] == "POST";
		if ($request['path_parts'] == 1) {
			if ($is_post) {
				return execute_send_email($request);
			} else {
				return execute_type_email($request);
			}
		} else if ($request['path_parts'] == 2) {
			if ($is_post) {
				return exeucte_fullfill_reset($request);
			} else {
				return execute_token_provided($request);
			}
		} else {
			return not_found_impl();
		}
	}
	
	// email or username provided. send email with token to that name.
	function execute_send_email($request) {
		$result = api_account_request_password_change_token($request['form']['pw_reset_username_or_email']);
		
		$message = "";
		
		if ($result['OK']) {
		
			$output = array(
				'<h1>Password Reset Request</h1>',
				'<p>',
				"Instructions to reset your password have been emailed to you.",
				'</p>',
				);
			
			return build_response_ok("Password Reset Request", implode("\n", $output));
		}
		
		switch ($result['message']) {
			case 'NOT_FOUND': $error = "No username or email was found in the database matching that."; break;
			default: $error = "Server returned error: ".$result['message'];
		}
		
		return execute_type_email($request, $error);
	}
	
	function execute_type_email($request, $error = null) {
		$output = array(
			'<h1>Oh no!</h1>',
			'<p>',
			"Enter the username of your account, or the email address associated with it. An email will be sent to you with a link to reset it.",
			'</p>',
			
			$error == null
				? ''
				: '<div style="color:#f00;">'.htmlspecialchars($error).'</div>',
			
			'<form action="'.$request['path'].'" method="post">',
			
			'<div>',
			"Username or Email: ",
			'<input type="text" name="pw_reset_username_or_email" value="'.htmlspecialchars($request['form']['pw_reset_username_or_email']).'" />',
			'<input type="submit" name="pw_reset_submit" value="Doot" />',
			'</div>',
			
			'</form>',
			);
		
		return build_response_ok("Password Reset Request", implode("\n", $output));
	}
	
	function exeucte_fullfill_reset($request) {
		$token = $request['url_parts'][1];
		
		$result = api_account_reset_password($request['url_parts'][1]);
		
		if ($result['OK']) {
			$output = array(
				'<h1>Password Reset</h1>',
				
				'<p>Your password has been reset to: <span$result['new_password']
		}
		
	}
?>