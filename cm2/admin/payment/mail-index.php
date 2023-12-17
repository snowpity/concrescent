<?php

require_once __DIR__ .'/../../lib/database/mail.php';
require_once __DIR__ .'/../../lib/util/util.php';
require_once __DIR__ .'/../../lib/util/cmlists.php';
require_once __DIR__ .'/../admin.php';

cm_admin_check_permission('payment-mail', 'payment-mail');

$mdb = new cm_mail_db($db);

$list_def = array(
	'ajax-url' => get_site_url(false) . '/admin/payment/mail-index.php',
	'entity-type' => 'form letter',
	'entity-type-pl' => 'form letters',
	'search-criteria' => 'name, subject, or body',
	'columns' => array(
		array(
			'name' => 'Name',
			'key' => 'name',
			'type' => 'text'
		),
	),
	'sort-order' => array(0),
	'row-key' => 'name',
	'name-key' => 'name',
	'row-actions' => array('edit', 'delete'),
	'table-actions' => array('add'),
	'add-url' => get_site_url(false) . '/admin/payment/mail-edit.php',
	'edit-url' => get_site_url(false) . '/admin/payment/mail-edit.php?name=',
	'delete-title' => 'Delete Form Letter'
);

if (isset($_POST['cm-list-action'])) {
	header('Content-type: text/plain');
	switch ($_POST['cm-list-action']) {
		case 'list':
			$template_names = array();
			$templates = $mdb->list_mail_templates();
			foreach ($templates as $template) {
				$template_prefix = substr($template['name'], 0, 18);
				if (
					$template_prefix == 'payment-requested-' ||
					$template_prefix == 'payment-completed-'
				) {
					$template_name = substr($template['name'], 18);
					if (isset($template_names[$template_name])) {
						$template_names[$template_name]['search-content'] = array_merge(
							$template_names[$template_name]['search-content'],
							array(
								$template['contact-address'],
								$template['from'],
								$template['bcc'],
								$template['subject'],
								$template['body']
							)
						);
					} else {
						$template_names[$template_name] = array(
							'name' => $template_name,
							'search-content' => array(
								$template_name,
								$template['contact-address'],
								$template['from'],
								$template['bcc'],
								$template['subject'],
								$template['body']
							)
						);
					}
				}
			}
			$template_entities = array_values($template_names);
			$response = cm_list_process_entities($list_def, $template_entities);
			echo json_encode($response);
			break;
		case 'delete':
			$id = $_POST['cm-list-key'];
			$ok1 = $mdb->clear_mail_template('payment-requested-' . $id);
			$ok2 = $mdb->clear_mail_template('payment-completed-' . $id);
			$response = array('ok' => ($ok1 || $ok2));
			echo json_encode($response);
			break;
	}
	exit(0);
}

cm_admin_head('Payment Request Form Letters');
cm_list_head($list_def);
cm_admin_body('Payment Request Form Letters');
cm_admin_nav('payment-mail');

echo '<article class="cm-search-page">';
cm_list_search_box($list_def);
cm_list_table($list_def);
echo '</article>';

cm_admin_dialogs();
cm_list_dialogs($list_def);
cm_admin_tail();
