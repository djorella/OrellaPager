<?php
// OrellaPager
//
// Errors are never displayed on the page. They go to the Apache error log
// (docker logs) and, when APP_DEBUG=1, to the browser DevTools console.
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$debugMessages = [];
if (getenv('APP_DEBUG') === '1') {
	set_error_handler(function ($errno, $errstr, $errfile, $errline) use (&$debugMessages) {
		$debugMessages[] = "PHP [$errno] $errstr in " . basename($errfile) . ":$errline";
		return false; // keep the normal logging too
	});
}

$dbhost = getenv('DB_HOST') ?: 'localhost';
$dbuser = getenv('DB_USER') ?: 'root';
$dbpass = getenv('DB_PASS') ?: '';
$dbname = getenv('DB_NAME') ?: 'orellapager';

$hcaptchaSiteKey = getenv('HCAPTCHA_SITEKEY') ?: '6a29ba4e-d39b-4e58-b64a-5b47ba5cbad2';
$beepCooldown    = (int) (getenv('BEEP_COOLDOWN') ?: 60);   // seconds between beeps per IP
$beepMaxLength   = 140;                                     // beeps.post is varchar(140)

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);
mysqli_set_charset($conn, 'utf8mb4');

function query($query) {
	global $conn;
	return mysqli_query($conn, $query);
}

// Run a prepared statement; returns the mysqli_stmt (use ->get_result() for SELECTs).
function prepared($sql, $types = '', ...$params) {
	global $conn;
	$stmt = mysqli_prepare($conn, $sql);
	if ($types !== '') {
		mysqli_stmt_bind_param($stmt, $types, ...$params);
	}
	mysqli_stmt_execute($stmt);
	return $stmt;
}

function getSingle($sql, $types = '', ...$params) {
	$result = prepared($sql, $types, ...$params)->get_result();
	$row = $result ? mysqli_fetch_row($result) : null;
	return $row ? $row[0] : null;
}

