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

class GetAccount
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
        //Fetch the authenticated user's info
        $result = $this->contact->GetByIDorUUID($request->getAttribute('contact_id'), null, array(
          'id',
          'date_created',
          'date_modified',
          'allow_marketing',
          'email_address',
          'real_name',
          'phone_number',
          'address_1',
          'address_2',
          'city',
          'state',
          'zip_code',
          'country',
          'notes',
        ));
        if($result === false){
          //This should not happen, but in case it does, output a dummy
          $result = [            
              "id"=> 1,
              "date_created"=> "1970-01-01 00:00:00",
              "date_modified"=> "1970-01-01 00:00:00",
              "allow_marketing"=> 0,
              "email_address"=> "Critical Error",
              "real_name"=> "Critical Error",
              "phone_number"=> "123456789",
              "address_1"=> "",
              "address_2"=> "",
              "city"=> "",
              "state"=> "",
              "zip_code"=> "",
              "country"=> "",
              "notes"=> null          
          ];
        }
        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
