<?php
require_once('db.php');
require_once('dates.php');
$conn = db_connect();

$id = $_GET['l'];

// Check the validity of the link and get the data
$sql = $conn->query("SELECT * FROM link WHERE id = '$id'");
if(mysqli_num_rows($sql) > 0) {
	$value = mysqli_fetch_object($sql);
	$expire = $value->expire;
	$url = $value->target;
	$name = $value->name;
	$date = $value->date;
	$creator = $value->creator;
	if(checkExpire($expire) == true) errorpage();
} else {
	errorpage();
}

function errorpage() {
	header('HTTP/1.0 404 Not Found');
	http_response_code(404);
	include('404.html');
	die();
}

session_start();

if(isset($_COOKIE['Skip_warning_page']) && $_COOKIE['Skip_warning_page'] == true) {
	header("Location: $url");
} else {
	// If the username has been defined as part of ip address (unregistered user)
	if(substr_count($creator, ".") == 2) {
		$creator = "Guest";
	}	
	
	// Get and update link statistics
	$sql = $conn->query("SELECT times_used FROM link_statistics WHERE id = '$id'");
	if(mysqli_num_rows($sql) > 0) {
		$value = mysqli_fetch_object($sql);
		$times_used = $value->times_used;
	} else {
		$times_used = 1;
	}

	// Add 1 to link use count if not already added
	if(isset($_SESSION['linkUsed'][$id]) == false) {
		$conn->query("UPDATE link_statistics SET times_used = times_used + 1 WHERE id = '$id'");
		$_SESSION['linkUsed'][$id] = true;
	} else {
		$conn->query("INSERT INTO link_statistics (id, times_used, times_cancelled) VALUES ('$id', 1, 0)");
		$_SESSION['linkUsed'][$id] = true;
	}

	if(!isset($_GET['cancel'])) {
		header("refresh:5; url=$url");
	}
}
?>

<!DOCTYPE html>
<html>
<head>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">
<link rel="stylesheet" href="./style_link.css">
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
<script>
// Updates the countdown
function checkParams() {
	if(window.location.href.indexOf("/cancel") == -1) {
		window.setInterval(function(){
			countdown();
		}, 1000);
	}
}

window.onload = (function(){
	checkParams();
});

var redirecting = false;
$(window).bind('beforeunload', function(){ // When redirect is performed
    redirecting  = true;
});

var current = 4;
var currentwidth = 0;
var progressbarColor = ["#cc0000", "#990099", "#0066ff", "#009999", "#00cc66"];
function countdown() {
	var progressbar = document.getElementsByClassName("progress-bar")[0];
	currentwidth = currentwidth + 20;
	progressbar.style.width = currentwidth + '%';
	progressbar.style["background-color"]  = progressbarColor[current];

	if(current == 0) {
		document.getElementById("countdown-text").innerHTML = "Redirecting...";
	} else if(current < 0) {
		if(redirecting == false) {
			document.getElementById("countdown-text").innerHTML = "Cannot redirect: invalid URL";
			progressbar.style["background-color"] = "#c8ce71";
		} else overtime++;
	} else {
		document.getElementById("countdown").innerHTML = current;
	}
	current--;
}
</script>

</head>
<body>
<div class="container" id="link">
  <div class="col-10 col-centered height">
    <h2 style="text-decoration:underline;"><?php echo $name .isset($_SESSION['skip']); ?></h2><br>
    <?php
      if(!isset($_GET['cancel'])) {
	      echo '<h2 style="color:gray" id="countdown-text">
		  Please wait <span style="color:gray" id="countdown">5</span> seconds. You will be redirected to:</h2>';
	  } else if($_GET['cancel'] == true) {
	      echo '<h2 style="color:red" id="countdown-text">Cancelled.</h2>';
	  }
    ?>
    <b><a id="url" href="<?php echo $url; ?>"><?php echo $url; ?></a></b>
	<br>
	<!-- <a id="preview" href="/preview.php?l=<?php echo $url; ?>">Preview page >></a> -->
	<br><br>
	<div class="progress">
      <div class="progress-bar progress-bar-secondary" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:0%">
      </div>
    </div>
    <br>
    <div class="row">
      <div class="col-3">
	  </div>
      <div class="col-3 text-left">
	    <?php if(isset($_GET['cancel']) === false): ?>
		  <a class="function-url" href="<?php echo $id; ?>/cancel">Cancel redirect</a>
		<?php else: ?>
		  <a class="function-url" href="../<?php echo $id; ?>">Uncancel redirect</a>
		<?php endif; ?>
      </div>
	  <div class="col-3 text-right">
        <a class="function-url" href="../settings.php?skip=true" title="Disables this page from showing again and redirects immediately">Don't show this page</a>
      </div>
	  <div class="col-3">
	  </div>
    </div>
  </div>
</div>
<div class="container" id="info" style="width:20%;">
  <ul>
    <div class="col-12" style="margin-top:15px;">
	  <li>Created <b><?php echo dateDiff($date); ?> ago</b> by <b><?php echo $creator; ?></b></li>
	  <?php if($expire != "") echo "<li>Expires in <b>". dateDiff($expire) ."</b></li>"; ?>
	  <li>Times used <b><?php echo $times_used; ?></b></li>
	</div>
  </ul>
</div>
</body>
</html>