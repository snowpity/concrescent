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
        // Extract the form data from the request body
        $data = (array)$request->getParsedBody();
        $data['id'] = $params['id'];

        $result = $this->filestore->GetByID($params['id'], array('event_id'));
        if ($result === false) {
            throw new HttpNotFoundException($request);
        }
        if ($result['event_id'] != $request->getAttribute('event_id')) {
            throw new HttpBadRequestException($request, 'Filestore item does not belong to the current event!');
        }

        $uploadedFiles = $request->getUploadedFiles();

        if (isset($uploadedFiles['data'])) {
            $data['data'] = $uploadedFiles['data']->getStream();
        }
        //echo is_resource($data['data']);
        //die(print_r($data, true));

        // Invoke the Domain with inputs and retain the result
        $data = $this->filestore->Update($data);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
