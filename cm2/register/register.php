<?php

use JetBrains\PhpStorm\NoReturn;

session_name('PHPSESSID_CMREG');
session_start();

require_once __DIR__ .'/../config/config.php';
require_once __DIR__ .'/../lib/database/database.php';
require_once __DIR__ .'/../lib/database/attendee.php';
require_once __DIR__ .'/../lib/database/forms.php';
require_once __DIR__ .'/../lib/database/mail.php';
require_once __DIR__ .'/../lib/util/res.php';
require_once __DIR__ .'/../lib/util/util.php';

global $cm_config;

$event_name = $cm_config['event']['name'];

$onsite_only = isset($_COOKIE['onsite_only']) && $_COOKIE['onsite_only'];
$override_code = $_GET['override_code'] ?? ($_POST['override_code'] ?? '');

$db = new cm_db();

$atdb = new cm_attendee_db($db);
$name_map = $atdb->get_badge_type_name_map();

$fdb = new cm_forms_db($db, 'attendee');
$questions = $fdb->list_questions();

$mdb = new cm_mail_db($db);
$contact_address = $mdb->get_contact_address('attendee-paid');

function cm_reg_item_update_from_post(&$item, $post)
{
	global $atdb,$onsite_only, $override_code,$fdb,$questions,$name_map;
	$errors = array();
	$item['first-name'] = trim($post['first-name']);
	if (!$item['first-name']) $errors['first-name'] = 'First name is required.';
	$item['last-name'] = trim($post['last-name']);
	if (!$item['last-name']) $errors['last-name'] = 'Last name is required.';

	$item['fandom-name'] = trim($post['fandom-name']);
	$item['name-on-badge'] = $item['fandom-name'] ? trim($post['name-on-badge']) : 'Real Name Only';
	if (!in_array($item['name-on-badge'], $atdb->names_on_badge)) {
		$errors['name-on-badge'] = 'Name on badge is required.';
	}

	$item['date-of-birth'] = parse_date(trim($post['date-of-birth']));
	if (!$item['date-of-birth']) $errors['date-of-birth'] = 'Date of birth is required.';
	$item['badge-type-id'] = (int)$post['badge-type-id'];
	$found_badge_type = false;
	$badge_types = $atdb->list_badge_types(true, true, $onsite_only, $override_code);
	foreach ($badge_types as $badge_type) {
		if ($badge_type['id'] == $item['badge-type-id']) {
			$found_badge_type = $badge_type;
			if ($item['date-of-birth'] && (
				($badge_type['min-birthdate'] && $item['date-of-birth'] < $badge_type['min-birthdate']) ||
				($badge_type['max-birthdate'] && $item['date-of-birth'] > $badge_type['max-birthdate'])
			)) $errors['badge-type-id'] = 'The badge you selected is not applicable.';
		}
	}
	if (!$found_badge_type) {
		$errors['badge-type-id'] = 'The badge you selected is not available.';
	}

	$item['addons'] = array();
	$item['addon-ids'] = array();
	foreach ($atdb->list_addons(true, true, $onsite_only, $name_map) as $addon) {
		if (isset($post['addon-'.$addon['id']]) && $post['addon-'.$addon['id']]) {
			if ($item['date-of-birth'] && (
				($addon['min-birthdate'] && $item['date-of-birth'] < $addon['min-birthdate']) ||
				($addon['max-birthdate'] && $item['date-of-birth'] > $addon['max-birthdate'])
			)) {
				$errors['addon-'.$addon['id']] = 'The addon you selected is not applicable.';
			}
			if ($found_badge_type && !$atdb->addon_applies($addon, $found_badge_type['id'])) {
				$errors['addon-'.$addon['id']] = 'The addon you selected is not applicable.';
			}
			$addon['payment-status'] = 'Incomplete';
			$item['addons'][] = $addon;
			$item['addon-ids'][] = $addon['id'];
		}
	}

	$item['email-address'] = trim($post['email-address']);
	if (!$item['email-address']) $errors['email-address'] = 'Email address is required.';
	$item['subscribed'] = isset($post['subscribed']) && $post['subscribed'];
	$item['phone-number'] = trim($post['phone-number']);
	if (!$item['phone-number']) $errors['phone-number'] = 'Phone number is required.';
	else if (strlen($item['phone-number']) < 7) $errors['phone-number'] = 'Phone number is too short.';

	$item['address-1'] = trim($post['address-1']);
	if (!$item['address-1']) $errors['address-1'] = 'Address is required.';
	$item['address-2'] = trim($post['address-2']);
	$item['city'] = trim($post['city']);
	if (!$item['city']) $errors['city'] = 'City is required.';
	$item['state'] = trim($post['state']);
	$item['zip-code'] = trim($post['zip-code']);
	$item['country'] = trim($post['country']);

	$item['ice-name'] = trim($post['ice-name']);
	$item['ice-relationship'] = trim($post['ice-relationship']);
	$item['ice-email-address'] = trim($post['ice-email-address']);
	$item['ice-phone-number'] = trim($post['ice-phone-number']);

	$item['payment-status'] = 'Incomplete';
	$item['payment-badge-price'] = $found_badge_type ? $found_badge_type['price'] : 0;
	$item['sales-tax'] = $found_badge_type ? $found_badge_type['sales-tax'] : 0;
	$item['payment-promo-code'] = null;
	$item['payment-promo-price'] = $found_badge_type ? $found_badge_type['price'] : 0;

	//Apply any promo code
	//TODO: Actually track promo code usage in the session!
	$promo_count = 0;
	if(isset($post['payment-promo-code']) && $post['payment-promo-code'] != '')
	$promo_code = $atdb->get_promo_code($post['payment-promo-code'], true, true, $name_map);
	if(isset($promo_code))
	if(!$atdb->apply_promo_code_to_item($promo_code, $item, $promo_count))
	{
		$errors['code'] = 'The promo code given could not be applied to this item.';
	}

	//If they're editing their badge...
	$item['editing-badge'] = (int)($post['editing-badge'] ?? 0);
	$item['uuid'] = trim($post['uuid'] ?? '');
	if($item['editing-badge'] > 0 )
	{
		//First, find them in the attendees table
		$existingBadge = $atdb->get_attendee($item['editing-badge'], $item['uuid'] );
		if($existingBadge)
		{
			//Fill in some data
			$item['payment-group-uuid'] = $existingBadge['payment-group-uuid'];
			$item['editing-prior-id'] = $existingBadge['badge-type-id'];
			$item['editing-prior-payment-status'] = $existingBadge['payment-status'];
			foreach ( $atdb->list_addon_purchases($item['editing-badge'], $name_map) as $addon) {
				$item['editing-prior-addon-ids'][] = $addon['addon-id'];
				//Update the addons entry that corrosponds
				foreach($item['addons'] as $addonidx => $nowaddon)
				{
					if($nowaddon['id'] == $addon['addon-id'])
					$item['addons'][$addonidx]['payment-status']  = $addon['payment-status'] ;
				}
			}

			//check if the price is different
			//TODO: Confirm correct handling of badges not yet paid
			if($found_badge_type && $existingBadge['payment-status'] == 'Completed')
			{
				//Hack: If they are completed and not changing the type, ensure it's zero
				$item['payment-promo-price'] =$item['editing-prior-id'] == $item['badge-type-id'] ? 0 : max(0,$item['payment-promo-price'] - $existingBadge['payment-badge-price']);

			}
		}
		else {
			die("Said you were editing a badge, but couldn't find it?");
		}

	}

	$item['form-answers'] = array();
	foreach ($questions as $question) {
		if ($question['active'] && $fdb->question_is_visible($question, $item['badge-type-id'])) {
			$answer = cm_form_posted_answer($question['question-id'], $question['type'],$post);
			$item['form-answers'][$question['question-id']] = $answer;
			if ($fdb->question_is_required($question, $item['badge-type-id']) && !$answer) {
				$errors['form-answer-'.$question['question-id']] = 'This question is required.';
			}
		}
	}
	return $errors;
}

