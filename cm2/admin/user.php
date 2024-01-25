<?php

require_once __DIR__ .'/../lib/util/util.php';
require_once __DIR__ .'/admin.php';

global $twig, $adb, $admin_user;

/**
 * @param string[] $banned_words
 */
function is_strong_password(string $password, array $banned_words = []): bool
{
	$banned_words = [
		...$banned_words,
		'password', '1234', 'qwerty', 'asdf', 'zxcv',
		'abcd', 'wxyz', '1111', '2000', '4321', '6969'
	];

	if (strlen($password) < 8) {
		return false;
	}
	if (!preg_match('/\d/', $password)) {
		return false;
	}
	if (!preg_match('/[A-Z]/', $password)) {
		return false;
	}
	if (!preg_match('/[a-z]/', $password)) {
		return false;
	}
	$password = strtolower($password);
	foreach ($banned_words as $banned_word) {
		$banned_word = strtolower($banned_word);
		if (str_contains($password, $banned_word)) {
			return false;
		}
	}
	return true;
}

if (isset($_POST['submit'])) {
	$old_username = (isset($_POST['username'   ]) ? trim($_POST['username'   ]) : '');
	$old_password = (isset($_POST['password'   ]) ? trim($_POST['password'   ]) : '');
	$new_name =     (isset($_POST['ea-name'    ]) ? trim($_POST['ea-name'    ]) : '');
	$new_username = (isset($_POST['ea-username']) ? trim($_POST['ea-username']) : '');
	$new_password = (isset($_POST['ea-password']) ? trim($_POST['ea-password']) : '');
	if ($old_username && $old_password) {
		if (!($admin_user = $adb->log_in($old_username, $old_password))) {
			$url = get_site_url(false) . '/admin/login.php?page=';
			$url .= urlencode($_SERVER['REQUEST_URI']);
			header('Location: ' . $url);
			exit(0);
		}
		$bannedWords = [$old_username, $old_password, $new_name, $new_username];
		if ($new_password && !is_strong_password($new_password, $bannedWords)) {
			$success = null;
			$error = 'Passwords must be at least 8 characters long and '.
				'must contain at least one uppercase letter, '.
				'one lowercase letter, and one digit. '.
				'It can\'t be a forbbiden word or your username.'
			;
		} else {
			$user = [];
			if ($new_name) {
				$user['name'] = $new_name;
			}
			if ($new_username) {
				$user['username'] = $new_username;
			}
			if ($new_password) {
				$user['password'] = $new_password;
			}
			if ($adb->update_user($old_username, $user)) {
				if ($new_username) {
					$old_username = $new_username;
				}
				if ($new_password) {
					$old_password = $new_password;
				}
				$success = 'Changes saved.';
				$error = null;
			} else {
				$success = null;
				$error = 'Save failed. Please try again.';
			}
		}
		if (!($admin_user = $adb->log_in($old_username, $old_password))) {
			$url = get_site_url(false) . '/admin/login.php?page=';
			$url .= urlencode($_SERVER['REQUEST_URI']);
			header('Location: ' . $url);
			exit(0);
		}
	} else {
		$success = null;
		$error = 'You must enter your current user name and password to change your account settings.'
		;
	}
} else {
	$old_username = $admin_user['username'];
	$new_name     = $admin_user['name'    ];
	$new_username = $admin_user['username'];
	$success = null;
	$error = null;
}

echo $twig->render('pages/admin/user.twig', [
	'success' => $success,
	'error' => $error,
	'old_username' => $old_username,
	'new_name' => $new_name,
	'new_username' => $new_username,
]);

