<?php
// OrellaPager: a pager message board. Settings come from environment
// variables (docker-compose.yml / .env).

// Errors never appear on the page. They go to the server log and, with
// APP_DEBUG=1, to the browser's DevTools console.
ini_set('display_errors', '0');
$debug = [];
if (getenv('APP_DEBUG') === '1') {
	set_error_handler(function ($no, $msg, $file, $line) use (&$debug) {
		$debug[] = "PHP: $msg (" . basename($file) . ":$line)";
		return false;
	});
}

$ip        = $_SERVER['REMOTE_ADDR'];
$siteKey   = getenv('HCAPTCHA_SITEKEY') ?: '6a29ba4e-d39b-4e58-b64a-5b47ba5cbad2';
$maxLength = 140;                                      // beeps.post is varchar(140): 140 bytes

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = mysqli_connect(getenv('DB_HOST') ?: 'localhost', getenv('DB_USER') ?: 'root',
                       getenv('DB_PASS') ?: '', getenv('DB_NAME') ?: 'orellapager');
// The tables are latin1 and hold UTF-8 text byte for byte. Talking latin1 to
// the database keeps those bytes untouched, so every language and emoji is
// stored and shown correctly. (A utf8mb4 connection garbles old rows and
// crashes on new Cyrillic or emoji beeps.)
mysqli_set_charset($conn, 'latin1');

// Run a prepared query. SELECTs return their rows, other statements return [].
function run($sql, $types = '', ...$params) {
	global $conn;
	$stmt = mysqli_prepare($conn, $sql);
	if ($types) mysqli_stmt_bind_param($stmt, $types, ...$params);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);
	return $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
}

// First column of the first row, or null.
function one($sql, $types = '', ...$params) {
	$rows = run($sql, $types, ...$params);
	return $rows ? array_values($rows[0])[0] : null;
}

function h($text) {
	return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

// A blocked posting attempt: logged for the server log and the monthly
// report (docker/spam-report.php reads these tab-separated lines).
function reject($reason, $detail = '') {
	global $ip;
	error_log("beep rejected ($reason) from $ip $detail");
	$line = implode("\t", [gmdate('Y-m-d H:i:s'), $ip, $reason, preg_replace('/\s+/', ' ', $detail)]) . "\n";
	@file_put_contents(getenv('REJECT_LOG') ?: '/var/log/orellapager/rejections.log', $line, FILE_APPEND | LOCK_EX);
}

// Ask hCaptcha whether the visitor's token is genuine. It is bound to our
// site key, expires quickly and works only once. Fails closed.
function captchaPassed($token) {
	global $ip, $siteKey;
	$secret = getenv('HCAPTCHA_SECRET');
	if (!$secret) {
		error_log('HCAPTCHA_SECRET is not set, so no beep can be accepted');
		return false;
	}
	$ch = curl_init('https://api.hcaptcha.com/siteverify');
	curl_setopt_array($ch, [
		CURLOPT_POSTFIELDS     => http_build_query(['secret' => $secret, 'response' => $token, 'remoteip' => $ip, 'sitekey' => $siteKey]),
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT        => 10,
	]);
	$reply = json_decode((string) curl_exec($ch), true);
	if (($reply['success'] ?? false) !== true) {
		reject('captcha-failed', json_encode($reply['error-codes'] ?? 'no response'));
		return false;
	}
	return true;
}

// A visitor is only stored once they beep or ping, never for just looking.
$user = one("select uid from users where ip = ?", 's', $ip);
function registerUser() {
	global $user, $ip;
	if ($user === null) {
		run("insert ignore into users(ip) values (?)", 's', $ip);
		$user = one("select uid from users where ip = ?", 's', $ip);
	}
}

// ?ping=N and ?unping=N. Only people who have beeped can be pinged.
$ping = filter_var($_REQUEST['ping'] ?? null, FILTER_VALIDATE_INT);
if ($ping && one("select 1 from beeps where uid = ? limit 1", 'i', $ping)) {
	registerUser();
	run("insert ignore into pings(uid, beeper) values (?, ?)", 'ii', $user, $ping);
}
$unping = filter_var($_REQUEST['unping'] ?? null, FILTER_VALIDATE_INT);
if ($unping && $user !== null) {
	run("delete from pings where uid = ? and beeper = ?", 'ii', $user, $unping);
}

// Posting a beep.
$notice = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['beep'])) {
	$beep  = trim((string) $_POST['beep']);
	$token = (string) ($_POST['h-captcha-response'] ?? '');

	if (($_POST['website'] ?? '') !== '') {
		reject('honeypot', mb_substr($beep, 0, 80));   // hidden field, only bots fill it: no reply
	} elseif ($beep === '' || strlen($beep) > $maxLength) {
		$notice = "Your beep must be between 1 and $maxLength characters (non-English letters and emoji take 2 to 4 each).";
	} elseif ($token === '') {
		reject('no-captcha-token', mb_substr($beep, 0, 80));
		$notice = 'Please check the captcha form.';
	} elseif (!captchaPassed($token)) {
		$notice = 'Leave you NASTY robot!!!';
	} else {
		registerUser();
		run("insert into beeps(uid, post, date) values (?, ?, now())", 'is', $user, $beep);
		// Redirect to a normal page view so that reloading, pulling to refresh or going back
		// can never send the form (and the used-up captcha answer) a second time.
		header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?posted=' . mysqli_insert_id($conn), true, 303);
		exit;
	}
}

