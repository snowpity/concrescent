<?php

require_once dirname(__FILE__).'/../../lib/database/database.php';
error_reporting(0);
header('Content-Type: text/plain');

$db = new cm_db();
$charset = $db->connection->character_set_name();
$charsets = $db->characterset();

if (
	$charset == 'utf8'
	&& str_starts_with($charsets['character_set_client'], 'utf8')
	&& str_starts_with($charsets['character_set_connection'], 'utf8')
	&& str_starts_with($charsets['character_set_results'], 'utf8')
) {
	echo 'OK MySQL is using UTF-8.';
} else {
	echo 'NG MySQL is not using UTF-8.';
}