<?php

session_name('PHPSESSID_CMADMIN');
session_start();

require_once __DIR__ .'/../lib/database/database.php';
require_once __DIR__ .'/../lib/database/admin.php';
require_once __DIR__ .'/../lib/util/util.php';
require_once __DIR__ .'/../lib/util/res.php';
require_once __DIR__ .'/admin-nav.php';
global $twig;

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

$cm_admin_nav_Filtered = array_map(function (array $group) use ($adb, $admin_user) : array {
	$authorizedLinks = [];

	foreach ($group as $link) {
		if ( !isset($link['permission']) || !$link['permission']
			|| $adb->user_has_permission($admin_user, $link['permission'])
		) {
			$authorizedLinks[] = $link;
		}
	}

	return $authorizedLinks;
}, $cm_admin_nav);

$twig->addFunction(new \Twig\TwigFunction(
	'user_has_permission',
	fn (...$args) => $adb->user_has_permission($admin_user,...$args)
));
$twig->addGlobal('cm_admin_nav', $cm_admin_nav_Filtered);
$twig->addGlobal('adminUsername', $GLOBALS['admin_user']['name']);

function cm_admin_head($title): void
{
	global $twig;
	echo $twig->render('components/admin_head.twig', [
		'title' => $title
	]);
}

function cm_admin_body($title): void
{
	global $twig;
	echo $twig->render('components/admin_body.twig', [
		'title' => $title,
	]);
}

function cm_admin_nav($page_id): void
{
	global $twig;
	echo $twig->render('components/admin_nav.twig', [
		'page_id' => $page_id,
	]);
}

function cm_admin_dialogs(): void
{
	global $twig;
	echo $twig->render('components/admin_dialogs.twig');
}

function cm_admin_tail(): void
{
	global $twig;
	echo $twig->render('components/admin_tail.twig');
}

function cm_admin_check_permission($page_id, $permission): void
{
	global $adb, $admin_user, $twig;

	if (!$adb->user_has_permission($admin_user, $permission)) {
		echo $twig->render('pages/admin/unauthorized.twig', [
			'page_id' => $page_id
		]);
		die(0);
	}
}
