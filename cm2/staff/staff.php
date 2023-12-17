<?php

session_name('PHPSESSID_CMAPPLYSTAFF');
session_start();

require_once __DIR__ .'/../config/config.php';
require_once __DIR__ .'/../lib/database/database.php';
require_once __DIR__ .'/../lib/database/staff.php';
require_once __DIR__ .'/../lib/database/forms.php';
require_once __DIR__ .'/../lib/database/mail.php';
require_once __DIR__ .'/../lib/util/res.php';
require_once __DIR__ .'/../lib/util/util.php';

$event_name = $cm_config['event']['name'];
$db = new cm_db();

$sdb = new cm_staff_db($db);
$name_map = $sdb->get_badge_type_name_map();
$dept_map = $sdb->get_department_map();
$pos_map = $sdb->get_position_map();

$fdb = new cm_forms_db($db, 'staff');
$questions = $fdb->list_questions();

$mdb = new cm_mail_db($db);
$contact_address = $mdb->get_contact_address('staff-submitted');

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

function cm_app_closed() {
	global $event_name, $contact_address;
	cm_app_head('Staff Applications Closed');
	cm_app_body('Staff Applications Closed');
	echo '<article>';
	echo '<div class="card">';
	echo '<div class="card-content">';
	echo '<p>';
	echo 'Staff applications for <b>';
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

function cm_app_message($title, $custom_text_name, $default_text, $fields = null) {
	global $event_name, $fdb, $contact_address;
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
	echo '<a href="index.php" role="button" class="button register-button">';
	echo 'Start a New Application';
	echo '</a>';
	echo '</div>';
	echo '</div>';
	echo '</article>';
	cm_app_tail();
	exit(0);
}
