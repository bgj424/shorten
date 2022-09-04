<?php session_start();
if (!isset($_SESSION['access'])) {
    echo "Maintenance";
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>     
<link rel="icon" href="cut.ico" type="image/x-icon" />
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css" integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/" crossorigin="anonymous">
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

<title>URL Shortener</title>
<style>
.form-control, button {
	border-radius:0!important;
}

#info {
	display: none;
}

body {
	background-color: #191919;
}

p, h1, h2, h3, h4, h5, h6, label {
	color: white;
}

a {
	color:#6086c4!important;
}

html {
  position: relative;
  min-height: 100%;
}

body {
	margin-bottom: 100px;
}

.footer {
	position: absolute;
	bottom: 0;
	width: 100%;
	height: 100px;
}

.mt {
	margin-top: 30vh;
}

.maindiv {
	background-color: #232323;
	padding: 50px;
}

#link-error, #link-success {
	display: none;
}

.alert {
	border-radius: 0px!important;
}

.copy {
	border: none;
	background-color: transparent;
	cursor: pointer;
}

.copy:focus {
	outline:0;
}

.copy:hover {
	filter: opacity(50%);
}
</style>
</head>
<body>
<div class="mt container w-50 align-middle">
	<div class="maindiv">
		<div class="alert alert-danger text-center" role="alert" id="link-error">
		Error
		<span class="alert-link"> 
			<?php if(isset($_SESSION['lastLinkError'])) echo "(".$_SESSION['lastLinkError'].")"; ?>
		</span>
		<button type="button" class="close" aria-label="Close" title="Dismiss" onclick="hideAlert();">
			<span aria-hidden="true">&times;</span>
		</button>
		</div>
		<div class="alert alert-success text-center" role="alert" id="link-success">
			<?php if(isset($_SESSION['lastCreatedLink'])) { ?>
			Shortened link:<br>
			<a class="alert-link" id="linktocopy" href="/<?php if(isset($_SESSION['lastCreatedLink'])) echo $_SESSION['lastCreatedLink']; ?>">shorten/<?php echo $_SESSION['lastCreatedLink'];?></a><?php } ?><button type="button" class="copy" title="Click to copy" onclick="copyString('linktocopy');">
			<i class="far fa-copy"></i>
			</button>
		</div>
		<div class="alert alert-success" role="alert" id="link-key" hidden>
			<?php if(isset($_SESSION['linkKey'])) { ?>
			Save the following key to view the statistics for your link:<br>
			<a class="alert-link" id="keytocopy">
			shorten/<?php echo $_SESSION['lastCreatedLink']; ?></a>
			<?php } ?>
			<button type="button" id="copy" title="Click to copy" onclick="copyString('keytocopy');">
			<i class="far fa-copy"></i>
			</button>
		</div>
		<form id="new_link" action="shorten.php" method="post" required>
			<label><h2>Shorten</h2></label>
			<div class="form-row mb-2">
				<div class="col-md-10">
					<input id="link" class="form-control" placeholder="http://..." type="text" name="link" maxlength="255" autocomplete="off">
				</div>
				<div class="col-md-2">
					<input class="btn-secondary form-control" id="submit" type="submit" value="Shorten" title="Please enter a URL" onclick="return validate();" disabled>
				</div>
			</div>
			<div class="form-row" id="extra">
				<div class="col-md-5">
					<label for="customurl">Custom URL <span class="text-muted">(Optional)</span></label>
					<input id="customurl" class="form-control" placeholder="Custom URL" type="text" name="customurl" minlength="4" maxlength="25" autocomplete="off">
				</div>
				<div class="col-md-4">
					<label for="linkname">Link name <span class="text-muted">(Optional)</span></label>
					<input id="linkname" class="form-control" placeholder="Name your link" type="text" name="name" maxlength="25" autocomplete="off">
				</div>
				<div class="col-md-3">
					<label for="linkexpire">Expires in <span class="text-muted">(Optional)</span></label>
					<select id="linkexpire" class="form-control" name="expire" form="new_link">		
						<option value="null">Never</option>
						<option value="10">10 minutes</option>
						<option value="30">30 minutes</option>
						<option value="1h">1 hour</option>
						<option value="1d">1 day</option>
						<option value="1w">1 week</option>
						<option value="1m">1 month</option>
						<option value="1y">1 year</option>
					</select>
				</div>
			</div>
		</form>
	</div>
  </div>
</div>
</div>
</body>

