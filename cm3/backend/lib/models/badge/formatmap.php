<?php

namespace CM3_Lib\models\badge;

use CM3_Lib\database\Column as cm_Column;

class formatmap extends \CM3_Lib\database\Table
{
    protected function setupTableDefinitions(): void
    {
        $this->TableName  = 'Badge_Format_Maps';
        $this->ColumnDefs = array(
            'context_code'	=> new cm_Column('VARCHAR', '3', false),
            'format_id' 		=> new cm_Column('SMALLINT', null, false),
            'badge_type_id'	=> new cm_Column('INT', null, false),
        );
        $this->IndexDefs = array();
        $this->PrimaryKeys = array(
            'context_code'=>false,
            'badge_type_id'=>false,
            'format_id'=>false,
        );
        $this->DefaultSearchColumns = array('context_code','badge_type_id','format_id');
    }
}
