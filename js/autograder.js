
var ag_context = {
	counter: 0,
	active_token: null
};

function ag_init(type) {
	// TODO: special instructions for different page types
	ag_context.type = type;
	document.getElementById('ag_button_host').innerHTML = '<button onclick="ag_button_click()">Run</button>';
	ag_update();
}

function ag_button_click() {
	var code = document.getElementById('ag_code').value;
	var language = document.getElementById('ag_language').value;
	document.getElementById('ag_output_host').innerHTML = '(waiting)';
	postForm('/autograder/run', 
		ag_start_wait,
		['feature', 'action', 'code', 'language', 'problem_id'], ['tinker', 'create', code, language, '']);
}

function ag_parse_response(response) {
	var output = {};
	var lines = response.split(',');
	for (var i = 0; i < lines.length; ++i) {
		var t = lines[i].split(':');
		output[hex_to_string(t[0])] = hex_to_string(t[1]);
	}
	return output;
}

function ag_start_wait(sc, response) {
	if (sc != 200) return;
	
	response = ag_parse_response(response);
	if (response.type == 'error') {
		ag_context.active_token = null;
		ag_display_output('#f00', true, response.msg);
	} else {
		ag_context.active_token = response.token;
		ag_context.state = 'NOT_STARTED';
		ag_context.counter = 0;
		ag_display_output('#888', true, 'Waiting for response...');
	}
}

function ag_is_poll_time(n) {
	if (n < 30) return n % 4 == 2;
	if (n < 100) return n % 8 == 0;
	if (n < 200) return n % 16 == 0;
	if (n < 300) return n % 32 == 0;
	return n % 64 == 0;
}

function ag_update() {
	
	if (ag_context.active_token != null) {
		if (ag_is_poll_time(ag_context.counter++)) {
			postForm(
				'/autograder/run', 
				ag_apply_poll_updates,
				['feature', 'action', 'token'], ['tinker', 'poll', ag_context.active_token]);
		}
	}
	
	setTimeout("ag_update()", 250);
}

function ag_apply_poll_updates(sc, response) {
	if (sc == 200) {
		response = ag_parse_response(response);
		if (response.token == ag_context.active_token) {
			switch (response.type) {
				case 'state': ag_display_output('#888', true, response.msg); break;
				case 'error': ag_display_output('#f00', true, response.msg); ag_context.active_token = null; break;
				case 'output': ag_display_output('#000', false, response.msg); ag_context.active_token = null; break;
				default: ag_display_output('#808', true, "Server returned unknown response."); break;
			}
		}
	}
}

function ag_display_output(color, italic, value) {
	document.getElementById('ag_output_host').innerHTML = '<div style="' + (italic ? "font-style:italic; " : '') + 'color:'+color+';">' + nl2br(htmlspecialchars(value)) + '</div>';
}
