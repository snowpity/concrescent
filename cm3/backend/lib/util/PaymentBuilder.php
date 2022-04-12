<?php

namespace CM3_Lib\util;

use Respect\Validation\Validator as v;

use CM3_Lib\models\banlist;
use CM3_Lib\models\payment;

use CM3_Lib\util\badgevalidator;
use CM3_Lib\util\badgepromoapplicator;

use CM3_Lib\database\TableValidator;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;
use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\SelectColumn;

use CM3_Lib\Factory\PaymentModuleFactory;
use CM3_Lib\Modules\Payment\PayProcessorInterface;
use CM3_Lib\Modules\Notification\Mail;

final class PaymentBuilder
{
    private array $cart = array();
    private array $cart_items = array();
    private string $cart_uuid = "";
    private float $cart_payment_txn_amt = 0;//Our own sanity check against the cart
    private bool $AllowPay = true;
    private bool $CanPay = true;
    private ?PayProcessorInterface $pp = null;
    private array $stagedItems = array();
    public function __construct(
        private badgeinfo $badgeinfo,
        private CurrentUserInfo $CurrentUserInfo,
        private PaymentModuleFactory $PaymentModuleFactory,
        private banlist $banlist,
        private payment $payment,
        private badgevalidator $badgevalidator,
        private badgepromoapplicator $badgepromoapplicator,
        private FrontendUrlTranslator $FrontendUrlTranslator,
        private Mail $Mail
    ) {
    }

    public function createCart($contact_id = null, $requested_by = '[self]')
    {
        $template = array(
            'event_id' => $this->CurrentUserInfo->GetEventId(),
            'contact_id' => $contact_id ?? $this->CurrentUserInfo->GetContactId(),
            'requested_by' => $requested_by,
            'items' => '[]',
            'payment_status' => 'NotReady',
            'payment_system' => 'Cash',
            'payment_txn_amt' => -1,

        );
        $this->cart = array_merge($template, $this->payment->Create($template));
        $this->cart_items = array();
        $cart_payment_txn_amt = 0;
        $this->AllowPay = true;
        $this->CanPay = true;
        $this->pp = null;
        $this->stagedItems = array();
    }

    public function loadCartFromBadge($context_code, $id)
    {
        $badge = $this->badgeinfo->GetSpecificBadge($id, $context_code, true);
        if ($badge===false) {
            return false;
        }
        //Fetch the associated payment
        return $this->loadCart($badge['payment_id']);
    }

    public function loadCart(int $cart_id, string $cart_uuid = null, $expectedEventId = null, $expectedContactId = null)
    {
        $cart = $this->payment->GetByIDorUUID($cart_id, $cart_uuid, array('id','uuid','event_id','contact_id','payment_status','payment_system','payment_txn_amt','items','payment_details'));

        if ($cart === false) {
            $this->cart = array();
            return false;
        }

        //Check that the cart is in the right event, and right contact
        if (
            (!is_null($expectedEventId) && $cart['event_id'] != $expectedEventId)
            ||(!is_null($expectedContactId) && $cart['contact_id'] != $expectedContactId)
        ) {
            $this->cart = array();
            return false;
        }

        $this->cart = $cart;
        //Extract and remove the UUID, since we never want to try saving it back
        $this->cart_uuid = $cart['uuid'];
        unset($this->cart['uuid']);
        $this->cart_items = json_decode($cart['items'], true) ?? array();
        return true;
    }

    public function saveCart()
    {
        $this->cart['items'] = json_encode($this->cart_items);
        if (isset($this->pp)) {
            $this->cart['payment_details'] = '';
            $this->pp->SaveOrder($this->cart['payment_details']);
        }

        //Save the current status
        $this->payment->Update($this->cart);
    }

    public function canEdit()
    {
        return
            $this->cart['payment_status'] == 'NotReady'
            ||$this->cart['payment_status'] == 'NotStarted'
            ||$this->cart['payment_status'] == 'Cancelled';
    }

