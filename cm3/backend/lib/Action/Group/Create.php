<?php

namespace CM3_Lib\Action\Group;

use CM3_Lib\models\application\group;
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
    public function __construct(private Responder $responder, private group $group)
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
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        // Extract the form data from the request body
        $data = (array)$request->getParsedBody();

        //Ensure we're only attempting to create a group for the current Event
        $data['event_id'] = $request->getAttribute('event_id');

        // Invoke the Domain with inputs and retain the result
        $data = $this->group->Create($data);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
