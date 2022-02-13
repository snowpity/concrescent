<?php

namespace CM3_Lib\models;

use CM3_Lib\database\Column as cm_Column;

class filestore extends \CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'FileStore';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('BIGINT', null, false, true, false, true, null, true),
            'event_id'		=> new cm_Column('INT', null, true),
            'context'		=> new cm_Column('VARCHAR', 3, false),
            'name'           => new cm_Column('VARCHAR', '255', false),
            'meta'			=> new cm_Column('VARCHAR', 500, true),
            'visible'		=> new cm_Column('TEXT', null, true),
            'data'		=> new cm_Column('LONGBLOB', null, false), //Just in case you need 4Gb of storage
            'mimetype' => new cm_Column('VARCHAR', '100', true),
            'date_created'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP'),
            'date_modified'	=> new cm_Column('TIMESTAMP', null, false, false, false, false, 'CURRENT_TIMESTAMP', false, 'ON UPDATE CURRENT_TIMESTAMP')
        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('id','context','name','mimetype', 'date_modified');
    }
}