    public function canCheckout()
    {
        //Check if we can alter this payment
        if (!(
            $this->cart['payment_status'] == 'NotStarted'
            ||$this->cart['payment_status'] == 'Incomplete'
            ||$this->cart['payment_status'] == 'Cancelled'
        )
        ) {
            return false;
        }
        return true;
    }
    public function getCartId()
    {
        return $this->cart['id'] ?? null;
    }
    public function getCartEventId()
    {
        return $this->cart['event_id'] ?? null;
    }
    public function getCartContactId()
    {
        return $this->cart['contact_id'] ?? null;
    }
    public function getCartStatus()
    {
        return $this->cart['payment_status'] ?? null;
    }
    public function setRequestedBy(string $name)
    {
        $this->cart['requested_by'] = $name;
    }

    public function setCartItems($items, $promocode = "", &$promoApplied = false)
    {
        $errors = array();
        $this->cart_items = array();
        foreach ($items as $key => $badge) {
            $errors[$key] = $this->setCartItem($key, $badge, $promocode, $promoApplied);
        }
        //Do we have errors?
        $this->cart['payment_status'] = count($errors) ? 'NotStarted' : 'NotReady';

        //Did we try a promo code and fail?
        if (!$promoApplied && !empty($data['promocode'])) {
            $result['errors']['promo'] = 'Promo did not apply to any items in the cart';
        }
        $this->saveCart();
        return $errors;
    }

    public function setCartItem($cartIx, $item, $promocode = "", &$promoApplied = false)
    {
        if (!isset($item['context_code'])) {
            $item['context_code']='A';
        }
        //Ensure this badge is owned by the user (if we're not editing) and is good on the surface
        if (isset($item['id']) && $item['id'] > 0) {
            $bi = $this->badgeinfo->getSpecificBadge($item['id'], $item['context_code']);
            //Preserve the current badge state, but only if it hasn't been preserved already
            if ($bi !== false && !isset($item['existing'])) {
                $item['existing'] = $bi;
            }
            //If this isn't ours, ensure certain fields aren't tampered with
            if ($bi['contact_id'] != $this->cart['contact_id']) {
                $allowed = array(
                    'notify_email',
                    'can_transfer',
                    'contact_id'
                );
                array_walk($allowed, function ($col) use ($item, $bi) {
                    if (isset($bi[$col])) {
                        $item[$col] = $bi[$col];
                    } else {
                        unset($item[$col]);
                    }
                });
                //And set the contact_id
                $item['contact_id'] = $bi['contact_id'];
            } else {
                $item['contact_id'] = $this->cart['contact_id'];
            }
        } else {
            $item['contact_id'] = $this->cart['contact_id'];
        }

        $errors = $this->badgevalidator->ValdateCartBadge($item);

        //Try to apply promo code, or otherwise update the price
        $promoApplied = $promoApplied | $this->badgepromoapplicator->TryApplyCode($item, $promocode);
        //Ensure there is an index associated
        $item['cartIx'] = isset($item['cartIx']) ? $item['cartIx'] : ($cartIx .'');
        $this->cart_items[$cartIx] = $item;
        return $errors;
    }

    public function getCartItemByIx($cartIx)
    {
        return $this->cart_items[$cartIx];
    }

    public function findCartItemIxById($context_code, $id)
    {
        foreach ($this->cart_items as $key => $item) {
            if ($item['context_code'] == $context_code && $item['id'] == $id) {
                return $key;
            }
        }
        return false;
    }

    public function getCartItems()
    {
        return $this->cart_items;
    }

    public function getCartErrors(bool $updateReadyStatus = false)
    {
        //Just run through and validate the items as they sit
        $result = array();
        foreach ($this->cart_items as $badge) {
            $result[$badge['cartIx']] = $this->badgevalidator->ValdateCartBadge($badge);
        }
        if ($updateReadyStatus) {
            $this->cart['payment_status'] = count($result) ? 'NotStarted' : 'NotReady';
        }
        return $result;
    }

