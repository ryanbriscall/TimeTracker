<?php
$db = __DIR__ . DIRECTORY_SEPARATOR . str_replace('.php','.json',basename(__FILE__));
if (isset($_GET['load'])) {
	echo file_get_contents($db);
	exit;
}
if (isset($_GET['save'])) {
	if (isset($_POST['saved']) && isset($_POST['active']) && isset($_POST['date']) && isset($_POST['slots'])) {
		$saved = json_decode($_POST['saved']);
		$active = json_decode($_POST['active']);
		$date = json_decode($_POST['date']);
		$slots = json_decode($_POST['slots']);
		$data = json_decode(file_get_contents($db));
		$data->$date = $slots;
		$data->active = $active;
		$data->saved = $saved;
		file_put_contents($db, json_encode($data));
		echo "true";
	}
	else { echo "false"; }
	exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title><?=explode('.',basename(__FILE__))[0]?></title>

<style>
:root { font-size: 16px; }
@media (min-width: 320px) and (max-width: 1200px) {
	:root { font-size: calc(16px + (28 - 16) * ((100vw - 320px) / (1200 - 320))); }
}
@media (min-width: 1200px) { :root { font-size: 28px; } }
html { font-family: sans-serif; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
body {
	line-height: 1.75;
	font-family: "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
	text-rendering: optimizeLegibility;
	-webkit-font-smoothing: antialiased;
	-moz-osx-font-smoothing: grayscale;
	margin: 0.8em;
	text-align: center;
}

/* More */
button,
input,
select {
	font-family: inherit;
	font-size: inherit;
}

/* Other */
*, *::before, *::after { box-sizing: border-box; }

button.slot { margin: 0 0.1em; outline: none; }
button.slot.active { border-style: inset; }
button.slot .time { display: block; font-size: 0.65em; }

</style>
<style id="cssdark">
body {color: #eee; background: #111; }

input,
select,
button,
textarea { color: #ccc; background: #333; }
</style>

<script>
function dbDate(offset = 0) {
	var d = new Date();
	d.setDate(d.getDate() + offset);
	return d.getFullYear()+'-'+('0'+(d.getMonth()+1)).slice(-2)+'-'+('0' + d.getDate()).slice(-2);
}
function dbTimestamp() {
	return Math.floor(Date.now() / 1000);
}

var els = {};
var timeEls = {};

var slots = ['Break', 'Active', 'A', 'B', 'C'];

var track = {};
slots.forEach(function(k) { track[k] = 0; });

var data = { 'active': 'Break' };
data[dbDate()] = {};
slots.forEach(function(k) { data[dbDate()][k] = 0; });

var timestamp = dbTimestamp();

var date = dbDate();

var saveInterval = 30;

function save() {
	var xhr = new XMLHttpRequest();
	xhr.onreadystatechange = function(data) {
		if (xhr.readyState == 4) {
			if (xhr.status == 200 || xhr.status == 206) {
				if (xhr.response === false) { slots = {}; alert('Error saving.'); }
			}
		}
	};
	var url = new URL(location.href);
	var slots = data[dbDate()];
	var active = data['active'];
	url.searchParams.set('save', '1');
	xhr.open('POST', url);
	xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
	xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
	xhr.responseType = 'json';
	xhr.send('saved='+data['saved']+'&active='+JSON.stringify(active)+'&date='+JSON.stringify(dbDate())+'&slots='+JSON.stringify(slots));
}

function switchSlot(slot) {
	els[data['active']].classList.remove('active');
	els[slot].classList.add('active');
	data['active'] = slot;
}

document.addEventListener("DOMContentLoaded", function() {
	var xhr = new XMLHttpRequest();
	xhr.onreadystatechange = function() {
		if (xhr.readyState == 4) {
			if (xhr.status == 200 || xhr.status == 206) {
				if (xhr.response !== null) {
					data = xhr.response;
					slots = data.slots;
					slots.forEach(function(k) {
						track[k] = 0;
					});

					if (!data.hasOwnProperty(dbDate())) {
						data[dbDate()] = {};
						slots.forEach(function(k) {
							data[dbDate()][k] = 0; 
						});
					}

					slots.forEach(function(k) { 
						app.insertAdjacentHTML(
							'beforeend',
							`<button id="${k}" class="slot ${(data['active'] === k ? 'active' : '')}"><span>${k}</span><span class="time">${(data[dbDate()][k] / 60 / 100).toFixed(2)}</span></button>`
						);
						els[k] = document.getElementById(k);
						timeEls[k] = document.getElementById(k).querySelector('.time');
					});
					
					document.addEventListener("click", function(e) {
						var el = e.target;
						if (!el.classList.contains("slot")) {
							el = el.closest('button.slot');
						}
						if (el !== null) {
							switchSlot(el.getAttribute('id'));
						}
					});

					document.addEventListener("keydown", function(e) {
						for (var n = 1; n <= 9; n++) {
							if (e.code === "Digit"+n) {
								if (slots[(n-1)] !== data['active']) {
									switchSlot(slots[(n-1)]);
								}
							}
						}
					});

					var clockInterval = setInterval(function() {
						if (!data.hasOwnProperty(dbDate())) {
							data[dbDate()] = {};
							slots.forEach(function(k) {
								data[dbDate()][k] = 0; 
							});
						}

						var ts = dbTimestamp();
						var active = data['active'];
						if (track[active] !== ts) {
							track[active] = ts;
							data[dbDate()][active] += 1;
							timeEls[active].innerHTML = (data[dbDate()][active] / 60 / 100).toFixed(2);
						}

						var elapsed = ts - data['saved'];
						if (elapsed > saveInterval) {
							data['saved'] = ts;
							save();
						}
					}, 500);

				}
			}
			else {
				console.log('ERROR');
			}
		}
	};
	var url = new URL(location.href);
	url.searchParams.set('load', '1');
	xhr.open('GET', url);
	xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
	xhr.responseType = 'json';
	xhr.send();

});
</script>

</head>
<body id="app">
</body>
</html>
