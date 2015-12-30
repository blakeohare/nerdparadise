<?
	require 'php/credentials.php';
	require 'php/util.php';
	require 'php/database.php';
	
	require 'php/api/account.php';
	require 'php/api/forum.php';
	
	$suppress_skin = false;
	require 'php/skin.php';
	
	function process_url_mapping($request) {
		$path = $request['path'];
		$parts = $request['path_parts'];
		$root = $parts[0];
		$length = count($parts);
		
		switch ($path) {
			case '/': return 'page_main.php';
			case '/login': return 'page_login.php';
			case '/logout': return 'page_logout.php';
			case '/faq': return 'page_faq.php';
			case '/secretdebug': return 'secret_debug.php';
			case '/contact': return 'page_contact.php';
			case '/about': return 'page_about.php';
			default:
				switch ($root) {
					case 'golf': return 'not_found.php';
					case 'comp': return 'not_found.php';
					case 'jams': return 'not_found.php';
					case 'tutorials': return 'not_found.php';
					case 'forum': 
						if ($length == 1) {
							// /forum
							return 'forum/main.php';
						} else if ($length == 2) {
							// /forum/{category}
							return 'forum/category.php';
						} else if ($length == 3) {
							$thread_id = intval($parts[2]);
							if ($thread_id > 0) {
								// /forum/{category}/{thread}
								return 'forum/thread.php';
							} else if (substr($parts[2], 0, strlen('page')) == 'page') {
								// /forum/{category}/page{pagenum}
								return 'forum/category.php';
							} else if($parts[2] == 'post') {
								// /forum/{category}/post
								return 'forum/post.php';
							}
						}
						break;
					default:
						// TODO: do lookup on old NP articles and forward to blakeohare.com
						break;
				}
				break;
		}
		return 'not_found.php';
	}
	
	function get_file_type($mime) {
		switch ($mime) {
			case 'image/gif': return 'GIF';
			case 'image/jpeg':
			case 'image/pjpeg': return 'JPEG';
			case 'image/png':
			case 'image/x-png': return 'PNG';
			default: return null;
		}
	}
	
	function get_url_parts() {
		$url_parts = explode('/', trim($_GET['url']));
		$output = array();
		foreach ($url_parts as $url_part) {
			$part = string_alphanums($url_part);
			if (strlen($part) > 0) {
				array_push($output, $part);
			}
		}
		return $output;
	}
	
	/**
	 * Gets information about the raw HTTP request.
	 */
	function get_http_request() {
		$url_parts = get_url_parts();
		$path = '/' . implode('/', $url_parts);
		$method = strtoupper(trim($_SERVER['REQUEST_METHOD']));
		$verified_user_id = 0;
		$login_id = null;
		$name = null;
		$is_admin = false;
		$ip = trim($_SERVER['REMOTE_ADDR']);
		$content = null;
		$form = array();
		$content_type = null;
		$raw_content = null;
		$files = array();
		$cookies = array();
		foreach ($_COOKIE as $k => $v) {
			$cookies[$k] = $v;
		}
		
		if ($method != 'GET') {
			$content_type = trim(isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : $_SERVER['HTTP_CONTENT_TYPE']);
			if (strpos($content_type, 'application/x-www-form-urlencoded') === 0 || strpos($content_type, 'multipart/form-data') === 0) {
				foreach ($_POST as $key => $value) {
					$form[$key] = $value;
				}
				
				foreach ($_FILES as $key => $value) {
					$file_info = array(
						'id' => $key,
						'mime' => $value['type'],
						'type' => get_file_type($value['type']),
						'size' => $value['size'],
						'path' => $value['tmp_name'],
						'is_image' => false);
					
					if ($file_info['type'] == 'PNG' || 
						$file_info['type'] == 'JPEG' || 
						$file_info['type'] == 'GIF') {
						$file_info['is_image'] = true;
						$dim = @getimagesize($file_info['path']);
						if (is_array($dim) && count($dim) == 2) {
							$file_info['image_width'] = $dim[0];
							$file_info['image_height'] = $dim[1];
						}
					}
					array_push($files, $file_info);
				}
			} else {
				$raw_content = file_get_contents('php://input');
			}
		}
		
		$session_token = trim($cookies['nptoken']);
		$client = trim($cookies['npclient']);
		
		$ttl_hours = 24 * 30; // change this for other clients upon request.
		
		if (isset($form['login_username'])) {
			$login_result = api_account_create_session($login_result['name'], $form['login_password'], $client, $ip, $ttl_hours);
			if ($login_result['status'] == 'OK') {
				$user_id = $login_result['user_id'];
				$session_token = $login_result['token'];
			}
		}
		
		$login_failure = false;
		$user_info = null;
		if (strlen($session_token) > 0) {
			$user_info = api_account_authenticate_with_session($session_token, $ip);
			if ($user_info['status'] != 'OK') {
				$user_info = null;
				$login_failure = true;
			}
		}
		
		if ($user_info !== null) {
			$verified_user_id = $user_info['user_id'];
			$is_admin = $user_info['is_admin'];
			$login_id = $user_info['login_id'];
			$name = $user_info['name'];
		}
		
		$_COOKIE['nptoken'] = $session_token;
		$_COOKIE['npclient'] = 'web';
		
		return array(
			'method' => $method,
			'path' => $path,
			'path_parts' => $url_parts,
			'user_id' => $verified_user_id,
			'login_id' => $login_id,
			'logged_in' => $verified_user_id > 0,
			'login_failure' => $login_failure,
			'name' => $name,
			'is_admin' => $is_admin,
			'content_type' => $content_type,
			'form' => $form,
			'raw_content' => $raw_content,
			'files' => $files,
			'cookies' => $cookies,
			'ip' => $ip,
		);
	}
	
	$request = get_http_request();
	
	// overwrites suppress_skin to true, possibly
	require 'php/' . process_url_mapping($request);
	
	$response = execute($request);
	$status = intval($response['SC']);
	switch ($status) {
			
		case 301:
		case 302:
			$url = $response['redirect'];
			if (strpos($url, 'http://') === 0) {
				if ($url[0] == '/') {
					$url = 'np10.nfshost.com'.$url;
				}
				$url = 'http://'.$url;
			}
			if ($status == 301) {
				header('HTTP/1.1 301 Moved Permanently');
				header('Location: '.$url);
			} else {
				header('Location: '.$url);
			}
			exit;
			break;
		
		case 200:
		case 400:
		case 401:
		case 403:
		case 404:
		case 500:
			if ($status != 200) http_response_code($status);
			if (!$suppress_skin) echo generate_header($response['title'], $request);
			echo $response['body'];
			if (!$suppress_skin) echo generate_footer($request);
			break;
			
		default:
			http_response_code(500);
			echo 'INVALID RESPONSE CODE';
			break;
	}
	
?>