    public function getCartTotal(bool $refresh = true)
    {
        if ($refresh) {
            $cart_payment_txn_amt = 0;

            foreach ($this->cart_items as $key => &$item) {
                $this->badgepromoapplicator->TryApplyCode($item, $item['payment_promo_code'] ?? '');
                $cart_payment_txn_amt += max(0, $item['payment_promo_price'] ?? $item['payment_badge_price']);
                //Check for addons
                if (isset($item['addons'])) {
                    $existingAddons = array_column(
                        $this->badgeinfo->GetAttendeeAddons($item['id']),
                        'payment_status',
                        'addon_id'
                    );
                    $availableaddons = array_column($this->badgeinfo->GetAttendeeAddonsAvailable($item['badge_type_id']), null, 'id');
                    foreach ($item['addons'] as $addon) {
                        if (isset($existingAddons[$addon['addon_id']]) && $existingAddons[$addon['addon_id']] == 'Completed') {
                            continue;
                        }

                        //Prep Sanity check the cart's amount...
                        $cart_payment_txn_amt += max(0, $addon['payment_promo_price'] ?? $addon['payment_price']);
                    }
                }
            }
            $this->cart['payment_txn_amt'] = $cart_payment_txn_amt;
            $this->saveCart();
        }

        return $this->cart['payment_txn_amt'];
    }

    public function setPayProcessor(string $PayProcessor)
    {
        if ($this->cart['payment_system'] != $PayProcessor) {
            $this->cart['payment_system'] = $PayProcessor;
            $this->cart['payment_details'] ="";
            unset($this->pp);
            //Save the current status
            $this->payment->Update($this->cart);
        }
    }
    public function getPayProcessorName(): ?string
    {
        return $this->cart['payment_system'];
    }
    public function getPayProcessor(): PayProcessorInterface
    {
        if (!isset($this->pp)) {
            //try {
            $this->pp = $this->PaymentModuleFactory->Create($this->cart['payment_system']);
            if (!empty($this->cart['payment_details'])) {
                $this->pp->LoadOrder($this->cart['payment_details']);
            }
            //} catch (\Exception $e) {
            //}
        }
        return $this->pp;
    }

    public function SetAllowPay(bool $CanPay)
    {
        $this->AllowPay = CanPay;
    }

    public function isFreeride()
    {
        return $this->cart['payment_txn_amt'] == 0;
    }

    public function resetPayment()
    {
        $this->stagedItems = array();
        $this->cart['payment_details'] = '';
        $this->cart['payment_status'] = 'NotReady';
    }

