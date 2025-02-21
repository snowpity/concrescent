<?php

require_once __DIR__ .'/../../config/config.php';
error_reporting(0);
header('Content-Type: text/plain');

try {
	$connection = new mysqli(
		$cm_config['database']['host'],
		$cm_config['database']['username'],
		$cm_config['database']['password'],
		$cm_config['database']['database']
	);
} catch (mysqli_sql_exception $e) {
	echo "NG Could not connect to database (code {$e->getCode()}): {$e->getMessage()}";
	if ($e->getCode() === 2002) {
		die('. Check if the service is running.');
	}
	die();
}

if (!$connection) {
	die('NG Could not connect to database. Check database configuration.');
}

$query = $connection->query('SELECT 6*7');
if (!$query) {
	die('NG Connection to database is not working. Check database configuration.');
}

$row = $query->fetch_row();
if (!$row) {
	die('NG Connection to database is not working. Check database configuration.');
}

$answer = $row[0];
if ($answer != 42) {
	die('NG Connection to database is not working. Check database configuration.');
}

$query->close();
echo 'OK Successfully connected to database.';
