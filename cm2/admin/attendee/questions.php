<?php

require_once __DIR__ .'/../../config/config.php';
require_once __DIR__ .'/../../lib/database/attendee.php';
require_once __DIR__ .'/../../lib/database/forms.php';
require_once __DIR__ .'/../../lib/util/util.php';
require_once __DIR__ .'/../../lib/util/cmforms.php';
require_once __DIR__ .'/../admin.php';

cm_admin_check_permission('attendee-questions', 'attendee-questions');

$atdb = new cm_attendee_db($db);
$name_list = $atdb->list_badge_type_names();

$form_def = array(
	'ajax-url' => get_site_url(false) . '/admin/attendee/questions.php',
	'context' => 'attendee',
	'subcontext' => $name_list
);

$fdb = new cm_forms_db($db, $form_def['context']);
cm_form_edit_process_requests($fdb);

cm_admin_head('Attendee Questions');
cm_form_edit_head($form_def);
cm_admin_body('Attendee Questions');
cm_admin_nav('attendee-questions');

echo '<article>';
cm_form_edit_body($form_def, array(
	array('type' => 'h1', 'title' => 'Register for ' . $cm_config['event']['name']),
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
	array('type' => 'h2', 'title' => 'Choose Your Addons'),
	array('type' => 'custom-text', 'name' => 'choose-addons'),
	array('type' => 'p', 'title' => 'Question provided by system'),
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
	array('type' => 'h2', 'title' => 'Additional Information'),
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
	array('type' => 'h1', 'title' => 'Payment Complete <i>(Attendee successfully paid online.)</i>'),
	array('type' => 'custom-text', 'name' => 'payment-complete', 'default' =>
		'Your payment has been accepted.<br><br>'.
		'You can <b><a href="[[review-link]]">review your order</a></b> at any time.'
	),
));
cm_form_edit_body($form_def, array(
	array('type' => 'h1', 'title' => 'Registration Complete <i>(Attendee opted to pay on-site.)</i>'),
	array('type' => 'custom-text', 'name' => 'registration-complete', 'default' =>
		'Your registration has been submitted. You will need to pay at the door.<br><br>'.
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
	array('type' => 'h1', 'title' => 'Payment Cancelled <i>(Attendee cancelled payment.)</i>'),
	array('type' => 'custom-text', 'name' => 'payment-cancelled', 'default' =>
		'You have cancelled your payment.'
	),
));
cm_form_edit_body($form_def, array(
	array('type' => 'h1', 'title' => 'Could Not Complete Registration <i>(Attendee has been blacklisted.)</i>'),
	array('type' => 'custom-text', 'name' => 'blacklisted', 'default' =>
		'We\'re sorry, there was an issue with your registration '.
		'and your registration could not be completed.<br><br>'.
		'If you think this is an error, please '.
		'<b><a href="mailto:[[contact-address]]">contact us</a></b>.'
	),
));
echo '</article>';

cm_admin_dialogs();
cm_form_edit_dialogs();
cm_admin_tail();
