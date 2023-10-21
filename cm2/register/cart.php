<?php

require_once dirname(__FILE__).'/../lib/util/util.php';
require_once dirname(__FILE__).'/register.php';


$all_badge_types = $atdb->list_badge_types();
$sellable_badge_types = $atdb->list_badge_types(true, true, $onsite_only, $override_code);
if (!$sellable_badge_types) cm_reg_closed();

$all_addons = $atdb->list_addons(false, false, false, $name_map);


function checkout_registration($payment_method, &$errors) {
	$errors = cm_reg_cart_verify_availability($payment_method);
	if ($errors) {
		$errors['checkout'] = (
			'There were some issues with your registration. '.
			'Please address the issues in red and try submitting again.'
		);
	} else {
		$_SESSION['payment_method'] = $payment_method;
		cm_reg_cart_set_state('ready');
		header('Location: checkout.php');
		exit(0);
	}
}

$errors = array();
if (isset($_POST['action'])) {
	switch ($_POST['action']) {
		case 'remove':
			cm_reg_cart_reset_promo_code();
			cm_reg_cart_remove((int)$_POST['index']);
			break;
		case 'removeall':
			cm_reg_cart_destroy();
			break;
		case 'redeem':
			$errors['code'] = cm_reg_apply_promo_code(trim($_POST['code']));

			break;
		case 'checkout':
			checkout_registration(trim($_POST['payment-method']), $errors);
			break;
	}
}

if (!cm_reg_cart_count()) {
	cm_reg_head('Shopping Cart');
	cm_reg_body('Shopping Cart');
	echo '<article>';
		echo '<div class="card">';
			echo '<div class="card-title">Shopping Cart</div>';
			echo '<div class="card-content">';
				echo '<p>';
					echo 'Your shopping cart is empty. ';
					echo 'To get started, click <b>Add a Badge</b>.';
				echo '</p>';
			echo '</div>';
			echo '<div class="card-buttons">';
				echo '<a href="edit.php" role="button" class="button register-button">';
					echo 'Add a Badge';
				echo '</a>';
			echo '</div>';
		echo '</div>';
	echo '</article>';
	cm_reg_tail();
	exit(0);
}

cm_reg_head('Shopping Cart');
cm_reg_body('Shopping Cart');
echo '<article>';

