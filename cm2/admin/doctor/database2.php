<?php

require_once 'util.php';
require_once __DIR__ .'/../../lib/database/database.php';
error_reporting(0);

$db = new cm_db();
if (!$db->connection) {
    failed('database2', 'Could not connect to database through CONcrescent. Check database configuration.');
} else if (!$db->now()) {
    failed('database2', 'Connection to database through CONcrescent is not working. Check database configuration.');
} else {
    passed('database2', 'Successfully connected to database through CONcrescent.');
}
