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
final class PostPrint
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
        $data = (array)$request->getParsedBody();
        $bid = $params['badge_id'];
        $cx = $params['context_code'];

        $current = $this->badgeinfo->GetSpecificBadge($bid, $cx, true);

        if ($current === false) {
            throw new HttpNotFoundException($request);
        }
        if ($current['payment_status'] != 'Completed') {
            throw new HttpBadRequestException($request, 'Badge is not in an allowed state!');
        }

        //Insert the print record
        $printJob = $this->printjob->Create([
            'event_id'=> $request->getAttribute('event_id'),
            'format_id' => $data['format_id'],
            'state' => ($data['localPrinted'] == true) ? 'Completed' : 'Queued',
            'meta' => json_encode($data['meta']),
            'data' =>  json_encode($data['data']),
            'result' => ''
        ]);

        $result['time_printed'] = date('Y-m-d H:i:s');
        $result['printjob_id'] = $printJob['id'];
        $result['printjob_status'] = ($data['localPrinted'] == true) ? 'Completed' : 'Queued';

        //Finalize their checkin
        $didUpdate = $this->badgeinfo->UpdateSpecificBadgeUnchecked(
            $bid,
            $cx,
            $result
        );

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
