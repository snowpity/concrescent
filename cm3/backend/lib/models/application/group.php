<?php

namespace CM3_Lib\models\application;

use CM3_Lib\database\Column as cm_Column;

class group extends \CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Application_Groups';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('INT', null, false, true, false, true, null, true),
            'event_id'		=> new cm_Column('INT', null, false),
            'context_code'	=> new cm_Column('VARCHAR', '3', false),
            'active'        => new cm_Column('BOOLEAN', null, false, defaultValue: 'true'),
            'can_assign'    => new cm_Column('BOOLEAN', null, false, defaultValue: 'true'), //Whether applications in the group can be assigned a location/time slot
            'order'					=> new cm_Column('TINYINT', null, false),
            'name'          => new cm_Column('VARCHAR', '255', false),
            'menu_icon'     => new cm_Column('VARCHAR', '255', true),
            'description'   => new cm_Column('TEXT', null, true),
            'appplication_name1'          => new cm_Column('VARCHAR', '255', false),
            'appplication_name2'          => new cm_Column('VARCHAR', '255', true),

            'date_created'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP'),
            'date_modified'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP', false, 'ON UPDATE CURRENT_TIMESTAMP'),
            'notes'			=> new cm_Column('TEXT', null, true),

        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('id','context_code','name','menu_icon');
    }
}
