<?php

namespace CM3_Lib\Action\Public;

use CM3_Lib\database\SearchTerm;

use CM3_Lib\models\admin\user;
use CM3_Lib\models\eventinfo;

use Branca\Branca;
use MessagePack\MessagePack;
use MessagePack\Packer;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Login
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(private Responder $responder, private user $user, private eventinfo $eventinfo, private Branca $Branca)
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
        $data['event_id'] = $data['event_id'] ?? null;
        //Note that this is only used for AdminUser accounts; magic links come with a token already.

        $founduser = $this->user->Search('*', array(
          new SearchTerm("username", $data['username']),
          new SearchTerm("active", 1)
        ), limit:1);

        if (count($founduser) == 0) {
            //Todo: Rate limit?
            return $this->responder
                ->withJson($response, array('error'=>array('message'=>'Unable to log in with supplied credentials.')))
                ->withStatus(401);
        }

        //Since we asked for (and got) one, bring it up a level for ease of use
        $founduser = $founduser[0];

        //Authenticate the password
        if (!password_verify($data['password'], $founduser['password'])) {
            //Nope!
            return $this->responder
                ->withJson($response, array('error'=>array('message'=>'Unable to log in with supplied credentials.')))
                ->withStatus(401);
        }

        //Looks good, start making their token!

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
        $data['event_id'] = $eventresult[0]['id'];


        //Associate the brand new account by setting the authorization
        $packer = new Packer();
        //Initialize payload
        $tokenPayload = $packer->pack($founduser['contact_id'])
          . $packer->pack($data['event_id']);

        //TODO: Load up and select their permissions for the selected event

        $result = array();
        $result['adminOnly'] = $founduser['adminOnly'];
        $result['preferences'] = $founduser['preferences'];
        $result['token'] = $this->Branca->encode($tokenPayload);


        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
