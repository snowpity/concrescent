<?php

require_once dirname(__FILE__).'/../../config/config.php';
require_once dirname(__FILE__).'/../../lib/database/application.php';
require_once dirname(__FILE__).'/../../lib/database/forms.php';
require_once dirname(__FILE__).'/../../lib/util/util.php';
require_once dirname(__FILE__).'/../../lib/util/cmforms.php';
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

cm_admin_check_permission('application-questions-'.$ctx_lc, 'application-questions-'.$ctx_lc);

$apdb = new cm_application_db($db, $context);
$name_list = $apdb->list_badge_type_names();

$form_def = array(
	'ajax-url' => get_site_url(false) . '/admin/application/questions.php?c=' . $ctx_lc,
	'context' => 'application-' . $ctx_lc,
	'subcontext' => $name_list
);

$fdb = new cm_forms_db($db, $form_def['context']);
cm_form_edit_process_requests($fdb);

cm_admin_head($ctx_name . ' Questions');
cm_form_edit_head($form_def);
cm_admin_body($ctx_name . ' Questions');
cm_admin_nav('application-questions-' . $ctx_lc);

echo '<article>';
cm_form_edit_body($form_def, array(
	array('type' => 'h1', 'title' => $ctx_name . ' Application for ' . $cm_config['event']['name']),
	array('type' => 'custom-text', 'name' => 'main'),

	array('type' => 'hr'),

	array('type' => 'h2', 'title' => 'Primary Contact Information'),
	array('type' => 'custom-text', 'name' => 'contact', 'default' =>
		'Please provide us with the name and contact information of '.
		'the person we should contact regarding this application. '.
		'This is the person who will be contacted in case this '.
		'application is accepted.'
	),
	array('type' => 'text', 'title' => 'First Name'),
	array('type' => 'text', 'title' => 'Last Name'),
	array('type' => 'text', 'title' => 'Email Address'),
	array('type' => 'text', 'title' => 'Phone Number'),

	array('type' => 'hr'),

	array('type' => 'h2', 'title' => $ctx_name . ' Information'),
	array('type' => 'custom-text', 'name' => 'application'),
	array(
		'type' => 'select',
		'title' => 'Badge Type',
		'values' => array($name_list ? $name_list[0]['name'] : ''),
		'text' => 'The type of ' . $ctx_name_lc . ' badge you are requesting.'
	),
	array(
		'type' => 'text',
		'title' => $ctx_info['business_name_term'],
		'text' => $ctx_info['business_name_text']
	),
	array(
		'type' => 'text',
		'title' => $ctx_info['application_name_term'],
		'text' => $ctx_info['application_name_text']
	),
	array(
		'type' => 'text',
		'title' => $ctx_info['assignment_term'][1] . ' Requested',
		'text' => 'The number of ' . strtolower($ctx_info['assignment_term'][1]) . ' you are requesting.'
	),
	array('type' => 'custom-questions'),

	array('type' => 'hr'),

	array('type' => 'h2', 'title' => 'Badge Information'),
	array('type' => 'custom-text', 'name' => 'applicant', 'default' =>
		'Please provide us with the names and contact information of '.
		'the people who should receive badges, <b>INCLUDING YOURSELF</b>. '.
		'We ask for date of birth only to verify age.'
	),
	array('type' => 'text', 'title' => 'Badges Requested'),
	array('type' => 'text', 'title' => 'First Name'),
	array('type' => 'text', 'title' => 'Last Name'),
	array('type' => 'text', 'title' => 'Fandom Name'),
	array('type' => 'select', 'title' => 'Name on Badge', 'values' => array('Fandom Name Large, Real Name Small')),
	array('type' => 'text', 'title' => 'Date of Birth'),
	array('type' => 'text', 'title' => 'Email Address'),
	array('type' => 'text', 'title' => 'Phone Number'),
));
cm_form_edit_body($form_def, array(
	array('type' => 'h1', 'title' => 'Application Submitted'),
	array('type' => 'custom-text', 'name' => 'application-submitted', 'default' =>
		'Your '.$ctx_name_lc.' application has been submitted.'
	),
));
cm_form_edit_body($form_def, array(
	array('type' => 'h1', 'title' => 'Application Already Submitted'),
	array('type' => 'custom-text', 'name' => 'application-already-submitted', 'default' =>
		'An application for this '.$ctx_name_lc.' has already been submitted.<br><br>'.
		'Please <b><a href="mailto:[[contact-address]]">contact us</a></b> '.
		'if you need to update your application or if you believe this is an error.'
	),
));
cm_form_edit_body($form_def, array(
	array('type' => 'h1', 'title' => 'Payment Complete'),
	array('type' => 'custom-text', 'name' => 'payment-complete', 'default' =>
		'Your '.$ctx_name_lc.' application has been confirmed '.
		'and your payment, if required, has been accepted.<br><br>'.
		'You can <b><a href="[[review-link]]">review your order</a></b> at any time.'
	),
));
cm_form_edit_body($form_def, array(
	array('type' => 'h1', 'title' => 'Payment Refused'),
	array('type' => 'custom-text', 'name' => 'payment-refused', 'default' =>
		'PayPal has refused this transaction.<br><br>'.
		'PayPal says: [[payment-txn-msg]]<br><br>'.
		'Unfortunately, that is all we know. Please try again later.'
	),
));
cm_form_edit_body($form_def, array(
	array('type' => 'h1', 'title' => 'Payment Cancelled'),
	array('type' => 'custom-text', 'name' => 'payment-cancelled', 'default' =>
		'You have cancelled your payment.'
	),
));
echo '</article>';

cm_admin_dialogs();
cm_form_edit_dialogs();
cm_admin_tail();
