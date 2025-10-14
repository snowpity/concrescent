<?php

use App\Config\ConfigurationMapper;
use App\Lib\Database\cm_db;

require_once 'util.php';
require_once __DIR__ .'/../../../config/concrescent.php';
error_reporting(0);

date_default_timezone_set($cm_config['timezone']);

$config = new ConfigurationMapper()->mapToConfiguration($cm_config);
$db = new cm_db($config->database);
$dbtime = $db->now();
$phptime = date('Y-m-d H:i:s O');
$dbTs = strtotime($dbtime);
$phpTs = strtotime($phptime);

$diff = abs($dbTs - $phpTs);
if ($diff > 600) {
	failed('database3', 'MySQL time and PHP time differ by '.$diff.' seconds which more than the limit (600 seconds). Check time zone settings, make sure time zone data is present in MySQL, and run /admin/timecheck.php to verify.');
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
