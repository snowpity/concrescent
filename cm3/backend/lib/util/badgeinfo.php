<?php

namespace CM3_Lib\util;

use CM3_Lib\database\SelectColumn;
use CM3_Lib\database\View;
use CM3_Lib\database\Join;
use CM3_Lib\database\SearchTerm;

use CM3_Lib\models\attendee\badgetype as a_badge_type;
use CM3_Lib\models\application\badgetype as g_badge_type;
use CM3_Lib\models\staff\badgetype as s_badge_type;
use CM3_Lib\models\attendee\badge as a_badge;
use CM3_Lib\models\application\submission as g_badge_submission;
use CM3_Lib\models\application\submissionapplicant as g_badge;
use CM3_Lib\models\application\group as g_group;
use CM3_Lib\models\staff\badge as s_badge;

/**
 * Action.
 */
final class badgeinfo
{
    public function __construct(
        private a_badge_type $a_badge_type,
        private g_badge_type $g_badge_type,
        private s_badge_type $s_badge_type,
        private a_badge $a_badge,
        private g_badge $g_badge,
        private s_badge $s_badge,
        private g_group $g_group,
        private g_badge_submission $g_badge_submission
    ) {
    }


    private $selectColumns = array(
      'id',
      'display_id',
      'real_name',
      'fandom_name',
      'name_on_badge',
      'date_of_birth',
      'notify_email',
      'time_printed',
      'time_checked_in'
      //View will add:
      //context_code
      //application_status
      //badge_type_id
      //payment_status
      //badge_type_name

      //Full view will add:
      //notes
      //large_name
      //small_name
      //application_name
      //assigned_slot_name (work in progress name)
      //assigned_department
      //assigned_position
      //assigned_department_and_position_concat
      //assigned_department_and_position_hyphen
      //badge_id_display
      //uuid
      //qr_data

    );
    private $event_id;
    public function SetEventId($event_id)
    {
        $this->event_id = $event_id;
    }

    public function GetSpecificBadge($id, $context_code, $full)
    {
        $result = false;
        switch ($context_code) {
            case 'A':
                $result =  $this->getASpecificBadge($id, $this->a_badge, $this->a_badge_type, 'A', $full ? $this->badgeViewFullAddAttendee() : null);
                // no break
            case 'S':
                $result =  $this->getASpecificBadge($id, $this->s_badge, $this->s_badge_type, 'S', $full ? $this->badgeViewFullAddStaff() : null);
                // no break
            default:
                $result =  $this->getASpecificGroupBadge($id, $full ? $this->badgeViewFullAddGroup() : null);
        }

        if ($result === false || !$full) {
            return $result;
        }
        return $this->addComputedColumns($result);
    }


    public function SearchSpecificBadge($uuid, $context_code, $full)
    {
        $result = false;
        switch ($context_code) {
            case 'A':
                $result =  $this->searchASpecificBadge($uuid, $this->a_badge, $this->a_badge_type, 'A', $full ? $this->badgeViewFullAddAttendee() : null);

                break;
            case 'S':
                $result =  $this->searchASpecificBadge($uuid, $this->s_badge, $this->s_badge_type, 'S', $full ? $this->badgeViewFullAddStaff() : null);
                break;
            default:
                $result =  $this->searchASpecificGroupBadge($uuid, $full ? $this->badgeViewFullAddGroup() : null);
        }
        if ($result === false || !$full) {
            return $result;
        }
        return $this->addComputedColumns($result);
    }

    public function SearchBadges($find, $order, $limit, $offset)
    {
        $whereParts = array(
            new SearchTerm('real_name', $find, Raw: 'MATCH(`real_name`, `fandom_name`, `notify_email`, `ice_name`, `ice_email_address`) AGAINST (? IN NATURAL LANGUAGE MODE) ')
        );
        // Invoke the Domain with inputs and retain the result
        $a_data = $this->a_badge->Search($this->badgeView($this->a_badge_type, 'A'), $whereParts, $order, $limit, $offset);
        $s_data = $this->s_badge->Search($this->badgeView($this->s_badge_type, 'S'), $whereParts, $order, $limit, $offset);
        $g_data = $this->g_badge->Search($this->groupBadgeView(), $whereParts, $order, $limit, $offset);

        return array_merge($a_data, $s_data, $g_data);
    }

