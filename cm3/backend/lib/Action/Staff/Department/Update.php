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
final class Update
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
        private assignedposition $assignedposition,
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

        if (!$this->department->verifyDepartmentBelongsToEvent($params['id'], $request->getAttribute('event_id'))) {
            throw new HttpBadRequestException($request, 'Department does not belong to current event');
        }

        //Ensure consistency with the enpoint being posted to
        $data['id'] = $params['id'];
        unset($data['event_id']);
        unset($data['date_created']);
        unset($data['date_modified']);

        $setPositions = $data['positions'];

        // Invoke the Domain with inputs and retain the result
        $data = $this->department->Update($data);

        //Sync the positions

        $currentPositions = $this->position->Search(
            new View(
                [
                'id',
                new SelectColumn('assigned', false, 'IFNULL(?,0)', 'assigned_count', JoinedTableAlias:'ap'),
                ],
                [
                new Join($this->assignedposition, ['position_id'=>'id'], 'LEFT', 'ap', [
                    new SelectColumn('position_id', true),
                    new SelectColumn('position_id', false, 'count(?)', 'assigned')
                ])
            ]
            ),
            array(
            new SearchTerm('department_id', $data['id'])
        )
        );


        //Process adds
        foreach (array_udiff($setPositions, $currentPositions, array($this,'compareID')) as $newPosition) {
            //ensure the key isn't specified
            unset($newPosition['id']);
            $newPosition['department_id'] = $data['id'];
            $this->position->Create($newPosition);
        }
        //Process removes
        foreach (array_udiff($currentPositions, $setPositions, array($this,'compareID')) as $deletedPosition) {
            $deletedPosition['department_id'] = $data['id'];
            $this->position->Delete($deletedPosition);
        }
        //Process modifications
        foreach (array_uintersect($setPositions, $currentPositions, array($this,'compareID')) as $modifiedPosition) {
            $modifiedPosition['department_id'] = $data['id'];
            $this->position->Update($modifiedPosition);
        }


        // Build the HTTP response
        return $this->responder
            ->withJson($response, $data);
    }
    public function compareID($left, $right, $idName = 'id')
    {
        //Handle not defineds
        if (!isset($left[$idName])) {
            return 1;
        } else {
            if (!isset($right[$idName])) {
                return -1;
            } else {
                //Spaceship!
                return $left[$idName] <=> $right[$idName];
            }
        }
    }
}
