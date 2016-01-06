<?

	$_global_variable_store = array();
	
	function get_global($name) {
		global $_global_variable_store;
		return isset($_global_variable_store[$name]) ? $_global_variable_store[$name] : null;
	}
	
	function set_global($name, $value) {
		global $_global_variable_store;
		$_global_variable_store[$name] = $value;
	}
	
	$_VARS = array();
	function get_var($name) {
		global $_VARS;
		if (count($_VARS) == 0) {
			$vars_db = sql_query("SELECT * FROM `var`");
			for ($i = 0; $i < $vars_db->num_rows; ++$i) {
				$var = $vars_db->fetch_assoc();
				$_VARS[$var['name']] = $var[(strlen($var['value']) == 0 ?
					'intvalue' :
					'value')];
			}
		}
		
		return $_VARS[$name];
	}
	
	// Takes a PHP string (which is really just an array of raw bytes) and converts it into
	// a list of strings, where each string is the array of bytes that represent one unicode
	// character.
	function convert_php_string_to_utf8_char_array($string) {
		$output = array();

		if (strlen($string) >= 3 &&
			ord($string[0]) == 239 &&
			ord($string[1]) == 187 &&
			ord($string[2]) == 191) {
			$string = substr($string, 3);
		}

		for ($i = 0; $i < strlen($string); ++$i) {
			$c = $string[$i];
			$value = ord($c);
			
			if (($value & 0x80) != 0) {
				if (($value & 0xE0) == 0xC0) {
					$extra_byte_count = 1;
				} else if (($value & 0xF0) == 0xE0) {
					$extra_byte_count = 2;
				} else if (($value & 0xF8) == 0xF0) {
					$extra_byte_count = 3;
				} else if (($value & 0xFC) == 0xF8) {
					$extra_byte_count = 4;
				} else if (($vlaue & 0xFE) == 0xFC) {
					$extra_byte_count = 5;
				}
				
				while ($extra_byte_count-- > 0) {
					$c .= $string[++$i];
				}
			}
			array_push($output, $c);
		}
		
		return $output;
	}
	
	// This no longer canonicalizes characters with accents.
	// Because it is strictly lower ASCII values, no unicode characters will get
	// accidentally converted into ASCII.
	function string_alphanums($string) {
		$output = array();
		$string = strtolower($string);
		$length = strlen($string);
		for ($i = 0; $i < $length; ++$i) {
			$c = ord($string[$i]);
			if (($c >= 97 && $c <= 122) || // a-z
				($c >= 48 && $c <= 57)) { // 0-9
				array_push($output, $string[$i]);
			}
		}
		
		return implode('', $output);
	}
	
	function string_to_hex($str) {
		$hex = '0123456789abcdef';
		$output = array();
		$str = ''.$str;
		for ($i = 0; $i < strlen($str); ++$i) {
			$c = ord($str[$i]) & 255;
			array_push($output, $hex[$c >> 4]);
			array_push($output, $hex[$c & 15]);
		}
		return implode('', $output);
	}
	
	function hex_to_string($hex) {
		$output = array();
		$hex = strtolower($hex);
		$length = strlen($hex);
		$digits = '0123456789abcdef';
		for ($i = 0; $i < $length; $i += 2) {
			$a = strpos($digits, $hex[$i]);
			$b = strpos($digits, $hex[$i + 1]);
			if ($a === false || $b === false) return '';
			array_push($output, chr($a * 16 + $b));
		}
		return implode('', $output);
	}
	
	function api_error($message) {
		return array('status' => 'ERROR', 'message' => $message, 'error' => true, 'ERROR' => true, 'OK' => false);
	}
	
	function api_success($values = null) {
		if ($values == null) $values = array();
		$values['status'] = 'OK';
		$values['error'] = false;
		$values['ERROR'] = false;
		$values['OK'] = true;
		$values['ok'] = true;
		
		return $values;
	}
	
	function build_response($http_status_code, $title, $body, $redirect, $flags) {
		$js = array();
		$css = array();
		$onload = array();
		if ($flags != null) {
			if (isset($flags['js'])) {
				$js = $flags['js'];
				if ($js == null) $js = array();
				if (is_string($js)) $js = array($js);
			}
			if (isset($flags['css'])) {
				$css = $flags['css'];
				if ($css == null) $css = array();
				if (is_string($css)) $css = array($css);
			}
			if (isset($flags['onload'])) {
				$onload = $flags['onload'];
				if ($onload == null) $onload = array();
				if (is_string($onload)) $onload = array($onload);
			}
		}
		return array(
			'SC' => $http_status_code,
			'title' => trim($title),
			'body' => trim($body),
			'js' => $js,
			'css' => $css,
			'onload' => $onload,
			'redirect' => $redirect);
	}
	
	function build_response_ok($title, $html, $flags = null) {
		return build_response(200, $title, $html, null, $flags);
	}
	
	function build_response_moved_permanently($url) {
		return build_response(301, null, $html, $url, null);
	}
	
	function build_response_moved_temporarily($url) {
		return build_response(302, null, $html, $url, null);
	}
	
	function build_response_user_error($html) {
		return build_response(400, 'BAD REQUEST! NO TREAT!', $html, null, null);
	}
	
	function build_response_unauthenticated($html) {
		return build_response(401, 'UNAUTHENTIFICATED', $html, null, null);
	}
	
	function build_response_forbidden($html) {
		return build_response(403, "FORBIDD'N", $html, null, null);
	}
	
	function build_response_not_found($html) {
		return build_response(404, "PAGE NOT FOUND", $html, null, null);
	}
	
	function build_response_server_error($html) {
		return build_response(500, "OH NO", $html, null, null);
	}
	
	function debug_print($thing) {
	
		echo '<div style="padding:8px; background-color:#aac; color:#000; font-size:12px; font-family: &quot;Courier New&quot;, monospace;"><pre>';
		print_r($thing);
		echo '</pre></div>';
	}
	
	function universal_to_string($thing, $indent = 0) {
		if (is_array($thing)) {
			if (is_array_list($thing)) return list_to_string($thing, $indent);
			return dictionary_to_string($thing, $indent);
		}
		if ($thing === null) return 'null';
		if ($thing === false) return 'false';
		if ($thing === true) return 'true';
		if ($thing === 0) return '0';
		if (is_numeric($thing)) return ''.$thing;
		if (is_string($thing)) return '"'.str_replace(
			array("\n", "\r", "\t", "\""),
			array("\\n", "\\r", "\\t", "\\\""),
			$thing).'"';
		return '<UNDEFINED TO STRING>'.$thing.'</UNDEFINED TO STRING>';
	}
	
	function is_array_list($array) {
		for ($i = count($array) - 1; $i >= 0; --$i) {
			if (!isset($array[$i])) return false;
		}
		return true;
	}
	
	function list_to_string($list, $indent = 0) {
		$length = count($list);
		if ($length == 0) return '[]';
		if ($length == 1) return '['.universal_to_string($list[0], 0).']';
		
		$tabs = '';
		while (strlen($tabs) < $indent) $tabs .= '  ';
		$output = array('[');
		for ($i = 0; $i < $length; ++$i) {
			array_push($output, $tabs . '  ' . universal_to_string($list[$i], $indent + 1) . (($i < $length - 1) ? ',' : ''));
		}
		array_push($output, $tabs.']');
		return implode("\n", $output);
	}
	
	function dictionary_to_string($dict, $indent = 0) {
		$length = count($dict);
		if ($length == 0) return '{}';
		
		$keys = array();
		foreach ($dict as $key => $value) {
			array_push($keys, $key);
		}
		sort($keys);
		
		$tabs = '';
		while (strlen($tabs) < $indent) $tabs .= '  ';
		$output = array('{');
		for ($i = 0; $i < $length; ++$i) {
			array_push($output, $tabs . '  ' . $keys[$i] . ': ' . universal_to_string($dict[$keys[$i]], $indent + 1) . (($i < $length - 1) ? ',' : ''));
		}
		array_push($output, $tabs.'}');
		return implode("\n", $output);
	}
	
	function int_to_base64($value) {
		$value = intval($value);
		if ($value == 0) return '0';
		$sign = '';
		if ($value < 0) {
			$sign = '-';
			$value = -$value;
		}
		$alphabet = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ@_';
		$output = array($sign);
		while ($value > 0) {
			array_push($output, $alphabet[$value % 64]);
			$value = $value >> 6;
		}
		return implode('', $output);
	}
	
	function sort_and_remove_duplicates($items) {
		$set = array();
		foreach ($items as $item) {
			$set['k' . $item] = $item;
		}
		$output = array();
		foreach ($set as $item) {
			array_push($output, $item);
		}
		sort($output);
		return $output;
	}
	
	function generate_gibberish($length) {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$output = array();
		while ($length-- > 0) {
			array_push($output, $chars[rand(0, strlen($chars) - 1)]);
		}
		return implode("", $output);
	}
	
	function unix_to_day($unix) {
		return date("M j, Y", $unix);
	}
	
	function unix_to_scaling_time($unix, $full_detail = false) {
		$diff = (time() - $unix);
		$is_future = $diff < 0;
		$diff = abs($diff);
		
		if ($diff < 60) {
			$output = $diff.' second'.($diff == 1 ? '' : 's');;
			return $is_future ? $output : ($output.' ago');
		} else if ($diff < 3600) {
			$minutes = intval($diff / 60);
			$output = $minutes.' minute'.($minutes == 1 ? '' : 's');
			$seconds = $diff - $minutes * 60;
			if ($seconds > 0) {
				$output .= ' '.$seconds . ' second'.($seconds == 1 ? '' : 's');
			}
			return $is_future ? $output : ($output.' ago');
		} else if ($diff < 3600 * 12) {
			$hours = intval($diff / 3600);
			$diff -= $hours * 3600;
			$minutes = intval($diff / 60);
			$output = $hours.' hour'.($hours == 1 ? '' : 's');
			if ($minutes > 0) {
				$output .= ' '.$minutes . ' minute'.($minutes == 1 ? '' : 's');
			}
			return $is_future ? $output : ($output.' ago');
		} else if ($full_detail) {
			return date("M j, Y g:i A", $unix);
		} else {
			return date("M j, Y", $unix);
		}
	}
	
	function seconds_to_duration($seconds) {
		if ($seconds < 0) $seconds = 0;
		$total = intval($seconds);
		$minutes = intval($total / 60);
		$seconds = $total - 60 * $minutes;
		$hours = intval($minutes / 60);
		$minutes -= $hours * 60;
		$days = intval($hours / 24);
		$hours -= $days * 24;
		
		$output = array();
		$show = false;
		if ($days > 0) {
			array_push($output, $days.' day'.($days == 1 ? '' : 's'));
			$show = true;
		}
		if ($show || $hours > 0) {
			array_push($output, $hours.' hour'.($hours == 1 ? '' : 's'));
			$show = true;
		}
		if ($show || $minutes > 0) {
			array_push($output, $minutes.' minute'.($minutes == 1 ? '' : 's'));
			$show = true;
		}
		if ($show || $seconds > 0) {
			array_push($output, $seconds.' second'.($seconds == 1 ? '' : 's'));
		}
		return implode(', ', $output);
	}
?>