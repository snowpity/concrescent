<?php

namespace CM3_Lib\Action\Staff\Department;

use CM3_Lib\models\staff\department;
use CM3_Lib\Responder\Responder;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

/**
 * Action.
 */
final class Move
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(private Responder $responder, private department $department)
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
        $data = (array) $request->getParsedBody();
        //TODO: Actually do something with submitted data. Also, provide some sane defaults


        // Invoke the Domain with inputs and retain the result
        $check = $this->department->GetByID($params['id'], ['id', 'event_id']);

        //Confirm id belongs to a department in this event
        if ($check === false)
        {
            throw new HttpNotFoundException($request);
        }

        if (!$check['event_id'] == $request->getAttribute('event_id'))
        {
            throw new HttpBadRequestException($request, 'department does not belong to current event');
        }

        //Determine parameters
        $upwards = false;
        switch ($data['direction'])
        {
            case 'up':
            case true:
            case 'true':
            case '1':
            case 1:
                $upwards = true;
        }
        $positions = $data['positions'] ?? 1;

        $result = $this->department->orderMove($params['id'], $upwards, $positions);

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}