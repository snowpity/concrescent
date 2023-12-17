<?php

require_once __DIR__ .'/../../lib/database/database.php';
error_reporting(0);
header('Content-Type: text/plain');

$db = new cm_db();
if (!$db->connection) {
	echo 'NG Could not connect to database through CONcrescent. Check database configuration.';
} else if (!$db->now()) {
	echo 'NG Connection to database through CONcrescent is not working. Check database configuration.';
} else {
	echo 'OK Successfully connected to database through CONcrescent.';
}
