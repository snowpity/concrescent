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
use CM3_Lib\util\PermEvent;

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
        $departments = $this->s_department->Search(array('id','parent_id','display_order','name','email_primary','email_secondary' ,'description' ), [new SearchTerm('event_id', $event_id)]);
        $positions = $this->s_position->Search(new View(
            array('id','department_id','name','is_exec','description','desired_count'),
            array(
                new Join($this->s_department, array(
                'id' => 'department_id',
                new SearchTerm('event_id', $event_id),
                new SearchTerm('active', 1),
            ), alias: 'd'),)
        ), [new SearchTerm('active', 1)]);
        $staffView = new View(array(
            new SelectColumn('position_id'),
            new SelectColumn('staff_id'),
            new SelectColumn('display_id', JoinedTableAlias:'s'),
            new SelectColumn('real_name', JoinedTableAlias:'s'),
            new SelectColumn('fandom_name', JoinedTableAlias:'s'),
            new SelectColumn('name_on_badge', JoinedTableAlias:'s'),
            new SelectColumn('application_status', JoinedTableAlias:'s'),
            new SelectColumn('application_status', JoinedTableAlias:'s'),
            new SelectColumn('name', Alias:'Position_Name', JoinedTableAlias:'p'),
            new SelectColumn('is_exec', JoinedTableAlias:'p'),
            new SelectColumn('description', Alias:'Position_Description', JoinedTableAlias:'p'),
            new SelectColumn('onboard_completed'),
            new SelectColumn('onboard_meta'),
        ), array(
            //Link the department and position stuff
            new Join($this->s_position, array(
                'id' => 'position_id',
                new SearchTerm('active', 1),
            ), alias: 'p'),
            new Join($this->s_department, array(
                'id' => new SearchTerm('department_id', 'id', JoinedTableAlias:'p'),
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
        ));
        //Do we have contact permissions?
        if ($this->CurrentUserInfo->hasEventPerm(PermEvent::Contact_Full)) {
            $staffView->Columns[] = new SelectColumn('email_address', JoinedTableAlias:'c');
            $staffView->Columns[] = new SelectColumn('phone_number', JoinedTableAlias:'c');

            $staffView->Joins[] =new Join($this->contact, array(
                'id' => new SearchTerm('contact_id', 'id', JoinedTableAlias:'s'),
            ), alias: 'c');
        }
        //$this->s_assignedposition->debugThrowBeforeSelect = true;
        $assignedpositions = $this->s_assignedposition->Search($staffView);


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
        foreach ($assignedpositions as $avalue) {
            $avalue['type'] = 'staff';
            $avalue['tid'] = 's'.$avalue['staff_id'].'p'.$avalue['position_id'];
            $positions[$avalue['position_id']]['children'][] = $avalue;
        }
        //Set all the positions into their departments
        foreach ($positions as $pvalue) {
            $pvalue['type'] = 'position';
            $pvalue['tid'] = 'p'.$pvalue['id'];
            $departments[$pvalue['department_id']]['children'][] = $pvalue;
        }
        //Set all the sub-departments into their parent departments
        foreach ($departments as &$dvalue) {
            $dvalue['type'] = 'department';
            $dvalue['tid'] = 'd'.$dvalue['id'];
            if ($dvalue['parent_id'] != null) {
                $departments[$dvalue['parent_id']]['children'][] = &$dvalue;
            }
        }

        //Effect the sorts on the departments' childrens
        foreach ($departments as &$value) {
            usort($value['children'], function ($a, $b) {
                switch ($a['type']) {
                    case 'staff':
                    {
                        switch ($b['type']) {
                            case 'staff':
                                //Staff-staff just compare real_name
                                return strcmp($a["real_name"], $b["real_name"]);
                            case 'position':
                            case 'department':
                            //Staff-position/department always below
                                return 1;

                        }
                    }
                    break;
                    case 'position':
                    {
                        switch ($b['type']) {
                            case 'position':
                                //position-position just compare exec status, execs first
                                return -1 * ($a["is_exec"] <=> $b["is_exec"]);
                            case 'staff':
                                return -1;
                            case 'department':
                                return 1;

                        }
                    }
                    break;
                    case 'department':
                    {
                        switch ($b['type']) {
                            case 'department':
                                //position-position just compare exec status, execs first
                                return $a["display_order"] <=> $b["display_order"];
                            case 'staff':
                            case 'position':
                                //department-Staff/position always below
                                return -1;

                        }
                    }
                    break;
                }
            });
        }
        // usort($departments, function ($a, $b) {
        //     return $a["display_order"] <=> $b["display_order"];
        // });
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
