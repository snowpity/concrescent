<?php

require_once __DIR__ .'/../../config/config.php';
error_reporting(0);
header('Content-Type: text/plain');

if (
	isset($cm_config['paypal']['api_url']) && $cm_config['paypal']['api_url'] &&
	isset($cm_config['paypal']['client_id']) && $cm_config['paypal']['client_id'] &&
	isset($cm_config['paypal']['secret']) && $cm_config['paypal']['secret'] &&
	isset($cm_config['paypal']['currency']) && $cm_config['paypal']['currency']
) {
	echo 'OK PayPal configuration has been specified.';
} else {
	echo 'NG PayPal configuration has not been specified.';
}
