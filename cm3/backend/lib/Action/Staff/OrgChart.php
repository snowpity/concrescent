<?php

namespace CM3_Lib\Action\Staff;

use CM3_Lib\database\SearchTerm;
use CM3_Lib\database\SelectColumn;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;

use CM3_Lib\models\staff\department as s_department;
use CM3_Lib\models\staff\position as s_position;
use CM3_Lib\models\staff\assignedposition as s_assignedposition;
use CM3_Lib\models\staff\badgetype as s_badge_type;
use CM3_Lib\models\staff\badge as s_badge;
use CM3_Lib\models\contact as contact;

use CM3_Lib\util\CurrentUserInfo;
use CM3_Lib\util\badgeinfo;

use CM3_Lib\Responder\Responder;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Action.
 */
final class OrgChart
{
    /**
     * The constructor.
     *
     * @param Responder $responder The responder
     * @param eventinfo $eventinfo The service
     */
    public function __construct(
        private Responder $responder,
        private s_department $s_department,
        private s_position $s_position,
        private s_assignedposition $s_assignedposition,
        private s_badge_type $s_badge_type,
        private s_badge $s_badge,
        private contact $contact,
        private CurrentUserInfo $CurrentUserInfo,
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
        $qp = $request->getQueryParams();
        //TODO: Actually do something with submitted data. Also, provide some sane defaults
        $event_id = $request->getAttribute('event_id');

        //Grab the departments, positions, and staff assigned to those positions
        $departments = $this->s_department->Search(array(), [new SearchTerm('event_id', $event_id)]);
        $positions = $this->s_position->Search(array('id','department_id','name','is_exec','description','desired_count'), [new SearchTerm('active', 1)]);
        $assignedpositions = $this->s_assignedposition->Search(
            new View(array(
                new SelectColumn('position_id'),
                new SelectColumn('staff_id'),
                new SelectColumn('display_id', JoinedTableAlias:'s'),
                new SelectColumn('real_name', JoinedTableAlias:'s'),
                new SelectColumn('fandom_name', JoinedTableAlias:'s'),
                new SelectColumn('name_on_badge', JoinedTableAlias:'s'),
                new SelectColumn('application_status', JoinedTableAlias:'s'),
                new SelectColumn('application_status', JoinedTableAlias:'s'),
                new SelectColumn('name', Alias:'Position_Name', JoinedTableAlias:'p'),
                new SelectColumn('description', Alias:'Position_Descrioption', JoinedTableAlias:'p'),
                new SelectColumn('onboard_completed'),
                new SelectColumn('onboard_meta'),
            ), array(
                //Link the department and position stuff
                new Join($this->s_position, array(
                    'id' => 'position_id',
                      new SearchTerm('active', 1),
                ), alias: 'p'),
                new Join($this->s_department, array(
                    'id' => new SearchTerm('id', 'position_id', JoinedTableAlias:'p'),
                      new SearchTerm('event_id', $event_id),
                      new SearchTerm('active', 1),
                ), alias: 'd'),
                //And now the staff badge stuff
                new Join($this->s_badge, array(
                    'id' => 'staff_id',
                      new SearchTerm('application_status', array(
                          'PendingAcceptance','Onboarding','Active'
                      ), 'IN'),
                ), alias: 's'),
            ))
        );

        //TODO: If current user has contact info reading privs...


        //First, index the departments
        $departments = array_combine(
            array_column($departments, 'id'),
            array_map('self::ect', $departments)
        );
        //and index the positions
        $positions = array_combine(
            array_column($positions, 'id'),
            array_map('self::ect', $positions)
        );

        //Set all the assigned positions into the actual positions
        foreach ($assignedpositions as $value) {
            $value['type'] = 'staff';
            $positions[$value['position_id']]['children'][] = $value;
        }
        //Set all the positions into their departments
        foreach ($positions as $value) {
            $value['type'] = 'position';
            $departments[$value['department_id']]['children'][] = $value;
        }
        //Set all the sub-departments into their parent departments
        foreach ($departments as $value) {
            $value['type'] = 'department';
            if ($value['parent_id'] != null) {
                $departments[$value['parent_id']]['children'][] = $value;
            }
        }

        //Finally, make out the result
        $result = array_values(array_filter($departments, function ($item) {
            return is_null($item['parent_id']);
        }));

        // Build the HTTP response
        return $this->responder
            ->withJson($response, $result);
    }
    //Ensure critical tree properties
    public static function ect($arrayItem)
    {
        $arrayItem['children'] = [];
        return $arrayItem;
    }
}
