<?php

namespace CM3_Lib\Action\Account;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\DbConnection;
use CM3_Lib\Factory\PaymentModuleFactory;
use CM3_Lib\util\PaymentBuilder;

use CM3_Lib\models\payment;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

class CheckoutCart
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
        $cart_id = $data['id'] ?? 0;
        $cart_uuid = $data['uuid'] ?? null;

        if (!$this->PaymentBuilder->loadCart($cart_id, $cart_uuid)) {
            throw new HttpNotFoundException($request);
        }

        //If the cart is in progress, we cannot adjust it until cancelled or completed...
        if (!$this->PaymentBuilder->canCheckout()
        ) {
            throw new HttpBadRequestException($request, 'Cart not in correct state to checkout: ' .$this->PaymentBuilder->getCartStatus());
        }

        if ($this->PaymentBuilder->getCartStatus() == 'Incomplete') {
            //Hrm, they've already initiated the payment request. Check if it's completed

            if ($this->PaymentBuilder->CompletePayment($data)) {

                // Build the HTTP response
                return $this->responder
                ->withJson($response, array(
                    'orderstatus' => $this->PaymentBuilder->getCartStatus()
                ));
            } else {
                $pp = $this->PaymentBuilder->getPayProcessor();
                // Build the HTTP response
                return $this->responder
                ->withJson($response, array(
                    'paymentURL' => $pp->RetrievePaymentRedirectURL(),
                    'payment_status' => $this->PaymentBuilder->getCartStatus()
                ));
            }
        } elseif ($this->PaymentBuilder->getCartStatus() == 'Cancelled') {
            //They want to try paying again after cancelling
            $this->PaymentBuilder->CancelPayment();
        }
        //Build the payment
        $errors = $this->PaymentBuilder->prepPayment();

        if (count($errors) > 0) {
            throw new \Exception('Errors! ' . var_dump($errors));
        }

        if (isset($data['payment_system'])) {
            $this->PaymentBuilder->setPayProcessor($data['payment_system']);
        } elseif (empty($this->PaymentBuilder->getPaymentProcessor())) {
            throw new Exception('payment_system not specified!');
        }
        //Finish the prep
        if ($this->PaymentBuilder->confirmPrep()) {
            return $this->responder
                ->withJson($response, array(
                    'paymentURL' => $this->PaymentBuilder->getPayProcessor()->RetrievePaymentRedirectURL(),
                    'payment_status' => $this->PaymentBuilder->getCartStatus()
                ));
        }
        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
