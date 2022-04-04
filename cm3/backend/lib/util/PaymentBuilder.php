<?php

namespace CM3_Lib\util;

use Respect\Validation\Validator as v;

use CM3_Lib\models\banlist;
use CM3_Lib\models\payment;

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
        private FrontendUrlTranslator $FrontendUrlTranslator,
        private Mail $Mail
    ) {
    }

    public function loadCart(int $cart_id, string $cart_uuid = null)
    {
        $cart = $this->payment->GetByIDorUUID($cart_id, $cart_uuid, array('id','event_id','contact_id','payment_status','payment_system','payment_txn_amt','items','payment_details'));

        if ($cart === false) {
            $this->cart = array();
            return false;
        }
        $this->cart = $cart;
        $this->cart_items = json_decode($cart['items'], true);
        return true;
    }

    private function saveCart()
    {
        $this->cart['items'] = json_encode($this->cart_items);
        if (isset($this->pp)) {
            $this->cart['payment_details'] = '';
            $this->pp->SaveOrder($this->cart['payment_details']);
        }

        //Save the current status
        $this->payment->Update($this->cart);
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
    public function getCartStatus()
    {
        return $this->cart['payment_status'] ?? null;
    }
    public function setPayProcessor(string $PayProcessor)
    {
        if ($this->cart['payment_system'] != $PayProcessor) {
            $this->cart['payment_system'] = $PayProcessor;
            $this->cart['payment_details'] ="";
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

    //Note we expect all items to have been validated
    public function prepPayment()
    {
        //First do some pre-checks
        $banlisted = false;
        $errors = array();

        foreach ($this->cart_items as $key => &$item) {
            //Create/Update the badge
            if (!isset($item['context_code'])) {
                $item['context_code']='A';
            }
            $bt = $this->badgeinfo->getBadgetType($item['context_code'], $item['badge_type_id']);
            $bi = $this->badgeinfo->getSpecificBadge($item['id'] ?? 0, $item['context_code']);
            $item['payment_txn_id'] = $this->cart['id'];
            if ($bi !== false) {
                //Preserve the current badge state
                $item['existing'] = $bi;
                $item['payment_status'] = 'Incomplete';
                $this->badgeinfo->UpdateSpecificBadgeUnchecked($item['id'], $item['context_code'], $item);
            } else {
                //Ensure the badge has an owner
                $item['contact_id'] =$item['contact_id'] ?? $this->CurrentUserInfo->GetContactId();
                //And that the payment status is Incomplete
                $item['payment_status'] = 'Incomplete';
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


            $this->stagedItems[] = array(
                $bt['name'],
                $bt['price'],
                1,
                $bt['description'],
                $this->CurrentUserInfo->GetEventId() . ':' . $item['context_code'] . ':' . $item['badge_type_id'],
                max(0, $bt['price'] - ($item['payment_promo_price'] ?? $item['payment_badge_price'])),
                $item['payment_promo_code'] ?? null
            );
            //Check if this item is payable
            if (!empty($bt['payment_deferred']) && $bt['payment_deferred']) {
                $this->CanPay = false;
            }
            //Prep Sanity check the cart's amount...
            $this->cart_payment_txn_amt += max(0, $item['payment_promo_price'] ?? $item['payment_badge_price']);

            //Check for addons
            if (isset($item['addons'])) {
                foreach ($item['addons'] as &$addon) {
                    $addon['attendee_id'] = $item['id'];
                    $addon['payment_txn_id'] = $this->cart['id'];
                    $addon['payment_status'] = 'Incomplete';
                    $this->badgeinfo->AddUpdateABadgeAddonUncheckedon($addon);

                    //Prep Sanity check the cart's amount...
                    $this->cart_payment_txn_amt += max(0, $addon['payment_promo_price'] ?? $addon['payment_badge_price']);
                }
            }
        }

        $this->getPayProcessor();

        $this->pp->SetReturnURLs(
            $this->FrontendUrlTranslator->GetPaymentReturn($this->cart['id']),
            $this->FrontendUrlTranslator->GetPaymentCancel($this->cart['id'])
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
                switch ($this->pp->GetOrderStatus()) {
                    case 'Cancelled':
                    case 'Rejected':
                    case 'NotReady':
                        $item['payment_status'] = $this->pp->GetOrderStatus();
                        //Not able to complete
                        break;
                }
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
                if (!isset($item['existing'])) {
                    $this->badgeinfo->setNextDisplayIDSpecificBadge($item['id'], $item['context_code']);
                }
            } else {
                throw new \Exception('Badge not found?!?' . $item['context_code'] . $item['id']);
            }

            //Check for addons
            if (isset($item['addons'])) {
                foreach ($item['addons'] as &$addon) {
                    $addon['payment_status'] = 'Complete';
                    $this->badgeinfo->AddUpdateABadgeAddonUncheckedon($addon);
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
        $to = $this->CurrentUserInfo->GetContactEmail();

        foreach ($this->cart_items as $item) {
            //Get the current info, not what's in the order
            $badge = $this->badgeinfo->getSpecificBadge($item['id'], $item['context_code']);
            $template = $this->cart['mail_template'] ?? ($item['context_code'] . '-payment-' .$this->cart['payment_status']);
            $this->Mail->SendTemplate($to, $template, $badge, $badge['notify_email']);
        }
    }
}