// The page after a successful post. The message is read back from the saved beep, and only
// for the visitor's own beep. The script at the end of the page then cleans the address,
// so a reload shows the board as if it had just been opened.
$posted = filter_var($_GET['posted'] ?? null, FILTER_VALIDATE_INT);
if ($posted && $user !== null) {
	$mine = one("select post from beeps where bip = ? and uid = ?", 'ii', $posted, $user);
	if ($mine !== null) {
		$notice = "Hey " . h($ip) . "! your last beep was: <strong>" . h($mine) . "</strong>";
	}
}

// Beeps as a table. $pinged lists the uids the visitor has pinged.
function renderBeeps($beeps, $pinged, $emptyMessage, $id) {
	print "<div class='collapsible' id='$id'><div class='collapsible-inner'><div class='table-container'>";
	if (!$beeps) {
		print "<p class='empty'>$emptyMessage</p></div></div></div>\n";
		return;
	}
	print <<<EOF
	<table class="board-table">
		<thead>
			<tr><th scope="col">UserId</th><th scope="col">Beep</th><th scope="col">Date</th><th scope="col">Pinged Status</th></tr>
		</thead>
		<tbody>
EOF;
	foreach ($beeps as $row) {
		$uid  = (int) $row['uid'];
		$post = h($row['post']);
		$date = h($row['date']);
		$link = in_array($uid, $pinged) ? "<a href=index.php?unping=$uid>Unping</a>" : "<a href=index.php?ping=$uid>Ping!</a>";
		print "\t\t\t<tr><td>$uid</td><td>$post</td><td>$date</td><td>$link</td></tr>\n";
	}
	print "\t\t</tbody>\n\t</table>\n</div></div></div>\n";
}

$safeSiteKey = h($siteKey);

