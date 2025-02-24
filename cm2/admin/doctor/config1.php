<?php

include 'util.php';

error_reporting(0);

$success = false;

function print_success() {
	if ($GLOBALS['success']) {
        passed('config1', 'Configuration file was loaded successfully.');
	} else {
        failed('config1', 'Configuration file could not be found or is invalid. Other tests may fail or never finish.');
	}
}

register_shutdown_function(print_success(...));

$success = ((@require_once __DIR__ .'/../../config/config.php') !== false);
