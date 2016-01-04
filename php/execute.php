<?
	require 'php/credentials.php';
	require 'php/util.php';
	require 'php/database.php';
	
	require 'php/api/account.php';
	require 'php/api/autograder.php';
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
			case '/account': return 'account_settings.php';
			case '/register': return 'register.php';
			case '/secretdebug': return 'secret_debug.php';
			case '/contact': return 'page_contact.php';
			case '/tos': return 'tos.php';
			case '/about': return 'page_about.php';
			default:
				switch ($root) {
					case 'autograder':
						if ($parts[1] == 'graderpoll') return 'autograder/grader_poll.php';
						if ($parts[1] == 'run') return 'autograder/client_poll.php';
						return "not_found.php";
						
					case 'tinker':
						if ($length == 1) return "tinker/main.php";
						return 'not_found.php';
					
					case 'practice':
						if ($length == 1) return 'practice/main.php';
						if ($length == 2) return 'practice/problem_list.php'; // parts[1] is language
						if ($length == 3 && $parts[2] == ''.intval($parts[2])) return 'practice/problem.php';
						return 'not_found.php';
					
					case 'golf':
						if ($length == 1) return 'golf/main.php';
						if ($length == 2 && $parts[1] == ''.intval($parts[1])) return 'golf/challenge.php';
						if ($length == 2 && $parts[1] == 'ranking') return 'golf/ranking.php';
						if ($length > 1 && $parts[1] == 'tips') return 'content.php';
						return 'not_found.php';
						
					case 'comp': return 'not_found.php';
						if ($length == 1) return 'comp/main.php';
						if ($length == 2) return 'comp/question_list.php';
						if ($length == 3 && $parts[2] == ''.intval($parts[2])) return 'comp/question.php';
						return 'not_found.php';
						
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
						} else if ($length == 4) {
							$thread_id = intval($parts[2]);
							if ($parts[3] == 'reply') return 'forum/post.php';
							if ($thread_id > 0 && substr($parts[3], 0, strlen('page')) == 'page') {
								return 'forum/thread.php';
							} else if ($thread_id > 0 && $parts[3] == 'new') {
								return 'forum/thread.php';
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
		$is_logout = $path == '/logout';
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
						if (is_array($dim) && count($dim) >= 2) {
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
		
		if (!$is_logout && isset($form['login_username'])) {
			$login_result = api_account_create_session($login_result['name'], $form['login_password'], $client, $ip, $ttl_hours);
			if ($login_result['status'] == 'OK') {
				$user_id = $login_result['user_id'];
				$session_token = $login_result['token'];
			}
		}
		
		$login_failure = false;
		$user_info = null;
		if (!$is_logout && strlen($session_token) > 0) {
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
			$avatar = $user_info['avatar'];
		}
		
		if ($is_logout) {
			$session_token = '';
		}
		
		setcookie('npclient', 'web', $expire);
		setcookie('nptoken', $session_token, $expire);
		
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
			'avatar' => $avatar,
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
			if (!$suppress_skin) echo generate_header($response['title'], $request, $response['js'], $response['css'], $response['onload']);
			echo $response['body'];
			if (!$suppress_skin) echo generate_footer($request);
			break;
			
		default:
			http_response_code(500);
			echo 'INVALID RESPONSE CODE';
			break;
	}
	
?>