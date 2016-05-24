<?php

require_once dirname(__FILE__).'/../lib/util/util.php';
require_once dirname(__FILE__).'/register.php';

$gid = isset($_GET['gid']) ? trim($_GET['gid']) : null;
$tid = isset($_GET['tid']) ? trim($_GET['tid']) : null;
if (!$gid || !$tid) {
	header('Location: index.php');
	exit(0);
}
$items = $atdb->list_attendees(null, null, $gid, $tid, $name_map, $fdb);
if (!$items) {
	header('Location: index.php');
	exit(0);
}

cm_reg_head('Review Order');
cm_reg_body('Review Order');
echo '<article>';

echo '<div class="card">';
	echo '<div class="card-title">Review Order</div>';
	echo '<div class="card-content">';
		echo '<p>';
			$count = count($items);
			$count .= ($count == 1) ? ' item' : ' items';
			echo 'Here are the details of the <b>' . $count . '</b> you ordered ';
			echo 'on <b>' . htmlspecialchars($items[0]['payment-date']) . '</b>.';
			if ($contact_address) {
				echo ' If you have any questions, feel free to ';
				echo '<b><a href="mailto:' . htmlspecialchars($contact_address) . '">contact us</a></b>.';
			}
		echo '</p>';
		echo '<div class="cm-list-table">';
			echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-cart">';
				$badge_price_total = 0;
				$promo_price_total = 0;
				echo '<thead>';
					echo '<tr>';
						echo '<th>Name</th>';
						echo '<th>Badge Type</th>';
						echo '<th class="td-numeric">Price</th>';
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
								$promo_code = $item['payment-promo-code'];
								if ($only_name) echo '<div><b>' . htmlspecialchars($only_name) . '</b></div>';
								if ($large_name) echo '<div><b>' . htmlspecialchars($large_name) . '</b></div>';
								if ($small_name) echo '<div>' . htmlspecialchars($small_name) . '</div>';
								if ($promo_code) echo '<div><b>Promo Code:</b> ' . htmlspecialchars($promo_code) . '</div>';
							echo '</td>';
							echo '<td>';
								$badge_type_name = $item['badge-type-name'];
								if ($badge_type_name) echo '<div>' . htmlspecialchars($badge_type_name) . '</div>';
							echo '</td>';
							echo '<td class="td-numeric">';
								$badge_price = (float)$item['payment-badge-price'];
								$promo_price = (float)$item['payment-promo-price'];
								if ($badge_price != $promo_price) {
									echo '<div><s>' . htmlspecialchars(price_string($badge_price)) . '</s></div>';
									echo '<div><b>' . htmlspecialchars(price_string($promo_price)) . '</b></div>';
								} else {
									echo '<div>' . htmlspecialchars(price_string($badge_price)) . '</div>';
								}
								$badge_price_total += $badge_price;
								$promo_price_total += $promo_price;
							echo '</td>';
							echo '<td>';
								$payment_status = $item['payment-status'];
								if ($payment_status) echo '<div>' . cm_status_label($payment_status) . '</div>';
							echo '</td>';
						echo '</tr>';
					}
				echo '</tbody>';
				echo '<tfoot>';
					echo '<tr>';
						echo '<th>Total:</th>';
						echo '<th></th>';
						echo '<th class="td-numeric">';
							if ($badge_price_total != $promo_price_total) {
								echo '<div><s>' . htmlspecialchars(price_string($badge_price_total)) . '</s></div>';
								echo '<div><b>' . htmlspecialchars(price_string($promo_price_total)) . '</b></div>';
							} else {
								echo '<div>' . htmlspecialchars(price_string($badge_price_total)) . '</div>';
							}
						echo '</th>';
						echo '<th></th>';
					echo '</tr>';
				echo '</tfoot>';
			echo '</table>';
		echo '</div>';
	echo '</div>';
echo '</div>';

echo '</article>';
cm_reg_tail();