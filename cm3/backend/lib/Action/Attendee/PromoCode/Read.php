<?php

namespace CM3_Lib\Action\Attendee\PromoCode;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\models\attendee\promocode;
use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
    public function __construct(private Responder $responder, private promocode $promocode)
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


        // Invoke the Domain with inputs and retain the result
        $result = $this->promocode->GetByID($params['id'], '*');

        //Confirm badge belongs to a promocode in this event
        if ($result === false) {
            throw new HttpNotFoundException($request);
        }

        if (!$result['event_id'] == $request->getAttribute('event_id')) {
            throw new HttpBadRequestException($request, 'PromoCode does not belong to current event');
        }

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
