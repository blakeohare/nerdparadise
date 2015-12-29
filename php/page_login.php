<?
	function execute($request) {
		$username = $request['form']['login_username'].'';
		$password = $request['form']['login_password'].'';
		
		$error = null;
		
		if (strlen($username) > 0) {
			$result = api_account_create_session($username, $password, 'web', $request['ip'], 14 * 24); // two weeks
			if ($result['OK']) {
				$expire = time() + 365 * 24 * 3600;
				setcookie('npclient', 'web', $expire);
				setcookie('nptoken', $result['token'], $expire);
				return build_response_moved_temporarily('/');
			} else {
				switch ($result['message']) {
					case 'WRONG_PASSWORD':
						$error = "Bad password. Did you forget it?";
						break;
					default:
						$error = "Server returned error code: ".$result['message'];
						break;
				}
			}
		}
		
		$output = array(
			'<h1>Log in</h1>',
			$error == null ? '' : nl2br(htmlspecialchars($error)),
			'<form action="/login" method="post">',
			'Username: <input type="text" name="login_username" value="'.htmlspecialchars($username).'"/><br />',
			'Password: <input type="text" name="login_password" /><br />',
			'<input type="submit" name="submit" value="Login" />',
			'</form>',
			);
		
		return build_response_ok("Log In", implode("\n", $output));
	}
?>