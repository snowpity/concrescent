<?php

require_once __DIR__ .'/../../lib/database/mail.php';
require_once __DIR__ .'/../../lib/util/cmmail.php';
require_once __DIR__ .'/../admin.php';

cm_admin_check_permission('attendee-mail', 'attendee-mail');

$mdb = new cm_mail_db($db);
$template_ids = array(
	'attendee-paid' => 'Registration Completed',
	'attendee-retrieve' => 'Magic Link request'
);
$templates = array();

if (isset($_POST['cm-mail-action'])) {
	$attempted = true;
	$succeeded = true;
	foreach ($template_ids as $id => $name) {
		$template = cm_mail_posted_template($id);
		$success = $mdb->set_mail_template($template);
		$templates[$id] = $template;
		if (!$success) $succeeded = false;
	}
} else {
	$attempted = false;
	$succeeded = true;
	foreach ($template_ids as $id => $name) {
		$template = $mdb->get_mail_template($id);
		$templates[$id] = $template;
	}
}

cm_admin_head('Attendee Form Letters');
cm_mail_head();
cm_admin_body('Attendee Form Letters');
cm_admin_nav('attendee-mail');

echo '<article>';
	echo '<form action="mail.php" method="post" class="card">';
		echo '<div class="card-content">';
			if ($attempted) {
				cm_mail_notification($succeeded);
				echo '<hr>';
			}
			foreach ($template_ids as $id => $name) {
				cm_mail_editor($id, $name, $templates[$id]);
				echo '<hr>';
			}
			cm_mail_merge_help(array(
				'qr-data' => 'The QR code data for this attendee.',
				'qr-url' => 'A URL of a QR code for this attendee.',
				'badge-type-name' => 'The name of the badge type the attendee has chosen.',
				'real-name' => 'The attendee\'s first and last name.',
				'fandom-name' => 'The attendee\'s fandom name.',
				'display-name' => 'The name the attendee has chosen to appear on their badge.',
				'unsubscribe-link' => 'A URL to remove the attendee\'s email address from the mailing list.',
				'payment-txn-id' => 'The PayPal transaction ID.',
				'payment-txn-amt' => 'The PayPal transaction amount.',
				'review-link' => 'The URL of the page to review a completed registration.'
			));
		echo '</div>';
		echo '<div class="card-buttons">';
			cm_mail_form_submit();
		echo '</div>';
	echo '</form>';
echo '</article>';

cm_admin_dialogs();
cm_mail_dialogs();
cm_admin_tail();
