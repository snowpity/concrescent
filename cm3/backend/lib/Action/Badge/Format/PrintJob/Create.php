<?php

namespace CM3_Lib\Action\Badge\Format\PrintJob;

use CM3_Lib\models\badge\printjob;
use CM3_Lib\models\badge\format;
use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

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
    public function __construct(private Responder $responder, private printjob $printjob, private format $format)
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

        //Ensure we're only attempting to create a printjob for the current Event
        $result = $this->format->GetByID($params['format_id'], array('event_id'));
        if ($result === false) {
            throw new HttpNotFoundException($request);
        }
        if ($result['event_id'] != $request->getAttribute('event_id')) {
            throw new HttpBadRequestException($request, 'Badge Format does not belong to the current event!');
        }

        //TODO: Work on permissions

        $data['event_id'] = $result['event_id'];
        $data['format_id'] = $params['format_id'];

        // Invoke the Domain with inputs and retain the result
        $data = $this->printjob->Create($data);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
