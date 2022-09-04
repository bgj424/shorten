<?php
session_start();
require_once('db.php');
$conn = db_connect();
$link = $_POST['link'];

/* Request validation */
function validity($url) {

	// Antispam measures
	$recentUrls = file("../txtdb/recent.txt");
	$recentUrls = unserialize($recentUrls[0]);
	$found = false;
	$date_now = new DateTime();
	foreach($recentUrls as $index => $value) {
		if(strpos($value['user'], getUserIP()) !== false) {
			$newdate = new DateTime($recentUrls[$index]['time']);
			$diff = $newdate->diff($date_now);
			if($diff->i >= 15) { // Time
				unset($recentUrls[$index]['user']);
				unset($recentUrls[$index]['value']);
				unset($recentUrls[$index]['time']);
			} else if($recentUrls[$index]['value'] < 10) { // How many links to trigger the antispam
				$recentUrls[$index]['value']++;
				file_put_contents("../txtdb/recent.txt", serialize($recentUrls));
				$found = true;
			} else {
				$_SESSION['lastLinkError'] = "Please wait a moment before creating a new link again";
				return false;
			}
		}
	}
	if($found === false) {
		$index = count($recentUrls);
		$recentUrls[$index]['user'] = getUserIP();
		$recentUrls[$index]['value'] = 1;
		$recentUrls[$index]['time'] = date_create()->format('Y-m-d H:i:s');
		file_put_contents("../txtdb/recent.txt", serialize($recentUrls));		
	}
	
	$url = trim($url);
	
	if(strpos($url, "://") === false) {
		$url = "http://".$url;
		global $link;
		$link = "http://".$link;
	}
	
	// Check for blacklisted urls (other url shorteners etc)
	$blacklist = array("bit.ly", "ad.fly", "tinyurl.com", "goo.gl", "shorten");
	$blacklistcount = count($blacklist);
	for($i = 0; $i < $blacklistcount; $i++) {
		if(strpos($url, $blacklist[$i]) !== false) { // Found blacklisted url
			$_SESSION['lastLinkError'] = "Blacklisted domain";
			return false;
		}
	}
	
	// Other validation
	if(filter_var($url, FILTER_VALIDATE_URL) === false) { // Check if the link is a URL
		$_SESSION['lastLinkError'] = "Invalid URL";
		return false;
	/*
	} else if(!@fopen($url,"r")) { // Checking if the link actually directs to anything
		$_SESSION['lastLinkError'] = "Invalid URL";
		return false; 
	*/
	} else if($_POST['customurl'] != "") { // Custom url specified
		global $conn;
		$sql = $conn->query("SELECT id FROM link WHERE id = '$_POST[customurl]'");
		if(mysqli_num_rows($sql) > 0) { // If a custom url is already being used
			$_SESSION['lastLinkError'] = "That URL already exists";
			return false;
		} else {
			global $id;
			$id = $_POST['customurl'];
			return true;
		}
	} else return true;
}

// Defining if we need to create a new link or use a pre-existing one
function alreadyExists($url) {
	// Always create new one if settings have been defined by user
	if($_POST['name'] == "" && $_POST['customurl'] == "" && $_POST['expire'] == "null") {
		global $conn;
		$sql = $conn->query("SELECT id FROM link WHERE target = '$url'");
		if(mysqli_num_rows($sql) > 0) {
			$value = mysqli_fetch_object($sql);
			global $id;
			$id = $value->id;
			return true; // exists
		} else {
			return false;
		}
	} return false;
}

function getUserIP() {
	$ip = getenv('HTTP_CLIENT_IP')?:
	getenv('HTTP_X_FORWARDED_FOR')?:
	getenv('HTTP_X_FORWARDED')?:
	getenv('HTTP_FORWARDED_FOR')?:
	getenv('HTTP_FORWARDED')?:
	getenv('REMOTE_ADDR');
	if(isset($ip)) {
		/* Trim the last 1/4 from the ip as
		it isnt stored in its full length */
		$trimmed = implode(".", array_slice(explode(".", $ip), 0, 3));
		return $trimmed;
	} else {
		return "NULL";
	}
}

function getLinkName() {
	if(isset($_POST['name']) && $_POST['name'] != "") {
		return $_POST['name'];
	} else {
		return "A shortened link";
	}
}

function expireToDateTime($inputstring) {
	if($inputstring == "null") $expire = NULL;
	else if($inputstring == "10") $expire = date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i:s")." +10 minutes"));
	else if($inputstring == "30") $expire = date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i:s")." +30 minutes"));
	else if($inputstring == "1h") $expire = date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i:s")." +1 hour"));
	else if($inputstring == "1d") $expire = date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i:s")." +1 day"));
	else if($inputstring == "1w") $expire = date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i:s")." +1 week"));
	else if($inputstring == "1m") $expire = date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i:s")." +1 month"));
	else if($inputstring == "1y") $expire = date("Y-m-d H:i:s",strtotime(date("Y-m-d H:i:s")." +1 year"));
	else $expire = NULL;
	return $expire;
}

function randomURL() {	
	$idchars = array("a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", 
	"q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9");
	$idchars_count = count($idchars);
	$unique = false;
	while($unique == false) {
		$idstring = "";
		$idchar = array();
		for($i = 0; $i < 3; $i++) {
			$idchar[$i] = rand(0, ($idchars_count - 1));
			$idchar[$i] = $idchars[$idchar[$i]];
			$idstring = $idstring.$idchar[$i];
		}
		global $conn;
		$sql = $conn->query("SELECT id FROM link WHERE id = '$idstring'");
		if(mysqli_num_rows($sql) < 1) {
			$unique = true;
			return $idstring;
		}
	}
}

function dbInsert($id) {
	global $conn;
	global $link;
	$insert = "INSERT INTO link (id, date, expire, target, creator, name) VALUES (?, ?, ?, ?, ?, ?)";
	$stmt = mysqli_prepare($conn, $insert);
	mysqli_stmt_bind_param($stmt, "ssssss", $id, $date, $expire, $link, $creator, $name);

	if(isset($_POST['expire'])) {
		$expire = expireToDateTime($_POST['expire']);
	} else $expire = NULL;
	$date = date_create()->format('Y-m-d H:i:s');
	$creator = "user";
	$name = getLinkName();

	mysqli_stmt_execute($stmt);

	// Update the text variable storage (last link date + links total)
	$total = file("../txtdb/var.txt");
	$updatedFile = ($total[0] + 1)."\n".$date;
	file_put_contents("../txtdb/var.txt", ($updatedFile));
	return true;
}

if(validity($link) === true) {
	if(isset($id) === false) {
		$id = randomURL();
	}
	
	if(alreadyExists($link) == false) {
		dbInsert($id);
		$_SESSION['newLink'] = true;
	} else {
		$_SESSION['newLink'] = false;	
	}

	$_SESSION['lastCreatedLink'] = $id;
	if(isset($_SESSION['lastLinkError']) === true) {
		unset($_SESSION['lastLinkError']);
	}
} else {
	unset($_SESSION['lastCreatedLink']);
}
header("Location: /");
?>