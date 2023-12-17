<?php

require_once __DIR__ .'/../../lib/database/payment.php';
require_once __DIR__ .'/../../lib/database/mail.php';
require_once __DIR__ .'/../admin.php';

cm_admin_check_permission('payment-request', 'payment-request');

$pdb = new cm_payment_db($db);
$mdb = new cm_mail_db($db);

$item = array();
$errors = array();

if (isset($_POST['submit'])) {
	$item['requested-by'] = $admin_user['username'];

	$item['first-name'] = trim($_POST['first-name']);
	if (!$item['first-name']) $errors['first-name'] = 'First name is required.';
	$item['last-name'] = trim($_POST['last-name']);
	if (!$item['last-name']) $errors['last-name'] = 'Last name is required.';
	$item['email-address'] = trim($_POST['email-address']);
	if (!$item['email-address']) $errors['email-address'] = 'Email address is required.';
	$item['mail-template'] = isset($_POST['mail-template']) ? trim($_POST['mail-template']) : '';
	if (!$item['mail-template']) $errors['mail-template'] = 'Form letter is required.';

	$item['payment-name'] = trim($_POST['payment-name']);
	if (!$item['payment-name']) $errors['payment-name'] = 'Payment For is required.';
	$item['payment-description'] = trim($_POST['payment-description']);
	$item['payment-price'] = (float)trim($_POST['payment-price']);
	if ($item['payment-price'] <= 0) $errors['payment-price'] = 'Requested amount is required.';

	$item['payment-status'] = 'Incomplete';

	if (!$errors) {
		$id = $pdb->create_payment($item);
		$item = $pdb->get_payment($id);

		$template = $mdb->get_mail_template('payment-requested-'.$item['mail-template']);
		$mdb->send_mail($item['email-address'], $template, $item);

		cm_admin_head('Request Payment');
		cm_admin_body('Request Payment');
		cm_admin_nav('payment-request');

		echo '<article>';
			echo '<div class="card">';
				echo '<div class="card-content">';
					echo '<p>The payment request has been sent.</p>';
				echo '</div>';
				echo '<div class="card-buttons">';
					echo '<a href="request.php" role="button" class="button register-button">';
						echo 'Request Another Payment';
					echo '</a>';
				echo '</div>';
			echo '</div>';
		echo '</article>';

		cm_admin_dialogs();
		cm_admin_tail();
		exit(0);
	}
}

$template_names = array();
$templates = $mdb->list_mail_templates();
foreach ($templates as $template) {
	$template_prefix = substr($template['name'], 0, 18);
	if (
		$template_prefix == 'payment-requested-' ||
		$template_prefix == 'payment-completed-'
	) {
		$template_name = substr($template['name'], 18);
		$template_key = substr($template['name'], 8, 9);
		if (isset($template_names[$template_name])) {
			$template_names[$template_name][$template_key] = $template;
		} else {
			$template_names[$template_name] = array($template_key => $template);
		}
	}
}
$templates = $template_names;
$template_names = array_keys($templates);
usort($template_names, 'strnatcasecmp');

cm_admin_head('Request Payment');
echo '<script type="text/javascript">cm_payment_request_templates = ('.json_encode($templates).');</script>';
echo '<script type="text/javascript" src="request.js"></script>';
cm_admin_body('Request Payment');
cm_admin_nav('payment-request');

echo '<article>';
	echo '<form action="request.php" method="post" class="card cm-reg-edit">';
		echo '<div class="card-content">';
			echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-form-table">';

				echo '<tr>';
					$value = isset($item['first-name']) ? htmlspecialchars($item['first-name']) : '';
					$error = isset($errors['first-name']) ? htmlspecialchars($errors['first-name']) : '';
					echo '<th><label for="first-name">First Name</label></th>';
					echo '<td><input type="text" id="first-name" name="first-name" value="' . $value . '">';
					if ($error) echo '<span class="error">' . $error . '</span>'; echo '</td>';
				echo '</tr>';

				echo '<tr>';
					$value = isset($item['last-name']) ? htmlspecialchars($item['last-name']) : '';
					$error = isset($errors['last-name']) ? htmlspecialchars($errors['last-name']) : '';
					echo '<th><label for="last-name">Last Name</label></th>';
					echo '<td><input type="text" id="last-name" name="last-name" value="' . $value . '">';
					if ($error) echo '<span class="error">' . $error . '</span>'; echo '</td>';
				echo '</tr>';

				echo '<tr>';
					$value = isset($item['email-address']) ? htmlspecialchars($item['email-address']) : '';
					$error = isset($errors['email-address']) ? htmlspecialchars($errors['email-address']) : '';
					echo '<th><label for="email-address">Email Address</label></th>';
					echo '<td><input type="email" id="email-address" name="email-address" value="' . $value . '">';
					if ($error) echo '<span class="error">' . $error . '</span>'; echo '</td>';
				echo '</tr>';

				echo '<tr>';
					$value = isset($item['payment-name']) ? htmlspecialchars($item['payment-name']) : '';
					$error = isset($errors['payment-name']) ? htmlspecialchars($errors['payment-name']) : '';
					echo '<th><label for="payment-name">Payment For</label></th>';
					echo '<td><input type="text" id="payment-name" name="payment-name" value="' . $value . '">';
					if ($error) echo '<span class="error">' . $error . '</span>'; echo '</td>';
				echo '</tr>';

				echo '<tr>';
					$value = isset($item['payment-description']) ? htmlspecialchars($item['payment-description']) : '';
					$error = isset($errors['payment-description']) ? htmlspecialchars($errors['payment-description']) : '';
					echo '<th><label for="payment-description">Description</label></th>';
					echo '<td><textarea id="payment-description" name="payment-description">' . $value . '</textarea>';
					if ($error) echo '<span class="error">' . $error . '</span>'; echo '</td>';
				echo '</tr>';

				echo '<tr>';
					$value = isset($item['payment-price']) ? htmlspecialchars($item['payment-price']) : '';
					$error = isset($errors['payment-price']) ? htmlspecialchars($errors['payment-price']) : '';
					echo '<th><label for="payment-price">Requested Amount</label></th>';
					echo '<td><input type="number" id="payment-price" name="payment-price" value="' . $value . '" min="0" step="0.01">';
					if ($error) echo '<span class="error">' . $error . '</span>'; echo '</td>';
				echo '</tr>';

				echo '<tr>';
					$value = isset($item['mail-template']) ? htmlspecialchars($item['mail-template']) : '';
					$error = isset($errors['mail-template']) ? htmlspecialchars($errors['mail-template']) : '';
					echo '<th><label for="mail-template">Form Letter</label></th>';
					echo '<td>';
						echo '<select id="mail-template" name="mail-template">';
							foreach ($template_names as $template_name) {
								$name = htmlspecialchars($template_name);
								echo '<option value="' . $name;
								echo ($value == $name) ? '" selected>' : '">';
								echo $name . '</option>';
							}
						echo '</select>';
						if ($error) echo '<span class="error">' . $error . '</span>';
					echo '</td>';
				echo '</tr>';

				echo '<tr class="cm-mail-editor">';
					echo '<th></th>';
					echo '<td>';
						echo '<div class="cm-mail-preview">';
							echo '<iframe></iframe>';
						echo '</div>';
					echo '</td>';
				echo '</tr>';

			echo '</table>';
		echo '</div>';
		echo '<div class="card-buttons">';
			echo '<input type="submit" name="submit" value="Send Payment Request">';
		echo '</div>';
	echo '</form>';
echo '</article>';

cm_admin_dialogs();
cm_admin_tail();
