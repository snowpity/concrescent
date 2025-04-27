<?php

require_once 'util.php';
require_once __DIR__ .'/../../config/config.php';
error_reporting(0);

try {
	$host = $cm_config['database']['host'];
	$dbname = $cm_config['database']['database'];
	$connection = new PDO(
		"mysql:host=$host;dbname=$dbname",
		$cm_config['database']['username'],
		$cm_config['database']['password']
	);
} catch (PDOException $e) {
	$message = 'Could not connect to database: ' . $e->getMessage();
	if ($e->getCode() === 2002) {
		$message .= '. Check if the service is running.';
	}
	failed('database1', $message);
	die();
}

$query = $connection->query('SELECT 6*7');
if (!$query) {
	failed('database1', 'Connection to database is not working. Check database configuration.');
	die();
}

$row = $query->fetch(PDO::FETCH_NUM);
if (!$row) {
	failed('database1', 'Connection to database is not working. Check database configuration.');
	die();
}

$answer = $row[0];
if ($answer != 42) {
	failed('database1', 'Connection to database is not working. Check database configuration.');
	die();
}

passed('database1', 'Successfully connected to database.');
