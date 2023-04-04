<?php

namespace CM3_Lib\Action\Badge\PrintJob;

use CM3_Lib\database\SearchTerm;
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
final class Read
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
        $qp = $request->getQueryParams();
        $includeFormat = $qp['includeFormat'] ?? 'false';
        //TODO: Actually do something with submitted data. Also, provide some sane defaults

        $result = $this->printjob->GetByID($params['id'], '*');
        if ($result === false) {
            throw new HttpNotFoundException($request);
        }
        if ($result['event_id'] != $request->getAttribute('event_id')) {
            throw new HttpBadRequestException($request, 'Print job does not belong to the current event!');
        }

        if (isset($result['data'])) {
            //Move into raw for safe-keeping
            $result['data_raw'] = $result['data'];
            $result['data'] = json_decode($result['data']);
            if (0==json_last_error()) {
                unset($result['data_raw']);
            }
        }
        if (isset($result['meta'])) {
            //Move into raw for safe-keeping
            $result['meta_raw'] = $result['meta'];
            $result['meta'] = json_decode($result['meta']);
            if (0==json_last_error()) {
                unset($result['meta_raw']);
            }
        }

        if ($includeFormat == 'true') {
            $result['format'] = $this->format->GetByID($result['format_id'], '*');
            if ($result['format']!==false) {
                //Convert layout to array
                $lRaw = $result['format']['layout'];
                $result['format']['layout'] = json_decode($result['format']['layout']);
                if (json_last_error()) {
                    $result['format']['layout_raw'] = $lRaw;
                }
            }
        }

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
