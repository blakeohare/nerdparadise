<?
	function api_account_register_user($name, $email, $password_plaintext1, $password_plaintext2, $current_ip) {
		$name = trim($name);
		
		if (strlen($name) > 20) return api_error('NAME_TOO_LONG');
		if (strlen($name) < 2) return api_error('NAME_TOO_SHORT');
		
		$alphabet = 'abcdefghijklmnopqrstuvwxyz';
		$allowed_chars = $alphabet . strtoupper($alphabet);
		for ($i = 0; $i < 10; ++$i) {
			$allowed_chars .= $i;
		}
		$allowed_chars .= '-:_+=*()&^%#@!~"`'.'$'."'".';/?.,>< ';
		
		for ($i = 0; $i < strlen($name); ++$i) {
			if (strpos($allowed_chars, $name[$i]) === false) {
				return api_error('NAME_INVALID_CHARS');
			}
		}
		
		$login_id = api_account_get_login_id($name);
		
		if (strlen($login_id) == 0) return api_error('NAME_NO_ALPHANUMS');
		
		$pw_check = api_account_validate_password($name, $password_plaintext1, $password_plaintext2);
		if ($pw_check['status'] == 'ERROR') {
			return $pw_check;
		} else {
			$password = $pw_check['password'];
		}
		
		$existing_user = api_account_lookup_user_by_name($login_id);
		
		if ($existing_user != null) return api_error('SIMILAR_NAME_EXISTS');
		
		$user_id = sql_insert("users", array(
			'name' => $name,
			'login_id' => $login_id,
			'pass_hash' => api_account_hash_password($password_plaintext),
			'email_addr' => $email_address,
			'time_registered' => time(),
			'time_last_online' => 0,
			'ip_registered' => $current_ip));
		
		if ($user_id == null) return api_error('UNKNOWN_ERROR');
		
		return api_success(array('user_id' => $user_id));
	}
	
	function api_account_validate_password($username, $password1, $password2) {
		if ($password1 != $password2) return api_error("PASSWORDS_DONT_MATCH");
		$password = trim($password1);
		if (strlen($password) == 0) return api_error("PASSWORD_IS_BLANK");
		
		$canonical_pass = strtolower($password);
		if ($canonical_pass == strtolower(trim($username))) return api_error('PASSWORD_SAME_AS_USER');
		if ($canonical_pass == 'nerdparadise' || 
			$canonical_pass == 'np' || 
			$canonical_pass == 'password' ||
			$canonical_pass == 'pw' ||
			$canonical_pass == 'guest' ||
			$canonical_pass == '123' ||
			$canonical_pass == '1234' ||
			$canonical_pass == '12345' ||
			$canonical_pass == 'pass') {
			
			return api_error('PASSWORD_EASY');
		}
		
		return api_success(array('password' => $password));
	}
	
	function api_account_hash_password($password_plaintext) {
		$salt = "I'm a hologram! zzZZzzZZzz &@#$^*&^{:";
		return sha1(sha1(trim($password_plaintext)).$salt);
	}
	
	function api_account_hash_password_legacy($password_plaintext) {
		return md5($password_plaintext);
	}
	
	function api_account_get_login_id($name) {
		return strtolower(string_alphanums($name));
	}
	
	function api_account_lookup_user_by_id($id) {
		return api_account_lookup_user_impl($id, null);
	}
	
	function api_account_lookup_user_by_name($name) {
		return api_account_lookup_user_impl(null, $name);
	}
	
	function api_account_lookup_user_impl($id, $name) {
		
		if ($id != null) {
			$where_clause = "`user_id` = ".intval($id);
		} else {
			$login_id = api_account_get_login_id($name);
			if (strlen($login_id) == 0) return null;
			$where_clause = "`login_id` = '$login_id'";
		}
		
		$output = sql_query_item("
			SELECT
				`user_id`,
				`login_id`,
				`name`,
				`pass_hash`,
				`time_last_online`,
				`ip_last`,
				`flags`
			FROM `users`
			WHERE $where_clause
			LIMIT 1");
		
		// expand flags into virtual fields.
		if ($output != null) {
			$is_admin = false;
			$is_disabled = false;
			$is_legacy_password = false;
			
			$flags_raw = $output['flags'];
			for ($i = 0; $i < strlen($flags_raw); ++$i) {
				switch ($flags_raw[$i]) {
					// The flags field is VARCHAR(20). If more than 20 things are listed here, time to expand the field.
					case 'A': $is_admin = true; break;
					case 'D': $is_disabled = true; break;
					case 'L': $is_legacy_password = true; break;
					default: break;
				}
			}
			
			$output['is_admin'] = $is_admin;
			$output['is_disabled'] = $is_disabled;
			$output['is_legacy_password'] = $is_legacy_password;
		}
		return $output;
	}
	
	function _api_account_remove_flag($original_flags, $flag_to_remove) {
		$output = array();
		for ($i = 0; $i < strlen($original_flags); ++$i) {
			if ($original_flags[$i] != $flag_to_remove) {
				array_push($output, $original_flags[$i]);
			}
		}
		return implode('', $output);
	}
	
	function api_account_authenticate_user($name, $pass_hash, $pass_plaintext, $ip) {
		$user_info = api_account_lookup_user_by_name($name.'');
		if ($user_info == 'null') return api_error('NOT_FOUND');
		
		// Do a one time fix to re-encrypt the user's password, if it's a legacy password.
		if ($user_info['is_legacy_password'] == 1 && $pass_plaintext != null) {
			$pass_hash = api_account_hash_password_legacy($pass_plaintext);
			if ($pass_hash == $user_info['pass_hash']) {
				$pass_hash = api_account_hash_password($pass_plaintext);
				sql_query("
					UPDATE `users`
					SET
						`pass_hash` = '".sql_sanitize_string($pass_hash)."',
						`flags` = '"._api_account_remove_flag($user_info['flags'], 'L')."'
					WHERE `user_id` = ".$user_info['user_id']."
					LIMIT 1");
					
				$user_info['pass_hash'] = $pass_hash;
				$user_info['is_pass_legacy'] = 0;
			}
		}
		
		if ($pass_hash == null) $pass_hash = api_account_hash_password($pass_plaintext);
		
		$user_id = $user_info['user_id'];
		
		if ($user_info['pass_hash'] != $pass_hash) return api_error('WRONG_PASSWORD');
		
		$now = time();
		
		if ($ip != $user_info['ip_last'] || $user_info['time_last_online'] < time() - 120) {
			sql_query("
				UPDATE `users`
				SET
					`ip_last` = '".sql_sanitize_string($ip)."',
					`time_last_online` = $now
				WHERE
					`user_id` = $user_id
				LIMIT 1");
		}
		
		return api_success($user_info);
	}
	
	function api_account_authenticate_with_session($token_id, $current_ip) {
		$session = sql_query_item("SELECT * FROM `sessions` WHERE `session_id` = '" . sql_sanitize_string($token_id) . "' LIMIT 1");
		
		$verified_session = null;
		if ($session != null) {
			$ttl = $session['ttl_hours'] * 3600;
			$last_visit = $session['last_visit'];
			$now = time();
			if ($last_visit + $ttl < $now) {
				// expired
				sql_query("DELETE FROM `sessions` WHERE `session_id` = '" . sql_sanitize_string($token_id) . "' LIMIT 1");
			} else {
				if ($last_visit + 120 < $now || $session['last_ip'] != $current_ip) {
					sql_query("UPDATE `sessions` SET `last_visit` = $now, `last_ip` = '".sql_sanitize_string($current_ip)."' WHERE `session_id` = '" . sql_sanitize_string($token_id) . "' LIMIT 1");
				}
				$verified_session = $session;
			}
		}
		if ($verified_session == null) {
			return api_error("NOT_FOUND");
		}
		
		$user_id = $verified_session['user_id'];
		
		$user_info = api_account_lookup_user_by_id($user_id);
		
		return api_success($user_info);
	}
	
	function api_account_create_session($name, $password_plaintext, $client, $current_ip, $ttl) {
		$token_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$token_builder = array();
		for ($i = 0; $i < 32; ++$i) {
			array_push($token_builder, $token_chars[rand() & 31]);
		}
		
		$token = implode('', $token_builder);
		
		$user_info = api_account_authenticate_user($name, null, $password_plaintext, $current_ip);
		if ($user_info['status'] == 'ERROR') {
			return $user_info;
		}
		
		sql_insert('sessions', array(
			'session_id' => $token,
			'user_id' => $user_info['user_id'],
			'client' => $client,
			'ttl_hours' => $ttl,
			'last_ip' => $current_ip,
			'last_visit' => time()));
		
		return api_success(array(
			'token' => $token,
			'user_id' => $user_info['user_id']));
	}
	
	function api_account_change_password($name, $old_password, $new_password1, $new_password2) {
		// TODO: send an email
		
		$user_info = api_account_lookup_user_by_name($name);
		
		if (strlen($new_password1) == 0) return api_error("INVALID");
		
		if (api_account_hash_password($old_password) != $user_info['pass_hash']) {
			return api_error('WRONG_OLD_PASSWORD');
		}
		
		$pw_check = api_account_validate_password($name, $new_password1, $new_password2);
		
		if ($pw_check['status'] == 'ERROR') return $pw_check;
		
		$password = $pw_check['password'];
		
		$pass_hash = api_account_hash_password($password);
		
		sql_query("UPDATE `users` SET `pass_hash` = '$pass_hash' WHERE `user_id` = " . $user_info['user_id'] . " LIMIT 1");
		
		return api_success();
	}
?>