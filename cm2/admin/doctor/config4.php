<?php

require_once 'util.php';
require_once __DIR__ .'/../../../config/concrescent.php';
error_reporting(0);

$fields = array_keys($cm_config['paypal'] ?? []);
$missingFields = array_diff([
    'api_url',
    'client_id',
    'secret',
    'currency',
], $fields);

if (empty($missingFields)) {
    passed('config4', 'PayPal configuration has been specified.');
} else {
    failed('config4', 'PayPal configuration has not been specified : '. implode(', ', $missingFields));
}
