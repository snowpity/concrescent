<?php

namespace CM3_Lib\Action\Public;

use CM3_Lib\database\SearchTerm;

use CM3_Lib\models\admin\user;
use CM3_Lib\models\eventinfo;
use CM3_Lib\models\application\group;
use CM3_Lib\util\TokenGenerator;

use Branca\Branca;
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
    public function __construct(private Responder $responder, private user $user, private TokenGenerator $TokenGenerator)
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

        //Looks good, request their token!
        $result = $this->TokenGenerator->forUser($founduser['contact_id'], $data['event_id']);
        //Add in some profile info
        $result['adminOnly'] = $founduser['adminOnly'];
        $result['preferences'] = $founduser['preferences'];

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
