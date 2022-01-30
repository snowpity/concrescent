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

class RefreshToken
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(private Responder $responder, private Branca $Branca)
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
        $result['id'] = $request->getAttribute('contact_id');
        $result['event_id'] = $request->getAttribute('event_id');
        $result['token'] = $this->Branca->encode($request->getAttribute('rawtoken'));

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
