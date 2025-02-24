<?php

require_once 'util.php';
error_reporting(0);

if (($_SERVER['HTTPS'] ?? false) === 'on') {
    passed('https', 'HTTPS is ON. Connections to CONcrescent are secure.');
} else {
    notice('https', 'HTTPS is OFF. Connections to CONcrescent are NOT secure.');
}
