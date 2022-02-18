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
final class Update
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
        $data['id'] = $params['id'];

        //Ensure we're only attempting to create a printjob for the current Event
        $fresult = $this->format->GetByID($params['format_id'], array('event_id'));
        if ($fresult === false) {
            throw new HttpNotFoundException($request);
        }
        if ($fresult['event_id'] != $request->getAttribute('event_id')) {
            throw new HttpBadRequestException($request, 'Badge Format does not belong to the current event!');
        }


        $result = $this->printjob->GetByID($params['id'], array('format_id'));
        if ($result === false) {
            throw new HttpNotFoundException($request);
        }
        if ($params['format_id'] != $result['format_id']) {
            throw new HttpBadRequestException($request, 'PrintJob does not belong to the specified format!');
        }


        // Invoke the Domain with inputs and retain the result
        $data = $this->printjob->Update($data);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
