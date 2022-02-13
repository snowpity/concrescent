<?php

namespace CM3_Lib\Action\Filestore;

use CM3_Lib\models\filestore;
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
    public function __construct(private Responder $responder, private filestore $filestore)
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
        //Confirm group belongs to event
        $current = $this->filestore->GetByID($params['id'], array('id'));
        if ($current === false) {
            throw new HttpNotFoundException($request);
        }
        if ($current['event_id'] != $request->getAttribute('event_id')) {
            throw new HttpBadRequestException($request, 'Filestore item does not belong to the current event!');
        }

        $data = $this->group->Delete(array('id'=>$params['id']));

        // Build the HTTP response
        return $this->responder
             ->withJson($response, $data);
    }
}
