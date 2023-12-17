<?php

require_once __DIR__ .'/../../lib/database/payment.php';
require_once __DIR__ .'/../../lib/util/util.php';
require_once __DIR__ .'/../../lib/util/cmlists.php';
require_once __DIR__ .'/../admin.php';

cm_admin_check_permission('payments', array('||', 'payments', 'payments-view', 'payments-edit', 'payments-delete'));
$can_view = $adb->user_has_permission($admin_user, 'payments-view');
$can_edit = $adb->user_has_permission($admin_user, 'payments-edit');
$can_delete = $adb->user_has_permission($admin_user, 'payments-delete');

$pdb = new cm_payment_db($db);

$list_def = array(
	'ajax-url' => get_site_url(false) . '/admin/payment/index.php',
	'entity-type' => 'payment request',
	'entity-type-pl' => 'payment requests',
	'search-criteria' => 'name, contact info, or transaction ID',
	'columns' => array(
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
			'name' => 'Email Address',
			'key' => 'email-address',
			'type' => 'email'
		),
		array(
			'name' => 'Payment For',
			'key' => 'payment-name',
			'type' => 'text'
		),
		array(
			'name' => 'Requested Amount',
			'key' => 'payment-price',
			'type' => 'price'
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
	),
	'sort-order' => array(~0),
	'row-key' => 'id',
	'name-key' => 'id-string',
	'row-actions' => array(
		(($can_view || $can_edit) ? 'edit' : null),
		($can_delete ? 'delete' : null)
	),
	'table-actions' => array(($can_edit ? 'add' : null)),
	'edit-label' => ($can_edit ? 'Edit' : 'View'),
	'add-url' => get_site_url(false) . '/admin/payment/edit.php',
	'edit-url' => get_site_url(false) . '/admin/payment/edit.php?id=',
	'delete-title' => 'Delete Payment Request'
);

if (isset($_POST['cm-list-action'])) {
	header('Content-type: text/plain');
	switch ($_POST['cm-list-action']) {
		case 'list':
			$payments = $pdb->list_payments();
			$response = cm_list_process_entities($list_def, $payments);
			echo json_encode($response);
			break;
		case 'delete':
			$id = $_POST['cm-list-key'];
			$ok = $pdb->delete_payment($id);
			$response = array('ok' => $ok);
			echo json_encode($response);
			break;
	}
	exit(0);
}

cm_admin_head('Payment Requests');
cm_list_head($list_def);
cm_admin_body('Payment Requests');
cm_admin_nav('payments');

echo '<article class="cm-search-page">';
cm_list_search_box($list_def);
cm_list_table($list_def);
echo '</article>';

cm_admin_dialogs();
cm_list_dialogs($list_def);
cm_admin_tail();
