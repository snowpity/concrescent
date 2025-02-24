<?php

require_once 'util.php';
require_once __DIR__ .'/../../config/config.php';
error_reporting(0);

try {
	$connection = new mysqli(
		$cm_config['database']['host'],
		$cm_config['database']['username'],
		$cm_config['database']['password'],
		$cm_config['database']['database']
	);
} catch (mysqli_sql_exception $e) {
	$message = "Could not connect to database (code {$e->getCode()}): {$e->getMessage()}";
	if ($e->getCode() === 2002) {
        $message .= '. Check if the service is running.';
	}
	failed('database1', $message);
    die();
}

if (!$connection) {
	failed('database1', 'Could not connect to database. Check database configuration.');
    die();
}

$query = $connection->query('SELECT 6*7');
if (!$query) {
	failed('database1', 'Connection to database is not working. Check database configuration.');
    die();
}

$row = $query->fetch_row();
if (!$row) {
	failed('database1', 'Connection to database is not working. Check database configuration.');
    die();
}

$answer = $row[0];
if ($answer != 42) {
	failed('database1', 'Connection to database is not working. Check database configuration.');
    die();
}

$query->close();
passed('database1', 'Successfully connected to database.');
