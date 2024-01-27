<?php

require_once __DIR__ .'/../lib/util/util.php';
require_once __DIR__ .'/register.php';

$onsite_only = isset($_COOKIE['onsite_only']) && $_COOKIE['onsite_only'];
$sellable_badge_types = $atdb->list_badge_types(true, true, $onsite_only);
if ($onsite_only || !$sellable_badge_types) {
	header('Location: index.php');
	exit(0);
}

$id = isset($_POST['id']) ? trim($_POST['id']) : null;
$uuid = isset($_POST['uuid']) ? trim($_POST['uuid']) : null;
if (!$id || !$uuid) {
	header('Location: index.php');
	exit(0);
}

$item = $atdb->get_attendee($id, $uuid, $name_map, $fdb);
if (!$item || $item['payment-status'] != 'Completed') {
	header('Location: index.php');
	exit(0);
}

$original_badge_type = $atdb->get_badge_type($item['badge-type-id']);
$applicable_badge_types = array($original_badge_type);
$targetable_badge_types = array();
foreach ($sellable_badge_types as $bt) {
	if ($item['date-of-birth'] && (
		($bt['min-birthdate'] && $item['date-of-birth'] < $bt['min-birthdate']) ||
		($bt['max-birthdate'] && $item['date-of-birth'] > $bt['max-birthdate'])
	)) {
		continue;
	}
	if ($bt['id'] != $item['badge-type-id'] && $bt['price'] > $item['payment-promo-price']) {
		$bt['price-diff'] = $bt['price'] - $item['payment-promo-price'];
		$applicable_badge_types[] = $bt;
		$targetable_badge_types[] = $bt;
	}
}

$sellable_addons = $atdb->list_addons(true, true, $onsite_only, $name_map);
$targetable_addons = array();
foreach ($sellable_addons as $addon) {
	if ($item['date-of-birth'] && (
		($addon['min-birthdate'] && $item['date-of-birth'] < $addon['min-birthdate']) ||
		($addon['max-birthdate'] && $item['date-of-birth'] > $addon['max-birthdate'])
	)) {
		continue;
	}
	if (!in_array($addon['id'], $item['addon-ids'])) {
		$targetable_addons[] = $addon;
	}
}

$errors = array();
if (isset($_POST['submit']) && isset($_POST['action']) && $_POST['action'] == 'checkout') {
	$item['first-name'] = trim($_POST['first-name']);
	if (!$item['first-name']) $errors['first-name'] = 'First name is required.';
	$item['last-name'] = trim($_POST['last-name']);
	if (!$item['last-name']) $errors['last-name'] = 'Last name is required.';

	$item['fandom-name'] = trim($_POST['fandom-name']);
	$item['name-on-badge'] = $item['fandom-name'] ? trim($_POST['name-on-badge']) : 'Real Name Only';
	if (!in_array($item['name-on-badge'], $atdb->names_on_badge)) {
		$errors['name-on-badge'] = 'Name on badge is required.';
	}

	$item['badge-type-id'] = isset($_POST['badge-type-id']) ? (int)$_POST['badge-type-id'] : $item['badge-type-id'];
	$item['new-badge-type'] = false;
	foreach ($applicable_badge_types as $badge_type) {
		if ($badge_type['id'] == $item['badge-type-id']) {
			$item['new-badge-type'] = $badge_type;
			if ($item['date-of-birth'] && (
				($badge_type['min-birthdate'] && $item['date-of-birth'] < $badge_type['min-birthdate']) ||
				($badge_type['max-birthdate'] && $item['date-of-birth'] > $badge_type['max-birthdate'])
			)) $errors['badge-type-id'] = 'The badge you selected is not applicable.';
		}
	}
	if (!$item['new-badge-type']) {
		$errors['badge-type-id'] = 'The badge you selected is not available.';
	}

	$item['new-addons'] = array();
	$item['new-addon-ids'] = array();
	if (!isset($item['addons'])) $item['addons'] = array();
	if (!isset($item['addon-ids'])) $item['addon-ids'] = array();
	foreach ($targetable_addons as $addon) {
		if (isset($_POST['addon-'.$addon['id']]) && $_POST['addon-'.$addon['id']]) {
			if ($item['date-of-birth'] && (
				($addon['min-birthdate'] && $item['date-of-birth'] < $addon['min-birthdate']) ||
				($addon['max-birthdate'] && $item['date-of-birth'] > $addon['max-birthdate'])
			)) {
				$errors['addon-'.$addon['id']] = 'The addon you selected is not applicable.';
			}
			if (!$atdb->addon_applies($addon, $item['badge-type-id'])) {
				$errors['addon-'.$addon['id']] = 'The addon you selected is not applicable.';
			}
			$item['addons'][] = $addon;
			$item['addon-ids'][] = $addon['id'];
			$item['new-addons'][] = $addon;
			$item['new-addon-ids'][] = $addon['id'];
		}
	}

	if (!$errors) {
		$_SESSION['payment_method'] = trim($_POST['payment-method'] ?? 'paypal');
		cm_reg_post_edit_set($item);
		cm_reg_post_edit_set_state('ready');
		header('Location: post-purchase-checkout.php');
		exit(0);
	}
}

