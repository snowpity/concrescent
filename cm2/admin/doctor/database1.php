<?php

require_once __DIR__ .'/../../config/config.php';
error_reporting(0);
header('Content-Type: text/plain');

$connection = new mysqli(
	$cm_config['database']['host'], $cm_config['database']['username'],
	$cm_config['database']['password'], $cm_config['database']['database']
);
if (!$connection) {
	echo 'NG Could not connect to database. Check database configuration.';
	exit(0);
}

$query = $connection->query('SELECT 6*7');
if (!$query) {
	echo 'NG Connection to database is not working. Check database configuration.';
	exit(0);
}

$row = $query->fetch_row();
if (!$row) {
	echo 'NG Connection to database is not working. Check database configuration.';
	exit(0);
}

$answer = $row[0];
if ($answer != 42) {
	echo 'NG Connection to database is not working. Check database configuration.';
	exit(0);
}

$query->close();
echo 'OK Successfully connected to database.';
