<?php

require_once 'util.php';
error_reporting(0);

if (
    version_compare(PHP_VERSION, '8.1') >= 0
) {
    passed('phpversion', 'PHP version is 8.1 or above.');
} else {
    failed('phpversion', 'PHP version is below 8.1. CONcrescent may not function correctly, and you won\'t get security updates for PHP!');
}
