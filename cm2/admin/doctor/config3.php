<?php

require_once 'util.php';
require_once __DIR__ .'/../../config/config.php';
error_reporting(0);

if (
	isset($cm_config['database']['host']) && $cm_config['database']['host'] &&
	isset($cm_config['database']['username']) && $cm_config['database']['username'] &&
	isset($cm_config['database']['password']) && $cm_config['database']['password'] &&
	isset($cm_config['database']['database']) && $cm_config['database']['database']
) {
    passed('config3', 'Database configuration has been specified.');
} else {
    failed('config3', 'Database configuration has not been specified.');
}
