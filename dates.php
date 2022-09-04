<?php
// Checks if the link has expired
function checkExpire($expire) {
	if($expire != "") {
		if($expire <= date("Y-m-d H:i:s")) {
			return true;
		}
	}
	return false;
}

// Returns formatted difference between specified date and now
function dateDiff($date) {
	$date_now = new DateTime();
	$newdate = new DateTime("$date");
	$diff = $newdate->diff($date_now);
	if($diff->y >= 1) {
		return $diff->format('%y years, %m months');
	} else if($diff->m >= 1) {
		return $diff->format('%m months, %d days');
	} else if($diff->d >= 1) {
		return $diff->format('%d days, %h hours');
	} else if($diff->h >= 1) {
		return $diff->format('%h hours, %i minutes');
	} else if($diff->i >= 1) {
		return $diff->i." minutes";
	} else {
		return "Under a minute";
	}
}
?>