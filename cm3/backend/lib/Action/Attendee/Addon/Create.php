<?php

namespace CM3_Lib\Action\Attendee\Addon;

use CM3_Lib\models\attendee\addon;
use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action.
 */
final class Create
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(private Responder $responder, private addon $addon)
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

        //Ensure we're making a addon with the associated event
        $data['event_id'] = $request->getAttribute('event_id');

        // Invoke the Domain with inputs and retain the result
        $data = $this->addon->Create($data);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
