<?php

namespace CM3_Lib\models\forms;

use CM3_Lib\database\Column as cm_Column;

class custom_text extends CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Forms_Custom_Text';
        $this->ColumnDefs = array(
            'id' 			=> new cm_Column('INT', null, false, true, false, true, null, true),
            'event_id'		=> new cm_Column('INT', null, false, false, false, true),
            'context'		=> new cm_Column('VARCHAR', 3, false),
            'name'			=> new cm_Column('VARCHAR', 63, false),
            'text'			=> new cm_Column('TEXT', null, true)
        );
        $this->IndexDefs = array(
            'primary' => new cm_ColumnIndex(array(
                'event_id' => false,
                'context' => false,
                'name' => false
            ), 'primary key')
        );
        $this->PrimaryKeys = array('id'=>false);
        $this->DefaultSearchColumns = array('name');
    }
}
