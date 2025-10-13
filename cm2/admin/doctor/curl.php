<?php

require_once 'util.php';
error_reporting(0);

if (function_exists('curl_init')) {
    passed('curl', 'The cURL extension is installed and working.');
} else {
    failed('curl', 'The cURL extension is not installed or is not working. Please reinstall the cURL extension.');
}
