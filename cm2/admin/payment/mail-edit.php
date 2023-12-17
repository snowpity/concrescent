<?php

require_once __DIR__ .'/../../lib/database/mail.php';
require_once __DIR__ .'/../../lib/util/cmmail.php';
require_once __DIR__ .'/../admin.php';

cm_admin_check_permission('payment-mail', 'payment-mail');

$mdb = new cm_mail_db($db);

if (isset($_POST['name']) && $_POST['name']) {
	$template_name = trim($_POST['name']);
} else if (isset($_GET['name']) && $_GET['name']) {
	$template_name = trim($_GET['name']);
} else {
	$template_name = null;
}

if (isset($_POST['cm-mail-action'])) {
	$attempted = true;
	$requested_template = cm_mail_posted_template('payment-requested');
	$completed_template = cm_mail_posted_template('payment-completed');
	if ($template_name) {
		$requested_template['name'] = 'payment-requested-' . $template_name;
		$requested_success = $mdb->set_mail_template($requested_template);
		$completed_template['name'] = 'payment-completed-' . $template_name;
		$completed_success = $mdb->set_mail_template($completed_template);
		$succeeded = $requested_success && $completed_success;
	} else {
		$succeeded = false;
	}
} else {
	$attempted = false;
	if ($template_name) {
		$requested_template = $mdb->get_mail_template('payment-requested-' . $template_name);
		$completed_template = $mdb->get_mail_template('payment-completed-' . $template_name);
	} else {
		$requested_template = array(
			'contact-address' => 'business@' . $_SERVER['SERVER_NAME'],
			'from' => 'business@' . $_SERVER['SERVER_NAME'],
			'bcc' => 'business@' . $_SERVER['SERVER_NAME'],
			'subject' => 'Payment requested for [[payment-name]] for [[event-name]]',
			'type' => 'Simple HTML',
			'body' => (
				"Greetings,\n\n".
				"A payment of <b>[[payment-price-string]]</b> has been requested ".
				"from you for <b>[[payment-name]]</b> for <b>[[event-name]]</b>.\n\n".
				"Please make the payment at the following URL:\n\n".
				"<a href=\"[[review-link]]\">[[review-link]]</a>\n\n".
				"Thank you,\n[[event-name]] Registration"
			)
		);
		$completed_template = array(
			'contact-address' => 'business@' . $_SERVER['SERVER_NAME'],
			'from' => 'business@' . $_SERVER['SERVER_NAME'],
			'bcc' => 'business@' . $_SERVER['SERVER_NAME'],
			'subject' => 'Payment completed for [[payment-name]] for [[event-name]]',
			'type' => 'Simple HTML',
			'body' => (
				"Greetings,\n\n".
				"The payment of <b>[[payment-price-string]]</b> for ".
				"<b>[[payment-name]]</b> for <b>[[event-name]]</b> ".
				"has been completed.\n\n".
				"You may review the payment at the following URL:\n\n".
				"<a href=\"[[review-link]]\">[[review-link]]</a>\n\n".
				"Thank you,\n[[event-name]] Registration"
			)
		);
	}
	$succeeded = true;
}

cm_admin_head('Payment Request Form Letters');
cm_mail_head();
cm_admin_body('Payment Request Form Letters');
cm_admin_nav('payment-mail');

echo '<article>';
	$url = 'mail-edit.php';
	if ($template_name) $url .= '?name=' . urlencode($template_name);
	echo '<form action="' . htmlspecialchars($url) . '" method="post" class="card">';
		echo '<div class="card-content">';
			if ($attempted) {
				cm_mail_notification($succeeded);
				echo '<hr>';
			}
			echo '<p>';
				echo '<b>Form Letter Name:</b>';
				echo '&nbsp;&nbsp;';
				echo '<input type="text" name="name" id="name"';
				if ($template_name) {
					echo ' value="' . htmlspecialchars($template_name) . '"';
					echo ' readonly';
				}
				echo '>';
				if ($attempted && !$template_name) {
					echo '&nbsp;&nbsp;';
					echo '<span class="error">A form letter name is required.</span>';
				}
			echo '</p>';
			echo '<hr>';
			cm_mail_editor('payment-requested', 'Payment Requested', $requested_template);
			cm_mail_editor('payment-completed', 'Payment Completed', $completed_template);
			cm_mail_merge_help(array(
				'real-name' => 'The person\'s first and last name.',
				'payment-name' => 'The short description of the requested payment.',
				'payment-description' => 'The long description of the requested payment.',
				'payment-price-string' => 'The requested amount to be paid.',
				'payment-txn-id' => 'The PayPal transaction ID.',
				'payment-txn-amt' => 'The PayPal transaction amount.',
				'review-link' => 'The URL of the page to complete or review a payment.'
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
