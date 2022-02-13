<?php

namespace CM3_Lib\Action\LocationMap\Coord;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\models\application\locationmap;
use CM3_Lib\models\application\locationcoord;
use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

/**
 * Action.
 */
final class Read
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(private Responder $responder, private locationcoord $locationcoord)
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
        //TODO: Actually do something with submitted data. Also, provide some sane defaults

        if ($this->locationmap->verifyMapBelongsToEvent($params['map_id'], $request->getAttribute('event_id'))) {
            throw new HttpBadRequestException($request, 'Location Map does not belong to the current event!');
        }

        $whereParts = array(
          new SearchTerm('id', $params['id']),
          new SearchTerm('map_id', $params['map_id'])
        );

        // Invoke the Domain with inputs and retain the result
        $result = $this->locationcoord->Search("*", $whereParts, null, 1);

        if ($result === false || is_null($result) || count($result) == 0) {
            throw new HttpNotFoundException($request);
        } else {
            $data = $result[0];
        }

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
