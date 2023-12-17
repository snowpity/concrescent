<?php

require_once __DIR__ .'/../../config/config.php';
require_once __DIR__ .'/../../lib/database/mail.php';
require_once __DIR__ .'/../../lib/util/cmmail.php';
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

cm_admin_check_permission('application-mail-'.$ctx_lc, 'application-mail-'.$ctx_lc);

$mdb = new cm_mail_db($db);
$template_ids = array(
	('application-submitted-'.$ctx_lc) => 'Application Submitted',
	('application-accepted-'.$ctx_lc) => 'Application Accepted',
	('application-paid-'.$ctx_lc) => 'Confirmed & Paid',
	('application-waitlisted-'.$ctx_lc) => 'Application Waitlisted',
	('application-rejected-'.$ctx_lc) => 'Application Rejected'
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

cm_admin_head($ctx_name . ' Form Letters');
cm_mail_head();
cm_admin_body($ctx_name . ' Form Letters');
cm_admin_nav('application-mail-' . $ctx_lc);

echo '<article>';
	echo '<form action="mail.php?c=' . $ctx_lc . '" method="post" class="card">';
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
				'qr-data' => 'The QR code data for this applicant.',
				'qr-url' => 'A URL of a QR code for this applicant.',
				'real-name' => 'The applicant\'s first and last name.',
				'fandom-name' => 'The applicant\'s fandom name.',
				'display-name' => 'The name the applicant has chosen to appear on their badge.',
				'unsubscribe-link' => 'A URL to remove the applicant\'s email address from the mailing list.',
				'badge-type-name' => 'The name of the badge type the applicant applied for.',
				'business-name' => $ctx_info['business_name_text'],
				'application-name' => $ctx_info['application_name_text'],
				'payment-txn-id' => 'The PayPal transaction ID.',
				'payment-txn-amt' => 'The PayPal transaction amount.',
				'review-link' => 'The URL of the page to review a completed application.'
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
