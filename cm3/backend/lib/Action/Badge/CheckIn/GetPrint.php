<?php

namespace CM3_Lib\Action\Badge\CheckIn;

use CM3_Lib\database\SelectColumn;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;
use CM3_Lib\database\SearchTerm;
use CM3_Lib\models\badge\printjob;

use CM3_Lib\util\badgeinfo;
use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

/**
 * Action.
 */
final class GetPrint
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(
        private Responder $responder,
        private badgeinfo $badgeinfo,
        private printjob $printjob
    ) {
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
        $bid = $params['badge_id'];
        $cx = $params['context_code'];
        $jid = $params['job_id'];


        //Insert the print record
        $printJob = $this->printjob->GetByID($jid, [
            'state', 'result','event_id'
        ]);

        if ($printJob === false) {
            throw new HttpNotFoundException($request);
        }
        if ($printJob['event_id'] != $request->getAttribute('event_id')) {
            throw new HttpBadRequestException($request, 'Print job does not belong to the current event');
        }

        unset($printJob['event_id']);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $printJob);
    }
}
