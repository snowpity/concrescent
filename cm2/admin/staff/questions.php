<?php

require_once __DIR__ .'/../../config/config.php';
require_once __DIR__ .'/../../lib/database/staff.php';
require_once __DIR__ .'/../../lib/database/forms.php';
require_once __DIR__ .'/../../lib/util/util.php';
require_once __DIR__ .'/../../lib/util/cmforms.php';
require_once __DIR__ .'/../admin.php';

cm_admin_check_permission('staff-questions', 'staff-questions');

$sdb = new cm_staff_db($db);
$name_list = $sdb->list_badge_type_names();

$form_def = array(
	'ajax-url' => get_site_url(false) . '/admin/staff/questions.php',
	'context' => 'staff',
	'subcontext' => $name_list
);

$fdb = new cm_forms_db($db, $form_def['context']);
cm_form_edit_process_requests($fdb);

cm_admin_head('Staff Questions');
cm_form_edit_head($form_def);
cm_admin_body('Staff Questions');
cm_admin_nav('staff-questions');

echo '<article>';
cm_form_edit_body($form_def, array(
	array('type' => 'h1', 'title' => 'Staff Application for ' . $cm_config['event']['name']),
	array('type' => 'custom-text', 'name' => 'main'),
	array('type' => 'hr'),
	array('type' => 'h2', 'title' => 'Personal Information'),
	array('type' => 'custom-text', 'name' => 'personal'),
	array('type' => 'text', 'title' => 'First Name'),
	array('type' => 'text', 'title' => 'Last Name'),
	array('type' => 'text', 'title' => 'Fandom Name'),
	array('type' => 'select', 'title' => 'Name on Badge', 'values' => array('Fandom Name Large, Real Name Small')),
	array('type' => 'text', 'title' => 'Date of Birth'),
	array('type' => 'select', 'title' => 'Badge Type', 'values' => array($name_list ? $name_list[0]['name'] : '')),
	array('type' => 'hr'),
	array('type' => 'h2', 'title' => 'Contact Information'),
	array('type' => 'custom-text', 'name' => 'contact'),
	array('type' => 'text', 'title' => 'Email Address'),
	array('type' => 'text', 'title' => 'Phone Number'),
	array('type' => 'text', 'title' => 'Street Address'),
	array('type' => 'text', 'title' => 'City'),
	array('type' => 'text', 'title' => 'State or Province'),
	array('type' => 'text', 'title' => 'ZIP or Postal Code'),
	array('type' => 'text', 'title' => 'Country'),
	array('type' => 'hr'),
	array('type' => 'h2', 'title' => 'Staff Information'),
	array('type' => 'custom-questions'),
	array('type' => 'hr'),
	array('type' => 'h2', 'title' => 'Emergency Contact Information'),
	array('type' => 'custom-text', 'name' => 'ice'),
	array('type' => 'text', 'title' => 'Emergency Contact Name'),
	array('type' => 'text', 'title' => 'Emergency Contact Relationship'),
	array('type' => 'text', 'title' => 'Emergency Contact Email Address'),
	array('type' => 'text', 'title' => 'Emergency Contact Phone Number'),
));
cm_form_edit_body($form_def, array(
	array('type' => 'h1', 'title' => 'Application Submitted'),
	array('type' => 'custom-text', 'name' => 'application-submitted', 'default' =>
		'Your staff application has been submitted.'
	),
));
cm_form_edit_body($form_def, array(
	array('type' => 'h1', 'title' => 'Application Already Submitted'),
	array('type' => 'custom-text', 'name' => 'application-already-submitted', 'default' =>
		'A staff application for this person has already been submitted.<br><br>'.
		'Please <b><a href="mailto:[[contact-address]]">contact us</a></b> '.
		'if you need an update on your status or if you believe this is an error.'
	),
));
cm_form_edit_body($form_def, array(
	array('type' => 'h1', 'title' => 'Payment Complete <i>(Staff member successfully paid online.)</i>'),
	array('type' => 'custom-text', 'name' => 'payment-complete', 'default' =>
		'Your staff application has been confirmed and your payment, if required, has been accepted.<br><br>'.
		'You can <b><a href="[[review-link]]">review your order</a></b> at any time.'
	),
));
cm_form_edit_body($form_def, array(
	array('type' => 'h1', 'title' => 'Payment Refused <i>(Payment did not go through.)</i>'),
	array('type' => 'custom-text', 'name' => 'payment-refused', 'default' =>
		'PayPal has refused this transaction.<br><br>'.
		'PayPal says: [[payment-txn-msg]]<br><br>'.
		'Unfortunately, that is all we know. Please try again later.'
	),
));
cm_form_edit_body($form_def, array(
	array('type' => 'h1', 'title' => 'Payment Cancelled <i>(Staff member cancelled payment.)</i>'),
	array('type' => 'custom-text', 'name' => 'payment-cancelled', 'default' =>
		'You have cancelled your payment.'
	),
));
echo '</article>';

cm_admin_dialogs();
cm_form_edit_dialogs();
cm_admin_tail();
