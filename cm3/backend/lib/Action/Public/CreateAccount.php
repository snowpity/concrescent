<?php

namespace CM3_Lib\Action\Public;

use CM3_Lib\database\SearchTerm;

use CM3_Lib\models\contact;
use CM3_Lib\models\eventinfo;

use Branca\Branca;
use MessagePack\MessagePack;
use MessagePack\Packer;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CreateAccount
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(private Responder $responder, private contact $contact, private eventinfo $eventinfo, private Branca $Branca)
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
        // Extract the form data from the request body
        $data = (array)$request->getParsedBody();
        $data['event_id'] = $data['event_id'] ?? null;

        //Check if there's an account already
        $existing = $this->contact->Search(null, array(new SearchTerm('email_address', $data['email_address'])), limit:1);
        if (count($existing) > 0) {
            return $this->responder
              ->withJson($response, array('error'=>array('message'=>'Contact already exists')))
              ->withStatus(400);
        }

        $result = $this->contact->Create($data);

        //Determine the event ID if not provided
        $thedate = date("Y/m/d");
        $eventresult = $this->eventinfo->Search(
            array('id'),
            terms: array(
              //This probably doesn't work like we think?
              new SearchTerm('id', $data['event_id'], EncapsulationFunction: 'ifnull(?,0)', EncapsulationColumnOnly:false),
              new SearchTerm('', null, TermType: 'OR', subSearch:array(
                new SearchTerm('date_end', $thedate, ">="),
                new SearchTerm('active', true),
                new SearchTerm('', CompareValue: $data['event_id'], Raw: '? IS NULL')
              ))
        ),
            order: array(
            'date_start'=> false
        ),
            limit: 1
        );
        //Save the Event_id in the result in case they didn't know before
        $result['event_id'] = $eventresult[0]['id'];


        //Associate the brand new account by setting the authorization
        $packer = new Packer();
        //On;y using the basic payload, it is imnpossible for them to have permissions after all
        $tokenPayload = $packer->pack($result['id'])
          . $packer->pack($result['event_id']);

        $result['token'] = $this->Branca->encode($tokenPayload);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
