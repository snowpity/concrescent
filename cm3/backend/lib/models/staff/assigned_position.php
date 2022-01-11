<?php

namespace CM3_Lib\models\staff;

use CM3_Lib\database\Column as cm_Column;

class assigned_position extends CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Staff_Assigned_Positions';
        $this->ColumnDefs = array(
            'position_id'		=> new cm_Column('INT', null, false),
            'staff_id' 			=> new cm_Column('BIGINT', null, false),
            'onboard_completed' => new cm_Column('BOOLEAN', null, false, defaultValue: 'false'),
            'onboard_meta'			=> new cm_Column('TEXT', null, true),
            'date_created'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP'),
            'date_modified'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP', false, 'ON UPDATE CURRENT_TIMESTAMP'),


        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array('position_id'=>false,'staff_id'=>false);
        $this->DefaultSearchColumns = array('position_id','staff_id');
    }
}

//TODO: Onboarding forms and flows
