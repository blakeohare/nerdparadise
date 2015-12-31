
var fiddle_context = {
	counter: 0,
	active_token: null
};

function fiddle_init() {
	document.getElementById('fiddle_button_host').innerHTML = '<button onclick="fiddle_button_click()">Run</button>';
	fiddle_update();
}

function fiddle_button_click() {
	var code = document.getElementById('fiddle_code').value;
	var language = document.getElementById('fiddle_language').value;
	document.getElementById('fiddle_output_host').innerHTML = '(waiting)';
	postForm('http://np10.nfshost.com/fiddle/poll', function(sc, content) {
		if (sc == 200) {
			fiddle_start_wait(content);
		}
	}, ['code', 'language'], [code, language]);
}

function fiddle_parse_response(response) {
	var output = {};
	var lines = response.split(',');
	for (var i = 0; i < lines.length; ++i) {
		var t = lines[i].split(':');
		output[hex_to_string(t[0])] = hex_to_string(t[1]);
	}
	return output;
}

function fiddle_start_wait(response) {
	response = fiddle_parse_response(response);
	if (response.type == 'error') {
		fiddle_context.active_token = null;
		fiddle_display_output('#f00', true, response.msg);
	} else {
		fiddle_context.active_token = response.token;
		fiddle_context.state = 'NOT_STARTED';
		fiddle_context.counter = 0;
		fiddle_display_output('#888', true, 'Waiting for response...');
	}
}

function fiddle_is_poll_time(n) {
	if (n < 30) return n % 4 == 2;
	if (n < 100) return n % 8 == 0;
	if (n < 200) return n % 16 == 0;
	if (n < 300) return n % 32 == 0;
	return n % 64 == 0;
}

function fiddle_update() {
	
	if (fiddle_context.active_token != null) {
		if (fiddle_is_poll_time(fiddle_context.counter++)) {
			httpGet('http://np10.nfshost.com/fiddle/poll/' + fiddle_context.active_token, fiddle_apply_poll_updates);
		}
	}
	
	setTimeout("fiddle_update()", 250);
}

function fiddle_apply_poll_updates(sc, response) {
	if (sc == 200) {
		response = fiddle_parse_response(response);
		if (response.token == fiddle_context.active_token) {
			switch (response.type) {
				case 'state': fiddle_display_output('#888', true, response.state); break;
				case 'error': fiddle_display_output('#f00', true, response.error); fiddle_context.active_token = null; break;
				case 'output': fiddle_display_output('#000', false, response.output); fiddle_context.active_token = null; break;
				default: fiddle_display_output('#808', true, "Server returned unknown response."); break;
			}
		}
	}
}

function fiddle_display_output(color, italic, value) {
	document.getElementById('fiddle_output_host').innerHTML = '<div style="' + (italic ? "font-style:italic; " : '') + 'color:'+color+';">' + nl2br(htmlspecialchars(value)) + '</div>';
}