print <<<EOF
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>The nostalgic pager board! | OrellaPager</title>
	<meta name="description" content="Ever wondered what a pager looked like, worked, or its popularity in the 80s & 90s? This website brings the nostalgia for those who had one before. Have fun!">

	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
	<link rel="manifest" href="/site.webmanifest">

	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=VT323&display=swap">
	<link rel="stylesheet" href="fonts/pager.css">

	<style>
		:root {
			--lcd: #DFFF87;        /* page and pager screen */
			--ink: #1c2410;        /* text and borders: near-black with a green tint */
			--row-a: #DFFF87;
			--row-b: #CCEB69;
			--head: #B4D84F;
			--shell-top: #474f3b;
			--shell-bottom: #2b3023;
			--edge: #161910;
		}
		*, *::before, *::after { box-sizing: border-box; }
		html { background: var(--lcd); scroll-behavior: smooth; }
		body { margin: 0; background: var(--lcd); color: var(--ink); font: 1.35rem/1.15 'VT323', monospace; }
		a { color: var(--ink); }
		.wrap { max-width: 796px; margin: 0 auto; padding: 1rem 0.5rem 3rem; }
		.visually-hidden, .hp { position: absolute; left: -9999px; width: 1px; height: 1px; overflow: hidden; }

		/* Title keeps the original pager font */
		.site-title { font: normal clamp(1.6rem, 6vw, 2.4rem)/1.2 'My Pager', 'VT323', monospace; text-align: center; margin: 0.5rem 0 1.5rem; }

		/* Pager chassis */
		.pager-device {
			background: linear-gradient(180deg, var(--shell-top), var(--shell-bottom));
			border: 4px solid var(--edge);
			border-radius: 28px;
			padding: 20px 22px 24px;
			box-shadow: inset 0 2px 0 rgba(255,255,255,0.14), inset 0 -5px 0 rgba(0,0,0,0.28),
			            0 7px 0 var(--edge), 0 16px 26px rgba(0,0,0,0.35);
		}
		.bezel { background: #252a1d; border: 3px solid var(--edge); border-radius: 16px; padding: 10px; box-shadow: inset 0 3px 8px rgba(0,0,0,0.6); }

		/* The textarea is the LCD screen */
		.lcd { position: relative; background: var(--lcd); border: 3px solid #12150c; border-radius: 8px; box-shadow: inset 0 0 18px rgba(60,90,0,0.35); }
		.lcd:focus-within { outline: 3px solid rgba(255,255,255,0.55); outline-offset: 2px; }
		.lcd textarea, .lcd-prompt {
			font: clamp(1.9rem, 6vw, 3.2rem)/1.05 'VT323', monospace;
			padding: 0.7rem 1rem;
			color: var(--ink);
		}
		.lcd textarea {
			display: block; width: 100%; height: 8rem; resize: none;
			background: transparent; border: 0; outline: none;
			caret-color: var(--ink); caret-shape: block;
		}
		.lcd textarea:placeholder-shown { caret-color: transparent; }   /* the blinking block below is the cursor */
		.lcd-prompt { position: absolute; inset: 0; pointer-events: none; overflow: hidden; }
		.lcd textarea:not(:placeholder-shown) ~ .lcd-prompt { display: none; }
		.cursor { display: inline-block; width: 0.5em; height: 0.8em; margin-left: 0.15em; background: var(--ink); vertical-align: -0.05em; animation: blink 1s steps(1, end) infinite; }
		@keyframes blink { 50% { opacity: 0; } }

		/* Sound button, captcha, Beep button: one row, centred vertically */
		.controls { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: 1rem; margin-top: 1.25rem; }
		.captcha-slot { justify-self: center; background: #20241a; border-radius: 12px; padding: 4px; box-shadow: inset 0 2px 6px rgba(0,0,0,0.6); }
		.h-captcha { width: 302px; height: 76px; }
		.btn-sound, .btn-beep { border-radius: 50%; cursor: pointer; }
		.btn-sound {
			width: 58px; height: 58px; border: 3px solid #050604;
			background: radial-gradient(circle at 35% 30%, #4b4b49, #121212 55%, #000);
			box-shadow: 0 4px 0 #050604, inset 0 -4px 6px rgba(0,0,0,0.6), inset 0 2px 3px rgba(255,255,255,0.2);
		}
		.btn-beep {
			width: 76px; height: 76px; border: 3px solid #a3a39a; color: #1b1d3a; font: 1.7rem/1 'VT323', monospace;
			background: radial-gradient(circle at 35% 30%, #fff, #ececec 60%, #c8c8bf);
			box-shadow: 0 4px 0 #6d6d64, inset 0 -3px 5px rgba(0,0,0,0.12);
		}
		.btn-sound:active, .btn-beep:active { transform: translateY(3px); box-shadow: 0 1px 0 #050604; }
		.btn-sound:focus-visible, .btn-beep:focus-visible { outline: 3px solid #fff; outline-offset: 3px; }

		/* Message after posting, and the scroll hint */
		.notice { text-align: center; margin: 1.5rem 0 0; font-size: 1.6rem; }
		/* Floating labels that fold a table away and bring it back */
		.toggle { background: none; border: 0; padding: 0.25rem 0.75rem; font: inherit; color: inherit; cursor: pointer; }
		.toggle:hover span { text-decoration: underline; }
		.toggle:focus-visible { outline: 3px solid var(--ink); outline-offset: 2px; border-radius: 6px; }
		.section-title:hover, .section-title:focus-within, .scroll-hint:hover span, .scroll-hint:focus-visible span { animation-play-state: paused; }   /* hold still while aimed at */
		.scroll-hint { display: block; width: 100%; text-align: center; margin: 1.5rem 0 0.75rem; font-size: 1.5rem; }
		.scroll-hint span { display: inline-block; animation: nudge 1.6s ease-in-out infinite; }
		.scroll-hint .hint-collapsed { display: none; }
		.scroll-hint[aria-expanded="false"] .hint-desktop, .scroll-hint[aria-expanded="false"] .hint-mobile { display: none !important; }
		.scroll-hint[aria-expanded="false"] .hint-collapsed { display: inline-block; }
		.emoji canvas { width: 32px; height: 32px; margin: -4px 1px; vertical-align: middle; image-rendering: pixelated; }
		.emoji .orig { display: none; }
		.show-original .emoji canvas { display: none; }
		.show-original .emoji .orig { display: inline; font-size: 1.25em; }
		.emoji-toggle { margin-left: 0.4rem; padding: 0 0.4rem; font: normal 1rem/1.3 'VT323', monospace; color: var(--ink); background: transparent; border: 2px solid var(--ink); border-radius: 4px; cursor: pointer; vertical-align: middle; }
		.emoji-toggle:hover { background: var(--head); }
		.emoji-toggle:focus-visible { outline: 3px solid var(--ink); outline-offset: 2px; }
		.chev { display: inline-block; width: 0; height: 0; margin-left: 0.5rem; vertical-align: 0.1em; border-left: 0.35rem solid transparent; border-right: 0.35rem solid transparent; border-top: 0.5rem solid currentColor; transition: transform 0.3s; }
		.section-title .toggle[aria-expanded="false"] .chev { transform: rotate(-90deg); }
		.collapsible { display: grid; grid-template-rows: 1fr; transition: grid-template-rows 0.35s ease; scroll-margin-top: 1rem; }
		.collapsible.is-collapsed { grid-template-rows: 0fr; }
		.collapsible-inner { overflow: hidden; min-height: 0; }
		.hint-mobile { display: none !important; }
		@keyframes nudge { 50% { transform: translateY(4px); } }

		/* Tables */
		.board-table { width: 100%; table-layout: fixed; border-collapse: collapse; border: 3px solid var(--ink); font-size: 1.4rem; }
		.board-table th, .board-table td { border: 2px solid var(--ink); padding: 0.3rem 0.5rem; text-align: left; vertical-align: top; overflow-wrap: break-word; word-break: break-word; }
		.board-table th { background: var(--head); font-weight: normal; font-size: 1.5rem; }
		.board-table tbody tr:nth-child(odd) { background: var(--row-a); }
		.board-table tbody tr:nth-child(even) { background: var(--row-b); }
		.board-table th:nth-child(1) { width: 15%; }
		.board-table th:nth-child(2) { width: 43%; }
		.board-table th:nth-child(3) { width: 25%; }
		.board-table th:nth-child(4) { width: 17%; }
		.empty { text-align: center; }
		.section-title { text-align: center; font-weight: normal; font-size: 2rem; margin: 2.5rem 0 1rem; animation: nudge 1.6s ease-in-out infinite; }
		.credit { text-align: center; margin: 2.5rem 0 0; }
		.version { text-align: center; margin: 0.75rem 0 0; font-size: 1.1rem; }

		/* Phones: stack the captcha above the two buttons, smaller table text */
		@media (max-width: 560px) {
			.pager-device { padding: 14px 12px 18px; border-radius: 22px; }
			.lcd textarea { height: 6.5rem; }
			.controls { grid-template-columns: 1fr auto; row-gap: 1.1rem; }
			.captcha-slot { grid-column: 1 / -1; grid-row: 1; }
			.btn-sound { grid-column: 1; grid-row: 2; justify-self: start; }
			.btn-beep { grid-column: 2; grid-row: 2; }
			.hint-desktop { display: none !important; }
			.hint-mobile { display: inline-block !important; }
			.board-table { font-size: 1.2rem; }
			.board-table th { font-size: 1.05rem; }
			.board-table td:nth-child(3) { font-size: 1.05rem; }
			.board-table th, .board-table td { padding: 0.25rem 0.3rem; }
			.board-table th:nth-child(1) { width: 17%; }
			.board-table th:nth-child(2) { width: 38%; }
			.board-table th:nth-child(3) { width: 28%; }
			.board-table th:nth-child(4) { width: 17%; }
		}
		/* Very narrow screens: shrink the fixed-size captcha so it never overflows the pager */
		@media (max-width: 357px) {
			.h-captcha { transform: scale(0.9); margin: -4px -15px; }
		}
		@media (max-width: 327px) {
			.h-captcha { transform: scale(0.8); margin: -8px -30px; }
			.board-table th, .board-table td { padding: 0.2rem 0.2rem; }
		}
		@media (prefers-reduced-motion: reduce) {
			html { scroll-behavior: auto; }
			.cursor, .scroll-hint span, .section-title { animation: none; }
			.collapsible, .chev { transition: none; }
		}
	</style>
	<script>
		function playBeep() { document.getElementById('play').play(); }
		function playNotif() { document.getElementById('notif').play(); }

		// Fold a table away or bring it back. The choice survives a page reload.
		function savedPanels() { try { return JSON.parse(sessionStorage.getItem('orellapager-collapsed')) || {}; } catch (e) { return {}; } }
		function setPanel(btn, open, animate) {
			var panel = document.getElementById(btn.getAttribute('aria-controls'));
			if (!animate) panel.style.transition = 'none';
			btn.setAttribute('aria-expanded', open ? 'true' : 'false');
			panel.classList.toggle('is-collapsed', !open);
			panel.querySelector('.collapsible-inner').inert = !open;
			if (!animate) { panel.offsetHeight; panel.style.transition = ''; }
			return panel;
		}
		function togglePanel(btn) {
			var open = btn.getAttribute('aria-expanded') !== 'true';
			var panel = setPanel(btn, open, true);
			try { var s = savedPanels(); s[panel.id] = !open; sessionStorage.setItem('orellapager-collapsed', JSON.stringify(s)); } catch (e) {}
			if (open && btn.hasAttribute('data-scroll')) {
				// scroll once the table has finished opening, or the page is still too short to scroll far enough
				var go = function () { panel.scrollIntoView({ behavior: 'smooth', block: 'start' }); };
				if (parseFloat(getComputedStyle(panel).transitionDuration) > 0) panel.addEventListener('transitionend', go, { once: true }); else go();
			}
		}
		function restorePanels() {
			var s = savedPanels();
			document.querySelectorAll('[aria-controls]').forEach(function (b) { if (s[b.getAttribute('aria-controls')]) setPanel(b, false, false); });
		}
	</script>
	<script src="pixel-emoji.js" defer></script>
	<script src="https://js.hcaptcha.com/1/api.js" async defer></script>
</head>
<body>
<main class="wrap">
	<h1 class="site-title">Welcome to OrellaPager!</h1>

	<form id="beep" class="pager-device" method="post">
		<div class="bezel">
			<div class="lcd">
				<label class="visually-hidden" for="beep-text">Your beep</label>
				<textarea id="beep-text" name="beep" maxlength="$maxLength" placeholder=" " required></textarea>
				<div class="lcd-prompt" aria-hidden="true">BEEP ANYTHING NOSTALGIC!<span class="cursor"></span></div>
			</div>
		</div>
		<div class="hp" aria-hidden="true">
			<label>Leave this field empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
		</div>
		<audio id="play" src="beep-02.wav" type="audio/wav" preload="auto"></audio>
		<audio id="notif" src="GTA3pager.mp3" type="audio/mpeg"></audio>
		<div class="controls">
			<button type="button" class="btn-sound" onclick="playNotif()" aria-label="Play pager sound"></button>
			<div class="captcha-slot"><div class="h-captcha" data-sitekey="$safeSiteKey"></div></div>
			<button type="submit" class="btn-beep" onclick="playBeep()">Beep</button>
		</div>
	</form>

EOF;
if ($notice !== '') {
	print "\t<p class='notice'>$notice</p>\n";
}

$pinged = $user === null ? [] : array_column(run("select beeper from pings where uid = ?", 'i', $user), 'beeper');

print <<<EOF
	<button type="button" class="toggle scroll-hint" aria-expanded="true" aria-controls="beeps" data-scroll onclick="togglePanel(this)"><span class="hint-desktop">&#9660; Scroll for Beeps</span><span class="hint-mobile">&#9650; Swipe Up for Beeps</span><span class="hint-collapsed">&#9660; Show Beeps</span></button>
	<h2 class="visually-hidden">Beeps</h2>

EOF;
renderBeeps(run("select * from beeps order by date desc"), $pinged, 'No beeps yet.', 'beeps');

print "\t<h2 class='section-title'><button type='button' class='toggle' aria-expanded='true' aria-controls='pinged' onclick='togglePanel(this)'>Users whom you pinged!<span class='chev' aria-hidden='true'></span></button></h2>\n";
renderBeeps(
	$user === null ? [] : run("select * from beeps where uid in (select beeper from pings where uid = ?) order by date desc", 'i', $user),
	$pinged,
	'None. Feel free to Ping any beeps in this Pager!!',
	'pinged'
);

print "\t<p class='credit'><a href='https://orellapager.me/aboutme/about.html'>About My Creator :D</a></p>\n";
print "\t<p class='version'>Version 3.2</p>\n";
print "</main>\n";

if ($debug) {
	print "<script>\n";
	foreach ($debug as $m) {
		print 'console.warn(' . json_encode($m, JSON_HEX_TAG | JSON_HEX_AMP) . ");\n";
	}
	print "</script>\n";
}
print "<script>restorePanels(); if (location.search.indexOf('posted=') !== -1) history.replaceState(null, '', location.pathname);</script>\n</body>\n</html>\n";