function clientIp() {
	return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// Records a blocked posting attempt: one tab-separated line per attempt in
// REJECT_LOG (summarised and emailed monthly by docker/spam-report.php) and a
// line in the Apache error log.
function logRejection($reason, $detail = '') {
	$ip = clientIp();
	error_log("beep rejected ($reason) from $ip" . ($detail !== '' ? ": $detail" : ''));
	$file = getenv('REJECT_LOG') ?: '/var/log/orellapager/rejections.log';
	$line = implode("\t", [gmdate('Y-m-d H:i:s'), $ip, $reason, str_replace(["\t", "\n", "\r"], ' ', $detail)]) . "\n";
	@file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
}

// uid of the visitor's IP, or null if this IP has never beeped or pinged.
// Visitors are NOT stored just for viewing the page: a users row is only
// created by getOrCreateUid() when they actually beep or ping.
function getUid() {
	return getSingle("select uid from users where ip = ?", 's', clientIp());
}

function getOrCreateUid() {
	$uid = getUid();
	if ($uid === null) {
		prepared("insert ignore into users(ip) values (?)", 's', clientIp());
		$uid = getUid();
	}
	return (int) $uid;
}

function isPoster($uid) {
	return getSingle("select 1 from beeps where uid = ? limit 1", 'i', $uid) !== null;
}

// Server-side hCaptcha check. Fails closed: no secret, no network, wrong
// hostname or a stale token all count as "not verified".
function verifyCaptcha($token) {
	$secret = getenv('HCAPTCHA_SECRET');
	if ($secret === false || $secret === '') {
		error_log('HCAPTCHA_SECRET is not set; refusing to accept beeps');
		return false;
	}
	$ch = curl_init('https://api.hcaptcha.com/siteverify');
	curl_setopt_array($ch, [
		CURLOPT_POST           => true,
		CURLOPT_POSTFIELDS     => http_build_query([
			'secret'   => $secret,
			'response' => $token,
			'remoteip' => clientIp(),
			'sitekey'  => $GLOBALS['hcaptchaSiteKey'],
		]),
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT        => 10,
	]);
	$body = curl_exec($ch);
	curl_close($ch);
	$r = is_string($body) ? json_decode($body, true) : null;
	if (!is_array($r) || ($r['success'] ?? false) !== true) {
		logRejection('captcha-failed', json_encode($r['error-codes'] ?? 'no response'));
		return false;
	}
	// The token must have been solved on our own site, not harvested elsewhere.
	// hCaptcha's public test keys (local development) report a dummy hostname.
	$testKeys = str_starts_with($secret, '0x0000000000000000') && ($r['hostname'] ?? '') === 'dummy-key-pass';
	$allowed = array_filter(array_map('trim', explode(',', getenv('HCAPTCHA_HOSTNAMES') ?: 'orellapager.me,www.orellapager.me')));
	if (!$testKeys && $allowed && !in_array($r['hostname'] ?? '', $allowed, true)) {
		logRejection('captcha-wrong-hostname', (string) ($r['hostname'] ?? ''));
		return false;
	}
	// And be fresh: solved less than 5 minutes ago.
	$ts = isset($r['challenge_ts']) ? strtotime($r['challenge_ts']) : false;
	if ($ts !== false && time() - $ts > 300) {
		logRejection('captcha-stale-token');
		return false;
	}
	return true;
}

function renderRows($beeps) {
	global $user;
	foreach ($beeps as $row) {
		$uid  = (int) $row['uid'];
		$post = htmlspecialchars($row['post'] ?? '', ENT_QUOTES, 'UTF-8');
		$date = htmlspecialchars($row['date'] ?? '', ENT_QUOTES, 'UTF-8');

		$pinged = $user !== null && getSingle("select beeper from pings where uid = ? and beeper = ?", 'ii', $user, $uid) !== null;
		$ping = $pinged
			? "<a href=index.php?unping=$uid>Unping</a>"
			: "<a href=index.php?ping=$uid>Ping!</a>";

		print <<<EOF
			<tr>
				<TD class="console">$uid</td>
				<td class="pager has-text-weight-semibold">$post</td>
				<td class="console">$date</td>
				<td class="console">$ping</td>
			</tr>
EOF;
	}
}

function renderBeeps($beeps) {
	print "<div class='table-container'>";
	print "<table border=4 bordercolor='white' class='table is-bordered is-narrow is-fullwidth is-centered calc'>";
	print <<<EOF
	<tr>
	<TH class="console">UserId</TH>
	<th class="console">Beep</th>
	<th class="console">Date</th>
	<th class="console">Pinged Status</th>
	</tr>
EOF;
	renderRows($beeps);
	print "</table>";
	print "</div>";
}

function renderPingedBeeps($beeps) {
	if (!$beeps) {
		print "<p class='has-text-centered has-text-white console'>None. Feel free to Ping any beeps in this Pager!!</p>";
		return;
	}
	renderBeeps($beeps);
}

$user   = getUid();
$notice = '';   // message shown under the form

// Ping / unping a beeper. Only real posters can be pinged, so crawlers
// following ?ping=N links no longer fill the table with junk.
if (!empty($_REQUEST['ping']) && ctype_digit((string) $_REQUEST['ping'])) {
	$ping = (int) $_REQUEST['ping'];
	if (isPoster($ping)) {
		$user = getOrCreateUid();
		prepared("insert ignore into pings(uid, beeper) values (?, ?)", 'ii', $user, $ping);
	}
}
if (!empty($_REQUEST['unping']) && ctype_digit((string) $_REQUEST['unping']) && $user !== null) {
	$unping = (int) $_REQUEST['unping'];
	prepared("delete from pings where uid = ? and beeper = ?", 'ii', $user, $unping);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['beep'])) {
	$beep    = trim((string) $_POST['beep']);
	$token   = (string) ($_POST['h-captcha-response'] ?? '');
	$honey   = (string) ($_POST['website'] ?? '');   // hidden field: humans leave it empty
	$ip      = clientIp();

	if ($honey !== '') {
		logRejection('honeypot', mb_substr($beep, 0, 80));
		// Say nothing: bots get no signal that they were detected.
	} elseif ($beep === '' || mb_strlen($beep) > $beepMaxLength) {
		$notice = "Your beep must be between 1 and $beepMaxLength characters.";
	} elseif ($token === '') {
		logRejection('no-captcha-token', mb_substr($beep, 0, 80));
		$notice = 'Please check the captcha form.';
	} elseif (!verifyCaptcha($token)) {
		$notice = 'Leave you NASTY robot!!!';
	} else {
		$user = getOrCreateUid();
		$last = getSingle("select max(date) from beeps where uid = ?", 'i', $user);
		$dup  = getSingle("select 1 from beeps where uid = ? and post = ? and date > now() - interval 1 day limit 1", 'is', $user, $beep);
		if ($last !== null && time() - strtotime($last) < $beepCooldown) {
			$notice = "Slow down! One beep every $beepCooldown seconds.";
		} elseif ($dup !== null) {
			$notice = 'You already beeped that today.';
		} else {
			prepared("insert into beeps(uid, post, date) values (?, ?, now())", 'is', $user, $beep);
			$safeIp   = htmlspecialchars($ip, ENT_QUOTES, 'UTF-8');
			$safeBeep = htmlspecialchars($beep, ENT_QUOTES, 'UTF-8');
			$notice = "Hey $safeIp! your last beep was: <strong class='pager has-text-white'>$safeBeep</strong>";
		}
	}
}

