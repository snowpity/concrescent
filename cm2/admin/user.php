<?php

require_once __DIR__ .'/../lib/util/util.php';
require_once __DIR__ .'/admin.php';

function is_strong_password($password, $banned_words) {
	if (strlen($password) < 8) return false;
	if (!preg_match('/[0-9]/', $password)) return false;
	if (!preg_match('/[A-Z]/', $password)) return false;
	if (!preg_match('/[a-z]/', $password)) return false;
	$password = strtolower($password);
	foreach ($banned_words as $banned_word) {
		$banned_word = strtolower($banned_word);
		if (strpos($password, $banned_word) !== false) {
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
		if ($new_password && !is_strong_password($new_password, array(
			$old_username, $old_password, $new_name, $new_username,
			'password', '1234', 'qwerty', 'asdf', 'zxcv',
			'abcd', 'wxyz', '1111', '2000', '4321', '6969'
		))) {
			$success = null;
			$error = (
				'Passwords must be at least 8 characters long and '.
				'must contain at least one uppercase letter, '.
				'one lowercase letter, and one digit.'
			);
		} else {
			$user = array();
			if ($new_name    ) $user['name'    ] = $new_name    ;
			if ($new_username) $user['username'] = $new_username;
			if ($new_password) $user['password'] = $new_password;
			if ($adb->update_user($old_username, $user)) {
				if ($new_username) $old_username = $new_username;
				if ($new_password) $old_password = $new_password;
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
		$error = (
			'You must enter your current user name and '.
			'password to change your account settings.'
		);
	}
} else {
	$old_username = $admin_user['username'];
	$new_name     = $admin_user['name'    ];
	$new_username = $admin_user['username'];
	$success = null;
	$error = null;
}

cm_admin_head('Account Settings');
cm_admin_body('Account Settings');
cm_admin_nav('admin-user');

echo '<article>';
	echo '<form action="user.php" method="post" class="card cm-reg-edit">';
		echo '<div class="card-content">';
			if ($success || $error) {
				if ($success) echo '<p class="cm-success-box">'.htmlspecialchars($success).'</p>';
				if ($error) echo '<p class="cm-error-box">'.htmlspecialchars($error).'</p>';
				echo '<hr>';
			}
			echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-form-table">';
				echo '<tr>';
					echo '<td colspan="2"><p><big><b>Enter your current user name and password to change your account settings.</b></big></p></td>';
				echo '</tr>';
				echo '<tr>';
					echo '<th><label for="username">Current User Name:</label></th>';
					echo '<td><input type="text" name="username" id="username" value="'.htmlspecialchars($old_username).'"></td>';
				echo '</tr>';
				echo '<tr>';
					echo '<th><label for="password">Current Password:</label></th>';
					echo '<td><input type="password" name="password" id="password"></td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan="2" class="hr"><hr></td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td colspan="2"><p>Enter your new account information below. To keep the same user name or password, leave the field blank.</p></td>';
				echo '</tr>';
				echo '<tr>';
					echo '<th><label for="ea-name">Display Name:</label></th>';
					echo '<td><input type="text" name="ea-name" id="ea-name" value="'.htmlspecialchars($new_name).'"></td>';
				echo '</tr>';
				echo '<tr>';
					echo '<th><label for="ea-username">New User Name:</label></th>';
					echo '<td><input type="text" name="ea-username" id="ea-username" value="'.htmlspecialchars($new_username).'"></td>';
				echo '</tr>';
				echo '<tr>';
					echo '<th><label for="ea-password">New Password:</label></th>';
					echo '<td><input type="password" name="ea-password" id="ea-password"></td>';
				echo '</tr>';
			echo '</table>';
		echo '</div>';
		echo '<div class="card-buttons">';
			echo '<input type="submit" name="submit" value="Save Changes">';
		echo '</div>';
	echo '</form>';
echo '</article>';

cm_admin_dialogs();
cm_admin_tail();
