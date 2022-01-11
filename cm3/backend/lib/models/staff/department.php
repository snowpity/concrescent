<?php

namespace CM3_Lib\models\staff;

use CM3_Lib\database\Column as cm_Column;

class department extends CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Staff_Departments';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('INT', null, false, true, false, true, null, true),
            'event_id'		=> new cm_Column('INT', null, false),
      'active'        => new cm_Column('BOOLEAN', null, false, defaultValue: 'false'),
      'parent_id' => new cm_Column('INT', null, true),
      'name'          => new cm_Column('VARCHAR', '255', false),
      'description'   => new cm_Column('TEXT', null, true),
      'email_primary' => new cm_Column('VARCHAR', '255', false),
      'email_secondary'=> new cm_Column('VARCHAR', '255', false),

            'date_created'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP'),
            'date_modified'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP', false, 'ON UPDATE CURRENT_TIMESTAMP'),
            'notes'			=> new cm_Column('TEXT', null, true),

        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('id','parent','name','email_primary');
    }
}
