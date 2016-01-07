
var conway_context = {
	is_running: false,
	tile_width: 10,
	grid: null,
	tgrid: null,
	cols: 0,
	rows: 0,
	canvas: null,
	draw: null,
	pixel_queue: [],
	enabled: true,
	dirty_bounds: null,
	canvas_size: null,
	canvas_host: null,
};

function conway_update() {
	var cc = conway_context;
	if (!cc.enabled) return;
	
	var canvas_w = cc.canvas_host.offsetWidth;
	var canvas_h = cc.canvas_host.offsetHeight;
	var tw = cc.tile_width;
	var cols = Math.floor(canvas_w / tw) + 1;
	var rows = Math.floor(canvas_h / tw) + 1;
	if (cc.canvas_size == null || cc.canvas_size[0] != canvas_w || cc.canvas_size[1] != canvas_h) {
		document.getElementById('top_canvas_host').innerHTML = '<canvas id="conways_game_of_life" width="' + canvas_w + '" height="' + canvas_h + '"/>';
		cc.canvas = document.getElementById('conways_game_of_life');
		cc.draw = cc.canvas.getContext('2d');
		cc.grid = resize_grid(cols, rows, cc.grid);
		cc.tgrid = resize_grid(cols, rows, cc.tgrid);
		cc.cols = cols;
		cc.rows = rows;
		cc.canvas_size = [canvas_w, canvas_h];
	}
	
	for (var i = 0; i < cc.pixel_queue.length; ++i) {
		var pt = cc.pixel_queue[i];
		var col = Math.floor(pt[0] / tw);
		var row = Math.floor(pt[1] / tw);
		if (col >= 0 && row >= 0 && col < cc.cols && row < cc.rows) {
			cc.grid[col][row] = 1.0;
			if (cc.dirty_bounds === null) {
				cc.dirty_bounds = [col, row, col, row];
			} else {
				if (col < cc.dirty_bounds[0]) cc.dirty_bounds[0] = col;
				if (row < cc.dirty_bounds[1]) cc.dirty_bounds[1] = row;
				if (col > cc.dirty_bounds[2]) cc.dirty_bounds[2] = col;
				if (row > cc.dirty_bounds[3]) cc.dirty_bounds[3] = row;
			}
		}
	}
	cc.pixel_queue.length = 0;
	
	if (cc.dirty_bounds === null) {
		cc.is_running = false;
		return;
	}
	var cb = cc.dirty_bounds;
	
	var left = cb[0] - 1;
	var right = cb[2] + 1;
	var top = cb[1] - 1;
	var bottom = cb[3] + 1;
	
	if (left < 0) left = 0;
	if (top < 0) top = 0;
	if (right >= cc.cols) right = cc.cols - 1;
	if (bottom >= cc.rows) bottom = cc.rows - 1;
	
	for (var y = top; y <= bottom; ++y) {
		for (var x = left; x <= right; ++x) {
			var count = 0;
			var sum = 0;
			for (var dx = -1; dx <= 1; ++dx) {
				for (var dy = -1; dy <= 1; ++dy) {
					var nx = x + dx;
					var ny = y + dy;
					if (nx >= 0 && nx < cc.cols && ny >= 0 && ny < cc.rows && cc.grid[nx][ny] > 0) {
						sum += cc.grid[nx][ny];
						count++;
					}
				}
			}
			if (cc.grid[x][y] > 0) {
				if (count < 3) {
					cc.tgrid[x][y] = 0;
				} else if (count >= 5) {
					cc.tgrid[x][y] = 0;
				} else {
					cc.tgrid[x][y] = sum / count;
				}
			} else {
				if (count == 3) {
					cc.tgrid[x][y] = sum / count;
				} else {
					cc.tgrid[x][y] = 0;
				}
			}
		}
	}
	
	var filled_cells = [];
	for (var y = top; y <= bottom; ++y) {
		for (var x = left; x <= right; ++x) {
			var t = cc.tgrid[x][y] - .05;
			if (t < 0) t = 0;
			cc.grid[x][y] = t;
			if (t > 0) {
				filled_cells.push([x, y]);
			}
		}
	}
	
	conway_render();
	
	if (filled_cells.length == 0) {
		cc.is_running = false;
		cc.dirty_bounds = null;
		return;
	}
	
	var new_bounds = [filled_cells[0][0], filled_cells[0][1], filled_cells[0][0], filled_cells[0][1]];
	for (var i = 1; i < filled_cells.length; ++i) {
		var pt = filled_cells[i];
		if (pt[0] < new_bounds[0]) new_bounds[0] = pt[0];
		if (pt[1] < new_bounds[1]) new_bounds[1] = pt[1];
		if (pt[0] > new_bounds[2]) new_bounds[2] = pt[0];
		if (pt[1] > new_bounds[3]) new_bounds[3] = pt[1];
	}
	cc.dirty_bounds = new_bounds;
	
	cc.is_running = true;
	window.setTimeout('conway_update()', 75);
}

