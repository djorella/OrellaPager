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
$cooldown  = (int) (getenv('BEEP_COOLDOWN') ?: 60);   // seconds between beeps from one IP
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
		if (one("select 1 from beeps where uid = ? and date > now() - interval ? second limit 1", 'ii', $user, $cooldown)) {
			$notice = "Slow down! One beep every $cooldown seconds.";
		} else {
			run("insert into beeps(uid, post, date) values (?, ?, now())", 'is', $user, $beep);
			$notice = "Hey " . h($ip) . "! your last beep was: <strong class='pager has-text-white'>" . h($beep) . "</strong>";
		}
	}
}

// Beeps as a table. $pinged lists the uids the visitor has pinged.
function renderBeeps($beeps, $pinged, $emptyMessage) {
	if (!$beeps) {
		print "<p class='has-text-centered has-text-white console'>$emptyMessage</p>";
		return;
	}
	print <<<EOF
	<div class='table-container'>
	<table border=4 bordercolor='white' class='table is-bordered is-narrow is-fullwidth is-centered calc'>
	<tr>
	<TH class="console">UserId</TH>
	<th class="console">Beep</th>
	<th class="console">Date</th>
	<th class="console">Pinged Status</th>
	</tr>
EOF;
	foreach ($beeps as $row) {
		$uid  = (int) $row['uid'];
		$post = h($row['post']);
		$date = h($row['date']);
		$link = in_array($uid, $pinged) ? "<a href=index.php?unping=$uid>Unping</a>" : "<a href=index.php?ping=$uid>Ping!</a>";
		print <<<EOF
		<tr>
			<TD class="console">$uid</td>
			<td class="pager has-text-weight-semibold">$post</td>
			<td class="console">$date</td>
			<td class="console">$link</td>
		</tr>
EOF;
	}
	print "</table></div>";
}

$safeSiteKey = h($siteKey);

print <<<EOF
<!DOCTYPE html>
<html class="outline">
	
	<head>
		<title>The nostalgic pager board! | OrellaPager</title>
		<meta name="description" content="Ever wondered what a pager looked like, worked,
		 or its popularity in the 80s & 90s? This website brings the nostalgia for 
		 those who had one before. Have fun!">
		<script defer src="https://use.fontawesome.com/releases/v5.3.1/js/all.js"></script>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.3/jquery.min.js"></script>	
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		

		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.0/css/bulma.min.css">
		<link rel="stylesheet" href="fonts/pager.css">
		<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
		<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
		<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
		<link rel="manifest" href="/site.webmanifest">
		
		
		<style type="text/css">
		.pager {
			font-family: 'My Pager';
    		font-weight: normal;
    		font-style: normal;
		}
		.calc {
			background-color: #DFFF87;
		}
		.outline {
			background-color: #606060FF;
		}
		.console {
			font: 1.0rem Inconsolata, monospace;
		}
		.center {
		 text-align: center;
 		 border: 3px solid white;
		}
		.narrow tr td{
			padding: 0.40em 0.0em
			background-color: black;
		}
		/* Fit the table to the screen: fixed column widths, long words break
		   instead of stretching the table (and forcing a sideways scroll). */
		.table { table-layout: fixed; }
		.table th, .table td { overflow-wrap: break-word; word-break: break-word; }
		.table th:nth-child(1) { width: 15%; }
		.table th:nth-child(2) { width: 43%; }
		.table th:nth-child(3) { width: 25%; }
		.table th:nth-child(4) { width: 17%; }
		@media (max-width: 600px) {
			.table th, .table td { padding: 0.3em 0.35em; font-size: 0.8rem; }
			.table th, .table td:nth-child(3) { font-size: 0.7rem; }
		}
		@media (max-width: 340px) {
			.table th, .table td { padding: 0.3em 0.2em; }
			.table td:nth-child(1), .table td:nth-child(4) { font-size: 0.7rem; }
		}
		/* Sound button on the left, Beep on the right, always on the same line. */
		.action-row { display: flex; justify-content: space-between; align-items: center; }
		.nope {
			position: absolute; left: -9999px; width: 1px; height: 1px; opacity: 0;
		}
		</style>
		<script type="text/JavaScript">
			function playBeep () {
				document.getElementById('play').play();
			}
			function playNotif () {
				document.getElementById('notif').play();
			}
		</script>
		<script src='https://www.hCaptcha.com/1/api.js' async defer></script>
	</head>
	<body>
		<h2 class="title pager has-text-centered has-text-white has-text-weight-bold pt-5">Welcome to OrellaPager! </h2>
			<br>	
		<div  >
				<div is-centered>

					<form id="beep" method="post">
					<div class=" is-expanded">
						<textarea class="textarea is-success is-focus calc pager has-text-weight-semibold" placeholder="Beep anything nostalgic!" name=beep maxlength="$maxLength" required></textarea>
					</div>	
					<div class="nope" aria-hidden="true">
						<label>Leave this field empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
					</div>
					<audio id="play" src="beep-02.wav" type="audio/wav" preload="auto"></audio>
					<audio id="notif" src="GTA3pager.mp3" type="audio/mpeg"></audio>
					<div class="buttons is-right pt-4">
						<div class="h-captcha" data-sitekey="$safeSiteKey"></div>
					</div>
					<div class="action-row pb-5">
						<button type="button" onclick="playNotif()" class="button is-rounded is-black console" aria-label="Play pager sound"></button>
						<input onclick="playBeep()" class="button is-rounded is-white console" type=submit value="Beep">
					</div>
					</form>
				</div>
			</div>
	</body>
</html>

EOF;
if ($notice !== '') {
	print "<p class='has-text-centered console has-text-white pt-3'>$notice</p>";
}

$pinged = $user === null ? [] : array_column(run("select beeper from pings where uid = ?", 'i', $user), 'beeper');

renderBeeps(run("select * from beeps order by date desc"), $pinged, 'No beeps yet.');
print "<br><HR class='has-background-white'><br>";

print "<h2 class='title pager has-text-white has-text-centered has-text-weight-bold'>Users whom you pinged!</h2>";
renderBeeps(
	$user === null ? [] : run("select * from beeps where uid in (select beeper from pings where uid = ?) order by date desc", 'i', $user),
	$pinged,
	'None. Feel free to Ping any beeps in this Pager!!'
);

print <<<EOF
<br><br>
<center>
<a class='has-text-centered has-text-white console' href='https://orellapager.me/aboutme/about.html'>About My Creator :D</a>
</center>
<br><br>
EOF;

if ($debug) {
	print "<script>\n";
	foreach ($debug as $m) {
		print 'console.warn(' . json_encode($m, JSON_HEX_TAG | JSON_HEX_AMP) . ");\n";
	}
	print "</script>\n";
}