echo '<div class="card">';
	echo '<div class="card-title">Shopping Cart</div>';
	echo '<div class="card-content">';
		if (isset($errors['checkout'])) {
			echo '<p class="cm-error-box">';
			echo htmlspecialchars($errors['checkout']);
			echo '</p>';
		}
		echo '<p>';
			$count = cm_reg_cart_count(true);
			$count .= ($count == 1) ? ' item' : ' items';
			echo 'Your shopping cart has <b>' . $count . '</b>. ';
			echo 'Your registration is not complete until your click <b>Place Order</b>.';
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
						echo '<th class="td-actions">Actions</th>';
					echo '</tr>';
				echo '</thead>';
				echo '<tbody>';
					for ($i = 0, $n = cm_reg_cart_count(); $i < $n; $i++) {
						$item = cm_reg_cart_get($i);
						echo '<tr>';
							echo '<td>';
								$real_name = trim(trim($item['first-name']) . ' ' . trim($item['last-name']));
								$fandom_name = trim($item['fandom-name']);
								$name_on_badge = $fandom_name ? trim($item['name-on-badge']) : 'Real Name Only';
								switch ($name_on_badge) {
									case 'Fandom Name Large, Real Name Small':
										echo '<div><b>' . htmlspecialchars($fandom_name) . '</b></div>';
										echo '<div>' . htmlspecialchars($real_name) . '</div>';
										break;
									case 'Real Name Large, Fandom Name Small':
										echo '<div><b>' . htmlspecialchars($real_name) . '</b></div>';
										echo '<div>' . htmlspecialchars($fandom_name) . '</div>';
										break;
									case 'Fandom Name Only':
										echo '<div><b>' . htmlspecialchars($fandom_name) . '</b></div>';
										break;
									default:
										echo '<div><b>' . htmlspecialchars($real_name) . '</b></div>';
										break;
								}
								$promo_code = trim($item['payment-promo-code'] ?? '');
								if ($promo_code) {
									echo '<div><b>Promo Code:</b> ' . htmlspecialchars($promo_code) . '</div>';
								}
							echo '</td>';
							echo '<td>';
								$badge_type_id = (int)$item['badge-type-id'];
								$badge_type_name = isset($name_map[$badge_type_id]) ? $name_map[$badge_type_id] : $badge_type_id;
								echo '<div>' . htmlspecialchars($badge_type_name) . '</div>';
								if (isset($errors[$i])) {
									echo '<div class="error">' . htmlspecialchars($errors[$i]) . '</div>';
								}
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
							echo '<td class="td-actions">';
								echo '<a href="edit.php?index=' . $i . '" role="button" class="button edit-button">Edit</a>';
								echo '<form action="cart.php" method="post">';
									echo '<input type="hidden" name="action" value="remove">';
									echo '<input type="hidden" name="index" value="' . $i . '">';
									echo '<input type="submit" name="submit" value="Remove">';
								echo '</form>';
							echo '</td>';
						echo '</tr>';
						if (isset($item['addons']) && $item['addons']) {
							foreach ($item['addons'] as $addon) {
								echo '<tr>';
									echo '<td>';
										echo '<div class="cm-cart-addon-name">' . htmlspecialchars($addon['name']) . '</div>';
										if (isset($errors[$i.'a'.$addon['id']])) {
											echo '<div class="cm-cart-addon-error error">' . htmlspecialchars($errors[$i.'a'.$addon['id']]) . '</div>';
										}
									echo '</td>';
									echo '<td><div>Addon</div></td>';
									echo '<td class="td-numeric"><div>' . htmlspecialchars(price_string($addon['price'])) . '</div></td>';
									echo '<td class="td-actions"></td>';
								echo '</tr>';
								$badge_price_total += (float)$addon['price'];
								$promo_price_total += (float)$addon['price'];
							}
						}
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
						echo '<th class="td-actions">';
							echo '<form action="cart.php" method="post">';
								echo '<input type="hidden" name="action" value="removeall">';
								echo '<input type="submit" name="submit" value="Remove All">';
							echo '</form>';
						echo '</th>';
					echo '</tr>';
				echo '</tfoot>';
			echo '</table>';
		echo '</div>';
	echo '</div>';
	echo '<div class="card-buttons">';
		echo '<a href="edit.php' . ($override_code != '' ? "?override_code=$override_code" : '' ) .'" role="button" class="button register-button">';
			echo 'Add Another Badge';
		echo '</a>';
	echo '</div>';
echo '</div>';

echo '<form action="cart.php" method="post" class="card">';
	echo '<div class="card-title">Redeem Promo Code</div>';
	echo '<div class="card-content">';
		echo '<p>';
			echo 'Enter Promo Code:';
			echo '&nbsp;&nbsp;&nbsp;&nbsp;';
			if (isset($errors['code'])) {
				echo '<input type="text" name="code" value="' . htmlspecialchars($_POST['code']) . '">';
				echo '&nbsp;&nbsp;&nbsp;&nbsp;';
				echo '<span class="error">' . htmlspecialchars($errors['code']) . '</span>';
			} else {
				echo '<input type="text" name="code">';
			}
		echo '</p>';
		echo '<p>';
			echo 'Only one promo code can be used at a time. ';
			echo 'Also, changing the contents of your shopping cart ';
			echo 'in any way will remove the promo code; you will ';
			echo 'then need to enter the promo code again.';
		echo '</p>';
	echo '</div>';
	echo '<div class="card-buttons">';
		echo '<input type="hidden" name="action" value="redeem">';
		echo '<input type="submit" name="submit" value="Redeem Code" class="register-button">';
	echo '</div>';
if ($override_code != '') {
	echo '<input type="hidden" name="override_code" value="'. $override_code . '" />';
}
echo '</form>';

echo '<form action="cart.php" method="post" class="card">';
	echo '<div class="card-title">Place Order</div>';
	echo '<div class="card-content">';
		echo '<p><b>Please select a payment method:</b></p>';
		echo '<div class="spacing">';
			if ($onsite_only) {
				echo '<div><label><input type="radio" name="payment-method" value="cash" checked>';
				echo 'Pay with cash at the event</label></div>';
			} else {
				echo '<div><label><input type="radio" name="payment-method" value="paypal" checked>';
				echo 'Pay with PayPal</label></div>';
				$badge_type_payable_onsite = array();
				$addon_payable_onsite = array();
				foreach ($all_badge_types as $bt) {
					$badge_type_payable_onsite[$bt['id']] = $bt['payable-onsite'];
				}
				foreach ($all_addons as $addon) {
					$addon_payable_onsite[$addon['id']] = $addon['payable-onsite'];
				}
				$all_payable_onsite = true;
				for ($i = 0, $n = cm_reg_cart_count(); $i < $n; $i++) {
					$item = cm_reg_cart_get($i);
					$badge_type_id = (int)$item['badge-type-id'];
					if (!$badge_type_payable_onsite[$badge_type_id]) {
						$all_payable_onsite = false;
					}
					if (isset($item['addons']) && $item['addons']) {
						foreach ($item['addons'] as $addon) {
							if (!$addon_payable_onsite[$addon['id']]) {
								$all_payable_onsite = false;
							}
						}
					}
				}
				if ($all_payable_onsite) {
					echo '<div><label><input type="radio" name="payment-method" value="cash">';
					echo 'Pay with cash at the event</label></div>';
				}
			}
		echo '</div>';
		echo '<p>Your registration is not complete until you click <b>Place Order</b>.</p>';
	echo '</div>';
	echo '<div class="card-buttons">';
		echo '<input type="hidden" name="action" value="checkout">';
		echo '<input type="submit" name="submit" value="Place Order" class="register-button">';
	echo '</div>';
if ($override_code != '') {
	echo '<input type="hidden" name="override_code" value="'. $override_code . '" />';
}
echo '</form>';

echo '</article>';
cm_reg_tail();
