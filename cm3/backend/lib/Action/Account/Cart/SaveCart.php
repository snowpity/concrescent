<?php

namespace CM3_Lib\Action\Account\Cart;

use CM3_Lib\database\SearchTerm;

use CM3_Lib\models\payment;
use CM3_Lib\util\badgevalidator;
use CM3_Lib\util\badgepromoapplicator;
use CM3_Lib\util\CurrentUserInfo;
use CM3_Lib\util\badgeinfo;

use Branca\Branca;
use MessagePack\MessagePack;
use MessagePack\Packer;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpBadRequestException;

class SaveCart
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(
        private Responder $responder,
        private payment $payment,
        private badgevalidator $badgevalidator,
        private badgepromoapplicator $badgepromoapplicator,
        private CurrentUserInfo $CurrentUserInfo,
        private badgeinfo $badgeinfo,
    ) {
    }

    /**
     * Action.
     *
     * @param ServerRequestInterface $request The request
     * @param ResponseInterface $response The response
     *
     * @return ResponseInterface The response
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $params): ResponseInterface
    {
        $data = (array)$request->getParsedBody();

        //Check if we have specified a cart
        $cart_id = $data['id'] ?? $params['id'] ?? 0;
        $cart_uuid = $data['uuid'] ?? '';
        $cart = $this->payment->GetByIDorUUID($cart_id, $cart_uuid, array('id','event_id','contact_id','payment_status'));
        if ($cart !== false) {
            //Check that the cart is ours, in the right event, and right state
            if (
                $cart['event_id'] != $this->CurrentUserInfo->GetEventId()
                || $cart['contact_id'] != $this->CurrentUserInfo->GetContactId()
            ) {
                $cart = false;
            }
        }
        //Do we need to create a new one?
        if ($cart == false && isset($data['items'])) {
            $template = array(
                'event_id' => $this->CurrentUserInfo->GetEventId(),
                'contact_id' => $request->getAttribute('contact_id'),
                'requested_by' => '[self]',
                'payment_status' => 'NotReady',
                'payment_txn_amt' => -1,

            );
            $cart = array_merge($template, $this->payment->Create($template));
        }
        //If the cart is in progress, we cannot adjust it until cancelled or completed...
        if (!(
            $cart['payment_status'] == 'NotReady'
            ||$cart['payment_status'] == 'NotStarted'
            ||$cart['payment_status'] == 'Cancelled'
        )
        ) {
            throw new HttpBadRequestException($request, 'Cart not in correct state to alter: ' .$cart['payment_status']);
        }

        //Validate items
        $result = array('errors'=>array());

        if (!isset($data['items'])) {
            $result['id'] = $cart['id'];

            // Build the HTTP response
            return $this->responder
                ->withJson($response, $result);
        }
        $items = array();
        $errors = array();
        $promoApplied = false;
        $promocode = $data['promocode'] ?? "";
        foreach ($data['items'] as $key => $badge) {
            //Ensure this badge is owned by the user (if we're not editing) and is good on the surface
            if (!isset($badge['id'])) {
                $badge['contact_id'] = $cart['contact_id'];
            } else {
                $bi = $this->badgeinfo->getSpecificBadge($badge['id'], $badge['context_code']);
                //If this isn't ours, ensure certain fields aren't tampered with
                if ($bi['contact_id'] != $cart['contact_id']) {
                    $allowed = array(
                        'notify_email',
                        'can_transfer',
                        'contact_id'
                    );
                    array_walk($allowed, function ($col) use ($badge, $bi) {
                        if (isset($bi[$col])) {
                            $badge[$col] = $bi[$col];
                        } else {
                            unset($badge[$col]);
                        }
                    });
                    //And set the contact_id
                    $badge['contact_id'] = $bi['contact_id'];
                }
            }

            $errors[isset($badge['cartIx']) ? $badge['cartIx'] : ($key .'')] = $this->badgevalidator->ValdateCartBadge($badge);

            //Try to apply promo code?
            //if (!empty($data['promocode'])) {
            $promoApplied = $promoApplied | $this->badgepromoapplicator->TryApplyCode($badge, $promocode);
            //}
            //Ensure there is an index associated
            $badge['cartIx'] = isset($badge['cartIx']) ? $badge['cartIx'] : ($key .'');
            $items[] = $badge;
        }
        //Do we have errors?
        $cart['payment_status'] = count($errors) ? 'NotStarted' : 'NotReady';

        //Did we try a promo code and fail?
        if (!$promoApplied && !empty($data['promocode'])) {
            $result['errors']['promo'] = 'Promo did not apply to any items in the cart';
        }
        $result['errors'] = $errors;
        $result['items'] = $items;
        $result['state'] = $cart['payment_status'];

        //Save the items into the cart
        $cart['items'] = json_encode($items);


        $updated = $this->payment->Update($cart);
        if ($updated === false) {
            //If we didn't change anything, just return the current ID
            $updated = array('id'=>$cart['id']);
        }
        $result =array_merge($result, $updated);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
