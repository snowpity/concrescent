<?php

use JetBrains\PhpStorm\NoReturn;

require_once __DIR__ .'/../config/config.php';

$context = (isset($_GET['c']) ? trim($_GET['c']) : null);
if (!$context) {
	header('Location: ../staff/');
	exit(0);
}
$ctx_lc = strtolower($context);
$ctx_uc = strtoupper($context);
$ctx_info = (
	$cm_config['application_types'][$ctx_uc] ?? null
);
if (!$ctx_info) {
	header('Location: ../staff/');
	exit(0);
}
$ctx_name = $ctx_info['nav_prefix'];
$ctx_name_lc = strtolower($ctx_name);

session_name('PHPSESSID_CMAPPLYAPP_' . $ctx_uc);
session_start();

require_once __DIR__ .'/../lib/database/database.php';
require_once __DIR__ .'/../lib/database/application.php';
require_once __DIR__ .'/../lib/database/forms.php';
require_once __DIR__ .'/../lib/database/mail.php';
require_once __DIR__ .'/../lib/util/res.php';
require_once __DIR__ .'/../lib/util/util.php';

$event_name = $cm_config['event']['name'];
$db = new cm_db();

$apdb = new cm_application_db($db, $context);
$name_map = $apdb->get_badge_type_name_map();

$fdb = new cm_forms_db($db, 'application-' . $ctx_lc);
$questions = $fdb->list_questions();

$mdb = new cm_mail_db($db);
$contact_address = $mdb->get_contact_address('application-submitted-' . $ctx_lc);

function cm_app_cart_set_state($state, $cart = null) {
	if ($cart) $_SESSION['cart'] = $cart;
	if (!isset($_SESSION['cart'])) $_SESSION['cart'] = array();
	$_SESSION['cart_hash'] = md5(serialize($_SESSION['cart']));
	$_SESSION['cart_state'] = $state;
}

function cm_app_cart_check_state($expected_state) {
	if (!isset($_SESSION['cart'])) return false;
	if (!isset($_SESSION['cart_hash'])) return false;
	if (!isset($_SESSION['cart_state'])) return false;
	$expected_hash = md5(serialize($_SESSION['cart']));
	if ($_SESSION['cart_hash'] != $expected_hash) return false;
	if ($_SESSION['cart_state'] != $expected_state) return false;
	return true;
}

function cm_app_cart_destroy() {
	unset($_SESSION['cart']);
	unset($_SESSION['cart_hash']);
	unset($_SESSION['cart_state']);
	session_destroy();
}

function cm_app_head($title) {
	echo '<!DOCTYPE HTML>';
	echo '<html>';
	echo '<head>';
	echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
	echo '<title>' . htmlspecialchars($title) . '</title>';
	echo '<link rel="shortcut icon" href="' . htmlspecialchars(theme_file_url('favicon.ico', false)) . '">';
	echo '<link rel="stylesheet" href="' . htmlspecialchars(resource_file_url('cm.css', false)) . '">';
	echo '<link rel="stylesheet" href="' . htmlspecialchars(theme_file_url('theme.css', false)) . '">';
	echo '<script type="text/javascript" src="' . htmlspecialchars(resource_file_url('jquery.js', false)) . '"></script>';
	echo '<script type="text/javascript" src="' . htmlspecialchars(resource_file_url('cmui.js', false)) . '"></script>';
}

function cm_app_body($title) {
	echo '</head>';
	echo '<body class="cm-reg">';
	echo '<header>';
	echo '<div class="pagename">' . htmlspecialchars($title) . '</div>';
	echo '</header>';
}

function cm_app_tail() {
	echo '</body>';
	echo '</html>';
}

#[NoReturn]
function cm_app_closed() {
	global $ctx_name, $event_name, $contact_address;
	cm_app_head($ctx_name . ' Applications Closed');
	cm_app_body($ctx_name . ' Applications Closed');
	echo '<article>';
	echo '<div class="card">';
	echo '<div class="card-content">';
	echo '<p>';
	echo htmlspecialchars($ctx_name);
	echo ' applications for <b>';
	echo htmlspecialchars($event_name);
	echo '</b> are currently closed.';
	if ($contact_address) {
		echo ' Please <b><a href="mailto:';
		echo htmlspecialchars($contact_address);
		echo '">contact us</a></b> if you have any questions.';
	}
	echo '</p>';
	echo '</div>';
	echo '</div>';
	echo '</article>';
	cm_app_tail();
	exit(0);
}

#[NoReturn]
function cm_app_message($title, $custom_text_name, $default_text, $fields = null) {
	global $ctx_lc, $ctx_name, $ctx_name_lc, $event_name, $fdb, $contact_address;
	cm_app_head($title);
	cm_app_body($title);
	echo '<article>';
	echo '<div class="card">';
	echo '<div class="card-title">';
	echo htmlspecialchars($title);
	echo '</div>';
	echo '<div class="card-content">';
	$text = $fdb->get_custom_text($custom_text_name);
	if (!$text) $text = $default_text;
	$text = safe_html_string($text, true);
	$merge_fields = array(
		'ctx-name' => $ctx_name,
		'ctx_name' => $ctx_name,
		'ctx-name-lc' => $ctx_name_lc,
		'ctx_name_lc' => $ctx_name_lc,
		'event-name' => $event_name,
		'event_name' => $event_name,
		'contact-address' => $contact_address,
		'contact_address' => $contact_address
	);
	if ($fields) {
		foreach ($fields as $k => $v) {
			$merge_fields[strtolower(str_replace('_', '-', $k))] = $v;
			$merge_fields[strtolower(str_replace('-', '_', $k))] = $v;
		}
	}
	echo mail_merge_html($text, $merge_fields);
	echo '</div>';
	echo '<div class="card-buttons">';
	echo '<a href="index.php?c=' . $ctx_lc . '" role="button" class="button register-button">';
	echo 'Start a New Application';
	echo '</a>';
	echo '</div>';
	echo '</div>';
	echo '</article>';
	cm_app_tail();
	exit(0);
}
