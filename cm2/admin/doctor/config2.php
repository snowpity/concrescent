<?php

require_once 'util.php';
require_once __DIR__ .'/../../../config/concrescent.php';
error_reporting(0);

$fields = array_keys($cm_config ?? []);
$missingFields = array_diff([
    'database',
    'paypal',
    'slack',
    'event',
    'application_types',
    'review_mode',
    'badge_printing',
    'default_admin',
], $fields);

if (empty($missingFields)) {
    passed('config2', 'All configuration sections are present.');
} else {
    failed('config2', 'Some configuration sections are missing : '. implode(', ', $missingFields));
}
