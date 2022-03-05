<?php

namespace CM3_Lib\Action\Account;

use CM3_Lib\database\SearchTerm;

use CM3_Lib\models\payment;
use CM3_Lib\util\badgevalidator;
use CM3_Lib\util\badgepromoapplicator;

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
        private badgepromoapplicator $badgepromoapplicator
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
        $cart_id = $data['id'] ?? 0;
        $cart_uuid = $data['uuid'] ?? 0;
        $cart = $this->payment->GetByIDorUUID($cart_id, $cart_uuid, array('id','event_id','contact_id','payment_status'));
        if ($cart !== false) {
            //Check that the cart is ours, in the right event, and right state
            if (
                $cart['event_id'] != $request->getAttribute('event_id')
                || $cart['contact_id'] != $request->getAttribute('contact_id')
            ) {
                $cart = false;
            }
        }
        //Do we need to create a new one?
        if ($cart == false) {
            $template = array(
                'event_id' => $request->getAttribute('event_id'),
                'contact_id' => $request->getAttribute('contact_id'),
                'requested_by' => '[self]',
                'payment_status' => 'NotStarted',
                'payment_txn_amt' => -1,

            );
            $cart = array_merge($template, $this->payment->Create($template));
        }
        //If the cart is in progress, we cannot adjust it until cancelled or completed...
        if (!(
            $cart['payment_status'] == 'NotStarted'
            ||$cart['payment_status'] == 'Cancelled'
        )
        ) {
            throw new HttpBadRequestException($request, 'Cart not in correct state to alter: ' .$cart['payment_status']);
        }

        $cart_total = 0.0;

        //Validate items
        $result = array('errors'=>array());

        $items = array();
        $errors = array();
        $promoApplied = false;
        foreach ($data['items'] as $key => $badge) {
            //Ensure this badge is owned by the user
            $badge['contact_id'] = $cart['contact_id'];
            $errors[isset($badge['index']) ? $badge['index'] : ($key .'')] = $this->badgevalidator->ValdateCartBadge($badge);
            $newitem = $badge;
            //Try to apply promo code?
            if (!empty($data['promocode'])) {
                $promoApplied = $promoApplied || $this->badgepromoapplicator->TryApplyCode($newitem, $data['promocode']);
            }
            //Ensure there is an index associated
            $newitem['index'] = isset($badge['index']) ? $badge['index'] : ($key .'');
            $items[] = $newitem;
            //Add to the cart total
            $cart_total += $badge['payment_promo_price'] ?? $badge['payment_badge_price'];
        }
        //Did we try a promo code and fail?
        if (!$promoApplied && !empty($data['promocode'])) {
            $result['errors']['promo'] = 'Promo did not apply to any items in the cart';
        }
        $cart['payment_txn_amt'] = $cart_total;
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
