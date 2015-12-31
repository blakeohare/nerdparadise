
function httpGet(url, resultCallback) {
	httpRequest(url, "GET", resultCallback, '', '');
}

function postForm(url, callback, keys, values) {
	var body = [];
	for (var i = 0; i < keys.length; ++i) {
		if (i > 0) body.push("&");
		body.push(keys[i] + "=");
		var t = encodeURI(values[i]);
		t = multi_replace(t, '&', '%26');
		t = multi_replace(t, '+', '%2B');
		body.push(t);
	}
	postContent(url, callback, body.join(''), "application/x-www-form-urlencoded");
}

function postContent(url, callback, content, contentType) {
	httpRequest(url, 'POST', callback, content, contentType);
}

function httpRequest(url, method, callback, content, contentType) {
	var req;
	if (window.ActiveXObject) req = new ActiveXObject("Microsoft.XMLHTTP");
	else if (window.XMLHttpRequest) req = new XMLHttpRequest();
	else return;
	req.open(method, url, true);
	req.onreadystatechange = function() {
		if (req.readyState == 4) {
			callback(req.status, req.responseText);
		}
	}
	
	if (method == "POST") {
		req.setRequestHeader("Content-type", contentType);
		req.send(content);
	} else {
		req.send(null);
	}
}

function htmlspecialchars(text) {
	var output = [];
	for (var i = 0; i < text.length; ++i) {
		var c = text.charAt(i);
		if (c == '<') c = '&lt;';
		else if (c == '>') c = '&gt;';
		else if (c == '&') c = '&amp;';
		else if (c == '"') c = '&quot;';
		output.push(c);
	}
	return output.join('');
}

function nl2br(s) { return multi_replace(s, "\n", '<br />'); }
function multi_replace(h, n, s) { return h.split(n).join(s); }
function hex_digit_to_num(h) { return '0123456789abcdef'.indexOf(h); }
function hex_to_string(h) {
	if (!h) return '';
	var o = [];
	h = h.toLowerCase();
	for (var i = 0; i < h.length; i += 2) {
		o.push(String.fromCharCode(hex_digit_to_num(h[i]) * 16 + hex_digit_to_num(h[i + 1])));
	}
	return o.join('');
}