<?php

namespace CM3_Lib\Action\Public;

use CM3_Lib\util\TokenGenerator;

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
        $result = $this->TokenGenerator->forUser(
            $request->getAttribute('contact_id'),
            $request->getAttribute('event_id')
        );

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
