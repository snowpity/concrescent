<?php

require_once 'util.php';
require_once __DIR__ .'/../../config/config.php';
error_reporting(0);

if (
	isset($cm_config['database'])
	&& isset($cm_config['paypal'])
	&& isset($cm_config['slack'])
	&& isset($cm_config['event'])
	&& isset($cm_config['application_types'])
	&& isset($cm_config['review_mode'])
	&& isset($cm_config['badge_printing'])
	&& isset($cm_config['default_admin'])
	&& isset($cm_config['theme'])
) {
    passed('config2', 'All configuration sections are present.');
} else {
    failed('config2', 'Some configuration sections are missing.');
}
