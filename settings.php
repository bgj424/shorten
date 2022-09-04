<?php
session_start();

// Skip true
if($_GET['skip'] == true && !isset($_COOKIE['Skip_warning_page'])) {
	setcookie("Skip_warning_page", true);
	if(isset($_SERVER[HTTP_REFERER])) {
		header("Location: $_SERVER[HTTP_REFERER]");
	}

// Skip false
} else if($_GET['skip'] == false && isset($_COOKIE['Skip_warning_page'])) {
	setcookie("Skip_warning_page", "", time()-10, "/"); // unset
	if(isset($_SERVER[HTTP_REFERER])) {
		header("Location: $_SERVER[HTTP_REFERER]");
	}
} else header("Location: /")
?>