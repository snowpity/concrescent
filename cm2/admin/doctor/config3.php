<?php

require_once 'util.php';
require_once __DIR__ .'/../../../config/concrescent.php';
error_reporting(0);

$fields = array_keys($cm_config['database'] ?? []);
$missingFields = array_diff([
    'host',
    'username',
    'password',
    'database',
], $fields);

if (empty($missingFields)) {
    passed('config3', 'Database configuration has been specified.');
} else {
    failed('config3', 'Database configuration is missing fields : '. implode(', ', $missingFields));
}
