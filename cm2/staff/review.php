<?php

require_once __DIR__ .'/../lib/util/util.php';
require_once __DIR__ .'/staff.php';

$gid = isset($_GET['gid']) ? trim($_GET['gid']) : null;
$tid = isset($_GET['tid']) ? trim($_GET['tid']) : null;
if (!$gid || !$tid) {
	header('Location: index.php');
	exit(0);
}
$items = $sdb->list_staff_members($gid, $tid, $name_map, $dept_map, $pos_map, $fdb);
if (!$items) {
	header('Location: index.php');
	exit(0);
}

$items_total = 0;
$cart_items = array();
$cart_items_total = 0;
foreach ($items as $item) {
	$items_total += (float)$item['payment-badge-price'];
	if (
		$item['application-status'] == 'Accepted' &&
		$item['payment-status'] != 'Completed'
	) {
		$cart_items[] = $item;
		$cart_items_total += (float)$item['payment-badge-price'];
	}
}

if ($cart_items && isset($_POST['submit'])) {
	cm_app_cart_set_state('ready', $cart_items);
	header('Location: checkout.php');
	exit(0);
}

$title = $cart_items ? 'Staff Registration Confirmation & Payment' : 'Review Order';
cm_app_head($title);
cm_app_body($title);
echo '<article>';

echo '<div class="card">';
	echo '<div class="card-title">' . htmlspecialchars($title) . '</div>';
	echo '<div class="card-content">';
		echo '<p>';
			if ($cart_items) {
				echo 'Please review your staff registration below. ';
				echo 'Your registration is not complete until you click <b>Confirm &amp; Pay</b>.';
			} else {
				$count = count($items);
				$count .= ($count == 1) ? ' item' : ' items';
				echo 'Here are the details of the <b>' . $count . '</b> you ordered ';
				echo 'on <b>' . htmlspecialchars($items[0]['payment-date']) . '</b>.';
			}
			if ($contact_address) {
				echo ' If you have any questions, feel free to ';
				echo '<b><a href="mailto:' . htmlspecialchars($contact_address) . '">contact us</a></b>.';
			}
		echo '</p>';
		echo '<div class="cm-list-table">';
			echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-cart">';
				echo '<thead>';
					echo '<tr>';
						echo '<th>Name</th>';
						echo '<th>Badge Type</th>';
						echo '<th class="td-numeric">Price</th>';
						echo '<th>Application Status</th>';
						echo '<th>Payment Status</th>';
					echo '</tr>';
				echo '</thead>';
				echo '<tbody>';
					foreach ($items as $item) {
						echo '<tr>';
							echo '<td>';
								$only_name = $item['only-name'];
								$large_name = $item['large-name'];
								$small_name = $item['small-name'];
								if ($only_name) echo '<div><b>' . htmlspecialchars($only_name) . '</b></div>';
								if ($large_name) echo '<div><b>' . htmlspecialchars($large_name) . '</b></div>';
								if ($small_name) echo '<div>' . htmlspecialchars($small_name) . '</div>';
							echo '</td>';
							echo '<td>';
								$badge_type_name = $item['badge-type-name'];
								if ($badge_type_name) echo '<div>' . htmlspecialchars($badge_type_name) . '</div>';
							echo '</td>';
							echo '<td class="td-numeric">';
								$badge_price = (float)$item['payment-badge-price'];
								echo '<div>' . htmlspecialchars(price_string($badge_price)) . '</div>';
							echo '</td>';
							echo '<td>';
								$application_status = $item['application-status'];
								if ($application_status) echo '<div>' . cm_status_label($application_status) . '</div>';
							echo '</td>';
							echo '<td>';
								$payment_status = $item['payment-status'];
								if ($payment_status) echo '<div>' . cm_status_label($payment_status) . '</div>';
							echo '</td>';
						echo '</tr>';
					}
				echo '</tbody>';
				echo '<tfoot>';
					if ($cart_items) {
						echo '<tr>';
							echo '<th>To Be Paid:</th>';
							echo '<th></th>';
							echo '<th class="td-numeric">' . htmlspecialchars(price_string($cart_items_total)) . '</th>';
							echo '<th></th>';
							echo '<th></th>';
						echo '</tr>';
					}
					echo '<tr>';
						echo '<th>Total:</th>';
						echo '<th></th>';
						echo '<th class="td-numeric">' . htmlspecialchars(price_string($items_total)) . '</th>';
						echo '<th></th>';
						echo '<th></th>';
					echo '</tr>';
				echo '</tfoot>';
			echo '</table>';
		echo '</div>';
	echo '</div>';
	if ($cart_items) {
		echo '<div class="card-buttons">';
			$url = 'review.php?gid=' . $gid . '&tid=' . $tid;
			echo '<form action="' . htmlspecialchars($url) . '" method="post">';
				echo '<input type="submit" name="submit" value="Confirm &amp; Pay" class="register-button">';
			echo '</form>';
		echo '</div>';
	}
echo '</div>';

echo '</article>';
cm_app_tail();
