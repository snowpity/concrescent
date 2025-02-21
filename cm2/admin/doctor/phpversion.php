<?php

error_reporting(0);
header('Content-Type: text/plain');

if (version_compare(PHP_VERSION, '8.1') >= 0) {
	echo 'OK PHP version is 8.1 or above.';
} else {
	echo 'NG PHP version is below 8.1. CONcrescent may not function correctly, and you won\'t get security updates for PHP!';
}