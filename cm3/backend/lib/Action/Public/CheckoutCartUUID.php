<?php

namespace CM3_Lib\Action\Public;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\Factory\PaymentModuleFactory;
use CM3_Lib\util\PaymentBuilder;

use CM3_Lib\models\payment;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

class CheckoutCartUUID
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
        private payment $payment
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
        $cart_uuid = $data['uuid'] ?? null;

        if (!$this->PaymentBuilder->loadCart($cart_id, $cart_uuid)) {
            throw new HttpNotFoundException($request);
        }

        //TODO: Validate ownership/permissions?

        //If the cart is in progress, we cannot adjust it until cancelled or completed...
        if (!$this->PaymentBuilder->canCheckout()
        ) {
            if ($this->PaymentBuilder->getCartStatus() == 'Completed') {
                //Weird, they're already completed. Let them know about that...
                return $this->responder
                ->withJson($response, array(
                    'state' => $this->PaymentBuilder->getCartStatus()
                ));
            }
            throw new HttpBadRequestException($request, 'Cart not in correct state to checkout: ' .$this->PaymentBuilder->getCartStatus());
        }

        if ($this->PaymentBuilder->getCartStatus() == 'Incomplete') {
            //Hrm, they've already initiated the payment request. Check if it's completed

            if ($this->PaymentBuilder->CompletePayment($data)) {
                $this->PaymentBuilder->SendStatusEmail();
                // Build the HTTP response
                return $this->responder
                ->withJson($response, array(
                    'state' => $this->PaymentBuilder->getCartStatus()
                ));
            }
        }
        //Since this isn't our cart, just spit the state
        return $this->responder
            ->withJson($response, array(
                'state' => $this->PaymentBuilder->getCartStatus()
            ));
    }
}
