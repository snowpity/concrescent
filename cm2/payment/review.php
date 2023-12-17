<?php

require_once __DIR__ .'/../lib/util/util.php';
require_once __DIR__ .'/payment.php';

$uid = isset($_GET['uid']) ? trim($_GET['uid']) : null;
if (!$uid) {
	header('Location: index.php');
	exit(0);
}
$item = $pdb->get_payment(null, $uid);
if (!$item) {
	header('Location: index.php');
	exit(0);
}

if ($item['payment-status'] != 'Completed' && isset($_POST['submit'])) {
	cm_payment_cart_set_state('ready', $item);
	header('Location: checkout.php');
	exit(0);
}

$title = ($item['payment-status'] == 'Completed') ? 'Review Order' : 'Make a Payment';
cm_payment_head($title);
cm_payment_body($title);
echo '<article>';

echo '<div class="card">';
	echo '<div class="card-title">' . htmlspecialchars($title) . '</div>';
	echo '<div class="card-content">';
		echo '<p>';
			if ($item['payment-status'] == 'Completed') {
				echo 'Here are the details of the payment you made on ';
				echo '<b>' . htmlspecialchars($item['payment-date']) . '</b>.';
			} else {
				echo 'Please review your payment below. Your payment ';
				echo 'is not complete until you click <b>Confirm &amp; Pay</b>.';
			}
			$template_name = 'payment-requested-' . $item['mail-template'];
			$contact_address = $mdb->get_contact_address($template_name);
			if ($contact_address) {
				echo ' If you have any questions, feel free to ';
				echo '<b><a href="mailto:' . htmlspecialchars($contact_address) . '">contact us</a></b>.';
			}
		echo '</p>';
		echo '<div class="cm-list-table">';
			echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-cart">';
				echo '<thead>';
					echo '<tr>';
						echo '<th>Item</th>';
						echo '<th class="td-numeric">Price</th>';
						echo '<th>Payment Status</th>';
					echo '</tr>';
				echo '</thead>';
				echo '<tbody>';
					echo '<tr>';
						echo '<td>';
							echo '<div><b>' . htmlspecialchars($item['real-name']) . '</b></div>';
							echo '<div>' . htmlspecialchars($item['payment-name']) . '</div>';
						echo '</td>';
						echo '<td class="td-numeric">';
							$price = (float)$item['payment-price'];
							echo '<div>' . htmlspecialchars(price_string($price)) . '</div>';
						echo '</td>';
						echo '<td>';
							$payment_status = $item['payment-status'];
							echo '<div>' . cm_status_label($payment_status) . '</div>';
						echo '</td>';
					echo '</tr>';
				echo '</tbody>';
			echo '</table>';
		echo '</div>';
	echo '</div>';
	if ($item['payment-status'] != 'Completed') {
		echo '<div class="card-buttons">';
			$url = 'review.php?uid=' . $uid;
			echo '<form action="' . htmlspecialchars($url) . '" method="post">';
				echo '<input type="submit" name="submit" value="Confirm &amp; Pay" class="register-button">';
			echo '</form>';
		echo '</div>';
	}
echo '</div>';

echo '</article>';
cm_payment_tail();
