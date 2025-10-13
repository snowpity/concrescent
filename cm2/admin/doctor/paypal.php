<?php

use App\Lib\Util\cm_paypal;

require_once 'util.php';
require_once __DIR__ .'/../../../config/concrescent.php';
error_reporting(0);

$success = false;

function print_success() {
	if ($GLOBALS['success']) {
        passed('paypal', 'Successfully connected to PayPal and received token.');
	} else {
        failed('paypal', 'Could not connect to PayPal or could not receive token. Check PayPal configuration and make sure OpenSSL is up to date.');
    }
}

register_shutdown_function(print_success(...));

$paypal = @new cm_paypal();
$token = @$paypal->get_token()['access_token'];
$success = !!$token;
