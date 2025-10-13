<?php

use App\Lib\Database\cm_admin_db;
use App\Lib\Database\cm_db;

require_once 'util.php';
require_once __DIR__ .'/../../../config/concrescent.php';
error_reporting(0);

$db = new cm_db();
$adb = new cm_admin_db($db);
$users = $adb->list_users();

if ($users && count($users)) {
    passed('database5', 'At least one user account exists.');
} else {
    failed('database5', 'No user accounts exist. Check database configuration, default administrator user configuration, and database privileges.');
}
