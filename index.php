<?php

$dbhost = 'localhost';
$dbuser = 'root';
$dbpass = '';
$dbname = 'orellapager';

$conn = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

function query($query) {
	global $conn;
	$result = mysqli_query($conn, $query);
	return $result;
}

function getSingle($query) {
	global $conn;
	$result = query($query);
	$row = mysqli_fetch_row($result);
	return $row[0];
}
function getUid(){
		global $conn;
		$ip = mysqli_real_escape_string($conn, $_SERVER['REMOTE_ADDR']);
		$uid = getSingle("select uid from users where ip = '".$ip."'");
		if (!$uid){
				query("insert into users(ip) values ('$ip')");
		}
		$uid = getSingle("select uid from users where ip = '".$ip."'");
		return $uid;
}
function renderBeeps($beeps){
	global $user;
	print "<br>";
	print "<table border=4 bordercolor='white'class='table is-narrow is-bordered is-fullwidth is-centered calc'>";
	print <<<EOF
	<tr >
	<TH class="console" >UserId</TH>
	<th class="console">Beep</th>
	<th class="console">Date</th>
	<th class="console">Pinged Status</th>
	</tr>
EOF;

	 foreach($beeps as $row) {
			$uid = $row['uid'];
			$post = htmlspecialchars($row['post']);
			$date = $row['date'];

			if (!getSingle("select beeper from pings where uid=$user and beeper=$uid"))
					$ping = <<<EOF
						<a href=index.php?ping=$uid>Ping!</a>
EOF;

			else {
					$ping = <<<EOF
						<a href=index.php?unping=$uid>Unping</a>
EOF;
			}

			print <<<EOF
			<tr>
				<TD class="console">$uid</td>
				<td class="pager has-text-weight-semibold">$post</td>
				<td class="console">$date</td>
				<td class="console">$ping</td>
			</tr>
EOF;
	}
	print "</table>";
}
function renderPingedBeeps($beeps){
	global $user;
	print "<table border=4 bordercolor='white' class='table is-bordered is-narrow is-fullwidth is-centered calc'>";

	if (!is_null($beeps)){
	
	print <<<EOF
	<tr>
	<TH class="console">UserId</TH>
	<th class="console">Beep</th>
	<th class="console">Date</th>
	<th class="console">Pinged Status</th>
	</tr>
EOF;
	
}	else{
	print "<p class='has-text-centered has-text-white console'>None. Feel free to Ping any beeps in this Pager!!</p>";

}
	 foreach($beeps as $row) {
			$uid = $row['uid'];
			$post = htmlspecialchars($row['post']);
			$date = $row['date'];

			if (!getSingle("select beeper from pings where uid=$user and beeper=$uid"))
					$ping = <<<EOF
						<a href=index.php?ping=$uid>Ping!</a>
EOF;

			else {
					$ping = <<<EOF
						<a href=index.php?unping=$uid>Unping</a>
EOF;
			}

			print <<<EOF
			<tr>
				<TD class="console">$uid</td>
				<td class="pager has-text-weight-semibold">$post</td>
				<td class="console">$date</td>
				<td class="console">$ping</td>
			</tr>
			
EOF;
	}
	print "</table>";
}
$user = getUid();
if ($_REQUEST['ping']){
	global $conn;
	$ping = mysqli_real_escape_string($conn, $_REQUEST['ping']);
	query("insert ignore into pings(uid, beeper) values ($user, '$ping')");
}
if ($_REQUEST['unping']){
	global $conn;
	$unping = mysqli_real_escape_string($conn, $_REQUEST['unping']);
	query("delete from pings where uid=$user and beeper='$unping'");
}


if($_REQUEST['beep']){
 global $conn;
 $beep = mysqli_real_escape_string($conn, $_REQUEST['beep']);
 $date = Date("Y-m-d H:i:s");
 $ip = mysqli_real_escape_string($conn, $_SERVER['REMOTE_ADDR']);
 query("insert into beeps(uid, post, date) values($user, '$beep', '$date')");
 print "<p class='has-text-centered console has-text-white pt-3'>Hey $ip! your last beep was: <strong class='pager has-text-white'>$beep</strong> <p>";

}


print <<<EOF
<!DOCTYPE html>
<html class="outline">
	
	<head>
		<title>The nostalgic pager board! | OrellaPager</title>
		<meta name="description" content="Ever wondered what a pager looked like, worked,
		 or its popularity in the 80s & 90s? This website brings the nostalgia for 
		 those who had one before. Have fun!">
		<script defer src="https://use.fontawesome.com/releases/v5.3.1/js/all.js"></script>
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
		.narrow tr td{
			padding: 0.40em 0.0em
			background-color: black;
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
	</head>
	<body>
		<h2 class="title pager has-text-centered has-text-white has-text-weight-bold pt-5">Welcome to OrellaPager! </h2>
			<br>	
		<div  >
				<div is-centered>

					<form a?unping=3ction=index.php method=post">
					<div class=" is-expanded">
						<textarea class="textarea is-success is-focus calc pager has-text-weight-semibold" placeholder="Beep anything nostalgic!" name=beep></textarea>
					</div>	
					<div class="buttons is-right pt-4 pb-5">
						<audio id="play" src="http://www.soundjay.com/button/beep-07.wav"></audio>
						<audio id="notif" src="GTA3pager.mp3" type="audio/mpeg">
						</audio>

						<input onclick="playBeep()" class="button is-rounded is-white console" type=submit value="Beep">
				</div>
					
			
					
					</form>	
					<button onclick="playNotif()" class="button is-rounded is-black console ">
				</div>
			</div>
	</body>
</html>

EOF;
#print "<section class='section'>";
$result = query("select * from beeps order by date desc");
$allBeeps = $result;
while($row= mysqli_fetch_assoc($allBeeps)){
		$allBeep[] = $row;
}
renderBeeps($allBeep);
print "<br>";



print "<HR class='has-background-white'>";
print "<br>";

print "<h2 class='title pager has-text-white has-text-centered has-text-weight-bold'>Users whom you pinged!</h2>";
$pingedBeeps = query("select * from beeps where uid in (select beeper from pings where uid=$user) order by date desc");
while($row = mysqli_fetch_assoc($pingedBeeps)){
		$pingedBeep[] = $row;
}
renderPingedBeeps($pingedBeep);

print "<br>";
print "<br>";



