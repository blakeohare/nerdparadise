<?
	$_DB = null;
	
	$_DB_QUERY_LOG = array();
	
	function sql_get_db() {
		global $_DB;
		
		if ($_DB === null) {
			global $CREDENTIALS;
			$_DB = new mysqli(
				$CREDENTIALS['mysql_host'],
				$CREDENTIALS['mysql_username'],
				$CREDENTIALS['mysql_password'],
				$CREDENTIALS['mysql_database']);
			if ($_DB->connect_errno) {
				die('MySQL database is down.');
			}
		}
		return $_DB;
	}
	
	function sql_log_query($query, $time) {
		global $_DB_QUERY_LOG;
		array_push($_DB_QUERY_LOG, array($query, $time));
	}
	
	function sql_get_logged_queries() {
		global $_DB_QUERY_LOG;
		return $_DB_QUERY_LOG;
	}
	
	function sql_query($query, $print = false) {
		$db = sql_get_db();
		
		if ($print) {
			debug_print($query);
		}
		$start = microtime(true);
		$output = $db->query(trim($query));
		$end = microtime(true);
		$diff = $end - $start;
		sql_log_query($query, $end - $start);
		
		if ($db->errno == 0) {
			return $output;
		}
		
		echo '<div style="padding:8px; background-color:#f88; color:#400; font-weight:bold; font-size:12px; font-family: &quot;Courier New&quot;, monospace;">';
		echo htmlspecialchars($db->error);
		echo '<br /><br />';
		echo nl2br(str_replace(' ', '&nbsp;', htmlspecialchars($query)));
		echo '</div>';
		exit;
	}
	
	function sql_query_item($query, $print = false) {
		$item = sql_query($query, $print);
		if ($item->num_rows == 0) return null;
		return $item->fetch_assoc();
	}
	
	function sql_insert($table, $values_lookup, $print = false) {
		$cols = array();
		$values = array();
		
		foreach ($values_lookup as $key => $value) {
			array_push($cols, $key);
			array_push($values, sql_sanitize_string($value));
		}
		
		$query = "INSERT INTO `$table` (`" . implode('`, `', $cols) . "`) VALUES ('" . implode("', '", $values) . "')";
		
		sql_query($query, $print);
		$db = sql_get_db();
		$insert_id = $db->insert_id;
		if ($insert_id > 0) return $insert_id;
		return null;
	}
	
	function sql_sanitize_string($value) {
		$db = sql_get_db();
		return $db->real_escape_string($value);
	}
	
?>