<?php

require_once 'util.php';
error_reporting(0);

if (
    version_compare(PHP_VERSION, '8.4') >= 0
) {
    passed('phpversion', 'PHP version is 8.4 or above.');
} else {
    failed('phpversion', 'PHP version is below 8.4. CONcrescent will not function properly.');
}
