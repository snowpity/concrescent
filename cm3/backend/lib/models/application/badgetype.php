<?php

namespace CM3_Lib\models\application;

use CM3_Lib\database\Column as cm_Column;
use Slim\Exception\HttpBadRequestException;

class badgetype extends \CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Application_Badge_Types';
        $this->ColumnDefs = array(
            'id'                        => new cm_Column('INT', null, false, true, false, true, null, true),
            'group_id'                  => new cm_Column('INT', null, false),
            'active'                    => new cm_Column('BOOLEAN', null, false, defaultValue: 'false'),
            'display_order'             => new cm_Column('INT', null, false),
            'name'                      => new cm_Column('VARCHAR', '255', false),
            'description'               => new cm_Column('TEXT', null, true),
            'rewards'                   => new cm_Column('TEXT', null, true),
            'max_applicant_count'       => new cm_Column('INT', null, false),
            'max_assignment_count'      => new cm_Column('INT', null, false),
            'base_price'                => new cm_Column('DECIMAL', '7,2', false),

            'base_applicant_count'      => new cm_Column('INT', null, false),
            'base_assignment_count'     => new cm_Column('INT', null, false),
            'price_per_applicant'       => new cm_Column('DECIMAL', '7,2', false),
            'price_per_assignment'      => new cm_Column('DECIMAL', '7,2', false),
            'max_prereg_discount'       => new cm_Column(
                'ENUM',
                array(
                  'No Discount',
                  'Price per Applicant',
                  'Price per Assignment',
                  'Total Price',
              ),
                false
            ),
            'payable_onsite'            => new cm_Column('BOOLEAN', null, false, defaultValue: 'false'),
            'max_total_applications'    => new cm_Column('INT', null, false, defaultValue: '0'),
            'max_total_applicants'      => new cm_Column('INT', null, false, defaultValue: '0'),
            'max_total_assignments'     => new cm_Column('INT', null, false, defaultValue: '0'),
            'start_date'                => new cm_Column('DATE', null, false),
            'end_date'                  => new cm_Column('DATE', null, false),
            'min_age'                   => new cm_Column('INT', null, true),
            'max_age'                   => new cm_Column('INT', null, true),
            'active_override_code'      => new cm_Column('VARCHAR', '255', true),
            'date_created'              => new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP'),
            'date_modified'             => new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP', false, 'ON UPDATE CURRENT_TIMESTAMP'),
            'notes'                     => new cm_Column('TEXT', null, true),
            //Generated columns
            'dates_available'           => new cm_Column('VARCHAR', '50', null, customPostfix: 'GENERATED ALWAYS as (concat(case `start_date` is null when true then \'forever\' else `start_date` end,\' to \', case end_date is null when true then \'forever\' else `end_date` end)) VIRTUAL'),
        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('id','name','base_price','base_applicant_count','dates_available');
    }

    public function verifyBadgeTypeBelongsToEvent(int $id, int $event_id)
    {
        //We need the group for this, go ahead and make it
        $group = new group($this->cm_db);
        $bt = $this->GetByID($id, new View(
            array(new SelectColumn('event_id', JoinedTableAlias:'grp')),
            array(
              new Join(
                  $group,
                  array(
                    'id' => 'group_id'
                  ),
                  alias:'grp'
              )
            )
        ));
        if ($bt === false) {
            return false;
        }
        if ($bt['event_id'] != $event_id) {
            return false;
        }
        return true;
    }

    public function verifyBadgeTypeBelongsToGroup(int $id, int $group_id)
    {
        $bt = $this->GetByIDorUUID($id, array('group_id'));
        if ($bt === false) {
            return false;
        }
        if ($bt['group_id'] != $group_id) {
            return false;
        }
        return true;
    }
}
