<?php

namespace CM3_Lib\Action\Staff\BadgeType;

use CM3_Lib\models\staff\badgetype;
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
    public function __construct(private Responder $responder, private badgetype $badgetype)
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

        $consideredNull = date_create('1971-01-01');
        if (empty($data['start_date']) || (date_create($data['start_date']) < $consideredNull)) {
            $data['start_date'] = null;
        }
        if (empty($data['end_date']) || (date_create($data['end_date']) < $consideredNull)) {
            $data['end_date'] =null;
        }
        // Invoke the Domain with inputs and retain the result
        $data = $this->badgetype->Create($data);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
