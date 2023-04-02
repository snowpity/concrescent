<?php

namespace CM3_Lib\Action\Badge\Format;

use CM3_Lib\database\SearchTerm;
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
    public function __construct(private Responder $responder, private format $format)
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
        //TODO: Actually do something with submitted data. Also, provide some sane defaults

        $result = $this->format->GetByID($params['id'], '*');
        if ($result === false) {
            throw new HttpNotFoundException($request);
        }
        if ($result['event_id'] != $request->getAttribute('event_id')) {
            throw new HttpBadRequestException($request, 'Badge Format does not belong to the current event!');
        }

        //Convert layout to array
        $lRaw = $result['layout'];
        $result['layout'] = json_decode($result['layout']);
        if (json_last_error()) {
            $result['layout_raw'] = $lRaw;
        }

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
