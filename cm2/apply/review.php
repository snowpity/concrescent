<?php

require_once __DIR__ .'/../lib/database/attendee.php';
require_once __DIR__ .'/../lib/util/util.php';
require_once __DIR__ .'/apply.php';

$gid = isset($_GET['gid']) ? trim($_GET['gid']) : null;
$tid = isset($_GET['tid']) ? trim($_GET['tid']) : null;
if (!$gid || !$tid) {
	header('Location: index.php?c=' . $ctx_lc);
	exit(0);
}

$applications = $apdb->list_applications($gid, $tid, true, $name_map, $fdb);
if (!$applications) {
	header('Location: index.php?c=' . $ctx_lc);
	exit(0);
}

$atdb = new cm_attendee_db($db);
$items = array();
$items_total = 0;
$items_total_sales_tax = 0;
$cart_items = array();
$cart_items_total = 0;
$cart_items_total_sales_tax = 0;
$use_permit = false;
$require_permit = false;
$require_contract = false;

foreach ($applications as $application) {
	$application_items = $apdb->generate_invoice($application, $atdb);
	if (strlen($application['payment-badge-price'] ?? '')) {
		$intended_price = $application['payment-badge-price'];
		$calculated_price = array_sum(array_column_simple($application_items, 'price'));
		if ($calculated_price != $intended_price) {
			$application_items = $apdb->generate_invoice($application, null);
			$application_items[0]['price'] = $intended_price;
			$application_items[0]['price-string'] = price_string($intended_price);
			for ($i = 1, $n = count($application_items); $i < $n; $i++) {
				$application_items[$i]['price'] = 0;
				$application_items[$i]['price-string'] = 'INCLUDED';
			}
		}
	}
	$add_to_cart = (
		$application['application-status'] == 'Accepted' &&
		$application['payment-status'] != 'Completed'
	);

    global $cm_config;
    $salesTax = ($cm_config['payment']['sales_tax'] ?? 0);

	foreach ($application_items as $item) {
		$item['application-status'] = $application['application-status'];
		$item['payment-status'] = $application['payment-status'];
		$items[] = $item;
		$salesTaxPart = $item['sales-tax'] ? $item['price'] * $salesTax : 0;
        $items_total += $item['price'] + $salesTaxPart;
        $items_total_sales_tax += $salesTaxPart;
		if ($add_to_cart) {
			$cart_items[] = $item;
            $salesTaxPart = $item['sales-tax'] ? $item['price'] * $salesTax : 0;
            $cart_items_total += $item['price'] + $salesTaxPart;
            $cart_items_total_sales_tax += $salesTaxPart;
		}
	}
	$badge = $apdb->get_badge_type($application['badge-type-id']);
	if ($badge) {
		if ($badge['use-permit']) $use_permit = true;
		if ($badge['require-permit']) $require_permit = true;
		if ($badge['require-contract']) $require_contract = true;
	}
}

$permit_number = $applications[0]['permit-number'];
$permit_number_error = null;

if ($cart_items && !$require_contract && isset($_POST['submit'])) {
	if ($use_permit || $require_permit) {
		$permit_number = trim($_POST['permit-number']);
		if ($require_permit && !$permit_number) {
			$permit_number_error = 'This application type requires a permit number.';
		} else {
			foreach ($applications as $application) {
				$apdb->update_permit_number($application['id'], $permit_number);
			}
			cm_app_cart_set_state('ready', $cart_items);
			header('Location: checkout.php?c=' . $ctx_lc);
			exit(0);
		}
	} else {
		cm_app_cart_set_state('ready', $cart_items);
		header('Location: checkout.php?c=' . $ctx_lc);
		exit(0);
	}
}

$title = $cart_items ? ($ctx_name . ' Registration Confirmation & Payment') : 'Review Order';
cm_app_head($title);
cm_app_body($title);
echo '<article>';

if ($cart_items && !$require_contract) {
	$url = 'review.php?c=' . $ctx_lc . '&gid=' . $gid . '&tid=' . $tid;
	echo '<form action="' . htmlspecialchars($url) . '" method="post" class="card">';
} else {
	echo '<div class="card">';
}
	echo '<div class="card-title">' . htmlspecialchars($title) . '</div>';
	echo '<div class="card-content">';

		echo '<p>';
			if ($cart_items) {
				echo 'Please review your ' . $ctx_name_lc . ' registration below.';
				if (!$require_contract) {
					echo ' Your registration is not complete until you click <b>Confirm &amp; Pay</b>.';
				}
			} else {
				$count = count($items);
				$count .= ($count == 1) ? ' item' : ' items';
				echo 'Here are the details of the <b>' . $count . '</b> you ordered ';
				echo 'on <b>' . htmlspecialchars($applications[0]['payment-date']) . '</b>.';
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
						echo '<th>Item</th>';
						echo '<th class="td-numeric">Price</th>';
						echo '<th>Application Status</th>';
						echo '<th>Payment Status</th>';
					echo '</tr>';
				echo '</thead>';
				echo '<tbody>';
					foreach ($items as $item) {
						echo '<tr>';
							echo '<td>';
								echo '<div><b>' . htmlspecialchars($item['name']) . '</b></div>';
								echo '<div>' . htmlspecialchars($item['details']) . '</div>';
							echo '</td>';
							echo '<td class="td-numeric">';
								echo '<div>' . htmlspecialchars($item['price-string']) . '</div>';
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
							echo '<th class="td-numeric">' . htmlspecialchars(price_string($cart_items_total));
                            if ($cart_items_total_sales_tax != 0) echo ' incl. sales tax ' . htmlspecialchars(price_string($cart_items_total_sales_tax));
                            echo '</th>';
							echo '<th></th>';
							echo '<th></th>';
						echo '</tr>';
					}
					echo '<tr>';
						echo '<th>Total:</th>';
						echo '<th class="td-numeric">' . htmlspecialchars(price_string($items_total));
                        if ($items_total_sales_tax != 0) echo ' incl. sales tax ' . htmlspecialchars(price_string($items_total_sales_tax));
                        echo '</th>';
						echo '<th></th>';
						echo '<th></th>';
					echo '</tr>';
				echo '</tfoot>';
			echo '</table>';
		echo '</div>';

		if ($cart_items) {
			if ($require_contract) {
				echo '<p><b>';
					echo 'Registration for this application type cannot be completed online. ';
					echo 'Please contact us to finalize your application.';
				echo '</b></p>';
			} else if ($use_permit || $require_permit) {
				echo '<p>';
					if ($require_permit) {
						echo 'This application type requires a seller\'s permit. Please enter the permit number below.';
					} else {
						echo 'This application type does not require a seller\'s permit. However, if you have one, you may enter the permit number below.';
					}
				echo '</p>';
				echo '<p>';
					echo '<b>Permit Number</b>';
					echo '&nbsp;&nbsp;&nbsp;&nbsp;';
					echo '<input type="text" id="permit-number" name="permit-number" value="' . htmlspecialchars($permit_number) . '">';
					if ($permit_number_error) {
						echo '&nbsp;&nbsp;&nbsp;&nbsp;';
						echo '<span class="error">' . htmlspecialchars($permit_number_error) . '</span>';
					}
				echo '</p>';
			}
		}

	echo '</div>';
	if ($cart_items && !$require_contract) {
		echo '<div class="card-buttons">';
			echo '<input type="submit" name="submit" value="Confirm &amp; Pay" class="register-button">';
		echo '</div>';
	}
if ($cart_items && !$require_contract) {
	echo '</form>';
} else {
	echo '</div>';
}

echo '</article>';
cm_app_tail();