cm_reg_head('Edit Order');
echo '<script type="text/javascript">cm_badge_type_info = ('.json_encode($applicable_badge_types).');</script>';
echo '<script type="text/javascript">cm_addon_info = ('.json_encode($targetable_addons).');</script>';
echo '<script type="text/javascript" src="post-purchase-edit.js"></script>';
cm_reg_body('Edit Order', false);
echo '<article>';
echo '<form action="post-purchase-edit.php" method="post">';

echo '<div class="card cm-reg-edit">';
	echo '<div class="card-title">Edit Order</div>';
	echo '<div class="card-content">';
		if ($errors) {
			echo '<div class="cm-error-box">';
				echo '<h2>You\'re not done yet!</h2>';
				echo '<p>Please address the issues in red and try submitting again.</p>';
			echo '</div>';
			echo '<hr>';
		}
		echo '<p>';
			echo 'You are editing the registration for <b>' . $item['display-name'] . '</b>.';
			if ($contact_address) {
				echo ' If you have any questions, feel free to ';
				echo '<b><a href="mailto:' . htmlspecialchars($contact_address) . '">contact us</a></b>.';
			}
		echo '</p>';
	echo '</div>';
echo '</div>';

echo '<div class="card cm-reg-edit">';
	echo '<div class="card-title">Change Name</div>';
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
				$value = isset($item['fandom-name']) ? htmlspecialchars($item['fandom-name']) : '';
				$error = isset($errors['fandom-name']) ? htmlspecialchars($errors['fandom-name']) : '';
				echo '<th><label for="fandom-name">Fandom Name</label></th>';
				echo '<td><input type="text" id="fandom-name" name="fandom-name" value="' . $value . '">';
				if ($error) echo '<span class="error">' . $error . '</span>'; echo '</td>';
			echo '</tr>';
			echo '<tr id="name-on-badge-row">';
				$value = isset($item['name-on-badge']) ? htmlspecialchars($item['name-on-badge']) : '';
				$error = isset($errors['name-on-badge']) ? htmlspecialchars($errors['name-on-badge']) : '';
				echo '<th><label for="name-on-badge">Name on Badge</label></th>';
				echo '<td>';
					echo '<select id="name-on-badge" name="name-on-badge">';
						foreach ($atdb->names_on_badge as $nob) {
							$hnob = htmlspecialchars($nob);
							echo '<option value="' . $hnob;
							echo ($value == $hnob) ? '" selected>' : '">';
							echo $hnob . '</option>';
						}
					echo '</select>';
					if ($error) echo '<span class="error">' . $error . '</span>';
				echo '</td>';
			echo '</tr>';
		echo '</table>';
	echo '</div>';
echo '</div>';