function cm_reg_cart_count($include_addons = false): int {
	if (!isset($_SESSION['cart'])) $_SESSION['cart'] = array();
	$count = count($_SESSION['cart']);
	if ($include_addons) {
		foreach ($_SESSION['cart'] as $item) {
			if (isset($item['addons'])) {
				$count += count($item['addons']);
			}
		}
	}
	return $count;
}

function cm_reg_cart_add($item) {
	if (!isset($_SESSION['cart'])) $_SESSION['cart'] = array();
	$_SESSION['cart'][] = $item;
}

function cm_reg_cart_get($index) {
	if (!isset($_SESSION['cart'])) $_SESSION['cart'] = array();
	if (!isset($_SESSION['cart'][$index])) return null;
	return $_SESSION['cart'][$index];
}

function cm_reg_cart_set($index, $item) {
	if (!isset($_SESSION['cart'])) $_SESSION['cart'] = array();
	$_SESSION['cart'][$index] = $item;
}

function cm_reg_cart_remove($index) {
	if (!isset($_SESSION['cart'])) $_SESSION['cart'] = array();
	if (!isset($_SESSION['cart'][$index])) return;
	array_splice($_SESSION['cart'], $index, 1);
}

function cm_reg_apply_promo_code($code) {
	if (!$code) return;
	global $atdb,$onsite_only, $override_code,$fdb,$questions,$name_map;
	$promo_code = $atdb->get_promo_code($code, true, true, $name_map);
	if (!$promo_code) {
		return 'This is not a valid promo code.';
	}
	$items = array();
	for ($i = 0, $n = cm_reg_cart_count(); $i < $n; $i++) {
		$item = cm_reg_cart_get($i);
		$item['index'] = $i;
		$item['payment-promo-code'] = $item['payment-promo-code'] ?? null;
		$item['payment-promo-price'] = $item['payment-promo-price'] ?? $item['payment-badge-price'];
		$items[] = $item;
	}
	usort($items, function($a, $b) {
		$av = (float)$a['payment-badge-price'];
		$bv = (float)$b['payment-badge-price'];
		if ($bv < $av) return -1;
		if ($bv > $av) return +1;
		return 0;
	});
	if (!$atdb->apply_promo_code_to_items($promo_code, $items)) {
		return 'This promo code does not apply to any items in your cart.';

	}
	foreach ($items as $item) {
		cm_reg_cart_set($item['index'], $item);
	}
}

