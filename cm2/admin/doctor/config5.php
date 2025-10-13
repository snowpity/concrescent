<?php

require_once 'util.php';
require_once __DIR__ .'/../../../config/concrescent.php';
error_reporting(0);


$fields = array_keys($cm_config['default_admin'] ?? []);
$missingFields = array_diff([
    'username',
    'password',
], $fields);

if (empty($missingFields)) {
    passed('config5', 'Default administrator user has been specified.');
} else {
    failed('config5', 'Default administrator user config is missing fields : '. implode(', ', $missingFields));
}
