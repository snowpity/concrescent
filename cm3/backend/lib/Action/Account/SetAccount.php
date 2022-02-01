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

class SetAccount
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
        $indata = (array)$request->getParsedBody();
        $data = array('id'=>$request->getAttribute('contact_id'));
        //Filter in the allowed fields
        foreach (array(
                 'allow_marketing',
                 'email_address',
                 'real_name',
                 'phone_number',
                 'address_1',
                 'address_2',
                 'city',
                 'state',
                 'zip_code',
                 'country'
               ) as $allowed) {
            if (isset($indata[$allowed])) {
                $data[$allowed] = $indata[$allowed];
            }
        }

        //Do the update!
        $updateResult = $this->contact->Update($data);
        if ($updateResult === false) {
            return $this->responder
            ->withJson($response, array('error'=>array('message'=>'Something went wrong.')))
            ->withStatus(400);
        }

        //Fetcj the authenticated user's info
        $result = $this->contact->GetByIDorUUID($data['id'], null, array(
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
          'country'
        ));
        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
