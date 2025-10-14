<?php

use App\Config\ConfigurationMapper;
use App\Lib\Database\cm_db;

require_once 'util.php';
require_once __DIR__ .'/../../../config/concrescent.php';
error_reporting(0);

$config = new ConfigurationMapper()->mapToConfiguration($cm_config);
$db = new cm_db($config->database);
$charsets = $db->characterset();

// The cm_db constructor sets the connection's encoding to utf8mb4,
// and that's what all of these should be for full Unicode® support.
if ($charsets['character_set_client'    ] === 'utf8mb4'
 && $charsets['character_set_connection'] === 'utf8mb4'
 && $charsets['character_set_database'  ] === 'utf8mb4'
 && $charsets['character_set_results'   ] === 'utf8mb4'
) {
    passed('database4', 'MySQL is using UTF-8.');
} else {
    failed('database4', "MySQL is not using UTF-8. Charsets: client = $charsets[character_set_client], connection = $charsets[character_set_connection], database = $charsets[character_set_database], results = $charsets[character_set_results].");
}
