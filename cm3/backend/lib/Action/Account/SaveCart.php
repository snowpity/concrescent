<?php

namespace CM3_Lib\Action\Account;

use CM3_Lib\database\SearchTerm;

use CM3_Lib\models\payment;

use Branca\Branca;
use MessagePack\MessagePack;
use MessagePack\Packer;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SaveCart
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(private Responder $responder, private payment $payment)
    {
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
        $cart_id = $data['uuid'] ?? 0;
        $cart = $this->payment->GetByIDorUUID(null, $cart_id, array('id','event_id','contact_id','payment_status'));
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
            $cart = $this->payment->Create(array(
                'event_id' => $request->getAttribute('event_id'),
                'contact_id' => $request->getAttribute('contact_id'),
                'requested_by' => '[self]',
                'payment_status' => 'NotStarted',
                'payment_txn_amt' => -1,

            ));
        }

        //Save the items into the cart
        $cart['items'] = json_encode($data['items']);


        //TODO: Validate items
        $result = array('errors'=>array());

        // cm_reg_cart_destroy(false);
        // $errors = array();
        // foreach ($json['badges'] as $key => $badge) {
        //     $newitem = array();
        //     $errors[isset($badge['index']) ? $badge['index'] : ($key .'')] = cm_reg_item_update_from_post($newitem, $badge);
        //     //Ensure there is an index associated
        //     $newitem['index'] = isset($badge['index']) ? $badge['index'] : ($key .'');
        //     cm_reg_cart_add($newitem);
        // }
        // //Count up the errors
        // $errorCount = 0;
        // foreach ($errors as $errorsection) {
        //     $errorCount += count($errorsection);
        // }
        // //If there are errors, report back
        // if ($errorCount > 0) {
        //     http_response_code(400);
        //     echo json_encode(array('errors' => $errors));
        //     exit(0);
        // }

        /// Apply promo

        // $error = cm_reg_apply_promo_code($json['code']);
        // if ($error) {
        //     http_response_code(400);
        //     echo json_encode(array('errors' => array('promo' => $error)));
        //     exit(0);
        // }
        // cm_reg_cart_set_state('promoapplied');


        $result =array_merge($result, $this->payment->Update($cart));

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
