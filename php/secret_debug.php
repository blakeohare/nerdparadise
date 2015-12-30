<?
	function execute($request) {
		$response = api_account_change_password('Blake', 'password2', 'blake', 'blake');
		debug_print($response);
		
		return build_response_ok("Test", "Ahoy. 2");
	}
?>