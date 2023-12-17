<?php

require_once __DIR__ .'/../../config/config.php';
error_reporting(0);
header('Content-Type: text/plain');

if (
	isset($cm_config['default_admin']['username']) && $cm_config['default_admin']['username'] &&
	isset($cm_config['default_admin']['password']) && $cm_config['default_admin']['password']
) {
	echo 'OK Default administrator user has been specified.';
} else {
	echo 'NG Default administrator user has not been specified.';
}
