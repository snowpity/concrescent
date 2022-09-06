<?php

namespace CM3_Lib\Action\Staff\Department;

use CM3_Lib\models\staff\department;
use CM3_Lib\models\staff\position;
use CM3_Lib\util\CurrentUserInfo;
use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action.
 */
final class Create
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(
        private Responder $responder,
        private CurrentUserInfo $CurrentUserInfo,
        private department $department,
        private position $position
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

        //Ensure we're making a badge type with the associated event
        $data['event_id'] = $this->CurrentUserInfo->GetEventId();

        //Make sure we don't have an ID, date_created, date_modified
        unset($data['id']);
        unset($data['date_created']);
        unset($data['date_modified']);

        $setPositions = $data['positions'];


        // Invoke the Domain with inputs and retain the result
        $data = $this->department->Create($data);
        //Process adds
        foreach ($setPositions as $newPosition) {
            //ensure the key isn't specified
            unset($newPosition['id']);
            $newPosition['department_id'] = $data['id'];
            $this->position->Create($newPosition);
        }

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
}
