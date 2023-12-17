<?php

error_reporting(0);
header('Content-Type: text/plain');

$success = false;

function print_success() {
	if ($GLOBALS['success']) {
		echo 'OK The cURL extension is installed and working.';
	} else {
		echo 'NG The cURL extension is not installed or is not working. Please reinstall the cURL extension.';
	}
}

register_shutdown_function(print_success(...));

$curl = @curl_init('https://www.paypal.com');
if (!$curl) exit(0);

@curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'HEAD');
@curl_setopt($curl, CURLOPT_HEADER, true);
@curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
$result = @curl_exec($curl);
if (!$result) exit(0);

@curl_close($curl);
$success = (substr($result, 0, 5) == 'HTTP/');
