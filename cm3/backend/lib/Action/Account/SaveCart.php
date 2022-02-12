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

class SaveCart
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



        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
