<?php

namespace CM3_Lib\Action\Staff\Department;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;
use CM3_Lib\database\SelectColumn;
use CM3_Lib\models\staff\department;
use CM3_Lib\models\staff\position;
use CM3_Lib\models\staff\assignedposition;
use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
    public function __construct(
        private Responder $responder,
        private department $department,
        private position $position,
        private assignedposition $assignedposition
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
        //TODO: Actually do something with submitted data. Also, provide some sane defaults


        // Invoke the Domain with inputs and retain the result
        $result = $this->department->GetByID($params['id'], '*');

        //Confirm badge belongs to a department in this event
        if ($result === false) {
            throw new HttpNotFoundException($request);
        }

        if (!$result['event_id'] == $request->getAttribute('event_id')) {
            throw new HttpBadRequestException($request, 'Department does not belong to current event');
        }

        //Fetch positions for this department
        $positions = $this->position->Search(
            new View(
                [
                'id','active','is_exec','name','description','desired_count',
                new SelectColumn('assigned', false, 'IFNULL(?,0)', 'assigned_count', JoinedTableAlias:'ap'),
                'notes'
            ],
                [
                new Join($this->assignedposition, ['position_id'=>'id'], 'LEFT', 'ap', [
                    new SelectColumn('position_id', true),
                    new SelectColumn('position_id', false, 'count(?)', 'assigned')
                ])
            ]
            ),
            array(
            new SearchTerm('department_id', $result['id'])
        )
        );
        $result['positions'] = $positions;

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
}
