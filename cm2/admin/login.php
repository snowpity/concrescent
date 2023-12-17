<?php

session_name('PHPSESSID_CMADMIN');
session_start();

require_once __DIR__ .'/../lib/database/database.php';
require_once __DIR__ .'/../lib/database/admin.php';
require_once __DIR__ .'/../lib/util/res.php';

$page = $_GET['page'] ?? null;
$attempted = false;

$db = new cm_db();
$adb = new cm_admin_db($db);
if (isset($_POST['username']) && isset($_POST['password'])) {
	if ($adb->log_in($_POST['username'], $_POST['password'])) {
		$adb->log_access();
		if ($page) {
			header('Location: ' . $page);
		} else {
			header('Location: index.php');
		}
		exit(0);
	}
	$attempted = true;
}
$adb->log_out();

echo '<!DOCTYPE HTML>';
echo '<html>';
echo '<head>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
echo '<title>CONcrescent - Log In</title>';
echo '<link rel="shortcut icon" href="' . htmlspecialchars(theme_file_url('favicon.ico', false)) . '">';
echo '<link rel="stylesheet" href="' . htmlspecialchars(resource_file_url('cm.css', false)) . '">';
echo '<link rel="stylesheet" href="' . htmlspecialchars(theme_file_url('theme.css', false)) . '">';
echo '</head>';
echo '<body class="cm-admin">';

echo '<header>';
	echo '<div class="appname">CONcrescent</div>';
echo '</header>';

echo '<article>';
	echo '<form';
	if ($page) {
		echo ' action="login.php?page=' . urlencode($page) . '"';
	} else {
		echo ' action="login.php"';
	}
	echo ' method="post"';
	echo ' class="card cm-login">';
		echo '<div class="card-title">Log In</div>';
		echo '<div class="card-content">';
			if ($attempted) {
				echo '<p class="cm-error-box">Login failed. Please try again.</p>';
			}
			echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-form-table">';
				echo '<tr>';
					echo '<th><label for="username">User Name:</label></th>';
					echo '<td><input type="text" name="username" id="username"></td>';
				echo '</tr>';
				echo '<tr>';
					echo '<th><label for="password">Password:</label></th>';
					echo '<td><input type="password" name="password" id="password"></td>';
				echo '</tr>';
			echo '</table>';
		echo '</div>';
		echo '<div class="card-buttons">';
			echo '<input type="submit" value="Log In">';
		echo '</div>';
	echo '</form>';
echo '</article>';

echo '</body>';
echo '</html>';
