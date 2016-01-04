<?
	// TODO: migrate most of this to api/accounts.php...HAHA, who am I kidding.
	
	function execute($request) {
		$user_id = $request['user_id'];
		if ($user_id == 0) {
			return build_response_forbidden("You must be logged in to see this page.");
		}
		
		$has_blurb = true;
		$user_info = api_account_canonicalize_user_db_entry(sql_query_item("SELECT * FROM `users` WHERE `user_id` = $user_id LIMIT 1"));
		$user_profile = sql_query_item("SELECT * FROM `user_profiles` WHERE `user_id` = $user_id LIMIT 1");
		if ($user_profile == null) {
			$user_profile = array(
				'user_id' => $user_id,
				'blurb' => '',
				'contact' => '');
			$has_blurb = false;
		}
		
		$profile_image = $user_info['image_id'];
		$profile_email = $user_info['email_addr'];
		$profile_blurb = $user_profile['blurb'];
		
		$new_profile_image_path = null;
		$upload_success = false;
		$errors = array();
		if ($request['method'] == 'POST') {
			$profile_email = trim($request['form']['profile_email']);
			$profile_old_password = $request['form']['profile_old_password'];
			$profile_new_password1 = $request['form']['profile_new_password1'];
			$profile_new_password2 = $request['form']['profile_new_password2'];
			$profile_blurb = $request['form']['profile_blurb'];
			
			$password_change_attempt = 
				strlen($profile_old_password) > 0 ||
				strlen($profile_new_password1) > 0 ||
				strlen($profile_new_password2) > 0;
			
			$upload_avatar = count($request['files']) == 1 && $request['files'][0]['size'] > 0;
			if ($upload_avatar) {
				$file = $request['files'][0];
				if (!$file['is_image']) {
					array_push($errors, "File was not an image.");
				} else {
					$width = intval($file['image_width']);
					$height = intval($file['image_height']);
					if ($width < 10 || $height < 10) array_push($errors, "Avatar width and height must be greater than 10 pixels.");
					else if ($width > 100 || $height > 100) array_push($errors, "Avatar must be small enough to fit in a 100x100 pixel box.");
					else if ($file['size'] > 50 * 1024) array_push($errors, "Avatar filesize is too big (limit is 50KB)");
					else {
						$image_key = generate_gibberish(10);
						$extension = null;
						switch ($file['type']) {
							case 'PNG': $extension = '.png'; break;
							case 'JPG':
							case 'JPEG': $extension = '.jpg'; break;
							case 'GIF': $extension = '.gif'; break;
							default: break;
						}
						if ($extension == null) {
							array_push($errors, "Unknown image format.");
						} else {
							$new_profile_image_path = $image_key . $extension;
							$destination = 'uploads/avatars/'.$new_profile_image_path;
							$error = false;
							@copy($file['path'], $destination) or $error = true;
							if ($error) {
								array_push($errors, "An unknown error occurred while copying the image.");
							} else {
								$upload_success = true;
							}
						}
					}
				}
			}
			
			if ($upload_success) {
				sql_query("
					UPDATE `users`
					SET
						`image_id` = '".sql_sanitize_string($new_profile_image_path)."',
						`image_dim` = '".intval($width)."|".intval($height)."'
					WHERE `user_id` = $user_id
					LIMIT 1");
			}
			
			$password_updated = false;
			if ($password_change_attempt) {
				$old_pass_hash = api_account_hash_password($profile_old_password);
				$pass_hash = sql_query_item("SELECT `pass_hash` FROM `users` WHERE `user_id` = $user_id LIMIT 1");
				if ($pass_hash['pass_hash'] != $old_pass_hash) {
					array_push($errors, "Old password was incorrect.");
				} else {
					$result = api_account_validate_password($request['name'], $profile_new_password1, $profile_new_password2);
					if ($result['ERROR']) {
						$error = '';
						switch ($result['message']) {
							case 'PASSWORDS_DONT_MATCH': $error = "New passowrd fields didn't match."; break;
							case 'PASSWORD_IS_BLANK': $error = "Password was blank."; break;
							case 'PASSWORD_SAME_AS_USER': $error = "Password was same as username."; break;
							case 'PASSWORD_EASY': $error = "Password is too easy to guess."; break;
							default: $error = "Invalid password."; break;
						}
						array_push($errors, $error);
					} else {
						$password_updated = true;
						sql_query("UPDATE `users` SET `pass_hash` = '".sql_sanitize_string(api_account_hash_password($profile_new_password1))."' WHERE `user_id` = $user_id LIMIT 1");
					}
				}
			}
			
			$email_validate = api_account_validate_email($profile_email);
			if ($email_validate['ERROR']) {
				if ($email_validate['BLANK_EMAIL']) {
					array_push($errors, "Email is blank.");
				} else {
					array_push($errors, "Invalid email.");
				}
			}
			
			if (count($errors) == 0) {
				sql_query("UPDATE `users` SET `email_addr` = '".sql_sanitize_string($profile_email)."' WHERE `user_id` = $user_id LIMIT 1");
				if ($has_blurb) {
					sql_query("UPDATE `user_profiles` SET `blurb` = '".sql_sanitize_string($profile_blurb)."' WHERE `user_id` = $user_id LIMIT 1");
				} else if (strlen(trim($profile_blurb)) > 0) {
					sql_insert('user_profiles', array(
						'user_id' => $user_id,
						'blurb' => $profile_blurb));
				}
			}
		}
		
		$output = array('<h1>Account Settings</h1>');
		
		if ($upload_success) {
			array_push($output,
				'<div>',
				"Profile Image Updated",
				'</div>');
		}
		
		if ($password_updated) {
			array_push($output,
				'<div>',
				"Password updated.",
				'</div>');
		}
		
		if (count($errors) > 0) {
			array_push($output, 
				'<div style="color:#f00;"><div>',
				implode('</div><div>', $errors),
				'</div></div>');
		}
		
		array_push($output, '<form action="'.$request['path'].'" method="post" enctype="multipart/form-data">');
		
		$has_image = strlen($user_info['image_id']) > 0;
		array_push($output, 
			'<div style="padding-bottom:20px;">',
			
			'<h2>Profile Image</h2>',
			
			$has_image ? '<div><img src="/uploads/avatars/'.$user_info['image_id'].'" /></div>' : '',
			
			'<div>',
			"Update: ",
			'<input type="file" name="avatar" />',
			'</div>',
			
			'<div>',
			'<input type="checkbox" name="profile_delete_image" value="1" /> Delete profile image',
			'</div>',
			
			'</div>');
		
		
		array_push($output,
			'<div style="padding-bottom:20px;">',
			'<h2>Profile Blurb</h2>',
			'<div>',
			'<textarea name="profile_blurb" rows="6" style="width:600px;">'.htmlspecialchars($profile_blurb).'</textarea>',
			'</div>',
			'</div>');
		
		array_push($output, 
			'<div style="padding-bottom:20px;">',
			'<h2>Email Address</h2>',
			'<div>',
			'<input type="text" name="profile_email" value="'.$profile_email.'" style="width:300px;"/>',
			'</div>',
			'</div>');
		
		array_push($output,
			'<div style="padding-bottom:20px;">',
			'<h2>Change Password</h2>',
			'<div>(leave blank to leave as is)</div>',
			'<table>',
			'<tr><td>Old Password:</td><td><input type="password" name="profile_old_password" /></td></tr>',
			'<tr><td>New Password:</td><td><input type="password" name="profile_new_password1" /></td></tr>',
			'<tr><td>New Password Confirm:</td><td><input type="password" name="profile_new_password2" /></td></tr>',
			'</table>',
			'</div>');
		
		array_push($output,
			'<div>',
			'<input type="submit" name="submit" value="Update" />',
			'</div>');
		
		array_push($output, '</form>');
		
		return build_response_ok('Account Settings', implode("\n", $output));
	}
?>