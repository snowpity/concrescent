<?php

error_reporting(0);
header('Content-Type: text/plain');

$success = false;

function print_success() {
	if ($GLOBALS['success']) {
		echo 'OK Successfully connected to PayPal and received token.';
	} else {
		echo 'NG Could not connect to PayPal or could not receive token. Check PayPal configuration and make sure OpenSSL is up to date.';
	}
}

register_shutdown_function(print_success(...));

@require_once __DIR__ .'/../../lib/util/paypal.php';
$paypal = @new cm_paypal();
$token = @$paypal->get_token()['access_token'];
$success = !!$token;
