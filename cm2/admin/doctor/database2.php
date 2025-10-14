<?php

require_once 'util.php';
require_once __DIR__ .'/../../../config/concrescent.php';

use App\Config\ConfigurationMapper;
use App\Lib\Database\cm_db;

error_reporting(0);

$config = new ConfigurationMapper()->mapToConfiguration($cm_config);
$db = new cm_db($config->database);
if (!$db->connection) {
    failed('database2', 'Could not connect to database through CONcrescent. Check database configuration.');
} else if (!$db->now()) {
    failed('database2', 'Connection to database through CONcrescent is not working. Check database configuration.');
} else {
    passed('database2', 'Successfully connected to database through CONcrescent.');
}