    //Note we expect all items to have been validated
    public function prepPayment()
    {
        //First do some pre-checks
        $banlisted = false;
        $errors = array();

        foreach ($this->cart_items as $key => &$item) {
            //Create/Update the badge
            $bt = $this->badgeinfo->getBadgetType($item['context_code'], $item['badge_type_id']);
            $bi = $this->badgeinfo->getSpecificBadge($item['id'] ?? 0, $item['context_code']);
            $item['payment_id'] = $this->cart['id'];
            $item['payment_status'] = 'Incomplete';
            if (isset($item['existing'])) {
                $this->badgeinfo->UpdateSpecificBadgeUnchecked($item['id'], $item['context_code'], $item);
            } else {
                //Ensure the badge has an owner
                $item['contact_id'] =$item['contact_id'] ?? $this->CurrentUserInfo->GetContactId();
                //And that the payment status is Incomplete
                $newID = $this->badgeinfo->CreateSpecificBadgeUnchecked($item);
                if ($newID !== false) {
                    $item['id'] = $newID['id'];
                }
            }
            //Save the form responses
            if (isset($item['form_responses'])) {
                $this->badgeinfo->SetSpecificBadgeResponses($item['id'], $item['context_code'], $item['form_responses']);
            }

            //Check for bans
            if ($this->banlist->is_banlisted($item)) {
                $banlisted = true;
                $canpay = false;
                //TODO: Bubble a notify event
                $errors[] = 'Banned:'.$key;
            }
            //TODO: Process applicants too

            //Only add this as a line item if we're a new badge or upgrading (hence needing payment)
            if (!isset($item['existing']) || 0 < $item['payment_promo_price']) {
                $this->stagedItems[] = array(
                    $bt['name'],
                    $bt['price'],
                    1,
                    $bt['description'],
                    $this->CurrentUserInfo->GetEventId() . ':' . $item['context_code'] . ':' . $item['badge_type_id'],
                    max(0, $bt['price'] - ($item['payment_promo_price'] ?? $item['payment_badge_price'])),
                    $item['payment_promo_code'] ?? null
                );
            }
            //Check if this item is payable
            if (!empty($bt['payment_deferred']) && $bt['payment_deferred']) {
                $this->CanPay = false;
            }
            //Prep Sanity check the cart's amount...
            $this->cart_payment_txn_amt += max(0, $item['payment_promo_price'] ?? $item['payment_badge_price']);

            //Check for addons
            if (isset($item['addons'])) {
                $existingAddons = array_column(
                    $this->badgeinfo->GetAttendeeAddons($item['id']),
                    'payment_status',
                    'addon_id'
                );
                $availableaddons = array_column($this->badgeinfo->GetAttendeeAddonsAvailable($item['badge_type_id']), null, 'id');
                foreach ($item['addons'] as $addon) {
                    if (isset($existingAddons[$addon['addon_id']]) && $existingAddons[$addon['addon_id']] == 'Completed') {
                        continue;
                    }
                    $addon['attendee_id'] = $item['id'];
                    $addon['payment_id'] = $this->cart['id'];
                    $addon['payment_status'] = 'Incomplete';

                    $this->badgeinfo->AddUpdateABadgeAddonUnchecked($addon);

                    //Add it to the payment
                    $faddon = $availableaddons[$addon['addon_id']];

                    $this->stagedItems[] = array(
                        $faddon['name'],
                        $faddon['price'],
                        1,
                        $faddon['description'],
                        $this->CurrentUserInfo->GetEventId() . ':' . $item['context_code'] . ',a:' . $addon['addon_id'],
                        max(0, $faddon['price'] - ($addon['payment_promo_price'] ?? $addon['payment_price'])),
                        $addon['payment_promo_code'] ?? null
                    );

                    //Prep Sanity check the cart's amount...
                    $this->cart_payment_txn_amt += max(0, $addon['payment_promo_price'] ?? $addon['payment_price']);
                }
            }
        }

        $this->getPayProcessor();

        $this->pp->SetReturnURLs(
            $this->FrontendUrlTranslator->GetPaymentReturn($this->cart_uuid),
            $this->FrontendUrlTranslator->GetPaymentCancel($this->cart_uuid)
        );
        foreach ($this->stagedItems as $sitem) {
            call_user_func_array(array($this->pp,'AddItem'), $sitem);
        }


        //Determine new cart status based on flags
        if (!$this->CanPay) {
            $this->cart['payment_status'] = 'AwaitingApproval';
        } else {
            $this->cart['payment_status'] = 'NotStarted';
        }

        //TODO: Real sanity check please
        $this->cart['payment_txn_amt'] = $this->cart_payment_txn_amt;

        $this->saveCart();

        //Report back the errors
        return $errors;
    }

