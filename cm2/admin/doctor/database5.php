<?php

require_once __DIR__ .'/../../lib/database/database.php';
require_once __DIR__ .'/../../lib/database/admin.php';
error_reporting(0);
header('Content-Type: text/plain');

$db = new cm_db();
$adb = new cm_admin_db($db);
$users = $adb->list_users();

if ($users && count($users)) {
	echo 'OK At least one user account exists.';
} else {
	echo 'NG No user accounts exist. Check database configuration, default administrator user configuration, and MySQL privileges.';
}