function cm_reg_cart_reset_promo_code() {
	if (!isset($_SESSION['cart'])) $_SESSION['cart'] = array();
	foreach ($_SESSION['cart'] as $index => $item) {
		$_SESSION['cart'][$index]['payment-promo-code'] = null;
		$_SESSION['cart'][$index]['payment-promo-price'] = $item['payment-badge-price'];
	}
}

function cm_reg_cart_verify_availability($payment_method)
{
	global $atdb,$onsite_only, $override_code,$name_map;
	$badge_map = array();
	$addon_map = array();
	$errors = array();
	foreach ($atdb->list_badge_types(true, true, $onsite_only, $override_code) as $bt) {
		$badge_map[$bt['id']] = $bt;
	}
	foreach ($atdb->list_addons(true, true, $onsite_only, $name_map) as $addon) {
		$addon_map[$addon['id']] = $addon;
	}
	for ($i = 0, $n = cm_reg_cart_count(); $i < $n; $i++) {
		$item = cm_reg_cart_get($i);
		$badge_type_id = $item['badge-type-id'];
		if (!isset($badge_map[$badge_type_id])) {
			$errors[$i] = 'This badge type is no longer available.';
		} else {
			$badge_type = $badge_map[$badge_type_id];
			if ($item['date-of-birth'] && (
				($badge_type['min-birthdate'] && $item['date-of-birth'] < $badge_type['min-birthdate']) ||
				($badge_type['max-birthdate'] && $item['date-of-birth'] > $badge_type['max-birthdate'])
			)) {
				$errors[$i] = 'This badge type is no longer applicable.';
			} else if ($payment_method == 'cash' && !$badge_type['payable-onsite']) {
				$errors[$i] = 'This badge type cannot be paid for with cash.';
			}
		}
		if (isset($item['addons']) && $item['addons']) {
			foreach ($item['addons'] as $addon) {
				$addon_id = $addon['id'];
				if (!isset($addon_map[$addon_id])) {
					$errors[$i.'a'.$addon_id] = 'This addon is no longer available.';
				} else {
					$addon = $addon_map[$addon_id];
					if ($item['date-of-birth'] && (
						($addon['min-birthdate'] && $item['date-of-birth'] < $addon['min-birthdate']) ||
						($addon['max-birthdate'] && $item['date-of-birth'] > $addon['max-birthdate'])
					)) {
						$errors[$i.'a'.$addon_id] = 'This addon is no longer applicable.';
					} else if (!$atdb->addon_applies($addon, $badge_type_id)) {
						$errors[$i.'a'.$addon_id] = 'This addon is no longer applicable.';
					} else if ($payment_method == 'cash' && !$addon['payable-onsite']) {
						$errors[$i.'a'.$addon_id] = 'This addon cannot be paid for with cash.';
					}
				}
			}
		}
	}
	return  $errors;
}

