<?php

require_once __DIR__ .'/../../lib/database/staff.php';
require_once __DIR__ .'/../../lib/database/misc.php';
require_once __DIR__ .'/../../lib/util/util.php';
require_once __DIR__ .'/../../lib/util/cmlists.php';
require_once __DIR__ .'/../admin.php';

cm_admin_check_permission('staff-departments', 'staff-departments');

$sdb = new cm_staff_db($db);

$midb = new cm_misc_db($db);
$domain = $midb->getval('mail-default-domain', $_SERVER['SERVER_NAME']);

$list_def = array(
	'ajax-url' => get_site_url(false) . '/admin/staff/departments.php',
	'entity-type' => 'department',
	'entity-type-pl' => 'departments',
	'search-criteria' => 'name or description',
	'columns' => array(
		array(
			'name' => 'Name',
			'key' => 'name',
			'type' => 'text'
		),
		array(
			'name' => 'Primary Email Alias',
			'key' => 'mail-alias-1',
			'type' => 'email'
		),
		array(
			'name' => 'Secondary Email Alias',
			'key' => 'mail-alias-2',
			'type' => 'email'
		),
	),
	'sort-order' => array(0),
	'row-key' => 'id',
	'name-key' => 'name',
	'active-key' => 'active',
	'row-actions' => array('switch', 'edit', 'delete'),
	'table-actions' => array('add'),
	'add-title' => 'Add Department',
	'edit-title' => 'Edit Department',
	'delete-title' => 'Delete Department',
	'edit-clear-function' => 'cm_departments_ui.clear',
	'edit-load-function' => 'cm_departments_ui.load',
	'edit-save-function' => 'cm_departments_ui.save'
);

if (isset($_POST['cm-list-action'])) {
	header('Content-type: text/plain');
	switch ($_POST['cm-list-action']) {
		case 'list':
			$departments = $sdb->list_departments();
			$response = cm_list_process_entities($list_def, $departments);
			echo json_encode($response);
			break;
		case 'create':
			$department = json_decode($_POST['cm-list-entity'], true);
			$id = $sdb->create_department($department);
			$ok = ($id !== false);
			$response = array('ok' => $ok);
			if ($ok) {
				$department = $sdb->get_department($id);
				if ($department) {
					$response['row'] = cm_list_make_row($list_def, $department);
				}
			}
			echo json_encode($response);
			break;
		case 'update':
			$department = json_decode($_POST['cm-list-entity'], true);
			$department['id'] = $_POST['cm-list-key'];
			$ok = $sdb->update_department($department);
			$response = array('ok' => $ok);
			if ($ok) {
				$department = $sdb->get_department($department['id']);
				if ($department) {
					$response['row'] = cm_list_make_row($list_def, $department);
				}
			}
			echo json_encode($response);
			break;
		case 'delete':
			$id = $_POST['cm-list-key'];
			$ok = $sdb->delete_department($id);
			$response = array('ok' => $ok);
			echo json_encode($response);
			break;
		case 'activate':
		case 'deactivate':
			$id = $_POST['cm-list-key'];
			$ok = $sdb->activate_department($id, $_POST['cm-list-action'] == 'activate');
			$response = array('ok' => $ok);
			if ($ok) {
				$department = $sdb->get_department($id);
				if ($department) {
					$response['row'] = cm_list_make_row($list_def, $department);
				}
			}
			echo json_encode($response);
			break;
	}
	exit(0);
}

cm_admin_head('Departments');

?><style>
	.edit-dialog {
		width: 800px;
		margin-left: -400px;
	}
	.ea-position-row + .ea-position-row {
		margin-top: 4px;
	}
</style><?php

echo '<script type="text/javascript" src="departments.js"></script>';
cm_list_head($list_def);

cm_admin_body('Departments');
cm_admin_nav('staff-departments');

echo '<article class="cm-search-page">';
cm_list_search_box($list_def);
cm_list_table($list_def);
echo '</article>';

cm_admin_dialogs();
cm_list_edit_dialog_start();

echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-form-table">';
	echo '<tr>';
		echo '<th><label for="ea-parent-id">Parent Department:</label></th>';
		echo '<td>';
			echo '<select name="ea-parent-id" id="ea-parent-id">';
				echo '<option value="">(None)</option>';
			echo '</select>';
		echo '</td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-name">Department Name:</label></th>';
		echo '<td><input type="text" name="ea-name" id="ea-name"></td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-description">Description:</label></th>';
		echo '<td><textarea name="ea-description" id="ea-description"></textarea></td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-mail-alias-1">Primary Email Alias:</label></th>';
		echo '<td>';
			echo '<input type="email" name="ea-mail-alias-1" id="ea-mail-alias-1">';
			echo '<span class="cm-mail-alias-admonishment">(This is for a <b>mailing list</b> at <b>' . htmlspecialchars($domain) . '</b>, <b>NOT</b> a personal address.)</span>';
		echo '</td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-mail-alias-2">Secondary Email Alias:</label></th>';
		echo '<td>';
			echo '<input type="email" name="ea-mail-alias-2" id="ea-mail-alias-2">';
			echo '<span class="cm-mail-alias-admonishment">(This is for a <b>mailing list</b> at <b>' . htmlspecialchars($domain) . '</b>, <b>NOT</b> a personal address.)</span>';
		echo '</td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-mail-depth">Default Recipient List:</label></th>';
		echo '<td>';
			echo '<select name="ea-mail-depth" id="ea-mail-depth">';
				echo '<option value="Executive">execs - only staff members in executive positions in this department</option>';
				echo '<option value="Staff">staff - staff members in all positions in this department</option>';
				echo '<option value="Recursive">all - all staff members in this department and all departments underneath</option>';
			echo '</select>';
		echo '</td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-active">Active:</label></th>';
		echo '<td><label><input type="checkbox" name="ea-active" id="ea-active">Active</label></td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th>Positions:</th>';
		echo '<td id="ea-positions">';
			echo '<div class="ea-position-row">';
				echo 'None';
			echo '</div>';
		echo '</td>';
	echo '</tr>';
	echo '<tr class="hidden">';
		echo '<th></th>';
		echo '<td id="ea-position-none">';
			echo '<div class="ea-position-row">';
				echo 'None';
			echo '</div>';
		echo '</td>';
	echo '</tr>';
	echo '<tr class="hidden">';
		echo '<th></th>';
		echo '<td id="ea-position-template">';
			echo '<div class="ea-position-row">';
				echo '<input type="text" class="ea-position-name">&nbsp;&nbsp;';
				echo '<label><input type="checkbox" class="ea-position-executive">Executive</label>&nbsp;&nbsp;';
				echo '<label><input type="checkbox" class="ea-position-active" checked>Active</label>&nbsp;&nbsp;';
				echo '<input type="hidden" class="ea-position-id">&nbsp;&nbsp;';
				echo '<button class="up-button">&#x2191;</button>';
				echo '<button class="down-button">&#x2193;</button>';
				echo '<button class="delete-button">Delete</button>';
			echo '</div>';
		echo '</td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th></th>';
		echo '<td id="ea-position-add">';
			echo '<button class="add-button">Add</button>';
		echo '</td>';
	echo '</tr>';
echo '</table>';

cm_list_edit_dialog_end();
cm_list_dialogs($list_def);
cm_admin_tail();
