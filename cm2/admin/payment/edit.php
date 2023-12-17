<?php

require_once __DIR__ .'/../../lib/database/payment.php';
require_once __DIR__ .'/../../lib/database/mail.php';
require_once __DIR__ .'/../../lib/util/util.php';
require_once __DIR__ .'/../admin.php';

cm_admin_check_permission('payments', array('||', 'payments-view', 'payments-edit'));
$can_edit = $adb->user_has_permission($admin_user, 'payments-edit') && !isset($_GET['ro']);

$pdb = new cm_payment_db($db);
$mdb = new cm_mail_db($db);

$new = !isset($_GET['id']);
$id = $new ? -1 : (int)$_GET['id'];
$item = $new ? array() : $pdb->get_payment($id);
$submitted = $can_edit && isset($_POST['submit']);
$changed = false;

if ($submitted) {
	/* Basic Information */
	$item['requested-by'] = trim($_POST['requested-by']);
	$item['first-name'] = trim($_POST['first-name']);
	$item['last-name'] = trim($_POST['last-name']);
	$item['email-address'] = trim($_POST['email-address']);
	$item['mail-template'] = isset($_POST['mail-template']) ? trim($_POST['mail-template']) : '';
	$item['payment-name'] = trim($_POST['payment-name']);
	$item['payment-description'] = trim($_POST['payment-description']);
	$item['payment-price'] = (float)$_POST['payment-price'];

	/* Payment Information */
	if (
		$new
		|| (        $item['payment-status'     ] !=        $_POST['payment-status'     ] )
		|| (        $item['payment-type'       ] !=        $_POST['payment-type'       ] )
		|| (        $item['payment-txn-id'     ] !=        $_POST['payment-txn-id'     ] )
		|| ( (float)$item['payment-txn-amt'    ] != (float)$_POST['payment-txn-amt'    ] )
		|| (        $item['payment-details'    ] !=        $_POST['payment-details'    ] )
	) {
		$item['payment-status'] = trim($_POST['payment-status']);
		$item['payment-type'] = trim($_POST['payment-type']);
		$item['payment-txn-id'] = trim($_POST['payment-txn-id']);
		$item['payment-txn-amt'] = (float)$_POST['payment-txn-amt'];
		$item['payment-details'] = $_POST['payment-details'];
		$item['payment-date'] = $db->now();
	}

	/* Write Changes */
	if ($new) {
		$id = $pdb->create_payment($item);
		$new = ($id === false);
		$changed = ($id !== false);
	} else {
		$changed = $pdb->update_payment($item);
	}
	if ($changed) {
		$item = $pdb->get_payment($id);
		if (isset($_POST['resend-email']) && $_POST['resend-email']) {
			$template_key = ($item['payment-status'] == 'Completed') ? 'completed' : 'requested';
			$template_name = 'payment-' . $template_key . '-' . $item['mail-template'];
			$template = $mdb->get_mail_template($template_name);
			$mdb->send_mail($item['email-address'], $template, $item);
		}
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

cm_admin_head($new ? 'Add Payment Request' : 'Edit Payment Request');
echo '<script type="text/javascript" src="edit.js"></script>';
cm_admin_body($new ? 'Add Payment Request' : 'Edit Payment Request');
cm_admin_nav('payments');

echo '<article>';
	if ($can_edit) {
		$url = $new ? 'edit.php' : ('edit.php?id=' . $id);
		echo '<form action="' . $url . '" method="post" class="card cm-reg-edit">';
	} else {
		echo '<div class="card cm-reg-edit">';
	}
		echo '<div class="card-content">';
			if ($can_edit && $submitted) {
				if ($changed) {
					echo '<p class="cm-success-box">Changes saved.</p>';
				} else {
					echo '<p class="cm-error-box">Save failed. Please try again.</p>';
				}
				echo '<hr>';
			}
			echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-form-table">';

				echo '<tr><td colspan="2"><h2>Payment Request Information</h2></td></tr>';

				echo '<tr>';
					echo '<th><label for="first-name">First Name</label></th>';
					$value = isset($item['first-name']) ? htmlspecialchars($item['first-name']) : '';
					if ($can_edit) {
						echo '<td><input type="text" id="first-name" name="first-name" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="last-name">Last Name</label></th>';
					$value = isset($item['last-name']) ? htmlspecialchars($item['last-name']) : '';
					if ($can_edit) {
						echo '<td><input type="text" id="last-name" name="last-name" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="email-address">Email Address</label></th>';
					$value = isset($item['email-address']) ? htmlspecialchars($item['email-address']) : '';
					if ($can_edit) {
						echo '<td><input type="email" id="email-address" name="email-address" value="' . $value . '"></td>';
					} else {
						echo '<td><a href="mailto:' . $value . '">' . $value . '</a></td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="payment-name">Payment For</label></th>';
					$value = isset($item['payment-name']) ? htmlspecialchars($item['payment-name']) : '';
					if ($can_edit) {
						echo '<td><input type="text" id="payment-name" name="payment-name" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="payment-description">Description</label></th>';
					if ($can_edit) {
						$value = isset($item['payment-description']) ? htmlspecialchars($item['payment-description']) : '';
						echo '<td><textarea id="payment-description" name="payment-description">' . $value . '</textarea></td>';
					} else {
						$value = isset($item['payment-description']) ? paragraph_string($item['payment-description']) : '';
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="payment-price">Requested Amount</label></th>';
					if ($can_edit) {
						$value = isset($item['payment-price']) ? htmlspecialchars($item['payment-price']) : '';
						echo '<td><input type="number" id="payment-price" name="payment-price" value="' . $value . '" min="0" step="0.01"></td>';
					} else {
						$value = isset($item['payment-price']) ? htmlspecialchars(price_string($item['payment-price'])) : '';
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="mail-template">Form Letter</label></th>';
					$value = isset($item['mail-template']) ? htmlspecialchars($item['mail-template']) : '';
					if ($can_edit) {
						echo '<td>';
							echo '<select id="mail-template" name="mail-template">';
								foreach ($template_names as $template_name) {
									$name = htmlspecialchars($template_name);
									echo '<option value="' . $name;
									echo ($value == $name) ? '" selected>' : '">';
									echo $name . '</option>';
								}
							echo '</select>';
						echo '</td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="requested-by">Requested By</label></th>';
					$value = isset($item['requested-by']) ? htmlspecialchars($item['requested-by']) : '';
					if ($can_edit) {
						echo '<td><input type="text" id="requested-by" name="requested-by" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr><td colspan="2" class="hr"><hr></td></tr>';
				echo '<tr><td colspan="2"><h2>Payment Status Information</h2></td></tr>';

				echo '<tr>';
					echo '<th><label for="payment-status">Payment Status</label></th>';
					$value = isset($item['payment-status']) ? htmlspecialchars($item['payment-status']) : '';
					if ($can_edit) {
						echo '<td>';
							echo '<select id="payment-status" name="payment-status">';
								foreach ($pdb->payment_statuses as $ps) {
									$hps = htmlspecialchars($ps);
									echo '<option value="' . $hps;
									echo ($value == $hps) ? '" selected>' : '">';
									echo $hps . '</option>';
								}
							echo '</select>';
						echo '</td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="payment-type">Payment Type</label></th>';
					$value = isset($item['payment-type']) ? htmlspecialchars($item['payment-type']) : '';
					if ($can_edit) {
						echo '<td><input type="text" id="payment-type" name="payment-type" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="payment-txn-id">Payment Transaction ID</label></th>';
					$value = isset($item['payment-txn-id']) ? htmlspecialchars($item['payment-txn-id']) : '';
					if ($can_edit) {
						echo '<td><input type="text" id="payment-txn-id" name="payment-txn-id" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="payment-txn-amt">Payment Transaction Amount</label></th>';
					if ($can_edit) {
						$value = isset($item['payment-txn-amt']) ? htmlspecialchars($item['payment-txn-amt']) : '';
						echo '<td><input type="number" id="payment-txn-amt" name="payment-txn-amt" value="' . $value . '" min="0" step="0.01"></td>';
					} else {
						$value = isset($item['payment-txn-amt']) ? htmlspecialchars(price_string($item['payment-txn-amt'])) : '';
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				$value = isset($item['payment-date']) ? htmlspecialchars($item['payment-date']) : '';
				if ($value) {
					echo '<tr>';
						echo '<th><label>Payment Date</label></th>';
						echo '<td>' . $value . '</td>';
					echo '</tr>';
				}

				echo '<tr>';
					echo '<th><label for="payment-details">Payment Details</label></th>';
					if ($can_edit) {
						$value = isset($item['payment-details']) ? htmlspecialchars($item['payment-details']) : '';
						echo '<td><textarea id="payment-details" name="payment-details">' . $value . '</textarea></td>';
					} else {
						$value = isset($item['payment-details']) ? paragraph_string($item['payment-details']) : '';
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				$value = isset($item['review-link']) ? htmlspecialchars($item['review-link']) : '';
				if ($value) {
					echo '<tr>';
						echo '<th><label>Review Order Link</label></th>';
						echo '<td><a href="' . $value . '" target="_blank">' . $value . '</a></td>';
					echo '</tr>';
				}

				if ($can_edit) {
					echo '<tr>';
						echo '<th>&nbsp;</th>';
						echo '<td><label><input type="checkbox" name="resend-email" value="1">';
						echo ($new ? 'Send' : 'Resend') . ' Payment Status Email';
						echo '</label></td>';
					echo '</tr>';
				}

				if (!$new) {

					echo '<tr><td colspan="2" class="hr"><hr></td></tr>';
					echo '<tr><td colspan="2"><h2>Record Information</h2></td></tr>';

					$value = isset($item['id-string']) ? htmlspecialchars($item['id-string']) : '';
					if ($value) {
						echo '<tr>';
							echo '<th><label>ID Number</label></th>';
							echo '<td>' . $value . '</td>';
						echo '</tr>';
					}

					$value = isset($item['uuid']) ? htmlspecialchars($item['uuid']) : '';
					if ($value) {
						echo '<tr>';
							echo '<th><label>UUID</label></th>';
							echo '<td><tt>' . $value . '</tt></td>';
						echo '</tr>';
					}

					$value = isset($item['date-created']) ? htmlspecialchars($item['date-created']) : '';
					if ($value) {
						echo '<tr>';
							echo '<th><label>Date Created</label></th>';
							echo '<td>' . $value . '</td>';
						echo '</tr>';
					}

					$value = isset($item['date-modified']) ? htmlspecialchars($item['date-modified']) : '';
					if ($value) {
						echo '<tr>';
							echo '<th><label>Date Modified</label></th>';
							echo '<td>' . $value . '</td>';
						echo '</tr>';
					}

				}

			echo '</table>';
		echo '</div>';
		if ($can_edit) {
			echo '<div class="card-buttons">';
				echo '<input type="submit" name="submit" value="Save Changes">';
			echo '</div>';
		}
	if ($can_edit) {
		echo '</form>';
	} else {
		echo '</div>';
	}
echo '</article>';

cm_admin_dialogs();
cm_admin_tail();