<footer class="footer">
	<div class="footerdiv container">
		<div class="row">
			<div class="col-md-3">
				<p>Links created<b><br>
				<?php
					require_once('dates.php');
					$total = file("../txtdb/var.txt");
					echo $total[0];
				?>
				</b></p>
			</div>
			<div class="col-md-3">
				<p>Last one created<b><br>
				<?php echo dateDiff($total[1])." ago"; ?>
				</b></p>
			</div>
			<div class="col-md-3 text-right">
				<a href="./statistics.php">Statistics</a>
			</div>
			<div class="col-md-3 text-right">
				<a href="./preferences.php">Preferences</a>
			</div>
		</div>
	</div>
</footer>

<script>
function showExtra() {
	var extra = document.getElementById("extra");
	if (extra.style.display == "" || extra.style.display == "none") {
		extra.style.display = "inline-flex";
		document.getElementById("extra-toggle").style.display = "none";
	} else {
		extra.style.display = "none";
		document.getElementById("extra-toggle").innerHTML = "Settings";
	}
}

function showAlert() {
	if("<?php if(isset($_SESSION['lastLinkError'])) echo $_SESSION['lastLinkError']; ?>" != "") {
		document.getElementById("link-error").style.display = "block";
	}
	
	if("<?php if(isset($_SESSION['lastCreatedLink'])) echo $_SESSION['lastCreatedLink']; ?>" != "") {
		document.getElementById("link-success").style.display = "block";
	}		
}

function hideAlert() {
	document.getElementsByClassName("alert")[0].style.display = "none";

	var xmlhttp = new XMLHttpRequest();
	xmlhttp.open("GET", "/misc/unsetalert.php", true);
	xmlhttp.send();
}

function copyString(id) {
	range = document.createRange();
    range.selectNode(document.getElementById(id));
    window.getSelection().addRange(range);
    document.execCommand("copy");
}

function validate() {
	if(document.getElementById("submit").classList.contains("btn-danger")) {
		return false;
	} else if(document.getElementById("submit").classList.contains("btn-secondary")) {
		return false;
	}
	return true;
}

function isURL(string) {
	var expression = /^(.+)\.(.+)$/;
	var regex = new RegExp(expression);

	if(string.match(regex)) {
		return true;
	} else {
		return false;
	}
}

// Change the button color on input
function updateButton() {
	var input = document.forms['new_link'].link.value;
	var button = document.getElementById("submit");
	if(input == "") {
		button.className = "btn-secondary form-control";
		button.disabled = true;
		button.title = "Please enter a URL";
	} else if(isURL(input) === true) {
		button.className = "btn-success form-control";
		button.disabled = false;
		button.title = "";
	} else {
		button.className = "btn-danger form-control";
		button.disabled = true;
		button.title = "Invalid URL";
	}
}

// Listens for user inputs
document.getElementById("link").addEventListener('input', updateButton);

window.onload = (function(){
	updateButton();
	showAlert();
});

/* Animation section */
var headings = ["Shorten", "Customize"];
var examples = ["https://www.youtube.com/watch?v=a_funny_video", "https://www.reddit.com/r/some_subreddit/comments/some_post", "https://www.twitch.tv/my_favorite_channel", "wowthatsaprettylongdomainyouhavethere.somerandomtld"];	
var currentString = 0;
function fadeText(id, strings, fadeTime, fadeDelay) {
	document.getElementById(id).innerHTML = strings[currentString];
	$("#" + id).fadeTo(fadeTime, 1);
	$("#" + id).delay(fadeDelay).fadeTo(fadeTime, 0);
	if(currentString == (strings.length - 1)) {
		currentString = 0;
	} else {
		currentString++;
	}
}

/*
function fadePlaceholder(id, strings, fadeTime, fadeDelay) {
	document.getElementsById('link')[0].placeholder = strings[currentString];
	$("#link").fadeTo(1500, 1);
	$("#link").delay(2500).fadeTo(1500, 0);
	if(currentString == (strings.length - 1)) {
		currentString = 0;
	} else {
		currentString++;
	}
}
*/
/*
$(document).ready(function(){
	fadeText("heading", headings, 2000, 3000);
	window.setInterval(function(){
		fadeText("heading", headings, 2000, 3000);
	}, 7500);
	
	window.setInterval(function(){
		fadePlaceholder();
	}, 8000);
});
*/

function showInfo() {
	document.getElementById("info").style.display = "block";
}

function hideInfo() {
	document.getElementById("info").style.display = "none";
}
</script>

</body>
</html>