<?php

namespace CM3_Lib\Action\LocationMap;

use CM3_Lib\models\application\locationmap;
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
    public function __construct(private Responder $responder, private locationmap $locationmap)
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
        $current = $this->locationmap->GetByID($params['id'], array('id'));
        if ($current === false) {
            throw new HttpNotFoundException($request);
        }
        if ($current['event_id'] != $request->getAttribute('event_id')) {
            throw new HttpBadRequestException($request, 'Location Map does not belong to the current event!');
        }

        $data = $this->locationmap->Update(array('id'=>$params['id'],'active'=>0));

        // We don't delete, just deactivate
        //$data = $this->group->Delete(array('id'=>$params['id']));

        // Build the HTTP response
        return $this->responder
              ->withJson($response, $data);
    }
}
