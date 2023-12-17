<?php

session_name('PHPSESSID_CMADMIN');
session_start();

require_once __DIR__ .'/../lib/database/database.php';
require_once __DIR__ .'/../lib/database/admin.php';
require_once __DIR__ .'/../lib/util/util.php';
require_once __DIR__ .'/../lib/util/res.php';
require_once __DIR__ .'/admin-nav.php';

$db = new cm_db();
$adb = new cm_admin_db($db);
$admin_user = $adb->logged_in_user();
if (!$admin_user) {
	$url = get_site_url(false) . '/admin/login.php?page=';
	$url .= urlencode($_SERVER['REQUEST_URI']);
	header('Location: ' . $url);
	exit(0);
}

if (
	(!isset($_SERVER['HTTP_X_REQUESTED_WITH'])) ||
	(strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest')
) {
	$adb->log_access();
}

function cm_admin_head($title) {
	echo '<!DOCTYPE HTML>';
	echo '<html>';
	echo '<head>';
	echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
	echo '<title>CONcrescent - ' . htmlspecialchars($title) . '</title>';
	echo '<link rel="shortcut icon" href="' . htmlspecialchars(theme_file_url('favicon.ico', false)) . '">';
	echo '<link rel="stylesheet" href="' . htmlspecialchars(resource_file_url('cm.css', false)) . '">';
	echo '<link rel="stylesheet" href="' . htmlspecialchars(theme_file_url('theme.css', false)) . '">';
	echo '<script type="text/javascript" src="' . htmlspecialchars(resource_file_url('jquery.js', false)) . '"></script>';
	echo '<script type="text/javascript" src="' . htmlspecialchars(resource_file_url('cmui.js', false)) . '"></script>';
}

function cm_admin_body($title) {
	echo '</head>';
	echo '<body class="cm-admin">';
	echo '<header>';
		echo '<div class="appname">CONcrescent</div>';
		echo '<div class="pagename">' . htmlspecialchars($title) . '</div>';
		echo '<div class="header-items">';
			echo '<div class="header-item">';
				echo htmlspecialchars($GLOBALS['admin_user']['name']);
			echo '</div>';
		echo '</div>';
	echo '</header>';
}

function cm_admin_nav($page_id) {
	global $cm_admin_nav, $adb, $admin_user;
	echo '<nav>';
		foreach ($cm_admin_nav as $group) {
			$first_link = true;
			foreach ($group as $link) {
				if (
					!isset($link['permission']) || !$link['permission'] ||
					$adb->user_has_permission($admin_user, $link['permission'])
				) {
					if ($first_link) echo '<ul>';
					if ($link['id'] == $page_id) {
						echo '<li class="current">';
					} else {
						echo '<li>';
					}
					$url = get_site_url(false) . $link['href'];
					echo '<a href="' . htmlspecialchars($url) . '"';
					if (isset($link['description']) && $link['description']) {
						echo ' title="' . htmlspecialchars($link['description']) . '"';
					}
					echo '>';
					echo htmlspecialchars($link['name']);
					echo '</a>';
					echo '</li>';
					$first_link = false;
				}
			}
			if (!$first_link) echo '</ul><hr>';
		}
	echo '</nav>';
}

function cm_admin_dialogs() {
	echo '<div class="dialog-cover hidden"></div>';
}

function cm_admin_tail() {
	echo '<div class="butterbar hidden"></div>';
	echo '</body>';
	echo '</html>';
}

function cm_admin_check_permission($page_id, $permission) {
	global $adb, $admin_user;
	if (!$adb->user_has_permission($admin_user, $permission)) {
		cm_admin_head('Unauthorized');
		cm_admin_body('Unauthorized');
		cm_admin_nav($page_id);
		echo '<article>';
			echo '<div class="card cm-unauthorized">';
				echo '<div class="card-content">';
					echo '<p>You do not have permission to view this page.</p>';
				echo '</div>';
			echo '</div>';
		echo '</article>';
		cm_admin_dialogs();
		cm_admin_tail();
		exit(0);
	}
}