$safeSiteKey = htmlspecialchars($hcaptchaSiteKey, ENT_QUOTES, 'UTF-8');

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
						<textarea class="textarea is-success is-focus calc pager has-text-weight-semibold" placeholder="Beep anything nostalgic!" name=beep maxlength="$beepMaxLength" required></textarea>
					</div>	
					<div class="nope" aria-hidden="true">
						<label>Leave this field empty <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
					</div>
					<div class="buttons is-right pt-4 pb-5">
						<audio id="play" src="beep-02.wav" type="audio/wav" preload="auto"></audio>
						<audio id="notif" src="GTA3pager.mp3" type="audio/mpeg">
						</audio>
					    <div class="h-captcha" data-sitekey="$safeSiteKey"></div>
						<br>
						<input onclick="playBeep()" class="button is-rounded is-white console" type=submit value="Beep">
				</div>
					
			
					
					</form>	
					<button onclick="playNotif()" class="button is-rounded is-black console ">
				</div>
			</div>
	</body>
</html>

EOF;
if ($notice !== '') {
	print "<p class='has-text-centered console has-text-white pt-3'>$notice</p>";
}

$allBeep = [];
$result = query("select * from beeps order by date desc");
while ($row = mysqli_fetch_assoc($result)) {
	$allBeep[] = $row;
}
renderBeeps($allBeep);
print "<br>";


print "<HR class='has-background-white'>";
print "<br>";

print "<h2 class='title pager has-text-white has-text-centered has-text-weight-bold'>Users whom you pinged!</h2>";
$pingedBeep = [];
if ($user !== null) {
	$pingedBeeps = prepared("select * from beeps where uid in (select beeper from pings where uid = ?) order by date desc", 'i', $user)->get_result();
	while ($row = mysqli_fetch_assoc($pingedBeeps)) {
		$pingedBeep[] = $row;
	}
}
renderPingedBeeps($pingedBeep);

print "<br>";
print "<br>";

print "<center>";
print "<a class='has-text-centered has-text-white console' href='https://orellapager.me/aboutme/about.html'>About My Creator :D</a>";
print "</center>";

print "<br>";
print "<br>";

if ($debugMessages) {
	print "<script>\n";
	foreach ($debugMessages as $m) {
		print 'console.warn(' . json_encode($m, JSON_HEX_TAG | JSON_HEX_AMP) . ");\n";
	}
	print "</script>\n";
}
