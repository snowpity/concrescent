<?php

require_once 'util.php';
require_once __DIR__ .'/../../config/config.php';
error_reporting(0);

if (
	isset($cm_config['default_admin']['username']) && $cm_config['default_admin']['username'] &&
	isset($cm_config['default_admin']['password']) && $cm_config['default_admin']['password']
) {
    passed('config5', 'Default administrator user has been specified.');
} else {
    failed('config5', 'Default administrator user has not been specified.');
}