function cm_reg_cart_total() {
    global $cm_config;

	if (!isset($_SESSION['cart'])) $_SESSION['cart'] = array();
    $salesTax = ($cm_config['payment']['sales_tax'] ?? 0);
	$total = 0;
	foreach ($_SESSION['cart'] as $item) {
        $itemPart = (float)$item['payment-promo-price'];
        $salesTaxPart = $item['sales-tax'] ? $itemPart * $salesTax : 0;
		$total += $itemPart + $salesTaxPart;
		if (isset($item['addons']) && $item['addons']) {
			foreach ($item['addons'] as $addon) {
				if($addon['payment-status'] === 'Incomplete') {
                    $itemPart = (float)$addon['price'];
                    $salesTaxPart = $addon['sales-tax'] ? $itemPart * $salesTax : 0;
                    $total += $itemPart + $salesTaxPart;
                }
			}
		}
	}
	return $total;
}

function cm_reg_cart_set_state($state) {
	if (!isset($_SESSION['cart'])) $_SESSION['cart'] = array();
	$_SESSION['cart_hash'] = md5(serialize($_SESSION['cart']));
	$_SESSION['cart_state'] = $state;
}

function cm_reg_cart_check_state($expected_state) {
	if (!isset($_SESSION['cart'])) return false;
	if (!isset($_SESSION['cart_hash'])) return false;
	if (!isset($_SESSION['cart_state'])) return false;
	$expected_hash = md5(serialize($_SESSION['cart']));
	if ($_SESSION['cart_hash'] != $expected_hash) return false;
	if ($_SESSION['cart_state'] != $expected_state) return false;
	return true;
}

function cm_reg_cart_destroy($close_session = true) {
	unset($_SESSION['cart']);
	unset($_SESSION['cart_hash']);
	unset($_SESSION['cart_state']);
	if($close_session)
		session_destroy();
}

function cm_reg_post_edit_get() {
	return $_SESSION['post_edit'] ?? null;
}

function cm_reg_post_edit_set($item): void {
	$_SESSION['post_edit'] = $item;
}

class PostEditSubTotal {
    /** @var float Gross total */
    public float $total = 0;
    public float $tax = 0;
}

function cm_reg_post_edit_total(): PostEditSubTotal {
    global $cm_config;
    $salesTax = ($cm_config['payment']['sales_tax'] ?? 0);

	$total = new PostEditSubTotal();

	if (isset($_SESSION['post_edit'])) {
		$item = $_SESSION['post_edit'];
		if (isset($item['new-badge-type'])) {
			$bt = $item['new-badge-type'];
			if (isset($bt['price-diff'])) {
                $taxPart = $bt['sales-tax'] ? (float)$bt['price-diff'] * $salesTax : 0;
                $total->total += (float)$bt['price-diff'] + $taxPart;
                $total->tax += $taxPart;
			}
		}
		if (isset($item['new-addons'])) {
			foreach ($item['new-addons'] as $addon) {
                $taxPart = $addon['sales-tax'] ? (float)$addon['price'] * $salesTax : 0;
				$total->total += (float)$addon['price'] + $taxPart;
                $total->tax += $taxPart;
			}
		}
	}
	return $total;
}

function cm_reg_post_edit_set_state(string $state): void {
	if (!isset($_SESSION['post_edit'])) $_SESSION['post_edit'] = array();
	$_SESSION['post_edit_hash'] = md5(serialize($_SESSION['post_edit']));
	$_SESSION['post_edit_state'] = $state;
}

function cm_reg_post_edit_check_state(string $expected_state): bool {
	if (!isset($_SESSION['post_edit'])) return false;
	if (!isset($_SESSION['post_edit_hash'])) return false;
	if (!isset($_SESSION['post_edit_state'])) return false;
	$expected_hash = md5(serialize($_SESSION['post_edit']));
	if ($_SESSION['post_edit_hash'] != $expected_hash) return false;
	if ($_SESSION['post_edit_state'] != $expected_state) return false;
	return true;
}

