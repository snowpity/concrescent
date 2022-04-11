<?php

namespace CM3_Lib\Action\Account\Cart;

use CM3_Lib\database\SearchTerm;

use CM3_Lib\models\payment;
use CM3_Lib\util\PaymentBuilder;
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
use Slim\Exception\HttpNotFoundException;

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
        private PaymentBuilder $PaymentBuilder,
        private CurrentUserInfo $CurrentUserInfo,
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

        $cart_loaded = $this->PaymentBuilder->loadCart(
            $cart_id,
            $cart_uuid,
            $this->CurrentUserInfo->GetEventId(),
            $this->CurrentUserInfo->GetContactId()
        );

        //Do we need to create a new one?
        if ($cart_loaded == false && isset($data['items'])) {
            $this->PaymentBuilder->createCart();
        }
        //If the cart is in progress, we cannot adjust it until cancelled or completed...
        if (!$this->PaymentBuilder->canEdit()) {
            throw new HttpBadRequestException($request, 'Cart not in correct state to alter: ' .$this->PaymentBuilder->getCartStatus());
        }

        //Validate items
        $result = array(
            'errors'=>array(),
            'items'=>array(),
            'state'=> $this->PaymentBuilder->getCartStatus()
        );

        if (!isset($data['items'])) {
            $result['id'] = $this->PaymentBuilder->getCartId();

            // Build the HTTP response
            return $this->responder
                ->withJson($response, $result);
        }
        $items = array();
        $errors = array();
        $promoApplied = false;
        $promocode = $data['promocode'] ?? "";

        $result['errors'] =  $this->PaymentBuilder->setCartItems($data['items'], $promocode);
        $result['items'] = $this->PaymentBuilder->getCartItems();
        $result['state'] = $this->PaymentBuilder->getCartStatus();
        $result['id']  = $this->PaymentBuilder->getCartId();

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
