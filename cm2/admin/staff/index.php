<?php

require_once __DIR__ .'/../../lib/database/staff.php';
require_once __DIR__ .'/../../lib/database/forms.php';
require_once __DIR__ .'/../../lib/util/util.php';
require_once __DIR__ .'/../../lib/util/cmlists.php';
require_once __DIR__ .'/../admin.php';

cm_admin_check_permission('staff', array('||',
	'staff', 'staff-view', 'staff-review', 'staff-edit', 'staff-delete'
));

$can_view = $adb->user_has_permission($admin_user, 'staff-view');
$can_review = $adb->user_has_permission($admin_user, 'staff-review');
$can_edit = $adb->user_has_permission($admin_user, 'staff-edit');
$can_delete = $adb->user_has_permission($admin_user, 'staff-delete');

$sdb = new cm_staff_db($db);
$name_map = $sdb->get_badge_type_name_map();
$dept_map = $sdb->get_department_map();
$pos_map = $sdb->get_position_map();

$fdb = new cm_forms_db($db, 'staff');
$questions = $fdb->list_questions();

$columns = array_merge(
	array(
		array(
			'name' => 'ID',
			'key' => 'id-string',
			'type' => 'text'
		),
		array(
			'name' => 'Real Name',
			'key' => 'real-name',
			'type' => 'text'
		),
		array(
			'name' => 'Fandom Name',
			'key' => 'fandom-name',
			'type' => 'text'
		),
		array(
			'name' => 'Badge Type',
			'key' => 'badge-type-name',
			'type' => 'text'
		),
		array(
			'name' => 'Email Address',
			'key' => 'email-address',
			'type' => 'email-subbed'
		),
	),
	cm_form_questions_to_list_columns($questions),
	array(
		array(
			'name' => 'Application Status',
			'key' => 'application-status',
			'type' => 'status-label'
		),
		array(
			'name' => 'Department',
			'key' => 'assigned-department-name',
			'type' => 'text'
		),
		array(
			'name' => 'Position',
			'key' => 'assigned-position-name',
			'type' => 'text'
		),
		array(
			'name' => 'Payment Status',
			'key' => 'payment-status',
			'type' => 'status-label'
		),
		array(
			'name' => 'Payment Date',
			'key' => 'payment-date',
			'type' => 'text'
		),
		array(
			'name' => 'P',
			'key' => 'print-count',
			'type' => 'numeric'
		),
		array(
			'name' => 'C',
			'key' => 'checkin-count',
			'type' => 'numeric'
		),
	)
);

$list_def = array(
	'loader' => 'server-side',
	'ajax-url' => get_site_url(false) . '/admin/staff/index.php',
	'reindex-url' => get_site_url(false) . '/admin/staff/reindex.php',
	'entity-type' => 'staff application',
	'entity-type-pl' => 'staff applications',
	'search-criteria' => 'name, badge type, contact info, or transaction ID',
	'search-delay' => 500,
	'qr' => 'auto',
	'columns' => $columns,
	'sort-order' => array(~0),
	'row-key' => 'id',
	'name-key' => 'display-name',
	'row-actions' => array(
		(($can_view || $can_edit) ? 'edit' : null),
		($can_delete ? 'delete' : null),
		($can_review ? 'review' : null)
	),
	'table-actions' => array(($can_edit ? 'add' : null)),
	'edit-label' => ($can_edit ? 'Edit' : 'View'),
	'add-url' => get_site_url(false) . '/admin/staff/edit.php',
	'edit-url' => get_site_url(false) . '/admin/staff/edit.php?id=',
	'review-url' => get_site_url(false) . '/admin/staff/edit.php?review&id=',
	'delete-title' => 'Delete Staff Application'
);

if (isset($_POST['cm-list-action'])) {
	header('Content-type: text/plain');
	switch ($_POST['cm-list-action']) {
		case 'list':
			$time = microtime(true);
			$response = $sdb->cm_ldb->list_indexes($list_def);
			$response['rows'] = array();
			foreach ($response['ids'] as $id) {
				$staff_member = $sdb->get_staff_member($id, false, $name_map, $dept_map, $pos_map, $fdb);
				$response['rows'][] = cm_list_make_row($list_def, $staff_member);
			}
			$response['time'] = microtime(true) - $time;
			echo json_encode($response);
			break;
		case 'delete':
			$id = $_POST['cm-list-key'];
			$ok = $sdb->delete_staff_member($id);
			$response = array('ok' => $ok);
			echo json_encode($response);
			break;
	}
	exit(0);
}

cm_admin_head('Staff Applications');
cm_list_head($list_def);
cm_admin_body('Staff Applications');
cm_admin_nav('staff');

echo '<article class="cm-search-page">';
cm_list_search_box($list_def);
cm_list_table($list_def);
echo '</article>';

cm_admin_dialogs();
cm_list_dialogs($list_def);
cm_admin_tail();
