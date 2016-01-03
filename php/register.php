<?
	function execute($request) {
		
		$error = null;
		
		if ($request['method'] == 'POST') {
			$username = trim($request['form']['register_username']);
			$email = trim($request['form']['register_email']);
			$password1 = $request['form']['register_password1'];
			$password2 = $request['form']['register_password2'];
			$tuba = strtolower(string_alphanums($request['form']['register_tos']));
			
			if ($tuba != 'tuba') {
				$error = "Please read the terms of service.";
			} else {
				$result = api_account_register_user($username, $email, $password1, $password2, $request['ip']);
				
				if ($result['OK']) {
					$output = array(
						'<h1>Account Registration</h1>',
						'<div>Registration successful!</div>',
						'<div>You may now <a href="/login">log in</a>.</div>',
						);
					return build_response_ok("Registration Successful", implode("\n", $output));
				} else {
					switch ($result['message']) {
						case 'NAME_BLANK': $error = "Name is blank."; break;
						case 'NAME_INVALID_CHARS': $error = "Name contains invalid characters."; break;
						case 'NAME_TOO_SHORT': $error = "Name is too short."; break;
						case 'NAME_TOO_LONG': $error = "Name is too long."; break;
						case 'NAME_NO_ALPHANUMS': $error = "Name must contain at least 1 alphanumeric character."; break;
						case 'SIMILAR_NAME_EXISTS': $error = "A similar username already exists."; break;
						case 'EMAIL_BLANK': $error = "Email was left blank. Use a mailinator if you're paranoid."; break;
						case 'INVALID_EMAIL': $error = "Email was invalid."; break;
						case 'PASSWORDS_DONT_MATCH': $error = "Passwords did not match."; break;
						case 'PASSWORD_IS_BLANK': $error = "Password was blank."; break;
						case 'PASSWORD_SAME_AS_USER': $error = "Password was same as username."; break;
						case 'PASSWORD_EASY': $error = "Your password is in the top 10 list of easy-to-guess passwords. Please pick something more creative."; break;
						default: $error = "Server returned error code: ".$result['message']; break;
					}
				}
			}
		}
		
		$output = array(
			'<style type="text/css">',
				'div#register_form h2 { margin:0px; margin-top:8px; font-size:16px; }',
				'div#register_form { padding-left:30px; padding-bottom:30px; font-size:12px;}',
				'.register_aside { color:#888; }',
				'div#register_error { color:#f00; font-weight:bold; }',
			'</style>',
			'<div id="register_form">',
				
				'<h1>Account Registration</h1>',
				
				'<form action="'.$request['path'].'" method="post">',
				
				$error == null
					? ''
					: '<div id="register_error">'.htmlspecialchars($error).'</div>',
				
				'<h2>Username</h2>',
				'<div><input type="text" name="register_username" value="'.htmlspecialchars($username).'" /></div>',
				
				'<h2>Email</h2>',
				'<div><input type="text" name="register_email" value="'.htmlspecialchars($email).'" /></div>',
				
				'<h2>Password</h2>',
				'<div>',
					'<input type="password" name="register_password1" /><br />',
					'<input type="password" name="register_password2" /> <span class="register_aside">Again. This time with feeling.</span>',
				'</div>',
				
				'<div style="margin-top:30px;"><input type="text" name="register_tos" style="width:30px;" value="'.htmlspecialchars($tuba).'" /> I read the <a href="/tos">Terms of Service</a>. As such, I know exactly what to type in this box.</div>',
				
				'<div style="margin-top:30px;">',
					'<input type="submit" name="register_submit" value="Register" />',
				'</div>',
				
				'</form>',
			'</div>',
		);
		
		return build_response_ok("Register New Account", implode("\n", $output));
	}
	
?>