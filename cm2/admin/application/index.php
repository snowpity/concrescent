<?php

require_once dirname(__FILE__).'/../../config/config.php';
require_once dirname(__FILE__).'/../../lib/database/application.php';
require_once dirname(__FILE__).'/../../lib/database/forms.php';
require_once dirname(__FILE__).'/../../lib/util/util.php';
require_once dirname(__FILE__).'/../../lib/util/cmlists.php';
require_once dirname(__FILE__).'/../admin.php';

$context = (isset($_GET['c']) ? trim($_GET['c']) : null);
if (!$context) {
	header('Location: ../');
	exit(0);
}
$ctx_lc = strtolower($context);
$ctx_uc = strtoupper($context);
$ctx_info = (
	$cm_config['application_types'][$ctx_uc] ?? null
);
if (!$ctx_info) {
	header('Location: ../');
	exit(0);
}
$ctx_name = $ctx_info['nav_prefix'];
$ctx_name_lc = strtolower($ctx_name);

cm_admin_check_permission('applications-'.$ctx_lc, array('||',
	'applications-'.$ctx_lc,
	'applications-view-'.$ctx_lc,
	'applications-review-'.$ctx_lc,
	'applications-edit-'.$ctx_lc,
	'applications-delete-'.$ctx_lc
));

$can_view = $adb->user_has_permission($admin_user, 'applications-view-'.$ctx_lc);
$can_review = $adb->user_has_permission($admin_user, 'applications-review-'.$ctx_lc);
$can_edit = $adb->user_has_permission($admin_user, 'applications-edit-'.$ctx_lc);
$can_delete = $adb->user_has_permission($admin_user, 'applications-delete-'.$ctx_lc);

$apdb = new cm_application_db($db, $context);
$name_map = $apdb->get_badge_type_name_map();

$fdb = new cm_forms_db($db, 'application-'.$ctx_lc);
$questions = $fdb->list_questions();

$columns = array_merge(
	array(
		array(
			'name' => 'ID',
			'key' => 'id-string',
			'type' => 'text'
		),
		array(
			'name' => $ctx_info['business_name_term'],
			'key' => 'business-name',
			'type' => 'text'
		),
		array(
			'name' => $ctx_info['application_name_term'],
			'key' => 'application-name',
			'type' => 'text'
		),
		array(
			'name' => 'Badge Type',
			'key' => 'badge-type-name',
			'type' => 'text'
		),
		array(
			'name' => '# '.$ctx_info['assignment_term'][1],
			'key' => 'assignment-count',
			'type' => 'quantity'
		),
		array(
			'name' => '# Badges',
			'key' => 'applicant-count',
			'type' => 'quantity'
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
			'name' => 'Assigned '.$ctx_info['assignment_term'][0],
			'key' => 'assigned-room-or-table-id',
			'type' => 'text'
		),
		array(
			'name' => 'Permit Number',
			'key' => 'permit-number',
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
	)
);

$list_def = array(
	'loader' => 'server-side',
	'ajax-url' => get_site_url(false) . '/admin/application/index.php?c='.$ctx_lc,
	'reindex-url' => get_site_url(false) . '/admin/application/reindex.php?c='.$ctx_lc,
	'entity-type' => $ctx_name_lc.' application',
	'entity-type-pl' => $ctx_name_lc.' applications',
	'search-criteria' => 'name, badge type, contact info, or transaction ID',
	'search-delay' => 500,
	'qr' => 'auto',
	'columns' => $columns,
	'sort-order' => array(~0),
	'row-key' => 'id',
	'name-key' => 'application-name',
	'row-actions' => array(
		(($can_view || $can_edit) ? 'edit' : null),
		($can_delete ? 'delete' : null),
		($can_review ? 'review' : null)
	),
	'table-actions' => array(($can_edit ? 'add' : null)),
	'edit-label' => ($can_edit ? 'Edit' : 'View'),
	'add-url' => get_site_url(false) . '/admin/application/edit.php?c='.$ctx_lc,
	'edit-url' => get_site_url(false) . '/admin/application/edit.php?c='.$ctx_lc.'&id=',
	'review-url' => get_site_url(false) . '/admin/application/edit.php?c='.$ctx_lc.'&review&id=',
	'delete-title' => 'Delete '.$ctx_name.' Application'
);

if (isset($_POST['cm-list-action'])) {
	header('Content-type: text/plain');
	switch ($_POST['cm-list-action']) {
		case 'list':
			$time = microtime(true);
			$response = $apdb->cm_anldb->list_indexes($list_def);
			$response['rows'] = array();
			foreach ($response['ids'] as $id) {
				$application = $apdb->get_application($id, false, true, $name_map, $fdb);
				$response['rows'][] = cm_list_make_row($list_def, $application);
			}
			$response['time'] = microtime(true) - $time;
			echo json_encode($response);
			break;
		case 'delete':
			$id = $_POST['cm-list-key'];
			$ok = $apdb->delete_application($id);
			$response = array('ok' => $ok);
			echo json_encode($response);
			break;
	}
	exit(0);
}

cm_admin_head($ctx_name.' Applications');
cm_list_head($list_def);
cm_admin_body($ctx_name.' Applications');
cm_admin_nav('applications-'.$ctx_lc);

echo '<article class="cm-search-page">';
cm_list_search_box($list_def);
cm_list_table($list_def);
echo '</article>';

cm_admin_dialogs();
cm_list_dialogs($list_def);
cm_admin_tail();
