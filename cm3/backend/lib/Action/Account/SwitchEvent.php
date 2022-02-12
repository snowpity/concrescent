<?php

namespace CM3_Lib\Action\Account;

use CM3_Lib\database\SearchTerm;

use CM3_Lib\models\admin\user;
use CM3_Lib\models\eventinfo;
use CM3_Lib\util\Permissions;
use CM3_Lib\util\TokenGenerator;

use Branca\Branca;
use MessagePack\MessagePack;
use MessagePack\Packer;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SwitchEvent
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(private Responder $responder, private TokenGenerator $TokenGenerator)
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
        $event_id = $data['event_id'] ?? null;

        $result = $this->TokenGenerator->forUser($request->getAttribute('contact_id'), $event_id);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
