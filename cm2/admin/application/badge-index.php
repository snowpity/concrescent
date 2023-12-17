<?php

require_once __DIR__ .'/../../config/config.php';
require_once __DIR__ .'/../../lib/database/application.php';
require_once __DIR__ .'/../../lib/database/forms.php';
require_once __DIR__ .'/../../lib/util/util.php';
require_once __DIR__ .'/../../lib/util/cmlists.php';
require_once __DIR__ .'/../admin.php';

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

cm_admin_check_permission('applicants-'.$ctx_lc, array('||',
	'applicants-'.$ctx_lc,
	'applicants-view-'.$ctx_lc,
	'applicants-edit-'.$ctx_lc,
	'applicants-delete-'.$ctx_lc
));

$can_view = $adb->user_has_permission($admin_user, 'applicants-view-'.$ctx_lc);
$can_edit = $adb->user_has_permission($admin_user, 'applicants-edit-'.$ctx_lc);
$can_delete = $adb->user_has_permission($admin_user, 'applicants-delete-'.$ctx_lc);

$apdb = new cm_application_db($db, $context);
$name_map = $apdb->get_badge_type_name_map();
$fdb = new cm_forms_db($db, 'application-'.$ctx_lc);

$columns = array(
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
		'name' => 'Email Address',
		'key' => 'email-address',
		'type' => 'email-subbed'
	),
	array(
		'name' => 'Already Registered',
		'key' => 'attendee-id',
		'type' => 'bool'
	),
	array(
		'name' => 'Status',
		'key' => 'application-status',
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
);

$list_def = array(
	'loader' => 'server-side',
	'ajax-url' => get_site_url(false) . '/admin/application/badge-index.php?c='.$ctx_lc,
	'reindex-url' => get_site_url(false) . '/admin/application/reindex.php?c='.$ctx_lc,
	'entity-type' => $ctx_name_lc.' badge',
	'entity-type-pl' => $ctx_name_lc.' badges',
	'search-criteria' => 'name, badge type, contact info, or transaction ID',
	'search-delay' => 500,
	'qr' => 'auto',
	'columns' => $columns,
	'sort-order' => array(~0),
	'row-key' => 'id',
	'name-key' => 'display-name',
	'row-actions' => array(
		(($can_view || $can_edit) ? 'edit' : null),
		($can_delete ? 'delete' : null)
	),
	'edit-label' => ($can_edit ? 'Edit' : 'View'),
	'edit-url' => get_site_url(false) . '/admin/application/badge-edit.php?c='.$ctx_lc.'&id=',
	'delete-title' => 'Delete '.$ctx_name.' Badge'
);

if (isset($_POST['cm-list-action'])) {
	header('Content-type: text/plain');
	switch ($_POST['cm-list-action']) {
		case 'list':
			$time = microtime(true);
			$response = $apdb->cm_atldb->list_indexes($list_def);
			$response['rows'] = array();
			foreach ($response['ids'] as $id) {
				$applicant = $apdb->get_applicant($id, false, true, $name_map, $fdb);
				$response['rows'][] = cm_list_make_row($list_def, $applicant);
			}
			$response['time'] = microtime(true) - $time;
			echo json_encode($response);
			break;
		case 'delete':
			$id = $_POST['cm-list-key'];
			$ok = $apdb->delete_applicant($id);
			$response = array('ok' => $ok);
			echo json_encode($response);
			break;
	}
	exit(0);
}

cm_admin_head($ctx_name.' Badges');
cm_list_head($list_def);
cm_admin_body($ctx_name.' Badges');
cm_admin_nav('applicants-'.$ctx_lc);

echo '<article class="cm-search-page">';
cm_list_search_box($list_def);
cm_list_table($list_def);
echo '</article>';

cm_admin_dialogs();
cm_list_dialogs($list_def);
cm_admin_tail();
