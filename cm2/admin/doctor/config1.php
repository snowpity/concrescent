<?php

error_reporting(0);
header('Content-Type: text/plain');

$success = false;

function print_success() {
	if ($GLOBALS['success']) {
		echo 'OK Configuration file was loaded successfully.';
	} else {
		echo 'NG Configuration file could not be found or is invalid. Other tests may fail or never finish.';
	}
}

register_shutdown_function('print_success');

$success = ((@require_once __DIR__ .'/../../config/config.php') !== false);
