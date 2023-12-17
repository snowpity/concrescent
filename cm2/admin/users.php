<?php

require_once __DIR__ .'/../lib/util/util.php';
require_once __DIR__ .'/../lib/util/cmlists.php';
require_once __DIR__ .'/admin.php';
require_once __DIR__ .'/admin-perms.php';

cm_admin_check_permission('admin-users', 'admin-users');

$list_def = array(
	'ajax-url' => get_site_url(false) . '/admin/users.php',
	'entity-type' => 'user',
	'entity-type-pl' => 'users',
	'search-criteria' => 'display name or user name',
	'columns' => array(
		array(
			'name' => 'Display Name',
			'key' => 'name',
			'type' => 'text'
		),
		array(
			'name' => 'User Name',
			'key' => 'username',
			'type' => 'text'
		),
	),
	'sort-order' => array(0),
	'row-key' => 'username',
	'name-key' => 'name',
	'active-key' => 'active',
	'row-actions' => array('switch', 'edit', 'delete'),
	'table-actions' => array('add'),
	'add-title' => 'Add User',
	'edit-title' => 'Edit User',
	'delete-title' => 'Delete User',
);
$list_def['edit-clear-function'] = <<<END
	function() {
		$('#ea-name').val('');
		$('#ea-username').val('');
		$('#ea-password').val('');
		$('#ea-active').prop('checked', true);
		$('.ea-permissions').prop('checked', false);
	}
END;
$list_def['edit-load-function'] = <<<END
	function(id, e) {
		$('#ea-name').val(e['name']);
		$('#ea-username').val(e['username']);
		$('#ea-password').val('');
		$('#ea-active').prop('checked', !!e['active']);
		$('.ea-permissions').each(function() {
			var name = $(this).attr('id').substring(15);
			$(this).prop('checked', e['permissions'].indexOf(name) >= 0);
		});
	}
END;
$list_def['edit-save-function'] = <<<END
	function(id, e) {
		var ne = {
			'name': $('#ea-name').val(),
			'username': $('#ea-username').val(),
			'password': $('#ea-password').val(),
			'active': $('#ea-active').is(':checked'),
			'permissions': []
		};
		$('.ea-permissions').each(function() {
			if ($(this).is(':checked')) {
				var name = $(this).attr('id').substring(15);
				ne['permissions'].push(name);
			}
		});
		return ne;
	}
END;

if (isset($_POST['cm-list-action'])) {
	header('Content-type: text/plain');
	switch ($_POST['cm-list-action']) {
		case 'list':
			$users = $adb->list_users();
			$response = cm_list_process_entities($list_def, $users);
			echo json_encode($response);
			break;
		case 'create':
			$user = json_decode($_POST['cm-list-entity'], true);
			$ok = $adb->create_user($user);
			$response = array('ok' => $ok);
			if ($ok) {
				$user = $adb->get_user($user['username']);
				if ($user) {
					$response['row'] = cm_list_make_row($list_def, $user);
				}
			}
			echo json_encode($response);
			break;
		case 'update':
			$username = $_POST['cm-list-key'];
			$user = json_decode($_POST['cm-list-entity'], true);
			$ok = $adb->update_user($username, $user);
			$response = array('ok' => $ok);
			if ($ok) {
				if (isset($user['username']) && $user['username']) {
					$username = $user['username'];
				}
				$user = $adb->get_user($username);
				if ($user) {
					$response['row'] = cm_list_make_row($list_def, $user);
				}
			}
			echo json_encode($response);
			break;
		case 'delete':
			$username = $_POST['cm-list-key'];
			$ok = $adb->delete_user($username);
			$response = array('ok' => $ok);
			echo json_encode($response);
			break;
		case 'activate':
		case 'deactivate':
			$username = $_POST['cm-list-key'];
			$ok = $adb->activate_user($username, $_POST['cm-list-action'] == 'activate');
			$response = array('ok' => $ok);
			if ($ok) {
				$user = $adb->get_user($username);
				if ($user) {
					$response['row'] = cm_list_make_row($list_def, $user);
				}
			}
			echo json_encode($response);
			break;
	}
	exit(0);
}

cm_admin_head('Admin Users');
cm_list_head($list_def);
cm_admin_body('Admin Users');
cm_admin_nav('admin-users');

echo '<article class="cm-search-page">';
cm_list_search_box($list_def);
cm_list_table($list_def);
echo '</article>';

cm_admin_dialogs();
cm_list_edit_dialog_start();

echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-form-table cm-user-editor">';
	echo '<tr>';
		echo '<th><label for="ea-name">Display Name:</label></th>';
		echo '<td><input type="text" name="ea-name" id="ea-name"></td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-username">User Name:</label></th>';
		echo '<td><input type="text" name="ea-username" id="ea-username"></td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-password">Password:</label></th>';
		echo '<td><input type="password" name="ea-password" id="ea-password"></td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-active">Active:</label></th>';
		echo '<td><label><input type="checkbox" name="ea-active" id="ea-active">Active</label></td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th class="th-tall">Permissions:</th>';
		echo '<td>';
			$first_group = true;
			foreach ($cm_admin_perms as $group) {
				$first_link = true;
				foreach ($group as $perm) {
					if ($first_link && !$first_group) echo '<hr>';
					echo '<div><label';
					if (isset($perm['description']) && $perm['description']) {
						echo ' title="';
						echo htmlspecialchars($perm['description']);
						echo '"';
					}
					echo '>';
					$id = htmlspecialchars('ea-permissions-' . $perm['id']);
					echo '<input type="checkbox"';
					echo ' name="' . $id . '"';
					echo ' id="' . $id . '"';
					echo ' class="ea-permissions">';
					echo htmlspecialchars($perm['name']);
					echo '</label></div>';
					$first_link = false;
				}
				if (!$first_link) $first_group = false;
			}
		echo '</td>';
	echo '</tr>';
echo '</table>';

cm_list_edit_dialog_end();
cm_list_dialogs($list_def);
cm_admin_tail();
