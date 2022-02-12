<?php

namespace CM3_Lib\Action\Account;

use CM3_Lib\database\SearchTerm;

use CM3_Lib\models\contact;

use Branca\Branca;
use MessagePack\MessagePack;
use MessagePack\Packer;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CheckoutCart
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(private Responder $responder, private contact $contact)
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

        // // Verify cart and attempt checkout
        // $errors = cm_reg_cart_verify_availability($json['payment_method']);
        // if ($errors) {
        //     http_response_code(400);
        //     echo json_encode(array('errors' => $errors));
        //     exit(0);
        // }
        // //Looks good!
        //   $_SESSION['payment_method'] = $json['payment_method'];
        // cm_reg_cart_set_state('ready');

        //require dirname(__FILE__).'/../register/checkout.php';


        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
