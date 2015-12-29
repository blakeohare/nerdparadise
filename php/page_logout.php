<?
	// This doesn't actually log you out. The account management just has a hardcoded hack for the /logout/ URL as this code
	// doesn't get executed until long after session management stuff should be taken care of.
	function execute($request) {
		return build_response_ok("Log Out", "Log out.");
	}
?>