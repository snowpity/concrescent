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
final class Delete
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
        //Confirm printjob belongs to event
        $current = $this->printjob->GetByID($params['id'], array('event_id'));
        if ($current === false) {
            throw new HttpNotFoundException($request);
        }
        if ($current['event_id'] != $request->getAttribute('event_id')) {
            throw new HttpBadRequestException($request, 'PrintJob does not belong to the current event!');
        }

        $data = $this->printjob->Update(array('id'=>$params['id'],'state'=>'Cancelling'));

        // We don't delete, just deactivate
        //$data = $this->printjob->Delete(array('id'=>$params['id']));

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
