<?php

namespace CM3_Lib\models\application;

use CM3_Lib\database\Column as cm_Column;

class location_coords extends CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Application_Location_Coords';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('INT', null, false, true, false, true, null, true),
            'map_id'		=> new cm_Column('INT', null, false),
            'location_id'		=> new cm_Column('INT', null, false),
            'coords'        => new cm_Column('VARCHAR', '255', false),
            'date_created'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP'),
            'date_modified'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP', false, 'ON UPDATE CURRENT_TIMESTAMP'),
            'notes'			=> new cm_Column('TEXT', null, true),

        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('id','map_id','location_id','coords');
    }
}