    public function getASpecificBadge($id, $badge, $badgetype, $contextCode, $addView)
    {
        $view = $this->badgeView($badgetype, $contextCode);
        if ($addView instanceof View) {
            if (!is_null($addView->Columns)) {
                $view->Columns = array_merge($view->Columns, $addView->Columns);
            }
            if (!is_null($addView->Joins)) {
                $view->Joins = array_merge($view->Joins, $addView->Joins);
            }
        }
        return $badge->GetByIDorUUID($id, null, $view);
    }
    public function getASpecificGroupBadge($id, $addView)
    {
        $view = $this->groupBadgeView();
        if ($addView instanceof View) {
            if (!is_null($addView->Columns)) {
                $view->Columns = array_merge($view->Columns, $addView->Columns);
            }
            if (!is_null($addView->Joins)) {
                $view->Joins = array_merge($view->Joins, $addView->Joins);
            }
        }
        return $this->g_badge->GetByIDorUUID($id, null, $view);
    }



    public function searchASpecificBadge($uuid, $badge, $badgetype, $contextCode, $addView)
    {
        $view = $this->badgeView($badgetype, $contextCode);
        if ($addView instanceof View) {
            if (!is_null($addView->Columns)) {
                $view->Columns = array_merge($view->Columns, $addView->Columns);
            }
            if (!is_null($addView->Joins)) {
                $view->Joins = array_merge($view->Joins, $addView->Joins);
            }
        }
        return $badge->GetByIDorUUID(null, $uuid, $view);
    }
    public function searchASpecificGroupBadge($uuid, $addView)
    {
        $view = $this->groupBadgeView();
        if ($addView instanceof View) {
            if (!is_null($addView->Columns)) {
                $view->Columns = array_merge($view->Columns, $addView->Columns);
            }
            if (!is_null($addView->Joins)) {
                $view->Joins = array_merge($view->Joins, $addView->Joins);
            }
        }
        return $this->g_badge->GetByIDorUUID(null, $uuid, $view);
    }

    public function badgeView($badgetype, $contextCode)
    {
        return new View(
            array_merge(
                $this->selectColumns,
                array(
               new SelectColumn('context_code', EncapsulationFunction: "'".$contextCode."'", Alias:'context_code'),
               new SelectColumn('application_status', EncapsulationFunction: "''", Alias:'application_status'),
               'badge_type_id',
               'payment_status',
               new SelectColumn('name', Alias:'badge_type_name', JoinedTableAlias:'typ')
             )
            ),
            array(

               new Join(
                   $badgetype,
                   array(
                     'id' => 'badge_type_id',
                     new SearchTerm('event_id', $this->event_id)
                   ),
                   alias:'typ'
               )
             )
        );
    }
    public function badgeViewFullAddAttendee()
    {
        return new View(
            array(
                'notes',
                'uuid'
            ),
            array(

            ),
        );
    }
    public function badgeViewFullAddStaff()
    {
        return new View(
            array(
                'notes',
                'uuid'
            )
        );
    }

    public function groupBadgeView()
    {
        return new View(
            array_merge(
                $this->selectColumns,
                array(
                   new SelectColumn('context_code', JoinedTableAlias:'grp'),
                   new SelectColumn('application_status', EncapsulationFunction: "''", Alias:'application_status'),
                   new SelectColumn('badge_type_id', JoinedTableAlias:'bs'),
                   new SelectColumn('payment_status', JoinedTableAlias:'bs'),
                   new SelectColumn('name', Alias:'badge_type_name', JoinedTableAlias:'typ')
                 )
            ),
            array(
                   new Join(
                       $this->g_badge_submission,
                       array(
                         'id' => 'application_id',
                       ),
                       alias:'bs'
                   ),

                   new Join(
                       $this->g_badge_type,
                       array(
                         'id' =>new SearchTerm('badge_type_id', null, JoinedTableAlias: 'bs'),
                       ),
                       alias:'typ'
                   ),
                  new Join(
                      $this->g_group,
                      array(
                        'id' => new SearchTerm('group_id', null, JoinedTableAlias: 'typ'),
                        new SearchTerm('event_id', $this->event_id)
                      ),
                      alias:'grp'
                  )
                 )
        );
    }
    public function badgeViewFullAddGroup()
    {
        return new View(
            array(
                'notes',
                'uuid'
            ),
            array()
        );
    }

    public function addComputedColumns($result)
    {

        //Add some computed helper columns
        switch ($result['name_on_badge']) {
            case 'Fandom Name Large, Real Name Small':
                $result['large_name'] = $result['fandom_name'];
                $result['small_name'] = $result['real_name'];
                break;
            case 'Real Name Large, Fandom Name Small':
                $result['large_name'] = $result['real_name'];
                $result['small_name'] = $result['fandom_name'];
                break;
            case 'Fandom Name Only':
                $result['only_name'] = $result['fandom_name'];
                break;
            case 'Real Name Only':
                $result['only_name'] = $result['real_name'];
                break;
        }
        $result['badge_id_display'] = $result['context_code'] . $result['display_id'];
        $result['qr_data'] = 'CM*' . $result['context_code'] . $result['display_id'] . '*' . $result['uuid'];
        return $result;
    }
}
