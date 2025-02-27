<?php

require_once 'util.php';
require_once __DIR__ .'/../../lib/database/database.php';
error_reporting(0);

$db = new cm_db();
$dbtime = $db->now();
$phptime = date('Y-m-d H:i:s');

$diff = abs(strtotime($dbtime) - strtotime($phptime));
if ($diff > 600) {
	failed('database3', 'MySQL time and PHP time differ by over 10 minutes. Check time zone settings, make sure time zone data is present in MySQL, and run /admin/timecheck.php to verify.');
    die();
}

$message = 'MySQL time and PHP time are synchronized';
if ($diff === 0) {
	$message .= ' exactly :D';
} else if ($diff === 1) {
	$message .= '. Discrepancy: 1 second.';
} else {
	$message .= ". Discrepancy: $diff seconds.";
}
passed('database3', $message);
