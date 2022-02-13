<?php

namespace CM3_Lib\Action\LocationMap\Coord;

use CM3_Lib\models\application\locationmap;
use CM3_Lib\models\application\locationcoord;
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
    public function __construct(private Responder $responder, private locationcoord $locationcoord, private locationmap $locationmap)
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

        if ($this->locationmap->verifyMapBelongsToEvent($params['map_id'], $request->getAttribute('event_id'))) {
            throw new HttpBadRequestException($request, 'Location Map does not belong to the current event!');
        }
        //Ensure we're only attempting to create a location for the current Event
        $data['event_id'] = $request->getAttribute('event_id');
        $data['map_id'] = $request->params['map_id');

        // Invoke the Domain with inputs and retain the result
        $data = $this->locationcoord->Create($data);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