if ($targetable_badge_types) {
	echo '<div class="card cm-reg-edit">';
		echo '<div class="card-title">Upgrade Badge</div>';
		echo '<div class="card-content">';
			echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-form-table">';
				echo '<tr><td>';
					$value = isset($item['badge-type-id']) ? htmlspecialchars($item['badge-type-id']) : '';
					$error = isset($errors['badge-type-id']) ? htmlspecialchars($errors['badge-type-id']) : '';
					foreach ($applicable_badge_types as $bt) {
						$checked = ($bt['id'] == $value);
						$original = ($bt['id'] == $original_badge_type['id']);
						$btid = htmlspecialchars($bt['id']);
						$btname = htmlspecialchars($bt['name']);
						$btprice = htmlspecialchars(price_string($bt['price']));
						$btdesc = safe_html_string($bt['description']);
						echo '<div class="cm-reg-upgrade" id="cm-reg-upgrade-' . $btid . '">';
						echo '<p><label>';
						echo '<input type="radio" id="upgrade-' . $btid . '" name="badge-type-id" value="' . $btid . ($checked ? '" checked>' : '">');
						if ($original) {
							$price_original = htmlspecialchars(price_string($item['payment-promo-price']));
							echo 'Keep my <b>' . $btname . '</b> (' . $price_original . ') registration';
						} else {
							$price_diff = htmlspecialchars(price_string($bt['price-diff']));
							echo 'Upgrade to <b>' . $btname . '</b> (' . $btprice . ') for <b>' . $price_diff . '</b>';
						}
						echo '</label></p>';
						if ($btdesc) echo '<p class="cm-reg-upgrade-desc">' . $btdesc . '</p>';
						if ($checked && $error) echo '<p class="error">' . $error . '</p>';
						echo '</div>';
					}
				echo '</td></tr>';
			echo '</table>';
		echo '</div>';
	echo '</div>';
}

if ($targetable_addons) {
	echo '<div class="card cm-reg-edit cm-reg-addons">';
		echo '<div class="card-title">Purchase Addons</div>';
		echo '<div class="card-content">';
			echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-form-table">';
				echo '<tr><td>';
					foreach ($targetable_addons as $addon) {
						$value = isset($item['addon-ids']) && in_array($addon['id'], $item['addon-ids']);
						$error = isset($errors['addon-'.$addon['id']]) ? htmlspecialchars($errors['addon-'.$addon['id']]) : '';
						$aid = htmlspecialchars($addon['id']);
						$aname = htmlspecialchars($addon['name']);
						$aprice = htmlspecialchars(price_string($addon['price']));
						$adesc = safe_html_string($addon['description']);
						echo '<div class="cm-reg-addon" id="cm-reg-addon-' . $aid . '">';
						echo '<p><label>';
						echo '<input type="checkbox" id="addon-' . $aid . '" name="addon-' . $aid . '" value="1"' . ($value ? ' checked>' : '>');
						echo $aname . ' &mdash; ' . $aprice;
						echo '</label></p>';
						if ($adesc) echo '<p class="cm-reg-addon-desc">' . $adesc . '</p>';
						if ($error) echo '<p class="error">' . $error . '</p>';
						echo '</div>';
					}
				echo '</td></tr>';
			echo '</table>';
		echo '</div>';
	echo '</div>';
}

echo '<div class="card cm-reg-edit" id="save-changes-card">';
	echo '<div class="card-title">Save Changes</div>';
	echo '<div class="card-content">';
		echo '<p>Your changes are not complete until you click <b>Save Changes</b>.</p>';
	echo '</div>';
	echo '<div class="card-buttons">';
		echo '<input type="submit" name="submit" value="Save Changes" class="register-button">';
	echo '</div>';
echo '</div>';

if ($targetable_badge_types || $targetable_addons) {
	echo '<div class="card cm-reg-edit hidden" id="place-order-card">';
		echo '<div class="card-title">Save Changes &amp; Place Order</div>';
		echo '<div class="card-content">';
			echo '<p>Your total for selected upgrades and addons is <b class="edit-order-total">FREE</b>.</p>';
			echo '<p><b>Please select a payment method for upgrades and addons:</b></p>';
			echo '<div class="spacing">';
				echo '<div><label><input type="radio" name="payment-method" value="paypal" checked>';
				echo 'Pay with PayPal</label></div>';
			echo '</div>';
			echo '<p>Your changes and upgrades are not complete until you click <b>Save Changes &amp; Place Order</b>.</p>';
		echo '</div>';
		echo '<div class="card-buttons">';
			echo '<input type="submit" name="submit" value="Save Changes &amp; Place Order" class="register-button">';
		echo '</div>';
	echo '</div>';
}

echo '<input type="hidden" name="id" value="' . $id . '">';
echo '<input type="hidden" name="uuid" value="' . $uuid . '">';
echo '<input type="hidden" name="action" value="checkout">';
echo '</form>';
echo '</article>';
cm_reg_tail();
