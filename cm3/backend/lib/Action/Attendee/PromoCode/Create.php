<?php

namespace CM3_Lib\Action\Attendee\PromoCode;

use CM3_Lib\models\attendee\promocode;
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

        //Ensure we're making a badge type with the associated event
        $data['event_id'] = $request->getAttribute('event_id');
        //Make sure we don't have an ID, date_created, date_modified
        unset($data['id']);
        unset($data['date_created']);
        unset($data['date_modified']);
        unset($data['dates_available']);

        if (empty($data['start_date'])) {
            $data['start_date'] = null;
        }
        if (empty($data['end_date'])) {
            $data['end_date'] = null;
        }

        // Invoke the Domain with inputs and retain the result
        $data = $this->promocode->Create($data);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
