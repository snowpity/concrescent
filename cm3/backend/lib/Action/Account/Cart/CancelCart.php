<?php

namespace CM3_Lib\Action\Account\Cart;

use CM3_Lib\database\SearchTerm;

use CM3_Lib\util\PaymentBuilder;
use CM3_Lib\util\CurrentUserInfo;

use Branca\Branca;
use MessagePack\MessagePack;
use MessagePack\Packer;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CancelCart
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
        private CurrentUserInfo $CurrentUserInfo
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
        $data = (array)$request->getQueryParams();

        //Fetch the authenticated user's info
        $c_id = $this->CurrentUserInfo->GetContactId();
        $e_id = $this->CurrentUserInfo->GetEventId();

        //Check if we have specified a cart
        $cart_id = $data['id'] ?? $params['id'] ?? 0;
        $cart_uuid = $data['uuid'] ?? '';

        $cart_loaded = $this->PaymentBuilder->loadCart(
            $cart_id,
            $cart_uuid,
            $this->CurrentUserInfo->GetEventId(),
            $this->CurrentUserInfo->GetContactId()
        );

        if (!$cart_loaded) {
            throw new HttpNotFoundException($request, $cart_id);
        }

        $this->PaymentBuilder->CancelPayment();
        $result = $this->PaymentBuilder->getCartExpandedState();

        // $searchTerms = array(
        //   new SearchTerm('event_id', $e_id),
        //   new SearchTerm('contact_id', $c_id),
        //   new SearchTerm('id', $params['id']),
        // );
        //
        // //Simply get the user's active Payments
        // $result = $this->payment->Search(
        //     array(
        //         'id',
        //         'payment_system',
        //         'payment_status',
        //     ),
        //     $searchTerms
        // );
        //
        // foreach ($result as &$payment) {
        //     //We can only do incomplete payments
        //     if (in_array($payment['payment_status'], array(
        //             'NotReady',
        //             'NotStarted',
        //             'Incomplete',
        //     ))) {
        //         $payment['payment_status'] = 'Cancelled';
        //         $this->payment->Update($payment);
        //     }
        // }


        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