function cm_reg_post_edit_destroy(): void {
    unset(
        $_SESSION['post_edit'],
        $_SESSION['post_edit_hash'],
        $_SESSION['post_edit_state']
    );
    session_destroy();
}

function cm_reg_head(string $title): void {
    global $twig;

    $template = $twig->createTemplate(<<<HEREDOC
        <!DOCTYPE HTML>
            <html lang="en">
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
                <title>Register {{ title|e }} </title>
                <link rel="shortcut icon" href="{{ theme_file_url('favicon.ico', false)|e }}">
                <link rel="stylesheet" href="{{ resource_file_url('cm.css', false)|e }}">
                <link rel="stylesheet" href="{{ theme_file_url('theme.css', false)|e }}">
                <script type="text/javascript" src="{{ resource_file_url('jquery.js', false)|e }}"></script>
                <script type="text/javascript" src="{{ resource_file_url('cmui.js', false)|e }}"></script>
        HEREDOC
    );
    echo $template->render([
        'title' => $title,
    ]);
}

function cm_reg_body(string $title, bool $show_cart = true): void {
    global $twig;

    $template = $twig->createTemplate(<<<HEREDOC
        </head>
        <body class="cm-reg">
          <header>
            <div class="pagename"> {{ title|e }}</div>
            {% if show_cart == true %}
            <div class="header-items">
              <div class="header-item">
              <a href="{{ get_site_url(false) ~ '/register/cart.php'}}">Shopping Cart: {{ count }} item{{ count == 1 ? '' : 's' }}</a>
              </div>
            </div>
            {% endif %}
          </header>
        HEREDOC
    );
    echo $template->render([
        'title' => $title,
        'show_cart' => $show_cart,
        'count' => cm_reg_cart_count(true),
    ]);
}

function cm_reg_tail(): void {
    global $twig;

    $template = $twig->createTemplate(<<<HEREDOC
            </body>
        </html>
        HEREDOC
    );
    echo $template->render();
}

#[NoReturn]
function cm_reg_closed(?DateTimeImmutable $datetime = null): never
{
	global $event_name, $contact_address;
	cm_reg_head('Registration Closed');
	cm_reg_body('Registration Closed', false);
	echo '<article>';
	echo '<div class="card">';
	echo '<div class="card-content">';
	echo '<p>';
	echo 'Registrations for <b>';
	echo htmlspecialchars($event_name);
	echo '</b>';
	if ($datetime) {
		echo " will open on {$datetime->format('F d, Y')}.";
	} else {
		echo ' are currently closed.';
	}
	if ($contact_address) {
		echo ' Please <b><a href="mailto:';
		echo htmlspecialchars($contact_address);
		echo '">contact us</a></b> if you have any questions.';
	}
	echo '</p>';
	echo '</div>';
	echo '</div>';
	echo '</article>';
	cm_reg_tail();
	exit(0);
}

#[NoReturn]
function cm_reg_message(string $title, string $custom_text_name, string $default_text, array|false|null $fields = null): never {
	global $event_name, $fdb, $contact_address;
	cm_reg_head($title);
	cm_reg_body($title, false);
	echo '<article>';
	echo '<div class="card">';
	echo '<div class="card-title">';
	echo htmlspecialchars($title);
	echo '</div>';
	echo '<div class="card-content">';
	$text = $fdb->get_custom_text($custom_text_name);
	if (!$text) $text = $default_text;
	$text = safe_html_string($text, true);
	$merge_fields = array(
		'event-name' => $event_name,
		'event_name' => $event_name,
		'contact-address' => $contact_address,
		'contact_address' => $contact_address
	);
	if ($fields) {
		foreach ($fields as $k => $v) {
			$merge_fields[strtolower(str_replace('_', '-', $k))] = $v;
			$merge_fields[strtolower(str_replace('-', '_', $k))] = $v;
		}
	}
	echo mail_merge_html($text, $merge_fields);
	echo '</div>';
	echo '<div class="card-buttons">';
	echo '<a href="index.php" role="button" class="button register-button">';
	echo 'Start a New Registration';
	echo '</a>';
	echo '</div>';
	echo '</div>';
	echo '</article>';
	cm_reg_tail();
	exit(0);
}