function conway_dirty(c, r) {
	if (cc.dirty_bounds === null) {
		cc.dirty_bounds = [c, r, c, r];
	} else {
		var cb = cc.dirty_bounds;
		if (cb[0] > c) cb[0] = c;
		if (cb[1] > r) cb[1] = r;
		if (cb[2] < c) cb[2] = c;
		if (cb[3] < r) cb[3] = r;
	}
}

function resize_grid(new_width, new_height, previous_grid) {
	var prev_width = previous_grid.length;
	var prev_height = prev_width == 0 ? 0 : previous_grid[0].length;
	var new_grid = [];
	for (var x = 0; x < new_width; ++x) {
		var col = [];
		for (var y = 0; y < new_height; ++y) {
			col.push((x < prev_width && y < prev_height) ? previous_grid[x][y] : 0);
		}
		new_grid.push(col);
	}
	return new_grid;
}

function conway_render() {
	var cc = conway_context;
	var tw = cc.tile_width;
	conway_draw_rect(0, 0, cc.cols * tw, cc.rows * tw, 0, 0, 0);
	for (var y = 0; y < cc.rows; ++y) {
		for (var x = 0; x < cc.cols; ++x) {
			var px = x * tw;
			var py = y * tw;
			var color = cc.grid[x][y];
			if (color > 0) {
				var c = Math.floor(color * 255);
				if (c < 0) c = 0;
				else if (c > 255) c = 255;
				conway_draw_rect(px, py, tw, tw, 0, 0, c);
			}
		}
	}
}

var _hd = '0123456789abcdef';
function conway_rgb2hex(r, g, b) {
	return '#' + _hd[r >> 4] + _hd[r & 15] + _hd[g >> 4] + _hd[g & 15] + _hd[b >> 4] + _hd[b & 15];
}

function conway_draw_rect(x, y, width, height, r, g, b) {
	var cc = conway_context;
	cc.draw.fillStyle = conway_rgb2hex(r, g, b);
	cc.draw.fillRect(x, y, width + .1, height + .1);
}

function conway_init() {
	conway_context.canvas_host = document.getElementById('top_canvas_host');
	if (!conway_context.canvas_host) {
		conway_context.enabled = false;
		return;
	}
	document.addEventListener('mousemove', conway_mousemove);
	conway_context.grid = [];
	conway_context.tgrid = [];
	conway_context.rows = 0;
	conway_context.cols = 0;
}

function conway_mousemove(ev) {
	var cc = conway_context;
	if (cc.enabled) {
		var pt = conway_get_pos_from_event(cc.canvas, ev);
		var x = Math.floor(pt[0] - cc.tile_width / 2);
		var y = Math.floor(pt[1] - cc.tile_width / 2);
		var tw = cc.tile_width;
		
		for (var dx = -1; dx <= 1; ++dx) {
			for (var dy = -1; dy <= 1; ++dy) {
				if (Math.random() < .5) {
					cc.pixel_queue.push([x + tw * dx, y + tw * dy]);
				}
			}
		}
		
		if (!cc.is_running) {
			cc.is_running = true;
			conway_update();
		}
	}
}

function conway_get_pos_from_event(element, ev) {
	if (!element) return [0, 0];
	var obj_left = 0;
	var obj_top = 0;
	while (element.offsetParent) {
		obj_left += element.offsetLeft;
		obj_top += element.offsetTop;
		element = element.offsetParent;
	}
	if (ev) return [ev.pageX - obj_left, ev.pageY - obj_top];
	return [window.event.x + document.body.scrollLeft - 2 - obj_left, window.event.y + document.body.scrollTop - 2 - obj_top];

}