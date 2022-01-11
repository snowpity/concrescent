<?php

namespace CM3_Lib\models\application;

use CM3_Lib\database\Column as cm_Column;

class assignments extends CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Application_Assignments';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('INT', null, false, true, false, true, null, true),
            'application_id'		=> new cm_Column('INT', null, false),
            'location_id'		=> new cm_Column('INT', null, false),
            'start_time'    => new cm_Column('DATETIME', null, false),
            'end_time'      => new cm_Column('DATETIME', null, false),
            'date_created'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP'),
            'date_modified'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP', false, 'ON UPDATE CURRENT_TIMESTAMP'),
            'notes'			=> new cm_Column('TEXT', null, true),

        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('id','application_id','location_id','start_time','end_time');
    }
}
