<?php

require_once __DIR__ .'/../lib/util/util.php';
require_once __DIR__ .'/register.php';

global $onsite_only;
global $override_code;
global $cm_config;
global $name_map;

global $twig;

$sellable_badge_types = $atdb->list_badge_types(true, true, $onsite_only, $override_code);
if (!$sellable_badge_types) {
	$futureBadges = $atdb->list_badge_types(true, true, $onsite_only, $override_code, true);
	$startDates = array_map(static fn(array $badge): string => ($badge['start-date'] ?? ''), $futureBadges);
	sort($startDates, SORT_STRING);

	$datetime = null;
	if ($startDates[0] ?? false) {
		$datetime = new DateTimeImmutable($startDates[0]);
	}
	cm_reg_closed($datetime);
}

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
			checkout_registration(trim($_POST['payment-method'] ?? 'paypal'), $errors);
			break;
	}
}

if (!cm_reg_cart_count()) {
	cm_reg_head('Shopping Cart');
	cm_reg_body('Shopping Cart');

    $template = $twig->createTemplate(<<<HEREDOC
        <article>
            <div class="card">
                <div class="card-title">Shopping Cart</div>
                <div class="card-content">
                <p>
                Your shopping cart is empty. 
                To get started, click <b>Add a Badge</b>.
                </p>
              </div>
                <div class="card-buttons">
                    <a href="edit.php" role="button" class="button register-button">
                    Add a Badge
                    </a>
                </div>
            </div>
        </article>
        HEREDOC
    );
    echo $template->render();

	cm_reg_tail();
	exit(0);
}


/**
 * Calculate total price
 */
$badge_price_total = 0;
$promo_price_total = 0;
$salesTaxSubTotal = 0;
$totalWithSalesTax = 0;
$salesTax = ($cm_config['payment']['sales_tax'] ?? 0);

foreach ($_SESSION['cart'] as $i => $item) {
    $badge_price_total += (float)$item['payment-badge-price'];
    $promo_price_total += (float)$item['payment-promo-price'];
    if ($item['sales-tax'] == 1) {
        $salesTaxSubTotal += $item['payment-promo-price'] * $salesTax;
    }
    foreach ($item['addons'] ?? [] as $addon) {
        $badge_price_total += (float)$addon['price'];
        $promo_price_total += (float)$addon['price'];
        if ($addon['sales-tax'] == 1) {
            $salesTaxSubTotal += $addon['price'] * $salesTax;
        }
    }
}

$totalWithSalesTax = $promo_price_total + $salesTaxSubTotal;

/**
 * Check if all items are payable on site
 */
$all_badge_types = $atdb->list_badge_types();
$all_addons = $atdb->list_addons(false, false, false, $name_map);

$badge_type_payable_onsite = [];
$addon_payable_onsite = [];
foreach ($all_badge_types as $bt) {
    $badge_type_payable_onsite[$bt['id']] = $bt['payable-onsite'];
}
foreach ($all_addons as $addon) {
    $addon_payable_onsite[$addon['id']] = $addon['payable-onsite'];
}

$allPayableOnsite = true;

foreach ($_SESSION['cart'] as $item) {
    $badge_type_id = (int)$item['badge-type-id'];
    if (!$badge_type_payable_onsite[$badge_type_id]) {
        $allPayableOnsite = false;
        break;
    }
    foreach ($item['addons'] ?? [] as $addon) {
        if (!$addon_payable_onsite[$addon['id']]) {
            $allPayableOnsite = false;
            break;
        }
    }
}
/** */

echo $twig->render('pages/register/cart.twig', [
    'count' => cm_reg_cart_count(true),
    'errors' => $errors,
    'items' => $_SESSION['cart'],
    'onsite_only' => $onsite_only,
    'override_code' => $override_code,
    'badge_price_total' => $badge_price_total,
    'promo_price_total' => $promo_price_total,
    'totalWithSalesTax' => $totalWithSalesTax,
    'salesTaxSubTotal' => $salesTaxSubTotal,
    'previouslyEnteredPromoCode' => $_POST['code'] ?? '',
    'allPayableOnsite' => $allPayableOnsite,
    'name_map' => $name_map,
]);
