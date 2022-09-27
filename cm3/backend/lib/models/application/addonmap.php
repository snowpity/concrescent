<?php

namespace CM3_Lib\models\application;

use CM3_Lib\database\Column as cm_Column;
use CM3_Lib\database\ColumnIndex as cm_ColumnIndex;

class addonmap extends \CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName = 'Application_Addon_Maps';
        $this->ColumnDefs = array(
            'badge_type_id'	=> new cm_Column('INT', null, false),
            'addon_id'	=> new cm_Column('INT', null, false)
        );
        $this->IndexDefs = array(
            'primary' => new cm_ColumnIndex(array(
                'badge_type_id' => false,
                'addon_id' => false,
            ), 'primary key')
        );
        $this->PrimaryKeys = array();
        $this->DefaultSearchColumns = array('addon_id');
    }
}