    public function confirmPrep()
    {
        //Make sure we're not AwaitingApproval
        if ($this->cart['payment_status'] == 'AwaitingApproval') {
            return false;
        }

        //If a free-ride we don't do anything
        if ($this->isFreeride()) {
            $this->cart['payment_system']='Freeride';
            return true;
        }

        //Are we in-progress already?

        if ($this->cart['payment_status'] == 'Incomplete') {
            return true;
        } elseif ($this->cart['payment_status'] == 'NotStarted') {
            if ($this->AllowPay && $this->CanPay) {
                $this->getPayProcessor();
                if ($this->pp->ConfirmOrder()) {
                    $payment_details = $this->pp->GetDetails();
                    $this->cart['payment_status'] = 'Incomplete';
                    $this->saveCart();
                    return true;
                } else {
                    throw new \Exception('Failed to confirm order with provider.');
                }
            }
            $this->saveCart();
        }
        return false;
    }

    public function CompletePayment($completionData)
    {
        if (!$this->isFreeride()) {
            $this->getPayProcessor();
            if (!$this->pp->CompleteOrder($completionData)) {
                //Failed. Do we know why?
                $this->cart['payment_status'] = $this->pp->GetOrderStatus();
                $this->saveCart();
                return false;
            }
        }

        foreach ($this->cart_items as $key => &$item) {
            //Update the badge
            if (!isset($item['context_code'])) {
                $item['context_code']='A';
            }
            $bi = $this->badgeinfo->getSpecificBadge($item['id'], $item['context_code']);
            if ($bi !== false) {
                $item['payment_status'] = 'Completed';

                $this->badgeinfo->UpdateSpecificBadgeUnchecked($item['id'], $item['context_code'], $item);
                if (!isset($item['existing']) || (isset($item['existing']) && $item['existing']['display_id'] == null)) {
                    $this->badgeinfo->setNextDisplayIDSpecificBadge($item['id'], $item['context_code']);
                }
            } else {
                throw new \Exception('Badge not found?!?' . $item['context_code'] . $item['id']);
            }

            //Check for addons
            if (isset($item['addons'])) {
                foreach ($item['addons'] as &$addon) {
                    $addon['attendee_id'] = $item['id'];
                    $addon['payment_id'] = $this->cart['id'];
                    $addon['payment_status'] = 'Completed';
                    $this->badgeinfo->AddUpdateABadgeAddonUnchecked($addon);
                }
            }
        }

        $this->cart['payment_status'] = 'Completed';
        $this->cart['payment_date'] = $this->payment->getDbNow();
        $this->saveCart();
        return true;
    }


    public function CancelPayment()
    {
        foreach ($this->cart_items as $key => &$item) {
            //Revert the badge
            $item['payment_status'] = 'Cancelled';
            if (isset($item['existing'])) {
                $this->badgeinfo->UpdateSpecificBadgeUnchecked($item['id'], $item['context_code'], $item['existing']);
            } else {
                $this->badgeinfo->UpdateSpecificBadgeUnchecked($item['id'], $item['context_code'], $item);
            }

            //Check for addons
            if (isset($item['addons'])) {
                foreach ($item['addons'] as &$addon) {
                    $addon['attendee_id'] = $item['id'];
                    $addon['payment_id'] = $this->cart['id'];
                    $addon['payment_status'] = 'Cancelled';
                    $this->badgeinfo->AddUpdateABadgeAddonUncheckedon($addon);
                }
            }
        }
        $this->getPayProcessor()->CancelOrder();
        $this->cart['payment_details'] = '';
        unset($this->pp);
    }

    public function SendStatusEmail()
    {
        foreach ($this->cart_items as $item) {
            $to = $this->CurrentUserInfo->GetContactEmail($item['contact_id']);
            //Get the current info, not what's in the order
            $badge = $this->badgeinfo->getSpecificBadge($item['id'], $item['context_code'], true);
            $template = $this->cart['mail_template'] ?? ($item['context_code'] . '-payment-' .$this->cart['payment_status']);
            $this->Mail->SendTemplate($to, $template, $badge, $badge['notify_email']);
        }
    }
}
